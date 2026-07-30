export type Bootstrap = {
  projectId: number
  proyectoNombre: string
  rol: string
  csrfToken: string
  usuario: string
  usuarioId: number | null
}

export type ApiError = { code: string; message: string }

export type ApiResult<T> = { ok: true; data: T } | { ok: false; error: ApiError }

// Payload de GET /plan-compras/api/contexto (contrato con lps-aia, Task 7)
export type Contexto = {
  projectId: number
  proyectoNombre: string
  usuario: string
  usuarioId: number | null
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
  impacto: ImpactoReimport
}

/** Una fila del detalle de impacto. `tipoInsumoAnterior` solo viene en el grupo de cambios de tipo. */
export type FilaImpacto = {
  descripcion: string
  unidad: string
  tipoInsumo: string
  tipoInsumoAnterior: string | null
  valorTotal: number
  paquete: string | null
}

export type GrupoImpacto = { cantidad: number; valor: number; detalle: FilaImpacto[] }

/**
 * Impacto de recargar una versión del presupuesto sobre el trabajo ya hecho, informado antes de
 * confirmar. No confundir con `ImpactoVersion`, que mide otra cosa: cuántos vínculos del maestro
 * quedan apuntando a la versión que se abandona al cambiar cuál rige.
 *
 * `cambianTipo` compara `tipo_insumo` y no la agrupación de SINCO: esa columna del Excel se lee y se
 * descarta, y la `agrupacion` que existe vive en el maestro indexada por (descripción, unidad), o sea
 * que es propiedad de la identidad del insumo y no cambia entre versiones. `tipo_insumo` sí se
 * persiste y sí es lo que consume el motor de sugerencias.
 */
