import { useEffect, useState } from 'react'
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
    return <p role="alert">{error}</p>
  }

  return (
    <section data-testid="pareto" aria-label="Distribución de restricciones duras por tipo">
      {resultado === null && <p>Cargando…</p>}

      {resultado !== null && resultado.distribucion.length === 0 && (
        <p>No hay restricciones duras pendientes esta semana.</p>
      )}

      {resultado !== null &&
        resultado.distribucion.map((fila) => (
          <div key={fila.tipo} data-testid={`pareto-fila-${fila.tipo}`}>
            <span>{fila.tipo}</span>
            <span>{fila.conteo}</span>
          </div>
        ))}
    </section>
  )
}
