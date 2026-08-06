import { test, expect } from '@playwright/test';
import { CREDENTIALS } from './fixtures/projects.mjs';
import { runSql } from './support/dbSnapshot.mjs';
import {
  changeWeek,
  login,
  postFormJson,
  selectProject,
} from './support/session.mjs';

const JMC = {
  name: 'Optimización Aeropuerto JMC',
  projectId: 68,
  dbPrefix: 'optimizacionJMC',
};
const DA_PORTO = {
  name: 'Preconstrucción Da Porto',
  projectId: 76,
  dbPrefix: 'da_porto',
};
const PRUEBA = {
  name: 'Prueba',
  projectId: 27,
  dbPrefix: 'prueba',
};
const QUALIFICATION_WEEK = 4;
const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 1180, height: 820 },
  { name: 'desktop', width: 1440, height: 900 },
];
const THEMES = ['dark'];
const SECTIONS = [
  {
    key: 'CNP',
    path: '/programacion-semanal/cnp',
    endpoint: '/api/cnp/list',
    action: 'Editar causa',
    actionExpectedInJmc: false,
  },
  {
    key: 'CNC',
    path: '/programacion-semanal/cnc',
    endpoint: '/api/cnc/list',
    action: 'Editar causa',
    actionExpectedInJmc: false,
  },
  {
    key: 'CIC',
    path: '/programacion-semanal/cic',
    endpoint: '/api/cic/list',
    action: 'Calificar proveedor',
    actionExpectedInJmc: false,
  },
];

function trapRuntimeErrors(page) {
  const errors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(`console: ${message.text()}`);
  });
  page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
  return errors;
}

function expectApiOk(response, label) {
  expect(response.ok, `${label}: HTTP ${response.status}`).toBe(true);
  expect(response.payload.parseError, `${label}: ${JSON.stringify(response.payload)}`).toBeFalsy();
  expect(response.payload.respuesta, `${label}: ${JSON.stringify(response.payload)}`).not.toBe('ERROR');
}

async function waitForSection(page) {
  await page.locator('#dt_cliente').waitFor({ state: 'attached', timeout: 30000 });
  await page.waitForFunction(() => {
    const jq = window.jQuery;
    if (!jq?.fn?.dataTable?.isDataTable('#dt_cliente')) return false;
    const table = jq('#dt_cliente').DataTable();
    const data = table.settings()[0]?.json?.data;
    if (!Array.isArray(data)) return false;
    const applied = table.rows({ search: 'applied' }).count();
    if (!window.matchMedia('(max-width: 768px)').matches) {
      return data.length === 0
        ? Boolean(document.querySelector('#dt_cliente .dataTables_empty'))
        : applied > 0;
    }
    const cards = document.querySelectorAll('#ps-legacy-card-view .ps-legacy-card').length;
    const empty = Boolean(document.querySelector('#ps-legacy-card-view .ps-legacy-card-empty'));
    return applied > 0 ? cards === applied : empty;
  }, null, { timeout: 30000 });
  await expect(page.locator('.dataTables_processing')).toBeHidden({ timeout: 30000 });
}

async function openSection(
  page,
  section,
  viewport,
  week = QUALIFICATION_WEEK,
  project = JMC,
  auth = CREDENTIALS,
) {
  await page.setViewportSize(viewport);
  await login(page, auth);
  await selectProject(page, project);
  const dataResponse = page.waitForResponse((response) => (
    response.url().includes(section.endpoint) && response.request().method() === 'POST'
  ));
  await changeWeek(page, week, section.path);
  expect((await dataResponse).ok(), `${section.key} list request`).toBe(true);
  await waitForSection(page);
}

async function reloadSection(page, section) {
  await page.goto(section.path, { waitUntil: 'commit' });
  const response = await page.waitForResponse((item) => item.url().includes(section.endpoint));
  expect(response.ok(), `${section.key} reload request`).toBe(true);
  await waitForSection(page);
}

async function expectTheme(page, theme) {
  // F0/Task 9: theme.js ya no expone setTheme; dark se aplica sin conmutacion.
  // Esto solo confirma el estado en vez de forzarlo.
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', theme);
}

async function readLayoutState(page, viewportName) {
  return page.evaluate((mode) => {
    const visible = (element) => {
      if (!element) return false;
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden'
        && rect.width > 0 && rect.height > 0;
    };
    const cardView = document.querySelector('#ps-legacy-card-view');
    const table = document.querySelector('#dt_cliente');
    const cards = [...document.querySelectorAll('#ps-legacy-card-view .ps-legacy-card')];
    const scrollBody = document.querySelector('#cuadroTabla .dataTables_scrollBody');
    const controls = [...document.querySelectorAll(
      '.btn-pdc-modern, .ps-legacy-card-action, #dt_cliente button.editar, #dt_cliente button.reprogramar',
    )].filter(visible);
    const targetSizes = controls.map((element) => {
      const rect = element.getBoundingClientRect();
      return { label: element.getAttribute('aria-label') || element.textContent.trim()
        || element.id || element.className, width: Math.round(rect.width), height: Math.round(rect.height) };
    });
    const overflowNodes = mode === 'mobile'
      ? [cardView, ...cards, ...cards.flatMap((card) => [...card.children])]
      : [scrollBody, table];
    const internalOverflow = overflowNodes.filter(visible).reduce((largest, element) => (
      Math.max(largest, element.scrollWidth - element.clientWidth)
    ), 0);
    const surface = mode === 'mobile' ? cards[0] : document.querySelector('#cuadroTabla');
    const html = document.documentElement;
    const bodyStyle = getComputedStyle(document.body);
    const surfaceStyle = surface ? getComputedStyle(surface) : null;
    return {
      actionText: controls.map((element) => element.textContent.trim()),
      cardCount: cards.length,
      cardsVisible: visible(cardView),
      darkBody: document.body.classList.contains('dark-mode'),
      internalOverflow,
      undersizedTargets: targetSizes.filter(({ width, height }) => width < 44 || height < 44),
      pageOverflow: html.scrollWidth - html.clientWidth,
      rowCount: document.querySelectorAll('#dt_cliente tbody tr:not(.dataTables_empty)').length,
      signature: `${bodyStyle.backgroundColor}|${surfaceStyle?.backgroundColor}|${surfaceStyle?.color}`,
      tableVisible: visible(table),
      theme: html.dataset.aiaTheme,
    };
  }, viewportName);
}

