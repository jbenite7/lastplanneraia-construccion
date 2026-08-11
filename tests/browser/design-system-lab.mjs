import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, logout, selectProject } from './support/session.mjs';

const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');
const ADMIN = { username: 'test.A', password: 'aia2026' };
const RESIDENT = { username: 'test.R', password: 'aia2026' };
const APPROVALS = JSON.parse(readFileSync(
  new URL('../../docs/design-system/family-approvals.json', import.meta.url),
  'utf8',
));
// El ledger es un histórico append-only: una familia puede acumular varias
// aprobaciones y solo la última queda vigente. `shell-navigation` acumula
// `adaptive-shell` (2026-07-12) y `sidebar-shell` (DS-026, 2026-07-22), que lo
// sustituye como shell desktop dejando el drawer adaptativo como compatibilidad.
// El laboratorio resuelve el candidato activo con esa misma regla de última
// entrada por familia (DesignSystemLabController::__invoke), así que el fixture
// se contrasta contra el candidato vigente, no contra cada entrada histórica.
const APPROVED_CANDIDATE_BY_FAMILY = new Map(
  APPROVALS.approvals.map(({ familyId, candidateId }) => [familyId, candidateId]),
);
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];

function contrastRatio(foreground, background) {
  const channels = (color) => (color.match(/[\d.]+/g) ?? []).slice(0, 3).map(Number);
  const luminance = (color) => {
    const [red, green, blue] = channels(color).map((value) => {
      const channel = value / 255;
      return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });
    return (0.2126 * red) + (0.7152 * green) + (0.0722 * blue);
  };
  const lighter = Math.max(luminance(foreground), luminance(background));
  const darker = Math.min(luminance(foreground), luminance(background));
  return (lighter + 0.05) / (darker + 0.05);
}

async function openAs(page, credentials) {
  await login(page, credentials);
  await selectProject(page, DA_PORTO);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });
}

test('administrator can open the laboratory before selecting a project', async ({ page }) => {
  await login(page, ADMIN);
  const response = await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });

  expect(response?.status()).toBe(200);
  await expect(page.locator('[data-family]')).toHaveCount(10);
  await logout(page);
});

async function selectFamily(page, family) {
  await page.locator(`[data-lab-family-link][data-family-target="${family}"]`).click();
  await expect(page.locator(`[data-family="${family}"]`)).toBeVisible();
  await expect(page.locator('[data-family]:visible')).toHaveCount(1);
}

test('family rail normalizes the initial URL and restores the selected family through history', async ({ page }) => {
  await login(page, ADMIN);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });
  await expect(page).toHaveURL(/\?family=foundations$/);
  await selectFamily(page, 'actions');
  await page.goBack({ waitUntil: 'domcontentloaded' });
  await expect(page).toHaveURL(/\?family=foundations$/);
  await expect(page.locator('[data-family="foundations"]')).toBeVisible();
  await expect(page.locator('[data-lab-family-link][data-family-target="foundations"]')).toHaveAttribute('aria-current', 'page');
});

test('laboratory first paint stays within the desktop performance budget', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await page.addInitScript(() => {
    window.__labPerformance = {
      cumulativeLayoutShift: 0,
      longTasks: [],
    };

    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (!entry.hadRecentInput) {
          window.__labPerformance.cumulativeLayoutShift += entry.value;
        }
      }
    }).observe({ type: 'layout-shift', buffered: true });

    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        window.__labPerformance.longTasks.push(entry.duration);
      }
    }).observe({ type: 'longtask', buffered: true });
  });

  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  const response = await page.goto(
    '/internal/design-system?family=bi-primitives',
    { waitUntil: 'domcontentloaded' },
  );
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(500);

  const metrics = await page.evaluate(() => ({
    cumulativeLayoutShift: window.__labPerformance.cumulativeLayoutShift,
    longTaskCount: window.__labPerformance.longTasks.length,
    maxLongTask: Math.max(0, ...window.__labPerformance.longTasks),
    totalLongTask: window.__labPerformance.longTasks.reduce((total, duration) => total + duration, 0),
    visibleFamilies: document.querySelectorAll('[data-family]:not([hidden])').length,
  }));

  test.info().annotations.push({
    type: 'performance',
    description: JSON.stringify(metrics),
  });

  expect(response?.status()).toBe(200);
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
  await expect(page.locator('[data-family="bi-primitives"]')).toBeVisible();
  expect(metrics.visibleFamilies).toBe(1);
  expect(metrics.cumulativeLayoutShift).toBeLessThanOrEqual(0.1);
  expect(metrics.maxLongTask).toBeLessThanOrEqual(250);
  expect(metrics.totalLongTask).toBeLessThanOrEqual(500);
});

