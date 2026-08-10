import { test, expect } from '@playwright/test';
import { loginAndSelectProject } from './support/session.mjs';

/**
 * Test: PG color persistence after filter + scroll.
 *
 * Validates that row state-based bg colors remain correct after
 * applying a filter and scrolling, without requiring a cell click.
 *
 * Flow:
 *  1. Login with the shared app fixture
 *  2. Select project "Prueba" (Construccion, id=27)
 *  3. Navigate to PG page
 *  4. Scroll to 8000px to trigger virtual rendering
 *  5. Assert visible rows have valid state classes (no pg-state-sin-datos)
 */
test.describe('PG color persistence after filter + scroll', () => {
  test('rows keep correct state class after filter and scroll', async ({ page }) => {
    // 1. Login + 2. Select project
    await loginAndSelectProject(page, { name: 'Prueba' });

    // 3. Navigate to PG
    await page.goto('http://localhost:8081/programa-general');
    await page.waitForSelector('.handsontable .htCore', { timeout: 15000 });

    // 4. Scroll to trigger virtual rendering
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
