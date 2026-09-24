import React, { useState, useEffect, useRef, useCallback } from 'react';
import { ActividadUI, parsearTextoActividad, formatearFechaObra } from '../domain/modelo';
import { calcularDesviacionFisica } from '../domain/validacion';
import { obtenerConfigEstado } from '../domain/presentacionEstados';

export interface CatalogoProfesional {
  id: number;
  nombre: string;
  cargo?: string;
}

export interface CatalogoSubcontratista {
  id: number;
  nombre: string;
  especialidad?: string;
}

export interface CatalogosPg {
  unidades: string[];
  codigos?: string[];
  profesionales: CatalogoProfesional[];
  subcontratistas: CatalogoSubcontratista[];
}

export interface DatosGuardarActividad {
  unique_id: number;
  Fecha_Inicio: string | null;
  Fecha_Fin: string | null;
  unidad: string;
  cantidad_ppto: number | null;
  Ejecutado: number;
  EjecutadoRatio: number;
  codigo_actividad?: string | null;
  Responsable_AIA: string;
  Sub_Contratista: string;
  Observaciones?: string | null;
  [key: string]: unknown;
}

export interface ProgramaDrawerProps {
  actividad: ActividadUI;
  catalogos: CatalogosPg;
  indiceActual: number;
  totalActividades: number;
  onCerrar: () => void;
  onGuardar: (datos: DatosGuardarActividad) => void;
  onNavigateSeq: (direccion: number) => void;
}

interface RecursoLeanItem {
  id: string;
  nombre: string;
  icono: string;
  detalle: string;
  estado: 'Liberado' | 'En gestión' | 'Bloqueante';
}

