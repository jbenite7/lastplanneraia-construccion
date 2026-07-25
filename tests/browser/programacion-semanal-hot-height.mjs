import { test, expect } from '@playwright/test';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { PROJECTS } from './fixtures/projects.mjs';

/**
 * Contrato de geometria de #hot-container en /programacion-semanal.
 *
 * Alcance: desktop 1180x820 dark (AGENTS.md). La altura del contenedor la
 * resuelve `syncContainerHeight()` midiendo `getBoundingClientRect().top`, de
 * modo que la grilla llena el viewport sin dejar hueco al fondo y sin provocar
 * scroll vertical del documento. La invariante debe sobrevivir a un reflujo de
 * `.header-actions` (la toolbar cambia de alto cuando se pueblan la leyenda,
 * `#mensajeActualizacion` o los botones sujetos a permisos/estado de semana).
 *
 * Regresion cubierta: `updateTableHeight()` recalculaba la altura con el offset
 * del navbar legacy (180px) desde un ResizeObserver sobre `.header-actions`,
 * sobrescribia el valor correcto y dejaba la grilla ~123px corta de forma
 * permanente (solo un resize de ventana la recuperaba).
 */

const VIEWPORT = { width: 1180, height: 820 };
// syncContainerHeight() reserva 2px de bottomGap; se admite el desplazamiento
// de 1-2px que introduce el reflujo posterior de la toolbar.
const MAX_BOTTOM_GAP = 8;

async function readGeometry(page) {
  return page.evaluate(() => {
    const container = document.getElementById('hot-container');
    const holder = document.querySelector('#hot-container .ht_master .wtHolder');
    const rect = container.getBoundingClientRect();
    return {
      inlineHeight: container.style.height,
      clientHeight: container.clientHeight,
      rectTop: Math.round(rect.top),
      rectBottom: Math.round(rect.bottom),
      innerHeight: window.innerHeight,
      bottomGap: Math.round(window.innerHeight - rect.bottom),
      docScrollHeight: document.documentElement.scrollHeight,
      holderHeight: holder ? Math.round(holder.getBoundingClientRect().height) : null,
      headerActionsHeight: document.querySelector('.header-actions')?.offsetHeight ?? null,
    };
  });
}

function expectFitsViewport(label, geo) {
  const detail = `${label}: ${JSON.stringify(geo)}`;
  // Sin hueco muerto al fondo...
  expect(geo.bottomGap, detail).toBeLessThanOrEqual(MAX_BOTTOM_GAP);
  // ...y sin desbordar el viewport.
  expect(geo.bottomGap, detail).toBeGreaterThanOrEqual(0);
  expect(geo.docScrollHeight, detail).toBeLessThanOrEqual(VIEWPORT.height);
  // La grilla de Handsontable sigue al contenedor.
  expect(geo.holderHeight, detail).toBe(geo.clientHeight);
}

test.use({ viewport: VIEWPORT });

test('la altura de #hot-container llena el viewport y sobrevive al reflujo de .header-actions', async ({ page }) => {
  const errors = installErrorCollectors(page);
  await loginAndSelectProject(page, PROJECTS[0]);
  await page.goto('/programacion-semanal', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#hot-container .handsontable', { timeout: 60_000 });
  await page.waitForTimeout(2_500);

  const initial = await readGeometry(page);
  expectFitsViewport('carga inicial', initial);

  // Reflujo de la toolbar: crece y vuelve a su tamano, como al poblarse la
  // leyenda, `#mensajeActualizacion` o los botones sujetos a permisos.
  await page.evaluate(() => {
    const probe = document.createElement('div');
    probe.id = 'hot-height-probe';
    probe.style.height = '40px';
    document.querySelector('.header-actions').appendChild(probe);
  });
  await page.waitForTimeout(1_200);

  const grown = await readGeometry(page);
  expect(grown.rectTop, `la toolbar crecio: ${JSON.stringify(grown)}`)
    .toBeGreaterThan(initial.rectTop);
  expectFitsViewport('toolbar crecida', grown);

  await page.evaluate(() => document.getElementById('hot-height-probe').remove());
  await page.waitForTimeout(1_200);

  const restored = await readGeometry(page);
  expect(restored.headerActionsHeight, `la toolbar recupero su alto: ${JSON.stringify(restored)}`)
    .toBe(initial.headerActionsHeight);
  expectFitsViewport('toolbar restaurada', restored);

  assertNoRuntimeErrors(errors);
});
