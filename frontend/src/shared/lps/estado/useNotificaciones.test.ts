import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { ApiError } from '../../../lib/api/cliente';
import * as gateway from '../api/notificaciones';
import { useNotificaciones } from './useNotificaciones';

const CSRF_TOKEN = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function notificacion(overrides: Partial<gateway.Notificacion> = {}): gateway.Notificacion {
  return {
    id: 1,
    type: 'pi_restriction_lowered',
    title: 'Restricción bajó de nivel',
    message: 'mensaje',
    itemCount: 1,
    createdAt: '2026-08-31 09:00:00',
    ...overrides,
  };
}

/** `document.hidden` es de sólo lectura en jsdom — se redefine para simular la pestaña. */
function fijarVisibilidad(visible: boolean): void {
  Object.defineProperty(document, 'hidden', { configurable: true, value: !visible });
  document.dispatchEvent(new Event('visibilitychange'));
}

/**
 * Con timers 100% falseados, `@testing-library`'s `waitFor` (que sondea con `setTimeout` real)
 * se queda esperando para siempre. Se evita del todo: cada resolución de promesa se fuerza
 * vaciando la cola de microtareas dentro de `act()`.
 */
async function vaciarMicrotareas(): Promise<void> {
  await act(async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
  });
}

beforeEach(() => {
  vi.useFakeTimers();
  Object.defineProperty(document, 'hidden', { configurable: true, value: false });
});

afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
  Object.defineProperty(document, 'hidden', { configurable: true, value: false });
});

// --- carga inicial ---------------------------------------------------------------------------

test('carga al montar y expone la lista en camelCase (AC-147)', async () => {
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas').mockResolvedValue({ success: true, ok: true, data: [notificacion()] });

  const { result } = renderHook(() => useNotificaciones(CSRF_TOKEN));

  expect(result.current.estado.status).toBe('cargando');
  await vaciarMicrotareas();

  expect(result.current.estado.status).toBe('lista');
  expect(espia).toHaveBeenCalledTimes(1);
  expect(espia.mock.calls[0]?.[0]).toMatchObject({ headers: { 'X-AIA-Idle-Refresh': '0' } });
  if (result.current.estado.status === 'lista') {
    expect(result.current.estado.notificaciones).toEqual([notificacion()]);
    expect(result.current.estado.actualizando).toBe(false);
  }
});

// --- un ciclo de 120s mientras la pestaña está visible (D-T02-13, AC-147/148) -----------------

test('vuelve a consultar cada 120s mientras el documento está visible', async () => {
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas').mockResolvedValue({ success: true, ok: true, data: [] });

  renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(espia).toHaveBeenCalledTimes(1);

  await act(async () => {
    await vi.advanceTimersByTimeAsync(120_000);
  });
  expect(espia).toHaveBeenCalledTimes(2);

  await act(async () => {
    await vi.advanceTimersByTimeAsync(120_000);
  });
  expect(espia).toHaveBeenCalledTimes(3);
});

// --- pestaña oculta pausa el ciclo y aborta lo que estuviera en vuelo (AC-149) -----------------

test('ocultar el documento detiene el timer y aborta la petición en curso', async () => {
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas').mockImplementation((opciones = {}) => {
    const señal = (opciones as { signal?: AbortSignal }).signal;
    return new Promise((_resolve, reject) => {
      señal?.addEventListener('abort', () => reject(new ApiError('abortado', { tipo: 'abortado', codigo: 'ABORTED' })));
    });
  });

  renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(espia).toHaveBeenCalledTimes(1); // la carga inicial quedó "en vuelo" (nunca resuelve sola)

  act(() => fijarVisibilidad(false)); // oculta la pestaña: debe abortar la carga inicial + parar el timer
  await vaciarMicrotareas();

  await act(async () => {
    await vi.advanceTimersByTimeAsync(240_000); // dos ciclos completos que NO deberían disparar nada
  });
  expect(espia).toHaveBeenCalledTimes(1); // ningún poll nuevo mientras está oculto
});

test('volver a mostrar el documento reanuda el ciclo de 120s (no relanza de inmediato)', async () => {
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas').mockResolvedValue({ success: true, ok: true, data: [] });

  renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(espia).toHaveBeenCalledTimes(1);

  act(() => fijarVisibilidad(false)); // oculta
  await vaciarMicrotareas();
  act(() => fijarVisibilidad(true)); // vuelve a mostrarse
  await vaciarMicrotareas();

  expect(espia).toHaveBeenCalledTimes(1); // volver a mostrar no dispara una carga inmediata

  await act(async () => {
    await vi.advanceTimersByTimeAsync(120_000);
  });
  expect(espia).toHaveBeenCalledTimes(2); // pero el siguiente ciclo de 120s sí llega
});

