// El estado de error de #formularioEditarContratos NO existe en reposo: solo aparece
// cuando validatePackageQuantities() encuentra una cantidad no entera en una fila con
// paquete elegido. Un barrido que abra el modal y mida sin pulsar Guardar encuentra 0
// nodos [aria-invalid="true"] y da un falso verde, asi que cada bloque autovalida
// primero que la sonda esta midiendo el estado real (0 en reposo, >0 provocado).
//
// Lo que se asierta aqui es la DESCRIPCION ACCESIBLE del campo invalido, leida del
// arbol de accesibilidad de Chromium (CDP), no del DOM. Medido antes de tocar nada:
// el campo llevaba `aria-invalid="true"` y `setCustomValidity(...)` puesto — con
// `element.validationMessage` no vacio y `validity.customError = true` — y aun asi su
// nodo AX llegaba con `description: null`. Chromium NO expone el mensaje de
// `setCustomValidity()` como descripcion accesible, y como #btn_guardar_contratos es
// `type="button"` (no hay submit nativo ni `reportValidity()`), tampoco sale la burbuja
// del UA. El aviso visible vive en un `.mensaje` global que no nombra el campo. WCAG
// 3.3.1 (Error Identification) y 4.1.2 (Name, Role, Value).

import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { VIEWPORT, openModal } from './support/contrast.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test.use({ viewport: VIEWPORT });

// Ningun camino de esta suite debe llegar al POST de guardado: la validacion falla
// antes. El contador lo demuestra en vez de suponerlo.
async function blockContractSaves(page) {
  const state = { count: 0 };
  await page.route('**/api/contratos/save**', async (route) => {
    const body = route.request().postData() || '';
    // Los catalogos de paquetes/recursos entran por el mismo endpoint y son lecturas.
    if (/opcion=actualizar(ListadoPaquetesContratacion|InsumosRecursos)/.test(body)) {
      await route.continue();
      return;
    }
    state.count += 1;
    await route.fulfill({ json: { respuesta: 'BIEN' } });
  });
  return state;
}

async function openContractModal(page) {
  await loginAndSelectProject(page, project);
  await page.goto('/contratos');
  await page.waitForLoadState('networkidle').catch(() => {});
  const saves = await blockContractSaves(page);
  // La semana canonica de Da Porto no trae filas de contrato, asi que el modal se abre
  // por el mismo camino que usan contratos-modal-header-dark.mjs y
  // modales-dark-homologacion.mjs: el marcado lo renderiza el servidor.
  await openModal(page, 'modalEditarContratos');
  // Sin modalidad marcada, updateSections() deja las cuatro secciones ocultas: `:visible`
  // no ve ninguna fila y validatePackageQuantities() pasaria trivialmente.
  //
  // Hay que esperar a las DOS recargas de catalogo que dispara el cambio de modalidad:
  // su `.done` hace `$select.html(options).val(current)`, que borra el <option> inyectado
  // por fillSlot() y deja el paquete vacio. Si la respuesta llega despues del clic, la
  // fila deja de validarse y la prueba mide 0 marcas — falso rojo intermitente (visto).
  const catalogs = ['actualizarListadoPaquetesContratacion', 'actualizarInsumosRecursos']
    .map((opcion) => page.waitForResponse((response) => response.url().includes('/api/contratos/save')
      && (response.request().postData() || '').includes(`opcion=${opcion}`), { timeout: 30000 }));
  await page.evaluate(() => {
    window.jQuery('#modalidadSI, #modalidadMO, #modalidadS, #modalidadOC')
      .prop('checked', false).prop('disabled', false);
    window.jQuery('#modalidadMO').prop('checked', true).trigger('change');
  });
  await Promise.all(catalogs);
  await expect(page.locator('#formularioEditarContratos .ct-contract-row:visible').first())
    .toBeVisible({ timeout: 15000 });
  return saves;
}

// Elegir paquete en un slot lo hace visible (el handler de .ct-package-select llama a
// syncProgressivePackageSlots), y solo las filas con paquete elegido se validan.
async function fillSlot(page, index, quantity) {
  await page.evaluate(({ i, q }) => {
    const section = [...document.querySelectorAll('.ct-contract-section')]
      .find((candidate) => getComputedStyle(candidate).display !== 'none');
    const select = section.querySelector(`.ct-contract-row[data-slot-index="${i}"] .ct-package-select`);
    const value = `Paquete sonda a11y ${i}`;
    if (![...select.options].some((option) => option.value === value)) {
      select.append(new Option(value, value));
    }
    select.value = value;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    const row = section.querySelector(`.ct-contract-row[data-slot-index="${i}"]`);
    row.querySelector('.ct-contract-quantity').value = q;
  }, { i: index, q: quantity });
}

const invalidCount = (page) => page.locator('#formularioEditarContratos [aria-invalid="true"]').count();

