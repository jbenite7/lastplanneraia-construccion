import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';

// La pantalla de Control de Cambios nacio sin su bloque de JS: tenia la tabla, la fila de filtros y
// los `onclick` de los modales, pero nadie inicializaba la DataTable ni definia `recargarTabla` /
// `cerrarTodosModales`. Nunca listo una sola solicitud, y el estado vacio decia la verdad solo
// porque la tabla `cambios` estaba vacia en toda la base. Este spec fija las dos mitades: que la
// tabla muestre lo que hay y que el modal se abra y se cierre sin ReferenceError.

const ID_SEMBRADO = 990001;
const TIPO_SEMBRADO = 'Cambio sembrado por el spec';

async function abrirPantalla(page) {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/control-cambios`);
  // `cargarDatosGeneralesPagina` rellena los ocultos; sin `baseDatos` no hay peticion que valga.
  await expect
    .poll(() => page.locator('#baseDatos').inputValue(), { timeout: 15_000 })
    .not.toBe('');
  return page.locator('#baseDatos').inputValue();
}

async function sembrar(page, db) {
  const res = await page.request.post(`${BASE_URL}/api/control-cambios/save?db=${db}`, {
    form: {
      opcion: 'nuevo',
      inputConsecutivo: String(ID_SEMBRADO),
      inputSolicitanteCambio: '1', // Obra
      inputFechaSolicitud: '2026-08-03',
      inputPrioridad: '1', // Alta
      inputTipoCambioAlcance: '1',
      inputResponsableSolucion: '1', // Obra
      inputJustificacion: TIPO_SEMBRADO,
      inputDescripcion: TIPO_SEMBRADO,
      inputAprobacion: '4', // En Estudio
    },
  });
  expect((await res.json()).respuesta).toBe('BIEN');
}

async function limpiar(page, db) {
  await page.request.post(`${BASE_URL}/api/control-cambios/save?db=${db}`, {
    form: { opcion: 'eliminar', Id: String(ID_SEMBRADO) },
  });
}

test.describe.serial('Control de Cambios lista sus solicitudes', () => {
  test('sin solicitudes muestra el estado vacio dentro de la tabla', async ({ page }) => {
    const db = await abrirPantalla(page);
    await limpiar(page, db);
    await page.reload();
    await expect(page.locator('#dt_cliente')).toBeVisible();
    // C-33 (Task 30): el estado vacio dejo de decir solo que no hay nada y ahora dice de donde
    // nace una solicitud. La asercion sigue al copy nuevo palabra por palabra, con la frase
    // entera y no un fragmento vago: si alguien la vacia o la vuelve a acortar, este caso cae.
    await expect(page.locator('#dt_cliente tbody')).toContainText(
      'Las solicitudes de cambio nacen en obra: cuando el diseño, el cliente o la interventoría '
      + 'piden algo distinto de lo contratado, regístralo aquí para tramitar su aprobación.'
    );
    // La fila estatica anterior convivia con las filas reales: no debe quedar rastro.
    await expect(page.locator('.cc-empty-state')).toHaveCount(0);
  });

  test('con una solicitud sembrada la muestra en la tabla', async ({ page }) => {
    const db = await abrirPantalla(page);
    await sembrar(page, db);
    await page.reload();
    const fila = page.locator('#dt_cliente tbody tr', { hasText: String(ID_SEMBRADO) });
    await expect(fila).toHaveCount(1);
    // Los codigos numericos de la base se muestran con su etiqueta, que es lo que filtra la cabecera.
    await expect(fila).toContainText('Obra');
    await expect(fila).toContainText('Alta');

    // El vendor de DataTables pinta toda fila de blanco y el adaptador solo cubria las pares: en
    // dark la primera fila salia blanca con texto claro encima, ilegible.
    const fondo = await fila.evaluate((tr) => getComputedStyle(tr).backgroundColor);
    expect(fondo).not.toBe('rgb(255, 255, 255)');

    await limpiar(page, db);
  });

  test('abrir y cerrar la orden de cambio no lanza errores de JS', async ({ page }) => {
    const errores = [];
    page.on('pageerror', (e) => errores.push(e.message));
    const db = await abrirPantalla(page);
    await sembrar(page, db);
    await page.reload();

    await page.locator('#dt_cliente tbody tr', { hasText: String(ID_SEMBRADO) }).first().click();
    await expect(page.locator('#modalordenDeCambio')).toBeVisible();
    await expect(page.locator('#inputConsecutivo')).toHaveValue(String(ID_SEMBRADO));

    await page.locator('#btn_cancelarOrden').click();
    await expect(page.locator('#modalordenDeCambio')).toBeHidden();

    expect(errores).toEqual([]);
    await limpiar(page, db);
  });
});
