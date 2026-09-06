import { createContext, useCallback, useEffect, useMemo, useReducer, useRef, type ReactNode } from 'react';
import { ApiError } from '../../../lib/api/cliente';
import type { RespuestaHilo, TargetHiloParams } from '../api/hilo';
import { agregarComentario, obtenerHilo } from '../api/hilo';
import { cerrarCrisis, registrarCrisis, type Trigger } from '../api/crisis';
import type { FilaLps } from '../dominio/campos';
import type { ConfiguracionRestricciones } from '../dominio/restricciones';

/**
 * Provider único del cajón contextual LPS (T02-AC-004..010, AC-021..035). Compone una sola vez en
 * `AppShell` (D-T02-01): los cuatro consumidores (PG/PI/PS/S25) llaman `abrir(contexto)` con un
 * `LpsActivityContext` ya resuelto por su propio adapter — nunca una fila cruda ni un setter de
 * grilla (AC-007).
 *
 * El estado de lectura del hilo vive aquí (`obtenerHilo`); las mutaciones (comentar, SOS, cerrar
 * crisis) también pasan por este provider para poder invalidar/recargar el hilo tras éxito sin que
 * cada componente reimplemente su propio refetch (D-T02-09: mutación autoritativa, sin optimistic
 * fake, React siempre recarga desde el servidor).
 */

// --- Modelo del contexto -----------------------------------------------------------------------

export interface EstadoActividadLps {
  key: string;
  label: string;
  phase: string | null;
  actions: string[];
}

export interface ContactosLps {
  telefono?: string;
  correo?: string;
}

/**
 * `LpsActivityContext` (diseño §"Modelo frontend"): lo que un adapter de módulo entrega al abrir
 * el cajón. No conserva la fila cruda de la grilla ni un setter — sólo el snapshot ya resuelto.
 */
export interface LpsActivityContext {
  target: TargetHiloParams;
  module: 'PG' | 'PI' | 'PS' | 'ESC';
  activity: {
    id: number;
    label: string;
    state: EstadoActividadLps;
    progress: { ratio: number; display: string };
    critical: boolean;
    isHeader: boolean;
  };
  restrictions: {
    config: ConfiguracionRestricciones;
    values: Record<string, unknown>;
  };
  crisis?: { alertId: number; active: boolean; level?: number };
  contacts?: ContactosLps;
  /** Texto plano ya resuelto por el adapter, para componer el mensaje SOS (`AccionesSos`). */
  subcontratista?: string;
  restriccionResumen?: string;
  /** Modo simulación (`lps_simulated_mode`, D-T02-10) resuelto por el consumidor antes de abrir. */
  simulado?: boolean;
  /** Filas ya autorizadas para el digest en memoria (T02-AC-130/131/133): nunca una grilla. */
  digestFilas?: readonly FilaLps[];
  /** Sólo se muestra si T01 entrega un href ya autorizado — el cajón nunca calcula acceso BI (AC-135/136). */
  biHref?: string | null;
  /** Fallback de retorno de foco si el disparador desaparece del DOM (AC-030). */
  retornarFocoAlternativo?: () => void;
}

type EstadoMutacion = 'inactiva' | 'enviando' | { error: string };

interface BaseAbierto {
  contexto: LpsActivityContext;
  ocultaPorFiltros: boolean;
}

export type EstadoCajonLps =
  | { status: 'closed' }
  | ({ status: 'opening' } & BaseAbierto)
  | ({ status: 'loading' } & BaseAbierto)
  | ({ status: 'empty'; hilo: RespuestaHilo; mutacion: EstadoMutacion } & BaseAbierto)
  | ({ status: 'ready'; hilo: RespuestaHilo; mutacion: EstadoMutacion } & BaseAbierto)
  | ({ status: 'refreshing'; hilo: RespuestaHilo; mutacion: EstadoMutacion } & BaseAbierto)
  | ({ status: 'partial-error'; hilo?: RespuestaHilo; error: ApiError; noDisponible: boolean } & BaseAbierto);

