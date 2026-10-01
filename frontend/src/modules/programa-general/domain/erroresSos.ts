import { ApiError } from '../../../lib/api/cliente';

const GENERICO = 'No se pudo registrar la crisis SOS. Inténtalo de nuevo.';

/**
 * Texto para la persona cuando `POST /api/lps/crisis/register` falla. Se resuelve aquí, en el
 * módulo, sin cambiar `cliente.ts` para otros consumidores: el cliente solo recupera el mensaje
 * del servidor cuando viene en `error.mensaje` (errores de `LpsApiController::renderApiError`).
 * Los rechazos del guardia legado (`legacy_require_csrf`, `rbac_guard`) y la sesión expirada
 * traen el mensaje en `mensaje` de primer nivel o con 200 + `respuesta: ERROR`, y el cliente
 * los reporta como «<ruta> respondió 403» o «devolvió una forma inesperada». Esos se traducen
 * por código; nunca se muestra la ruta ni el detalle técnico.
 */
export function mensajeErrorSos(error: unknown): string {
  if (!(error instanceof ApiError)) return GENERICO;

  const SESION_VENCIDA =
    'Tu sesión venció o el proyecto ya no está activo: la crisis no se registró. Recarga la página e inténtalo de nuevo.';
  // Antes de la rama `http`: el mensaje del guardia legado no es para mostrarlo tal cual.
  if (error.codigo === 'SESION_LEGADO') return SESION_VENCIDA;

  const mensajeTecnico = /^\/api\//.test(error.message) || error.message.trim() === '';
  if (error.tipo === 'http' && !mensajeTecnico) return error.message;

  if (error.tipo === 'red') {
    return 'Sin conexión con el servidor: la crisis no se registró. Revisa la conexión e inténtalo de nuevo.';
  }
  if (error.status === 401) return SESION_VENCIDA;
  if (error.tipo === 'forma_invalida' && error.status === 200) {
    return 'Error del sistema: la respuesta del servidor no se pudo leer. Recarga la página para ver si la crisis quedó registrada antes de repetirla.';
  }
  if (error.status === 403) {
    return 'No se pudo registrar la crisis: no tienes permiso para declararla o el token de seguridad venció. Recarga la página e inténtalo de nuevo.';
  }
  if (error.status !== null && error.status >= 500) {
    return 'El servidor no pudo registrar la crisis. Inténtalo de nuevo en unos minutos.';
  }
  return GENERICO;
}
