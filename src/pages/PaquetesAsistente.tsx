import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { moneda } from '../lib/agGrid'
import { apiGet, apiPost } from '../lib/api'
import { claveInsumo } from '../lib/paquetesState'
import {
  estadoInicialWizard,
  ordenarDestinos,
  procedenciaDeAsignacion,
  wizardReducer,
} from '../lib/paqueteWizardState'
import { MODALIDADES, TIPOS_NEGOCIACION } from '../lib/types'
import type { ActividadesInsumo, CandidatoPaquete, InsumoPaquete, PaqueteCatalogo, SugerenciaPaquete } from '../lib/types'

const tipoNegLabel = (v: string) => TIPOS_NEGOCIACION.find((t) => t.value === v)?.label ?? v
const modalidadLabel = (v?: string) => MODALIDADES.find((m) => m.value === v)?.label ?? v ?? ''

/** Mismo umbral que la auto-asignación del motor: por encima, un error se paga distinto. */
const UMBRAL_CUANTIA = 20_000_000

type PanelCandidatos = { paquete: { id: number; nombre: string }; lista: CandidatoPaquete[] } | null
type FiltroCola = 'todos' | 'con' | 'sin'

export default function PaquetesAsistente({
  onCambio,
  actividadesMap,
  usadosEnProyecto,
}: {
  onCambio: () => void
  actividadesMap: Record<string, ActividadesInsumo>
  /** paqueteId → cuantía ya asignada en este proyecto, para ordenar el desplegable de destino. */
  usadosEnProyecto: Map<number, number>
}) {
  const [state, dispatch] = useReducer(wizardReducer, estadoInicialWizard)
  const [insumos, setInsumos] = useState<InsumoPaquete[]>([])
  const [sugerencias, setSugerencias] = useState<Map<string, SugerenciaPaquete>>(new Map())
  const [paquetes, setPaquetes] = useState<PaqueteCatalogo[]>([])
  const [tipoNeg, setTipoNeg] = useState<string>('')
  const [nuevoNombre, setNuevoNombre] = useState('')
  const [panel, setPanel] = useState<PanelCandidatos>(null)
  const [candSel, setCandSel] = useState<Set<string>>(new Set())
  const [filtro, setFiltro] = useState<FiltroCola>('todos')

  const recargar = useCallback(async () => {
    const [ins, sug, cat] = await Promise.all([
      apiGet<{ insumos: InsumoPaquete[] }>('/plan-compras/api/paquetes/insumos?filtro=sin_asignar'),
      apiGet<{ sugerencias: SugerenciaPaquete[] }>('/plan-compras/api/paquetes/sugerencias').catch(() => ({ sugerencias: [] })),
      apiGet<{ paquetes: PaqueteCatalogo[] }>('/plan-compras/api/paquetes').catch(() => ({ paquetes: [] })),
    ])
    setInsumos(ins.insumos)
    const mapa = new Map<string, SugerenciaPaquete>()
    for (const s of sug.sugerencias) mapa.set(claveInsumo(s.descripcionNorm, s.unidad), s)
    setSugerencias(mapa)
    setPaquetes(cat.paquetes)
  }, [])

  useEffect(() => { dispatch({ type: 'RESET' }); void recargar() }, [recargar])

  const sugerenciaDe = useCallback(
    (i: InsumoPaquete) => sugerencias.get(claveInsumo(i.descripcionNorm, i.unidad)) ?? null,
    [sugerencias],
  )

  // El filtro acota la cola sin tocar su orden por valor: primero lo caro, con propuesta o sin ella.
  const cola = useMemo(() => {
    if (filtro === 'todos') return insumos
    return insumos.filter((i) => (filtro === 'con' ? sugerenciaDe(i) !== null : sugerenciaDe(i) === null))
  }, [insumos, filtro, sugerenciaDe])

  const conPropuesta = useMemo(() => insumos.filter((i) => sugerenciaDe(i) !== null).length, [insumos, sugerenciaDe])

  const actual = cola[state.indice]
  const clave = actual ? claveInsumo(actual.descripcionNorm, actual.unidad) : ''
  const sugerencia = actual ? sugerenciaDe(actual) : null
  const actividades = actual ? actividadesMap[clave] ?? null : null

  // El destino llega puesto: la propuesta del motor, o lo que se hubiera elegido antes de saltar.
  const destino: number | '' = actual ? state.destinos[clave] ?? sugerencia?.paqueteId ?? '' : ''
  const setDestino = (v: number | '') => dispatch({ type: 'ELEGIR', clave, paqueteId: v === '' ? null : v })

  const destinosOrdenados = useMemo(() => ordenarDestinos(paquetes, usadosEnProyecto), [paquetes, usadosEnProyecto])
  const paquetesFiltrados = useMemo(
    () => (tipoNeg === '' ? destinosOrdenados : destinosOrdenados.filter((p) => p.tipoNegociacion === tipoNeg)),
    [destinosOrdenados, tipoNeg],
  )

  useEffect(() => { setTipoNeg(''); setNuevoNombre('') }, [state.indice, insumos])

  const paqueteElegido = paquetes.find((p) => p.id === destino) ?? null
  const esCaro = (actual?.valorTotal ?? 0) >= UMBRAL_CUANTIA
  // Doble conteo: si el destino es a todo costo, el material lo pone el contratista. Se avisa y ya:
  // prohibirlo aquí obligaría a salir de la herramienta para resolver una decisión de catálogo.
  const avisoDobleConteo =
    paqueteElegido !== null &&
    paqueteElegido.tipoNegociacion === 'a_todo_costo' &&
    paqueteElegido.admiteMateriales !== true &&
    (actual?.tipoRecurso ?? '').toUpperCase() === 'MATERIAL'

  const asignarA = async (paqueteId: number, nombre: string) => {
    if (!actual) return
    const objetivo = actual
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/paquetes/asignar', {
        insumos: [{ descripcionNorm: objetivo.descripcionNorm, unidad: objetivo.unidad }],
        paqueteId,
        procedencia: procedenciaDeAsignacion(sugerenciaDe(objetivo), paqueteId),
      })
      onCambio()
      // Ofrece candidatos del mismo paquete filtrados por el tipo de recurso del insumo (4ª señal).
      const c = await apiGet<{ candidatos: CandidatoPaquete[] }>(
        `/plan-compras/api/paquetes/candidatos?paqueteId=${paqueteId}` +
        (objetivo.tipoRecurso ? `&tipoRecurso=${encodeURIComponent(objetivo.tipoRecurso)}` : ''),
      ).catch(() => ({ candidatos: [] as CandidatoPaquete[] }))
      await recargar() // el actual sale de la lista; el índice apunta al siguiente
      dispatch({
        type: 'LISTO',
        mensaje: `Asignado a ${nombre}.`,
        ultima: { descripcionNorm: objetivo.descripcionNorm, unidad: objetivo.unidad, destino: nombre },
      })
      if (c.candidatos.length > 0) { setPanel({ paquete: { id: paqueteId, nombre }, lista: c.candidatos }); setCandSel(new Set()) }
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const crearYAsignar = async () => {
    if (nuevoNombre.trim() === '') return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ paquete: PaqueteCatalogo & { existente: number } }>('/plan-compras/api/paquetes', {
        nombre: nuevoNombre, tipoNegociacion: tipoNeg || TIPOS_NEGOCIACION[0].value,
      })
      await asignarA(r.paquete.id, r.paquete.nombre)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const omitirActual = async () => {
    if (!actual) return
    const objetivo = actual
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/paquetes/omitir', {
        insumos: [{ descripcionNorm: objetivo.descripcionNorm, unidad: objetivo.unidad }],
        // Omitir lo que el motor proponía también lo corrige: rechazar hacia fuera del plan cuenta.
        procedencia: procedenciaDeAsignacion(sugerenciaDe(objetivo), null),
      })
      onCambio()
      await recargar()
      dispatch({
        type: 'LISTO',
        mensaje: 'Insumo omitido.',
        ultima: { descripcionNorm: objetivo.descripcionNorm, unidad: objetivo.unidad, destino: null },
      })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  /** Devuelve el último insumo movido a «sin asignar»: un paso atrás sin salir del asistente. */
  const deshacer = async () => {
    const u = state.ultima
    if (!u) return
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/paquetes/desasignar', {
        insumos: [{ descripcionNorm: u.descripcionNorm, unidad: u.unidad }],
      })
      onCambio()
      await recargar()
      dispatch({ type: 'LISTO', mensaje: 'Deshecho: el insumo volvió a la cola.' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const asignarAlDestino = () => {
    const p = paquetes.find((x) => x.id === destino)
    if (p) void asignarA(p.id, p.nombre)
  }

  // Enter recorre la cola sin ratón. No se captura mientras se escribe el nombre de un paquete
  // nuevo, donde Enter significa otra cosa.
  const alTeclear = (e: React.KeyboardEvent<HTMLDivElement>) => {
    if (e.key !== 'Enter' || state.ocupado || panel !== null) return
    const t = e.target as HTMLElement
    if (t.tagName === 'INPUT' || t.tagName === 'BUTTON') return
    if (destino === '') return
    e.preventDefault()
    asignarAlDestino()
  }

  const toggleCand = (k: string) => {
    setCandSel((prev) => { const n = new Set(prev); n.has(k) ? n.delete(k) : n.add(k); return n })
  }

  const anadirCandidatos = async () => {
    if (!panel) return
    const lista = panel.lista.filter((c) => candSel.has(claveInsumo(c.descripcionNorm, c.unidad)))
    if (lista.length === 0) { setPanel(null); return }
    dispatch({ type: 'OCUPADO' })
    try {
      // Sin procedencia a propósito: el motor solo ofreció una lista de parecidos; quién entra y
      // quién no lo decide el ojo. Contarlo como acierto suyo inflaría la métrica.
      await apiPost('/plan-compras/api/paquetes/asignar', {
        insumos: lista.map((c) => ({ descripcionNorm: c.descripcionNorm, unidad: c.unidad })),
        paqueteId: panel.paquete.id,
      })
      onCambio()
      await recargar()
      dispatch({ type: 'LISTO', mensaje: `${lista.length} insumo(s) más a ${panel.paquete.nombre}.` })
      setPanel(null)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const restantes = Math.max(0, cola.length - state.indice)

  if (panel) {
    return (
      <div className="pdc-wiz" data-testid="pdc-wiz">
        <div className="pdc-wiz-candidatos" data-testid="pdc-wiz-candidatos">
          <h3>¿Añadir más insumos a «{panel.paquete.nombre}»?</h3>
          <p className="pdc-sub">Insumos sin asignar parecidos{panel.lista[0]?.tipoRecurso ? ` (${panel.lista[0].tipoRecurso})` : ''}. Marca los que también van a este paquete.</p>
          <ul className="pdc-wiz-cand-lista">
            {panel.lista.map((c) => {
              const k = claveInsumo(c.descripcionNorm, c.unidad)
              return (
                <li key={k}>
                  <label>
                    <input type="checkbox" checked={candSel.has(k)} onChange={() => toggleCand(k)} />
                    <span>{c.descripcion}</span>
                    <span className="pdc-wiz-cand-meta">{c.unidad} · {moneda(c.valorTotal)}</span>
                  </label>
                </li>
              )
            })}
          </ul>
          <div className="pdc-wiz-acciones">
            <button type="button" className="pdc-paq-primario" data-testid="pdc-wiz-cand-anadir" disabled={state.ocupado || candSel.size === 0} onClick={anadirCandidatos}>
              Añadir {candSel.size} a «{panel.paquete.nombre}»
            </button>
            <button type="button" data-testid="pdc-wiz-cand-omitir" disabled={state.ocupado} onClick={() => setPanel(null)}>
              Continuar sin añadir
            </button>
          </div>
        </div>
      </div>
    )
  }

  if (!actual) {
    return (
      <div className="pdc-wiz pdc-wiz-fin" data-testid="pdc-wiz">
        <h3>{filtro === 'todos' ? 'No quedan insumos sin asignar 🎉' : 'Nada en este filtro'}</h3>
        <p className="pdc-sub">
          {filtro === 'todos'
            ? 'Recorriste todos los insumos del presupuesto activo. Revisa la cobertura arriba o usa el modo masivo para ajustes.'
            : 'Cambia el filtro para seguir recorriendo la cola.'}
        </p>
        {filtro !== 'todos' && (
          <button type="button" onClick={() => { setFiltro('todos'); dispatch({ type: 'RESET' }) }}>Ver todos</button>
        )}
        <button type="button" onClick={() => { dispatch({ type: 'RESET' }); void recargar() }}>Volver a recorrer</button>
      </div>
    )
  }

  return (
    <div className="pdc-wiz" data-testid="pdc-wiz" onKeyDown={alTeclear}>
      {state.mensaje && (
        <div className="pdc-info" role="status">
          {state.mensaje}
          {state.ultima && (
            <button type="button" className="pdc-wiz-deshacer" data-testid="pdc-wiz-deshacer" disabled={state.ocupado} onClick={deshacer}>
              Deshacer
            </button>
          )}
        </div>
      )}

      <div className="pdc-wiz-progreso">
        Quedan <strong>{restantes}</strong> insumo(s) sin asignar, <strong>{conPropuesta}</strong> con propuesta del motor.
        <span className="pdc-wiz-filtro" role="group" aria-label="Filtrar la cola">
          {([['todos', 'Todos'], ['con', 'Con propuesta'], ['sin', 'Sin propuesta']] as [FiltroCola, string][]).map(([v, l]) => (
            <button key={v} type="button" data-testid={`pdc-wiz-filtro-${v}`} className={filtro === v ? 'is-activo' : ''}
              onClick={() => { setFiltro(v); dispatch({ type: 'RESET' }) }}>{l}</button>
          ))}
        </span>
      </div>

      <article className="pdc-wiz-card" data-testid="pdc-wiz-card">
        <header>
          <h3>{actual.descripcion}</h3>
          <span className={esCaro ? 'pdc-wiz-valor es-caro' : 'pdc-wiz-valor'} data-testid="pdc-wiz-valor">
            {moneda(actual.valorTotal)}
            {esCaro && <span className="pdc-wiz-caro-nota">revísalo con calma</span>}
          </span>
        </header>
        <dl className="pdc-wiz-atributos">
          <div><dt>Unidad</dt><dd>{actual.unidad}</dd></div>
          <div><dt>Tipo de recurso</dt><dd>{actual.tipoRecurso ?? '—'}</dd></div>
          <div><dt>Agrupación</dt><dd>{actual.agrupacion ?? '—'}</dd></div>
        </dl>

        {actividades?.ruta && (
          <p className="pdc-wiz-ruta" data-testid="pdc-wiz-ruta" title="Dónde vive en el presupuesto">
            {actividades.ruta}
          </p>
        )}

        {actividades && actividades.total > 0 && (
          <details className="pdc-wiz-actividades" data-testid="pdc-wiz-actividades">
            <summary>Requerido por {actividades.total} actividad(es) del presupuesto</summary>
            <ul className="pdc-wiz-act-lista">
              {actividades.items.map((a, i) => (
                <li key={`${a.codigo}-${i}`}>
                  <span className="pdc-tt-cod">{a.codigo}</span>
                  <span className="pdc-tt-act">{a.actividad}</span>
                  <span className="pdc-tt-cant">{a.cantidad.toLocaleString('es-CO')} {actual.unidad}</span>
                </li>
              ))}
            </ul>
            {actividades.total > actividades.items.length && (
              <div className="pdc-tt-mas">y {actividades.total - actividades.items.length} más…</div>
            )}
          </details>
        )}

        {sugerencia ? (
          <div className="pdc-wiz-sugerencia" data-testid="pdc-wiz-sugerencia">
            <strong>Propuesta del motor:</strong> {sugerencia.paqueteNombre}
            <span className={`pdc-paq-tag conf-${sugerencia.confianza}`}>{sugerencia.capa} · confianza {sugerencia.confianza}</span>
            <p className="pdc-sub">{sugerencia.evidencia}</p>
          </div>
        ) : (
          <div className="pdc-wiz-sugerencia es-vacia" data-testid="pdc-wiz-sugerencia">
            <strong>Sin propuesta.</strong>
            <p className="pdc-sub">Ninguna señal alcanzó para proponer un destino: lo eliges tú.</p>
          </div>
        )}

        <div className="pdc-wiz-manual">
          <div className="pdc-wiz-fila">
            <label>Tipo de negociación
              <select data-testid="pdc-wiz-tipo" value={tipoNeg} onChange={(e) => { setTipoNeg(e.target.value); setDestino('') }}>
                <option value="">Todos</option>
                {TIPOS_NEGOCIACION.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
              </select>
            </label>
            <label>Paquete
              <select data-testid="pdc-wiz-paquete" value={destino} onChange={(e) => setDestino(e.target.value === '' ? '' : Number(e.target.value))}>
                <option value="">Elegir paquete…</option>
                {/* La modalidad solo se anota cuando no es «contrato»: avisa que ese destino no lleva
                    proceso de contratación con fechas (orden de compra, consumo directo, no contratable). */}
                {paquetesFiltrados.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.nombre}{p.modalidad && p.modalidad !== 'contrato' ? ` — ${modalidadLabel(p.modalidad)}` : ''}
                  </option>
                ))}
              </select>
            </label>
            <button type="button" className="pdc-paq-primario" data-testid="pdc-wiz-asignar" disabled={state.ocupado || destino === ''}
              onClick={asignarAlDestino}>
              Asignar <kbd>Enter</kbd>
            </button>
          </div>
          {avisoDobleConteo && (
            <p className="pdc-wiz-aviso" role="status" data-testid="pdc-wiz-aviso-doble">
              «{paqueteElegido?.nombre}» es a todo costo: el material lo pone el contratista, así que este
              insumo quedaría contado dos veces. Puedes asignarlo igual si sabes que no es el caso.
            </p>
          )}
          <div className="pdc-wiz-fila">
            <input data-testid="pdc-wiz-crear-nombre" placeholder="…o crea un paquete nuevo" value={nuevoNombre} onChange={(e) => setNuevoNombre(e.target.value)} />
            <button type="button" data-testid="pdc-wiz-crear" disabled={state.ocupado || nuevoNombre.trim() === ''} onClick={crearYAsignar}>
              Crear y asignar {tipoNeg ? `(${tipoNegLabel(tipoNeg)})` : ''}
            </button>
          </div>
        </div>

        <footer className="pdc-wiz-acciones">
          <button type="button" data-testid="pdc-wiz-omitir" disabled={state.ocupado} onClick={omitirActual}>Omitir (no va al plan)</button>
          <button type="button" data-testid="pdc-wiz-saltar" disabled={state.ocupado} onClick={() => dispatch({ type: 'SALTAR' })}>Saltar por ahora</button>
        </footer>
      </article>
    </div>
  )
}
