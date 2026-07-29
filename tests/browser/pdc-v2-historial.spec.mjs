import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
// Dos fixtures con contenido distinto: el versionamiento inteligente no crea versión nueva si el
// contenido es idéntico al de la activa, y aquí hacen falta DOS para comparar y para poder cambiar
// cuál rige.
const FIXTURE_V1 = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
const FIXTURE_V2 = 'tests/browser/fixtures/pdc/presupuesto-mini-v2.xlsx';

usarSandboxPdc();

/**
 * Los tres puentes que la revisión de UX pidió desde el historial de versiones (f23-f25) y la
 * elección manual de la versión oficial (f15, f19). Hasta ahora el historial era una tabla que solo
 * se miraba: para ver un presupuesto había que ir a otra pestaña y volver a elegir la versión a
 * mano, y la marca «Activa» se la llevaba siempre la última importación.
 */
test('historial: clic lleva al visor, dos marcadas comparan, y se fija la versión oficial', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    for (const fixture of [FIXTURE_V1, FIXTURE_V2]) {
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.locator('[data-testid="pdc-import-file"]').setInputFiles(fixture);
      await expect(page.locator('[data-testid="pdc-import-resumen"]')).toBeVisible({ timeout: 20000 });
      await page.locator('[data-testid="pdc-import-confirmar"]').click();
      await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });
    }

    const versiones = page.locator('[data-testid="pdc-import-versiones"]');
    const filas = versiones.locator('.ag-row');
    await expect(filas).toHaveCount(2, { timeout: 15000 });

    // f24: la casilla admite dos y bloquea la tercera. Con dos versiones solo se puede comprobar
    // que las dos marcan y que el botón se habilita; el tope lo cubre el test unitario.
    // Ojo: `[col-id=...]` también casa con la celda de CABECERA. Hay que acotar a las filas.
    const casillas = versiones.locator('.ag-row [col-id="comparar"]');
    const comparar = page.locator('[data-testid="pdc-import-comparar"]');
    await expect(comparar).toBeDisabled();
    await casillas.nth(0).click();
    await expect(comparar).toContainText('1/2');
    await expect(comparar).toBeDisabled();
    await casillas.nth(1).click();
    await expect(comparar).toContainText('2/2');
    await expect(comparar).toBeEnabled();

    // f25: «Comparar» lleva al comparador con las dos ya enfrentadas.
    await comparar.click();
    await expect(page.locator('h1')).toContainText('Comparativo', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-cmp-resumen"]')).toBeVisible({ timeout: 20000 });
    expect(page.url()).toContain('a=');
    expect(page.url()).toContain('b=');
    // f22: el comparador trae el mismo selector de nivel que el visor.
    await page.locator('[data-testid="pdc-cmp-eje-actividades"]').click();
    await expect(page.locator('[data-testid="pdc-cmp-nivel"]')).toBeVisible();

    // f23: clic en una fila del historial lleva directo al visor con esa versión, sin preguntar.
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(versiones.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await versiones.locator('.ag-row [col-id="version"]').first().click();
    await expect(page.locator('h1')).toContainText('Presupuesto', { timeout: 15000 });
    expect(page.url()).toContain('version=');

    // f15 + f19: fijar como oficial una versión que no lo es, con confirmación de por medio.
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(versiones.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    const aFijar = versiones.locator('.ag-row').filter({ hasNot: page.locator('text=Activa') }).first();
    await aFijar.locator('[col-id="oficial"]').click();

    const confirmacion = page.locator('[data-testid="pdc-import-confirmar-oficial"]');
    await expect(confirmacion).toBeVisible({ timeout: 15000 });
    await expect(confirmacion).toContainText('presupuesto oficial');
    await page.locator('[data-testid="pdc-import-oficial-confirmar"]').click();
    await expect(page.locator('[data-testid="pdc-import-aviso"]')).toContainText('Ahora rige', { timeout: 20000 });

    // Sigue habiendo exactamente una versión activa, y es otra que la de antes.
    await expect(versiones.locator('.ag-cell', { hasText: 'Activa' })).toHaveCount(1, { timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
