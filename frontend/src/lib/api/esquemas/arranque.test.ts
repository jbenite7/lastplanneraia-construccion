import { EsquemaArranque } from './arranque';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

const usuario = { username: 'test.A', displayName: 'Ana', role: 'A' };
const proyecto = { id: 73, name: 'Da Porto' };

function arranqueValido(overrides: Record<string, unknown> = {}) {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: usuario,
    project: proyecto,
    capabilities: { canManageWeeks: true },
    navigation: { bi: null },
    week: { current: 6 },
    csrfToken,
    ...overrides,
  };
}

test('anonymous con missing_session es válido', () => {
  const resultado = EsquemaArranque.safeParse({
    state: 'anonymous',
    authenticated: false,
    reason: 'missing_session',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  expect(resultado.success).toBe(true);
});

test.each(['timeout', 'inactive', 'stale_session', 'session_unverified'] as const)(
  'anonymous con reason=%s (sesión expirada) es válido',
  (reason) => {
    const resultado = EsquemaArranque.safeParse({
      state: 'anonymous',
      authenticated: false,
      reason,
      user: null,
      project: null,
      capabilities: {},
      navigation: { bi: null },
      week: null,
      csrfToken,
    });

    expect(resultado.success).toBe(true);
  },
);

test('anonymous con una razón inventada es inválido — el vocabulario lo fija SessionMiddleware', () => {
  const resultado = EsquemaArranque.safeParse({
    state: 'anonymous',
    authenticated: false,
    reason: 'motivo_inventado',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  expect(resultado.success).toBe(false);
});

test('password_change_required no expone usuario_temp ni ningún campo de usuario', () => {
  const resultado = EsquemaArranque.safeParse({
    state: 'password_change_required',
    authenticated: false,
    reason: null,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  expect(resultado.success).toBe(true);
});

test('password_change_required con user no nulo es inválido — combinación prohibida', () => {
  const resultado = EsquemaArranque.safeParse({
    state: 'password_change_required',
    authenticated: false,
    reason: null,
    user: usuario,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  expect(resultado.success).toBe(false);
});

test('authenticated sin proyecto (autenticado_sin_proyecto) es válido con project y week en null', () => {
  const resultado = EsquemaArranque.safeParse(
    arranqueValido({ project: null, week: null }),
  );

  expect(resultado.success).toBe(true);
});

test('authenticated listo (con proyecto) es válido con week presente', () => {
  const resultado = EsquemaArranque.safeParse(arranqueValido());

  expect(resultado.success).toBe(true);
});

test('authenticated con week pero sin project es la combinación prohibida — week no puede sobrevivir sin project', () => {
  const resultado = EsquemaArranque.safeParse(
    arranqueValido({ project: null, week: { current: 6 } }),
  );

  expect(resultado.success).toBe(false);
});

test('authenticated con reason no nulo es inválido — reason solo aplica a anonymous', () => {
  const resultado = EsquemaArranque.safeParse(arranqueValido({ reason: 'timeout' }));

  expect(resultado.success).toBe(false);
});

test('authenticated con user nulo es inválido — un state=authenticated siempre trae usuario', () => {
  const resultado = EsquemaArranque.safeParse(arranqueValido({ user: null }));

  expect(resultado.success).toBe(false);
});

test('capabilities acepta cualquier booleana extensible sin lista fija', () => {
  const resultado = EsquemaArranque.safeParse(
    arranqueValido({ capabilities: { canManageWeeks: true, unaCapacidadNueva: false } }),
  );

  expect(resultado.success).toBe(true);
});

test('un state fuera del vocabulario aprobado (§8.2) es inválido', () => {
  const resultado = EsquemaArranque.safeParse(arranqueValido({ state: 'loading' }));

  expect(resultado.success).toBe(false);
});

test('un csrfToken que no sea hexadecimal de 64 caracteres es inválido en cualquier estado', () => {
  const resultado = EsquemaArranque.safeParse(arranqueValido({ csrfToken: 'invalido' }));

  expect(resultado.success).toBe(false);
});
