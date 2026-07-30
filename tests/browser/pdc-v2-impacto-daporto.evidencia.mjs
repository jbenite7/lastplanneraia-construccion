import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';

/**
 * Evidencia del informe de impacto contra Da Porto (proyecto 73). **Previsualiza y cancela: no
 * confirma nada**, así que el presupuesto real y sus 12 asignaciones quedan intactos.
 *
 * El fixture `daporto-clase0-simulado.xlsx` se construyó a mano a partir del clase 1 que está en la
 * base (ver `scripts` de la bitácora), con tres cambios deliberados:
 *   · se quita ALAMBRE NEGRO (DE AMARRAR), que TIENE paquete   → «desaparece con paquete» = 1
 *   · se añade FIBRA ESTRUCTURAL CLASE 0, que no existía       → «nuevo sin paquete» = 1
 *   · CONCRETO DE 3000PSI pasa de tipo M a E, y tiene paquete  → «cambia de tipo» = 1
 *
 * Es una prueba del MECANISMO, no del caso real: el clase 0 de Da Porto todavía no existe. Eso se
 * comprobará cuando llegue el presupuesto de verdad.
 */
const DA_PORTO = {
  key: 'construction', name: 'Da Porto', projectId: 73, dbPrefix: 'da_porto', area: 'Construccion',
  maxWeek: 1, operationalWeek: 1, purchasingWeek: 1,
  purchasingCapabilities: ['pdc'], enabledModules: ['pdc'],
};
const FIXTURE = 'tests/browser/fixtures/pdc/daporto-clase0-simulado.xlsx';

test('Da Porto: recargar informa el impacto antes de confirmar, y cancelar no escribe', async ({ page }) => {
  await loginAndSelectProject(page, DA_PORTO);
  try {
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 20000 });

    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    const impacto = page.locator('[data-testid="pdc-import-impacto"]');
    await expect(impacto).toBeVisible({ timeout: 60000 });

    // Las tres cifras dan 1 · 1 · 1 y el detalle nombra exactamente esos tres.
    const nuevos = page.locator('[data-testid="pdc-impacto-nuevos"]');
    await expect(nuevos).toContainText('1 insumo nuevo sin paquete');
    await nuevos.locator('summary').click();
    await expect(nuevos).toContainText('FIBRA ESTRUCTURAL CLASE 0');

    const desaparecen = page.locator('[data-testid="pdc-impacto-desaparecen"]');
    await expect(desaparecen).toContainText('1 insumo con paquete que desaparece');
    await desaparecen.locator('summary').click();
    await expect(desaparecen).toContainText('ALAMBRE NEGRO (DE AMARRAR)');
    // El detalle dice a qué paquete estaba asignado: sin eso, el aviso no es accionable.
    await expect(desaparecen).toContainText('Suministro ACERO DE REFUERZO');

    const cambian = page.locator('[data-testid="pdc-impacto-cambian"]');
    await expect(cambian).toContainText('1 insumo que cambia de tipo');
    await cambian.locator('summary').click();
    await expect(cambian).toContainText('CONCRETO DE 3000PSI (21 MPA) PREMEZCLADO');
    await expect(cambian).toContainText('M → E');
    await expect(cambian).toContainText('Suministro CONCRETO');

    // El valor afectado existe y el texto de antes del botón no promete reagrupar nada solo.
    await expect(page.locator('[data-testid="pdc-impacto-valor"]')).toContainText('Valor afectado');
    const conserva = page.locator('[data-testid="pdc-import-conserva"]');
    await expect(conserva).toContainText('se conservan');
    await expect(conserva).toContainText('Queda por revisar a mano');
    expect(await conserva.innerText()).not.toMatch(/reasign|reagrup|automátic/i);

    await page.screenshot({ path: 'goals/pdc-preparar-b1/evidence/impacto-daporto-clase0.png' });

    // Cancelar es no confirmar: se sale de la pantalla sin tocar el botón.
    await page.goto('/plan-compras#/ensamble/presupuesto', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-visor-cifras"]')).toContainText('820 apariciones en APU', { timeout: 30000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
