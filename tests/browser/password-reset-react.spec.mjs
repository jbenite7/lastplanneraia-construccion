import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { WCAG_TAGS } from './support/accessibility.mjs';
import {
  TEMAS,
  VIEWPORTS,
  arranqueAnonimo,
  arranqueAutenticadoSinProyecto,
  arranqueCambioClave,
  fijarTema,
  simularSesion,
} from './support/login-react-fixtures.mjs';

/**
 * Comportamiento y accesibilidad de la pantalla de restablecimiento de contraseña React, servida
 * en `/password/reset` (S03, Tarea 9).
 *
 * **Casi todo el backend está simulado con `page.route()`**: ningún test cambia una contraseña,
 * usa un token real ni escribe en la base. Los dobles de error NO se escriben a mano: se leen de
 * `tests/fixtures/api-password-reset-error-bodies.json`, capturado del controlador real — la lección
 * de S02, donde dobles inventados ocultaron que un 403 real mostraba la ruta y el status.
 *
 * La excepción, a propósito, es el bloque «servidor real» (correcciones §11): deja pasar
 * `/api/auth/password/reset/validate` al contenedor con un token sintético que no existe (solo
 * lectura: `findValidToken` es un SELECT) o con un CSRF que la sesión PHP no emitió (403 antes de
 * leer el cuerpo). Ninguno de los dos llega a `update`.
 */

const RUTA_VALIDATE = '/api/auth/password/reset/validate';
const RUTA_UPDATE = '/api/auth/password/reset';
// Sintético: forma válida (64 hex) pero no existe en `password_reset_tokens`.
const TOKEN = 'a'.repeat(64);
const CLAVE = 'Abcdef!';
const TITULO = 'Define tu nueva contraseña';
const MENSAJE_INVALIDO = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
const MENSAJE_TECNICO_VALIDAR = 'No pudimos validar el enlace. Intenta nuevamente.';
const MENSAJE_NO_CONFIRMADO =
  'No pudimos confirmar el cambio. Intenta iniciar sesión; si no funciona, solicita un enlace nuevo.';
const AVISO_EXITO = 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.';

const CUERPOS = JSON.parse(
  readFileSync(new URL('../fixtures/api-password-reset-error-bodies.json', import.meta.url), 'utf8'),
);

function fixture(nombre) {
  const { status, cuerpo } = CUERPOS[nombre];
  return { status, cuerpo };
}

