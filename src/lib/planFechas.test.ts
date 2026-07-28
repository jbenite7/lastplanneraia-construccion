import { describe, expect, it } from 'vitest'
import {
  estadoFila, etiquetaDesfase, generaProceso, mensajeCalculo, opcionFrente, paquetesSinFrente, procedenciaDeAmarre,
  resumenPlan,
} from './planFechas'
import type { Desfase, FilaPlan, FrenteDisponible, SugerenciaFrente } from './types'

const fila = (over: Partial<FilaPlan> = {}): FilaPlan => ({
  paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'orden_compra',
  frenteNombre: 'ESTRUCTURA', uniqueId: 9001, fechaAncla: '2026-08-18', fechaArranque: '2026-05-23',
  diasTotales: 87, duracionProvisional: false, responsable: '', diasRetraso: 0, pasos: [],
  ...over,
})

describe('estadoFila', () => {
  it('lo vencido es «vencido», con sus días', () => {
    expect(estadoFila(fila({ diasRetraso: 65 }))).toEqual({ clave: 'vencido', etiqueta: '65 días de retraso' })
  })

  it('sin retraso es «en plazo»', () => {
    expect(estadoFila(fila()).clave).toBe('en-plazo')
  })

  it('la duración provisional se distingue, aunque esté en plazo', () => {
    expect(estadoFila(fila({ duracionProvisional: true })).clave).toBe('provisional')
  })

  it('vencido manda sobre provisional: es lo urgente', () => {
    expect(estadoFila(fila({ diasRetraso: 10, duracionProvisional: true })).clave).toBe('vencido')
  })
})

describe('resumenPlan', () => {
  it('cuenta vencidos, provisionales y total', () => {
    const r = resumenPlan([fila({ diasRetraso: 5 }), fila({ duracionProvisional: true }), fila()])
    expect(r).toEqual({ total: 3, vencidos: 1, provisionales: 1 })
  })

  it('un plan vacío no rompe', () => {
    expect(resumenPlan([])).toEqual({ total: 0, vencidos: 0, provisionales: 0 })
  })
})

describe('opcionFrente', () => {
  it('siempre lleva la fecha: el cronograma repite nombres de frente', () => {
    const f: FrenteDisponible = { uniqueId: 1, nombre: 'PISOS Y ENCHAPES', capitulo: '05', fechaInicio: '2027-05-12' }
    expect(opcionFrente(f)).toBe('PISOS Y ENCHAPES — 2027-05-12')
  })
})

describe('generaProceso', () => {
  it('contrato y orden de compra entran al plan de fechas', () => {
    expect(generaProceso('contrato')).toBe(true)
    expect(generaProceso('orden_compra')).toBe(true)
  })

  it('consumo directo y no contratable no entran', () => {
    expect(generaProceso('consumo_directo')).toBe(false)
    expect(generaProceso('no_contratable')).toBe(false)
  })

  it('sin modalidad se asume contrato (default del catálogo)', () => {
    expect(generaProceso(undefined)).toBe(true)
  })
})

describe('procedenciaDeAmarre', () => {
  const sugerencia: SugerenciaFrente = {
    uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18', origen: 'reglas', confianza: 'alta', evidencia: 'coincide por código',
  }

  it('elegir el frente propuesto cuenta como acierto confirmado', () => {
    expect(procedenciaDeAmarre(sugerencia, 9001)).toEqual({
      origen: 'reglas', confianza: 'alta', evidencia: 'coincide por código', confirmado: true,
    })
  })

  it('elegir otro frente no deja procedencia', () => {
    expect(procedenciaDeAmarre(sugerencia, 9002)).toBeUndefined()
  })

  it('sin propuesta previa no hay procedencia', () => {
    expect(procedenciaDeAmarre(undefined, 9001)).toBeUndefined()
  })
})

describe('paquetesSinFrente', () => {
  const base = { paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'contrato', insumos: 3, subtotal: 100 }

  it('excluye los ya amarrados y los que no generan proceso, y ordena por cuantía', () => {
    const porPaquete = [
      { ...base, paqueteId: 1, subtotal: 100 },
      { ...base, paqueteId: 2, subtotal: 500 },
      { ...base, paqueteId: 3, modalidad: 'consumo_directo', subtotal: 999 },
    ]
    const amarres = { 1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' } }
    expect(paquetesSinFrente(porPaquete, amarres).map((p) => p.paqueteId)).toEqual([2])
  })
})

describe('etiquetaDesfase', () => {
  it('describe el movimiento cuando el frente sigue existiendo', () => {
    const d: Desfase = { paqueteId: 1, nombre: 'Suministro CONCRETO', frenteNombre: 'ESTRUCTURA', fechaGuardada: '2026-05-23', fechaActual: '2026-06-10', diasMovidos: 18 }
    expect(etiquetaDesfase(d)).toBe('se movió de 2026-05-23 a 2026-06-10, 18 día(s)')
  })

  it('avisa distinto cuando el frente desapareció del cronograma', () => {
    const d: Desfase = { paqueteId: 1, nombre: 'Suministro CONCRETO', frenteNombre: 'ESTRUCTURA', fechaGuardada: '2026-05-23', fechaActual: null, diasMovidos: null }
    expect(etiquetaDesfase(d)).toBe('«ESTRUCTURA» ya no está en el cronograma')
  })
})

describe('mensajeCalculo', () => {
  it('reporta calculados y avisa si algunos quedaron sin duración de referencia', () => {
    expect(mensajeCalculo({ calculados: 40, sinDuracion: 3 })).toBe('40 paquete(s) recalculado(s); 3 sin duración de referencia.')
  })

  it('sin pendientes, mensaje simple', () => {
    expect(mensajeCalculo({ calculados: 40, sinDuracion: 0 })).toBe('40 paquete(s) recalculado(s).')
  })
})
