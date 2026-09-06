import { z } from 'zod';

/**
 * Respuestas de los dos endpoints que `ControlActividad` posee en exclusiva (Tarea 6, T01):
 * el heartbeat de `/session/touch` (`SessionController::touch`) y el logout idempotente de
 * `/api/auth/logout` (`AuthApiController::logout`).
 */
export const EsquemaRespuestaTouch = z.object({
  success: z.boolean(),
  timestamp: z.number(),
  timeoutSeconds: z.number(),
});

export const EsquemaRespuestaLogout = z.object({
  success: z.boolean(),
});

export type RespuestaTouch = z.infer<typeof EsquemaRespuestaTouch>;
export type RespuestaLogout = z.infer<typeof EsquemaRespuestaLogout>;
