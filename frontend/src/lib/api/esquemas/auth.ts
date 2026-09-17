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

export const EsquemaSolicitudRecuperacion = z
  .object({
    email: z.string().trim().email(),
  })
  .strict();

export const EsquemaRecuperacionAceptada = z
  .object({
    success: z.literal(true),
    message: z.string().min(1),
  })
  .strict();

export type SolicitudLogin = z.infer<typeof EsquemaSolicitudLogin>;
export type RespuestaLogin = z.infer<typeof EsquemaRespuestaLogin>;
export type SolicitudCambioClave = z.infer<typeof EsquemaSolicitudCambioClave>;
export type RespuestaCambioClave = z.infer<typeof EsquemaRespuestaCambioClave>;
export type RespuestaCancelacionClave = z.infer<typeof EsquemaRespuestaCancelacionClave>;
export type SolicitudRecuperacion = z.infer<typeof EsquemaSolicitudRecuperacion>;
export type RecuperacionAceptada = z.infer<typeof EsquemaRecuperacionAceptada>;

/**
 * Contratos de `/api/auth/password/reset*` (Tarea 1, S03). El controlador aún no
 * existe en el backend (llega en una tarea posterior de este mismo plan) — como con
 * `RespuestaLogin` en S01, este esquema define el contrato objetivo, no uno ya
 * servido. `TOKEN_RESET_PATTERN` es hex minúscula de 64 caracteres, igual al que
 * genera `PasswordResetService`. El token nunca viaja en la URL: siempre va en el
 * body de un POST.
 */
export const TOKEN_RESET_PATTERN = /^[a-f0-9]{64}$/;
export const MENSAJE_ENLACE_RESET_INVALIDO = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';

export const EsquemaSolicitudValidarReset = z
  .object({
    token: z.string().regex(TOKEN_RESET_PATTERN),
  })
  .strict();
export type SolicitudValidarReset = z.infer<typeof EsquemaSolicitudValidarReset>;

export const EsquemaEstadoEnlaceReset = z.discriminatedUnion('state', [
  z
    .object({
      success: z.literal(true),
      state: z.literal('valid'),
    })
    .strict(),
  z
    .object({
      success: z.literal(true),
      state: z.literal('invalid'),
      message: z.literal(MENSAJE_ENLACE_RESET_INVALIDO),
    })
    .strict(),
]);
export type EstadoEnlaceReset = z.infer<typeof EsquemaEstadoEnlaceReset>;

/**
 * La política de contraseña se evalúa en orden y se detiene en la primera falla,
 * para que el mensaje que ve la persona señale exactamente qué corregir primero —
 * nunca las cuatro comprobaciones a la vez. `TextEncoder` mide bytes, no
 * caracteres UTF-16, para que un acento no cuente doble ni falte contra el mínimo.
 */
export const EsquemaSolicitudRestablecerClave = z
  .object({
    token: z.string().regex(TOKEN_RESET_PATTERN),
    password: z.string(),
    confirmPassword: z.string(),
  })
  .strict()
  .superRefine(({ password, confirmPassword }, context) => {
    if (new TextEncoder().encode(password).length < 6) {
      context.addIssue({
        code: 'custom',
        path: ['password'],
        message: 'La contraseña debe tener al menos 6 caracteres',
      });
    } else if (!/[A-Z]/.test(password)) {
      context.addIssue({
        code: 'custom',
        path: ['password'],
        message: 'Debe contener al menos una letra mayúscula',
      });
    } else if (!/[^a-zA-Z0-9]/.test(password)) {
      context.addIssue({
        code: 'custom',
        path: ['password'],
        message: 'Debe contener al menos un carácter especial (!@#$%...)',
      });
    } else if (password !== confirmPassword) {
      context.addIssue({
        code: 'custom',
        path: ['confirmPassword'],
        message: 'Las contraseñas no coinciden',
      });
    }
  });
export type SolicitudRestablecerClave = z.infer<typeof EsquemaSolicitudRestablecerClave>;

export const EsquemaRestablecimientoAceptado = z
  .object({
    success: z.literal(true),
    message: z.literal('Contraseña restablecida correctamente.'),
    redirect: z.literal('/login?reset=1'),
  })
  .strict();
export type RestablecimientoAceptado = z.infer<typeof EsquemaRestablecimientoAceptado>;
