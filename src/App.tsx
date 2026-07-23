import { HashRouter, NavLink, Navigate, Route, Routes } from 'react-router-dom'
import MaestroInsumos from './pages/MaestroInsumos'
import ImportarPresupuesto from './pages/ImportarPresupuesto'
import VisorPresupuesto from './pages/VisorPresupuesto'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <nav className="pdc-nav" aria-label="Submódulos del plan de compras">
          <span className="pdc-nav-title">Plan de Compras</span>
          <NavLink to="/ensamble/importar" className="pdc-nav-link">Ensamble</NavLink>
          <NavLink to="/ensamble/presupuesto" className="pdc-nav-link">Presupuesto</NavLink>
          <span className="pdc-nav-link pdc-nav-disabled" aria-disabled="true" title="Disponible en la fase B">
            Seguimiento
          </span>
        </nav>
        <Routes>
          <Route path="/" element={<Navigate to="/ensamble/importar" replace />} />
          <Route path="/ensamble/importar" element={<ImportarPresupuesto />} />
          <Route path="/ensamble/maestro" element={<MaestroInsumos />} />
          <Route path="/ensamble/presupuesto" element={<VisorPresupuesto />} />
          <Route path="/maestro" element={<Navigate to="/ensamble/maestro" replace />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
