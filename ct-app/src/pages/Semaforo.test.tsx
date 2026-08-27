// @vitest-environment jsdom
//
// Tests de Semaforo (ct-app, etapa piloto, Task 8 — posición 4 del lienzo de Intermedia, CT-18.3).
// Hallazgo de esta sesión (entrada 20 de la Bitácora del plan
// `docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md`): las posiciones 4 y 5 nunca se
// construyeron pese a que la tarea que las debía traer se cerró como completa. Este archivo es el
// rol A (test writer) de esa reparación — Semaforo.tsx NO existe todavía, así que esta corrida
// debe fallar en RED.
//
// CONTRATO DE NEGOCIO (decisión de Felipe, cita textual de la entrada 20): «semana 0 va en rojo
// cuando las restricciones duras tienen pendientes, es la más urgente; si ya tiene sus
// restricciones duras liberadas, verde». El color de cada franja lo da si sus restricciones duras
// están liberadas, NO la cercanía de la franja — pero cuando SÍ hay pendientes, la urgencia de esos
// pendientes SÍ depende de qué tan cerca está la franja de ejecutarse: los pendientes de la semana
// 0 bloquean trabajo que debía empezar YA (crítico); los pendientes de semana 5-6 son el trabajo
// normal de un lookahead que apenas se está preparando (informativo, no crítico). Semana 1-2 y 3-4
// quedan en medio: pendientes ahí SÍ importan pronto, pero no son la emergencia de hoy.
//
// Mapeo franja × pendientes -> estado (documentado aquí porque el brief no lo fija numéricamente;
// se deriva del contrato de negocio de arriba usando el vocabulario YA EXISTENTE en
// `docs/design-system/state-semantics.json` — dimensiones `severity` {none,low,medium,high} y
// `urgency` {none,soon,now}, mismos atributos que ya emite
// `DesignSystemComponent::semanticAttributes()` en PHP (`data-aia-severity`, `data-aia-urgency`).
// No se inventan clases de color nuevas ni tokens nuevos, solo se combinan los 4 niveles ya
// definidos (`neutral`, `healthy`, `attention`, `urgent`):
//
//   pendientes === 0 (cualquier franja)      -> healthy:   severity=low,    urgency=none
//   completeness === 'insuficiente'           -> neutral:   severity=none,   urgency=none (SIEMPRE,
//                                                 sin importar listas/pendientes — 0 de 0 no es
//                                                 "liberado", es "no hay dato")
//   franja "0" con pendientes > 0             -> urgent:    severity=high,   urgency=now
//   franja "1-2" o "3-4" con pendientes > 0    -> attention: severity=medium, urgency=soon
//   franja "5-6" con pendientes > 0            -> neutral:   severity=none,   urgency=none (trabajo
//                                                 normal del lookahead, no una alarma)
//
// Las 4 franjas se renderizan SIEMPRE, en el mismo orden (0 -> 1-2 -> 3-4 -> 5-6), cada una con:
// - su etiqueta legible,
// - el par listas/pendientes (derivado de `value` y `basis.filas_usadas` del MetricResult:
//   listas = Math.round(value * filas_usadas), pendientes = filas_usadas - listas),
// - `data-aia-severity` / `data-aia-urgency` en el nodo raíz de la franja según la tabla de arriba.
//
// Contrato de fetch: Semaforo se auto-alimenta con 4 llamadas independientes a `getMetric()` (una
// por franja, mismas claves que ya declaró el catálogo del backend:
// `pi_semaforo_semana_0`, `pi_semaforo_semana_1_2`, `pi_semaforo_semana_3_4`,
// `pi_semaforo_semana_5_6`) — mismo patrón que ya usa Intermedia.tsx para
// `pi_hard_restrictions_ready_rate`: cada franja resuelve o degrada SOLA, un fallo de red en una no
// tumba a las otras tres (ni role="alert" global).
//
// Selectores estables: contenedor raíz `data-testid="semaforo"`, cada fila
// `data-testid={`franja-${key}`}` (key = la clave de métrica exacta, p. ej.
// `franja-pi_semaforo_semana_0`) — mismo patrón que `data-testid={`fila-restriccion-${id}`}` en
// ListaRestricciones.

import '@testing-library/jest-dom/vitest'
import { render, screen, waitFor, within } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Semaforo } from './Semaforo'
import { getMetric } from '../lib/api'
import type { MetricResult } from '../lib/api'

