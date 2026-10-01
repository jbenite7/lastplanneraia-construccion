import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');

const DRAWER_FIXTURE = `
  <aside id="lps_drawer"></aside>
  <section id="lps_comments_card">
    <div id="lps_comments_container"></div>
  </section>
  <section id="lps_action_card"></section>
  <section id="lps_closure_card"></section>
`;

const ROW = {
  unique_id: 1471,
  Actividad: 'Actividad de prueba',
  D_y_E: 'N/A',
  Materiales: 'N/A',
  MdeO: 'N/A',
  Equipos: 'N/A',
  Predecesora: 'N/A',
  Pdto_Cons: 'N/A',
  Seguimiento: 'N/A',
};

async function loadDrawerHarness(page) {
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.setContent(DRAWER_FIXTURE);
  await page.evaluate(() => {
    window.__commentRequests = [];
    window.fetch = (url, options = {}) => {
      const request = {
        url: String(url),
        signal: options.signal ?? null,
        body: options.body instanceof FormData ? Object.fromEntries(options.body.entries()) : null,
      };
      window.__commentRequests.push(request);
      return new Promise((resolve, reject) => {
        request.resolve = resolve;
        request.reject = reject;
        request.signal?.addEventListener('abort', () => {
          reject(new DOMException('The operation was aborted.', 'AbortError'));
        }, { once: true });
      });
    };
  });
  await page.addScriptTag({ path: 'public/js/modules/lps_drawer.js' });
}

test('drawer cancels superseded and page-hidden comment requests without reporting them as network errors', async ({ page }) => {
  const consoleErrors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  await loadDrawerHarness(page);

  await page.evaluate((row) => {
    window.LPSContextualDrawer.updateContext(row, 'programacion-semanal');
    window.LPSContextualDrawer.updateContext({ ...row, unique_id: 1472 }, 'programacion-semanal');
  }, ROW);

  await expect.poll(() => page.evaluate(() => window.__commentRequests.length)).toBe(2);
  await expect.poll(() => page.evaluate(() => ({
    firstHasSignal: window.__commentRequests[0].signal instanceof AbortSignal,
    firstAborted: window.__commentRequests[0].signal?.aborted ?? false,
  }))).toEqual({ firstHasSignal: true, firstAborted: true });

  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide')));
  await expect.poll(() => page.evaluate(() => window.__commentRequests[1].signal?.aborted ?? false)).toBe(true);
  expect(consoleErrors).toEqual([]);
});

test('drawer still reports a genuine current-context comments network failure', async ({ page }) => {
  const consoleErrors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  await loadDrawerHarness(page);

  await page.evaluate((row) => {
    window.LPSContextualDrawer.updateContext(row, 'programacion-semanal');
    window.__commentRequests[0].reject(new TypeError('Failed to fetch'));
  }, ROW);

  await expect(page.locator('#lps_comments_container')).toHaveText('Error de conexión.');
  expect(consoleErrors).toHaveLength(1);
  expect(consoleErrors[0]).toContain('Error al cargar comentarios: TypeError: Failed to fetch');
});

test('drawer rejects a resolved HTTP 500 even when its JSON payload looks successful', async ({ page }) => {
  const consoleErrors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  await loadDrawerHarness(page);

  await page.evaluate((row) => {
    window.LPSContextualDrawer.updateContext(row, 'programacion-semanal');
    window.__commentRequests[0].resolve(new Response(JSON.stringify({
      respuesta: 'OK',
      mensaje: 'Fallo controlado',
      data: [],
    }), {
      status: 500,
      statusText: 'Internal Server Error',
      headers: { 'Content-Type': 'application/json' },
    }));
  }, ROW);

  await expect(page.locator('#lps_comments_container')).toHaveText('Error 500: Fallo controlado');
  expect(consoleErrors).toHaveLength(1);
  expect(consoleErrors[0]).toContain('Error al cargar comentarios: Error: HTTP 500: Fallo controlado');
});

test('operational navigation aborts an in-flight drawer request without console or server errors', async ({ page }) => {
  const runtimeErrors = installErrorCollectors(page);
  let releaseRequest;
  const requestRelease = new Promise((resolve) => {
    releaseRequest = resolve;
  });

  try {
    await loginAndSelectProject(page, DA_PORTO);
    await page.goto('/programacion-semanal', { waitUntil: 'domcontentloaded' });
    await expect.poll(() => page.evaluate(() => typeof window.LPSContextualDrawer?.updateContext)).toBe('function');
    await page.route('**/api/lps/comments?consecutivo=999999*', async (route) => {
      await requestRelease;
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ respuesta: 'OK', data: [] }),
      }).catch(() => {});
    });

    const requestStarted = page.waitForRequest((request) => request.url().includes('/api/lps/comments?consecutivo=999999'));
    await page.evaluate((row) => {
      window.LPSContextualDrawer.updateContext(row, 'programacion-semanal');
    }, { ...ROW, unique_id: 999999 });
    await requestStarted;
    await page.goto('/programacion-intermedia', { waitUntil: 'domcontentloaded' });
    releaseRequest();

    await expect.poll(() => page.evaluate(() => location.pathname)).toBe('/programacion-intermedia');
    assertNoRuntimeErrors(runtimeErrors);
  } finally {
    releaseRequest?.();
    await logout(page).catch(() => {});
  }
});