test('admin laboratory is deterministic across themes and viewports', async ({ page }) => {
  await openAs(page, ADMIN);
  await expect(page.locator('[data-family]')).toHaveCount(10);
  await expect(page.locator('[data-family="foundations"]')).toBeVisible();
  await expect(page.locator('[data-family]:visible')).toHaveCount(1);

  for (const viewport of VIEWPORTS) {
    await page.setViewportSize(viewport);
    // F0/Task 8: dark es el unico tema, aplicado sin conmutacion. El bucle
    // sobrevive como marcador de "sobre cada tema gobernado" (hoy uno solo).
    for (const theme of ['dark']) {
      await expect(page.locator('html')).toHaveAttribute('data-aia-theme', theme);
      const overflow = await page.evaluate(() => (
        document.documentElement.scrollWidth - document.documentElement.clientWidth
      ));
      expect(overflow).toBeLessThanOrEqual(1);
    }
  }
});

test('defaults to dark and maps responsive density by viewport', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
  const foundations = page.locator('[data-family="foundations"]');
  await expect(foundations).toHaveAttribute('data-active-candidate', 'foundation-inventory-action-color');
  await expect(foundations).toHaveAttribute('data-family-status', 'candidate');
  await expect(foundations.locator('.ds-lab__family-head .aia-chip')).toHaveText('En revisión');
  await expect(page.locator('body')).toHaveAttribute('data-density', 'compact');
  await page.setViewportSize(VIEWPORTS[1]);
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('body')).toHaveAttribute('data-density', 'compact');
});

test('approved visual fixture resolves every family from the approval ledger', async ({ page }) => {
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  const response = await page.goto(
    '/internal/design-system?fixture=approved-family-v1',
    { waitUntil: 'domcontentloaded' },
  );

  expect(response?.status()).toBe(200);
  await expect(page.locator('[data-family]')).toHaveCount(APPROVED_CANDIDATE_BY_FAMILY.size);
  for (const [familyId, candidateId] of APPROVED_CANDIDATE_BY_FAMILY) {
    const family = page.locator(`[data-family="${familyId}"]`);
    await expect(family).toHaveAttribute('data-active-candidate', candidateId);
    await expect(family).toHaveAttribute('data-family-status', 'approved');
  }
});

