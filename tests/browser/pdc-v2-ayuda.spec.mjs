import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PROJECTS } from './fixtures/projects.mjs';

// Contra la obra real, como el e2e de vencimientos y por el mismo motivo: esta pantalla SOLO LEE
// —abre paneles y navega—, no amarra, no recalcula y no escribe una fila. Y es el único proyecto
// con datos de verdad en las ocho pantallas, así que si una reventara al montar el botón, aquí se
// vería. No hay nada que sembrar ni que restaurar.
const project = PROJECTS.find((p) => p.projectId === 73) ?? PROJECTS[0];

// Las ocho páginas del módulo, con la ruta por la que se llega a cada una. Pasos no está en la
// barra de pestañas —se configura una vez y se entra desde el Plan— pero tiene ayuda igual.
const PANTALLAS = [
  ['importar', '/ensamble/importar'],
  ['maestro', '/ensamble/maestro'],
  ['presupuesto', '/ensamble/presupuesto'],
  ['comparar', '/ensamble/comparar'],
  ['paquetes', '/ensamble/paquetes'],
  ['plan', '/ensamble/plan'],
  ['pasos', '/ensamble/plan/pasos'],
  ['seguimiento', '/seguimiento/avance'],
];

/** El helper de sesión silencia el recorrido para todos los e2e; aquí lo queremos ver. */
async function permitirRecorrido(page) {
  await page.addInitScript(() => {
    try {
      window.localStorage.removeItem('aia-pdc-recorrido');
    } catch {
      // Sin almacén, el recorrido sale igual: es el comportamiento que este test quiere.
    }
  });
}

async function abrir(page, ruta) {
  await page.goto(`/plan-compras#${ruta}`, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('h1').first()).toBeVisible({ timeout: 15000 });
}

test.describe('PDC v2 · ayuda dentro de la aplicación', () => {
  test.use({ viewport: { width: 1180, height: 820 } });

  test.afterEach(async ({ page }) => { await logout(page); });

  test('el recorrido sale la primera vez, se omite y no vuelve al recargar', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await permitirRecorrido(page);
    await abrir(page, '/ensamble/importar');

    const recorrido = page.getByTestId('pdc-recorrido');
    await expect(recorrido).toBeVisible({ timeout: 15000 });
    await expect(page.getByTestId('pdc-recorrido-progreso')).toHaveText('Paso 1 de 6');

    // Omitible en el PRIMER clic: no hay que recorrerlo para salir de él.
    await page.getByTestId('pdc-recorrido-omitir').click();
    await expect(recorrido).toBeHidden();

    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15000 });
    await expect(page.getByTestId('pdc-recorrido')).toBeHidden();
  });

  test('recorrerlo entero también lo cierra para siempre', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await permitirRecorrido(page);
    await abrir(page, '/ensamble/importar');
    await expect(page.getByTestId('pdc-recorrido')).toBeVisible({ timeout: 15000 });

    for (let paso = 1; paso <= 6; paso += 1) {
      await expect(page.getByTestId('pdc-recorrido-progreso')).toHaveText(`Paso ${paso} de 6`);
      await page.getByTestId('pdc-recorrido-siguiente').click();
    }
    await expect(page.getByTestId('pdc-recorrido')).toBeHidden();

    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 15000 });
    await expect(page.getByTestId('pdc-recorrido')).toBeHidden();
  });

  test('las ocho pantallas tienen su ayuda, y abre con las tres preguntas', async ({ page }) => {
    await loginAndSelectProject(page, project);

    for (const [id, ruta] of PANTALLAS) {
      await abrir(page, ruta);
      await page.getByTestId(`pdc-ayuda-boton-${id}`).first().click();

      const panel = page.getByTestId(`pdc-ayuda-panel-${id}`).first();
      await expect(panel, `sin panel de ayuda en ${id}`).toBeVisible({ timeout: 10000 });
      // El orden es el contrato del spec, no una preferencia de maquetación.
      await expect(panel.getByText('Qué hace esta pantalla')).toBeVisible();
      await expect(panel.getByText('Qué tengo que hacer yo aquí')).toBeVisible();
      await expect(panel.getByText('Qué pasa después')).toBeVisible();

      await page.getByTestId(`pdc-ayuda-cerrar-${id}`).first().click();
      await expect(panel).toBeHidden();
    }
  });

  test('se abre y se cierra con teclado, y el foco vuelve donde estaba', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await abrir(page, '/ensamble/plan');

    const boton = page.getByTestId('pdc-ayuda-boton-plan').first();
    await boton.focus();
    await page.keyboard.press('Enter');
    await expect(page.getByTestId('pdc-ayuda-panel-plan').first()).toBeVisible({ timeout: 10000 });

    await page.keyboard.press('Escape');
    await expect(page.getByTestId('pdc-ayuda-panel-plan').first()).toBeHidden();
    // Sin esto, quien navega con teclado vuelve al principio de la página y pierde el sitio.
    await expect(boton).toBeFocused();
  });

  test('el recorrido se puede relanzar desde la ayuda', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await abrir(page, '/ensamble/plan');
    // El helper lo dejó como visto, así que no debería estar en pantalla.
    await expect(page.getByTestId('pdc-recorrido')).toBeHidden();

    await page.getByTestId('pdc-ayuda-boton-plan').first().click();
    await page.getByTestId('pdc-ayuda-relanzar-plan').first().click();

    await expect(page.getByTestId('pdc-recorrido')).toBeVisible({ timeout: 10000 });
    await expect(page.getByTestId('pdc-recorrido-progreso')).toHaveText('Paso 1 de 6');
  });

  test('el panel abierto no provoca scroll horizontal', async ({ page }) => {
    await loginAndSelectProject(page, project);
    await abrir(page, '/ensamble/plan');
    await page.getByTestId('pdc-ayuda-boton-plan').first().click();
    await expect(page.getByTestId('pdc-ayuda-panel-plan').first()).toBeVisible({ timeout: 10000 });

    const desborda = await page.evaluate(
      () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
    );
    expect(desborda, 'el panel de ayuda desborda el viewport de 1180px').toBe(false);
  });
});
