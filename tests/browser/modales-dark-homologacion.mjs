import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import {
  VIEWPORT,
  installContrastProbe,
  openModal,
  closeModal,
  measure,
  matchedStyles,
  explainRules,
} from './support/contrast.mjs';

// Guarda de regresion de las siete regresiones medidas en
// .superpowers/sdd/task-5c-report.md 5.7: el shell .aia-modal paso a oscuro y
// la piel concreta de seis modales seguia en el eje de tokens fijo (claro).
// Ninguna suite existente lo habria detectado: design-system-body-canvas-dark
// mide el `body` de 10 rutas, no el interior de un modal.

const project = PROJECTS.find(({ key }) => key === 'construction');
const AA = 4.5;

test.use({ viewport: VIEWPORT });

test('botones outline del pie de modal legibles sobre el pie oscuro', async ({ page }) => {
  await loginAndSelectProject(page, project);
  // Una sola vez: addInitScript persiste en las navegaciones siguientes.
  await installContrastProbe(page);

  const cases = [
    ['/listado-actividades', 'modalEliminar', '#modalEliminar .modal-footer .btn.btn-default'],
    ['/pdc', 'modalEliminar', '#modalEliminar .modal-footer .btn.btn-default'],
    ['/control-cambios', 'modalEliminar', '#btn_cancelar_eliminar'],
    ['/listado-actividades', 'modalAutoGenerarListado', '#btnAutoGenListadoAnalizar'],
  ];

  for (const [route, modalId, selector] of cases) {
    await page.goto(route);
    await openModal(page, modalId);
    const result = await measure(page, selector);
    expect(result, `${route} ${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${route} ${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
    await closeModal(page, modalId);
  }
});

test('/control-cambios #modalordenDeCambio: etiquetas del formulario legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/control-cambios');
  await installContrastProbe(page);
  await openModal(page, 'modalordenDeCambio');

  for (const selector of [
    '#modalordenDeCambio label[for="inputJustificacion"]',
    '#modalordenDeCambio label[for="inputDescripcion"]',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // Ninguna utilidad de vendor con color puede sobrevivir dentro del modal:
  // llevan !important desde @layer vendor y, al invertirse el orden de capas
  // para !important, no hay @layer posterior capaz de vencerlas.
  const leftovers = await page.evaluate(() => {
    const modal = document.getElementById('modalordenDeCambio');
    const banned = [
      'bg-light',
      'bg-white',
      'border',
      'border-right',
      'border-top',
      'border-bottom',
      'text-muted',
    ];
    return [...modal.querySelectorAll('*')].flatMap((el) =>
      banned.filter((c) => el.classList.contains(c)),
    ).length;
  });
  expect(leftovers, 'quedan utilidades bg-*/border-*/text-muted de Bootstrap').toBe(0);
});

test('/pdc #modalContrato: cuerpo y campos legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/pdc');
  await installContrastProbe(page);
  await openModal(page, 'modalContrato');

  for (const selector of [
    '#modalContrato .modal-body',
    '#modalContrato .pdc-contract-section__title',
    '#modalContrato input.form-control',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // pdc.css entra por <link> sin capa y le gana a toda @layer: la regla del
  // shell es inerte aqui. Si el fondo no viene del tema activo, el arreglo
  // se escribio en el archivo equivocado.
  const bg = await matchedStyles(page, '#modalContrato .modal-content', 'background-color');
  // --ds-active-surface resuelve a rgba(28, 36, 31, 0.92).
  expect(bg.computed, `candidatas:\n      ${explainRules(bg)}`).toBe('rgba(28, 36, 31, 0.92)');
});

test('/programa-general-actualizar: modales de auto-asociacion y exito legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/programa-general-actualizar');
  await installContrastProbe(page);

  await openModal(page, 'modalAutoAsociar');
  for (const selector of ['#modalAutoAsociar .modal-body', '#modalAutoAsociar .modal-footer']) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }
  await closeModal(page, 'modalAutoAsociar');

  await openModal(page, 'modalImportacionExitosa');
  for (const selector of ['#modalImportacionExitosa h3', '#modalImportacionExitosa p']) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // Los style= del markup ganan a todo: mientras sigan ahi, ningun cambio de
  // CSS puede alcanzar a estos dos modales.
  const inlineColors = await page.evaluate(() =>
    ['modalAutoAsociar', 'modalImportacionExitosa'].flatMap((id) =>
      [...document.getElementById(id).querySelectorAll('[style]')]
        .map((el) => el.getAttribute('style'))
        .filter((s) => /(^|;)\s*(background|color)\s*:/i.test(s)),
    ).length,
  );
  expect(inlineColors, 'quedan style= de color en el markup').toBe(0);
});

test('/programacion-semanal #modal_change_monitor: cuerpo y pie legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/programacion-semanal');
  // La ruta auto-dispara save/auto-program al cargar; medir durante ese
  // repintado da valores de un estado intermedio.
  await page.waitForLoadState('networkidle').catch(() => {});
  await installContrastProbe(page);
  await openModal(page, 'modal_change_monitor');

  for (const selector of [
    '#modal_change_monitor .cm-modal-body',
    '#modal_change_monitor .cm-modal-footer',
    '#modal_change_monitor .cm-close-btn',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }
});

// El barrido ciego de los 56 modales .aia-modal de las siete rutas destapo seis
// modales mas con el mismo defecto que los seis del encargo: piel clara bajo un
// shell que ya es oscuro. Ninguno figuraba en 5.7 del informe de origen.
const EXTRA = [
  ['/contratos', 'modalEditarContratos', [
    '#modalEditarContratos .ct-modalidad-label',
    '#modalEditarContratos .ct-checkbox-item',
    '#modalEditarContratos .ct-contract-header__cell',
  ]],
  // #btnAutoGenListadoAplicar queda fuera: nace deshabilitado y WCAG 1.4.3 exime
  // los controles inactivos. Su tinta pasa de 1,61:1 a 4,28:1 con el resto del
  // cambio; asertarlo a 4,5 obligaria a pintar un deshabilitado que no parece
  // deshabilitado.
  ['/listado-actividades', 'modalAutoGenerarListado', ['#modalAutoGenerarListado .modal-footer .btn.btn-secondary']],
  ['/programa-general-actualizar', 'modalCargarExcel', ['#modalCargarExcel h3#form_general']],
  ['/programacion-semanal', 'modal_leyenda_colores_ps', ['.ps-legend-quick-intro']],
  ['/programacion-semanal', 'formulario_nuevo', [
    '#btn_recargar_bandeja_no_autoprogramadas',
    '#btn_listar',
  ]],
  ['/programacion-semanal', 'modal_cnc_hot', ['#modal_cnc_hot .ps-required']],
];

for (const [route, modalId, selectors] of EXTRA) {
  test(`${route} #${modalId}: piel legible sobre oscuro`, async ({ page }) => {
    await loginAndSelectProject(page, project);
    await page.goto(route);
    await page.waitForLoadState('networkidle').catch(() => {});
    await installContrastProbe(page);
    await openModal(page, modalId);

    for (const selector of selectors) {
      const result = await measure(page, selector);
      expect(result, `${selector} no existe`).not.toBeNull();
      expect
        .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
        .toBeGreaterThanOrEqual(AA);
    }
  });
}

// El valor COMPUTADO es la unica prueba de quien gano la cascada. Este bloque
// existe porque el mismo defecto que ataca el tramo —una regla que parece ganar
// y esta inerte— puede repetirse en el propio arreglo.
test('la tinta que gana en los botones del pie es la del tema activo, no el verde corporativo', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await installContrastProbe(page);
  await page.goto('/listado-actividades');
  await openModal(page, 'modalEliminar');

  const ink = await matchedStyles(page, '#modalEliminar .modal-footer .btn.btn-default', 'color');
  // --ds-active-text-primary resuelve a #f7faf8.
  expect(ink.computed, `candidatas:\n      ${explainRules(ink)}`).toBe('rgb(247, 250, 248)');

  const border = await matchedStyles(
    page,
    '#modalEliminar .modal-footer .btn.btn-default',
    'border-top-color',
  );
  // --ds-active-border resuelve a rgba(221, 239, 230, 0.22).
  expect(border.computed, `candidatas:\n      ${explainRules(border)}`)
    .toBe('rgba(221, 239, 230, 0.22)');

  const winner = ink.rules.find((r) => r.value === 'var(--ds-active-text-primary)');
  expect(winner, 'la regla escrita no aparece entre las candidatas').toBeTruthy();
  expect(winner.file).toContain('/css/styles.css');
  expect(winner.layers, 'la regla debe seguir en la capa module').toContain('module');
});

