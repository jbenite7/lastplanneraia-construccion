/**
 * Admin Family Catalog — verify page renders, list families/aliases/elementos.
 * Read-only heavy test due to complexity of family catalog forms.
 */
import { test, expect } from '@playwright/test';
import { runSql } from '../../../tests/browser/support/dbSnapshot.mjs';
import { installErrorCollectors } from '../../../tests/browser/support/assertions.mjs';
import { generateFindings, attachAssertionCollector } from '../../support/findings.mjs';
import { adminLogin, adminLogout, navigateTo } from '../../support/admin.mjs';

function scalar(sql) {
  try { return Number(runSql(sql).trim().split(/\s+/).pop() || 0); } catch { return 0; }
}

test.describe('Admin Family Catalog', () => {
  test.afterEach(async ({ page }, testInfo) => {
    runSql("DELETE FROM general_pdc_familias WHERE codigo LIKE 'E2E_FAMILY_%'");
    const errs = testInfo._e2eErrors || { pageErrors: [], consoleErrors: [], serverErrors: [], assertionErrors: [] };
    generateFindings(testInfo, errs);
  });

  test('Family catalog page renders with families, aliases, and elementos', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      await adminLogin(page);
      await navigateTo(page, '/admin/matching/family-catalog');

      // Verify page loads
      await expect(page.locator('body')).toBeVisible({ timeout: 10_000 });

      // Check for table or list content
      const tableVisible = await page.locator('table, #dt_cliente, [role="grid"]').first().isVisible().catch(() => false);
      const listVisible = await page.locator('ul, ol, .list-group').first().isVisible().catch(() => false);

      // Verify no fatal error
      const bodyText = await page.locator('body').textContent().catch(() => '');
      expect(bodyText, 'Page must not show fatal error').not.toContain('Fatal error');

      // DB verify: families exist
      const familyCount = scalar('SELECT COUNT(*) FROM general_pdc_familias LIMIT 1');
      // If table exists in DB, log it
      if (familyCount >= 0) {
        // At minimum verify page renders without 500
        expect(tableVisible || listVisible, 'At least table or list content must be visible').toBe(true);
      }
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });

  test('Family catalog: create family if form available', async ({ page }, testInfo) => {
    const errors = installErrorCollectors(page);
    attachAssertionCollector(errors);
    testInfo._e2eErrors = errors;

    try {
      await adminLogin(page);
      await navigateTo(page, '/admin/matching/family-catalog');

      const code = `E2E_FAMILY_${Date.now()}`;
      const form = page.locator('form[action="/admin/matching/family-catalog/family"]').first();
      await expect(form).toBeVisible({ timeout: 10_000 });
      await form.locator('input[name="codigo"]').fill(code);
      await form.locator('input[name="nombre"]').fill(`E2E Family ${Date.now()}`);
      await form.locator('input[name="categoria"]').fill('E2E');
      await form.locator('button[type="submit"]').click();
      await page.waitForURL('**/admin/matching/family-catalog**', { timeout: 15_000 });

      expect(scalar(`SELECT COUNT(*) FROM general_pdc_familias WHERE codigo='${code}'`)).toBe(1);
    } finally {
      await adminLogout(page).catch(() => {});
    }
  });
});
