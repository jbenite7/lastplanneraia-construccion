import { describe, expect, it } from 'vitest'
import { agregar, aPayload, disponibles, mover, quitar, validar, type PasoEditable } from './pasosState'
import type { PasoCatalogo } from './types'

const paso = (clave: string, colLegacy: string | null = 'diasX'): PasoEditable => ({
  clave,
  nombre: clave,
  alias: '',
  colLegacy,
  diasFijos: colLegacy === null ? 15 : null,
  diasSugeridos: null,
})

const cat = (clave: string, colLegacy: string | null, diasSugeridos: number | null): PasoCatalogo => ({
  id: 1, clave, nombre: clave.toUpperCase(), colLegacy, diasSugeridos, peso: colLegacy === null ? null : 0.5, ordenDefault: 0,
})

describe('mover', () => {
  it('sube un paso y deja el resto en su orden relativo', () => {
    const r = mover([paso('a'), paso('b'), paso('c')], 2, 0)
    expect(r.map((p) => p.clave)).toEqual(['c', 'a', 'b'])
  })

  it('no hace nada si el destino queda fuera de la lista', () => {
    const antes = [paso('a'), paso('b')]
    expect(mover(antes, 0, -1)).toEqual(antes)
    expect(mover(antes, 0, 2)).toEqual(antes)
  })
})

describe('validar', () => {
  it('rechaza una lista vacía', () => {
    expect(validar([])).toEqual({ ok: false, mensaje: 'El proceso necesita al menos un paso.' })
  })

  it('rechaza un paso sin respaldo del catálogo al que no se le pusieron días', () => {
    const sinDias = { ...paso('aprobacion_cliente', null), diasFijos: null }
    expect(validar([paso('a'), sinDias]).ok).toBe(false)
  })

  it('rechaza días negativos', () => {
    const negativo = { ...paso('aprobacion_cliente', null), diasFijos: -1 }
    expect(validar([negativo]).ok).toBe(false)
  })

  it('acepta cero días: un paso puede ser instantáneo sin ser inválido', () => {
    const cero = { ...paso('licify', null), diasFijos: 0 }
    expect(validar([cero])).toEqual({ ok: true })
  })

  it('acepta una lista de más de siete pasos', () => {
    const muchos = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'].map((c) => paso(c))
    expect(validar(muchos)).toEqual({ ok: true })
  })
})

describe('aPayload', () => {
  it('manda el alias solo cuando lo hay y los días solo cuando el paso los necesita', () => {
    const conAlias = { ...paso('entrega_pliegos'), alias: 'Envío de pliegos' }
    expect(aPayload([conAlias, paso('aprobacion_cliente', null)])).toEqual([
      { clave: 'entrega_pliegos', alias: 'Envío de pliegos' },
      { clave: 'aprobacion_cliente', diasFijos: 15 },
    ])
  })

  it('un alias que es solo espacios no viaja: equivale a no haber puesto ninguno', () => {
    const espacios = { ...paso('legalizacion'), alias: '   ' }
    expect(aPayload([espacios])).toEqual([{ clave: 'legalizacion' }])
  })
})

describe('agregar y disponibles', () => {
  it('un paso ya en la lista no se ofrece dos veces', () => {
    expect(disponibles([cat('a', 'diasX', null), cat('z', null, 3)], [paso('a')]).map((c) => c.clave)).toEqual(['z'])
  })

  it('al agregar un paso sin respaldo, arranca con los días que sugiere el catálogo', () => {
    expect(agregar([paso('a')], cat('z', null, 3))[1].diasFijos).toBe(3)
  })

  it('un paso con respaldo del catálogo no lleva días fijos', () => {
    expect(agregar([], cat('a', 'diasX', null))[0].diasFijos).toBeNull()
  })

  it('agregar dos veces el mismo paso no lo duplica', () => {
    const una = agregar([], cat('a', 'diasX', null))
    expect(agregar(una, cat('a', 'diasX', null))).toHaveLength(1)
  })

  it('se puede insertar en una posición concreta', () => {
    const lista = [paso('a'), paso('b')]
    expect(agregar(lista, cat('z', null, 3), 1).map((p) => p.clave)).toEqual(['a', 'z', 'b'])
  })
})

describe('quitar', () => {
  it('saca el paso de la lista', () => {
    expect(quitar([paso('a'), paso('b')], 'a').map((p) => p.clave)).toEqual(['b'])
  })
})
