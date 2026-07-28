import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Los cuatro modulos operativos tintan sus estados con la misma idea -el tono
// dice el nivel, la intensidad ordena dentro del nivel- pero cada uno la
// escribia por su cuenta. Siete de los ocho tintes de /programacion-intermedia
// eran el mismo hex que los de /programa-general, con la misma formula y los
// mismos porcentajes, en dos hojas distintas; /pdc mantenia siete hex propios.
//
// Este test fija la escalera compartida del design system por su valor
// resuelto, no por el texto del CSS: `color-mix` solo existe una vez que el
// motor lo calcula, y lo que hay que garantizar es el pixel.
//
// Los valores son los que la aplicacion ya pintaba antes de centralizarlos: la
// promocion no mueve nada. Si un paso cambia, este test lo dice.
const LADDER = {
  // Familia critical. Paso 1 es el mas intenso de la escalera compartida.
  '--ds-state-tint-red-1': '#562522',
  '--ds-state-tint-red-3': '#4a2723',
  '--ds-state-tint-red-6': '#3d2924',
  // Familia warning.
  '--ds-state-tint-amber-1': '#3e3714',
  '--ds-state-tint-amber-2': '#3a3616',
  '--ds-state-tint-amber-4': '#363418',
  '--ds-state-tint-amber-5': '#33331b',
  // Familia success.
  '--ds-state-tint-green-2': '#1e3e2c',
  '--ds-state-tint-green-5': '#1f392a',
  // Familia info.
  '--ds-state-tint-teal-2': '#134841',
  // Superficies silenciosas.
  '--ds-state-tint-neutral-quiet': '#1c2821',
  '--ds-state-tint-neutral-flat': '#1b231e',
  // Matices promovidos desde /pdc, ya verificados en contraste (8,88-10,99:1)
  // al tokenizarse a dark. Entran al sistema con su valor exacto.
  '--ds-state-tint-violet-pdc': '#33204a',
  '--ds-state-tint-red-pdc': '#431414',
  '--ds-state-tint-orange-pdc': '#452a0d',
  '--ds-state-tint-amber-pdc': '#3a3a0f',
  '--ds-state-tint-green-pdc': '#173d26',
  '--ds-state-tint-blue-pdc': '#17334f',
  '--ds-state-tint-neutral-pdc': '#2b2f2d',
};

// Cada token de modulo debe resolver al MISMO VALOR que su paso en la escalera.
//
// Lo que este guard puede y no puede ver: compara el valor calculado, asi que
// detecta cualquier DESVIACION -si alguien mueve un porcentaje o cambia la base
// de la mezcla, salta-, pero NO detecta la DUPLICACION, porque una copia fiel
// de la formula resuelve al mismo string que el token. Que la hoja del modulo
// no reescriba la formula lo verifica el test estatico
// tests/design-system/state-tint-ladder.test.mjs, que es donde leer el texto
// del CSS es la herramienta correcta.
const MODULE_BINDINGS = {
  '/programacion-intermedia': {
    '--pi-critical-bg': '--ds-state-tint-red-1',
    '--pi-overdue-bg': '--ds-state-tint-red-3',
    '--pi-exec-blocked-bg': '--ds-state-tint-red-6',
    '--pi-due-bg': '--ds-state-tint-amber-2',
    '--pi-alert1-bg': '--ds-state-tint-amber-4',
    '--pi-alert23-bg': '--ds-state-tint-amber-5',
    '--pi-alert46-bg': '--ds-state-tint-green-5',
    '--pi-control-bg': '--ds-state-tint-teal-2',
  },
  '/programa-general': {
    '--pg-critical-bg': '--ds-state-tint-red-1',
    '--pg-delayed-bg': '--ds-state-tint-red-3',
    '--pg-alert-bg': '--ds-state-tint-amber-1',
    '--pg-due-bg': '--ds-state-tint-amber-2',
    '--pg-restr-1-bg': '--ds-state-tint-amber-4',
    '--pg-restr-2-3-bg': '--ds-state-tint-amber-5',
    '--pg-future-bg': '--ds-state-tint-green-2',
    '--pg-restr-4-6-bg': '--ds-state-tint-green-5',
    '--pg-progress-bg': '--ds-state-tint-teal-2',
    '--pg-nodata-bg': '--ds-state-tint-neutral-quiet',
    '--pg-done-bg': '--ds-state-tint-neutral-flat',
  },
  '/pdc': {
    '--pdc-missing-bg': '--ds-state-tint-violet-pdc',
    '--pdc-critical-bg': '--ds-state-tint-red-pdc',
    '--pdc-delayed-bg': '--ds-state-tint-orange-pdc',
    '--pdc-completed-late-bg': '--ds-state-tint-amber-pdc',
    '--pdc-completed-ontime-bg': '--ds-state-tint-green-pdc',
    '--pdc-active-bg': '--ds-state-tint-blue-pdc',
    '--pdc-not-started-bg': '--ds-state-tint-neutral-pdc',
  },
};

