import {
  EsquemaEstadoEnlaceReset,
  EsquemaRecuperacionAceptada,
  EsquemaRespuestaCambioClave,
  EsquemaRespuestaCancelacionClave,
  EsquemaRespuestaLogin,
  EsquemaRestablecimientoAceptado,
  EsquemaSolicitudCambioClave,
  EsquemaSolicitudLogin,
  EsquemaSolicitudRecuperacion,
  EsquemaSolicitudRestablecerClave,
  EsquemaSolicitudValidarReset,
  MENSAJE_ENLACE_RESET_INVALIDO,
} from './auth';

const MENSAJE_GENERICO_RECUPERACION =
  'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';

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

// --- Recuperación de contraseña (S02) ------------------------------------

test('la solicitud S02 acepta únicamente un email válido y recortado', () => {
  expect(EsquemaSolicitudRecuperacion.parse({ email: ' persona@empresa.com ' })).toEqual({
    email: 'persona@empresa.com',
  });
  expect(
    EsquemaSolicitudRecuperacion.safeParse({ email: 'persona@empresa.com', scope: 'admin' }).success,
  ).toBe(false);
  expect(EsquemaSolicitudRecuperacion.safeParse({ email: 'sin-formato' }).success).toBe(false);
});

test('la respuesta pública de recuperación nunca acepta el resultado interno', () => {
  expect(
    EsquemaRecuperacionAceptada.safeParse({
      success: true,
      message: MENSAJE_GENERICO_RECUPERACION,
      delivery: 'enviado',
    }).success,
  ).toBe(false);
  expect(
    EsquemaRecuperacionAceptada.safeParse({
      success: true,
      message: MENSAJE_GENERICO_RECUPERACION,
    }).success,
  ).toBe(true);
});

// --- Restablecimiento de contraseña (S03) --------------------------------

const TOKEN = 'a'.repeat(64);

test('el enlace S03 solo acepta un token hex exacto', () => {
  expect(EsquemaSolicitudValidarReset.parse({ token: TOKEN })).toEqual({ token: TOKEN });
  expect(EsquemaSolicitudValidarReset.safeParse({ token: TOKEN.toUpperCase() }).success).toBe(false);
  expect(EsquemaSolicitudValidarReset.safeParse({ token: TOKEN, scope: 'admin' }).success).toBe(false);
});

function firstResetIssue(password: string, confirmPassword: string) {
  const result = EsquemaSolicitudRestablecerClave.safeParse({ token: TOKEN, password, confirmPassword });
  expect(result.success).toBe(false);
  if (result.success) throw new Error('Se esperaba una validación S03 fallida');
  return result.error.issues[0];
}

test('la política cliente conserva las cuatro comprobaciones observables', () => {
  expect(firstResetIssue('abc', 'abc').message).toBe('La contraseña debe tener al menos 6 caracteres');
  expect(firstResetIssue('abcdef!', 'abcdef!').message).toBe('Debe contener al menos una letra mayúscula');
  expect(firstResetIssue('Abcdef', 'Abcdef').message).toBe(
    'Debe contener al menos un carácter especial (!@#$%...)',
  );
  expect(firstResetIssue('Abcdef!', 'Otra1!').path).toEqual(['confirmPassword']);
});

test('la solicitud S03 acepta una contraseña válida y coincidente', () => {
  expect(
    EsquemaSolicitudRestablecerClave.safeParse({
      token: TOKEN,
      password: 'Abcdef!',
      confirmPassword: 'Abcdef!',
    }).success,
  ).toBe(true);
});

test('el estado del enlace S03 solo acepta valid o invalid con el mensaje fijo', () => {
  expect(EsquemaEstadoEnlaceReset.safeParse({ success: true, state: 'valid' }).success).toBe(true);
  expect(
    EsquemaEstadoEnlaceReset.safeParse({
      success: true,
      state: 'invalid',
      message: MENSAJE_ENLACE_RESET_INVALIDO,
    }).success,
  ).toBe(true);
});

test('invalid nunca acepta identidad o razón interna', () => {
  expect(
    EsquemaEstadoEnlaceReset.safeParse({
      success: true,
      state: 'invalid',
      message: MENSAJE_ENLACE_RESET_INVALIDO,
      username: 'test.A',
    }).success,
  ).toBe(false);
});

test('la respuesta de restablecimiento aceptado fija mensaje y redirect', () => {
  expect(
    EsquemaRestablecimientoAceptado.safeParse({
      success: true,
      message: 'Contraseña restablecida correctamente.',
      redirect: '/login?reset=1',
    }).success,
  ).toBe(true);
  expect(
    EsquemaRestablecimientoAceptado.safeParse({
      success: true,
      message: 'otro mensaje',
      redirect: '/login?reset=1',
    }).success,
  ).toBe(false);
});
