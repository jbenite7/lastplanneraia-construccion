import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { FRACCION_UMBRAL_POR_DEFECTO, guardarUmbral, leerUmbral, partidasSobreUmbral, umbralPorDefecto } from './tamiz'
import type { CandidatoGlobal } from './types'

const c = (codigo: string, valorTotal: number): CandidatoGlobal =>
  ({ codigo, descripcion: codigo, unidad: 'SG', insumos: 1, valorTotal })

// Da Porto, versión activa: la medición que fija el valor por defecto.
const COSTO_DA_PORTO = 29_492_804_354

/**
 * Las pruebas corren en `environment: 'node'`, sin DOM. En vez de arrastrar jsdom solo por esto se
 * inyecta un almacenamiento mínimo; de paso queda cubierto el caso de no tener ninguno.
 */
function almacenDeMentira(): Storage {
  const mapa = new Map<string, string>()
  return {
    get length() { return mapa.size },
    clear: () => mapa.clear(),
    getItem: (k: string) => mapa.get(k) ?? null,
    key: (i: number) => [...mapa.keys()][i] ?? null,
    removeItem: (k: string) => { mapa.delete(k) },
    setItem: (k: string, v: string) => { mapa.set(k, v) },
  }
}

describe('umbralPorDefecto', () => {
  it('es el 0,25 % del presupuesto', () => {
    expect(FRACCION_UMBRAL_POR_DEFECTO).toBe(0.0025)
  })

  it('en Da Porto da un número legible del orden de 73 millones', () => {
    expect(umbralPorDefecto(COSTO_DA_PORTO)).toBe(73_000_000)
  })

  it('con costo cero no explota ni devuelve NaN', () => {
    expect(umbralPorDefecto(0)).toBe(0)
    expect(umbralPorDefecto(Number.NaN)).toBe(0)
  })

  it('en un presupuesto pequeño no se pasa de listo con el redondeo', () => {
    expect(umbralPorDefecto(100_000_000)).toBe(0)
  })
})

describe('partidasSobreUmbral', () => {
  const candidatos = [c('A', 890_000_000), c('B', 100_000_000), c('C', 5_000_000)]

  it('deja solo las que igualan o superan el umbral', () => {
    expect(partidasSobreUmbral(candidatos, 73_000_000).map((x) => x.codigo)).toEqual(['A', 'B'])
  })

  it('el umbral es inclusivo: una partida que vale exactamente el umbral se muestra', () => {
    expect(partidasSobreUmbral(candidatos, 100_000_000).map((x) => x.codigo)).toEqual(['A', 'B'])
  })

  it('con umbral cero deja todas (el usuario pidió verlo todo)', () => {
    expect(partidasSobreUmbral(candidatos, 0)).toHaveLength(3)
  })

  it('con un umbral altísimo no deja ninguna, y eso no es un error', () => {
    expect(partidasSobreUmbral(candidatos, 10_000_000_000)).toEqual([])
  })

  it('no muta el arreglo que recibe', () => {
    const copia = [...candidatos]
    partidasSobreUmbral(candidatos, 50_000_000)
    expect(candidatos).toEqual(copia)
  })
})

describe('umbral persistido por proyecto', () => {
  beforeEach(() => {
    (globalThis as { localStorage?: Storage }).localStorage = almacenDeMentira()
  })
  afterEach(() => {
    delete (globalThis as { localStorage?: Storage }).localStorage
  })

  it('sin nada guardado cae al valor por defecto del proyecto', () => {
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(73_000_000)
  })

  it('devuelve lo que el usuario puso', () => {
    guardarUmbral(73, 150_000_000)
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(150_000_000)
  })

  it('no mezcla proyectos', () => {
    guardarUmbral(73, 150_000_000)
    expect(leerUmbral(99, 4_000_000_000)).toBe(10_000_000)
  })

  it('acepta el cero como decisión del usuario, no como ausencia', () => {
    guardarUmbral(73, 0)
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(0)
  })

  it('un valor corrupto cae al por defecto en vez de romper la pantalla', () => {
    localStorage.setItem('pdc-umbral-global:73', 'no-es-un-numero')
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(73_000_000)
  })

  it('una cadena vacía no se lee como cero', () => {
    localStorage.setItem('pdc-umbral-global:73', '')
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(73_000_000)
  })

  it('un negativo guardado a mano cae al por defecto', () => {
    localStorage.setItem('pdc-umbral-global:73', '-5')
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(73_000_000)
  })
})

describe('sin almacenamiento (pruebas, modo privado)', () => {
  it('leer devuelve el por defecto y guardar no revienta', () => {
    expect(leerUmbral(73, COSTO_DA_PORTO)).toBe(73_000_000)
    expect(() => guardarUmbral(73, 1_000_000)).not.toThrow()
  })
})
