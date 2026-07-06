/**
 * PDC (Plan de Compras y Contrataciones) — Full CRUD + tab navigation + chips/filters + semi-auto.
 *
 * Target: Da Porto only (construction; PC doesn't have PDC → 404 — F-010).
 * Table: HTML table (#dt_cliente DataTable).
 *
 * Workflow covered:
 *   1. Page load + breadcrumb + table + API + DB verification
 *   2. Chips / filter verification + chip click + reset
 *   3. Tab navigation: Familias de obra → Paquetes de contratacion → Plan de Compras
 *   4. CRUD: create → edit → delete (if create button present)
 *   5. Semi-auto: preview → apply (F-007 known: may fail) → feedback
 *   6. Button verification: Actualizar, Desglosar
 *
 * Known issues documented:
 *   F-007: auto/apply may fail with "Solicitud inválida"
 *   F-008: PDC has ~15 real rows in DB for Da Porto
 *   F-010: Aeropuerto PC excluded — module not available
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { PDC_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

const E2E_PDC_PREFIX = 'E2E PDC';

function paqueteName(ts) {
  return `${E2E_PDC_PREFIX} ${ts}`;
}

/**
 * Count rows in pdc for current project.
 */
function countPdcRows() {
  return scalar(
    `SELECT COUNT(*) FROM pdc WHERE project_id=${PROJECT.projectId}`,
  );
}

