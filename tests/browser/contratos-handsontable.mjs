import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { runSql } from './support/dbSnapshot.mjs';
import {
  HANDSONTABLE_GOAL_THEMES,
  HANDSONTABLE_GOAL_VIEWPORTS,
  measureHandsontableGoalMatrix,
  setHandsontableGoalTheme,
} from './support/handsontableGoalMatrix.mjs';
import {
  changeWeek, login, loginAndSelectProject, logout, postFormJson, selectProject,
} from './support/session.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];
const viewerUsername = `tvct${process.pid}`;

function createViewerFixture() {
  runSql(`INSERT INTO general_usuarios
    (nombre,email,cargo,usuario,password,force_password_change,activo)
    SELECT 'Test Visualizador Contratos','${viewerUsername}@aia.local','Visualizador','${viewerUsername}',password,0,1
    FROM general_usuarios WHERE usuario='test.A' LIMIT 1;
    INSERT INTO project_members (project_id,user_id,role)
    SELECT ${project.projectId},id,'V' FROM general_usuarios WHERE usuario='${viewerUsername}';`);
}

function removeViewerFixture() {
  runSql(`DELETE pm FROM project_members pm
    JOIN general_usuarios u ON u.id=pm.user_id WHERE u.usuario='${viewerUsername}';
    DELETE FROM general_usuarios WHERE usuario='${viewerUsername}';`);
}

async function openEmptyContractCard(page) {
  const emptyCard = page.locator('.ct-mobile-card', { hasText: 'Sin paquetes asociados' }).first();
  await expect(emptyCard).toBeVisible({ timeout: 15_000 });
  await emptyCard.getByRole('button', { name: 'Editar paquetes' }).click();
  await expect(page.locator('#modalEditarContratos')).toBeVisible({ timeout: 15_000 });
}

