import { expect } from '@playwright/test';
import { assertNavbarForProject, assertProjectContext, assertRestrictionConfig } from './assertions.mjs';
import { runSql } from './dbSnapshot.mjs';
import { captureReloadingJsonRequest, changeWeek } from './session.mjs';
import { waitForRender } from '../../../e2e/support/handsontable.mjs';

const TEST_USERNAME = 'test.A';

function sqlString(value) {
  return `'${String(value ?? '').replaceAll("'", "''")}'`;
}

function scalar(sql) {
  const output = runSql(sql).trim();
  return Number(output.split(/\s+/).pop() || 0);
}

function firstRow(sql) {
  const line = runSql(sql).trim().split('\n').filter(Boolean).pop();
  return line ? line.split('\t') : [];
}

export function payloadHasServerError(payload) {
  const body = typeof payload === 'string' ? payload : JSON.stringify(payload);
  if (/\b(?:SQLSTATE(?:\[[^\]]+\])?|PDOException|Fatal error|Uncaught (?:Error|Exception))\b/i.test(body || '')) {
    return true;
  }
  if (typeof payload === 'string') return /^(?:error|failed|failure)\b/i.test(payload.trim());
  if (!payload || typeof payload !== 'object') return false;
  return payload.success === false
    || String(payload.respuesta ?? '').toUpperCase() === 'ERROR'
    || String(payload.status ?? '').toLowerCase() === 'error'
    || Boolean(payload.error);
}

function expectSuccessfulPayload(payload, label) {
  expect(payload, `${label} response`).toBeTruthy();
  expect(payloadHasServerError(payload), `${label}: ${JSON.stringify(payload)}`).toBe(false);
  expect(payload.respuesta, `${label}: ${JSON.stringify(payload)}`).not.toBe('ERROR');
  expect(payload.status, `${label}: ${JSON.stringify(payload)}`).not.toBe('error');
}

function isPostTo(response, path, operation = '') {
  if (new URL(response.url()).pathname !== path || response.request().method() !== 'POST') return false;
  if (!operation) return true;
  return new URLSearchParams(response.request().postData() || '').get('opcion') === operation;
}

async function responsePayload(response, label = 'Operational request') {
  const text = await response.text();
  expect(response.ok(), `${label} HTTP ${response.status()}: ${text.slice(0, 500)}`).toBe(true);
  let payload;
  try {
    payload = JSON.parse(text);
  } catch {
    payload = text;
  }
  expect(payloadHasServerError(payload), `${label} body: ${text.slice(0, 500)}`).toBe(false);
  return payload;
}

async function waitForHotModule(page, moduleName) {
  await page.waitForFunction((name) => {
    const module = window[name];
    const hot = module?.getHotInstance?.();
    return Boolean(hot && hot.countRows() >= 0);
  }, moduleName, { timeout: 30_000 });
  await waitForRender(page, 30_000);
}

async function hotTarget(page, moduleName, fixture, prop) {
  return page.evaluate(({ name, uniqueId, property }) => {
    const hot = window[name]?.getHotInstance?.();
    if (!hot) return null;
    const source = hot.getSourceData();
    let physicalRow = source.findIndex((row) => (
      Number(row?.unique_id) === Number(uniqueId) && Number(row?.Titulo || 0) !== 1
    ));
    if (physicalRow < 0) {
      physicalRow = source.findIndex((row) => Number(row?.Titulo || 0) !== 1);
    }
    if (physicalRow < 0) return null;
    const visualRow = hot.toVisualRow(physicalRow);
    const col = hot.propToCol(property);
    if (visualRow == null || visualRow < 0 || col < 0) return null;
    const row = source[physicalRow];
    return {
      visualRow,
      col,
      value: String(row?.[property] ?? ''),
      uniqueId: Number(row?.unique_id || 0),
      consecutive: Number(row?.Consecutivo || 0),
    };
  }, { name: moduleName, uniqueId: fixture.programUniqueId, property: prop });
}

