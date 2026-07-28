import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// El cuerpo del modal «Guia Operativa» de /programa-general lo inyecta
// `renderLegendModal()` (public/js/modules/programa_general/hot.js) al abrirlo:
// cerrado mide 0x0 y ningun barrido en reposo lo ve. Su CSS vivia en
// `public/css/styles.css` con la paleta clara -tarjetas blancas y textos
// slate- dentro de una superficie ya oscura.
//
// Este guard mide el pixel compuesto, no el texto CSS: rasteriza cada color
// sobre la cadena real de fondos (incluidos gradientes y alfas) y exige que el
// modal siga leyendose en dark y con contraste AA.
const TEXT_SAMPLES = [
  { label: 'intro', selector: '.pg-legend-quick-intro' },
  { label: 'titulo de grupo', selector: '.pg-legend-quick-group-title' },
  { label: 'nombre de estado', selector: '.pg-legend-quick-state strong' },
  { label: 'descripcion de estado', selector: '.pg-legend-quick-state small' },
  { label: 'accion recomendada', selector: '.pg-legend-quick-action' },
  { label: 'intro de alertas', selector: '.pg-legend-quick-alert-intro' },
  { label: 'codigo de alerta', selector: '.pg-legend-quick-alert-item strong' },
  { label: 'detalle de alerta', selector: '.pg-legend-quick-alert-item small' },
  { label: 'badge P1', selector: '.pg-legend-quick-badge.is-p1' },
  { label: 'badge P2', selector: '.pg-legend-quick-badge.is-p2' },
  { label: 'badge P3', selector: '.pg-legend-quick-badge.is-p3' },
  { label: 'prioridad P1', selector: '.pg-legend-quick-priority.is-p1' },
];

// Superficies que no deben volver a ser islas claras dentro del modal oscuro.
const SURFACE_SAMPLES = [
  { label: 'cabecera', selector: '.pg-legend-quick-header' },
  { label: 'tarjeta de grupo', selector: '.pg-legend-quick-group' },
  { label: 'caja de alertas', selector: '.pg-legend-quick-alerts' },
  { label: 'tarjeta de alerta', selector: '.pg-legend-quick-alert-item' },
];

// Umbral de «esto es una superficie oscura». El canvas del tema ronda 0,015 de
// luminancia relativa; las tarjetas claras que habia rondaban 1,0. Se deja
// holgado a proposito para no congelar un tono concreto: lo que se vigila es
// que la superficie pertenezca al tema oscuro, no cual de sus tokens usa.
const MAX_SURFACE_LUMINANCE = 0.18;
const MIN_TEXT_RATIO = 4.5;

async function readLegendModal(page, { texts, surfaces }) {
  return page.evaluate(({ texts, surfaces }) => {
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

    // Un gradiente no aparece en `backgroundColor`: se muestrea su primera
    // parada, que es la que cae bajo el texto de la cabecera.
    const paintOf = (element) => {
      const style = getComputedStyle(element);
      const image = style.backgroundImage;
      if (image && image !== 'none') {
        const stop = image.match(/(rgba?\([^)]*\)|#[0-9a-f]{3,8}|color\([^)]*\)|oklch\([^)]*\))/i);
        if (stop) return stop[1];
      }
      return style.backgroundColor;
    };

    const isOpaque = (color) => {
      const parts = color.match(/rgba?\(([^)]+)\)/);
      if (!parts) return color !== 'transparent';
      const values = parts[1].split(',').map((v) => parseFloat(v));
      return values.length < 4 || values[3] >= 0.999;
    };

    // Compone hacia abajo desde el primer ancestro opaco: sin esto un alfa o un
    // fondo transparente devuelve una ratio inventada contra el color heredado.
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

    const measure = ({ label, selector }) => {
      const element = document.querySelector(selector);
      if (!element) return { label, selector, missing: true };
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      const backdrop = backdropOf(element);
      const background = rasterize(paintOf(element), `rgb(${backdrop.r}, ${backdrop.g}, ${backdrop.b})`);
      const foreground = rasterize(style.color, `rgb(${background.r}, ${background.g}, ${background.b})`);
      return {
        label,
        selector,
        visible: rect.width > 0 && rect.height > 0,
        background: hex(background),
        foreground: hex(foreground),
        backgroundLuminance: luminance(background),
        textRatio: contrast(foreground, background),
      };
    };

    return {
      texts: texts.map(measure),
      surfaces: surfaces.map(measure),
    };
  }, { texts, surfaces });
}

test.describe('modal de leyenda de Programa General en dark', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend .pg-filter-chip').first()).toBeVisible({ timeout: 45000 });
    await page.locator('.leyenda_colores').first().click();
    // El cuerpo se inyecta al abrir; esperar a un nodo del contenido -no al
    // contenedor- evita medir el modal vacio.
    await expect(page.locator('.pg-legend-quick-group').first()).toBeVisible({ timeout: 15000 });
  });

  test('ninguna superficie del modal es una isla clara', async ({ page }) => {
    const { surfaces } = await readLegendModal(page, {
      texts: TEXT_SAMPLES,
      surfaces: SURFACE_SAMPLES,
    });

    for (const surface of surfaces) {
      expect(surface.missing, `no se encontro ${surface.selector}`).toBeFalsy();
      expect(surface.visible, `${surface.label} mide 0x0: el modal no llego a abrirse`).toBe(true);
      expect(
        surface.backgroundLuminance,
        `${surface.label} (${surface.selector}) pinta ${surface.background}, que no pertenece al tema oscuro`,
      ).toBeLessThanOrEqual(MAX_SURFACE_LUMINANCE);
    }
  });

  test('todo el texto del modal alcanza AA sobre su fondo real', async ({ page }) => {
    const { texts } = await readLegendModal(page, {
      texts: TEXT_SAMPLES,
      surfaces: SURFACE_SAMPLES,
    });

    for (const sample of texts) {
      expect(sample.missing, `no se encontro ${sample.selector}`).toBeFalsy();
      expect(sample.visible, `${sample.label} mide 0x0: el modal no llego a abrirse`).toBe(true);
      expect(
        sample.textRatio,
        `${sample.label} (${sample.selector}) da ${sample.textRatio.toFixed(2)}:1 con ${sample.foreground} sobre ${sample.background}`,
      ).toBeGreaterThanOrEqual(MIN_TEXT_RATIO);
    }
  });
});
