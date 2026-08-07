import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/programacion-intermedia.json', import.meta.url),
  'utf8',
));
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(
  ({ theme }) => theme === 'dark',
);

// Filas fijas que cubren la escala de estado propia de Programacion Intermedia.
//
// El mock existe para que la captura no dependa de la base ni del momento, y eso se conserva. Lo
// que se corrige es que devolvia `data: []`: la grilla salia VACIA, asi que el golden solo
// retrataba cromo y maquetacion y era ciego a bordes de celda, tintes de fila y ruta critica. La
// Task 33 lo midio: cambiar el borde de celda de 1px a 2px no movio ni un pixel de este golden.
//
// PI NO comparte vocabulario con Programa General: aqui no se sigue el avance sino el
// ALISTAMIENTO (liberacion de restricciones). `PIStateMachine.getState()`
// (public/js/modules/programacion_intermedia/stateMachine.js:161) decide con cuatro entradas, y
// las cuatro se usan abajo:
//   · `unique_id` ausente devuelve un estado degenerado -no se siembra: el endpoint real siempre
//     trae identificador;
//   · `Semanas_Inicio` (negativo = vencido, 0, 1, 2-3, 4-6, >6) marca los peldanos de alerta;
//   · `Ejecutado` en (0, 1) convierte la fila en "en ejecucion";
//   · las restricciones duras (`D_y_E`, `Materiales`, `MdeO`, `Equipos` al 100 %, `Predecesora`
//     al 50 %) deciden si esta liberada;
//   · `Ruta_Critica` solo separa `blocked-overdue` de `blocked-overdue-critical`.
//
// Se siembran los NUEVE estados que la vista tine (`statePresentation`, hot.js:437): ocho de
// `trackedStates` mas `neutral`. NO se siembra `header` (Titulo != 0) porque el endpoint real
// filtra `Titulo = 0` (ProgramacionIntermediaController::list) y esa fila no existe en PI.
const FILAS_DE_ESTADO = [
  // Bloqueo por falta de Responsable AIA (Task 38). Se anade el 2026-08-05 porque las nueve filas
  // de abajo llevaban todas responsable asignado y el golden era ciego al tratamiento: con
  // `Responsable_AIA` vacio, `buildPICellProperties()` (hot.js:938) marca cada celda de restriccion
  // `readOnly` + `pi-cell-locked-resp` —candado delante del valor— y `piResponsableRenderer`
  // sustituye la celda del responsable por «🔒 Falta Responsable AIA». Va la PRIMERA a proposito:
  // en 1180x820 solo entran unas cinco filas y mas abajo no saldria en el retrato.
  { unique_id: 100, Id: 100, Titulo: 0, Actividad: 'Localizacion y replanteo', Sub_Contratista: 'Topografia Andina', Responsable_AIA: '', Semanas_Inicio: 0, Ejecutado: 0, D_y_E: '100%', Materiales: '100%', MdeO: '0%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '50%', Modelo: '0%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // blocked-overdue-critical: vencido, sin liberar y en ruta critica.
  { unique_id: 101, Id: 101, Titulo: 0, Actividad: 'Pilotaje eje A', Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'L. Marin', Semanas_Inicio: -2, Ejecutado: 0, D_y_E: '100%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '50%', Modelo: 'N/A', Ruta_Critica: '1', alerta_crisis: 0, Observaciones: '' },
  // blocked-overdue: mismo vencimiento, sin realce de criticidad.
  { unique_id: 102, Id: 102, Titulo: 0, Actividad: 'Excavacion sotano 1', Sub_Contratista: 'Movitierra', Responsable_AIA: 'C. Rojas', Semanas_Inicio: -1, Ejecutado: 0, D_y_E: '33%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '50%', Pdto_Cons: '100%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // blocked-due: debe iniciar esta semana y aun no esta liberada.
  { unique_id: 103, Id: 103, Titulo: 0, Actividad: 'Vigas de cimentacion', Sub_Contratista: 'Estructuras Andinas', Responsable_AIA: 'C. Rojas', Semanas_Inicio: 0, Ejecutado: 0, D_y_E: '100%', Materiales: '66%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // alert-1-week: alistamiento urgente.
  { unique_id: 104, Id: 104, Titulo: 0, Actividad: 'Placa nivel 1', Sub_Contratista: 'Estructuras Andinas', Responsable_AIA: 'M. Torres', Semanas_Inicio: 1, Ejecutado: 0, D_y_E: '100%', Materiales: '33%', MdeO: '100%', Equipos: '66%', Predecesora: '100%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // execution-blocked: ya arranco pero sigue sin liberar.
  { unique_id: 105, Id: 105, Titulo: 0, Actividad: 'Muros pantalla eje 3', Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'M. Torres', Semanas_Inicio: 1, Ejecutado: 0.45, D_y_E: '66%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // liberated-control: todas las restricciones duras cumplidas.
  { unique_id: 106, Id: 106, Titulo: 0, Actividad: 'Mamposteria nivel 2', Sub_Contratista: 'Obra Blanca Ltda', Responsable_AIA: 'L. Marin', Semanas_Inicio: 2, Ejecutado: 0, D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // alert-2-3-weeks: alistamiento en riesgo.
  { unique_id: 107, Id: 107, Titulo: 0, Actividad: 'Redes hidrosanitarias piso 2', Sub_Contratista: 'Hidraulicos JR', Responsable_AIA: 'A. Pena', Semanas_Inicio: 3, Ejecutado: 0, D_y_E: '66%', Materiales: '33%', MdeO: '100%', Equipos: '100%', Predecesora: '50%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // alert-4-6-weeks: alistamiento pendiente.
  { unique_id: 108, Id: 108, Titulo: 0, Actividad: 'Instalacion electrica piso 3', Sub_Contratista: 'Electricos del Valle', Responsable_AIA: 'A. Pena', Semanas_Inicio: 5, Ejecutado: 0, D_y_E: '33%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '0%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  // neutral: fuera del horizonte de seis semanas, sin peldano de alerta.
  { unique_id: 109, Id: 109, Titulo: 0, Actividad: 'Fachada flotante oriente', Sub_Contratista: 'Fachadas Integrales', Responsable_AIA: 'L. Marin', Semanas_Inicio: 8, Ejecutado: 0, D_y_E: '0%', Materiales: '0%', MdeO: '33%', Equipos: '66%', Predecesora: '0%', Pdto_Cons: '0%', Modelo: '0%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
];

async function mockDeterministicData(page) {
  await page.route('**/api/general/restriction-config**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: false }),
  }));
  await page.route('**/programacion-intermedia/filtros', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }),
  }));
  await page.route('**/api/pi/list**', (route) => route.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS_DE_ESTADO }),
  }));
}

