import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';

test('los KPI del control tower no muestran la unidad cruda "count"', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/bi/control-tower`);
  await page.waitForLoadState('networkidle');
  const texto = await page.locator('body').innerText();
  expect(texto).not.toMatch(/\bcount\b/i);
});

test('los KPI de BI siguen mostrando las unidades legitimas', async ({ page }) => {
  // El sandbox emite `count` en todos los KPI, asi que la unidad legitima se inyecta: si no, no
  // habria nada que afirmar y el test solo cubriria la mitad negativa, que es el hueco que tapa.
  // El scorecard llega embebido en el HTML de la propia navegacion (script#bi-data), no por un
  // fetch XHR a /api/bi/control-tower: se intercepta la navegacion, no una llamada de API.
  await page.route('**/bi/control-tower**', async (route) => {
    const respuesta = await route.fetch();
    let cuerpo = await respuesta.text();
    cuerpo = cuerpo.replace(
      /(<script id="bi-data" type="application\/json">)([\s\S]*?)(<\/script>)/,
      (match, pre, json, post) => {
        try {
          const datos = JSON.parse(json);
          if (Array.isArray(datos.scorecard) && datos.scorecard.length >= 2) {
            datos.scorecard[0] = { ...datos.scorecard[0], value: 87, unit: '%' };
            datos.scorecard[1] = { ...datos.scorecard[1], value: 12, unit: 'count' };
          }
          return pre + JSON.stringify(datos) + post;
        } catch (_err) {
          return match;
        }
      }
    );
    await route.fulfill({ response: respuesta, body: cuerpo });
  });

  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/bi/control-tower`);
  await page.waitForLoadState('networkidle');

  // La unidad legitima se ve...
  await expect(page.locator('#kpi-ppc')).toHaveText('87 %');
  // ...y la interna sigue sin verse, sobre el mismo payload.
  await expect(page.locator('#kpi-programadas')).toHaveText('12');
});
