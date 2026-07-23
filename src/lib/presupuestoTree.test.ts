import { describe, expect, it } from 'vitest'
import { filasVisibles, totalesPorItem } from './presupuestoTree'
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
