import type { Desfase, FilaPlan, FrenteDisponible, ProcedenciaAmarre, ResumenPaquetes, SugerenciaFrente } from './types'

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
 * Valor que debe verse en la celda «Responsable». AG Grid muta `data.responsable` in-place al
 * confirmar la edición (valueSetter por defecto), sin esperar el POST — por eso un fallo de guardado
 * no alcanza a evitar la mutación, solo puede corregirla después. `overrides` es esa corrección: si
 * hay uno pendiente (el POST falló y se fijó el valor anterior), manda sobre el dato ya mutado.
 */
export function valorResponsableMostrado(fila: Pick<FilaPlan, 'paqueteId' | 'responsable'>, overrides: Record<number, string>): string {
  return overrides[fila.paqueteId] ?? fila.responsable
}

export type PlanUiState = { ocupado: boolean; mensaje: string | null }

export type PlanUiAction =
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialPlanUi: PlanUiState = { ocupado: false, mensaje: null }

export function planUiReducer(state: PlanUiState, action: PlanUiAction): PlanUiState {
  switch (action.type) {
    case 'OCUPADO':
      return { ocupado: true, mensaje: null }
    case 'LISTO':
      return { ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ocupado: false, mensaje: action.mensaje }
  }
}
