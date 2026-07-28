import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Los cuatro modulos operativos tintan sus estados con la misma idea -el matiz
// dice la identidad del estado, la intensidad ordena dentro del matiz- y desde
// la promocion de la escalera comparten un solo vocabulario.
//
// Lo que cambia en esta version: la escalera ya NO se deriva mezclando el tono
// semantico contra `--ds-active-surface-raised`. Esa superficie lleva alfa, asi
// que la mezcla arrastraba el gris del canvas y devolvia tintes pardos
// (`--ds-state-tint-red-1` era #562522, un rojo agrisado). Los siete tonos que
// /pdc habia elegido y medido a mano -#431414 y companeros- son limpios y
// saturados, y son los que el producto quiere. Pasan a ser las ANCLAS.
//
// Ocho familias x tres pasos. Los pasos 2 y 3 bajan CROMA con la luminosidad
// FIJA (`oklch(from ... l calc(c * k) h)`), no luminosidad:
//
//   #431414 esta en L=0,268 (OKLCH) y el fondo de pagina #111a15 en L=0,193.
//   Son 0,075 de margen: bajando luminosidad, al tercer paso el tinte se cae
//   dentro del canvas. Sobre oscuro el margen esta en el croma, que va de
//   C=0,073 a 0 sin tocar el fondo. Medido antes de escribir la escalera; no
//   cambiar el eje sin volver a medirlo.
//
// Este test fija la escalera por su valor RESUELTO, no por el texto del CSS:
// `oklch(from ...)` solo existe una vez que el motor lo calcula, y lo que hay
// que garantizar es el pixel.
const LADDER = {
  // Paso 1 = ancla, con el hex exacto que /pdc midio. Pasos 2 y 3, croma x0,6 y
  // x0,3 sobre la misma luminosidad y el mismo matiz.
  '--ds-state-tint-violet-1': '#33204a',
  '--ds-state-tint-violet-2': '#30253e',
  '--ds-state-tint-violet-3': '#2d2935',
  '--ds-state-tint-red-1': '#431414',
  '--ds-state-tint-red-2': '#391d1c',
  '--ds-state-tint-red-3': '#302221',
  '--ds-state-tint-orange-1': '#452a0d',
  '--ds-state-tint-orange-2': '#3d2d1e',
  '--ds-state-tint-orange-3': '#372f28',
  '--ds-state-tint-amber-1': '#3a3a0f',
  '--ds-state-tint-amber-2': '#393923',
  '--ds-state-tint-amber-3': '#38382e',
  '--ds-state-tint-green-1': '#173d26',
  '--ds-state-tint-green-2': '#253a2c',
  '--ds-state-tint-green-3': '#2d3730',
  '--ds-state-tint-blue-1': '#17334f',
  '--ds-state-tint-blue-2': '#233343',
  '--ds-state-tint-blue-3': '#2b323a',
  '--ds-state-tint-teal-1': '#134841',
  '--ds-state-tint-teal-2': '#2a4440',
  '--ds-state-tint-teal-3': '#35413f',
  // La familia neutral es la excepcion declarada: su ancla tiene C=0,007, o sea
  // no hay croma que gastar. Sus tres pasos bajan LUMINOSIDAD, que en una
  // familia acromatica es el unico eje disponible, y conservan los tres grises
  // que el producto ya pintaba (`not-started` de /pdc, `Sin Datos` y
  // `Terminada` de /programa-general).
  '--ds-state-tint-neutral-1': '#2b2f2d',
  '--ds-state-tint-neutral-2': '#1b2721',
  '--ds-state-tint-neutral-3': '#1b231e',
};

const FAMILIES = {
  violet: ['--ds-state-tint-violet-1', '--ds-state-tint-violet-2', '--ds-state-tint-violet-3'],
  red: ['--ds-state-tint-red-1', '--ds-state-tint-red-2', '--ds-state-tint-red-3'],
  orange: ['--ds-state-tint-orange-1', '--ds-state-tint-orange-2', '--ds-state-tint-orange-3'],
  amber: ['--ds-state-tint-amber-1', '--ds-state-tint-amber-2', '--ds-state-tint-amber-3'],
  green: ['--ds-state-tint-green-1', '--ds-state-tint-green-2', '--ds-state-tint-green-3'],
  blue: ['--ds-state-tint-blue-1', '--ds-state-tint-blue-2', '--ds-state-tint-blue-3'],
  teal: ['--ds-state-tint-teal-1', '--ds-state-tint-teal-2', '--ds-state-tint-teal-3'],
  neutral: ['--ds-state-tint-neutral-1', '--ds-state-tint-neutral-2', '--ds-state-tint-neutral-3'],
};
const CHROMATIC = Object.keys(FAMILIES).filter((family) => family !== 'neutral');

