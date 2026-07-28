import type { Desfase, FilaPlan, FrenteDisponible, ProcedenciaAmarre, ResumenPaquetes, SugerenciaFrente } from './types'

export type EstadoFila = { clave: 'vencido' | 'provisional' | 'en-plazo'; etiqueta: string }

/**
 * El estado que se pinta en cada fila. Lo vencido manda sobre lo provisional: un plazo aproximado
 * importa, pero una contratación que debió arrancar hace dos meses importa más.
 */
export function estadoFila(f: FilaPlan): EstadoFila {
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
