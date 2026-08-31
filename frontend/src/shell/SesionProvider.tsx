import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { ApiError, pedir } from '../lib/api/cliente';
import {
  EsquemaArranque,
  RAZONES_EXPIRACION,
  type Arranque,
  type ArranqueAutenticado,
} from '../lib/api/esquemas/arranque';

/**
 * Las siete pantallas de arranque (spec T01 §7). `cargando` y
 * `error_recuperable` son puramente locales — no viajan en el bootstrap, ver
 * `esquemas/arranque.ts`. Las otras cinco se derivan de `state`/`reason`/
 * `project` de la respuesta.
 */
export type EstadoSesion =
  | 'cargando'
  | 'anonimo'
  | 'cambio_clave_requerido'
  | 'autenticado_sin_proyecto'
  | 'listo'
  | 'expirado'
  | 'error_recuperable';

export interface ResultadoSesion {
  estado: EstadoSesion;
  /** El bootstrap crudo, cuando llegó uno válido. `null` en `cargando`/`error_recuperable`. */
  arranque: Arranque | null;
  /** Atajo tipado para `autenticado_sin_proyecto`/`listo`, donde `user` nunca es null. */
  autenticado: ArranqueAutenticado | null;
  error: ApiError | null;
  recargar: () => Promise<void>;
}

function esRazonDeExpiracion(reason: string): boolean {
  return (RAZONES_EXPIRACION as readonly string[]).includes(reason);
}

function derivarEstado(arranque: Arranque): Exclude<EstadoSesion, 'cargando' | 'error_recuperable'> {
  if (arranque.state === 'password_change_required') {
    return 'cambio_clave_requerido';
  }

  if (arranque.state === 'anonymous') {
    return esRazonDeExpiracion(arranque.reason) ? 'expirado' : 'anonimo';
  }

  return arranque.project === null ? 'autenticado_sin_proyecto' : 'listo';
}

/**
 * Implementación interna: consulta `/api/session` al montar y expone las
 * siete pantallas de arranque. Privada a este módulo — `SesionProvider` es la
 * única que la invoca, para que solo exista una consulta viva por árbol y
 * cualquier componente descendiente (incluido, más adelante, `ControlActividad`
 * de la Tarea 6) lea del mismo Context en vez de recalcular por su cuenta.
 *
 * Al recargar limpia el bootstrap anterior antes de pedir uno nuevo: nunca se
 * conserva UI operativa de un estado previo mientras se vuelve a resolver
 * sesión o proyecto (spec T01 §7).
 */
function useArranqueSesion(): ResultadoSesion {
  const [arranque, setArranque] = useState<Arranque | null>(null);
  const [error, setError] = useState<ApiError | null>(null);
  const [cargando, setCargando] = useState(true);

  const recargar = useCallback(async () => {
    setCargando(true);
    setError(null);
    setArranque(null);
    try {
      setArranque(await pedir('/api/session', EsquemaArranque));
    } catch (causa) {
      setError(causa instanceof ApiError
        ? causa
        : new ApiError('No se pudo consultar la sesión', { tipo: 'red', codigo: 'NETWORK_ERROR' }));
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  const estado: EstadoSesion = useMemo(() => {
    if (cargando) {
      return 'cargando';
    }
    if (error || !arranque) {
      return 'error_recuperable';
    }

    return derivarEstado(arranque);
  }, [cargando, error, arranque]);

  const autenticado = arranque?.state === 'authenticated' ? arranque : null;

  return { estado, arranque, autenticado, error, recargar };
}

const ContextoSesion = createContext<ResultadoSesion | null>(null);

/**
 * Único punto donde se consulta `/api/session` (spec T01 §6: `SesionProvider`
 * envuelve `AuthOutlet`/`ProjectPicker`/`AppShell`). Cualquier componente que
 * necesite sesión —`Rutas` hoy, `ControlActividad` y el resto de S01–S27 más
 * adelante— vive dentro de este árbol y consume `useSesion()`, nunca invoca
 * el hook de estado por su cuenta.
 */
export function SesionProvider({ children }: { children: ReactNode }) {
  const valor = useArranqueSesion();

  return <ContextoSesion.Provider value={valor}>{children}</ContextoSesion.Provider>;
}

/**
 * Lee la sesión del `SesionProvider` más cercano. Lanza si se usa fuera de
 * uno — un consumidor sin Provider es un error de composición del árbol, no
 * un estado de sesión válido que debamos fingir con datos falsos.
 */
export function useSesion(): ResultadoSesion {
  const contexto = useContext(ContextoSesion);

  if (contexto === null) {
    throw new Error('useSesion() debe usarse dentro de <SesionProvider>');
  }

  return contexto;
}
