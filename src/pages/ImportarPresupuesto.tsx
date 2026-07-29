import { useEffect, useMemo, useReducer, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import {
  MODULOS_TABLA, TEXTO_LARGO, autoSizeStrategy, columnaMoneda, columnaNumero, columnaTexto,
  defaultColDef, moneda, pdcTheme,
} from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { alternarSeleccion, puedeMarcar, rutaComparar, rutaVisor } from '../lib/historialVersiones'
import { estadoInicial, importReducer } from '../lib/importState'
import { etiquetaVersion } from '../lib/versionLabel'
import type { Comparativo, ImportConfirmResult, ImportErrorFila, ImportPreview, ImpactoVersion, ResumenDiff, VersionPresupuesto } from '../lib/types'

// Mismo criterio que MaestroInsumos.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule, // cellClass de la casilla de comparar y de la acción de fijar como oficial
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const colsErrores: ColDef<ImportErrorFila>[] = [
  columnaNumero('fila', 'Fila'),
  { field: 'columna', headerName: 'Columna' },
  columnaTexto('motivo', 'Motivo', 240),
]

/**
 * Columnas del historial. La casilla y la acción se identifican por `colId` porque el clic hace
 * tres cosas distintas según dónde caiga: marcar para comparar, fijar como oficial, o —en el resto
 * de la fila— abrir esa versión en el visor.
 */
const colsVersiones = (seleccion: number[]): ColDef<VersionPresupuesto>[] => [
  {
    colId: 'comparar', headerName: '⇄', width: 52, sortable: false, suppressAutoSize: true,
    cellClass: 'pdc-celda-accion',
    valueGetter: (p) => (p.data && seleccion.includes(p.data.id) ? 1 : 0),
    valueFormatter: (p) => (p.value === 1 ? '☑' : '☐'),
    headerTooltip: 'Marca hasta dos versiones para compararlas',
  },
  // El nombre de la versión y el del archivo son los dos que la revisión encontró recortados
  // («102 DAPORTO RIONEGRO PI_Version…», «Import Da Po…»): envuelven en vez de cortarse.
  {
    ...TEXTO_LARGO, colId: 'version', headerName: 'Versión', minWidth: 240,
    valueGetter: (p) => (p.data ? etiquetaVersion(p.data) : ''),
  },
  columnaTexto('archivoNombre', 'Archivo', 220),
  columnaNumero('totalActividades', 'Actividades'),
  columnaNumero('totalInsumos', 'Insumos'),
  columnaMoneda('costoTotal', 'Costo total'),
  { field: 'importadoPor', headerName: 'Importó' },
  { field: 'activa', headerName: 'Estado', valueFormatter: (p) => (p.value ? 'Activa' : '') },
  {
    colId: 'oficial', headerName: '', width: 150, sortable: false, suppressAutoSize: true,
    cellClass: (p) => (p.data?.activa ? undefined : 'pdc-celda-accion'),
    valueGetter: (p) => (p.data?.activa ? '' : 'Fijar como oficial'),
  },
]

export default function ImportarPresupuesto() {
  const [state, dispatch] = useReducer(importReducer, estadoInicial)
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [cmp, setCmp] = useState<ResumenDiff | null>(null)
  // Versiones marcadas para comparar (máximo dos) y la que está esperando confirmación para
  // volverse oficial. Ver historialVersiones.ts.
  const [seleccion, setSeleccion] = useState<number[]>([])
  const [porFijar, setPorFijar] = useState<VersionPresupuesto | null>(null)
  const [impacto, setImpacto] = useState<ImpactoVersion | null>(null)
  const [avisoOficial, setAvisoOficial] = useState<string | null>(null)
  const fileRef = useRef<HTMLInputElement>(null)
  const navigate = useNavigate()

  const cargarVersiones = () => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }
  useEffect(cargarVersiones, [])

  const cols = useMemo(() => colsVersiones(seleccion), [seleccion])
  const destinoComparar = rutaComparar(seleccion)

  /**
   * Un clic hace tres cosas distintas según la columna: marcar para comparar, empezar a fijar como
   * oficial, o abrir esa versión en el visor. El resto de la fila navega directo, sin modal — que
   * es lo que se pidió: ver un presupuesto no cambia nada, así que no hay nada que confirmar.
   */
  const onVersionClick = (e: CellClickedEvent<VersionPresupuesto>) => {
    if (!e.data) return
    const col = e.column?.getColId()
    if (col === 'comparar') {
      if (!puedeMarcar(seleccion, e.data.id)) {
        setAvisoOficial('Solo se pueden comparar dos versiones a la vez: desmarca una para elegir otra.')
        return
      }
      setAvisoOficial(null)
      setSeleccion((prev) => alternarSeleccion(prev, e.data!.id))
      return
    }
    if (col === 'oficial') {
      if (e.data.activa) return // ya es la oficial: no hay nada que fijar
      pedirConfirmacionOficial(e.data)
      return
    }
    navigate(rutaVisor(e.data.id))
  }

  /**
   * Antes de cambiar la versión oficial se pregunta, y se dice qué queda afectado con un número
   * real. Se avisa, no se bloquea: las asignaciones a paquete y el plan de fechas no dependen de la
   * versión y sobreviven solos — lo único atado a una versión concreta son los vínculos del maestro.
   */
  const pedirConfirmacionOficial = (v: VersionPresupuesto) => {
    setPorFijar(v)
    setImpacto(null)
    setAvisoOficial(null)
    apiGet<ImpactoVersion>('/plan-compras/api/presupuesto/impacto-version')
      .then(setImpacto)
      .catch(() => setImpacto(null))
  }

  const onFijarOficial = async () => {
    if (!porFijar) return
    try {
      await apiPost('/plan-compras/api/presupuesto/activar', { versionId: porFijar.id })
      setAvisoOficial(`Ahora rige la ${etiquetaVersion(porFijar)}.`)
      setPorFijar(null)
      setImpacto(null)
      cargarVersiones()
    } catch (e) {
      const mensaje = e instanceof PdcApiError && e.code === 'FORBIDDEN'
        ? 'No tienes permiso para cambiar cuál presupuesto rige (hace falta el mismo permiso que para importar).'
        : e instanceof Error ? e.message : String(e)
      setAvisoOficial(mensaje)
      setPorFijar(null)
    }
  }

  const onArchivo = async (file: File | undefined) => {
    if (!file) return
    dispatch({ type: 'SUBIR' })
    try {
      const preview = await apiUpload<ImportPreview>('/plan-compras/api/presupuesto/preview', file)
      dispatch({ type: 'PREVIEW_OK', preview })
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'VALIDATION_FAILED') {
        const detalle = e.details as { errores?: ImportErrorFila[] } | undefined
        dispatch({ type: 'PREVIEW_ERRORES', errores: detalle?.errores ?? [] })
      } else {
        dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
      }
    } finally {
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const onConfirmar = async () => {
    if (!state.preview) return
    dispatch({ type: 'CONFIRMAR' })
    setCmp(null)
    try {
      const resultado = await apiPost<ImportConfirmResult>('/plan-compras/api/presupuesto/confirmar', { importToken: state.preview.importToken })
      dispatch({ type: 'CONFIRMADO', resultado })
      cargarVersiones()
      if (!resultado.sinCambios && resultado.versionIdAnterior != null) {
        apiGet<Comparativo>(`/plan-compras/api/presupuesto/comparar?versionA=${resultado.versionIdAnterior}&versionB=${resultado.versionId}`)
          .then((c) => setCmp(c.resumen))
          .catch(() => setCmp(null))
      }
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const r = state.preview?.resumen

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Importar presupuesto</h1>
        <p>Sube el Excel exportado del software de presupuestos (hoja «Presupuesto», máx. 10MB).</p>
      </header>

      <input
        ref={fileRef}
        data-testid="pdc-import-file"
        type="file"
        accept=".xlsx"
        disabled={state.fase === 'subiendo' || state.fase === 'confirmando'}
        onChange={(e) => onArchivo(e.target.files?.[0])}
      />

      {state.fase === 'subiendo' && <p>Analizando el archivo…</p>}
      {state.mensajeError && <div className="pdc-error" role="alert">{state.mensajeError}</div>}

      {state.fase === 'previewErrores' && (
        <div className="pdc-bloque">
          <div className="pdc-error" role="alert">
            El archivo tiene {state.errores.length} error(es); no se importó nada. Corrige el Excel y vuelve a subirlo.
          </div>
          <div className="pdc-grid-corta">
            <AgGridReact<ImportErrorFila>
              theme={pdcTheme}
              rowData={state.errores}
              columnDefs={colsErrores}
              defaultColDef={defaultColDef}
              autoSizeStrategy={autoSizeStrategy}
            />
          </div>
        </div>
      )}

      {(state.fase === 'previewOk' || state.fase === 'confirmando') && r && (
        <div className="pdc-bloque" data-testid="pdc-import-resumen">
          <h2>Previsualización — {state.preview?.versionLabel ?? 'sin versión'}</h2>
          <p>
            {r.capitulos} capítulos · {r.subcapitulos} subcapítulos · {r.grupos} grupos · {r.actividades} actividades ·{' '}
            {r.insumos} insumos · Costo total {moneda(r.costoTotal)}
          </p>
          {state.preview?.advertencias.map((a) => (
            <p key={a} className="pdc-advertencia">⚠ {a}</p>
          ))}
          {state.preview?.sinCambios && state.preview.versionActiva && (
            <p className="pdc-advertencia" data-testid="pdc-import-sincambios">
              ⚠ Este presupuesto es idéntico a la <strong>Versión {state.preview.versionActiva.numero}</strong> (activa). No se creará una versión nueva.
            </p>
          )}
          <button
            type="button"
            data-testid="pdc-import-confirmar"
            disabled={state.fase === 'confirmando'}
            onClick={onConfirmar}
          >
            {state.fase === 'confirmando' ? 'Importando…' : 'Confirmar e importar'}
          </button>
        </div>
      )}

      {state.fase === 'confirmado' && state.resultado && (
        <div className="pdc-bloque pdc-exito" role="status" data-testid="pdc-import-confirmado">
          {state.resultado.sinCambios ? (
            <p>Sin cambios: se mantiene la <strong>Versión {state.resultado.versionNumero}</strong> activa.</p>
          ) : (
            <>
              <p>Cargada la <strong>Versión {state.resultado.versionNumero}</strong> — ahora es la versión activa del proyecto.</p>
              {cmp && (
                <div data-testid="pdc-import-comparativo">
                  <p>
                    Cambios vs la versión anterior: {cmp.nuevos} nuevos · {cmp.eliminados} eliminados · {cmp.modificados} modificados ·{' '}
                    <span className="pdc-cmp-sobrecosto">sobrecostos {moneda(cmp.sobrecostos)}</span> ·{' '}
                    <span className="pdc-cmp-ahorro">ahorros {moneda(cmp.ahorros)}</span>
                  </p>
                  <a className="pdc-nav-link" href="#/ensamble/comparar">Ver comparativo completo →</a>
                </div>
              )}
            </>
          )}
        </div>
      )}

      <div className="pdc-bloque" data-testid="pdc-import-versiones">
        <div className="pdc-fila-acciones">
          <h2>Historial de versiones</h2>
          <div className="pdc-selector">
            <span className="pdc-ayuda">
              Clic en una fila para verla · marca hasta dos para comparar
            </span>
            <button
              type="button"
              data-testid="pdc-import-comparar"
              disabled={destinoComparar === null}
              onClick={() => destinoComparar && navigate(destinoComparar)}
            >
              Comparar {seleccion.length}/2
            </button>
          </div>
        </div>

        {avisoOficial && <div className="pdc-info" role="status" data-testid="pdc-import-aviso">{avisoOficial}</div>}

        {porFijar && (
          <div className="pdc-panel" data-testid="pdc-import-confirmar-oficial">
            <p>
              ¿Fijar la <strong>{etiquetaVersion(porFijar)}</strong> como el presupuesto oficial del
              proyecto? Es la base del visor, del maestro y del Pareto.
            </p>
            <p className="pdc-ayuda">
              {impacto === null
                ? 'Comprobando qué queda afectado…'
                : impacto.vinculosAfectados === 0
                  ? 'No hay vínculos del maestro hechos sobre la versión que se abandona.'
                  : `${impacto.vinculosAfectados} vínculo(s) del maestro quedarán apuntando a la versión que se abandona`
                    + `${impacto.versionActual ? ` (${impacto.versionActual.label})` : ''}.`}
              {' '}Los paquetes y el plan de fechas no dependen de la versión: se conservan.
            </p>
            <button type="button" data-testid="pdc-import-oficial-confirmar" onClick={onFijarOficial}>
              Sí, que rija esta
            </button>{' '}
            <button type="button" data-testid="pdc-import-oficial-cancelar" onClick={() => { setPorFijar(null); setImpacto(null) }}>
              Cancelar
            </button>
          </div>
        )}

        <div className="pdc-grid-corta">
          <AgGridReact<VersionPresupuesto>
            theme={pdcTheme}
            rowData={versiones}
            columnDefs={cols}
            defaultColDef={defaultColDef}
            autoSizeStrategy={autoSizeStrategy}
            getRowId={(p) => String(p.data.id)}
            onCellClicked={onVersionClick}
          />
        </div>
      </div>
    </section>
  )
}