export const ProgramaDrawer: React.FC<ProgramaDrawerProps> = ({
  actividad,
  catalogos,
  indiceActual,
  totalActividades,
  onCerrar,
  onGuardar,
  onNavigateSeq,
}) => {
  const [fechaInicio, setFechaInicio] = useState(actividad.Fecha_Inicio || '');
  const [fechaFin, setFechaFin] = useState(actividad.Fecha_Fin || '');
  const [unidad, setUnidad] = useState(actividad.unidad || 'm³');
  const [cantidadPpto, setCantidadPpto] = useState(actividad.cantidad_ppto?.toString() || '');
  const [avanceReal, setAvanceReal] = useState(actividad.avanceRealPct.toString());
  const [profesional, setProfesional] = useState(actividad.Responsable_AIA || '');
  const [subcontratista, setSubcontratista] = useState(actividad.Sub_Contratista || '');
  const [observaciones, setObservaciones] = useState(actividad.Observaciones || '');
  const [sosDeclarado, setSosDeclarado] = useState(actividad.alerta_crisis === 1);

  // Sincronizar estado cuando cambia la actividad seleccionada (navegación secuencial)
  useEffect(() => {
    setFechaInicio(actividad.Fecha_Inicio || '');
    setFechaFin(actividad.Fecha_Fin || '');
    setUnidad(actividad.unidad || 'm³');
    setCantidadPpto(actividad.cantidad_ppto !== null && actividad.cantidad_ppto !== undefined ? actividad.cantidad_ppto.toString() : '');
    setAvanceReal(actividad.avanceRealPct.toString());
    setProfesional(actividad.Responsable_AIA || '');
    setSubcontratista(actividad.Sub_Contratista || '');
    setObservaciones(actividad.Observaciones || '');
    setSosDeclarado(actividad.alerta_crisis === 1);
  }, [actividad]);

  // Mantener referencia al estado actual para atajos de teclado sin closures obsoletos
  const stateRef = useRef({
    fechaInicio,
    fechaFin,
    unidad,
    cantidadPpto,
    avanceReal,
    profesional,
    subcontratista,
    observaciones,
    actividad,
  });

  useEffect(() => {
    stateRef.current = {
      fechaInicio,
      fechaFin,
      unidad,
      cantidadPpto,
      avanceReal,
      profesional,
      subcontratista,
      observaciones,
      actividad,
    };
  });

  const handleSave = useCallback(() => {
    const cur = stateRef.current;
    const pptoNum = cur.cantidadPpto !== '' ? parseFloat(cur.cantidadPpto) : null;
    const realNum = cur.avanceReal !== '' ? parseFloat(cur.avanceReal) : 0;
    const ratio = realNum / 100;

    onGuardar({
      unique_id: cur.actividad.unique_id,
      Fecha_Inicio: cur.fechaInicio || null,
      Fecha_Fin: cur.fechaFin || null,
      unidad: cur.unidad,
      cantidad_ppto: cur.unidad === '%' ? null : (isNaN(pptoNum as number) ? null : pptoNum),
      Ejecutado: realNum,
      EjecutadoRatio: ratio,
      codigo_actividad: cur.actividad.codigo_actividad,
      Responsable_AIA: cur.profesional,
      Sub_Contratista: cur.subcontratista,
      Observaciones: cur.observaciones || null,
    });
  }, [onGuardar]);

  // Atajos de teclado: [, ], Esc, ⌘S / Ctrl+S
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const isFormField =
        e.target instanceof HTMLInputElement ||
        e.target instanceof HTMLTextAreaElement ||
        e.target instanceof HTMLSelectElement;

      if (e.key === 'Escape') {
        e.preventDefault();
        onCerrar();
        return;
      }

      if ((e.metaKey || e.ctrlKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        handleSave();
        return;
      }

      if (!isFormField) {
        if (e.key === '[') {
          e.preventDefault();
          onNavigateSeq(-1);
        } else if (e.key === ']') {
          e.preventDefault();
          onNavigateSeq(1);
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [onCerrar, onNavigateSeq, handleSave]);

  const realRatio = (parseFloat(avanceReal) || 0) / 100;
  const teorRatio = actividad.avanceTeoricoPct / 100;
  const pptoNum = cantidadPpto !== '' ? parseFloat(cantidadPpto) : null;
  const desviacion = calcularDesviacionFisica(realRatio, teorRatio, pptoNum, unidad);
  const estadoCfg = obtenerConfigEstado(actividad.Estado);
  const parsedAct = parsearTextoActividad(actividad.Actividad);

  // 7 Recursos Lean
  const recursosLean: RecursoLeanItem[] = [
    {
      id: 'mo',
      nombre: 'Mano de Obra',
      icono: 'fas fa-users',
      detalle: 'Cuadrilla de ejecución disponible en obra',
      estado: 'Liberado',
    },
    {
      id: 'maq',
      nombre: 'Maquinaria',
      icono: 'fas fa-tractor',
      detalle: 'Equipos y maquinaria requerida para el tajo',
      estado: actividad.alerta_crisis === 1 ? 'Bloqueante' : 'Liberado',
    },
    {
      id: 'mat',
      nombre: 'Materiales',
      icono: 'fas fa-truck-loading',
      detalle: 'Insumos verificados y disponibles en bodega',
      estado: 'Liberado',
    },
    {
      id: 'info',
      nombre: 'Información',
      icono: 'fas fa-drafting-compass',
      detalle: 'Planos estructurales y especificaciones vigentes',
      estado: 'Liberado',
    },
    {
      id: 'prev',
      nombre: 'Condiciones Previas',
      icono: 'fas fa-project-diagram',
      detalle: 'Prerrequisitos y actividades predecesoras al 100%',
      estado: 'Liberado',
    },
    {
      id: 'seg',
      nombre: 'Seguridad',
      icono: 'fas fa-hard-hat',
      detalle: 'Protocolos SST y condiciones de espacio liberadas',
      estado: 'Liberado',
    },
    {
      id: 'ext',
      nombre: 'Externos',
      icono: 'fas fa-file-contract',
      detalle: 'Permisos de obra, licencias y trámites al día',
      estado: 'Liberado',
    },
  ];

  return (
    <>
      <div className="drawer-backdrop active" onClick={onCerrar} aria-hidden="true" />
      <aside
        className="drawer-panel-pro active"
        role="dialog"
        aria-modal="true"
        aria-label="Editor Contextual LPS"
      >
        {/* Cabecera Contextual */}
        <div className="drawer-pro-header">
          <div className="drawer-pro-topline">
            <div className="drawer-breadcrumb">
              <i className="far fa-folder" aria-hidden="true"></i>
              <span>{actividad.capituloNombre}</span> › Actividad <strong>{actividad.unique_id}</strong>
            </div>
            <button
              type="button"
              className="drawer-close-btn"
              onClick={onCerrar}
              aria-label="Cerrar panel (Esc)"
            >
              <i className="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>

          <div className="drawer-title-row">
            <div className="drawer-title-group">
              <h3 className="drawer-act-title">{parsedAct.titulo}</h3>
              {parsedAct.subtitulo && (
                <span className="drawer-act-subtitle">{parsedAct.subtitulo}</span>
              )}
            </div>
            <span className="drawer-act-code cell-code">{actividad.codigo_actividad || '-'}</span>
          </div>

          <div style={{ display: 'flex', gap: '6px', alignItems: 'center', marginTop: '2px' }}>
            <span className={`status-pill status-cell-badge ${estadoCfg.claseChip}`}>
              <span
                className="status-dot chip-dot"
                style={{ backgroundColor: estadoCfg.colorDot }}
              ></span>
              <span>{estadoCfg.texto}</span>
            </span>
            {actividad.esRutaCritica && (
              <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
                RC · Ruta Crítica
              </span>
            )}
          </div>
        </div>

        {/* Navegación Secuencial */}
        <div className="drawer-seq-nav">
          <span className="seq-indicator">
            Actividad {indiceActual} de {totalActividades}
          </span>
          <div className="seq-btn-group">
            <button
              type="button"
              className="seq-btn"
              onClick={() => onNavigateSeq(-1)}
              title="Actividad Anterior (Atajo: [)"
            >
              <i className="fas fa-chevron-left" aria-hidden="true"></i> Anterior <span className="seq-key">[</span>
            </button>
            <button
              type="button"
              className="seq-btn"
              onClick={() => onNavigateSeq(1)}
              title="Actividad Siguiente (Atajo: ])"
            >
              Siguiente <span className="seq-key">]</span> <i className="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        {/* Cuerpo del Drawer con Scroll */}
        <div className="drawer-pro-body">
          {/* SECCIÓN 1: Plazos y Cronograma */}
          <div className="pro-section">
            <div className="pro-section-title">
              <span className="pro-section-title-label">
                <i className="far fa-calendar-alt" aria-hidden="true"></i>{' '}
                <span>Plazos y Cronograma</span>
              </span>
            </div>
            <div className="form-grid-2col">
              <div className="form-field-group">
                <label className="form-label" htmlFor="drawerInputInicio">
                  Fecha Inicio
                </label>
                <div className="form-input-date-wrap">
                  <input
                    id="drawerInputInicio"
                    type="date"
                    className="form-input-pro"
                    value={fechaInicio}
                    onChange={(e) => setFechaInicio(e.target.value)}
                  />
                  <span className="drawer-date-formatted form-date-hint" style={{ fontSize: '11px', color: 'var(--ds-text-muted)', marginTop: '2px', display: 'block' }}>
                    Formato obra: <strong>{formatearFechaObra(fechaInicio)}</strong>
                  </span>
                </div>
              </div>
              <div className="form-field-group">
                <label className="form-label" htmlFor="drawerInputFin">
                  Fecha Fin
                </label>
                <div className="form-input-date-wrap">
                  <input
                    id="drawerInputFin"
                    type="date"
                    className="form-input-pro"
                    value={fechaFin}
                    onChange={(e) => setFechaFin(e.target.value)}
                  />
                  <span className="drawer-date-formatted form-date-hint" style={{ fontSize: '11px', color: 'var(--ds-text-muted)', marginTop: '2px', display: 'block' }}>
                    Formato obra: <strong>{formatearFechaObra(fechaFin)}</strong>
                  </span>
                </div>
              </div>
            </div>
            {actividad.Semanas_Inicio !== undefined && actividad.Semanas_Inicio !== null && (
              <div className="drawer-schedule-meta" style={{ fontSize: '11px', color: 'var(--ds-text-muted)', marginTop: '2px' }}>
                <span>Semana contractual: <strong>Sem {actividad.Semanas_Inicio}</strong></span>
              </div>
            )}
            {actividad.plazoVencido && (
              <div
                className="drawer-alert-overdue cell-alert cell-date-overdue date-overdue"
                style={{
                  color: 'var(--ds-state-danger-text, #ef4444)',
                  fontSize: '11px',
                  marginTop: '4px',
                  fontWeight: 600,
                }}
              >
                ⚠️ Plazo vencido hace {actividad.diasVencimiento} días
              </div>
            )}
          </div>

          {/* SECCIÓN 2: Responsables & Asignaciones (Opcional en S05 · Cascada a Lookahead S07) */}
          <div className="pro-section">
            <div className="pro-section-title">
              <span className="pro-section-title-label">
                <i className="fas fa-user-hard-hat" aria-hidden="true"></i>{' '}
                <span>Responsables & Asignaciones</span>
              </span>
              <span className="badge-optional-pill">Opcional en S05</span>
            </div>
            <div className="pro-help-callout">
              <i className="fas fa-info-circle" aria-hidden="true"></i>
              <div>
                Opcional en Programa General. Si se asigna, viaja prellenado automáticamente a{' '}
                <strong>Programación Intermedia (Lookahead)</strong>, donde es obligatorio para
                comprometer la actividad.
              </div>
            </div>
            <div className="form-field-group">
              <label className="form-label" htmlFor="drawerSelectProfesional">
                Profesional AIA Responsable
              </label>
              <select
                id="drawerSelectProfesional"
                className="form-select-pro"
                value={profesional}
                onChange={(e) => setProfesional(e.target.value)}
              >
                <option value="">(Sin asignar · Definir en Lookahead)</option>
                {catalogos.profesionales.map((p) => (
                  <option key={p.id} value={p.nombre}>
                    {p.nombre}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-field-group">
              <label className="form-label" htmlFor="drawerSelectSubcontratista">
                Empresa Subcontratista
              </label>
              <select
                id="drawerSelectSubcontratista"
                className="form-select-pro"
                value={subcontratista}
                onChange={(e) => setSubcontratista(e.target.value)}
              >
                <option value="">(Sin asignar · Definir en Lookahead)</option>
                {catalogos.subcontratistas.map((s) => (
                  <option key={s.id} value={s.nombre}>
                    {s.nombre}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* SECCIÓN 3: Presupuesto y Avance Físico */}
          <div className="pro-section">
            <div className="pro-section-title">
              <span className="pro-section-title-label">
                <i className="fas fa-cubes" aria-hidden="true"></i>{' '}
                <span>Presupuesto y Avance Físico</span>
              </span>
            </div>
            <div className="form-grid-2col">
              <div className="form-field-group">
                <label className="form-label" htmlFor="drawerSelectUnidad">
                  Unidad
                </label>
                <select
                  id="drawerSelectUnidad"
                  className="form-select-pro"
                  value={unidad}
                  onChange={(e) => {
                    const nuevaUnidad = e.target.value;
                    setUnidad(nuevaUnidad);
                    if (nuevaUnidad === '%') {
                      setCantidadPpto('');
                    }
                  }}
                >
                  {catalogos.unidades.map((u) => (
                    <option key={u} value={u}>
                      {u}
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-field-group">
                <label className="form-label" htmlFor="drawerInputPpto">
                  Cantidad PPTO
                </label>
                <input
                  id="drawerInputPpto"
                  type="number"
                  className="form-input-pro"
                  value={cantidadPpto}
                  onChange={(e) => setCantidadPpto(e.target.value)}
                  disabled={unidad === '%'}
                />
              </div>
            </div>

            <div className="form-grid-2col" style={{ marginTop: '8px' }}>
              <div className="form-field-group">
                <label className="form-label">Avance Teórico Servidor</label>
                <input
                  type="text"
                  className="form-input-pro"
                  value={`${actividad.avanceTeoricoPct.toFixed(1)}%`}
                  disabled
                  style={{ opacity: 0.65, cursor: 'not-allowed' }}
                />
              </div>
              <div className="form-field-group">
                <label className="form-label" htmlFor="drawerInputReal">
                  Avance Real
                </label>
                <input
                  id="drawerInputReal"
                  type="number"
                  step="0.1"
                  min="0"
                  max={unidad === '%' ? '100' : undefined}
                  className="form-input-pro"
                  value={avanceReal}
                  onChange={(e) => setAvanceReal(e.target.value)}
                />
              </div>
            </div>

            {/* Dual Gauge con Desviación Física Δ */}
            <div className="dual-gauge-box" style={{ marginTop: '10px' }}>
              <div className="dual-gauge-header">
                <span className="gauge-metric-title">Desviación Física (Δ):</span>
                <span className={`gauge-delta-val ${desviacion.esNegativo ? 'delta-neg' : 'delta-ok delta-pos'}`}>
                  {desviacion.textoFormateado}
                </span>
              </div>
              <div className="dual-track micro-gauge-bar">
                <div
                  className="dual-fill-teor"
                  style={{ width: `${Math.min(Math.max(actividad.avanceTeoricoPct, 0), 100)}%` }}
                ></div>
                <div
                  className="dual-fill-real micro-gauge-fill"
                  style={{
                    width: `${Math.min(Math.max(parseFloat(avanceReal) || 0, 0), 100)}%`,
                    backgroundColor: desviacion.esNegativo ? 'var(--ds-state-danger-text)' : 'var(--aia-corporate)',
                  }}
                ></div>
              </div>
              <div
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  fontSize: '10px',
                  color: 'var(--ds-text-muted)',
                  marginTop: '4px',
                }}
              >
                <span>0%</span>
                <span>Meta teórica: {actividad.avanceTeoricoPct.toFixed(1)}%</span>
                <span>100%</span>
              </div>
            </div>
          </div>

          {/* SECCIÓN 4: Matriz de los 7 Recursos Lean */}
          <div className="pro-section">
            <div className="pro-section-title">
              <span className="pro-section-title-label">
                <i className="fas fa-shield-alt" aria-hidden="true"></i>{' '}
                <span>Matriz de los 7 Recursos Lean</span>
              </span>
              <span className="badge-optional-pill">
                {actividad.Estado_Restricciones ? `Liberación: ${actividad.Estado_Restricciones}` : '7 Recursos'}
              </span>
            </div>
            <div className="lean-matrix-list">
              {recursosLean.map((rec) => (
                <div key={rec.id} className="lean-resource-card">
                  <div className="lean-res-left">
                    <i
                      className={rec.icono}
                      aria-hidden="true"
                      style={{
                        fontSize: '11px',
                        color:
                          rec.estado === 'Bloqueante'
                            ? 'var(--ds-state-danger-text)'
                            : rec.estado === 'En gestión'
                            ? 'var(--ds-color-state-warning-text)'
                            : 'var(--ds-color-state-success-text)',
                      }}
                    ></i>
                    <div>
                      <div className="lean-res-name">{rec.nombre}</div>
                      <div className="lean-res-detail">{rec.detalle}</div>
                    </div>
                  </div>
                  <span
                    className={`lean-pill ${
                      rec.estado === 'Bloqueante'
                        ? 'pill-bloqueado'
                        : rec.estado === 'En gestión'
                        ? 'pill-gestion'
                        : 'pill-liberado'
                    }`}
                  >
                    {rec.estado}
                  </span>
                </div>
              ))}
            </div>
          </div>

          {/* SECCIÓN 5: Bitácora SOS */}
          <div className="pro-section">
            <div className="pro-section-title">
              <span className="pro-section-title-label">
                <i className="fas fa-comment-dots" aria-hidden="true"></i>{' '}
                <span>Bitácora SOS</span>
              </span>
              <span className="badge-optional-pill">Observaciones & SOS</span>
            </div>

            <div className="timeline-feed">
              {actividad.Observaciones ? (
                <div className="timeline-item">
                  <div className="timeline-meta">
                    <span>{actividad.Responsable_AIA || 'AIA'}</span>
                    <span>Observación guardada</span>
                  </div>
                  <div className="timeline-text">{actividad.Observaciones}</div>
                </div>
              ) : (
                <div className="timeline-item" style={{ fontStyle: 'italic', color: 'var(--ds-text-muted)' }}>
                  Sin observaciones previas registradas.
                </div>
              )}
            </div>

            <div className="form-field-group">
              <label className="form-label" htmlFor="drawerObservaciones">
                Nueva Observación Técnica
              </label>
              <textarea
                id="drawerObservaciones"
                className="form-input-pro"
                style={{ height: '54px', padding: '6px', resize: 'none' }}
                placeholder="Escribir una nueva observación técnica..."
                value={observaciones}
                onChange={(e) => setObservaciones(e.target.value)}
              ></textarea>
            </div>

            <button
              type="button"
              className="btn-sos-trigger"
              onClick={() => setSosDeclarado(true)}
            >
              <i className="fas fa-bell" aria-hidden="true"></i>{' '}
              {sosDeclarado || actividad.alerta_crisis === 1 ? 'Alerta SOS LPS Activa' : 'Declarar Crisis SOS LPS'}
            </button>
          </div>
        </div>

        {/* Drawer Footer Fijo */}
        <div className="drawer-pro-footer">
          <button type="button" className="btn-pro-cancel" onClick={onCerrar}>
            Descartar (Esc)
          </button>
          <button type="button" className="btn-pro-save" onClick={handleSave}>
            <i className="fas fa-check" aria-hidden="true"></i> Guardar Cambios (⌘S)
          </button>
        </div>
      </aside>
    </>
  );
};
