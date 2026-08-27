// Cliente API tipado de la Torre de Control (ct-app, etapa piloto).
//
// Mismo patrón de CSRF que pdc-app/src/lib/api.ts + bootstrap.ts: un blob inyectado en el
// global trae el token y viaja en `X-CSRF-Token`, toda petición manda `credentials:
// 'same-origin'` y `X-AIA-Expect-Json: '1'` (convención del repo — SessionMiddleware lee esa
// cabecera para responder JSON en vez de redirigir a /login cuando la sesión vence a mitad de
// una gestión). Diferencia deliberada con pdc-app (ver ct-app/src/lib/api.test.ts, rol A): no
// hay aquí un `bootstrap.ts` con caché + fallback a un GET /contexto propio — Task 5 no define
// ese endpoint, así que el CSRF se lee directo de `globalThis.__CT_BOOTSTRAP__`.

/** Espejo de MetricResult (PHP: src/Services/Bi/MetricExecutor.php, Tasks 2/5). */
export interface MetricResult {
  value: number | null
  basis: {
    obras_incluidas: number
    obras_esperadas: number
    corte: string
    filas_usadas: number
  }
  completeness: 'completa' | 'parcial' | 'insuficiente'
  missing: string[]
}

export type GestionEstado = 'en_gestion' | 'liberada' | 'sin_gestionar' | 'no_aplica'

/** Etiqueta de UI por estado (D87: los tres estados de gestión deben distinguirse por texto). */
export const ETIQUETAS_ESTADO: Record<GestionEstado, string> = {
  sin_gestionar: 'Sin gestionar',
  en_gestion: 'En gestión',
  liberada: 'Liberada',
  no_aplica: 'No aplica',
}

/**
 * Fila de GET /api/bi/control-tower/restricciones (Task 7 paso 3a). 13 campos camelCase, ver
 * `task-7-paso3a-report.md` sección "Forma de cada fila". `semanaInicioActividadBloqueada` puede
 * ser negativo (medido en dev) — no se acota aquí.
 */
export interface Restriccion {
  id: number
  restriccion: string
  semana: number
  actividadBloqueada: string | null
  responsableAsignado: string | null
  fechaCompromiso: string | null
  estadoLiberacion: GestionEstado
  asignadoPor: string | null
  asignadoEn: string | null
  diasVencida: number | null
  semanaInicioActividadBloqueada: number | null
  actividadesEncadenadas: number
  tocaRutaCritica: boolean
}

interface CtBootstrap {
  csrfToken: string
}

/**
 * Error tipado de la API de la Torre. Carga el código del envelope del servidor y el status
 * HTTP, para que el llamador distinga un 403 (sin permiso) de cualquier otra falla sin tener
 * que parsear el mensaje — nunca un catch mudo ni un `undefined`.
 */
export class CtApiError extends Error {
  code: string
  status: number
  details?: unknown

  constructor(code: string, message: string, status: number, details?: unknown) {
    super(message)
    this.name = 'CtApiError'
    this.code = code
    this.status = status
    this.details = details
  }
}

function readBootstrap(): CtBootstrap {
  const raw = (globalThis as Record<string, unknown>).__CT_BOOTSTRAP__
  if (!raw || typeof raw !== 'object' || typeof (raw as Record<string, unknown>).csrfToken !== 'string') {
    // Sin bootstrap inyectado no hay CSRF que mandar: fallar explícito en vez de mandar un
    // token vacío que el servidor rechazaría igual, pero sin decir por qué.
    throw new CtApiError(
      'MISSING_BOOTSTRAP',
      'Falta el contexto de la Torre de Control (__CT_BOOTSTRAP__) para autenticar la petición.',
      0,
    )
  }
  return raw as CtBootstrap
}

interface GestionPayload {
  responsable: string
  fechaCompromiso: string
  estado: GestionEstado
}

interface GestionResponse {
  ok: true
  restriccion: unknown
}

