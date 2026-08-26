import { afterEach, describe, expect, it, vi } from 'vitest'
import { CtApiError, getRestricciones, postGestionRestriccion } from './api'
import type { GestionEstado, MetricResult, Restriccion } from './api'

// CSRF: mismo patrón que pdc-app/src/lib/api.ts + bootstrap.ts — un blob inyectado en el global
// (allá `__PDC_BOOTSTRAP__`, aquí `__CT_BOOTSTRAP__`) trae `csrfToken` y el cliente lo manda en
// `X-CSRF-Token`. A diferencia de pdc-app, ct-app no tiene (todavía) un GET /contexto propio del
// que hacer fallback: Task 5 no define ese endpoint, así que el test solo cubre la vía inyectada.
function stubBootstrap(csrfToken = 'tok-csrf-ct') {
  ;(globalThis as Record<string, unknown>).__CT_BOOTSTRAP__ = { csrfToken }
}

afterEach(() => {
  delete (globalThis as Record<string, unknown>).__CT_BOOTSTRAP__
  vi.unstubAllGlobals()
})

// Espejo de MetricResult (PHP: src/Services/Bi/MetricExecutor.php, Tasks 2/5). No hay lógica que
// ejercitar todavía —Task 5 no construyó el endpoint que la produce— pero fijar el tipo aquí es lo
// que impide que api.ts (Task 4, rol B) se aparte del contrato ya acordado entre las tareas 2/3/5/7.
describe('MetricResult', () => {
  it('acepta value null con basis completo, completeness y missing (métrica insuficiente)', () => {
    const ejemplo: MetricResult = {
      value: null,
      basis: { obras_incluidas: 0, obras_esperadas: 5, corte: '2026-08-24', filas_usadas: 0 },
      completeness: 'insuficiente',
      missing: ['obra sin datos de campo'],
    }
    expect(ejemplo.completeness).toBe('insuficiente')
    expect(ejemplo.value).toBeNull()
  })

  it('acepta value numérico con completeness completa y missing vacío', () => {
    const ejemplo: MetricResult = {
      value: 0.87,
      basis: { obras_incluidas: 5, obras_esperadas: 5, corte: '2026-08-24', filas_usadas: 240 },
      completeness: 'completa',
      missing: [],
    }
    expect(ejemplo.value).toBe(0.87)
    expect(ejemplo.missing).toHaveLength(0)
  })
})

describe('postGestionRestriccion', () => {
  const payload = {
    responsable: 'Pipe Ramos',
    fechaCompromiso: '2026-09-01',
    estado: 'en_gestion' as GestionEstado,
  }

  it('manda el CSRF del bootstrap en X-CSRF-Token, credenciales same-origin y el body en JSON', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ ok: true, restriccion: { id: 42, ...payload } }),
    })
    vi.stubGlobal('fetch', fetchMock)

    await postGestionRestriccion(42, payload)

    expect(fetchMock).toHaveBeenCalledTimes(1)
    const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit & { headers: Record<string, string> }]
    expect(url).toBe('/api/bi/control-tower/restricciones/42/gestion')
    expect(init.method).toBe('POST')
    expect(init.credentials).toBe('same-origin')
    expect(init.headers['X-CSRF-Token']).toBe('tok-csrf-ct')
    expect(init.headers['Content-Type']).toBe('application/json')
    // X-AIA-Expect-Json: convención del repo (SessionMiddleware::expectsJsonResponse()) para que
    // una sesión vencida responda JSON en vez de redirigir a /login — la sigue pdc-app y la debe
    // seguir ct-app, no es un invento de esta tarea.
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
    expect(JSON.parse(init.body as string)).toEqual(payload)
  })

  it('resuelve con {ok:true, restriccion} cuando el servidor confirma la gestión', async () => {
    stubBootstrap()
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ ok: true, restriccion: { id: 42, ...payload } }),
      }),
    )

    const res = await postGestionRestriccion(42, payload)

    expect(res.ok).toBe(true)
    expect(res.restriccion).toEqual({ id: 42, ...payload })
  })

  it('un 403 se propaga como CtApiError tipado — no como catch mudo ni undefined', async () => {
    stubBootstrap()
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 403,
        json: async () => ({
          ok: false,
          error: { code: 'FORBIDDEN', message: 'Sin permiso para gestionar esta restricción.' },
        }),
      }),
    )

    const err = await postGestionRestriccion(42, payload).catch((e: unknown) => e)

    expect(err).toBeInstanceOf(CtApiError)
    expect((err as CtApiError).code).toBe('FORBIDDEN')
    expect((err as CtApiError).message.length).toBeGreaterThan(0)
  })

  it('la promesa rechaza en vez de resolver silenciosamente cuando el servidor niega el permiso', async () => {
    stubBootstrap()
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 403,
        json: async () => ({ ok: false, error: { code: 'FORBIDDEN', message: 'Sin permiso' } }),
      }),
    )

    await expect(postGestionRestriccion(42, payload)).rejects.toBeInstanceOf(CtApiError)
  })
})

