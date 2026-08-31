import { describe, expect, test } from 'vitest';
import { construirContexto, claveEstado, etiquetaEstado, accionesEstado, esEstadoRutina } from './contexto';
import { configuracionPorDefecto } from './restricciones';
import type { EstadoOperativoLps } from './severidad';

const config = configuracionPorDefecto();

describe('claveEstado / etiquetaEstado / accionesEstado', () => {
  test('claveEstado prioriza "state", luego "key", vacío si no hay estado', () => {
    expect(claveEstado({ state: 'atrasada' })).toBe('atrasada');
    expect(claveEstado({ key: 'terminada' })).toBe('terminada');
    expect(claveEstado(null)).toBe('');
  });

  test('etiquetaEstado prioriza stateView.label, luego el mapa PG, luego estado_operativo/Estado, luego "Control"', () => {
    expect(etiquetaEstado({}, { label: 'Personalizada' })).toBe('Personalizada');
    expect(etiquetaEstado({}, { state: 'atrasada' })).toBe('Atrasada');
    expect(etiquetaEstado({ Estado: 'Fuera de programa\nNota interna' }, null)).toBe('Fuera de programa');
    expect(etiquetaEstado({}, null)).toBe('Control');
  });

  test('accionesEstado prioriza "actions", luego actionItems/compactItems .text, filtrando vacíos', () => {
    expect(accionesEstado({ actions: ['a', null, 'b'] })).toEqual(['a', 'b']);
    expect(accionesEstado({ actionItems: [{ text: 'x' }, { text: '' }] })).toEqual(['x']);
    expect(accionesEstado(null)).toEqual([]);
  });

  test('esEstadoRutina reconoce los estados de rutina y vacío', () => {
    expect(esEstadoRutina('')).toBe(true);
    expect(esEstadoRutina('liberated-control')).toBe(true);
    expect(esEstadoRutina('atrasada')).toBe(false);
  });
});

describe('construirContexto — precedencia SOS/cabecera (T02-AC)', () => {
  test('fila de capítulo: severidad neutral y isHeader true, aunque alerta_crisis esté activa', () => {
    const contexto = construirContexto({ Titulo: 1, alerta_crisis: 1 }, 'programa-general', null, config);
    expect(contexto.isHeader).toBe(true);
    expect(contexto.severity).toBe('neutral');
    expect(contexto.isSOS).toBe(true);
  });

  test('crisis SOS activa en fila normal: severidad critical sin importar el estado operativo', () => {
    const estado: EstadoOperativoLps = { state: 'terminada' };
    const contexto = construirContexto({ alerta_crisis: 1 }, 'programa-general', estado, config);
    expect(contexto.isHeader).toBe(false);
    expect(contexto.severity).toBe('critical');
    expect(contexto.isCrisis).toBe(true);
  });
});

describe('construirContexto — integración PG con horizonte y brecha profunda', () => {
  test('actividad atrasada por estado operativo -> attention, con ITR e isCritical resueltos', () => {
    const estado: EstadoOperativoLps = { state: 'atrasada' };
    const fila = { prioridad: 'P1', Semanas_Inicio: '-2', D_y_E: '0%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%' };
    const contexto = construirContexto(fila, 'programa-general', estado, config);
    expect(contexto.severity).toBe('attention');
    expect(contexto.isCritical).toBe(true);
    expect(contexto.deepGap).toBe(true); // D_y_E en 0% está por debajo de 0.66
    expect(contexto.semanasInicio).toBe(-2);
  });
});

describe('construirContexto — integración PS', () => {
  test('estado "prog-sin-compromiso" en Programación Semanal -> attention', () => {
    const estado: EstadoOperativoLps = { state: 'prog-sin-compromiso', phase: 'programacion' };
    const contexto = construirContexto({}, 'programacion-semanal', estado, config);
    expect(contexto.severity).toBe('attention');
    expect(contexto.phase).toBe('programacion');
  });
});

describe('construirContexto — crisis reactiva', () => {
  test('P1 liberada, iniciada por avance, vencida y con atraso >= 10 -> crisis reactiva', () => {
    // `isReactiveCrisis` exige `isCrisis` (severidad "critical") ya resuelta: en la matriz de plan
    // eso solo ocurre, con la fila liberada, vía los estados 'blocked-overdue-critical' o
    // 'execution-blocked' + P1 (lps_drawer.js:611-642) — la clasificación que produce ese
    // `stateKey` la resuelve el `stateAdapter` de cada `hot.js`, fuera del alcance de esta tarea;
    // aquí se entrega ya resuelta, como hace el drawer legado.
    const fila = {
      prioridad: 'P1',
      Semanas_Inicio: '-3',
      atraso: 12,
      Ejecutado: '10%',
      D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%',
    };
    const contexto = construirContexto(fila, 'programa-general', { state: 'execution-blocked' }, config);
    expect(contexto.isLiberada).toBe(true);
    expect(contexto.isReactiveCrisis).toBe(true);
    expect(contexto.severity).toBe('critical');
  });

  test('Programación Semanal nunca marca crisis reactiva/predictiva (son exclusivas de PG/PI)', () => {
    const fila = {
      prioridad: 'P1',
      Semanas_Inicio: '-3',
      atraso: 12,
      Ejecutado: '10%',
      D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%',
    };
    const contexto = construirContexto(fila, 'programacion-semanal', { state: 'prog-lista-para-confirmar' }, config);
    expect(contexto.isReactiveCrisis).toBe(false);
    expect(contexto.isPredictiveCrisis).toBe(false);
  });
});
