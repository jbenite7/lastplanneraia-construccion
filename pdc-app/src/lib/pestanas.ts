/**
 * Pestañas dentro de una pantalla.
 *
 * Las tres pantallas grandes del módulo apilaban sus tablas una debajo de otra, y la de abajo solo
 * se descubría haciendo scroll: en Maestro había 3.079 insumos de catálogo tapando la cola de
 * pendientes, y en Plan las sugerencias de frente vivían debajo de la grilla. Con pestañas se ve de
 * entrada cuántas secciones hay y se salta a cualquiera sin desplazarse.
 */
export type Pestana = {
  id: string
  etiqueta: string
  /** Número que va en la propia pestaña. Es lo que hace visible el trabajo pendiente sin abrirla. */
  conteo?: number
}

/**
 * Qué pestaña recibe el foco al pulsar una tecla dentro de la lista, según el patrón ARIA de
 * pestañas: flechas para moverse (dando la vuelta en los extremos), Inicio y Fin para los bordes.
 * Cualquier otra tecla no mueve el foco — la escribe quien esté escribiendo.
 */
export function focoPorTecla(actual: number, total: number, tecla: string): number {
  if (total <= 0) return 0
  switch (tecla) {
    case 'ArrowRight':
      return (actual + 1) % total
    case 'ArrowLeft':
      return (actual - 1 + total) % total
    case 'Home':
      return 0
    case 'End':
      return total - 1
    default:
      return actual
  }
}

/** Etiqueta completa de una pestaña: con su conteo cuando lo tiene. */
export function etiquetaPestana(p: Pestana): string {
  return p.conteo === undefined ? p.etiqueta : `${p.etiqueta} (${p.conteo})`
}
