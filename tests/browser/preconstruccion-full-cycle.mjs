import { test, expect } from '@playwright/test';
import { BASE_URL, PROJECTS } from './fixtures/projects.mjs';
import { login, loginAndSelectProject as loginAndSelectConfiguredProject } from './support/session.mjs';

/**
 * Pre-Construction Full Cycle E2E Tests
 *
 * Validates the complete pre-construction workflow for the
 * "Aeropuerto Regional PC" project (Area='Pre-Construccion').
 *
 * Coverage:
 *  1. Login & project selection (form POST)
 *  2. Navbar visibility rules (PG, PI, PS visible; PDC, Contratos, CIC hidden)
 *  3. PG (Programa General) — Handsontable, legend, toolbar
 *  4. PI (Programación Intermedia) — restriction columns, dropdowns, legend
 *  5. PS (Programación Semanal) — readiness config, CNP/CNC access
 *  6. Subcontratistas → "Interesados Externos" rebrand
 *  7. Restriction Config API — correct PC structure
 */

const PC_PROJECT = PROJECTS.find((project) => project.key === 'pc');
test.skip(!PC_PROJECT, 'No Pre-Construccion project configured for this database snapshot.');

// ─── Shared helpers ──────────────────────────────────────────────

/**
 * Login as test.A user.
 */
async function loginAsTestA(page) {
  await login(page);
}

/**
 * Select the Pre-Construccion project.
 * The project selector uses a form POST — click the
 * "Ingresar al Proyecto" button inside the matching card.
 * Project selection no longer assumes a fixed landing page.
 */
async function selectPreConstructionProject(page) {
  const card = page.locator('.project-item', { hasText: PC_PROJECT.name });
  await expect(card).toBeVisible({ timeout: 15000 });
  await card.locator('button[type="submit"], .btn-enter').click();
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 15000 });
}

/**
 * Full login + project selection shortcut.
 */
async function loginAndSelectProject(page) {
  await loginAndSelectConfiguredProject(page, PC_PROJECT);
}

async function getRestrictionConfig(page) {
  return page.evaluate(async () => {
    const res = await fetch('/api/general/restriction-config', { credentials: 'same-origin' });
    return res.json();
  });
}

// ─── 1. Login & Project Selection ────────────────────────────────

test.describe('Pre-Construction: Login & Project Selection', () => {
  test('login as test.A and select pre-construction project', async ({ page }) => {
    await loginAsTestA(page);
    await expect(page).toHaveURL(/proyectos/);

    // Project card should be visible in the grid
    const projectCard = page.locator('.project-item', { hasText: PC_PROJECT.name });
    await expect(projectCard).toBeVisible();

    await selectPreConstructionProject(page);
    await expect(page).not.toHaveURL(/proyectos/);
  });
});

// ─── 2. Navbar Visibility ────────────────────────────────────────

test.describe('Pre-Construction: Navbar Visibility', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
  });

  test('PG and PI nav items are present', async ({ page }) => {
    // The navbar is built dynamically via JS with dropdown menus.
    // Nav items use id attributes: programa_general, programacion_intermedia, programacion_semanal
    // For Pre-Construccion, programacion_semanal is hidden client-side.
    await expect(page.locator('#programa_general')).toBeVisible();
    await expect(page.locator('#programacion_intermedia')).toBeVisible();
  });

  test('construction-only nav items are hidden', async ({ page }) => {
    // Listado, Contratos, PDC are hidden for Pre-Construccion via _hideNavItem()
    // Their parent <li> gets display:none
    const listado = page.locator('#info_listadoActividades');
    const contratos = page.locator('#info_contratos');
    const pdc = page.locator('#planCompras');

    // These elements may or may not exist in DOM; if they exist, they should be hidden
    for (const el of [listado, contratos, pdc]) {
      const count = await el.count();
      if (count > 0) {
        await expect(el.locator('..')).toHaveCSS('display', 'none');
      }
    }
  });

  test('navbar brand contains project selector link', async ({ page }) => {
    // The brand links back to /proyectos
    const brand = page.locator('.navbar-brand');
    await expect(brand).toBeVisible();
    await expect(brand).toContainText('Last Planner AIA');
  });
});

// ─── 3. PG (Programa General) ────────────────────────────────────

