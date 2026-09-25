import { expect, test } from '@playwright/test';

const ENTRAR = 'http://localhost:8081/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto');

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

    const legado = await medirRiel(page, 'http://localhost:8081/programacion-semanal');
    const react = await medirRiel(page, 'http://localhost:8081/programa-general');

    expect(legado.estado).toBe('collapsed');
    expect(react.estado).toBe('collapsed');
    expect(Math.abs(react.ancho - legado.ancho)).toBeLessThanOrEqual(2);
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
  await page.goto('http://localhost:8081/programacion-semanal');
  await page.evaluate(() => localStorage.setItem('aia-sidebar-state', 'expanded'));
  await page.goto('http://localhost:8081/programa-general');

  await expect(page.locator('aside.aia-navigation--sidebar')).toHaveAttribute('data-sidebar-state', 'expanded');
});

test('la semana vive en la barra de contexto, como en el legado', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto(ENTRAR);
  await page.goto('http://localhost:8081/programa-general');

  await expect(page.locator('aside.aia-navigation--sidebar select')).toHaveCount(0);
  await expect(page.locator('aside.aia-navigation--sidebar .aia-sidebar__week')).toHaveCount(0);
  const chip = page.locator('#shellContextBar .context-week-chip');
  await expect(chip).toBeVisible();
  await chip.click();
  await expect(page.getByRole('menu')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(chip).toBeFocused();
});
