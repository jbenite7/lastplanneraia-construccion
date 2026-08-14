/**
 * Red de caracterización de las reglas de habilitación de Programación
 * Intermedia (I1–I7). Plan F2a-2b-1.
 *
 * CARACTERIZA, NO CORRIGE: cada aserción fija lo que el código hace hoy.
 *
 * Intermedia es más frágil que Semanal para esto: escribe cellMeta a mano
 * (syncRestrictionLockForVisualRow) y limpia clases del DOM, y `_canEditGlobal`
 * se fija en buildRowClassCache(). Por eso el arnés re-decide disparando el
 * hook `afterFilter`, que es la vía que el propio módulo usa para invalidar
 * sus caches de fila.
 */
import { test, expect } from '@playwright/test';
import { login, selectProject, changeWeek } from './support/session.mjs';
import {
  waitForGridReady,
  setEnablementContext,
  readCellDecisions,
  readSourceRow,
  countGridRows,
  expectDecisionMatchesEditor,
} from './support/enablement-probe.mjs';

const PROJECT_CANDIDATES = [
  'Preconstrucción Da Porto',
  'Optimización Aeropuerto JMC',
  'Da Porto',
  'Prueba',
];

async function selectAvailableProject(page) {
  for (const name of PROJECT_CANDIDATES) {
    const card = page.locator('.project-item').filter({
      has: page.getByRole('heading', { name, exact: true }),
    });
    if (await card.count()) {
      await selectProject(page, { name });
      return name;
    }
  }
  throw new Error(`Ninguno de los proyectos candidatos existe: ${PROJECT_CANDIDATES.join(', ')}`);
}

async function openIntermedia(page) {
  await login(page);
  await selectAvailableProject(page);
  await page.goto('/programacion-intermedia');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const maxSemana = Number(await page.locator('#Max_Semana').inputValue());
  expect(Number.isInteger(maxSemana) && maxSemana > 0, `Max_Semana inválido: ${maxSemana}`).toBe(true);

  for (let semana = maxSemana; semana >= 1; semana -= 1) {
    await changeWeek(page, semana, '/programacion-intermedia');
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    if (await countGridRows(page, 'PI') > 0) {
      await waitForGridReady(page, 'PI');
      return { maxSemana, semana };
    }
  }
  throw new Error(`Ninguna semana 1..${maxSemana} tiene filas en Programación Intermedia`);
}

/**
 * Localiza filas con las características que cada regla necesita, leyendo el
 * dato real en vez de sembrarlo: una cabecera (I3), una fila normal con
 * Responsable AIA y otra sin él (I4).
 */
async function findRows(page) {
  // La condición de cabecera se pregunta al propio módulo (PIStateMachine.getState,
  // la misma que consume buildPIRowMeta) en vez de reinventarla aquí: una
  // heurística propia sobre los datos daba cero cabeceras y habría dejado I3
  // saltada sin que nadie supiera si es que no las hay o es que no las vi.
  return page.evaluate(() => {
    const hot = window.PIHotModule.getHotInstance();
    const total = hot.countRows();
    const found = { header: null, conResponsable: null, sinResponsable: null, total, headerCount: 0 };
    for (let row = 0; row < total; row += 1) {
      const physical = typeof hot.toPhysicalRow === 'function' ? hot.toPhysicalRow(row) : row;
      const data = hot.getSourceDataAtRow(physical);
      if (!data) continue;
      const estado = window.PIStateMachine && typeof window.PIStateMachine.getState === 'function'
        ? window.PIStateMachine.getState(data) : null;
      const esHeader = estado === 'header';
      if (esHeader) found.headerCount += 1;
      const tieneResponsable = String(data.Responsable_AIA || '').trim() !== '';
      if (esHeader && found.header === null) found.header = row;
      if (!esHeader && tieneResponsable && found.conResponsable === null) found.conResponsable = row;
      if (!esHeader && !tieneResponsable && found.sinResponsable === null) found.sinResponsable = row;
    }
    return found;
  });
}

/**
 * Las props de restricción son dinámicas: salen de /api/general/restriction-config
 * (I1), así que se leen del propio módulo en vez de fijarse como constante.
 */
async function readRestrictionProps(page) {
  return page.evaluate(() => {
    const hot = window.PIHotModule.getHotInstance();
    const columnas = (hot.getSettings().columns || []).map((c) => c.data);
    const config = window.__RESTRICTION_CONFIG__;
    const claves = config && Array.isArray(config.restrictions)
      ? config.restrictions.map((r) => r.key) : [];
    return claves.filter((clave) => columnas.includes(clave));
  });
}

