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
  }
  // Task 8 (2026-08-21, verificado en vivo): `celda.style.anchorName` hay que
  // REASIGNARLO siempre, no solo la primera vez. Con `renderAllRows: false`
  // Handsontable recicla el mismo <td> para otra fila al hacer scroll -el
  // recorrido del globo dispara ese scroll-, y su `TextRenderer` de base
  // limpia `style` en cada reciclado (comentario de `piRestrictionRenderer`
  // en `hot.js`, misma maquinaria). El `dataset.aiaAncla` sobrevive porque no
  // es `style`, pero el `anchor-name` real si se pierde: sin reasignarlo aqui
  // el globo apunta a un ancla que ya no existe en ningun elemento y el
  // motor de anchor-positioning cae a una posicion sin relacion con la celda,
  // superpuesta con la grilla -eso es lo que interceptaba el clic del boton
  // "siguiente" tras varios saltos.
  celda.style.anchorName = nombre;
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

// Task 7 (2026-08-21): el contenido real del globo. El selector, las
// opciones y el guardado son LOS DE HOY (`hot.js` los arma en
// `abrirGloboHabilitacion()` y los pasa aqui ya calculados); este modulo solo
// dibuja lo que le entregan y reenvia el cambio a `datosFila.guardar`, que es
// quien conoce `hot`, `saveRow` y la pila de deshacer.

const formatearAvance = (avance) => (avance == null ? '0%' : String(avance));

const construirMarcadorAvance = (datosFila) => {
  const marcador = document.createElement('div');
  marcador.className = 'aia-readiness-popover__marcador';

  const avance = document.createElement('span');
  avance.className = 'aia-readiness-popover__avance';
  avance.textContent = formatearAvance(datosFila.avance);
  marcador.appendChild(avance);

  // El chip reusa el HTML exacto que hot.js ya arma con `ops-state-chip` +
  // `stateChipAttrs` (mismo componente que la columna de estado operativo).
  const chipWrapper = document.createElement('span');
  chipWrapper.className = 'aia-readiness-popover__chip';
  chipWrapper.innerHTML = datosFila.estadoChipHtml || '';
  marcador.appendChild(chipWrapper);

  return { marcador, avance };
};

// Task 8 (2026-08-21): recorrido entre actividades sin cerrar el globo. El
// salto de fila (saltarse capitulos, resolver cual es la siguiente/anterior
// actividad) lo resuelve `hot.js`, que es quien conoce la grilla; este modulo
// solo expone los botones y reenvia el clic/tecla a `datosFila.siguiente` /
// `datosFila.anterior` -mismas dos funciones que arma `abrirGloboHabilitacion`
// para el paquete de la fila abierta-. Si la fila de turno es la primera o la
// ultima actividad, `hot.js` no encuentra destino y esas funciones no hacen
// nada: el globo se queda donde esta, nunca vacio ni cerrado.
const construirNavegacion = () => {
  const nav = document.createElement('div');
  nav.className = 'aia-readiness-popover__nav';

  const anterior = document.createElement('button');
  anterior.type = 'button';
  anterior.className = 'aia-readiness-popover__anterior';
  anterior.setAttribute('aria-label', 'Actividad anterior');
  anterior.textContent = '‹';
  anterior.addEventListener('click', () => irA(-1));
  nav.appendChild(anterior);

  const siguiente = document.createElement('button');
  siguiente.type = 'button';
  siguiente.className = 'aia-readiness-popover__siguiente';
  siguiente.setAttribute('aria-label', 'Actividad siguiente');
  siguiente.textContent = '›';
  siguiente.addEventListener('click', () => irA(1));
  nav.appendChild(siguiente);

  return nav;
};

const construirCabecera = (datosFila) => {
  const cabecera = document.createElement('div');
  cabecera.className = 'aia-readiness-popover__cabecera';

  const titulo = document.createElement('span');
  titulo.className = 'aia-readiness-popover__titulo';
  titulo.textContent = datosFila.Actividad || 'Habilitación';
  cabecera.appendChild(titulo);

  const meta = document.createElement('span');
  meta.className = 'aia-readiness-popover__meta';
  meta.textContent = 'Semana ' + (datosFila.Semana || '-') + ' · ' +
    (datosFila.Responsable_AIA || 'Sin responsable');
  cabecera.appendChild(meta);

  cabecera.appendChild(construirNavegacion());

  return cabecera;
};

