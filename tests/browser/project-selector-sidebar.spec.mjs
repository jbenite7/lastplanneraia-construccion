import { expect, test } from '@playwright/test';
import { CREDENTIALS } from './fixtures/projects.mjs';
import { login, logout } from './support/session.mjs';

const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];

// Tarea 10 (S04, «Corte, conservando el PHP», Felipe 2026-09-18): GET /proyectos ya no sirve
// views/core/project_selector.view.php + views/partials/shell_sidebar.php — sirve el shell React
// (BarraLateral + SelectorProyectos). Ruling T9-4 decía que este spec seguía midiendo el legado
// «hasta la Tarea 10»; esta es esa tarea. El contrato DOM que T7-4 declaró vinculante
// (data-shell-pattern="sidebar", data-destination-id, .aia-sidebar__group h3,
// .aia-sidebar__link, data-sidebar-toggle/data-sidebar-state) sigue midiéndose tal cual — es lo
// que BarraLateral reprodujo a propósito. Lo que NO tiene equivalente en React se adapta en
// forma, no se descarta:
//   - El bloque de cuenta de la pantalla standalone es estático (`cuentaPropia`, sin
//     disparador/panel emergente — ver BarraLateral.tsx `data-aia-menu-panel` sin
//     `data-aia-menu-trigger`), así que las aserciones de menú desplegable (ArrowDown, Escape,
//     clic-fuera, aria-haspopup) no aplican: no hay nada que abrir o cerrar.
//   - "Cerrar sesión" es un <button> con POST+CSRF (T7-6), no un <a href="/logout"> — la
//     aserción vinculante ahora es "no hay un GET destructivo", no el valor de un href.
//   - Las tarjetas de proyecto son '.project-selector-react__item' (TarjetaProyecto.tsx), no
//     '.project-item'; el contenedor es '.project-selector-react__list', no '#projectGrid'.
for (const viewport of VIEWPORTS) {
  test(`project selector sidebar is operable at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    // T7-5 (rojo preexistente medido por el coordinador en dfe86d2f, no una regresión de S04):
    // PR #37 (frente `bloqueo-tema-claro`, 2026-09-07) cambió el default de `theme-bootstrap.js`
    // a "light" para toda la app. Este spec mide específicamente el rail en oscuro, así que ahora
    // materializa el tema que va a medir en vez de heredarlo — mismo patrón que ese frente aplicó
    // en los specs que el CI sí corre (ver `tests/browser/shell-sidebar-rollout.mjs`). El CI no
    // corre este archivo (corrección 9 de S04), por eso quedó sin arreglar hasta ahora.
    await page.addInitScript(() => {
      try {
        window.localStorage.setItem('aia-theme', 'dark');
      } catch {
        /* modo privado */
      }
    });
    await login(page, CREDENTIALS);

    const sidebar = page.locator('[data-shell-pattern="sidebar"]');
    await expect(sidebar).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    const projectsLink = sidebar.locator('[data-destination-id="projects"]');
    await expect(projectsLink).toHaveAttribute('aria-current', 'page');
    await expect(sidebar.locator('[aria-current="page"]')).toHaveCount(1);
    await expect(sidebar.locator('[data-sidebar-notifications]')).toHaveCount(0);

    // The group heading stays muted (secondary), not the primary text that
    // dark-mode.css's `body.dark-mode h1..h6 !important` would otherwise force.
    const [headingColor, secondaryToken] = await Promise.all([
      sidebar.locator('.aia-sidebar__group h3').first().evaluate((el) => getComputedStyle(el).color),
      page.evaluate(() => {
        const probe = document.createElement('span');
        probe.style.color = getComputedStyle(document.documentElement).getPropertyValue('--ds-active-text-secondary').trim();
        document.body.append(probe);
        const value = getComputedStyle(probe).color;
        probe.remove();
        return value;
      }),
    ]);
    expect(headingColor, 'group heading is not muted').toBe(secondaryToken);

    // The group heading is sentence case, not the uppercase eyebrow that
    // remained before, and legacy globals (styles.css `*` reset / `h1..h6`
    // tracking) must not claw back its inset or letter-spacing.
    const heading = await sidebar.locator('.aia-sidebar__group h3').first().evaluate((el) => {
      const style = getComputedStyle(el);
      return { textTransform: style.textTransform, marginLeft: style.marginLeft, marginTop: style.marginTop, letterSpacing: style.letterSpacing };
    });
    expect(heading.textTransform, 'group heading must not be uppercase').toBe('none');
    expect(heading.letterSpacing, 'group heading must not keep legacy tracking').toBe('normal');
    expect(parseFloat(heading.marginLeft), 'group heading needs a left inset').toBeGreaterThan(0);
    // Tarea 10 (S04): esta aserción exigía margen superior > 0, medido sobre la vista PHP legada
    // de /proyectos, que no cargaba el adaptador del shell. El adaptador canónico
    // (`adapters/shell-sidebar.css`, regla de `.aia-sidebar__group h3` con
    // `margin-block-start: 0 !important`) pega el título a sus ítems a propósito —el
    // section-gap separa los grupos— y la barra React lo carga, igual que el resto de pantallas
    // con el shell. Se afirma el contrato canónico, no el de la vista retirada.
    expect(parseFloat(heading.marginTop), 'group heading sits on its items (canonical shell adapter)').toBe(0);

    // styles.css's `* { padding: 0 }` reset (module layer) would collapse every rail inset; the
    // component's !important paddings must hold so content is not flush against the edge. A
    // diferencia del sidebar PHP (shell_sidebar.php), BarraLateral no pinta íconos por entrada
    // de navegación (ver BarraLateral.tsx: `.aia-sidebar__link` solo lleva
    // `.aia-sidebar__label`, y `ConmutadorTema` un emoji sin `.aia-icon`) — se mide el inset del
    // contenido real de cada uno en vez de un ícono inexistente, conservando la intención
    // original: "no queda pegado al borde y las dos columnas (nav / utilidad del pie) alinean".
    const insets = await sidebar.evaluate((rail) => {
      const railLeft = rail.getBoundingClientRect().left;
      const at = (sel) => { const el = rail.querySelector(sel); return el ? Math.round(el.getBoundingClientRect().left - railLeft) : null; };
      return { linkContent: at('.aia-sidebar__link .aia-sidebar__label'), utilityContent: at('.aia-sidebar__utility span') };
    });
    expect(insets.linkContent, 'nav item content must be inset from the rail edge').toBeGreaterThan(16);
    expect(Math.abs(insets.linkContent - insets.utilityContent), 'nav and footer utility columns must align').toBeLessThanOrEqual(2);

    const main = page.getByTestId('selector-proyectos');
    const sidebarWidth = await sidebar.evaluate((el) => el.getBoundingClientRect().width);
    // Tarea 10 (S04): BarraLateral pone `body.aia-shell--sidebar` al montar y el adaptador anima
    // el padding del body, así que una lectura inmediata cae a mitad de la transición (medido:
    // 200 y 156 px frente a 240). Se espera a que el layout se asiente en vez de leer una vez.
    await expect
      .poll(async () => Math.round(await main.evaluate((el) => el.getBoundingClientRect().left)), {
        message: 'main content must start exactly where the rail ends',
      })
      .toBe(Math.round(sidebarWidth));

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);

    // handsontable-module.css locks `body { overflow: hidden }` on desktop for
    // grid pages; the selector must stay a scrollable document. Force content
    // past the fold and confirm the document actually scrolls to reveal it.
    const verticalScroll = await page.evaluate(() => {
      const probe = document.createElement('div');
      probe.style.height = '600px';
      probe.dataset.e2eScrollProbe = 'true';
      document.querySelector('[data-testid="selector-proyectos"]').appendChild(probe);
      window.scrollTo(0, document.documentElement.scrollHeight);
      const scrolledY = Math.round(window.scrollY || document.documentElement.scrollTop);
      const bodyOverflowY = getComputedStyle(document.body).overflowY;
      probe.remove();
      window.scrollTo(0, 0);
      return { scrolledY, bodyOverflowY };
    });
    expect(verticalScroll.bodyOverflowY, 'body scroll is locked to hidden').not.toBe('hidden');
    expect(verticalScroll.scrolledY, 'document cannot scroll past the fold').toBeGreaterThan(0);

    // The card grid must clear the rail and breathe symmetrically. A `* { padding: 0 }`
    // reset in a late layer strips the vendor gutters, so this is easy to lose.
    const gutters = await page.evaluate(() => {
      const box = (el) => el.getBoundingClientRect();
      const railRight = box(document.querySelector('.aia-navigation--sidebar')).right;
      const items = [...document.querySelectorAll('.project-selector-react__list .project-selector-react__item')];
      const perRow = items.filter((i) => Math.abs(box(i).top - box(items[0]).top) < 2).length;
      return {
        left: Math.round(box(items[0]).left - railRight),
        right: Math.round(window.innerWidth - box(items[perRow - 1]).right),
        between: perRow > 1 ? Math.round(box(items[1]).left - box(items[0]).right) : null,
      };
    });
    expect(gutters.left, 'cards touch the rail').toBeGreaterThan(0);
    expect(gutters.between, 'cards touch each other').toBeGreaterThan(0);
    expect(Math.abs(gutters.left - gutters.right), 'left/right gutters are lopsided').toBeLessThanOrEqual(2);

    // A long real account name must ellipsize inside the rail, never widen it.
    // `.aia-menu { width: fit-content }` in primitives.css would otherwise win.
    // (En React el nombre vive en `.aia-sidebar__account-head`, no en
    // `.aia-sidebar__account .aia-sidebar__label` del disparador legado: el bloque de cuenta de
    // esta pantalla es estático, sin disparador — ver BarraLateral.tsx.)
    await sidebar.locator('.aia-sidebar__account .aia-sidebar__account-head').evaluate((el) => {
      el.textContent = 'Usuario · Juan Felipe Benitez Ramos';
    });
    const railRight = await sidebar.evaluate((el) => el.getBoundingClientRect().right);
    for (const selector of ['.aia-sidebar__account', '.aia-sidebar__account .aia-sidebar__account-head']) {
      const right = await sidebar.locator(selector).evaluate((el) => el.getBoundingClientRect().right);
      expect(right, `${selector} escapes the rail`).toBeLessThanOrEqual(railRight);
    }
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth),
    ).toBeLessThanOrEqual(0);

    const toggle = sidebar.locator('[data-sidebar-toggle]');
    await toggle.click();
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'collapsed');
    await expect
      .poll(() => main.evaluate((el) => Math.round(el.getBoundingClientRect().left)))
      .toBe(await sidebar.evaluate((el) => Math.round(el.getBoundingClientRect().width)));
    await toggle.click();
    await expect(sidebar).toHaveAttribute('data-sidebar-state', 'expanded');

    // F0/Task 8 retiro el conmutador de tema (.aia-theme-switch); dark queda
    // aplicado sin conmutacion.
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    // T7-6 (ruling del coordinador): el bloque de cuenta de la pantalla standalone es estático
    // (sin disparador ni panel emergente — no hay nada que abrir/cerrar aquí, a diferencia del
    // menú de `MenuCuenta` en T01). "Cerrar sesión" es un <button> real, alcanzable por teclado,
    // que dispara un único POST con CSRF contra /api/auth/logout — nunca un
    // <a href="/logout"> (ese GET destruye la sesión sin CSRF ni comprobación de método).
    const logoutButton = sidebar.getByRole('button', { name: 'Cerrar sesión' });
    await expect(logoutButton).toBeVisible();
    await expect(sidebar.locator('a[href="/logout"]')).toHaveCount(0);

    // Anchor/button account items must take the panel's colour
    // (--ds-active-text-primary via `color: inherit`), not the vendor link/button blue.
    // /proyectos only renders one account item (Cerrar sesión; no "Cambiar proyecto" while
    // already on the project selector — BarraLateral `showChangeProject={false}`).
    const [logoutColor, primaryToken] = await Promise.all([
      logoutButton.evaluate((el) => getComputedStyle(el).color),
      page.evaluate(() => {
        const probe = document.createElement('span');
        probe.style.color = getComputedStyle(document.documentElement).getPropertyValue('--ds-active-text-primary').trim();
        document.body.append(probe);
        const value = getComputedStyle(probe).color;
        probe.remove();
        return value;
      }),
    ]);
    expect(logoutColor, 'logout button uses the vendor colour').toBe(primaryToken);

    // Keyboard reachability: Tab desde el disparador del toggle llega al botón de logout sin
    // atajos rotos — sustituye a la prueba de foco por ArrowDown/Escape del menú legado, que no
    // aplica a un bloque sin disparador.
    await logoutButton.focus();
    await expect(logoutButton).toBeFocused();

    await logout(page);
  });
}
