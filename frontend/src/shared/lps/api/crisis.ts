import { z } from 'zod';
import { pedir } from '../../../lib/api/cliente';
import { EsquemaMeta, EsquemaTarget, queryDeTarget } from './esquemas';
import type { TargetHiloParams } from './hilo';

/**
 * Pasarela de `POST /api/lps/crisis/register` y `POST /api/lps/crisis/close` (T02-AC-105..129).
 * Único punto que llama `pedir()` para el ciclo de crisis; `AccionesSos.tsx`/`CierreCrisis.tsx`
 * reciben estas operaciones ya tipadas.
 */

export const EsquemaTrigger = z.enum(['MANUAL', 'SOS-RES', 'SOS-DIR', 'SOS-COO', 'SOS-GER']);
export type Trigger = z.infer<typeof EsquemaTrigger>;

const EsquemaRespuestaRegistrarCrisis = z.object({
  respuesta: z.literal('OK'),
  ok: z.literal(true),
  mensaje: z.string(),
  data: z.object({ alertId: z.number().int().positive(), wasActive: z.boolean() }),
  target: EsquemaTarget,
  meta: EsquemaMeta,
});
export type RespuestaRegistrarCrisis = z.infer<typeof EsquemaRespuestaRegistrarCrisis>;

const EsquemaRespuestaCerrarCrisis = z.object({
  respuesta: z.literal('OK'),
  ok: z.literal(true),
  mensaje: z.string(),
  data: z.object({ alertId: z.number().int().positive() }),
  target: EsquemaTarget,
  meta: EsquemaMeta,
});
export type RespuestaCerrarCrisis = z.infer<typeof EsquemaRespuestaCerrarCrisis>;

export interface RegistrarCrisisParams {
  trigger: Trigger;
  csrfToken: string;
  target: TargetHiloParams;
}

/**
 * `actions.notifyNext` es idempotente (T02-AC-111): registrar sobre una alerta ya activa no
 * cambia de nivel — `data.wasActive` es la única señal de si ya existía.
 */
export function registrarCrisis(
  params: RegistrarCrisisParams,
  opciones: RequestInit = {},
): Promise<RespuestaRegistrarCrisis> {
  // El tipo TS ya cierra `trigger` al enum; no se revalida aquí con `.parse()` — eso duplicaría
  // la validación de `LpsCrisisTrigger::isValid()` en el servidor (única fuente de verdad,
  // AGENTS.md "el servidor manda sobre alcance y permisos") y, peor, un caller que la rodee (TS
  // no es una barrera en runtime) recibiría un `ZodError` sin tipar en vez del `ApiError`
  // consistente que ya arma `pedir()` a partir del 422 real del servidor.
  const cuerpo = queryDeTarget(params.target);
  cuerpo.set('trigger', params.trigger);
  cuerpo.set('_csrf_token', params.csrfToken);

  return pedir('/api/lps/crisis/register', EsquemaRespuestaRegistrarCrisis, {
    ...opciones,
    method: 'POST',
    body: cuerpo,
  });
}

export interface CerrarCrisisParams {
  alertaId: number;
  justificacion: string;
  csrfToken: string;
}

/**
 * Éxito no limpia banderas localmente (T02-AC-128): el llamador recarga el snapshot vía
 * `obtenerHilo()`, esta pasarela sólo confirma el cierre.
 */
export function cerrarCrisis(params: CerrarCrisisParams, opciones: RequestInit = {}): Promise<RespuestaCerrarCrisis> {
  const cuerpo = new URLSearchParams({
    alerta_id: String(params.alertaId),
    justificacion: params.justificacion,
    _csrf_token: params.csrfToken,
  });

  return pedir('/api/lps/crisis/close', EsquemaRespuestaCerrarCrisis, {
    ...opciones,
    method: 'POST',
    body: cuerpo,
  });
}
