// El estado de error de #formulario_nuevo NO existe en reposo: solo aparece cuando
// submitNewActivity() valida un formulario incompleto. Un barrido que abra el modal y
// mida sin pulsar Guardar encuentra 0 nodos `.ps-field-error` y da un falso verde, asi
// que cada bloque autovalida primero que la sonda esta midiendo el estado real.
//
// Lo que se asierta aqui es la atadura programatica campo-a-campo (WCAG 3.3.1 / 4.1.2):
// el borde rojo y el aviso global ya existian, pero `aria-invalid` y `aria-describedby`
// venian a null en los campos marcados, asi que un lector de pantalla que recorra el
// formulario campo por campo no oye cual esta mal al posarse en el.

import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { VIEWPORT, openModal } from './support/contrast.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Los cuatro obligatorios que marca la rama `missing` de submitNewActivity().
const REQUIRED_FIELDS = ['#idNuevoDisplay', '#Actividad', '#Sub_Contratista', '#Responsable_AIA'];

test.use({ viewport: VIEWPORT });

async function openNewActivityModal(page) {
  await loginAndSelectProject(page, project);
  await page.goto('/programacion-semanal');
  await page.waitForLoadState('networkidle').catch(() => {});
  await openModal(page, 'formulario_nuevo');
}

function ariaStateOf(page, selectors) {
  return page.evaluate((list) => {
    const out = {};
    for (const selector of list) {
      const el = document.querySelector(selector);
      out[selector] = el
        ? {
          invalid: el.getAttribute('aria-invalid'),
          describedby: el.getAttribute('aria-describedby'),
          errorClass: el.classList.contains('ps-field-error'),
          describedbyText: (el.getAttribute('aria-describedby') || '')
            .split(/\s+/)
            .filter(Boolean)
            .map((id) => (document.getElementById(id) || {}).textContent || '')
            .join(' ')
            .trim(),
        }
        : null;
    }
    return out;
  }, selectors);
}

const countErrors = (page) => page.locator('#formulario_nuevo .ps-field-error').count();

// showFeedback('error', ...) levanta un SweetAlert2 real cuyo backdrop intercepta
// los clics siguientes. Sin cerrarlo, el segundo submit se queda esperando 120s y
// la prueba miente sobre lo que estaba midiendo.
async function dismissNotice(page) {
  const confirm = page.locator('.swal2-container .swal2-confirm');
  if (await confirm.count()) {
    await confirm.first().click();
    await page.locator('.swal2-container').waitFor({ state: 'detached', timeout: 10_000 });
  }
}

test('#formulario_nuevo: los campos vacios quedan atados a su mensaje de error', async ({ page }) => {
  await openNewActivityModal(page);

  // Autovalidacion de la sonda: en reposo no hay estado de error que medir.
  expect(await countErrors(page), 'el modal no deberia abrir con campos en error').toBe(0);

  await page.locator('#btn_guardar_nueva_actividad').click();

  // Autovalidacion de la sonda: si esto es 0, el submit no provoco el estado y
  // todo lo que venga despues seria un falso verde.
  expect(await countErrors(page), 'el submit vacio no provoco el estado de error').toBe(
    REQUIRED_FIELDS.length,
  );

  const state = await ariaStateOf(page, [...REQUIRED_FIELDS, '#idNuevo']);

  for (const selector of REQUIRED_FIELDS) {
    expect(state[selector], `${selector} no existe`).not.toBeNull();
    expect(state[selector].errorClass, `${selector} sin .ps-field-error`).toBe(true);
    expect(state[selector].invalid, `${selector} sin aria-invalid`).toBe('true');
    expect(state[selector].describedby, `${selector} sin aria-describedby`).toBeTruthy();
    expect(
      state[selector].describedbyText.length,
      `${selector} apunta a un mensaje vacio`,
    ).toBeGreaterThan(0);
  }

  // Decision declarada: #idNuevo es type="hidden" — el UA lo saca del arbol de
  // accesibilidad, asi que marcarlo seria inerte. El par visible/etiquetado es
  // #idNuevoDisplay y ese es el unico que se marca.
  expect(state['#idNuevo'].invalid, '#idNuevo (hidden) no debe llevar aria-invalid').toBeNull();
});

test('#formulario_nuevo: la marca desaparece al corregir y reenviar', async ({ page }) => {
  await openNewActivityModal(page);

  await page.locator('#btn_guardar_nueva_actividad').click();
  expect(await countErrors(page), 'el submit vacio no provoco el estado de error').toBe(
    REQUIRED_FIELDS.length,
  );

  // Se corrigen los cuatro obligatorios y se deja Compromiso invalido a proposito:
  // asi el segundo submit vuelve a fallar sin llegar nunca al POST, y la prueba no
  // escribe una actividad en la base compartida.
  await page.evaluate(() => {
    const pick = (selector) => {
      const el = document.querySelector(selector);
      const option = Array.from(el.options).find((candidate) => candidate.value !== '');
      el.value = option ? option.value : '';
      return el.value;
    };
    document.querySelector('#idNuevo').value = '999999';
    document.querySelector('#idNuevoDisplay').value = '999999';
    document.querySelector('#Actividad').value = 'Sonda de accesibilidad';
    pick('#Sub_Contratista');
    pick('#Responsable_AIA');
    document.querySelector('#Compromiso').value = '0';
  });

  await dismissNotice(page);
  await page.locator('#btn_guardar_nueva_actividad').click();

  const state = await ariaStateOf(page, [...REQUIRED_FIELDS, '#Compromiso']);

  for (const selector of REQUIRED_FIELDS) {
    expect(state[selector].errorClass, `${selector} conservo .ps-field-error`).toBe(false);
    expect(state[selector].invalid, `${selector} conservo aria-invalid`).toBeNull();
    expect(state[selector].describedby, `${selector} conservo aria-describedby`).toBeNull();
  }

  // Y la rama de compromiso invalido marca su campo con el mismo contrato.
  expect(state['#Compromiso'].errorClass, '#Compromiso sin .ps-field-error').toBe(true);
  expect(state['#Compromiso'].invalid, '#Compromiso sin aria-invalid').toBe('true');
  expect(state['#Compromiso'].describedby, '#Compromiso sin aria-describedby').toBeTruthy();
  expect(state['#Compromiso'].describedbyText.length).toBeGreaterThan(0);
});

test('#formulario_nuevo: reabrir el modal deja los campos limpios', async ({ page }) => {
  await openNewActivityModal(page);

  await page.locator('#btn_guardar_nueva_actividad').click();
  expect(await countErrors(page), 'el submit vacio no provoco el estado de error').toBe(
    REQUIRED_FIELDS.length,
  );

  // Cerrar y volver a abrir por el boton real: normalizeNewActivityForm() corre en
  // ese camino y es quien debe dejar el formulario sin marcas heredadas.
  await dismissNotice(page);
  await page.evaluate(() => window.jQuery('#formulario_nuevo').modal('hide'));
  await page.waitForTimeout(350);
  await page.locator('#btn_agregar_actividad').click();
  await page.waitForTimeout(450);

  expect(await countErrors(page), 'el modal reabierto arrastro campos en error').toBe(0);

  const state = await ariaStateOf(page, REQUIRED_FIELDS);
  for (const selector of REQUIRED_FIELDS) {
    expect(state[selector].invalid, `${selector} arrastro aria-invalid`).toBeNull();
    expect(state[selector].describedby, `${selector} arrastro aria-describedby`).toBeNull();
  }
});
