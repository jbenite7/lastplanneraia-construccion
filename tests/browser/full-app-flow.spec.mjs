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

      // El shell de sidebar (DS-027) retiró el navbar superior y con él su cajón
      // mobile: `#drawerToggle` / `#aiaNavbar` ya no existen en ninguna vista
      // (views/partials/shell_sidebar.php + DesignSystemComponent::sidebarNavigation()
      // solo emiten `[data-shell-pattern="sidebar"]` con `[data-destination-id]`).
      // En viewports estrechos la navegación crítica ES el rail colapsado: fijo a
      // la izquierda, 4rem de ancho, con los destinos como iconos accionables.
      // Mismo criterio que el arreglo del caso desktop en assertions.mjs.
      const rail = page.locator('[data-shell-pattern="sidebar"]');
      await expect(rail).toBeVisible();
      await expect(rail).toHaveAttribute('data-sidebar-state', 'collapsed');

      // El rail no puede tapar el contenido ni empujarlo fuera del viewport.
      const railLayout = await page.evaluate(() => {
        const aside = document.querySelector('[data-shell-pattern="sidebar"]');
        return {
          railWidth: Math.round(aside.getBoundingClientRect().width),
          bodyOffset: Math.round(Number.parseFloat(getComputedStyle(document.body).paddingLeft)),
          scrollWidth: document.documentElement.scrollWidth,
          viewportWidth: window.innerWidth,
        };
      });
      expect(railLayout.bodyOffset).toBe(railLayout.railWidth);
      expect(railLayout.scrollWidth).toBeLessThanOrEqual(railLayout.viewportWidth);

      // Cada destino visible del proyecto sigue siendo alcanzable con el dedo.
      for (const destinationId of project.expectedVisibleNav) {
        const destination = rail.locator(`[data-destination-id="${destinationId}"]`);
        await expect(destination, `Destino mobile ${destinationId}`).toBeVisible();
        const box = await destination.boundingBox();
        expect(box, `Destino mobile ${destinationId} sin caja`).not.toBeNull();
        expect(box.height, `Destino mobile ${destinationId} bajo el mínimo táctil`).toBeGreaterThanOrEqual(44);
      }

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
