import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { ControlActividad } from './ControlActividad';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuestaTouch(): Response {
  return new Response(JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }), { status: 200 });
}

beforeEach(() => {
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

// Contrato de 3600 segundos (spec T01 §"un único listener de actividad, un único temporizador"):
// sin actividad, la sesión se cierra exactamente cuando se cumplen 3600s desde el último evento
// registrado — ni antes ni después.
test('contrato de 3600 segundos: sin actividad, cierra sesión exactamente al llegar al timeout', async () => {
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async () => respuestaTouch()));
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  control.iniciar();

  await vi.advanceTimersByTimeAsync(3_599_999);
  expect(alCerrarSesion).not.toHaveBeenCalled();

  await vi.advanceTimersByTimeAsync(1);
  expect(alCerrarSesion).toHaveBeenCalledWith('timeout', 'confirmado');
  expect(alCerrarSesion).toHaveBeenCalledOnce();

  control.detener();
});

test('la actividad reciente reagenda el timeout — no expira a los 3600s del arranque si hubo actividad después', async () => {
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async () => respuestaTouch()));
  const alCerrarSesion = vi.fn();
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion });

  control.iniciar();

  // Actividad a los 3000s reagenda el reloj: el timeout real es 3000s + 3600s, no 3600s desde el arranque.
  await vi.advanceTimersByTimeAsync(3_000_000);
  window.dispatchEvent(new Event('mousemove'));

  await vi.advanceTimersByTimeAsync(599_999);
  expect(alCerrarSesion).not.toHaveBeenCalled();

  await vi.advanceTimersByTimeAsync(1);
  expect(alCerrarSesion).not.toHaveBeenCalled(); // todavía no: el reloj se reinició en el segundo 3000s

  await vi.advanceTimersByTimeAsync(3_600_000 - 600_000);
  expect(alCerrarSesion).toHaveBeenCalledWith('timeout', 'confirmado');

  control.detener();
});

test('agendamiento de touch en background: dispara cada intervalo configurado, un único temporizador', () => {
  const fetchFalso = vi.fn().mockImplementation(async () => respuestaTouch());
  vi.stubGlobal('fetch', fetchFalso);
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  control.iniciar();

  expect(fetchFalso).not.toHaveBeenCalled();

  vi.advanceTimersByTime(60_000);
  expect(fetchFalso).toHaveBeenCalledTimes(1);

  vi.advanceTimersByTime(60_000);
  expect(fetchFalso).toHaveBeenCalledTimes(2);

  control.detener();
});

test('las lecturas de touch en background llevan X-AIA-Idle-Refresh: 0', () => {
  const fetchFalso = vi.fn().mockImplementation(async () => respuestaTouch());
  vi.stubGlobal('fetch', fetchFalso);
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  control.iniciar();
  vi.advanceTimersByTime(60_000);

  expect(fetchFalso).toHaveBeenCalledWith('/session/touch', expect.objectContaining({ method: 'POST' }));
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  expect(new Headers(opciones.headers).get('X-AIA-Idle-Refresh')).toBe('0');

  control.detener();
});

test('un único conjunto de listeners: iniciar() dos veces no duplica los listeners de actividad', () => {
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async () => respuestaTouch()));
  const espiaAdd = vi.spyOn(window, 'addEventListener');
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  control.iniciar();
  const llamadasTrasPrimerInicio = espiaAdd.mock.calls.length;
  control.iniciar(); // segunda llamada: debe ser un no-op

  expect(espiaAdd.mock.calls.length).toBe(llamadasTrasPrimerInicio);

  control.detener();
  espiaAdd.mockRestore();
});

test('detener() retira exactamente los listeners que iniciar() registró y limpia ambos temporizadores', () => {
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async () => respuestaTouch()));
  const espiaAdd = vi.spyOn(window, 'addEventListener');
  const espiaRemove = vi.spyOn(window, 'removeEventListener');
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  control.iniciar();
  const eventosRegistrados = espiaAdd.mock.calls.map(([evento]) => evento).sort();

  control.detener();
  const eventosRetirados = espiaRemove.mock.calls.map(([evento]) => evento).sort();

  expect(eventosRetirados).toEqual(eventosRegistrados);
  expect(vi.getTimerCount()).toBe(0);

  espiaAdd.mockRestore();
  espiaRemove.mockRestore();
});

// "Ningún temporizador propio de módulo": esta suite es la única dueña de setTimeout/setInterval
// del shell — tras iniciar() debe haber como máximo dos temporizadores activos (expiración +
// heartbeat de touch), nunca más, y ninguno adicional aparece por un simple montaje/desmontaje.
test('ningún temporizador propio de módulo: iniciar() deja como máximo dos temporizadores activos', () => {
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async () => respuestaTouch()));
  const control = new ControlActividad({ obtenerCsrfToken: () => csrfToken, alCerrarSesion: vi.fn() });

  expect(vi.getTimerCount()).toBe(0);
  control.iniciar();
  expect(vi.getTimerCount()).toBe(2);

  control.detener();
  expect(vi.getTimerCount()).toBe(0);
});
