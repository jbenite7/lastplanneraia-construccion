import { useEffect, useRef, useState } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { AppShell } from './AppShell';
import { CambioClaveObligatorio } from './auth/CambioClaveObligatorio';
import { limpiarParametrosAviso, resolverAvisoAcceso } from './auth/avisos';
import { PantallaLogin } from './auth/PantallaLogin';
import { SelectorProyecto } from './SelectorProyecto';
import { SesionProvider, useSesion } from './SesionProvider';

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

  // Se congela en el PRIMER render, antes de que el efecto de abajo limpie la URL — `estado`
  // arranca en `cargando` y solo pasa a `anonimo`/`expirado` de forma asíncrona (tras resolver
  // `/api/session`), así que sin esta captura el efecto de limpieza (que sí corre desde el primer
  // commit) borraría `reset=1`/`timeout=1`/`inactive=1` antes de que la rama de login llegara a
  // leerlos: el aviso nunca se veía. Verificado en el navegador integrado — `/app?reset=1` no
  // mostraba nada hasta este fix.
  //
  // Ronda de arreglos 1: congelarla PARA SIEMPRE (con `useState` sin setter) reabría el problema
  // por otro lado — `RutasSegunSesion` nunca se desmonta (`cerrarSesion` recarga el bootstrap sin
  // recargar la página), así que un logout voluntario vuelve a `anonimo` con `reason=missing_session`
  // en el MISMO montaje, `resolverAvisoAcceso` cae a mirar la query (a propósito, ver `avisos.ts`)
  // y la copia congelada seguía diciendo `?reset=1` horas después de que nadie hubiera restablecido
  // nada. Por eso ahora es estado mutable: se lee para pintar el aviso mientras la pantalla de
  // acceso está en pantalla, y se invalida (abajo) en cuanto se sale de ella — así sobrevive la
  // carrera del montaje inicial sin sobrevivir un ciclo completo de login/logout.
  const [searchAviso, setSearchAviso] = useState(() => window.location.search);
  const enPantallaAcceso = estado === 'anonimo' || estado === 'expirado';
  const estabaEnPantallaAccesoRef = useRef(false);
  const veniaDeCambioClaveRef = useRef(false);

  // Tras una cancelación confirmada (Tarea 9), `recargar()` deja `estado` en `anonimo` con la
  // sesión pendiente ya destruida por el servidor — el foco vuelve al campo de usuario del login,
  // nunca al propio botón que disparó la cancelación (ya no existe). Va en un efecto, no en el
  // `.then()` de `alSalir`, porque el `<input id="usuario">` de `PantallaLogin` todavía no existe
  // en el DOM en el instante en que esa promesa resuelve — el commit de React llega después.
  useEffect(() => {
    if (veniaDeCambioClaveRef.current && estado === 'anonimo') {
      document.getElementById('usuario')?.focus();
    }
    veniaDeCambioClaveRef.current = estado === 'cambio_clave_requerido';
  }, [estado]);

  // Los avisos consumibles por query (`reset=1`, legacy `timeout=1|inactive=1`) sobreviven un
  // recargo o un "atrás" del navegador porque viven en la URL, a diferencia de los que trae la
  // razón de servidor (ver `avisos.ts`) — así que se consumen una sola vez limpiando la URL al
  // montar, sin esperar a que se muestre ningún aviso en particular.
  useEffect(() => {
    const limpia = limpiarParametrosAviso(window.location.href);
    if (limpia !== window.location.href) {
      window.history.replaceState(null, '', limpia);
    }
  }, []);

  // Invalida `searchAviso` en cuanto se SALE de la pantalla de acceso (login exitoso, redirección
  // a cambio de clave, etc.) — no antes, porque mientras el usuario sigue viendo esa pantalla
  // (reintentos de credenciales incluidos) el aviso debe seguir visible sin parpadear. Si más
  // tarde se vuelve a `anonimo`/`expirado` en el mismo montaje (logout sin recargar la página), la
  // query ya vale `''` y `resolverAvisoAcceso` no tiene nada que mostrar salvo que el servidor dé
  // una razón nueva de verdad (timeout/inactive/stale_session reales).
  useEffect(() => {
    if (estabaEnPantallaAccesoRef.current && !enPantallaAcceso) {
      setSearchAviso('');
    }
    estabaEnPantallaAccesoRef.current = enPantallaAcceso;
  }, [enPantallaAcceso]);

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
      return (
        <CambioClaveObligatorio
          csrfToken={arranque?.state === 'password_change_required' ? arranque.csrfToken : ''}
          alCompletar={recargar}
          alSalir={recargar}
        />
      );

    // `anonimo` y `expirado` comparten pantalla: la Tarea 1 de S01 es la que
    // distingue el aviso de "sesión vencida" del login limpio; aquí ambas
    // vuelven al mismo punto de entrada sin arrastrar estado del proyecto.
    case 'anonimo':
    case 'expirado':
      return (
        <PantallaLogin
          csrfToken={arranque?.csrfToken ?? ''}
          aviso={resolverAvisoAcceso(
            arranque?.state === 'anonymous' ? arranque.reason : null,
            searchAviso,
          )}
          modo={{ tipo: 'normal' }}
          alRevalidar={recargar}
          alResolver={async () => {
            // `next` no cambia la acción: en los dos casos ('projects' y 'password_change')
            // el siguiente bootstrap ya trae el `state` correcto — `RutasSegunSesion` deriva
            // la pantalla (Tarea 9: cambio de clave obligatorio) de ese `state`, no de `next`.
            await recargar();
          }}
        />
      );

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
