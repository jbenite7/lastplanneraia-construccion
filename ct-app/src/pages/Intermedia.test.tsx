// @vitest-environment jsdom
//
// Tests de Intermedia (ct-app, etapa piloto, Task 7 ensamblaje) — ensambla el lienzo completo de
// la hoja de Intermedia (CT-8.3): alarma de huérfanas (posición 1) + titular narrativo (posición
// 2) + lista de restricciones (posición 3, ya construida en Task 7 paso 3b). El semáforo (4) y el
// Pareto (5) NO entran en este sub-paso — quedan fuera de alcance a propósito.
//
// Decisión central de este sub-paso (documentada en el reporte del rol A): Intermedia.tsx trae
// los datos UNA sola vez con `getRestricciones()` y deriva de ahí tanto el resumen que necesita
// Titular (huerfanasCount, vencidasCount, vencidasMaxDias) como el array que necesita
// AlarmaHuerfanas (huerfanas) y el que necesita ListaRestricciones (todas, o solo huérfanas si el
// usuario usó el filtro). Esto exige que ListaRestricciones.tsx deje de hacer SIEMPRE su propio
// fetch: se le agrega una prop opcional `restricciones?: Restriccion[]` — cuando Intermedia se la
// pasa, ListaRestricciones NO llama a getRestricciones() por su cuenta (evita el fetch duplicado
// que estos tests verifican contando llamadas al mock). Cuando se omite (como en
// ListaRestricciones.test.tsx, que sigue sin tocarse), el comportamiento actual no cambia:
// self-fetch al montar. Es un cambio aditivo y mínimo — no se tocó el flujo interno de
// PanelGestion ni el manejo de estado tras un guardado exitoso.
//
// Concern documentado, NO cubierto por estos tests a propósito (ver reporte): si el usuario
// gestiona una huérfana desde el panel dentro de esta misma sesión, ListaRestricciones actualiza
// SU copia local (comportamiento ya fijado por Task 7 paso 3b), pero el conteo que muestra
// AlarmaHuerfanas (derivado de la copia que sostiene Intermedia) no se refresca hasta un
// remount/refetch. Sincronizar ambas copias es un refactor más profundo (levantar el estado de
// gestión hasta Intermedia) que se consideró de costo alto para el alcance de este sub-paso —
// queda anotado como pendiente, no como bug de estos tests.
//
// Criterios de agregación usados por las fixtures de abajo (mismos que fija AlarmaHuerfanas.test.tsx
// y el comentario de ResumenLookaheadIntermedia en titulares.ts):
// - huérfana: `estadoLiberacion === 'sin_gestionar' && responsableAsignado === null`.
// - vencida: `diasVencida !== null && diasVencida > 0` (una huérfana nunca es vencida: sin fecha
//   de compromiso asignada no hay "días de atraso" que contar — diasVencida debe venir null).
// - `listasRate` (fallback documentado, ver Titular.test.tsx y el gap de MetricExecutor): mientras
//   no exista el endpoint, Intermedia.tsx usa siempre `{ value: null, completeness: 'insuficiente' }`.
//
// Mecanismo del filtro "Ver huérfanas" (ver AlarmaHuerfanas.test.tsx): Intermedia mantiene el
// estado de "solo huérfanas" y le pasa a ListaRestricciones el array ya filtrado. Es un filtro de
// una sola dirección en este sub-paso (no hay botón para volver a "ver todas") — ver reporte.

import '@testing-library/jest-dom/vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { Intermedia } from './Intermedia'
import { construirTitular } from '../lib/titulares'
import type { Restriccion } from '../lib/api'

const { getRestriccionesMock, postGestionRestriccionMock } = vi.hoisted(() => ({
  getRestriccionesMock: vi.fn(),
  postGestionRestriccionMock: vi.fn(),
}))

vi.mock('../lib/api', async (importOriginal) => {
  const real = await importOriginal<typeof import('../lib/api')>()
  return {
    ...real,
    getRestricciones: getRestriccionesMock,
    postGestionRestriccion: postGestionRestriccionMock,
  }
})

function restriccion(overrides: Partial<Restriccion>): Restriccion {
  return {
    id: 1,
    restriccion: 'Materiales',
    semana: 3,
    actividadBloqueada: 'Vaciado placa piso 3',
    responsableAsignado: null,
    fechaCompromiso: null,
    estadoLiberacion: 'sin_gestionar',
    asignadoPor: null,
    asignadoEn: null,
    diasVencida: null,
    semanaInicioActividadBloqueada: 2,
    actividadesEncadenadas: 1,
    tocaRutaCritica: false,
    ...overrides,
  }
}

