import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];

const modules = [
  { key: 'listado-actividades', url: '/listado-actividades' },
  { key: 'contratos', url: '/contratos' },
  { key: 'pdc', url: '/pdc' },
];

async function openReviewPanel(page, moduleKey) {
  await page.waitForFunction(() => window.jQuery && window.SemiAutoReview, null, { timeout: 20000 });
  await page.evaluate((key) => window.SemiAutoReview.open(key), moduleKey);

  const panel = page.locator(`#semiAutoReview-${moduleKey}`);
  await expect(panel).toBeVisible({ timeout: 10000 });
  await expect(panel.locator('.sar-filter-band')).toBeVisible();
  await expect(panel.locator('.sar-filter-text')).toBeVisible();
  await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 30000 });
  await expect(panel.locator('.sar-analysis')).toContainText('Proceso de análisis');
  await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
  await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
  await expect(panel.locator('.sar-group-title')).toContainText([
    'Listo para aplicar',
    'Requiere revisión',
    'Conflictos',
  ]);

  const visibleText = await panel.evaluate((el) => el.innerText);
  if (await panel.locator('.sar-review-btn').count() > 0) {
    await panel.locator('.sar-review-btn').first().click();
    await expect(panel.locator('.sar-suggestion-analysis').first()).toContainText('Cómo llegó');
  }
  expect(visibleText).not.toContain('Corrida');
  expect(visibleText).not.toContain('Diff');
  expect(visibleText).not.toContain('breadcrumb');
  expect(visibleText).not.toContain('pdc_diff');
  expect(visibleText).not.toContain('confianza_deteccion');
  expect(visibleText).not.toContain('fechaElaboracionPliegos');
  expect(visibleText).not.toContain('tipoContrato');
  expect(visibleText).not.toContain('M_O,S');
}

test.describe('Semi-auto review panel', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/programacion-semanal');
  });

  for (const module of modules) {
    test(`preview embedded panel in ${module.key}`, async ({ page }) => {
      await page.goto(module.url, { waitUntil: 'networkidle', timeout: 30000 });
      await openReviewPanel(page, module.key);
    });
  }

  test('admin can open technical detail without showing it by default', async ({ page }) => {
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });
    await openReviewPanel(page, 'contratos');

    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel.locator('.sar-tech-btn')).toBeVisible();
    await expect(panel.locator('.sar-tech-wrap')).not.toBeVisible();

    await panel.locator('.sar-tech-btn').click();
    await expect(panel.locator('.sar-tech-wrap')).toBeVisible();
    await expect(panel.locator('.sar-tech-wrap')).toContainText('run_id');
    await expect(panel.locator('.sar-tech-wrap')).toContainText('trace');
  });

  test('non-admin role does not see technical detail', async ({ page }) => {
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });
    await page.locator('#permiso_canonico').evaluate((el) => { el.value = 'R'; });
    await openReviewPanel(page, 'contratos');

    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel.locator('.sar-tech-btn')).toHaveCount(0);
    const visibleText = await panel.evaluate((el) => el.innerText);
    expect(visibleText).not.toContain('run_id');
  });
});