// La unica fuente de verdad de lo que oye un lector de pantalla. El DOM puede llevar
// `aria-describedby` y aun asi no producir descripcion (id inexistente, ancla vacia).
async function axQuantityNodes(page) {
  const cdp = await page.context().newCDPSession(page);
  await cdp.send('Accessibility.enable');
  const { nodes } = await cdp.send('Accessibility.getFullAXTree');
  await cdp.detach();
  const byName = new Map();
  for (const node of nodes) {
    const name = node.name?.value || '';
    if (node.ignored || node.role?.value !== 'spinbutton') continue;
    if (!/Cantidad de contratos/i.test(name)) continue;
    byName.set(name, {
      name,
      invalid: node.properties?.find((p) => p.name === 'invalid')?.value?.value ?? 'false',
      description: node.description?.value ?? null,
    });
  }
  return byName;
}

const nodeFor = (map, slot) => [...map.values()]
  .find(({ name }) => name.trim().endsWith(` ${slot}`));

test('#formularioEditarContratos: la cantidad invalida expone su propia descripcion accesible', async ({ page }) => {
  const saves = await openContractModal(page);

  // Autovalidacion de la sonda: en reposo no hay estado de error que medir.
  expect(await invalidCount(page), 'el modal no deberia abrir con campos en error').toBe(0);

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');

  // Autovalidacion de la sonda: si esto es 0, el submit no provoco el estado y todo lo
  // que venga despues seria un falso verde.
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBeGreaterThan(0);

  const ax = await axQuantityNodes(page);
  const flagged = nodeFor(ax, 1);
  expect(flagged, 'el spinbutton de la fila 1 no esta en el arbol de accesibilidad').toBeTruthy();
  expect(flagged.invalid, 'el arbol no marca el campo como invalido').toBe('true');
  expect(
    (flagged.description || '').trim().length,
    'el campo invalido llega al arbol de accesibilidad sin descripcion propia',
  ).toBeGreaterThan(0);

  expect(saves.count, 'la validacion no debe llegar al POST de guardado').toBe(0);
});

test('#formularioEditarContratos: la marca desaparece al corregir y se queda solo donde falta', async ({ page }) => {
  const saves = await openContractModal(page);

  // Dos filas invalidas a la vez. Al corregir solo la primera, el segundo submit vuelve
  // a fallar por la segunda: se comprueban las dos direcciones sin escribir en la base.
  await fillSlot(page, 1, '');
  await fillSlot(page, 2, '0');
  await page.locator('#btn_guardar_contratos').click();
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBe(2);

  await page.evaluate(() => {
    const section = [...document.querySelectorAll('.ct-contract-section')]
      .find((candidate) => getComputedStyle(candidate).display !== 'none');
    section.querySelector('.ct-contract-row[data-slot-index="1"] .ct-contract-quantity').value = '3';
  });
  await page.locator('#btn_guardar_contratos').click();
  expect(await invalidCount(page), 'la fila corregida conservo la marca').toBe(1);

  const dom = await page.evaluate(() => {
    const section = [...document.querySelectorAll('.ct-contract-section')]
      .find((candidate) => getComputedStyle(candidate).display !== 'none');
    const read = (i) => {
      const el = section.querySelector(`.ct-contract-row[data-slot-index="${i}"] .ct-contract-quantity`);
      return {
        invalid: el.getAttribute('aria-invalid'),
        describedby: el.getAttribute('aria-describedby'),
        anchorText: (el.getAttribute('aria-describedby') || '')
          .split(/\s+/).filter(Boolean)
          .map((id) => (document.getElementById(id) || {}).textContent || '')
          .join(' ').trim(),
        validationMessage: el.validationMessage,
      };
    };
    return { one: read(1), two: read(2) };
  });

  expect(dom.one.invalid, 'la fila corregida conservo aria-invalid').toBeNull();
  expect(dom.one.describedby, 'la fila corregida conservo aria-describedby').toBeNull();
  expect(dom.one.validationMessage, 'la fila corregida conservo el customValidity').toBe('');
  expect(dom.two.invalid, 'la fila que sigue mal perdio aria-invalid').toBe('true');
  expect(dom.two.anchorText.length, 'la fila que sigue mal apunta a un mensaje vacio').toBeGreaterThan(0);

  const ax = await axQuantityNodes(page);
  expect(nodeFor(ax, 1).invalid, 'el arbol sigue marcando invalida la fila corregida').toBe('false');
  expect((nodeFor(ax, 1).description || '').trim(), 'la fila corregida conservo descripcion').toBe('');
  expect((nodeFor(ax, 2).description || '').trim().length).toBeGreaterThan(0);

  expect(saves.count, 'la validacion no debe llegar al POST de guardado').toBe(0);
});

