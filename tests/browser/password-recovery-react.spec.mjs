import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
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
 * Comportamiento y accesibilidad de la pantalla de recuperación de contraseña React,
 * servida en `/password/forgot` (S02, Tarea 9). Ambos alias (`/password/forgot` y
 * `/app/password/forgot`, ver `rutas.tsx`) resuelven la misma ruta pública ANTES de la
 * máquina de estados por sesión, así que se ve igual anónimo, con cambio de clave
 * pendiente o autenticado — la sección "Sesión" ejerce las tres.
 *
 * **Casi todo el backend está simulado con `page.route()`.** Ningún test toca SMTP ni la base
 * de datos. La excepción, a propósito, es el caso «403 real»: deja pasar
 * `/api/auth/password/forgot` al servidor con un CSRF que no conoce, que responde 403 antes de
 * llegar al servicio de correo. Existe porque los dobles de error, escritos a mano, emitían un
 * bloque `error` que el controlador real no emitía, y ocultaron que un 403/503 real mostraba
 * «/api/auth/password/forgot respondió 403» (ola final de la revisión S02). Los dobles de error
 * de este archivo copian la forma real con `cuerpoErrorRecuperacion()`.
 */

const RUTA_FORGOT = '/api/auth/password/forgot';
const CORREO = 'persona@example.test';
const MENSAJE_GENERICO =
  'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';
const MENSAJE_TECNICO_RED = 'No pudimos conectar. Intenta nuevamente.';

/** Doble de `/api/auth/password/forgot` que además valida el cuerpo enviado. */
async function instalarRecuperacion(page, responder) {
  const llamadas = { total: 0 };
  await page.route(`**${RUTA_FORGOT}`, async (route) => {
    if (route.request().method() !== 'POST') {
      await route.fallback();
      return;
    }
    expect(route.request().postDataJSON()).toEqual({ email: CORREO });
    llamadas.total += 1;
    const { status, cuerpo } = responder(llamadas.total);
    await route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(cuerpo) });
  });
  return llamadas;
}

/**
 * Cuerpo de error tal como lo emite `PasswordRecoveryApiController::respondError()`: claves planas
 * con `fieldErrors` como listas y bloque `error` con `campos` como strings, que solo existe si hay
 * errores de campo. No se usa `cuerpoError()` de las fixtures del login: su `fieldErrors` es plano
 * y no es la forma de este endpoint.
 */
function cuerpoErrorRecuperacion({ code, message, campos = null }) {
  return {
    success: false,
    code,
    message,
    ...(campos ? { fieldErrors: Object.fromEntries(Object.entries(campos).map(([campo, texto]) => [campo, [texto]])) } : {}),
    error: { codigo: code, mensaje: message, ...(campos ? { campos } : {}) },
  };
}

async function esperarPantallaDeRecuperacion(page) {
  await expect(page.getByRole('heading', { level: 1, name: 'Restablecer contraseña' })).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
}

