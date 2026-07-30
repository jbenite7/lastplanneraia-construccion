/**
 * La primera vuelta guiada por el módulo, y la memoria de que ya se dio.
 *
 * Persistencia como la de la barra lateral (`public/js/modules/aia_ui/sidebar_navigation.js`):
 * clave con prefijo `aia-`, lectura envuelta y valor validado contra una lista cerrada. El motivo
 * de envolverla no es teórico: con las cookies bloqueadas, leer el almacén del navegador lanza, y
 * la ayuda no puede ser lo que tumba el módulo.
 */
import type { PantallaAyuda } from './ayuda'

export const CLAVE_RECORRIDO = 'aia-pdc-recorrido'

/** El único valor guardado que significa algo. Cualquier otra cosa se trata como no visto. */
const VALOR_VISTO = 'visto'

export type PasoRecorrido = {
  pantalla: PantallaAyuda
  ruta: string
  titulo: string
  texto: string
}

/**
 * Seis paradas, no ocho: el recorrido cuenta el camino, no el inventario. Comparar y Pasos se
 * dejan fuera a propósito —el primero es consulta y el segundo se configura una vez— y las dos
 * tienen su botón de ayuda para quien llegue a ellas.
 */
export const PASOS_RECORRIDO: PasoRecorrido[] = [
  {
    pantalla: 'importar',
    ruta: '/ensamble/importar',
    titulo: 'Todo empieza con el presupuesto',
    texto:
      'Aquí se sube el Excel del presupuesto de la obra. Es el primer paso y el que alimenta a '
      + 'todos los demás: sin presupuesto cargado, el resto del módulo no tiene con qué trabajar.',
  },
  {
    pantalla: 'maestro',
    ruta: '/ensamble/maestro',
    titulo: 'Después, poner los insumos en el idioma de la empresa',
    texto:
      'Cada obra escribe los nombres de los insumos a su manera. Aquí se conectan con el catálogo '
      + 'único de la empresa, para que el mismo material sea el mismo material en todas las obras.',
  },
  {
    pantalla: 'presupuesto',
    ruta: '/ensamble/presupuesto',
    titulo: 'Mirar el presupuesto antes de seguir',
    texto:
      'Esta pantalla no cambia nada: sirve para revisar lo que entró y para que el sistema te '
      + 'señale lo que conviene mirar dos veces, como una actividad sin cantidad.',
  },
  {
    pantalla: 'paquetes',
    ruta: '/ensamble/paquetes',
    titulo: 'Del orden en que se construye al orden en que se compra',
    texto:
      'El presupuesto está ordenado como se construye. Aquí se agrupan sus insumos en paquetes de '
      + 'contratación: lo que se va a contratar junto, con un mismo tercero.',
  },
  {
    pantalla: 'plan',
    ruta: '/ensamble/plan',
    titulo: 'El plan te dice cuándo arrancar cada contratación',
    texto:
      'Con los paquetes armados y amarrados a un frente del cronograma, el sistema cuenta hacia '
      + 'atrás los días que toma contratar y te dice cuándo hay que empezar para llegar a tiempo.',
  },
  {
    pantalla: 'seguimiento',
    ruta: '/seguimiento/avance',
    titulo: 'Y esta es la pantalla del día a día',
    texto:
      'Aquí se marca lo que ya pasó y se ve qué se vence. Es la que vas a abrir todas las mañanas. '
      + 'Puedes volver a ver este recorrido desde el botón de ayuda de cualquier pantalla.',
  },
]

/** El almacén del navegador, o nada si no hay. Se resuelve tarde para poder inyectarlo en pruebas. */
function almacenPorDefecto(): Storage | null {
  try {
    return typeof globalThis.localStorage === 'undefined' ? null : globalThis.localStorage
  } catch {
    return null
  }
}

export function leerVisto(almacen: Storage | null = almacenPorDefecto()): boolean {
  if (!almacen) return false
  try {
    return almacen.getItem(CLAVE_RECORRIDO) === VALOR_VISTO
  } catch {
    return false
  }
}

export function marcarVisto(almacen: Storage | null = almacenPorDefecto()): void {
  if (!almacen) return
  try {
    almacen.setItem(CLAVE_RECORRIDO, VALOR_VISTO)
  } catch {
    // Sin memoria, el recorrido volverá a salir. Molesto, no roto.
  }
}

export function olvidarVisto(almacen: Storage | null = almacenPorDefecto()): void {
  if (!almacen) return
  try {
    almacen.removeItem(CLAVE_RECORRIDO)
  } catch {
    // Igual que arriba: no poder olvidar no justifica tumbar la pantalla.
  }
}
