import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Solo lectura: no importa presupuestos ni cambia asignaciones — verifica que la modalidad de
// contratación (A3.1) viaja del catálogo a la UI y solo se pinta cuando no es «contrato».
test('paquetes: la modalidad de contratación se ve en el resumen y en el selector de creación', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/paquetes', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).toBeVisible({ timeout: 20000 });

    // El formulario de creación ofrece las 4 modalidades y arranca en «contrato».
    const selModalidad = page.locator('[data-testid="pdc-paq-crear-modalidad"]');
    await expect(selModalidad).toBeVisible();
    await expect(selModalidad).toHaveValue('contrato');
    await expect(selModalidad.locator('option')).toHaveCount(4);

    // La lista de paquetes marca las modalidades sin proceso de contratación completo.
    await page.getByRole('tab', { name: /Paquetes con insumos/ }).click();
    const lista = page.locator('[data-testid="pdc-paq-paquetes"]');
    await expect(lista).toContainText('Orden de compra', { timeout: 20000 });
    for (const clase of ['orden_compra', 'consumo_directo', 'no_contratable']) {
      await expect(lista.locator(`.pdc-paq-modalidad--${clase}`).first(), clase).toBeVisible();
    }
    // «Contrato» es el default y no se pinta (evita ruido: la mayoría de paquetes son contratos).
    await expect(lista.locator('.pdc-paq-modalidad', { hasText: /^Contrato$/ })).toHaveCount(0);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');

    if (process.env.PDC_SHOT) {
      await page.locator('.pdc-paq-crear').screenshot({ path: `${process.env.PDC_SHOT}-crear.png` });
      await lista.screenshot({ path: `${process.env.PDC_SHOT}-lista.png` });
    }
  } finally {
    await logout(page).catch(() => {});
  }
});
