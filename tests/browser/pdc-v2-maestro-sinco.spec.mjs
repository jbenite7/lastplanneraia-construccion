import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx';
const FIXTURE_PRESUPUESTO = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

// Único spec del PDC v2 que NO se aísla cambiando de proyecto: escribe en `general_maestro_insumos`,
// el catálogo de insumos de toda la empresa, que no tiene project_id. El aislamiento aquí es por
// nomenclatura, no por proyecto:
//
//  - El fixture usa códigos `ZZTEST-90x` y descripciones `ZZTEST ...`. Eso desactiva la rama de
//    huérfanas de MaestroSincoImportService (líneas ~74-95): si una `descripcion_norm + unidad` del
//    archivo coincidiera con un insumo REAL sin codigo_sinco, el import le estamparía el código de
//    prueba y le pisaría descripción, agrupación, tipo de recurso y valor unitario. Con la marca
//    ZZTEST ninguna clave puede coincidir con nada real, así que el import solo puede INSERTAR.
//  - Lo insertado se borra al terminar (y también al resetear el sandbox, por si el test revienta):
//    solo las filas marcadas y solo si ningún presupuesto las referencia.
//
// El proyecto sacrificable sigue haciendo falta para tener sesión y contexto en la vista.
usarSandboxPdc();

function borrarInsumosDePrueba() {
  return Number(sqlEnApp(
    `$st = $db->query("DELETE m FROM general_maestro_insumos m `
    + `LEFT JOIN pdc_insumo_vinculos v ON v.maestro_id = m.id `
    + `WHERE m.codigo_sinco LIKE 'ZZTEST-%' AND v.id IS NULL"); echo $st->rowCount();`,
  ));
}

test('importar maestro SINCO: preview, confirmación y catálogo poblado', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    // La vista Maestro solo muestra el importador cuando el proyecto ya tiene un presupuesto
    // activo; sin él solo pinta «Ve a Ensamble → Importar». El sandbox arranca vacío, así que el
    // presupuesto de juguete es prerrequisito del recorrido, no parte de lo que se prueba aquí.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE_PRESUPUESTO);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.goto('/plan-compras#/ensamble/maestro', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });

    await page.getByRole('tab', { name: /Importar SINCO/ }).click();
    await page.locator('[data-testid="pdc-maestro-import-file"]').setInputFiles(FIXTURE);
    const resumen = page.locator('[data-testid="pdc-maestro-import-resumen"]');
    await expect(resumen).toContainText('5 insumos activos', { timeout: 20000 });

    await page.locator('[data-testid="pdc-maestro-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // El catálogo global muestra un insumo del fixture (idempotente ante re-corridas).
    await page.getByRole('tab', { name: /Catálogo global/ }).click();
    const catalogo = page.locator('[data-testid="pdc-maestro-catalogo"]');
    await page.locator('[data-testid="pdc-maestro-busqueda"]').fill('ZZTEST PISO CERAMICO');
    await expect(catalogo.locator('.ag-cell', { hasText: 'ZZTEST PISO CERAMICO 30X30' }).first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    // Incondicional: el catálogo es global, así que lo que este test sembró no puede quedarse ahí
    // aunque la prueba falle a mitad de camino.
    expect(borrarInsumosDePrueba(), 'el import debió dejar 5 insumos ZZTEST que borrar').toBe(5);
    await logout(page).catch(() => {});
  }
});