type Accion =
  | { tipo: 'abrir'; contexto: LpsActivityContext }
  | { tipo: 'cerrar' }
  | { tipo: 'hilo-cargando' }
  | { tipo: 'hilo-listo'; hilo: RespuestaHilo }
  | { tipo: 'hilo-error'; error: ApiError }
  | { tipo: 'marcar-oculta'; oculta: boolean }
  | { tipo: 'mutacion-enviando' }
  | { tipo: 'mutacion-error'; error: string }
  | { tipo: 'mutacion-lista' };

function reducir(estado: EstadoCajonLps, accion: Accion): EstadoCajonLps {
  if (accion.tipo === 'abrir') {
    return { status: 'opening', contexto: accion.contexto, ocultaPorFiltros: false };
  }

  if (estado.status === 'closed') return estado;

  if (accion.tipo === 'cerrar') return { status: 'closed' };

  if (accion.tipo === 'marcar-oculta') {
    return { ...estado, ocultaPorFiltros: accion.oculta } as EstadoCajonLps;
  }

  if (accion.tipo === 'hilo-cargando') {
    if (estado.status === 'opening') {
      return { status: 'loading', contexto: estado.contexto, ocultaPorFiltros: estado.ocultaPorFiltros };
    }
    if (estado.status === 'ready' || estado.status === 'empty') {
      return { ...estado, status: 'refreshing' };
    }
    if (estado.status === 'partial-error') {
      return { status: 'loading', contexto: estado.contexto, ocultaPorFiltros: estado.ocultaPorFiltros };
    }
    return estado;
  }

  if (accion.tipo === 'hilo-listo') {
    const base = {
      contexto: estado.contexto,
      ocultaPorFiltros: estado.ocultaPorFiltros,
      hilo: accion.hilo,
      mutacion: 'inactiva' as EstadoMutacion,
    };
    return accion.hilo.comments.length === 0 ? { status: 'empty', ...base } : { status: 'ready', ...base };
  }

  if (accion.tipo === 'hilo-error') {
    const hiloPrevio = estado.status === 'ready' || estado.status === 'empty' || estado.status === 'refreshing' ? estado.hilo : undefined;
    return {
      status: 'partial-error',
      contexto: estado.contexto,
      ocultaPorFiltros: estado.ocultaPorFiltros,
      hilo: hiloPrevio,
      error: accion.error,
      noDisponible: accion.error.codigo === 'LPS_TARGET_NOT_FOUND' || accion.error.codigo === 'LPS_TARGET_STALE',
    };
  }

  if (accion.tipo === 'mutacion-enviando') {
    if (estado.status === 'ready' || estado.status === 'empty' || estado.status === 'refreshing') {
      return { ...estado, mutacion: 'enviando' };
    }
    return estado;
  }

  if (accion.tipo === 'mutacion-error') {
    if (estado.status === 'ready' || estado.status === 'empty' || estado.status === 'refreshing') {
      return { ...estado, mutacion: { error: accion.error } };
    }
    return estado;
  }

  if (accion.tipo === 'mutacion-lista') {
    if (estado.status === 'ready' || estado.status === 'empty' || estado.status === 'refreshing') {
      return { ...estado, mutacion: 'inactiva' };
    }
    return estado;
  }

  return estado;
}

// --- API pública del hook ----------------------------------------------------------------------

export interface LpsDrawerApi {
  estado: EstadoCajonLps;
  /** Abre el cajón con un contexto ya resuelto por el adapter del módulo llamante (AC-007). */
  abrir: (contexto: LpsActivityContext, disparador?: HTMLElement | null) => void;
  cerrar: () => void;
  reintentar: () => void;
  marcarOcultaPorFiltros: (oculta: boolean) => void;
  comentar: (input: { comentario: string; parentId?: number; menciones?: { roles: string[] } }) => Promise<void>;
  registrarSos: (input: { trigger: Trigger }) => Promise<void>;
  cerrarCrisisAlerta: (input: { alertaId: number; justificacion: string }) => Promise<void>;
  /** Elemento que disparó la apertura vigente — `null` si desapareció del DOM (AC-029/030). */
  disparadorRef: React.MutableRefObject<HTMLElement | null>;
}

const ContextoLpsDrawer = createContext<LpsDrawerApi | null>(null);

