import type { PaqueteCatalogo, Procedencia, SugerenciaPaquete } from './types'

/** Lo que se puede deshacer: el insumo que se acaba de mover y a dónde fue. */
export type UltimaAccion = {
  descripcionNorm: string
  unidad: string
  /** Nombre del paquete, o null si se omitió. */
  destino: string | null
}

/** Estado del asistente paso a paso (wizard) de empaquetamiento. El puntero recorre los insumos
 *  sin asignar en orden Pareto; asignar/omitir recargan la lista (el índice se conserva y apunta
 *  al siguiente sin asignar), mientras que SALTAR avanza sin actuar.
 *
 *  `destinos` recuerda lo que se eligió a mano para cada insumo mientras dure la sesión: saltar un
 *  caso dudoso y volver a él no debe costar el trabajo ya hecho. */
export type WizardState = {
  indice: number
  ocupado: boolean
  mensaje: string | null
  destinos: Record<string, number>
  ultima: UltimaAccion | null
}

export type WizardAction =
  | { type: 'SALTAR' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string; ultima?: UltimaAccion }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'ELEGIR'; clave: string; paqueteId: number | null }
  | { type: 'RESET' }

export const estadoInicialWizard: WizardState = {
  indice: 0,
  ocupado: false,
  mensaje: null,
  destinos: {},
  ultima: null,
}

export function wizardReducer(state: WizardState, action: WizardAction): WizardState {
  switch (action.type) {
    case 'SALTAR':
      return { ...state, indice: state.indice + 1, mensaje: null }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      // Sin `ultima` no hay nada que deshacer: recargar la lista limpia el ofrecimiento anterior
      // para que el botón no proponga deshacer algo que ya no es lo último que pasó.
      return { ...state, ocupado: false, mensaje: action.mensaje ?? null, ultima: action.ultima ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
    case 'ELEGIR': {
      const destinos = { ...state.destinos }
      if (action.paqueteId === null) {
        delete destinos[action.clave]
      } else {
        destinos[action.clave] = action.paqueteId
      }
      return { ...state, destinos }
    }
    case 'RESET':
      return { ...estadoInicialWizard }
  }
}

/**
 * Qué se le cuenta al motor por esta asignación.
 *
 * Aceptar su propuesta tal cual conserva la capa que la produjo: es un acierto, y a la vez queda
 * confirmada porque un humano la miró. Descartarla —eligiendo otro destino u omitiendo el insumo—
 * sigue siendo una decisión humana, pero deja el par sugerido→elegido, que es la señal de dónde
 * falla. Sin propuesta previa no hay nada que atribuirle a nadie.
 *
 * `paqueteElegido` en null significa que se omitió.
 */
export function procedenciaDeAsignacion(
  sugerencia: SugerenciaPaquete | null,
  paqueteElegido: number | null,
): Procedencia | undefined {
  if (sugerencia === null) {
    return undefined
  }
  if (paqueteElegido === sugerencia.paqueteId) {
    return {
      origen: sugerencia.capa,
      confianza: sugerencia.confianza,
      evidencia: sugerencia.evidencia,
      confirmado: true,
    }
  }
  return { sugeridoPaqueteId: sugerencia.paqueteId, sugeridaCapa: sugerencia.capa }
}

/**
 * Ordena el catálogo para el desplegable de destino: primero los paquetes que el proyecto ya usa,
 * por cuantía descendente, y debajo el resto en el orden alfabético que trae el servidor.
 *
 * Cuando el motor se equivoca, el destino correcto casi siempre es un paquete que la obra ya está
 * usando: son ~100 de 216, y buscarlos entre todos es el cuello de botella al corregir.
 */
export function ordenarDestinos(
  paquetes: PaqueteCatalogo[],
  usados: Map<number, number>,
): PaqueteCatalogo[] {
  const enUso = paquetes.filter((p) => usados.has(p.id))
  const resto = paquetes.filter((p) => !usados.has(p.id))
  enUso.sort((a, b) => (usados.get(b.id) ?? 0) - (usados.get(a.id) ?? 0))
  return [...enUso, ...resto]
}
