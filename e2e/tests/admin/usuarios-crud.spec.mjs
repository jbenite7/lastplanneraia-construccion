/**
 * Admin Usuarios — listar, crear, editar e inactivar.
 * La app no elimina usuarios: conserva trazabilidad y usa Activo/Inactivo.
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

function cleanupUsers() {
  runSql(`
    DELETE pm FROM project_members pm
    JOIN general_usuarios u ON u.id = pm.user_id
    WHERE u.usuario LIKE 'e2ecrud%';
    DELETE FROM general_usuarios WHERE usuario LIKE 'e2ecrud%';
  `);
}

async function setCargo(page, value) {
  await page.locator('#cargo').evaluate((select, cargo) => {
    const option = new Option(cargo, cargo, true, true);
    select.append(option);
    select.dispatchEvent(new Event('change', { bubbles: true }));
  }, value);
}

async function fillUsername(page, value) {
  await page.locator('#usuario').evaluate((input) => input.removeAttribute('readonly'));
  await page.locator('#usuario').fill(value);
}

async function createUserViaUi(page, username, email) {
  await navigateTo(page, '/admin/usuarios/crear');
  await page.locator('#nombre').fill(`E2E Test Usuario ${username}`);
  await page.locator('#email').fill(email);
  await setCargo(page, 'Residente Oficina Tecnica');
  await fillUsername(page, username);
  await page.locator('#password').fill('aia2026');
  await page.locator('.assignment-project').first().selectOption('73');
  await page.locator('.assignment-role').first().selectOption('R');
  await page.locator('#submitBtn').click();
  await expect(page.locator(':text("Usuario creado")')).toBeVisible({ timeout: 15_000 });
  await page.locator('button:has-text("Ir a la lista"), button:has-text("OK")').first().click();
  await page.waitForURL('**/admin/usuarios**', { timeout: 20_000 }).catch(() => {});
}

function userIdByUsername(username) {
  return scalar(`SELECT id FROM general_usuarios WHERE usuario='${username}' LIMIT 1`);
}

test.describe('Admin Usuarios: CRUD completo', () => {
  test.afterEach(async ({ page }, testInfo) => {
    cleanupUsers();
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });

  test('Listar usuarios: verify DataTable columns and buttons', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      await adminLogin(page);
      await navigateTo(page, ADMIN_SELECTORS.usuarios.url);

      await expect(page.locator(ADMIN_SELECTORS.usuarios.heading)).toBeVisible({ timeout: 10_000 });
      for (const col of ADMIN_SELECTORS.usuarios.columns) {
        await expect(page.locator(`th:has-text("${col}"), [role="columnheader"]:has-text("${col}")`).first()).toBeVisible({ timeout: 5_000 });
      }
      await expect(page.locator(ADMIN_SELECTORS.usuarios.buttons.nuevoUsuario)).toBeVisible({ timeout: 5_000 });
      await expect(page.locator(ADMIN_SELECTORS.usuarios.filters.mostrarInactivos).first()).toBeVisible({ timeout: 5_000 });
      expect(scalar('SELECT COUNT(*) FROM general_usuarios')).toBeGreaterThan(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Crear usuario: fill form, save, verify in table and DB', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const username = `e2ecrud${String(Date.now()).slice(-10)}`;
    const email = `e2e-crud-${Date.now()}@test.com`;

    try {
      await adminLogin(page);
      await createUserViaUi(page, username, email);
      await expect(page.locator(`tr:has-text("${username}")`).first()).toBeVisible({ timeout: 10_000 });
      expect(userIdByUsername(username), 'DB must have created user').toBeGreaterThan(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Editar usuario: modify name, save, verify change', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const username = `e2ecrud${String(Date.now()).slice(-10)}`;
    const email = `e2e-crud-${Date.now()}@test.com`;

    try {
      await adminLogin(page);
      await createUserViaUi(page, username, email);
      const userId = userIdByUsername(username);
      expect(userId, 'User exists before edit').toBeGreaterThan(0);

      await navigateTo(page, `/admin/usuarios/editar?id=${userId}`);
      await page.locator('#nombre').fill('E2E Test EDITADO');
      await page.locator('#updateBtn').click();
      await expect(page.getByRole('heading', { name: 'Actualizado' })).toBeVisible({ timeout: 15_000 });
      await page.locator('button:has-text("Continuar"), button:has-text("OK")').first().click();
      await page.waitForURL('**/admin/usuarios**', { timeout: 20_000 }).catch(() => {});

      await expect(page.locator('tr:has-text("E2E Test EDITADO")').first()).toBeVisible({ timeout: 10_000 });
      expect(scalar(`SELECT COUNT(*) FROM general_usuarios WHERE usuario='${username}' AND nombre='E2E Test EDITADO'`)).toBe(1);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Inactivar usuario: update active flag and verify blocked state', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;
    const username = `e2ecrud${String(Date.now()).slice(-10)}`;
    const email = `e2e-crud-${Date.now()}@test.com`;

    try {
      await adminLogin(page);
      await createUserViaUi(page, username, email);
      const userId = userIdByUsername(username);
      expect(userId, 'User exists before inactive update').toBeGreaterThan(0);

      await navigateTo(page, `/admin/usuarios/editar?id=${userId}`);
      await page.locator('#activo').uncheck({ force: true });
      await page.locator('#updateBtn').click();
      await expect(page.getByRole('heading', { name: 'Actualizado' })).toBeVisible({ timeout: 15_000 });
      await page.locator('button:has-text("Continuar"), button:has-text("OK")').first().click();

      expect(scalar(`SELECT activo FROM general_usuarios WHERE usuario='${username}' LIMIT 1`)).toBe(0);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });
});
