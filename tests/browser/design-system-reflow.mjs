import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const REFLOW = { width: 320, height: 720 };
const FAMILIES = [
  'foundations', 'shell-navigation', 'page-structure', 'actions',
  'forms-filters', 'states-feedback', 'data-display', 'overlays',
  'vendor-adapters', 'bi-primitives',
];

async function openAsAdmin(page, path) {
  await page.setViewportSize(REFLOW);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await page.goto(path, { waitUntil: 'domcontentloaded' });
}

async function reflowState(page, scopeSelector) {
  return page.evaluate((scope) => {
    const root = document.documentElement;
    const text = document.querySelector(scope);
    const selectors = 'h1,h2,h3,h4,h5,h6,p,label,button,.aia-chip,[data-state-text]';
    const violations = [...text.querySelectorAll(selectors)].flatMap((element) => {
      const style = getComputedStyle(element);
      const invalid = style.wordBreak === 'break-all'
        || style.overflowWrap === 'anywhere' || style.hyphens === 'auto';
      return invalid ? [{ tag: element.tagName, text: element.textContent.trim().slice(0, 80) }] : [];
    });
    return {
      overflow: root.scrollWidth - root.clientWidth,
      violations,
    };
  }, scopeSelector);
}

test('laboratory families reflow at 320 CSS px without fragmenting words', async ({ page }) => {
  await openAsAdmin(page, '/internal/design-system');
  const selector = page.locator('[data-lab-family]');
  for (const family of FAMILIES) {
    await selector.selectOption(family);
    const scope = `[data-family="${family}"]`;
    await expect(page.locator(scope)).toBeVisible();
    for (const theme of ['dark', 'linen']) {
      await page.evaluate((value) => window.AiaDesignSystem.setTheme(value), theme);
      const state = await reflowState(page, scope);
      expect(state.overflow, `${family}:${theme}`).toBeLessThanOrEqual(1);
      expect(state.violations, `${family}:${theme}`).toEqual([]);
    }
  }
});

test('Programa General pilot reflows at 320 CSS px in both themes', async ({ page }) => {
  await openAsAdmin(page, '/programa-general');
  await page.waitForFunction(() => Boolean(document.querySelector('#mobile-card-view')));
  await expect(page.locator('main#contenido')).toBeVisible();
  for (const theme of ['dark', 'linen']) {
    await page.evaluate((value) => window.AiaDesignSystem.setTheme(value), theme);
    const state = await reflowState(page, 'main#contenido');
    expect(state.overflow, `programa-general:${theme}`).toBeLessThanOrEqual(1);
    expect(state.violations, `programa-general:${theme}`).toEqual([]);
  }
});
