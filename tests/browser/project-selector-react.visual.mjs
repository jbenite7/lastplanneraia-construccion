import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import {
  arranqueAutenticadoConProyecto,
  fijarTema,
  listaProyectos,
  simularProyectos,
  simularSesion,
} from './support/project-selector-react-fixtures.mjs';

/**
 * Contrato visual del selector de proyectos React (S04, Tarea 9 — T9-5 / Tarea 9b).
 *
 * **Este archivo tiene dos mitades deliberadamente separadas, igual que
 * `login-react.visual.mjs` (S01) y `password-recovery-react.visual.mjs` (S02):**
 *
 * 1. `candidate …` — corre siempre y escribe PNG a
 *    `test-output/s04-selector-proyectos-candidates/`, que está en `.gitignore`. Son propuestas
 *    para que una persona las mire; no se comparan contra nada, así que no pueden fallar por
 *    diferencia ni fijar un baseline por accidente.
 * 2. `golden …` — usa `toHaveScreenshot` y **se salta** mientras no exista
 *    `S04_GOLDENS_APROBADOS=1` en el entorno. Los 8 candidatos de esta mitad ya fueron aprobados
 *    explícitamente por Felipe (Tarea 9b, ruling T9b-5), con la condición de que "Last Planner
 *    AIA" quedara en una sola línea — verificada por el coordinador en las capturas de HEAD.
 *    Sus hashes quedan anclados en las filas `project-selector-react-*` de
 *    `docs/design-system/manifests/project-selector.json`.
 *
 * **Corrección posterior a esa aprobación (mismo T9b):** los candidatos a 390x844 y 768x1024
 * medían 913 y 1093px de alto — 69px de scroll fantasma, la altura de `.shell-mobile-topbar`,
 * que se sumaba en vez de compartirse con el contenido (`public/css/project-selector-react.css`,
 * comentario junto al `@media (max-width: 1179px)`). Tras el arreglo los 8 PNG miden
 * exactamente su viewport — incluidos los dos que ya coincidían antes — y son, píxel a píxel,
 * idénticos a los aprobados en la zona 0..alto-del-viewport: la aprobación de Felipe sigue
 * vigente, la mitad `golden` no necesita `capture: "element"` ni ninguna excepción en
 * `docs/design-system/evidence-exceptions.json`.
 *
 * Ocho candidatos: dos temas × cuatro viewports (`390x844`, `768x1024`, `1180x820`, `1440x900`),
 * fallback claro (corrección de ejecución #2). Cada uno captura el camino feliz: lista con dos
 * proyectos, uno marcado "Proyecto actual", CTA de Control Tower visible, drawer cerrado en 390.
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's04-selector-proyectos-candidates');
const GOLDENS_APROBADOS = process.env.S04_GOLDENS_APROBADOS === '1';

const TEMA_EN_ARCHIVO = { oscuro: 'dark', claro: 'light' };

const TEMAS = ['oscuro', 'claro'];
const VIEWPORTS = [
  { nombre: '390x844', width: 390, height: 844 },
  { nombre: '768x1024', width: 768, height: 1024 },
  { nombre: '1180x820', width: 1180, height: 820 },
  { nombre: '1440x900', width: 1440, height: 900 },
];

/** Deja el selector de proyectos autenticado quieto y listo para capturar. */
async function prepararSelectorDeProyectos(page, tema, viewport) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await fijarTema(page, tema);
  await simularSesion(page, [arranqueAutenticadoConProyecto()]);
  await simularProyectos(page, [listaProyectos()]);

  await page.goto('/app/proyectos');
  await expect(page.getByRole('heading', { level: 1, name: 'Tus proyectos' })).toBeVisible();
  await expect(page.getByRole('heading', { level: 2, name: 'Da Porto' })).toBeVisible();
  await page.evaluate(() => document.activeElement?.blur?.());

  const overflow = await page.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
  );
  expect(overflow).toBeLessThanOrEqual(1);
}

test.beforeAll(async () => {
  await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
});

for (const tema of TEMAS) {
  for (const viewport of VIEWPORTS) {
    const nombre = `project-selector-react-${TEMA_EN_ARCHIVO[tema]}-${viewport.nombre}`;

    test(`candidate ${nombre}`, async ({ page }) => {
      await prepararSelectorDeProyectos(page, tema, viewport);

      await page.screenshot({
        path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`),
        fullPage: true,
      });
    });

    test(`golden ${nombre}`, async ({ page }) => {
      test.skip(
        !GOLDENS_APROBADOS,
        'Baseline pendiente de la aprobación visual explícita de Felipe (S04 Tarea 9b, ruling T9b-5).',
      );
      await prepararSelectorDeProyectos(page, tema, viewport);
      // `fullPage: true`, igual que el candidato: tras el arreglo del scroll fantasma (T9b) el
      // PNG mide exactamente el viewport declarado en los 8 casos, así que golden y candidato
      // capturan con el mismo encuadre y son comparables punto por punto.
      await expect(page).toHaveScreenshot(`${nombre}.png`, { fullPage: true });
    });
  }
}