async function assertHotValue(page, moduleName, identity, prop, expected) {
  await expect.poll(() => page.evaluate(({ name, uniqueId, consecutive, property }) => {
    const rows = window[name]?.getHotInstance?.()?.getSourceData?.() || [];
    const row = rows.find((item) => (
      (uniqueId > 0 && Number(item?.unique_id) === Number(uniqueId))
      || (consecutive > 0 && Number(item?.Consecutivo) === Number(consecutive))
    ));
    return String(row?.[property] ?? '');
  }, {
    name: moduleName,
    uniqueId: identity.uniqueId,
    consecutive: identity.consecutive,
    property: prop,
  }), { timeout: 30_000 }).toBe(String(expected));
}

async function clickHotCell(page, moduleName, row, col) {
  const rect = await page.evaluate(({ name, visualRow, visualCol }) => {
    const hot = window[name]?.getHotInstance?.();
    if (!hot) return null;
    hot.scrollViewportTo(visualRow, visualCol);
    hot.render();
    const cell = hot.getCell(visualRow, visualCol);
    if (!cell) return null;
    const box = cell.getBoundingClientRect();
    return { x: box.left + box.width / 2, y: box.top + box.height / 2 };
  }, { name: moduleName, visualRow: row, visualCol: col });
  expect(rect, `${moduleName} cell ${row}:${col}`).toBeTruthy();
  await page.mouse.click(rect.x, rect.y);
}

async function editOperationalCell(page, moduleName, row, col, value) {
  await page.evaluate(({ name, visualRow, visualCol }) => {
    document.querySelectorAll('[data-e2e-operational-editor]')
      .forEach((element) => element.removeAttribute('data-e2e-operational-editor'));
    const hot = window[name]?.getHotInstance?.();
    if (!hot) throw new Error(`Handsontable module not found: ${name}`);
    hot.scrollViewportTo(visualRow, visualCol);
    hot.selectCell(visualRow, visualCol);
    const editor = hot.getActiveEditor();
    if (!editor?.TEXTAREA) throw new Error(`Active editor not found: ${name}`);
    editor.beginEditing();
    editor.TEXTAREA.setAttribute('data-e2e-operational-editor', 'true');
  }, { name: moduleName, visualRow: row, visualCol: col });
  const editor = page.locator('textarea[data-e2e-operational-editor="true"]:visible');
  await expect(editor).toHaveCount(1);
  await editor.focus();
  await page.keyboard.press('ControlOrMeta+A');
  await editor.pressSequentially(String(value), { delay: 20 });
  await page.keyboard.press('Enter');
  await page.evaluate(() => document.querySelectorAll('[data-e2e-operational-editor]')
    .forEach((element) => element.removeAttribute('data-e2e-operational-editor')));
}

export class TemporaryProjectAccess {
  constructor(project, username = TEST_USERNAME) {
    this.project = project;
    this.username = username;
    this.original = null;
    this.granted = false;
  }

  grant(role = 'A') {
    const userId = scalar(
      `SELECT id FROM general_usuarios WHERE usuario=${sqlString(this.username)} AND activo=1 LIMIT 1;`,
    );
    expect(userId, `Active test user ${this.username}`).toBeGreaterThan(0);
    const row = firstRow(
      `SELECT id, role FROM project_members WHERE project_id=${this.project.projectId} AND user_id=${userId} LIMIT 1;`,
    );
    this.original = row.length === 2 ? { id: Number(row[0]), role: row[1] } : null;
    this.userId = userId;

    if (this.original) {
      runSql(`UPDATE project_members SET role=${sqlString(role)} WHERE id=${this.original.id};`);
    } else {
      runSql(`INSERT INTO project_members (project_id, user_id, role) VALUES (${this.project.projectId}, ${userId}, ${sqlString(role)});`);
    }
    expect(firstRow(
      `SELECT role FROM project_members WHERE project_id=${this.project.projectId} AND user_id=${userId};`,
    )[0]).toBe(role);
    this.granted = true;
    return this;
  }

