import { ApiError } from '../../../lib/api/cliente';
import { cerrarCrisis, registrarCrisis } from './crisis';

afterEach(() => {
  vi.unstubAllGlobals();
});

// --- registrarCrisis -----------------------------------------------------

test('registrarCrisis por consecutivo+modulo envía trigger + csrf en form-urlencoded', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    mensaje: 'Alerta registrada',
    data: { alertId: 9, wasActive: false },
    target: { kind: 'activity', activityId: 3, module: 'PG', week: 1 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await registrarCrisis({
    trigger: 'MANUAL',
    csrfToken: 'a'.repeat(64),
    target: { consecutivo: 3, modulo: 'PG' },
  });

  expect(resultado.data).toEqual({ alertId: 9, wasActive: false });
  const [ruta, opciones] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toBe('/api/lps/crisis/register');
  expect(opciones.method).toBe('POST');
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('trigger')).toBe('MANUAL');
  expect(cuerpo.get('consecutivo')).toBe('3');
  expect(cuerpo.get('modulo')).toBe('PG');
  expect(cuerpo.get('_csrf_token')).toBe('a'.repeat(64));
});

test('registrarCrisis con target de alerta envía alerta_id', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    mensaje: 'Alerta registrada',
    data: { alertId: 9, wasActive: true },
    target: { kind: 'alert', activityId: 3, module: 'PG', week: 1, alertId: 9 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  await registrarCrisis({ trigger: 'SOS-RES', csrfToken: 'a'.repeat(64), target: { alertaId: 9 } });

  const [, opciones] = fetchFalso.mock.calls[0];
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('alerta_id')).toBe('9');
  expect(cuerpo.get('consecutivo')).toBeNull();
});

test.each(['MANUAL', 'SOS-RES', 'SOS-DIR', 'SOS-COO', 'SOS-GER'] as const)(
  'trigger %s del enum cerrado se acepta',
  async (trigger) => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({
      respuesta: 'OK',
      ok: true,
      mensaje: 'Alerta registrada',
      data: { alertId: 1, wasActive: false },
      target: { kind: 'activity', activityId: 3, module: 'PG', week: 1 },
      meta: { requestId: 'x' },
    }), { status: 200 })));

    await expect(
      registrarCrisis({ trigger, csrfToken: 'a'.repeat(64), target: { consecutivo: 3, modulo: 'PG' } }),
    ).resolves.toBeDefined();

    vi.unstubAllGlobals();
  },
);

test('un 422 VALIDATION_FAILED con trigger inválido en fields propaga como ApiError con camposInvalidos', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({
      ok: false,
      error: { code: 'VALIDATION_FAILED', message: 'Datos inválidos.', fields: { trigger: 'Debe ser MANUAL, SOS-RES, SOS-DIR, SOS-COO o SOS-GER.' } },
    }),
    { status: 422 },
  )));

  const error = await registrarCrisis({
    // @ts-expect-error — se prueba deliberadamente un trigger fuera del tipo, como si viniera de un caller no confiable
    trigger: 'AUTO-DESCONOCIDO',
    csrfToken: 'a'.repeat(64),
    target: { consecutivo: 3, modulo: 'PG' },
  }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('VALIDATION_FAILED');
  expect((error as ApiError).camposInvalidos).toEqual({ trigger: 'Debe ser MANUAL, SOS-RES, SOS-DIR, SOS-COO o SOS-GER.' });
});

test('un 409 LPS_ESCALATION_TERMINAL propaga como ApiError, sin caer en CAPABILITY_REQUIRED', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ ok: false, error: { code: 'LPS_ESCALATION_TERMINAL', message: 'La alerta ya está en el nivel más alto; no hay a quién escalar.' } }),
    { status: 409 },
  )));

  const error = await registrarCrisis({
    trigger: 'MANUAL',
    csrfToken: 'a'.repeat(64),
    target: { alertaId: 9 },
  }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('LPS_ESCALATION_TERMINAL');
  expect((error as ApiError).status).toBe(409);
});

// --- cerrarCrisis ----------------------------------------------------------

test('cerrarCrisis envía alerta_id + justificacion + csrf', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
    respuesta: 'OK',
    ok: true,
    mensaje: 'Crisis mitigada exitosamente',
    data: { alertId: 9 },
    target: { kind: 'alert', activityId: 3, module: 'PG', week: 1, alertId: 9 },
    meta: { requestId: 'x' },
  }), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const resultado = await cerrarCrisis({
    alertaId: 9,
    justificacion: 'x'.repeat(100),
    csrfToken: 'a'.repeat(64),
  });

  expect(resultado.data).toEqual({ alertId: 9 });
  const [ruta, opciones] = fetchFalso.mock.calls[0];
  expect(String(ruta)).toBe('/api/lps/crisis/close');
  const cuerpo = opciones.body as URLSearchParams;
  expect(cuerpo.get('alerta_id')).toBe('9');
  expect(cuerpo.get('justificacion')).toBe('x'.repeat(100));
  expect(cuerpo.get('_csrf_token')).toBe('a'.repeat(64));
});

test('un 409 LPS_TARGET_STALE al cerrar propaga como ApiError — React recarga, no muta local', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({ ok: false, error: { code: 'LPS_TARGET_STALE', message: 'El contexto de la alerta cambió; recarga la actividad.' } }),
    { status: 409 },
  )));

  const error = await cerrarCrisis({
    alertaId: 9,
    justificacion: 'x'.repeat(100),
    csrfToken: 'a'.repeat(64),
  }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).codigo).toBe('LPS_TARGET_STALE');
});

test('un 422 por justificación corta propaga camposInvalidos.justificacion', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(
    JSON.stringify({
      ok: false,
      error: { code: 'VALIDATION_FAILED', message: 'Datos inválidos.', fields: { justificacion: 'Debe tener al menos 100 caracteres (recortada).' } },
    }),
    { status: 422 },
  )));

  const error = await cerrarCrisis({
    alertaId: 9,
    justificacion: 'corta',
    csrfToken: 'a'.repeat(64),
  }).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).camposInvalidos).toEqual({ justificacion: 'Debe tener al menos 100 caracteres (recortada).' });
});

test('abort en cerrarCrisis rechaza con ApiError tipo abortado, sin reintento', async () => {
  const fetchFalso = vi.fn().mockRejectedValue(new DOMException('The operation was aborted.', 'AbortError'));
  vi.stubGlobal('fetch', fetchFalso);

  const error = await cerrarCrisis(
    { alertaId: 9, justificacion: 'x'.repeat(100), csrfToken: 'a'.repeat(64) },
    { signal: new AbortController().signal },
  ).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(ApiError);
  expect((error as ApiError).tipo).toBe('abortado');
  expect(fetchFalso).toHaveBeenCalledTimes(1);
});
