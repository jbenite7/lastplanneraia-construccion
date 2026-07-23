import type { MaestroImportErrorFila, MaestroImportPreview, MaestroImportResultado } from './types'

export type MaestroImportState = {
  fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'
  preview: MaestroImportPreview | null
  resultado: MaestroImportResultado | null
  errores: MaestroImportErrorFila[]
  mensajeError: string | null
}

export type MaestroImportAction =
  | { type: 'SUBIR' }
  | { type: 'PREVIEW_OK'; preview: MaestroImportPreview }
  | { type: 'PREVIEW_ERRORES'; errores: MaestroImportErrorFila[] }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'CONFIRMAR' }
  | { type: 'CONFIRMADO'; resultado: MaestroImportResultado }
  | { type: 'REINICIAR' }

export const estadoInicialMaestroImport: MaestroImportState = {
  fase: 'idle', preview: null, resultado: null, errores: [], mensajeError: null,
}

export function maestroImportReducer(state: MaestroImportState, action: MaestroImportAction): MaestroImportState {
  switch (action.type) {
    case 'SUBIR':
      return { ...estadoInicialMaestroImport, fase: 'subiendo' }
    case 'PREVIEW_OK':
      return { fase: 'previewOk', preview: action.preview, resultado: null, errores: [], mensajeError: null }
    case 'PREVIEW_ERRORES':
      return { fase: 'previewErrores', preview: null, resultado: null, errores: action.errores, mensajeError: null }
    case 'FALLO':
      return { ...state, fase: 'idle', mensajeError: action.mensaje }
    case 'CONFIRMAR':
      return { ...state, fase: 'confirmando', mensajeError: null }
    case 'CONFIRMADO':
      return { ...state, fase: 'confirmado', resultado: action.resultado, mensajeError: null }
    case 'REINICIAR':
      return estadoInicialMaestroImport
  }
}
