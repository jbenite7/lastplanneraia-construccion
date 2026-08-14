/**
 * Arnés para caracterizar las reglas de habilitación de las grillas de
 * Programación Semanal e Intermedia (plan F2a-2b-1, spec E7/E8).
 *
 * Las reglas leen su contexto del DOM en cada llamada (#permiso_canonico,
 * #semana/#semana_PHP, #Max_Semana, #Semanal_Confirmada), así que la matriz
 * de roles × fases × semanas se recorre inyectando esos inputs en vez de
 * abrir una sesión por rol — test.C, test.D no están habilitadas y DCV no
 * tiene cuenta (AGENTS.md §Seguridad).
 *
 * La parte delicada no es escribir los inputs sino forzar a la grilla a
 * RE-DECIDIR, porque Handsontable cachea cellMeta:
 *  - Semanal: `cells()` llama a isPropReadOnly() en cada evaluación y no
 *    escribe setCellMeta, así que `hot.render()` + getCellMeta bastan.
 *  - Intermedia: `_canEditGlobal` se fija en buildRowClassCache() y las
 *    filas se cachean (_rowMetaCache…). La vía que el propio módulo usa para
 *    saltarse ese cache es su hook `afterFilter` (resetPIRowCaches +
 *    buildRowClassCache + applyRowClassesToDOM), así que el inyector lo
 *    dispara con hot.runHooks('afterFilter') en vez de recargar la página,
 *    que perdería el contexto inyectado.
 *
 * Límite declarado: esto caracteriza la DECISIÓN DEL CLIENTE. No afirma nada
 * sobre la autorización del servidor (tests/test_semanal_rbac_solo_lectura.php
 * y tests/test_weekly_governance.php cubren esa otra mitad).
 */
import { expect } from '@playwright/test';

function resolveModule(moduleKey) {
  if (moduleKey !== 'PS' && moduleKey !== 'PI') {
    throw new Error(`moduleKey debe ser 'PS' o 'PI', llegó: ${moduleKey}`);
  }
  return moduleKey;
}

/**
 * Espera a que la grilla del módulo esté montada y con datos.
 */
export async function waitForGridReady(page, moduleKey, timeout = 45000) {
  const key = resolveModule(moduleKey);
  await page.locator('#loading').waitFor({ state: 'hidden', timeout });
  await page.waitForFunction((k) => {
    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && typeof mod.getHotInstance === 'function' && mod.getHotInstance();
    return Boolean(hot && typeof hot.countRows === 'function' && hot.countRows() > 0);
  }, key, { timeout });
}

/**
 * Fija el contexto que las reglas leen del DOM y fuerza la re-decisión.
 *
 * Campos: { permiso, semana, maxSemana, semanalConfirmada } — los que se
 * omitan conservan el valor que puso el servidor.
 */
export async function setEnablementContext(page, moduleKey, context) {
  const key = resolveModule(moduleKey);
  await page.evaluate(({ k, ctx }) => {
    const setValue = (selector, value) => {
      const el = document.querySelector(selector);
      if (el) el.value = String(value);
    };
    if (ctx.permiso !== undefined) setValue('#permiso_canonico', ctx.permiso);
    if (ctx.semana !== undefined) {
      setValue('#semana', ctx.semana);
      setValue('#semana_PHP', ctx.semana);
    }
    if (ctx.maxSemana !== undefined) setValue('#Max_Semana', ctx.maxSemana);
    if (ctx.semanalConfirmada !== undefined) setValue('#Semanal_Confirmada', ctx.semanalConfirmada);

    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && mod.getHotInstance && mod.getHotInstance();
    if (!hot) throw new Error('No hay instancia de Handsontable montada');

    if (k === 'PI') {
      // La vía propia del módulo para invalidar sus caches de fila y
      // recalcular _canEditGlobal — ver el hook afterFilter en
      // public/js/modules/programacion_intermedia/hot.js.
      hot.runHooks('afterFilter');
    }
    hot.render();
  }, { k: key, ctx: context });
}

/**
 * Lee la decisión de habilitación por columna para una fila visual dada.
 *
 * Devuelve { [prop]: { readOnly, classes } }. Se lee de getCellMeta, que
 * re-evalúa el callback `cells()` del módulo — la misma función que decide
 * en producción — y devuelve también las clases (`ps-cell-readonly`,
 * `pi-cell-editable`, `pi-cell-readonly`, `pi-cell-locked-resp`,
 * `pi-cell-dropdown`) con las que la decisión se hace visible en el DOM.
 */
