import { describe, it, expect } from 'vitest';
import { normalizarActividades } from './modelo';
import { FilaActividadPg } from '../../../lib/api/esquemas/programa-general';

describe('Dominio S05: modelo y normalizacion de actividades', () => {
  const mockFilas: FilaActividadPg[] = [
    {
      unique_id: 1,
      Consecutivo_en_Programa: '1',
      Id: '1',
      Actividad: 'PRELIMINARES Y CIMENTACIÓN',
      Titulo: 1, // Capítulo
      Fecha_Inicio: '2026-08-01',
      Fecha_Fin: '2026-08-30',
      Ruta_Critica: 0,
      Ejecutado: 0.1,
      Estado: 'En Curso',
    },
    {
      unique_id: 2,
      Consecutivo_en_Programa: '2',
      Id: '1.1',
      Actividad: 'Descapote y limpieza de terreno',
      Titulo: 0, // Tarea
      Fecha_Inicio: '2026-08-01',
      Fecha_Fin: '2026-08-10', // Vencida respecto a 2026-08-23
      Ruta_Critica: 1,
      Ejecutado: 0.25,
      Ejecutado_Teorico: 0.5,
      Estado: 'Atrasada',
      cantidad_ppto: 450,
      unidad: 'm³',
      codigo_actividad: 'PRE-01',
      Responsable_AIA: 'Ing. Restrepo',
      Sub_Contratista: 'Excavaciones SAS',
    },
    {
      unique_id: 3,
      Consecutivo_en_Programa: '3',
      Id: '1.2',
      Actividad: 'Excavación zapatas',
      Titulo: 0,
      Fecha_Inicio: '2026-08-11',
      Fecha_Fin: '2026-08-20', // Vencida pero completada al 100%
      Ruta_Critica: 0,
      Ejecutado: 1.0,
      Estado: 'Terminada',
    },
    {
      unique_id: 4,
      Consecutivo_en_Programa: '4',
      Id: '1.3',
      Actividad: 'Vaciado solados',
      Titulo: 0,
      Fecha_Inicio: '2026-08-25',
      Fecha_Fin: '2026-09-05', // Futura
      Ruta_Critica: 0,
      Ejecutado: 0,
      Estado: 'Actividad Futura',
    },
  ];

  it('identifica capitulos y propaga capituloNombre a las tareas hijas', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');

    expect(normalizadas[0].esCapitulo).toBe(true);
    expect(normalizadas[0].capituloNombre).toBe('PRELIMINARES Y CIMENTACIÓN');

    expect(normalizadas[1].esCapitulo).toBe(false);
    expect(normalizadas[1].capituloNombre).toBe('PRELIMINARES Y CIMENTACIÓN');
    expect(normalizadas[2].capituloNombre).toBe('PRELIMINARES Y CIMENTACIÓN');
  });

  it('calcula avanceRealPct, avanceTeoricoPct y delta correctamente', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');
    const capitulo = normalizadas[0];
    const tarea2 = normalizadas[1];
    const tareaFutura = normalizadas[3];

    expect(capitulo.avanceRealPct).toBe(0);
    expect(capitulo.avanceTeoricoPct).toBe(0);
    expect(capitulo.deltaPct).toBe(0);
    expect(capitulo.deltaTexto).toBe('-');

    expect(tarea2.avanceRealPct).toBe(25.0);
    expect(tarea2.avanceTeoricoPct).toBe(50.0);
    expect(tarea2.deltaPct).toBe(-25.0);
    expect(tarea2.deltaTexto).toBe('-25%');

    // Tarea futura con fecha inicio posterior a fechaReferencia calcula ratio teorico 0%
    expect(tareaFutura.avanceTeoricoPct).toBe(0);
    expect(tareaFutura.deltaPct).toBe(0);
  });

  it('identifica ruta critica segun Ruta_Critica === 1', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');

    expect(normalizadas[1].esRutaCritica).toBe(true);
    expect(normalizadas[2].esRutaCritica).toBe(false);
  });

  it('calcula plazo vencido y dias de vencimiento para tareas no terminadas con fecha fin pasada', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');
    const tarea2 = normalizadas[1];

    expect(tarea2.plazoVencido).toBe(true);
    expect(tarea2.diasVencimiento).toBe(13); // 2026-08-23 - 2026-08-10 = 13 días
  });

  it('no marca plazo vencido en tareas terminadas (100%) aunque la fecha fin haya pasado', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');
    const tarea3 = normalizadas[2];

    expect(tarea3.plazoVencido).toBe(false);
    expect(tarea3.diasVencimiento).toBe(0);
  });

  it('no marca plazo vencido en capitulos', () => {
    const normalizadas = normalizarActividades(mockFilas, 33, '2026-08-23');
    const cap1 = normalizadas[0];

    expect(cap1.plazoVencido).toBe(false);
  });

  it('asigna capituloNombre General si una tarea precede al primer capitulo', () => {
    const filasHuerfanas: FilaActividadPg[] = [
      {
        unique_id: 99,
        Actividad: 'Actividad sin capitulo previo',
        Titulo: 0,
      },
    ];
    const normalizadas = normalizarActividades(filasHuerfanas, 33);
    expect(normalizadas[0].capituloNombre).toBe('General');
    expect(normalizadas[0].esCapitulo).toBe(false);
  });
});
