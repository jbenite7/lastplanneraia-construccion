import { plural } from './texto'
import type { ImpactoReimport } from './types'

/**
 * ¿Hay algo que contarle al usuario antes de que confirme?
 *
 * Se mira la cantidad de los tres grupos, no `valorAfectado`: un insumo de $0 que se queda sin
 * paquete sigue siendo trabajo que aparece, y en Da Porto los insumos de valor cero existen y son
 * justo de los que hay que desconfiar.
 */
export function hayImpacto(i: ImpactoReimport | null | undefined): boolean {
  if (!i || i.versionActiva === null) return false
  return i.nuevosSinPaquete.cantidad > 0
    || i.desaparecenConPaquete.cantidad > 0
    || i.cambianTipo.cantidad > 0
}

/**
 * Qué se conserva y qué no, en palabras, para el renglón que va antes del botón de confirmar.
 *
 * El texto no promete nada automático: los insumos que cambian de tipo quedan «por revisar», nunca
 * «reasignados». Todo el módulo se sostiene sobre confirmación humana y esta pantalla no es la
 * excepción.
 */
export function textoConserva(i: ImpactoReimport | null | undefined): string {
  const base = 'Las asignaciones a paquete de los insumos que siguen existiendo se conservan, '
    + 'y el plan de fechas no depende de la versión.'
  if (!hayImpacto(i) || !i) {
    return `${base} Con esta versión no se pierde nada del trabajo hecho.`
  }
  const partes: string[] = []
  if (i.nuevosSinPaquete.cantidad > 0) {
    partes.push(`${plural(i.nuevosSinPaquete.cantidad, 'insumo nuevo', 'insumos nuevos')} sin paquete`)
  }
  if (i.desaparecenConPaquete.cantidad > 0) {
    const n = i.desaparecenConPaquete.cantidad
    partes.push(`${plural(n, 'insumo asignado', 'insumos asignados')} que desaparece${n === 1 ? '' : 'n'}`)
  }
  if (i.cambianTipo.cantidad > 0) {
    const n = i.cambianTipo.cantidad
    partes.push(`${plural(n, 'insumo')} que cambia${n === 1 ? '' : 'n'} de tipo`)
  }
  return `${base} Queda por revisar a mano: ${partes.join(' · ')}.`
}
