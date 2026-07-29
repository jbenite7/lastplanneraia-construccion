import type { VinculoInsumo } from './types'

export type MaestroState = {
  seleccion: Set<number>
  vinculando: VinculoInsumo | null
  ocupado: boolean
  mensaje: string | null
}

export type MaestroAction =
  | { type: 'TOGGLE_SEL'; id: number }
  | { type: 'SEL_TODOS'; ids: number[] }
  | { type: 'LIMPIAR_SEL' }
  | { type: 'ABRIR_VINCULAR'; vinculo: VinculoInsumo }
  | { type: 'CERRAR_VINCULAR' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialMaestro: MaestroState = { seleccion: new Set(), vinculando: null, ocupado: false, mensaje: null }

export function maestroReducer(state: MaestroState, action: MaestroAction): MaestroState {
  switch (action.type) {
    case 'TOGGLE_SEL': {
      const seleccion = new Set(state.seleccion)
      if (seleccion.has(action.id)) seleccion.delete(action.id)
      else seleccion.add(action.id)
      return { ...state, seleccion }
    }
    case 'SEL_TODOS':
      return { ...state, seleccion: new Set(action.ids) }
    case 'LIMPIAR_SEL':
      return { ...state, seleccion: new Set() }
    case 'ABRIR_VINCULAR':
      return { ...state, vinculando: action.vinculo, mensaje: null }
    case 'CERRAR_VINCULAR':
      return { ...state, vinculando: null }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      return { seleccion: new Set(), vinculando: null, ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
  }
}

/**
 * Por qué pestaña abre el Maestro.
 *
 * Abría siempre por «Pendientes por vincular», y con 0 pendientes eso era una tabla vacía justo
 * cuando la noticia es buena (100 % vinculado). Es el mismo criterio que Paquetes —abrir por el
 * trabajo que falta— aplicado al caso en que no falta nada: entonces lo único que se puede hacer
 * ahí es mirar el catálogo.
 */
export function pestanaInicialMaestro(pendientes: number): 'pendientes' | 'catalogo' {
  return pendientes > 0 ? 'pendientes' : 'catalogo'
}
