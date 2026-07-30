import { useCallback, useEffect, useMemo, useReducer, useRef, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowStyleModule, TooltipModule, ValidationModule } from 'ag-grid-community'
import type { ColDef, ITooltipParams, RowClickedEvent } from 'ag-grid-community'
import {
  COLUMNA_CORTA, MIN_WIDTH_PALABRA_LARGA, MODULOS_TABLA, TEXTO_LARGO, ajusteDeAncho, autoSizeStrategy, columnaMoneda, columnaTexto,
  columnasQueCaben, defaultColDef, moneda, pdcTheme, usaAnchoContenedor, vacioTabla
} from '../lib/agGrid'
import Pestanas, { PanelPestana } from '../components/Pestanas'
import SubpaquetesPanel from '../components/SubpaquetesPanel'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { ACCION_PROPONER, claveInsumo, estaCerradoPorValor, estadoInicialPaquetes, filtroInicial, muestraTipoNegociacion, paquetesReducer } from '../lib/paquetesState'
import type { FiltroPaquetes } from '../lib/paquetesState'
import { MODALIDADES, TIPOS_NEGOCIACION } from '../lib/types'
import type { ActividadesInsumo, InsumoPaquete, PaqueteCatalogo, ResumenPaquetes, SugerenciaPaquete } from '../lib/types'
import PaquetesAsistente from './PaquetesAsistente'
import { contarInsumos, filtraPorTexto, plural } from '../lib/texto'

// Registro selectivo de módulos (no AllCommunityModule); ValidationModule solo en dev — patrón del repo.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
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
      <div className="pdc-tt-cab">Requerido por {plural(info.total, 'actividad', 'actividades')}:</div>
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

const CONFIANZA_LABEL: Record<string, string> = { alta: 'alta', media: 'media', baja: 'baja' }
const tipoNegLabel = (v: string) => TIPOS_NEGOCIACION.find((t) => t.value === v)?.label ?? v
const modalidadLabel = (v?: string) => MODALIDADES.find((m) => m.value === v)?.label ?? v ?? ''
// Etiquetas humanas de la fuente de cada propuesta del sembrado.
const FUENTE_LABEL: Record<string, string> = {
  ia: 'Experto (IA)', exacta: 'Histórico', reglas: 'Reglas', tokens: 'Similitud', indirectos: 'Indirectos', agrupacion: 'Agrupación',
}
const FUENTES_ORDEN = ['reglas', 'ia', 'indirectos', 'exacta', 'tokens', 'agrupacion']

