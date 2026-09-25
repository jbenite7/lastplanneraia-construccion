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
  await expect(page.locator('.pro-toast[role="status"]')).toContainText(/Cambios guardados con éxito/i);
}

export async function abrirLeyendaPg(page) {
  await page.getByRole('button', { name: 'Leyenda', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: /Gu[ií]a Operativa - Programa General/i });
  await expect(dialog, 'La Leyenda debe abrirse desde la barra de herramientas React').toBeVisible();
  return dialog;
}

export async function postearActualizacionPgConCsrf(page, url, body, csrfToken) {
  return page.evaluate(
    async ({ apiUrl, apiBody, token }) => {
      const formData = new URLSearchParams();
      Object.entries(apiBody).forEach(([key, value]) => formData.set(key, String(value)));
      formData.set('_csrf_token', token);
      formData.set('csrf_token', token);
      const response = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': token,
        },
        body: formData.toString(),
      });
      const text = await response.text();
      let payload;
      try {
        payload = JSON.parse(text);
      } catch {
        payload = { parseError: true, text };
      }
      return { ok: response.ok, status: response.status, payload };
    },
    { apiUrl: url, apiBody: body, token: csrfToken },
  );
}

export async function leerCampoFilaPg(page, uniqueId, columna) {
  const headers = page.locator('table.programa-table-pro thead th');
  const headerCount = await headers.count();
  let columnIndex = -1;
  for (let index = 0; index < headerCount; index += 1) {
    if ((await headers.nth(index).getAttribute('aria-label') || await headers.nth(index).innerText()).trim() === columna) {
      columnIndex = index;
      break;
    }
  }
  if (columnIndex < 0) throw new Error(`La columna React ${columna} no está visible`);
  const row = page.locator(`${TABLE_ROWS}[data-unique-id="${uniqueId}"]`);
  return (await row.locator('td').nth(columnIndex).innerText()).trim();
}
