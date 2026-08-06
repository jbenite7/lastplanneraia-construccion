import type { Opcion } from './listaBuscable'

/**
 * Lógica del filtro de columna con casillas — el equivalente al `changeType` de Programa General.
 *
 * AG Grid Community no trae *set filter* (es Enterprise), así que el modelo y la comparación se
 * definen aquí y el componente solo los pinta. Aparte, así se puede probar sin DOM.
 */

/** Qué guarda el filtro: los valores marcados. `null` (sin modelo) significa que no filtra. */
export interface ModeloFiltroLista {
  valores: string[]
}

/**
 * Etiqueta única para «esta celda no tiene valor». `null`, `undefined` y `''` son lo mismo para
 * quien mira la tabla —una celda en blanco— y ofrecerlos como tres opciones distintas sería ruido.
 */
export const VALOR_VACIO = '(sin valor)'

/** El valor de una celda, como cadena comparable. */
function comoTexto(valor: unknown): string {
  if (valor === null || valor === undefined || valor === '') return VALOR_VACIO
  return String(valor)
}

/**
 * ¿Esta fila pasa el filtro?
 *
 * Una selección vacía **no** filtra: si desmarcar todo dejara la tabla en blanco, el estado
 * intermedio de «voy a marcar tres de cien» sería una pantalla vacía en cada clic.
 */
export function pasaFiltroLista(modelo: ModeloFiltroLista | null, valor: unknown): boolean {
  if (modelo === null || modelo.valores.length === 0) return true
  return modelo.valores.includes(comoTexto(valor))
}

/**
 * Los valores distintos de una columna, ordenados en español y con los vacíos agrupados al final
 * (donde estorban menos: casi nunca son lo que se busca).
 */
export function valoresDistintos(valores: unknown[]): Opcion[] {
  const vistos = new Set(valores.map(comoTexto))
  const hayVacios = vistos.delete(VALOR_VACIO)
  const ordenados = [...vistos].sort((a, b) => a.localeCompare(b, 'es'))
  if (hayVacios) ordenados.push(VALOR_VACIO)
  return ordenados.map((v) => ({ valor: v, etiqueta: v }))
}