test('state words, density and dialog behavior follow the family contract', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'states-feedback');
  await expect(page.locator('[data-family="states-feedback"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  const stateCandidates = page.locator('[data-state-candidate]');
  await expect(stateCandidates).toHaveCount(1);
  await expect(stateCandidates).toHaveAttribute('data-state-candidate', 'tinted-status');
  const stateContent = await stateCandidates.evaluateAll((elements) => elements.map((element) => (
    [...element.querySelectorAll('[data-state-text]')].map((item) => item.textContent.trim())
  )));
  expect(stateContent[0]).toEqual(['A tiempo', 'Por comprometer', 'Pendiente de aprobación del responsable', 'Bloqueado', 'Actividad guardada', 'No se pudo guardar']);
  await expect(stateCandidates.locator('[data-aia-component="progress"]')).toHaveCount(1);
  await expect(stateCandidates.locator('[data-aia-component="live-region"]')).toHaveCount(1);
  const longState = page.locator('.ds-state-set')
    .getByText('Pendiente de aprobación del responsable', { exact: true });
  const stateContract = await longState.evaluate((element) => ({
    height: element.getBoundingClientRect().height,
    overflowWrap: getComputedStyle(element).overflowWrap,
    wordBreak: getComputedStyle(element).wordBreak,
    hyphens: getComputedStyle(element).hyphens,
  }));
  expect(stateContract.height).toBeGreaterThan(28);
  expect(stateContract.overflowWrap).toBe('normal');
  expect(stateContract.wordBreak).toBe('normal');
  expect(stateContract.hyphens).toBe('none');

  await page.locator('[data-lab-density][value="compact"]').check();
  await expect(page.locator('body')).toHaveAttribute('data-density', 'compact');

  await selectFamily(page, 'overlays');
  await expect(page.locator('[data-family="overlays"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  const overlays = page.locator('[data-family="overlays"]');
  await expect(overlays.locator('[data-aia-component="dialog"]')).toHaveCount(1);
  await expect(overlays.locator('[data-aia-component="menu"]')).toHaveCount(1);
  await expect(overlays.locator('[data-aia-component="popover"]')).toHaveCount(1);
  await expect.poll(() => page.evaluate(() => typeof window.AiaComponents?.init)).toBe('function');
  await page.evaluate(() => window.AiaComponents.init(document));
  await page.evaluate(() => window.AiaComponents.init(document));
  const menuTrigger = overlays.locator('[data-aia-menu-trigger]');
  await menuTrigger.click();
  await expect(menuTrigger).toHaveAttribute('aria-expanded', 'true');
  await menuTrigger.press('Escape');
  await expect(menuTrigger).toHaveAttribute('aria-expanded', 'false');
  await expect(menuTrigger).toBeFocused();
  const popoverTrigger = overlays.locator('[data-aia-popover-trigger]');
  await popoverTrigger.click();
  await expect(popoverTrigger).toHaveAttribute('aria-expanded', 'true');
  await popoverTrigger.press('Escape');
  await expect(popoverTrigger).toBeFocused();
  const open = overlays.locator('[data-aia-dialog-open]');
  const dialog = overlays.locator('[data-aia-dialog]');
  await expect(dialog).toHaveAttribute('aria-labelledby', 'lab-dialog-title');
  await expect(dialog).toHaveAttribute('aria-describedby', 'lab-dialog-description');
  await open.click();
  await expect(dialog).toHaveAttribute('open', '');
  await expect(dialog).toHaveAttribute('data-overlay-presentation', 'drawer');
  const drawerGeometry = await dialog.evaluate((element) => ({
    bottom: innerHeight - element.getBoundingClientRect().bottom,
    width: element.getBoundingClientRect().width,
  }));
  expect(Math.abs(drawerGeometry.bottom)).toBeLessThanOrEqual(1);
  expect(drawerGeometry.width).toBeGreaterThanOrEqual(350);
  await dialog.press('Escape');
  await expect(dialog).not.toHaveAttribute('open', '');
  await expect(open).toBeFocused();
  await page.setViewportSize(VIEWPORTS[1]);
  await open.click();
  await expect(dialog).toHaveAttribute('data-overlay-presentation', 'modal');
  await dialog.press('Escape');
  await expect(open).toBeFocused();
});

test('severity and urgency blocks keep distinct semantic backgrounds', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'states-feedback');
  const family = page.locator('[data-family="states-feedback"]');
  const semanticColors = await family.locator('.ds-state-semantics__level').evaluateAll((elements) => (
    elements.map((element) => getComputedStyle(element).backgroundColor)
  ));
  expect(new Set(semanticColors).size).toBe(4);

  const mappedColors = await family.locator('.ds-state-module__state').evaluateAll((elements) => (
    elements.map((element) => ({
      key: `${element.dataset.aiaSeverity}:${element.dataset.aiaUrgency}`,
      hue: element.dataset.aiaHue || null,
      background: getComputedStyle(element).backgroundColor,
    }))
  ));
  // El fondo crítico de referencia se lee del propio mapa de gravedad, que no
  // lleva matiz: así el test compara contra lo que el sistema declara crítico
  // en vez de contra un color escrito a mano aquí, que se quedaría atrás en
  // cuanto el token cambie.
  const criticalBackground = await family
    .locator('.ds-state-semantics__level[data-aia-severity="high"][data-aia-urgency="now"]')
    .evaluate((element) => getComputedStyle(element).backgroundColor);
  // Esta aserción exigía que dos estados con la misma clave `severity:urgency`
  // tuvieran el MISMO fondo, y contradecía al eje de matiz: `data-aia-hue`
  // existe justamente para que dos estados del mismo nivel se distingan
  // (states-feedback.css:88-100 lo explica con el caso de /pdc). Se actualiza a
  // la regla vigente, y la nueva comprueba MÁS que la vieja, no menos:
  //   (1) dos estados del mismo nivel con matiz distinto NO comparten fondo
  //       —lo contrario de lo que se pedía antes, y es el propósito del eje—;
  //   (2) ningún estado crítico pierde su fondo crítico por llevar matiz, que
  //       es la excepción decidida el 2026-08-11 y lo único que no admite
  //       ambigüedad.
  // La vieja no comprobaba (2) en absoluto.
  const porClave = new Map();
  for (const { key, hue, background } of mappedColors) {
    if (!porClave.has(key)) porClave.set(key, []);
    porClave.get(key).push({ hue, background });
  }
  for (const [key, estados] of porClave) {
    // El nivel crítico queda fuera de (1) a propósito: es la excepción del
    // 2026-08-11, y ahí el fondo es uniforme por diseño. Su comprobación es (2),
    // abajo, que es más estricta — no admite ni una desviación.
    if (key === 'high:now') continue;
    const conMatiz = estados.filter(({ hue }) => hue);
    const matices = new Set(conMatiz.map(({ hue }) => hue));
    if (matices.size > 1) {
      const fondos = new Set(conMatiz.map(({ background }) => background));
      expect(fondos.size, `«${key}»: ${matices.size} matices distintos comparten fondo`).toBeGreaterThan(1);
    }
  }
  const criticos = mappedColors.filter(({ key }) => key === 'high:now');
  for (const { hue, background } of criticos) {
    expect(background, `un estado crítico con matiz «${hue}» perdió su fondo crítico`).toBe(criticalBackground);
  }
  expect(new Set(mappedColors.map(({ background }) => background)).size).toBeGreaterThanOrEqual(3);
});

