import { useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import {
  ModuleRegistry, NumberFilterModule, TextFilterModule,
  TooltipModule, ValidationModule,
} from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import {
  COLUMNA_CORTA, MODULOS_TABLA, columnasQueCaben, usaAnchoContenedor, TEXTO_LARGO, ajusteDeAncho, autoSizeStrategy, columnaMoneda, columnaNumero, defaultColDef, pdcTheme, vacioTabla
} from '../lib/agGrid'
import { PdcApiError, apiGet } from '../lib/api'
import { NIVELES_PRESUPUESTO, NIVEL_INSUMO, expandirHastaNivel, filasVisibles } from '../lib/presupuestoTree'
import type { FilaVisor } from '../lib/presupuestoTree'
import type { ArbolPresupuesto, VersionPresupuesto } from '../lib/types'
import { etiquetaVersion } from '../lib/versionLabel'
import { plural } from '../lib/texto'

// Mismo criterio que ImportarPresupuesto.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  // Filtros por columna del modo tabla: el registro es selectivo, así que hay que pedirlos. El
  // filtro por valores con casillas (Set Filter) es de AG Grid Enterprise y aquí se sustituye con
  // los desplegables de Tipo insumo y Unidad de la barra de herramientas.
  TextFilterModule,
  NumberFilterModule,
  TooltipModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

export default function VisorPresupuesto() {
  // `?version=N` lo pone el historial al hacer clic en una fila: se llega aquí con esa versión ya
  // cargada, sin tener que volver a elegirla en el selector. Sin parámetro manda la activa.
  const [params] = useSearchParams()
  const versionDeLaRuta = Number(params.get('version')) || null

  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [versionId, setVersionId] = useState<number | null>(versionDeLaRuta)
  const [arbol, setArbol] = useState<ArbolPresupuesto | null>(null)
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  // Hasta qué nivel se ve. Arranca en «Insumo» —el árbol abría colapsado en dos filas y había que
  // ir abriendo carpetas a mano para llegar a lo que se venía a mirar. Son ~1.343 filas en la
  // versión activa de Da Porto y AG Grid las virtualiza sin despeinarse (medido).
  const [nivel, setNivel] = useState<number>(NIVEL_INSUMO)
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

  // Elegir un nivel siembra el conjunto de ramas abiertas; a partir de ahí el clic manual manda,
  // hasta que se elija otro nivel o llegue otro árbol. Los dos gestos hablan el mismo idioma
  // (códigos), así que no se estorban.
  useEffect(() => {
    if (arbol) setExpandidos(expandirHastaNivel(arbol.items, nivel))
  }, [arbol, nivel])

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
    // `minWidth`: el código de un insumo de cuarto nivel («01.01.01.01») es la referencia con la
    // que se busca la partida en el presupuesto de origen; recortado a «01.01.0…» no sirve.
    { field: 'codigo', headerName: 'Código', filter: plano, sortable: plano, minWidth: 116 },
    ...(plano ? [{
      ...TEXTO_LARGO, field: 'ruta', headerName: 'Dónde está', minWidth: 240, filter: true, sortable: true,
      tooltipValueGetter: (p) => String(p.value ?? ''),
    } as ColDef<FilaVisor>] : []),
    {
      ...TEXTO_LARGO,
      field: 'descripcion', headerName: 'Descripción', minWidth: 320,
      // `pre-wrap` (ver styles.css): conserva la sangría del árbol —que se dibuja con espacios— y
      // además envuelve. Con el `pre` de antes, una descripción larga se recortaba sin remedio.
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
    // «Tipo» a secas: el dato es una letra («S», «M») y el rótulo largo pedía el triple de ancho
    // del que necesita, se lo quitaba a la descripción y aun así salía recortado.
    { ...COLUMNA_CORTA, colId: 'tipo', field: 'tipoInsumo', headerName: 'Tipo', headerTooltip: 'Tipo de insumo', filter: plano, sortable: plano },
    { ...COLUMNA_CORTA, colId: 'unidad', field: 'unidad', headerName: 'Und', filter: plano, sortable: plano },
    { ...columnaNumero('cantidad', 'Cantidad'), colId: 'cantidad', filter: plano ? 'agNumberColumnFilter' : false, sortable: plano },
    {
      ...columnaMoneda('valorUnitario', 'Vr. unitario'), colId: 'vrUnitario',
      filter: plano ? 'agNumberColumnFilter' : false, sortable: plano,
    },
    {
      ...columnaMoneda('valorTotal', 'Valor total'),
      filter: plano ? 'agNumberColumnFilter' : false, sortable: plano,
    },
  ], [plano])

  // Qué se sacrifica primero cuando el hueco no da: «Tipo» y «Und» tienen su propio filtro arriba,
  // y el valor unitario se deduce del total y la cantidad. El código, la descripción y el valor
  // total no se esconden nunca: son la fila.
  const [refGrid, anchoGrid] = usaAnchoContenedor()
  const colsVisibles = useMemo(
    () => columnasQueCaben(cols, anchoGrid, ['tipo', 'unidad', 'vrUnitario', 'cantidad']),
    [cols, anchoGrid],
  )

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
          <p>Vista del presupuesto importado. Elige hasta qué nivel verlo, o haz clic en una fila para abrirla.</p>
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
            {/* El nivel solo tiene sentido con jerarquía: en modo tabla no hay ramas que abrir. */}
            {!plano && (
              <label className="pdc-selector">
                Ver hasta{' '}
                <select
                  data-testid="pdc-visor-nivel"
                  value={nivel}
                  onChange={(e) => setNivel(Number(e.target.value))}
                >
                  {NIVELES_PRESUPUESTO.map((n) => (
                    <option key={n.valor} value={n.valor}>{n.etiqueta}</option>
                  ))}
                </select>
              </label>
            )}
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
              {plural(filas.length, 'fila')}
            </span>
          </div>

          <div className="pdc-grid" data-testid="pdc-visor-arbol" ref={refGrid}>
            <AgGridReact<FilaVisor>
              // Remontar al cambiar de modo: si no, AG Grid reutiliza las celdas ya pintadas y
              // arrastra la sangría del árbol a la tabla en las filas que ya estaban en pantalla.
              key={plano ? 'tabla' : 'arbol'}
              theme={pdcTheme}
              rowData={filas}
              overlayNoRowsTemplate={vacioTabla("Ninguna fila del presupuesto coincide con los filtros puestos.")}
              columnDefs={colsVisibles}
              getRowId={(p) => p.data.key}
              onCellClicked={onCellClicked}
              // Los filtros por columna solo tienen sentido sin jerarquía: en el árbol ordenarían y
              // esconderían filas dejando hijos sin su padre.
              defaultColDef={{ ...defaultColDef, floatingFilter: plano }}
              autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
              tooltipShowDelay={350}
            />
          </div>
        </>
      )}
    </section>
  )
}
