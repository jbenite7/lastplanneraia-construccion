// Conducta compartida del tooltip de estado (state-tooltip.css).
// Dos obligaciones que el CSS solo no puede cumplir:
//  1. WCAG 1.4.13 «dismissable»: Escape oculta todo tooltip visible sin mover
//     el puntero (body.aia-tips-off); cualquier movimiento posterior del mouse
//     o cambio de foco lo rehabilita.
//  2. Volteo: si el chip esta tan abajo que el panel se saldria del viewport,
//     se marca data-aia-tip-side="top" y el CSS lo dibuja por encima.
export function activarStateTips(raiz) {
  const contenedor = raiz || document;

  const rehabilitar = () => document.body.classList.remove('aia-tips-off');
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') {
      document.body.classList.add('aia-tips-off');
    }
  });
  document.addEventListener('mousemove', rehabilitar, { passive: true });
  document.addEventListener('focusin', rehabilitar);

  const decidirLado = (chip) => {
    const tip = chip.querySelector('.aia-state-tip');
    if (!tip) {
      return;
    }
    const caja = chip.getBoundingClientRect();
    const altoEstimado = Math.max(tip.scrollHeight, 96);
    const cabeAbajo = caja.bottom + altoEstimado < window.innerHeight - 8;
    if (cabeAbajo) {
      chip.removeAttribute('data-aia-tip-side');
    } else {
      chip.setAttribute('data-aia-tip-side', 'top');
    }
  };

  contenedor.addEventListener('mouseover', (ev) => {
    const chip = ev.target instanceof Element ? ev.target.closest('.ops-state-chip') : null;
    if (chip) {
      decidirLado(chip);
    }
  }, { passive: true });
  contenedor.addEventListener('focusin', (ev) => {
    const chip = ev.target instanceof Element ? ev.target.closest('.ops-state-chip') : null;
    if (chip) {
      decidirLado(chip);
    }
  });
}
