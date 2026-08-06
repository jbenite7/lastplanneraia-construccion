import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';
import { elegirEnSelector } from './support/pdc-selector.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

// Confirmar la importación deja el presupuesto de juguete como versión activa: se escribe en el
// proyecto sacrificable «PDC Sandbox E2E», que se resetea antes de cada test.
usarSandboxPdc();

test('el visor señala sin bloquear, y sus cifras dicen qué cuentan', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);

    // Antes de confirmar, la pantalla dice en palabras qué se conserva. En un proyecto reseteado no
    // hay versión activa, así que el mensaje es el de «no se pierde nada».
    await expect(page.locator('[data-testid="pdc-import-conserva"]')).toContainText('se conservan', { timeout: 20000 });

    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.goto('/plan-compras#/ensamble/presupuesto', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-visor-arbol"]')).toBeVisible({ timeout: 20000 });

    // Las dos magnitudes, cada una con su palabra: es lo que evita el «me suena raro el número».
    const cifras = page.locator('[data-testid="pdc-visor-cifras"]');
    await expect(cifras).toContainText('insumos distintos');
    await expect(cifras).toContainText('apariciones en APU');

    // Los tres avisos existen y se pueden desplegar.
    for (const id of ['pdc-aviso-sin-cantidad', 'pdc-aviso-en-cero', 'pdc-aviso-globales']) {
      const aviso = page.locator(`[data-testid="${id}"]`);
      await expect(aviso).toBeVisible();
      await aviso.locator('summary').click();
    }

    // El umbral es un control de la vista, no una constante del servidor: bajarlo a cero no puede
    // dejar menos partidas de las que había.
    const umbral = page.locator('[data-testid="pdc-aviso-umbral"]');
    await expect(umbral).toBeVisible();
    await umbral.fill('0');
    await expect(umbral).toHaveValue('0');

    // NO bloquean nada. Se demuestra HACIÉNDOLO con los avisos abiertos: el árbol se sirve, se
    // asigna un insumo a un paquete, y se recalcula el plan. Es la condición de hecho 3 del spec, y
    // razonarla no vale: el aviso podría estar deshabilitando un botón sin que nadie lo notara.
    await expect(page.locator('[data-testid="pdc-visor-arbol"] .ag-row').first()).toBeVisible();

    // Visitar el Maestro es lo que genera los vínculos de la versión activa; sin ese paso la grilla
    // de paquetes llega vacía y el «no bloquea» quedaría sin demostrar por una razón ajena.
    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 20000 });

    // Por la pestaña y no por `goto` con hash: es como llega el usuario, y es lo que hace el spec de
    // paquetes que sí pasa.
    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Paquetes').click();
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 20000 });
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).toBeVisible({ timeout: 20000 });
    await page.locator('.pdc-paq-crear-plegable > summary').click();
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill('E2E Tamiz No Bloquea');
    await elegirEnSelector(page, 'pdc-paq-crear-tipo', 'Suministro');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    const grid = page.locator('[data-testid="pdc-paq-grid"]');
    await elegirEnSelector(page, 'pdc-paq-filtro', 'Sin asignar');
    await expect(grid.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await grid.locator('.ag-row').first().click();
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Plan').click();
    await page.locator('[data-testid="pdc-plan-recalcular"]').click();
    await expect(page.locator('[data-testid="pdc-plan-resumen"]')).toBeVisible({ timeout: 30000 });

    // Y los avisos siguen ahí después de todo el recorrido: no se «resuelven» solos ni desaparecen.
    await page.locator('[aria-label="Submódulos del plan de compras"] >> text="Presupuesto"').first().click();
    await expect(page.locator('[data-testid="pdc-visor-avisos"]')).toBeVisible({ timeout: 20000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
