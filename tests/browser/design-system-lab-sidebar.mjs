import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];

for (const viewport of VIEWPORTS) {
  test(`sidebar states and geometry remain operable at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.goto('/internal/design-system?family=shell-navigation', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    const candidate = page.locator('[data-family="shell-navigation"] .ds-shell-candidate');
    const sidebar = candidate.locator('[data-shell-pattern="sidebar"]');
    const toggle = sidebar.locator('[data-sidebar-toggle]');
    await expect(sidebar).toBeVisible();
    await expect(sidebar.locator('[data-sidebar-group]')).toHaveCount(3);
    await expect(sidebar.locator('[aria-current="page"]')).toHaveCount(1);
    await expect(sidebar.locator('[data-sidebar-group="information"]')).toContainText('Control Tower - Informes');
    await expect(sidebar.locator('[data-sidebar-group="information"]')).toContainText('Profesionales');
    await expect(sidebar.locator('[data-sidebar-group="information"]')).toContainText('Subcontratistas');
    await expect(sidebar.locator('[data-sidebar-group="information"]')).toContainText('Indicadores LPS');
    await expect(sidebar.locator('[data-sidebar-group="information"]')).toContainText('Control de Cambios');
    await expect(sidebar.locator('[data-sidebar-group="information"]')).not.toContainText('Integración');
    await expect(sidebar.locator('[data-sidebar-group="information"] [data-sidebar-item]')).toHaveCount(6);
    await expect(sidebar.locator('[data-sidebar-group="obra"]')).toContainText('Programa General');
    await expect(sidebar.locator('[data-sidebar-group="obra"]')).toContainText('Programación Intermedia');
    await expect(sidebar.locator('[data-sidebar-group="obra"]')).toContainText('Programación Semanal');
    await expect(sidebar.locator('[data-sidebar-group="obra"]')).toContainText('Actualizar Cronograma');
    await expect(sidebar.locator('[data-sidebar-group="obra"] [data-sidebar-item]')).toHaveCount(4);
    await expect(sidebar.locator('[data-sidebar-group="compras"]')).toContainText('Familias de Actividades');
    await expect(sidebar.locator('[data-sidebar-group="compras"]')).toContainText('Paquetes de Contratación');
    await expect(sidebar.locator('[data-sidebar-group="compras"]')).toContainText('Plan de Compras');
    await expect(sidebar.locator('[data-sidebar-group="compras"] [data-sidebar-item]')).toHaveCount(3);

    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'collapsed');
    const collapsedInitial = await sidebar.evaluate((element) => {
      const probe = document.createElement('div');
      probe.style.width = 'var(--ds-sidebar-width-collapsed)';
      document.body.append(probe);
      const collapsed = probe.getBoundingClientRect().width;
      probe.remove();
      return { width: element.getBoundingClientRect().width, collapsed };
    });
    expect(collapsedInitial.width).toBeGreaterThanOrEqual(collapsedInitial.collapsed - 1);
    expect(collapsedInitial.width).toBeLessThanOrEqual(collapsedInitial.collapsed + 1);

    // En colapsado el label se convierte en una píldora flotante con
    // visibility:hidden, que lo saca del árbol de accesibilidad. El nombre
    // accesible no puede depender de él: cada control lo expone por su cuenta,
    // o el rail queda mudo para lectores de pantalla (axe button-name/link-name).
    await expect(toggle).toHaveAccessibleName('Expandir menú');
    await expect(sidebar.locator('[data-aia-menu-trigger]'))
      .toHaveAccessibleName('Usuario · Administrador');
    for (const name of [
      'Control Tower - Informes', 'Semanas del Proyecto', 'Profesionales', 'Subcontratistas',
      'Indicadores LPS', 'Control de Cambios', 'Programa General', 'Programación Intermedia',
      'Programación Semanal', 'Actualizar Cronograma', 'Familias de Actividades',
      'Paquetes de Contratación', 'Plan de Compras',
    ]) {
      await expect(sidebar.getByRole('link', { name, exact: true })).toHaveCount(1);
    }

    await toggle.click();
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');
    const expandedToken = await page.evaluate(() => {
      const probe = document.createElement('div');
      probe.style.width = 'var(--ds-sidebar-width-expanded)';
      document.body.append(probe);
      const width = probe.getBoundingClientRect().width;
      probe.remove();
      return width;
    });
    await expect.poll(() => sidebar.evaluate((element) => element.getBoundingClientRect().width))
      .toBeGreaterThanOrEqual(expandedToken - 1);
    await expect.poll(() => sidebar.evaluate((element) => element.getBoundingClientRect().width))
      .toBeLessThanOrEqual(expandedToken + 1);
    await toggle.press('Escape');
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');
    await expect(toggle).toBeFocused();

    for (const state of ['loading', 'empty', 'error', 'default']) {
      await candidate.locator(`[data-sidebar-state-action="${state}"]`).click();
      await expect(candidate.locator(`[data-sidebar-state-action="${state}"]`)).toHaveAttribute('aria-pressed', 'true');
      if (state === 'empty') await expect(sidebar.locator('[data-sidebar-empty]').first()).toBeVisible();
      if (state === 'error') await expect(sidebar.locator('[data-sidebar-notification-message]')).toContainText('No pudimos cargar los avisos');
      if (state === 'error') {
        const retry = sidebar.locator('[data-sidebar-notification-retry]');
        await expect(retry).toBeVisible();
        await retry.click();
        await expect(candidate.locator('[data-sidebar-state-action="default"]')).toHaveAttribute('aria-pressed', 'true');
      }
      if (state === 'default') await expect(sidebar.locator('[data-sidebar-notification-message]')).toContainText('Avisos');
    }
    const stateButtonColors = await candidate.locator('[data-sidebar-state-action]').evaluateAll((buttons) => buttons.map((button) => getComputedStyle(button).backgroundColor));
    expect(new Set(stateButtonColors).size).toBeGreaterThan(1);

    const accountTrigger = sidebar.locator('[data-aia-menu-trigger]');
    const accountPanel = sidebar.locator('[data-aia-menu-panel]');
    await accountTrigger.click();
    await expect(accountPanel).toBeVisible();
    await accountTrigger.press('Escape');
    await expect(accountPanel).toBeHidden();
    await expect(accountTrigger).toBeFocused();

    const targets = await sidebar.evaluate((element) => [...element.querySelectorAll('a,button')]
      .filter((node) => node.getClientRects().length > 0)
      .map((node) => ({
        label: node.getAttribute('aria-label') || node.textContent.trim(),
        width: node.getBoundingClientRect().width,
        height: node.getBoundingClientRect().height,
      })));
    expect(targets.every(({ width, height }) => width >= 44 && height >= 44)).toBe(true);
  });
}
