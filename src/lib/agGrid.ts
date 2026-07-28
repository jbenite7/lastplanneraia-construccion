import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  RowAutoHeightModule,
  themeQuartz,
} from 'ag-grid-community'
import type { ColDef, ColDefField, SizeColumnsToContentStrategy } from 'ag-grid-community'

/**
 * Módulos que necesita cualquier tabla del módulo. El registro sigue siendo selectivo (nada de
 * `AllCommunityModule`, que arrastra ~1,3 MB): cada página añade encima los suyos.
 *
 * - `ColumnAutoSizeModule` es lo que hace existir a `autoSizeStrategy`; sin él el ancho por
 *   contenido se ignora en silencio.
 * - `RowAutoHeightModule` es lo que hace existir a `autoHeight`; sin él el texto envuelto se
 *   recorta contra una fila de altura fija, que es peor que el «…» que veníamos a quitar.
 */
export const MODULOS_TABLA = [ClientSideRowModelModule, ColumnAutoSizeModule, RowAutoHeightModule]

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

// `satisfies` y no `: ColDef`: anotarlo como ColDef lo vuelve ColDef<any>, y esparcirlo dentro de
// un ColDef<FilaVisor> hace que TypeScript rechace el `field` genérico. Así se comprueba igual y
// además se puede reutilizar en cualquier tabla, sea cual sea su tipo de fila.
export const defaultColDef = { resizable: true, sortable: true } satisfies ColDef

/**
 * Columnas de cifra: nunca envuelven. Un importe partido en dos renglones se lee peor y descuadra
 * la altura de la fila. Si no cabe, la columna se ensancha — para eso está el autoSizeStrategy.
 *
 * Se exporta como objeto además de las funciones porque varias columnas no salen de un `field`
 * directo (llevan `valueGetter`) y necesitan las mismas propiedades sin repetirlas a mano.
 */
export const CIFRA = { type: 'rightAligned', wrapText: false } satisfies ColDef

/**
 * Texto largo: envuelve y la fila crece en vez de recortar con «…».
 *
 * `suppressAutoSize` la deja fuera de la medición por contenido a propósito: una descripción de
 * doscientos caracteres pediría una columna de doscientos caracteres y echaría de la pantalla a
 * todas las demás. El texto largo se resuelve envolviendo, no ensanchando.
 */
export const TEXTO_LARGO = {
  wrapText: true,
  autoHeight: true,
  flex: 1,
  minWidth: 200,
  suppressAutoSize: true,
} satisfies ColDef

/** Columna de dinero a partir de un campo. */
export function columnaMoneda<TData>(field: ColDefField<TData>, headerName: string): ColDef<TData> {
  return { ...CIFRA, field, headerName, valueFormatter: (p) => moneda(p.value as number | null | undefined) }
}

/**
 * Columnas numéricas que no son dinero (cantidades, días, conteos). Alinean a la derecha y no
 * envuelven, pero **no reformatean el valor**: las cantidades del APU llevan decimales pequeños y
 * redondearlas aquí cambiaría lo que el usuario lee del presupuesto.
 */
export function columnaNumero<TData>(field: ColDefField<TData>, headerName: string): ColDef<TData> {
  return { ...CIFRA, field, headerName }
}

/** Columna de texto largo a partir de un campo. */
export function columnaTexto<TData>(
  field: ColDefField<TData>,
  headerName: string,
  minWidth = 200,
): ColDef<TData> {
  return { ...TEXTO_LARGO, field, headerName, minWidth }
}

/**
 * El ancho sale del contenido, no de un número escrito a mano: es lo que evita el «102 DAPORTO
 * RIONEGRO PI_Version…» y el «$ 29.492.804.3…» que la revisión encontró recortados.
 */
export const autoSizeStrategy: SizeColumnsToContentStrategy = { type: 'fitCellContents' }
