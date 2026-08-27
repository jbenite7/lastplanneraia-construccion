import { useId, useState } from 'react'
import './Linaje.css'
import { CtApiError, getLineage } from '../lib/api'
import type { LineageInfo, MetricResult } from '../lib/api'

// Control «de dónde sale esto» (ct-app, etapa piloto, Task 7 paso 4 — CT-6.3). GENÉRICO Y
// REUTILIZABLE: cualquier cifra del lienzo con `metricKey` de catálogo puede envolverse con
// `<Linaje metricKey="..." />`. Contrato fijado por `Linaje.test.tsx` (rol A) — ver su cabecera
// para la forma real de `LineageService::getForMetric()` y las decisiones de diseño que este
// componente implementa.
//
// Fetch perezoso: `getLineage(metricKey)` se llama la PRIMERA vez que el control se abre, nunca
// al montar — el lienzo puede tener varias cifras con control simultáneamente, y pedir el
// contrato de las que nadie abre es red desperdiciada. El resultado se cachea en `estado`: cerrar
// y volver a abrir no repite la llamada (comparado contra `estado === null`, no contra `abierto`).
//
// Un `<button>` nativo ya es alcanzable por Tab y responde a Enter/Space sin código adicional —
// no hace falta `tabIndex` ni un manejador de teclado propio para un elemento nativamente
// enfocable. El control nunca depende de hover: solo `onClick` (que Enter/Space también disparan
// de forma nativa sobre un botón enfocado).

type EstadoLinaje =
  | { tipo: 'cargando' }
  | { tipo: 'listo'; info: LineageInfo }
  | { tipo: 'vacio' }
  | { tipo: 'error'; mensaje: string }

interface LinajeProps {
  metricKey: string
  /** El basis CONCRETO de esta cifra particular (Task 7 paso 3, `MetricResult.basis`). */
  basis?: MetricResult['basis']
}

function mensajeDeError(err: unknown): string {
  // Nunca un catch mudo: CtApiError trae el mensaje que ya redactó el servidor; cualquier otra
  // falla (red caída, etc.) muestra al menos su propio mensaje, y solo si ni eso hay, un genérico.
  if (err instanceof CtApiError) return err.message
  if (err instanceof Error) return err.message
  return 'No se pudo cargar el linaje de esta métrica.'
}

export function Linaje({ metricKey, basis }: LinajeProps) {
  const panelId = useId()
  const [abierto, setAbierto] = useState(false)
  const [estado, setEstado] = useState<EstadoLinaje | null>(null)

  function handleToggle() {
    if (abierto) {
      setAbierto(false)
      return
    }
    setAbierto(true)
    if (estado === null) {
      setEstado({ tipo: 'cargando' })
      getLineage(metricKey)
        .then((info) => setEstado(info === null ? { tipo: 'vacio' } : { tipo: 'listo', info }))
        .catch((err: unknown) => setEstado({ tipo: 'error', mensaje: mensajeDeError(err) }))
    }
  }

  return (
    <div className="ct-linaje">
      <button
        type="button"
        className="ct-linaje-boton"
        aria-expanded={abierto}
        aria-controls={panelId}
        onClick={handleToggle}
      >
        ¿De dónde sale esto?
        <span className="ct-linaje-glifo" aria-hidden="true">
          {abierto ? '▴' : '▾'}
        </span>
      </button>

      {abierto && (
        <div id={panelId} className="ct-linaje-panel">
          {estado?.tipo === 'cargando' && (
            <p className="ct-linaje-estado" role="status">
              Cargando linaje…
            </p>
          )}

          {estado?.tipo === 'error' && (
            <p className="ct-linaje-error" role="alert">
              {estado.mensaje}
            </p>
          )}

          {estado?.tipo === 'vacio' && (
            <p className="ct-linaje-estado">Sin información de trazabilidad para esta métrica.</p>
          )}

          {estado?.tipo === 'listo' && (
            <>
              <div className="ct-linaje-contrato" data-testid="linaje-contrato">
                <h3 className="ct-linaje-titulo">{estado.info.metricName}</h3>
                <p>{estado.info.definition}</p>
                <p>
                  <span className="ct-linaje-label">Fórmula:</span> {estado.info.formula}
                </p>
                <p>
                  <span className="ct-linaje-label">Fuente:</span> {estado.info.sourceView} —{' '}
                  <span className="ct-linaje-label">Tablas:</span> {estado.info.sourceTables}
                </p>
                <p>
                  <span className="ct-linaje-label">Grano:</span> {estado.info.grain}
                </p>
                <p>
                  <span className="ct-linaje-label">Política de corte:</span> {estado.info.cutoffPolicy}
                </p>
                <p>
                  <span className="ct-linaje-label">Filtros:</span> {estado.info.filters}
                </p>
                <p>
                  <span className="ct-linaje-label">Versión:</span> {estado.info.version}
                </p>
                <p>
                  <span className="ct-linaje-label">Última actualización:</span> {estado.info.lastUpdated}
                </p>
                <p>
                  <span className="ct-linaje-label">Limitaciones conocidas:</span> {estado.info.knownLimitations}
                </p>
              </div>

              {basis && (
                <div className="ct-linaje-basis" data-testid="linaje-basis">
                  <p>
                    <span className="ct-linaje-label">Obras incluidas:</span> {basis.obras_incluidas} de{' '}
                    {basis.obras_esperadas} esperadas
                  </p>
                  <p>
                    <span className="ct-linaje-label">Corte de este cálculo:</span> {basis.corte}
                  </p>
                  <p>
                    <span className="ct-linaje-label">Filas usadas:</span> {basis.filas_usadas}
                  </p>
                </div>
              )}
            </>
          )}
        </div>
      )}
    </div>
  )
}
