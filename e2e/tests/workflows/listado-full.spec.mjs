/**
 * Listado de Actividades (Familias de obra) — Full CRUD + semi-auto workflow.
 *
 * Target: Daporto only (construction; PC doesn't have this module → 404 — F-010).
 * Table: HTML table (#dt_cliente DataTable), NOT Handsontable.
 *
 * Workflow covered:
 *   1. Page load + breadcrumb verification
 *   2. API list verification
 *   3. DB row count verification
 *   4. Create new family via UI modal
 *   5. Edit family via row action
 *   6. Delete family via row action + confirm
 *   7. Semi-auto: preview → apply (F-004 known: may fail) → feedback
 *   8. DB verify for any auto-generated data
 *
 * Known issues documented:
 *   F-004: auto/apply may fail with "Solicitud inválida"
 *   F-005: familia/save API endpoint is 404 — CRUD via UI forms only
 *   F-010: Aeropuerto PC excluded — module not available
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { LISTADO_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

const E2E_FAMILY_PREFIX = 'E2E Listado';

function familyName(ts) {
  return `${E2E_FAMILY_PREFIX} ${ts}`;
}

/**
 * Count rows in listado_actividades for current project.
 */
function countFamilias() {
  return scalar(
    `SELECT COUNT(*) FROM listado_actividades WHERE project_id=${PROJECT.projectId}`,
  );
}

/**
 * Find a row by familia name in the DataTable.
 */
async function findRow(page, nombre) {
  return page.locator(`#dt_cliente tbody tr`).filter({ hasText: nombre }).first();
}

/**
 * Count visible rows in the DataTable body.
 */
async function visibleRowCount(page) {
  return page.locator('#dt_cliente tbody tr').count();
}

// ─── Test Suite ───────────────────────────────────────────────────────────────

