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
