import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ClientSideRowModelModule, ModuleRegistry, RowStyleModule, TextEditorModule, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import {
  estadoFila,
  estadoInicialPlanUi,
  etiquetaDesfase,
  mensajeCalculo,
  opcionFrente,
  paquetesAmarradosSinCalcular,
  paquetesSinFrente,
  planUiReducer,
  preseleccionDestinos,
  procedenciaDeAmarre,
  resumenPlan,
  trasGuardarEdicion,
  valorResponsableMostrado,
} from '../lib/planFechas'
import type { Desfase, FilaPlan, FrenteDisponible, PlanResultado, ResumenPaquetes, SugerenciaFrente } from '../lib/types'

// Registro selectivo de módulos (no AllCommunityModule); ValidationModule solo en dev — patrón del repo.
// TextEditorModule: la columna Responsable es `editable: true` (edición de texto simple); sin este
// módulo AG Grid rechaza la edición en runtime (error #200) aunque la columna se vea igual.
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule,
  RowStyleModule,
  TextEditorModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const moneda = (v: number | null | undefined) => (v == null ? '' : `$ ${v.toLocaleString('es-CO')}`)
const mensajeError = (e: unknown) => (e instanceof Error ? e.message : String(e))

export default function PlanFechas() {
  const [ui, dispatch] = useReducer(planUiReducer, estadoInicialPlanUi)
  const [plan, setPlan] = useState<FilaPlan[]>([])
  const [amarres, setAmarres] = useState<PlanResultado['amarres']>({})
  const [frentes, setFrentes] = useState<FrenteDisponible[]>([])
  const [sugerencias, setSugerencias] = useState<Record<number, SugerenciaFrente>>({})
  // Bloqueante del review final A4: true solo cuando la petición de sugerencias ya resolvió (con
  // éxito o sin él). Sin esto, el efecto de preselección de abajo no puede distinguir «todavía no
  // sabemos si hay propuesta» de «ya sabemos que no la hay» — ver preseleccionDestinos().
  const [sugerenciasCargadas, setSugerenciasCargadas] = useState(false)
  const [desfases, setDesfases] = useState<Desfase[]>([])
  const [porPaquete, setPorPaquete] = useState<ResumenPaquetes['porPaquete']>([])
  const [expandido, setExpandido] = useState<number | null>(null)
  // Lo que el usuario tiene elegido en cada <select> de "sin frente" mientras dura la sesión;
  // arranca en la propuesta del motor (ver el efecto de preselección más abajo).
  const [destinos, setDestinos] = useState<Record<number, number | ''>>({})
  // Corrige la columna «Responsable» cuando el POST falla. AG Grid muta `data.responsable`
  // in-place al confirmar la edición (antes de saber si el guardado tuvo éxito); este mapa es lo
  // único que puede devolver la celda a lo último confirmado — ver trasGuardarEdicion.
  const [responsableOverride, setResponsableOverride] = useState<Record<number, string>>({})

  const cargar = useCallback(() => {
    apiGet<PlanResultado>('/plan-compras/api/plan')
      .then((d) => {
        setPlan(d.plan)
        setAmarres(d.amarres)
        // Cuando se recargan los datos del servidor (la verdad), limpiar el overlay de correcciones
        // pendientes: si un guardado falló y dejó un responsable revertido, este nuevo dato lo supera.
        setResponsableOverride({})
      })
      .catch((e) => { setPlan([]); setAmarres({}); dispatch({ type: 'FALLO', mensaje: mensajeError(e) }) })
    apiGet<{ frentes: FrenteDisponible[] }>('/plan-compras/api/plan/frentes')
      .then((d) => setFrentes(d.frentes))
      .catch(() => setFrentes([]))
    // Se marca "no cargadas" al empezar cada carga (no solo en el montaje inicial): si `cargar()` se
    // vuelve a invocar (recalcular, amarrar), una `sinFrente` que cambie antes de que esta respuesta
    // nueva llegue no debe sembrar con las sugerencias todavía viejas.
    setSugerenciasCargadas(false)
    apiGet<{ sugerencias: Record<number, SugerenciaFrente> }>('/plan-compras/api/plan/sugerencias')
      .then((d) => { setSugerencias(d.sugerencias); setSugerenciasCargadas(true) })
      .catch(() => { setSugerencias({}); setSugerenciasCargadas(true) })
    apiGet<{ desfases: Desfase[] }>('/plan-compras/api/plan/desfases')
      .then((d) => setDesfases(d.desfases))
      .catch(() => setDesfases([]))
    apiGet<ResumenPaquetes>('/plan-compras/api/paquetes/resumen')
      .then((d) => setPorPaquete(d.porPaquete))
      .catch(() => setPorPaquete([]))
  }, [])

  useEffect(() => { cargar() }, [cargar])

  const resumen = useMemo(() => resumenPlan(plan), [plan])
  const sinFrente = useMemo(() => paquetesSinFrente(porPaquete, amarres), [porPaquete, amarres])
  // Importante 2 del review final: un paquete recién amarrado sale de «Sin frente» pero no entra a
  // la grilla (que solo lee el plan calculado) hasta que alguien pulsa «Recalcular». Sin este
  // bloque queda invisible en las dos partes de la pantalla a la vez.
  const sinCalcular = useMemo(() => paquetesAmarradosSinCalcular(porPaquete, amarres, plan), [porPaquete, amarres, plan])
  // Importante 3: qué fila tiene un desfase, para que ese estado mande sobre vencido/provisional/en-plazo.
  const desfasePorPaquete = useMemo(() => new Map(desfases.map((d) => [d.paqueteId, d])), [desfases])

  // Preselección con la propuesta del motor (mismo criterio que el asistente de insumos de A3.6):
  // solo la primera vez que aparece cada paquete, para no pisar lo que el usuario ya cambió a mano.
  // Ver preseleccionDestinos() para el porqué de esperar a `sugerenciasCargadas` (bloqueante del
  // review final A4: sin esa espera, una carrera entre `sinFrente` y `sugerencias` perdía la
  // propuesta para siempre en esa carga).
  useEffect(() => {
    setDestinos((prev) => preseleccionDestinos(prev, sinFrente, sugerencias, sugerenciasCargadas))
  }, [sinFrente, sugerencias, sugerenciasCargadas])

  const onResponsable = async (paqueteId: number, responsable: string, anterior: string) => {
    // AG Grid ya mutó data.responsable a `responsable` (valueSetter por defecto, corrió antes de
    // este handler). Confiamos en esa edición optimista retirando cualquier override que quedara de
    // un intento previo; si este intento también falla, más abajo se vuelve a fijar.
    setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: true }))
    try {
      await apiPost('/plan-compras/api/plan/responsable', { paqueteId, responsable })
    } catch (e) {
      const mensaje = e instanceof PdcApiError && e.code === 'PAQUETE_SIN_PLAN'
        ? 'Este paquete todavía no tiene plan calculado; usa «Recalcular» antes de asignar responsable.'
        : mensajeError(e)
      dispatch({ type: 'FALLO', mensaje })
      // El guardado no ocurrió: la celda no puede seguir mostrando lo que AG Grid ya escribió.
      setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: false, anterior }))
    }
  }

  const onRecalcular = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ calculados: number; sinDuracion: number }>('/plan-compras/api/plan/calcular', {})
      dispatch({ type: 'LISTO', mensaje: mensajeCalculo(r) })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }

  const onAmarrar = async (paqueteId: number, uniqueId: number, anterior: number | '') => {
    const frente = frentes.find((f) => f.uniqueId === uniqueId)
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/amarrar', {
        paqueteId,
        uniqueId,
        procedencia: procedenciaDeAmarre(sugerencias[paqueteId], uniqueId),
      })
      dispatch({ type: 'LISTO', mensaje: frente ? `Amarrado a «${frente.nombre}».` : 'Amarrado.' })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
      // El <select> queda con lo que el usuario eligió (no hay edición optimista que revertir: el
      // amarre solo se dispara al pulsar «Amarrar», no al elegir), así que esto es un no-op salvo
      // que el helper reciba un valor distinto — se conserva por si algún día vuelve a hacer falta.
      setDestinos((prev) => trasGuardarEdicion(prev, paqueteId, { ok: false, anterior }))
    }
  }

  // Crítico del review final: el <select> ya NO dispara el amarre en su onChange — solo elige. Este
  // botón explícito es el único disparador, igual que «Asignar a paquete» en PaquetesContratacion.
  const onAmarrarClick = (paqueteId: number) => {
    const valor = destinos[paqueteId]
    if (valor === undefined || valor === '') return
    void onAmarrar(paqueteId, valor, valor)
  }

  // Acción masiva: acepta de golpe la propuesta del motor para todos los «sin frente» que la
  // tienen, tal como quedó preseleccionada — mismo patrón que «Aceptar sugeridos» en
  // PaquetesContratacion. Sin este botón, aceptar 50 propuestas exige 50 clics uno por uno.
  const sugeridosPendientes = useMemo(
    () => sinFrente.filter((p) => sugerencias[p.paqueteId] !== undefined),
    [sinFrente, sugerencias],
  )

  const onAceptarSugeridos = async () => {
    if (sugeridosPendientes.length === 0) return
    dispatch({ type: 'OCUPADO' })
    let total = 0
    let algunFallo = false
    for (const p of sugeridosPendientes) {
      // Importante 1 del review final A4: el botón masivo mandaba siempre `s.uniqueId` (la propuesta
      // cruda del motor), ignorando lo que el usuario tuviera elegido en el <select> de esa fila. Si
      // alguien cambiaba el frente a mano y luego pulsaba este botón, se amarraba al frente del motor
      // —no al que se veía en pantalla— y encima quedaba con procedencia de motor y
      // `confirmado_humano = 1`: un acierto falso que corrompe la métrica. `destinos` es la fuente de
      // verdad de lo que está en pantalla (arranca en la propuesta, pero el usuario puede cambiarlo);
      // `procedenciaDeAmarre` ya sabe no acreditar al motor cuando el destino elegido no coincide con
      // su propuesta — mismo criterio que el amarre individual (onAmarrar).
      const destino = destinos[p.paqueteId]
      if (destino === undefined || destino === '') continue // el usuario lo dejó sin elegir
      const s = sugerencias[p.paqueteId]
      try {
        await apiPost('/plan-compras/api/plan/amarrar', {
          paqueteId: p.paqueteId,
          uniqueId: destino,
          procedencia: procedenciaDeAmarre(s, destino),
        })
        total++
      } catch {
        algunFallo = true // un fallo puntual no debe frenar a los demás paquetes del lote
      }
    }
    dispatch({
      type: 'LISTO',
      mensaje: algunFallo
        ? `${total} de ${sugeridosPendientes.length} paquete(s) amarrado(s); alguno falló.`
        : `${total} paquete(s) amarrado(s) por sugerencia del motor.`,
    })
    cargar()
  }

  const cols = useMemo<ColDef<FilaPlan>[]>(() => [
    { headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 220 },
    { headerName: 'Frente', field: 'frenteNombre', flex: 1, minWidth: 160 },
    { headerName: 'Arranque', field: 'fechaArranque', width: 120 },
    { headerName: 'Necesidad en obra', field: 'fechaAncla', width: 150 },
    { headerName: 'Días', field: 'diasTotales', width: 90, type: 'rightAligned' },
    {
      headerName: 'Responsable', field: 'responsable', flex: 1, minWidth: 160, editable: true,
      valueGetter: (p) => (p.data ? valorResponsableMostrado(p.data, responsableOverride) : ''),
      onCellValueChanged: (p) => {
        if (!p.data) return
        void onResponsable(p.data.paqueteId, (p.newValue ?? '').trim(), (p.oldValue ?? '').trim())
      },
    },
    {
      headerName: 'Estado', flex: 1, minWidth: 160, sortable: false,
      valueGetter: (p) => (p.data ? estadoFila(p.data, desfasePorPaquete.get(p.data.paqueteId)).etiqueta : ''),
      cellClass: (p) => (p.data ? `pdc-plan-estado pdc-plan-estado--${estadoFila(p.data, desfasePorPaquete.get(p.data.paqueteId)).clave}` : undefined),
    },
  ], [responsableOverride, desfasePorPaquete])

  const filaExpandida = plan.find((f) => f.paqueteId === expandido) ?? null

  return (
    <section className="pdc-bloque pdc-plan">
      <header className="pdc-paq-header">
        <div>
          <h1>Plan de compras</h1>
          <p className="pdc-sub">Qué hay que empezar a contratar y cuándo — lo vencido va primero.</p>
        </div>
        <div className="pdc-plan-resumen" data-testid="pdc-plan-resumen">
          <span><strong>{resumen.total}</strong> paquete(s)</span>
          <span className="pdc-plan-resumen-vencidos"><strong>{resumen.vencidos}</strong> vencido(s)</span>
          <span><strong>{resumen.provisionales}</strong> con duración estimada</span>
        </div>
      </header>

      {/* Menor del review final A4: `.pdc-info` pintaba igual un éxito que un fallo, así que una
          aserción de e2e sobre ese selector pasaba aunque el amarre hubiera fallado — `ui.tipo`
          distingue cuál de los dos fue. */}
      {ui.mensaje && <div className={ui.tipo === 'error' ? 'pdc-error' : 'pdc-info'} role="status">{ui.mensaje}</div>}

      <div className="pdc-paq-toolbar">
        <button type="button" className="pdc-paq-primario" data-testid="pdc-plan-recalcular" disabled={ui.ocupado} onClick={onRecalcular}>
          Recalcular
        </button>
      </div>

      <div data-testid="pdc-plan-grid" className="pdc-grid-wrap">
        <AgGridReact<FilaPlan>
          theme={pdcTheme}
          rowData={plan}
          columnDefs={cols}
          domLayout="autoHeight"
          suppressCellFocus
          onCellClicked={(e: CellClickedEvent<FilaPlan>) => {
            if (!e.data || e.colDef.field === 'responsable') return
            const id = e.data.paqueteId
            setExpandido((prev) => (prev === id ? null : id))
          }}
          getRowClass={(p) => {
            if (!p.data) return undefined
            const clave = estadoFila(p.data, desfasePorPaquete.get(p.data.paqueteId)).clave
            if (clave === 'desfasado') return 'pdc-plan-fila-desfasada'
            if (clave === 'vencido') return 'pdc-plan-fila-vencida'
            return undefined
          }}
        />
      </div>
      {plan.length === 0 && <p className="pdc-vacio">Todavía no hay paquetes con plan calculado.</p>}

      {filaExpandida && (
        <div className="pdc-plan-detalle" data-testid="pdc-plan-detalle">
          <h3>Pasos de «{filaExpandida.nombre}»</h3>
          <table className="pdc-plan-pasos">
            <thead>
              <tr><th>Paso</th><th>Días</th><th>Inicio</th><th>Fin</th></tr>
            </thead>
            <tbody>
              {filaExpandida.pasos.map((p) => (
                <tr key={p.orden}>
                  <td>{p.paso}</td><td>{p.dias}</td><td>{p.fechaInicio}</td><td>{p.fechaFin}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <h2>Sin frente</h2>
      <p className="pdc-sub">Paquetes que generan proceso de contratación y todavía no están amarrados a un frente del cronograma.</p>
      {sugeridosPendientes.length > 0 && (
        <div className="pdc-paq-toolbar">
          <button
            type="button"
            data-testid="pdc-plan-aceptar-sugeridos"
            className="pdc-paq-primario"
            disabled={ui.ocupado}
            onClick={onAceptarSugeridos}
          >
            Aceptar {sugeridosPendientes.length} sugerida(s)
          </button>
        </div>
      )}
      <ul className="pdc-paq-lista" data-testid="pdc-plan-sin-frente">
        {sinFrente.map((p) => {
          const sugerencia = sugerencias[p.paqueteId]
          const destino = destinos[p.paqueteId] ?? ''
          return (
            <li key={p.paqueteId}>
              <strong>{p.nombre}</strong>
              <span className="pdc-paq-meta">{moneda(p.subtotal)}</span>
              <select
                aria-label={`Frente para ${p.nombre}`}
                disabled={ui.ocupado}
                value={destino}
                onChange={(e) => {
                  const valor = e.target.value === '' ? '' : Number(e.target.value)
                  setDestinos((prev) => ({ ...prev, [p.paqueteId]: valor }))
                }}
              >
                <option value="">Elegir frente…</option>
                {/* La fecha siempre va en la etiqueta: el cronograma repite nombres de frente en
                    fechas distintas y sin la fecha las opciones son indistinguibles. */}
                {frentes.map((f) => (
                  <option key={f.uniqueId} value={f.uniqueId}>{opcionFrente(f)}</option>
                ))}
              </select>
              {/* Único disparador del amarre (Crítico del review final): elegir en el <select> ya no
                  basta — la opción preseleccionada con la propuesta del motor no emite `change`, así
                  que sin este botón aceptarla tal cual era imposible desde la interfaz. */}
              <button
                type="button"
                data-testid={`pdc-plan-amarrar-${p.paqueteId}`}
                className="pdc-paq-primario"
                disabled={ui.ocupado || destino === ''}
                onClick={() => onAmarrarClick(p.paqueteId)}
              >
                Amarrar
              </button>
              {sugerencia && (
                <span className={`pdc-paq-tag conf-${sugerencia.confianza}`}>
                  {sugerencia.origen} · confianza {sugerencia.confianza}
                </span>
              )}
            </li>
          )
        })}
        {sinFrente.length === 0 && <li className="pdc-vacio">Todos los paquetes que generan proceso ya tienen frente.</li>}
      </ul>

      <h2>Amarrados, pendientes de calcular</h2>
      <p className="pdc-sub">Ya tienen frente pero el plan todavía no se ha recalculado con ese amarre — no aparecen en la grilla de arriba.</p>
      <ul className="pdc-paq-lista" data-testid="pdc-plan-sin-calcular">
        {sinCalcular.map((p) => (
          <li key={p.paqueteId}>
            <strong>{p.nombre}</strong>
            <span className="pdc-paq-meta">{moneda(p.subtotal)}</span>
            <button type="button" disabled={ui.ocupado} onClick={onRecalcular}>Recalcular todo el plan</button>
          </li>
        ))}
        {sinCalcular.length === 0 && <li className="pdc-vacio">Todo lo amarrado ya está calculado.</li>}
      </ul>

      <h2>Desfases</h2>
      <p className="pdc-sub">El cronograma se reprogramó después de amarrar estos paquetes. No se aplica solo.</p>
      <ul className="pdc-paq-lista" data-testid="pdc-plan-desfases">
        {desfases.map((d) => (
          <li key={d.paqueteId}>
            <strong>{d.nombre}</strong>
            <span className="pdc-paq-meta">{etiquetaDesfase(d)}</span>
            {/* No hay recálculo por paquete en el backend: este botón dispara el mismo recálculo
                global que el de la barra superior, y el texto debe decirlo así. */}
            <button type="button" disabled={ui.ocupado} onClick={onRecalcular}>Recalcular todo el plan</button>
          </li>
        ))}
        {desfases.length === 0 && <li className="pdc-vacio">Ningún amarre quedó desactualizado.</li>}
      </ul>
    </section>
  )
}
