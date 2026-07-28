import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Dos superficies de /programacion-intermedia que ningun barrido en reposo ve:
//
//  - El **tooltip de ayuda** solo existe mientras el puntero esta encima. Lleva
//    el naranja corporativo a proposito, asi que aqui no se vigila el matiz sino
//    que su cuerpo y su titulo sigan legibles sobre el.
//  - La **celda de estado `neutral`** (fila sin clasificar) solo aparece con la
//    tabla cargada y con datos que la produzcan. Su pareja fondo/texto vivia en
//    hex crudo -una isla clara dentro de una tabla ya oscura-.
//
// El guard mide el pixel compuesto sobre la cadena real de fondos, no el texto
// del CSS: comprueba que la superficie pertenece al tema oscuro y que el texto
// alcanza AA, sin congelar que token concreto lo consigue.
const MAX_SURFACE_LUMINANCE = 0.18;
const MIN_TEXT_RATIO = 4.5;

const COLOR_HELPERS = () => {
  const rasterize = (color, backdrop) => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = backdrop;
    ctx.fillRect(0, 0, 1, 1);
    ctx.fillStyle = color;
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
    return { r, g, b };
  };
  const hex = ({ r, g, b }) =>
    '#' + [r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('');
  const luminance = ({ r, g, b }) => {
    const channel = (v) => {
      const n = v / 255;
      return n <= 0.03928 ? n / 12.92 : ((n + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
  };
  const contrast = (a, b) => {
    const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (hi + 0.05) / (lo + 0.05);
  };
  const isOpaque = (color) => {
    const parts = color.match(/rgba?\(([^)]+)\)/);
    if (!parts) return color !== 'transparent';
    const values = parts[1].split(',').map((v) => parseFloat(v));
    return values.length < 4 || values[3] >= 0.999;
  };
  const paintOf = (element) => {
    const style = getComputedStyle(element);
    const image = style.backgroundImage;
    if (image && image !== 'none') {
      const stop = image.match(/(rgba?\([^)]*\)|#[0-9a-f]{3,8}|color\([^)]*\)|oklch\([^)]*\))/i);
      if (stop) return stop[1];
    }
    return style.backgroundColor;
  };
  // Compone hacia abajo desde el primer ancestro opaco: sin esto un alfa
  // devuelve una ratio inventada contra el color heredado.
  const backdropOf = (element) => {
    const chain = [];
    let node = element.parentElement;
    while (node) {
      const paint = paintOf(node);
      chain.push(paint);
      if (paint && paint !== 'none' && isOpaque(paint) && !paint.includes('rgba(0, 0, 0, 0)')) break;
      node = node.parentElement;
    }
    let painted = rasterize(getComputedStyle(document.body).backgroundColor, '#000000');
    for (let i = chain.length - 1; i >= 0; i -= 1) {
      painted = rasterize(chain[i], `rgb(${painted.r}, ${painted.g}, ${painted.b})`);
    }
    return painted;
  };
  const measure = (element) => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    const backdrop = backdropOf(element);
    const background = rasterize(paintOf(element), `rgb(${backdrop.r}, ${backdrop.g}, ${backdrop.b})`);
    const foreground = rasterize(style.color, `rgb(${background.r}, ${background.g}, ${background.b})`);
    return {
      visible: rect.width > 0 && rect.height > 0,
      background: hex(background),
      foreground: hex(foreground),
      backgroundLuminance: luminance(background),
      textRatio: contrast(foreground, background),
    };
  };
  return { measure };
};

test.describe('superficies con estado abierto de Programacion Intermedia en dark', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programacion-intermedia', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#piLegend .pdc-legend-item').first()).toBeVisible({ timeout: 60000 });
    await expect(page.locator('#hot-container .handsontable td').first()).toBeVisible({ timeout: 60000 });
  });

  test('el tooltip de ayuda se lee sobre el naranja corporativo', async ({ page }) => {
    // Se dispara por evento, no por `.hover()`: el disparador puede quedar bajo
    // la cabecera fija y la accionabilidad de Playwright nunca resuelve.
    await page.evaluate(() => {
      const $ = window.jQuery;
      const el = Array.from(document.querySelectorAll('.pi-help-trigger'))
        .find((n) => n.getBoundingClientRect().width > 0);
      if (el && $) { $(el).trigger('mouseenter'); $(el).trigger('click'); }
    });
    await expect(page.locator('.tooltip-inner')).toBeVisible({ timeout: 15000 });

    const readings = await page.evaluate((helpersSrc) => {
      const { measure } = new Function('return (' + helpersSrc + ')')()();
      const inner = document.querySelector('.tooltip-inner');
      const h6 = inner.querySelector('h6');
      return {
        body: { label: 'cuerpo del tooltip', ...measure(inner) },
        title: h6 ? { label: 'titulo del tooltip', ...measure(h6) } : null,
      };
    }, COLOR_HELPERS.toString());

    for (const sample of [readings.body, readings.title].filter(Boolean)) {
      expect(sample.visible, `${sample.label} mide 0x0: el tooltip no llego a abrirse`).toBe(true);
      expect(
        sample.textRatio,
        `${sample.label} da ${sample.textRatio.toFixed(2)}:1 con ${sample.foreground} sobre ${sample.background}`,
      ).toBeGreaterThanOrEqual(MIN_TEXT_RATIO);
    }
  });

  test('ninguna celda de estado es una isla clara y todas alcanzan AA', async ({ page }) => {
    // `neutral` no siempre esta en los datos del proyecto. Se aplica la misma
    // cadena de clases que emite `hot.js` (`pi-row-state pi-state-<estado>`)
    // sobre una celda real de la tabla para que la regla se evalue en su sitio.
    const readings = await page.evaluate((helpersSrc) => {
      const { measure } = new Function('return (' + helpersSrc + ')')()();
      const states = [
        'blocked-overdue-critical', 'blocked-overdue', 'blocked-due',
        'alert-1-week', 'alert-2-3-weeks', 'alert-4-6-weeks',
        'execution-blocked', 'liberated-control', 'neutral',
      ];
      const host = document.querySelector('#hot-container .handsontable tbody td');
      if (!host) return { missingHost: true };
      const original = host.className;
      const out = [];
      for (const state of states) {
        host.className = 'pi-row-state pi-state-' + state;
        host.textContent = host.textContent || 'x';
        out.push({ label: 'celda ' + state, ...measure(host) });
      }
      host.className = original;
      return { out };
    }, COLOR_HELPERS.toString());

    expect(readings.missingHost, 'la tabla no llego a renderizar ninguna celda').toBeFalsy();

    for (const sample of readings.out) {
      expect(
        sample.backgroundLuminance,
        `${sample.label} pinta ${sample.background}, que no pertenece al tema oscuro`,
      ).toBeLessThanOrEqual(MAX_SURFACE_LUMINANCE);
      expect(
        sample.textRatio,
        `${sample.label} da ${sample.textRatio.toFixed(2)}:1 con ${sample.foreground} sobre ${sample.background}`,
      ).toBeGreaterThanOrEqual(MIN_TEXT_RATIO);
    }
  });
});
