import type { ActividadDiff } from './types'

export type FilaComparativo = ActividadDiff & {
  key: string
  expandible: boolean
  expandido: boolean
}

/** Filas de actividades visibles según el set de códigos expandidos (jerarquía por codigo/codigoPadre). */
export function filasComparativoVisibles(actividades: ActividadDiff[], expandidos: Set<string>): FilaComparativo[] {
  const porCodigo = new Map(actividades.map((a) => [a.codigo, a]))
  const tieneHijos = new Set(actividades.filter((a) => a.codigoPadre !== null).map((a) => a.codigoPadre as string))

  const visible = (a: ActividadDiff): boolean => {
    let padre = a.codigoPadre
    while (padre !== null) {
      if (!expandidos.has(padre)) return false
      padre = porCodigo.get(padre)?.codigoPadre ?? null
    }
    return true
  }

  const filas: FilaComparativo[] = []
  for (const a of actividades) {
    if (!visible(a)) continue
    filas.push({
      ...a,
      key: a.codigo,
      expandible: tieneHijos.has(a.codigo),
      expandido: expandidos.has(a.codigo),
    })
  }
  return filas
}

/** Clase CSS para colorear un delta: sobrecosto (sube) / ahorro (baja) / neutro. */
export function claseDelta(deltaValor: number, estado: string): string {
  if (estado === 'igual' || deltaValor === 0) return ''
  return deltaValor > 0 ? 'pdc-cmp-sobrecosto' : 'pdc-cmp-ahorro'
}
