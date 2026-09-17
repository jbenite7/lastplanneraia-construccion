import { pedir } from './cliente';
import {
  EsquemaEstadoEnlaceReset,
  EsquemaRecuperacionAceptada,
  EsquemaRespuestaCambioClave,
  EsquemaRespuestaCancelacionClave,
  EsquemaRespuestaLogin,
  EsquemaRestablecimientoAceptado,
  EsquemaSolicitudCambioClave,
  EsquemaSolicitudLogin,
  EsquemaSolicitudRecuperacion,
  EsquemaSolicitudRestablecerClave,
  EsquemaSolicitudValidarReset,
  type EstadoEnlaceReset,
  type RecuperacionAceptada,
  type RespuestaCambioClave,
  type RespuestaCancelacionClave,
  type RespuestaLogin,
  type RestablecimientoAceptado,
  type SolicitudCambioClave,
  type SolicitudLogin,
  type SolicitudRestablecerClave,
} from './esquemas/auth';

/**
 * Gateway de `/api/auth/*` (Tarea 2, S01). Cada función valida su solicitud con
 * el esquema correspondiente antes de enviarla y delega en `pedir()` — el único
 * punto de `fetch` de producción — para el envío y el parseo de la respuesta.
 * Ninguna reintenta la mutación por su cuenta: una falla se propaga como
 * `ApiError` y la decide quien llame.
 */

export async function iniciarSesion(solicitud: SolicitudLogin, csrfToken: string): Promise<RespuestaLogin> {
  const body = EsquemaSolicitudLogin.parse(solicitud);

  return pedir('/api/auth/login', EsquemaRespuestaLogin, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(body),
  });
}

export async function cambiarClave(
  solicitud: SolicitudCambioClave,
  csrfToken: string,
): Promise<RespuestaCambioClave> {
  const body = EsquemaSolicitudCambioClave.parse(solicitud);

  return pedir('/api/auth/password/change', EsquemaRespuestaCambioClave, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(body),
  });
}

export async function cancelarCambioClave(csrfToken: string): Promise<RespuestaCancelacionClave> {
  return pedir('/api/auth/password/cancel', EsquemaRespuestaCancelacionClave, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({}),
  });
}

export async function solicitarRecuperacion(
  email: string,
  csrfToken: string,
): Promise<RecuperacionAceptada> {
  const solicitud = EsquemaSolicitudRecuperacion.parse({ email });

  return pedir('/api/auth/password/forgot', EsquemaRecuperacionAceptada, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(solicitud),
  });
}

/**
 * `signal` solo aplica aquí: validar el enlace ocurre al montar la pantalla y se
 * puede cancelar (navegación fuera, doble montaje de React en desarrollo);
 * `restablecerClave` es un envío explícito de formulario, sin necesidad de abortar.
 */
export async function validarEnlaceReset(
  token: string,
  csrfToken: string,
  signal?: AbortSignal,
): Promise<EstadoEnlaceReset> {
  const solicitud = EsquemaSolicitudValidarReset.parse({ token });

  return pedir('/api/auth/password/reset/validate', EsquemaEstadoEnlaceReset, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(solicitud),
    signal,
  });
}

export async function restablecerClave(
  input: SolicitudRestablecerClave,
  csrfToken: string,
): Promise<RestablecimientoAceptado> {
  const solicitud = EsquemaSolicitudRestablecerClave.parse(input);

  return pedir('/api/auth/password/reset', EsquemaRestablecimientoAceptado, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(solicitud),
  });
}
