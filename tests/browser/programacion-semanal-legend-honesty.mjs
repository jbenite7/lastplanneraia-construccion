import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// La leyenda de /programacion-semanal es un filtro: cada chip promete el color
// que van a tener las filas que selecciona. Cuando la muestra no coincide con la
// fila, la leyenda miente sobre la grilla.
//
// Dos defectos que este test cierra:
//
//   1. `programacion-semanal.css` agrupaba `.ps-alert-high` y `.ps-alert-medium`
//      en la misma regla de dark y les daba a ambos `--ps-high-bg`. En la fila,
//      en cambio, `medium` usa `--ps-medium-bg`. Los dos chips salian identicos
//      y ninguno de los dos correspondia a lo que el usuario veria al filtrar.
//
//   2. La leyenda venia de `--aia-*` (paleta clara) y la fila de `--ps-*`
//      (oscura), asi que ademas de valores distintos eran vocabularios
//      distintos. En claro `high` era NARANJA y `medium` AMBAR; los tokens de
//      fila hicieron a los dos ambar y se perdio la distincion de matiz.
//
// Lo que la leyenda promete es el MATIZ, no el valor exacto: por decision de
// diseno el chip usa el paso intenso de su familia y la fila el tenue, porque
// aqui se tine la fila completa en una grilla densa. Asi que la correspondencia
// que hay que exigir es de familia -mismo tono- y no de hex.
//
// El test no fija ningun valor: lee los dos y compara. Sigue valiendo cuando la
// escalera cambie.
const LEGEND_TO_ROW = {
  'ps-alert-critical-route': '--ps-critical-bg',
  'ps-alert-high': '--ps-high-bg',
  'ps-alert-medium': '--ps-medium-bg',
  'ps-alert-control': '--ps-control-bg',
};

// `Trabajo No Planificado` no tiene regla de fila propia y ademas pertenece a la
// fase de Calificacion: la leyenda solo renderiza la fase activa, asi que en la
// fase por defecto (Programacion) no esta en el DOM. Se mide si aparece.
const LEGEND_ONLY = ['ps-alert-tnp'];

// Las que la fase Programacion siempre renderiza. `ps-alert-medium` aparece dos
// veces -«Condiciones Pendientes» y «Por Comprometer» comparten clase-, asi que
// las clases distintas son cuatro.
const ALWAYS_PRESENT = Object.keys(LEGEND_TO_ROW);

// Umbral de "no son la misma muestra", no una afirmacion perceptual. Con el
// defecto presente, dos de los cinco daban 0.
const MIN_CHANNEL_SEPARATION = 10;

function toHex([r, g, b]) {
  return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

function separation(a, b) {
  return Math.max(...a.map((v, i) => Math.abs(v - b[i])));
}

function hue([r, g, b]) {
  const [rn, gn, bn] = [r / 255, g / 255, b / 255];
  const max = Math.max(rn, gn, bn);
  const delta = max - Math.min(rn, gn, bn);
  if (delta === 0) return null; // gris: no tiene matiz que comparar
  let h;
  if (max === rn) h = 60 * (((gn - bn) / delta) % 6);
  else if (max === gn) h = 60 * ((bn - rn) / delta + 2);
  else h = 60 * ((rn - gn) / delta + 4);
  return (h + 360) % 360;
}

function hueDistance(a, b) {
  const [ha, hb] = [hue(a), hue(b)];
  if (ha === null || hb === null) return null;
  const raw = Math.abs(ha - hb);
  return Math.min(raw, 360 - raw);
}

// Margen para el matiz entre chip y fila: son dos intensidades de la misma
// familia, y a estas luminancias tan bajas el matiz medido en sRGB se mueve unos
// grados por el redondeo de 8 bits.
const MAX_HUE_DRIFT = 25;

async function readLegendAndRowTints(page, legendClasses, rowTokens) {
  return page.evaluate(({ classes, tokens }) => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    const pageBackground = getComputedStyle(document.body).backgroundColor;
    const paint = (color) => {
      ctx.fillStyle = pageBackground;
      ctx.fillRect(0, 0, 1, 1);
      ctx.fillStyle = color;
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
      return [r, g, b];
    };

    const legend = {};
    for (const cls of classes) {
      const chip = document.querySelector(`#psAlertsLegend .${cls}`);
      legend[cls] = chip ? paint(getComputedStyle(chip).backgroundColor) : null;
    }

    // El tinte de fila se lee del token que la regla de fila consume, en el
    // ambito de la pagina: construir una fila de Handsontable falsa exigiria
    // duplicar el id #hot-container.
    const scope = document.querySelector('.ps-page') || document.body;
    const probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;left:-9999px;';
    scope.appendChild(probe);
    const row = {};
    for (const token of tokens) {
      probe.style.backgroundColor = '';
      probe.style.backgroundColor = `var(${token})`;
      const declared = getComputedStyle(probe).backgroundColor;
      row[token] = declared && declared !== 'rgba(0, 0, 0, 0)' ? paint(declared) : null;
    }
    probe.remove();

    return { legend, row };
  }, { classes: legendClasses, tokens: rowTokens });
}

test.describe('leyenda de Programación Semanal', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programacion-semanal', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#psAlertsLegend .pdc-legend-item').first())
      .toBeVisible({ timeout: 45000 });
  });

  test('cada muestra de la leyenda comparte familia con la fila que filtra', async ({ page }) => {
    const classes = [...Object.keys(LEGEND_TO_ROW), ...LEGEND_ONLY];
    const { legend, row } = await readLegendAndRowTints(page, classes, Object.values(LEGEND_TO_ROW));

    for (const [cls, token] of Object.entries(LEGEND_TO_ROW)) {
      expect(legend[cls], `no se encontró el chip .${cls} en la leyenda`).toBeTruthy();
      expect(row[token], `${token} no resuelve en la página`).toBeTruthy();

      const drift = hueDistance(legend[cls], row[token]);
      expect(
        drift,
        `.${cls} pinta ${toHex(legend[cls])} en la leyenda y ${toHex(row[token])} en la fila; `
        + 'uno de los dos es gris y no se puede comparar la familia',
      ).not.toBeNull();
      expect(
        drift,
        `.${cls} promete la familia de ${toHex(legend[cls])} en la leyenda pero su fila pinta `
        + `${toHex(row[token])} (${token}), a ${drift?.toFixed(0)}° de distancia`,
      ).toBeLessThanOrEqual(MAX_HUE_DRIFT);
    }
  });

  test('las muestras de la fase se distinguen entre sí', async ({ page }) => {
    const classes = [...ALWAYS_PRESENT, ...LEGEND_ONLY];
    const { legend } = await readLegendAndRowTints(page, classes, []);

    // Las de la fase activa tienen que estar; `tnp` se mide solo si aparece.
    for (const cls of ALWAYS_PRESENT) {
      expect(legend[cls], `la fase Programación debería renderizar .${cls}`).toBeTruthy();
    }
    const present = classes.filter((cls) => legend[cls]);

    for (let i = 0; i < present.length; i += 1) {
      for (let j = i + 1; j < present.length; j += 1) {
        const [a, b] = [present[i], present[j]];
        expect(
          separation(legend[a], legend[b]),
          `.${a} y .${b} pintan casi lo mismo: ${toHex(legend[a])} vs ${toHex(legend[b])}`,
        ).toBeGreaterThanOrEqual(MIN_CHANNEL_SEPARATION);
      }
    }
  });
});
