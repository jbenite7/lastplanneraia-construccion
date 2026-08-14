import { test, expect } from '@playwright/test';
import { runSql } from './support/dbSnapshot.mjs';
import { assertE2EMutationConsent } from './support/restoration.mjs';
import { changeWeek, login, loginAndSelectProject, selectProject } from './support/session.mjs';

const DA_PORTO = { name: 'Da Porto', dbPrefix: 'da_porto' };
const JMC = { name: 'Optimización Aeropuerto JMC', dbPrefix: 'optimizacionJMC', projectId: 68 };

const ROLE_CASES = [
  { code: 'A', username: 'test.A', canView: true, canEdit: true },
  { code: 'D', username: 'test.D', canView: true, canEdit: true },
  { code: 'R', username: 'test.R', canView: true, canEdit: true },
  { code: 'C', username: 'test.C', canView: false, canEdit: false },
];

// Las cuatro pruebas que escriben en la base pasan por `runSql`, y `runSql` exige el stack
// aislado de CI: `dbSnapshot.mjs` enruta cada comando por `isolatedComposeArgs`, que llama a
// `assertIsolatedComposeEnvironment` y revienta si `COMPOSE_PROJECT_NAME` no empieza por
// `lps-aia-design-system-ci-`. Ese candado NO se relaja: existe para que un e2e no escriba
// sobre la base de desarrollo compartida. Fuera de ese entorno las cuatro se saltan con motivo
// en vez de fallar, que es lo único honesto que se puede hacer sin tocar el candado.
//
// Corregido 2026-08-14: hasta entonces el fixture del stack aislado tampoco alcanzaba para
// estas cuatro — del proyecto 68 solo sembraba la semana 5 sin confirmar, y el proyecto 27 no
// existía allí —, así que no había ningún entorno donde pudieran correr. Se amplió el fixture
// (`database/fixtures/design-system-ci.sql`): JMC ganó las semanas 1-4 confirmadas, con filas
// que cumplen la precondición de cada caso, y el de CNP se movió al 68. Ver
// docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design.md. `MUTACION_HABILITADA`
// se conserva: sigue siendo cierto que estas pruebas solo pueden correr en el stack aislado.
const MUTACION_HABILITADA = (() => {
  try {
    assertE2EMutationConsent(process.env);
    return true;
  } catch {
    return false;
  }
})();

const MOTIVO_SIN_ENTORNO_AISLADO = 'Escribe en la base: exige el stack aislado de CI '
  + '(COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-*, COMPOSE_FILE=docker-compose.yml:'
  + 'docker-compose.ci.yml, E2E_REQUIRE_ISOLATED_DB=1, E2E_ALLOW_DB_MUTATION=design-system-ci). '
  + 'Ese stack ya trae el dato desde 8a0d5e46, así que allí los cuatro casos corren y pasan; '
  + 'aquí se saltan solo por el candado de escritura, no por falta de fixture.';

function sqlValue(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replaceAll('\\', '\\\\').replaceAll("'", "''")}'`;
}

async function weeklyRows(page, week) {
  const response = await page.request.get(
    `/api/semanal/list?db=${encodeURIComponent(JMC.dbPrefix)}&semana=${week}&_=${Date.now()}`,
  );
  expect(response.ok()).toBe(true);
  return (await response.json()).data.filter((row) => String(row.Consecutivo || '').trim());
}

async function cnpRows(page, week) {
  const response = await page.request.post('/api/cnp/list', { form: { semana: String(week) } });
  expect(response.ok()).toBe(true);
  return (await response.json()).data;
}

function weeklyPayload(row, week, overrides = {}) {
  return { opcion: 'modificar', semana: String(week), Id: String(row.Consecutivo),
    Descripcion: row.Descripcion || '', Ubicacion: row.Ubicacion || '',
    Sub_Contratista: row.Sub_Contratista || '', Responsable_AIA: row.Responsable_AIA || '',
    Empresa: row.Empresa || '', Unidad: row.Unidad || '%',
    Compromiso: String(row.Compromiso ?? ''),
    Cantidad_Sugerida: String(row.Cantidad_Sugerida ?? ''),
    Real: String(row.Ejecutado_Real ?? ''), Rendimientos: row.Rendimientos || '',
    Categoria_CNC: row.Categoria_CNC || '', CNC: row.CNC || '',
    Observaciones_CNC: row.Observaciones_CNC || '', Es_TNP: row.Es_TNP || '',
    ...overrides };
}