export interface PropiedadesLpsDrawerProvider {
  children: ReactNode;
  csrfToken: string;
  /** `generacion` de `SesionProvider`: cambia con cada login/logout/cambio de proyecto (AC-021). */
  generacionSesion: number;
  /** Semana vigente del shell; sólo invalida targets de actividad, nunca targets de alerta (AC-022/023). */
  semana: number | null;
}

/**
 * Único `LpsDrawerProvider` autenticado (AC-004). Compone en `AppShell` y expone `useLpsDrawer()`
 * a cualquier módulo — el drawer/UI en sí vive en `CajonContextualLps`, montado una sola vez junto
 * al provider (AC-171).
 */
export function LpsDrawerProvider({ children, csrfToken, generacionSesion, semana }: PropiedadesLpsDrawerProvider) {
  const [estado, dispatch] = useReducer(reducir, { status: 'closed' } as EstadoCajonLps);
  const disparadorRef = useRef<HTMLElement | null>(null);
  const controladorRef = useRef<AbortController | null>(null);
  const generacionSesionAnterior = useRef(generacionSesion);
  const semanaAnterior = useRef(semana);
  const historiaEmpujada = useRef(false);

  const cancelarLecturaVigente = useCallback(() => {
    controladorRef.current?.abort();
    controladorRef.current = null;
  }, []);

  const cargarHilo = useCallback((target: TargetHiloParams) => {
    cancelarLecturaVigente();
    const controlador = new AbortController();
    controladorRef.current = controlador;
    dispatch({ tipo: 'hilo-cargando' });

    obtenerHilo(target, { signal: controlador.signal })
      .then((hilo) => {
        // AC-032: una respuesta tardía nunca sustituye el hilo del target actual — si este
        // controlador ya no es el vigente (se abortó/reemplazó), se descarta en silencio.
        if (controladorRef.current !== controlador) return;
        dispatch({ tipo: 'hilo-listo', hilo });
      })
      .catch((causa: unknown) => {
        if (controladorRef.current !== controlador) return;
        if (causa instanceof ApiError && causa.tipo === 'abortado') return;
        const error = causa instanceof ApiError ? causa : new ApiError('Fallo inesperado al leer el hilo LPS', { tipo: 'red' });
        dispatch({ tipo: 'hilo-error', error });
      });
  }, [cancelarLecturaVigente]);

  const abrir = useCallback((contexto: LpsActivityContext, disparador?: HTMLElement | null) => {
    disparadorRef.current = disparador ?? null;
    dispatch({ tipo: 'abrir', contexto });
    cargarHilo(contexto.target);
  }, [cargarHilo]);

  const cerrar = useCallback(() => {
    cancelarLecturaVigente();
    dispatch({ tipo: 'cerrar' });
  }, [cancelarLecturaVigente]);

  const reintentar = useCallback(() => {
    if (estado.status === 'partial-error') cargarHilo(estado.contexto.target);
  }, [estado, cargarHilo]);

  const marcarOcultaPorFiltros = useCallback((oculta: boolean) => {
    dispatch({ tipo: 'marcar-oculta', oculta });
  }, []);

  // AC-031: la lectura del hilo se cancela al desmontar el provider.
  useEffect(() => cancelarLecturaVigente, [cancelarLecturaVigente]);

  // AC-027: back cierra primero un drawer abierto por deep link, antes de abandonar la ruta —
  // se empuja una entrada de historial sintética al abrir; el primer `popstate` la consume y
  // cierra el cajón en vez de dejar que la navegación real ocurra.
  useEffect(() => {
    if (estado.status === 'closed') {
      historiaEmpujada.current = false;
      return;
    }
    if (!historiaEmpujada.current) {
      window.history.pushState({ lpsCajonAbierto: true }, '');
      historiaEmpujada.current = true;
    }
    function alPopstate() {
      historiaEmpujada.current = false;
      dispatch({ tipo: 'cerrar' });
    }
    window.addEventListener('popstate', alPopstate);
    return () => window.removeEventListener('popstate', alPopstate);
  }, [estado.status]);

  // AC-021: el target se limpia al cambiar sesión/proyecto (generación de `SesionProvider`).
  useEffect(() => {
    if (generacionSesionAnterior.current === generacionSesion) return;
    generacionSesionAnterior.current = generacionSesion;
    cancelarLecturaVigente();
    dispatch({ tipo: 'cerrar' });
  }, [generacionSesion, cancelarLecturaVigente]);

  // AC-022/023: la semana sólo gobierna targets de actividad (PG/PI/PS); un target de alerta (S25)
  // porta su propia semana y no se invalida por el cambio de semana del shell.
  useEffect(() => {
    if (semanaAnterior.current === semana) return;
    semanaAnterior.current = semana;
    if (estado.status === 'closed') return;
    const esTargetDeAlerta = 'alertaId' in estado.contexto.target;
    if (esTargetDeAlerta) return;
    cancelarLecturaVigente();
    dispatch({ tipo: 'cerrar' });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- `estado` sólo se lee, no se persigue.
  }, [semana, cancelarLecturaVigente]);

  const comentar = useCallback(
    async (input: { comentario: string; parentId?: number; menciones?: { roles: string[] } }) => {
      if (estado.status === 'closed' || estado.status === 'opening' || estado.status === 'loading') return;
      dispatch({ tipo: 'mutacion-enviando' });
      try {
        await agregarComentario({
          comentario: input.comentario,
          csrfToken,
          target: estado.contexto.target,
          parentId: input.parentId,
          menciones: input.menciones,
        });
        // AC-101/102: sin inserción optimista — se relee el hilo autoritativo.
        dispatch({ tipo: 'mutacion-lista' });
        cargarHilo(estado.contexto.target);
      } catch (causa) {
        // AC-103: error de comentario conserva borrador/target/hilo — el formulario mantiene su
        // propio texto, este provider sólo registra el error de mutación.
        dispatch({ tipo: 'mutacion-error', error: causa instanceof ApiError ? causa.message : 'No se pudo comentar.' });
        throw causa;
      }
    },
    [estado, csrfToken, cargarHilo],
  );

  const registrarSos = useCallback(
    async (input: { trigger: Trigger }) => {
      if (estado.status === 'closed' || estado.status === 'opening' || estado.status === 'loading') return;
      if (!('alertaId' in estado.contexto.target) && estado.contexto.module === 'ESC') return;
      dispatch({ tipo: 'mutacion-enviando' });
      try {
        await registrarCrisis({ trigger: input.trigger, csrfToken, target: estado.contexto.target });
        dispatch({ tipo: 'mutacion-lista' });
        cargarHilo(estado.contexto.target);
      } catch (causa) {
        dispatch({ tipo: 'mutacion-error', error: causa instanceof ApiError ? causa.message : 'No se pudo registrar la crisis.' });
        throw causa;
      }
    },
    [estado, csrfToken, cargarHilo],
  );

  const cerrarCrisisAlerta = useCallback(
    async (input: { alertaId: number; justificacion: string }) => {
      if (estado.status === 'closed' || estado.status === 'opening' || estado.status === 'loading') return;
      dispatch({ tipo: 'mutacion-enviando' });
      try {
        await cerrarCrisis({ alertaId: input.alertaId, justificacion: input.justificacion, csrfToken });
        // AC-127/128: éxito recarga el hilo/snapshot autoritativo; React nunca limpia banderas
        // de crisis localmente.
        dispatch({ tipo: 'mutacion-lista' });
        cargarHilo(estado.contexto.target);
      } catch (causa) {
        dispatch({ tipo: 'mutacion-error', error: causa instanceof ApiError ? causa.message : 'No se pudo cerrar la crisis.' });
        throw causa;
      }
    },
    [estado, csrfToken, cargarHilo],
  );

  const valor = useMemo<LpsDrawerApi>(
    () => ({ estado, abrir, cerrar, reintentar, marcarOcultaPorFiltros, comentar, registrarSos, cerrarCrisisAlerta, disparadorRef }),
    [estado, abrir, cerrar, reintentar, marcarOcultaPorFiltros, comentar, registrarSos, cerrarCrisisAlerta],
  );

  return <ContextoLpsDrawer.Provider value={valor}>{children}</ContextoLpsDrawer.Provider>;
}

export { ContextoLpsDrawer };
