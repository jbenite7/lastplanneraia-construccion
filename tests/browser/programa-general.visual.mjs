import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/programa-general.json', import.meta.url),
  'utf8',
));
const ADMIN = { username: 'test.A', password: 'aia2026' };
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(({ theme, viewport }) => theme === 'dark' && viewport.width >= 1180);

async function mockDeterministicData(page) {
  await page.route('**/api/general/restriction-config**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: false }),
  }));
  await page.route('**/api/general/codigos**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }),
  }));
  await page.route('**/programa-general/filtros', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }),
  }));
  await page.route('**/api/general/list**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }),
  }));
  await page.route('**/api/general/update-batch**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ respuesta: 'BIEN' }),
  }));
}

async function storeTheme(page, theme) {
  await page.evaluate((value) => localStorage.setItem('aia-theme', value), theme);
}

async function expectLegendContained(page) {
  await expect.poll(() => page.locator('#pgLegend').evaluate((legend) => {
    const boundary = legend.getBoundingClientRect();
    return [...legend.querySelectorAll('.pg-filter-chip')].every((item) => {
      const box = item.getBoundingClientRect();
      return box.left >= boundary.left - 1 && box.right <= boundary.right + 1;
    });
  })).toBe(true);
}

for (const scenario of VISUAL_SCENARIOS) {
    test(`${scenario.id} remains stable`, async ({ page }) => {
      await mockDeterministicData(page);
      await loginAndSelectProject(page, PROJECTS[0], ADMIN);
      await changeWeek(page, PROJECTS[0].maxWeek, '/programa-general');
      await page.setViewportSize(scenario.viewport);
      await storeTheme(page, scenario.theme);
      await page.reload({ waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
      await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
      await expectLegendContained(page);
      await page.evaluate(() => document.fonts.ready);
      // The auto-update flow emits a short-lived success badge that can still be
      // visible depending on runner timing. Wait for it to settle so the visual
      // contract captures the stable toolbar state instead of a transient toast.
      await expect(page.locator('#save-status')).toBeHidden({ timeout: 10000 });
      await expect(page).toHaveScreenshot(
        path.basename(scenario.golden),
        {
          fullPage: false,
          maxDiffPixelRatio: 0.03,
        },
      );
    });
}
