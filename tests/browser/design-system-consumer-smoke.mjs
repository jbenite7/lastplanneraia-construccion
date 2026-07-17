import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const routes = [
  '/contratos', '/control-cambios', '/dashboard/escalamientos', '/indicadores',
  '/listado-actividades', '/pdc', '/profesionales', '/programa-general-actualizar',
  '/programa-general', '/programacion-intermedia', '/programacion-semanal/cic',
  '/programacion-semanal/cnc', '/programacion-semanal/cnp',
  '/programacion-semanal', '/subcontratistas',
];

test('the 15 shared-head consumers load the canonical entrypoint', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const route of routes) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
      await expect(
        page.locator('link[href^="/css/aia-design-system.css"]'),
        `${route} must load the canonical entrypoint once`,
      ).toHaveCount(1);
      expect(await page.locator('body').innerText()).not.toContain('Fatal error');
    }
  } finally {
    await logout(page).catch(() => {});
  }
});
