import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('paquetes: crear, asignar, omitir, cobertura y un paso del asistente', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  // DESTRUCTIVO: importa un presupuesto de juguete en el proyecto real y lo deja como versión
  // activa, además de desasignar lo que encuentre. Contra un entorno con el presupuesto de DAPORTO
  // cargado eso tumba el trabajo de empaquetamiento. Corre solo con la variable puesta:
  //   PDC_E2E_DESTRUCTIVO=1 npx playwright test tests/browser/pdc-v2-paquetes.spec.mjs
  test.skip(
    process.env.PDC_E2E_DESTRUCTIVO !== '1',
    'Test destructivo: reemplaza la versión activa del proyecto. Exporta PDC_E2E_DESTRUCTIVO=1 para correrlo.',
  );

  await loginAndSelectProject(page, project);
  try {
    // 1) Import fresco → versión activa.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // 2) Maestro: visitarlo genera los vínculos (insumos únicos consolidados) de la versión activa.
    await page.locator('nav >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    // 3) Paquetes.
    await page.locator('nav >> text=Paquetes').click();
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
    await expect(page.locator('[data-testid="pdc-paq-paquetes"]')).toContainText('E2E Paquete Pisos', { timeout: 15000 });

    // 6) Omitir el siguiente insumo sin asignar.
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
