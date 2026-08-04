import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];
const FAMILIES = [
  'foundations', 'shell-navigation', 'page-structure', 'actions',
  'forms-filters', 'states-feedback', 'data-display', 'overlays',
  'vendor-adapters', 'bi-primitives',
];

// Excepción registrada en DESIGN.md §5 bis («Excepción al mínimo de 44 px»):
// en la familia de tablas densas desktop —operadas con ratón, sin equivalente
// móvil por contrato de AGENTS.md— el suelo es el de WCAG 2.2 SC 2.5.8 (AA),
// 24x24px, no los 44 del resto del sistema. No es un aflojamiento de la vara:
// es la vara que ya estaba escrita, que este test aplicaba plana a todas las
// familias. Se acota al gatillo canónico de filtro de columna (su skin único
// vive en components/table-filter-trigger.css, y `.changeType` es el nombre
// que Handsontable impone en el DOM que genera); cualquier otro objetivo, aquí
// dentro incluido, sigue midiéndose contra 44. Si la superficie se abriera a
// táctil, la excepción caduca y esta lista se vacía.
const DENSE_TABLE_TARGETS = '.aia-table-filter-trigger, .changeType';
const DENSE_TABLE_MIN = 24;
const DEFAULT_MIN = 44;

async function readLayoutContract(page, scopeSelector) {
  return page.evaluate(({ scope, denseSelector, denseMin, defaultMin }) => {
    const root = document.documentElement;
    const panel = document.querySelector(scope);
    const textSelectors = 'h1,h2,h3,h4,h5,h6,p,label,button,.aia-chip,[data-state-text]';
    const textViolations = [...panel.querySelectorAll(textSelectors)].flatMap((element) => {
      const style = getComputedStyle(element);
      const invalid = style.wordBreak === 'break-all'
        || style.overflowWrap === 'anywhere' || style.hyphens === 'auto';
      return invalid ? [{ tag: element.tagName, text: element.textContent.trim().slice(0, 80) }] : [];
    });
    const targetSelectors = [
      'a[href]', 'button', 'select', 'textarea', 'summary',
      'input:not([type="hidden"])', '[role="button"]', '[role="option"]',
      '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const targetViolations = [...panel.querySelectorAll(targetSelectors)].flatMap((element) => {
      const style = getComputedStyle(element);
      if (style.display === 'none' || style.visibility === 'hidden'
        || element.closest('[hidden]') || element.getClientRects().length === 0) return [];
      const target = ['radio', 'checkbox'].includes(element.getAttribute('type'))
        ? element.closest('label') || element
        : element;
      const box = target.getBoundingClientRect();
      const min = target.matches(denseSelector) ? denseMin : defaultMin;
      return box.width + 0.01 < min || box.height + 0.01 < min
        ? [{
          label: element.getAttribute('aria-label') || element.textContent.trim().slice(0, 80),
          width: box.width,
          height: box.height,
          min,
        }]
        : [];
    });
    return {
      overflow: root.scrollWidth - root.clientWidth,
      textViolations,
      targetViolations,
    };
  }, {
    scope: scopeSelector,
    denseSelector: DENSE_TABLE_TARGETS,
    denseMin: DENSE_TABLE_MIN,
    defaultMin: DEFAULT_MIN,
  });
}

for (const viewport of VIEWPORTS) {
  test(`all laboratory families keep desktop layout and target contracts at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('html')).toHaveAttribute('data-aia-theme', 'dark');

    for (const family of FAMILIES) {
      await page.locator(`[data-lab-family-link][data-family-target="${family}"]`).click();
      const scope = `[data-family="${family}"]`;
      await expect(page.locator(scope)).toBeVisible();
      const contract = await readLayoutContract(page, scope);
      expect(contract.overflow, `${family}: horizontal overflow`).toBeLessThanOrEqual(1);
      expect(contract.textViolations, `${family}: fragmented text`).toEqual([]);
      expect(contract.targetViolations, `${family}: targets below their minimum (44px; 24px en tablas densas, DESIGN.md §5 bis)`).toEqual([]);
    }
  });

  test(`the sticky family rail stays below the 97px laboratory header at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport);
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    await page.goto('/internal/design-system?family=vendor-adapters', { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => window.scrollTo(0, 640));
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(0);

    const geometry = await page.evaluate(() => {
      const header = document.querySelector('.ds-lab__header').getBoundingClientRect();
      const rail = document.querySelector('.ds-lab__rail-wrap').getBoundingClientRect();
      return {
        headerTop: header.top,
        headerBottom: header.bottom,
        railTop: rail.top,
        overlap: Math.max(0, header.bottom - rail.top),
      };
    });

    expect(geometry.headerTop, 'the global header must remain fully sticky').toBe(0);
    expect(geometry.overlap, `rail top ${geometry.railTop} overlaps header bottom ${geometry.headerBottom}`).toBe(0);
  });
}
