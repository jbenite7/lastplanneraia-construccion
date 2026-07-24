import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx'; // el que usa pdc-v2-import.spec.mjs

test('versionamiento inteligente: re-cargue idéntico avisa sin cambios', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });

    const fileInput = page.locator('[data-testid="pdc-import-file"]');
    const resumen = page.locator('[data-testid="pdc-import-resumen"]');
    const sinCambios = page.locator('[data-testid="pdc-import-sincambios"]');
    const confirmar = page.locator('[data-testid="pdc-import-confirmar"]');
    const confirmado = page.locator('[data-testid="pdc-import-confirmado"]');

    // Primer cargue del fixture. El proyecto Da Porto es compartido entre specs e2e y
    // puede acumular versiones de corridas previas, así que NO se asume su estado: si el
    // fixture ya coincide con la versión activa, el preview avisa "sin cambios" desde este
    // primer intento (estado válido: el auto-comparativo/versionado ya lo tenía al día).
    // Si no coincide, se crea una versión nueva. Ambos casos dejan la MISMA postcondición
    // (versión activa == contenido del fixture), que es lo que habilita la aserción clave
    // del segundo cargue más abajo — por eso basta con branchear aquí sin duplicar specs.
    await fileInput.setInputFiles(FIXTURE);
    await expect(resumen).toBeVisible({ timeout: 20000 });
    const yaEstabaActiva = await sinCambios.isVisible();
    await confirmar.click();
    await expect(confirmado).toBeVisible({ timeout: 20000 });
    if (yaEstabaActiva) {
      await expect(confirmado).toContainText('Sin cambios');
    }

    // Segundo cargue del MISMO archivo: tras el primer confirmar (sin cambios o recién
    // creada), la versión activa del proyecto YA es el contenido del fixture en cualquiera
    // de los dos casos anteriores. Esta es la aserción clave, robusta al estado previo del
    // proyecto compartido: el aviso "sin cambios" debe disparar aquí siempre.
    await fileInput.setInputFiles(FIXTURE);
    await expect(sinCambios).toBeVisible({ timeout: 20000 });
    await confirmar.click();
    await expect(confirmado).toContainText('Sin cambios', { timeout: 20000 });

    // El historial de versiones usa la nueva etiqueta "Versión N · fecha" (A1.7).
    await expect(page.locator('[data-testid="pdc-import-versiones"]')).toContainText('Versión', { timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
