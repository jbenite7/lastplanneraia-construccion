import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from './support/dbSnapshot.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import {
  HANDSONTABLE_GOAL_THEMES,
  HANDSONTABLE_GOAL_VIEWPORTS,
  measureHandsontableGoalMatrix,
  setHandsontableGoalTheme,
} from './support/handsontableGoalMatrix.mjs';
import {
  changeWeek, login, loginAndSelectProject, logout, postFormJson, selectProject,
} from './support/session.mjs';

const PROJECT = PROJECTS.find((project) => project.key === 'construction');
const PDC_TABLES = ['pdc', 'papelera_pdc'];
const VIEWER_USERNAME = `test.pdc.v.${process.pid}`;

function installPdcInvariantCollectors(page) {
  const issues = { warnings: [], clientErrors: [], requestFailures: [] };
  page.on('console', (message) => {
    if (message.type() === 'warning') issues.warnings.push(message.text());
  });
  page.on('response', (response) => {
    if (response.status() >= 400 && response.status() < 500) {
      issues.clientErrors.push(`${response.status()} ${new URL(response.url()).pathname}`);
    }
  });
  page.on('requestfailed', (request) => {
    const errorText = request.failure()?.errorText || 'failed';
    // Full-page redirects intentionally abort in-flight requests; they are not transport failures.
    if (errorText !== 'net::ERR_ABORTED') {
      issues.requestFailures.push(`${errorText} ${request.url()}`);
    }
  });
  return issues;
}

function consumeExpectedClientError(issues, status, pathname) {
  const expected = `${status} ${pathname}`;
  const index = issues.clientErrors.indexOf(expected);
  expect(index, `Expected HTTP failure was not collected: ${expected}`).toBeGreaterThanOrEqual(0);
  issues.clientErrors.splice(index, 1);
}

function consumeExpectedConsoleResourceError(runtimeErrors) {
  const message = 'Failed to load resource: the server responded with a status of 403 (Forbidden)';
  const index = runtimeErrors.consoleErrors.indexOf(message);
  expect(index, `Expected console resource error was not collected: ${message}`).toBeGreaterThanOrEqual(0);
  runtimeErrors.consoleErrors.splice(index, 1);
}

function assertPdcInvariants(issues) {
  expect(issues.warnings, 'console.warn messages').toEqual([]);
  expect(issues.clientErrors, 'Unexpected HTTP 4xx responses').toEqual([]);
  expect(issues.requestFailures, 'Failed network requests').toEqual([]);
}

async function waitForMainHot(page) {
  await expect(page.locator('#dt_cliente .ht_master.handsontable')).toBeVisible({ timeout: 30_000 });
  await expect.poll(() => page.evaluate(() => window.table?.countRows() || 0)).toBeGreaterThan(0);
}

