// @vitest-environment jsdom
//
// Tests de Intermedia (ct-app, etapa piloto) — ensambla el lienzo completo de la hoja de
// Intermedia (CT-8.3): alarma de huérfanas (posición 1) + titular narrativo (posición 2) + lista
// de restricciones (posición 3, Task 7 paso 3b) + semáforo (4) + pareto (5, Task 8 — construidos
// tras el hallazgo de la entrada 20 de la Bitácora del plan, montados en Intermedia.tsx al cerrar
// el craft visual). Semaforo/Pareto traen su propio fetch (getMetric() / getParetoRestricciones())
// independiente del de este archivo; se mockean con un default neutro para que los tests de
// arriba (huérfanas/vencidas/listasRate) no dependan de su resultado — ningún test de este
// archivo hace aserciones sobre el contenido del semáforo o el pareto, eso lo cubren
// Semaforo.test.tsx y Pareto.test.tsx por separado.
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
// - `listasRate`: HASTA Task 7 paso 5, Intermedia.tsx usaba siempre el fallback fijo
//   `{ value: null, completeness: 'insuficiente' }` porque ningún controller invocaba
//   `MetricExecutor::execute()`. Ese gap ya se cerró (`GET /api/bi/control-tower/metricas/{metricKey}`,
//   `tests/test_bi_metric_endpoint.php`) — ver el describe "Task 7 paso 5" más abajo, que reemplaza
//   ese comportamiento: Intermedia ahora llama a `getMetric('pi_hard_restrictions_ready_rate')` y
//   pasa el `MetricResult` REAL como `listasRate`. Los tests de arriba (huérfanas/vencidas) siguen
//   sin depender de `listasRate` a propósito: `construirTitular()` la evalúa solo cuando
//   huerfanasCount y vencidasCount son ambos 0 (ver titulares.ts, orden de prioridad) — por eso usan
//   el default `getMetricMock` de `beforeEach()` sin declararlo cada vez.
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
import type { MetricResult, Restriccion } from '../lib/api'

// `getMetric` está COMPARTIDO entre Intermedia (1 llamada, adherencia D59) y Semaforo.tsx (4
// llamadas, una por franja — Task 8, montado en el lienzo tras la entrada 20 de la Bitácora).
// `getMetricMock` es el mock crudo que ve cada llamada real (para las aserciones de
// `toHaveBeenCalledTimes`/`toHaveBeenCalledWith` sobre la clave de adherencia, que filtran por
// argumento — nunca cuentan las 4 llamadas del semáforo). `getMetricAdherenciaMock` es lo que los
// tests de este archivo configuran: SOLO afecta la respuesta para
// 'pi_hard_restrictions_ready_rate'; cualquier otra clave (las 4 del semáforo) recibe un neutro
// fijo en `beforeEach`, así que ningún test de Intermedia puede romper Semaforo por accidente ni
// viceversa.
const {
  getRestriccionesMock,
  postGestionRestriccionMock,
  getMetricMock,
  getMetricAdherenciaMock,
  getParetoRestriccionesMock,
} = vi.hoisted(() => ({
  getRestriccionesMock: vi.fn(),
  postGestionRestriccionMock: vi.fn(),
  getMetricMock: vi.fn(),
  getMetricAdherenciaMock: vi.fn(),
  getParetoRestriccionesMock: vi.fn(),
}))

vi.mock('../lib/api', async (importOriginal) => {
  const real = await importOriginal<typeof import('../lib/api')>()
  return {
    ...real,
    getRestricciones: getRestriccionesMock,
    postGestionRestriccion: postGestionRestriccionMock,
    getMetric: getMetricMock,
    getParetoRestricciones: getParetoRestriccionesMock,
  }
})

// Fixture por defecto de getMetric('pi_hard_restrictions_ready_rate') — Task 7 paso 5. Los tests
// que no versan sobre listasRate (huérfanas/vencidas dominan la prioridad de construirTitular(),
// ver titulares.ts) usan este default sin tener que declararlo cada vez; los que sí prueban la
// integración real lo sobrescriben explícito.
function metricResult(overrides: Partial<MetricResult> = {}): MetricResult {
  return {
    value: 0.5833333333333334,
    basis: { obras_incluidas: 1, obras_esperadas: 1, corte: '2026-08-26', filas_usadas: 12 },
    completeness: 'completa',
    ...overrides,
    missing: overrides.missing ?? [],
  }
}

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

const METRIC_KEY_ADHERENCIA = 'pi_hard_restrictions_ready_rate'

