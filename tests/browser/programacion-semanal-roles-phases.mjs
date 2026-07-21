import { test, expect } from '@playwright/test';
import { runSql } from './support/dbSnapshot.mjs';
import { changeWeek, login, loginAndSelectProject, selectProject } from './support/session.mjs';

const DA_PORTO = { name: 'Da Porto', dbPrefix: 'da_porto' };
const JMC = { name: 'Optimización Aeropuerto JMC', dbPrefix: 'optimizacionJMC', projectId: 68 };
const PRUEBA = { name: 'Prueba', dbPrefix: 'prueba', projectId: 27 };
const PROGRAMMING_WEEK = 1;

const ROLE_CASES = [
  { code: 'A', username: 'test.A', canView: true, canEdit: true },
  { code: 'D', username: 'test.D', canView: true, canEdit: true },
  { code: 'R', username: 'test.R', canView: true, canEdit: true },
  { code: 'C', username: 'test.C', canView: false, canEdit: false },
];

function sqlValue(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replaceAll('\\', '\\\\').replaceAll("'", "''")}'`;
}

async function weeklyRows(page, week) {
  const response = await page.request.get(
    `/api/semanal/list?db=${encodeURIComponent(JMC.dbPrefix)}&semana=${week}&_=${Date.now()}`,
  );
  expect(response.ok()).toBe(true);
  return (await response.json()).data.filter((row) => String(row.Consecutivo || '').trim());
}

async function cnpRows(page, week) {
  const response = await page.request.post('/api/cnp/list', { form: { semana: String(week) } });
  expect(response.ok()).toBe(true);
  return (await response.json()).data;
}

function weeklyPayload(row, week, overrides = {}) {
  return { opcion: 'modificar', semana: String(week), Id: String(row.Consecutivo),
    Descripcion: row.Descripcion || '', Ubicacion: row.Ubicacion || '',
    Sub_Contratista: row.Sub_Contratista || '', Responsable_AIA: row.Responsable_AIA || '',
    Empresa: row.Empresa || '', Unidad: row.Unidad || '%',
    Compromiso: String(row.Compromiso ?? ''),
    Cantidad_Sugerida: String(row.Cantidad_Sugerida ?? ''),
    Real: String(row.Ejecutado_Real ?? ''), Rendimientos: row.Rendimientos || '',
    Categoria_CNC: row.Categoria_CNC || '', CNC: row.CNC || '',
    Observaciones_CNC: row.Observaciones_CNC || '', Es_TNP: row.Es_TNP || '',
    ...overrides };
}

async function postWeeklyUpdate(page, row, week, overrides = {}) {
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  return page.request.post(`/api/semanal/save?db=${encodeURIComponent(JMC.dbPrefix)}`, {
    form: { ...weeklyPayload(row, week, overrides), _csrf_token: csrf || '' },
  });
}

function weeklyState(row) {
  return ['Descripcion', 'Ubicacion', 'Empresa', 'Cantidad_Sugerida', 'Rendimientos',
    'Compromiso', 'Ejecutado_Real', 'P_Completado', 'PAC', 'Categoria_CNC',
    'CNC', 'Observaciones_CNC', 'Sub_Contratista', 'Responsable_AIA']
    .map((key) => [key, String(row?.[key] ?? '')]);
}

function restoreWeeklyRowSql(row) {
  runSql(`UPDATE programacion_semanal SET `
    + `Descripcion=${sqlValue(row.Descripcion)}, Ubicacion=${sqlValue(row.Ubicacion)}, `
    + `Empresa=${sqlValue(row.Empresa)}, Cantidad_Sugerida=${sqlValue(row.Cantidad_Sugerida)}, `
    + `Rendimientos=${sqlValue(row.Rendimientos)}, Compromiso=${sqlValue(row.Compromiso)}, `
    + `Sub_Contratista=${sqlValue(row.Sub_Contratista)}, `
    + `Responsable_AIA=${sqlValue(row.Responsable_AIA)}, `
    + `Ejecutado_Real=${sqlValue(row.Ejecutado_Real)}, `
    + `P_Completado=${sqlValue(row.P_Completado)}, PAC=${sqlValue(row.PAC)}, `
    + `Categoria_CNC=${sqlValue(row.Categoria_CNC)}, CNC=${sqlValue(row.CNC)}, `
    + `Observaciones_CNC=${sqlValue(row.Observaciones_CNC)} `
    + `WHERE project_id=${JMC.projectId} AND row_id=${Number(row.Consecutivo)};`);
}

