import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx'; // el que usa pdc-v2-import.spec.mjs

// El primer cargue deja el presupuesto de juguete como versión activa: va contra el proyecto
// sacrificable «PDC Sandbox E2E», que se resetea antes de cada test.
usarSandboxPdc();

test('versionamiento inteligente: re-cargue idéntico avisa sin cambios', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });

    const fileInput = page.locator('[data-testid="pdc-import-file"]');
    const resumen = page.locator('[data-testid="pdc-import-resumen"]');
    const sinCambios = page.locator('[data-testid="pdc-import-sincambios"]');
    const confirmar = page.locator('[data-testid="pdc-import-confirmar"]');
    const confirmado = page.locator('[data-testid="pdc-import-confirmado"]');

    // Primer cargue del fixture. El sandbox arranca sin ninguna versión (lo resetea el seed antes
    // de cada test), así que este cargue SIEMPRE crea la versión 1: no hay con qué comparar todavía
    // y el aviso «sin cambios» no debe aparecer. Antes este bloque tenía que branchear porque el
    // spec escribía en Da Porto, compartido con el resto de specs y con estado impredecible.
    await fileInput.setInputFiles(FIXTURE);
    await expect(resumen).toBeVisible({ timeout: 20000 });
    await expect(sinCambios).toHaveCount(0);
    await confirmar.click();
    await expect(confirmado).toBeVisible({ timeout: 20000 });
    await expect(confirmado).not.toContainText('Sin cambios');

    // Segundo cargue del MISMO archivo: la versión activa ya es el contenido del fixture, así que
    // el aviso «sin cambios» debe dispararse y no debe crearse una versión nueva.
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
