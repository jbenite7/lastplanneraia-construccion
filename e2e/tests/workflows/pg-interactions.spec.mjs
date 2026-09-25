import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, logout, postFormJson, getJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { abrirActividadPg, contarFilasPg, editarCampoDrawerPg, esperarTablaPg, guardarDrawerPg, leerCampoFilaPg } from '../../support/programa-general-react.mjs';

const PROJECT_DA_PORTO = PROJECTS.find((project) => project.key === 'construction');
const PROJECT_PC = PROJECTS.find((project) => project.key === 'pc');
const ADMIN = { username: 'test.A', password: 'aia2026' };
const RESIDENT = { username: 'test.R', password: 'aia2026' };
const SUBCONTRACTOR = { username: 'test.C', password: 'aia2026' };
const VIEWER = { username: 'test.V', password: 'aia2026' };
const REQUIRE_ISOLATED_DB = process.env.E2E_REQUIRE_ISOLATED_DB === '1';

if (REQUIRE_ISOLATED_DB) {
  if (process.env.E2E_ALLOW_DB_MUTATION !== 'design-system-ci') {
    throw new Error('E2E_ALLOW_DB_MUTATION=design-system-ci is required for isolated persistence tests');
  }
  if (!PROJECT_DA_PORTO) {
    throw new Error('Da Porto construction project is required for the isolated Programa General gate');
  }
}

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiGet(page, url) {
  const result = await getJson(page, url);
  return { ok: result.ok && !result.payload.parseError, payload: result.payload };
}

async function escogerActividadConUnidad(page) {
  await esperarTablaPg(page);
  const row = page.locator('table.programa-table-pro tbody tr.row-activity').first();
  const uniqueId = Number(await row.getAttribute('data-unique-id'));
  expect(uniqueId, 'PG React must expose the activity unique_id').toBeGreaterThan(0);
  await abrirActividadPg(page, uniqueId);
  const originalValue = await page.getByLabel('Unidad', { exact: true }).inputValue();
  await page.keyboard.press('Escape');
  return { uniqueId, originalValue, testValue: originalValue === 'ml' ? 'm2' : 'ml' };
}

async function guardarUnidad(page, target, value) {
  const response = page.waitForResponse((candidate) => (
    candidate.url().includes('/api/general/update?') && candidate.request().method() === 'POST'
  ));
  await abrirActividadPg(page, target.uniqueId);
  await editarCampoDrawerPg(page, 'Unidad', value);
  await guardarDrawerPg(page);
  const payload = await (await response).json();
  expect(payload.respuesta ?? payload.success).toBeTruthy();
}

async function activarTreceColumnas(page) {
  const control = page.getByRole('button', { name: /13 Cols Reales/i });
  if ((await control.getAttribute('aria-pressed')) !== 'true') await control.click();
  await expect(control).toHaveAttribute('aria-pressed', 'true');
}

