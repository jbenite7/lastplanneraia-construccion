import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { iniciarSesion } from '../../lib/api/auth';
import { PantallaLogin } from './PantallaLogin';

vi.mock('../../lib/api/auth', () => ({
  iniciarSesion: vi.fn(),
}));

const csrfToken = '0'.repeat(64);
const MENSAJE_401 = 'invalid_credentials';
const MENSAJE_CREDENCIALES_TEXTO = 'Usuario o contraseña incorrectos.';

function propiedades() {
  return {
    csrfToken,
    aviso: null,
    alResolver: vi.fn().mockResolvedValue(undefined),
    alRevalidar: vi.fn().mockResolvedValue(undefined),
    modo: { tipo: 'normal' as const },
  };
}

async function enviarFormularioValido(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.click(screen.getByRole('button', { name: 'Entrar' }));
}

afterEach(() => {
  vi.clearAllMocks();
});

// --- marco, marca y tema -----------------------------------------------------

test('S01-UX-01: MarcoAcceso trae un único h1, marca, tema y pie', () => {
  render(<PantallaLogin {...propiedades()} />);

  expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
  expect(screen.getByRole('heading', { level: 1, name: 'Entrar' })).toBeInTheDocument();
  expect(screen.getByText('Last Planner AIA')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /cambiar a tema/i })).toBeInTheDocument();
  expect(screen.getByText('© Last Planner AIA')).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /saltar al contenido/i })).toHaveAttribute(
    'href',
    '#contenido-acceso',
  );
});

test('S01-T11: MarcoAcceso trae el scope de presentación responsive sin perder las primitivas del shell', () => {
  const { container } = render(<PantallaLogin {...propiedades()} />);

  const raiz = container.querySelector('.aia-shell');
  expect(raiz).toHaveClass('aia-auth');
  expect(container.querySelector('main.aia-page')).toHaveClass('aia-auth__layout');
});

test('S01-UX-02: el enlace de recuperación de contraseña sigue apuntando a la ruta PHP', () => {
  render(<PantallaLogin {...propiedades()} />);

  expect(screen.getByRole('link', { name: /olvid/i })).toHaveAttribute('href', '/password/forgot');
});

// --- atributos de usuario y toggle -------------------------------------------

test('S01-UX-03: el campo de usuario trae los atributos que evitan autocorrección/mayúsculas', () => {
  render(<PantallaLogin {...propiedades()} />);

  const campoUsuario = screen.getByLabelText('Usuario');
  expect(campoUsuario).toHaveAttribute('autocapitalize', 'none');
  expect(campoUsuario).toHaveAttribute('autocomplete', 'username');
});

test('S01-UX-04: toggle y doble click producen una sola mutación', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockResolvedValue({ success: true, next: 'projects', message: null });
  render(<PantallaLogin {...propiedades()} />);

  await user.click(screen.getByRole('button', { name: 'Mostrar contraseña' }));
  expect(screen.getByLabelText('Contraseña')).toHaveAttribute('type', 'text');

  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.dblClick(screen.getByRole('button', { name: 'Entrar' }));

  await waitFor(() => expect(iniciarSesion).toHaveBeenCalledOnce());
});

// --- estado ocupado -----------------------------------------------------------

