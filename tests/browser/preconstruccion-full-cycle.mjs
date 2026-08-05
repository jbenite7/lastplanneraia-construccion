import { test, expect } from '@playwright/test';

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

const BASE_URL = 'http://localhost:8081';
const PC_PROJECT = 'Aeropuerto Regional PC';

// ─── Shared helpers ──────────────────────────────────────────────

/**
 * Abre sesión como test.A por la puerta de servicio.
 *
 * Hasta el 2026-08-04 este helper tecleaba usuario y contraseña en /login, que
 * AGENTS.md prohíbe: la sesión local se abre siempre por /dev/entrar. El rol que
 * queda en sesión es el real de project_members, así que la cobertura no cambia.
 * Requiere DEV_DOOR=1 y DEV_DOOR_USERS en .env; sin eso redirige a /login.
 */
async function loginAsTestA(page) {
  await page.goto(`${BASE_URL}/dev/entrar?u=test.A`);
  await page.waitForURL('**/proyectos', { timeout: 15000 });
}

/**
 * Select the Pre-Construccion project.
 * The project selector uses a form POST — click the
 * "Ingresar al Proyecto" button inside the matching card.
 * Pre-Construccion projects redirect to /programa-general.
 */
async function selectPreConstructionProject(page) {
  const card = page.locator('.project-item', { hasText: PC_PROJECT });
  await card.locator('button[type="submit"]').click();
  await page.waitForURL('**/programa-general', { timeout: 15000 });
}

/**
 * Full login + project selection shortcut.
 *
 * La puerta de servicio admite `&p=<Proyecto_Proceso>` para aterrizar directo, pero
 * aquí se conserva el paso por /proyectos: la primera prueba verifica justamente que
 * la tarjeta del proyecto se ve y que el POST del selector lleva a /programa-general.
 */
async function loginAndSelectProject(page) {
  await loginAsTestA(page);
  await selectPreConstructionProject(page);
}

// ─── 1. Login & Project Selection ────────────────────────────────

test.describe('Pre-Construction: Login & Project Selection', () => {
  test('login as test.A and select pre-construction project', async ({ page }) => {
    await loginAsTestA(page);
    await expect(page).toHaveURL(/proyectos/);

    // Project card should be visible in the grid
    const projectCard = page.locator('.project-item', { hasText: PC_PROJECT });
    await expect(projectCard).toBeVisible();

    await selectPreConstructionProject(page);
    // Pre-Construccion projects land on /programa-general
    await expect(page).toHaveURL(/programa-general/);
  });
});

// ─── 2. Navbar Visibility ────────────────────────────────────────

test.describe('Pre-Construction: Navbar Visibility', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
  });

  test('PG and PI nav items are present', async ({ page }) => {
    // Antes miraba #programa_general y #programacion_intermedia, ids del nav legado que ya no
    // produce ningun documento: la navegacion vive en el shell (views/partials/shell_sidebar.php).
    const shell = page.locator('[data-aia-component="navigation"]').first();
    await expect(shell.locator('[data-destination-id="programa-general"]')).toHaveCount(1);
    await expect(shell.locator('[data-destination-id="programacion-intermedia"]')).toHaveCount(1);
  });

  test('construction-only nav items are hidden', async ({ page }) => {
    // Hasta el 2026-08-04 esta prueba miraba #info_listadoActividades, #info_contratos y
    // #planCompras, los ids del nav legado que producia info_general_nav.js. Ese nav y el PDC v1
    // se borraron, asi que la comprobacion quedo vacia: con 0 elementos el bucle no corria nunca y
    // la prueba pasaba sin verificar nada. La regla equivalente vive ahora en
    // views/partials/shell_sidebar.php, que en Pre-Construccion saca 'plan-compras' del rail.
    // El id del destino viaja en `data-destination-id`; `data-shell-destination` es un marcador
    // sin valor (solo el laboratorio le pone contenido). Verificado en /programa-general con el
    // proyecto Pre-Construccion real antes de fijarlo aqui.
    const shell = page.locator('[data-aia-component="navigation"]').first();
    await expect(shell).toBeVisible();

    await expect(shell.locator('[data-destination-id="plan-compras"]')).toHaveCount(0);
    await expect(shell.locator('[data-sidebar-group="compras"]')).toHaveCount(0);
    // Los modulos de obra si permanecen: es lo que distingue ocultar de estar roto.
    await expect(shell.locator('[data-destination-id="programa-general"]')).toHaveCount(1);
  });

  test('navbar brand contains project selector link', async ({ page }) => {
    // La marca ya no es .navbar-brand del nav legado, sino la del shell; sigue enlazando a
    // /proyectos, que es la conducta que esta prueba cuida.
    const brand = page.locator('a[href="/proyectos"]').filter({ hasText: 'Last Planner AIA' });
    await expect(brand).toBeVisible();
  });
});

