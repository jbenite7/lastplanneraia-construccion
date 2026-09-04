import { z } from 'zod';

/**
 * Semana/contexto de administración semanal (Tarea 5, T01). `WeekContextService` (PHP) compone
 * esta misma forma tanto para el bootstrap (`GET /api/session`) como para cada mutación
 * (`/context/week`, `/context/clear-week`) — nunca diverge entre uno y otro.
 */
export const EsquemaSemanaOpcion = z.object({
  number: z.number().int().positive(),
  startsOn: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  endsOn: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
});

export const EsquemaSemanaActiva = z.object({
  current: z.number().int().positive(),
  options: z.array(EsquemaSemanaOpcion),
  actions: z.object({
    select: z.boolean(),
    create: z.boolean(),
    deleteLast: z.boolean(),
  }),
});

export type SemanaActiva = z.infer<typeof EsquemaSemanaActiva>;
export type SemanaOpcion = z.infer<typeof EsquemaSemanaOpcion>;

/** `POST /context/week` y `POST /context/clear-week` (T01-API-04/05). */
export const EsquemaRespuestaContextoSemana = z.object({
  ok: z.literal(true),
  week: EsquemaSemanaActiva.nullable(),
});

/** `POST /api/context/weeks/create` (T01-API-06). */
export const EsquemaRespuestaCrearSemana = z.object({
  ok: z.literal(true),
  week: EsquemaSemanaOpcion,
});

/** `POST /api/context/weeks/delete-last` (T01-API-07). */
export const EsquemaRespuestaEliminarSemana = z.object({
  ok: z.literal(true),
  deletedWeek: z.number().int().positive(),
  maxWeek: z.number().int().min(0),
});