// --- desmontar/logout aborta (AC-149) ----------------------------------------------------------

test('desmontar cancela la petición en vuelo y el timer', async () => {
  const señalesAbortadas: boolean[] = [];
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas').mockImplementation((opciones = {}) => {
    const señal = (opciones as { signal?: AbortSignal }).signal;
    return new Promise((_resolve, reject) => {
      señal?.addEventListener('abort', () => {
        señalesAbortadas.push(true);
        reject(new ApiError('abortado', { tipo: 'abortado', codigo: 'ABORTED' }));
      });
    });
  });

  const { unmount } = renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(espia).toHaveBeenCalledTimes(1);

  unmount();

  expect(señalesAbortadas).toEqual([true]);

  await act(async () => {
    await vi.advanceTimersByTimeAsync(240_000);
  });
  expect(espia).toHaveBeenCalledTimes(1); // ningún poll tras desmontar
});

// --- error conserva la bandeja previa y ofrece reintento manual (AC-150) ----------------------

test('un error de fondo conserva las notificaciones previas como desactualizadas', async () => {
  const espia = vi.spyOn(gateway, 'obtenerNoLeidas')
    .mockResolvedValueOnce({ success: true, ok: true, data: [notificacion({ id: 7 })] })
    .mockRejectedValueOnce(new ApiError('falló', { tipo: 'red', codigo: 'NETWORK_ERROR' }));

  const { result } = renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(result.current.estado.status).toBe('lista');

  await act(async () => {
    await vi.advanceTimersByTimeAsync(120_000);
  });

  expect(espia).toHaveBeenCalledTimes(2);
  expect(result.current.estado.status).toBe('error');
  if (result.current.estado.status === 'error') {
    expect(result.current.estado.notificacionesPrevias).toEqual([notificacion({ id: 7 })]);
    expect(result.current.estado.error).toBeInstanceOf(ApiError);
  }
});

test('reintentar() tras un error vuelve a consultar manualmente', async () => {
  vi.spyOn(gateway, 'obtenerNoLeidas')
    .mockRejectedValueOnce(new ApiError('falló', { tipo: 'red', codigo: 'NETWORK_ERROR' }))
    .mockResolvedValueOnce({ success: true, ok: true, data: [notificacion({ id: 9 })] });

  const { result } = renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(result.current.estado.status).toBe('error');

  act(() => result.current.reintentar());
  await vaciarMicrotareas();

  expect(result.current.estado.status).toBe('lista');
  if (result.current.estado.status === 'lista') {
    expect(result.current.estado.notificaciones).toEqual([notificacion({ id: 9 })]);
  }
});

// --- marcarLeida sólo retira tras éxito (AC-151) ------------------------------------------------

test('marcarLeida() retira el ítem de la lista sólo después de que el servidor confirme éxito', async () => {
  vi.spyOn(gateway, 'obtenerNoLeidas').mockResolvedValue({
    success: true,
    ok: true,
    data: [notificacion({ id: 1 }), notificacion({ id: 2 })],
  });
  const espiaMarcar = vi.spyOn(gateway, 'marcarLeida').mockResolvedValue({ success: true, ok: true });

  const { result } = renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(result.current.estado.status).toBe('lista');

  await act(async () => {
    await result.current.marcarLeida(1);
  });

  expect(espiaMarcar).toHaveBeenCalledWith(1, CSRF_TOKEN);
  expect(result.current.estado.status).toBe('lista');
  if (result.current.estado.status === 'lista') {
    expect(result.current.estado.notificaciones.map((n) => n.id)).toEqual([2]);
  }
});

test('marcarLeida() no retira nada si el servidor falla', async () => {
  vi.spyOn(gateway, 'obtenerNoLeidas').mockResolvedValue({ success: true, ok: true, data: [notificacion({ id: 1 })] });
  vi.spyOn(gateway, 'marcarLeida').mockRejectedValue(new ApiError('falló', { tipo: 'http', status: 403, codigo: 'CSRF_INVALID' }));

  const { result } = renderHook(() => useNotificaciones(CSRF_TOKEN));
  await vaciarMicrotareas();
  expect(result.current.estado.status).toBe('lista');

  await expect(act(async () => {
    await result.current.marcarLeida(1);
  })).rejects.toBeInstanceOf(ApiError);

  expect(result.current.estado.status).toBe('lista');
  if (result.current.estado.status === 'lista') {
    expect(result.current.estado.notificaciones.map((n) => n.id)).toEqual([1]);
  }
});
