/**
 * A4.1 — los pasos del proceso de contratación de una obra, como los edita la pantalla.
 *
 * Lógica pura, sin React ni fetch: reordenar, agregar, quitar y validar son reglas del dominio y se
 * verifican en Vitest sin montar la pantalla. La validación es la misma que hace el servidor
 * (`PasosContratacionService::guardar`) — aquí para responder al instante, allá porque el servidor no
 * puede confiar en el cliente. Si divergen, manda la del servidor.
 */
import type { PasoCatalogo } from './types'

export type PasoEditable = {
  clave: string
  /** Nombre del catálogo de la empresa. */
  nombre: string
  /** Nombre propio de esta obra; vacío = se usa el del catálogo. */
  alias: string
  /** Columna del catálogo legacy de la que salen sus días por paquete; null = días fijos. */
  colLegacy: string | null
  diasFijos: number | null
  diasSugeridos: number | null
}

export function mover(pasos: PasoEditable[], desde: number, hacia: number): PasoEditable[] {
  if (desde < 0 || desde >= pasos.length || hacia < 0 || hacia >= pasos.length) return pasos
  const copia = [...pasos]
  const [p] = copia.splice(desde, 1)
  copia.splice(hacia, 0, p)
  return copia
}

export function quitar(pasos: PasoEditable[], clave: string): PasoEditable[] {
  return pasos.filter((p) => p.clave !== clave)
}

export function agregar(pasos: PasoEditable[], cat: PasoCatalogo, posicion?: number): PasoEditable[] {
  if (pasos.some((p) => p.clave === cat.clave)) return pasos
  const nuevo: PasoEditable = {
    clave: cat.clave,
    nombre: cat.nombre,
    alias: '',
    colLegacy: cat.colLegacy,
    // Un paso sin respaldo del catálogo necesita días sí o sí; arrancar con lo que sugiere el
    // catálogo evita que la pantalla nazca en estado inválido.
    diasFijos: cat.colLegacy === null ? (cat.diasSugeridos ?? 0) : null,
    diasSugeridos: cat.diasSugeridos,
  }
  const copia = [...pasos]
  copia.splice(posicion ?? copia.length, 0, nuevo)
  return copia
}

export function disponibles(cat: PasoCatalogo[], pasos: PasoEditable[]): PasoCatalogo[] {
  const usadas = new Set(pasos.map((p) => p.clave))
  return cat.filter((c) => !usadas.has(c.clave))
}

export function validar(pasos: PasoEditable[]): { ok: boolean; mensaje?: string } {
  if (pasos.length === 0) return { ok: false, mensaje: 'El proceso necesita al menos un paso.' }
  for (const p of pasos) {
    // Cero días sí vale: un paso puede ser un trámite del mismo día (Licify duraba 0-2 en el
    // histórico). Lo que no vale es dejarlo en blanco o en negativo.
    if (p.colLegacy === null && (p.diasFijos === null || !Number.isInteger(p.diasFijos) || p.diasFijos < 0)) {
      return {
        ok: false,
        mensaje: `«${p.alias || p.nombre}» no tiene duración en el catálogo de la empresa: escribe cuántos días dura en esta obra.`,
      }
    }
  }
  return { ok: true }
}

export function aPayload(pasos: PasoEditable[]): { clave: string; alias?: string; diasFijos?: number }[] {
  return pasos.map((p) => ({
    clave: p.clave,
    ...(p.alias.trim() !== '' ? { alias: p.alias.trim() } : {}),
    ...(p.colLegacy === null ? { diasFijos: p.diasFijos ?? 0 } : {}),
  }))
}