async function postWeeklyUpdate(page, row, week, overrides = {}) {
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  return page.request.post(`/api/semanal/save?db=${encodeURIComponent(JMC.dbPrefix)}`, {
    form: { ...weeklyPayload(row, week, overrides), _csrf_token: csrf || '' },
  });
}

function weeklyState(row) {
  return ['Descripcion', 'Ubicacion', 'Empresa', 'Cantidad_Sugerida', 'Rendimientos',
    'Compromiso', 'Ejecutado_Real', 'P_Completado', 'PAC', 'Categoria_CNC',
    'CNC', 'Observaciones_CNC', 'Sub_Contratista', 'Responsable_AIA']
    .map((key) => [key, String(row?.[key] ?? '')]);
}

function restoreWeeklyRowSql(row) {
  runSql(`UPDATE programacion_semanal SET `
    + `Descripcion=${sqlValue(row.Descripcion)}, Ubicacion=${sqlValue(row.Ubicacion)}, `
    + `Empresa=${sqlValue(row.Empresa)}, Cantidad_Sugerida=${sqlValue(row.Cantidad_Sugerida)}, `
    + `Rendimientos=${sqlValue(row.Rendimientos)}, Compromiso=${sqlValue(row.Compromiso)}, `
    + `Sub_Contratista=${sqlValue(row.Sub_Contratista)}, `
    + `Responsable_AIA=${sqlValue(row.Responsable_AIA)}, `
    + `Ejecutado_Real=${sqlValue(row.Ejecutado_Real)}, `
    + `P_Completado=${sqlValue(row.P_Completado)}, PAC=${sqlValue(row.PAC)}, `
    + `Categoria_CNC=${sqlValue(row.Categoria_CNC)}, CNC=${sqlValue(row.CNC)}, `
    + `Observaciones_CNC=${sqlValue(row.Observaciones_CNC)} `
    + `WHERE project_id=${JMC.projectId} AND row_id=${Number(row.Consecutivo)};`);
}

function restoreCnpRowSql(row, projectId = JMC.projectId) {
  runSql(`UPDATE programacion_semanal SET Activa=${sqlValue(row.Activa)}, `
    + `Reprogramada_Por_Usuario=${sqlValue(row.Reprogramada_Por_Usuario)}, `
    + `Categoria_CNP=${sqlValue(row.Categoria_CNP)}, CNP=${sqlValue(row.CNP)}, `
    + `Observaciones_CNP=${sqlValue(row.Observaciones_CNP)} `
    + `WHERE project_id=${projectId} AND row_id=${Number(row.Consecutivo)};`);
}

