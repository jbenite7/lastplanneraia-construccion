import './lib/tokens.css'

// Andamiaje de la etapa piloto (Task 6): sin páginas todavía — las construye Task 7.
// Este componente solo prueba que el bundle levanta y que los tokens del design system
// llegan por `@import`, no que la pantalla esté terminada.
function App() {
  // Sin className: 'ct-app-shell' no tiene ninguna regla en tokens.css ni en ningún otro CSS
  // del bundle — sería una clase muerta. Task 7 le pone la suya cuando construya la pantalla real.
  return <div>Torre de Control — andamiaje en construcción</div>
}

export default App