function expectLayout(state, section, viewport, theme) {
  expect(state.theme, `${section.key} ${viewport.name} ${theme} theme`).toBe(theme);
  expect(state.darkBody, `${section.key} ${viewport.name} ${theme} body`).toBe(theme === 'dark');
  expect(state.pageOverflow, `${section.key} ${viewport.name} ${theme} page overflow`).toBeLessThanOrEqual(1);
  expect(state.internalOverflow, `${section.key} ${viewport.name} ${theme} internal overflow`).toBeLessThanOrEqual(1);
  expect(state.undersizedTargets, `${section.key} ${viewport.name} ${theme} targets`).toEqual([]);
  if (viewport.name === 'mobile') {
    expect(state.cardsVisible, `${section.key} mobile cards`).toBe(true);
    expect(state.tableVisible, `${section.key} mobile table`).toBe(false);
    expect(state.cardCount, `${section.key} mobile records`).toBeGreaterThan(0);
    expect(state.actionText.some((label) => label.includes(section.action)))
      .toBe(section.actionExpectedInJmc);
  } else {
    expect(state.cardsVisible, `${section.key} ${viewport.name} cards`).toBe(false);
    expect(state.tableVisible, `${section.key} ${viewport.name} table`).toBe(true);
    expect(state.rowCount, `${section.key} ${viewport.name} records`).toBeGreaterThan(0);
  }
}

async function expectEmptyState(page, viewportName) {
  await page.evaluate(() => {
    window.jQuery('#dt_cliente').DataTable().search('__QA_NO_EXISTE_20260709__').draw();
  });
  if (viewportName === 'mobile') {
    const empty = page.locator('#ps-legacy-card-view .ps-legacy-card-empty');
    await expect(empty).toBeVisible();
    await expect(empty).toContainText('No hay registros');
  } else {
    const empty = page.locator('#dt_cliente tbody td.dataTables_empty');
    await expect(empty).toBeVisible();
    await expect(empty).not.toHaveText('');
  }
  await page.evaluate(() => window.jQuery('#dt_cliente').DataTable().search('').draw());
}

async function listRows(page, endpoint, week) {
  const response = await postFormJson(page, endpoint, { semana: week });
  expectApiOk(response, `${endpoint} week ${week}`);
  return response.payload.data || [];
}

function rowId(row) {
  return String(row.Consecutivo ?? row.row_id ?? row.Id);
}

function cnpState(row) {
  return {
    Activa: String(row.Activa ?? ''),
    Categoria_CNP: row.Categoria_CNP ?? null,
    CNP: row.CNP ?? null,
    Observaciones_CNP: row.Observaciones_CNP ?? null,
    Responsable_AIA: String(row.Responsable_AIA ?? ''),
    Reprogramada_Por_Usuario: String(row.Reprogramada_Por_Usuario ?? ''),
  };
}

function cncState(row) {
  return {
    Categoria_CNC: row.Categoria_CNC ?? null,
    CNC: row.CNC ?? null,
    Observaciones_CNC: row.Observaciones_CNC ?? null,
  };
}

function sqlValue(value) {
  if (value === null || value === undefined) return 'NULL';
  return `X'${Buffer.from(String(value), 'utf8').toString('hex')}'`;
}

function tableChecksum(table) {
  return runSql(`CHECKSUM TABLE \`${table}\`;`).trim().split(/\s+/).at(-1);
}

function restoreCnpRow(project, row) {
  const id = Number(row.row_id ?? row.Consecutivo);
  expect(Number.isInteger(id) && id > 0, 'CNP restore row id').toBe(true);
  const assignments = [
    `Activa=${Number(row.Activa)}`,
    `Responsable_AIA=${sqlValue(row.Responsable_AIA)}`,
    `Categoria_CNP=${sqlValue(row.Categoria_CNP)}`,
    `CNP=${sqlValue(row.CNP)}`,
    `Observaciones_CNP=${sqlValue(row.Observaciones_CNP)}`,
    `Reprogramada_Por_Usuario=${Number(row.Reprogramada_Por_Usuario || 0)}`,
  ];
  runSql(`UPDATE programacion_semanal SET ${assignments.join(', ')} `
    + `WHERE project_id=${Number(project.projectId)} AND row_id=${id};`);
}

function restoreCncRow(project, row) {
  const id = Number(row.row_id ?? row.Consecutivo);
  expect(Number.isInteger(id) && id > 0, 'CNC restore row id').toBe(true);
  runSql(`UPDATE programacion_semanal SET Categoria_CNC=${sqlValue(row.Categoria_CNC)}, `
    + `CNC=${sqlValue(row.CNC)}, Observaciones_CNC=${sqlValue(row.Observaciones_CNC)} `
    + `WHERE project_id=${Number(project.projectId)} AND row_id=${id};`);
}

function restoreCicRow(project, row) {
  const excluded = new Set(['project_id', 'Id', 'semanasEnProyecto']);
  const numeric = new Set(['Semana', 'Cal_Integral', 'Cal_Integral_Acum']);
  const fields = Object.entries(row).filter(([key]) => !excluded.has(key)
    && /^[A-Za-z0-9_]+$/.test(key));
  const assignments = fields.map(([key, value]) => {
    const encoded = numeric.has(key)
      ? (value === null || value === undefined ? 'NULL' : String(Number(value)))
      : sqlValue(value);
    return `\`${key}\`=${encoded}`;
  });
  runSql(`UPDATE cic SET ${assignments.join(', ')} WHERE project_id=${Number(project.projectId)} `
    + `AND Id=${Number(row.Id)};`);
}

