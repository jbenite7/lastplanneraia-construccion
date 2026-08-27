import { useEffect, useState } from 'react'
import type { CSSProperties } from 'react'
import './Semaforo.css'
import { getMetric } from '../lib/api'
import type { MetricResult } from '../lib/api'

// Semáforo de la hoja Intermedia (ct-app, etapa piloto, Task 8 — posición 4 del lienzo, CT-18.3).
//
// Contrato de negocio (decisión de Felipe, entrada 20 de la Bitácora del plan
// `docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md`): «semana 0 va en rojo cuando las
// restricciones duras tienen pendientes, es la más urgente; si ya tiene sus restricciones duras
// liberadas, verde». El color de cada franja lo da si sus restricciones duras están liberadas,
// no la cercanía de la franja — pero la urgencia de los pendientes SÍ depende de qué tan cerca
// está la franja de ejecutarse. Mapeo completo documentado en Semaforo.test.tsx.
//
// Vocabulario: `data-aia-severity`/`data-aia-urgency`, los mismos atributos que ya emite
// `DesignSystemComponent::semanticAttributes()` (src/View/Components/DesignSystemComponent.php)
// del lado PHP — no se inventan clases ni tokens nuevos.
//
// Cada franja se auto-alimenta con su propia llamada a getMetric(): un fallo de red en una no
// tumba a las otras 3 (ni hay un role="alert" global).

// `ct-app` no trae todavía una utilidad `.sr-only` compartida (fuera de alcance de esta ronda:
// eso es craft visual, se audita aparte). Estilo inline estándar de "visually hidden" — oculta
// visualmente sin usar `display:none`, que un lector de pantalla sí respetaría.
const estiloSoloLector: CSSProperties = {
  position: 'absolute',
  width: '1px',
  height: '1px',
  padding: 0,
  margin: '-1px',
  overflow: 'hidden',
  clip: 'rect(0, 0, 0, 0)',
  whiteSpace: 'nowrap',
  border: 0,
}

interface Franja {
  clave: string
  etiqueta: string
  urgente: boolean // franja "0"
  informativa: boolean // franja "5-6"
}

const FRANJAS: Franja[] = [
  { clave: 'pi_semaforo_semana_0', etiqueta: 'Semana 0', urgente: true, informativa: false },
  { clave: 'pi_semaforo_semana_1_2', etiqueta: 'Semana 1-2', urgente: false, informativa: false },
  { clave: 'pi_semaforo_semana_3_4', etiqueta: 'Semana 3-4', urgente: false, informativa: false },
  { clave: 'pi_semaforo_semana_5_6', etiqueta: 'Semana 5-6', urgente: false, informativa: true },
]

type EstadoSemantico = { severity: 'none' | 'low' | 'medium' | 'high'; urgency: 'none' | 'soon' | 'now' }

function estadoDeFranja(franja: Franja, metric: MetricResult | null, pendientes: number | null): EstadoSemantico {
  if (metric === null) return { severity: 'none', urgency: 'none' }
  if (metric.completeness === 'insuficiente' || pendientes === null) {
    return { severity: 'none', urgency: 'none' }
  }
  if (pendientes === 0) return { severity: 'low', urgency: 'none' }
  if (franja.urgente) return { severity: 'high', urgency: 'now' }
  if (franja.informativa) return { severity: 'none', urgency: 'none' }
  return { severity: 'medium', urgency: 'soon' }
}

// Traduce severity/urgency a un vocabulario legible por lector de pantalla — mismos 4 niveles de
// `docs/design-system/state-semantics.json` (neutral/healthy/attention/urgent). `data-aia-severity`
// y `data-aia-urgency` no anuncian nada por sí solos: este texto es lo que un lector de pantalla
// puede leer.
function textoAccesibleDeEstado(estado: EstadoSemantico): string {
  if (estado.severity === 'high' && estado.urgency === 'now') return 'Urgente: acción inmediata.'
  if (estado.severity === 'medium' && estado.urgency === 'soon') return 'Atención: revisar pronto.'
  if (estado.severity === 'low') return 'Controlado.'
  return 'Sin datos suficientes.'
}

interface EstadoFilaFranja {
  metric: MetricResult | null
  error: string | null
}

export function Semaforo() {
  const [estados, setEstados] = useState<Record<string, EstadoFilaFranja>>({})

  useEffect(() => {
    let cancelado = false

    for (const franja of FRANJAS) {
      getMetric(franja.clave)
        .then((metric) => {
          if (cancelado) return
          setEstados((actuales) => ({ ...actuales, [franja.clave]: { metric, error: null } }))
        })
        .catch((err: unknown) => {
          if (cancelado) return
          const mensaje = err instanceof Error ? err.message : 'No se pudo cargar esta franja.'
          setEstados((actuales) => ({ ...actuales, [franja.clave]: { metric: null, error: mensaje } }))
        })
    }

    return () => {
      cancelado = true
    }
  }, [])

  return (
    <section className="ct-semaforo" data-testid="semaforo" aria-label="Semáforo de restricciones duras por franja">
      {FRANJAS.map((franja) => {
        const fila = estados[franja.clave]
        const metric = fila?.metric ?? null
        const listas =
          metric && metric.value !== null
            ? Math.max(0, Math.min(metric.basis.filas_usadas, Math.round(metric.value * metric.basis.filas_usadas)))
            : null
        const pendientes = metric && listas !== null ? metric.basis.filas_usadas - listas : null
        const estado = estadoDeFranja(franja, metric, pendientes)

        return (
          <div
            key={franja.clave}
            className="ct-semaforo-franja"
            data-testid={`franja-${franja.clave}`}
            data-aia-severity={estado.severity}
            data-aia-urgency={estado.urgency}
          >
            <p className="ct-semaforo-etiqueta">{franja.etiqueta}</p>
            <span className="ct-semaforo-chip" aria-hidden="true">
              {textoAccesibleDeEstado(estado)}
            </span>
            <span style={estiloSoloLector}>{textoAccesibleDeEstado(estado)}</span>
            {fila?.error ? (
              <p className="ct-semaforo-error" role="alert">{fila.error}</p>
            ) : metric === null ? (
              <p className="ct-semaforo-cargando">Cargando…</p>
            ) : listas === null ? (
              <p className="ct-semaforo-cargando">Sin datos suficientes.</p>
            ) : (
              <p className="ct-semaforo-detalle">
                <span className="ct-semaforo-listas">{listas} listas</span>, {pendientes} pendientes
              </p>
            )}
          </div>
        )
      })}
    </section>
  )
}
