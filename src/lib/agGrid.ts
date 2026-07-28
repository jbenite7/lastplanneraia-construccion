import { themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'

/**
 * Tema único del módulo. Estaba copiado byte a byte en los seis archivos de página, así que
 * cualquier retoque del aspecto había que hacerlo seis veces o quedaba a medias.
 */
export const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

/**
 * Dinero. Un 0 se muestra como «$ 0» y solo la ausencia deja la celda vacía: hasta ahora el visor
 * y el comparador dejaban en blanco un valor que sí existía y valía cero, que es información
 * distinta de «no hay dato».
 */
export function moneda(v: number | null | undefined): string {
  if (v === null || v === undefined) return ''
  return `$ ${Number(v).toLocaleString('es-CO')}`
}

export const defaultColDef: ColDef = { resizable: true, sortable: true }

/**
 * Columnas de cifra: nunca envuelven. Un importe partido en dos renglones se lee peor y descuadra
 * la altura de la fila. Si no cabe, la columna se ensancha — para eso está el autoSizeStrategy.
 */
export function columnaMoneda(field: string, headerName: string): ColDef {
  return {
    field,
    headerName,
    type: 'rightAligned',
    valueFormatter: (p) => moneda(p.value as number | null | undefined),
    wrapText: false,
  }
}

/**
 * Columnas numéricas que no son dinero (cantidades, días, conteos). Alinean a la derecha y no
 * envuelven, pero **no reformatean el valor**: las cantidades del APU llevan decimales pequeños y
 * redondearlas aquí cambiaría lo que el usuario lee del presupuesto.
 */
export function columnaNumero(field: string, headerName: string): ColDef {
  return { field, headerName, type: 'rightAligned', wrapText: false }
}

/** Columnas de texto largo: envuelven y la fila crece en vez de recortar con «…». */
export function columnaTexto(field: string, headerName: string): ColDef {
  return { field, headerName, wrapText: true, autoHeight: true, flex: 1 }
}