async function waitForViewportMode(page, viewportName) {
  await expect.poll(async () => {
    const state = await readLayoutState(page, viewportName);
    return viewportName === 'mobile'
      ? state.cardsVisible && !state.tableVisible
      : !state.cardsVisible && state.tableVisible;
  }, { timeout: 10000 }).toBe(true);
}

for (const section of SECTIONS) {
  test(`${section.key}: cards mobile y tablas tablet/desktop cumplen temas y overflow`, async ({ page }) => {
    const errors = trapRuntimeErrors(page);
    await openSection(page, section, VIEWPORTS[0]);
    for (const viewport of VIEWPORTS) {
      await test.step(viewport.name, async () => {
        await page.setViewportSize(viewport);
        await waitForViewportMode(page, viewport.name);
        for (const theme of THEMES) {
          await expectTheme(page, theme);
          const state = await readLayoutState(page, viewport.name);
          expectLayout(state, section, viewport, theme);
        }
        await expectEmptyState(page, viewport.name);
      });
    }
    expect(errors, `${section.key} runtime errors`).toEqual([]);
  });
}

test('CNP mobile alinea acciones, limpia HTML y ofrece búsqueda explicita', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, VIEWPORTS[0]);
  const firstTitle = page.locator('#ps-legacy-card-view .ps-legacy-card h3').first();
  await expect(firstTitle).toBeVisible();
  await expect(firstTitle).not.toContainText('<b>');
  await expect(firstTitle).not.toContainText('<small>');
  const cardStates = await page.evaluate(() => {
    const states = ['is-cnp-critical', 'is-cnp-non-critical',
      'is-cnp-overdue-critical', 'is-cnp-overdue-non-critical'];
    const cards = [...document.querySelectorAll('#ps-legacy-card-view .ps-legacy-card')];
    const exact = cards.every((card) => states.filter((state) => card.classList.contains(state)).length === 1);
    const sample = cards[0];
    const colors = states.map((state) => {
      states.forEach((name) => sample.classList.remove(name));
      sample.classList.add(state);
      return getComputedStyle(sample).backgroundColor;
    });
    return { count: cards.length, exact, colors };
  });
  expect(cardStates.count).toBeGreaterThan(0);
  expect(cardStates.exact).toBe(true);
  expect(new Set(cardStates.colors).size).toBe(4);

  const legend = page.locator('.ps-actions-row .leyenda_colores');
  const sections = page.locator('#dropdownTriggerSecciones');
  await expect(legend).toBeVisible();
  await expect(sections).toBeVisible();
  const alignment = await page.evaluate(() => {
    const a = document.querySelector('.ps-actions-row .leyenda_colores').getBoundingClientRect();
    const b = document.querySelector('#dropdownTriggerSecciones').getBoundingClientRect();
    return { topDelta: Math.abs(a.top - b.top), heightDelta: Math.abs(a.height - b.height) };
  });
  expect(alignment.topDelta).toBeLessThanOrEqual(1);
  expect(alignment.heightDelta).toBeLessThanOrEqual(1);

  const search = page.locator('#ps_legacy_search');
  const searchLabel = page.locator('label[for="ps_legacy_search"]');
  const clear = page.locator('#btn_limpiar_buscador');
  // Copy corto a propósito: los rótulos largos truncaban el placeholder a 1180px.
  const expectedSearchCopy = 'Buscar actividad o causa';
  await expect(search).toBeVisible();
  await expect(search).toHaveAttribute('placeholder', expectedSearchCopy);
  await expect(search).toHaveAccessibleName(expectedSearchCopy);
  await expect(searchLabel).toHaveText(expectedSearchCopy);
  await expect(searchLabel).toHaveClass(/\bsr-only\b/);
  await expect(clear).toBeDisabled();
  await search.fill('__QA_NO_EXISTE__');
  await expect(clear).toBeEnabled();
  await expect(page.locator('#ps-legacy-card-view .ps-legacy-card-empty')).toBeVisible();
  await clear.click();
  await expect(search).toHaveValue('');
  await expect(clear).toBeDisabled();
  await expect(page.locator('#ps-legacy-card-view .ps-legacy-card')).not.toHaveCount(0);
});

test('CNP tablet reserva ancho legible para la búsqueda', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, VIEWPORTS[1]);
  const layout = await page.evaluate(() => {
    const box = (selector) => document.querySelector(selector).getBoundingClientRect();
    const target = parseFloat(getComputedStyle(document.documentElement)
      .getPropertyValue('--ds-target-min')) || 44;
    const toolbar = box('.toolbarFiltro');
    const label = box('.ps-toolbar-filter label');
    const input = box('#ps_legacy_search');
    const clearButton = document.querySelector('#btn_limpiar_buscador');
    const clear = clearButton.getBoundingClientRect();
    return { target, toolbarWidth: toolbar.width, labelHeight: label.height,
      inputWidth: input.width, clearWidth: clear.width, clearRight: clear.right,
      clearScrollWidth: clearButton.scrollWidth, toolbarRight: toolbar.right };
  });
  expect(layout.toolbarWidth).toBeGreaterThanOrEqual(layout.target * 7);
  expect(layout.labelHeight).toBeLessThanOrEqual(layout.target);
  expect(layout.inputWidth).toBeGreaterThanOrEqual(layout.target * 5);
  expect(layout.clearWidth).toBeGreaterThanOrEqual(layout.target);
  expect(layout.clearScrollWidth).toBeLessThanOrEqual(layout.clearWidth + 1);
  expect(layout.clearRight).toBeLessThanOrEqual(layout.toolbarRight + 1);
});

