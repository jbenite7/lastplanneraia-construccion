import { z } from 'zod';
import { pedir } from './cliente';
import { EsquemaSesion } from './esquemas/sesion';

const esquemaDePrueba = z.object({ nombre: z.string() });

afterEach(() => {
  vi.unstubAllGlobals();
});

test('devuelve los datos cuando la respuesta cumple el esquema', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 'obra' }), { status: 200 }),
  ));

  await expect(pedir('/api/x', esquemaDePrueba)).resolves.toEqual({ nombre: 'obra' });
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

test('un 500 falla como error de red, no como forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response('se cayo', { status: 500 }),
  ));

  await expect(pedir('/api/x', esquemaDePrueba)).rejects.toThrow(/500/);
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

test('rechaza un token CSRF que no tenga 64 caracteres hexadecimales', () => {
  const sesion = EsquemaSesion.safeParse({
    authenticated: false,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    csrfToken: 'invalido',
  });

  expect(sesion.success).toBe(false);
});
