// Gate del CONTRATO DE TABLA — superficie de RUNTIME.
//
// La superficie estática la cubre `scripts/design-system-table-contract.mjs`, que lee archivos y
// comprueba que los tokens existan y estén bien formados. Aquí se mide lo que leer archivos no
// puede ver, y que es justo lo que el criterio de cierre del goal exigía:
//
//   1. Que los `--ds-table-*` RESUELVAN en el motor, con la tabla renderizada y CON FILAS. Un
//      token puede existir en `tokens.css` y llegar vacío a la celda si la cadena de `var()` se
//      rompe o una capa lo pisa.
//   2. Que cada peldaño de `--ds-cell-state-*` cumpla AA sobre su PROPIO fondo. Desde el
//      2026-08-03 la escala deriva de `--ds-color-state-*` y un peldaño se calcula con
//      `color-mix()`: su valor final no existe en ningún archivo, solo en el navegador.
//
// El laboratorio es la superficie elegida a propósito: carga el design system completo, trae una
// tabla con filas y es determinista. `/programacion-semanal` queda descartada porque abrirla
// dispara `save` y `auto-program` — una verificación pensada como «solo mirar» escribiría en la
// base.
import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, logout, selectProject } from './support/session.mjs';

const DA_PORTO = PROJECTS.find((project) => project.name === 'Da Porto');
const ADMIN = { username: 'test.A', password: 'aia2026' };

// Viewport canónico de validación de este repositorio (AGENTS.md): desktop 1180×820, dark.
const VIEWPORT = { width: 1180, height: 820 };

const TABLE_TOKENS = [
  '--ds-table-row-h',
  '--ds-table-cell-pad-x',
  '--ds-table-cell-pad-y',
  '--ds-table-header-bg',
  '--ds-table-header-fg',
  '--ds-table-border',
  '--ds-table-zebra',
  '--ds-table-row-hover',
  '--ds-table-row-selected',
  '--ds-table-cell-focus',
  '--ds-table-empty-bg',
  '--ds-table-empty-fg',
];

const CELL_STATES = ['neutral', 'ok', 'atencion', 'riesgo', 'critico', 'bloqueado', 'sin-datos'];

const AA_MIN = 4.5;

/** Contraste WCAG entre dos colores ya reducidos a canales sRGB 0-255. */
function contrastRatio(foreground, background) {
  const luminance = ([red, green, blue]) => {
    const [r, g, b] = [red, green, blue].map((value) => {
      const channel = value / 255;
      return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });
    return (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
  };
  const lighter = Math.max(luminance(foreground), luminance(background));
  const darker = Math.min(luminance(foreground), luminance(background));
  return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Resuelve cada par de la escala semántica y lo devuelve en canales sRGB.
 *
 * Dos pasos, y ninguno es prescindible. Primero se pinta el token en un elemento real, porque
 * `getPropertyValue` sobre la variable devuelve el texto sin evaluar (`color-mix(...)`,
 * `var(...)`). Después se pasa por un lienzo, porque el color computado se serializa en el
 * espacio en que se escribió —`oklch(...)` para esta escala— y leer esos tres números como si
 * fueran canales RGB da un contraste inventado: fue el primer resultado de este gate, y daba
 * 1,01:1 sobre pares que en pantalla se leen perfectamente.
 */
const RESOLVE_STATES = (states) => {
  const probe = document.createElement('div');
  probe.style.position = 'absolute';
  probe.style.left = '-9999px';
  document.body.append(probe);

  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const context = canvas.getContext('2d', { willReadFrequently: true });
  const toSrgb = (color) => {
    context.clearRect(0, 0, 1, 1);
    context.fillStyle = '#000';
    context.fillStyle = color;
    context.fillRect(0, 0, 1, 1);
    const [red, green, blue, alpha] = context.getImageData(0, 0, 1, 1).data;
    return { channels: [red, green, blue], alpha };
  };

  const resolved = {};
  for (const state of states) {
    probe.style.backgroundColor = `var(--ds-cell-state-${state}-bg)`;
    probe.style.color = `var(--ds-cell-state-${state}-fg)`;
    const computed = getComputedStyle(probe);
    const bg = toSrgb(computed.backgroundColor);
    const fg = toSrgb(computed.color);
    resolved[state] = {
      bg: bg.channels,
      fg: fg.channels,
      bgAlpha: bg.alpha,
      bgText: computed.backgroundColor,
      fgText: computed.color,
    };
  }
  probe.remove();
  return resolved;
};

test.use({ viewport: VIEWPORT, colorScheme: 'dark' });

test('los tokens de tabla resuelven sobre una celda real con filas cargadas', async ({ page }) => {
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });

  // El laboratorio muestra una familia por vez y arranca en `foundations`, que no tiene tabla.
  // `vendor-adapters` es la familia donde se demuestran los adaptadores de grilla, así que es la
  // única superficie del laboratorio donde medir el contrato tiene sentido.
  await page.locator('[data-lab-family-link][data-family-target="vendor-adapters"]').click();
  const family = page.locator('[data-family="vendor-adapters"]');
  await expect(family).toBeVisible();

  const rows = family.locator('.ds-table-viewport table tbody tr');
  // Con filas: una tabla vacía no ejercita padding de celda ni zebra, y es el estado en el que
  // el gate estático ya daba verde sin haber mirado nada.
  await expect(rows.first()).toBeVisible();
  expect(await rows.count()).toBeGreaterThan(0);

  const missing = await page.evaluate((tokens) => {
    const root = getComputedStyle(document.documentElement);
    return tokens.filter((token) => root.getPropertyValue(token).trim() === '');
  }, TABLE_TOKENS);
  expect(missing, `tokens de tabla sin resolver: ${missing.join(', ')}`).toEqual([]);

  const cell = rows.first().locator('td').first();
  const padding = await cell.evaluate((node) => {
    const computed = getComputedStyle(node);
    return { x: computed.paddingLeft, y: computed.paddingTop };
  });
  // El relleno vertical del adaptador se perdió una vez y las grillas se compactaron sin que
  // ningún gate lo viera; por eso se asierta que no sea cero, no solo que el token exista.
  expect(padding.x).not.toBe('0px');
  expect(padding.y).not.toBe('0px');

  await logout(page);
});

test('cada peldaño de la escala semántica cumple AA sobre su propio fondo', async ({ page }) => {
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });

  const resolved = await page.evaluate(RESOLVE_STATES, CELL_STATES);

  const failures = [];
  for (const state of CELL_STATES) {
    const { bg, fg, bgAlpha, bgText, fgText } = resolved[state];
    // Un par a medio resolver (fondo transparente) es un fallo de contrato, no un aprobado:
    // significa que la cadena de `var()` se rompió.
    if (bgAlpha === 0) {
      failures.push(`${state}: fondo sin resolver (${bgText})`);
      continue;
    }
    const ratio = contrastRatio(fg, bg);
    if (ratio < AA_MIN) {
      failures.push(`${state}: ${ratio.toFixed(2)}:1 (mínimo ${AA_MIN}:1) — ${fgText} sobre ${bgText}`);
    }
  }

  expect(failures, `peldaños bajo AA:\n${failures.join('\n')}`).toEqual([]);

  await logout(page);
});
