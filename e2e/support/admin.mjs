/**
 * Admin panel authentication and navigation helpers.
 *
 * The admin panel (`/admin/`) uses a separate session from the main app.
 * Login goes through `/admin/login` with its own form.
 *
 * Usage:
 *   import { adminLogin, adminLogout, navigateTo } from '../../support/admin.mjs';
 */

import { expect } from '@playwright/test';

/** Default admin credentials for testing. test.A is Admin role but may not have admin panel access. jbenitez is System Admin. */
export const ADMIN_CREDENTIALS = {
  username: 'jbenitez',
  password: 'Jbe#1106z',
};

/**
 * Login to the admin panel via `/admin/login`.
 *
 * DOM (validated 2026-07-05):
 *   textbox "Usuario"      → fill
 *   textbox "Contraseña"   → fill
 *   button "Ingresar"       → click
 *   redirect to /admin/
 *
 * @param {import('@playwright/test').Page} page
 * @param {object} [credentials] - { username, password }
 */
export async function adminLogin(page, credentials = ADMIN_CREDENTIALS) {
  await page.goto('/admin/login', { waitUntil: 'commit', timeout: 30_000 });
  await page.getByRole('textbox', { name: 'Usuario' }).waitFor({ state: 'visible', timeout: 15_000 });
  await page.getByRole('textbox', { name: 'Usuario' }).fill(credentials.username);
  await page.getByRole('textbox', { name: 'Contraseña' }).fill(credentials.password);
  await page.getByRole('button', { name: 'Ingresar' }).click();
  await page.waitForURL('**/admin/', { timeout: 30_000 }).catch(() => {});
}

/**
 * Logout from admin panel.
 * Clicks "Salir" in sidebar and expects redirect to /admin/login.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function adminLogout(page) {
  const salir = page.locator('a:has-text("Salir"), button:has-text("Salir")').first();
  await salir.waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});
  await salir.click();
  await page.waitForURL('**/admin/login', { timeout: 15_000 }).catch(() => {});
}

/**
 * Navigate to an admin path after login.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} path - Admin relative path (e.g., '/admin/usuarios')
 */
export async function navigateTo(page, path) {
  await page.goto(path, { waitUntil: 'commit', timeout: 30_000 });
  await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => {});
}

/**
 * Verify admin dashboard is loaded.
 * Checks heading "Panel de Control" and sidebar nav items.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function assertAdminDashboard(page) {
  await expect(page.locator('h1:has-text("Panel de Control")')).toBeVisible({ timeout: 15_000 });
  const navItems = ['Dashboard', 'Proyectos', 'Usuarios', 'Catálogo Familias', 'Matching Config'];
  for (const item of navItems) {
    await expect(
      page.locator(`a:has-text("${item}"), button:has-text("${item}")`).first(),
      `Admin nav item "${item}" must be visible`,
    ).toBeVisible({ timeout: 5_000 });
  }
}