test.describe('Contratos - Handsontable y tarjetas mobile', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ width: 534, height: 750 });
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto(`/contratos?semana=${project.maxWeek}`, {
      waitUntil: 'networkidle',
      timeout: 30_000,
    });
  });

  test('la tarjeta mobile muestra una accion de edicion visible y accesible', async ({ page }) => {
    const emptyCard = page.locator('.ct-mobile-card', { hasText: 'Sin paquetes asociados' }).first();
    const editButton = emptyCard.getByRole('button', { name: 'Editar paquetes' });

    await expect(editButton).toBeVisible({ timeout: 15_000 });
    await expect(editButton).toContainText('Editar paquetes');
    await expect(editButton).toHaveAttribute('aria-label', 'Editar paquetes');
  });

  test('cargar la ruta consulta pendientes sin crear corridas ni sugerencias', async ({ page }) => {
    const before = runSql(`SELECT CONCAT(
      (SELECT COUNT(*) FROM semi_auto_runs WHERE project_id=${project.projectId} AND module='contratos'), '|',
      (SELECT COUNT(*) FROM semi_auto_suggestions WHERE project_id=${project.projectId} AND module='contratos'), '|',
      (SELECT COUNT(*) FROM auto_program_log WHERE project_id=${project.projectId})
    );`).trim();
    const automationRequests = [];
    page.on('request', (request) => {
      if (request.url().includes('/api/contratos/auto/')) automationRequests.push(request.url());
    });
    await page.reload({ waitUntil: 'networkidle' });
    const after = runSql(`SELECT CONCAT(
      (SELECT COUNT(*) FROM semi_auto_runs WHERE project_id=${project.projectId} AND module='contratos'), '|',
      (SELECT COUNT(*) FROM semi_auto_suggestions WHERE project_id=${project.projectId} AND module='contratos'), '|',
      (SELECT COUNT(*) FROM auto_program_log WHERE project_id=${project.projectId})
    );`).trim();
    expect(automationRequests.filter((url) => url.includes('/preview'))).toEqual([]);
    expect(after).toBe(before);
  });

  test('carga una sola instancia Handsontable y ningun runtime DataTables', async ({ page }) => {
    const runtime = await page.evaluate(() => ({
      resources: performance.getEntriesByType('resource').map((entry) => entry.name),
      wrappers: document.querySelectorAll('.dataTables_wrapper, #dt_cliente').length,
      handsontableMasters: document.querySelectorAll('#hot-container .ht_master').length,
      hasDataTablesPlugin: Boolean(window.jQuery?.fn?.dataTable || window.jQuery?.fn?.DataTable),
      hasHotInstance: Boolean(window.ContratosHotModule?.getHotInstance()),
    }));

    expect(runtime.resources.filter((url) => /data-?tables|datatable-|global-table-align|mobile-table-fix/i.test(url))).toEqual([]);
    expect(runtime.wrappers).toBe(0);
    expect(runtime.hasDataTablesPlugin).toBe(false);
    expect(runtime.handsontableMasters).toBe(1);
    expect(runtime.hasHotInstance).toBe(true);
    expect(await page.locator('[aria-label*="Eliminar"], .eliminar, .btn-eliminar').count()).toBe(0);
  });

  test('distingue carga, vacio, error y datos sin crear registros sinteticos', async ({ page }) => {
    const status = page.locator('#ct-table-status');
    await expect(status).toHaveAttribute('data-state', 'data');

    let releaseEmpty;
    await page.route('**/api/contratos/list**', async (route) => {
      await new Promise((resolve) => { releaseEmpty = resolve; });
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) });
    });
    await page.evaluate(() => window.ContratosHotModule.loadData());
    await expect(status).toHaveAttribute('data-state', 'loading');
    releaseEmpty();
    await expect(status).toHaveAttribute('data-state', 'empty');
    await expect(status).toContainText('Sin registros');
    expect(await page.evaluate(() => window.ContratosHotModule.getHotInstance().countSourceRows())).toBe(0);
    await expect(page.locator('.ct-mobile-card')).toHaveCount(0);
    await expect(page.locator('.ct-mobile-card-list__empty'))
      .toHaveText('No hay paquetes de contratacion para mostrar.');
    await page.unroute('**/api/contratos/list**');

    await page.route('**/api/contratos/list**', (route) => route.fulfill({
      status: 500,
      contentType: 'application/json',
      body: JSON.stringify({ error: 'fallo controlado' }),
    }));
    await page.evaluate(() => window.ContratosHotModule.loadData());
    await expect(status).toHaveAttribute('data-state', 'error');
    await expect(status).toContainText('No se pudieron cargar');
  });

  test('mantiene paridad de registros y paquetes entre API, HOT y tarjetas', async ({ page }) => {
    const apiResponse = await page.request.post(
      `/api/contratos/list?db=${project.dbName}&semana=${project.maxWeek}`,
    );
    expect(apiResponse.ok()).toBe(true);
    const api = (await apiResponse.json()).data;
    const parity = await page.evaluate(() => {
      const normalize = (value) => String(value || '').replace(/\s+/g, ' ').trim();
      const summaryText = (value) => {
        if (!normalize(value)) return 'Sin paquetes asociados';
        const template = document.createElement('template');
        template.innerHTML = String(value);
        return normalize(template.content.textContent);
      };
      return {
        hot: window.ContratosHotModule.getHotInstance().getSourceData().map((row) => ({
          Id: String(row.Id),
          actividad: row.actividad,
          contratosAsociados: row.contratosAsociados,
          paquetesSemanticos: summaryText(row.contratosAsociados),
        })),
        cards: [...document.querySelectorAll('.ct-mobile-card')].map((card) => ({
          Id: card.dataset.recordId,
          actividad: card.querySelector('.ct-mobile-card__title')?.textContent || '',
          paquetes: normalize(card.querySelector('.ct-mobile-card__summary')?.innerText),
        })),
      };
    });

    expect(parity.hot.map((row) => row.Id)).toEqual(api.map((row) => String(row.Id)));
    expect(parity.cards.map((row) => row.Id)).toEqual(api.map((row) => String(row.Id)));
    expect(parity.cards.map((row) => row.actividad)).toEqual(api.map((row) => row.actividad));
    expect(parity.cards.map((row) => row.paquetes))
      .toEqual(parity.hot.map((row) => row.paquetesSemanticos));
    await expect(page.locator('.ct-mobile-card__action')).toHaveCount(api.length);
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.reload({ waitUntil: 'networkidle' });
    await expect(page.locator('#hot-container button.editar')).toHaveCount(api.length);
  });

  test('distribuye columnas según su contenido, aprovecha el ancho y no trunca texto', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.reload({ waitUntil: 'networkidle' });
    await expect(page.locator('#ct-table-status')).toHaveAttribute('data-state', 'data');

    const layout = await page.evaluate(() => {
      const hot = window.ContratosHotModule.getHotInstance();
      const container = document.getElementById('hot-container');
      const probe = document.createElement('span');
      probe.style.cssText = 'position:absolute;visibility:hidden;width:var(--spacing-xs)';
      document.body.appendChild(probe);
      const spacingXs = probe.getBoundingClientRect().width;
      probe.remove();
      const widths = hot.getColHeader().map((_, index) => hot.getColWidth(index));
      const assignedWidth = parseFloat(getComputedStyle(container).getPropertyValue('--hot-table-width'));
      const visibleTextNodes = [...container.querySelectorAll('.ht_clone_top thead th, .ht_master tbody td')]
        .filter((element) => element.getClientRects().length && element.textContent.trim());
      const truncated = visibleTextNodes.filter((element) => {
        const style = getComputedStyle(element);
        const clipsX = ['hidden', 'clip'].includes(style.overflowX);
        const clipsY = ['hidden', 'clip'].includes(style.overflowY);
        const lineClamped = style.webkitLineClamp !== 'none' && Number(style.webkitLineClamp) > 0;
        return (clipsX && element.scrollWidth > element.clientWidth)
          || ((clipsY || lineClamped) && element.scrollHeight > element.clientHeight);
      }).map((element) => element.textContent.trim());
      return {
        widths,
        assignedWidth,
        containerWidth: container.clientWidth,
        spacingXs,
        truncated,
      };
    });

    expect(layout.widths.every((width) => width > 0)).toBe(true);
    expect(layout.widths.reduce((sum, width) => sum + width, 0)).toBe(layout.assignedWidth);
    expect(layout.containerWidth - layout.assignedWidth).toBeLessThanOrEqual(layout.spacingXs);
    expect(layout.widths[0]).toBeLessThan(layout.widths[2]);
    expect(layout.widths[1]).toBeLessThan(layout.widths[3]);
    expect(layout.widths[4]).toBeLessThan(layout.widths[6]);
    expect(layout.widths[5]).toBeLessThan(layout.widths[6]);
    expect(layout.truncated).toEqual([]);
  });

  test('presenta HTML de paquetes de forma segura en HOT y tarjetas', async ({ page }) => {
    await page.route('**/api/contratos/list**', (route) => route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [{
        Id: 9001,
        codigo: 'SAFE-1',
        actividad: 'Familia segura',
        descripcionActividad: 'Contenido controlado',
        fechaInicio: '2026-07-13',
        tipoContrato: 'S',
        contratosAsociados: '<b class="ct-text-info" onclick="window.__unsafe=1">Suministro</b><br>Paquete: <img src=x onerror="window.__unsafe=1"> Cable<script>window.__unsafe=1</script>',
      }] }),
    }));
    await page.evaluate(() => window.ContratosHotModule.loadData());
    await expect(page.locator('#ct-table-status')).toHaveAttribute('data-state', 'data');

    const safe = await page.evaluate(() => ({
      unsafeExecuted: Boolean(window.__unsafe),
      unsafeNodes: document.querySelectorAll('#hot-container img, #hot-container script, .ct-mobile-card img, .ct-mobile-card script').length,
      unsafeAttributes: document.querySelectorAll('#hot-container [onclick], #hot-container [onerror], .ct-mobile-card [onclick], .ct-mobile-card [onerror]').length,
      cardText: document.querySelector('.ct-mobile-card__summary')?.innerText || '',
      cardHtml: document.querySelector('.ct-mobile-card__summary')?.innerHTML || '',
    }));
    expect(safe.unsafeExecuted).toBe(false);
    expect(safe.unsafeNodes).toBe(0);
    expect(safe.unsafeAttributes).toBe(0);
    expect(safe.cardText).toContain('Suministro');
    expect(safe.cardText).toContain('Paquete:');
    expect(safe.cardHtml).not.toContain('<img');
    expect(safe.cardHtml).not.toContain('<script');
  });

  test('modal identifica el registro, aplica SI exclusivo y cancela sin residuos', async ({ page }) => {
    const cards = page.locator('.ct-mobile-card');
    expect(await cards.count()).toBeGreaterThan(1);
    const firstCard = cards.nth(0);
    const firstFamily = await firstCard.locator('.ct-mobile-card__title').innerText();
    const firstRecordId = await firstCard.getAttribute('data-record-id');
    const initialRecord = await page.evaluate((recordId) => window.ContratosHotModule
      .getHotInstance()
      .getSourceData()
      .find((row) => String(row.Id) === String(recordId)), firstRecordId);
    await firstCard.getByRole('button', { name: 'Editar paquetes' }).click();
    const modal = page.locator('#modalEditarContratos');
    await expect(modal).toBeVisible();
    await expect(modal.locator('.ct-modal-title-family')).toHaveText(firstFamily);
    await expect(modal.locator('.ct-modalidad-group .aia-choice')).toHaveCount(4);
    const headerGeometry = await modal.locator('.ct-modal-header').evaluate((header) => ({
      height: header.getBoundingClientRect().height,
      viewport: innerHeight,
    }));
    expect(headerGeometry.height).toBeLessThanOrEqual(headerGeometry.viewport * 0.18);

    const si = modal.getByRole('checkbox', { name: 'Suministro e Instalación' });
    const mo = modal.getByRole('checkbox', { name: 'Mano de Obra' });
    const supply = modal.getByRole('checkbox', { name: 'Suministro', exact: true });
    const oc = modal.getByRole('checkbox', { name: 'Orden de servicio/compra' });
    if (await mo.isChecked()) await mo.uncheck();
    if (await supply.isChecked()) await supply.uncheck();
    if (await oc.isChecked()) await oc.uncheck();
    await si.check();
    await expect(si).toBeChecked();
    await expect(mo).not.toBeChecked();
    await expect(supply).not.toBeChecked();
    await expect(oc).not.toBeChecked();
    await expect(mo).toBeDisabled();
    await expect(supply).toBeDisabled();
    await expect(oc).toBeDisabled();

    await page.evaluate(() => {
      const section = document.querySelector('#parametro_EditarContratosSI');
      const select = section.querySelector('.ct-package-select');
      select.append(new Option('Temporal sin guardar', 'Temporal sin guardar'));
      select.value = 'Temporal sin guardar';
      select.dispatchEvent(new Event('change', { bubbles: true }));
      section.querySelector('.ct-contract-quantity').value = '4';
      section.querySelector('.ct-add-package').click();
    });
    await modal.getByRole('button', { name: 'Cancelar edición' }).click();
    await expect(modal).toBeHidden();

    await firstCard.getByRole('button', { name: 'Editar paquetes' }).click();
    await expect(modal).toBeVisible();
    await expect(modal.locator('.ct-modal-title-family')).toHaveText(firstFamily);
    const sectionState = await modal.locator('.ct-contract-section:visible').evaluateAll((sections) => (
      sections.map((section) => ({
        prefix: section.dataset.prefix,
        visibleSlots: section.dataset.visibleSlots,
        rows: [...section.querySelectorAll('.ct-contract-row')].map((row) => ({
          hidden: row.hidden,
          package: row.querySelector('.ct-package-select')?.value || '',
          quantity: row.querySelector('.ct-contract-quantity')?.value || '',
        })),
      }))
    ));
    const rowsPerSection = await modal.locator('.ct-contract-section:visible').evaluateAll((sections) => (
      sections.map((section) => [...section.querySelectorAll('.ct-contract-row')]
        .filter((row) => getComputedStyle(row).display !== 'none').length)
    ));
    expect(rowsPerSection, JSON.stringify(sectionState)).toEqual(rowsPerSection.map(() => 1));
    sectionState.forEach((section) => {
      expect(section.rows[0].package).toBe(String(initialRecord[`paquete${section.prefix}1`] || ''));
      expect(section.rows[0].quantity).toBe(String(initialRecord[`cantidad${section.prefix}1`] || '1'));
    });
    await expect(modal.locator('.ct-contract-quantity')).toHaveCount(20);
    for (let cycle = 0; cycle < 2; cycle += 1) {
      await modal.getByRole('button', { name: 'Close' }).click();
      await expect(modal).toBeHidden();
      await firstCard.getByRole('button', { name: 'Editar paquetes' }).click();
      await expect(modal).toBeVisible();
    }
    const repeatedState = await page.evaluate(() => ({
      select2Containers: document.querySelectorAll('#modalEditarContratos .select2-container').length,
      selects: document.querySelectorAll('#modalEditarContratos select.ct-contract-control').length,
      saveHandlers: (window.jQuery?._data(document.getElementById('btn_guardar_contratos'), 'events')?.click || [])
        .filter((handler) => handler.namespace === 'ctSave').length,
      cancelHandlers: (window.jQuery?._data(document.getElementById('btn_cancelar_contratos'), 'events')?.click || [])
        .filter((handler) => handler.namespace === 'ctCancel').length,
    }));
    expect(repeatedState.select2Containers).toBe(repeatedState.selects);
    expect(repeatedState.saveHandlers).toBe(1);
    expect(repeatedState.cancelHandlers).toBe(1);
  });

  test('carga catálogo real, permite buscar paquetes y escribir recursos sin desbordar chips', async ({ page }) => {
    await openEmptyContractCard(page);
    const modal = page.locator('#modalEditarContratos');
    const section = modal.locator('.ct-contract-section:visible').first();
    const packageSelect = section.locator('.ct-package-select').first();
    const realOptions = (await packageSelect.locator('option').allTextContents()).filter((value) => value.trim());
    expect(realOptions.length).toBeGreaterThan(0);

    await packageSelect.locator('xpath=following-sibling::span[contains(@class,"select2")]').click();
    const packageSearch = page.locator('.select2-container--open .select2-search__field');
    await packageSearch.fill(realOptions[0].slice(0, Math.min(8, realOptions[0].length)));
    const matchingPackage = page.locator('.select2-results__option').filter({ hasText: realOptions[0] });
    await expect(matchingPackage).toBeVisible();
    await matchingPackage.click();

    const resourceSelect = section.locator('.ct-contract-control--multiple').first();
    await section.locator('.ct-contract-row:visible').first().locator('.select2-selection--multiple').click({ force: true });
    const resourceSearch = page.locator('.select2-container--open .select2-search__field');
    const hostileResource = 'Recurso <img src=x onerror=alert(1)> escrito';
    await resourceSearch.fill(hostileResource);
    await resourceSearch.press('Enter');
    const chips = section.locator('.ct-contract-row:visible').first().locator('.select2-selection--multiple');
    await expect(chips).toContainText(hostileResource);
    await expect(chips.locator('img, script, [onerror], [onclick]')).toHaveCount(0);
    await expect(resourceSelect).toHaveValues([hostileResource]);
    const chipLayout = await chips.evaluate((element) => ({
      overflow: element.scrollWidth - element.clientWidth,
      viewportOverflow: element.getBoundingClientRect().right - innerWidth,
    }));
    expect(chipLayout.overflow).toBeLessThanOrEqual(1);
    expect(chipLayout.viewportOverflow).toBeLessThanOrEqual(0);

    await modal.getByRole('button', { name: 'Cancelar edición' }).click();
    await openEmptyContractCard(page);
    await expect(modal.locator('.select2-selection__choice', { hasText: 'Recurso escrito por el usuario' })).toHaveCount(0);
  });

  test('guardar emite una sola petición aunque se active dos veces', async ({ page }) => {
    let catalogRequests = 0;
    let modifyRequests = 0;
    await page.route('**/api/contratos/save**', async (route) => {
      const postData = route.request().postData() || '';
      if (postData.includes('opcion=actualizarListadoPaquetesContratacion')) {
        catalogRequests += 1;
        await route.fulfill({ json: {
          listadoSI: '<option value=""></option>', listadoMO: '<option value=""></option>',
          listadoS: '<option value=""></option>', listadoOC: '<option value=""></option>',
        } });
        return;
      }
      if (postData.includes('opcion=actualizarInsumosRecursos')) {
        catalogRequests += 1;
        await route.fulfill({ json: {
          listadoSI: '<option value=""></option>', listadoMO: '<option value=""></option>',
          listadoS: '<option value=""></option>', listadoOC: '<option value=""></option>',
        } });
        return;
      }
      if (postData.includes('opcion=modificar')) {
        modifyRequests += 1;
        await route.fulfill({ json: { respuesta: 'BIEN' } });
        return;
      }
      await route.continue();
    });
    await page.locator('.ct-mobile-card').first().getByRole('button', { name: 'Editar paquetes' }).click();
    await expect.poll(() => catalogRequests).toBe(2);

    await page.evaluate(() => {
      [...document.querySelectorAll('.ct-contract-section')]
        .filter((section) => getComputedStyle(section).display !== 'none').forEach((section) => {
        const select = section.querySelector('.ct-package-select');
        const value = `Paquete único ${section.dataset.prefix}`;
        select.append(new Option(value, value));
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    await page.evaluate(() => {
      const save = document.getElementById('btn_guardar_contratos');
      save.click();
      save.click();
    });
    await expect(page.locator('#modalEditarContratos')).toBeHidden();
    expect(modifyRequests).toBe(1);
    await expect(page.locator('#mensajeActualizacion')).toContainText('guardaron correctamente');
  });

  test('un error de guardado queda visible, conserva el modal y permite reintentar', async ({ page }) => {
    await page.route('**/api/contratos/save**', async (route) => {
      const postData = route.request().postData() || '';
      if (postData.includes('opcion=modificar')) {
        await route.fulfill({ status: 500, json: { mensaje: 'Fallo controlado de guardado' } });
        return;
      }
      await route.continue();
    });
    await openEmptyContractCard(page);
    const modal = page.locator('#modalEditarContratos');
    const section = modal.locator('.ct-contract-section:visible').first();
    await section.locator('.ct-package-select').first().evaluate((select) => {
      select.append(new Option('Paquete para error', 'Paquete para error', true, true));
      window.jQuery(select).trigger('change');
    });
    await modal.getByRole('button', { name: 'Guardar contratos' }).click();
    await expect(modal).toBeVisible();
    await expect(modal.locator('.mensaje')).toContainText('Fallo controlado de guardado');
    await expect(modal.getByRole('button', { name: 'Guardar contratos' })).toBeEnabled();
  });

  test('respuestas tardías de otro registro no mezclan catálogos', async ({ page }) => {
    const pendingCatalog = [];
    const pendingResources = [];
    await page.route('**/api/contratos/save**', async (route) => {
      const postData = route.request().postData() || '';
      if (postData.includes('opcion=actualizarListadoPaquetesContratacion')) {
        await new Promise((resolve) => pendingCatalog.push({ route, resolve }));
        return;
      }
      if (postData.includes('opcion=actualizarInsumosRecursos')) {
        await new Promise((resolve) => pendingResources.push({ route, resolve }));
        return;
      }
      await route.continue();
    });

    const cards = page.locator('.ct-mobile-card');
    expect(await cards.count()).toBeGreaterThan(1);
    await cards.nth(0).getByRole('button', { name: 'Editar paquetes' }).click();
    await expect.poll(() => pendingCatalog.length).toBe(1);
    const modal = page.locator('#modalEditarContratos');
    await modal.getByRole('button', { name: 'Close' }).click();
    await expect(modal).toBeHidden();
    const secondFamily = await cards.nth(1).locator('.ct-mobile-card__title').innerText();
    await cards.nth(1).getByRole('button', { name: 'Editar paquetes' }).click();
    await expect.poll(() => pendingCatalog.length).toBe(2);

    pendingCatalog[1].resolve();
    await pendingCatalog[1].route.fulfill({ json: {
      listadoSI: '<option value="SECOND">SECOND</option>', listadoMO: '<option value="SECOND">SECOND</option>',
      listadoS: '<option value="SECOND">SECOND</option>', listadoOC: '<option value="SECOND">SECOND</option>',
    } });
    await expect.poll(() => pendingResources.length).toBe(1);
    pendingResources[0].resolve();
    await pendingResources[0].route.fulfill({ json: {
      listadoSI: '<option value="R-SECOND">R-SECOND</option>', listadoMO: '<option value="R-SECOND">R-SECOND</option>',
      listadoS: '<option value="R-SECOND">R-SECOND</option>', listadoOC: '<option value="R-SECOND">R-SECOND</option>',
    } });

    pendingCatalog[0].resolve();
    await pendingCatalog[0].route.fulfill({ json: {
      listadoSI: '<option value="FIRST">FIRST</option>', listadoMO: '<option value="FIRST">FIRST</option>',
      listadoS: '<option value="FIRST">FIRST</option>', listadoOC: '<option value="FIRST">FIRST</option>',
    } });
    await page.waitForTimeout(100);
    expect(pendingResources).toHaveLength(1);

    await expect(modal.locator('.ct-modal-title-family')).toHaveText(secondFamily);
    const options = await modal.locator('.ct-contract-section:visible .ct-package-select').first()
      .locator('option').allTextContents();
    expect(options).toContain('SECOND');
    expect(options).not.toContain('FIRST');
    await expect(modal.locator('.ct-contract-quantity')).toHaveCount(20);
  });

  test('toolbar contiene auto-definir y selector de módulo sin texto recortado', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.reload({ waitUntil: 'networkidle' });
    const toolbar = page.locator('.toolbarFilaBotones');
    const autoButton = toolbar.locator('#btn_auto_asignar_contratos');
    await expect(autoButton).toBeVisible();
    const moduleSelector = toolbar.locator('.aia-info-nav');
    await expect(moduleSelector).toBeVisible();
    await expect(moduleSelector).toContainText('Paquetes de contratacion');
    const layout = await toolbar.evaluate((element) => ({
      overflow: element.scrollWidth - element.clientWidth,
      clipped: [...element.querySelectorAll('button, a')]
        .filter((control) => control.scrollWidth > control.clientWidth + 1).length,
      ...(() => {
        const button = element.querySelector('#btn_auto_asignar_contratos').getBoundingClientRect();
        const nav = element.querySelector('.aia-info-nav').getBoundingClientRect();
        const trigger = element.querySelector('.aia-info-nav__trigger');
        const toolbarRect = element.getBoundingClientRect();
        const toolbarStyle = getComputedStyle(element);
        const rowStyle = getComputedStyle(element.closest('.filaBotones'));
        const triggerStyle = getComputedStyle(trigger);
        const navStyle = getComputedStyle(element.querySelector('.aia-info-nav'));
        const resolveToken = (token) => {
          const probe = document.createElement('span');
          probe.style.cssText = `position:absolute;visibility:hidden;width:var(${token})`;
          document.body.appendChild(probe);
          const pixels = probe.getBoundingClientRect().width;
          probe.remove();
          return pixels;
        };
        return {
          toolbarWidth: toolbarRect.width,
          buttonWidth: button.width,
          navWidth: nav.width,
          verticalOverlap: Math.min(button.bottom, nav.bottom) - Math.max(button.top, nav.top),
          contained: button.left >= toolbarRect.left && nav.right <= toolbarRect.right,
          gap: parseFloat(toolbarStyle.columnGap),
          expectedGap: resolveToken('--spacing-sm') + resolveToken('--spacing-xs'),
          toolbarMarginBottom: parseFloat(toolbarStyle.marginBottom),
          expectedToolbarMargin: resolveToken('--spacing-xs'),
          rowMarginBottom: parseFloat(rowStyle.marginBottom),
          expectedRowMargin: resolveToken('--spacing-sm'),
          triggerRadius: parseFloat(triggerStyle.borderTopLeftRadius),
          navRadius: parseFloat(navStyle.borderTopLeftRadius),
          radiusMin: resolveToken('--ds-radius-sm'),
          radiusMax: resolveToken('--ds-radius-md'),
        };
      })(),
    }));
    expect(layout.overflow).toBeLessThanOrEqual(0);
    expect(layout.clipped).toBe(0);
    expect(layout.verticalOverlap).toBeGreaterThan(0);
    expect(layout.contained).toBe(true);
    expect(layout.buttonWidth).toBeLessThanOrEqual(layout.toolbarWidth / 2);
    expect(layout.navWidth).toBeLessThanOrEqual(layout.toolbarWidth / 2);
    expect(layout.gap).toBe(layout.expectedGap);
    expect(layout.toolbarMarginBottom).toBe(layout.expectedToolbarMargin);
    expect(layout.rowMarginBottom).toBe(layout.expectedRowMargin);
    expect(layout.triggerRadius).toBe(layout.navRadius);
    expect(layout.triggerRadius).toBeGreaterThanOrEqual(layout.radiusMin);
    expect(layout.triggerRadius).toBeLessThanOrEqual(layout.radiusMax);
  });

  test('abre filtros por columna, combina condiciones y limpia todo', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.reload({ waitUntil: 'networkidle' });
    const initial = await page.evaluate(() => {
      const hot = window.ContratosHotModule.getHotInstance();
      const first = hot.getSourceData()[0];
      return {
        rows: hot.countRows(),
        family: String(first.actividad),
        code: String(first.codigo),
      };
    });
    expect(initial.rows).toBeGreaterThan(1);

    const menuButtons = page.locator('#hot-container .ht_clone_top .changeType');
    const buttonCount = await menuButtons.count();
    expect(buttonCount).toBe(7);
    await menuButtons.nth(2).click();
    let menu = page.locator('.htDropdownMenu:visible');
    await expect(menu).toBeVisible();
    await menu.getByRole('button', { name: 'Borrar', exact: true }).last().click();
    await expect(menu.getByRole('checkbox').first()).not.toBeChecked();
    await menu.getByRole('textbox', { name: 'Buscar' }).fill(initial.family);
    await menu.getByRole('checkbox').first().check();
    await menu.getByRole('button', { name: 'OK', exact: true }).click();
    await expect.poll(
      () => page.evaluate(() => window.ContratosHotModule.getHotInstance().countRows()),
    ).toBeLessThan(initial.rows);
    const familyFilteredRows = await page.evaluate(() => window.ContratosHotModule.getHotInstance().countRows());
    expect(familyFilteredRows).toBeGreaterThan(0);

    await menuButtons.nth(1).click();
    menu = page.locator('.htDropdownMenu:visible');
    await expect(menu).toBeVisible();
    await menu.getByRole('button', { name: 'Borrar', exact: true }).last().click();
    await expect(menu.getByRole('checkbox').first()).not.toBeChecked();
    await menu.getByRole('checkbox').first().check();
    await menu.getByRole('button', { name: 'OK', exact: true }).click();
    await expect.poll(() => page.evaluate(() => window.ContratosHotModule.getHotInstance().countRows()))
      .toBe(1);

    for (const column of [2, 1]) {
      await menuButtons.nth(column).click();
      menu = page.locator('.htDropdownMenu:visible');
      await expect(menu).toBeVisible();
      await menu.getByRole('button', { name: 'Seleccionar todo', exact: true }).last().click();
      await menu.getByRole('button', { name: 'OK', exact: true }).click();
    }
    await expect.poll(() => page.evaluate(() => window.ContratosHotModule.getHotInstance().countRows()))
      .toBe(initial.rows);
  });

  test('cumple la matriz técnica mobile, tablet y desktop en Dark y Linen', async ({ page }) => {
    const consoleProblems = [];
    const failedRequests = [];
    const failedResponses = [];
    page.on('console', (message) => {
      if (message.type() === 'error' || message.type() === 'warning') consoleProblems.push(message.text());
    });
    page.on('requestfailed', (request) => failedRequests.push(`${request.method()} ${request.url()}`));
    page.on('response', (response) => {
      if (response.status() >= 400) failedResponses.push(`${response.status()} ${response.url()}`);
    });
    for (const viewport of HANDSONTABLE_GOAL_VIEWPORTS) {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      for (const theme of HANDSONTABLE_GOAL_THEMES) {
        await setHandsontableGoalTheme(page, theme);
        await page.reload({ waitUntil: 'networkidle' });
        await expect(page.locator('#ct-table-status')).toHaveAttribute('data-state', 'data');
        const measurement = await measureHandsontableGoalMatrix(page, {
          cards: '.ct-mobile-card',
          controls: 'button, a, input, select, textarea, [role="button"]',
        });
        expect(measurement.theme).toBe(theme);
        expect(measurement.pageOverflowX, `${viewport.name} ${theme}`).toBe(0);
        expect(measurement.overflowingControls, `${viewport.name} ${theme}`).toEqual([]);
        expect(measurement.overflowingContent, `${viewport.name} ${theme}`).toEqual([]);
        expect(measurement.dataTables.wrappers).toBe(0);
        expect(measurement.dataTables.scripts).toEqual([]);
        expect(measurement.dataTables.styles).toEqual([]);
        expect(measurement.dataTables.plugin).toBe(false);
        expect(measurement.dataTables.legacyDom).toBe(0);
        expect(measurement.dataTables.delegatedSelectors).toEqual([]);
        expect(measurement.legacyDataTableListeners).toEqual([]);
        expect(measurement.rawHtmlText).toEqual([]);
        expect(measurement.darkBody).toBe(theme === 'dark');
        if (viewport.name === 'mobile') {
          expect(measurement.cards.visible).toBeGreaterThan(0);
        } else {
          expect(measurement.hot.masters).toBe(1);
          expect(measurement.hot.overflowX, `${viewport.name} ${theme}`).toBe(0);
          expect(measurement.headerCellAlignment.aligned, `${viewport.name} ${theme}`).toBe(true);
        }
      }
    }
    expect(consoleProblems).toEqual([]);
    expect(failedRequests).toEqual([]);
    expect(failedResponses).toEqual([]);
  });

  test('los paquetes adicionales aparecen con mas hasta completar cinco', async ({ page }) => {
    await openEmptyContractCard(page);

    const modal = page.locator('#modalEditarContratos');
    const section = modal.locator('.ct-contract-section:visible').first();
    const visibleRows = section.locator('.ct-contract-row:visible');
    const addButton = section.getByRole('button', { name: /Agregar paquete/ });

    await expect(visibleRows).toHaveCount(1);
    await expect(addButton).toBeVisible();

    for (let expected = 2; expected <= 5; expected += 1) {
      await addButton.click();
      await expect(visibleRows).toHaveCount(expected);
    }

    await expect(addButton).toBeHidden();
    await expect(visibleRows.locator('.ct-contract-index')).toHaveText([
      'Paquete 1.', 'Paquete 2.', 'Paquete 3.', 'Paquete 4.', 'Paquete 5.',
    ]);
    for (const row of await visibleRows.all()) {
      await expect(row.locator('.ct-contract-mobile-label')).toHaveText([
        'Paquete de contratación',
        'Cantidad de contratos',
        'Insumos y recursos requeridos',
      ]);
      await expect(row.locator('.ct-contract-mobile-help')).toHaveText([
        'Selecciona o escribe el paquete que se contratará.',
        'Número de contratos separados para este paquete.',
        'Agrega los insumos o recursos que componen el paquete.',
      ]);
    }
    const separation = await section.evaluate((element) => {
      const rows = [...element.querySelectorAll('.ct-contract-row:not([hidden])')];
      const rowGap = parseFloat(getComputedStyle(element.querySelector('.ct-contract-list')).rowGap);
      return {
        rowGap,
        gaps: rows.slice(1).map((row, index) => (
          row.getBoundingClientRect().top - rows[index].getBoundingClientRect().bottom
        )),
        styledRows: rows.map((row) => {
          const style = getComputedStyle(row);
          return {
            border: parseFloat(style.borderTopWidth),
            radius: parseFloat(style.borderTopLeftRadius),
            shadow: style.boxShadow,
          };
        }),
      };
    });
    expect(separation.gaps.every((gap) => gap >= separation.rowGap)).toBe(true);
    expect(separation.styledRows.every((row) => row.border > 0 && row.radius > 0 && row.shadow !== 'none')).toBe(true);
  });

  test('usa sesiones reales editor y readOnly con acciones y backend coherentes', async ({ page }) => {
    await expect(page.locator('#cap_contratos_editar')).toHaveValue('1');
    expect(await page.locator('.ct-mobile-card__action').count()).toBeGreaterThan(0);
    await expect(page.locator('#btn_auto_asignar_contratos')).toBeVisible();

    createViewerFixture();
    try {
      await logout(page);
      await login(page, { username: viewerUsername, password: 'aia2026' });
      await selectProject(page, project);
      await changeWeek(page, project.maxWeek, '/contratos');
      await page.goto(`/contratos?semana=${project.maxWeek}`, { waitUntil: 'networkidle' });

      await expect(page.locator('#permiso_canonico')).toHaveValue('V');
      await expect(page.locator('#cap_contratos_editar')).toHaveValue('0');
      await expect(page.locator('.ct-mobile-card__action')).toHaveCount(0);
      await expect(page.locator('#btn_auto_asignar_contratos')).toHaveCount(0);
      await page.setViewportSize({ width: 1024, height: 768 });
      await page.reload({ waitUntil: 'networkidle' });
      await expect(page.locator('#hot-container button.editar')).toHaveCount(0);

      const deniedSave = await postFormJson(page, '/api/contratos/save', {
        opcion: 'modificar', Id: '1', tipoContrato: 'MO', semana: project.maxWeek,
      });
      expect(deniedSave.status).toBe(403);
      const deniedAuto = await postFormJson(page, '/api/contratos/auto/preview', {
        semana: project.maxWeek,
      });
      expect(deniedAuto.status).toBe(403);
    } finally {
      await logout(page).catch(() => {});
      removeViewerFixture();
    }
  });
});
