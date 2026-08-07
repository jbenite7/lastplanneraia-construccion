import { useEffect, useId, useRef, useState } from 'react'
import { ListaBuscable } from './ListaBuscable'
import type { Opcion } from '../lib/listaBuscable'

export interface SelectorProps {
  value: string
  onChange: (valor: string) => void
  opciones: Opcion[]
  etiqueta: string
  placeholder?: string
  disabled?: boolean
  testid?: string
}

/**
 * Sustituto de `<select>`. Misma forma de uso (valor controlado, `onChange` con un string), pero
 * la lista se puede buscar en cuanto pasa de ocho opciones.
 *
 * Por qué no es un `<select>` nativo con `<datalist>`: el nativo no admite buscar dentro de sus
 * opciones y `<datalist>` no restringe a la lista. Aquí el valor siempre sale de las opciones.
 */
export function Selector({
  value, onChange, opciones, etiqueta, placeholder = 'Elegir…', disabled = false, testid,
}: SelectorProps) {
  const [abierto, setAbierto] = useState(false)
  const caja = useRef<HTMLDivElement>(null)
  const boton = useRef<HTMLButtonElement>(null)
  const idBase = useId()
  const elegida = opciones.find((o) => o.valor === value)

  // Cerrar al hacer clic fuera. Sin esto quedan dos popups abiertos a la vez cuando la página
  // tiene varios selectores seguidos, que es el caso de Paquetes y del Plan.
  useEffect(() => {
    if (!abierto) return
    const fuera = (e: MouseEvent) => {
      if (caja.current && !caja.current.contains(e.target as Node)) setAbierto(false)
    }
    document.addEventListener('mousedown', fuera)
    return () => document.removeEventListener('mousedown', fuera)
  }, [abierto])

  // Cierra y devuelve el foco al botón que abrió el popup, tanto al elegir una opción como al
  // salir con Escape — si no, el popup se desmonta con el foco dentro y cae al <body>.
  const cerrar = () => {
    setAbierto(false)
    boton.current?.focus()
  }

  const seleccionar = (valor: string) => {
    // Un <select> nativo no dispara onChange si se reelige el mismo valor; este tampoco debe.
    if (valor === value) { cerrar(); return }
    onChange(valor)
    cerrar()
  }

  return (
    <div
      className="pdc-selector-caja"
      ref={caja}
      // Si el foco sale del control (Tab) sin pasar por otro elemento interno, el popup queda
      // abierto y desanclado del foco: lo cerramos también en ese caso.
      onBlur={(e) => {
        if (!e.currentTarget.contains(e.relatedTarget as Node | null)) setAbierto(false)
      }}
    >
      <button
        ref={boton}
        type="button"
        className="pdc-selector-boton"
        data-testid={testid}
        aria-label={etiqueta}
        aria-haspopup="listbox"
        aria-expanded={abierto}
        disabled={disabled}
        onClick={() => setAbierto((a) => !a)}
      >
        <span className={elegida ? 'pdc-selector-valor' : 'pdc-selector-valor es-vacio'}>
          {elegida ? elegida.etiqueta : placeholder}
        </span>
        <span className="pdc-selector-flecha" aria-hidden="true" />
      </button>
      {abierto && (
        <div className="pdc-selector-popup">
          <ListaBuscable
            opciones={opciones}
            modo="una"
            seleccion={value === '' ? [] : [value]}
            onSeleccion={(s) => seleccionar(s[0] ?? '')}
            onCerrar={cerrar}
            idBase={idBase}
          />
        </div>
      )}
    </div>
  )
}
