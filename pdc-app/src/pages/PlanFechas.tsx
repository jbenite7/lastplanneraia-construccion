import { useCallback, useEffect, useMemo, useReducer, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowSelectionModule, RowStyleModule, SelectEditorModule, TextEditorModule, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, SelectionChangedEvent } from 'ag-grid-community'
import Pestanas, { PanelPestana } from '../components/Pestanas'
import { Selector } from '../components/Selector'
import {
  COLUMNA_FECHA, MODULOS_TABLA, TEXTO_LARGO, ajusteDeAncho, autoSizeStrategy, columnaNumero, columnaTexto,
  columnasQueCaben, defaultColDef, usaAnchoContenedor,
  moneda, pdcTheme, propsBuscador, vacioTabla
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
  avisoFrentesSinAncla,
  motivoSinAnclas,
  opcionFrente,
  opcionesFrente,
  opcionAncla,
  anclasOrdenadas,
  resumenCorrespondencias,
  procedenciaConSugerencia,
  opcionesResponsable,
  accionMasaResponsable,
  MASA_SIN_ELEGIR,
  paquetesAmarradosSinCalcular,
  paquetesSinFrente,
  destinosSinFrente,
  etiquetaDestino,
  claveDestino,
  destinoDePaquete,
  type DestinoContratable,
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
import { esCorregido, validarDias } from '../lib/duracionesObra'
import { etiquetaMovimiento, resumenDelta } from '../lib/reprogramacion'
import { claseCorte, etiquetaCorte } from '../lib/vencimientos'
import type { AnclaDisponible, MotivoSinPropuesta, PanelCorrespondencias, Desfase, FilaPlan, FrenteDisponible, PlanResultado, ResponsableElegible, ResumenPaquetes, SimulacionReprogramacion, SugerenciaFrente } from '../lib/types'
import { filtraPorTexto, plural } from '../lib/texto'
import BotonAyuda from '../components/BotonAyuda'
import { AvisoColumnasOcultas } from '../components/AvisoColumnasOcultas'

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
  const [destinosContratables, setDestinosContratables] = useState<DestinoContratable[]>([])
  const [amarresDestino, setAmarresDestino] = useState<PlanResultado['amarresDestino']>([])
  const [frentes, setFrentes] = useState<FrenteDisponible[]>([])
  const [sugerencias, setSugerencias] = useState<Record<number, SugerenciaFrente>>({})
  // El motivo por el que un paquete no recibió propuesta, y el panel de correspondencias que lo
  // resuelve. Van juntos: la fila sin propuesta ofrece el atajo que abre el panel en su rama.
  const [motivos, setMotivos] = useState<Record<number, MotivoSinPropuesta>>({})
  const [panel, setPanel] = useState<PanelCorrespondencias | null>(null)
  const [panelAbierto, setPanelAbierto] = useState(false)
  const [ramaFoco, setRamaFoco] = useState<string | null>(null)
  const [anclas, setAnclas] = useState<AnclaDisponible[]>([])
  // Por qué el desplegable de frentes no ofrece nada. Sin esto, «no hay cronograma», «no tienes
  // permiso» y «se cayó la petición» se ven idénticos: una lista vacía. Ver motivoSinAnclas().
  const [anclasFallo, setAnclasFallo] = useState<string | null>(null)
  const [anclasCargando, setAnclasCargando] = useState(true)
  const motivoAnclas = motivoSinAnclas(anclas, frentes, anclasFallo, anclasCargando)
  // Cuántos frentes del cronograma no se pudieron ofrecer por no tener ninguna actividad debajo.
  // Es el hermano de `motivoAnclas`: aquel explica una lista vacía, este una lista INCOMPLETA, que
  // es más traicionera porque parece completa.
  const [frentesSinAncla, setFrentesSinAncla] = useState(0)
  const avisoSinAncla = avisoFrentesSinAncla(frentesSinAncla)
  // Bloqueante del review final A4: true solo cuando la petición de sugerencias ya resolvió (con
  // éxito o sin él). Sin esto, el efecto de preselección de abajo no puede distinguir «todavía no
  // sabemos si hay propuesta» de «ya sabemos que no la hay» — ver preseleccionDestinos().
  const [sugerenciasCargadas, setSugerenciasCargadas] = useState(false)
  const [desfases, setDesfases] = useState<Desfase[]>([])
  const [porPaquete, setPorPaquete] = useState<ResumenPaquetes['porPaquete']>([])
  const [expandido, setExpandido] = useState<number | null>(null)
  // Lo que el usuario tiene elegido en cada <select> de "sin frente" mientras dura la sesión;
  // arranca en la propuesta del motor (ver el efecto de preselección más abajo).
  // Indexado por `claveDestino()` —«paquete:lote»— y no por id de paquete: los lotes de un mismo
  // paquete comparten `paqueteId`, así que con una clave numérica los tres compartirían desplegable.
  const [destinos, setDestinos] = useState<Record<string, number | ''>>({})
  // Corrige la columna «Responsable» cuando el POST falla. AG Grid muta `data.responsable`
  // in-place al confirmar la edición (antes de saber si el guardado tuvo éxito); este mapa es lo
  // único que puede devolver la celda a lo último confirmado — ver trasGuardarEdicion.
  const [responsableOverride, setResponsableOverride] = useState<Record<number, string>>({})
  const [elegibles, setElegibles] = useState<ResponsableElegible[]>([])
  // Filas marcadas con el checkbox de la grilla, para la asignación en masa (Task 5).
  const [seleccionados, setSeleccionados] = useState<number[]>([])
  // Arranca en el centinela, no en '' : '' significa «quitar el responsable», y con él de valor
  // inicial la pantalla ofrecía esa acción destructiva nada más entrar (ver MASA_SIN_ELEGIR).
  const [masaEtiqueta, setMasaEtiqueta] = useState<string>(MASA_SIN_ELEGIR)
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
        setDestinosContratables(d.destinos ?? [])
        setAmarresDestino(d.amarresDestino ?? [])
        // Cuando se recargan los datos del servidor (la verdad), limpiar el overlay de correcciones
        // pendientes: si un guardado falló y dejó un responsable revertido, este nuevo dato lo supera.
        setResponsableOverride({})
      })
      .catch((e) => {
        setPlan([]); setAmarres({}); setDestinosContratables([]); setAmarresDestino([])
        dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
      })
    // `sinAncla` es opcional en el tipo a propósito: un bundle servido desde una caché vieja puede
    // hablar con un servidor que ya lo manda, y al revés. Sin dato, 0 = no se avisa de nada.
    apiGet<{ frentes: FrenteDisponible[]; sinAncla?: number }>('/plan-compras/api/plan/frentes')
      .then((d) => { setFrentes(d.frentes); setFrentesSinAncla(d.sinAncla ?? 0) })
    // Las anclas incluyen las 242 actividades: hay ramas sin frente propio cuyo hito real es una
    // actividad concreta (CUBIERTA ancla en «LOSA AÉREA CUBIERTA»).
    setAnclasCargando(true)
    apiGet<{ anclas: AnclaDisponible[] }>('/plan-compras/api/plan/anclas')
      .then((d) => { setAnclas(d.anclas); setAnclasFallo(null) })
      // El mensaje se guarda en vez de descartarse: es lo único que distingue un 403 de una caída de
      // red, y quien mira la pantalla es quien puede actuar sobre esa diferencia.
      .catch((e) => { setAnclas([]); setAnclasFallo(mensajeError(e)) })
      .finally(() => setAnclasCargando(false))
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
  // «Sin frente» enumera UNIDADES CONTRATABLES: un paquete partido en tres da tres filas, cada una
  // con su propio frente. `paquetesSinFrente` se conserva para el resto de las cuentas de la pantalla,
  // que siguen razonando por paquete.
  const sinFrente = useMemo(
    () => destinosSinFrente(destinosContratables, amarresDestino),
    [destinosContratables, amarresDestino],
  )
  const sinFrentePorPaquete = useMemo(() => paquetesSinFrente(porPaquete, amarres), [porPaquete, amarres])
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
    setDestinos((prev) => preseleccionDestinos(prev, sinFrentePorPaquete, sugerencias, sugerenciasCargadas))
  }, [sinFrentePorPaquete, sugerencias, sugerenciasCargadas])

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
    // El centinela no es una elección: el botón ya está apagado en ese caso, y esto es el cinturón
    // por si alguna vez se llega aquí por otra vía. Mandarlo al servidor equivaldría a «quitar».
    if (paqueteIds.length === 0 || etiqueta === MASA_SIN_ELEGIR) return
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
      // El mensaje nombra lo que de verdad pasó: decir «asignado» tras vaciar un lote de veinte
      // deja al usuario creyendo lo contrario de lo que acaba de hacer.
      dispatch({
        type: 'LISTO',
        mensaje: etiqueta === ''
          ? `Responsable quitado a ${plural(paqueteIds.length, 'paquete')}.`
          : `Responsable asignado a ${plural(paqueteIds.length, 'paquete')}.`,
      })
      // El lote se guardó: limpiar la selección y la persona elegida, para no repetir el mismo
      // envío por accidente sobre un lote que ya quedó asignado.
      setSeleccionados([])
      setMasaEtiqueta(MASA_SIN_ELEGIR)
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

  /**
   * A4.2 — la obra corrige la duración de un paso.
   *
   * Recarga entera y no solo la fila: el servidor recalcula TODO el plan de la obra, así que las
   * fechas de otros paquetes que compartan esa fila del catálogo también se movieron. Refrescar
   * solo la fila dejaría el resto de la pantalla mostrando fechas que ya no son.
   */
  const onGuardarDuracionObra = async (duracionRef: number, columna: string, dias: number) => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/duraciones/obra', { duracionRef, dias: { [columna]: dias } })
      dispatch({ type: 'LISTO', mensaje: 'Duración guardada para esta obra.' })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
      cargar()   // el campo vuelve a mostrar lo que el servidor tiene, no lo que se tecleó
    }
  }

  const onRestablecerDuracionObra = async (duracionRef: number, columna: string) => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/duraciones/obra/borrar', { duracionRef, columnas: [columna] })
      dispatch({ type: 'LISTO', mensaje: 'Este paso vuelve a la duración de la empresa.' })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }

  // B2 · reprogramación. `null` = no hay nada simulado en pantalla. La simulación vive solo aquí:
  // no la escribió nadie en la base, así que cancelar es tirarla y ya — no hay nada que deshacer.
  const [simulacion, setSimulacion] = useState<SimulacionReprogramacion | null>(null)
  // El titular del panel. Se memoriza en vez de recalcularlo en cada trozo del JSX: son cuatro
  // cifras del mismo recorrido y pintarlas por separado invitaba a que discreparan entre sí.
  const delta = useMemo(() => resumenDelta(simulacion?.movidos ?? []), [simulacion])

  const onSimularReprogramacion = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const s = await apiGet<SimulacionReprogramacion>('/plan-compras/api/plan/reprogramacion/simular')
      setSimulacion(s)
      dispatch({ type: 'LISTO', mensaje: '' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }

  const onAplicarReprogramacion = async () => {
    if (simulacion === null) return
    // Se manda la lista explícita de lo que se enseñó, no un «aplica todo»: si el cronograma
    // cambiara entre mirar y confirmar, el backend ignora lo que ya no cuadra en vez de mover
    // paquetes que el usuario nunca vio en el delta.
    const ids = simulacion.movidos.map((m) => m.paqueteId)
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ aplicados: number; ignorados: number }>(
        '/plan-compras/api/plan/reprogramacion/aplicar',
        { paqueteIds: ids },
      )
      setSimulacion(null)
      dispatch({
        type: 'LISTO',
        mensaje: r.ignorados > 0
          ? `Reprogramados ${r.aplicados} ${plural(r.aplicados, 'paquete', 'paquetes')}. ${r.ignorados} quedaron sin aplicar por no tener frente vivo.`
          : `Reprogramados ${r.aplicados} ${plural(r.aplicados, 'paquete', 'paquetes')}.`,
      })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }

  const onAmarrar = async (
    paqueteId: number,
    uniqueId: number,
    anterior: number | '',
    subpaqueteId = 0,
  ) => {
    const frente = frentes.find((f) => f.uniqueId === uniqueId)
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/amarrar', {
        paqueteId,
        subpaqueteId,
        uniqueId,
        // La procedencia solo viaja para el paquete entero: la sugerencia del motor es del paquete, y
        // atribuirle el amarre de un lote le contaría un acierto que no tuvo.
        procedencia: subpaqueteId === 0 ? procedenciaConSugerencia(sugerencias[paqueteId], uniqueId) : {},
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

  const onAmarrarClick = (paqueteId: number, subpaqueteId = 0) => {
    const valor = destinos[claveDestino({ paqueteId, subpaqueteId })]
    if (valor === undefined || valor === '') return
    void onAmarrar(paqueteId, valor, valor, subpaqueteId)
  }

  // Acción masiva: acepta de golpe la propuesta del motor para todos los «sin frente» que la
  // tienen, tal como quedó preseleccionada — mismo patrón que «Aceptar sugeridos» en
  // PaquetesContratacion. Sin este botón, aceptar 50 propuestas exige 50 clics uno por uno.
  // Las piezas del MOTOR razonan por paquete y no por lote, a propósito: el motor no aprende de
  // lotes (son casuística de obra), y preseleccionar la sugerencia del paquete en sus tres lotes les
  // daría a los tres el mismo frente — exactamente lo contrario de «a cada uno su fecha».
  const sugeridosPendientes = useMemo(
    () => sinFrentePorPaquete.filter((p) => sugerencias[p.paqueteId] !== undefined),
    [sinFrentePorPaquete, sugerencias],
  )
  const porConfianza = useMemo(
    () => agruparPorConfianza(sinFrentePorPaquete, sugerencias),
    [sinFrentePorPaquete, sugerencias],
  )
  const [confirmarMedia, setConfirmarMedia] = useState(false)
  const [buscaSinFrente, setBuscaSinFrente] = useState('')
  const [buscaPlan, setBuscaPlan] = useState('')

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
      const destino = destinoDePaquete(destinos, p.paqueteId)
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
    { ...TEXTO_LARGO, headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 172 },
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
    { ...COLUMNA_FECHA, headerName: 'Arranque', field: 'fechaArranque' },
    // «Necesidad en obra» no cabía en el encabezado y salía «Necesidad …»; la columna de al lado ya
    // dice «Arranque», así que el contexto lo pone la pareja.
    { ...COLUMNA_FECHA, headerName: 'Necesidad', field: 'fechaAncla' },
    { ...columnaNumero('diasTotales', 'Días'), colId: 'dias' },
    {
      headerName: 'Responsable', colId: 'responsable', field: 'responsableNombre',
      flex: 1, minWidth: 160, editable: true,
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
      // Envuelve: «172 días de retraso» no cabe de una línea en lo que deja el shell, y recortado
      // a «172 d…» pierde justo la unidad que lo hace legible.
      headerName: 'Estado', colId: 'estado', flex: 1, minWidth: 132, sortable: false,
      wrapText: true, autoHeight: true,
      valueGetter: (p) => (p.data ? estadoFila(p.data, desfasePorPaquete.get(p.data.paqueteId)).etiqueta : ''),
      cellClass: (p) => (p.data ? `pdc-plan-estado pdc-plan-estado--${estadoFila(p.data, desfasePorPaquete.get(p.data.paqueteId)).clave}` : undefined),
    },
    {
      // Deshacer el amarre. Era el hallazgo más serio de la revisión: una vez amarrado no había
      // forma de volver atrás desde la interfaz.
      colId: 'desamarrar', headerName: '', width: 116, maxWidth: 116, sortable: false, suppressAutoSize: true,
      cellClass: 'pdc-celda-accion',
      valueGetter: () => 'Desamarrar',
    },
  ], [responsableOverride, desfasePorPaquete, elegibles, frentes])

  // Nueve columnas no caben legibles en el ancho que deja el shell. «Días» sale de restar las dos
  // fechas que quedan a la vista; el responsable se consulta al abrir la fila. «Estado» va al final
  // de la lista a propósito: es el semáforo por el que se abre esta pantalla.
  const [refGrid, anchoGrid] = usaAnchoContenedor()
  const colsVisibles = useMemo(
    // 44 px: la columna de casillas de selección múltiple, que la pone `rowSelection` y no está en
    // `cols`.
    () => columnasQueCaben(cols, anchoGrid, ['dias', 'responsable', 'estado'], 44),
    [cols, anchoGrid],
  )

  // Qué ofrece el botón de masa ahora mismo — y, si no ofrece nada, por qué. La regla vive en
  // `lib/planFechas.ts` con prueba propia: es la que impide que la acción por defecto sea destructiva.
  const accionMasa = accionMasaResponsable({
    marcados: seleccionados.length, etiqueta: masaEtiqueta, ocupado: ui.ocupado,
  })

  const filaExpandida = plan.find((f) => f.paqueteId === expandido) ?? null

  return (
    <section className="pdc-bloque pdc-plan">
      <header className="pdc-paq-header">
        <div>
          <div className="pdc-titulo-fila"><h1>Plan de compras</h1><BotonAyuda pantalla="plan" /></div>
          <p className="pdc-sub">Qué hay que empezar a contratar y cuándo — lo vencido va primero.</p>
        </div>
        {/* Misma forma que la cobertura de Paquetes de contratación, a propósito: quien pasa de una
            pantalla a otra reconoce el indicador sin releerlo. El número grande es la plata, no el
            conteo — un paquete de acero pesa lo que cincuenta de ferretería, y el porcentaje por
            conteo esconde justo eso. */}
        <div data-testid="pdc-plan-cobertura" className="pdc-paq-cobertura">
          <div className="pdc-paq-cobertura-num">{cobertura.porcentajeValor}%</div>
          {/* El número grande es del VALOR y este detalle es por CONTEO: en Da Porto se leían «73%»
              y «20 de 93 paquetes con fecha» pegados, y 20/93 es 21%, no 73. Dos métricas distintas
              presentadas como si fueran la misma cifra. Nombrar la unidad cuesta dos palabras y
              deshace la contradicción sin tocar ningún cálculo. */}
          <div className="pdc-paq-cobertura-detalle">
            del valor · {cobertura.conFecha} de {cobertura.total} paquetes con fecha
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
        <Selector
          testid="pdc-plan-masa-persona"
          etiqueta="Persona para asignar a los paquetes seleccionados"
          value={masaEtiqueta}
          onChange={setMasaEtiqueta}
          disabled={ui.ocupado || seleccionados.length === 0}
          // Resolución del merge con main (2026-08-07): manda la semántica de main, expresada con
          // el Selector de esta rama. El arranque es un marcador de posición, no una acción (ver
          // MASA_SIN_ELEGIR): al entrar, la pantalla llegó a ofrecer «quitar responsable» sin que
          // nadie lo pidiera. Quitarlo a un lote sigue siendo posible, pero hay que elegirlo a
          // propósito, y la opción lo dice entera en vez de esconderse tras un «Sin asignar» a secas.
          // El centinela va DENTRO de la lista, no como `placeholder`: el placeholder no es
          // elegible, y aquí el valor de arranque tiene que poder recuperarse.
          // Fila «vacía» deliberada: opcionesResponsable necesita una fila para saber si debe sumar
          // una opción extra de huérfano, y aquí no hay una fila puntual — solo la lista general de
          // gente elegible del proyecto (siempre empieza en '' = "Sin asignar").
          opciones={[
            { valor: MASA_SIN_ELEGIR, etiqueta: 'Elige a quién asignar…' },
            ...opcionesResponsable(elegibles, { responsableUserId: null, responsableNombre: '', responsableCargo: '', responsableHuerfano: false })
              .map((o) => ({ valor: o, etiqueta: o === '' ? 'Sin asignar — quitar responsable' : o })),
          ]}
        />
        <button
          type="button"
          data-testid="pdc-plan-masa-asignar"
          data-accion={accionMasa.accion}
          disabled={accionMasa.deshabilitado}
          onClick={() => void onResponsableMasa(seleccionados, masaEtiqueta)}
        >
          {accionMasa.texto}
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

      <input
        {...propsBuscador('Buscar en el plan de fechas', 'pdc-plan-buscar')}
        value={buscaPlan}
        onChange={(e) => setBuscaPlan(e.target.value)}
      />
      <AvisoColumnasOcultas columnas={colsVisibles} testid="pdc-plan-cols-ocultas" />
      <div data-testid="pdc-plan-grid" className="pdc-grid-wrap" ref={refGrid}>
        <AgGridReact<FilaPlan>
          theme={pdcTheme}
          rowData={planVisible}
          // Identidad de fila estable: el plan se recalcula entero y vuelve como filas nuevas.
          // Sin esto AG Grid las empareja por posicion y pierde el estado de la fila abierta.
          // La fila es un DESTINO (paquete + lote), no un paquete: un paquete partido en tres
          // trae tres filas con el mismo paqueteId, y un id repetido rompe la grilla entera.
          getRowId={(p) => `${p.data.paqueteId}:${p.data.subpaqueteId}`}
          quickFilterText={buscaPlan}
          overlayNoRowsTemplate={vacioTabla("Todavía no hay paquetes con plan calculado. Amarra un paquete a un frente y pulsa «Recalcular».")}
          columnDefs={colsVisibles}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
          // Igual que en Paquetes: sin `autoHeight`, la grilla scrollea por dentro en vez de
          // estirar la página y dejar la barra de acciones fuera de la pantalla.
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

      {filaExpandida && (
        <div className="pdc-plan-detalle" data-testid="pdc-plan-detalle">
          <h3>Pasos de «{filaExpandida.nombre}»</h3>
          {/* Dice lo CONTRARIO que el aviso del catálogo de pasos, y a propósito: allá se cambia el
              estándar de la empresa, aquí solo esta obra. */}
          <p className="pdc-sub" data-testid="pdc-plan-pasos-alcance">
            Estos días son de esta obra: cambiarlos mueve las fechas de este paquete aquí, y no
            toca a las demás obras.
          </p>
          <table className="pdc-plan-pasos">
            <thead>
              {/* «Hasta», no «Fin»: el intervalo de cada paso es medio abierto —esa fecha es la
                  frontera con el paso siguiente, no su último día trabajado (ver la convención en
                  PlanFechasService::calcular()). Con «Fin», «7 días · 23 may → 30 may» se lee como
                  ocho días y como si dos pasos compartieran uno; «Hasta» dice lo que el dato es. */}
              <tr><th>Paso</th><th>Días</th><th>Inicio</th><th>Hasta</th><th>Estado</th></tr>
            </thead>
            <tbody>
              {filaExpandida.pasos.map((p) => (
                <tr key={p.orden}>
                  <td>{p.paso}</td>
                  <td className={esCorregido(p.origen) ? 'pdc-dias-obra' : undefined}>
                    <input
                      type="number"
                      min={0}
                      /* Remonta cuando el servidor devuelve otro número: el campo no es controlado,
                         así que sin esta clave restablecer dejaría en pantalla el valor viejo. */
                      key={`${p.orden}-${p.dias}-${p.origen}`}
                      className="pdc-dias-input"
                      data-testid={`pdc-plan-paso-dias-${p.orden}`}
                      defaultValue={p.dias}
                      disabled={filaExpandida.duracionRef === null || p.colLegacy === null}
                      aria-label={`Días de «${p.paso}»${esCorregido(p.origen) ? ', corregido por esta obra' : ', valor de la empresa'}`}
                      onBlur={(e) => {
                        const v = validarDias(e.target.value)
                        if (!v.ok) {
                          dispatch({ type: 'FALLO', mensaje: v.motivo })
                          e.target.value = String(p.dias)
                          return
                        }
                        if (v.dias === p.dias) return
                        void onGuardarDuracionObra(filaExpandida.duracionRef as number, p.colLegacy as string, v.dias)
                      }}
                    />
                    {esCorregido(p.origen) && (
                      <button
                        type="button"
                        className="pdc-paq-secundario"
                        data-testid={`pdc-plan-paso-restablecer-${p.orden}`}
                        onClick={() => void onRestablecerDuracionObra(filaExpandida.duracionRef as number, p.colLegacy as string)}
                      >
                        Volver al de la empresa
                      </button>
                    )}
                  </td>
                  <td>{p.fechaInicio}</td><td>{p.fechaFin}</td>
                  {/* El corte lo decide el servidor con la misma función que la pestaña de
                      Vencimientos: aquí solo se le pone color y palabra. Calcularlo en el navegador
                      sería la forma más fácil de que la lista y el color acaben diciendo cosas
                      distintas sobre el mismo paso. */}
                  <td className={claseCorte(p.vencimiento)} data-testid={`pdc-plan-paso-estado-${p.orden}`}>
                    {etiquetaCorte(p.vencimiento)}
                  </td>
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
      {/* El aviso va arriba y una sola vez, no repetido en cada fila: el motivo es del proyecto
          entero, no de un paquete. Sin él, 96 desplegables vacíos no dicen nada 96 veces. */}
      {motivoAnclas && <p className="pdc-flujo-nota" role="status">{motivoAnclas}</p>}
      {avisoSinAncla && (
        <p className="pdc-flujo-nota" data-testid="pdc-plan-frentes-sin-ancla" role="status">
          {avisoSinAncla}
        </p>
      )}
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
                const elegido = destinoDePaquete(destinos, p.paqueteId)
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
                        {/* Nunca dentro de un <label>: ver el comentario largo de Seguimiento.tsx —
                            un <label> sin htmlFor reenvía un click sintético al <button> del Selector
                            y reabre el popup justo tras cerrarlo. */}
                        <span className="pdc-selector">
                          <Selector
                            etiqueta={`Nodo del cronograma para ${rama}`}
                            placeholder="Elegir nodo del cronograma…"
                            disabled={ui.ocupado}
                            // Sin estado propio (igual que el <select> original con defaultValue=""):
                            // el guardado dispara la recarga del panel, así que no hay valor que
                            // conservar entre una rama y la siguiente.
                            value=""
                            onChange={(valor) => {
                              const a = anclas.find((x) => String(x.uniqueId) === valor)
                              if (a) void onGuardarCorrespondencia(rama, a.nombre, 'proyecto')
                            }}
                            opciones={anclasOrdenadas(anclas).map((a) => ({ valor: String(a.uniqueId), etiqueta: opcionAncla(a) }))}
                          />
                        </span>
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
        {filtraPorTexto(sinFrente, buscaSinFrente, (x) => etiquetaDestino(x)).map((p) => {
          // La identidad de la fila es paquete + lote. Con la clave puesta solo en el paquete, los
          // tres lotes de un paquete partido compartían desplegable: elegir el frente de uno lo
          // elegía en los tres, y amarrar uno los sacaba a todos de la lista.
          const clave = claveDestino(p)
          // El motor sugiere por paquete y no por lote; en un lote no hay propuesta que mostrar, y
          // enseñar la del paquete invitaría a darle a los tres el mismo frente.
          const sugerencia = p.esLote ? undefined : sugerencias[p.paqueteId]
          const destino = destinos[clave] ?? ''
          return (
            <li key={clave}>
              <strong>{etiquetaDestino(p)}</strong>
              {p.esLote && <span className="pdc-paq-tag">lote de obra</span>}
              <span className="pdc-paq-meta">{moneda(p.valor)}</span>
              {/* Nunca dentro de un <label>: ver el comentario largo de Seguimiento.tsx — un <label>
                  sin htmlFor reenvía un click sintético al <button> del Selector y reabre el popup
                  justo tras cerrarlo. Crítico del review final, sigue vigente: elegir aquí SOLO
                  actualiza `destinos` — el amarre lo dispara únicamente el botón «Amarrar» de abajo. */}
              <span className="pdc-selector">
                <Selector
                  testid={`pdc-plan-frente-${clave}`}
                  etiqueta={`Frente para ${etiquetaDestino(p)}`}
                  placeholder="Elegir frente…"
                  disabled={ui.ocupado}
                  value={String(destino)}
                  onChange={(valor) => {
                    const nuevo = valor === '' ? '' : Number(valor)
                    setDestinos((prev) => ({ ...prev, [clave]: nuevo }))
                  }}
                  // La fecha siempre va en la etiqueta: el cronograma repite nombres de frente en
                  // fechas distintas y sin la fecha las opciones son indistinguibles. Los frentes van
                  // primero y las actividades después, marcadas: son 242 y enterrarían a los 31
                  // frentes, que es lo que casi siempre se busca.
                  opciones={(anclas.length > 0 ? anclasOrdenadas(anclas) : frentes.map((f) => ({ ...f, esFrente: true }))).map((f) => ({ valor: String(f.uniqueId), etiqueta: opcionAncla(f) }))}
                />
              </span>
              {/* Sin opciones, el motivo se dice aparte del desplegable: el Selector no admite una
                  opción deshabilitada como aviso, así que va como texto junto al control. */}
              {motivoAnclas && (anclas.length > 0 ? anclasOrdenadas(anclas) : frentes).length === 0 && (
                <span className="pdc-paq-meta">{motivoAnclas}</span>
              )}
              {/* Único disparador del amarre (Crítico del review final): elegir en el <select> ya no
                  basta — la opción preseleccionada con la propuesta del motor no emite `change`, así
                  que sin este botón aceptarla tal cual era imposible desde la interfaz. */}
              <button
                type="button"
                data-testid={`pdc-plan-amarrar-${clave}`}
                className="pdc-paq-primario"
                disabled={ui.ocupado || destino === ''}
                onClick={() => onAmarrarClick(p.paqueteId, p.subpaqueteId)}
              >
                Amarrar
              </button>
              {/* El origen viaja en el `title` y no en el texto: escrito entero
                  («CORRESPONDENCIA · CONFIANZA ALTA») el chip se llevaba 190 px de la fila, casi
                  cinco veces lo que le quedaba al nombre del paquete. Lo que se decide mirando es
                  la confianza; el origen se consulta, y para eso basta con posarse encima. */}
              {sugerencia && (
                <span
                  className={`pdc-paq-tag conf-${sugerencia.confianza}`}
                  title={`Origen de la propuesta: ${sugerencia.origen}`}
                >
                  confianza {sugerencia.confianza}
                </span>
              )}
              {/* Sin propuesta ya no es una fila muda: dice qué rama falta y ofrece resolverla. */}
              {!sugerencia && !p.esLote && motivos[p.paqueteId] && (
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
        {sinFrente.length === 0 && (
          <li className="pdc-vacio">
            Todas las contrataciones que generan proceso ya tienen frente, lotes de obra incluidos.
          </li>
        )}
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
      <p className="pdc-sub">
        El cronograma se reprogramó después de amarrar estos paquetes. No se aplica solo: primero
        mira qué cambia y luego decides.
      </p>
      <ul className="pdc-paq-lista" data-testid="pdc-plan-desfases">
        {desfases.map((d) => (
          <li key={d.paqueteId}>
            <strong>{d.nombre}</strong>
            <span className="pdc-paq-meta">{etiquetaDesfase(d)}</span>
          </li>
        ))}
        {desfases.length === 0 && <li className="pdc-vacio">Ningún amarre quedó desactualizado.</li>}
      </ul>

      {/* Antes había aquí un «Recalcular todo el plan» por fila. No arreglaba nada: `calcular()`
          proyecta contra la fecha del amarre, que es una copia congelada del cronograma, así que
          el aviso seguía ahí después de pulsarlo (medición del 2026-07-29). Lo reemplaza el par
          simular → aplicar, que sí refresca esa fecha y solo sobre lo que el usuario confirmó. */}
      {desfases.length > 0 && simulacion === null && (
        <button
          type="button"
          className="pdc-paq-primario"
          data-testid="pdc-plan-simular-reprogramacion"
          disabled={ui.ocupado}
          onClick={() => void onSimularReprogramacion()}
        >
          Ver qué cambia
        </button>
      )}

      {simulacion !== null && (
        <div className="pdc-panel" data-testid="pdc-plan-delta-reprogramacion">
          <p data-testid="pdc-plan-delta-resumen">
            Se moverían <strong>{plural(delta.paquetes, 'paquete')}</strong> — {delta.atrasan} se
            atrasan y {delta.adelantan} se adelantan.{' '}
            <strong>{plural(delta.pasosProtegidos, 'paso')}</strong> ya{' '}
            {delta.pasosProtegidos === 1 ? 'ocurrió y conserva' : 'ocurrieron y conservan'} su fecha
            real.
          </p>
          <ul className="pdc-paq-lista">
            {simulacion.movidos.map((m) => (
              <li key={m.paqueteId}>
                <strong>{m.nombre}</strong>
                <span className="pdc-paq-meta">{etiquetaMovimiento(m)}</span>
              </li>
            ))}
            {simulacion.movidos.length === 0 && (
              <li className="pdc-vacio">Ningún paquete tiene un frente vivo al que moverse.</li>
            )}
          </ul>
          {simulacion.huerfanos.length > 0 && (
            <p data-testid="pdc-plan-delta-huerfanos">
              {plural(simulacion.huerfanos.length, 'paquete')}{' '}
              {simulacion.huerfanos.length === 1 ? 'apunta' : 'apuntan'} a un frente que ya no está
              en el cronograma. No se reamarran solos: amárralos a mano desde la grilla.
            </p>
          )}
          <button
            type="button"
            className="pdc-paq-primario"
            data-testid="pdc-plan-aplicar-reprogramacion"
            disabled={ui.ocupado || simulacion.movidos.length === 0}
            onClick={() => void onAplicarReprogramacion()}
          >
            Aplicar a {plural(simulacion.movidos.length, 'paquete')}
          </button>
          <button
            type="button"
            data-testid="pdc-plan-cancelar-reprogramacion"
            onClick={() => setSimulacion(null)}
          >
            Cancelar
          </button>
        </div>
      )}
      </PanelPestana>
      )}
    </section>
  )
}
