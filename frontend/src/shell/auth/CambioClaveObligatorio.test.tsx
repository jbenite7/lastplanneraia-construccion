import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { cambiarClave, cancelarCambioClave } from '../../lib/api/auth';
import { CambioClaveObligatorio } from './CambioClaveObligatorio';

vi.mock('../../lib/api/auth', () => ({
  cambiarClave: vi.fn(),
  cancelarCambioClave: vi.fn(),
}));

const csrfToken = '0'.repeat(64);

function propiedades() {
  return {
    csrfToken,
    alCompletar: vi.fn().mockResolvedValue(undefined),
    alSalir: vi.fn().mockResolvedValue(undefined),
  };
}

async function llenarFormularioValido(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Nueva contraseña'), 'Clave!23');
  await user.type(screen.getByLabelText('Confirmar contraseña'), 'Clave!23');
}

afterEach(() => {
  vi.clearAllMocks();
});

// --- marco, marca, tema y único h1 -------------------------------------------

test('el diálogo vive dentro de MarcoAcceso: un único h1, marca, tema y pie', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
  expect(screen.getByText('Last Planner AIA')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /cambiar a tema/i })).toBeInTheDocument();
  expect(screen.getByText('© Last Planner AIA')).toBeInTheDocument();
});

test('el diálogo queda anunciado como modal y asociado al título de la página', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  const dialogo = screen.getByRole('dialog');
  const titulo = screen.getByRole('heading', { level: 1 });

  expect(dialogo).toHaveAttribute('aria-modal', 'true');
  expect(dialogo).toHaveAttribute('aria-labelledby', titulo.id);
});

// --- foco inicial ---------------------------------------------------------------

test('al montar, el foco inicial cae en el campo de nueva contraseña', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  expect(screen.getByLabelText('Nueva contraseña')).toHaveFocus();
});

// --- toggles independientes ------------------------------------------------------

test('mostrar/ocultar un campo de contraseña no revela el otro', async () => {
  const user = userEvent.setup();
  render(<CambioClaveObligatorio {...propiedades()} />);

  const botonesMostrar = screen.getAllByRole('button', { name: 'Mostrar contraseña' });
  await user.click(botonesMostrar[0]);

  expect(screen.getByLabelText('Nueva contraseña')).toHaveAttribute('type', 'text');
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveAttribute('type', 'password');
});

// --- errores de campo (las cinco reglas del servidor) + aria-describedby ---------

test('un 422 de longitud/mayúscula/especial asigna el error al campo con aria-describedby', async () => {
  const user = userEvent.setup();
  vi.mocked(cambiarClave).mockRejectedValue(
    new ApiError('Revisa los datos.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: {
        password:
          'La contraseña debe tener al menos 6 caracteres; Debe contener al menos una letra mayúscula; Debe contener al menos un carácter especial (!@#$%...)',
      },
    }),
  );
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  const mensaje = await screen.findByText(
    'La contraseña debe tener al menos 6 caracteres; Debe contener al menos una letra mayúscula; Debe contener al menos un carácter especial (!@#$%...)',
  );
  const campo = screen.getByLabelText('Nueva contraseña');

  expect(campo).toHaveAttribute('aria-describedby', mensaje.id);
  expect(campo).toHaveAttribute('aria-invalid', 'true');
  expect(campo).toHaveFocus();
});

test('un 422 de confirmación no coincidente asigna el error al campo de confirmación', async () => {
  const user = userEvent.setup();
  vi.mocked(cambiarClave).mockRejectedValue(
    new ApiError('Revisa los datos.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: { confirmation: 'Las contraseñas no coinciden' },
    }),
  );
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  const mensaje = await screen.findByText('Las contraseñas no coinciden');
  const campo = screen.getByLabelText('Confirmar contraseña');

  expect(campo).toHaveAttribute('aria-describedby', mensaje.id);
  expect(campo).toHaveAttribute('aria-invalid', 'true');
});