// El proyecto sembrado avanza de semana con el tiempo (Max_Semana ya no es 1), así que la
// "semana corriente" y la "semana histórica" se derivan del propio dato en vez de fijarse
// como constante: si se fijaran, la prueba se pudre cada vez que el proyecto sube de semana.
// `#Max_Semana` es el campo oculto que ya expone cada vista de programación semanal
// (views/programacion-semanal/*.view.php) con el valor que el servidor calculó — el mismo que
// consume la regla de semana histórica en public/js/modules/programacion_semanal/hot.js:355-365
// (`Max_Semana - 2 >= semana`). Leerlo desde el DOM evita tocar la base de datos para algo que
// la propia página ya resuelve.
async function resolveMaxWeek(page) {
  await changeWeek(page, 1, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const maxWeek = Number(await page.locator('#Max_Semana').inputValue());
  expect(Number.isInteger(maxWeek) && maxWeek > 0, `Max_Semana inválido: ${maxWeek}`).toBe(true);
  return maxWeek;
}

// Semana histórica según la misma regla que aplica el cliente: cualquier semana <= Max_Semana - 2.
// Se toma el borde exacto porque es la semana histórica más reciente y, por diseño de LPS, una
// semana ya cerrada permanece confirmada — no hace falta buscar entre varias.
async function resolveHistoricalWeek(page) {
  const maxWeek = await resolveMaxWeek(page);
  const historicalWeek = maxWeek - 2;
  expect(historicalWeek, `Sin margen histórico: Max_Semana=${maxWeek}`).toBeGreaterThan(0);
  return historicalWeek;
}

// La semana de calificación se busca, no se fija. Fijarla en 4 hacía que estas pruebas
// dependieran del estado del proyecto sembrado: en la base de desarrollo JMC iba por otra
// semana, y en el fixture aislado de CI (`docker-compose.ci.yml`) va por la 5, así que el
// literal fallaba en los dos sitios con «Expected 4, Received 5». Se recorre desde
// `Max_Semana` hacia atrás hasta encontrar la primera semana ya cerrada, que es la que el
// producto muestra en fase de calificación.
async function resolveQualificationWeek(page) {
  const maxWeek = await resolveMaxWeek(page);
  for (let week = maxWeek; week >= 1; week -= 1) {
    await changeWeek(page, week, '/programacion-semanal');
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    const phase = await page.locator('.ps-weekly-phase-title').textContent();
    if (String(phase || '').includes('Calificación')) return week;
  }
  throw new Error(`Ninguna semana de ${JMC.name} está en fase de calificación (Max_Semana=${maxWeek})`);
}

// La fila tampoco se fija por nombre. Atarla a 'Movilización general' o 'Descapote' era la
// misma pudrición que el comentario de `resolveMaxWeek` dice haber evitado para la semana, un
// nivel más abajo: medido el 2026-08-13, esos dos nombres solo existen en las semanas 1-4, 10 y
// 11 del proyecto sembrado, así que en cuanto la semana resuelta fue otra (Max_Semana=11 →
// histórica 9, calificación 10) el `find` devolvió `undefined`. Se elige por la PRECONDICIÓN
// que cada caso necesita, y si ninguna fila la cumple la prueba falla diciendo cuál falta —
// nunca se salta por dato ausente, que sería tapar una regresión.
function tieneResponsables(row) {
  return String(row.Sub_Contratista || '').trim() !== ''
    && String(row.Responsable_AIA || '').trim() !== '';
}

// «Compromiso confirmado»: fila con responsables y con un compromiso mayor que cero. Es la
// precondición de los tres casos de API, porque sin compromiso el servidor rechazaría por otro
// motivo y el 422/409 que se mide dejaría de probar lo que dice probar.
function esCompromisoConfirmado(row) {
  return tieneResponsables(row) && Number(row.Compromiso) > 0;
}

async function pickWeeklyRow(page, week, predicate, precondicion) {
  const rows = await weeklyRows(page, week);
  const row = rows.find(predicate);
  expect(
    row,
    `Ninguna fila de la semana ${week} de ${JMC.name} cumple la precondición: ${precondicion}`,
  ).toBeTruthy();
  return row;
}

// Semana confirmada del proyecto abierto, derivada igual que la de calificación: se recorre
// desde `Max_Semana` hacia atrás leyendo el `#Semanal_Confirmada` que ya emite la vista. Antes
// estaba fija en 4 para el proyecto «Prueba», que hoy solo tiene las semanas 1 y 2 — la prueba
// ni llegaba a la aserción: se caía en `changeWeek` esperando `#semana_PHP` con valor 4.
async function resolveConfirmedWeek(page) {
  const maxWeek = await resolveMaxWeek(page);
  for (let week = maxWeek; week >= 1; week -= 1) {
    await changeWeek(page, week, '/programacion-semanal');
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    if (await page.locator('#Semanal_Confirmada').inputValue() === '1') return week;
  }
  throw new Error(`Ninguna semana del proyecto abierto está confirmada (Max_Semana=${maxWeek})`);
}

// La semana sin actividades se busca, no se fija: fijar la 1 se pudrio en cuanto Da Porto
// gano una segunda semana vacia sembrada aparte (2026-08-14) para sostener este mismo caso, y
// se pudriria de nuevo si alguna semana ganara filas mas adelante. Se recorre desde la 1 porque
// "la primera semana vacia" es la propiedad que el caso necesita, no un numero concreto.
async function resolveEmptyWeek(page, project) {
  const maxWeek = await resolveMaxWeek(page);
  for (let week = 1; week <= maxWeek; week += 1) {
    const response = await page.request.get(
      `/api/semanal/list?db=${encodeURIComponent(project.dbPrefix)}&semana=${week}`,
    );
    expect(response.ok()).toBe(true);
    if ((await response.json()).data.length === 0) return week;
  }
  throw new Error(`Ninguna semana de ${project.name} está vacía (Max_Semana=${maxWeek})`);
}

async function openJmcQualification(page, week) {
  await page.setViewportSize({ width: 390, height: 844 });
  await loginAndSelectProject(page, JMC);
  const targetWeek = week ?? await resolveQualificationWeek(page);
  await changeWeek(page, targetWeek, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  return targetWeek;
}

async function openProgrammingWeek(
  page,
  roleCase,
  viewport = { width: 1180, height: 820 },
  week,
) {
  await page.setViewportSize(viewport);
  await login(page, { username: roleCase.username, password: 'aia2026' });
  await selectProject(page, DA_PORTO);
  const targetWeek = week ?? await resolveMaxWeek(page);
  await changeWeek(page, targetWeek, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  await expect(page.locator('#permiso_canonico[aria-hidden="true"]')).toHaveValue(roleCase.code);
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Programación de Compromisos',
  );
  return targetWeek;
}

// Bootstrap 4.3.1 ignora el cierre mientras la apertura sigue en transición: `Modal.prototype.hide`
// sale sin hacer nada si `_isTransitioning` es verdadero. Y `toBeVisible()` pasa en cuanto el
// modal se pinta, antes de que termine el fundido, así que el clic en Cancelar/Cerrar se perdía y
// el modal quedaba abierto. Eso —no un cambio del producto— es el intermitente medido el
// 2026-08-13: el mismo caso de calificación salió verde y rojo en corridas seguidas sin tocar
// nada. Se espera a que Bootstrap declare terminada la transición y solo entonces se cierra.
async function dismissModal(page, modal, dismiss) {
  const modalId = await modal.getAttribute('id');
  await page.waitForFunction(
    (id) => {
      const data = window.jQuery(`#${id}`).data('bs.modal');
      return Boolean(data) && data._isTransitioning !== true;
    },
    modalId,
    { timeout: 15000 },
  );
  await dismiss();
  await expect(modal).toBeHidden();
}

async function expectSectionDropdown(page) {
  // Scoped por aria-label: desde que las acciones que no caben viven en el
  // menu "Mas" (otro .ps-dropdown-nav en la misma toolbar), el selector
  // desnudo matchea dos elementos y rompe en modo estricto.
  const navigation = page.locator('.ps-dropdown-nav[aria-label="Navegacion Programacion Semanal"]');
  await navigation.locator('.btn-dropdown-trigger').click();
  await expect(navigation).toHaveClass(/is-open/);
  const items = navigation.locator('.ps-dropdown-item');
  await expect(items).toHaveCount(4);
  await expect(items).toHaveText([
    /Actividades/,
    /Causas No Programacion/,
    /Causas No Cumplimiento/,
    /Calificacion Proveedores/,
  ]);
  await page.locator('body').click({ position: { x: 4, y: 4 } });
  await expect(navigation).not.toHaveClass(/is-open/);
}

async function probeWeeklyPermissions(page, roleCase, week) {
  const list = await page.request.get(
    `/api/semanal/list?db=${DA_PORTO.dbPrefix}&semana=${week}`,
  );
  expect(list.status()).toBe(roleCase.canView ? 200 : 403);

  const edit = await page.request.post(`/api/semanal/save?db=${DA_PORTO.dbPrefix}`, {
    form: {
      opcion: 'listar_excepciones_autoprogramacion',
      semana: String(week),
    },
  });
  expect(edit.status()).toBe(roleCase.canEdit ? 200 : 403);
}

async function switchToClientQualificationPhase(page) {
  const response = page.waitForResponse((item) => (
    item.url().includes('/api/semanal/list') && item.request().method() === 'GET'
  ));
  await page.evaluate(() => {
    document.querySelector('#Semanal_Confirmada').value = '1';
    window.PSHotModule.reload();
  });
  expect((await response).ok()).toBe(true);
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Calificación de Compromisos',
  );
  await expect(page.locator('#weeklyPhaseMobileLabel')).toHaveText('Calificación');
}

test.describe('Programación Semanal: permisos por rol', () => {
  for (const roleCase of ROLE_CASES) {
    test(`rol ${roleCase.code} respeta lectura y edición`, async ({ page }) => {
      const week = await openProgrammingWeek(page, roleCase);
      await probeWeeklyPermissions(page, roleCase, week);

      const manageButtons = page.locator([
        '#btn_autoprogramar',
        '#btn_agregar_actividad',
        '#btn_cerrar_compromisos_semana',
      ].join(','));
      await expect(manageButtons).toHaveCount(3);

      if (roleCase.canEdit) {
        for (const button of await manageButtons.all()) {
          await expect(button).toBeVisible();
          await expect(button).toBeEnabled();
        }
      } else {
        // Quien no gestiona no ve estos botones: se esconden, no se muestran en gris.
        // Es deliberado desde 54834f2d («el Visualizador deja de ver botones que nunca pudo
        // usar»): `syncPhaseUI` los oculta cuando `canManageToolbarActions()` es falso
        // (public/js/modules/programacion_semanal/hot.js:3149-3157) y ademas los deshabilita
        // (:3166-3168), de modo que la doble barrera sobrevive a que alguien fuerce el
        // display desde el inspector. Esta prueba esperaba «visible pero deshabilitado», que
        // era el comportamiento anterior a ese commit; confirmado con Felipe el 2026-08-08.
        for (const button of await manageButtons.all()) {
          await expect(button).toBeHidden();
          await expect(button).toBeDisabled();
        }
      }
    });
  }
});

test('avance móvil y API rechazan una actividad sin responsables', async ({ page }) => {
  test.skip(!MUTACION_HABILITADA, MOTIVO_SIN_ENTORNO_AISLADO);
  const week = await openJmcQualification(page);
  // Precondición: la fila tiene responsables (los que la prueba va a quitar) y compromiso, para
  // que la tarjeta móvil ofrezca el campo de avance real.
  const original = await pickWeeklyRow(
    page, week, esCompromisoConfirmado, 'responsables y compromiso > 0',
  );
  try {
    runSql(`UPDATE programacion_semanal SET Sub_Contratista=NULL, Responsable_AIA=NULL WHERE project_id=${JMC.projectId} AND row_id=${Number(original.Consecutivo)};`);
    await page.reload();
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    // La tarjeta se localiza por el identificador que imprime `renderMobileCard`
    // (`.ps-mobile-id` = Id o Consecutivo), no por el nombre de la actividad: dos filas pueden
    // compartir nombre y el filtro por texto devolvería dos tarjetas.
    const card = page.locator('article.ps-mobile-card').filter({
      has: page.locator('.ps-mobile-id', {
        hasText: new RegExp(`^${String(original.Id || original.Consecutivo)}$`),
      }),
    });
    let requests = 0;
    page.on('request', (request) => { if (request.url().includes('/api/semanal/save')) requests += 1; });
    await card.locator('input[data-mobile-prop="Ejecutado_Real"]').fill('61');
    await card.locator('[data-mobile-save-prop="Ejecutado_Real"]').click();
    await expect(card.locator('[data-mobile-save-status]')).toContainText('Falta Sub-Contratista');
    expect(requests).toBe(0);
    const direct = await postWeeklyUpdate(page, { ...original, Sub_Contratista: '', Responsable_AIA: '' }, week, { Real: '61' });
    expect(direct.status()).toBe(422);
  } finally {
    restoreWeeklyRowSql(original);
    const restored = (await weeklyRows(page, week))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(restored)).toEqual(weeklyState(original));
  }
});

