import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];

// D12/D16: el tema ya no es uno solo, y el laboratorio arranca en el default del
// producto (claro). Afirmar 'dark' a secas fallaba en las dos patas de la matriz;
// mismo patron ya corregido en design-system-lab.mjs.
const E2E_THEME = process.env.E2E_THEME === 'dark' ? 'dark' : 'light';

async function forceTheme(page, theme) {
  await page.evaluate((t) => {
    try { localStorage.setItem('aia-theme', t); } catch (_) { /* sin persistencia */ }
    document.documentElement.setAttribute('data-aia-theme', t);
    document.documentElement.classList.toggle('aia-theme-dark', t === 'dark');
  }, theme);
}

async function openLaboratory(page, viewport) {
  await page.setViewportSize(viewport);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });
  await forceTheme(page, E2E_THEME);
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', E2E_THEME);
}

async function tabSequence(page, scope, required) {
  await page.evaluate(() => document.activeElement?.blur());
  const visited = [];
  for (let step = 0; step < 100 && visited.length < required; step += 1) {
    await page.keyboard.press('Tab');
    const current = await page.evaluate((selector) => {
      const active = document.activeElement;
      if (!document.querySelector(selector)?.contains(active)) return null;
      const style = getComputedStyle(active);
      const label = active.getAttribute('aria-label')
        || active.labels?.[0]?.firstChild?.textContent.trim()
        || active.textContent.trim();
      return {
        label,
        focusVisible: style.outlineStyle !== 'none' || style.boxShadow !== 'none',
      };
    }, scope);
    if (current && current.label !== visited.at(-1)?.label) visited.push(current);
  }
  return visited;
}

for (const viewport of VIEWPORTS) {
  test(`keyboard order and dialog focus remain operable at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await openLaboratory(page, viewport);

    await page.locator('[data-lab-family-link][data-family-target="actions"]').click();
    const actions = await tabSequence(
      page,
      '[data-family="actions"] [data-aia-component="action-group"]',
      2,
    );
    expect(actions.map(({ label }) => label)).toEqual(['Guardar cambios', 'Cancelar']);
    expect(actions.every(({ focusVisible }) => focusVisible)).toBe(true);

    await page.locator('[data-lab-family-link][data-family-target="forms-filters"]').click();
    const filters = await tabSequence(
      page,
      '[data-family="forms-filters"] [data-aia-component="filter-form"]',
      5,
    );
    expect(filters.map(({ label }) => label)).toEqual([
      'Buscar', 'Responsable', 'Estado', 'Aplicar filtros', 'Limpiar',
    ]);
    expect(filters.every(({ focusVisible }) => focusVisible)).toBe(true);

    await page.locator('[data-lab-family-link][data-family-target="shell-navigation"]').click();
    const sidebar = page.locator('[data-family="shell-navigation"] [data-shell-pattern="sidebar"]');
    const sidebarToggle = sidebar.locator('[data-sidebar-toggle]');
    // El fixture del laboratorio arranca colapsado desde 4bc75ef9 (2026-07-23); este assert nacio
    // tres dias antes, cuando arrancaba expandido, y nunca habia corrido hasta aqui porque el test
    // moria arriba por el tema (b97a1b54). Enter alterna, asi que sobre un sidebar colapsado lo
    // expandia y el assert de abajo fallaba sin que el componente hiciera nada mal — medido en
    // Playwright con la secuencia keydown→keypress→click→keyup y un solo cambio de estado. Se parte
    // de expandido a proposito para probar las dos transiciones que Escape necesita: colapsar y
    // restaurar. No se afloja el assert; se fija el punto de partida.
    await sidebarToggle.focus();
    if (await sidebar.getAttribute('data-sidebar-state') === 'collapsed') {
      await sidebarToggle.press('Enter');
      await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');
      await expect(sidebarToggle).toHaveAttribute('aria-expanded', 'true');
    }
    await sidebarToggle.press('Enter');
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'collapsed');
    await expect(sidebarToggle).toHaveAttribute('aria-expanded', 'false');
    await sidebarToggle.press('Escape');
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');
    await expect(sidebarToggle).toBeFocused();

    await page.locator('[data-lab-family-link][data-family-target="overlays"]').click();
    const open = page.locator('[data-family="overlays"] [data-aia-dialog-open]');
    const dialog = page.locator('[data-family="overlays"] [data-aia-dialog]');
    await open.focus();
    await open.press('Enter');
    await expect(dialog).toHaveAttribute('open', '');
    expect(await dialog.evaluate((element) => element.contains(document.activeElement))).toBe(true);
    await page.keyboard.press('Escape');
    await expect(dialog).not.toHaveAttribute('open', '');
    await expect(open).toBeFocused();
  });
}
