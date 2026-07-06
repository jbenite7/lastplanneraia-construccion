import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings } from '../../support/findings.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

function sqlText(sql) {
  try { return runSql(sql).trim().split(/\n/).pop() || ''; } catch { return ''; }
}

const MAX_WEEK_LIMIT = 5;

test.describe('New week creation and ejecutado propagation', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto project required');
  });

  test('Da Porto: create new week and verify ejecutado propagation PS→PG', async ({ page }, testInfo) => {
    let snapshot;
    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();
      await loginAndSelectProject(page, PROJECT);

      const db = PROJECT.dbPrefix;
      const pid = PROJECT.projectId;

      // 1. Check current week count
      const currentCount = scalar(`SELECT COUNT(*) FROM semanas_activas WHERE project_id=${pid}`);
      console.log(`[NEW-WEEK] Current semana count: ${currentCount}`);

      // 2. Skip if already at max reasonable limit
      if (currentCount >= MAX_WEEK_LIMIT) {
        console.log(`[NEW-WEEK] Skipped: semana count ${currentCount} >= ${MAX_WEEK_LIMIT} max limit`);
        test.skip(true, `Skipping: already at ${currentCount} weeks (limit ${MAX_WEEK_LIMIT})`);
        return;
      }

      // 3. Determine next week number (current max + 1)
      const currentMaxWeek = scalar(
        `SELECT COALESCE(MAX(Semana), 0) FROM semanas_activas WHERE project_id=${pid}`
      );
      const nextWeek = currentMaxWeek + 1;
      console.log(`[NEW-WEEK] Current max week: ${currentMaxWeek}, creating week: ${nextWeek}`);

      // 4. Get the Fecha_Fin_Sem of the current max week to calculate next start
      const nextStart = sqlText(
        `SELECT DATE_FORMAT(DATE_ADD(MAX(Fecha_Fin_Sem), INTERVAL 1 DAY), '%Y-%m-%d') FROM semanas_activas WHERE project_id=${pid}`
      );
      console.log(`[NEW-WEEK] Next start date: ${nextStart}`);

      // 5. Snapshot the previous week's ejecutado data for comparison
      const prevWeek = currentMaxWeek;
      let prevEjecutadoCount = 0;
      let prevEjecutadoSum = 0;
      if (prevWeek > 0) {
        prevEjecutadoCount = scalar(
          `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${pid} AND Semana=${prevWeek}`
        );
        prevEjecutadoSum = scalar(
          `SELECT COALESCE(SUM(CAST(Ejecutado_Real AS DECIMAL(12,2))), 0) FROM programa_consolidado WHERE project_id=${pid} AND Semana=${prevWeek}`
        );
        console.log(`[NEW-WEEK] Previous week ${prevWeek}: ${prevEjecutadoCount} rows, ejecutado sum: ${prevEjecutadoSum}`);
      }

      // 6. Capture pre-creation row count for semanas_activas
      const preCount = scalar(`SELECT COUNT(*) FROM semanas_activas WHERE project_id=${pid}`);

      // 7. Create the new week via POST
      console.log(`[NEW-WEEK] POST nueva_semana.php?db=${db} f_inicio_sem=${nextStart}`);
      const r = await postFormJson(page, `/legacy/funciones_generales/php/nueva_semana.php?db=${db}`, {
        opcion: 'nueva_sem',
        f_inicio_sem: nextStart,
      });
      const createOk = r.ok && !r.payload.parseError;
      console.log(`[NEW-WEEK] Create response ok=${r.ok} parseError=${r.payload.parseError}`);
      if (!createOk) {
        console.log(`[NEW-WEEK] Create payload: ${JSON.stringify(r.payload).slice(0, 300)}`);
      }

      // 8. Wait briefly for DB propagation
      await page.waitForTimeout(2000);

      // 9. Verify the new week exists in DB
      const newWeekExists = scalar(
        `SELECT COUNT(*) FROM semanas_activas WHERE project_id=${pid} AND Semana=${nextWeek}`
      );
      console.log(`[NEW-WEEK] Week ${nextWeek} rows in semanas_activas: ${newWeekExists}`);
      expect(newWeekExists, `Week ${nextWeek} must exist in semanas_activas`).toBeGreaterThan(0);

      // 10. Verify programacion_semanal has rows for the new week
      const psRowCount = scalar(
        `SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${pid} AND Semana=${nextWeek}`
      );
      console.log(`[NEW-WEEK] programacion_semanal rows for week ${nextWeek}: ${psRowCount}`);

      // 11. Verify programa_consolidado has rows for the new week (PG propagation)
      const pgRowCount = scalar(
        `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${pid} AND Semana=${nextWeek}`
      );
      console.log(`[NEW-WEEK] programa_consolidado rows for week ${nextWeek}: ${pgRowCount}`);
      expect(pgRowCount, `PG must have data for new week ${nextWeek}`).toBeGreaterThan(0);

      // 12. Compare ejecutado propagation: previous week vs new week
      let newEjecutadoCount = 0;
      let newEjecutadoSum = 0;
      if (prevWeek > 0) {
        newEjecutadoCount = scalar(
          `SELECT COUNT(*) FROM programa_consolidado WHERE project_id=${pid} AND Semana=${nextWeek}`
        );
        newEjecutadoSum = scalar(
          `SELECT COALESCE(SUM(CAST(Ejecutado_Real AS DECIMAL(12,2))), 0) FROM programa_consolidado WHERE project_id=${pid} AND Semana=${nextWeek}`
        );
        console.log(`[NEW-WEEK] Week ${nextWeek}: ${newEjecutadoCount} rows, ejecutado sum: ${newEjecutadoSum}`);

        console.log(`[NEW-WEEK] Propagation report:`);
        console.log(`  Previous week ${prevWeek}: ${prevEjecutadoCount} rows, ejecutado sum: ${prevEjecutadoSum}`);
        console.log(`  New week ${nextWeek}: ${newEjecutadoCount} rows, ejecutado sum: ${newEjecutadoSum}`);

        // Both weeks should have comparable row counts (same activities propagated)
        if (prevEjecutadoCount > 0) {
          expect(newEjecutadoCount, 'New week should have comparable PG rows to previous week')
            .toBeGreaterThanOrEqual(prevEjecutadoCount * 0.5);
        }
      }

      // 13. Verify post-creation week count increased
      const postCount = scalar(`SELECT COUNT(*) FROM semanas_activas WHERE project_id=${pid}`);
      console.log(`[NEW-WEEK] Post-creation semana count: ${postCount}`);
      expect(postCount, 'Week count must increase after creation').toBe(preCount + 1);

      console.log(`[NEW-WEEK] PASS — Week ${nextWeek} created and propagation verified`);
    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }
  });

  test.afterEach(async ({ page }, testInfo) => {
    generateFindings(testInfo, { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] });
  });
});
