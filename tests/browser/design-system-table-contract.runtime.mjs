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
// La sonda compartida, no una copia. Además de rasterizar con canvas —que es lo
// que hace falta para `oklch()` y `color-mix()`—, COMPONE ALFA sobre los
// ancestros hasta la primera capa opaca. Aquí no es un detalle: dos peldaños de
// la escala heredan `--ds-active-surface`, que es translúcido, y medirlos sin
// componer da un contraste inventado. Sus dos límites conocidos (elementos fuera
// de flujo y fondos con degradado) no afectan a esta medición: los especímenes
// son elementos en flujo con fondo plano.
import { installContrastProbe, measure } from './support/contrast.mjs';

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

/**
 * Siembra un espécimen por peldaño y devuelve sus selectores.
 *
 * Se pinta el token en un elemento real porque `getPropertyValue` sobre la variable devuelve el
 * texto sin evaluar (`color-mix(...)`, `var(...)`). Los especímenes van dentro del contenedor
 * principal, no en un rincón suelto: los dos peldaños que heredan `--ds-active-surface` son
 * translúcidos, así que su contraste depende de sobre qué se compongan y hay que darles el
 * mismo fondo que tendría una celda.
 */
const SEMBRAR_ESPECIMENES = (states) => {
  const host = document.querySelector('[data-family="vendor-adapters"]') ?? document.body;
  const caja = document.createElement('div');
  caja.id = 'especimenes-cell-state';
  for (const state of states) {
    const muestra = document.createElement('span');
    muestra.id = `especimen-${state}`;
    muestra.textContent = 'Actividad de referencia';
    muestra.style.backgroundColor = `var(--ds-cell-state-${state}-bg)`;
    muestra.style.color = `var(--ds-cell-state-${state}-fg)`;
    muestra.style.display = 'block';
    muestra.style.padding = '0.5rem 0.75rem';
    caja.append(muestra);
  }
  host.append(caja);
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
  await installContrastProbe(page);
  await login(page, ADMIN);
  await selectProject(page, DA_PORTO);
  await page.goto('/internal/design-system', { waitUntil: 'domcontentloaded' });

  await page.evaluate(SEMBRAR_ESPECIMENES, CELL_STATES);

  const failures = [];
  for (const state of CELL_STATES) {
    const medida = await measure(page, `#especimen-${state}`);
    // Un par a medio resolver es un fallo de contrato, no un aprobado: significa que la cadena
    // de `var()` se rompió y el texto cae a color heredado. Fue el caso de `sin-datos`, que
    // apuntaba a `--ds-active-text-tertiary`, una variable que nunca existió.
    if (!medida || typeof medida.ratio !== 'number') {
      failures.push(`${state}: la sonda no pudo medir el espécimen`);
      continue;
    }
    if (medida.ratio < AA_MIN) {
      failures.push(`${state}: ${medida.ratio.toFixed(2)}:1 (mínimo ${AA_MIN}:1)`);
    }
  }

  expect(failures, `peldaños bajo AA:\n${failures.join('\n')}`).toEqual([]);

  await logout(page);
});
