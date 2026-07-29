import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
// Dos fixtures distintos: re-importar el mismo archivo no crea versión nueva (versionamiento
// inteligente), así que no habría nada que comparar. Ver la nota en pdc-v2-visor.spec.mjs.
const FIXTURE_V1 = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
const FIXTURE_V2 = 'tests/browser/fixtures/pdc/presupuesto-mini-v2.xlsx';

// Escribe presupuestos de juguete: va contra el proyecto sacrificable «PDC Sandbox E2E», que se
// resetea antes de cada test.
usarSandboxPdc();

test('comparativo: dos versiones muestran resumen y ejes', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    // Prep: garantizar ≥2 versiones importadas (mismo patrón que pdc-v2-visor.spec.mjs).
    for (const [fixture, etiqueta] of [[FIXTURE_V1, 'PI_TEST_1'], [FIXTURE_V2, 'PI_TEST_2']]) {
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.locator('[data-testid="pdc-import-file"]').setInputFiles(fixture);
      await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText(etiqueta, { timeout: 20000 });
      await page.locator('[data-testid="pdc-import-confirmar"]').click();
      await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });
    }

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

/**
 * Una versión importada por el parser anterior al fix A1.8 tiene los insumos inflados por 1/Cant APU:
 * en DAPORTO eso hace que el comparativo muestre una caída de $45 mil millones que nunca ocurrió.
 * El aviso tiene que estar ANTES del resumen para que nadie lea ese Δ como un cambio del presupuesto.
 */
test('comparativo: una versión obsoleta se advierte antes del resumen', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    for (const [fixture, etiqueta] of [[FIXTURE_V1, 'PI_TEST_1'], [FIXTURE_V2, 'PI_TEST_2']]) {
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.locator('[data-testid="pdc-import-file"]').setInputFiles(fixture);
      await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText(etiqueta, { timeout: 20000 });
      await page.locator('[data-testid="pdc-import-confirmar"]').click();
      await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });
    }

    // Se marca a mano porque el fixture del sandbox es sano: lo que se prueba es la advertencia,
    // no la detección (esa la cubre la migración, que corre el diagnóstico sobre datos reales).
    const motivo = 'Importada con el bug A1.8: las cifras no son comparables.';
    sqlEnApp(
      `$db->query("UPDATE pdc_presupuesto_versiones SET obsoleta = 1, obsoleta_motivo = ?, `
      + `obsoleta_marcada_at = NOW() WHERE project_id = ? AND version_numero = 1", `
      + `[${JSON.stringify(motivo)}, ${project.projectId}]);`,
    );

    await page.goto('/plan-compras#/ensamble/comparar', { waitUntil: 'domcontentloaded' });
    const aviso = page.locator('[data-testid="pdc-cmp-aviso-obsoleta"]');
    await expect(aviso).toBeVisible({ timeout: 15000 });
    await expect(aviso).toContainText('no es confiable');
    await expect(aviso).toContainText(motivo);
    await expect(aviso).toHaveAttribute('role', 'alert');

    // El selector avisa desde antes de elegir.
    await expect(page.locator('[data-testid="pdc-cmp-version-a"]')).toContainText('(obsoleta)');

    // El aviso precede al resumen en el DOM: se lee primero.
    const resumen = page.locator('[data-testid="pdc-cmp-resumen"]');
    await expect(resumen).toBeVisible({ timeout: 15000 });
    const avisoPrimero = await page.evaluate(() => {
      const a = document.querySelector('[data-testid="pdc-cmp-aviso-obsoleta"]');
      const r = document.querySelector('[data-testid="pdc-cmp-resumen"]');
      return !!(a && r) && (a.compareDocumentPosition(r) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
    });
    expect(avisoPrimero).toBe(true);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