test('state headings and canonical spinner remain legible across desktop dark viewports', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await page.goto(
    '/internal/design-system?fixture=approved-family-v1&family=states-feedback',
    { waitUntil: 'domcontentloaded' },
  );
  const family = page.locator('[data-family="states-feedback"]');
  const heading = family.locator('#state-semantics-title');
  const loading = family.locator('[data-ui-group="loading-spinner"][role="status"]');

  for (const viewport of VIEWPORTS) {
    await page.setViewportSize(viewport);
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
    const colors = await heading.evaluate((element) => {
      let background = element.parentElement;
      while (background && getComputedStyle(background).backgroundColor === 'rgba(0, 0, 0, 0)') {
        background = background.parentElement;
      }
      return {
        foreground: getComputedStyle(element).color,
        background: getComputedStyle(background ?? document.body).backgroundColor,
      };
    });
    const spinner = await loading.locator('.aia-spinner').evaluate((element) => {
      const rect = element.getBoundingClientRect();
      return { width: rect.width, height: rect.height };
    });

    expect(contrastRatio(colors.foreground, colors.background)).toBeGreaterThanOrEqual(4.5);
    expect(spinner).toEqual({ width: 24, height: 24 });
    await expect(loading).toHaveAttribute('role', 'status');
    await expect(loading).toHaveAttribute('aria-live', 'polite');
    await expect(loading).toContainText('Carga indeterminada');
  }
});

test('density selector shows and applies the touch and compact contracts', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  const swatch = page.locator('[data-family="foundations"] .ds-swatch').first();
  await page.locator('[data-lab-density][value="touch"]').check();
  const touchHeight = await swatch.evaluate((element) => element.getBoundingClientRect().height);
  await expect(page.locator('[data-density-sample="touch"]')).toContainText('44 px visual y operable');
  await expect(page.locator('[data-density-sample="compact"]')).toContainText('36 px visual');
  await page.locator('[data-lab-density][value="compact"]').check();
  const compactHeight = await swatch.evaluate((element) => element.getBoundingClientRect().height);
  expect(compactHeight).toBeLessThan(touchHeight);
  await expect(page.locator('[data-density-sample="compact"]')).toHaveAttribute('data-selected', 'true');
  const compactAction = page.locator('[data-density-sample="compact"] .aia-btn');
  const actionSize = await compactAction.evaluate((element) => element.getBoundingClientRect());
  expect(actionSize.height).toBeGreaterThanOrEqual(44);
  expect(actionSize.width).toBeGreaterThanOrEqual(44);
});

test('dark appearance applies distinct accessible brand variants', async ({ page }) => {
  await openAs(page, ADMIN);
  const swatches = page.locator('[data-family="foundations"] .ds-swatch');
  const dark = await swatches.evaluateAll((elements) => (
    elements.map((element) => getComputedStyle(element).backgroundColor)
  ));
  expect(dark).toEqual(['rgb(108, 144, 119)', 'rgb(197, 114, 71)', 'rgb(44, 170, 159)', 'rgb(135, 124, 209)']);
  expect(new Set(dark).size).toBe(dark.length);
  await expect(swatches.first()).toHaveCSS('color', 'rgb(20, 28, 24)');
});

test('sidebar shell exposes grouped navigation and an accessible collapse state', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'shell-navigation');
  const shell = page.locator('[data-shell-pattern="sidebar"]');
  await expect(shell).toHaveCount(1);
  await expect(shell).toHaveAttribute('data-aia-component', 'navigation');
  // 11 destinos = Información 6 + Obra 4 + Compras 1, el espejo del shell real. Compras bajó de
  // 3 a 1 el 2026-08-04: «Familias de Actividades» y «Paquetes de Contratación» eran el PDC v1.
  await expect(shell.locator('[data-shell-destination]')).toHaveCount(11);
  await expect(shell.locator('[aria-current="page"]')).toHaveCount(1);
  await expect(shell.locator('[data-sidebar-group="obra"]')).toContainText('Programación Semanal');
  await expect(shell.locator('[data-sidebar-group="compras"]')).toContainText('Plan de Compras');
  await expect(shell.locator('[data-sidebar-group="information"]')).not.toContainText('Integración');
  // El rail arranca colapsado por contrato (fixture `initialState => 'collapsed'`,
  // igual que el shell real). El toggle alterna en ambos sentidos y Escape es la
  // salida accesible del rail de solo iconos: expande cuando está colapsado y no
  // hace nada cuando ya está expandido (sidebar_navigation.js).
  const toggle = shell.locator('[data-sidebar-toggle]');
  await expect(shell).toHaveAttribute('data-sidebar-state', 'collapsed');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await toggle.click();
  await expect(shell).toHaveAttribute('data-sidebar-state', 'expanded');
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await toggle.click();
  await expect(shell).toHaveAttribute('data-sidebar-state', 'collapsed');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await toggle.press('Escape');
  await expect(shell).toHaveAttribute('data-sidebar-state', 'expanded');
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await expect(toggle).toBeFocused();
});

