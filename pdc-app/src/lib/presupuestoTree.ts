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
  /** Ruta de ancestros («CAPÍTULO › SUBCAPÍTULO › …»): en modo tabla es lo único que sitúa la fila. */
  ruta: string
}

/**
 * Filtros del visor. El texto busca en descripción y código; `tipoInsumo` y `unidad` son de valor
 * exacto (los valores que existan en el presupuesto). `plano` cambia el árbol por una tabla llana,
 * que es donde tiene sentido ordenar y filtrar por columna sin romper la jerarquía.
 */
export type FiltroVisor = {
  texto?: string
  tipoInsumo?: string
  unidad?: string
  plano?: boolean
}

/**
 * Los cinco niveles del presupuesto, con el nombre que usa el equipo (ver el glosario del dominio:
 * capítulos > subcapítulos > grupos > actividades, y el APU de cada actividad la descompone en
 * insumos). El número es la profundidad: `nivel` de cada fila cuenta segmentos del código.
 */
export const NIVELES_PRESUPUESTO = [
  { valor: 1, etiqueta: 'Capítulo' },
  { valor: 2, etiqueta: 'Subcapítulo' },
  { valor: 3, etiqueta: 'Grupo' },
  { valor: 4, etiqueta: 'Actividad' },
  { valor: 5, etiqueta: 'Insumo' },
] as const

/** El nivel más profundo: el insumo, que cuelga de la actividad y no de un código propio. */
export const NIVEL_INSUMO = 5

/**
 * Qué hay que tener abierto para ver el árbol hasta un nivel dado.
 *
 * Devuelve el mismo `Set<string>` de códigos que produce hacer clic a mano —lo que `filasVisibles`
 * ya sabe consumir—, así que el selector de nivel y el clic conviven sin estorbarse: elegir un
 * nivel siembra el conjunto, y a partir de ahí se puede seguir abriendo o cerrando ramas sueltas.
 *
 * Una fila es visible cuando todos sus ancestros están abiertos, así que para ver el nivel N hay
 * que abrir todo lo que esté por encima: los ítems con `nivel < N`. Con N = insumo eso incluye a
 * las actividades, que es de donde cuelgan.
 */
export function expandirHastaNivel(
  items: Array<{ codigo: string; nivel: number }>,
  nivel: number,
): Set<string> {
  return new Set(items.filter((i) => i.nivel < nivel).map((i) => i.codigo))
}

/** Normaliza para buscar como busca la gente: sin tildes y sin importar mayúsculas. */
const norm = (s: string): string =>
  s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase()

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

