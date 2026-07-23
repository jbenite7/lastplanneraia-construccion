import { describe, expect, it } from 'vitest'
import { estadoInicial, importReducer } from './importState'
import type { ImportPreview } from './types'

const preview: ImportPreview = {
  importToken: 'a'.repeat(32),
  versionLabel: 'PI_TEST_1',
  resumen: { capitulos: 2, subcapitulos: 2, grupos: 2, actividades: 2, insumos: 4, costoTotal: 100 },
  advertencias: [],
}

describe('importReducer', () => {
  it('flujo feliz: idle → subiendo → previewOk → confirmando → confirmado', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    expect(s.fase).toBe('subiendo')
    s = importReducer(s, { type: 'PREVIEW_OK', preview })
    expect(s.fase).toBe('previewOk')
    expect(s.preview?.importToken).toBe(preview.importToken)
    s = importReducer(s, { type: 'CONFIRMAR' })
    expect(s.fase).toBe('confirmando')
    s = importReducer(s, { type: 'CONFIRMADO' })
    expect(s.fase).toBe('confirmado')
  })

  it('errores de validación llevan a previewErrores y limpian preview', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    s = importReducer(s, { type: 'PREVIEW_ERRORES', errores: [{ fila: 2, columna: 'UM', motivo: 'vacía' }] })
    expect(s.fase).toBe('previewErrores')
    expect(s.errores).toHaveLength(1)
    expect(s.preview).toBeNull()
  })

  it('FALLO desde cualquier fase guarda el mensaje y vuelve a idle', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    s = importReducer(s, { type: 'FALLO', mensaje: 'Sesión expirada' })
    expect(s.fase).toBe('idle')
    expect(s.mensajeError).toBe('Sesión expirada')
  })

  it('REINICIAR vuelve al estado inicial', () => {
    let s = importReducer(estadoInicial, { type: 'PREVIEW_OK', preview })
    s = importReducer(s, { type: 'REINICIAR' })
    expect(s).toEqual(estadoInicial)
  })
})