test('CNP 787 reúne la barra en una fila con Secciones a la derecha', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, { width: 787, height: 750 }, 1, DA_PORTO, CREDENTIALS);
  const layout = await page.evaluate(() => {
    const box = (selector) => document.querySelector(selector).getBoundingClientRect();
    const row = box('.ps-actions-row');
    const controls = ['.leyenda_colores', '#ps_legacy_search',
      '#btn_limpiar_buscador', '#dropdownTriggerSecciones'].map(box);
    const tops = controls.map(({ top }) => top);
    const table = box('.dataTables_scrollHead');
    const gap = parseFloat(getComputedStyle(document.documentElement)
      .getPropertyValue('--spacing-xs')) || 4;
    return { row, controls, topSpread: Math.max(...tops) - Math.min(...tops),
      rightDelta: Math.abs(row.right - controls.at(-1).right),
      tableGap: table.top - row.bottom, gap,
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth };
  });
  expect(layout.topSpread).toBeLessThanOrEqual(1);
  expect(layout.rightDelta).toBeLessThanOrEqual(1);
  expect(layout.tableGap).toBeGreaterThanOrEqual(layout.gap - 1);
  expect(layout.overflow).toBeLessThanOrEqual(0);
  for (const control of layout.controls) {
    expect(control.left).toBeGreaterThanOrEqual(layout.row.left - 1);
    expect(control.right).toBeLessThanOrEqual(layout.row.right + 1);
  }
  await page.setViewportSize({ width: 1440, height: 900 });
  const desktop = await page.evaluate(() => {
    const box = (selector) => document.querySelector(selector).getBoundingClientRect();
    const row = box('.ps-actions-row');
    const controls = ['.leyenda_colores', '#ps_legacy_search',
      '#btn_limpiar_buscador', '#dropdownTriggerSecciones'].map(box);
    const tops = controls.map(({ top }) => top);
    return { topSpread: Math.max(...tops) - Math.min(...tops),
      rightDelta: Math.abs(row.right - controls.at(-1).right),
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth };
  });
  expect(desktop.topSpread).toBeLessThanOrEqual(1);
  expect(desktop.rightDelta).toBeLessThanOrEqual(1);
  expect(desktop.overflow).toBeLessThanOrEqual(0);
});

for (const section of SECTIONS.filter(({ key }) => key === 'CNP')) {
  test(`${section.key}: Editar causa desde card abre controles utilizables`, async ({ page }) => {
    const errors = trapRuntimeErrors(page);
    await openSection(page, section, VIEWPORTS[0], 1, DA_PORTO, CREDENTIALS);
    const action = page.locator(
      '#ps-legacy-card-view .ps-legacy-card-action[data-legacy-action="edit"]',
    ).first();
    await expect(action).toBeVisible();
    await page.locator('#dt_cliente tbody button.editar').evaluateAll((buttons) => {
      buttons.forEach((button) => button.remove());
    });
    await action.click();
    const editorState = await page.evaluate(() => {
      const role = document.querySelector('#permiso_canonico')?.value || '';
      const week = Number(document.querySelector('#semana, #semana_PHP')?.value || 0);
      const maxWeek = Number(document.querySelector('#Max_Semana')?.value || 0);
      return { role, week, maxWeek,
        canEdit: window.RbacCapabilities?.canEditLps(role, week, maxWeek),
        categoryCount: document.querySelectorAll('#select_Categoria_CNC').length,
        editorCount: document.querySelectorAll('#ps-legacy-mobile-editor').length };
    });
    expect(editorState.categoryCount, JSON.stringify(editorState)).toBeGreaterThan(0);
    await expect(page.locator('#select_Categoria_CNC:visible')).toBeVisible();
    await expect(page.locator('#select_CNC:visible')).toBeVisible();
    await expect(page.locator('#select_Observaciones_CNC:visible')).toBeVisible();
    await expect(page.locator('#btn_guardar_editar:visible')).toBeVisible();
    const editorTargets = await page.locator(
      '#btn_guardar_editar:visible, #btn_cancelar_editar:visible',
    ).evaluateAll((buttons) => buttons.map((button) => button.getBoundingClientRect().height));
    expect(Math.min(...editorTargets)).toBeGreaterThanOrEqual(44);
    await page.locator('#btn_cancelar_editar:visible').click();
    expect(errors, `${section.key} edit action errors`).toEqual([]);
  });
}

