// Fixtures compartidos para los specs de errores/accesibilidad del shell React (Tarea 8, T01).
// Nunca tocan el backend real: `interceptarRed()` cubre `/api/session` y deja cualquier otra
// petición `/api/**` como "inesperada" (falla el test) salvo que el caller registre su propia
// ruta ANTES de navegar — mismo patrón que `shell-control-actividad.spec.mjs`.

export const CSRF = 'a'.repeat(64);

export function bootstrapAutenticado(overrides = {}) {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Residente QA', role: 'R' },
    project: { id: 73, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: {
      bi: null,
      groups: [
        {
          id: 'obra',
          label: 'Obra',
          items: [
            { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false },
            { id: 'indicadores', label: 'Indicadores LPS', href: '/indicadores', icon: 'chart', action: false },
          ],
        },
      ],
    },
    week: {
      current: 6,
      options: [{ number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' }],
      actions: { select: false, create: false, deleteLast: false },
    },
    csrfToken: CSRF,
    ...overrides,
  };
}

export function bootstrapAnonimo(reason = 'missing_session') {
  return {
    state: 'anonymous',
    authenticated: false,
    reason,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken: CSRF,
  };
}

/**
 * Registra `/api/session` (siempre autenticado, salvo `anonimoDesde` que lo apaga) y un
 * catch-all de `/api/**` que aborta cualquier petición no anticipada — así una fuga de fetch sin
 * interceptar rompe el test en vez de tocar el backend real. `session/touch` se intercepta con
 * éxito por defecto para que `ControlActividad` no interfiera en specs que no lo ejercitan.
 */
export async function interceptarRed(page, { proyectoInicial = 'Da Porto' } = {}) {
  const estado = { autenticado: true, proyecto: proyectoInicial };
  const requests = [];
  const paginaErrors = [];
  const consolaErrors = [];

  page.on('pageerror', (error) => paginaErrors.push(String(error)));
  page.on('console', (msg) => {
    if (msg.type() === 'error') consolaErrors.push(msg.text());
  });

  await page.route('**/api/**', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method(), inesperado: true });
    await route.abort('failed');
  });

  await page.route('**/session/touch', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method() });
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
    });
  });

  await page.route('**/api/session', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method() });
    const cuerpo = estado.autenticado
      ? bootstrapAutenticado({ project: { id: 73, name: estado.proyecto, area: 'Construccion' } })
      : bootstrapAnonimo();
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(cuerpo) });
  });

  return { estado, requests, paginaErrors, consolaErrors };
}
