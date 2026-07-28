import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('importar presupuesto: preview, confirmación y versión activa', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  // DESTRUCTIVO: confirma la importación del presupuesto de juguete en el proyecto real, lo que crea
  // una versión nueva y la deja activa, desactivando el presupuesto de DAPORTO sin restaurarlo.
  // Corre solo con la variable puesta:
  //   PDC_E2E_DESTRUCTIVO=1 npx playwright test tests/browser/pdc-v2-import.spec.mjs
  test.skip(
    process.env.PDC_E2E_DESTRUCTIVO !== '1',
    'Test destructivo: reemplaza la versión activa del proyecto. Exporta PDC_E2E_DESTRUCTIVO=1 para correrlo.',
  );

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    // La ruta raíz de la SPA redirige a Ensamble → Importar.
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });

    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);

    const resumen = page.locator('[data-testid="pdc-import-resumen"]');
    await expect(resumen).toContainText('PI_TEST_1', { timeout: 20000 });
    await expect(resumen).toContainText('2 actividades');
    await expect(resumen).toContainText('4 insumos');

    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // El historial muestra una versión activa con el label del fixture.
    const versiones = page.locator('[data-testid="pdc-import-versiones"]');
    await expect(versiones.locator('.ag-cell', { hasText: 'PI_TEST_1' }).first()).toBeVisible({ timeout: 15000 });
    await expect(versiones.locator('.ag-cell', { hasText: 'Activa' }).first()).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
