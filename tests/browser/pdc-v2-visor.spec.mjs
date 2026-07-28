import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
// Dos fixtures, no uno importado dos veces: el versionamiento inteligente NO crea una versión nueva
// cuando el contenido es idéntico a la activa (ver PresupuestoImportService::confirmar), así que el
// segundo cargue del mismo archivo dejaría una sola versión y el selector no tendría qué comparar.
// `-v2` solo cambia la cantidad de LOSA MACIZA E=12 (40 → 45) y la etiqueta de versión; la rama de
// PRELIMINARES —la que asierta este spec— es idéntica en ambos.
const FIXTURE_V1 = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
const FIXTURE_V2 = 'tests/browser/fixtures/pdc/presupuesto-mini-v2.xlsx';

// Importar deja el presupuesto de juguete como versión activa. Contra un proyecto real eso vacía
// «Presupuesto» y «Paquetes» sin restaurarlos, así que el spec escribe en el proyecto sacrificable
// «PDC Sandbox E2E», que se resetea antes de cada test.
usarSandboxPdc();

test('visor: árbol expandible del presupuesto activo con insumos y totales', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    // Garantizar DOS versiones (una activa + una histórica).
    for (const [fixture, etiqueta] of [[FIXTURE_V1, 'PI_TEST_1'], [FIXTURE_V2, 'PI_TEST_2']]) {
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.locator('[data-testid="pdc-import-file"]').setInputFiles(fixture);
      await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText(etiqueta, { timeout: 20000 });
      await page.locator('[data-testid="pdc-import-confirmar"]').click();
      await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });
    }

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
    await expect(arbol.locator('.ag-cell', { hasText: 'ZZTEST TEJA DE ZINC' }).first()).toBeVisible();
    // Total del insumo = cant_apu × rendimiento × cantidad de la actividad × VrUnit:
    // 1,05 × 1,2 × 18 m2 = 22,68 m2 × $ 25.000 = $ 567.000. (El valor anterior, «$ 540.000», no
    // corresponde a este fixture y nunca llegó a comprobarse: el spec vivía apagado tras el gate.)
    await expect(arbol.locator('.ag-cell', { hasText: '$ 567.000' }).first()).toBeVisible();

    // Colapsar el capítulo: toda la rama desaparece.
    await cap.click();
    await expect(arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' })).toHaveCount(0);
    await expect(arbol.locator('.ag-cell', { hasText: 'ZZTEST TEJA DE ZINC' })).toHaveCount(0);

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
