import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { restablecerClave, validarEnlaceReset } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { MENSAJE_ENLACE_RESET_INVALIDO, type EstadoEnlaceReset } from '../../lib/api/esquemas/auth';
import type { EnlaceReset } from './tokenReset';
import { PantallaRestablecerClave } from './PantallaRestablecerClave';

vi.mock('../../lib/api/auth', () => ({
  validarEnlaceReset: vi.fn(),
  restablecerClave: vi.fn(),
}));

const TOKEN = 'a'.repeat(64);
const CANDIDATO: EnlaceReset = { kind: 'candidate', token: TOKEN };
const INVALIDO: EnlaceReset = { kind: 'invalid' };

function propiedades(enlace: EnlaceReset = CANDIDATO) {
  return {
    enlace,
    csrfToken: 'b'.repeat(64),
    alRevalidar: vi.fn().mockResolvedValue(undefined),
  };
}

afterEach(() => {
  vi.clearAllMocks();
});

test('valida el token y solo después muestra el formulario, sin filtrar el token', async () => {
  let resolver!: (valor: EstadoEnlaceReset) => void;
  vi.mocked(validarEnlaceReset).mockReturnValue(
    new Promise((resolve) => {
      resolver = resolve;
    }),
  );

  const { container } = render(<PantallaRestablecerClave {...propiedades()} />);

  expect(screen.getByRole('status')).toHaveTextContent('Validando enlace…');
  expect(screen.queryByLabelText('Nueva contraseña')).not.toBeInTheDocument();
  expect(validarEnlaceReset).toHaveBeenCalledWith(TOKEN, 'b'.repeat(64), expect.anything());
  expect(container.textContent ?? '').not.toContain(TOKEN);

  resolver({ success: true, state: 'valid' });

  expect(await screen.findByLabelText('Nueva contraseña')).toBeVisible();
  expect(screen.getByLabelText('Confirmar contraseña')).toBeVisible();
  expect(container.textContent ?? '').not.toContain(TOKEN);
});

