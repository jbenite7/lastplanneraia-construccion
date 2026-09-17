import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { TEMAS, VIEWPORTS, arranqueAnonimo, fijarTema, simularSesion } from './support/login-react-fixtures.mjs';

/**
 * Candidatos visuales de la pantalla de recuperación de contraseña React (S02, Tarea 9).
 *
 * **Este archivo NO aprueba nada.** A diferencia de `login-react.visual.mjs`, no lleva mitad
 * `golden`: los candidatos se escriben en `test-output/` (ignorado por git) para que Felipe los
 * revise. Ningún PNG ni `toHaveScreenshot` se crea en `__screenshots__` — esa aprobación llega
 * en un cambio aparte, cuando exista.
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's02-password-forgot-candidates');
const TEMA_EN_ARCHIVO = { oscuro: 'dark', claro: 'light' };

for (const tema of TEMAS) {
  for (const viewport of VIEWPORTS) {
    const nombre = `password-forgot-${TEMA_EN_ARCHIVO[tema]}-${viewport.width}x${viewport.height}`;

    test(`candidate ${nombre}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.emulateMedia({ reducedMotion: 'reduce' });
      await fijarTema(page, tema);
      await simularSesion(page, [arranqueAnonimo()]);
      await page.goto('/password/forgot');
      await expect(page.getByRole('heading', { level: 1, name: 'Restablecer contraseña' })).toBeVisible();
      // Sin foco heredado de la navegación anterior: las ocho imágenes deben ser comparables.
      await page.evaluate(() => document.activeElement?.blur?.());

      await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
      // `fullPage` queda descartado a propósito, igual que en `login-react.visual.mjs`: el
      // candidato debe medir exactamente el viewport, no la página completa.
      await page.screenshot({ path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`) });
    });
  }
}
