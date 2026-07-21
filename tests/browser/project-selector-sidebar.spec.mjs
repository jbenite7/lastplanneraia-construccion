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

    // styles.css's `* { padding: 0 }` reset (module layer) would collapse every
    // rail inset; the component's !important paddings must hold so content is
    // not flush against the edge and the icon columns line up.
    const insets = await sidebar.evaluate((rail) => {
      const railLeft = rail.getBoundingClientRect().left;
      const at = (sel) => { const el = rail.querySelector(sel); return el ? Math.round(el.getBoundingClientRect().left - railLeft) : null; };
      return { linkIcon: at('.aia-sidebar__link .aia-icon'), utilityIcon: at('.aia-sidebar__utility .aia-icon') };
    });
    expect(insets.linkIcon, 'nav item icons must be inset from the rail edge').toBeGreaterThan(16);
    expect(Math.abs(insets.linkIcon - insets.utilityIcon), 'nav and footer icon columns must align').toBeLessThanOrEqual(2);

    const main = page.locator('.project-selector-main');
    const sidebarWidth = await sidebar.evaluate((el) => el.getBoundingClientRect().width);
    const mainLeft = await main.evaluate((el) => el.getBoundingClientRect().left);
    expect(Math.round(mainLeft)).toBe(Math.round(sidebarWidth));

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);

    // handsontable-module.css locks `body { overflow: hidden }` on desktop for
    // grid pages; the selector must stay a scrollable document. Force content
    // past the fold and confirm the document actually scrolls to reveal it.
    const verticalScroll = await page.evaluate(() => {
      const probe = document.createElement('div');
      probe.style.height = '600px';
      probe.dataset.e2eScrollProbe = 'true';
      document.querySelector('.project-selector-main').appendChild(probe);
      window.scrollTo(0, document.documentElement.scrollHeight);
      const scrolledY = Math.round(window.scrollY || document.documentElement.scrollTop);
      const bodyOverflowY = getComputedStyle(document.body).overflowY;
      probe.remove();
      window.scrollTo(0, 0);
      return { scrolledY, bodyOverflowY };
    });
    expect(verticalScroll.bodyOverflowY, 'body scroll is locked to hidden').not.toBe('hidden');
    expect(verticalScroll.scrolledY, 'document cannot scroll past the fold').toBeGreaterThan(0);

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

    // Hardened menu semantics: the trigger advertises the popup, clicking away
    // dismisses it, and the role=menu is keyboard navigable.
    await expect(accountTrigger).toHaveAttribute('aria-haspopup', 'menu');

    // Dismiss on outside click.
    await page.locator('#main-content').click({ position: { x: 10, y: 10 } });
    await expect(accountPanel).toBeHidden();

    // Arrow keys open the menu and move focus onto the items; once open the
    // keys act on the focused menuitem, so drive them through the keyboard.
    const menuItems = sidebar.locator('[data-aia-menu-panel] [role="menuitem"]');
    await accountTrigger.press('ArrowDown');
    await expect(accountPanel).toBeVisible();
    await expect(menuItems.first()).toBeFocused();
    await page.keyboard.press('End');
    await expect(menuItems.last()).toBeFocused();
    await page.keyboard.press('Home');
    await expect(menuItems.first()).toBeFocused();
    await page.keyboard.press('ArrowDown');
    await expect(menuItems.nth(1)).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(accountPanel).toBeHidden();
    await expect(accountTrigger).toBeFocused();

    await logout(page);
  });
}
