/**
 * Las palabras de la curva de desembolsos.
 *
 * Ninguna aritmética del reparto vive aquí: la hace el servidor (`FlujoCajaService`), porque la
 * exportación a Excel tiene que traer exactamente los mismos números que la pantalla y un segundo
 * cálculo en el navegador es la forma más fácil de que dejen de coincidir.
 *
 * Lo que sí vive aquí es lo que hay que **decir**. Esta curva va a llegar a un comité de dirección y
 * alguien la va a tratar como presupuesto de tesorería: la advertencia del método y la declaración de
 * lo que queda fuera no son adornos, son parte del dato.
 */

export type MesFlujo = {
  mes: string
  previsto: number
  acumulado: number
  destinos: number
  /** Contrataciones con frente amarrado: reparto sobre las fechas de su propio frente. */
  contratado: number
  /** Nómina, imprevistos, provisiones y ferretería: reparto sobre toda la duración de la obra. */
  permanente: number
  /** Se va a contratar, pero nadie le ha amarrado frente: reparto que SE VA A MOVER. */
  provisional: number
}

export type OrigenFlujo = 'contratado' | 'permanente' | 'provisional'

export type ExcluidosFlujo = {
  destinos: number
  valor: number
  motivos: Record<string, { destinos: number; valor: number }>
}

export type RespuestaFlujoCaja = {
  nota: string
  duracionObra: { desde: string; hasta: string; origen: string } | null
  meses: MesFlujo[]
  total: number
  porOrigen: Record<OrigenFlujo, { destinos: number; valor: number }>
  incluidos: { destinos: number; valor: number }
  excluidos: ExcluidosFlujo
  valorTotalDelPlan: number
  detalle: {
    paqueteId: number
    subpaqueteId: number
    nombre: string
    paqueteNombre: string
    valor: number
    incluido: boolean
    origen: OrigenFlujo
    motivoExclusion: string | null
    repartoDesde: string | null
    repartoHasta: string | null
    meses: Record<string, number>
  }[]
}

const MESES = [
  'enero',
  'febrero',
  'marzo',
  'abril',
  'mayo',
  'junio',
  'julio',
  'agosto',
  'septiembre',
  'octubre',
  'noviembre',
  'diciembre',
]

/**
 * `2026-02` → `febrero 2026`.
 *
 * Sin `new Date(mes)`: `new Date('2026-02')` se interpreta en UTC y en husos al oeste —el nuestro es
 * UTC−5— devuelve el 31 de enero, así que la fila de febrero se rotularía «enero». Se parte la cadena
 * a mano, que además no depende del `locale` del navegador.
 */
export function etiquetaMes(mes: string): string {
  const [anio, num] = mes.split('-')
  const nombre = MESES[Number(num) - 1]
  if (nombre === undefined || anio === undefined) return mes
  return `${nombre} ${anio}`
}

/**
 * Qué porcentaje del valor del plan cubre la curva.
 *
 * Desde que la curva cuenta el presupuesto entero esto es 100 % siempre que la obra tenga fechas, y
 * por eso vale la pena mostrarlo: cuando NO es 100 % es porque algo quedó fuera, y ese es justo el
 * momento en que hay que mirar.
 *
 * `null` cuando no hay nada que medir: un «0 %» sobre un plan vacío se lee como un fallo del cálculo,
 * y un «100 %» sobre un plan vacío es peor todavía.
 */
export function cobertura(r: RespuestaFlujoCaja): number | null {
  if (r.valorTotalDelPlan <= 0) return null
  return Math.round((r.incluidos.valor / r.valorTotalDelPlan) * 1000) / 10
}

/**
 * Qué parte de la curva son compromisos con fecha propia, en porcentaje.
 *
 * Es la cifra que dice cuánto se puede creer de la forma de la curva. Con el 90 % contratado, los
 * picos son reales; con el 30 %, la curva es sobre todo un reparto uniforme y su forma no significa
 * gran cosa todavía. `null` con la curva vacía.
 */
export function porcentajeConFecha(r: RespuestaFlujoCaja): number | null {
  if (r.total <= 0) return null
  return Math.round((r.porOrigen.contratado.valor / r.total) * 1000) / 10
}

/**
 * La advertencia sobre la parte provisional: lo que se va a contratar pero todavía no tiene frente, y
 * que por tanto está repartido de forma uniforme y **se moverá** cuando alguien lo amarre.
 *
 * Cadena vacía cuando no hay nada provisional. Es la frase que evita que la curva se lea como más
 * firme de lo que es sin tener que esconder ese dinero.
 */
export function textoProvisional(r: RespuestaFlujoCaja, formatoValor: (v: number) => string): string {
  const p = r.porOrigen.provisional
  if (p.destinos <= 0) return ''
  const pct = r.total > 0 ? Math.round((p.valor / r.total) * 1000) / 10 : 0
  const cuantas =
    p.destinos === 1
      ? '1 contratación que todavía no tiene frente amarrado en el cronograma y va repartida'
      : `${p.destinos} contrataciones que todavía no tienen frente amarrado en el cronograma y van repartidas`
  return `${formatoValor(p.valor)} de esta curva (${pct} %) es reparto provisional: ${cuantas} por igual sobre toda la obra. Esa parte se moverá en cuanto se le amarre un frente.`
}

/** Rótulos de los tres orígenes. Los mismos en la pantalla y en la exportación. */
export const ETIQUETAS_ORIGEN: Record<OrigenFlujo, string> = {
  contratado: 'Contratado con fecha',
  permanente: 'Nómina y provisiones',
  provisional: 'Provisional',
}

/**
 * Lo que la curva NO está incluyendo, en una frase, con sus motivos.
 *
 * Cadena vacía cuando no hay nada fuera: no se inventa una advertencia cuando no hay nada que
 * declarar, igual que hace el tablero de vencimientos.
 */
export function textoExcluidos(e: ExcluidosFlujo, formatoValor: (v: number) => string): string {
  if (e.destinos <= 0) return ''
  const motivos = Object.entries(e.motivos)
    .sort((a, b) => b[1].valor - a[1].valor)
    .map(([motivo, m]) => `${m.destinos} ${motivo.toLowerCase()} (${formatoValor(m.valor)})`)
  const cabeza =
    e.destinos === 1
      ? `Esta curva no incluye 1 contratación por ${formatoValor(e.valor)}`
      : `Esta curva no incluye ${e.destinos} contrataciones por ${formatoValor(e.valor)}`
  return `${cabeza}: ${motivos.join(' · ')}.`
}

/**
 * El pico de la curva: el mes que más plata pide. Es la pregunta que un comité hace primero, y
 * buscarla a ojo en veinte filas es justo lo que la pantalla debería ahorrar.
 *
 * `null` con la curva vacía.
 */
export function mesPico(meses: MesFlujo[]): MesFlujo | null {
  if (meses.length === 0) return null
  return meses.reduce((max, m) => (m.previsto > max.previsto ? m : max), meses[0])
}

/**
 * Alto de la barra de un mes, en porcentaje del mes más alto. Es la única «forma» de la curva que se
 * dibuja: una barra por fila dentro de la propia tabla, sin librería de gráficos, porque lo que se
 * pide es leer números y ver de un golpe dónde está el pico.
 */
export function alturaBarra(previsto: number, pico: number): number {
  if (pico <= 0) return 0
  return Math.max(1, Math.round((previsto / pico) * 100))
}
