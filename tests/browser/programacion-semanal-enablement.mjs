/**
 * Red de caracterización de las reglas de habilitación de Programación
 * Semanal (S1–S6, S11–S13). Plan F2a-2b-1.
 *
 * CARACTERIZA, NO CORRIGE: cada aserción fija lo que el código hace hoy.
 * Si algo parece un bug, se caracteriza el comportamiento real y se anota
 * en la tabla de «comportamientos a revisar» del informe del plan.
 */
import { test, expect } from '@playwright/test';
import { login, selectProject, changeWeek } from './support/session.mjs';
import {
  waitForGridReady,
  setEnablementContext,
  readCellDecisions,
  expectDecisionMatchesEditor,
  countGridRows,
} from './support/enablement-probe.mjs';

// La base local y el fixture de CI no siembran los mismos proyectos ni los
// mismos volúmenes: JMC existe en CI; en la base local, «Da Porto» tiene 3
// filas que no llegan a la grilla y «Preconstrucción Da Porto» tiene 212 en
// tres semanas. Se prueba en orden y se usa el primero que de verdad rinda
// filas, en vez de atarse a un proyecto concreto o a un orden de siembra.
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

/**
 * Abre Programación Semanal en una semana que de verdad tenga filas.
 *
 * No se fija la semana como constante: el proyecto sembrado avanza y un
 * literal se pudre (misma lección que dejó escrita
 * programacion-semanal-roles-phases.mjs). Se recorre desde Max_Semana hacia
 * atrás hasta encontrar la primera con filas, porque sin filas no hay celda
 * cuya decisión leer.
 */
async function openSemanal(page) {
  await login(page);
  await selectAvailableProject(page);
  await page.goto('/programacion-semanal');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
  const maxSemana = Number(await page.locator('#Max_Semana').inputValue());
  expect(Number.isInteger(maxSemana) && maxSemana > 0, `Max_Semana inválido: ${maxSemana}`).toBe(true);

  for (let semana = maxSemana; semana >= 1; semana -= 1) {
    await changeWeek(page, semana, '/programacion-semanal');
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    const rows = await countGridRows(page, 'PS');
    if (rows > 0) {
      await waitForGridReady(page, 'PS');
      return { maxSemana, semana, rows };
    }
  }
  throw new Error(`Ninguna semana 1..${maxSemana} tiene filas en Programación Semanal`);
}

test.describe('arnés: validación contra dos reglas conocidas', () => {
  test('S6: una columna readOnly fija sale readOnly con cualquier rol y fase', async ({ page }) => {
    const { maxSemana, semana } = await openSemanal(page);
    for (const permiso of ['A', 'V']) {
      for (const semanalConfirmada of [0, 1]) {
        await setEnablementContext(page, 'PS', {
          permiso, semana, maxSemana, semanalConfirmada,
        });
        const decisions = await readCellDecisions(page, 'PS', { row: 0, columns: ['Actividad'] });
        expect(decisions.Actividad.readOnly, `Actividad con ${permiso}/conf=${semanalConfirmada}`).toBe(true);
        expect(decisions.Actividad.classes).toContain('ps-cell-readonly');
      }
    }
  });

  test('S1+S2: Compromiso readOnly con V, editable con A en semana corriente sin confirmar', async ({ page }) => {
    const { maxSemana, semana } = await openSemanal(page);

    await setEnablementContext(page, 'PS', {
      permiso: 'V', semana, maxSemana, semanalConfirmada: 0,
    });
    const conV = await expectDecisionMatchesEditor(page, 'PS', { row: 0, prop: 'Compromiso' });
    expect(conV.readOnly, 'Compromiso con rol V').toBe(true);

    await setEnablementContext(page, 'PS', {
      permiso: 'A', semana, maxSemana, semanalConfirmada: 0,
    });
    const conA = await expectDecisionMatchesEditor(page, 'PS', { row: 0, prop: 'Compromiso' });
    expect(conA.readOnly, 'Compromiso con rol A, semana corriente, sin confirmar').toBe(false);
  });
});

/**
 * `editableProps` declara NUEVE props (hot.js:37-47), pero la grilla solo monta
 * OCHO de ellas como columna: `Descripcion` no está en el array `columns`
 * (hot.js:2641-2667), así que `propToCol('Descripcion')` devuelve -1 y no hay
 * celda cuya decisión leer. Se separa aquí en vez de disimularlo, y la brecha
 * queda fijada por su propia prueba más abajo.
 */
const DECLARED_EDITABLE_PROPS = [
  'Descripcion', 'Ubicacion', 'Sub_Contratista', 'Responsable_AIA',
  'Compromiso', 'Ejecutado_Real', 'Categoria_CNC', 'CNC', 'Observaciones_CNC',
];
const PROPS_SIN_COLUMNA = ['Descripcion'];
const EDITABLE_PROPS = DECLARED_EDITABLE_PROPS.filter((p) => !PROPS_SIN_COLUMNA.includes(p));
const CONTROL_READONLY_PROP = 'Actividad';

const ROLES = ['A', 'D', 'R', 'DCV', 'C', 'V'];
const DIRECTOR_ROLES = ['A', 'D'];
const EDITOR_ROLES = ['A', 'D', 'R', 'DCV'];

