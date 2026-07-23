import { useEffect, useReducer, useRef, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ClientSideRowModelModule, ModuleRegistry, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { estadoInicial, importReducer } from '../lib/importState'
import type { ImportErrorFila, ImportPreview, VersionPresupuesto } from '../lib/types'

// Mismo criterio que MaestroInsumos.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const colsErrores: ColDef<ImportErrorFila>[] = [
  { field: 'fila', headerName: 'Fila', width: 90 },
  { field: 'columna', headerName: 'Columna', width: 140 },
  { field: 'motivo', headerName: 'Motivo', flex: 1 },
]

const colsVersiones: ColDef<VersionPresupuesto>[] = [
  { field: 'versionLabel', headerName: 'Versión', flex: 1 },
  { field: 'archivoNombre', headerName: 'Archivo', flex: 1 },
  { field: 'totalActividades', headerName: 'Actividades', width: 120 },
  { field: 'totalInsumos', headerName: 'Insumos', width: 110 },
  {
    field: 'costoTotal', headerName: 'Costo total', width: 150,
    valueFormatter: (p) => p.value != null ? `$ ${Number(p.value).toLocaleString('es-CO')}` : '',
  },
  { field: 'importadoPor', headerName: 'Importó', width: 130 },
  { field: 'createdAt', headerName: 'Fecha', width: 160 },
  { field: 'activa', headerName: 'Estado', width: 100, valueFormatter: (p) => (p.value ? 'Activa' : '') },
]

export default function ImportarPresupuesto() {
  const [state, dispatch] = useReducer(importReducer, estadoInicial)
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
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
    try {
      await apiPost('/plan-compras/api/presupuesto/confirmar', { importToken: state.preview.importToken })
      dispatch({ type: 'CONFIRMADO' })
      cargarVersiones()
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
            <AgGridReact<ImportErrorFila> theme={pdcTheme} rowData={state.errores} columnDefs={colsErrores} />
          </div>
        </div>
      )}

      {(state.fase === 'previewOk' || state.fase === 'confirmando') && r && (
        <div className="pdc-bloque" data-testid="pdc-import-resumen">
          <h2>Previsualización — {state.preview?.versionLabel ?? 'sin versión'}</h2>
          <p>
            {r.capitulos} capítulos · {r.subcapitulos} subcapítulos · {r.grupos} grupos · {r.actividades} actividades ·{' '}
            {r.insumos} insumos · Costo total $ {r.costoTotal.toLocaleString('es-CO')}
          </p>
          {state.preview?.advertencias.map((a) => (
            <p key={a} className="pdc-advertencia">⚠ {a}</p>
          ))}
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

      {state.fase === 'confirmado' && (
        <div className="pdc-bloque pdc-exito" role="status">
          Presupuesto importado: ahora es la versión activa del proyecto.
        </div>
      )}

      <div className="pdc-bloque" data-testid="pdc-import-versiones">
        <h2>Historial de versiones</h2>
        <div style={{ height: 260 }}>
          <AgGridReact<VersionPresupuesto> theme={pdcTheme} rowData={versiones} columnDefs={colsVersiones} />
        </div>
      </div>
    </section>
  )
}
