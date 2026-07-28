import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Solo lectura: no amarra paquetes ni recalcula — a diferencia de pdc-v2-paquetes.spec.mjs (que SÍ
// importa un presupuesto de juguete y es destructivo), esta prueba solo navega y lee la pestaña
// «Plan» (A4). Verifica que la tabla trae filas, que los vencidos se ven primero y en rojo, que un
// paquete se puede expandir a sus pasos, y que existen los bloques «Sin frente» y «Desfases».
test('plan: la pestaña Plan carga el plan calculado con vencidos primero', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });

    // El resumen (paquete(s) / vencido(s) / con duración estimada) siempre está, haya o no plan.
    await expect(page.locator('[data-testid="pdc-plan-resumen"]')).toBeVisible({ timeout: 20000 });

    const grid = page.locator('[data-testid="pdc-plan-grid"]');
    await expect(grid).toBeVisible();

    // Si hay paquetes con plan calculado (depende de que alguien haya amarrado y recalculado antes),
    // la tabla trae filas y los vencidos van primero y en rojo (clase pdc-plan-fila-vencida).
    const filas = grid.locator('.ag-row');
    const total = await filas.count();
    if (total > 0) {
      await expect(filas.first()).toBeVisible({ timeout: 15000 });

      // Si hay al menos un vencido, la primera fila (ya ordenada por el backend) debe llevar la
      // clase que la pinta en rojo.
      const vencidosTexto = await page.locator('[data-testid="pdc-plan-resumen"]').innerText();
      if (!vencidosTexto.includes('0 vencido')) {
        await expect(filas.first()).toHaveClass(/pdc-plan-fila-vencida/);
      }

      // Un click en una fila expande sus siete pasos del proceso de contratación.
      await filas.first().click();
      await expect(page.locator('[data-testid="pdc-plan-detalle"]')).toBeVisible({ timeout: 15000 });
      await expect(page.locator('[data-testid="pdc-plan-detalle"] table tbody tr')).toHaveCount(7);
    } else {
      await expect(page.locator('.pdc-vacio').first()).toBeVisible();
    }

    // «Sin frente» y «Desfases» son secciones fijas de la vista, con o sin datos.
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible();
    await expect(page.locator('[data-testid="pdc-plan-desfases"]')).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');

    if (process.env.PDC_SHOT) {
      await page.screenshot({ path: `${process.env.PDC_SHOT}-plan.png`, fullPage: true });
    }
  } finally {
    await logout(page).catch(() => {});
  }
});
