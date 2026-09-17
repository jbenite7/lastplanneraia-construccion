import { ApiError } from './cliente';
import {
  cambiarClave,
  cancelarCambioClave,
  iniciarSesion,
  restablecerClave,
  solicitarRecuperacion,
  validarEnlaceReset,
} from './auth';

afterEach(() => {
  vi.unstubAllGlobals();
});

const CSRF = 'a'.repeat(64);

function respuesta(cuerpo: unknown, status = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status });
}

// --- iniciarSesion --------------------------------------------------------

test('iniciarSesion envía POST con el body y el header CSRF, y devuelve el next parseado', async () => {
  const fetchFalso = vi
    .fn()
    .mockResolvedValue(respuesta({ success: true, next: 'projects', message: null }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await iniciarSesion({ username: 'ana', password: 'clave' }, CSRF);

  expect(resultado).toEqual({ success: true, next: 'projects', message: null });
  expect(fetchFalso).toHaveBeenCalledWith('/api/auth/login', expect.objectContaining({ method: 'POST' }));
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  const encabezados = new Headers(opciones.headers);
  expect(encabezados.get('X-CSRF-Token')).toBe(CSRF);
  expect(JSON.parse(opciones.body as string)).toEqual({ username: 'ana', password: 'clave' });
});

test('iniciarSesion rechaza localmente una solicitud sin username, sin llamar a fetch', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);

  await expect(iniciarSesion({ username: '', password: 'clave' }, CSRF)).rejects.toThrow();
  expect(fetchFalso).not.toHaveBeenCalled();
});

test('iniciarSesion propaga un ApiError tipado cuando la respuesta no trae next', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(respuesta({ success: true, mustChangePassword: false, message: null })),
  );

  const error = await iniciarSesion({ username: 'ana', password: 'clave' }, CSRF).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('forma_invalida');
});

// --- cambiarClave -----------------------------------------------------------

test('cambiarClave envía password y confirmation a /api/auth/password/change', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true, next: 'projects' }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await cambiarClave({ password: 'nueva', confirmation: 'nueva' }, CSRF);

  expect(resultado).toEqual({ success: true, next: 'projects' });
  expect(fetchFalso).toHaveBeenCalledWith(
    '/api/auth/password/change',
    expect.objectContaining({ method: 'POST' }),
  );
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  expect(JSON.parse(opciones.body as string)).toEqual({ password: 'nueva', confirmation: 'nueva' });
});

// --- cancelarCambioClave -----------------------------------------------------

test('cancelarCambioClave llama a /api/auth/password/cancel con el header CSRF y sin body de solicitud', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true, next: 'login' }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await cancelarCambioClave(CSRF);

  expect(resultado).toEqual({ success: true, next: 'login' });
  expect(fetchFalso).toHaveBeenCalledWith(
    '/api/auth/password/cancel',
    expect.objectContaining({ method: 'POST' }),
  );
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  const encabezados = new Headers(opciones.headers);
  expect(encabezados.get('X-CSRF-Token')).toBe(CSRF);
});

// --- solicitarRecuperacion ---------------------------------------------

const MENSAJE_GENERICO_RECUPERACION =
  'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';

test('solicitarRecuperacion recorta el email, envía CSRF y devuelve el mensaje genérico', async () => {
  const fetchFalso = vi
    .fn()
    .mockResolvedValue(respuesta({ success: true, message: MENSAJE_GENERICO_RECUPERACION }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await solicitarRecuperacion(' persona@empresa.com ', CSRF);

  expect(resultado).toEqual({ success: true, message: MENSAJE_GENERICO_RECUPERACION });
  expect(fetchFalso).toHaveBeenCalledOnce();
  expect(fetchFalso).toHaveBeenCalledWith(
    '/api/auth/password/forgot',
    expect.objectContaining({ method: 'POST' }),
  );
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  const encabezados = new Headers(opciones.headers);
  expect(encabezados.get('X-CSRF-Token')).toBe(CSRF);
  expect(JSON.parse(opciones.body as string)).toEqual({ email: 'persona@empresa.com' });
});

test('solicitarRecuperacion rechaza localmente un email sin formato, sin llamar a fetch', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);

  await expect(solicitarRecuperacion('sin-formato', CSRF)).rejects.toThrow();
  expect(fetchFalso).not.toHaveBeenCalled();
});

test('solicitarRecuperacion propaga un ApiError tipado cuando el servidor responde 422', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      respuesta(
        { error: { code: 'validation_failed', fields: { email: 'correo inválido' } } },
        422,
      ),
    ),
  );

  const error = await solicitarRecuperacion('persona@empresa.com', CSRF).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('http');
  expect((error as ApiError).status).toBe(422);
  expect((error as ApiError).camposInvalidos?.email).toBe('correo inválido');
});

// --- validarEnlaceReset / restablecerClave (S03) -----------------------

const TOKEN = 'a'.repeat(64);

test('validarEnlaceReset envía el token por POST con el header CSRF y devuelve el estado parseado', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true, state: 'valid' }));
  vi.stubGlobal('fetch', fetchFalso);
  const controller = new AbortController();

  const resultado = await validarEnlaceReset(TOKEN, CSRF, controller.signal);

  expect(resultado).toEqual({ success: true, state: 'valid' });
  expect(fetchFalso).toHaveBeenCalledWith(
    '/api/auth/password/reset/validate',
    expect.objectContaining({ method: 'POST', signal: controller.signal }),
  );
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  const encabezados = new Headers(opciones.headers);
  expect(encabezados.get('X-CSRF-Token')).toBe(CSRF);
  expect(JSON.parse(opciones.body as string)).toEqual({ token: TOKEN });
});

test('validarEnlaceReset rechaza localmente un token con formato inválido, sin llamar a fetch', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);

  await expect(validarEnlaceReset('token-corto', CSRF)).rejects.toThrow();
  expect(fetchFalso).not.toHaveBeenCalled();
});

test('restablecerClave envía token, password y confirmPassword a /api/auth/password/reset', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    respuesta({ success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1' }),
  );
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await restablecerClave(
    { token: TOKEN, password: 'Abcdef!', confirmPassword: 'Abcdef!' },
    CSRF,
  );

  expect(resultado).toEqual({
    success: true,
    message: 'Contraseña restablecida correctamente.',
    redirect: '/login?reset=1',
  });
  expect(fetchFalso).toHaveBeenCalledWith(
    '/api/auth/password/reset',
    expect.objectContaining({ method: 'POST' }),
  );
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  const encabezados = new Headers(opciones.headers);
  expect(encabezados.get('X-CSRF-Token')).toBe(CSRF);
  expect(JSON.parse(opciones.body as string)).toEqual({
    token: TOKEN,
    password: 'Abcdef!',
    confirmPassword: 'Abcdef!',
  });
});

test('restablecerClave rechaza localmente contraseñas que no cumplen la política, sin llamar a fetch', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);

  await expect(
    restablecerClave({ token: TOKEN, password: 'abc', confirmPassword: 'abc' }, CSRF),
  ).rejects.toThrow();
  expect(fetchFalso).not.toHaveBeenCalled();
});

test('restablecerClave propaga un ApiError tipado cuando el enlace ya no es válido', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      respuesta({ error: { codigo: 'reset_token_invalid', mensaje: 'El enlace no es válido o ya expiró.' } }, 422),
    ),
  );

  const error = await restablecerClave(
    { token: TOKEN, password: 'Abcdef!', confirmPassword: 'Abcdef!' },
    CSRF,
  ).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('http');
  expect((error as ApiError).status).toBe(422);
  expect((error as ApiError).codigo).toBe('reset_token_invalid');
});