test('sidebar shell keeps desktop width, context and theme-visible brand mark', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[1]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'shell-navigation');
  const navigation = page.locator('[data-family="shell-navigation"] [data-shell-pattern="sidebar"]');
  const logo = navigation.locator('.aia-sidebar__brand img');

  const dark = await navigation.evaluate((element) => ({
    background: getComputedStyle(element).backgroundColor,
    width: Number.parseFloat(getComputedStyle(element).width),
    collapsedWidth: (() => {
      const probe = document.createElement('div');
      probe.style.width = 'var(--ds-sidebar-width-collapsed)';
      document.body.append(probe);
      const value = probe.getBoundingClientRect().width;
      probe.remove();
      return value;
    })(),
    expandedWidth: (() => {
      const probe = document.createElement('div');
      probe.style.width = 'var(--ds-sidebar-width-expanded)';
      document.body.append(probe);
      const value = probe.getBoundingClientRect().width;
      probe.remove();
      return value;
    })(),
  }));
  // El ancho lo manda el token del estado renderizado, no un valor suelto: en
  // desktop el rail arranca colapsado, así que se contrasta contra
  // --ds-sidebar-width-collapsed y se comprueba que el par de tokens desktop
  // existe y es coherente. La geometría del ciclo colapsar/expandir en ambos
  // viewports la cubre tests/browser/design-system-lab-sidebar.mjs.
  await expect(navigation).toHaveAttribute('data-sidebar-state', 'collapsed');
  expect(dark.width).toBeGreaterThanOrEqual(dark.collapsedWidth - 1);
  expect(dark.width).toBeLessThanOrEqual(dark.collapsedWidth + 1);
  expect(dark.collapsedWidth).toBeGreaterThan(0);
  expect(dark.expandedWidth).toBeGreaterThan(dark.collapsedWidth);
  expect(dark.background).not.toBe('rgba(0, 0, 0, 0)');
  await expect(logo).not.toHaveCSS('filter', 'none');

  for (const state of ['loading', 'empty', 'error', 'default']) {
    await navigation.locator('xpath=..').locator(`[data-sidebar-state-action="${state}"]`).click();
    if (state === 'default') {
      await expect(navigation.locator('.aia-sidebar__notification-state')).toContainText('Avisos');
    }
  }
});

test('approved page structure freezes the integrated header with bounded content', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'page-structure');
  const candidates = page.locator('[data-page-structure-candidate]');
  await expect(candidates).toHaveCount(1);
  await expect(candidates).toHaveAttribute('data-page-structure-candidate', 'inline-header');
  const pageHeader = candidates.locator('[data-aia-component="page-header"]');
  await expect(pageHeader).toHaveCount(1);
  await expect(pageHeader).toHaveAttribute('aria-labelledby', 'lab-page-title');
  await expect(pageHeader.getByRole('navigation', { name: 'Miga de pan' })).toHaveCount(1);
  const content = await candidates.evaluateAll((elements) => elements.map((element) => ({
    actions: [...element.querySelectorAll('[data-page-action]')].map((item) => item.textContent.trim()),
    sections: [...element.querySelectorAll('[data-page-section]')].map((item) => item.textContent.trim()),
  })));
  expect(content[0].actions).toEqual(['Filtrar', 'Crear actividad']);
  expect(content[0].sections).toHaveLength(2);
  const sectionInsets = await candidates.locator('[data-page-section]').evaluateAll((sections) => (
    sections.map((section) => {
      const child = section.querySelector('strong');
      return {
        paddingInlineStart: parseFloat(getComputedStyle(section).paddingInlineStart),
        contentInset: child.getBoundingClientRect().left - section.getBoundingClientRect().left,
      };
    })
  ));
  expect(sectionInsets.every(({ paddingInlineStart }) => paddingInlineStart >= 12)).toBe(true);
  expect(sectionInsets.every(({ contentInset }) => contentInset >= 12)).toBe(true);
  const titleColors = await candidates.locator('h4').evaluateAll((elements) => (
    elements.map((element) => getComputedStyle(element).color)
  ));
  expect(titleColors).toEqual(['rgb(247, 250, 248)']);
});

