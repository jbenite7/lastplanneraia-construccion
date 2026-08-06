import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { scanAccessibility } from './support/accessibility.mjs';

const VIEWPORTS = [
  { name: 'desktop', width: 1180, height: 820 },
  { name: 'wide-desktop', width: 1440, height: 900 },
];
const ADMIN = { username: 'test.A', password: 'aia2026' };

test.beforeEach(async ({ page }) => {
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
});

for (const viewport of VIEWPORTS) {
  for (const theme of ['dark']) {
    test(`${viewport.name} ${theme} consume el núcleo aprobado`, async ({ page }, testInfo) => {
      await page.setViewportSize(viewport);
      await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
      await page.waitForTimeout(500);

      await expect(page.locator('body.aia-shell')).toBeVisible();
      await expect(page.locator('main.aia-page')).toBeVisible();
      await expect(page.locator('.aia-action-group')).toBeVisible();
      await expect(page.locator('.aia-filter-form')).toBeVisible();
      await expect(page.locator('#hot-container.aia-grid-shell')).toBeAttached();

      const state = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        // Piso de objetivo: 24px, WCAG 2.2 SC 2.5.8 (AA) — no 44px, que es SC 2.5.5 (AAA) y
        // heuristica tactil. DESIGN.md §5 bis declara la excepcion para la familia de tablas
        // desktop, que nombra a `/programa-general`: sin equivalente movil por contrato
        // (AGENTS.md §Routing: desktop >=1180px, dark), el riesgo que previenen los 44px no
        // existe aqui, «pero el suelo de 24x24px si aplica y no se cruza». El CSS ya se movio a
        // ese suelo el 2026-08-03 (T-5): `toolbar-controls.css` da a estos chips
        // `min-height: var(--ds-control-compact-min)` = 24px (`tokens.css:440`). Esta asercion
        // se quedo en 44 y por eso abortaba la corrida ANTES de axe — medido 2026-08-05: los 7
        // chips miden 34px en 1180x820 y en 1440x900, o sea 10px POR ENCIMA del suelo real.
        // Bajar el numero aqui no relaja nada: alinea el test con el contrato escrito y devuelve
        // la ejecucion de axe, que es la guardia que de verdad faltaba.
        filtersVisible: [...document.querySelectorAll('#pgLegend .pg-filter-chip')]
          .every((item) => {
            const box = item.getBoundingClientRect();
            return box.height >= 24 && box.width >= 24;
          }),
        filterBoxes: [...document.querySelectorAll('#pgLegend .pg-filter-chip')]
          .map((item) => {
            const box = item.getBoundingClientRect();
            return `${Math.round(box.width)}x${Math.round(box.height)}`;
          }),
        legendContained: (() => {
          const legend = document.querySelector('#pgLegend');
          if (!legend) return false;
          const boundary = legend.getBoundingClientRect();
          return [...legend.querySelectorAll('.pg-filter-chip')].every((item) => {
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
