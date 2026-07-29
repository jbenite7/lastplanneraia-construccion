import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

// Importa un presupuesto de juguete y desasigna lo que encuentre: contra un proyecto real eso tumba
// el trabajo de empaquetamiento. Va contra el proyecto sacrificable «PDC Sandbox E2E», que se
// resetea antes de cada test — incluido el paquete «E2E ...» que este spec crea en el catálogo
// global (`general_paquetes_contratacion` no tiene project_id).
usarSandboxPdc();

test('paquetes: crear, asignar, omitir, cobertura y un paso del asistente', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    // 1) Import fresco → versión activa.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // 2) Maestro: visitarlo genera los vínculos (insumos únicos consolidados) de la versión activa.
    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    // 3) Paquetes.
    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Paquetes').click();
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).toBeVisible({ timeout: 15000 });

    const grid = page.locator('[data-testid="pdc-paq-grid"]');
    const filtro = page.locator('[data-testid="pdc-paq-filtro"]');

    // Reset determinista: las asignaciones se heredan por proyecto entre corridas → devolver todo a sin asignar.
    for (const estado of ['asignados', 'omitidos']) {
      await filtro.selectOption(estado);
      await page.waitForTimeout(400);
      if (await grid.locator('.ag-row').count() > 0) {
        await page.locator('[data-testid="pdc-paq-sel-todos"]').click();
        await page.locator('[data-testid="pdc-paq-desasignar"]').click();
        await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });
      }
    }

    // 4) Crear paquete (idempotente: si existe, el backend devuelve el existente; queda seleccionado como destino).
    // El bloque de crear paquete vive plegado desde julio de 2026 (le costaba una barra de alto
    // a la tabla): hay que desplegarlo antes de usarlo.
    await page.locator('.pdc-paq-crear-plegable > summary').click();
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill('E2E Paquete Pisos');
    await page.locator('[data-testid="pdc-paq-crear-tipo"]').selectOption('suministro');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    // 5) Asignar el primer insumo sin asignar.
    await filtro.selectOption('sin_asignar');
    await expect(grid.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await grid.locator('.ag-row').first().click();
    await expect(page.locator('.pdc-paq-sel')).toContainText('1 seleccionado');
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    // Cobertura ya no es 0 y el paquete aparece en la lista con subtotal.
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).not.toContainText('0 asignados', { timeout: 15000 });
    // La lista de paquetes es una pestaña desde la revisión de UX (f28): ya no vive al final.
    await page.getByRole('tab', { name: /Paquetes con insumos/ }).click();
    await expect(page.locator('[data-testid="pdc-paq-paquetes"]')).toContainText('E2E Paquete Pisos', { timeout: 15000 });

    // 6) Omitir el siguiente insumo sin asignar (de vuelta a la pestaña de la grilla).
    await page.getByRole('tab', { name: /^Insumos/ }).click();
    await filtro.selectOption('sin_asignar');
    await page.waitForTimeout(400);
    if (await grid.locator('.ag-row').count() > 0) {
      await grid.locator('.ag-row').first().click();
      await page.locator('[data-testid="pdc-paq-omitir"]').click();
      await expect(page.locator('.pdc-info')).toContainText('omitido', { timeout: 15000 });
    }

    // 7) Un paso del asistente: cambiar de modo y actuar sobre la tarjeta (o ver el fin).
    await page.locator('text=Asistente paso a paso').click();
    await expect(page.locator('[data-testid="pdc-wiz"]')).toBeVisible({ timeout: 15000 });
    const card = page.locator('[data-testid="pdc-wiz-card"]');
    if (await card.count() > 0) {
      // La propuesta del motor llega puesta en el desplegable: es lo que convierte el recorrido en
      // «revisar y confirmar» en vez de buscar el paquete a mano entre más de 200.
      const sugerencia = page.locator('[data-testid="pdc-wiz-sugerencia"]');
      const texto = await sugerencia.innerText();
      const select = page.locator('[data-testid="pdc-wiz-paquete"]');
      if (!texto.includes('Sin propuesta')) {
        const valor = await select.inputValue();
        expect(valor, 'con propuesta, el destino llega preseleccionado').not.toBe('');
        const elegido = await select.locator('option:checked').innerText();
        expect(texto).toContain(elegido.split(' — ')[0]);
      } else {
        expect(await select.inputValue(), 'sin propuesta el destino queda vacío').toBe('');
      }
      // El botón duplicado se retiró: queda un solo camino.
      await expect(page.locator('[data-testid="pdc-wiz-aceptar-sugerido"]')).toHaveCount(0);
      await expect(page.locator('[data-testid="pdc-wiz-filtro-sin"]')).toBeVisible();

      await page.locator('[data-testid="pdc-wiz-saltar"]').click();
      await expect(page.locator('[data-testid="pdc-wiz"]')).toBeVisible();
    }

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
