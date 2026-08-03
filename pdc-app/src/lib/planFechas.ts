import type { AnclaDisponible, Desfase, FilaPlan, FrenteDisponible, PanelCorrespondencias, ProcedenciaAmarre, ResponsableElegible, ResumenPaquetes, SugerenciaFrente, DestinoContratable} from './types'

export type EstadoFila = { clave: 'desfasado' | 'vencido' | 'provisional' | 'en-plazo'; etiqueta: string }

/**
 * El estado que se pinta en cada fila. Un desfase manda sobre todo lo demás: si el cronograma se
 * reprogramó después de amarrar, el arranque calculado ya no corresponde al frente vigente — ni
 * "vencido" ni "en plazo" son verdad, porque las fechas mostradas están calculadas contra un frente
 * que ya no existe con esa fecha. Debajo de eso, lo vencido manda sobre lo provisional: un plazo
 * aproximado importa, pero una contratación que debió arrancar hace dos meses importa más.
 */
export function estadoFila(f: FilaPlan, desfase?: Desfase): EstadoFila {
  if (desfase) {
    return { clave: 'desfasado', etiqueta: `Desactualizado: ${etiquetaDesfase(desfase)}` }
  }
  if (f.diasRetraso > 0) {
    return { clave: 'vencido', etiqueta: `${f.diasRetraso} días de retraso` }
  }
  if (f.duracionProvisional) {
    return { clave: 'provisional', etiqueta: 'plazo estimado' }
  }
  return { clave: 'en-plazo', etiqueta: 'en plazo' }
}

/**
 * Cuánto del plan está hecho. El encabezado decía «11 paquete(s)» y parecía el total, pero al lado
 * vivían 85 esperando frente: el entregable final era el único sitio del módulo sin indicador de
 * cobertura, justo donde más falta hace.
 *
 * Se mide **por valor y por conteo** (decisión del grilleo) porque no dicen lo mismo: un paquete de
 * acero pesa lo que cincuenta de ferretería, y el porcentaje por conteo esconde exactamente eso.
 *
 * El denominador son **solo los paquetes que generan proceso**. Nómina, imprevistos y consumo
 * directo no se le compran a nadie y nunca van a tener fecha; contarlos dejaría el plan en un
 * porcentaje que no puede llegar a 100 ni haciéndolo todo bien.
 */
export function coberturaPlan(
  porPaquete: PaquetePorProyecto[],
  amarres: Record<number, unknown>,
): {
  conFecha: number; total: number; porcentajeConteo: number
  valorConFecha: number; valorTotal: number; porcentajeValor: number
} {
  const conProceso = porPaquete.filter((p) => generaProceso(p.modalidad))
  // Amarrado cuenta como cubierto aunque no tenga plan calculado: la decisión difícil ya se tomó y
  // lo que falta es pulsar «Recalcular». Lo vigila el aviso aparte de `paquetesAmarradosSinCalcular`.
  const cubiertos = conProceso.filter((p) => p.paqueteId in amarres)
  const valorTotal = sumaValor(conProceso)
  const valorConFecha = sumaValor(cubiertos)
  return {
    conFecha: cubiertos.length,
    total: conProceso.length,
    porcentajeConteo: porcentaje(cubiertos.length, conProceso.length),
    valorConFecha,
    valorTotal,
    porcentajeValor: porcentaje(valorConFecha, valorTotal),
  }
}

/** Suma la cuantía de un grupo de paquetes. Es lo que se arriesga al aceptar propuestas en masa. */
export function sumaValor(paquetes: PaquetePorProyecto[]): number {
  return paquetes.reduce((acc, p) => acc + p.subtotal, 0)
}

/** Un total de cero devuelve 0 %, no NaN ni 100 %: hay paquetes reales que valen $ 0. */
function porcentaje(parte: number, total: number): number {
  if (total <= 0) return 0
  return Math.round((parte / total) * 100)
}

/**
 * Lo vencido, resumido para la franja de alerta. Tres paquetes con 98, 83 y 66 días de retraso
 * eran hasta ahora texto pequeño dentro de su fila: lo más grave del proyecto, contado en voz baja.
 */
