import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PROJECTS } from './fixtures/projects.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Las seis pantallas con tabla. «plan/pasos» no tiene grilla, así que no aplica.
const RUTAS = ['importar', 'maestro', 'presupuesto', 'comparar', 'paquetes', 'plan'];

/**
 * «comparar» es la única cuya tabla depende de los datos: con menos de dos versiones importadas la
 * pantalla no pinta grilla, sino su vacío declarado (ver ComparativoPresupuesto.tsx). Da Porto tiene
 * una sola versión, así que exigirle grilla incondicionalmente ponía este test en rojo desde que se
 * escribió (2026-07-29) sin que hubiera nada roto en la aplicación.
 *
 * No se saca de la lista: cuando la obra sí tenga dos versiones, su comparativo es de las tablas más
 * anchas y hay que medirlo. Lo que se hace es exigir la EXPLICACIÓN: sin grilla, la pantalla tiene
 * que estar enseñando ese vacío concreto. «No hay tabla porque no hay nada que comparar» es correcto;
 * «no hay tabla y nadie dice por qué» es el fallo que este test debe seguir cazando.
 */
const VACIO_DECLARADO = {
  comparar: 'Necesitas al menos dos versiones importadas para comparar.',
};

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
  const sinDatos = new Set();
  try {
    for (const cond of CONDICIONES) {
      await page.setViewportSize({ width: cond.w, height: cond.h });
      await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
      await page.evaluate((estado) => localStorage.setItem('aia-sidebar-state', estado), cond.lateral);

      for (const ruta of RUTAS) {
        await page.goto(`/plan-compras#/ensamble/${ruta}`, { waitUntil: 'domcontentloaded' });
        await page.reload({ waitUntil: 'domcontentloaded' });
        const grid = page.locator('.pdc-grid, .pdc-grid-wrap, .pdc-grid-corta').first();

        // Solo las rutas con vacío declarado pueden no tener grilla, y solo si lo enseñan. Para las
        // otras cinco la ausencia de tabla sigue siendo un fallo duro, como hasta ahora.
        const vacioEsperado = VACIO_DECLARADO[ruta];
        if (vacioEsperado !== undefined && !(await grid.isVisible())) {
          await expect(
            page.getByText(vacioEsperado),
            `${cond.nombre} · ${ruta}: sin grilla y sin el vacío declarado que lo explique`,
          ).toBeVisible({ timeout: 20000 });
          // Se anota para que la corrida diga qué dejó de medir: una cobertura que se pierde en
          // silencio se acaba dando por hecha.
          sinDatos.add(ruta);
          continue;
        }

        await expect(grid, `${cond.nombre} · ${ruta}`).toBeVisible({ timeout: 20000 });
        await expect(grid.locator('.ag-header-cell').first()).toBeVisible({ timeout: 20000 });

        // El reajuste de anchos lo disparan eventos de AG Grid tras pintar las filas
        // (`ajusteDeAncho` en pdc-app/src/lib/agGrid.ts: onFirstDataRendered + onGridSizeChanged), y
        // un `waitForTimeout` fijo era una apuesta sobre cuándo aterriza el último. Medido: en una
        // corrida tranquila el desborde es 0 desde el primer instante, pero encadenando specs
        // aparecía 1 px intermitente en «importar» a 1440 — la medición llegaba a mitad del reajuste.
        //
        // Se espera a la CONDICIÓN, no a un número de milisegundos. Esto no ablanda el test: un
        // desborde de verdad no se va solo, agota el plazo y falla igual; lo único que deja de
        // fallar es el instante intermedio del propio layout.
        await expect
          .poll(
            () => page.evaluate(() => {
              const g = document.querySelector('.pdc-grid, .pdc-grid-wrap, .pdc-grid-corta');
              const vp = g && (g.querySelector('.ag-body-horizontal-scroll-viewport')
                || g.querySelector('.ag-center-cols-viewport'));
              return vp ? vp.scrollWidth - vp.clientWidth : null;
            }),
            { message: `${cond.nombre} · ${ruta}`, timeout: 10000, intervals: [100, 200, 300, 500] },
          )
          .toBe(0);
      }
    }
    // Va en el reporte, no en un console.log que nadie lee: si algún día esta obra tiene dos
    // versiones y la ruta se empieza a medir, la anotación desaparece sola y eso también se ve.
    if (sinDatos.size > 0) {
      test.info().annotations.push({
        type: 'sin-datos',
        description: `No se midió el ancho de: ${[...sinDatos].join(', ')} — la obra `
          + `«${project.name}» no tiene datos suficientes y la pantalla enseña su vacío declarado.`,
      });
    }
  } finally {
    await page.evaluate(() => localStorage.setItem('aia-sidebar-state', 'collapsed')).catch(() => {});
    await logout(page).catch(() => {});
  }
});
