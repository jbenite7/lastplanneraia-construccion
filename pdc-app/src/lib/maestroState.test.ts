import { describe, expect, it } from 'vitest'
import { estadoInicialMaestro, maestroReducer, pestanaInicialMaestro } from './maestroState'
import type { VinculoInsumo } from './types'

const vinculo: VinculoInsumo = {
  id: 7, descripcionOriginal: 'Teja', descripcionNorm: 'TEJA', unidad: 'M2', tipoInsumo: 'MAT',
  cantidadTotal: 1, valorTotal: 100, apariciones: 1, maestroId: null, maestroDescripcion: null, estado: 'pendiente',
}

describe('maestroReducer', () => {
  it('toggle de selección agrega y quita ids', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'TOGGLE_SEL', id: 7 })
    expect(s.seleccion.has(7)).toBe(true)
    s = maestroReducer(s, { type: 'TOGGLE_SEL', id: 7 })
    expect(s.seleccion.has(7)).toBe(false)
  })

  it('SEL_TODOS reemplaza la selección y LIMPIAR_SEL la vacía', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'SEL_TODOS', ids: [1, 2, 3] })
    expect([...s.seleccion]).toEqual([1, 2, 3])
    s = maestroReducer(s, { type: 'LIMPIAR_SEL' })
    expect(s.seleccion.size).toBe(0)
  })

  it('abrir/cerrar el panel de vinculación', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'ABRIR_VINCULAR', vinculo })
    expect(s.vinculando?.id).toBe(7)
    s = maestroReducer(s, { type: 'CERRAR_VINCULAR' })
    expect(s.vinculando).toBeNull()
  })

  it('OCUPADO/LISTO/FALLO gobiernan ocupado y mensaje; LISTO limpia selección y panel', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'SEL_TODOS', ids: [1] })
    s = maestroReducer(s, { type: 'ABRIR_VINCULAR', vinculo })
    s = maestroReducer(s, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    s = maestroReducer(s, { type: 'LISTO', mensaje: 'Hecho' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('Hecho')
    expect(s.seleccion.size).toBe(0)
    expect(s.vinculando).toBeNull()
    s = maestroReducer(s, { type: 'FALLO', mensaje: 'Error X' })
    expect(s.mensaje).toBe('Error X')
    expect(s.ocupado).toBe(false)
  })
})

describe('pestanaInicialMaestro', () => {
  it('con pendientes abre por ellos, que es el trabajo que falta', () => {
    expect(pestanaInicialMaestro(12)).toBe('pendientes')
  })

  it('sin pendientes abre por el catálogo, no por una tabla vacía', () => {
    expect(pestanaInicialMaestro(0)).toBe('catalogo')
  })
})
