import { describe, expect, it } from 'vitest'
import { TIPO_RECURSO_EQUIPO, etiquetaTipoRecurso } from './tipoRecurso'

describe('tipoRecurso', () => {
  // Espejo exacto de App\Services\Pdc\TipoRecursoEquipo. La divergencia PHP↔SPA no rompe nada
  // visible hasta que alguien intenta guardar, y entonces falla sin explicar por qué — ya pasó al
  // agregar `no_aplica` a TIPOS_NEGOCIACION (docs/pdc-v2.md §deudas de datos saldadas).
  it('fija los strings canónicos que comparte con el PHP', () => {
    expect(TIPO_RECURSO_EQUIPO.SIN_CLASIFICAR).toBe('EQUIPO (SIN CLASIFICAR)')
    expect(TIPO_RECURSO_EQUIPO.ALQUILADO).toBe('ALQUILER EQUIPOS')
    expect(TIPO_RECURSO_EQUIPO.COMPRADO).toBe('EQUIPO COMPRADO')
  })

  it('traduce el valor guardado a lo que lee una persona', () => {
    // El dato guarda el valor canónico de SINCO; la pantalla dice algo legible.
    expect(etiquetaTipoRecurso('ALQUILER EQUIPOS')).toBe('Equipo alquilado')
    expect(etiquetaTipoRecurso('EQUIPO COMPRADO')).toBe('Equipo comprado')
    expect(etiquetaTipoRecurso('EQUIPO (SIN CLASIFICAR)')).toBe('Equipo (sin clasificar)')
  })

  it('deja pasar cualquier otro tipo de recurso sin tocarlo', () => {
    expect(etiquetaTipoRecurso('MATERIAL')).toBe('MATERIAL')
    expect(etiquetaTipoRecurso('MANO DE OBRA')).toBe('MANO DE OBRA')
    expect(etiquetaTipoRecurso('')).toBe('')
  })
})
