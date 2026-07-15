import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { scanAccessibility } from './support/accessibility.mjs';

const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 1180, height: 820 },
  { name: 'desktop', width: 1440, height: 900 },
];
const ADMIN = { username: 'test.A', password: 'aia2026' };

test.beforeEach(async ({ page }) => {
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
});

for (const viewport of VIEWPORTS) {
  for (const theme of ['dark', 'linen']) {
    test(`${viewport.name} ${theme} consume el núcleo aprobado`, async ({ page }, testInfo) => {
      await page.setViewportSize(viewport);
      await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
      await page.waitForTimeout(500);
      await page.evaluate((value) => window.AiaDesignSystem.setTheme(value), theme);

      await expect(page.locator('body.aia-shell')).toBeVisible();
      await expect(page.locator('main.aia-page')).toBeVisible();
      await expect(page.locator('.aia-action-group')).toBeVisible();
      await expect(page.locator('.aia-filter-form')).toBeVisible();
      await expect(page.locator('#hot-container.aia-grid-shell')).toBeAttached();

      const state = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        filtersVisible: [...document.querySelectorAll('#pgLegend .pdc-legend-item')]
          .every((item) => item.getBoundingClientRect().height >= 44),
        filterHeights: [...document.querySelectorAll('#pgLegend .pdc-legend-item')]
          .map((item) => Math.round(item.getBoundingClientRect().height)),
        legendContained: (() => {
          const legend = document.querySelector('#pgLegend');
          if (!legend) return false;
          const boundary = legend.getBoundingClientRect();
          return [...legend.querySelectorAll('.pdc-legend-item')].every((item) => {
            const box = item.getBoundingClientRect();
            return box.left >= boundary.left - 1 && box.right <= boundary.right + 1;
          });
        })(),
        gridVisible: !document.querySelector('#hot-container')?.hidden,
        cardsVisible: !document.querySelector('#mobile-card-view')?.hidden,
        theme: document.documentElement.dataset.aiaTheme,
      }));
      expect(state.overflow).toBeLessThanOrEqual(1);
      expect(state.filtersVisible, JSON.stringify(state)).toBe(true);
      expect(state.legendContained, JSON.stringify(state)).toBe(true);
      expect(state.gridVisible || state.cardsVisible).toBe(true);
      expect(state.theme).toBe(theme);

      const report = await scanAccessibility(page, {
        surface: `programa-general:${viewport.name}:${theme}`,
        include: 'main#contenido',
        reportPath: testInfo.outputPath('axe-report.json'),
      });
      expect(report.blocking).toEqual([]);
    });
  }
}
