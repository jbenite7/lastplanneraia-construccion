import { test, expect } from '@playwright/test';

// El dashboard de escalamientos no tiene malla Handsontable: pasa al drawer un adaptador que solo
// sabe escribir filas. Antes fingia ser una malla y reventaba en tres sitios distintos, dos de
// ellos invisibles hasta que el usuario interactuaba.

async function irAEscalamientos(page) {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8091/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8091/dashboard/escalamientos');
  await page.waitForLoadState('networkidle');
}

test('escalamientos carga sin errores de JS', async ({ page }) => {
  const errores = [];
  page.on('pageerror', (e) => errores.push(e.message));
  await irAEscalamientos(page);
  expect(errores).toEqual([]);
});

test('abrir y cerrar el drawer en escalamientos no lanza errores de JS', async ({ page }) => {
  const errores = [];
  page.on('pageerror', (e) => errores.push(e.message));
  await irAEscalamientos(page);

  const trigger = page.locator('#lps_sidebar_trigger');
  await expect(trigger).toBeVisible();

  await trigger.click();
  await expect(page.locator('#lps_drawer')).toHaveClass(/open/);
  await page.waitForTimeout(600); // el repintado diferido ocurre a los 300 ms

  // Se cierra por su propio boton: con el drawer abierto, el panel tapa el trigger del sidebar.
  await page.locator('#lps_drawer_close').click();
  await expect(page.locator('#lps_drawer')).not.toHaveClass(/open/);
  await page.waitForTimeout(600);

  expect(errores).toEqual([]);
});

test('el digest semanal no se ofrece en una superficie sin malla', async ({ page }) => {
  await irAEscalamientos(page);
  // Depende de getSourceData(), que solo una malla puede dar: ofrecerlo seria prometer de mas.
  await expect(page.locator('#lps_btn_digest')).toBeHidden();
});
