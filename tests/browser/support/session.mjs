import { expect } from '@playwright/test';
import { BASE_URL, CREDENTIALS } from '../fixtures/projects.mjs';

export async function login(page, credentials = CREDENTIALS) {
  // `credentials.password` queda SIN USO a propósito: la puerta de desarrollo
  // (`/dev/entrar`, ver src/Core/DevDoor.php) no pide contraseña, solo comprueba que
  // `credentials.username` esté en DEV_DOOR_USERS. AGENTS.md §Seguridad prohíbe abrir
  // sesión local tecleando credenciales en /login; esta función usa la puerta de
  // servicio en su lugar. `password` se mantiene en la firma/objeto porque decenas de
  // consumidores construyen `CREDENTIALS` con `{ username, password }` y no se les
  // cambia el contrato.
  await page.goto(`${BASE_URL}/dev/entrar?u=${encodeURIComponent(credentials.username)}`);
  const landedPath = new URL(page.url()).pathname;
  if (landedPath !== '/proyectos') {
    throw new Error(
      `La puerta de desarrollo (/dev/entrar) no autenticó a "${credentials.username}": `
      + `aterrizó en "${landedPath}" en vez de "/proyectos". Revisa en el .env local que `
      + 'DEV_DOOR=1 y que DEV_DOOR_USERS incluya esa cuenta (ver docs/superpowers/specs/'
      + '2026-07-30-dev-door-design.md).',
    );
  }
  await expect(page.locator('.project-item').first()).toBeVisible({ timeout: 45000 });
}

export async function logout(page) {
  await page.goto(`${BASE_URL}/logout`);
  await page.waitForURL(/login|\/$/, { timeout: 15000 }).catch(() => {});
}

export async function selectProject(page, project) {
  const card = page.locator('.project-item').filter({
    has: page.getByRole('heading', { name: project.name, exact: true }),
  });
  await expect(card, `Project card not found: ${project.name}`).toBeVisible({ timeout: 45000 });
  await card.locator('button[type="submit"], .btn-enter').click();
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
}

/**
 * Marca el recorrido guiado del PDC como ya visto, antes de que cargue cualquier página.
 *
 * Cada test de Playwright arranca con un almacén limpio, así que para el módulo TODOS los tests son
 * un usuario que entra por primera vez: sin esto, el recorrido se abre como diálogo modal y tapa
 * los clics de los veinte e2e del PDC que existían antes de que hubiera ayuda.
 *
 * Va como `addInitScript` y no como un `evaluate` posterior porque el recorrido se decide al montar
 * la aplicación: escribir la clave después de cargar llegaría tarde.
 *
 * Un test que SÍ quiera ver el recorrido —`pdc-v2-ayuda.spec.mjs`— pide `silenciarRecorrido: false`
 * al entrar. No vale que lo borre por su cuenta con otro `addInitScript`: este corre en CADA
 * navegación, así que tras una recarga volvería a escribir «visto» y la prueba de que el módulo
 * recuerda la decisión mediría el andamiaje en vez de la aplicación.
 */
export async function silenciarRecorridoPdc(page) {
  await page.addInitScript(() => {
    try {
      window.localStorage.setItem('aia-pdc-recorrido', 'visto');
    } catch {
      // Sin almacén no hay nada que silenciar, y el recorrido tampoco podrá recordarse.
    }
  });
}

export async function loginAndSelectProject(
  page,
  project,
  credentials = CREDENTIALS,
  { silenciarRecorrido = true } = {},
) {
  if (silenciarRecorrido) await silenciarRecorridoPdc(page);
  await login(page, credentials);
  await selectProject(page, project);
}

export async function changeWeek(page, week, destination = '/programa-general') {
  const response = await page.evaluate(
    async ({ selectedWeek, redirectTo }) => {
      const res = await fetch('/context/week', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ semana: selectedWeek }),
      });
      const payload = await res.json().catch(() => ({}));
      if (payload.success) window.location.href = redirectTo;
      return { ok: res.ok, status: res.status, payload };
    },
    { selectedWeek: week, redirectTo: destination },
  );

  expect(response.ok, JSON.stringify(response)).toBe(true);
  expect(response.payload.success, JSON.stringify(response)).toBe(true);
  await page.waitForURL(`**${destination}`, { timeout: 45000 });
  await expect(page.locator('#semana, #semana_PHP').first()).toHaveValue(String(week), { timeout: 45000 });
}

