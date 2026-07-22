export type Bootstrap = {
  projectId: number
  proyectoNombre: string
  rol: string
  csrfToken: string
  usuario: string
}

export type ApiError = { code: string; message: string }

export type ApiResult<T> = { ok: true; data: T } | { ok: false; error: ApiError }

// Payload de GET /plan-compras/api/contexto (contrato con lps-aia, Task 7)
export type Contexto = {
  projectId: number
  proyectoNombre: string
  usuario: string
  rol: string
  csrfToken: string
}
