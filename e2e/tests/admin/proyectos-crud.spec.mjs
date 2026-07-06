/**
 * Admin Proyectos CRUD — listar, crear, editar, eliminar.
 * Usa el flujo real del admin: formularios de pagina y tablas globales.
 */
import { test, expect } from '@playwright/test';
import { runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { adminLogin, adminLogout, navigateTo } from '../../support/admin.mjs';
import { ADMIN_SELECTORS } from '../../support/moduleSelectors.mjs';

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

function cleanupProjects() {
  runSql(`
    DELETE sa FROM semanas_activas sa
    JOIN general_proyectos_procesos p ON p.Id = sa.project_id
    WHERE p.Proyecto_Proceso LIKE 'E2E Test Proyecto%';
    DELETE pm FROM project_members pm
    JOIN general_proyectos_procesos p ON p.Id = pm.project_id
    WHERE p.Proyecto_Proceso LIKE 'E2E Test Proyecto%';
    DELETE FROM general_proyectos_procesos
    WHERE Proyecto_Proceso LIKE 'E2E Test Proyecto%';
  `);
}

async function createProjectViaUi(page, name) {
  await navigateTo(page, '/admin/proyectos/crear');
  await page.locator('input[name="nombre"]').fill(name);
  await page.locator('select[name="area"]').selectOption('Construccion');
  await page.locator('input[name="fecha_inicio_lb"]').fill('2026-07-06');
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/admin/proyectos**', { timeout: 20_000 });
  await expect(page.locator(`tr:has-text("${name}")`).first()).toBeVisible({ timeout: 10_000 });
}

function projectIdByName(name) {
  return scalar(`SELECT Id FROM general_proyectos_procesos WHERE Proyecto_Proceso='${name}' LIMIT 1`);
}

test.describe('Admin Proyectos: CRUD completo', () => {
  test.afterEach(async ({ page }, testInfo) => {
    cleanupProjects();
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });

  test('Listar proyectos: verify DataTable, columns, export buttons', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      await adminLogin(page);
      await navigateTo(page, ADMIN_SELECTORS.proyectos.url);

      await expect(page.locator(ADMIN_SELECTORS.proyectos.heading)).toBeVisible({ timeout: 10_000 });
      for (const col of ADMIN_SELECTORS.proyectos.columns) {
        await expect(page.locator(`th:has-text("${col}"), [role="columnheader"]:has-text("${col}")`).first()).toBeVisible({ timeout: 5_000 });
      }
      await expect(page.locator(ADMIN_SELECTORS.proyectos.buttons.nuevoProyecto)).toBeVisible({ timeout: 5_000 });
      expect(scalar('SELECT COUNT(*) FROM general_proyectos_procesos')).toBeGreaterThan(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Crear proyecto: fill form, save, verify in table', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const name = `E2E Test Proyecto ${Date.now()}`;

    try {
      await adminLogin(page);
      await createProjectViaUi(page, name);
      expect(projectIdByName(name), 'DB must have created project').toBeGreaterThan(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Editar proyecto: modify, save, verify change', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const name = `E2E Test Proyecto ${Date.now()}`;
    const editedName = `${name} Editado`;

    try {
      await adminLogin(page);
      await createProjectViaUi(page, name);

      await page.locator(`tr:has-text("${name}") a[title="Editar"]`).first().click();
      await page.locator('input[name="nombre"]').fill(editedName);
      await page.locator('button[type="submit"]').click();
      await page.waitForURL('**/admin/proyectos**', { timeout: 20_000 });

      await expect(page.locator(`tr:has-text("${editedName}")`).first()).toBeVisible({ timeout: 10_000 });
      expect(projectIdByName(editedName), 'DB must have edited project').toBeGreaterThan(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Eliminar proyecto: delete, verify removed', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const name = `E2E Test Proyecto ${Date.now()}`;

    try {
      await adminLogin(page);
      await createProjectViaUi(page, name);
      const projectId = projectIdByName(name);
      expect(projectId, 'Project exists before delete').toBeGreaterThan(0);

      const csrf = await page.locator('#deleteForm input[name="csrf_token"]').inputValue();
      const response = await page.evaluate(async ({ projectId, csrf }) => {
        const body = new URLSearchParams({ id: String(projectId), csrf_token: csrf });
        const res = await fetch('/admin/proyectos/eliminar', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
          redirect: 'manual',
        });
        return { status: res.status, ok: res.status >= 200 && res.status < 400 };
      }, { projectId, csrf });

      expect(response.ok || response.status === 0, JSON.stringify(response)).toBe(true);
      await navigateTo(page, ADMIN_SELECTORS.proyectos.url);
      await expect(page.locator(`tr:has-text("${name}")`)).toHaveCount(0);
      expect(projectIdByName(name), 'DB must not have deleted project').toBe(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });
});
