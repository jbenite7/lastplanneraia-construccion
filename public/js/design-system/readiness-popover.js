// Globo de habilitacion — abre sobre `.pi-habilitacion-cell` (columna fundida
// de Task 4/2026-08-21) con clic o Enter/Espacio. Task 6: solo el ciclo
// abrir/foco/teclado/cerrar; el CONTENIDO (selectores por restriccion,
// guardado) es la Task 7 y no se adelanta aqui.
//
// Reutiliza la maquinaria de `state-tooltip.js` de este mismo frente:
// `popover="manual"` (no "auto", que cerraria unos popovers con otros y
// pelearia con el clic de la propia celda), anclaje CSS via `anchor-name`/
// `positionAnchor` con respaldo de coordenadas fijas, y fondo opaco resuelto
// igual en la hoja companera. Lo que el tooltip NO necesitaba y este globo
// SI: es interactivo (contendra selectores en la Task 7), asi que al abrir
// mueve el foco DENTRO del globo, atrapa el Tab mientras esta abierto, y
// Escape lo cierra devolviendo el foco a la celda que lo abrio — eso es lo
// que exige la prueba de teclado de esta tarea. Un clic fuera del globo y de
// la celda tambien lo cierra (spec, linea 165): con `popover="manual"` el
// navegador NO aplica light-dismiss por si solo -eso es exclusivo de
// "auto"-, asi que el clic-afuera se codifica a mano aqui, con el mismo
// cierre y devolucion de foco que Escape.

const SOPORTA_ANCLA =
  typeof CSS !== 'undefined' &&
  typeof CSS.supports === 'function' &&
  CSS.supports('anchor-name', '--aia-ancla');

const HOLGURA = 8;
let contador = 0;
let estado = null; // { celda, globo }

// A diferencia de `state-tooltip.js` -donde el panel es un unico nodo que vive
// siempre en el DOM del chip-, aqui `construirGlobo()` crea un globo NUEVO en
// cada apertura (se destruye al cerrar). El nombre de ancla se calcula una
// sola vez por celda y se recuerda en `celda.dataset.aiaAncla`, pero
// `globo.style.positionAnchor` hay que asignarlo SIEMPRE al globo de turno:
// si se salta ahi apenas la celda ya tenia ancla de una apertura anterior, el
// globo nuevo queda sin `position-anchor` y el navegador lo cae a su posicion
// por defecto (0,0) en vez de anclarlo a la celda.
const emparejarAncla = (celda, globo) => {
  if (!SOPORTA_ANCLA) {
    return;
  }
  let nombre = celda.dataset.aiaAncla;
  if (!nombre) {
    contador += 1;
    nombre = `--aia-popover-ancla-${contador}`;
    celda.dataset.aiaAncla = nombre;
    celda.style.anchorName = nombre;
  }
  globo.style.positionAnchor = nombre;
};

const colocar = (celda, globo) => {
  if (SOPORTA_ANCLA) {
    return;
  }
  const cajaCelda = celda.getBoundingClientRect();
  const cajaGlobo = globo.getBoundingClientRect();
  const separacion = 4;

  const cabeAbajo = cajaCelda.bottom + separacion + cajaGlobo.height <= window.innerHeight - HOLGURA;
  const y = cabeAbajo
    ? cajaCelda.bottom + separacion
    : Math.max(HOLGURA, cajaCelda.top - separacion - cajaGlobo.height);

  const cabeDerecha = cajaCelda.left + cajaGlobo.width <= window.innerWidth - HOLGURA;
  const x = cabeDerecha
    ? cajaCelda.left
    : Math.max(HOLGURA, cajaCelda.right - cajaGlobo.width);

  globo.style.setProperty('--aia-popover-x', `${Math.round(x)}px`);
  globo.style.setProperty('--aia-popover-y', `${Math.round(y)}px`);
};

const construirGlobo = (datosFila) => {
  const globo = document.createElement('div');
  globo.className = 'aia-readiness-popover';
  globo.setAttribute('popover', 'manual');
  globo.setAttribute('role', 'dialog');
  globo.setAttribute('aria-modal', 'false');
  globo.tabIndex = -1;

  const titulo = document.createElement('span');
  titulo.className = 'aia-readiness-popover__titulo';
  titulo.textContent = (datosFila && datosFila.Actividad) || 'Habilitación';
  globo.setAttribute('aria-label', titulo.textContent);
  globo.appendChild(titulo);

  document.body.appendChild(globo);
  return globo;
};

const focoAtrapado = (ev) => {
  if (!estado || ev.key !== 'Tab') {
    return;
  }
  const focosables = estado.globo.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
  );
  if (focosables.length === 0) {
    ev.preventDefault();
    estado.globo.focus();
    return;
  }
  const primero = focosables[0];
  const ultimo = focosables[focosables.length - 1];
  if (ev.shiftKey && document.activeElement === primero) {
    ev.preventDefault();
    ultimo.focus();
  } else if (!ev.shiftKey && document.activeElement === ultimo) {
    ev.preventDefault();
    primero.focus();
  }
};

const alTeclado = (ev) => {
  if (!estado) {
    return;
  }
  if (ev.key === 'Escape') {
    ev.preventDefault();
    cerrar();
    return;
  }
  focoAtrapado(ev);
};

// Clic afuera: ni dentro del globo ni sobre la celda que lo abrio. Se
// registra en fase de `capture` y se puede instalar en cuanto se abre el
// globo, sin ningun retraso: el clic que abre el globo llega a `hot.js`
// (bubble, document) recien DESPUES de que su fase de captura ya paso, asi
// que agregar aqui este listener de captura durante ese manejador de bubble
// no alcanza a la fase de captura de ese MISMO evento -solo a los
// siguientes-, y por eso no se cierra a si mismo.
const alClicAfuera = (ev) => {
  if (!estado) {
    return;
  }
  const objetivo = ev.target;
  if (!(objetivo instanceof Node)) {
    return;
  }
  if (estado.globo.contains(objetivo) || estado.celda.contains(objetivo)) {
    return;
  }
  cerrar();
};

function cerrar() {
  if (!estado) {
    return;
  }
  const { celda, globo } = estado;
  estado = null;
  document.removeEventListener('keydown', alTeclado, true);
  document.removeEventListener('click', alClicAfuera, true);
  if (globo.isConnected && typeof globo.matches === 'function' && globo.matches(':popover-open')) {
    globo.hidePopover();
  }
  if (globo.isConnected) {
    globo.remove();
  }
  if (celda && celda.isConnected) {
    celda.focus();
  }
}

function abrir(celda, datosFila) {
  if (!(celda instanceof Element)) {
    return;
  }
  if (estado && estado.celda === celda) {
    return;
  }
  cerrar();

  const globo = construirGlobo(datosFila);
  if (typeof globo.showPopover !== 'function') {
    globo.remove();
    return;
  }
  emparejarAncla(celda, globo);
  try {
    globo.showPopover();
  } catch {
    // Un popover ya abierto o desconectado no vale la pena romper la tabla.
    globo.remove();
    return;
  }
  estado = { celda, globo };
  colocar(celda, globo);
  globo.focus();
  document.addEventListener('keydown', alTeclado, true);
  document.addEventListener('click', alClicAfuera, true);
}

function irA() {
  // Reservado para la Task 7 (navegacion entre cuadritos dentro del globo).
  // No se implementa aqui: esta tarea solo cubre abrir/cerrar/foco/teclado.
}

export const AIAReadinessPopover = { abrir, cerrar, irA };

if (typeof window !== 'undefined') {
  window.AIAReadinessPopover = AIAReadinessPopover;
}