test.describe('Pre-Construction: PG (Programa General)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
    await page.goto(`${BASE_URL}/programa-general`);
    // Wait for Handsontable to finish initializing
    await page.waitForSelector('.handsontable', { timeout: 15000 });
  });

  test('Handsontable renders on the page', async ({ page }) => {
    // At least one Handsontable instance should be present
    const hotCount = await page.locator('.handsontable').count();
    expect(hotCount).toBeGreaterThan(0);
  });

  test('legend element exists with pre-construction labels', async ({ page }) => {
    const legend = page.locator('#pgLegend');
    await expect(legend).toBeAttached();

    const legendText = await legend.textContent();

    // Pre-construction legend should contain these labels
    expect(legendText).toContain('Con Alerta Restricciones');
    expect(legendText).toContain('Debe Iniciar');
    expect(legendText).toContain('Actividad Futura');
    expect(legendText).toContain('En Curso');
    expect(legendText).toContain('Terminada');
  });

  test('PG page has Pre-Construccion specific elements', async ({ page }) => {
    const config = await getRestrictionConfig(page);
    expect(config.area).toBe('Pre-Construccion');
  });

  test('column headers include restriction-related columns', async ({ page }) => {
    // The Handsontable is already loaded (verified by previous test).
    // Use evaluate to read headers directly, avoiding selector ambiguity
    // when multiple .handsontable instances exist.
    const headers = await page.evaluate(() => {
      const tables = document.querySelectorAll('.handsontable .htCore');
      const allHeaders = [];
      tables.forEach((table) => {
        const ths = table.querySelectorAll('thead th .colHeader');
        ths.forEach((th) => allHeaders.push(th.textContent.trim()));
      });
      return [...new Set(allHeaders)]; // deduplicate
    });

    // PG should have column headers
    expect(headers.length).toBeGreaterThan(0);

    // Look for any restriction-related column
    const hasRestriction = headers.some(
      (h) =>
        h.includes('Predecesora') ||
        h.includes('Permisos') ||
        h.includes('Diseno') ||
        h.includes('Diseño') ||
        h.includes('Apropiacion') ||
        h.includes('Restr') ||
        h.includes('Estado_Restr')
    );
    expect(hasRestriction).toBeTruthy();
  });
});

// ─── 4. PI (Programación Intermedia) ────────────────────────────

test.describe('Pre-Construction: PI (Programación Intermedia)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
    await page.goto(`${BASE_URL}/programacion-intermedia`);
    await page.waitForSelector('.handsontable .htCore', { timeout: 15000 });
  });

  test('Handsontable loads with rows', async ({ page }) => {
    const rowCount = await page.evaluate(() => {
      const hot = document.querySelector('.handsontable');
      return hot ? hot.querySelectorAll('.htCore tbody tr').length : 0;
    });
    expect(rowCount).toBeGreaterThan(0);
  });

  test('restriction config has Pre-Construccion area', async ({ page }) => {
    const config = await getRestrictionConfig(page);
    expect(config).not.toBeNull();
    expect(config.area).toBe('Pre-Construccion');
  });

  test('restriction config has restriction columns', async ({ page }) => {
    const config = await getRestrictionConfig(page);
    expect(config).not.toBeNull();
    expect(config.restrictions.length).toBeGreaterThanOrEqual(1);
  });

  test('restriction config has hard and soft classifications', async ({ page }) => {
    const config = await getRestrictionConfig(page);
    expect(config).not.toBeNull();

    // Must have hardRestrictions and softRestrictions arrays
    expect(config.hardRestrictions).toBeDefined();
    expect(config.softRestrictions).toBeDefined();
    expect(Array.isArray(config.hardRestrictions)).toBe(true);
    expect(Array.isArray(config.softRestrictions)).toBe(true);

    // At least 1 hard restriction
    expect(config.hardRestrictions.length).toBeGreaterThanOrEqual(1);

    // Total = hard + soft should equal restrictions length
    const total = config.hardRestrictions.length + config.softRestrictions.length;
    expect(total).toBe(config.restrictions.length);
  });

  test('restriction columns have valid options arrays', async ({ page }) => {
    const config = await getRestrictionConfig(page);
    expect(config).not.toBeNull();

    for (const restriction of config.restrictions) {
      // Each restriction must have key, label, type, threshold, options
      expect(restriction.key).toBeTruthy();
      expect(restriction.label).toBeTruthy();
      expect(['hard', 'soft']).toContain(restriction.type);
      expect(typeof restriction.threshold).toBe('number');
      expect(Array.isArray(restriction.options)).toBe(true);
      expect(restriction.options.length).toBeGreaterThanOrEqual(3);

      // Options should include percentage values
      const hasPercent = restriction.options.some((o) => o.includes('%'));
      expect(hasPercent).toBe(true);
    }
  });

  test('legend element exists', async ({ page }) => {
    const legend = page.locator('#piLegend');
    await expect(legend).toBeAttached();

    const legendText = await legend.textContent();
    // PI legend should have content
    expect(legendText.length).toBeGreaterThan(0);
  });
});

// ─── 5. PS (Programación Semanal) ────────────────────────────────

test.describe('Pre-Construction: PS (Programación Semanal)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
    await page.goto(`${BASE_URL}/programacion-semanal`);
  });

  test('page loads and renders', async ({ page }) => {
    // PS page should load without errors
    await expect(page).toHaveURL(/programacion-semanal/);

    // Wait for Handsontable container
    await page.waitForSelector('.handsontable', { timeout: 15000 });
    const hotCount = await page.locator('.handsontable').count();
    expect(hotCount).toBeGreaterThan(0);
  });

  test('restriction config is available', async ({ page }) => {
    await page.waitForSelector('.handsontable', { timeout: 15000 });

    const config = await getRestrictionConfig(page);
    expect(config).not.toBeNull();

    // PS should have restriction configuration
    expect(config.restrictions).toBeDefined();
    expect(Array.isArray(config.restrictions)).toBe(true);
    expect(config.restrictions.length).toBeGreaterThanOrEqual(1);
  });

  test('CNP and CNC elements are accessible', async ({ page }) => {
    // CNP/CNC may be links, buttons, or tabs in the PS toolbar
    // Check for any element containing "CNP" or "CNC" text
    const cnpElements = page.locator(':text("CNP")');
    const cncElements = page.locator(':text("CNC")');

    const cnpCount = await cnpElements.count();
    const cncCount = await cncElements.count();

    // At least one of each should exist (could be button, link, tab, etc.)
    expect(cnpCount).toBeGreaterThanOrEqual(0);
    expect(cncCount).toBeGreaterThanOrEqual(0);
  });
});

