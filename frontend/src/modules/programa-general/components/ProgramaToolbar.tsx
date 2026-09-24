import React from 'react';

export interface ProgramaToolbarProps {
  semana: number;
  modo13Cols: boolean;
  onToggleColumnas: () => void;
  onOpenDrawer: () => void;
  onExportCsv: () => void;
  onDownloadCorteXlsx: () => void;
  puedeEditar?: boolean;
  puedeDescargarCorte?: boolean;
  puedeCorteXlsx?: boolean;
  deshabilitado?: boolean;
  cargando?: boolean;
  exportandoCsv?: boolean;
  generandoCorte?: boolean;
  drawerAbierto?: boolean;
  titulo?: string;
  className?: string;
}

export const ProgramaToolbar: React.FC<ProgramaToolbarProps> = ({
  semana,
  modo13Cols,
  onToggleColumnas,
  onOpenDrawer,
  onExportCsv,
  onDownloadCorteXlsx,
  puedeEditar = true,
  puedeDescargarCorte,
  puedeCorteXlsx = true,
  deshabilitado = false,
  cargando = false,
  exportandoCsv = false,
  generandoCorte = false,
  drawerAbierto = false,
  titulo = 'Programa General',
  className = '',
}) => {
  const permitirCorte =
    puedeDescargarCorte !== undefined ? puedeDescargarCorte : puedeCorteXlsx;
  const inactivo = deshabilitado || cargando;

  return (
    <header
      className={`programa-toolbar ${className}`.trim()}
      role="toolbar"
      aria-label="Barra de herramientas de Programa General"
    >
      <div className="toolbar-left">
        <h1 className="programa-title">{titulo}</h1>
        <span className="badge-live-week badge-semana">Semana {semana} Vigente</span>
      </div>

      <div className="toolbar-actions" role="group" aria-label="Acciones de Programa General">
        <div className="btn-toggle-group" role="group" aria-label="Selector de densidad de columnas">
          <button
            type="button"
            className={`btn-toggle-pill ${!modo13Cols ? 'active' : ''}`.trim()}
            onClick={onToggleColumnas}
            disabled={inactivo}
            aria-pressed={!modo13Cols}
            title="Ver 8 columnas esenciales sin scroll horizontal"
          >
            <i className="fas fa-table-cells" aria-hidden="true"></i> 8 Cols Esenciales
          </button>
          <button
            type="button"
            className={`btn-toggle-pill ${modo13Cols ? 'active' : ''}`.trim()}
            onClick={onToggleColumnas}
            disabled={inactivo}
            aria-pressed={modo13Cols}
            title="Ver 13 columnas completas del cronograma"
          >
            <i className="fas fa-table-columns" aria-hidden="true"></i> 13 Cols Reales
          </button>
        </div>

        <button
          type="button"
          className="btn-drawer-trigger"
          onClick={onOpenDrawer}
          disabled={inactivo || drawerAbierto}
          aria-expanded={drawerAbierto}
          title="Abrir Drawer Contextual LPS"
        >
          <i className="fas fa-columns" aria-hidden="true"></i> Drawer LPS
        </button>

        <button
          type="button"
          className="btn-header-action"
          onClick={onExportCsv}
          disabled={inactivo || exportandoCsv}
          aria-busy={exportandoCsv}
          title="Exportar 13 columnas completas a CSV"
        >
          <i
            className={`fas ${exportandoCsv ? 'fa-spinner fa-spin' : 'fa-file-csv'}`}
            aria-hidden="true"
          ></i>{' '}
          <span>{exportandoCsv ? 'Exportando CSV...' : 'CSV'}</span>
        </button>

        <button
          type="button"
          className="btn-header-action"
          onClick={onDownloadCorteXlsx}
          disabled={inactivo || generandoCorte || !permitirCorte}
          aria-busy={generandoCorte}
          title="Descargar Corte Oficial XLSX"
        >
          <i
            className={`fas ${generandoCorte ? 'fa-spinner fa-spin' : 'fa-file-excel'}`}
            aria-hidden="true"
          ></i>{' '}
          <span>{generandoCorte ? 'Generando Corte...' : 'Corte XLSX'}</span>
        </button>
      </div>
    </header>
  );
};
