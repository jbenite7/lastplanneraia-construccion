import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { ProjectDbSnapshot, countE2ERows } from './support/dbSnapshot.mjs';
import { loginAndSelectProject, logout, changeWeek } from './support/session.mjs';
import { runModuleFlow, validateProjectShell } from './support/moduleFlows.mjs';

for (const project of PROJECTS) {
  test.describe(`Full reusable app flow — ${project.name}`, () => {
    let snapshot;

    test.beforeEach(async ({ page }) => {
      snapshot = new ProjectDbSnapshot(project).capture();
      await loginAndSelectProject(page, project);
    });

    test.afterEach(async ({ page }) => {
      await logout(page).catch(() => {});
      if (snapshot) {
        snapshot.restore();
        snapshot.dispose();
      }
      expect(countE2ERows(), 'Temporary E2E rows must be cleaned up').toBe(0);
    });

    test('desktop shell, context and week switch', async ({ page }) => {
      const errors = installErrorCollectors(page);

      await validateProjectShell(page, project);
      await changeWeek(page, project.maxWeek, '/programa-general');
      await validateProjectShell(page, project);

      assertNoRuntimeErrors(errors);
    });

    test('mobile critical navigation and render', async ({ page }) => {
      const errors = installErrorCollectors(page);
      await page.setViewportSize({ width: 390, height: 844 });

      await validateProjectShell(page, project);
      await expect(page.locator('#drawerToggle')).toBeVisible();
      await page.locator('#drawerToggle').click();
      await expect(page.locator('#aiaNavbar')).toHaveClass(/show/);

      await runModuleFlow(page, project, 'programaGeneral');
      await runModuleFlow(page, project, 'programacionIntermedia');
      await runModuleFlow(page, project, 'subcontratistas');

      assertNoRuntimeErrors(errors);
    });

    for (const moduleName of project.enabledModules) {
      test(`module flow: ${moduleName}`, async ({ page }) => {
        const errors = installErrorCollectors(page);
        await runModuleFlow(page, project, moduleName);
        assertNoRuntimeErrors(errors);
      });
    }
  });
}