  restore() {
    if (!this.granted || !this.userId) return;
    if (this.original) {
      runSql(`UPDATE project_members SET role=${sqlString(this.original.role)} WHERE id=${this.original.id};`);
    } else {
      runSql(`DELETE FROM project_members WHERE project_id=${this.project.projectId} AND user_id=${this.userId};`);
    }
    this.granted = false;
  }
}

export function createOperationalFixture(project) {
  const week = Number(project.operationalWeek);
  const program = firstRow(`
    SELECT p.unique_id, p.Consecutivo, pc.Consecutivo
    FROM programa p
    JOIN programa_consolidado pc
      ON pc.project_id=p.project_id
      AND pc.unique_id=p.unique_id
      AND pc.Semana=${week}
    WHERE p.project_id=${project.projectId}
      AND COALESCE(p.Titulo, 0)=0
    ORDER BY p.Consecutivo, pc.Consecutivo
    LIMIT 1;
  `);
  expect(program.length, `${project.name} needs a PG/PI activity in week ${week}`).toBe(3);

  const token = `E2E-P${project.projectId}-S${week}`;
  return {
    token,
    projectId: project.projectId,
    week,
    programUniqueId: Number(program[0]),
    programConsecutive: Number(program[1]),
    intermediateConsecutive: Number(program[2]),
    professionalId: 990000 + project.projectId,
    professionalName: `${token}-RESPONSABLE`,
    subcontractorId: 991000 + project.projectId,
    subcontractorName: `${token}-${project.constructionOnly ? 'SUBCONTRATISTA' : 'INTERESADO'}`,
    weeklyConsecutive: 992000 + project.projectId,
    weeklyActivity: `${token}-ACTIVIDAD-SEMANAL`,
    cicId: 993000 + project.projectId,
    cnpConsecutive: 994000 + project.projectId,
    cnpActivity: `${token}-ACTIVIDAD-CNP`,
    pgUnit: project.projectId % 2 === 0 ? 'm2' : 'ml',
    piObservation: `${token}-OBSERVACION-PI`,
    sharedNote: `${token}-RESTRICCION-COMPARTIDA`,
    familyName: `${token}-FAMILIA-PISOS`,
    familyDescription: `${token}-DESCRIPCION-COMPRAS`,
    assistantFamilyName: `${token}-FAMILIA-ASISTENTE`,
    assistantFamilyDescription: `${token}-DESCRIPCION-ASISTENTE`,
    packageName: `${token}-PAQUETE-MO`,
    resourceName: `${token}-RECURSO`,
    pdcObservation: `${token}-OBSERVACION-PDC`,
    baselineWeeklyRows: scalar(
      `SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${project.projectId} AND Semana=${week};`,
    ),
  };
}