export function resumenVencidos(filas: FilaPlan[]): { cuantos: number; diasMaximo: number } {
  const vencidas = filas.filter((f) => f.diasRetraso > 0)
  return {
    cuantos: vencidas.length,
    diasMaximo: vencidas.reduce((max, f) => Math.max(max, f.diasRetraso), 0),
  }
}

/**
 * Las propuestas del motor, partidas por confianza. Sin esto, el botón masivo aceptaba las 40 de
 * una vez sin decir que 37 eran de confianza media —deducidas de la actividad padre, no de la
 * descripción del insumo— y solo 3 de confianza alta.
 *
 * Las de confianza **baja** salen agrupadas para poder contarlas, pero ningún botón masivo las
 * consume: se aceptan una a una desde su fila.
 */
export function agruparPorConfianza(
  sinFrente: PaquetePorProyecto[],
  sugerencias: Record<number, SugerenciaFrente>,
): { alta: PaquetePorProyecto[]; media: PaquetePorProyecto[]; baja: PaquetePorProyecto[] } {
  const grupos = { alta: [], media: [], baja: [] } as Record<
    SugerenciaFrente['confianza'], PaquetePorProyecto[]
  >
  for (const p of sinFrente) {
    const s = sugerencias[p.paqueteId]
    if (s) grupos[s.confianza].push(p)
  }
  return grupos
}

export function resumenPlan(filas: FilaPlan[]): { total: number; vencidos: number; provisionales: number } {
  return {
    total: filas.length,
    vencidos: filas.filter((f) => f.diasRetraso > 0).length,
    provisionales: filas.filter((f) => f.duracionProvisional).length,
  }
}

/**
 * Etiqueta del desplegable de frentes. Siempre lleva la fecha, no solo cuando hay ambigüedad: el
 * cronograma repite nombres de frente en fechas distintas (PISOS Y ENCHAPES el 12-may y el 8-jul) y
 * sin la fecha las dos opciones son indistinguibles.
 */
export function opcionFrente(f: FrenteDisponible): string {
  return `${f.nombre} — ${f.fechaInicio}`
}

/** Modalidades que generan proceso de contratación con fecha (A3.2). El resto (consumo directo, no
 *  contratable) no debería pedir un frente nunca. */
const MODALIDADES_CON_PROCESO = new Set(['contrato', 'orden_compra'])

export function generaProceso(modalidad?: string): boolean {
  return MODALIDADES_CON_PROCESO.has(modalidad ?? 'contrato')
}

/**
 * Qué se le cuenta al motor por amarrar un paquete a un frente. Mismo criterio que
 * `procedenciaDeAsignacion` en paqueteWizardState.ts: aceptar la propuesta tal cual es un acierto
 * confirmado. A diferencia de aquel caso, aquí no hay nada que perseguir cuando se elige otro frente
 * —no hay "corrección" del motor en A4, solo el amarre que quedó— así que ese caso no deja procedencia.
 */
export function procedenciaDeAmarre(
  sugerencia: SugerenciaFrente | undefined,
  uniqueIdElegido: number,
): ProcedenciaAmarre | undefined {
  if (!sugerencia || sugerencia.uniqueId !== uniqueIdElegido) return undefined
  return { origen: sugerencia.origen, confianza: sugerencia.confianza, evidencia: sugerencia.evidencia, confirmado: true }
}

type PaquetePorProyecto = ResumenPaquetes['porPaquete'][number]

/**
 * Paquetes que deberían tener fecha y todavía no la tienen: generan proceso, tienen insumos
 * asignados en este proyecto y no aparecen en `amarres`. Orden por cuantía descendente, igual
 * criterio que el resto del sembrado: lo caro primero.
 */
export function paquetesSinFrente(
  porPaquete: PaquetePorProyecto[],
  amarres: Record<number, unknown>,
): PaquetePorProyecto[] {
  return porPaquete
    .filter((p) => generaProceso(p.modalidad) && !(p.paqueteId in amarres))
    .sort((a, b) => b.subtotal - a.subtotal)
}

/** La identidad de una fila de «Sin frente»: paquete + lote. Nunca solo el paquete. */
export type { DestinoContratable }

