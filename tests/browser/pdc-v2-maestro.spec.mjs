import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('maestro: cold start masivo y re-import con auto-match', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Import fresco para tener versión activa con vínculos regenerables.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // Maestro: la carga genera vínculos.
    await page.locator('nav >> text=Maestro').click();
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    const cobertura = page.locator('[data-testid="pdc-maestro-cobertura"]');

    // Si hay pendientes (cold start o insumos nuevos): masivo → cobertura 100%.
    const textoCob = await cobertura.innerText();
    if (!textoCob.includes('Cobertura: 100%')) {
      await page.locator('[data-testid="pdc-maestro-sel-todos"]').click();
      await page.locator('[data-testid="pdc-maestro-crear-masivo"]').click();
      await expect(cobertura).toContainText('Cobertura: 100%', { timeout: 20000 });
    }
    await expect(cobertura).toContainText('0 pendientes', { timeout: 15000 });

    // El catálogo global contiene los insumos del fixture.
    const catalogo = page.locator('[data-testid="pdc-maestro-catalogo"]');
    await expect(catalogo.locator('.ag-cell', { hasText: 'TEJA DE ZINC' }).first()).toBeVisible({ timeout: 15000 });

    // Búsqueda del catálogo.
    await page.locator('[data-testid="pdc-maestro-busqueda"]').fill('concreto');
    await expect(catalogo.locator('.ag-cell', { hasText: 'CONCRETO 4000PSI' }).first()).toBeVisible({ timeout: 15000 });

    // Retiro desde el catálogo: la fila desaparece de la vista activa.
    await page.locator('[data-testid="pdc-maestro-busqueda"]').fill('bombeo');
    const filaBombeo = catalogo.locator('.ag-row', { hasText: 'SERVICIO BOMBEO' }).first();
    await expect(filaBombeo).toBeVisible({ timeout: 15000 });
    await filaBombeo.locator('.pdc-celda-accion').click();
    await expect(page.locator('.pdc-exito')).toContainText('retirado', { timeout: 15000 });
    await expect(catalogo.locator('.ag-cell', { hasText: 'SERVICIO BOMBEO' })).toHaveCount(0, { timeout: 15000 });

    // Ver retirados → reaparece con acción Reactivar.
    await page.locator('[data-testid="pdc-maestro-ver-retirados"]').check();
    const filaRetirada = catalogo.locator('.ag-row', { hasText: 'SERVICIO BOMBEO' }).first();
    await expect(filaRetirada.locator('.pdc-celda-accion')).toHaveText('Reactivar', { timeout: 15000 });
    await filaRetirada.locator('.pdc-celda-accion').click();
    await expect(page.locator('.pdc-exito')).toContainText('reactivado', { timeout: 15000 });

    // Recargar el maestro: regenerar repone el auto-match → cobertura 100% de nuevo.
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toContainText('Cobertura: 100%', { timeout: 20000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