export type ImpactoReimport = {
  versionActiva: { id: number; label: string | null } | null
  nuevosSinPaquete: GrupoImpacto
  desaparecenConPaquete: GrupoImpacto
  cambianTipo: GrupoImpacto
  valorAfectado: number
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

/**
 * Qué queda afectado al cambiar cuál versión es la oficial. Solo los vínculos del maestro llevan
 * `version_id`; los paquetes y el plan de fechas viven a nivel de proyecto y sobreviven al cambio.
 */
export type ImpactoVersion = {
  vinculosAfectados: number
  versionActual: { id: number; label: string } | null
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
  /** 1 si la importó un parser defectuoso: sus cifras no son comparables. Mismo 1|0 que `activa`. */
  obsoleta: number
  /** Explicación de por qué no es confiable; se muestra tal cual al usuario. */
  obsoletaMotivo: string | null
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
  avisos: AvisosPresupuesto
  items: ArbolItem[]
  insumos: ArbolInsumo[]
}

export type CandidatoGlobal = {
  codigo: string
  descripcion: string
  unidad: string
  /** Con cuántos insumos se resuelve el APU de la actividad. */
  insumos: number
  valorTotal: number
}

export type ActividadSinCantidad = { codigo: string; descripcion: string; valorTotal: number; lineas: number }

export type InsumoEnCero = {
  codigo: string
  actividad: string
  descripcion: string
  unidad: string
  cantidad: number
  valorUnitario: number
}

/**
 * Lo que el presupuesto no explica solo. Viaja dentro del árbol y no en un endpoint aparte: un
 * presupuesto no puede mostrarse sin sus avisos. **Ninguno bloquea nada.**
 *
 * `partidasGlobales.candidatos` llega sin umbral aplicado: el servidor manda los 57 de Da Porto con
 * su valor y la vista filtra con el umbral que pone el usuario.
 */
export type AvisosPresupuesto = {
  costoTotal: number
  insumosDistintos: number
  aparicionesApu: number
  actividadesSinCantidad: { cantidad: number; lineasEnCero: number; detalle: ActividadSinCantidad[] }
  insumosEnCero: { cantidad: number; detalle: InsumoEnCero[] }
  partidasGlobales: { unidades: string[]; candidatos: CandidatoGlobal[] }
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

/** Un lado del comparativo. `obsoleta` viaja aquí para poder advertir antes de que alguien lea el diff. */
export type LadoComparativo = {
  id: number
  label: string
  obsoleta: number
  obsoletaMotivo: string | null
}

export type Comparativo = {
  versionA: LadoComparativo
  versionB: LadoComparativo
  resumen: ResumenDiff
  actividades: ActividadDiff[]
  insumos: InsumoDiff[]
}

// Tipos de paquetes de contratación (Fase A3)
export type PaqueteCatalogo = {
  id: number
  nombre: string
  tipoNegociacion: string
  modalidad?: string
  insumosGlobal: number
  /** Si es «a todo costo», ¿compra producto terminado? Sin esto un MATERIAL ahí es doble conteo. */
  admiteMateriales?: boolean
}

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
export type ActividadesInsumo = {
  total: number
  items: ActividadDeInsumo[]
  /** «CAPÍTULO › subcapítulo › grupo › actividad» de la actividad de mayor valor. */
  ruta?: string
}

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
 * Qué se le cuenta al motor por una asignación. Aceptar su propuesta tal cual conserva la capa
 * (acierto); descartarla deja el par sugerido→elegido para saber dónde falla. Los dos casos son
 * ortogonales: una fila puede venir del motor y estar confirmada por un humano a la vez.
 */
export type Procedencia = {
  origen?: SugerenciaPaquete['capa']
  confianza?: SugerenciaPaquete['confianza']
  evidencia?: string
  confirmado?: boolean
  sugeridoPaqueteId?: number
  sugeridaCapa?: SugerenciaPaquete['capa']
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

// Tipos del plan de fechas (Fase A4)
// `vencimiento` lo resuelve el servidor con la misma regla que el tablero de Vencimientos (B2):
// 'cumplido' | 'vencido' | 'sem1' | 'sem2' | 'sem3' | 'sem6' | 'adelante' | 'sin_fecha'. Se recibe como
// string y no como unión literal a propósito: si el servidor añadiera un corte, la pantalla lo mostraría
// crudo (ver etiquetaCorte) en vez de romper el build o esconder la fila.
export type PasoPlan = {
  orden: number
  paso: string
  dias: number
  fechaInicio: string
  fechaFin: string
  fechaReal: string | null
  vencimiento: string
}

export type FilaPlan = {
  paqueteId: number
  nombre: string
  tipoNegociacion: string
  modalidad: string
  frenteNombre: string
  uniqueId: number
  fechaAncla: string
  fechaArranque: string
  diasTotales: number
  duracionProvisional: boolean
  responsableUserId: number | null
  responsableNombre: string
  responsableCargo: string
  responsableHuerfano: boolean
  diasRetraso: number
  pasos: PasoPlan[]
}

export type ResponsableElegible = {
  id: number
  nombre: string
  cargo: string
}

export type Desfase = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  fechaGuardada: string
  fechaActual: string | null
  diasMovidos: number | null
}

// B2 · el delta de una reprogramación, antes de aplicarla. `arranqueActual` es null cuando el
// paquete todavía no tenía plan calculado: no hay un «desde» que enseñar, y fingir uno mentiría.
export type DeltaPaquete = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  anclaActual: string
  anclaNueva: string
  diasMovidos: number
  arranqueActual: string | null
  arranqueNuevo: string
  pasosQueSeMueven: number
  pasosConFechaReal: number
}

// Un amarre a un frente que ya no está en el cronograma. Va aparte de `movidos` porque no tiene
// delta que aplicar: lo resuelve una persona amarrándolo a mano.
export type HuerfanoReprogramacion = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  anclaActual: string
}

export type SimulacionReprogramacion = {
  movidos: DeltaPaquete[]
  huerfanos: HuerfanoReprogramacion[]
}

// A4.1 · diferido nº 2 — copiar la configuración de pasos de otra obra.
export type OrigenCopia = { projectId: number; nombre: string; pasos: number }

export type PasoPreviewCopia = {
  clave: string
  nombre: string
  alias: string
  diasFijos: number | null
  tieneCatalogo: boolean
}

// `incompleta` = la obra origen tiene algún paso sin duración. La copia hereda ese hueco, así que
// hay que decirlo ANTES de copiar.
export type PreviewCopia = { pasos: PasoPreviewCopia[]; incompleta: boolean }

// A4.1 · diferido nº 3 — una entrada del historial de configuración. `pasos` vacío = esa vez la
// obra volvió al proceso por defecto de la empresa.
export type EntradaHistorialPasos = {
  id: number
  usuario: string
  cuando: string
  pasos: { clave: string; alias: string; diasFijos: number | null }[]
}

