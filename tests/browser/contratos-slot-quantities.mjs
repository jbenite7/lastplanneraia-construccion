import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];
const preconstructionProject = {
  name: 'Aeropuerto Regional PC',
  maxWeek: 3,
};

test.describe('Contratos - cantidades por paquete', () => {
  test('muestra steppers de cantidad junto a paquetes', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });

    const table = page.locator('#dt_cliente');
    await expect(table).toBeVisible({ timeout: 15000 });

    const editButton = page.locator('#dt_cliente tbody button.editar').first();
    await expect(editButton).toBeVisible({ timeout: 15000 });
    await editButton.click();

    const modal = page.locator('#modalEditarContratos');
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(modal.locator('.ct-contract-quantity')).toHaveCount(20);
    await expect(modal.locator('.ct-contract-section:visible .ct-contract-header__cell', { hasText: 'Contratos' }).first()).toBeVisible();

    const visibleQuantity = modal.locator('.ct-contract-section:visible .ct-contract-quantity').first();
    await expect(visibleQuantity).toBeVisible();
    await expect(visibleQuantity).toHaveAttribute('min', '1');
    await expect(visibleQuantity).toHaveAttribute('step', '1');

    await page.screenshot({
      path: 'docs/qa/evidence/contratos-cantidades-20260703.png',
      fullPage: true,
    });
  });

  test('bloquea URL directa en preconstruccion', async ({ page }) => {
    await loginAndSelectProject(page, preconstructionProject);
    await changeWeek(page, preconstructionProject.maxWeek, '/programa-general');

    const response = await page.goto(`/contratos?semana=${preconstructionProject.maxWeek}`, {
      waitUntil: 'domcontentloaded',
    });

    expect(response?.status()).toBe(404);
    await expect(page.locator('body')).toContainText('Contratos no esta disponible');
  });
});
