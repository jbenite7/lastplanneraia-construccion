import { createContext } from 'react'

/**
 * Cómo pide una pantalla que se vuelva a ver el recorrido del módulo.
 *
 * Vive en su propio archivo y no en `App.tsx` para romper el ciclo: `App` importa `BotonAyuda`, y
 * `BotonAyuda` necesita este contexto.
 *
 * Lo consume `BotonAyuda` directamente, en vez de recibirlo por props desde cada página. Con ocho
 * páginas montando el botón, pasar el mismo callback ocho veces es cableado que alguien acaba
 * olvidando en la novena — y el síntoma sería un botón de «ver otra vez el recorrido» que no
 * aparece, que es justo la clase de fallo que nadie reporta.
 *
 * El valor por defecto no hace nada a propósito: un botón montado fuera del proveedor —en una
 * prueba, por ejemplo— debe renderizar sin relanzador, no reventar.
 */
export const AyudaContexto = createContext<{ relanzarRecorrido: (() => void) | null }>({
  relanzarRecorrido: null,
})
