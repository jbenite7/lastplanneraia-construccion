import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');
const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet-horizontal', width: 1180, height: 820 },
];

async function openShell(page, viewport) {
  await page.setViewportSize(viewport);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded', timeout: 45000 });
  await expect(page.locator('#drawerToggle')).toBeVisible({ timeout: 45000 });
  await expect(page.locator('#lps_sidebar_trigger')).toBeVisible({ timeout: 45000 });
}

async function expectTheme(page, theme) {
  // F0/Task 9: theme.js ya no expone setTheme; dark se aplica sin conmutacion.
  // Esto solo confirma el estado en vez de forzarlo.
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', theme);
}

async function expectMinimumTarget(locator, label) {
  const box = await locator.boundingBox();
  expect.soft(box, `${label} must have a box`).not.toBeNull();
  expect.soft(box?.width || 0, `${label} width`).toBeGreaterThanOrEqual(44);
  expect.soft(box?.height || 0, `${label} height`).toBeGreaterThanOrEqual(44);
}

async function expectNoHorizontalOverflow(page, label) {
  const overflow = await page.evaluate(() => {
    const root = document.documentElement;
    return root.scrollWidth - root.clientWidth;
  });
  expect.soft(overflow, label).toBeLessThanOrEqual(1);
}

async function expectFocusInside(page, selector, label) {
  const inside = await page.evaluate((containerSelector) => {
    const container = document.querySelector(containerSelector);
    return Boolean(container && container.contains(document.activeElement));
  }, selector);
  expect(inside, label).toBe(true);
}

test.describe('Design system foundation shell drawers', () => {
  test('navbar drawer works on mobile and tablet in dark', async ({ page }) => {
    await page.setViewportSize(VIEWPORTS[0]);
    await loginAndSelectProject(page, DA_PORTO);

    for (const viewport of VIEWPORTS) {
      await openShell(page, viewport);
      const trigger = page.locator('#drawerToggle');
      const close = page.locator('#drawerClose');

      expect.soft(await trigger.getAttribute('aria-controls')).toBe('aiaNavbar');
      expect.soft(await trigger.getAttribute('aria-expanded')).toBe('false');
      await expectMinimumTarget(trigger, 'navbar trigger');
      await expectMinimumTarget(close, 'navbar close');

      for (const theme of ['dark']) {
        await expectTheme(page, theme);
        await expectNoHorizontalOverflow(page, `${viewport.name} navbar ${theme} overflow`);
      }
    }
  });
});
