import { expect, test } from '@playwright/test';
import { CREDENTIALS } from './fixtures/projects.mjs';
import { login, logout } from './support/session.mjs';

const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];

for (const viewport of VIEWPORTS) {
  test(`project selector sidebar is operable at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await login(page, CREDENTIALS);

    const sidebar = page.locator('[data-shell-pattern="sidebar"]');
    await expect(sidebar).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    const projectsLink = sidebar.locator('[data-destination-id="proyectos"]');
    await expect(projectsLink).toHaveAttribute('aria-current', 'page');
    await expect(sidebar.locator('[aria-current="page"]')).toHaveCount(1);
    await expect(sidebar.locator('[data-sidebar-notifications]')).toHaveCount(0);

    const main = page.locator('.project-selector-main');
    const sidebarWidth = await sidebar.evaluate((el) => el.getBoundingClientRect().width);
    const mainLeft = await main.evaluate((el) => el.getBoundingClientRect().left);
    expect(Math.round(mainLeft)).toBe(Math.round(sidebarWidth));

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);

    const toggle = sidebar.locator('[data-sidebar-toggle]');
    await toggle.click();
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'collapsed');
    await expect
      .poll(() => main.evaluate((el) => Math.round(el.getBoundingClientRect().left)))
      .toBe(await sidebar.evaluate((el) => Math.round(el.getBoundingClientRect().width)));
    await toggle.click();
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');

    const accountTrigger = sidebar.locator('[data-aia-menu-trigger]');
    const accountPanel = sidebar.locator('[data-aia-menu-panel]');
    await expect(accountPanel).toBeHidden();
    await accountTrigger.click();
    await expect(accountPanel).toBeVisible();

    const themeToggle = sidebar.locator('.aia-theme-switch');
    await themeToggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'linen');
    await page.evaluate(() => window.AiaDesignSystem.setTheme('dark'));

    await expect(sidebar.getByRole('menuitem', { name: 'Cerrar sesión' })).toHaveAttribute('href', '/logout');

    await accountTrigger.press('Escape');
    await expect(accountPanel).toBeHidden();
    await expect(accountTrigger).toBeFocused();

    await logout(page);
  });
}
