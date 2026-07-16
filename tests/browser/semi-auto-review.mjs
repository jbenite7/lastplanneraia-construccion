import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { DatabaseSnapshot, runSql } from './support/dbSnapshot.mjs';
import {
  E2ERestorationScope,
  ScopedFileSnapshot,
  assertE2EMutationConsent,
  maybeInjectE2EFailure,
} from './support/restoration.mjs';
import { changeWeek, loginAndSelectProject, logout } from './support/session.mjs';

// allow: SIZE_OK — this Playwright narrative owns one semi-auto review surface end to end.
assertE2EMutationConsent();

const project = PROJECTS.find((item) => item.key === 'construction') || PROJECTS[0];

const modules = [
  { key: 'listado-actividades', url: '/listado-actividades' },
  { key: 'contratos', url: '/contratos' },
  { key: 'pdc', url: '/pdc' },
];

const runtimeErrorsByPage = new WeakMap();

const feedbackFamilies = [
  'REVOQUE_HUMEDO',
  'REVOQUE_SECO',
  'CABINAS_BANO',
  'CARPINTERIA_METALICA',
  'PLANTA_ELECTRICA',
  'MALACATE',
  'GRIFERIAS_INCRUSTACIONES',
  'GEODREN',
  'ASEO',
  'BOTADA_ESCOMBROS',
  'AMENIDADES_CUBIERTA',
];

const activeListadoFamilies = [
  'REVOQUE_HUMEDO',
  'REVOQUE_SECO',
  'CABINAS_BANO',
  'CARPINTERIA_METALICA',
  'PLANTA_ELECTRICA',
  'GRIFERIAS_INCRUSTACIONES',
  'ASEO',
];

const contractualOnlyFamilies = [
  'GEODREN',
  'MALACATE',
  'BOTADA_ESCOMBROS',
  'AMENIDADES_CUBIERTA',
];

const reviewFamilies = [
  'CAMPAMENTO',
  'RED_TELECOMUNICACIONES',
  'ASEO',
  'BOTADA_ESCOMBROS',
  'AMENIDADES_CUBIERTA',
];

function sqlNumber(sql) {
  const output = runSql(sql).trim();
  return Number(output.split(/\s+/).filter(Boolean).at(-1) || 0);
}

function sqlList(values) {
  return values.map((value) => `'${String(value).replace(/'/g, "''")}'`).join(',');
}

function expectAtLeastOne(sql, message) {
  expect(sqlNumber(sql), message).toBeGreaterThan(0);
}

function contractItemSql(family, type, name) {
  return `
    SELECT COUNT(*)
    FROM general_pdc_familias f
    JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
    JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
    WHERE f.codigo = '${family}'
      AND i.tipo_paquete = '${type}'
      AND i.paquete_nombre = '${name}'
  `;
}

function contractPackageSql(family, name) {
  return `
    SELECT COUNT(*)
    FROM general_pdc_familias f
    JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
    JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
    WHERE f.codigo = '${family}' AND i.paquete_nombre = '${name}'
  `;
}

function expectDaPortoFeedbackModel() {
  expect(sqlNumber(`
    SELECT COUNT(DISTINCT codigo)
    FROM general_pdc_familias
    WHERE codigo IN (${sqlList(feedbackFamilies)})
  `), 'Da Porto feedback families').toBe(feedbackFamilies.length);

  expect(sqlNumber(`
    SELECT COUNT(DISTINCT f.codigo)
    FROM general_pdc_activity_rules r
    JOIN general_pdc_familias f ON f.id = r.familia_id
    WHERE r.activa = 1 AND f.codigo IN (${sqlList(activeListadoFamilies)})
  `), 'Da Porto active Listado activity rules').toBe(activeListadoFamilies.length);

  expect(sqlNumber(`
    SELECT COUNT(DISTINCT codigo)
    FROM general_pdc_familias
    WHERE COALESCE(activa, 1) = 0 AND codigo IN (${sqlList(contractualOnlyFamilies)})
  `), 'Da Porto contractual-only families inactive for Listado').toBe(contractualOnlyFamilies.length);
}

function expectDaPortoContractOptions() {
  const cases = [
    ['SANITARIOS', 'Suministro', 'APARATOS SANITARIOS'],
    ['SANITARIOS', 'Mano de Obra', 'INSTALACION APARATOS SANITARIOS'],
    ['GRIFERIAS_INCRUSTACIONES', 'Orden de Compra', 'GRIFERIAS E INCRUSTACIONES'],
    ['PINTURAS', '', 'PINTURAS'],
    ['PLANTA_ELECTRICA', '', 'PLANTA ELECTRICA'],
    ['CARPINTERIA_METALICA', '', 'CARPINTERIA METALICA'],
    ['CARPINTERIA_MADERA', 'Suministro', 'CARPINTERIA MADERA - FABRICACION Y SUMINISTRO'],
    ['CARPINTERIA_MADERA', 'Mano de Obra', 'CARPINTERIA MADERA - INSTALACION'],
  ];

  for (const [family, type, name] of cases) {
    const sql = type ? contractItemSql(family, type, name) : contractPackageSql(family, name);
    expectAtLeastOne(sql, `${family} ${type || name}`);
  }
  expectAtLeastOne(
    contractItemSql('MALACATE', 'Equipos', 'MALACATE'),
    'MALACATE equipo',
  );
}

