import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, login, loginAndSelectProject, logout, postFormJson, selectProject } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { CNC_SELECTORS } from '../../support/moduleSelectors.mjs';

const DP = PROJECTS.find((p) => p.key === 'construction');
const PC = PROJECTS.find((p) => p.key === 'pc');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

async function apiPost(page, url, body, label) {
  const r = await postFormJson(page, url, body);
  const ok = r.ok && !r.payload.parseError;
  if (!ok) console.log(`[CNC] ${label}: ${JSON.stringify(r.payload).slice(0, 300)}`);
  return { ok, payload: r.payload };
}

/**
 * Run the full CNC calificación workflow against a given project.
 * Steps:
 *  1. Navigate to /programacion-semanal/cnc and verify #dt_cliente loads
 *  2. Fetch CNC list via API — if 0 rows, log and skip gracefully
 *  3. Get CNC reasons via /api/cnc/reasons
 *  4. Pick a row → mark PAC=1 (cumplida) → verify CNC fields cleared
 *  5. Pick a different row → mark PAC=0 (incumplida) → select CNC reason via modal → save
 *  6. Verify changes via API
 *  7. Check PAC global is calculated
 *  8. Open leyenda modal → verify → close
 *  9. Apply filters if available
 */
async function runCncWorkflow(page, project, findings) {
  const db = project.dbPrefix;
  const semana = project.maxWeek || 1;

  // 1. Navigate to CNC page
  await changeWeek(page, semana, '/programacion-semanal/cnc').catch(() => {});
  await page.waitForSelector(CNC_SELECTORS.tableSelector, { state: 'attached', timeout: 15000 }).catch(() => {});
  const tableVisible = await page.locator(CNC_SELECTORS.tableSelector).isVisible().catch(() => false);
  findings.push(`CNC page loaded: #dt_cliente visible=${tableVisible}`);

  if (!tableVisible) {
    findings.push('CNC page: #dt_cliente not visible — skipping UI assertions');
    return;
  }

  // 2. Fetch CNC list via API
  const cncList = await apiPost(page, '/api/cnc/list', { semana: String(semana), db }, `CNC list S${semana}`);
  const rows = cncList.ok ? (cncList.payload.data || []) : [];
  findings.push(`CNC list S${semana}: ${rows.length} filas`);

  if (rows.length === 0) {
    findings.push('CNC: 0 filas — sin actividades con Categoria_CNC. Skipping calificación flow.');
    return;
  }

  // 3. Get CNC reasons
  const reasonsRes = await apiPost(page, '/api/cnc/reasons', { categoria: 'Programación' }, 'CNC reasons Programación');
  const reasons = reasonsRes.ok ? (reasonsRes.payload || []) : [];
  findings.push(`CNC reasons (Programación): ${Array.isArray(reasons) ? reasons.length : 'N/A'}`);

  const hasReasons = Array.isArray(reasons) && reasons.length > 0;
  const firstReason = hasReasons ? (reasons[0].CNC || reasons[0].cnc || '') : '';

  // 4. Mark first row as PAC=1 (cumplida) via semanal API
  const rowCumplida = rows[0];
  const idCumplida = String(rowCumplida.Consecutivo || rowCumplida.row_id || rowCumplida.id || '');
  findings.push(`Row to mark cumplida: Consecutivo=${idCumplida}, Actividad=${(rowCumplida.Actividad || '').slice(0, 60)}`);

  // Build semanal save payload — use existing data to mark as cumplida (PAC=1)
  const saveCumplida = await apiPost(page, '/api/semanal/save', {
    Consecutivo: idCumplida,
    semana: String(semana),
    db,
    Descripcion: rowCumplida.Descripcion || '',
    Ubicacion: rowCumplida.Ubicacion || '',
    Sub_Contratista: rowCumplida.Sub_Contratista || '',
    Responsable_AIA: rowCumplida.Responsable_AIA || '',
    Empresa: rowCumplida.Empresa || '',
    Compromiso: String(rowCumplida.Compromiso || '0'),
    Cantidad_Sugerida: String(rowCumplida.Cantidad_Sugerida || '0'),
    Ejecutado_Real: String(rowCumplida.Compromiso || '1'),
    Categoria_CNC: '',
    CNC: '',
    Observaciones_CNC: '',
    Rendimientos: rowCumplida.Rendimientos || '',
  }, `Semanal save cumplida S${semana}`);
  findings.push(`Semanal save (cumplida PAC=1): ok=${saveCumplida.ok}`);

  // Verify PAC changed via API
  const verifyList1 = await apiPost(page, '/api/cnc/list', { semana: String(semana), db }, `CNC verify after cumplida`);
  if (verifyList1.ok) {
    const remainingRows = (verifyList1.payload.data || []).filter(
      (r) => String(r.Consecutivo || r.row_id || r.id) === idCumplida,
    );
    // After PAC=1, CNC fields should be cleared → row should NOT appear in CNC list
    const stillInCnc = remainingRows.length > 0 && remainingRows[0].Categoria_CNC;
    findings.push(`CNC verify after cumplida: row ${idCumplida} ${stillInCnc ? 'STILL in CNC list (unexpected)' : 'removed from CNC list (expected)'}`);
  }

  // 5. Mark second row as PAC=0 (incumplida) with CNC reason
  if (rows.length >= 2) {
    const rowIncumplida = rows[1];
    const idIncumplida = String(rowIncumplida.Consecutivo || rowIncumplida.row_id || rowIncumplida.id || '');
    findings.push(`Row to mark incumplida: Consecutivo=${idIncumplida}, Actividad=${(rowIncumplida.Actividad || '').slice(0, 60)}`);

    if (hasReasons && firstReason) {
      // Save via CNC API with a cause
      const saveIncumplida = await apiPost(page, '/api/cnc/save', {
        Consecutivo: idIncumplida,
        Categoria_CNC: 'Programación',
        CNC: firstReason,
        Observaciones_CNC: 'E2E test incumplida',
      }, `CNC save incumplida`);
      findings.push(`CNC save (incumplida): ok=${saveIncumplida.ok}, respuesta=${saveIncumplida.payload?.respuesta || 'N/A'}`);

      // Verify via API
      const verifyList2 = await apiPost(page, '/api/cnc/list', { semana: String(semana), db }, `CNC verify after incumplida`);
      if (verifyList2.ok) {
        const matched = (verifyList2.payload.data || []).find(
          (r) => String(r.Consecutivo || r.row_id || r.id) === idIncumplida,
        );
        if (matched) {
          findings.push(`CNC verify incumplida: Categoria_CNC=${matched.Categoria_CNC}, CNC=${matched.CNC}`);
        } else {
          findings.push(`CNC verify incumplida: row ${idIncumplida} not found in CNC list`);
        }
      }
    } else {
      findings.push('No CNC reasons available — skipping incumplida assignment');
    }
  } else {
    findings.push('Only 1 CNC row available — skipping incumplida test (need ≥2 rows)');
  }

  // 6. Verify the second row via API to confirm CNC cause was applied
  const finalVerify = await apiPost(page, '/api/cnc/list', { semana: String(semana), db }, `CNC final verify`);
  if (finalVerify.ok) {
    const cncRows = finalVerify.payload.data || [];
    const withCnc = cncRows.filter((r) => r.Categoria_CNC && r.CNC);
    findings.push(`CNC final: ${cncRows.length} total rows, ${withCnc.length} with CNC cause assigned`);
  }

  // 7. Check PAC global is calculated — verify via semanal list
  const semanalList = await apiPost(page, '/api/semanal/list', { db, semana: String(semana) }, `Semanal list S${semana}`);
  if (semanalList.ok && semanalList.payload.data?.length > 0) {
    const withPac = semanalList.payload.data.filter((r) => r.PAC !== null && r.PAC !== '' && r.PAC !== undefined);
    const pacSum = withPac.reduce((acc, r) => acc + Number(r.PAC || 0), 0);
    const pacGlobal = withPac.length > 0 ? (pacSum / withPac.length) : 0;
    findings.push(`PAC global: ${withPac.length} rows with PAC, sum=${pacSum}, avg=${pacGlobal.toFixed(3)}`);
  } else {
    findings.push('Semanal list: no data for PAC global check');
  }

  // 8. Open leyenda modal
  const leyendaBtn = page.locator(CNC_SELECTORS.buttons.leyenda);
  if (await leyendaBtn.isVisible().catch(() => false)) {
    await leyendaBtn.click();
    // Wait for modal to appear — try common patterns
    const modalVisible = await page.locator('.modal.show, .modal.in, [role="dialog"]').isVisible({ timeout: 5000 }).catch(() => false);
    findings.push(`Leyenda modal opened: ${modalVisible}`);

    // Close it
    const closeBtn = page.locator('.modal.show button:has-text("Cerrar"), .modal.in button:has-text("Cerrar"), [role="dialog"] button:has-text("Cerrar")');
    if (await closeBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await closeBtn.click();
      findings.push('Leyenda modal closed');
    } else {
      // Fallback: press Escape
      await page.keyboard.press('Escape');
      findings.push('Leyenda modal closed via Escape');
    }
  } else {
    findings.push('Leyenda button not visible — skipping');
  }

  // 9. Apply filters if available
  const searchInput = page.locator('#dt_cliente_filter input, .dataTables_filter input');
  if (await searchInput.isVisible({ timeout: 3000 }).catch(() => false)) {
    await searchInput.fill('E2E');
    await page.waitForTimeout(500);
    const filteredRows = await page.locator('#dt_cliente tbody tr').count();
    findings.push(`Filter applied (E2E): ${filteredRows} visible rows`);
    await searchInput.clear();
    await page.waitForTimeout(500);
    findings.push('Filter cleared');
  } else {
    findings.push('No search filter available on CNC page');
  }
}

