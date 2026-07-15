import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { PROJECTS } from './fixtures/projects.mjs';
import {
  changeWeek,
  login,
  loginAndSelectProject,
  postFormJson,
  selectProject,
} from './support/session.mjs';
import { ProjectDbSnapshot, runSql } from './support/dbSnapshot.mjs';
import {
  HANDSONTABLE_GOAL_THEMES,
  HANDSONTABLE_GOAL_VIEWPORTS,
  measureHandsontableGoalMatrix,
  setHandsontableGoalTheme,
} from './support/handsontableGoalMatrix.mjs';

const PROJECT = {
  key: 'listado-real',
  name: 'Optimización Aeropuerto JMC',
  projectId: 68,
  dbPrefix: 'optimizacionJMC',
  area: 'Construccion',
  maxWeek: 5,
};
const READONLY_PROJECT = PROJECTS.find((project) => project.key === 'construction');
const READONLY_USERNAME = `test.V.listado.${process.pid}`;
const MUTATED_TABLES = ['actividades', 'contratos_trazabilidad'];
const AUTOMATION_TABLES = [
  'actividades',
  'actividad_programa_fuentes',
  'programa',
  'programa_consolidado',
];
const AUTO_SOURCE_ID = 9995001;
const FILTER_TEST_ROWS = [
  {
    Id: 91001, codigo: 'LA-F01', actividad: 'Familia filtro Alfa', descripcionActividad: 'Grupo uno',
    actividadInicio: '101', nombreActividadInicio: 'Actividad inicial 101', fechaInicio: '2026-07-01', tipoContrato: 'MO',
  },
  {
    Id: 91002, codigo: 'LA-F02', actividad: 'Familia filtro Beta', descripcionActividad: 'Grupo dos',
    actividadInicio: '102', nombreActividadInicio: 'Actividad inicial 102', fechaInicio: '2026-07-02', tipoContrato: 'S',
  },
  {
    Id: 91003, codigo: 'LA-F03', actividad: 'Familia filtro Gamma', descripcionActividad: 'Grupo tres',
    actividadInicio: '103', nombreActividadInicio: 'Actividad inicial 103', fechaInicio: '2026-07-03', tipoContrato: 'SI',
  },
];
const PARITY_FIELDS = [
  'Id', 'codigo', 'actividad', 'descripcionActividad', 'actividadInicio',
  'nombreActividadInicio', 'fechaInicio', 'tipoContrato',
];

function comparableRows(rows) {
  return rows.map((row) => Object.fromEntries(
    PARITY_FIELDS.map((field) => [field, String(row[field] ?? '')]),
  ));
}

function compactExpectedActivity(value) {
  return String(value || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s*\[Capítulo:[^\]]*\]\s*/i, ' ')
    .replace(/\s*\(Inicia\s+(?:el|en):\s*\d{4}-\d{2}-\d{2}\)\s*/i, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

async function openListado(page, viewport = { width: 1440, height: 900 }) {
  await page.setViewportSize(viewport);
  await loginAndSelectProject(page, PROJECT);
  await changeWeek(page, PROJECT.maxWeek, `/listado-actividades?semana=${PROJECT.maxWeek}`);
  await page.waitForFunction(() => {
    const module = window.ListadoActividadesHotModule;
    const hot = module?.getHotInstance?.();
    return Boolean(hot && hot.countSourceRows() >= 0);
  }, null, { timeout: 30_000 });
  await page.waitForTimeout(250);
}

async function openListadoAs(page, credentials, viewport = { width: 1440, height: 900 }) {
  await page.setViewportSize(viewport);
  await login(page, credentials);
  await selectProject(page, READONLY_PROJECT);
  await changeWeek(page, READONLY_PROJECT.maxWeek, `/listado-actividades?semana=${READONLY_PROJECT.maxWeek}`);
  await page.waitForFunction(() => (
    window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() >= 0
  ));
}

function ensureReadOnlyFixture() {
  runSql(`INSERT INTO general_usuarios (nombre,email,cargo,usuario,password,force_password_change,activo)
    SELECT 'Test Visualizador Listado','${READONLY_USERNAME}@aia.local','Visualizador','${READONLY_USERNAME}',password,0,1
    FROM general_usuarios WHERE usuario='test.A' LIMIT 1;
    INSERT INTO project_members (project_id,user_id,role)
    SELECT ${READONLY_PROJECT.projectId},id,'V' FROM general_usuarios WHERE usuario='${READONLY_USERNAME}';`);
  return () => runSql(`DELETE pm FROM project_members pm JOIN general_usuarios u ON u.id=pm.user_id WHERE u.usuario='${READONLY_USERNAME}';
    DELETE FROM general_usuarios WHERE usuario='${READONLY_USERNAME}';`);
}

async function openColumnFilter(page, header) {
  const column = page.locator('#hot-container .ht_clone_top:visible thead th')
    .filter({ hasText: header }).first();
  const button = column.locator('.changeType:visible');
  await expect(button, `Filtro visible de la columna ${header}`).toBeVisible();
  await button.click();
  const menu = page.locator('.htDropdownMenu:visible');
  await expect(menu).toBeVisible();
  return { button, menu };
}

async function filterColumnByValue(page, header, value) {
  const { menu } = await openColumnFilter(page, header);
  await menu.locator('.htUIClearAll a').click();
  const option = menu.locator('.htFiltersMenuValue .htCore tbody tr')
    .filter({ hasText: String(value) }).first();
  await expect(option, `Valor ${value} en filtro ${header}`).toBeVisible();
  await option.click();
  await menu.locator('.htUIButtonOK input').click();
  await expect(menu).toBeHidden();
}

async function clearColumnFilter(page, header) {
  const { button, menu } = await openColumnFilter(page, header);
  await menu.locator('.htUISelectAll a').click();
  await menu.locator('.htUIButtonOK input').click();
  await expect(menu).toBeHidden();
  await expect(button).not.toHaveClass(/htFiltersActive/);
}

async function useListadoRows(page, rows = FILTER_TEST_ROWS) {
  await page.route('**/api/listado-actividades/list**', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ data: rows }),
  }));
}