function prepareWeeklyFixture(project, fixture) {
  const id = project.projectId;
  const nit = 800000000 + id;
  expect(scalar(`SELECT COUNT(*) FROM profesionales WHERE project_id=${id} AND id=${fixture.professionalId};`)).toBe(0);
  expect(scalar(`SELECT COUNT(*) FROM subcontratistas WHERE project_id=${id} AND Id=${fixture.subcontractorId};`)).toBe(0);
  expect(scalar(`SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${id} AND Consecutivo=${fixture.weeklyConsecutive};`)).toBe(0);
  expect(scalar(`SELECT COUNT(*) FROM cic WHERE project_id=${id} AND Id=${fixture.cicId};`)).toBe(0);
  expect(scalar(`SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${id} AND Consecutivo=${fixture.cnpConsecutive};`)).toBe(0);

  runSql(`
    INSERT INTO profesionales (project_id, id, nombre, email, cargo, activo)
    VALUES (${id}, ${fixture.professionalId}, ${sqlString(fixture.professionalName)},
      ${sqlString(`e2e-p${id}@example.test`)}, ${sqlString(project.professionalCargo)}, 1);
    INSERT INTO subcontratistas
      (project_id, Id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo)
    VALUES (${id}, ${fixture.subcontractorId}, ${sqlString(fixture.subcontractorName)},
      ${sqlString(`e2e-provider-p${id}@example.test`)}, ${nit}, 'E2E QA', ${sqlString(project.providerType)}, 1);
    UPDATE semanas_activas
      SET Semanal_Confirmada=0, fechaCierreCompromisos=NULL
      WHERE project_id=${id} AND Semana=${fixture.week};
    UPDATE programacion_semanal SET Activa='NA'
      WHERE project_id=${id} AND Semana=${fixture.week};
    INSERT INTO programacion_semanal
      (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa,
       Id, Actividad, Descripcion, Ubicacion, Fecha_Inicio, Fecha_Fin, Empresa,
       Ejecutado, Unidad, cantidad_ppto, Cantidad_Sugerida, Compromiso, Ejecutado_Real,
       Activa, Categoria_CNP, CNP, Observaciones_CNP, Categoria_CNC, CNC, Observaciones_CNC)
    SELECT ${id}, ${fixture.weeklyConsecutive}, ${fixture.weeklyConsecutive}, ${fixture.week},
      ${fixture.programUniqueId}, ${fixture.programConsecutive}, ${sqlString(fixture.token)},
      ${sqlString(fixture.weeklyActivity)}, 'E2E fixture restaurable', 'E2E QA',
      sa.Fecha_Inicio_Sem, sa.Fecha_Fin_Sem, 'AIA', 0, 'und', 10, 2, 1, 0, '1',
      'Programación', 'Otra', ${sqlString(`${fixture.token}-CNP`)},
      'Programación', 'Otra', ${sqlString(`${fixture.token}-CNC`)}
    FROM semanas_activas sa
    WHERE sa.project_id=${id} AND sa.Semana=${fixture.week};
    INSERT INTO programacion_semanal
      (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa,
       Id, Actividad, Descripcion, Ubicacion, Fecha_Inicio, Fecha_Fin, Empresa,
       Ejecutado, Unidad, cantidad_ppto, Compromiso, Ejecutado_Real, Activa,
       Responsable_AIA, Categoria_CNP, CNP, Observaciones_CNP)
    SELECT ${id}, ${fixture.cnpConsecutive}, ${fixture.cnpConsecutive}, ${fixture.week},
      ${fixture.programUniqueId}, ${fixture.programConsecutive}, ${sqlString(`${fixture.token}-CNP`)},
      ${sqlString(fixture.cnpActivity)}, 'E2E fixture CNP restaurable', 'E2E QA',
      sa.Fecha_Inicio_Sem, sa.Fecha_Fin_Sem, 'AIA', 0, 'und', 10, 0, 0, '0',
      ${sqlString(fixture.professionalName)}, 'Programación', 'Otra', ${sqlString(`${fixture.token}-CNP`)}
    FROM semanas_activas sa
    WHERE sa.project_id=${id} AND sa.Semana=${fixture.week};
    INSERT INTO cic
      (project_id, Id, Semana, subcontratista, correo_contacto, NIT, alcance,
       tipo_proveedor, PAC, PAC_Acum, P_Completado, P_Completado_Acum, Observaciones)
    VALUES (${id}, ${fixture.cicId}, ${fixture.week}, ${sqlString(fixture.subcontractorName)},
      ${sqlString(`e2e-provider-p${id}@example.test`)}, ${sqlString(String(nit))}, 'E2E QA',
      ${sqlString(project.providerType)}, '100%', '100%', '100%', '100%', ${sqlString(`${fixture.token}-CIC`)});
  `);
}

