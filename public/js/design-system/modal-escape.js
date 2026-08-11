/**
 * `Esc` cierra los modales de backdrop estatico.
 *
 * Con `data-backdrop="static"` Bootstrap no cierra al pulsar fuera, y aunque la
 * instancia reporta `keyboard: true`, la tecla no cierra: la unica salida es la
 * «×». No es trampa de teclado (WCAG 2.1.2) porque la «×» es alcanzable con Tab,
 * pero rompe la convencion que todo usuario intenta primero.
 *
 * Vivia solo en `programacion_intermedia/hot.js` y cubria 2 de los 11 modales
 * del repo. Se extrae en vez de copiarse nueve veces.
 *
 * Se escucha en fase de captura (tercer argumento `true`) a proposito: Handsontable
 * tiene su propio listener de `Escape` en `document` (fase de burbuja) que cancela
 * la celda en edicion y llama a `stopImmediatePropagation`. Si un modal se abre
 * sobre una celda en edicion, ese listener de Handsontable se ejecutaba primero y
 * el `Escape` nunca llegaba a cerrar el modal — hacia falta un segundo `Escape`.
 * Capturando antes de que el evento llegue a burbuja, cerramos el modal en el
 * primer `Escape` sin bloquear que Handsontable siga cancelando la edicion.
 */
export function activarEscapeEnModales() {
  document.addEventListener(
    "keydown",
    (ev) => {
      if (ev.key !== "Escape") return;
      const abierto = document.querySelector(".modal.show");
      if (!abierto) return;
      // `data-aia-escape="off"` deja la puerta abierta a un modal que de verdad
      // no deba cerrarse asi (una confirmacion destructiva a medias, por ejemplo).
      if (abierto.dataset.aiaEscape === "off") return;
      window.jQuery(abierto).modal("hide");
    },
    true,
  );
}