// La bandeja de #formulario_nuevo solo existe con filas: en reposo el <tbody>
// trae un unico "Cargando actividades..." y ningun barrido la ve. Con filas
// reales medidas el 2026-07-28, el boton "Usar" daba 2,03:1 y dos de los tres
// chips de motivo caian bajo AA (3,80 y 3,59), y ninguna suite lo detectaba.
// Las filas se inyectan con las mismas clases que emite
// public/js/modules/programacion_semanal/hot.js (renderBandeja): lo que se
// verifica es la piel CSS, no el render de datos.
const BANDEJA_ROWS = `
  <tr class="ps-row-excepcion" data-id="A-101">
    <td class="ps-excepcion-id">A-101</td>
    <td class="ps-excepcion-actividad">Vaciado placa</td>
    <td><span class="ps-motivo-chip"><span class="ps-motivo-restriction">MdeO</span>pendientes</span></td>
    <td class="text-right"><button type="button" class="btn btn-sm btn-outline-secondary btn_usar_excepcion">Usar</button></td>
  </tr>
  <tr class="ps-row-excepcion ps-row-selected" data-id="A-102">
    <td class="ps-excepcion-id">A-102</td>
    <td class="ps-excepcion-actividad">Instalacion ductos</td>
    <td><span class="ps-motivo-chip ps-motivo-chip--clean">Lista</span></td>
    <td class="text-right"><button type="button" class="btn btn-sm btn-outline-secondary btn_usar_excepcion">Usar</button></td>
  </tr>
  <tr class="ps-row-excepcion" data-id="A-103">
    <td class="ps-excepcion-id">A-103</td>
    <td class="ps-excepcion-actividad">Pintura fachada</td>
    <td><span class="ps-motivo-chip is-empty">No autoprogramada</span></td>
    <td class="text-right"><button type="button" class="btn btn-sm btn-outline-secondary btn_usar_excepcion">Usar</button></td>
  </tr>`;

