import {
  EsquemaRespuestaCambioClave,
  EsquemaRespuestaCancelacionClave,
  EsquemaRespuestaLogin,
  EsquemaSolicitudCambioClave,
  EsquemaSolicitudLogin,
} from './auth';

// --- EsquemaRespuestaLogin ---------------------------------------------

test('login exitoso exige next y no acepta mustChangePassword legacy', () => {
  expect(EsquemaRespuestaLogin.safeParse({ success: true, next: 'projects', message: null }).success).toBe(true);
  expect(EsquemaRespuestaLogin.safeParse({ success: true, mustChangePassword: false, message: null }).success).toBe(
    false,
  );
});

test('login exitoso acepta next: password_change', () => {
  expect(
    EsquemaRespuestaLogin.safeParse({ success: true, next: 'password_change', message: null }).success,
  ).toBe(true);
});

test('login rechaza un next fuera del enum cerrado', () => {
  expect(EsquemaRespuestaLogin.safeParse({ success: true, next: 'otro', message: null }).success).toBe(false);
});

test('login rechaza success: false — esa forma la resuelve ApiError, no este esquema', () => {
  expect(EsquemaRespuestaLogin.safeParse({ success: false, next: 'projects', message: null }).success).toBe(false);
});

// --- EsquemaSolicitudLogin -----------------------------------------------

test('la solicitud de login rechaza username o password vacíos', () => {
  expect(EsquemaSolicitudLogin.safeParse({ username: '', password: 'x' }).success).toBe(false);
  expect(EsquemaSolicitudLogin.safeParse({ username: 'x', password: '' }).success).toBe(false);
  expect(EsquemaSolicitudLogin.safeParse({ username: 'x', password: 'x' }).success).toBe(true);
});

// --- Cambio y cancelación de clave ---------------------------------------

test('la solicitud de cambio de clave exige password y confirmation', () => {
  expect(EsquemaSolicitudCambioClave.safeParse({ password: 'a', confirmation: 'a' }).success).toBe(true);
  expect(EsquemaSolicitudCambioClave.safeParse({ password: 'a' }).success).toBe(false);
});

test('la respuesta de cambio de clave fija next en projects', () => {
  expect(EsquemaRespuestaCambioClave.safeParse({ success: true, next: 'projects' }).success).toBe(true);
  expect(EsquemaRespuestaCambioClave.safeParse({ success: true, next: 'login' }).success).toBe(false);
});

test('la respuesta de cancelación fija next en login', () => {
  expect(EsquemaRespuestaCancelacionClave.safeParse({ success: true, next: 'login' }).success).toBe(true);
  expect(EsquemaRespuestaCancelacionClave.safeParse({ success: true, next: 'projects' }).success).toBe(false);
});