test.describe('recuperación React — comportamiento', () => {
  test('anónimo: un solo envío llega al servidor aunque se haga doble clic', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({
      status: 200,
      cuerpo: { success: true, message: MENSAJE_GENERICO },
    }));

    await page.goto('/password/forgot');
    await esperarPantallaDeRecuperacion(page);
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).dblclick();

    await expect(page.getByRole('status')).toContainText(MENSAJE_GENERICO);
    expect(forgot.total).toBe(1);
    // El `disabled` del envío suelta el foco: tras el éxito vuelve al campo, nunca a BODY.
    await expect(page.getByLabel('Correo electrónico')).toBeFocused();
    // Tras el éxito el campo se limpia: no queda el correo tecleado en pantalla.
    await expect(page.getByLabel('Correo electrónico')).toHaveValue('');
  });

  test('alias: `/app/password/forgot` sirve la misma pantalla, estable entre recargo y navegación', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await instalarRecuperacion(page, () => ({ status: 200, cuerpo: { success: true, message: MENSAJE_GENERICO } }));

    // Historial real: /login -> /app/password/forgot, para que `goBack`/`goForward` tengan
    // adónde ir. Sin una entrada previa, `goBack` cae a `about:blank`, que no prueba nada.
    await page.goto('/login');
    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();

    await page.goto('/app/password/forgot');
    await esperarPantallaDeRecuperacion(page);

    // Observa cada `h1` que exista en el documento mientras se recarga la propia pantalla de
    // recuperación: si el login apareciera un instante durante ese recargo, este arreglo lo
    // atrapa aunque la aserción puntual de después no coincida con ese frame.
    await page.evaluate(() => {
      window.__tituloVistos = [];
      const registrar = () => {
        const h1 = document.querySelector('h1');
        if (h1 && !window.__tituloVistos.includes(h1.textContent)) {
          window.__tituloVistos.push(h1.textContent);
        }
      };
      new MutationObserver(registrar).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
      registrar();
    });
    await page.reload();
    await esperarPantallaDeRecuperacion(page);
    const vistosTrasRecargo = await page.evaluate(() => window.__tituloVistos || []);
    expect(vistosTrasRecargo).not.toContain('Bienvenido a Last Planner AIA');

    await page.goBack();
    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();
    expect(new URL(page.url()).pathname).toBe('/login');

    await page.goForward();
    await esperarPantallaDeRecuperacion(page);
    expect(new URL(page.url()).pathname).toBe('/app/password/forgot');
  });

  test('sesión: anónimo, cambio de clave pendiente y autenticado muestran S02 sin filtrar identidad', async ({ page }) => {
    for (const arranque of [arranqueAnonimo(), arranqueCambioClave(), arranqueAutenticadoSinProyecto()]) {
      await page.unrouteAll?.({ behavior: 'ignoreErrors' });
      await simularSesion(page, [arranque]);
      await instalarRecuperacion(page, () => ({ status: 200, cuerpo: { success: true, message: MENSAJE_GENERICO } }));

      await page.goto('/password/forgot');
      await esperarPantallaDeRecuperacion(page);

      const cuerpo = await page.locator('body').innerText();
      expect(cuerpo).not.toContain('test.R');
      expect(cuerpo).not.toContain('Prueba Residente');
    }
  });

  test('422: el correo queda, el error se asocia al campo y el foco entra al input', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({
      status: 422,
      cuerpo: cuerpoErrorRecuperacion({
        code: 'validation_error',
        message: 'Revisa el correo electrónico.',
        campos: { email: 'Ingresa un correo electrónico válido.' },
      }),
    }));

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();

    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    await expect(page.locator('#recuperacion-email-error')).toHaveText('Ingresa un correo electrónico válido.');
    await expect(page.getByLabel('Correo electrónico')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.getByLabel('Correo electrónico')).toHaveAttribute('aria-describedby', 'recuperacion-email-error');
    await expect(page.getByLabel('Correo electrónico')).toBeFocused();
    expect(forgot.total).toBe(1);
  });

  test('403: "Actualizar sesión" revalida sin reenviar el correo y conserva el correo tecleado con el foco de vuelta', async ({ page }) => {
    // Ronda de arreglo 1 (S02-UX-06): antes de `rutas.tsx` (fix del coordinador), `alRevalidar`
    // desmontaba `PantallaRecuperarClave` en favor de "Cargando…" mientras la sesión se
    // revalidaba, perdiendo el correo tecleado y el foco. Este caso ahora exige explícitamente
    // las tres garantías de la spec: correo conservado, foco de vuelta en el campo, sin un
    // segundo POST.
    const sesion = await simularSesion(page, [arranqueAnonimo(), arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({
      status: 403,
      cuerpo: cuerpoErrorRecuperacion({
        code: 'csrf_invalid',
        message: 'No fue posible validar la solicitud. Intenta nuevamente.',
      }),
    }));

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();

    await expect(page.getByRole('alert')).toContainText('No fue posible validar la solicitud. Intenta nuevamente.');
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toBeVisible();
    expect(forgot.total).toBe(1);
    expect(sesion.total).toBe(1);

    await page.getByRole('button', { name: 'Actualizar sesión' }).click();
    await expect.poll(() => sesion.total).toBe(2);
    expect(forgot.total).toBe(1);

    // Tras la revalidación exitosa: el correo sigue ahí y el foco vuelve al campo — nunca a BODY.
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    await expect(page.getByLabel('Correo electrónico')).toBeFocused();
  });

  test('403 real: el servidor rechaza un CSRF desconocido y la alerta muestra el mensaje humano, nunca la ruta ni el status', async ({ page }) => {
    // Sin doble de `/api/auth/password/forgot`: el POST llega al servidor real con el CSRF de
    // `arranqueAnonimo()`, que esta sesión de PHP no emitió. El controlador valida CSRF antes de
    // leer el body, así que responde 403 sin llegar al servicio de correo.
    await simularSesion(page, [arranqueAnonimo()]);

    await page.goto('/password/forgot');
    await esperarPantallaDeRecuperacion(page);
    await page.getByLabel('Correo electrónico').fill('nadie@example.invalid');
    const respuesta = page.waitForResponse((r) => r.url().includes(RUTA_FORGOT) && r.request().method() === 'POST');
    await page.getByRole('button', { name: 'Enviar enlace' }).click();
    expect((await respuesta).status()).toBe(403);

    const alerta = page.getByRole('alert');
    await expect(alerta).toContainText('No fue posible validar la solicitud. Intenta nuevamente.');
    await expect(alerta).not.toContainText('/api/');
    await expect(alerta).not.toContainText('respondió');
    await expect(alerta).not.toContainText('403');
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toBeFocused();
    await expect(page.getByLabel('Correo electrónico')).toHaveValue('nadie@example.invalid');
  });

  test('403 + revalidación fallida: la pantalla sigue montada, conserva el correo y avisa dentro', async ({ page }) => {
    // `/api/session`: arranque sano, 500 al revalidar, sano al reintentar.
    const sesion = await simularSesion(page, [arranqueAnonimo(), 500, arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({
      status: 403,
      cuerpo: cuerpoErrorRecuperacion({ code: 'csrf_invalid', message: 'No fue posible validar la solicitud. Intenta nuevamente.' }),
    }));

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();
    await page.getByRole('button', { name: 'Actualizar sesión' }).click();

    await expect(page.getByRole('alert')).toContainText('No pudimos actualizar la sesión. Intenta nuevamente.');
    await expect(page.getByText(/no pudimos conectar con la aplicación/i)).toHaveCount(0);
    await esperarPantallaDeRecuperacion(page);
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toBeFocused();
    expect(sesion.total).toBe(2);

    await page.getByRole('button', { name: 'Actualizar sesión' }).click();
    await expect.poll(() => sesion.total).toBe(3);
    await expect(page.getByRole('alert')).toHaveCount(0);
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    await expect(page.getByLabel('Correo electrónico')).toBeFocused();
    expect(forgot.total).toBe(1);
  });

  test('503: alerta con el copy del propio servidor, foco en la alerta y correo preservado', async ({ page }) => {
    const MENSAJE_SERVIDOR = 'El servicio de recuperación no está disponible en este momento.';
    await simularSesion(page, [arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({
      status: 503,
      cuerpo: cuerpoErrorRecuperacion({ code: 'recovery_unavailable', message: MENSAJE_SERVIDOR }),
    }));

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();

    await expect(page.getByRole('alert')).toHaveText(MENSAJE_SERVIDOR);
    await expect(page.getByRole('alert')).toBeFocused();
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    expect(forgot.total).toBe(1);
  });

  test('red: fallo de transporte muestra el aviso técnico fijo del frontend, sin reintento oculto', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    let intentos = 0;
    await page.route(`**${RUTA_FORGOT}`, async (route) => {
      intentos += 1;
      await route.abort('failed');
    });

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();

    await expect(page.getByRole('alert')).toHaveText(MENSAJE_TECNICO_RED);
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    expect(intentos).toBe(1);
  });

  test('contrato roto: un 200 sin `message` cae en el mismo aviso técnico, sin exponer detalle del servidor', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const forgot = await instalarRecuperacion(page, () => ({ status: 200, cuerpo: { success: true } }));

    await page.goto('/password/forgot');
    await page.getByLabel('Correo electrónico').fill(CORREO);
    await page.getByRole('button', { name: 'Enviar enlace' }).click();

    await expect(page.getByRole('alert')).toHaveText(MENSAJE_TECNICO_RED);
    await expect(page.getByLabel('Correo electrónico')).toHaveValue(CORREO);
    expect(forgot.total).toBe(1);
  });

  test('navegación: "Volver al inicio de sesión" llega a /login', async ({ page }) => {
    // Ruling del coordinador (ronda de arreglo 1, S02-UX-10): la spec solo exige llegar a
    // `/login` con su pantalla; no exige que la navegación evite una recarga documental.
    await simularSesion(page, [arranqueAnonimo(), arranqueAnonimo()]);
    await page.goto('/password/forgot');
    await esperarPantallaDeRecuperacion(page);

    await page.getByRole('link', { name: 'Volver al inicio de sesión' }).click();

    await expect(page.getByRole('heading', { level: 1, name: /^Bienvenido a Last Planner AIA$/ })).toBeVisible();
    expect(new URL(page.url()).pathname).toBe('/login');
  });
});

