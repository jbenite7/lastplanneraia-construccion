import { useSelectorProyecto } from './useSelectorProyecto';

type PropiedadesPanelCambiarProyecto = {
  alElegir: () => Promise<void>;
  csrfToken: string;
};

/**
 * Lista de proyectos dentro del panel de cuenta del rail (Tarea 4, ronda de arreglos 1 —
 * hallazgo del revisor de código). Usa el mismo `useSelectorProyecto` que la pantalla completa
 * `SelectorProyecto`, sin duplicar fetch ni CSRF, pero con marcado propio de panel angosto: sin
 * `<h1>` (el disparador del menú ya anuncia "Cuenta · {nombre}", un segundo encabezado de nivel
 * de página aquí sería un salto de jerarquía semántica) y sin `.aia-card` (ese padding/borde/
 * sombra asumen el ancho de una pantalla completa, no los `min-width`/`max-width` angostos de
 * `[data-aia-menu-panel]` en `shell-sidebar.css`). Cada proyecto es un `role="menuitem"`, como el
 * resto de las acciones del panel.
 */
export function PanelCambiarProyecto({ alElegir, csrfToken }: PropiedadesPanelCambiarProyecto) {
  const { proyectos, error, seleccionandoId, elegir } = useSelectorProyecto(alElegir, csrfToken);

  return (
    <>
      {error && (
        <p role="alert" className="aia-alert aia-alert--error">
          {error}
        </p>
      )}

      {proyectos === null ? (
        <p role="status">Cargando proyectos…</p>
      ) : proyectos.length === 0 ? (
        <p>No tienes proyectos asignados. Pídele acceso a un administrador.</p>
      ) : (
        proyectos.map((proyecto) => (
          <button
            key={proyecto.id}
            type="button"
            role="menuitem"
            className="aia-sidebar__account-item"
            disabled={seleccionandoId !== null}
            onClick={() => void elegir(proyecto)}
          >
            {seleccionandoId === proyecto.id ? 'Abriendo…' : proyecto.name}
          </button>
        ))
      )}
    </>
  );
}
