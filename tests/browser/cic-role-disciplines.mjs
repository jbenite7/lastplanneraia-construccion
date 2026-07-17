import { test, expect } from '@playwright/test';
import { ProjectDbSnapshot, runSql } from './support/dbSnapshot.mjs';
import {
  changeWeek,
  login,
  postFormJson,
  selectProject,
} from './support/session.mjs';

const PROJECT = { name: 'Prueba', projectId: 27, dbPrefix: 'prueba' };
const AUTH = { username: 'test.R', password: 'aia2026' };
const WEEK = 7;
const DISCIPLINES = ['cal', 'adm', 'gsa', 'sst'];
const COUNTS = {
  mdo: { cal: 3, adm: 5, gsa: 8, sst: 10 },
  si: { cal: 3, adm: 6, gsa: 14, sst: 10 },
};
const SCORES = { cal: 'Calidad', adm: 'ADM', gsa: 'GSA', sst: 'SST' };

const ROLE_CASES = [
  { code: 'R', allowed: ['cal'] },
  { code: 'G', allowed: ['gsa'] },
  { code: 'SG', allowed: ['gsa', 'sst'] },
  { code: 'S', allowed: ['sst'] },
  { code: 'OT', allowed: ['adm'] },
  { code: 'A', allowed: DISCIPLINES },
  { code: 'D', allowed: DISCIPLINES },
];
const DENIED_ROLES = ['DCV', 'V', 'C'];

function sqlText(value) {
  return `'${String(value).replaceAll("'", "''")}'`;
}

function userId() {
  return Number(runSql("SELECT id FROM general_usuarios WHERE usuario='test.R' LIMIT 1;").trim());
}

function setProjectRole(id, role) {
  runSql(`INSERT INTO project_members (project_id, user_id, role) `
    + `VALUES (${PROJECT.projectId}, ${id}, ${sqlText(role)}) `
    + 'ON DUPLICATE KEY UPDATE role=VALUES(role);');
}

function restoreMembership(id, originalRole) {
  if (originalRole) setProjectRole(id, originalRole);
  else runSql(`DELETE FROM project_members WHERE project_id=${PROJECT.projectId} AND user_id=${id};`);
}

function fieldsFor(prefix, disciplines, value) {
  const fields = {};
  for (const discipline of disciplines) {
    for (let index = 1; index <= COUNTS[prefix][discipline]; index += 1) {
      fields[`${prefix}_${discipline}_${index}`] = value;
    }
  }
  return fields;
}

function payload(row, prefix, fields, marker) {
  return { opcion: `modificar_${prefix}`, Id: String(row.Id), semana: String(row.Semana),
    [`${prefix}_Observaciones`]: marker, ...fields };
}

async function cicRows(page) {
  const response = await postFormJson(page, '/api/cic/list', { semana: WEEK });
  expect(response.status, JSON.stringify(response.payload)).toBe(200);
  return response.payload.data || [];
}

function protectedState(row, prefix) {
  const state = { Observaciones: row.Observaciones, Cal_Integral: row.Cal_Integral };
  for (const discipline of DISCIPLINES) {
    state[SCORES[discipline]] = row[SCORES[discipline]];
    for (let index = 1; index <= COUNTS[prefix][discipline]; index += 1) {
      state[`${prefix}_${discipline}_${index}`] = row[`${prefix}_${discipline}_${index}`];
    }
  }
  return state;
}

async function expectUiMatrix(page, roleCase) {
  for (const discipline of DISCIPLINES) {
    for (const prefix of ['mdo', 'si']) {
      const section = page.locator(`#${prefix}_${discipline}`);
      const allowed = roleCase.allowed.includes(discipline);
      await expect(section).toHaveCSS('display', allowed ? /^(?!none$).+/ : 'none');
      const disabled = await section.locator('input').evaluateAll((inputs) => (
        inputs.every((input) => input.disabled)
      ));
      expect(disabled, `${roleCase.code} ${prefix} ${discipline}`).toBe(!allowed);
    }
  }
}

async function openCic(page, roleCase) {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page, AUTH);
  await selectProject(page, PROJECT);
  await changeWeek(page, WEEK, '/programacion-semanal/cic');
  await expect(page.locator('#permiso_canonico')).toHaveValue(roleCase.code);
  await expect.poll(() => page.locator('#ps-legacy-card-view .ps-legacy-card').count())
    .toBeGreaterThan(0);
  await expect(page.locator('#ps-legacy-card-view [data-legacy-action="edit"]'))
    .toHaveCount(2);
}

