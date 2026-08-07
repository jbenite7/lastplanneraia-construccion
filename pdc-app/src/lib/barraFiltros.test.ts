import { describe, expect, it } from 'vitest'
import { chipsDeGrilla } from './barraFiltros'

const NOMBRES = { agrupacion: 'Agrupación', valorTotal: 'Valor total', descripcion: 'Descripción' }

describe('chipsDeGrilla', () => {
  it('sin filtros no hay chips', () => {
    expect(chipsDeGrilla({}, NOMBRES)).toEqual([])
  })

  it('un filtro de lista se lee con sus valores', () => {
    expect(chipsDeGrilla({ agrupacion: { valores: ['MAT', 'MOB'] } }, NOMBRES))
      .toEqual([{ id: 'agrupacion', texto: 'Agrupación: MAT, MOB' }])
  })

  it('más de tres valores se resumen en vez de desbordar la barra', () => {
    const modelo = { agrupacion: { valores: ['a', 'b', 'c', 'd', 'e'] } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Agrupación: 5 valores')
  })

  it('un filtro de texto se lee con su condición', () => {
    const modelo = { descripcion: { filterType: 'text', type: 'contains', filter: 'cemento' } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Descripción: contiene «cemento»')
  })

  it('un filtro de número se lee con su condición', () => {
    const modelo = { valorTotal: { filterType: 'number', type: 'greaterThan', filter: 1000 } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Valor total: mayor que 1.000')
  })

  it('una columna sin nombre declarado usa su propio id, no queda «undefined»', () => {
    expect(chipsDeGrilla({ rara: { valores: ['x'] } }, NOMBRES)[0].texto).toBe('rara: x')
  })
})
