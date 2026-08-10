import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/programa-general-actualizar.json', import.meta.url),
  'utf8',
));
const ADMIN = { username: 'test.A', password: 'aia2026' };
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(({ theme }) => theme === 'dark');

// Filas fijas para la grilla de mapeo de Actualizar Cronograma.
//
// Igual que en programa-general.visual.mjs, el mock existe para que la captura no dependa del
// estado de la base ni del momento. La trampa documentada ahi es la misma aqui: un mock que
// devuelve `data: []` deja la grilla VACIA, y un golden de un tablero en blanco no detecta
// ninguna regresion de color ni de layout. Por eso estas filas tienen datos reales en todas las
// columnas que pinta `hot_actualizar.js` (columns de initHandsontable, linea 659 y siguientes):
// unique_id, Id, Actividad, programaAnteriorAsociar, Fecha_Inicio, Fecha_Fin, unidad,
// cantidad_ppto, Estado_Restricciones y Ejecutado.
//
// `programaAnteriorAsociar` es la columna que decide el tinte `pg-row-unmapped`
// (afterRenderer en hot_actualizar.js): `null`, `''` o `'*No Asociada*'` la marcan sin asociar.
// Se dejan a proposito dos filas sin asociar y tres asociadas para que el golden capture ambos
// estados, no solo uno.
const FILAS_DE_MAPEO = [
  { unique_id: 1, Id: '1', Actividad: 'Cimentacion eje 4', programaAnteriorAsociar: 'Cimentacion eje 4', Fecha_Inicio: '2026-02-02', Fecha_Fin: '2026-02-20', unidad: 'm3', cantidad_ppto: 120, Estado_Restricciones: 1, Ejecutado: 1 },
  { unique_id: 2, Id: '2', Actividad: 'Muros nivel 2', programaAnteriorAsociar: 'Muros nivel 2', Fecha_Inicio: '2026-03-02', Fecha_Fin: '2026-03-27', unidad: 'm2', cantidad_ppto: 340, Estado_Restricciones: 0.62, Ejecutado: 0.62 },
  { unique_id: 3, Id: '3', Actividad: 'Redes hidrosanitarias', programaAnteriorAsociar: '*No Asociada*', Fecha_Inicio: '2026-05-04', Fecha_Fin: '2026-06-12', unidad: 'ml', cantidad_ppto: 520, Estado_Restricciones: 0, Ejecutado: 0 },
  { unique_id: 4, Id: '4', Actividad: 'Instalacion electrica', programaAnteriorAsociar: null, Fecha_Inicio: '2026-04-06', Fecha_Fin: '2026-05-15', unidad: 'ml', cantidad_ppto: 480, Estado_Restricciones: 0, Ejecutado: 0 },
  { unique_id: 5, Id: '5', Actividad: 'Losa nivel 3', programaAnteriorAsociar: 'Losa nivel 3', Fecha_Inicio: '2026-03-16', Fecha_Fin: '2026-04-10', unidad: 'm3', cantidad_ppto: 95, Estado_Restricciones: 0.35, Ejecutado: 0.17 },
];

async function mockDeterministicData(page) {
  await page.route('**/api/general/codigos**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }),
  }));
  await page.route('**/api/general/list**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS_DE_MAPEO }),
  }));
}

async function storeTheme(page, theme) {
  await page.evaluate((value) => localStorage.setItem('aia-theme', value), theme);
}

for (const scenario of VISUAL_SCENARIOS) {
    test(`${scenario.id} remains stable`, async ({ page }) => {
      await mockDeterministicData(page);
      await loginAndSelectProject(page, PROJECTS[0], ADMIN);
      await changeWeek(page, PROJECTS[0].maxWeek, '/programa-general-actualizar');
      await page.setViewportSize(scenario.viewport);
      await storeTheme(page, scenario.theme);
      await page.reload({ waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
      await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
      // `#loading` es el overlay de carga inicial de la pagina (linksComunesHead2.js lo oculta
      // con un timeout de 500 ms tras resolver el fetch de datos generales). Sin esta espera el
      // golden retrata el spinner a mitad de pantalla en vez de la grilla estable.
      await expect(page.locator('#loading')).toBeHidden({ timeout: 10000 });
      await page.evaluate(() => document.fonts.ready);
      // A diferencia de programa-general.visual.mjs, aqui NO se espera a que `#save-status` se
      // oculte: `programa-general-actualizar.css` no trae la regla `.badge-badge-hidden` que sí
      // define `programa-general.css` (`.pg-page .pg-status-badges .badge-badge-hidden`), asi
      // que el chip "Auto-Guardado" queda visible de forma permanente en esta pantalla. Es el
      // comportamiento real de la interfaz hoy -- se documenta, no se corrige aqui (fuera del
      // alcance de esta tarea) -- y el golden lo retrata tal como es.
      await expect(page).toHaveScreenshot(
        path.basename(scenario.golden),
        {
          fullPage: false,
          maxDiffPixels: 100,
        },
      );
    });
}
