// El aviso global de error del modal "Editar contratos" (`<p class="mensaje">` de
// views/contratos/contratos.view.php) se veia pero no se anunciaba. Medido en el arbol de
// accesibilidad de Chromium (CDP) con el estado de error provocado, antes de tocar nada:
// `role: null`, `aria-live: null`, y el texto solo existia como `StaticText` suelto —
// cero regiones live dentro de #modalEditarContratos. Un lector de pantalla veia aparecer
// el texto y no lo decia. WCAG 3.3.1 (Error Identification).
//
// Lo que se asierta aqui se lee del arbol de accesibilidad, no del marcado: un
// `role="alert"` puesto en el DOM puede no producir region live (nodo ignorado, region
// vacia, subarbol que no cuelga de ella), asi que comprobar el atributo no demuestra
// nada. Cada bloque autovalida ademas que la sonda esta midiendo el estado real: el error
// NO existe en reposo y un barrido que abra el modal sin pulsar Guardar da un falso verde.
//
// Dos decisiones deliberadas, distintas del patron de Programacion Semanal
// (public/js/modules/programacion_semanal/hot.js:383-385):
//
//   1. El aviso NO caduca solo. Alli un temporizador borra el texto a los 4 s y devuelve
//      el nodo a `role="status"`, dejando como unica senal el color del borde mientras la
//      clase de error sigue puesta — senal efimera. Aqui el aviso acompana a los
//      `aria-invalid` de las cantidades, que duran hasta la siguiente validacion, y dura
//      lo mismo que ellos.
//   2. `role="alert"` va fijo en el marcado, no se pone junto con el texto: una region
//      live que nace a la vez que su contenido es el caso que el navegador se salta.
//
// Y la trampa de repetir: reponer el MISMO texto puede no anunciarse una segunda vez, y
// este aviso repite palabra por palabra en cada Guardar fallido. Se comprueba que el texto
// se retira y se vuelve a insertar de verdad, en vez de suponerlo.

import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { VIEWPORT, openModal } from './support/contrast.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test.use({ viewport: VIEWPORT });

const VALIDATION_MESSAGE = 'La cantidad de contratos del paquete actual debe ser un entero mayor o igual a 1.';

// Ningun camino de esta suite debe llegar al POST de guardado real: la validacion falla
// antes. El contador lo demuestra en vez de suponerlo.
async function blockContractSaves(page) {
  const state = { count: 0, respond: () => ({ respuesta: 'BIEN' }) };
  await page.route('**/api/contratos/save**', async (route) => {
    const body = route.request().postData() || '';
    // Los catalogos de paquetes/recursos entran por el mismo endpoint y son lecturas.
    if (/opcion=actualizar(ListadoPaquetesContratacion|InsumosRecursos)/.test(body)) {
      await route.continue();
      return;
    }
    state.count += 1;
    await route.fulfill({ json: state.respond() });
  });
  return state;
}

