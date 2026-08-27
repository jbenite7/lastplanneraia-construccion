// @vitest-environment jsdom
//
// Tests de Linaje (ct-app, etapa piloto, Task 7 paso 4 — CT-6.3: «cada cifra del lienzo lleva un
// control discreto "de dónde sale esto"»). Componente GENÉRICO Y REUTILIZABLE: cualquier cifra del
// lienzo que tenga un `metricKey` de catálogo puede envolverse con `<Linaje metricKey="..." />`.
// Hoy la única cifra con `metric_key` real disponible es la que alimenta Titular
// (`pi_hard_restrictions_ready_rate`, ver Titular.test.tsx) — el semáforo (4 métricas de D58,
// Task 7 paso 3-bis) y el pareto NO tienen componente visual en el alcance de archivos de esta
// etapa (confirmado releyendo la sección "Files" de Task 7 en el plan), así que no se integra aquí
// con ningún otro componente. La integración con Titular.tsx queda para un sub-paso posterior si
// hace falta — decisión documentada en el reporte de este paso.
//
// ---------------------------------------------------------------------------------------------
// Investigación previa: la forma REAL del JSON, no inventada
// ---------------------------------------------------------------------------------------------
//
// `GET /api/bi/lineage?metric_key=X` (`BiControlTowerApiController::lineage()`,
// src/Controllers/Api/BiControlTowerApiController.php:296-312) responde
// `{"respuesta":"BIEN","lineage":{...}}` — un envelope DISTINTO al `{ok:true/false}` que usa el
// resto de `api.ts` (`postGestionRestriccion`, `getRestricciones`). `LineageService::getForMetric()`
// (src/Services/Bi/LineageService.php:59-79) arma ese objeto así, campo por campo del catálogo de
// `MetricDictionaryService`:
//
//   metric_key, metric_name, definition, formula, source_view (= execution_source),
//   source_tables (= implode(', ', source_relations)), grain,
//   filters (= implode(', ', filters)), version,
//   last_updated (?? '2026-07-10 00:00:00'), known_limitations
//
// Si el `metric_key` no existe en el catálogo, `getForMetric()` devuelve `[]` (PHP), que
// `json_encode` serializa como `"lineage":[]` — SIGUE siendo `"respuesta":"BIEN"` (200, sin
// error), solo que sin ninguno de los campos de arriba. Ese es el caso borde de este archivo: no
// es un fallo del servidor, es "esta cifra no tiene lineage declarado".
//
// GAP encontrado (documentado, no resuelto — no toco PHP): CT-6.3 pide "política de corte" como
// parte del contrato general de la cifra. `MetricDictionaryService::catalog()` SÍ tiene un campo
// `cutoff_policy` por métrica (ej. 'Fin de la semana seleccionada en semanas_activas.' para
// `pi_hard_restrictions_ready_rate`), pero `LineageService::getForMetric()` NO lo copia al array
// de salida — no está en la lista de claves de arriba, confirmado leyendo el método completo. Lo
// que SÍ viaja es `filters` (las condiciones SQL del filtro, ej. 'Titulo=0, Semanas_Inicio>=0,
// Semanas_Inicio<=6, Ejecutado<1'), que es un dato distinto de "política de corte" (una frase de
// negocio sobre CUÁNDO se corta el dato, no las condiciones de fila). Por eso este test NO fija un
// campo `cutoffPolicy` en `LineageInfo` ni exige que el componente muestre una "política de corte"
// general — sería inventar un dato que el backend no tiene. Lo que SÍ se cubre, y es literalmente
// lo que pide el encargo, es "el basis del cálculo concreto" (Task 7 paso 3, `MetricResult.basis`
// en `api.ts`), que trae `corte: string` — el corte de ESTE cálculo particular, no la política
// general. Corregir el vacío de `LineageService.php` es trabajo de otra tarea (backend), anotado
// como concern en el reporte.
//
// **Corrección, ronda 1 (revisión de spec+calidad):** el gap de arriba SÍ se cerró — rol B
// implementó `Linaje.tsx` en la misma tarea y de paso agregó `cutoff_policy` a
// `LineageService::getForMetric()` (PHP) más el campo `cutoffPolicy` en `LineageInfo`
// (`ct-app/src/lib/api.ts:176-189`) y su render en `Linaje.tsx:84` (`<p>Política de corte:
// {estado.info.cutoffPolicy}</p>`). Este archivo no tenía ninguna aserción que protegiera ese
// campo de una regresión — fixture y test "contrato completo" actualizados abajo para cubrirlo.
// El párrafo de arriba se deja intacto como registro de la investigación original, no se borra.
//
// ---------------------------------------------------------------------------------------------
// Contrato que este test fija para rol B (ct-app/src/lib/api.ts y ct-app/src/pages/Linaje.tsx)
// ---------------------------------------------------------------------------------------------
//
//   export interface LineageInfo {
//     metricKey: string
//     metricName: string
//     definition: string
//     formula: string
//     sourceView: string
//     sourceTables: string
//     grain: string
//     cutoffPolicy: string
//     filters: string
//     version: string
//     lastUpdated: string
//     knownLimitations: string
//   }
//   export function getLineage(metricKey: string): Promise<LineageInfo | null>
//
// `getLineage()` resuelve `null` (no rechaza) cuando el servidor responde BIEN con `lineage:[]` —
// "no encontrado" es un resultado válido, no una falla de red/servidor. Rechaza con `CtApiError`
// (mismo patrón que el resto de `api.ts`) cuando el servidor falla de verdad (403, 500, respuesta
// sin `respuesta:'BIEN'`, red caída). El componente distingue ambos casos: "sin información de
// trazabilidad" (neutro, sin `role="alert"`) vs. error real (`role="alert"`, mensaje visible).
//
//   interface LinajeProps {
//     metricKey: string
//     basis?: MetricResult['basis']  // el basis CONCRETO de esta cifra particular (Task 7 paso 3)
//   }
//
// Decisiones de diseño de este paso (documentadas para que rol B implemente contra esto, no las
// invente de nuevo):
//
// 1. **Fetch perezoso, en el primer open — no en el montaje.** El lienzo puede tener varias cifras
//    con control de linaje simultáneamente; pedir el contrato de las que nadie abre es red
//    desperdiciada. `getLineage(metricKey)` se llama la primera vez que el control se abre, y el
//    resultado se cachea en el propio componente: cerrar y volver a abrir NO repite el fetch (test
//    de abajo). Si el fetch falló, no se prueba retry automático al reabrir — decisión abierta para
//    rol B, anotada como concern.
// 2. **El control es un `<button>` real con `aria-expanded`.** Un `<button>` nativo ya es
//    alcanzable por Tab y responde a Enter/Space sin código adicional — no hace falta un
//    `tabindex` explícito (el encargo lo menciona como técnica general; para un elemento
//    nativamente enfocable no aplica). Este test prueba el CONTRATO de accesibilidad (foco por
//    Tab, alcance por teclado, `aria-expanded` reflejando el estado) sin exigir markup interno
//    específico (`aria-controls`, ids) que le quitaría libertad de implementación a rol B.
// 3. **Nunca depende de hover.** Se prueba explícitamente: `hover` solo, sin click ni tecla, no
//    debe abrir el panel ni disparar el fetch.
// 4. **El basis (cálculo concreto) se distingue visualmente del contrato general** — dos
//    `data-testid` separados (`linaje-contrato` / `linaje-basis`), nunca mezclados en el mismo
//    bloque de texto. Sin `basis`, no se renderiza esa sección (no se inventa un basis vacío).

