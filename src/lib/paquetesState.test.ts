import { describe, expect, it } from 'vitest'
import { claveInsumo, estadoInicialPaquetes, paquetesReducer } from './paquetesState'
import type { SugerenciaPaquete } from './types'

const sug: SugerenciaPaquete = {
  descripcionNorm: 'PISO CERAMICO 30X30', unidad: 'M2',
  paqueteId: 7, paqueteNombre: 'Pisos', capa: 'exacta', confianza: 'alta', evidencia: 'en 2 proyectos',
}

describe('paquetesReducer', () => {
  it('selección: toggle, todos, limpiar', () => {
    const k = claveInsumo('PISO CERAMICO 30X30', 'M2')
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(true)
    s = paquetesReducer(s, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(false)
    s = paquetesReducer(s, { type: 'SEL_TODOS', claves: [k, claveInsumo('AYUDANTE', 'HC')] })
    expect(s.seleccion.size).toBe(2)
    s = paquetesReducer(s, { type: 'LIMPIAR_SEL' })
    expect(s.seleccion.size).toBe(0)
  })

  it('sugerencias se indexan por clave y se limpian', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'SUGERENCIAS_OK', sugerencias: [sug] })
    expect(s.sugerencias.get(claveInsumo(sug.descripcionNorm, sug.unidad))?.paqueteId).toBe(7)
    expect(s.ocupado).toBe(false)
    s = paquetesReducer(s, { type: 'LIMPIAR_SUGERENCIAS' })
    expect(s.sugerencias.size).toBe(0)
  })

  it('ocupado/listo/fallo', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    s = paquetesReducer(s, { type: 'LISTO', mensaje: '2 asignados' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('2 asignados')
    s = paquetesReducer(s, { type: 'FALLO', mensaje: 'error' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('error')
  })

  it('claveInsumo combina norma y unidad de forma estable', () => {
    expect(claveInsumo('A', 'M2')).toBe('A@@M2')
    expect(claveInsumo('A', 'M2')).not.toBe(claveInsumo('A', 'KG'))
  })
})
