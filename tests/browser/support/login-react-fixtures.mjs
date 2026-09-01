/**
 * Dobles de `/api/session` y `/api/auth/*` para las pruebas de navegador de la pantalla de
 * acceso React (S01, Tarea 14).
 *
 * **Por qué existe este archivo y no está dentro del spec.** Lo comparten
 * `login-react.spec.mjs` (funcional) y `login-react.visual.mjs` (goldens): si viviera en el
 * funcional, el visual tendría que importar un archivo que Playwright ya recolectó como spec.
 * `support/**` está en `testIgnore` (ver `playwright.config.mjs`), así que aquí no se recolecta
 * nada por accidente.
 *
 * **Ninguna prueba de este frente toca credenciales reales ni la base de datos.** Todo el
 * arranque y todas las mutaciones de auth se sirven desde estos dobles: la pantalla se ejerce
 * contra contratos, no contra cuentas. Es una exigencia del encargo, no una comodidad — el
 * frente tiene prohibido tocar usuarios, y un fixture que sembrara cuentas violaría eso
 * (ver el ruling de la Tarea 5 en `progress.md`).
 */

/** El esquema Zod del arranque exige `/^[a-f0-9]{64}$/`; un token corto lo rechaza el cliente. */
export const CSRF_TOKEN = 'a'.repeat(64);

const NAVEGACION_VACIA = { bi: null, groups: [] };

/** Arranque anónimo. `reason` por defecto es el estado normal de "nunca hubo sesión". */
export function arranqueAnonimo(reason = 'missing_session') {
  return {
    state: 'anonymous',
    authenticated: false,
    reason,
    user: null,
    project: null,
    capabilities: {},
    navigation: NAVEGACION_VACIA,
    week: null,
    csrfToken: CSRF_TOKEN,
  };
}

/** Arranque con cambio de clave pendiente: la sesión existe pero no autentica nada todavía. */
export function arranqueCambioClave() {
  return {
    state: 'password_change_required',
    authenticated: false,
    reason: null,
    user: null,
    project: null,
    capabilities: {},
    navigation: NAVEGACION_VACIA,
    week: null,
    csrfToken: CSRF_TOKEN,
  };
}

/** Arranque autenticado sin proyecto elegido — a donde aterriza un login correcto. */
export function arranqueAutenticadoSinProyecto() {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Prueba Residente', role: 'R' },
    project: null,
    capabilities: {},
    navigation: NAVEGACION_VACIA,
    week: null,
    csrfToken: CSRF_TOKEN,
  };
}

function json(cuerpo, status = 200) {
  return {
    status,
    contentType: 'application/json',
    body: JSON.stringify(cuerpo),
  };
}

/**
 * Instala el doble de `/api/session`. `secuencia` es la lista de arranques a devolver, uno por
 * consulta: el shell vuelve a pedir el bootstrap tras cada login, cambio de clave o cancelación
 * (`recargar()` en `SesionProvider`), así que la progresión de pantallas se expresa como una
 * secuencia de respuestas. La última se repite si se piden más.
 *
 * Devuelve un contador vivo de llamadas para poder afirmar "una petición por acción".
 */
export async function simularSesion(page, secuencia) {
  const llamadas = { total: 0 };

  await page.route('**/api/session', async (route) => {
    const indice = Math.min(llamadas.total, secuencia.length - 1);
    llamadas.total += 1;
    const respuesta = secuencia[indice];

    if (typeof respuesta === 'number') {
      // Un status desnudo = fallo de servidor. Cuerpo no-JSON a propósito: es lo que produce
      // un 500 real de PHP, y el cliente debe caer en `error_recuperable`, no en el login.
      await route.fulfill({ status: respuesta, contentType: 'text/html', body: '<h1>Error</h1>' });
      return;
    }

    await route.fulfill(json(respuesta));
  });

  return llamadas;
}

/**
 * Instala un doble para una ruta de mutación de auth y cuenta sus llamadas.
 * `responder(indiceDeLlamada)` devuelve `{ status, cuerpo }`.
 */
export async function simularMutacion(page, ruta, responder) {
  const llamadas = { total: 0, cuerpos: [] };

  await page.route(`**${ruta}`, async (route) => {
    const indice = llamadas.total;
    llamadas.total += 1;
    // El cuerpo enviado NO se registra: lleva la contraseña tecleada, y estas trazas se guardan
    // en disco cuando un test falla (`trace: 'retain-on-failure'`). Solo se guarda su longitud,
    // que basta para afirmar "se envió algo" sin dejar la credencial en el artefacto.
    llamadas.cuerpos.push((route.request().postData() ?? '').length);

    const { status, cuerpo } = responder(indice);
    await route.fulfill(json(cuerpo, status));
  });

  return llamadas;
}

/**
 * Cuerpo de error tal como lo emite `AuthApiController` tras el ruling de la Tarea 5: claves
 * planas para los consumidores legados MÁS el bloque `error` anidado, que es el único que
 * `cliente.ts` sabe leer. Emitir solo la forma plana haría que la pantalla mostrara el mensaje
 * genérico "<ruta> respondió 401" en vez del texto del servidor — el desalineamiento que ese
 * ruling existe para cerrar.
 */
export function cuerpoError({ code, message, fieldErrors = null }) {
  return {
    success: false,
    code,
    message,
    ...(fieldErrors ? { fieldErrors } : {}),
    error: {
      codigo: code,
      mensaje: message,
      ...(fieldErrors ? { campos: fieldErrors } : {}),
    },
  };
}

/** Fija el tema antes de que corra el script de arranque del documento. */
export async function fijarTema(page, tema) {
  await page.addInitScript((valor) => {
    try {
      localStorage.setItem('aia-theme', valor);
    } catch {
      // Sin storage el documento cae a oscuro por sí solo; el test lo detectará al comparar.
    }
  }, tema === 'claro' ? 'light' : 'dark');
}

/** La matriz de la Tarea 14: dos temas por cuatro anchos. */
export const TEMAS = ['oscuro', 'claro'];

export const VIEWPORTS = [
  { nombre: '390x844', width: 390, height: 844 },
  { nombre: '768x1024', width: 768, height: 1024 },
  { nombre: '1180x820', width: 1180, height: 820 },
  { nombre: '1440x900', width: 1440, height: 900 },
];
