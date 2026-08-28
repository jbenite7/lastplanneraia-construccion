import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { PantallaLogin } from './PantallaLogin';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

afterEach(() => vi.unstubAllGlobals());

test('envía las credenciales con CSRF y avisa al shell al entrar', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({
    success: true,
    mustChangePassword: false,
    message: null,
  }));
  vi.stubGlobal('fetch', fetchFalso);
  const alEntrar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<PantallaLogin alEntrar={alEntrar} csrfToken={csrfToken} />);

  await usuario.type(screen.getByLabelText(/usuario/i), 'test.A');
  await usuario.type(screen.getByLabelText(/contraseña/i), 'clave');
  await usuario.click(screen.getByRole('button', { name: /^entrar$/i }));

  await waitFor(() => expect(alEntrar).toHaveBeenCalledOnce());
  expect(fetchFalso).toHaveBeenCalledWith('/api/auth/login', expect.objectContaining({
    method: 'POST',
    credentials: 'same-origin',
    headers: expect.any(Headers),
  }));
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  expect(new Headers(opciones.headers).get('X-CSRF-Token')).toBe(csrfToken);
});

test('muestra un mensaje genérico cuando las credenciales no sirven', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({
    success: false,
    mustChangePassword: false,
    message: 'Detalle que no se debe revelar.',
  }, 401)));
  const alEntrar = vi.fn();
  const usuario = userEvent.setup();

  render(<PantallaLogin alEntrar={alEntrar} csrfToken={csrfToken} />);

  await usuario.type(screen.getByLabelText(/usuario/i), 'test.A');
  await usuario.type(screen.getByLabelText(/contraseña/i), 'mala');
  await usuario.click(screen.getByRole('button', { name: /^entrar$/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/usuario o contraseña incorrectos/i);
  expect(alEntrar).not.toHaveBeenCalled();
});

test('mantiene la recuperación de contraseña en la ruta PHP', () => {
  render(<PantallaLogin alEntrar={vi.fn()} csrfToken={csrfToken} />);

  expect(screen.getByRole('link', { name: /olvid/i })).toHaveAttribute('href', '/password/forgot');
});