test('API semanal rechaza fase, CNC incompleta y semana suplantada', async ({ page }) => {
  test.skip(!MUTACION_HABILITADA, MOTIVO_SIN_ENTORNO_AISLADO);
  const week = await openJmcQualification(page);
  const original = await pickWeeklyRow(
    page, week, esCompromisoConfirmado, 'responsables y compromiso > 0',
  );
  try {
    const cnc = await postWeeklyUpdate(page, original, week, { Real: '39', Categoria_CNC: '', CNC: '', Observaciones_CNC: '' });
    expect(cnc.status()).toBe(422);
    const tnpSpoof = await postWeeklyUpdate(page, original, week, {
      Real: '39', Es_TNP: '1', Categoria_CNC: '', CNC: '', Observaciones_CNC: '',
    });
    expect(tnpSpoof.status()).toBe(422);
    const phase = await postWeeklyUpdate(page, original, week, { Compromiso: '41' });
    expect(phase.status()).toBe(409);
    // Semana suplantada: cualquiera distinta de la abierta, para que el servidor la rechace.
    const spoof = await postWeeklyUpdate(page, original, week === 1 ? week + 1 : week - 1);
    expect(spoof.status()).toBe(422);
    const after = (await weeklyRows(page, week))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(after)).toEqual(weeklyState(original));
  } finally {
    restoreWeeklyRowSql(original);
  }
});

