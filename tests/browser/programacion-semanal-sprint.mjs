import { test, expect } from '@playwright/test';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';
import { runSql } from './support/dbSnapshot.mjs';

const JMC = { name: 'Optimización Aeropuerto JMC' };
const WEEK = 4;
const ACTIVITY_TEXT = 'Movilización general';

function tableChecksum(table) {
  return runSql(`CHECKSUM TABLE \`${table}\`;`).trim().split(/\s+/).at(-1);
}

async function openQualificationWeek(page, viewport = { width: 390, height: 844 }) {
  await page.setViewportSize(viewport);
  await loginAndSelectProject(page, JMC);
  await changeWeek(page, WEEK, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  await page.reload();
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  await expect(page.locator('#semana_PHP')).toHaveValue(String(WEEK));
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Calificación de Compromisos',
  );
}

const WEEKLY_STATE_FIELDS = [
  'Compromiso', 'Ejecutado_Real', 'Categoria_CNC', 'CNC', 'Observaciones_CNC',
];

function weeklyState(row) {
  return Object.fromEntries(WEEKLY_STATE_FIELDS.map((field) => {
    const value = String(row?.[field] ?? '').trim();
    const normalized = ['Compromiso', 'Ejecutado_Real'].includes(field) && value !== ''
      ? String(Number(value)) : value;
    return [field, normalized];
  }));
}

async function readWeeklyRow(page, activityText = ACTIVITY_TEXT) {
  const db = await page.locator('#baseDatos_PHP').inputValue();
  const response = await page.request.get(`/api/semanal/list?db=${encodeURIComponent(db)}&semana=${WEEK}&_=${Date.now()}`);
  expect(response.ok()).toBe(true);
  const payload = await response.json();
  return { db, row: payload.data.find((item) => String(item.Actividad || '').includes(activityText)) };
}

async function restoreWeeklyRow(page, db, row) {
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const response = await page.request.post(`/api/semanal/save?db=${encodeURIComponent(db)}`, { form: {
    opcion: 'modificar', _csrf_token: csrf || '', Id: String(row.Consecutivo), semana: String(WEEK),
    Descripcion: row.Descripcion || '', Ubicacion: row.Ubicacion || '',
    Sub_Contratista: row.Sub_Contratista || '', Responsable_AIA: row.Responsable_AIA || '',
    Empresa: row.Empresa || '', Unidad: row.Unidad || '%',
    Compromiso: String(row.Compromiso ?? ''), Cantidad_Sugerida: String(row.Cantidad_Sugerida ?? ''),
    Real: String(row.Ejecutado_Real ?? ''), Rendimientos: row.Rendimientos || '',
    Categoria_CNC: row.Categoria_CNC || '', CNC: row.CNC || '',
    Observaciones_CNC: row.Observaciones_CNC || '', Es_TNP: row.Es_TNP || '',
  } });
  const payload = await response.json();
  expect(response.ok(), JSON.stringify(payload)).toBe(true);
  expect(payload.respuesta).toBe('BIEN');
  await expect.poll(async () => {
    const restored = (await readWeeklyRow(page)).row;
    return weeklyState(restored);
  }, { message: 'La restauración debe quedar confirmada por una lectura posterior' })
    .toEqual(weeklyState(row));
}

async function visibleTouchTargets(page) {
  return page.evaluate(() => {
    const selector = '.hot-full-bleed button, .hot-full-bleed a[href], .hot-full-bleed input:not([type="hidden"]), '
      + '.hot-full-bleed select, .hot-full-bleed textarea, .hot-full-bleed [role="button"], '
      + '#lps_sidebar_trigger, #notificationDropdown';
    return Array.from(document.querySelectorAll(selector)).map((element) => ({ element,
      rect: element.getBoundingClientRect(), style: getComputedStyle(element) }))
      .filter(({ rect, style }) => style.display !== 'none' && style.visibility !== 'hidden'
        && rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < innerHeight
        && rect.right > 0 && rect.left < innerWidth)
      .map(({ element, rect }) => ({ label: element.getAttribute('aria-label') || element.textContent.trim()
        || element.id || element.className, className: String(element.className),
      width: Math.round(rect.width), height: Math.round(rect.height) }));
  });
}

