import { describe, expect, it } from 'vitest'
import { claseDelta, filasComparativoVisibles } from './comparativo'
import type { ActividadDiff } from './types'

const act = (codigo: string, codigoPadre: string | null, nivel: number, tipoFila: ActividadDiff['tipoFila'], estado: ActividadDiff['estado'] = 'igual'): ActividadDiff => ({
  codigo, codigoPadre, nivel, tipoFila, descripcion: codigo, valorA: 100, valorB: 120, deltaValor: 20, deltaPct: 20, estado,
})

const arbol: ActividadDiff[] = [
  act('01', null, 1, 'capitulo'),
  act('01.01', '01', 2, 'subcapitulo'),
  act('01.01.01.01', '01.01', 3, 'actividad', 'modificado'),
  act('02', null, 1, 'capitulo'),
]

describe('filasComparativoVisibles', () => {
  it('sin expandidos solo muestra las raíces', () => {
    const filas = filasComparativoVisibles(arbol, new Set())
    expect(filas.map((f) => f.codigo)).toEqual(['01', '02'])
    expect(filas[0].expandible).toBe(true)
    expect(filas[1].expandible).toBe(false)
  })

  it('expandir un padre revela sus hijos directos', () => {
    const filas = filasComparativoVisibles(arbol, new Set(['01']))
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '02'])
    expect(filas.find((f) => f.codigo === '01')?.expandido).toBe(true)
  })

  it('un nieto solo es visible si toda su cadena está expandida', () => {
    expect(filasComparativoVisibles(arbol, new Set(['01'])).map((f) => f.codigo)).not.toContain('01.01.01.01')
    expect(filasComparativoVisibles(arbol, new Set(['01', '01.01'])).map((f) => f.codigo)).toContain('01.01.01.01')
  })
})

describe('claseDelta', () => {
  it('sobrecosto / ahorro / neutro', () => {
    expect(claseDelta(50, 'modificado')).toBe('pdc-cmp-sobrecosto')
    expect(claseDelta(-50, 'eliminado')).toBe('pdc-cmp-ahorro')
    expect(claseDelta(0, 'igual')).toBe('')
  })
})