test('API semanal rechaza un proyecto distinto al seleccionado', async ({ page }) => {
  await openJmcQualification(page);
  const list = await page.request.get('/api/semanal/list?db=da_porto&semana=1');
  expect(list.status()).toBe(403);
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const save = await page.request.post('/api/semanal/save?db=da_porto', { form: {
    opcion: 'modificar', semana: '1', Id: '0', _csrf_token: csrf || '',
  } });
  expect(save.status()).toBe(403);
  const checks = [
    page.request.get('/api/semanal/tnp-actividades?db=da_porto&semana=1'),
    page.request.get('/api/semanal/auto-program-log?db=da_porto&semana=1'),
    page.request.post('/api/semanal/auto-program', { form: { db: 'da_porto', semana: '0' } }),
    page.request.post('/api/semanal/reabrir?db=da_porto', {
      form: { semana: '0', motivo: '', _csrf_token: csrf || '' },
    }),
  ];
  for (const response of await Promise.all(checks)) expect(response.status()).toBe(403);

  // reabrir exige CSRF como el resto de mutaciones privilegiadas
  const sinCsrf = await page.request.post(`/api/semanal/reabrir?db=${JMC.dbPrefix}`, {
    form: { semana: '4', motivo: 'Motivo de reapertura suficientemente largo' },
  });
  expect(sinCsrf.status()).toBe(403);
  expect((await sinCsrf.json()).mensaje).toContain('CSRF');
});