// Cada token de modulo debe resolver al MISMO VALOR que su paso en la escalera.
//
// Lo que este guard puede y no puede ver: compara el valor calculado, asi que
// detecta cualquier DESVIACION -si alguien cambia un multiplicador o el ancla,
// salta-, pero NO detecta la DUPLICACION, porque una copia fiel de la formula
// resuelve al mismo string que el token. Que la hoja del modulo no reescriba la
// formula lo verifica el test estatico
// tests/design-system/state-tint-ladder.test.mjs, que es donde leer el texto
// del CSS es la herramienta correcta.
//
// Los modulos que tenian mas usuarios que pasos colapsan de forma explicita:
// con tres pasos por familia, `exec-blocked` y `danger-soft` de Intermedia caen
// los dos en red-3, y `restr-1`/`restr-2-3` de General (alert1/alert23 en
// Intermedia) caen los dos en amber-3. En la escalera vieja esos pares ya
// distaban dE-OK 0,008, es decir eran el mismo color en pantalla.
const MODULE_BINDINGS = {
  '/programacion-intermedia': {
    '--pi-critical-bg': '--ds-state-tint-red-1',
    '--pi-overdue-bg': '--ds-state-tint-red-2',
    '--pi-exec-blocked-bg': '--ds-state-tint-red-3',
    '--pi-danger-soft-bg': '--ds-state-tint-red-3',
    '--pi-due-bg': '--ds-state-tint-amber-2',
    '--pi-alert1-bg': '--ds-state-tint-amber-3',
    '--pi-alert23-bg': '--ds-state-tint-amber-3',
    '--pi-alert46-bg': '--ds-state-tint-green-2',
    '--pi-ok-soft-bg': '--ds-state-tint-green-3',
    '--pi-control-bg': '--ds-state-tint-teal-1',
  },
  '/programa-general': {
    '--pg-critical-bg': '--ds-state-tint-red-1',
    '--pg-delayed-bg': '--ds-state-tint-red-2',
    '--pg-alert-bg': '--ds-state-tint-amber-1',
    '--pg-due-bg': '--ds-state-tint-amber-2',
    '--pg-restr-1-bg': '--ds-state-tint-amber-3',
    '--pg-restr-2-3-bg': '--ds-state-tint-amber-3',
    '--pg-future-bg': '--ds-state-tint-green-1',
    '--pg-restr-4-6-bg': '--ds-state-tint-green-2',
    '--pg-progress-bg': '--ds-state-tint-teal-1',
    '--pg-nodata-bg': '--ds-state-tint-neutral-2',
    '--pg-done-bg': '--ds-state-tint-neutral-3',
  },
  '/pdc': {
    '--pdc-missing-bg': '--ds-state-tint-violet-1',
    '--pdc-critical-bg': '--ds-state-tint-red-1',
    '--pdc-delayed-bg': '--ds-state-tint-orange-1',
    '--pdc-completed-late-bg': '--ds-state-tint-amber-1',
    '--pdc-completed-ontime-bg': '--ds-state-tint-green-1',
    '--pdc-active-bg': '--ds-state-tint-blue-1',
    '--pdc-not-started-bg': '--ds-state-tint-neutral-1',
  },
};

// Se devuelven dos lecturas por token porque sirven a dos comparaciones
// distintas:
//
//   - `declared` es la cadena que el motor calcula (`rgb()`, `color(srgb ...)`
//     u `oklch()` segun como se derive cada token). Es exacta y sin redondeo,
//     asi que es la buena para comparar un token contra otro y para leer matiz
//     y croma: en 8 bits el matiz de un color de croma bajo es ruido.
//   - `painted` es el pixel tras componer sobre el fondo de pagina real. Es
//     legible por humanos -un hex- pero pasa por el compositor de canvas, que
//     trabaja en 8 bits premultiplicados y puede desviarse una unidad por canal
//     respecto al calculo en flotante.
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

    const paint = (color) => {
      ctx.fillStyle = pageBackground;
      ctx.fillRect(0, 0, 1, 1);
      ctx.fillStyle = color;
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
      return [r, g, b];
    };

    const result = { __page: { declared: pageBackground, painted: paint(pageBackground) } };
    for (const name of tokenNames) {
      probe.style.backgroundColor = '';
      probe.style.backgroundColor = `var(${name})`;
      const declared = getComputedStyle(probe).backgroundColor;
      if (!declared || declared === 'rgba(0, 0, 0, 0)') {
        result[name] = null;
        continue;
      }
      result[name] = { declared, painted: paint(declared) };
    }
    probe.remove();
    return result;
  }, names);
}

