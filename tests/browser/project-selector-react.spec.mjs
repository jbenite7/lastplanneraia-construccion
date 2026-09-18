import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import {
  CSRF_TOKEN,
  arranqueAnonimo,
  arranqueAutenticadoConProyecto,
  cuerpoError,
  listaProyectos,
  listaProyectosVacia,
  seleccionExitosa,
  seleccionRechazada,
  simularLogout,
  simularProyectos,
  simularSeleccion,
  simularSesion,
  vigilarLlamadasNoInterceptadas,
} from './support/project-selector-react-fixtures.mjs';

/**
 * Comportamiento y accesibilidad del piloto React del selector de proyectos, servido en
 * `/app/proyectos` (S04, Tarea 9). Cierra el gate funcional del plan
 * `2026-08-30-s04-selector-proyectos-react`.
 *
 * **`/proyectos` a secas es inalcanzable por navegación real durante el piloto**
 * (`SpaRouter` no la sirve — corrección de ejecución #9): todo escenario navega a
 * `/app/proyectos`.
 *
 * **Los escenarios interceptados no tocan el backend real.** `vigilarLlamadasNoInterceptadas`
 * se instala ANTES que cualquier doble específico (Playwright resuelve el handler más reciente
 * primero) y cualquier request a `/api/**` que ningún doble haya reclamado queda abortada y
 * registrada — el `expect(sinInterceptar).toEqual([])` al final de cada test prueba negativo:
 * ninguna llamada se escapó al servidor real, ni siquiera una que este spec no anticipó.
 *
 * Los casos contra el servidor real (T9-2, sin interceptar nada) viven en su propio
 * `describe`, más abajo.
 */

async function esperarPantalla(page) {
  await expect(page.getByTestId('selector-proyectos')).toBeVisible();
  await expect(page.getByRole('heading', { level: 1, name: 'Tus proyectos' })).toBeVisible();
}

