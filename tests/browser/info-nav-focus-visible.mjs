import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };
const TRIGGER = '.filaBotones .aia-info-nav__trigger';
const ITEM = '.filaBotones .aia-info-nav__item';

// `public/css/styles.css` apagaba el anillo de foco de la barra «Informacion
// General» con `outline: none` en `:focus`, sin poner sustituto. Medido: el
// conmutador quedaba en `outline-style: none` con el foco de teclado puesto y
// `:focus-visible` casando. Es WCAG 2.4.7, y alcanza a las tres rutas que
// montan `info_general_nav.js` con `.filaBotones`: /listado-actividades,
// /contratos y /pdc.
//
// El indicador pasa a ser del design system: `adapters/legacy-bridge.css` vive
// en `@layer legacy-overrides`, la ultima del orden declarado en
// `aia-design-system.css:1`, asi que gana a `styles.css` (capa `module`) sin
// necesitar `!important`. El `outline: none` de `styles.css` sobrevive a
// proposito y pasa a hacer lo unico legitimo que hacia: matar el anillo del
// navegador en el foco de RATON, que es `:focus` sin `:focus-visible`.
//
// Por eso las dos aserciones que importan son que con el foco de TECLADO algo
// pinta. No basta `outlineWidth > 0`: con `outline: none` el computado deja
// `3px` -el ancho inicial `medium` que el atajo no toca- y un test asi pasaria
// estando roto.

