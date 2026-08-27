import { useEffect, useMemo, useState } from 'react'
import { getRestricciones } from '../lib/api'
import type { Restriccion } from '../lib/api'
import type { ResumenLookaheadIntermedia } from '../lib/titulares'
import { AlarmaHuerfanas } from './AlarmaHuerfanas'
import { Titular } from './Titular'
import { ListaRestricciones } from './ListaRestricciones'

// Hoja de Intermedia (ct-app, etapa piloto, Task 7 ensamblaje, CT-8.3): ensambla alarma de
// huérfanas (posición 1) + titular narrativo (posición 2) + lista de restricciones (posición 3,
// ya construida en Task 7 paso 3b). El semáforo (4) y el Pareto (5) NO entran en este sub-paso.
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
// - listasRate: fallback fijo { value: null, completeness: 'insuficiente' } — no existe todavía un
//   endpoint que ejecute MetricExecutor para pi_hard_restrictions_ready_rate (gap real,
//   documentado en el reporte de Task 7; no se resuelve en este sub-paso).
//
// El filtro "Ver huérfanas" (ver AlarmaHuerfanas.test.tsx) es de una sola dirección en este
// sub-paso: Intermedia mantiene el estado de "solo huérfanas" y le pasa a ListaRestricciones el
// array ya filtrado; no hay botón para volver a "ver todas".
//
// Concern documentado, no cubierto por los tests a propósito: si el usuario gestiona una huérfana
// desde el panel dentro de esta misma sesión, ListaRestricciones actualiza su copia local, pero el
// conteo que muestra AlarmaHuerfanas (derivado de la copia que sostiene Intermedia) no se refresca
// hasta un remount/refetch. Sincronizar ambas copias es un refactor más profundo, fuera de alcance.

function esHuerfana(r: Restriccion): boolean {
  return r.estadoLiberacion === 'sin_gestionar' && r.responsableAsignado === null
}

function esVencida(r: Restriccion): boolean {
  return r.diasVencida !== null && r.diasVencida > 0
}

export function Intermedia() {
  const [restricciones, setRestricciones] = useState<Restriccion[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [soloHuerfanas, setSoloHuerfanas] = useState(false)

  useEffect(() => {
    getRestricciones()
      .then((data) => setRestricciones(data))
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'No se pudieron cargar las restricciones.')
      })
  }, [])

  const huerfanas = useMemo(() => (restricciones ?? []).filter(esHuerfana), [restricciones])
  const vencidas = useMemo(() => (restricciones ?? []).filter(esVencida), [restricciones])

  if (error) {
    return <p role="alert">{error}</p>
  }

  if (restricciones === null) {
    return <p>Cargando lookahead…</p>
  }

  const resumen: ResumenLookaheadIntermedia = {
    huerfanasCount: huerfanas.length,
    vencidasCount: vencidas.length,
    vencidasMaxDias: vencidas.reduce((max, r) => Math.max(max, r.diasVencida ?? 0), 0),
    // Fallback documentado arriba: sin endpoint que ejecute pi_hard_restrictions_ready_rate
    // todavía, se usa siempre esta afirmación honesta de "no hay dato", nunca un valor inventado.
    listasRate: { value: null, completeness: 'insuficiente' },
  }

  const restriccionesVisibles = soloHuerfanas ? huerfanas : restricciones

  return (
    <div>
      <AlarmaHuerfanas huerfanas={huerfanas} onVerHuerfanas={() => setSoloHuerfanas(true)} />
      <Titular resumen={resumen} />
      <ListaRestricciones restricciones={restriccionesVisibles} />
    </div>
  )
}
