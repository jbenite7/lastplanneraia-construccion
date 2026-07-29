/**
 * El historial de versiones deja de ser una tabla que solo se mira: desde ahí se salta al visor y
 * al comparador, y se decide cuál presupuesto rige. Toda la lógica de esos puentes vive aquí para
 * poder verificarla sin montar la pantalla.
 */

/** Dos versiones y no más: comparar es enfrentar A contra B, no una lista. */
export const MAX_COMPARAR = 2

/** Si al marcar esta versión se pasaría del máximo. La casilla se bloquea, no se desmarca otra. */
export function puedeMarcar(seleccion: number[], id: number): boolean {
  return seleccion.includes(id) || seleccion.length < MAX_COMPARAR
}

/**
 * Marca o desmarca una versión. Al intentar una tercera devuelve la selección **igual**: rotar la
 * más vieja fuera sería silencioso y dejaría comparando algo que nadie eligió.
 */
export function alternarSeleccion(seleccion: number[], id: number): number[] {
  if (seleccion.includes(id)) return seleccion.filter((x) => x !== id)
  if (seleccion.length >= MAX_COMPARAR) return seleccion
  return [...seleccion, id]
}

/** El visor, con esa versión ya cargada. Un clic en la fila: sin modal de por medio. */
export function rutaVisor(versionId: number): string {
  return `/ensamble/presupuesto?version=${versionId}`
}

/**
 * El comparador con las dos versiones enfrentadas, o null si todavía no hay dos (y entonces el
 * botón «Comparar» va deshabilitado).
 *
 * La más antigua va como A y la más reciente como B —los ids son crecientes— para que el signo del
 * delta se lea como el equipo lo espera: positivo es sobrecosto respecto de lo que había antes.
 */
export function rutaComparar(seleccion: number[]): string | null {
  if (seleccion.length !== MAX_COMPARAR) return null
  const [a, b] = [...seleccion].sort((x, y) => x - y)
  return `/ensamble/comparar?a=${a}&b=${b}`
}
