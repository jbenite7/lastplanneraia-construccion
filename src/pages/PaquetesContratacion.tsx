import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ClientSideRowModelModule, ModuleRegistry, RowStyleModule, TooltipModule, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { ColDef, ITooltipParams, RowClickedEvent } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { claveInsumo, estadoInicialPaquetes, paquetesReducer } from '../lib/paquetesState'
import { TIPOS_NEGOCIACION } from '../lib/types'
import type { ActividadesInsumo, InsumoPaquete, PaqueteCatalogo, ResumenPaquetes, SugerenciaPaquete } from '../lib/types'
import PaquetesAsistente from './PaquetesAsistente'

// Registro selectivo de módulos (no AllCommunityModule); ValidationModule solo en dev — patrón del repo.
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule,
  RowStyleModule,
  TooltipModule, // tooltip de actividades que requieren el insumo
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

/** Tooltip: actividades del presupuesto que requieren el insumo (códigos = amarre al cronograma en A4). */
function TooltipActividades(params: ITooltipParams<InsumoPaquete>) {
  const data = params.data
  const mapa = (params.context?.actividadesMap ?? {}) as Record<string, ActividadesInsumo>
  const info = data ? mapa[claveInsumo(data.descripcionNorm, data.unidad)] : undefined
  if (!info || info.total === 0) {
    return <div className="pdc-tt">Sin actividades registradas para este insumo.</div>
  }
  const ocultas = info.total - info.items.length
  return (
    <div className="pdc-tt">
      <div className="pdc-tt-cab">Requerido por {info.total} actividad(es):</div>
      <ul className="pdc-tt-lista">
        {info.items.map((a, i) => (
          <li key={`${a.codigo}-${i}`}>
            <span className="pdc-tt-cod">{a.codigo}</span>
            <span className="pdc-tt-act">{a.actividad}</span>
            <span className="pdc-tt-cant">{a.cantidad.toLocaleString('es-CO')} {data?.unidad}</span>
          </li>
        ))}
      </ul>
      {ocultas > 0 && <div className="pdc-tt-mas">y {ocultas} más…</div>}
    </div>
  )
}

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const moneda = (v: number | null | undefined) => (v == null ? '' : `$ ${v.toLocaleString('es-CO')}`)
const CONFIANZA_LABEL: Record<string, string> = { alta: 'alta', media: 'media', baja: 'baja' }
const tipoNegLabel = (v: string) => TIPOS_NEGOCIACION.find((t) => t.value === v)?.label ?? v

type Filtro = 'todos' | 'sin_asignar' | 'asignados' | 'omitidos'
type Modo = 'masivo' | 'asistente'

