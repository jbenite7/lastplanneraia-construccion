import { HashRouter, NavLink, Navigate, Route, Routes } from 'react-router-dom'
import MaestroInsumos from './pages/MaestroInsumos'
import ImportarPresupuesto from './pages/ImportarPresupuesto'
import VisorPresupuesto from './pages/VisorPresupuesto'
import ComparativoPresupuesto from './pages/ComparativoPresupuesto'
import PaquetesContratacion from './pages/PaquetesContratacion'
import PlanFechas from './pages/PlanFechas'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <nav className="pdc-nav" aria-label="Submódulos del plan de compras">
          <span className="pdc-nav-title">Plan de Compras</span>
          <NavLink to="/ensamble/importar" className="pdc-nav-link">Ensamble</NavLink>
          <NavLink to="/ensamble/maestro" className="pdc-nav-link">Maestro</NavLink>
          <NavLink to="/ensamble/presupuesto" className="pdc-nav-link">Presupuesto</NavLink>
          <NavLink to="/ensamble/comparar" className="pdc-nav-link">Comparar</NavLink>
          <NavLink to="/ensamble/paquetes" className="pdc-nav-link">Paquetes</NavLink>
          <NavLink to="/ensamble/plan" className="pdc-nav-link">Plan</NavLink>
          <span className="pdc-nav-link pdc-nav-disabled" aria-disabled="true" title="Disponible en la fase B">
            Seguimiento
          </span>
        </nav>
        <Routes>
          <Route path="/" element={<Navigate to="/ensamble/importar" replace />} />
          <Route path="/ensamble/importar" element={<ImportarPresupuesto />} />
          <Route path="/ensamble/maestro" element={<MaestroInsumos />} />
          <Route path="/ensamble/presupuesto" element={<VisorPresupuesto />} />
          <Route path="/ensamble/comparar" element={<ComparativoPresupuesto />} />
          <Route path="/ensamble/paquetes" element={<PaquetesContratacion />} />
          <Route path="/ensamble/plan" element={<PlanFechas />} />
          <Route path="/maestro" element={<Navigate to="/ensamble/maestro" replace />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
