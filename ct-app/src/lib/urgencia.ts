// Orden de urgencia de restricciones (ct-app, etapa piloto, Task 7 paso 2).
//
// N4 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-20.1): «"Urgencia" en la
// lista de restricciones = cuándo golpea, luego cuánto arrastra. Primero la restricción cuya
// actividad bloqueada inicia más pronto; a igual semana, la que más actividades encadena y si
// toca ruta crítica.» Literal a la decisión de la hoja: liberar hoy la que mata el compromiso de
// dentro de tres semanas.
//
// Librería pura: sin fetch, sin DOM. Recibe un resumen ya resuelto por el backend — el conteo de
// "actividades encadenadas" no tiene todavía un método de servicio que lo agregue
// (ver ControlTowerService::fetchIntermedia(), que hace SELECT * sin GROUP BY); no es
// responsabilidad de esta función, que solo ordena lo que le entra.

/** Insumo de ordenamiento: una restricción ya resuelta por el backend (fila de bi_pi_restricciones). */
export interface RestriccionUrgencia {
  id: number
  /**
   * Semana de inicio (ventana 0–6, espejo de `Semanas_Inicio`) de la actividad bloqueada que
   * arranca más pronto. `null` = no se conoce cuándo golpea — se trata como la peor urgencia
   * posible (sube al tope), igual que las huérfanas del PDC no se hunden en el orden por falta
   * de dato.
   */
  semanaInicioActividadBloqueada: number | null
  /** Cuántas actividades cuelgan de esta restricción (desempate: más arrastre, más urgente). */
  actividadesEncadenadas: number
  /** Si la restricción toca ruta crítica (desempate final: true antes que false). */
  tocaRutaCritica: boolean
}

/**
 * Compara dos restricciones según N4: semana ascendente (null = máxima urgencia, primero),
 * desempate por actividadesEncadenadas descendente, desempate final por tocaRutaCritica
 * (true antes que false). Devuelve 0 cuando los tres criterios empatan — `Array.prototype.sort`
 * es estable (garantizado desde ES2019), así que el orden de entrada se conserva en ese caso.
 */
function compararUrgencia(a: RestriccionUrgencia, b: RestriccionUrgencia): number {
  const semanaA = a.semanaInicioActividadBloqueada
  const semanaB = b.semanaInicioActividadBloqueada

  if (semanaA !== semanaB) {
    // null = sin fecha conocida = la peor urgencia = va primero, por encima de cualquier semana
    // numérica (incluida la semana 0, que sí es un valor real).
    if (semanaA === null) return -1
    if (semanaB === null) return 1
    return semanaA - semanaB
  }

  if (a.actividadesEncadenadas !== b.actividadesEncadenadas) {
    return b.actividadesEncadenadas - a.actividadesEncadenadas
  }

  if (a.tocaRutaCritica !== b.tocaRutaCritica) {
    return a.tocaRutaCritica ? -1 : 1
  }

  return 0
}

/**
 * Ordena restricciones por urgencia (N4). No muta `restricciones`: copia antes de ordenar.
 * Acepta `readonly` porque el test de inmutabilidad pasa un array congelado (`Object.freeze`)
 * — aceptar solo `RestriccionUrgencia[]` rechazaría esa llamada en tiempo de tipos aunque el
 * comportamiento en runtime sea correcto.
 */
export function ordenarPorUrgencia(restricciones: readonly RestriccionUrgencia[]): RestriccionUrgencia[] {
  return [...restricciones].sort(compararUrgencia)
}
