import React from 'react';
import {
  ActividadUI,
  parsearTextoActividad,
  formatearFechaObra,
  formatearCantidadPresupuesto,
} from '../domain/modelo';
import { obtenerConfigEstado } from '../domain/presentacionEstados';

export interface ProgramaTableProps {
  actividades: ActividadUI[];
  actividadSeleccionadaId: number | null;
  onSelectActividad: (id: number) => void;
  modo13Cols?: boolean;
  className?: string;
}

const COLUMNAS_8 = ['id', 'codigo', 'actividad', 'inicio', 'fin', 'ppto', 'avance', 'estado'] as const;
const COLUMNAS_13 = [
  'id', 'codigo', 'actividad', 'rc', 'inicio', 'sem', 'fin',
  'cantidad', 'unidad', 'real', 'teorico', 'restricciones', 'estado',
] as const;

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
        <colgroup>
          {(modo13Cols ? COLUMNAS_13 : COLUMNAS_8).map((clave) => (
            <col key={clave} className={`pg-col-${clave}`} />
          ))}
        </colgroup>
        <thead>
          {modo13Cols ? (
            <tr>
              <th>ID</th>
              <th>CÓDIGO</th>
              <th>ACTIVIDAD</th>
              <th>RC</th>
              <th>F. INICIO</th>
              <th>SEM. INICIO</th>
              <th>F. FIN</th>
              <th>CANTIDAD PPTO</th>
              <th>UNIDAD</th>
              <th>AVANCE REAL</th>
              <th>AVANCE TEÓR</th>
              <th>LIB. RESTRICCIONES</th>
              <th>ESTADO</th>
            </tr>
          ) : (
            <tr>
              <th>ID</th>
              <th>CÓDIGO</th>
              <th>ACTIVIDAD</th>
              <th>F. INICIO</th>
              <th>F. FIN</th>
              <th>PPTO TOTAL</th>
              <th>AVANCE (REAL / TEÓR)</th>
              <th>ESTADO</th>
            </tr>
          )}
        </thead>
        <tbody>
          {actividades.map((act) => {
            if (act.esCapitulo) {
              const parsedCap = parsearTextoActividad(act.Actividad);
              return (
                <tr key={`cap-${act.unique_id}`} className="row-chapter chapter-heading-row">
                  <td colSpan={(modo13Cols ? COLUMNAS_13 : COLUMNAS_8).length}>
                    <div className="chapter-cell-content">
                      <span className="chapter-icon">
                        <i className="far fa-folder" aria-hidden="true"></i>
                      </span>
                      <strong className="chapter-title">{parsedCap.titulo}</strong>
                      <span className="chapter-badge chapter-badge-count">Capítulo</span>
                      {act.avanceRealPct !== undefined && act.avanceRealPct !== null && act.avanceRealPct > 0 && (
                        <div className="chapter-progress">
                          <span>Avance {act.avanceRealPct}%</span>
                          <div className="mini-progress-track">
                            <div
                              className="mini-progress-fill"
                              style={{ width: `${Math.min(Math.max(act.avanceRealPct, 0), 100)}%` }}
                            />
                          </div>
                        </div>
                      )}
                    </div>
                  </td>
                </tr>
              );
            }

            const isSelected = actividadSeleccionadaId === act.unique_id;
            const estadoCfg = obtenerConfigEstado(act.Estado);
            const parsed = parsearTextoActividad(act.Actividad);
            const pptoStr = formatearCantidadPresupuesto(act.cantidad_ppto, act.unidad);

            const cantPptoStr =
              act.cantidad_ppto !== null && act.cantidad_ppto !== undefined
                ? Number(act.cantidad_ppto).toFixed(1)
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
                data-unique-id={act.unique_id}
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
                        <div className="activity-title-group">
                          <span className="activity-title">{parsed.titulo}</span>
                          {parsed.subtitulo && (
                            <span className="activity-subtitle">{parsed.subtitulo}</span>
                          )}
                        </div>
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
                    <td className="cell-date">{formatearFechaObra(act.Fecha_Inicio)}</td>
                    <td className="cell-date" style={{ textAlign: 'center' }}>
                      {act.Semanas_Inicio !== null && act.Semanas_Inicio !== undefined
                        ? `Sem ${act.Semanas_Inicio}`
                        : '-'}
                    </td>
                    <td className="cell-date">
                      <span className={act.plazoVencido ? 'cell-date-overdue' : undefined}>
                        {formatearFechaObra(act.Fecha_Fin)}
                      </span>
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
                      <strong className="avance-real avance-real-val">{act.avanceRealPct.toFixed(1)}%</strong>
                    </td>
                    <td style={{ textAlign: 'right', color: 'var(--ds-text-muted)' }}>
                      <span className="avance-teor-val">{act.avanceTeoricoPct.toFixed(1)}%</span>
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
                        <div className="activity-title-group">
                          <span className="activity-title">{parsed.titulo}</span>
                          {parsed.subtitulo && (
                            <span className="activity-subtitle">{parsed.subtitulo}</span>
                          )}
                        </div>
                        {act.esRutaCritica && (
                          <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
                            RC
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="cell-date">{formatearFechaObra(act.Fecha_Inicio)}</td>
                    <td className="cell-date">
                      <span className={act.plazoVencido ? 'cell-date-overdue' : undefined}>
                        {formatearFechaObra(act.Fecha_Fin)}
                      </span>
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
                          <strong className="avance-real avance-real-val">{act.avanceRealPct.toFixed(1)}%</strong>
                          <span className="avance-teor avance-teor-val"> / {act.avanceTeoricoPct.toFixed(1)}%</span>
                          <span
                            className={`avance-delta avance-delta-badge ${
                              act.deltaPct < 0 ? 'delta-neg' : 'delta-ok delta-pos'
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
