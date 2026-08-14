/**
 * Pieza canonica del menu flotante del shell (spec 2026-08-14, decisiones D1-D4).
 *
 * Responsabilidad unica: por debajo de un umbral, convertir un contenedor de
 * navegacion en flotante -oculto, con boton disparador, velo, cierre por
 * Escape/clic-fuera/eleccion de destino, y foco atrapado mientras esta abierto.
 *
 * NO sabe que es un sidebar ni que es una preferencia de usuario: no lee ni
 * escribe localStorage en ningun punto. Esa separacion es la que hace que la
 * decision D3 (la preferencia manda solo por encima del umbral) se cumpla por
 * construccion en el consumidor, no por disciplina aqui.
 */

export const UMBRAL_FLOTANTE = 1180;

export function debeSerFlotante(ancho, umbral = UMBRAL_FLOTANTE) {
  const medido = Number(ancho);
  if (!Number.isFinite(medido)) return false;
  return medido < umbral;
}

const FOCUSABLES = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

export function crearShellDrawer({ contenedor, disparador, umbral = UMBRAL_FLOTANTE, velo }) {
  if (!contenedor || !disparador) {
    throw new Error('shell-drawer necesita contenedor y disparador');
  }
  let abierto = false;

  function focoDentro(evento) {
    if (!abierto || evento.key !== 'Tab') return;
    const focusables = [...contenedor.querySelectorAll(FOCUSABLES)].filter(
      (el) => el.offsetParent !== null,
    );
    if (focusables.length === 0) return;
    const primero = focusables[0];
    const ultimo = focusables[focusables.length - 1];
    if (evento.shiftKey && document.activeElement === primero) {
      evento.preventDefault();
      ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
      evento.preventDefault();
      primero.focus();
    }
  }

  function abrir() {
    if (abierto) return;
    abierto = true;
    contenedor.dataset.shellDrawerOpen = 'true';
    disparador.setAttribute('aria-expanded', 'true');
    if (velo) velo.hidden = false;
    const primero = contenedor.querySelector(FOCUSABLES);
    if (primero) primero.focus();
  }

  function cerrar({ devolverFoco = true } = {}) {
    if (!abierto) return;
    abierto = false;
    delete contenedor.dataset.shellDrawerOpen;
    disparador.setAttribute('aria-expanded', 'false');
    if (velo) velo.hidden = true;
    if (devolverFoco) disparador.focus();
  }

  function sincronizarModo() {
    const flotante = debeSerFlotante(window.innerWidth, umbral);
    contenedor.dataset.shellDrawerMode = flotante ? 'flotante' : 'fijo';
    disparador.hidden = !flotante;
    if (!flotante) cerrar({ devolverFoco: false });
  }

  function alClicDisparador() {
    if (abierto) cerrar();
    else abrir();
  }

  function alClicVelo() {
    cerrar();
  }

  function alClicContenedor(evento) {
    if (evento.target.closest('a[href]')) cerrar({ devolverFoco: false });
  }

  function alTeclado(evento) {
    if (evento.key === 'Escape' && abierto) {
      evento.preventDefault();
      cerrar();
    }
    focoDentro(evento);
  }

  disparador.addEventListener('click', alClicDisparador);
  if (velo) velo.addEventListener('click', alClicVelo);
  contenedor.addEventListener('click', alClicContenedor);
  document.addEventListener('keydown', alTeclado);
  window.addEventListener('resize', sincronizarModo);
  sincronizarModo();

  // Sin esto, cada re-montaje del modulo consumidor (p. ej. una vista que
  // reinicializa el shell tras un cambio de proyecto) apilaria un listener
  // de keydown mas en document, que nunca se recolecta.
  function destruir() {
    cerrar({ devolverFoco: false });
    disparador.removeEventListener('click', alClicDisparador);
    if (velo) velo.removeEventListener('click', alClicVelo);
    contenedor.removeEventListener('click', alClicContenedor);
    document.removeEventListener('keydown', alTeclado);
    window.removeEventListener('resize', sincronizarModo);
  }

  return { abrir, cerrar, estaAbierto: () => abierto, sincronizarModo, destruir };
}

if (typeof window !== 'undefined') {
  window.AIAShellDrawer = { UMBRAL_FLOTANTE, debeSerFlotante, crearShellDrawer };
}
