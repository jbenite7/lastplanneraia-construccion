import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import {
  CIFRA, MODULOS_TABLA, TEXTO_LARGO, autoSizeStrategy, columnaMoneda, columnaTexto, defaultColDef,
  moneda, pdcTheme,
} from '../lib/agGrid'
import { PdcApiError, apiGet } from '../lib/api'
import { claseDelta, filasComparativoVisibles } from '../lib/comparativo'
import type { FilaComparativo } from '../lib/comparativo'
import { NIVELES_PRESUPUESTO, expandirHastaNivel } from '../lib/presupuestoTree'
import type { Comparativo, InsumoDiff, VersionPresupuesto } from '../lib/types'
import { etiquetaVersion } from '../lib/versionLabel'

// Mismo criterio que VisorPresupuesto.tsx: registro selectivo de módulos.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule, // cellClass condicional de la columna Δ (sobrecosto/ahorro)
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const signo = (v: number) => (v > 0 ? '+' : '') + v.toLocaleString('es-CO')

// Mismo vocabulario de niveles que el visor, menos el insumo: el diff jerárquico llega hasta la
// actividad. Ofrecer un nivel que esta pantalla no tiene sería prometer algo que no va a pasar.
const NIVELES_COMPARATIVO = NIVELES_PRESUPUESTO.filter((n) => n.etiqueta !== 'Insumo')
const NIVEL_ACTIVIDAD = NIVELES_COMPARATIVO[NIVELES_COMPARATIVO.length - 1].valor

export default function ComparativoPresupuesto() {
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [idA, setIdA] = useState<number | null>(null)
  const [idB, setIdB] = useState<number | null>(null)
  const [data, setData] = useState<Comparativo | null>(null)
  const [eje, setEje] = useState<'actividades' | 'insumos'>('insumos')
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  // Igual que el visor: abre desplegado y con selector de nivel, para que moverse entre las dos
  // pantallas no obligue a cambiar de idioma.
  const [nivel, setNivel] = useState<number>(NIVEL_ACTIVIDAD)
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
      .then((d) => setData(d))
      .catch((e) => {
        setData(null)
        setError(e instanceof PdcApiError ? e.message : e instanceof Error ? e.message : String(e))
      })
  }, [idA, idB])

  useEffect(() => {
    if (data) setExpandidos(expandirHastaNivel(data.actividades, nivel))
  }, [data, nivel])

  const filasAct = useMemo(
    () => (data ? filasComparativoVisibles(data.actividades, expandidos) : []),
    [data, expandidos],
  )

  const colsAct: ColDef<FilaComparativo>[] = useMemo(() => [
    {
      ...TEXTO_LARGO,
      field: 'descripcion', headerName: 'Actividad', minWidth: 320, cellClass: 'pdc-visor-descripcion',
      valueFormatter: (p) => {
        const f = p.data as FilaComparativo
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    columnaMoneda('valorA', 'Versión A'),
    columnaMoneda('valorB', 'Versión B'),
    {
      ...CIFRA, field: 'deltaValor', headerName: 'Δ', valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as FilaComparativo).estado),
    },
    { field: 'estado', headerName: 'Estado' },
  ], [])

  const colsIns: ColDef<InsumoDiff>[] = useMemo(() => [
    columnaTexto('descripcion', 'Insumo', 280),
    { field: 'tipoInsumo', headerName: 'Tipo' },
    { field: 'unidad', headerName: 'Und' },
    columnaMoneda('valorA', 'Versión A'),
    columnaMoneda('valorB', 'Versión B'),
    {
      ...CIFRA, field: 'deltaValor', headerName: 'Δ', valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as InsumoDiff).estado),
    },
    { field: 'estado', headerName: 'Estado' },
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
            {/* Solo en el eje jerárquico: la lista de insumos es plana y no tiene ramas que abrir. */}
            {eje === 'actividades' && (
              <label className="pdc-selector">
                Ver hasta{' '}
                <select data-testid="pdc-cmp-nivel" value={nivel} onChange={(e) => setNivel(Number(e.target.value))}>
                  {NIVELES_COMPARATIVO.map((n) => (
                    <option key={n.valor} value={n.valor}>{n.etiqueta}</option>
                  ))}
                </select>
              </label>
            )}
          </div>

          <div style={{ height: 520 }} data-testid="pdc-cmp-grid">
            {eje === 'actividades' ? (
              <AgGridReact<FilaComparativo>
                theme={pdcTheme} rowData={filasAct} columnDefs={colsAct} getRowId={(p) => p.data.key}
                onCellClicked={onCellClickedAct}
                defaultColDef={defaultColDef} autoSizeStrategy={autoSizeStrategy}
              />
            ) : (
              <AgGridReact<InsumoDiff>
                theme={pdcTheme} rowData={data.insumos} columnDefs={colsIns}
                getRowId={(p) => `${p.data.descripcionNorm}|${p.data.unidad}`}
                defaultColDef={defaultColDef} autoSizeStrategy={autoSizeStrategy}
              />
            )}
          </div>
        </>
      )}
    </section>
  )
}