for (const scenario of VISUAL_SCENARIOS) {
  test(`${scenario.id} remains stable`, async ({ page }) => {
    await mockDeterministicData(page);
    await loginAndSelectProject(page, PROJECTS[0]);
    await page.setViewportSize(scenario.viewport);
    await page.evaluate((value) => localStorage.setItem('aia-theme', value), scenario.theme);
    await page.goto(scenario.route, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    await page.evaluate(() => document.fonts.ready);
    await expect(page.locator('#save-status')).toBeHidden();
    // Candado contra la regresion que creo esta task: si la grilla vuelve a quedar vacia, el
    // golden pasaria en verde retratando una tabla en blanco en vez de fallar. Se comprueba la
    // cuenta de filas sembradas, no solo que exista alguna.
    await expect
      .poll(() => page.locator('#hot-container .ht_master tbody tr').count())
      .toBe(FILAS_DE_ESTADO.length);
    await expect(page).toHaveScreenshot(
      path.basename(scenario.golden),
      {
        fullPage: false,
        // Mismo criterio que `programa-general.visual.mjs`: tope ABSOLUTO en vez de ratio. Con el
        // 0,2 % anterior (1.935 px aqui, 2.592 px a 1440x900) la red no mordia el bloqueo por falta
        // de Responsable AIA que ahora se siembra: quitar la marca «Falta Responsable AIA» mueve
        // 385 px en ambos viewports y el golden pasaba en verde. El ruido de renderizado medido
        // entre corridas sigue siendo cero, asi que los 100 px son holgura por si cambia la
        // maquina, no una necesidad del render actual.
        maxDiffPixels: 100,
      },
    );
  });
}
