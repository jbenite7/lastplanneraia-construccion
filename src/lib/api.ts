import { getBootstrap } from './bootstrap'
import type { ApiResult } from './types'

export class PdcApiError extends Error {
  code: string
  constructor(code: string, message: string) {
    super(message)
    this.code = code
  }
}

async function request<T>(path: string, init: RequestInit & { headers?: Record<string, string> }): Promise<T> {
  const res = await fetch(path, {
    credentials: 'same-origin',
    ...init,
    headers: { 'X-AIA-Expect-Json': '1', ...(init.headers ?? {}) },
  })
  if (res.status === 401 || res.status === 419) {
    throw new PdcApiError('SESSION_EXPIRED', 'La sesión expiró. Vuelve a iniciar sesión en Last Planner.')
  }
  const body = (await res.json().catch(() => null)) as ApiResult<T> | null
  if (!body || typeof (body as { ok?: unknown }).ok !== 'boolean') {
    throw new PdcApiError('BAD_RESPONSE', `Respuesta inválida del servidor (HTTP ${res.status}).`)
  }
  if (!body.ok) throw new PdcApiError(body.error.code, body.error.message)
  return body.data
}

export function apiGet<T>(path: string): Promise<T> {
  return request<T>(path, { method: 'GET' })
}

export async function apiPost<T>(path: string, payload: unknown): Promise<T> {
  const boot = await getBootstrap()
  return request<T>(path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': boot.csrfToken,
    },
    body: JSON.stringify(payload),
  })
}
