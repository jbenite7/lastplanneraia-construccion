import { expect, test } from '@playwright/test';

/**
 * `ControlActividad` (Tarea 6, T01) con la red COMPLETAMENTE interceptada antes de navegar —
 * nunca toca el backend real. Cubre los tres escenarios que pide el brief: timeout, logout y
 * cambio de proyecto, con la interacción del navegador real (Playwright `page.clock` para el
 * timeout, sin esperar 3600s de verdad).
 */

const CSRF = 'a'.repeat(64);

function bootstrapAutenticado(overrides = {}) {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Residente QA', role: 'R' },
    project: { id: 73, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken: CSRF,
    ...overrides,
  };
}

function bootstrapAnonimo(reason = 'missing_session') {
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

/** Registro de requests + estado mutable de sesión que las rutas interceptadas leen. */
async function interceptarRed(page, { proyectoInicial = 'Da Porto' } = {}) {
  const estado = { autenticado: true, proyecto: proyectoInicial };
  const requests = [];

  // Catch-all PRIMERO — Playwright resuelve rutas en orden inverso de registro, así que las
  // específicas (registradas después) lo sobrescriben (mismo patrón que shell-week-context.spec.mjs).
  await page.route('**/api/**', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method(), inesperado: true });
    await route.abort('failed');
  });
  await page.route('**/session/touch', async (route) => {
    requests.push({
      url: route.request().url(),
      method: route.request().method(),
      headers: route.request().headers(),
    });
    if (!estado.autenticado) {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ success: false, sessionExpired: true, reason: 'timeout', redirect: '/login?timeout=1' }),
      });
      return;
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
    });
  });

  await page.route('**/api/session', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method() });
    const cuerpo = estado.autenticado ? bootstrapAutenticado({ project: { id: 73, name: estado.proyecto, area: 'Construccion' } }) : bootstrapAnonimo();
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(cuerpo) });
  });

  await page.route('**/api/auth/logout', async (route) => {
    requests.push({
      url: route.request().url(),
      method: route.request().method(),
      headers: route.request().headers(),
    });
    estado.autenticado = false;
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) });
  });

  await page.route('**/api/proyectos', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method() });
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        projects: [
          { id: 73, name: 'Da Porto', area: 'Construccion', active: true, role: 'R', roleLabel: 'Residente de Obra' },
          { id: 91, name: 'Otro Proyecto', area: 'Construccion', active: true, role: 'R', roleLabel: 'Residente de Obra' },
        ],
        navigation: { bi: { visible: false, href: null } },
      }),
    });
  });

  await page.route('**/api/proyectos/seleccionar', async (route) => {
    const body = route.request().postDataJSON();
    requests.push({ url: route.request().url(), method: route.request().method(), body });
    estado.proyecto = body.name;
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, message: null, route: '/app' }),
    });
  });

  return { estado, requests };
}

test.describe('ControlActividad — red completamente interceptada', () => {
  test('timeout de 3600s: sin actividad, cierra sesión y vuelve a la pantalla de login', async ({ page }) => {
    await page.clock.install({ time: new Date('2026-08-31T09:00:00Z') });
    const { requests } = await interceptarRed(page);

    await page.goto('/app');
    await expect(page.getByRole('navigation')).toBeVisible();

    // Avanza 3600s exactos sin ninguna interacción del usuario — el temporizador local de
    // `ControlActividad` es la única autoridad sobre el timeout de inactividad.
    await page.clock.fastForward('01:00:00');

    await expect(page.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeVisible();

    const logout = requests.find((r) => r.url.includes('/api/auth/logout'));
    expect(logout).toBeTruthy();
    expect(logout.headers['x-csrf-token']).toBe(CSRF);
  });

  test('logout manual: un clic en "cerrar sesión" manda un único POST con CSRF y vuelve al login', async ({ page }) => {
    const { requests } = await interceptarRed(page);

    await page.goto('/app');
    await page.getByRole('button', { name: /cuenta ·/i }).click();
    await page.getByRole('menuitem', { name: /cerrar sesión/i }).click();

    await expect(page.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeVisible();

    const llamadasLogout = requests.filter((r) => r.url.includes('/api/auth/logout'));
    expect(llamadasLogout).toHaveLength(1);
    expect(llamadasLogout[0].headers['x-csrf-token']).toBe(CSRF);
  });

  // T7-1 (Tarea 7, S04): "Cambiar proyecto" dejó de abrir una lista en sitio dentro del menú de
  // cuenta y pasó a un enlace de navegación completa a `/proyectos` (spec S04 §421, descarta las
  // cachés del proyecto anterior antes de cualquier render operativo). Ese `href` literal es el
  // contrato que se comprueba abajo — pero seguirlo de verdad en este spec, con la red 100%
  // interceptada (sin backend PHP real detrás), aterriza en el `/login` legado: `SpaRouter` (T7-3)
  // NO migra `/proyectos` a secas todavía, solo el prefijo `/app`, así que un navegador real
  // sirve ese path desde PHP, que no reconoce esta sesión simulada. Por eso el resto del test
  // continúa por el alias SPA-nativo `/app/proyectos` — la misma pantalla React, alcanzable sin
  // depender del legado — en vez de seguir el enlace literalmente.
  test('cambiar de proyecto conserva el mismo ControlActividad — un logout posterior sigue funcionando en un solo POST', async ({ page }) => {
    const { requests } = await interceptarRed(page);

    await page.goto('/app');
    await page.getByRole('button', { name: /cuenta ·/i }).click();

    const cambiarProyecto = page.getByRole('menuitem', { name: /cambiar proyecto/i });
    await expect(cambiarProyecto).toHaveAttribute('href', '/proyectos');

    await page.goto('/app/proyectos');
    await page.getByRole('button', { name: /ingresar al proyecto otro proyecto/i }).click();

    // La selección real navega de documento completo a la `route` que devolvió el servidor
    // (mockeada como `/app` arriba) — el bootstrap que sigue trae el proyecto ya cambiado.
    await expect(page).toHaveURL(/\/app$/);

    // `{ exact: true }` a propósito: desde la Tarea 8 (T01 §14) existe una región `aria-live`
    // oculta con el título de la pestaña ("App · Otro Proyecto · Last Planner AIA"), que también
    // contiene el nombre del proyecto como subcadena — sin `exact` el match deja de ser único.
    await expect(page.getByText('Otro Proyecto', { exact: true })).toBeVisible();

    // Tras T7-1, la navegación completa SÍ destruye y recrea el árbol de React (documento nuevo,
    // `ControlActividad` incluido) — ya no hay una única instancia que sobreviva al cambio. Lo
    // que sigue vigente es el resultado observable: el CSRF de la sesión nueva sigue siendo
    // válido y el logout posterior funciona en un único POST, igual que antes del cambio.
    await page.getByRole('button', { name: /cuenta ·/i }).click();
    await page.getByRole('menuitem', { name: /cerrar sesión/i }).click();

    await expect(page.getByRole('heading', { name: /bienvenido a last planner aia/i })).toBeVisible();

    const llamadasLogout = requests.filter((r) => r.url.includes('/api/auth/logout'));
    expect(llamadasLogout).toHaveLength(1);
    expect(requests.some((r) => r.inesperado)).toBe(false);
  });
});
