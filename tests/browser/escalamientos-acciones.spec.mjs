import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';
import { SANDBOX_LOCAL, razonStackDistinto, sqlEnApp } from './support/pdc-sandbox.mjs';

/**
 * El dashboard de escalamientos no tiene malla Handsontable, y el cajón contextual solo sabía leer
 * «la fila activa» de una malla. Sin ella, `getActiveRowData()` devolvía null y con eso caía en
 * cadena la detección de crisis —que es la que muestra las tarjetas de acción y cierre—, el SOS y
 * el cierre formal. La pantalla existe para atender crisis y no dejaba atender ninguna.
 *
 * Estos tests cubren la superficie SIN malla. Las tres que sí la tienen se cubren en
 * `full-app-flow.spec.mjs`; aquí solo se comprueba que no se les cambió el contrato.
 */

const PROYECTO = 'PDC Sandbox E2E';
const ALERTA_ID = 999_001;
const PROYECTO_ID = 990_100;

function sembrarAlerta(modulo = 'PG') {
  sqlEnApp(
    `$db->query("DELETE FROM lps_escalamientos WHERE id = ${ALERTA_ID}");`
    + `$db->query("INSERT INTO lps_escalamientos`
    + ` (project_id, id, proyecto_id, semana, consecutivo_en_programa, unique_id, modulo,`
    + ` trigger_origen, nivel_actual, estado)`
    + ` VALUES (${PROYECTO_ID}, ${ALERTA_ID}, ${PROYECTO_ID}, 1, 1, 1, '${modulo}', 'SOS-DIR', 1, 'Activo')");`,
  );
}

function borrarAlerta() {
  sqlEnApp(`$db->query("DELETE FROM lps_escalamientos WHERE id = ${ALERTA_ID}");`);
}

test.beforeEach(async ({ page }) => {
  test.skip(!SANDBOX_LOCAL, 'La alerta se siembra por «docker compose exec» contra el MySQL local.');
  const razon = razonStackDistinto();
  test.skip(razon !== null, razon ?? '');
  sembrarAlerta();

  // El modo simulación desvía el SOS al portapapeles y nunca llama a la API: sin fijarlo, el spec
  // dependería de lo que hubiera dejado en localStorage la última sesión manual.
  await page.addInitScript(() => window.localStorage.setItem('lps_simulated_mode', 'false'));
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent(PROYECTO)}`);
  await page.goto(`${BASE_URL}/dashboard/escalamientos`);
  await page.waitForLoadState('networkidle');
});

test.afterAll(() => {
  if (!SANDBOX_LOCAL || razonStackDistinto() !== null) return;
  borrarAlerta();
});

async function abrirPrimeraCrisis(page) {
  await page.locator('[onclick*="openLpsDrawer"]').first().click();
  await expect(page.locator('#lps_consecutivo')).toHaveText(/Actividad #/);
}

/**
 * La tarjeta solo RELLENA el cajón; abrirlo es un segundo gesto, sobre el disparador del sidebar.
 * Sin esto sus botones existen pero no son visibles, y Playwright espera a un elemento que nadie va
 * a mostrar.
 */
async function desplegarCajon(page) {
  await page.locator('#lps_sidebar_trigger').click();
  await expect(page.locator('#lps_drawer')).toHaveClass(/open/);
}

test('abrir una tarjeta de crisis deja el cajón operativo, con sus tarjetas de acción y cierre', async ({ page }) => {
  // Antes del arreglo ambas quedaban ocultas: sin fila activa no se detectaba la crisis.
  await expect(page.locator('#lps_closure_card')).toBeHidden();

  await abrirPrimeraCrisis(page);

  await expect(page.locator('#lps_closure_card')).toBeVisible();
  await expect(page.locator('#lps_action_card')).toBeVisible();
});

test('el SOS registra el escalamiento contra el módulo en que nació la crisis', async ({ page }) => {
  const registros = [];
  await page.route('**/api/lps/crisis/register', async (route) => {
    registros.push(route.request().postData() ?? '');
    await route.fulfill({ json: { respuesta: 'OK' } });
  });

  await abrirPrimeraCrisis(page);
  await desplegarCajon(page);
  await page.locator('#lps_btn_email').click();

  await expect.poll(() => registros.length).toBe(1);
  // 'PG' es el módulo de la alerta sembrada. Deducirlo de la superficie activa daría 'PS', porque
  // el dashboard no es ninguno de los tres módulos de programación: los reúne a todos.
  expect(registros[0]).toContain('PG');
});

test('mitigar la crisis envía el cierre y refresca la superficie', async ({ page }) => {
  let cierreRecibido = null;
  await page.route('**/api/lps/crisis/close', async (route) => {
    cierreRecibido = route.request().postData() ?? '';
    await route.fulfill({ json: { respuesta: 'OK' } });
  });

  await abrirPrimeraCrisis(page);
  await desplegarCajon(page);

  // `fill` dispara el evento `input`, que es lo que habilita el botón al pasar los 100 caracteres.
  await page.locator('#lps_closure_justification').fill(
    'Cierre de prueba automatizado: se comprueba que la mitigacion viaja al servidor con su '
    + 'justificacion y que la superficie de origen se entera para refrescarse.',
  );
  await expect(page.locator('#lps_btn_close_crisis')).toBeEnabled();

  // El adaptador sin malla traduce la escritura de `alerta_crisis` en una recarga del dashboard.
  await Promise.all([
    page.waitForLoadState('load'),
    page.locator('#lps_btn_close_crisis').click(),
  ]);

  expect(cierreRecibido).toContain(String(ALERTA_ID));
});
