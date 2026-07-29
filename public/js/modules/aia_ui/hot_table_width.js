/**
 * Ancho declarado de la tabla de Handsontable.
 *
 * EL PROBLEMA. `public/css/handsontable-module.css:127-131` fuerza
 * `table-layout: auto !important` sobre `#hot-container table` desde
 * `layer(vendor)`, anulando el `table-layout: fixed` que el propio vendor
 * declara en `.handsontable table.htCore`. Con `auto` el navegador trata los
 * `<col width>` del colgroup como sugerencias y colapsa la tabla al ancho de su
 * contenido: la grilla queda a media anchura dentro de un `wtHider` que sí mide
 * lo correcto. El `width: 100% !important` de esa misma regla no lo compensa,
 * porque el padre directo de la tabla es `.wtSpreader`, que Handsontable
 * mantiene a `width: 0`, así que el 100% resuelve a cero.
 *
 * EL ESCAPE, que ya existía. `handsontable-module.css:134-138` devuelve
 * `table-layout: fixed !important` cuando `#hot-container` lleva la clase
 * `hot-fixed-columns`, y toma el ancho de `--hot-table-width`. Cinco módulos
 * —programa-general, programacion-intermedia, programacion-semanal,
 * listado-actividades y contratos— ya lo aplican a mano tras calcular sus
 * anchos de columna. Los tres que no lo hacían (profesionales, subcontratistas,
 * programa-general-actualizar) eran exactamente los tres que se veían
 * estrechos.
 *
 * POR QUÉ NO SE ARREGLA EN CSS. Para declaraciones `!important` el orden de
 * capas se invierte, así que `layer(vendor)` gana a `layer(module)` y a
 * cualquier hoja sin capa. Una regla en la hoja del módulo no alcanza al
 * `auto !important`; se comprobó y no surte efecto.
 *
 * POR QUÉ LEE EL COLGROUP. Los cinco módulos que ya lo aplican suman sus
 * propias constantes de ancho de columna. Aquí se lee la suma real que
 * Handsontable acaba de escribir en el colgroup del master, que incluye la
 * columna de encabezados de fila y no exige conocer el reparto de cada vista.
 * Es la misma cifra que el `wtHider`, medida en lugar de reconstruida.
 */
(function () {
  'use strict';

  function sumarColgroup(container) {
    var cols = container.querySelectorAll('.ht_master colgroup col');
    if (!cols.length) return 0;

    var total = 0;
    for (var i = 0; i < cols.length; i += 1) {
      total += parseFloat(cols[i].style.width) || 0;
    }

    return total;
  }

  /**
   * Fija el ancho declarado de la tabla a partir del colgroup ya renderizado.
   * Idempotente: se puede llamar tras cada render o resize.
   *
   * @param {HTMLElement|string} target `#hot-container` o su id.
   * @returns {number} el ancho aplicado, o 0 si no había nada que medir.
   */
  function sincronizarAnchoTabla(target) {
    var container = typeof target === 'string'
      ? document.getElementById(target)
      : target;
    if (!container) return 0;

    var ancho = sumarColgroup(container);
    if (!ancho) return 0;

    container.classList.add('hot-fixed-columns');
    container.style.setProperty('--hot-table-width', ancho + 'px');

    return ancho;
  }

  window.AIA = window.AIA || {};
  window.AIA.sincronizarAnchoTabla = sincronizarAnchoTabla;
}());
