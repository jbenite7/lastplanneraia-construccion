/** Estado del asistente paso a paso (wizard) de empaquetamiento. El puntero recorre los insumos
 *  sin asignar en orden Pareto; asignar/omitir recargan la lista (el índice se conserva y apunta
 *  al siguiente sin asignar), mientras que SALTAR avanza sin actuar. */
export type WizardState = {
  indice: number
  ocupado: boolean
  mensaje: string | null
}

export type WizardAction =
  | { type: 'SALTAR' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'RESET' }

export const estadoInicialWizard: WizardState = { indice: 0, ocupado: false, mensaje: null }

export function wizardReducer(state: WizardState, action: WizardAction): WizardState {
  switch (action.type) {
    case 'SALTAR':
      return { ...state, indice: state.indice + 1, mensaje: null }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      return { ...state, ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
    case 'RESET':
      return { ...estadoInicialWizard }
  }
}
