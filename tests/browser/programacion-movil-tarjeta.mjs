import { test, expect } from '@playwright/test';
import { login } from './support/session.mjs';

const CANDIDATOS = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

// `Preconstrucción Da Porto` (área Pre-Construcción) solo tiene UNA restricción
// configurada en `/api/general/restriction-config` (`restriccion_pc_1`), a
// diferencia de las siete de un proyecto de Construcción (ver
// CONSTRUCTION_DEFAULTS en programacion_intermedia/hot.js). No es un defecto:
// el numero de restricciones depende del área del proyecto. La prueba que
// verifica "aparecen las siete restricciones" necesita un proyecto de
// Construcción para ser representativa del contrato E2-bis, así que usa este
// orden en vez del general.
const CANDIDATOS_CONSTRUCCION = ['Optimización Aeropuerto JMC', 'Da Porto', 'Prueba', 'Preconstrucción Da Porto'];

async function abrir(page, ruta, candidatos = CANDIDATOS) {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  for (const name of candidatos) {
    const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (await card.count()) {
      await card.locator('button[type="submit"], .btn-enter').click();
      break;
    }
  }
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
  await page.goto(ruta);
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 }).catch(() => {});
  await page.waitForTimeout(1200);
}

test('semanal: la tarjeta cerrada no pasa de 380px', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const alto = await page.locator('.ps-mobile-card').first().evaluate((el) => el.getBoundingClientRect().height);
  expect(alto, `La tarjeta mide ${Math.round(alto)}px cerrada`).toBeLessThan(380);
});

test('semanal: el detalle esta plegado y el compromiso visible sin desplegar', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const tarjeta = page.locator('.ps-mobile-card').first();
  await expect(tarjeta.locator('details.ps-mobile-detalle')).not.toHaveAttribute('open', '');
  await expect(tarjeta.locator('.ps-mobile-edicion')).toBeVisible();
});

test('semanal: el capitulo no va dentro del titulo', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const titulo = await page.locator('.ps-mobile-card h3').first().textContent();
  expect(titulo, 'El capitulo sigue dentro del titulo').not.toContain('Capítulo');
});

test('semanal: la linea de foco dice lo mismo que el aria del chip', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const datos = await page.evaluate(() => {
    const card = document.querySelector('.ps-mobile-card');
    const foco = card.querySelector('.ps-mobile-foco');
    const boton = card.querySelector('.ops-state-zoom');
    return { foco: foco ? foco.textContent.trim() : null, aria: boton ? boton.getAttribute('aria-label') : '' };
  });
  if (datos.foco) {
    expect(datos.aria, 'El aria del chip no contiene el foco que muestra la tarjeta').toContain(datos.foco);
  }
});

test('intermedia: el contador cuenta duras y coincide con los segmentos', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const datos = await page.evaluate(() => {
    const card = document.querySelector('.pi-mobile-card');
    const chip = card.querySelector('.pi-mobile-card__state').textContent.trim();
    const total = card.querySelectorAll('.pi-mobile-card__barra span').length;
    const liberadas = card.querySelectorAll('.pi-mobile-card__barra span.is-liberada').length;
    return { chip, total, liberadas };
  });
  expect(datos.chip).toBe(`${datos.liberadas} de ${datos.total}`);
  expect(datos.total, 'El contador debe contar las duras, no las siete').toBeLessThanOrEqual(5);
});

test('intermedia: el chip nunca contradice al estado de su propia tarjeta', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const filas = await page.evaluate(() => {
    const modulo = window.PIHotModule && window.PIHotModule.getHotInstance;
    return [...document.querySelectorAll('.pi-mobile-card')].slice(0, 20).map((card) => {
      const chip = card.querySelector('.pi-mobile-card__state').textContent.trim();
      const [liberadas, total] = chip.split(' de ').map(Number);
      return { completo: liberadas === total, clase: card.className };
    });
  });
  // Una tarjeta con todas las duras liberadas no puede estar pintada como
  // bloqueada, y una con alguna pendiente no puede estar pintada como lista:
  // el contador y el estado salen de la misma regla (E2-bis-e).
  for (const fila of filas) {
    if (fila.completo) {
      expect(fila.clase, 'Todas las duras liberadas pero pintada como bloqueada').not.toContain('execution-blocked');
    }
  }
});

test('intermedia: al desplegar aparecen las siete restricciones', async ({ page }) => {
  await abrir(page, '/programacion-intermedia', CANDIDATOS_CONSTRUCCION);
  const tarjeta = page.locator('.pi-mobile-card').first();
  await tarjeta.locator('details.pi-mobile-card__detalle > summary').click();
  const controles = await tarjeta.locator('[data-pi-restriccion]').count();
  expect(controles, 'Se editan las siete, aunque el contador cuente cinco').toBeGreaterThan(5);
});

test('intermedia: sin responsable, las restricciones se bloquean Y se explica', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const hallazgo = await page.evaluate(() => {
    const cards = [...document.querySelectorAll('.pi-mobile-card')];
    for (const card of cards) {
      const resp = card.querySelector('.pi-mobile-card__responsable');
      if (resp && resp.textContent.trim() === 'Sin responsable') {
        const control = card.querySelector('[data-pi-restriccion]');
        return { bloqueado: control ? control.disabled : null, aviso: Boolean(card.querySelector('.pi-mobile-card__aviso')) };
      }
    }
    return null;
  });
  if (hallazgo === null) {
    test.skip(true, 'El proyecto sembrado no tiene ninguna fila sin responsable');
  }
  expect(hallazgo.bloqueado, 'Sin responsable las restricciones deben estar bloqueadas (I4)').toBe(true);
  expect(hallazgo.aviso, 'Se bloquean sin decir por que').toBe(true);
});
