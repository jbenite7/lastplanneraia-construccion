import type { ArbolInsumo, ArbolItem } from './types'

export type FilaVisor = {
  key: string
  tipo: 'item' | 'insumo'
  nivel: number
  codigo: string
  descripcion: string
  unidad: string | null
  cantidad: number | null
  tipoInsumo: string | null
  valorUnitario: number | null
  valorTotal: number | null
  expandible: boolean
  expandido: boolean
}

// Roll-up de costos: actividad = suma de sus insumos; cada padre = suma de sus hijos.
export function totalesPorItem(items: ArbolItem[], insumos: ArbolInsumo[]): Map<number, number> {
  const totales = new Map<number, number>()
  const porCodigo = new Map(items.map((i) => [i.codigo, i]))
  for (const item of items) totales.set(item.id, 0)
  for (const ins of insumos) {
    totales.set(ins.itemId, (totales.get(ins.itemId) ?? 0) + (ins.valorTotal ?? 0))
  }
  // Propagar de hojas a raíces: recorrer por nivel descendente garantiza hijos antes que padres.
  const porNivelDesc = [...items].sort((a, b) => b.nivel - a.nivel)
  for (const item of porNivelDesc) {
    if (item.codigoPadre === null) continue
    const padre = porCodigo.get(item.codigoPadre)
    if (padre) totales.set(padre.id, (totales.get(padre.id) ?? 0) + (totales.get(item.id) ?? 0))
  }
  return totales
}

export function filasVisibles(items: ArbolItem[], insumos: ArbolInsumo[], expandidos: Set<string>): FilaVisor[] {
  const totales = totalesPorItem(items, insumos)
  const tieneHijos = new Set(items.filter((i) => i.codigoPadre !== null).map((i) => i.codigoPadre as string))
  const insumosPorItem = new Map<number, ArbolInsumo[]>()
  for (const ins of insumos) {
    const lista = insumosPorItem.get(ins.itemId) ?? []
    lista.push(ins)
    insumosPorItem.set(ins.itemId, lista)
  }
  const porCodigo = new Map(items.map((i) => [i.codigo, i]))

  const visible = (item: ArbolItem): boolean => {
    let padre = item.codigoPadre
    while (padre !== null) {
      if (!expandidos.has(padre)) return false
      padre = porCodigo.get(padre)?.codigoPadre ?? null
    }
    return true
  }

  const filas: FilaVisor[] = []
  for (const item of items) {
    if (!visible(item)) continue
    const propios = insumosPorItem.get(item.id) ?? []
    const expandible = tieneHijos.has(item.codigo) || propios.length > 0
    filas.push({
      key: item.codigo,
      tipo: 'item',
      nivel: item.nivel,
      codigo: item.codigo,
      descripcion: item.descripcion,
      unidad: item.unidad,
      cantidad: item.cantidad,
      tipoInsumo: null,
      valorUnitario: null,
      valorTotal: totales.get(item.id) ?? 0,
      expandible,
      expandido: expandidos.has(item.codigo),
    })
    if (item.tipoFila === 'actividad' && expandidos.has(item.codigo)) {
      propios.forEach((ins, idx) => {
        filas.push({
          key: `i:${item.id}:${idx}`,
          tipo: 'insumo',
          nivel: item.nivel + 1,
          codigo: '',
          descripcion: ins.descripcion,
          unidad: ins.unidad,
          cantidad: ins.cantidadTotal,
          tipoInsumo: ins.tipoInsumo,
          valorUnitario: ins.valorUnitario,
          valorTotal: ins.valorTotal,
          expandible: false,
          expandido: false,
        })
      })
    }
  }
  return filas
}
