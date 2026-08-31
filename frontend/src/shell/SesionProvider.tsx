import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { ApiError, pedir } from '../lib/api/cliente';
import {
  EsquemaArranque,
  RAZONES_EXPIRACION,
  type Arranque,
  type ArranqueAutenticado,
} from '../lib/api/esquemas/arranque';
import type { ResultadoCierreSesion } from './control-actividad/ControlActividad';
import { useControlActividad } from './control-actividad/useControlActividad';

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
  /**
   * Cierra sesión de forma CSRF-idempotente vía el único `ControlActividad` del árbol (Tarea 6,
   * T01). Nunca construyas tu propio POST a `/api/auth/logout` — este es el único camino. El
   * `ResultadoCierreSesion` resuelto distingue "el servidor confirmó" de "no logramos confirmarlo"
   * (fallo de red) — la mayoría de los llamadores puede ignorarlo (la invalidación local ya ocurrió
   * de todas formas); `logoutSinConfirmar` abajo es la proyección de ese mismo dato para la UI.
   */
  cerrarSesion: () => Promise<ResultadoCierreSesion>;
  /**
   * Se incrementa en cada `recargar()` (login, cambio de proyecto, logout, proyecto inválido).
   * Expuesta para que un futuro cliente de módulo pueda invalidar su propia caché derivada sin
   * volver a resolver sesión por su cuenta.
   */
  generacion: number;
  /**
   * La señal de aborto de la petición de bootstrap vigente. `null` antes de la primera consulta.
   * Un cliente de módulo puede pasarla a `pedir(ruta, esquema, { signal })` para que sus propias
   * peticiones se cancelen junto con el cambio de sesión/proyecto que las volvió obsoletas.
   */
  señal: AbortSignal | null;
  /**
   * `true` cuando el último `cerrarSesion()` no logró confirmarse con el servidor (fallo de red,
   * no un 403 idempotente — ver `ResultadoCierreSesion` en `ControlActividad.ts`). El cliente
   * invalidó la sesión localmente igual, pero un consumidor (p. ej. el mensaje de
   * `error_recuperable` en `rutas.tsx`) puede usar esto para no decir "sesión cerrada" cuando en
   * realidad no hay confirmación del servidor. Se limpia en cuanto `estado` deja de ser
   * `error_recuperable`. Fix round 1 (Tarea 6): antes este caso se conflacionaba con la
   * idempotencia legítima del 403.
   */
  logoutSinConfirmar: boolean;
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
 * cualquier componente descendiente (incluido `ControlActividad` de la Tarea 6)
 * lea del mismo Context en vez de recalcular por su cuenta.
 *
 * Al recargar limpia el bootstrap anterior antes de pedir uno nuevo: nunca se
 * conserva UI operativa de un estado previo mientras se vuelve a resolver
 * sesión o proyecto (spec T01 §7).
 *
 * **Generación e invalidación (Tarea 6, checkpoint T01 "un cambio de sesión/proyecto invalida
 * todo resultado operativo previo"):** `recargar()` es la única vía por la que hoy pasan cambiar
 * de proyecto (`useSelectorProyecto`/`PanelCambiarProyecto` vía `alCambiarProyecto`), logout
 * (`ControlActividad.alCerrarSesion`) y un proyecto que dejó de ser válido — así que la garantía
 * de generación vive aquí una sola vez, no en cada llamador. Cada `recargar()`: (1) aborta el
 * `AbortController` de la petición anterior si seguía en vuelo, (2) sube la generación, (3) solo
 * aplica el resultado (éxito o error) si la generación no cambió mientras la petición estaba en
 * curso — una respuesta tardía de una generación ya descartada se ignora en silencio. El
 * `AbortSignal` vigente se expone en `señal` para que un cliente de módulo futuro encadene sus
 * propias peticiones a la misma cancelación.
 */
function useArranqueSesion(): Omit<ResultadoSesion, 'cerrarSesion' | 'logoutSinConfirmar'> & { csrfTokenAutenticado: string | null } {
  const [arranque, setArranque] = useState<Arranque | null>(null);
  const [error, setError] = useState<ApiError | null>(null);
  const [cargando, setCargando] = useState(true);
  const [generacion, setGeneracion] = useState(0);
  const [señal, setSeñal] = useState<AbortSignal | null>(null);
  // Espejo de `autenticado?.csrfToken` que, a propósito, NO se limpia en el instante en que
  // `recargar()` pone `arranque` en `null` — solo se actualiza cuando llega un resultado nuevo.
  // `ControlActividad` (Tarea 6) se ata a este valor, no a `autenticado?.csrfToken` en vivo: el
  // token CSRF es de sesión (constante mientras dure la sesión de PHP), así que un cambio de
  // proyecto no debe destruir y recrear el controlador solo por el hueco de carga entre
  // `setArranque(null)` y la respuesta siguiente — perdería el timer de expiración a mitad de
  // camino y, peor, un logout disparado durante ese hueco no encontraría instancia con la que
  // mandar el POST.
  const [csrfTokenAutenticado, setCsrfTokenAutenticado] = useState<string | null>(null);
  const generacionRef = useRef(0);
  const controladorRef = useRef<AbortController | null>(null);

  const recargar = useCallback(async () => {
    controladorRef.current?.abort();
    const controlador = new AbortController();
    controladorRef.current = controlador;

    const generacionDeEstaLlamada = generacionRef.current + 1;
    generacionRef.current = generacionDeEstaLlamada;
    setGeneracion(generacionDeEstaLlamada);
    setSeñal(controlador.signal);

    setCargando(true);
    setError(null);
    setArranque(null);

    const esGeneracionVigente = () => generacionRef.current === generacionDeEstaLlamada;

    try {
      const resultado = await pedir('/api/session', EsquemaArranque, { signal: controlador.signal });
      if (!esGeneracionVigente()) return; // respuesta tardía de una generación ya descartada
      setArranque(resultado);
      setCsrfTokenAutenticado(resultado.state === 'authenticated' ? resultado.csrfToken : null);
    } catch (causa) {
      if (!esGeneracionVigente()) return;
      if (causa instanceof ApiError && causa.tipo === 'abortado') return; // aborto propio, no es un error visible
      setError(causa instanceof ApiError
        ? causa
        : new ApiError('No se pudo consultar la sesión', { tipo: 'red', codigo: 'NETWORK_ERROR' }));
      setCsrfTokenAutenticado(null);
    } finally {
      if (esGeneracionVigente()) setCargando(false);
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

  return { estado, arranque, autenticado, error, recargar, generacion, señal, csrfTokenAutenticado };
}

const ContextoSesion = createContext<ResultadoSesion | null>(null);

/**
 * Único punto donde se consulta `/api/session` (spec T01 §6: `SesionProvider`
 * envuelve `AuthOutlet`/`ProjectPicker`/`AppShell`) y único punto donde vive
 * `ControlActividad` (Tarea 6): mientras haya `autenticado.csrfToken`, un único
 * `ControlActividad` posee actividad humana, timeout (3600s) y logout. Cualquier
 * componente que necesite sesión —`Rutas`, `AppShell`, el resto de S01–S27 más
 * adelante— vive dentro de este árbol y consume `useSesion()`, nunca invoca el
 * hook de estado ni instancia su propio `ControlActividad` por su cuenta.
 */
export function SesionProvider({ children }: { children: ReactNode }) {
  const sesion = useArranqueSesion();
  const [logoutSinConfirmar, setLogoutSinConfirmar] = useState(false);
  const { cerrarSesion } = useControlActividad(sesion.csrfTokenAutenticado, (resultado: ResultadoCierreSesion) => {
    setLogoutSinConfirmar(resultado === 'red');
    void sesion.recargar();
  });

  // Se limpia en cuanto se abandona la pantalla de error recuperable — reintentar con éxito, o
  // resolver a cualquier otro estado (login, sesión activa), deja de ser "el logout no se confirmó".
  useEffect(() => {
    if (sesion.estado !== 'error_recuperable') setLogoutSinConfirmar(false);
  }, [sesion.estado]);

  const valor: ResultadoSesion = { ...sesion, cerrarSesion, logoutSinConfirmar };

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
