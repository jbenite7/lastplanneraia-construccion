import { useEffect, useReducer, useRef, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ModuleRegistry, ValidationModule } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'
import {
  MODULOS_TABLA, TEXTO_LARGO, autoSizeStrategy, columnaMoneda, columnaNumero, columnaTexto,
  defaultColDef, moneda, pdcTheme,
} from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { estadoInicial, importReducer } from '../lib/importState'
import { etiquetaVersion } from '../lib/versionLabel'
import type { Comparativo, ImportConfirmResult, ImportErrorFila, ImportPreview, ResumenDiff, VersionPresupuesto } from '../lib/types'

// Mismo criterio que MaestroInsumos.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const colsErrores: ColDef<ImportErrorFila>[] = [
  columnaNumero('fila', 'Fila'),
  { field: 'columna', headerName: 'Columna' },
  columnaTexto('motivo', 'Motivo', 240),
]

const colsVersiones: ColDef<VersionPresupuesto>[] = [
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
]

export default function ImportarPresupuesto() {
  const [state, dispatch] = useReducer(importReducer, estadoInicial)
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [cmp, setCmp] = useState<ResumenDiff | null>(null)
  const fileRef = useRef<HTMLInputElement>(null)

  const cargarVersiones = () => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }
  useEffect(cargarVersiones, [])

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
          <div style={{ height: 280 }}>
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
        <h2>Historial de versiones</h2>
        <div style={{ height: 260 }}>
          <AgGridReact<VersionPresupuesto>
            theme={pdcTheme}
            rowData={versiones}
            columnDefs={colsVersiones}
            defaultColDef={defaultColDef}
            autoSizeStrategy={autoSizeStrategy}
          />
        </div>
      </div>
    </section>
  )
}