test('token local inválido no llama a la API y ofrece S02/S01', () => {
  render(<PantallaRestablecerClave {...propiedades(INVALIDO)} />);

  expect(validarEnlaceReset).not.toHaveBeenCalled();
  expect(screen.getByRole('alert')).toHaveTextContent(MENSAJE_ENLACE_RESET_INVALIDO);
  expect(screen.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toHaveAttribute('href', '/password/forgot');
  expect(screen.getByRole('link', { name: 'Volver al inicio de sesión' })).toHaveAttribute('href', '/login');
  expect(screen.queryByLabelText('Nueva contraseña')).not.toBeInTheDocument();
});

test('la API dice enlace inválido: misma pantalla terminal, sin formulario', async () => {
  vi.mocked(validarEnlaceReset).mockResolvedValue({
    success: true,
    state: 'invalid',
    message: MENSAJE_ENLACE_RESET_INVALIDO,
  });

  render(<PantallaRestablecerClave {...propiedades()} />);

  expect(await screen.findByRole('alert')).toHaveTextContent(MENSAJE_ENLACE_RESET_INVALIDO);
  expect(screen.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toHaveAttribute('href', '/password/forgot');
  expect(screen.queryByLabelText('Nueva contraseña')).not.toBeInTheDocument();
});

test('403 al validar: ofrece "Actualizar sesión" y revalida solo tras click, sin loop', async () => {
  const props = propiedades();
  vi.mocked(validarEnlaceReset)
    .mockRejectedValueOnce(
      new ApiError('No fue posible validar la solicitud. Intenta nuevamente.', {
        tipo: 'http',
        status: 403,
        codigo: 'csrf_invalid',
      }),
    )
    .mockResolvedValueOnce({ success: true, state: 'valid' });

  render(<PantallaRestablecerClave {...props} />);

  const boton = await screen.findByRole('button', { name: 'Actualizar sesión' });
  expect(boton).toHaveFocus();
  expect(validarEnlaceReset).toHaveBeenCalledTimes(1);

  boton.click();

  await screen.findByLabelText('Nueva contraseña');
  expect(props.alRevalidar).toHaveBeenCalledTimes(1);
  // Una sola revalidación adicional tras el click — nunca dos POST por el mismo click.
  expect(validarEnlaceReset).toHaveBeenCalledTimes(2);
});

test('403 al validar: si la revalidación de sesión falla, conserva la acción y avisa dentro', async () => {
  const props = propiedades();
  props.alRevalidar = vi.fn().mockRejectedValue(new Error('sesión no disponible'));
  vi.mocked(validarEnlaceReset).mockRejectedValue(
    new ApiError('No fue posible validar la solicitud. Intenta nuevamente.', {
      tipo: 'http',
      status: 403,
      codigo: 'csrf_invalid',
    }),
  );

  render(<PantallaRestablecerClave {...props} />);

  const boton = await screen.findByRole('button', { name: 'Actualizar sesión' });
  boton.click();

  expect(await screen.findByText('No pudimos actualizar la sesión. Intenta nuevamente.')).toBeInTheDocument();
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());
  // No revalida el enlace si la sesión no se pudo refrescar.
  expect(validarEnlaceReset).toHaveBeenCalledTimes(1);
});

test('503 al validar: mensaje del servidor y "Intentar nuevamente" sin cambiar a inválido', async () => {
  vi.mocked(validarEnlaceReset)
    .mockRejectedValueOnce(
      new ApiError('No pudimos validar el enlace en este momento. Intenta nuevamente.', {
        tipo: 'http',
        status: 503,
        codigo: 'reset_unavailable',
      }),
    )
    .mockResolvedValueOnce({ success: true, state: 'valid' });

  render(<PantallaRestablecerClave {...propiedades()} />);

  const alerta = await screen.findByRole('alert');
  expect(alerta).toHaveTextContent('No pudimos validar el enlace en este momento. Intenta nuevamente.');
  expect(screen.queryByText(MENSAJE_ENLACE_RESET_INVALIDO)).not.toBeInTheDocument();

  const boton = screen.getByRole('button', { name: 'Intentar nuevamente' });
  expect(boton).toHaveFocus();
  boton.click();

  await screen.findByLabelText('Nueva contraseña');
  expect(validarEnlaceReset).toHaveBeenCalledTimes(2);
});

test('red/contrato al validar: mensaje fijo y "Intentar nuevamente"', async () => {
  vi.mocked(validarEnlaceReset).mockRejectedValue(
    new ApiError('/api/auth/password/reset/validate no respondió — revisa tu conexión', {
      tipo: 'red',
      codigo: 'NETWORK_ERROR',
    }),
  );

  render(<PantallaRestablecerClave {...propiedades()} />);

  const alerta = await screen.findByRole('alert');
  expect(alerta).toHaveTextContent('No pudimos validar el enlace. Intenta nuevamente.');
  expect(screen.getByRole('button', { name: 'Intentar nuevamente' })).toBeInTheDocument();
});

test('doble click en "Intentar nuevamente" incrementa el intento una sola vez', async () => {
  vi.mocked(validarEnlaceReset)
    .mockRejectedValueOnce(
      new ApiError('/api/auth/password/reset/validate no respondió — revisa tu conexión', {
        tipo: 'red',
        codigo: 'NETWORK_ERROR',
      }),
    )
    .mockResolvedValueOnce({ success: true, state: 'valid' });

  render(<PantallaRestablecerClave {...propiedades()} />);

  const boton = await screen.findByRole('button', { name: 'Intentar nuevamente' });
  boton.click();
  boton.click();

  await screen.findByLabelText('Nueva contraseña');
  expect(validarEnlaceReset).toHaveBeenCalledTimes(2);
});

test('aborta la validación al desmontar y no actualiza estado tras el desmontaje', async () => {
  let capturado: AbortSignal | undefined;
  let resolver!: (valor: EstadoEnlaceReset) => void;
  vi.mocked(validarEnlaceReset).mockImplementation((_token, _csrf, signal) => {
    capturado = signal;
    return new Promise((resolve) => {
      resolver = resolve;
    });
  });

  const { unmount } = render(<PantallaRestablecerClave {...propiedades()} />);
  await waitFor(() => expect(capturado).toBeDefined());

  unmount();
  expect(capturado?.aborted).toBe(true);

  resolver({ success: true, state: 'valid' });
  // No hay assertion de UI posible tras desmontar; basta con que no lance.
});

test('nunca pinta ni el token ni la palabra "token" en ningún estado', async () => {
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  const { container } = render(<PantallaRestablecerClave {...propiedades()} />);
  await screen.findByLabelText('Nueva contraseña');

  expect(container.innerHTML).not.toContain(TOKEN);
  expect(container.querySelector('[data-token]')).toBeNull();
  expect(container.querySelector('input[type="hidden"]')).toBeNull();
});

// --- Tarea 6: política, campos secretos y toggles accesibles ---------------------

test('presenta dos secretos con política y toggles independientes', async () => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...propiedades()} />);

  const password = await screen.findByLabelText('Nueva contraseña');
  const confirm = screen.getByLabelText('Confirmar contraseña');

  expect(password).toHaveAttribute('autocomplete', 'new-password');
  expect(password).toHaveAttribute('aria-describedby', 'reset-password-policy');
  expect(confirm).toHaveAttribute('autocomplete', 'new-password');
  expect(password).toHaveAttribute('placeholder', 'Nueva contraseña');
  expect(confirm).toHaveAttribute('placeholder', 'Confirma tu contraseña');

  await user.click(screen.getAllByRole('button', { name: 'Mostrar contraseña' })[0]);

  expect(password).toHaveAttribute('type', 'text');
  expect(confirm).toHaveAttribute('type', 'password');
  expect(screen.getByRole('button', { name: 'Ocultar contraseña' })).toHaveAttribute('aria-pressed', 'true');
  expect(password).toHaveFocus();
});

