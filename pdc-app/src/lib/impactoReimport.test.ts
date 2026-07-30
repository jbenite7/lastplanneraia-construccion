import { describe, expect, it } from 'vitest'
import { hayImpacto, textoConserva } from './impactoReimport'
import type { ImpactoReimport } from './types'

const grupoVacio = { cantidad: 0, valor: 0, detalle: [] }
const cero: ImpactoReimport = {
  versionActiva: { id: 7, label: 'V1' },
  nuevosSinPaquete: grupoVacio,
  desaparecenConPaquete: grupoVacio,
  cambianTipo: grupoVacio,
  valorAfectado: 0,
}

describe('hayImpacto', () => {
  it('es falso cuando las cuatro cifras dan cero', () => {
    expect(hayImpacto(cero)).toBe(false)
  })

  it('es falso sin versión activa (no hay trabajo previo que perder)', () => {
    expect(hayImpacto({ ...cero, versionActiva: null })).toBe(false)
  })

  it('es verdadero con un solo insumo nuevo sin paquete', () => {
    expect(hayImpacto({ ...cero, nuevosSinPaquete: { cantidad: 1, valor: 0, detalle: [] } })).toBe(true)
  })

  it('un insumo de valor cero que se queda sin paquete cuenta como impacto', () => {
    // En Da Porto los insumos de $0 existen: si se mirara `valorAfectado` en vez de la cantidad,
    // este caso se anunciaría como «sin impacto» siendo trabajo que aparece.
    expect(hayImpacto({ ...cero, nuevosSinPaquete: { cantidad: 3, valor: 0, detalle: [] } })).toBe(true)
  })

  it('es verdadero cuando solo cambia el tipo de un insumo', () => {
    expect(hayImpacto({ ...cero, cambianTipo: { cantidad: 3, valor: 100, detalle: [] } })).toBe(true)
  })

  it('tolera la ausencia del bloque', () => {
    expect(hayImpacto(null)).toBe(false)
    expect(hayImpacto(undefined)).toBe(false)
  })
})

describe('textoConserva', () => {
  it('sin impacto dice que no se pierde nada del trabajo hecho', () => {
    expect(textoConserva(cero)).toContain('no se pierde')
  })

  it('siempre dice qué se conserva, con o sin impacto', () => {
    expect(textoConserva(cero)).toContain('se conservan')
    expect(textoConserva({ ...cero, cambianTipo: { cantidad: 1, valor: 1, detalle: [] } })).toContain('se conservan')
  })

  it('con impacto nombra qué queda por revisar', () => {
    const t = textoConserva({
      ...cero,
      nuevosSinPaquete: { cantidad: 2, valor: 500, detalle: [] },
      desaparecenConPaquete: { cantidad: 1, valor: 300, detalle: [] },
      valorAfectado: 800,
    })
    expect(t).toContain('2 insumos nuevos sin paquete')
    expect(t).toContain('1 insumo asignado que desaparece')
  })

  it('concuerda el verbo en plural', () => {
    const t = textoConserva({ ...cero, desaparecenConPaquete: { cantidad: 4, valor: 1, detalle: [] } })
    expect(t).toContain('4 insumos asignados que desaparecen')
  })

  it('nunca promete reagrupar solo', () => {
    const t = textoConserva({ ...cero, cambianTipo: { cantidad: 4, valor: 900, detalle: [] } })
    expect(t).not.toMatch(/reasign|reagrup|automátic/i)
    expect(t).toContain('revisar')
    expect(t).toContain('4 insumos que cambian de tipo')
  })
})
