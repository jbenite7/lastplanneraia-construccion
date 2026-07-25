import { expect, test } from '@playwright/test';
import { login } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const FIXTURES_BY_FAMILY = {
  'page-structure': 1,
  'forms-filters': 2,
  'shell-navigation': 3,
  'vendor-adapters': 3,
  overlays: 1,
  actions: 1,
  'bi-primitives': 1,
};

test('P1 and P2 operational fixtures stay contained in the dark desktop laboratory', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await login(page, ADMIN);

  for (const [family, count] of Object.entries(FIXTURES_BY_FAMILY)) {
    await page.goto(`/internal/design-system?family=${family}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
    await expect(page.locator(`[data-family="${family}"] [data-operational-fixture]`)).toHaveCount(count);
    const overflow = await page.evaluate(() => (
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    ));
    expect(overflow).toBeLessThanOrEqual(1);
  }
});

test('vendor fixtures announce observable P1 and P2 state changes', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await login(page, ADMIN);
  await page.goto('/internal/design-system?family=vendor-adapters', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

  const grid = page.locator('[data-operational-fixture="editable-grid"]');
  await grid.getByRole('button', { name: 'Guardar cambios' }).click();
  await expect(grid).toHaveAttribute('data-contract-state', 'saving');
  await expect(grid.getByRole('status')).toHaveText('Estado actual: Guardando');

  const dataTable = page.locator('[data-operational-fixture="datatables-legacy"]');
  await dataTable.getByRole('button', { name: 'Ordenar por actividad' }).click();
  await expect(dataTable).toHaveAttribute('data-contract-state', 'sorting');
  await expect(dataTable.locator('[data-sortable-body] tr').first()).toContainText('Redes hidrosanitarias');

  const tomSelect = page.locator('[data-operational-fixture="tom-select-advanced"]');
  await tomSelect.getByRole('option', { name: 'Carlos Ruiz · Director' }).click();
  await expect(tomSelect.locator('.item[data-value="carlos"]')).toBeVisible();
  await expect(tomSelect).toHaveAttribute('data-contract-state', 'success');
  await tomSelect.getByRole('button', { name: 'Limpiar' }).click();
  await expect(tomSelect.locator('.item[data-value="carlos"]')).toBeHidden();
  await expect(tomSelect.getByRole('status')).toHaveText('Estado actual: Sin resultados');
});

test('vendor adapter previews expose their full operational affordances', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await login(page, ADMIN);
  await page.goto('/internal/design-system?family=vendor-adapters', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

  const handsontable = page.locator('[data-vendor-fixture="handsontable"]');
  await handsontable.getByRole('button', { name: 'Añadir actividad' }).click();
  await expect(handsontable.locator('[data-handsontable-new-row]')).toBeVisible();
  await expect(handsontable.getByRole('status')).toContainText('Nueva fila añadida');
  await handsontable.getByRole('spinbutton', { name: 'Avance de Cimentación' }).fill('65');
  await expect(handsontable.getByRole('status')).toContainText('Cambios guardados automáticamente');

  const select2 = page.locator('[data-vendor-fixture="select2"]');
  await select2.locator('[data-select2-preview-toggle]').click();
  await select2.locator('[data-select2-search-value][data-select2-value="Carlos Ruiz · Director"]').click();
  await expect(select2.locator('[data-select2-preview-value]')).toHaveText('Carlos Ruiz · Director');
  await select2.getByRole('button', { name: 'Limpiar selección' }).click();
  await expect(select2.getByRole('status')).toHaveText('Sin responsable seleccionado.');

  const sweetAlert = page.locator('[data-vendor-fixture="sweetalert2"]');
  await sweetAlert.getByRole('button', { name: 'Aplicar cambios' }).click();
  await expect(sweetAlert.getByRole('alertdialog')).toHaveAttribute('data-confirmed', 'true');
  await expect(sweetAlert.getByRole('status')).toContainText('Cambios aplicados correctamente');

  const dataTable = page.locator('[data-operational-fixture="datatables-legacy"]');
  await dataTable.getByRole('button', { name: 'Página 2' }).click();
  await expect(dataTable).toHaveAttribute('data-contract-state', 'loading');
});
