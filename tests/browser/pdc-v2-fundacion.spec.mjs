import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout, getJson } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test('la isla React del PDC v2 monta con contexto real del proyecto', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    const response = await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    expect(response?.status(), '/plan-compras debe responder').toBeLessThan(400);

    // El shell inyectó el bootstrap para la SPA.
    const bootstrap = await page.evaluate(() => window.__PDC_BOOTSTRAP__);
    expect(bootstrap?.projectId).toBe(project.projectId);
    expect(String(bootstrap?.csrfToken || '')).toHaveLength(64);

    // La SPA montó y muestra el contexto.
    await expect(page.locator('[data-testid="pdc-contexto"]')).toContainText(project.name, { timeout: 15000 });

    // AG Grid renderizó la fila del proyecto.
    await expect(
      page.locator('.ag-cell').filter({ hasText: `${project.projectId} — ${project.name}` }),
    ).toBeVisible({ timeout: 15000 });

    // La ruta HTTP real del endpoint responde envelope ok con el proyecto activo.
    const ctx = await getJson(page, '/plan-compras/api/contexto');
    expect(ctx.status, 'contexto debe responder 200').toBe(200);
    expect(ctx.payload?.ok).toBe(true);
    expect(ctx.payload?.data?.projectId).toBe(project.projectId);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
