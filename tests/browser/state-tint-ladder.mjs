import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// Los cuatro modulos operativos tintan sus estados con el mismo vocabulario: el
// MATIZ dice cual estado es y se pinta en el fondo; el NIVEL dice que hacer y
// cuando y se pinta en el acento.
//
// EL VOCABULARIO ES NOMINAL. Hubo una version con tres pasos por familia,
// derivados del ancla bajando croma con la luminosidad fija. Medida aqui mismo,
// la separacion maxima entre dos pasos consecutivos de una misma familia era
// 1,012:1 de contraste y dE-OK 0,0168 -por debajo del umbral de percepcion-: no
// eran tres colores, era uno. Y como los modulos tenian mas estados que pasos,
// dos entradas de leyenda que el usuario filtra por separado acababan pintando
// fondos bit-identicos.
//
// Sobre este canvas no hay eje de intensidad que gastar. El contraste WCAG es
// ciego al croma, asi que bajar croma no separa; y bajar luminosidad tampoco
// tiene recorrido, porque el ancla mas oscura (#431414) esta en L=0,268 OKLCH y
// el fondo de pagina (#111a15) en L=0,207: 0,061 de margen. Queda un solo eje
// util, el matiz, y de ahi la regla del contrato: un matiz = un estado, y
// ningun modulo puede tener dos estados que compartan matiz.
//
// Este test fija la paleta por su valor RESUELTO, no por el texto del CSS.
const PALETTE = {
  violet: '#33204a',
  red: '#431414',
  orange: '#452a0d',
  amber: '#3a3a0f',
  green: '#173d26',
  blue: '#17334f',
  teal: '#134841',
  // El unico acromatico. No es un tinte apagado sino el matiz del silencio, y
  // su hex sale de `not-started` de /pdc igual que los otros siete.
  neutral: '#2b2f2d',
};
const HUES = Object.keys(PALETTE);
const CHROMATIC = HUES.filter((hue) => hue !== 'neutral');
const tintOf = (hue) => `--ds-state-tint-${hue}`;

// Cada estado de leyenda con su matiz. Esta tabla ES la reasignacion: si alguien
// devuelve dos estados de un mismo modulo al mismo matiz, el bloque de
// unicidad de mas abajo lo caza midiendo el pixel, no leyendo el nombre.
//
// /pdc ya cumplia («un matiz por estado») y no se toca. Intermedia y General se
// reasignaron: repetian matiz -Intermedia tenia tres rojos y tres ambares,
// General dos ambares y dos neutros- y con la paleta reducida a anclas eso son
// fondos identicos.
const MODULE_LEGENDS = {
  '/programacion-intermedia': {
    '--pi-critical-bg': 'red', // RC inicio vencido: bloqueado y vencido
    '--pi-overdue-bg': 'orange', // Inicio vencido: fuera de plazo
    '--pi-due-bg': 'violet', // Inicio por Habilitar: le faltan condiciones
    '--pi-alert1-bg': 'amber', // Alistamiento Urgente: por resolver
    '--pi-alert23-bg': 'teal', // Alistamiento en Riesgo: contexto
    '--pi-alert46-bg': 'neutral', // Alistamiento Pendiente: silencio
    '--pi-exec-blocked-bg': 'blue', // En Ejecucion Pendiente: en marcha
    '--pi-control-bg': 'green', // Listo para Comprometer: controlado
  },
  '/programa-general': {
    '--pg-delayed-bg': 'red', // Atrasada
    '--pg-due-bg': 'orange', // Debe Iniciar
    '--pg-alert-bg': 'amber', // Con Alerta Restricciones
    '--pg-future-bg': 'green', // Actividad Futura
    '--pg-progress-bg': 'blue', // En Curso
    '--pg-done-bg': 'neutral', // Terminada
    '--pg-nodata-bg': 'violet', // Sin Datos
  },
  '/pdc': {
    '--pdc-critical-bg': 'red',
    '--pdc-delayed-bg': 'orange',
    '--pdc-completed-late-bg': 'amber',
    '--pdc-completed-ontime-bg': 'green',
    '--pdc-active-bg': 'blue',
    '--pdc-missing-bg': 'violet',
    '--pdc-not-started-bg': 'neutral',
  },
};

