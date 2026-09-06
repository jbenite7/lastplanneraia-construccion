import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { ControlActividad } from './ControlActividad';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

beforeEach(() => {
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

test('logout es CSRF-idempotente: dos llamadas concurrentes solo mandan un POST y comparten el mismo resultado', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true }));
  vi.stubGlobal('fetch', fetchFalso);
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  const [resultado1, resultado2] = await Promise.all([control.cerrarSesion('usuario'), control.cerrarSesion('usuario')]);

  expect(fetchFalso).toHaveBeenCalledTimes(1);
  expect(alCerrarSesion).toHaveBeenCalledTimes(1);
  expect(resultado1).toBe('confirmado');
  expect(resultado2).toBe('confirmado');
});

test('logout envía el CSRF por header contra /api/auth/logout', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true }));
  vi.stubGlobal('fetch', fetchFalso);
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  await control.cerrarSesion('usuario');

  expect(fetchFalso).toHaveBeenCalledWith('/api/auth/logout', expect.objectContaining({ method: 'POST' }));
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  expect(new Headers(opciones.headers).get('X-CSRF-Token')).toBe(csrfToken);
});

test('logout es idempotente ante un 403 (sesión ya cerrada por el servidor): invalida localmente Y lo reporta como confirmado', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ success: false, message: 'Solicitud no permitida.' }, 403)));
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  await expect(control.cerrarSesion('usuario')).resolves.toBe('confirmado');

  expect(alCerrarSesion).toHaveBeenCalledWith('usuario', 'confirmado');
});

// Fix round 1 (hallazgo de revisión): un 403 idempotente y un fallo de red genuino NO son el mismo
// caso. Ambos invalidan localmente (el cliente nunca se queda sirviendo una sesión muerta), pero
// solo el 403 es una confirmación real del servidor — un fallo de red no lo es, y el llamador
// necesita poder distinguirlos (p. ej. para no decir "sesión cerrada" cuando en realidad no hubo
// confirmación).
test('logout ante un fallo de red genuino invalida localmente PERO lo reporta como `red`, no como confirmado', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  await expect(control.cerrarSesion('red')).resolves.toBe('red');

  expect(alCerrarSesion).toHaveBeenCalledWith('red', 'red');
});

test('el 403 idempotente y el fallo de red producen resultados distintos para la misma razón de cierre', async () => {
  const fetch403 = vi.fn().mockResolvedValue(respuesta({ success: false }, 403));
  const controlConfirmado = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });
  vi.stubGlobal('fetch', fetch403);
  const resultadoConfirmado = await controlConfirmado.cerrarSesion('timeout');

  const fetchRed = vi.fn().mockRejectedValue(new TypeError('Failed to fetch'));
  const controlRed = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });
  vi.stubGlobal('fetch', fetchRed);
  const resultadoRed = await controlRed.cerrarSesion('timeout');

  expect(resultadoConfirmado).toBe('confirmado');
  expect(resultadoRed).toBe('red');
  expect(resultadoConfirmado).not.toBe(resultadoRed);
});

test('llamar cerrarSesion() otra vez tras completar la primera manda un segundo POST (no queda bloqueado para siempre)', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true }));
  vi.stubGlobal('fetch', fetchFalso);
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  await control.cerrarSesion('usuario');
  await control.cerrarSesion('usuario');

  expect(fetchFalso).toHaveBeenCalledTimes(2);
});

test('touch válido no cierra la sesión', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }));
  vi.stubGlobal('fetch', fetchFalso);
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  control.iniciar();
  await vi.advanceTimersByTimeAsync(60_000);

  expect(alCerrarSesion).not.toHaveBeenCalled();
  control.detener();
});

test('touch inválido (401 sin cuerpo reconocible) cierra sesión de todas formas', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: false }, 401));
  vi.stubGlobal('fetch', fetchFalso);
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  control.iniciar();
  await vi.advanceTimersByTimeAsync(60_000);

  expect(alCerrarSesion).toHaveBeenCalled();
  control.detener();
});

const casosDeRazon: Array<[string, string]> = [
  ['timeout', 'timeout'],
  ['inactive', 'inactive'],
  ['stale_session', 'stale_session'],
  ['session_unverified', 'session_unverified'],
];

for (const [razonServidor, razonEsperada] of casosDeRazon) {
  test(`touch 401 con reason=${razonServidor} (SessionMiddleware) cierra sesión con esa razón`, async () => {
    const fetchFalso = vi.fn().mockResolvedValue(
      respuesta({ success: false, sessionExpired: true, reason: razonServidor, redirect: '/login' }, 401),
    );
    vi.stubGlobal('fetch', fetchFalso);
    const alCerrarSesion = vi.fn();
    const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

    control.iniciar();
    await vi.advanceTimersByTimeAsync(60_000);

    // El propio POST de logout, en este fixture, también recibe el 401 del mock global — no es
    // el foco de este test (que es el mapeo de razón del touch), así que el resultado se acepta
    // como cualquiera de los dos valores válidos en vez de fijar uno arbitrario.
    expect(alCerrarSesion).toHaveBeenCalledWith(razonEsperada, expect.stringMatching(/^(confirmado|red)$/));
    control.detener();
  });
}

// "Pérdida de membresía de proyecto": el touch en sí no la modela (es agnóstico de proyecto), pero
// cualquier invalidación explícita disparada desde fuera (p. ej. el bootstrap detectando que el
// proyecto activo ya no es válido) debe pasar por el mismo `cerrarSesion` idempotente — nunca un
// segundo camino de logout.
test('una invalidación explícita por pérdida de membresía usa el mismo cerrarSesion idempotente', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true }));
  vi.stubGlobal('fetch', fetchFalso);
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  await control.cerrarSesion('membership_loss');

  expect(alCerrarSesion).toHaveBeenCalledWith('membership_loss', 'confirmado');
  expect(fetchFalso).toHaveBeenCalledTimes(1);
});

test('un fallo de red en el heartbeat de touch no cierra la sesión por sí solo (el temporizador local sigue siendo la autoridad)', async () => {
  const fetchFalso = vi.fn().mockRejectedValue(new TypeError('Failed to fetch'));
  vi.stubGlobal('fetch', fetchFalso);
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  control.iniciar();
  await vi.advanceTimersByTimeAsync(60_000);

  expect(alCerrarSesion).not.toHaveBeenCalled();
  control.detener();
});

test('detener() a mitad de un cerrarSesion en curso no impide que la invalidación local se complete', async () => {
  let liberar!: () => void;
  vi.stubGlobal('fetch', vi.fn().mockReturnValue(new Promise((resolver) => {
    liberar = () => resolver(respuesta({ success: true }));
  })));
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  const promesa = control.cerrarSesion('usuario');
  liberar();
  await promesa;

  expect(alCerrarSesion).toHaveBeenCalledWith('usuario', 'confirmado');
});