// La semana canonica de Da Porto no trae filas de contrato en este entorno, asi que el
// boton "editar" no existe: el modal se abre por el mismo camino que usan
// contratos-cantidad-a11y.mjs y modales-dark-homologacion.mjs.
async function openContractModal(page) {
  await loginAndSelectProject(page, project);
  await page.goto('/contratos');
  await page.waitForLoadState('networkidle').catch(() => {});
  const saves = await blockContractSaves(page);
  await openModal(page, 'modalEditarContratos');
  // Sin modalidad marcada, updateSections() deja las cuatro secciones ocultas: `:visible`
  // no ve ninguna fila y la validacion pasaria trivialmente. Y hay que esperar a las DOS
  // recargas de catalogo que dispara el cambio de modalidad: su `.done` hace
  // `$select.html(options).val(current)`, que borraria el <option> inyectado despues.
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

// La unica fuente de verdad de lo que oye un lector de pantalla. Se resuelve el nodo AX
// PARTIENDO DEL ELEMENTO DEL DOM (DOM.querySelector -> getPartialAXTree), no buscando por
// nombre en el arbol completo: asi la asercion no puede caer sobre otro nodo con texto
// parecido, y si el `<p>` quedara ignorado por accesibilidad se veria aqui.
async function axMessageNode(page) {
  const cdp = await page.context().newCDPSession(page);
  try {
    await cdp.send('Accessibility.enable');
    const { root } = await cdp.send('DOM.getDocument', { depth: -1 });
    const { nodeId } = await cdp.send('DOM.querySelector', {
      nodeId: root.nodeId,
      selector: '#modalEditarContratos .mensaje',
    });
    if (!nodeId) return null;
    const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: true });
    const byId = new Map(nodes.map((node) => [node.nodeId, node]));
    // getPartialAXTree devuelve ancestros + el nodo + sus hijos; el nodo del elemento es
    // el ultimo que no es descendiente de otro devuelto.
    const self = nodes.find((node) => node.backendDOMNodeId
      && !node.ignored
      && ['alert', 'status', 'paragraph', 'generic', 'log'].includes(node.role?.value));
    const target = self || nodes[nodes.length - 1];
    const prop = (name) => target.properties?.find((p) => p.name === name)?.value?.value;
    // Texto real que cuelga de la region, recorriendo el subarbol devuelto.
    const collect = (node, out) => {
      if (!node) return out;
      if (node.name?.value) out.push(node.name.value);
      for (const childId of node.childIds || []) collect(byId.get(childId), out);
      return out;
    };
    return {
      role: target.role?.value ?? null,
      ignored: !!target.ignored,
      live: prop('live') ?? null,
      atomic: prop('atomic') ?? null,
      relevant: prop('relevant') ?? null,
      text: collect(target, []).join(' ').trim(),
    };
  } finally {
    await cdp.detach().catch(() => {});
  }
}

const domMessage = (page) => page.evaluate(() => {
  const el = document.querySelector('#modalEditarContratos .mensaje');
  return {
    role: el.getAttribute('role'),
    text: el.textContent.trim(),
    hasErrorClass: el.classList.contains('ct-message-error'),
  };
});

test('#modalEditarContratos: el aviso global de error llega al arbol de accesibilidad como region live', async ({ page }) => {
  const saves = await openContractModal(page);

  // Autovalidacion de la sonda: en reposo la region existe pero esta vacia, asi que no
  // anuncia nada al abrir el modal. Si aqui hubiera texto, el resto seria un falso verde.
  expect(await invalidCount(page), 'el modal no deberia abrir con campos en error').toBe(0);
  const atRest = await axMessageNode(page);
  expect(atRest, 'el aviso global no aparece en el arbol de accesibilidad').toBeTruthy();
  expect(atRest.text, 'el aviso global abre con texto: la region anunciaria al abrir').toBe('');

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');

  // Autovalidacion de la sonda: si esto es 0, el submit no provoco el estado de error.
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBeGreaterThan(0);

  const announced = await axMessageNode(page);
  expect(announced.ignored, 'el aviso llega ignorado al arbol de accesibilidad').toBe(false);
  expect(announced.role, 'el aviso no llega como alert al arbol de accesibilidad').toBe('alert');
  expect(announced.live, 'el aviso no es una region live: nada se anunciaria').toBe('assertive');
  expect(announced.atomic, 'la region no se lee entera: el anuncio saldria a trozos').toBe(true);
  expect(
    announced.text,
    'el texto del aviso no cuelga de la region live que deberia anunciarlo',
  ).toContain('entero mayor o igual a 1');

  expect(saves.count, 'la validacion no debe llegar al POST de guardado').toBe(0);
});

