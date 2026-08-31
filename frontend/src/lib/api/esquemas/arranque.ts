import { z } from 'zod';
import { EsquemaGrupoNavegacion, EsquemaNavegacionBi, EsquemaProyecto, EsquemaUsuario } from './sesion';

/**
 * Las siete pantallas de arranque del shell (spec T01 §7) se reparten así:
 *
 * - `cargando` es puramente local — todavía no hay respuesta de `/api/session`,
 *   así que no tiene representación en este esquema.
 * - `recoverable_error` (red, 5xx, JSON roto) tampoco: es lo que pasa cuando
 *   `pedir()` ni siquiera entrega un cuerpo válido contra este esquema.
 * - Las otras cinco sí viajan en `state`/`reason`: `anonymous` (missing_session),
 *   `password_change_required`, y `authenticated` — que se subdivide en
 *   "sin proyecto" / "listo" según `project`, y en "expirado" según `reason`
 *   cuando `authenticated=false` (timeout, inactive, stale_session,
 *   session_unverified). `useSesion` hace esa derivación; aquí solo se valida
 *   la forma que el servidor puede producir.
 */

const EsquemaCapacidades = z.record(z.string(), z.boolean());
const EsquemaTokenCsrf = z.string().regex(/^[a-f0-9]{64}$/);
const EsquemaSemanaActiva = z.object({ current: z.number().int().positive() });

/** Motivos que `SessionMiddleware` produce hoy para una sesión no autenticada. */
export const RAZONES_NO_AUTENTICADO = [
  'missing_session',
  'timeout',
  'inactive',
  'stale_session',
  'session_unverified',
] as const;

/** De esos motivos, los que significan "hubo sesión y dejó de ser válida". */
export const RAZONES_EXPIRACION = ['timeout', 'inactive', 'stale_session', 'session_unverified'] as const;

export const EsquemaArranqueAnonimo = z.object({
  state: z.literal('anonymous'),
  authenticated: z.literal(false),
  reason: z.enum(RAZONES_NO_AUTENTICADO),
  user: z.null(),
  project: z.null(),
  capabilities: EsquemaCapacidades,
  navigation: z.object({ bi: z.null(), groups: z.array(EsquemaGrupoNavegacion) }),
  week: z.null(),
  csrfToken: EsquemaTokenCsrf,
});

export const EsquemaArranqueCambioClaveRequerido = z.object({
  state: z.literal('password_change_required'),
  authenticated: z.literal(false),
  reason: z.null(),
  user: z.null(),
  project: z.null(),
  capabilities: EsquemaCapacidades,
  navigation: z.object({ bi: z.null(), groups: z.array(EsquemaGrupoNavegacion) }),
  week: z.null(),
  csrfToken: EsquemaTokenCsrf,
});

export const EsquemaArranqueAutenticado = z.object({
  state: z.literal('authenticated'),
  authenticated: z.literal(true),
  reason: z.null(),
  user: EsquemaUsuario,
  project: EsquemaProyecto.nullable(),
  capabilities: EsquemaCapacidades,
  navigation: z.object({ bi: EsquemaNavegacionBi.nullable(), groups: z.array(EsquemaGrupoNavegacion) }),
  week: EsquemaSemanaActiva.nullable(),
  csrfToken: EsquemaTokenCsrf,
});

export const EsquemaArranque = z
  .discriminatedUnion('state', [
    EsquemaArranqueAnonimo,
    EsquemaArranqueCambioClaveRequerido,
    EsquemaArranqueAutenticado,
  ])
  .refine(
    (arranque) => arranque.state !== 'authenticated' || arranque.project !== null || arranque.week === null,
    { message: 'week solo puede existir cuando hay un project activo' },
  );

export type Arranque = z.infer<typeof EsquemaArranque>;
export type ArranqueAutenticado = z.infer<typeof EsquemaArranqueAutenticado>;
