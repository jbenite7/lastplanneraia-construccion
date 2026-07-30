import { useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import {
  CIFRA, MODULOS_TABLA, columnasQueCaben, usaAnchoContenedor, TEXTO_LARGO, ajusteDeAncho, autoSizeStrategy, columnaMoneda, columnaTexto, defaultColDef,
  moneda, pdcTheme, vacioTabla
} from '../lib/agGrid'
import { PdcApiError, apiGet } from '../lib/api'
import { filtraPorTexto } from '../lib/texto'
import { claseDelta, filasComparativoVisibles } from '../lib/comparativo'
import type { FilaComparativo } from '../lib/comparativo'
import { NIVELES_PRESUPUESTO, expandirHastaNivel } from '../lib/presupuestoTree'
import type { Comparativo, InsumoDiff, LadoComparativo, VersionPresupuesto } from '../lib/types'
import { etiquetaVersion } from '../lib/versionLabel'
import BotonAyuda from '../components/BotonAyuda'

// Mismo criterio que VisorPresupuesto.tsx: registro selectivo de módulos.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule, // cellClass condicional de la columna Δ (sobrecosto/ahorro)
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

// Sin decimales, igual que `moneda`: la columna Δ vive al lado de las dos de dinero y con decimales
// variables las tres se leían desalineadas entre sí.
const signo = (v: number) => (v > 0 ? '+' : '')
  + v.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })

// Mismo vocabulario de niveles que el visor, menos el insumo: el diff jerárquico llega hasta la
// actividad. Ofrecer un nivel que esta pantalla no tiene sería prometer algo que no va a pasar.
const NIVELES_COMPARATIVO = NIVELES_PRESUPUESTO.filter((n) => n.etiqueta !== 'Insumo')
const NIVEL_ACTIVIDAD = NIVELES_COMPARATIVO[NIVELES_COMPARATIVO.length - 1].valor

