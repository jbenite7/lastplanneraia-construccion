import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { solicitarRecuperacion } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { PantallaRecuperarClave } from './PantallaRecuperarClave';

vi.mock('../../lib/api/auth', () => ({
  solicitarRecuperacion: vi.fn(),
}));

const csrfToken = '0'.repeat(64);
const MENSAJE_GENERICO = 'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';

function propiedades() {
  return {
    csrfToken,
    alRevalidar: vi.fn().mockResolvedValue(undefined),
  };
}

afterEach(() => {
  vi.clearAllMocks();
});

test('presenta el formulario completo y accesible', () => {
  render(<PantallaRecuperarClave {...propiedades()} />);

  expect(screen.getByRole('heading', { name: 'Restablecer contraseña' })).toBeVisible();
  expect(
    screen.getByText('Ingresa tu correo y te enviaremos un enlace seguro para crear una nueva contraseña.'),
  ).toBeVisible();
  expect(screen.getByLabelText('Correo electrónico')).toHaveAttribute('autocomplete', 'email');
  expect(screen.getByLabelText('Correo electrónico')).toHaveAttribute('placeholder', 'nombre@empresa.com');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
  expect(screen.getByRole('link', { name: 'Volver al inicio de sesión' })).toHaveAttribute('href', '/login');
});

test('un email vacío no llama al gateway y avisa sobre el campo', async () => {
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  expect(await screen.findByRole('alert')).toHaveTextContent('Ingresa un correo electrónico válido.');
  expect(solicitarRecuperacion).not.toHaveBeenCalled();
});

test('un formato inválido no llama al gateway, avisa sobre el campo y conserva el correo', async () => {
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'noesuncorreo');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  const campo = screen.getByLabelText('Correo electrónico');
  expect(await screen.findByRole('alert')).toHaveTextContent('Ingresa un correo electrónico válido.');
  expect(campo).toHaveAttribute('aria-invalid', 'true');
  expect(campo).toHaveValue('noesuncorreo');
  expect(solicitarRecuperacion).not.toHaveBeenCalled();
});

test('un Enter con email válido envía una sola vez', async () => {
  vi.mocked(solicitarRecuperacion).mockResolvedValue({ success: true, message: MENSAJE_GENERICO });
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com{Enter}');

  await screen.findByRole('status');
  expect(solicitarRecuperacion).toHaveBeenCalledTimes(1);
  expect(solicitarRecuperacion).toHaveBeenCalledWith('persona@empresa.com', csrfToken);
});

test('mientras envía, deshabilita campo y botón y muestra el copy de espera', async () => {
  let resolver: (valor: { success: true; message: string }) => void = () => {};
  vi.mocked(solicitarRecuperacion).mockImplementation(
    () =>
      new Promise((resolve) => {
        resolver = resolve;
      }),
  );
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  expect(screen.getByLabelText('Correo electrónico')).toBeDisabled();
  expect(screen.getByRole('button', { name: 'Enviando…' })).toBeDisabled();

  resolver({ success: true, message: MENSAJE_GENERICO });
  await screen.findByRole('status');
});

test('un éxito limpia email pero mantiene formulario', async () => {
  vi.mocked(solicitarRecuperacion).mockResolvedValue({ success: true, message: MENSAJE_GENERICO });
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  expect(await screen.findByRole('status')).toHaveTextContent(MENSAJE_GENERICO);
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
});

test('un fallo de transporte conserva el correo y muestra el aviso técnico', async () => {
  vi.mocked(solicitarRecuperacion).mockRejectedValue(new Error('red no disponible'));
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  const alerta = await screen.findByRole('alert');
  expect(alerta).toHaveTextContent('No pudimos conectar. Intenta nuevamente.');
  expect(alerta).toHaveFocus();
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('persona@empresa.com');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
});

test('un 422 asocia el error al campo, conserva el correo y enfoca el email', async () => {
  vi.mocked(solicitarRecuperacion).mockRejectedValue(
    new ApiError('Revisa el correo electrónico.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: { email: 'Ingresa un correo electrónico válido.' },
    }),
  );
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  const campo = screen.getByLabelText('Correo electrónico');
  expect(await screen.findByText('Ingresa un correo electrónico válido.')).toBeVisible();
  expect(campo).toHaveAttribute('aria-invalid', 'true');
  expect(campo).toHaveValue('persona@empresa.com');
  expect(campo).toHaveFocus();
  expect(solicitarRecuperacion).toHaveBeenCalledTimes(1);
});

test('un 503 muestra el aviso técnico del servidor, conserva el correo y enfoca la alerta', async () => {
  const mensajeServidor =
    'No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador.';
  vi.mocked(solicitarRecuperacion).mockRejectedValue(
    new ApiError(mensajeServidor, { tipo: 'http', status: 503, codigo: 'recovery_unavailable' }),
  );
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  const alerta = await screen.findByRole('alert');
  expect(alerta).toHaveTextContent(mensajeServidor);
  expect(alerta).toHaveFocus();
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('persona@empresa.com');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
});

test('un 403 ofrece actualizar sesión, conserva el correo y no reenvía el correo', async () => {
  const props = propiedades();
  vi.mocked(solicitarRecuperacion).mockRejectedValue(
    new ApiError('No fue posible validar la solicitud. Intenta nuevamente.', {
      tipo: 'http',
      status: 403,
      codigo: 'csrf_invalid',
    }),
  );
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...props} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));

  const actualizar = await screen.findByRole('button', { name: 'Actualizar sesión' });
  expect(actualizar).toHaveFocus();
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('persona@empresa.com');

  await user.click(actualizar);

  expect(props.alRevalidar).toHaveBeenCalledOnce();
  expect(solicitarRecuperacion).toHaveBeenCalledOnce();
  expect(screen.queryByRole('button', { name: 'Actualizar sesión' })).not.toBeInTheDocument();
  expect(screen.getByLabelText('Correo electrónico')).toHaveFocus();
});

test('editar el correo tras un 422 limpia el error de campo', async () => {
  vi.mocked(solicitarRecuperacion).mockRejectedValue(
    new ApiError('Revisa el correo electrónico.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: { email: 'Ingresa un correo electrónico válido.' },
    }),
  );
  const user = userEvent.setup();
  render(<PantallaRecuperarClave {...propiedades()} />);

  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));
  await screen.findByText('Ingresa un correo electrónico válido.');

  await user.type(screen.getByLabelText('Correo electrónico'), 'x');

  expect(screen.queryByText('Ingresa un correo electrónico válido.')).not.toBeInTheDocument();
});
