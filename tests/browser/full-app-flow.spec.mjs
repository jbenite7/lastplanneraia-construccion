import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { ContainerFileSnapshot } from './support/containerFileSnapshot.mjs';
import { DatabaseSnapshot } from './support/dbSnapshot.mjs';
import {
  E2ERestorationScope,
  assertE2EMutationConsent,
  maybeInjectE2EFailure,
} from './support/restoration.mjs';
import { loginAndSelectProject, logout, changeWeek } from './support/session.mjs';
import { runModuleFlow, validateProjectShell } from './support/moduleFlows.mjs';

assertE2EMutationConsent();

for (const project of PROJECTS) {
  test.describe(`Full reusable app flow — ${project.name}`, () => {
    let restoration;

    test.beforeEach(async ({ page }) => {
      restoration = new E2ERestorationScope(
        new DatabaseSnapshot(),
        new ContainerFileSnapshot('/var/www/html/public/storage'),
      );
      restoration.capture();
      await loginAndSelectProject(page, project);
    });

    test.afterEach(async ({ page }) => {
      const cleanupErrors = [];
      try {
        await logout(page);
      } catch (error) {
        cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
      }
      if (restoration) {
        try {
          const receipt = restoration.restore();
          console.info(`E2E_RESTORATION_RECEIPT ${JSON.stringify(receipt)}`);
        } catch (error) {
          cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
        }
        try {
          restoration.dispose();
        } catch (error) {
          cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
        }
        restoration = null;
      }
      if (cleanupErrors.length > 0) {
        throw new AggregateError(cleanupErrors, 'Full app E2E cleanup failed.');
      }
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
        maybeInjectE2EFailure(`full-app-flow:module:${moduleName}`);
        assertNoRuntimeErrors(errors);
      });
    }
  });
}
