/**
 * PDC end-to-end contract.
 *
 * The migrated module owns two Handsontable grids: the package board and the
 * definition modal. This suite intentionally avoids legacy table-row evidence.
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, postFormJson } from '../../../tests/browser/support/session.mjs';

const PROJECT = PROJECTS.find((project) => project.key === 'construction');
const PDC_TABLES = [
  'actividades',
  'pdc', 'papelera_pdc', 'semi_auto_runs', 'semi_auto_suggestions',
  'semi_auto_decisions', 'semi_auto_feedback', 'semi_auto_assistant_feedback',
  'semi_auto_learning_candidates', 'semi_auto_learning_rules',
  'semi_auto_project_config', 'semi_auto_proactive_queue',
];

async function waitForMainHot(page) {
  await expect(page.locator('#dt_cliente .ht_master.handsontable')).toBeVisible({ timeout: 30_000 });
  await expect.poll(() => page.evaluate(() => window.table?.countRows() || 0)).toBeGreaterThan(0);
}

async function openDefinitionGrid(page) {
  const response = page.waitForResponse((candidate) => (
    candidate.url().includes('/api/pdc/list')
      && new URL(candidate.url()).searchParams.get('definirContratos') === '1'
  ));
  await page.locator('#btn_definirContratosPDC').click();
  const apiResponse = await response;
  expect(apiResponse.ok()).toBe(true);
  const payload = await apiResponse.json();
  expect(payload.data.length).toBeGreaterThan(0);
  await expect(page.locator('#modalDefinirContratos')).toBeVisible();
  await expect(page.locator('#dt_definirContratos .ht_master.handsontable')).toBeVisible();
  await expect.poll(() => page.evaluate(() => window.definirHot?.countRows() || 0))
    .toBe(payload.data.length);
}

async function closeDefinitionGrid(page) {
  await page.locator('#btn_cancelar_definirContratos').click();
  await expect(page.locator('#modalDefinirContratos')).toBeHidden();
}

async function openMainEdit(page, consecutivo) {
  const selector = `#dt_cliente .ht_master button.pdc-row-action--edit[data-pdc-consecutivo="${consecutivo}"]`;
  await expect(page.locator(selector)).toHaveCount(1);
  await page.locator(selector).click();
  await expect(page.locator('#modalContrato')).toBeVisible();
  await expect(page.locator('#encabezado > #Id')).toHaveValue(String(consecutivo));
}

function pdcFingerprint() {
  const scoped = new ProjectDbSnapshot(PROJECT, ['pdc']).capture();
  const fingerprint = scoped.fingerprint();
  scoped.dispose();
  return fingerprint;
}

async function postPdcFormJson(page, body) {
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  return postFormJson(page, '/api/pdc/save', { ...body, _csrf_token: csrf || '' });
}

async function consumeExpectedConsoleStatus(runtimeErrors, status, label) {
  const fragment = `status of ${status}`;
  await expect.poll(
    () => runtimeErrors.consoleErrors.findIndex((message) => message.includes(fragment)),
    { message: `Expected console status ${status} for ${label}` },
  ).toBeGreaterThanOrEqual(0);
  const index = runtimeErrors.consoleErrors.findIndex((message) => message.includes(fragment));
  runtimeErrors.consoleErrors.splice(index, 1);
}

function consumeExpectedClientStatus(clientErrors, status, pathname) {
  const expected = `${status} ${pathname}`;
  const index = clientErrors.indexOf(expected);
  expect(index, `Expected HTTP response was not collected: ${expected}`).toBeGreaterThanOrEqual(0);
  clientErrors.splice(index, 1);
}

test.describe('PDC: Handsontable workflow', () => {
  let snapshot;
  let initialFingerprint;
  let runtimeErrors;
  let warnings;
  let requestFailures;
  let clientErrors;

  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto construction fixture is required for PDC');
    snapshot = new ProjectDbSnapshot(PROJECT, PDC_TABLES).capture();
    initialFingerprint = snapshot.fingerprint();
    runtimeErrors = installErrorCollectors(page);
    warnings = [];
    requestFailures = [];
    clientErrors = [];
    page.on('console', (message) => {
      if (message.type() === 'warning') warnings.push(message.text());
    });
    page.on('requestfailed', (request) => {
      const failure = request.failure()?.errorText || 'failed';
      if (failure !== 'net::ERR_ABORTED') requestFailures.push(`${failure} ${request.url()}`);
    });
    page.on('response', (response) => {
      if (response.status() >= 400 && response.status() < 500) {
        clientErrors.push(`${response.status()} ${new URL(response.url()).pathname}`);
      }
    });
    await loginAndSelectProject(page, PROJECT);
    await changeWeek(page, PROJECT.maxWeek, '/pdc');
    await waitForMainHot(page);
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) await page.close();
    if (snapshot) {
      if (snapshot.fingerprint() !== initialFingerprint) snapshot.restore();
      expect(snapshot.fingerprint()).toBe(initialFingerprint);
    }
    if (snapshot) snapshot.dispose();
    snapshot = null;
    assertNoRuntimeErrors(runtimeErrors);
    expect(warnings, 'console.warn messages').toEqual([]);
    expect(requestFailures, 'Failed network requests').toEqual([]);
    expect(clientErrors, 'Unexpected HTTP 4xx responses').toEqual([]);
  });

  test('keeps both HOT grids, filters cleanly, and leaves no DataTables runtime', async ({ page }) => {
    const main = await page.evaluate(() => ({
      rows: window.table?.countRows() || 0,
      headers: window.table?.getColHeader() || [],
      legacyShells: document.querySelectorAll('.dataTables_wrapper, table.dataTable').length,
      legacyScripts: [...document.scripts]
        .filter((script) => /datatables|gyrocode/i.test(script.src)).map((script) => script.src),
    }));

    expect(main.rows).toBeGreaterThan(0);
    expect(main.headers).toContain('PAQUETE DE CONTRATACION');
    expect(main.legacyShells).toBe(0);
    expect(main.legacyScripts).toEqual([]);

    const filterContract = await page.evaluate(() => {
      const hot = window.table;
      const filters = hot.getPlugin('filters');
      const before = hot.countRows();
      filters.addCondition(2, 'contains', ['__PDC_E2E_FILTER_MISS__']);
      filters.filter();
      const filtered = hot.countRows();
      filters.clearConditions();
      filters.filter();
      return { before, filtered, restored: hot.countRows() };
    });
    expect(filterContract.filtered).toBe(0);
    expect(filterContract.restored).toBe(filterContract.before);

    await openDefinitionGrid(page);
    const definition = await page.evaluate(() => ({
      rows: window.definirHot?.countRows() || 0,
      headers: window.definirHot?.getColHeader() || [],
      numericColumn: window.definirHot?.getCellMeta(0, 2)?.type,
    }));
    expect(definition.rows).toBeGreaterThan(0);
    expect(definition.headers).toEqual([
      'Modalidad de contratacion',
      'Paquete de contratacion',
      'Cantidad de contratos',
    ]);
    expect(definition.numericColumn).toBe('numeric');
  });

  test('previews, selects, applies, reloads, persists, undoes, and restores automation', async ({ page }) => {
    const packageName = `E2E PDC automation ${Date.now()}`;
    runSql(`
      INSERT INTO actividades (
        project_id, Id, codigo, actividad, actividadInicio, fechaInicio, fechaInicioProyectada,
        tipoContrato, semanaActualizacion, S1, paqueteS1, cantidadS1, confianza_deteccion
      ) VALUES (
        ${PROJECT.projectId}, 990001, 990001, 'Fixture automatización PDC', 990001,
        '2026-08-01', '2026-08-01', 'S', ${PROJECT.maxWeek},
        'Suministro de prueba', '${packageName}', 1, 95
      );
    `);
    const before = pdcFingerprint();
    const preview = page.waitForResponse((response) => (
      response.url().includes('/api/pdc/auto/preview') && response.request().method() === 'POST'
    ));
    await page.locator('#btn_pdcSemiAuto').click();
    expect((await (await preview).json()).respuesta).toBe('BIEN');
    const panel = page.locator('#semiAutoReview-pdc');
    await expect(panel).toBeVisible();
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    let applyPayload = null;
    for (let attempt = 0; attempt < 3; attempt += 1) {
      const choices = panel.locator('.sar-row-check:not(:disabled)');
      const choiceCount = await choices.count();
      expect(choiceCount).toBeGreaterThan(0);
      for (let index = 0; index < choiceCount; index += 1) {
        await choices.nth(index).setChecked(index === 0);
      }
      const applied = page.waitForResponse((response) => (
        response.url().includes('/api/pdc/auto/apply') && response.request().method() === 'POST'
      ));
      await panel.locator('.sar-btn-apply').click();
      applyPayload = await (await applied).json();
      if (applyPayload.respuesta === 'BIEN') break;
      const retried = page.waitForResponse((response) => (
        response.url().includes('/api/pdc/auto/preview') && response.request().method() === 'POST'
      ));
      await panel.locator('.sar-btn-preview').click();
      expect((await (await retried).json()).respuesta).toBe('BIEN');
      await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    }
    expect(applyPayload.respuesta, JSON.stringify(applyPayload)).toBe('BIEN');
    expect(Number(applyPayload.aplicadas)).toBeGreaterThan(0);
    const afterApply = pdcFingerprint();
    expect(afterApply).not.toBe(before);

    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    expect(pdcFingerprint()).toBe(afterApply);
    await page.locator('#btn_pdcSemiAuto').click();
    await expect(panel).toBeVisible();
    await expect(panel.locator('.sar-btn-undo')).toBeEnabled({ timeout: 90_000 });
    const undone = page.waitForResponse((response) => (
      response.url().includes('/api/pdc/auto/undo') && response.request().method() === 'POST'
    ));
    await panel.locator('.sar-btn-undo').click();
    const undoPayload = await (await undone).json();
    expect(undoPayload.respuesta, JSON.stringify(undoPayload)).toBe('BIEN');
    expect(Number(undoPayload.revertidas)).toBeGreaterThan(0);
    await expect.poll(() => pdcFingerprint(), { timeout: 90_000 }).toBe(before);
  });

  test('loads calculated edit state, cancels cleanly, saves, reloads, and restores', async ({ page }) => {
    const target = await page.evaluate(() => {
      const row = window.table.getSourceData().find((item) => Number(item.titulo) === 0);
      return { consecutivo: row.consecutivo, original: row.observacionesContrato || '' };
    });
    const sentinel = `E2E PDC modal ${Date.now()}`;

    await openMainEdit(page, target.consecutivo);
    await expect(page.locator('#estadoProceso')).not.toHaveValue('');
    await expect(page.locator('#divDeberiaProceso')).not.toHaveText('');
    await expect(page.locator('#divDiagnostico')).not.toHaveText('');
    await page.locator('#observacionesContrato').fill(sentinel);
    await page.locator('#btn_cancelar_editar').click();
    await expect(page.locator('#modalContrato')).toBeHidden();
    await openMainEdit(page, target.consecutivo);
    await expect(page.locator('#observacionesContrato')).toHaveValue(target.original);
    await page.locator('#observacionesContrato').fill(sentinel);
    const saved = page.waitForResponse((response) => (
      response.url().includes('/api/pdc/save')
        && response.request().postData()?.includes('opcion=modificar')
    ));
    await page.locator('#btn_guardar_pdc').click();
    expect(await (await saved).json()).toBe('OK');
    await expect(page.locator('#modalContrato')).toBeHidden();

    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    await openMainEdit(page, target.consecutivo);
    await expect(page.locator('#observacionesContrato')).toHaveValue(sentinel);
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(initialFingerprint);
    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    await openMainEdit(page, target.consecutivo);
    await expect(page.locator('#observacionesContrato')).toHaveValue(target.original);
    snapshot.dispose();
    snapshot = null;
  });

  test('reuses the modal grid, validates quantities, persists, reloads, and restores', async ({ page }) => {
    await openDefinitionGrid(page);
    const instanceId = await page.evaluate(() => {
      window.definirHot.__pdcE2eIdentity ??= `pdc-e2e-${Date.now()}`;
      return window.definirHot.__pdcE2eIdentity;
    });
    await closeDefinitionGrid(page);
    await openDefinitionGrid(page);
    expect(await page.evaluate(() => window.definirHot.__pdcE2eIdentity)).toBe(instanceId);
    expect(await page.locator('#dt_definirContratos .ht_master.handsontable').count()).toBe(1);

    const target = await page.evaluate(() => {
      const hot = window.definirHot;
      const row = hot.getSourceData().findIndex((item) => item.consecutivo !== '' && item.consecutivo != null);
      const item = hot.getSourceDataAtRow(row);
      return {
        row,
        original: Number(item.numeroSubcontratos),
        consecutivo: item.consecutivo,
        tipoPaquete: item.tipoPaquete,
        paqueteContratacion: item.paqueteContratacion,
      };
    });
    expect(target.row).toBeGreaterThanOrEqual(0);
    expect(Number.isInteger(target.original)).toBe(true);

    await page.evaluate(({ row, original }) => {
      window.definirHot.setDataAtCell(row, 2, Math.max(2, original + 1), 'pdc-e2e-cancel');
    }, target);
    await closeDefinitionGrid(page);
    await openDefinitionGrid(page);
    expect(await page.evaluate(({ consecutivo }) => {
      const row = window.definirHot.getSourceData().find((item) => String(item.consecutivo) === String(consecutivo));
      return Number(row?.numeroSubcontratos);
    }, target)).toBe(target.original);

    for (const value of ['', 0, -1, 1.5]) {
      const invalidResponse = await postPdcFormJson(page, {
        opcion: 'guardar_DefinirContratos',
        semana: PROJECT.maxWeek,
        numeroSubcontratos: JSON.stringify({
          numeroSubcontratos: [{ consecutivo: target.consecutivo, numeroSubcontratos: value }],
        }),
      });
      expect(invalidResponse.status).toBe(422);
      consumeExpectedClientStatus(clientErrors, 422, '/api/pdc/save');
      await consumeExpectedConsoleStatus(runtimeErrors, 422, `invalid quantity ${String(value)}`);
    }

    const invalid = await page.evaluate(({ row }) => new Promise((resolve) => {
      const hot = window.definirHot;
      hot.getCellMeta(row, 2).validator('1.5', resolve);
    }), target);
    expect(invalid).toBe(false);

    const updatedQuantity = Math.max(2, target.original + 1);
    const valid = await page.evaluate(({ row, value }) => new Promise((resolve) => {
      const hot = window.definirHot;
      hot.getCellMeta(row, 2).validator(String(value), (isValid) => {
        hot.setDataAtCell(row, 2, value, 'pdc-e2e');
        resolve(isValid);
      });
    }), { row: target.row, value: updatedQuantity });
    expect(valid).toBe(true);

    const saveResponse = page.waitForResponse((candidate) => (
      candidate.url().includes('/api/pdc/save')
        && candidate.request().postData()?.includes('opcion=guardar_DefinirContratos')
    ));
    await page.locator('#btn_guardar_definirContratos').click();
    expect(await (await saveResponse).json()).toBe('conModificaciones');
    await expect(page.locator('#modalDefinirContratos')).toBeHidden();

    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    await openDefinitionGrid(page);
    expect(await page.evaluate(({ consecutivo }) => {
      const row = window.definirHot.getSourceData().find((item) => String(item.consecutivo) === String(consecutivo));
      return Number(row?.numeroSubcontratos);
    }, target)).toBe(updatedQuantity);
    expect(await page.evaluate(({ tipoPaquete, paqueteContratacion }) => (
      window.table.getSourceData().filter((row) => (
        Number(row.titulo) === 0 && row.tipoPaquete === tipoPaquete
        && row.paqueteContratacion === paqueteContratacion
      )).length
    ), target)).toBe(updatedQuantity);
    await closeDefinitionGrid(page);
    const additionalId = await page.evaluate(({ tipoPaquete, paqueteContratacion }) => {
      const row = window.table.getSourceData().find((item) => (
        item.tipoPaquete === tipoPaquete && item.paqueteContratacion === paqueteContratacion
        && Number(item.subcontratoPaquete) > 1
      ));
      return Number(row?.consecutivo || 0);
    }, target);
    expect(additionalId).toBeGreaterThan(0);
    const baseDelete = await postPdcFormJson(page, {
      opcion: 'eliminar_actividad_pdc',
      semana: PROJECT.maxWeek,
      Id: target.consecutivo,
    });
    expect(baseDelete.status).toBe(422);
    consumeExpectedClientStatus(clientErrors, 422, '/api/pdc/save');
    await consumeExpectedConsoleStatus(runtimeErrors, 422, 'protected base-row deletion');
    expect(await page.evaluate((id) => (
      window.table.getSourceData().some((row) => Number(row.consecutivo) === Number(id))
    ), target.consecutivo)).toBe(true);
    const deleteButton = page.locator(`#dt_cliente .ht_master button.pdc-row-action--delete[data-pdc-consecutivo="${additionalId}"]`);
    await expect(deleteButton).toHaveCount(1);
    await deleteButton.click();
    await expect(page.locator('#modalEliminar')).toBeVisible();
    const deleted = page.waitForResponse((response) => (
      response.url().includes('/api/pdc/save')
        && response.request().postData()?.includes('opcion=eliminar_actividad_pdc')
    ));
    await page.locator('#eliminar-usuario').click();
    const deletePayload = await (await deleted).json();
    expect(deletePayload.respuesta, JSON.stringify(deletePayload)).toBe('BIEN');
    await expect.poll(() => page.evaluate((id) => (
      window.table.getSourceData().some((row) => Number(row.consecutivo) === id)
    ), additionalId)).toBe(false);

    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    expect(await page.evaluate((id) => (
      window.table.getSourceData().some((row) => Number(row.consecutivo) === id)
    ), additionalId)).toBe(false);
    expect(Number(runSql(`SELECT COUNT(*) FROM papelera_pdc
      WHERE project_id=${PROJECT.projectId} AND semana=${PROJECT.maxWeek}
      AND consecutivo=${additionalId};`).trim())).toBe(1);

    const restored = await postPdcFormJson(page, {
      opcion: 'restaurar_actividad_pdc',
      semana: PROJECT.maxWeek,
      Id: additionalId,
    });
    expect(restored.status, JSON.stringify(restored)).toBe(200);
    expect(restored.payload.respuesta, JSON.stringify(restored.payload)).toBe('BIEN');
    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    expect(await page.evaluate((id) => (
      window.table.getSourceData().some((row) => Number(row.consecutivo) === id)
    ), additionalId)).toBe(true);
    expect(Number(runSql(`SELECT COUNT(*) FROM papelera_pdc
      WHERE project_id=${PROJECT.projectId} AND semana=${PROJECT.maxWeek}
      AND consecutivo=${additionalId};`).trim())).toBe(0);

    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(initialFingerprint);
    snapshot.dispose();
    snapshot = null;
    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    await openDefinitionGrid(page);
    expect(await page.evaluate(({ consecutivo }) => {
      const row = window.definirHot.getSourceData().find((item) => String(item.consecutivo) === String(consecutivo));
      return Number(row?.numeroSubcontratos);
    }, target)).toBe(target.original);
    expect(await page.evaluate(({ tipoPaquete, paqueteContratacion }) => (
      window.table.getSourceData().filter((row) => (
        Number(row.titulo) === 0 && row.tipoPaquete === tipoPaquete
        && row.paqueteContratacion === paqueteContratacion
      )).length
    ), target)).toBe(target.original);
  });
});