export function filasVisibles(
  items: ArbolItem[],
  insumos: ArbolInsumo[],
  expandidos: Set<string>,
  filtro: FiltroVisor = {},
): FilaVisor[] {
  const totales = totalesPorItem(items, insumos)
  const tieneHijos = new Set(items.filter((i) => i.codigoPadre !== null).map((i) => i.codigoPadre as string))
  const insumosPorItem = new Map<number, ArbolInsumo[]>()
  for (const ins of insumos) {
    const lista = insumosPorItem.get(ins.itemId) ?? []
    lista.push(ins)
    insumosPorItem.set(ins.itemId, lista)
  }
  const porCodigo = new Map(items.map((i) => [i.codigo, i]))

  const rutaDe = (item: ArbolItem, incluirse = false): string => {
    const partes: string[] = incluirse ? [item.descripcion] : []
    let padre = item.codigoPadre
    while (padre !== null) {
      const p = porCodigo.get(padre)
      if (!p) break
      partes.unshift(p.descripcion)
      padre = p.codigoPadre
    }
    return partes.join(' › ')
  }

  // ── Qué pasa el filtro ──────────────────────────────────────────────────
  const texto = norm((filtro.texto ?? '').trim())
  const tipoIns = (filtro.tipoInsumo ?? '').trim()
  const unidadF = (filtro.unidad ?? '').trim()
  const hayFiltro = texto !== '' || tipoIns !== '' || unidadF !== ''

  const insumoPasa = (ins: ArbolInsumo): boolean =>
    (texto === '' || norm(ins.descripcion).includes(texto))
    && (tipoIns === '' || ins.tipoInsumo === tipoIns)
    && (unidadF === '' || ins.unidad === unidadF)

  // Un ítem pasa por sí mismo solo con el texto: tipo de insumo y unidad son atributos del insumo,
  // así que filtrar por ellos debe dejar únicamente las ramas que llevan a insumos que cumplen.
  const itemPasaSolo = (item: ArbolItem): boolean =>
    tipoIns === '' && unidadF === ''
    && texto !== ''
    && (norm(item.descripcion).includes(texto) || norm(item.codigo).includes(texto))

  /** Códigos que deben verse: los que coinciden y todos sus ancestros, para no perder el contexto. */
  const relevantes = new Set<string>()
  if (hayFiltro) {
    const marcarConAncestros = (item: ArbolItem): void => {
      let actual: ArbolItem | undefined = item
      while (actual) {
        if (relevantes.has(actual.codigo)) break
        relevantes.add(actual.codigo)
        actual = actual.codigoPadre !== null ? porCodigo.get(actual.codigoPadre) : undefined
      }
    }
    for (const item of items) {
      if (itemPasaSolo(item)) marcarConAncestros(item)
      const propios = insumosPorItem.get(item.id) ?? []
      if (propios.some(insumoPasa)) marcarConAncestros(item)
    }
  }

  // ── Modo tabla: sin jerarquía, cada fila se sitúa por su ruta ───────────
  if (filtro.plano) {
    const filas: FilaVisor[] = []
    for (const item of items) {
      const propios = insumosPorItem.get(item.id) ?? []
      if (!hayFiltro || itemPasaSolo(item)) {
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
          expandible: false,
          expandido: false,
          ruta: rutaDe(item),
        })
      }
      propios.forEach((ins, idx) => {
        if (hayFiltro && !insumoPasa(ins)) return
        filas.push({
          key: `i:${item.id}:${idx}`,
          tipo: 'insumo',
          nivel: item.nivel + 1,
          // El insumo hereda el código de su actividad: sin jerarquía es lo único que lo rastrea.
          codigo: item.codigo,
          descripcion: ins.descripcion,
          unidad: ins.unidad,
          cantidad: ins.cantidadTotal,
          tipoInsumo: ins.tipoInsumo,
          valorUnitario: ins.valorUnitario,
          valorTotal: ins.valorTotal,
          expandible: false,
          expandido: false,
          ruta: rutaDe(item, true),
        })
      })
    }
    return filas
  }

  // ── Modo árbol ──────────────────────────────────────────────────────────
  // Con filtro activo el camino hasta cada coincidencia se abre solo: buscar algo enterrado a cuatro
  // niveles y que no aparezca nada sería inútil.
  const visible = (item: ArbolItem): boolean => {
    let padre = item.codigoPadre
    while (padre !== null) {
      if (!(expandidos.has(padre) || (hayFiltro && relevantes.has(padre)))) return false
      padre = porCodigo.get(padre)?.codigoPadre ?? null
    }
    return true
  }

  const filas: FilaVisor[] = []
  for (const item of items) {
    if (hayFiltro && !relevantes.has(item.codigo)) continue
    if (!visible(item)) continue
    const propios = insumosPorItem.get(item.id) ?? []
    const expandible = tieneHijos.has(item.codigo) || propios.length > 0
    const abierto = expandidos.has(item.codigo) || (hayFiltro && relevantes.has(item.codigo))
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
      expandido: abierto,
      ruta: rutaDe(item),
    })
    if (item.tipoFila === 'actividad' && abierto) {
      propios.forEach((ins, idx) => {
        // Con filtro activo solo se listan los insumos que cumplen: si se buscó «teja», el resto de
        // la actividad no debe colarse por vecindad.
        if (hayFiltro && !insumoPasa(ins)) return
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
          ruta: rutaDe(item, true),
        })
      })
    }
  }
  return filas
}