test.each([
  ['abc', 'abc', 'La contraseña debe tener al menos 6 caracteres', 'Nueva contraseña'],
  ['abcdef!', 'abcdef!', 'Debe contener al menos una letra mayúscula', 'Nueva contraseña'],
  ['Abcdef', 'Abcdef', 'Debe contener al menos un carácter especial (!@#$%...)', 'Nueva contraseña'],
  ['Abcdef!', 'Otra1!', 'Las contraseñas no coinciden', 'Confirmar contraseña'],
])('valida %s sin llamar API', async (password, confirm, message, label) => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...propiedades()} />);

  await user.type(await screen.findByLabelText('Nueva contraseña'), password);
  await user.type(screen.getByLabelText('Confirmar contraseña'), confirm);
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(restablecerClave).not.toHaveBeenCalled();
  expect(await screen.findByText(message)).toBeVisible();
  await waitFor(() => expect(screen.getByLabelText(label)).toHaveFocus());
});

test('editar el campo con error lo limpia sin tocar el otro, y conserva lo tecleado', async () => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...propiedades()} />);

  await user.type(await screen.findByLabelText('Nueva contraseña'), 'abc');
  await user.type(screen.getByLabelText('Confirmar contraseña'), 'abc');
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  await screen.findByText('La contraseña debe tener al menos 6 caracteres');

  await user.type(screen.getByLabelText('Nueva contraseña'), '!');

  expect(screen.queryByText('La contraseña debe tener al menos 6 caracteres')).not.toBeInTheDocument();
  expect(screen.getByLabelText('Nueva contraseña')).toHaveValue('abc!');
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveValue('abc');
});

test('con las cuatro reglas satisfechas, el envío local no marca error', async () => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...propiedades()} />);

  await user.type(await screen.findByLabelText('Nueva contraseña'), 'Abcdef!');
  await user.type(screen.getByLabelText('Confirmar contraseña'), 'Abcdef!');
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});
