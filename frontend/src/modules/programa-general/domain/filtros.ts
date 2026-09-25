import { ActividadUI } from './modelo';

export interface ConteosSenales {
  total: number;
  atrasadas: number;
  conAlerta: number;
  debeIniciar: number;
  enCurso: number;
  futuras: number;
  terminadas: number;
}

export function calcularConteosSenales(actividades: ActividadUI[]): ConteosSenales {
  const soloTareas = actividades.filter((a) => !a.esCapitulo);
  return {
    total: soloTareas.length,
    atrasadas: soloTareas.filter((a) => a.Estado === 'Atrasada').length,
    conAlerta: soloTareas.filter((a) => a.Estado === 'Con Alerta').length,
    debeIniciar: soloTareas.filter((a) => a.Estado === 'Debe Iniciar').length,
    enCurso: soloTareas.filter((a) => a.Estado === 'En Curso').length,
    futuras: soloTareas.filter((a) => a.Estado === 'Actividad Futura').length,
    terminadas: soloTareas.filter((a) => a.Estado === 'Terminada').length,
  };
}

export function filtrarActividades(
  actividades: ActividadUI[],
  busqueda: string,
  estadoFiltro: string | null
): ActividadUI[] {
  const termino = (busqueda ?? '').trim().toLowerCase();
  const filtroActivo = estadoFiltro && estadoFiltro !== 'Todos' && estadoFiltro.trim() !== '' ? estadoFiltro : null;

  return actividades.filter((act) => {
    if (act.esCapitulo) return true; // Los capítulos se preservan para agrupar

    if (filtroActivo && act.Estado !== filtroActivo) {
      return false;
    }

    if (termino === '') return true;

    const matchTexto = (act.Actividad ?? '').toLowerCase().includes(termino);
    const matchCodigo = (act.codigo_actividad ?? '').toLowerCase().includes(termino);
    const matchResponsable = (act.Responsable_AIA ?? '').toLowerCase().includes(termino);
    const matchSubc = (act.Sub_Contratista ?? '').toLowerCase().includes(termino);

    return matchTexto || matchCodigo || matchResponsable || matchSubc;
  });
}

/** Cuenta solo tareas operativas: los capítulos agrupan, no son actividades (spec S05 ronda 1.2, H3). */
export function contarTareasVisibles(actividades: ActividadUI[]): number {
  return actividades.filter((a) => !a.esCapitulo).length;
}
