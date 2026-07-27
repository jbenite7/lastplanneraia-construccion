import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import {
  ClientSideRowModelModule, ModuleRegistry, NumberFilterModule, TextFilterModule,
  TooltipModule, ValidationModule, themeQuartz,
} from 'ag-grid-community'
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
  // Filtros por columna del modo tabla: el registro es selectivo, así que hay que pedirlos. El
  // filtro por valores con casillas (Set Filter) es de AG Grid Enterprise y aquí se sustituye con
  // los desplegables de Tipo insumo y Unidad de la barra de herramientas.
  TextFilterModule,
  NumberFilterModule,
  TooltipModule,
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
  const [texto, setTexto] = useState('')
  const [tipoInsumo, setTipoInsumo] = useState('')
  const [unidad, setUnidad] = useState('')
  const [plano, setPlano] = useState(false)

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

  const filtro = useMemo(
    () => ({ texto, tipoInsumo, unidad, plano }),
    [texto, tipoInsumo, unidad, plano],
  )

  const filas = useMemo(
    () => (arbol ? filasVisibles(arbol.items, arbol.insumos, expandidos, filtro) : []),
    [arbol, expandidos, filtro],
  )

  // Valores que existen de verdad en este presupuesto: un desplegable con opciones vacías es peor
  // que no tenerlo. Es el equivalente al filtro por valores de Excel, que en AG Grid es de pago.
  const tiposInsumo = useMemo(
    () => [...new Set((arbol?.insumos ?? []).map((i) => i.tipoInsumo).filter(Boolean))].sort(),
    [arbol],
  )
  const unidades = useMemo(
    () => [...new Set((arbol?.insumos ?? []).map((i) => i.unidad).filter(Boolean))].sort(),
    [arbol],
  )

  const cols: ColDef<FilaVisor>[] = useMemo(() => [
    { field: 'codigo', headerName: 'Código', width: 130, filter: plano, sortable: plano },
    ...(plano ? [{
      field: 'ruta', headerName: 'Dónde está', width: 300, filter: true, sortable: true,
      tooltipValueGetter: (p) => String(p.value ?? ''),
    } as ColDef<FilaVisor>] : []),
    {
      field: 'descripcion', headerName: 'Descripción', flex: 1, minWidth: 320,
      cellClass: 'pdc-visor-descripcion',
      // En modo tabla no hay jerarquía que dibujar, así que la sangría y las flechas sobran.
      filter: plano, sortable: plano,
      valueFormatter: (p) => {
        const f = p.data as FilaVisor
        if (plano) return f.descripcion
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : f.tipo === 'insumo' ? '· ' : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    { field: 'tipoInsumo', headerName: 'Tipo insumo', width: 160, filter: plano, sortable: plano },
    { field: 'unidad', headerName: 'Und', width: 90, filter: plano, sortable: plano },
    { field: 'cantidad', headerName: 'Cantidad', width: 120, filter: plano ? 'agNumberColumnFilter' : false, sortable: plano },
    {
      field: 'valorUnitario', headerName: 'Vr. unitario', width: 140,
      filter: plano ? 'agNumberColumnFilter' : false, sortable: plano,
      valueFormatter: (p) => moneda(p.value),
    },
    {
      field: 'valorTotal', headerName: 'Valor total', width: 160,
      filter: plano ? 'agNumberColumnFilter' : false, sortable: plano,
      valueFormatter: (p) => moneda(p.value),
    },
  ], [plano])

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
        <>
          <div className="pdc-visor-tools">
            <input
              type="search"
              data-testid="pdc-visor-buscar"
              className="pdc-visor-buscar"
              placeholder="Buscar insumo, actividad o código…"
              aria-label="Buscar en el presupuesto"
              value={texto}
              onChange={(e) => setTexto(e.target.value)}
            />
            <label className="pdc-selector">
              Tipo insumo{' '}
              <select data-testid="pdc-visor-tipo" value={tipoInsumo} onChange={(e) => setTipoInsumo(e.target.value)}>
                <option value="">Todos</option>
                {tiposInsumo.map((t) => <option key={t} value={t}>{t}</option>)}
              </select>
            </label>
            <label className="pdc-selector">
              Unidad{' '}
              <select data-testid="pdc-visor-unidad" value={unidad} onChange={(e) => setUnidad(e.target.value)}>
                <option value="">Todas</option>
                {unidades.map((u) => <option key={u} value={u}>{u}</option>)}
              </select>
            </label>
            <label className="pdc-visor-modo">
              <input
                type="checkbox"
                data-testid="pdc-visor-plano"
                checked={plano}
                onChange={(e) => setPlano(e.target.checked)}
              />
              {' '}Ver como tabla
            </label>
            {(texto !== '' || tipoInsumo !== '' || unidad !== '') && (
              <button
                type="button"
                data-testid="pdc-visor-limpiar"
                onClick={() => { setTexto(''); setTipoInsumo(''); setUnidad('') }}
              >
                Limpiar filtros
              </button>
            )}
            <span data-testid="pdc-visor-conteo" className="pdc-visor-conteo">
              {filas.length} fila(s)
            </span>
          </div>

          <div style={{ height: 560 }} data-testid="pdc-visor-arbol">
            <AgGridReact<FilaVisor>
              // Remontar al cambiar de modo: si no, AG Grid reutiliza las celdas ya pintadas y
              // arrastra la sangría del árbol a la tabla en las filas que ya estaban en pantalla.
              key={plano ? 'tabla' : 'arbol'}
              theme={pdcTheme}
              rowData={filas}
              columnDefs={cols}
              getRowId={(p) => p.data.key}
              onCellClicked={onCellClicked}
              // Los filtros por columna solo tienen sentido sin jerarquía: en el árbol ordenarían y
              // esconderían filas dejando hijos sin su padre.
              defaultColDef={{ floatingFilter: plano, resizable: true }}
              tooltipShowDelay={350}
            />
          </div>
        </>
      )}
    </section>
  )
}
