import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ApiError } from '../../lib/api/cliente';
import { listarProyectos, seleccionarProyecto } from '../../lib/api/proyectos';
import type { ArranqueAutenticado } from '../../lib/api/esquemas/arranque';
import type { ListaProyectos, ProyectoDisponible } from '../../lib/api/esquemas/proyectos';
import { filtrarProyectos, normalizarBusqueda, textoConteo } from './filtrarProyectos';
import { idBotonSeleccion, TarjetaProyecto } from './TarjetaProyecto';

/**
 * Lo único de la sesión que este componente necesita: el CSRF para la Tarea 6 (selección real) y
 * el proyecto activo para marcar "Proyecto actual" en su tarjeta. No es el tipo `ArranqueAutenticado`
 * completo a propósito — nada aquí depende de `capabilities`, `navigation` de sesión ni `week`.
 */
export type SesionSelectorProyectos = Pick<ArranqueAutenticado, 'csrfToken' | 'project'>;

type PropiedadesSelectorProyectos = {
  session: SesionSelectorProyectos;
  onOpen: (route: string) => void;
  onRevalidate: () => Promise<void>;
  /**
   * Tarea 7, S04: avisa a quien compone esta pantalla (`RutaProyectos`, en `rutas.tsx`) en cuanto
   * el manifiesto de navegación llegó, para que el rail (`BarraLateral` +
   * `navegacionSelectorProyectos`) sepa si debe agregar "Control Tower - Informes". No se levanta
   * el fetch a un nivel más arriba para no duplicar el `AbortController`/candado de esta pantalla.
   */
  onNavigation?: (navigation: ListaProyectos['navigation']) => void;
};

const MENSAJE_ERROR_CARGA = 'No pudimos cargar tus proyectos. Intenta de nuevo.';

/**
 * Un solo copy para todo rechazo de selección. El servidor ya responde siempre el mismo literal
 * (`ProjectApiController::REJECTION_MESSAGE`) justo para que la respuesta no sirva de oráculo de
 * acceso: aquí se traduce a lenguaje de producto sin añadir ninguna pista de si el proyecto no
 * existe, está inactivo o simplemente no es tuyo.
 */
const MENSAJE_RECHAZO_UI = 'No pudimos abrir ese proyecto. Verifica tu acceso e inténtalo de nuevo.';
const MENSAJE_CSRF = 'Tu sesión de seguridad cambió. Actualízala antes de volver a intentar.';
/** Mismo literal que `PantallaRestablecerClave` (S03): un evento, un copy. */
const MENSAJE_REVALIDAR_FALLIDO = 'No pudimos actualizar la sesión. Intenta nuevamente.';
/** 422, 5xx, red y contrato roto comparten copy: ninguno permite afirmar que el proyecto se abrió. */
const MENSAJE_NO_CONFIRMADO = 'No pudimos confirmar si el proyecto se abrió. Inténtalo nuevamente.';

type FocoPendiente = 'origen' | 'revalidar' | null;

function esApiErrorHttp(causa: unknown, status: number): causa is ApiError {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === status;
}

