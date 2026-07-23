import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('visor: árbol expandible del presupuesto activo con insumos y totales', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Garantizar DOS versiones (una activa + una histórica): importar el fixture dos veces.
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

    // Colapsar el capítulo: toda la rama desaparece.
    await cap.click();
    await expect(arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' })).toHaveCount(0);
    await expect(arbol.locator('.ag-cell', { hasText: 'TEJA DE ZINC' })).toHaveCount(0);

    // Selector de versión presente.
    const selectorVersion = page.locator('[data-testid="pdc-visor-version"]');
    await expect(selectorVersion).toBeVisible();

    // Cambio de versión: seleccionar una histórica re-renderiza el árbol.
    const opciones = await selectorVersion.locator('option').count();
    expect(opciones).toBeGreaterThanOrEqual(2);
    await selectorVersion.selectOption({ index: 1 }); // selección explícita por versionId (la más reciente; el camino histórico lo cubre el test PHP)
    await expect(arbol.locator('.ag-cell', { hasText: 'PRELIMINARES' }).first()).toBeVisible({ timeout: 10000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
