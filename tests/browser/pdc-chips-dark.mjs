import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Tres chips de /pdc se quedaron en la paleta clara cuando el resto del modulo
// paso a oscuro: los dos indicadores de desviacion del cronograma
// (`N dias de adelanto` / `N dias de retraso`, que el renderer inyecta en la
// celda de estado) y el badge de fecha de corte que el modulo crea en JS.
// Sobre el canvas oscuro se leian como parches de tema claro.
//
// Vivian con hex literal en public/css/styles.css, que es el punto ciego del
// gate: existe un budget `pdc` con `hardcoded-hex: 0` pero cubre
// public/css/pdc.css, no styles.css, que no esta en ningun budget.
//
// El test inyecta los elementos en vez de esperar que los datos los produzcan:
// `--ahead` solo aparece con actividades adelantadas y el badge solo si hay
// semana en contexto, asi que depender del fixture volveria el guard
// intermitente. Lo que se verifica es lo que el CSS produce para esas clases
// sobre esta pagina, que es exactamente la pregunta.
const CHIPS = [
  { name: '.pdc-delta--delay', html: '<span class="pdc-delta pdc-delta--delay">63 días de retraso</span>' },
  { name: '.pdc-delta--ahead', html: '<span class="pdc-delta pdc-delta--ahead">7 días de adelanto</span>' },
  { name: '#ctxPdcCalcDate', html: '<div id="ctxPdcCalcDate">Corte <span class="pdc-calc-date">12/07</span></div>' },
];

// El canvas de /pdc esta por debajo de 0,1 de luminancia relativa. Un chip
// oscuro tiene que quedarse del lado oscuro de la escala; 0,25 deja margen para
// los tintes mas intensos (`--ds-state-tint-red-1` ronda 0,03) sin admitir un
// pastel claro, que arranca por encima de 0,7.
const MAX_BACKGROUND_LUMINANCE = 0.25;
const MIN_CONTRAST = 4.5;

function relativeLuminance([r, g, b]) {
  const channel = (value) => {
    const v = value / 255;
    return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

function contrastRatio(a, b) {
  const [light, dark] = [relativeLuminance(a), relativeLuminance(b)].sort((x, y) => y - x);
  return (light + 0.05) / (dark + 0.05);
}

function toHex([r, g, b]) {
  return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

// Se rasteriza en canvas por lo mismo que en state-tint-ladder.mjs: los tokens
// llegan como rgb(), color(srgb ...) u oklch() segun como se derive cada uno.
async function measureChips(page, chips) {
  return page.evaluate((specs) => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    const pageBackground = getComputedStyle(document.body).backgroundColor;
    const paint = (color, backdrop) => {
      ctx.fillStyle = backdrop;
      ctx.fillRect(0, 0, 1, 1);
      ctx.fillStyle = color;
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
      return [r, g, b];
    };

    const host = document.createElement('div');
    host.style.cssText = 'position:absolute;left:-9999px;top:0;';
    document.body.appendChild(host);

    const results = {};
    for (const { name, html } of specs) {
      host.innerHTML = html;
      const el = host.firstElementChild;
      const style = getComputedStyle(el);
      const background = paint(style.backgroundColor, pageBackground);
      results[name] = {
        background,
        // El texto se compone sobre el fondo ya pintado del propio chip.
        foreground: paint(style.color, `rgb(${background.join(', ')})`),
      };
    }
    host.remove();
    return results;
  }, chips);
}

test('los chips de /pdc se pintan en oscuro', async ({ page }) => {
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project);
  await page.goto('/pdc', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pdc-legend .pdc-legend-item').first()).toBeVisible({ timeout: 45000 });

  const measured = await measureChips(page, CHIPS);

  for (const { name } of CHIPS) {
    const { background, foreground } = measured[name];
    const seen = `fondo ${toHex(background)}, texto ${toHex(foreground)}`;

    expect(
      relativeLuminance(background),
      `${name} deberia tener fondo oscuro y tiene ${seen}`,
    ).toBeLessThanOrEqual(MAX_BACKGROUND_LUMINANCE);

    expect(
      contrastRatio(background, foreground),
      `${name} deberia superar ${MIN_CONTRAST}:1 y tiene ${seen}`,
    ).toBeGreaterThanOrEqual(MIN_CONTRAST);
  }
});