const marcarFilaError = (fila, mensaje, onReintentar) => {
  fila.classList.add('aia-readiness-popover__fila--error');
  let aviso = fila.querySelector('.aia-readiness-popover__error');
  if (!aviso) {
    aviso = document.createElement('div');
    aviso.className = 'aia-readiness-popover__error';
    const texto = document.createElement('span');
    aviso.appendChild(texto);
    const reintentar = document.createElement('button');
    reintentar.type = 'button';
    reintentar.className = 'aia-readiness-popover__reintentar';
    reintentar.textContent = 'Reintentar';
    aviso.appendChild(reintentar);
    fila.appendChild(aviso);
  }
  aviso.querySelector('span').textContent = mensaje;
  aviso.querySelector('button').onclick = onReintentar;
};

const limpiarFilaError = (fila) => {
  fila.classList.remove('aia-readiness-popover__fila--error');
  const aviso = fila.querySelector('.aia-readiness-popover__error');
  if (aviso) {
    aviso.remove();
  }
};

const construirCuadrito = (item) => {
  const box = document.createElement('span');
  box.className = 'aia-readiness__box';
  box.setAttribute('data-restriccion', item.key || '');
  const lectura = window.AIAReadiness
    ? window.AIAReadiness.leerRestriccion(item.value, item.umbralRatio)
    : { relleno: 0, cumple: false, esNoAplica: false };
  if (lectura.esNoAplica) {
    box.classList.add('aia-readiness__box--na');
    return box;
  }
  if (lectura.cumple) {
    box.classList.add('aia-readiness__box--met');
    return box;
  }
  const fill = document.createElement('span');
  fill.className = 'aia-readiness__fill';
  fill.style.height = Math.round(lectura.relleno * 100) + '%';
  box.appendChild(fill);
  return box;
};

// Task 10 (2026-08-21): esta es LA PIEZA compartida entre el globo de
// escritorio y la tarjeta movil — cuadrito + etiqueta + selector de una
// restriccion. El envase (caja flotante vs. `<details>`) y el mecanismo de
// guardado los decide quien llama: el globo pasa `onCambio` con su propio
// `datosFila.guardar` (Task 7), la tarjeta movil pasa `datasetSelect` para
// que su listener delegado existente (`hot.js`, `data-pi-restriccion`) ya
// se encargue sin que esta pieza sepa nada de `saveRow`.
const construirFilaRestriccion = (item, opciones) => {
  const opts = opciones || {};
  const fila = document.createElement('div');
  fila.className = opts.claseFila || 'aia-readiness-popover__fila';
  fila.appendChild(construirCuadrito(item));

  const etiqueta = document.createElement('label');
  etiqueta.className = opts.claseEtiqueta || 'aia-readiness-popover__etiqueta';
  etiqueta.textContent = item.label;
  fila.appendChild(etiqueta);

  const select = document.createElement('select');
  select.disabled = typeof opts.disabled === 'function' ? Boolean(opts.disabled(item)) : Boolean(opts.disabled);
  const datasetSelect = typeof opts.datasetSelect === 'function' ? opts.datasetSelect(item) : opts.datasetSelect;
  if (datasetSelect) {
    Object.keys(datasetSelect).forEach((clave) => {
      select.dataset[clave] = datasetSelect[clave];
    });
  }
  const idSelect = typeof opts.idSelect === 'function' ? opts.idSelect(item) : opts.idSelect;
  if (idSelect) {
    select.id = idSelect;
  }
  if (opts.forEtiqueta && idSelect) {
    etiqueta.setAttribute('for', idSelect);
  }
  item.options.forEach((opcion) => {
    const option = document.createElement('option');
    option.value = opcion;
    option.textContent = opcion === '' ? '—' : opcion;
    if (String(item.value || '') === opcion) {
      option.selected = true;
    }
    select.appendChild(option);
  });
  etiqueta.appendChild(select);
  fila.appendChild(etiqueta);

  if (typeof opts.onCambio === 'function') {
    select.addEventListener('change', () => opts.onCambio(select, fila));
  }

  if (opts.registroSelects) {
    opts.registroSelects[item.key] = select;
  }

  return fila;
};

