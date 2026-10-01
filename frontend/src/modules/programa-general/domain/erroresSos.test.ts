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

  it('401 y SESION_LEGADO hablan de la sesión', () => {
    expect(mensajeErrorSos(http(401, '/api/lps/crisis/register respondió 401'))).toMatch(/sesión/i);
    const legado = new ApiError('Sesión expirada', { tipo: 'http', status: 200, codigo: 'SESION_LEGADO' });
    expect(mensajeErrorSos(legado)).toMatch(/sesión venció/i);
  });

  it('una forma inválida con 200 es un error del sistema, no una sesión vencida', () => {
    const forma = new ApiError('/api/lps/crisis/register devolvió una forma inesperada — ok: …', { tipo: 'forma_invalida', status: 200, codigo: 'INVALID_SHAPE' });
    expect(mensajeErrorSos(forma)).toBe(
      'Error del sistema: la respuesta del servidor no se pudo leer. Recarga la página para ver si la crisis quedó registrada antes de repetirla.',
    );
  });

  it('5xx y red tienen su propio texto', () => {
    expect(mensajeErrorSos(http(500, '/api/lps/crisis/register respondió 500'))).toMatch(/servidor/i);
    expect(mensajeErrorSos(new ApiError('Failed to fetch', { tipo: 'red' }))).toMatch(/conexión/i);
  });

  it('un error cualquiera cae a un texto genérico entendible', () => {
    expect(mensajeErrorSos(new Error('boom'))).toBe('No se pudo registrar la crisis SOS. Inténtalo de nuevo.');
  });
});