/**
 * Oráculo: replica, cláusula por cláusula, lo que hoy hace isPropReadOnly()
 * (hot.js:411-429). Se escribe aquí para que un cambio en producción rompa
 * esta red en vez de pasar inadvertido; los nombres S1..S4 son los del
 * inventario del plan.
 *
 * OJO con el orden: la cláusula de Ejecutado_Real (S3) se evalúa ANTES que
 * la de semana histórica (S2), así que no la hereda. Está caracterizado
 * aparte en «comportamientos a revisar».
 */
function esperadoReadOnly(prop, { permiso, esHistorica, semanalConfirmada }) {
  if (!DECLARED_EDITABLE_PROPS.includes(prop)) return true;              // S1/S6
  if (prop === 'Ejecutado_Real') {                                       // S3
    return semanalConfirmada !== 1 || !EDITOR_ROLES.includes(permiso);
  }
  const permitido = esHistorica                                          // S2
    ? DIRECTOR_ROLES.includes(permiso)
    : EDITOR_ROLES.includes(permiso);
  if (!permitido) return true;
  if (['Compromiso', 'Sub_Contratista', 'Responsable_AIA'].includes(prop) // S4
    && semanalConfirmada === 1) return true;
  return false;
}

test.describe('S1–S4, S6: la matriz de roles, fases y semanas', () => {
  test('las nueve columnas editables deciden igual que la regla, en las 24 combinaciones', async ({ page }) => {
    const { semana } = await openSemanal(page);
    // La semana histórica se fabrica moviendo Max_Semana, no la semana: así la
    // grilla conserva las filas que ya cargó y solo cambia el contexto que la
    // regla lee (Max_Semana - 2 >= semana).
    const escenarios = [
      { etiqueta: 'corriente', maxSemana: semana, esHistorica: false },
      { etiqueta: 'histórica', maxSemana: semana + 2, esHistorica: true },
    ];

    const desviaciones = [];
    for (const permiso of ROLES) {
      for (const semanalConfirmada of [0, 1]) {
        for (const escenario of escenarios) {
          await setEnablementContext(page, 'PS', {
            permiso, semana, maxSemana: escenario.maxSemana, semanalConfirmada,
          });
          const columnas = [...EDITABLE_PROPS, CONTROL_READONLY_PROP];
          const decisiones = await readCellDecisions(page, 'PS', { row: 0, columns: columnas });
          for (const prop of columnas) {
            const esperado = esperadoReadOnly(prop, {
              permiso, esHistorica: escenario.esHistorica, semanalConfirmada,
            });
            if (decisiones[prop].readOnly !== esperado) {
              desviaciones.push(
                `${prop} · rol ${permiso} · confirmada=${semanalConfirmada} · semana ${escenario.etiqueta}: `
                + `esperado readOnly=${esperado}, real=${decisiones[prop].readOnly}`,
              );
            }
          }
        }
      }
    }
    expect(desviaciones, `Desviaciones:\n${desviaciones.join('\n')}`).toEqual([]);
  });

  test('anclas literales: seis combinaciones fijadas a mano, sin pasar por el oráculo', async ({ page }) => {
    const { semana } = await openSemanal(page);
    const anclas = [
      { permiso: 'A', maxSemana: semana, semanalConfirmada: 0, prop: 'Compromiso', readOnly: false },
      { permiso: 'V', maxSemana: semana, semanalConfirmada: 0, prop: 'Compromiso', readOnly: true },
      { permiso: 'A', maxSemana: semana, semanalConfirmada: 1, prop: 'Compromiso', readOnly: true },
      { permiso: 'R', maxSemana: semana + 2, semanalConfirmada: 0, prop: 'Ubicacion', readOnly: true },
      { permiso: 'A', maxSemana: semana + 2, semanalConfirmada: 0, prop: 'Ubicacion', readOnly: false },
      { permiso: 'A', maxSemana: semana, semanalConfirmada: 0, prop: 'Ejecutado_Real', readOnly: true },
    ];
    for (const ancla of anclas) {
      await setEnablementContext(page, 'PS', {
        permiso: ancla.permiso, semana, maxSemana: ancla.maxSemana,
        semanalConfirmada: ancla.semanalConfirmada,
      });
      const decisiones = await readCellDecisions(page, 'PS', { row: 0, columns: [ancla.prop] });
      expect(
        decisiones[ancla.prop].readOnly,
        `${ancla.prop} · rol ${ancla.permiso} · confirmada=${ancla.semanalConfirmada} · maxSemana=${ancla.maxSemana}`,
      ).toBe(ancla.readOnly);
    }
  });
});

