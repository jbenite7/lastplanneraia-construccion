import { afterEach, describe, expect, it, vi } from 'vitest'
import { apiGet, apiPost, PdcApiError } from './api'
import { __resetBootstrapForTests } from './bootstrap'

function stubBootstrap() {
  ;(globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__ = {
    projectId: 7, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-csrf', usuario: 'pipe',
  }
}

afterEach(() => {
  __resetBootstrapForTests()
  delete (globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__
  vi.unstubAllGlobals()
})

describe('apiGet', () => {
  it('desenvuelve data del envelope y manda X-AIA-Expect-Json', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({ ok: true, data: { x: 1 } }),
    })
    vi.stubGlobal('fetch', fetchMock)
    await expect(apiGet<{ x: number }>('/plan-compras/api/algo')).resolves.toEqual({ x: 1 })
    const [, init] = fetchMock.mock.calls[0]
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
  })

  it('lanza PdcApiError con code del envelope de error', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true, status: 403,
      json: async () => ({ ok: false, error: { code: 'FORBIDDEN', message: 'Sin permiso' } }),
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect(err).toBeInstanceOf(PdcApiError)
    expect((err as PdcApiError).code).toBe('FORBIDDEN')
  })

  it('mapea HTTP 401 a SESSION_EXPIRED', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false, status: 401, json: async () => ({ success: false, sessionExpired: true }),
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect((err as PdcApiError).code).toBe('SESSION_EXPIRED')
  })

  it('mapea payload sin envelope a BAD_RESPONSE', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false, status: 500, json: async () => { throw new Error('not json') },
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect((err as PdcApiError).code).toBe('BAD_RESPONSE')
  })
})

describe('apiPost', () => {
  it('envía JSON con X-CSRF-Token del bootstrap', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({ ok: true, data: null }),
    })
    vi.stubGlobal('fetch', fetchMock)
    await apiPost('/plan-compras/api/algo', { a: 1 })
    const [, init] = fetchMock.mock.calls[0]
    expect(init.method).toBe('POST')
    expect(init.headers['X-CSRF-Token']).toBe('tok-csrf')
    expect(init.headers['Content-Type']).toBe('application/json')
    expect(init.body).toBe(JSON.stringify({ a: 1 }))
  })
})
