import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PROJECTS } from './fixtures/projects.mjs';
import { elegirEnSelector } from './support/pdc-selector.mjs';

// Contra la obra real (Da Porto), no contra el sandbox: este frente SOLO LEE —no amarra, no
// recalcula, no escribe una sola fila—, así que no hay nada que sembrar ni que restaurar. Y es el
// único proyecto con plan calculado de verdad, que es lo que hace que el tablero tenga qué agrupar.
const project = PROJECTS.find((p) => p.projectId === 73) ?? PROJECTS[0];

// Los cortes que la pantalla sabe nombrar. Cualquier otra palabra en la columna «Estado» del plan
// significa que el servidor mandó un corte nuevo y la SPA lo está enseñando crudo.
const CORTES_CONOCIDOS = [
  'Vencido', 'Vence en 1 semana', 'Vence en 2 semanas', 'Vence en 3 semanas',
  'Vence en 6 semanas', 'Más adelante', 'Sin fecha programada', 'Cumplido',
];

test.describe('PDC v2 · B2 — vencimientos', () => {
  test.afterEach(async ({ page }) => { await logout(page); });

  test('la pestaña agrupa lo pendiente, cuadra con el servidor y declara lo que no mira', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/seguimiento/avance', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Seguimiento', { timeout: 15000 });

    await page.getByRole('tab', { name: /Vencimientos/ }).click();
    await expect(page.getByTestId('pdc-venc-conteos')).toBeVisible({ timeout: 15000 });

    // La verdad la dice el servidor: la pantalla no puede contradecirlo.
    const api = await page.evaluate(async () => {
      const r = await fetch('/plan-compras/api/seguimiento/vencimientos', { credentials: 'same-origin' });
      return (await r.json()).data;
    });
    const suma = Object.values(api.conteos).reduce((a, b) => a + b, 0);
    expect(api.totalPendientes, 'los conteos por corte suman el total de pasos pendientes').toBe(suma);

    // Cada corte con filas tiene su grupo en pantalla; el que no las tiene, no inventa una sección.
    for (const corte of ['vencido', 'sem1', 'sem2', 'sem3', 'sem6', 'sin_fecha']) {
      const grupo = page.getByTestId(`pdc-venc-grupo-${corte}`);
      if ((api.conteos[corte] ?? 0) > 0) {
        await expect(grupo, `falta el grupo ${corte}`).toBeVisible();
      } else {
        await expect(grupo, `sobra el grupo ${corte}`).toHaveCount(0);
      }
    }

    // «Más adelante» se cuenta pero no se lista: su número está en los chips y no hay grupo.
    await expect(page.locator('[data-corte="adelante"]')).toContainText(String(api.conteos.adelante ?? 0));
    await expect(page.getByTestId('pdc-venc-grupo-adelante')).toHaveCount(0);

    // Un tablero vacío y un tablero ciego no pueden verse igual.
    const aviso = page.getByTestId('pdc-venc-sin-fechas');
    if (api.sinFechas.paquetes > 0) {
      await expect(aviso).toContainText(String(api.sinFechas.paquetes));
    } else {
      await expect(aviso).toHaveCount(0);
    }
  });

  test('filtrar por paso deja solo las filas de ese paso', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/seguimiento/avance', { waitUntil: 'domcontentloaded' });
    await page.getByRole('tab', { name: /Vencimientos/ }).click();
    await expect(page.getByTestId('pdc-venc-conteos')).toBeVisible({ timeout: 15000 });

    // El `Selector` no es un <select>: para leer las opciones hay que abrir su popup, igual que
    // haría una persona.
    await page.getByTestId('pdc-venc-filtro-paso').click();
    const popup = page.locator('.pdc-selector-popup');
    await popup.waitFor({ state: 'visible' });
    // La primera opción es «Todos» (opción de negocio elegible, no un placeholder): el paso real a
    // filtrar es la segunda.
    const opciones = popup.getByRole('option');
    const cuantas = await opciones.count();
    test.skip(cuantas < 2, 'El proyecto no tiene pasos pendientes que filtrar.');

    const etiqueta = (await opciones.nth(1).textContent())?.trim();
    await page.keyboard.press('Escape');
    await popup.waitFor({ state: 'detached' });

    await elegirEnSelector(page, 'pdc-venc-filtro-paso', etiqueta);
    // El filtro va al servidor: hay que esperar a que la tabla se repinte con la respuesta nueva.
    await expect(page.locator('.pdc-venc-grupo tbody tr').first()).toBeVisible({ timeout: 15000 });

    // La primera celda de cada fila es un <th scope="row"> con el paquete, así que el paso —la
    // segunda columna— es el segundo hijo de la fila, no el primer <td>.
    const pasos = await page.locator('.pdc-venc-grupo tbody tr td:nth-child(2)').allTextContents();
    expect(pasos.length).toBeGreaterThan(0);
    for (const p of pasos) {
      expect(p.trim()).toBe(etiqueta);
    }
  });

  test('el semáforo del plan usa las mismas palabras que el tablero', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });

    // `.ag-row` a secas, igual que pdc-v2-plan.spec.mjs: el contenedor interno de AG Grid cambia de
    // nombre entre versiones y no es parte de ningún contrato.
    const filas = page.locator('[data-testid="pdc-plan-grid"] .ag-row');
    await expect(filas.first()).toBeVisible({ timeout: 20000 });
    // En la celda de «Paquete», no en cualquiera: clicar la de Frente o Responsable abre su editor
    // (singleClickEdit) y la de Desamarrar dispara la confirmación, en vez de expandir la fila.
    await filas.first().locator('[col-id="nombre"]').click();

    const detalle = page.getByTestId('pdc-plan-detalle');
    await expect(detalle).toBeVisible({ timeout: 15000 });
    const estados = await detalle.locator('tbody tr td:last-child').allTextContents();
    expect(estados.length, 'la tabla de pasos trae filas').toBeGreaterThan(0);
    for (const e of estados) {
      expect(CORTES_CONOCIDOS).toContain(e.trim());
    }
  });
});
