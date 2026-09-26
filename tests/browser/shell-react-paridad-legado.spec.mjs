import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/projects.mjs';

const ENTRAR = BASE_URL + '/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto');

async function medirRiel(page, ruta) {
  await page.goto(ruta);
  const riel = page.locator('aside.aia-navigation--sidebar');
  await riel.waitFor();
  return riel.evaluate((el) => {
    const activo = el.querySelector('[aria-current="page"]');
    const cs = activo ? getComputedStyle(activo) : null;
    return {
      estado: el.getAttribute('data-sidebar-state'),
      ancho: Math.round(el.getBoundingClientRect().width),
      etiquetas: [...el.querySelectorAll('a, button')]
        .map((nodo) => (nodo.getAttribute('aria-label') || nodo.textContent || '').trim())
        .filter(Boolean),
      navegacion: [...el.querySelectorAll('[data-destination-id]')]
        .map((nodo) => (nodo.getAttribute('aria-label') || nodo.textContent || '').trim())
        .filter(Boolean),
      iconos: [...el.querySelectorAll('[data-destination-id]')].map((nodo) => {
        const icono = nodo.querySelector('.aia-icon, i');
        const rect = icono?.getBoundingClientRect();
        const estilo = icono ? getComputedStyle(icono) : null;
        const svg = icono?.querySelector('svg.aia-icon__glyph');
        return {
          id: nodo.getAttribute('data-destination-id'),
          ruta: nodo.getAttribute('href'),
          nombre: [...(icono?.classList ?? [])].find((clase) => clase.startsWith('aia-icon--')),
          glifo: [...(svg?.children ?? [])].map((parte) => `${parte.tagName.toLowerCase()}:` +
            [...parte.attributes].sort((a, b) => a.name.localeCompare(b.name))
              .map((atributo) => `${atributo.name}=${atributo.value}`).join(',')),
          respaldo: Boolean(svg?.querySelector('circle[cx="12"][cy="12"][r="7"]')),
          visible: Boolean(rect && rect.width > 0 && rect.height > 0
            && estilo?.display !== 'none' && estilo?.visibility !== 'hidden'),
        };
      }),
      colorActivo: cs?.color ?? null,
    };
  });
}

for (const tema of ['light', 'dark']) {
  test(`el riel React se comporta como el del legado · ${tema}`, async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.addInitScript((temaInicial) => localStorage.setItem('aia-theme', temaInicial), tema);
    await page.goto(ENTRAR);
    await page.evaluate(() => localStorage.removeItem('aia-sidebar-state'));

    const legado = await medirRiel(page, BASE_URL + '/programacion-semanal');
    const react = await medirRiel(page, BASE_URL + '/programa-general');

    expect(legado.estado).toBe('collapsed');
    expect(react.estado).toBe('collapsed');
    expect(Math.abs(react.ancho - legado.ancho)).toBeLessThanOrEqual(2);
    expect(legado.iconos.every((icono) => icono.visible)).toBe(true);
    expect(react.iconos.every((icono) => icono.visible)).toBe(true);
    expect(react.iconos.map(({ id }) => id)).toEqual(legado.iconos.map(({ id }) => id));
    for (const esperado of legado.iconos) {
      const actual = react.iconos.find(({ id }) => id === esperado.id);
      expect(actual.ruta, esperado.id).toBe(esperado.ruta);
      expect(actual.nombre, esperado.id).toBe(esperado.nombre);
      expect(actual.glifo, `${esperado.id}: glifo del legado`).toEqual(esperado.glifo);
      expect(actual.respaldo, `${esperado.id}: respaldo genérico`).toBe(false);
    }
    // Las opciones de semana ya no pertenecen al riel React (R1.2-4), y tema/cuenta son
    // controles propios del host. La paridad verificable del riel son sus destinos emitidos por
    // el servidor; se excluye solo la entrada activa que difiere entre las dos rutas comparadas.
    expect(react.navegacion.filter((etiqueta) => !/programa general|programación semanal/i.test(etiqueta)))
      .toEqual(legado.navegacion.filter((etiqueta) => !/programa general|programación semanal/i.test(etiqueta)));
  });
}

test('el estado del riel se comparte entre legado y React', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto(ENTRAR);
  await page.goto(BASE_URL + '/programacion-semanal');
  await page.evaluate(() => localStorage.setItem('aia-sidebar-state', 'expanded'));
  await page.goto(BASE_URL + '/programa-general');

  await expect(page.locator('aside.aia-navigation--sidebar')).toHaveAttribute('data-sidebar-state', 'expanded');
});

test('la semana vive en la barra de contexto, como en el legado', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto(ENTRAR);
  await page.goto(BASE_URL + '/programa-general');

  await expect(page.locator('aside.aia-navigation--sidebar select')).toHaveCount(0);
  await expect(page.locator('aside.aia-navigation--sidebar .aia-sidebar__week')).toHaveCount(0);
  const chip = page.locator('#shellContextBar .context-week-chip');
  await expect(chip).toBeVisible();
  for (const icono of ['calendar', 'chevron-down']) {
    const glifo = chip.locator(`.aia-icon--${icono} svg.aia-icon__glyph`);
    await expect(glifo).toBeVisible();
    const caja = await glifo.boundingBox();
    expect(caja?.width).toBeGreaterThan(0);
    expect(caja?.height).toBeGreaterThan(0);
  }
  await chip.click();
  await expect(page.getByRole('menu')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(chip).toBeFocused();
});
