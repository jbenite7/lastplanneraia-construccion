import type { FilaSeguimiento, FiltrosSeguimiento } from './types'

const ESTADOS: Record<string, string> = {
  sin_empezar: 'Sin empezar',
  en_curso: 'En curso',
  terminado: 'Terminado',
}

/** Un estado que no conocemos se muestra crudo: desaparecer de la pantalla es peor que verse raro. */
export function etiquetaEstado(estado: string): string {
  return ESTADOS[estado] ?? estado
}

/**
 * El desfase en palabras. `null` es «no hay contra que medir» (paso pendiente, o paso con avance
 * cuyo plan aun no se ha recalculado) y no dice nada; cero se dice «A tiempo», porque un «0 días»
 * suelto se lee como si faltara el dato.
 */
export function etiquetaDesfaseDias(dias: number | null): string {
  if (dias === null) return ''
  if (dias === 0) return 'A tiempo'
  const n = Math.abs(dias)
  const unidad = n === 1 ? 'día' : 'días'
  return dias > 0 ? `${n} ${unidad} tarde` : `${n} ${unidad} antes`
}

/**
 * Los cuatro filtros de la lista, acumulativos.
 *
 * «Mis paquetes» sin usuario conocido devuelve vacio, no todo: si no sabemos quien eres, mostrar la
 * obra entera bajo una etiqueta que dice «mios» es mentir sobre lo que se esta viendo.
 */
export function filtrarSeguimiento(
  filas: FilaSeguimiento[],
  f: FiltrosSeguimiento,
  usuarioId: number | null,
): FilaSeguimiento[] {
  return filas.filter((fila) => {
    if (f.soloMios && (usuarioId === null || fila.responsableUserId !== usuarioId)) return false
    if (f.frente !== '' && fila.frenteNombre !== f.frente) return false
    if (f.estado !== '' && fila.estado !== f.estado) return false
    if (f.soloAtrasados && !fila.atrasado) return false
    return true
  })
}

/** Los frentes que de verdad aparecen en los datos, para poblar el desplegable sin inventar opciones. */
export function frentesDeSeguimiento(filas: FilaSeguimiento[]): string[] {
  return [...new Set(filas.map((f) => f.frenteNombre).filter((n) => n !== ''))].sort()
}
