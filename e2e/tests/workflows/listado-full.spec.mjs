import { test, expect } from '@playwright/test';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import {
  changeWeek,
  loginAndSelectProject,
  postFormJson,
} from '../../../tests/browser/support/session.mjs';

const PROJECT = {
  name: 'Optimización Aeropuerto JMC',
  projectId: 68,
  dbPrefix: 'optimizacionJMC',
  maxWeek: 5,
};
const TABLES = [
  'actividades', 'actividad_programa_fuentes', 'contratos_trazabilidad', 'programa', 'programa_consolidado',
];
const AUTO_SOURCE_ID = 9995002;

async function openListado(page) {
  await loginAndSelectProject(page, PROJECT);
  await changeWeek(page, PROJECT.maxWeek, `/listado-actividades?semana=${PROJECT.maxWeek}`);
  await page.waitForFunction(() => (
    window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0
  ), null, { timeout: 30_000 });
}

async function listadoRows(page) {
  const response = await postFormJson(
    page,
    `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`,
  );
  expect(response.ok, JSON.stringify(response)).toBe(true);
  expect(Array.isArray(response.payload.data)).toBe(true);
  return response.payload.data;
}

async function postJson(page, url, body) {
  return page.evaluate(async ({ endpoint, payload }) => {
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    return { ok: response.ok, status: response.status, payload: await response.json() };
  }, { endpoint: url, payload: body });
}

