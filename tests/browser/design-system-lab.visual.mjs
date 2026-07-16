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
const STATES_FEEDBACK_FAMILY = 'states-feedback';

function contrastRatio(foreground, background) {
  const luminance = (color) => {
    const channels = color.match(/[\d.]+/g).slice(0, 3).map(Number).map((channel) => {
      const normalized = channel / 255;
      return normalized <= 0.03928
        ? normalized / 12.92
        : ((normalized + 0.055) / 1.055) ** 2.4;
    });
    return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
  };
  const foregroundLuminance = luminance(foreground);
  const backgroundLuminance = luminance(background);
  return (Math.max(foregroundLuminance, backgroundLuminance) + 0.05)
    / (Math.min(foregroundLuminance, backgroundLuminance) + 0.05);
}

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

async function assertStatesFeedbackVisualContract(page, panel) {
  const status = panel.locator('[data-ui-group="loading-spinner"]');
  const spinner = status.locator('.aia-spinner');
  const label = status.locator('span').last();

  await expect(status).toBeVisible();
  await expect(status).toHaveAttribute('role', 'status');
  await expect(status).toHaveAttribute('aria-live', 'polite');
  await expect(status).toContainText('Carga indeterminada');
  await expect(spinner).toBeVisible();
  await expect(spinner).toHaveAttribute('aria-hidden', 'true');

  const contract = await status.evaluate((element) => {
    const panelElement = element.closest('[data-family="states-feedback"]');
    const spinnerElement = element.querySelector('.aia-spinner');
    const labelElement = element.querySelector('span:last-child');
    const statusRect = element.getBoundingClientRect();
    const spinnerRect = spinnerElement.getBoundingClientRect();
    const labelRect = labelElement.getBoundingClientRect();
    const panelRect = panelElement.getBoundingClientRect();
    const style = getComputedStyle(element);

    return {
      foreground: style.color,
      background: style.backgroundColor,
      pageOverflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      panelOverflowX: panelElement.scrollWidth - panelElement.clientWidth,
      spinnerWidth: spinnerRect.width,
      spinnerHeight: spinnerRect.height,
      spinnerInsideStatus: spinnerRect.left >= statusRect.left - 1
        && spinnerRect.right <= statusRect.right + 1
        && spinnerRect.top >= statusRect.top - 1
        && spinnerRect.bottom <= statusRect.bottom + 1,
      statusInsidePanel: statusRect.left >= panelRect.left - 1
        && statusRect.right <= panelRect.right + 1,
      spinnerPrecedesLabel: spinnerRect.right <= labelRect.left + 1,
      centerDelta: Math.abs(
        (spinnerRect.top + (spinnerRect.height / 2))
        - (labelRect.top + (labelRect.height / 2)),
      ),
    };
  });

  expect(contrastRatio(contract.foreground, contract.background)).toBeGreaterThanOrEqual(4.5);
  expect(contract.pageOverflowX).toBeLessThanOrEqual(1);
  expect(contract.panelOverflowX).toBeLessThanOrEqual(1);
  expect(contract.spinnerWidth).toBe(24);
  expect(contract.spinnerHeight).toBe(24);
  expect(contract.spinnerInsideStatus).toBe(true);
  expect(contract.statusInsidePanel).toBe(true);
  expect(contract.spinnerPrecedesLabel).toBe(true);
  expect(contract.centerDelta).toBeLessThanOrEqual(1);
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
    if (scenario.family === STATES_FEEDBACK_FAMILY) {
      await assertStatesFeedbackVisualContract(page, panel);
      return;
    }
    await expect(panel).toHaveScreenshot(path.basename(scenario.golden));
  });
}
