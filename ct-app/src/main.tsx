import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import { aplicarTemaAlDocumento, resolverTemaInicial } from './lib/theme'

// Task 8 paso 2 (entrada 19c/27 de la Bitácora del plan): se resuelve y aplica el tema ANTES del
// primer render de React — el documento ya llegó con `data-aia-theme="dark"` puesto por
// theme-bootstrap.js (servidor, sin flash); esto lo sobreescribe a la elección del usuario (o a
// `prefers-color-scheme`) en el primer tick posible, para minimizar el parpadeo si el resultado
// difiere de "dark".
aplicarTemaAlDocumento(resolverTemaInicial())

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