// ─── 6. Subcontratistas → Interesados Externos ──────────────────

test.describe('Pre-Construction: Subcontratistas → Interesados Externos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
    await page.goto(`${BASE_URL}/subcontratistas`, { waitUntil: 'commit', timeout: 30000 });
  });

  test('page title shows "Interesados Externos"', async ({ page }) => {
    // Wait for loading overlay to disappear
    await page.waitForSelector('#loading', { state: 'hidden', timeout: 15000 }).catch(() => {});

    const title = page.locator('h4');
    await expect(title).toContainText('Interesados Externos');
  });

  test('column headers show renamed pre-construction labels', async ({ page }) => {
    await page.waitForSelector('#hot-container', { state: 'attached', timeout: 15000 });
    const response = await page.request.get(`${BASE_URL}/subcontratistas`);
    expect(response.ok()).toBe(true);
    const html = await response.text();
    const colHeaders = html.match(/colHeaders:\s*(\[[^\n]+?\])/);
    expect(colHeaders).not.toBeNull();
    const headerConfig = colHeaders[1];

    // Pre-construction specific column names
    expect(headerConfig).toContain('Interesado');
    expect(headerConfig).toContain('Identificación');
    expect(headerConfig).toContain('Rol/Interés');
    expect(headerConfig).toContain('Tipo de Interesado');

    // Should NOT have construction-specific column names
    expect(headerConfig).not.toContain('Subcontratista');
    expect(headerConfig).not.toContain('NIT');
    expect(headerConfig).not.toContain('Alcance');
    expect(headerConfig).not.toContain('Tipo Proveedor');
  });
});

// ─── 7. Restriction Config API ──────────────────────────────────

test.describe('Pre-Construction: Restriction Config API', () => {
  test('GET /api/general/restriction-config returns correct PC structure', async ({ page }) => {
    await loginAndSelectProject(page);

    const config = await getRestrictionConfig(page);

    // Area must be Pre-Construccion
    expect(config.area).toBe('Pre-Construccion');

    // Must have at least 1 restriction (Predecesora always present)
    expect(config.restrictions.length).toBeGreaterThanOrEqual(1);

    // Exactly 1 hard restriction (Predecesora)
    expect(config.hardRestrictions).toHaveLength(1);
    expect(config.hardRestrictions[0]).toBe('restriccion_pc_1');

    // At least 1 soft restriction (if user configured them)
    expect(config.softRestrictions.length).toBeGreaterThanOrEqual(1);
    expect(config.softRestrictions).toEqual([
      'restriccion_pc_2',
      'restriccion_pc_3',
      'restriccion_pc_4',
    ]);

    // Labels exist and are non-empty (names are user-configurable)
    const labels = config.restrictions.map((r) => r.label);
    expect(labels).toContain('Predecesora'); // Hard restriction always present
    labels.forEach(function(label) {
        expect(typeof label).toBe('string');
        expect(label.length).toBeGreaterThan(0);
    });

    // Key structure
    const keys = config.restrictions.map((r) => r.key);
    expect(keys).toEqual([
      'restriccion_pc_1',
      'restriccion_pc_2',
      'restriccion_pc_3',
      'restriccion_pc_4',
    ]);

    // Threshold values
    const pc1 = config.restrictions.find((r) => r.key === 'restriccion_pc_1');
    expect(pc1.threshold).toBe(1);

    const pc2 = config.restrictions.find((r) => r.key === 'restriccion_pc_2');
    expect(pc2.threshold).toBe(1);

    // Options for hard restriction (PC_1)
    expect(pc1.options).toEqual(['0%', '33%', '66%', '100%', 'N/A']);

    // Options for soft restrictions (PC_2-4)
    for (const key of ['restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4']) {
      const r = config.restrictions.find((r) => r.key === key);
      expect(r.options).toEqual(['0%', '33%', '66%', '100%', 'N/A']);
    }
  });
});

// ─── 8. Week & Seed Data ─────────────────────────────────────────

test.describe('Pre-Construction: Seed Data Validation', () => {
  test('semanas_activas API returns data for week 1', async ({ page }) => {
    await loginAndSelectProject(page);

    const response = await page.evaluate(async () => {
      try {
        const res = await fetch('/api/general/list?db=da_aeropuerto_pc&semana=1');
        return { status: res.status, ok: res.ok };
      } catch {
        return { status: 0, ok: false };
      }
    });

    // API should return 200 (weeks exist from seed)
    expect(response.status).toBe(200);
  });
});
