import { renderHook } from '@testing-library/react';
import { expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { useRecuperacionErrorApi } from './useRecuperacionErrorApi';

test('un 401 fuera del bootstrap dispara recargar() una sola vez', () => {
  const recargar = vi.fn().mockResolvedValue(undefined);
  const error = new ApiError('no autenticado', { tipo: 'http', status: 401, codigo: 'UNAUTHENTICATED' });

  const { rerender } = renderHook(({ error: e }) => useRecuperacionErrorApi(e, { recargar }), {
    initialProps: { error },
  });
  expect(recargar).toHaveBeenCalledTimes(1);

  // El mismo objeto de error no vuelve a disparar recargar() en un re-render.
  rerender({ error });
  expect(recargar).toHaveBeenCalledTimes(1);
});

test('403/404/409/422/5xx no disparan ningún efecto automático', () => {
  const recargar = vi.fn().mockResolvedValue(undefined);
  const casos = [403, 404, 409, 422, 500] as const;

  for (const status of casos) {
    recargar.mockClear();
    const error = new ApiError('x', { tipo: 'http', status, codigo: 'X' });
    renderHook(() => useRecuperacionErrorApi(error, { recargar }));
    expect(recargar).not.toHaveBeenCalled();
  }
});

test('error null no dispara nada', () => {
  const recargar = vi.fn().mockResolvedValue(undefined);
  renderHook(() => useRecuperacionErrorApi(null, { recargar }));
  expect(recargar).not.toHaveBeenCalled();
});

test('un segundo 401 distinto (nueva instancia) sí vuelve a disparar recargar()', () => {
  const recargar = vi.fn().mockResolvedValue(undefined);
  const primero = new ApiError('no autenticado', { tipo: 'http', status: 401, codigo: 'UNAUTHENTICATED' });
  const segundo = new ApiError('no autenticado otra vez', { tipo: 'http', status: 401, codigo: 'UNAUTHENTICATED' });

  const { rerender } = renderHook(({ error }) => useRecuperacionErrorApi(error, { recargar }), {
    initialProps: { error: primero },
  });
  expect(recargar).toHaveBeenCalledTimes(1);

  rerender({ error: segundo });
  expect(recargar).toHaveBeenCalledTimes(2);
});