test('CNP: editar causa persiste, recarga y restaura la fila original', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  const errors = trapRuntimeErrors(page);
  let original;
  let selectedId;
  try {
    await openSection(page, section, VIEWPORTS[0], 1, DA_PORTO, CREDENTIALS);
    const rows = await listRows(page, section.endpoint, 1);
    const action = page.locator('#ps-legacy-card-view [data-legacy-action="edit"]').first();
    await expect(action).toBeVisible();
    await action.click();
    selectedId = await page.locator('#encabezado #Id').inputValue();
    original = rows.find((row) => rowId(row) === selectedId);
    expect(original, `CNP selected row ${selectedId}`).toBeTruthy();
    const category = page.locator('#select_Categoria_CNC:visible');
    const cause = page.locator('#select_CNC:visible');
    const observation = page.locator('#select_Observaciones_CNC:visible');
    await expect(category).toBeVisible();
    await expect.poll(() => cause.locator('option:not([value=""])').count()).toBeGreaterThan(0);
    const categoryValue = await category.inputValue();
    expect(categoryValue).toBeTruthy();
    const causes = await cause.locator('option:not([value=""])').evaluateAll((options) => (
      options.map((option) => option.value)
    ));
    expect(causes.length, 'CNP category must expose causes').toBeGreaterThan(0);
    const changedCause = causes.find((value) => value !== original.CNP) || causes[0];
    await cause.selectOption(changedCause);
    const marker = `QA editar CNP ${Date.now()}`;
    await observation.fill(marker);
    const saveResponse = page.waitForResponse((response) => response.url().includes('/api/cnp/save'));
    await page.locator('#btn_guardar_editar:visible').click();
    const savedResponse = await saveResponse;
    expect(savedResponse.ok(), 'CNP save request').toBe(true);
    const submitted = new URLSearchParams(savedResponse.request().postData() || '');
    expect(submitted.get('Consecutivo')).toBe(selectedId);
    expect(submitted.get('Categoria_CNP')).toBe(categoryValue);
    expect(submitted.get('CNP')).toBe(changedCause);
    expect(submitted.get('Observaciones_CNP')).toBe(marker);
    expect((await savedResponse.json()).respuesta).toBe('BIEN');
    let persisted = (await listRows(page, section.endpoint, 1))
      .find((row) => rowId(row) === rowId(original));
    expect(persisted.Categoria_CNP).toBe(categoryValue);
    expect(persisted.CNP).toBe(changedCause);
    expect(persisted.Observaciones_CNP).toBe(marker);
    await reloadSection(page, section);
    await expect(page.locator('#ps-legacy-card-view')).toContainText(marker);
    await page.locator('#ps-legacy-card-view [data-legacy-action="edit"]').first().click();
    await expect(page.locator('#btn_guardar_editar:visible')).toBeVisible();
    await expect(page.locator('#select_Observaciones_CNC:visible')).toHaveValue(marker);
    const noOpResponse = page.waitForResponse((response) => response.url().includes('/api/cnp/save'));
    await page.locator('#btn_guardar_editar:visible').click();
    expect((await (await noOpResponse).json()).respuesta).toBe('BIEN');
    persisted = (await listRows(page, section.endpoint, 1))
      .find((row) => rowId(row) === rowId(original));
    expect(persisted.Observaciones_CNP).toBe(marker);
    expect(errors, 'CNP edit runtime errors').toEqual([]);
  } finally {
    if (original) {
      restoreCnpRow(DA_PORTO, original);
      const restored = (await listRows(page, section.endpoint, 1))
        .find((row) => rowId(row) === rowId(original));
      expect(cnpState(restored), 'CNP row after finally').toEqual(cnpState(original));
    }
  }
});

test('CNC: editar causa persiste, recarga y restaura la fila original', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNC');
  const errors = trapRuntimeErrors(page);
  let original;
  try {
    await openSection(page, section, VIEWPORTS[0], 5, PRUEBA, CREDENTIALS);
    const rows = await listRows(page, section.endpoint, 5);
    await page.locator('#dt_cliente tbody button.editar').evaluateAll((buttons) => {
      buttons.forEach((button) => button.remove());
    });
    await page.evaluate(() => window.jQuery('#dt_cliente').DataTable().draw(false));
    const action = page.locator('#ps-legacy-card-view [data-legacy-action="edit"]');
    await expect(action).toHaveCount(1);
    await action.click();
    const selectedId = await page.locator('#encabezado #Id').inputValue();
    original = rows.find((row) => rowId(row) === selectedId);
    expect(original, `CNC selected row ${selectedId}`).toBeTruthy();
    const category = page.locator('#select_Categoria_CNC:visible');
    const cause = page.locator('#select_CNC:visible');
    const observation = page.locator('#select_Observaciones_CNC:visible');
    await expect(category).toBeVisible();
    await expect.poll(() => cause.locator('option:not([value=""])').count()).toBeGreaterThan(0);
    const causes = await cause.locator('option:not([value=""])').evaluateAll((options) => (
      options.map((option) => option.value)
    ));
    const changedCause = causes.find((value) => value !== original.CNC) || causes[0];
    await cause.selectOption(changedCause);
    const marker = `QA editar CNC ${Date.now()}`;
    await observation.fill(marker);
    const saveResponse = page.waitForResponse((response) => response.url().includes('/api/cnc/save'));
    await page.locator('#btn_guardar_editar:visible').click();
    const savedResponse = await saveResponse;
    expect(savedResponse.ok(), 'CNC save request').toBe(true);
    const submitted = new URLSearchParams(savedResponse.request().postData() || '');
    expect(submitted.get('Consecutivo')).toBe(selectedId);
    expect(submitted.get('Categoria_CNC')).toBe(await category.inputValue());
    expect(submitted.get('CNC')).toBe(changedCause);
    expect(submitted.get('Observaciones_CNC')).toBe(marker);
    expect((await savedResponse.json()).respuesta).toBe('BIEN');
    let persisted = (await listRows(page, section.endpoint, 5))
      .find((row) => rowId(row) === selectedId);
    expect(persisted.CNC).toBe(changedCause);
    expect(persisted.Observaciones_CNC).toBe(marker);
    await reloadSection(page, section);
    await expect(page.locator('#ps-legacy-card-view')).toContainText(marker);
    await page.locator('#ps-legacy-card-view [data-legacy-action="edit"]').click();
    await expect(page.locator('#btn_guardar_editar:visible')).toBeVisible();
    await expect(page.locator('#select_Observaciones_CNC:visible')).toHaveValue(marker);
    const noOpResponse = page.waitForResponse((response) => response.url().includes('/api/cnc/save'));
    await page.locator('#btn_guardar_editar:visible').click();
    expect((await (await noOpResponse).json()).respuesta).toBe('BIEN');
    persisted = (await listRows(page, section.endpoint, 5))
      .find((row) => rowId(row) === selectedId);
    expect(persisted.Observaciones_CNC).toBe(marker);
    expect(errors, 'CNC edit runtime errors').toEqual([]);
  } finally {
    if (original) {
      restoreCncRow(PRUEBA, original);
      const restored = (await listRows(page, section.endpoint, 5))
        .find((row) => rowId(row) === rowId(original));
      expect(cncState(restored), 'CNC row after finally').toEqual(cncState(original));
    }
  }
});

