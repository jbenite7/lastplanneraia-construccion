import React from 'react';

export interface ProgramaFiltersProps {
  busqueda: string;
  onBusquedaChange: (valor: string) => void;
  estadoFiltro?: string | null;
  onClearEstado?: () => void;
  onLimpiarEstado?: () => void;
  onClearAll?: () => void;
  onLimpiarTodo?: () => void;
  totalVisibles?: number;
  totalTotal?: number;
  avanceMacroPct?: number | null;
  deshabilitado?: boolean;
  className?: string;
}

export const ProgramaFilters: React.FC<ProgramaFiltersProps> = ({
  busqueda,
  onBusquedaChange,
  estadoFiltro = null,
  onClearEstado,
  onLimpiarEstado,
  onClearAll,
  onLimpiarTodo,
  totalVisibles,
  totalTotal,
  avanceMacroPct,
  deshabilitado = false,
  className = '',
}) => {
  const clearEstadoHandler = onLimpiarEstado || onClearEstado;
  const clearAllHandler = onLimpiarTodo || onClearAll;

  const tieneBusqueda = Boolean(busqueda && busqueda.trim().length > 0);
  const tieneEstado = Boolean(
    estadoFiltro && estadoFiltro !== 'Todos' && estadoFiltro.trim() !== ''
  );
  const hayFiltrosActivos = tieneBusqueda || tieneEstado;

  const handleClearAll = () => {
    onBusquedaChange('');
    if (clearEstadoHandler) {
      clearEstadoHandler();
    }
    if (clearAllHandler) {
      clearAllHandler();
    }
  };

  return (
    <div className={`programa-filters-section ${className}`.trim()}>
      <div className="signals-search-row">
        <div className="search-input-wrapper">
          <label htmlFor="pg-search-input" className="sr-only">
            Buscar actividad, código o responsable
          </label>
          <i className="fas fa-search search-icon" aria-hidden="true"></i>
          <input
            id="pg-search-input"
            type="text"
            className="search-input"
            value={busqueda}
            onChange={(e) => onBusquedaChange(e.target.value)}
            placeholder="Buscar actividad, código o responsable..."
            aria-label="Buscar actividad, código o responsable"
            disabled={deshabilitado}
          />
          {tieneBusqueda && (
            <button
              type="button"
              className="btn-clear-search"
              onClick={() => onBusquedaChange('')}
              aria-label="Limpiar búsqueda"
              title="Limpiar búsqueda"
              disabled={deshabilitado}
            >
              <i className="fas fa-times" aria-hidden="true"></i>
            </button>
          )}
          <span className="search-kbd" aria-hidden="true">
            ⌘K
          </span>
        </div>

        {(totalVisibles !== undefined || avanceMacroPct !== undefined) && (
          <div className="signals-stats" aria-label="Estadísticas de actividades visibles">
            {totalVisibles !== undefined && (
              <span className="stat-pill">
                Actividades visibles:{' '}
                <strong>
                  {totalVisibles}
                  {totalTotal !== undefined ? ` de ${totalTotal}` : ''}
                </strong>
              </span>
            )}
            {avanceMacroPct !== undefined && avanceMacroPct !== null && (
              <span className="stat-pill stat-avance">
                Avance macro obra: <strong>{avanceMacroPct}%</strong>
              </span>
            )}
          </div>
        )}
      </div>

      {hayFiltrosActivos && (
        <div
          className="active-filters-bar"
          role="status"
          aria-live="polite"
          aria-label="Filtros aplicados"
        >
          <span className="active-filters-label">Filtros activos:</span>

          {tieneBusqueda && (
            <span className="filter-pill-active">
              <span>Texto: &ldquo;{busqueda}&rdquo;</span>
              <button
                type="button"
                className="btn-remove-pill"
                onClick={() => onBusquedaChange('')}
                aria-label="Quitar filtro de texto"
                title="Quitar filtro de texto"
              >
                <i className="fas fa-times" aria-hidden="true"></i>
              </button>
            </span>
          )}

          {tieneEstado && (
            <span className="filter-pill-active">
              <span>Estado: {estadoFiltro}</span>
              <button
                type="button"
                className="btn-remove-pill"
                onClick={() => (clearEstadoHandler ? clearEstadoHandler() : null)}
                aria-label={`Quitar filtro de estado ${estadoFiltro}`}
                title={`Quitar filtro de estado ${estadoFiltro}`}
              >
                <i className="fas fa-times" aria-hidden="true"></i>
              </button>
            </span>
          )}

          <button
            type="button"
            className="btn-clear-all-filters"
            onClick={handleClearAll}
            aria-label="Limpiar todos los filtros"
          >
            Limpiar filtros
          </button>
        </div>
      )}
    </div>
  );
};