/**
 * Pantalla de lectura del selector de proyectos (Tarea 5, S04): carga por el gateway
 * (`listarProyectos`), filtra por nombre (`filtrarProyectos`) y controla los estados de
 * carga/vacío/sin-resultados/error. Reproduce la estructura observable de
 * `views/core/project_selector.view.php` (encabezado, buscador con `role="status"`
 * `aria-live="polite"`, grilla de tarjetas, vacío y sin-resultados) con las clases `aia-*`
 * existentes; el CSS tokenizado de `project-selector-react__*` llega en la Tarea 8.
 *
 * La Tarea 6 conectó la selección real: un POST por clic (`seleccionarProyecto`), controles
 * bloqueados mientras vuela, sin reintentos automáticos, y una única salida de éxito —
 * `onOpen(result.route)` con **el `route` que devolvió el servidor**, nunca una ruta calculada
 * aquí. Ese valor llegó validado por `EsquemaRutaInterna` en el gateway, así que el shell solo
 * navega a paths internos que el contrato aceptó.
 *
 * Las ramas de recuperación, todas sin reenviar la mutación:
 *
 * - `success:false` (rechazo del servidor) no es excepción: conserva lista y filtro, muestra el
 *   copy único de `MENSAJE_RECHAZO_UI` y al cerrar el aviso devuelve el foco al botón de origen.
 * - **401** revalida la sesión (`onRevalidate`) y oculta la lista operativa mientras tanto: con la
 *   sesión caída, ofrecer tarjetas seleccionables solo produce más 401.
 * - **403** (CSRF vencido) NO revalida solo: muestra «Actualizar sesión» y espera el clic, igual
 *   que S02/S03 — reautenticar y reenviar en el mismo gesto convertiría un token vencido en una
 *   mutación que el usuario nunca confirmó.
 * - 422, 5xx, red y contrato roto comparten copy seguro y dejan el reintento en manos del usuario.
 *
 * El foco de recuperación va por estado (`focoPendiente` + `useEffect`), no por
 * `requestAnimationFrame`: bajo jsdom el rAF dispara fuera del `act()` de Testing Library y la
 * aserción de foco correría antes de que el foco exista. Es el patrón de
 * `PantallaRestablecerClave` (S03).
 *
 * El candado de concurrencia es ref + estado: `seleccionandoId` pinta la UI, pero quien impide el
 * segundo POST es `enviandoRef`, que no espera al re-render de React. El `disabled` del botón es
 * presentación, no el candado: entre el clic y el repintado sigue habilitado.
 *
 * **Corrección de revisión final (S04, T10):** este párrafo citaba `useSelectorProyecto.ts` y
 * `PanelCambiarProyecto` como la alternativa no absorbida — ninguno de los dos existe ya en el
 * árbol. Este componente sigue con su propio fetch (`AbortController`) y su propio POST porque es
 * el único selector de proyectos que queda: la pantalla standalone `/proyectos`/`/app/proyectos`
 * (`RutaProyectos` en `rutas.tsx`), que navega con `onOpen(route)` (documento completo, al destino
 * que decidió el servidor) y distingue 401 de 403 con manejo de foco propio (ver `focoPendiente`
 * más abajo).
 */
