// tests/browser/pdc-v2-equipos.spec.mjs — Ola 2: clasificar equipo alquilado vs comprado.
//
// La cola vive en el maestro de insumos, que es un catálogo GLOBAL: no hay proyecto que aislar y
// tampoco hay pantalla nueva, sólo una sección más en las pestañas que el maestro ya tenía. Se entra
// por el sandbox sacrificable igual que el resto de los specs del PDC v2, pero lo que se comprueba es
// el catálogo de la empresa.
//
// NO destructivo respecto a los equipos reales: siembra sus propios insumos con una marca en
// `creado_por`, opera sobre ellos y los borra al final. Lo que sí toca son filas del catálogo global
// — por eso las crea y las limpia él mismo, en vez de clasificar equipos que alguien tenga que
// desclasificar después.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

const MARCA = 'e2e-equipos-ola2';
const SIN_CLASIFICAR = 'EQUIPO (SIN CLASIFICAR)';
const ALQUILADO = 'ALQUILER EQUIPOS';

/** Tres equipos con pista de alquiler y dos sin pista, para poder probar el lote y el resto. */
function sembrar() {
  const filas = [
    ['E2E EQUIPO ALQ UNO', 'ALQUILER MAQUINARIA Y EQUIPOS'],
    ['E2E EQUIPO ALQ DOS', 'ALQUILER MAQUINARIA Y EQUIPOS'],
    ['E2E EQUIPO ALQ TRES', 'ALQUILER BIENES MUEBLES'],
    ['E2E EQUIPO MUDO UNO', 'MTTO COMPRA MAQUINARIA Y EQUIPO'],
    ['E2E EQUIPO MUDO DOS', 'MAT-HERRAMIENTA EQUIPO MENOR Y CONSUMIBLES'],
  ];
  // `sqlEnApp` recibe PHP, no SQL: los valores viajan por prepared statement, como en el resto del repo.
  const php = filas.map(([desc, agr]) => (
    `$db->query('INSERT INTO general_maestro_insumos `
    + `(descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, activo, creado_por, created_at) `
    + `VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())', `
    + `['${desc}', '${desc}', 'UN', '${agr}', '${agr}', '${SIN_CLASIFICAR}', '${MARCA}']);`
  )).join('');
  sqlEnApp(php);
}

function limpiar() {
  sqlEnApp(`$db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', ['${MARCA}']);`);
}

/** Cuenta filas sembradas con un tipo de recurso dado. Devuelve el número, ya parseado. */
function contar(tipoRecurso, exigirAutor = false) {
  const extra = exigirAutor ? ' AND clasificado_at IS NOT NULL' : '';
  const salida = sqlEnApp(
    `echo (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos `
    + `WHERE creado_por = ? AND tipo_recurso = ?${extra}', ['${MARCA}', '${tipoRecurso}'])->fetchColumn();`,
  );
  return Number(salida.trim());
}

/**
 * Abre la cola, esté donde esté.
 *
 * En una obra CON presupuesto la cola es una pestaña más del maestro. En una obra SIN presupuesto la
 * pantalla del maestro muestra su aviso de «importa un presupuesto» — y la cola aparece igual, porque
 * es del catálogo GLOBAL de la empresa y no depende del presupuesto de ninguna obra. Esconderla ahí
 * era un bug: dejaba el tapón sin puerta de entrada precisamente en una obra nueva, que es donde
 * alguien va a estar montando el maestro.
 */
async function abrirCola(page) {
  // La pantalla llega a su forma final en dos pasos: `cargarEquipos()` responde antes que
  // `cargar()`, así que la pestaña puede existir un instante y desaparecer cuando el maestro
  // descubre que la obra no tiene presupuesto (entonces la cola se pinta suelta, sin pestaña).
  // Reintentar en vez de esperar un estado concreto cubre las dos formas sin acoplarse a cuál llega.
  const cola = page.getByTestId('pdc-equipos-cola');
  for (let intento = 0; intento < 5; intento++) {
    if (await cola.isVisible().catch(() => false)) break;
    const pestana = page.getByRole('tab', { name: /Clasificar equipos/ });
    if ((await pestana.count()) > 0) {
      await pestana.click({ timeout: 5_000 }).catch(() => {});
    }
    await page.waitForTimeout(1_000);
  }
  await expect(cola).toBeVisible();
}

test.beforeEach(() => { limpiar(); sembrar(); });
test.afterEach(() => { limpiar(); });

test('la cola del maestro clasifica equipos en lote, con la pista de SINCO como evidencia', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/plan-compras#/ensamble/maestro');

  await abrirCola(page);
  const cola = page.getByTestId('pdc-equipos-cola');

  // La evidencia se muestra, no se esconde: la agrupación cruda de SINCO y la sugerencia derivada.
  await expect(cola).toContainText('ALQUILER MAQUINARIA Y EQUIPOS');
  await expect(cola).toContainText('Equipo alquilado');
  // Lo que no tiene pista NO se sugiere. No se comprueba en el DOM porque AG Grid virtualiza las
  // filas y los «sin pista» quedan al final de la cola entera; se comprueba donde vive el dato.
  expect(contar(SIN_CLASIFICAR)).toBe(5);

  // El encabezado lleva el conteo del tapón, y cuadra con la base.
  const encabezado = page.getByRole('heading', { name: /Clasificar equipos \(\d+\)/ });
  await expect(encabezado).toBeVisible();
  const antes = Number((await encabezado.textContent()).match(/\((\d+)\)/)[1]);

  // El atajo SELECCIONA, no guarda: el botón de clasificar sigue siendo el que escribe.
  await page.getByTestId('pdc-equipos-sel-alquiler').click();

  // Nada se guardó todavía: los cinco siguen sin clasificar en la base.
  expect(contar(SIN_CLASIFICAR)).toBe(5);

  await page.getByTestId('pdc-equipos-clasificar-alquilado').click();
  await expect(page.getByRole('status')).toContainText(/clasificad/);

  // La cola baja exactamente en los que se clasificaron.
  await expect(page.getByRole('heading', { name: `Clasificar equipos (${antes - 3})` })).toBeVisible();

  // Y sobrevive a recargar, porque está en la base y no en memoria.
  expect(contar(ALQUILADO, true)).toBe(3);

  await page.reload();
  await abrirCola(page);
  // Los tres clasificados salieron de la cola y los dos sin pista siguen ahí. Se mide en la base:
  // el DOM sólo tiene las filas visibles de una cola de cientos.
  expect(contar(SIN_CLASIFICAR)).toBe(2);
  expect(contar(ALQUILADO, true)).toBe(3);

  await page.screenshot({ path: 'tests/browser/__screenshots__/pdc-v2-equipos-cola.png', fullPage: false });

  await logout(page);
});

test('sin overflow horizontal a 1180px, el viewport desktop canónico', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await loginAndSelectProject(page, project);
  await page.goto('/plan-compras#/ensamble/maestro');
  await abrirCola(page);

  const desborda = await page.evaluate(
    () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
  );
  expect(desborda).toBe(false);

  await logout(page);
});
