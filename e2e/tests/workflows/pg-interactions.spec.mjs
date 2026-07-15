import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, logout, postFormJson, getJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { editCell, getCellValue, getRowCount, waitForRender } from '../../support/handsontable.mjs';
import { PG_SELECTORS, COMMON_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT_DA_PORTO = PROJECTS.find((p) => p.key === 'construction');
const PROJECT_PC = PROJECTS.find((p) => p.key === 'pc');
const PASSWORD = 'aia2026';
const ADMIN = { username: 'test.A', password: PASSWORD };
const RESIDENT = { username: 'test.R', password: PASSWORD };
const SUBCONTRACTOR = { username: 'test.C', password: PASSWORD };
const VIEWER = { username: 'test.V', password: PASSWORD };
const REQUIRE_ISOLATED_DB = process.env.E2E_REQUIRE_ISOLATED_DB === '1';

if (REQUIRE_ISOLATED_DB) {
  if (process.env.E2E_ALLOW_DB_MUTATION !== 'design-system-ci') {
    throw new Error('E2E_ALLOW_DB_MUTATION=design-system-ci is required for isolated persistence tests');
  }
  if (!PROJECT_DA_PORTO) {
    throw new Error('Da Porto construction project is required for the isolated Programa General gate');
  }
}

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

function postedUnit(response) {
  return new URLSearchParams(response.request().postData() || '').get('unidad');
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function apiGet(page, url) {
  const r = await getJson(page, url);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function editableUnitRow(page) {
  return page.evaluate(() => {
    const hot = window.PGHotModule?.getHotInstance?.();
    if (!hot) return null;
    const physicalRow = hot.getSourceData().findIndex((row) => (
      row && Number(row.Titulo) !== 1 && Number(row.unique_id) > 0
    ));
    if (physicalRow < 0) return null;
    const row = hot.getSourceDataAtRow(physicalRow);
    return {
      visualRow: hot.toVisualRow(physicalRow),
      uniqueId: Number(row.unique_id),
      originalValue: String(row.unidad ?? row.Unidad ?? ''),
      testValue: String(row.unidad ?? row.Unidad ?? '') === 'ml' ? 'm2' : 'ml',
    };
  });
}

/* ──────────────────────────────────────────────────────────────────────────────
   Da Porto Admin — full PG workflow
   ────────────────────────────────────────────────────────────────────────────── */
test.describe('PG interactions', () => {
  test('Da Porto Admin: edit cell, leyenda, export CSV, LPS drawer', async ({ page }, testInfo) => {
    test.skip(!PROJECT_DA_PORTO, 'Da Porto project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;
    let beforeFingerprint;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT_DA_PORTO).capture();
      beforeFingerprint = snapshot.fingerprint();
      await loginAndSelectProject(page, PROJECT_DA_PORTO, ADMIN);

      /* ── navigate + render ── */
      await changeWeek(page, 1, '/programa-general');
      await waitForRender(page);
      await page.waitForTimeout(1000);

      const rowCount = await getRowCount(page);
      findings.push(`PG rows (hot.countRows): ${rowCount}`);

      // Fallback: count via DOM if hot returns 0
      let actualRowCount = rowCount;
      if (actualRowCount === 0) {
        actualRowCount = await page.locator('.handsontable .htCore tbody tr, [role="treegrid"] [role="rowgroup"]:first-child [role="row"]').count();
        findings.push(`PG rows (DOM fallback): ${actualRowCount}`);
      }
      expect(actualRowCount, 'PG must have rows').toBeGreaterThan(0);

      const target = await editableUnitRow(page);
      expect(target, 'PG needs an editable activity row').toBeTruthy();
      findings.push(`Target unique_id: ${target.uniqueId}`);
      const saveResponse = page.waitForResponse((response) => (
        response.url().includes('/api/general/update?') &&
        response.request().method() === 'POST' && postedUnit(response) === target.testValue
      ));
      await editCell(page, target.visualRow, 7, target.testValue);
      const savePayload = await (await saveResponse).json();
      expect(savePayload.respuesta).toBe('BIEN');
      expect(savePayload.unidad).toBe(target.testValue);

      // API verify
      const apiRes = await apiGet(page, `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`);
      const apiRow = apiRes.payload.data?.find((row) => Number(row.unique_id) === target.uniqueId);
      expect(apiRow?.unidad, 'API must return the edited unit').toBe(target.testValue);
      findings.push(`API verify ${target.testValue}: OK`);

      // DB verify
      const dbCount = scalar(
        `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_DA_PORTO.projectId} ` +
        `AND unique_id=${target.uniqueId} AND unidad='${target.testValue}'`,
      );
      expect(dbCount, 'DB must persist the edited unit').toBe(1);
      findings.push(`DB verify ${target.testValue}: OK`);

      const uiValue = await getCellValue(page, target.visualRow, 7);
      expect(uiValue, 'UI must show the edited unit').toBe(target.testValue);

      /* ── restore original value ── */
      const restoreResponse = page.waitForResponse((response) => (
        response.url().includes('/api/general/update?') &&
        response.request().method() === 'POST' && postedUnit(response) === target.originalValue
      ));
      await editCell(page, target.visualRow, 7, target.originalValue);
      expect((await (await restoreResponse).json()).respuesta).toBe('BIEN');
      expect(await getCellValue(page, target.visualRow, 7)).toBe(target.originalValue);

      /* ── leyenda modal ── */
      await page.click(PG_SELECTORS.buttons.leyenda);
      await page.waitForTimeout(500);
      const leyendaVisible = await page.locator(COMMON_SELECTORS.leyendaModal).first().isVisible().catch(() => false);
      findings.push(`Leyenda content visible: ${leyendaVisible}`);
      if (leyendaVisible) {
        const closeBtn = page.locator(COMMON_SELECTORS.leyendaClose).first();
        if (await closeBtn.isVisible().catch(() => false)) await closeBtn.click();
        else await page.keyboard.press('Escape');
      }

      /* ── export CSV ── */
      const downloadPromise = page.waitForEvent('download', { timeout: 10000 });
      await page.click(PG_SELECTORS.buttons.exportCSV);
      const download = await downloadPromise;
      expect(download, 'CSV download should not be empty').toBeTruthy();
      findings.push(`CSV download: ${download.suggestedFilename()}`);

      /* ── LPS Drawer ── */
      const drawerBtn = page.locator(COMMON_SELECTORS.lpsDrawer);
      if (await drawerBtn.isVisible().catch(() => false)) {
        await drawerBtn.click();
        const drawerDialog = page.locator(COMMON_SELECTORS.lpsDrawerDialog);
        await expect(drawerDialog, 'LPS Drawer dialog should be visible').toBeVisible({ timeout: 5000 });
        await page.keyboard.press('Escape');
        findings.push('LPS Drawer: OK');
      } else {
        findings.push('LPS Drawer button not visible');
      }

      console.log('\n[PG Da Porto Admin] Findings:');
      findings.forEach((f) => console.log(`  ${f}`));
      errors.findings = findings;
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) {
        snapshot.restore();
        const afterFingerprint = snapshot.fingerprint();
        expect(afterFingerprint).toBe(beforeFingerprint);
        snapshot.dispose();
      }
    }
    testInfo._e2eErrors = errors;
  });

  /* ──────────────────────────────────────────────────────────────────────────────
     Da Porto Residente — key interactions (omit editing if no permission)
     ────────────────────────────────────────────────────────────────────────────── */
  test('Da Porto Residente: view PG, leyenda, export, LPS drawer', async ({ page }, testInfo) => {
    test.skip(!PROJECT_DA_PORTO, 'Da Porto project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT_DA_PORTO).capture();
      await loginAndSelectProject(page, PROJECT_DA_PORTO, RESIDENT);

      await changeWeek(page, 1, '/programa-general');
      await waitForRender(page);
      await page.waitForTimeout(1000);

      const rowCount = await getRowCount(page);
      findings.push(`PG rows: ${rowCount}`);

      // Test RBAC: try editing via API as different role
      const apiRes = await apiGet(page, `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`);
      if (apiRes.ok && apiRes.payload.data?.length > 0) {
        findings.push(`PG API access: ${apiRes.payload.data.length} rows visible`);
      }

      /* ── leyenda ── */
      const leyendaBtn = page.locator(PG_SELECTORS.buttons.leyenda);
      if (await leyendaBtn.isVisible().catch(() => false)) {
        await leyendaBtn.click();
        await page.waitForTimeout(500);
        const vis = await page.locator(COMMON_SELECTORS.leyendaModal).first().isVisible().catch(() => false);
        findings.push(`Leyenda visible: ${vis}`);
        const closeBtn = page.locator(COMMON_SELECTORS.leyendaClose).first();
        if (await closeBtn.isVisible().catch(() => false)) await closeBtn.click();
        else await page.keyboard.press('Escape');
      }

      /* ── export CSV ── */
      const exportBtn = page.locator(PG_SELECTORS.buttons.exportCSV);
      if (await exportBtn.isVisible().catch(() => false)) {
        const dlPromise = page.waitForEvent('download', { timeout: 10000 });
        await exportBtn.click();
        const dl = await dlPromise;
        expect(dl, 'CSV download should fire').toBeTruthy();
        findings.push(`CSV: ${dl.suggestedFilename()}`);
      }

      /* ── LPS Drawer ── */
      const drawerBtn = page.locator(COMMON_SELECTORS.lpsDrawer);
      if (await drawerBtn.isVisible().catch(() => false)) {
        await drawerBtn.click();
        await expect(page.locator(COMMON_SELECTORS.lpsDrawerDialog)).toBeVisible({ timeout: 5000 });
        await page.keyboard.press('Escape');
        findings.push('LPS Drawer: OK');
      }

      console.log('\n[PG Da Porto Residente] Findings:');
      findings.forEach((f) => console.log(`  ${f}`));
      errors.findings = findings;
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    testInfo._e2eErrors = errors;
  });

  test('Da Porto read-only roles: visibility and manipulated writes', async ({ page }) => {
    const roles = [
      { credentials: SUBCONTRACTOR, canView: false },
      { credentials: VIEWER, canView: true },
    ];
    for (const role of roles) {
      await loginAndSelectProject(page, PROJECT_DA_PORTO, role.credentials);
      const viewResponse = await page.goto('/programa-general');
      expect(viewResponse.status()).toBe(200);
      const listResponse = await getJson(
        page,
        `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`,
      );
      expect(listResponse.status).toBe(role.canView ? 200 : 403);
      if (role.canView) await waitForRender(page);
      else await expect(page.locator('.handsontable .htCore')).toHaveCount(0);
      const forbiddenWrite = await postFormJson(
        page,
        `/api/general/update?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`,
        { opcion: 'modificar', Id: '0', unidad: 'E2E_FORBIDDEN' },
      );
      expect(forbiddenWrite.status).toBe(403);
      await logout(page);
    }
  });

  /* ──────────────────────────────────────────────────────────────────────────────
     Aeropuerto PC Admin — pre-construccion chips, edit, leyenda, export
     ────────────────────────────────────────────────────────────────────────────── */
  test('Aeropuerto PC Admin: chips, edit cell, leyenda, export', async ({ page }, testInfo) => {
    test.skip(!PROJECT_PC, 'Aeropuerto PC project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;
    let beforeFingerprint;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT_PC).capture();
      beforeFingerprint = snapshot.fingerprint();
      await loginAndSelectProject(page, PROJECT_PC, ADMIN);

      await changeWeek(page, 1, '/programa-general');
      await waitForRender(page);

      const rowCount = await getRowCount(page);
      expect(rowCount, 'PC PG must have rows').toBeGreaterThan(0);
      findings.push(`PC PG rows: ${rowCount}`);

      /* ── verify pre-construccion chip names ── */
      for (const chipText of PG_SELECTORS.chips.preconstruccion) {
        const chip = page.locator(`button:has-text("${chipText}"), span:has-text("${chipText}")`).first();
        const visible = await chip.isVisible().catch(() => false);
        findings.push(`Chip "${chipText}": ${visible ? 'visible' : 'not found'}`);
      }

      /* ── edit valid unit and verify the persistence boundary ── */
      const target = await editableUnitRow(page);
      expect(target, 'PC PG needs an editable activity row').toBeTruthy();
      const saveResponse = page.waitForResponse((response) => (
        response.url().includes('/api/general/update?') &&
        response.request().method() === 'POST' && postedUnit(response) === target.testValue
      ));
      await editCell(page, target.visualRow, 7, target.testValue);
      const savePayload = await (await saveResponse).json();
      expect(savePayload.respuesta).toBe('BIEN');
      expect(savePayload.unidad).toBe(target.testValue);

      const uiVal = await getCellValue(page, target.visualRow, 7);
      expect(uiVal, 'PC UI should show the valid edited unit').toBe(target.testValue);
      findings.push(`PC UI verify: "${uiVal}"`);

      // API
      const apiRes = await apiGet(page, `/api/general/list?db=${PROJECT_PC.dbPrefix}&semana=1`);
      const apiRow = apiRes.payload.data?.find((row) => Number(row.unique_id) === target.uniqueId);
      expect(String(apiRow?.unidad ?? apiRow?.Unidad ?? ''), 'PC API must return the edited unit')
        .toBe(target.testValue);
      findings.push(`PC API verify ${target.testValue}: OK`);

      // DB
      const dbCount = scalar(
        `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_PC.projectId} ` +
        `AND unique_id=${target.uniqueId} AND unidad='${target.testValue}'`,
      );
      expect(dbCount, 'PC DB must persist the edited unit').toBe(1);
      findings.push(`PC DB verify ${target.testValue}: OK`);

      /* ── restore ── */
      const restoreResponse = page.waitForResponse((response) => (
        response.url().includes('/api/general/update?') &&
        response.request().method() === 'POST' && postedUnit(response) === target.originalValue
      ));
      await editCell(page, target.visualRow, 7, target.originalValue);
      expect((await (await restoreResponse).json()).respuesta).toBe('BIEN');
      expect(await getCellValue(page, target.visualRow, 7), 'PC value restored').toBe(target.originalValue);
      findings.push('PC value restored');

      /* ── leyenda ── */
      await page.click(PG_SELECTORS.buttons.leyenda);
      await expect(page.locator(COMMON_SELECTORS.leyendaModal), 'PC Leyenda visible')
        .toBeVisible({ timeout: 5000 });
      await page.click(COMMON_SELECTORS.leyendaClose);
      findings.push('PC Leyenda: OK');

      /* ── export CSV ── */
      const dlP = page.waitForEvent('download', { timeout: 10000 });
      await page.click(PG_SELECTORS.buttons.exportCSV);
      const dl = await dlP;
      expect(dl, 'PC CSV download should fire').toBeTruthy();
      findings.push(`PC CSV: ${dl.suggestedFilename()}`);

      console.log('\n[PG Aeropuerto PC Admin] Findings:');
      findings.forEach((f) => console.log(`  ${f}`));
      errors.findings = findings;
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) {
        snapshot.restore();
        const afterFingerprint = snapshot.fingerprint();
        expect(afterFingerprint).toBe(beforeFingerprint);
        snapshot.dispose();
      }
    }
    testInfo._e2eErrors = errors;
  });

  /* ── afterEach: generate findings.md per test ── */
  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });
});