test.describe('PG interactions', () => {
  test('Da Porto Admin: persiste desde el Drawer, exporta CSV y abre LPS', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    let snapshot;
    let beforeFingerprint;
    try {
      snapshot = new ProjectDbSnapshot(PROJECT_DA_PORTO).capture();
      beforeFingerprint = snapshot.fingerprint();
      await loginAndSelectProject(page, PROJECT_DA_PORTO, ADMIN);
      await changeWeek(page, 1, '/programa-general');
      expect(await contarFilasPg(page), 'PG React must have activity rows').toBeGreaterThan(0);
      const target = await escogerActividadConUnidad(page);
      await guardarUnidad(page, target, target.testValue);
      await page.reload();
      await esperarTablaPg(page);
      await activarTreceColumnas(page);
      expect(await leerCampoFilaPg(page, target.uniqueId, 'UNIDAD')).toBe(target.testValue);
      const api = await apiGet(page, `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`);
      const apiRow = api.payload.data?.find((row) => Number(row.unique_id) === target.uniqueId);
      expect(apiRow?.unidad, 'UI → API persistence').toBe(target.testValue);
      expect(scalar(`SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_DA_PORTO.projectId} AND unique_id=${target.uniqueId} AND unidad='${target.testValue}'`), 'API → DB persistence').toBe(1);
      const download = page.waitForEvent('download');
      await page.getByRole('button', { name: 'CSV', exact: true }).click();
      expect((await download).suggestedFilename()).toContain('programa_general');
      await abrirActividadPg(page, target.uniqueId);
      await expect(page.getByRole('dialog', { name: 'Editor Contextual LPS' })).toBeVisible();
      await page.keyboard.press('Escape');
      await guardarUnidad(page, target, target.originalValue);
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) {
        snapshot.restore();
        expect(snapshot.fingerprint()).toBe(beforeFingerprint);
        snapshot.dispose();
      }
    }
    testInfo._e2eErrors = errors;
  });

  test('Da Porto Residente: persiste el campo permitido desde el Drawer', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    let snapshot;
    try {
      snapshot = new ProjectDbSnapshot(PROJECT_DA_PORTO).capture();
      await loginAndSelectProject(page, PROJECT_DA_PORTO, RESIDENT);
      await changeWeek(page, 1, '/programa-general');
      expect(await contarFilasPg(page), 'Resident must see PG activities').toBeGreaterThan(0);
      const target = await escogerActividadConUnidad(page);
      await guardarUnidad(page, target, target.testValue);
      await page.reload();
      await esperarTablaPg(page);
      await activarTreceColumnas(page);
      expect(await leerCampoFilaPg(page, target.uniqueId, 'UNIDAD')).toBe(target.testValue);
      await guardarUnidad(page, target, target.originalValue);
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    testInfo._e2eErrors = errors;
  });

  test('Da Porto read-only roles: UI and manipulated API writes remain denied', async ({ page }) => {
    for (const role of [{ credentials: SUBCONTRACTOR, canView: false }, { credentials: VIEWER, canView: true }]) {
      await loginAndSelectProject(page, PROJECT_DA_PORTO, role.credentials);
      expect((await page.goto('/programa-general')).status()).toBe(200);
      const list = await getJson(page, `/api/general/list?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`);
      expect(list.status).toBe(role.canView ? 200 : 403);
      if (role.canView) {
        await esperarTablaPg(page);
        const uniqueId = Number(await page.locator('tr.row-activity').first().getAttribute('data-unique-id'));
        await abrirActividadPg(page, uniqueId);
        const unit = page.getByLabel('Unidad', { exact: true });
        const save = page.getByRole('button', { name: /Guardar Cambios/i });
        expect((await unit.isDisabled()) || !(await save.isVisible().catch(() => false)), 'Viewer drawer must not permit editing').toBe(true);
      } else {
        await expect(page.getByRole('alert')).toBeVisible();
      }
      const denied = await postFormJson(page, `/api/general/update?db=${PROJECT_DA_PORTO.dbPrefix}&semana=1`, { opcion: 'modificar', Id: '0', unidad: 'E2E_FORBIDDEN' });
      expect([403, 422], 'Manipulated PG update must be denied').toContain(denied.status);
      await logout(page);
    }
  });

  test('Aeropuerto PC Admin: conserva chips, persistencia y CSV en React', async ({ page }, testInfo) => {
    test.skip(!PROJECT_PC, 'Aeropuerto PC project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    let snapshot;
    let beforeFingerprint;
    try {
      snapshot = new ProjectDbSnapshot(PROJECT_PC).capture();
      beforeFingerprint = snapshot.fingerprint();
      await loginAndSelectProject(page, PROJECT_PC, ADMIN);
      await changeWeek(page, 1, '/programa-general');
      expect(await contarFilasPg(page)).toBeGreaterThan(0);
      for (const label of ['Atrasada', 'Con Alerta', 'Debe Iniciar', 'En Curso']) await expect(page.getByRole('button', { name: new RegExp(label) }).first()).toBeVisible();
      const target = await escogerActividadConUnidad(page);
      await guardarUnidad(page, target, target.testValue);
      await activarTreceColumnas(page);
      expect(await leerCampoFilaPg(page, target.uniqueId, 'UNIDAD')).toBe(target.testValue);
      expect(scalar(`SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT_PC.projectId} AND unique_id=${target.uniqueId} AND unidad='${target.testValue}'`)).toBe(1);
      const download = page.waitForEvent('download');
      await page.getByRole('button', { name: 'CSV', exact: true }).click();
      expect((await download).suggestedFilename()).toContain('programa_general');
      await guardarUnidad(page, target, target.originalValue);
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) {
        snapshot.restore();
        expect(snapshot.fingerprint()).toBe(beforeFingerprint);
        snapshot.dispose();
      }
    }
    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({}, testInfo) => {
    generateFindings(testInfo, testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] });
  });
});
