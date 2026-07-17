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

/** Admin panel credentials supplied explicitly by the E2E environment. */
export const ADMIN_CREDENTIALS = {
  username: process.env.E2E_ADMIN_USERNAME,
  password: process.env.E2E_ADMIN_PASSWORD,
};

function requireAdminCredentials(credentials) {
  if (!credentials?.username || !credentials?.password) {
    throw new Error(
      'Admin E2E credentials are required: set E2E_ADMIN_USERNAME and E2E_ADMIN_PASSWORD.',
    );
  }
  return credentials;
}

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
  const resolvedCredentials = requireAdminCredentials(credentials);
  await page.goto('/admin/login', { waitUntil: 'commit', timeout: 30_000 });
  await page.getByRole('textbox', { name: 'Usuario' }).waitFor({ state: 'visible', timeout: 15_000 });
  await page.getByRole('textbox', { name: 'Usuario' }).fill(resolvedCredentials.username);
  await page.getByRole('textbox', { name: 'Contraseña' }).fill(resolvedCredentials.password);
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