import '@testing-library/jest-dom/vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { Linaje } from './Linaje'
import { CtApiError } from '../lib/api'
import type { MetricResult } from '../lib/api'

// Espejo exacto de LineageService::getForMetric() (ver investigación arriba), en camelCase —
// mismo criterio de traducción snake_case -> camelCase que ya usa `Restriccion` en api.ts. No se
// exporta desde este archivo: es la especificación que rol B debe exportar desde `lib/api.ts`
// junto con `getLineage()`.
interface LineageInfo {
  metricKey: string
  metricName: string
  definition: string
  formula: string
  sourceView: string
  sourceTables: string
  grain: string
  cutoffPolicy: string
  filters: string
  version: string
  lastUpdated: string
  knownLimitations: string
}

const { getLineageMock } = vi.hoisted(() => ({
  getLineageMock: vi.fn(),
}))

vi.mock('../lib/api', async (importOriginal) => {
  const real = await importOriginal<typeof import('../lib/api')>()
  return {
    ...real,
    getLineage: getLineageMock,
  }
})

// Fixture con datos REALES de `pi_hard_restrictions_ready_rate`
// (src/Services/Bi/MetricDictionaryService.php:99-127), no inventados: es la única cifra del
// lienzo con `metric_key` de catálogo disponible hoy (ver cabecera del archivo).
function lineageInfo(overrides: Partial<LineageInfo> = {}): LineageInfo {
  return {
    metricKey: 'pi_hard_restrictions_ready_rate',
    metricName: 'Porcentaje de actividades listas en ventana',
    definition: 'Proporción de actividades Lookahead con restricciones duras cumplidas.',
    formula: 'SUM(hard_restrictions_ready=1) / COUNT(*)',
    sourceView: 'bi_pg_semana',
    sourceTables: 'programa_consolidado, semanas_activas',
    grain: 'project_id + Semana',
    cutoffPolicy: 'Fin de la semana seleccionada en semanas_activas.',
    filters: 'Titulo=0, Semanas_Inicio>=0, Semanas_Inicio<=6, Ejecutado<1',
    version: '1.0',
    lastUpdated: '2026-07-10 00:00:00',
    knownLimitations: 'D_y_E, Materiales, MdeO y Equipos requieren 1.0; Predecesora 0.5.',
    ...overrides,
  }
}

