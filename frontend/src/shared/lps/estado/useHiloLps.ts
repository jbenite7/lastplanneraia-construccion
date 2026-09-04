import type { ComentarioRaiz } from '../api/esquemas';
import type { RespuestaHilo } from '../api/hilo';
import { useLpsDrawer } from './useLpsDrawer';

export interface VistaHiloLps {
  /** `true` mientras se lee por primera vez (sin contenido previo que mostrar). */
  cargandoInicial: boolean;
  /** `true` en una relectura que conserva el contenido anterior marcado como actualizándose (AC-035). */
  actualizando: boolean;
  comentarios: ComentarioRaiz[];
  acciones: RespuestaHilo['actions'] | null;
  crisisAlerta: RespuestaHilo['crisisAlert'] | null;
  /** Presente cuando la última lectura falló; el diagnóstico/restricciones no dependen de esto (AC-034). */
  error: { mensaje: string; noDisponible: boolean } | null;
  enviandoMutacion: boolean;
  errorMutacion: string | null;
}

/**
 * Proyección de sólo-lectura del hilo desde el estado del `LpsDrawerProvider` — los componentes
 * de hilo/diagnóstico consumen esta forma en vez de desarmar `EstadoCajonLps` ellos mismos.
 */
export function useHiloLps(): VistaHiloLps {
  const { estado } = useLpsDrawer();

  if (estado.status === 'closed' || estado.status === 'opening') {
    return {
      cargandoInicial: estado.status === 'opening',
      actualizando: false,
      comentarios: [],
      acciones: null,
      crisisAlerta: null,
      error: null,
      enviandoMutacion: false,
      errorMutacion: null,
    };
  }

  if (estado.status === 'loading') {
    return {
      cargandoInicial: true,
      actualizando: false,
      comentarios: [],
      acciones: null,
      crisisAlerta: null,
      error: null,
      enviandoMutacion: false,
      errorMutacion: null,
    };
  }

  if (estado.status === 'partial-error') {
    return {
      cargandoInicial: false,
      actualizando: false,
      comentarios: estado.hilo?.comments ?? [],
      acciones: estado.hilo?.actions ?? null,
      crisisAlerta: estado.hilo?.crisisAlert ?? null,
      error: { mensaje: estado.error.message, noDisponible: estado.noDisponible },
      enviandoMutacion: false,
      errorMutacion: null,
    };
  }

  return {
    cargandoInicial: false,
    actualizando: estado.status === 'refreshing',
    comentarios: estado.hilo.comments,
    acciones: estado.hilo.actions,
    crisisAlerta: estado.hilo.crisisAlert ?? null,
    error: null,
    enviandoMutacion: estado.mutacion === 'enviando',
    errorMutacion: typeof estado.mutacion === 'object' ? estado.mutacion.error : null,
  };
}
