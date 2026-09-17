import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { restablecerClave, validarEnlaceReset } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import {
  MENSAJE_ENLACE_RESET_INVALIDO,
  type EstadoEnlaceReset,
  type RestablecimientoAceptado,
} from '../../lib/api/esquemas/auth';
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
    alCompletar: vi.fn(),
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

// --- Fix ronda 1: paridad visual de la ayuda de política (correcciones §7) -------

test('la ayuda de política es un párrafo único, tras el campo "Nueva contraseña", asociado por aria-describedby', async () => {
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...propiedades()} />);

  const password = await screen.findByLabelText('Nueva contraseña');
  const ayuda = screen.getByText('Mínimo 6 caracteres, una mayúscula y un carácter especial.');

  expect(ayuda.tagName).toBe('P');
  expect(password).toHaveAttribute('aria-describedby', ayuda.id);

  const posicionCampo = password.compareDocumentPosition(ayuda);
  expect(Boolean(posicionCampo & Node.DOCUMENT_POSITION_FOLLOWING)).toBe(true);
  expect(screen.queryByRole('list')).not.toBeInTheDocument();
});

test('con las cuatro reglas satisfechas, el envío local no marca error', async () => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  // Desde la Tarea 7 el envío válido llama al servidor: queda en vuelo para mirar solo lo local.
  vi.mocked(restablecerClave).mockReturnValue(new Promise(() => {}));
  render(<PantallaRestablecerClave {...propiedades()} />);

  await user.type(await screen.findByLabelText('Nueva contraseña'), 'Abcdef!');
  await user.type(screen.getByLabelText('Confirmar contraseña'), 'Abcdef!');
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});

// --- Tarea 7: mutación, errores y navegación segura ------------------------------

const CLAVE_VALIDA = 'Abcdef!';
const MENSAJE_REUSO = 'La nueva contraseña no puede ser igual a la anterior';
const MENSAJE_NO_CONFIRMADO =
  'No pudimos confirmar el cambio. Intenta iniciar sesión; si no funciona, solicita un enlace nuevo.';

function errorHttp(status: number, codigo: string, mensaje: string, campos?: Record<string, string>) {
  return new ApiError(mensaje, { tipo: 'http', status, codigo, camposInvalidos: campos ?? null });
}

async function formularioValido(props = propiedades()) {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  const vista = render(<PantallaRestablecerClave {...props} />);
  await user.type(await screen.findByLabelText('Nueva contraseña'), CLAVE_VALIDA);
  await user.type(screen.getByLabelText('Confirmar contraseña'), CLAVE_VALIDA);
  return { user, props, ...vista };
}

async function esperarSecretosLimpios() {
  await waitFor(() => expect(screen.getByLabelText('Nueva contraseña')).toHaveValue(''));
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveValue('');
}