export async function captureReloadingJsonRequest(page, path, destination, action, timeout = 45_000) {
  const captureKey = `e2e:ajax:${Date.now()}:${Math.random()}`;
  await page.evaluate(({ key, targetPath }) => {
    if (!window.jQuery) throw new Error('jQuery is required to capture the reloading request');
    sessionStorage.removeItem(key);
    const handler = (_event, _xhr, settings, data) => {
      if (new URL(settings.url, window.location.href).pathname !== targetPath) return;
      sessionStorage.setItem(key, JSON.stringify(data));
      window.jQuery(document).off('ajaxSuccess.e2eReloadJson', handler);
    };
    window.jQuery(document).on('ajaxSuccess.e2eReloadJson', handler);
  }, { key: captureKey, targetPath: path });

  const responsePromise = page.waitForResponse((response) => (
    new URL(response.url()).pathname === path && response.request().method() === 'POST'
  ), { timeout });
  const navigationPromise = page.waitForEvent('framenavigated', {
    predicate: (frame) => frame === page.mainFrame()
      && new URL(frame.url()).pathname === destination,
    timeout,
  });

  await action();
  const response = await responsePromise;
  expect(response.ok(), `${path} HTTP ${response.status()}`).toBe(true);
  expect(response.headers()['content-type'] || '').toMatch(/^application\/json\b/i);
  await page.waitForFunction((key) => sessionStorage.getItem(key) !== null, captureKey, { timeout });
  const serialized = await page.evaluate((key) => {
    const value = sessionStorage.getItem(key);
    sessionStorage.removeItem(key);
    return value;
  }, captureKey);
  const payload = JSON.parse(serialized);
  await navigationPromise;
  return { response, payload };
}

export async function postFormJson(page, url, body = {}, options = {}) {
  return page.evaluate(
    async ({ apiUrl, apiBody, includePdcCsrf, includeCsrf }) => {
      const formData = new URLSearchParams();
      const append = (prefix, value) => {
        if (Array.isArray(value)) {
          value.forEach((entry, index) => append(`${prefix}[${index}]`, entry));
        } else if (value && typeof value === 'object') {
          Object.entries(value).forEach(([key, entry]) => append(`${prefix}[${key}]`, entry));
        } else {
          formData.append(prefix, value == null ? '' : String(value));
        }
      };

      Object.entries(apiBody).forEach(([key, value]) => append(key, value));
      const headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const shouldAttachCsrf = includeCsrf && (apiUrl.startsWith('/api/general/')
        || apiUrl.startsWith('/api/semanal/')
        || apiUrl.startsWith('/api/subcontratistas/')
        || apiUrl.startsWith('/api/profesionales/')
        || apiUrl.startsWith('/api/control-cambios/')
        || apiUrl.startsWith('/api/cic/')
        || apiUrl.startsWith('/api/cnc/')
        || apiUrl.startsWith('/api/cnp/'));
      if (shouldAttachCsrf) {
        headers['X-CSRF-Token'] = csrfToken;
        if (!formData.has('_csrf_token')) {
          formData.append('_csrf_token', csrfToken);
        }
      }

      // nueva_semana.php / eliminar_semana.php (legacy_require_csrf, formKey
      // lps_week_admin) exigen _csrf_token en el body POST, no en un header.
      // El shell (#shellWeekMenusData) y cargarDatosGeneralesPagina2.js
      // (window.__lpsWeekCsrf) lo publican solo en algunas vistas; si la
      // página actual no cargó ninguno de los dos (p. ej. justo tras login,
      // antes de navegar a un módulo con el shell), se pide uno fresco al
      // endpoint legacy que ya lo emite en cada respuesta.
      const isWeekAdminUrl = apiUrl.includes('nueva_semana.php') || apiUrl.includes('eliminar_semana.php');
      if (isWeekAdminUrl && !formData.has('_csrf_token')) {
        let weekCsrfToken = '';
        try {
          weekCsrfToken = JSON.parse(document.getElementById('shellWeekMenusData')?.textContent || '{}').csrfToken || '';
        } catch {
          weekCsrfToken = '';
        }
        if (!weekCsrfToken) {
          weekCsrfToken = window.__lpsWeekCsrf || '';
        }
        if (!weekCsrfToken) {
          try {
            const weekRes = await fetch('/legacy/funciones_generales/php/datosGeneralesPagina.php', {
              method: 'POST',
              credentials: 'same-origin',
            });
            const weekPayload = await weekRes.json().catch(() => ({}));
            weekCsrfToken = weekPayload?.data?.weekCsrfToken || '';
          } catch {
            weekCsrfToken = '';
          }
        }
        formData.set('_csrf_token', weekCsrfToken);
      }

      const res = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: formData.toString(),
      });
      const text = await res.text();
      let payload;
      try {
        payload = JSON.parse(text);
      } catch {
        payload = { parseError: true, text };
      }
      return { ok: res.ok, status: res.status, payload };
    },
    { apiUrl: url, apiBody: body, includePdcCsrf: options.includePdcCsrf !== false, includeCsrf: options.includeCsrf !== false },
  );
}

export async function getJson(page, url) {
  return page.evaluate(async (apiUrl) => {
    const res = await fetch(apiUrl, { credentials: 'same-origin' });
    const text = await res.text();
    let payload;
    try {
      payload = JSON.parse(text);
    } catch {
      payload = { parseError: true, text };
    }
    return { ok: res.ok, status: res.status, payload };
  }, url);
}
