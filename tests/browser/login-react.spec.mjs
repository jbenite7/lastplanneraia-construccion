import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { WCAG_TAGS } from './support/accessibility.mjs';
import {
  CSRF_TOKEN,
  TEMAS,
  VIEWPORTS,
  arranqueAnonimo,
  arranqueAutenticadoSinProyecto,
  arranqueCambioClave,
  cuerpoError,
  fijarTema,
  simularMutacion,
  simularSesion,
} from './support/login-react-fixtures.mjs';

/**
 * Comportamiento y accesibilidad de la pantalla de acceso React servida en `/login`
 * (S01, Tarea 14). Cierra el gate funcional del plan `2026-08-30-s01-login-react`.
 *
 * **Todo el backend está simulado con `page.route()`.** Ni un solo test teclea una credencial
 * real ni escribe una fila: el frente tiene prohibido tocar usuarios, credenciales y schema, y
 * los dos estados que solo existirían con una cuenta `force_password_change=1` se ejercen aquí
 * por contrato, igual que la Tarea 5 los cubrió a nivel unitario.
 *
 * **Se prueba `/login`, no `/app`.** Es la ruta que la Tarea 13 acaba de cortar al shell React
 * y por tanto la que un usuario ve; probar `/app` mediría el andamio en vez del producto.
 */

/** Un `<h1>` y solo uno: la regla de la spec T01 §14 que `MarcoAcceso` existe para garantizar. */
async function esperarPantallaDeAcceso(page, titulo) {
  await expect(page.getByRole('heading', { level: 1, name: titulo })).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
}