// Espejo del contrato fijado por Task 7 paso 3a
// (.superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-paso3a-report.md, sección "Forma de
// cada fila"): 13 campos camelCase que devuelve GET /api/bi/control-tower/restricciones. Este tipo
// es SOLO especificación de este test — quien implementa `getRestricciones()` en api.ts (rol B)
// exporta el tipo real; este archivo no lo define, lo consume vía `import type`.
const RESTRICCION_EJEMPLO: Restriccion = {
  id: 14,
  restriccion: 'Modelo',
  semana: 3,
  actividadBloqueada: 'Vaciado placa piso 3',
  responsableAsignado: null,
  fechaCompromiso: null,
  estadoLiberacion: 'sin_gestionar',
  asignadoPor: null,
  asignadoEn: null,
  diasVencida: null,
  // Puede ser negativo (medido en dev, ver el reporte del paso 3a) — no acotar a 0-6 aquí.
  semanaInicioActividadBloqueada: -3,
  actividadesEncadenadas: 12,
  tocaRutaCritica: true,
}

describe('getRestricciones', () => {
  it('hace GET a /api/bi/control-tower/restricciones con credenciales same-origin y X-AIA-Expect-Json, SIN CSRF ni bootstrap', async () => {
    // Decisión de diseño (documentada en el reporte de esta ronda): a diferencia de
    // postGestionRestriccion (POST, necesita CSRF), un GET no muta nada y el backend de Task 7
    // paso 3a documenta explícito "Sin CSRF: ningún GET lo valida". Mismo precedente que
    // pdc-app/src/lib/api.ts: `apiGet()` no llama a `getBootstrap()` ni manda `X-CSRF-Token`,
    // solo `apiPost()`/`apiUpload()` lo hacen. Este test fija que `getRestricciones()` funciona
    // aunque `__CT_BOOTSTRAP__` no esté inyectado — a propósito, NO se llama stubBootstrap() aquí.
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ ok: true, restricciones: [RESTRICCION_EJEMPLO] }),
    })
    vi.stubGlobal('fetch', fetchMock)

    await getRestricciones()

    expect(fetchMock).toHaveBeenCalledTimes(1)
    const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit & { headers: Record<string, string> }]
    expect(url).toBe('/api/bi/control-tower/restricciones')
    expect(init.method).toBe('GET')
    expect(init.credentials).toBe('same-origin')
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
    expect(init.headers['X-CSRF-Token']).toBeUndefined()
    expect(init.body).toBeUndefined()
  })

  it('resuelve con el array de restricciones tal como llega, con los 13 campos y sus tipos nulables', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ ok: true, restricciones: [RESTRICCION_EJEMPLO] }),
      }),
    )

    const restricciones = await getRestricciones()

    expect(restricciones).toEqual([RESTRICCION_EJEMPLO])
    // `tocaRutaCritica` debe llegar como boolean real (contrato del backend: nunca 0/1) — el
    // cliente no debe convertir/perder el tipo en el camino.
    expect(typeof restricciones[0].tocaRutaCritica).toBe('boolean')
    // semanaInicioActividadBloqueada puede ser negativo: no se trunca ni se valida aquí.
    expect(restricciones[0].semanaInicioActividadBloqueada).toBe(-3)
  })

  it('resuelve con [] cuando el proyecto no tiene restricciones (envelope con array vacío, no null)', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ ok: true, restricciones: [] }),
      }),
    )

    await expect(getRestricciones()).resolves.toEqual([])
  })

  it('un 404 sin PERM_INTERNAL_BI_PREVIEW (rol sin capacidad) se propaga como CtApiError NOT_FOUND, nunca [] silencioso', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 404,
        json: async () => ({ ok: false, error: { code: 'NOT_FOUND', message: 'Esta página no existe.' } }),
      }),
    )

    const err = await getRestricciones().catch((e: unknown) => e)

    expect(err).toBeInstanceOf(CtApiError)
    expect((err as CtApiError).code).toBe('NOT_FOUND')
    expect((err as CtApiError).status).toBe(404)
  })

  it('una respuesta sin `ok` booleano (HTML de error, JSON roto) lanza CtApiError BAD_RESPONSE en vez de romper con TypeError', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 500,
        json: async () => {
          throw new SyntaxError('Unexpected token < in JSON')
        },
      }),
    )

    const err = await getRestricciones().catch((e: unknown) => e)

    expect(err).toBeInstanceOf(CtApiError)
    expect((err as CtApiError).code).toBe('BAD_RESPONSE')
  })

  it('la promesa rechaza en vez de resolver en silencio cuando el servidor niega el acceso', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 404,
        json: async () => ({ ok: false, error: { code: 'NOT_FOUND', message: 'Esta página no existe.' } }),
      }),
    )

    await expect(getRestricciones()).rejects.toBeInstanceOf(CtApiError)
  })
})