export function claveDestino(d: { paqueteId: number; subpaqueteId: number }): string {
  return `${d.paqueteId}:${d.subpaqueteId}`
}

/**
 * Unidades contratables que deberían tener fecha y todavía no la tienen.
 *
 * Sustituye a `paquetesSinFrente()` en la pantalla, y el cambio de unidad es el punto: un paquete
 * partido en tres aparece como TRES filas, cada una eligiendo su propio frente, porque es cada lote
 * el que se contrata en su momento —«eso lo contrato en 2 meses; eso lo necesito ya»—. Contarlo por
 * paquete dejaría a los lotes sin forma de recibir fecha desde la pantalla.
 *
 * Esta forma se eligió sobre la alternativa de añadir un segundo desplegable de lote a la fila: esa
 * fila ya lleva el frente, la procedencia de la sugerencia y el botón de amarrar, y una segunda
 * elección dentro de la misma fila obliga a leer dos controles para entender una decisión.
 *
 * Orden por cuantía descendente: lo caro primero, igual criterio que el resto del sembrado.
 */
export function destinosSinFrente(
  destinos: DestinoContratable[],
  amarrados: { paqueteId: number; subpaqueteId: number }[],
): DestinoContratable[] {
  const yaAmarrados = new Set(amarrados.map(claveDestino))
  return destinos
    .filter((d) => d.generaProceso && !yaAmarrados.has(claveDestino(d)))
    .sort((a, b) => b.valor - a.valor)
}

/**
 * Cómo se rotula una fila de «Sin frente». Un lote se nombra con su paquete delante —«Pisos ›
 * Porcelanato»— porque su nombre suelto no dice de qué paquete sale, y en una lista de 96 filas
 * «Porcelanato» a secas no se sitúa.
 */
export function etiquetaDestino(d: DestinoContratable): string {
  return d.esLote ? `${d.paqueteNombre} › ${d.nombre}` : d.nombre
}

/**
 * Paquetes que ya tienen frente pero todavía no tienen plan calculado: acaban de amarrarse (o se
 * reamarraron a un frente distinto, que invalida el plan viejo — ver PlanFechasService::amarrar())
 * y nadie ha pulsado «Recalcular» todavía. Sin esta lista, un paquete así sale de «Sin frente»
 * (porque ya está en `amarres`) y no aparece en la grilla (que solo lee `plan`, el calculado):
 * queda invisible en las dos partes de la pantalla a la vez.
 */
export function paquetesAmarradosSinCalcular(
  porPaquete: PaquetePorProyecto[],
  amarres: Record<number, unknown>,
  plan: FilaPlan[],
): PaquetePorProyecto[] {
  const calculados = new Set(plan.map((f) => f.paqueteId))
  return porPaquete
    .filter((p) => generaProceso(p.modalidad) && p.paqueteId in amarres && !calculados.has(p.paqueteId))
    .sort((a, b) => b.subtotal - a.subtotal)
}

/**
 * Semilla de `destinos` (lo elegido en cada <select> de "sin frente") con la propuesta del motor.
 *
 * Bloqueante del review final A4: `sinFrente` y `sugerencias` llegan de dos peticiones HTTP
 * independientes. Si `sinFrente` tiene contenido antes de que lleguen las sugerencias, sembrar cada
 * paquete con `''` deja esa clave fijada para siempre — cuando las sugerencias sí llegan, ya no es
 * `undefined` y el efecto no la vuelve a tocar, así que la propuesta se pierde para esa carga. La
 * espera a `sugerenciasCargadas` (true solo cuando la petición de sugerencias ya resolvió, con éxito
 * o sin él) evita sembrar a ciegas antes de saber si hay o no propuesta para cada paquete.
 *
 * La clave es `claveDestino()` —«paquete:lote»— y no el id de paquete. Al añadir los lotes, la
 * pantalla pasó a leer `destinos['123:0']` mientras esto seguía escribiendo `destinos['123']`, y la
 * preselección del motor dejó de aplicarse **sin que TypeScript dijera nada**: un `Record<number, T>`
 * es asignable a un `Record<string, T>` porque las claves numéricas son un subconjunto de las de
 * texto. El test «siembra con la clave paquete:lote» es lo que fija esto.
 */
