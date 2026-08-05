import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const VIEWPORT = { width: 1180, height: 820 };

// Regresion guard para el hallazgo Critical de la Tarea 3 (commit 8bb6f1b):
// el sed `s/html\.aia-theme-dark body\.dark-mode/html.aia-theme-dark/g` borro
// `body` como sujeto de la regla en contratos.css y listado-actividades.css,
// dejando la regla inerte sobre <html> y el <body> cayendo al fallback claro
// de styles.css (--surface-bg: #f5f5f7). El test de
// tests/design-system/dead-theme-removal.test.mjs solo verifica la ausencia
// de `.dark-mode` en el CSS: una regla que pierde su sujeto o su condicion de
// tema no deja rastro de `.dark-mode` y ese test estatico pasa igual. Este
// test cierra ese hueco midiendo el color real en el navegador.
//
// Las dos rutas del hallazgo original -/contratos y /listado-actividades- ya no
// existen: `569ae903` («retirar superficies deprecadas listado-actividades y
// contratos») las saco de public/index.php y hoy responden 404. Estuvieron aqui
// esperandose en oscuro hasta el 2026-08-04, cuando el paso a `expect.soft` de
// los guards de color dejo de abortar el bucle en la primera y las hizo
// visibles a las dos. Se retiran: un guard que navega a una ruta inexistente no
// mide tema, mide routing, y ya hay suites para eso.
//
// El fondo esperado no es uniforme: las rutas con shell (.pg-page/.ps-page/
// .pi-page) usan --ds-color-bg-page-dark (#111a15); las demas caen al canvas
// generico --ds-color-bg-canvas-dark (#0b100d), que es exactamente el valor que
// la regla rota de Finding 1 dejaba de aplicar.
const EXPECTED_BODY_BACKGROUND = {
  '/programa-general': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .pg-page
  '/programacion-semanal': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .ps-page
  '/programacion-intermedia': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .pi-page
  // Las cinco de abajo son las superficies claras que F1 ataca (spec F1-styles-css.md /
  // plan F1-styles-css.plan.md, Task 1). Hoy caen al fallback claro de styles.css
  // (--surface-bg: #f5f5f7 -> body rgb(245,245,247)) porque su @layer base resuelve como
  // module.base y gana a components del DS (styles.css:26,36,104). Ninguna tiene hoy CSS
  // propio que pinte `body` en oscuro: pdc.css no declara `body {}` y las otras cuatro no
  // tienen hoja de módulo. El valor esperado es el canvas oscuro genérico
  // --ds-color-bg-canvas-dark (rgb(11, 16, 13)), porque ninguna de estas cinco usa una
  // clase de "page" con su propio --ds-color-bg-page-dark (no son
  // .pg-page/.ps-page/.pi-page). Task 3 del plan F1
  // remapea --surface-bg a var(--ds-active-bg-canvas), que es justo ese token.
  '/indicadores': 'rgb(11, 16, 13)',
  '/profesionales': 'rgb(11, 16, 13)',
  '/subcontratistas': 'rgb(11, 16, 13)',
  '/control-cambios': 'rgb(11, 16, 13)',
};

// `/programa-general`, `/programacion-semanal` y `/programacion-intermedia`
// pintan el body via `background: var(--ds-active-bg-page)` en una regla SIN
// condicion de tema (programa-general.css:24, programacion-semanal.css:12-13,
// programacion-intermedia.css:41). EXPECTED_BODY_BACKGROUND pasa en esas tres
// rutas solo porque <html> siempre lleva `.aia-theme-dark`: no prueba nada
// sobre las reglas SI condicionadas por tema. Esas tres rutas si definen sus
// custom properties de color de estado bajo un selector con la condicion de
// tema (`html.aia-theme-dark .pg-page` / `html.aia-theme-dark body.ps-page` /
// `html.aia-theme-dark .pi-page`); este bloque cierra el hueco leyendo el
// valor calculado real de esa custom property (no solo que no este vacia).
// `.pg-page`/`.pi-page` son clases del propio <body> (ver
// views/programa-general/programa_general.view.php:30 y
// views/programacion-intermedia/programacion_intermedia.view.php:15), asi
// que se leen desde `document.body` igual que el fondo.
const EXPECTED_STATE_TOKEN = {
  '/programa-general': {
    property: '--pg-critical-bg',
    // public/css/programa-general.css, bajo `html.aia-theme-dark .pg-page`.
    // Consume `--ds-state-tint-red`, que desde la reconstruccion de la
    // escalera es el ancla de /pdc en vez de una mezcla contra una superficie
    // con alfa (antes: color-mix(in srgb, #8f1d1d 48%, rgba(35, 48, 41, 0.86) 52%)).
    value: '#431414',
  },
  '/programacion-semanal': {
    property: '--ps-critical-bg',
    // public/css/programacion-semanal.css, bajo `html.aia-theme-dark body.ps-page`.
    // El commit 9f6de25 movio este tinte a la escalera compartida y no actualizo
    // esta expectativa, que quedo obsoleta. El valor de abajo es el que declara
    // hoy la hoja, no una relajacion del test. La reconstruccion de la escalera
    // no lo movio: el ancla roja ya era este #431414.
    value: 'color-mix(in srgb, #431414 46%, rgba(28, 36, 31, 0.92) 54%)',
  },
  '/programacion-intermedia': {
    property: '--pi-critical-bg',
    // public/css/programacion-intermedia.css, bajo `html.aia-theme-dark .pi-page`.
    // Mismo ancla que /programa-general.
    value: '#431414',
  },
};

