import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  RowAutoHeightModule,
  themeQuartz,
} from 'ag-grid-community'
import type { ColDef, ColDefField, SizeColumnsToContentStrategy } from 'ag-grid-community'
import { useEffect, useState } from 'react'

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
 *
 * **Siempre sin decimales.** `toLocaleString` sin opciones devuelve los decimales que traiga el
 * número —0, 1 o 2—, y en una misma tabla convivían «$ 3.144.138», «$ 102.290.635,8» y
 * «$ 25.430.823.601,77»: las columnas de dinero no alineaban y comparar magnitudes de un vistazo
 * era imposible. En obra los pesos se leen sin centavos, así que el redondeo es al peso.
 */
export function moneda(v: number | null | undefined): string {
  if (v === null || v === undefined) return ''
  return `$ ${Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`
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
 * Ancho mínimo para una columna de texto que puede traer una palabra larga sin espacios.
 *
 * AG Grid envuelve con `overflow-wrap: break-word`, que solo parte una palabra cuando esa palabra
 * no cabe **entera** en un renglón. Con la columna «Agrupación» a 130 px, «SUBCONTRATACION» no
 * cabía y salía «SUBCONTRATACIO / N PERSONAL». Subir el mínimo es lo que quita la causa; prohibir
 * el corte en CSS (ver `.ag-cell-wrap-text` en styles.css) es el cinturón por si aparece una
 * palabra aún más larga.
 */
export const MIN_WIDTH_PALABRA_LARGA = 170

/**
 * Esconde las columnas secundarias cuando la pantalla es angosta.
 *
 * Decisión del dueño del producto (grilleo 2026-07-29): por debajo de 1200 px se esconden columnas
 * en vez de ofrecer scroll lateral. Cuáles son «secundarias» lo decide cada página, porque lo
 * prescindible depende de a qué se va a esa pantalla — en Paquetes, «Destino» y «Sugerencia» son
 * justo lo que se viene a mirar y no se esconden nunca.
 */
export function columnasVisibles<T extends { colId?: string }>(
  columnas: T[],
  angosta: boolean,
  secundarias: string[],
): (T & { hide?: boolean })[] {
  if (!angosta) return columnas.map((c) => ({ ...c, hide: false }))
  return columnas.map((c) => ({ ...c, hide: c.colId !== undefined && secundarias.includes(c.colId) }))
}

/**
 * Mensaje de tabla vacía, en español y con el estilo del módulo.
 *
 * Sin esto AG Grid pinta «No Rows To Show» —su texto de fábrica, en inglés— y lo hacía justo donde
 * el vacío era una buena noticia: el Maestro con 0 pendientes enseñaba una pared en blanco en vez
 * de decir que ya estaba todo vinculado. Un vacío es el mejor momento para explicar, así que cada
 * tabla trae el suyo en vez de compartir uno genérico.
 */
export function vacioTabla(mensaje: string): string {
  const escapado = mensaje
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
  return `<span class="pdc-tabla-vacia">${escapado}</span>`
}

/** Umbral de «pantalla angosta»: por debajo de esto, un portátil de 1024 px ya perdía columnas. */
export const ANCHO_ANGOSTO = 1200

/**
 * `true` mientras la ventana esté por debajo de {@link ANCHO_ANGOSTO}, y reacciona al redimensionar
 * — no solo al montar: alguien que arrastra la ventana o gira la tableta debe ver las columnas
 * aparecer y desaparecer, no quedarse con la decisión que se tomó al abrir la página.
 */
export function usaPantallaAngosta(): boolean {
  const consulta = `(max-width: ${ANCHO_ANGOSTO - 1}px)`
  const [angosta, setAngosta] = useState(
    () => typeof window !== 'undefined' && window.matchMedia(consulta).matches,
  )
  useEffect(() => {
    const mq = window.matchMedia(consulta)
    const alCambiar = (e: MediaQueryListEvent) => setAngosta(e.matches)
    setAngosta(mq.matches)
    mq.addEventListener('change', alCambiar)
    return () => mq.removeEventListener('change', alCambiar)
  }, [consulta])
  return angosta
}

/**
 * El ancho sale del contenido, no de un número escrito a mano: es lo que evita el «102 DAPORTO
 * RIONEGRO PI_Version…» y el «$ 29.492.804.3…» que la revisión encontró recortados.
 */
export const autoSizeStrategy: SizeColumnsToContentStrategy = { type: 'fitCellContents' }