test('action theme candidate keeps the integrated hierarchy and operable states', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'actions');
  const family = page.locator('[data-family="actions"]');
  await expect(family).toHaveAttribute('data-active-candidate', 'theme-adaptive-primary');
  await expect(family).toHaveAttribute('data-family-status', 'candidate');
  await expect(family.locator('.ds-lab__family-head .aia-chip')).toHaveText('En revisión');
  const candidates = page.locator('[data-action-candidate]');
  await expect(candidates).toHaveCount(1);
  await expect(candidates).toHaveAttribute('data-action-candidate', 'theme-adaptive-primary');
  await expect(candidates).toHaveAttribute('data-action-pattern', 'solid-outline');
  await expect(candidates.locator('h3')).toHaveText('Primaria corporativa por tema');
  await expect(candidates.locator('[data-aia-component="action-group"]')).toHaveCount(1);
  await expect(candidates.locator('[data-aia-component="icon"]')).toHaveCount(4);
  const contracts = await candidates.evaluateAll((elements) => elements.map((element) => ({
    labels: [...element.querySelectorAll('[data-aia-component="action-group"] button')].map((button) => button.textContent.trim()),
    disabled: [...element.querySelectorAll('[data-aia-component="action-group"] button')].map((button) => button.disabled),
    targets: [...element.querySelectorAll('button')].map((button) => button.getBoundingClientRect().height),
  })));
  expect(contracts[0].disabled).toEqual([false, false, true, true]);
  expect(contracts[0].targets.every((height) => height >= 44)).toBe(true);
  await expect(candidates.locator('.aia-btn--critical, .aia-btn--icon, .aia-btn--floating')).toHaveCount(3);
});

test('icon-only actions use a legible canonical glyph size', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'actions');
  const iconButton = page.getByRole('button', { name: 'Filtrar', exact: true });
  const size = await iconButton.evaluate((element) => Number.parseFloat(getComputedStyle(element).fontSize));
  expect(size).toBeGreaterThanOrEqual(20);
});

test('approved visible filters expose the canonical fields and actions', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'forms-filters');
  await expect(page.locator('[data-family="forms-filters"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  const candidates = page.locator('[data-filter-candidate]');
  await expect(candidates).toHaveCount(1);
  await expect(candidates).toHaveAttribute('data-filter-candidate', 'inline-fields');
  await expect(candidates.locator('[data-aia-component="filter-form"]')).toHaveCount(1);
  await expect(candidates.locator('[data-aia-component="search"]')).toHaveCount(1);
  await expect(candidates.locator('[data-aia-component="pagination"]')).toHaveCount(1);
  const contracts = await candidates.evaluateAll((elements) => elements.map((element) => ({
    fields: [...element.querySelectorAll('[data-filter-field]')].map((field) => field.dataset.filterField),
    actions: [...element.querySelectorAll('[data-aia-component="action-group"] button')].map((action) => action.textContent.trim()),
  })));
  expect(contracts[0].fields).toEqual(['search', 'responsible', 'status']);
  expect(contracts[0].actions).toEqual(['Aplicar filtros', 'Limpiar']);
  await expect(candidates.locator('details')).toHaveCount(0);
});

test('form controls, Select2 multi and active pagination preserve readable insets', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'forms-filters');
  const family = page.locator('[data-family="forms-filters"]');
  const controls = family.locator('.aia-input:not([type="file"]), .aia-select, .aia-textarea');
  const insets = await controls.evaluateAll((elements) => elements.map((element) => ({
    block: Number.parseFloat(getComputedStyle(element).paddingBlockStart),
    inline: Number.parseFloat(getComputedStyle(element).paddingInlineStart),
  })));
  expect(insets.length).toBeGreaterThanOrEqual(7);
  expect(insets.every(({ block }) => block >= 12)).toBe(true);
  expect(insets.every(({ inline }) => inline >= 16)).toBe(true);

  const multi = family.getByRole('combobox', { name: 'Responsables de revisión' });
  const multiInset = await multi.evaluate((element) => Number.parseFloat(getComputedStyle(element).paddingInlineStart));
  expect(multiInset).toBeGreaterThanOrEqual(12);

  const current = family.locator('.aia-pagination [aria-current="page"]');
  const paginationColors = await current.evaluate((element) => ({
    foreground: getComputedStyle(element).color,
    background: getComputedStyle(element).backgroundColor,
  }));
  expect(contrastRatio(paginationColors.foreground, paginationColors.background)).toBeGreaterThanOrEqual(4.5);
});

