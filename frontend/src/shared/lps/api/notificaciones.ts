import { z } from 'zod';
import { pedir } from '../../../lib/api/cliente';

/**
 * Pasarela de `GET /api/notifications/unread` y `POST /api/notifications/read`
 * (`NotificationController`, `src/Controllers/Core/NotificationController.php`).
 *
 * A diferencia de `hilo.ts`/`crisis.ts`, esta pasarela modela el contrato REAL medido en vivo
 * (2026-08-31, `docker compose exec app curl .../api/notifications/unread` contra el fixture del
 * worktree), no el aditivo documentado en
 * `docs/superpowers/specs/2026-08-30-t02-contexto-lps-react-design.md` §Notificaciones: ese
 * documento describe una forma con `ok:true` y sin `project_id` que las Tareas 9-11 (backend,
 * fuera de esta tarea) todavía no implementaron — `NotificationController` hoy no trae `ok` y sí
 * trae `project_id`. Se sigue la regla del brief: "es tu fuente de verdad sobre la forma que
 * devuelve el servidor hoy — no la que te gustaría". Cuando esa tarea backend aterrice, este
 * archivo se actualiza junto con su prueba.
 *
 * `POST /api/notifications/read` tampoco exige CSRF hoy (comprobado en vivo: un POST sin
 * `_csrf_token` responde `{"success":true}`) — a diferencia de las mutaciones de `lps_drawer`, que
 * sí lo exigen vía `legacy_require_csrf('lps_drawer')`. No se inventa un campo que el servidor no
 * pide.
 */

const EsquemaNotificacionCruda = z.object({
  id: z.number().int().positive(),
  type: z.string(),
  title: z.string(),
  message: z.string(),
  item_count: z.number().int(),
  created_at: z.string(),
  // project_id viaja hoy (snake_case, a veces string no numérico — "optimizacionJMC"), pero
  // React nunca lo necesita: se descarta en la transformación, igual que
  // LpsThreadPresenter::presentReact() nunca expone project_id/usuario_id (T02-AC-083).
  project_id: z.unknown().optional(),
});

/** Forma que consume React: camelCase, sin `project_id`. */
const EsquemaNotificacion = EsquemaNotificacionCruda.transform(({ id, type, title, message, item_count, created_at }) => ({
  id,
  type,
  title,
  message,
  itemCount: item_count,
  createdAt: created_at,
}));
export type Notificacion = z.infer<typeof EsquemaNotificacion>;

const EsquemaRespuestaNoLeidas = z.object({
  success: z.literal(true),
  data: z.array(EsquemaNotificacion),
});
export type RespuestaNoLeidas = z.infer<typeof EsquemaRespuestaNoLeidas>;

export function obtenerNoLeidas(opciones: RequestInit = {}): Promise<RespuestaNoLeidas> {
  return pedir('/api/notifications/unread', EsquemaRespuestaNoLeidas, opciones);
}

const EsquemaRespuestaMarcarLeida = z.object({
  success: z.boolean(),
});
export type RespuestaMarcarLeida = z.infer<typeof EsquemaRespuestaMarcarLeida>;

/** Idempotente y no enumerativo: un id ajeno o inexistente responde igual que uno propio. */
export function marcarLeida(id: number, opciones: RequestInit = {}): Promise<RespuestaMarcarLeida> {
  return pedir('/api/notifications/read', EsquemaRespuestaMarcarLeida, {
    ...opciones,
    method: 'POST',
    body: JSON.stringify({ id }),
  });
}
