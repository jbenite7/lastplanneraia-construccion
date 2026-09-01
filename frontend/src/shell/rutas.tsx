import { useEffect } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { AppShell } from './AppShell';
import { PantallaLogin } from './PantallaLogin';
import { SelectorProyecto } from './SelectorProyecto';
import { SesionProvider, useSesion } from './SesionProvider';

/**
 * El cambio de clave obligatorio sigue siendo una pantalla PHP (S01 no la ha
 * migrado). En vez de renderizar un shell vacío mientras tanto, sale de la
 * SPA de inmediato — nunca deja a la vista un estado operativo que no aplica.
 */
function RedireccionCambioClave() {
  useEffect(() => {
    window.location.href = '/login';
  }, []);

  return <p role="status">Redirigiendo…</p>;
}

/**
 * Punto de entrada de la SPA (spec T01 §6: `SesionProvider` envuelve
 * `AuthOutlet`/`ProjectPicker`/`AppShell`). El árbol completo de rutas vive
 * dentro del Provider para que cualquier descendiente —incluidos los módulos
 * que las Tareas 3–10 cuelguen del outlet— consuma `useSesion()` del mismo
 * Context en vez de volver a resolver sesión por su cuenta.
 */
export function Rutas() {
  return (
    <SesionProvider>
      <RutasSegunSesion />
    </SesionProvider>
  );
}

/**
 * Deriva su UI de las siete pantallas de arranque (spec T01 §7) que expone
 * `useSesion` desde el `SesionProvider` que la envuelve. No conserva ninguna
 * pantalla anterior mientras `recargar()` vuelve a resolver sesión o
 * proyecto: cada estado se pinta desde cero.
 */
function RutasSegunSesion() {
  const { estado, arranque, autenticado, recargar, cerrarSesion, logoutSinConfirmar, generacion } = useSesion();

  switch (estado) {
    case 'cargando':
      return <p role="status">Cargando…</p>;

    case 'error_recuperable':
      return (
        <section role="alert">
          <p>
            {logoutSinConfirmar
              ? 'Intentamos cerrar tu sesión pero no pudimos confirmarlo con el servidor. Revisa tu conexión e inténtalo de nuevo.'
              : 'No pudimos conectar con la aplicación. Inténtalo de nuevo.'}
          </p>
          <button type="button" onClick={() => void recargar()}>
            Reintentar
          </button>
        </section>
      );

    case 'cambio_clave_requerido':
      return <RedireccionCambioClave />;

    // `anonimo` y `expirado` comparten pantalla: la Tarea 1 de S01 es la que
    // distingue el aviso de "sesión vencida" del login limpio; aquí ambas
    // vuelven al mismo punto de entrada sin arrastrar estado del proyecto.
    case 'anonimo':
    case 'expirado':
      return <PantallaLogin alEntrar={recargar} csrfToken={arranque?.csrfToken ?? ''} />;

    case 'autenticado_sin_proyecto':
      return <SelectorProyecto alElegir={recargar} csrfToken={autenticado?.csrfToken ?? ''} />;

    case 'listo':
      if (!autenticado || !autenticado.project) {
        return null;
      }

      // `AppShell` es la única raíz de rutas cliente: los módulos de S01-S27 cuelgan de su
      // `Outlet` como rutas hijas (Tarea 4, checkpoint T01 "un solo contrato de shell/outlet,
      // ninguna superficie migrada todavía" — de ahí que hoy no haya ninguna `<Route>` hija).
      return (
        <BrowserRouter>
          <Routes>
            <Route
              element={<AppShell cerrarSesion={cerrarSesion} generacionSesion={generacion} recargar={recargar} sesion={autenticado} />}
              path="*"
            />
          </Routes>
        </BrowserRouter>
      );
  }
}
