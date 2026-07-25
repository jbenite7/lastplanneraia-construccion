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
      expect(
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