function basisFixture(overrides: Partial<MetricResult['basis']> = {}): MetricResult['basis'] {
  return {
    obras_incluidas: 5,
    obras_esperadas: 5,
    corte: '2026-08-24',
    filas_usadas: 240,
    ...overrides,
  }
}

// Deferred manual: permite observar el estado "cargando" ANTES de resolver el fetch, controlando
// a mano cuándo se resuelve la promesa (mismo problema que un `mockResolvedValue` no puede probar,
// porque resuelve en el mismo microtask del render).
function crearDiferido<T>() {
  let resolve!: (value: T) => void
  let reject!: (reason?: unknown) => void
  const promise = new Promise<T>((res, rej) => {
    resolve = res
    reject = rej
  })
  return { promise, resolve, reject }
}

const METRIC_KEY = 'pi_hard_restrictions_ready_rate'

beforeEach(() => {
  getLineageMock.mockReset()
})

describe('Linaje — cerrado por defecto', () => {
  it('el control es un botón con aria-expanded="false" y el contrato no está en el DOM', () => {
    render(<Linaje metricKey={METRIC_KEY} />)

    const boton = screen.getByRole('button', { name: /de d[oó]nde sale esto/i })
    expect(boton).toHaveAttribute('aria-expanded', 'false')
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
    expect(getLineageMock).not.toHaveBeenCalled()
  })
})

describe('Linaje — abrir/cerrar, alcanzable por teclado, sin depender de hover', () => {
  it('Tab lleva el foco al control sin usar el mouse', async () => {
    const user = userEvent.setup()
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.tab()

    expect(screen.getByRole('button', { name: /de d[oó]nde sale esto/i })).toHaveFocus()
  })

  it('Enter abre el control con el foco puesto (sin click) y Enter otra vez lo cierra', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)
    const boton = screen.getByRole('button', { name: /de d[oó]nde sale esto/i })

    await user.tab()
    await user.keyboard('{Enter}')

    expect(boton).toHaveAttribute('aria-expanded', 'true')
    await screen.findByTestId('linaje-contrato')

    await user.keyboard('{Enter}')

    expect(boton).toHaveAttribute('aria-expanded', 'false')
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
  })

  it('un click también abre el panel (no solo teclado) y llama a getLineage(metricKey) exactamente una vez', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    await screen.findByTestId('linaje-contrato')
    expect(getLineageMock).toHaveBeenCalledTimes(1)
    expect(getLineageMock).toHaveBeenCalledWith(METRIC_KEY)
  })

  it('cerrar y volver a abrir NO repite el fetch — el resultado ya cargado se reutiliza', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)
    const boton = screen.getByRole('button', { name: /de d[oó]nde sale esto/i })

    await user.click(boton)
    await screen.findByTestId('linaje-contrato')
    await user.click(boton) // cerrar
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
    await user.click(boton) // volver a abrir

    await screen.findByTestId('linaje-contrato')
    expect(getLineageMock).toHaveBeenCalledTimes(1)
  })

  it('hover solo, sin click ni tecla, NO abre el panel ni dispara el fetch — nada valioso en hover', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.hover(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
    expect(getLineageMock).not.toHaveBeenCalled()
  })
})

describe('Linaje — estado de carga', () => {
  it('mientras getLineage() está pendiente, muestra un estado de carga accesible (role="status")', async () => {
    const user = userEvent.setup()
    const { promise, resolve } = crearDiferido<LineageInfo | null>()
    getLineageMock.mockReturnValue(promise)
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    expect(await screen.findByRole('status')).toHaveTextContent(/cargando/i)

    resolve(lineageInfo())
    await screen.findByTestId('linaje-contrato')
  })
})