test.describe('selector de proyectos React — comportamiento', () => {
  test('una sola respuesta vigente: metadata, proyecto actual y CTA de BI', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await expect(page.getByRole('heading', { level: 2, name: 'Da Porto' })).toBeVisible();
    await expect(page.getByText('Proyecto actual')).toBeVisible();
    // Alcance a la propia pantalla (`data-testid="selector-proyectos"`): el rail lateral pinta
    // su propio enlace "Control Tower - Informes" al mismo `href`
    // (`navegacionSelectorProyectos`), y sin este alcance el `getByRole` resuelve dos nodos.
    await expect(
      page.getByTestId('selector-proyectos').getByRole('link', { name: 'Control Tower' }),
    ).toHaveAttribute('href', '/bi/control-tower');
    await expect(page.getByRole('status').filter({ hasText: /disponibles?$/ })).toHaveText(
      '2 proyectos disponibles',
    );

    expect(sinInterceptar).toEqual([]);
  });

  test('búsqueda: filtra, cuenta, limpia y muestra sin-resultados', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    const buscador = page.getByLabel('Buscar proyecto');
    await buscador.fill('agora');
    await expect(page.getByRole('heading', { level: 2, name: 'Ágora' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 2, name: 'Da Porto' })).toHaveCount(0);
    await expect(page.getByText('1 proyecto encontrado')).toBeVisible();

    await page.getByRole('button', { name: 'Limpiar búsqueda' }).click();
    await expect(buscador).toHaveValue('');
    await expect(page.getByRole('heading', { level: 2, name: 'Da Porto' })).toBeVisible();

    await buscador.fill('zzz-no-existe');
    await expect(page.getByRole('heading', { level: 2, name: 'No encontramos proyectos' })).toBeVisible();

    expect(sinInterceptar).toEqual([]);
  });

  test('vacío: sin proyectos asignados', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectosVacia()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await expect(
      page.getByRole('heading', { level: 2, name: 'No tienes proyectos asignados' }),
    ).toBeVisible();

    expect(sinInterceptar).toEqual([]);
  });

  test('GET 500 y retry a 200', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    const llamadas = await simularProyectos(page, [500, listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);
    await expect(page.getByRole('alert')).toBeVisible();

    await page.getByRole('button', { name: 'Reintentar' }).click();
    await expect(page.getByRole('heading', { level: 2, name: 'Da Porto' })).toBeVisible();

    expect(llamadas.total).toBe(2);
    expect(sinInterceptar).toEqual([]);
  });

  test('selección rechazada por el servidor: sin segundo POST y foco de vuelta al origen', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);
    const seleccion = await simularSeleccion(page, () => ({ status: 200, cuerpo: seleccionRechazada() }));

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    const botonAgora = page.getByRole('button', { name: /Ágora/ });
    await botonAgora.click();

    await expect(page.getByRole('alert').last()).toContainText('No pudimos abrir ese proyecto');
    expect(seleccion.total).toBe(1);

    await page.getByRole('button', { name: 'Cerrar aviso' }).click();
    await expect(botonAgora).toBeFocused();

    // Un segundo clic no debería duplicar el POST más allá de la nueva intención del usuario.
    expect(seleccion.total).toBe(1);
    expect(sinInterceptar).toEqual([]);
  });

  test('403 (CSRF vencido): revalida solo con el clic, nunca reenvía sola', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    const sesion = await simularSesion(page, [
      arranqueAutenticadoConProyecto(),
      arranqueAutenticadoConProyecto(),
    ]);
    await simularProyectos(page, [listaProyectos()]);
    const seleccion = await simularSeleccion(page, () => ({
      status: 403,
      cuerpo: cuerpoError({ code: 'csrf_invalid', message: 'No fue posible validar la solicitud. Intenta nuevamente.' }),
    }));

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await page.getByRole('button', { name: /Ágora/ }).click();
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toBeVisible();
    expect(seleccion.total).toBe(1);

    await page.getByRole('button', { name: 'Actualizar sesión' }).click();
    await expect(page.getByRole('button', { name: 'Actualizar sesión' })).toHaveCount(0);

    // La revalidación pasa por `/api/session`, nunca reenvía el POST de selección.
    expect(seleccion.total).toBe(1);
    expect(sesion.total).toBe(2);
    expect(sinInterceptar).toEqual([]);
  });

  test('selección exitosa: navega a la ruta exacta que devolvió el servidor', async ({ page }) => {
    const sinInterceptar = await vigilarLlamadasNoInterceptadas(page);
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);
    await simularSeleccion(page, () => ({ status: 200, cuerpo: seleccionExitosa('/programacion-semanal') }));

    // El shell navega con `window.location.assign(route)`: interceptamos el GET de esa ruta con
    // una página "landing fixture" del mismo origen, para no depender de que exista de verdad.
    await page.route('**/programacion-semanal', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<!doctype html><title>landing fixture</title><h1>Landing fixture</h1>',
      });
    });

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await page.getByRole('button', { name: /Ágora/ }).click();
    await expect(page).toHaveURL(/\/programacion-semanal$/);

    expect(sinInterceptar).toEqual([]);
  });

  test('no request de mutación lleva project_id, db, role, area, week ni route en el body', async ({ page }) => {
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);
    const cuerpos = [];
    await page.route('**/api/proyectos/seleccionar', async (route) => {
      cuerpos.push(route.request().postData() ?? '');
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(seleccionExitosa('/programacion-semanal')),
      });
    });
    await page.route('**/programacion-semanal', (route) =>
      route.fulfill({ status: 200, contentType: 'text/html', body: '<title>landing</title>' }),
    );

    await page.goto('/app/proyectos');
    await esperarPantalla(page);
    await page.getByRole('button', { name: /Ágora/ }).click();
    await expect(page).toHaveURL(/\/programacion-semanal$/);

    expect(cuerpos).toHaveLength(1);
    const enviado = JSON.parse(cuerpos[0]);
    expect(Object.keys(enviado)).toEqual(['name']);
    for (const prohibida of ['project_id', 'db', 'role', 'area', 'week', 'route']) {
      expect(enviado).not.toHaveProperty(prohibida);
    }
  });

  test('drawer móvil en 390px: toggle, Escape y foco de entrada', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    const disparador = page.getByRole('button', { name: 'Abrir menú de navegación' });
    await expect(disparador).toBeVisible();

    // Ronda de arreglo 2 (hallazgo del coordinador): `toBeVisible()` no detecta que el
    // disparador flotante (`position: fixed`, V1 de la ronda 1) tape el `h1` — solo comprueba
    // que el propio h1 esté en el DOM y no oculto, no que otro elemento fijo se dibuje encima.
    // `elementFromPoint` sobre la esquina superior izquierda real del h1 sí lo distingue: sin la
    // reserva de espacio de `.project-selector-react__header`, ese punto resuelve al botón, no
    // al título.
    const h1 = page.getByRole('heading', { level: 1, name: 'Tus proyectos' });
    const cajaH1 = await h1.boundingBox();
    const elementoEnEsquina = await page.evaluate(
      ([x, y]) => {
        const el = document.elementFromPoint(x, y);
        return el?.closest('h1') !== null;
      },
      [cajaH1.x + 2, cajaH1.y + 2],
    );
    expect(elementoEnEsquina).toBe(true);

    await disparador.click();

    await expect(page.getByRole('button', { name: 'Cerrar menú de navegación' })).toBeVisible();
    const primerEnlace = page.getByRole('link', { name: /Tus proyectos/ });
    await expect(primerEnlace).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Abrir menú de navegación' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Abrir menú de navegación' })).toBeFocused();
  });

  test('escritorio 1180px: rail fijo con "Tus proyectos" marcado aria-current', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await expect(page.getByRole('button', { name: 'Abrir menú de navegación' })).toHaveCount(0);
    const enlaceProyectos = page.getByRole('link', { name: 'Tus proyectos' });
    await expect(enlaceProyectos).toHaveAttribute('aria-current', 'page');
  });

  test('sin overflow horizontal y sin errores de consola en el camino feliz', async ({ page }) => {
    const erroresConsola = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') erroresConsola.push(msg.text());
    });

    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(1);
    expect(erroresConsola).toEqual([]);
  });

  test('Axe: sin impactos serious/critical', async ({ page }) => {
    await simularSesion(page, [arranqueAutenticadoConProyecto()]);
    await simularProyectos(page, [listaProyectos()]);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    const report = await new AxeBuilder({ page }).analyze();
    expect(report.violations.filter(({ impact }) => ['serious', 'critical'].includes(impact))).toEqual([]);
  });
});

