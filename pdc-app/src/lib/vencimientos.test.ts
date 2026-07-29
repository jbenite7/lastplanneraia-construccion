import { describe, expect, it } from 'vitest'
import { CORTES, claseCorte, etiquetaCorte, textoDesfase, textoSinFechas } from './vencimientos'

describe('cortes', () => {
  it('van del más urgente al menos, y «sin fecha» va al final', () => {
    expect(CORTES.map((c) => c.id)).toEqual(['vencido', 'sem1', 'sem2', 'sem3', 'sem6', 'sin_fecha'])
  })

  it('no lista «más adelante»: el servidor lo cuenta pero no lo manda', () => {
    expect(CORTES.some((c) => c.id === 'adelante')).toBe(false)
  })

  it('un corte desconocido se muestra crudo en vez de desaparecer', () => {
    expect(etiquetaCorte('marciano')).toBe('marciano')
    expect(claseCorte('marciano')).toBe('')
  })

  it('cada corte tiene su clase', () => {
    expect(claseCorte('vencido')).toBe('pdc-venc--vencido')
    expect(claseCorte('sem1')).toBe('pdc-venc--sem1')
  })

  it('un paso cumplido tiene su propia clase', () => {
    expect(claseCorte('cumplido')).toBe('pdc-venc--cumplido')
    expect(etiquetaCorte('cumplido')).toBe('Cumplido')
  })
})

describe('textoDesfase', () => {
  it('dice los días de retraso en palabras', () => {
    expect(textoDesfase(1)).toBe('1 día tarde')
    expect(textoDesfase(9)).toBe('9 días tarde')
  })

  it('sin desfase no dice nada: un «0 días» suelto se lee como dato faltante', () => {
    expect(textoDesfase(null)).toBe('')
  })
})

describe('textoSinFechas', () => {
  it('calla cuando no hay nada que declarar', () => {
    expect(textoSinFechas({ paquetes: 0, sinFrente: 0, sinCalcular: 0 })).toBe('')
  })

  it('dice cuántos paquetes no se están mirando y por qué', () => {
    expect(textoSinFechas({ paquetes: 3, sinFrente: 2, sinCalcular: 1 })).toBe(
      'Este tablero no está mirando 3 paquetes sin fechas: 2 sin frente y 1 amarrado pendiente de recalcular.',
    )
  })

  it('en singular no dice «1 paquetes»', () => {
    expect(textoSinFechas({ paquetes: 1, sinFrente: 1, sinCalcular: 0 })).toBe(
      'Este tablero no está mirando 1 paquete sin fechas: 1 sin frente.',
    )
  })

  it('omite el motivo que vale cero', () => {
    expect(textoSinFechas({ paquetes: 2, sinFrente: 0, sinCalcular: 2 })).toBe(
      'Este tablero no está mirando 2 paquetes sin fechas: 2 amarrados pendientes de recalcular.',
    )
  })
})