test('#modalEditarContratos: el aviso no caduca solo mientras el estado de error sigue puesto', async ({ page }) => {
  const saves = await openContractModal(page);

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBeGreaterThan(0);

  // El patron de Programacion Semanal borra el texto y devuelve el nodo a `role="status"`
  // a los 4000 ms. Se espera holgadamente por encima de ese umbral: si alguien copiara
  // aquel temporizador, este bloque lo caza.
  await page.waitForTimeout(5200);

  const still = await domMessage(page);
  expect(still.text, 'el aviso se borro solo mientras el campo sigue invalido').toBe(VALIDATION_MESSAGE);
  expect(still.role, 'el aviso degrado su rol a status: dejaria de anunciar').toBe('alert');
  expect(still.hasErrorClass, 'el aviso perdio su clase de error').toBe(true);
  expect(await invalidCount(page), 'el estado de error desaparecio solo').toBeGreaterThan(0);

  const late = await axMessageNode(page);
  expect(late.role, 'el arbol dejo de ver el aviso como alert').toBe('alert');
  expect(late.text, 'el arbol se quedo sin el texto del aviso').toContain('entero mayor o igual a 1');

  expect(saves.count, 'la validacion no debe llegar al POST de guardado').toBe(0);
});

test('#modalEditarContratos: repetir el mismo error reinserta el texto en vez de reescribirlo', async ({ page }) => {
  const saves = await openContractModal(page);

  // Un `role="alert"` al que se le reescribe el mismo texto puede no anunciarse la segunda
  // vez. Aqui se observa el nodo con un MutationObserver: lo que demuestra que hay un
  // anuncio nuevo es que el texto se vacia y se vuelve a insertar, no que el DOM acabe
  // igual que antes (que es justamente lo que no se anunciaria).
  await page.evaluate(() => {
    const el = document.querySelector('#modalEditarContratos .mensaje');
    window.__msgLog = [];
    const observer = new MutationObserver(() => {
      window.__msgLog.push(el.textContent.trim());
    });
    observer.observe(el, { childList: true, characterData: true, subtree: true });
  });

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');
  expect(await invalidCount(page), 'el submit no provoco el estado de error').toBeGreaterThan(0);

  // Segundo Guardar con la misma cantidad mal: mensaje identico palabra por palabra.
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');

  const log = await page.evaluate(() => window.__msgLog);
  const emptied = log.filter((entry) => entry === '').length;
  const filled = log.filter((entry) => entry === VALIDATION_MESSAGE).length;

  expect(filled, 'el aviso no se inserto las dos veces').toBeGreaterThanOrEqual(2);
  expect(
    emptied,
    'el segundo aviso reescribio el mismo texto sin vaciar antes: puede no anunciarse',
  ).toBeGreaterThanOrEqual(1);
  // El vaciado tiene que caer ENTRE las dos inserciones, no antes de la primera.
  expect(
    log.lastIndexOf('') > log.indexOf(VALIDATION_MESSAGE),
    'el hueco que fuerza el segundo anuncio no ocurrio entre los dos avisos',
  ).toBe(true);

  const announced = await axMessageNode(page);
  expect(announced.role).toBe('alert');
  expect(announced.text, 'el aviso no volvio al arbol tras la reinsercion').toContain('entero mayor o igual a 1');

  expect(saves.count, 'la validacion no debe llegar al POST de guardado').toBe(0);
});

test('#modalEditarContratos: cerrar el modal vacia la region sin retirarle el rol', async ({ page }) => {
  await openContractModal(page);

  await fillSlot(page, 1, '');
  await page.locator('#btn_guardar_contratos').click();
  await expect(page.locator('#modalEditarContratos .mensaje')).toContainText('entero mayor o igual a 1');

  await page.evaluate(() => window.jQuery('#modalEditarContratos').modal('hide'));
  await page.waitForTimeout(400);

  // La region tiene que quedar vacia (si no, reabrir el modal arrastraria un aviso viejo)
  // pero conservar `role="alert"`: si se le quitara el rol, reponerlo junto al siguiente
  // texto es justo el caso que el navegador se salta.
  const afterHide = await domMessage(page);
  expect(afterHide.text, 'el cierre del modal dejo texto en la region live').toBe('');
  expect(afterHide.role, 'el cierre del modal retiro el role="alert"').toBe('alert');
  expect(afterHide.hasErrorClass, 'el cierre del modal dejo la clase de error').toBe(false);
});
