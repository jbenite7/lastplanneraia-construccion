import { describe, expect, it } from 'vitest'
import { etiquetaVersion } from './versionLabel'

describe('etiquetaVersion', () => {
  it('compone Versión N · fecha', () => {
    expect(etiquetaVersion({ versionNumero: 3, createdAt: '2026-07-23 16:00' })).toBe('Versión 3 · 2026-07-23 16:00')
  })
  it('añade el label del Excel si existe', () => {
    expect(etiquetaVersion({ versionNumero: 3, createdAt: '2026-07-23', versionLabel: 'PI_Version_3' })).toBe('Versión 3 · 2026-07-23 · PI_Version_3')
  })
  it('ignora label vacío', () => {
    expect(etiquetaVersion({ versionNumero: 1, createdAt: '2026-07-23', versionLabel: '' })).toBe('Versión 1 · 2026-07-23')
  })
})
