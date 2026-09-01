// tests/browser/pdc-v2-duraciones-obra.spec.mjs — la obra corrige la duración de un paso.
//
// Contra el sandbox sacrificable, no contra Da Porto: corregir una duración mueve fechas reales.
// `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

usarSandboxPdc();

/** La celda «Paquete» de la fila del paquete de prueba, dentro de la grilla del plan. */
const filaDelPaquete = (page) => page.getByTestId('pdc-plan-grid')
  .locator('.ag-row').filter({ hasText: 'ZZTEST DUROBRA' }).first()
  .locator('[col-id="nombre"]');

test('la obra corrige un paso, la fecha se mueve, y vuelve al número de la empresa', async ({ page }) => {
  // Un paquete con su fila de catálogo propia: 3+2+7+4+5+10+2 = 33 días.
  const montaje = JSON.parse(sqlEnApp(
    `$db->query("INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete, `
    + `diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, `
    + `diasLegalizacionContrato, diasFabricacion, diasInsumosObra) `
    + `VALUES ('ZZTEST DUROBRA', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)"); `
    + `$ref = (int) $db->lastInsertId(); `
    + `$db->query("INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, `
    + `modalidad_contratacion, duracion_ref, activo, creado_por, created_at) `
    + `VALUES ('ZZTEST DUROBRA', 'zztest durobra', 'a_todo_costo', 'contrato', ?, 1, 'e2e-durobra', NOW())", [$ref]); `
    + `$paq = (int) $db->lastInsertId(); `
    + `$uid = (int) $db->query('SELECT unique_id FROM programa WHERE project_id = ? AND Titulo = 1 ORDER BY Consecutivo LIMIT 1', `
    + `[${project.projectId}])->fetchColumn(); `
    + `$s = new App\\Services\\Pdc\\PlanFechasService($db); `
    + `$s->amarrar(${project.projectId}, $paq, $uid, 'e2e-durobra'); `
    + `$s->calcular(${project.projectId}, 'e2e-durobra'); `
    + `echo json_encode(['paquete' => $paq, 'ref' => $ref]);`,
  ));

  const total = () => Number(sqlEnApp(
    `echo (int) $db->query('SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', `
    + `[${project.projectId}, ${montaje.paquete}])->fetchColumn();`,
  ));

  try {
    expect(total(), 'punto de partida: lo que dice el catálogo').toBe(33);

    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/ensamble/plan');

    // Desplegar el paquete abre el panel de pasos. Se localiza por `.ag-row` y no por
    // `getByRole('cell')`: AG Grid marca sus celdas como `gridcell`, que no es el rol `cell`.
    await filaDelPaquete(page).click();
    const panel = page.getByTestId('pdc-plan-detalle');
    await expect(panel).toBeVisible({ timeout: 15000 });

    // El aviso dice lo CONTRARIO que el del catálogo. Es la diferencia que evita corregir el
    // estándar de la empresa creyendo que se corrige solo esta obra.
    await expect(page.getByTestId('pdc-plan-pasos-alcance')).toContainText('son de esta obra');

    // Fabricación es el sexto paso del proceso por defecto, y `orden` arranca en 0: orden 5.
    // (El brief decía 6; la corrida lo desmintió — ese testid es «Insumos en obra», de 2 días.)
    const dias = page.getByTestId('pdc-plan-paso-dias-5');
    await expect(dias).toHaveValue('10', { timeout: 15000 });
    // El INICIO del paso, no su fin: el plan se resta hacia atrás desde la fecha de necesidad
    // (PlanFechasService::calcular()), así que alargar un paso empuja su arranque hacia atrás y deja
    // quieto el fin, que es la frontera con el paso siguiente. Mirar el fin no mediría nada.
    const fechaInicioAntes = await panel.locator('tbody tr').nth(5).locator('td').nth(2).innerText();

    await dias.fill('15');
    await dias.blur();

    // Escribir, RECARGAR y recuperar: sin la recarga esto solo probaría que React repintó.
    await page.reload();
    await filaDelPaquete(page).click();
    await expect(page.getByTestId('pdc-plan-paso-dias-5')).toHaveValue('15', { timeout: 15000 });

    expect(total(), 'el proceso se alargó los cinco días de la corrección').toBe(38);

    const fechaInicioDespues = await page.getByTestId('pdc-plan-detalle')
      .locator('tbody tr').nth(5).locator('td').nth(2).innerText();
    expect(fechaInicioDespues, 'la fecha del paso se movió de verdad').not.toBe(fechaInicioAntes);

    // Y la vuelta atrás, que es lo que hace segura la corrección.
    await page.getByTestId('pdc-plan-paso-restablecer-5').click();
    await expect(page.getByTestId('pdc-plan-paso-dias-5')).toHaveValue('10', { timeout: 15000 });
    expect(total(), 'restablecer devuelve el número de la empresa').toBe(33);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    sqlEnApp(
      `$db->query("DELETE FROM pdc_proyecto_duraciones WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_plan_paso WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_plan_paquete WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_paquete_frente WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'e2e-durobra'"); `
      + `$db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ZZTEST DUROBRA'"); echo 'ok';`,
    );
    await logout(page).catch(() => {});
  }
});