// Se devuelven dos lecturas por token porque sirven a dos comparaciones
// distintas:
//
//   - `declared` es la cadena que el motor calcula (`rgb()`, `color(srgb ...)`
//     u `oklch()` segun como se derive cada token). Es exacta y sin redondeo,
//     asi que es la buena para comparar un token contra otro.
//   - `painted` es el pixel tras componer el alfa sobre el fondo de pagina
//     real. Es legible por humanos -un hex- pero pasa por el compositor de
//     canvas, que trabaja en 8 bits premultiplicados y puede desviarse una
//     unidad por canal respecto al calculo en flotante.
//
// Comparar hex rasterizados entre si arrastraria ese redondeo a una asercion
// de igualdad estricta; por eso el binding de modulos usa `declared`.
async function resolveTokens(page, names) {
  return page.evaluate((tokenNames) => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    const pageBackground = getComputedStyle(document.body).backgroundColor;
    const probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;left:-9999px;';
    document.body.appendChild(probe);

    const result = {};
    for (const name of tokenNames) {
      probe.style.backgroundColor = '';
      probe.style.backgroundColor = `var(${name})`;
      const declared = getComputedStyle(probe).backgroundColor;
      if (!declared || declared === 'rgba(0, 0, 0, 0)') {
        result[name] = null;
        continue;
      }
      ctx.fillStyle = pageBackground;
      ctx.fillRect(0, 0, 1, 1);
      ctx.fillStyle = declared;
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
      result[name] = { declared, painted: [r, g, b] };
    }
    probe.remove();
    return result;
  }, names);
}

function toHex([r, g, b]) {
  return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

function channelDrift(painted, expectedHex) {
  const expected = [1, 3, 5].map((i) => parseInt(expectedHex.slice(i, i + 2), 16));
  return Math.max(...painted.map((v, i) => Math.abs(v - expected[i])));
}

test.describe('escalera de tintes de estado', () => {
  test('el design system publica la escalera con los valores en uso', async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend').first()).toBeVisible({ timeout: 45000 });

    const resolved = await resolveTokens(page, Object.keys(LADDER));
    const missing = Object.keys(LADDER).filter((name) => resolved[name] === null);
    expect(missing, `la escalera no declara: ${missing.join(', ')}`).toEqual([]);

    // Tolerancia de una unidad por canal: el compositor de canvas redondea en
    // 8 bits premultiplicados y se desvia hasta 1 respecto al calculo exacto.
    // Lo que se fija aqui es el color, no la aritmetica del rasterizador.
    for (const [name, expected] of Object.entries(LADDER)) {
      const drift = channelDrift(resolved[name].painted, expected);
      expect(
        drift,
        `${name} deberia valer ${expected} y pinta ${toHex(resolved[name].painted)}`,
      ).toBeLessThanOrEqual(1);
    }
  });

  for (const [route, bindings] of Object.entries(MODULE_BINDINGS)) {
    test(`${route} resuelve sus tintes al mismo valor que la escalera`, async ({ page }) => {
      await page.setViewportSize(VIEWPORT);
      await loginAndSelectProject(page, project);
      await page.goto(route, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(3000);

      const names = [...Object.keys(bindings), ...new Set(Object.values(bindings))];
      const resolved = await resolveTokens(page, names);

      // Igualdad estricta sobre la cadena calculada, no sobre el hex pintado:
      // si el modulo consume el token de la escalera, el motor devuelve
      // literalmente el mismo valor y no hay redondeo que tolerar. Un modulo
      // que recalcule la formula por su cuenta cae aqui aunque el resultado se
      // parezca.
      for (const [moduleToken, ladderToken] of Object.entries(bindings)) {
        expect(
          resolved[moduleToken]?.declared,
          `${moduleToken} deberia resolver igual que ${ladderToken}`,
        ).toBe(resolved[ladderToken]?.declared);
      }
    });
  }
});