async function runProgramGeneral(page, project, fixture) {
  await changeWeek(page, fixture.week, '/programa-general');
  await assertProjectContext(page, project);
  await assertRestrictionConfig(page, project);
  await waitForHotModule(page, 'PGHotModule');

  const target = await hotTarget(page, 'PGHotModule', fixture, 'unidad');
  expect(target, `${project.name} editable PG unit`).toBeTruthy();
  const nextValue = target.value === fixture.pgUnit ? (fixture.pgUnit === 'ml' ? 'm2' : 'ml') : fixture.pgUnit;
  const save = page.waitForResponse((response) => (
    isPostTo(response, '/api/general/update')
      && new URLSearchParams(response.request().postData() || '').get('unidad') === nextValue
  ));
  await editOperationalCell(page, 'PGHotModule', target.visualRow, target.col, nextValue);
  const payload = await responsePayload(await save);
  expectSuccessfulPayload(payload, 'PG update');

  await page.reload({ waitUntil: 'domcontentloaded' });
  await waitForHotModule(page, 'PGHotModule');
  await assertHotValue(page, 'PGHotModule', target, 'unidad', nextValue);
}

async function runProgramacionIntermedia(page, project, fixture) {
  await changeWeek(page, fixture.week, '/programacion-intermedia');
  await waitForHotModule(page, 'PIHotModule');
  const target = await hotTarget(page, 'PIHotModule', fixture, 'Observaciones');
  expect(target, `${project.name} editable PI observation`).toBeTruthy();
  const save = page.waitForResponse((response) => isPostTo(response, '/api/pi/save', 'modificar'));
  await editOperationalCell(page, 'PIHotModule', target.visualRow, target.col, fixture.piObservation);
  expectSuccessfulPayload(await responsePayload(await save), 'PI save');

  await page.reload({ waitUntil: 'domcontentloaded' });
  await waitForHotModule(page, 'PIHotModule');
  await assertHotValue(page, 'PIHotModule', target, 'Observaciones', fixture.piObservation);

  const selectableRows = await page.evaluate(() => {
    const hot = window.PIHotModule.getHotInstance();
    const rows = [];
    for (let physical = 0; physical < hot.countSourceRows() && rows.length < 2; physical += 1) {
      const row = hot.getSourceDataAtRow(physical);
      if (!row || Number(row.Titulo || 0) === 1 || !Number(row.unique_id || row.Consecutivo_en_Programa)) continue;
      const visual = hot.toVisualRow(physical);
      if (visual != null && visual >= 0) {
        rows.push({ visual, id: Number(row.unique_id || row.Consecutivo_en_Programa) });
      }
    }
    return rows;
  });
  expect(selectableRows.length, `${project.name} shared PI rows`).toBe(2);
  for (const row of selectableRows) await clickHotCell(page, 'PIHotModule', row.visual, 1);

  await page.locator('#btn-shared-constraint').click();
  const modal = page.locator('#modal_shared_constraint');
  await expect(modal).toBeVisible();
  const restriction = project.hardRestrictions[0];
  await modal.locator('#btn_pi_shared_clear_restrictions').click();
  await modal.locator(`#piSharedRestriction_${restriction}`).check({ force: true });
  const valueSelect = modal.locator(`.pi-shared-restriction-value[data-restriction-type="${restriction}"]`);
  const options = await valueSelect.locator('option').evaluateAll((items) => items
    .map((item) => item.value).filter(Boolean));
  expect(options.length, `${restriction} options`).toBeGreaterThan(0);
  await valueSelect.selectOption(options.at(-1));
  await modal.locator('#piSharedNote').fill(fixture.sharedNote);

  const preview = page.waitForResponse((response) => isPostTo(
    response,
    '/programacion-intermedia/shared-constraints/preview',
  ));
  await modal.locator('#btn_pi_shared_preview').click();
  expectSuccessfulPayload(await responsePayload(await preview), 'PI shared preview');
  await expect(modal.locator('#piSharedPreview')).not.toContainText('Seleccione filas');

  const apply = page.waitForResponse((response) => isPostTo(
    response,
    '/programacion-intermedia/shared-constraints/apply',
  ));
  await modal.locator('#btn_pi_shared_apply').click();
  const confirm = page.locator('.swal2-confirm:visible');
  if (await confirm.isVisible({ timeout: 1_000 }).catch(() => false)) await confirm.click();
  expectSuccessfulPayload(await responsePayload(await apply), 'PI shared apply');
  await expect(modal).toBeHidden({ timeout: 30_000 });

  await page.reload({ waitUntil: 'domcontentloaded' });
  await waitForHotModule(page, 'PIHotModule');
  const persisted = scalar(`
    SELECT COUNT(*) FROM programa_consolidado
    WHERE project_id=${project.projectId} AND Semana=${fixture.week}
      AND unique_id IN (${selectableRows.map((row) => row.id).join(',') || '0'})
      AND ${restriction}=${sqlString(options.at(-1))};
  `);
  expect(persisted, `${project.name} shared restriction persisted`).toBe(selectableRows.length);
}

