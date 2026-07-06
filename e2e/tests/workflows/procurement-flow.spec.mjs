import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { changeWeek, loginAndSelectProject, logout, postFormJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');

async function apiPost(page, url, body, label) {
  const response = await postFormJson(page, url, body);
  const ok = response.ok && (!response.payload.parseError);
  if (!ok) {
    console.log(`[Procurement] ${label}: ${JSON.stringify(response.payload).slice(0, 300)}`);
  }
  return { ok, payload: response.payload };
}

function scalar(sql) {
  try {
    const value = runSql(sql).trim().split(/\s+/).pop();
    return Number(value || 0);
  } catch { return 0; }
}

test.describe('Procurement: Plan de Compras workflow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required');
  });

  test('Da Porto: auto-generate families, contratos semi-auto, PDC semi-auto', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      await loginAndSelectProject(page, PROJECT);
      await changeWeek(page, PROJECT.maxWeek, '/listado-actividades').catch(() => {});

      // 1. Auto-generate families from Programa General
      const famPreview = await apiPost(page, '/api/listado-actividades/auto/preview', {}, 'familias auto preview');
      if (famPreview.ok && famPreview.payload.run_id) {
        findings.push(`Familias auto-generadas: run_id=${famPreview.payload.run_id}, steps=${famPreview.payload.analysis?.steps?.length || 0}`);
        const famApply = await apiPost(page, '/api/listado-actividades/auto/apply', {
          run_id: famPreview.payload.run_id,
        }, 'familias auto apply');
        if (!famApply.ok) findings.push(`Familias auto apply failed: ${JSON.stringify(famApply.payload).slice(0, 200)}`);
      } else {
        findings.push(`Familias auto preview no generó resultados: ${JSON.stringify(famPreview.payload).slice(0, 200)}`);
      }

      // 2. Create 2 manual families
      const timestamp = Date.now();
      for (const tipo of ['Suministro', 'Mano de Obra']) {
        const famCreate = await apiPost(page, '/api/listado-actividades/familia/save', {
          opcion: 'crear',
          nombre_familia: `E2E ${tipo} ${timestamp}`,
          descripcion: `Familia ${tipo} creada por E2E`,
          tipo: tipo,
          fecha_inicio: '2026-07-06',
        }, `crear familia ${tipo}`);
        if (famCreate.ok) {
          findings.push(`Familia ${tipo} creada correctamente`);
          // Clean up
          if (famCreate.payload.id) {
            await apiPost(page, '/api/listado-actividades/familia/save', {
              opcion: 'eliminar',
              id: famCreate.payload.id,
            }, `eliminar familia ${tipo}`).catch(() => {});
          }
        } else {
          findings.push(`Familia ${tipo} NO se pudo crear: ${JSON.stringify(famCreate.payload).slice(0, 200)}`);
        }
      }

      // 3. Contratos — semi-auto preview + apply
      await changeWeek(page, PROJECT.maxWeek, '/contratos').catch(() => {});
      const contrPreview = await apiPost(page, '/api/contratos/auto/preview', {}, 'contratos preview');
      if (contrPreview.ok && contrPreview.payload.run_id) {
        findings.push(`Contratos preview generado: run_id=${contrPreview.payload.run_id}, steps=${contrPreview.payload.analysis?.steps?.length || 0}`);
        const contrApply = await apiPost(page, '/api/contratos/auto/apply', {
          run_id: contrPreview.payload.run_id,
        }, 'contratos apply');
        if (contrApply.ok) {
          findings.push(`Contratos apply exitoso: aplicadas=${contrApply.payload.aplicadas || 'N/A'}`);
        } else {
          findings.push(`Contratos apply falló: ${JSON.stringify(contrApply.payload).slice(0, 200)}`);
        }
      } else {
        findings.push(`Contratos preview no generó: ${JSON.stringify(contrPreview.payload).slice(0, 200)}`);
      }

      // 4. PDC — semi-auto preview + apply
      await changeWeek(page, PROJECT.maxWeek, '/pdc').catch(() => {});
      const pdcPreview = await apiPost(page, '/api/pdc/auto/preview', {}, 'PDC preview');
      if (pdcPreview.ok && pdcPreview.payload.run_id) {
        findings.push(`PDC preview generado: run_id=${pdcPreview.payload.run_id}, steps=${pdcPreview.payload.analysis?.steps?.length || 0}`);
        const pdcApply = await apiPost(page, '/api/pdc/auto/apply', {
          run_id: pdcPreview.payload.run_id,
        }, 'PDC apply');
        if (pdcApply.ok) {
          findings.push(`PDC apply exitoso: aplicadas=${pdcApply.payload.aplicadas || 'N/A'}`);
        } else {
          findings.push(`PDC apply falló: ${JSON.stringify(pdcApply.payload).slice(0, 200)}`);
        }

        // Verify PDC rows exist
        const pdcCount = scalar(`SELECT COUNT(*) FROM pdc WHERE project_id=${PROJECT.projectId}`);
        if (pdcCount > 0) {
          findings.push(`Filas PDC en DB: ${pdcCount}`);
        } else {
          findings.push('No hay filas PDC generadas en la base de datos');
        }

        // Verify PDC list endpoint
        const pdcList = await apiPost(page, '/api/pdc/list', { semana: PROJECT.maxWeek }, 'PDC list');
        if (pdcList.ok && pdcList.payload.data?.length > 0) {
          findings.push(`PDC list endpoint: ${pdcList.payload.data.length} filas`);
        } else {
          findings.push(`PDC list endpoint sin datos: ${JSON.stringify(pdcList.payload).slice(0, 200)}`);
        }
      } else {
        findings.push(`PDC preview no generó: ${JSON.stringify(pdcPreview.payload).slice(0, 200)}`);
      }

      // Log all findings
      console.log(`\n[Procurement] Findings:`);
      findings.forEach((f) => console.log(`  ${f}`));

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    // Attach findings to errors so afterEach includes them
    errors.assertionErrors = errors.assertionErrors || [];
    errors.findings = findings;
    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });
});