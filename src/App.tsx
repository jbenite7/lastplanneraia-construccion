import { HashRouter, NavLink, Navigate, Route, Routes } from 'react-router-dom'
import { PANTALLAS } from './lib/navegacion'
import MaestroInsumos from './pages/MaestroInsumos'
import ImportarPresupuesto from './pages/ImportarPresupuesto'
import VisorPresupuesto from './pages/VisorPresupuesto'
import ComparativoPresupuesto from './pages/ComparativoPresupuesto'
import PaquetesContratacion from './pages/PaquetesContratacion'
import PlanFechas from './pages/PlanFechas'
import PasosContratacion from './pages/PasosContratacion'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        {/* Pestañas del módulo, no una segunda barra de navegación del sistema. La barra lateral
            del shell aporta UNA entrada al módulo y el nombre del módulo ya lo dice su barra de
            contexto; repetirlo aquí era parte del problema («dos sistemas de navegación
            conviviendo»). Son enlaces con `aria-current="page"` —lo que NavLink pone solo— y no
            role="tab": estas pestañas navegan entre rutas, y el patrón ARIA de pestañas describe
            paneles que se muestran y esconden dentro de una misma página. Ese sí se usa, tal cual,
            dentro de Maestro, Paquetes y Plan. */}
        <nav className="pdc-nav" aria-label="Submódulos del plan de compras">
          {PANTALLAS.map((p) => (
            <NavLink key={p.ruta} to={p.ruta} className="pdc-nav-link">{p.etiqueta}</NavLink>
          ))}
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
          {/* Fuera de PANTALLAS a propósito: se configura una vez por obra, y se llega desde el
              Plan de compras. Una pestaña permanente para eso sería ruido en la barra. */}
          <Route path="/ensamble/plan/pasos" element={<PasosContratacion />} />
          <Route path="/maestro" element={<Navigate to="/ensamble/maestro" replace />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
