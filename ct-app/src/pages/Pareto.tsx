import { useEffect, useState } from 'react'
import './Pareto.css'
import { getParetoRestricciones } from '../lib/api'
import type { ParetoRestriccionesResult } from '../lib/api'

// Pareto de restricciones (ct-app, etapa piloto, Task 8 — posición 5 del lienzo de Intermedia,
// CT-18.3): distribución por tipo de las restricciones duras no liberadas.
//
// Contrato de datos: `getParetoRestricciones()` ya llega ordenada DESC por `conteo` — este
// componente NO reordena, pinta el array tal como llega. `tipo` es el valor crudo del backend
// ('D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora') — no existe diccionario de
// traducción, así que se muestra crudo (límite conocido, no se inventa vocabulario aquí).

export function Pareto() {
  const [resultado, setResultado] = useState<ParetoRestriccionesResult | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let cancelado = false

    getParetoRestricciones()
      .then((data) => {
        if (cancelado) return
        setResultado(data)
      })
      .catch((err: unknown) => {
        if (cancelado) return
        setError(err instanceof Error ? err.message : 'No se pudo cargar el pareto de restricciones.')
      })

    return () => {
      cancelado = true
    }
  }, [])

  if (error) {
    return (
      <p className="ct-pareto-error" role="alert">
        {error}
      </p>
    )
  }

  const conteoMaximo = resultado?.distribucion.reduce((max, fila) => Math.max(max, fila.conteo), 0) ?? 0

  return (
    <section className="ct-pareto" data-testid="pareto" aria-label="Distribución de restricciones duras por tipo">
      {resultado === null && <p className="ct-pareto-mensaje">Cargando…</p>}

      {resultado !== null && resultado.distribucion.length === 0 && (
        <p className="ct-pareto-mensaje aia-empty">No hay restricciones duras pendientes esta semana.</p>
      )}

      {resultado !== null &&
        resultado.distribucion.map((fila) => {
          const porcentaje = conteoMaximo > 0 ? Math.round((fila.conteo / conteoMaximo) * 100) : 0
          return (
            <div key={fila.tipo} className="ct-pareto-fila" data-testid={`pareto-fila-${fila.tipo}`}>
              <p className="ct-pareto-tipo">{fila.tipo}</p>
              <div className="ct-pareto-barra-pista">
                <div className="ct-pareto-barra" style={{ inlineSize: `${porcentaje}%` }} />
              </div>
              <p className="ct-pareto-conteo">{fila.conteo}</p>
            </div>
          )
        })}
    </section>
  )
}
