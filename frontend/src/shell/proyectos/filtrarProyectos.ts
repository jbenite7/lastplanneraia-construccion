import type { ProyectoDisponible } from '../../lib/api/esquemas/proyectos';

/**
 * Dominio puro de búsqueda y conteo del selector de proyectos (Tarea 4, S04). Reproduce el
 * comportamiento observable de `views/core/project_selector.view.php` (`updateResults()`):
 * búsqueda por nombre normalizada, sin reordenar, y los cuatro textos de estado. Sin fetch, sin
 * React — el shell (`useSelectorProyecto`/`SelectorProyecto`) consume esto, no al revés.
 */

export function normalizarBusqueda(value: string): string {
  return value
    .trim()
    .toLocaleLowerCase('es-CO')
    .normalize('NFD')
    .replace(/\p{M}/gu, '');
}

export function filtrarProyectos(
  projects: readonly ProyectoDisponible[],
  query: string,
): ProyectoDisponible[] {
  const needle = normalizarBusqueda(query);
  if (needle === '') return [...projects];
  return projects.filter(({ name }) => normalizarBusqueda(name).includes(needle));
}

export function textoConteo(total: number, hasQuery: boolean): string {
  const noun = total === 1 ? 'proyecto' : 'proyectos';
  const state = hasQuery
    ? total === 1 ? 'encontrado' : 'encontrados'
    : total === 1 ? 'disponible' : 'disponibles';
  return `${total} ${noun} ${state}`;
}