test('S01-UX-05: mientras envía, el formulario queda aria-busy y el botón cambia de texto', async () => {
  const user = userEvent.setup();
  let resolverRespuesta: (valor: { success: true; next: 'projects'; message: null }) => void = () => {};
  vi.mocked(iniciarSesion).mockReturnValue(
    new Promise((resolve) => {
      resolverRespuesta = resolve;
    }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.click(screen.getByRole('button', { name: 'Entrar' }));

  expect(screen.getByRole('button', { name: 'Entrando…' })).toBeDisabled();
  expect(screen.getByLabelText('Usuario')).toBeDisabled();
  expect(screen.getByLabelText('Contraseña')).toBeDisabled();
  expect(document.querySelector('form')).toHaveAttribute('aria-busy', 'true');

  resolverRespuesta({ success: true, next: 'projects', message: null });
  await waitFor(() => expect(screen.getByRole('button', { name: 'Entrar' })).not.toBeDisabled());
});

// --- username preservado / password limpio ------------------------------------

test('S01-UX-06: un intento fallido conserva el usuario escrito', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError(MENSAJE_401, { tipo: 'http', status: 401, codigo: 'invalid_credentials' }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await enviarFormularioValido(user);

  await screen.findByRole('alert');
  expect(screen.getByLabelText('Usuario')).toHaveValue('fixture');
});

test('S01-UX-07: un intento fallido limpia la contraseña escrita', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError(MENSAJE_401, { tipo: 'http', status: 401, codigo: 'invalid_credentials' }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await enviarFormularioValido(user);

  await screen.findByRole('alert');
  expect(screen.getByLabelText('Contraseña')).toHaveValue('');
});

// --- 401 genérico e indistinguible --------------------------------------------

test('S01-UX-08: un 401 muestra un único mensaje genérico, sin distinguir el motivo', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError(MENSAJE_401, { tipo: 'http', status: 401, codigo: 'invalid_credentials' }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await enviarFormularioValido(user);

  expect(await screen.findByRole('alert')).toHaveTextContent('Usuario o contraseña incorrectos.');
});

// --- 403 recuperable sin reenvío ----------------------------------------------

test('un 403 revalida sesión sin reenviar la contraseña', async () => {
  const user = userEvent.setup();
  const alRevalidar = vi.fn().mockResolvedValue(undefined);
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError('Solicitud no permitida.', { tipo: 'http', status: 403, codigo: 'csrf_invalid' }),
  );
  render(<PantallaLogin {...propiedades()} alRevalidar={alRevalidar} />);

  await enviarFormularioValido(user);
  await user.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  expect(alRevalidar).toHaveBeenCalledOnce();
  expect(iniciarSesion).toHaveBeenCalledOnce();
});

// --- 422 por campo --------------------------------------------------------------

test('S01-UX-09: un 422 asigna el error a cada campo y lo asocia por aria-describedby', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError('Revisa los datos.', {
      tipo: 'http',
      status: 422,
      codigo: 'validation_error',
      camposInvalidos: { username: 'El usuario es obligatorio.', password: 'La clave es obligatoria.' },
    }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.click(screen.getByRole('button', { name: 'Entrar' }));

  const mensajeUsuario = await screen.findByText('El usuario es obligatorio.');
  const mensajeClave = screen.getByText('La clave es obligatoria.');
  const campoUsuario = screen.getByLabelText('Usuario');
  const campoClave = screen.getByLabelText('Contraseña');

  expect(campoUsuario).toHaveAttribute('aria-describedby', mensajeUsuario.id);
  expect(campoClave).toHaveAttribute('aria-describedby', mensajeClave.id);
  expect(campoUsuario).toHaveAttribute('aria-invalid', 'true');
  expect(campoClave).toHaveAttribute('aria-invalid', 'true');
  expect(campoUsuario).toHaveFocus();
});

// --- red/5xx/contrato: recuperable con copy técnico seguro ---------------------

test('S01-UX-10: un fallo de red usa copy técnico seguro y permite reintentar', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValueOnce(
    new ApiError('/api/auth/login no respondió', { tipo: 'red', codigo: 'NETWORK_ERROR' }),
  );
  vi.mocked(iniciarSesion).mockResolvedValueOnce({ success: true, next: 'projects', message: null });
  render(<PantallaLogin {...propiedades()} />);

  await enviarFormularioValido(user);
  expect(await screen.findByRole('alert')).toHaveTextContent('No pudimos conectar. Intenta de nuevo.');

  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.click(screen.getByRole('button', { name: 'Entrar' }));

  await waitFor(() => expect(iniciarSesion).toHaveBeenCalledTimes(2));
});