// Envoltura del globo: reenvia el cambio a `datosFila.guardar` (Task 7),
// con el mismo manejo de error/reintento y actualizacion del avance de
// siempre.
const construirFilaRestriccionGlobo = (item, datosFila, avanceEl, registroSelects) => {
  let filaRef = null;
  const intentar = () => {
    if (!filaRef || typeof datosFila.guardar !== 'function') {
      return;
    }
    limpiarFilaError(filaRef);
    const select = filaRef.querySelector('select');
    const valorNuevo = select.value;
    datosFila.guardar(item.key, valorNuevo, (resultado) => {
      if (resultado && resultado.ok) {
        limpiarFilaError(filaRef);
        if (avanceEl && resultado.avance != null) {
          avanceEl.textContent = formatearAvance(resultado.avance);
        }
        return;
      }
      marcarFilaError(filaRef, (resultado && resultado.message) || 'Error al guardar', intentar);
    });
  };

  filaRef = construirFilaRestriccion(item, {
    disabled: !datosFila.canEdit,
    registroSelects,
    onCambio: intentar,
  });
  return filaRef;
};

const construirGrupo = (titulo, items, datosFila, avanceEl, registroSelects) => {
  if (!items || items.length === 0) {
    return null;
  }
  const grupo = document.createElement('div');
  grupo.className = 'aia-readiness-popover__grupo';

  const rotulo = document.createElement('h4');
  rotulo.className = 'aia-readiness-popover__rotulo';
  rotulo.textContent = titulo;
  grupo.appendChild(rotulo);

  items.forEach((item) => {
    grupo.appendChild(construirFilaRestriccionGlobo(item, datosFila, avanceEl, registroSelects));
  });

  return grupo;
};

const construirGlobo = (datosFila) => {
  const datos = datosFila || {};
  const globo = document.createElement('div');
  globo.className = 'aia-readiness-popover';
  globo.setAttribute('popover', 'manual');
  globo.setAttribute('role', 'dialog');
  globo.setAttribute('aria-modal', 'false');
  globo.tabIndex = -1;

  const cabecera = construirCabecera(datos);
  globo.setAttribute('aria-label', datos.Actividad || 'Habilitación');
  globo.appendChild(cabecera);

  const { marcador, avance } = construirMarcadorAvance(datos);
  globo.appendChild(marcador);

  if (!datos.canEdit && datos.razonSoloLectura) {
    const lectura = document.createElement('p');
    lectura.className = 'aia-readiness-popover__solo-lectura';
    lectura.textContent = datos.razonSoloLectura;
    globo.appendChild(lectura);
  }

  const selects = {};
  const grupoObligatorias = construirGrupo('Obligatorias', datos.obligatorias, datos, avance, selects);
  if (grupoObligatorias) {
    globo.appendChild(grupoObligatorias);
  }
  const grupoSeguimiento = construirGrupo('De seguimiento', datos.seguimiento, datos, avance, selects);
  if (grupoSeguimiento) {
    globo.appendChild(grupoSeguimiento);
  }

  document.body.appendChild(globo);
  return { globo, selects };
};

