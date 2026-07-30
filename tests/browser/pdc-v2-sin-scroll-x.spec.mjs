import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PROJECTS } from './fixtures/projects.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Las seis pantallas con tabla. «plan/pasos» no tiene grilla, así que no aplica.
const RUTAS = ['importar', 'maestro', 'presupuesto', 'comparar', 'paquetes', 'plan'];

/**
 * El ancho de la tabla NO es el de la ventana: la barra lateral del shell expandida se lleva 208 px,
 * así que a 1180 la grilla trabaja con 820. Ese era el caso que se escapaba —hasta 142 px de scroll
 * lateral en Presupuesto—, y por eso las tres condiciones se prueban, no solo la cómoda.
 *
 * Si este test se pone rojo, lo más probable es que una columna nueva no esté declarada en la lista
 * de prescindibles de su pantalla (ver `columnasQueCaben` en pdc-app/src/lib/agGrid.ts).
 */
const CONDICIONES = [
  { nombre: '1180 lateral cerrada', w: 1180, h: 820, lateral: 'collapsed' },
  { nombre: '1180 lateral abierta', w: 1180, h: 820, lateral: 'expanded' },
  { nombre: '1440 lateral abierta', w: 1440, h: 900, lateral: 'expanded' },
];

test('las tablas del PDC no tienen scroll horizontal en desktop', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  await loginAndSelectProject(page, project);
  try {
    for (const cond of CONDICIONES) {
      await page.setViewportSize({ width: cond.w, height: cond.h });
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.evaluate((estado) => localStorage.setItem('aia-sidebar-state', estado), cond.lateral);

      for (const ruta of RUTAS) {
        await page.goto(`/plan-compras#/ensamble/${ruta}`, { waitUntil: 'domcontentloaded' });
        await page.reload({ waitUntil: 'domcontentloaded' });
        const grid = page.locator('.pdc-grid, .pdc-grid-wrap, .pdc-grid-corta').first();

        // Una pantalla puede no tener grilla porque a la obra le faltan datos, no porque algo se
        // haya roto. «Comparar» es el caso real: necesita DOS versiones de presupuesto, y Da Porto
        // ha llegado a tener una sola —el test se ponía rojo y parecía una regresión de anchos—.
        //
        // Solo se tolera el vacío que la propia pantalla DECLARA. Si no hay grilla y tampoco hay
        // mensaje que lo explique, se falla: es exactamente la diferencia entre un dato que falta y
        // un defecto que se esconde.
        if (!(await grid.count())) {
          const vacioDeclarado = page.getByText(/Necesitas al menos dos versiones|No hay .* para mostrar/i);
          if (await vacioDeclarado.count()) {
            const motivo = (await vacioDeclarado.first().innerText()).trim();
            test.info().annotations.push({
              type: 'omitida',
              description: `${cond.nombre} · ${ruta}: sin grilla porque la obra no tiene los datos — «${motivo}»`,
            });
            continue;
          }
        }
        await expect(grid, `${cond.nombre} · ${ruta}`).toBeVisible({ timeout: 20000 });
        await expect(grid.locator('.ag-header-cell').first()).toBeVisible({ timeout: 20000 });
        // El reajuste de anchos ocurre tras pintar las filas (ver `ajusteDeAncho`).
        await page.waitForTimeout(1200);

        const desborde = await page.evaluate(() => {
          const g = document.querySelector('.pdc-grid, .pdc-grid-wrap, .pdc-grid-corta');
          const vp = g && (g.querySelector('.ag-body-horizontal-scroll-viewport')
            || g.querySelector('.ag-center-cols-viewport'));
          return vp ? vp.scrollWidth - vp.clientWidth : null;
        });
        expect(desborde, `${cond.nombre} · ${ruta}`).toBe(0);
      }
    }
  } finally {
    await page.evaluate(() => localStorage.setItem('aia-sidebar-state', 'collapsed')).catch(() => {});
    await logout(page).catch(() => {});
  }
});
