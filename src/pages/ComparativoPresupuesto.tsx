import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ClientSideRowModelModule, ModuleRegistry, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import { moneda, pdcTheme } from '../lib/agGrid'
import { PdcApiError, apiGet } from '../lib/api'
import { claseDelta, filasComparativoVisibles } from '../lib/comparativo'
import type { FilaComparativo } from '../lib/comparativo'
import type { Comparativo, InsumoDiff, VersionPresupuesto } from '../lib/types'
import { etiquetaVersion } from '../lib/versionLabel'

// Mismo criterio que VisorPresupuesto.tsx: registro selectivo de módulos.
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule, // cellClass condicional de la columna Δ (sobrecosto/ahorro)
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const signo = (v: number) => (v > 0 ? '+' : '') + v.toLocaleString('es-CO')

export default function ComparativoPresupuesto() {
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [idA, setIdA] = useState<number | null>(null)
  const [idB, setIdB] = useState<number | null>(null)
  const [data, setData] = useState<Comparativo | null>(null)
  const [eje, setEje] = useState<'actividades' | 'insumos'>('insumos')
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => {
        setVersiones(d.versiones)
        // Preselección: B = activa (o la más reciente), A = la inmediatamente anterior.
        if (d.versiones.length >= 2) {
          const activa = d.versiones.find((v) => v.activa === 1) ?? d.versiones[0]
          const anterior = d.versiones.find((v) => v.id !== activa.id) ?? d.versiones[1]
          setIdB(activa.id)
          setIdA(anterior.id)
        }
      })
      .catch(() => setVersiones([]))
  }, [])

  useEffect(() => {
    if (idA == null || idB == null || idA === idB) { setData(null); return }
    setError(null)
    apiGet<Comparativo>(`/plan-compras/api/presupuesto/comparar?versionA=${idA}&versionB=${idB}`)
      .then((d) => { setData(d); setExpandidos(new Set()) })
      .catch((e) => {
        setData(null)
        setError(e instanceof PdcApiError ? e.message : e instanceof Error ? e.message : String(e))
      })
  }, [idA, idB])

  const filasAct = useMemo(
    () => (data ? filasComparativoVisibles(data.actividades, expandidos) : []),
    [data, expandidos],
  )

  const colsAct: ColDef<FilaComparativo>[] = useMemo(() => [
    {
      field: 'descripcion', headerName: 'Actividad', flex: 1, minWidth: 320, cellClass: 'pdc-visor-descripcion',
      valueFormatter: (p) => {
        const f = p.data as FilaComparativo
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    { field: 'valorA', headerName: 'Versión A', width: 150, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorB', headerName: 'Versión B', width: 150, valueFormatter: (p) => moneda(p.value) },
    {
      field: 'deltaValor', headerName: 'Δ', width: 140, valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as FilaComparativo).estado),
    },
    { field: 'estado', headerName: 'Estado', width: 120 },
  ], [])

  const colsIns: ColDef<InsumoDiff>[] = useMemo(() => [
    { field: 'descripcion', headerName: 'Insumo', flex: 1, minWidth: 280 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 150 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'valorA', headerName: 'Versión A', width: 150, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorB', headerName: 'Versión B', width: 150, valueFormatter: (p) => moneda(p.value) },
    {
      field: 'deltaValor', headerName: 'Δ', width: 140, valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as InsumoDiff).estado),
    },
    { field: 'estado', headerName: 'Estado', width: 120 },
  ], [])

  const onCellClickedAct = (e: CellClickedEvent<FilaComparativo>) => {
    const f = e.data
    if (!f || !f.expandible || e.colDef.field !== 'descripcion') return
    setExpandidos((prev) => {
      const next = new Set(prev)
      if (next.has(f.key)) next.delete(f.key)
      else next.add(f.key)
      return next
    })
  }

  const selectorVersion = (value: number | null, on: (id: number | null) => void, testid: string) => (
    <select data-testid={testid} value={value ?? ''} onChange={(e) => on(e.target.value === '' ? null : Number(e.target.value))}>
      <option value="">—</option>
      {versiones.map((v) => (
        <option key={v.id} value={v.id}>{etiquetaVersion(v)}{v.activa ? ' (activa)' : ''}</option>
      ))}
    </select>
  )

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Comparativo de versiones</h1>
          <p>Elige dos versiones para ver qué cambió: sobrecostos y ahorros por actividad e insumo.</p>
        </div>
        <div className="pdc-cmp-selectores">
          <label className="pdc-selector">A {selectorVersion(idA, setIdA, 'pdc-cmp-version-a')}</label>
          <label className="pdc-selector">B {selectorVersion(idB, setIdB, 'pdc-cmp-version-b')}</label>
        </div>
      </header>

      {error && <div className="pdc-error" role="alert">{error}</div>}
      {versiones.length < 2 && (
        <div className="pdc-bloque pdc-vacio">Necesitas al menos dos versiones importadas para comparar.</div>
      )}

      {data && (
        <>
          <div className="pdc-cmp-resumen" data-testid="pdc-cmp-resumen">
            <span>{moneda(data.resumen.costoA)} → {moneda(data.resumen.costoB)}</span>
            <span className={claseDelta(data.resumen.delta, 'modificado')}>
              Δ {signo(data.resumen.delta)}
            </span>
            <span className="pdc-cmp-sobrecosto">Sobrecostos {moneda(data.resumen.sobrecostos)}</span>
            <span className="pdc-cmp-ahorro">Ahorros {moneda(data.resumen.ahorros)}</span>
            <span>{data.resumen.nuevos} nuevos · {data.resumen.eliminados} eliminados · {data.resumen.modificados} modificados</span>
          </div>

          <div className="pdc-cmp-toggle">
            <button type="button" className={eje === 'insumos' ? 'activo' : ''} onClick={() => setEje('insumos')} data-testid="pdc-cmp-eje-insumos">Insumos</button>
            <button type="button" className={eje === 'actividades' ? 'activo' : ''} onClick={() => setEje('actividades')} data-testid="pdc-cmp-eje-actividades">Actividades</button>
          </div>

          <div style={{ height: 520 }} data-testid="pdc-cmp-grid">
            {eje === 'actividades' ? (
              <AgGridReact<FilaComparativo> theme={pdcTheme} rowData={filasAct} columnDefs={colsAct} getRowId={(p) => p.data.key} onCellClicked={onCellClickedAct} />
            ) : (
              <AgGridReact<InsumoDiff> theme={pdcTheme} rowData={data.insumos} columnDefs={colsIns} getRowId={(p) => `${p.data.descripcionNorm}|${p.data.unidad}`} />
            )}
          </div>
        </>
      )}
    </section>
  )
}
