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
// Lo que este test guarda es lo que la poda OWNS: que el conmutador no vuelva a
// suprimir su propio outline. No guarda que el anillo se vea, y la diferencia
// importa: medido, el trigger sigue SIN marca de foco despues de la poda, porque
// su `outline-style` es `none` desde antes -un `<button>` desnudo en esta pagina
// ya computa `none`, asi que lo suprime un reset global de botones-. Ese defecto
// de WCAG 2.4.7 es anterior y mas ancho que este bloque, y esta reportado como
// hallazgo aparte; afirmarlo aqui como si esta poda lo resolviera seria falso.
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

  test('el conmutador no aplasta su propio outline al enfocarse', async ({ page }) => {
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

    // Antes de la poda esto valía 0px: el `outline: 0 !important` del bloque
    // muerto lo aplastaba. Es la mitad del problema que esta poda sí resuelve.
    expect(
      Number.parseFloat(focused.outlineWidth),
      `el conmutador vuelve a aplastar su propio outline (${focused.outlineWidth}); `
      + 'alguien reintrodujo un `outline: 0` en su cascada',
    ).toBeGreaterThan(0);
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