// Task 10 (2026-08-21): las mismas dos filas de grupo (Obligatorias / De
// seguimiento) que arma el globo, pero como fragmento suelto para que otro
// envase -la tarjeta movil, `pi-mobile-card__detalle`- lo inserte donde le
// convenga. `opcionesFila` viaja tal cual a `construirFilaRestriccion`: es
// lo que le permite a cada envase decidir su propio mecanismo de guardado
// (`onCambio`, `datasetSelect`) sin que esta funcion lo conozca.
const construirCuerpoRestricciones = (datosFila, opcionesFila) => {
  const datos = datosFila || {};
  const contenido = document.createDocumentFragment();

  const armarGrupo = (titulo, items) => {
    if (!items || items.length === 0) {
      return null;
    }
    const grupo = document.createElement('div');
    grupo.className = 'aia-readiness-popover__grupo';

    const rotulo = document.createElement('h4');
    rotulo.className = 'aia-readiness-popover__rotulo';
    rotulo.textContent = titulo;
    grupo.appendChild(rotulo);

    items.forEach((item) => {
      grupo.appendChild(construirFilaRestriccion(item, opcionesFila));
    });

    return grupo;
  };

  const grupoObligatorias = armarGrupo('Obligatorias', datos.obligatorias);
  if (grupoObligatorias) {
    contenido.appendChild(grupoObligatorias);
  }
  const grupoSeguimiento = armarGrupo('De seguimiento', datos.seguimiento);
  if (grupoSeguimiento) {
    contenido.appendChild(grupoSeguimiento);
  }

  return contenido;
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

// Step 7 (Task 7): Ctrl+Z (Cmd+Z en mac) llama a `estado.datosFila.deshacer()`
// -NO al `hot.undo()` nativo de Handsontable-. Verificado en vivo: las siete
// props de restriccion dejaron de ser columnas propias de la grilla desde
// que la Task 4 las fundio en `__habilitacion`, asi que `propToCol('D_y_E')`
// (y equivalentes) no resuelve, y el `ChangeAction.undo()` nativo de
// Handsontable revienta al intentar `setDataAtCell(row, columna)` con una
// columna inexistente. `deshacer()` (armado en `hot.js`) es por eso un unico
// nivel de deshacer PROPIO del globo -mismo camino de escritura que
// `guardar`, `setDataAtRowProp(..., 'edit')`, no un guardado nuevo-, no la
// pila nativa. Tras invocarlo, se sincronizan los `<select>` afectados
// leyendo el dato ya revertido, porque nada dentro del globo escucha
// `afterChange` para refrescarse solo.
const alDeshacer = (ev) => {
  if (!estado || !(ev.ctrlKey || ev.metaKey) || ev.key.toLowerCase() !== 'z') {
    return;
  }
  if (typeof estado.datosFila.deshacer !== 'function') {
    return;
  }
  ev.preventDefault();
  estado.datosFila.deshacer();
  if (typeof estado.datosFila.leerValor !== 'function') {
    return;
  }
  Object.keys(estado.selects).forEach((prop) => {
    const select = estado.selects[prop];
    const valor = estado.datosFila.leerValor(prop);
    select.value = String(valor == null ? '' : valor);
  });
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
  if (ev.key === 'ArrowUp' || ev.key === 'ArrowDown') {
    ev.preventDefault();
    irA(ev.key === 'ArrowUp' ? -1 : 1);
    return;
  }
  alDeshacer(ev);
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

  const { globo, selects } = construirGlobo(datosFila);
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
  estado = { celda, globo, datosFila: datosFila || {}, selects };
  colocar(celda, globo);
  globo.focus();
  document.addEventListener('keydown', alTeclado, true);
  document.addEventListener('click', alClicAfuera, true);
}

// Task 8: `direccion` es -1 (anterior) o 1 (siguiente). El globo NO sigue al
// raton -saltar solo ocurre por este boton/tecla explicitos, nunca porque el
// cursor paso sobre otra fila- y no cierra el globo actual antes de saber si
// hay destino: si `hot.js` no encuentra una fila siguiente/anterior (extremo
// de la tabla, o solo quedan capitulos), la funcion correspondiente
// simplemente no hace nada y el globo se queda como estaba.
function irA(direccion) {
  if (!estado) {
    return;
  }
  const funcion = direccion < 0 ? estado.datosFila.anterior : estado.datosFila.siguiente;
  if (typeof funcion !== 'function') {
    return;
  }
  funcion();
}

export const AIAReadinessPopover = { abrir, cerrar, irA, construirCuerpoRestricciones };

if (typeof window !== 'undefined') {
  window.AIAReadinessPopover = AIAReadinessPopover;
}
