import { describe, expect, it } from 'vitest'
import { NIVELES_PRESUPUESTO, NIVEL_INSUMO, expandirHastaNivel, filasVisibles, totalesPorItem } from './presupuestoTree'
import type { ArbolInsumo, ArbolItem } from './types'

const items: ArbolItem[] = [
  { id: 1, codigo: '01', codigoPadre: null, nivel: 1, tipoFila: 'capitulo', descripcion: 'PRELIMINARES', unidad: null, cantidad: null },
  { id: 2, codigo: '01.01', codigoPadre: '01', nivel: 2, tipoFila: 'subcapitulo', descripcion: 'CAMPAMENTO', unidad: null, cantidad: null },
  { id: 3, codigo: '01.01.01', codigoPadre: '01.01', nivel: 3, tipoFila: 'grupo', descripcion: 'INSTALACIONES', unidad: null, cantidad: null },
  { id: 4, codigo: '01.01.01.01', codigoPadre: '01.01.01', nivel: 4, tipoFila: 'actividad', descripcion: 'CASETA', unidad: 'M2', cantidad: 18 },
  { id: 5, codigo: '02', codigoPadre: null, nivel: 1, tipoFila: 'capitulo', descripcion: 'ESTRUCTURA', unidad: null, cantidad: null },
]

const insumos: ArbolInsumo[] = [
  { itemId: 4, descripcion: 'TEJA', tipoInsumo: 'MAT', unidad: 'M2', cantApu: 1.05, rendimiento: 1.2, cantidadTotal: 21.6, valorUnitario: 25000, valorTotal: 540000 },
  { itemId: 4, descripcion: 'AYUDANTE', tipoInsumo: 'MO', unidad: 'HC', cantApu: 8, rendimiento: 0.5, cantidadTotal: 9, valorUnitario: 9500, valorTotal: 85500 },
]

describe('totalesPorItem', () => {
  it('actividad suma sus insumos y los padres hacen roll-up', () => {
    const t = totalesPorItem(items, insumos)
    expect(t.get(4)).toBe(625500)   // 540000 + 85500
    expect(t.get(3)).toBe(625500)   // grupo
    expect(t.get(2)).toBe(625500)   // subcapítulo
    expect(t.get(1)).toBe(625500)   // capítulo
    expect(t.get(5)).toBe(0)        // capítulo sin actividades
  })
})

describe('filasVisibles', () => {
  it('colapsado: solo raíces, marcadas expandibles', () => {
    const filas = filasVisibles(items, insumos, new Set())
    expect(filas.map((f) => f.codigo)).toEqual(['01', '02'])
    expect(filas[0].expandible).toBe(true)
    expect(filas[0].expandido).toBe(false)
    expect(filas[1].expandible).toBe(false) // '02' no tiene hijos
  })

  it('expandir en cadena revela cada nivel', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01']))
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '01.01.01', '02'])
  })

  it('un hijo NO aparece si falta un ancestro intermedio', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01.01'])) // falta 01.01
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '02'])
  })

  it('actividad expandida inserta sus insumos como filas', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01', '01.01.01', '01.01.01.01']))
    const desc = filas.map((f) => `${f.tipo}:${f.descripcion}`)
    expect(desc).toEqual([
      'item:PRELIMINARES', 'item:CAMPAMENTO', 'item:INSTALACIONES', 'item:CASETA',
      'insumo:TEJA', 'insumo:AYUDANTE', 'item:ESTRUCTURA',
    ])
    const teja = filas[4]
    expect(teja.nivel).toBe(5)
    expect(teja.valorTotal).toBe(540000)
    expect(teja.expandible).toBe(false)
  })

  it('la fila de actividad lleva el total roll-up en valorTotal', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01', '01.01.01']))
    const caseta = filas.find((f) => f.codigo === '01.01.01.01')!
    expect(caseta.valorTotal).toBe(625500)
    expect(caseta.expandible).toBe(true) // tiene insumos
  })
})

