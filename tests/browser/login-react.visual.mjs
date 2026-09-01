import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import {
  TEMAS,
  VIEWPORTS,
  arranqueAnonimo,
  fijarTema,
  simularSesion,
} from './support/login-react-fixtures.mjs';

/**
 * Contrato visual de la pantalla de acceso React (S01, Tarea 14).
 *
 * **Este archivo NO aprueba nada por sí solo.** Tiene dos mitades deliberadamente separadas:
 *
 * 1. `candidate …` — corre siempre y escribe PNG a `test-output/s01-login-candidates/`, que está
 *    en `.gitignore`. Son propuestas para que una persona las mire; no se comparan contra nada,
 *    así que no pueden fallar por diferencia ni fijar un baseline por accidente.
 * 2. `golden …` — usa `toHaveScreenshot` y **se salta** mientras no exista
 *    `S01_GOLDENS_APROBADOS=1` en el entorno. Playwright crea el baseline en su primera corrida
 *    con `--update-snapshots`; dejar esa mitad activa antes de la aprobación de Felipe convertiría
 *    la primera corrida en una aprobación tácita, que es justo lo que `AGENTS.md` prohíbe
 *    ("no regeneres snapshots ni baselines para forzar un resultado verde; los cambios visuales
 *    requieren aprobación explícita").
 *
 * El golden `auth-login-dark-1180x820` que ya vive anclado por `sha256` en
 * `docs/design-system/manifests/auth.json` **no se toca aquí**: los candidatos escriben en otra
 * carpeta, así que ninguna ancla existente se mueve.
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's01-login-candidates');
const GOLDENS_APROBADOS = process.env.S01_GOLDENS_APROBADOS === '1';

/** Deja la pantalla de acceso anónima quieta y lista para capturar. */
async function prepararPantallaDeAcceso(page, tema, viewport) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await fijarTema(page, tema);
  await simularSesion(page, [arranqueAnonimo()]);
  await page.goto('/login');
  await expect(page.getByRole('heading', { level: 1, name: /^Entrar$/ })).toBeVisible();
  // El foco por defecto queda en el `<body>`; sin esto, una captura podría llevar el anillo de
  // foco de la navegación anterior y las ocho imágenes no serían comparables entre sí.
  await page.evaluate(() => document.activeElement?.blur?.());
}

for (const tema of TEMAS) {
  for (const viewport of VIEWPORTS) {
    const nombre = `login-${tema}-${viewport.nombre}`;

    test(`candidate ${nombre}`, async ({ page }) => {
      await prepararPantallaDeAcceso(page, tema, viewport);
      await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
      await page.screenshot({
        path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`),
        fullPage: true,
      });
    });

    test(`golden ${nombre}`, async ({ page }) => {
      test.skip(
        !GOLDENS_APROBADOS,
        'Baseline pendiente de la aprobación visual explícita de Felipe (S01 Tarea 14, paso 3).',
      );
      await prepararPantallaDeAcceso(page, tema, viewport);
      await expect(page).toHaveScreenshot(`${nombre}.png`, { fullPage: true });
    });
  }
}
