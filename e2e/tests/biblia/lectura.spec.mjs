/**
 * Biblia de flujos · T5 lectura — escenarios críticos de acceso.
 *
 * Cada test() empieza por el `id` del escenario que cubre. Ver docs/flujos/lectura-bi.md
 * y docs/flujos/README.md.
 *
 * Cuando uno de estos falla hay dos salidas, y ninguna es ajustar la prueba para que pase:
 * o la biblia describe mal el comportamiento (se corrige la biblia), o el código incumple
 * (hallazgo a docs/EXPERIMENTS.md).
 */
import { test, expect } from '@playwright/test';

const PROYECTO = 'Da Porto';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

/** Abre sesión por la puerta de servicio: el rol que queda es el real de project_members. */
async function entrarComo(page, cuenta, proyecto = PROYECTO) {
  return page.goto(`/dev/entrar?u=${encodeURIComponent(cuenta)}&p=${encodeURIComponent(proyecto)}`);
}

test.describe('T5 · Lectura — Indicadores y Torre de Control', () => {
  test('BI-007 · el mismo rol restringido ve dos mecanismos de negación distintos', async ({ page }) => {
    // test.C tiene rol C (Subcontratista) en Da Porto — uno de los cuatro roles
    // que ROLES_SIN_INFORME excluye del informe de Indicadores, y sin
    // PERM_INTERNAL_BI_PREVIEW (RbacManager.php:33 solo concede esa capacidad a A/D/R).
    await entrarComo(page, 'test.C');

    // 403 desde IndicadoresController::index() (:27-29), antes del require de la
    // vista: no es que el cliente oculte el embed, es que el servidor nunca lo sirve.
    const indicadoresResp = await page.goto('/indicadores');
    expect(indicadoresResp.status()).toBe(403);
    await expect(page.locator('h1')).toHaveText('Error 403');

    // Y no es el mismo 403: BiPreviewAccessPolicy::canOpen() corta ANTES del
    // alcance de proyecto, con 404 explícito y a propósito («para no confirmar
    // que la pantalla existe», BiViewController.php:54-60) — el módulo entero
    // está oculto mientras se desarrolla, no solo el informe de este rol.
    const biResp = await page.goto('/bi/control-tower');
    expect(biResp.status()).toBe(404);
  });

  test('BI-008 · un rol permitido entra a la Torre de Control y uno denegado no', async ({ page }) => {
    // Permitido: test.R (Residente) — ampliado el 2026-08-24 (reparto de
    // lienzos por rol). El flag `bi.control_tower.visible` está en 1.
    await entrarComo(page, 'test.R');
    const permitido = await page.goto('/bi/control-tower');
    expect(permitido.status()).toBe(200);

    // Denegado: test.C (Subcontratista) — sin PERM_INTERNAL_BI_PREVIEW.
    await entrarComo(page, 'test.C');
    const denegado = await page.goto('/bi/control-tower');
    expect(denegado.status()).toBe(404);
  });
});
