import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { TEMAS, VIEWPORTS, arranqueAnonimo, fijarTema, simularSesion } from './support/login-react-fixtures.mjs';

/**
 * Contrato visual de la pantalla de restablecimiento de contraseña React (S03, Tarea 9).
 *
 * **Dos mitades deliberadamente separadas, igual que `password-recovery-react.visual.mjs`:**
 *
 * 1. `candidate …` — corre siempre y escribe PNG a `test-output/s03-password-reset-candidates/`
 *    (en `.gitignore`). Son propuestas para que Felipe las mire; no se comparan contra nada.
 * 2. `golden …` — usa `toHaveScreenshot` y **se salta** mientras no exista
 *    `S03_GOLDENS_APROBADOS=1`. No hay baseline ni fila en `docs/design-system/manifests/auth.json`
 *    hasta la aprobación visual explícita (correcciones §13).
 *
 * Dos estados por tema y viewport: `valido` (formulario vacío, validación simulada con un token
 * sintético que nunca aparece en el nombre del archivo) e `invalido` (sin token, sin API).
 */

const DIRECTORIO_CANDIDATOS = path.join('test-output', 's03-password-reset-candidates');
const TEMA_EN_ARCHIVO = { oscuro: 'dark', claro: 'light' };
const GOLDENS_APROBADOS = process.env.S03_GOLDENS_APROBADOS === '1';
const TOKEN_SINTETICO = 'a'.repeat(64);

const ESTADOS = {
  valido: {
    ruta: `/password/reset?token=${TOKEN_SINTETICO}`,
    async simular(page) {
      await page.route('**/api/auth/password/reset/validate', (route) =>
        route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, state: 'valid' }) }),
      );
    },
    esperar: (page) => expect(page.getByLabel('Nueva contraseña')).toBeVisible(),
  },
  invalido: {
    ruta: '/password/reset',
    async simular() {},
    esperar: (page) => expect(page.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toBeVisible(),
  },
};

async function prepararPantalla(page, estado, tema, viewport) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await fijarTema(page, tema);
  await simularSesion(page, [arranqueAnonimo()]);
  await ESTADOS[estado].simular(page);
  await page.goto(ESTADOS[estado].ruta);
  await expect(page.getByRole('heading', { level: 1, name: 'Define tu nueva contraseña' })).toBeVisible();
  await ESTADOS[estado].esperar(page);
  // El estado inválido enfoca «Solicitar un nuevo enlace» al montar: sin foco residual, las
  // imágenes deben ser comparables entre sí.
  await page.evaluate(() => document.activeElement?.blur?.());
}

for (const estado of Object.keys(ESTADOS)) {
  for (const tema of TEMAS) {
    for (const viewport of VIEWPORTS) {
      const nombre = `password-reset-${estado}-${TEMA_EN_ARCHIVO[tema]}-${viewport.width}x${viewport.height}`;

      test(`candidate ${nombre}`, async ({ page }) => {
        await prepararPantalla(page, estado, tema, viewport);
        await mkdir(DIRECTORIO_CANDIDATOS, { recursive: true });
        // Sin `fullPage`: el candidato mide exactamente el viewport.
        await page.screenshot({ path: path.join(DIRECTORIO_CANDIDATOS, `${nombre}.png`) });
      });

      test(`golden ${nombre}`, async ({ page }) => {
        test.skip(!GOLDENS_APROBADOS, 'Baseline pendiente de la aprobación visual explícita de Felipe (S03).');
        await prepararPantalla(page, estado, tema, viewport);
        await expect(page).toHaveScreenshot(`${nombre}.png`);
      });
    }
  }
}
