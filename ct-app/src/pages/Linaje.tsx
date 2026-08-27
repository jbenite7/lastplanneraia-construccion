import { useId, useState } from 'react'
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
    <div>
      <button type="button" aria-expanded={abierto} aria-controls={panelId} onClick={handleToggle}>
        ¿De dónde sale esto?
      </button>

      {abierto && (
        <div id={panelId}>
          {estado?.tipo === 'cargando' && <p role="status">Cargando linaje…</p>}

          {estado?.tipo === 'error' && <p role="alert">{estado.mensaje}</p>}

          {estado?.tipo === 'vacio' && <p>Sin información de trazabilidad para esta métrica.</p>}

          {estado?.tipo === 'listo' && (
            <>
              <div data-testid="linaje-contrato">
                <h3>{estado.info.metricName}</h3>
                <p>{estado.info.definition}</p>
                <p>Fórmula: {estado.info.formula}</p>
                <p>
                  Fuente: {estado.info.sourceView} — Tablas: {estado.info.sourceTables}
                </p>
                <p>Grano: {estado.info.grain}</p>
                <p>Política de corte: {estado.info.cutoffPolicy}</p>
                <p>Filtros: {estado.info.filters}</p>
                <p>Versión: {estado.info.version}</p>
                <p>Última actualización: {estado.info.lastUpdated}</p>
                <p>Limitaciones conocidas: {estado.info.knownLimitations}</p>
              </div>

              {basis && (
                <div data-testid="linaje-basis">
                  <p>
                    Obras incluidas: {basis.obras_incluidas} de {basis.obras_esperadas} esperadas
                  </p>
                  <p>Corte de este cálculo: {basis.corte}</p>
                  <p>Filas usadas: {basis.filas_usadas}</p>
                </div>
              )}
            </>
          )}
        </div>
      )}
    </div>
  )
}
