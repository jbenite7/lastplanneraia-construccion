import { pedir } from './cliente';
import {
  EsquemaRespuestaCambioClave,
  EsquemaRespuestaCancelacionClave,
  EsquemaRespuestaLogin,
  EsquemaSolicitudCambioClave,
  EsquemaSolicitudLogin,
  type RespuestaCambioClave,
  type RespuestaCancelacionClave,
  type RespuestaLogin,
  type SolicitudCambioClave,
  type SolicitudLogin,
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
