import { useCallback, useEffect, useRef, useState } from 'react';
import { ApiError } from '../../../lib/api/cliente';
import { marcarLeida as marcarLeidaGateway, obtenerNoLeidas, type Notificacion } from '../api/notificaciones';

/**
 * Único hook de la bandeja de notificaciones (T02-AC-137..152, D-T02-13 "un solo ciclo de
 * actualización"). No es un Context/Provider a propósito — a diferencia de `LpsDrawerProvider`,
 * la bandeja no necesita compartirse entre módulos distantes: `BandejaNotificaciones` es el único
 * consumidor previsto (spec §Arquitectura frontend), así que un hook de instancia basta.
 *
 * D-T02-12: la bandeja es de identidad, no de proyecto — cambiar de proyecto NO la reinicia (el
 * hook no depende de `generacionSesion`). Cerrar sesión sí la vacía, pero eso lo logra el
 * desmontaje natural de `BandejaNotificaciones` cuando el árbol deja de estar autenticado, no una
 * dependencia explícita aquí.
 */

const INTERVALO_MS = 120_000;

export type EstadoNotificaciones =
  | { status: 'cargando' }
  | { status: 'lista'; notificaciones: readonly Notificacion[]; actualizando: boolean }
  | { status: 'error'; notificacionesPrevias: readonly Notificacion[] | null; error: ApiError };

export interface UseNotificacionesApi {
  estado: EstadoNotificaciones;
  /** AC-150: reintento manual tras un error (también sirve para refrescar a mano). */
  reintentar: () => void;
  /** AC-151: sólo retira el ítem de la lista después de que el servidor confirme éxito. */
  marcarLeida: (id: number) => Promise<void>;
}

/**
 * @param csrfToken Token `shell_api` de `useSesion().autenticado?.csrfToken` — el consumidor sólo
 * monta este hook mientras haya sesión autenticada (mismo patrón que `LpsDrawerProvider`).
 */
export function useNotificaciones(csrfToken: string): UseNotificacionesApi {
  const [estado, setEstado] = useState<EstadoNotificaciones>({ status: 'cargando' });
  const controladorRef = useRef<AbortController | null>(null);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const cancelarVigente = useCallback(() => {
    controladorRef.current?.abort();
    controladorRef.current = null;
  }, []);

  const cargar = useCallback((esActualizacionDeFondo: boolean) => {
    cancelarVigente();
    const controlador = new AbortController();
    controladorRef.current = controlador;

    setEstado((previo) => {
      if (esActualizacionDeFondo && previo.status === 'lista') {
        return { ...previo, actualizando: true };
      }
      return { status: 'cargando' };
    });

    // AC-148: el polling nunca prolonga artificialmente el timeout de sesión.
    obtenerNoLeidas({ signal: controlador.signal, headers: { 'X-AIA-Idle-Refresh': '0' } })
      .then((respuesta) => {
        if (controladorRef.current !== controlador) return; // respuesta tardía descartada
        setEstado({ status: 'lista', notificaciones: respuesta.data, actualizando: false });
      })
      .catch((causa: unknown) => {
        if (controladorRef.current !== controlador) return;
        if (causa instanceof ApiError && causa.tipo === 'abortado') return; // aborto propio (hide/unmount), no un error visible
        const error = causa instanceof ApiError
          ? causa
          : new ApiError('No se pudieron cargar las notificaciones', { tipo: 'red', codigo: 'NETWORK_ERROR' });
        // AC-150: un error conserva la última bandeja conocida, marcada como desactualizada.
        setEstado((previo) => ({
          status: 'error',
          notificacionesPrevias:
            previo.status === 'lista' ? previo.notificaciones
              : previo.status === 'error' ? previo.notificacionesPrevias
                : null,
          error,
        }));
      });
  }, [cancelarVigente]);

  const reintentar = useCallback(() => cargar(false), [cargar]);

  const marcarLeida = useCallback(async (id: number) => {
    await marcarLeidaGateway(id, csrfToken);
    // AC-151: sólo se retira aquí, tras éxito confirmado — nunca antes (sin optimistic fake,
    // D-T02-09 aplica igual que en LpsDrawerProvider).
    setEstado((previo) => {
      if (previo.status !== 'lista') return previo;
      return { ...previo, notificaciones: previo.notificaciones.filter((n) => n.id !== id) };
    });
  }, [csrfToken]);

  // AC-147: carga al entrar. Se cancela al desmontar (AC-149).
  useEffect(() => {
    cargar(false);
    return cancelarVigente;
    // eslint-disable-next-line react-hooks/exhaustive-deps -- una sola carga al montar; `cargar` es estable.
  }, []);

  // D-T02-13/AC-147/148/149: un solo timer de 120s, vivo sólo mientras la pestaña está visible.
  useEffect(() => {
    function limpiarTimer() {
      if (timerRef.current !== null) {
        clearInterval(timerRef.current);
        timerRef.current = null;
      }
    }

    function armarTimer() {
      limpiarTimer();
      timerRef.current = setInterval(() => cargar(true), INTERVALO_MS);
    }

    function alCambiarVisibilidad() {
      if (document.hidden) {
        // AC-149: ocultar la pestaña aborta el request en curso y el timer — no sólo lo pausa.
        limpiarTimer();
        cancelarVigente();
        setEstado((previo) => (previo.status === 'lista' ? { ...previo, actualizando: false } : previo));
      } else {
        armarTimer();
      }
    }

    if (!document.hidden) armarTimer();
    document.addEventListener('visibilitychange', alCambiarVisibilidad);

    return () => {
      limpiarTimer();
      document.removeEventListener('visibilitychange', alCambiarVisibilidad);
    };
  }, [cargar, cancelarVigente]);

  return { estado, reintentar, marcarLeida };
}
