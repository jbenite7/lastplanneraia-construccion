import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ClientSideRowModelModule, ModuleRegistry, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet } from '../lib/api'
import { filasVisibles } from '../lib/presupuestoTree'
import type { FilaVisor } from '../lib/presupuestoTree'
import type { ArbolPresupuesto, VersionPresupuesto } from '../lib/types'
import { etiquetaVersion } from '../lib/versionLabel'

// Mismo criterio que ImportarPresupuesto.tsx: registro selectivo de módulos
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

const moneda = (v: number | null) => (v == null || v === 0 ? '' : `$ ${v.toLocaleString('es-CO')}`)

export default function VisorPresupuesto() {
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [versionId, setVersionId] = useState<number | null>(null)
  const [arbol, setArbol] = useState<ArbolPresupuesto | null>(null)
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  const [sinPresupuesto, setSinPresupuesto] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }, [])

  useEffect(() => {
    const q = versionId != null ? `?versionId=${versionId}` : ''
    setError(null)
    setSinPresupuesto(false)
    apiGet<ArbolPresupuesto>(`/plan-compras/api/presupuesto/arbol${q}`)
      .then((a) => {
        setArbol(a)
        setSinPresupuesto(false)
        setExpandidos(new Set())
      })
      .catch((e) => {
        if (e instanceof PdcApiError && e.code === 'NO_VERSION') {
          setSinPresupuesto(true)
          setArbol(null)
        } else {
          setError(e instanceof Error ? e.message : String(e))
          setArbol(null)
        }
      })
  }, [versionId])

  const filas = useMemo(
    () => (arbol ? filasVisibles(arbol.items, arbol.insumos, expandidos) : []),
    [arbol, expandidos],
  )

  const cols: ColDef<FilaVisor>[] = useMemo(() => [
    { field: 'codigo', headerName: 'Código', width: 130 },
    {
      field: 'descripcion', headerName: 'Descripción', flex: 1, minWidth: 320,
      cellClass: 'pdc-visor-descripcion',
      valueFormatter: (p) => {
        const f = p.data as FilaVisor
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : f.tipo === 'insumo' ? '· ' : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    { field: 'tipoInsumo', headerName: 'Tipo insumo', width: 160 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'cantidad', headerName: 'Cantidad', width: 110 },
    { field: 'valorUnitario', headerName: 'Vr. unitario', width: 130, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorTotal', headerName: 'Valor total', width: 150, valueFormatter: (p) => moneda(p.value) },
  ], [])

  const onCellClicked = (e: CellClickedEvent<FilaVisor>) => {
    const f = e.data
    if (!f || !f.expandible || e.colDef.field !== 'descripcion') return
    setExpandidos((prev) => {
      const next = new Set(prev)
      if (next.has(f.key)) next.delete(f.key)
      else next.add(f.key)
      return next
    })
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Presupuesto</h1>
          <p>Vista del presupuesto importado. Haz clic en una fila para expandirla.</p>
        </div>
        {versiones.length > 0 && (
          <label className="pdc-selector">
            Versión{' '}
            <select
              data-testid="pdc-visor-version"
              value={versionId ?? ''}
              onChange={(e) => setVersionId(e.target.value === '' ? null : Number(e.target.value))}
            >
              <option value="">Activa</option>
              {versiones.map((v) => (
                <option key={v.id} value={v.id}>
                  {etiquetaVersion(v)}{v.activa ? ' (activa)' : ''}
                </option>
              ))}
            </select>
          </label>
        )}
      </header>

      {error && <div className="pdc-error" role="alert">{error}</div>}

      {sinPresupuesto ? (
        <div className="pdc-bloque pdc-vacio" data-testid="pdc-visor-vacio">
          Este proyecto aún no tiene un presupuesto importado. Ve a <strong>Ensamble → Importar</strong>.
        </div>
      ) : (
        <div style={{ height: 560 }} data-testid="pdc-visor-arbol">
          <AgGridReact<FilaVisor>
            theme={pdcTheme}
            rowData={filas}
            columnDefs={cols}
            getRowId={(p) => p.data.key}
            onCellClicked={onCellClicked}
          />
        </div>
      )}
    </section>
  )
}