function providers(rows) {
  return [
    { prefix: 'mdo', row: rows.find((row) => row.tipo_proveedor === 'Mano de Obra') },
    { prefix: 'si', row: rows.find((row) => row.tipo_proveedor !== 'Mano de Obra') },
  ];
}

function rowById(rows, row) {
  return rows.find((item) => String(item.Id) === String(row.Id));
}

async function verifyProvider(page, roleCase, provider) {
  const { prefix, row: before } = provider;
  expect(before, `${prefix} provider`).toBeTruthy();
  const marker = `QA CIC ${roleCase.code} ${prefix} ${Date.now()}`;
  const saved = await postFormJson(page, '/api/cic/save', payload(
    before,
    prefix,
    fieldsFor(prefix, roleCase.allowed, '1'),
    marker,
  ));
  expect(saved.status, JSON.stringify(saved.payload)).toBe(200);

  const afterAllowed = rowById(await cicRows(page), before);
  for (const discipline of roleCase.allowed) {
    for (let index = 1; index <= COUNTS[prefix][discipline]; index += 1) {
      expect(String(afterAllowed[`${prefix}_${discipline}_${index}`])).toBe('1');
    }
    expect(Number(afterAllowed[SCORES[discipline]])).toBe(1);
  }
  for (const discipline of DISCIPLINES.filter((item) => !roleCase.allowed.includes(item))) {
    expect(afterAllowed[SCORES[discipline]]).toBe(before[SCORES[discipline]]);
  }

  const partialDiscipline = roleCase.allowed[0];
  const partialBefore = protectedState(afterAllowed, prefix);
  const partial = await postFormJson(page, '/api/cic/save', payload(afterAllowed, prefix,
    { [`${prefix}_${partialDiscipline}_1`]: '0.5' }, 'PAYLOAD PARCIAL'));
  expect(partial.status, `${roleCase.code} ${prefix} parcial`).toBe(422);
  expect(protectedState(rowById(await cicRows(page), before), prefix)).toEqual(partialBefore);

  const deniedDisciplines = DISCIPLINES.filter((item) => !roleCase.allowed.includes(item));
  for (const denied of deniedDisciplines) {
    const deniedBefore = protectedState(rowById(await cicRows(page), before), prefix);
    const attackFields = { ...fieldsFor(prefix, roleCase.allowed, '0'),
      ...fieldsFor(prefix, [denied], '0.5') };
    const rejected = await postFormJson(page, '/api/cic/save',
      payload(afterAllowed, prefix, attackFields, 'INTENTO NO AUTORIZADO'));
    expect(rejected.status, `${roleCase.code} ${prefix} ${denied}`).toBe(403);
    const afterDenied = rowById(await cicRows(page), before);
    expect(protectedState(afterDenied, prefix)).toEqual(deniedBefore);
  }
}

test.describe.serial('CIC: autorización por disciplina', () => {
  let snapshot;
  let testUserId;
  let originalRole;

  test.beforeAll(() => {
    snapshot = new ProjectDbSnapshot(PROJECT, ['cic']).capture();
    testUserId = userId();
    originalRole = runSql(`SELECT role FROM project_members WHERE project_id=${PROJECT.projectId} `
      + `AND user_id=${testUserId} LIMIT 1;`).trim();
    setProjectRole(testUserId, 'R');
  });

  test.afterAll(() => {
    try {
      if (snapshot) {
        snapshot.restore();
        snapshot.dispose();
      }
    } finally {
      if (testUserId) restoreMembership(testUserId, originalRole);
    }
  });

  for (const roleCase of ROLE_CASES) {
    test(`rol ${roleCase.code} solo guarda sus disciplinas`, async ({ page }) => {
      setProjectRole(testUserId, roleCase.code);
      await openCic(page, roleCase);
      for (const provider of providers(await cicRows(page))) {
        await verifyProvider(page, roleCase, provider);
      }
      await expectUiMatrix(page, roleCase);
    });
  }

  for (const deniedRole of DENIED_ROLES) {
    test(`rol ${deniedRole} no puede guardar CIC`, async ({ page }) => {
      setProjectRole(testUserId, deniedRole);
      await login(page, AUTH);
      await selectProject(page, PROJECT);
      const rejected = await postFormJson(page, '/api/cic/save', {
        opcion: 'modificar_mdo', Id: '1', semana: String(WEEK),
        ...fieldsFor('mdo', ['cal'], '1'),
      });
      expect(rejected.status, `${deniedRole} debe quedar bloqueado`).toBe(403);
    });
  }
});