test('CNC: un error de guardado conserva el editor y muestra el fallo', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNC');
  await openSection(page, section, VIEWPORTS[0], 5, PRUEBA, CREDENTIALS);
  await page.locator('#ps-legacy-card-view [data-legacy-action="edit"]').click();
  await page.route('**/api/cnc/save', (route) => route.fulfill({
    contentType: 'application/json',
    body: JSON.stringify({ respuesta: 'ERROR', mensaje: 'QA rechazo controlado' }),
  }));
  await page.locator('#btn_guardar_editar:visible').click();
  await expect(page.locator('#btn_guardar_editar:visible')).toBeVisible();
  await expect(page.locator('#mensajeActualizacion')).toContainText('Error');
});

test('CNC: Otra exige observación y no degrada la justificación', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNC');
  let original;
  try {
    await openSection(page, section, VIEWPORTS[0], 5, PRUEBA, CREDENTIALS);
    original = (await listRows(page, section.endpoint, 5))[0];
    expect(original).toBeTruthy();
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const response = await page.request.post('/api/cnc/save', { form: {
      Consecutivo: String(original.Consecutivo), semana: '5',
      Categoria_CNC: original.Categoria_CNC, CNC: 'Otra', Observaciones_CNC: '',
      _csrf_token: csrf || '',
    } });
    expect(response.status()).toBe(422);
    expect((await response.json()).respuesta).toBe('ERROR');
    const after = (await listRows(page, section.endpoint, 5))
      .find((row) => rowId(row) === rowId(original));
    expect(cncState(after)).toEqual(cncState(original));
  } finally { if (original) restoreCncRow(PRUEBA, original); }
});

test('CNP: reprogramar desde card cambia el estado y finally lo restaura', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  const errors = trapRuntimeErrors(page);
  let original;
  let selectedId;
  try {
    await openSection(page, section, VIEWPORTS[0], 1, DA_PORTO, CREDENTIALS);
    await expectTheme(page, 'dark');
    const before = await listRows(page, section.endpoint, 1);
    await page.locator('#dt_cliente tbody button.reprogramar').evaluateAll((buttons) => {
      buttons.forEach((button) => button.remove());
    });
    await page.evaluate(() => window.jQuery('#dt_cliente').DataTable().draw(false));
    const action = page.locator(
      '#ps-legacy-card-view [data-legacy-action="reprogram"]',
    ).first();
    await expect(action).toBeVisible();
    let requestCount = 0;
    page.on('request', (request) => {
      if (request.url().includes('/api/cnp/reprogramar')) requestCount += 1;
    });
    await action.click();
    await expect(page.locator('#modalReprogramar')).toBeVisible();
    const modalSurfaces = await page.evaluate(() => ({
      content: getComputedStyle(document.querySelector('#modalReprogramar .modal-content')).backgroundColor,
      body: getComputedStyle(document.querySelector('#modalReprogramar .modal-body')).backgroundColor,
      footer: getComputedStyle(document.querySelector('#modalReprogramar .modal-footer')).backgroundColor,
      cancelHeight: document.querySelector('#modalReprogramar [aria-label="Cancelar"]')
        .getBoundingClientRect().height,
    }));
    expect(modalSurfaces.body).toBe(modalSurfaces.content);
    expect(modalSurfaces.footer).toBe(modalSurfaces.content);
    expect(modalSurfaces.cancelHeight).toBeGreaterThanOrEqual(44);
    selectedId = await page.locator('#encabezado #Id').inputValue();
    original = before.find((row) => rowId(row) === selectedId);
    expect(original, `CNP selected row ${selectedId}`).toBeTruthy();
    await page.locator('#modalReprogramar [aria-label="Cancelar"]').click();
    await expect(page.locator('#modalReprogramar')).toBeHidden();
    await expect(page.locator('.modal-backdrop')).toHaveCount(0);
    expect(requestCount, 'Cancelar no debe reprogramar').toBe(0);
    await action.click();
    await expect(page.locator('#modalReprogramar')).toBeVisible();
    const response = page.waitForResponse((item) => item.url().includes('/api/cnp/reprogramar'));
    await page.locator('#reprogramar-usuario').click();
    const reprogrammedResponse = await response;
    expect(reprogrammedResponse.ok(), 'CNP reprogram request').toBe(true);
    expect((await reprogrammedResponse.json()).respuesta).toBe('BIEN');
    expect(requestCount, 'Reprogramar debe enviar exactamente una solicitud').toBe(1);
    await expect.poll(async () => {
      const rows = await listRows(page, section.endpoint, 1);
      return rows.some((row) => rowId(row) === selectedId);
    }).toBe(false);
    await reloadSection(page, section);
    await expect(page.locator('#ps-legacy-card-view .ps-legacy-card')).toHaveCount(before.length - 1);
    const dbState = runSql(`SELECT CONCAT(Activa, '|', Reprogramada_Por_Usuario) `
      + `FROM programacion_semanal WHERE project_id=${DA_PORTO.projectId} AND row_id=${Number(selectedId)};`).trim();
    expect(dbState).toBe('1|1');
    expect(errors, 'CNP reprogram runtime errors').toEqual([]);
  } finally {
    if (original) {
      restoreCnpRow(DA_PORTO, original);
      const restored = (await listRows(page, section.endpoint, 1))
        .find((row) => rowId(row) === rowId(original));
      expect(restored, 'CNP reprogrammed row restored').toBeTruthy();
      expect(cnpState(restored), 'CNP reprogram state after finally').toEqual(cnpState(original));
    }
  }
});

