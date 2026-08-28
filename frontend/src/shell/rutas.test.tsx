import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vitest';
import { Rutas } from './rutas';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function responderSesion(cuerpo: unknown) {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(cuerpo), { status: 200 }),
  ));
}

afterEach(() => vi.unstubAllGlobals());

test('sin sesión muestra el login', async () => {
  responderSesion({
    authenticated: false,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument());
});

test('con sesión pero sin proyecto muestra el selector', async () => {
  responderSesion({
    authenticated: true,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: null,
    capabilities: { canManageWeeks: true },
    navigation: { bi: null },
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('heading', { name: /proyecto/i })).toBeInTheDocument());
});

test('con sesión y proyecto muestra la aplicación', async () => {
  responderSesion({
    authenticated: true,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto' },
    capabilities: { canManageWeeks: true },
    navigation: { bi: { visible: false, href: null } },
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
