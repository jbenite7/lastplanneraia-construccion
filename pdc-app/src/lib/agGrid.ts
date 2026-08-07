import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  CustomFilterModule,
  DateFilterModule,
  LocaleModule,
  NumberFilterModule,
  QuickFilterModule,
  RowApiModule,
  RowAutoHeightModule,
  TextFilterModule,
  themeQuartz,
} from 'ag-grid-community'
import type { ColDef, ColDefField, SizeColumnsToFitGridStrategy } from 'ag-grid-community'
import { useEffect, useState } from 'react'
import { FiltroLista } from '../components/FiltroLista'

/**
 * Módulos que necesita cualquier tabla del módulo. El registro sigue siendo selectivo (nada de
 * `AllCommunityModule`, que arrastra ~1,3 MB): cada página añade encima los suyos.
 *
 * - `ColumnAutoSizeModule` es lo que hace existir a `autoSizeStrategy`; sin él el ancho por
 *   contenido se ignora en silencio.
 * - `RowAutoHeightModule` es lo que hace existir a `autoHeight`; sin él el texto envuelto se
 *   recorta contra una fila de altura fija, que es peor que el «…» que veníamos a quitar.
 * - Los cinco módulos de filtro son lo que hace existir el embudo de la cabecera y el buscador
 *   rápido. `LocaleModule` no es cosmético: sin él el menú dice «Contains», «Apply» y «Reset» en
 *   una aplicación que está entera en español.
 * - `RowApiModule` es lo que hace existir a `api.forEachNode`, que usa `FiltroLista` para juntar
 *   los valores distintos de la columna: sin él el embudo abre vacío («Nada coincide con «»») y
 *   AG Grid tira el error #200 en consola en vez de listar nada.
 */
export const MODULOS_TABLA = [
  ClientSideRowModelModule, ColumnAutoSizeModule, RowAutoHeightModule,
  TextFilterModule, NumberFilterModule, DateFilterModule, CustomFilterModule,
  QuickFilterModule, LocaleModule, RowApiModule,
]

/**
 * Textos del menú de filtro en español. AG Grid Community no publica un paquete de idiomas en esta
 * versión (`ag-grid-community/locale` no está en sus `exports`), así que se declara aquí lo que se
 * ve. Lo que no esté en este mapa sale en inglés: si aparece una cadena nueva en pantalla, se añade.
 */
export const localeTextEs: Record<string, string> = {
  applyFilter: 'Aplicar', clearFilter: 'Limpiar', resetFilter: 'Restablecer',
  cancelFilter: 'Cancelar', textFilter: 'Filtro de texto', numberFilter: 'Filtro de número',
  dateFilter: 'Filtro de fecha', filterOoo: 'Filtrar…', empty: 'Elige una',
  equals: 'Igual a', notEqual: 'Distinto de', lessThan: 'Menor que', greaterThan: 'Mayor que',
  lessThanOrEqual: 'Menor o igual que', greaterThanOrEqual: 'Mayor o igual que',
  inRange: 'Entre', inRangeStart: 'Desde', inRangeEnd: 'Hasta',
  contains: 'Contiene', notContains: 'No contiene',
  startsWith: 'Empieza por', endsWith: 'Termina en',
  blank: 'Vacío', notBlank: 'No vacío', before: 'Antes de', after: 'Después de',
  andCondition: 'Y', orCondition: 'O', dateFormatOoo: 'aaaa-mm-dd',

  // Claves ARIA: no se ven en pantalla, las lee un lector de pantalla. Sin traducir, una interfaz
  // en español anunciaba «Filter Input» o «Open Filter Menu» en inglés — el piso de accesibilidad
  // del design system de este repo es contractual, no cosmético (hallazgo de verificación de la
  // Task 10, 2026-08-06). Los nombres de clave y el texto en inglés que traducen salen tal cual del
  // paquete instalado (`node_modules/ag-grid-community/dist/package/main.esm.mjs`, los `translate(
  // "ariaX", "default en inglés")` de cada componente): no se inventa ninguna clave que la librería
  // no reconozca, porque una clave inexistente no traduce nada y solo ensucia el mapa.
  ariaFilterInput: 'Campo del filtro', ariaDateFilterInput: 'Campo del filtro de fecha',
  ariaFilterMenuOpen: 'Abrir menú de filtro', ariaFilterColumn: 'Presiona CTRL ENTER para filtrar',
  ariaFilterValue: 'Valor del filtro', ariaFilterFromValue: 'Filtrar desde el valor',
  ariaFilterToValue: 'Filtrar hasta el valor', ariaFilteringOperator: 'Operador de filtrado',
  ariaColumnFiltered: 'Columna filtrada',
  ariaSortableColumn: 'Presiona ENTER para ordenar', ariaMenuColumn: 'Presiona ALT ABAJO para abrir el menú de columna',
  ariaLabelColumnMenu: 'Menú de columna', ariaLabelColumnFilter: 'Filtro de columna',
}

