import { useCallback, useId, useMemo } from 'react'
import { useGridFilter } from 'ag-grid-react'
import type { CustomFilterProps } from 'ag-grid-react'
import { ListaBuscable } from './ListaBuscable'
import { pasaFiltroLista, valoresDistintos } from '../lib/filtroLista'
import type { ModeloFiltroLista } from '../lib/filtroLista'

/**
 * El embudo de la cabecera: lista de los valores que hay en esa columna, con casillas y su propia
 * lupa. Es el equivalente del `changeType` de Programa General, escrito a mano porque el *set
 * filter* de AG Grid es Enterprise.
 *
 * Toda la sustancia —qué deja pasar, cómo se ordenan los valores— está en `lib/filtroLista.ts`.
 */
export function FiltroLista({ model, onModelChange, getValue, api }: CustomFilterProps<unknown, unknown, ModeloFiltroLista>) {
  const idBase = useId()

  // Los valores que ofrece la lista salen de las filas cargadas, no de un catálogo: si una columna
  // solo trae tres agrupaciones, ofrecer las cuarenta del proyecto sería mentir sobre la tabla.
  const opciones = useMemo(() => {
    const valores: unknown[] = []
    api.forEachNode((nodo) => {
      valores.push(getValue(nodo))
    })
    return valoresDistintos(valores)
  }, [api, getValue])

  const doesFilterPass = useCallback(
    ({ node }: { node: Parameters<typeof getValue>[0] }) => pasaFiltroLista(model, getValue(node)),
    [model, getValue],
  )

  useGridFilter({ doesFilterPass })

  return (
    <div className="pdc-filtro-lista">
      <ListaBuscable
        opciones={opciones}
        modo="varias"
        seleccion={model?.valores ?? []}
        onSeleccion={(s) => onModelChange(s.length === 0 ? null : { valores: s })}
        idBase={idBase}
      />
    </div>
  )
}
