// tests/browser/pdc-v2-pasos.spec.mjs — A4.1: la obra arma su propio proceso de contratación.
//
// Contra el sandbox sacrificable, no contra el proyecto real: la configuración de pasos mueve las
// fechas de TODOS los paquetes de la obra, y Da Porto es la línea base con la que se demuestra la
// cero regresión de esta fase. `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

usarSandboxPdc();

test('una obra agrega un paso propio y vuelve al proceso por defecto', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/plan-compras#/ensamble/plan');

  await page.getByTestId('pdc-plan-configurar-pasos').click();

  const lista = page.getByTestId('pdc-pasos-lista');
  await expect(lista.locator('li')).toHaveCount(7);
  await expect(page.getByTestId('pdc-pasos-por-defecto')).toBeVisible();
  // Sin configuración propia no hay nada que restablecer: el botón no debe existir todavía.
  await expect(page.getByTestId('pdc-pasos-restablecer')).toHaveCount(0);

  // Licify y Aprobación del cliente son justamente las dos variantes que el roadmap pedía no
  // hardcodear: si el catálogo no las ofreciera, este selectOption fallaría.
  await page.getByTestId('pdc-pasos-agregar').selectOption('aprobacion_cliente');
  await expect(lista.locator('li')).toHaveCount(8);

  await page.getByTestId('pdc-pasos-guardar').click();
  await expect(page.getByText(/Se recalcularon \d+ paquetes/)).toBeVisible();
  await expect(page.getByTestId('pdc-pasos-por-defecto')).toHaveCount(0);

  // Quitar avisa ANTES de guardar, y con un número: es la garantía que pidió el grilleo.
  await page.getByRole('button', { name: 'Quitar Aprobación del cliente' }).click();
  await expect(page.getByTestId('pdc-pasos-aviso-quitar')).toContainText('Vas a quitar un paso');

  // Y la vuelta atrás, que es lo que hace seguro probar esto en cualquier obra.
  await page.getByTestId('pdc-pasos-restablecer').click();
  await expect(page.getByText('proceso por defecto')).toBeVisible();
  await expect(lista.locator('li')).toHaveCount(7);

  await logout(page);
});
