import { coincide } from './texto'

/**
 * Lógica de la lista con buscador, sin React.
 *
 * Vive aquí y no dentro del componente porque las pruebas del módulo corren en
 * `environment: 'node'` y el proyecto no tiene jsdom: lo que esté en el .tsx no se puede probar.
 * Es el mismo reparto que ya siguen `paquetesState` y `planFechas`.
 */

/** Una opción de la lista. `valor` es lo que viaja al estado; `etiqueta`, lo que se lee y se busca. */
export interface Opcion {
  valor: string
  etiqueta: string
}

/**
 * A partir de cuántas opciones aparece la caja de búsqueda.
 *
 * Decisión del dueño del producto (2026-08-06): una lupa sobre tres opciones es ruido; sobre
 * trescientas es imprescindible. El control se ve igual en ambos casos, solo cambia si trae caja.
 */
export const MINIMO_PARA_BUSCAR = 8

export function necesitaBuscador(cuantasOpciones: number): boolean {
  return cuantasOpciones >= MINIMO_PARA_BUSCAR
}

/** Las opciones que quedan tras teclear. Conserva el orden que traía la lista. */
export function opcionesVisibles(opciones: Opcion[], busqueda: string): Opcion[] {
  return opciones.filter((o) => coincide(o.etiqueta, busqueda))
}

/**
 * Dónde queda el resaltado al pulsar una tecla. La lista es circular: bajar desde el último
 * lleva al primero, que es lo que hace un `<select>` nativo abierto y lo que la gente espera.
 */
export function mueveResaltado(actual: number, tecla: string, total: number): number {
  if (total <= 0) return 0
  switch (tecla) {
    case 'ArrowDown': return (actual + 1) % total
    case 'ArrowUp': return (actual - 1 + total) % total
    case 'Home': return 0
    case 'End': return total - 1
    default: return actual
  }
}

/** Marca o desmarca un valor en una selección múltiple. Devuelve un arreglo nuevo. */
export function alterna(seleccion: string[], valor: string): string[] {
  return seleccion.includes(valor) ? seleccion.filter((v) => v !== valor) : [...seleccion, valor]
}
