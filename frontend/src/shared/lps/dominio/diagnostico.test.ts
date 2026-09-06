import { describe, expect, test } from 'vitest';
import { diagnosticoContexto, escaparHtml, brechasCompromisoSemanal } from './diagnostico';
import { construirContexto } from './contexto';
import { configuracionPorDefecto } from './restricciones';
import type { EstadoOperativoLps } from './severidad';

const config = configuracionPorDefecto();

describe('escaparHtml', () => {
  test('escapa &, <, >, ", \'', () => {
    expect(escaparHtml(`<b>"Fase" & 'obra'</b>`)).toBe('&lt;b&gt;&quot;Fase&quot; &amp; &#039;obra&#039;&lt;/b&gt;');
  });

  test('valor vacío/nulo -> cadena vacía', () => {
    expect(escaparHtml(null)).toBe('');
    expect(escaparHtml(undefined)).toBe('');
  });
});

describe('brechasCompromisoSemanal', () => {
  test('lista los tres pendientes cuando no hay ningún dato', () => {
    expect(brechasCompromisoSemanal({})).toEqual([
      'definir compromiso mayor a cero',
      'asignar Responsable AIA',
      'asignar Sub-Contratista',
    ]);
  });

  test('sin pendientes cuando compromiso > 0, responsable y subcontratista están asignados', () => {
    expect(brechasCompromisoSemanal({ Compromiso: '5', Responsable_AIA: 'Juan', Sub_Contratista: 'ACME' })).toEqual([]);
  });

  test('compromiso <= 0 cuenta como pendiente', () => {
    expect(brechasCompromisoSemanal({ Compromiso: '0' })).toContain('definir compromiso mayor a cero');
  });
});

describe('diagnosticoContexto — PS (estados prog-*/cal-*, T02-AC "PS program/qualification states")', () => {
  test.each<[string, EstadoOperativoLps, Record<string, unknown>, string[]]>([
    ['prog-bloqueo-critico-sin-compromiso', { state: 'prog-bloqueo-critico-sin-compromiso' }, {}, ['🚨', 'Escalar liberación antes de confirmar producción']],
    ['prog-ejecucion-con-restricciones crítico', { state: 'prog-ejecucion-con-restricciones' }, { prioridad: 'P1', D_y_E: '0%' }, ['⚠️', 'Escalar continuidad del frente']],
    ['prog-condiciones-pendientes', { state: 'prog-condiciones-pendientes' }, {}, ['🟠', 'cerrar condiciones de habilitación']],
    ['prog-sin-compromiso lista las brechas', { state: 'prog-sin-compromiso' }, {}, ['🟡', 'definir compromiso mayor a cero']],
    ['prog-lista-para-confirmar', { state: 'prog-lista-para-confirmar' }, {}, ['🟢', 'listos']],
    ['cal-incumplida-critica', { state: 'cal-incumplida-critica' }, { Compromiso: '10', Ejecutado_Real: '4' }, ['🔴', 'Registrar CNC']],
    ['cal-sin-calificar', { state: 'cal-sin-calificar' }, {}, ['🟡', 'falta registrar ejecutado real']],
    ['cal-cumplida-control', { state: 'cal-cumplida-control' }, {}, ['🟢', 'cumplido']],
    ['estado desconocido -> mensaje de control por defecto', { state: 'algo-nuevo' }, {}, ['🟢', 'sin alertas críticas activas']],
  ])('%s', (_titulo, estado, fila, fragmentos) => {
    const contexto = construirContexto(fila, 'programacion-semanal', estado, config);
    const html = diagnosticoContexto(contexto);
    fragmentos.forEach((fragmento) => expect(html).toContain(fragmento));
  });
});

describe('diagnosticoContexto — PG/PI (por severidad) y SOS', () => {
  test('SOS activo tiene precedencia sobre cualquier severidad', () => {
    const contexto = construirContexto({ alerta_crisis: 1 }, 'programa-general', { state: 'terminada' }, config);
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('CRISIS ACTIVA POR ESCALAMIENTO SOS');
  });

  test('severidad crítica sin crisis reactiva describe el bloqueo con el horizonte en semanas', () => {
    const fila = { prioridad: 'P1', Semanas_Inicio: '2', D_y_E: '0%' };
    const contexto = construirContexto(fila, 'programa-general', { state: 'execution-blocked' }, config);
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('BLOQUEO CRÍTICO');
    expect(html).toContain('inicia en 2 semana(s)');
  });

  test('severidad attention describe atención operativa prioritaria', () => {
    const contexto = construirContexto({}, 'programa-general', { state: 'atrasada' }, config);
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('Atención operativa prioritaria');
  });

  test('severidad info describe preparación temprana', () => {
    // Horizonte 4-6 semanas y no liberada (D_y_E sin liberar) -> severidadPlan da 'info'.
    const contexto = construirContexto({ Semanas_Inicio: '5', D_y_E: '0%' }, 'programa-general', null, config);
    expect(contexto.severity).toBe('info');
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('Preparación temprana');
    expect(html).toContain('Inicia en 5 semana(s)');
  });

  test('P1 liberada sin ninguna severidad activa: "P1 EN CONTROL"', () => {
    const fila = { prioridad: 'P1', D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%' };
    const contexto = construirContexto(fila, 'programa-general', { state: 'en-curso' }, config);
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('P1 EN CONTROL');
  });

  test('sin bloqueos ni P1: seguimiento rutinario', () => {
    const contexto = construirContexto({}, 'programa-general', { state: 'en-curso' }, config);
    const html = diagnosticoContexto(contexto);
    expect(html).toContain('SEGUIMIENTO RUTINARIO');
  });
});
