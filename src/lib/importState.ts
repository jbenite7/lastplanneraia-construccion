import type { ImportErrorFila, ImportPreview } from './types'

export type ImportState = {
  fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'
  preview: ImportPreview | null
  errores: ImportErrorFila[]
  mensajeError: string | null
}

export type ImportAction =
  | { type: 'SUBIR' }
  | { type: 'PREVIEW_OK'; preview: ImportPreview }
  | { type: 'PREVIEW_ERRORES'; errores: ImportErrorFila[] }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'CONFIRMAR' }
  | { type: 'CONFIRMADO' }
  | { type: 'REINICIAR' }

export const estadoInicial: ImportState = { fase: 'idle', preview: null, errores: [], mensajeError: null }

export function importReducer(state: ImportState, action: ImportAction): ImportState {
  switch (action.type) {
    case 'SUBIR':
      return { ...estadoInicial, fase: 'subiendo' }
    case 'PREVIEW_OK':
      return { fase: 'previewOk', preview: action.preview, errores: [], mensajeError: null }
    case 'PREVIEW_ERRORES':
      return { fase: 'previewErrores', preview: null, errores: action.errores, mensajeError: null }
    case 'FALLO':
      return { ...state, fase: 'idle', mensajeError: action.mensaje }
    case 'CONFIRMAR':
      return { ...state, fase: 'confirmando', mensajeError: null }
    case 'CONFIRMADO':
      return { ...state, fase: 'confirmado', mensajeError: null }
    case 'REINICIAR':
      return estadoInicial
  }
}
