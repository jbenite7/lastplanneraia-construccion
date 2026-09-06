import { useCallback, useEffect, useRef } from 'react';
import { ControlActividad, type RazonCierreSesion, type ResultadoCierreSesion } from './ControlActividad';

export interface ResultadoControlActividad {
  /** Cierra sesión de forma idempotente vía el único `ControlActividad` vivo. */
  cerrarSesion: () => Promise<ResultadoCierreSesion>;
}

/**
 * Instancia el único `ControlActividad` del árbol (Tarea 6, T01) mientras haya sesión autenticada
 * (`csrfToken` no nulo) y lo destruye al perderla o al desmontar `SesionProvider`. `alInvalidar` es
 * lo que reconecta el cierre con el resto del shell: siempre `recargar()` de `useArranqueSesion`,
 * la misma vía que ya usan el cambio de proyecto y el logout manual de `MenuCuenta` — así "sesión
 * cerrada" nunca tiene un segundo camino de invalidación. Recibe el `ResultadoCierreSesion` para
 * que `SesionProvider` pueda distinguir "el servidor confirmó" de "no logramos confirmarlo" (Fix
 * round 1, hallazgo de revisión de la Tarea 6: no conflacionar 403-idempotente con fallo de red).
 */
export function useControlActividad(
  csrfToken: string | null,
  alInvalidar: (resultado: ResultadoCierreSesion) => void,
): ResultadoControlActividad {
  const controlRef = useRef<ControlActividad | null>(null);
  const alInvalidarRef = useRef(alInvalidar);
  alInvalidarRef.current = alInvalidar;

  useEffect(() => {
    if (!csrfToken) {
      controlRef.current = null;
      return;
    }

    const control = new ControlActividad({
      obtenerCsrfToken: () => csrfToken,
      alCerrarSesion: (_razon, resultado) => alInvalidarRef.current(resultado),
    });
    controlRef.current = control;
    control.iniciar();

    return () => {
      control.detener();
      controlRef.current = null;
    };
  }, [csrfToken]);

  const cerrarSesion = useCallback(async (): Promise<ResultadoCierreSesion> => {
    const razon: RazonCierreSesion = 'usuario';
    if (controlRef.current) {
      return controlRef.current.cerrarSesion(razon);
    }

    // Sin instancia activa (no autenticado) no hay sesión que cerrar — invalida igual para que un
    // llamador defensivo nunca quede colgado esperando una promesa que nunca resuelve. No hubo
    // ninguna petición de red que pudiera fallar, así que es una confirmación real.
    alInvalidarRef.current('confirmado');
    return 'confirmado';
  }, []);

  return { cerrarSesion };
}