vi.mock('../lib/api', () => ({
  getMetric: vi.fn(),
}))

const CLAVES_FRANJAS = [
  'pi_semaforo_semana_0',
  'pi_semaforo_semana_1_2',
  'pi_semaforo_semana_3_4',
  'pi_semaforo_semana_5_6',
] as const

function metricResult(overrides: Partial<MetricResult> = {}): MetricResult {
  return {
    value: 1,
    basis: { obras_incluidas: 1, obras_esperadas: 1, corte: '2026-08-26', filas_usadas: 10 },
    completeness: 'completa',
    missing: [],
    ...overrides,
  }
}

/** Configura getMetric() para resolver según la clave pedida, con un mapa completo por defecto. */
function mockMetricasPorClave(mapa: Partial<Record<(typeof CLAVES_FRANJAS)[number], MetricResult>>) {
  const base: Record<(typeof CLAVES_FRANJAS)[number], MetricResult> = {
    pi_semaforo_semana_0: metricResult(),
    pi_semaforo_semana_1_2: metricResult(),
    pi_semaforo_semana_3_4: metricResult(),
    pi_semaforo_semana_5_6: metricResult(),
    ...mapa,
  }
  vi.mocked(getMetric).mockImplementation((clave: string) => {
    const resultado = base[clave as (typeof CLAVES_FRANJAS)[number]]
    if (!resultado) return Promise.reject(new Error(`clave inesperada: ${clave}`))
    return Promise.resolve(resultado)
  })
}

afterEach(() => {
  vi.clearAllMocks()
})

describe('Semaforo — carga las 4 franjas de forma independiente', () => {
  it('llama a getMetric() una vez por cada una de las 4 claves del catálogo, sin duplicar', async () => {
    mockMetricasPorClave({})

    render(<Semaforo />)

    await waitFor(() => expect(getMetric).toHaveBeenCalledTimes(4))
    const clavesLlamadas = vi.mocked(getMetric).mock.calls.map((llamada) => llamada[0])
    expect(clavesLlamadas.sort()).toEqual([...CLAVES_FRANJAS].sort())
  })

  it('renderiza las 4 franjas siempre, en el orden 0 -> 1-2 -> 3-4 -> 5-6', async () => {
    mockMetricasPorClave({})

    render(<Semaforo />)

    await waitFor(() => expect(screen.getByTestId(`franja-${CLAVES_FRANJAS[3]}`)).toBeInTheDocument())

    const contenedor = screen.getByTestId('semaforo')
    const testIds = CLAVES_FRANJAS.map((clave) => `franja-${clave}`)
    const posiciones = testIds.map((id) => within(contenedor).getByTestId(id))
    const ordenEnDom = Array.from(contenedor.querySelectorAll('[data-testid^="franja-"]'))

    expect(orderOf(ordenEnDom, posiciones)).toEqual([0, 1, 2, 3])
  })
})

function orderOf(domOrder: Element[], nodes: HTMLElement[]): number[] {
  return nodes.map((n) => domOrder.indexOf(n))
}

