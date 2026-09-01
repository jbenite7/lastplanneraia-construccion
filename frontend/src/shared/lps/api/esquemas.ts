import { z } from 'zod';

/**
 * Bloques Zod compartidos por las pasarelas de `frontend/src/shared/lps/api/` (Tarea 7, T02).
 *
 * Decisión sobre "estricto" (brief Tarea 7): estos esquemas son estrictos en FORMA — tipo exacto,
 * presencia/ausencia según el caso, enums cerrados donde el servidor mismo valida un enum cerrado
 * (`module`, `actorWriteBlock`, `trigger`) — pero NO llaman `.strict()` a nivel de objeto: una
 * clave adicional que el servidor agregue de forma aditiva se ignora en vez de romper el parseo.
 * Es la misma postura que ya toman `esquemas/sesion.ts`, `esquemas/contexto.ts` y
 * `esquemas/arranque.ts` (ninguno usa `.strict()`), así que esta pasarela no introduce una
 * política nueva: la generaliza al módulo LPS. La razón de fondo la da el propio brief: el
 * servidor evoluciona el envoltorio en fases (D-T02-08, D-T02-09 añaden campos aditivos sin
 * versionar la ruta) — `.strict()` convertiría cada campo nuevo del backend en una regresión del
 * frontend el mismo día en que se agrega, antes de que exista una tarea para consumirlo.
 *
 * Dónde SÍ se es estricto, y por qué es seguro serlo:
 * - `module` (PG/PI/PS): `LpsTargetResolver` sólo acepta esos tres módulos
 *   (`src/Services/Lps/LpsApiController.php` construye el target con ese resolver); un cuarto
 *   valor es un contrato roto, no una evolución aditiva.
 * - `actorWriteBlock` (none/forbidden/profile_required): enum cerrado de
 *   `LpsActionPolicy::evaluate()` (src/Services/Lps/LpsActionPolicy.php:23-28).
 * - `trigger` (MANUAL/SOS-RES/SOS-DIR/SOS-COO/SOS-GER): enum cerrado de `LpsCrisisTrigger::VALUES`
 *   (src/Services/Lps/LpsCrisisTrigger.php).
 * - `target.kind` (activity/alert): las dos únicas formas de `LpsTarget` (constantes
 *   `KIND_ACTIVITY`/`KIND_ALERT`, src/Services/Lps/LpsTarget.php).
 *
 * Dónde deliberadamente NO se cierra el enum: `type` de una notificación
 * (`NotificationType::$registry`, src/Core/Notifications/NotificationType.php) es un catálogo que
 * crece sin tocar el transporte — cerrarlo aquí rompería el frontend cada vez que se registre un
 * tipo nuevo en el backend sin que React necesite saber de él todavía.
 */

export const EsquemaModulo = z.enum(['PG', 'PI', 'PS']);
export type Modulo = z.infer<typeof EsquemaModulo>;

export const EsquemaActorWriteBlock = z.enum(['none', 'forbidden', 'profile_required']);

export const EsquemaMeta = z.object({
  requestId: z.string(),
});

/**
 * `actions` de `LpsActionPolicy::evaluate()` (src/Services/Lps/LpsActionPolicy.php:17): sólo
 * booleanos efectivos + el motivo de bloqueo de escritura. Nunca una matriz de roles.
 */
export const EsquemaAcciones = z.object({
  read: z.boolean(),
  comment: z.boolean(),
  notifyNext: z.boolean(),
  close: z.boolean(),
  actorWriteBlock: EsquemaActorWriteBlock,
});

/**
 * `LpsApiController::targetToArray()` (src/Controllers/Api/LpsApiController.php:288-302):
 * `alertId` sólo existe cuando `kind==='alert'` — de ahí la unión discriminada en vez de un
 * `alertId` opcional plano, que dejaría pasar la combinación imposible "activity" + alertId.
 */
const EsquemaTargetBase = {
  activityId: z.number().int().positive(),
  module: EsquemaModulo,
  week: z.number().int().positive(),
};

export const EsquemaTargetActividad = z.object({
  kind: z.literal('activity'),
  ...EsquemaTargetBase,
});

export const EsquemaTargetAlerta = z.object({
  kind: z.literal('alert'),
  ...EsquemaTargetBase,
  alertId: z.number().int().positive(),
});

export const EsquemaTarget = z.discriminatedUnion('kind', [EsquemaTargetActividad, EsquemaTargetAlerta]);
export type Target = z.infer<typeof EsquemaTarget>;

/**
 * `crisisAlert` sólo viaja cuando `target.isAlert()` (LpsApiController.php:159-165). `level` no es
 * opcional aquí: `LpsTarget::forAlert()` siempre lo recibe como `int`, nunca `null`.
 */
export const EsquemaCrisisAlert = z.object({
  id: z.number().int().positive(),
  active: z.boolean(),
  level: z.number().int().positive(),
});

/** `{roles: string[]}` normalizado por `LpsThreadService::normalizeMentions()`, o ausente. */
export const EsquemaMenciones = z
  .object({
    roles: z.array(z.string()),
  })
  .nullable();

/**
 * Vista React de un comentario (`LpsThreadPresenter::item()` con `includeUserId: false`,
 * src/Services/Lps/LpsThreadPresenter.php:54-71): nunca `usuario_id`, `project_id` ni prefijo de
 * tabla — eso es exclusivo de `presentLegacy()`, que `comments` (el campo que React lee) no usa.
 */
const EsquemaComentarioHijo = z.object({
  id: z.number().int().positive(),
  comentario: z.string(),
  created_at: z.string(),
  autor_nombre: z.string().nullable(),
  autor_cargo: z.string().nullable(),
  menciones: EsquemaMenciones,
});

/**
 * Un solo nivel de anidación (`LpsThreadPresenter::buildTree()`): sólo las raíces traen
 * `respuestas`; una respuesta nunca trae `respuestas` propio (reply-a-reply se descarta en
 * silencio en el presenter, nunca llega aquí).
 */
export const EsquemaComentarioRaiz = EsquemaComentarioHijo.extend({
  respuestas: z.array(EsquemaComentarioHijo),
});
export type ComentarioRaiz = z.infer<typeof EsquemaComentarioRaiz>;

/**
 * Los dos únicos targets server-authoritative que acepta `LpsTargetResolver` (D-T02-02). Vivía
 * duplicado literal en `hilo.ts` y `crisis.ts` (mismo cuerpo, mismo nombre de función); se extrae
 * aquí — encargo del controlador de la Tarea 8 — antes de que un tercer llamador lo triplicara.
 */
export type TargetHiloParams = { consecutivo: number; modulo: Modulo } | { alertaId: number };

/**
 * Serializa un target a los mismos `URLSearchParams` que espera tanto `GET /api/lps/comments`
 * (query string) como los POST de hilo/crisis (cuerpo `application/x-www-form-urlencoded`, donde
 * el llamador añade sus propios campos sobre este mismo `URLSearchParams`). Puerto 1:1 del
 * `queryDeTarget` que antes vivía por separado en cada pasarela.
 */
export function queryDeTarget(target: TargetHiloParams): URLSearchParams {
  const query = new URLSearchParams();
  if ('alertaId' in target) {
    query.set('alerta_id', String(target.alertaId));
  } else {
    query.set('consecutivo', String(target.consecutivo));
    query.set('modulo', EsquemaModulo.parse(target.modulo));
  }
  return query;
}
