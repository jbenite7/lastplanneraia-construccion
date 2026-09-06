import { ApiError } from './cliente';
import { cambiarClave, cancelarCambioClave, iniciarSesion } from './auth';

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
