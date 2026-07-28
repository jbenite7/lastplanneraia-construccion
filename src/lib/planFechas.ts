import type { Desfase, FilaPlan, FrenteDisponible, ProcedenciaAmarre, ResponsableElegible, ResumenPaquetes, SugerenciaFrente } from './types'

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
 */
export function preseleccionDestinos(
  prev: Record<number, number | ''>,
  sinFrente: { paqueteId: number }[],
  sugerencias: Record<number, SugerenciaFrente>,
  sugerenciasCargadas: boolean,
): Record<number, number | ''> {
  if (!sugerenciasCargadas) return prev
  let cambio = false
  const next = { ...prev }
  for (const p of sinFrente) {
    if (next[p.paqueteId] === undefined) {
      const s = sugerencias[p.paqueteId]
      next[p.paqueteId] = s ? s.uniqueId : ''
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
