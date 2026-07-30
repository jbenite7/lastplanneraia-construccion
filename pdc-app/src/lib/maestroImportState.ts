import { plural } from './texto'
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

/**
 * La frase que resume una carga del maestro.
 *
 * Vive aquí y no dentro del JSX para poder probarla: es texto con concordancia y con una parte
 * condicional, que es justo donde se cuelan los «1 pendientes» y los «0 resueltos».
 *
 * Los pendientes resueltos solo se nombran si los hubo. Un «0 pendientes resueltos» no informa de
 * nada que el usuario pueda usar, y alarga la única línea que va a leer.
 */
export function resumenImportacionMaestro(r: MaestroImportResultado): string {
  const entraron = `Maestro importado: ${plural(r.creados, 'creado')}, `
    + `${plural(r.actualizados, 'actualizado')}, ${plural(r.enriquecidos, 'enriquecido')}.`

  if (r.reenganchados <= 0) return entraron

  // «Se resolvieron solos» y no «reenganchados»: lo segundo es el nombre interno del vínculo que
  // vuelve de pendiente a automático, y no significa nada para quien está mirando la obra.
  const verbo = r.reenganchados === 1 ? 'se resolvió solo' : 'se resolvieron solos'
  return `${entraron} Además, ${plural(r.reenganchados, 'pendiente')} ${verbo} `
    + 'al entrar sus insumos.'
}
