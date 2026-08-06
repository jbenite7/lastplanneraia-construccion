/**
 * Texto de interfaz: plurales y búsqueda.
 *
 * Vive aparte de `agGrid.ts` porque no tiene nada que ver con las tablas: lo usan mensajes,
 * contadores y buscadores de las seis pantallas.
 */

/**
 * «N cosa» o «N cosas», con separador de miles.
 *
 * El «(s)» aparecía en 24 sitios del módulo —«11 paquete(s)», «0 seleccionado(s)», «1343 fila(s)»—
 * y es la marca de que nadie se ocupó del detalle. De paso resuelve el separador: el conteo del
 * visor se leía «1343» mientras el dinero de la misma pantalla sí agrupaba.
 *
 * El cero va en plural: «0 paquete» no lo dice nadie.
 */
export function plural(n: number, singular: string, pluralExplicito?: string): string {
  const palabra = n === 1 ? singular : (pluralExplicito ?? `${singular}s`)
  return `${n.toLocaleString('es-CO')} ${palabra}`
}

/**
 * Las dos magnitudes de insumos que el módulo cuenta, y sus dos palabras.
 *
 * En el comité del 2026-07-29 el módulo anunció 820 insumos, el dueño del producto esperaba ~390, y
 * explicar la diferencia tomó tres turnos de conversación; más adelante apareció un 396 en otra
 * pantalla. Los tres números eran verdaderos: ninguno decía de qué hablaba. Aquí viven las dos
 * palabras, en un solo sitio, para que no se separen con el uso.
 */
export type MagnitudInsumos = 'apariciones' | 'distintos'

export const PALABRA_INSUMOS: Record<MagnitudInsumos, string> = {
  apariciones: 'apariciones en APU',
  distintos: 'insumos distintos',
}

const PALABRA_INSUMOS_SINGULAR: Record<MagnitudInsumos, string> = {
  apariciones: 'aparición en APU',
  distintos: 'insumo distinto',
}

/** «820 apariciones en APU» · «396 insumos distintos». Nunca «820 insumos» a secas. */
export function contarInsumos(n: number, magnitud: MagnitudInsumos): string {
  const palabra = n === 1 ? PALABRA_INSUMOS_SINGULAR[magnitud] : PALABRA_INSUMOS[magnitud]
  return `${n.toLocaleString('es-CO')} ${palabra}`
}

/**
 * Centinela para proteger la ñ, sacado del área de uso privado de Unicode.
 *
 * Tiene que ser un carácter que no aparezca jamás en una descripción de insumo: con un espacio o un
 * guion, deshacer el cambio convertiría todos los espacios del texto en eñes.
 */
const CENTINELA_ENIE = ''

/**
 * Minúsculas y sin acentos, para comparar lo que el usuario teclea con lo que hay en pantalla.
 *
 * La **ñ se conserva**: `NFD` la descompone en `n` + tilde y quitar los diacríticos convertiría
 * «CAÑO» en «cano», que es otra palabra.
 */
export function normaliza(s: string): string {
  return s
    .toLowerCase()
    .replace(/ñ/g, CENTINELA_ENIE)
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(new RegExp(CENTINELA_ENIE, 'g'), 'ñ')
}

/** Filtra por subcadena, sin distinguir mayúsculas ni acentos. Una búsqueda vacía no filtra. */
export function filtraPorTexto<T>(filas: T[], busqueda: string, campo: (f: T) => string): T[] {
  const q = normaliza(busqueda.trim())
  if (q === '') return filas
  return filas.filter((f) => normaliza(campo(f)).includes(q))
}

/**
 * ¿El texto contiene lo buscado? Sin distinguir mayúsculas ni acentos, conservando la ñ.
 *
 * Es el mismo criterio que `filtraPorTexto` aplicado a un solo valor: lo necesitan la lista
 * buscable, el filtro de columna y el buscador rápido, y tenerlo en un solo sitio es lo que
 * garantiza que los tres respondan igual a lo que el usuario teclea.
 */
export function coincide(texto: string, busqueda: string): boolean {
  const q = normaliza(busqueda.trim())
  if (q === '') return true
  return normaliza(texto).includes(q)
}
