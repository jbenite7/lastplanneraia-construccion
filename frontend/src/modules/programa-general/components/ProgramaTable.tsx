import React from 'react';
import { ActividadUI } from '../domain/modelo';
import { obtenerConfigEstado } from '../domain/presentacionEstados';

export interface ProgramaTableProps {
  actividades: ActividadUI[];
  actividadSeleccionadaId: number | null;
  onSelectActividad: (id: number) => void;
  modo13Cols?: boolean;
  className?: string;
}

export function formatearRestricciones(val?: string | number | null): string {
  if (val === null || val === undefined || val === '') return '-';
  const str = String(val).trim();
  if (str.endsWith('%')) return str;
  const num = Number(str);
  if (!isNaN(num)) {
    const pct = num <= 1 ? Math.round(num * 100) : Math.round(num);
    return `${pct}%`;
  }
  return str;
}

export const ProgramaTable: React.FC<ProgramaTableProps> = ({
  actividades,
  actividadSeleccionadaId,
  onSelectActividad,
  modo13Cols = false,
  className = '',
}) => {
  return (
    <div
      className={`table-wrapper-pro table-viewport ${className}`.trim()}
      role="region"
      aria-label="Cronograma de Actividades"
    >
      <table className="programa-table-pro lps-dense-table" aria-label="Cronograma de Actividades">
        <thead>
          {modo13Cols ? (
            <tr>
              <th style={{ width: '40px', textAlign: 'center' }}>ID</th>
              <th style={{ width: '65px' }}>CÓDIGO</th>
              <th>ACTIVIDAD</th>
              <th style={{ width: '45px', textAlign: 'center' }}>RC</th>
              <th style={{ width: '85px' }}>F. INICIO</th>
              <th style={{ width: '80px', textAlign: 'center' }}>SEM. INICIO</th>
              <th style={{ width: '85px' }}>F. FIN</th>
              <th style={{ width: '95px', textAlign: 'right' }}>CANTIDAD PPTO</th>
              <th style={{ width: '50px', textAlign: 'center' }}>UNIDAD</th>
              <th style={{ width: '80px', textAlign: 'right' }}>AVANCE REAL</th>
              <th style={{ width: '80px', textAlign: 'right' }}>AVANCE TEÓR</th>
              <th style={{ width: '95px', textAlign: 'right' }}>LIB. RESTRICCIONES</th>
              <th style={{ width: '100px', textAlign: 'center' }}>ESTADO</th>
            </tr>
          ) : (
            <tr>
              <th style={{ width: '42px', textAlign: 'center' }}>ID</th>
              <th style={{ width: '68px' }}>CÓDIGO</th>
              <th>ACTIVIDAD</th>
              <th style={{ width: '85px' }}>F. INICIO</th>
              <th style={{ width: '85px' }}>F. FIN</th>
              <th style={{ width: '95px', textAlign: 'right' }}>PPTO TOTAL</th>
              <th style={{ width: '150px' }}>AVANCE (REAL / TEÓR)</th>
              <th style={{ width: '100px', textAlign: 'center' }}>ESTADO</th>
            </tr>
          )}
        </thead>
        <tbody>
          {actividades.map((act) => {
            if (act.esCapitulo) {
              return (
                <tr key={`cap-${act.unique_id}`} className="row-chapter chapter-heading-row">
                  <td colSpan={modo13Cols ? 13 : 8}>
                    <div className="chapter-cell-content">
                      <span className="chapter-icon">
                        <i className="far fa-folder" aria-hidden="true"></i>
                      </span>
                      <strong className="chapter-title">{act.Actividad}</strong>
                      <span className="chapter-badge">Capítulo</span>
                    </div>
                  </td>
                </tr>
              );
            }

            const isSelected = actividadSeleccionadaId === act.unique_id;
            const estadoCfg = obtenerConfigEstado(act.Estado);
            const pptoStr =
              act.cantidad_ppto !== null && act.cantidad_ppto !== undefined
                ? `${act.cantidad_ppto.toFixed(1)} ${act.unidad ?? ''}`.trim()
                : '-';

            const cantPptoStr =
              act.cantidad_ppto !== null && act.cantidad_ppto !== undefined
                ? act.cantidad_ppto.toFixed(1)
                : '-';

            const handleKeyDown = (e: React.KeyboardEvent<HTMLTableRowElement>) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                onSelectActividad(act.unique_id);
              }
            };

            return (
              <tr
                key={act.unique_id}
                className={`row-activity data-row ${isSelected ? 'active-editing' : ''}`.trim()}
                onClick={() => onSelectActividad(act.unique_id)}
                tabIndex={0}
                role="row"
                aria-selected={isSelected}
                onKeyDown={handleKeyDown}
              >
                {modo13Cols ? (
                  <>
                    <td style={{ textAlign: 'center', color: 'var(--ds-text-muted)' }}>
                      {act.unique_id}
                    </td>
                    <td>
                      <code className="cell-code">{act.codigo_actividad || '-'}</code>
                    </td>
                    <td>
                      <div className="activity-cell-name cell-activity">
                        <span className="activity-title">{act.Actividad}</span>
                      </div>
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      {act.esRutaCritica ? (
                        <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
                          RC
                        </span>
                      ) : (
                        <span style={{ color: 'var(--ds-text-muted)' }}>No</span>
                      )}
                    </td>
                    <td className="cell-date">{act.Fecha_Inicio ?? '-'}</td>
                    <td className="cell-date" style={{ textAlign: 'center' }}>
                      {act.Semanas_Inicio !== null && act.Semanas_Inicio !== undefined
                        ? `Sem ${act.Semanas_Inicio}`
                        : '-'}
                    </td>
                    <td className="cell-date">
                      <span>{act.Fecha_Fin ?? '-'}</span>
                      {act.plazoVencido && (
                        <span
                          className="cell-alert cell-date-overdue"
                          title={`Plazo vencido hace ${act.diasVencimiento} días`}
                        >
                          {' '}⚠️
                        </span>
                      )}
                    </td>
                    <td className="cell-ppto" style={{ textAlign: 'right' }}>
                      {cantPptoStr}
                    </td>
                    <td style={{ textAlign: 'center', color: 'var(--ds-text-muted)' }}>
                      {act.unidad || '-'}
                    </td>
                    <td style={{ textAlign: 'right' }}>
                      <strong className="avance-real">{act.avanceRealPct.toFixed(1)}%</strong>
                    </td>
                    <td style={{ textAlign: 'right', color: 'var(--ds-text-muted)' }}>
                      {act.avanceTeoricoPct.toFixed(1)}%
                    </td>
                    <td style={{ textAlign: 'right' }}>
                      {formatearRestricciones(act.Estado_Restricciones)}
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <span className={`status-pill status-cell-badge ${estadoCfg.claseChip}`}>
                        <span
                          className="status-dot chip-dot"
                          style={{ backgroundColor: estadoCfg.colorDot }}
                        ></span>
                        <span>{estadoCfg.texto}</span>
                      </span>
                    </td>
                  </>
                ) : (
                  <>
                    <td style={{ textAlign: 'center', color: 'var(--ds-text-muted)' }}>
                      {act.unique_id}
                    </td>
                    <td>
                      <code className="cell-code">{act.codigo_actividad || '-'}</code>
                    </td>
                    <td>
                      <div className="activity-cell-name cell-activity">
                        <span className="activity-title">{act.Actividad}</span>
                        {act.esRutaCritica && (
                          <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
                            RC
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="cell-date">{act.Fecha_Inicio ?? '-'}</td>
                    <td className="cell-date">
                      <span>{act.Fecha_Fin ?? '-'}</span>
                      {act.plazoVencido && (
                        <span
                          className="cell-alert cell-date-overdue"
                          title={`Plazo vencido hace ${act.diasVencimiento} días`}
                        >
                          {' '}⚠️
                        </span>
                      )}
                    </td>
                    <td className="cell-ppto" style={{ textAlign: 'right' }}>
                      {pptoStr}
                    </td>
                    <td className="cell-avance">
                      <div className="cell-avance-dual">
                        <div className="avance-numbers avance-text-box">
                          <strong className="avance-real">{act.avanceRealPct.toFixed(1)}%</strong>
                          <span className="avance-teor"> / {act.avanceTeoricoPct.toFixed(1)}%</span>
                          <span
                            className={`avance-delta avance-delta-badge ${
                              act.deltaPct < 0 ? 'delta-neg' : 'delta-pos'
                            }`}
                          >
                            {act.deltaTexto}
                          </span>
                        </div>
                        <div className="micro-gauge-track">
                          <div
                            className="micro-gauge-bar micro-gauge-fill"
                            style={{
                              width: `${Math.min(Math.max(act.avanceRealPct, 0), 100)}%`,
                            }}
                          ></div>
                        </div>
                      </div>
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <span className={`status-pill status-cell-badge ${estadoCfg.claseChip}`}>
                        <span
                          className="status-dot chip-dot"
                          style={{ backgroundColor: estadoCfg.colorDot }}
                        ></span>
                        <span>{estadoCfg.texto}</span>
                      </span>
                    </td>
                  </>
                )}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};
