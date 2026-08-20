// Conducta compartida del tooltip de estado (state-tooltip.css).
//
// POR QUE ESTE ARCHIVO CAMBIO EL 2026-08-20. El panel se dibujaba con
// `position: absolute` colgando del chip, es decir DENTRO del scroller de la
// tabla: cualquier ancestro con `overflow` lo recortaba, y en las ultimas filas
// y en la ultima columna aparecia cortado. El volteo que hacia este archivo
// (`data-aia-tip-side` / `data-aia-tip-alinea`) era un parche contra ese
// recorte. Ahora el panel se muestra con la Popover API, que lo promueve al
// top-layer del navegador — ahi no hay ancestro que recorte — y por eso los dos
// atributos de volteo se retiraron en vez de dejarlos muertos: quien decide
// arriba/abajo e inicio/fin es el anclaje CSS (`position-try-fallbacks`) o, si
// el navegador no lo soporta, `colocar()` de aqui abajo.
//
// Lo que NO cambio, porque es contrato:
//  1. Se muestra al hover Y al foco (WCAG 1.4.13).
//  2. Escape lo descarta (body.aia-tips-off); mover el mouse o cambiar el foco
//     lo rehabilita.
//  3. Es hoverable: se puede entrar el puntero al panel sin que se cierre.
//  4. El fundido lo gobierna el CSS, que respeta prefers-reduced-motion.
//
// Se usa popover="manual" y no "auto" a proposito: "auto" cierra por su cuenta
// al primer pointerdown fuera y cierra unos popovers con otros, lo que pelearia
// con el clic del chip que abre el drawer de PS/PI. Con "manual" el unico que
// abre y cierra es este archivo.

const SOPORTA_ANCLA =
  typeof CSS !== 'undefined' &&
  typeof CSS.supports === 'function' &&
  CSS.supports('anchor-name', '--aia-ancla');

const HOLGURA = 8;
let contador = 0;
let abierto = null;

const tipDe = (chip) => (chip ? chip.querySelector('.aia-state-tip') : null);

// Enlaza chip y panel para el anclaje CSS. El nombre tiene que ser unico por
// chip: si dos chips compartieran `anchor-name`, el panel se anclaria al que el
// navegador decida y no al que se esta señalando.
const emparejarAncla = (chip, tip) => {
  if (!SOPORTA_ANCLA || chip.dataset.aiaAncla) {
    return;
  }
  contador += 1;
  const nombre = `--aia-state-ancla-${contador}`;
  chip.dataset.aiaAncla = nombre;
  chip.style.anchorName = nombre;
  tip.style.positionAnchor = nombre;
};

// Respaldo para navegadores sin anclaje CSS: coordenadas fijas medidas con el
// panel ya visible (en el top-layer no hereda el desplazamiento del scroller,
// asi que los rects del viewport bastan).
const colocar = (chip, tip) => {
  if (SOPORTA_ANCLA) {
    return;
  }
  const chipCaja = chip.getBoundingClientRect();
  const tipCaja = tip.getBoundingClientRect();
  const separacion = 4;

  const cabeAbajo = chipCaja.bottom + separacion + tipCaja.height <= window.innerHeight - HOLGURA;
  const y = cabeAbajo
    ? chipCaja.bottom + separacion
    : Math.max(HOLGURA, chipCaja.top - separacion - tipCaja.height);

  const cabeDerecha = chipCaja.left + tipCaja.width <= window.innerWidth - HOLGURA;
  const x = cabeDerecha
    ? chipCaja.left
    : Math.max(HOLGURA, chipCaja.right - tipCaja.width);

  tip.style.setProperty('--aia-tip-x', `${Math.round(x)}px`);
  tip.style.setProperty('--aia-tip-y', `${Math.round(y)}px`);
};

const ocultar = () => {
  if (!abierto) {
    return;
  }
  const { tip } = abierto;
  abierto = null;
  if (tip.isConnected && tip.matches(':popover-open')) {
    tip.hidePopover();
  }
};

const mostrar = (chip) => {
  const tip = tipDe(chip);
  if (!tip || typeof tip.showPopover !== 'function') {
    return;
  }
  if (abierto && abierto.tip === tip) {
    return;
  }
  ocultar();
  if (document.body.classList.contains('aia-tips-off')) {
    return;
  }
  tip.setAttribute('popover', 'manual');
  emparejarAncla(chip, tip);
  try {
    tip.showPopover();
  } catch {
    // Un popover ya abierto o desconectado del documento lanza; no hay tooltip
    // que valga la pena romper una tabla entera.
    return;
  }
  abierto = { chip, tip };
  colocar(chip, tip);
};

// Sigue abierto si el puntero o el foco estan sobre el chip o sobre su panel:
// esto es lo que hace el tooltip hoverable (WCAG 1.4.13).
const siguePerteneciendo = (nodo) => {
  if (!abierto || !(nodo instanceof Element)) {
    return false;
  }
  return abierto.chip.contains(nodo) || abierto.tip.contains(nodo);
};

export function activarStateTips(raiz) {
  const contenedor = raiz || document;
  if (contenedor.dataset && contenedor.dataset.aiaStateTips === '1') {
    return;
  }
  if (contenedor.dataset) {
    contenedor.dataset.aiaStateTips = '1';
  }

  const rehabilitar = () => document.body.classList.remove('aia-tips-off');
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') {
      document.body.classList.add('aia-tips-off');
      ocultar();
    }
  });
  document.addEventListener('mousemove', rehabilitar, { passive: true });
  document.addEventListener('focusin', rehabilitar);

  contenedor.addEventListener('mouseover', (ev) => {
    const chip = ev.target instanceof Element ? ev.target.closest('.ops-state-chip') : null;
    if (chip) {
      mostrar(chip);
    } else if (!siguePerteneciendo(ev.target)) {
      ocultar();
    }
  }, { passive: true });

  contenedor.addEventListener('focusin', (ev) => {
    const chip = ev.target instanceof Element ? ev.target.closest('.ops-state-chip') : null;
    if (chip) {
      mostrar(chip);
    } else if (!siguePerteneciendo(ev.target)) {
      ocultar();
    }
  });

  // El panel vive en el top-layer con posicion respecto al viewport: si la
  // tabla se desplaza por debajo, hay que recalcular (o el navegador lo hace
  // solo cuando hay anclaje CSS).
  const recolocar = () => {
    if (abierto) {
      colocar(abierto.chip, abierto.tip);
    }
  };
  window.addEventListener('scroll', recolocar, { passive: true, capture: true });
  window.addEventListener('resize', recolocar, { passive: true });
}
