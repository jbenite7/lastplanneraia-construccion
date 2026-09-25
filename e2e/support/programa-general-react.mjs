import { expect } from '@playwright/test';

const TABLE_ROWS = 'table.programa-table-pro tbody tr.row-activity';
const DRAWER = '[role="dialog"][aria-label="Editor Contextual LPS"]';

export async function esperarTablaPg(page) {
  await expect(page.locator(TABLE_ROWS).first()).toBeVisible({ timeout: 45_000 });
}

export async function contarFilasPg(page) {
  await esperarTablaPg(page);
  return page.locator(TABLE_ROWS).count();
}

export async function abrirActividadPg(page, uniqueId) {
  const row = page.locator(`${TABLE_ROWS}[data-unique-id="${uniqueId}"]`);
  await expect(row, `La actividad ${uniqueId} debe existir en la tabla React`).toBeVisible();
  await row.click();
  await expect(page.locator(DRAWER)).toBeVisible();
}

export async function editarCampoDrawerPg(page, etiqueta, valor) {
  const field = page.getByLabel(etiqueta, { exact: true });
  await expect(field, `El Drawer debe exponer el campo ${etiqueta}`).toBeEnabled();
  const tag = await field.evaluate((element) => element.tagName.toLowerCase());
  if (tag === 'select') await field.selectOption(valor);
  else await field.fill(valor);
}

export async function guardarDrawerPg(page) {
  const save = page.getByRole('button', { name: /Guardar Cambios/i });
  await expect(save).toBeEnabled();
  await save.click();
  await expect(page.getByRole('status')).toContainText(/Cambios guardados con éxito/i);
}

export async function leerCampoFilaPg(page, uniqueId, columna) {
  const headers = page.locator('table.programa-table-pro thead th');
  const headerCount = await headers.count();
  let columnIndex = -1;
  for (let index = 0; index < headerCount; index += 1) {
    if ((await headers.nth(index).innerText()).trim() === columna) {
      columnIndex = index;
      break;
    }
  }
  if (columnIndex < 0) throw new Error(`La columna React ${columna} no está visible`);
  const row = page.locator(`${TABLE_ROWS}[data-unique-id="${uniqueId}"]`);
  return (await row.locator('td').nth(columnIndex).innerText()).trim();
}
