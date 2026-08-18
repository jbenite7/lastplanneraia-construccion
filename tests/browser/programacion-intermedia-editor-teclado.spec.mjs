/**
 * Contrato de teclado y anclaje del editor de celda Tom Select.
 *
 * Fija el comportamiento reparado el 2026-08-18 en
 * `public/js/HandsontableTomSelectEditor.js`, tras auditar la columna
 * Sub-Contratista de Programación Intermedia. Lo que cada prueba impide que
 * vuelva, medido antes de arreglarlo:
 *
 * - Enter no confirmaba: **añadía otro subcontratista**. Dos pulsaciones
 *   dejaban «TRANSCAR ANTIOQUIA SAS, ARQUITOP SAS» sin que nadie eligiera el
 *   segundo, porque al ocultarse el ya elegido la opción activa se corre sola.
 * - Tab confirmaba pero no avanzaba de celda —en una celda normal sí avanza— y
 *   dejaba el foco del navegador en `<body>`.
 * - ← y → cerraban el editor, así que corregir una letra del buscador
 *   destruía lo escrito.
 * - La ventana flotante se quedaba clavada al desplazar la grilla y acababa
 *   sobre OTRA actividad mientras seguía editando la de origen.
 *
 * Estas pruebas escriben en la celda, así que cada una devuelve el valor
 * original antes de terminar.
 *
 * El editor es compartido: `/programa-general-actualizar` usa la variante
 * simple del mismo archivo. Si algo de aquí cambia, esa pantalla también.
 */
import { test, expect } from '@playwright/test';
import { login, selectProject, changeWeek } from './support/session.mjs';
import { waitForGridReady, countGridRows } from './support/enablement-probe.mjs';

const PROYECTOS_CANDIDATOS = [
  'Preconstrucción Da Porto',
  'Optimización Aeropuerto JMC',
  'Da Porto',
  'Prueba',
];

const COLUMNA = 'Sub_Contratista';

async function abrirIntermediaConFilas(page) {
  await login(page);

  let elegido = null;
  for (const nombre of PROYECTOS_CANDIDATOS) {
    const tarjeta = page.locator('.project-item').filter({
      has: page.getByRole('heading', { name: nombre, exact: true }),
    });
    if (await tarjeta.count()) {
      await selectProject(page, { name: nombre });
      elegido = nombre;
      break;
    }
  }
  if (!elegido) {
    throw new Error(`Ningún proyecto candidato existe: ${PROYECTOS_CANDIDATOS.join(', ')}`);
  }

  await page.goto('/programacion-intermedia');
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });

  const maxSemana = Number(await page.locator('#Max_Semana').inputValue());
  for (let semana = maxSemana; semana >= 1; semana -= 1) {
    await changeWeek(page, semana, '/programacion-intermedia');
    await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
    if ((await countGridRows(page, 'PI')) > 0) {
      await waitForGridReady(page, 'PI');
      return;
    }
  }
  throw new Error(`Ninguna semana 1..${maxSemana} tiene filas en Programación Intermedia`);
}

/** Índice visual de la columna, leído del propio grid y no fijado a mano. */
function indiceColumna(page) {
  return page.evaluate((prop) => {
    const hot = window.PIHotModule.getHotInstance();
    return hot.propToCol(prop);
  }, COLUMNA);
}

function leerCelda(page, fila, col) {
  return page.evaluate(({ f, c }) => {
    const hot = window.PIHotModule.getHotInstance();
    const v = hot.getDataAtCell(f, c);
    return v === null || v === undefined ? '' : String(v);
  }, { f: fila, c: col });
}

async function restaurarCelda(page, fila, col, valor) {
  await page.evaluate(({ f, c, v }) => {
    const hot = window.PIHotModule.getHotInstance();
    hot.setDataAtCell(f, c, v);
  }, { f: fila, c: col, v: valor });
  await page.waitForTimeout(400); // deja salir el autoguardado por celda
}

/** Abre el editor con doble clic, como lo abre una persona. */
async function abrirEditor(page, fila, col) {
  const td = page.locator('#hot-container .ht_master tbody tr').nth(fila).locator('td').nth(col);
  await td.dblclick();
  await page.locator('.htTomSelectWrapper').waitFor({ state: 'visible', timeout: 5000 });
}