test('Programacion Semanal CSS uses registered color tokens only', async ({ page }) => {
  const response = await page.request.get('/css/programacion-semanal.css');
  expect(response.ok()).toBe(true);
  const css = await response.text();
  expect(css.match(/(?:#[\da-f]{3,8}\b|(?:rgb|hsl|oklch)\()/gi) || []).toEqual([]);
});

test('mobile qualification opens CNC before saving an unmet commitment', async ({ page }) => {
  await openQualificationWeek(page);
  await page.evaluate(() => window.AiaDesignSystem.setTheme('dark'));
  const { db, row } = await readWeeklyRow(page);
  expect(row).toBeTruthy();
  const original = { ...row };
  const originalChecksums = {
    programacionSemanal: tableChecksum('programacion_semanal'),
    programaConsolidado: tableChecksum('programa_consolidado'),
  };
  const changedReal = Math.max(0, Number(row.Compromiso) - 1);
  const card = page.locator('article.ps-mobile-card').filter({ hasText: ACTIVITY_TEXT });
  await expect(card).toHaveCount(1);
  const input = card.locator('input[data-mobile-prop="Ejecutado_Real"]');
  const save = card.locator('button[data-mobile-save-prop="Ejecutado_Real"]');
  const fabOverlapsSave = await save.evaluate((button) => {
    const fab = document.querySelector('#lps_sidebar_trigger');
    if (!fab) return false;
    const a = button.getBoundingClientRect();
    const b = fab.getBoundingClientRect();
    return a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top;
  });
  expect(fabOverlapsSave).toBe(false);
  const saveLabelFits = await save.evaluate((button) => (
    button.scrollWidth <= button.clientWidth && button.textContent.trim() === 'Guardar avance'
  ));
  expect(saveLabelFits).toBe(true);

  try {
    await input.fill(String(changedReal));
    await save.click();
    const modal = page.locator('#modal_cnc_hot');
    await expect(modal).toBeVisible();
    const darkModal = await modal.evaluate((element) => {
      const lightness = (selector) => {
        const color = getComputedStyle(element.querySelector(selector)).backgroundColor;
        const values = color.match(/[\d.]+/g).slice(0, 3).map(Number);
        return values.reduce((sum, value) => sum + value, 0) / values.length;
      };
      return { theme: document.documentElement.dataset.aiaTheme,
        bodySurface: lightness('.modal-body'), footerSurface: lightness('.modal-footer'),
        controlSurface: lightness('#hot_cat_cnc') };
    });
    expect(darkModal.theme).toBe('dark');
    expect(darkModal.bodySurface).toBeLessThan(120);
    expect(darkModal.footerSurface).toBeLessThan(120);
    expect(darkModal.controlSurface).toBeLessThan(120);
    await expect(page.locator('.swal2-popup.swal2-show:not(.swal2-toast)')).toHaveCount(0);
    const category = modal.locator('#hot_cat_cnc');
    const categoryValue = await category.locator('option:not([value=""])').first().getAttribute('value');
    expect(categoryValue).toBeTruthy();
    const reasonsResponse = page.waitForResponse((response) => response.url().includes('/api/cnc/reasons'));
    await category.selectOption(categoryValue);
    expect((await reasonsResponse).ok()).toBe(true);
    const cause = modal.locator('#hot_cnc');
    await expect(cause.locator('option[value="Otra"]')).toHaveCount(1);
    await cause.selectOption('Otra');
    const observation = `Validacion sprint CNC ${Date.now()}`;
    await modal.locator('#hot_obs_cnc').fill(observation);
    const saveResponse = page.waitForResponse((response) => response.url().includes('/api/semanal/save'));
    await modal.locator('#btn_guardar_cnc_hot').click();
    const savedResponse = await saveResponse;
    expect(savedResponse.ok()).toBe(true);
    expect(await savedResponse.json()).toMatchObject({ respuesta: 'BIEN' });
    await expect(modal).toBeHidden();
    await expect(card.locator('[data-mobile-save-status]')).toContainText('Guardado');
    await page.reload();
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    await expect(card.locator('input[data-mobile-prop="Ejecutado_Real"]')).toHaveValue(String(changedReal));
    const persisted = (await readWeeklyRow(page)).row;
    expect(weeklyState(persisted).Ejecutado_Real).toBe(String(changedReal));
    expect(persisted.Categoria_CNC).toBe(categoryValue);
    expect(persisted.CNC).toBe('Otra');
    expect(persisted.Observaciones_CNC).toBe(observation);
  } finally {
    await restoreWeeklyRow(page, db, original);
    expect({
      programacionSemanal: tableChecksum('programacion_semanal'),
      programaConsolidado: tableChecksum('programa_consolidado'),
    }).toEqual(originalChecksums);
  }
});

test('desktop qualification opens CNC from the editable table', async ({ page }) => {
  await openQualificationWeek(page, { width: 1440, height: 900 });
  const { db, row } = await readWeeklyRow(page);
  expect(row).toBeTruthy();
  const original = { ...row };
  const changedReal = Math.max(0, Number(row.Compromiso) - 1);
  const table = page.locator('#hot-container .ht_master table.htCore');
  const activityRow = table.locator('tbody tr').filter({ hasText: ACTIVITY_TEXT });
  await expect(activityRow).toHaveCount(1);
  const cell = activityRow.getByRole('gridcell').nth(9);
  try {
    await cell.dblclick();
    const editor = page.locator('textarea.handsontableInput:visible');
    await expect(editor).toBeVisible();
    await editor.fill(String(changedReal));
    await page.keyboard.press('Enter');
    const modal = page.locator('#modal_cnc_hot');
    await expect(modal).toBeVisible();
    await expect(page.locator('.swal2-popup.swal2-show')).toHaveCount(0);

    const category = modal.locator('#hot_cat_cnc');
    const categoryValue = await category.locator('option:not([value=""])').first().getAttribute('value');
    expect(categoryValue).toBeTruthy();
    const reasonsResponse = page.waitForResponse((response) => response.url().includes('/api/cnc/reasons'));
    await category.selectOption(categoryValue);
    expect((await reasonsResponse).ok()).toBe(true);
    const cause = modal.locator('#hot_cnc');
    await expect(cause.locator('option[value="Otra"]')).toHaveCount(1);
    await cause.selectOption('Otra');
    const observation = `Validacion escritorio CNC ${Date.now()}`;
    await modal.locator('#hot_obs_cnc').fill(observation);
    const saveResponse = page.waitForResponse((response) => response.url().includes('/api/semanal/save'));
    await modal.locator('#btn_guardar_cnc_hot').click();
    const savedResponse = await saveResponse;
    expect(savedResponse.ok()).toBe(true);
    expect(await savedResponse.json()).toMatchObject({ respuesta: 'BIEN' });
    await expect(modal).toBeHidden();

    await page.reload();
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    const persisted = (await readWeeklyRow(page)).row;
    expect(weeklyState(persisted)).toEqual({ ...weeklyState(original),
      Ejecutado_Real: String(changedReal), Categoria_CNC: categoryValue,
      CNC: 'Otra', Observaciones_CNC: observation });
  } finally {
    await restoreWeeklyRow(page, db, original);
  }
});

test('tablet horizontal keeps the table and 44px toolbar targets without page overflow', async ({ page }) => {
  await openQualificationWeek(page, { width: 1180, height: 820 });
  const state = await page.evaluate(() => {
    return { overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      tableVisible: getComputedStyle(document.querySelector('#hot-container')).display !== 'none' };
  });
  expect(state).toMatchObject({ overflow: 0, tableVisible: true });
  const undersized = (await visibleTouchTargets(page))
    .filter(({ width, height }) => width < 44 || height < 44);
  expect(undersized).toEqual([]);
});

test('desktop keeps 44px toolbar targets without page overflow', async ({ page }) => {
  await openQualificationWeek(page, { width: 1440, height: 900 });
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth
    - document.documentElement.clientWidth);
  expect(overflow).toBe(0);
  const undersized = (await visibleTouchTargets(page))
    .filter(({ width, height }) => width < 44 || height < 44);
  expect(undersized).toEqual([]);
});

for (const section of ['CNP', 'CNC', 'CIC']) {
  test(`${section} renders records as mobile cards without a visible table`, async ({ page }) => {
    await openQualificationWeek(page);
    await page.goto(`/legacy/cambiar_pagina.php?seccion=${section}&semana=${WEEK}`);
    await page.locator('#dt_cliente').waitFor({ state: 'attached', timeout: 30000 });
    await page.waitForFunction(() => {
      const cards = document.querySelectorAll('#ps-legacy-card-view .ps-legacy-card');
      const empty = document.querySelector('#ps-legacy-card-view .ps-legacy-card-empty');
      return cards.length > 0 || Boolean(empty);
    });
    await expect(page.locator('#ps-legacy-card-view')).toBeVisible();
    await expect(page.locator('#dt_cliente')).toBeHidden();
    const cards = page.locator('#ps-legacy-card-view article.ps-legacy-card');
    const empty = page.locator('#ps-legacy-card-view .ps-legacy-card-empty');
    await expect.poll(async () => await cards.count() + await empty.count()).toBeGreaterThan(0);
    const targetHeights = await page.evaluate(() => Array.from(document.querySelectorAll('button, a.btn'))
      .map((element) => element.getBoundingClientRect())
      .filter((rect) => rect.width > 0 && rect.height > 0
        && rect.bottom > 0 && rect.top < innerHeight && rect.right > 0 && rect.left < innerWidth)
      .map((rect) => Math.round(rect.height)));
    expect(Math.min(...targetHeights)).toBeGreaterThanOrEqual(44);
  });
}
