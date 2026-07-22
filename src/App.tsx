import { HashRouter, Routes, Route, Navigate } from 'react-router-dom'
import MaestroInsumos from './pages/MaestroInsumos'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <Routes>
          <Route path="/" element={<Navigate to="/maestro" replace />} />
          <Route path="/maestro" element={<MaestroInsumos />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