test.describe('acceso React — comportamiento', () => {
  test('anónimo: la pantalla de entrada se sirve con sus etiquetas y su enlace de recuperación', async ({ page }) => {
    const sesion = await simularSesion(page, [arranqueAnonimo()]);
    await page.goto('/login');

    await esperarPantallaDeAcceso(page, /^Entrar$/);

    // Etiquetas asociadas de verdad: `getByLabel` resuelve por la relación label/control,
    // así que falla si alguien deja el texto suelto al lado del input.
    await expect(page.getByLabel('Usuario')).toBeVisible();
    await expect(page.getByLabel('Contraseña', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: /olvidaste tu contraseña/i })).toHaveAttribute(
      'href',
      '/password/forgot',
    );

    // S02 sigue sin migrar: el enlace debe apuntar al PHP legado, no a una ruta React inventada.
    expect(sesion.total).toBe(1);
  });

  test('aviso de clave restablecida: se muestra una vez y la URL queda limpia', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await page.goto('/login?reset=1');

    await expect(page.getByRole('status')).toHaveText(/contraseña fue restablecida/i);
    // El parámetro se consume: sobrevive un recargo si no se limpia, y el aviso reaparecería.
    await expect.poll(() => new URL(page.url()).search).toBe('');
  });

  test('401: mensaje no enumerable, contraseña descartada y una sola petición por envío', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    const login = await simularMutacion(page, '/api/auth/login', () => ({
      status: 401,
      cuerpo: cuerpoError({ code: 'INVALID_CREDENTIALS', message: 'Usuario o contraseña incorrectos.' }),
    }));

    await page.goto('/login');
    await page.getByLabel('Usuario').fill('cuenta-que-no-existe');
    await page.getByLabel('Contraseña', { exact: true }).fill('valor-de-prueba');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await expect(page.getByRole('alert')).toHaveText('Usuario o contraseña incorrectos.');
    // El foco entra al resumen del error: quien navega por teclado o con lector se entera.
    await expect(page.getByRole('alert')).toBeFocused();
    // Nunca se conserva la contraseña tras un intento fallido.
    await expect(page.getByLabel('Contraseña', { exact: true })).toHaveValue('');
    expect(login.total).toBe(1);

    // Un segundo envío es otra petición, ni más ni menos: sin reintentos ocultos.
    await page.getByLabel('Contraseña', { exact: true }).fill('otro-valor');
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect.poll(() => login.total).toBe(2);
  });

  test('422: los errores por campo se pintan en su campo y el foco entra al primero', async ({ page }) => {
    await simularSesion(page, [arranqueAnonimo()]);
    await simularMutacion(page, '/api/auth/login', () => ({
      status: 422,
      cuerpo: cuerpoError({
        code: 'VALIDATION_ERROR',
        message: 'Revisa los datos marcados.',
        fieldErrors: { username: 'El usuario es obligatorio.' },
      }),
    }));

    await page.goto('/login');
    await page.getByLabel('Usuario').fill('x');
    await page.getByLabel('Contraseña', { exact: true }).fill('y');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await expect(page.locator('#usuario-error')).toHaveText('El usuario es obligatorio.');
    await expect(page.getByLabel('Usuario')).toHaveAttribute('aria-invalid', 'true');
    // El campo inválido queda descrito por su mensaje, no solo pintado de rojo.
    await expect(page.getByLabel('Usuario')).toHaveAttribute('aria-describedby', 'usuario-error');
    await expect(page.getByLabel('Usuario')).toBeFocused();
  });

  test('cambio de clave pendiente: el login exitoso lleva al panel obligatorio, no a la aplicación', async ({ page }) => {
    // Primer bootstrap anónimo; tras el login, el servidor reporta el cambio pendiente.
    await simularSesion(page, [arranqueAnonimo(), arranqueCambioClave()]);
    await simularMutacion(page, '/api/auth/login', () => ({
      status: 200,
      cuerpo: { success: true, next: 'password_change', message: null },
    }));

    await page.goto('/login');
    await page.getByLabel('Usuario').fill('cuenta-de-prueba');
    await page.getByLabel('Contraseña', { exact: true }).fill('valor-de-prueba');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await esperarPantallaDeAcceso(page, /Actualiza tu contraseña/);
    await expect(page.getByLabel('Nueva contraseña')).toBeFocused();
  });

  test('cambio exitoso: una sola mutación y el shell vuelve a resolver el arranque', async ({ page }) => {
    const sesion = await simularSesion(page, [arranqueCambioClave(), arranqueAutenticadoSinProyecto()]);
    const cambio = await simularMutacion(page, '/api/auth/password/change', () => ({
      status: 200,
      cuerpo: { success: true, next: 'projects' },
    }));

    await page.goto('/login');
    await esperarPantallaDeAcceso(page, /Actualiza tu contraseña/);

    await page.getByLabel('Nueva contraseña').fill('Valor-De-Prueba-1!');
    await page.getByLabel('Confirmar contraseña').fill('Valor-De-Prueba-1!');
    await page.getByRole('button', { name: 'Actualizar y continuar' }).click();

    // Sale de la pantalla de acceso: el arranque siguiente ya es autenticado.
    await expect(page.getByRole('heading', { level: 1, name: /Actualiza tu contraseña/ })).toHaveCount(0);
    expect(cambio.total).toBe(1);
    await expect.poll(() => sesion.total).toBe(2);
  });

  test('cancelar: Escape y Salir solo preguntan; únicamente Confirmar salida muta', async ({ page }) => {
    await simularSesion(page, [arranqueCambioClave(), arranqueAnonimo()]);
    const cancelacion = await simularMutacion(page, '/api/auth/password/cancel', () => ({
      status: 200,
      cuerpo: { success: true, next: 'login' },
    }));

    await page.goto('/login');
    await esperarPantallaDeAcceso(page, /Actualiza tu contraseña/);

    // Escape abre la confirmación y NO llama a la API: perder la sesión pendiente por un
    // reflejo de teclado sería demasiado barato.
    await page.keyboard.press('Escape');
    await expect(page.getByRole('heading', { name: /¿Salir del cambio de contraseña\?/ })).toBeVisible();
    expect(cancelacion.total).toBe(0);

    // El foco arranca en la salida segura, no en la acción destructiva.
    await expect(page.getByRole('button', { name: 'Seguir editando' })).toBeFocused();

    await page.getByRole('button', { name: 'Seguir editando' }).click();
    expect(cancelacion.total).toBe(0);

    await page.getByRole('button', { name: 'Salir' }).click();
    await page.getByRole('button', { name: 'Confirmar salida' }).click();

    await expect.poll(() => cancelacion.total).toBe(1);
    await esperarPantallaDeAcceso(page, /^Entrar$/);
    // Tras cancelar, el foco vuelve al campo de usuario, no a un botón que ya no existe.
    await expect(page.getByLabel('Usuario')).toBeFocused();
  });

  test('error de arranque: un 5xx cae en la pantalla recuperable, nunca en el login por descarte', async ({ page }) => {
    // 500 primero, arranque sano después: el botón "Reintentar" debe poder recuperarse.
    const sesion = await simularSesion(page, [500, arranqueAnonimo()]);
    await page.goto('/login');

    await expect(page.getByRole('alert')).toContainText(/No pudimos conectar con la aplicación/);
    // Lo que esta prueba existe para impedir: que un fallo técnico se presente como "no hay sesión".
    await expect(page.getByRole('button', { name: 'Entrar' })).toHaveCount(0);

    await page.getByRole('button', { name: 'Reintentar' }).click();
    await esperarPantallaDeAcceso(page, /^Entrar$/);
    await expect.poll(() => sesion.total).toBe(2);
  });
});

