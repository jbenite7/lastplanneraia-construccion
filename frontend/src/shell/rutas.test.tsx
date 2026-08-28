import { render, screen, waitFor } from '@testing-library/react';
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
    csrfToken,
  });

  render(<Rutas />);

  await waitFor(() => expect(screen.getByRole('navigation')).toBeInTheDocument());
});
