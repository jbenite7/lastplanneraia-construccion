/**
 * Estado vacío compartido para instancias de Handsontable.
 *
 * Handsontable no trae mensaje de vacío propio: cuando la malla no tiene
 * filas queda como un hueco oscuro sin explicación. Este componente
 * superpone un panel (reutilizando la primitiva `.aia-empty` del sistema
 * de diseño) que explica qué falta y cómo se soluciona, y lo oculta en
 * cuanto aparecen filas.
 */
export function attachHtEmptyState(hot, { titulo, cuerpo }) {
  // `hot.rootElement` ES el elemento contenedor pasado al constructor de Handsontable
  // (no un hijo suyo): hay que anclar el panel ahí, no en su padre. Usar `.parentElement`
  // apunta a la envoltura de layout (p.ej. `.hot-full-bleed`), que puede cubrir toda la
  // página en vez del área real de la malla.
  const host = hot.rootElement;
  if (!host) return;

  let panel = host.querySelector(':scope > .ht-empty-state');
  if (!panel) {
    panel = document.createElement('div');
    panel.className = 'ht-empty-state aia-empty';
    panel.setAttribute('role', 'status');
    panel.setAttribute('aria-live', 'polite');
    panel.innerHTML =
      '<div class="ht-empty-state__content">' +
      '<h2 class="ht-empty-state__titulo"></h2>' +
      '<p class="ht-empty-state__cuerpo"></p>' +
      '</div>';
    host.appendChild(panel);
  }
  // Los textos se actualizan siempre, aunque ya exista panel: una segunda llamada con
  // otro titulo/cuerpo (p. ej. tras cambiar de semana) debe reflejarse igual.
  panel.querySelector('.ht-empty-state__titulo').textContent = titulo;
  panel.querySelector('.ht-empty-state__cuerpo').textContent = cuerpo;

  const sync = () => {
    panel.hidden = hot.countRows() > 0;
  };
  sync();

  // Guarda de idempotencia: sin esto, invocar attachHtEmptyState() dos veces sobre la
  // misma instancia (reconstruccion sin destroy(), doble montaje) acumula los mismos
  // cuatro hooks y sync() se dispara N veces por evento. No corrompe datos, pero es
  // desperdicio silencioso — y esta pieza ya tiene un segundo consumidor (PDC).
  if (!panel.dataset.attached) {
    panel.dataset.attached = 'true';
    hot.addHook('afterLoadData', sync);
    hot.addHook('afterChange', sync);
    hot.addHook('afterRemoveRow', sync);
    hot.addHook('afterCreateRow', sync);
  }
}
