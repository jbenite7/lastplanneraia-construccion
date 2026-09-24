import { describe, it, expect } from 'vitest';
import { calcularConteosSenales, filtrarActividades } from './filtros';
import { ActividadUI } from './modelo';

describe('Dominio S05: filtros y senales', () => {
  const actividadesMock: ActividadUI[] = [
    {
      unique_id: 1,
      Consecutivo_en_Programa: '1',
      Actividad: 'CAPÍTULO I: ESTRUCTURA',
      Titulo: 1,
      esCapitulo: true,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'En Curso',
      avanceRealPct: 30,
      avanceTeoricoPct: 50,
      deltaPct: -20,
      deltaTexto: '-20%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
    },
    {
      unique_id: 2,
      Consecutivo_en_Programa: '2',
      Actividad: 'Cimentación zapatas',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'Atrasada',
      codigo_actividad: 'EST-01',
      Responsable_AIA: 'Ing. Restrepo',
      Sub_Contratista: 'Excavaciones SAS',
      avanceRealPct: 15,
      avanceTeoricoPct: 50,
      deltaPct: -35,
      deltaTexto: '-35%',
      esRutaCritica: true,
      plazoVencido: true,
      diasVencimiento: 10,
    },
    {
      unique_id: 3,
      Consecutivo_en_Programa: '3',
      Actividad: 'Columnas primer nivel',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'Con Alerta',
      codigo_actividad: 'EST-02',
      Responsable_AIA: 'Ing. Restrepo',
      Sub_Contratista: 'Concretos del Valle',
      avanceRealPct: 40,
      avanceTeoricoPct: 50,
      deltaPct: -10,
      deltaTexto: '-10%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
    },
    {
      unique_id: 4,
      Consecutivo_en_Programa: '4',
      Actividad: 'Muros contención',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'Debe Iniciar',
      codigo_actividad: 'EST-03',
      Responsable_AIA: 'Arq. Gomez',
      Sub_Contratista: 'Estructuras SAS',
      avanceRealPct: 0,
      avanceTeoricoPct: 0,
      deltaPct: 0,
      deltaTexto: '0%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
    },
    {
      unique_id: 5,
      Consecutivo_en_Programa: '5',
      Actividad: 'Vigas y losas',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'En Curso',
      codigo_actividad: 'EST-04',
      Responsable_AIA: 'Ing. Restrepo',
      Sub_Contratista: 'Concretos del Valle',
      avanceRealPct: 55,
      avanceTeoricoPct: 50,
      deltaPct: 5,
      deltaTexto: '+5%',
      esRutaCritica: true,
      plazoVencido: false,
      diasVencimiento: 0,
    },
    {
      unique_id: 6,
      Consecutivo_en_Programa: '6',
      Actividad: 'Acabados losa superior',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'Actividad Futura',
      codigo_actividad: 'EST-05',
      Responsable_AIA: 'Arq. Gomez',
      Sub_Contratista: 'Acabados SAS',
      avanceRealPct: 0,
      avanceTeoricoPct: 0,
      deltaPct: 0,
      deltaTexto: '0%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
    },
    {
      unique_id: 7,
      Consecutivo_en_Programa: '7',
      Actividad: 'Localización y replanteo preliminar',
      Titulo: 0,
      esCapitulo: false,
      capituloNombre: 'CAPÍTULO I: ESTRUCTURA',
      Estado: 'Terminada',
      codigo_actividad: 'PRE-00',
      Responsable_AIA: 'Ing. Restrepo',
      Sub_Contratista: 'Excavaciones SAS',
      avanceRealPct: 100,
      avanceTeoricoPct: 100,
      deltaPct: 0,
      deltaTexto: '0%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
    },
  ];

  it('calcula conteos de senales excluyendo los capitulos', () => {
    const conteos = calcularConteosSenales(actividadesMock);

    expect(conteos.total).toBe(6); // 7 items - 1 capítulo
    expect(conteos.atrasadas).toBe(1);
    expect(conteos.conAlerta).toBe(1);
    expect(conteos.debeIniciar).toBe(1);
    expect(conteos.enCurso).toBe(1);
    expect(conteos.futuras).toBe(1);
    expect(conteos.terminadas).toBe(1);
  });

  it('preserva capitulos en el filtrado independientemente de la busqueda o estado', () => {
    const filtradas = filtrarActividades(actividadesMock, 'xyzNoExiste', 'Atrasada');
    expect(filtradas).toHaveLength(1);
    expect(filtradas[0].esCapitulo).toBe(true);
    expect(filtradas[0].Actividad).toBe('CAPÍTULO I: ESTRUCTURA');
  });

  it('filtra por termino de busqueda en Actividad, codigo, responsable o subcontratista', () => {
    // Por nombre de actividad
    const porNombre = filtrarActividades(actividadesMock, 'cimentación', null);
    expect(porNombre.some((a) => a.unique_id === 2)).toBe(true);

    // Por código
    const porCodigo = filtrarActividades(actividadesMock, 'est-04', null);
    expect(porCodigo.some((a) => a.unique_id === 5)).toBe(true);

    // Por Responsable AIA
    const porResp = filtrarActividades(actividadesMock, 'Gomez', null);
    expect(porResp.some((a) => a.unique_id === 4)).toBe(true);
    expect(porResp.some((a) => a.unique_id === 6)).toBe(true);

    // Por Subcontratista
    const porSubc = filtrarActividades(actividadesMock, 'Concretos', null);
    expect(porSubc.some((a) => a.unique_id === 3)).toBe(true);
    expect(porSubc.some((a) => a.unique_id === 5)).toBe(true);
  });

  it('filtra por estado canonico', () => {
    const soloAtrasadas = filtrarActividades(actividadesMock, '', 'Atrasada');
    // 1 capítulo + 1 tarea atrasada
    expect(soloAtrasadas).toHaveLength(2);
    expect(soloAtrasadas.find((a) => !a.esCapitulo)?.Estado).toBe('Atrasada');
  });

  it('retorna todas las actividades cuando no hay filtro de texto ni de estado', () => {
    const todas = filtrarActividades(actividadesMock, '', null);
    expect(todas).toHaveLength(7);

    const todasConAlias = filtrarActividades(actividadesMock, '   ', 'Todos');
    expect(todasConAlias).toHaveLength(7);
  });

  it('combina busqueda de texto con filtro de estado', () => {
    const combinadas = filtrarActividades(actividadesMock, 'Restrepo', 'En Curso');
    // 1 capítulo + 1 tarea (vigas y losas, resp Restrepo, en curso)
    expect(combinadas).toHaveLength(2);
    expect(combinadas.find((a) => !a.esCapitulo)?.unique_id).toBe(5);
  });
});
