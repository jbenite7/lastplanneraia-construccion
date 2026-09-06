import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

/**
 * Tarea 9 (T01), paso 3: navegación real (sin red interceptada, contra el backend del stack)
 * entre el shell React (`/app`) y una ruta PHP no migrada (`/proyectos`), probando que la
 * sesión same-origin sobrevive el viaje de ida y vuelta y que el salto entre los dos mundos es
 * una navegación de página completa — no un enrutamiento cliente de React Router capturando una
 * URL que no le corresponde.
 *
 * `/proyectos` (ProjectSelectorController) se eligió como la ruta PHP no migrada porque no
 * depende de contexto de semana/DataScope — a diferencia de `/programa-general`, que sin pasar
 * antes por `changeWeek()` (ver `tests/browser/support/session.mjs`) dispara
 * `ProjectSqlGuard::"Alias de tabla de proyecto ambiguo"` en `semanas_activas`. Ese comportamiento
 * es real y preexistente, ajeno a esta tarea: se documenta aquí para que quien lea este spec no
 * lo reintroduzca sin querer.
 *
 * A diferencia de `shell-runtime-react-errores.spec.mjs` y hermanos (Tarea 8), este spec NO
 * intercepta `/api/**`: la convivencia es justamente lo que se prueba, así que necesita sesión y
 * backend reales — la puerta de desarrollo (`/dev/entrar`, ver AGENTS.md §Seguridad), nunca
 * `/login` con credenciales tecleadas.
 *
 * Marcador de "es la vista PHP legada, no el shell": el HTML del shell siempre trae
 * `<div id="root"></div>` (`public/app/index.html`); ninguna vista legada del sitio lo tiene.
 */

test.describe('Shell React — coexistencia con rutas PHP no migradas', () => {
  test('navegar de /app a /proyectos y volver conserva la sesión y usa navegación completa', async ({ page }) => {
    const project = PROJECTS.find((candidato) => candidato.key === 'construction') ?? PROJECTS[0];

    await loginAndSelectProject(page, project, { username: 'test.R', password: 'aia2026' });

    // 1. Entra al shell React por deep link directo — sesión ya autenticada por la puerta de
    // desarrollo, sin tocar /login.
    await page.goto('/app');
    await expect(page.locator('#root')).toBeVisible();
    await expect(page.getByRole('navigation')).toBeVisible({ timeout: 45000 });

    // 2. Salta a una ruta PHP no migrada. Debe ser una navegación de página completa: 'load' del
    // frame principal solo se dispara en una recarga real de documento, nunca en un cambio de
    // ruta interno de React Router.
    const navegacionCompletaAlLegado = page.waitForEvent('load');
    await page.goto('/proyectos');
    await navegacionCompletaAlLegado;

    await expect(page).toHaveTitle(/Seleccionar Proyecto/);
    await expect(page.locator('#root')).toHaveCount(0);
    // Same-origin session continuity: /proyectos no redirigió a /login (ProjectSelectorController
    // manda a /login si `$_SESSION['usuario']` no está fijado) — el propio `page.goto` ya hubiera
    // reportado otra URL final si la sesión se hubiera perdido en el salto.
    expect(new URL(page.url()).pathname).toBe('/proyectos');
    await expect(page.locator('.project-item').first()).toBeVisible({ timeout: 45000 });

    // 3. Vuelta al shell React: la sesión sigue viva del lado PHP, sin flash de login.
    const navegacionCompletaAlShell = page.waitForEvent('load');
    await page.goto('/app');
    await navegacionCompletaAlShell;

    await expect(page.locator('#root')).toBeVisible();
    await expect(page.getByRole('navigation')).toBeVisible({ timeout: 45000 });
    await expect(page.getByLabel(/usuario|contraseña/i)).toHaveCount(0);
  });
});
