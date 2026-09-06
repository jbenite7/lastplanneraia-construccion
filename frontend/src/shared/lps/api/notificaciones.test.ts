import { ApiError } from '../../../lib/api/cliente';
import { marcarLeida, obtenerNoLeidas } from './notificaciones';

afterEach(() => {
  vi.unstubAllGlobals();
});

// --- obtenerNoLeidas: forma real de NotificationController::getUnread() tras T02 Tarea 9 ---
// ({"success":true,"ok":true,"data":[{id,type,title,message,item_count,created_at}]} — ya sin
// project_id, ver NotificationService::getUnreadByUser()).

const CSRF_TOKEN = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function itemCrudo(overrides: Record<string, unknown> = {}): Record<string, unknown> {
  return {
    id: 154,
    type: 'ps_autoprogrammed_cnp_restriction',
    title: 'Actividad autodesprogramada por restricciones',
    message: '1 actividad(es) pasaron a CNP genérica.',
    item_count: 19,
    created_at: '2026-08-26 22:54:06',
    ...overrides,
  };
}

test('obtenerNoLeidas transforma item_count/created_at snake_case a camelCase', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, ok: true, data: [itemCrudo()] }),
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

test('obtenerNoLeidas nunca expone project_id a React aunque un despliegue intermedio lo envíe', async () => {
  // El servidor ya no lo manda (T02-AC-142) — este caso cubre compatibilidad hacia atrás, no el
  // contrato vigente.
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, ok: true, data: [itemCrudo({ project_id: 'otro_proyecto' })] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado.data[0]).not.toHaveProperty('project_id');
  expect(resultado.data[0]).not.toHaveProperty('projectId');
});

test('obtenerNoLeidas con lista vacía resuelve success:true, ok:true y data:[]', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, ok: true, data: [] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado).toEqual({ success: true, ok: true, data: [] });
});

test('un "type" no catalogado en NotificationType::$registry no rompe el parseo — el catálogo crece sin tocar el transporte', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, ok: true, data: [itemCrudo({ type: 'un_tipo_que_no_existe_todavia' })] }),
    { status: 200 },
  )));

  const resultado = await obtenerNoLeidas();

  expect(resultado.data[0].type).toBe('un_tipo_que_no_existe_todavia');
});

test('un id no numérico falla como ApiError de forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, ok: true, data: [itemCrudo({ id: '154' })] }),
    { status: 200 },
  )));

  const error = await obtenerNoLeidas().catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('forma_invalida');
});

test('una respuesta sin el aditivo ok falla como ApiError de forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: true, data: [] }),
    { status: 200 },
  )));

  const error = await obtenerNoLeidas().catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('forma_invalida');
});

// --- sesión ausente (SessionMiddleware intercepta antes que el controlador) ---

test('sin sesión (401 sessionExpired) propaga como ApiError con redirect — caracterizado en test_notifications_api_contract.php', async () => {
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

// --- marcarLeida: forma real de NotificationController::markAsRead() tras T02 Tarea 9 -------
// ({"success":true,"ok":true} — ahora exige CSRF vía header X-CSRF-Token, T02-AC-139).

test('marcarLeida envía JSON {id} por POST con el header X-CSRF-Token', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({ success: true, ok: true }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await marcarLeida(31, CSRF_TOKEN);

  expect(resultado).toEqual({ success: true, ok: true });
  const [ruta, opciones] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toBe('/api/notifications/read');
  expect(opciones.method).toBe('POST');
  expect(opciones.body).toBe(JSON.stringify({ id: 31 }));
  expect((opciones.headers as Headers).get('X-CSRF-Token')).toBe(CSRF_TOKEN);
});

test('marcarLeida con id inexistente sigue devolviendo success/ok:true (idempotente, no revela pertenencia)', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ success: true, ok: true }), { status: 200 })));

  await expect(marcarLeida(999999999, CSRF_TOKEN)).resolves.toEqual({ success: true, ok: true });
});

test('marcarLeida sin CSRF válido rechaza con ApiError http/403/CSRF_INVALID', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: false, ok: false, error: { code: 'CSRF_INVALID', message: 'Token inválido.' } }),
    { status: 403 },
  )));

  const error = await marcarLeida(31, 'token-invalido').catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('http');
  expect((error as ApiError).status).toBe(403);
  expect((error as ApiError).codigo).toBe('CSRF_INVALID');
});

test('un id no positivo rechaza con ApiError http/400/VALIDATION_FAILED', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ success: false, ok: false, error: { code: 'VALIDATION_FAILED', message: 'ID de notificación requerido.' } }),
    { status: 400 },
  )));

  const error = await marcarLeida(0, CSRF_TOKEN).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).status).toBe(400);
  expect((error as ApiError).codigo).toBe('VALIDATION_FAILED');
});

test('un abort en marcarLeida rechaza con ApiError tipo abortado, sin reintento', async () => {
  const fetchFalso = vi.fn().mockRejectedValue(new DOMException('The operation was aborted.', 'AbortError'));
  vi.stubGlobal('fetch', fetchFalso);

  const error = await marcarLeida(31, CSRF_TOKEN, { signal: new AbortController().signal }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('abortado');
  expect(fetchFalso).toHaveBeenCalledTimes(1);
});
