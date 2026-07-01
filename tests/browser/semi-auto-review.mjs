import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { assertNoRuntimeErrors, installErrorCollectors } from './support/assertions.mjs';
import { runSql } from './support/dbSnapshot.mjs';
import { changeWeek, loginAndSelectProject } from './support/session.mjs';

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
  'ESPEJOS',
  'CABINAS_BANO',
  'BARANDAS_BALCON',
  'PASAMANOS_CERRAJERIA',
  'PLANTA_ELECTRICA',
  'MALACATE',
  'GRIFERIAS_INCRUSTACIONES',
  'GEODREN',
  'ASEO',
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
    WHERE r.activa = 1 AND f.codigo IN (${sqlList(feedbackFamilies)})
  `), 'Da Porto feedback activity rules').toBe(feedbackFamilies.length);
}

function expectDaPortoContractOptions() {
  const cases = [
    ['SANITARIOS', 'Suministro', 'APARATOS SANITARIOS'],
    ['SANITARIOS', 'Mano de Obra', 'INSTALACION APARATOS SANITARIOS'],
    ['GRIFERIAS_INCRUSTACIONES', 'Orden de Compra', 'GRIFERIAS E INCRUSTACIONES'],
    ['PINTURAS', '', 'PINTURAS'],
    ['PLANTA_ELECTRICA', '', 'PLANTA ELECTRICA'],
    ['PASAMANOS_CERRAJERIA', 'Suministro', 'PASAMANOS ESCALERAS'],
    ['PASAMANOS_CERRAJERIA', 'Mano de Obra', 'INSTALACION PASAMANOS ESCALERAS'],
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
  await expect(panel.locator('.sar-tab')).toContainText([
    'Aplicar automático',
    'Revisar',
  ]);
  await expect(panel.locator('.sar-filter-text')).toBeVisible();
  await expect(panel.locator('.sar-status')).toContainText('Análisis listo', { timeout: 90000 });
  await expect(panel.locator('.sar-analysis')).toContainText('Proceso de análisis');
  await expect(panel.locator('.sar-analysis-progress')).toContainText('100%');
  await expect(panel.locator('.sar-assistant')).toContainText('Asistente AIA');
  await expect(panel.locator('.sar-assistant')).toContainText('Recomendaciones');
  await expect(panel.locator('.sar-assistant')).toContainText('Alertas');
  await expect(panel.locator('.sar-summary')).toContainText('Encontramos', { timeout: 10000 });
  await expect(panel.locator('.sar-group-title')).toContainText([
    'Aplicar automático',
  ]);
  await panel.locator('.sar-tab', { hasText: 'Revisar' }).click();
  await expect(panel.locator('.sar-group-title').filter({ hasText: /Revisar|Revisar manualmente/ }).first()).toBeVisible();

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

  test.beforeEach(async ({ page }) => {
    runtimeErrorsByPage.set(page, installErrorCollectors(page));
    await loginAndSelectProject(page, project);
    await changeWeek(page, project.maxWeek, '/programacion-semanal');
  });

  test.afterEach(async ({ page }) => {
    const errors = runtimeErrorsByPage.get(page);
    if (errors) assertNoRuntimeErrors(errors);
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
