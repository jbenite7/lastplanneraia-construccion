import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, selectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');
const MANIFEST = JSON.parse(readFileSync(
  new URL('../../docs/design-system/manifests/laboratory.json', import.meta.url),
  'utf8',
));
const APPROVALS = JSON.parse(readFileSync(
  new URL('../../docs/design-system/family-approvals.json', import.meta.url),
  'utf8',
));
const APPROVED_BY_FAMILY = new Map(
  APPROVALS.approvals.map(({ familyId, candidateId }) => [familyId, candidateId]),
);
const FAMILY_COUNT = new Set(MANIFEST.scenarios.map(({ family }) => family)).size;

async function openLaboratory(page, scenario) {
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  const search = new URLSearchParams({ fixture: scenario.fixture });
  await page.goto(`${scenario.route}?${search}`, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
}

async function freezeTheme(page, theme) {
  await page.evaluate((value) => {
    localStorage.setItem('aia-theme', value);
    window.AiaDesignSystem.setTheme(value);
  }, theme);
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', theme);
}

for (const scenario of MANIFEST.scenarios) {
  test(`${scenario.id} remains stable`, async ({ page }) => {
    await openLaboratory(page, scenario);
    const options = page.locator('[data-lab-family] option');
    await expect(options).toHaveCount(FAMILY_COUNT);
    const families = await options.evaluateAll((nodes) => nodes.map((node) => node.value));
    expect(families).toContain(scenario.family);
    expect(scenario.fixture).toBe('approved-family-v1');
    await page.setViewportSize(scenario.viewport);
    await page.locator('#lab-density').selectOption(scenario.density);
    await freezeTheme(page, scenario.theme);
    await page.locator('[data-lab-family]').selectOption(scenario.family);
    const panel = page.locator(`[data-family="${scenario.family}"]`);
    await expect(panel).toBeVisible();
    const approvedCandidateId = APPROVED_BY_FAMILY.get(scenario.family);
    expect(approvedCandidateId, `missing approval for ${scenario.family}`).toBeTruthy();
    await expect(panel).toHaveAttribute(
      'data-active-candidate',
      approvedCandidateId,
    );
    await expect(panel).toHaveAttribute('data-family-status', 'approved');
    await expect(panel).toHaveScreenshot(path.basename(scenario.golden));
  });
}
