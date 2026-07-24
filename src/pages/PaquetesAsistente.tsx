import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { apiGet, apiPost } from '../lib/api'
import { claveInsumo } from '../lib/paquetesState'
import { estadoInicialWizard, wizardReducer } from '../lib/paqueteWizardState'
import { TIPOS_NEGOCIACION } from '../lib/types'
import type { CandidatoPaquete, InsumoPaquete, PaqueteCatalogo, SugerenciaPaquete } from '../lib/types'

const moneda = (v: number | null | undefined) => (v == null ? '' : `$ ${v.toLocaleString('es-CO')}`)
const tipoNegLabel = (v: string) => TIPOS_NEGOCIACION.find((t) => t.value === v)?.label ?? v

type PanelCandidatos = { paquete: { id: number; nombre: string }; lista: CandidatoPaquete[] } | null

export default function PaquetesAsistente({ onCambio }: { onCambio: () => void }) {
  const [state, dispatch] = useReducer(wizardReducer, estadoInicialWizard)
  const [insumos, setInsumos] = useState<InsumoPaquete[]>([])
  const [sugerencias, setSugerencias] = useState<Map<string, SugerenciaPaquete>>(new Map())
  const [paquetes, setPaquetes] = useState<PaqueteCatalogo[]>([])
  const [tipoNeg, setTipoNeg] = useState<string>('')
  const [destino, setDestino] = useState<number | ''>('')
  const [nuevoNombre, setNuevoNombre] = useState('')
  const [panel, setPanel] = useState<PanelCandidatos>(null)
  const [candSel, setCandSel] = useState<Set<string>>(new Set())

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

  const actual = insumos[state.indice]
  const sugerencia = actual ? sugerencias.get(claveInsumo(actual.descripcionNorm, actual.unidad)) ?? null : null
  const paquetesFiltrados = useMemo(
    () => (tipoNeg === '' ? paquetes : paquetes.filter((p) => p.tipoNegociacion === tipoNeg)),
    [paquetes, tipoNeg],
  )

  // Reinicia los controles manuales al cambiar de insumo.
  useEffect(() => { setTipoNeg(''); setDestino(''); setNuevoNombre('') }, [state.indice, insumos])

  const asignarA = async (paqueteId: number, nombre: string) => {
    if (!actual) return
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/paquetes/asignar', {
        insumos: [{ descripcionNorm: actual.descripcionNorm, unidad: actual.unidad }], paqueteId,
      })
      onCambio()
      // Ofrece candidatos del mismo paquete filtrados por el tipo de recurso del insumo (4ª señal).
      const c = await apiGet<{ candidatos: CandidatoPaquete[] }>(
        `/plan-compras/api/paquetes/candidatos?paqueteId=${paqueteId}` +
        (actual.tipoRecurso ? `&tipoRecurso=${encodeURIComponent(actual.tipoRecurso)}` : ''),
      ).catch(() => ({ candidatos: [] as CandidatoPaquete[] }))
      await recargar() // el actual sale de la lista; el índice apunta al siguiente
      dispatch({ type: 'LISTO', mensaje: `Asignado a ${nombre}.` })
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
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/paquetes/omitir', {
        insumos: [{ descripcionNorm: actual.descripcionNorm, unidad: actual.unidad }],
      })
      onCambio()
      await recargar()
      dispatch({ type: 'LISTO', mensaje: 'Insumo omitido.' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const toggleCand = (clave: string) => {
    setCandSel((prev) => { const n = new Set(prev); n.has(clave) ? n.delete(clave) : n.add(clave); return n })
  }

  const anadirCandidatos = async () => {
    if (!panel) return
    const lista = panel.lista.filter((c) => candSel.has(claveInsumo(c.descripcionNorm, c.unidad)))
    if (lista.length === 0) { setPanel(null); return }
    dispatch({ type: 'OCUPADO' })
    try {
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

  const pendientes = insumos.length
  const restantes = Math.max(0, pendientes - state.indice)

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
        <h3>No quedan insumos sin asignar 🎉</h3>
        <p className="pdc-sub">Recorriste todos los insumos del presupuesto activo. Revisa la cobertura arriba o usa el modo masivo para ajustes.</p>
        <button type="button" onClick={() => { dispatch({ type: 'RESET' }); void recargar() }}>Volver a recorrer</button>
      </div>
    )
  }

  return (
    <div className="pdc-wiz" data-testid="pdc-wiz">
      {state.mensaje && <div className="pdc-info" role="status">{state.mensaje}</div>}
      <div className="pdc-wiz-progreso">Quedan <strong>{restantes}</strong> insumo(s) sin asignar (orden por valor).</div>

      <article className="pdc-wiz-card" data-testid="pdc-wiz-card">
        <header>
          <h3>{actual.descripcion}</h3>
          <span className="pdc-wiz-valor">{moneda(actual.valorTotal)}</span>
        </header>
        <dl className="pdc-wiz-atributos">
          <div><dt>Unidad</dt><dd>{actual.unidad}</dd></div>
          <div><dt>Tipo de recurso</dt><dd>{actual.tipoRecurso ?? '—'}</dd></div>
          <div><dt>Agrupación</dt><dd>{actual.agrupacion ?? '—'}</dd></div>
        </dl>

        {sugerencia && (
          <div className="pdc-wiz-sugerencia" data-testid="pdc-wiz-sugerencia">
            <div>
              <strong>Sugerido:</strong> {sugerencia.paqueteNombre}
              <span className="pdc-paq-tag">{sugerencia.capa} · {sugerencia.confianza}</span>
              <p className="pdc-sub">{sugerencia.evidencia}</p>
            </div>
            <button type="button" className="pdc-paq-primario" data-testid="pdc-wiz-aceptar-sugerido" disabled={state.ocupado}
              onClick={() => asignarA(sugerencia.paqueteId, sugerencia.paqueteNombre)}>
              Aceptar sugerido
            </button>
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
                {paquetesFiltrados.map((p) => <option key={p.id} value={p.id}>{p.nombre}</option>)}
              </select>
            </label>
            <button type="button" className="pdc-paq-primario" data-testid="pdc-wiz-asignar" disabled={state.ocupado || destino === ''}
              onClick={() => { const p = paquetes.find((x) => x.id === destino); if (p) void asignarA(p.id, p.nombre) }}>
              Asignar
            </button>
          </div>
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
