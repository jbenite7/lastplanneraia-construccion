import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { arranqueAnonimo, arranqueCambioClave, simularSesion } from './support/login-react-fixtures.mjs';

/**
 * Errores de `/api/auth/*` contra la forma REAL del servidor (plan 2026-09-17).
 *
 * `AuthApiController::respondError()` emitía `redirect: null`, `correlationId: null` y `campos: []`,
 * y cualquiera de esos invalidaba el cuerpo entero en `pedir()`: el 422 de la política de claves
 * llegaba a `CambioClaveObligatorio` sin razón por campo. Los dobles escritos a mano tenían la forma
 * buena y lo ocultaban. Aquí no hay dobles de `/api/auth/*` escritos a mano:
 *
 * - **403:** el POST llega al servidor real con el CSRF de `arranqueAnonimo()`, que esta sesión de
 *   PHP no emitió; responde 403 antes de leer credenciales. Solo `/api/session` va simulado.
 * - **422 de cambio de clave:** no se puede provocar desde la UI sin una cuenta con
 *   `force_password_change=1` y su clave, y crearla muta datos (prohibido). Se sirve el cuerpo que
 *   `tests/test_api_auth_contract.php` capturó del servidor (con sesión forjada) en
 *   `tests/fixtures/api-auth-error-bodies.json` — el archivo, no una copia — y ese test falla si el
 *   servidor deja de emitirlo igual.
 */

const CUERPOS_REALES = JSON.parse(
  readFileSync(new URL('../fixtures/api-auth-error-bodies.json', import.meta.url), 'utf8'),
);

test.describe('errores de /api/auth con la forma real del servidor', () => {
  test('403 real: el login recibe code y message del servidor, sin claves nulas ni vacías, y la pantalla lo trata como CSRF', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);

    await page.goto('/login');
    await page.getByLabel('Usuario').fill('cuenta-que-no-existe');
    await page.getByLabel('Contraseña', { exact: true }).fill('valor-de-prueba');
    const respuesta = page.waitForResponse(
      (r) => r.url().includes('/api/auth/login') && r.request().method() === 'POST',
    );
    await page.getByRole('button', { name: 'Entrar' }).click();

    const recibida = await respuesta;
    expect(recibida.status()).toBe(403);
    const cuerpo = await recibida.json();
    expect(cuerpo).toEqual(CUERPOS_REALES['403_csrf_invalid'].cuerpo);
    expect(cuerpo.error).toEqual({ codigo: 'csrf_invalid', mensaje: 'Solicitud no permitida.' });
    expect(cuerpo).not.toHaveProperty('redirect');
    expect(cuerpo).not.toHaveProperty('correlationId');
    expect(cuerpo).not.toHaveProperty('fieldErrors');

    const alerta = page.getByRole('alert');
    await expect(alerta).toContainText('Tu sesión de formulario ya no es válida');
    await expect(alerta).not.toContainText('/api/');
    await expect(alerta).not.toContainText('respondió');
  });

  test('422 de política de claves: la razón por campo del servidor llega al campo «Nueva contraseña»', async ({ page }) => {
    const real = CUERPOS_REALES['422_password_change_validation_error'];
    await simularSesion(page, [arranqueCambioClave()]);
    await page.route('**/api/auth/password/change', (route) =>
      route.fulfill({ status: real.status, contentType: 'application/json', body: JSON.stringify(real.cuerpo) }),
    );

    await page.goto('/login');
    await page.getByLabel('Nueva contraseña').fill('debil');
    await page.getByLabel('Confirmar contraseña').fill('debil');
    await page.getByRole('button', { name: 'Actualizar y continuar' }).click();

    const campo = page.getByLabel('Nueva contraseña');
    await expect(campo).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#clave-nueva-error')).toHaveText(real.cuerpo.error.campos.password);
    await expect(campo).toBeFocused();
  });
});
