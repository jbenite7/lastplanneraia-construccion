import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('visor: árbol expandible del presupuesto activo con insumos y totales', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Garantizar una versión activa: importar el fixture (idempotente para el visor).
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // Ir al visor.
    await page.locator('nav >> text=Presupuesto').click();
    await expect(page.locator('h1')).toContainText('Presupuesto', { timeout: 15000 });
    const arbol = page.locator('[data-testid="pdc-visor-arbol"]');

    // Colapsado: capítulos con total roll-up.
    const cap = arbol.locator('.ag-cell', { hasText: 'PRELIMINARES' }).first();
    await expect(cap).toBeVisible({ timeout: 15000 });
    await expect(arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' })).toHaveCount(0);

    // Expandir cadena: 01 → 01.01 → 01.01.01 → actividad → insumos.
    await cap.click();
    await arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO' }).first().click();
    await arbol.locator('.ag-cell', { hasText: 'INSTALACIONES' }).first().click();
    await arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' }).first().click();
    await expect(arbol.locator('.ag-cell', { hasText: 'TEJA DE ZINC' }).first()).toBeVisible();
    await expect(arbol.locator('.ag-cell', { hasText: '$ 540.000' }).first()).toBeVisible();

    // Selector de versión presente.
    await expect(page.locator('[data-testid="pdc-visor-version"]')).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
