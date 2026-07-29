import { apiGet } from './api'
import type { Bootstrap, Contexto } from './types'

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
    // Si el blob inyectado no trae el id (versión vieja cacheada, etc.) cae a null en vez de romper.
    usuarioId: typeof r.usuarioId === 'number' ? r.usuarioId : null,
  }
}

async function fetchContexto(): Promise<Bootstrap> {
  // apiGet centraliza credentials, headers, envelope y mapeo de errores.
  const c = await apiGet<Contexto>('/plan-compras/api/contexto')
  return {
    projectId: c.projectId,
    proyectoNombre: c.proyectoNombre,
    rol: c.rol,
    csrfToken: c.csrfToken,
    usuario: c.usuario,
    usuarioId: c.usuarioId ?? null,
  }
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
