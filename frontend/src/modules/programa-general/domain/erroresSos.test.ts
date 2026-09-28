import { describe, it, expect } from 'vitest';
import { ApiError } from '../../../lib/api/cliente';
import { mensajeErrorSos } from './erroresSos';

const http = (status: number, mensaje: string, codigo: string | null = null) =>
  new ApiError(mensaje, { tipo: 'http', status, codigo: codigo ?? `HTTP_${status}` });

describe('mensajeErrorSos', () => {
  it('usa el mensaje del servidor cuando el cliente lo trae', () => {
    expect(mensajeErrorSos(http(409, 'La actividad ya no está vigente en esta semana.', 'LPS_TARGET_STALE')))
      .toBe('La actividad ya no está vigente en esta semana.');
  });

  it('no muestra rutas ni códigos crudos: un 403 genérico se traduce', () => {
    const texto = mensajeErrorSos(http(403, '/api/lps/crisis/register respondió 403'));
    expect(texto).not.toMatch(/\/api\//);
    expect(texto).toMatch(/permiso|token de seguridad/i);
  });

  it('401 y la respuesta 200 con forma inesperada (sesión expirada del legado) hablan de la sesión', () => {
    expect(mensajeErrorSos(http(401, '/api/lps/crisis/register respondió 401'))).toMatch(/sesión/i);
    const forma = new ApiError('/api/lps/crisis/register devolvió una forma inesperada — ok: …', { tipo: 'forma_invalida', status: 200, codigo: 'INVALID_SHAPE' });
    expect(mensajeErrorSos(forma)).toMatch(/sesión/i);
  });

  it('5xx y red tienen su propio texto', () => {
    expect(mensajeErrorSos(http(500, '/api/lps/crisis/register respondió 500'))).toMatch(/servidor/i);
    expect(mensajeErrorSos(new ApiError('Failed to fetch', { tipo: 'red' }))).toMatch(/conexión/i);
  });

  it('un error cualquiera cae a un texto genérico entendible', () => {
    expect(mensajeErrorSos(new Error('boom'))).toBe('No se pudo registrar la crisis SOS. Inténtalo de nuevo.');
  });
});
