import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };
const TRIGGER = '.filaBotones .la-toolbar-switcher .aia-info-nav__trigger';

// El conmutador de vista de /listado-actividades tenia dos reglas escritas para
// el tema claro que el producto ya no tiene: una base con `--aia-green-*` y un
// bloque `:hover, :focus` que tintaba el fondo con `success-bg`.
//
// De esas dos reglas, casi todo estaba muerto: el override de dark
// (`html.aia-theme-dark .filaBotones .la-toolbar-switcher .aia-info-nav__trigger`)
// gana con mas especificidad -anade `html.aia-theme-dark`- y tambien lleva
// `!important`, asi que vence incluso sobre el `:hover`.
//
// Pero NO todo. El bloque de hover declaraba ademas `outline: 0 !important`, que
// el override dark no declara, asi que esa linea SI aplicaba. Y su sustituto
// -`box-shadow: var(--ds-shadow-focus)`- si lo pisaba el override. Resultado
// medido: el trigger es alcanzable por teclado y `:focus-visible` casa, pero no
// pinta ningun indicador de foco. Media regla viva suprimiendo el anillo sin
// poner nada en su lugar.
//
// Lo que este test guarda es lo que la poda OWNS, que NO es que el anillo se
// vea. Medido tras podar: `outline-width: 3px` con `outline-style: none`. Esos
// 3px son solo `medium` -el ancho inicial que el atajo `outline: none` deja al
// resetear-, asi que no pintan nada. Aserta `outlineWidth > 0` seria un test
// que pasa estando roto.
//
// Quien lo suprime ahora esta medido y es `styles.css:2739`, una copia hermana
// de este mismo bloque muerto en la hoja compartida, que le gana por
// especificidad al `button:focus` de Bootstrap. El conmutador no es `.btn`.
// Ese defecto de WCAG 2.4.7 alcanza tambien a /contratos y /pdc y esta
// reportado aparte.
//
// Lo que si es propio de la poda: la hoja de MODULO ya no declara `outline`
// sobre el trigger. Importa porque lo hacia con `!important`, y eso habria
// derrotado al arreglo cuando se haga en `styles.css`. Eso es lo que se guarda.
async function focusIndicator(page) {
  return page.evaluate((selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;
    const base = getComputedStyle(el);
    return {
      outlineWidth: base.outlineWidth,
      outlineStyle: base.outlineStyle,
      boxShadow: base.boxShadow,
      focusVisible: el.matches(':focus-visible'),
      isActive: document.activeElement === el,
    };
  }, TRIGGER);
}

test.describe('conmutador de vista de /listado-actividades', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT);
    await loginAndSelectProject(page, project);
    await page.goto('/listado-actividades', { waitUntil: 'domcontentloaded' });
    await expect(page.locator(TRIGGER)).toBeVisible({ timeout: 45000 });
  });

  test('la hoja de módulo no vuelve a suprimir el outline del conmutador', async ({ page }) => {
    const before = await focusIndicator(page);
    expect(before, 'no se encontró el conmutador').toBeTruthy();

    // Se llega tabulando, no con .focus(): `:focus-visible` distingue el foco de
    // teclado del de raton, y es el de teclado el que necesita la marca.
    let reached = false;
    for (let i = 0; i < 60 && !reached; i += 1) {
      await page.keyboard.press('Tab');
      reached = await page.evaluate(
        (selector) => document.activeElement === document.querySelector(selector),
        TRIGGER,
      );
    }
    expect(reached, 'el conmutador no es alcanzable tabulando').toBe(true);

    const focused = await focusIndicator(page);
    expect(focused.focusVisible, 'el foco de teclado debería activar :focus-visible').toBe(true);

    // Se recorren las reglas que casan con el trigger y se queda con las que
    // declaran `outline` desde `listado-actividades.css`. Antes de la poda habia
    // una (`outline: 0 !important`); ahora no debe haber ninguna.
    const culprits = await page.evaluate((selector) => {
      const node = document.querySelector(selector);
      const found = [];
      for (const sheet of document.styleSheets) {
        let rules;
        try { rules = sheet.cssRules; } catch { continue; }
        const walk = (list, href) => {
          for (const rule of list) {
            if (rule.styleSheet) {
              try { walk(rule.styleSheet.cssRules, rule.href || href); } catch { /* CORS */ }
              continue;
            }
            if (rule.cssRules && !rule.selectorText) { walk(rule.cssRules, href); continue; }
            if (!rule.selectorText) continue;
            const decl = rule.style?.cssText || '';
            // `outline-offset` no suprime nada: solo separa el anillo del borde.
            if (!/(^|[^-])outline(-width|-style|-color)?\s*:/.test(decl)) continue;
            try {
              if (!node.matches(rule.selectorText.replace(/:focus-visible/g, ':focus'))) continue;
            } catch { continue; }
            found.push({ file: (href || 'inline').split('/').pop().split('?')[0], sel: rule.selectorText, decl });
          }
        };
        walk(rules, sheet.href);
      }
      return found;
    }, TRIGGER);

    const fromModule = culprits.filter(({ file }) => file === 'listado-actividades.css');
    expect(
      fromModule,
      'la hoja de módulo volvió a declarar `outline` sobre el conmutador: '
      + `${JSON.stringify(fromModule)}. Esa era exactamente la regla podada, y con `
      + '`!important` derrotaría al arreglo que toca hacer en styles.css:2739.',
    ).toEqual([]);
  });

  test('el reposo y el hover conservan su aspecto', async ({ page }) => {
    // Las declaraciones de color de las dos reglas claras estaban muertas: las
    // ganaba el override de dark. Podarlas no debe mover ni el reposo ni el
    // hover; si algo cambia aqui, es que no estaban tan muertas.
    const rest = await focusIndicator(page);
    const geometry = await page.evaluate((selector) => {
      const cs = getComputedStyle(document.querySelector(selector));
      return {
        backgroundColor: cs.backgroundColor,
        color: cs.color,
        borderTopColor: cs.borderTopColor,
        borderTopWidth: cs.borderTopWidth,
        borderTopStyle: cs.borderTopStyle,
        borderRadius: cs.borderTopLeftRadius,
        minHeight: cs.minHeight,
        paddingTop: cs.paddingTop,
        paddingLeft: cs.paddingLeft,
        outlineOffset: cs.outlineOffset,
      };
    }, TRIGGER);

    // Estos son los valores que el override de dark NO declara y que, por tanto,
    // vienen de la regla base: si se poda entera se pierden. El de 44px es el
    // area tactil accesible.
    expect(geometry.minHeight, 'la altura mínima accesible').toBe('44px');
    expect(geometry.paddingTop).toBe('4px');
    expect(geometry.paddingLeft).toBe('8px');
    expect(geometry.outlineOffset).toBe('2px');
    expect(geometry.borderTopWidth).toBe('1px');
    expect(geometry.borderTopStyle).toBe('solid');
    expect(geometry.borderRadius).toBe('12px');

    // Y estos vienen del override de dark, no de la regla clara.
    expect(geometry.backgroundColor).toBe('rgba(35, 48, 41, 0.86)');
    expect(geometry.color).toBe('rgb(247, 250, 248)');
    expect(geometry.borderTopColor).toBe('rgba(221, 239, 230, 0.22)');

    await page.locator(TRIGGER).hover();
    await page.waitForTimeout(200);
    const hovered = await focusIndicator(page);
    expect(hovered.boxShadow, 'el hover no debería cambiar la sombra').toBe(rest.boxShadow);
  });
});
