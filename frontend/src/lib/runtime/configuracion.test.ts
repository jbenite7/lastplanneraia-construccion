import { expect, test } from 'vitest';
import { leerConfiguracionRuntime } from './configuracion';

const CSRF_TOKEN = '0'.repeat(64);

function documentoCon(contenido: string | null): Document {
  const documento = document.implementation.createHTMLDocument('');

  if (contenido !== null) {
    const nodo = documento.createElement('script');
    nodo.id = 'aia-runtime-config';
    nodo.type = 'application/json';
    nodo.textContent = contenido;
    documento.body.appendChild(nodo);
  }

  return documento;
}

test('sin el nodo inyectado, la configuración es la de aplicación normal', () => {
  expect(leerConfiguracionRuntime(documentoCon(null))).toEqual({ mode: 'application' });
});

test('con un runtime de mantenimiento válido, se devuelve tal cual — el bundle nunca reescribe su forma', () => {
  const configuracion = {
    mode: 'maintenance',
    action: '/algo/que-el-servidor-decide',
    error: false,
    state: 'anonymous',
    csrfToken: CSRF_TOKEN,
  };

  expect(leerConfiguracionRuntime(documentoCon(JSON.stringify(configuracion)))).toEqual(configuracion);
});

test('con state password_change_required también se acepta', () => {
  const configuracion = {
    mode: 'maintenance',
    action: '/algo/que-el-servidor-decide',
    error: false,
    state: 'password_change_required',
    csrfToken: CSRF_TOKEN,
  };

  expect(leerConfiguracionRuntime(documentoCon(JSON.stringify(configuracion)))).toEqual(configuracion);
});

test('JSON roto en el nodo cae en "invalid", nunca lanza ni arranca la aplicación normal', () => {
  expect(leerConfiguracionRuntime(documentoCon('{roto'))).toEqual({ mode: 'invalid' });
});

test('una forma que no cumple el esquema (falta csrfToken) cae en "invalid"', () => {
  const configuracion = {
    mode: 'maintenance',
    action: '/algo',
    error: false,
    state: 'anonymous',
  };

  expect(leerConfiguracionRuntime(documentoCon(JSON.stringify(configuracion)))).toEqual({ mode: 'invalid' });
});

test('action que no empieza por "/" cae en "invalid"', () => {
  const configuracion = {
    mode: 'maintenance',
    action: 'https://otro-host/algo',
    error: false,
    state: 'anonymous',
    csrfToken: CSRF_TOKEN,
  };

  expect(leerConfiguracionRuntime(documentoCon(JSON.stringify(configuracion)))).toEqual({ mode: 'invalid' });
});

test('un mode desconocido cae en "invalid"', () => {
  expect(leerConfiguracionRuntime(documentoCon(JSON.stringify({ mode: 'algo-inesperado' })))).toEqual({
    mode: 'invalid',
  });
});