export async function readCellDecisions(page, moduleKey, { row, columns }) {
  const key = resolveModule(moduleKey);
  return page.evaluate(({ k, visualRow, props }) => {
    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && mod.getHotInstance && mod.getHotInstance();
    if (!hot) throw new Error('No hay instancia de Handsontable montada');

    const decisions = {};
    for (const prop of props) {
      const col = hot.propToCol(prop);
      if (!Number.isInteger(col) || col < 0) {
        decisions[prop] = { error: `propToCol no resuelve "${prop}"` };
        continue;
      }
      const meta = hot.getCellMeta(visualRow, col) || {};
      decisions[prop] = {
        readOnly: meta.readOnly === true,
        classes: String(meta.className || '').split(/\s+/).filter(Boolean),
      };
    }
    return decisions;
  }, { k: key, visualRow: row, props: columns });
}

/**
 * Devuelve los datos fuente de la fila visual, para elegir filas con o sin
 * Responsable AIA, filas cabecera, etc. sin sembrar datos.
 */
export async function readSourceRow(page, moduleKey, row) {
  const key = resolveModule(moduleKey);
  return page.evaluate(({ k, visualRow }) => {
    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && mod.getHotInstance && mod.getHotInstance();
    if (!hot) throw new Error('No hay instancia de Handsontable montada');
    const physicalRow = typeof hot.toPhysicalRow === 'function' ? hot.toPhysicalRow(visualRow) : visualRow;
    return hot.getSourceDataAtRow(physicalRow) || null;
  }, { k: key, visualRow: row });
}

export async function countGridRows(page, moduleKey) {
  const key = resolveModule(moduleKey);
  return page.evaluate((k) => {
    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && mod.getHotInstance && mod.getHotInstance();
    return hot ? hot.countRows() : 0;
  }, key);
}

/**
 * Comprueba que el lector no miente: intenta abrir el editor de una celda por
 * la vía del usuario (seleccionar + Enter) y devuelve si se abrió de verdad.
 *
 * Un lector que solo leyera clases caracterizaría el CSS; esta función ata la
 * decisión leída al comportamiento real del editor. Se usa al menos una vez
 * por sentido (editable acepta, readOnly rechaza) al validar el arnés.
 */
export async function attemptOpenEditor(page, moduleKey, { row, prop }) {
  const key = resolveModule(moduleKey);
  return page.evaluate(async ({ k, visualRow, targetProp }) => {
    const mod = k === 'PS' ? window.PSHotModule : window.PIHotModule;
    const hot = mod && mod.getHotInstance && mod.getHotInstance();
    if (!hot) throw new Error('No hay instancia de Handsontable montada');
    const col = hot.propToCol(targetProp);
    if (!Number.isInteger(col) || col < 0) throw new Error(`propToCol no resuelve "${targetProp}"`);

    hot.scrollViewportTo(visualRow, col);
    hot.selectCell(visualRow, col);
    if (hot.getActiveEditor && hot.getActiveEditor()) {
      // Cierra cualquier editor previo antes de intentar abrir el nuestro.
      hot.getActiveEditor().close();
    }
    // La vía que usa el propio Handsontable cuando el usuario pulsa Enter/F2.
    if (typeof hot.beginEditing === 'function') {
      hot.beginEditing();
    } else {
      const editor = hot.getActiveEditor();
      if (editor) editor.beginEditing();
    }
    await new Promise((resolve) => setTimeout(resolve, 50));
    const editor = hot.getActiveEditor ? hot.getActiveEditor() : null;
    const opened = Boolean(editor && typeof editor.isOpened === 'function' && editor.isOpened());
    if (opened) editor.close();
    hot.deselectCell();
    return opened;
  }, { k: key, visualRow: row, targetProp: prop });
}

/**
 * Aserción de conveniencia: la decisión leída y el editor real coinciden.
 */
export async function expectDecisionMatchesEditor(page, moduleKey, { row, prop }) {
  const decisions = await readCellDecisions(page, moduleKey, { row, columns: [prop] });
  const opened = await attemptOpenEditor(page, moduleKey, { row, prop });
  expect(
    opened,
    `El lector dice readOnly=${decisions[prop].readOnly} para "${prop}" (fila ${row}) `
    + `pero el editor ${opened ? 'se abrió' : 'no se abrió'}: el lector estaría mintiendo`,
  ).toBe(!decisions[prop].readOnly);
  return decisions[prop];
}
