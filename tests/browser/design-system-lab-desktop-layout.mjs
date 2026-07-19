import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];
const FAMILIES = [
  'foundations', 'shell-navigation', 'page-structure', 'actions',
  'forms-filters', 'states-feedback', 'data-display', 'overlays',
  'vendor-adapters', 'bi-primitives',
];

async function readLayoutContract(page, scopeSelector) {
  return page.evaluate((scope) => {
    const root = document.documentElement;
    const panel = document.querySelector(scope);
    const textSelectors = 'h1,h2,h3,h4,h5,h6,p,label,button,.aia-chip,[data-state-text]';
    const textViolations = [...panel.querySelectorAll(textSelectors)].flatMap((element) => {
      const style = getComputedStyle(element);
      const invalid = style.wordBreak === 'break-all'
        || style.overflowWrap === 'anywhere' || style.hyphens === 'auto';
      return invalid ? [{ tag: element.tagName, text: element.textContent.trim().slice(0, 80) }] : [];
    });
    const targetSelectors = [
      'a[href]', 'button', 'select', 'textarea', 'summary',
      'input:not([type="hidden"])', '[role="button"]', '[role="option"]',
      '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const targetViolations = [...panel.querySelectorAll(targetSelectors)].flatMap((element) => {
      const style = getComputedStyle(element);
      if (style.display === 'none' || style.visibility === 'hidden'
        || element.closest('[hidden]') || element.getClientRects().length === 0) return [];
      const target = ['radio', 'checkbox'].includes(element.getAttribute('type'))
        ? element.closest('label') || element
        : element;
      const box = target.getBoundingClientRect();
      return box.width + 0.01 < 44 || box.height + 0.01 < 44
        ? [{ label: element.getAttribute('aria-label') || element.textContent.trim().slice(0, 80), width: box.width, height: box.height }]
        : [];
    });
    return {
      overflow: root.scrollWidth - root.clientWidth,
      textViolations,
      targetViolations,
    };
  }, scopeSelector);
}

for (const viewport of VIEWPORTS) {
  test(`all laboratory families keep desktop layout and target contracts at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    for (const family of FAMILIES) {
      await page.locator(`[data-lab-family-link][data-family-target="${family}"]`).click();
      const scope = `[data-family="${family}"]`;
      await expect(page.locator(scope)).toBeVisible();
      const contract = await readLayoutContract(page, scope);
      expect(contract.overflow, `${family}: horizontal overflow`).toBeLessThanOrEqual(1);
      expect(contract.textViolations, `${family}: fragmented text`).toEqual([]);
      expect(contract.targetViolations, `${family}: targets below 44px`).toEqual([]);
    }
  });

  test(`the sticky family rail stays below the 97px laboratory header at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.goto('/internal/design-system?family=vendor-adapters', { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => window.scrollTo(0, 640));
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(0);

    const geometry = await page.evaluate(() => {
      const header = document.querySelector('.ds-lab__header').getBoundingClientRect();
      const rail = document.querySelector('.ds-lab__rail-wrap').getBoundingClientRect();
      return {
        headerTop: header.top,
        headerBottom: header.bottom,
        railTop: rail.top,
        overlap: Math.max(0, header.bottom - rail.top),
      };
    });

    expect(geometry.headerTop, 'the global header must remain fully sticky').toBe(0);
    expect(geometry.overlap, `rail top ${geometry.railTop} overlaps header bottom ${geometry.headerBottom}`).toBe(0);
  });
}
