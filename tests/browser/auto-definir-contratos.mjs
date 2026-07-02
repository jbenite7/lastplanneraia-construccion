import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];

test.describe('Auto-definir contratos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
  });

  test('abre bandeja embebida y genera preview', async ({ page }) => {
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });

    const button = page.locator('#btn_auto_asignar_contratos');
    await expect(button).toBeVisible({ timeout: 10000 });

    const responsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/contratos/auto/preview'),
      { timeout: 60000 },
    );
    await button.click();

    const response = await responsePromise;
    const payload = await response.json();
    expect(payload.respuesta, JSON.stringify(payload)).toBe('BIEN');
    expect(payload.run_id, JSON.stringify(payload)).toBeTruthy();

    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel).toBeVisible({ timeout: 10000 });
    await expect(panel.locator('.sar-analysis')).toContainText('Estamos revisando tus propuestas');
    await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
    await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
    await expect(panel.locator('.sar-group-title')).toContainText([
      'Aplicar automático',
    ]);

    const visibleText = await panel.evaluate((el) => el.innerText);
    const hasCards = await panel.locator('.sar-card').count();
    if (hasCards > 0) {
      expect(visibleText).toMatch(/Alta seguridad|Revisar|No recomendado/);
      expect(visibleText).toContain('Cambios propuestos');
      await panel.locator('.sar-review-btn').first().click();
      await expect(panel.locator('.sar-suggestion-analysis').first()).toContainText('Cómo llegó');
    } else {
      expect(visibleText).toContain('Sin propuestas en este grupo');
    }
    expect(visibleText).not.toContain('Diff');
    expect(visibleText).not.toContain('breadcrumb');
    expect(visibleText).not.toContain('confianza_deteccion');
  });

  test('endpoint auto-define legacy queda retirado', async ({ page }) => {
    const response = await page.request.post('/api/contratos/auto-define', {
      form: { db: project.dbName, semana: String(project.maxWeek) },
    });
    expect(response.status()).toBe(404);
  });
});