function relativeLuminance([r, g, b]) {
  const [rs, gs, bs] = [r, g, b].map((channel) => {
    const c = channel / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

function contrastRatio(a, b) {
  const [hi, lo] = [relativeLuminance(a), relativeLuminance(b)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
}

function parseRgb(value) {
  const nums = value.match(/[\d.]+/g);
  if (!nums || nums.length < 3) return null;
  return nums.slice(0, 3).map(Number);
}

async function focusState(page, selector) {
  return page.evaluate((sel) => {
    const node = document.querySelector(sel);
    if (!node) return null;
    const cs = getComputedStyle(node);
    // El fondo contra el que se recorta el anillo es el del ancestro pintado,
    // no el del propio control: `outline-offset` lo separa del borde.
    let behind = node.parentElement;
    let behindColor = 'rgba(0, 0, 0, 0)';
    while (behind) {
      const bg = getComputedStyle(behind).backgroundColor;
      if (bg && !/rgba?\([^)]*,\s*0\s*\)$/.test(bg) && bg !== 'transparent') {
        behindColor = bg;
        break;
      }
      behind = behind.parentElement;
    }
    return {
      outlineWidth: cs.outlineWidth,
      outlineStyle: cs.outlineStyle,
      outlineColor: cs.outlineColor,
      boxShadow: cs.boxShadow,
      focusVisible: node.matches(':focus-visible'),
      isActive: document.activeElement === node,
      behindColor,
    };
  }, selector);
}

async function tabUntil(page, selector) {
  for (let i = 0; i < 80; i += 1) {
    await page.keyboard.press('Tab');
    const reached = await page.evaluate(
      (sel) => document.activeElement === document.querySelector(sel),
      selector,
    );
    if (reached) return true;
  }
  return false;
}

// Mide el elemento que DE HECHO tiene el foco, en vez de exigir que sea uno
// concreto. Nace de una intermitencia real: la version anterior tabulaba a
// ciegas buscando el primer `.aia-info-nav__item` con `querySelector`, y en
// /pdc -la pagina mas larga de las tres- el recorrido podia dar la vuelta al
// documento entero antes de alcanzarlo. Al pasar por el chrome del navegador
// se perdia la modalidad de teclado y `:focus-visible` dejaba de casar: fallaba
// 1 de cada 4 corridas. Medir el foco vivo elimina el recorrido y con el la
// intermitencia.
async function activeElementState(page) {
  return page.evaluate(() => {
    const node = document.activeElement;
    if (!node || node === document.body) return null;
    const cs = getComputedStyle(node);
    let behind = node.parentElement;
    let behindColor = 'rgba(0, 0, 0, 0)';
    while (behind) {
      const bg = getComputedStyle(behind).backgroundColor;
      if (bg && !/rgba?\([^)]*,\s*0\s*\)$/.test(bg) && bg !== 'transparent') {
        behindColor = bg;
        break;
      }
      behind = behind.parentElement;
    }
    return {
      matchesItem: node.matches('.aia-info-nav__item'),
      className: node.className,
      outlineWidth: cs.outlineWidth,
      outlineStyle: cs.outlineStyle,
      outlineColor: cs.outlineColor,
      boxShadow: cs.boxShadow,
      focusVisible: node.matches(':focus-visible'),
      isActive: true,
      behindColor,
    };
  });
}

function assertPaints(state, label) {
  expect(state, `no se encontró ${label}`).toBeTruthy();
  expect(state.focusVisible, `${label}: el foco de teclado debería activar :focus-visible`).toBe(true);

  // La aserción del defecto. `outline-style: none` significa que no se pinta
  // nada, por mucho que `outline-width` valga 3px.
  //
  // Se exige `outline` y NO se acepta un `box-shadow` cualquiera como
  // sustituto. Motivo medido: el disparador ya arrastra una sombra decorativa
  // (`0 10px 22px rgba(30,72,45,.16)`) que aparece igual en hover, asi que
  // aceptarla dejaba pasar el test con el defecto presente. El contrato del
  // design system -`core.css:104` y `adapters/lps-drawer.css:182`- pinta
  // siempre `outline` mas `box-shadow`, asi que exigir el outline es exigir el
  // contrato, no una preferencia.
  const paintsOutline = state.outlineStyle !== 'none' && Number.parseFloat(state.outlineWidth) > 0;
  expect(
    paintsOutline,
    `${label}: el foco de teclado no pinta anillo `
    + `(outline: ${state.outlineWidth} ${state.outlineStyle}, box-shadow: ${state.boxShadow}). `
    + 'Es WCAG 2.4.7: alguien suprimió el anillo sin poner sustituto.',
  ).toBe(true);

  const ring = parseRgb(state.outlineColor);
  const behind = parseRgb(state.behindColor);
  if (ring && behind) {
    const ratio = contrastRatio(ring, behind);
    expect(
      ratio,
      `${label}: el anillo (${state.outlineColor}) no llega a 3:1 contra su fondo `
      + `(${state.behindColor}); mide ${ratio.toFixed(2)}:1. WCAG 1.4.11.`,
    ).toBeGreaterThanOrEqual(3);
  }
}

// Las tres rutas que cargan `info_general_nav.js` y tienen `.filaBotones`. Se
// recorren de verdad en vez de dar por hecho que el arreglo viaja: el supresor
// de `styles.css` esta anclado a `.filaBotones`, que es markup por vista.
const ROUTES = ['/listado-actividades', '/contratos', '/pdc'];

for (const route of ROUTES) {
  test.describe(`foco visible de la barra «Información General» en ${route}`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setViewportSize(VIEWPORT);
      await loginAndSelectProject(page, project);
      await page.goto(route, { waitUntil: 'domcontentloaded' });
      await expect(page.locator(TRIGGER)).toBeVisible({ timeout: 45000 });
    });

    test('el disparador pinta un anillo al recibir foco de teclado', async ({ page }) => {
      // Tabulando, no con .focus(): `:focus-visible` distingue teclado de ratón,
      // y es el de teclado el que exige la marca.
      expect(await tabUntil(page, TRIGGER), 'el disparador no es alcanzable tabulando').toBe(true);
      assertPaints(await focusState(page, TRIGGER), `el disparador de ${route}`);
    });

    test('los ítems del menú pintan un anillo al recibir foco de teclado', async ({ page }) => {
      // El menú se abre CON TECLADO -Enter sobre el disparador ya enfocado-, no
      // con un clic. Así la modalidad de teclado nunca se pierde y el `Tab`
      // siguiente cae en el primer ítem sin recorrer el documento.
      expect(await tabUntil(page, TRIGGER), 'el disparador no es alcanzable tabulando').toBe(true);
      await page.keyboard.press('Enter');
      await expect(page.locator(ITEM).first()).toBeVisible({ timeout: 10000 });

      await page.keyboard.press('Tab');
      const state = await activeElementState(page);
      expect(state, 'nada tiene el foco tras abrir el menú').toBeTruthy();
      expect(
        state.matchesItem,
        `el Tab tras abrir el menú no cayó en un ítem, sino en «${state.className}»`,
      ).toBe(true);
      assertPaints(state, `el ítem de menú de ${route}`);
    });

    test('el foco de ratón sigue sin anillo, que es lo que el `outline: none` sí debe hacer', async ({ page }) => {
      await page.locator(TRIGGER).click();
      const state = await focusState(page, TRIGGER);
      expect(state.isActive, 'el clic debería dejar el foco en el disparador').toBe(true);
      // `:focus-visible` no casa tras un clic de ratón, así que el anillo del
      // navegador debe seguir suprimido: quitarlo era el propósito legítimo del
      // `outline: none` de styles.css, y no debe perderse al arreglar el teclado.
      expect(state.focusVisible, 'el clic de ratón no debería activar :focus-visible').toBe(false);
      expect(state.outlineStyle, 'el foco de ratón no debería pintar anillo').toBe('none');
    });
  });
}