test.describe('I2: la matriz de roles, fases y semanas', () => {
  test('las columnas editables siguen isUserAllowedToEdit en las 24 combinaciones', async ({ page }) => {
    const { semana } = await openIntermedia(page);
    const { conResponsable } = await findRows(page);
    expect(conResponsable, 'No hay ninguna fila normal con Responsable AIA').not.toBeNull();

    const ROLES = ['A', 'D', 'R', 'DCV', 'C', 'V'];
    const DIRECTOR = ['A', 'D'];
    const EDITOR = ['A', 'D', 'R', 'DCV'];

    // I2 se distingue de S2 en algo que conviene no perder de vista: aquí
    // `Semanal_Confirmada === 1` bloquea TODO, sin excepción de rol.
    const esperado = (permiso, esHistorica, semanalConfirmada) => {
      if (semanalConfirmada === 1) return false;
      return esHistorica ? DIRECTOR.includes(permiso) : EDITOR.includes(permiso);
    };

    const desviaciones = [];
    for (const permiso of ROLES) {
      for (const semanalConfirmada of [0, 1]) {
        for (const esHistorica of [false, true]) {
          await setEnablementContext(page, 'PI', {
            permiso,
            semana,
            maxSemana: esHistorica ? semana + 2 : semana,
            semanalConfirmada,
          });
          const decisiones = await readCellDecisions(page, 'PI', {
            row: conResponsable, columns: ['Observaciones'],
          });
          const puedeEditar = !decisiones.Observaciones.readOnly;
          const puedeEditarEsperado = esperado(permiso, esHistorica, semanalConfirmada);
          if (puedeEditar !== puedeEditarEsperado) {
            desviaciones.push(
              `Observaciones · rol ${permiso} · confirmada=${semanalConfirmada} · `
              + `${esHistorica ? 'histórica' : 'corriente'}: esperado editable=${puedeEditarEsperado}, `
              + `real=${puedeEditar}`,
            );
          }
        }
      }
    }
    expect(desviaciones, `Desviaciones:\n${desviaciones.join('\n')}`).toEqual([]);
  });
});

test.describe('I3 e I4: las dos particularidades de Intermedia', () => {
  /**
   * I3 dice que una fila cabecera (`state === 'header'`, es decir `Titulo != 0`
   * según stateMachine.js:161-167) no es editable en ninguna columna.
   *
   * NO SE PUEDE EJERCITAR CON UNA CABECERA REAL, y la causa no son los datos
   * sembrados: el listado del servidor filtra `Titulo = 0`
   * (src/Controllers/Programacion/ProgramacionIntermediaController.php:182),
   * así que la grilla NUNCA recibe una fila cabecera. La rama `meta.isHeader`
   * de buildPICellProperties() es, hoy, código inalcanzable desde esta vista.
   *
   * En vez de dejar la regla como saltada —que ocultaría por qué—, se fija el
   * hecho que la hace inalcanzable: si algún día el listado deja de filtrar,
   * esta prueba se pone roja y obliga a cubrir I3 de verdad.
   */
  test('I3: la grilla no recibe filas cabecera porque el listado filtra Titulo = 0', async ({ page }) => {
    await openIntermedia(page);
    const { headerCount, total } = await findRows(page);
    expect(total, 'Sin filas no se puede afirmar nada').toBeGreaterThan(0);
    expect(
      headerCount,
      'Llegaron filas cabecera a la grilla: I3 pasó a ser alcanzable y hay que cubrirla con una cabecera real',
    ).toBe(0);
  });

  test('I4: el candado por Responsable AIA cambia la decisión de las restricciones', async ({ page }) => {
    const { semana } = await openIntermedia(page);
    const { conResponsable, sinResponsable } = await findRows(page);
    test.skip(
      conResponsable === null || sinResponsable === null,
      'El proyecto sembrado no tiene a la vez una fila con y otra sin Responsable AIA',
    );

    await setEnablementContext(page, 'PI', {
      permiso: 'A', semana, maxSemana: semana, semanalConfirmada: 0,
    });
    const restricciones = await readRestrictionProps(page);
    expect(restricciones.length, 'Sin columnas de restricción no hay candado que medir').toBeGreaterThan(0);

    const conResp = await readCellDecisions(page, 'PI', {
      row: conResponsable, columns: restricciones,
    });
    const sinResp = await readCellDecisions(page, 'PI', {
      row: sinResponsable, columns: restricciones,
    });

    for (const prop of restricciones) {
      expect(conResp[prop].readOnly, `${prop} en fila CON responsable`).toBe(false);
      expect(sinResp[prop].readOnly, `${prop} en fila SIN responsable`).toBe(true);
      expect(sinResp[prop].classes, `${prop} sin responsable debe señalar el candado`)
        .toContain('pi-cell-locked-resp');
    }
  });
});

