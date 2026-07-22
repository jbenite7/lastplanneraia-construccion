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

const AGGREGATOR = 'link[href^="/runtime/css/aia-design-system.css"]';
const CORE = 'link[href^="/runtime/css/design-system/entrypoints/core.css"]';

// Superficies migradas: core + adjuntos declarados, nunca el agregador ni CSS de grilla.
async function expectSegmentedHead(page, { attachments }) {
  await expect(page.locator(CORE)).toHaveCount(1);
  await expect(page.locator(AGGREGATOR)).toHaveCount(0);
  for (const vendor of ['jquery-ui', 'anychart', 'select2', 'sweetalert2', 'handsontable']) {
    const locator = page.locator(`link[href^="/runtime/css/design-system/entrypoints/attach-${vendor}.css"]`);
    await expect(locator, `attach-${vendor}`).toHaveCount(attachments.includes(vendor) ? 1 : 0);
  }
  await expect(page.locator('link[href*="handsontable-module.css"]')).toHaveCount(0);
}

test('the 15 shared-head consumers load the canonical entrypoint', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const route of routes) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
      await expect(
        page.locator(AGGREGATOR),
        `${route} must load the canonical entrypoint once`,
      ).toHaveCount(1);
      await expect(page.locator(CORE), `${route} must not load the segmented core`).toHaveCount(0);
      expect(await page.locator('body').innerText()).not.toContain('Fatal error');
    }
  } finally {
    await logout(page).catch(() => {});
  }
});

test('project selector loads the segmented core without grid vendors', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    await page.goto('/proyectos', { waitUntil: 'domcontentloaded' });
    await expectSegmentedHead(page, { attachments: [] });
  } finally {
    await logout(page).catch(() => {});
  }
});