function expectReviewRequiredFamilies() {
  expect(sqlNumber(`
    SELECT COUNT(DISTINCT codigo)
    FROM general_pdc_familias
    WHERE siempre_revision = 1 AND codigo IN (${sqlList(reviewFamilies)})
  `), 'Da Porto review-required families').toBe(reviewFamilies.length);
}

function expectCanonicalProgramKeys() {
  expectAtLeastOne(`
    SELECT COUNT(*)
    FROM programa_consolidado pc
    JOIN programa p ON p.project_id = pc.project_id AND p.unique_id = pc.unique_id
    WHERE pc.project_id = ${project.projectId}
      AND pc.unique_id = pc.Consecutivo_en_Programa
      AND pc.row_id = pc.Consecutivo
  `, 'programa_consolidado joins programa by project_id + unique_id');

  expectAtLeastOne(`
    SELECT COUNT(*)
    FROM actividades a
    JOIN programa_consolidado pc
      ON pc.project_id = a.project_id AND pc.unique_id = a.actividadInicio
    WHERE a.project_id = ${project.projectId}
      AND a.actividadInicio > 0
  `, 'listado actividadInicio joins programa_consolidado by project_id + unique_id');
}

async function openReviewPanel(page, moduleKey) {
  await page.waitForFunction(() => window.jQuery && window.SemiAutoReview, null, { timeout: 20000 });
  await page.evaluate((key) => window.SemiAutoReview.open(key), moduleKey);

  const panel = page.locator(`#semiAutoReview-${moduleKey}`);
  await expect(panel).toBeVisible({ timeout: 10000 });
  await expect(panel.locator('.sar-title')).toContainText('Bandeja de decisiones');
  await expect(panel.locator('.sar-tab')).toContainText([
    'Decisión',
    'Listas',
    'Todo',
  ]);
  await expect(panel.locator('.sar-filter-band')).toBeVisible();
  await expect(panel.locator('.sar-filter-text')).toBeVisible();
  await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90000 });
  maybeInjectE2EFailure(`semi-auto-review:preview:${moduleKey}`);
  await expect(panel.locator('.sar-analysis')).toContainText('Estamos revisando tus propuestas');
  await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
  const assistant = panel.locator('.sar-assistant');
  const assistantGrid = assistant.locator('.sar-assistant-grid');
  const assistantToggle = assistant.locator('.sar-assistant-toggle');
  await expect(assistant).toContainText('Asistente AIA');
  await expect(assistantGrid).toBeVisible();
  await expect(assistantToggle).toHaveAttribute('aria-expanded', 'true');
  await expect(assistantToggle).toHaveText('Ocultar detalle');

  await assistantToggle.click();
  await expect(assistantGrid).not.toBeVisible();
  await expect(assistantToggle).toHaveAttribute('aria-expanded', 'false');
  await expect(assistantToggle).toHaveText('Ver detalle');

  await assistantToggle.click();
  await expect(assistantGrid).toBeVisible();
  await expect(assistantToggle).toHaveAttribute('aria-expanded', 'true');
  await expect(assistantToggle).toHaveText('Ocultar detalle');
  await expect(assistant).toContainText('Recomendaciones');
  await expect(assistant).toContainText('Alertas');
  await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
  await panel.locator('.sar-tab', { hasText: 'Todo' }).click();
  await expect(panel.locator('.sar-filter-band')).toHaveValue('all');
  await panel.locator('.sar-tab', { hasText: 'Listas' }).click();
  await expect(panel.locator('.sar-filter-band')).toHaveValue('ready');
  await panel.locator('.sar-tab', { hasText: 'Decisión' }).click();
  await expect(panel.locator('.sar-filter-band')).toHaveValue('decision');

  const cardCount = await panel.locator('.sar-card').count();
  if (cardCount > 0) {
    await expect(panel.locator('.sar-card').first().locator('.sar-row-check')).toBeVisible();
    await expect(panel.locator('.sar-card').first().locator('.sar-badge')).toBeVisible();
    await expect(panel.locator('.sar-card').first().locator('.sar-review-btn')).toContainText(/Detalle|Ver detalle/);
  }

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
  expect(visibleText).not.toContain('Sin dato');
}

