import { test, expect } from '@playwright/test';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { adminLogin, adminLogout, assertAdminDashboard, ADMIN_CREDENTIALS } from '../../support/admin.mjs';
import { ADMIN_SELECTORS } from '../../support/moduleSelectors.mjs';

test.describe('Admin Panel: login, dashboard, and navigation', () => {
  test.afterEach(async ({ page }, testInfo) => {
    const errors = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errors);
  });

  test('Admin login shows dashboard with stat cards, sidebar, and breadcrumb', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      // 1. Login via helper
      await adminLogin(page, ADMIN_CREDENTIALS);

      // 2. Verify URL is /admin/
      await expect(page).toHaveURL(/\/admin\//, { timeout: 15_000 });

      // 3. Assert heading + sidebar nav items
      await assertAdminDashboard(page);

      // 4. Verify stat cards are visible (values may change — check labels only)
      const { statCards } = ADMIN_SELECTORS.dashboard;
      for (const cardLabel of statCards) {
        await expect(
          page.locator(`text=${cardLabel}`).first(),
          `Stat card "${cardLabel}" must be visible`,
        ).toBeVisible({ timeout: 5_000 });
      }

      // 5. Verify sidebar nav items visible (assertAdminDashboard already covers this,
      //    but we repeat explicitly for the spec requirement)
      const { sidebarItems } = ADMIN_SELECTORS.dashboard;
      for (const item of sidebarItems) {
        await expect(
          page.locator(`a:has-text("${item}"), button:has-text("${item}")`).first(),
          `Sidebar item "${item}" must be visible`,
        ).toBeVisible({ timeout: 5_000 });
      }

      // 6. Verify breadcrumb (flexible match for different admin panel versions)
      const breadcrumb = page.locator('.breadcrumb, nav[aria-label="breadcrumb"], [class*="breadcrumb"]').first();
      await expect(breadcrumb, 'Admin breadcrumb must be visible').toBeVisible({ timeout: 5_000 });

      // 7. Verify heading "Panel de Control"
      await expect(
        page.locator(ADMIN_SELECTORS.dashboard.heading),
        'Heading "Panel de Control" must be visible',
      ).toBeVisible({ timeout: 5_000 });
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Admin logout redirects to login page with form visible', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      // 1. Login first
      await adminLogin(page, ADMIN_CREDENTIALS);
      await expect(page).toHaveURL(/\/admin\//, { timeout: 15_000 });

      // 2. Logout
      await adminLogout(page);

      // 3. Verify redirect to /admin/login
      await expect(page).toHaveURL(/\/admin\/login/, { timeout: 15_000 });

      // 4. Verify login form is visible
      await expect(
        page.getByRole('textbox', { name: 'Usuario' }),
        'Username field must be visible after logout',
      ).toBeVisible({ timeout: 10_000 });
      await expect(
        page.getByRole('textbox', { name: 'Contraseña' }),
        'Password field must be visible after logout',
      ).toBeVisible({ timeout: 5_000 });
      await expect(
        page.getByRole('button', { name: 'Ingresar' }),
        'Submit button must be visible after logout',
      ).toBeVisible({ timeout: 5_000 });
    } finally {
      // Ensure clean state even if test fails mid-way
      await adminLogout(page).catch(() => {});
    }
  });

  test('Protected admin route /admin/proyectos redirects to login when not authenticated', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    // 1. Navigate directly to a protected admin route WITHOUT logging in
    await page.goto(ADMIN_SELECTORS.proyectos.url, { waitUntil: 'commit', timeout: 30_000 });

    // 2. Verify redirect to /admin/login
    await expect(page).toHaveURL(/\/admin\/login/, { timeout: 15_000 });

    // 3. Verify login form is visible
    await expect(
      page.getByRole('textbox', { name: 'Usuario' }),
      'Username field must be visible on redirect',
    ).toBeVisible({ timeout: 10_000 });
  });
});
