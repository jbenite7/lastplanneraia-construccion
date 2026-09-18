import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { App } from '../App';
import { Rutas } from './rutas';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function responderSesion(cuerpo: unknown) {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(cuerpo), { status: 200 }),
  ));
}

/**
 * Como `responderSesion`, pero discrimina por URL: `/api/session` responde `cuerpoSesion` y
 * `/api/auth/password/reset/validate` responde `cuerpoValidacion` (S03, Tarea 5) — desde que
 * `PantallaRestablecerClave` llama a `validarEnlaceReset` de verdad, un solo cuerpo para toda
 * llamada ya no alcanza.
 */
function responderSesionYValidacion(cuerpoSesion: unknown, cuerpoValidacion: unknown) {
  vi.stubGlobal(
    'fetch',
    vi.fn((input: RequestInfo | URL) => {
      const url = String(input);
      if (url.includes('/api/auth/password/reset/validate')) {
        return Promise.resolve(new Response(JSON.stringify(cuerpoValidacion), { status: 200 }));
      }
      return Promise.resolve(new Response(JSON.stringify(cuerpoSesion), { status: 200 }));
    }),
  );
}

afterEach(() => {
  vi.unstubAllGlobals();
  window.history.pushState({}, '', '/');
});

test('sin sesión (anonymous/missing_session) muestra el login', async () => {
  responderSesion({
    state: 'anonymous',
    authenticated: false,
    reason: 'missing_session',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument());
});

test('una sesión expirada (timeout) también vuelve al login, no a una pantalla operativa', async () => {
  responderSesion({
    state: 'anonymous',
    authenticated: false,
    reason: 'timeout',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument());
});

test('un bootstrap con cambio de clave pendiente muestra el panel, sin login, selector, sidebar ni identidad', async () => {
  responderSesion({
    state: 'password_change_required',
    authenticated: false,
    reason: null,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  });

  render(<Rutas />);

  expect(await screen.findByRole('button', { name: 'Actualizar y continuar' })).toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: /proyecto/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
  expect(screen.queryByLabelText('Usuario')).not.toBeInTheDocument();
});

test('con sesión pero sin proyecto muestra el selector', async () => {
  responderSesion({
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: null,
    capabilities: { canManageWeeks: true },
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('heading', { name: /proyecto/i })).toBeInTheDocument());
});

test('con sesión y proyecto muestra la aplicación', async () => {
  responderSesion({
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto', area: 'Construccion' },
    capabilities: { canManageWeeks: true },
    navigation: { bi: { visible: false, href: null }, groups: [] },
    week: { current: 6, options: [{ number: 6, startsOn: "2026-08-24", endsOn: "2026-08-30" }], actions: { select: true, create: true, deleteLast: true } },
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('navigation')).toBeInTheDocument());
});

test('un error de sesión muestra alerta y permite reintentar sin mostrar el login', async () => {
  vi.stubGlobal('fetch', vi.fn()
    .mockRejectedValueOnce(new Error('red no disponible'))
    .mockReturnValueOnce(new Promise<Response>(() => {})));

  render(<Rutas />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();

  fireEvent.click(screen.getByRole('button', { name: /reintentar/i }));

  expect(await screen.findByRole('status')).toHaveTextContent(/cargando/i);
});

test('un contrato roto (JSON malformado) cae en el mismo estado recuperable que un fallo de red', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{"roto":', { status: 200 })));

  render(<Rutas />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
});

test('un 5xx en el bootstrap cae en el estado recuperable, nunca en el login por descarte', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ error: { codigo: 'INTERNAL', mensaje: 'falla interna' } }), { status: 500 }),
  ));

  render(<Rutas />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
});