test('/programacion-semanal #formulario_nuevo: la bandeja con filas es legible sobre oscuro', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/programacion-semanal');
  await page.waitForLoadState('networkidle').catch(() => {});
  await installContrastProbe(page);
  await openModal(page, 'formulario_nuevo');
  await page.evaluate((html) => {
    document.getElementById('tbody_excepciones_no_autoprogramadas').innerHTML = html;
  }, BANDEJA_ROWS);
  // El puntero fuera de la tabla: con el raton encima, `.ps-row-excepcion:hover`
  // desplaza a `.ps-row-selected` y se mediria otro estado del que se cree.
  await page.mouse.move(5, 5);
  await page.waitForTimeout(300);

  for (const selector of [
    '#tabla_excepciones_no_autoprogramadas .btn_usar_excepcion',
    '#tabla_excepciones_no_autoprogramadas .ps-motivo-chip',
    '#tabla_excepciones_no_autoprogramadas .ps-motivo-chip--clean',
    '#tabla_excepciones_no_autoprogramadas .ps-motivo-chip.is-empty',
    '#tabla_excepciones_no_autoprogramadas .ps-motivo-restriction',
    '#tabla_excepciones_no_autoprogramadas .ps-excepcion-id',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect
      .soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // 1.4.11: el boton "Usar" no tiene relleno propio en reposo, asi que su unica
  // frontera es el borde. Se mide contra el fondo compuesto de su celda.
  const borderRatio = await page.evaluate(() => {
    const el = document.querySelector('#tabla_excepciones_no_autoprogramadas .btn_usar_excepcion');
    const probe = window.__aiaContrast;
    const cs = getComputedStyle(el);
    // Se reusa la sonda: se pinta el color del borde como tinta de un nodo
    // temporal dentro de la misma celda para componer sobre los mismos ancestros.
    const ghost = document.createElement('span');
    ghost.id = 'aia-ghost-border';
    ghost.style.color = cs.borderTopColor;
    el.parentElement.appendChild(ghost);
    const out = probe('#aia-ghost-border');
    ghost.remove();
    return out;
  });
  expect
    .soft(borderRatio.ratio, `borde del boton Usar — ${borderRatio.fg} sobre ${borderRatio.bg}`)
    .toBeGreaterThanOrEqual(3);

  // El estado con el raton encima tambien: antes daba 4,01:1 (bajo AA).
  await page.locator('#tabla_excepciones_no_autoprogramadas .btn_usar_excepcion').first().hover();
  await page.waitForTimeout(250);
  const hovered = await measure(page, '#tabla_excepciones_no_autoprogramadas .btn_usar_excepcion');
  expect
    .soft(hovered.ratio, `boton Usar en hover — ${hovered.fg} sobre ${hovered.bg}`)
    .toBeGreaterThanOrEqual(AA);
});
