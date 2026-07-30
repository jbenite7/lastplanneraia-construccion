/**
 * Espejo de `App\Services\Pdc\TipoRecursoEquipo`. Los strings tienen que coincidir EXACTAMENTE.
 *
 * `ALQUILADO` vale `ALQUILER EQUIPOS` porque es el valor que SINCO ya emite: adoptarlo evita tener
 * dos nombres para la misma cosa cada vez que se recarga el maestro. Lo que la persona lee sale de
 * `etiquetaTipoRecurso`, no del dato guardado.
 *
 * Hay un test a cada lado que fija estos valores, por lo mismo que se fijaron los cinco de
 * `TIPOS_NEGOCIACION`: una divergencia PHP↔SPA no rompe nada visible hasta que alguien guarda, y
 * entonces falla sin explicar por qué.
 */
export const TIPO_RECURSO_EQUIPO = {
  SIN_CLASIFICAR: 'EQUIPO (SIN CLASIFICAR)',
  ALQUILADO: 'ALQUILER EQUIPOS',
  COMPRADO: 'EQUIPO COMPRADO',
} as const

export type DestinoEquipo = typeof TIPO_RECURSO_EQUIPO.ALQUILADO | typeof TIPO_RECURSO_EQUIPO.COMPRADO

const ETIQUETAS: Record<string, string> = {
  [TIPO_RECURSO_EQUIPO.SIN_CLASIFICAR]: 'Equipo (sin clasificar)',
  [TIPO_RECURSO_EQUIPO.ALQUILADO]: 'Equipo alquilado',
  [TIPO_RECURSO_EQUIPO.COMPRADO]: 'Equipo comprado',
}

/** Nombre legible de un tipo de recurso. Los que no son equipo pasan tal cual. */
export function etiquetaTipoRecurso(valor: string): string {
  return ETIQUETAS[valor] ?? valor
}
