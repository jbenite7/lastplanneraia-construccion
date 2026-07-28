import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

// Confirmar la importación deja el presupuesto de juguete como versión activa. Contra un proyecto
// real eso desactiva su presupuesto sin restaurarlo, así que el spec escribe en el proyecto
// sacrificable «PDC Sandbox E2E», que se resetea antes de cada test.
usarSandboxPdc();

test('importar presupuesto: preview, confirmación y versión activa', async ({ page }) => {
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