function restoreCnpRowSql(row, projectId = JMC.projectId) {
  runSql(`UPDATE programacion_semanal SET Activa=${sqlValue(row.Activa)}, `
    + `Reprogramada_Por_Usuario=${sqlValue(row.Reprogramada_Por_Usuario)}, `
    + `Categoria_CNP=${sqlValue(row.Categoria_CNP)}, CNP=${sqlValue(row.CNP)}, `
    + `Observaciones_CNP=${sqlValue(row.Observaciones_CNP)} `
    + `WHERE project_id=${projectId} AND row_id=${Number(row.Consecutivo)};`);
}

async function openJmcQualification(page) {
  await page.setViewportSize({ width: 390, height: 844 });
  await loginAndSelectProject(page, JMC);
  await changeWeek(page, 4, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
}

async function openProgrammingWeek(
  page,
  roleCase,
  viewport = { width: 1180, height: 820 },
) {
  await page.setViewportSize(viewport);
  await login(page, { username: roleCase.username, password: 'aia2026' });
  await selectProject(page, DA_PORTO);
  await changeWeek(page, PROGRAMMING_WEEK, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  await expect(page.locator('#permiso_canonico[aria-hidden="true"]')).toHaveValue(roleCase.code);
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Programación de Compromisos',
  );
}

async function expectSectionDropdown(page) {
  const navigation = page.locator('.ps-dropdown-nav');
  await navigation.locator('.btn-dropdown-trigger').click();
  await expect(navigation).toHaveClass(/is-open/);
  const items = navigation.locator('.ps-dropdown-item');
  await expect(items).toHaveCount(4);
  await expect(items).toHaveText([
    /Actividades/,
    /Causas No Programacion/,
    /Causas No Cumplimiento/,
    /Calificacion Proveedores/,
  ]);
  await page.locator('body').click({ position: { x: 4, y: 4 } });
  await expect(navigation).not.toHaveClass(/is-open/);
}

async function probeWeeklyPermissions(page, roleCase) {
  const list = await page.request.get(
    `/api/semanal/list?db=${DA_PORTO.dbPrefix}&semana=${PROGRAMMING_WEEK}`,
  );
  expect(list.status()).toBe(roleCase.canView ? 200 : 403);

  const edit = await page.request.post(`/api/semanal/save?db=${DA_PORTO.dbPrefix}`, {
    form: {
      opcion: 'listar_excepciones_autoprogramacion',
      semana: String(PROGRAMMING_WEEK),
    },
  });
  expect(edit.status()).toBe(roleCase.canEdit ? 200 : 403);
}

async function switchToClientQualificationPhase(page) {
  const response = page.waitForResponse((item) => (
    item.url().includes('/api/semanal/list') && item.request().method() === 'GET'
  ));
  await page.evaluate(() => {
    document.querySelector('#Semanal_Confirmada').value = '1';
    window.PSHotModule.reload();
  });
  expect((await response).ok()).toBe(true);
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Calificación de Compromisos',
  );
  await expect(page.locator('#weeklyPhaseMobileLabel')).toHaveText('Calificación');
}

test.describe('Programación Semanal: permisos por rol', () => {
  for (const roleCase of ROLE_CASES) {
    test(`rol ${roleCase.code} respeta lectura y edición`, async ({ page }) => {
      await openProgrammingWeek(page, roleCase);
      await probeWeeklyPermissions(page, roleCase);

      const manageButtons = page.locator([
        '#btn_autoprogramar',
        '#btn_agregar_actividad',
        '#btn_cerrar_compromisos_semana',
      ].join(','));
      await expect(manageButtons).toHaveCount(3);

      if (roleCase.canEdit) {
        for (const button of await manageButtons.all()) {
          await expect(button).toBeVisible();
          await expect(button).toBeEnabled();
        }
      } else {
        for (const button of await manageButtons.all()) {
          await expect(button).toBeVisible();
          await expect(button).toBeDisabled();
        }
      }
    });
  }
});

