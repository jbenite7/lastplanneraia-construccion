import { useEffect, useMemo, useState } from 'react'
import './Intermedia.css'
import { getMetric, getRestricciones } from '../lib/api'
import type { Restriccion } from '../lib/api'
import type { ListasRateResumen, ResumenLookaheadIntermedia } from '../lib/titulares'
import { AlarmaHuerfanas } from './AlarmaHuerfanas'
import { Titular } from './Titular'
import { ListaRestricciones } from './ListaRestricciones'
import type { AjusteGuardado } from './ListaRestricciones'
import { ToggleTema } from './ToggleTema'
import { Semaforo } from './Semaforo'
import { Pareto } from './Pareto'

// Hoja de Intermedia (ct-app, etapa piloto, CT-8.3): ensambla las cinco posiciones del lienzo —
// alarma de huérfanas (1, Task 7 ensamblaje) + titular narrativo (2, ídem) + lista de
// restricciones (3, Task 7 paso 3b) + semáforo (4) + pareto (5). Las posiciones 4 y 5 se
// construyeron en Task 8 tras el hallazgo de la entrada 20 de la Bitácora del plan: el lienzo
// quedó cerrado como completo en Task 7 con solo tres de sus cinco piezas. Semaforo/Pareto traen
// su propio fetch independiente (no comparten el getRestricciones() de aquí abajo), así que no
// alteran el contrato de agregación que sigue documentado en este mismo bloque.
//
// Decisión central de este sub-paso: Intermedia trae los datos UNA sola vez con
// getRestricciones() y deriva de ahí tanto el resumen que necesita Titular (huerfanasCount,
// vencidasCount, vencidasMaxDias) como el array que necesita AlarmaHuerfanas (huerfanas) y el que
// necesita ListaRestricciones (todas, o solo huérfanas si el usuario usó el filtro). Para esto,
// ListaRestricciones deja de hacer SIEMPRE su propio fetch: se le agregó una prop opcional
// `restricciones?`, y cuando Intermedia se la pasa, no llama a getRestricciones() por su cuenta.
//
// Criterios de agregación (mismos que fija AlarmaHuerfanas.test.tsx y el comentario de
// ResumenLookaheadIntermedia en lib/titulares.ts):
// - huérfana: estadoLiberacion === 'sin_gestionar' && responsableAsignado === null.
// - vencida: diasVencida !== null && diasVencida > 0 (una huérfana nunca es vencida: sin fecha de
//   compromiso asignada no hay "días de atraso" que contar).
// - listasRate: Task 7 paso 5 (D59) — se trae con getMetric('pi_hard_restrictions_ready_rate') en
//   un efecto propio, independiente del de getRestricciones(). Si getMetric() rechaza (red, 404,
//   lo que sea), listasRate degrada SOLO su propio dato a { value: null, completeness:
//   'insuficiente' } — la lectura honesta de "no hay dato", nunca un valor inventado — sin tumbar
//   el resto del lienzo (huérfanas y lista siguen visibles, sin role="alert").
//
// El filtro "Ver huérfanas" (ver AlarmaHuerfanas.test.tsx) es de una sola dirección en este
// sub-paso: Intermedia mantiene el estado de "solo huérfanas" y le pasa a ListaRestricciones el
// array ya filtrado; no hay botón para volver a "ver todas".
//
// Fix ronda 1 (hallazgo Important de la revisión): si el usuario gestiona una huérfana desde el
// panel dentro de esta misma sesión, el contador de AlarmaHuerfanas y el titular quedaban
// desactualizados (N nunca bajaba) porque Intermedia.restricciones nunca se enteraba del guardado
// — y sin router en ct-app no había forma de refrescar sin recargar el navegador completo.
// `handleGestionGuardada` recibe el payload que ListaRestricciones ya confirmó con el servidor
// (vía la prop `onGestionGuardada` que se le pasa abajo) y lo aplica sobre la propia copia de
// `restricciones` — el `useMemo` de huérfanas/vencidas recalcula solo, así que el contador, el
// titular y el filtro "solo huérfanas" quedan consistentes con la fila en el mismo render.

function esHuerfana(r: Restriccion): boolean {
  return r.estadoLiberacion === 'sin_gestionar' && r.responsableAsignado === null
}

function esVencida(r: Restriccion): boolean {
  return r.diasVencida !== null && r.diasVencida > 0
}

/** Clave del catálogo que D59 muestra como cifra dura — ver docblock arriba. */
const METRIC_KEY_ADHERENCIA = 'pi_hard_restrictions_ready_rate'

/** Lectura honesta de "no hay dato" — nunca un valor inventado. Ver docblock arriba. */
const LISTAS_RATE_FALLBACK: ListasRateResumen = { value: null, completeness: 'insuficiente' }

export function Intermedia() {
  const [restricciones, setRestricciones] = useState<Restriccion[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [soloHuerfanas, setSoloHuerfanas] = useState(false)
  const [listasRate, setListasRate] = useState<ListasRateResumen>(LISTAS_RATE_FALLBACK)

  useEffect(() => {
    getRestricciones()
      .then((data) => setRestricciones(data))
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'No se pudieron cargar las restricciones.')
      })
  }, [])

  useEffect(() => {
    getMetric(METRIC_KEY_ADHERENCIA)
      .then((resultado) => setListasRate({ value: resultado.value, completeness: resultado.completeness }))
      .catch(() => setListasRate(LISTAS_RATE_FALLBACK))
  }, [])

  const huerfanas = useMemo(() => (restricciones ?? []).filter(esHuerfana), [restricciones])
  const vencidas = useMemo(() => (restricciones ?? []).filter(esVencida), [restricciones])

  function handleGestionGuardada(id: number, ajuste: AjusteGuardado) {
    setRestricciones((actuales) => (actuales ?? []).map((r) => (r.id === id ? { ...r, ...ajuste } : r)))
  }

  if (error) {
    return (
      <div className="ct-intermedia-layout">
        <p className="ct-intermedia-mensaje" role="alert">
          {error}
        </p>
      </div>
    )
  }

  if (restricciones === null) {
    return (
      <div className="ct-intermedia-layout">
        <p className="ct-intermedia-mensaje">Cargando lookahead…</p>
      </div>
    )
  }

  const resumen: ResumenLookaheadIntermedia = {
    huerfanasCount: huerfanas.length,
    vencidasCount: vencidas.length,
    vencidasMaxDias: vencidas.reduce((max, r) => Math.max(max, r.diasVencida ?? 0), 0),
    listasRate,
  }

  const restriccionesVisibles = soloHuerfanas ? huerfanas : restricciones

  return (
    <div className="ct-intermedia-layout">
      <div className="ct-intermedia-toolbar">
        <ToggleTema />
      </div>
      <AlarmaHuerfanas huerfanas={huerfanas} onVerHuerfanas={() => setSoloHuerfanas(true)} />
      <Titular resumen={resumen} />
      <ListaRestricciones restricciones={restriccionesVisibles} onGestionGuardada={handleGestionGuardada} />
      <Semaforo />
      <Pareto />
    </div>
  )
}
