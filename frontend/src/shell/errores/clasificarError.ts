import type { ApiError } from '../../lib/api/cliente';

/**
 * Las siete filas de la tabla de errores globales (spec T01 §15), colapsadas a una variante por
 * fila. La UI decide por `variante`, nunca por `status`/`tipo` crudos de `ApiError` — así un
 * futuro código HTTP no mapeado explícitamente no obliga a tocar cada consumidor (cae en
 * "servidor", ver el `default` de abajo).
 */
export type VarianteError = 'red' | 'contrato' | 'sesion' | 'prohibido' | 'no_encontrado' | 'validacion' | 'servidor';

export interface ClasificacionError {
  variante: VarianteError;
  titulo: string;
  /** Siempre `error.message` — `cliente.ts` ya lo construye de forma segura (nunca inserta HTML o
   *  cuerpo crudo, ver su propio contrato), así que esta capa no reconstruye el texto. */
  mensaje: string;
  correlationId: string | null;
  camposInvalidos: Readonly<Record<string, string>> | null;
}

function titulo(variante: VarianteError): string {
  switch (variante) {
    case 'red':
      return 'Sin conexión';
    case 'contrato':
      return 'Respuesta inesperada';
    case 'sesion':
      return 'Sesión finalizada';
    case 'prohibido':
      return 'Acceso denegado';
    case 'no_encontrado':
      return 'No encontrado';
    case 'validacion':
      return 'Revisa los datos';
    case 'servidor':
      return 'Error del servidor';
  }
}

function variante(error: ApiError): VarianteError {
  if (error.tipo === 'red' || error.tipo === 'abortado') {
    return 'red';
  }

  if (error.tipo !== 'http') {
    // json_invalido | contenido_inesperado | forma_invalida: el contrato esperado se rompió.
    return 'contrato';
  }

  switch (error.status) {
    case 401:
      return 'sesion';
    case 403:
      return 'prohibido';
    case 404:
      return 'no_encontrado';
    case 409:
    case 422:
      return 'validacion';
    default:
      return 'servidor';
  }
}

/**
 * Traduce un `ApiError` (transporte) a una clasificación de presentación (spec T01 §15). Pura y
 * sin efectos: `PanelError` la usa para pintar, `useRecuperacionErrorApi` para decidir si actúa.
 */
export function clasificarError(error: ApiError): ClasificacionError {
  const v = variante(error);
  return {
    variante: v,
    titulo: titulo(v),
    mensaje: error.message,
    correlationId: error.correlationId,
    camposInvalidos: error.camposInvalidos,
  };
}