export default function ComparativoPresupuesto() {
  // `?a=N&b=M` los pone el historial al pulsar «Comparar» con dos versiones marcadas: se llega con
  // las dos ya enfrentadas. Sin parámetros vale la preselección de siempre (activa vs anterior).
  const [params] = useSearchParams()
  const aDeLaRuta = Number(params.get('a')) || null
  const bDeLaRuta = Number(params.get('b')) || null

  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [idA, setIdA] = useState<number | null>(aDeLaRuta)
  const [idB, setIdB] = useState<number | null>(bDeLaRuta)
  const [data, setData] = useState<Comparativo | null>(null)
  const [eje, setEje] = useState<'actividades' | 'insumos'>('insumos')
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  // Igual que el visor: abre desplegado y con selector de nivel, para que moverse entre las dos
  // pantallas no obligue a cambiar de idioma.
  const [nivel, setNivel] = useState<number>(NIVEL_ACTIVIDAD)
  const [error, setError] = useState<string | null>(null)
  const [busca, setBusca] = useState('')

  useEffect(() => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => {
        setVersiones(d.versiones)
        // Preselección: B = activa (o la más reciente), A = la inmediatamente anterior. Solo
        // cuando no vinieron dos versiones por la ruta: si el usuario las eligió en el historial,
        // pisarlas con la preselección le borraría la elección delante de los ojos.
        if (aDeLaRuta !== null && bDeLaRuta !== null) return
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
      ...CIFRA, field: 'deltaValor', headerName: 'Diferencia', valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as FilaComparativo).estado),
    },
    { colId: 'estado', field: 'estado', headerName: 'Estado' },
  ], [])

  const colsIns: ColDef<InsumoDiff>[] = useMemo(() => [
    columnaTexto('descripcion', 'Insumo', 280),
    { colId: 'tipo', field: 'tipoInsumo', headerName: 'Tipo' },
    { colId: 'unidad', field: 'unidad', headerName: 'Und' },
    columnaMoneda('valorA', 'Versión A'),
    columnaMoneda('valorB', 'Versión B'),
    {
      ...CIFRA, field: 'deltaValor', headerName: 'Diferencia', valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as InsumoDiff).estado),
    },
    { colId: 'estado', field: 'estado', headerName: 'Estado' },
  ], [])

  // «Estado» repite lo que ya dice la diferencia (0 = igual, y el color marca sobrecosto o ahorro),
  // así que es lo primero que sobra; «Tipo» y «Und» describen el insumo, no el cambio.
  const [refGrid, anchoGrid] = usaAnchoContenedor()
  const visiblesAct = useMemo(() => columnasQueCaben(colsAct, anchoGrid, ['estado', 'tipo', 'unidad']), [colsAct, anchoGrid])
  const visiblesIns = useMemo(() => columnasQueCaben(colsIns, anchoGrid, ['estado', 'tipo', 'unidad']), [colsIns, anchoGrid])

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
      {/* «(obsoleta)» va en la opción para que la advertencia empiece antes de elegir, no después. */}
      {versiones.map((v) => (
        <option key={v.id} value={v.id}>
          {etiquetaVersion(v)}{v.activa ? ' (activa)' : ''}{v.obsoleta ? ' (obsoleta)' : ''}
        </option>
      ))}
    </select>
  )

  /**
   * Versiones elegidas que no son confiables. Se calcula sobre `data` (no sobre los selectores) para
   * que el aviso corresponda siempre al diff que se está viendo.
   */
  const ladosObsoletos = data
    ? [data.versionA, data.versionB].filter((l) => l.obsoleta === 1)
    : []

  /** Misma etiqueta que el selector, para que el aviso nombre la versión como el usuario la eligió. */
  const etiquetaLado = (lado: LadoComparativo): string => {
    const v = versiones.find((x) => x.id === lado.id)
    return v ? etiquetaVersion(v) : (lado.label || `Versión #${lado.id}`)
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <div className="pdc-titulo-fila"><h1>Comparativo de versiones</h1><BotonAyuda pantalla="comparar" /></div>
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

      {/* Antes del resumen a propósito: el Δ de DAPORTO es de −$45 mil millones y se lee como una
          caída del presupuesto, cuando en realidad es el rastro de un import defectuoso. */}
      {/* Plegable: el titular —lo accionable, «no te fíes de esta comparación»— sigue a la vista y
          se anuncia igual, pero el motivo son dos párrafos de seis renglones que no cambian entre
          visitas y empujaban la tabla fuera de la pantalla. */}
      {ladosObsoletos.length > 0 && (
        <details className="pdc-aviso-obsoleta" role="alert" data-testid="pdc-cmp-aviso-obsoleta">
          <summary>
            <strong>
              {ladosObsoletos.length === 1
                ? `La versión «${etiquetaLado(ladosObsoletos[0])}» no es confiable.`
                : 'Las dos versiones que estás comparando no son confiables.'}
            </strong>{' '}
            <span className="pdc-ayuda">Ver por qué</span>
          </summary>
          {ladosObsoletos.map((l) => (
            <p key={l.id}>
              {ladosObsoletos.length > 1 && <b>{etiquetaLado(l)}: </b>}
              {l.obsoletaMotivo}
            </p>
          ))}
        </details>
      )}

      {data && (
        <>
          <div className="pdc-cmp-resumen" data-testid="pdc-cmp-resumen">
            <span>{moneda(data.resumen.costoA)} → {moneda(data.resumen.costoB)}</span>
            <span className={claseDelta(data.resumen.delta, 'modificado')}>
              Diferencia {signo(data.resumen.delta)}
            </span>
            <span className="pdc-cmp-sobrecosto">Sobrecostos {moneda(data.resumen.sobrecostos)}</span>
            {/* Sin signo: «Ahorros $ -46.629.280.887» se lee al revés de lo que significa. La
                palabra ya dice la dirección; el menos solo la contradecía. El Δ de al lado sí lo
                conserva, porque ahí el signo es la información. */}
            <span className="pdc-cmp-ahorro">Ahorros {moneda(Math.abs(data.resumen.ahorros))}</span>
            <span>{data.resumen.nuevos} nuevos · {data.resumen.eliminados} eliminados · {data.resumen.modificados} modificados</span>
          </div>
          <p className="pdc-ayuda" data-testid="pdc-cmp-formula">La diferencia es lo que subió menos lo que bajó.</p>

          <div className="pdc-cmp-toggle">
            <button type="button" className={eje === 'insumos' ? 'activo' : ''} onClick={() => setEje('insumos')} data-testid="pdc-cmp-eje-insumos">Insumos</button>
            <button type="button" className={eje === 'actividades' ? 'activo' : ''} onClick={() => setEje('actividades')} data-testid="pdc-cmp-eje-actividades">Actividades</button>
            {/* Solo en el eje jerárquico: la lista de insumos es plana y no tiene ramas que abrir. */}
            <input
              className="pdc-buscador"
              data-testid="pdc-cmp-buscar"
              placeholder={eje === 'insumos' ? 'Buscar insumo…' : 'Buscar actividad…'}
              aria-label="Buscar en el comparativo"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
            />
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

          <div className="pdc-grid" data-testid="pdc-cmp-grid" ref={refGrid}>
            {eje === 'actividades' ? (
              <AgGridReact<FilaComparativo>
                theme={pdcTheme} rowData={filtraPorTexto(filasAct, busca, (f) => f.descripcion)} columnDefs={visiblesAct} getRowId={(p) => p.data.key}
                overlayNoRowsTemplate={vacioTabla("Estas dos versiones no cambiaron ninguna actividad.")}
                onCellClicked={onCellClickedAct}
                defaultColDef={defaultColDef} autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
              />
            ) : (
              <AgGridReact<InsumoDiff>
                theme={pdcTheme} rowData={filtraPorTexto(data.insumos, busca, (i) => i.descripcion)} columnDefs={visiblesIns}
                overlayNoRowsTemplate={vacioTabla("Estas dos versiones no cambiaron ningún insumo.")}
                getRowId={(p) => `${p.data.descripcionNorm}|${p.data.unidad}`}
                defaultColDef={defaultColDef} autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
              />
            )}
          </div>
        </>
      )}
    </section>
  )
}
