import { describe, it, expect } from 'vitest'
import { validarDias, esCorregido } from './duracionesObra'

describe('validarDias', () => {
  it('acepta un entero de cero o más', () => {
    expect(validarDias('120')).toEqual({ ok: true, dias: 120 })
    expect(validarDias('0')).toEqual({ ok: true, dias: 0 })
  })

  it('rechaza el vacío, porque un paso sin días no es un paso de cero días', () => {
    expect(validarDias('')).toEqual({ ok: false, motivo: 'Escribe cuántos días dura el paso.' })
  })

  it('rechaza negativos y decimales', () => {
    expect(validarDias('-1').ok).toBe(false)
    expect(validarDias('1.5').ok).toBe(false)
  })

  it('rechaza lo que no es un número', () => {
    expect(validarDias('doce').ok).toBe(false)
  })
})

describe('esCorregido', () => {
  it('distingue el número de la obra del de la empresa', () => {
    expect(esCorregido('obra')).toBe(true)
    expect(esCorregido('empresa')).toBe(false)
  })
})
