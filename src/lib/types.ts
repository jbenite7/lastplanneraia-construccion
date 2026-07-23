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
  // 1|0 deliberado (no boolean): AG Grid inferiría cellDataType boolean y
  // renderizaría checkbox ignorando el valueFormatter "Activa" del historial.
  activa: number
  importadoPor: string
  createdAt: string
}

export type ArbolItem = {
  id: number
  codigo: string
  codigoPadre: string | null
  nivel: number
  tipoFila: 'capitulo' | 'subcapitulo' | 'grupo' | 'actividad'
  descripcion: string
  unidad: string | null
  cantidad: number | null
}

export type ArbolInsumo = {
  itemId: number
  descripcion: string
  tipoInsumo: string
  unidad: string
  cantApu: number | null
  rendimiento: number | null
  cantidadTotal: number | null
  valorUnitario: number | null
  valorTotal: number | null
}

export type ArbolPresupuesto = {
  version: { id: number; versionLabel: string; activa: number }
  items: ArbolItem[]
  insumos: ArbolInsumo[]
}

// Tipos del maestro de insumos (Task 6)
export type VinculoInsumo = {
  id: number
  descripcionOriginal: string
  descripcionNorm: string
  unidad: string
  tipoInsumo: string
  cantidadTotal: number
  valorTotal: number
  apariciones: number
  maestroId: number | null
  maestroDescripcion: string | null
  estado: 'pendiente' | 'auto' | 'confirmado'
}

export type ResumenVinculos = {
  total: number
  auto: number
  confirmados: number
  pendientes: number
  cobertura: number
}

export type MaestroInsumo = {
  id: number
  descripcion: string
  unidad: string
  tipoInsumo: string
  activo?: number
  creadoPor?: string
  createdAt?: string
  updatedAt?: string | null
}

export type SugerenciaMaestro = {
  id: number
  descripcion: string
  unidad: string
  tipoInsumo: string
}

// Tipos del importador del maestro SINCO (Fase A2.5)
export type MaestroImportResumen = {
  total: number
  activos: number
  omitidos: number
  agrupaciones: number
  tiposRecurso: number
}

export type MaestroImportPreview = { importToken: string; resumen: MaestroImportResumen }

export type MaestroImportConflicto = { codigoSinco: string; descripcion: string; chocaCon: string }

export type MaestroImportResultado = {
  creados: number
  actualizados: number
  enriquecidos: number
  conflictos: MaestroImportConflicto[]
}

export type MaestroImportErrorFila = { fila: number; columna: string; motivo: string }