/**
 * Matriz de presentación: dos temas por cuatro anchos. Mismo piso de accesibilidad que
 * `login-react.spec.mjs` — los goldens viven aparte y necesitan aprobación explícita.
 */
test.describe('recuperación React — accesibilidad en la matriz', () => {
  for (const tema of TEMAS) {
    for (const viewport of VIEWPORTS) {
      test(`${tema} ${viewport.nombre}: un h1, foco visible, sin scroll horizontal, sin violación Axe y sin peticiones inesperadas`, async ({ page }) => {
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
        await instalarRecuperacion(page, () => ({ status: 200, cuerpo: { success: true, message: MENSAJE_GENERICO } }));

        await page.goto('/password/forgot');
        await esperarPantallaDeRecuperacion(page);

        await expect(page.locator('html')).toHaveAttribute('data-aia-theme', tema === 'claro' ? 'light' : 'dark');

        const medidas = await page.evaluate(() => {
          const raiz = document.documentElement;
          return { overflow: raiz.scrollWidth - raiz.clientWidth };
        });
        expect(medidas.overflow).toBeLessThanOrEqual(1);

        // Orden de tabulación: correo -> enviar -> volver al login (más el conmutador de tema
        // antes, en la cabecera). Se navega con teclado real, no con `.focus()`.
        await page.getByLabel('Correo electrónico').focus();
        const anillo = await page.evaluate(() => {
          const estilo = getComputedStyle(document.activeElement);
          return {
            outlineWidth: parseFloat(estilo.outlineWidth) || 0,
            outlineStyle: estilo.outlineStyle,
            boxShadow: estilo.boxShadow,
          };
        });
        expect(
          (anillo.outlineWidth > 0 && anillo.outlineStyle !== 'none') || anillo.boxShadow !== 'none',
        ).toBe(true);

        await page.keyboard.press('Shift+Tab');
        await expect(page.getByRole('button', { name: /Cambiar a tema/ })).toBeFocused();

        // Zoom 200 % (WCAG 2.2 SC 1.4.4): mitad de ancho, mismo criterio que login-react.spec.mjs.
        await page.setViewportSize({
          width: Math.round(viewport.width / 2),
          height: Math.round(viewport.height / 2),
        });
        const overflowConZoom = await page.evaluate(
          () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
        );
        expect(overflowConZoom).toBeLessThanOrEqual(1);
        await page.setViewportSize({ width: viewport.width, height: viewport.height });

        const resultados = await new AxeBuilder({ page }).withTags(WCAG_TAGS).analyze();
        const bloqueantes = resultados.violations.filter(
          ({ impact }) => impact === 'critical' || impact === 'serious',
        );
        expect(
          bloqueantes.map(({ id, impact, nodes }) => `${id} (${impact}) ×${nodes.length}`),
        ).toEqual([]);

        expect(erroresDeConsola).toEqual([]);
        // Solo `/api/session`: ningún POST se disparó al montar la pantalla.
        expect(peticiones.every((p) => p === '/api/session')).toBe(true);
      });
    }
  }
});