test('#formularioEditarContratos: cerrar y reabrir el modal no arrastra marcas', async ({ page }) => {
  await openContractModal(page);

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBeGreaterThan(0);

  // El handler `hidden.bs.modal` es el segundo camino de limpieza: si solo limpiara el
  // que revalida, un campo cerrado en error volveria anunciado como invalido.
  await page.evaluate(() => window.jQuery('#modalEditarContratos').modal('hide'));
  await page.waitForTimeout(400);

  const afterHide = await page.evaluate(() => {
    const el = document.getElementById('cantidadMO1');
    return {
      invalid: el.getAttribute('aria-invalid'),
      describedby: el.getAttribute('aria-describedby'),
      anchorText: (document.getElementById('ct-error-cantidadMO1') || {}).textContent || '',
      validationMessage: el.validationMessage,
    };
  });
  expect(afterHide.invalid, 'el cierre del modal no retiro aria-invalid').toBeNull();
  expect(afterHide.describedby, 'el cierre del modal no retiro aria-describedby').toBeNull();
  expect(afterHide.anchorText.trim(), 'el cierre del modal dejo texto en el ancla').toBe('');
  expect(afterHide.validationMessage, 'el cierre del modal no retiro el customValidity').toBe('');
});

// Tercer camino de limpieza: el reset que corre al abrir el modal por el boton "editar"
// real (public/js/modules/contratos/hot.js). Es el unico que vive fuera de la vista, y
// no lo cubre ningun bloque anterior porque la semana de compras que resuelve /contratos
// para Da Porto no trae filas en este entorno. Por eso la lista se sirve desde un fixture
// de ruta: la prueba ejercita el handler real sin depender del estado de la base.
test('#formularioEditarContratos: abrir por el boton editar barre las marcas heredadas', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.route('**/api/contratos/list**', async (route) => {
    await route.fulfill({
      json: {
        data: [{
          Id: 424242,
          codigo: 1,
          actividad: 'Actividad sonda a11y',
          descripcionActividad: 'Fila servida por fixture, no toca la base.',
          actividadInicio: '1',
          nombreActividadInicio: 'Actividad sonda a11y',
          fechaInicio: '2026-10-21',
          tipoContrato: 'MO',
          semanaActualizacion: 1,
          contratosAsociados: '',
        }],
      },
    });
  });
  const saves = await blockContractSaves(page);
  await page.goto('/contratos');
  await page.waitForLoadState('networkidle').catch(() => {});
  await expect(page.locator('#hot-container button.editar').first()).toBeVisible({ timeout: 20000 });

  // Se siembra a mano el estado completo de error: si el reset solo retirase parte, el
  // campo quedaria atado a un mensaje que ya no aplica.
  await page.evaluate(() => {
    const el = document.getElementById('cantidadMO1');
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', 'ct-error-cantidadMO1');
    document.getElementById('ct-error-cantidadMO1').textContent = 'suciedad heredada';
    el.setCustomValidity('suciedad heredada');
  });
  expect(await invalidCount(page), 'la sonda no logro sembrar el estado de error').toBeGreaterThan(0);

  await page.locator('#hot-container button.editar').first().click();
  await expect(page.locator('#modalEditarContratos')).toBeVisible({ timeout: 20000 });
  await page.waitForTimeout(1200);

  const state = await page.evaluate(() => {
    const el = document.getElementById('cantidadMO1');
    return {
      invalid: el.getAttribute('aria-invalid'),
      describedby: el.getAttribute('aria-describedby'),
      anchorText: (document.getElementById('ct-error-cantidadMO1') || {}).textContent || '',
      validationMessage: el.validationMessage,
      value: el.value,
    };
  });
  expect(state.invalid, 'el reset de hot.js no retiro aria-invalid').toBeNull();
  expect(state.describedby, 'el reset de hot.js no retiro aria-describedby').toBeNull();
  expect(state.anchorText.trim(), 'el reset de hot.js dejo texto en el ancla').toBe('');
  expect(state.validationMessage, 'el reset de hot.js no retiro el customValidity').toBe('');
  expect(state.value, 'el reset de hot.js dejo de restaurar la cantidad por defecto').toBe('1');
  expect(saves.count, 'abrir el modal no debe disparar un guardado').toBe(0);
});

test('#formularioEditarContratos: las anclas de error no ocupan layout', async ({ page }) => {
  await openContractModal(page);

  // `.sr-only` llega por access.css (capa `utilities`) a traves del agregador del design
  // system, no por un <link> propio de /contratos. Si esa cadena se rompiera, las 20
  // anclas se verian como texto suelto dentro del modal.
  const anchors = await page.evaluate(() => {
    const list = [...document.querySelectorAll('#formularioEditarContratos .ct-field-error-message')];
    return {
      count: list.length,
      boxes: list.slice(0, 3).map((el) => {
        const rect = el.getBoundingClientRect();
        const style = getComputedStyle(el);
        return {
          width: Math.round(rect.width),
          height: Math.round(rect.height),
          position: style.position,
          clip: style.clip,
        };
      }),
    };
  });

  expect(anchors.count, 'falta un ancla por campo de cantidad').toBe(20);
  for (const box of anchors.boxes) {
    expect(box.width).toBeLessThanOrEqual(1);
    expect(box.height).toBeLessThanOrEqual(1);
    expect(box.position).toBe('absolute');
    expect(box.clip).toBe('rect(0px, 0px, 0px, 0px)');
  }
});
