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

    // Da Porto está al 100 % por valor, así que desde la tanda 3 el aparato de asignar arranca
    // plegado tras «Asignar insumos» (el trabajo que importa ya está hecho). Se despliega para
    // llegar al formulario de creación; lo que este spec comprueba no cambia.
    // Idempotente a propósito: el bloque arranca abierto o cerrado según la cobertura del proyecto,
    // y un click a ciegas lo cerraba justo cuando ya estaba abierto.
    const abrir = async (selector) => {
      const det = page.locator(selector);
      if (await det.evaluate((el) => !el.open)) await det.locator('> summary').click();
    };
    await abrir('.pdc-paq-herramientas');
    // El bloque de crear paquete vive plegado desde julio de 2026: le costaba una barra de alto a
    // la tabla, y crear paquetes es una acción ocasional frente a asignar.
    await abrir('.pdc-paq-crear-plegable');

    // El formulario de creación ofrece las 4 modalidades y arranca en «contrato». Desde la migración
    // a `Selector` (tanda 2026-08-06) ya no es un `<select>` nativo: se lee el botón y se abre el
    // popup para contar las opciones, con `elegirEnSelector` como sustituto de `selectOption`.
    const botonModalidad = page.locator('[data-testid="pdc-paq-crear-modalidad"]');
    await expect(botonModalidad).toBeVisible();
    await expect(botonModalidad).toContainText('Contrato');
    await botonModalidad.click();
    const popupModalidad = page.locator('.pdc-selector-popup');
    await popupModalidad.waitFor({ state: 'visible' });
    await expect(popupModalidad.getByRole('option')).toHaveCount(4);
    await popupModalidad.getByRole('option', { name: 'Contrato', exact: true }).click();
    await popupModalidad.waitFor({ state: 'detached', timeout: 5000 });

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