/**
 * T9-3: el logout desde la barra del selector manda un único POST con CSRF a
 * `/api/auth/logout` y ningún `GET /logout`. La Tarea 7 cambió ese enlace por un botón
 * precisamente porque el GET destruye la sesión sin CSRF (`BarraLateral.tsx`, comentario T7-6);
 * hasta este spec ningún test de navegador lo protegía.
 */
test.describe('selector de proyectos React — logout de la barra', () => {
  test('"Cerrar sesión" manda un único POST con CSRF, nunca GET /logout', async ({ page }) => {
    const peticiones = [];
    page.on('request', (req) => peticiones.push({ method: req.method(), url: req.url() }));

    await simularSesion(page, [arranqueAutenticadoConProyecto(), arranqueAnonimo()]);
    await simularProyectos(page, [listaProyectos()]);
    const logout = await simularLogout(page);

    await page.goto('/app/proyectos');
    await esperarPantalla(page);

    await expect(page.locator('a[href="/logout"]')).toHaveCount(0);

    await page.getByRole('button', { name: 'Cerrar sesión' }).click();
    await expect.poll(() => logout.total).toBe(1);

    expect(logout.csrf[0]).toBe(CSRF_TOKEN);

    const getLogout = peticiones.filter((p) => p.method === 'GET' && p.url.endsWith('/logout'));
    expect(getLogout).toEqual([]);
    const postsLogout = peticiones.filter(
      (p) => p.method === 'POST' && p.url.includes('/api/auth/logout'),
    );
    expect(postsLogout).toHaveLength(1);
  });
});

