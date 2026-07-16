import { test, expect } from '@playwright/test';
import { GLOBAL_TABLES, OPERATIONAL_PROJECTS } from './fixtures/projects.mjs';
import { installErrorCollectors, assertNoRuntimeErrors } from './support/assertions.mjs';
import { ProjectDbSnapshot } from './support/dbSnapshot.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';
import {
  TemporaryProjectAccess,
  assertProjectHasNoE2ERows,
  cleanupOperationalFixture,
  createOperationalFixture,
  payloadHasServerError,
  runLastPlannerCycle,
  runPurchasingCycle,
} from './support/operationalCycle.mjs';

test('operational fixtures cover the requested projects and weeks', () => {
  expect(OPERATIONAL_PROJECTS.map((project) => ({
    key: project.key,
    projectId: project.projectId,
    operationalWeek: project.operationalWeek,
    purchasingWeek: project.purchasingWeek,
    assistantProgramUniqueId: project.assistantProgramUniqueId,
    constructionOnly: project.constructionOnly,
  }))).toEqual([
    {
      key: 'construction', projectId: 73, operationalWeek: 1, purchasingWeek: 1, assistantProgramUniqueId: 1471, constructionOnly: true,
    },
    {
      key: 'jmc', projectId: 68, operationalWeek: 5, purchasingWeek: 6, assistantProgramUniqueId: 11058, constructionOnly: true,
    },
    {
      key: 'preconstruction-da-porto',
      projectId: 76,
      operationalWeek: 1,
      purchasingWeek: null,
      assistantProgramUniqueId: null,
      constructionOnly: false,
    },
  ]);
});

test('operational response guard rejects SQLSTATE and error-shaped bodies', () => {
  expect([
    payloadHasServerError('SQLSTATE[23000]: integrity constraint violation'),
    payloadHasServerError({ error: 'PDOException while saving' }),
    payloadHasServerError({ success: false }),
  ]).toEqual([true, true, true]);
  expect([
    payloadHasServerError('OK'),
    payloadHasServerError({ respuesta: 'BIEN' }),
    payloadHasServerError({ success: true, data: [] }),
  ]).toEqual([false, false, false]);
});

for (const project of OPERATIONAL_PROJECTS) {
  test(`${project.name}: complete operational cycle restores its baseline`, async ({ page }) => {
    test.setTimeout(project.constructionOnly ? 12 * 60_000 : 7 * 60_000);
    page.setDefaultTimeout(30_000);
    page.setDefaultNavigationTimeout(30_000);
    const access = new TemporaryProjectAccess(project);
    const runtimeErrors = installErrorCollectors(page);
    const fixture = createOperationalFixture(project);
    const snapshots = [];

    try {
      access.grant();
      for (const baselineProject of OPERATIONAL_PROJECTS) {
        const snapshot = new ProjectDbSnapshot(
          baselineProject,
          [...GLOBAL_TABLES, 'contratos_trazabilidad'],
        );
        const baseline = { project: baselineProject, snapshot, fingerprint: null };
        snapshots.push(baseline);
        snapshot.capture();
        baseline.fingerprint = snapshot.fingerprint();
      }

      await loginAndSelectProject(page, project);
      await page.waitForLoadState('networkidle', { timeout: 30_000 });
      await runLastPlannerCycle(page, project, fixture);
      if (project.constructionOnly) {
        await runPurchasingCycle(page, project, {
          ...fixture,
          week: project.purchasingWeek,
        });
      }
      assertNoRuntimeErrors(runtimeErrors);
    } finally {
      await logout(page).catch(() => {});
      const cleanupSteps = [
        () => cleanupOperationalFixture(fixture),
        ...snapshots.flatMap((baseline) => [
          () => {
            if (
              baseline.fingerprint !== null
              && baseline.project.projectId !== project.projectId
            ) {
              expect(
                baseline.snapshot.fingerprint(),
                `${baseline.project.name} must remain isolated from ${project.name}`,
              ).toBe(baseline.fingerprint);
            }
          },
          () => baseline.snapshot.restore(),
          () => {
            if (baseline.fingerprint !== null) {
              expect(
                baseline.snapshot.fingerprint(),
                `${baseline.project.name} restored after ${project.name}`,
              ).toBe(baseline.fingerprint);
            }
          },
          () => baseline.snapshot.dispose(),
        ]),
        () => access.restore(),
        ...OPERATIONAL_PROJECTS.map((baselineProject) => (
          () => assertProjectHasNoE2ERows(baselineProject)
        )),
      ];
      let cleanupFailure;
      for (const cleanup of cleanupSteps) {
        try {
          await cleanup();
        } catch (error) {
          cleanupFailure ??= error;
        }
      }
      if (cleanupFailure) throw cleanupFailure;
    }
  });
}
