import { expect, test } from '@playwright/test';

/**
 * Selección/creación/eliminación de semana (Tarea 5, T01) con la red COMPLETAMENTE interceptada
 * antes de navegar — nunca toca el backend real ni ninguna base de datos: `/api/session`,
 * `/context/week`, `/context/clear-week`, `/api/context/weeks/create` y
 * `/api/context/weeks/delete-last` se sirven con respuestas sintéticas. Cero DML real (brief T01,
 * Tarea 5, paso 8).
 *
 * Cada test arma su propio "servidor" en memoria: un objeto de estado que las rutas
 * interceptadas leen/actualizan, así que la semana que ve React tras cada mutación es
 * consistente con el bootstrap re-consultado — igual que el contrato real (`recargar()` nunca
 * pinta una copia optimista, ver spec T01 §11).
 */

const CSRF = 'a'.repeat(64);

function bootstrapAutenticado(estado) {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Residente QA', role: 'R' },
    project: { id: 73, name: 'Da Porto', area: 'Construccion' },
    capabilities: { canManageWeeks: true },
    navigation: { bi: null, groups: [] },
    week: estado.week,
    csrfToken: CSRF,
  };
}

function semanaDe(numero, opciones) {
  const opcion = opciones.find((o) => o.number === numero) ?? null;
  return {
    current: numero,
    options: opciones,
    actions: { select: opciones.length > 1, create: true, deleteLast: opciones.length > 0 },
    ...(opcion ? {} : {}),
  };
}

/** Instala la interceptación completa ANTES de `page.goto`. Devuelve el registro de requests. */
async function interceptarRed(page, estadoInicial) {
  const estado = { ...estadoInicial };
  const requests = [];

  // Playwright resuelve las rutas en orden INVERSO de registro (la última registrada gana). El
  // catch-all va primero para que las rutas específicas, registradas después, lo sobrescriban —
  // si fuera al revés, `**/api/**` interceptaría también `/api/session` y la abortaría.
  await page.route('**/api/**', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method(), inesperado: true });
    await route.abort('failed');
  });

  await page.route('**/api/session', async (route) => {
    requests.push({ url: route.request().url(), method: route.request().method() });
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado(estado)) });
  });

  await page.route('**/context/week', async (route) => {
    const body = route.request().postDataJSON();
    requests.push({ url: route.request().url(), method: route.request().method(), body });
    const opcion = estado.week.options.find((o) => o.number === body.semana);
    if (!opcion) {
      await route.fulfill({ status: 404, contentType: 'application/json', body: JSON.stringify({ ok: false, error: { code: 'WEEK_NOT_FOUND', message: 'no existe' } }) });
      return;
    }
    estado.week = semanaDe(body.semana, estado.week.options);
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, week: estado.week }) });
  });

  await page.route('**/api/context/weeks/create', async (route) => {
    const body = route.request().postDataJSON();
    requests.push({ url: route.request().url(), method: route.request().method(), body });
    const numero = Math.max(...estado.week.options.map((o) => o.number)) + 1;
    const fin = new Date(`${body.startsOn}T00:00:00`);
    fin.setDate(fin.getDate() + 6);
    const nuevaOpcion = { number: numero, startsOn: body.startsOn, endsOn: fin.toISOString().slice(0, 10) };
    estado.week = semanaDe(numero, [...estado.week.options, nuevaOpcion]);
    await route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify({ ok: true, week: nuevaOpcion }) });
  });

  await page.route('**/api/context/weeks/delete-last', async (route) => {
    const body = route.request().postDataJSON();
    requests.push({ url: route.request().url(), method: route.request().method(), body });
    const maxima = Math.max(...estado.week.options.map((o) => o.number));
    if (body.week !== maxima) {
      await route.fulfill({ status: 409, contentType: 'application/json', body: JSON.stringify({ ok: false, error: { code: 'WEEK_NOT_LAST', message: 'no es la última' } }) });
      return;
    }
    const opciones = estado.week.options.filter((o) => o.number !== maxima);
    estado.week = semanaDe(opciones.length > 0 ? Math.max(...opciones.map((o) => o.number)) : 0, opciones);
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, deletedWeek: maxima, maxWeek: maxima - 1 }) });
  });

  return { estado, requests };
}

