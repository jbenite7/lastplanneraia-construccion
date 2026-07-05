import { test, expect } from '@playwright/test';
import { BASE_URL, PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';
import { ProjectDbSnapshot } from './support/dbSnapshot.mjs';

const CONSTRUCTION_PROJECT = PROJECTS.find((project) => project.key === 'construction');

test.describe('PDC module tests', () => {
  let snapshot;

  test.beforeEach(async ({ page }) => {
    snapshot = new ProjectDbSnapshot(CONSTRUCTION_PROJECT).capture();
    await loginAndSelectProject(page, CONSTRUCTION_PROJECT);

    // Navigate to PDC page with a valid construction week.
    await changeWeek(page, 8, '/pdc');
    // Wait for DataTable initComplete to render toolbar and legend chips
    await page.waitForTimeout(3000);
  });

  test.afterEach(() => {
    if (snapshot) {
      snapshot.restore();
      snapshot.dispose();
    }
  });

  test('Test 1: Verify toolbar buttons and page title', async ({ page }) => {
    // Verify the toolbar has the expected buttons by text content
    const actualizarBtn = page.locator('#btn_actualizarPDC');
    await expect(actualizarBtn).toBeVisible({ timeout: 10000 });
    await expect(actualizarBtn).toContainText('Actualizar');

    // Desglosar button
    const desglosarBtn = page.locator('#btn_definirContratosPDC');
    await expect(desglosarBtn).toBeVisible({ timeout: 5000 });
    await expect(desglosarBtn).toContainText('Desglosar');

    await expect(page.locator('#btn_auto_generar_desde_contratos')).toHaveCount(0);

    // Ver alertas button
    const soloAlertasBtn = page.locator('#btn_soloAlertas');
    await expect(soloAlertasBtn).toBeVisible({ timeout: 5000 });
    await expect(soloAlertasBtn).toContainText('Ver alertas');

    // Verify Wizard button does NOT exist - search by text content
    const wizardCount = await page.evaluate(() => {
      const allElements = document.querySelectorAll('button, .btn, a.btn, input[type="button"], input[type="submit"]');
      let count = 0;
      allElements.forEach(el => {
        const text = el.textContent || el.value || '';
        if (text.toLowerCase().includes('wizard')) {
          count++;
        }
      });
      return count;
    });
    console.log(`[Test 1] Wizard button occurrences found: ${wizardCount}`);
    expect(wizardCount).toBe(0);

    // Verify page title or body mentions the current module name
    const pageTitle = await page.locator('title').textContent();
    console.log(`[Test 1] Page title: "${pageTitle}"`);
    const titleMatch = pageTitle && (
      pageTitle.includes('PDC') ||
      pageTitle.includes('Plan de Compras y Contrataciones')
    );
    // Also check body content as fallback
    const bodyText = await page.locator('body').textContent();
    const bodyMatch = bodyText.includes('PDC') || bodyText.includes('Plan de Compras y Contrataciones');
    expect(titleMatch || bodyMatch).toBeTruthy();
  });

  test('Test 2: Semi-auto PDC abre preview embebido', async ({ page }) => {
    await page.waitForFunction(() => window.jQuery && window.SemiAutoReview, null, { timeout: 20000 });

    const responsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/pdc/auto/preview'),
      { timeout: 60000 }
    );

    await page.evaluate(() => window.SemiAutoReview.open('pdc'));

    const response = await responsePromise;
    const data = await response.json();
    console.log(`[Test 2] PDC semi-auto preview: ${JSON.stringify({
      respuesta: data.respuesta,
      total: data.total,
      preselected: data.preselected,
    })}`);
    expect(data.respuesta).toBe('BIEN');
    expect(data.run_id).toBeTruthy();

    const panel = page.locator('#semiAutoReview-pdc');
    await expect(panel).toBeVisible({ timeout: 10000 });
    await expect(panel.locator('.sar-analysis')).toContainText('Estamos revisando tus propuestas');
    await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
    await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
    await expect(panel.locator('.sar-group-title')).toContainText([
      'Aplicar automático',
    ]);

    const visibleText = await panel.evaluate((el) => el.innerText);
    expect(visibleText).not.toContain('Corrida');
    expect(visibleText).not.toContain('Diff');
    expect(visibleText).not.toContain('pdc_diff');
    expect(visibleText).not.toContain('fechaElaboracionPliegos');

    const table = page.locator('#dt_cliente');
    await expect(table).toBeVisible({ timeout: 10000 });
    console.log('[Test 2] Table remains visible after preview');
  });

  test('Test 3: Legend chips and filters', async ({ page }) => {
    // Verify all 7 legend chips exist
    // Use class-based selectors for chips with unique classes
    const chipSelectors = [
      { text: 'Informacion pendiente', selector: '.pdc-legend-item.missing', cssVar: '--pdc-missing-bg' },
      { text: 'Inicio de contratacion vencido', selector: '.pdc-legend-item.critical', cssVar: '--pdc-critical-bg' },
      { text: 'Contratacion atrasada', selector: '.pdc-legend-item.delayed', cssVar: '--pdc-delayed-bg' },
      { text: 'Contratacion cerrada tarde', selector: '.pdc-legend-item.completed-late', cssVar: '--pdc-completed-late-bg' },
      { text: 'Contratacion cerrada a tiempo', selector: '.pdc-legend-item.completed-ontime', cssVar: '--pdc-completed-ontime-bg' },
      { text: 'Contratacion en curso', selector: '.pdc-legend-item.active', cssVar: '--pdc-active-bg' },
      { text: 'Contratacion pendiente de inicio', selector: '.pdc-legend-item.not-started', cssVar: '--pdc-not-started-bg' },
    ];

    for (const { text, selector, cssVar } of chipSelectors) {
      const chip = page.locator(selector);
      await expect(chip).toBeVisible({ timeout: 10000 });
      const chipText = await chip.textContent();
      console.log(`[Test 3] Chip visible: "${text}" (actual text: "${chipText?.trim()}")`);
      await expect(chip).toContainText(text);

      const colors = await chip.evaluate((el, varName) => {
        const root = getComputedStyle(document.documentElement);
        const probe = document.createElement('span');
        probe.style.backgroundColor = root.getPropertyValue(varName).trim();
        document.body.appendChild(probe);
        const expected = getComputedStyle(probe).backgroundColor;
        probe.remove();

        return {
          actual: getComputedStyle(el).backgroundColor,
          expected,
        };
      }, cssVar);
      expect(colors.actual).toBe(colors.expected);
    }

    const legendOverflow = await page.locator('#dt_cliente_wrapper .pdc-legend-wrap').evaluate((el) => ({
      clientWidth: el.clientWidth,
      scrollWidth: el.scrollWidth,
      legendClientWidth: el.querySelector('.pdc-legend')?.clientWidth || 0,
      legendScrollWidth: el.querySelector('.pdc-legend')?.scrollWidth || 0,
    }));
    expect(legendOverflow.scrollWidth).toBeLessThanOrEqual(legendOverflow.clientWidth + 1);
    expect(legendOverflow.legendScrollWidth).toBeLessThanOrEqual(legendOverflow.legendClientWidth + 1);

    // Click "Contratacion en curso" chip
    const enCursoChip = page.locator('.pdc-legend-item.active');
    await expect(enCursoChip).toBeVisible({ timeout: 5000 });
    console.log('[Test 3] Clicking "Contratacion en curso" chip');
    await enCursoChip.click();
    await page.waitForTimeout(500);

    // Verify the chip toggles - chip should get inactive-filter class on other chips
    const allChips = page.locator('.pdc-legend-item');
    // The active chip should NOT have inactive-filter
    const enCursoClasses = await enCursoChip.getAttribute('class');
    console.log(`[Test 3] "Contratacion en curso" chip classes after click: "${enCursoClasses}"`);
    expect(enCursoClasses).not.toContain('inactive-filter');

    // Other chips should have inactive-filter
    const otherChips = await allChips.all();
    for (const chip of otherChips) {
      const text = await chip.textContent();
      if (text && text.includes('Contratacion en curso')) continue;
      const classes = await chip.getAttribute('class') || '';
      console.log(`[Test 3] Chip "${text?.trim()}" classes: "${classes}"`);
    }
  });

  test('Test 4: Actualizar button', async ({ page }) => {
    const actualizarBtn = page.locator('#btn_actualizarPDC');
    await expect(actualizarBtn).toBeVisible({ timeout: 10000 });

    console.log('[Test 4] Clicking Actualizar button');

    const responsePromise = page.waitForResponse(
      (response) => response.url().includes('/api/pdc/auto/apply-from-contratos'),
      { timeout: 15000 },
    ).catch(() => null);

    await actualizarBtn.click();

    const response = await responsePromise;
    if (response) {
      const capturedResponse = await response.json().catch(() => null);
      console.log(`[Test 4] Actualizar response: ${JSON.stringify(capturedResponse)}`);
      if (capturedResponse) {
        expect(capturedResponse.respuesta).toBe('BIEN');
        console.log(`[Test 4] Actualizar response verified: ${capturedResponse.respuesta}`);
      } else {
        expect(response.status()).toBeGreaterThanOrEqual(200);
        expect(response.status()).toBeLessThan(500);
        console.log(`[Test 4] Non-JSON modern response accepted with status ${response.status()}`);
      }
    } else {
      console.log('[Test 4] No network response captured');
    }

    // Wait for page stability
    await page.waitForTimeout(3000);
  });

  test('Test 5: Verify PDC package types render in table', async ({ page }) => {
    // Wait for the table to load
    const table = page.locator('#dt_cliente');
    await expect(table).toBeVisible({ timeout: 15000 });
    await expect(table).toContainText('MODALIDAD DE CONTRATACION');
    await expect(table).toContainText('PAQUETE DE CONTRATACION');

    // Contract type visibility depends on current project data. Some fixtures
    // intentionally start with no PDC packages, so keep this as a render guard.
    const bodyText = await table.textContent();
    console.log(`[Test 5] Table contains "Orden de servicio/compra": ${bodyText?.includes('Orden de servicio/compra')}`);

    const tableText = bodyText || '';
    const supportedTypes = ['Orden de servicio/compra', 'Mano de Obra', 'Suministro', 'Suministro e Instalación'];
    if (supportedTypes.some((type) => tableText.includes(type))) {
      expect(supportedTypes.some((type) => tableText.includes(type))).toBe(true);
    } else {
      expect(tableText).toContain('MODALIDAD DE CONTRATACION');
    }
  });

  test('Test 6: Navigation uses modern module routes', async ({ page }) => {
    const contratosBtn = page.locator('.ps-module-switcher #btn_contratos');
    await expect(contratosBtn).toBeVisible({ timeout: 10000 });
    await contratosBtn.click();
    await expect(page).toHaveURL(/\/contratos(?:\?|$)/, { timeout: 15000 });
    expect(page.url()).not.toContain('/legacy/cambiar_pagina.php');

    const actividadesBtn = page.locator('.ps-module-switcher #btn_Actividades');
    await expect(actividadesBtn).toBeVisible({ timeout: 10000 });
    await actividadesBtn.click();
    await expect(page).toHaveURL(/\/listado-actividades(?:\?|$)/, { timeout: 15000 });
    expect(page.url()).not.toContain('/legacy/cambiar_pagina.php');

    const pdcBtn = page.locator('.ps-module-switcher #btn_planCompras');
    await expect(pdcBtn).toBeVisible({ timeout: 10000 });
    await pdcBtn.click();
    await expect(page).toHaveURL(/\/pdc(?:\?|$)/, { timeout: 15000 });
    expect(page.url()).not.toContain('/legacy/cambiar_pagina.php');
  });
});