type Envelope = GestionResponse | { ok: false; error: { code: string; message: string } }

/**
 * POST /api/bi/control-tower/restricciones/{id}/gestion — registra la gestión (responsable,
 * fecha de compromiso, estado) de una restricción. Lanza `CtApiError` ante cualquier falla del
 * servidor (403 incluido): la promesa rechaza, nunca resuelve en silencio con un valor falso.
 */
export async function postGestionRestriccion(
  id: number,
  payload: GestionPayload,
): Promise<GestionResponse> {
  const boot = readBootstrap()

  const res = await fetch(`/api/bi/control-tower/restricciones/${id}/gestion`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': boot.csrfToken,
      'X-AIA-Expect-Json': '1',
    },
    body: JSON.stringify(payload),
  })

  const body = (await res.json().catch(() => null)) as Envelope | null

  if (!body || typeof body.ok !== 'boolean') {
    throw new CtApiError('BAD_RESPONSE', `Respuesta inválida del servidor (HTTP ${res.status}).`, res.status)
  }
  if (!body.ok) {
    throw new CtApiError(body.error.code, body.error.message, res.status, body.error)
  }
  return body
}

interface RestriccionesResponse {
  ok: true
  restricciones: Restriccion[]
}

type RestriccionesEnvelope = RestriccionesResponse | { ok: false; error: { code: string; message: string } }

/**
 * GET /api/bi/control-tower/restricciones — listado de restricciones con urgencia (Task 7 paso
 * 3a). Sin CSRF: es un GET, no muta nada, y el backend documenta explícito que ningún GET lo
 * valida (mismo precedente que `pdc-app/src/lib/api.ts`: `apiGet()` no llama a `getBootstrap()`).
 * Lanza `CtApiError` ante cualquier falla — la promesa nunca resuelve en silencio.
 */
export async function getRestricciones(): Promise<Restriccion[]> {
  const res = await fetch('/api/bi/control-tower/restricciones', {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'X-AIA-Expect-Json': '1',
    },
  })

  const body = (await res.json().catch(() => null)) as RestriccionesEnvelope | null

  if (!body || typeof body.ok !== 'boolean') {
    throw new CtApiError('BAD_RESPONSE', `Respuesta inválida del servidor (HTTP ${res.status}).`, res.status)
  }
  if (!body.ok) {
    throw new CtApiError(body.error.code, body.error.message, res.status, body.error)
  }
  return body.restricciones
}

type MetricEnvelope = ({ ok: true } & MetricResult) | { ok: false; error: { code: string; message: string } }

/**
 * GET /api/bi/control-tower/metricas/{metricKey} — ejecuta una métrica `ejecutable` del catálogo
 * (Task 7 paso 5). Envelope FLAT `{ok, value, basis, completeness, missing}`, mirror exacto de
 * `MetricResult` — no anidado bajo una clave `metric`. Mismo criterio que `getRestricciones()`:
 * GET, sin mutación, sin CSRF ni dependencia de `__CT_BOOTSTRAP__`. Lanza `CtApiError` ante
 * cualquier falla del servidor (404 de métrica desconocida o de rol denegado incluidos) — la
 * promesa nunca resuelve en silencio.
 */
