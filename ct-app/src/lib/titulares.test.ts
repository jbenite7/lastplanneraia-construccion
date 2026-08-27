import { describe, expect, it } from 'vitest'
import { construirTitular } from './titulares'
import type { ResumenLookaheadIntermedia, TitularCondicion } from './titulares'

// Paso 1 de Task 7 (rol A, test writer) — fija el contrato de `titulares.ts` ANTES de que exista
// (Paso 2, rol B, lo implementa). Librería pura: sin fetch, sin DOM.
//
// N1 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-20.1): «El titular
// narrativo se produce por plantillas por regla — un juego finito de frases con huecos, elegidas
// por condiciones medibles. Nunca IA generativa, nunca redacción manual. Cada plantilla es
// auditable con la misma trazabilidad que una cifra: condición que la disparó, cifras que la
// llenan.»
//
// Contexto de negocio (CT-8.3): esta es la hoja de Intermedia. El titular ocupa la posición 2 del
// lienzo — después de la alarma de huérfanas (posición 1) y antes de la lista accionable
// (posición 3) — y responde «qué está pasando con el lookahead y por qué».
//
// Forma de entrada — un resumen ya agregado, no las filas crudas de `pi_shared_constraints`:
// agregar (contar huérfanas, contar vencidas) es responsabilidad de quien arme este resumen
// (la página o un lib de agregación posterior), no de la plantilla — mantiene `titulares.ts` como
// una función pura de resumen -> texto, sin lógica de negocio de conteo mezclada con lógica de
// redacción.
// - `huerfanasCount` / `vencidasCount` / `vencidasMaxDias`: mismo criterio que la alarma de
//   huérfanas del lienzo (posición 1) — sin responsable NI fecha de compromiso
//   (`ResponsableAsignado`/`FechaCompromiso` de la migración CT-7.3,
//   database/migrations/20260827_pi_shared_constraints_gestion.sql) para huérfanas; FechaCompromiso
//   vencida para vencidas.
// - `listasRate` espeja `MetricResult` (api.ts) para la métrica YA existente
//   `pi_hard_restrictions_ready_rate` (MetricDictionaryService.php:99, catálogo actual, no una de
//   las 4 nuevas de D58): «SUM(hard_restrictions_ready=1) / COUNT(*)» — `value` es la fracción
//   LISTA (con análisis cumplido), NO la fracción sin análisis. El titular de D59 («el 69% entró
//   sin análisis») es `1 - value`; ver el cálculo en cada plantilla de abajo.
//
// El juego finito de plantillas (orden = prioridad; la primera condición que se cumple gana):
//   1. `huerfanas`               — huerfanasCount > 0
//   2. `vencidas`                — huerfanasCount === 0 && vencidasCount > 0
//   3. `adherencia_insuficiente` — sin las dos anteriores, listasRate.completeness === 'insuficiente'
//   4. `adherencia_baja`         — completeness === 'completa', value conocido, y más del 30% de
//                                  las actividades entra sin análisis (umbral de diseño de este
//                                  paso: 30 puntos porcentuales; el caso citado en D59 es 69%,
//                                  muy por encima)
//   5. `sano`                    — completeness === 'completa', value conocido, <= 30% sin análisis
//   6. `neutral`                 — cualquier otra combinación (p.ej. completeness === 'parcial'):
//                                  NUNCA cadena vacía, aunque ninguna plantilla de negocio aplique

function resumen(overrides: Partial<ResumenLookaheadIntermedia> = {}): ResumenLookaheadIntermedia {
  return {
    huerfanasCount: 0,
    vencidasCount: 0,
    vencidasMaxDias: 0,
    listasRate: { value: 0.85, completeness: 'completa' },
    ...overrides,
  }
}

function condicionesEsperadas(): TitularCondicion[] {
  return ['huerfanas', 'vencidas', 'adherencia_insuficiente', 'adherencia_baja', 'sano', 'neutral']
}

describe('construirTitular — el juego finito de plantillas cubre las seis condiciones declaradas', () => {
  it('el tipo TitularCondicion tiene exactamente estas seis condiciones (documentación ejecutable)', () => {
    // No es una prueba de comportamiento: fija que el juego de plantillas se mantiene finito y
    // que cualquier condición nueva se declara aquí primero, no se agrega calladamente en la
    // implementación.
    expect(condicionesEsperadas()).toHaveLength(6)
  })
})

