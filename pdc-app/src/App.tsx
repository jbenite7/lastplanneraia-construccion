import { useCallback, useMemo, useState } from 'react'
import { HashRouter, NavLink, Navigate, Route, Routes } from 'react-router-dom'
import { PANTALLAS } from './lib/navegacion'
import { AyudaContexto } from './lib/ayudaContexto'
import { leerVisto, olvidarVisto } from './lib/recorrido'
import Recorrido from './components/Recorrido'
import MaestroInsumos from './pages/MaestroInsumos'
import ImportarPresupuesto from './pages/ImportarPresupuesto'
import VisorPresupuesto from './pages/VisorPresupuesto'
import ComparativoPresupuesto from './pages/ComparativoPresupuesto'
import PaquetesContratacion from './pages/PaquetesContratacion'
import PlanFechas from './pages/PlanFechas'
import PasosContratacion from './pages/PasosContratacion'
import Seguimiento from './pages/Seguimiento'

export default function App() {
  // Se decide una vez al montar. Si se leyera en cada render, marcar «visto» a mitad del recorrido
  // lo cerraría de golpe en el paso siguiente.
  const [recorridoActivo, setRecorridoActivo] = useState(() => !leerVisto())

  const relanzarRecorrido = useCallback(() => {
    olvidarVisto()
    setRecorridoActivo(true)
  }, [])

  // Memorizado para no rehacer el objeto en cada render: el contexto lo consumen los ocho botones
  // de ayuda, y un valor nuevo cada vez los volvería a renderizar todos sin motivo.
  const ayuda = useMemo(() => ({ relanzarRecorrido }), [relanzarRecorrido])

  return (
    <HashRouter>
      <AyudaContexto.Provider value={ayuda}>
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
          <Route path="/seguimiento/avance" element={<Seguimiento />} />
          <Route path="/maestro" element={<Navigate to="/ensamble/maestro" replace />} />
        </Routes>
        {/* Dentro de HashRouter porque navega entre pantallas, y dentro de .pdc-shell para heredar
            la escala densa del módulo. Uno para todo el módulo: no es la ayuda de una pantalla,
            es la presentación del camino completo. */}
        <Recorrido activo={recorridoActivo} onCerrar={() => setRecorridoActivo(false)} />
      </div>
      </AyudaContexto.Provider>
    </HashRouter>
  )
}
