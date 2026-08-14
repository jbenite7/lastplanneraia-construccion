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