test('mientras el bootstrap está en vuelo se ve "Cargando…", nunca el login por descarte', async () => {
  let resolverSesion: (respuesta: Response) => void = () => {};
  vi.stubGlobal('fetch', vi.fn().mockReturnValue(
    new Promise<Response>((resolve) => {
      resolverSesion = resolve;
    }),
  ));

  render(<Rutas />);

  expect(screen.getByRole('status')).toHaveTextContent(/cargando/i);
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();

  resolverSesion(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
  await waitFor(() => expect(screen.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument());
});

// --- avisos consumibles una vez (ronda de arreglos 1) --------------------------

const ANONIMA_MISSING_SESSION = {
  state: 'anonymous',
  authenticated: false,
  reason: 'missing_session',
  user: null,
  project: null,
  capabilities: {},
  navigation: { bi: null, groups: [] },
  week: null,
  csrfToken,
};

const AUTENTICADA_CON_PROYECTO = {
  state: 'authenticated',
  authenticated: true,
  reason: null,
  user: { username: 'test.A', displayName: 'Ana', role: 'A' },
  project: { id: 1, name: 'Da Porto', area: 'Construccion' },
  capabilities: { canManageWeeks: true },
  navigation: { bi: { visible: false, href: null }, groups: [] },
  week: { current: 6, options: [{ number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' }], actions: { select: true, create: true, deleteLast: true } },
  csrfToken,
};

test('/app?reset=1 muestra el aviso una vez y limpia la URL', async () => {
  window.history.pushState({}, '', '/app?reset=1');
  responderSesion(ANONIMA_MISSING_SESSION);

  render(<Rutas />);

  await screen.findByRole('heading', { name: /bienvenido a last planner aia/i });
  expect(await screen.findByText(/restablecida correctamente/i)).toBeInTheDocument();
  await waitFor(() => expect(window.location.search).toBe(''));
});

test('tras un ciclo de logout en el mismo montaje, el aviso de reset ya consumido no reaparece', async () => {
  window.history.pushState({}, '', '/app?reset=1');

  let autenticado = false;

  const fetchFalso = vi.fn(async (entrada: RequestInfo | URL, opciones?: RequestInit) => {
    const ruta = typeof entrada === 'string' ? entrada : entrada.toString();
    const metodo = opciones?.method ?? 'GET';

    if (ruta === '/api/session') {
      return new Response(JSON.stringify(autenticado ? AUTENTICADA_CON_PROYECTO : ANONIMA_MISSING_SESSION), {
        status: 200,
      });
    }
    if (ruta === '/api/auth/login' && metodo === 'POST') {
      autenticado = true;
      return new Response(JSON.stringify({ success: true, next: 'projects', message: null }), { status: 200 });
    }
    if (ruta === '/api/auth/logout' && metodo === 'POST') {
      autenticado = false;
      return new Response(JSON.stringify({ success: true }), { status: 200 });
    }

    // Peticiones de fondo ajenas a este escenario (notificaciones, semana, etc.): no participan
    // en la aserción y no deben hacer fallar el montaje.
    return new Response(JSON.stringify({}), { status: 200 });
  });
  vi.stubGlobal('fetch', fetchFalso);

  const usuario = userEvent.setup();
  render(<Rutas />);

  // 1) primera visita: el aviso de reset aparece y la URL se limpia.
  await screen.findByRole('heading', { name: /bienvenido a last planner aia/i });
  expect(await screen.findByText(/restablecida correctamente/i)).toBeInTheDocument();
  await waitFor(() => expect(window.location.search).toBe(''));

  // 2) login exitoso.
  await usuario.type(screen.getByLabelText('Usuario'), 'test.A');
  await usuario.type(screen.getByLabelText('Contraseña'), 'clave-valida');
  await usuario.click(screen.getByRole('button', { name: 'Entrar' }));
  await waitFor(() => expect(screen.getByRole('navigation')).toBeInTheDocument());

  // 3) logout desde el menú de cuenta, sin recargar la página (mismo montaje de `Rutas`).
  await usuario.click(screen.getByRole('button', { name: /cuenta/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cerrar sesión/i }));

  // 4) de vuelta al login: el aviso de reset, ya consumido en el paso 1, no reaparece.
  await waitFor(() => expect(screen.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument());
  expect(screen.queryByRole('status')).not.toBeInTheDocument();
});

// --- riesgo capital: login exitoso + fallo de arranque posterior --------------

test('login exitoso seguido de un fallo de arranque muestra el error recuperable, sin reenviar credenciales', async () => {
  let intentosSesion = 0;
  const loginFalso = vi.fn(async () =>
    new Response(JSON.stringify({ success: true, next: 'projects', message: null }), { status: 200 }),
  );

  const fetchFalso = vi.fn(async (entrada: RequestInfo | URL, opciones?: RequestInit) => {
    const ruta = typeof entrada === 'string' ? entrada : entrada.toString();
    const metodo = opciones?.method ?? 'GET';

    if (ruta === '/api/session') {
      intentosSesion += 1;
      if (intentosSesion === 1) {
        return new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 });
      }
      // El bootstrap posterior al login (Tarea 9: `alResolver` llama `recargar()`) falla —
      // simula una caída técnica de `/api/session` justo después de un login exitoso.
      return new Response(
        JSON.stringify({ error: { codigo: 'INTERNAL', mensaje: 'falla interna' } }),
        { status: 500 },
      );
    }
    if (ruta === '/api/auth/login' && metodo === 'POST') {
      return loginFalso();
    }

    return new Response(JSON.stringify({}), { status: 200 });
  });
  vi.stubGlobal('fetch', fetchFalso);

  const usuario = userEvent.setup();
  render(<Rutas />);

  await screen.findByRole('heading', { name: /bienvenido a last planner aia/i });

  await usuario.type(screen.getByLabelText('Usuario'), 'test.A');
  await usuario.type(screen.getByLabelText('Contraseña'), 'clave-valida');
  await usuario.click(screen.getByRole('button', { name: 'Entrar' }));

  // El fallo del bootstrap posterior al login se ve como error recuperable, nunca como el
  // formulario de login "por descarte" — el riesgo capital de esta tarea.
  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();

  // Ni la propia recuperación (botón "Reintentar") ni ningún otro camino reenvía credenciales:
  // `alResolver`/`recargar` solo hablan con `GET /api/session`.
  expect(loginFalso).toHaveBeenCalledOnce();
  expect(fetchFalso).toHaveBeenCalledWith('/api/auth/login', expect.objectContaining({ method: 'POST' }));
});

// --- host oculto de mantenimiento (Tarea 12, S01) ------------------------------

test('con configuracionRuntime de mantenimiento (anonymous) se ve el login sin llamar a /api/session', async () => {
  const fetchEspia = vi.fn();
  vi.stubGlobal('fetch', fetchEspia);

  render(
    <Rutas
      configuracionRuntime={{
        mode: 'maintenance',
        action: '/_aia/host-oculto',
        error: false,
        state: 'anonymous',
        csrfToken,
      }}
    />,
  );

  expect(await screen.findByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument();
  expect(fetchEspia).not.toHaveBeenCalled();

  const formulario = screen.getByRole('button', { name: 'Entrar' }).closest('form');
  expect(formulario).toHaveAttribute('action', '/_aia/host-oculto');
  expect(formulario).toHaveAttribute('method', 'post');
});

test('con configuracionRuntime de mantenimiento y error=true se ve el rechazo genérico', async () => {
  vi.stubGlobal('fetch', vi.fn());

  render(
    <Rutas
      configuracionRuntime={{
        mode: 'maintenance',
        action: '/_aia/host-oculto',
        error: true,
        state: 'anonymous',
        csrfToken,
      }}
    />,
  );

  expect(await screen.findByRole('alert')).toHaveTextContent(/usuario o contraseña incorrectos/i);
});

test('con configuracionRuntime de mantenimiento (password_change_required) se ve el cambio de clave, sin /api/session', async () => {
  const fetchEspia = vi.fn();
  vi.stubGlobal('fetch', fetchEspia);

  render(
    <Rutas
      configuracionRuntime={{
        mode: 'maintenance',
        action: '/_aia/host-oculto',
        error: false,
        state: 'password_change_required',
        csrfToken,
      }}
    />,
  );

  expect(await screen.findByRole('button', { name: 'Actualizar y continuar' })).toBeInTheDocument();
  expect(fetchEspia).not.toHaveBeenCalledWith('/api/session', expect.anything());
});

test('con configuracionRuntime inválida se ve una alerta recuperable, sin llamar a /api/session', async () => {
  const fetchEspia = vi.fn();
  vi.stubGlobal('fetch', fetchEspia);

  render(<Rutas configuracionRuntime={{ mode: 'invalid' }} />);

  expect(await screen.findByRole('alert')).toBeInTheDocument();
  expect(fetchEspia).not.toHaveBeenCalled();
});

// --- S02: la ruta pública de recuperación prevalece sobre el estado de sesión (Tarea 4) ----

const PENDIENTE_CAMBIO_CLAVE = {
  state: 'password_change_required',
  authenticated: false,
  reason: null,
  user: null,
  project: null,
  capabilities: {},
  navigation: { bi: null, groups: [] },
  week: null,
  csrfToken,
};

const AUTENTICADA_SIN_PROYECTO = { ...AUTENTICADA_CON_PROYECTO, project: null, week: null };

test.each(['/password/forgot', '/app/password/forgot'])('%s muestra la recuperación de clave', async (ruta) => {
  window.history.pushState({}, '', ruta);
  responderSesion(ANONIMA_MISSING_SESSION);

  render(<Rutas />);

  expect(await screen.findByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
});

test.each([
  ['anónima', ANONIMA_MISSING_SESSION],
  ['con cambio de clave pendiente', PENDIENTE_CAMBIO_CLAVE],
  ['autenticada sin proyecto', AUTENTICADA_SIN_PROYECTO],
  ['autenticada con proyecto', AUTENTICADA_CON_PROYECTO],
])('la recuperación prevalece sobre una sesión %s', async (_nombre, cuerpo) => {
  window.history.pushState({}, '', '/password/forgot');
  responderSesion(cuerpo);

  render(<Rutas />);

  expect(await screen.findByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('button', { name: 'Actualizar y continuar' })).not.toBeInTheDocument();
  expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
});

test('en la ruta de recuperación, el bootstrap en vuelo muestra "Cargando…" y un fallo la alerta recuperable', async () => {
  window.history.pushState({}, '', '/password/forgot');
  vi.stubGlobal('fetch', vi.fn()
    .mockRejectedValueOnce(new Error('red no disponible'))
    .mockReturnValueOnce(new Promise<Response>(() => {})));

  render(<Rutas />);

  expect(screen.getByRole('status')).toHaveTextContent(/cargando/i);
  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByRole('heading', { name: 'Restablecer contraseña' })).not.toBeInTheDocument();

  fireEvent.click(screen.getByRole('button', { name: /reintentar/i }));
  expect(await screen.findByRole('status')).toHaveTextContent(/cargando/i);
});

test('403 csrf_invalid: "Actualizar sesión" revalida sin perder el correo ni desmontar la pantalla', async () => {
  // Ronda de arreglo 1 (Tarea 9, S02-UX-06): `alRevalidar` es `recargar()`, que pone la sesión en
  // `cargando` mientras pide un bootstrap nuevo. Antes de este fix, `RutaRecuperacion` trataba
  // CUALQUIER `cargando` como "sin sesión todavía" y desmontaba `PantallaRecuperarClave` en favor
  // de `<p role="status">Cargando…</p>` — perdiendo el correo tecleado y el foco (reproducido en
  // `.superpowers/sdd/2026-08-30-s02-recuperar-clave-react/revisor-403.mjs`).
  window.history.pushState({}, '', '/password/forgot');

  let resolverSegundaSesion: (respuesta: Response) => void = () => {};
  const segundaSesion = new Promise<Response>((resolve) => {
    resolverSegundaSesion = resolve;
  });
  let llamadasSesion = 0;

  vi.stubGlobal('fetch', vi.fn((input: RequestInfo | URL) => {
    const url = String(input);
    if (url.includes('/api/auth/password/forgot')) {
      return Promise.resolve(new Response(JSON.stringify({
        success: false,
        code: 'csrf_invalid',
        message: 'No fue posible validar la solicitud. Intenta nuevamente.',
        error: {
          codigo: 'csrf_invalid',
          mensaje: 'No fue posible validar la solicitud. Intenta nuevamente.',
        },
      }), { status: 403 }));
    }

    llamadasSesion += 1;
    if (llamadasSesion === 1) {
      return Promise.resolve(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
    }
    return segundaSesion;
  }));

  render(<Rutas />);

  expect(await screen.findByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();

  const campo = screen.getByLabelText('Correo electrónico') as HTMLInputElement;
  fireEvent.change(campo, { target: { value: 'persona@example.test' } });
  fireEvent.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  await screen.findByRole('button', { name: 'Actualizar sesión' });
  fireEvent.click(screen.getByRole('button', { name: 'Actualizar sesión' }));

  // Mientras la revalidación sigue en vuelo: la pantalla de recuperación sigue montada, con el
  // correo intacto — nunca "Cargando…" ni el login.
  expect(screen.getByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();
  expect(screen.queryByText(/cargando/i)).not.toBeInTheDocument();
  expect((screen.getByLabelText('Correo electrónico') as HTMLInputElement).value).toBe('persona@example.test');

  resolverSegundaSesion(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));

  await waitFor(() => expect(screen.getByLabelText('Correo electrónico')).toHaveFocus());
  expect((screen.getByLabelText('Correo electrónico') as HTMLInputElement).value).toBe('persona@example.test');
});

test('403 csrf_invalid: si la revalidación falla, la pantalla sigue montada con el correo y avisa dentro', async () => {
  // Ola final de la revisión S02: con `/api/session` en 500 durante "Actualizar sesión", la ruta
  // pública caía en `ErrorArranqueRecuperable` («No pudimos conectar con la aplicación») y se
  // perdía el correo. Tras un arranque previo, el fallo se cuenta dentro de la pantalla.
  window.history.pushState({}, '', '/password/forgot');
  let llamadasSesion = 0;

  vi.stubGlobal('fetch', vi.fn((input: RequestInfo | URL) => {
    const url = String(input);
    if (url.includes('/api/auth/password/forgot')) {
      return Promise.resolve(new Response(JSON.stringify({
        success: false,
        code: 'csrf_invalid',
        message: 'No fue posible validar la solicitud. Intenta nuevamente.',
        error: { codigo: 'csrf_invalid', mensaje: 'No fue posible validar la solicitud. Intenta nuevamente.' },
      }), { status: 403 }));
    }

    llamadasSesion += 1;
    if (llamadasSesion === 2) {
      return Promise.resolve(new Response('<h1>Error</h1>', { status: 500, headers: { 'Content-Type': 'text/html' } }));
    }
    return Promise.resolve(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
  }));

  render(<Rutas />);

  const campo = (await screen.findByLabelText('Correo electrónico')) as HTMLInputElement;
  fireEvent.change(campo, { target: { value: 'persona@example.test' } });
  fireEvent.click(screen.getByRole('button', { name: 'Enviar enlace' }));
  fireEvent.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  expect(await screen.findByText('No pudimos actualizar la sesión. Intenta nuevamente.')).toBeInTheDocument();
  expect(screen.queryByText(/no pudimos conectar con la aplicación/i)).not.toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();
  expect((screen.getByLabelText('Correo electrónico') as HTMLInputElement).value).toBe('persona@example.test');
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());

  // Un segundo intento con `/api/session` sano limpia el aviso y devuelve el foco al campo.
  fireEvent.click(screen.getByRole('button', { name: 'Actualizar sesión' }));
  await waitFor(() => expect(screen.queryByText('No pudimos actualizar la sesión. Intenta nuevamente.')).not.toBeInTheDocument());
  await waitFor(() => expect(screen.getByLabelText('Correo electrónico')).toHaveFocus());
  expect((screen.getByLabelText('Correo electrónico') as HTMLInputElement).value).toBe('persona@example.test');
  expect(llamadasSesion).toBe(3);
});

test('App monta la recuperación de clave en /password/forgot', async () => {
  window.history.pushState({}, '', '/password/forgot');
  responderSesion(AUTENTICADA_CON_PROYECTO);

  render(<App />);

  expect(await screen.findByRole('heading', { name: 'Restablecer contraseña' })).toBeInTheDocument();
});

// --- S03: la ruta pública de restablecimiento prevalece sobre el estado de sesión (Tarea 4) --

const TOKEN_RESET = 'a'.repeat(64);

function llamadasA(fragmento: string): number {
  const simulado = globalThis.fetch as unknown as { mock: { calls: unknown[][] } };
  return simulado.mock.calls.filter(([entrada]) => String(entrada).includes(fragmento)).length;
}

test.each([
  ['anónima', '/password/reset', ANONIMA_MISSING_SESSION],
  ['con cambio de clave pendiente', '/password/reset', PENDIENTE_CAMBIO_CLAVE],
  ['autenticada sin proyecto', '/password/reset', AUTENTICADA_SIN_PROYECTO],
  ['autenticada con proyecto', '/password/reset', AUTENTICADA_CON_PROYECTO],
  ['anónima', '/app/password/reset', ANONIMA_MISSING_SESSION],
  ['con cambio de clave pendiente', '/app/password/reset', PENDIENTE_CAMBIO_CLAVE],
  ['autenticada con proyecto', '/app/password/reset', AUTENTICADA_CON_PROYECTO],
])('el restablecimiento prevalece sobre una sesión %s en %s', async (_nombre, ruta, cuerpo) => {
  window.history.pushState({}, '', `${ruta}?token=${TOKEN_RESET}`);
  responderSesionYValidacion(cuerpo, { success: true, state: 'valid' });

  render(<Rutas />);

  expect(await screen.findByRole('heading', { name: 'Define tu nueva contraseña' })).toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: /bienvenido a last planner aia/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('button', { name: 'Actualizar y continuar' })).not.toBeInTheDocument();
  expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
  expect(screen.queryByText('test.A')).not.toBeInTheDocument();
  expect(screen.queryByText('Ana')).not.toBeInTheDocument();
  // El token nunca se pinta.
  expect(document.body.textContent ?? '').not.toContain(TOKEN_RESET);
});

test.each([
  ['/password/reset', ''],
  ['/password/reset', '?token=abc'],
  ['/password/reset', `?token=${TOKEN_RESET}&token=${TOKEN_RESET}`],
  ['/app/password/reset', ''],
  ['/app/password/reset', `?token=${TOKEN_RESET.toUpperCase()}`],
])('%s%s sin token válido muestra el enlace inválido sin llamar a la API de restablecimiento', async (ruta, query) => {
  window.history.pushState({}, '', `${ruta}${query}`);
  responderSesion(AUTENTICADA_CON_PROYECTO);

  render(<Rutas />);

  expect(await screen.findByText('El enlace no es válido o ya expiró. Solicita uno nuevo.')).toBeInTheDocument();
  expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
  expect(llamadasA('/api/auth/password/reset')).toBe(0);
});

test('en la ruta de restablecimiento, el bootstrap en vuelo muestra "Cargando…" y un fallo la alerta recuperable', async () => {
  window.history.pushState({}, '', `/password/reset?token=${TOKEN_RESET}`);
  vi.stubGlobal('fetch', vi.fn()
    .mockRejectedValueOnce(new Error('red no disponible'))
    .mockReturnValueOnce(new Promise<Response>(() => {})));

  render(<Rutas />);

  expect(screen.getByRole('status')).toHaveTextContent(/cargando/i);
  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByRole('heading', { name: 'Define tu nueva contraseña' })).not.toBeInTheDocument();
});

test('App monta el restablecimiento en /password/reset', async () => {
  window.history.pushState({}, '', `/password/reset?token=${TOKEN_RESET}`);
  responderSesionYValidacion(AUTENTICADA_CON_PROYECTO, { success: true, state: 'valid' });

  render(<App />);

  expect(await screen.findByRole('heading', { name: 'Define tu nueva contraseña' })).toBeInTheDocument();
});

test('403 csrf_invalid al validar el enlace: "Actualizar sesión" revalida sin desmontar la pantalla (pendiente Tarea 4)', async () => {
  // Tarea 4 dejó este test pendiente (ledger): con la pantalla real montada, un 403 al validar
  // ofrece "Actualizar sesión"; si `/api/session` falla en ese intento, la pantalla de
  // restablecimiento sigue montada y avisa dentro — mismo patrón que S02 (línea ~499).
  window.history.pushState({}, '', `/password/reset?token=${TOKEN_RESET}`);
  let llamadasSesion = 0;

  vi.stubGlobal(
    'fetch',
    vi.fn((input: RequestInfo | URL) => {
      const url = String(input);
      if (url.includes('/api/auth/password/reset/validate')) {
        return Promise.resolve(
          new Response(
            JSON.stringify({
              success: false,
              code: 'csrf_invalid',
              message: 'No fue posible validar la solicitud. Intenta nuevamente.',
              error: { codigo: 'csrf_invalid', mensaje: 'No fue posible validar la solicitud. Intenta nuevamente.' },
            }),
            { status: 403 },
          ),
        );
      }

      llamadasSesion += 1;
      if (llamadasSesion === 2) {
        return Promise.resolve(new Response('<h1>Error</h1>', { status: 500, headers: { 'Content-Type': 'text/html' } }));
      }
      return Promise.resolve(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
    }),
  );

  render(<Rutas />);

  expect(await screen.findByRole('heading', { name: 'Define tu nueva contraseña' })).toBeInTheDocument();
  fireEvent.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  expect(await screen.findByText('No pudimos actualizar la sesión. Intenta nuevamente.')).toBeInTheDocument();
  // La pantalla de restablecimiento real sigue montada — nunca "Cargando…" ni el login.
  expect(screen.getByRole('heading', { name: 'Define tu nueva contraseña' })).toBeInTheDocument();
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());
});

test('403 al validar, revalidación exitosa: la segunda validación usa el CSRF nuevo, una sola vez', async () => {
  // Cierra el pendiente de Tarea 4 en su variante de éxito: prueba que `csrfTokenRef` cumple su
  // propósito (evitar el efecto duplicado si `csrfToken` fuera dependencia directa) con el token
  // que de verdad cambia tras `alRevalidar()`, no con un mock.
  window.history.pushState({}, '', `/password/reset?token=${TOKEN_RESET}`);
  const csrfNuevo = 'f'.repeat(64);
  let llamadasValidate = 0;
  let llamadasSesion = 0;
  let resolverSegundaSesion: (respuesta: Response) => void = () => {};
  const segundaSesion = new Promise<Response>((resolve) => {
    resolverSegundaSesion = resolve;
  });

  vi.stubGlobal(
    'fetch',
    vi.fn((input: RequestInfo | URL, init?: RequestInit) => {
      const url = String(input);
      if (url.includes('/api/auth/password/reset/validate')) {
        llamadasValidate += 1;
        if (llamadasValidate === 1) {
          expect(new Headers(init?.headers).get('X-CSRF-Token')).toBe(csrfToken);
          return Promise.resolve(
            new Response(
              JSON.stringify({
                success: false,
                code: 'csrf_invalid',
                message: 'No fue posible validar la solicitud. Intenta nuevamente.',
                error: { codigo: 'csrf_invalid', mensaje: 'No fue posible validar la solicitud. Intenta nuevamente.' },
              }),
              { status: 403 },
            ),
          );
        }
        expect(new Headers(init?.headers).get('X-CSRF-Token')).toBe(csrfNuevo);
        return Promise.resolve(new Response(JSON.stringify({ success: true, state: 'valid' }), { status: 200 }));
      }

      llamadasSesion += 1;
      if (llamadasSesion === 1) {
        return Promise.resolve(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
      }
      return segundaSesion;
    }),
  );

  render(<Rutas />);

  fireEvent.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  // Mientras la revalidación sigue en vuelo, la pantalla sigue montada — nunca "Cargando…".
  expect(screen.getByRole('heading', { name: 'Define tu nueva contraseña' })).toBeInTheDocument();
  expect(screen.queryByText(/cargando/i)).not.toBeInTheDocument();

  resolverSegundaSesion(
    new Response(JSON.stringify({ ...ANONIMA_MISSING_SESSION, csrfToken: csrfNuevo }), { status: 200 }),
  );

  expect(await screen.findByLabelText('Nueva contraseña')).toBeVisible();
  expect(llamadasValidate).toBe(2);
});

test('éxito del restablecimiento: reemplaza el historial hacia /login?reset=1 y muestra el aviso de S01', async () => {
  // S03, Tarea 7: la URL con el token no debe quedar en el historial (navegación con `replace`),
  // y el aviso `reset=1` lo pinta el login de S01 sin duplicarlo aquí.
  window.history.pushState({}, '', `/password/reset?token=${TOKEN_RESET}`);
  const largoInicial = window.history.length;
  let llamadasReset = 0;

  vi.stubGlobal(
    'fetch',
    vi.fn((input: RequestInfo | URL) => {
      const url = String(input);
      if (url.includes('/api/auth/password/reset/validate')) {
        return Promise.resolve(new Response(JSON.stringify({ success: true, state: 'valid' }), { status: 200 }));
      }
      if (url.includes('/api/auth/password/reset')) {
        llamadasReset += 1;
        return Promise.resolve(
          new Response(
            JSON.stringify({ success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1' }),
            { status: 200 },
          ),
        );
      }
      return Promise.resolve(new Response(JSON.stringify(ANONIMA_MISSING_SESSION), { status: 200 }));
    }),
  );

  const user = userEvent.setup();
  render(<Rutas />);

  await user.type(await screen.findByLabelText('Nueva contraseña'), 'Abcdef!');
  await user.type(screen.getByLabelText('Confirmar contraseña'), 'Abcdef!');
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  await screen.findByRole('heading', { name: /bienvenido a last planner aia/i });
  expect(await screen.findByText(/restablecida correctamente/i)).toBeInTheDocument();
  expect(llamadasReset).toBe(1);
  expect(window.history.length).toBe(largoInicial);
  expect(window.location.pathname).toBe('/login');
  expect(window.location.href).not.toContain(TOKEN_RESET);
});

// --- Tarea 7, S04: pantalla standalone /app/proyectos y /proyectos -----------------------

const CAMBIO_CLAVE_REQUERIDO = {
  state: 'password_change_required',
  authenticated: false,
  reason: null,
  user: null,
  project: null,
  capabilities: {},
  navigation: { bi: null, groups: [] },
  week: null,
  csrfToken,
};

for (const pathname of ['/app/proyectos', '/proyectos']) {
  test(`${pathname}: con sesión y proyecto muestra el selector, con su propio rail`, async () => {
    window.history.pushState({}, '', pathname);
    responderSesion(AUTENTICADA_CON_PROYECTO);

    render(<Rutas />);

    expect(await screen.findByTestId('selector-proyectos')).toBeVisible();
    expect(screen.getByRole('navigation', { name: /navegación del proyecto/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Tus proyectos' })).toHaveAttribute('aria-current', 'page');
  });

  test(`${pathname}: sin proyecto también muestra el selector (no hay AppShell que lo tape)`, async () => {
    window.history.pushState({}, '', pathname);
    responderSesion(AUTENTICADA_SIN_PROYECTO);

    render(<Rutas />);

    expect(await screen.findByTestId('selector-proyectos')).toBeVisible();
  });

  test(`${pathname}: anónima nunca ve el selector, va al login`, async () => {
    window.history.pushState({}, '', pathname);
    responderSesion(ANONIMA_MISSING_SESSION);

    render(<Rutas />);

    await waitFor(() => expect(screen.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeInTheDocument());
    expect(screen.queryByTestId('selector-proyectos')).not.toBeInTheDocument();
  });

  // T7-2 (decisión del coordinador): el plan original solo exigía `state==='authenticated'`, lo
  // que dejaría pasar un cambio de clave obligatorio pendiente. Esta prueba fija que NO pasa.
  test(`${pathname}: con cambio de clave obligatorio pendiente, ve el panel de cambio de clave — nunca el selector`, async () => {
    window.history.pushState({}, '', pathname);
    responderSesion(CAMBIO_CLAVE_REQUERIDO);

    render(<Rutas />);

    expect(await screen.findByRole('button', { name: 'Actualizar y continuar' })).toBeInTheDocument();
    expect(screen.queryByTestId('selector-proyectos')).not.toBeInTheDocument();
  });
}

test('/app sin proyecto redirige a la ruta canónica /proyectos (Tarea 10)', async () => {
  window.history.pushState({}, '', '/app');
  responderSesion(AUTENTICADA_SIN_PROYECTO);

  render(<Rutas />);

  expect(await screen.findByTestId('selector-proyectos')).toBeVisible();
  await waitFor(() => expect(window.location.pathname).toBe('/proyectos'));
});

test('/app/proyectos: un error de sesión (red) muestra la alerta global, no el selector', async () => {
  window.history.pushState({}, '', '/app/proyectos');
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('red no disponible')));

  render(<Rutas />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
  expect(screen.queryByTestId('selector-proyectos')).not.toBeInTheDocument();
});

test('/app/proyectos: elegir un proyecto navega de documento completo a la ruta que devuelve el servidor', async () => {
  window.history.pushState({}, '', '/app/proyectos');
  const asignar = vi.fn();
  vi.stubGlobal('location', { ...window.location, assign: asignar });
  const fetchFalso = vi.fn((entrada: RequestInfo | URL) => {
    const ruta = String(entrada);
    if (ruta === '/api/session') {
      return Promise.resolve(new Response(JSON.stringify(AUTENTICADA_SIN_PROYECTO), { status: 200 }));
    }
    if (ruta === '/api/proyectos') {
      return Promise.resolve(new Response(JSON.stringify({
        projects: [{ id: 1, name: 'Da Porto', area: 'Construccion', active: true, role: 'A', roleLabel: 'Administrador' }],
        navigation: { bi: { visible: false, href: null } },
      }), { status: 200 }));
    }
    if (ruta === '/api/proyectos/seleccionar') {
      return Promise.resolve(new Response(JSON.stringify({ success: true, message: null, route: '/programacion-semanal' }), { status: 200 }));
    }
    return Promise.resolve(new Response(JSON.stringify({}), { status: 200 }));
  });
  vi.stubGlobal('fetch', fetchFalso);

  const usuario = userEvent.setup();
  render(<Rutas />);

  await usuario.click(await screen.findByRole('button', { name: /da porto/i }));

  await waitFor(() => expect(asignar).toHaveBeenCalledWith('/programacion-semanal'));
});
