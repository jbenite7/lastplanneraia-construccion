import { describe, expect, it } from 'vitest'
import { construirAccionSugerida } from './accionSugerida'
import type { AccionSugerida } from './accionSugerida'

// Sub-paso de Task 7 que cierra D89 (rol A, test writer) — fija el contrato de
// `accionSugerida.ts` ANTES de que exista (rol B lo implementa en una tarea separada).
// Librería pura: sin fetch, sin DOM, sin dependencia del backend.
//
// D89 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-8.3): «Cada alerta trae su
// acción sugerida y a quién acudir. "Materiales sin liberar: llame al proveedor, o escale a
// compras." Sale del hallazgo más incómodo de la entrevista: "sabía que se iba a caer y no hice
// nada porque no sabía cómo resolverlo." Señalar el problema no basta si quien lo ve no sabe qué
// hacer.»
//
// Ruling previo (.superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/progress.md, entrada D89):
// `ActionRecommendationService::actionsFromPI()` (PHP) genera un TOP-5 GLOBAL de acciones para el
// brief ejecutivo (filtra `is_hard=1 && is_ready=0`, corta a 5) — grano distinto al que D89 pide
// aquí, que es UNA acción por CADA fila individual de la lista de restricciones (CT-8.3, posición
// 3 del lienzo de Intermedia). Por eso esta librería no toca `ActionRecommendationService` ni el
// backend: es plantillas por tipo de restricción, mismo patrón que `titulares.ts` (N1, CT-20.1) —
// un juego finito de frases con huecos, elegidas por condición medible (aquí, el tipo de
// restricción), nunca IA generativa ni redacción manual.
//
// Distinción importante con CT-20.1 (nota técnica bajo N1-N11): «las acciones recomendadas [de la
// hoja 8.1, panorama de obras] derivan su dueño del rol (el director de la obra señalada), según
// D20 y D89» — ESE es el mecanismo de `ActionRecommendationService`/top-5 del brief ejecutivo, con
// un contacto personalizado a la obra concreta. Esta librería es OTRO mecanismo, para OTRA hoja
// (8.3, lista de restricciones): el "contacto" que produce es un rol GENÉRICO por tipo de
// restricción (a quién acudir en general — "Proveedor / Compras", no "Juan Pérez de la obra X"),
// no personalizado al proyecto ni derivado de sesión. No confundir ambos D89.
//
// Catálogo cerrado de restricciones duras (CT-15, D51): D_y_E, Materiales, MdeO, Equipos,
// Predecesora — verificado contra los valores reales de `pi_shared_constraints.Restriccion` en dev
// (2026-08-26): el catálogo trae exactamente estos 5 más "ruido" que no calza con ninguno
// (`Pdto_Cons`, `Modelo`, `restriccion_pc_1`) — de ahí la importancia del caso "dato sucio" de
// abajo: no es hipotético, existe hoy en la base de desarrollo.
//
// Las 5 plantillas (texto + contacto), decisión de diseño de este paso, documentadas una por una:
//
// - `Materiales` — el ejemplo textual YA dado por la spec: "llame al proveedor, o escale a
//   compras". Contacto: "Proveedor / Compras".
// - `D_y_E` (diseño y especificaciones) — el catálogo del backend (`MetricDictionaryService.php`)
//   llama a esta restricción "diseño y especificaciones"; quien resuelve un plano o especificación
//   pendiente en AIA es la Oficina Técnica, escalando al diseñador si no hay respuesta. Contacto:
//   "Oficina Técnica / Diseño".
// - `MdeO` (mano de obra) — la mano de obra la gestiona el subcontratista que la aporta; si no
//   responde, escala al residente de la obra, que es quien puede reasignar cuadrillas. Contacto:
//   "Subcontratista / Residente".
// - `Equipos` — mismo patrón que Materiales (es logística de suministro): el proveedor del equipo
//   primero, compras como escalamiento si el proveedor no libera. Contacto: "Proveedor de equipos
//   / Compras".
// - `Predecesora` (actividad predecesora incompleta o no ejecutada) — no es un problema de
//   suministro sino de secuencia: quien puede desbloquearla es el residente (si la predecesora es
//   propia de la obra) o quien lleva la programación general si el bloqueo es de otro frente.
//   Contacto: "Residente / Programación".
//
// Dato sucio / tipo futuro sin plantilla (N1: "una condición sin plantilla produce el titular
// neutro, jamás cadena vacía" — mismo principio aplicado aquí): `construirAccionSugerida` NUNCA
// lanza excepción ni devuelve texto/contacto vacíos para un `tipoRestriccion` que no calce con
// ninguna de las 5 plantillas duras. Produce una acción neutra declarada, con el residente de obra
// como contacto genérico de última instancia (es quien ya recibe el resto de las alarmas de la
// hoja, D33/D87).
//
// Coincidencia exacta, sin normalizar mayúsculas ni espacios: los 5 valores llegan tal cual desde
// `restriccion` (columna `Restriccion` de `pi_shared_constraints`, ver `Restriccion.restriccion`
// en api.ts) — es un enum de backend, no texto libre de usuario. Una variante de capitalización
// (`'materiales'` en vez de `'Materiales'`) cuenta como dato sucio y cae en la plantilla neutra,
// no en un match relajado que podría ocultar un catálogo desalineado entre backend y frontend.

const RESTRICCIONES_DURAS = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'] as const

function esNoVacio(s: string): boolean {
  return s.trim().length > 0
}

