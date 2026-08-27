// Titular narrativo de la hoja Intermedia (ct-app, etapa piloto, Task 7 paso 2).
//
// N1 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-20.1): «El titular
// narrativo se produce por plantillas por regla — un juego finito de frases con huecos, elegidas
// por condiciones medibles. Nunca IA generativa, nunca redacción manual. Cada plantilla es
// auditable con la misma trazabilidad que una cifra: condición que la disparó, cifras que la
// llenan.»
//
// Librería pura: sin fetch, sin DOM. Recibe un resumen YA agregado (contar huérfanas, contar
// vencidas es responsabilidad de quien arme el resumen, no de esta plantilla) para mantener esta
// función como resumen -> texto, sin lógica de negocio de conteo mezclada con redacción.

/** El juego finito de condiciones que puede disparar un titular. Ver el orden de evaluación abajo. */
export type TitularCondicion =
  | 'huerfanas'
  | 'vencidas'
  | 'adherencia_insuficiente'
  | 'adherencia_baja'
  | 'sano'
  | 'neutral'

/** Espejo de MetricResult (api.ts) para pi_hard_restrictions_ready_rate: value = fracción LISTA. */
export interface ListasRateResumen {
  value: number | null
  completeness: 'completa' | 'parcial' | 'insuficiente'
}

/** Insumo ya agregado de la hoja Intermedia — no filas crudas de pi_shared_constraints. */
export interface ResumenLookaheadIntermedia {
  /** Restricciones sin responsable ni fecha de compromiso (mismo criterio que la alarma, posición 1). */
  huerfanasCount: number
  /** Restricciones con FechaCompromiso vencida (y con responsable/fecha asignados). */
  vencidasCount: number
  /** Días de atraso de la vencida más antigua. */
  vencidasMaxDias: number
  /** pi_hard_restrictions_ready_rate ya resuelta: value = fracción lista, no la fracción sin análisis. */
  listasRate: ListasRateResumen
}

/** Resultado auditable (N1): la condición que disparó el titular y las cifras que llenaron sus huecos. */
export interface TitularResultado {
  condicion: TitularCondicion
  texto: string
  variables: Record<string, number>
}

/** Umbral de diseño de este paso (no viene de la spec): más de este % sin análisis es "adherencia_baja". */
const UMBRAL_SIN_ANALISIS_PCT = 30

function pluralRestricciones(count: number): string {
  return count === 1 ? 'restricción' : 'restricciones'
}

function titularHuerfanas(resumen: ResumenLookaheadIntermedia): TitularResultado {
  const { huerfanasCount } = resumen
  return {
    condicion: 'huerfanas',
    texto: `${huerfanasCount} ${pluralRestricciones(huerfanasCount)} sin responsable ni fecha de compromiso: es la alarma más urgente del lookahead.`,
    variables: { huerfanas: huerfanasCount },
  }
}

function titularVencidas(resumen: ResumenLookaheadIntermedia): TitularResultado {
  const { vencidasCount, vencidasMaxDias } = resumen
  return {
    condicion: 'vencidas',
    texto: `${vencidasCount} ${pluralRestricciones(vencidasCount)} vencidas, la más antigua con ${vencidasMaxDias} días de atraso.`,
    variables: { vencidas: vencidasCount, diasMax: vencidasMaxDias },
  }
}

function titularAdherenciaInsuficiente(): TitularResultado {
  return {
    condicion: 'adherencia_insuficiente',
    texto: 'No hay datos suficientes todavía para calcular la adherencia al análisis de restricciones duras.',
    variables: {},
  }
}

/** % de actividades que entró sin análisis, redondeado al entero — cifra que llena todas las plantillas 4/5. */
function sinAnalisisPct(value: number): number {
  return Math.round((1 - value) * 100)
}

function titularAdherenciaBaja(pct: number): TitularResultado {
  return {
    condicion: 'adherencia_baja',
    texto: `El ${pct}% de las actividades entró al lookahead sin análisis de restricciones duras.`,
    variables: { sinAnalisisPct: pct },
  }
}

function titularSano(pct: number): TitularResultado {
  return {
    condicion: 'sano',
    texto: `Solo el ${pct}% de las actividades entró sin análisis: el lookahead está bien gestionado.`,
    variables: { sinAnalisisPct: pct },
  }
}

function titularNeutral(): TitularResultado {
  return {
    condicion: 'neutral',
    texto: 'Sin novedades que reportar en el lookahead de esta semana.',
    variables: {},
  }
}

/**
 * Construye el titular narrativo de la hoja Intermedia (posición 2 del lienzo, CT-8.3): el
 * primero de un juego finito de seis condiciones, evaluadas en este orden de prioridad —
 * huérfanas > vencidas > adherencia_insuficiente > adherencia_baja > sano > neutral. Nunca
 * devuelve una cadena vacía.
 */
export function construirTitular(resumen: ResumenLookaheadIntermedia): TitularResultado {
  if (resumen.huerfanasCount > 0) {
    return titularHuerfanas(resumen)
  }

  if (resumen.vencidasCount > 0) {
    return titularVencidas(resumen)
  }

  const { listasRate } = resumen

  if (listasRate.completeness === 'insuficiente') {
    return titularAdherenciaInsuficiente()
  }

  if (listasRate.completeness === 'completa' && listasRate.value !== null) {
    const pct = sinAnalisisPct(listasRate.value)
    return pct > UMBRAL_SIN_ANALISIS_PCT ? titularAdherenciaBaja(pct) : titularSano(pct)
  }

  return titularNeutral()
}
