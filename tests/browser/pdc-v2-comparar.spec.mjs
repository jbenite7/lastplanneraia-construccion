import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('comparativo: dos versiones muestran resumen y ejes', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Prep: garantizar ≥2 versiones importadas (mismo patrón que pdc-v2-visor.spec.mjs):
    // importar el fixture mini dos veces por la vista Importar, sin asumir el estado previo del proyecto.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.goto('/plan-compras#/ensamble/comparar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Comparativo de versiones', { timeout: 15000 });

    // Con ≥2 versiones, la preselección A/B dispara la comparación automáticamente.
    await expect(page.locator('[data-testid="pdc-cmp-version-a"]')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-cmp-version-b"]')).toBeVisible();
    await expect(page.locator('[data-testid="pdc-cmp-resumen"]')).toBeVisible({ timeout: 15000 });

    // Toggle a Actividades y de vuelta a Insumos.
    await page.locator('[data-testid="pdc-cmp-eje-actividades"]').click();
    await expect(page.locator('[data-testid="pdc-cmp-grid"] .ag-row').first()).toBeVisible({ timeout: 15000 });
    await page.locator('[data-testid="pdc-cmp-eje-insumos"]').click();
    await expect(page.locator('[data-testid="pdc-cmp-grid"] .ag-row').first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
