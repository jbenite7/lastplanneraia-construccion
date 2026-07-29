import { afterEach, describe, expect, it, vi } from 'vitest'
import { getBootstrap, __resetBootstrapForTests } from './bootstrap'

afterEach(() => {
  __resetBootstrapForTests()
  delete (globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__
  vi.unstubAllGlobals()
})

const contextoOk = {
  ok: true,
  data: { projectId: 3, proyectoNombre: 'DAPORTO', usuario: 'pipe', rol: 'D', csrfToken: 'tok-2' },
}

describe('getBootstrap', () => {
  it('usa window.__PDC_BOOTSTRAP__ cuando el shell PHP lo inyecta', async () => {
    ;(globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__ = {
      projectId: 7, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-1', usuario: 'pipe',
    }
    const b = await getBootstrap()
    expect(b.projectId).toBe(7)
    expect(b.csrfToken).toBe('tok-1')
  })

  it('en dev (sin inyección) obtiene el contexto por fetch con X-AIA-Expect-Json y cookies same-origin', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => contextoOk })
    vi.stubGlobal('fetch', fetchMock)
    const b = await getBootstrap()
    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe('/plan-compras/api/contexto')
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
    expect(init.credentials).toBe('same-origin')
    expect(b).toEqual({ projectId: 3, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-2', usuario: 'pipe' })
  })

  it('cachea el resultado (no repite el fetch)', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => contextoOk })
    vi.stubGlobal('fetch', fetchMock)
    await getBootstrap()
    await getBootstrap()
    expect(fetchMock).toHaveBeenCalledTimes(1)
  })

  it('dedup bajo concurrencia: llamadas simultáneas comparten un único fetch en vuelo', async () => {
    let resolveJson: (v: unknown) => void = () => {}
    const jsonPending = new Promise((res) => { resolveJson = res })
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: () => jsonPending })
    vi.stubGlobal('fetch', fetchMock)
    const [a, b] = [getBootstrap(), getBootstrap()]
    resolveJson(contextoOk)
    const [ba, bb] = await Promise.all([a, b])
    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(ba).toBe(bb)
  })

  it('lanza error claro si el contexto responde envelope de error', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true, status: 200,
      json: async () => ({ ok: false, error: { code: 'NO_PROJECT', message: 'Selecciona un proyecto' } }),
    }))
    await expect(getBootstrap()).rejects.toThrow('Selecciona un proyecto')
  })
})
