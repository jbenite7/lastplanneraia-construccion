import { useEffect, useMemo, useRef, useState } from 'react'
import type { KeyboardEvent } from 'react'
import {
  alterna, mueveResaltado, necesitaBuscador, opcionesVisibles,
} from '../lib/listaBuscable'
import type { Opcion } from '../lib/listaBuscable'

export interface ListaBuscableProps {
  opciones: Opcion[]
  modo: 'una' | 'varias'
  seleccion: string[]
  onSeleccion: (s: string[]) => void
  onCerrar?: () => void
  idBase: string
}

/**
 * Lista de opciones con buscador. Es la única lista del módulo: la usan el desplegable
 * (`Selector`) y el filtro de columna (`FiltroLista`), para que buscar se sienta igual en los dos.
 *
 * La lógica está en `lib/listaBuscable.ts` y aquí solo queda el render — el entorno de pruebas del
 * módulo no tiene DOM, así que todo lo comprobable tiene que vivir fuera de este archivo.
 */
export function ListaBuscable({
  opciones, modo, seleccion, onSeleccion, onCerrar, idBase,
}: ListaBuscableProps) {
  const [busqueda, setBusqueda] = useState('')
  const [resaltado, setResaltado] = useState(0)
  const cajaBusqueda = useRef<HTMLInputElement>(null)
  const visibles = useMemo(() => opcionesVisibles(opciones, busqueda), [opciones, busqueda])
  const conBuscador = necesitaBuscador(opciones.length)

  // Al abrir, el foco va a la caja: quien abre una lista de trescientos insumos viene a escribir.
  useEffect(() => { cajaBusqueda.current?.focus() }, [])
  // Teclear recorta la lista, y el resaltado podría quedar apuntando fuera de ella.
  useEffect(() => { setResaltado(0) }, [busqueda])

  const elegir = (valor: string) => {
    if (modo === 'varias') {
      onSeleccion(alterna(seleccion, valor))
      return
    }
    onSeleccion([valor])
    onCerrar?.()
  }

  const alTeclado = (e: KeyboardEvent) => {
    if (e.key === 'Escape') { e.preventDefault(); onCerrar?.(); return }
    if (e.key === 'Enter') {
      e.preventDefault()
      const opcion = visibles[resaltado]
      if (opcion) elegir(opcion.valor)
      return
    }
    const siguiente = mueveResaltado(resaltado, e.key, visibles.length)
    if (siguiente !== resaltado) { e.preventDefault(); setResaltado(siguiente) }
  }

  return (
    <div className="pdc-lista" onKeyDown={alTeclado}>
      {conBuscador && (
        <input
          ref={cajaBusqueda}
          type="search"
          className="pdc-lista-buscar"
          placeholder="Buscar…"
          aria-label="Buscar en la lista"
          aria-controls={`${idBase}-opciones`}
          value={busqueda}
          onChange={(e) => setBusqueda(e.target.value)}
        />
      )}
      {modo === 'varias' && (
        <div className="pdc-lista-masa">
          <button type="button" onClick={() => onSeleccion(visibles.map((o) => o.valor))}>
            Todas
          </button>
          <button type="button" onClick={() => onSeleccion([])}>Ninguna</button>
        </div>
      )}
      <ul
        id={`${idBase}-opciones`}
        className="pdc-lista-opciones"
        role="listbox"
        aria-multiselectable={modo === 'varias' || undefined}
        aria-activedescendant={visibles[resaltado] ? `${idBase}-op-${resaltado}` : undefined}
        tabIndex={conBuscador ? -1 : 0}
      >
        {visibles.map((o, i) => (
          <li
            key={o.valor}
            id={`${idBase}-op-${i}`}
            role="option"
            aria-selected={seleccion.includes(o.valor)}
            className={i === resaltado ? 'pdc-lista-op es-resaltada' : 'pdc-lista-op'}
            onClick={() => elegir(o.valor)}
            onMouseEnter={() => setResaltado(i)}
          >
            {modo === 'varias' && (
              <input type="checkbox" readOnly checked={seleccion.includes(o.valor)} tabIndex={-1} />
            )}
            {o.etiqueta}
          </li>
        ))}
        {visibles.length === 0 && <li className="pdc-lista-vacia">Nada coincide con «{busqueda}»</li>}
      </ul>
    </div>
  )
}