// --- color: sRGB <-> OKLab/OKLCh -------------------------------------------
// Se convierte en Node y no en la pagina para poder leer `declared` con toda su
// precision: `oklch(0.268 0.0219 23.4)` llega en flotante y rasterizarlo a 8
// bits antes de medir el matiz destruiria justo lo que se quiere medir.
const linear = (c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);

function parseColor(declared) {
  const nums = (declared.match(/-?\d*\.?\d+(?:e-?\d+)?/g) ?? []).map(Number);
  if (declared.startsWith('oklch')) {
    const [L, C, H] = nums;
    const rad = (H * Math.PI) / 180;
    return { L, C, H, a: C * Math.cos(rad), b: C * Math.sin(rad) };
  }
  let rgb;
  if (declared.startsWith('color(')) rgb = nums.slice(0, 3);
  else rgb = nums.slice(0, 3).map((v) => v / 255);
  return oklabOf(rgb.map(linear));
}

function oklabOf([r, g, b]) {
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
  const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
  const a = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
  const bb = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
  let H = (Math.atan2(bb, a) * 180) / Math.PI;
  if (H < 0) H += 360;
  return { L, C: Math.hypot(a, bb), H, a, b: bb };
}

function oklabOfPainted([r, g, b]) {
  return oklabOf([r, g, b].map((v) => linear(v / 255)));
}

function deltaE(paintedA, paintedB) {
  const a = oklabOfPainted(paintedA);
  const b = oklabOfPainted(paintedB);
  return Math.hypot(a.L - b.L, a.a - b.a, a.b - b.b);
}

function luminance([r, g, b]) {
  const [rl, gl, bl] = [r, g, b].map((v) => linear(v / 255));
  return 0.2126 * rl + 0.7152 * gl + 0.0722 * bl;
}

function contrast(a, b) {
  const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
}

function hueGap(a, b) {
  const raw = Math.abs(a - b) % 360;
  return raw > 180 ? 360 - raw : raw;
}

