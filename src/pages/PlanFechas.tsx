import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowSelectionModule, RowStyleModule, SelectEditorModule, TextEditorModule, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, SelectionChangedEvent } from 'ag-grid-community'
import {
  MODULOS_TABLA, TEXTO_LARGO, autoSizeStrategy, columnaNumero, columnaTexto, defaultColDef,
  moneda, pdcTheme,
} from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import {
  contarSinResponsable,
  estadoFila,
  estadoInicialPlanUi,
  etiquetaDesfase,
  etiquetaElegible,
  idPorEtiqueta,
  mensajeCalculo,
  opcionFrente,
  opcionesResponsable,
  paquetesAmarradosSinCalcular,
  paquetesSinFrente,
  planUiReducer,
  preseleccionDestinos,
  procedenciaDeAmarre,
  resumenPlan,
  trasGuardarEdicion,
  valorResponsableMostrado,
} from '../lib/planFechas'
import type { Desfase, FilaPlan, FrenteDisponible, PlanResultado, ResponsableElegible, ResumenPaquetes, SugerenciaFrente } from '../lib/types'

// Registro selectivo de módulos (no AllCommunityModule); ValidationModule solo en dev — patrón del repo.
// TextEditorModule: la columna Responsable es `editable: true`; sin este módulo AG Grid rechaza la
// edición en runtime (error #200) aunque la columna se vea igual.
// SelectEditorModule: lo que hace existir a `agSelectCellEditor`. Sin él la celda sigue siendo
// editable pero cae al editor de texto, así que el desplegable no llega a abrirse nunca —
// exactamente el modo en que el e2e del responsable lo detectó.
// RowSelectionModule: checkboxes de selección múltiple para la asignación en masa. Es Community
// (ver ag-grid-community/main.d.ts) — Enterprise sigue fuera del repo.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule,
  RowStyleModule,
  RowSelectionModule,
  SelectEditorModule,
  TextEditorModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

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
  const [elegibles, setElegibles] = useState<ResponsableElegible[]>([])
  // Filas marcadas con el checkbox de la grilla, para la asignación en masa (Task 5).
  const [seleccionados, setSeleccionados] = useState<number[]>([])
  const [masaEtiqueta, setMasaEtiqueta] = useState('')

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
    apiGet<{ responsables: ResponsableElegible[] }>('/plan-compras/api/plan/responsables')
      .then((d) => setElegibles(d.responsables))
      .catch(() => setElegibles([]))
    apiGet<ResumenPaquetes>('/plan-compras/api/paquetes/resumen')
      .then((d) => setPorPaquete(d.porPaquete))
      .catch(() => setPorPaquete([]))
  }, [])

  useEffect(() => { cargar() }, [cargar])

  const resumen = useMemo(() => resumenPlan(plan), [plan])
  // Cuántos paquetes del plan siguen sin dueño (incluye huérfanos): la decisión de producto es que
  // dejarlo sin asignar es válido, pero tiene que verse de un vistazo — ver contarSinResponsable.
  // Importante del review final: sin memo, a propósito. AG Grid muta las filas de `plan` in-place
  // (el valueSetter de la columna Responsable), así que la referencia del array nunca cambia con
  // una edición celda a celda — un useMemo([plan]) se queda pegado al conteo de antes de editar
  // hasta el próximo «Recalcular» o recarga completa, mintiendo justo durante el reparto manual.
  // Recalcularlo en cada render es barato (un filter sobre unas pocas centenas de filas, como
  // mucho) y siempre queda al día: cada edición individual ya dispara un re-render propio, porque
  // onResponsable cambia responsableOverride (mismo patrón que `filaExpandida` más abajo, que
  // tampoco memoiza su lectura de `plan`).
  const sinResponsable = contarSinResponsable(plan)
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

  const onResponsable = async (paqueteId: number, etiqueta: string, anterior: string) => {
    // AG Grid ya mutó la fila al valor nuevo (valueSetter por defecto, corrió antes de este
    // handler). El override se fija de entrada para pintar la elección al instante —es lo único que
    // sabe la etiqueta completa («Nombre — Cargo») que el usuario acaba de elegir—, pero solo dura
    // mientras el POST está en vuelo: `trasGuardarEdicion` lo suelta en éxito (la fila ya quedó
    // coherente vía el valueSetter, así que de ahí en más manda el dato real) y lo fija al valor
    // anterior en fallo. Crítico del review final: dejarlo puesto tras un éxito —en vez de
    // soltarlo— era el bug. AG Grid calcula `newValue` para la SIGUIENTE edición ejecutando el
    // valueGetter de la columna (que mira este override primero) DESPUÉS de correr su valueSetter,
    // así que un override que sobrevive a su propio guardado le hace creer a AG Grid que el usuario
    // volvió a elegir la persona vieja (Ana) en la siguiente edición, en vez de la nueva (Luis) que
    // de verdad eligió — se reenviaba el id de Ana al servidor, en silencio, sin ningún error.
    setResponsableOverride((prev) => ({ ...prev, [paqueteId]: etiqueta }))
    try {
      await apiPost('/plan-compras/api/plan/responsable', {
        paqueteId,
        responsableUserId: idPorEtiqueta(elegibles, etiqueta),
      })
      setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: true }))
    } catch (e) {
      let mensaje = mensajeError(e)
      if (e instanceof PdcApiError && e.code === 'PAQUETE_SIN_PLAN') {
        mensaje = 'Este paquete todavía no tiene plan calculado; usa «Recalcular» antes de asignar responsable.'
      } else if (e instanceof PdcApiError && e.code === 'RESPONSABLE_NO_ELEGIBLE') {
        mensaje = 'Esa persona ya no pertenece al equipo activo del proyecto; recarga la página para ver la lista al día.'
      }
      dispatch({ type: 'FALLO', mensaje })
      // El guardado no ocurrió: la celda no puede seguir mostrando lo que AG Grid ya escribió.
      setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: false, anterior }))
    }
  }

  // Asignación en masa (Task 5): más de cien paquetes por proyecto hacen que asignar de uno en uno
  // sea una hora de clics. `onResponsable` queda intacto arriba —esta función es un camino nuevo y
  // paralelo, no un reemplazo— para no arriesgar la edición celda a celda que ya funciona.
  const onResponsableMasa = async (paqueteIds: number[], etiqueta: string) => {
    if (paqueteIds.length === 0) return
    dispatch({ type: 'OCUPADO' })
    const userId = idPorEtiqueta(elegibles, etiqueta)
    // Mismo override que en la edición individual: sin él, las filas recién asignadas se repintan
    // con los datos viejos del servidor hasta que `cargar()` traiga los nuevos.
    setResponsableOverride((prev) => {
      const siguiente = { ...prev }
      paqueteIds.forEach((id) => { siguiente[id] = etiqueta })
      return siguiente
    })
    try {
      await apiPost('/plan-compras/api/plan/responsable', { paqueteIds, responsableUserId: userId })
      dispatch({ type: 'LISTO', mensaje: `Responsable asignado a ${paqueteIds.length} paquete(s).` })
      // El lote se guardó: limpiar la selección y la persona elegida, para no repetir el mismo
      // envío por accidente sobre un lote que ya quedó asignado.
      setSeleccionados([])
      setMasaEtiqueta('')
      cargar()
    } catch (e) {
      let mensaje = mensajeError(e)
      if (e instanceof PdcApiError && e.code === 'PAQUETE_SIN_PLAN') {
        // El backend rechaza el lote ENTERO (todo o nada): no se guardó ninguno, así que el mensaje
        // no puede sugerir que una parte sí quedó asignada.
        mensaje = 'Alguno de los paquetes seleccionados no tiene plan calculado. No se asignó ningún responsable; usa «Recalcular» primero.'
      } else if (e instanceof PdcApiError && e.code === 'RESPONSABLE_NO_ELEGIBLE') {
        mensaje = 'Esa persona ya no pertenece al equipo activo del proyecto; recarga la página para ver la lista al día.'
      }
      dispatch({ type: 'FALLO', mensaje })
      // Nada se guardó: hay que soltar el override de todo el lote, no solo de una fila. La
      // selección se conserva a propósito (a diferencia del caso de éxito): el usuario probablemente
      // quiere corregir algo (recalcular, elegir otra persona) y reintentar sobre el mismo lote.
      setResponsableOverride((prev) => {
        const siguiente = { ...prev }
        paqueteIds.forEach((id) => { delete siguiente[id] })
        return siguiente
      })
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
    { ...TEXTO_LARGO, headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 220 },
    columnaTexto('frenteNombre', 'Frente', 160),
    // Las fechas nunca envuelven y toman su ancho del contenido: partir «2026-07-28» en dos
    // renglones no ahorra nada y descuadra la fila.
    { headerName: 'Arranque', field: 'fechaArranque' },
    { headerName: 'Necesidad en obra', field: 'fechaAncla' },
    columnaNumero('diasTotales', 'Días'),
    {
      headerName: 'Responsable', colId: 'responsable', field: 'responsableNombre',
      flex: 1, minWidth: 220, editable: true,
      cellEditor: 'agSelectCellEditor',
      // Las opciones se calculan por fila, no una sola vez para la tabla: una fila con responsable
      // huérfano necesita su propia opción extra (ver opcionesResponsable) o AG Grid no podría
      // mostrar el valor que ya tiene.
      cellEditorParams: (p: { data?: FilaPlan }) => ({
        values: p.data ? opcionesResponsable(elegibles, p.data) : [''],
      }),
      valueGetter: (p) => (p.data ? valorResponsableMostrado(p.data, responsableOverride) : ''),
      // valueSetter explícito, no el de por defecto: aquel solo escribiría la etiqueta en
      // `responsableNombre`, y como el valueGetter deriva lo que se ve de `responsableUserId` —que
      // el editor no toca—, la celda volvía a leerse vacía y `onCellValueChanged` recibía '' como
      // newValue. Resultado: se guardaba «sin responsable» justo después de elegir a alguien.
      // Aquí se actualizan los cuatro campos a la vez para que la fila quede coherente hasta que
      // `cargar()` la refresque desde el servidor.
      valueSetter: (p) => {
        const persona = elegibles.find((e) => etiquetaElegible(e) === (p.newValue ?? '').trim())
        p.data.responsableUserId = persona?.id ?? null
        p.data.responsableNombre = persona?.nombre ?? ''
        p.data.responsableCargo = persona?.cargo ?? ''
        // Lo elegido sale siempre de la lista de elegibles, así que nunca es un huérfano.
        p.data.responsableHuerfano = false
        return true
      },
      cellClass: (p) => (p.data?.responsableHuerfano ? 'pdc-plan-responsable-huerfano' : undefined),
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
  ], [responsableOverride, desfasePorPaquete, elegibles])

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

      {/* Asignación en masa (Task 5): más de cien paquetes por proyecto hacen que asignar de uno en
          uno sea una hora de clics — con selección múltiple son cinco minutos. El contador de "sin
          responsable" vive aquí, junto al control que resuelve el problema, no solo porque se pueda
          ver de un vistazo (decisión de producto: dejar sin asignar es válido, pero no puede pasar
          desapercibido). */}
      <div className="pdc-plan-masa">
        <span
          data-testid="pdc-plan-sin-responsable"
          className={sinResponsable > 0 ? 'pdc-plan-sin-responsable es-pendiente' : 'pdc-plan-sin-responsable'}
        >
          <strong>{sinResponsable}</strong> sin responsable
        </span>
        <select
          data-testid="pdc-plan-masa-persona"
          aria-label="Persona para asignar a los paquetes seleccionados"
          value={masaEtiqueta}
          onChange={(e) => setMasaEtiqueta(e.target.value)}
          disabled={ui.ocupado || seleccionados.length === 0}
        >
          {/* Fila «vacía» deliberada: opcionesResponsable necesita una fila para saber si debe sumar
              una opción extra de huérfano, y aquí no hay una fila puntual — solo la lista general de
              gente elegible del proyecto (siempre empieza en '' = "Sin asignar"). */}
          {opcionesResponsable(elegibles, { responsableUserId: null, responsableNombre: '', responsableCargo: '', responsableHuerfano: false }).map((o) => (
            <option key={o} value={o}>{o === '' ? 'Sin asignar' : o}</option>
          ))}
        </select>
        <button
          type="button"
          data-testid="pdc-plan-masa-asignar"
          disabled={ui.ocupado || seleccionados.length === 0}
          onClick={() => void onResponsableMasa(seleccionados, masaEtiqueta)}
        >
          {/* La opción "Sin asignar" también es válida para el lote (quitar responsable a varios a la
              vez), pero es además el valor con el que arranca el selector — el botón dice "Quitar"
              en ese caso para que nadie vacíe N paquetes sin darse cuenta de qué eligió. */}
          {masaEtiqueta === ''
            ? `Quitar responsable a ${seleccionados.length} paquete${seleccionados.length === 1 ? '' : 's'}`
            : `Asignar a ${seleccionados.length} paquete${seleccionados.length === 1 ? '' : 's'}`}
        </button>
      </div>

      <div data-testid="pdc-plan-grid" className="pdc-grid-wrap">
        <AgGridReact<FilaPlan>
          theme={pdcTheme}
          rowData={plan}
          columnDefs={cols}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          domLayout="autoHeight"
          suppressCellFocus
          // Selección múltiple (Community — ver RowSelectionModule) solo por checkbox:
          // enableClickSelection: false deja que clicar una fila siga abriendo su detalle
          // (onCellClicked, abajo) sin seleccionarla de paso, que sería fácil de no notar antes de
          // pulsar "Asignar".
          rowSelection={{ mode: 'multiRow', checkboxes: true, headerCheckbox: true, enableClickSelection: false }}
          selectionColumnDef={{ width: 44, pinned: 'left' }}
          onSelectionChanged={(e: SelectionChangedEvent<FilaPlan>) => setSeleccionados(e.api.getSelectedRows().map((r) => r.paqueteId))}
          onCellClicked={(e: CellClickedEvent<FilaPlan>) => {
            if (!e.data || e.colDef.colId === 'responsable') return
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
              {/* «Hasta», no «Fin»: el intervalo de cada paso es medio abierto —esa fecha es la
                  frontera con el paso siguiente, no su último día trabajado (ver la convención en
                  PlanFechasService::calcular()). Con «Fin», «7 días · 23 may → 30 may» se lee como
                  ocho días y como si dos pasos compartieran uno; «Hasta» dice lo que el dato es. */}
              <tr><th>Paso</th><th>Días</th><th>Inicio</th><th>Hasta</th></tr>
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