/**
 * Find a row by paquete name in the DataTable.
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

test.describe('PDC: CRUD + tab navigation + chips/filters + semi-auto workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required — F-010: PC has no PDC module');
  });

  test('Da Porto: full PDC workflow — table, chips, tabs, CRUD, semi-auto', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      await loginAndSelectProject(page, PROJECT);

      // ══════════════════════════════════════════════════════════════════════════
      // 1. NAVIGATE TO PDC + BREADCRUMB
      // ══════════════════════════════════════════════════════════════════════════
      await page.goto('/pdc', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('#dt_cliente', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /pdc');

      // ─── 1a. Breadcrumb verification ────────────────────────────────────────
      const breadcrumb = page.locator('.breadcrumb, nav[aria-label="breadcrumb"], [class*="breadcrumb"]');
      const breadcrumbText = await breadcrumb.first().textContent().catch(() => '');
      const hasDaPorto = breadcrumbText.includes('Da Porto');
      const hasPlanCompras = breadcrumbText.includes('Plan de Compras');
      findings.push(`Breadcrumb: "${breadcrumbText.trim()}" — DaPorto=${hasDaPorto}, PlanDeCompras=${hasPlanCompras}`);
      expect(hasDaPorto, 'Breadcrumb must contain "Da Porto"').toBe(true);
      expect(hasPlanCompras, 'Breadcrumb must contain "Plan de Compras"').toBe(true);

      // ─── 1b. Table visible ──────────────────────────────────────────────────
      const tableVisible = await page.locator('#dt_cliente').isVisible();
      expect(tableVisible, 'DataTable #dt_cliente must be visible').toBe(true);
      const initialUiCount = await visibleRowCount(page);
      findings.push(`UI table visible: ${initialUiCount} rows`);

      // ─── 1c. API verify: /api/pdc/list ──────────────────────────────────────
      const listResult = await apiPost(page, '/api/pdc/list', {});
      if (listResult.ok) {
        const apiCount = Array.isArray(listResult.payload.data)
          ? listResult.payload.data.length
          : (listResult.payload.total || 0);
        findings.push(`API /api/pdc/list: ${apiCount} rows`);
      } else {
        findings.push(`API list failed: ${JSON.stringify(listResult.payload).slice(0, 200)}`);
      }

      // ─── 1d. DB verify: pdc table row count ────────────────────────────────
      const dbCount = countPdcRows();
      findings.push(`DB pdc count: ${dbCount} (F-008: expect ~15 real rows)`);
      expect(dbCount, 'PDC table should have rows (F-008)').toBeGreaterThan(0);

      // ══════════════════════════════════════════════════════════════════════════
      // 2. CHIPS / FILTERS
      // ══════════════════════════════════════════════════════════════════════════
      findings.push('--- Chips / Filters ---');

      // Verify chip elements are present
      const chipContainer = page.locator('.chip, .badge, [class*="chip"], [class*="filter"]');
      const chipCount = await chipContainer.count();
      findings.push(`Chip/badge elements found: ${chipCount}`);

      // Verify known chip labels exist in the page
      for (const chipLabel of PDC_SELECTORS.chips) {
        const chipLocator = page.locator(`button:has-text("${chipLabel}"), .chip:has-text("${chipLabel}"), .badge:has-text("${chipLabel}"), span:has-text("${chipLabel}"), a:has-text("${chipLabel}")`);
        const chipVisible = await chipLocator.first().isVisible().catch(() => false);
        if (chipVisible) {
          findings.push(`Chip "${chipLabel}": visible`);
        } else {
          findings.push(`Chip "${chipLabel}": not visible`);
        }
      }

      // Click a chip to filter (try "Contratacion en curso" which should have rows)
      const filterChip = page.locator(
        'button:has-text("Contratacion en curso"), .chip:has-text("Contratacion en curso"), .badge:has-text("Contratacion en curso"), a:has-text("Contratacion en curso")'
      ).first();
      if (await filterChip.isVisible().catch(() => false)) {
        await filterChip.click();
        await page.waitForTimeout(1500);
        const filteredCount = await visibleRowCount(page);
        findings.push(`After chip filter "Contratacion en curso": ${filteredCount} rows`);
        expect(filteredCount, 'Filtered table should have rows').toBeGreaterThan(0);

        // Reset filter — click the same chip again or look for a reset/clear button
        const resetBtn = page.locator(
          'button:has-text("Todos"), button:has-text("Limpiar"), button:has-text("Reset"), button:has-text("Clear"), .chip.active:has-text("Contratacion en curso")'
        ).first();
        if (await resetBtn.isVisible().catch(() => false)) {
          await resetBtn.click();
          await page.waitForTimeout(1500);
          const resetCount = await visibleRowCount(page);
          findings.push(`After filter reset: ${resetCount} rows`);
        } else {
          // Click chip again to toggle off
          await filterChip.click();
          await page.waitForTimeout(1500);
          const resetCount = await visibleRowCount(page);
          findings.push(`After chip toggle-off: ${resetCount} rows`);
        }
      } else {
        findings.push('Chip "Contratacion en curso" not found — skipping filter test');
      }

      // ══════════════════════════════════════════════════════════════════════════
      // 3. TAB NAVIGATION
      // ══════════════════════════════════════════════════════════════════════════
      findings.push('--- Tab Navigation ---');

      // Click "Familias de obra" tab
      const tabFamilias = page.locator(PDC_SELECTORS.tabs.familias);
      if (await tabFamilias.isVisible().catch(() => false)) {
        await tabFamilias.click();
        await page.waitForTimeout(1500);
        const familiasTableVisible = await page.locator('#dt_cliente').isVisible().catch(() => false);
        findings.push(`Tab "Familias de obra" clicked — table visible: ${familiasTableVisible}`);
      } else {
        findings.push('Tab "Familias de obra" not found');
      }

      // Click "Paquetes de contratacion" tab
      const tabPaquetes = page.locator(PDC_SELECTORS.tabs.paquetes);
      if (await tabPaquetes.isVisible().catch(() => false)) {
        await tabPaquetes.click();
        await page.waitForTimeout(1500);
        const paquetesTableVisible = await page.locator('#dt_cliente').isVisible().catch(() => false);
        findings.push(`Tab "Paquetes de contratacion" clicked — table visible: ${paquetesTableVisible}`);
      } else {
        findings.push('Tab "Paquetes de contratacion" not found');
      }

      // Click "Plan de Compras" tab (back to main)
      const tabPlanCompras = page.locator(PDC_SELECTORS.tabs.planCompras);
      if (await tabPlanCompras.isVisible().catch(() => false)) {
        await tabPlanCompras.click();
        await page.waitForTimeout(1500);
        const planTableVisible = await page.locator('#dt_cliente').isVisible().catch(() => false);
        findings.push(`Tab "Plan de Compras" clicked — table visible: ${planTableVisible}`);
        expect(planTableVisible, 'PDC table must be visible after returning to Plan de Compras tab').toBe(true);
      } else {
        findings.push('Tab "Plan de Compras" not found');
      }

      // ══════════════════════════════════════════════════════════════════════════
      // 4. CRUD (create → edit → delete)
      // ══════════════════════════════════════════════════════════════════════════
      findings.push('--- CRUD ---');

      const timestamp = Date.now();
      const testPaquete = paqueteName(timestamp);

      // Look for create / add button
      const createBtn = page.locator(
        'button:has-text("Nuevo"), button:has-text("Agregar"), button:has-text("Crear"), button:has-text("Adicionar")'
      ).first();
      const createBtnVisible = await createBtn.isVisible().catch(() => false);

      if (createBtnVisible) {
        await createBtn.click();
        await page.waitForTimeout(1500);

        // Wait for modal/dialog
        const modal = page.locator('dialog[open], .modal.show, .modal[style*="display: block"], [role="dialog"]');
        await modal.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
        findings.push('Create modal opened');

        // Fill form fields — PDC-specific
        const paqueteField = page.locator(
          'input[name*="paquete"], input[name*="contratacion"], textarea[name*="paquete"], input[placeholder*="paquete"]'
        );
        const modalidadField = page.locator(
          'select[name*="modalidad"], input[name*="modalidad"], select[name*="tipo_contratacion"]'
        );
        const familiasField = page.locator(
          'input[name*="familia"], textarea[name*="familia"], select[name*="familia"]'
        );

        if (await paqueteField.count() > 0) {
          await paqueteField.first().fill(testPaquete);
          findings.push(`Filled paquete: ${testPaquete}`);
        }
        if (await modalidadField.count() > 0) {
          const tagName = await modalidadField.first().evaluate((el) => el.tagName.toLowerCase());
          if (tagName === 'select') {
            const options = await modalidadField.first().locator('option').allTextContents();
            const validOption = options.find((o) => o.trim() && !o.includes('Seleccion'));
            if (validOption) {
              await modalidadField.first().selectOption({ label: validOption.trim() });
              findings.push(`Selected modalidad: ${validOption.trim()}`);
            }
          } else {
            await modalidadField.first().fill('Licitacion Publica');
            findings.push('Filled modalidad: Licitacion Publica');
          }
        }
        if (await familiasField.count() > 0) {
          const tagName = await familiasField.first().evaluate((el) => el.tagName.toLowerCase());
          if (tagName !== 'select') {
            await familiasField.first().fill('E2E Test Familia');
          }
          findings.push('Filled familias asociadas');
        }

        // Save
        const saveBtn = page.locator(
          'button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]:visible'
        );
        await saveBtn.first().click();
        await page.waitForTimeout(2000);
        findings.push('Clicked save button');

        // Verify new row in table
        const newRow = await findRow(page, testPaquete);
        const rowVisible = await newRow.isVisible().catch(() => false);
        findings.push(`New row visible in table: ${rowVisible}`);

        if (rowVisible) {
          // ─── Edit ─────────────────────────────────────────────────────────
          const editBtn = newRow.locator(PDC_SELECTORS.rowActions.editar);
          if (await editBtn.count() > 0) {
            await editBtn.click();
            await modal.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
            findings.push('Edit modal opened');

            const editField = page.locator(
              'input[name*="paquete"], input[name*="contratacion"], textarea[name*="paquete"]'
            );
            if (await editField.count() > 0) {
              await editField.first().clear();
              await editField.first().fill(`${testPaquete} EDITADO`);
              findings.push('Updated paquete name in edit modal');
            }

            const saveEditBtn = page.locator(
              'button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]:visible'
            );
            await saveEditBtn.first().click();
            await page.waitForTimeout(2000);
            findings.push('Saved edit');

            const editedRow = await findRow(page, testPaquete);
            const editRowVisible = await editedRow.isVisible().catch(() => false);
            findings.push(`Row still visible after edit: ${editRowVisible}`);
          } else {
            findings.push('Edit button not found on row');
          }

          // ─── Delete ──────────────────────────────────────────────────────
          const deleteBtn = newRow.locator(PDC_SELECTORS.rowActions.eliminar);
          if (await deleteBtn.count() > 0) {
            await deleteBtn.click();
            await page.waitForTimeout(500);

            const confirmBtn = page.locator(
              'button:has-text("Eliminar"), button:has-text("Confirmar"), button:has-text("Aceptar"), .swal2-confirm, button:has-text("Yes")'
            );
            if (await confirmBtn.count() > 0) {
              await confirmBtn.first().click();
              findings.push('Confirmed deletion');
            }
            await page.waitForTimeout(2000);

            const deletedRow = await findRow(page, testPaquete);
            const deletedRowVisible = await deletedRow.isVisible().catch(() => false);
            findings.push(`Row visible after delete: ${deletedRowVisible}`);
            expect(deletedRowVisible, `Row "${testPaquete}" should be removed after delete`).toBe(false);
          } else {
            findings.push('Delete button not found on row');
          }
        } else {
          findings.push('CRUD: new row not found in table — skipping edit/delete');
        }
      } else {
        findings.push('No create button found — skipping CRUD test (module may be read-only)');
      }

      // ══════════════════════════════════════════════════════════════════════════
      // 5. SEMI-AUTO WORKFLOW
      // ══════════════════════════════════════════════════════════════════════════
      findings.push('--- Semi-auto workflow ---');

      // Click "Ver alertas" button if present
      const alertBtn = page.locator(PDC_SELECTORS.buttons.verAlertas);
      if (await alertBtn.isVisible().catch(() => false)) {
        await alertBtn.click();
        await page.waitForTimeout(1500);
        findings.push('Clicked "Ver alertas"');
      } else {
        findings.push('"Ver alertas" button not found');
      }

      // API: semi-auto preview
      const previewResult = await apiPost(page, '/api/pdc/auto/preview', {});
      if (previewResult.ok && previewResult.payload.run_id) {
        const runId = previewResult.payload.run_id;
        const steps = previewResult.payload.analysis?.steps?.length || 0;
        findings.push(`Preview OK: run_id=${runId}, steps=${steps}`);
        expect(runId, 'Preview must return run_id').toBeTruthy();

        // API: semi-auto apply — F-007 known: may fail with "Solicitud inválida"
        const applyResult = await apiPost(page, '/api/pdc/auto/apply', { run_id: runId });
        if (applyResult.ok) {
          findings.push(`Apply OK: ${JSON.stringify(applyResult.payload).slice(0, 200)}`);
        } else {
          findings.push(`F-007: auto/apply failed (expected): ${JSON.stringify(applyResult.payload).slice(0, 300)}`);
        }

        // API: feedback — attempt regardless of apply result
        const feedbackResult = await apiPost(page, '/api/pdc/auto/feedback', {
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

      // DB verify: semi-auto runs for PDC
      const autoRunCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_runs WHERE project_id=${PROJECT.projectId} AND module='pdc'`,
      );
      const suggestionCount = scalar(
        `SELECT COUNT(*) FROM semi_auto_suggestions WHERE project_id=${PROJECT.projectId} AND module='pdc'`,
      );
      findings.push(`Semi-auto DB: runs=${autoRunCount}, suggestions=${suggestionCount}`);

      // ══════════════════════════════════════════════════════════════════════════
      // 6. BUTTON VERIFICATION
      // ══════════════════════════════════════════════════════════════════════════
      findings.push('--- Button verification ---');

      const actualizarBtn = page.locator(PDC_SELECTORS.buttons.actualizar);
      const actualizarVisible = await actualizarBtn.isVisible().catch(() => false);
      findings.push(`"Actualizar" button visible: ${actualizarVisible}`);

      const desglosarBtn = page.locator(PDC_SELECTORS.buttons.desglosar);
      const desglosarVisible = await desglosarBtn.isVisible().catch(() => false);
      findings.push(`"Desglosar" button visible: ${desglosarVisible}`);

      expect(actualizarVisible, '"Actualizar" button must be visible').toBe(true);
      expect(desglosarVisible, '"Desglosar" button must be visible').toBe(true);

      // ══════════════════════════════════════════════════════════════════════════
      // SUMMARY
      // ══════════════════════════════════════════════════════════════════════════
      const finalUiCount = await visibleRowCount(page);
      findings.push(`Final UI row count: ${finalUiCount}`);

      console.log(`\n[PDC] Findings (${findings.length}):`);
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
