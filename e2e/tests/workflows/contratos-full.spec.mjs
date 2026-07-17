/**
 * Contratos end-to-end contract.
 *
 * The module owns one Handsontable grid on tablet/desktop and derives its
 * mobile cards from the same API data. Package editing happens in the family
 * modal; this suite deliberately contains no legacy table-row assumptions.
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot } from '../../../tests/browser/support/dbSnapshot.mjs';
import {
  changeWeek,
  loginAndSelectProject,
  postFormJson,
} from '../../../tests/browser/support/session.mjs';
import { CONTRATOS_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((project) => project.key === 'construction');
const TABLES = ['actividades', 'contratos_trazabilidad'];

async function openContratos(page) {
  await loginAndSelectProject(page, PROJECT);
  await changeWeek(page, PROJECT.maxWeek, `/contratos?semana=${PROJECT.maxWeek}`);
  await page.goto(`/contratos?semana=${PROJECT.maxWeek}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30_000,
  });
  await expect(page.locator(CONTRATOS_SELECTORS.tableStatus))
    .toHaveAttribute('data-state', 'data', { timeout: 30_000 });
  await expect(page.locator(CONTRATOS_SELECTORS.handsontableMaster)).toHaveCount(1);
  await expect.poll(() => page.evaluate(() => (
    window.ContratosHotModule?.getHotInstance?.()?.countSourceRows() || 0
  ))).toBeGreaterThan(0);
}

async function listRows(page) {
  const response = await postFormJson(
    page,
    `/api/contratos/list?semana=${PROJECT.maxWeek}`,
    { semana: PROJECT.maxWeek },
  );
  expect(response.ok, JSON.stringify(response)).toBe(true);
  expect(Array.isArray(response.payload.data), JSON.stringify(response.payload)).toBe(true);
  return response.payload.data;
}

test.describe.serial('Contratos — Handsontable, tarjetas y modal restaurable', () => {
  let snapshot;
  let baselineFingerprint;

  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'La fixture de construcción es obligatoria para Contratos');
    snapshot = new ProjectDbSnapshot(PROJECT, TABLES).capture();
    baselineFingerprint = snapshot.fingerprint();
    await openContratos(page);
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) await page.close();
    if (!snapshot) return;
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(baselineFingerprint);
    snapshot.dispose();
    snapshot = null;
  });

  test('mantiene una sola fuente HOT, cero runtime DataTables y paridad con mobile', async ({ page }) => {
    const apiRows = await listRows(page);
    const desktop = await page.evaluate(() => {
      const hot = window.ContratosHotModule.getHotInstance();
      return {
        rows: hot.getSourceData(),
        headers: hot.getColHeader(),
        masters: document.querySelectorAll('#hot-container .ht_master.handsontable').length,
        legacyShells: document.querySelectorAll('.dataTables_wrapper, table.dataTable').length,
        legacyResources: performance.getEntriesByType('resource')
          .map((entry) => entry.name)
          .filter((url) => /data-?tables|datatable-|gyrocode/i.test(url)),
        legacyPlugin: Boolean(window.jQuery?.fn?.dataTable || window.jQuery?.fn?.DataTable),
      };
    });

    expect(desktop.masters).toBe(1);
    expect(desktop.legacyShells).toBe(0);
    expect(desktop.legacyResources).toEqual([]);
    expect(desktop.legacyPlugin).toBe(false);
    expect(desktop.headers).toEqual(CONTRATOS_SELECTORS.columns);
    expect(desktop.rows.map((row) => String(row.Id)))
      .toEqual(apiRows.map((row) => String(row.Id)));
    expect(desktop.rows.map((row) => row.contratosAsociados || ''))
      .toEqual(apiRows.map((row) => row.contratosAsociados || ''));

    await page.setViewportSize({ width: 390, height: 844 });
    await expect(page.locator(CONTRATOS_SELECTORS.mobileCards).first()).toBeVisible();
    const cards = await page.locator(CONTRATOS_SELECTORS.mobileCards).evaluateAll((items) => (
      items.map((card) => ({
        id: card.dataset.recordId,
        family: card.querySelector('.ct-mobile-card__title')?.textContent?.trim() || '',
        packages: card.querySelector('.ct-mobile-card__summary')?.innerText?.trim() || '',
      }))
    ));
    expect(cards.map((card) => card.id)).toEqual(apiRows.map((row) => String(row.Id)));
    expect(cards.map((card) => card.family)).toEqual(apiRows.map((row) => row.actividad));
    expect(cards).toHaveLength(desktop.rows.length);
    expect(cards.every((card) => card.packages.length > 0)).toBe(true);
  });

  test('combina filtros sobre todos los registros y los limpia sin condiciones residuales', async ({ page }) => {
    const result = await page.evaluate(() => {
      const hot = window.ContratosHotModule.getHotInstance();
      const filters = hot.getPlugin('filters');
      const before = hot.countRows();
      const firstFamily = String(hot.getSourceDataAtRow(0)?.actividad || '');

      filters.addCondition(2, 'contains', [firstFamily]);
      filters.addCondition(3, 'contains', ['__CONTRATOS_E2E_SIN_COINCIDENCIAS__']);
      filters.filter();
      const combined = hot.countRows();

      filters.clearConditions();
      filters.filter();
      return {
        before,
        combined,
        restored: hot.countRows(),
        conditions: filters.conditionCollection.getFilteredColumns(),
      };
    });

    expect(result.before).toBeGreaterThan(0);
    expect(result.combined).toBe(0);
    expect(result.restored).toBe(result.before);
    expect(result.conditions).toEqual([]);
  });

  test('abre el registro correcto, revela hasta cinco paquetes y cancela sin escrituras ni mezcla', async ({ page }) => {
    await page.setViewportSize({ width: 534, height: 750 });
    const cards = page.locator(CONTRATOS_SELECTORS.mobileCards);
    await expect.poll(() => cards.count()).toBeGreaterThan(1);

    const first = cards.filter({ hasText: 'Sin paquetes asociados' }).first();
    await expect(first).toBeVisible();
    const firstId = await first.getAttribute('data-record-id');
    const firstFamily = await first.locator('.ct-mobile-card__title').innerText();
    await first.locator(CONTRATOS_SELECTORS.rowActions.editar).click();

    const modal = page.locator(CONTRATOS_SELECTORS.editModal);
    await expect(modal).toBeVisible();
    await expect(page.locator('#contratoId')).toHaveValue(firstId);
    await expect(modal.locator('.ct-modal-title-family')).toHaveText(firstFamily);
    const packageSection = modal.locator('.ct-contract-section:visible').first();
    await expect(packageSection.locator('.ct-contract-row:visible')).toHaveCount(1);

    const addButton = packageSection.locator('.ct-add-package');
    for (let slots = 2; slots <= 5; slots += 1) {
      await addButton.click();
      await expect(packageSection.locator('.ct-contract-row:visible')).toHaveCount(slots);
    }
    await expect(addButton).toBeHidden();

    const si = modal.locator('#modalidadSI');
    const mo = modal.locator('#modalidadMO');
    for (const selector of ['#modalidadMO', '#modalidadS', '#modalidadOC']) {
      const option = modal.locator(selector);
      if (await option.isChecked()) await option.uncheck();
    }
    await expect(si).toBeEnabled();
    await si.check();
    for (const selector of ['#modalidadMO', '#modalidadS', '#modalidadOC']) {
      await expect(modal.locator(selector)).toBeDisabled();
    }
    await si.uncheck();
    await mo.check();
    await expect(si).toBeDisabled();

    let writes = 0;
    const countWrites = (request) => {
      if (request.method() === 'POST' && /\/api\/contratos\/(save|durations)/.test(request.url())) writes += 1;
    };
    page.on('request', countWrites);
    await modal.locator(CONTRATOS_SELECTORS.buttons.cancelar).click();
    await expect(modal).toBeHidden();
    expect(writes).toBe(0);
    page.off('request', countWrites);

    const second = cards.filter({ hasNotText: firstFamily }).first();
    const secondId = await second.getAttribute('data-record-id');
    const secondFamily = await second.locator('.ct-mobile-card__title').innerText();
    await second.locator(CONTRATOS_SELECTORS.rowActions.editar).click();
    await expect(modal).toBeVisible();
    await expect(page.locator('#contratoId')).toHaveValue(secondId);
    await expect(modal.locator('.ct-modal-title-family')).toHaveText(secondFamily);
    expect(secondId).not.toBe(firstId);
    expect(secondFamily).not.toBe(firstFamily);
    await expect(modal.locator('.ct-contract-row')).toHaveCount(20);
  });
});