// A4.1 · diferido nº 4 — una fila del catálogo legacy de duraciones. Es de la EMPRESA: cambiarla
// mueve las fechas de todas las obras cuyos paquetes la usen, de ahí `paquetesQueLaUsan`.
export type DuracionCatalogo = {
  duracionRef: number
  paqueteContratacion: string
  tipoPaquete: string
  dias: Record<string, number | null>
  paquetesQueLaUsan: number
}

export type FrenteDisponible = { uniqueId: number; nombre: string; capitulo: string; fechaInicio: string }

// Propuesta de amarre a frente del motor A4. `origen`/`confianza` son uniones literales ('similitud'|'rama'
// y 'alta'|'media'|'baja'), distintos del enum de capas del sembrado de insumos (SugerenciaPaquete['capa'])
// — de ahí que no se reutilice ese tipo, aunque el criterio de "aceptar la propuesta = acierto" sí se
// reutiliza (ver procedenciaDeAmarre en planFechas.ts).
export type SugerenciaFrente = {
  uniqueId: number
  nombre: string
  fechaInicio: string
  origen: 'similitud' | 'rama'
  confianza: 'alta' | 'media' | 'baja'
  evidencia: string
}

/** Lo que envía `POST plan/amarrar` cuando el frente elegido coincide con la propuesta del motor. */
export type ProcedenciaAmarre = { origen: string; confianza: string; evidencia: string; confirmado: true }

export type AmarrePlan = {
  uniqueId: number
  frenteNombre: string
  fechaAncla: string
  origen: 'similitud' | 'rama' | 'humano'
  confianza: 'alta' | 'media' | 'baja' | null
  confirmadoHumano: boolean
}

/**
 * Un destino contratable: la unidad del módulo. Un paquete sin partir (`subpaqueteId = 0`) o un lote
 * de un paquete partido. Lo define `SubpaquetesService::destinos()` en el servidor, que es el único
 * sitio donde se decide qué cuenta como unidad — el plan de fechas, el tablero de vencimientos y el
 * flujo de caja consumen esa misma lista para no poder contar distinto.
 */
export type DestinoContratable = {
  paqueteId: number
  subpaqueteId: number
  nombre: string
  paqueteNombre: string
  esLote: boolean
  esResto: boolean
  modalidad: string
  generaProceso: boolean
  valor: number
}

export type PlanResultado = {
  plan: FilaPlan[]
  /** Indexado por paquete y solo los SIN partir: un paquete partido no es una unidad contratable. */
  amarres: Record<number, AmarrePlan>
  /** Las unidades contratables de la obra: paquetes sin partir y lotes de los partidos. */
  destinos: DestinoContratable[]
  /** Qué destinos ya tienen frente, por paquete Y lote. */
  amarresDestino: { paqueteId: number; subpaqueteId: number }[]
}

/**
 * Todas las etiquetas conocidas: sirve para PINTAR y FILTRAR cualquier tipo que venga del catálogo.
 * No es la lista de lo que se puede crear — para eso está `TIPOS_NEGOCIACION_CREABLES`.
 */
export const TIPOS_NEGOCIACION: { value: string; label: string }[] = [
  { value: 'a_todo_costo', label: 'A todo costo (Sum. + Inst.)' },
  { value: 'suministro', label: 'Suministro' },
  { value: 'mano_obra', label: 'Mano de obra' },
  { value: 'consumibles', label: 'Consumibles' },
  // Los buckets no contratables (nómina, imprevistos, provisiones) no compran nada: ninguno de los
  // cuatro tipos de arriba los describe. Ver 20260728_pdc_v2_tipo_no_aplica.php.
  { value: 'no_aplica', label: 'No aplica' },
]

/**
 * Lo que el backend acepta hoy al crear un paquete: `PaquetesService::crearPaquete()` valida contra
 * su constante `TIPOS`, que todavía no lista `no_aplica`. Ofrecerlo en el formulario devolvería
 * `PAQUETE_INVALIDO` al enviar, así que se ofrece solo para pintar y filtrar, no para crear.
 *
 * Cuando esa constante incluya `no_aplica`, esta lista puede volver a ser `TIPOS_NEGOCIACION`.
 */
export const TIPOS_NEGOCIACION_CREABLES = TIPOS_NEGOCIACION.filter((t) => t.value !== 'no_aplica')