test('rol R histórico solo puede calificar el compromiso confirmado', async ({ page }) => {
  test.skip(!MUTACION_HABILITADA, MOTIVO_SIN_ENTORNO_AISLADO);
  await page.setViewportSize({ width: 390, height: 844 });
  // Bug preexistente destapado el 2026-08-14 al sembrar la membresía de test.R en JMC (Task 2
  // del plan): `loginAndSelectProject` sin tercer argumento entra con `CREDENTIALS` por defecto
  // (test.A), así que este caso —pese a su nombre— corría como Admin y nunca ejercitaba la
  // regla que dice probar. Quedaba enmascarado porque test.R nunca había sido miembro de JMC:
  // sin membresía, pasar sus credenciales habría hecho fallar `selectProject` con un error
  // distinto, así que nadie lo notó. Bug del propio test, no del producto.
  await loginAndSelectProject(page, JMC, { username: 'test.R', password: 'aia2026' });
  // Esta prueba SÍ quiere una semana histórica (a diferencia de las que comparten
  // openJmcQualification con semana=4 fija): se deriva para no asumir que la semana 4 seguirá
  // siendo la histórica confirmada cuando Max_Semana avance.
  const historicalWeek = await resolveHistoricalWeek(page);
  await changeWeek(page, historicalWeek, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  await expect(page.locator('.ps-weekly-phase-title')).toHaveText(
    'Fase: Calificación de Compromisos',
  );

  const original = await pickWeeklyRow(
    page, historicalWeek, esCompromisoConfirmado, 'responsables y compromiso > 0',
  );
  try {
    const qualification = await postWeeklyUpdate(page, original, historicalWeek);
    expect(qualification.status()).toBe(200);
    const planning = await postWeeklyUpdate(page, original, historicalWeek, {
      Descripcion: `${original.Descripcion || ''} QA histórico`,
    });
    expect(planning.status()).toBe(409);
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const blocked = [
      page.request.post(`/api/semanal/reabrir?db=${JMC.dbPrefix}`, {
        form: { semana: String(historicalWeek), motivo: '', _csrf_token: csrf || '' },
      }),
      page.request.post('/api/cnp/save', { form: {
        Id: '1', semana: String(historicalWeek), Categoria_CNP: 'Programación', CNP: 'QA', _csrf_token: csrf || '',
      } }),
      page.request.post('/api/cnp/reprogramar', { form: { Id: '1', semana: String(historicalWeek), _csrf_token: csrf || '' } }),
      page.request.post('/api/cnc/save', { form: {
        Id: '1', semana: String(historicalWeek), Categoria_CNC: 'Administrativas',
        CNC: 'Otra', Observaciones_CNC: 'QA política', _csrf_token: csrf || '',
      } }),
    ];
    for (const response of await Promise.all(blocked)) expect(response.status()).toBe(403);
    const after = (await weeklyRows(page, historicalWeek))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(weeklyState(after)).toEqual(weeklyState(original));
  } finally {
    restoreWeeklyRowSql(original);
  }
});

// Se mueve al proyecto abierto (JMC) el 2026-08-14: el caso original usaba el proyecto 27
// («Prueba»), que no existe en el fixture aislado de CI. Sembrar «Prueba» entero para una sola
// fila de CNP era más caro que reaprovechar las semanas confirmadas que este mismo archivo ya
// siembra para JMC. Decisión de la sesión, ver docs/superpowers/specs/
// 2026-08-14-fixture-ci-semanal-roles-design.md.
test('API CNP no reprograma una semana confirmada', async ({ page }) => {
  test.skip(!MUTACION_HABILITADA, MOTIVO_SIN_ENTORNO_AISLADO);
  await loginAndSelectProject(page, JMC);
  const week = await resolveConfirmedWeek(page);
  await changeWeek(page, week, '/programacion-semanal/cnp');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const original = (await cnpRows(page, week))[0];
  expect(original, `La semana confirmada ${week} de ${JMC.name} no tiene filas CNP`).toBeTruthy();
  try {
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const response = await page.request.post('/api/cnp/reprogramar', { form: {
      Id: String(original.Consecutivo), semana: String(week), _csrf_token: csrf || '',
    } });
    expect(response.status()).toBe(409);
    expect((await response.json()).respuesta).toBe('ERROR');
    const after = (await cnpRows(page, week))
      .find((row) => String(row.Consecutivo) === String(original.Consecutivo));
    expect(after).toBeTruthy();
  } finally {
    restoreCnpRowSql(original);
  }
});

test('semana sin actividades no fabrica filas ni tarjetas', async ({ page }) => {
  await page.setViewportSize({ width: 551, height: 750 });
  await login(page, { username: ROLE_CASES[0].username, password: 'aia2026' });
  await selectProject(page, DA_PORTO);
  const emptyWeek = await resolveEmptyWeek(page, DA_PORTO);
  await changeWeek(page, emptyWeek, '/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const response = await page.request.get(
    `/api/semanal/list?db=${DA_PORTO.dbPrefix}&semana=${emptyWeek}`,
  );
  expect(response.ok()).toBe(true);
  expect((await response.json()).data).toEqual([]);
  await expect(page.locator('article.ps-mobile-card')).toHaveCount(0);
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toBeVisible();

  await page.setViewportSize({ width: 787, height: 750 });
  await page.reload();
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const grid = await page.evaluate(() => ({
    rows: window.PSHotModule.getHotInstance().countRows(),
    text: document.querySelector('#hot-container').textContent,
  }));
  expect(grid.rows).toBe(0);
  expect(grid.text).not.toContain('Programada Manualmente');
});

test('toolbar tablet muestra texto comprensible sin overflow', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0], { width: 787, height: 750 });
  // `.btn-pdc-modern` ya no existe en esta toolbar: la migración al design system la cambió por
  // `.aia-btn` el 2026-08-01 (ed9ca6db, «impeccable polish on programacion semanal»), y desde
  // entonces el selector no encontraba nada y `labels.length` era 0. Era pudrición del test, no
  // una regresión del producto: los botones siguen ahí, visibles y con texto.
  const state = await page.evaluate(() => {
    const visible = [...document.querySelectorAll('.ps-hot-toolbar-actions .aia-btn')]
      .filter((button) => getComputedStyle(button).display !== 'none');
    return {
      labels: visible.map((button) => button.innerText.trim()),
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });
  expect(state.labels.length).toBeGreaterThan(0);
  expect(state.labels.every((label) => label.length > 0)).toBe(true);
  expect(state.overflow).toBeLessThanOrEqual(1);
});

