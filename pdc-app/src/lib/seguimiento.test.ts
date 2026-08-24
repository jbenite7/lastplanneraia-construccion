import { describe, expect, it } from 'vitest'
import { etiquetaDesfaseDias, etiquetaEstado, filtrarSeguimiento, frentesDeSeguimiento } from './seguimiento'
import type { FilaSeguimiento, FiltrosSeguimiento } from './types'

const fila = (over: Partial<FilaSeguimiento>): FilaSeguimiento => ({
  paqueteId: 1, subpaqueteId: 0, nombre: 'Paquete', frenteNombre: 'ESTRUCTURA',
  responsableUserId: null, responsableNombre: '', responsableHuerfano: false,
  pasoActual: 'Cotizacion', cumplidos: 0, total: 7, estado: 'sin_empezar',
  atrasado: false, finProgramado: '2026-05-01', finProyectado: '2026-05-01',
  ...over,
})

const SIN_FILTRO: FiltrosSeguimiento = { soloMios: false, frente: '', estado: '', soloAtrasados: false }

describe('etiquetaEstado', () => {
  it('traduce los tres estados a algo legible', () => {
    expect(etiquetaEstado('sin_empezar')).toBe('Sin empezar')
    expect(etiquetaEstado('en_curso')).toBe('En curso')
    expect(etiquetaEstado('terminado')).toBe('Terminado')
  })

  it('un estado desconocido se muestra tal cual en vez de desaparecer', () => {
    expect(etiquetaEstado('otro')).toBe('otro')
  })
})

describe('etiquetaDesfaseDias', () => {
  it('sin desfase medible no dice nada', () => {
    expect(etiquetaDesfaseDias(null)).toBe('')
  })

  it('puntual se dice con palabras, no con un cero', () => {
    expect(etiquetaDesfaseDias(0)).toBe('A tiempo')
  })

  it('tarde y temprano se distinguen sin leer el signo', () => {
    expect(etiquetaDesfaseDias(10)).toBe('10 días tarde')
    expect(etiquetaDesfaseDias(1)).toBe('1 día tarde')
    expect(etiquetaDesfaseDias(-3)).toBe('3 días antes')
  })
})

describe('filtrarSeguimiento', () => {
  const filas = [
    fila({ paqueteId: 1, responsableUserId: 7, frenteNombre: 'ESTRUCTURA', estado: 'sin_empezar', atrasado: false }),
    fila({ paqueteId: 2, responsableUserId: 9, frenteNombre: 'ACABADOS', estado: 'en_curso', atrasado: true }),
    fila({ paqueteId: 3, responsableUserId: 7, frenteNombre: 'ACABADOS', estado: 'terminado', atrasado: false }),
  ]

  it('sin filtros devuelve todo', () => {
    expect(filtrarSeguimiento(filas, SIN_FILTRO, 7)).toHaveLength(3)
  })

  it('«mis paquetes» usa el usuario logueado', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([1, 3])
  })

  it('«mis paquetes» sin usuario conocido no devuelve nada, en vez de devolver todo', () => {
    expect(filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true }, null)).toHaveLength(0)
  })

  it('filtra por frente', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, frente: 'ACABADOS' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([2, 3])
  })

  it('filtra por estado', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, estado: 'terminado' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([3])
  })

  it('filtra por atraso', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloAtrasados: true }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([2])
  })

  it('los filtros se acumulan', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true, frente: 'ACABADOS' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([3])
  })
})

describe('frentesDeSeguimiento', () => {
  it('lista los frentes presentes, sin repetir y ordenados', () => {
    const filas = [fila({ frenteNombre: 'ESTRUCTURA' }), fila({ frenteNombre: 'ACABADOS' }), fila({ frenteNombre: 'ESTRUCTURA' })]
    expect(frentesDeSeguimiento(filas)).toEqual(['ACABADOS', 'ESTRUCTURA'])
  })

  it('ordena en espanol: las tildes y la ene no se van al final', () => {
    const filas = [
      fila({ frenteNombre: 'ZAPATAS' }),
      fila({ frenteNombre: 'ÑANDU' }),
      fila({ frenteNombre: 'MUROS' }),
      fila({ frenteNombre: 'MAMPOSTERÍA' }),
      fila({ frenteNombre: 'MAMPOSTERIA EXTERIOR' }),
    ]
    expect(frentesDeSeguimiento(filas)).toEqual([
      'MAMPOSTERÍA', 'MAMPOSTERIA EXTERIOR', 'MUROS', 'ÑANDU', 'ZAPATAS',
    ])
  })

  it('ignora los vacios: un frente sin nombre no es una opcion que se pueda elegir', () => {
    expect(frentesDeSeguimiento([fila({ frenteNombre: '' })])).toEqual([])
  })
})
