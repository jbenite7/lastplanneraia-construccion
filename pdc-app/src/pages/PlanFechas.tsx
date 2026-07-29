import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { Link } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowSelectionModule, RowStyleModule, SelectEditorModule, TextEditorModule, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, SelectionChangedEvent } from 'ag-grid-community'
import Pestanas, { PanelPestana } from '../components/Pestanas'
import {
  MODULOS_TABLA, TEXTO_LARGO, autoSizeStrategy, columnaNumero, columnaTexto, defaultColDef,
  moneda, pdcTheme, vacioTabla
} from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import {
  AVISO_DESAMARRAR,
  accionDeClic,
  agruparPorConfianza,
  coberturaPlan,
  contarSinResponsable,
  estadoFila,
  estadoInicialPlanUi,
  etiquetaDesfase,
  etiquetaElegible,
  idPorEtiqueta,
  mensajeCalculo,
  opcionFrente,
  opcionesFrente,
  opcionAncla,
  anclasOrdenadas,
  resumenCorrespondencias,
  procedenciaConSugerencia,
  opcionesResponsable,
  paquetesAmarradosSinCalcular,
  paquetesSinFrente,
  planUiReducer,
  preseleccionDestinos,
  procedenciaDeAmarre,
  resumenPlan,
  resumenVencidos,
  sumaValor,
  trasGuardarEdicion,
  uniqueIdPorEtiquetaFrente,
  valorResponsableMostrado,
} from '../lib/planFechas'
import type { AnclaDisponible, MotivoSinPropuesta, PanelCorrespondencias, Desfase, FilaPlan, FrenteDisponible, PlanResultado, ResponsableElegible, ResumenPaquetes, SugerenciaFrente } from '../lib/types'
import { filtraPorTexto, plural } from '../lib/texto'

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
  // El motivo por el que un paquete no recibió propuesta, y el panel de correspondencias que lo
  // resuelve. Van juntos: la fila sin propuesta ofrece el atajo que abre el panel en su rama.
  const [motivos, setMotivos] = useState<Record<number, MotivoSinPropuesta>>({})
  const [panel, setPanel] = useState<PanelCorrespondencias | null>(null)
  const [panelAbierto, setPanelAbierto] = useState(false)
  const [ramaFoco, setRamaFoco] = useState<string | null>(null)
  const [anclas, setAnclas] = useState<AnclaDisponible[]>([])
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
  // Fila esperando confirmación para desamarrarse: borra fechas, así que se pregunta antes.
  const [porDesamarrar, setPorDesamarrar] = useState<FilaPlan | null>(null)
  // Las cuatro secciones de esta pantalla vivían apiladas: «Sin frente» y sus 40 sugerencias solo
  // aparecían al bajar rodando por debajo de la grilla.
  const [seccion, setSeccion] = useState('plan')

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
    // Las anclas incluyen las 242 actividades: hay ramas sin frente propio cuyo hito real es una
    // actividad concreta (CUBIERTA ancla en «LOSA AÉREA CUBIERTA»).
    apiGet<{ anclas: AnclaDisponible[] }>('/plan-compras/api/plan/anclas')
      .then((d) => setAnclas(d.anclas))
      .catch(() => setAnclas([]))
    apiGet<PanelCorrespondencias>('/plan-compras/api/plan/correspondencias')
      .then((d) => setPanel(d))
      .catch(() => setPanel(null))
      .catch(() => setFrentes([]))
    // Se marca "no cargadas" al empezar cada carga (no solo en el montaje inicial): si `cargar()` se
    // vuelve a invocar (recalcular, amarrar), una `sinFrente` que cambie antes de que esta respuesta
    // nueva llegue no debe sembrar con las sugerencias todavía viejas.
    setSugerenciasCargadas(false)
    apiGet<{ sugerencias: Record<number, SugerenciaFrente>; motivos: Record<number, MotivoSinPropuesta> }>('/plan-compras/api/plan/sugerencias')
      .then((d) => { setSugerencias(d.sugerencias); setMotivos(d.motivos ?? {}); setSugerenciasCargadas(true) })
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
  const cobertura = useMemo(() => coberturaPlan(porPaquete, amarres), [porPaquete, amarres])
  const vencidos = useMemo(() => resumenVencidos(plan), [plan])
  // Ninguno de los dos se persiste: cerrar la franja dura lo que dure la visita (decisión del
  // grilleo), y el filtro es una lupa momentánea, no una preferencia.
  const [soloVencidos, setSoloVencidos] = useState(false)
  const [franjaCerrada, setFranjaCerrada] = useState(false)
  // Filtra, no reordena: lo vencido ya venía primero del servidor y cambiar el orden aquí haría
  // que quitar el filtro devolviera la tabla distinta de como estaba.
  const planVisible = useMemo(
    () => (soloVencidos ? plan.filter((f) => f.diasRetraso > 0) : plan),
    [plan, soloVencidos],
  )
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
      dispatch({ type: 'LISTO', mensaje: `Responsable asignado a ${plural(paqueteIds.length, 'paquete')}.` })
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

  /**
   * Deshace el amarre. Se pregunta antes porque destruye las fechas calculadas —que es
   * justamente lo que alguien puede haberle comunicado ya a un proveedor—, y la pregunta dice la
   * verdad completa: también dice lo que NO se pierde, para que corregir un frente mal elegido no
   * dé más miedo del que merece.
   */
  const onDesamarrar = async (fila: FilaPlan) => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/desamarrar', { paqueteId: fila.paqueteId })
      dispatch({ type: 'LISTO', mensaje: `«${fila.nombre}» vuelve a «Sin frente».` })
      setPorDesamarrar(null)
      // Recargar entero, no solo el plan: la lista «Sin frente» se calcula en el cliente cruzando
      // el resumen de paquetes con los amarres, así que refrescar uno solo dejaría el paquete
      // invisible en las dos partes de la pantalla a la vez.
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
      setPorDesamarrar(null)
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
        procedencia: procedenciaConSugerencia(sugerencias[paqueteId], uniqueId),
      })
      // El aviso de recalcular no es un adorno: cambiar de frente invalida el plan viejo, así que
      // la fila desaparece de la grilla y cae en «Amarrados, pendientes de calcular». Sin decirlo,
      // parece que el paquete se perdió.
      dispatch({
        type: 'LISTO',
        mensaje: frente
          ? `Amarrado a «${frente.nombre}». Recalcula para ver sus fechas.`
          : 'Amarrado. Recalcula para ver sus fechas.',
      })
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
  /**
   * Guarda una correspondencia rama → nodo del cronograma. NO amarra nada: solo cambia lo que el
   * motor propondrá. Por eso recarga las sugerencias al terminar en vez de escribir amarres.
   */
  const onGuardarCorrespondencia = async (rama: string, ancla: string, alcance: 'global' | 'proyecto') => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/correspondencias', { rama, ancla, alcance })
      const [c, sug] = await Promise.all([
        apiGet<PanelCorrespondencias>('/plan-compras/api/plan/correspondencias'),
        apiGet<{ sugerencias: Record<number, SugerenciaFrente>; motivos: Record<number, MotivoSinPropuesta> }>('/plan-compras/api/plan/sugerencias'),
      ])
      setPanel(c)
      setSugerencias(sug.sugerencias)
      setMotivos(sug.motivos ?? {})
      setRamaFoco(null)
      dispatch({ type: 'LISTO', mensaje: `«${rama}» ahora apunta a «${ancla}». No se amarró ningún paquete: solo cambió lo que se propone.` })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : 'No se pudo guardar la correspondencia.' })
    }
  }

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
  const porConfianza = useMemo(() => agruparPorConfianza(sinFrente, sugerencias), [sinFrente, sugerencias])
  const [confirmarMedia, setConfirmarMedia] = useState(false)
  const [buscaSinFrente, setBuscaSinFrente] = useState('')

  // Recibe qué lote aceptar en vez de leerlo del cierre: los dos botones (confianza alta directo,
  // confianza media tras confirmar) comparten este cuerpo, y con él todas las garantías que ya
  // costaron un review — respetar el <select> de la fila y no acreditar al motor cuando el destino
  // elegido no coincide con su propuesta.
  const onAceptarSugeridos = async (lote: typeof sugeridosPendientes) => {
    if (lote.length === 0) return
    dispatch({ type: 'OCUPADO' })
    let total = 0
    let algunFallo = false
    for (const p of lote) {
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
        ? `${total} de ${plural(lote.length, 'paquete')} amarrado${total === 1 ? '' : 's'}; alguno falló.`
        : `${plural(total, 'paquete')} amarrado${total === 1 ? '' : 's'} por sugerencia del motor.`,
    })
    cargar()
  }

  const cols = useMemo<ColDef<FilaPlan>[]>(() => [
    { ...TEXTO_LARGO, headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 220 },
    {
      // Cambiar de frente desde la propia tabla: hasta la revisión de UX esta columna era texto
      // plano y el selector solo existía en «Sin frente», donde un paquete ya amarrado no aparece.
      // El servidor ya sabía reamarrar (amarrar() hace ON DUPLICATE KEY UPDATE); solo faltaba
      // exponerlo.
      ...columnaTexto('frenteNombre', 'Frente', 160),
      colId: 'frente', editable: true, cellEditor: 'agSelectCellEditor',
      cellEditorParams: (p: { data?: FilaPlan }) => ({
        values: p.data ? opcionesFrente(frentes, p.data) : [],
      }),
      valueGetter: (p) => (p.data ? `${p.data.frenteNombre} — ${p.data.fechaAncla}` : ''),
      // valueSetter explícito: el de por defecto escribiría la etiqueta entera («ESTRUCTURA —
      // 2026-08-18») dentro de `frenteNombre` y dejaría la fila incoherente si el POST fallara.
      // Aquí se actualizan los tres campos a la vez, hasta que `cargar()` traiga la verdad.
      valueSetter: (p) => {
        const destino = frentes.find((f) => opcionFrente(f) === (p.newValue ?? '').trim())
        if (destino === undefined) return false
        p.data.uniqueId = destino.uniqueId
        p.data.frenteNombre = destino.nombre
        p.data.fechaAncla = destino.fechaInicio
        return true
      },
      onCellValueChanged: (p) => {
        if (!p.data) return
        const destino = uniqueIdPorEtiquetaFrente(frentes, (p.newValue ?? '').trim())
        // El anterior sale de `oldValue` y no de la fila: el valueSetter ya la actualizó, así que
        // compararla consigo misma nunca detectaría un cambio. Elegir la misma opción que ya estaba
        // no dispara reamarre — no habría nada que cambiar y sí un plan calculado que invalidar.
        const anterior = uniqueIdPorEtiquetaFrente(frentes, (p.oldValue ?? '').trim())
        if (destino === null || destino === anterior) return
        void onAmarrar(p.data.paqueteId, destino, anterior ?? '')
      },
    },
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
    {
      // Deshacer el amarre. Era el hallazgo más serio de la revisión: una vez amarrado no había
      // forma de volver atrás desde la interfaz.
      colId: 'desamarrar', headerName: '', width: 120, sortable: false, suppressAutoSize: true,
      cellClass: 'pdc-celda-accion',
      valueGetter: () => 'Desamarrar',
    },
  ], [responsableOverride, desfasePorPaquete, elegibles, frentes])

  const filaExpandida = plan.find((f) => f.paqueteId === expandido) ?? null

  return (
    <section className="pdc-bloque pdc-plan">
      <header className="pdc-paq-header">
        <div>
          <h1>Plan de compras</h1>
          <p className="pdc-sub">Qué hay que empezar a contratar y cuándo — lo vencido va primero.</p>
        </div>
        {/* Misma forma que la cobertura de Paquetes de contratación, a propósito: quien pasa de una
            pantalla a otra reconoce el indicador sin releerlo. El número grande es la plata, no el
            conteo — un paquete de acero pesa lo que cincuenta de ferretería, y el porcentaje por
            conteo esconde justo eso. */}
        <div data-testid="pdc-plan-cobertura" className="pdc-paq-cobertura">
          <div className="pdc-paq-cobertura-num">{cobertura.porcentajeValor}%</div>
          <div className="pdc-paq-cobertura-detalle">
            {cobertura.conFecha} de {cobertura.total} paquetes con fecha
          </div>
          <div className="pdc-paq-barra">
            <div className="pdc-paq-barra-fill" style={{ transform: `scaleX(${cobertura.porcentajeValor / 100})` }} />
          </div>
          <div className="pdc-plan-resumen" data-testid="pdc-plan-resumen">
            <span><strong>{resumen.total}</strong> {resumen.total === 1 ? 'paquete' : 'paquetes'}</span>
            <span className="pdc-plan-resumen-vencidos"><strong>{resumen.vencidos}</strong> {resumen.vencidos === 1 ? 'vencido' : 'vencidos'}</span>
            <span><strong>{resumen.provisionales}</strong> con duración estimada</span>
          </div>
        </div>
      </header>

      {/* Un amarrado sin recalcular cuenta como cubierto arriba (la decisión difícil ya se tomó),
          así que sin este aviso la cobertura escondería trabajo pendiente de un solo botón. */}
      {sinCalcular.length > 0 && (
        <p className="pdc-plan-aviso-recalcular" data-testid="pdc-plan-aviso-recalcular" role="status">
          {sinCalcular.length === 1
            ? '1 paquete ya tiene frente pero le faltan las fechas: pulsa «Recalcular».'
            : `${sinCalcular.length} paquetes ya tienen frente pero les faltan las fechas: pulsa «Recalcular».`}
        </p>
      )}

      {/* Menor del review final A4: `.pdc-info` pintaba igual un éxito que un fallo, así que una
          aserción de e2e sobre ese selector pasaba aunque el amarre hubiera fallado — `ui.tipo`
          distingue cuál de los dos fue. */}
      {ui.mensaje && <div className={ui.tipo === 'error' ? 'pdc-error' : 'pdc-info'} role="status">{ui.mensaje}</div>}

      {/* El dueño del producto preguntó si perdía lo avanzado antes de atreverse a pulsarlo. El
          texto dice lo que el código garantiza: las tres columnas del responsable quedan fuera del
          ON DUPLICATE KEY UPDATE y los pasos se actualizan en su misma fila (upsert, no DELETE +
          INSERT) — ver PlanFechasService::calcular(). Si esa garantía cambiara, este texto pasaría
          a ser mentira: hay tests que la vigilan. */}
      <div className="pdc-paq-toolbar">
        <button type="button" className="pdc-paq-primario" data-testid="pdc-plan-recalcular" disabled={ui.ocupado} onClick={onRecalcular}>
          Recalcular
        </button>
        {/* A4.1 — los pasos del proceso son configurables por obra. El enlace vive aquí y no en la
            barra de pestañas porque se configura una vez y casi no se vuelve a tocar. */}
        <Link to="/ensamble/plan/pasos" className="pdc-paq-secundario" data-testid="pdc-plan-configurar-pasos">
          Configurar pasos
        </Link>
        {/* La duda («¿pierdo lo avanzado?») se tiene una vez; el párrafo se leía en cada visita a
            las cuatro pestañas. Plegado, la respuesta sigue a un clic. El texto NO cambia: es la
            garantía que vigilan los tests de PlanFechasService. */}
        <details className="pdc-plan-recalcular-ayuda">
          <summary>¿qué conserva?</summary>
          <span className="pdc-plan-recalcular-nota" data-testid="pdc-plan-recalcular-nota">
            Recalcula las fechas contra el cronograma vigente. Conserva los responsables, los amarres
            a frentes y lo ya registrado en cada paso: lo único que cambia son las fechas.
          </span>
        </details>
      </div>

      <Pestanas
        idBase="pdc-plan"
        etiquetaLista="Secciones del plan de compras"
        activa={seccion}
        onCambiar={setSeccion}
        pestanas={[
          { id: 'plan', etiqueta: 'Plan', conteo: plan.length },
          { id: 'sin-frente', etiqueta: 'Sin frente', conteo: sinFrente.length },
          { id: 'sin-calcular', etiqueta: 'Pendientes de calcular', conteo: sinCalcular.length },
          { id: 'desfases', etiqueta: 'Desfases', conteo: desfases.length },
        ]}
      />

      {seccion === 'plan' && (
      <PanelPestana idBase="pdc-plan" id="plan">
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

      {porDesamarrar && (
        <div className="pdc-panel" data-testid="pdc-plan-confirmar-desamarrar">
          <p>¿Quitarle el frente a <strong>{porDesamarrar.nombre}</strong>?</p>
          <p className="pdc-ayuda">{AVISO_DESAMARRAR}</p>
          <button type="button" data-testid="pdc-plan-desamarrar-confirmar" disabled={ui.ocupado} onClick={() => void onDesamarrar(porDesamarrar)}>
            Sí, quitarle el frente
          </button>{' '}
          <button type="button" data-testid="pdc-plan-desamarrar-cancelar" onClick={() => setPorDesamarrar(null)}>
            Cancelar
          </button>
        </div>
      )}

      {/* Lo más grave del proyecto se contaba en voz baja: tres paquetes con 98, 83 y 66 días de
          retraso, en texto pequeño dentro de su fila. La franja lo dice arriba y además lleva a
          verlos, que es lo que se pregunta a continuación. Se puede cerrar, pero vuelve al recargar:
          es una alerta de obra, no un anuncio. */}
      {vencidos.cuantos > 0 && !franjaCerrada && (
        <div className="pdc-plan-franja" data-testid="pdc-plan-franja-vencidos" role="status">
          <span className="pdc-plan-franja-texto">
            <strong>{vencidos.cuantos}</strong>
            {vencidos.cuantos === 1 ? ' paquete debió arrancar' : ' paquetes debieron arrancar'}
            {' '}hace hasta <strong>{vencidos.diasMaximo}</strong> días.
          </span>
          <button
            type="button"
            className="pdc-plan-franja-accion"
            data-testid="pdc-plan-franja-filtrar"
            onClick={() => setSoloVencidos((v) => !v)}
          >
            {soloVencidos ? 'Ver todos' : 'Ver solo los vencidos'}
          </button>
          <button
            type="button"
            className="pdc-plan-franja-cerrar"
            data-testid="pdc-plan-franja-cerrar"
            aria-label="Cerrar el aviso de vencidos"
            onClick={() => setFranjaCerrada(true)}
          >
            ×
          </button>
        </div>
      )}

      <div data-testid="pdc-plan-grid" className="pdc-grid-wrap">
        <AgGridReact<FilaPlan>
          theme={pdcTheme}
          rowData={planVisible}
          overlayNoRowsTemplate={vacioTabla("Todavía no hay paquetes con plan calculado. Amarra un paquete a un frente y pulsa «Recalcular».")}
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
          // Un solo clic abre el desplegable de responsable; hasta ahora hacía falta un doble clic.
          singleClickEdit
          onCellClicked={(e: CellClickedEvent<FilaPlan>) => {
            if (!e.data) return
            const accion = accionDeClic(e.column?.getColId())
            if (accion === 'editar') return
            if (accion === 'accion') { setPorDesamarrar(e.data); return }
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
      </PanelPestana>
      )}

      {seccion === 'sin-frente' && (
      <PanelPestana idBase="pdc-plan" id="sin-frente">
      <p className="pdc-sub">Paquetes que generan proceso de contratación y todavía no están amarrados a un frente del cronograma.</p>
      {/* Antes había un solo botón que escribía las 40 propuestas de un clic. Medido en Da Porto:
          37 eran de confianza media —deducidas de la actividad padre, no de la descripción del
          insumo— y solo 3 de confianza alta. El desglose lo dice antes de pulsar, el botón primario
          solo toca las seguras, y las medias pasan por una confirmación. Las bajas no las acepta
          ningún botón masivo: se aceptan una a una desde su fila. */}
      {sugeridosPendientes.length > 0 && (
        <div className="pdc-paq-toolbar pdc-plan-propuestas">
          <span className="pdc-plan-desglose" data-testid="pdc-plan-desglose-confianza">
            Propuestas del motor:{' '}
            <span className="pdc-paq-tag conf-alta">{porConfianza.alta.length} alta</span>{' '}
            <span className="pdc-paq-tag conf-media">{porConfianza.media.length} media</span>{' '}
            <span className="pdc-paq-tag conf-baja">{porConfianza.baja.length} baja</span>
          </span>
          <button
            type="button"
            data-testid="pdc-plan-aceptar-alta"
            className="pdc-paq-primario"
            disabled={ui.ocupado || porConfianza.alta.length === 0}
            onClick={() => void onAceptarSugeridos(porConfianza.alta)}
          >
            Aceptar {porConfianza.alta.length} de confianza alta
          </button>
          {porConfianza.media.length > 0 && (
            <button
              type="button"
              data-testid="pdc-plan-aceptar-media"
              className="pdc-paq-secundario"
              disabled={ui.ocupado}
              onClick={() => setConfirmarMedia(true)}
            >
              Revisar {porConfianza.media.length} de confianza media
            </button>
          )}
        </div>
      )}

      {confirmarMedia && (
        <div className="pdc-panel" data-testid="pdc-plan-confirmar-media">
          <p>
            Vas a amarrar <strong>{porConfianza.media.length}</strong> paquetes por propuestas de
            confianza <strong>media</strong>, que suman <strong>{moneda(sumaValor(porConfianza.media))}</strong>.
          </p>
          <p className="pdc-ayuda">
            Confianza media significa que el motor dedujo el frente de la actividad padre, no de la
            descripción del insumo. Acierta a menudo, pero no siempre: revisa la lista si algo te
            suena raro. Cada fila respeta el frente que hayas cambiado a mano.
          </p>
          <details className="pdc-plan-lista-previa">
            <summary>Ver los {porConfianza.media.length} amarres</summary>
            <ul>
              {porConfianza.media.map((p) => {
                // El destino que se va a escribir es el del `<select>` de la fila, no la propuesta
                // cruda del motor: si alguien lo cambió a mano, la lista debe enseñar ese.
                const elegido = destinos[p.paqueteId]
                const frente = frentes.find((f) => f.uniqueId === elegido)
                return (
                  <li key={p.paqueteId}>
                    <strong>{p.nombre}</strong> → {frente ? opcionFrente(frente) : 'sin frente elegido'}
                    <span className="pdc-paq-meta">{moneda(p.subtotal)}</span>
                  </li>
                )
              })}
            </ul>
          </details>
          <button
            type="button"
            data-testid="pdc-plan-confirmar-media-si"
            disabled={ui.ocupado}
            onClick={() => { setConfirmarMedia(false); void onAceptarSugeridos(porConfianza.media) }}
          >
            Sí, amarrar los {porConfianza.media.length}
          </button>{' '}
          <button type="button" data-testid="pdc-plan-confirmar-media-no" onClick={() => setConfirmarMedia(false)}>
            Cancelar
          </button>
        </div>
      )}
      {/* Panel de correspondencias: cerrado por defecto. Es donde se resuelve la causa de que un
          paquete no reciba propuesta, en vez de amarrarlo a mano uno por uno. */}
      {panel && (
        <section className="pdc-plan-panel-corresp" data-testid="pdc-plan-panel-correspondencias">
          <button
            type="button"
            className="pdc-plan-panel-toggle"
            data-testid="pdc-plan-panel-toggle"
            aria-expanded={panelAbierto}
            onClick={() => setPanelAbierto((v) => !v)}
          >
            {panelAbierto ? '▾' : '▸'} Correspondencias del presupuesto con el cronograma
            <span className="pdc-plan-panel-resumen">{resumenCorrespondencias(panel)}</span>
          </button>
          {panelAbierto && (
            <div className="pdc-plan-panel-cuerpo">
              <p className="pdc-nota">
                Dicen a qué parte del cronograma corresponde cada rama del presupuesto. Cambiarlas no
                amarra ningún paquete: solo cambia lo que se propone de aquí en adelante.
              </p>
              {panel.pendientes.length > 0 && (
                <div data-testid="pdc-plan-panel-pendientes">
                  <h4>Ramas sin asignar ({panel.pendientes.length})</h4>
                  <p className="pdc-nota">Son las que hoy dejan paquetes sin fecha.</p>
                  <ul className="pdc-paq-lista">
                    {panel.pendientes.map((rama) => (
                      <li key={rama} className={ramaFoco === rama ? 'pdc-destacado' : undefined}>
                        <strong>{rama}</strong>
                        <select
                          aria-label={`Nodo del cronograma para ${rama}`}
                          disabled={ui.ocupado}
                          defaultValue=""
                          onChange={(e) => {
                            const a = anclas.find((x) => String(x.uniqueId) === e.target.value)
                            if (a) void onGuardarCorrespondencia(rama, a.nombre, 'proyecto')
                          }}
                        >
                          <option value="">Elegir nodo del cronograma…</option>
                          {anclasOrdenadas(anclas).map((a) => (
                            <option key={a.uniqueId} value={a.uniqueId}>{opcionAncla(a)}</option>
                          ))}
                        </select>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              <h4>Ya resueltas ({panel.correspondencias.length})</h4>
              <ul className="pdc-paq-lista" data-testid="pdc-plan-panel-resueltas">
                {panel.correspondencias.map((c) => (
                  <li key={c.rama}>
                    <strong>{c.rama}</strong>
                    <span className="pdc-paq-meta">→ {c.ancla}</span>
                    {c.alcance !== 'global' && <span className="pdc-paq-tag">{c.alcance}</span>}
                    {c.nota && <span className="pdc-nota">{c.nota}</span>}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </section>
      )}
      <input
        className="pdc-buscador"
        data-testid="pdc-plan-buscar-sin-frente"
        placeholder="Buscar paquete…"
        aria-label="Buscar paquete sin frente"
        value={buscaSinFrente}
        onChange={(e) => setBuscaSinFrente(e.target.value)}
      />
      <ul className="pdc-paq-lista pdc-plan-sinfrente-lista" data-testid="pdc-plan-sin-frente">
        {filtraPorTexto(sinFrente, buscaSinFrente, (x) => x.nombre).map((p) => {
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
                    fechas distintas y sin la fecha las opciones son indistinguibles. Los frentes van
                    primero y las actividades después, marcadas: son 242 y enterrarían a los 31
                    frentes, que es lo que casi siempre se busca. */}
                {(anclas.length > 0 ? anclasOrdenadas(anclas) : frentes.map((f) => ({ ...f, esFrente: true }))).map((f) => (
                  <option key={f.uniqueId} value={f.uniqueId}>{opcionAncla(f)}</option>
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
              {/* Sin propuesta ya no es una fila muda: dice qué rama falta y ofrece resolverla. */}
              {!sugerencia && motivos[p.paqueteId] && (
                <span className="pdc-paq-motivo" data-testid={`pdc-plan-motivo-${p.paqueteId}`}>
                  {motivos[p.paqueteId].texto}
                  {motivos[p.paqueteId].rama && (
                    <button
                      type="button"
                      className="pdc-enlace"
                      data-testid={`pdc-plan-atajo-${p.paqueteId}`}
                      onClick={() => { setRamaFoco(motivos[p.paqueteId].rama); setPanelAbierto(true) }}
                    >
                      Asignarla
                    </button>
                  )}
                </span>
              )}
            </li>
          )
        })}
        {sinFrente.length === 0 && <li className="pdc-vacio">Todos los paquetes que generan proceso ya tienen frente.</li>}
      </ul>
      </PanelPestana>
      )}

      {seccion === 'sin-calcular' && (
      <PanelPestana idBase="pdc-plan" id="sin-calcular">
      <p className="pdc-sub">Ya tienen frente pero el plan todavía no se ha recalculado con ese amarre — no aparecen en la pestaña «Plan».</p>
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
      </PanelPestana>
      )}

      {seccion === 'desfases' && (
      <PanelPestana idBase="pdc-plan" id="desfases">
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
      </PanelPestana>
      )}
    </section>
  )
}