test('tabla semanal tablet usa superficies dark cuando el tema es dark', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0], { width: 787, height: 750 });
  await expect.poll(() => page.evaluate(() => [
    '#hot-container', '.ps-hot-toolbar-shell',
    '.ps-hot-toolbar-actions .aia-btn',
    '.ps-toolbar-right .btn-filter-toggle',
    '#hot-container .handsontable thead th',
  ].every((selector) => {
    const color = getComputedStyle(document.querySelector(selector)).backgroundColor;
    const channels = color.match(/[\d.]+/g).slice(0, 3).map(Number);
    return channels.reduce((sum, value) => sum + value, 0) / 3 < 140;
  })), { timeout: 2000 }).toBe(true);
});

test('filtro sin resultados conserva dropdown y modales operables', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0]);
  await expectSectionDropdown(page);

  // La leyenda se movió al menú de desbordamiento «Mas» el 2026-08-03 (f61f9661), igual que
  // Imprimir y Exportar: hay que abrirlo antes de tocarla. El caso de calificación (más abajo)
  // ya lo hacía; este se quedó clicando un botón invisible y agotaba el timeout.
  const moreMenu = page.locator('.ps-hot-overflow-nav');
  await moreMenu.locator('.btn-dropdown-trigger').click();
  await expect(moreMenu).toHaveClass(/is-open/);
  await page.locator('.leyenda_colores').click();
  const legendModal = page.locator('#modal_leyenda_colores_ps');
  await expect(legendModal).toBeVisible();
  await expect(legendModal.locator('.modal-title')).toContainText(
    'Programación de Compromisos',
  );
  await expect(legendModal.locator('.modal-body')).toContainText(
    'Defina compromisos viables',
  );
  await dismissModal(page, legendModal, () => (
    legendModal.locator('button[aria-label="Cerrar"]').click()
  ));

  let saveRequests = 0;
  page.on('request', (request) => {
    if (request.url().includes('/api/semanal/save')) saveRequests += 1;
  });
  await page.locator('#btn_cerrar_compromisos_semana').click();
  const closeModal = page.locator('#modal_cerrar_compromisos');
  await expect(closeModal).toBeVisible();
  await expect(closeModal.locator('.modal-title')).toContainText('Cierre de Compromisos');
  await dismissModal(page, closeModal, () => (
    closeModal.locator('#btn_cancelar_compromisos_semana').click()
  ));
  expect(saveRequests).toBe(0);

  const legendItems = page.locator('#psAlertsLegend .pdc-legend-item');
  await expect(legendItems).not.toHaveCount(0);
  const counts = await legendItems.locator('.count-badge').allTextContents();
  expect(counts.every((count) => count.trim() === '(0)')).toBe(true);
  if (!await legendItems.first().isVisible()) {
    await page.locator('.btn-filter-toggle').click();
    await expect(legendItems.first()).toBeVisible();
  }
  await legendItems.first().click();
  await expect(page.locator('#mobileAlertCount')).toHaveText('1');
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toHaveText(
    'No hay actividades con los filtros actuales.',
  );
  const filteredRows = await page.evaluate(() => (
    window.PSHotModule.getHotInstance().countRows()
  ));
  expect(filteredRows).toBe(0);

  await legendItems.first().click();
  await expect(page.locator('#mobileAlertCount')).toHaveText('0');

  await expect(page.locator('article.ps-mobile-card')).toHaveCount(0);
  await expect(page.locator('#mobile-card-view .ps-mobile-empty')).toBeHidden();
});