beforeEach(() => {
  getRestriccionesMock.mockReset()
  postGestionRestriccionMock.mockReset()
  getMetricMock.mockReset()
  getMetricAdherenciaMock.mockReset()
  getMetricAdherenciaMock.mockResolvedValue(metricResult())
  // getMetricMock es lo que Intermedia.tsx y Semaforo.tsx llaman de verdad. Para la clave de
  // adherencia delega en getMetricAdherenciaMock (lo que los tests configuran); para cualquier
  // otra clave (las 4 franjas del semáforo) siempre resuelve neutro — nunca depende de lo que un
  // test de Intermedia haya configurado para adherencia, así que un `mockRejectedValue` de
  // adherencia no puede tumbar las 4 franjas del semáforo con un role="alert" inesperado.
  getMetricMock.mockImplementation((metricKey: string) =>
    metricKey === METRIC_KEY_ADHERENCIA
      ? getMetricAdherenciaMock(metricKey)
      : Promise.resolve(metricResult({ value: 0.5, completeness: 'completa' })),
  )
  getParetoRestriccionesMock.mockReset()
  // Default neutro: ningún test de este archivo hace aserciones sobre el pareto (eso lo cubre
  // Pareto.test.tsx) — una distribución vacía evita que Pareto dispare su role="alert" de error
  // de red, que rompería los asserts de "no hay ningún alert" de los describes de arriba.
  getParetoRestriccionesMock.mockResolvedValue({ distribucion: [], basis: { filas_usadas: 0, corte: '' } })
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

  it('sin huérfanas ni vencidas, y con listasRate completeness=insuficiente (getMetric real), muestra adherencia_insuficiente', async () => {
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
    // A diferencia de antes de Task 7 paso 5, esto YA NO es el default implícito de Intermedia.tsx
    // — es un resultado real posible de getMetric() (la métrica sin filas que cumplan sus filtros
    // para el proyecto/semana de sesión), estubeado explícito aquí.
    getMetricAdherenciaMock.mockResolvedValue(
      metricResult({ value: null, completeness: 'insuficiente', missing: ['sin_filas_que_cumplan_los_filtros'] }),
    )

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

// Fix ronda 1 (hallazgo Important de la revisión de spec+calidad): gestionar una huérfana desde
// dentro de Intermedia debe bajar el contador de AlarmaHuerfanas y actualizar el titular en el
// mismo render, sin remount ni un segundo getRestricciones(). Antes de este fix, N nunca bajaba —
// el useMemo de huérfanas de Intermedia nunca se enteraba de un guardado hecho por ListaRestricciones.
describe('Intermedia — fix ronda 1: gestionar una huérfana actualiza el contador y el titular', () => {
  it('tras guardar, la alarma baja de 2 a 1, el titular refleja huerfanasCount=1 y la fila sale del filtro "solo huérfanas"', async () => {
    const user = userEvent.setup()
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 1 }), // huérfana
      restriccion({ id: 2 }), // huérfana
    ])
    postGestionRestriccionMock.mockResolvedValue({
      ok: true,
      restriccion: { id: 1, responsable: 'Pipe Ramos', fechaCompromiso: '2026-09-01', estado: 'en_gestion' },
    })

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')
    expect(screen.getByTestId('alarma-huerfanas').textContent).toMatch(/2/)

    // Entra al filtro "solo huérfanas" y gestiona la primera fila.
    await user.click(within(screen.getByTestId('alarma-huerfanas')).getByRole('button', { name: /huérfanas/i }))
    const fila = screen.getByTestId('fila-restriccion-1')
    await user.click(within(fila).getByRole('button', { name: /gestionar/i }))
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    await user.type(screen.getByLabelText(/fecha.*compromiso/i), '2026-09-01')
    await user.selectOptions(screen.getByLabelText(/estado/i), 'en_gestion')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    // El titular es la señal más fuerte de que Intermedia.restricciones se actualizó: solo cambia
    // si huerfanasCount pasó de 2 a 1 en el resumen que Intermedia reconstruye.
    const tituloEsperado = construirTitular({
      huerfanasCount: 1,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: null, completeness: 'insuficiente' },
    }).texto
    expect(await screen.findByText(tituloEsperado)).toBeInTheDocument()

    // El contador de la alarma también bajó.
    expect(screen.getByTestId('alarma-huerfanas').textContent).toMatch(/1/)

    // La fila ya gestionada dejó de cumplir el criterio de huérfana: sale sola del filtro "solo
    // huérfanas" activo, sin necesidad de un botón "ver todas" ni de recargar.
    expect(screen.queryByTestId('fila-restriccion-1')).not.toBeInTheDocument()
    expect(screen.getByTestId('fila-restriccion-2')).toBeInTheDocument()

    // Un solo fetch inicial — el fix no agregó un refetch para refrescar el contador.
    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })
})