test('avance móvil y API rechazan una actividad sin responsables', async ({ page }) => {
  await openJmcQualification(page);
  const original = (await weeklyRows(page, 4)).find((row) => String(row.Actividad).includes('Descapote'));
  expect(original).toBeTruthy();
  try {
    runSql(`UPDATE programacion_semanal SET Sub_Contratista=NULL, Responsable_AIA=NULL WHERE project_id=${JMC.projectId} AND row_id=${Number(original.Consecutivo)};`);
    await page.reload();
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    const card = page.locator('article.ps-mobile-card').filter({ hasText: 'Descapote' });
    let requests = 0;
    page.on('request', (request) => { if (request.url().includes('/api/semanal/save')) requests += 1; });
    await card.locator('input[data-mobile-prop="Ejecutado_Real"]').fill('61');
    await card.locator('[data-mobile-save-prop="Ejecutado_Real"]').click();
    await expect(card.locator('[data-mobile-save-status]')).toContainText('Falta Sub-Contratista');
    expect(requests).toBe(0);
    const direct = await postWeeklyUpdate(page, { ...original, Sub_Contratista: '', Responsable_AIA: '' }, 4, { Real: '61' });
    expect(direct.status()).toBe(422);
  } finally {
    restoreWeeklyRowSql(original);
    const restored = (await weeklyRows(page, 4))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(restored)).toEqual(weeklyState(original));
  }
});

test('API semanal rechaza fase, CNC incompleta y semana suplantada', async ({ page }) => {
  await openJmcQualification(page);
  const original = (await weeklyRows(page, 4))
    .find((row) => String(row.Actividad).includes('Movilización general'));
  expect(original).toBeTruthy();
  try {
    const cnc = await postWeeklyUpdate(page, original, 4, { Real: '39', Categoria_CNC: '', CNC: '', Observaciones_CNC: '' });
    expect(cnc.status()).toBe(422);
    const tnpSpoof = await postWeeklyUpdate(page, original, 4, {
      Real: '39', Es_TNP: '1', Categoria_CNC: '', CNC: '', Observaciones_CNC: '',
    });
    expect(tnpSpoof.status()).toBe(422);
    const phase = await postWeeklyUpdate(page, original, 4, { Compromiso: '41' });
    expect(phase.status()).toBe(409);
    const spoof = await postWeeklyUpdate(page, original, 3);
    expect(spoof.status()).toBe(422);
    const after = (await weeklyRows(page, 4))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(after)).toEqual(weeklyState(original));
  } finally {
    restoreWeeklyRowSql(original);
  }
});

test('API semanal rechaza un proyecto distinto al seleccionado', async ({ page }) => {
  await openJmcQualification(page);
  const list = await page.request.get('/api/semanal/list?db=da_porto&semana=1');
  expect(list.status()).toBe(403);
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const save = await page.request.post('/api/semanal/save?db=da_porto', { form: {
    opcion: 'modificar', semana: '1', Id: '0', _csrf_token: csrf || '',
  } });
  expect(save.status()).toBe(403);
  const checks = [
    page.request.get('/api/semanal/tnp-actividades?db=da_porto&semana=1'),
    page.request.get('/api/semanal/auto-program-log?db=da_porto&semana=1'),
    page.request.post('/api/semanal/auto-program', { form: { db: 'da_porto', semana: '0' } }),
    page.request.post('/api/semanal/reabrir?db=da_porto', {
      form: { semana: '0', motivo: '', _csrf_token: csrf || '' },
    }),
  ];
  for (const response of await Promise.all(checks)) expect(response.status()).toBe(403);

  // reabrir exige CSRF como el resto de mutaciones privilegiadas
  const sinCsrf = await page.request.post(`/api/semanal/reabrir?db=${JMC.dbPrefix}`, {
    form: { semana: '4', motivo: 'Motivo de reapertura suficientemente largo' },
  });
  expect(sinCsrf.status()).toBe(403);
  expect((await sinCsrf.json()).mensaje).toContain('CSRF');
});

