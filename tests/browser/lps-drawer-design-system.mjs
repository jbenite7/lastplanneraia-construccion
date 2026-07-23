import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };

// El Cajón Contextual LPS es un parcial compartido; se valida en
// /dashboard/escalamientos porque incluye el mismo drawer sin autosave.
// Alcance contractual: desktop dark 1180x820.
test.describe('LPS drawer design system', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/dashboard/escalamientos', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#lps_sidebar_trigger')).toBeVisible({ timeout: 45000 });
  });

  test('abre accesible, sin estilos inline y estable en desktop dark', async ({ page }) => {
    const drawer = page.locator('#lps_drawer');
    await expect(drawer).toHaveAttribute('aria-hidden', 'true');
    expect(await drawer.evaluate((el) => el.hasAttribute('inert'))).toBe(true);

    await page.locator('#lps_sidebar_trigger').click();
    await expect(drawer).toHaveAttribute('aria-hidden', 'false');
    await expect(drawer).toHaveClass(/open/);
    expect(await drawer.evaluate((el) => el.hasAttribute('inert'))).toBe(false);
    // Transición de apertura (300ms) + foco diferido al botón de cierre (320ms).
    await page.waitForTimeout(700);
    await expect(page.locator('#lps_drawer_close')).toBeFocused();

    // El parcial migrado no declara estilos inline; el JS solo usa
    // style.display para revelar tarjetas y en el estado por defecto
    // ninguna está revelada.
    const inlineNodes = await drawer.evaluate((el) => (
      [...el.querySelectorAll('[style]')].map((n) => `${n.id || n.className}`)
    ));
    expect(inlineNodes, inlineNodes.join(', ')).toEqual([]);

    const axe = await new AxeBuilder({ page })
      .include('#lps_drawer')
      .withTags(['wcag2a', 'wcag2aa'])
      .analyze();
    const serious = axe.violations.filter((v) => ['critical', 'serious'].includes(v.impact));
    expect(serious.map((v) => v.id)).toEqual([]);

    await page.evaluate(() => document.fonts.ready);
    await expect(drawer).toHaveScreenshot('lps-drawer-dark-1180x820.png');

    await page.locator('#lps_drawer_close').click();
    await expect(drawer).toHaveAttribute('aria-hidden', 'true');
    expect(await drawer.evaluate((el) => el.hasAttribute('inert'))).toBe(true);
  });
});
