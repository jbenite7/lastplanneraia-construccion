import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx';

test('importar maestro SINCO: preview, confirmación y catálogo poblado', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/maestro', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });

    await page.locator('[data-testid="pdc-maestro-import-file"]').setInputFiles(FIXTURE);
    const resumen = page.locator('[data-testid="pdc-maestro-import-resumen"]');
    await expect(resumen).toContainText('5 insumos activos', { timeout: 20000 });

    await page.locator('[data-testid="pdc-maestro-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // El catálogo global muestra un insumo del fixture (idempotente ante re-corridas).
    const catalogo = page.locator('[data-testid="pdc-maestro-catalogo"]');
    await page.locator('[data-testid="pdc-maestro-busqueda"]').fill('PISO CERAMICO');
    await expect(catalogo.locator('.ag-cell', { hasText: 'PISO CERAMICO 30X30' }).first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