test.describe('I5: la excepción que ignora rol y fase', () => {
  /**
   * `__shared_selected` es editable en toda fila no cabecera SIN mirar rol ni
   * fase (hot.js:956-961). Se comprueba justo donde todo lo demás está
   * bloqueado —rol V y fase confirmada—, porque es la regla más fácil de
   * romper sin querer al extraer: contradice a todas las otras.
   */
  test('__shared_selected sigue editable con rol V y fase confirmada', async ({ page }) => {
    const { semana } = await openIntermedia(page);
    const { conResponsable } = await findRows(page);

    await setEnablementContext(page, 'PI', {
      permiso: 'V', semana, maxSemana: semana, semanalConfirmada: 1,
    });

    const enFilaNormal = await readCellDecisions(page, 'PI', {
      row: conResponsable, columns: ['__shared_selected', 'Observaciones'],
    });
    expect(enFilaNormal.Observaciones.readOnly, 'Observaciones con V y confirmada').toBe(true);
    expect(enFilaNormal.__shared_selected.readOnly, '__shared_selected con V y confirmada').toBe(false);

    // La mitad "en cabecera es readOnly" no se comprueba aquí: el listado
    // filtra Titulo = 0 y la grilla nunca recibe cabeceras (ver la prueba de I3).
  });
});

test.describe('I1: las props editables salen de la configuración del proyecto', () => {
  test('las columnas de restricción declaradas por la API son las editables', async ({ page }) => {
    const { semana } = await openIntermedia(page);
    const { conResponsable } = await findRows(page);
    await setEnablementContext(page, 'PI', {
      permiso: 'A', semana, maxSemana: semana, semanalConfirmada: 0,
    });

    const restricciones = await readRestrictionProps(page);
    expect(restricciones.length, 'La config no declaró ninguna restricción con columna').toBeGreaterThan(0);

    const decisiones = await readCellDecisions(page, 'PI', {
      row: conResponsable, columns: restricciones,
    });
    for (const prop of restricciones) {
      expect(decisiones[prop].readOnly, `${prop} declarada por la config`).toBe(false);
    }

    // El complemento —una columna de restricción que la config NO declare y
    // que por tanto salga readOnly— no se puede medir aquí: la grilla monta
    // exactamente las columnas que la config declara, así que no existe una
    // "restricción no declarada" con celda. Queda como límite de la red, no
    // como regla sin cubrir; se anota en la tabla de cobertura.
  });
});

test.describe('I7: la apertura del dropdown re-verifica la decisión', () => {
  /**
   * El editor de una celda de dropdown solo debe abrirse cuando la regla la
   * declara editable. Se comprueba por el editor real y en los dos sentidos:
   * con rol A sin confirmar (abre) y con rol V (no abre), sobre la misma
   * columna y la misma fila, para que la diferencia solo pueda venir de la
   * decisión.
   */
  test('el editor de Responsable_AIA se abre solo cuando la celda es editable', async ({ page }) => {
    const { semana } = await openIntermedia(page);
    const { conResponsable } = await findRows(page);

    await setEnablementContext(page, 'PI', {
      permiso: 'A', semana, maxSemana: semana, semanalConfirmada: 0,
    });
    const conA = await expectDecisionMatchesEditor(page, 'PI', {
      row: conResponsable, prop: 'Responsable_AIA',
    });
    expect(conA.readOnly, 'Responsable_AIA con rol A sin confirmar').toBe(false);

    await setEnablementContext(page, 'PI', {
      permiso: 'V', semana, maxSemana: semana, semanalConfirmada: 0,
    });
    const conV = await expectDecisionMatchesEditor(page, 'PI', {
      row: conResponsable, prop: 'Responsable_AIA',
    });
    expect(conV.readOnly, 'Responsable_AIA con rol V').toBe(true);
  });
});