test('doble click produce una sola mutación, bloquea controles y el éxito navega a /login?reset=1', async () => {
  const { user, props } = await formularioValido();
  let resolver!: (valor: RestablecimientoAceptado) => void;
  vi.mocked(restablecerClave).mockReturnValue(
    new Promise((resolve) => {
      resolver = resolve;
    }),
  );

  await user.dblClick(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(restablecerClave).toHaveBeenCalledOnce();
  expect(restablecerClave).toHaveBeenCalledWith(
    { token: TOKEN, password: CLAVE_VALIDA, confirmPassword: CLAVE_VALIDA },
    'b'.repeat(64),
  );
  expect(screen.getByRole('button', { name: 'Actualizando…' })).toBeDisabled();
  expect(screen.getByLabelText('Nueva contraseña')).toBeDisabled();
  expect(screen.getByLabelText('Confirmar contraseña')).toBeDisabled();

  // Enter (submit) mientras está ocupado: sigue siendo una sola mutación.
  fireEvent.submit(screen.getByRole('button', { name: 'Actualizando…' }).closest('form')!);
  expect(restablecerClave).toHaveBeenCalledOnce();

  resolver({ success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1' });

  await waitFor(() => expect(props.alCompletar).toHaveBeenCalledWith('/login?reset=1'));
  expect(props.alCompletar).toHaveBeenCalledOnce();
});

test('Enter en el campo envía una sola vez', async () => {
  const { user } = await formularioValido();
  vi.mocked(restablecerClave).mockReturnValue(new Promise(() => {}));

  await user.type(screen.getByLabelText('Confirmar contraseña'), '{Enter}');
  await user.keyboard('{Enter}');

  expect(restablecerClave).toHaveBeenCalledOnce();
});

test('un redirect distinto de la ruta segura no navega y avisa sin afirmar éxito', async () => {
  const { user, props } = await formularioValido();
  vi.mocked(restablecerClave).mockResolvedValue({
    success: true,
    message: 'Contraseña restablecida correctamente.',
    redirect: '//evil.example/login' as '/login?reset=1',
  });

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(MENSAJE_NO_CONFIRMADO);
  expect(props.alCompletar).not.toHaveBeenCalled();
  await esperarSecretosLimpios();
});

test.each([
  [422, 'validation_error', MENSAJE_REUSO, 'password', 'Nueva contraseña'],
  [422, 'validation_error', 'Las contraseñas no coinciden', 'confirmPassword', 'Confirmar contraseña'],
])('422 %s (%s → %s): limpia secretos, marca el campo y enfoca', async (status, codigo, mensaje, campo, etiqueta) => {
  const { user, props } = await formularioValido();
  vi.mocked(restablecerClave).mockRejectedValue(errorHttp(status, codigo, mensaje, { [campo]: mensaje }));

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(await screen.findByText(mensaje)).toBeVisible();
  await esperarSecretosLimpios();
  await waitFor(() => expect(screen.getByLabelText(etiqueta)).toHaveFocus());
  expect(screen.getByLabelText(etiqueta)).toHaveAttribute('aria-invalid', 'true');
  expect(props.alCompletar).not.toHaveBeenCalled();
  expect(restablecerClave).toHaveBeenCalledOnce();
});

test('tras cualquier respuesta los alternadores vuelven a ocultar', async () => {
  const { user } = await formularioValido();
  vi.mocked(restablecerClave).mockRejectedValue(
    errorHttp(422, 'validation_error', MENSAJE_REUSO, { password: MENSAJE_REUSO }),
  );

  for (const boton of screen.getAllByRole('button', { name: 'Mostrar contraseña' })) {
    await user.click(boton);
  }
  expect(screen.getByLabelText('Nueva contraseña')).toHaveAttribute('type', 'text');
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveAttribute('type', 'text');

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  await screen.findByText(MENSAJE_REUSO);
  expect(screen.getByLabelText('Nueva contraseña')).toHaveAttribute('type', 'password');
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveAttribute('type', 'password');
  expect(screen.queryByRole('button', { name: 'Ocultar contraseña' })).not.toBeInTheDocument();
});

test('403 limpia secretos, ofrece "Actualizar sesión" y nunca reenvía', async () => {
  const { user, props } = await formularioValido();
  const mensaje = 'No fue posible validar la solicitud. Intenta nuevamente.';
  vi.mocked(restablecerClave).mockRejectedValue(errorHttp(403, 'csrf_invalid', mensaje));

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(mensaje);
  await esperarSecretosLimpios();
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());

  await user.click(screen.getByRole('button', { name: 'Actualizar sesión' }));

  expect(props.alRevalidar).toHaveBeenCalledOnce();
  await waitFor(() => expect(screen.queryByRole('button', { name: 'Actualizar sesión' })).not.toBeInTheDocument());
  await waitFor(() => expect(screen.getByLabelText('Nueva contraseña')).toHaveFocus());
  expect(restablecerClave).toHaveBeenCalledOnce();
  expect(validarEnlaceReset).toHaveBeenCalledOnce();
});

test('403 y la revalidación falla: conserva la acción, avisa dentro y no reenvía', async () => {
  const props = propiedades();
  props.alRevalidar.mockRejectedValue(new Error('fallo'));
  const { user } = await formularioValido(props);
  vi.mocked(restablecerClave).mockRejectedValue(
    errorHttp(403, 'csrf_invalid', 'No fue posible validar la solicitud. Intenta nuevamente.'),
  );

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));
  await user.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  expect(await screen.findByText('No pudimos actualizar la sesión. Intenta nuevamente.')).toBeInTheDocument();
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());
  expect(restablecerClave).toHaveBeenCalledOnce();
});

test('410 pasa al estado de enlace inválido, sin formulario', async () => {
  const { user, props } = await formularioValido();
  vi.mocked(restablecerClave).mockRejectedValue(errorHttp(410, 'reset_link_invalid', MENSAJE_ENLACE_RESET_INVALIDO));

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(MENSAJE_ENLACE_RESET_INVALIDO);
  expect(screen.queryByLabelText('Nueva contraseña')).not.toBeInTheDocument();
  await waitFor(() => expect(screen.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toHaveFocus());
  expect(props.alCompletar).not.toHaveBeenCalled();
});

test.each([
  ['503', () => errorHttp(503, 'reset_unavailable', 'Error al actualizar la contraseña.'), 'Error al actualizar la contraseña.'],
  ['red', () => new ApiError('Failed to fetch', { tipo: 'red' }), MENSAJE_NO_CONFIRMADO],
  ['2xx malformado', () => new ApiError('forma', { tipo: 'forma_invalida', status: 200 }), MENSAJE_NO_CONFIRMADO],
  ['error desconocido', () => new Error('boom'), MENSAJE_NO_CONFIRMADO],
])('%s: aviso técnico honesto, secretos limpios y foco en la alerta', async (_caso, crear, mensaje) => {
  const { user, props, container } = await formularioValido();
  vi.mocked(restablecerClave).mockRejectedValue(crear());

  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(mensaje);
  await esperarSecretosLimpios();
  await waitFor(() => expect(screen.getByRole('alert')).toHaveFocus());
  expect(screen.getByRole('button', { name: 'Actualizar contraseña' })).toBeEnabled();
  expect(screen.queryByText(/restablecida correctamente/i)).not.toBeInTheDocument();
  expect(props.alCompletar).not.toHaveBeenCalled();
  expect(container.innerHTML).not.toContain(TOKEN);
  expect(restablecerClave).toHaveBeenCalledOnce();
});
