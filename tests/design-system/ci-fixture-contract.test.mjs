import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const fixtureUrl = new URL('../../database/fixtures/design-system-ci.sql', import.meta.url);

test('synthetic fixture declares both isolated projects, users, catalogs and substantive module data', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  for (const token of [
    "(73, 'Da Porto'",
    "(75, 'Aeropuerto Regional PC'",
    'INSERT INTO `general_usuarios`',
    'INSERT INTO `project_members`',
    'INSERT INTO `rbac_roles`',
    'INSERT INTO `rbac_role_permissions`',
    'INSERT INTO `general_pdc_activity_rules`',
    'INSERT INTO `programa_consolidado`',
    'INSERT INTO `programacion_semanal`',
    'INSERT INTO `actividades`',
    'INSERT INTO `pdc`',
    'INSERT INTO `profesionales`',
    'INSERT INTO `subcontratistas`',
    'INSERT INTO `cic`',
    'INSERT INTO `cip`',
  ]) {
    assert.equal(fixture.includes(token), true, `missing fixture contract token: ${token}`);
  }

  assert.doesNotMatch(fixture, /@aia\.com\.co|Base_de_Datos\s*=\s*['"](?!da_porto|da_aeropuerto_pc)/);
});

test('synthetic fixture forces a meaningful project-scoped reconciliation pair', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  assert.match(fixture, /CREATE TABLE `zleg_da_porto_programa`/);
  assert.match(fixture, /INSERT INTO `zleg_da_porto_programa`/);
  assert.match(fixture, /INSERT INTO `programa`[\s\S]*?\(73,\s*101,/);
  assert.match(fixture, /INSERT INTO `programa`[\s\S]*?\(75,\s*201,/);
});

test('fixture uses deterministic literals instead of runtime clocks or production dumps', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  assert.doesNotMatch(fixture, /\b(?:NOW|CURRENT_TIMESTAMP|RAND|UUID)\s*\(/i);
  assert.doesNotMatch(fixture, /mysqldump|customer|production_dump|real[_ -]?data/i);
  assert.match(fixture, /ci\.invalid/);
});

test('fixture pre-seeds migration defaults that would otherwise use runtime timestamps', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  assert.match(fixture, /CREATE TABLE IF NOT EXISTS `general_pdc_chapter_category_map`/);
  assert.match(
    fixture,
    /INSERT INTO `general_pdc_chapter_category_map`[\s\S]*?'PRELIMINARES'[\s\S]*?'2026-01-01 00:00:00'/,
  );
  assert.match(
    fixture,
    /'lps\.contratos\.auto_definir'[\s\S]*?'2026-01-01 00:00:00'/,
  );
  assert.match(
    fixture,
    /'OT', 'lps\.pdc\.auto_generar', 1, 'semi_auto_migration', '2026-01-01 00:00:00'/,
  );
});

test('fixture includes the global report-table contract used by the full-app report flow', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  for (const table of [
    'general_curvas',
    'general_curvas_pdc',
    'general_informe_consolidado',
    'general_informe_restricciones_consolidado',
    'general_informe_pdc',
    'general_informe_subcontratistas',
  ]) {
    assert.match(fixture, new RegExp(`CREATE TABLE IF NOT EXISTS ${String.fromCharCode(96)}${table}${String.fromCharCode(96)}`));
  }
});

test('fixture installs the canonical auto-program identity triggers for control rows', async () => {
  const fixture = await readFile(fixtureUrl, 'utf8');

  for (const timing of ['INSERT', 'UPDATE']) {
    assert.match(
      fixture,
      new RegExp(`CREATE TRIGGER ${String.fromCharCode(96)}trg_auto_program_log_unique_id_${timing}${String.fromCharCode(96)} BEFORE ${timing}`),
    );
  }
  assert.match(fixture, /NEW\.`consecutivo` > 0 AND EXISTS[\s\S]*?SET NEW\.`unique_id` = NEW\.`consecutivo`/);
  assert.match(fixture, /NEW\.`unique_id` IS NOT NULL AND NOT EXISTS[\s\S]*?SET NEW\.`unique_id` = NULL/);
  assert.match(fixture, /NEW\.`consecutivo` <= 0 THEN SET NEW\.`unique_id` = NULL/);
  assert.doesNotMatch(fixture, /\(73,\s*0,\s*0,/);
  assert.doesNotMatch(fixture, /FOREIGN_KEY_CHECKS\s*=\s*0[\s\S]*?INSERT INTO `auto_program_log`/);
});
