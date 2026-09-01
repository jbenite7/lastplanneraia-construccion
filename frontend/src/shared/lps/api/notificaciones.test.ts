import { ApiError } from '../../../lib/api/cliente';
import { marcarLeida, obtenerNoLeidas } from './notificaciones';

afterEach(() => {
  vi.unstubAllGlobals();
});

// --- obtenerNoLeidas: forma real de NotificationController::getUnread() ---
// (medida en vivo contra el contenedor: {"success":true,"data":[{id,type,title,message,
// item_count,created_at,project_id}]} — sin "ok", con project_id todavía snake_case).

function itemCrudo(overrides: Record<string, unknown> = {}): Record<string, unknown> {
  return {
    id: 154,
    type: 'ps_autoprogrammed_cnp_restriction',
    title: 'Actividad autodesprogramada por restricciones',
    message: '1 actividad(es) pasaron a CNP genérica.',
    item_count: 19,
    created_at: '2026-08-26 22:54:06',
    project_id: 'pdc_sandbox_e2e',
    ...overrides,
  };
}

test('obtenerNoLeidas transforma item_count/created_at snake_case a camelCase', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [itemCrudo()] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado.data).toEqual([
    {
      id: 154,
      type: 'ps_autoprogrammed_cnp_restriction',
      title: 'Actividad autodesprogramada por restricciones',
      message: '1 actividad(es) pasaron a CNP genérica.',
      itemCount: 19,
      createdAt: '2026-08-26 22:54:06',
    },
  ]);
});

test('obtenerNoLeidas nunca expone project_id a React — se descarta en la transformación', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [itemCrudo({ project_id: 'otro_proyecto' })] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado.data[0]).not.toHaveProperty('project_id');
  expect(resultado.data[0]).not.toHaveProperty('projectId');
});

test('obtenerNoLeidas con lista vacía resuelve success:true y data:[]', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado).toEqual({ success: true, data: [] });
});

test('un "type" no catalogado en NotificationType::$registry no rompe el parseo — el catálogo crece sin tocar el transporte', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [itemCrudo({ type: 'un_tipo_que_no_existe_todavia' })] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado.data[0].type).toBe('un_tipo_que_no_existe_todavia');
});

test('un id no numérico falla como ApiError de forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [itemCrudo({ id: '154' })] }),
    { status: 200 },
  )));

  const error = await obtenerNoLeidas().catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('forma_invalida');
});

// --- sesión ausente (SessionMiddleware intercepta antes que el controlador) ---

test('sin sesión (401 sessionExpired) propaga como ApiError con redirect — caracterizado en test_lps_api_contract.php', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: false, sessionExpired: true, reason: 'missing_session', redirect: '/login' }),
    { status: 401 },
  )));

  const error = await obtenerNoLeidas().catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).status).toBe(401);
  expect((error as ApiError).redirect).toBe('/login');
  expect((error as ApiError).razon).toBe('missing_session');
});

// --- marcarLeida: forma real de NotificationController::markAsRead() ------
// (medida en vivo: {"success":true} — sin CSRF, el controlador actual no lo exige; sin "ok",
// sin eco del id).

test('marcarLeida envía JSON {id} por POST', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({ success: true }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await marcarLeida(31);

  expect(resultado).toEqual({ success: true });
  const [ruta, opciones] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toBe('/api/notifications/read');
  expect(opciones.method).toBe('POST');
  expect(opciones.body).toBe(JSON.stringify({ id: 31 }));
});

test('marcarLeida con id inexistente sigue devolviendo success:true (idempotente, no revela pertenencia)', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ success: true }), { status: 200 })));

  await expect(marcarLeida(999999999)).resolves.toEqual({ success: true });
});

test('un abort en marcarLeida rechaza con ApiError tipo abortado, sin reintento', async () => {
  const fetchFalso = vi.fn().mockRejectedValue(new DOMException('The operation was aborted.', 'AbortError'));
  vi.stubGlobal('fetch', fetchFalso);

  const error = await marcarLeida(31, { signal: new AbortController().signal }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('abortado');
  expect(fetchFalso).toHaveBeenCalledTimes(1);
});
