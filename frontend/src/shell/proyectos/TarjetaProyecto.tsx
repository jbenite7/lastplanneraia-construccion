import type { ProyectoDisponible } from '../../lib/api/esquemas/proyectos';

type PropiedadesTarjetaProyecto = {
  project: ProyectoDisponible;
  current: boolean;
  busy: boolean;
  disabled: boolean;
  onSelect: (project: ProyectoDisponible) => void;
};

const ETIQUETAS_AREA: Record<ProyectoDisponible['area'], string> = {
  Construccion: 'Construcción',
  'Pre-Construccion': 'Pre-Construcción',
};

/**
 * Tarjeta de un proyecto disponible (Tarea 5, S04). Presentación pura: no conoce transporte ni
 * CSRF, solo pinta `project` y delega el clic en `onSelect`. Reproduce la metadata de
 * `views/core/project_selector.view.php` (área, estado, rol, botón de ingreso) con las clases
 * `aia-*` existentes; el marcado BEM (`project-selector-react__*`) solo aporta ganchos de layout,
 * el CSS tokenizado llega en la Tarea 8.
 *
 * `busy`/`disabled` los gobierna el contenedor durante una selección en curso (Tarea 6): `busy`
 * marca la tarjeta que se está abriendo —cambia su nombre accesible a «Abriendo …» y pone
 * `aria-busy`— y `disabled` bloquea el resto de las tarjetas para que un clic no dispare un
 * segundo POST.
 */
/**
 * Id estable del botón de ingreso. El contenedor recupera el foco buscando este id en el DOM vivo,
 * no guardando el nodo: la lista se desmonta o se refiltra mientras un POST vuela, y un nodo
 * guardado queda desconectado, con lo que `focus()` se vuelve un no-op y el foco cae al `<body>`.
 */
export function idBotonSeleccion(projectId: number): string {
  return `project-select-${projectId}`;
}

export function TarjetaProyecto({ project, current, busy, disabled, onSelect }: PropiedadesTarjetaProyecto) {
  const tituloId = `project-title-${project.id}`;

  return (
    <li className="project-selector-react__item">
      <article className="aia-card project-selector-react__card" aria-labelledby={tituloId}>
        <div className="project-selector-react__card-header">
          <h2 id={tituloId} className="project-selector-react__card-title">
            {project.name}
          </h2>
          <div className="project-selector-react__card-badges">
            <span className="aia-chip">{ETIQUETAS_AREA[project.area]}</span>
            {/* `active` es `z.literal(true)` en el contrato (`/api/proyectos` solo entrega
                proyectos activos, Tarea 4): no hay una rama "Inactivo" que mostrar aquí. */}
            <span className="aia-chip aia-chip--success">Activo</span>
          </div>
        </div>

        <p className="project-selector-react__card-role">
          Rol: <b>{project.roleLabel}</b>
        </p>

        {current && <p className="project-selector-react__card-current">Proyecto actual</p>}

        <button
          id={idBotonSeleccion(project.id)}
          type="button"
          className="aia-btn aia-btn--block"
          disabled={disabled}
          aria-busy={busy}
          onClick={() => onSelect(project)}
        >
          {busy ? `Abriendo ${project.name}…` : `Ingresar al proyecto ${project.name}`}
        </button>
      </article>
    </li>
  );
}