/**
 * El editor devuelve el foco a la celda en el tick siguiente al cierre (ver
 * `moverSeleccion`), así que el estado se consulta hasta que se estabiliza en
 * vez de leerse al vuelo: leerlo de inmediato mide la carrera, no el contrato.
 */
async function esperarEstado(page, comprobacion, mensaje) {
  let ultimo = null;
  await expect.poll(
    async () => {
      ultimo = await estadoEditor(page);
      // El estado observado viaja en el valor para que, al fallar, el informe
      // diga qué pasó de verdad en vez de un `false` mudo.
      return comprobacion(ultimo) ? 'ok' : JSON.stringify(ultimo);
    },
    { message: mensaje, timeout: 5000 },
  ).toBe('ok');
  return ultimo;
}

function estadoEditor(page) {
  return page.evaluate(() => {
    const w = document.querySelector('.htTomSelectWrapper');
    const abierto = !!w && getComputedStyle(w).display !== 'none';
    const hot = window.PIHotModule.getHotInstance();
    const sel = hot.getSelectedLast();
    return {
      abierto,
      topEditor: abierto ? parseFloat(w.style.top) : null,
      fila: sel ? sel[0] : null,
      col: sel ? sel[1] : null,
      etiquetaFoco: document.activeElement ? document.activeElement.tagName : null,
    };
  });
}

/**
 * Resalta la primera opción REAL por API, sin depender de dónde esté el ratón.
 *
 * Salta «➕ Crear …», que no es un valor sino una acción: seleccionarla abre
 * otro módulo en una pestaña nueva. Con la celda ya llena puede ser la única
 * opción que queda a la vista (las elegidas se ocultan), y entonces la prueba
 * medía otra cosa; por eso quien llama vacía la celda antes.
 */
function resaltarPrimeraOpcion(page) {
  return page.evaluate(() => {
    const ts = document.querySelector('.htTomSelectWrapper select').tomselect;
    const opciones = [...ts.dropdown_content.querySelectorAll('.option')].filter((o) => {
      const v = o.getAttribute('data-value') || '';
      return !v.includes('➕') && !v.includes('Crear');
    });
    if (!opciones.length) throw new Error('El desplegable no ofrece ninguna opción real');
    ts.setActiveOption(opciones[0]);
    return opciones[0].getAttribute('data-value');
  });
}