// Tarea 5 del arreglo del SOS: el cajon legado manda la semana que se ve, o la alerta.
const TARGET_FIXTURE = `
  <input type="hidden" id="semana_PHP" value="__SEMANA__" />
  <aside id="lps_drawer"></aside>
  <section id="lps_comments_card"><div id="lps_comments_container"></div></section>
  <section id="lps_action_card"></section>
  <section id="lps_closure_card"></section>
  <textarea id="lps_comment_input"></textarea>
  <button id="lps_btn_send_comment" type="button">Enviar</button>
  <button id="lps_btn_email" type="button">Email</button>
`;

async function loadTargetHarness(page, semanaHtml) {
  await loadDrawerHarness(page);
  await page.evaluate((html) => {
    document.body.insertAdjacentHTML('afterbegin', html);
    localStorage.setItem('lps_simulated_mode', 'false');
    window.__comment_fetch_log = window.__commentRequests;
    window.LPSContextualDrawer.init(null, 'programacion-semanal', { writeRow() {} });
  }, TARGET_FIXTURE.replace('__SEMANA__', semanaHtml));
}

// Dispara las tres llamadas (lectura, comentario, registro de crisis) y devuelve lo que salio.
async function fireThreeCalls(page, row) {
  return page.evaluate(async (fila) => {
    window.__commentRequests.length = 0;
    window.LPSContextualDrawer.updateContext(fila, 'programacion-semanal');
    document.getElementById('lps_comment_input').value = 'hola';
    document.getElementById('lps_btn_send_comment').click();
    document.getElementById('lps_btn_email').click();
    await new Promise((resolve) => setTimeout(resolve, 50));
    const params = (url) => Object.fromEntries(new URL(url, location.origin).searchParams.entries());
    const byPath = (path) => window.__commentRequests.find((r) => r.url.includes(path));
    return {
      lectura: params(byPath('/api/lps/comments?').url),
      comentario: byPath('/api/lps/comments/add')?.body ?? null,
      crisis: byPath('/api/lps/crisis/register')?.body ?? null,
    };
  }, row);
}

test('drawer sends the viewed week in read, comment and crisis calls', async ({ page }) => {
  await loadTargetHarness(page, '12');
  const out = await fireThreeCalls(page, ROW);
  expect(out.lectura.semana).toBe('12');
  expect(out.comentario?.semana).toBe('12');
  expect(out.crisis?.semana).toBe('12');
  expect(out.lectura).not.toHaveProperty('escalamiento_id');
  expect(out.comentario).not.toHaveProperty('escalamiento_id');
  expect(out.crisis).not.toHaveProperty('escalamiento_id');
});

test('drawer sends escalamiento_id and no semana when the row carries an alert', async ({ page }) => {
  await loadTargetHarness(page, '12');
  const out = await fireThreeCalls(page, { ...ROW, alerta_id: 77, modulo: 'PI' });
  expect(out.lectura.escalamiento_id).toBe('77');
  expect(out.comentario?.escalamiento_id).toBe('77');
  expect(out.crisis?.escalamiento_id).toBe('77');
  expect(out.lectura).not.toHaveProperty('semana');
  expect(out.comentario).not.toHaveProperty('semana');
  expect(out.crisis).not.toHaveProperty('semana');
});

for (const [label, value] of [['missing', null], ['zero', '0'], ['empty', '']]) {
  test(`drawer never sends semana when semana_PHP is ${label}`, async ({ page }) => {
    await loadDrawerHarness(page);
    await page.evaluate(({ html, value: v }) => {
      const markup = v === null ? html.replace(/<input type="hidden" id="semana_PHP"[^>]*>/, '') : html;
      document.body.insertAdjacentHTML('afterbegin', markup);
      localStorage.setItem('lps_simulated_mode', 'false');
      window.LPSContextualDrawer.init(null, 'programacion-semanal', { writeRow() {} });
    }, { html: TARGET_FIXTURE.replace('__SEMANA__', value ?? ''), value });
    const out = await fireThreeCalls(page, ROW);
    expect(out.lectura).not.toHaveProperty('semana');
    expect(out.comentario).not.toHaveProperty('semana');
    expect(out.crisis).not.toHaveProperty('semana');
  });
}
