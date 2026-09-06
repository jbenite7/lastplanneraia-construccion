import { useSelectorProyecto } from './useSelectorProyecto';

type PropiedadesSelectorProyecto = {
  alElegir: () => Promise<void>;
  csrfToken: string;
};

/**
 * Pantalla completa del selector de proyecto (estado `autenticado_sin_proyecto`, spec T01 §7).
 * El fetch/POST/CSRF vive en `useSelectorProyecto` (ronda de arreglos 1 de la Tarea 4): este
 * componente solo aporta el envoltorio de página (`<h1>`, `.aia-card`) — `MenuCuenta` usa el
 * mismo hook con su propio envoltorio de panel, sin duplicar la lógica.
 */
export function SelectorProyecto({ alElegir, csrfToken }: PropiedadesSelectorProyecto) {
  const { proyectos, error, seleccionandoId, elegir } = useSelectorProyecto(alElegir, csrfToken);

  return (
    <section className="aia-card">
      <h1>Elige un proyecto</h1>

      {error && <p role="alert" className="aia-alert aia-alert--error">{error}</p>}

      {proyectos === null ? (
        <p role="status">Cargando proyectos…</p>
      ) : proyectos.length === 0 ? (
        <p>No tienes proyectos asignados. Pídele acceso a un administrador.</p>
      ) : (
        <ul>
          {proyectos.map((proyecto) => (
            <li key={proyecto.id}>
              <button
                type="button"
                className="aia-btn aia-btn--secondary"
                disabled={seleccionandoId !== null}
                onClick={() => void elegir(proyecto)}
              >
                {seleccionandoId === proyecto.id ? 'Abriendo…' : proyecto.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
