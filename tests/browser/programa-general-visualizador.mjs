import { test, expect } from '@playwright/test';
import { loginAndSelectProject } from './support/session.mjs';

const DA_PORTO = { name: 'Da Porto' };

// Botones de la barra de Programa General que mutan datos: solo deben verse cuando
// el rol tiene capacidad de escritura (`canManageGeneralProgram`), medida en
// `.superpowers/sdd/2026-08-10-frente-1a-seguridad-y-permisos/task-6-report.md`.
// «Descargar Corte» y «Exportar CSV» son lectura y deben quedarse para todos los roles
// que ven la página.
const WRITE_ACTION_SELECTOR = '#actualizarEjecucion';
const READ_ACTION_SELECTORS = ['#descargarCorteProgramacion', '#btn-export', '#btn-refresh'];

test.describe('Programa General — toolbar por rol', () => {
  test('el Visualizador no ve acciones de escritura', async ({ page }) => {
    await loginAndSelectProject(page, DA_PORTO, { username: 'test.V' });
    await page.goto('/programa-general');
    await expect(page.locator(WRITE_ACTION_SELECTOR)).toHaveCount(0);
    for (const selector of READ_ACTION_SELECTORS) {
      await expect(page.locator(selector)).toBeVisible();
    }
  });

  test('el Residente sí ve la acción de escritura', async ({ page }) => {
    await loginAndSelectProject(page, DA_PORTO, { username: 'test.R' });
    await page.goto('/programa-general');
    await expect(page.locator(WRITE_ACTION_SELECTOR)).toBeVisible();
    for (const selector of READ_ACTION_SELECTORS) {
      await expect(page.locator(selector)).toBeVisible();
    }
  });
});
