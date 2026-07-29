import type { DeltaPaquete } from './types'

/**
 * El movimiento de un paquete en palabras.
 *
 * El signo de `diasMovidos` no se enseña nunca crudo: «-9 días» se lee como un error de la pantalla,
 * y la dirección —si el paquete se atrasa o se adelanta— es justo lo que hay que entender antes de
 * aplicar. El convenio del backend es el de `PlanFechasService::desfases()`: positivo = el frente se
 * atrasó, negativo = se adelantó.
 */
export function etiquetaMovimiento(m: DeltaPaquete): string {
  const dias = Math.abs(m.diasMovidos)
  const direccion = m.diasMovidos >= 0 ? 'se atrasa' : 'se adelanta'
  const unidad = dias === 1 ? 'día' : 'días'
  // Sin arranque previo (paquete amarrado pero nunca calculado) no hay un «desde» que enseñar.
  // Fingir uno pondría en pantalla una fecha que nunca existió.
  const arranque = m.arranqueActual === null
    ? `arranque ${m.arranqueNuevo}`
    : `arranque ${m.arranqueActual} → ${m.arranqueNuevo}`
  return `${direccion} ${dias} ${unidad}: ${arranque}`
}

/**
 * El titular del panel de delta.
 *
 * `pasosProtegidos` son los pasos con fecha real: los que NO se van a mover. Se cuentan y se dicen
 * porque son la garantía que hace segura esta operación — lo programado se recalcula, lo ocurrido
 * nunca se borra.
 */
export function resumenDelta(movidos: DeltaPaquete[]): {
  paquetes: number
  pasosProtegidos: number
  atrasan: number
  adelantan: number
} {
  return {
    paquetes: movidos.length,
    pasosProtegidos: movidos.reduce((n, m) => n + m.pasosConFechaReal, 0),
    atrasan: movidos.filter((m) => m.diasMovidos >= 0).length,
    adelantan: movidos.filter((m) => m.diasMovidos < 0).length,
  }
}