async function runTestForProject(page, project, label) {
  const errors = installErrorCollectors(page);
  attachAssertionCollector(errors);
  const findings = [];
  let snapshot;

  try {
    snapshot = new ProjectDbSnapshot(project).capture();
    await loginAndSelectProject(page, project);
    await runCncWorkflow(page, project, findings);

    console.log(`\n[CNC ${label}] ${findings.length} findings:`);
    findings.forEach((f) => console.log(`  - ${f}`));
  } finally {
    await logout(page).catch(() => {});
    if (snapshot) { snapshot.restore(); snapshot.dispose(); }
  }

  errors.findings = findings;
  return errors;
}

test.describe('PS Calificación — CNC workflow', () => {
  test('Da Porto (Admin): CNC calificar cumplida, incumplida con causa, PAC, leyenda, filtros', async ({ page }, testInfo) => {
    test.skip(!DP, 'Da Porto project required');
    const errors = await runTestForProject(page, DP, 'Da Porto');
    testInfo._e2eErrors = errors;
  });

  test('Aeropuerto PC (Admin): CNC calificar cumplida, incumplida con causa, PAC, leyenda, filtros', async ({ page }, testInfo) => {
    test.skip(!PC, 'Aeropuerto PC project required');
    const errors = await runTestForProject(page, PC, 'Aeropuerto PC');
    testInfo._e2eErrors = errors;
  });

  test('Da Porto (Residente): CNC access verification', async ({ page }, testInfo) => {
    test.skip(!DP, 'Da Porto project required');
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(DP).capture();

      // Login as Residente (test.R / aia2026)
      await login(page, { username: 'test.R', password: 'aia2026' });
      await selectProject(page, DP);

      // Navigate to CNC
      await changeWeek(page, DP.maxWeek || 1, '/programacion-semanal/cnc').catch(() => {});

      // Verify page loads — Residente should see the CNC page (may be read-only)
      const tableExists = await page.locator(CNC_SELECTORS.tableSelector).isVisible({ timeout: 10000 }).catch(() => false);
      findings.push(`Residente CNC access: #dt_cliente visible=${tableExists}`);

      // Try API access
      const cncList = await apiPost(page, '/api/cnc/list', {
        semana: String(DP.maxWeek || 1),
        db: DP.dbPrefix,
      }, 'CNC list Residente');
      findings.push(`Residente CNC API: ok=${cncList.ok}, rows=${cncList.payload?.data?.length || 0}`);

      // Attempt save — should be denied or succeed depending on RBAC
      if (cncList.ok && cncList.payload.data?.length > 0) {
        const testRow = cncList.payload.data[0];
        const saveAttempt = await apiPost(page, '/api/cnc/save', {
          Consecutivo: String(testRow.Consecutivo || testRow.row_id || ''),
          Categoria_CNC: testRow.Categoria_CNC || '',
          CNC: testRow.CNC || '',
          Observaciones_CNC: testRow.Observaciones_CNC || '',
        }, 'CNC save Residente attempt');
        findings.push(`Residente CNC save attempt: ok=${saveAttempt.ok}, respuesta=${saveAttempt.payload?.respuesta || 'N/A'}`);
      }

      console.log(`\n[CNC Residente] ${findings.length} findings:`);
      findings.forEach((f) => console.log(`  - ${f}`));
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