export function preseleccionDestinos(
  prev: Record<string, number | ''>,
  sinFrente: { paqueteId: number; subpaqueteId?: number }[],
  sugerencias: Record<number, SugerenciaFrente>,
  sugerenciasCargadas: boolean,
): Record<string, number | ''> {
  if (!sugerenciasCargadas) return prev
  let cambio = false
  const next = { ...prev }
  for (const p of sinFrente) {
    const clave = claveDestino({ paqueteId: p.paqueteId, subpaqueteId: p.subpaqueteId ?? 0 })
    if (next[clave] === undefined) {
      const s = sugerencias[p.paqueteId]
      next[clave] = s ? s.uniqueId : ''
      cambio = true
    }
  }
  return cambio ? next : prev
}

/**
 * El mensaje del desfase. `fechaActual`/`diasMovidos` en null es un caso distinto de «se movió»: el
 * frente amarrado desapareció del cronograma (se borró o se renombró la actividad), y hay que
 * decirlo así en vez de imprimir "null" o reventar.
 */
export function etiquetaDesfase(d: Desfase): string {
  if (d.fechaActual === null || d.diasMovidos === null) {
    return `«${d.frenteNombre}» ya no está en el cronograma`
  }
  return `se movió de ${d.fechaGuardada} a ${d.fechaActual}, ${Math.abs(d.diasMovidos)} día(s)`
}

export function mensajeCalculo(r: { calculados: number; sinDuracion: number }): string {
  const base = `${r.calculados} paquete(s) recalculado(s)`
  return r.sinDuracion > 0 ? `${base}; ${r.sinDuracion} sin duración de referencia.` : `${base}.`
}

/**
 * Reconcilia una edición optimista con lo que en verdad pasó en el servidor.
 *
 * La interfaz muestra el valor tecleado/elegido antes de esperar la respuesta del POST (edición
 * optimista, deseada: no hay parpadeo mientras se espera). El problema que cierra esta tarea es que,
 * cuando el POST falla, nada devolvía la celda a lo último confirmado — se quedaba mostrando un
 * guardado que nunca ocurrió. Este helper es ese "nada": en éxito retira cualquier override pendiente
 * (gana el dato real, ya sea el que mutó AG Grid o el que ya estaba elegido); en fallo fija el valor
 * anterior al intento, sin tocar los overrides de otras filas en curso.
 *
 * Sirve para los dos sitios de la Task 9 (overlay de Responsable y `destinos` del <select> de "sin
 * frente"): mismo problema, mismo criterio de reconciliación.
 */
export function trasGuardarEdicion<T>(
  valores: Record<number, T>,
  id: number,
  resultado: { ok: true } | { ok: false; anterior: T },
): Record<number, T> {
  if (resultado.ok) {
    if (!(id in valores)) return valores // sin override que retirar: no dispares un re-render de balde
    const resto = { ...valores }
    delete resto[id]
    return resto
  }
  return { ...valores, [id]: resultado.anterior }
}

/**
 * Lo que se añade al nombre de quien ya no puede ser responsable. Ver `responsableHuerfano`.
 *
 * Menor del review final A4: el servidor marca huérfano por dos causas distintas (la persona salió
 * del proyecto, o su cuenta se desactivó) con un único booleano que no distingue cuál de las dos
 * fue — «ya no está en el proyecto» afirmaba la primera causa aunque hubiera sido la segunda. Esta
 * redacción es cierta en ambos casos sin inventar una distinción que el backend no manda.
 */
export const MARCA_HUERFANO = ' (ya no está disponible)'

/** Etiqueta con la que una persona se ve y se elige: el cargo desempata nombres parecidos. */
export function etiquetaElegible(persona: Pick<ResponsableElegible, 'nombre' | 'cargo'>): string {
  return persona.cargo ? `${persona.nombre} — ${persona.cargo}` : persona.nombre
}

/**
 * Etiqueta del responsable que trae la fila del servidor. El servidor manda el nombre resuelto (no
 * solo el id) justamente para este caso: a un huérfano no lo encontraríamos en la lista de
 * elegibles, y la celda quedaría en blanco sin explicar por qué.
 */