test('un 422 de "igual a la anterior" asigna el error al campo de nueva contraseña', async () => {
  const user = userEvent.setup();
  vi.mocked(cambiarClave).mockRejectedValue(
    new ApiError('Revisa los datos.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: { password: 'La nueva contraseña no puede ser igual a la anterior' },
    }),
  );
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  expect(await screen.findByText('La nueva contraseña no puede ser igual a la anterior')).toBeInTheDocument();
});

// --- error de servidor genérico (red/5xx) -----------------------------------------

test('un fallo de red o 5xx muestra copy técnico seguro', async () => {
  const user = userEvent.setup();
  vi.mocked(cambiarClave).mockRejectedValue(
    new ApiError('/api/auth/password/change no respondió', { tipo: 'red', codigo: 'NETWORK_ERROR' }),
  );
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  expect(await screen.findByRole('alert')).toHaveTextContent('No pudimos conectar. Intenta de nuevo.');
});

// --- estado ocupado y disparo único -------------------------------------------------

test('mientras envía, el formulario queda aria-busy y los campos deshabilitados', async () => {
  const user = userEvent.setup();
  let resolverRespuesta: (valor: { success: true; next: 'projects' }) => void = () => {};
  vi.mocked(cambiarClave).mockReturnValue(
    new Promise((resolve) => {
      resolverRespuesta = resolve;
    }),
  );
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  expect(screen.getByRole('button', { name: 'Actualizando…' })).toBeDisabled();
  expect(screen.getByLabelText('Nueva contraseña')).toBeDisabled();
  expect(screen.getByLabelText('Confirmar contraseña')).toBeDisabled();
  expect(document.querySelector('form')).toHaveAttribute('aria-busy', 'true');

  resolverRespuesta({ success: true, next: 'projects' });
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar y continuar' })).not.toBeDisabled());
});

test('un doble click en Actualizar y continuar produce una sola mutación', async () => {
  const user = userEvent.setup();
  vi.mocked(cambiarClave).mockResolvedValue({ success: true, next: 'projects' });
  render(<CambioClaveObligatorio {...propiedades()} />);

  await llenarFormularioValido(user);
  await user.dblClick(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  await waitFor(() => expect(cambiarClave).toHaveBeenCalledOnce());
});

test('un cambio exitoso delega en alCompletar', async () => {
  const user = userEvent.setup();
  const alCompletar = vi.fn().mockResolvedValue(undefined);
  vi.mocked(cambiarClave).mockResolvedValue({ success: true, next: 'projects' });
  render(<CambioClaveObligatorio {...propiedades()} alCompletar={alCompletar} />);

  await llenarFormularioValido(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar y continuar' }));

  await waitFor(() => expect(alCompletar).toHaveBeenCalledOnce());
});

// --- Escape y botón Salir: solo abren confirmación, no llaman a la API -----------

test('Escape abre la confirmación de salida sin llamar a la API', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });

  expect(cancelarCambioClave).not.toHaveBeenCalled();
  expect(screen.getByRole('heading', { name: '¿Salir del cambio de contraseña?' })).toBeVisible();
});

test('el botón Salir abre la confirmación de salida sin llamar a la API', async () => {
  const user = userEvent.setup();
  render(<CambioClaveObligatorio {...propiedades()} />);

  await user.click(screen.getByRole('button', { name: 'Salir' }));

  expect(cancelarCambioClave).not.toHaveBeenCalled();
  expect(screen.getByRole('heading', { name: '¿Salir del cambio de contraseña?' })).toBeVisible();
});

// --- ronda de arreglos 1: foco seguro y nombre accesible de la confirmación ------

test('al abrir la confirmación, el foco cae en "Seguir editando", nunca en el botón destructivo', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });

  expect(screen.getByRole('button', { name: 'Seguir editando' })).toHaveFocus();
});