type Filtro = FiltroPaquetes
type Modo = 'masivo' | 'asistente' | 'paquetes'

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
  const [nuevaModalidad, setNuevaModalidad] = useState(MODALIDADES[0].value)
  const [buscaPaquete, setBuscaPaquete] = useState('')
  // Qué paquete tiene abierto su panel de lotes. Uno a la vez: abrir varios llena la pantalla de
  // tablas y la decisión de partir se toma paquete por paquete, no comparando cinco.
  const [loteAbierto, setLoteAbierto] = useState<number | null>(null)
  // El filtro de apertura se decide una sola vez, cuando llega el primer resumen: en el primer
  // render todavía no se sabe cuántos insumos faltan. A partir de ahí manda el usuario — volver a
  // aplicarlo en cada recarga le pisaría el filtro que acabara de elegir a mano.
  const filtroDecidido = useRef(false)

  const cargar = useCallback((f: Filtro) => {
    apiGet<{ version: unknown; insumos: InsumoPaquete[] }>(`/plan-compras/api/paquetes/insumos?filtro=${f}`)
      .then((d) => { setInsumos(d.insumos); setSinVersion(false) })
      .catch((e) => { setInsumos([]); if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinVersion(true) })
    apiGet<{ paquetes: PaqueteCatalogo[] }>('/plan-compras/api/paquetes')
      .then((d) => setPaquetes(d.paquetes))
      .catch(() => setPaquetes([]))
    apiGet<ResumenPaquetes>('/plan-compras/api/paquetes/resumen')
      .then((r) => {
        setResumen(r)
        if (!filtroDecidido.current) {
          filtroDecidido.current = true
          setFiltro(filtroInicial({ sinAsignar: r.total - r.asignados - r.omitidos, total: r.total }))
        }
      })
      .catch(() => setResumen(null))
    apiGet<{ mapa: Record<string, ActividadesInsumo> }>('/plan-compras/api/paquetes/insumo-actividades')
      .then((d) => setActividadesMap(d.mapa))
      .catch(() => setActividadesMap({}))
  }, [])
  // El asistente trae sus propios datos; las otras dos pestañas leen de la misma carga.
  useEffect(() => { if (modo !== 'asistente') cargar(filtro) }, [cargar, filtro, modo])

  const agrupaciones = useMemo(
    () => [...new Set(insumos.map((i) => i.agrupacion).filter((a): a is string => !!a))].sort(),
    [insumos],
  )
  const visibles = useMemo(
    () => (agrupacion === '' ? insumos : insumos.filter((i) => i.agrupacion === agrupacion)),
    [insumos, agrupacion],
  )

  const cerradoPorValor = estaCerradoPorValor(resumen?.coberturaValor)
  // Lo que sigue sin destino pese al 100 % por valor: son los insumos de $ 0, que existen.
  const sueltos = useMemo(() => {
    const libres = insumos.filter((i) => i.omitido !== 1 && i.paqueteNombre == null)
    return { cuantos: libres.length, valor: libres.reduce((a, i) => a + i.valorTotal, 0) }
  }, [insumos])

  const cols = useMemo<ColDef<InsumoPaquete>[]>(() => [
    {
      headerName: '', width: 46, cellClass: 'pdc-paq-check', sortable: false, suppressAutoSize: true,
      valueGetter: (p) => (p.data && state.seleccion.has(claveInsumo(p.data.descripcionNorm, p.data.unidad)) ? 1 : 0),
      valueFormatter: (p) => (p.value === 1 ? '✔' : ''),
    },
    {
      ...TEXTO_LARGO,
      headerName: 'Insumo', field: 'descripcion', flex: 2, minWidth: 220,
      // Tooltip con las actividades que requieren el insumo (el valueGetter solo dispara el tooltip).
      tooltipValueGetter: (p) => (p.data ? p.data.descripcion : ''),
      tooltipComponent: TooltipActividades,
    },
    { ...columnaTexto('agrupacion', 'Agrupación', MIN_WIDTH_PALABRA_LARGA), colId: 'agrupacion', valueFormatter: (p) => p.value ?? '—' },
    { ...COLUMNA_CORTA, headerName: 'Recurso', field: 'tipoRecurso', colId: 'recurso', minWidth: 96, valueFormatter: (p) => p.value ?? '—' },
    { ...COLUMNA_CORTA, colId: 'unidad', headerName: 'Und', field: 'unidad' },
    columnaMoneda('valorTotal', 'Valor total'),
    {
      ...TEXTO_LARGO,
      headerName: 'Destino', colId: 'destino', minWidth: 150,
      valueGetter: (p) => {
        if (!p.data) return ''
        if (p.data.omitido === 1) return '— Omitido —'
        return p.data.paqueteNombre ?? ''
      },
    },
    {
      ...TEXTO_LARGO,
      headerName: 'Sugerencia', colId: 'sugerencia', minWidth: 190, sortable: false,
      valueGetter: (p) => {
        if (!p.data) return ''
        const s = state.sugerencias.get(claveInsumo(p.data.descripcionNorm, p.data.unidad))
        return s ? `${s.paqueteNombre} · ${FUENTE_LABEL[s.capa] ?? s.capa}/${CONFIANZA_LABEL[s.confianza]}` : ''
      },
    },
  ], [state.seleccion, state.sugerencias])

  // Por debajo de 1200 px se esconden «Agrupación» y «Recurso» — lo prescindible de esta pantalla.
  // «Destino» y «Sugerencia» nunca: son el motivo por el que se entra aquí.
  const [refGrid, anchoGrid] = usaAnchoContenedor()
  const colsVisibles = useMemo(
    () => columnasQueCaben(cols, anchoGrid, ['agrupacion', 'recurso', 'unidad']),
    [cols, anchoGrid],
  )

  const onRowClicked = (e: RowClickedEvent<InsumoPaquete>) => {
    if (!e.data) return
    dispatch({ type: 'TOGGLE_SEL', clave: claveInsumo(e.data.descripcionNorm, e.data.unidad) })
  }

  const seleccionados = useMemo(
    () => visibles.filter((i) => state.seleccion.has(claveInsumo(i.descripcionNorm, i.unidad))),
    [visibles, state.seleccion],
  )
  const insumosPayload = (lista: InsumoPaquete[]) => lista.map((i) => ({ descripcionNorm: i.descripcionNorm, unidad: i.unidad }))

  // Por qué está apagado un botón. Vacío cuando está encendido: un `title` que sobra estorba.
  const faltaSeleccion = seleccionados.length === 0 ? 'Marca al menos un insumo de la tabla.' : ''
  const faltaParaAsignar = seleccionados.length === 0
    ? 'Marca al menos un insumo de la tabla y elige el paquete destino.'
    : paqueteDestino === '' ? 'Elige el paquete destino.' : ''

  // Qué paquetes usa ya este proyecto y por cuánto: el asistente los pone arriba del desplegable,
  // porque al corregir una propuesta el destino correcto casi siempre es uno que la obra ya usa.
  const usadosEnProyecto = useMemo(
    () => new Map((resumen?.porPaquete ?? []).map((p) => [p.paqueteId, p.subtotal])),
    [resumen],
  )

  // Resumen de la propuesta por fuente (para el preview).
  const resumenSembrado = useMemo(() => {
    const porFuente: Record<string, number> = {}
    for (const s of state.sugerencias.values()) porFuente[s.capa] = (porFuente[s.capa] ?? 0) + 1
    return { total: state.sugerencias.size, porFuente }
  }, [state.sugerencias])

  const refrescar = () => { cargar(filtro); dispatch({ type: 'LIMPIAR_SEL' }) }

  // El único botón de propuestas (ver ACCION_PROPONER): pide sugerencias y no escribe nada. Lo que
  // escribe es «Aceptar N sugeridas», más abajo, y solo cuando la persona lo pulsa.
  const onProponer = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const d = await apiGet<{ version: unknown; sugerencias: SugerenciaPaquete[] }>(ACCION_PROPONER.endpoint)
      dispatch({ type: 'SUGERENCIAS_OK', sugerencias: d.sugerencias })
      if (d.sugerencias.length === 0) dispatch({ type: 'LISTO', mensaje: 'No hay propuestas: todo está asignado o no hay señales suficientes.' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onAceptarSugeridos = async () => {
    // Agrupa por paquete + capa + confianza y asigna en bloque, conservando la procedencia: aceptar
    // una sugerencia tal cual es un acierto del motor y así se contabiliza, aunque quede confirmada
    // por el humano que la revisó en la grilla.
    const grupos = new Map<string, { paqueteId: number; capa: string; confianza: string; evidencia: string; insumos: { descripcionNorm: string; unidad: string }[] }>()
    for (const s of state.sugerencias.values()) {
      const clave = `${s.paqueteId}|${s.capa}|${s.confianza}`
      const g = grupos.get(clave) ?? { paqueteId: s.paqueteId, capa: s.capa, confianza: s.confianza, evidencia: s.evidencia, insumos: [] }
      g.insumos.push({ descripcionNorm: s.descripcionNorm, unidad: s.unidad })
      grupos.set(clave, g)
    }
    if (grupos.size === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      let total = 0
      for (const g of grupos.values()) {
        const r = await apiPost<{ asignados: number }>('/plan-compras/api/paquetes/asignar', {
          insumos: g.insumos,
          paqueteId: g.paqueteId,
          procedencia: { origen: g.capa, confianza: g.confianza, evidencia: g.evidencia, confirmado: true },
        })
        total += r.asignados
      }
      dispatch({ type: 'LIMPIAR_SUGERENCIAS' })
      dispatch({ type: 'LISTO', mensaje: `${plural(total, 'sugerencia')} aceptada${total === 1 ? '' : 's'}.` })
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
      dispatch({ type: 'LISTO', mensaje: `${plural(r.asignados, 'insumo')} asignado${r.asignados === 1 ? '' : 's'}.` })
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
      dispatch({ type: 'LISTO', mensaje: `${plural(r.omitidos, 'insumo')} omitido${r.omitidos === 1 ? '' : 's'}.` })
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
      dispatch({ type: 'LISTO', mensaje: `${plural(r.desasignados, 'insumo')} devuelto${r.desasignados === 1 ? '' : 's'} a sin asignar.` })
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
        nombre: nuevoNombre, tipoNegociacion: nuevoTipo, modalidad: nuevaModalidad,
      })
      setNuevoNombre('')
      setPaqueteDestino(r.paquete.id)
      dispatch({ type: 'LISTO', mensaje: r.paquete.existente === 1 ? 'El paquete ya existía; queda seleccionado.' : 'Paquete creado.' })
      cargar(filtro)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  // Las secciones de la pantalla, incluidos los dos modos de trabajo: antes los modos eran una
  // barra propia y la lista de paquetes vivía al final, solo alcanzable bajando rodando.
  const barra = (
    <Pestanas
      idBase="pdc-paq"
      etiquetaLista="Secciones de paquetes de contratación"
      activa={modo}
      onCambiar={(id) => setModo(id as Modo)}
      pestanas={[
        { id: 'masivo', etiqueta: 'Insumos distintos', conteo: visibles.length },
        { id: 'asistente', etiqueta: 'Asistente paso a paso' },
        { id: 'paquetes', etiqueta: 'Paquetes con insumos', conteo: (resumen?.porPaquete ?? []).length },
      ]}
    />
  )

  if (sinVersion) {
    return (
      <section className="pdc-bloque">
        <h1>Paquetes de contratación</h1>
        <p className="pdc-vacio">El proyecto no tiene un presupuesto importado. Importa un presupuesto para empezar a empaquetar.</p>
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

      {modo === 'asistente' && (
        <PanelPestana idBase="pdc-paq" id="asistente">
          <PaquetesAsistente onCambio={() => cargar(filtro)} actividadesMap={actividadesMap} usadosEnProyecto={usadosEnProyecto} />
        </PanelPestana>
      )}

      {modo === 'masivo' && (
      <PanelPestana idBase="pdc-paq" id="masivo">
      {state.mensaje && <div className="pdc-info" role="status">{state.mensaje}</div>}

      {/* Con el 100 % del valor asignado, esta pantalla enseñaba tres barras de controles y once
          botones para una fila de $ 0. El trabajo que importa está hecho: se dice, y el aparato de
          trabajo se pliega. Lo que quede suelto sigue contándose, porque «100 % por valor» no es
          «100 %». */}
      {cerradoPorValor && (
        <div className="pdc-paq-cierre" data-testid="pdc-paq-cierre" role="status">
          <strong>Por valor está todo asignado.</strong>{' '}
          {sueltos.cuantos === 0
            ? 'No queda ningún insumo sin destino.'
            : `Queda ${contarInsumos(sueltos.cuantos, 'distintos')} sin destino, de ${moneda(sueltos.valor)}.`}
        </div>
      )}

      <details className="pdc-paq-herramientas" open={!cerradoPorValor}>
        <summary>Asignar insumos</summary>

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
        <button
          type="button"
          data-testid="pdc-paq-sembrar"
          className="pdc-paq-primario"
          disabled={state.ocupado}
          onClick={onProponer}
          title="Solo propone: las propuestas aparecen en la columna «Sugerencia» y no se guarda nada hasta que las aceptes."
        >
          {ACCION_PROPONER.etiqueta}
        </button>
        {state.sugerencias.size > 0 && (
          <button type="button" data-testid="pdc-paq-aceptar-sugeridos" className="pdc-paq-primario" disabled={state.ocupado} onClick={onAceptarSugeridos}>
            Aceptar {plural(state.sugerencias.size, 'sugerida')}
          </button>
        )}
      </div>

      {resumenSembrado.total > 0 && (
        <div data-testid="pdc-paq-sembrado-resumen" className="pdc-paq-sembrado">
          <strong>Propuesta del motor: {contarInsumos(resumenSembrado.total, 'distintos')}</strong> — nada se ha guardado. Revísala en la columna «Sugerencia» y ajusta lo que quieras antes de <em>Aceptar</em>.
          <div className="pdc-paq-fuentes">
            {FUENTES_ORDEN.filter((f) => resumenSembrado.porFuente[f]).map((f) => (
              <span key={f} className="pdc-paq-fuente"><span className={`pdc-paq-punto pdc-fuente-${f}`} />{FUENTE_LABEL[f]}: {resumenSembrado.porFuente[f]}</span>
            ))}
          </div>
        </div>
      )}

      <div className="pdc-paq-acciones">
        <button type="button" data-testid="pdc-paq-sel-todos" disabled={visibles.length === 0}
          onClick={() => dispatch({ type: 'SEL_TODOS', claves: visibles.map((i) => claveInsumo(i.descripcionNorm, i.unidad)) })}>
          Seleccionar visibles
        </button>
        <button type="button" data-testid="pdc-paq-limpiar-sel" disabled={seleccionados.length === 0} onClick={() => dispatch({ type: 'LIMPIAR_SEL' })}>
          Limpiar
        </button>
        <span className="pdc-paq-sel">{plural(seleccionados.length, 'seleccionado')}</span>
        <select data-testid="pdc-paq-select-paquete" aria-label="Paquete destino" value={paqueteDestino} onChange={(e) => setPaqueteDestino(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">Paquete destino…</option>
          {paquetes.map((p) => <option key={p.id} value={p.id}>{p.nombre}</option>)}
        </select>
        {/* `title` en los tres: apagados sin explicación, obligaban a probar combinaciones para
            descubrir qué faltaba. Ahora el propio botón dice qué le falta para encenderse. */}
        <button type="button" data-testid="pdc-paq-asignar" className="pdc-paq-primario" title={faltaParaAsignar} disabled={state.ocupado || paqueteDestino === '' || seleccionados.length === 0} onClick={onAsignar}>
          Asignar a paquete
        </button>
        <button type="button" data-testid="pdc-paq-omitir" title={faltaSeleccion} disabled={state.ocupado || seleccionados.length === 0} onClick={onOmitir}>
          Omitir
        </button>
        <button type="button" data-testid="pdc-paq-desasignar" title={faltaSeleccion} disabled={state.ocupado || seleccionados.length === 0} onClick={onDesasignar}>
          Devolver a sin asignar
        </button>
      </div>

      {/* Plegado: crear un paquete se hace un puñado de veces por obra, mientras que asignar se
          repite cientos. Tenerlo siempre desplegado costaba una barra entera de alto a la tabla,
          que es donde vive el trabajo. */}
      <details className="pdc-paq-crear-plegable">
        <summary>Crear un paquete nuevo</summary>
      <div className="pdc-paq-crear">
        <input data-testid="pdc-paq-crear-nombre" placeholder="Crear paquete nuevo…" value={nuevoNombre} onChange={(e) => setNuevoNombre(e.target.value)} />
        <select data-testid="pdc-paq-crear-tipo" aria-label="Tipo de negociación" value={nuevoTipo} onChange={(e) => setNuevoTipo(e.target.value)}>
          {TIPOS_NEGOCIACION.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
        </select>
        <select
          data-testid="pdc-paq-crear-modalidad"
          aria-label="Modalidad de contratación"
          title={MODALIDADES.find((m) => m.value === nuevaModalidad)?.ayuda}
          value={nuevaModalidad}
          onChange={(e) => setNuevaModalidad(e.target.value)}
        >
          {MODALIDADES.map((m) => <option key={m.value} value={m.value} title={m.ayuda}>{m.label}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-crear" disabled={state.ocupado || nuevoNombre.trim() === ''} onClick={onCrearPaquete}>
          Crear paquete
        </button>
      </div>
      </details>
      </details>

      <div data-testid="pdc-paq-grid" className="pdc-grid-wrap" ref={refGrid}>
        <AgGridReact<InsumoPaquete>
          theme={pdcTheme}
          rowData={visibles}
          overlayNoRowsTemplate={vacioTabla("No queda ningún insumo con este filtro.")}
          columnDefs={colsVisibles}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
          context={{ actividadesMap }}
          tooltipShowDelay={350}
          onRowClicked={onRowClicked}
          // Sin `domLayout="autoHeight"`: con 263 insumos la tabla medía miles de píxeles y
          // arrastraba la página entera, así que la grilla nunca llegaba a tener scroll propio.
          // Ahora la envoltura tiene alto (`.pdc-grid-wrap` crece con el hueco libre) y scrollea
          // por dentro, con el encabezado de columnas siempre a la vista.
          suppressCellFocus
          getRowClass={(p) => (p.data?.omitido === 1 ? 'pdc-paq-fila-omitida' : undefined)}
        />
      </div>
      </PanelPestana>
      )}

      {modo === 'paquetes' && (
      <PanelPestana idBase="pdc-paq" id="paquetes">
      <input
        className="pdc-buscador"
        data-testid="pdc-paq-buscar-paquete"
        placeholder="Buscar paquete…"
        aria-label="Buscar paquete"
        value={buscaPaquete}
        onChange={(e) => setBuscaPaquete(e.target.value)}
      />
      <ul data-testid="pdc-paq-paquetes" className="pdc-paq-lista">
        {filtraPorTexto(resumen?.porPaquete ?? [], buscaPaquete, (x) => x.nombre).map((p) => (
          <li key={p.paqueteId}>
            <strong>{p.nombre}</strong>
            {/* Los buckets no contratables ya no mienten (su tipo es `no_aplica`): se omite el badge
                porque no aporta nada al lado de la modalidad, no porque el dato sea falso. */}
            {muestraTipoNegociacion(p.tipoNegociacion) && (
              <span className="pdc-paq-tag">{tipoNegLabel(p.tipoNegociacion)}</span>
            )}
            {p.modalidad && p.modalidad !== 'contrato' && (
              <span
                className={`pdc-paq-modalidad pdc-paq-modalidad--${p.modalidad}`}
                title={MODALIDADES.find((m) => m.value === p.modalidad)?.ayuda}
              >
                {modalidadLabel(p.modalidad)}
              </span>
            )}
            <span className="pdc-paq-meta">{contarInsumos(p.insumos, 'distintos')} · {moneda(p.subtotal)}</span>
            {/* Partir vive aquí, junto a los insumos del paquete: es mirándolos como se decide que
                «aquí había porcelanato, porcelanato, tableta gres, cerámica» son contratos distintos. */}
            <button
              type="button"
              className="pdc-paq-lotes"
              data-testid={`pdc-paq-lotes-${p.paqueteId}`}
              aria-expanded={loteAbierto === p.paqueteId}
              onClick={() => setLoteAbierto(loteAbierto === p.paqueteId ? null : p.paqueteId)}
            >
              {loteAbierto === p.paqueteId ? 'Cerrar lotes' : 'Lotes de obra'}
            </button>
            {loteAbierto === p.paqueteId && (
              <SubpaquetesPanel
                paqueteId={p.paqueteId}
                paqueteNombre={p.nombre}
                // `cargar` ya trae insumos, catálogo y resumen en la misma pasada: los tres tienen
                // que moverse juntos o la cobertura de la cabecera queda contando lo de antes.
                onCambio={() => cargar(filtro)}
              />
            )}
          </li>
        ))}
        {(resumen?.porPaquete ?? []).length === 0 && <li className="pdc-vacio">Aún no hay insumos asignados a ningún paquete.</li>}
      </ul>
      </PanelPestana>
      )}
    </section>
  )
}

/**
 * Tres indicadores, no uno: por conteo («que no quede nada suelto»), por valor (la cola larga es
 * barata y por conteo parece un agujero enorme) y el acierto del motor, que es lo único que dice si
 * las sugerencias sirven. El acierto arranca «sin datos» a propósito: un 100 % vacío sería mentira.
 */
function Cobertura({ resumen }: { resumen: ResumenPaquetes }) {
  const acierto = resumen.acierto
  return (
    <div data-testid="pdc-paq-cobertura" className="pdc-paq-cobertura">
      {/* Tres porcentajes juntos sin decir de qué era el hallazgo de la revisión de usabilidad:
          «33,6 %» al lado de «1 %» y «100 %» obligaba a parar y adivinar cuál es la meta. El grande
          lleva ahora su nombre encima; los otros dos ya llevaban el suyo. */}
      <div className="pdc-paq-cobertura-titulo">Insumos con destino</div>
      <div className="pdc-paq-cobertura-num">{resumen.cobertura}%</div>
      <div className="pdc-paq-cobertura-detalle">
        {resumen.asignados} asignados + {resumen.omitidos} omitidos de {resumen.total} insumos distintos
      </div>
      <div className="pdc-paq-barra"><div className="pdc-paq-barra-fill" style={{ transform: `scaleX(${resumen.cobertura / 100})` }} /></div>
      <dl className="pdc-paq-indicadores">
        {resumen.coberturaValor !== undefined && (
          <div data-testid="pdc-paq-cobertura-valor">
            <dt>Del valor, con destino</dt>
            <dd>{resumen.coberturaValor}%</dd>
          </div>
        )}
        {acierto && (
          <div
            data-testid="pdc-paq-acierto"
            title={acierto.tasa === null
              ? 'Aún no se ha aplicado ninguna sugerencia del motor.'
              : `${plural(acierto.correcciones, 'corrección', 'correcciones')} sobre ${plural(acierto.sugerenciasAplicadas, 'decisión', 'decisiones')} del motor.`}
          >
            <dt>Acierto del motor</dt>
            <dd>{acierto.tasa === null ? 'sin datos' : `${acierto.tasa}%`}</dd>
            {/* Un 100 % sobre tres decisiones y un 100 % sobre trescientas no significan lo mismo,
                y la base solo estaba en el texto que aparece al pasar el ratón. */}
            {acierto.tasa !== null && (
              <dd className="pdc-paq-indicador-base">sobre {plural(acierto.sugerenciasAplicadas, 'decisión', 'decisiones')}</dd>
            )}
          </div>
        )}
      </dl>
    </div>
  )
}
