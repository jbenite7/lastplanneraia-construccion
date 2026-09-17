import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { TEMAS, VIEWPORTS, arranqueAnonimo, fijarTema, simularSesion } from './support/login-react-fixtures.mjs';

/**
 * Contrato visual de la pantalla de recuperación de contraseña React (S02, Tarea 9 / 9b).
 *
 * **Este archivo tiene dos mitades deliberadamente separadas, igual que `login-react.visual.mjs`:**
 *
 * 1. `candidate …` — corre siempre y escribe PNG a `test-output/s02-password-forgot-candidates/`,
 *    que está en `.gitignore`. Son propuestas para que una persona las mire; no se comparan contra
 *    nada, así que no pueden fallar por diferencia ni fijar un baseline por accidente.
 * 2. `golden …` — usa `toHaveScreenshot` y **se salta** mientras no exista
 *    `S02_GOLDENS_APROBADOS=1` en el entorno. Los 8 candidatos de esta mitad ya fueron aprobados
 *    explícitamente por Felipe (Tarea 9b): sus hashes quedan anclados en las filas
 *    `auth-password-forgot-react-*` de `docs/design-system/manifests/auth.json`.
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's02-password-forgot-candidates');
const TEMA_EN_ARCHIVO = { oscuro: 'dark', claro: 'light' };
const GOLDENS_APROBADOS = process.env.S02_GOLDENS_APROBADOS === '1';

/** Deja la pantalla de recuperación anónima quieta y lista para capturar. */
async function prepararPantallaDeRecuperacion(page, tema, viewport) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await fijarTema(page, tema);
  await simularSesion(page, [arranqueAnonimo()]);
  await page.goto('/password/forgot');
  await expect(page.getByRole('heading', { level: 1, name: 'Restablecer contraseña' })).toBeVisible();
  // Sin foco heredado de la navegación anterior: las ocho imágenes deben ser comparables.
  await page.evaluate(() => document.activeElement?.blur?.());
}

for (const tema of TEMAS) {
  for (const viewport of VIEWPORTS) {
    const nombre = `password-forgot-${TEMA_EN_ARCHIVO[tema]}-${viewport.width}x${viewport.height}`;

    test(`candidate ${nombre}`, async ({ page }) => {
      await prepararPantallaDeRecuperacion(page, tema, viewport);
      await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
      // `fullPage` queda descartado a propósito, igual que en `login-react.visual.mjs`: el
      // candidato debe medir exactamente el viewport, no la página completa.
      await page.screenshot({ path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`) });
    });

    test(`golden ${nombre}`, async ({ page }) => {
      test.skip(
        !GOLDENS_APROBADOS,
        'Baseline pendiente de la aprobación visual explícita de Felipe (S02 Tarea 9b).',
      );
      await prepararPantallaDeRecuperacion(page, tema, viewport);
      await expect(page).toHaveScreenshot(`${nombre}.png`);
    });
  }
}
