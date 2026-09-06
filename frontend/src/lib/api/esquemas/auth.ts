import { z } from 'zod';

/**
 * Contratos de `/api/auth/*` (Tarea 2, S01). `next` reemplaza al booleano legacy
 * `mustChangePassword` que hoy todavía emite `AuthApiController::login()` — el
 * backend se actualiza en la Tarea 5 de este mismo plan para dejar de emitirlo;
 * este esquema define el contrato objetivo, no el que el servidor sirve hoy.
 */

export const EsquemaSolicitudLogin = z.object({
  username: z.string().trim().min(1),
  password: z.string().min(1),
});

export const EsquemaRespuestaLogin = z.object({
  success: z.literal(true),
  next: z.enum(['projects', 'password_change']),
  message: z.null(),
});

export const EsquemaSolicitudCambioClave = z.object({
  password: z.string(),
  confirmation: z.string(),
});

export const EsquemaRespuestaCambioClave = z.object({
  success: z.literal(true),
  next: z.literal('projects'),
});

export const EsquemaRespuestaCancelacionClave = z.object({
  success: z.literal(true),
  next: z.literal('login'),
});

export type SolicitudLogin = z.infer<typeof EsquemaSolicitudLogin>;
export type RespuestaLogin = z.infer<typeof EsquemaRespuestaLogin>;
export type SolicitudCambioClave = z.infer<typeof EsquemaSolicitudCambioClave>;
export type RespuestaCambioClave = z.infer<typeof EsquemaRespuestaCambioClave>;
export type RespuestaCancelacionClave = z.infer<typeof EsquemaRespuestaCancelacionClave>;
