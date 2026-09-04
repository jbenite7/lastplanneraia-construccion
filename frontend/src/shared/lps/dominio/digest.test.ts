import { describe, expect, test } from 'vitest';
import { compilarDigestSemanal } from './digest';
import { configuracionPorDefecto } from './restricciones';

const config = configuracionPorDefecto();
const FECHA_FIJA = new Date('2026-08-31T12:00:00-05:00');

describe('compilarDigestSemanal — caso sin datos', () => {
  test('sin filas -> mensaje "sin bloqueos"', () => {
    const resultado = compilarDigestSemanal([], config, FECHA_FIJA);
    expect(resultado.sinBloqueos).toBe(true);
    expect(resultado.texto).toBe('Excelente. No se encontraron bloqueos críticos en actividades P1 (Ruta Crítica) para esta semana.');
    expect(resultado.bloqueosPorSubcontratista).toEqual({});
  });

  test('filas presentes pero ninguna P1/bloqueada -> también "sin bloqueos"', () => {
    const filas = [
      { prioridad: 'P2', D_y_E: '0%' }, // no es P1: no cuenta aunque tenga ITR incompleto
      { prioridad: 'P1', D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%' }, // P1 pero liberada, sin otro indicio de bloqueo
    ];
    const resultado = compilarDigestSemanal(filas, config, FECHA_FIJA);
    expect(resultado.sinBloqueos).toBe(true);
  });
});

describe('compilarDigestSemanal — agrupación por subcontratista', () => {
  test('agrupa solo filas P1 con bloqueo, por subcontratista, en orden de aparición', () => {
    const filas = [
      { prioridad: 'P1', Sub_Contratista: 'ACME', Consecutivo: '10', Actividad: 'Excavación', D_y_E: '0%' },
      { prioridad: 'P1', Sub_Contratista: 'ACME', Consecutivo: '11', Actividad: 'Cimentación', atraso: 5 },
      { prioridad: 'P1', Sub_Contratista: 'Beta SAS', Consecutivo: '12', Actividad: 'Mampostería', alerta_crisis: 1, D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%' },
    ];
    const resultado = compilarDigestSemanal(filas, config, FECHA_FIJA);

    expect(resultado.sinBloqueos).toBe(false);
    expect(Object.keys(resultado.bloqueosPorSubcontratista)).toEqual(['ACME', 'Beta SAS']);
    expect(resultado.bloqueosPorSubcontratista.ACME).toHaveLength(2);
    expect(resultado.bloqueosPorSubcontratista.ACME[0]).toContain('Actividad #10 (Excavación)');
    expect(resultado.bloqueosPorSubcontratista['Beta SAS'][0]).toContain('Actividad #12 (Mampostería)');
  });

  test('el texto trae encabezado, secciones por responsable y el cierre fijo', () => {
    const filas = [{ prioridad: 'P1', Sub_Contratista: 'ACME', Consecutivo: '10', Actividad: 'Excavación', D_y_E: '0%' }];
    const { texto } = compilarDigestSemanal(filas, config, FECHA_FIJA);

    expect(texto).toContain('📋 REPORTE CONSOLIDADO DE BLOQUEOS LPS - OBRA AIA');
    expect(texto).toContain('Semana de Control: ' + FECHA_FIJA.toLocaleDateString('es-CO'));
    expect(texto).toContain('▶️ RESPONSABLE: ACME');
    expect(texto).toContain('• Actividad #10 (Excavación)');
    expect(texto).toContain('Solicitamos a los líderes de frente asegurar recursos');
  });

  test('fila P1 liberada (ITR completo) pero con causa_no_cumplimiento explícita sí cuenta como bloqueo', () => {
    const filas = [{
      prioridad: 'P1', Sub_Contratista: 'ACME', causa_no_cumplimiento: 'Clima adverso',
      D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%',
    }];
    const resultado = compilarDigestSemanal(filas, config, FECHA_FIJA);
    expect(resultado.sinBloqueos).toBe(false);
    expect(resultado.bloqueosPorSubcontratista.ACME[0]).toContain('Restricción: Clima adverso');
  });

  test('fila sin subcontratista asignado cae bajo "Sin Asignar"', () => {
    const filas = [{ prioridad: 'P1', D_y_E: '0%' }];
    const resultado = compilarDigestSemanal(filas, config, FECHA_FIJA);
    expect(Object.keys(resultado.bloqueosPorSubcontratista)).toEqual(['Sin Asignar']);
  });
});
