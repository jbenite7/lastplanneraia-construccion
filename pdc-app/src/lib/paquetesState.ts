import type { SugerenciaPaquete } from './types'

/** Clave estable de un insumo único: norma + unidad. */
export function claveInsumo(descripcionNorm: string, unidad: string): string {
  return `${descripcionNorm}@@${unidad}`
}

/**
 * El único botón de propuestas de la pantalla de Paquetes.
 *
 * Antes eran dos —«Sembrar 1ª iteración» y «Auto-asignar lo seguro»— y elegir entre ellos exigía
 * entender una distinción interna del motor: proponer contra escribir, y el umbral de $20 M. Ahora
 * hay uno solo y **solo propone**: las propuestas aparecen en la columna «Sugerencia» y no se
 * guarda nada hasta que la persona pulsa «Aceptar N sugeridas».
 *
 * Vive aquí, y no suelto en el componente, para que la etiqueta y el endpoint sean verificables:
 * si algún día el botón pasara a escribir, habría que cambiar este contrato a la vista de todos.
 */
export const ACCION_PROPONER = {
  etiqueta: 'Proponer destinos',
  endpoint: '/plan-compras/api/paquetes/sugerencias',
  escribe: false,
} as const

/** Los cuatro estados por los que se puede filtrar la grilla de insumos. */
export type FiltroPaquetes = 'todos' | 'sin_asignar' | 'asignados' | 'omitidos'

/**
 * Con qué filtro abre la pantalla de Paquetes.
 *
 * Abre en «Sin asignar» mientras quede algo pendiente. La revisión encontró un insumo suelto entre
 * 396 filas con el filtro en «Todos»: para dar con él había que saber de antemano que existía el
 * desplegable. Lo primero que se ve tiene que ser el trabajo que falta, no el que ya está hecho.
 * Cuando no queda nada pendiente, «Todos» vuelve a ser lo útil — es la vista del resultado.
 */
export function filtroInicial(resumen: { sinAsignar: number; total: number } | null): FiltroPaquetes {
  if (resumen === null) return 'todos'
  return resumen.sinAsignar > 0 ? 'sin_asignar' : 'todos'
}

export type PaquetesState = {
  seleccion: Set<string>
  sugerencias: Map<string, SugerenciaPaquete>
  ocupado: boolean
  mensaje: string | null
}

export type PaquetesAction =
  | { type: 'TOGGLE_SEL'; clave: string }
  | { type: 'SEL_TODOS'; claves: string[] }
  | { type: 'LIMPIAR_SEL' }
  | { type: 'SUGERENCIAS_OK'; sugerencias: SugerenciaPaquete[] }
  | { type: 'LIMPIAR_SUGERENCIAS' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialPaquetes: PaquetesState = {
  seleccion: new Set(), sugerencias: new Map(), ocupado: false, mensaje: null,
}

export function paquetesReducer(state: PaquetesState, action: PaquetesAction): PaquetesState {
  switch (action.type) {
    case 'TOGGLE_SEL': {
      const seleccion = new Set(state.seleccion)
      if (seleccion.has(action.clave)) seleccion.delete(action.clave)
      else seleccion.add(action.clave)
      return { ...state, seleccion }
    }
    case 'SEL_TODOS':
      return { ...state, seleccion: new Set(action.claves) }
    case 'LIMPIAR_SEL':
      return { ...state, seleccion: new Set() }
    case 'SUGERENCIAS_OK': {
      const sugerencias = new Map<string, SugerenciaPaquete>()
      for (const s of action.sugerencias) sugerencias.set(claveInsumo(s.descripcionNorm, s.unidad), s)
      return { ...state, sugerencias, ocupado: false, mensaje: null }
    }
    case 'LIMPIAR_SUGERENCIAS':
      return { ...state, sugerencias: new Map() }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      return { ...state, ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
  }
}

/**
 * ¿Ya no queda valor que asignar?
 *
 * Con el 100 % del valor asignado, la pantalla seguía abriendo con tres barras de controles y once
 * botones para enseñar un insumo de $ 0. El trabajo que importa está hecho; el aparato para hacerlo
 * puede plegarse.
 */
export function estaCerradoPorValor(coberturaValor: number | undefined): boolean {
  return coberturaValor === 100
}

/**
 * ¿Vale la pena enseñar el tipo de negociación de un paquete?
 *
 * Antes esto se decidía por la modalidad, para tapar un dato falso: «Nómina de obra» salía como
 * CONSUMIBLES porque el catálogo no tenía ningún valor que describiera «no se le compra a nadie».
 * Corregido ese dato en el catálogo (los cuatro buckets no contratables pasaron a `no_aplica`, ver
 * 20260728_pdc_v2_tipo_no_aplica.php), la regla vuelve a ser la simple: se esconde el tipo que no
 * aporta, no la modalidad.
 *
 * El cambio recupera un badge que el parche escondía de más: «Ferretería y consumibles de obra» es
 * `consumo_directo` pero su tipo `suministro` siempre fue cierto —sí se suministra, solo que a
 * demanda— y ahora se vuelve a ver.
 */
export function muestraTipoNegociacion(tipoNegociacion?: string): boolean {
  return tipoNegociacion !== 'no_aplica'
}