test('CSS de Listado usa tokens y no habilita scroll horizontal', () => {
  const css = readFileSync(new URL('../../public/css/listado-actividades.css', import.meta.url), 'utf8');
  expect(css).not.toMatch(/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(/i);
  expect(css).not.toMatch(/overflow-x\s*:\s*(auto|scroll)/i);
  expect(css).not.toMatch(/(?:padding|margin|gap|border-radius|font-size|height|width)\s*:\s*[0-9.]+px/i);
});

test.describe('Listado de Actividades migrado a Handsontable', () => {
  test('carga exactamente los datos API sin runtime DataTables', async ({ page }) => {
    await openListado(page, { width: 390, height: 844 });
    await expect(page.locator('#permiso_canonico')).toHaveValue('R');
    const api = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    expect(api.ok).toBe(true);
    expect(Array.isArray(api.payload.data)).toBe(true);
    await page.waitForFunction(() => (
      document.querySelector('link[href*="/css/styles.css"]')?.dataset.aiaDataTablesPurged === 'true'
    ));

    const state = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      const dataTableCssRules = [];
      const collectRules = (rules, href) => Array.from(rules || []).forEach((rule) => {
        if (rule.selectorText && /(?:table\.dataTable|\.dataTables_|\.dt-(?:button|container|layout|paging|search|info|length|scroll|nowrap|min-width|text))/i.test(rule.selectorText)) {
          dataTableCssRules.push(`${href || 'inline'}: ${rule.selectorText}`);
        }
        if (rule.cssRules) collectRules(rule.cssRules, href);
      });
      Array.from(document.styleSheets).forEach((sheet) => {
        try { collectRules(sheet.cssRules, sheet.href); } catch (_) { /* cross-origin */ }
      });
      return {
        hotRows: hot.countSourceRows(),
        source: hot.getSourceData(),
        cards: document.querySelectorAll('#la-mobile-card-list .la-mobile-card').length,
        cardRows: [...document.querySelectorAll('#la-mobile-card-list .la-mobile-card')].map((card) => {
          const fields = Object.fromEntries([...card.querySelectorAll('.la-mobile-card__field')].map((field) => [
            field.querySelector('.la-mobile-card__label')?.textContent.trim(),
            field.querySelector('.la-mobile-card__value')?.textContent.trim(),
          ]));
          return {
            Id: card.dataset.rowId, codigo: card.querySelector('.la-mobile-card__code')?.textContent.replace(/^Cod\.\s*/, '').trim() || '',
            actividad: card.querySelector('.la-mobile-card__title')?.textContent.trim() || '',
            descripcionActividad: fields['Descripción'] || '', nombreActividadInicio: fields['Inicio en obra'] || '',
            fechaInicio: fields['Fecha de inicio'] || '',
            tipoContrato: [...card.querySelectorAll('.la-contract-badges .badge')].map((badge) => badge.textContent.trim().replace('S+I', 'SI')).join(','),
            actions: card.querySelectorAll('.la-mobile-card__actions button').length,
          };
        }),
        rawMarkupVisible: /<\/?(?:b|small|br)(?:\s|>)/i.test(document.querySelector('#la-mobile-card-list').innerText),
        hotMasters: document.querySelectorAll('#hot-container .ht_master.handsontable').length,
        dataTableWrappers: document.querySelectorAll('.dataTables_wrapper').length,
        dataTableScripts: [...document.scripts].filter((script) => /datatables|gyrocode/i.test(script.src)).length,
        dataTableStyles: [...document.querySelectorAll('link[rel="stylesheet"]')]
          .filter((link) => /datatables|gyrocode/i.test(link.href)).length,
        dataTablePlugin: Boolean(window.jQuery?.fn?.dataTable || window.jQuery?.fn?.DataTable),
        dataTableDom: document.querySelectorAll('table.dataTable, #dt_cliente').length,
        dataTableCssRules,
        sharedStylesheets: [...document.querySelectorAll('link[href*="/css/styles.css"]')]
          .map((link) => ({ href: link.href, media: link.media, purged: link.dataset.aiaDataTablesPurged || '' })),
        loaderScripts: [...document.scripts].map((script) => script.src).filter((src) => /linksComunesHead2/.test(src)),
        delegatedHandlers: Object.values(window.jQuery?._data(document, 'events') || {})
          .flatMap((handlers) => Array.from(handlers))
          .filter((handler) => /dataTables|dt_cliente/i.test(handler.selector || '')).length,
      };
    });
    expect(state.hotRows).toBe(api.payload.data.length);
    expect(state.cards).toBe(api.payload.data.length);
    expect(comparableRows(state.source)).toEqual(comparableRows(api.payload.data));
    expect(state.cardRows).toEqual(api.payload.data.map((row) => ({
      Id: String(row.Id || ''), codigo: String(row.codigo || ''), actividad: String(row.actividad || ''),
      descripcionActividad: String(row.descripcionActividad || ''),
      nombreActividadInicio: compactExpectedActivity(row.nombreActividadInicio),
      fechaInicio: String(row.fechaInicio || ''), tipoContrato: String(row.tipoContrato || ''),
      actions: 2,
    })));
    expect(state.rawMarkupVisible).toBe(false);
    expect(state.hotMasters).toBe(1);
    expect({
      wrappers: state.dataTableWrappers, scripts: state.dataTableScripts,
      styles: state.dataTableStyles, plugin: state.dataTablePlugin,
      dom: state.dataTableDom, handlers: state.delegatedHandlers, cssRules: state.dataTableCssRules,
    }, JSON.stringify({ shared: state.sharedStylesheets, loaders: state.loaderScripts })).toEqual(
      { wrappers: 0, scripts: 0, styles: 0, plugin: false, dom: 0, handlers: 0, cssRules: [] },
    );
  });

  test('convierte contenido hostil a texto seguro en HOT y tarjetas', async ({ page }) => {
    const hostile = [{ ...FILTER_TEST_ROWS[0], actividadInicio: '',
      actividad: '<b>Familia</b><br><small>segura</small><img src=x onerror="window.__laXss=1">',
      descripcionActividad: '<b>Descripción</b><br><small>final</small><img src=x onerror="window.__laXss=1">',
      nombreActividadInicio: '<b>1.1.</b><br><small>Actividad válida</small><img src=x onerror="window.__laXss=1">' }];
    await useListadoRows(page, hostile);
    await openListado(page, { width: 390, height: 844 });
    const state = await page.evaluate(() => ({
      source: window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0),
      unsafeNodes: document.querySelectorAll('#hot-container img, #hot-container script, #la-mobile-card-list img, #la-mobile-card-list script').length,
      unsafeAttributes: document.querySelectorAll('#hot-container [onerror], #la-mobile-card-list [onerror]').length,
      visibleRawTags: /<(?:b|small|br|img)\b/i.test(document.querySelector('#la-mobile-card-list').innerText),
      executed: Boolean(window.__laXss),
    }));
    expect(state.source.actividad).toBe('Familia segura');
    expect(state.source.descripcionActividad).toBe('Descripción final');
    expect(state.source.nombreActividadInicio).toBe('1.1. Actividad válida');
    expect(state).toMatchObject({ unsafeNodes: 0, unsafeAttributes: 0, visibleRawTags: false, executed: false });
  });

  test('la carga normal termina sin errores de consola ni peticiones fallidas', async ({ page }) => {
    await openListado(page);
    const failures = [];
    page.on('console', (message) => { if (message.type() === 'error') failures.push(`console:${message.text()}`); });
    page.on('pageerror', (error) => failures.push(`pageerror:${error.message}`));
    page.on('requestfailed', (request) => failures.push(`requestfailed:${request.url()}`));
    page.on('response', (response) => {
      if (response.status() >= 400) failures.push(`http:${response.status()}:${response.url()}`);
    });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0);
    await expect(page.locator('#la-table-state')).toHaveAttribute('data-state', 'ready');
    expect(failures).toEqual([]);
  });

  test('los filtros se combinan y limpian desde la interfaz sin condiciones residuales', async ({ page }) => {
    await useListadoRows(page);
    await openListado(page);
    const target = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      const row = hot.getSourceData().find((item) => item.codigo && item.actividad);
      return { before: hot.countRows(), codigo: String(row.codigo), actividad: String(row.actividad) };
    });
    expect(target.before).toBeGreaterThan(1);

    await filterColumnByValue(page, 'Código', target.codigo);
    await filterColumnByValue(page, 'Familia', target.actividad);

    const filtered = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      return Array.from({ length: hot.countRows() }, (_, visualRow) => ({
        codigo: String(hot.getDataAtRowProp(visualRow, 'codigo')),
        actividad: String(hot.getDataAtRowProp(visualRow, 'actividad')),
      }));
    });
    expect(filtered.length).toBeGreaterThan(0);
    expect(filtered.length).toBeLessThan(target.before);
    expect(filtered.every((row) => row.codigo === target.codigo && row.actividad === target.actividad)).toBe(true);

    await clearColumnFilter(page, 'Familia');
    await clearColumnFilter(page, 'Código');
    const restored = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      const filters = hot.getPlugin('filters');
      return {
        rows: hot.countRows(),
        conditions: filters.conditionCollection.exportAllConditions(),
        activeButtons: document.querySelectorAll('#hot-container .changeType.htFiltersActive').length,
      };
    });
    expect(restored).toEqual({ rows: target.before, conditions: [], activeButtons: 0 });
  });

  test('la acción renderizada tras filtrar conserva el Id físico de la fila visible', async ({ page }) => {
    await useListadoRows(page);
    await openListado(page);
    const target = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      const rows = hot.getSourceData();
      const firstCode = String(rows[0]?.codigo || '');
      const sourceIndex = rows.findIndex((row, index) => index > 0 && row.Id && row.codigo && String(row.codigo) !== firstCode);
      const row = rows[sourceIndex];
      return row ? { id: String(row.Id), codigo: String(row.codigo), actividad: String(row.actividad) } : null;
    });
    expect(target, 'Se requiere una fila distinta de la primera para probar el mapeo físico').not.toBeNull();

    await filterColumnByValue(page, 'Código', target.codigo);
    const visible = await page.evaluate(() => {
      const hot = window.ListadoActividadesHotModule.getHotInstance();
      return {
        id: String(hot.getDataAtRowProp(0, 'Id')),
        physicalRow: hot.toPhysicalRow(0),
      };
    });
    expect(visible.id).toBe(target.id);
    expect(visible.physicalRow).toBeGreaterThan(0);

    await page.locator('#hot-container .ht_master tbody tr:visible').first()
      .locator('button.eliminar:visible').click();
    await expect(page.locator('#modalEliminar')).toBeVisible();
    await expect(page.locator('#encabezado #Id')).toHaveValue(target.id);
    await expect(page.locator('#modal-body-texto-eliminar')).toContainText(target.actividad);
  });

  test('toolbar y modales principales permanecen funcionales', async ({ page }) => {
    await openListado(page, { width: 390, height: 844 });
    await expect(page.locator('#btn_cargarActividadesExcel')).toBeVisible();
    await expect(page.locator('#btn_nueva_actividad')).toBeVisible();
    await expect(page.locator('#btn_auto_generar_listado')).toBeVisible();
    await expect(page.locator('[data-aia-info-nav-trigger]')).toBeVisible();
    const toolbarMetrics = await page.locator('.toolbarFilaBotones').evaluate((toolbar) => ({
      overflow: [...toolbar.querySelectorAll('button')].filter((button) => button.scrollWidth > button.clientWidth + 1).length,
      labels: [...toolbar.querySelectorAll('button')].map((button) => button.innerText.trim()),
      icons: [...toolbar.querySelectorAll('button')].filter((button) => button.querySelector('i')).length,
    }));
    expect(toolbarMetrics.overflow).toBe(0);
    expect(toolbarMetrics.icons).toBeGreaterThanOrEqual(4);

    await page.locator('[data-aia-info-nav-trigger]').click();
    await expect(page.locator('[data-aia-info-nav-menu]')).toBeVisible();
    await expect(page.locator('[data-aia-info-nav-menu] .aia-info-nav__item')).toHaveCount(3);
    await expect(page.locator('[data-aia-info-nav-menu] .aia-info-nav__item.is-active')).toContainText('Familias de obra');
    await page.locator('[data-aia-info-nav-trigger]').click();

    await page.locator('#btn_cargarActividadesExcel').click();
    await expect(page.locator('#modalCargarExcel')).toBeVisible();
    await expect(page.locator('#formCargarExcel input[type="file"]')).toBeVisible();
    await page.locator('#modalCargarExcel button[aria-label="Close"]').click();
    await expect(page.locator('#modalCargarExcel')).not.toBeVisible();

    await page.locator('#btn_nueva_actividad').click();
    await expect(page.locator('#modalNuevaActividad')).toBeVisible();
    await expect(page.locator('#modalNuevaActividad #actividad')).toBeVisible();
    await expect(page.locator('#modalNuevaActividad #actividadInicio')).toBeAttached();
    await expect(page.locator('#modalNuevaActividad input[name="tipoContratoCheck"]')).toHaveCount(4);
    expect(await page.locator('#modalNuevaActividad input[name="tipoContratoCheck"]').evaluateAll((inputs) => (
      inputs.map((input) => ({ value: input.value, label: input.getAttribute('aria-label') }))
    ))).toEqual([
      { value: 'S', label: 'Suministro' }, { value: 'MO', label: 'Mano de Obra' },
      { value: 'SI', label: 'Suministro e Instalación' }, { value: 'OC', label: 'Orden de servicio/compra' },
    ]);
    await page.locator('#modalNuevaActividad input[value="MO"]').check();
    await page.locator('#modalNuevaActividad input[value="SI"]').check();
    expect(await page.locator('#modalNuevaActividad input[name="tipoContratoCheck"]').evaluateAll((inputs) => (
      Object.fromEntries(inputs.map((input) => [input.value, input.checked]))
    ))).toEqual({ S: false, MO: false, SI: true, OC: false });
    await page.locator('#modalNuevaActividad #btn_listar').click();
    await expect(page.locator('#modalNuevaActividad')).not.toBeVisible();
  });

  test('muestra loading hasta recibir datos y luego pasa a ready', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await loginAndSelectProject(page, PROJECT);
    let releaseResponse;
    const responseGate = new Promise((resolve) => { releaseResponse = resolve; });
    await page.route('**/api/listado-actividades/list**', async (route) => {
      await responseGate;
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: FILTER_TEST_ROWS }) });
    });
    await changeWeek(page, PROJECT.maxWeek, `/listado-actividades?semana=${PROJECT.maxWeek}`);
    await expect(page.locator('#la-table-state')).toHaveAttribute('data-state', 'loading');
    await expect(page.locator('#la-table-state')).toContainText('Cargando');
    releaseResponse();
    await page.waitForFunction(
      (length) => window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() === length,
      FILTER_TEST_ROWS.length,
    );
    await expect(page.locator('#la-table-state')).toHaveAttribute('data-state', 'ready');
    expect(await page.evaluate(() => window.ListadoActividadesHotModule.getHotInstance().countSourceRows())).toBe(FILTER_TEST_ROWS.length);
  });

  test('muestra un estado vacío sin fabricar filas', async ({ page }) => {
    await page.route('**/api/listado-actividades/list**', (route) => route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [] }),
    }));
    await openListado(page);
    await expect(page.locator('#la-table-state')).toHaveAttribute('data-state', 'empty');
    await expect(page.locator('#la-table-state')).toContainText('No hay familias');
    expect(await page.evaluate(() => window.ListadoActividadesHotModule.getHotInstance().countSourceRows())).toBe(0);
  });

  test('distingue un error de carga del estado vacío', async ({ page }) => {
    await page.route('**/api/listado-actividades/list**', (route) => route.fulfill({
      status: 503,
      contentType: 'application/json',
      body: JSON.stringify({ respuesta: 'ERROR' }),
    }));
    await openListado(page);
    await expect(page.locator('#la-table-state')).toHaveAttribute('data-state', 'error');
    await expect(page.locator('#la-table-state')).toContainText('No fue posible cargar');
    const state = await page.evaluate(() => ({
      rows: window.ListadoActividadesHotModule.getHotInstance().countSourceRows(),
      cards: document.querySelectorAll('#la-mobile-card-list .la-mobile-card').length,
    }));
    expect(state).toEqual({ rows: 0, cards: 0 });
  });

  test('desktop edita inline y revierte visualmente si el servidor rechaza', async ({ page }) => {
    await openListado(page, { width: 1440, height: 900 });
    await expect(page.locator('#hot-container button[title*="Editar"]')).toHaveCount(0);
    const before = await page.evaluate(() => (
      window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0).codigo
    ));
    await page.route('**/api/listado-actividades/update-cell**', (route) => route.fulfill({
      status: 422,
      contentType: 'application/json',
      body: '{"respuesta":"ERROR","mensaje":"Cambio rechazado por prueba"}',
    }));
    const codeCell = page.locator('#hot-container .ht_master tbody tr').first().locator('td').nth(1);
    await codeCell.dblclick();
    const editor = page.locator('#hot-container .handsontableInputHolder:visible textarea');
    await editor.fill(`${before}-rechazado`);
    await editor.press('Enter');
    await expect(page.locator('#mensajeActualizacion')).toContainText('Cambio rechazado por prueba');
    const after = await page.evaluate(() => (
      window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0).codigo
    ));
    expect(after).toBe(before);
  });

  test('mobile permite cambiar inicio cuando la modalidad ya estaba pendiente', async ({ page }) => {
    const writes = [];
    await page.route('**/api/listado-actividades/update-card**', async (route) => {
      writes.push(Object.fromEntries(new URLSearchParams(route.request().postData() || '')));
      await route.fulfill({ status: 200, contentType: 'application/json', body: '{"respuesta":"BIEN"}' });
    });
    await openListado(page, { width: 390, height: 844 });
    await page.evaluate(() => {
      window.ListadoActividadesHotModule.getHotInstance().setSourceDataAtCell(0, 'tipoContrato', 'S', 'internal-update');
      window.dispatchEvent(new Event('resize'));
    });
    const card = page.locator('#la-mobile-card-list .la-mobile-card').first();
    await card.locator('button[title="Editar familia"]').click();
    const select = card.locator('.la-mobile-card__select');
    const next = await select.locator('option').evaluateAll((options, current) => options.find((option) => option.value && option.value !== current)?.value, await select.inputValue());
    await select.selectOption(next);
    await card.locator('button[title="Guardar cambios"]').click();
    await expect.poll(() => writes).toEqual([
      expect.objectContaining({ actividadInicio: String(next), tipoContrato: 'S' }),
    ]);
  });

  test('mobile nunca deja dos tarjetas en edición', async ({ page }) => {
    await openListado(page, { width: 390, height: 844 });
    const cards = page.locator('#la-mobile-card-list .la-mobile-card');
    expect(await cards.count()).toBeGreaterThan(1);
    await cards.nth(0).locator('button[title="Editar familia"]').click();
    await expect(page.locator('#la-mobile-card-list .la-mobile-card.is-editing')).toHaveCount(1);
    await cards.nth(1).locator('button[title="Editar familia"]').click();
    await expect(page.locator('#la-mobile-card-list .la-mobile-card.is-editing')).toHaveCount(1);
    await expect(cards.nth(0)).not.toHaveClass(/is-editing/);
    await expect(cards.nth(1)).toHaveClass(/is-editing/);
  });

  test('mobile oculta controles hasta Editar y mantiene SI exclusivo', async ({ page }) => {
    await openListado(page, { width: 390, height: 844 });
    const card = page.locator('#la-mobile-card-list .la-mobile-card').first();
    await expect(card.locator('select, input[type="checkbox"]')).toHaveCount(0);
    await card.locator('button[title="Editar familia"]').click();
    await expect(card.locator('.la-mobile-card__select')).toBeEnabled();
    expect(await card.locator('.la-mobile-card__select option').evaluateAll((options) => (
      options.filter((option) => option.value).every((option) => option.textContent.trim() && !/<[^>]+>/.test(option.textContent))
    ))).toBe(true);
    expect(await card.locator('.la-mobile-tipo-toggle input').evaluateAll((inputs) => (
      inputs.map((input) => [input.value, input.getAttribute('aria-label')])
    ))).toEqual([
      ['MO', 'Mano de Obra'], ['S', 'Suministro'], ['SI', 'Suministro e Instalación'], ['OC', 'Orden de servicio/compra'],
    ]);
    await card.locator('input[value="MO"]').check();
    await card.locator('input[value="SI"]').check();
    expect(await card.locator('.la-mobile-tipo-toggle input').evaluateAll((inputs) => (
      Object.fromEntries(inputs.map((input) => [input.value, input.checked]))
    ))).toEqual({ MO: false, S: false, SI: true, OC: false });
    expect(await card.locator('.la-mobile-tipo-toggle input').evaluateAll((inputs) => (
      Object.fromEntries(inputs.map((input) => [input.value, input.disabled]))
    ))).toEqual({ MO: true, S: true, SI: false, OC: true });
    await card.locator('input[value="SI"]').uncheck();
    expect(await card.locator('.la-mobile-tipo-toggle input').evaluateAll((inputs) => (
      Object.fromEntries(inputs.map((input) => [input.value, input.disabled]))
    ))).toEqual({ MO: false, S: false, SI: false, OC: false });
  });
});