const VALIDO = { status: 200, cuerpo: { success: true, state: 'valid' } };
const EXITO = {
  status: 200,
  cuerpo: { success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1' },
};

/**
 * Doble de un POST de restablecimiento. `responder(n)` devuelve `{status, cuerpo}`, `'abort'`
 * (fallo de red) o `{status, crudo}` (cuerpo sin JSON). El cuerpo enviado se compara pero no se
 * guarda: lleva la contraseña tecleada y las trazas se escriben a disco si un test falla.
 */
async function simularPost(page, ruta, esperado, responder) {
  const llamadas = { total: 0 };
  await page.route(`**${ruta}`, async (route) => {
    if (route.request().method() !== 'POST') {
      await route.fallback();
      return;
    }
    expect(route.request().postDataJSON()).toEqual(esperado);
    llamadas.total += 1;
    const respuesta = responder(llamadas.total);
    if (respuesta === 'abort') {
      await route.abort('failed');
      return;
    }
    await route.fulfill({
      status: respuesta.status,
      contentType: 'application/json',
      body: respuesta.crudo ?? JSON.stringify(respuesta.cuerpo),
    });
  });
  return llamadas;
}

const simularValidacion = (page, responder) => simularPost(page, RUTA_VALIDATE, { token: TOKEN }, responder);
const simularUpdate = (page, responder, clave = CLAVE, confirmacion = clave) =>
  simularPost(page, RUTA_UPDATE, { token: TOKEN, password: clave, confirmPassword: confirmacion }, responder);

async function esperarTitulo(page) {
  await expect(page.getByRole('heading', { level: 1, name: TITULO })).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
}

async function esperarFormulario(page) {
  await esperarTitulo(page);
  await expect(page.getByLabel('Nueva contraseña')).toBeVisible();
}

async function esperarInvalido(page) {
  await esperarTitulo(page);
  await expect(page.getByRole('alert')).toHaveText(MENSAJE_INVALIDO);
  await expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Volver al inicio de sesión' })).toBeVisible();
  await expect(page.locator('form')).toHaveCount(0);
}

async function textoVisible(page) {
  return page.evaluate(() => document.body.innerText);
}

const campoClave = (page) => page.locator('#reset-password');
const campoConfirmacion = (page) => page.locator('#reset-confirm');
const alternador = (page, id) => page.locator(`.aia-auth__campo-icono:has(#${id}) .aia-auth__clave-toggle`);

async function llenar(page, clave = CLAVE, confirmacion = clave) {
  await campoClave(page).fill(clave);
  await campoConfirmacion(page).fill(confirmacion);
}

test.describe('restablecimiento React — enlace', () => {
  for (const [caso, query] of [
    ['sin token', ''],
    ['token repetido', `?token=${TOKEN}&token=${TOKEN}`],
    ['token mal formado', '?token=abc123'],
    ['token en mayúsculas', `?token=${'A'.repeat(64)}`],
  ]) {
    test(`${caso}: estado inválido sin llamar a la API`, async ({ page }) => {
      await simularSesion(page, [arranqueAnonimo()]);
      const validar = await simularValidacion(page, () => VALIDO);
      const peticiones = [];
      page.on('request', (r) => {
        const { pathname } = new URL(r.url());
        if (pathname.startsWith('/api/auth/')) peticiones.push(pathname);
      });

      await page.goto(`/password/reset${query}`);
      await esperarInvalido(page);
      await expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toBeFocused();
      await expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toHaveAttribute('href', '/password/forgot');
      expect(validar.total).toBe(0);
      expect(peticiones).toEqual([]);
    });
  }

  test('carga: muestra «Validando enlace…» mientras el servidor no responde', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    let soltar;
    const pendiente = new Promise((resolve) => {
      soltar = resolve;
    });
    await page.route(`**${RUTA_VALIDATE}`, async (route) => {
      await pendiente;
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(VALIDO.cuerpo) });
    });

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarTitulo(page);
    await expect(page.getByRole('status')).toHaveText('Validando enlace…');
    await expect(page.locator('form')).toHaveCount(0);
    soltar();
    await esperarFormulario(page);
  });

  test('válido: formulario con política, placeholders, ayuda asociada y botón de llave', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const validar = await simularValidacion(page, () => VALIDO);

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await expect(page.getByText('Usa al menos 6 caracteres, una mayúscula y un carácter especial.')).toBeVisible();
    await expect(campoClave(page)).toHaveAttribute('placeholder', 'Nueva contraseña');
    await expect(campoConfirmacion(page)).toHaveAttribute('placeholder', 'Confirma tu contraseña');
    await expect(campoClave(page)).toHaveAttribute('aria-describedby', 'reset-password-policy');
    await expect(page.locator('#reset-password-policy')).toHaveText('Mínimo 6 caracteres, una mayúscula y un carácter especial.');
    await expect(page.getByRole('button', { name: 'Actualizar contraseña' })).toBeVisible();
    await expect(campoClave(page)).toHaveAttribute('type', 'password');
    await expect(campoConfirmacion(page)).toHaveAttribute('type', 'password');
    expect(validar.total).toBe(1);
    expect(await textoVisible(page)).not.toContain(TOKEN);
  });

  test('inválido desde la API: misma alerta pública, sin token ni identidad en pantalla', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => ({
      status: 200,
      cuerpo: { success: true, state: 'invalid', message: MENSAJE_INVALIDO },
    }));

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarInvalido(page);
    await expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toBeFocused();
    const texto = await textoVisible(page);
    expect(texto).not.toContain(TOKEN);
    expect(texto).not.toContain('@');
  });

  for (const [nombre, arranque] of [
    ['anónima', arranqueAnonimo()],
    ['autenticada', arranqueAutenticadoSinProyecto()],
    ['con cambio de clave pendiente', arranqueCambioClave()],
  ]) {
    test(`sesión ${nombre}: la misma pantalla, sin usuario ni proyecto`, async ({ page }) => {
      await simularSesion(page, [arranque]);
      await simularValidacion(page, () => VALIDO);

      await page.goto(`/password/reset?token=${TOKEN}`);
      await esperarFormulario(page);
      const texto = await textoVisible(page);
      expect(texto).not.toContain('Prueba Residente');
      expect(texto).not.toContain('test.R');
      expect(new URL(page.url()).pathname).toBe('/password/reset');
    });
  }

  test('validación 403: «Actualizar sesión» pide una sesión nueva y revalida una sola vez, solo tras el clic', async ({ page }) => {
    const sesion = await simularSesion(page, [arranqueAnonimo()]);
    const validar = await simularValidacion(page, (n) => (n === 1 ? fixture('403_csrf_invalid') : VALIDO));

    await page.goto(`/password/reset?token=${TOKEN}`);
    const accion = page.getByRole('button', { name: 'Actualizar sesión' });
    await expect(accion).toBeFocused();
    await expect(page.getByRole('alert')).toHaveText(CUERPOS['403_csrf_invalid'].cuerpo.message);
    await page.waitForTimeout(300);
    expect(sesion.total).toBe(1);
    expect(validar.total).toBe(1);

    await accion.click();
    await esperarFormulario(page);
    expect(sesion.total).toBe(2);
    expect(validar.total).toBe(2);
  });

  test('validación 503: mensaje del servidor, sin reintento automático', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const validar = await simularValidacion(page, (n) => (n === 1 ? fixture('503_reset_validate_unavailable') : VALIDO));

    await page.goto(`/password/reset?token=${TOKEN}`);
    await expect(page.getByRole('alert')).toHaveText(CUERPOS['503_reset_validate_unavailable'].cuerpo.message);
    await expect(page.getByRole('button', { name: 'Intentar nuevamente' })).toBeFocused();
    await page.waitForTimeout(300);
    expect(validar.total).toBe(1);

    await page.getByRole('button', { name: 'Intentar nuevamente' }).click();
    await esperarFormulario(page);
    expect(validar.total).toBe(2);
  });

  for (const [caso, responder] of [
    ['red caída', () => 'abort'],
    ['200 malformado', () => ({ status: 200, crudo: '<html>oops</html>' })],
  ]) {
    test(`validación con ${caso}: aviso técnico fijo, sin detalle`, async ({ page }) => {
      await simularSesion(page, [arranqueAnonimo()]);
      await simularValidacion(page, responder);

      await page.goto(`/password/reset?token=${TOKEN}`);
      const alerta = page.getByRole('alert');
      await expect(alerta).toHaveText(MENSAJE_TECNICO_VALIDAR);
      await expect(alerta).not.toContainText('/api/');
      await expect(alerta).not.toContainText('oops');
    });
  }
});

