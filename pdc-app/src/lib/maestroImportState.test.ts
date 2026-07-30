import { describe, expect, it } from 'vitest'
import { estadoInicialMaestroImport, maestroImportReducer, resumenImportacionMaestro } from './maestroImportState'
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
    s = maestroImportReducer(s, { type: 'CONFIRMADO', resultado: { creados: 5, actualizados: 0, enriquecidos: 0, conflictos: [], reenganchados: 0 } })
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

describe('resumen de una importación del maestro', () => {
  const base = { creados: 5, actualizados: 2, enriquecidos: 1, conflictos: [], reenganchados: 0 }

  it('cuenta lo que entró al catálogo', () => {
    expect(resumenImportacionMaestro(base))
      .toBe('Maestro importado: 5 creados, 2 actualizados, 1 enriquecido.')
  })

  it('añade los pendientes que se resolvieron solos', () => {
    // El número que explica por qué la lista de pendientes baja sin que nadie la toque. Sin él, el
    // usuario que vuelve a «Pendientes por vincular» cree que se equivocó de cuenta.
    expect(resumenImportacionMaestro({ ...base, reenganchados: 12 }))
      .toBe('Maestro importado: 5 creados, 2 actualizados, 1 enriquecido. '
        + 'Además, 12 pendientes se resolvieron solos al entrar sus insumos.')
  })

  it('concuerda en singular', () => {
    expect(resumenImportacionMaestro({ ...base, reenganchados: 1 }))
      .toContain('1 pendiente se resolvió solo')
  })

  it('si no resolvió ninguno, no dice nada de pendientes', () => {
    // Un «0 pendientes resueltos» es ruido: no informa de nada que el usuario pueda usar.
    expect(resumenImportacionMaestro(base)).not.toMatch(/pendiente/i)
  })

  it('nunca usa la palabra de dentro', () => {
    // «Reenganchado» es como se llama por dentro el vínculo que vuelve de pendiente a automático.
    // Es vocabulario del sistema, no del residente de obra que lee la pantalla.
    expect(resumenImportacionMaestro({ ...base, reenganchados: 3 })).not.toMatch(/reenganch/i)
  })
})
