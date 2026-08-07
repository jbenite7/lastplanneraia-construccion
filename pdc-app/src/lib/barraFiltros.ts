/**
 * Traduce el modelo de filtros de AG Grid a texto que se pueda leer en un chip.
 *
 * Existe porque el módulo permite filtrar por dos vías —los controles de arriba y el embudo de
 * cada columna— y sin esto la tabla se queda vacía sin decir por qué.
 */

export interface Chip {
  id: string
  texto: string
}

/** Cuántos valores marcados se enumeran antes de resumir. Cuatro ya no caben en una barra. */
const MAXIMO_VALORES_ENUMERADOS = 3

const CONDICIONES: Record<string, string> = {
  contains: 'contiene', notContains: 'no contiene',
  equals: 'igual a', notEqual: 'distinto de',
  startsWith: 'empieza por', endsWith: 'termina en',
  blank: 'vacío', notBlank: 'no vacío',
  lessThan: 'menor que', greaterThan: 'mayor que',
  lessThanOrEqual: 'menor o igual que', greaterThanOrEqual: 'mayor o igual que',
  inRange: 'entre',
}

function textoDeCondicion(m: { type?: string; filter?: unknown; filterType?: string }): string {
  const condicion = CONDICIONES[m.type ?? ''] ?? (m.type ?? 'filtrado')
  if (m.filter === undefined || m.filter === null) return condicion
  const valor = typeof m.filter === 'number' ? m.filter.toLocaleString('es-CO') : `«${String(m.filter)}»`
  return `${condicion} ${valor}`
}

/**
 * Un chip por columna filtrada. `nombres` mapea id de columna → encabezado; una columna que no esté
 * en el mapa se anuncia con su id, que es feo pero cierto — nunca «undefined».
 */
export function chipsDeGrilla(
  modeloGrilla: Record<string, unknown>,
  nombres: Record<string, string>,
): Chip[] {
  return Object.entries(modeloGrilla).map(([id, modelo]) => {
    const nombre = nombres[id] ?? id
    const m = modelo as { valores?: string[] } & { type?: string; filter?: unknown; filterType?: string }
    if (Array.isArray(m.valores)) {
      const texto = m.valores.length > MAXIMO_VALORES_ENUMERADOS
        ? `${m.valores.length} valores`
        : m.valores.join(', ')
      return { id, texto: `${nombre}: ${texto}` }
    }
    return { id, texto: `${nombre}: ${textoDeCondicion(m)}` }
  })
}
