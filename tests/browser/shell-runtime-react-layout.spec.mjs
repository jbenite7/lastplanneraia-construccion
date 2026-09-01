import { expect, test } from '@playwright/test';
import { bootstrapAutenticado } from './fixtures/shell-runtime-react.mjs';

/**
 * Regresión de layout del encabezado del rail (fix-sidebar-header, 2026-08-30/31).
 *
 * Por qué un test de navegador y no uno de componente: el bug era puramente de CSS Grid
 * (`shell-sidebar.css` fija `grid-template-columns: 1fr auto` en el header, pensado para 2
 * hijos explícitos; el shell React mete 4 — marca, contexto, semana, toggle). jsdom no calcula
 * layout de grid (todo `getBoundingClientRect()` devuelve 0 ahí), así que ningún test de
 * componente (Vitest + Testing Library) puede detectar que la columna `1fr` colapsó a 0px y
 * que "Last Planner AIA" se partió en una letra por línea. Solo un navegador real —Playwright—
 * calcula el grid y puede medir el ancho renderizado.
 *
 * Qué mide: que el nombre de marca y el contexto de proyecto/usuario ocupan un ancho real
 * (no colapsan a la columna auto-colocada perdiendo la 1fr), y que el bloque de semana no
 * termina compartiendo la columna del botón de colapsar. Sigue el mismo método con el que se
 * midió el bug originalmente (medición de `getBoundingClientRect()` en el navegador integrado).
 */

const ANCHO_MINIMO_MARCA = 60; // "Last Planner AIA" no puede caber en menos que esto sin partirse.

const SEMANA_COMPLETA = {
  current: 6,
  options: [
    { number: 5, startsOn: '2026-08-17', endsOn: '2026-08-23' },
    { number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' },
  ],
  actions: { select: true, create: true, deleteLast: true },
};

async function entrarConSemanaCompleta(page, viewport) {
  await page.setViewportSize(viewport);
  await page.route('**/api/**', (route) => route.abort('failed'));
  await page.route('**/api/session', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify(bootstrapAutenticado({
      // Semana con las tres acciones habilitadas: reproduce el header de 4 hijos real
      // (marca + contexto + semana con selector/botones + toggle), no una versión reducida.
      week: SEMANA_COMPLETA,
    })),
  }));
  await page.route('**/session/touch', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
  }));
  await page.goto('/app');
  await expect(page.getByRole('navigation')).toBeVisible();
}

/** Grupo de navegación sintético de tamaño arbitrario — el número real de ítems depende del rol
 *  y del proyecto (`$shellHiddenByRole` en `shell_sidebar.php`), así que la prueba de
 *  desbordamiento vertical no puede calibrarse contra un solo caso: cubre un menú corto (cabe
 *  sin scroll) y uno largo (obliga a scroll propio del nav) por igual. */
function grupoConNItems(n) {
  const items = Array.from({ length: n }, (_, i) => ({
    id: `item-${i}`,
    label: `Módulo de prueba ${i + 1}`,
    href: `/modulo-${i}`,
    icon: 'program',
    action: false,
  }));
  return { id: 'obra', label: 'Obra', items };
}

async function entrarConMenuDeTamano(page, viewport, tema, n) {
  await page.setViewportSize(viewport);
  await page.addInitScript((t) => { try { localStorage.setItem('aia-theme', t); } catch { /* modo privado */ } }, tema);
  await page.route('**/api/**', (route) => route.abort('failed'));
  await page.route('**/api/session', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify(bootstrapAutenticado({
      navigation: { bi: null, groups: [grupoConNItems(n)] },
      week: SEMANA_COMPLETA,
    })),
  }));
  await page.route('**/session/touch', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }),
  }));
  await page.goto('/app');
  await expect(page.getByRole('navigation')).toBeVisible();
}

test('[1180×820] el encabezado del rail expandido no colapsa la columna de marca/contexto', async ({ page }) => {
  await entrarConSemanaCompleta(page, { width: 1180, height: 820 });

  const marca = page.locator('.aia-sidebar__brand-name');
  const contexto = page.locator('.aia-sidebar__context');
  const semana = page.locator('.aia-sidebar__week');
  const toggle = page.locator('.aia-sidebar__toggle');

  await expect(marca).toBeVisible();
  await expect(marca).toHaveText('Last Planner AIA');

  const [cajaMarca, cajaContexto, cajaSemana, cajaToggle] = await Promise.all([
    marca.boundingBox(),
    contexto.boundingBox(),
    semana.boundingBox(),
    toggle.boundingBox(),
  ]);

  // El síntoma medido del bug: con el header roto, marca y contexto caían a 0px de ancho
  // porque la columna 1fr se colapsaba (el bloque semana le robaba la columna auto al toggle).
  expect(cajaMarca.width, `marca=${cajaMarca.width}px`).toBeGreaterThan(ANCHO_MINIMO_MARCA);
  expect(cajaContexto.width, `contexto=${cajaContexto.width}px`).toBeGreaterThan(ANCHO_MINIMO_MARCA);

  // "Last Planner AIA" no puede componerse a una letra por línea: con la fuente/ancho reales,
  // una línea (o dos, si el ancho es justo) miden bastante menos que 17 líneas de alto.
  const alturaLinea = await marca.evaluate((el) => parseFloat(getComputedStyle(el).fontSize));
  expect(cajaMarca.height, `alto marca=${cajaMarca.height}px, fontSize=${alturaLinea}px`).toBeLessThan(alturaLinea * 4);

  // El bloque de semana no debe terminar compartiendo la columna angosta del toggle: su ancho
  // real debe acercarse al ancho del header, no al ancho (~44px) del botón de colapsar.
  const cajaHeader = await page.locator('.aia-sidebar__header').boundingBox();
  expect(cajaSemana.width, `semana=${cajaSemana.width}px, header=${cajaHeader.width}px`).toBeGreaterThan(cajaHeader.width * 0.5);

  // El toggle conserva su tamaño de objetivo táctil, sin ser aplastado por el bloque de semana.
  expect(cajaToggle.width).toBeGreaterThanOrEqual(40);
});

