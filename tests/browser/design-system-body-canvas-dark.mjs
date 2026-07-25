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
    }
  } finally {
    await logout(page).catch(() => {});
  }
});
