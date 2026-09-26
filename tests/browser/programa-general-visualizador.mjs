import { test, expect } from '@playwright/test';
import { loginAndSelectProject } from './support/session.mjs';

const DA_PORTO = { name: 'Da Porto' };

const READ_ACTIONS = ['Leyenda', 'Recargar', 'CSV', 'Corte XLSX'];

test.describe('Programa General — toolbar por rol', () => {
  test('el Visualizador no ve acciones de escritura', async ({ page }) => {
    await loginAndSelectProject(page, DA_PORTO, { username: 'test.V' });
    await page.goto('/programa-general');
    await expect(page.getByRole('button', { name: 'Actualizar Ejecución' })).toHaveCount(0);
    for (const action of READ_ACTIONS) {
      await expect(page.getByRole('button', { name: action })).toBeVisible();
    }
    await expect(page.getByRole('link', { name: 'BI Programa' })).toHaveCount(0);
  });

  test('el Residente sí ve la acción de escritura', async ({ page }) => {
    await loginAndSelectProject(page, DA_PORTO, { username: 'test.R' });
    await page.goto('/programa-general');
    await expect(page.getByRole('button', { name: 'Actualizar Ejecución' })).toBeVisible();
    for (const action of READ_ACTIONS) {
      await expect(page.getByRole('button', { name: action })).toBeVisible();
    }
    await expect(page.getByRole('link', { name: 'BI Programa' })).toBeVisible();
  });
});