export default function PaquetesContratacion() {
  const [modo, setModo] = useState<Modo>('masivo')
  const [state, dispatch] = useReducer(paquetesReducer, estadoInicialPaquetes)
  const [insumos, setInsumos] = useState<InsumoPaquete[]>([])
  const [paquetes, setPaquetes] = useState<PaqueteCatalogo[]>([])
  const [resumen, setResumen] = useState<ResumenPaquetes | null>(null)
  const [actividadesMap, setActividadesMap] = useState<Record<string, ActividadesInsumo>>({})
  const [filtro, setFiltro] = useState<Filtro>('todos')
  const [agrupacion, setAgrupacion] = useState<string>('')
  const [sinVersion, setSinVersion] = useState(false)
  const [paqueteDestino, setPaqueteDestino] = useState<number | ''>('')
  const [nuevoNombre, setNuevoNombre] = useState('')
  const [nuevoTipo, setNuevoTipo] = useState(TIPOS_NEGOCIACION[0].value)

  const cargar = useCallback((f: Filtro) => {
    apiGet<{ version: unknown; insumos: InsumoPaquete[] }>(`/plan-compras/api/paquetes/insumos?filtro=${f}`)
      .then((d) => { setInsumos(d.insumos); setSinVersion(false) })
      .catch((e) => { setInsumos([]); if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinVersion(true) })
    apiGet<{ paquetes: PaqueteCatalogo[] }>('/plan-compras/api/paquetes')
      .then((d) => setPaquetes(d.paquetes))
      .catch(() => setPaquetes([]))
    apiGet<ResumenPaquetes>('/plan-compras/api/paquetes/resumen')
      .then(setResumen)
      .catch(() => setResumen(null))
    apiGet<{ mapa: Record<string, ActividadesInsumo> }>('/plan-compras/api/paquetes/insumo-actividades')
      .then((d) => setActividadesMap(d.mapa))
      .catch(() => setActividadesMap({}))
  }, [])
  useEffect(() => { if (modo === 'masivo') cargar(filtro) }, [cargar, filtro, modo])

  const agrupaciones = useMemo(
    () => [...new Set(insumos.map((i) => i.agrupacion).filter((a): a is string => !!a))].sort(),
    [insumos],
  )
  const visibles = useMemo(
    () => (agrupacion === '' ? insumos : insumos.filter((i) => i.agrupacion === agrupacion)),
    [insumos, agrupacion],
  )

  const cols = useMemo<ColDef<InsumoPaquete>[]>(() => [
    {
      headerName: '', width: 46, cellClass: 'pdc-paq-check', sortable: false,
      valueGetter: (p) => (p.data && state.seleccion.has(claveInsumo(p.data.descripcionNorm, p.data.unidad)) ? 1 : 0),
      valueFormatter: (p) => (p.value === 1 ? '✔' : ''),
    },
    {
      headerName: 'Insumo', field: 'descripcion', flex: 2, minWidth: 220,
      // Tooltip con las actividades que requieren el insumo (el valueGetter solo dispara el tooltip).
      tooltipValueGetter: (p) => (p.data ? p.data.descripcion : ''),
      tooltipComponent: TooltipActividades,
    },
    { headerName: 'Agrupación', field: 'agrupacion', flex: 1, minWidth: 130, valueFormatter: (p) => p.value ?? '—' },
    { headerName: 'Recurso', field: 'tipoRecurso', width: 120, valueFormatter: (p) => p.value ?? '—' },
    { headerName: 'Und', field: 'unidad', width: 78 },
    { headerName: 'Valor total', field: 'valorTotal', width: 140, type: 'rightAligned', valueFormatter: (p) => moneda(p.value) },
    {
      headerName: 'Destino', flex: 1, minWidth: 150,
      valueGetter: (p) => {
        if (!p.data) return ''
        if (p.data.omitido === 1) return '— Omitido —'
        return p.data.paqueteNombre ?? ''
      },
    },
    {
      headerName: 'Sugerencia', flex: 1, minWidth: 190, sortable: false,
      valueGetter: (p) => {
        if (!p.data) return ''
        const s = state.sugerencias.get(claveInsumo(p.data.descripcionNorm, p.data.unidad))
        return s ? `${s.paqueteNombre} · ${s.capa}/${CONFIANZA_LABEL[s.confianza]}` : ''
      },
    },
  ], [state.seleccion, state.sugerencias])

  const onRowClicked = (e: RowClickedEvent<InsumoPaquete>) => {
    if (!e.data) return
    dispatch({ type: 'TOGGLE_SEL', clave: claveInsumo(e.data.descripcionNorm, e.data.unidad) })
  }

  const seleccionados = useMemo(
    () => visibles.filter((i) => state.seleccion.has(claveInsumo(i.descripcionNorm, i.unidad))),
    [visibles, state.seleccion],
  )
  const insumosPayload = (lista: InsumoPaquete[]) => lista.map((i) => ({ descripcionNorm: i.descripcionNorm, unidad: i.unidad }))

  const refrescar = () => { cargar(filtro); dispatch({ type: 'LIMPIAR_SEL' }) }

  const onSugerir = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const d = await apiGet<{ version: unknown; sugerencias: SugerenciaPaquete[] }>('/plan-compras/api/paquetes/sugerencias')
      dispatch({ type: 'SUGERENCIAS_OK', sugerencias: d.sugerencias })
      if (d.sugerencias.length === 0) dispatch({ type: 'LISTO', mensaje: 'Sin sugerencias: no hay historial suficiente todavía.' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onAceptarSugeridos = async () => {
    // Agrupa las sugerencias por paquete y asigna en bloque; el humano ya las vio en la grilla.
    const porPaquete = new Map<number, { descripcionNorm: string; unidad: string }[]>()
    for (const s of state.sugerencias.values()) {
      const lista = porPaquete.get(s.paqueteId) ?? []
      lista.push({ descripcionNorm: s.descripcionNorm, unidad: s.unidad })
      porPaquete.set(s.paqueteId, lista)
    }
    if (porPaquete.size === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      let total = 0
      for (const [paqueteId, lista] of porPaquete) {
        const r = await apiPost<{ asignados: number }>('/plan-compras/api/paquetes/asignar', { insumos: lista, paqueteId })
        total += r.asignados
      }
      dispatch({ type: 'LIMPIAR_SUGERENCIAS' })
      dispatch({ type: 'LISTO', mensaje: `${total} sugerencia(s) aceptada(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onAsignar = async () => {
    if (paqueteDestino === '' || seleccionados.length === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ asignados: number }>('/plan-compras/api/paquetes/asignar', {
        insumos: insumosPayload(seleccionados), paqueteId: paqueteDestino,
      })
      dispatch({ type: 'LISTO', mensaje: `${r.asignados} insumo(s) asignado(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onOmitir = async () => {
    if (seleccionados.length === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ omitidos: number }>('/plan-compras/api/paquetes/omitir', { insumos: insumosPayload(seleccionados) })
      dispatch({ type: 'LISTO', mensaje: `${r.omitidos} insumo(s) omitido(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onDesasignar = async () => {
    if (seleccionados.length === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ desasignados: number }>('/plan-compras/api/paquetes/desasignar', { insumos: insumosPayload(seleccionados) })
      dispatch({ type: 'LISTO', mensaje: `${r.desasignados} insumo(s) devuelto(s) a sin asignar.` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onCrearPaquete = async () => {
    if (nuevoNombre.trim() === '') return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ paquete: PaqueteCatalogo & { existente: number } }>('/plan-compras/api/paquetes', {
        nombre: nuevoNombre, tipoNegociacion: nuevoTipo,
      })
      setNuevoNombre('')
      setPaqueteDestino(r.paquete.id)
      dispatch({ type: 'LISTO', mensaje: r.paquete.existente === 1 ? 'El paquete ya existía; queda seleccionado.' : 'Paquete creado.' })
      cargar(filtro)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const barra = (
    <div className="pdc-paq-modos" role="tablist">
      <button type="button" role="tab" aria-selected={modo === 'masivo'} className={modo === 'masivo' ? 'is-activo' : ''} onClick={() => setModo('masivo')}>
        Modo masivo
      </button>
      <button type="button" role="tab" aria-selected={modo === 'asistente'} className={modo === 'asistente' ? 'is-activo' : ''} onClick={() => setModo('asistente')}>
        Asistente paso a paso
      </button>
    </div>
  )

  if (sinVersion) {
    return (
      <section className="pdc-bloque">
        <h1>Paquetes de contratación</h1>
        <p className="pdc-vacio">El proyecto no tiene un presupuesto importado. Importa un presupuesto para empezar a empaquetar.</p>
      </section>
    )
  }

  if (modo === 'asistente') {
    return (
      <section className="pdc-bloque pdc-paquetes">
        <header className="pdc-paq-header">
          <div>
            <h1>Paquetes de contratación</h1>
            <p className="pdc-sub">Agrupa los insumos del presupuesto activo. Meta: 100% asignado u omitido.</p>
          </div>
          {resumen && <Cobertura resumen={resumen} />}
        </header>
        {barra}
        <PaquetesAsistente onCambio={() => cargar(filtro)} actividadesMap={actividadesMap} />
      </section>
    )
  }

  return (
    <section className="pdc-bloque pdc-paquetes">
      <header className="pdc-paq-header">
        <div>
          <h1>Paquetes de contratación</h1>
          <p className="pdc-sub">Agrupa los insumos del presupuesto activo. Meta: 100% asignado u omitido.</p>
        </div>
        {resumen && <Cobertura resumen={resumen} />}
      </header>

      {barra}

      {state.mensaje && <div className="pdc-info" role="status">{state.mensaje}</div>}

      <div className="pdc-paq-toolbar">
        <select data-testid="pdc-paq-filtro" aria-label="Filtrar por estado" value={filtro} onChange={(e) => setFiltro(e.target.value as Filtro)}>
          <option value="todos">Todos</option>
          <option value="sin_asignar">Sin asignar</option>
          <option value="asignados">Asignados</option>
          <option value="omitidos">Omitidos</option>
        </select>
        <select aria-label="Filtrar por agrupación" value={agrupacion} onChange={(e) => setAgrupacion(e.target.value)}>
          <option value="">Todas las agrupaciones</option>
          {agrupaciones.map((a) => <option key={a} value={a}>{a}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-sugerir" disabled={state.ocupado} onClick={onSugerir}>
          Sugerir asignaciones
        </button>
        {state.sugerencias.size > 0 && (
          <button type="button" data-testid="pdc-paq-aceptar-sugeridos" className="pdc-paq-primario" disabled={state.ocupado} onClick={onAceptarSugeridos}>
            Aceptar {state.sugerencias.size} sugerida(s)
          </button>
        )}
      </div>

      <div className="pdc-paq-acciones">
        <button type="button" data-testid="pdc-paq-sel-todos" disabled={visibles.length === 0}
          onClick={() => dispatch({ type: 'SEL_TODOS', claves: visibles.map((i) => claveInsumo(i.descripcionNorm, i.unidad)) })}>
          Seleccionar visibles
        </button>
        <button type="button" data-testid="pdc-paq-limpiar-sel" disabled={seleccionados.length === 0} onClick={() => dispatch({ type: 'LIMPIAR_SEL' })}>
          Limpiar
        </button>
        <span className="pdc-paq-sel">{seleccionados.length} seleccionado(s)</span>
        <select data-testid="pdc-paq-select-paquete" aria-label="Paquete destino" value={paqueteDestino} onChange={(e) => setPaqueteDestino(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">Paquete destino…</option>
          {paquetes.map((p) => <option key={p.id} value={p.id}>{p.nombre}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-asignar" className="pdc-paq-primario" disabled={state.ocupado || paqueteDestino === '' || seleccionados.length === 0} onClick={onAsignar}>
          Asignar a paquete
        </button>
        <button type="button" data-testid="pdc-paq-omitir" disabled={state.ocupado || seleccionados.length === 0} onClick={onOmitir}>
          Omitir
        </button>
        <button type="button" data-testid="pdc-paq-desasignar" disabled={state.ocupado || seleccionados.length === 0} onClick={onDesasignar}>
          Devolver a sin asignar
        </button>
      </div>

      <div className="pdc-paq-crear">
        <input data-testid="pdc-paq-crear-nombre" placeholder="Crear paquete nuevo…" value={nuevoNombre} onChange={(e) => setNuevoNombre(e.target.value)} />
        <select data-testid="pdc-paq-crear-tipo" aria-label="Tipo de negociación" value={nuevoTipo} onChange={(e) => setNuevoTipo(e.target.value)}>
          {TIPOS_NEGOCIACION.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-crear" disabled={state.ocupado || nuevoNombre.trim() === ''} onClick={onCrearPaquete}>
          Crear paquete
        </button>
      </div>

      <div data-testid="pdc-paq-grid" className="pdc-grid-wrap">
        <AgGridReact<InsumoPaquete>
          theme={pdcTheme}
          rowData={visibles}
          columnDefs={cols}
          context={{ actividadesMap }}
          tooltipShowDelay={350}
          onRowClicked={onRowClicked}
          domLayout="autoHeight"
          suppressCellFocus
          getRowClass={(p) => (p.data?.omitido === 1 ? 'pdc-paq-fila-omitida' : undefined)}
        />
      </div>

      <h2>Paquetes con insumos</h2>
      <ul data-testid="pdc-paq-paquetes" className="pdc-paq-lista">
        {(resumen?.porPaquete ?? []).map((p) => (
          <li key={p.paqueteId}>
            <strong>{p.nombre}</strong>
            <span className="pdc-paq-tag">{tipoNegLabel(p.tipoNegociacion)}</span>
            <span className="pdc-paq-meta">{p.insumos} insumo(s) · {moneda(p.subtotal)}</span>
          </li>
        ))}
        {(resumen?.porPaquete ?? []).length === 0 && <li className="pdc-vacio">Aún no hay insumos asignados a ningún paquete.</li>}
      </ul>
    </section>
  )
}

function Cobertura({ resumen }: { resumen: ResumenPaquetes }) {
  return (
    <div data-testid="pdc-paq-cobertura" className="pdc-paq-cobertura">
      <div className="pdc-paq-cobertura-num">{resumen.cobertura}%</div>
      <div className="pdc-paq-cobertura-detalle">
        {resumen.asignados} asignados + {resumen.omitidos} omitidos de {resumen.total}
      </div>
      <div className="pdc-paq-barra"><div className="pdc-paq-barra-fill" style={{ transform: `scaleX(${resumen.cobertura / 100})` }} /></div>
    </div>
  )
}
