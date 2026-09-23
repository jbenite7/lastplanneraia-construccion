import React from 'react';
import { ActividadUI } from '../domain/modelo';
import { obtenerConfigEstado } from '../domain/presentacionEstados';

export interface ProgramaCardsProps {
  actividades: ActividadUI[];
  actividadSeleccionadaId?: number | null;
  onSelectActividad: (id: number) => void;
  className?: string;
}

export const ProgramaCards: React.FC<ProgramaCardsProps> = ({
  actividades,
  actividadSeleccionadaId = null,
  onSelectActividad,
  className = '',
}) => {
  return (
    <div
      className={`cards-wrapper-mobile ${className}`.trim()}
      role="feed"
      aria-label="Tarjetas de actividades"
    >
      {actividades.map((act) => {
        if (act.esCapitulo) {
          return (
            <div key={`cap-card-${act.unique_id}`} className="card-chapter-header">
              <i className="far fa-folder" aria-hidden="true"></i> {act.Actividad}
            </div>
          );
        }

        const isSelected = actividadSeleccionadaId === act.unique_id;
        const estadoCfg = obtenerConfigEstado(act.Estado);
        const pptoStr =
          act.cantidad_ppto !== null && act.cantidad_ppto !== undefined
            ? `${act.cantidad_ppto.toFixed(1)} ${act.unidad ?? ''}`.trim()
            : '-';

        const handleKeyDown = (e: React.KeyboardEvent<HTMLElement>) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            onSelectActividad(act.unique_id);
          }
        };

        return (
          <article
            key={`card-${act.unique_id}`}
            className={`activity-mobile-card ${isSelected ? 'active-editing' : ''}`.trim()}
            onClick={() => onSelectActividad(act.unique_id)}
            tabIndex={0}
            role="button"
            aria-selected={isSelected}
            aria-label={`${act.codigo_actividad || ''} ${act.Actividad}`.trim()}
            onKeyDown={handleKeyDown}
          >
            <div className="card-topline">
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                <code className="cell-code">{act.codigo_actividad || '-'}</code>
                {act.esRutaCritica && (
                  <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
                    RC
                  </span>
                )}
              </div>
              <span className={`status-pill status-cell-badge ${estadoCfg.claseChip}`}>
                <span
                  className="status-dot chip-dot"
                  style={{ backgroundColor: estadoCfg.colorDot }}
                ></span>
                <span>{estadoCfg.texto}</span>
              </span>
            </div>
            <h3 className="card-title">
              {act.Actividad}
              {act.plazoVencido && (
                <span
                  className="cell-alert"
                  title={`Plazo vencido hace ${act.diasVencimiento} días`}
                >
                  {' '}⚠️
                </span>
              )}
            </h3>
            <div className="card-metrics">
              <div>
                Ppto: <strong>{pptoStr}</strong>
              </div>
              <div>
                Avance: <strong>{act.avanceRealPct.toFixed(1)}%</strong>{' '}
                <span style={{ color: 'var(--ds-text-muted)' }}>({act.deltaTexto})</span>
              </div>
            </div>
          </article>
        );
      })}
    </div>
  );
};
