import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { VIEWPORT, installContrastProbe, openModal, measureBoundary } from './support/contrast.mjs';

// La cabecera de #modalEditarContratos pinta su fondo con un DEGRADADO, y la
// sonda compartida de `support/contrast.mjs` solo compone `backgroundColor`:
// sobre un degradado devuelve el color del ancestro y sobrestima el contraste
// (media 15,5:1 donde la verdad es 8,25:1). Por eso este guard calcula la razon
// contra las DOS paradas reales del degradado.
//
// Guarda el tramo F1 de Contratos: su piel local desaparecio y hoy la tinta, el
// filete y el anillo de foco los provee el shell .aia-modal. Si alguien edita el
// shell, esta ruta regresa en silencio y ninguna otra suite lo ve — el caso de
// /contratos en modales-dark-homologacion.mjs solo mide tres nodos del CUERPO.
//
// A proposito NO se asierta que color, que token ni que archivo gana: solo que
// el resultado se lee. Cambiar el mecanismo debe poder hacerse sin tocar esto.

const project = PROJECTS.find(({ key }) => key === 'construction');
const AA = 4.5;
const NO_TEXTO = 3; // WCAG 1.4.11 para fronteras de control.

test.use({ viewport: VIEWPORT });

// Razon de contraste de la tinta de `selector` contra cada paradas del degradado
// que pinta `superficie`. Devuelve una razon por parada.
async function contrasteSobreDegradado(page, superficie, selector) {
  return page.evaluate(
    ([selSuperficie, selTinta]) => {
      const lum = ([r, g, b]) => {
        const f = (c) => {
          const s = c / 255;
          return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
        };
        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
      };
      const razon = (a, b) => {
        const [x, y] = [lum(a), lum(b)];
        return Math.round(((Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05)) * 100) / 100;
      };

      const superficie = document.querySelector(selSuperficie);
      const tinta = document.querySelector(selTinta);
      if (!superficie || !tinta) return null;

      const paradas = [
        ...getComputedStyle(superficie).backgroundImage.matchAll(/rgba?\(([^)]+)\)/g),
      ].map((m) => m[1].split(',').slice(0, 3).map((v) => Number.parseFloat(v)));
      if (paradas.length === 0) return { paradas: [], razones: [] };

      // La `opacity` acumulada de la cadena de ancestros diluye la tinta contra
      // lo que tenga debajo: sin esto un boton al 0,8 mide su color crudo.
      let opacidad = 1;
      for (let n = tinta; n; n = n.parentElement) {
        const o = Number.parseFloat(getComputedStyle(n).opacity);
        if (Number.isFinite(o)) opacidad *= o;
      }
      const color = getComputedStyle(tinta)
        .color.match(/[\d.]+/g)
        .slice(0, 3)
        .map(Number);

      return {
        paradas,
        razones: paradas.map((parada) => {
          const mezclada = color.map((v, i) => v * opacidad + parada[i] * (1 - opacidad));
          return razon(mezclada, parada);
        }),
      };
    },
    [superficie, selector],
  );
}

test('/contratos #modalEditarContratos: la tinta de la cabecera se lee sobre el degradado', async ({
  page,
}) => {
  await loginAndSelectProject(page, project);
  await page.goto('/contratos');
  await installContrastProbe(page);
  await openModal(page, 'modalEditarContratos');

  const cabecera = '#modalEditarContratos .ct-modal-header';

  // Autovalidacion de la sonda: si la cabecera dejara de pintar un degradado,
  // las razones saldrian vacias y el test pasaria sin medir nada.
  const paradas = await page.evaluate(
    (sel) =>
      [...getComputedStyle(document.querySelector(sel)).backgroundImage.matchAll(/rgba?\(/g)].length,
    cabecera,
  );
  expect(paradas, 'la cabecera ya no pinta un degradado: revisa este guard').toBeGreaterThanOrEqual(
    2,
  );

  for (const selector of [
    '#modalEditarContratos .ct-modal-title',
    '#modalEditarContratos .ct-modal-title .aia-modal__headline',
    '#modalEditarContratos .ct-modal-title .aia-modal__subtitle',
    '#modalEditarContratos .ct-modal-close',
  ]) {
    const medida = await contrasteSobreDegradado(page, cabecera, selector);
    expect(medida, `${selector} no existe`).not.toBeNull();
    expect(medida.razones.length, `${selector}: no se midio ninguna parada`).toBeGreaterThan(0);
    for (const [i, r] of medida.razones.entries()) {
      expect
        .soft(r, `${selector} contra la parada ${i} del degradado (${medida.paradas[i]})`)
        .toBeGreaterThanOrEqual(AA);
    }
  }
});

test('/contratos #modalEditarContratos: los controles conservan un foco perceptible', async ({
  page,
}) => {
  await loginAndSelectProject(page, project);
  await page.goto('/contratos');
  await installContrastProbe(page);
  await openModal(page, 'modalEditarContratos');

  const control = '#modalEditarContratos .ct-contract-field--quantity .form-control';

  const enReposo = await measureBoundary(page, control);
  expect(enReposo, 'el control no existe').not.toBeNull();

  await page.evaluate((sel) => document.querySelector(sel).focus(), control);
  await page.waitForTimeout(250);

  const conFoco = await page.evaluate((sel) => {
    const cs = getComputedStyle(document.querySelector(sel));
    return {
      esElActivo: document.activeElement === document.querySelector(sel),
      anillo: cs.boxShadow,
      contorno: cs.outlineStyle,
      contornoAncho: cs.outlineWidth,
    };
  }, control);
  expect(conFoco.esElActivo, 'el control no tomo el foco').toBe(true);

  // Debe existir ALGUN indicador de foco. No se exige cual: anillo, contorno o
  // borde. Lo que no vale es que no haya ninguno.
  const hayAnillo = conFoco.anillo !== 'none' && conFoco.anillo !== '';
  const hayContorno =
    conFoco.contorno !== 'none' && Number.parseFloat(conFoco.contornoAncho || '0') > 0;
  const frontera = await measureBoundary(page, control);
  const bordeCambia = frontera.border !== enReposo.border;
  expect(
    hayAnillo || hayContorno || bordeCambia,
    `el control no muestra ningun indicador de foco: ${JSON.stringify(conFoco)}`,
  ).toBe(true);

  // Y ese indicador tiene que distinguirse de lo que lo rodea.
  expect
    .soft(
      Math.max(frontera.borderVsFill, frontera.borderVsSurround),
      `borde con foco ${frontera.border} sobre relleno ${frontera.fill} / entorno ${frontera.surround}`,
    )
    .toBeGreaterThanOrEqual(NO_TEXTO);
});
