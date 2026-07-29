import { describe, expect, it } from 'vitest'
import { PANTALLAS } from './navegacion'

describe('PANTALLAS', () => {
  it('la pantalla del cargue de Excel se llama «Cargar presupuesto»', () => {
    expect(PANTALLAS[0]).toEqual({ ruta: '/ensamble/importar', etiqueta: 'Cargar presupuesto' })
  })

  it('ninguna pantalla se llama «Ensamble»: esa palabra nombra la etapa entera', () => {
    // Un mismo nombre haciendo de etiqueta de etapa y de nombre de pantalla dejaba a quien leía la
    // barra sin forma de saber que las otras cinco cuelgan de la primera.
    expect(PANTALLAS.map((p) => p.etiqueta)).not.toContain('Ensamble')
  })

  it('son las seis pantallas del ensamble, en el orden del flujo', () => {
    expect(PANTALLAS.map((p) => p.ruta)).toEqual([
      '/ensamble/importar',
      '/ensamble/maestro',
      '/ensamble/presupuesto',
      '/ensamble/comparar',
      '/ensamble/paquetes',
      '/ensamble/plan',
    ])
  })

  it('ninguna etiqueta se repite: dos pestañas con el mismo nombre no se pueden distinguir', () => {
    expect(new Set(PANTALLAS.map((p) => p.etiqueta)).size).toBe(PANTALLAS.length)
  })
})
