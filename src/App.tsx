import { HashRouter, Routes, Route, Navigate } from 'react-router-dom'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <Routes>
          <Route path="/" element={<Navigate to="/maestro" replace />} />
          <Route path="/maestro" element={<main>Plan de Compras v2</main>} />
        </Routes>
      </div>
    </HashRouter>
  )
}
