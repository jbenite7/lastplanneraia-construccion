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
// El fondo esperado no es uniforme: las rutas con shell (.pg-page/.ps-page/
// .pi-page) usan --ds-color-bg-page-dark (#111a15); contratos y
// listado-actividades estilan `body` directo con --ds-color-bg-canvas-dark
// (#0b100d), que es exactamente el valor que la regla rota de Finding 1
// dejaba de aplicar.
const EXPECTED_BODY_BACKGROUND = {
  '/programa-general': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .pg-page
  '/programacion-semanal': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .ps-page
  '/programacion-intermedia': 'rgb(17, 26, 21)', // --ds-color-bg-page-dark via .pi-page
  '/contratos': 'rgb(11, 16, 13)', // --ds-color-bg-canvas-dark via `html.aia-theme-dark body`
  '/listado-actividades': 'rgb(11, 16, 13)', // --ds-color-bg-canvas-dark via `html.aia-theme-dark body`
  // Las cinco de abajo son las superficies claras que F1 ataca (spec F1-styles-css.md /
  // plan F1-styles-css.plan.md, Task 1). Hoy caen al fallback claro de styles.css
  // (--surface-bg: #f5f5f7 -> body rgb(245,245,247)) porque su @layer base resuelve como
  // module.base y gana a components del DS (styles.css:26,36,104). Ninguna tiene hoy CSS
  // propio que pinte `body` en oscuro: pdc.css no declara `body {}` y las otras cuatro no
  // tienen hoja de módulo. El valor esperado es el canvas oscuro genérico
  // --ds-color-bg-canvas-dark (rgb(11, 16, 13)), el mismo que /contratos y
  // /listado-actividades, porque ninguna de estas cinco usa una clase de "page" con su
  // propio --ds-color-bg-page-dark (no son .pg-page/.ps-page/.pi-page). Task 3 del plan F1
  // remapea --surface-bg a var(--ds-active-bg-canvas), que es justo ese token.
  '/pdc': 'rgb(11, 16, 13)',
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
    // public/css/programa-general.css:29, bajo `html.aia-theme-dark .pg-page`
    value: 'color-mix(in srgb, #8f1d1d 48%, rgba(35, 48, 41, 0.86) 52%)',
  },
  '/programacion-semanal': {
    property: '--ps-critical-bg',
    // public/css/programacion-semanal.css:18, bajo `html.aia-theme-dark body.ps-page`
    value: 'color-mix(in srgb, #8f1d1d 24%, rgba(28, 36, 31, 0.92) 76%)',
  },
  '/programacion-intermedia': {
    property: '--pi-critical-bg',
    // public/css/programacion-intermedia.css:46, bajo `html.aia-theme-dark .pi-page`
    value: 'color-mix(in srgb, #8f1d1d 48%, rgba(35, 48, 41, 0.86) 52%)',
  },
};

test('el body de cada ruta de la Tarea 3 usa su fondo oscuro, no el fallback claro', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const [route, expectedBackground] of Object.entries(EXPECTED_BODY_BACKGROUND)) {
      const response = await page.goto(route, { waitUntil: 'load' });
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
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
        expect(
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
// cubre casi toda la pantalla en /contratos, /pdc, /profesionales,
// /subcontratistas y /listado-actividades vive en su propio contenedor
// (`#hot-container`, o `#dt_cliente` en /pdc) con su propio fondo CSS, sin
// heredar el del body. Medido en vivo a 1180x820 dark, logueado como test.A
// en un proyecto con project_id activo (Da Porto u otro: contratos/
// listado-actividades no tienen filas seed en ningun proyecto disponible,
// asi que la comparacion es sobre el contenedor, no sobre una celda de
// datos, lo cual ademas es correcto: una celda puede llevar legitimamente
// un tinte de estado, por ejemplo `.pdc-header` en rgb(139, 64, 17) o
// `.pdc-missing-data` en rgb(233, 213, 255) en /pdc; ninguna de las dos es
// un bug, y afirmar "ninguna celda es blanca" habria sido una asercion
// invalida). El contenedor no tiene `style` inline con background (solo
// height/width/overflow), asi que su color viene integramente de la
// cascada CSS y es un punto de medicion real, no vacuo:
//   /contratos            #hot-container -> rgb(17, 26, 21)   (referencia: OK)
//   /listado-actividades  #hot-container -> rgb(17, 26, 21)   (OK)
//   /pdc                  #dt_cliente    -> rgba(28, 36, 31, 0.92) (OK, superficie "glass" ya oscura)
//   /profesionales        #hot-container -> rgb(255, 255, 255) (ROTO: contenedor blanco)
//   /subcontratistas      #hot-container -> rgb(255, 255, 255) (ROTO: contenedor blanco)
// En /profesionales y /subcontratistas el `body` que las envuelve ya es
// oscuro (rgb(11, 16, 13), la misma EXPECTED_BODY_BACKGROUND de arriba) y
// las celdas de datos (`td`) tambien miden blanco solido, pero el
// contenedor es el nivel donde el token deberia aplicarse de forma estable
// sin depender de si hay filas cargadas -- el arreglo (una tarea posterior
// de F1 sobre `@layer components`, o F6 tokenizando
// handsontable-module.css) vive fuera de este archivo; este test solo
// deja el hueco visible.
const EXPECTED_GRID_SURFACE_BACKGROUND = {
  '/contratos': { selector: '#hot-container', background: 'rgb(17, 26, 21)' },
  '/listado-actividades': { selector: '#hot-container', background: 'rgb(17, 26, 21)' },
  '/pdc': { selector: '#dt_cliente', background: 'rgba(28, 36, 31, 0.92)' },
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
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
      await page.waitForSelector(expected.selector, { state: 'attached', timeout: 15000 });
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
