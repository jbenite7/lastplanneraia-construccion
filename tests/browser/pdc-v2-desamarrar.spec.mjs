import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';
import { elegirEnSelector } from './support/pdc-selector.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
// Debe coincidir con `PDC_SANDBOX_FRENTE_PLAN` en database/seeds/pdc_e2e_sandbox_project.php: el
// nombre es lo que hace que el motor proponga ese frente con confianza alta.
const PAQUETE_PLAN = 'ZZTEST PAQUETE PLAN';

usarSandboxPdc();

function amarresDelProyecto(projectId) {
  const out = sqlEnApp(
    `$rows = $db->query('SELECT paquete_id FROM pdc_paquete_frente WHERE project_id = ?', [${projectId}])`
    + `->fetchAll(PDO::FETCH_COLUMN); echo implode(',', $rows);`,
  );
  return out === '' ? [] : out.split(',').map(Number);
}

function responsableGuardado(projectId, paqueteId) {
  return sqlEnApp(
    `$v = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', `
    + `[${projectId}, ${paqueteId}])->fetchColumn(); echo $v === false ? 'sin-fila' : (string) $v;`,
  );
}

/**
 * El hallazgo más serio de la revisión: amarrar un paquete a un frente era una decisión sin retorno
 * desde la interfaz. No había forma de cambiar el frente (la columna era texto plano y el selector
 * solo existía en «Sin frente», donde un paquete amarrado ya no aparece) ni de deshacer el amarre
 * (no existía en ninguna capa).
 *
 * Este spec MUTA: amarra, recalcula, asigna responsable y desamarra, todo contra el proyecto
 * sacrificable «PDC Sandbox E2E», que el seed resetea antes de cada test.
 */
test('plan: desamarrar devuelve el paquete a «Sin frente» y le conserva el responsable', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    // Montaje por la interfaz, igual que pdc-v2-plan.spec.mjs: el bloque «Sin frente» se alimenta
    // del resumen de paquetes, que solo cuenta insumos de la versión ACTIVA. Hacen falta las tres
    // cosas —presupuesto importado, vínculos del maestro generados y un insumo asignado a un
    // paquete— para que exista un paquete al que amarrarle un frente.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Paquetes').click();
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    // El bloque de crear paquete vive plegado desde julio de 2026 (le costaba una barra de alto
    // a la tabla): hay que desplegarlo antes de usarlo.
    await page.locator('.pdc-paq-crear-plegable > summary').click();
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill(PAQUETE_PLAN);
    await elegirEnSelector(page, 'pdc-paq-crear-tipo', 'A todo costo (Sum. + Inst.)');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    const gridPaquetes = page.locator('[data-testid="pdc-paq-grid"]');
    await elegirEnSelector(page, 'pdc-paq-filtro', 'Sin asignar');
    await expect(gridPaquetes.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await gridPaquetes.locator('.ag-row').first().click();
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });

    // Amarrar el primero de «Sin frente» y recalcular, para tener una fila real en la grilla.
    await page.getByRole('tab', { name: /Sin frente/ }).click();
    const sinFrente = page.locator('[data-testid="pdc-plan-sin-frente"]');
    await expect(sinFrente).toBeVisible({ timeout: 20000 });
    // El testid del Selector de fila es `pdc-plan-frente-<clave>` (paquete:lote): dinámico, así que
    // se localiza por prefijo en vez de por un valor fijo — mismo criterio que el botón «Amarrar».
    const primero = sinFrente.locator('li:has([data-testid^="pdc-plan-frente-"])').first();
    await expect(primero).toBeVisible({ timeout: 20000 });
    const testidFrente = await primero.locator('[data-testid^="pdc-plan-frente-"]').getAttribute('data-testid');
    await primero.locator(`[data-testid="${testidFrente}"]`).click();
    const popupFrente = page.locator('.pdc-selector-popup');
    await popupFrente.waitFor({ state: 'visible' });
    // Elige el primero de la lista: mismo criterio que `selectOption({ index: 1 })` sobre el
    // `<select>` nativo, cuya opción 0 era el placeholder «Elegir frente…» (el Selector no la
    // repite dentro del popup, así que aquí la primera opción real es la de índice 0).
    await popupFrente.getByRole('option').first().click();
    await popupFrente.waitFor({ state: 'detached', timeout: 5000 });
    await primero.locator('button[data-testid^="pdc-plan-amarrar-"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    await page.locator('[data-testid="pdc-plan-recalcular"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 30000 });

    await page.getByRole('tab', { name: /^Plan/ }).click();
    const grid = page.locator('[data-testid="pdc-plan-grid"]');
    const fila = grid.locator('.ag-row').first();
    await expect(fila).toBeVisible({ timeout: 20000 });

    const amarrados = amarresDelProyecto(project.projectId);
    expect(amarrados.length, 'hay un amarre real que deshacer').toBeGreaterThan(0);
    const paqueteId = amarrados[0];

    // Darle responsable: es lo que no puede perderse al desamarrar.
    await fila.locator('[col-id="responsable"]').click();
    const editor = grid.locator('.ag-cell-editor.ag-select');
    await expect(editor).toBeVisible({ timeout: 10000 });
    await editor.locator('.ag-picker-field-wrapper').click();
    await page.locator('.ag-select-list .ag-list-item').nth(1).click();
    await expect.poll(() => responsableGuardado(project.projectId, paqueteId), { timeout: 15000 })
      .not.toBe('');

    const conResponsable = responsableGuardado(project.projectId, paqueteId);
    expect(Number(conResponsable), 'el paquete quedó con responsable antes de desamarrar').toBeGreaterThan(0);

    // Desamarrar, con la confirmación que dice las dos verdades.
    await grid.locator('.ag-row').first().locator('[col-id="desamarrar"]').click();
    const confirmacion = page.locator('[data-testid="pdc-plan-confirmar-desamarrar"]');
    await expect(confirmacion).toBeVisible({ timeout: 15000 });
    await expect(confirmacion).toContainText('fechas');
    await expect(confirmacion).toContainText('responsable se conserva');
    await page.locator('[data-testid="pdc-plan-desamarrar-confirmar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('Sin frente', { timeout: 20000 });

    // El amarre desapareció y las fechas con él…
    await expect.poll(() => amarresDelProyecto(project.projectId).includes(paqueteId), { timeout: 15000 })
      .toBe(false);

    // …pero el responsable sigue ahí. Era el borrado silencioso que la revisión destapó.
    expect(responsableGuardado(project.projectId, paqueteId), 'el responsable sobrevive al desamarre')
      .toBe(conResponsable);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
