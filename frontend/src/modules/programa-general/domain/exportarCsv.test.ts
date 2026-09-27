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
});