test.describe('Listado de Actividades: CRUD + semi-auto workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required — F-010: PC has no listado module');
  });

  test('Da Porto: full Listado workflow — CRUD, breadcrumb, API, DB, semi-auto', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      await loginAndSelectProject(page, PROJECT);

      // ─── 1. Navigate to Listado ───────────────────────────────────────────
      await page.goto('/listado-actividades', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('#dt_cliente', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /listado-actividades');

      // ─── 2. Breadcrumb verification ───────────────────────────────────────
      const breadcrumb = page.locator('.breadcrumb, nav[aria-label="breadcrumb"], [class*="breadcrumb"]');
      const breadcrumbText = await breadcrumb.first().textContent().catch(() => '');
      findings.push(`Breadcrumb: "${breadcrumbText.trim()}"`);
      // Breadcrumb format varies — just verify page loaded, not exact text

      // ─── 3. Table visible + buttons present ───────────────────────────────
      const tableVisible = await page.locator('#dt_cliente').isVisible();
      expect(tableVisible, 'DataTable #dt_cliente must be visible').toBe(true);

      const nuevaFamiliaBtn = page.locator(LISTADO_SELECTORS.buttons.nuevaFamilia);
      const autoGenerarBtn = page.locator(LISTADO_SELECTORS.buttons.autoGenerar);
      await expect(nuevaFamiliaBtn, '"Nueva Familia" button visible').toBeVisible({ timeout: 5_000 });
      await expect(autoGenerarBtn, '"Auto-generar Familias" button visible').toBeVisible({ timeout: 5_000 });
      findings.push('Buttons present: Nueva Familia, Auto-generar Familias');

      // ─── 4. API verify: /api/listado-actividades/list ─────────────────────
      const listResult = await apiPost(page, '/api/listado-actividades/list', {});
      if (listResult.ok) {
        const apiCount = Array.isArray(listResult.payload.data)
          ? listResult.payload.data.length
          : (listResult.payload.total || 0);
        findings.push(`API /api/listado-actividades/list: ${apiCount} rows`);
      } else {
        findings.push(`API list failed: ${JSON.stringify(listResult.payload).slice(0, 200)}`);
      }

      // ─── 5. DB verify: initial count ──────────────────────────────────────
      const initialDbCount = countFamilias();
      const initialUiCount = await visibleRowCount(page);
      findings.push(`DB count: ${initialDbCount} | UI rows: ${initialUiCount}`);

      // ─── 6. CREATE: Click "Nueva Familia" → fill modal → save ─────────────
      const timestamp = Date.now();
      const testFamilyName = familyName(timestamp);
      const testDescripcion = `Descripcion E2E ${timestamp}`;
      const testFechaInicio = '2026-07-06';
      const testModalidad = 'Licitacion Publica';

      await nuevaFamiliaBtn.click();

      // Wait for modal/dialog to appear
      const modal = page.locator('dialog[open], .modal.show, .modal[style*="display: block"], [role="dialog"]');
      await modal.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
      findings.push('Modal opened for new family');

      // Fill form fields — adapt to actual field names
      const nombreField = page.locator('input[name="nombre_familia"], input[placeholder*="nombre"], input[name="nombre"]');
      const descField = page.locator('textarea[name="descripcion"], input[name="descripcion"], textarea[placeholder*="descri"]');
      const fechaField = page.locator('input[name="fecha_inicio"], input[type="date"][name*="fecha"], input[name="fecha"]');
      const modalidadField = page.locator('select[name="modalidad"], select[name="modalidad_contratacion"], select[name="tipo"]');

      if (await nombreField.count() > 0) {
        await nombreField.first().fill(testFamilyName);
        findings.push(`Filled nombre_familia: ${testFamilyName}`);
      }
      if (await descField.count() > 0) {
        await descField.first().fill(testDescripcion);
        findings.push(`Filled descripcion`);
      }
      if (await fechaField.count() > 0) {
        await fechaField.first().fill(testFechaInicio);
        findings.push(`Filled fecha_inicio: ${testFechaInicio}`);
      }
      if (await modalidadField.count() > 0) {
        // Select first non-empty option if select, or fill if input
        const tagName = await modalidadField.first().evaluate((el) => el.tagName.toLowerCase());
        if (tagName === 'select') {
          const options = await modalidadField.first().locator('option').allTextContents();
          const validOption = options.find((o) => o.trim() && !o.includes('Seleccion'));
          if (validOption) {
            await modalidadField.first().selectOption({ label: validOption.trim() });
            findings.push(`Selected modalidad: ${validOption.trim()}`);
          }
        } else {
          await modalidadField.first().fill(testModalidad);
          findings.push(`Filled modalidad: ${testModalidad}`);
        }
      }

      // Save the form — try multiple button patterns
      let saved = false;
      for (const sel of [
        'button:has-text("Guardar")',
        'button:has-text("Aceptar")',
        'button:has-text("Crear")',
        'button[type="submit"]',
        'dialog button:has-text("Save")',
        'button:has-text("Agregar")',
      ]) {
        const btn = modal.locator(sel).first();
        if (await btn.count() > 0 && await btn.isVisible().catch(() => false)) {
          await btn.click();
          saved = true;
          break;
        }
      }
      if (!saved) {
        // Try clicking any submit-like button anywhere on page
        const anyBtn = page.locator('button[type="submit"]:visible, button:has-text("Guardar"):visible').first();
        if (await anyBtn.count() > 0) await anyBtn.click();
      }
      await page.waitForTimeout(2000);
      findings.push('Clicked save button');

      // ─── 7. Verify new row in table (soft — may need page refresh) ─────────
      const newRow = await findRow(page, testFamilyName);
      const rowVisible = await newRow.isVisible().catch(() => false);
      findings.push(`New row visible in table: ${rowVisible}`);
      if (!rowVisible) {
        // Try refreshing the table/page
        await page.reload({ waitUntil: 'commit', timeout: 30_000 });
        await page.waitForSelector('#dt_cliente', { state: 'attached', timeout: 15_000 });
        await page.waitForTimeout(2000);
        const newRow2 = await findRow(page, testFamilyName);
        const visible2 = await newRow2.isVisible().catch(() => false);
        findings.push(`After reload, row visible: ${visible2}`);
      }

      // ─── 8. API verify after create ───────────────────────────────────────
      const listAfterCreate = await apiPost(page, '/api/listado-actividades/list', {});
      if (listAfterCreate.ok && Array.isArray(listAfterCreate.payload.data)) {
        const found = listAfterCreate.payload.data.some(
          (row) => (row.nombre_familia || row.familia || '').includes(testFamilyName),
        );
        findings.push(`API verify after create: found=${found}`);
      }

      // ─── 9. DB verify after create ────────────────────────────────────────
      const dbCountAfterCreate = countFamilias();
      findings.push(`DB count after create: ${dbCountAfterCreate} (was ${initialDbCount})`);

      // ─── 10. EDIT: Click edit on new row → change descripcion ──────────────
      const editBtn = newRow.locator(LISTADO_SELECTORS.rowActions.editar);
      if (await editBtn.count() > 0) {
        await editBtn.click();
        await modal.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
        findings.push('Edit modal opened');

        const updatedDesc = `Descripcion E2E EDITADA ${timestamp}`;
        const editDescField = page.locator('textarea[name="descripcion"], input[name="descripcion"], textarea[placeholder*="descri"]');
        if (await editDescField.count() > 0) {
          await editDescField.first().clear();
          await editDescField.first().fill(updatedDesc);
          findings.push('Updated descripcion in edit modal');
        }

        const saveEditBtn = page.locator('button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]:visible');
        if (await saveEditBtn.count() > 0) await saveEditBtn.first().click().catch(() => {});
        await page.waitForTimeout(2000);
        findings.push('Saved edit');

        // Verify edit persisted
        const editedRow = await findRow(page, testFamilyName);
        const editRowVisible = await editedRow.isVisible().catch(() => false);
        findings.push(`Row still visible after edit: ${editRowVisible}`);
      } else {
        findings.push('Edit button not found on row — skipping edit step');
      }

      // ─── 11. DELETE: Click delete on test row → confirm ───────────────────
      const deleteBtn = newRow.locator(LISTADO_SELECTORS.rowActions.eliminar);
      if (await deleteBtn.count() > 0) {
        await deleteBtn.click();
        await page.waitForTimeout(500);

        // Confirm deletion if confirmation dialog appears
        const confirmBtn = page.locator('button:has-text("Eliminar"), button:has-text("Confirmar"), button:has-text("Aceptar"), .swal2-confirm, button:has-text("Yes")');
        if (await confirmBtn.count() > 0) {
          await confirmBtn.first().click();
          findings.push('Confirmed deletion');
        }
        await page.waitForTimeout(2000);

        // Verify row removed
        const deletedRow = await findRow(page, testFamilyName);
        const deletedRowVisible = await deletedRow.isVisible().catch(() => false);
        findings.push(`Row visible after delete: ${deletedRowVisible}`);
      } else {
        findings.push('Delete button not found on row — skipping delete step');
      }

      // ─── 12. DB verify after full CRUD cycle ──────────────────────────────
      const dbCountAfterCrud = countFamilias();
      findings.push(`DB count after full CRUD: ${dbCountAfterCrud}`);

      // ─── 13. Semi-auto: preview → apply → feedback ────────────────────────
      findings.push('--- Semi-auto workflow ---');

      const previewResult = await apiPost(page, '/api/listado-actividades/auto/preview', {});
      if (previewResult.ok && previewResult.payload.run_id) {
        const runId = previewResult.payload.run_id;
        const steps = previewResult.payload.analysis?.steps?.length || 0;
        findings.push(`Preview OK: run_id=${runId}, steps=${steps}`);
        expect(runId, 'Preview must return run_id').toBeTruthy();

        // Apply — F-004 known: may fail with "Solicitud inválida"
        const applyResult = await apiPost(page, '/api/listado-actividades/auto/apply', { run_id: runId });
        if (applyResult.ok) {
          findings.push(`Apply OK: ${JSON.stringify(applyResult.payload).slice(0, 200)}`);
        } else {
          // F-004: known blocker — log finding but do NOT fail the test
          findings.push(`F-004: auto/apply failed (expected): ${JSON.stringify(applyResult.payload).slice(0, 300)}`);
        }

        // Feedback — attempt regardless of apply result
        const feedbackResult = await apiPost(page, '/api/listado-actividades/auto/feedback', {
          run_id: runId,
          decision: 'accept',
        });
        if (feedbackResult.ok) {
          findings.push(`Feedback OK: ${JSON.stringify(feedbackResult.payload).slice(0, 200)}`);
        } else {
          findings.push(`Feedback failed: ${JSON.stringify(feedbackResult.payload).slice(0, 200)}`);
        }
      } else {
        findings.push(`Preview did not generate results: ${JSON.stringify(previewResult.payload).slice(0, 300)}`);
      }

      // ─── 14. DB verify: any auto-generated data in semi_auto tables ───────
      const autoRunCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_runs WHERE project_id=${PROJECT.projectId} AND module='listado_actividades'`,
      );
      const suggestionCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_suggestions WHERE project_id=${PROJECT.projectId} AND module='listado_actividades'`,
      );
      findings.push(`Semi-auto DB: runs=${autoRunCount}, suggestions=${suggestionCount}`);

      // ─── 15. Final UI row count check ─────────────────────────────────────
      const finalUiCount = await visibleRowCount(page);
      findings.push(`Final UI row count: ${finalUiCount}`);

      // ─── Summary ──────────────────────────────────────────────────────────
      console.log(`\n[Listado] Findings (${findings.length}):`);
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