test('Listado respeta una sesión readOnly real sin alterar permisos en el navegador', async ({ page }) => {
  const cleanupViewer = ensureReadOnlyFixture();
  try {
    await openListadoAs(page, { username: READONLY_USERNAME, password: 'aia2026' });
  await expect(page.locator('#permiso_canonico')).toHaveValue('V');
  await expect(page.locator('#btn_cargarActividadesExcel')).toHaveCount(0);
  await expect(page.locator('#btn_nueva_actividad')).toHaveCount(0);
  await expect(page.locator('#btn_auto_generar_listado')).toHaveCount(0);
  await expect(page.locator('#hot-container button.eliminar')).toHaveCount(0);
  const readOnly = await page.evaluate(() => {
    const hot = window.ListadoActividadesHotModule.getHotInstance();
    return ['codigo', 'descripcionActividad', 'nombreActividadInicio', 'fechaInicio', 'tipoContrato']
      .map((prop) => hot.getCellMeta(0, hot.propToCol(prop)).readOnly);
  });
  expect(readOnly).toEqual([true, true, true, true, true]);
  const denied = await page.request.post('/api/listado-actividades/update-cell', {
    form: { id: '0', prop: 'codigo', value: 'DENEGADO' },
  });
    expect(denied.status()).toBe(403);
  } finally {
    cleanupViewer();
  }
});

