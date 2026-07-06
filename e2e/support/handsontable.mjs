/**
 * Handsontable interaction helpers for Playwright E2E tests.
 *
 * Tries multiple patterns to find the Handsontable instance:
 *   1. window.hot
 *   2. window.*.getHotInstance() (PGHotModule, psHotModule, etc.)
 *   3. Any window.* object with countRows() + getData()
 *
 * Usage:
 *   import { editCell, getCellValue, waitForRender } from '../../support/handsontable.mjs';
 */

const SELECTOR_TABLE = '.handsontable .htCore, [role="treegrid"]';

// JS code string to find the hot instance (sent to browser via page.evaluate)
const FIND_HOT = `(function () {\n`
  + `  if (window.hot && typeof window.hot.countRows === 'function') return window.hot;\n`
  + `  for (var key in window) {\n`
  + `    try {\n`
  + `      var m = window[key];\n`
  + `      if (m && typeof m === 'object' && typeof m.getHotInstance === 'function') {\n`
  + `        var inst = m.getHotInstance();\n`
  + `        if (inst && typeof inst.countRows === 'function') return inst;\n`
  + `      }\n`
  + `    } catch (_) {}\n`
  + `  }\n`
  + `  for (var k2 in window) {\n`
  + `    try {\n`
  + `      var o = window[k2];\n`
  + `      if (o && typeof o === 'object' && typeof o.countRows === 'function' && typeof o.getData === 'function') return o;\n`
  + `    } catch (_) {}\n`
  + `  }\n`
  + `  return null;\n`
  + `})()`;

/**
 * Wait for Handsontable to finish rendering.
 */
export async function waitForRender(page, timeout = 15_000) {
  await page.waitForSelector(SELECTOR_TABLE, { state: 'visible', timeout });
  // Use new Function() to eval FIND_HOT safely
  await page.evaluate((code) => {
    var fn = new Function('return ' + code);
    var hot = fn();
    if (hot && typeof hot.render === 'function') hot.render();
  }, FIND_HOT).catch(() => {});
  await page.waitForTimeout(500);
}

/**
 * Read cell value (row/col are 0-based data indices).
 */
export async function getCellValue(page, row, col) {
  const value = await page.evaluate(
    ({ r, c, code }) => {
      var fn = new Function('return ' + code);
      var hot = fn();
      if (hot && typeof hot.getDataAtCell === 'function') {
        var v = hot.getDataAtCell(r, c);
        return v == null ? '' : String(v);
      }
      return '';
    },
    { r: row, c: col, code: FIND_HOT },
  );
  return value;
}

/**
 * Double-click a cell, type a value, press Enter.
 */
export async function editCell(page, row, col, value) {
  await page.waitForTimeout(500);
  const cell = page.locator('.handsontable .htCore tbody tr, [role="treegrid"] [role="rowgroup"]:first-child [role="row"]').nth(row)
    .locator('td, [role="gridcell"]').nth(col);

  if (await cell.count() > 0) {
    await cell.scrollIntoViewIfNeeded();
    await cell.dblclick({ timeout: 10_000 }).catch(() => {});
  }

  await page.waitForTimeout(300);
  const editor = page.locator('.handsontableInput, .htEditor textarea, textarea.handsontableInput');
  await editor.waitFor({ state: 'visible', timeout: 5_000 }).catch(() => {});
  await editor.fill(value);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(500);
}

/**
 * Get number of data rows.
 */
export async function getRowCount(page) {
  try {
    return await page.evaluate((code) => {
      var fn = new Function('return ' + code);
      var hot = fn();
      return (hot && typeof hot.countRows === 'function') ? hot.countRows() : 0;
    }, FIND_HOT);
  } catch { return 0; }
}

/**
 * Get the full data array.
 */
export async function getTableData(page) {
  try {
    return await page.evaluate((code) => {
      var fn = new Function('return ' + code);
      var hot = fn();
      return (hot && typeof hot.getData === 'function') ? hot.getData() : [];
    }, FIND_HOT);
  } catch { return []; }
}

/**
 * Select dropdown option in a cell.
 */
export async function selectDropdown(page, row, col, optionLabel) {
  const cell = page.locator('.handsontable .htCore tbody tr, [role="treegrid"] [role="rowgroup"]:first-child [role="row"]').nth(row)
    .locator('td, [role="gridcell"]').nth(col);
  if (await cell.count() > 0) {
    await cell.scrollIntoViewIfNeeded();
    await cell.click({ timeout: 10_000 }).catch(() => {});
  }
  await page.waitForTimeout(500);
  const option = page.locator('.htDropdownMenu .ht_master table.htCore tbody td, .htDropdownMenu [role="option"]')
    .filter({ hasText: optionLabel }).first();
  if (await option.count() > 0) await option.click();
  await page.waitForTimeout(300);
}

/**
 * Get column headers.
 */
export async function getColumnHeaders(page) {
  try {
    return await page.evaluate((code) => {
      var fn = new Function('return ' + code);
      var hot = fn();
      if (hot && typeof hot.getColHeader === 'function') return hot.getColHeader();
      var ths = document.querySelectorAll('.handsontable thead th .colHeader, [role="treegrid"] [role="columnheader"]');
      return Array.from(ths).map(function (th) { return (th.textContent || '').trim(); });
    }, FIND_HOT);
  } catch { return []; }
}
