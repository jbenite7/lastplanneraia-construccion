import { describe, expect, it } from 'vitest'
import {
  estadoInicialWizard,
  ordenarDestinos,
  procedenciaDeAsignacion,
  wizardReducer,
} from './paqueteWizardState'
import type { PaqueteCatalogo, SugerenciaPaquete } from './types'

const sug = (paqueteId: number): SugerenciaPaquete => ({
  descripcionNorm: 'PISO CERAMICO 30X30',
  unidad: 'M2',
  paqueteId,
  paqueteNombre: 'Suministro PISOS',
  capa: 'reglas',
  confianza: 'alta',
  evidencia: 'Regla «CERAMIC» sobre la descripción.',
})

describe('wizardReducer', () => {
  it('SALTAR avanza el índice y limpia mensaje', () => {
    let s = wizardReducer({ ...estadoInicialWizard, mensaje: 'x' }, { type: 'SALTAR' })
    expect(s.indice).toBe(1)
    expect(s.mensaje).toBe(null)
    s = wizardReducer(s, { type: 'SALTAR' })
    expect(s.indice).toBe(2)
  })

  it('OCUPADO/LISTO conservan el índice (la lista se recarga aparte)', () => {
    let s = wizardReducer({ ...estadoInicialWizard, indice: 3 }, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    expect(s.indice).toBe(3)
    s = wizardReducer(s, { type: 'LISTO', mensaje: 'ok' })
    expect(s.ocupado).toBe(false)
    expect(s.indice).toBe(3)
    expect(s.mensaje).toBe('ok')
  })

  it('FALLO corta ocupado con mensaje', () => {
    const s = wizardReducer({ ...estadoInicialWizard, indice: 1, ocupado: true }, { type: 'FALLO', mensaje: 'boom' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('boom')
  })

  it('RESET vuelve al inicio', () => {
    const s = wizardReducer(
      { indice: 5, ocupado: true, mensaje: 'y', destinos: { 'A@@M2': 3 }, ultima: null },
      { type: 'RESET' },
    )
    expect(s).toEqual(estadoInicialWizard)
  })

  it('lo elegido a mano sobrevive a SALTAR: volver no pierde el trabajo a medias', () => {
    let s = wizardReducer(estadoInicialWizard, { type: 'ELEGIR', clave: 'PISO@@M2', paqueteId: 7 })
    s = wizardReducer(s, { type: 'SALTAR' })
    s = wizardReducer(s, { type: 'SALTAR' })
    expect(s.destinos['PISO@@M2']).toBe(7)
  })

  it('ELEGIR con null olvida la elección en vez de guardar un vacío', () => {
    let s = wizardReducer(estadoInicialWizard, { type: 'ELEGIR', clave: 'PISO@@M2', paqueteId: 7 })
    s = wizardReducer(s, { type: 'ELEGIR', clave: 'PISO@@M2', paqueteId: null })
    expect('PISO@@M2' in s.destinos).toBe(false)
  })

  it('FALLO no borra lo elegido a mano: un error de red no debe costar el trabajo', () => {
    let s = wizardReducer(estadoInicialWizard, { type: 'ELEGIR', clave: 'PISO@@M2', paqueteId: 7 })
    s = wizardReducer(s, { type: 'FALLO', mensaje: 'la red falló' })
    expect(s.destinos['PISO@@M2']).toBe(7)
  })

  it('LISTO guarda la última acción para poder deshacerla, y OCUPADO la conserva', () => {
    let s = wizardReducer(estadoInicialWizard, {
      type: 'LISTO',
      mensaje: 'Asignado a Suministro PISOS.',
      ultima: { descripcionNorm: 'PISO CERAMICO 30X30', unidad: 'M2', destino: 'Suministro PISOS' },
    })
    expect(s.ultima?.destino).toBe('Suministro PISOS')
    s = wizardReducer(s, { type: 'OCUPADO' })
    expect(s.ultima?.destino).toBe('Suministro PISOS')
  })

  it('LISTO sin última acción la limpia: recargar no deja un deshacer huérfano', () => {
    let s = wizardReducer(estadoInicialWizard, {
      type: 'LISTO',
      mensaje: 'ok',
      ultima: { descripcionNorm: 'PISO', unidad: 'M2', destino: 'X' },
    })
    s = wizardReducer(s, { type: 'LISTO', mensaje: 'otra cosa' })
    expect(s.ultima).toBe(null)
  })
})

describe('procedenciaDeAsignacion', () => {
  it('sin propuesta del motor no hay nada que atribuirle', () => {
    expect(procedenciaDeAsignacion(null, 5)).toBeUndefined()
  })

  it('aceptar la propuesta tal cual conserva la capa y queda confirmada', () => {
    expect(procedenciaDeAsignacion(sug(5), 5)).toEqual({
      origen: 'reglas',
      confianza: 'alta',
      evidencia: 'Regla «CERAMIC» sobre la descripción.',
      confirmado: true,
    })
  })

  it('elegir otro destino deja el par sugerido→elegido, sin fingir que vino del motor', () => {
    const p = procedenciaDeAsignacion(sug(5), 9)
    expect(p).toEqual({ sugeridoPaqueteId: 5, sugeridaCapa: 'reglas' })
    expect(p?.origen).toBeUndefined()
  })

  it('omitir sobre una propuesta también la corrige: rechazar hacia fuera del plan cuenta', () => {
    expect(procedenciaDeAsignacion(sug(5), null)).toEqual({ sugeridoPaqueteId: 5, sugeridaCapa: 'reglas' })
  })
})

describe('ordenarDestinos', () => {
  const paq = (id: number, nombre: string): PaqueteCatalogo => ({
    id, nombre, tipoNegociacion: 'suministro', insumosGlobal: 0,
  })
  const catalogo = [paq(1, 'Alfa'), paq(2, 'Beta'), paq(3, 'Gamma'), paq(4, 'Delta')]

  it('los que el proyecto ya usa van primero, por cuantía descendente', () => {
    const r = ordenarDestinos(catalogo, new Map([[3, 900], [1, 100]]))
    expect(r.map((p) => p.nombre)).toEqual(['Gamma', 'Alfa', 'Beta', 'Delta'])
  })

  it('sin nada usado, el catálogo conserva su orden alfabético', () => {
    const r = ordenarDestinos(catalogo, new Map())
    expect(r.map((p) => p.nombre)).toEqual(['Alfa', 'Beta', 'Gamma', 'Delta'])
  })

  it('no pierde ni duplica paquetes', () => {
    const r = ordenarDestinos(catalogo, new Map([[2, 50]]))
    expect(r).toHaveLength(catalogo.length)
    expect(new Set(r.map((p) => p.id)).size).toBe(catalogo.length)
  })

  it('un paquete usado que ya no está en el catálogo no inventa una fila', () => {
    const r = ordenarDestinos(catalogo, new Map([[99, 500]]))
    expect(r.map((p) => p.id)).toEqual([1, 2, 3, 4])
  })
})
