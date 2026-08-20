/** Forma minima que necesita el aviso: lo que devuelve `columnasQueCaben` sobre unos `ColDef`. */
export interface ColumnaAvisable {
  colId?: string
  field?: string
  headerName?: string
  hide?: boolean | null
}

export interface AvisoColumnasOcultasProps {
  columnas: readonly ColumnaAvisable[]
  testid?: string
}

/** Nombre con el que el usuario reconoce la columna; el id tecnico es el ultimo recurso. */
function nombreDe(c: ColumnaAvisable): string {
  return c.headerName ?? c.field ?? c.colId ?? ''
}

/**
 * Dice cuantas columnas escondio `columnasQueCaben` por falta de hueco, y cuales.
 *
 * El porque: esconder columnas es sano —evita el scroll lateral— pero hasta ahora era invisible.
 * Quien no supiera que «Vr. unitario» existe daba por hecho que la tabla no lo trae, y quien lo
 * supiera no tenia forma de saber que basta con colapsar la barra lateral para recuperarlo. Un
 * dato que desaparece sin avisar se lee como un dato que no existe.
 *
 * Se pinta solo cuando hay algo escondido, igual que {@link BarraFiltros}: una linea permanente
 * roba altura a la tabla, y el hueco que aparece es en si la señal de que algo cambio.
 *
 * Accesible sin depender del color ni del raton: el recuento es texto real, y los nombres viven en
 * un `<details>` que se abre con teclado. El `title` es un extra para el raton, nunca el unico
 * camino al dato.
 */
export function AvisoColumnasOcultas({ columnas, testid }: AvisoColumnasOcultasProps) {
  const nombres = columnas.filter((c) => c.hide).map(nombreDe).filter((n) => n !== '')
  if (nombres.length === 0) return null
  const recuento = nombres.length === 1
    ? '1 columna oculta por espacio'
    : `${nombres.length} columnas ocultas por espacio`
  return (
    <details className="pdc-cols-ocultas" data-testid={testid}>
      <summary title={nombres.join(', ')}>{recuento}</summary>
      <ul className="pdc-cols-ocultas-lista">
        {nombres.map((n) => <li key={n}>{n}</li>)}
      </ul>
      <p className="pdc-cols-ocultas-pista">Vuelven a aparecer al ensanchar la ventana o colapsar la barra lateral.</p>
    </details>
  )
}
