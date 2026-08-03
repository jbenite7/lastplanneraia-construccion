import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/programa-general.json', import.meta.url),
  'utf8',
));
const ADMIN = { username: 'test.A', password: 'aia2026' };
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(({ theme, viewport }) => theme === 'dark' && viewport.width >= 1180);

// Filas fijas que cubren la escala de estado de Programa General.
//
// El mock existe para que la captura no dependa del estado de la base ni del momento, y eso se
// conserva. Lo que se corrige es que devolvia `data: []`: la grilla salia VACIA, asi que los
// goldens retrataban un tablero en blanco y no podian detectar ninguna regresion de color. El
// proyecto sembrado si tiene datos —Da Porto tiene 273 filas en `programa`— y no era el problema.
//
// `classifyPGRow()` decide la clase con tres entradas, y las tres importan aqui:
//   · sin `Consecutivo` ni `Id` cae a `sin-datos`, y asi se provoca ese peldano a proposito;
//   · `Titulo` distinto de 0 se clasifica como cabecera (`pdc-header`), no como estado;
//   · `Estado` se normaliza contra una lista cerrada — las etiquetas de abajo salen de
//     `normalizeEstadoToStateKey()`, no son texto libre.
const FILAS_DE_ESTADO = [
  { Id: 1, Consecutivo: 1, Titulo: 0, Actividad: 'Cimentacion eje 4', Estado: 'Terminada', Ruta_Critica: '0', Semanas_Inicio: 1, Fecha_Inicio: '2026-02-02', Fecha_Fin: '2026-02-20', unidad: 'm3', cantidad_ppto: 120, Ejecutado_Teorico: 120, EjecutadoDisplay: '100%', Estado_Restricciones: 'Liberada' },
  { Id: 2, Consecutivo: 2, Titulo: 0, Actividad: 'Muros nivel 2', Estado: 'En curso', Ruta_Critica: '0', Semanas_Inicio: 2, Fecha_Inicio: '2026-03-02', Fecha_Fin: '2026-03-27', unidad: 'm2', cantidad_ppto: 340, Ejecutado_Teorico: 210, EjecutadoDisplay: '62%', Estado_Restricciones: 'Liberada' },
  { Id: 3, Consecutivo: 3, Titulo: 0, Actividad: 'Redes hidrosanitarias', Estado: 'Actividad futura', Ruta_Critica: '0', Semanas_Inicio: 8, Fecha_Inicio: '2026-05-04', Fecha_Fin: '2026-06-12', unidad: 'ml', cantidad_ppto: 520, Ejecutado_Teorico: 0, EjecutadoDisplay: '0%', Estado_Restricciones: 'Pendiente' },
  { Id: 4, Consecutivo: 4, Titulo: 0, Actividad: 'Instalacion electrica', Estado: 'Debe iniciar', Ruta_Critica: '0', Semanas_Inicio: 4, Fecha_Inicio: '2026-04-06', Fecha_Fin: '2026-05-15', unidad: 'ml', cantidad_ppto: 480, Ejecutado_Teorico: 0, EjecutadoDisplay: '0%', Estado_Restricciones: 'Pendiente' },
  { Id: 5, Consecutivo: 5, Titulo: 0, Actividad: 'Losa nivel 3', Estado: 'Atrasada', Ruta_Critica: '0', Semanas_Inicio: 3, Fecha_Inicio: '2026-03-16', Fecha_Fin: '2026-04-10', unidad: 'm3', cantidad_ppto: 95, Ejecutado_Teorico: 60, EjecutadoDisplay: '17%', Estado_Restricciones: 'Pendiente' },
  // Ruta critica marcada: comparte estado con la anterior pero anade el realce de criticidad.
  { Id: 6, Consecutivo: 6, Titulo: 0, Actividad: 'Fachada oriente', Estado: 'Atrasada', Ruta_Critica: '1', Semanas_Inicio: 3, Fecha_Inicio: '2026-03-16', Fecha_Fin: '2026-04-24', unidad: 'm2', cantidad_ppto: 260, Ejecutado_Teorico: 130, EjecutadoDisplay: '8%', Estado_Restricciones: 'Pendiente' },
  // Deliberadamente SIN Consecutivo ni Id: es la unica via para provocar `sin-datos`.
  { Titulo: 0, Actividad: 'Cubierta', Estado: '', Ruta_Critica: '0', Semanas_Inicio: null, Fecha_Inicio: '', Fecha_Fin: '', unidad: '', cantidad_ppto: null, Ejecutado_Teorico: null, EjecutadoDisplay: '', Estado_Restricciones: '' },
];

async function mockDeterministicData(page) {
  await page.route('**/api/general/restriction-config**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: false }),
  }));
  await page.route('**/api/general/codigos**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }),
  }));
  await page.route('**/programa-general/filtros', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }),
  }));
  await page.route('**/api/general/list**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS_DE_ESTADO }),
  }));
  await page.route('**/api/general/update-batch**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ respuesta: 'BIEN' }),
  }));
}

async function storeTheme(page, theme) {
  await page.evaluate((value) => localStorage.setItem('aia-theme', value), theme);
}

async function expectLegendContained(page) {
  await expect.poll(() => page.locator('#pgLegend').evaluate((legend) => {
    const boundary = legend.getBoundingClientRect();
    return [...legend.querySelectorAll('.pg-filter-chip')].every((item) => {
      const box = item.getBoundingClientRect();
      return box.left >= boundary.left - 1 && box.right <= boundary.right + 1;
    });
  })).toBe(true);
}

for (const scenario of VISUAL_SCENARIOS) {
    test(`${scenario.id} remains stable`, async ({ page }) => {
      await mockDeterministicData(page);
      await loginAndSelectProject(page, PROJECTS[0], ADMIN);
      await changeWeek(page, PROJECTS[0].maxWeek, '/programa-general');
      await page.setViewportSize(scenario.viewport);
      await storeTheme(page, scenario.theme);
      await page.reload({ waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
      await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
      await expectLegendContained(page);
      await page.evaluate(() => document.fonts.ready);
      // The auto-update flow emits a short-lived success badge that can still be
      // visible depending on runner timing. Wait for it to settle so the visual
      // contract captures the stable toolbar state instead of a transient toast.
      await expect(page.locator('#save-status')).toBeHidden({ timeout: 10000 });
      await expect(page).toHaveScreenshot(
        path.basename(scenario.golden),
        {
          fullPage: false,
          maxDiffPixelRatio: 0.03,
        },
      );
    });
}
