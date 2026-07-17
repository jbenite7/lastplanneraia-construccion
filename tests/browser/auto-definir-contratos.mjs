import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { ProjectDbSnapshot, runSql } from './support/dbSnapshot.mjs';
import { changeWeek, loginAndSelectProject, postFormJson } from './support/session.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];
const AUTOMATION_TABLES = [
  'actividades',
  'semi_auto_assistant_feedback',
  'semi_auto_decisions',
  'semi_auto_feedback',
  'semi_auto_learning_candidates',
  'semi_auto_learning_rules',
  'semi_auto_project_config',
  'semi_auto_proactive_queue',
  'semi_auto_runs',
  'semi_auto_suggestions',
];

function activityFingerprint() {
  const scoped = new ProjectDbSnapshot(project, ['actividades']).capture();
  const fingerprint = scoped.fingerprint();
  scoped.dispose();
  return fingerprint;
}

async function openPreview(page) {
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/contratos/auto/preview')
      && response.request().method() === 'POST',
    { timeout: 90_000 },
  );
  await page.locator('#btn_auto_asignar_contratos').click();
  const response = await responsePromise;
  const payload = await response.json();
  expect(response.ok(), JSON.stringify(payload)).toBe(true);
  expect(payload.respuesta, JSON.stringify(payload)).toBe('BIEN');
  expect(payload.run_id, JSON.stringify(payload)).toBeTruthy();
  return payload;
}

async function contractsData(page) {
  const response = await postFormJson(
    page,
    `/api/contratos/list?semana=${project.maxWeek}`,
    {},
  );
  expect(response.status, JSON.stringify(response.payload)).toBe(200);
  return response.payload.data || [];
}

