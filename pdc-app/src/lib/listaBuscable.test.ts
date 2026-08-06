import { describe, expect, it } from 'vitest'
import {
  alterna, MINIMO_PARA_BUSCAR, mueveResaltado, necesitaBuscador, opcionesVisibles,
} from './listaBuscable'
import type { Opcion } from './listaBuscable'

const o = (v: string, e = v): Opcion => ({ valor: v, etiqueta: e })

describe('necesitaBuscador', () => {
  it('el umbral acordado con el dueño del producto son 8 opciones', () => {
    expect(MINIMO_PARA_BUSCAR).toBe(8)
  })

  it('con 7 opciones no aparece la lupa; con 8 sí', () => {
    expect(necesitaBuscador(7)).toBe(false)
    expect(necesitaBuscador(8)).toBe(true)
  })
})

describe('opcionesVisibles', () => {
  const opciones = [o('a', 'Cemento gris'), o('b', 'Arena lavada'), o('c', 'CAÑO PVC')]

  it('recorta por la etiqueta, no por el valor', () => {
    expect(opcionesVisibles(opciones, 'arena').map((x) => x.valor)).toEqual(['b'])
  })

  it('sin búsqueda devuelve todas', () => {
    expect(opcionesVisibles(opciones, '')).toHaveLength(3)
  })

  it('conserva el orden original', () => {
    expect(opcionesVisibles(opciones, 'o').map((x) => x.valor)).toEqual(['a', 'c'])
  })
})

describe('mueveResaltado', () => {
  it('baja y sube dentro de la lista', () => {
    expect(mueveResaltado(0, 'ArrowDown', 3)).toBe(1)
    expect(mueveResaltado(2, 'ArrowUp', 3)).toBe(1)
  })

  it('da la vuelta en los extremos: la lista es circular', () => {
    expect(mueveResaltado(2, 'ArrowDown', 3)).toBe(0)
    expect(mueveResaltado(0, 'ArrowUp', 3)).toBe(2)
  })

  it('Home e End van a los extremos', () => {
    expect(mueveResaltado(1, 'Home', 3)).toBe(0)
    expect(mueveResaltado(1, 'End', 3)).toBe(2)
  })

  it('una tecla cualquiera no mueve nada', () => {
    expect(mueveResaltado(1, 'a', 3)).toBe(1)
  })

  it('con la lista vacía se queda en 0 y no devuelve -1', () => {
    expect(mueveResaltado(0, 'ArrowDown', 0)).toBe(0)
    expect(mueveResaltado(0, 'ArrowUp', 0)).toBe(0)
  })
})

describe('alterna', () => {
  it('añade lo que no estaba y quita lo que estaba', () => {
    expect(alterna(['a'], 'b')).toEqual(['a', 'b'])
    expect(alterna(['a', 'b'], 'a')).toEqual(['b'])
  })

  it('no muta el arreglo que recibe', () => {
    const antes = ['a']
    alterna(antes, 'b')
    expect(antes).toEqual(['a'])
  })
})
