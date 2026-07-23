import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ClientSideRowModelModule, ModuleRegistry, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, RowDoubleClickedEvent } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { estadoInicialMaestro, maestroReducer } from '../lib/maestroState'
import type { MaestroInsumo, ResumenVinculos, SugerenciaMaestro, VinculoInsumo } from '../lib/types'

// Mismo criterio que ImportarPresupuesto.tsx/VisorPresupuesto.tsx: registro selectivo de módulos
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

const moneda = (v: number | null | undefined) => (v == null ? '' : `$ ${v.toLocaleString('es-CO')}`)

export default function MaestroInsumos() {
  const [state, dispatch] = useReducer(maestroReducer, estadoInicialMaestro)
  const [vinculos, setVinculos] = useState<VinculoInsumo[]>([])
  const [resumen, setResumen] = useState<ResumenVinculos | null>(null)
  const [catalogo, setCatalogo] = useState<MaestroInsumo[]>([])
  const [busqueda, setBusqueda] = useState('')
  const [sugerencias, setSugerencias] = useState<SugerenciaMaestro[]>([])
  const [sinPresupuesto, setSinPresupuesto] = useState(false)

  const cargar = useCallback(async () => {
    try {
      const g = await apiPost<ResumenVinculos & { versionId: number }>('/plan-compras/api/maestro/vinculos/generar', {})
      void g
      const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
      setResumen(v.resumen)
      setVinculos(v.vinculos)
      setSinPresupuesto(false)
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinPresupuesto(true)
      else if (e instanceof PdcApiError && e.code === 'FORBIDDEN') {
        // Sin permiso de escritura: cargar solo lectura.
        try {
          const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
          setResumen(v.resumen)
          setVinculos(v.vinculos)
        } catch { setSinPresupuesto(true) }
      } else dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }, [])

  const cargarCatalogo = useCallback((q: string) => {
    apiGet<{ insumos: MaestroInsumo[] }>(`/plan-compras/api/maestro?busqueda=${encodeURIComponent(q)}`)
      .then((d) => setCatalogo(d.insumos))
      .catch(() => setCatalogo([]))
  }, [])

  useEffect(() => { void cargar() }, [cargar])
  useEffect(() => { cargarCatalogo(busqueda) }, [busqueda, cargarCatalogo])

  useEffect(() => {
    if (!state.vinculando) { setSugerencias([]); return }
    apiGet<{ sugerencias: SugerenciaMaestro[] }>(`/plan-compras/api/maestro/sugerencias?vinculoId=${state.vinculando.id}`)
      .then((d) => setSugerencias(d.sugerencias))
      .catch(() => setSugerencias([]))
  }, [state.vinculando])

  const pendientes = useMemo(() => vinculos.filter((v) => v.estado === 'pendiente'), [vinculos])

  const colsPendientes: ColDef<VinculoInsumo>[] = useMemo(() => [
    {
      headerName: '✓', width: 60, field: 'id',
      valueFormatter: (p) => (state.seleccion.has(p.value as number) ? '●' : ''),
    },
    { field: 'descripcionOriginal', headerName: 'Insumo', flex: 1, minWidth: 260 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 150 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'apariciones', headerName: 'Usos', width: 80 },
    { field: 'valorTotal', headerName: 'Valor total', width: 140, valueFormatter: (p) => moneda(p.value) },
  ], [state.seleccion])

  const colsCatalogo: ColDef<MaestroInsumo>[] = useMemo(() => [
    { field: 'descripcion', headerName: 'Insumo', flex: 1, minWidth: 280 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 160 },
  ], [])

  const onPendienteClick = (e: CellClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'TOGGLE_SEL', id: e.data.id })
  }
  const onPendienteDoble = (e: RowDoubleClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'ABRIR_VINCULAR', vinculo: e.data })
  }

  const crearMasivo = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ creados: number; vinculados: number }>('/plan-compras/api/maestro/crear-desde-pendientes', {
        vinculoIds: [...state.seleccion],
      })
      dispatch({ type: 'LISTO', mensaje: `${r.creados} insumos creados en el maestro, ${r.vinculados} vinculados.` })
      await cargar()
      cargarCatalogo(busqueda)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const vincularA = async (maestroId: number) => {
    if (!state.vinculando) return
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/maestro/vinculos/confirmar', { vinculoId: state.vinculando.id, maestroId })
      dispatch({ type: 'LISTO', mensaje: 'Insumo vinculado.' })
      await cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  if (sinPresupuesto) {
    return (
      <section className="pdc-page">
        <header className="pdc-header"><h1>Maestro de insumos</h1></header>
        <div className="pdc-bloque pdc-vacio" data-testid="pdc-maestro-vacio">
          Este proyecto aún no tiene un presupuesto importado. Ve a <strong>Ensamble → Importar</strong>.
        </div>
      </section>
    )
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Maestro de insumos</h1>
          <p>Catálogo único de AIA. Vincula o crea los insumos del presupuesto activo.</p>
        </div>
        {resumen && (
          <p data-testid="pdc-maestro-cobertura" className="pdc-cobertura">
            Cobertura: {resumen.cobertura}% · {resumen.pendientes} pendientes de {resumen.total}
          </p>
        )}
      </header>

      {state.mensaje && <div className="pdc-exito" role="status">{state.mensaje}</div>}

      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Pendientes por vincular ({pendientes.length})</h2>
          <div>
            <button type="button" data-testid="pdc-maestro-sel-todos" onClick={() => dispatch({ type: 'SEL_TODOS', ids: pendientes.map((p) => p.id) })}>
              Seleccionar todos
            </button>{' '}
            <button
              type="button"
              data-testid="pdc-maestro-crear-masivo"
              disabled={state.seleccion.size === 0 || state.ocupado}
              onClick={crearMasivo}
            >
              {state.ocupado ? 'Procesando…' : `Crear ${state.seleccion.size} en el maestro`}
            </button>
          </div>
        </div>
        <p className="pdc-ayuda">Clic = seleccionar · doble clic = vincular a un insumo existente.</p>
        <div style={{ height: 300 }} data-testid="pdc-maestro-pendientes">
          <AgGridReact<VinculoInsumo>
            theme={pdcTheme}
            rowData={pendientes}
            columnDefs={colsPendientes}
            getRowId={(p) => String(p.data.id)}
            onCellClicked={onPendienteClick}
            onRowDoubleClicked={onPendienteDoble}
          />
        </div>
      </div>

      {state.vinculando && (
        <div className="pdc-bloque pdc-panel" data-testid="pdc-maestro-panel">
          <h2>Vincular «{state.vinculando.descripcionOriginal}» ({state.vinculando.unidad})</h2>
          {sugerencias.length === 0 ? (
            <p>Sin sugerencias — créalo con la acción masiva o búscalo en el catálogo.</p>
          ) : (
            <ul>
              {sugerencias.map((s) => (
                <li key={s.id}>
                  <button type="button" disabled={state.ocupado} onClick={() => vincularA(s.id)}>
                    {s.descripcion} ({s.unidad})
                  </button>
                </li>
              ))}
            </ul>
          )}
          <button type="button" onClick={() => dispatch({ type: 'CERRAR_VINCULAR' })}>Cerrar</button>
        </div>
      )}

      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Catálogo global</h2>
          <input
            data-testid="pdc-maestro-busqueda"
            type="search"
            placeholder="Buscar insumo…"
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
          />
        </div>
        <div style={{ height: 280 }} data-testid="pdc-maestro-catalogo">
          <AgGridReact<MaestroInsumo>
            theme={pdcTheme}
            rowData={catalogo}
            columnDefs={colsCatalogo}
            getRowId={(p) => String(p.data.id)}
          />
        </div>
      </div>
    </section>
  )
}
