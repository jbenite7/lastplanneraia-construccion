import type { CandidatoGlobal } from './types'

/**
 * Fracción del presupuesto con la que arranca el umbral del «globalazo».
 *
 * Medida contra Da Porto ($29.492.804.354): de las 57 actividades con unidad global y APU de ≤2
 * insumos, el 0,25 % —redondeado al millón, $73.000.000— deja 18, que son todos los «todo costo»
 * reales de hidráulica y eléctrica, sin la cola de maderas y bioseguridad de menos de $30 M. Con el
 * 0,50 % quedaban 5 y se caían partidas que sí hay que mirar; con el 0,10 % entraban 34 y el listado
 * empezaba a parecer inventario en vez de hallazgo.
 *
 * Es solo el punto de partida: **el umbral lo asigna el usuario en la vista**. Un umbral cerrado en
 * el código sería un juicio disfrazado de constante, y el juicio es de quien conoce la obra.
 */
export const FRACCION_UMBRAL_POR_DEFECTO = 0.0025

const CLAVE = (projectId: number | string): string => `pdc-umbral-global:${projectId}`

/**
 * El almacenamiento del navegador, o null donde no exista.
 *
 * Se resuelve por `globalThis` en vez de tocar `localStorage` directo porque este módulo también
 * corre en las pruebas, que no arrastran un DOM: sin el guardia, importarlo reventaría.
 */
function almacen(): Storage | null {
  try {
    return (globalThis as { localStorage?: Storage }).localStorage ?? null
  } catch {
    return null
  }
}

/**
 * El umbral por defecto, redondeado al millón hacia abajo: el número que se ve en el control tiene
 * que ser legible. El redondeo hace entrar alguna partida más que la fracción exacta, y eso es
 * preferible a poner «$73.732.011» en una casilla que el usuario va a querer editar a mano.
 */
export function umbralPorDefecto(costoTotal: number): number {
  if (!Number.isFinite(costoTotal) || costoTotal <= 0) return 0
  return Math.floor((costoTotal * FRACCION_UMBRAL_POR_DEFECTO) / 1_000_000) * 1_000_000
}

/** Las partidas que el usuario pidió ver con el umbral que puso. No muta lo que recibe. */
export function partidasSobreUmbral(candidatos: CandidatoGlobal[], umbral: number): CandidatoGlobal[] {
  return candidatos.filter((c) => c.valorTotal >= umbral)
}

/**
 * El umbral que puso el usuario en este proyecto, o el por defecto.
 *
 * El cero se respeta como decisión («quiero verlo todo»), así que la ausencia se distingue por la
 * clave inexistente y no por un valor falsy. Un almacenamiento inaccesible o con basura cae al por
 * defecto en silencio: el visor tiene que abrir igual.
 */
export function leerUmbral(projectId: number | string, costoTotal: number): number {
  const store = almacen()
  if (store === null) return umbralPorDefecto(costoTotal)
  try {
    const crudo = store.getItem(CLAVE(projectId))
    if (crudo === null) return umbralPorDefecto(costoTotal)
    const n = Number(crudo)
    return crudo.trim() !== '' && Number.isFinite(n) && n >= 0 ? n : umbralPorDefecto(costoTotal)
  } catch {
    return umbralPorDefecto(costoTotal)
  }
}

export function guardarUmbral(projectId: number | string, umbral: number): void {
  const store = almacen()
  if (store === null) return
  try {
    store.setItem(CLAVE(projectId), String(umbral))
  } catch {
    // Sin cuota o en modo privado: el umbral vale para esta sesión y nada más. No es un error que
    // deba interrumpir la pantalla.
  }
}
