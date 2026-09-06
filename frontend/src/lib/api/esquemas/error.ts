import { z } from 'zod';

/**
 * Forma tolerante del cuerpo de error de `/api/*`.
 *
 * El backend no tiene un único envoltorio: `ErrorPage` usa
 * `{error:{codigo,mensaje}}`, varios controladores de `Api/` usan
 * `{ok:false,error:{code,message}}` (y a veces `fields` sueltos junto al
 * error), y `SessionMiddleware::finishUnauthorized()` responde
 * `{success:false,sessionExpired,reason,redirect}`. `pedir()` extrae lo que
 * encuentre de cualquiera de esas formas en vez de exigir una sola — por eso
 * todo aquí es opcional y el objeto acepta campos adicionales.
 */
export const EsquemaCuerpoErrorApi = z
  .object({
    error: z
      .object({
        codigo: z.string().optional(),
        code: z.string().optional(),
        mensaje: z.string().optional(),
        message: z.string().optional(),
        campos: z.record(z.string(), z.string()).optional(),
        fields: z.record(z.string(), z.string()).optional(),
      })
      .optional(),
    reason: z.string().optional(),
    redirect: z.string().optional(),
    correlationId: z.string().optional(),
  })
  .passthrough();

export type CuerpoErrorApi = z.infer<typeof EsquemaCuerpoErrorApi>;