test.describe.serial('Listado de Actividades — ciclo real restaurable', () => {
  let snapshot;
  let baselineFingerprint;
  let autoRunId;

  test.beforeEach(async ({ page }) => {
    snapshot = new ProjectDbSnapshot(PROJECT, TABLES).capture();
    baselineFingerprint = snapshot.fingerprint();
    autoRunId = null;
    await openListado(page);
  });

  test.afterEach(() => {
    if (!snapshot) return;
    if (autoRunId) {
      const safeRunId = String(autoRunId).replaceAll("'", "''");
      for (const table of ['semi_auto_feedback', 'semi_auto_decisions', 'semi_auto_suggestions', 'semi_auto_runs']) {
        runSql(`DELETE FROM ${table} WHERE run_id='${safeRunId}'`);
      }
    }
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(baselineFingerprint);
    snapshot.dispose();
    snapshot = null;
  });

  test('API, Handsontable y mobile comparten los mismos registros', async ({ page }) => {
    const apiRows = await listadoRows(page);
    const hotRows = await page.evaluate(() => (
      window.ListadoActividadesHotModule.getHotInstance().getSourceData()
    ));
    expect(hotRows).toEqual(apiRows);

    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForTimeout(250);
    const cardIds = await page.locator('#la-mobile-card-list .la-mobile-card')
      .evaluateAll((cards) => cards.map((card) => Number(card.dataset.rowId)));
    expect(cardIds).toEqual(apiRows.map((row) => Number(row.Id)));
  });

  test('Nueva Familia persiste tras recargar y se restaura', async ({ page }) => {
    const name = `E2E Listado ${Date.now()}`;
    await page.locator('#btn_nueva_actividad').click();
    await expect(page.locator('#modalNuevaActividad')).toBeVisible();
    await page.waitForTimeout(500);
    await page.locator('#actividad').fill(name);
    await page.locator('#descripcionActividad').fill('Validación E2E restaurable');
    await page.locator('#actividadInicio').selectOption({ index: 1 });
    await expect(page.locator('#fechaInicio')).not.toHaveValue('');
    const mo = page.locator('#modalNuevaActividad .aia-tipo-pill[data-tipo-code="MO"] input');
    await mo.check();
    await expect(mo).toBeChecked();

    const response = page.waitForResponse((item) => (
      item.url().includes('/api/listado-actividades/save')
      && item.request().postData()?.includes('registrar')
    ));
    await page.locator('#btn_guardar_actividad').click();
    expect((await (await response).json()).respuesta).toBe('BIEN');
    expect((await listadoRows(page)).filter((row) => row.actividad === name)).toHaveLength(1);

    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => (
      window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0
    ));
    expect((await listadoRows(page)).filter((row) => row.actividad === name)).toHaveLength(1);
  });

  test('Eliminar exige confirmación y persiste tras recargar', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForTimeout(250);
    const firstCard = page.locator('#la-mobile-card-list .la-mobile-card').first();
    const id = Number(await firstCard.getAttribute('data-row-id'));
    await firstCard.locator('button[title="Eliminar familia"]').click();
    await expect(page.locator('#modalEliminar')).toBeVisible();

    const response = page.waitForResponse((item) => (
      item.url().includes('/api/listado-actividades/save')
      && item.request().postData()?.includes('eliminar')
    ));
    await page.locator('#eliminar-usuario').click();
    expect((await (await response).json()).respuesta).toBe('BIEN');
    expect((await listadoRows(page)).some((row) => Number(row.Id) === id)).toBe(false);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => (
      window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0
    ));
    expect((await listadoRows(page)).some((row) => Number(row.Id) === id)).toBe(false);
  });

  test('Auto preview, selección, apply, recarga y undo restauran familias', async ({ page }) => {
    runSql(`DELETE FROM programa_consolidado WHERE project_id=${PROJECT.projectId} AND Semana=${PROJECT.maxWeek} AND unique_id=${AUTO_SOURCE_ID}`);
    runSql(`DELETE FROM programa WHERE project_id=${PROJECT.projectId} AND Consecutivo=${AUTO_SOURCE_ID}`);
    runSql(`INSERT INTO programa (project_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
      VALUES (${PROJECT.projectId}, ${AUTO_SOURCE_ID}, 'E2E.AUTO.2', 'PISOS LAMINADOS E2E [Capítulo: ACABADOS TORRE B]', 0, '2031-03-03', '2031-03-05')`);
    runSql(`INSERT INTO programa_consolidado
      (project_id, row_id, unique_id, Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
      VALUES (${PROJECT.projectId}, ${AUTO_SOURCE_ID}, ${AUTO_SOURCE_ID}, ${AUTO_SOURCE_ID}, ${PROJECT.maxWeek}, ${AUTO_SOURCE_ID},
        'E2E.AUTO.2', 'PISOS LAMINADOS E2E [Capítulo: ACABADOS TORRE B]', 0, '2031-03-03', '2031-03-05')`);
    const initialIds = (await listadoRows(page)).map((row) => Number(row.Id));
    const previewResponse = page.waitForResponse((item) => (
      item.url().includes('/api/listado-actividades/auto/preview')
    ));
    await page.locator('#btn_auto_generar_listado').click();
    const preview = await (await previewResponse).json();
    autoRunId = preview.run_id;
    expect(preview.respuesta, JSON.stringify(preview)).toBe('BIEN');
    const panel = page.locator('#semiAutoReview-listado-actividades');
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await panel.locator('.sar-tab[data-filter="ready"]').click();
    const choices = panel.locator('.sar-row-check:not(:disabled)');
    expect(await choices.count()).toBeGreaterThan(0);
    const apply = panel.locator('.sar-btn-apply');
    if (await apply.isDisabled()) await choices.first().check();
    await expect(apply).toBeEnabled();

    const applyResponse = page.waitForResponse((item) => (
      item.url().includes('/api/listado-actividades/auto/apply')
    ));
    await apply.click();
    const applied = await (await applyResponse).json();
    expect(applied.respuesta, JSON.stringify(applied)).toBe('BIEN');
    expect(Number(applied.aplicadas)).toBeGreaterThan(0);
    const appliedIds = (await listadoRows(page)).map((row) => Number(row.Id));
    expect(appliedIds.length).toBeGreaterThan(initialIds.length);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => (
      window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0
    ));
    expect((await listadoRows(page)).length).toBe(appliedIds.length);

    const undone = await postJson(
      page,
      `/api/listado-actividades/auto/undo?semana=${PROJECT.maxWeek}`,
      { run_id: preview.run_id },
    );
    expect(undone.payload.respuesta, JSON.stringify(undone)).toBe('BIEN');
    expect(Number(undone.payload.revertidas)).toBeGreaterThan(0);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => (
      window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0
    ));
    expect((await listadoRows(page)).map((row) => Number(row.Id))).toEqual(initialIds);
  });
});
