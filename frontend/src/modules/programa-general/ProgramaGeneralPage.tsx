import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { programaGeneralApi } from './api/programaGeneralApi';
import { ContextoPg } from '../../lib/api/esquemas/programa-general';
import { ActividadUI, normalizarActividades } from './domain/modelo';
import { calcularConteosSenales, contarTareasVisibles, filtrarActividades } from './domain/filtros';
import { generarContenidoCsv13Cols, dispararDescargaCsv } from './domain/exportarCsv';
import { ProgramaToolbar } from './components/ProgramaToolbar';
import { ProgramaSignalsBar } from './components/ProgramaSignalsBar';
import { ProgramaFilters } from './components/ProgramaFilters';
import { ProgramaTable } from './components/ProgramaTable';
import { ProgramaCards } from './components/ProgramaCards';
import { ProgramaDrawer } from './components/ProgramaDrawer';
import './programa-general.css';

export const ProgramaGeneralPage: React.FC = () => {
  const [contexto, setContexto] = useState<ContextoPg | null>(null);
  const [actividades, setActividades] = useState<ActividadUI[]>([]);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [estadoFiltro, setEstadoFiltro] = useState<string | null>(null);
  const [busqueda, setBusqueda] = useState('');
  const [modo13Cols, setModo13Cols] = useState(false);
  const [actividadSeleccionadaId, setActividadSeleccionadaId] = useState<number | null>(null);
  const [toastMsg, setToastMsg] = useState<string | null>(null);
  const [exportandoCsv, setExportandoCsv] = useState(false);
  const [generandoCorte, setGenerandoCorte] = useState(false);

  const api = useMemo(() => programaGeneralApi(), []);

  useEffect(() => {
    document.body.classList.add('pg-page');
    return () => {
      document.body.classList.remove('pg-page');
    };
  }, []);

  useEffect(() => {
    const token = contexto?.csrf_shell || contexto?.csrf_token;
    if (!token) return;
    let meta = document.querySelector('meta[name="lps-shell-csrf-token"]') as HTMLMetaElement | null;
    let created = false;
    if (!meta) {
      meta = document.createElement('meta');
      meta.name = 'lps-shell-csrf-token';
      document.head.appendChild(meta);
      created = true;
    }
    meta.content = token;
    return () => {
      if (created && meta && meta.parentNode) {
        meta.parentNode.removeChild(meta);
      }
    };
  }, [contexto?.csrf_shell, contexto?.csrf_token]);

  useEffect(() => {
    let cancelado = false;
    async function cargar() {
      try {
        setCargando(true);
        setError(null);
        const ctx = await api.obtenerContexto();
        if (cancelado) return;
        setContexto(ctx);
        const rawAct = await api.obtenerActividades(ctx.semana.numero);
        if (cancelado) return;
        setActividades(normalizarActividades(rawAct, ctx.semana.numero));
      } catch (err: unknown) {
        if (!cancelado) {
          const msg = err instanceof Error ? err.message : 'Error cargando Programa General';
          setError(msg);
        }
      } finally {
        if (!cancelado) setCargando(false);
      }
    }
    cargar();
    return () => {
      cancelado = true;
    };
  }, [api]);

  const conteos = useMemo(() => calcularConteosSenales(actividades), [actividades]);
  const actividadesFiltradas = useMemo(
    () => filtrarActividades(actividades, busqueda, estadoFiltro),
    [actividades, busqueda, estadoFiltro]
  );

  const tareasOperativas = useMemo(() => actividades.filter((a) => !a.esCapitulo), [actividades]);

  const actividadSeleccionada = useMemo(() => {
    return actividades.find((a) => a.unique_id === actividadSeleccionadaId) || null;
  }, [actividades, actividadSeleccionadaId]);

  const handleNavigateSeq = useCallback(
    (direccion: number) => {
      const idx = tareasOperativas.findIndex((a) => a.unique_id === actividadSeleccionadaId);
      if (idx === -1) return;
      const nuevoIdx = idx + direccion;
      if (nuevoIdx >= 0 && nuevoIdx < tareasOperativas.length) {
        setActividadSeleccionadaId(tareasOperativas[nuevoIdx].unique_id);
      }
    },
    [tareasOperativas, actividadSeleccionadaId]
  );

  const handleGuardar = async (datos: Record<string, unknown>) => {
    if (!contexto) return;
    try {
      const ejecNum = datos.Ejecutado !== undefined && datos.Ejecutado !== null ? Number(datos.Ejecutado) : 0;
      const ratioNum = datos.EjecutadoRatio !== undefined && datos.EjecutadoRatio !== null ? Number(datos.EjecutadoRatio) : ejecNum / 100;

      await api.guardarActividad({
        unique_id: Number(datos.unique_id),
        semana: contexto.semana.numero,
        Fecha_Inicio: (datos.Fecha_Inicio as string) ?? null,
        Fecha_Fin: (datos.Fecha_Fin as string) ?? null,
        unidad: (datos.unidad as string) ?? 'm³',
        cantidad_ppto: datos.cantidad_ppto !== undefined && datos.cantidad_ppto !== null ? Number(datos.cantidad_ppto) : null,
        Ejecutado: ejecNum,
        EjecutadoRatio: ratioNum,
        Responsable_AIA: (datos.Responsable_AIA as string) ?? null,
        Sub_Contratista: (datos.Sub_Contratista as string) ?? null,
        codigo_actividad: (datos.codigo_actividad as string) ?? '',
        csrf_token: contexto.csrf_token,
      });

      setToastMsg('Cambios guardados con éxito');
      setTimeout(() => setToastMsg(null), 3000);

      // Recargar actividades actualizadas
      const raw = await api.obtenerActividades(contexto.semana.numero);
      setActividades(normalizarActividades(raw, contexto.semana.numero));
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Error al guardar actividad';
      alert(`Error al guardar: ${msg}`);
    }
  };

  const handleExportCsv = useCallback(() => {
    if (!contexto) return;
    try {
      setExportandoCsv(true);
      const csv = generarContenidoCsv13Cols(actividades);
      const fechaHoy = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const nombre = `programa_general_sem_${contexto.semana.numero}_${fechaHoy}.csv`;
      dispararDescargaCsv(csv, nombre);
    } finally {
      setExportandoCsv(false);
    }
  }, [actividades, contexto]);

  const handleDownloadCorteXlsx = useCallback(async () => {
    if (!contexto) return;
    try {
      setGenerandoCorte(true);
      const res = await api.generarCorteXlsx(contexto.semana.numero);
      if (res.url) {
        window.open(res.url, '_blank');
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Error al generar corte XLSX';
      alert(`Error al generar corte: ${msg}`);
    } finally {
      setGenerandoCorte(false);
    }
  }, [api, contexto]);

  if (cargando) {
    return (
      <div className="programa-general-container" role="status" aria-label="Cargando">
        <p className="pg-loading">Cargando Programa General...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="programa-general-container" role="alert">
        <p className="pg-error">{error}</p>
      </div>
    );
  }

  if (!contexto) return null;

  const indiceActualDrawer = tareasOperativas.findIndex((a) => a.unique_id === actividadSeleccionadaId) + 1;

  return (
    <div className="programa-general-container">
      {/* Compatibilidad con contratos E2E y shell legacy */}
      <div style={{ display: 'none' }} aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value={contexto.proyecto.codigo || ''} readOnly />
        <input type="hidden" id="baseDatos" value={contexto.proyecto.codigo || ''} readOnly />
        <input type="hidden" id="proyecto_PHP" value={contexto.proyecto.nombre || ''} readOnly />
        <input type="hidden" id="proyecto" value={contexto.proyecto.nombre || ''} readOnly />
        <input type="hidden" id="semana_PHP" value={String(contexto.semana.numero || '')} readOnly />
        <input type="hidden" id="semana" name="semana" value={String(contexto.semana.numero || '')} readOnly />
        <input type="hidden" id="area_PHP" value={contexto.proyecto.tipo || 'Construccion'} readOnly />
      </div>

      <ProgramaToolbar
        semana={contexto.semana.numero}
        modo13Cols={modo13Cols}
        onToggleColumnas={() => setModo13Cols(!modo13Cols)}
        onOpenDrawer={() => {
          if (tareasOperativas.length > 0) {
            setActividadSeleccionadaId(tareasOperativas[0].unique_id);
          }
        }}
        onExportCsv={handleExportCsv}
        onDownloadCorteXlsx={handleDownloadCorteXlsx}
        puedeEditar={contexto.permisos.puedeEditar}
        puedeCorteXlsx={contexto.permisos.puedeCorteXlsx}
        exportandoCsv={exportandoCsv}
        generandoCorte={generandoCorte}
      />

      <ProgramaSignalsBar
        conteos={conteos}
        estadoFiltro={estadoFiltro}
        onSelectEstado={setEstadoFiltro}
      />

      <ProgramaFilters
        busqueda={busqueda}
        onBusquedaChange={setBusqueda}
        estadoFiltro={estadoFiltro}
        onLimpiarEstado={() => setEstadoFiltro(null)}
        totalVisibles={contarTareasVisibles(actividadesFiltradas)}
        totalTotal={conteos.total}
        avanceMacroPct={
          conteos.total > 0
            ? Math.round(
                (actividades
                  .filter((a) => !a.esCapitulo)
                  .reduce((acc, a) => acc + a.avanceRealPct, 0) /
                  conteos.total) *
                  10
              ) / 10
            : 0
        }
      />

      <div className="programa-content-area">
        <ProgramaTable
          actividades={actividadesFiltradas}
          actividadSeleccionadaId={actividadSeleccionadaId}
          onSelectActividad={setActividadSeleccionadaId}
          modo13Cols={modo13Cols}
        />
        <ProgramaCards
          actividades={actividadesFiltradas}
          onSelectActividad={setActividadSeleccionadaId}
        />
      </div>

      {actividadSeleccionada && (
        <ProgramaDrawer
          actividad={actividadSeleccionada}
          catalogos={contexto.catalogos}
          indiceActual={indiceActualDrawer > 0 ? indiceActualDrawer : 1}
          totalActividades={tareasOperativas.length}
          onCerrar={() => setActividadSeleccionadaId(null)}
          onGuardar={handleGuardar}
          onNavigateSeq={handleNavigateSeq}
        />
      )}

      {toastMsg && (
        <div className="pro-toast" role="status">
          <i className="fas fa-check-circle" aria-hidden="true"></i> {toastMsg}
        </div>
      )}
    </div>
  );
};
export default ProgramaGeneralPage;