export function etiquetaResponsableFila(
  fila: Pick<FilaPlan, 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
): string {
  if (fila.responsableUserId === null || fila.responsableNombre === '') return ''
  const base = etiquetaElegible({ nombre: fila.responsableNombre, cargo: fila.responsableCargo })
  return fila.responsableHuerfano ? `${base}${MARCA_HUERFANO}` : base
}

/**
 * Opciones del desplegable. El '' inicial es lo que permite dejar el paquete sin responsable; el
 * huérfano se añade al final solo si es el valor actual de esta fila, porque AG Grid no puede
 * mostrar un valor que no esté entre las opciones — sin esto, abrir el editor de una fila huérfana
 * borraría de la vista al responsable que sí tiene.
 */
export function opcionesResponsable(
  elegibles: ResponsableElegible[],
  fila: Pick<FilaPlan, 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
): string[] {
  const opciones = ['', ...elegibles.map(etiquetaElegible)]
  const actual = etiquetaResponsableFila(fila)
  return actual !== '' && !opciones.includes(actual) ? [...opciones, actual] : opciones
}

/** Traduce lo elegido en el desplegable al id que espera el servidor. Desconocido y '' → sin responsable. */
export function idPorEtiqueta(elegibles: ResponsableElegible[], etiqueta: string): number | null {
  return elegibles.find((e) => etiquetaElegible(e) === etiqueta)?.id ?? null
}

/**
 * Valor que debe verse en la celda «Responsable». AG Grid muta la fila in-place al confirmar la
 * edición (valueSetter por defecto), sin esperar el POST — por eso el override es la única fuente
 * fiable mientras dura la sesión: guarda lo último confirmado, y si el POST falla se le devuelve el
 * valor anterior.
 */
export function valorResponsableMostrado(
  fila: Pick<FilaPlan, 'paqueteId' | 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
  overrides: Record<number, string>,
): string {
  return overrides[fila.paqueteId] ?? etiquetaResponsableFila(fila)
}

/**
 * Cuántos paquetes están pendientes de dueño. Un huérfano cuenta: tiene un nombre escrito, pero esa
 * persona ya no está en el proyecto, así que sigue habiendo trabajo que repartir — dejarlo fuera de
 * la cuenta escondería paquetes que en la práctica no tienen a quién responderle.
 */
export function contarSinResponsable(
  filas: Array<{ responsableUserId: number | null; responsableHuerfano?: boolean }>,
): number {
  return filas.filter((f) => f.responsableUserId === null || f.responsableHuerfano === true).length
}

// Menor del review final A4: `.pdc-info` pintaba también los mensajes de FALLO con el mismo verde
// de éxito, así que una aserción de e2e sobre ese selector pasaba aunque el amarre hubiera fallado.
// `tipo` es lo que permite a la vista pintar (y a un test verificar) cuál de los dos fue.
export type PlanUiState = { ocupado: boolean; mensaje: string | null; tipo: 'exito' | 'error' | null }

export type PlanUiAction =
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialPlanUi: PlanUiState = { ocupado: false, mensaje: null, tipo: null }

export function planUiReducer(state: PlanUiState, action: PlanUiAction): PlanUiState {
  switch (action.type) {
    case 'OCUPADO':
      return { ocupado: true, mensaje: null, tipo: null }
    case 'LISTO':
      return { ocupado: false, mensaje: action.mensaje ?? null, tipo: action.mensaje ? 'exito' : null }
    case 'FALLO':
      return { ocupado: false, mensaje: action.mensaje, tipo: 'error' }
  }
}

/** Columnas que se editan en la propia grilla del plan. */
const COLUMNAS_EDITABLES = new Set(['responsable', 'frente'])

/** Columnas que disparan una acción con confirmación en vez de editar o abrir el detalle. */
const COLUMNAS_ACCION = new Set(['desamarrar'])

/**
 * Qué hace un clic sencillo según la columna donde cayó.
 *
 * En la tabla del Plan el clic sencillo ya estaba ocupado: abre el detalle de los siete pasos. Al
 * pedir que un solo clic baste para editar hubo que repartir el gesto por columna — edita donde se
 * puede editar, dispara la acción donde hay una, y abre el detalle en todo lo demás. Sin columna
 * identificada abre el detalle, que es lo que no toca ningún dato.
 */
