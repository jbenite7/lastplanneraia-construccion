import './lib/tokens.css'
import { Intermedia } from './pages/Intermedia'

// Task 7 paso 6: reemplaza el placeholder de Task 6 (andamiaje) por la hoja real.
// `renderCtPiloto()` (BiViewController.php) sirve este bundle únicamente para la hoja
// Intermedia, así que no hace falta router: una sola pantalla, montada directo.
function App() {
  return <Intermedia />
}

export default App
