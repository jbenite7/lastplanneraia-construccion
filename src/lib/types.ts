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

// Tipos de importación de presupuesto (Task 7)
export type ImportResumen = {
  capitulos: number
  subcapitulos: number
  grupos: number
  actividades: number
  insumos: number
  costoTotal: number
}

export type ImportPreview = {
  importToken: string
  versionLabel: string | null
  resumen: ImportResumen
  advertencias: string[]
}

export type ImportErrorFila = {
  fila: number
  columna: string
  motivo: string
}

export type VersionPresupuesto = {
  id: number
  versionLabel: string
  archivoNombre: string
  totalActividades: number
  totalInsumos: number
  costoTotal: number
  activa: boolean
  importadoPor: string
  createdAt: string
}