describe('Linaje — el contrato general de la métrica (LineageInfo)', () => {
  it('muestra nombre, definición, fórmula, fuente, grano, versión y limitaciones conocidas', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    const contrato = await screen.findByTestId('linaje-contrato')
    // nombre
    expect(within(contrato).getByText(/porcentaje de actividades listas en ventana/i)).toBeInTheDocument()
    // definición en una frase
    expect(within(contrato).getByText(/proporci[oó]n de actividades lookahead con restricciones duras cumplidas/i)).toBeInTheDocument()
    // fórmula (tal como la da el backend — no hay traducción a "lenguaje de negocio" en el
    // contrato actual, ver GAP documentado arriba: el propio backend entrega la expresión técnica)
    expect(within(contrato).getByText(/sum\(hard_restrictions_ready=1\)\s*\/\s*count\(\*\)/i)).toBeInTheDocument()
    // fuente
    expect(contrato.textContent).toMatch(/bi_pg_semana/)
    expect(contrato.textContent).toMatch(/programa_consolidado/)
    // grano
    expect(contrato.textContent).toMatch(/project_id \+ semana/i)
    // política de corte (CT-6.3, cerrado en ronda 1 — ver nota de corrección en la cabecera)
    expect(contrato.textContent).toMatch(/fin de la semana seleccionada en semanas_activas/i)
    // versión del contrato
    expect(contrato.textContent).toMatch(/1\.0/)
    // limitaciones conocidas
    expect(within(contrato).getByText(/predecesora 0\.5/i)).toBeInTheDocument()
  })

  it('cada cifra pide SU propio metricKey — dos instancias con distinto metricKey llaman a getLineage con cada uno', async () => {
    const user = userEvent.setup()
    getLineageMock.mockImplementation((key: string) =>
      Promise.resolve(lineageInfo({ metricKey: key, metricName: `Métrica ${key}` })),
    )
    render(
      <>
        <Linaje metricKey="pi_hard_restrictions_ready_rate" />
        <Linaje metricKey="pi_restriction_pareto" />
      </>,
    )
    const botones = screen.getAllByRole('button', { name: /de d[oó]nde sale esto/i })
    expect(botones).toHaveLength(2)

    await user.click(botones[0])
    await user.click(botones[1])

    await screen.findByText(/m[eé]trica pi_hard_restrictions_ready_rate/i)
    await screen.findByText(/m[eé]trica pi_restriction_pareto/i)
    expect(getLineageMock).toHaveBeenNthCalledWith(1, 'pi_hard_restrictions_ready_rate')
    expect(getLineageMock).toHaveBeenNthCalledWith(2, 'pi_restriction_pareto')
  })
})

describe('Linaje — el basis del cálculo concreto (CT-6.3, MetricResult.basis de Task 7 paso 3)', () => {
  it('con basis, lo muestra en una sección distinta y separada del contrato general', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} basis={basisFixture()} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    const contrato = await screen.findByTestId('linaje-contrato')
    const basis = screen.getByTestId('linaje-basis')
    expect(basis).not.toBe(contrato)
    expect(basis.textContent).toMatch(/5/) // obras_incluidas y obras_esperadas
    expect(basis.textContent).toMatch(/2026-08-24/) // corte de ESTE cálculo
    expect(basis.textContent).toMatch(/240/) // filas_usadas
  })

  it('sin basis, no se renderiza la sección (nunca se inventa un basis vacío)', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(lineageInfo())
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    await screen.findByTestId('linaje-contrato')
    expect(screen.queryByTestId('linaje-basis')).not.toBeInTheDocument()
  })
})

describe('Linaje — manejo de error, nunca catch mudo ni panel vacío', () => {
  it('CtApiError del fetch: muestra el mensaje del servidor en role="alert", sin pintar el contrato', async () => {
    const user = userEvent.setup()
    getLineageMock.mockRejectedValue(new CtApiError('FORBIDDEN', 'Sin permiso para ver el linaje de esta métrica.', 403))
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/sin permiso para ver el linaje de esta m[eé]trica/i)
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
  })

  it('cualquier otra falla (red caída, 500) también se muestra — nunca desaparece en un catch vacío', async () => {
    const user = userEvent.setup()
    getLineageMock.mockRejectedValue(new Error('network error'))
    render(<Linaje metricKey={METRIC_KEY} />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    const alerta = await screen.findByRole('alert')
    expect(alerta.textContent?.trim().length).toBeGreaterThan(0)
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
  })
})

describe('Linaje — caso borde: metricKey sin lineage declarado en el catálogo', () => {
  it('getLineage() resuelve null (BIEN + lineage:[] del backend) => estado neutro "sin información de trazabilidad", nunca un crash ni un role="alert"', async () => {
    const user = userEvent.setup()
    getLineageMock.mockResolvedValue(null)
    render(<Linaje metricKey="metrica_sin_catalogar" />)

    await user.click(screen.getByRole('button', { name: /de d[oó]nde sale esto/i }))

    const boton = screen.getByRole('button', { name: /de d[oó]nde sale esto/i })
    expect(boton).toHaveAttribute('aria-expanded', 'true')
    expect(await screen.findByText(/sin informaci[oó]n de trazabilidad/i)).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
    expect(screen.queryByTestId('linaje-contrato')).not.toBeInTheDocument()
  })
})
