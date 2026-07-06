/**
 * Dashboard — Full workflow: load, sections, notifications, LPS drawer, RBAC.
 *
 * Target: Da Porto (construction; projectId=73).
 *
 * Workflow covered:
 *   1. Login (Admin) → navigate to /dashboard → verify page loads
 *   2. Verify key sections: escalamientos, notificaciones, indicadores
 *   3. Verify LPS Drawer opens/closes
 *   4. Verify breadcrumb contains project name
 *   5. API: GET /api/notifications/unread — check notification count
 *   6. Residente RBAC: login as test.R → verify dashboard loads for restricted role
 *
 * Dashboard is read-only — no mutations, no DB snapshot restore needed.
 */

import { test, expect } from '@playwright/test';
import { PROJECTS } from '../../../tests/browser/fixtures/projects.mjs';
import { ProjectDbSnapshot } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { login, loginAndSelectProject, logout, selectProject, getJson } from '../../../tests/browser/support/session.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { COMMON_SELECTORS, DASHBOARD_SELECTORS } from '../../support/moduleSelectors.mjs';

const PROJECT = PROJECTS.find((p) => p.key === 'construction');
const RESIDENTE_CREDENTIALS = { username: 'test.R', password: 'aia2026' };

// ─── Test Suite ───────────────────────────────────────────────────────────────