test('laboratory scroll and file alignment hold across desktop dark viewports', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await page.goto(
    '/internal/design-system?fixture=approved-family-v1&family=forms-filters',
    { waitUntil: 'domcontentloaded' },
  );
  await selectFamily(page, 'forms-filters');
  const family = page.locator('[data-family="forms-filters"]');

  for (const viewport of VIEWPORTS) {
    await page.setViewportSize(viewport);
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');
    const geometry = await family.evaluate((element) => {
      const file = element.querySelector('.aia-input[type="file"]');
      const code = element.querySelector('.aia-input[aria-describedby="code-help"]');
      const helper = element.querySelector('#code-help');
      const fileField = file.closest('.aia-field');
      const codeField = code.closest('.aia-field');
      const fileRect = file.getBoundingClientRect();
      const codeRect = code.getBoundingClientRect();
      const helperRect = helper.getBoundingClientRect();
      const fileFieldRect = fileField.getBoundingClientRect();
      const codeFieldRect = codeField.getBoundingClientRect();
      const fileCenter = (fileRect.top + fileRect.bottom) / 2;
      const codeCenter = (codeRect.top + codeRect.bottom) / 2;
      const fieldsShareRow = Math.abs(fileFieldRect.top - codeFieldRect.top) <= 1;
      const peerCenter = fieldsShareRow
        ? (codeRect.top + helperRect.bottom) / 2
        : codeCenter + fileFieldRect.top - codeFieldRect.top;
      const families = document.querySelector('.ds-lab__families');
      const previousMinBlockSize = families.style.minBlockSize;
      families.style.minBlockSize = `${innerHeight + 400}px`;
      families.getBoundingClientRect();
      const scrollTargets = [document.body, document.documentElement];
      scrollTargets.forEach((target) => { target.scrollTop = 0; });
      const scrollTopBefore = Math.max(...scrollTargets.map((target) => target.scrollTop));
      scrollTargets.forEach((target) => { target.scrollTop = 240; });
      const scrollTopAfter = Math.max(...scrollTargets.map((target) => target.scrollTop));
      families.style.minBlockSize = previousMinBlockSize;
      scrollTargets.forEach((target) => { target.scrollTop = 0; });

      return {
        centerDelta: Math.abs(fileCenter - peerCenter),
        fileHeight: fileRect.height,
        codeHeight: codeRect.height,
        overflowY: getComputedStyle(document.body).overflowY,
        scrollTopBefore,
        scrollTopAfter,
      };
    });

    expect(geometry.centerDelta).toBeLessThanOrEqual(1);
    expect(Math.abs(geometry.fileHeight - geometry.codeHeight)).toBeLessThanOrEqual(1);
    // El body tiene que quedarse en `visible`. Con cualquier otro valor se
    // vuelve contenedor de scroll y, como nunca scrollea de verdad
    // (scrollHeight === clientHeight), deja inerte el `position: sticky` del
    // header y del rail. Esto asertaba 'auto', que era justamente el defecto:
    // no aportaba scroll —el que scrollea es documentElement, y eso lo cubre
    // la asercion siguiente— y solo servia para congelar el bug.
    expect(geometry.overflowY).toBe('visible');
    expect(geometry.scrollTopAfter).toBeGreaterThan(geometry.scrollTopBefore);
  }
});

test('data display switches one record set between cards and table', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'data-display');
  await expect(page.locator('[data-family="data-display"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  await expect(page.locator('[data-aia-component="data-display"]')).toHaveCount(1);
  const table = page.locator('[data-display-mode="table"]');
  const cards = page.locator('[data-display-mode="cards"]');
  await expect(table).toBeHidden();
  await expect(cards).toBeVisible();
  const records = await page.locator('[data-display-mode]').evaluateAll((elements) => elements.map((element) => (
    [...element.querySelectorAll('[data-record-id]')].map((record) => record.dataset.recordId)
  )));
  expect(records[0]).toEqual(records[1]);
  await page.setViewportSize(VIEWPORTS[1]);
  await expect(table).toBeVisible();
  await expect(cards).toBeHidden();
});

test('vendor fixtures use canonical adapters without local inline styles', async ({ page }) => {
  await openAs(page, ADMIN);
  await selectFamily(page, 'vendor-adapters');
  await expect(page.locator('[data-family="vendor-adapters"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  const fixtures = page.locator('[data-vendor-fixture]');
  await expect(fixtures).toHaveCount(3);
  const contracts = await fixtures.evaluateAll((elements) => elements.map((element) => ({
    vendor: element.dataset.vendorFixture,
    adapter: element.dataset.adapter,
    inlineStyles: element.querySelectorAll('[style]').length,
  })));
  expect(contracts).toEqual([
    { vendor: 'handsontable', adapter: 'canonical', inlineStyles: 0 },
    { vendor: 'select2', adapter: 'canonical', inlineStyles: 0 },
    { vendor: 'sweetalert2', adapter: 'canonical', inlineStyles: 0 },
  ]);
});

test('Select2 single selection preserves the canonical lateral inset', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'vendor-adapters');

  const selection = page.locator('[data-vendor-fixture="select2"] .select2-selection--single');
  const rendered = selection.locator('.select2-selection__rendered');
  const inset = await rendered.evaluate((element) => {
    const selectionRect = element.closest('.select2-selection').getBoundingClientRect();
    const text = element.firstChild;
    const range = document.createRange();
    range.setStart(text, 0);
    range.setEnd(text, 1);
    return {
      computedPadding: Number.parseFloat(getComputedStyle(element).paddingInlineStart),
      contentInset: range.getBoundingClientRect().left - selectionRect.left,
    };
  });

  expect(inset.computedPadding).toBeGreaterThanOrEqual(16);
  expect(inset.contentInset).toBeGreaterThanOrEqual(16);
});