test.describe('restablecimiento React — envío', () => {
  test('éxito: doble clic envía una vez, llega a /login con el aviso y el token sale del historial', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);
    const updates = await simularUpdate(page, () => EXITO);

    // Entrada previa real en el historial: sin ella, `goBack` cae a about:blank y no prueba nada.
    await page.goto('/login');
    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();
    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await llenar(page);
    await page.getByRole('button', { name: 'Actualizar contraseña' }).dblclick();

    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();
    await expect(page.getByRole('status')).toHaveText(AVISO_EXITO);
    await expect.poll(() => new URL(page.url()).pathname + new URL(page.url()).search).toBe('/login');
    expect(updates.total).toBe(1);

    await page.goBack();
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    expect(page.url()).not.toContain('token=');
    expect(new URL(page.url()).pathname).toBe('/login');
  });

  test('Enter en la confirmación envía una sola vez', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);
    const updates = await simularUpdate(page, () => EXITO);

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await llenar(page);
    await campoConfirmacion(page).press('Enter');
    await expect(page.getByRole('status')).toHaveText(AVISO_EXITO);
    expect(updates.total).toBe(1);
  });

  for (const [caso, clave, confirmacion, campo, mensaje] of [
    ['longitud', 'Ab!', 'Ab!', 'reset-password', 'La contraseña debe tener al menos 6 caracteres'],
    ['sin mayúscula', 'abcdef!', 'abcdef!', 'reset-password', 'Debe contener al menos una letra mayúscula'],
    ['sin carácter especial', 'Abcdefg', 'Abcdefg', 'reset-password', 'Debe contener al menos un carácter especial (!@#$%...)'],
    ['no coinciden', 'Abcdef!', 'Abcdef?', 'reset-confirm', 'Las contraseñas no coinciden'],
  ]) {
    test(`política local (${caso}): mensaje en el campo, foco y cero envíos`, async ({ page }) => {
      await simularSesion(page, [arranqueAnonimo()]);
      await simularValidacion(page, () => VALIDO);
      const updates = await simularUpdate(page, () => EXITO);

      await page.goto(`/password/reset?token=${TOKEN}`);
      await esperarFormulario(page);
      await llenar(page, clave, confirmacion);
      await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

      await expect(page.locator(`#${campo}-error`)).toHaveText(mensaje);
      await expect(page.locator(`#${campo}`)).toBeFocused();
      await expect(page.locator(`#${campo}`)).toHaveAttribute('aria-invalid', 'true');
      await expect(page.locator(`#${campo}`)).toHaveAttribute('aria-describedby', new RegExp(`${campo}-error`));
      expect(updates.total).toBe(0);
    });
  }

  for (const [fixtureNombre, campo] of [
    ['422_reset_password_validation_error', 'reset-password'],
    ['422_reset_confirm_validation_error', 'reset-confirm'],
  ]) {
    test(`422 del servidor (${campo}): secretos limpios, mensaje del servidor y foco en el campo`, async ({ page }) => {
      await simularSesion(page, [arranqueAnonimo()]);
      await simularValidacion(page, () => VALIDO);
      const updates = await simularUpdate(page, () => fixture(fixtureNombre));

      await page.goto(`/password/reset?token=${TOKEN}`);
      await esperarFormulario(page);
      await llenar(page);
      await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

      await expect(page.locator(`#${campo}-error`)).toHaveText(CUERPOS[fixtureNombre].cuerpo.message);
      await expect(page.locator(`#${campo}`)).toBeFocused();
      await expect(campoClave(page)).toHaveValue('');
      await expect(campoConfirmacion(page)).toHaveValue('');
      expect(updates.total).toBe(1);
    });
  }

  test('403 del envío: secretos limpios, «Actualizar sesión» revalida sin reenviar', async ({ page }) => {
    const sesion = await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);
    const updates = await simularUpdate(page, () => fixture('403_csrf_invalid'));

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await llenar(page);
    await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

    const accion = page.getByRole('button', { name: 'Actualizar sesión' });
    await expect(accion).toBeFocused();
    await expect(page.getByRole('alert')).toContainText(CUERPOS['403_csrf_invalid'].cuerpo.message);
    await expect(campoClave(page)).toHaveValue('');
    await expect(campoConfirmacion(page)).toHaveValue('');
    const sesionesAntes = sesion.total;

    await accion.click();
    await expect(accion).toHaveCount(0);
    await expect(campoClave(page)).toBeFocused();
    await page.waitForTimeout(300);
    expect(sesion.total).toBe(sesionesAntes + 1);
    expect(updates.total).toBe(1);
  });

  test('410: el formulario desaparece y el foco va a «Solicitar un nuevo enlace»', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);
    await simularUpdate(page, () => fixture('410_reset_link_invalid'));

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await llenar(page);
    await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

    await esperarInvalido(page);
    await expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toBeFocused();
  });

  test('503 del envío: mensaje del servidor, alerta enfocada, secretos limpios y sin reintento', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);
    const updates = await simularUpdate(page, () => fixture('503_reset_unavailable'));

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await llenar(page);
    await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

    const alerta = page.locator('p[role="alert"].aia-alert');
    await expect(alerta).toHaveText(CUERPOS['503_reset_unavailable'].cuerpo.message);
    await expect(alerta).toBeFocused();
    await expect(campoClave(page)).toHaveValue('');
    await expect(campoConfirmacion(page)).toHaveValue('');
    await page.waitForTimeout(400);
    expect(updates.total).toBe(1);
  });

  for (const [caso, responder] of [
    ['red caída', () => 'abort'],
    ['200 malformado', () => ({ status: 200, crudo: JSON.stringify({ success: true, redirect: 'https://evil.test/' }) })],
    ['200 sin JSON', () => ({ status: 200, crudo: 'Fatal error in /var/www' })],
  ]) {
    test(`envío con ${caso}: aviso ambiguo seguro, sin afirmar éxito ni filtrar detalle`, async ({ page }) => {
      await simularSesion(page, [arranqueAnonimo()]);
      await simularValidacion(page, () => VALIDO);
      const updates = await simularUpdate(page, responder);

      await page.goto(`/password/reset?token=${TOKEN}`);
      await esperarFormulario(page);
      await llenar(page);
      await page.getByRole('button', { name: 'Actualizar contraseña' }).click();

      const alerta = page.locator('p[role="alert"].aia-alert');
      await expect(alerta).toHaveText(MENSAJE_NO_CONFIRMADO);
      await expect(alerta).toBeFocused();
      await expect(campoClave(page)).toHaveValue('');
      const texto = await textoVisible(page);
      expect(texto).not.toContain(TOKEN);
      expect(texto).not.toContain('/var/www');
      expect(texto).not.toContain('evil');
      expect(new URL(page.url()).pathname).toBe('/password/reset');
      expect(updates.total).toBe(1);
    });
  }
});

