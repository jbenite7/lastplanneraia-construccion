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
