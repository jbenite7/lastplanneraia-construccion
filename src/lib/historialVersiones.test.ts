import { describe, expect, it } from 'vitest'
import { MAX_COMPARAR, alternarSeleccion, puedeMarcar, rutaComparar, rutaVisor } from './historialVersiones'

describe('selección para comparar', () => {
  it('deja marcar dos versiones', () => {
    expect(alternarSeleccion(alternarSeleccion([], 11), 12)).toEqual([11, 12])
  })

  it('al intentar una tercera, no la admite', () => {
    expect(alternarSeleccion([11, 12], 13)).toEqual([11, 12])
    expect(puedeMarcar([11, 12], 13)).toBe(false)
  })

  it('la casilla de una ya marcada sigue disponible: desmarcar siempre se puede', () => {
    expect(puedeMarcar([11, 12], 12)).toBe(true)
    expect(alternarSeleccion([11, 12], 12)).toEqual([11])
  })

  it('desmarcar libera el sitio para otra', () => {
    const tras = alternarSeleccion(alternarSeleccion([11, 12], 11), 13)
    expect(tras).toEqual([12, 13])
  })

  it('con menos de dos, no hay a dónde ir: el botón Comparar queda deshabilitado', () => {
    expect(rutaComparar([])).toBeNull()
    expect(rutaComparar([11])).toBeNull()
  })
})

describe('rutaComparar', () => {
  it('con dos marcadas lleva al comparador con ambas', () => {
    expect(rutaComparar([11, 12])).toBe('/ensamble/comparar?a=11&b=12')
  })

  it('la más antigua va como A, marque quien marque primero', () => {
    // El signo del delta se lee «positivo = sobrecosto frente a lo que había antes»: invertir el
    // orden lo daría al revés sin que nada lo avise.
    expect(rutaComparar([12, 11])).toBe('/ensamble/comparar?a=11&b=12')
  })

  it('el máximo es dos, y está dicho en un solo sitio', () => {
    expect(MAX_COMPARAR).toBe(2)
  })
})

describe('rutaVisor', () => {
  it('lleva al visor con esa versión ya cargada, sin preguntar nada por el camino', () => {
    expect(rutaVisor(12)).toBe('/ensamble/presupuesto?version=12')
  })
})