export function SelectorProyectos({ session, onOpen, onRevalidate, onNavigation }: PropiedadesSelectorProyectos) {
  const [lista, setLista] = useState<ListaProyectos | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [query, setQuery] = useState('');
  const [intento, setIntento] = useState(0);
  const [seleccionandoId, setSeleccionandoId] = useState<number | null>(null);
  const [errorSeleccion, setErrorSeleccion] = useState<string | null>(null);
  const [sesionVencida, setSesionVencida] = useState(false);
  const [revalidando, setRevalidando] = useState(false);
  const [focoPendiente, setFocoPendiente] = useState<FocoPendiente>(null);
  const enviandoRef = useRef(false);
  /**
   * El origen se guarda por **id de proyecto**, no por nodo: la lista se desmonta (401) o se
   * refiltra mientras el POST vuela, y un `HTMLButtonElement` guardado queda desconectado. Un ref
   * a un nodo suelto convierte `focus()` en un no-op silencioso y el foco cae al `<body>`.
   */
  const origenIdRef = useRef<number | null>(null);
  const pantallaRef = useRef<HTMLElement | null>(null);
  const revalidarRef = useRef<HTMLButtonElement | null>(null);

  useEffect(() => {
    const controlador = new AbortController();
    setError(null);

    void (async () => {
      try {
        const respuesta = await listarProyectos(controlador.signal);
        setLista(respuesta);
        onNavigation?.(respuesta.navigation);
      } catch (causa) {
        if (causa instanceof ApiError && causa.tipo === 'abortado') return;
        setError(MENSAJE_ERROR_CARGA);
      }
    })();

    return () => controlador.abort();
  }, [intento]);

  const hasQuery = normalizarBusqueda(query) !== '';
  const resultados = useMemo(
    () => (lista ? filtrarProyectos(lista.projects, query) : []),
    [lista, query],
  );

  const reintentar = useCallback(() => setIntento((valor) => valor + 1), []);

  useEffect(() => {
    if (focoPendiente === null) return;

    const origenVivo =
      origenIdRef.current === null
        ? null
        : document.getElementById(idBotonSeleccion(origenIdRef.current));
    const candidatos =
      focoPendiente === 'revalidar' ? [revalidarRef.current, origenVivo] : [origenVivo];
    // Ancla de último recurso: la pantalla misma (`tabIndex={-1}`). Si la tarjeta de origen ya no
    // está —filtrada fuera o desmontada—, el foco tiene que quedar en algo anunciable, no en el
    // `<body>`, desde donde el lector de pantalla pierde el hilo.
    const destino = [...candidatos, pantallaRef.current].find((nodo) => nodo?.isConnected);

    destino?.focus();
    setFocoPendiente(null);
  }, [focoPendiente]);

  /** Revalidación de sesión: nunca reenvía el POST, solo repara la sesión. */
  async function revalidarSesion(focoSiFalla: FocoPendiente, devolverFoco: boolean) {
    // Guarda de estado, con la misma ventana que el `disabled` del botón: no es un candado real,
    // solo evita la revalidación doble más obvia. Puede permitirse porque `/api/session` es una
    // lectura idempotente; el candado que sí protege una mutación es `enviandoRef`.
    if (revalidando) return;
    setRevalidando(true);

    try {
      await onRevalidate();
      setSesionVencida(false);
      setErrorSeleccion(null);
      // El foco vuelve a la tarjeta de origen resuelta contra el DOM vivo, así que da igual si la
      // lista se desmontó por el camino (401) o nunca se ocultó (403).
      if (devolverFoco) setFocoPendiente('origen');
    } catch {
      setSesionVencida(true);
      setErrorSeleccion(MENSAJE_REVALIDAR_FALLIDO);
      setFocoPendiente(focoSiFalla);
    } finally {
      setRevalidando(false);
    }
  }

  function soltarCandado() {
    enviandoRef.current = false;
    setSeleccionandoId(null);
  }

  async function handleSelect(project: ProyectoDisponible) {
    // El ref es el candado real: `seleccionandoId` solo existe después del re-render.
    if (enviandoRef.current) return;
    enviandoRef.current = true;
    origenIdRef.current = project.id;
    setSeleccionandoId(project.id);
    setErrorSeleccion(null);
    setSesionVencida(false);

    // En el éxito el candado no se suelta: el shell ya está navegando y reactivar las tarjetas
    // solo abriría la puerta a un segundo POST contra una pantalla que se va.
    let rutaDestino: string | null = null;

    try {
      const resultado = await seleccionarProyecto(project.name, session.csrfToken);

      if (!resultado.success) {
        setErrorSeleccion(MENSAJE_RECHAZO_UI);
        return;
      }

      rutaDestino = resultado.route;
    } catch (causa) {
      if (esApiErrorHttp(causa, 401)) {
        await revalidarSesion('origen', false);
        return;
      }

      if (esApiErrorHttp(causa, 403)) {
        setSesionVencida(true);
        setErrorSeleccion(MENSAJE_CSRF);
        setFocoPendiente('revalidar');
        return;
      }

      setErrorSeleccion(MENSAJE_NO_CONFIRMADO);
    } finally {
      if (rutaDestino === null) soltarCandado();
    }

    // Sin ruta no hubo éxito: el `catch` genérico (422, 5xx, red, contrato) llega hasta aquí
    // porque no retorna, y sin esta guarda navegaría con `null`.
    if (rutaDestino === null) return;

    // `onOpen` va FUERA del try: si se clasificara como fallo del POST, una excepción del shell
    // se leería como error de red y dejaría la pantalla bloqueada sin más salida que recargar.
    try {
      onOpen(rutaDestino);
    } catch {
      soltarCandado();
      setErrorSeleccion(MENSAJE_NO_CONFIRMADO);
      setFocoPendiente('origen');
    }
  }

  function cerrarAviso() {
    setErrorSeleccion(null);
    setSesionVencida(false);
    setFocoPendiente('origen');
  }

  return (
    <main
      ref={pantallaRef}
      id="main-content"
      className="project-selector-react"
      data-testid="selector-proyectos"
      tabIndex={-1}
    >
      <header className="project-selector-react__header">
        <div className="project-selector-react__header-text">
          <h1>Tus proyectos</h1>
          <p>Selecciona el proyecto en el que quieres trabajar.</p>
        </div>
        {lista?.navigation.bi.visible && (
          <a className="aia-btn aia-btn--secondary" href={lista.navigation.bi.href}>
            Control Tower
          </a>
        )}
      </header>

      {error && (
        <p role="alert" className="aia-alert aia-alert--error">
          {error}{' '}
          <button type="button" className="aia-btn aia-btn--secondary" onClick={reintentar}>
            Reintentar
          </button>
        </p>
      )}

      {errorSeleccion !== null && (
        <p role="alert" className="aia-alert aia-alert--error project-selector-react__selection-alert">
          {errorSeleccion}{' '}
          {sesionVencida && (
            <button
              ref={revalidarRef}
              type="button"
              className="aia-btn aia-btn--secondary"
              disabled={revalidando}
              onClick={() => void revalidarSesion('revalidar', true)}
            >
              {revalidando ? 'Actualizando…' : 'Actualizar sesión'}
            </button>
          )}{' '}
          <button type="button" className="aia-btn aia-btn--secondary" onClick={cerrarAviso}>
            Cerrar aviso
          </button>
        </p>
      )}

      {revalidando && !sesionVencida ? (
        <p role="status" className="project-selector-react__loading">
          Actualizando tu sesión…
        </p>
      ) : lista === null ? (
        error === null && (
          <p role="status" className="project-selector-react__loading">
            Cargando proyectos…
          </p>
        )
      ) : lista.projects.length === 0 ? (
        <div className="aia-empty project-selector-react__empty">
          <h2 className="aia-title">No tienes proyectos asignados</h2>
          <p>Contacta al administrador para solicitar acceso.</p>
        </div>
      ) : (
        <>
          <div role="search" className="project-selector-react__search">
            <label htmlFor="project-search">Buscar proyecto</label>
            <input
              id="project-search"
              type="search"
              value={query}
              placeholder="Buscar proyecto..."
              autoComplete="off"
              aria-controls="project-list"
              aria-describedby="project-search-status"
              onChange={(event) => setQuery(event.currentTarget.value)}
            />
            {hasQuery && resultados.length > 0 && (
              <button type="button" onClick={() => setQuery('')}>
                Limpiar búsqueda
              </button>
            )}
          </div>

          <p id="project-search-status" role="status" aria-live="polite">
            {textoConteo(resultados.length, hasQuery)}
          </p>

          {/*
            `id="project-list"` vive en este contenedor estable, no en el `<ul>` de abajo: el
            `aria-controls="project-list"` del buscador debe apuntar a algo que exista siempre,
            incluida la rama sin resultados (que no pinta ningún `<ul>`).
          */}
          <div id="project-list">
            {resultados.length === 0 ? (
              <div className="aia-empty project-selector-react__empty">
                <h2 className="aia-title">No encontramos proyectos</h2>
                <p>Prueba con otro término de búsqueda.</p>
                <button type="button" onClick={() => setQuery('')}>
                  Limpiar búsqueda
                </button>
              </div>
            ) : (
              <ul className="project-selector-react__list">
                {resultados.map((project) => (
                  <TarjetaProyecto
                    key={project.id}
                    project={project}
                    current={session.project?.id === project.id}
                    busy={seleccionandoId === project.id}
                    disabled={seleccionandoId !== null}
                    onSelect={(elegido) => void handleSelect(elegido)}
                  />
                ))}
              </ul>
            )}
          </div>
        </>
      )}
    </main>
  );
}