test('CIC rechaza semana suplantada y campos fuera del formulario', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CIC');
  let row;
  try {
  await openSection(page, section, VIEWPORTS[0], 6, PRUEBA, CREDENTIALS);
  row = (await listRows(page, section.endpoint, 6))[0];
  expect(row).toBeTruthy();
  const prefix = row.tipo_proveedor === 'Mano de Obra' ? 'mdo' : 'si';
  const field = `${prefix}_cal_1`;
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const base = { opcion: `modificar_${prefix}`, Id: String(row.Id),
    [`${prefix}_Observaciones`]: row.Observaciones || '', [field]: row[field] || '',
    _csrf_token: csrf || '' };
  const wrongWeek = await page.request.post('/api/cic/save', { form: {
    ...base, semana: String(Number(row.Semana) + 1),
  } });
  expect(wrongWeek.status()).toBe(409);
  const unknown = await page.request.post('/api/cic/save', { form: {
    ...base, semana: String(row.Semana), [`${prefix}_intruso`]: '1',
  } });
  expect(unknown.status()).toBe(422);
  const invalidValue = await page.request.post('/api/cic/save', { form: {
    ...base, semana: String(row.Semana), [field]: '2',
  } });
  expect(invalidValue.status()).toBe(422);
  const after = (await listRows(page, section.endpoint, 6))
    .find((item) => String(item.Id) === String(row.Id));
  expect(after[field]).toBe(row[field]);
  } finally { if (row) restoreCicRow(PRUEBA, row); }
});

test('CIC: calificar proveedor desde card persiste y finally restaura', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CIC');
  const errors = trapRuntimeErrors(page);
  let original;
  try {
    await openSection(page, section, VIEWPORTS[0], 6, PRUEBA, CREDENTIALS);
    const before = await listRows(page, section.endpoint, 6);
    const action = page.locator(
      '#ps-legacy-card-view [data-legacy-action="edit"]',
    ).first();
    await expect(action).toBeVisible();
    await page.locator('#dt_cliente tbody button.editar').evaluateAll((buttons) => {
      buttons.forEach((button) => button.remove());
    });
    await action.click();
    const modal = page.locator('#modalcic_mdo.show, #modalcic_si.show');
    await expect(modal).toBeVisible();
    const selectedId = await page.locator('#encabezado #Id').inputValue();
    original = before.find((row) => String(row.Id) === selectedId);
    expect(original, `CIC selected provider ${selectedId}`).toBeTruthy();
    const modalId = await modal.getAttribute('id');
    const prefix = modalId === 'modalcic_mdo' ? 'mdo' : 'si';
    const changedScore = String(original[`${prefix}_cal_1`]) === '1' ? '0.5' : '1';
    const score = modal.locator(`input[name="${prefix}_cal_1"][value="${changedScore}"]`);
    await score.check();
    const marker = `QA calificacion CIC ${Date.now()}`;
    await modal.locator(`#${prefix}_Observaciones`).fill(marker);
    const save = modal.locator(`#btn_guardar_cic_${prefix}`);
    const response = page.waitForResponse((item) => item.url().includes('/api/cic/save'));
    await save.click();
    const savedResponse = await response;
    expect(savedResponse.ok(), 'CIC save request').toBe(true);
    expect((await savedResponse.json()).respuesta).toBe('BIEN');
    let persisted = (await listRows(page, section.endpoint, 6))
      .find((row) => String(row.Id) === selectedId);
    expect(persisted.Observaciones).toBe(marker);
    expect(String(persisted[`${prefix}_cal_1`])).toBe(changedScore);
    await reloadSection(page, section);
    persisted = (await listRows(page, section.endpoint, 6))
      .find((row) => String(row.Id) === selectedId);
    expect(persisted.Observaciones).toBe(marker);
    expect(errors, 'CIC qualification runtime errors').toEqual([]);
  } finally {
    if (original) {
      restoreCicRow(PRUEBA, original);
      const restored = (await listRows(page, section.endpoint, 6))
        .find((row) => String(row.Id) === String(original.Id));
      expect(restored, 'CIC provider restored').toBeTruthy();
      expect(restored, 'CIC provider state after finally').toEqual(original);
    }
  }
});

test('CIC mobile conserva la acción para roles G, S y SG', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CIC');
  await openSection(page, section, VIEWPORTS[0], 6, PRUEBA, CREDENTIALS);
  for (const role of ['G', 'S', 'SG']) {
    await page.evaluate((value) => {
      document.querySelector('#permiso_canonico').value = value;
      window.jQuery('#dt_cliente').DataTable().draw(false);
    }, role);
    await expect(page.locator('#ps-legacy-card-view [data-legacy-action="edit"]'))
      .not.toHaveCount(0);
  }
});

test('CIC respeta la semana seleccionada aunque exista una posterior', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CIC');
  await openSection(page, section, VIEWPORTS[0], 6, PRUEBA, CREDENTIALS);
  await expect(page.locator('#semana_PHP')).toHaveValue('6');
  const renderedWeeks = await page.evaluate(() => window.jQuery('#dt_cliente').DataTable()
    .rows().data().toArray().map((row) => Number(row.Semana)));
  expect(renderedWeeks).not.toHaveLength(0);
  expect(new Set(renderedWeeks)).toEqual(new Set([6]));
});

test('CNP tablet mantiene todas las superficies dark, sin claras residuales', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, { width: 787, height: 750 }, 7, PRUEBA, CREDENTIALS);
  await expectTheme(page, 'dark');
  await expect.poll(() => page.evaluate(() => {
    const lightness = (value) => value.match(/[\d.]+/g).slice(0, 3)
      .map(Number).reduce((sum, channel) => sum + channel, 0) / 3;
    const selectors = ['.grupo_botones1', '.leyenda_colores', '#dropdownTriggerSecciones',
      '#ps_legacy_search', '.dataTables_scrollHead', 'table.dataTable thead th',
      'table.dataTable tbody td'];
    return { surfaces: selectors.every((selector) => lightness(
      getComputedStyle(document.querySelector(selector)).backgroundColor,
    ) < 140), info: lightness(getComputedStyle(
      document.querySelector('#dt_cliente_info'),
    ).color) > 180 };
  }), { timeout: 2000 }).toEqual({ surfaces: true, info: true });
});

