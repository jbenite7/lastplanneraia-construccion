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

    // The group heading stays muted (secondary), not the primary text that
    // dark-mode.css's `body.dark-mode h1..h6 !important` would otherwise force.
    const [headingColor, secondaryToken] = await Promise.all([
      sidebar.locator('.aia-sidebar__group h3').first().evaluate((el) => getComputedStyle(el).color),
      page.evaluate(() => {
        const probe = document.createElement('span');
        probe.style.color = getComputedStyle(document.documentElement).getPropertyValue('--ds-active-text-secondary').trim();
        document.body.append(probe);
        const value = getComputedStyle(probe).color;
        probe.remove();
        return value;
      }),
    ]);
    expect(headingColor, 'group heading is not muted').toBe(secondaryToken);

    // The group heading is sentence case, not the uppercase eyebrow that
    // remained before, and legacy globals (styles.css `*` reset / `h1..h6`
    // tracking) must not claw back its inset or letter-spacing.
    const heading = await sidebar.locator('.aia-sidebar__group h3').first().evaluate((el) => {
      const style = getComputedStyle(el);
      return { textTransform: style.textTransform, marginLeft: style.marginLeft, marginTop: style.marginTop, letterSpacing: style.letterSpacing };
    });
    expect(heading.textTransform, 'group heading must not be uppercase').toBe('none');
    expect(heading.letterSpacing, 'group heading must not keep legacy tracking').toBe('normal');
    expect(parseFloat(heading.marginLeft), 'group heading needs a left inset').toBeGreaterThan(0);
    expect(parseFloat(heading.marginTop), 'group heading needs top spacing').toBeGreaterThan(0);

    const main = page.locator('.project-selector-main');
    const sidebarWidth = await sidebar.evaluate((el) => el.getBoundingClientRect().width);
    const mainLeft = await main.evaluate((el) => el.getBoundingClientRect().left);
    expect(Math.round(mainLeft)).toBe(Math.round(sidebarWidth));

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);

    // The card grid must clear the rail and breathe symmetrically. A `* { padding: 0 }`
    // reset in a late layer strips the vendor gutters, so this is easy to lose.
    const gutters = await page.evaluate(() => {
      const box = (el) => el.getBoundingClientRect();
      const railRight = box(document.querySelector('.aia-navigation--sidebar')).right;
      const items = [...document.querySelectorAll('#projectGrid .project-item')];
      const perRow = items.filter((i) => Math.abs(box(i).top - box(items[0]).top) < 2).length;
      return {
        left: Math.round(box(items[0]).left - railRight),
        right: Math.round(window.innerWidth - box(items[perRow - 1]).right),
        between: perRow > 1 ? Math.round(box(items[1]).left - box(items[0]).right) : null,
      };
    });
    expect(gutters.left, 'cards touch the rail').toBeGreaterThan(0);
    expect(gutters.between, 'cards touch each other').toBeGreaterThan(0);
    expect(Math.abs(gutters.left - gutters.right), 'left/right gutters are lopsided').toBeLessThanOrEqual(2);

    // A long real account name must ellipsize inside the rail, never widen it.
    // `.aia-menu { width: fit-content }` in primitives.css would otherwise win.
    await sidebar.locator('.aia-sidebar__account .aia-sidebar__label').evaluate((el) => {
      el.textContent = 'Usuario · Juan Felipe Benitez Ramos';
    });
    const railRight = await sidebar.evaluate((el) => el.getBoundingClientRect().right);
    for (const selector of ['.aia-sidebar__account', '.aia-sidebar__account .aia-sidebar__label']) {
      const right = await sidebar.locator(selector).evaluate((el) => el.getBoundingClientRect().right);
      expect(right, `${selector} escapes the rail`).toBeLessThanOrEqual(railRight);
    }
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth),
    ).toBeLessThanOrEqual(0);

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

    // Anchor menu items must take the panel's colour, not the vendor link blue.
    const [logoutColor, themeColor] = await Promise.all([
      sidebar.getByRole('menuitem', { name: 'Cerrar sesión' }).evaluate((el) => getComputedStyle(el).color),
      sidebar.locator('.aia-theme-switch').evaluate((el) => getComputedStyle(el).color),
    ]);
    expect(logoutColor, 'logout link uses the vendor anchor colour').toBe(themeColor);

    await accountTrigger.press('Escape');
    await expect(accountPanel).toBeHidden();
    await expect(accountTrigger).toBeFocused();

    await logout(page);
  });
}
