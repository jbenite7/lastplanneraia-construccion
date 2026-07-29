import { describe, expect, it } from 'vitest'
import { ACCION_PROPONER, claveInsumo, estadoInicialPaquetes, filtroInicial, paquetesReducer, estaCerradoPorValor, muestraTipoNegociacion } from './paquetesState'
import { TIPOS_NEGOCIACION } from './types'
import type { SugerenciaPaquete } from './types'

const sug: SugerenciaPaquete = {
  descripcionNorm: 'PISO CERAMICO 30X30', unidad: 'M2',
  paqueteId: 7, paqueteNombre: 'Pisos', capa: 'exacta', confianza: 'alta', evidencia: 'en 2 proyectos',
}

describe('paquetesReducer', () => {
  it('selección: toggle, todos, limpiar', () => {
    const k = claveInsumo('PISO CERAMICO 30X30', 'M2')
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(true)
    s = paquetesReducer(s, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(false)
    s = paquetesReducer(s, { type: 'SEL_TODOS', claves: [k, claveInsumo('AYUDANTE', 'HC')] })
    expect(s.seleccion.size).toBe(2)
    s = paquetesReducer(s, { type: 'LIMPIAR_SEL' })
    expect(s.seleccion.size).toBe(0)
  })

  it('sugerencias se indexan por clave y se limpian', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'SUGERENCIAS_OK', sugerencias: [sug] })
    expect(s.sugerencias.get(claveInsumo(sug.descripcionNorm, sug.unidad))?.paqueteId).toBe(7)
    expect(s.ocupado).toBe(false)
    s = paquetesReducer(s, { type: 'LIMPIAR_SUGERENCIAS' })
    expect(s.sugerencias.size).toBe(0)
  })

  it('ocupado/listo/fallo', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    s = paquetesReducer(s, { type: 'LISTO', mensaje: '2 asignados' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('2 asignados')
    s = paquetesReducer(s, { type: 'FALLO', mensaje: 'error' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('error')
  })

  it('claveInsumo combina norma y unidad de forma estable', () => {
    expect(claveInsumo('A', 'M2')).toBe('A@@M2')
    expect(claveInsumo('A', 'M2')).not.toBe(claveInsumo('A', 'KG'))
  })
})

describe('filtroInicial', () => {
  it('abre en «sin asignar» cuando queda algo pendiente', () => {
    // El caso real de la revisión: 1 insumo suelto entre 396 filas, invisible con el filtro en
    // «Todos». Lo primero que se ve tiene que ser el trabajo que falta, no el que ya está hecho.
    expect(filtroInicial({ sinAsignar: 1, total: 396 })).toBe('sin_asignar')
  })

  it('abre en «todos» cuando ya no queda nada', () => {
    expect(filtroInicial({ sinAsignar: 0, total: 396 })).toBe('todos')
  })

  it('sin datos todavía, no adivina: se queda en todos', () => {
    expect(filtroInicial(null)).toBe('todos')
  })
})

describe('ACCION_PROPONER — el botón único de propuestas', () => {
  it('proponer no escribe: nada se guarda hasta que la persona confirma', () => {
    expect(ACCION_PROPONER.escribe).toBe(false)
  })

  it('pide sugerencias por lectura, no por un endpoint de asignación', () => {
    expect(ACCION_PROPONER.endpoint).toBe('/plan-compras/api/paquetes/sugerencias')
    expect(ACCION_PROPONER.endpoint).not.toContain('asignar')
  })

  it('el nombre se entiende sin conocer el motor por dentro', () => {
    // «Sembrar 1ª iteración» era vocabulario del desarrollo: el dueño del producto tuvo que
    // preguntar qué hacía el botón antes de atreverse a pulsarlo.
    const etiqueta = ACCION_PROPONER.etiqueta.toLowerCase()
    expect(etiqueta).not.toContain('sembrar')
    expect(etiqueta).not.toContain('iteración')
    expect(etiqueta).toContain('proponer')
  })
})

describe('estaCerradoPorValor', () => {
  it('el 100 % por valor cierra la pantalla', () => {
    expect(estaCerradoPorValor(100)).toBe(true)
  })

  it('cualquier otra cosa no', () => {
    expect(estaCerradoPorValor(99)).toBe(false)
    expect(estaCerradoPorValor(0)).toBe(false)
    // Sin dato no se decide que está cerrado: el endpoint puede no traerlo.
    expect(estaCerradoPorValor(undefined)).toBe(false)
  })
})

describe('muestraTipoNegociacion', () => {
  it('«no aplica» no se enseña: al lado de la modalidad no dice nada', () => {
    expect(muestraTipoNegociacion('no_aplica')).toBe(false)
  })

  it('los cuatro tipos que sí describen una compra se mantienen', () => {
    expect(muestraTipoNegociacion('a_todo_costo')).toBe(true)
    expect(muestraTipoNegociacion('mano_obra')).toBe(true)
    expect(muestraTipoNegociacion('suministro')).toBe(true)
    expect(muestraTipoNegociacion('consumibles')).toBe(true)
  })

  it('el suministro a demanda recupera su badge: era cierto y el parche viejo lo escondía', () => {
    // «Ferretería y consumibles de obra» es consumo_directo, pero se suministra de verdad.
    expect(muestraTipoNegociacion('suministro')).toBe(true)
  })

  it('sin tipo se enseña: el default del catálogo es «a todo costo»', () => {
    expect(muestraTipoNegociacion(undefined)).toBe(true)
  })
})

describe('TIPOS_NEGOCIACION', () => {
  /*
   * Esta lista alimenta a la vez las etiquetas, los filtros y los formularios de creación, así que
   * tiene que coincidir EXACTAMENTE con `PaquetesService::TIPOS` (src/Services/Pdc/PaquetesService.php
   * en lps-aia): `crearPaquete()` valida contra esa constante y devuelve PAQUETE_INVALIDO —sin
   * explicar nada— ante cualquier valor que aquí sobre. Ya pasó una vez, al agregar `no_aplica` a la
   * SPA antes que al PHP; costó partir la lista en dos hasta que el backend se puso al día.
   */
  it('son exactamente los cinco tipos que el backend acepta', () => {
    expect(TIPOS_NEGOCIACION.map((t) => t.value)).toEqual([
      'a_todo_costo', 'suministro', 'mano_obra', 'consumibles', 'no_aplica',
    ])
  })

  it('todos tienen etiqueta en español: un badge nunca debe mostrar el valor crudo', () => {
    for (const t of TIPOS_NEGOCIACION) {
      expect(t.label.trim()).not.toBe('')
      expect(t.label).not.toContain('_')
    }
  })
})