test('rol R histórico solo puede calificar el compromiso confirmado', async ({ page }) => {
  await openJmcQualification(page);
  const original = (await weeklyRows(page, 4))
    .find((row) => String(row.Actividad).includes('Movilización general'));
  expect(original).toBeTruthy();
  try {
    const qualification = await postWeeklyUpdate(page, original, 4);
    expect(qualification.status()).toBe(200);
    const planning = await postWeeklyUpdate(page, original, 4, {
      Descripcion: `${original.Descripcion || ''} QA histórico`,
    });
    expect(planning.status()).toBe(409);
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const blocked = [
      page.request.post(`/api/semanal/reabrir?db=${JMC.dbPrefix}`, {
        form: { semana: '4', motivo: '', _csrf_token: csrf || '' },
      }),
      page.request.post('/api/cnp/save', { form: {
        Id: '1', semana: '4', Categoria_CNP: 'Programación', CNP: 'QA',
      } }),
      page.request.post('/api/cnp/reprogramar', { form: { Id: '1', semana: '4' } }),
      page.request.post('/api/cnc/save', { form: {
        Id: '1', semana: '4', Categoria_CNC: 'Administrativas',
        CNC: 'Otra', Observaciones_CNC: 'QA política',
      } }),
    ];
    for (const response of await Promise.all(blocked)) expect(response.status()).toBe(403);
    const after = (await weeklyRows(page, 4))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(after)).toEqual(weeklyState(original));
  } finally {
    restoreWeeklyRowSql(original);
  }
});

test('API CNP no reprograma una semana confirmada', async ({ page }) => {
  await loginAndSelectProject(page, PRUEBA);
  await changeWeek(page, 4, '/programacion-semanal/cnp');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const original = (await cnpRows(page, 4))[0];
  expect(original).toBeTruthy();
  try {
    const response = await page.request.post('/api/cnp/reprogramar', { form: {
      Id: String(original.Consecutivo), semana: '4',
    } });
    expect(response.status()).toBe(409);
    expect((await response.json()).respuesta).toBe('ERROR');
    const after = (await cnpRows(page, 4))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(after).toBeTruthy();
  } finally {
    restoreCnpRowSql(original, PRUEBA.projectId);
  }
});

test('semana sin actividades no fabrica filas ni tarjetas', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0], { width: 551, height: 750 });
  const response = await page.request.get('/api/semanal/list?db=da_porto&semana=1');
  expect(response.ok()).toBe(true);
  expect((await response.json()).data).toEqual([]);
  await expect(page.locator('article.ps-mobile-card')).toHaveCount(0);
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toBeVisible();

  await page.setViewportSize({ width: 787, height: 750 });
  await page.reload();
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const grid = await page.evaluate(() => ({
    rows: window.PSHotModule.getHotInstance().countRows(),
    text: document.querySelector('#hot-container').textContent,
  }));
  expect(grid.rows).toBe(0);
  expect(grid.text).not.toContain('Programada Manualmente');
});

test('toolbar tablet muestra texto comprensible sin overflow', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0], { width: 787, height: 750 });
  const state = await page.evaluate(() => {
    const visible = [...document.querySelectorAll('.ps-hot-toolbar-actions .btn-pdc-modern')]
      .filter((button) => getComputedStyle(button).display !== 'none');
    return {
      labels: visible.map((button) => button.innerText.trim()),
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });
  expect(state.labels.length).toBeGreaterThan(0);
  expect(state.labels.every((label) => label.length > 0)).toBe(true);
  expect(state.overflow).toBeLessThanOrEqual(1);
});

test('tabla semanal tablet usa superficies dark cuando el tema es dark', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0], { width: 787, height: 750 });
  await page.evaluate(() => window.AiaDesignSystem.setTheme('dark'));
  await expect.poll(() => page.evaluate(() => [
    '#hot-container', '.ps-hot-toolbar-shell',
    '.ps-hot-toolbar-actions .btn-pdc-modern',
    '.ps-toolbar-right .btn-filter-toggle',
    '#hot-container .handsontable thead th',
  ].every((selector) => {
    const color = getComputedStyle(document.querySelector(selector)).backgroundColor;
    const channels = color.match(/[\d.]+/g).slice(0, 3).map(Number);
    return channels.reduce((sum, value) => sum + value, 0) / 3 < 140;
  })), { timeout: 2000 }).toBe(true);
});

