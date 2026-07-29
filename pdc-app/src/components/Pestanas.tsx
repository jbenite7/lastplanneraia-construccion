import { useRef } from 'react'
import { etiquetaPestana, focoPorTecla } from '../lib/pestanas'
import type { Pestana } from '../lib/pestanas'

/**
 * Pestañas dentro de una pantalla. Un solo componente para las tres pantallas que las necesitan:
 * implementarlo tres veces nos devolvería al problema del tema de AG Grid duplicado seis veces.
 *
 * Aquí sí es el patrón ARIA de pestañas de verdad —paneles que se muestran y esconden dentro de la
 * misma página—, a diferencia de la barra del módulo, que navega entre rutas y usa `aria-current`.
 *
 * Quien la usa monta SOLO el panel activo. No es un detalle de rendimiento: una tabla de AG Grid
 * montada dentro de un panel oculto mide mal su ancho y sale descuadrada al mostrarse.
 */
export default function Pestanas({
  pestanas,
  activa,
  onCambiar,
  idBase,
  etiquetaLista,
}: {
  pestanas: Pestana[]
  activa: string
  onCambiar: (id: string) => void
  idBase: string
  etiquetaLista: string
}) {
  const refs = useRef<Record<string, HTMLButtonElement | null>>({})
  const indiceActivo = Math.max(0, pestanas.findIndex((p) => p.id === activa))

  return (
    <div className="pdc-pestanas" role="tablist" aria-label={etiquetaLista}>
      {pestanas.map((p, i) => (
        <button
          key={p.id}
          ref={(el) => { refs.current[p.id] = el }}
          type="button"
          role="tab"
          id={`${idBase}-tab-${p.id}`}
          aria-controls={`${idBase}-panel-${p.id}`}
          aria-selected={p.id === activa}
          // Un solo punto de parada en toda la lista: se entra con el tabulador y se recorre con
          // las flechas, que es lo que espera quien navega con teclado.
          tabIndex={i === indiceActivo ? 0 : -1}
          className={p.id === activa ? 'pdc-pestana is-activa' : 'pdc-pestana'}
          onClick={() => onCambiar(p.id)}
          onKeyDown={(e) => {
            const destino = focoPorTecla(indiceActivo, pestanas.length, e.key)
            if (destino === indiceActivo) return
            e.preventDefault()
            const id = pestanas[destino].id
            onCambiar(id)
            refs.current[id]?.focus()
          }}
        >
          {etiquetaPestana(p)}
        </button>
      ))}
    </div>
  )
}

/** Envoltorio del contenido de una pestaña, con la relación de ida y vuelta que pide el patrón. */
export function PanelPestana({
  idBase,
  id,
  children,
}: {
  idBase: string
  id: string
  children: React.ReactNode
}) {
  return (
    <div role="tabpanel" id={`${idBase}-panel-${id}`} aria-labelledby={`${idBase}-tab-${id}`} tabIndex={0}>
      {children}
    </div>
  )
}
