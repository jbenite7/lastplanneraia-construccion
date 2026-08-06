import { describe, expect, it } from 'vitest'
import { pasaFiltroLista, VALOR_VACIO, valoresDistintos } from './filtroLista'

describe('pasaFiltroLista', () => {
  it('sin modelo no filtra nada', () => {
    expect(pasaFiltroLista(null, 'lo que sea')).toBe(true)
  })

  it('con la lista vacía tampoco filtra: desmarcar todo no deja la tabla en blanco', () => {
    expect(pasaFiltroLista({ valores: [] }, 'MAT')).toBe(true)
  })

  it('deja pasar solo lo marcado', () => {
    expect(pasaFiltroLista({ valores: ['MAT'] }, 'MAT')).toBe(true)
    expect(pasaFiltroLista({ valores: ['MAT'] }, 'MOB')).toBe(false)
  })

  it('null, undefined y cadena vacía se agrupan bajo un solo valor', () => {
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, null)).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, undefined)).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, '')).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, 'MAT')).toBe(false)
  })

  it('compara números por su texto: la columna enseña texto', () => {
    expect(pasaFiltroLista({ valores: ['3'] }, 3)).toBe(true)
  })
})

describe('valoresDistintos', () => {
  it('quita repetidos y ordena en español', () => {
    expect(valoresDistintos(['b', 'a', 'b', 'á']).map((o) => o.valor)).toEqual(['a', 'á', 'b'])
  })

  it('agrupa los vacíos y los pone al final', () => {
    const r = valoresDistintos(['b', null, 'a', '', undefined])
    expect(r.map((o) => o.valor)).toEqual(['a', 'b', VALOR_VACIO])
    expect(r[2].etiqueta).toBe(VALOR_VACIO)
  })

  it('con todo vacío devuelve una sola opción', () => {
    expect(valoresDistintos([null, '', undefined])).toHaveLength(1)
  })
})