describe('construirAccionSugerida — el catálogo cerrado de 5 restricciones duras (CT-15, D51)', () => {
  it('produce una AccionSugerida con texto y contacto no vacíos para cada una de las 5 restricciones', () => {
    for (const tipo of RESTRICCIONES_DURAS) {
      const r: AccionSugerida = construirAccionSugerida(tipo)

      expect(esNoVacio(r.texto)).toBe(true)
      expect(esNoVacio(r.contacto)).toBe(true)
    }
  })

  it('las 5 plantillas son genuinamente distintas entre sí, no una copia con el nombre cambiado', () => {
    const resultados = RESTRICCIONES_DURAS.map((tipo) => construirAccionSugerida(tipo))

    const textos = new Set(resultados.map((r) => r.texto))
    const contactos = new Set(resultados.map((r) => r.contacto))

    expect(textos.size).toBe(RESTRICCIONES_DURAS.length)
    expect(contactos.size).toBe(RESTRICCIONES_DURAS.length)
  })
})

describe('construirAccionSugerida — Materiales (el ejemplo textual dado por D89)', () => {
  it('menciona proveedor y compras, como cita literalmente la spec', () => {
    const r = construirAccionSugerida('Materiales')

    expect(r.texto.toLowerCase()).toContain('proveedor')
    expect(r.texto.toLowerCase()).toContain('compras')
    expect(r.contacto).toBe('Proveedor / Compras')
  })
})

describe('construirAccionSugerida — D_y_E (diseño y especificaciones)', () => {
  it('dirige a la oficina técnica o diseño, no a compras ni al residente', () => {
    const r = construirAccionSugerida('D_y_E')

    expect(r.contacto).toMatch(/Oficina Técnica|Diseño/)
    expect(r.contacto).not.toMatch(/Compras|Residente/)
  })
})

describe('construirAccionSugerida — MdeO (mano de obra)', () => {
  it('dirige al subcontratista o al residente, no a compras ni a un proveedor de materiales', () => {
    const r = construirAccionSugerida('MdeO')

    expect(r.contacto).toMatch(/Subcontratista|Residente/)
    expect(r.contacto).not.toMatch(/Proveedor|Compras/)
  })
})

describe('construirAccionSugerida — Equipos', () => {
  it('dirige a un proveedor de equipos o a compras, distinguible del contacto de Materiales', () => {
    const equipos = construirAccionSugerida('Equipos')
    const materiales = construirAccionSugerida('Materiales')

    expect(equipos.contacto.toLowerCase()).toContain('equipo')
    expect(equipos.contacto).not.toBe(materiales.contacto)
  })
})

describe('construirAccionSugerida — Predecesora (actividad predecesora incompleta o no ejecutada)', () => {
  it('dirige al residente o a programación, no a un proveedor externo', () => {
    const r = construirAccionSugerida('Predecesora')

    expect(r.contacto).toMatch(/Residente|Programación/)
    expect(r.contacto).not.toMatch(/Proveedor|Compras|Oficina Técnica/)
  })
})

describe('construirAccionSugerida — dato sucio: tipo que no calza con ninguna plantilla dura', () => {
  it('un tipo desconocido nunca lanza excepción', () => {
    expect(() => construirAccionSugerida('tipo_que_no_existe')).not.toThrow()
  })

  it('un tipo desconocido produce una acción neutra declarada, nunca texto ni contacto vacíos', () => {
    const r = construirAccionSugerida('tipo_que_no_existe')

    expect(esNoVacio(r.texto)).toBe(true)
    expect(esNoVacio(r.contacto)).toBe(true)
  })

  it('valores reales de "ruido" presentes hoy en pi_shared_constraints.Restriccion (dev, 2026-08-26) caen en la plantilla neutra, no en una de las 5 duras', () => {
    const ruidoReal = ['Pdto_Cons', 'Modelo', 'restriccion_pc_1']

    for (const tipo of ruidoReal) {
      const r = construirAccionSugerida(tipo)
      expect(esNoVacio(r.texto)).toBe(true)
      expect(esNoVacio(r.contacto)).toBe(true)
    }
  })

  it('cadena vacía como tipoRestriccion también cae en la plantilla neutra, sin excepción', () => {
    expect(() => construirAccionSugerida('')).not.toThrow()
    const r = construirAccionSugerida('')
    expect(esNoVacio(r.texto)).toBe(true)
    expect(esNoVacio(r.contacto)).toBe(true)
  })

  it('el texto neutro no es un placeholder disfrazado de vacío (espacios, guion suelto)', () => {
    const r = construirAccionSugerida('tipo_que_no_existe')

    expect(r.texto.trim()).not.toBe('-')
    expect(r.texto.trim()).not.toBe('—')
    expect(r.contacto.trim()).not.toBe('-')
    expect(r.contacto.trim()).not.toBe('—')
  })

  it('coincidencia exacta, sin normalizar: una variante de capitalización de un tipo duro cae en la plantilla neutra, no en la dura', () => {
    const durElMalEscrito = construirAccionSugerida('materiales') // minúscula, no calza con 'Materiales'
    const materialesReal = construirAccionSugerida('Materiales')

    expect(durElMalEscrito.contacto).not.toBe(materialesReal.contacto)
  })
})

describe('construirAccionSugerida — auditabilidad (mismo principio que N1: nunca string vacío, para ningún caso)', () => {
  it('nunca devuelve texto ni contacto vacíos, para ninguno de los 5 tipos duros ni para dato sucio', () => {
    const casos = [...RESTRICCIONES_DURAS, 'tipo_futuro_no_declarado', '', 'Pdto_Cons']

    for (const tipo of casos) {
      const r = construirAccionSugerida(tipo)
      expect(esNoVacio(r.texto)).toBe(true)
      expect(esNoVacio(r.contacto)).toBe(true)
    }
  })
})