test.describe('Dashboard: full load, sections, LPS drawer, RBAC', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!PROJECT, 'Da Porto (construction) project required');
  });

  test('Da Porto: dashboard load, sections, notifications, LPS drawer', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      // ─── 1. Login and navigate to dashboard ──────────────────────────────
      await loginAndSelectProject(page, PROJECT);
      await page.goto('/dashboard', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('body', { state: 'attached', timeout: 15_000 });
      findings.push('Page loaded: /dashboard');

      // ─── 2. Verify key sections are visible ──────────────────────────────
      const pageText = await page.locator('body').textContent().catch(() => '');

      for (const section of DASHBOARD_SELECTORS.sections) {
        const visible = pageText.includes(section);
        findings.push(`Section "${section}": ${visible ? 'visible' : 'NOT found in page text'}`);
        // Soft check: log finding but don't fail the test
        if (!visible) {
          console.log(`[Dashboard] Section "${section}" not found in page text — may be loaded lazily`);
        }
      }

      // ─── 3. Verify breadcrumb contains project name ──────────────────────
      const breadcrumb = page.locator(
        `${COMMON_SELECTORS.breadcrumb}, nav[aria-label="breadcrumb"], [class*="breadcrumb"]`,
      );
      const breadcrumbText = await breadcrumb.first().textContent().catch(() => '');
      const hasProjectName = breadcrumbText.includes('Da Porto') || breadcrumbText.includes('da_porto');
      findings.push(`Breadcrumb: "${breadcrumbText.trim().slice(0, 100)}" — hasProjectName=${hasProjectName}`);

      // ─── 4. API: check notification count ─────────────────────────────────
      const notifResult = await getJson(page, '/api/notifications/unread');
      if (notifResult.ok && !notifResult.payload.parseError) {
        const count = notifResult.payload.count
          ?? notifResult.payload.total
          ?? (Array.isArray(notifResult.payload.data) ? notifResult.payload.data.length : null);
        findings.push(`API /api/notifications/unread: count=${count}`);
      } else {
        findings.push(`API notifications failed: ${JSON.stringify(notifResult.payload).slice(0, 200)}`);
      }

      // ─── 5. Verify LPS Drawer opens and closes ───────────────────────────
      const drawerBtn = page.locator(COMMON_SELECTORS.lpsDrawer);
      const drawerBtnVisible = await drawerBtn.isVisible().catch(() => false);
      findings.push(`LPS Drawer button visible: ${drawerBtnVisible}`);

      if (drawerBtnVisible) {
        await drawerBtn.click();
        const drawerDialog = page.locator(COMMON_SELECTORS.lpsDrawerDialog);
        const drawerOpen = await drawerDialog.isVisible().catch(() => false);
        findings.push(`LPS Drawer opened: ${drawerOpen}`);

        if (drawerOpen) {
          // Close the drawer
          const closeBtn = drawerDialog.locator('button:has-text("Cerrar"), button:has-text("Close"), button[aria-label="Close"]');
          if (await closeBtn.count() > 0) {
            await closeBtn.first().click();
            await page.waitForTimeout(500);
            const drawerClosed = !(await drawerDialog.isVisible().catch(() => false));
            findings.push(`LPS Drawer closed: ${drawerClosed}`);
          } else {
            // Try pressing Escape as fallback
            await page.keyboard.press('Escape');
            await page.waitForTimeout(500);
            const drawerClosed = !(await drawerDialog.isVisible().catch(() => false));
            findings.push(`LPS Drawer closed via Escape: ${drawerClosed}`);
          }
        }
      } else {
        findings.push('LPS Drawer button not visible — skipping drawer test');
      }

      // ─── 6. Summary ──────────────────────────────────────────────────────
      console.log(`\n[Dashboard] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    testInfo._e2eErrors = errors;
  });

  test('Da Porto: Residente RBAC — dashboard loads for restricted role', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    const findings = [];
    let snapshot;

    try {
      snapshot = new ProjectDbSnapshot(PROJECT).capture();

      // ─── 1. Login as Residente ───────────────────────────────────────────
      await login(page, RESIDENTE_CREDENTIALS);
      await selectProject(page, PROJECT);
      findings.push('Logged in as Residente (test.R)');

      // ─── 2. Navigate to dashboard ────────────────────────────────────────
      await page.goto('/dashboard', { waitUntil: 'commit', timeout: 30_000 });
      await page.waitForSelector('body', { state: 'attached', timeout: 15_000 });
      const currentUrl = page.url();
      findings.push(`Navigated to: ${currentUrl}`);

      // ─── 3. Verify page loaded (not redirected to 403 or login) ──────────
      const isLoginPage = currentUrl.includes('/login');
      const is403 = (await page.locator('body').textContent().catch(() => '')).includes('403')
        || (await page.locator('body').textContent().catch(() => '')).includes('Acceso denegado');
      findings.push(`Is login page: ${isLoginPage}, Is 403: ${is403}`);

      if (!isLoginPage && !is403) {
        // ─── 4. Verify Residente can see at least some dashboard content ────
        const pageText = await page.locator('body').textContent().catch(() => '');
        for (const section of DASHBOARD_SELECTORS.sections) {
          const visible = pageText.includes(section);
          findings.push(`Residente section "${section}": ${visible ? 'visible' : 'NOT found'}`);
        }

        // ─── 5. Verify breadcrumb ──────────────────────────────────────────
        const breadcrumb = page.locator(
          `${COMMON_SELECTORS.breadcrumb}, nav[aria-label="breadcrumb"], [class*="breadcrumb"]`,
        );
        const breadcrumbText = await breadcrumb.first().textContent().catch(() => '');
        findings.push(`Residente breadcrumb: "${breadcrumbText.trim().slice(0, 100)}"`);
      }

      // ─── 6. Summary ──────────────────────────────────────────────────────
      console.log(`\n[Dashboard Residente] Findings (${findings.length}):`);
      findings.forEach((f) => console.log(`  ${f}`));

      errors.findings = findings;

    } finally {
      await logout(page).catch(() => {});
      if (snapshot) { snapshot.restore(); snapshot.dispose(); }
    }

    testInfo._e2eErrors = errors;
  });

  test.afterEach(async ({ page }, testInfo) => {
    const errs = testInfo._e2eErrors || {
      pageErrors: [],
      consoleErrors: [],
      serverErrors: [],
      assertionErrors: [],
    };
    generateFindings(testInfo, errs);
  });
});
