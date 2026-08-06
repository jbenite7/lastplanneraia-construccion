/**
 * Elegir una opción en el `Selector` del PDC, que ya no es un `<select>` nativo.
 *
 * Sustituto directo de `locator('[data-testid=X]').selectOption(v)`: abre el popup, y si trae
 * caja de búsqueda —a partir de ocho opciones— escribe para acotar antes de hacer clic. Por eso
 * recibe la **etiqueta visible**, no el valor interno: es lo único que el usuario ve.
 */
export async function elegirEnSelector(page, testid, etiquetaVisible) {
  await page.locator(`[data-testid="${testid}"]`).click();
  const popup = page.locator('.pdc-selector-popup');
  await popup.waitFor({ state: 'visible' });
  const buscar = popup.locator('.pdc-lista-buscar');
  if (await buscar.count() > 0) await buscar.fill(etiquetaVisible);
  await popup.getByRole('option', { name: etiquetaVisible, exact: false }).first().click();
  // Timeout explícito: si el popup no cierra (p. ej. el bug del <label> que reenvía el click al
  // botón y lo reabre — ver diagnostico-vencimientos.md), esto falla en 5s con un mensaje claro en
  // vez de agotar los 120s del test entero disfrazado de sesión perdida.
  await popup.waitFor({ state: 'detached', timeout: 5000 });
}
