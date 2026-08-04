/**
 * Biblia de flujos · T1 transversal — escenarios críticos.
 *
 * Cada test() empieza por el `id` del escenario que cubre, para que un fallo apunte
 * a la línea de biblia que se incumple. Ver docs/flujos/README.md.
 *
 * Cuando uno de estos falla hay dos salidas, y ninguna es ajustar la prueba para que pase:
 * o la biblia describe mal el comportamiento (se corrige la biblia), o el código incumple
 * (hallazgo a docs/EXPERIMENTS.md).
 */
import { test, expect } from '@playwright/test';

const PROYECTO = 'Da Porto';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

/** Abre sesión por la puerta de servicio: el rol que queda es el real de project_members. */
async function entrarComo(page, cuenta, proyecto = PROYECTO) {
  const respuesta = await page.goto(
    `/dev/entrar?u=${encodeURIComponent(cuenta)}&p=${encodeURIComponent(proyecto)}`,
  );
  return respuesta;
}

test.describe('T1 · Autenticación y sesión', () => {
  test('AUTH-001 · una ruta protegida sin sesión manda al login', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/programa-general');
    await expect(page).toHaveURL(/\/login/);
  });

  test('AUTH-002 · sin sesión y con X-AIA-Expect-Json, la respuesta es 401 JSON', async ({ request }) => {
    const respuesta = await request.get('/programa-general', {
      headers: { 'X-AIA-Expect-Json': '1' },
      maxRedirects: 0,
    });

    expect(respuesta.status()).toBe(401);
    expect(respuesta.headers()['content-type']).toContain('application/json');

    const cuerpo = await respuesta.json();
    expect(cuerpo).toMatchObject({
      success: false,
      sessionExpired: true,
      reason: 'missing_session',
      redirect: '/login',
    });
  });

  test('AUTH-009 · la puerta de servicio sin proyecto aterriza en /proyectos', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/dev/entrar?u=test.R');
    await expect(page).toHaveURL(/\/proyectos/);
  });
});

test.describe('T1 · Selección de proyecto', () => {
  test('PROY-005 · entrar por la puerta deja proyecto y project_id en sesión', async ({ page }) => {
    await entrarComo(page, 'test.R');
    // Si el proyecto quedó en sesión, una ruta operativa responde sin rebotar al selector.
    await page.goto('/programa-general');
    await expect(page).not.toHaveURL(/\/proyectos/);
    await expect(page).not.toHaveURL(/\/login/);
  });

  test('PROY-006 · la puerta de servicio no concede permisos por encima de la cuenta', async ({ page }) => {
    // test.V es Visualizador en project_members: entrar por la puerta debe dejarlo Visualizador,
    // no elevarlo. Se comprueba por su efecto observable: no ve acciones de escritura.
    await entrarComo(page, 'test.V');
    await page.goto('/programa-general');
    await expect(page).not.toHaveURL(/\/login/);

    const acciones = page.getByRole('button', { name: /crear|eliminar|guardar/i });
    expect(await acciones.count()).toBe(0);
  });
});

test.describe('T1 · Capacidades por rol', () => {
  test('RBAC-001 · Residente gestiona semanas en /programa-general y Visualizador no', async ({ page }) => {
    // Los controles viven dentro del flyout «Semanas del Proyecto»: hay que abrirlo.
    // Identificadores estables medidos el 2026-08-04: #shellWeekCreateOpen y .shell-week-flyout__delete.
    async function abrirPanelSemanas() {
      await page.goto('/programa-general');
      await page.getByRole('button', { name: /Semanas del Proyecto/i }).click();
      await expect(page.locator('.shell-week-flyout__item').first()).toBeVisible();
    }

    await entrarComo(page, 'test.R');
    await abrirPanelSemanas();
    await expect(page.locator('#shellWeekCreateOpen')).toBeVisible();
    await expect(page.locator('.shell-week-flyout__delete')).toBeVisible();

    await entrarComo(page, 'test.V');
    await abrirPanelSemanas();
    await expect(page.locator('#shellWeekCreateOpen')).toHaveCount(0);
    await expect(page.locator('.shell-week-flyout__delete')).toHaveCount(0);
  });

  test('RBAC-D · la matriz del cliente coincide con la del servidor para el rol en sesión', async ({ page }) => {
    await entrarComo(page, 'test.V');
    await page.goto('/programa-general');

    // rbac_capabilities.js reimplementa en JS las reglas de RbacManager.
    // Un Visualizador no debe poder gestionar semanas por ninguna de las dos vías.
    const puedeGestionarSemanas = await page.evaluate(() => {
      if (typeof window.RbacCapabilities?.canManageWeeks !== 'function') return null;
      const rol = window.RbacCapabilities.resolveRole?.() ?? window.USER_ROLE ?? 'V';
      return window.RbacCapabilities.canManageWeeks(rol);
    });

    test.skip(puedeGestionarSemanas === null, 'RbacCapabilities no expuesto en esta vista');
    expect(puedeGestionarSemanas).toBe(false);
  });
});