test.describe('Auto-definir contratos', () => {
  let snapshot;
  let baselineFingerprint;

  test.beforeEach(async ({ page }) => {
    snapshot = new ProjectDbSnapshot(project, AUTOMATION_TABLES).capture();
    baselineFingerprint = snapshot.fingerprint();
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto(`/contratos?semana=${project.maxWeek}`, {
      waitUntil: 'networkidle',
      timeout: 30_000,
    });
  });

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) await page.close();
    if (!snapshot) return;
    snapshot.restore();
    expect(snapshot.fingerprint()).toBe(baselineFingerprint);
    snapshot.dispose();
    snapshot = null;
  });

  test('abre bandeja embebida y genera preview', async ({ page }) => {
    const payload = await openPreview(page);

    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel).toBeVisible({ timeout: 10000 });
    await expect(panel.locator('.sar-analysis')).toContainText('Estamos revisando tus propuestas');
    await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
    await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
    await expect(panel.locator('.sar-tab')).toContainText([
      'Decisión',
      'Listas',
      'Todo',
    ]);
    await expect(panel.locator('.sar-filter-band')).toBeVisible();

    const visibleText = await panel.evaluate((el) => {
      const copy = el.cloneNode(true);
      copy.querySelector('.sar-tech-wrap')?.remove();
      return copy.innerText;
    });
    const hasCards = await panel.locator('.sar-card').count();
    if (hasCards > 0) {
      expect(visibleText).toMatch(/Alta seguridad|Revisar|No recomendado/);
      expect(visibleText).toMatch(/Definir contratos|Necesita decisión|Revisar manualmente/);
      expect(visibleText).toContain('Cambios propuestos');
      expect(visibleText).toMatch(/Qué pasa|Requiere revisión/);
      await panel.locator('.sar-review-btn').first().click();
      await expect(panel.locator('.sar-suggestion-analysis').first()).toContainText('Cómo llegó');
    } else {
      expect(visibleText).toContain('Sin propuestas en este grupo');
    }
    expect(visibleText).not.toContain('Diff');
    expect(visibleText).not.toContain('breadcrumb');
    expect(visibleText).not.toContain('confianza_deteccion');

    await panel.getByRole('button', { name: 'Listas', exact: true }).click();
    const readyCards = panel.locator('.sar-card.is-ready');
    expect(await readyCards.count()).toBeGreaterThan(0);
    const readyCard = readyCards.first();
    await expect(readyCard.locator('.sar-contract-editor')).toBeVisible();
    expect(await readyCard.innerText()).not.toMatch(/Cantidad (?:SI|S|MO|OC)\d/);
    await expect(readyCard).toContainText('# Contratos');
    await expect(readyCard).not.toContainText('# Actividades');

    const resourceTags = readyCard.locator('.sar-contract-row:not(.sar-hidden) .sar-resource-tags').first();
    await expect(resourceTags).toHaveAttribute('multiple', '');
    const resourcePills = resourceTags.locator('xpath=following-sibling::span[contains(@class,"select2")]');
    await resourcePills.click();
    const resourceSearch = page.locator('.select2-container--open .select2-search__field');
    await resourceSearch.fill('Cable de prueba');
    const feedbackRequest = page.waitForRequest((request) => request.url().includes('/api/contratos/auto/feedback'));
    await resourceSearch.press('Enter');
    const corrected = (await feedbackRequest).postDataJSON().corrected;
    expect(Object.values(corrected)).toEqual(['Cable de prueba']);
    await expect(resourcePills).toContainText('Cable de prueba');
  });

  test('revisa, edita, selecciona, aplica, recarga, persiste y deshace', async ({ page }) => {
    const beforeActivities = activityFingerprint();
    const preview = await openPreview(page);
    const target = (preview.suggestions || []).find((suggestion) => suggestion.preselected);
    expect(target, JSON.stringify(preview.analysis || preview)).toBeTruthy();

    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel).toBeVisible();
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await panel.getByRole('button', { name: 'Listas', exact: true }).click();

    const targetChoice = panel.locator(`.sar-row-check[value="${target.suggestion_id}"]`);
    const targetCard = targetChoice.locator('xpath=ancestor::article[contains(@class,"sar-card")]');
    await expect(targetCard).toBeVisible();
    await targetCard.locator('.sar-review-btn').click();
    await expect(targetCard.locator('.sar-contract-editor')).toBeVisible();

    const packageInput = targetCard.locator(
      '.sar-contract-row:not(.sar-hidden) input[data-field^="paquete"]',
    ).first();
    await expect(packageInput).toBeVisible();
    const packageField = await packageInput.getAttribute('data-field');
    const originalValue = String(target.current?.[packageField] ?? '');
    const sentinel = `E2E AUTO CONTRATOS ${Date.now()}`;
    const feedbackPromise = page.waitForResponse(
      (response) => response.url().includes('/api/contratos/auto/feedback')
        && response.request().method() === 'POST',
      { timeout: 30_000 },
    );
    await packageInput.fill(sentinel);
    await packageInput.dispatchEvent('change');
    const feedback = await (await feedbackPromise).json();
    expect(feedback.respuesta, JSON.stringify(feedback)).toBe('BIEN');
    expect(feedback.updated_suggestion, JSON.stringify(feedback)).toBe(true);
    await expect(panel.locator('.sar-status')).toContainText('Ajuste guardado');

    const choices = panel.locator('.sar-row-check:not(:disabled)');
    const choiceCount = await choices.count();
    for (let index = 0; index < choiceCount; index += 1) {
      await choices.nth(index).setChecked(false);
    }
    await targetChoice.check();
    await expect(panel.locator('.sar-btn-apply')).toBeEnabled();
    const applyPromise = page.waitForResponse(
      (response) => response.url().includes('/api/contratos/auto/apply')
        && response.request().method() === 'POST',
      { timeout: 90_000 },
    );
    await panel.locator('.sar-btn-apply').click();
    const applied = await (await applyPromise).json();
    const applyErrors = Number(applied.errores || 0) > 0
      ? runSql(`SELECT result_payload FROM semi_auto_decisions WHERE run_id='${String(applied.run_id).replaceAll("'", "''")}' AND decision='error'`)
      : '';
    expect(applied.respuesta, JSON.stringify(applied)).toBe('BIEN');
    expect(Number(applied.aplicadas), `${JSON.stringify(applied)} ${applyErrors}`).toBe(1);
    expect(Number(applied.errores || 0), applyErrors).toBe(0);
    expect(activityFingerprint()).not.toBe(beforeActivities);

    await page.reload({ waitUntil: 'networkidle' });
    const persisted = (await contractsData(page)).find(
      (row) => String(row.Id) === String(target.target_pk),
    );
    expect(persisted, `No se encontro la actividad ${target.target_pk}`).toBeTruthy();
    expect(String(persisted[packageField] ?? '')).toBe(sentinel);
    const persistedFingerprint = activityFingerprint();
    expect(persistedFingerprint).not.toBe(beforeActivities);

    const refreshPreviewPromise = page.waitForResponse(
      (response) => response.url().includes('/api/contratos/auto/preview')
        && response.request().method() === 'POST',
      { timeout: 90_000 },
    );
    await page.locator('#btn_auto_asignar_contratos').click();
    const refreshedPreview = await (await refreshPreviewPromise).json();
    expect(refreshedPreview.respuesta).toBe('BIEN');
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    const naturalUndoState = await page.evaluate(() => {
      const db = document.querySelector('#baseDatos')?.value || '';
      const week = document.querySelector('#semana')?.value || document.querySelector('#Max_Semana')?.value || '';
      const key = `aia.semiAuto.undo.contratos.${db}.${week}`;
      return { key, runId: window.sessionStorage.getItem(key) };
    });
    expect(naturalUndoState.runId).toBe(applied.run_id);
    await expect(panel.locator('.sar-btn-undo')).toBeEnabled();

    // Reproduce el estado observado en navegador tras recargar: el puntero de
    // undo puede quedar asociado al preview más reciente. El backend debe
    // resolver la última aplicación real, nunca responder éxito con 0 cambios.
    const undoStorageKey = await page.evaluate(() => {
      const db = document.querySelector('#baseDatos')?.value || '';
      const week = document.querySelector('#semana')?.value || document.querySelector('#Max_Semana')?.value || '';
      return `aia.semiAuto.undo.contratos.${db}.${week}`;
    });
    await page.evaluate(({ key, previewRunId }) => {
      if (!window.sessionStorage.getItem(key)) throw new Error('No se encontró el puntero exacto de undo de contratos');
      window.sessionStorage.setItem(key, previewRunId);
    }, { key: undoStorageKey, previewRunId: refreshedPreview.run_id });
    await page.reload({ waitUntil: 'networkidle' });
    const previewBeforeUndoPromise = page.waitForResponse(
      (response) => response.url().includes('/api/contratos/auto/preview')
        && response.request().method() === 'POST',
      { timeout: 90_000 },
    );
    await page.locator('#btn_auto_asignar_contratos').click();
    expect((await (await previewBeforeUndoPromise).json()).respuesta).toBe('BIEN');
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await expect(panel.locator('.sar-btn-undo')).toBeEnabled();
    const undoRequestPromise = page.waitForRequest(
      (request) => request.url().includes('/api/contratos/auto/undo')
        && request.method() === 'POST',
      { timeout: 90_000 },
    );
    const undoPromise = page.waitForResponse(
      (response) => response.url().includes('/api/contratos/auto/undo')
        && response.request().method() === 'POST',
      { timeout: 90_000 },
    );
    await panel.locator('.sar-btn-undo').click();
    const undoRequest = await undoRequestPromise;
    expect(undoRequest.postDataJSON().run_id).toBe(refreshedPreview.run_id);
    const undone = await (await undoPromise).json();
    expect(undone.respuesta, JSON.stringify(undone)).toBe('BIEN');
    expect(Number(undone.revertidas), JSON.stringify(undone)).toBe(1);
    expect(Number(undone.errores || 0), JSON.stringify(undone)).toBe(0);
    expect(undone.run_id).toBe(applied.run_id);
    await expect.poll(() => activityFingerprint(), { timeout: 90_000 }).toBe(beforeActivities);

    await page.reload({ waitUntil: 'networkidle' });
    const restored = (await contractsData(page)).find(
      (row) => String(row.Id) === String(target.target_pk),
    );
    expect(restored, `No se encontro la actividad restaurada ${target.target_pk}`).toBeTruthy();
    expect(String(restored[packageField] ?? '')).toBe(originalValue);
  });

  test('endpoint auto-define legacy queda retirado', async ({ page }) => {
    const response = await page.request.post('/api/contratos/auto-define', {
      form: { db: project.dbName, semana: String(project.maxWeek) },
    });
    expect(response.status()).toBe(404);
  });

  test('una aplicación parcial conserva y habilita Deshacer', async ({ page }) => {
    const preview = await openPreview(page);
    const panel = page.locator('#semiAutoReview-contratos');
    await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90_000 });
    await panel.getByRole('button', { name: 'Listas', exact: true }).click();
    const choice = panel.locator('.sar-row-check:not(:disabled)').first();
    await expect(choice).toBeVisible();
    await choice.check();

    await page.route('**/api/contratos/auto/apply*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          respuesta: 'BIEN',
          run_id: preview.run_id,
          aplicadas: 1,
          errores: 1,
        }),
      });
    });
    await panel.locator('.sar-btn-apply').click();
    await expect(panel.locator('.sar-status')).toContainText('terminó parcialmente');
    await expect(panel.locator('.sar-btn-undo')).toBeEnabled();

    const exactKeyValue = await page.evaluate(() => {
      const db = document.querySelector('#baseDatos')?.value || '';
      const week = document.querySelector('#semana')?.value || document.querySelector('#Max_Semana')?.value || '';
      return window.sessionStorage.getItem(`aia.semiAuto.undo.contratos.${db}.${week}`);
    });
    expect(exactKeyValue).toBe(preview.run_id);
  });
});