// Alias declarados: no son estados de leyenda sino otra superficie del mismo
// estado (o una superficie auxiliar que reusa su matiz). Se verifican igual
// -tienen que resolver al ancla que dicen- pero quedan fuera de la asercion de
// unicidad, porque compartir color con su estado es justo lo que deben hacer.
const MODULE_ALIASES = {
  '/programacion-intermedia': {
    '--pi-danger-soft-bg': 'red',
    '--pi-ok-soft-bg': 'green',
  },
  '/programa-general': {
    '--pg-critical-bg': 'red',
    '--pg-notrequired-bg': 'neutral',
    '--pg-ontime-bg': 'blue',
    '--pg-restr-0-bg': 'amber',
    '--pg-restr-1-bg': 'amber',
    '--pg-restr-2-3-bg': 'amber',
    '--pg-restr-4-6-bg': 'amber',
  },
  '/pdc': {},
};

// Se devuelven dos lecturas por token porque sirven a dos comparaciones
// distintas:
//
//   - `declared` es la cadena que el motor calcula. Es exacta y sin redondeo,
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
// precision: un color de croma bajo rasterizado a 8 bits pierde justo el matiz
// que se quiere medir.
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

// Umbrales. Todos van por debajo de lo medido pero cerca, para que midan algo:
// un umbral escrito para acomodar la medicion no discrimina nada.
//
// El contraste WCAG NO sirve para separar dos tintes entre si sobre oscuro
// -mide luminancia relativa y toda la paleta vive entre 1,10:1 y 1,72:1 contra
// el canvas-, asi que la separacion se mide en distancia perceptual OKLab, que
// sobre oscuro si discrimina croma. WCAG se usa donde si es la metrica
// correcta: tinta sobre tinte.
const MIN_DELTA_E_VS_CANVAS = 0.06; // medido: 0,094 (neutral) a 0,164 (teal)
const MIN_DELTA_E_BETWEEN_HUES = 0.035; // medido: minimo ~0,049 (amber vs green)
const MIN_TEXT_CONTRAST = 4.5; // AA. Medido con --ds-active-text-primary: 9,8 a 15,3
const MIN_TINTED_TEXT_CONTRAST = 7; // AAA. Medido con el texto tintado: 8,88 a 10,99
const MIN_HUE_SEPARATION_DEG = 20; // medido: minimo 28,1° (green vs teal)
const MIN_CHROMA_RATIO_VS_NEUTRAL = 4; // medido: 8,5 (teal 0,0560 / neutral 0,0066)

// El catalogo empareja cada ancla con el texto tintado con el que /pdc midio su
// contraste. Son SIETE para OCHO anclas: teal entro al design system con esta
// paleta -no venia de /pdc- y no trajo texto propio. Se declara aqui para que
// el hueco sea una asercion y no un olvido.
const HUE_WITHOUT_TINTED_TEXT = 'teal';

async function contractHues() {
  const semantics = JSON.parse(await readFile('docs/design-system/state-semantics.json', 'utf8'));
  return semantics.hues;
}

async function openProgramaGeneral(page) {
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#pgLegend').first()).toBeVisible({ timeout: 45000 });
}

