import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, logout, postFormJson, getJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}
function sqlText(sql) {
  try { return runSql(sql).trim().split(/\n/).pop() || ''; } catch { return ''; }
}

async function apiPost(page, url, body, label) {
  const r = await postFormJson(page, url, body);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}
async function apiGet(page, url, label) {
  const r = await getJson(page, url);
  return { ok: r.ok && !r.payload.parseError, payload: r.payload };
}

async function ensureSecondWeek(page, project) {
  const count = scalar(`SELECT COUNT(*) FROM semanas_activas WHERE project_id=${project.projectId}`);
  if (count >= 2) return;
  const nextStart = sqlText(
    `SELECT COALESCE(DATE_FORMAT(DATE_ADD(MAX(Fecha_Fin_Sem), INTERVAL 1 DAY), '%Y-%m-%d'), '2026-07-07') FROM semanas_activas WHERE project_id=${project.projectId}`
  );
  await postFormJson(page, `/legacy/funciones_generales/php/nueva_semana.php?db=${project.dbPrefix}`, {
    opcion: 'nueva_sem', f_inicio_sem: nextStart,
  });
  const newCount = scalar(`SELECT COUNT(*) FROM semanas_activas WHERE project_id=${project.projectId} AND Semana=2`);
  expect(newCount, `Week 2 must exist (count=${newCount})`).toBeGreaterThan(0);
}

async function runWeekLPS(page, project, week, findings) {
  const db = project.dbPrefix;

  await changeWeek(page, week, '/programa-general').catch(() => {});
  const pg = await apiGet(page, `/api/general/list?db=${db}&semana=${week}`, `PG S${week}`);
  if (pg.ok && pg.payload.data?.length > 0) {
    const row = pg.payload.data[0];
    const upd = await apiPost(page, '/api/general/update', {
      uniq_id: String(row.uniq_id || row.unique_id || row.id || ''),
      campo: 'Avance_Programado',
      valor: String((parseFloat(row.Avance_Programado || '0')) + 5),
      semana: String(week), db,
    }, `PG S${week} update`);
    if (!upd.ok) findings.push(`PG S${week} update: ${JSON.stringify(upd.payload).slice(0,200)}`);
  } else { findings.push(`PG S${week}: sin filas`); }

  await changeWeek(page, week, '/programacion-intermedia').catch(() => {});
  const pi = await apiGet(page, '/api/pi/list', `PI S${week}`);
  if (pi.ok && pi.payload.data?.length > 0) {
    const piSave = await apiPost(page, '/api/pi/save', { semana: String(week), db, opcion: 'liberar_todas' }, `PI S${week}`);
    if (!piSave.ok) findings.push(`PI S${week}: ${JSON.stringify(piSave.payload).slice(0,200)}`);
  } else { findings.push(`PI S${week}: sin filas`); }

  await changeWeek(page, week, '/programacion-semanal').catch(() => {});
  const ps = await apiGet(page, `/api/semanal/list?db=${db}&semana=${week}`, `PS S${week}`);
  if (ps.ok && ps.payload.data?.length > 0) {
    const psSave = await apiPost(page, '/api/semanal/save', { semana: String(week), db, opcion: 'autoprogramar' }, `PS S${week}`);
    if (!psSave.ok) findings.push(`PS S${week}: ${JSON.stringify(psSave.payload).slice(0,200)}`);
  } else { findings.push(`PS S${week}: sin filas`); }

  await apiPost(page, '/api/indicadores/generar', { db, semana: String(week) }, `Ind S${week}`);

  if (!project.constructionOnly) return;

  await changeWeek(page, week, '/programacion-semanal/cnp').catch(() => {});
  const cnp = await apiPost(page, '/api/cnp/list', { semana: String(week), db }, `CNP S${week}`);
  findings.push(`CNP S${week}: ${cnp.payload.data?.length || 0} filas`);

  await changeWeek(page, week, '/programacion-semanal/cnc').catch(() => {});
  const cnc = await apiPost(page, '/api/cnc/list', { semana: String(week), db }, `CNC S${week}`);
  findings.push(`CNC S${week}: ${cnc.payload.data?.length || 0} filas`);

  await changeWeek(page, week, '/programacion-semanal/cic').catch(() => {});
  const cic = await apiPost(page, '/api/cic/list', { semana: String(week), db }, `CIC S${week}`);
  findings.push(`CIC S${week}: ${cic.ok ? (cic.payload.data?.length || 0) + ' filas' : 'ERROR'}`);
}

test.describe('LPS two-week deep workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto project required');
  });

  test('Da Porto: 2 weeks LPS flow with API updates', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    let snapshot;
    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();
      await loginAndSelectProject(page, PROJECT);
      await ensureSecondWeek(page, PROJECT);
      const allFindings = [];
      for (const week of [1, 2]) await runWeekLPS(page, PROJECT, week, allFindings);
      console.log(`\n[LPS] ${allFindings.length} findings total`);
      allFindings.forEach((f) => console.log(`  - ${f}`));
      const pgCheck = scalar(`SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${PROJECT.projectId} AND Semana=1`);
      expect(pgCheck, 'PG must have data for week 1').toBeGreaterThan(0);
      errors.findings = allFindings;
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || { pageErrors:[], consoleErrors:[], serverErrors:[], assertionErrors:[] };
    generateFindings(testInfo, errs);
  });
});
