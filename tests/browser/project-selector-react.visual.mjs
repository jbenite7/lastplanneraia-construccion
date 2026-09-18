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
 * Candidatos visuales del piloto React del selector de proyectos (S04, Tarea 9 — T9-5).
 *
 * **Esta suite NO aprueba nada por sí sola.** Solo escribe PNG a
 * `test-output/s04-selector-proyectos-candidates/` (bajo `outputDir`, en `.gitignore`) para que
 * Felipe los mire y el coordinador decida congelarlos como goldens en la Tarea 10.
 * **A propósito no hay mitad `golden`:** ningún `toHaveScreenshot`, ningún manifiesto, ningún
 * hash actualizado — la corrección 11 lo prohíbe explícitamente hasta su aprobación. Compárese
 * con `login-react.visual.mjs` (S01), que sí tiene una mitad `golden` porque esa pantalla ya
 * pasó por la aprobación que esta todavía no tiene.
 *
 * Ocho candidatos: dos temas × cuatro viewports (`390x844`, `768x1024`, `1180x820`, `1440x900`),
 * fallback claro (corrección de ejecución #2). Cada uno captura el camino feliz: lista con dos
 * proyectos, uno marcado "Proyecto actual", CTA de Control Tower visible, drawer cerrado en 390.
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's04-selector-proyectos-candidates');

const TEMA_EN_ARCHIVO = { oscuro: 'dark', claro: 'light' };

const TEMAS = ['oscuro', 'claro'];
const VIEWPORTS = [
  { nombre: '390x844', width: 390, height: 844 },
  { nombre: '768x1024', width: 768, height: 1024 },
  { nombre: '1180x820', width: 1180, height: 820 },
  { nombre: '1440x900', width: 1440, height: 900 },
];

test.beforeAll(async () => {
  await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
});

for (const tema of TEMAS) {
  for (const viewport of VIEWPORTS) {
    const nombre = `project-selector-react-${TEMA_EN_ARCHIVO[tema]}-${viewport.nombre}`;

    test(`candidate ${nombre}`, async ({ page }) => {
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

      await page.screenshot({
        path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`),
        fullPage: true,
      });
    });
  }
}