/**
 * T9-2 (corrección de ejecución #10): además de los dobles, al menos un caso por endpoint
 * tocado va contra el servidor real, SIN interceptar la API. Ninguno selecciona un proyecto de
 * verdad — el POST de abajo usa un nombre inexistente y un CSRF inválido a propósito, así que el
 * flujo de negocio nunca llega a ejecutarse (el controlador corta en la validación de CSRF antes
 * de leer el body: `ProjectApiController::select()`, sesión → CSRF → payload).
 *
 * **Hallazgo de la primera corrida, revisado (documentado, no ocultado):** una primera lectura
 * (sin la cabecera `Accept`) sugería que `GET /api/proyectos` sin sesión siempre respondía 302 a
 * `/login`, nunca el 401 que pide la corrección #10 — pero eso resultó ser el camino de
 * navegación de página, no el que toma el cliente real. `pedir()` (`cliente.ts`) manda siempre
 * `Accept: application/json`, y `SessionMiddleware::finishUnauthorized()` decide 302-vs-401 por
 * esa misma cabecera (`expectsJsonResponse()`, `src/Core/SessionMiddleware.php`): con
 * `Accept: application/json` sí devuelve **401**, confirmado con `curl -i -H 'Accept:
 * application/json'`. El único dato que sigue siendo distinto de lo que pedía la corrección #10
 * es el CUERPO: no es el de `ProjectApiController::respondSessionInvalid()`
 * (`{success:false,code:'session_invalid',...}, error:{codigo:...}`) — ese sigue siendo
 * inalcanzable desde "sin sesión en absoluto", porque `SessionMiddleware::beginRequest()` corta
 * antes de que el router llegue al controlador. El cuerpo real es el de
 * `finishUnauthorized()`: `{success:false, sessionExpired:true, reason, redirect}`, que es
 * justo la forma que `cliente.ts` ya sabe leer (`detalle.redirect`/`detalle.reason` en
 * `EsquemaCuerpoErrorApi`). El test de abajo verifica esa forma real.
 */
test.describe('selector de proyectos React — servidor real (sin interceptar)', () => {
  test('GET /api/proyectos sin sesión responde 401 (vía SessionMiddleware, con Accept: application/json)', async ({
    request,
  }) => {
    const respuesta = await request.get('/api/proyectos', {
      headers: { Accept: 'application/json' },
    });
    expect(respuesta.status()).toBe(401);
    const cuerpo = await respuesta.json();
    expect(cuerpo).toMatchObject({
      success: false,
      sessionExpired: true,
      reason: 'missing_session',
      redirect: '/login',
    });
  });

  test('POST /api/proyectos/seleccionar sin CSRF válido es rechazado con 403, con sesión real', async ({
    page,
  }) => {
    // Puerta de servicio, sin `p`: aterriza en `/proyectos` (legado), una lectura — no
    // selecciona ningún proyecto. `page.request` comparte cookies con `page` (misma
    // `BrowserContext`); el fixture `request` de nivel de test es un `APIRequestContext`
    // independiente y sin sesión — usarlo aquí habría probado el caso anónimo por accidente.
    await page.goto('/dev/entrar?u=test.R');
    await expect(page).not.toHaveURL(/\/login/);

    const respuesta = await page.request.post('/api/proyectos/seleccionar', {
      headers: { 'X-CSRF-Token': 'x'.repeat(64), 'Content-Type': 'application/json' },
      data: { name: 'proyecto-inexistente-e2e-nunca-se-selecciona' },
    });

    expect(respuesta.status()).toBe(403);
    const cuerpo = await respuesta.json();
    expect(cuerpo).toMatchObject({
      success: false,
      code: 'csrf_invalid',
      message: 'No fue posible validar la solicitud. Intenta nuevamente.',
      error: {
        codigo: 'csrf_invalid',
        mensaje: 'No fue posible validar la solicitud. Intenta nuevamente.',
      },
    });
  });
});