/**
 * A4.1 — un paso del catálogo de pasos de contratación de la empresa.
 *
 * `colLegacy` dice de qué columna del catálogo legacy de duraciones salen sus días **por paquete**
 * (concreto no tarda lo mismo que unas puertas). Cuando es null, el paso no tiene respaldo ahí y su
 * duración la fija la obra: son los casos de Licify y la aprobación del cliente, que el sistema viejo
 * nunca guardó en columnas propias.
 */
export type PasoCatalogo = {
  id: number
  clave: string
  nombre: string
  colLegacy: string | null
  diasSugeridos: number | null
  peso: number | null
  ordenDefault: number
}

/** Un paso tal como lo usa una obra: con su alias y sus días fijos ya resueltos. */
export type PasoProyecto = {
  pasoId: number | null
  clave: string
  nombre: string
  colLegacy: string | null
  diasFijos: number | null
  peso: number | null
}

export type RespuestaPasos = {
  catalogo: PasoCatalogo[]
  /** Los pasos efectivos: si la obra no configuró nada, son los siete por defecto de la empresa. */
  proyecto: PasoProyecto[]
  configurado: boolean
  /** Cuántos paquetes tienen plan calculado: quitar un paso borra una fila por cada uno. */
  paquetesConPlan: number
}


/**
 * Un nodo del cronograma al que se puede amarrar. `esFrente` distingue los encabezados de las
 * actividades: ambos son destinos válidos, pero el motor solo propone encabezados salvo que una
 * correspondencia curada nombre una actividad (el caso CUBIERTA → LOSA AÉREA CUBIERTA).
 */
export type AnclaDisponible = FrenteDisponible & { esFrente: boolean }

/** Por qué un paquete no recibió propuesta, y qué rama hay que resolver para que la reciba. */
export type MotivoSinPropuesta = { texto: string; rama: string | null }

export type Correspondencia = {
  rama: string
  ancla: string
  confirmado: boolean
  alcance: string
  nota: string
}

export type PanelCorrespondencias = {
  correspondencias: Correspondencia[]
  pendientes: string[]
  confirmadas: number
  sinConfirmar: number
}

// --- PDC v2 · Fase B1 (Seguimiento) ---

/** Una fila de `GET /plan-compras/api/seguimiento`: el estado de un paquete de un vistazo. */
export type FilaSeguimiento = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  responsableUserId: number | null
  responsableNombre: string
  responsableHuerfano: boolean
  pasoActual: string
  cumplidos: number
  total: number
  estado: 'sin_empezar' | 'en_curso' | 'terminado'
  atrasado: boolean
  finProgramado: string | null
  finProyectado: string
}

/**
 * Un paso en el panel de detalle, con sus tres fechas.
 *
 * `fechaInicio`/`fechaFin` en null significan «este paso lleva avance pero el plan aun no se ha
 * recalculado tras un reamarre» — no es un error, y la pantalla lo muestra tal cual.
 */
export type PasoSeguimiento = {
  pasoId: number | null
  orden: number
  paso: string
  dias: number
  fechaInicio: string | null
  fechaFin: string | null
  fechaReal: string | null
  proyectadoInicio: string
  proyectadoFin: string
  desfaseDias: number | null
  registradoPor: string
  registradoAt: string | null
}

/** Los cuatro filtros de la lista. `''` y `false` significan «no filtrar por esto». */
export type FiltrosSeguimiento = {
  soloMios: boolean
  frente: string
  estado: '' | 'sin_empezar' | 'en_curso' | 'terminado'
  soloAtrasados: boolean
}

// Tablero de vencimientos (Fase B2, primera mitad). Una fila por PASO pendiente, no por paquete.
export type FilaVencimiento = {
  paqueteId: number
  paquete: string
  frenteNombre: string
  pasoId: number | null
  orden: number
  paso: string
  clave: string
  fechaFin: string | null
  responsableUserId: number | null
  responsableNombre: string
  estado: string
  diasDesfase: number | null
}

export type RespuestaVencimientos = {
  hoy: string
  filas: FilaVencimiento[]
  conteos: Record<string, number>
  totalPendientes: number
  pasos: { clave: string; paso: string }[]
  sinFechas: { paquetes: number; sinFrente: number; sinCalcular: number }
}
