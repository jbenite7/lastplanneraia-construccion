import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Los chips de filtro de /programa-general son neutros a proposito: el color lo
// lleva un punto indicador (`.indicator`), que es el canal de acento del nivel.
// En dark ese punto salia gris en los siete estados (2-11 % de saturacion) y
// «Atrasada» -el `urgent` del modulo- caia en #9c9c98, un gris neutro puro.
//
// La causa es de cascada, no de intencion: `--pg-*-border` se define en dark
// (public/css/programa-general.css) mezclando `--ds-color-state-*-bg`, y esos
// `*-bg` (public/css/tokens.css) siguen siendo los pasteles casi blancos del
// tema claro. Mezclar un casi-blanco con el borde da gris, y el matiz -la unica
// senal de prioridad que tiene el chip en reposo- se pierde entera.
//
// Este test mide el pixel real, no el texto CSS: cualquier reescritura de la
// formula que vuelva a apagar el matiz lo pone rojo.
// Ojo: en este chip la clase CSS (`alerta-restricciones`) y el `data-filter`
// (`con-alerta-restricciones`) no coinciden; es el unico de los siete asi.
const CHROMATIC_DOTS = [
  { filter: 'atrasada', level: 'urgent', hueRange: [340, 30] },
  { filter: 'con-alerta-restricciones', level: 'attention', hueRange: [30, 70] },
  { filter: 'debe-iniciar', level: 'attention', hueRange: [30, 70] },
  { filter: 'actividad-futura', level: 'healthy', hueRange: [110, 175] },
  { filter: 'en-curso', level: 'healthy', hueRange: [110, 195] },
];

// Los dos estados silenciosos del modulo (`Terminada`, `Sin Datos`) no llevan
// matiz por diseno, igual que `not-started` en /pdc. Se afirman neutros para
// que un cambio futuro no les asigne color por descuido.
const NEUTRAL_DOTS = ['terminada', 'sin-datos'];

const MIN_SATURATION = 35;
const MAX_NEUTRAL_SATURATION = 20;

function toHsl({ r, g, b }) {
  const [rn, gn, bn] = [r / 255, g / 255, b / 255];
  const max = Math.max(rn, gn, bn);
  const min = Math.min(rn, gn, bn);
  const lightness = (max + min) / 2;
  const delta = max - min;
  if (delta === 0) return { hue: 0, saturation: 0, lightness: lightness * 100 };
  const saturation = delta / (1 - Math.abs(2 * lightness - 1));
  let hue;
  if (max === rn) hue = 60 * (((gn - bn) / delta) % 6);
  else if (max === gn) hue = 60 * ((bn - rn) / delta + 2);
  else hue = 60 * ((rn - gn) / delta + 4);
  return {
    hue: (hue + 360) % 360,
    saturation: saturation * 100,
    lightness: lightness * 100,
  };
}

function hueWithin(hue, [start, end]) {
  return start <= end ? hue >= start && hue <= end : hue >= start || hue <= end;
}

function toHex({ r, g, b }) {
  return `#${[r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('')}`;
}

// El color se rasteriza en el navegador en lugar de parsearse aqui. Los tokens
// pueden llegar como `rgb()`, `color(srgb ...)` o `oklch(...)` segun como se
// derive cada uno, y un parser propio se vuelve un segundo motor de color que
// hay que mantener -y que ya fallo una vez con `oklch`-. Pintar sobre canvas
// sobre el fondo real del chip devuelve el sRGB que el ojo ve, sea cual sea el
// espacio de origen, y de paso resuelve el alfa sin componerlo a mano.
async function readLegendDots(page) {
  return page.evaluate(() => {
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
    const pageBackground = getComputedStyle(document.body).backgroundColor;
    return [...document.querySelectorAll('#pgLegend .pg-filter-chip')].map((chip) => {
      const dot = chip.querySelector('.indicator');
      const chipPainted = rasterize(getComputedStyle(chip).backgroundColor, pageBackground);
      const chipCss = `rgb(${chipPainted.r}, ${chipPainted.g}, ${chipPainted.b})`;
      return {
        filter: chip.dataset.filter,
        hasDot: Boolean(dot),
        painted: dot ? rasterize(getComputedStyle(dot).backgroundColor, chipCss) : null,
      };
    });
  });
}

test.describe('leyenda de Programa General', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend .pg-filter-chip').first()).toBeVisible({ timeout: 45000 });
  });

  test('cada punto cromatico conserva el matiz de su nivel', async ({ page }) => {
    const dots = await readLegendDots(page);
    const byFilter = new Map(dots.map((dot) => [dot.filter, dot]));

    for (const { filter, level, hueRange } of CHROMATIC_DOTS) {
      const entry = byFilter.get(filter);
      expect(entry, `falta el chip data-filter="${filter}"`).toBeTruthy();
      expect(entry.hasDot, `${filter} no tiene .indicator`).toBe(true);

      const { hue, saturation } = toHsl(entry.painted);
      const seen = `${toHex(entry.painted)} (hue ${hue.toFixed(0)}, sat ${saturation.toFixed(0)}%)`;

      expect(
        saturation,
        `${filter} (${level}) deberia leerse cromatico y sale ${seen}`,
      ).toBeGreaterThanOrEqual(MIN_SATURATION);

      expect(
        hueWithin(hue, hueRange),
        `${filter} (${level}) deberia caer en el matiz ${hueRange[0]}-${hueRange[1]} y sale ${seen}`,
      ).toBe(true);
    }
  });

  test('los estados silenciosos no reciben matiz', async ({ page }) => {
    const dots = await readLegendDots(page);
    const byFilter = new Map(dots.map((dot) => [dot.filter, dot]));

    for (const filter of NEUTRAL_DOTS) {
      const entry = byFilter.get(filter);
      expect(entry, `falta el chip data-filter="${filter}"`).toBeTruthy();
      const { saturation } = toHsl(entry.painted);
      expect(
        saturation,
        `${filter} deberia seguir neutro y sale ${toHex(entry.painted)} (sat ${saturation.toFixed(0)}%)`,
      ).toBeLessThanOrEqual(MAX_NEUTRAL_SATURATION);
    }
  });
});

// Nota deliberada: no hay un test aparte de "«Atrasada» y «Actividad Futura» se
// distinguen entre si". Comparar matices entre dos grises es ruido -con el bug
// presente esa comparacion pasaba, porque el hue de un gris es un numero sin
// significado-, y las bandas de `CHROMATIC_DOTS` ya son disjuntas: si cada punto
// cae en la suya, la separacion esta garantizada por construccion.
