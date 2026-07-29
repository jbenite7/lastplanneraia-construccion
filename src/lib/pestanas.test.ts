import { describe, expect, it } from 'vitest'
import { etiquetaPestana, focoPorTecla } from './pestanas'

describe('focoPorTecla', () => {
  it('las flechas mueven el foco de una pestaña a la siguiente y a la anterior', () => {
    expect(focoPorTecla(0, 3, 'ArrowRight')).toBe(1)
    expect(focoPorTecla(1, 3, 'ArrowLeft')).toBe(0)
  })

  it('da la vuelta en los extremos, como manda el patrón de pestañas', () => {
    expect(focoPorTecla(2, 3, 'ArrowRight')).toBe(0)
    expect(focoPorTecla(0, 3, 'ArrowLeft')).toBe(2)
  })

  it('Inicio y Fin van a los bordes', () => {
    expect(focoPorTecla(1, 3, 'Home')).toBe(0)
    expect(focoPorTecla(1, 3, 'End')).toBe(2)
  })

  it('cualquier otra tecla no mueve el foco', () => {
    expect(focoPorTecla(1, 3, 'a')).toBe(1)
    expect(focoPorTecla(1, 3, 'Enter')).toBe(1)
  })

  it('sin pestañas no revienta', () => {
    expect(focoPorTecla(0, 0, 'ArrowRight')).toBe(0)
  })
})

describe('etiquetaPestana', () => {
  it('el conteo va en la propia pestaña: es lo que hace visible lo pendiente sin abrirla', () => {
    expect(etiquetaPestana({ id: 'x', etiqueta: 'Sin frente', conteo: 40 })).toBe('Sin frente (40)')
  })

  it('cero también se dice: «(0)» informa, y esconderlo obligaría a abrir para saberlo', () => {
    expect(etiquetaPestana({ id: 'x', etiqueta: 'Desfases', conteo: 0 })).toBe('Desfases (0)')
  })

  it('sin conteo, solo la etiqueta', () => {
    expect(etiquetaPestana({ id: 'x', etiqueta: 'Catálogo global' })).toBe('Catálogo global')
  })
})