test('Listado cumple la matriz responsive Dark/Linen', async ({ page }) => {
  await openListado(page, HANDSONTABLE_GOAL_VIEWPORTS[0]);
  for (const viewport of HANDSONTABLE_GOAL_VIEWPORTS) {
    await page.setViewportSize(viewport);
    await page.waitForTimeout(150);
    for (const theme of HANDSONTABLE_GOAL_THEMES) {
      await test.step(`${viewport.name} ${theme}`, async () => {
        await setHandsontableGoalTheme(page, theme);
        const state = await measureHandsontableGoalMatrix(page, {
          hot: '#hot-container .handsontable',
          cards: '#la-mobile-card-list .la-mobile-card',
          controls: '.toolbarFilaBotones button, .toolbarFiltro button, #hot-container button, #la-mobile-card-list button, #la-mobile-card-list select',
          headers: '#hot-container .ht_clone_top thead th',
          cells: '#hot-container .ht_master tbody tr:first-child td',
        });
        const shell = await page.evaluate(() => ({
          mixedTheme: document.documentElement.classList.contains('aia-theme-dark')
            && document.documentElement.classList.contains('aia-theme-linen'),
          toolbarRows: new Set([...document.querySelectorAll('.toolbarFilaBotones > div')]
            .filter((node) => getComputedStyle(node).display !== 'none')
            .map((node) => {
              const rect = node.getBoundingClientRect();
              return Math.round(rect.top + rect.height / 2);
            })).size,
        }));
        expect(state.theme).toBe(theme);
        expect(shell.mixedTheme).toBe(false);
        expect(state.darkBody).toBe(theme === 'dark');
        expect(state.pageOverflowX).toBe(0);
        expect(state.hot.overflowX).toBe(0);
        expect(state.overflowingControls).toEqual([]);
        expect(state.hot.masters).toBe(1);
        if (viewport.name !== 'mobile') {
          expect(shell.toolbarRows).toBe(1);
          expect(state.headerCellAlignment.available).toBe(true);
          expect(state.headerCellAlignment.aligned).toBe(true);
          const widths = state.headerCellAlignment.columns.slice(1).map((column) => column.headerWidth);
          expect(Math.max(...widths)).toBeLessThanOrEqual(viewport.width * 0.4);
          expect(Math.max(...widths) / Math.min(...widths)).toBeLessThan(5);
        }
        expect(viewport.name === 'mobile' ? state.cards.visible : state.hot.visible).toBeGreaterThan(0);
        expect(state.dataTables).toEqual({
          wrappers: 0, scripts: [], styles: [], plugin: false,
          legacyDom: 0, delegatedSelectors: [],
        });
      });
    }
  }
});