test('filtro sin resultados conserva dropdown y modales operables', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0]);
  await expectSectionDropdown(page);

  await page.locator('.leyenda_colores').click();
  const legendModal = page.locator('#modal_leyenda_colores_ps');
  await expect(legendModal).toBeVisible();
  await expect(legendModal.locator('.modal-title')).toContainText(
    'Programación de Compromisos',
  );
  await expect(legendModal.locator('.modal-body')).toContainText(
    'Defina compromisos viables',
  );
  await legendModal.locator('button[aria-label="Cerrar"]').click();
  await expect(legendModal).toBeHidden();

  let saveRequests = 0;
  page.on('request', (request) => {
    if (request.url().includes('/api/semanal/save')) saveRequests += 1;
  });
  await page.locator('#btn_cerrar_compromisos_semana').click();
  const closeModal = page.locator('#modal_cerrar_compromisos');
  await expect(closeModal).toBeVisible();
  await expect(closeModal.locator('.modal-title')).toContainText('Cierre de Compromisos');
  await closeModal.locator('#btn_cancelar_compromisos_semana').click();
  await expect(closeModal).toBeHidden();
  expect(saveRequests).toBe(0);

  const legendItems = page.locator('#psAlertsLegend .pdc-legend-item');
  await expect(legendItems).not.toHaveCount(0);
  const counts = await legendItems.locator('.count-badge').allTextContents();
  expect(counts.every((count) => count.trim() === '(0)')).toBe(true);
  if (!await legendItems.first().isVisible()) {
    await page.locator('.btn-filter-toggle').click();
    await expect(legendItems.first()).toBeVisible();
  }
  await legendItems.first().click();
  await expect(page.locator('#mobileAlertCount')).toHaveText('1');
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toHaveText(
    'No hay actividades con los filtros actuales.',
  );
  const filteredRows = await page.evaluate(() => (
    window.PSHotModule.getHotInstance().countRows()
  ));
  expect(filteredRows).toBe(0);

  await legendItems.first().click();
  await expect(page.locator('#mobileAlertCount')).toHaveText('0');

  await expect(page.locator('article.ps-mobile-card')).toHaveCount(0);
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toBeHidden();
});

test('calificación expone controles y modales sin escribir datos', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0]);
  await switchToClientQualificationPhase(page);
  let saveRequests = 0;
  page.on('request', (request) => {
    if (request.url().includes('/api/semanal/save')) saveRequests += 1;
  });

  for (const selector of [
    '#btn_autoprogramar',
    '#btn_agregar_actividad',
    '#btn_cerrar_compromisos_semana',
  ]) {
    await expect(page.locator(selector)).toBeHidden();
  }
  await expect(page.locator('#btn_informe_compromisos')).toBeVisible();
  await expect(page.locator('#btn_tnp')).toBeVisible();
  await expect(page.locator('#btn_reabrir_semana')).toBeVisible();

  await expectSectionDropdown(page);
  await page.locator('.leyenda_colores').click();
  const legendModal = page.locator('#modal_leyenda_colores_ps');
  await expect(legendModal).toBeVisible();
  await expect(legendModal.locator('.modal-title')).toContainText(
    'Calificación de Actividades',
  );
  await expect(legendModal.locator('.modal-body')).toContainText(
    'Cierre incumplidas con CNC',
  );
  await legendModal.locator('button[aria-label="Cerrar"]').click();
  await expect(legendModal).toBeHidden();

  await page.locator('#btn_reabrir_semana').click();
  const reopenModal = page.locator('#modal_reabrir_semana');
  await expect(reopenModal).toBeVisible();
  await expect(reopenModal.locator('#btn_confirmar_reabrir')).toBeDisabled();
  await reopenModal.getByRole('button', { name: 'Cancelar' }).click();
  await expect(reopenModal).toBeHidden();

  await page.locator('#btn_tnp').click();
  const tnpModal = page.locator('#modal_tnp');
  await expect(tnpModal).toBeVisible();
  await expect(tnpModal.locator('#tnp_actividad_select')).toBeAttached();
  expect(await tnpModal.locator('#tnp_categoria_cp option').count()).toBeGreaterThan(1);
  await tnpModal.getByRole('button', { name: 'Cerrar' }).click();
  await expect(tnpModal).toBeHidden();
  expect(saveRequests).toBe(0);
});
