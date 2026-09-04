import { expect, test } from '@playwright/test';
import { bootstrapAutenticado } from './fixtures/shell-runtime-react.mjs';

/**
 * Recuperación de errores globales del shell (Tarea 8, T01 §15) con la red COMPLETAMENTE
 * interceptada ANTES de navegar — nunca toca el backend real (mismo patrón que
 * `shell-control-actividad.spec.mjs`).
 *
 * Alcance honesto: hoy el único consumidor en vivo de `pedir()` fuera del bootstrap es
 * `ControlActividad` (`/session/touch`, ya cubierto en `shell-control-actividad.spec.mjs`). Los
 * demás casos de la tabla de §15 (403/404/409/422/5xx de un módulo, error de render de una
 * pantalla) no tienen todavía una superficie real que los dispare — quedan probados a nivel de
 * componente en `frontend/src/shell/errores/*.test.tsx` (`PanelError`, `LimiteErrorRuta`,
 * `clasificarError`, `useRecuperacionErrorApi`). Este spec cubre lo que SÍ es alcanzable desde el
 * navegador hoy: la recuperación de red del bootstrap, y que el harness detecta cualquier
 * petición o error inesperado.
 */

test.describe('Shell React — recuperación de errores globales (red interceptada)', () => {
  test('el bootstrap sin red muestra la pantalla recuperable y "Reintentar" la resuelve', async ({ page }) => {
    const requests = [];
    const paginaErrors = [];
    let primeraLlamada = true;

    page.on('pageerror', (error) => paginaErrors.push(String(error)));

    await page.route('**/api/**', async (route) => {
      requests.push({ url: route.request().url(), method: route.request().method(), inesperado: true });
      await route.abort('failed');
    });

    // El bootstrap falla la primera vez (red caída) y responde bien en el reintento.
    await page.route('**/api/session', async (route) => {
      if (primeraLlamada) {
        primeraLlamada = false;
        await route.abort('failed');
        return;
      }
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado()) });
    });
    await page.route('**/session/touch', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
      });
    });

    await page.goto('/app');

    const alerta = page.getByRole('alert');
    await expect(alerta).toBeVisible();
    await expect(alerta).toContainText(/no pudimos conectar/i);
    // Nunca se filtra un cuerpo crudo (HTML/JSON) a la UI del panel recuperable.
    await expect(alerta).not.toContainText('<html');

    await page.getByRole('button', { name: /reintentar/i }).click();

    await expect(page.getByRole('navigation')).toBeVisible();
    expect(paginaErrors).toEqual([]);
    expect(requests.some((r) => r.inesperado)).toBe(false);
  });

  test('el harness detecta una petición no interceptada como fallo del propio fixture', async ({ page }) => {
    const requests = [];

    await page.route('**/api/**', async (route) => {
      requests.push({ url: route.request().url(), method: route.request().method(), inesperado: true });
      await route.abort('failed');
    });
    await page.route('**/api/session', async (route) => {
      requests.push({ url: route.request().url(), method: route.request().method() });
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado()) });
    });
    await page.route('**/session/touch', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
      });
    });
    // A propósito, SIN interceptar `/api/proyectos`: cualquier código que lo llame cae en el
    // catch-all `inesperado`. Lo disparamos manualmente para probar que el catch-all funciona.
    await page.goto('/app');
    await expect(page.getByRole('navigation')).toBeVisible();

    await page.evaluate(() => fetch('/api/proyectos').catch(() => {}));
    await page.waitForTimeout(200);

    expect(requests.some((r) => r.inesperado && r.url.includes('/api/proyectos'))).toBe(true);
  });

  test('sin errores de página ni de consola durante un recorrido normal del shell', async ({ page }) => {
    const paginaErrors = [];
    const consolaErrors = [];
    page.on('pageerror', (error) => paginaErrors.push(String(error)));
    page.on('console', (msg) => {
      if (msg.type() === 'error') consolaErrors.push(msg.text());
    });

    await page.route('**/api/**', (route) => route.abort('failed'));
    await page.route('**/api/session', (route) => (
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado()) })
    ));
    await page.route('**/session/touch', (route) => (
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }) })
    ));

    await page.goto('/app');
    await expect(page.getByRole('navigation')).toBeVisible();
    await page.getByRole('button', { name: /cuenta ·/i }).click();
    await expect(page.getByRole('menuitem', { name: /cambiar proyecto/i })).toBeVisible();
    await page.getByRole('menuitem', { name: /cerrar sesión/i }).isVisible();

    expect(paginaErrors).toEqual([]);
    expect(consolaErrors).toEqual([]);
  });

  test('sin overflow horizontal de página en el shell autenticado (390×844)', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.route('**/api/**', (route) => route.abort('failed'));
    await page.route('**/api/session', (route) => (
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado()) })
    ));
    await page.route('**/session/touch', (route) => (
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }) })
    ));

    await page.goto('/app');
    await expect(page.getByRole('navigation')).toBeVisible();

    const overflow = await page.evaluate(() => (
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    ));
    expect(overflow).toBeLessThanOrEqual(1);
  });
});