test.describe('restablecimiento React — teclado y navegación', () => {
  test('alternadores independientes: nombre y aria-pressed dinámicos, el foco vuelve al campo', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    const primero = alternador(page, 'reset-password');
    const segundo = alternador(page, 'reset-confirm');

    await primero.click();
    await expect(campoClave(page)).toHaveAttribute('type', 'text');
    await expect(campoConfirmacion(page)).toHaveAttribute('type', 'password');
    await expect(primero).toHaveAttribute('aria-label', 'Ocultar contraseña');
    await expect(primero).toHaveAttribute('aria-pressed', 'true');
    await expect(segundo).toHaveAttribute('aria-pressed', 'false');
    await expect(campoClave(page)).toBeFocused();

    // Teclado: Tab hasta el segundo alternador y Enter.
    await campoConfirmacion(page).focus();
    await page.keyboard.press('Tab');
    await expect(segundo).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(campoConfirmacion(page)).toHaveAttribute('type', 'text');
    await expect(segundo).toHaveAttribute('aria-label', 'Ocultar contraseña');
    await expect(campoConfirmacion(page)).toBeFocused();
    await expect(campoClave(page)).toHaveAttribute('type', 'text');
  });

  test('orden de tabulación lógico: clave → alternador → confirmación → alternador → botón → volver', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularValidacion(page, () => VALIDO);

    await page.goto(`/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await campoClave(page).focus();
    const orden = [
      alternador(page, 'reset-password'),
      campoConfirmacion(page),
      alternador(page, 'reset-confirm'),
      page.getByRole('button', { name: 'Actualizar contraseña' }),
      page.getByRole('link', { name: 'Volver al inicio de sesión' }),
    ];
    for (const destino of orden) {
      await page.keyboard.press('Tab');
      await expect(destino).toBeFocused();
    }
    for (const destino of [...orden].reverse().slice(1)) {
      await page.keyboard.press('Shift+Tab');
      await expect(destino).toBeFocused();
    }
  });

  test('navegación: «Solicitar un nuevo enlace» llega a S02 y «Volver al inicio de sesión» a S01', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);

    await page.goto('/password/reset');
    await esperarInvalido(page);
    await page.getByRole('link', { name: 'Solicitar un nuevo enlace' }).click();
    await expect(page.getByRole('heading', { level: 1, name: 'Restablecer contraseña' })).toBeVisible();
    expect(new URL(page.url()).pathname).toBe('/password/forgot');

    await page.goto('/password/reset');
    await esperarInvalido(page);
    await page.getByRole('link', { name: 'Volver al inicio de sesión' }).click();
    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();
    expect(new URL(page.url()).pathname).toBe('/login');
  });

  test('recargar con token válido vuelve a validar una vez y conserva la pantalla', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const validar = await simularValidacion(page, () => VALIDO);

    await page.goto(`/app/password/reset?token=${TOKEN}`);
    await esperarFormulario(page);
    await page.reload();
    await esperarFormulario(page);
    expect(validar.total).toBe(2);
  });
});

/**
 * Casos contra el servidor real (correcciones §11): sin doble de `/api/auth/password/reset*`.
 * Ninguno llega a `update`; el token es sintético y la validación es de solo lectura.
 */
test.describe('restablecimiento React — servidor real', () => {
  test('token sintético inexistente: el servidor responde invalid y la pantalla ofrece un enlace nuevo', async ({ page }) => {
    // Tampoco se simula `/api/session`: el CSRF es el que emitió la sesión PHP real.
    await page.goto('/login');
    const respuesta = page.waitForResponse((r) => r.url().includes(RUTA_VALIDATE));
    await page.goto(`/password/reset?token=${TOKEN}`);
    const r = await respuesta;
    expect(r.status()).toBe(200);
    expect(await r.json()).toEqual({ success: true, state: 'invalid', message: MENSAJE_INVALIDO });

    await esperarInvalido(page);
    const texto = await textoVisible(page);
    expect(texto).not.toContain(TOKEN);
    expect(texto).not.toContain('/api/');
  });

  test('token mal formado: inválido sin ninguna llamada a la API de restablecimiento', async ({ page }) => {
    const peticiones = [];
    page.on('request', (r) => {
      if (new URL(r.url()).pathname.startsWith('/api/auth/')) peticiones.push(r.url());
    });
    await page.goto('/password/reset?token=no-es-un-token');
    await esperarInvalido(page);
    await page.waitForTimeout(300);
    expect(peticiones).toEqual([]);
  });

  test('403 real: CSRF desconocido al validar muestra el mensaje humano y «Actualizar sesión»', async ({ page }) => {
    // Solo `/api/session` simulado: su CSRF (`a`×64) no lo emitió esta sesión PHP, así que el
    // controlador real responde 403 antes de leer el cuerpo.
    await simularSesion(page, [arranqueAnonimo()]);
    const respuesta = page.waitForResponse((r) => r.url().includes(RUTA_VALIDATE));
    await page.goto(`/password/reset?token=${TOKEN}`);
    const r = await respuesta;
    expect(r.status()).toBe(403);
    expect(await r.json()).toEqual(CUERPOS['403_csrf_invalid'].cuerpo);

    const alerta = page.getByRole('alert');
    await expect(alerta).toHaveText(CUERPOS['403_csrf_invalid'].cuerpo.message);
    await expect(alerta).not.toContainText('/api/');
    await expect(alerta).not.toContainText('respondió');
    await expect(alerta).not.toContainText('403');
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toBeFocused();
  });
});

async function auditarPresentacion(page, { tema, viewport, ruta, esperar, peticionesPermitidas }) {
  const erroresDeConsola = [];
  page.on('console', (mensaje) => {
    if (mensaje.type() === 'error') erroresDeConsola.push(mensaje.text());
  });
  page.on('pageerror', (error) => erroresDeConsola.push(String(error)));
  const peticiones = [];
  page.on('request', (request) => {
    const url = new URL(request.url());
    if (url.pathname.startsWith('/api/')) peticiones.push(url.pathname);
  });

  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await fijarTema(page, tema);
  await simularSesion(page, [arranqueAnonimo()]);
  await simularValidacion(page, () => VALIDO);

  await page.goto(ruta);
  await esperar(page);
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', tema === 'claro' ? 'light' : 'dark');

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);

  // Zoom 200 % (WCAG 1.4.4/1.4.10): mitad de ancho, mismo criterio que S01/S02.
  await page.setViewportSize({ width: Math.round(viewport.width / 2), height: Math.round(viewport.height / 2) });
  const overflowConZoom = await page.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
  );
  expect(overflowConZoom).toBeLessThanOrEqual(1);
  await page.setViewportSize({ width: viewport.width, height: viewport.height });

  const resultados = await new AxeBuilder({ page }).withTags(WCAG_TAGS).analyze();
  const bloqueantes = resultados.violations.filter(({ impact }) => impact === 'critical' || impact === 'serious');
  expect(bloqueantes.map(({ id, impact, nodes }) => `${id} (${impact}) ×${nodes.length}`)).toEqual([]);

  expect(erroresDeConsola).toEqual([]);
  expect(peticiones.every((p) => peticionesPermitidas.includes(p))).toBe(true);
}

test.describe('restablecimiento React — accesibilidad en la matriz', () => {
  for (const tema of TEMAS) {
    for (const viewport of VIEWPORTS) {
      test(`válido ${tema} ${viewport.nombre}: un h1, foco visible, sin scroll horizontal, sin violación Axe`, async ({ page }) => {
        await auditarPresentacion(page, {
          tema,
          viewport,
          ruta: `/password/reset?token=${TOKEN}`,
          esperar: async (p) => {
            await esperarFormulario(p);
            await campoClave(p).focus();
            const anillo = await p.evaluate(() => {
              const estilo = getComputedStyle(document.activeElement);
              return { w: parseFloat(estilo.outlineWidth) || 0, s: estilo.outlineStyle, b: estilo.boxShadow };
            });
            expect((anillo.w > 0 && anillo.s !== 'none') || anillo.b !== 'none').toBe(true);
            await p.keyboard.press('Shift+Tab');
            await expect(p.getByRole('button', { name: /Cambiar a tema/ })).toBeFocused();
          },
          peticionesPermitidas: ['/api/session', RUTA_VALIDATE],
        });
      });
    }
  }

  for (const tema of TEMAS) {
    for (const viewport of VIEWPORTS.filter((v) => ['1180x820', '390x844'].includes(v.nombre))) {
      test(`inválido ${tema} ${viewport.nombre}: sin scroll horizontal y sin violación Axe`, async ({ page }) => {
        await auditarPresentacion(page, {
          tema,
          viewport,
          ruta: '/password/reset',
          esperar: esperarInvalido,
          peticionesPermitidas: ['/api/session'],
        });
      });
    }
  }
});
