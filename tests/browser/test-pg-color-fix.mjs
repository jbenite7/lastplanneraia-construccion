import { test, expect } from '@playwright/test';

/**
 * Test: PG color persistence after filter + scroll.
 *
 * Validates that row state-based bg colors remain correct after
 * applying a filter and scrolling, without requiring a cell click.
 *
 * Flow:
 *  1. Login as jbenitez
 *  2. Select project "Optimización Aeropuerto JMC"
 *  3. Navigate to PG page
 *  4. Apply filter "(5A)" via Handsontable filters plugin
 *  5. Scroll to 8000px
 *  6. Assert visible rows have valid state classes (no pg-state-sin-datos)
 */
test.describe('PG color persistence after filter + scroll', () => {
  test('rows keep correct state class after filter and scroll', async ({ page }) => {
    // 1. Login
    await page.goto('http://localhost:8081/login');
    await page.locator('input[name="username"]').fill('jbenitez');
    await page.locator('input[name="password"]').fill('Jbe#1106z');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL('**/proyectos', { timeout: 15000 });

    // 2. Select project
    await page.locator('text=Optimización Aeropuerto JMC').click();
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    // 3. Navigate to PG
    await page.goto('http://localhost:8081/programacion-semanal');
    await page.waitForTimeout(2000);

    // 4. Apply filter (5A) via Handsontable filters plugin
    await page.evaluate(() => {
      const hot = window.PGHotModule?.getHotInstance();
      if (!hot) throw new Error('PGHotModule not available');
      const filtersPlugin = hot.getPlugin('filters');
      filtersPlugin.addCondition(0, 'contains', ['5A']);
      filtersPlugin.filter();
    });
    await page.waitForTimeout(1000);

    // 5. Scroll to 8000px
    await page.evaluate(() => {
      const hot = window.PGHotModule?.getHotInstance();
      if (!hot) throw new Error('PGHotModule not available');
      hot.rootElement.querySelector('.wtHolder').scrollTop = 8000;
    });
    await page.waitForTimeout(1500);

    // 6. Assert visible rows do NOT have pg-state-sin-datos
    const visibleCells = await page.locator('.handsontable td').all();
    let sinDatosCount = 0;
    for (const cell of visibleCells) {
      const classAttr = await cell.getAttribute('class');
      if (classAttr && classAttr.includes('pg-state-sin-datos')) {
        sinDatosCount++;
      }
    }

    expect(sinDatosCount).toBe(0);

    // Also assert at least some rows have a valid state class
    const validStateClasses = [
      'pg-state-actividad-futura',
      'pg-state-en-curso',
      'pg-state-atrasada',
      'pg-state-terminada',
      'pg-state-debe-iniciar',
    ];
    let validCount = 0;
    for (const cell of visibleCells) {
      const classAttr = await cell.getAttribute('class');
      if (classAttr) {
        for (const cls of validStateClasses) {
          if (classAttr.includes(cls)) {
            validCount++;
            break;
          }
        }
      }
    }
    expect(validCount).toBeGreaterThan(0);
  });
});