/**
 * Matriz de presentación: dos temas por cuatro anchos. Cada combinación comprueba el piso de
 * accesibilidad del sistema de diseño, no la apariencia — los goldens viven en el archivo
 * visual y necesitan aprobación explícita antes de convertirse en baseline.
 */
test.describe('acceso React — accesibilidad en la matriz', () => {
  for (const tema of TEMAS) {
    for (const viewport of VIEWPORTS) {
      test(`${tema} ${viewport.nombre}: un h1, foco visible, 44px, sin scroll horizontal y sin violación Axe`, async ({ page }) => {
        const erroresDeConsola = [];
        page.on('console', (mensaje) => {
          if (mensaje.type() === 'error') erroresDeConsola.push(mensaje.text());
        });
        page.on('pageerror', (error) => erroresDeConsola.push(String(error)));

        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await fijarTema(page, tema);
        await simularSesion(page, [arranqueAnonimo()]);
        await page.goto('/login');
        await esperarPantallaDeAcceso(page, /^Entrar$/);

        // El tema pedido es el que el documento aplica: si el conmutador o el script de
        // arranque se desalinearan, el resto de la comprobación mediría la paleta equivocada.
        await expect(page.locator('html')).toHaveAttribute(
          'data-aia-theme',
          tema === 'claro' ? 'light' : 'dark',
        );

        const medidas = await page.evaluate(() => {
          const raiz = document.documentElement;
          const objetivos = [
            ...document.querySelectorAll('button, input, a[href]'),
          ].map((nodo) => {
            const caja = nodo.getBoundingClientRect();
            // El skip link vive fuera de pantalla hasta recibir foco: su caja mide 0 y no es
            // un objetivo táctil mientras esté oculto.
            return { alto: caja.height, oculto: caja.height === 0 };
          });

          return {
            overflow: raiz.scrollWidth - raiz.clientWidth,
            objetivosPequenos: objetivos.filter((o) => !o.oculto && o.alto < 44).length,
          };
        });

        expect(medidas.overflow).toBeLessThanOrEqual(1);
        expect(medidas.objetivosPequenos).toBe(0);

        // Foco visible: el elemento enfocado debe dibujar algún anillo, no perderse en la página.
        await page.getByLabel('Usuario').focus();
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

        // Zoom 200% (WCAG 2.2 SC 1.4.4): se emula reduciendo el viewport a la mitad del ancho,
        // que es lo que ve el usuario al ampliar. Sigue sin haber scroll horizontal.
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
      });
    }
  }
});
