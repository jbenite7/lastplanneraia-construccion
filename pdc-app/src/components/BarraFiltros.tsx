import type { Chip } from '../lib/barraFiltros'

export interface BarraFiltrosProps {
  chips: Chip[]
  onQuitar: (id: string) => void
  onLimpiar: () => void
  testid?: string
}

/**
 * Qué está filtrado ahora mismo, y cómo quitarlo.
 *
 * Cuando no hay nada filtrado no se pinta: una barra vacía permanente es cromo que roba altura a
 * la tabla, y el hueco que deja al aparecer es precisamente la señal de que algo cambió.
 */
export function BarraFiltros({ chips, onQuitar, onLimpiar, testid }: BarraFiltrosProps) {
  if (chips.length === 0) return null
  return (
    <div className="pdc-barra-filtros" data-testid={testid} role="status">
      <span className="pdc-barra-filtros-titulo">Filtrado por:</span>
      {chips.map((c) => (
        <button
          key={c.id}
          type="button"
          className="pdc-chip-filtro"
          aria-label={`Quitar filtro ${c.texto}`}
          onClick={() => onQuitar(c.id)}
        >
          {c.texto}
          <span className="pdc-chip-quitar" aria-hidden="true">×</span>
        </button>
      ))}
      <button type="button" className="pdc-barra-filtros-limpiar" onClick={onLimpiar}>
        Limpiar todo
      </button>
    </div>
  )
}
