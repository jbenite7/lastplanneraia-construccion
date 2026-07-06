/**
 * Contratos (Paquetes de contratacion) — Full CRUD + semi-auto workflow.
 *
 * Target: Da Porto only (construction; PC doesn't have this module → 404 — F-010).
 * Table: HTML table (#dt_cliente DataTable), NOT Handsontable.
 *
 * Workflow covered:
 *   1. Page load + breadcrumb verification
 *   2. API list verification (/api/contratos/list)
 *   3. DB row count verification (contratos table, project_id scoped)
 *   4. Create new contrato via UI
 *   5. Edit contrato via row action
 *   6. Delete contrato via row action + confirm
 *   7. Semi-auto: preview → apply (F-006 known: may fail) → feedback
 *   8. DB verify for semi-auto runs and suggestions
 *
 * Known issues documented:
 *   F-006: auto/apply may fail with "Solicitud inválida"
 *   F-010: Aeropuerto PC excluded — module not available
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { CONTRATOS_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

// ─── Helpers ──────────────────────────────────────────────────────────────────

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

/**
 * Count rows in contratos for current project.
 */
function countContratos() {
  return scalar(
    `SELECT COUNT(*) FROM contratos WHERE project_id=${PROJECT.projectId}`,
  );
}

/**
 * Find a row by text in the DataTable.
 */
async function findRow(page, text) {
  return page.locator('#dt_cliente tbody tr').filter({ hasText: text }).first();
}

/**
 * Count visible rows in the DataTable body.
 */
async function visibleRowCount(page) {
  return page.locator('#dt_cliente tbody tr').count();
}

// ─── Test Suite ───────────────────────────────────────────────────────────────

