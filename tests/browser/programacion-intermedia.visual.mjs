import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/programacion-intermedia.json', import.meta.url),
  'utf8',
));
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(
  ({ theme, viewport }) => theme === 'dark' && viewport.width >= 1180,
);

async function mockDeterministicData(page) {
  await page.route('**/api/general/restriction-config**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: false }),
  }));
  await page.route('**/programacion-intermedia/filtros', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }),
  }));
  await page.route('**/api/pi/list**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }),
  }));
}

for (const scenario of VISUAL_SCENARIOS) {
  test(`${scenario.id} remains stable`, async ({ page }) => {
    await mockDeterministicData(page);
    await loginAndSelectProject(page, PROJECTS[0]);
    await page.setViewportSize(scenario.viewport);
    await page.evaluate((value) => localStorage.setItem('aia-theme', value), scenario.theme);
    await page.goto(scenario.route, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    await page.evaluate(() => document.fonts.ready);
    await expect(page.locator('#save-status')).toBeHidden();
    await expect(page).toHaveScreenshot(
      path.basename(scenario.golden),
      {
        fullPage: false,
        // Mismo criterio que `programa-general.visual.mjs`: 0,2 % en vez del 3 % anterior. El ruido
        // de renderizado medido entre corridas es cero, asi que este valor es holgura deliberada
        // por si cambia la maquina, no una necesidad del render actual.
        maxDiffPixelRatio: 0.002,
      },
    );
  });
}
