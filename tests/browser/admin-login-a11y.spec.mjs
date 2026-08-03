import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';

// `/admin/login` tenia sus campos sin <label> (solo placeholder, que desaparece al escribir) y
// con autocomplete vacio, asi que ningun gestor de contraseñas los rellenaba. `/login` de la app
// ya hacia las dos cosas bien; este spec exige la misma calidad en el admin.

test('el acceso de admin etiqueta sus campos y declara autocomplete', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/admin/login`);
  for (const [name, ac] of [['usuario', 'username'], ['password', 'current-password']]) {
    const campo = page.locator(`input[name="${name}"]`);
    await expect(campo).toHaveAttribute('autocomplete', ac);
    const id = await campo.getAttribute('id');
    expect(id, `el campo ${name} necesita id para que <label for> lo alcance`).toBeTruthy();
    await expect(page.locator(`label[for="${id}"]`)).toHaveCount(1);
  }
});
