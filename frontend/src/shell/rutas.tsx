import { type ReactNode, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { BrowserRouter, Navigate, Route, Routes, useLocation, useNavigate } from 'react-router-dom';
import type { ListaProyectos } from '../lib/api/esquemas/proyectos';
import type { ConfiguracionRuntime } from '../lib/runtime/configuracion';
import { AppShell } from './AppShell';
import { CambioClaveObligatorio } from './auth/CambioClaveObligatorio';
import { limpiarParametrosAviso, resolverAvisoAcceso } from './auth/avisos';
import { PantallaLogin } from './auth/PantallaLogin';
import { PantallaRecuperarClave } from './auth/PantallaRecuperarClave';
import { PantallaRestablecerClave } from './auth/PantallaRestablecerClave';
import { leerTokenReset } from './auth/tokenReset';
import { BarraLateral } from './navegacion/BarraLateral';
import { navegacionSelectorProyectos } from './proyectos/NavegacionSelectorProyectos';
import { SelectorProyectos } from './proyectos/SelectorProyectos';
import { SesionProvider, useSesion } from './SesionProvider';

const CONFIGURACION_APLICACION_POR_DEFECTO: ConfiguracionRuntime = { mode: 'application' };

/**
 * Punto de entrada de la SPA (spec T01 §6: `SesionProvider` envuelve
 * `AuthOutlet`/`ProjectPicker`/`AppShell`). El árbol completo de rutas vive
 * dentro del Provider para que cualquier descendiente —incluidos los módulos
 * que las Tareas 3–10 cuelguen del outlet— consuma `useSesion()` del mismo
 * Context en vez de volver a resolver sesión por su cuenta.
 *
 * `configuracionRuntime` (Tarea 12, S01) es lo que `App.tsx` lee de
 * `#aia-runtime-config` — nunca resuelto aquí. Con `mode: 'maintenance'` esta
 * función bifurca ANTES de montar `SesionProvider`: el propósito entero del
 * host oculto es no depender de `/api/session`, que exige una app completa.
 * Con `mode: 'invalid'` (JSON corrupto) se ofrece una pantalla recuperable en
 * vez de arrancar como si nada se hubiera inyectado.
 */
export function Rutas({ configuracionRuntime = CONFIGURACION_APLICACION_POR_DEFECTO }: { configuracionRuntime?: ConfiguracionRuntime }) {
  if (configuracionRuntime.mode === 'invalid') {
    return <PantallaConfiguracionInvalida />;
  }

  if (configuracionRuntime.mode === 'maintenance') {
    return <RutaMantenimiento configuracion={configuracionRuntime} />;
  }

  // Un único `BrowserRouter` para toda la rama de aplicación (S02, Tarea 4). Vive aquí y no en
  // `App` porque `Rutas` se monta sola en las pruebas y `AppShell` necesita un router alrededor.
  // Las rutas públicas de acceso se resuelven ANTES de la máquina de estados por sesión: en
  // `/password/forgot` (S02) y `/password/reset` (S03) la pantalla pública se ve igual con sesión
  // anónima, pendiente de cambio de clave o autenticada. Todo lo demás cae en `RutasSegunSesion`, sin cambios de orden.
  return (
    <BrowserRouter>
      <SesionProvider>
        <Routes>
          <Route element={<RutaRecuperacion />} path="/password/forgot" />
          <Route element={<RutaRecuperacion />} path="/app/password/forgot" />
          <Route element={<RutaRestablecimiento />} path="/password/reset" />
          <Route element={<RutaRestablecimiento />} path="/app/password/reset" />
          <Route element={<RutaProyectos />} path="/app/proyectos" />
          <Route element={<RutaProyectos />} path="/proyectos" />
          <Route element={<RutasSegunSesion />} path="*" />
        </Routes>
      </SesionProvider>
    </BrowserRouter>
  );
}

/**
 * Pantalla de arranque sin resolver o con fallo técnico: compartida por la ruta pública de
 * recuperación y por la máquina de estados, para que ambas digan lo mismo.
 */
function ErrorArranqueRecuperable({ logoutSinConfirmar, recargar }: { logoutSinConfirmar: boolean; recargar: () => Promise<void> }) {
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
}

type PropsPantallaPublica = { csrfToken: string; alRevalidar: () => Promise<void> };

/**
 * Marco común de las rutas públicas de acceso: recuperación (S02) y restablecimiento (S03).
 * Nació como `RutaRecuperacion` y se generalizó en S03 (Tarea 4) para que ambas compartan el
 * mismo comportamiento de arranque y revalidación en vez de duplicarlo. Espera el bootstrap solo para obtener el token
 * CSRF; una vez resuelto, ignora el estado de sesión (anónimo, cambio de clave pendiente o
 * autenticado) y pinta la pantalla que le pasa `pintar`.
 *
 * **Ronda de arreglo 1 (S02-UX-06):** "Cargando…" solo se pinta en la carga INICIAL, antes de
 * que exista algún bootstrap. `alRevalidar` (el botón "Actualizar sesión" tras un 403
 * `csrf_invalid`) es `recargar()`, que pone `estado` en `cargando` y `arranque` en `null`
 * mientras pide un bootstrap nuevo (`SesionProvider.recargar`) — sin este resguardo, esa
 * revalidación desmontaba la pantalla pública y perdía el correo tecleado y el foco
 * (reproducido en `.superpowers/sdd/2026-08-30-s02-recuperar-clave-react/revisor-403.mjs`,
 * cubierto en `rutas.test.tsx`). `huboArranquePrevio` distingue "nunca hubo sesión" (sí muestra
 * "Cargando…", como ya prueba el escenario de bootstrap en vuelo/fallido de más abajo) de "ya
 * hubo una, se está revalidando" (se queda montada la pantalla). `csrfDeUltimoArranque` conserva
 * el último token conocido durante ese hueco, en vez de mandar uno vacío mientras se resuelve.
 *
 * **Ola final de la revisión S02:** la revalidación también puede FALLAR (`/api/session` en 500).
 * Antes, ese `error_recuperable` reemplazaba la pantalla por `ErrorArranqueRecuperable` y se
 * perdía el correo. Ahora, si ya hubo un arranque, la pantalla sigue montada y `alRevalidar`
 * **rechaza**, para que la pantalla cuente el fallo dentro («No pudimos actualizar la
 * sesión») y deje el foco en «Actualizar sesión». `recargar()` no lanza ni devuelve el resultado,
 * así que la promesa se liquida desde un efecto cuando la sesión sale de `cargando`: leer `estado`
 * justo después de `await recargar()` daría el valor viejo. `generacion` va en las dependencias
 * por si React agrupa `cargando` y el resultado final en un solo commit.
 */
function RutaPublicaAcceso({ pintar }: { pintar: (props: PropsPantallaPublica) => ReactNode }) {
  const { estado, arranque, recargar, logoutSinConfirmar, generacion } = useSesion();
  const huboArranquePrevio = useRef(false);
  const csrfDeUltimoArranque = useRef('');
  const revalidacionPendiente = useRef<{ resolver: () => void; rechazar: (causa: Error) => void } | null>(null);

  useEffect(() => {
    const pendiente = revalidacionPendiente.current;
    if (!pendiente || estado === 'cargando') return;

    revalidacionPendiente.current = null;
    if (estado === 'error_recuperable') {
      pendiente.rechazar(new Error('No se pudo revalidar la sesión'));
    } else {
      pendiente.resolver();
    }
  }, [estado, generacion]);

  const alRevalidar = useCallback(
    () =>
      new Promise<void>((resolver, rechazar) => {
        revalidacionPendiente.current = { resolver, rechazar };
        void recargar();
      }),
    [recargar],
  );

  if (arranque) {
    huboArranquePrevio.current = true;
    csrfDeUltimoArranque.current = arranque.csrfToken;
  }

  if (estado === 'cargando' && !huboArranquePrevio.current) {
    return <p role="status">Cargando…</p>;
  }

  if (estado === 'error_recuperable' && !huboArranquePrevio.current) {
    return <ErrorArranqueRecuperable logoutSinConfirmar={logoutSinConfirmar} recargar={recargar} />;
  }

  return pintar({ csrfToken: arranque?.csrfToken ?? csrfDeUltimoArranque.current, alRevalidar });
}

function RutaRecuperacion() {
  return <RutaPublicaAcceso pintar={(props) => <PantallaRecuperarClave {...props} />} />;
}

/**
 * Ruta pública de restablecimiento (S03). El token se lee de la query UNA vez por `search`
 * (`leerTokenReset`, estricto) y solo viaja por props a la pantalla: nunca a contexto, estado
 * global, logs ni DOM. La pantalla recibe `csrfToken`, `enlace`, `alRevalidar` y `alCompletar`; jamás
 * usuario ni proyecto.
 */
function RutaRestablecimiento() {
  const { search } = useLocation();
  const navigate = useNavigate();
  const enlace = useMemo(() => leerTokenReset(search), [search]);
  // Tarea 7: `replace` saca del historial la URL con el token; el aviso `reset=1` lo pinta el
  // login de S01. La pantalla solo llama esto con la ruta segura fija (`RUTA_EXITO`).
  const alCompletar = useCallback((ruta: string) => navigate(ruta, { replace: true }), [navigate]);
  return (
    <RutaPublicaAcceso
      pintar={(props) => <PantallaRestablecerClave enlace={enlace} alCompletar={alCompletar} {...props} />}
    />
  );
}

/**
 * `#aia-runtime-config` existe pero no cumple el contrato — nunca ocurre en producción salvo
 * un bug del propio inyector. Recuperable con una recarga: no hay estado de sesión que perder
 * porque este camino nunca llegó a pedir `/api/session`.
 */
function PantallaConfiguracionInvalida() {
  return (
    <section role="alert">
      <p>No pudimos cargar la configuración de la página. Inténtalo de nuevo.</p>
      <button type="button" onClick={() => window.location.reload()}>
        Reintentar
      </button>
    </section>
  );
}

type ConfiguracionMantenimiento = Extract<ConfiguracionRuntime, { mode: 'maintenance' }>;

/**
 * Árbol del host oculto de mantenimiento. Deliberadamente fuera de `SesionProvider`: ese
 * Provider dispara `/api/session` al montar, y el punto entero de esta rama es no tocar esa
 * ruta (Tarea 5: bloqueada por `MaintenanceMode` salvo `maintenance_bypass`). Todo lo que
 * necesita esta pantalla ya vino inyectado por `MaintenanceLoginController::show()`.
 */
function RutaMantenimiento({ configuracion }: { configuracion: ConfiguracionMantenimiento }) {
  if (configuracion.state === 'password_change_required') {
    return (
      <CambioClaveObligatorio
        csrfToken={configuracion.csrfToken}
        alCompletar={async () => {
          window.location.assign('/proyectos');
        }}
        alSalir={async () => {
          window.location.assign(configuracion.action);
        }}
      />
    );
  }

  return (
    <PantallaLogin
      csrfToken={configuracion.csrfToken}
      aviso={null}
      modo={{
        tipo: 'mantenimiento',
        action: configuracion.action,
        error: configuracion.error,
        csrfToken: configuracion.csrfToken,
      }}
      alRevalidar={async () => {}}
      alResolver={async () => {}}
    />
  );
}

/**
 * Pantalla standalone `/app/proyectos` y `/proyectos` (Tarea 7, S04): rail genérico
 * (`BarraLateral` + `navegacionSelectorProyectos`) como hermano de `SelectorProyectos`, fuera de
 * `AppShell` — no hay un proyecto activo del que colgar el rail T01 completo.
 *
 * **T7-2 (decisión del coordinador):** el plan original solo pedía `estado==='listo'` o
 * `'autenticado_sin_proyecto'`; con eso una sesión con cambio de clave pendiente vería el
 * selector en vez del panel obligatorio. La guarda correcta es "autenticado, sin importar si ya
 * tiene proyecto" — que es justo lo que NO cubren los otros cinco estados de `useSesion()` — así
 * que delegar en `RutasSegunSesion` para cualquier otro estado reutiliza sus ramas de
 * `cargando`/`error_recuperable`/`cambio_clave_requerido`/`anonimo`/`expirado` sin duplicarlas.
 */
function RutaProyectos() {
  const { estado, autenticado, recargar, cerrarSesion } = useSesion();
  const [navegacion, setNavegacion] = useState<ListaProyectos['navigation'] | null>(null);

  if (estado !== 'autenticado_sin_proyecto' && estado !== 'listo') {
    return <RutasSegunSesion />;
  }

  if (!autenticado) return null;

  return (
    <>
      <BarraLateral
        activeId="projects"
        accountName={autenticado.user.displayName}
        groups={navegacionSelectorProyectos(navegacion)}
        showChangeProject={false}
        cuentaPropia
        cerrarSesion={cerrarSesion}
      />
      <SelectorProyectos
        session={{ csrfToken: autenticado.csrfToken, project: autenticado.project }}
        onOpen={(route) => window.location.assign(route)}
        onRevalidate={recargar}
        onNavigation={setNavegacion}
      />
    </>
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
  //
  // Tarea 14: `cargando` se ignora en vez de tratarse como "cualquier otro estado". `recargar()`
  // hace `setCargando(true)` + `setArranque(null)` ANTES de pedir el bootstrap nuevo, así que
  // entre el cambio de clave y el login hay siempre un commit intermedio. Medido en navegador
  // con un observador de mutaciones sobre `#root`:
  //   h1=Actualiza tu contraseña → h1=null status=Cargando… → h1=Bienvenido a Last Planner AIA (foco en BODY)
  // Con la versión anterior ese commit intermedio entraba por la última línea y ponía el ref en
  // `false` (porque `cargando` !== `cambio_clave_requerido`), así que al llegar `anonimo` la
  // condición ya no se cumplía y el foco NUNCA volvía al campo de usuario. Ninguna prueba lo
  // cubría: `rutas.test.tsx` no menciona foco ni cancelación.
  useEffect(() => {
    if (estado === 'cambio_clave_requerido') {
      veniaDeCambioClaveRef.current = true;
      return;
    }

    // Estado transitorio de `recargar()`: no confirma ni desmiente de dónde se viene.
    if (estado === 'cargando') return;

    if (veniaDeCambioClaveRef.current && estado === 'anonimo') {
      document.getElementById('usuario')?.focus();
    }
    veniaDeCambioClaveRef.current = false;
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
      return <ErrorArranqueRecuperable logoutSinConfirmar={logoutSinConfirmar} recargar={recargar} />;

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

    // Tarea 7, S04: la pantalla ya no se pinta aquí — vive en `RutaProyectos`
    // (`/app/proyectos`/`/proyectos`), con su propio rail. Una sesión sin proyecto en
    // cualquier otro path se redirige al alias piloto (`/app/proyectos` sirve por prefijo
    // migrado de `SpaRouter`; `/proyectos` a secas no, hasta la Tarea 10).
    case 'autenticado_sin_proyecto':
      return <Navigate replace to="/app/proyectos" />;

    case 'listo':
      if (!autenticado || !autenticado.project) {
        return null;
      }

      // `AppShell` es la única raíz de rutas cliente: los módulos de S01-S27 cuelgan de su
      // `Outlet` como rutas hijas (Tarea 4, checkpoint T01 "un solo contrato de shell/outlet,
      // ninguna superficie migrada todavía" — de ahí que hoy no haya ninguna `<Route>` hija).
      // El router es el único `BrowserRouter` que monta `Rutas` (S02, Tarea 4): aquí solo se
      // declaran rutas descendientes bajo el `path="*"` de arriba, nunca un segundo router.
      return (
        <Routes>
          <Route
            element={<AppShell cerrarSesion={cerrarSesion} generacionSesion={generacion} recargar={recargar} sesion={autenticado} />}
            path="*"
          />
        </Routes>
      );
  }
}
