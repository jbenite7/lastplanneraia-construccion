import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';
import { elegirEnSelector } from './support/pdc-selector.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
// Debe coincidir con `PDC_SANDBOX_FRENTE_PLAN` en database/seeds/pdc_e2e_sandbox_project.php.
const PAQUETE_PLAN = 'ZZTEST PAQUETE PLAN';
// Uno de los cinco miembros que el seed mete en project_members del sandbox.
const RESPONSABLE = 'Test Residente — Residente de Obra';

usarSandboxPdc();

function responsableGuardado(projectId) {
  const out = sqlEnApp(
    `$row = $db->query('SELECT responsable_user_id, responsable_asignado_por FROM pdc_plan_paquete `
    + `WHERE project_id = ? AND responsable_user_id IS NOT NULL LIMIT 1', [${projectId}])`
    + `->fetch(PDO::FETCH_ASSOC); echo json_encode($row ?: null);`,
  );
  return JSON.parse(out);
}

// La regresión que cubre: `responsable()` seguía haciendo UPDATE contra la columna `responsable`,
// eliminada por 20260728_pdc_v2_responsable_usuario.sql, así que asignar desde la interfaz fallaba
// SIEMPRE con «Unknown column 'responsable'». Un test que solo mirase la pantalla no lo habría
// visto: la edición es optimista y la celda cambia igual. Por eso se comprueba contra la base.
test('plan: el responsable se elige de la gente del proyecto y se guarda', async ({ page }) => {
  expect(responsableGuardado(project.projectId), 'el sandbox debe empezar sin responsables').toBeNull();

  await loginAndSelectProject(page, project);
  try {
    // Mismo montaje por interfaz que pdc-v2-plan.spec.mjs: hacen falta presupuesto importado,
    // vínculos del maestro e insumo asignado para que exista un paquete que el motor pueda amarrar.
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

    // «Sin frente» es una pestaña desde la revisión de UX (f28).
    await page.getByRole('tab', { name: /Sin frente/ }).click();
    const filaConSugerencia = page.locator('[data-testid="pdc-plan-sin-frente"] li:has(.pdc-paq-tag)').first();
    await expect(filaConSugerencia).toBeVisible({ timeout: 20000 });
    await filaConSugerencia.locator('button[data-testid^="pdc-plan-amarrar-"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    // A diferencia de pdc-v2-plan.spec.mjs, aquí SÍ se recalcula: sin fila en pdc_plan_paquete no
    // hay a quién asignarle un responsable (el endpoint respondería PAQUETE_SIN_PLAN).
    await page.locator('[data-testid="pdc-plan-recalcular"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 30000 });

    await page.getByRole('tab', { name: /^Plan/ }).click();
    const grid = page.locator('[data-testid="pdc-plan-grid"]');
    const celda = grid.locator('.ag-row').first().locator('[col-id="responsable"]');
    await expect(celda).toBeVisible({ timeout: 20000 });
    await expect(celda, 'el paquete recién calculado arranca sin responsable').toHaveText('');

    // UN SOLO clic, no un doble: desde la revisión de UX la grilla usa `singleClickEdit`. Con un
    // dblclick el primer clic abre el editor y el segundo cae ya sobre el desplegable y despliega
    // la lista, que queda tapando el control — el clic siguiente se estrellaba contra el popup
    // («subtree intercepts pointer events»). Este clic único es además la prueba de que basta uno.
    await celda.click();
    // AG Grid 36 no usa un <select> nativo: `agSelectCellEditor` monta su propio widget
    // (`.ag-cell-editor.ag-select`) y despliega las opciones en un popup `.ag-select-list` que
    // cuelga FUERA del contenedor del grid — por eso la lista se busca en `page`, no en `grid`.
    // Verificado en el DOM real; si esta clase cambia con una subida de versión, lo que hay que
    // ajustar es el selector, nunca la comprobación contra la base de más abajo.
    const editor = grid.locator('.ag-cell-editor.ag-select');
    await expect(editor, 'la celda debe abrir un desplegable, no un campo de texto')
      .toBeVisible({ timeout: 10000 });

    await editor.locator('.ag-picker-field-wrapper').click();
    // La lista sale del proyecto: si el endpoint nuevo fallara, no habría ninguna opción con este
    // nombre y esta línea moriría aquí en vez de guardar un valor inventado.
    await page.locator('.ag-select-list .ag-list-item', { hasText: RESPONSABLE }).click();

    await expect(celda).toHaveText(RESPONSABLE, { timeout: 15000 });
    await expect(page.locator('.pdc-error')).toHaveCount(0);

    // La prueba de verdad: que llegó a la base. La celda cambia igual aunque el POST falle.
    await expect.poll(
      () => responsableGuardado(project.projectId),
      { timeout: 15000, message: 'el responsable elegido debe quedar guardado en pdc_plan_paquete' },
    ).not.toBeNull();

    const guardado = responsableGuardado(project.projectId);
    expect(Number(guardado.responsable_user_id), 'responsable_user_id guardado').toBeGreaterThan(0);
    expect(guardado.responsable_asignado_por, 'se registra quién hizo la asignación').not.toBe('');

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