// Task 7 paso 5 (rol A, test writer): D59 ("las dos lecturas del cero, separadas y rotuladas").
// Ruling del controlador (progress.md, "antes de Task 7 paso 5"): la cifra "53,2% vs 57,5%" que
// cita la spec para una "señal predictiva" es un hallazgo de investigación ESTÁTICO que la propia
// spec ya desestima como "evidencia débil... no alcanza para sostenerlo en comité" — el piloto NO
// fabrica esa señal. D59 se cumple mostrando SOLO la adherencia (`pi_hard_restrictions_ready_rate`)
// como cifra dura, correctamente rotulada. El gap que bloqueaba eso: Intermedia.tsx usaba siempre
// el fallback fijo `{value:null, completeness:'insuficiente'}` porque no existía un endpoint que
// ejecutara la métrica — ahora existe (`GET /api/bi/control-tower/metricas/{metricKey}`,
// `tests/test_bi_metric_endpoint.php`) y `getMetric()` (nueva, api.ts) lo consume.
describe('Intermedia — Task 7 paso 5 (D59): adherencia real vía getMetric(), nunca una predicción', () => {
  it('llama a getMetric("pi_hard_restrictions_ready_rate") y pasa el resultado REAL como listasRate a construirTitular()', async () => {
    getRestriccionesMock.mockResolvedValue([
      restriccion({
        id: 20,
        estadoLiberacion: 'liberada',
        responsableAsignado: 'Carlos Pérez',
        fechaCompromiso: '2026-09-10',
        diasVencida: null,
      }),
    ])
    // completeness=completa, value=0.72 -> sinAnalisisPct = round((1-0.72)*100) = 28, <= 30 ->
    // condición 'sano' (ver titulares.ts, UMBRAL_SIN_ANALISIS_PCT). Valor elegido deliberadamente
    // distinto del default de metricResult() (0.5833...) para que este test falle si Intermedia
    // ignora el MetricResult real y sigue usando cualquier valor fijo/hardcodeado.
    getMetricAdherenciaMock.mockResolvedValue(metricResult({ value: 0.72, completeness: 'completa' }))

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    expect(getMetricAdherenciaMock).toHaveBeenCalledTimes(1)
    expect(getMetricAdherenciaMock).toHaveBeenCalledWith('pi_hard_restrictions_ready_rate')

    const tituloEsperado = construirTitular({
      huerfanasCount: 0,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: 0.72, completeness: 'completa' },
    }).texto
    expect(screen.getByText(tituloEsperado)).toBeInTheDocument()
    // La cifra dura de D59: "28%" (sin análisis), no un porcentaje de "va a fallar".
    expect(screen.getByText(/28%/)).toBeInTheDocument()
  })

  it('con value bajo (mucho sin análisis), la condición es adherencia_baja — sigue siendo la cifra dura, no una predicción', async () => {
    getRestriccionesMock.mockResolvedValue([])
    // sinAnalisisPct = round((1-0.5)*100) = 50 > 30 -> 'adherencia_baja'.
    getMetricAdherenciaMock.mockResolvedValue(metricResult({ value: 0.5, completeness: 'completa' }))

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    const tituloEsperado = construirTitular({
      huerfanasCount: 0,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: 0.5, completeness: 'completa' },
    }).texto
    expect(screen.getByText(tituloEsperado)).toBeInTheDocument()
    expect(screen.getByText(/50%/)).toBeInTheDocument()
  })

  it('si getMetric() rechaza, listasRate cae a {value:null, completeness:"insuficiente"} sin romper el resto de la hoja (huérfanas y lista siguen visibles)', async () => {
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 1 }), // huérfana — para probar que el resto del lienzo sigue funcionando
    ])
    getMetricAdherenciaMock.mockRejectedValue(new Error('NOT_FOUND'))

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    // El resto de la hoja no se cae por un fallo aislado de getMetric(): huérfanas y lista siguen.
    expect(screen.getByTestId('alarma-huerfanas').textContent).toMatch(/1/)
    expect(screen.getByTestId('fila-restriccion-1')).toBeInTheDocument()

    // huerfanasCount=1 domina la prioridad de construirTitular() sobre listasRate — el punto de
    // este caso no es el texto del titular (ver el primer describe para eso), sino que un getMetric()
    // que rechaza no deja la hoja en blanco ni sin gestionar (sin error boundary roto).
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('sin huérfanas/vencidas y con getMetric() rechazada, el titular cae a adherencia_insuficiente (honesto, nunca un valor inventado)', async () => {
    getRestriccionesMock.mockResolvedValue([])
    getMetricAdherenciaMock.mockRejectedValue(new Error('NOT_FOUND'))

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    const tituloEsperado = construirTitular({
      huerfanasCount: 0,
      vencidasCount: 0,
      vencidasMaxDias: 0,
      listasRate: { value: null, completeness: 'insuficiente' },
    }).texto
    expect(screen.getByText(tituloEsperado)).toBeInTheDocument()
  })

  it('D59: la hoja nunca muestra una señal predictiva ni un porcentaje de "esto va a fallar" — solo la adherencia, rotulada', async () => {
    getRestriccionesMock.mockResolvedValue([])
    getMetricAdherenciaMock.mockResolvedValue(metricResult({ value: 0.5, completeness: 'completa' }))

    render(<Intermedia />)
    await screen.findByTestId('lista-restricciones')

    // Ruling del controlador: la cifra "53,2% vs 57,5%" de la spec es evidencia YA DESESTIMADA, no
    // una señal a mostrar. Ningún texto de la hoja debe sonar a predicción/estimación de fallo —
    // guarda de regresión: si alguien agrega un componente de "señal predictiva" sin actualizar
    // este test, esta aserción lo agarra.
    expect(
      screen.queryByText(/va a fallar|predicci[oó]n|riesgo estimado|probabilidad de (incumplir|fallar)|estimaci[oó]n de riesgo/i),
    ).not.toBeInTheDocument()
  })
})
