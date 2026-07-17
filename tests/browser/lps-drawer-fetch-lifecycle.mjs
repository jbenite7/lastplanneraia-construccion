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
      const request = { url: String(url), signal: options.signal ?? null };
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
    await page.route('**/api/lps/comments?consecutivo=999999', async (route) => {
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
