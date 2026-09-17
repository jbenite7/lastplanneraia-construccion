import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

/**
 * Errores de `/api/lps/*` contra la forma REAL del servidor (plan 2026-09-17-errores-api-lps-contrato).
 *
 * `LpsApiController` emitía `error.fields: []` en todo error sin campos. El esquema del cliente
 * pide objeto, así que `pedir()` descartaba el cuerpo entero y el cajón recibía `HTTP_404`: no
 * reconocía `LPS_TARGET_NOT_FOUND` y pintaba el cuerpo normal (diagnóstico, hilo vacío,
 * formulario) sobre una actividad que no existe, en vez de «ya no está disponible».
 *
 * **Sin dobles de red:** sesión PHP real por la puerta de servicio, `/api/session` real y
 * `/api/lps/comments` real. Nada de `page.route`.
 *
 * **Cómo se abre el cajón.** Hoy ninguna pantalla React monta `DisparadorLps` (`rutas.tsx`:
 * «ninguna superficie migrada todavía»), pero `AppShell` sí monta el `LpsDrawerProvider` y el
 * `CajonContextualLps` reales bajo `/app`. El spec alcanza el valor del provider por el árbol de
 * fibras de React y llama `abrir()` — la misma función que llamaría el disparador — con un target
 * inexistente. Es acoplamiento a React, no al bundle: si el árbol cambia de forma, el spec falla
 * con nombre propio («no se encontró el provider»), nunca en verde.
 *
 * **409 `LPS_TARGET_STALE`:** no se provoca aquí. Exige una alerta existente y cerrada, y
 * `lps_escalamientos` está vacía en la base de dev; crearla sería DML. Su cuerpo lo captura
 * `tests/test_lps_api_contract.php` del render real y lo verifica
 * `frontend/src/lib/api/esquemas/error.contrato.test.ts`; la reacción del reducer a ese código es
 * la misma rama `noDisponible` que este spec ejercita con el 404.
 */

const PROYECTO = 'PDC Sandbox E2E';
const CUERPOS_REALES = JSON.parse(
  readFileSync(new URL('../fixtures/api-lps-error-bodies.json', import.meta.url), 'utf8'),
);

test('404 real: el cajón LPS reconoce LPS_TARGET_NOT_FOUND y muestra «no disponible»', async ({ page }) => {
  const erroresDePagina = [];
  page.on('pageerror', (error) => erroresDePagina.push(error.message));

  const entrada = await page.goto(`/dev/entrar?u=test.R&p=${encodeURIComponent(PROYECTO)}`);
  expect(entrada?.ok(), 'la puerta de servicio debe estar abierta en este servidor').toBe(true);

  await page.goto('/app');
  await expect(page.locator('main')).toBeVisible();

  const respuesta = page.waitForResponse((r) => r.url().includes('/api/lps/comments'));
  const abierto = await page.evaluate(() => {
    const raiz = document.getElementById('root');
    const clave = raiz && Object.keys(raiz).find((k) => k.startsWith('__reactContainer$'));
    const pila = clave ? [raiz[clave]] : [];
    while (pila.length > 0) {
      const fibra = pila.pop();
      if (!fibra) continue;
      const valor = fibra.memoizedProps?.value;
      if (valor && typeof valor.abrir === 'function' && typeof valor.cerrar === 'function' && 'estado' in valor) {
        valor.abrir({
          target: { consecutivo: 999999999, modulo: 'PS' },
          module: 'PS',
          activity: {
            id: 999999999,
            label: 'Actividad inexistente',
            state: { key: 'pendiente', label: 'Pendiente', phase: null, actions: [] },
            progress: { ratio: 0, display: '0%' },
            critical: false,
            isHeader: false,
          },
          // Copia de `configuracionPorDefecto()` (frontend/src/shared/lps/dominio/restricciones.ts):
          // `IndicadorRestricciones` la recorre, y con `{}` el árbol entero se desmonta.
          restrictions: {
            config: {
              area: 'Construccion',
              restrictions: [
                { key: 'D_y_E', label: 'Diseños y Especif.', type: 'hard', threshold: 100 },
                { key: 'Materiales', label: 'Materiales', type: 'hard', threshold: 100 },
                { key: 'MdeO', label: 'Mano de Obra', type: 'hard', threshold: 100 },
                { key: 'Equipos', label: 'Equipos', type: 'hard', threshold: 100 },
                { key: 'Predecesora', label: 'Predecesora', type: 'hard', threshold: 50 },
                { key: 'Pdto_Cons', label: 'Procedimiento Constructivo', type: 'soft', threshold: 100 },
                { key: 'Seguimiento', label: 'Seguimiento', type: 'soft', threshold: 100 },
              ],
              hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
              softRestrictions: ['Pdto_Cons', 'Seguimiento'],
            },
            values: {},
          },
        });
        return true;
      }
      if (fibra.sibling) pila.push(fibra.sibling);
      if (fibra.child) pila.push(fibra.child);
    }
    return false;
  });
  expect(abierto, 'no se encontró el LpsDrawerProvider en el árbol de /app').toBe(true);

  const recibida = await respuesta;
  expect(recibida.status()).toBe(404);
  const cajon = page.getByRole('dialog', { name: 'Actividad inexistente' });
  await expect(cajon).toBeVisible();
  await expect(cajon.getByRole('alert')).toHaveText('Esta actividad o alerta ya no está disponible.');
  await expect(cajon.locator('form.lps-formulario-comentario')).toHaveCount(0);

  const cuerpo = await recibida.json();
  expect({ ...cuerpo, meta: { requestId: '<requestId>' } }).toEqual(CUERPOS_REALES['404_lps_target_not_found'].cuerpo);
  expect(cuerpo.error).toEqual({ code: 'LPS_TARGET_NOT_FOUND', message: 'No fue posible completar la acción.' });
  expect(erroresDePagina).toEqual([]);
});
