/**
 * Las palabras y las reglas de pantalla de los subpaquetes («lotes»).
 *
 * Un paquete de preconstrucción —«Pisos»— se parte en los lotes que la obra de verdad contrata:
 * porcelanato, tableta gres, cerámica, cada uno con su proveedor y su fecha. Ninguna decisión de
 * dominio vive aquí: quién es el «Resto», qué modalidad genera proceso y cómo se cuenta un destino
 * contratable lo decide el servidor (`SubpaquetesService`), porque el plan de fechas, el tablero de
 * vencimientos y el flujo de caja consumen esa misma definición y no puede haber dos.
 */

export type Subpaquete = {
  subpaqueteId: number
  nombre: string
  modalidad: string
  responsableUserId: number | null
  esResto: boolean
  orden: number
  insumos: number
  valor: number
  generaProceso: boolean
}

export type ResumenSombrilla = {
  lotes: number
  valorTotal: number
  valorFueraDelPlan: number
  lotesFueraDelPlan: { nombre: string; modalidad: string; valor: number }[]
  desde: string | null
  hasta: string | null
  lotesConPlan: number
  pasos: number
  pasosCumplidos: number
  avance: number | null
}

export type RespuestaSubpaquetes = {
  subpaquetes: Subpaquete[]
  resumen: ResumenSombrilla | null
}

/**
 * Los nombres que el usuario escribió en el campo de partir, limpios.
 *
 * Se parte por salto de línea y por coma: la gente escribe las dos cosas, y rechazar una de las dos
 * formas obliga a adivinar cuál espera la pantalla. Se quitan vacíos y duplicados —el servidor los
 * rechazaría igual, pero avisar antes de enviar es más barato que un error de vuelta.
 */
export function nombresDePartir(texto: string): string[] {
  const crudos = texto
    .split(/[\n,]/)
    .map((n) => n.trim())
    .filter((n) => n !== '')
  return [...new Set(crudos)]
}

/**
 * Por qué el botón de partir está desactivado, o cadena vacía si se puede partir.
 *
 * Devuelve el motivo y no un booleano a propósito: un botón apagado sin explicación es la forma más
 * rápida de que alguien crea que la aplicación está rota.
 */
export function motivoNoPartir(texto: string): string {
  const nombres = nombresDePartir(texto)
  if (nombres.length === 0) return 'Escribe el nombre de al menos un lote.'
  return ''
}

/**
 * El rango de fechas del paquete sombrilla, en una frase.
 *
 * Vacío mientras ningún lote tenga plan: un rango a medias se lee como el rango completo, y quien lo
 * vea no tiene forma de saber que faltan lotes por fechar.
 */
export function textoRango(r: ResumenSombrilla): string {
  if (r.desde === null || r.hasta === null) {
    return 'Ningún lote tiene fechas todavía: amárralos a su frente en «Plan» y recalcula.'
  }
  const cuantos =
    r.lotesConPlan === r.lotes
      ? `sus ${r.lotes} lotes`
      : `${r.lotesConPlan} de sus ${r.lotes} lotes`
  return `De ${r.desde} a ${r.hasta}, abarcando ${cuantos}.`
}

/**
 * Cuánto del valor del paquete no entra al plan, en una frase, con los lotes que lo causan.
 *
 * Es la contrapartida de haber dejado que cada lote elija su modalidad: si la obra decide que la
 * cerámica es una provisión, ese dinero deja de generar contratación y el sombrilla tiene que
 * decirlo, no callarlo.
 */
export function textoFueraDelPlan(r: ResumenSombrilla, formatoValor: (v: number) => string): string {
  if (r.lotesFueraDelPlan.length === 0) return ''
  const nombres = r.lotesFueraDelPlan.map((l) => `${l.nombre} (${l.modalidad})`).join(' · ')
  return `${formatoValor(r.valorFueraDelPlan)} de este paquete no entra al plan de fechas: ${nombres}.`
}

/**
 * El avance agregado del sombrilla en palabras. `null` cuando no hay pasos: un «0 %» sin proceso
 * detrás se lee como un proceso atascado.
 */
export function textoAvance(r: ResumenSombrilla): string {
  if (r.avance === null) return ''
  return `${r.pasosCumplidos} de ${r.pasos} pasos cumplidos (${r.avance} %).`
}

/**
 * ¿Se puede borrar este lote? El «Resto» no: desaparece solo al deshacer la partición, y ofrecer un
 * botón que siempre falla es peor que no ofrecerlo.
 */
export function sePuedeBorrar(s: Subpaquete): boolean {
  return !s.esResto
}

/**
 * Aviso de que borrar el último lote de verdad deshace la partición entera.
 *
 * Sin este aviso, quien borre el tercer lote de tres ve desaparecer también el «Resto» y no entiende
 * por qué: es correcto —un paquete con un único lote «Resto» sería el «lote de compatibilidad» que el
 * alcance prohíbe— pero tiene que estar dicho antes, no después.
 */
export function avisoAlBorrar(lotes: Subpaquete[], subpaqueteId: number): string {
  const deVerdad = lotes.filter((l) => !l.esResto)
  if (deVerdad.length === 1 && deVerdad[0].subpaqueteId === subpaqueteId) {
    return 'Es el último lote: al borrarlo, el paquete deja de estar partido y todos sus insumos vuelven a él.'
  }
  return 'Sus insumos volverán al lote «Resto».'
}