export function accionDeClic(colId: string | undefined): 'editar' | 'accion' | 'detalle' {
  if (colId !== undefined && COLUMNAS_EDITABLES.has(colId)) return 'editar'
  if (colId !== undefined && COLUMNAS_ACCION.has(colId)) return 'accion'
  return 'detalle'
}

/**
 * Lo que dice la confirmación de desamarrar. Vive aquí, y no suelto en la vista, porque tiene que
 * ser verdad verificable: el servicio borra los pasos y vacía las fechas, y conserva las tres
 * columnas del responsable (ver PlanFechasService::limpiarPlanCalculado). Un mensaje tranquilizador
 * que prometiera conservar las fechas sería mentira, y uno que amenazara con perder el responsable
 * haría que nadie se atreviera a corregir un frente mal elegido.
 */
export const AVISO_DESAMARRAR =
  'Se borran las fechas calculadas de este paquete y vuelve a «Sin frente». El responsable se conserva.'

/**
 * Opciones del desplegable de frente de una fila del plan.
 *
 * Incluye el frente que la fila tiene puesto aunque ya no esté entre los disponibles: el cronograma
 * se reprograma y una actividad puede desaparecer, y sin su propia opción AG Grid no podría ni
 * mostrar el valor actual de la celda. Mismo criterio que `opcionesResponsable` con los huérfanos.
 */
export function opcionesFrente(
  frentes: FrenteDisponible[],
  fila: Pick<FilaPlan, 'frenteNombre' | 'fechaAncla'>,
): string[] {
  const opciones = frentes.map(opcionFrente)
  const actual = `${fila.frenteNombre} — ${fila.fechaAncla}`
  return fila.frenteNombre !== '' && !opciones.includes(actual) ? [...opciones, actual] : opciones
}

/** Traduce lo elegido en el desplegable de frente al uniqueId que espera el servidor. */
export function uniqueIdPorEtiquetaFrente(frentes: FrenteDisponible[], etiqueta: string): number | null {
  return frentes.find((f) => opcionFrente(f) === etiqueta)?.uniqueId ?? null
}

/**
 * Etiqueta de un nodo del cronograma para el desplegable.
 *
 * Se marcan las actividades («· actividad») y no los encabezados: la lista pasó de 31 a 273 al
 * permitir amarrar a una actividad concreta, y sin la marca no hay forma de distinguir un frente de
 * obra de una tarea suelta que se llama parecido. La fecha va siempre porque el cronograma repite
 * nombres en fechas distintas.
 */
export function opcionAncla(a: AnclaDisponible): string {
  return a.esFrente ? opcionFrente(a) : `${opcionFrente(a)} · actividad`
}

/**
 * Anclas ordenadas para el desplegable: primero los frentes, después las actividades.
 *
 * Los 31 frentes resuelven la enorme mayoría de los casos; las 242 actividades son la excepción
 * (CUBIERTA y las impermeabilizaciones). Ponerlas detrás evita que la lista larga entierre lo que
 * casi siempre se busca.
 */
export function anclasOrdenadas(anclas: AnclaDisponible[]): AnclaDisponible[] {
  return [...anclas].sort((a, b) =>
    a.esFrente === b.esFrente ? a.fechaInicio.localeCompare(b.fechaInicio) : a.esFrente ? -1 : 1,
  )
}

/**
 * Por qué el desplegable de frentes no tiene nada que ofrecer. `null` = sí tiene.
 *
 * Existe porque un desplegable vacío es mudo y tres causas distintas se ven exactamente igual: que
 * el proyecto no tenga cronograma en su semana activa, que el permiso de lectura del plan lo haya
 * rechazado, y que la petición se cayera. La pantalla las trataba a las tres como «no hay frentes»
 * porque la carga hacía `.catch(() => setAnclas([]))`: el fallo se perdía y quien miraba no tenía
 * forma de saber cuál de las tres le había tocado.
 *
 * Reportado el 2026-07-30 sobre el proyecto «Prueba» en el entorno de pruebas, donde el desplegable
 * salió vacío y hubo que ir al código para descartar que fuera un fallo del servicio.
 */