describe('Semaforo — mapeo franja x pendientes -> estado (contrato de Felipe, entrada 20)', () => {
  it('franja con 0 pendientes (100% liberado) se pinta healthy: severity=low, urgency=none — para CUALQUIER franja', async () => {
    // value=1 sobre filas_usadas=10 -> listas=10, pendientes=0, en las 4 franjas.
    mockMetricasPorClave({
      pi_semaforo_semana_0: metricResult({ value: 1, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 10 } }),
      pi_semaforo_semana_5_6: metricResult({ value: 1, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 6 } }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila0 = screen.getByTestId('franja-pi_semaforo_semana_0')
      expect(fila0).toHaveAttribute('data-aia-severity', 'low')
      expect(fila0).toHaveAttribute('data-aia-urgency', 'none')
    })
    const fila56 = screen.getByTestId('franja-pi_semaforo_semana_5_6')
    expect(fila56).toHaveAttribute('data-aia-severity', 'low')
    expect(fila56).toHaveAttribute('data-aia-urgency', 'none')
  })

  it('franja "0" con pendientes se pinta urgent: severity=high, urgency=now (la más urgente, cita de Felipe)', async () => {
    // value=0.4 sobre filas_usadas=10 -> listas=4, pendientes=6.
    mockMetricasPorClave({
      pi_semaforo_semana_0: metricResult({ value: 0.4, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 10 } }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila = screen.getByTestId('franja-pi_semaforo_semana_0')
      expect(fila).toHaveAttribute('data-aia-severity', 'high')
      expect(fila).toHaveAttribute('data-aia-urgency', 'now')
    })
  })

  it.each([
    ['pi_semaforo_semana_1_2', 'Semana 1-2'],
    ['pi_semaforo_semana_3_4', 'Semana 3-4'],
  ] as const)('franja %s con pendientes se pinta attention: severity=medium, urgency=soon (no la emergencia de hoy, pero pronto)', async (clave, _etiqueta) => {
    mockMetricasPorClave({
      [clave]: metricResult({ value: 0.6, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 10 } }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila = screen.getByTestId(`franja-${clave}`)
      expect(fila).toHaveAttribute('data-aia-severity', 'medium')
      expect(fila).toHaveAttribute('data-aia-urgency', 'soon')
    })
  })

  it('franja "5-6" con pendientes se pinta neutral: severity=none, urgency=none — trabajo normal del lookahead, no una alarma', async () => {
    mockMetricasPorClave({
      pi_semaforo_semana_5_6: metricResult({ value: 0.5, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 6 } }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila = screen.getByTestId('franja-pi_semaforo_semana_5_6')
      expect(fila).toHaveAttribute('data-aia-severity', 'none')
      expect(fila).toHaveAttribute('data-aia-urgency', 'none')
    })
  })

  it('denominador 0 (completeness insuficiente) se pinta neutral SIEMPRE, nunca como "0 pendientes = todo liberado"', async () => {
    mockMetricasPorClave({
      pi_semaforo_semana_0: metricResult({
        value: null,
        basis: { obras_incluidas: 0, obras_esperadas: 1, corte: 'x', filas_usadas: 0 },
        completeness: 'insuficiente',
        missing: ['sin_filas_que_cumplan_los_filtros'],
      }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila = screen.getByTestId('franja-pi_semaforo_semana_0')
      expect(fila).toHaveAttribute('data-aia-severity', 'none')
      expect(fila).toHaveAttribute('data-aia-urgency', 'none')
      // No debe leerse como "0 pendientes": es un estado de "sin dato", no de éxito.
      expect(within(fila).queryByText(/0 pendientes/i)).not.toBeInTheDocument()
    })
  })
})

describe('Semaforo — el par listas/pendientes se deriva de value y basis.filas_usadas', () => {
  it('muestra listas = round(value * filas_usadas) y pendientes = filas_usadas - listas', async () => {
    // value=0.5833333333333334, filas_usadas=12 -> listas=round(7)=7, pendientes=5.
    mockMetricasPorClave({
      pi_semaforo_semana_3_4: metricResult({
        value: 0.5833333333333334,
        basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 12 },
      }),
    })

    render(<Semaforo />)

    await waitFor(() => {
      const fila = screen.getByTestId('franja-pi_semaforo_semana_3_4')
      expect(within(fila).getByText(/7/)).toBeInTheDocument()
      expect(within(fila).getByText(/5/)).toBeInTheDocument()
    })
  })
})

describe('Semaforo — un error de red en una franja no tumba a las otras 3', () => {
  it('la franja que falla muestra su propio estado de error; las otras 3 siguen mostrando su par listas/pendientes', async () => {
    vi.mocked(getMetric).mockImplementation((clave: string) => {
      if (clave === 'pi_semaforo_semana_1_2') return Promise.reject(new Error('network down'))
      return Promise.resolve(metricResult({ value: 1, basis: { obras_incluidas: 1, obras_esperadas: 1, corte: 'x', filas_usadas: 10 } }))
    })

    render(<Semaforo />)

    await waitFor(() => {
      const filaFallida = screen.getByTestId('franja-pi_semaforo_semana_1_2')
      expect(within(filaFallida).getByRole('alert')).toBeInTheDocument()
    })

    // Las otras 3 siguen vivas y sin alerta.
    for (const clave of ['pi_semaforo_semana_0', 'pi_semaforo_semana_3_4', 'pi_semaforo_semana_5_6']) {
      const fila = screen.getByTestId(`franja-${clave}`)
      expect(within(fila).queryByRole('alert')).not.toBeInTheDocument()
      expect(fila).toHaveAttribute('data-aia-severity', 'low')
    }
  })
})
