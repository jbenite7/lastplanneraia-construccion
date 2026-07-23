import { describe, expect, it } from 'vitest'
import { estadoInicialMaestroImport, maestroImportReducer } from './maestroImportState'
import type { MaestroImportPreview } from './types'

const preview: MaestroImportPreview = {
  importToken: 'a'.repeat(32),
  resumen: { total: 6, activos: 5, omitidos: 1, agrupaciones: 4, tiposRecurso: 3 },
}

describe('maestroImportReducer', () => {
  it('flujo feliz: idle → subiendo → previewOk → confirmando → confirmado', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    expect(s.fase).toBe('subiendo')
    s = maestroImportReducer(s, { type: 'PREVIEW_OK', preview })
    expect(s.fase).toBe('previewOk')
    expect(s.preview?.resumen.activos).toBe(5)
    s = maestroImportReducer(s, { type: 'CONFIRMAR' })
    expect(s.fase).toBe('confirmando')
    s = maestroImportReducer(s, { type: 'CONFIRMADO', resultado: { creados: 5, actualizados: 0, enriquecidos: 0, conflictos: [] } })
    expect(s.fase).toBe('confirmado')
    expect(s.resultado?.creados).toBe(5)
  })

  it('errores de validación llevan a previewErrores y limpian preview', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    s = maestroImportReducer(s, { type: 'PREVIEW_ERRORES', errores: [{ fila: 2, columna: 'Codigo Insumo', motivo: 'vacío' }] })
    expect(s.fase).toBe('previewErrores')
    expect(s.errores).toHaveLength(1)
    expect(s.preview).toBeNull()
  })

  it('FALLO vuelve a idle con mensaje; REINICIAR resetea', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    s = maestroImportReducer(s, { type: 'FALLO', mensaje: 'Sesión expirada' })
    expect(s.fase).toBe('idle')
    expect(s.mensajeError).toBe('Sesión expirada')
    expect(maestroImportReducer(s, { type: 'REINICIAR' })).toEqual(estadoInicialMaestroImport)
  })
})
