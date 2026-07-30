import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';

/**
 * Evidencia contra Da Porto (proyecto 73), **solo lectura**.
 *
 * Vive aparte de `pdc-v2-tamiz.spec.mjs` —que corre en el sandbox sacrificable— porque este mira el
 * presupuesto real: los seis números que este trabajo midió a mano contra la base tienen que ser los
 * mismos que aparecen en pantalla. No escribe nada: navega el visor y lee.
 *
 * Se corre a mano para dejar evidencia; no entra en ninguna suite automática, porque depende de que
 * la base local tenga cargado el clase 1 de Da Porto.
 */
const DA_PORTO = {
  key: 'construction',
  name: 'Da Porto',
  projectId: 73,
  dbPrefix: 'da_porto',
  area: 'Construccion',
  maxWeek: 1,
  operationalWeek: 1,
  purchasingWeek: 1,
  purchasingCapabilities: ['pdc'],
  enabledModules: ['pdc'],
};

test('Da Porto: el tamiz dice en pantalla los seis números medidos', async ({ page }) => {
  await loginAndSelectProject(page, DA_PORTO);
  try {
    await page.goto('/plan-compras#/ensamble/presupuesto', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-visor-arbol"]')).toBeVisible({ timeout: 30000 });

    // Las dos cifras del comité, juntas y nombradas.
    const cifras = page.locator('[data-testid="pdc-visor-cifras"]');
    await expect(cifras).toContainText('396 insumos distintos');
    await expect(cifras).toContainText('820 apariciones en APU');

    // 47 actividades sin cantidad, que arrastran 102 líneas: el «~46 insumos vacíos» del comité.
    const sinCantidad = page.locator('[data-testid="pdc-aviso-sin-cantidad"]');
    await expect(sinCantidad).toContainText('47');
    await expect(sinCantidad).toContainText('102');

    // 10 insumos en cero por su propia línea de APU: el residuo real. 102 + 10 = 112.
    await expect(page.locator('[data-testid="pdc-aviso-en-cero"]')).toContainText('10');

    // El umbral por defecto ($73.000.000 = 0,25 % redondeado al millón) marca 18 partidas.
    const globales = page.locator('[data-testid="pdc-aviso-globales"]');
    await globales.locator('summary').click();
    await expect(globales).toContainText('18');
    const umbral = page.locator('[data-testid="pdc-aviso-umbral"]');
    await expect(umbral).toHaveValue('73000000');
    await expect(globales).toContainText('de 57 candidatos con unidad global');
    await expect(globales).toContainText('RED CONTRA INCENDIO TODO COSTO');

    // El umbral es de la vista: moverlo cambia el listado en el sitio.
    await umbral.fill('0');
    await expect(globales).toContainText('57 actividades resueltas con una partida global');
    await umbral.fill('300000000');
    await expect(globales).toContainText('3 actividades resueltas con una partida global');

    // Y sobrevive a recargar: se guarda por proyecto.
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-visor-arbol"]')).toBeVisible({ timeout: 30000 });
    await expect(page.locator('[data-testid="pdc-aviso-umbral"]')).toHaveValue('300000000');

    // Tras recargar los avisos vuelven plegados (es lo que se quiere: no roban alto a la grilla),
    // así que hay que abrirlos antes de tocar el control o de retratarlos.
    for (const id of ['pdc-aviso-sin-cantidad', 'pdc-aviso-en-cero', 'pdc-aviso-globales']) {
      const d = page.locator(`[data-testid="${id}"]`);
      if (!(await d.evaluate((el) => el.open))) await d.locator('summary').click();
    }
    // Volver al valor medido y dejar la captura de evidencia.
    await page.locator('[data-testid="pdc-aviso-umbral"]').fill('73000000');
    await expect(page.locator('[data-testid="pdc-aviso-globales"]')).toContainText('18 actividades resueltas con una partida global');
    await page.locator('[data-testid="pdc-visor-avisos"]').screenshot({
      path: 'goals/pdc-preparar-b1/evidence/tamiz-daporto-avisos.png',
    });

    // Sin errores de consola ni overflow horizontal en el viewport permitido.
    const scrollX = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    expect(scrollX, 'el visor no debe desbordar horizontalmente a 1180px').toBe(false);
    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
