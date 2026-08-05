import { test, expect } from '@playwright/test';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

const JMC = { name: 'Optimización Aeropuerto JMC' };
const VIEWPORT = { width: 1440, height: 900 };

async function openDarkWeek(page) {
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, JMC);
  await changeWeek(page, 6, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  // F0/Task 9: theme.js ya no expone setTheme; dark se aplica sin conmutacion.
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
  await expect(page.locator('#hot-container .ht_master.handsontable')).toBeVisible();
}

function rgbLightness(value) {
  const channels = String(value).match(/[\d.]+/g)?.slice(0, 3).map(Number) || [];
  return channels.length === 3 ? channels.reduce((sum, item) => sum + item, 0) / 3 : 255;
}

test('modal de actividad manual usa superficies dark coherentes', async ({ page }) => {
  await openDarkWeek(page);
  await page.locator('#btn_agregar_actividad').click();
  const modal = page.locator('#formulario_nuevo');
  await expect(modal).toBeVisible();
  const metrics = await modal.evaluate((node) => [
    '.modal-content', '.modal-body', '.ps-card-excepciones',
    '.ps-card-excepciones__header', '.ps-card-excepciones__body',
    '#tabla_excepciones_no_autoprogramadas thead th',
    '#tabla_excepciones_no_autoprogramadas tbody td', '.form-control',
  ].map((selector) => {
    const target = node.querySelector(selector);
    return { selector, exists: Boolean(target),
      background: target ? getComputedStyle(target).backgroundColor : '' };
  }));
  const rendered = metrics.filter((item) => item.exists);
  expect(rendered.length).toBeGreaterThan(5);
  for (const item of rendered) {
    expect(rgbLightness(item.background), item.selector).toBeLessThan(120);
  }
});

test('estado operativo muestra el nombre completo, sin recorte', async ({ page }) => {
  await openDarkWeek(page);
  // Contrato (Task 8, 2026-08-05, C-49 parte 1). El anterior era «una linea, con
  // elipsis si no cabe, y oculto por debajo de 120 px». Medido con datos reales,
  // ese contrato NUNCA mostraba el nombre: la columna rendizaba 116 px, el
  // contenedor del boton media 96 y la consulta `@container (max-width: 120px)`
  // se cumplia siempre, asi que «Lista para Confirmar» no se leia jamas — solo un
  // punto de color y «2 pend.». La columna sube a 164 px (contenedor 128) y el
  // nombre se apila sobre el contador para disponer del ancho entero.
  // Lo que se exige ahora: el nombre esta visible y ENTERO, sin recorte en ningun
  // eje. El camino de ocultarlo sigue siendo legitimo si la columna se estrecha,
  // y entonces el punto + contador tienen que quedar visibles.
  const zooms = page.locator('#hot-container .ops-state-zoom:visible');
  await expect(zooms.first()).toBeVisible();
  const metrics = await zooms.evaluateAll((nodes) => nodes.map((node) => {
    const chip = node.querySelector('.ops-state-chip');
    const count = node.querySelector('.ops-state-count');
    const chipStyle = chip ? getComputedStyle(chip) : null;
    const chipVisible = Boolean(chipStyle && chipStyle.display !== 'none');
    return {
      text: node.getAttribute('aria-label') || node.textContent.trim(),
      chipVisible,
      whiteSpace: chipVisible ? chipStyle.whiteSpace : null,
      overflow: chipVisible ? chipStyle.overflow : null,
      scrollHeight: chipVisible ? chip.scrollHeight : 0,
      clientHeight: chipVisible ? chip.clientHeight : 0,
      scrollWidth: chipVisible ? chip.scrollWidth : 0,
      clientWidth: chipVisible ? chip.clientWidth : 0,
      countVisible: Boolean(count && count.getClientRects().length),
      hasAccessibleDetail: Boolean(node.closest('[title], [aria-label]')),
    };
  }));
  expect(metrics.length).toBeGreaterThan(0);
  for (const item of metrics) {
    if (item.chipVisible) {
      expect(item.scrollHeight, item.text).toBeLessThanOrEqual(item.clientHeight + 1);
      expect(item.scrollWidth, item.text).toBeLessThanOrEqual(item.clientWidth + 1);
    } else {
      expect(item.countVisible, item.text).toBe(true);
    }
    expect(item.hasAccessibleDetail, item.text).toBe(true);
  }
});

test('filtros y acciones usan hit area accesible con visual compacto', async ({ page }) => {
  await openDarkWeek(page);
  const metrics = await page.evaluate(() => [
    ...document.querySelectorAll('.handsontable thead .changeType, .ps-action-btn'),
  ].filter((node) => node.getClientRects().length).map((node) => {
    const rect = node.getBoundingClientRect();
    const icon = node.matches('.ps-action-btn') ? node.querySelector('.ps-action-icon, i') : null;
    const visual = icon ? icon.getBoundingClientRect() : null;
    const before = getComputedStyle(node, '::before');
    const pseudoWidth = parseFloat(before.width);
    return { className: node.className, hitWidth: rect.width, hitHeight: rect.height,
      visualWidth: visual?.width || (Number.isFinite(pseudoWidth) ? pseudoWidth : rect.width) };
  }));
  expect(metrics.length).toBeGreaterThan(0);
  for (const item of metrics) {
    expect(item.hitWidth, item.className).toBeGreaterThanOrEqual(44);
    expect(item.hitHeight, item.className).toBeGreaterThanOrEqual(44);
    expect(item.visualWidth, item.className).toBeGreaterThanOrEqual(28);
    expect(item.visualWidth, item.className).toBeLessThanOrEqual(34);
  }
});