test('el body de cada ruta de la Tarea 3 usa su fondo oscuro, no el fallback claro', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const [route, expectedBackground] of Object.entries(EXPECTED_BODY_BACKGROUND)) {
      const response = await page.goto(route, { waitUntil: 'load' });
      // `soft` + `continue` por lo mismo que el fondo: una ruta que no responde
      // abortaba el bucle y dejaba sin medir las que venian detras.
      expect.soft(response?.status(), `${route} must respond`).toBeLessThan(400);
      if ((response?.status() ?? 500) >= 400) continue;
      const background = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
      // `soft` a proposito: durante F1 varias rutas estan en rojo a la vez y se van
      // apagando tramo a tramo. Con un expect duro el test aborta en la primera y no
      // se puede leer el progreso; con soft, cada corrida lista TODAS las que faltan.
      expect.soft(
        background,
        `${route}: body.backgroundColor debe ser el token dark (${expectedBackground}), no un valor claro`,
      ).toBe(expectedBackground);

      const stateToken = EXPECTED_STATE_TOKEN[route];
      if (stateToken) {
        const tokenValue = await page.evaluate(
          (propertyName) => getComputedStyle(document.body).getPropertyValue(propertyName).trim(),
          stateToken.property,
        );
        expect.soft(
          tokenValue,
          `${route}: ${stateToken.property} debe resolver al valor dark declarado (${stateToken.value}), ` +
            'no quedar vacio ni con un valor distinto (p. ej. si la regla condicionada por tema pierde su prefijo)',
        ).toBe(stateToken.value);
      }
    }
  } finally {
    await logout(page).catch(() => {});
  }
});

// Hallazgo posterior a la Tarea 3 (commit efabf46): este archivo mide solo
// `document.body`, y las cinco rutas de EXPECTED_BODY_BACKGROUND ya dan
// verde ahi. Pero el body es el lienzo exterior; la grilla Handsontable que
// cubre casi toda la pantalla en /pdc, /profesionales y /subcontratistas vive
// en su propio contenedor (`#hot-container`, o `#dt_cliente` en /pdc) con su
// propio fondo CSS, sin heredar el del body. Medido en vivo a 1180x820 dark,
// logueado como test.A en un proyecto con project_id activo. La comparacion es
// sobre el contenedor, no sobre una celda de datos, lo cual ademas es correcto:
// una celda puede llevar legitimamente un tinte de estado, por ejemplo
// `.pdc-header` en rgb(139, 64, 17) o `.pdc-missing-data` en rgb(233, 213, 255)
// en /pdc; ninguna de las dos es un bug, y afirmar "ninguna celda es blanca"
// habria sido una asercion invalida. El contenedor no tiene `style` inline con
// background (solo height/width/overflow), asi que su color viene integramente
// de la cascada CSS y es un punto de medicion real, no vacuo.
//
// El bloque llevaba tambien /contratos y /listado-actividades -la primera como
// referencia OK-, retiradas el 2026-08-04 por el mismo motivo que arriba: las
// rutas ya no existen desde `569ae903`.
//
// Los dos rojos deliberados que documentaba este comentario (/profesionales y
// /subcontratistas con `#hot-container` en rgb(255, 255, 255)) YA NO ESTAN: el
// 404 de /contratos abortaba el bucle antes de llegar a ellas, asi que el
// comentario describia una medicion que llevaba tiempo sin repetirse. Con el
// bucle completo las tres rutas vivas dan verde.
const EXPECTED_GRID_SURFACE_BACKGROUND = {
  '/profesionales': { selector: '#hot-container', background: 'rgb(17, 26, 21)' },
  '/subcontratistas': { selector: '#hot-container', background: 'rgb(17, 26, 21)' },
};

test('la superficie de la grilla Handsontable (no el body) usa fondo oscuro en cada ruta', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const [route, expected] of Object.entries(EXPECTED_GRID_SURFACE_BACKGROUND)) {
      const response = await page.goto(route, { waitUntil: 'load' });
      // Igual que arriba: el 404 de una ruta no puede dejar ciegas a las demas.
      expect.soft(response?.status(), `${route} must respond`).toBeLessThan(400);
      if ((response?.status() ?? 500) >= 400) continue;
      await page.waitForSelector(expected.selector, { state: 'attached', timeout: 15000 });
      // `attached` solo garantiza que el nodo existe. Handsontable monta su
      // superficie de forma asincrona y, mientras no lo ha hecho, el contenedor
      // devuelve el blanco del vendor: leer ahi produce un rojo intermitente en
      // /pdc que no corresponde a ninguna regla mal puesta. Se espera a que la
      // grilla este montada. Esto NO enmascara los rojos deliberados de
      // /profesionales y /subcontratistas: ahi el blanco lo pinta el CSS y sigue
      // siendo blanco despues de montar.
      await page
        .waitForFunction(
          (selector) => {
            const el = document.querySelector(selector);
            return Boolean(el?.querySelector('.handsontable, .ht_master, table'));
          },
          expected.selector,
          { timeout: 15000 },
        )
        .catch(() => {});
      const background = await page.evaluate((selector) => {
        const el = document.querySelector(selector);
        return el ? getComputedStyle(el).backgroundColor : null;
      }, expected.selector);
      // `soft`, igual que el test de arriba: /profesionales y /subcontratistas estan
      // en rojo hoy a proposito (grilla blanca sobre canvas oscuro); un expect duro
      // ocultaria la segunda ruta rota detras de la primera.
      expect.soft(
        background,
        `${route}: el fondo de ${expected.selector} (la superficie de la grilla, no una celda) debe ser el ` +
          `token dark (${expected.background}); un valor claro es una grilla blanca flotando sobre el canvas oscuro`,
      ).toBe(expected.background);
    }
  } finally {
    await logout(page).catch(() => {});
  }
});
