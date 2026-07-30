import { describe, expect, it } from 'vitest'
import { etiquetaMovimiento, resumenDelta } from './reprogramacion'
import type { DeltaPaquete } from './types'

const m = (over: Partial<DeltaPaquete> = {}): DeltaPaquete => ({
  paqueteId: 1,
  nombre: 'CONCRETO',
  frenteNombre: 'ESTRUCTURA',
  anclaActual: '2026-09-01',
  anclaNueva: '2026-09-22',
  diasMovidos: 21,
  arranqueActual: '2026-07-30',
  arranqueNuevo: '2026-08-20',
  pasosQueSeMueven: 7,
  pasosConFechaReal: 1,
  ...over,
})

describe('etiquetaMovimiento', () => {
  it('un frente atrasado dice que se atrasa y cuántos días', () => {
    expect(etiquetaMovimiento(m())).toBe('se atrasa 21 días: arranque 2026-07-30 → 2026-08-20')
  })

  // El signo crudo no se enseña nunca: «-9 días» se lee como un error de la pantalla, y la
  // dirección es justo lo que hay que entender antes de aplicar.
  it('un frente adelantado no dice «-9 días»', () => {
    expect(etiquetaMovimiento(m({ diasMovidos: -9, arranqueNuevo: '2026-07-21' })))
      .toBe('se adelanta 9 días: arranque 2026-07-30 → 2026-07-21')
  })

  it('un solo día va en singular', () => {
    expect(etiquetaMovimiento(m({ diasMovidos: 1, arranqueNuevo: '2026-07-31' })))
      .toBe('se atrasa 1 día: arranque 2026-07-30 → 2026-07-31')
  })

  it('sin arranque previo no se inventa un «desde»', () => {
    expect(etiquetaMovimiento(m({ arranqueActual: null })))
      .toBe('se atrasa 21 días: arranque 2026-08-20')
  })
})

describe('resumenDelta', () => {
  it('cuenta paquetes, pasos ya ocurridos y en qué dirección se mueven', () => {
    expect(resumenDelta([m(), m({ paqueteId: 2, diasMovidos: -3, pasosConFechaReal: 2 })]))
      .toEqual({ paquetes: 2, pasosProtegidos: 3, atrasan: 1, adelantan: 1 })
  })

  it('sin nada que mover, todo en cero', () => {
    expect(resumenDelta([])).toEqual({ paquetes: 0, pasosProtegidos: 0, atrasan: 0, adelantan: 0 })
  })
})
