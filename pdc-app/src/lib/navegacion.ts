/**
 * Las pantallas del módulo y cómo se llaman.
 *
 * Vive fuera del componente para poder verificarse: los nombres de la barra son la primera —y a
 * veces la única— explicación que recibe quien entra, y ya costaron un hallazgo. «Ensamble» hacía
 * a la vez de etiqueta de la etapa completa y de nombre de la pantalla del cargue de Excel, así que
 * quien leía la barra no podía saber que las otras cinco también son Ensamble.
 */
export type EntradaNav = {
  ruta: string
  etiqueta: string
}

export const PANTALLAS: EntradaNav[] = [
  { ruta: '/ensamble/importar', etiqueta: 'Cargar presupuesto' },
  { ruta: '/ensamble/maestro', etiqueta: 'Maestro' },
  { ruta: '/ensamble/presupuesto', etiqueta: 'Presupuesto' },
  { ruta: '/ensamble/comparar', etiqueta: 'Comparar' },
  { ruta: '/ensamble/paquetes', etiqueta: 'Paquetes' },
  { ruta: '/ensamble/plan', etiqueta: 'Plan' },
  { ruta: '/seguimiento/avance', etiqueta: 'Seguimiento' },
]