test('SweetAlert2 title and action remain legible in dark mode', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'vendor-adapters');
  const popup = page.locator('[data-vendor-fixture="sweetalert2"] .aia-glass-popup');
  const titleColors = await popup.locator('.swal2-title').evaluate((element) => ({
    foreground: getComputedStyle(element).color,
    background: getComputedStyle(element.closest('.aia-glass-popup')).backgroundColor,
  }));
  expect(contrastRatio(titleColors.foreground, titleColors.background)).toBeGreaterThanOrEqual(4.5);
  await expect(popup.locator('.aia-glass-confirm-btn')).toHaveCSS(
    'background-color',
    'rgb(108, 144, 119)',
  );
});

test('BI figure exposes equivalent visual and tabular data', async ({ page }) => {
  await page.setViewportSize(VIEWPORTS[0]);
  await openAs(page, ADMIN);
  await selectFamily(page, 'bi-primitives');
  await expect(page.locator('[data-family="bi-primitives"] .ds-lab__family-head .aia-chip')).toHaveText('Aprobado');
  const figures = page.locator('[data-bi-figure]');
  await expect(figures).toHaveCount(2);
  await expect(page.locator('[data-bi-candidate] [data-aia-component="popover"]')).toHaveCount(1);
  const contracts = await figures.evaluateAll((elements) => elements.map((element) => ({
    visual: [...element.querySelectorAll('[data-bi-point]')].map((point) => point.dataset.biPoint),
    tabular: [...element.querySelectorAll('[data-bi-row]')].flatMap((row) => row.dataset.biRow.split(',')),
    inlineStyles: element.querySelectorAll('[style]').length,
    labelled: Boolean(element.getAttribute('aria-labelledby')),
    described: Boolean(element.getAttribute('aria-describedby')),
  })));
  for (const contract of contracts) {
    expect(contract.visual).toEqual(contract.tabular);
    expect(contract.inlineStyles).toBe(0);
    expect(contract.labelled && contract.described).toBe(true);
  }
  await expect(page.locator('.aia-bi-gauge__meter')).toHaveCount(1);
  const curveFigure = page.getByRole('figure', { name: 'Curva S de ejecución' });
  await expect(curveFigure).toHaveCount(1);
  await expect(page.locator('.aia-bi-radar')).toHaveCount(1);
  const curveContract = await curveFigure.locator('.aia-bi__plot').evaluate((plot) => {
    const plan = plot.querySelector('.aia-bi__line--plan');
    const executed = plot.querySelector('.aia-bi__line--executed');
    const planPoint = plot.querySelector('.aia-bi__point--plan');
    const executedPoint = plot.querySelector('.aia-bi__point--executed');
    return {
      planColor: getComputedStyle(plan).stroke,
      executedColor: getComputedStyle(executed).stroke,
      planWidth: getComputedStyle(plan).strokeWidth,
      executedWidth: getComputedStyle(executed).strokeWidth,
      planPointFill: getComputedStyle(planPoint).fill,
      executedPointFill: getComputedStyle(executedPoint).fill,
    };
  });
  expect(curveContract.planColor).not.toBe(curveContract.executedColor);
  expect(Number.parseFloat(curveContract.planWidth)).toBeGreaterThanOrEqual(1.25);
  expect(Number.parseFloat(curveContract.executedWidth)).toBeGreaterThanOrEqual(1.25);
  expect(curveContract.planPointFill).toBe(curveContract.planColor);
  expect(curveContract.executedPointFill).toBe(curveContract.executedColor);

  await page.setViewportSize(VIEWPORTS[0]);
  const radarLayout = await page.locator('.aia-bi-radar').evaluate((radar) => {
    const radarRect = radar.getBoundingClientRect();
    const figureRect = radar.closest('figure').getBoundingClientRect();
    return Math.abs(
      (radarRect.left + (radarRect.width / 2))
      - (figureRect.left + (figureRect.width / 2)),
    );
  });
  expect(radarLayout).toBeLessThanOrEqual(1);
  const visualContract = await page.locator('.ds-bi-gallery').evaluate((gallery) => ({
    guidancePadding: getComputedStyle(gallery.querySelector('.aia-bi__guidance')).paddingInlineStart,
    gridOpacity: getComputedStyle(gallery.querySelector('.aia-bi__grid')).opacity,
    donutAspect: getComputedStyle(gallery.querySelector('.aia-bi-gauge__meter')).aspectRatio,
  }));
  expect(visualContract).toEqual({ guidancePadding: '16px', gridOpacity: '0.3', donutAspect: '1 / 1' });
});

test('resident receives a forbidden response from the laboratory', async ({ page }) => {
  await openAs(page, RESIDENT);
  await expect(page.locator('body')).toContainText('403 Forbidden');
  await logout(page);
});