test.describe('Contexto de semana — red completamente interceptada', () => {
  test('seleccionar otra semana envía el número exacto y refresca sin `db`', async ({ page }) => {
    const opciones = [
      { number: 5, startsOn: '2026-08-17', endsOn: '2026-08-23' },
      { number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' },
    ];
    const { requests } = await interceptarRed(page, { week: semanaDe(6, opciones) });

    await page.goto('/app');
    await expect(page.locator('.aia-sidebar__week-label')).toContainText('Semana 6');

    await page.getByLabel(/cambiar de semana/i).selectOption('5');
    await expect(page.locator('.aia-sidebar__week-label')).toContainText('Semana 5');

    const mutacion = requests.find((r) => r.url.includes('/context/week') && r.method === 'POST');
    expect(mutacion?.body).toEqual({ semana: 5 });

    expect(requests.some((r) => r.inesperado)).toBe(false);
    expect(requests.some((r) => r.url.includes('db='))).toBe(false);
  });

  test('crear semana envía startsOn, no muta la lista local y refresca desde el servidor', async ({ page }) => {
    const opciones = [{ number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' }];
    const { requests } = await interceptarRed(page, { week: semanaDe(6, opciones) });

    await page.goto('/app');
    await page.getByRole('button', { name: /crear semana/i }).click();
    await page.getByLabel(/fecha de inicio/i).fill('2026-08-31');
    await page.getByRole('button', { name: /^crear$/i }).click();

    await expect(page.locator('.aia-sidebar__week-label')).toContainText('Semana 7');
    await expect(page.getByRole('dialog', { name: /crear nueva semana/i })).toHaveCount(0);

    const mutacion = requests.find((r) => r.url.includes('/api/context/weeks/create'));
    expect(mutacion?.body).toEqual({ startsOn: '2026-08-31' });
    expect(requests.some((r) => r.inesperado)).toBe(false);
  });

  test('eliminar la última semana exige confirmación y refresca sin reintento automático', async ({ page }) => {
    const opciones = [
      { number: 5, startsOn: '2026-08-17', endsOn: '2026-08-23' },
      { number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' },
    ];
    const { requests } = await interceptarRed(page, { week: semanaDe(6, opciones) });

    await page.goto('/app');
    await page.getByRole('button', { name: /eliminar semana 6/i }).click();
    await expect(page.getByRole('dialog', { name: /eliminar la semana 6/i })).toBeVisible();

    await page.getByRole('button', { name: /^eliminar$/i }).click();
    await expect(page.locator('.aia-sidebar__week-label')).toContainText('Semana 5');

    const llamadasDelete = requests.filter((r) => r.url.includes('/api/context/weeks/delete-last'));
    expect(llamadasDelete).toHaveLength(1); // nunca se reintenta sola
    expect(llamadasDelete[0].body).toEqual({ week: 6 });
  });

  test('crear semana bloqueada por el servidor muestra el error y no reintenta automáticamente', async ({ page }) => {
    const opciones = [{ number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' }];
    // Mismo orden que `interceptarRed`: el catch-all va PRIMERO — Playwright resuelve rutas en
    // orden inverso de registro, así que las específicas (registradas después) lo sobrescriben.
    await page.route('**/api/**', async (route) => route.abort('failed'));
    await page.route('**/api/session', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(bootstrapAutenticado({ week: semanaDe(6, opciones) })),
      });
    });
    let llamadasCreate = 0;
    await page.route('**/api/context/weeks/create', async (route) => {
      llamadasCreate++;
      await route.fulfill({
        status: 409,
        contentType: 'application/json',
        body: JSON.stringify({ ok: false, error: { code: 'SEMANA_NO_CONFIRMADA', message: 'No se puede crear la Semana 7 hasta confirmar los compromisos de la Semana 6.' } }),
      });
    });

    await page.goto('/app');
    await page.getByRole('button', { name: /crear semana/i }).click();
    await page.getByLabel(/fecha de inicio/i).fill('2026-08-31');
    await page.getByRole('button', { name: /^crear$/i }).click();

    await expect(page.getByRole('alert')).toContainText(/confirmar los compromisos/i);
    expect(llamadasCreate).toBe(1);
  });
});