async function openDefinirContratos(page) {
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

async function closeDefinirContratos(page) {
  await page.locator('#btn_cancelar_definirContratos').click();
  await expect(page.locator('#modalDefinirContratos')).toBeHidden();
}

async function loginAsRole(page, username) {
  await logout(page);
  await login(page, { username, password: 'aia2026' });
  await selectProject(page, PROJECT);
  await changeWeek(page, PROJECT.maxWeek, '/pdc');
  await waitForMainHot(page);
}

async function postPdcFormJson(page, body) {
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  return postFormJson(page, '/api/pdc/save', { ...body, _csrf_token: csrf || '' });
}

function ensureViewerFixture() {
  runSql(`INSERT INTO general_usuarios
    (nombre,email,cargo,usuario,password,force_password_change,activo)
    SELECT 'Test PDC Visualizador','test.pdc.v@aia.local','Visualizador','${VIEWER_USERNAME}',password,0,1
    FROM general_usuarios WHERE usuario='test.A' LIMIT 1;
    INSERT INTO project_members (project_id,user_id,role)
    SELECT ${PROJECT.projectId},id,'V' FROM general_usuarios WHERE usuario='${VIEWER_USERNAME}';`);
  return () => runSql(`DELETE pm FROM project_members pm
    JOIN general_usuarios u ON u.id=pm.user_id WHERE u.usuario='${VIEWER_USERNAME}';
    DELETE FROM general_usuarios WHERE usuario='${VIEWER_USERNAME}';`);
}

test.describe('PDC Handsontable', () => {
  let snapshot;
  let initialFingerprint;
  let runtimeErrors;
  let invariantErrors;

  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto construction fixture is required for PDC');
    runtimeErrors = installErrorCollectors(page);
    invariantErrors = installPdcInvariantCollectors(page);
    snapshot = new ProjectDbSnapshot(PROJECT, PDC_TABLES).capture();
    initialFingerprint = snapshot.fingerprint();
    await loginAndSelectProject(page, PROJECT);
    await changeWeek(page, PROJECT.maxWeek, '/pdc');
    await waitForMainHot(page);
  });

  test.afterEach(async ({ page }) => {
    const collectedRuntimeErrors = runtimeErrors;
    const collectedInvariantErrors = invariantErrors;
    if (!page.isClosed()) await page.close();
    if (snapshot) {
      snapshot.restore();
      expect(snapshot.fingerprint()).toBe(initialFingerprint);
    }
    if (snapshot) snapshot.dispose();
    snapshot = null;
    if (collectedRuntimeErrors) assertNoRuntimeErrors(collectedRuntimeErrors);
    if (collectedInvariantErrors) assertPdcInvariants(collectedInvariantErrors);
  });

  test('renders both HOT grids, has no DataTables runtime, and clears filters', async ({ page }) => {
    const contract = await page.evaluate(() => ({
      mainRows: window.table?.countRows() || 0,
      mainHeaders: window.table?.getColHeader() || [],
      dataTableWrappers: document.querySelectorAll('.dataTables_wrapper').length,
      dataTableTables: document.querySelectorAll('table.dataTable').length,
      dataTableScripts: [...document.scripts]
        .filter((script) => /datatables|gyrocode/i.test(script.src)).map((script) => script.src),
      dataTableStyles: [...document.styleSheets]
        .map((sheet) => sheet.href || '').filter((href) => /datatables|gyrocode/i.test(href)),
      dataTableHelpers: [...document.scripts]
        .filter((script) => /mobile-table-fix|datatable-height|global-table-align/i.test(script.src))
        .map((script) => script.src),
      dataTablePlugin: Boolean(window.jQuery?.fn?.dataTable || window.jQuery?.fn?.DataTable),
    }));

    expect(contract.mainRows).toBeGreaterThan(0);
    expect(contract.mainHeaders).toContain('PAQUETE DE CONTRATACION');
    expect(contract.dataTableWrappers).toBe(0);
    expect(contract.dataTableTables).toBe(0);
    expect(contract.dataTableScripts).toEqual([]);
    expect(contract.dataTableStyles).toEqual([]);
    expect(contract.dataTableHelpers).toEqual([]);
    expect(contract.dataTablePlugin).toBe(false);

    const missingCounts = await page.evaluate(() => ({
      expected: window.table.getSourceData().filter((row) => (
        Number(row.titulo) === 0 && (
          !String(row.fechaInicioProyectada || '').trim()
          || row.valorPresupuesto === '' || row.valorPresupuesto == null
        )
      )).length,
      displayed: Number(document.querySelector('#count-missing')?.textContent || 0),
    }));
    expect(missingCounts.displayed).toBe(missingCounts.expected);

    const filteredRows = await page.evaluate(() => {
      const hot = window.table;
      const filters = hot.getPlugin('filters');
      filters.addCondition(2, 'contains', ['__PDC_HOT_FILTER_MISS__']);
      filters.filter();
      return hot.countRows();
    });
    expect(filteredRows).toBe(0);

    await expect(page.locator('#btn_limpiarFiltrosPDC')).toBeVisible();
    await page.locator('#btn_limpiarFiltrosPDC').click();
    const restoredRows = await page.evaluate(() => window.table.countRows());
    expect(restoredRows).toBe(contract.mainRows);

    await openDefinirContratos(page);
    const definirContract = await page.evaluate(() => ({
      rows: window.definirHot?.countRows() || 0,
      headers: window.definirHot?.getColHeader() || [],
      numeric: window.definirHot?.getCellMeta(0, 2)?.type,
    }));
    expect(definirContract.rows).toBeGreaterThan(0);
    expect(definirContract.headers).toEqual([
      'Modalidad de contratacion',
      'Paquete de contratacion',
      'Cantidad de contratos',
    ]);
    expect(definirContract.numeric).toBe('numeric');
    assertNoRuntimeErrors(runtimeErrors);
  });

  test('opens and applies a column filter from the visible interface', async ({ page }) => {
    const source = await page.evaluate(() => {
      return { total: window.table.countRows() };
    });
    const buttons = page.locator('#dt_cliente .ht_clone_top thead .changeType');
    const buttonCount = await buttons.count();
    expect(buttonCount).toBeGreaterThan(2);
    await buttons.nth(2).click();
    const menu = page.locator('.htDropdownMenu');
    await expect(menu).toBeVisible();
    const valueCells = menu.locator('.htUIMultipleSelectHot .ht_master td');
    const valueCount = await valueCells.count();
    expect(valueCount).toBeGreaterThan(0);
    const valueCell = valueCells.nth(0);
    const packageName = (await valueCell.innerText()).trim();
    const expected = await page.evaluate((value) => window.table.getSourceData()
      .filter((row) => row.paqueteContratacion === value).length, packageName);
    await menu.locator('.htUIClearAll').click();
    await valueCell.locator('input[type="checkbox"]').setChecked(true);
    const okButton = menu.locator('.htUIButton input[value="OK"]');
    await expect(okButton).toHaveCount(1);
    await okButton.click();
    await expect(menu).toBeHidden();
    expect(await page.evaluate(() => window.table.countRows())).toBe(expected);
    await page.locator('#btn_limpiarFiltrosPDC').click();
    expect(await page.evaluate(() => window.table.countRows())).toBe(source.total);
    assertNoRuntimeErrors(runtimeErrors);
  });

  test('keeps legend counts and alert filtering in sync and clears every state', async ({ page }) => {
    const counts = await page.evaluate(() => ({
      total: window.table.countRows(),
      headers: window.table.getSourceData().filter((row) => Number(row.titulo) !== 0).length,
      states: [...document.querySelectorAll('.pdc-legend-item .count-badge')]
        .map((badge) => Number(badge.textContent || 0)),
      alerts: Number(document.querySelector('#count-alertas')?.textContent || 0),
    }));
    expect(counts.states.reduce((sum, value) => sum + value, 0)).toBe(counts.total - counts.headers);
    await page.locator('#btn_soloAlertas').click();
    await expect(page.locator('#btn_soloAlertas')).toHaveClass(/is-active/);
    expect(await page.evaluate(() => window.table.countRows())).toBe(counts.headers + counts.alerts);
    await page.locator('.pdc-legend-item.missing').click();
    expect(await page.evaluate(() => window.table.countRows())).toBe(counts.headers + counts.states[0]);
    await page.locator('#btn_limpiarFiltrosPDC').click();
    expect(await page.evaluate(() => window.table.countRows())).toBe(counts.total);
    await expect(page.locator('#btn_soloAlertas')).not.toHaveClass(/is-active/);
    assertNoRuntimeErrors(runtimeErrors);
  });

  test('covers mobile, tablet horizontal, and desktop in Dark and Linen', async ({ page }) => {
    for (const viewport of HANDSONTABLE_GOAL_VIEWPORTS) {
      await page.setViewportSize(viewport);
      await page.waitForTimeout(150);
      for (const theme of HANDSONTABLE_GOAL_THEMES) {
        await test.step(`${viewport.name} ${theme}`, async () => {
          await setHandsontableGoalTheme(page, theme);
          const state = await measureHandsontableGoalMatrix(page, {
            hot: '#dt_cliente .handsontable',
            controls: '.toolbarAcciones button, .pdc-legend-item, #dt_cliente button',
            headers: '#dt_cliente .ht_clone_top thead th',
            cells: '#dt_cliente .ht_master tbody tr:first-child td',
          });
          expect(state.theme).toBe(theme);
          expect(state.darkBody).toBe(theme === 'dark');
          expect(state.pageOverflowX).toBe(0);
          expect(state.hot.overflowX, JSON.stringify(state.hot)).toBe(0);
          expect(state.overflowingControls).toEqual([]);
          expect(state.hot.masters).toBe(1);
          expect(state.hot.visible).toBeGreaterThan(0);
          expect(state.headerCellAlignment.available).toBe(true);
          expect(state.headerCellAlignment.aligned).toBe(true);
          expect(state.dataTables).toEqual({
            wrappers: 0, scripts: [], styles: [], plugin: false,
            legacyDom: 0, delegatedSelectors: [],
          });
          expect(state.legacyDataTableListeners).toEqual([]);
          expect(state.rawHtmlText).toEqual([]);

          await openDefinirContratos(page);
          const secondary = await measureHandsontableGoalMatrix(page, {
            hot: '#dt_definirContratos .handsontable',
            controls: '#modalDefinirContratos button',
            headers: '#dt_definirContratos thead th',
            cells: '#dt_definirContratos .ht_master tbody tr:first-child td',
          });
          expect(secondary.pageOverflowX).toBe(0);
          expect(secondary.hot.overflowX, JSON.stringify(secondary.hot)).toBe(0);
          expect(secondary.overflowingControls).toEqual([]);
          expect(secondary.hot.masters).toBe(1);
          expect(secondary.headerCellAlignment.available).toBe(true);
          expect(
            secondary.headerCellAlignment.aligned,
            JSON.stringify(secondary.headerCellAlignment.columns),
          ).toBe(true);
          expect(secondary.dataTables).toEqual({
            wrappers: 0, scripts: [], styles: [], plugin: false,
            legacyDom: 0, delegatedSelectors: [],
          });
          expect(secondary.legacyDataTableListeners).toEqual([]);
          expect(secondary.rawHtmlText).toEqual([]);
          await closeDefinirContratos(page);
        });
      }
    }
  });

  test('reopens and cancels the definition modal without duplicate HOT instances or handlers', async ({ page }) => {
    const identities = [];

    for (let iteration = 0; iteration < 3; iteration += 1) {
      await openDefinirContratos(page);
      identities.push(await page.evaluate(() => {
        window.definirHot.__pdcBrowserIdentity ??= `pdc-browser-${Date.now()}`;
        return window.definirHot.__pdcBrowserIdentity;
      }));
      expect(await page.locator('#dt_definirContratos .ht_master.handsontable').count()).toBe(1);
      await closeDefinirContratos(page);
    }

    expect(identities.every((instance) => instance === identities[0])).toBe(true);
    await openDefinirContratos(page);
    await expect(page.locator('#btn_guardar_definirContratos')).toBeEnabled();
    expect(await page.evaluate(() => window.definirHot.getCellMeta(0, 2).readOnly)).toBe(false);

    let saveRequests = 0;
    await page.route('**/api/pdc/save', async (route) => {
      saveRequests += 1;
      await route.fulfill({ json: 'sinModificaciones' });
    });
    await page.evaluate(() => window.definirHot.setDataAtCell(0, 2, 1, 'pdc-hot-test'));
    await page.locator('#btn_guardar_definirContratos').click();
    await expect.poll(() => saveRequests).toBe(1);
    await page.waitForTimeout(200); // Allow a duplicate listener, if any, to issue its request.
    expect(saveRequests).toBe(1);
    assertNoRuntimeErrors(runtimeErrors);
  });

  test('uses authenticated editor and readOnly sessions without permission overrides', async ({ page }) => {
    await loginAsRole(page, 'test.A');
    await expect(page.locator('#permiso_canonico')).toHaveValue('A');
    await expect(page.locator('#btn_actualizarPDC')).toBeVisible();
    expect(await page.locator('.pdc-row-action--edit').count()).toBeGreaterThan(0);
    await openDefinirContratos(page);
    await expect(page.locator('#btn_guardar_definirContratos')).toBeEnabled();
    expect(await page.evaluate(() => window.definirHot.getCellMeta(0, 2).readOnly)).toBe(false);
    await closeDefinirContratos(page);
    const missingCsrf = await postFormJson(page, '/api/pdc/save', {
      opcion: 'guardar_DefinirContratos', semana: PROJECT.maxWeek,
      numeroSubcontratos: JSON.stringify({ numeroSubcontratos: [] }),
    });
    expect(missingCsrf.status).toBe(403);
    consumeExpectedClientError(invariantErrors, 403, '/api/pdc/save');
    consumeExpectedConsoleResourceError(runtimeErrors);

    const cleanupViewer = ensureViewerFixture();
    try {
      await loginAsRole(page, VIEWER_USERNAME);
      await expect(page.locator('#permiso_canonico')).toHaveValue('V');
      await expect(page.locator('#btn_actualizarPDC')).toHaveCount(0);
      await expect(page.locator('#btn_pdcSemiAuto')).toHaveCount(0);
      expect(await page.locator('[aria-label="Ver actividad"]').count()).toBeGreaterThan(0);
      await openDefinirContratos(page);
      await expect(page.locator('#btn_guardar_definirContratos')).toBeDisabled();
      expect(await page.evaluate(() => window.definirHot.getCellMeta(0, 2).readOnly)).toBe(true);
      const denied = await postPdcFormJson(page, {
        opcion: 'guardar_DefinirContratos', semana: PROJECT.maxWeek,
        numeroSubcontratos: JSON.stringify({ numeroSubcontratos: [] }),
      });
      expect(denied.status).toBe(403);
      consumeExpectedClientError(invariantErrors, 403, '/api/pdc/save');
      consumeExpectedConsoleResourceError(runtimeErrors);
    } finally {
      await logout(page);
      cleanupViewer();
    }
  });

  test('rejects non-integer quantities, persists a valid quantity, reloads, and restores the snapshot', async ({ page }) => {
    await openDefinirContratos(page);
    const target = await page.evaluate(() => {
      const hot = window.definirHot;
      const row = hot.getSourceData().findIndex((item) => item.consecutivo !== '' && item.consecutivo != null);
      const item = hot.getSourceDataAtRow(row);
      return { row, original: Number(item.numeroSubcontratos), consecutivo: item.consecutivo };
    });
    expect(target.row).toBeGreaterThanOrEqual(0);
    expect(Number.isInteger(target.original)).toBe(true);

    for (const value of ['', '0', '-1', '1.5']) {
      const invalid = await page.evaluate(({ row, candidate }) => new Promise((resolve) => {
        const hot = window.definirHot;
        hot.getCellMeta(row, 2).validator(candidate, resolve);
      }), { row: target.row, candidate: value });
      expect(invalid).toBe(false);
    }
    expect(await page.evaluate(({ row }) => new Promise((resolve) => {
      window.definirHot.getCellMeta(row, 2).validator('1', resolve);
    }), target)).toBe(true);

    const updatedQuantity = Math.max(2, target.original + 1);
    const valid = await page.evaluate(({ row, value }) => new Promise((resolve) => {
      const hot = window.definirHot;
      hot.getCellMeta(row, 2).validator(String(value), (isValid) => {
        hot.setDataAtCell(row, 2, value, 'pdc-hot-test');
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
    await openDefinirContratos(page);
    expect(await page.evaluate(({ consecutivo }) => {
      const row = window.definirHot.getSourceData().find((item) => String(item.consecutivo) === String(consecutivo));
      return Number(row?.numeroSubcontratos);
    }, target)).toBe(updatedQuantity);

    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(initialFingerprint);
    snapshot.dispose();
    snapshot = null;
    await page.reload({ waitUntil: 'networkidle' });
    await waitForMainHot(page);
    await openDefinirContratos(page);
    expect(await page.evaluate(({ consecutivo }) => {
      const row = window.definirHot.getSourceData().find((item) => String(item.consecutivo) === String(consecutivo));
      return Number(row?.numeroSubcontratos);
    }, target)).toBe(target.original);
    assertNoRuntimeErrors(runtimeErrors);
  });
});
