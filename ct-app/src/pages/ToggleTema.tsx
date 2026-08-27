import { useState } from 'react'
import './ToggleTema.css'
import { aplicarTemaAlDocumento } from '../lib/theme'
import type { Tema } from '../lib/theme'

// Toggle de tema visible (Task 8 paso 2, entrada 19c/27 de la Bitácora del plan). El estado
// inicial se lee del atributo que `main.tsx` ya aplicó al documento antes de montar React (no se
// vuelve a resolver aquí, para no divergir de lo que el usuario ya está viendo). El botón nombra
// la acción que ejecuta (el tema AL QUE cambia), no el tema actual — mismo patrón que cualquier
// control de "cambiar a-" del sistema.

function temaActualDelDocumento(): Tema {
  return document.documentElement.getAttribute('data-aia-theme') === 'light' ? 'light' : 'dark'
}

export function ToggleTema() {
  const [tema, setTema] = useState<Tema>(temaActualDelDocumento)

  function handleClick() {
    const siguiente: Tema = tema === 'dark' ? 'light' : 'dark'
    aplicarTemaAlDocumento(siguiente)
    setTema(siguiente)
  }

  return (
    <button type="button" className="ct-toggle-tema" onClick={handleClick}>
      {tema === 'dark' ? 'Tema claro' : 'Tema oscuro'}
    </button>
  )
}
