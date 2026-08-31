import { z } from 'zod';
import { ApiError, pedir } from './cliente';
import { EsquemaSesion } from './esquemas/sesion';

const esquemaDePrueba = z.object({ nombre: z.string() });

afterEach(() => {
  vi.unstubAllGlobals();
});

// --- Éxito ------------------------------------------------------------

test('devuelve los datos cuando la respuesta cumple el esquema', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 'obra' }), { status: 200 }),
  ));

  await expect(pedir('/api/x', esquemaDePrueba)).resolves.toEqual({ nombre: 'obra' });
});

test('éxito vacío: un 204 sin cuerpo resuelve contra un esquema que acepta ausencia de datos', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));

  await expect(pedir('/api/x', z.void())).resolves.toBeUndefined();
});

test('falla nombrando la ruta y el campo cuando el backend cambia la forma', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 123 }), { status: 200 }),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(Error);
  expect((error as Error).message).toContain('/api/x');
  expect((error as Error).message).toContain('nombre');
});

test('envía cookies de mismo origen y conserva el header CSRF del llamador', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 'obra' }), { status: 200 }),
  );
  vi.stubGlobal('fetch', fetchFalso);

  await pedir('/api/x', esquemaDePrueba, {
    body: JSON.stringify({ cambio: true }),
    headers: { 'X-CSRF-Token': 'a'.repeat(64) },
  });

  const [, opciones] = fetchFalso.mock.calls[0];
  const encabezados = new Headers(opciones.headers);

  expect(opciones.credentials).toBe('same-origin');
  expect(encabezados.get('Accept')).toBe('application/json');
  expect(encabezados.get('Content-Type')).toBe('application/json');
  expect(encabezados.get('X-CSRF-Token')).toBe('a'.repeat(64));
});

test('cuerpo form-urlencoded: URLSearchParams como body NO fuerza Content-Type application/json', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 'obra' }), { status: 200 }),
  );
  vi.stubGlobal('fetch', fetchFalso);

  const cuerpo = new URLSearchParams({ consecutivo: '1', comentario: 'censo' });
  await pedir('/api/x', esquemaDePrueba, { method: 'POST', body: cuerpo });

  const [, opciones] = fetchFalso.mock.calls[0];
  const encabezados = new Headers(opciones.headers);

  // `fetch` nativo ya fija `application/x-www-form-urlencoded;charset=UTF-8` solo
  // porque el body es un URLSearchParams — cliente.ts no debe pisarlo con
  // `application/json` como hace hoy para cualquier body no vacío.
  expect(encabezados.get('Content-Type')).not.toBe('application/json');
  expect(opciones.body).toBe(cuerpo);
});

test('rechaza un token CSRF que no tenga 64 caracteres hexadecimales', () => {
  const sesion = EsquemaSesion.safeParse({
    authenticated: false,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    csrfToken: 'invalido',
  });

  expect(sesion.success).toBe(false);
});

// --- Errores HTTP tipados ----------------------------------------------

test('un 500 falla como ApiError con el status, no con la forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response('se cayo', { status: 500 }),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).status).toBe(500);
  expect((error as Error).message).toMatch(/500/);
});

test.each([401, 403, 404, 409, 422] as const)(
  'un %i con {ok:false,error:{code,message}} llega tipado con status, código y mensaje',
  async (status) => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response(
        JSON.stringify({ ok: false, error: { code: 'RECURSO_DENEGADO', message: `motivo ${status}` } }),
        { status },
      ),
    ));

    const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

    expect(error).toBeInstanceOf(ApiError);
    expect((error as ApiError).status).toBe(status);
    expect((error as ApiError).codigo).toBe('RECURSO_DENEGADO');
    expect((error as ApiError).message).toBe(`motivo ${status}`);

    vi.unstubAllGlobals();
  },
);

test('un 5xx tipado con vocabulario en español ({error:{codigo,mensaje}}) también se captura', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(
      JSON.stringify({ error: { codigo: 'FALLA_SERVIDOR', mensaje: 'algo se rompió' } }),
      { status: 503 },
    ),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('FALLA_SERVIDOR');
  expect((error as ApiError).message).toBe('algo se rompió');
});

test('errores de campo: un 422 con error.fields entrega camposInvalidos', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(
      JSON.stringify({
        ok: false,
        error: { code: 'VALIDATION_ERROR', message: 'revisa el formulario', fields: { semana: 'la semana es inválida' } },
      }),
      { status: 422 },
    ),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).camposInvalidos).toEqual({ semana: 'la semana es inválida' });
});

test('redirect: un 401 con {success:false,sessionExpired,reason,redirect} (forma de finishUnauthorized) expone redirect', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(
      JSON.stringify({ success: false, sessionExpired: true, reason: 'timeout', redirect: '/login?timeout=1' }),
      { status: 401 },
    ),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).redirect).toBe('/login?timeout=1');
  expect((error as ApiError).status).toBe(401);
});

test('correlation ID: una cabecera X-Correlation-Id viaja en el ApiError', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(
      JSON.stringify({ ok: false, error: { code: 'X', message: 'x' } }),
      { status: 500, headers: { 'X-Correlation-Id': 'corr-abc-123' } },
    ),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).correlationId).toBe('corr-abc-123');
});

// --- Contrato roto: JSON malformado / HTML inesperado -------------------

test('JSON malformado: un 200 con un cuerpo que no parsea como JSON no se inserta en la UI', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response('{"nombre": "obra"', { status: 200 }),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('json_invalido');
});

test('HTML inesperado: un 200 con <html> en vez de JSON se identifica sin insertar el cuerpo en la UI', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response('<html><body>Not Found</body></html>', {
      status: 200,
      headers: { 'Content-Type': 'text/html; charset=utf-8' },
    }),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('contenido_inesperado');
  expect((error as Error).message).not.toContain('<html>');
});

// --- Abort y red ---------------------------------------------------------

test('abort: una señal cancelada rechaza con un ApiError de tipo abortado, no un error genérico', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(
    new DOMException('The operation was aborted.', 'AbortError'),
  ));

  const error = await pedir('/api/x', esquemaDePrueba, { signal: new AbortController().signal })
    .catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('abortado');
});

test('pérdida de red: fetch rechazado por conectividad se distingue de un abort', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('red');
  expect((error as ApiError).status).toBeNull();
});