test('[1180×820] el rail colapsado oculta el bloque de semana sin romper el layout', async ({ page }) => {
  await entrarConSemanaCompleta(page, { width: 1180, height: 820 });

  await page.getByRole('button', { name: /colapsar menú/i }).click();
  await expect(page.locator('[data-sidebar-state="collapsed"]')).toBeVisible();
  await expect(page.locator('.aia-sidebar__week')).toBeHidden();
});

test('[390×844] el drawer móvil muestra marca, contexto y semana legibles', async ({ page }) => {
  await entrarConSemanaCompleta(page, { width: 390, height: 844 });

  await page.getByRole('button', { name: /abrir menú de navegación/i }).click();
  const aside = page.getByRole('navigation').locator('xpath=ancestor::aside');
  await expect(aside).toHaveAttribute('data-shell-drawer-open', 'true');

  const marca = page.locator('.aia-sidebar__brand-name');
  await expect(marca).toHaveText('Last Planner AIA');
  const cajaMarca = await marca.boundingBox();
  expect(cajaMarca.width, `marca=${cajaMarca.width}px`).toBeGreaterThan(ANCHO_MINIMO_MARCA);

  await expect(page.locator('.aia-sidebar__context')).toBeVisible();
  await expect(page.locator('.aia-sidebar__week')).toBeVisible();
});

/**
 * Regresión de desbordamiento vertical (fix-sidebar-header, 2026-08-31, hallazgo de la
 * coordinadora): el mismo bloque de semana que el fix de arriba corrige horizontalmente le come
 * ~90px de altura al rail que el "presupuesto cero-scroll" de `shell-sidebar.css` no
 * contemplaba (ese presupuesto se calibró para el shell PHP legado, sin el bloque de semana que
 * la Tarea 5 T01 solo agrega en React). Sin scroll propio del nav, un rol con más módulos de los
 * que ese presupuesto fijo permite pinta el menú encima del pie.
 *
 * El fix le devuelve al nav de React (`#app-shell-nav`, id por defecto de `NavegacionLateral`)
 * el `overflow-y: auto` canónico que `shell-sidebar.css` apaga para TODO el shell (React y
 * legado) — apagado que existe solo por un flyout de semana del legado
 * (`.shell-week-flyout`, disparado dentro del `<li>` del nav) que React no tiene, porque su
 * contexto de semana vive en el encabezado. Por eso el fix se scoped a `#app-shell-nav`: el
 * legado nunca emite ese id, así que su comportamiento (incluido el flyout) no cambia.
 *
 * Por qué dos tamaños de menú y no solo el actual: el número de ítems depende del rol y del
 * proyecto — un menú corto no desborda nunca, así que probar solo el caso actual no habría
 * atrapado el bug hasta que alguien con más módulos lo viera en producción.
 */
for (const menuSize of [{ etiqueta: 'menú corto', n: 2 }, { etiqueta: 'menú largo', n: 25 }]) {
  for (const viewport of [{ width: 1180, height: 820 }, { width: 390, height: 844 }]) {
    for (const tema of ['light', 'dark']) {
      test(`[${viewport.width}×${viewport.height} · ${tema} · ${menuSize.etiqueta}] el nav no pinta el menú encima del pie`, async ({ page }) => {
        await entrarConMenuDeTamano(page, viewport, tema, menuSize.n);

        const nav = page.locator('.aia-sidebar__nav');
        const footer = page.locator('.aia-sidebar__footer');
        const [cajaNav, cajaFooter] = await Promise.all([nav.boundingBox(), footer.boundingBox()]);

        // El síntoma medido por la coordinadora: borde inferior del nav (o de su contenido,
        // que con overflow:visible se sale de la caja) por encima del borde superior del pie.
        expect(cajaNav.y + cajaNav.height, `navBottom=${cajaNav.y + cajaNav.height} footerTop=${cajaFooter.y}`)
          .toBeLessThanOrEqual(cajaFooter.y + 1);

        // El pie sigue visible y pulsable — no tapado por contenido del nav desbordado.
        await expect(footer).toBeVisible();
        const conmutadorTema = page.getByRole('button', { name: /tema/i });
        await expect(conmutadorTema).toBeVisible();
        const cajaConmutador = await conmutadorTema.boundingBox();
        expect(cajaConmutador.y + cajaConmutador.height, 'el conmutador de tema debe caber en el viewport')
          .toBeLessThanOrEqual(viewport.height + 1);
      });
    }
  }
}

test('[1180×820] el rail colapsado con un menú largo tampoco desborda sobre el pie', async ({ page }) => {
  await entrarConMenuDeTamano(page, { width: 1180, height: 820 }, 'light', 30);

  await page.getByRole('button', { name: /colapsar menú/i }).click();
  await expect(page.locator('[data-sidebar-state="collapsed"]')).toBeVisible();

  const nav = page.locator('.aia-sidebar__nav');
  const footer = page.locator('.aia-sidebar__footer');
  const [cajaNav, cajaFooter] = await Promise.all([nav.boundingBox(), footer.boundingBox()]);
  expect(cajaNav.y + cajaNav.height).toBeLessThanOrEqual(cajaFooter.y + 1);
  await expect(footer).toBeVisible();
});
