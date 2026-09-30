import { describe, expect, it } from 'vitest';
import { ActividadUI } from './modelo';
import { generarContenidoCsv13Cols } from './exportarCsv';

function crearActividad(unique_id: number, Semanas_Inicio: number | null): ActividadUI {
  return {
    unique_id,
    Actividad: `Actividad ${unique_id}`,
    Titulo: 0,
    Estado: 'En Curso',
    Semanas_Inicio,
    esCapitulo: false,
    capituloNombre: 'General',
    avanceRealPct: 0,
    avanceTeoricoPct: 0,
    deltaPct: 0,
    deltaTexto: '0%',
    esRutaCritica: false,
    plazoVencido: false,
    diasVencimiento: 0,
  };
}

describe('generarContenidoCsv13Cols', () => {
  it('exporta el desfase crudo, conserva el cero y deja vacío el valor nulo', () => {
    const contenido = generarContenidoCsv13Cols([
      crearActividad(1, -9),
      crearActividad(2, 0),
      crearActividad(3, 3),
      crearActividad(4, null),
    ]);
    const filas = contenido.replace(/^\uFEFF/, '').split('\r\n');
    const semanasInicio = filas.slice(1).map((fila) => fila.split(',')[5]);

    expect(semanasInicio).toEqual(['-9', '0', '3', '']);
  });

  it('exporta el código guardado, o si falta la numeración WBS, o si falta el consecutivo', () => {
    const conCodigo = { ...crearActividad(1, 0), codigo_actividad: 'EST-01', Id: '1.1', Consecutivo_en_Programa: '11' };
    const soloWbs = { ...crearActividad(2, 0), codigo_actividad: '', Id: '1.2', Consecutivo_en_Programa: '12' };
    const soloConsecutivo = { ...crearActividad(3, 0), codigo_actividad: '', Id: '', Consecutivo_en_Programa: '13' };

    const contenido = generarContenidoCsv13Cols([conCodigo, soloWbs, soloConsecutivo]);
    const codigos = contenido.replace(/^\uFEFF/, '').split('\r\n').slice(1).map((fila) => fila.split(',')[1]);

    expect(codigos).toEqual(['EST-01', '1.2', '13']);
  });
});
