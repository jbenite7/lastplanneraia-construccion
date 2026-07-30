/**
 * Las palabras del tablero de vencimientos. Ninguna regla de fecha vive aquí a propósito: quién está
 * vencido lo decide el servidor (SeguimientoService::clasificarVencimiento), porque dos usuarios en
 * husos distintos tienen que ver el mismo vencido y porque el semáforo del plan usa esa misma regla.
 */
export type EstadoVencimiento = 'vencido' | 'sem1' | 'sem2' | 'sem3' | 'sem6' | 'adelante' | 'sin_fecha'

/**
 * Los cortes que se listan, del más urgente al menos. «Más adelante» no está: el servidor lo cuenta
 * —para que la suma cuadre— pero no manda sus filas, y una sección vacía permanente sería ruido.
 * «Sin fecha programada» va al final y sí se lista: es el hueco que el plan todavía no fechó, y
 * esconderlo es exactamente lo que hace que un paquete se pierda sin que nadie lo note.
 */
export const CORTES: { id: EstadoVencimiento; etiqueta: string }[] = [
  { id: 'vencido', etiqueta: 'Vencido' },
  { id: 'sem1', etiqueta: 'Vence en 1 semana' },
  { id: 'sem2', etiqueta: 'Vence en 2 semanas' },
  { id: 'sem3', etiqueta: 'Vence en 3 semanas' },
  { id: 'sem6', etiqueta: 'Vence en 6 semanas' },
  { id: 'sin_fecha', etiqueta: 'Sin fecha programada' },
]

const ETIQUETAS: Record<string, string> = Object.fromEntries(
  [
    ...CORTES,
    { id: 'adelante', etiqueta: 'Más adelante' },
    { id: 'cumplido', etiqueta: 'Cumplido' },
  ].map((c) => [c.id, c.etiqueta]),
)

/** Un corte que no conocemos se muestra crudo: desaparecer de la pantalla es peor que verse raro. */
export function etiquetaCorte(id: string): string {
  return ETIQUETAS[id] ?? id
}

/** La clase del semáforo. Cadena vacía para lo desconocido: sin color inventado. */
export function claseCorte(id: string): string {
  return ETIQUETAS[id] === undefined ? '' : `pdc-venc--${id}`
}

/** Los días de retraso en palabras. `null` no dice nada: un «0 días» suelto se lee como dato faltante. */
export function textoDesfase(dias: number | null): string {
  if (dias === null) return ''
  return `${dias} ${dias === 1 ? 'día' : 'días'} tarde`
}

/**
 * Lo que el tablero NO está mirando, dicho en pantalla.
 *
 * Un tablero vacío y un tablero ciego se ven igual. Este texto es la diferencia, y por eso nombra
 * además el motivo: «sin frente» se arregla decidiendo, «pendiente de recalcular» con un botón.
 */
export function textoSinFechas(s: { paquetes: number; sinFrente: number; sinCalcular: number }): string {
  if (s.paquetes <= 0) return ''
  const motivos: string[] = []
  if (s.sinFrente > 0) motivos.push(`${s.sinFrente} sin frente`)
  if (s.sinCalcular > 0) {
    motivos.push(
      s.sinCalcular === 1
        ? '1 amarrado pendiente de recalcular'
        : `${s.sinCalcular} amarrados pendientes de recalcular`,
    )
  }
  const cuantos = s.paquetes === 1 ? '1 paquete' : `${s.paquetes} paquetes`
  return `Este tablero no está mirando ${cuantos} sin fechas: ${motivos.join(' y ')}.`
}
