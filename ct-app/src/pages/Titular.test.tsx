// @vitest-environment jsdom
//
// Tests de Titular (ct-app, etapa piloto, Task 7 ensamblaje — posición 2 del lienzo de Intermedia,
// CT-8.3: «qué está pasando con el lookahead y por qué»).
//
// Titular es PRESENTACIONAL Y PURO en el sentido de props: recibe un `ResumenLookaheadIntermedia`
// YA agregado (mismo tipo que consume `construirTitular()` en `lib/titulares.ts`, Task 7 paso 2) y
// pinta `construirTitular(resumen).texto` tal cual — no reimplementa la redacción ni el orden de
// prioridad de las seis condiciones, eso ya está fijado y probado en `titulares.test.ts`.
//
// GAP investigado y documentado en el reporte de este sub-paso: no existe hoy un endpoint HTTP que
// ejecute `MetricExecutor::execute('pi_hard_restrictions_ready_rate', ...)` y devuelva un
// `MetricResult` a ct-app. Confirmado por grep: `MetricExecutor` nunca se invoca desde ningún
// Controller (`src/Controllers/`), solo se menciona en comentarios de
// `MetricDictionaryService.php`. Resolver ese endpoint es alcance de otro sub-paso (backend), NO
// de este. Fallback de ESTE sub-paso: Titular no calcula `listasRate` — lo recibe ya resuelto
// dentro de `resumen`, y quien ensambla Intermedia.tsx decide qué poner ahí hasta que el endpoint
// exista. El fallback que fija `Intermedia.test.tsx` es
// `{ value: null, completeness: 'insuficiente' }`, que cae en la plantilla
// `adherencia_insuficiente` ("No hay datos suficientes todavía...") — una afirmación honesta sobre
// un dato que hoy no se puede calcular, no un valor inventado.

import '@testing-library/jest-dom/vitest'
import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Titular } from './Titular'
import { construirTitular } from '../lib/titulares'
import type { ResumenLookaheadIntermedia } from '../lib/titulares'

function resumen(overrides: Partial<ResumenLookaheadIntermedia> = {}): ResumenLookaheadIntermedia {
  return {
    huerfanasCount: 0,
    vencidasCount: 0,
    vencidasMaxDias: 0,
    listasRate: { value: 0.85, completeness: 'completa' },
    ...overrides,
  }
}

describe('Titular — pinta el resultado de construirTitular() tal cual, sin reimplementar la redacción', () => {
  it.each([
    ['huerfanas', resumen({ huerfanasCount: 4 })],
    ['vencidas', resumen({ vencidasCount: 2, vencidasMaxDias: 9 })],
    [
      'adherencia_insuficiente (fallback de listasRate sin endpoint, ver gap arriba)',
      resumen({ listasRate: { value: null, completeness: 'insuficiente' } }),
    ],
    ['sano', resumen({ listasRate: { value: 0.9, completeness: 'completa' } })],
    ['adherencia_baja', resumen({ listasRate: { value: 0.5, completeness: 'completa' } })],
    ['neutral', resumen({ listasRate: { value: null, completeness: 'parcial' } })],
  ] as const)('condición %s: el texto en pantalla es exactamente el que produce construirTitular()', (_nombre, r) => {
    const esperado = construirTitular(r).texto

    render(<Titular resumen={r} />)

    expect(screen.getByText(esperado)).toBeInTheDocument()
  })
})
