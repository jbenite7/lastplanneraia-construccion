/**
 * Reglas de la corrección de duración por obra.
 *
 * Vive en `lib/` y no dentro del componente porque en este proyecto las pruebas son de funciones
 * puras: `pdc-app` no tiene pruebas de componente, y la pantalla se cubre con Playwright.
 */

export type Validacion = { ok: true; dias: number } | { ok: false; motivo: string }

export function validarDias(bruto: string): Validacion {
  const t = bruto.trim()
  // El vacío no es cero: un paso sin días escritos es un campo a medio llenar, y guardarlo como
  // cero movería las fechas de la obra sin que nadie lo haya pedido.
  if (t === '') return { ok: false, motivo: 'Escribe cuántos días dura el paso.' }
  if (!/^\d+$/.test(t)) return { ok: false, motivo: 'Los días son un número entero de cero o más.' }
  return { ok: true, dias: Number(t) }
}

export function esCorregido(origen: string): boolean {
  return origen === 'obra'
}
