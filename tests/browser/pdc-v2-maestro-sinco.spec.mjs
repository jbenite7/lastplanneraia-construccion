import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx';

test('importar maestro SINCO: preview, confirmación y catálogo poblado', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  // MUTANTE sobre datos compartidos (no destructivo: el import es upsert, nunca desactiva ni borra
  // lo ausente). Escribe en `general_maestro_insumos`, catálogo GLOBAL de toda la empresa —no se
  // aísla por project_id—, así que siembra ahí los insumos de juguete del fixture y quedan con
  // activo=1 participando del auto-match de cualquier proyecto. Peor: por la rama de huérfanas de
  // MaestroSincoImportService, si una `descripcion_norm + unidad` del fixture coincide con un insumo
  // REAL sin codigo_sinco (así nacen los creados desde el maestro en el cold start), le estampa el
  // código SINCO de prueba y le pisa descripción, agrupación, tipo de recurso y valor unitario.
  // Nada de esto se restaura solo: hay que retirar los insumos a mano (activo=0) desde el catálogo.
  // Corre solo con la variable puesta:
  //   PDC_E2E_DESTRUCTIVO=1 npx playwright test tests/browser/pdc-v2-maestro-sinco.spec.mjs
  test.skip(
    process.env.PDC_E2E_DESTRUCTIVO !== '1',
    'Test mutante: escribe en el catálogo global de insumos. Exporta PDC_E2E_DESTRUCTIVO=1 para correrlo.',
  );

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