describe('construirTitular — condición 1: restricciones huérfanas (máxima prioridad)', () => {
  it('con huérfanas presentes, dispara la plantilla de huérfanas con el conteo en los huecos', () => {
    const r = construirTitular(resumen({ huerfanasCount: 4, vencidasCount: 9, listasRate: { value: 0.1, completeness: 'insuficiente' } }))

    expect(r.condicion).toBe('huerfanas')
    expect(r.variables.huerfanas).toBe(4)
    expect(r.texto).toContain('4')
    expect(r.texto.length).toBeGreaterThan(0)
  })

  it('huérfanas gana incluso cuando también hay vencidas — es la alarma más urgente (N4: sin dato = arriba de todo)', () => {
    const r = construirTitular(resumen({ huerfanasCount: 1, vencidasCount: 20 }))

    expect(r.condicion).toBe('huerfanas')
  })

  it('singular: una sola huérfana no produce una frase en plural incoherente', () => {
    const r = construirTitular(resumen({ huerfanasCount: 1 }))

    expect(r.condicion).toBe('huerfanas')
    expect(r.texto).not.toMatch(/1 restricciones/)
  })
})

describe('construirTitular — condición 2: restricciones vencidas', () => {
  it('sin huérfanas pero con vencidas, dispara la plantilla de vencidas con conteo y máximo de días', () => {
    const r = construirTitular(resumen({ huerfanasCount: 0, vencidasCount: 6, vencidasMaxDias: 27 }))

    expect(r.condicion).toBe('vencidas')
    expect(r.variables.vencidas).toBe(6)
    expect(r.variables.diasMax).toBe(27)
    expect(r.texto).toContain('6')
    expect(r.texto).toContain('27')
  })
})

describe('construirTitular — condición 3: adherencia sin dato suficiente', () => {
  it('sin huérfanas ni vencidas, completeness "insuficiente" dispara la plantilla de dato insuficiente', () => {
    const r = construirTitular(resumen({ listasRate: { value: null, completeness: 'insuficiente' } }))

    expect(r.condicion).toBe('adherencia_insuficiente')
    expect(r.texto.length).toBeGreaterThan(0)
  })
})

describe('construirTitular — condiciones 4 y 5: adherencia baja vs. sano, sobre la cifra dura de D59', () => {
  it('69% sin análisis (el caso real citado en D59: value=0.31) dispara "adherencia_baja" con el porcentaje sin análisis en los huecos, no el porcentaje listo', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.31, completeness: 'completa' } }))

    expect(r.condicion).toBe('adherencia_baja')
    expect(r.variables.sinAnalisisPct).toBe(69)
    expect(r.texto).toContain('69')
  })

  it('15% sin análisis (value=0.85) dispara "sano" — bien gestionado, sin alarma', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.85, completeness: 'completa' } }))

    expect(r.condicion).toBe('sano')
    expect(r.variables.sinAnalisisPct).toBe(15)
  })

  it('el umbral es 30% sin análisis: exactamente en el límite (value=0.70) cae del lado "sano", no "baja"', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.7, completeness: 'completa' } }))

    expect(r.condicion).toBe('sano')
    expect(r.variables.sinAnalisisPct).toBe(30)
  })

  it('un punto por encima del umbral (31% sin análisis, value=0.69) ya cae del lado "adherencia_baja"', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.69, completeness: 'completa' } }))

    expect(r.condicion).toBe('adherencia_baja')
  })
})

describe('construirTitular — condición 6: el titular neutro nunca es cadena vacía', () => {
  it('una condición sin plantilla de negocio (completeness "parcial") produce el titular neutro declarado', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.5, completeness: 'parcial' } }))

    expect(r.condicion).toBe('neutral')
    expect(r.texto).not.toBe('')
    expect(r.texto.trim().length).toBeGreaterThan(0)
  })

  it('el titular neutro tampoco es un placeholder disfrazado de vacío (espacios, guion suelto)', () => {
    const r = construirTitular(resumen({ listasRate: { value: 0.5, completeness: 'parcial' } }))

    expect(r.texto.trim()).not.toBe('-')
    expect(r.texto.trim()).not.toBe('—')
  })
})

describe('construirTitular — auditabilidad (N1: condición que disparó + cifras que llenan)', () => {
  it('toda respuesta trae la condición que la disparó y un objeto de variables, aunque esté vacío', () => {
    const r = construirTitular(resumen({ huerfanasCount: 2 }))

    expect(condicionesEsperadas()).toContain(r.condicion)
    expect(typeof r.variables).toBe('object')
    expect(r.variables).not.toBeNull()
  })

  it('nunca devuelve una cadena vacía, para ninguna de las seis condiciones', () => {
    const casos: ResumenLookaheadIntermedia[] = [
      resumen({ huerfanasCount: 3 }),
      resumen({ vencidasCount: 2, vencidasMaxDias: 5 }),
      resumen({ listasRate: { value: null, completeness: 'insuficiente' } }),
      resumen({ listasRate: { value: 0.2, completeness: 'completa' } }),
      resumen({ listasRate: { value: 0.95, completeness: 'completa' } }),
      resumen({ listasRate: { value: 0.5, completeness: 'parcial' } }),
    ]

    for (const caso of casos) {
      const r = construirTitular(caso)
      expect(r.texto.trim().length).toBeGreaterThan(0)
    }
  })
})