test.describe('Semi-auto review panel', () => {
  test.describe.configure({ timeout: 240000 });
  let restoration;

  test.beforeEach(async ({ page }) => {
    restoration = new E2ERestorationScope(
      new DatabaseSnapshot(),
      new ScopedFileSnapshot([]),
    );
    restoration.capture();
    runtimeErrorsByPage.set(page, installErrorCollectors(page));
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/programacion-semanal');
  });

  test.afterEach(async ({ page }) => {
    const cleanupErrors = [];
    const errors = runtimeErrorsByPage.get(page);
    try {
      if (errors) assertNoRuntimeErrors(errors);
    } catch (error) {
      cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
    }
    try {
      await logout(page);
    } catch (error) {
      cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
    }
    if (restoration) {
      try {
        const receipt = restoration.restore();
        console.info(`E2E_RESTORATION_RECEIPT ${JSON.stringify(receipt)}`);
      } catch (error) {
        cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
      }
      try {
        restoration.dispose();
      } catch (error) {
        cleanupErrors.push(error instanceof Error ? error : new Error(String(error)));
      }
      restoration = null;
    }
    if (cleanupErrors.length > 0) {
      throw new AggregateError(cleanupErrors, 'Semi-auto review E2E cleanup failed.');
    }
  });

  for (const module of modules) {
    test(`preview embedded panel in ${module.key}`, async ({ page }) => {
      await page.goto(module.url, { waitUntil: 'networkidle', timeout: 30000 });
      await openReviewPanel(page, module.key);
    });
  }

  test('Da Porto feedback model covers the three semi-auto modules', async ({ page }) => {
    expectDaPortoFeedbackModel();
    expectDaPortoContractOptions();
    expectReviewRequiredFamilies();
    expectCanonicalProgramKeys();

    await page.waitForURL('**/programacion-semanal', { timeout: 15000 });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    for (const module of modules) {
      await page.goto(module.url, { waitUntil: 'networkidle', timeout: 30000 });
      await openReviewPanel(page, module.key);
    }
  });

  test('listado create activity review uses source selector and multiple modalities', async ({ page }, testInfo) => {
    await page.goto('/listado-actividades?semana=1', { waitUntil: 'networkidle', timeout: 30000 });
    await openReviewPanel(page, 'listado-actividades');

    const panel = page.locator('#semiAutoReview-listado-actividades');
    let card = panel.locator('.sar-card', { hasText: 'Propuesta para crear actividad' }).first();
    if (await card.count() === 0) {
      await panel.locator('.sar-tab', { hasText: 'Revisar' }).click();
      card = panel.locator('.sar-card', { hasText: 'Propuesta para crear actividad' }).first();
    }
    await expect(card, 'Da Porto semana 1 debe tener propuesta de crear actividad').toBeVisible();

    const sourceSelect = card.locator('select.sar-source-select');
    if (!(await sourceSelect.isVisible())) {
      await card.locator('.sar-review-btn').click();
    }
    await expect(sourceSelect).toBeVisible();
    await expect(sourceSelect.locator('option').first()).toContainText(/Fecha|20\d{2}-\d{2}-\d{2}|confianza/i);
    await expect(card.locator('.sar-source-context')).toContainText('Basado en el programa general:');
    await expect(card.locator('.sar-source-context')).toContainText('Inicio:');

    const optionValues = await sourceSelect.locator('option').evaluateAll((options) => options.map((option) => option.value));
    if (optionValues.length > 1) {
      const previousContext = await card.locator('.sar-source-context').innerText();
      await sourceSelect.selectOption(optionValues.at(-1));
      await expect(card.locator('.sar-source-context')).not.toHaveText(previousContext);
    }

    const detailText = await card.locator('.sar-detail').innerText();
    expect(detailText).not.toContain('Semana');
    expect(detailText).not.toContain('Actividad de inicio');
    expect(detailText).not.toContain('Fecha de inicio');

    const si = card.locator('.sar-modality-check[value="SI"]');
    const mo = card.locator('.sar-modality-check[value="MO"]');
    const suministro = card.locator('.sar-modality-check[value="S"]');
    const oc = card.locator('.sar-modality-check[value="OC"]');
    await expect(si).toBeVisible();
    await expect(mo).toBeVisible();
    await expect(suministro).toBeVisible();
    await expect(oc).toBeVisible();

    if (await si.isChecked()) {
      await expect(mo).toBeDisabled();
      await expect(suministro).toBeDisabled();
      await expect(oc).toBeDisabled();
      await si.uncheck();
      await expect(mo).toBeEnabled();
      await expect(suministro).toBeEnabled();
      await expect(oc).toBeEnabled();
    }
    await mo.check();
    await suministro.check();
    await expect(si).toBeDisabled();
    await expect(card.locator('.sar-modality-value')).toHaveValue('MO,S');
    await card.screenshot({
      path: testInfo.outputPath('listado-selector-modalidad.png'),
    });

    const visibleText = await panel.evaluate((el) => el.innerText);
    expect(visibleText).toContain('Basado en el programa general');
    expect(visibleText).not.toContain('selectedSourceId');
    expect(visibleText).not.toContain('semanaActualizacion');
  });

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