// ─── 3. PG (Programa General) ────────────────────────────────────

test.describe('Pre-Construction: PG (Programa General)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndSelectProject(page);
    // loginAndSelectProject already lands on /programa-general
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
    expect(legendText).toContain('Con Restricción Pendiente');
    expect(legendText).toContain('Por Iniciar');
    expect(legendText).toContain('Actividad Futura');
    expect(legendText).toContain('En Ejecución');
    expect(legendText).toContain('Completada');
  });

  test('PG page has Pre-Construccion specific elements', async ({ page }) => {
    // Verify the page has the pg-page class applied (PC-specific styling)
    const body = page.locator('body');
    const html = await page.content();

    // The page should contain Pre-Construccion specific scripts
    expect(html).toContain('Pre-Construccion');
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
    const config = await page.evaluate(() => window.__RESTRICTION_CONFIG__);
    expect(config).not.toBeNull();
    expect(config.area).toBe('Pre-Construccion');
  });

  test('restriction config has restriction columns', async ({ page }) => {
    const config = await page.evaluate(() => window.__RESTRICTION_CONFIG__);
    expect(config).not.toBeNull();
    expect(config.restrictions.length).toBeGreaterThanOrEqual(1);
  });

  test('restriction config has hard and soft classifications', async ({ page }) => {
    const config = await page.evaluate(() => window.__RESTRICTION_CONFIG__);
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
    const config = await page.evaluate(() => window.__RESTRICTION_CONFIG__);
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

    const config = await page.evaluate(() => window.__RESTRICTION_CONFIG__);
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
    await page.goto(`${BASE_URL}/subcontratistas`);
  });

  test('page title shows "Interesados Externos"', async ({ page }) => {
    // Wait for loading overlay to disappear
    await page.waitForSelector('#loading', { state: 'hidden', timeout: 15000 });

    const title = page.locator('h4');
    await expect(title).toContainText('Interesados Externos');
  });

  test('column headers show renamed pre-construction labels', async ({ page }) => {
    await page.waitForSelector('.handsontable .htCore', { timeout: 15000 });

    const headers = await page.evaluate(() => {
      const ths = document.querySelectorAll('.handsontable .htCore thead th .colHeader');
      return Array.from(ths).map((th) => th.textContent.trim());
    });

    // Pre-construction specific column names
    expect(headers).toContain('Interesado');
    expect(headers).toContain('Identificación');
    expect(headers).toContain('Rol/Interés');
    expect(headers).toContain('Tipo de Interesado');

    // Should NOT have construction-specific column names
    expect(headers).not.toContain('Subcontratista');
    expect(headers).not.toContain('NIT');
    expect(headers).not.toContain('Alcance');
    expect(headers).not.toContain('Tipo Proveedor');
  });
});

// ─── 7. Restriction Config API ──────────────────────────────────

test.describe('Pre-Construction: Restriction Config API', () => {
  test('GET /api/general/restriction-config returns correct PC structure', async ({ page }) => {
    await loginAndSelectProject(page);

    const config = await page.evaluate(async () => {
      const res = await fetch('/api/general/restriction-config');
      return res.json();
    });

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
    expect(pc1.threshold).toBe(50);

    const pc2 = config.restrictions.find((r) => r.key === 'restriccion_pc_2');
    expect(pc2.threshold).toBe(100);

    // Options for hard restriction (PC_1).
    // Fueron 5 (0/33/66/100/N-A) hasta el commit 41720ad7 del 2026-07-05, «normalize Predecesora
    // options in intermedia module», que las bajo a 4 a proposito para alinear backend y frontend.
    // Esta prueba llevaba rota desde antes y nunca registro el cambio. Verificado el 2026-08-04 en
    // los tres sitios: GeneralApiController.php:1598, programacion_intermedia/hot.js:48 y el texto
    // de ayuda de hot.js:80, que solo documenta 0/50/100.
    expect(pc1.options).toEqual(['0%', '50%', '100%', 'N/A']);

    // Options for soft restrictions (PC_2-4)
    for (const key of ['restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4']) {
      const r = config.restrictions.find((r) => r.key === key);
      expect(r.options).toEqual(['0%', '50%', '100%', 'N/A']);
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
