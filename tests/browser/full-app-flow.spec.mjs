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
      //
      // CORREGIDO el 2026-08-19. Esta comprobacion exigia
      // `bodyOffset === railWidth`, es decir que el `body` reservara sitio para
      // el carril. Eso dejo de ser cierto el 2026-08-14 con el menu flotante
      // (`docs/superpowers/specs/2026-08-14-shell-menu-flotante-responsive-design.md`),
      // que decide justo lo contrario: por debajo de 1180 px el `aside` sale
      // del flujo y «el `body` pierde el `padding-left`».
      //
      // El test se quedo con el contrato viejo y **puso el CI en rojo desde
      // entonces**: 64 esperado contra 0 recibido, en toda corrida de `main`.
      // No es una regresion disfrazada: medido a 390 px, el carril lleva
      // `transform: translateX(-64px)` -esta FUERA de la pantalla, no encima
      // del contenido-, `scrollWidth` es igual al viewport, y quien responde en
      // el pixel donde estaria el carril es el contenido de la pagina. Se
      // comprobo mirando la captura, no solo el CSS.
      //
      // Asi que la asercion pasa a medir lo que de verdad importaba y el
      // enunciado ya decia: que el carril **no tape** el contenido y que no
      // haya desbordamiento horizontal.
      const railLayout = await page.evaluate(() => {
        const aside = document.querySelector('[data-shell-pattern="sidebar"]');
        const caja = aside.getBoundingClientRect();
        // A quien alcanza el dedo en la franja del carril: si responde el
        // propio carril, esta tapando; si responde la pagina, no.
        const enLaFranja = document.elementFromPoint(
          Math.max(1, Math.round(caja.width / 2)),
          Math.round(window.innerHeight / 2),
        );
        return {
          railWidth: Math.round(caja.width),
          bodyOffset: Math.round(Number.parseFloat(getComputedStyle(document.body).paddingLeft)),
          scrollWidth: document.documentElement.scrollWidth,
          viewportWidth: window.innerWidth,
          railTapaElContenido: Boolean(enLaFranja && aside.contains(enLaFranja)),
        };
      });
      // Fuera del flujo: el cuerpo no reserva sitio para el carril flotante.
      expect(railLayout.bodyOffset).toBe(0);
      // Y sobre todo, no tapa lo que hay debajo.
      expect(railLayout.railTapaElContenido).toBe(false);
      expect(railLayout.scrollWidth).toBeLessThanOrEqual(railLayout.viewportWidth);

      // Con el carril fuera de pantalla, la navegacion critica pasa por su
      // disparador propio. Que exista y abra es parte de que la navegacion sea
      // alcanzable: sin el, el menu flotante seria inalcanzable con el dedo.
      //
      // El selector es `.shell-menu-trigger` y no `[data-shell-drawer-toggle]`:
      // ese segundo lo emite `DesignSystemComponent::navigation()`, y estas
      // vistas montan `views/partials/shell_sidebar.php`, que trae el suyo.
      // Medido en el DOM real a 390 px, no deducido de la spec.
      const disparador = page.locator('button.shell-menu-trigger');
      await expect(disparador, 'el menu flotante necesita su disparador por debajo de 1180 px').toBeVisible();
      await expect(disparador).toHaveAttribute('aria-expanded', 'false');
      await disparador.click();
      await expect(disparador).toHaveAttribute('aria-expanded', 'true');

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