test.describe('paleta de matices de estado', () => {
  test('el design system publica los ocho matices con su ancla exacta', async ({ page }) => {
    await openProgramaGeneral(page);

    const names = HUES.map(tintOf);
    expect(names.length, 'ocho matices, uno por estado').toBe(8);
    const resolved = await resolveTokens(page, names);
    const missing = names.filter((name) => resolved[name] === null);
    expect(missing, `la paleta no declara: ${missing.join(', ')}`).toEqual([]);

    // Tolerancia de una unidad por canal: el compositor de canvas redondea en
    // 8 bits premultiplicados y se desvia hasta 1 respecto al calculo exacto.
    // `soft` de aqui en adelante en los bucles de este archivo: cada matiz (y
    // cada par de matices) es una medicion independiente, y con aserciones
    // duras una sola desviacion esconde a todas las demas. Ningun umbral se
    // relaja: lo unico que cambia es cuantos fallos reporta una corrida.
    for (const [hue, expected] of Object.entries(PALETTE)) {
      const drift = channelDrift(resolved[tintOf(hue)].painted, expected);
      expect.soft(
        drift,
        `${tintOf(hue)} deberia valer ${expected} y pinta ${toHex(resolved[tintOf(hue)].painted)}`,
      ).toBeLessThanOrEqual(1);
    }
  });

  test('los ocho se separan del canvas, entre si, y sostienen el texto', async ({ page }) => {
    await openProgramaGeneral(page);

    const names = HUES.map(tintOf);
    const resolved = await resolveTokens(page, [...names, '--ds-active-text-primary']);
    const canvas = resolved.__page.painted;
    const text = resolved['--ds-active-text-primary'];
    expect(text, 'la pagina debe publicar --ds-active-text-primary').not.toBeNull();

    for (const hue of HUES) {
      const painted = resolved[tintOf(hue)].painted;
      expect.soft(
        deltaE(painted, canvas),
        `${tintOf(hue)} (${toHex(painted)}) se hunde en el canvas ${toHex(canvas)}`,
      ).toBeGreaterThanOrEqual(MIN_DELTA_E_VS_CANVAS);
      expect.soft(
        contrast(text.painted, painted),
        `el texto primario sobre ${tintOf(hue)} (${toHex(painted)}) no llega a AA`,
      ).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
    }

    // Los 28 pares. Es la propiedad que sostiene toda la decision: con un solo
    // tinte por matiz, dos estados solo se distinguen si sus matices se
    // distinguen. Incluye a `neutral`, que en la version anterior se quedaba
    // fuera de la asercion de matiz sin que el test lo dijera.
    let pairs = 0;
    for (let i = 0; i < HUES.length; i += 1) {
      for (let j = i + 1; j < HUES.length; j += 1) {
        const [a, b] = [HUES[i], HUES[j]];
        expect.soft(
          deltaE(resolved[tintOf(a)].painted, resolved[tintOf(b)].painted),
          `${a} y ${b} pintan casi lo mismo: `
          + `${toHex(resolved[tintOf(a)].painted)} vs ${toHex(resolved[tintOf(b)].painted)}`,
        ).toBeGreaterThanOrEqual(MIN_DELTA_E_BETWEEN_HUES);
        pairs += 1;
      }
    }
    expect(pairs, 'se midieron los 28 pares').toBe(28);
  });

  test('los siete cromaticos ocupan matices distintos y el octavo es acromatico', async ({ page }) => {
    await openProgramaGeneral(page);

    const resolved = await resolveTokens(page, HUES.map(tintOf));
    const lch = Object.fromEntries(HUES.map((hue) => [hue, parseColor(resolved[tintOf(hue)].declared)]));

    // Que dos anclas no se acerquen en el circulo de matiz. La version anterior
    // media la deriva de cada paso respecto a su ancla, pero como todos los
    // pasos conservaban `h` literalmente esa medida daba 0,000° siempre: no
    // podia fallar. Lo que si puede fallar -y es lo que importa- es que dos de
    // los siete matices se junten.
    for (let i = 0; i < CHROMATIC.length; i += 1) {
      for (let j = i + 1; j < CHROMATIC.length; j += 1) {
        const [a, b] = [CHROMATIC[i], CHROMATIC[j]];
        expect.soft(
          hueGap(lch[a].H, lch[b].H),
          `${a} (${lch[a].H.toFixed(1)}°) y ${b} (${lch[b].H.toFixed(1)}°) son casi el mismo matiz`,
        ).toBeGreaterThanOrEqual(MIN_HUE_SEPARATION_DEG);
      }
    }

    // Y que `neutral` siga siendo el silencio. El umbral anterior era un croma
    // absoluto de 0,03 que no discriminaba nada -habia pasos cromaticos por
    // debajo de el-. Aqui se compara contra la propia paleta: neutral tiene que
    // ser varias veces menos cromatico que el MENOS cromatico de los siete.
    const leastChromatic = Math.min(...CHROMATIC.map((hue) => lch[hue].C));
    expect(
      leastChromatic / lch.neutral.C,
      `neutral (C=${lch.neutral.C.toFixed(4)}) dejo de ser acromatico frente al `
      + `cromatico mas apagado (C=${leastChromatic.toFixed(4)})`,
    ).toBeGreaterThanOrEqual(MIN_CHROMA_RATIO_VS_NEUTRAL);
  });

  // El par con el que se midio el contraste original (8,88-10,99:1) es el ancla
  // contra SU texto tintado, y ningun test lo cubria: la capa de matiz empareja
  // los tintes con `--ds-active-text-primary`, asi que el texto tintado vivia
  // sin guard aunque /pdc lo pinta en sus siete chips.
  test('cada ancla sostiene su texto tintado', async ({ page }) => {
    await openProgramaGeneral(page);
    const hues = await contractHues();

    const sinTexto = hues.filter(({ text }) => text === undefined).map(({ id }) => id);
    expect(
      sinTexto,
      'el catalogo debe declarar el texto tintado de siete de los ocho matices',
    ).toEqual([HUE_WITHOUT_TINTED_TEXT]);

    const withText = hues.filter(({ text }) => text !== undefined);
    const resolved = await resolveTokens(
      page,
      withText.flatMap(({ tint, text }) => [tint, text]),
    );

    for (const { id, tint, text } of withText) {
      expect.soft(resolved[tint], `${tint} no resuelve`).not.toBeNull();
      expect.soft(resolved[text], `${text} no resuelve`).not.toBeNull();
      if (!resolved[tint] || !resolved[text]) continue; // sin color no hay ratio que medir
      const ratio = contrast(resolved[text].painted, resolved[tint].painted);
      expect.soft(
        ratio,
        `el texto tintado de ${id} (${toHex(resolved[text].painted)}) sobre su ancla `
        + `(${toHex(resolved[tint].painted)}) mide ${ratio.toFixed(2)}:1`,
      ).toBeGreaterThanOrEqual(MIN_TINTED_TEXT_CONTRAST);
    }
    expect(withText.length, 'siete anclas con texto tintado').toBe(7);
  });

  for (const [route, legend] of Object.entries(MODULE_LEGENDS)) {
    test(`${route} da un matiz distinto a cada estado`, async ({ page }) => {
      await page.setViewportSize(VIEWPORT);
      await loginAndSelectProject(page, project);
      await page.goto(route, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(3000);

      const aliases = MODULE_ALIASES[route];
      const bindings = { ...legend, ...aliases };
      const names = [...Object.keys(bindings), ...new Set(Object.values(bindings).map(tintOf))];
      const resolved = await resolveTokens(page, names);

      // Igualdad estricta sobre la cadena calculada, no sobre el hex pintado:
      // si el modulo consume el token de la paleta, el motor devuelve
      // literalmente el mismo valor y no hay redondeo que tolerar. Un modulo
      // que recalcule el color por su cuenta cae aqui aunque el resultado se
      // parezca.
      for (const [moduleToken, hue] of Object.entries(bindings)) {
        expect.soft(
          resolved[tintOf(hue)]?.declared,
          `${tintOf(hue)} no resuelve en ${route}`,
        ).toBeTruthy();
        expect.soft(
          resolved[moduleToken]?.declared,
          `${moduleToken} deberia resolver igual que ${tintOf(hue)} (matiz ${hue})`,
        ).toBe(resolved[tintOf(hue)]?.declared);
      }

      // Y la regla, medida en el pixel: dos entradas de leyenda no pueden
      // pintar el mismo fondo. Es el fallo concreto que motivo la reduccion de
      // la escalera -`alert1`/`alert23` aqui, `restr-1`/`restr-2-3` en
      // General- y el que ningun guard cazaba.
      const entries = Object.entries(legend);
      for (let i = 0; i < entries.length; i += 1) {
        for (let j = i + 1; j < entries.length; j += 1) {
          const [tokenA] = entries[i];
          const [tokenB] = entries[j];
          // Un token que no resuelve ya quedo reportado arriba; comparar `null`
          // aqui solo anadiria un fallo derivado.
          if (!resolved[tokenA] || !resolved[tokenB]) continue;
          expect.soft(
            deltaE(resolved[tokenA].painted, resolved[tokenB].painted),
            `${tokenA} y ${tokenB} son dos entradas de leyenda y pintan `
            + `${toHex(resolved[tokenA].painted)} y ${toHex(resolved[tokenB].painted)}`,
          ).toBeGreaterThanOrEqual(MIN_DELTA_E_BETWEEN_HUES);
        }
      }
    });
  }
});
