import { afterEach, describe, expect, it, vi } from 'vitest'
import { CtApiError, postGestionRestriccion } from './api'
import type { GestionEstado, MetricResult } from './api'

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