export async function getMetric(metricKey: string): Promise<MetricResult> {
  const res = await fetch(`/api/bi/control-tower/metricas/${encodeURIComponent(metricKey)}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'X-AIA-Expect-Json': '1',
    },
  })

  const body = (await res.json().catch(() => null)) as MetricEnvelope | null

  if (!body || typeof body.ok !== 'boolean') {
    throw new CtApiError('BAD_RESPONSE', `Respuesta inválida del servidor (HTTP ${res.status}).`, res.status)
  }
  if (!body.ok) {
    throw new CtApiError(body.error.code, body.error.message, res.status, body.error)
  }
  const { ok: _ok, ...result } = body
  return result
}

/**
 * Espejo camelCase de `LineageService::getForMetric()` (PHP: src/Services/Bi/LineageService.php),
 * fijado por `Linaje.test.tsx` (rol A, Task 7 paso 4). `cutoffPolicy` viaja desde el paso 4
 * ronda 1 — `getForMetric()` no lo copiaba al array de salida hasta ese fix.
 */
export interface LineageInfo {
  metricKey: string
  metricName: string
  definition: string
  formula: string
  sourceView: string
  sourceTables: string
  grain: string
  cutoffPolicy: string
  filters: string
  version: string
  lastUpdated: string
  knownLimitations: string
}

/** Forma cruda snake_case que entrega `LineageService::getForMetric()` vía el envelope BI. */
interface LineageInfoRaw {
  metric_key: string
  metric_name: string
  definition: string
  formula: string
  source_view: string
  source_tables: string
  grain: string
  cutoff_policy: string
  filters: string
  version: string
  last_updated: string
  known_limitations: string
}

/**
 * `GET /api/bi/lineage` usa un envelope propio y distinto al `{ok:true/false}` del resto de este
 * archivo — `{"respuesta":"BIEN","lineage":{...}}` (ver `BiControlTowerApiController::lineage()`,
 * src/Controllers/Api/BiControlTowerApiController.php:296-312). Cuando el `metric_key` no está en
 * el catálogo, `lineage` llega como `[]` (PHP vacío serializado): sigue siendo `respuesta:'BIEN'`,
 * 200, sin error — no es una falla de servidor, así que ese caso no lanza.
 */
interface LineageEnvelope {
  respuesta: string
  lineage: LineageInfoRaw | unknown[]
}

function toLineageInfo(raw: LineageInfoRaw): LineageInfo {
  return {
    metricKey: raw.metric_key,
    metricName: raw.metric_name,
    definition: raw.definition,
    formula: raw.formula,
    sourceView: raw.source_view,
    sourceTables: raw.source_tables,
    grain: raw.grain,
    cutoffPolicy: raw.cutoff_policy,
    filters: raw.filters,
    version: raw.version,
    lastUpdated: raw.last_updated,
    knownLimitations: raw.known_limitations,
  }
}

/**
 * GET /api/bi/lineage?metric_key=X — el contrato de linaje de una métrica (CT-6.3). Resuelve
 * `null` (no rechaza) cuando el servidor responde BIEN con `lineage:[]`: "esta cifra no tiene
 * lineage declarado en el catálogo" es un resultado válido, no una falla de red/servidor. Rechaza
 * con `CtApiError` ante cualquier falla real (403, 500, respuesta sin `respuesta:'BIEN'`, red
 * caída) — la promesa nunca resuelve en silencio ante un error del servidor.
 */
export async function getLineage(metricKey: string): Promise<LineageInfo | null> {
  const res = await fetch(`/api/bi/lineage?metric_key=${encodeURIComponent(metricKey)}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'X-AIA-Expect-Json': '1',
    },
  })

  const body = (await res.json().catch(() => null)) as (LineageEnvelope & Record<string, unknown>) | null

  if (!res.ok || !body || body.respuesta !== 'BIEN') {
    // El endpoint no comparte el envelope `{ok:false,error:{code,message}}` del resto de este
    // archivo (ver docstring de LineageEnvelope): en 401/403 devuelve `{error: string}` o
    // `{reason: string}` según cuál guardia lo detuvo. Se usa lo que venga, con un mensaje
    // genérico como último recurso — nunca un catch mudo.
    const mensaje =
      (typeof body?.error === 'string' ? body.error : null) ??
      (typeof body?.reason === 'string' ? body.reason : null) ??
      `No se pudo cargar el linaje de la métrica (HTTP ${res.status}).`
    throw new CtApiError('BAD_RESPONSE', mensaje, res.status, body)
  }

  if (Array.isArray(body.lineage)) {
    // getForMetric() devuelve [] (PHP) cuando metric_key no está en el catálogo — ver docstring.
    return null
  }

  return toLineageInfo(body.lineage)
}
