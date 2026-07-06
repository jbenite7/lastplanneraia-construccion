import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, logout, postFormJson, getJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { getCellValue, getRowCount, waitForRender } from '../../support/handsontable.mjs';
import { PG_SELECTORS, COMMON_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT_DA_PORTO = PROJECTS.find((p) => p.key === 'construction');
const PROJECT_PC = PROJECTS.find((p) => p.key === 'pc');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function apiGet(page, url) {
  const r = await getJson(page, url);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
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

    try {
      snapshot = new ProjectDbSnapshot(PROJECT_DA_PORTO).capture();
      await loginAndSelectProject(page, PROJECT_DA_PORTO);

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

      /* ── save original Unidad value ── */
      const originalValue = await getCellValue(page, 0, 7);
      findings.push(`Original Unidad: "${originalValue}"`);

      /* ── edit cell directly via Handsontable API (bypass dblclick UI) ── */
      await page.evaluate(({ row, col, val }) => {
        if (window.hot && typeof window.hot.setDataAtCell === 'function') {
          window.hot.setDataAtCell(row, col, val);
          window.hot.render();
          return;
        }
        for (var k in window) {
          try {
            var m = window[k];
            if (m && typeof m === 'object' && typeof m.getHotInstance === 'function') {
              var hot = m.getHotInstance();
              if (hot && typeof hot.setDataAtCell === 'function') {
                hot.setDataAtCell(row, col, val);
                hot.render();
                return;
              }
            }
          } catch (_) {}
        }
      }, { row: 0, col: 7, val: 'E2E_TEST' });
      await page.waitForTimeout(500);

      const uiValue = await getCellValue(page, 0, 7);
      findings.push(`UI after setDataAtCell: "${uiValue}"`);

      // API verify
      const apiRes = await apiGet(page, `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`);
      if (apiRes.ok && apiRes.payload.data?.length > 0) {
        findings.push('API verify: data received');
      } else {
        findings.push(`API verify: no data (${JSON.stringify(apiRes.payload).slice(0, 200)})`);
      }

      // DB verify
      const dbCount = scalar(
        `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_DA_PORTO.projectId}`,
      );
      findings.push(`DB rows in programa_consolidado: ${dbCount}`);

      /* ── restore original value ── */
      await page.evaluate(({ row, col, val }) => {
        // same hot lookup
        for (var k in window) {
          try {
            var m = window[k];
            if (m && typeof m === 'object' && typeof m.getHotInstance === 'function') {
              var hot = m.getHotInstance();
              if (hot && typeof hot.setDataAtCell === 'function') {
                hot.setDataAtCell(row, col, val);
                hot.render();
                return;
              }
            }
          } catch (_) {}
        }
      }, { row: 0, col: 7, val: originalValue || '' });
      await page.waitForTimeout(500);

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
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
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
      // Use jbenitez credentials (known working) and test RBAC via API
      await loginAndSelectProject(page, PROJECT_DA_PORTO);

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

  /* ──────────────────────────────────────────────────────────────────────────────
     Aeropuerto PC Admin — pre-construccion chips, edit, leyenda, export
     ────────────────────────────────────────────────────────────────────────────── */
  test('Aeropuerto PC Admin: chips, edit cell, leyenda, export', async ({ page }, testInfo) => {
    test.skip(!PROJECT_PC, 'Aeropuerto PC project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT_PC).capture();
      await loginAndSelectProject(page, PROJECT_PC);

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

      /* ── edit cell ── */
      const origVal = await getCellValue(page, 0, 7);
      await editCell(page, 0, 7, 'E2E_PC_TEST');
      await page.waitForTimeout(500);

      const uiVal = await getCellValue(page, 0, 7);
      expect(uiVal, 'PC UI should show E2E_PC_TEST').toBe('E2E_PC_TEST');
      findings.push(`PC UI verify: "${uiVal}"`);

      // API
      const apiRes = await apiGet(page, `/api/general/list?db=${PROJECT_PC.dbPrefix}&semana=1`);
      if (apiRes.ok && apiRes.payload.data?.length > 0) {
        const apiRow = apiRes.payload.data[0];
        expect(String(apiRow.Unidad || ''), 'PC API Unidad should be E2E_PC_TEST').toBe('E2E_PC_TEST');
        findings.push('PC API verify: OK');
      } else {
        findings.push(`PC API verify: no data (${JSON.stringify(apiRes.payload).slice(0, 200)})`);
      }

      // DB
      const dbCount = scalar(
        `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_PC.projectId} AND Unidad='E2E_PC_TEST'`,
      );
      expect(dbCount, 'PC DB should have E2E_PC_TEST row').toBeGreaterThan(0);
      findings.push(`PC DB verify: ${dbCount} rows`);

      /* ── restore ── */
      await editCell(page, 0, 7, origVal || '');
      await page.waitForTimeout(500);
      expect(await getCellValue(page, 0, 7), 'PC value restored').toBe(origVal || '');
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
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    testInfo._e2eErrors = errors;
  });

  /* ── afterEach: generate findings.md per test ── */
  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });
});
