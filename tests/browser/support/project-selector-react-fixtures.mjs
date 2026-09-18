/**
 * Dobles de `/api/session`, `/api/proyectos` y `/api/proyectos/seleccionar` para el piloto React
 * del selector de proyectos (S04, Tarea 9). Mismo patrón que
 * `support/login-react-fixtures.mjs` (S01, Tarea 14): vive fuera de los `.spec.mjs` porque el
 * funcional y el visual comparten estos fixtures y `support/**` está en `testIgnore`.
 *
 * **T9-1 (decisión del coordinador):** cada fixture está validado contra los esquemas Zod reales
 * (`frontend/src/lib/api/esquemas/{sesion,arranque,proyectos}.ts`), no copiado de los literales
 * del brief — que son anteriores a S01-S03 y el contrato estricto los rechaza (falta `week`,
 * `navigation.groups`, `project.area` en el arranque; y `EsquemaListaProyectos`/
 * `EsquemaProyectoDisponible` son `.strict()`, cero claves extra). Ver
 * `scratchpad/validar-fixtures.mjs` para la corrida que lo probó antes de escribir un solo test.
 */

export const CSRF_TOKEN = 'a'.repeat(64);

const NAVEGACION_VACIA = { bi: null, groups: [] };

/** Arranque autenticado con un proyecto ya activo — la sesión que consume `SelectorProyectos`. */
export function arranqueAutenticadoConProyecto() {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Ana Residente', role: 'R' },
    project: { id: 73, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: NAVEGACION_VACIA,
    week: null,
    csrfToken: CSRF_TOKEN,
  };
}

/** Arranque anónimo (posterior a un logout confirmado). */
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

/** `EsquemaListaProyectos` (proyectos.ts) — dos proyectos, BI visible, uno "actual". */
export function listaProyectos({ projects, biVisible = true } = {}) {
  return {
    projects: projects ?? [
      { id: 73, name: 'Da Porto', area: 'Construccion', active: true, role: 'A', roleLabel: 'Administrador' },
      { id: 91, name: 'Ágora', area: 'Pre-Construccion', active: true, role: 'R', roleLabel: 'Residente de Obra' },
    ],
    navigation: {
      bi: biVisible
        ? { visible: true, href: '/bi/control-tower' }
        : { visible: false, href: null },
    },
  };
}

export function listaProyectosVacia() {
  return { projects: [], navigation: { bi: { visible: false, href: null } } };
}

/** `EsquemaResultadoSeleccionProyecto`, rama éxito: la ruta la decide siempre el servidor. */
export function seleccionExitosa(route = '/programacion-semanal') {
  return { success: true, message: null, route };
}

/**
 * `EsquemaResultadoSeleccionProyecto`, rama rechazo: el literal es fijo
 * (`MENSAJE_RECHAZO_PROYECTO` en `esquemas/proyectos.ts`) — cualquier otro texto no valida contra
 * el discriminated union y el fallo se vería (mal) como contrato roto, no como rechazo.
 */
export function seleccionRechazada() {
  return { success: false, message: 'No se pudo acceder al proyecto seleccionado.', route: null };
}

function json(cuerpo, status = 200) {
  return { status, contentType: 'application/json', body: JSON.stringify(cuerpo) };
}

/** Doble de `/api/session`, igual contrato que `login-react-fixtures.mjs`. */
export async function simularSesion(page, secuencia) {
  const llamadas = { total: 0 };
  await page.route('**/api/session', async (route) => {
    const indice = Math.min(llamadas.total, secuencia.length - 1);
    llamadas.total += 1;
    const respuesta = secuencia[indice];
    if (typeof respuesta === 'number') {
      await route.fulfill({ status: respuesta, contentType: 'text/html', body: '<h1>Error</h1>' });
      return;
    }
    await route.fulfill(json(respuesta));
  });
  return llamadas;
}

/**
 * Doble de `GET /api/proyectos`. `secuencia` permite el caso "500 y retry a 200" del brief: una
 * lista de respuestas, la última se repite.
 */
export async function simularProyectos(page, secuencia) {
  const llamadas = { total: 0 };
  await page.route('**/api/proyectos', async (route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback();
      return;
    }
    const indice = Math.min(llamadas.total, secuencia.length - 1);
    llamadas.total += 1;
    const respuesta = secuencia[indice];
    if (typeof respuesta === 'number') {
      await route.fulfill({ status: respuesta, contentType: 'text/html', body: '<h1>Error</h1>' });
      return;
    }
    await route.fulfill(json(respuesta));
  });
  return llamadas;
}

/** Doble de `POST /api/proyectos/seleccionar`. `responder(indice)` devuelve `{status, cuerpo}`. */
export async function simularSeleccion(page, responder) {
  const llamadas = { total: 0, csrf: [] };
  await page.route('**/api/proyectos/seleccionar', async (route) => {
    const indice = llamadas.total;
    llamadas.total += 1;
    llamadas.csrf.push(route.request().headers()['x-csrf-token'] ?? null);
    const { status, cuerpo } = responder(indice);
    await route.fulfill(json(cuerpo, status));
  });
  return llamadas;
}

/** Doble de `POST /api/auth/logout` — para T9-3. */
export async function simularLogout(page, { status = 200 } = {}) {
  const llamadas = { total: 0, csrf: [] };
  await page.route('**/api/auth/logout', async (route) => {
    llamadas.total += 1;
    llamadas.csrf.push(route.request().headers()['x-csrf-token'] ?? null);
    await route.fulfill(json({ success: true }, status));
  });
  return llamadas;
}

/**
 * Cuerpo de error tal como lo emite `ProjectApiController::respondError()` (mismo patrón que
 * `login-react-fixtures.mjs`, capturado de `tests/fixtures/api-projects-error-bodies.json`).
 */
export function cuerpoError({ code, message, fieldErrors = null }) {
  const error = { codigo: code, mensaje: message, ...(fieldErrors ? { campos: fieldErrors } : {}) };
  return { success: false, code, message, error };
}

/** Fija el tema antes de que corra el script de arranque del documento. */
export async function fijarTema(page, tema) {
  await page.addInitScript((valor) => {
    try {
      localStorage.setItem('aia-theme', valor);
    } catch {
      // Sin storage el documento cae a su fallback por sí solo.
    }
  }, tema === 'claro' ? 'light' : 'dark');
}

export const TEMAS = ['oscuro', 'claro'];

export const VIEWPORTS = [
  { nombre: '390x844', width: 390, height: 844 },
  { nombre: '768x1024', width: 768, height: 1024 },
  { nombre: '1180x820', width: 1180, height: 820 },
  { nombre: '1440x900', width: 1440, height: 900 },
];

/**
 * Catch-all: cualquier petición a `/api/**` que no haya sido interceptada explícitamente por un
 * `page.route` más específico (Playwright resuelve el más reciente primero) queda registrada y
 * abortada. Instalar ESTO PRIMERO en cada escenario interceptado, antes de los dobles concretos,
 * para poder afirmar al final "nada llegó al backend real" — incluida una llamada que ningún
 * doble anticipó.
 */
export async function vigilarLlamadasNoInterceptadas(page) {
  const sinInterceptar = [];
  await page.route('**/api/**', async (route) => {
    sinInterceptar.push(`${route.request().method()} ${route.request().url()}`);
    await route.abort();
  });
  return sinInterceptar;
}
