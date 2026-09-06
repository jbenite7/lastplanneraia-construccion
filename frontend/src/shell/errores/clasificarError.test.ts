import { describe, expect, test } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { clasificarError } from './clasificarError';

/**
 * Tabla de recuperación de errores globales (spec T01 §15). Cada fila de esa tabla tiene su
 * variante propia: la UI (PanelError, useRecuperacionErrorApi) decide qué hacer mirando
 * `variante`, nunca `status`/`tipo` crudos — así un futuro cambio de código HTTP no obliga a
 * tocar cada consumidor.
 */
describe('clasificarError', () => {
  test('red/abortado clasifican como "red" sin inventar un status', () => {
    const rojo = new ApiError('/api/session no respondió — revisa tu conexión', { tipo: 'red', codigo: 'NETWORK_ERROR' });
    expect(clasificarError(rojo).variante).toBe('red');

    const abortado = new ApiError('/api/session se canceló', { tipo: 'abortado', codigo: 'ABORTED' });
    expect(clasificarError(abortado).variante).toBe('red');
  });

  test('json_invalido/contenido_inesperado/forma_invalida clasifican como "contrato"', () => {
    for (const tipo of ['json_invalido', 'contenido_inesperado', 'forma_invalida'] as const) {
      const error = new ApiError('contrato roto', { tipo, status: 200, codigo: 'X' });
      expect(clasificarError(error).variante).toBe('contrato');
    }
  });

  test('http 401 clasifica como "sesion"', () => {
    const error = new ApiError('no autenticado', { tipo: 'http', status: 401, codigo: 'UNAUTHENTICATED' });
    expect(clasificarError(error).variante).toBe('sesion');
  });

  test('http 403 clasifica como "prohibido"', () => {
    const error = new ApiError('sin permiso', { tipo: 'http', status: 403, codigo: 'FORBIDDEN' });
    expect(clasificarError(error).variante).toBe('prohibido');
  });

  test('http 404 clasifica como "no_encontrado"', () => {
    const error = new ApiError('no existe', { tipo: 'http', status: 404, codigo: 'NOT_FOUND' });
    expect(clasificarError(error).variante).toBe('no_encontrado');
  });

  test('http 409 y 422 clasifican como "validacion" y conservan camposInvalidos', () => {
    const conflicto = new ApiError('conflicto', { tipo: 'http', status: 409, codigo: 'CONFLICT' });
    expect(clasificarError(conflicto).variante).toBe('validacion');

    const invalido = new ApiError('inválido', {
      tipo: 'http',
      status: 422,
      codigo: 'VALIDATION',
      camposInvalidos: { fecha: 'La fecha es obligatoria' },
    });
    const clasificacion = clasificarError(invalido);
    expect(clasificacion.variante).toBe('validacion');
    expect(clasificacion.camposInvalidos).toEqual({ fecha: 'La fecha es obligatoria' });
  });

  test('http 5xx clasifica como "servidor" y conserva correlationId', () => {
    const error = new ApiError('fallo interno', {
      tipo: 'http',
      status: 500,
      codigo: 'INTERNAL',
      correlationId: 'corr-123',
    });
    const clasificacion = clasificarError(error);
    expect(clasificacion.variante).toBe('servidor');
    expect(clasificacion.correlationId).toBe('corr-123');
  });

  test('el mensaje expuesto es siempre el ya-seguro de ApiError, nunca HTML/cuerpo crudo', () => {
    const error = new ApiError('/api/x respondió 502 con HTML en vez del contrato esperado', {
      tipo: 'contenido_inesperado',
      status: 502,
      codigo: 'UNEXPECTED_CONTENT_TYPE',
    });
    const clasificacion = clasificarError(error);
    expect(clasificacion.mensaje).toBe(error.message);
    expect(clasificacion.mensaje).not.toMatch(/<[a-z]+[\s>]/i);
  });

  test('un status http no mapeado explícitamente cae en "servidor", no revienta', () => {
    const error = new ApiError('teapot', { tipo: 'http', status: 418, codigo: 'TEAPOT' });
    expect(clasificarError(error).variante).toBe('servidor');
  });
});