function toHex([r, g, b]) {
  return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

function channelDrift(painted, expectedHex) {
  const expected = [1, 3, 5].map((i) => parseInt(expectedHex.slice(i, i + 2), 16));
  return Math.max(...painted.map((v, i) => Math.abs(v - expected[i])));
}

// Umbrales.
//
// El brief pedia >=3:1 contra el fondo de pagina y >=1,3:1 entre vecinos, en
// contraste WCAG. Medido, ninguna de las dos cifras es alcanzable por un tinte
// oscuro sobre un canvas oscuro: la escalera ENTERA -la vieja y la nueva- vive
// entre 1,10:1 y 1,72:1 contra #111a15, y dos pasos que solo se diferencian en
// croma quedan por debajo de 1,02:1 entre si porque el contraste WCAG es ciego
// al croma (ver el informe de la tarea con las 24 medidas). Pedir 3:1 dejaria
// el guard rojo para siempre y pedir 1,3:1 entre vecinos seria incompatible con
// derivar por croma, que es el eje que la propia tarea fija.
//
// Lo que se exige en su lugar mide la MISMA propiedad -que el paso se separe
// del canvas y de su vecino- con una metrica que sobre oscuro si funciona:
// distancia perceptual en OKLab. Los suelos van por debajo de lo medido pero
// muy por encima de cero, asi que fallan si alguien acerca un paso a su vecino
// (con k=0,8 en vez de 0,6 el dE23 cae a ~0,008) o hunde un tinte en el canvas.
const MIN_DELTA_E_VS_CANVAS = 0.03; // medido: 0,040 (neutral-3) a 0,164 (teal-1)
const MIN_DELTA_E_NEIGHBOUR = 0.012; // medido: 0,016 a 0,040
// Los tintes se emparejan con `--ds-active-text-primary` en la capa de matiz
// (states-feedback.css / legacy-bridge.css). Ese par SI tiene un umbral WCAG
// real y alcanzable, y es el que impide que un paso se vuelva demasiado claro.
const MIN_TEXT_CONTRAST = 4.5; // medido: 9,8 a 15,3
const MAX_HUE_DRIFT_DEG = 2;
const MAX_CHROMA_RATIO = 0.8; // cada paso baja croma al menos un 20%
const MAX_LIGHTNESS_DRIFT = 0.004;
const MAX_NEUTRAL_CHROMA = 0.03;

test.describe('escalera de tintes de estado', () => {
  test('el design system publica las ocho anclas con sus tres pasos', async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend').first()).toBeVisible({ timeout: 45000 });

    const names = Object.keys(LADDER);
    expect(names.length, 'ocho familias por tres pasos').toBe(24);
    const resolved = await resolveTokens(page, names);
    const missing = names.filter((name) => resolved[name] === null);
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

  test('cada paso se separa del canvas, de su vecino y sostiene el texto', async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend').first()).toBeVisible({ timeout: 45000 });

    const names = Object.keys(LADDER);
    const resolved = await resolveTokens(page, [...names, '--ds-active-text-primary']);
    const canvas = resolved.__page.painted;
    const text = resolved['--ds-active-text-primary'];
    expect(text, 'la pagina debe publicar --ds-active-text-primary').not.toBeNull();

    let checkedSteps = 0;
    let checkedPairs = 0;
    for (const [family, steps] of Object.entries(FAMILIES)) {
      expect(steps.length, `${family} debe tener tres pasos`).toBe(3);
      for (const step of steps) {
        expect(resolved[step], `${step} no resuelve`).not.toBeNull();
        const painted = resolved[step].painted;
        expect(
          deltaE(painted, canvas),
          `${step} (${toHex(painted)}) se hunde en el canvas ${toHex(canvas)}`,
        ).toBeGreaterThanOrEqual(MIN_DELTA_E_VS_CANVAS);
        expect(
          contrast(text.painted, painted),
          `el texto primario sobre ${step} (${toHex(painted)}) no llega a AA`,
        ).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
        checkedSteps += 1;
      }
      for (let i = 0; i < steps.length - 1; i += 1) {
        const [a, b] = [steps[i], steps[i + 1]];
        expect(
          deltaE(resolved[a].painted, resolved[b].painted),
          `${a} y ${b} pintan casi lo mismo: ${toHex(resolved[a].painted)} vs ${toHex(resolved[b].painted)}`,
        ).toBeGreaterThanOrEqual(MIN_DELTA_E_NEIGHBOUR);
        checkedPairs += 1;
      }
    }
    expect(checkedSteps, 'se midieron los 24 pasos').toBe(24);
    expect(checkedPairs, 'se midieron los 16 pares de vecinos').toBe(16);
  });

  test('los pasos bajan croma sin mover matiz ni luminosidad', async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#pgLegend').first()).toBeVisible({ timeout: 45000 });

    const resolved = await resolveTokens(page, Object.keys(LADDER));

    expect(CHROMATIC.length, 'siete familias cromaticas').toBe(7);
    for (const family of CHROMATIC) {
      const steps = FAMILIES[family];
      const lch = steps.map((step) => {
        expect(resolved[step], `${step} no resuelve`).not.toBeNull();
        return parseColor(resolved[step].declared);
      });
      const anchor = lch[0];
      for (let i = 1; i < lch.length; i += 1) {
        // El matiz se lee del valor declarado a proposito: derivar por
        // `color-mix(in srgb, ...)` hacia un gris -que es lo que hacia la
        // escalera vieja- desplaza el tono varios grados, y eso es exactamente
        // lo que este limite tiene que cazar.
        expect(
          hueGap(lch[i].H, anchor.H),
          `${steps[i]} se fue a ${lch[i].H.toFixed(1)}° y su ancla esta en ${anchor.H.toFixed(1)}°`,
        ).toBeLessThanOrEqual(MAX_HUE_DRIFT_DEG);
        expect(
          Math.abs(lch[i].L - anchor.L),
          `${steps[i]} movio la luminosidad (${lch[i].L.toFixed(4)} vs ${anchor.L.toFixed(4)}): el eje es el croma`,
        ).toBeLessThanOrEqual(MAX_LIGHTNESS_DRIFT);
        expect(
          lch[i].C / lch[i - 1].C,
          `${steps[i]} apenas baja croma respecto a ${steps[i - 1]} (${lch[i].C.toFixed(4)} vs ${lch[i - 1].C.toFixed(4)})`,
        ).toBeLessThanOrEqual(MAX_CHROMA_RATIO);
      }
    }

    // La familia acromatica no tiene matiz que conservar: lo que hay que
    // garantizar es lo contrario -que ninguno de sus pasos se vuelva de color-
    // y que el eje que si usa, la luminosidad, sea monotono.
    const neutral = FAMILIES.neutral.map((step) => parseColor(resolved[step].declared));
    for (let i = 0; i < neutral.length; i += 1) {
      expect(
        neutral[i].C,
        `${FAMILIES.neutral[i]} dejo de ser neutro (C=${neutral[i].C.toFixed(4)})`,
      ).toBeLessThanOrEqual(MAX_NEUTRAL_CHROMA);
      if (i > 0) {
        expect(
          neutral[i].L,
          `${FAMILIES.neutral[i]} no baja luminosidad respecto a ${FAMILIES.neutral[i - 1]}`,
        ).toBeLessThan(neutral[i - 1].L);
      }
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
          resolved[ladderToken]?.declared,
          `${ladderToken} no resuelve en ${route}`,
        ).toBeTruthy();
        expect(
          resolved[moduleToken]?.declared,
          `${moduleToken} deberia resolver igual que ${ladderToken}`,
        ).toBe(resolved[ladderToken]?.declared);
      }
    });
  }
});