/**
 * Tema único del módulo. Estaba copiado byte a byte en los seis archivos de página, así que
 * cualquier retoque del aspecto había que hacerlo seis veces o quedaba a medias.
 */
export const pdcTheme = themeQuartz.withParams({
  /*
   * Los colores salen de los tokens del sistema, no de hex propios: AG Grid emite estos parámetros
   * como custom properties, así que un `var()` se resuelve igual que en cualquier hoja y la tabla
   * cambia de tema con el resto de la aplicación. El segundo valor es el respaldo de `npm run dev`,
   * donde aia-design-system.css no está cargado.
   */
  /* Superficie de tabla del contrato --ds-table-*: la familia (zebra, hover,
     empty) se mezcla desde --ds-active-surface; el lienzo dejaba a esta grilla
     más oscura que las Handsontable del resto de la app. */
  backgroundColor: 'var(--ds-active-surface, #1c241f)',
  foregroundColor: 'var(--ds-active-text-primary, #f7faf8)',
  accentColor: 'var(--ds-active-action-primary, #6c9077)',
  headerBackgroundColor: 'var(--ds-table-header-bg, var(--ds-state-tint-green, #173d26))',
  borderColor: 'var(--ds-table-border, #ddefe638)',
  /*
   * Densidad de hoja de cálculo (decisión del dueño del producto, 2026-07-29). La fila venía en
   * 42 px y el encabezado en 48 con celdas de 14 px: 17 filas del presupuesto en toda la pantalla,
   * cuando Excel —con la misma letra de 11 pt ≈ 14,7 px— pone la fila en 20. La letra no era el
   * problema; el aire alrededor sí. Con 28/32 se ven ~26 filas sin encoger el texto por debajo del
   * mínimo legible. Las filas que envuelven (`autoHeight`) siguen creciendo lo que necesiten.
   */
  fontSize: 13,
  rowHeight: 28,
  headerHeight: 32,
  headerFontSize: 12,
  cellHorizontalPadding: 10,
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
 * Ancho mínimo de una columna de cifra. Medido sobre el importe más ancho que aparece hoy en obra
 * —«$ 70.912.032.642», el costo directo de Da Porto: catorce caracteres— con el tipo de la tabla y
 * el padding de la celda. Por debajo de esto, el reparto de `fitGridWidth` vuelve a recortar el
 * dinero, que es el dato por el que existen estas tablas.
 */
export const MIN_WIDTH_CIFRA = 168

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
 * Columnas de cifra: nunca envuelven. Un importe partido en dos renglones se lee peor y descuadra
 * la altura de la fila. Si no cabe, la columna se ensancha — para eso está el autoSizeStrategy.
 *
 * Se exporta como objeto además de las funciones porque varias columnas no salen de un `field`
 * directo (llevan `valueGetter`) y necesitan las mismas propiedades sin repetirlas a mano.
 */
export const CIFRA = {
  type: 'rightAligned', wrapText: false, minWidth: MIN_WIDTH_CIFRA, filter: 'agNumberColumnFilter',
} satisfies ColDef

/**
 * Columnas cortas por naturaleza —unidad, tipo de insumo de una letra, conteos— con techo: en el
 * reparto de `fitGridWidth` una columna sin límite se lleva ancho que necesita el texto de al lado.
 * «Und» con 200 px de ancho para escribir «M2» era ancho robado a «Tipo».
 *
 * El mínimo subió de 70 a 80: con `FiltroLista` la cabecera suma el icono de embudo, y por debajo
 * de 80 «Und» en Paquetes desbordaba 1 px a 1440 con la barra lateral abierta (visto en
 * `pdc-v2-sin-scroll-x.spec.mjs`, condición «1440 lateral abierta»).
 */
export const COLUMNA_CORTA = { minWidth: 80, maxWidth: 104, filter: FiltroLista } satisfies ColDef

/**
 * Fecha ISO (`2026-05-25`). Diez caracteres que no admiten recorte: «2026-…» no dice nada, y en el
 * plan de compras la fecha ES la decisión.
 */
export const COLUMNA_FECHA = { minWidth: 124, maxWidth: 148, filter: 'agDateColumnFilter' } satisfies ColDef

/**
 * Categoría o agrupación: texto medio (más que una unidad, menos que una descripción). Envuelve en
 * vez de recortarse, porque «MAT-ELECTRICOS Y AFI…» y «MAT-ELECTRICOS Y AFINES» se distinguen mal
 * de otra categoría que empiece igual.
 */
export const COLUMNA_CATEGORIA = {
  flex: 1,
  minWidth: MIN_WIDTH_PALABRA_LARGA,
  wrapText: true,
  autoHeight: true,
  filter: FiltroLista,
} satisfies ColDef

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
  filter: 'agTextColumnFilter',
} satisfies ColDef

/** Columna de dinero a partir de un campo. */
export function columnaMoneda<TData>(field: ColDefField<TData>, headerName: string): ColDef<TData> {
  return { ...CIFRA, field, headerName, valueFormatter: (p) => moneda(p.value as number | null | undefined) }
}

/**
 * Columnas numéricas que no son dinero (cantidades, días, conteos). Alinean a la derecha y no
 * envuelven, pero **no reformatean el valor**: las cantidades del APU llevan decimales pequeños y
 * redondearlas aquí cambiaría lo que el usuario lee del presupuesto.
 *
 * El mínimo es la mitad que el de dinero: «1542» o «107 días» no necesitan el ancho de
 * «$ 70.912.032.642», y reservárselo se lo quitaba a las columnas que sí lo necesitan.
 */
export function columnaNumero<TData>(field: ColDefField<TData>, headerName: string): ColDef<TData> {
  return { ...CIFRA, field, headerName, minWidth: 92 }
}

/** Columna de texto largo a partir de un campo. */
export function columnaTexto<TData>(
  field: ColDefField<TData>,
  headerName: string,
  minWidth = 200,
): ColDef<TData> {
  return { ...TEXTO_LARGO, field, headerName, minWidth }
}

/** Lo que AG Grid da a una columna que no pide nada (`defaultMinWidth` de la estrategia de ancho). */
export const ANCHO_COLUMNA_POR_DEFECTO = 90
/**
 * Lo que hay que reservar además de las columnas: la barra de scroll vertical (16 px), que aparece
 * en cuanto hay más filas que hueco y no la descuenta nadie, más 4 px de colchón para el redondeo
 * del reparto entre columnas con `flex` — medido: el Plan se pasaba por exactamente 4 px.
 */
export const ANCHO_BARRA_SCROLL = 20
/** El filete que AG Grid dibuja entre columnas. Uno por columna: seis columnas son seis píxeles, y
 *  seis píxeles bastan para que aparezca la barra horizontal que veníamos a quitar. */
export const ANCHO_BORDE_COLUMNA = 1

/**
 * Esconde columnas hasta que la tabla quepa sin scroll horizontal.
 *
 * La versión anterior decidía por el ancho de la **ventana** (`max-width: 1199px`), y ese número no
 * es el ancho de la tabla: la barra lateral del shell expandida se lleva 208 px, así que a 1180 la
 * grilla trabaja con 820 y nadie se enteraba — hasta 142 px de scroll lateral en Presupuesto con la
 * lateral abierta. Aquí se mide el hueco real y se esconden las prescindibles **en el orden que
 * declara cada página**, una a una, solo mientras haga falta: con la lateral cerrada no se esconde
 * nada, y al colapsarla vuelven a aparecer.
 *
 * `prescindibles` va de más a menos sacrificable. Lo que se viene a mirar a esa pantalla no entra en
 * la lista: en Paquetes, «Destino» y «Sugerencia» no se esconden nunca.
 *
 * `anchoNoDeclarado` es para las columnas que AG Grid añade por su cuenta y no están en
 * `columnDefs` — hoy solo la de casillas de selección múltiple del Plan, 44 px que no aparecían en
 * la cuenta y dejaban 4 px de scroll lateral justo por ese hueco.
 *
 * El cálculo solo es tan bueno como los mínimos declarados: **una columna sin `minWidth` se estima
 * en {@link ANCHO_COLUMNA_POR_DEFECTO}** aunque en pantalla ocupe el doble. Si una tabla nueva
 * desborda, empieza por ahí.
 */
export function columnasQueCaben<T extends { colId?: string; minWidth?: number | null; width?: number | null; hide?: boolean | null }>(
  columnas: T[],
  anchoDisponible: number,
  prescindibles: string[],
  anchoNoDeclarado = 0,
): T[] {
  // 0 = todavía no se ha medido el contenedor (primer render): no esconder nada a ciegas.
  if (anchoDisponible <= 0) return columnas.map((c) => ({ ...c, hide: false }))
  const pide = (c: T) => c.minWidth ?? c.width ?? ANCHO_COLUMNA_POR_DEFECTO
  const ocultas = new Set<string>()
  const suma = () => anchoNoDeclarado + columnas
    .filter((c) => !(c.colId !== undefined && ocultas.has(c.colId)))
    .reduce((a, c) => a + pide(c) + ANCHO_BORDE_COLUMNA, 0)
  for (const id of prescindibles) {
    if (suma() + ANCHO_BARRA_SCROLL <= anchoDisponible) break
    if (columnas.some((c) => c.colId === id)) ocultas.add(id)
  }
  return columnas.map((c) => ({ ...c, hide: c.colId !== undefined && ocultas.has(c.colId) }))
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

/**
 * Ancho real del contenedor de la tabla, en píxeles, medido con `ResizeObserver`.
 *
 * Sustituye a la media query de ventana: reacciona a lo que de verdad cambia el hueco —colapsar o
 * abrir la barra lateral del shell, redimensionar la ventana— y no a una suposición sobre cuánto
 * cromo hay alrededor. Devuelve 0 hasta la primera medición, y {@link columnasQueCaben} interpreta
 * ese 0 como «todavía no sé, no escondas nada».
 *
 * Devuelve un **callback ref**, no un objeto ref: varias tablas del módulo viven dentro de un
 * render condicional (`{data && <tabla/>}`), así que en el momento en que corre el efecto el
 * `ref.current` de un `useRef` todavía es `null` y el observador no llegaba a engancharse nunca —
 * el ancho se quedaba en 0 y no se escondía ninguna columna. El callback se dispara cuando el nodo
 * entra en el DOM, sea cuando sea.
 */
export function usaAnchoContenedor(): [(el: HTMLElement | null) => void, number] {
  const [nodo, setNodo] = useState<HTMLElement | null>(null)
  const [ancho, setAncho] = useState(0)
  useEffect(() => {
    if (!nodo || typeof ResizeObserver === 'undefined') return
    const ro = new ResizeObserver((entradas) => {
      setAncho(Math.round(entradas[0]?.contentRect.width ?? 0))
    })
    ro.observe(nodo)
    return () => ro.disconnect()
  }, [nodo])
  return [setNodo, ancho]
}

/**
 * El ancho se reparte dentro de la grilla, no lo pide el contenido.
 *
 * `fitCellContents` —lo que había— mide cada columna por su celda más ancha y suma sin mirar el
 * ancho disponible: en Presupuesto la suma se pasaba y «Vr. unitario» quedaba cortada por el borde
 * derecho mostrando «$ 70.91». No es un recorte con «…», que se ve: es una cifra que parece
 * completa y no lo está, que es el peor fallo posible en la columna del dinero.
 *
 * `fitGridWidth` reparte el ancho real entre las columnas, así que nada se sale. El mínimo de
 * `CIFRA` (ver `MIN_WIDTH_CIFRA`) es lo que garantiza que un importe de miles de millones siga
 * cabiendo entero después del reparto.
 */
export const autoSizeStrategy: SizeColumnsToFitGridStrategy = {
  type: 'fitGridWidth',
  defaultMinWidth: ANCHO_COLUMNA_POR_DEFECTO,
}

/**
 * Reajuste del ancho de columnas después de pintar.
 *
 * `fitGridWidth` reparte contra el ancho que la grilla tiene en el primer render, y la barra de
 * scroll vertical aparece **después**, cuando ya hay filas: se lleva 16 px que nadie descontó y la
 * última columna —siempre la del dinero— queda medio tapada por el borde. Volver a ajustar cuando
 * ya se conoce el tamaño real cierra ese desfase, y `onGridSizeChanged` lo mantiene cerrado si
 * cambia el ancho (colapsar la barra lateral, redimensionar la ventana).
 *
 * Se exporta como objeto para esparcirlo (`{...ajusteDeAncho}`) en cada `<AgGridReact>`: son siete
 * tablas y la alternativa era repetir dos handlers idénticos en cada una.
 */
export const ajusteDeAncho = {
  localeText: localeTextEs,
  onFirstDataRendered: (p: { api: { sizeColumnsToFit: () => void } }) => p.api.sizeColumnsToFit(),
  onGridSizeChanged: (p: { api: { sizeColumnsToFit: () => void } }) => p.api.sizeColumnsToFit(),
}

/**
 * Caja de búsqueda rápida de una tabla. Devuelve las props que se pasan a `<input>`; el texto lo
 * guarda la página y viaja a `<AgGridReact quickFilterText={...}>`.
 *
 * AG Grid busca sobre las columnas **visibles**: es lo acordado (2026-08-06). Buscar en columnas
 * ocultas encontraría filas donde el texto no se ve por ninguna parte.
 */
export function propsBuscador(etiqueta: string, testid: string) {
  return {
    type: 'search' as const,
    className: 'pdc-buscador-tabla',
    placeholder: 'Buscar…',
    'aria-label': etiqueta,
    'data-testid': testid,
  }
}