beforeEach(() => {
  getRestriccionesMock.mockReset()
  postGestionRestriccionMock.mockReset()
})

describe('Intermedia — ensambla huérfanas + titular + lista con UN solo fetch', () => {
  it('deriva huerfanasCount/vencidas de los mismos datos que trajo una vez, sin refetch por componente hijo', async () => {
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 1 }), // huérfana
      restriccion({ id: 2 }), // huérfana
      restriccion({
        id: 3,
        estadoLiberacion: 'en_gestion',
        responsableAsignado: 'Ana Ruiz',
        fechaCompromiso: '2026-08-01',
        diasVencida: 5,
      }), // vencida
      restriccion({
        id: 4,
        estadoLiberacion: 'liberada',
        responsableAsignado: 'Carlos Pérez',
        fechaCompromiso: '2026-09-10',
        diasVencida: null,
      }), // ni huérfana ni vencida
    ])

    render(<Intermedia />)

    await screen.findByTestId('lista-restricciones')

    // Posición 1 — alarma de huérfanas: 2 (ids 1 y 2), no 4 (el total) ni 1.
    const alarma = screen.getByTestId('alarma-huerfanas')
    expect(alarma.textContent).toMatch(/2/)
    expect(alarma.textContent).toMatch(/sin an[aá]lisis/i)

    // Posición 2 — titular: con huerfanasCount > 0, la condición 'huerfanas' domina sobre
    // vencidas/listasRate (mismo orden de prioridad de construirTitular(), no reimplementado
    // aquí). El texto exacto solo depende de huerfanasCount para esa plantilla.
    const tituloEsperado = construirTitular({
      huerfanasCount: 2,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: null, completeness: 'insuficiente' },
    }).texto
    expect(screen.getByText(tituloEsperado)).toBeInTheDocument()

    // Posición 3 — lista: las 4 filas visibles (sin filtro activado todavía).
    expect(screen.getAllByTestId(/^fila-restriccion-/)).toHaveLength(4)

    // El requisito duro de este sub-paso: UN solo fetch, no uno por componente hijo.
    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })

  it('sin huérfanas ni vencidas, usa el fallback documentado de listasRate (adherencia_insuficiente)', async () => {
    getRestriccionesMock.mockResolvedValue([
      restriccion({
        id: 10,
        estadoLiberacion: 'liberada',
        responsableAsignado: 'Carlos Pérez',
        fechaCompromiso: '2026-09-10',
        diasVencida: null,
      }),
      restriccion({
        id: 11,
        estadoLiberacion: 'en_gestion',
        responsableAsignado: 'Ana Ruiz',
        fechaCompromiso: '2026-09-15',
        diasVencida: null,
      }),
    ])

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    const alarma = screen.getByTestId('alarma-huerfanas')
    expect(within(alarma).queryByRole('button')).not.toBeInTheDocument()

    const tituloEsperado = construirTitular({
      huerfanasCount: 0,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: null, completeness: 'insuficiente' },
    }).texto
    expect(screen.getByText(tituloEsperado)).toBeInTheDocument()

    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })

  it('el botón "Ver huérfanas" filtra la lista sin volver a llamar a getRestricciones() (filtro en cliente, no refetch)', async () => {
    const user = userEvent.setup()
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 1 }), // huérfana
      restriccion({ id: 2 }), // huérfana
      restriccion({
        id: 3,
        estadoLiberacion: 'liberada',
        responsableAsignado: 'Carlos Pérez',
        fechaCompromiso: '2026-09-10',
        diasVencida: null,
      }),
    ])

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')
    expect(screen.getAllByTestId(/^fila-restriccion-/)).toHaveLength(3)

    await user.click(within(screen.getByTestId('alarma-huerfanas')).getByRole('button', { name: /huérfanas/i }))

    const filas = screen.getAllByTestId(/^fila-restriccion-/)
    expect(filas.map((f) => f.getAttribute('data-testid')).sort()).toEqual([
      'fila-restriccion-1',
      'fila-restriccion-2',
    ])

    // El filtro fue puramente del lado del cliente: la promesa de datos sigue habiéndose
    // resuelto una sola vez.
    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })

  it('si getRestricciones() rechaza, muestra un error visible en vez de una pantalla en blanco (sin fallo silencioso)', async () => {
    getRestriccionesMock.mockRejectedValue(new Error('NOT_FOUND'))

    render(<Intermedia />)

    expect(await screen.findByRole('alert')).toBeInTheDocument()
  })
})
