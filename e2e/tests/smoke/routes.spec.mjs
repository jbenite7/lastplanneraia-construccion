import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { loginAndSelectProject, logout } from '../../../tests/browser/support/session.mjs';
import {
  assertNoRuntimeErrors,
  installErrorCollectors,
  expectUsablePage,
} from '../../../tests/browser/support/assertions.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

test.describe('Smoke: all routes render without fatal errors', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required');
    await loginAndSelectProject(page, PROJECT);
  });

  test.afterEach(async ({ page }, testInfo) => {
    await logout(page).catch(() => {});
    const errors = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errors);
  });

  const routes = [
    { path: '/dashboard', selectors: ['body'] },
    { path: '/programa-general', selectors: ['.handsontable', '.htCore', 'body'] },
    { path: '/programacion-intermedia', selectors: ['.handsontable', '.htCore', 'body'] },
    { path: '/programacion-semanal', selectors: ['.handsontable', '.htCore', 'body'] },
    { path: '/programacion-semanal/cnp', selectors: ['#dt_cliente', 'body'] },
    { path: '/programacion-semanal/cnc', selectors: ['#dt_cliente', 'body'] },
    { path: '/programacion-semanal/cic', selectors: ['#dt_cliente', 'body'] },
    // Podadas el 2026-08-04: /listado-actividades, /contratos y /pdc se eliminaron con el PDC v1.
    // El humo las seguia visitando y fallaba con «No usable selector found for /listado-actividades».
    { path: '/indicadores', selectors: ['body'] },
    { path: '/control-cambios', selectors: ['.handsontable', '#dt_cliente', 'body'] },
  ];

  for (const route of routes) {
    test(`route ${route.path} renders without fatal errors`, async ({ page }, testInfo) => {
      const errors = installErrorCollectors(page);
      attachAssertionCollector(errors);
      testInfo._e2eErrors = errors;

      await expectUsablePage(page, route.path, route.selectors);
      await expect(page.locator('body')).toBeVisible({ timeout: 10000 });

      if (route.path !== '/dashboard') {
        const bodyText = await page.locator('body').textContent().catch(() => '');
        expect(bodyText, `${route.path} must not show fatal error`).not.toContain('Fatal error');
        // "500 Internal Server Error" or "Error 500" only — ignore "500 caracteres" etc
        expect(bodyText, `${route.path} must not show internal server error`).not.toMatch(/\b500\s*(Internal|Error|Server)/i);
      }

      assertNoRuntimeErrors(errors);
    });
  }
});