test('CNP tablet contiene las acciones dentro de su columna', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, { width: 787, height: 750 }, 7, PRUEBA, CREDENTIALS);
  await expectTheme(page, 'dark');
  const geometry = await page.evaluate(() => [...document.querySelectorAll(
    '#dt_cliente tbody td:first-child button.editar, #dt_cliente tbody td:first-child button.reprogramar',
  )].map((button) => {
    const cell = button.closest('td').getBoundingClientRect();
    const rect = button.getBoundingClientRect();
    const icon = button.querySelector('i').getBoundingClientRect();
    return { height: rect.height, width: rect.width,
      iconHeight: icon.height, iconWidth: icon.width,
      centerDeltaX: Math.abs((rect.left + rect.width / 2) - (icon.left + icon.width / 2)),
      centerDeltaY: Math.abs((rect.top + rect.height / 2) - (icon.top + icon.height / 2)),
      contained: rect.left >= cell.left && rect.right <= cell.right };
  }));
  expect(geometry.length).toBeGreaterThan(0);
  expect(geometry.every(({ width, height }) => width >= 44 && height >= 44)).toBe(true);
  expect(geometry.every(({ iconWidth, iconHeight }) => iconWidth >= 32 && iconWidth <= 36
    && iconHeight >= 32 && iconHeight <= 36)).toBe(true);
  expect(geometry.every(({ centerDeltaX, centerDeltaY }) => centerDeltaX <= 1
    && centerDeltaY <= 1)).toBe(true);
  expect(geometry.every(({ contained }) => contained)).toBe(true);
  const columns = await page.evaluate(() => {
    const headers = [...document.querySelectorAll('.dataTables_scrollHead thead th')]
      .filter((cell) => cell.getBoundingClientRect().width > 0);
    const cells = [...document.querySelectorAll('.dataTables_scrollBody tbody tr:first-child td')]
      .filter((cell) => cell.getBoundingClientRect().width > 0);
    return headers.map((header, index) => {
      const head = header.getBoundingClientRect();
      const body = cells[index].getBoundingClientRect();
      return { left: Math.abs(head.left - body.left), width: Math.abs(head.width - body.width) };
    });
  });
  expect(columns.length).toBeGreaterThan(0);
  expect(columns.every(({ left, width }) => left <= 1 && width <= 1)).toBe(true);
  const rowStates = await page.evaluate(() => {
    const table = window.jQuery('#dt_cliente').DataTable();
    const data = table.rows().data().toArray();
    return table.rows().nodes().toArray().map((row, index) => {
      const overdue = Number(data[index].Atrasada) === 1;
      const critical = Number(data[index].Critica) === 1;
      const expected = overdue
        ? (critical ? 'row-cnp-overdue-critical' : 'row-cnp-overdue-non-critical')
        : (critical ? 'row-cnp-critical' : 'row-cnp-non-critical');
      return { expected, className: row.className };
    });
  });
  expect(rowStates.length).toBeGreaterThan(0);
  expect(rowStates.every(({ expected, className }) => className.includes(expected))).toBe(true);
  const stateColors = await page.evaluate(() => {
    const row = document.querySelector('.dataTables_scrollBody tbody tr');
    const original = row.className;
    const states = ['row-cnp-critical', 'row-cnp-non-critical',
      'row-cnp-overdue-critical', 'row-cnp-overdue-non-critical'];
    const colors = states.map((state) => {
      row.className = state;
      return getComputedStyle(row.querySelector('td')).backgroundColor;
    });
    row.className = original;
    return colors;
  });
  expect(new Set(stateColors).size).toBe(4);
  expect(await page.evaluate(() => document.documentElement.scrollWidth
    - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
});

test('CNP desktop alinea buscador y deja margen antes de la tabla', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, { width: 1440, height: 900 }, 7, PRUEBA, CREDENTIALS);
  const layout = await page.evaluate(() => {
    const input = document.querySelector('#ps_legacy_search').getBoundingClientRect();
    const clear = document.querySelector('#btn_limpiar_buscador').getBoundingClientRect();
    const table = document.querySelector('.dataTables_scrollHead').getBoundingClientRect();
    return { topDelta: Math.abs(input.top - clear.top),
      gap: table.top - Math.max(input.bottom, clear.bottom),
      clipped: clear.right > innerWidth || input.left < 0,
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth };
  });
  expect(layout.topDelta).toBeLessThanOrEqual(1);
  expect(layout.gap).toBeGreaterThanOrEqual(8);
  expect(layout.clipped).toBe(false);
  expect(layout.overflow).toBeLessThanOrEqual(1);
});

test('CNP recupera la leyenda histórica de cuatro estados', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CNP');
  await openSection(page, section, { width: 1440, height: 900 }, 7, PRUEBA, CREDENTIALS);
  await page.locator('.leyenda_colores').click();
  const modal = page.locator('#modal_leyenda_colores');
  await expect(modal).toBeVisible();
  await expect(modal.locator('.modal-title')).toHaveText(
    'Guía Operativa - Causas de No Programación',
  );
  await expect(modal.locator('.modal-body img')).toHaveCount(0);
  await expect(modal.locator('.ps-legend-quick')).toBeVisible();
  await expect(modal.locator('.ps-legend-quick-row')).toHaveCount(4);
  const items = modal.locator('.ps-cnp-legend-item');
  await expect(items).toHaveCount(4);
  await expect(items).toHaveText([
    /Crítica por programar/, /No crítica por programar/,
    /Atrasada crítica por programar/, /Atrasada no crítica por programar/,
  ]);
});

test('CIC converge y una recarga no vuelve a mutar su tabla', async ({ page }) => {
  const section = SECTIONS.find(({ key }) => key === 'CIC');
  await openSection(page, section, VIEWPORTS[0], 6, PRUEBA, CREDENTIALS);
  const checksum = tableChecksum('cic');
  await reloadSection(page, section);
  expect(tableChecksum('cic')).toBe(checksum);
});