export function motivoSinAnclas(
  anclas: AnclaDisponible[],
  frentes: FrenteDisponible[],
  fallo: string | null,
  cargando: boolean,
): string | null {
  if (anclas.length > 0 || frentes.length > 0) {
    return null
  }
  if (cargando) {
    return 'Cargando los frentes del cronograma…'
  }
  if (fallo !== null) {
    // El mensaje del servidor viaja entero: un 403 y una caída de red no se arreglan igual, y quien
    // mira la pantalla es quien puede distinguirlas si se las nombramos.
    return `No se pudieron cargar los frentes: ${fallo}`
  }
  return 'Este proyecto no tiene cronograma en su semana activa, así que no hay frentes que ofrecer.'
}

/**
 * Cuántos frentes del cronograma se quedaron fuera del desplegable. `null` = ninguno.
 *
 * Un encabezado del cronograma no tiene identidad propia —`unique_id` lo da MS Project a las tareas,
 * no a los capítulos—, así que se ancla a la actividad más temprana de su subárbol. Cuando no tiene
 * ninguna debajo, no hay a qué amarrarlo y no se puede ofrecer. Ver `anclasDeEncabezados()` en
 * PlanFechasService.
 *
 * Se dice en vez de callarse por lo mismo que existe `motivoSinAnclas()`, y el caso es peor: aquel
 * explica una lista VACÍA, que ya se nota sola; esta explica una lista INCOMPLETA, que parece
 * completa. Una que ofrece 24 de 25 sin avisar hace pensar que el que falta no existe.
 */
export function avisoFrentesSinAncla(sinAncla: number): string | null {
  if (sinAncla <= 0) {
    return null
  }
  return sinAncla === 1
    ? '1 frente del cronograma no se puede ofrecer todavía: no tiene ninguna actividad debajo a la '
      + 'que amarrarse. Añádele una actividad en el cronograma y volverá a la lista.'
    : `${sinAncla} frentes del cronograma no se pueden ofrecer todavía: no tienen ninguna actividad `
      + 'debajo a la que amarrarse. Añádeles una actividad en el cronograma y volverán a la lista.'
}

/**
 * Resumen del panel de correspondencias.
 *
 * «Pendiente» es solo la rama que hoy deja a algún paquete sin fecha, no cualquier rama sin regla
 * propia: un grupo fino cuyo subcapítulo padre ya está resuelto no es trabajo por hacer. Contarlas
 * todas daba 66 pendientes cuando los paquetes huérfanos eran 4.
 */
export function resumenCorrespondencias(p: PanelCorrespondencias): string {
  const partes = [`${p.confirmadas} confirmada${p.confirmadas === 1 ? '' : 's'}`]
  if (p.sinConfirmar > 0) partes.push(`${p.sinConfirmar} sin confirmar`)
  partes.push(
    p.pendientes.length === 0
      ? 'ninguna rama pendiente'
      : `${p.pendientes.length} rama${p.pendientes.length === 1 ? '' : 's'} sin asignar`,
  )
  return partes.join(' · ')
}

/**
 * Procedencia que se manda al amarrar, incluyendo qué proponía el motor.
 *
 * `sugeridoUniqueId` es lo que permite registrar la corrección cuando la persona elige otro destino:
 * sin él, el servidor no tiene con qué comparar y el acierto del motor nunca se puede medir.
 */
export function procedenciaConSugerencia(
  sugerencia: SugerenciaFrente | undefined,
  uniqueIdElegido: number,
): Record<string, unknown> | undefined {
  const base = procedenciaDeAmarre(sugerencia, uniqueIdElegido)
  if (sugerencia === undefined) return base
  // `base` viene undefined justamente cuando la persona eligió un destino distinto del propuesto,
  // que es el caso que MÁS interesa registrar: es una corrección al motor. Se manda el sugerido sin
  // la capa, para que el amarre quede como «humano» y a la vez el par sugerido→elegido se guarde.
  return { ...(base ?? {}), sugeridoUniqueId: sugerencia.uniqueId, origenSugerido: sugerencia.origen }
}