test.describe.serial('Mutaciones restaurables de Listado', () => {
  let snapshot;
  let baselineFingerprint;

  test.beforeEach(async ({ page }) => {
    snapshot = new ProjectDbSnapshot(PROJECT, MUTATED_TABLES).capture();
    baselineFingerprint = snapshot.fingerprint();
    await openListado(page, { width: 390, height: 844 });
  });

  test.afterEach(() => {
    if (!snapshot) return;
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(baselineFingerprint);
    snapshot.dispose();
    snapshot = null;
  });

  test('Cancelar edición móvil no escribe cambios', async ({ page }) => {
    const writes = [];
    page.on('request', (request) => {
      if (request.method() === 'POST' && request.url().includes('/api/listado-actividades/update-card')) {
        writes.push(request);
      }
    });
    const firstCard = page.locator('#la-mobile-card-list .la-mobile-card').first();
    const before = await page.evaluate(() => {
      const row = window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0);
      return { actividadInicio: String(row.actividadInicio || ''), tipoContrato: row.tipoContrato };
    });
    await firstCard.locator('button[title="Editar familia"]').click();
    const activitySelect = firstCard.locator('.la-mobile-card__select');
    const nextActivity = await activitySelect.locator('option').evaluateAll((options, current) => {
      return options.map((option) => option.value).find((value) => value && value !== current);
    }, before.actividadInicio);
    await activitySelect.selectOption(nextActivity);
    const targetType = String(before.tipoContrato).includes('SI') ? 'MO' : 'SI';
    await firstCard.locator(`input[value="${targetType}"]`).check();
    await firstCard.locator('button[title="Cancelar edición"]').click();
    await page.waitForTimeout(400);

    expect(writes).toHaveLength(0);
    const after = await page.evaluate(() => {
      const row = window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0);
      return { actividadInicio: String(row.actividadInicio || ''), tipoContrato: row.tipoContrato };
    });
    expect(after).toEqual(before);
  });

  test('desktop persiste ediciones inline autorizadas después de recargar', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0);
    const original = await page.evaluate(() => {
      const row = window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0);
      return { id: row.Id, codigo: row.codigo, descripcion: row.descripcionActividad };
    });
    const next = { codigo: String(Number(original.codigo) + 1000), descripcion: `${original.descripcion} E2E` };
    for (const edit of [{ cell: 1, value: next.codigo }, { cell: 3, value: next.descripcion }]) {
      const response = page.waitForResponse((item) => item.url().includes('/api/listado-actividades/update-cell'));
      const cell = page.locator('#hot-container .ht_master tbody tr').first().locator('td').nth(edit.cell);
      await cell.dblclick();
      const editor = page.locator('#hot-container .handsontableInputHolder:visible textarea');
      await editor.fill(edit.value);
      await editor.press('Enter');
      expect((await response).status()).toBe(200);
    }
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0);
    const persisted = await page.evaluate((id) => window.ListadoActividadesHotModule.getHotInstance().getSourceData().find((row) => row.Id === id), original.id);
    expect(persisted).toMatchObject({ codigo: Number(next.codigo), descripcionActividad: next.descripcion });
  });

  test('Guardar edición móvil persiste actividad, fecha y modalidad', async ({ page }) => {
    const writes = [];
    page.on('request', (request) => {
      if (request.method() === 'POST' && request.url().includes('/api/listado-actividades/update-card')) {
        writes.push(request);
      }
    });
    const original = await page.evaluate(() => {
      const row = window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0);
      return { id: row.Id, actividadInicio: String(row.actividadInicio || ''), tipoContrato: row.tipoContrato };
    });
    const firstCard = page.locator('#la-mobile-card-list .la-mobile-card').first();
    await firstCard.locator('button[title="Editar familia"]').click();
    const activitySelect = firstCard.locator('.la-mobile-card__select');
    const targetActivity = await activitySelect.locator('option').evaluateAll((options, current) => {
      return options.map((option) => option.value).find((value) => value && value !== current);
    }, original.actividadInicio);
    const targetDate = await page.locator(`#actividadInicio option[value="${targetActivity}"]`).evaluate((option) => {
      return option.textContent.match(/([0-9]{4}-[0-9]{2}-[0-9]{2})/)?.[1] || '';
    });
    const targetType = String(original.tipoContrato).includes('SI') ? 'MO' : 'SI';
    await activitySelect.selectOption(targetActivity);
    await firstCard.locator(`input[value="${targetType}"]`).check();
    await page.waitForTimeout(200);
    expect(writes).toHaveLength(0);

    await firstCard.locator('button[title="Guardar cambios"]').click();
    await expect.poll(() => writes.length).toBe(1);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() > 0);
    const persisted = await page.evaluate((id) => {
      return window.ListadoActividadesHotModule.getHotInstance().getSourceData().find((row) => row.Id === id);
    }, original.id);
    expect(String(persisted.actividadInicio)).toBe(targetActivity);
    expect(persisted.fechaInicio).toBe(targetDate);
    expect(persisted.tipoContrato).toBe(targetType);
  });

  test('Nueva Familia envía un solo POST y crea un solo registro', async ({ page }) => {
    const uniqueName = `E2E HOT ${Date.now()}`;
    const saveRequests = [];
    page.on('request', (request) => {
      const isRegister = request.postData()?.includes('registrar');
      if (isRegister && request.method() === 'POST' && request.url().includes('/api/listado-actividades/save')) {
        saveRequests.push(request);
      }
    });

    await page.locator('#btn_nueva_actividad').click();
    await expect(page.locator('#modalNuevaActividad')).toBeVisible();
    await page.waitForTimeout(500);
    await page.locator('#btn_guardar_actividad').click();
    await expect(page.locator('#modalNuevaActividad')).toBeVisible();
    expect(saveRequests).toHaveLength(0);
    await expect(page.locator('#modalNuevaActividad .mensaje')).toContainText('debe llenar todos los campos');
    await page.locator('#actividad').fill(uniqueName);
    await page.locator('#descripcionActividad').fill('Familia temporal para validar Handsontable');
    await page.locator('#actividadInicio').selectOption({ index: 1 });
    await expect(page.locator('#fechaInicio')).not.toHaveValue('');
    await page.locator('.aia-tipo-pill[data-tipo-code="MO"] input').check();
    const formState = await page.locator('#modalNuevaActividad form').evaluate((form) => {
      return Object.fromEntries(new FormData(form).entries());
    });
    expect(formState.actividad).toBe(uniqueName);
    expect(formState.descripcionActividad).not.toBe('');
    expect(formState.actividadInicio).not.toBe('');
    expect(formState.fechaInicio).not.toBe('');
    expect(formState.tipoContratoCheck).toBe('MO');
    const saveResponsePromise = page.waitForResponse((response) => {
      return response.url().includes('/api/listado-actividades/save')
        && response.request().postData()?.includes('registrar');
    });
    await page.locator('#btn_guardar_actividad').click();
    const saveResponse = await saveResponsePromise;
    const savePayload = await saveResponse.json();
    await page.waitForTimeout(250);

    expect(saveRequests).toHaveLength(1);
    expect(savePayload.respuesta, JSON.stringify(savePayload)).toBe('BIEN');
    const api = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    const created = api.payload.data.filter((row) => row.actividad === uniqueName);
    expect(created).toHaveLength(1);
  });

  test('Cargar desde Excel valida, importa, recarga y queda restaurable', async ({ page }) => {
    const before = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    await page.locator('#btn_cargarActividadesExcel').click();
    const modal = page.locator('#modalCargarExcel');
    await expect(modal).toBeVisible();
    const file = modal.locator('#archivoExcel');
    await file.setInputFiles({
      name: 'listado.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('actividad;descripcionActividad\n', 'utf8'),
    });
    const invalidResponse = page.waitForResponse((response) => (
      response.url().includes('/api/listado-actividades/save')
    ));
    await modal.locator('input[type="submit"]').click();
    expect((await (await invalidResponse).json()).respuesta).toBe('ERROR');
    const afterInvalid = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    expect(afterInvalid.payload.data).toEqual(before.payload.data);

    await file.setInputFiles({
      name: 'listado.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from(
        'actividad;descripcionActividad\nFamilia CSV Uno;Descripción uno\nFamilia CSV Dos;Descripción dos\n',
        'utf8',
      ),
    });
    const validResponse = page.waitForResponse((response) => (
      response.url().includes('/api/listado-actividades/save')
    ));
    await modal.locator('input[type="submit"]').click();
    expect((await (await validResponse).json()).respuesta).toBe('BIEN');
    await expect(modal).not.toBeVisible();
    const imported = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    expect(imported.payload.data.map((row) => row.actividad)).toEqual([
      'Familia CSV Uno',
      'Familia CSV Dos',
    ]);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => (
      window.ListadoActividadesHotModule?.getHotInstance?.()?.countSourceRows() === 2
    ));
    expect((await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`)).payload.data)
      .toHaveLength(2);
  });

  test('Eliminar confirma una vez y retira la familia', async ({ page }) => {
    const original = await page.evaluate(() => {
      const row = window.ListadoActividadesHotModule.getHotInstance().getSourceDataAtRow(0);
      return { id: row.Id, actividad: row.actividad };
    });
    const deleteRequests = [];
    page.on('request', (request) => {
      const isDelete = request.postData()?.includes('eliminar');
      if (isDelete && request.url().includes('/api/listado-actividades/save')) deleteRequests.push(request);
    });
    await page.locator('#la-mobile-card-list .la-mobile-card').first()
      .locator('button[title="Eliminar familia"]').click();
    await expect(page.locator('#modalEliminar')).toBeVisible();
    const responsePromise = page.waitForResponse((response) => {
      return response.url().includes('/api/listado-actividades/save')
        && response.request().postData()?.includes('eliminar');
    });
    await page.locator('#eliminar-usuario').click();
    const payload = await (await responsePromise).json();
    await page.waitForTimeout(300);
    expect(payload.respuesta).toBe('BIEN');
    expect(deleteRequests).toHaveLength(1);
    const api = await postFormJson(page, `/api/listado-actividades/list?semana=${PROJECT.maxWeek}`);
    expect(api.payload.data.some((row) => row.Id === original.id)).toBe(false);
  });
});

test.describe('Automatización restaurable de Listado', () => {
  let snapshot;
  let baselineFingerprint;
  let runIds;

  test.beforeEach(async ({ page }) => {
    snapshot = new ProjectDbSnapshot(PROJECT, AUTOMATION_TABLES).capture();
    baselineFingerprint = snapshot.fingerprint();
    runIds = [];
    runSql(`DELETE FROM programa_consolidado WHERE project_id=${PROJECT.projectId} AND Semana=${PROJECT.maxWeek} AND unique_id=${AUTO_SOURCE_ID}`);
    runSql(`DELETE FROM programa WHERE project_id=${PROJECT.projectId} AND Consecutivo=${AUTO_SOURCE_ID}`);
    runSql(`INSERT INTO programa (project_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
      VALUES (${PROJECT.projectId}, ${AUTO_SOURCE_ID}, 'E2E.AUTO.1', 'PISOS LAMINADOS E2E [Capítulo: ACABADOS TORRE A]', 0, '2031-02-03', '2031-02-05')`);
    runSql(`INSERT INTO programa_consolidado
      (project_id, row_id, unique_id, Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
      VALUES (${PROJECT.projectId}, ${AUTO_SOURCE_ID}, ${AUTO_SOURCE_ID}, ${AUTO_SOURCE_ID}, ${PROJECT.maxWeek}, ${AUTO_SOURCE_ID},
        'E2E.AUTO.1', 'PISOS LAMINADOS E2E [Capítulo: ACABADOS TORRE A]', 0, '2031-02-03', '2031-02-05')`);
    await openListado(page, { width: 390, height: 844 });
  });

  test.afterEach(() => {
    if (!snapshot) return;
    for (const runId of runIds || []) {
      const safeRunId = String(runId).replaceAll("'", "''");
      runSql(`DELETE FROM semi_auto_feedback WHERE run_id='${safeRunId}'`);
      runSql(`DELETE FROM semi_auto_decisions WHERE run_id='${safeRunId}'`);
      runSql(`DELETE FROM semi_auto_suggestions WHERE run_id='${safeRunId}'`);
      runSql(`DELETE FROM semi_auto_runs WHERE run_id='${safeRunId}'`);
    }
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(baselineFingerprint);
    snapshot.dispose();
    snapshot = null;
  });

  test('Auto abre, analiza y cierra sin ocultar los registros', async ({ page }) => {
    const previewPromise = page.waitForResponse(
      (response) => response.url().includes('/api/listado-actividades/auto/preview'),
      { timeout: 90_000 },
    );
    await page.locator('#btn_auto_generar_listado').click();
    const preview = await (await previewPromise).json();
    runIds.push(preview.run_id);
    expect(preview.respuesta).toBe('BIEN');
    const fixtureSuggestion = (preview.suggestions || []).find((suggestion) => (
      JSON.stringify(suggestion).includes(String(AUTO_SOURCE_ID))
    ));
    expect(fixtureSuggestion, JSON.stringify(preview.summary || preview.analysis?.summary)).toBeTruthy();
    expect(fixtureSuggestion.preselected, JSON.stringify(fixtureSuggestion)).toBe(true);

    const panel = page.locator('#semiAutoReview-listado-actividades');
    await expect(panel).toBeVisible();
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await expect(panel.locator('.sar-btn-preview')).toBeVisible();
    await expect(panel.locator('.sar-btn-apply')).toBeVisible();
    await expect(panel.locator('.sar-btn-undo')).toBeVisible();
    await panel.locator('.sar-btn-close').click();
    await expect(panel).not.toBeVisible();
    await expect(page.locator('#la-mobile-card-list .la-mobile-card').first()).toBeVisible();
  });

  test('Auto aplica y deshace propuestas sin dejar cambios', async ({ page }) => {
    const previewPromise = page.waitForResponse(
      (response) => response.url().includes('/api/listado-actividades/auto/preview'),
      { timeout: 90_000 },
    );
    await page.locator('#btn_auto_generar_listado').click();
    const preview = await (await previewPromise).json();
    runIds.push(preview.run_id);
    expect(preview.respuesta).toBe('BIEN');
    const fixtureSuggestion = (preview.suggestions || []).find((suggestion) => (
      JSON.stringify(suggestion).includes(String(AUTO_SOURCE_ID))
    ));
    expect(fixtureSuggestion, JSON.stringify(preview.summary || preview.analysis?.summary)).toBeTruthy();
    expect(fixtureSuggestion.preselected, JSON.stringify(fixtureSuggestion)).toBe(true);

    const panel = page.locator('#semiAutoReview-listado-actividades');
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await panel.locator('.sar-tab[data-filter="ready"]').click();
    const apply = panel.locator('.sar-btn-apply');
    const choices = panel.locator('.sar-row-check:not(:disabled)');
    expect(await choices.count()).toBeGreaterThan(0);
    if (await apply.isDisabled()) {
      await choices.first().check();
    }
    await expect(apply).toBeEnabled();
    const applyPromise = page.waitForResponse(
      (response) => response.url().includes('/api/listado-actividades/auto/apply'),
      { timeout: 90_000 },
    );
    await apply.click();
    const applied = await (await applyPromise).json();
    const applyErrors = Number(applied.errores || 0) > 0
      ? runSql(`SELECT result_payload FROM semi_auto_decisions WHERE run_id='${applied.run_id}' AND decision='error'`)
      : '';
    expect(applied.respuesta, JSON.stringify(applied)).toBe('BIEN');
    expect(Number(applied.aplicadas), `${JSON.stringify(applied)} ${applyErrors}`).toBeGreaterThan(0);
    expect(Number(applied.errores || 0), applyErrors).toBe(0);

    const undo = panel.locator('.sar-btn-undo');
    await expect(undo).toBeEnabled({ timeout: 90_000 });
    const undoPromise = page.waitForResponse(
      (response) => response.url().includes('/api/listado-actividades/auto/undo'),
      { timeout: 90_000 },
    );
    await undo.click();
    const reverted = await (await undoPromise).json();
    expect(reverted.respuesta, JSON.stringify(reverted)).toBe('BIEN');
    expect(Number(reverted.revertidas)).toBeGreaterThan(0);
    expect(Number(reverted.errores || 0)).toBe(0);
  });
});
