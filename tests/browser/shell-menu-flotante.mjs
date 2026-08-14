/**
 * Menú flotante del shell bajo 1180px (spec 2026-08-14, decisiones D1-D4,
 * plan docs/superpowers/plans/2026-08-14-shell-menu-flotante-responsive.md).
 */
import { test, expect } from '@playwright/test';
import { login } from './support/session.mjs';

const CANDIDATOS = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

async function abrir(page, ancho) {
  await page.setViewportSize({ width: ancho, height: 844 });
  await login(page);
  for (const name of CANDIDATOS) {
    const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (await card.count()) {
      await card.locator('button[type="submit"], .btn-enter').click();
      break;
    }
  }
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
  await page.goto('/programa-general');
  await page.waitForTimeout(1200);
}

test('en 390 la navegacion no ocupa columna y el disparador es visible', async ({ page }) => {
  await abrir(page, 390);
  const padding = await page.evaluate(() => getComputedStyle(document.body).paddingLeft);
  expect(padding).toBe('0px');
  await expect(page.locator('#shellMenuTrigger')).toBeVisible();
});

test('el menu se abre, se cierra con Escape y devuelve el foco al boton', async ({ page }) => {
  await abrir(page, 390);
  await page.locator('#shellMenuTrigger').click();
  await expect(page.locator('.aia-navigation--sidebar')).toHaveAttribute('data-shell-drawer-open', 'true');
  await page.keyboard.press('Escape');
  await expect(page.locator('.aia-navigation--sidebar')).not.toHaveAttribute('data-shell-drawer-open', 'true');
  await expect(page.locator('#shellMenuTrigger')).toBeFocused();
});

test('abrir el menu en movil NO toca la preferencia guardada', async ({ page }) => {
  await abrir(page, 390);
  const antes = await page.evaluate(() => localStorage.getItem('aia-sidebar-state'));
  await page.locator('#shellMenuTrigger').click();
  await page.locator('#shellMenuTrigger').click();
  const despues = await page.evaluate(() => localStorage.getItem('aia-sidebar-state'));
  expect(despues).toBe(antes);
});

test('en 1440 el comportamiento es el de siempre y el disparador no se ve', async ({ page }) => {
  await abrir(page, 1440);
  await expect(page.locator('#shellMenuTrigger')).toBeHidden();
  const padding = await page.evaluate(() => getComputedStyle(document.body).paddingLeft);
  expect(padding).not.toBe('0px');
});

// Detectado el 2026-08-14 al generar la evidencia móvil del piloto: el
// disparador, fijo en la esquina superior izquierda, tapaba el texto de
// contexto (#ctxProyecto) porque .context-bar no reservaba su sitio. La
// captura lo mostraba a simple vista; esta prueba lo hubiera cazado sin
// necesidad de mirarla.
test('el disparador no se solapa con el texto de contexto en 390', async ({ page }) => {
  await abrir(page, 390);
  const solapa = await page.evaluate(() => {
    const trigger = document.getElementById('shellMenuTrigger');
    const ctx = document.getElementById('ctxProyecto');
    const tr = trigger.getBoundingClientRect();
    const cr = ctx.getBoundingClientRect();
    return !(tr.right < cr.left || tr.left > cr.right || tr.bottom < cr.top || tr.top > cr.bottom);
  });
  expect(solapa, 'El disparador tapa el texto de contexto').toBe(false);
});