async function autoprogramIfNeeded(page, fixture) {
  if (fixture.baselineWeeklyRows > 0) return;
  await changeWeek(page, fixture.week, '/programacion-semanal');
  await waitForHotModule(page, 'PSHotModule');
  const auto = page.waitForResponse((response) => isPostTo(response, '/api/semanal/save', 'autoprogramar'));
  await page.locator('#btn_autoprogramar').click();
  const continueButton = page.locator('#btnConfirmarAutoprogramar');
  if (await continueButton.isVisible({ timeout: 3_000 }).catch(() => false)) await continueButton.click();
  expectSuccessfulPayload(await responsePayload(await auto), 'PS autoprogram');
  const restrictionModal = page.locator('#modalRestriccionesFaltantes');
  if (await restrictionModal.isVisible({ timeout: 1_000 }).catch(() => false)) {
    await restrictionModal.locator('button[data-dismiss="modal"]').last().click();
  }
}

async function runProgramacionSemanal(page, project, fixture) {
  await autoprogramIfNeeded(page, fixture);
  prepareWeeklyFixture(project, fixture);
  await changeWeek(page, fixture.week, '/programacion-semanal');
  await waitForHotModule(page, 'PSHotModule');

  const target = await page.evaluate((consecutive) => {
    const hot = window.PSHotModule.getHotInstance();
    const physical = hot.getSourceData().findIndex((row) => Number(row.Consecutivo) === Number(consecutive));
    if (physical < 0) return null;
    return {
      visualRow: hot.toVisualRow(physical),
      consecutive,
      uniqueId: Number(hot.getSourceDataAtRow(physical).unique_id || 0),
      subCol: hot.propToCol('Sub_Contratista'),
      responsibleCol: hot.propToCol('Responsable_AIA'),
      commitmentCol: hot.propToCol('Compromiso'),
    };
  }, fixture.weeklyConsecutive);
  expect(target, `${project.name} PS fixture row`).toBeTruthy();

  for (const edit of [
    { col: target.subCol, value: fixture.subcontractorName, label: 'subcontractor' },
    { col: target.responsibleCol, value: fixture.professionalName, label: 'responsible' },
    { col: target.commitmentCol, value: '2', label: 'commitment' },
  ]) {
    const visualRow = await page.evaluate((consecutive) => {
      const hot = window.PSHotModule.getHotInstance();
      const physical = hot.getSourceData()
        .findIndex((row) => Number(row?.Consecutivo) === Number(consecutive));
      return physical < 0 ? -1 : hot.toVisualRow(physical);
    }, fixture.weeklyConsecutive);
    expect(visualRow, `${project.name} PS fixture visual row before ${edit.label}`).toBeGreaterThanOrEqual(0);
    const save = page.waitForResponse((response) => (
      isPostTo(response, '/api/semanal/save', 'modificar')
        && new URLSearchParams(response.request().postData() || '').get('Id')
          === String(fixture.weeklyConsecutive)
    ));
    await editOperationalCell(page, 'PSHotModule', visualRow, edit.col, edit.value);
    expectSuccessfulPayload(await responsePayload(await save), `PS ${edit.label}`);
  }

  await page.reload({ waitUntil: 'domcontentloaded' });
  await waitForHotModule(page, 'PSHotModule');
  const saved = firstRow(`
    SELECT Sub_Contratista, Responsable_AIA, Compromiso
    FROM programacion_semanal
    WHERE project_id=${project.projectId} AND Consecutivo=${fixture.weeklyConsecutive};
  `);
  expect(saved).toEqual([fixture.subcontractorName, fixture.professionalName, '2']);

  await page.locator('#btn_cerrar_compromisos_semana').click();
  await expect(page.locator('#modal_cerrar_compromisos')).toBeVisible();
  const confirm = page.waitForResponse((response) => isPostTo(response, '/api/semanal/save', 'bloquear_compromisos'));
  await page.locator('#btn_confirmar_compromisos_semana').click();
  const confirmPayload = await responsePayload(await confirm);
  expect(['Bloqueado', 'No_Bloqueado']).toContain(String(confirmPayload?.respuesta ?? confirmPayload));
  expect(String(confirmPayload?.respuesta ?? confirmPayload), JSON.stringify(confirmPayload)).toBe('Bloqueado');
  const acknowledgement = page.locator('#btn_cerrar_aceptar_compromisos_semana');
  if (await acknowledgement.isVisible({ timeout: 2_000 }).catch(() => false)) await acknowledgement.click();

  for (const section of [
    { path: '/programacion-semanal/cnp', endpoint: '/api/cnp/list', marker: fixture.cnpActivity },
    { path: '/programacion-semanal/cnc', endpoint: '/api/cnc/list', marker: fixture.weeklyActivity },
    { path: '/programacion-semanal/cic', endpoint: '/api/cic/list', marker: fixture.subcontractorName },
  ]) {
    const list = page.waitForResponse((response) => isPostTo(response, section.endpoint));
    await changeWeek(page, fixture.week, section.path);
    await responsePayload(await list, section.endpoint);
    await expect(page.locator('#dt_cliente')).toBeVisible({ timeout: 30_000 });
    await expect(page.locator('body')).toContainText(section.marker, { timeout: 30_000 });
  }
}

