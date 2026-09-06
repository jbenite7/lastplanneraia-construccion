import { ApiError } from '../../../lib/api/cliente';
import { agregarComentario, obtenerHilo } from './hilo';

afterEach(() => {
  vi.unstubAllGlobals();
});

function respuestaHiloTipica(overrides: Record<string, unknown> = {}): unknown {
  return {
    respuesta: 'OK',
    ok: true,
    data: [],
    comments: [
      {
        id: 1,
        comentario: 'hola',
        created_at: '2026-08-31 09:00:00',
        autor_nombre: 'Ana',
        autor_cargo: 'Residente',
        menciones: null,
        respuestas: [],
      },
    ],
    target: { kind: 'activity', activityId: 3, module: 'PG', week: 1 },
    actions: { read: true, comment: true, notifyNext: true, close: false, actorWriteBlock: 'none' },
    meta: { requestId: 'abc123' },
    ...overrides,
  };
}

// --- obtenerHilo: éxito ------------------------------------------------

test('obtenerHilo por consecutivo+modulo pide GET con esos query params', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify(respuestaHiloTipica()), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await obtenerHilo({ consecutivo: 3, modulo: 'PG' });

  expect(resultado.target).toEqual({ kind: 'activity', activityId: 3, module: 'PG', week: 1 });
  expect(resultado.comments).toHaveLength(1);
  const [ruta] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toContain('/api/lps/comments?');
  expect(String(ruta)).toContain('consecutivo=3');
  expect(String(ruta)).toContain('modulo=PG');
});

test('obtenerHilo por alertaId pide alerta_id en vez de consecutivo/modulo', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(
    JSON.stringify(respuestaHiloTipica({
      target: { kind: 'alert', activityId: 3, module: 'PG', week: 1, alertId: 7 },
      crisisAlert: { id: 7, active: true, level: 2 },
    })),
    { status: 200 },
  ));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await obtenerHilo({ alertaId: 7 });

  expect(resultado.crisisAlert).toEqual({ id: 7, active: true, level: 2 });
  const [ruta] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toContain('alerta_id=7');
  expect(String(ruta)).not.toContain('consecutivo=');
});

test('obtenerHilo sin crisisAlert (target de actividad) deja crisisAlert undefined, no null inventado', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify(respuestaHiloTipica()), { status: 200 })));

  const resultado = await obtenerHilo({ consecutivo: 3, modulo: 'PG' });

  expect(resultado.crisisAlert).toBeUndefined();
});

// --- obtenerHilo: forma inválida / error tipado ------------------------

test('un target ausente en la respuesta falla como ApiError de forma inválida, no como TypeError en el componente', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(respuestaHiloTipica({ target: undefined })), { status: 200 }),
  ));

  const error = await obtenerHilo({ consecutivo: 3, modulo: 'PG' }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('forma_invalida');
});

test('un 404 LPS_TARGET_NOT_FOUND propaga como ApiError con código, sin esquema de error propio', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ ok: false, error: { code: 'LPS_TARGET_NOT_FOUND', message: 'No fue posible completar la acción.' } }),
    { status: 404 },
  )));

  const error = await obtenerHilo({ alertaId: 999999999 }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('LPS_TARGET_NOT_FOUND');
  expect((error as ApiError).status).toBe(404);
});

test('un abort en obtenerHilo rechaza con ApiError tipo abortado', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new DOMException('The operation was aborted.', 'AbortError')));

  const error = await obtenerHilo({ consecutivo: 3, modulo: 'PG' }, { signal: new AbortController().signal })
    .catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('abortado');
});

// --- agregarComentario ---------------------------------------------------

test('agregarComentario envía form-urlencoded con consecutivo+modulo+comentario+_csrf_token', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    comment_id: 55,
    data: { commentId: 55 },
    target: { kind: 'activity', activityId: 3, module: 'PG', week: 1 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await agregarComentario({
    comentario: 'censo t02',
    csrfToken: 'a'.repeat(64),
    target: { consecutivo: 3, modulo: 'PG' },
  });

  expect(resultado.data.commentId).toBe(55);
  const [ruta, opciones] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toBe('/api/lps/comments/add');
  expect(opciones.method).toBe('POST');
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('comentario')).toBe('censo t02');
  expect(cuerpo.get('consecutivo')).toBe('3');
  expect(cuerpo.get('modulo')).toBe('PG');
  expect(cuerpo.get('_csrf_token')).toBe('a'.repeat(64));
  expect(cuerpo.get('alerta_id')).toBeNull();
});

test('agregarComentario con target de alerta envía alerta_id, no consecutivo/modulo', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    comment_id: 56,
    data: { commentId: 56 },
    target: { kind: 'alert', activityId: 3, module: 'PG', week: 1, alertId: 7 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  await agregarComentario({ comentario: 'x', csrfToken: 'a'.repeat(64), target: { alertaId: 7 } });

  const [, opciones] = fetchFalso.mock.calls[0];
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('alerta_id')).toBe('7');
  expect(cuerpo.get('consecutivo')).toBeNull();
  expect(cuerpo.get('modulo')).toBeNull();
});

test('agregarComentario incluye parent_id y menciones sólo cuando se pasan', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    comment_id: 57,
    data: { commentId: 57 },
    target: { kind: 'activity', activityId: 3, module: 'PG', week: 1 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  await agregarComentario({
    comentario: 'respuesta',
    csrfToken: 'a'.repeat(64),
    target: { consecutivo: 3, modulo: 'PG' },
    parentId: 1,
    menciones: { roles: ['R'] },
  });

  const [, opciones] = fetchFalso.mock.calls[0];
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('parent_id')).toBe('1');
  expect(cuerpo.get('menciones')).toBe(JSON.stringify({ roles: ['R'] }));
});

test('un 409 PROFILE_REQUIRED al comentar propaga como ApiError, sin reintento automático', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ ok: false, error: { code: 'PROFILE_REQUIRED', message: 'La bitácora queda disponible en modo lectura.' } }),
    { status: 409 },
  ));
  vi.stubGlobal('fetch', fetchFalso);

  const error = await agregarComentario({
    comentario: 'x',
    csrfToken: 'a'.repeat(64),
    target: { consecutivo: 3, modulo: 'PG' },
  }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('PROFILE_REQUIRED');
  expect(fetchFalso).toHaveBeenCalledTimes(1); // sin reintento automático de mutación
});
