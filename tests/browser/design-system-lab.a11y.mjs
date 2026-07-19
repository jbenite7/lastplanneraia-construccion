import { expect, test } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, selectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');

test('every approved family has no blocking axe findings in the required matrix', async ({ page }, testInfo) => {
  const helper = await import('./support/accessibility.mjs');
  expect(typeof helper.scanAccessibility).toBe('function');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  const homologation = JSON.parse(await readFile(
    new URL('../../docs/design-system/homologation.json', import.meta.url), 'utf8',
  ));
  const scenarios = helper.approvedAccessibilityScenarios(homologation)
    .filter((scenario) => scenario.theme === 'dark' && scenario.size.width >= 1180);
  expect(scenarios).toHaveLength(20);
  const matrixBlocking = [];
  const contrastSamples = [];

  for (const scenario of scenarios) {
    await page.evaluate((theme) => localStorage.setItem('aia-theme', theme), scenario.theme);
    await page.setViewportSize(scenario.size);
    await page.goto(`/internal/design-system?family=${scenario.family}`);
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', scenario.theme);
    const report = await helper.scanAccessibility(page, {
      surface: `lab/${scenario.family}/${scenario.theme}/${scenario.viewport}`,
      include: `[data-family="${scenario.family}"]`,
      reportPath: testInfo.outputPath(
        `axe-${scenario.family}-${scenario.theme}-${scenario.viewport}.json`,
      ),
    });
    expect(report.designSystemVersion).toBe(homologation.designSystemVersion);
    if (report.blocking.length === 0) continue;
    const contrastSample = await page.locator(report.blocking[0].selector).first().evaluate((element) => ({
      selector: element.outerHTML.slice(0, 120),
      color: getComputedStyle(element).color,
      background: getComputedStyle(element).backgroundColor,
      actionText: getComputedStyle(element).getPropertyValue('--ds-active-action-text').trim(),
      actionBackground: getComputedStyle(element).getPropertyValue('--ds-active-action-primary').trim(),
      ancestors: [element.parentElement, element.parentElement?.parentElement,
        element.parentElement?.parentElement?.parentElement, element.closest('figure')]
        .filter(Boolean).map((ancestor) => ({
          tag: ancestor.tagName, className: ancestor.className,
          color: getComputedStyle(ancestor).color,
          background: getComputedStyle(ancestor).backgroundColor,
        })),
    }));
    matrixBlocking.push(...report.blocking);
    contrastSamples.push({ surface: report.surface, sample: contrastSample });
  }
  expect(matrixBlocking, JSON.stringify(contrastSamples)).toEqual([]);
});
