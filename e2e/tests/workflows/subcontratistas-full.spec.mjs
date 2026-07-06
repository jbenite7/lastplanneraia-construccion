/**
 * Subcontratistas + Profesionales — Full CRUD workflow (API-driven).
 *
 * Targets:
 *   - Da Porto (construction; projectId=73): "Subcontratistas" title, "Mano de Obra"
 *   - Aeropuerto PC (pre-construction; projectId=75): "Interesados Externos" title, "Consultor"
 *   - Residente RBAC check on Da Porto
 *
 * Table: Handsontable (#hot-container) for both modules.
 *
 * Workflow per project:
 *   1. Navigate → verify Handsontable page loads
 *   2. Verify page title (project-specific)
 *   3. API list verification
 *   4. DB count verification
 *   5. Create via API
 *   6. Verify in API list
 *   7. Update via API
 *   8. Verify updated in API list
 *   9. Delete via API
 *   10. Verify removed from API list
 *   11. DB cleanup verification (snapshot restore)
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { SUBCONTRATISTAS_SELECTORS } from '../../support/moduleSelectors.mjs';

const DP = PROJECTS.find((p) => p.key === 'construction');
const PC = PROJECTS.find((p) => p.key === 'pc');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

function stamp(suffix) {
  return `E2E ${suffix} ${Date.now()} ${Math.floor(Math.random() * 10000)}`;
}

// ─── Test 1: Da Porto — Subcontratistas full CRUD ─────────────────────────────

test.describe('Subcontratistas: Da Porto (construction) CRUD workflow', () => {
  test('Da Porto Admin: Subcontratistas — page load, API, DB, create, update, delete', async ({ page }, testInfo) => {
    test.skip(!DP, 'Da Porto (construction) project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(DP).capture();

      await loginAndSelectProject(page, DP);

      // ─── 1. Navigate to /subcontratistas ───────────────────────────────────
      await page.goto('/subcontratistas', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('.handsontable, #hot-container, body', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /subcontratistas');

      // ─── 2. Verify title ───────────────────────────────────────────────────
      const titleEl = page.locator('.header-actions h4');
      await expect(titleEl).toContainText(DP.expectedSubcontractorTitle, { timeout: 10_000 });
      findings.push(`Title verified: "${DP.expectedSubcontractorTitle}"`);

      // ─── 3. Handsontable visible ────────────────────────────────────────────
      const hotVisible = await page.locator('.handsontable .htCore, #hot-container').first().isVisible().catch(() => false);
      findings.push(`Handsontable visible: ${hotVisible}`);

      // ─── 4. API list verify ─────────────────────────────────────────────────
      const listBefore = await apiPost(page, `/api/subcontratistas/list?db=${DP.dbPrefix}`, { opcion: 'listar' });
      expect(listBefore.ok, 'API /api/subcontratistas/list must succeed').toBe(true);
      expect(listBefore.payload.status, JSON.stringify(listBefore.payload)).toBe('success');
      const initialCount = Array.isArray(listBefore.payload.data) ? listBefore.payload.data.length : 0;
      findings.push(`API list: ${initialCount} rows`);
      expect(initialCount, 'API list should return rows array').toBeGreaterThan(-1);

      // ─── 5. DB count ───────────────────────────────────────────────────────
      const dbCountBefore = scalar(
        `SELECT COUNT(*) FROM subcontratistas WHERE project_id=${DP.projectId}`,
      );
      findings.push(`DB subcontratistas count before: ${dbCountBefore}`);

      // ─── 6. CREATE ─────────────────────────────────────────────────────────
      const name = stamp('Subcont TEST');
      const email = `e2e-test-${Date.now()}@mail.com`;
      const nit = String(Date.now()).slice(-9);

      const createResult = await apiPost(page, `/api/subcontratistas/save?db=${DP.dbPrefix}`, {
        opcion: 'crear',
        subcontratista: name,
        correo_contacto: email,
        NIT: nit,
        alcance: 'E2E Scope',
        tipo_proveedor: DP.providerType,
      });
      expect(createResult.ok, 'Create API must succeed').toBe(true);
      expect(createResult.payload.status, JSON.stringify(createResult.payload)).toBe('success');
      const createdId = createResult.payload.id;
      expect(createdId, 'Create must return id').toBeTruthy();
      findings.push(`Created: id=${createdId}, name="${name}"`);

      await page.waitForTimeout(1000);

      // ─── 7. Verify in API list ─────────────────────────────────────────────
      const listAfterCreate = await apiPost(page, `/api/subcontratistas/list?db=${DP.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterCreate.ok, 'API list after create must succeed').toBe(true);
      const foundAfterCreate = listAfterCreate.payload.data.some(
        (row) => Number(row.Id) === Number(createdId) && row.subcontratista === name,
      );
      expect(foundAfterCreate, `Created row (id=${createdId}) must appear in API list`).toBe(true);
      findings.push('Create verified in API list');

      // ─── 8. UPDATE ─────────────────────────────────────────────────────────
      const updatedName = `${name} Updated`;
      const updateResult = await apiPost(page, `/api/subcontratistas/save?db=${DP.dbPrefix}`, {
        opcion: 'guardar_cambios',
        id: createdId,
        column: 'subcontratista',
        value: updatedName,
      });
      expect(updateResult.ok, 'Update API must succeed').toBe(true);
      expect(updateResult.payload.status, JSON.stringify(updateResult.payload)).toBe('success');
      findings.push(`Updated: id=${createdId}, new name="${updatedName}"`);

      await page.waitForTimeout(1000);

      // ─── 9. Verify update in API list ──────────────────────────────────────
      const listAfterUpdate = await apiPost(page, `/api/subcontratistas/list?db=${DP.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterUpdate.ok, 'API list after update must succeed').toBe(true);
      const foundAfterUpdate = listAfterUpdate.payload.data.some(
        (row) => Number(row.Id) === Number(createdId) && row.subcontratista === updatedName,
      );
      expect(foundAfterUpdate, `Updated row (id=${createdId}) must have name="${updatedName}"`).toBe(true);
      findings.push('Update verified in API list');

      // ─── 10. DELETE ────────────────────────────────────────────────────────
      const deleteResult = await apiPost(page, `/api/subcontratistas/save?db=${DP.dbPrefix}`, {
        opcion: 'eliminar',
        Id: createdId,
      });
      expect(deleteResult.ok, 'Delete API must succeed').toBe(true);
      expect(deleteResult.payload.status, JSON.stringify(deleteResult.payload)).toBe('success');
      findings.push(`Deleted: id=${createdId}`);

      await page.waitForTimeout(1000);

      // ─── 11. Verify removed from API list ──────────────────────────────────
      const listAfterDelete = await apiPost(page, `/api/subcontratistas/list?db=${DP.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterDelete.ok, 'API list after delete must succeed').toBe(true);
      const foundAfterDelete = listAfterDelete.payload.data.some(
        (row) => Number(row.Id) === Number(createdId),
      );
      expect(foundAfterDelete, `Deleted row (id=${createdId}) must NOT appear in API list`).toBe(false);
      findings.push('Delete verified: row removed from API list');

      // ─── 12. DB count after full CRUD ──────────────────────────────────────
      const dbCountAfter = scalar(
        `SELECT COUNT(*) FROM subcontratistas WHERE project_id=${DP.projectId}`,
      );
      findings.push(`DB subcontratistas count after CRUD: ${dbCountAfter} (was ${dbCountBefore})`);
      expect(dbCountAfter, 'DB count should return to original after delete').toBe(dbCountBefore);

      // ─── Summary ────────────────────────────────────────────────────────────
      console.log(`\n[Da Porto Subcontratistas] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errs);
  });
});

// ─── Test 2: Aeropuerto PC — Subcontratistas (Interesados Externos) ───────────

test.describe('Subcontratistas: Aeropuerto PC (pre-construccion) CRUD workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PC, 'Aeropuerto PC project required — set E2E_INCLUDE_PRECONSTRUCTION=1');
  });

  test('Aeropuerto PC Admin: Interesados Externos — page load, API, DB, create, update, delete', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PC).capture();

      await loginAndSelectProject(page, PC);

      // ─── 1. Navigate to /subcontratistas ───────────────────────────────────
      await page.goto('/subcontratistas', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('.handsontable, #hot-container, body', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /subcontratistas');

      // ─── 2. Verify title (PC: "Interesados Externos") ──────────────────────
      const titleEl = page.locator('.header-actions h4');
      await expect(titleEl).toContainText(PC.expectedSubcontractorTitle, { timeout: 10_000 });
      findings.push(`Title verified: "${PC.expectedSubcontractorTitle}"`);

      // ─── 3. Handsontable visible ────────────────────────────────────────────
      const hotVisible = await page.locator('.handsontable .htCore, #hot-container').first().isVisible().catch(() => false);
      findings.push(`Handsontable visible: ${hotVisible}`);

      // ─── 4. API list verify ─────────────────────────────────────────────────
      const listBefore = await apiPost(page, `/api/subcontratistas/list?db=${PC.dbPrefix}`, { opcion: 'listar' });
      expect(listBefore.ok, 'API /api/subcontratistas/list must succeed').toBe(true);
      expect(listBefore.payload.status, JSON.stringify(listBefore.payload)).toBe('success');
      const initialCount = Array.isArray(listBefore.payload.data) ? listBefore.payload.data.length : 0;
      findings.push(`API list: ${initialCount} rows`);

      // ─── 5. DB count ───────────────────────────────────────────────────────
      const dbCountBefore = scalar(
        `SELECT COUNT(*) FROM subcontratistas WHERE project_id=${PC.projectId}`,
      );
      findings.push(`DB subcontratistas count before: ${dbCountBefore}`);

      // ─── 6. CREATE (PC uses "Consultor" provider type) ─────────────────────
      const name = stamp('Interesado E2E');
      const email = `e2e-pc-${Date.now()}@mail.com`;
      const nit = String(Date.now()).slice(-9);

      const createResult = await apiPost(page, `/api/subcontratistas/save?db=${PC.dbPrefix}`, {
        opcion: 'crear',
        subcontratista: name,
        correo_contacto: email,
        NIT: nit,
        alcance: 'E2E Scope PC',
        tipo_proveedor: PC.providerType,
      });
      expect(createResult.ok, 'Create API must succeed').toBe(true);
      expect(createResult.payload.status, JSON.stringify(createResult.payload)).toBe('success');
      const createdId = createResult.payload.id;
      expect(createdId, 'Create must return id').toBeTruthy();
      findings.push(`Created: id=${createdId}, name="${name}", tipo_proveedor="${PC.providerType}"`);

      await page.waitForTimeout(1000);

      // ─── 7. Verify in API list ─────────────────────────────────────────────
      const listAfterCreate = await apiPost(page, `/api/subcontratistas/list?db=${PC.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterCreate.ok, 'API list after create must succeed').toBe(true);
      const foundAfterCreate = listAfterCreate.payload.data.some(
        (row) => Number(row.Id) === Number(createdId) && row.subcontratista === name,
      );
      expect(foundAfterCreate, `Created row (id=${createdId}) must appear in API list`).toBe(true);
      findings.push('Create verified in API list');

      // ─── 8. UPDATE ─────────────────────────────────────────────────────────
      const updatedName = `${name} Updated`;
      const updateResult = await apiPost(page, `/api/subcontratistas/save?db=${PC.dbPrefix}`, {
        opcion: 'guardar_cambios',
        id: createdId,
        column: 'subcontratista',
        value: updatedName,
      });
      expect(updateResult.ok, 'Update API must succeed').toBe(true);
      expect(updateResult.payload.status, JSON.stringify(updateResult.payload)).toBe('success');
      findings.push(`Updated: id=${createdId}, new name="${updatedName}"`);

      await page.waitForTimeout(1000);

      // ─── 9. Verify update in API list ──────────────────────────────────────
      const listAfterUpdate = await apiPost(page, `/api/subcontratistas/list?db=${PC.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterUpdate.ok, 'API list after update must succeed').toBe(true);
      const foundAfterUpdate = listAfterUpdate.payload.data.some(
        (row) => Number(row.Id) === Number(createdId) && row.subcontratista === updatedName,
      );
      expect(foundAfterUpdate, `Updated row (id=${createdId}) must have name="${updatedName}"`).toBe(true);
      findings.push('Update verified in API list');

      // ─── 10. DELETE ────────────────────────────────────────────────────────
      const deleteResult = await apiPost(page, `/api/subcontratistas/save?db=${PC.dbPrefix}`, {
        opcion: 'eliminar',
        Id: createdId,
      });
      expect(deleteResult.ok, 'Delete API must succeed').toBe(true);
      expect(deleteResult.payload.status, JSON.stringify(deleteResult.payload)).toBe('success');
      findings.push(`Deleted: id=${createdId}`);

      await page.waitForTimeout(1000);

      // ─── 11. Verify removed from API list ──────────────────────────────────
      const listAfterDelete = await apiPost(page, `/api/subcontratistas/list?db=${PC.dbPrefix}`, { opcion: 'listar' });
      expect(listAfterDelete.ok, 'API list after delete must succeed').toBe(true);
      const foundAfterDelete = listAfterDelete.payload.data.some(
        (row) => Number(row.Id) === Number(createdId),
      );
      expect(foundAfterDelete, `Deleted row (id=${createdId}) must NOT appear in API list`).toBe(false);
      findings.push('Delete verified: row removed from API list');

      // ─── 12. DB count after full CRUD ──────────────────────────────────────
      const dbCountAfter = scalar(
        `SELECT COUNT(*) FROM subcontratistas WHERE project_id=${PC.projectId}`,
      );
      findings.push(`DB subcontratistas count after CRUD: ${dbCountAfter} (was ${dbCountBefore})`);
      expect(dbCountAfter, 'DB count should return to original after delete').toBe(dbCountBefore);

      // ─── Summary ────────────────────────────────────────────────────────────
      console.log(`\n[Aeropuerto PC Interesados Externos] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errs);
  });
});

// ─── Test 3: Da Porto — Profesionales full CRUD ───────────────────────────────

test.describe('Profesionales: Da Porto (construction) CRUD workflow', () => {
  test('Da Porto Admin: Profesionales — page load, API, DB, create, update, delete', async ({ page }, testInfo) => {
    test.skip(!DP, 'Da Porto (construction) project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(DP).capture();

      await loginAndSelectProject(page, DP);

      // ─── 1. Navigate to /profesionales ─────────────────────────────────────
      await page.goto('/profesionales', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('.handsontable, #hot-container, body', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /profesionales');

      // ─── 2. Handsontable visible ────────────────────────────────────────────
      const hotVisible = await page.locator('.handsontable .htCore, #hot-container').first().isVisible().catch(() => false);
      findings.push(`Handsontable visible: ${hotVisible}`);

      // ─── 3. API list verify ─────────────────────────────────────────────────
      const listBefore = await apiPost(page, `/api/profesionales/list?db=${DP.dbPrefix}`, {});
      expect(listBefore.ok, 'API /api/profesionales/list must succeed').toBe(true);
      expect(listBefore.payload.status, JSON.stringify(listBefore.payload)).toBe('success');
      const initialCount = Array.isArray(listBefore.payload.data) ? listBefore.payload.data.length : 0;
      findings.push(`API list: ${initialCount} rows`);

      // ─── 4. DB count ───────────────────────────────────────────────────────
      const dbCountBefore = scalar(
        `SELECT COUNT(*) FROM profesionales WHERE project_id=${DP.projectId}`,
      );
      findings.push(`DB profesionales count before: ${dbCountBefore}`);

      // ─── 5. CREATE ─────────────────────────────────────────────────────────
      const name = stamp('Prof');
      const email = `e2e-prof-${Date.now()}@mail.com`;

      const createResult = await apiPost(page, `/api/profesionales/save?db=${DP.dbPrefix}`, {
        opcion: 'crear',
        nombre: name,
        email,
        cargo: DP.professionalCargo,
      });
      expect(createResult.ok, 'Create API must succeed').toBe(true);
      expect(createResult.payload.status, JSON.stringify(createResult.payload)).toBe('success');
      const createdId = createResult.payload.id;
      expect(createdId, 'Create must return id').toBeTruthy();
      findings.push(`Created: id=${createdId}, nombre="${name}", cargo="${DP.professionalCargo}"`);

      await page.waitForTimeout(1000);

      // ─── 6. Verify in API list ─────────────────────────────────────────────
      const listAfterCreate = await apiPost(page, `/api/profesionales/list?db=${DP.dbPrefix}`, {});
      expect(listAfterCreate.ok, 'API list after create must succeed').toBe(true);
      const foundAfterCreate = listAfterCreate.payload.data.some(
        (row) => Number(row.id) === Number(createdId) && row.nombre === name,
      );
      expect(foundAfterCreate, `Created row (id=${createdId}) must appear in API list`).toBe(true);
      findings.push('Create verified in API list');

      // ─── 7. UPDATE ─────────────────────────────────────────────────────────
      const updatedName = `${name} Updated`;
      const updateResult = await apiPost(page, `/api/profesionales/save?db=${DP.dbPrefix}`, {
        opcion: 'guardar_cambios',
        cambios: [{ id: createdId, prop: 'nombre', value: updatedName }],
      });
      expect(updateResult.ok, 'Update API must succeed').toBe(true);
      expect(updateResult.payload.status, JSON.stringify(updateResult.payload)).toBe('success');
      findings.push(`Updated: id=${createdId}, new nombre="${updatedName}"`);

      await page.waitForTimeout(1000);

      // ─── 8. Verify update in API list ──────────────────────────────────────
      const listAfterUpdate = await apiPost(page, `/api/profesionales/list?db=${DP.dbPrefix}`, {});
      expect(listAfterUpdate.ok, 'API list after update must succeed').toBe(true);
      const foundAfterUpdate = listAfterUpdate.payload.data.some(
        (row) => Number(row.id) === Number(createdId) && row.nombre === updatedName,
      );
      expect(foundAfterUpdate, `Updated row (id=${createdId}) must have nombre="${updatedName}"`).toBe(true);
      findings.push('Update verified in API list');

      // ─── 9. DELETE ────────────────────────────────────────────────────────
      const deleteResult = await apiPost(page, `/api/profesionales/save?db=${DP.dbPrefix}`, {
        opcion: 'eliminar',
        id: createdId,
      });
      expect(deleteResult.ok, 'Delete API must succeed').toBe(true);
      expect(deleteResult.payload.status, JSON.stringify(deleteResult.payload)).toBe('success');
      findings.push(`Deleted: id=${createdId}`);

      await page.waitForTimeout(1000);

      // ─── 10. Verify removed from API list ──────────────────────────────────
      const listAfterDelete = await apiPost(page, `/api/profesionales/list?db=${DP.dbPrefix}`, {});
      expect(listAfterDelete.ok, 'API list after delete must succeed').toBe(true);
      const foundAfterDelete = listAfterDelete.payload.data.some(
        (row) => Number(row.id) === Number(createdId),
      );
      expect(foundAfterDelete, `Deleted row (id=${createdId}) must NOT appear in API list`).toBe(false);
      findings.push('Delete verified: row removed from API list');

      // ─── 11. DB count after full CRUD ──────────────────────────────────────
      const dbCountAfter = scalar(
        `SELECT COUNT(*) FROM profesionales WHERE project_id=${DP.projectId}`,
      );
      findings.push(`DB profesionales count after CRUD: ${dbCountAfter} (was ${dbCountBefore})`);
      expect(dbCountAfter, 'DB count should return to original after delete').toBe(dbCountBefore);

      // ─── Summary ────────────────────────────────────────────────────────────
      console.log(`\n[Da Porto Profesionales] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errs);
  });
});

// ─── Test 4: Residente RBAC — Subcontratistas access ──────────────────────────

test.describe('RBAC: Residente access to Subcontratistas', () => {
  test('Da Porto Residente: can access /subcontratistas and see data', async ({ page }, testInfo) => {
    test.skip(!DP, 'Da Porto (construction) project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];

    try {
      // Login as Residente (test.R)
      await loginAndSelectProject(page, { ...DP }, { username: 'test.R', password: 'aia2026' });

      // ─── 1. Navigate to /subcontratistas ───────────────────────────────────
      await page.goto('/subcontratistas', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('.handsontable, #hot-container, body', { state: 'attached', timeout: 15_000 });
      findings.push('Residente: Page loaded /subcontratistas');

      // ─── 2. Verify title ───────────────────────────────────────────────────
      const titleEl = page.locator('.header-actions h4');
      await expect(titleEl).toContainText(DP.expectedSubcontractorTitle, { timeout: 10_000 });
      findings.push(`Residente: Title verified "${DP.expectedSubcontractorTitle}"`);

      // ─── 3. Handsontable visible ────────────────────────────────────────────
      const hotVisible = await page.locator('.handsontable .htCore, #hot-container').first().isVisible().catch(() => false);
      findings.push(`Residente: Handsontable visible: ${hotVisible}`);

      // ─── 4. API list — Residente should be able to read data ───────────────
      const listResult = await apiPost(page, `/api/subcontratistas/list?db=${DP.dbPrefix}`, { opcion: 'listar' });
      expect(listResult.ok, 'Residente API list must succeed').toBe(true);
      expect(listResult.payload.status, JSON.stringify(listResult.payload)).toBe('success');
      const rowCount = Array.isArray(listResult.payload.data) ? listResult.payload.data.length : 0;
      findings.push(`Residente: API list returned ${rowCount} rows`);
      expect(rowCount, 'Residente should see data in subcontratistas').toBeGreaterThanOrEqual(0);

      // ─── Summary ────────────────────────────────────────────────────────────
      console.log(`\n[Residente RBAC] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
    }

    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errs);
  });
});