test.describe('Editor Tom Select · contrato de teclado y anclaje', () => {
  test.beforeEach(async ({ page }) => {
    await abrirIntermediaConFilas(page);
  });

  test('Enter asigna solo lo resaltado, confirma y cierra', async ({ page }) => {
    const col = await indiceColumna(page);
    const original = await leerCelda(page, 0, col);
    // Se parte de celda vacía: lo elegido se oculta de la lista, así que una
    // celda llena cambia qué opciones hay y la prueba dejaría de ser la misma.
    await restaurarCelda(page, 0, col, '');

    await abrirEditor(page, 0, col);
    const resaltado = await resaltarPrimeraOpcion(page);
    await page.keyboard.press('Enter');

    const estado = await esperarEstado(
      page,
      (e) => !e.abierto && e.etiquetaFoco === 'TD',
      'Enter debe cerrar el editor y devolver el foco a la celda, no a <body>',
    );
    expect(estado.abierto).toBe(false);
    expect(estado.etiquetaFoco).toBe('TD');

    const valor = await leerCelda(page, 0, col);
    const asignados = valor.split(',').map((s) => s.trim()).filter(Boolean);
    expect(asignados, 'una pulsación asigna exactamente uno').toEqual([resaltado]);

    await restaurarCelda(page, 0, col, original);
  });

  test('la variante simple obedece el mismo contrato', async ({ page }) => {
    // Responsable AIA usa `tomSelectSingle`, la misma variante con la que
    // `/programa-general-actualizar` edita su columna. Se cubre aquí porque es
    // donde hay datos con los que ejercitarla.
    const col = await page.evaluate(
      () => window.PIHotModule.getHotInstance().propToCol('Responsable_AIA'),
    );
    const original = await leerCelda(page, 0, col);
    await restaurarCelda(page, 0, col, '');

    await abrirEditor(page, 0, col);
    const resaltado = await resaltarPrimeraOpcion(page);
    await page.keyboard.press('Enter');

    const estado = await esperarEstado(
      page,
      (e) => !e.abierto && e.etiquetaFoco === 'TD',
      'la variante simple también cierra con Enter y devuelve el foco',
    );
    expect(estado.abierto).toBe(false);
    expect(await leerCelda(page, 0, col)).toBe(resaltado);

    await restaurarCelda(page, 0, col, original);
  });

  test('Tab confirma y avanza a la celda siguiente', async ({ page }) => {
    const col = await indiceColumna(page);
    const original = await leerCelda(page, 0, col);

    await abrirEditor(page, 0, col);
    await page.keyboard.press('Tab');

    const estado = await esperarEstado(
      page,
      (e) => !e.abierto && e.col === col + 1,
      'Tab avanza una columna, como en el resto del grid',
    );
    expect(estado.col).toBe(col + 1);
    expect(estado.etiquetaFoco).toBe('TD');

    await restaurarCelda(page, 0, col, original);
  });

  test('las flechas horizontales no destruyen lo escrito en el buscador', async ({ page }) => {
    const col = await indiceColumna(page);

    await abrirEditor(page, 0, col);
    await page.keyboard.type('AR');
    await page.keyboard.press('ArrowLeft');

    const estado = await estadoEditor(page);
    expect(estado.abierto, 'con texto escrito, ← mueve el cursor y NO cierra').toBe(true);

    const texto = await page.evaluate(
      () => document.querySelector('.htTomSelectWrapper .ts-control input').value,
    );
    expect(texto).toBe('AR');

    await page.keyboard.press('Escape');
  });

  test('con el buscador vacío, → confirma y avanza', async ({ page }) => {
    const col = await indiceColumna(page);
    const original = await leerCelda(page, 0, col);

    await abrirEditor(page, 0, col);
    await page.keyboard.press('ArrowRight');

    const estado = await esperarEstado(
      page,
      (e) => !e.abierto && e.col === col + 1,
      'con el buscador vacío, → cierra el editor y avanza',
    );
    expect(estado.col).toBe(col + 1);

    await restaurarCelda(page, 0, col, original);
  });

  test('la ventana flotante sigue a su celda al desplazar la grilla', async ({ page }) => {
    const col = await indiceColumna(page);
    const filas = await countGridRows(page, 'PI');
    test.skip(filas < 4, 'hacen falta filas suficientes para desplazar');

    await abrirEditor(page, 0, col);
    const antes = await page.evaluate(() => {
      const w = document.querySelector('.htTomSelectWrapper');
      const td = document.querySelector('#hot-container .ht_master td.current');
      return { editor: parseFloat(w.style.top), celda: td.getBoundingClientRect().top };
    });
    expect(Math.abs(antes.editor - antes.celda), 'al abrir ya está pegado').toBeLessThan(2);

    // Se desplaza por la API de la grilla, no con la rueda: la rueda depende de
    // dónde caiga el puntero y en una corrida sin cabeza puede no desplazar
    // nada, con lo que la prueba pasaría sin haber comprobado nada.
    await page.evaluate(() => {
      const hot = window.PIHotModule.getHotInstance();
      hot.scrollViewportTo({ row: Math.min(3, hot.countRows() - 1), verticalSnap: 'top' });
    });
    await page.waitForTimeout(400);

    const despues = await page.evaluate(() => {
      const w = document.querySelector('.htTomSelectWrapper');
      const abierto = !!w && getComputedStyle(w).display !== 'none';
      if (!abierto) return { abierto };
      const td = document.querySelector('#hot-container .ht_master td.current');
      return {
        abierto,
        editor: parseFloat(w.style.top),
        celda: td ? td.getBoundingClientRect().top : null,
      };
    });

    // Dos desenlaces admisibles, y ninguno es «quedarse clavado sobre otra
    // fila»: o el editor sigue a su celda, o se cierra porque la celda dejó de
    // estar a la vista.
    if (despues.abierto) {
      expect(despues.celda, 'la celda sigue existiendo si el editor sigue abierto').not.toBeNull();
      expect(
        Math.abs(despues.editor - despues.celda),
        'tras desplazar, el editor sigue sobre su celda',
      ).toBeLessThan(2);
    }

    await page.keyboard.press('Escape');
  });
});