export async function runLastPlannerCycle(page, project, fixture) {
  await assertNavbarForProject(page, project);
  await runProgramGeneral(page, project, fixture);
  await runProgramacionIntermedia(page, project, fixture);
  await runProgramacionSemanal(page, project, fixture);

  if (!project.constructionOnly) {
    await page.goto('/subcontratistas', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.header-actions h4')).toContainText('Interesados Externos');
    for (const id of project.expectedHiddenNav) {
      await expect(page.locator(`#${id}:visible`), `${id} must remain hidden`).toHaveCount(0);
    }
  }
}

// El ciclo de compras del PDC v1 (crear familia -> definir contrato -> asistente de contratos
// -> actualizar PDC) se eliminó el 2026-08-04 con el módulo. `runPurchasingCycle` ya no existe;
// el sucesor, Plan de Compras v2, se cubre en tests/browser/pdc-v2-*.spec.mjs.

export function assertProjectHasNoE2ERows(project) {
  const id = project.projectId;
  const count = scalar(`
    SELECT SUM(n) FROM (
      SELECT COUNT(*) n FROM profesionales WHERE project_id=${id} AND (nombre LIKE 'E2E-%' OR email LIKE 'e2e-%')
      UNION ALL SELECT COUNT(*) FROM subcontratistas WHERE project_id=${id} AND (subcontratista LIKE 'E2E-%' OR correo_contacto LIKE 'e2e-%')
      UNION ALL SELECT COUNT(*) FROM programacion_semanal WHERE project_id=${id} AND (Actividad LIKE 'E2E-%' OR Descripcion LIKE 'E2E-%')
      UNION ALL SELECT COUNT(*) FROM cic WHERE project_id=${id} AND Observaciones LIKE 'E2E-%'
    ) scoped_e2e;
  `);
  expect(count, `${project.name} must not retain E2E rows`).toBe(0);
}

export function cleanupOperationalFixture(fixture) {
  if (!fixture?.packageName) return;
  runSql(`
    DELETE FROM general_dias_procesos_contratacion
    WHERE paqueteContratacion=${sqlString(fixture.packageName)};
  `);
}