test.describe('comportamientos a revisar (caracterizados, no corregidos)', () => {
  /**
   * `Ejecutado_Real` se resuelve con un `return` propio ANTES de la comprobación
   * de semana histórica, así que en fase de calificación un rol R edita el
   * avance de una semana histórica que no puede tocar en ninguna otra columna.
   * No se corrige aquí (el plan lo prohíbe): se fija tal cual y se anota.
   */
  test('Ejecutado_Real ignora la restricción de semana histórica', async ({ page }) => {
    const { semana } = await openSemanal(page);
    await setEnablementContext(page, 'PS', {
      permiso: 'R', semana, maxSemana: semana + 2, semanalConfirmada: 1,
    });
    const decisiones = await readCellDecisions(page, 'PS', {
      row: 0, columns: ['Ejecutado_Real', 'Ubicacion'],
    });
    expect(decisiones.Ubicacion.readOnly, 'Ubicacion en histórica con R').toBe(true);
    expect(decisiones.Ejecutado_Real.readOnly, 'Ejecutado_Real en histórica con R').toBe(false);
  });

  /**
   * `Descripcion` se declara editable en `editableProps` pero la grilla no la
   * monta como columna, así que esa entrada no gobierna ninguna celda. Importa
   * para lo que viene: al extraer las reglas, una lista de nueve props haría
   * pensar que hay nueve columnas que atender, y son ocho.
   */
  test('Descripcion es editable por regla pero no tiene columna en la grilla', async ({ page }) => {
    await openSemanal(page);
    const sinColumna = await page.evaluate((props) => {
      const hot = window.PSHotModule.getHotInstance();
      return props.filter((prop) => {
        const col = hot.propToCol(prop);
        return !Number.isInteger(col) || col < 0;
      });
    }, DECLARED_EDITABLE_PROPS);
    expect(sinColumna).toEqual(PROPS_SIN_COLUMNA);
  });
});

test.describe('S11 y S13: las dos reglas que no se leen de las clases de celda', () => {
  /**
   * S11 — el dropdown solo auto-abre si la celda no es readOnly
   * (hot.js:1300-1317). Se comprueba por la vía del editor real, no por la
   * clase, porque la decisión vive en shouldAutoOpenDropdown().
   */
  test('S11: el editor de un dropdown se abre solo cuando la celda es editable', async ({ page }) => {
    const { semana } = await openSemanal(page);

    await setEnablementContext(page, 'PS', {
      permiso: 'A', semana, maxSemana: semana, semanalConfirmada: 0,
    });
    const editable = await expectDecisionMatchesEditor(page, 'PS', { row: 0, prop: 'Sub_Contratista' });
    expect(editable.readOnly, 'Sub_Contratista con A sin confirmar').toBe(false);

    // Confirmada = 1 bloquea Sub_Contratista por S4; el dropdown debe seguirla.
    await setEnablementContext(page, 'PS', {
      permiso: 'A', semana, maxSemana: semana, semanalConfirmada: 1,
    });
    const bloqueado = await expectDecisionMatchesEditor(page, 'PS', { row: 0, prop: 'Sub_Contratista' });
    expect(bloqueado.readOnly, 'Sub_Contratista con A y confirmada').toBe(true);
  });

  /**
   * S13 — la card móvil ofrece input solo cuando la regla dice que la celda es
   * editable (`!editableProps[prop] || isPropReadOnly(prop)` → texto plano,
   * hot.js:3393-3396).
   *
   * ES LA PRUEBA QUE SOSTIENE LO QUE VIENE DESPUÉS: ata la card a la MISMA
   * regla que la grilla, así que si al extraer las reglas card y grilla se
   * desincronizan, esto se pone rojo.
   */
  test('S13: la card móvil edita exactamente cuando la grilla edita', async ({ page }) => {
    const { semana } = await openSemanal(page);
    await page.setViewportSize({ width: 390, height: 844 });

    const casos = [
      { permiso: 'A', semanalConfirmada: 0, editableEsperado: true },
      { permiso: 'V', semanalConfirmada: 0, editableEsperado: false },
      { permiso: 'A', semanalConfirmada: 1, editableEsperado: false },
    ];

    for (const caso of casos) {
      await setEnablementContext(page, 'PS', {
        permiso: caso.permiso, semana, maxSemana: semana,
        semanalConfirmada: caso.semanalConfirmada,
      });
      // Las cards NO se repintan solas al cambiar el contexto: renderMobileCards()
      // se dispara desde applyFiltersAndRender() y desde los guardados, no desde
      // un resize. Se usa la API pública del módulo (reload → loadData →
      // applyFiltersAndRender → renderMobileCards), que es la vía del propio
      // código y no reescribe los inputs de contexto inyectados.
      await page.evaluate(() => window.PSHotModule.reload());
      await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });

      const decisiones = await readCellDecisions(page, 'PS', { row: 0, columns: ['Compromiso'] });
      expect(
        decisiones.Compromiso.readOnly,
        `grilla · ${caso.permiso}/conf=${caso.semanalConfirmada}`,
      ).toBe(!caso.editableEsperado);

      const cardTieneInput = await page.evaluate(() => {
        const card = document.querySelector('#mobile-card-view .ps-mobile-card');
        if (!card) return null;
        return Boolean(card.querySelector('input[data-mobile-prop="Compromiso"]'));
      });
      expect(
        cardTieneInput,
        `card · ${caso.permiso}/conf=${caso.semanalConfirmada}: la card debe seguir a la grilla`,
      ).toBe(caso.editableEsperado);
    }
  });
});
