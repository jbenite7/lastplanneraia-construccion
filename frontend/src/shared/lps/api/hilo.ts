import { z } from 'zod';
import { pedir } from '../../../lib/api/cliente';
import {
  EsquemaAcciones,
  EsquemaComentarioRaiz,
  EsquemaCrisisAlert,
  EsquemaMenciones,
  EsquemaMeta,
  EsquemaModulo,
  EsquemaTarget,
  type Modulo,
} from './esquemas';

/**
 * Pasarela de `GET /api/lps/comments` y `POST /api/lps/comments/add` (T02-AC-075..104). Es el
 * único lugar de `frontend/src/shared/lps/` que llama `pedir()` para el hilo de comentarios —
 * los componentes reciben estas dos funciones ya tipadas, nunca construyen la URL/el cuerpo ellos
 * mismos (brief Tarea 7 §Paso 3).
 *
 * Sólo cubre el camino TIPADO (con `modulo` o `alerta_id`): el camino puramente legado —
 * `consecutivo` solo, sin `modulo` — es el que usa `lps_drawer.js` hoy y responde una forma
 * distinta a propósito (OK/data vacío para un consecutivo inexistente en vez de 404, ver
 * `LpsApiController::comments()` líneas 114-127); React siempre manda `modulo` o `alertaId`, así
 * que ese camino queda fuera de esta pasarela.
 */

const EsquemaRespuestaHilo = z.object({
  respuesta: z.literal('OK'),
  ok: z.literal(true),
  data: z.array(z.unknown()),
  comments: z.array(EsquemaComentarioRaiz),
  target: EsquemaTarget,
  actions: EsquemaAcciones,
  crisisAlert: EsquemaCrisisAlert.optional(),
  meta: EsquemaMeta,
});
export type RespuestaHilo = z.infer<typeof EsquemaRespuestaHilo>;

const EsquemaRespuestaAgregarComentario = z.object({
  respuesta: z.literal('OK'),
  ok: z.literal(true),
  comment_id: z.number().int().positive(),
  data: z.object({ commentId: z.number().int().positive() }),
  target: EsquemaTarget,
  meta: EsquemaMeta,
});
export type RespuestaAgregarComentario = z.infer<typeof EsquemaRespuestaAgregarComentario>;

/** Los dos únicos targets server-authoritative que acepta `LpsTargetResolver` (D-T02-02). */
export type TargetHiloParams = { consecutivo: number; modulo: Modulo } | { alertaId: number };

function queryDeTarget(target: TargetHiloParams): URLSearchParams {
  const query = new URLSearchParams();
  if ('alertaId' in target) {
    query.set('alerta_id', String(target.alertaId));
  } else {
    query.set('consecutivo', String(target.consecutivo));
    query.set('modulo', EsquemaModulo.parse(target.modulo));
  }
  return query;
}

export function obtenerHilo(target: TargetHiloParams, opciones: RequestInit = {}): Promise<RespuestaHilo> {
  const query = queryDeTarget(target);
  return pedir(`/api/lps/comments?${query.toString()}`, EsquemaRespuestaHilo, opciones);
}

export interface AgregarComentarioParams {
  comentario: string;
  csrfToken: string;
  target: TargetHiloParams;
  parentId?: number;
  menciones?: z.infer<typeof EsquemaMenciones>;
}

export function agregarComentario(
  params: AgregarComentarioParams,
  opciones: RequestInit = {},
): Promise<RespuestaAgregarComentario> {
  const cuerpo = queryDeTarget(params.target);
  cuerpo.set('comentario', params.comentario);
  cuerpo.set('_csrf_token', params.csrfToken);
  if (params.parentId !== undefined) {
    cuerpo.set('parent_id', String(params.parentId));
  }
  if (params.menciones) {
    cuerpo.set('menciones', JSON.stringify(params.menciones));
  }

  return pedir('/api/lps/comments/add', EsquemaRespuestaAgregarComentario, {
    ...opciones,
    method: 'POST',
    body: cuerpo,
  });
}
