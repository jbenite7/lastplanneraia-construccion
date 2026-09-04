import { z } from 'zod';
import { pedir } from '../../../lib/api/cliente';

/**
 * Pasarela de `GET /api/notifications/unread` y `POST /api/notifications/read`
 * (`NotificationController`, `src/Controllers/Core/NotificationController.php`).
 *
 * T02 Tarea 9 cerró la brecha que este archivo documentaba desde la Tarea 7: el servidor ahora
 * trae `ok:true` (aditivo, D-T02-08) y ya NO envía `project_id` — se descarta en
 * `NotificationService::getUnreadByUser()` antes de responder (T02-AC-142), así que el campo se
 * conserva aquí sólo como `optional()` por si un despliegue intermedio todavía lo trae, nunca
 * porque el contrato lo pida.
 *
 * `POST /api/notifications/read` ahora SÍ exige CSRF (T02-AC-139) — antes de esta tarea no lo
 * pedía (medido en vivo, 2026-08-31). Reutiliza el mismo token `shell_api` que
 * `AuthApiController`/`SessionApiController` (el `csrfToken` de `useSesion().autenticado`), vía
 * header `X-CSRF-Token` — no el `_csrf_token` de formulario que usa `lps_drawer`.
 */

const EsquemaNotificacionCruda = z.object({
  id: z.number().int().positive(),
  type: z.string(),
  title: z.string(),
  message: z.string(),
  item_count: z.number().int(),
  created_at: z.string(),
  // Compatibilidad hacia atrás únicamente: el servidor ya no envía este campo (T02-AC-142).
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
  ok: z.literal(true),
  data: z.array(EsquemaNotificacion),
});
export type RespuestaNoLeidas = z.infer<typeof EsquemaRespuestaNoLeidas>;

export function obtenerNoLeidas(opciones: RequestInit = {}): Promise<RespuestaNoLeidas> {
  return pedir('/api/notifications/unread', EsquemaRespuestaNoLeidas, opciones);
}

const EsquemaRespuestaMarcarLeida = z.object({
  success: z.boolean(),
  ok: z.boolean(),
});
export type RespuestaMarcarLeida = z.infer<typeof EsquemaRespuestaMarcarLeida>;

/**
 * Idempotente y no enumerativo: un id ajeno o inexistente responde igual que uno propio
 * (T02-AC-141). Exige `csrfToken` (T02-AC-139) — el mismo token de sesión que
 * `useSesion().autenticado?.csrfToken`, nunca uno construido localmente.
 */
export function marcarLeida(id: number, csrfToken: string, opciones: RequestInit = {}): Promise<RespuestaMarcarLeida> {
  const encabezados = new Headers(opciones.headers);
  encabezados.set('X-CSRF-Token', csrfToken);

  return pedir('/api/notifications/read', EsquemaRespuestaMarcarLeida, {
    ...opciones,
    method: 'POST',
    headers: encabezados,
    body: JSON.stringify({ id }),
  });
}
