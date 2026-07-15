import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { getJson, login, loginAndSelectProject, logout } from './support/session.mjs';
import { installErrorCollectors, assertNoRuntimeErrors } from './support/assertions.mjs';

const project = PROJECTS[0];

const MODULE_ACCESS = [
  { path: '/programa-general', label: /BI Programa/i, target: /\/bi\/programa-general/ },
  { path: '/programacion-intermedia', label: /BI Intermedia/i, target: /\/bi\/intermedia/ },
  { path: '/programacion-semanal', label: /BI Semanal/i, target: /\/bi\/semanal/ },
  { path: '/pdc', label: /BI PDC/i, target: /\/bi\/pdc/ },
  { path: '/subcontratistas', label: /BI (Contratistas|Interesados)/i, target: /\/bi\/contratistas/ },
  { path: '/profesionales', label: /BI Responsables/i, target: /\/bi\/responsables/ },
  { path: '/indicadores', label: /BI Curva S/i, target: /\/bi\/curva-s/ },
];

async function expectBiLinkToNavigate(page, locator, target) {
  await expect(locator).toBeVisible({ timeout: 20000 });
  const href = await locator.getAttribute('href');
  const url = new URL(href, 'http://localhost');
  expect(url.searchParams.get('project_id')).toBe(String(project.projectId));
  expect(url.searchParams.get('semana')).toBeTruthy();

  await Promise.all([
    page.waitForURL(target, { timeout: 20000, waitUntil: 'domcontentloaded' }),
    locator.click(),
  ]);
}

test.describe(`Control Tower access points — ${project.name}`, () => {
  test.afterEach(async ({ page }) => {
    await logout(page).catch(() => {});
  });

  test('shows a Control Tower entry point in the project selector', async ({ page }) => {
    const errors = installErrorCollectors(page);
    await login(page);

    const link = page.locator('a[data-bi-access-link="control-tower"]').filter({ hasText: 'Control Tower' }).first();
    await expect(link).toBeVisible({ timeout: 20000 });
    await expect(link).toHaveAttribute('href', /\/bi\/control-tower/);

    assertNoRuntimeErrors(errors);
  });

  test('opens Control Tower from the main navigation and contextual drawer', async ({ page }) => {
    const errors = installErrorCollectors(page);
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });

    await expect(page.locator('ul.main-links > #biControlTowerNavItem')).toHaveCount(0);
    await page.locator('#informacionGeneral').click();
    const navLink = page.locator('#informacionGeneralMenu a[data-bi-access-link="control-tower"]').first();
    await expectBiLinkToNavigate(page, navLink, /\/bi\/control-tower/);

    await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
    await page.locator('#lps_sidebar_trigger').click();
    const drawerLink = page.locator('#lps_bi_control_tower_card a[data-bi-access-link="control-tower"]');
    await expectBiLinkToNavigate(page, drawerLink, /\/bi\/control-tower/);

    assertNoRuntimeErrors(errors);
  });

  test('rejects unauthorized project IDs in HTML and API', async ({ page }) => {
    await loginAndSelectProject(page, project);
    const projectsResponse = await getJson(page, '/api/bi/projects');
    expect(projectsResponse.ok).toBe(true);
    const allowedId = Number(projectsResponse.payload.projects[0].project_id);
    const unauthorizedId = 999999999;

    const apiResponse = await getJson(
      page,
      '/api/bi/report/programa-general?project_ids[]=' + allowedId
        + '&project_ids[]=' + unauthorizedId,
    );
    expect(apiResponse.status).toBe(403);
    expect(apiResponse.payload.error).toMatch(/permiso/i);

    const htmlResponse = await page.goto(
      '/bi/programa-general?project_ids[]=' + allowedId
        + '&project_ids[]=' + unauthorizedId,
      { waitUntil: 'domcontentloaded' },
    );
    expect(htmlResponse.status()).toBe(403);
    await expect(page.getByRole('heading', { name: 'Error 403' })).toBeVisible();
    await expect(page.locator('body')).toContainText(/permiso/i);
  });

  test('ignores role supplied by query string', async ({ page }) => {
    await loginAndSelectProject(page, project);
    const response = await getJson(
      page,
      '/api/bi/report/programa-general?project_id=' + project.projectId + '&role=C',
    );
    expect(response.ok).toBe(true);
    expect(response.payload.role).not.toBe('C');
  });

  for (const item of MODULE_ACCESS) {
    test(`opens contextual BI view from ${item.path}`, async ({ page }) => {
      const errors = installErrorCollectors(page);
      await loginAndSelectProject(page, project);
      await page.goto(item.path, { waitUntil: 'domcontentloaded' });

      const link = page.getByRole('link', { name: item.label }).first();
      await expectBiLinkToNavigate(page, link, item.target);
      await expect(page.locator('main')).toBeVisible({ timeout: 20000 });

      assertNoRuntimeErrors(errors);
    });
  }
});

const ROLE_ACCESS = [
  { code: 'A', expected: true },
  { code: 'D', expected: true },
  { code: 'R', expected: true },
  { code: 'C', expected: false },
];

test.describe('Control Tower RBAC entry point', () => {
  for (const roleCase of ROLE_ACCESS) {
    test(roleCase.code + ' visibility follows lps.indicadores.ver', async ({ page }) => {
      await login(page, {
        username: 'test.' + roleCase.code,
        password: 'aia2026',
      });
      const links = page.locator('a[data-bi-access-link="control-tower"]');
      if (roleCase.expected) {
        await expect(links.first()).toBeVisible();
      } else {
        await expect(links).toHaveCount(0);
        const response = await getJson(page, '/api/bi/projects');
        expect(response.status).toBe(403);
      }
    });
  }
});
