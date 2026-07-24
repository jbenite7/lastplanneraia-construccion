import { describe, expect, it } from 'vitest'
import { estadoInicialWizard, wizardReducer } from './paqueteWizardState'

describe('wizardReducer', () => {
  it('SALTAR avanza el índice y limpia mensaje', () => {
    let s = wizardReducer({ ...estadoInicialWizard, mensaje: 'x' }, { type: 'SALTAR' })
    expect(s.indice).toBe(1)
    expect(s.mensaje).toBe(null)
    s = wizardReducer(s, { type: 'SALTAR' })
    expect(s.indice).toBe(2)
  })

  it('OCUPADO/LISTO conservan el índice (la lista se recarga aparte)', () => {
    let s = wizardReducer({ indice: 3, ocupado: false, mensaje: null }, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    expect(s.indice).toBe(3)
    s = wizardReducer(s, { type: 'LISTO', mensaje: 'ok' })
    expect(s.ocupado).toBe(false)
    expect(s.indice).toBe(3)
    expect(s.mensaje).toBe('ok')
  })

  it('FALLO corta ocupado con mensaje', () => {
    const s = wizardReducer({ indice: 1, ocupado: true, mensaje: null }, { type: 'FALLO', mensaje: 'boom' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('boom')
  })

  it('RESET vuelve al inicio', () => {
    const s = wizardReducer({ indice: 5, ocupado: true, mensaje: 'y' }, { type: 'RESET' })
    expect(s.indice).toBe(0)
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe(null)
  })
})
