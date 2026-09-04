import { useEffect, useRef } from 'react';
import type { ApiError } from '../../lib/api/cliente';

type OpcionesRecuperacionErrorApi = {
  /** Vuelve a resolver sesión/proyecto — la única forma segura de "cerrar sesión" ante un 401
   *  fuera del bootstrap (spec T01 §15: "401 → limpiar sesión/proyecto y volver a S01"). El
   *  bootstrap propio de `SesionProvider` ya se recupera solo; este hook es para cualquier OTRA
   *  petición (`pedir()`) que descubra a mitad de camino que la sesión dejó de ser válida. */
  recargar: () => Promise<void>;
};

/**
 * Efecto de recuperación para un `ApiError` que NO vino del bootstrap. Un 401 dispara
 * `recargar()` una sola vez por instancia de error — `SesionProvider` vuelve a resolver estado y
 * normalmente aterriza en `anonimo`/`expirado` (spec T01 §7), sin que este hook necesite navegar
 * ni limpiar nada por su cuenta.
 *
 * 403/404/409/422/5xx/red/contrato NO disparan ningún efecto automático a propósito: esos los
 * resuelve la UI que los muestra (`PanelError`, con sus propios botones de reintentar/salir).
 * Un efecto automático ahí sería una redirección silenciosa que el usuario no pidió.
 */
export function useRecuperacionErrorApi(error: ApiError | null, { recargar }: OpcionesRecuperacionErrorApi): void {
  const ultimoErrorTratado = useRef<ApiError | null>(null);

  useEffect(() => {
    if (error === null || error === ultimoErrorTratado.current) return;
    ultimoErrorTratado.current = error;

    if (error.tipo === 'http' && error.status === 401) {
      void recargar();
    }
  }, [error, recargar]);
}
