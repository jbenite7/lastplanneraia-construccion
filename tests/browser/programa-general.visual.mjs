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
// D16 (spec temas 2026-08-28): el carril visual corre AMBOS temas. `E2E_THEME`
// no elige el tema que se pinta —eso lo dice cada escenario, y `storeTheme` mas
// abajo ya lo materializa de verdad con recarga— sino que subconjunto corre esta
// invocacion, para que las dos patas de la matriz de CI midan la suya. Sin la
// variable corren los dos, que es lo que quiere una corrida local.
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(
  ({ theme }) => !process.env.E2E_THEME || theme === process.env.E2E_THEME,
);

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
  // Fila de CAPITULO (`Titulo: 1`). Se anade el 2026-08-05 porque las siete filas de abajo
  // llevaban todas `Titulo: 0` y el golden era ciego al encabezado sobrio que introdujo la Task 36
  // (`.handsontable td.pdc-header` en styles.css: peso 700, filete superior y superficie elevada).
  // La forma copia la que emite el endpoint real para `Titulo = 1`
  // (GeneralApiController::list:110): fuerza `Estado = 'Capítulo'`, `boton = 'No Boton'` y
  // `Ejecutado_Teorico = null`; `unidad` vacia sale como '%' y entonces `cantidad_ppto` se anula.
  // `Actividad` llega con `<b>` desde la base y `pgActividadRenderer` la pinta como HTML saneado.
  { Id: '1', Consecutivo: 0, Titulo: 1, Actividad: '<b>Capitulo 1 - Estructura</b>', Estado: 'Capítulo', Ruta_Critica: null, Semanas_Inicio: 0, Fecha_Inicio: '2026-02-02', Fecha_Fin: '2026-06-12', unidad: '%', cantidad_ppto: null, Ejecutado_Teorico: null, EjecutadoDisplay: '', Estado_Restricciones: 0, boton: 'No Boton' },
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

// Playwright sale con error si un archivo no registra NI UN test ("no tests
// found"), asi que una pata de la matriz cuyo tema aun no tiene escenarios en el
// manifiesto pondria el job en rojo sin que nada este roto. Este marcador la deja
// en verde y ADEMAS deja dicho en el informe por que no midio nada, que es justo
// lo que hoy pasa con `light`: los goldens claros no existen todavia porque la
// pagina no rinde en claro (ver el comentario largo de
// tests/design-system/visual-ci-contract.test.mjs).
if (VISUAL_SCENARIOS.length === 0) {
  test.skip(`sin escenarios visuales para el tema "${process.env.E2E_THEME}"`, () => {});
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
          // Tope ABSOLUTO, no ratio. Venia de 3 % (~29.000 px) y la campana lo bajo a 0,2 %, pero
          // 0,2 % siguen siendo 1.935 px a 1180x820 y 2.592 px a 1440x900: mas que suficiente para
          // perdonar los tratamientos finos. Medido el 2026-08-05 sobre la fila de capitulo que
          // ahora se siembra: anular el peso 700 y la superficie elevada de `td.pdc-header` mueve
          // 208 px a 1180x820 y 161 px a 1440x900 — la red lo dejaba pasar entero. El piso de ruido
          // se remidio en la misma sesion: con la tolerancia en 0 y sin tocar nada, CERO pixeles de
          // diferencia. Por eso 100 px es holgura de maquina, no necesidad del render, y ademas no
          // se afloja sola al crecer el viewport como hacia el ratio.
          maxDiffPixels: 100,
        },
      );
    });
}
