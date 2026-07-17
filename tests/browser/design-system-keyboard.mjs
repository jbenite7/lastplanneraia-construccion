import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const MOBILE = { width: 390, height: 844 };

async function openAsAdmin(page, path) {
  await page.setViewportSize(MOBILE);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await page.goto(path, { waitUntil: 'domcontentloaded' });
}

async function tabSequence(page, scope, required) {
  await page.evaluate(() => document.activeElement?.blur());
  const visited = [];
  for (let step = 0; step < 80 && visited.length < required; step += 1) {
    await page.keyboard.press('Tab');
    const current = await page.evaluate((selector) => {
      const active = document.activeElement;
      if (!document.querySelector(selector)?.contains(active)) return null;
      const style = getComputedStyle(active);
      const label = active.getAttribute('aria-label')
        || active.labels?.[0]?.firstChild?.textContent.trim() || active.textContent.trim();
      return { label, focusVisible: style.outlineStyle !== 'none' || style.boxShadow !== 'none' };
    }, scope);
    if (current && current.label !== visited.at(-1)?.label) visited.push(current);
  }
  return visited;
}

test('canonical actions and filters have logical keyboard order and visible focus', async ({ page }) => {
  await openAsAdmin(page, '/internal/design-system');
  await page.locator('[data-lab-family-link][data-family-target="actions"]').click();
  const actions = await tabSequence(page, '[data-family="actions"] [data-aia-component="action-group"]', 2);
  expect(actions.map(({ label }) => label)).toEqual(['Guardar cambios', 'Cancelar']);
  expect(actions.every(({ focusVisible }) => focusVisible)).toBe(true);

  await page.locator('[data-lab-family-link][data-family-target="forms-filters"]').click();
  const filters = await tabSequence(page, '[data-family="forms-filters"] [data-aia-component="filter-form"]', 5);
  expect(filters.map(({ label }) => label)).toEqual([
    'Buscar', 'Responsable', 'Estado', 'Aplicar filtros', 'Limpiar',
  ]);
  expect(filters.every(({ focusVisible }) => focusVisible)).toBe(true);
});

test('Programa General actions are keyboard reachable and its dialog returns focus', async ({ page }) => {
  await openAsAdmin(page, '/programa-general');
  await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
  const actions = await tabSequence(page, 'main#contenido', 5);
  expect(actions.map(({ label }) => label)).toEqual([
    'Leyenda', 'Actualizar Ejecución', 'Descargar Corte', 'Exportar CSV', 'Recargar',
  ]);
  expect(actions.every(({ focusVisible }) => focusVisible)).toBe(true);
  const legend = page.getByRole('button', { name: 'Leyenda' });
  await legend.focus();
  await expect(legend).toBeFocused();
  await expect(page.locator('#modal_leyenda_colores')).toHaveCount(1);
  await page.keyboard.press('Enter');
  const dialog = page.locator('#modal_leyenda_colores');
  await expect(dialog).toBeVisible();
  await expect(dialog).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(dialog).toBeHidden();
  await expect(legend).toBeFocused();
});
