import { expect, test } from 'vitest';
import { limpiarParametrosAviso, resolverAvisoAcceso } from './avisos';

// --- resolverAvisoAcceso: prioridad de razón de servidor -------------------

test('reason=timeout produce el aviso de sesión expirada', () => {
  expect(resolverAvisoAcceso('timeout', '')).toEqual({
    tipo: 'sesion_expirada',
    mensaje: 'Su sesión expiró por inactividad. Ingresa de nuevo.',
  });
});

test('reason=inactive produce el aviso de cuenta inactiva', () => {
  expect(resolverAvisoAcceso('inactive', '')).toEqual({
    tipo: 'cuenta_inactiva',
    mensaje: 'Tu cuenta está inactiva. Contacta al administrador.',
  });
});

test('reason=stale_session produce el aviso de sesión inválida', () => {
  expect(resolverAvisoAcceso('stale_session', '')).toEqual({
    tipo: 'sesion_invalida',
    mensaje: 'Tu sesión ya no es válida. Ingresa de nuevo.',
  });
});

test('reason=session_unverified produce el mismo aviso de sesión inválida', () => {
  expect(resolverAvisoAcceso('session_unverified', '')).toEqual({
    tipo: 'sesion_invalida',
    mensaje: 'Tu sesión ya no es válida. Ingresa de nuevo.',
  });
});

test('reason=missing_session sin query no muestra aviso', () => {
  expect(resolverAvisoAcceso('missing_session', '')).toBeNull();
});

// Escenario real verificado en el navegador integrado: tras un cambio de clave exitoso el
// usuario queda deslogueado (reason=missing_session) y PHP redirige a `/login?reset=1` — el
// aviso de clave restablecida debe seguir apareciendo en ese caso, no ser tapado por la razón.
test('reason=missing_session con reset=1 sí muestra el aviso de clave restablecida', () => {
  expect(resolverAvisoAcceso('missing_session', '?reset=1')).toEqual({
    tipo: 'clave_restablecida',
    mensaje: 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.',
  });
});

// --- resolverAvisoAcceso: query, solo cuando no hay razón de servidor -----

test('sin razón de servidor, reset=1 produce el aviso de clave restablecida', () => {
  expect(resolverAvisoAcceso(null, '?reset=1')).toEqual({
    tipo: 'clave_restablecida',
    mensaje: 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.',
  });
});

test('sin razón de servidor, el legacy timeout=1 produce el aviso de sesión expirada', () => {
  expect(resolverAvisoAcceso(null, '?timeout=1')).toEqual({
    tipo: 'sesion_expirada',
    mensaje: 'Su sesión expiró por inactividad. Ingresa de nuevo.',
  });
});

test('sin razón de servidor, el legacy inactive=1 produce el aviso de cuenta inactiva', () => {
  expect(resolverAvisoAcceso(null, '?inactive=1')).toEqual({
    tipo: 'cuenta_inactiva',
    mensaje: 'Tu cuenta está inactiva. Contacta al administrador.',
  });
});

test('reset=1 gana sobre un legacy timeout=1 presente a la vez', () => {
  expect(resolverAvisoAcceso(null, '?timeout=1&reset=1')).toEqual({
    tipo: 'clave_restablecida',
    mensaje: 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.',
  });
});

test('sin razón conocida ni query reconocida, no hay aviso', () => {
  expect(resolverAvisoAcceso(null, '')).toBeNull();
  expect(resolverAvisoAcceso(null, '?foo=1')).toBeNull();
});

test('una razón de servidor desconocida cae a revisar la query', () => {
  expect(resolverAvisoAcceso('otra_cosa', '?reset=1')).toEqual({
    tipo: 'clave_restablecida',
    mensaje: 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.',
  });
});

// --- limpiarParametrosAviso -------------------------------------------------

test('limpiarParametrosAviso quita timeout/inactive/reset y conserva el resto (URL absoluta)', () => {
  expect(limpiarParametrosAviso('https://app.example.com/login?reset=1&foo=bar')).toBe(
    'https://app.example.com/login?foo=bar',
  );
});

test('limpiarParametrosAviso funciona con rutas relativas y no inventa origen', () => {
  expect(limpiarParametrosAviso('/login?timeout=1&inactive=1&foo=bar')).toBe('/login?foo=bar');
});

test('limpiarParametrosAviso es un no-op cuando no hay parámetros de aviso', () => {
  expect(limpiarParametrosAviso('/login?foo=bar')).toBe('/login?foo=bar');
});
