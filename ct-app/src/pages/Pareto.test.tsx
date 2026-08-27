// @vitest-environment jsdom
//
// Tests de Pareto (ct-app, etapa piloto, Task 8 — posición 5 del lienzo de Intermedia, CT-18.3).
// Mismo hallazgo que Semaforo.test.tsx (entrada 20 de la Bitácora del plan): esta posición nunca
// se construyó. Pareto.tsx NO existe todavía — esta corrida debe fallar en RED.
//
// Contrato de datos: `getParetoRestricciones()` (nueva función de `lib/api.ts`, fijada en
// `api.test.ts`, rol A) contra `GET /api/bi/control-tower/restricciones/pareto`
// (`src/Controllers/Api/BiRestrictionParetoController.php`). Forma real leída del código:
// `{distribucion:[{tipo, conteo}], basis:{filas_usadas, corte}}` — `distribucion` YA llega
// ordenada DESC por `conteo` (la query SQL trae `ORDER BY conteo DESC`), así que Pareto.tsx NO
// debe reordenar: pinta el array tal como llega.
//
// `tipo` es el valor CRUDO de `restriction_type` — el controlador documenta que no existe
// diccionario de traducción en el repo hoy ('D_y_E', 'Materiales', 'MdeO', 'Equipos',
// 'Predecesora'). Pareto.tsx muestra ese valor crudo, sin traducir — límite conocido, documentado
// aquí y no resuelto en este sub-paso.
//
// Estados: carga ("Cargando…" antes de resolver), error de red (role="alert", mismo patrón que
// AlarmaHuerfanas/ListaRestricciones/Intermedia), distribución vacía (mensaje explícito de "no hay
// nada pendiente", NUNCA una lista/barra vacía silenciosa), distribución no vacía (cada fila con
// su tipo crudo y su conteo, en el orden recibido).
//
// Selectores estables: contenedor raíz `data-testid="pareto"`, cada fila
// `data-testid={`pareto-fila-${tipo}`}` — mismo patrón de testid por clave de negocio que usa
// Semaforo.test.tsx (`franja-${key}`) y ListaRestricciones (`fila-restriccion-${id}`).

import '@testing-library/jest-dom/vitest'
import { render, screen, waitFor, within } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Pareto } from './Pareto'
import { getParetoRestricciones } from '../lib/api'
import type { ParetoRestriccionesResult } from '../lib/api'

vi.mock('../lib/api', () => ({
  getParetoRestricciones: vi.fn(),
}))

afterEach(() => {
  vi.clearAllMocks()
})

function resultado(overrides: Partial<ParetoRestriccionesResult> = {}): ParetoRestriccionesResult {
  return {
    distribucion: [
      { tipo: 'Materiales', conteo: 12 },
      { tipo: 'MdeO', conteo: 7 },
      { tipo: 'D_y_E', conteo: 3 },
    ],
    basis: { filas_usadas: 22, corte: 'Semana 4, restricciones duras no liberadas' },
    ...overrides,
  }
}

describe('Pareto — estado de carga', () => {
  it('muestra un estado de carga antes de que getParetoRestricciones() resuelva', () => {
    vi.mocked(getParetoRestricciones).mockReturnValue(new Promise(() => {})) // nunca resuelve

    render(<Pareto />)

    expect(screen.getByText(/cargando/i)).toBeInTheDocument()
  })
})

describe('Pareto — distribución no vacía', () => {
  it('llama a getParetoRestricciones() exactamente una vez al montar', async () => {
    vi.mocked(getParetoRestricciones).mockResolvedValue(resultado())

    render(<Pareto />)

    await waitFor(() => expect(getParetoRestricciones).toHaveBeenCalledTimes(1))
  })

  it('renderiza cada fila con su tipo CRUDO (sin traducir) y su conteo, en el orden recibido del backend', async () => {
    vi.mocked(getParetoRestricciones).mockResolvedValue(resultado())

    render(<Pareto />)

    await waitFor(() => expect(screen.getByTestId('pareto-fila-Materiales')).toBeInTheDocument())

    const contenedor = screen.getByTestId('pareto')
    const filas = Array.from(contenedor.querySelectorAll('[data-testid^="pareto-fila-"]'))
    const tiposEnOrden = filas.map((fila) => fila.getAttribute('data-testid'))

    expect(tiposEnOrden).toEqual(['pareto-fila-Materiales', 'pareto-fila-MdeO', 'pareto-fila-D_y_E'])

    // 'D_y_E' se muestra tal cual, no como "Diseño y Estudios" ni ninguna traducción inventada.
    const filaDyE = screen.getByTestId('pareto-fila-D_y_E')
    expect(within(filaDyE).getByText('D_y_E')).toBeInTheDocument()
    expect(within(filaDyE).getByText('3')).toBeInTheDocument()

    const filaMateriales = screen.getByTestId('pareto-fila-Materiales')
    expect(within(filaMateriales).getByText('Materiales')).toBeInTheDocument()
    expect(within(filaMateriales).getByText('12')).toBeInTheDocument()
  })

  it('NO reordena la distribución que ya llegó ordenada del backend, ni siquiera si un conteo fuera igual', async () => {
    // Fixture deliberadamente "casi empatada" para probar que no hay un sort propio que reordene
    // por algún criterio distinto (alfabético, etc.) al que ya trae el backend.
    vi.mocked(getParetoRestricciones).mockResolvedValue(
      resultado({
        distribucion: [
          { tipo: 'Equipos', conteo: 5 },
          { tipo: 'Predecesora', conteo: 5 },
          { tipo: 'Materiales', conteo: 1 },
        ],
      }),
    )

    render(<Pareto />)

    await waitFor(() => expect(screen.getByTestId('pareto-fila-Equipos')).toBeInTheDocument())

    const contenedor = screen.getByTestId('pareto')
    const filas = Array.from(contenedor.querySelectorAll('[data-testid^="pareto-fila-"]'))
    const tiposEnOrden = filas.map((fila) => fila.getAttribute('data-testid'))

    expect(tiposEnOrden).toEqual(['pareto-fila-Equipos', 'pareto-fila-Predecesora', 'pareto-fila-Materiales'])
  })
})

describe('Pareto — distribución vacía', () => {
  it('muestra un mensaje explícito de estado vacío, no una lista/barra vacía silenciosa', async () => {
    vi.mocked(getParetoRestricciones).mockResolvedValue(resultado({ distribucion: [], basis: { filas_usadas: 0, corte: 'x' } }))

    render(<Pareto />)

    await waitFor(() => {
      const contenedor = screen.getByTestId('pareto')
      expect(contenedor.querySelectorAll('[data-testid^="pareto-fila-"]')).toHaveLength(0)
    })
    expect(screen.getByText(/no hay restricciones duras pendientes|sin restricciones pendientes/i)).toBeInTheDocument()
  })
})

describe('Pareto — error de red', () => {
  it('un rechazo de getParetoRestricciones() se muestra con role="alert", sin tumbar el resto del árbol', async () => {
    vi.mocked(getParetoRestricciones).mockRejectedValue(new Error('No se pudo cargar el pareto de restricciones.'))

    render(<Pareto />)

    await waitFor(() => expect(screen.getByRole('alert')).toBeInTheDocument())
    expect(screen.getByRole('alert')).toHaveTextContent('No se pudo cargar el pareto de restricciones.')
  })
})