describe('búsqueda en el árbol', () => {
  it('encuentra un insumo aunque su rama esté colapsada, y abre el camino hasta él', () => {
    // Sin buscar, TEJA está a cuatro niveles de profundidad y no se ve.
    const filas = filasVisibles(items, insumos, new Set(), { texto: 'teja' })
    const desc = filas.map((f) => f.descripcion)
    expect(desc).toContain('TEJA')
    // Aparecen sus ancestros para no perder el contexto de dónde está.
    expect(desc).toEqual(['PRELIMINARES', 'CAMPAMENTO', 'INSTALACIONES', 'CASETA', 'TEJA'])
    // Y el otro insumo de la misma actividad NO se cuela.
    expect(desc).not.toContain('AYUDANTE')
  })

  it('las ramas sin coincidencias desaparecen', () => {
    const filas = filasVisibles(items, insumos, new Set(), { texto: 'teja' })
    expect(filas.map((f) => f.codigo)).not.toContain('02')
  })

  it('busca también por código de actividad', () => {
    const filas = filasVisibles(items, insumos, new Set(), { texto: '01.01.01.01' })
    expect(filas.map((f) => f.codigo)).toContain('01.01.01.01')
  })

  it('ignora tildes y mayúsculas', () => {
    const conTilde: ArbolItem[] = [
      ...items,
      { id: 6, codigo: '03', codigoPadre: null, nivel: 1, tipoFila: 'capitulo', descripcion: 'CIMENTACIÓN', unidad: null, cantidad: null },
    ]
    const filas = filasVisibles(conTilde, insumos, new Set(), { texto: 'cimentacion' })
    expect(filas.map((f) => f.descripcion)).toContain('CIMENTACIÓN')
  })

  it('sin coincidencias devuelve una lista vacía, no el árbol entero', () => {
    expect(filasVisibles(items, insumos, new Set(), { texto: 'zzz' })).toEqual([])
  })

  it('filtra por tipo de insumo y por unidad', () => {
    const soloMat = filasVisibles(items, insumos, new Set(), { tipoInsumo: 'MAT' })
    expect(soloMat.map((f) => f.descripcion)).toContain('TEJA')
    expect(soloMat.map((f) => f.descripcion)).not.toContain('AYUDANTE')
    const soloHc = filasVisibles(items, insumos, new Set(), { unidad: 'HC' })
    expect(soloHc.map((f) => f.descripcion)).toContain('AYUDANTE')
    expect(soloHc.map((f) => f.descripcion)).not.toContain('TEJA')
  })
})

describe('modo tabla (plano)', () => {
  it('lista todas las filas sin jerarquía, con su ruta', () => {
    const filas = filasVisibles(items, insumos, new Set(), { plano: true })
    // 5 items + 2 insumos, sin depender de qué esté expandido.
    expect(filas).toHaveLength(7)
    const teja = filas.find((f) => f.descripcion === 'TEJA')!
    expect(teja.ruta).toBe('PRELIMINARES › CAMPAMENTO › INSTALACIONES › CASETA')
    expect(teja.expandible).toBe(false)
  })

  it('el insumo hereda el código de su actividad para poder rastrearlo', () => {
    const filas = filasVisibles(items, insumos, new Set(), { plano: true })
    expect(filas.find((f) => f.descripcion === 'TEJA')!.codigo).toBe('01.01.01.01')
  })

  it('en plano el filtro deja solo las coincidencias, sin ancestros', () => {
    const filas = filasVisibles(items, insumos, new Set(), { plano: true, texto: 'teja' })
    expect(filas.map((f) => f.descripcion)).toEqual(['TEJA'])
  })
})

describe('expandirHastaNivel', () => {
  it('con nivel 2, se ven capítulos y subcapítulos, no lo de más abajo', () => {
    const filas = filasVisibles(items, insumos, expandirHastaNivel(items, 2))
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '02'])
  })

  it('con nivel 1 no se abre nada: solo los capítulos', () => {
    expect(expandirHastaNivel(items, 1).size).toBe(0)
  })

  it('con nivel «insumo», se ve todo el árbol abierto', () => {
    const filas = filasVisibles(items, insumos, expandirHastaNivel(items, NIVEL_INSUMO))
    expect(filas.map((f) => f.descripcion)).toEqual([
      'PRELIMINARES', 'CAMPAMENTO', 'INSTALACIONES', 'CASETA', 'TEJA', 'AYUDANTE', 'ESTRUCTURA',
    ])
  })

  it('el conjunto que devuelve es el que el árbol ya sabe consumir', () => {
    // No inventa una estructura nueva: son códigos, igual que los que produce hacer clic.
    // '02' entra aunque no tenga hijos — marcar como abierta una rama vacía no cambia nada de lo
    // que se ve, y filtrarlas costaría recorrer la jerarquía entera para nada.
    expect(expandirHastaNivel(items, 3)).toEqual(new Set(['01', '01.01', '02']))
  })

  it('los cinco niveles del presupuesto tienen nombre, y el último es el insumo', () => {
    expect(NIVELES_PRESUPUESTO.map((n) => n.valor)).toEqual([1, 2, 3, 4, 5])
    expect(NIVELES_PRESUPUESTO[NIVELES_PRESUPUESTO.length - 1].valor).toBe(NIVEL_INSUMO)
  })
})
