import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { Rutas } from './rutas';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function responderSesion(cuerpo: unknown) {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(cuerpo), { status: 200 }),
  ));
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

  await waitFor(() => expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument());
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

  await waitFor(() => expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument());
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
  expect(screen.queryByRole('heading', { name: /entrar/i })).not.toBeInTheDocument();
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
  expect(screen.queryByRole('heading', { name: /entrar/i })).not.toBeInTheDocument();

  fireEvent.click(screen.getByRole('button', { name: /reintentar/i }));

  expect(await screen.findByRole('status')).toHaveTextContent(/cargando/i);
});

test('un contrato roto (JSON malformado) cae en el mismo estado recuperable que un fallo de red', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{"roto":', { status: 200 })));

  render(<Rutas />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos conectar/i);
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

  await screen.findByRole('heading', { name: /entrar/i });
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
  await screen.findByRole('heading', { name: /entrar/i });
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
  await waitFor(() => expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument());
  expect(screen.queryByRole('status')).not.toBeInTheDocument();
});
