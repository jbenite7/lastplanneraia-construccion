import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { loginAndSelectProject } from './support/session.mjs';

for (const width of [390, 768]) {
  test(`Programacion Intermedia renders its two records as cards with a hidden grid at ${width}px`, async ({ page }) => {
    const project = PROJECTS[0];
    const errors = installErrorCollectors(page);
    await page.setViewportSize({ width, height: 844 });
    await loginAndSelectProject(page, project);

    const listResponse = page.waitForResponse((response) => (
      response.url().includes('/api/pi/list') && response.request().method() === 'GET'
    ));
    await page.goto('/programacion-intermedia', { waitUntil: 'commit' });
    await listResponse;

    await expect.poll(() => page.evaluate(() => {
      const grid = document.getElementById('hot-container');
      const cards = [...document.querySelectorAll('#mobile-card-view .pi-mobile-card')];
      const instance = window.PIHotModule && window.PIHotModule.getHotInstance();
      return {
        cardCount: cards.length,
        hiddenGrid: getComputedStyle(grid).display === 'none' && grid.getClientRects().length === 0,
        instanceReady: Boolean(instance),
        sourceRows: instance ? instance.getSourceData().length : 0,
        visibleCards: cards.filter((card) => card.getClientRects().length > 0).length,
      };
    }), {
      message: 'The hidden Handsontable must initialize safely and project every row into a visible mobile card.',
    }).toEqual({
      cardCount: 2,
      hiddenGrid: true,
      instanceReady: true,
      sourceRows: 2,
      visibleCards: 2,
    });

    assertNoRuntimeErrors(errors);
  });
}

test('Programacion Intermedia preserves its two-row Handsontable on desktop', async ({ page }) => {
  const project = PROJECTS[0];
  const errors = installErrorCollectors(page);
  await page.setViewportSize({ width: 1280, height: 900 });
  await loginAndSelectProject(page, project);

  const listResponse = page.waitForResponse((response) => response.url().includes('/api/pi/list'));
  await page.goto('/programacion-intermedia', { waitUntil: 'commit' });
  await listResponse;

  await expect.poll(() => page.evaluate(() => ({
    cards: document.querySelectorAll('#mobile-card-view .pi-mobile-card').length,
    renderedRows: document.querySelectorAll('#hot-container .ht_master tbody tr').length,
    sourceRows: window.PIHotModule?.getHotInstance()?.getSourceData().length || 0,
  }))).toEqual({ cards: 0, renderedRows: 2, sourceRows: 2 });
  assertNoRuntimeErrors(errors);
});
