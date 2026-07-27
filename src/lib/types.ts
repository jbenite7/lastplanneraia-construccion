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
  sinCambios: boolean
  versionActiva: { id: number; numero: number; label: string | null; createdAt: string } | null
}

export type ImportConfirmResult = {
  versionId: number
  versionNumero: number
  versionLabel: string | null
  versionIdAnterior: number | null
  sinCambios: boolean
  resumen: ImportResumen
}

export type ImportErrorFila = {
  fila: number
  columna: string
  motivo: string
}

export type VersionPresupuesto = {
  id: number
  versionNumero: number
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

// Tipos del comparativo de versiones (Fase A1.6)
export type EstadoDiff = 'nuevo' | 'eliminado' | 'modificado' | 'igual'

export type ActividadDiff = {
  codigo: string
  codigoPadre: string | null
  nivel: number
  tipoFila: 'capitulo' | 'subcapitulo' | 'grupo' | 'actividad'
  descripcion: string
  valorA: number
  valorB: number
  deltaValor: number
  deltaPct: number | null
  estado: EstadoDiff
}

export type InsumoDiff = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  tipoInsumo: string
  cantidadA: number
  cantidadB: number
  valorA: number
  valorB: number
  deltaValor: number
  deltaPct: number | null
  estado: EstadoDiff
}

export type ResumenDiff = {
  costoA: number
  costoB: number
  delta: number
  sobrecostos: number
  ahorros: number
  nuevos: number
  eliminados: number
  modificados: number
}

export type Comparativo = {
  versionA: { id: number; label: string }
  versionB: { id: number; label: string }
  resumen: ResumenDiff
  actividades: ActividadDiff[]
  insumos: InsumoDiff[]
}

// Tipos de paquetes de contratación (Fase A3)
export type PaqueteCatalogo = { id: number; nombre: string; tipoNegociacion: string; modalidad?: string; insumosGlobal: number }

/**
 * Modalidad de contratación — dimensión ortogonal al tipo de negociación: dice CÓMO se compra, que es
 * lo que decide si el paquete entra al plan de fechas y cómo se le hace seguimiento.
 */
export const MODALIDADES: { value: string; label: string; ayuda: string }[] = [
  { value: 'contrato', label: 'Contrato', ayuda: 'Alcance cerrado, un proveedor: proceso completo con fechas' },
  { value: 'orden_compra', label: 'Orden de compra', ayuda: 'Commodity recurrente: solo se programa la primera entrega' },
  { value: 'consumo_directo', label: 'Consumo directo', ayuda: 'Ferretería a demanda: sin proceso ni fecha, se controla el gasto' },
  { value: 'no_contratable', label: 'No contratable', ayuda: 'Nómina e imprevistos: no se le compran a nadie' },
]

export type InsumoPaquete = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  tipoInsumo: string
  agrupacion: string | null
  tipoRecurso: string | null
  cantidadTotal: number
  valorTotal: number
  paqueteId: number | null
  paqueteNombre: string | null
  // 1|0 deliberado (no boolean) — consistente con el resto de flags de la SPA.
  omitido: number
  // Actividades del presupuesto que requieren el insumo (se rellena en cliente para el tooltip).
  actividades?: ActividadesInsumo
}

export type ActividadDeInsumo = { codigo: string; actividad: string; cantidad: number; valor: number }
export type ActividadesInsumo = { total: number; items: ActividadDeInsumo[] }

export type SugerenciaPaquete = {
  descripcionNorm: string
  unidad: string
  paqueteId: number
  paqueteNombre: string
  capa: 'ia' | 'exacta' | 'reglas' | 'tokens' | 'indirectos' | 'agrupacion'
  confianza: 'alta' | 'media' | 'baja'
  evidencia: string
}

/**
 * Reparto de la auto-asignación (A3.3): lo que el motor despacha solo frente a lo que deja a un
 * humano, con el motivo — `valor` (supera el umbral acordado) o `confianza` (la evidencia no basta).
 */
export type PropuestaAuto = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  valorTotal: number
  paqueteId: number
  paqueteNombre: string
  capa: string
  confianza: string
  evidencia: string
  motivo?: 'valor' | 'confianza'
}

export type PlanAuto = {
  version: { id: number; label: string }
  umbral: number
  auto: PropuestaAuto[]
  revision: PropuestaAuto[]
}

export type CandidatoPaquete = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  agrupacion: string | null
  tipoRecurso: string | null
  valorTotal: number
}

/**
 * Tasa de acierto del motor (A3.3): `tasa` es null mientras no haya decisiones suyas aplicadas —
 * un 100 % sin base sería una mentira cómoda. La base se expone para que el número sea auditable.
 */
export type AciertoMotor = { sugerenciasAplicadas: number; correcciones: number; tasa: number | null }

export type ResumenPaquetes = {
  version: { id: number; label: string }
  total: number
  asignados: number
  omitidos: number
  cobertura: number
  coberturaValor?: number
  acierto?: AciertoMotor
  porPaquete: { paqueteId: number; nombre: string; tipoNegociacion: string; modalidad?: string; insumos: number; subtotal: number }[]
}

export const TIPOS_NEGOCIACION: { value: string; label: string }[] = [
  { value: 'a_todo_costo', label: 'A todo costo (Sum. + Inst.)' },
  { value: 'suministro', label: 'Suministro' },
  { value: 'mano_obra', label: 'Mano de obra' },
  { value: 'consumibles', label: 'Consumibles' },
]
