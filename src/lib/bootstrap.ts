import type { ApiResult, Bootstrap, Contexto } from './types'

let cached: Bootstrap | null = null
let pending: Promise<Bootstrap> | null = null

function fromInjected(raw: unknown): Bootstrap | null {
  if (!raw || typeof raw !== 'object') return null
  const r = raw as Record<string, unknown>
  if (typeof r.projectId !== 'number' || typeof r.csrfToken !== 'string') return null
  return {
    projectId: r.projectId,
    proyectoNombre: String(r.proyectoNombre ?? ''),
    rol: String(r.rol ?? ''),
    csrfToken: r.csrfToken,
    usuario: String(r.usuario ?? ''),
  }
}

async function fetchContexto(): Promise<Bootstrap> {
  const res = await fetch('/plan-compras/api/contexto', {
    credentials: 'same-origin',
    headers: { 'X-AIA-Expect-Json': '1' },
  })
  const body = (await res.json()) as ApiResult<Contexto>
  if (!body.ok) throw new Error(body.error.message)
  const c = body.data
  return { projectId: c.projectId, proyectoNombre: c.proyectoNombre, rol: c.rol, csrfToken: c.csrfToken, usuario: c.usuario }
}

export async function getBootstrap(): Promise<Bootstrap> {
  if (cached) return cached
  const injected = fromInjected((globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__)
  if (injected) {
    cached = injected
    return cached
  }
  pending ??= fetchContexto()
    .then((b) => {
      cached = b
      return b
    })
    .finally(() => {
      pending = null
    })
  return pending
}

export function __resetBootstrapForTests(): void {
  cached = null
  pending = null
}