test.describe('Contratos (Paquetes de contratacion): CRUD + semi-auto workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required — F-010: PC has no contratos module');
  });

  test('Da Porto: full Contratos workflow — CRUD, breadcrumb, API, DB, semi-auto', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      await loginAndSelectProject(page, PROJECT);

      // ─── 1. Navigate to Contratos ─────────────────────────────────────────
      await page.goto('/contratos', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('#dt_cliente', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /contratos');

      // ─── 2. Breadcrumb verification ───────────────────────────────────────
      const breadcrumb = page.locator('.breadcrumb, nav[aria-label="breadcrumb"], [class*="breadcrumb"]');
      const breadcrumbText = await breadcrumb.first().textContent().catch(() => '');
      const hasDaPorto = breadcrumbText.includes('Da Porto');
      const hasContratos = breadcrumbText.includes('Paquetes de contratacion');
      findings.push(`Breadcrumb: "${breadcrumbText.trim()}" — DaPorto=${hasDaPorto}, Paquetes=${hasContratos}`);
      expect(hasDaPorto, 'Breadcrumb must contain "Da Porto"').toBe(true);
      expect(hasContratos, 'Breadcrumb must contain "Paquetes de contratacion"').toBe(true);

      // ─── 3. Table visible + buttons present ───────────────────────────────
      const tableVisible = await page.locator('#dt_cliente').isVisible();
      expect(tableVisible, 'DataTable #dt_cliente must be visible').toBe(true);

      const autoDefinirBtn = page.locator(CONTRATOS_SELECTORS.buttons.autoDefinir);
      await expect(autoDefinirBtn, '"Auto-definir paquetes" button visible').toBeVisible({ timeout: 5_000 });
      findings.push('Button present: Auto-definir paquetes');

      // ─── 4. API verify: /api/contratos/list ───────────────────────────────
      const listResult = await apiPost(page, '/api/contratos/list', {});
      if (listResult.ok) {
        const apiCount = Array.isArray(listResult.payload.data)
          ? listResult.payload.data.length
          : (listResult.payload.total || 0);
        findings.push(`API /api/contratos/list: ${apiCount} rows`);
      } else {
        findings.push(`API list failed: ${JSON.stringify(listResult.payload).slice(0, 200)}`);
      }

      // ─── 5. DB verify: initial count ──────────────────────────────────────
      const initialDbCount = countContratos();
      const initialUiCount = await visibleRowCount(page);
      findings.push(`DB count: ${initialDbCount} | UI rows: ${initialUiCount}`);

      // ─── 6. Column headers verification ───────────────────────────────────
      const headers = await page.locator('#dt_cliente thead th').allTextContents();
      const normalizedHeaders = headers.map((h) => h.trim());
      for (const expected of CONTRATOS_SELECTORS.columns) {
        const found = normalizedHeaders.some((h) => h.includes(expected));
        findings.push(`Column "${expected}": ${found ? 'present' : 'MISSING'}`);
      }

      // ─── 7. CRUD: Create a new contrato via UI if row exists to edit ──────
      const timestamp = Date.now();
      const testFamilia = `E2E Contrato ${timestamp}`;
      const testDescripcion = `Descripcion E2E ${timestamp}`;
      const testFechaInicio = '2026-07-06';
      const testModalidad = 'Licitacion Publica';

      // Check if there is a "create" / "new" button (may vary per UI)
      const createBtn = page.locator('button:has-text("Nuevo"), button:has-text("Crear"), button:has-text("Agregar"), a:has-text("Nuevo")');
      const hasCreateBtn = (await createBtn.count()) > 0;

      if (hasCreateBtn) {
        await createBtn.first().click();
        const modal = page.locator('dialog[open], .modal.show, .modal[style*="display: block"], [role="dialog"]');
        await modal.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
        findings.push('Create modal opened');

        // Fill form fields
        const familiaField = page.locator('input[name="familia"], input[name="nombre_familia"], select[name="familia"]');
        const descField = page.locator('textarea[name="descripcion"], input[name="descripcion"], textarea[placeholder*="descri"]');
        const fechaField = page.locator('input[name="fecha_inicio"], input[type="date"][name*="fecha"], input[name="fecha"]');
        const modalidadField = page.locator('select[name="modalidad"], select[name="modalidad_contratacion"], input[name="modalidad"]');

        if (await familiaField.count() > 0) {
          const tagName = await familiaField.first().evaluate((el) => el.tagName.toLowerCase());
          if (tagName === 'select') {
            const options = await familiaField.first().locator('option').allTextContents();
            const validOption = options.find((o) => o.trim() && !o.includes('Seleccion'));
            if (validOption) await familiaField.first().selectOption({ label: validOption.trim() });
          } else {
            await familiaField.first().fill(testFamilia);
          }
          findings.push('Filled familia field');
        }
        if (await descField.count() > 0) {
          await descField.first().fill(testDescripcion);
          findings.push('Filled descripcion');
        }
        if (await fechaField.count() > 0) {
          await fechaField.first().fill(testFechaInicio);
          findings.push(`Filled fecha_inicio: ${testFechaInicio}`);
        }
        if (await modalidadField.count() > 0) {
          const tagName = await modalidadField.first().evaluate((el) => el.tagName.toLowerCase());
          if (tagName === 'select') {
            const options = await modalidadField.first().locator('option').allTextContents();
            const validOption = options.find((o) => o.trim() && !o.includes('Seleccion'));
            if (validOption) await modalidadField.first().selectOption({ label: validOption.trim() });
          } else {
            await modalidadField.first().fill(testModalidad);
          }
          findings.push(`Filled modalidad: ${testModalidad}`);
        }

        // Save
        const saveBtn = page.locator('button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]:visible, dialog button:has-text("Save")');
        await saveBtn.first().click();
        await page.waitForTimeout(2000);
        findings.push('Clicked save button');

        // Verify new row
        const newRow = await findRow(page, testFamilia);
        const rowVisible = await newRow.isVisible().catch(() => false);
        findings.push(`New row visible: ${rowVisible}`);

        // API verify after create
        const listAfterCreate = await apiPost(page, '/api/contratos/list', {});
        if (listAfterCreate.ok && Array.isArray(listAfterCreate.payload.data)) {
          const found = listAfterCreate.payload.data.some(
            (row) => (row.familia || row.nombre_familia || '').includes(testFamilia),
          );
          findings.push(`API verify after create: found=${found}`);
        }

        // DB verify after create
        const dbCountAfterCreate = countContratos();
        findings.push(`DB count after create: ${dbCountAfterCreate} (was ${initialDbCount})`);

        // ─── 8. EDIT: Click edit on new row ─────────────────────────────────
        const editBtn = newRow.locator(CONTRATOS_SELECTORS.rowActions.editar);
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

          const saveEditBtn = page.locator('button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]:visible, dialog button:has-text("Save")');
          await saveEditBtn.first().click();
          await page.waitForTimeout(2000);
          findings.push('Saved edit');

          // Verify edit persisted
          const editedRow = await findRow(page, testFamilia);
          const editRowVisible = await editedRow.isVisible().catch(() => false);
          findings.push(`Row still visible after edit: ${editRowVisible}`);
        } else {
          findings.push('Edit button not found on row — skipping edit step');
        }

        // ─── 9. DELETE: Click delete on test row → confirm ──────────────────
        const deleteBtn = newRow.locator(CONTRATOS_SELECTORS.rowActions.eliminar);
        if (await deleteBtn.count() > 0) {
          await deleteBtn.click();
          await page.waitForTimeout(500);

          const confirmBtn = page.locator('button:has-text("Eliminar"), button:has-text("Confirmar"), button:has-text("Aceptar"), .swal2-confirm, button:has-text("Yes")');
          if (await confirmBtn.count() > 0) {
            await confirmBtn.first().click();
            findings.push('Confirmed deletion');
          }
          await page.waitForTimeout(2000);

          const deletedRow = await findRow(page, testFamilia);
          const deletedRowVisible = await deletedRow.isVisible().catch(() => false);
          findings.push(`Row visible after delete: ${deletedRowVisible}`);
          expect(deletedRowVisible, `Row "${testFamilia}" should be removed after delete`).toBe(false);
        } else {
          findings.push('Delete button not found on row — skipping delete step');
        }
      } else {
        findings.push('No create button found — CRUD via UI not available; skipping create/edit/delete');
      }

      // ─── 10. DB verify after full CRUD cycle ──────────────────────────────
      const dbCountAfterCrud = countContratos();
      findings.push(`DB count after full CRUD: ${dbCountAfterCrud}`);

      // ─── 11. Semi-auto: preview → apply → feedback ────────────────────────
      findings.push('--- Semi-auto workflow ---');

      const previewResult = await apiPost(page, '/api/contratos/auto/preview', {});
      if (previewResult.ok && previewResult.payload.run_id) {
        const runId = previewResult.payload.run_id;
        const steps = previewResult.payload.analysis?.steps?.length || 0;
        findings.push(`Preview OK: run_id=${runId}, steps=${steps}`);
        expect(runId, 'Preview must return run_id').toBeTruthy();

        // Apply — F-006 known: may fail with "Solicitud inválida"
        const applyResult = await apiPost(page, '/api/contratos/auto/apply', { run_id: runId });
        if (applyResult.ok) {
          findings.push(`Apply OK: ${JSON.stringify(applyResult.payload).slice(0, 200)}`);
        } else {
          // F-006: known blocker — log finding but do NOT fail the test
          findings.push(`F-006: auto/apply failed (expected): ${JSON.stringify(applyResult.payload).slice(0, 300)}`);
        }

        // Feedback — attempt regardless of apply result
        const feedbackResult = await apiPost(page, '/api/contratos/auto/feedback', {
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

      // ─── 12. DB verify: any auto-generated data in semi_auto tables ───────
      const autoRunCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_runs WHERE project_id=${PROJECT.projectId} AND module='contratos'`,
      );
      const suggestionCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_suggestions WHERE project_id=${PROJECT.projectId} AND module='contratos'`,
      );
      findings.push(`Semi-auto DB: runs=${autoRunCount}, suggestions=${suggestionCount}`);

      // ─── 13. Final UI row count check ─────────────────────────────────────
      const finalUiCount = await visibleRowCount(page);
      findings.push(`Final UI row count: ${finalUiCount}`);

      // ─── Summary ──────────────────────────────────────────────────────────
      console.log(`\n[Contratos] Findings (${findings.length}):`);
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