test('un 5xx usa el mismo copy técnico seguro que un fallo de red', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockRejectedValue(
    new ApiError('/api/auth/login respondió 500', { tipo: 'http', status: 500, codigo: 'internal_error' }),
  );
  render(<PantallaLogin {...propiedades()} />);

  await enviarFormularioValido(user);

  expect(await screen.findByRole('alert')).toHaveTextContent('No pudimos conectar. Intenta de nuevo.');
});

// --- next === 'projects' | 'password_change' ------------------------------------

test('un login exitoso hacia projects delega en alResolver con next', async () => {
  const user = userEvent.setup();
  const alResolver = vi.fn().mockResolvedValue(undefined);
  vi.mocked(iniciarSesion).mockResolvedValue({ success: true, next: 'projects', message: null });
  render(<PantallaLogin {...propiedades()} alResolver={alResolver} />);

  await enviarFormularioValido(user);

  await waitFor(() => expect(alResolver).toHaveBeenCalledWith('projects'));
});

// --- aviso: mostrado y anunciado ------------------------------------------------

test('S01-UX-11: el aviso de sesión vencida se anuncia como status', () => {
  render(
    <PantallaLogin
      {...propiedades()}
      aviso={{ tipo: 'sesion_expirada', mensaje: 'Su sesión expiró por inactividad. Ingresa de nuevo.' }}
    />,
  );

  expect(screen.getByRole('status')).toHaveTextContent('Su sesión expiró por inactividad. Ingresa de nuevo.');
});

test('sin aviso, no aparece ningún status', () => {
  render(<PantallaLogin {...propiedades()} />);

  expect(screen.queryByRole('status')).not.toBeInTheDocument();
});

// --- modo mantenimiento (Tarea 12: ruta oculta servida por SpaHostRenderer) ---------------

test('en modo mantenimiento el formulario es un POST nativo al host oculto, no fetch a /api/auth/login', () => {
  render(
    <PantallaLogin
      {...propiedades()}
      modo={{ tipo: 'mantenimiento', action: '/_aia/host-oculto', error: false, csrfToken }}
    />,
  );

  const formulario = screen.getByRole('button', { name: 'Entrar' }).closest('form');
  expect(formulario).toHaveAttribute('method', 'post');
  expect(formulario).toHaveAttribute('action', '/_aia/host-oculto');

  // Nombres legacy — es el mismo contrato que `MaintenanceLoginController::submit()` lee de $_POST.
  expect(screen.getByLabelText('Usuario')).toHaveAttribute('name', 'usuario');
  expect(screen.getByLabelText('Contraseña')).toHaveAttribute('name', 'password');

  expect(iniciarSesion).not.toHaveBeenCalled();
});

test('en modo mantenimiento el csrfToken viaja en un campo oculto, nunca visible ni en la URL', () => {
  const { container } = render(
    <PantallaLogin
      {...propiedades()}
      modo={{ tipo: 'mantenimiento', action: '/_aia/host-oculto', error: false, csrfToken }}
    />,
  );

  const campoCsrf = container.querySelector('input[name="csrf_token"]');
  expect(campoCsrf).toHaveAttribute('type', 'hidden');
  expect(campoCsrf).toHaveValue(csrfToken);
});

test('en modo mantenimiento, error=true muestra un rechazo genérico sin distinguir la causa', () => {
  render(
    <PantallaLogin
      {...propiedades()}
      modo={{ tipo: 'mantenimiento', action: '/_aia/host-oculto', error: true, csrfToken }}
    />,
  );

  expect(screen.getByRole('alert')).toHaveTextContent(MENSAJE_CREDENCIALES_TEXTO);
});

test('en modo mantenimiento, error=false no muestra ninguna alerta', () => {
  render(
    <PantallaLogin
      {...propiedades()}
      modo={{ tipo: 'mantenimiento', action: '/_aia/host-oculto', error: false, csrfToken }}
    />,
  );

  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});
