import { Fragment } from 'react';
import type { SemanaActiva } from '../../lib/api/esquemas/contexto';

/**
 * Menús de semana del riel (paridad con `.shell-week-flyout` de `views/partials/shell_sidebar.php`).
 * Los datos y permisos llegan ya resueltos por el servidor en `SemanaActiva`; aquí solo se pintan.
 * El CSS (`adapters/shell-sidebar.css`) los muestra por hover o foco dentro del `<li>`, con el riel
 * colapsado o desplegado.
 */
export type MenuSemanasRiel = {
  semana: SemanaActiva;
  /** Hay una mutación de semana en curso: se bloquean los ítems para no encadenar peticiones. */
  ocupado?: boolean;
  /** `destino` es la ruta del módulo elegido, o `null` si se queda en la página actual. */
  alElegir: (numero: number, destino: string | null) => void;
  alCrear: () => void;
  alEliminar: (numero: number) => void;
};

/** Módulos del riel que despliegan la lista de semanas (los mismos del legado PG/PI/PS). */
export const MODULOS_CON_SEMANAS: readonly string[] = [
  'programa-general',
  'programacion-intermedia',
  'programacion-semanal',
];

type PropiedadesFlyoutSemanas = {
  menu: MenuSemanasRiel;
  titulo: string;
  /** Ruta del módulo al que lleva elegir una semana; `null` en «Semanas del Proyecto». */
  destino: string | null;
  /** Módulo activo: solo ahí se marca la semana vigente. En «Semanas del Proyecto» siempre. */
  marcarVigente: boolean;
  gestion: boolean;
};

function IconoPapelera() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
      <path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6" />
    </svg>
  );
}

export function FlyoutSemanas({ menu, titulo, destino, marcarVigente, gestion }: PropiedadesFlyoutSemanas) {
  const { semana, ocupado = false } = menu;
  const ultima = Math.max(...semana.options.map((opcion) => opcion.number));
  const puedeElegir = semana.actions.select && !ocupado;

  return (
    <div
      className="shell-week-flyout"
      role="menu"
      aria-label={gestion ? titulo : `Semanas de ${titulo}`}
    >
      <span className="shell-week-flyout__head">{titulo}</span>

      {gestion && semana.actions.create && (
        <button
          className="shell-week-flyout__item shell-week-flyout__create"
          disabled={ocupado}
          onClick={menu.alCrear}
          role="menuitem"
          type="button"
        >
          <span>+ Nueva semana</span>
        </button>
      )}

      {semana.options.map((opcion) => {
        const boton = (
          <button
            aria-current={marcarVigente && opcion.number === semana.current ? 'true' : undefined}
            className="shell-week-flyout__item"
            disabled={!puedeElegir}
            onClick={() => menu.alElegir(opcion.number, destino)}
            role="menuitem"
            type="button"
          >
            <span>Semana {opcion.number}</span>
            <small>Del {opcion.startsOn} al {opcion.endsOn}</small>
          </button>
        );
        if (!gestion) return <Fragment key={opcion.number}>{boton}</Fragment>;
        return (
          <div className="shell-week-flyout__row" key={opcion.number}>
            {boton}
            {semana.actions.deleteLast && opcion.number === ultima && (
              <button
                aria-label={`Eliminar Semana ${opcion.number}`}
                className="shell-week-flyout__delete"
                disabled={ocupado}
                onClick={() => menu.alEliminar(opcion.number)}
                type="button"
              >
                <IconoPapelera />
              </button>
            )}
          </div>
        );
      })}
    </div>
  );
}