test('chips de alertas usan superficies dark del modulo', async ({ page }) => {
  await openDarkWeek(page);
  const chips = page.locator('#psAlertsLegend .pdc-legend-item:visible');
  await expect(chips.first()).toBeVisible();
  const metrics = await chips.evaluateAll((nodes) => nodes.map((node) => ({
    text: node.textContent.trim(),
    background: getComputedStyle(node).backgroundColor,
    color: getComputedStyle(node).color,
  })));
  for (const item of metrics) {
    expect(rgbLightness(item.background), item.text).toBeLessThan(120);
    expect(Math.abs(rgbLightness(item.color) - rgbLightness(item.background)), item.text)
      .toBeGreaterThan(90);
  }
});

test('modal eliminar actividad y asignar CNP usa dark mode', async ({ page }) => {
  await openDarkWeek(page);
  await page.locator('.ps-action-btn.eliminar:visible').first().click();
  const modal = page.locator('#modal_eliminar_actividad');
  await expect(modal).toBeVisible();
  const surfaces = await modal.evaluate((node) => [
    '.modal-content', '.modal-body', '.modal-footer', '.form-control',
  ].map((selector) => ({ selector,
    background: getComputedStyle(node.querySelector(selector)).backgroundColor })));
  for (const item of surfaces) {
    expect(rgbLightness(item.background), item.selector).toBeLessThan(120);
  }
  await modal.locator('[data-dismiss="modal"], .btn-cancelar').last().click();
  await expect(modal).toBeHidden();
});

test('modal de calificacion CIC contrasta en dark', async ({ page }) => {
  await openDarkWeek(page);
  await page.goto('/programacion-semanal/cic', { waitUntil: 'domcontentloaded' });
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 }).catch(() => {});
  for (const theme of ['dark']) {
    await page.locator('#dt_cliente tbody button.editar:visible').first().click();
    const modal = page.locator('#modalcic_mdo.show, #modalcic_si.show');
    await expect(modal).toBeVisible();
    const metrics = await modal.evaluate((node) => ({
      body: getComputedStyle(node.querySelector('.modal-body')).backgroundColor,
      question: getComputedStyle(node.querySelector('.ps-cic-question, .pregunta')).color,
    }));
    const delta = Math.abs(rgbLightness(metrics.question) - rgbLightness(metrics.body));
    expect(delta, `${theme} question contrast`).toBeGreaterThan(90);
    expect(rgbLightness(metrics.body)).toBeLessThan(120);
    await modal.locator('[data-dismiss="modal"], .close').first().click();
    await expect(modal).toBeHidden();
  }
});

test('modal CIC mobile conserva contraste y opciones dentro del box', async ({ page }) => {
  await openDarkWeek(page);
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/programacion-semanal/cic', { waitUntil: 'domcontentloaded' });
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 }).catch(() => {});
  for (const theme of ['dark']) {
    await page.locator(
      '#ps-legacy-card-view [data-legacy-action="edit"]:visible, #dt_cliente tbody button.editar:visible',
    ).first().click();
    const modal = page.locator('#modalcic_mdo.show, #modalcic_si.show');
    await expect(modal).toBeVisible();
    const metrics = await modal.evaluate((node) => {
      const header = node.querySelector('.modal-header');
      const title = node.querySelector('[id^="modal-body-texto-cic_"]');
      const fieldset = node.querySelector('fieldset.pregunta');
      const fieldRect = fieldset.getBoundingClientRect();
      return {
        header: getComputedStyle(header).backgroundColor,
        title: getComputedStyle(title).color,
        optionsInside: [...fieldset.querySelectorAll('.form-check-input')].every((input) => {
          const rect = input.getBoundingClientRect();
          return rect.left >= fieldRect.left - 1 && rect.right <= fieldRect.right + 1;
        }),
      };
    });
    const contrastDelta = Math.abs(rgbLightness(metrics.title) - rgbLightness(metrics.header));
    expect(metrics.optionsInside, `${theme} radio options`).toBe(true);
    expect(contrastDelta, `${theme} header contrast`).toBeGreaterThan(90);
    await modal.locator('[data-dismiss="modal"], .close').first().click();
    await expect(modal).toBeHidden();
  }
});

test('dropdowns de responsables son legibles en dark', async ({ page }) => {
  await openDarkWeek(page);
  for (const prop of ['Sub_Contratista', 'Responsable_AIA']) {
    await page.evaluate((key) => {
      const hot = window.PSHotModule.getHotInstance();
      const column = hot.propToCol(key);
      hot.selectCell(0, column);
      hot.getActiveEditor().beginEditing();
    }, prop);
    const editor = page.locator('.handsontableInput:visible');
    await expect(editor).toBeVisible();
    await page.keyboard.press('ArrowDown');
    const colors = await editor.evaluate((node) => ({
      background: getComputedStyle(node).backgroundColor,
      color: getComputedStyle(node).color,
    }));
    expect(rgbLightness(colors.background), prop).toBeLessThan(120);
    expect(Math.abs(rgbLightness(colors.color) - rgbLightness(colors.background)), prop)
      .toBeGreaterThan(90);
    const option = page.locator('.htAutocomplete:visible td, .handsontable.listbox:visible td, .htDropdownMenu:visible td').first();
    await expect(option).toBeVisible();
    const optionColors = await option.evaluate((node) => ({
      background: getComputedStyle(node).backgroundColor,
      color: getComputedStyle(node).color,
    }));
    expect(rgbLightness(optionColors.background), `${prop} option`).toBeLessThan(120);
    expect(Math.abs(rgbLightness(optionColors.color) - rgbLightness(optionColors.background)), `${prop} option`)
      .toBeGreaterThan(90);
    await page.keyboard.press('Escape');
  }
});
