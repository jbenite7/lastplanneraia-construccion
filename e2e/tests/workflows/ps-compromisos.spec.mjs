import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, login, loginAndSelectProject, logout, postFormJson, getJson, selectProject } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { waitForRender } from '../../support/handsontable.mjs';
import { PS_SELECTORS, COMMON_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');
const PC_PROJECT = PROJECTS.find((p) => p.key === 'pc');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function apiGet(page, url) {
  const r = await getJson(page, url);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function closeOpenModal(page) {
  const modal = page.locator('.modal.show, dialog[open]').last();
  if (!(await modal.isVisible().catch(() => false))) {
    return;
  }

  const closeBtn = modal.locator('button:has-text("Cerrar"), button:has-text("Cancelar"), .close, [data-dismiss="modal"]').first();
  if (await closeBtn.isVisible().catch(() => false)) {
    await closeBtn.click();
  } else {
    await page.keyboard.press('Escape').catch(() => {});
  }
  await page.waitForTimeout(500);
}

/**
 * Shared PS compromisos workflow for a given project/week/role.
 *
 * Steps:
 *  1. Navigate to /programacion-semanal, wait for render
 *  2. Verify phase text & breadcrumb
 *  3. Open leyenda modal → verify dialog → close
 *  4. Autoprogramar activities (graceful if none available)
 *  5. Verify activities appear in table + API + DB
 *  6. Add manual activity → verify in table
 *  7. Delete a row via CNP modal → verify removal
 *  8. Confirm compromisos → verify chips & PAC column
 *  9. Export CSV → verify download
 * 10. LPS Drawer verification
 */
async function runPSCompromisos(page, project, week, findings, roleLabel) {
  const db = project.dbPrefix;
  const label = `${roleLabel} S${week}`;

  // ── 1. Navigate to PS ───────────────────────────────────────────────────
  await changeWeek(page, week, '/programacion-semanal').catch(() => {});
  await waitForRender(page);
  await page.waitForTimeout(1000);

  // ── 2. Verify phase text & breadcrumb ───────────────────────────────────
  const phaseVisible = await page.locator(PS_SELECTORS.phase).isVisible().catch(() => false);
  findings.push(`${label} phase text visible: ${phaseVisible}`);

  const breadcrumbText = await page.locator(COMMON_SELECTORS.breadcrumb).textContent().catch(() => '');
  findings.push(`${label} breadcrumb: "${breadcrumbText.trim()}"`);

  // ── 3. Leyenda modal ────────────────────────────────────────────────────
  const leyendaBtn = page.locator(PS_SELECTORS.buttons.leyenda);
  if (await leyendaBtn.isVisible().catch(() => false)) {
    await leyendaBtn.click();
    await page.waitForTimeout(500);
    const leyendaDialog = page.locator(COMMON_SELECTORS.leyendaModal);
    const dialogVisible = await leyendaDialog.isVisible().catch(() => false);
    findings.push(`${label} leyenda dialog visible: ${dialogVisible}`);
    if (dialogVisible) {
      await page.locator(COMMON_SELECTORS.leyendaClose).click().catch(() => {});
      await page.waitForTimeout(300);
    }
  } else {
    findings.push(`${label} leyenda button not found`);
  }

  // ── 4. Autoprogramar ───────────────────────────────────────────────────
  const autoBtn = page.locator(PS_SELECTORS.buttons.autoprogramar);
  if (await autoBtn.isVisible().catch(() => false)) {
    await autoBtn.click();
    await page.waitForTimeout(1500);

    // If a TNP modal opens with activities, select some and confirm
    const tnpModal = page.locator('dialog').filter({ hasText: /actividad|TNP|compromiso/i }).first();
    if (await tnpModal.isVisible().catch(() => false)) {
      findings.push(`${label} TNP modal opened`);
      // Select first available checkbox/row in the modal
      const firstCheckbox = tnpModal.locator('input[type="checkbox"], .htCheckboxRenderer').first();
      if (await firstCheckbox.isVisible().catch(() => false)) {
        await firstCheckbox.check().catch(() => {});
        await page.waitForTimeout(300);
      }
      // Confirm in modal
      const confirmBtn = tnpModal.locator('button:has-text("Confirmar"), button:has-text("Aceptar"), button:has-text("Aplicar")').first();
      if (await confirmBtn.isVisible().catch(() => false)) {
        await confirmBtn.click();
        await page.waitForTimeout(1000);
      }
    } else {
      // No modal — auto-programar may have run silently or no TNP activities
      findings.push(`${label} autoprogramar: no TNP modal appeared (may be empty or auto-applied)`);
    }
  } else {
    findings.push(`${label} autoprogramar button not found`);
  }

  await waitForRender(page);
  await page.waitForTimeout(1000);

  // ── 5. Verify activities in table + API + DB ───────────────────────────
  // UI: check table has rows
  const tableRows = await page.locator('[role="treegrid"] tbody tr, .handsontable .htCore tbody tr').count().catch(() => 0);
  findings.push(`${label} table rows (UI): ${tableRows}`);

  // API: /api/semanal/list
  const apiList = await apiGet(page, `/api/semanal/list?db=${db}&semana=${week}`);
  if (apiList.ok && apiList.payload.data?.length > 0) {
    findings.push(`${label} API /api/semanal/list: ${apiList.payload.data.length} rows`);
  } else {
    findings.push(`${label} API /api/semanal/list: 0 rows or error — ${JSON.stringify(apiList.payload).slice(0, 200)}`);
  }

  // DB: programacion_semanal count
  const dbCount = scalar(`SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${project.projectId} AND Semana=${week}`);
  findings.push(`${label} DB programacion_semanal count: ${dbCount}`);

  // ── 6. Add manual activity ──────────────────────────────────────────────
  const addBtn = page.locator(PS_SELECTORS.buttons.agregarActividad);
  if (await addBtn.isVisible().catch(() => false)) {
    await addBtn.click();
    await page.waitForTimeout(1000);

    // Fill the manual activity form (dialog or inline)
    const addDialog = page.locator('dialog').filter({ hasText: /agregar|manual|actividad/i }).first();
    if (await addDialog.isVisible().catch(() => false)) {
      // Fill available fields
      const actividadInput = addDialog.locator('input[name*="actividad"], textarea[name*="actividad"], input[placeholder*="actividad"]').first();
      if (await actividadInput.isVisible().catch(() => false)) {
        await actividadInput.fill(`E2E PS Test ${Date.now()}`);
      }
      const unidadInput = addDialog.locator('input[name*="unidad"], select[name*="unidad"]').first();
      if (await unidadInput.isVisible().catch(() => false)) {
        await unidadInput.fill('m2').catch(() => {});
      }
      const cantidadInput = addDialog.locator('input[name*="cantidad"], input[name*="cant"]').first();
      if (await cantidadInput.isVisible().catch(() => false)) {
        await cantidadInput.fill('10');
      }

      // Save
      const saveBtn = addDialog.locator('button:has-text("Guardar"), button:has-text("Aceptar"), button[type="submit"]').first();
      if (await saveBtn.isVisible().catch(() => false)) {
        await saveBtn.click();
        await page.waitForTimeout(1500);
        findings.push(`${label} manual activity added`);
      } else {
        findings.push(`${label} manual activity save button not found`);
      }
    } else {
      // Inline row addition (Handsontable pattern)
      findings.push(`${label} manual activity: no dialog, may use inline row`);
    }
  } else {
    findings.push(`${label} agregarActividad button not found`);
  }

  await waitForRender(page);
  await page.waitForTimeout(1000);

  // ── 7. Delete a row via CNP modal ──────────────────────────────────────
  const deleteBtn = page.locator(PS_SELECTORS.rowActions.eliminar).first();
  if (await deleteBtn.isVisible().catch(() => false)) {
    await deleteBtn.click();
    await page.waitForTimeout(1000);

    // CNP modal should appear requiring a reason
    const cnpModal = page.locator('dialog').filter({ hasText: /CNP|causa|motivo|razón|no cumplimiento/i }).first();
    if (await cnpModal.isVisible().catch(() => false)) {
      findings.push(`${label} CNP modal opened`);
      // Select a CNC reason if dropdown available
      const cncSelect = cnpModal.locator('select, [role="combobox"]').first();
      if (await cncSelect.isVisible().catch(() => false)) {
        // Select first non-empty option
        await cncSelect.click();
        await page.waitForTimeout(300);
        const firstOption = cnpModal.locator('select option:nth-child(2), [role="option"]').first();
        if (await firstOption.isVisible().catch(() => false)) {
          await firstOption.click().catch(() => {});
        }
      }
      // Fill observation if required
      const obsInput = cnpModal.locator('textarea, input[name*="observ"], input[name*="obs"]').first();
      if (await obsInput.isVisible().catch(() => false)) {
        await obsInput.fill('E2E test deletion reason');
      }
      // Confirm delete
      const confirmDelete = cnpModal.locator('button:has-text("Eliminar"), button:has-text("Confirmar"), button:has-text("Aceptar")').first();
      if (await confirmDelete.isVisible().catch(() => false)) {
        await confirmDelete.click();
        await page.waitForTimeout(1500);
        findings.push(`${label} row deleted via CNP`);
      }
    } else {
      // Simple confirm dialog
      const confirmBtn = page.locator('dialog button:has-text("Confirmar"), dialog button:has-text("Aceptar")').first();
      if (await confirmBtn.isVisible().catch(() => false)) {
        await confirmBtn.click();
        await page.waitForTimeout(1000);
        findings.push(`${label} row deleted (simple confirm)`);
      } else {
        findings.push(`${label} delete: no confirmation modal appeared`);
      }
    }
  } else {
    findings.push(`${label} delete button not found`);
  }

  await waitForRender(page);
  await page.waitForTimeout(500);
  await closeOpenModal(page);

  // ── 8. Confirm compromisos ─────────────────────────────────────────────
  const confirmarBtn = page.locator(PS_SELECTORS.buttons.confirmarCompromisos);
  if (await confirmarBtn.isVisible().catch(() => false)) {
    await confirmarBtn.click();
    await page.waitForTimeout(2000);

    // Verify chips updated
    const chipCount = await page.locator('.chip, .badge, [class*="chip"]').count().catch(() => 0);
    findings.push(`${label} chip/badge count after confirm: ${chipCount}`);

    // Verify PAC column has values
    const pacHeader = await page.locator('[role="treegrid"] th:has-text("PAC"), .handsontable th:has-text("PAC")').isVisible().catch(() => false);
    findings.push(`${label} PAC column header visible: ${pacHeader}`);
  } else {
    findings.push(`${label} confirmarCompromisos button not found`);
  }

  // ── 9. Export CSV ──────────────────────────────────────────────────────
  const exportBtn = page.locator(PS_SELECTORS.buttons.exportCSV);
  if (await exportBtn.isVisible().catch(() => false)) {
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
      exportBtn.click(),
    ]);
    if (download) {
      const path = await download.path();
      if (path) {
        const fs = await import('fs');
        const content = fs.readFileSync(path, 'utf-8');
        findings.push(`${label} CSV download: ${content.length} bytes, ${content.split('\n').length} lines`);
      }
    } else {
      findings.push(`${label} CSV download: no download event (may use different mechanism)`);
    }
  } else {
    findings.push(`${label} export CSV button not found`);
  }

  // ── 10. LPS Drawer ─────────────────────────────────────────────────────
  const drawerBtn = page.locator(COMMON_SELECTORS.lpsDrawer);
  if (await drawerBtn.isVisible().catch(() => false)) {
    await drawerBtn.click();
    await page.waitForTimeout(1000);
    const drawerDialog = page.locator(COMMON_SELECTORS.lpsDrawerDialog);
    const drawerVisible = await drawerDialog.isVisible().catch(() => false);
    findings.push(`${label} LPS Drawer visible: ${drawerVisible}`);
    if (drawerVisible) {
      // Close drawer
      await page.keyboard.press('Escape').catch(() => {});
      await page.waitForTimeout(300);
    }
  } else {
    findings.push(`${label} LPS Drawer button not found`);
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Test Suite
// ═══════════════════════════════════════════════════════════════════════════════

test.describe('PS Compromisos — Programación Semanal workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto project required');
  });

  // ── Test 1: Da Porto Admin, semana 2 ────────────────────────────────────
  test('Da Porto Admin: PS compromisos full flow (week 2)', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();
      await loginAndSelectProject(page, PROJECT);
      await runPSCompromisos(page, PROJECT, 2, findings, 'DaPortoAdmin');

      console.log(`\n[PS-Compromisos] Da Porto Admin findings:`);
      findings.forEach((f) => console.log(`  ${f}`));
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    errors.findings = findings;
    testInfo._e2eErrors = errors;
  });

  // ── Test 2: Aeropuerto PC Admin, semana 1 ──────────────────────────────
  test('Aeropuerto PC Admin: PS compromisos flow (week 1)', async ({ page }, testInfo) => {
    test.skip(!PC_PROJECT, 'Aeropuerto PC project required (set E2E_INCLUDE_PRECONSTRUCTION=1)');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PC_PROJECT).capture();
      await loginAndSelectProject(page, PC_PROJECT);
      await runPSCompromisos(page, PC_PROJECT, 1, findings, 'PCAdmin');

      console.log(`\n[PS-Compromisos] Aeropuerto PC Admin findings:`);
      findings.forEach((f) => console.log(`  ${f}`));
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    errors.findings = findings;
    testInfo._e2eErrors = errors;
  });

  // ── Test 3: Da Porto Residente, semana 2 ───────────────────────────────
  test('Da Porto Residente: PS compromisos RBAC restrictions (week 2)', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      // Login as Residente (test.R / aia2026)
      await login(page, { username: 'test.R', password: 'aia2026' });
      await selectProject(page, PROJECT);
      await runPSCompromisos(page, PROJECT, 2, findings, 'DaPortoResidente');

      // RBAC: Residente may not see certain buttons or may get 403
      const addBtnVisible = await page.locator(PS_SELECTORS.buttons.agregarActividad).isVisible().catch(() => false);
      const confirmBtnVisible = await page.locator(PS_SELECTORS.buttons.confirmarCompromisos).isVisible().catch(() => false);
      findings.push(`DaPortoResidente RBAC — agregarActividad visible: ${addBtnVisible}, confirmarCompromisos visible: ${confirmBtnVisible}`);

      console.log(`\n[PS-Compromisos] Da Porto Residente findings:`);
      findings.forEach((f) => console.log(`  ${f}`));
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    errors.findings = findings;
    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });
});