test('con la confirmación abierta, el diálogo queda nombrado por su propio título, no sin nombre', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });

  const dialogo = screen.getByRole('dialog');
  const tituloConfirmacion = screen.getByRole('heading', { name: '¿Salir del cambio de contraseña?' });

  expect(dialogo).toHaveAttribute('aria-labelledby', tituloConfirmacion.id);
});

// --- backdrop inocuo ----------------------------------------------------------------

test('clic fuera del diálogo no cancela nada', async () => {
  const user = userEvent.setup();
  render(<CambioClaveObligatorio {...propiedades()} />);

  await user.click(document.body);

  expect(cancelarCambioClave).not.toHaveBeenCalled();
  expect(screen.queryByRole('heading', { name: '¿Salir del cambio de contraseña?' })).not.toBeInTheDocument();
});

// --- confirmación: un solo disparo y delega en alSalir ------------------------------

test('Confirmar salida cancela una sola vez y delega en alSalir', async () => {
  const user = userEvent.setup();
  const alSalir = vi.fn().mockResolvedValue(undefined);
  vi.mocked(cancelarCambioClave).mockResolvedValue({ success: true, next: 'login' });
  render(<CambioClaveObligatorio {...propiedades()} alSalir={alSalir} />);

  fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });
  expect(cancelarCambioClave).not.toHaveBeenCalled();
  expect(screen.getByRole('heading', { name: '¿Salir del cambio de contraseña?' })).toBeVisible();

  await user.click(screen.getByRole('button', { name: 'Confirmar salida' }));

  await waitFor(() => expect(cancelarCambioClave).toHaveBeenCalledOnce());
  expect(alSalir).toHaveBeenCalledOnce();
});

test('un doble click en Confirmar salida produce una sola cancelación', async () => {
  const user = userEvent.setup();
  vi.mocked(cancelarCambioClave).mockResolvedValue({ success: true, next: 'login' });
  render(<CambioClaveObligatorio {...propiedades()} />);

  await user.click(screen.getByRole('button', { name: 'Salir' }));
  await user.dblClick(screen.getByRole('button', { name: 'Confirmar salida' }));

  await waitFor(() => expect(cancelarCambioClave).toHaveBeenCalledOnce());
});

test('Seguir editando vuelve al formulario sin llamar a la API', async () => {
  const user = userEvent.setup();
  render(<CambioClaveObligatorio {...propiedades()} />);

  await user.click(screen.getByRole('button', { name: 'Salir' }));
  await user.click(screen.getByRole('button', { name: 'Seguir editando' }));

  expect(cancelarCambioClave).not.toHaveBeenCalled();
  expect(screen.getByRole('button', { name: 'Actualizar y continuar' })).toBeInTheDocument();
});

// --- Tab atrapado dentro del diálogo -------------------------------------------------

test('Tab en el último control del diálogo vuelve al primero (foco atrapado)', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  const dialogo = screen.getByRole('dialog');
  const controles = dialogo.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled])');
  const primero = controles[0];
  const ultimo = controles[controles.length - 1];

  ultimo.focus();
  fireEvent.keyDown(dialogo, { key: 'Tab' });

  expect(primero).toHaveFocus();
});

test('Shift+Tab en el primer control del diálogo vuelve al último (foco atrapado)', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  const dialogo = screen.getByRole('dialog');
  const controles = dialogo.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled])');
  const primero = controles[0];
  const ultimo = controles[controles.length - 1];

  primero.focus();
  fireEvent.keyDown(dialogo, { key: 'Tab', shiftKey: true });

  expect(ultimo).toHaveFocus();
});

// --- presentación responsive (Tarea 11) -----------------------------------------------

test('S01-T11: el diálogo trae la clase de panel móvil sin perder aia-modal-surface ni el atrapa-foco', () => {
  render(<CambioClaveObligatorio {...propiedades()} />);

  const dialogo = screen.getByRole('dialog');
  expect(dialogo).toHaveClass('aia-modal-surface');
  expect(dialogo).toHaveClass('aia-auth__dialog');
});