test('calificación expone controles y modales sin escribir datos', async ({ page }) => {
  await openProgrammingWeek(page, ROLE_CASES[0]);
  await switchToClientQualificationPhase(page);
  let saveRequests = 0;
  page.on('request', (request) => {
    if (request.url().includes('/api/semanal/save')) saveRequests += 1;
  });

  for (const selector of [
    '#btn_autoprogramar',
    '#btn_agregar_actividad',
    '#btn_cerrar_compromisos_semana',
  ]) {
    await expect(page.locator(selector)).toBeHidden();
  }
  // Imprimir vive en el menu de desbordamiento "Mas" (task 25, f61f966):
  // hay que abrirlo antes de comprobar visibilidad del control movido.
  const moreMenu = page.locator('.ps-hot-overflow-nav');
  await moreMenu.locator('.btn-dropdown-trigger').click();
  await expect(moreMenu).toHaveClass(/is-open/);
  await expect(page.locator('#btn_informe_compromisos')).toBeVisible();
  await page.locator('body').click({ position: { x: 4, y: 4 } });
  await expect(moreMenu).not.toHaveClass(/is-open/);

  await expect(page.locator('#btn_tnp')).toBeVisible();
  await expect(page.locator('#btn_reabrir_semana')).toBeVisible();

  await expectSectionDropdown(page);

  // Leyenda tambien se movio al menu "Mas": lo reabrimos antes de tocarla.
  await moreMenu.locator('.btn-dropdown-trigger').click();
  await expect(moreMenu).toHaveClass(/is-open/);
  await page.locator('.leyenda_colores').click();
  const legendModal = page.locator('#modal_leyenda_colores_ps');
  await expect(legendModal).toBeVisible();
  await expect(legendModal.locator('.modal-title')).toContainText(
    'Calificación de Actividades',
  );
  await expect(legendModal.locator('.modal-body')).toContainText(
    'Cierre incumplidas con CNC',
  );
  await dismissModal(page, legendModal, () => (
    legendModal.locator('button[aria-label="Cerrar"]').click()
  ));

  await page.locator('#btn_reabrir_semana').click();
  const reopenModal = page.locator('#modal_reabrir_semana');
  await expect(reopenModal).toBeVisible();
  await expect(reopenModal.locator('#btn_confirmar_reabrir')).toBeDisabled();
  await dismissModal(page, reopenModal, () => (
    reopenModal.getByRole('button', { name: 'Cancelar' }).click()
  ));

  await page.locator('#btn_tnp').click();
  const tnpModal = page.locator('#modal_tnp');
  await expect(tnpModal).toBeVisible();
  await expect(tnpModal.locator('#tnp_actividad_select')).toBeAttached();
  expect(await tnpModal.locator('#tnp_categoria_cp option').count()).toBeGreaterThan(1);
  await dismissModal(page, tnpModal, () => (
    tnpModal.getByRole('button', { name: 'Cerrar' }).click()
  ));
  expect(saveRequests).toBe(0);
});
