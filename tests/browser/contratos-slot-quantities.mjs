import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';
import { ProjectDbSnapshot, runSql } from './support/dbSnapshot.mjs';

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];
const preconstructionProject = PROJECTS.find((item) => item.key === 'pc') || null;

test.describe('Contratos - cantidades por paquete', () => {
  test('muestra steppers de cantidad junto a paquetes', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });

    const table = page.locator('#hot-container');
    await expect(table).toBeVisible({ timeout: 15000 });

    const editButton = page.locator('#hot-container button.editar').first();
    await expect(editButton).toBeVisible({ timeout: 15000 });
    await editButton.click();

    const modal = page.locator('#modalEditarContratos');
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(modal.locator('.ct-contract-quantity')).toHaveCount(20);
    await expect(modal.locator('.ct-contract-section:visible .ct-contract-header__cell', { hasText: 'Contratos' }).first()).toBeVisible();

    const visibleQuantity = modal.locator('.ct-contract-section:visible .ct-contract-quantity').first();
    await expect(visibleQuantity).toBeVisible();
    await expect(visibleQuantity).toHaveAttribute('min', '1');
    await expect(visibleQuantity).toHaveAttribute('step', '1');

  });

  test('rechaza vacío, cero, negativos y decimales antes de enviar', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });
    await page.locator('#hot-container button.editar').first().click();
    const modal = page.locator('#modalEditarContratos');
    await expect(modal).toBeVisible();

    let saveRequests = 0;
    await page.route('**/api/contratos/save**', async (route) => {
      saveRequests += 1;
      await route.fulfill({ json: { respuesta: 'BIEN' } });
    });

    for (const invalid of ['', '0', '-1', '1.5']) {
      await page.evaluate((value) => {
        const section = [...document.querySelectorAll('.ct-contract-section')]
          .find((candidate) => getComputedStyle(candidate).display !== 'none');
        const packageSelect = section.querySelector('.ct-package-select');
        if (![...packageSelect.options].some((option) => option.value === 'Paquete de validación')) {
          packageSelect.append(new Option('Paquete de validación', 'Paquete de validación'));
        }
        packageSelect.value = 'Paquete de validación';
        packageSelect.dispatchEvent(new Event('change', { bubbles: true }));
        section.querySelector('.ct-contract-quantity').value = value;
      }, invalid);
      await modal.getByRole('button', { name: 'Guardar contratos' }).click();
      await expect(modal.locator('.mensaje')).toContainText('entero mayor o igual a 1');
      expect(saveRequests).toBe(0);
    }
  });

  test('valida las siete duraciones como enteros antes de guardar', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/contratos');
    await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });

    await page.evaluate(() => window.openDurationsModal([{
      tipoPaquete: 'Suministro',
      paqueteContratacion: 'Paquete duración UI',
      diasElaboracionPliegos: 1,
      diasEntregaPliegos: 1,
      diasReciboPropuestas: 1,
      diasCuadrosComparativos: 1,
      diasLegalizacionContrato: 1,
      diasFabricacion: 1,
      diasInsumosObra: 1,
    }], 'payload-controlado'));

    const modal = page.locator('#modalDuracionesContratos');
    await expect(modal).toBeVisible();
    await expect(modal.locator('.duration-input')).toHaveCount(7);
    await modal.locator('.duration-input').first().fill('1.5');

    let durationRequests = 0;
    await page.route('**/api/contratos/save**', async (route) => {
      durationRequests += 1;
      await route.fulfill({ json: { respuesta: 'BIEN' } });
    });
    await modal.getByRole('button', { name: 'Guardar duraciones' }).click();

    await expect(modal.locator('.mensaje-duraciones')).toContainText('enteros iguales o mayores a cero');
    expect(durationRequests).toBe(0);
  });

  test('persiste paquete, cantidad, recursos y siete duraciones; luego restaura el estado inicial', async ({ page }) => {
    test.setTimeout(90000);
    const snapshot = new ProjectDbSnapshot(project, ['actividades', 'contratos_trazabilidad']).capture();
    const initialFingerprint = snapshot.fingerprint();
    const auditBaseline = Number(runSql('SELECT COALESCE(MAX(id), 0) FROM general_auditoria_acciones;').trim() || 0);
    const token = `${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
    const packageName = `E2E paquete contratos ${token}`;
    const resources = [`E2E recurso A ${token}`, `E2E recurso B ${token}`];
    let recordId = '';

    try {
      await loginAndSelectProject(page, project);
      await changeWeek(page, project.maxWeek, '/contratos');
      await page.goto('/contratos', { waitUntil: 'networkidle', timeout: 30000 });
      await page.locator('#hot-container button.editar').first().click();
      const editModal = page.locator('#modalEditarContratos');
      await expect(editModal).toBeVisible();
      recordId = await page.locator('#contratoId').inputValue();
      expect(recordId).not.toBe('');

      await Promise.all([
        page.waitForResponse((response) => response.url().includes('/api/contratos/save')
          && response.request().postData()?.includes('opcion=actualizarListadoPaquetesContratacion')),
        page.waitForResponse((response) => response.url().includes('/api/contratos/save')
          && response.request().postData()?.includes('opcion=actualizarInsumosRecursos')),
        page.evaluate(() => {
          $('#modalidadSI, #modalidadMO, #modalidadS, #modalidadOC')
            .prop('checked', false)
            .prop('disabled', false);
          $('#modalidadMO').prop('checked', true).trigger('change');
        }),
      ]);

      await page.evaluate(({ packageName: name, resources: values }) => {
        const addSelectedOption = (selector, value) => {
          const select = document.querySelector(selector);
          select.append(new Option(value, value, true, true));
          $(select).trigger('change');
        };
        document.querySelectorAll('.ct-package-select').forEach((select) => {
          $(select).val('').trigger('change');
        });
        document.querySelectorAll('.ct-contract-control--multiple').forEach((select) => {
          $(select).val(null).trigger('change');
        });
        addSelectedOption('#paqueteMO1', name);
        values.forEach((value) => addSelectedOption('#MO1', value));
        $('#MO1').val(values).trigger('change');
        $('#cantidadMO1').val('3');
      }, { packageName, resources });

      await editModal.getByRole('button', { name: 'Guardar contratos' }).click();
      const durationModal = page.locator('#modalDuracionesContratos');
      await expect(durationModal).toBeVisible({ timeout: 15000 });
      await expect(durationModal.locator('.duration-package')).toContainText(packageName);
      const durationInputs = durationModal.locator('.duration-input');
      await expect(durationInputs).toHaveCount(7);
      for (let index = 0; index < 7; index += 1) {
        await durationInputs.nth(index).fill(String(index + 2));
      }
      const durationSaveResponse = page.waitForResponse((response) => response.url().includes('/api/contratos/save')
        && response.request().postData()?.includes('opcion=guardarDuracionesContratacion'));
      const finalContractSaveResponse = page.waitForResponse((response) => {
        const postData = response.request().postData() || '';
        const params = new URLSearchParams(postData);
        return response.url().includes('/api/contratos/save')
          && [...params.values()].includes(packageName)
          && !postData.includes('opcion=guardarDuracionesContratacion');
      });
      await durationModal.getByRole('button', { name: 'Guardar duraciones' }).click();
      const durationSavePayload = await (await durationSaveResponse).json();
      expect(durationSavePayload.respuesta, JSON.stringify(durationSavePayload)).toBe('BIEN');
      const finalContractSavePayload = await (await finalContractSaveResponse).json();
      expect(finalContractSavePayload.respuesta, JSON.stringify(finalContractSavePayload)).toBe('BIEN');
      await expect(durationModal).toBeHidden({ timeout: 15000 });
      await expect(editModal).toBeHidden({ timeout: 15000 });
      await expect(page.locator('#mensajeActualizacion')).toContainText('guardaron correctamente');
      const persistedContract = runSql(
        `SELECT CONCAT_WS('|', COALESCE(paqueteMO1, ''), COALESCE(cantidadMO1, ''), COALESCE(MO1, '')) FROM actividades WHERE project_id = ${project.projectId} AND Id = ${Number(recordId)} AND semanaActualizacion = ${project.maxWeek} LIMIT 1;`,
      ).trim();
      expect(persistedContract).toBe(`${packageName}|3|${resources.join(';')}`);

      await page.setViewportSize({ width: 390, height: 844 });
      await page.reload({ waitUntil: 'networkidle' });
      const card = page.locator(`.ct-mobile-card[data-record-id="${recordId}"]`);
      await expect(card).toHaveCount(1);
      const reopenedCatalogResponse = page.waitForResponse((response) => response.url().includes('/api/contratos/save')
        && response.request().method() === 'POST');
      await card.getByRole('button', { name: 'Editar paquetes' }).click();
      await expect(editModal).toBeVisible();
      const catalogResponse = await reopenedCatalogResponse;
      expect(
        catalogResponse.status(),
        `${catalogResponse.request().postData()} :: ${await catalogResponse.text()}`,
      ).toBe(200);
      await expect(editModal.locator('#paqueteMO1')).toHaveValue(packageName, { timeout: 15000 });
      await expect(editModal.locator('#cantidadMO1')).toHaveValue('3');
      await expect(editModal.locator('#MO1')).toHaveValues(resources);

      const persistedDurations = runSql(
        `SELECT CONCAT_WS(',', diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra) FROM general_dias_procesos_contratacion WHERE paqueteContratacion = '${packageName.replaceAll("'", "''")}' AND tipoPaquete = 'Mano de Obra' LIMIT 1;`,
      ).trim();
      expect(persistedDurations).toBe('2,3,4,5,6,7,8');
      await page.evaluate(({ packageName: name, values }) => window.openDurationsModal([{
        tipoPaquete: 'Mano de Obra',
        paqueteContratacion: name,
        diasElaboracionPliegos: values[0],
        diasEntregaPliegos: values[1],
        diasReciboPropuestas: values[2],
        diasCuadrosComparativos: values[3],
        diasLegalizacionContrato: values[4],
        diasFabricacion: values[5],
        diasInsumosObra: values[6],
      }], null), { packageName, values: persistedDurations.split(',').map(Number) });
      await expect(durationModal).toBeVisible();
      expect(await durationModal.locator('.duration-input').evaluateAll((inputs) => inputs.map((input) => input.value)))
        .toEqual(['2', '3', '4', '5', '6', '7', '8']);
    } finally {
      snapshot.restore();
      runSql(`DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = '${packageName.replaceAll("'", "''")}';`);
      runSql(`DELETE FROM general_auditoria_acciones WHERE id > ${auditBaseline} AND modulo = 'Contratos';`);
      expect(snapshot.fingerprint()).toBe(initialFingerprint);
      expect(runSql(`SELECT COUNT(*) FROM general_dias_procesos_contratacion WHERE paqueteContratacion = '${packageName.replaceAll("'", "''")}';`).trim()).toBe('0');
      snapshot.dispose();
    }
  });

  test('bloquea URL directa en preconstruccion', async ({ page }) => {
    test.skip(!preconstructionProject, 'Preconstruction fixture is not enabled in this environment');
    await loginAndSelectProject(page, preconstructionProject);
    await changeWeek(page, preconstructionProject.maxWeek, '/programa-general');

    const response = await page.goto(`/contratos?semana=${preconstructionProject.maxWeek}`, {
      waitUntil: 'domcontentloaded',
    });

    expect(response?.status()).toBe(404);
    await expect(page.locator('body')).toContainText('Contratos no esta disponible');
  });
});
