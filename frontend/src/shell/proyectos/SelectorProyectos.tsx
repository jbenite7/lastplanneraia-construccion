import { useCallback, useEffect, useMemo, useState } from 'react';
import { ApiError } from '../../lib/api/cliente';
import { listarProyectos } from '../../lib/api/proyectos';
import type { ArranqueAutenticado } from '../../lib/api/esquemas/arranque';
import type { ListaProyectos, ProyectoDisponible } from '../../lib/api/esquemas/proyectos';
import { filtrarProyectos, normalizarBusqueda, textoConteo } from './filtrarProyectos';
import { TarjetaProyecto } from './TarjetaProyecto';

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
};

const MENSAJE_ERROR_CARGA = 'No pudimos cargar tus proyectos. Intenta de nuevo.';

/**
 * Pantalla de lectura del selector de proyectos (Tarea 5, S04): carga por el gateway
 * (`listarProyectos`), filtra por nombre (`filtrarProyectos`) y controla los estados de
 * carga/vacío/sin-resultados/error. Reproduce la estructura observable de
 * `views/core/project_selector.view.php` (encabezado, buscador con `role="status"`
 * `aria-live="polite"`, grilla de tarjetas, vacío y sin-resultados) con las clases `aia-*`
 * existentes; el CSS tokenizado de `project-selector-react__*` llega en la Tarea 8.
 *
 * `onOpen`/`onRevalidate` ya forman parte de la interfaz pública porque así la fija el brief de
 * esta tarea — la Tarea 6 los conecta a una selección real (POST) y a la reautenticación en 401.
 * Aquí el clic en «Ingresar al proyecto» todavía no dispara transporte: esta tarea es solo
 * lectura, filtrado y estados, tal como lo pide el encargo.
 *
 * El fetch propio (con `AbortController`) duplica al de `useSelectorProyecto.ts` en vez de
 * compartir un hook común. La Tarea 7 borra `SelectorProyecto.tsx`, pero **no** el hook ni
 * `PanelCambiarProyecto.tsx`, que lo sigue consumiendo — así que esta duplicación no se cierra
 * sola con esa tarea. Se deja así a propósito en la Tarea 5 (unificar tocaría un archivo fuera de
 * su lista de cambios y arriesgaría los tests de `SelectorProyecto`/`PanelCambiarProyecto` sin que
 * el encargo lo pidiera); queda anotada en el reporte para que una tarea posterior decida si vale
 * la pena absorberla.
 */
export function SelectorProyectos({ session, onOpen: _onOpen, onRevalidate: _onRevalidate }: PropiedadesSelectorProyectos) {
  const [lista, setLista] = useState<ListaProyectos | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [query, setQuery] = useState('');
  const [intento, setIntento] = useState(0);

  useEffect(() => {
    const controlador = new AbortController();
    setError(null);

    void (async () => {
      try {
        const respuesta = await listarProyectos(controlador.signal);
        setLista(respuesta);
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

  // La selección real (POST /api/proyectos/seleccionar), su manejo de errores 401/403/422/5xx y
  // el foco de recuperación llegan en la Tarea 6: aquí el clic todavía no dispara transporte.
  function handleSelect(_project: ProyectoDisponible) {}

  return (
    <main id="main-content" className="project-selector-react" tabIndex={-1}>
      <header className="project-selector-react__header">
        <div>
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

      {lista === null ? (
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
                    busy={false}
                    disabled={false}
                    onSelect={handleSelect}
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
