/**
 * HandsontableTomSelectEditor.js
 *
 * Editor de celda de Handsontable montado sobre Tom Select. Lo usan tres
 * columnas: Sub-Contratista (múltiple) y Responsable AIA (simple) en
 * `/programacion-intermedia`, y una columna simple en
 * `/programa-general-actualizar`. Cambiar algo aquí las toca a las tres.
 *
 * El ratón se resuelve con `pointerdown`, que dispara ANTES que el `mousedown`
 * en captura de HOT: así el ítem ya está elegido cuando HOT llama a
 * `getValue()`. Ese truco funciona y se conserva.
 *
 * El teclado NO se resolvía. Auditado el 2026-08-18: Enter no confirmaba sino
 * que asignaba otro subcontratista, Tab no avanzaba de celda, las flechas
 * horizontales destruían lo escrito en el buscador y el foco acababa en
 * `<body>`. Ahora hay un contrato de teclas único —`atiendeTecla()`— para las
 * dos variantes, y la ventana flotante sigue a su celda al hacer scroll en vez
 * de quedarse clavada sobre otra fila.
 *
 * Es una reparación, no el destino: el plan acordado es sustituir esta pieza y
 * Select2 por un control propio único, portando `ListaBuscable` del Plan de
 * Compras. Por eso aquí no se unifican las dos clases casi gemelas ni se
 * rediseña el desplegable: lo que se arregla es lo que duele hoy.
 */
(function (Handsontable, $) {
  'use strict';

  // ══════════════════════════════════════════════════════════════════
  // Piezas comunes a las dos variantes del editor
  //
  // Viven aquí, y no duplicadas dentro de cada clase, porque el contrato
  // de teclas es UNO: si diverge entre la variante simple y la múltiple,
  // la misma tecla hace dos cosas distintas en dos columnas contiguas de
  // la misma tabla. Las dos clases siguen siendo casi gemelas —eso lo
  // resuelve el control único que las sustituirá—, pero lo que gobierna
  // el teclado ya no se copia.
  // ══════════════════════════════════════════════════════════════════

  /** La opción «➕ Crear …» no es un valor: es una acción disfrazada de opción. */
  function esOpcionCrear(valor) {
    var v = String(valor == null ? '' : valor);
    return v.indexOf('➕') > -1 || v.indexOf('Crear') > -1;
  }

  /**
   * Mueve la selección del grid y, con ella, el foco del DOM.
   *
   * Medido el 2026-08-18: al cerrar el editor el foco caía en `<body>`, así que
   * la celda se veía seleccionada pero el navegador estaba en otro sitio. HOT
   * pone el foco en el `TD` al llamar `selectCell`, de modo que mover y enfocar
   * son la misma operación. Los índices se recortan al tablero: pedir una
   * columna que no existe deja la selección donde estaba, no la pierde.
   */
  function moverSeleccion(hot, deltaFila, deltaCol) {
    if (!hot || hot.isDestroyed) return;
    var sel = hot.getSelectedLast();
    if (!sel) return;
    var fila = Math.min(Math.max(sel[0] + deltaFila, 0), hot.countRows() - 1);
    var col  = Math.min(Math.max(sel[1] + deltaCol, 0), hot.countCols() - 1);
    hot.selectCell(fila, col);
  }

  /**
   * Devuelve el foco del navegador a la celda seleccionada.
   *
   * Se llama al CERRAR, no al pulsar la tecla, y ese orden es el hallazgo:
   * `finishEditing()` de HOT no cierra en el acto, así que enfocar la celda
   * desde el manejador de teclas funcionaba un instante y luego la destrucción
   * de Tom Select se llevaba el foco a `<body>`. Con Tab no se notaba —el
   * cambio de selección de HOT vuelve a enfocar por su cuenta—, pero con Enter
   * y Escape, que confirman sin moverse, el teclado quedaba fuera de la tabla.
   * Medido con `programacion-intermedia-editor-teclado.spec.mjs`.
   */
  function devolverFocoACelda(hot) {
    if (!hot || hot.isDestroyed) return;
    var sel = hot.getSelectedLast();
    if (!sel) return;
    var celda = hot.getCell(sel[0], sel[1]);
    if (celda && typeof celda.focus === 'function') celda.focus();
  }

  /**
   * El contrato de teclas del editor. Devuelve true si ya atendió la pulsación.
   *
   * Lo que arregla, todo medido en `/programacion-intermedia` el 2026-08-18:
   *
   * - **Enter no confirmaba: añadía otro subcontratista.** Dos Enter dejaban
   *   `TRANSCAR ANTIOQUIA SAS, ARQUITOP SAS` en la celda sin que nadie eligiera
   *   el segundo: al ocultarse el ya elegido (`hideSelected`), la opción activa
   *   se corre sola. En el resto del grid Enter termina la edición; aquí no
   *   existía ninguna tecla que confirmara.
   * - **Tab confirmaba pero no avanzaba de celda**, mientras que en una celda
   *   normal Tab sí avanza (comprobado: columna 1 → 2). Además soltaba el foco.
   * - **← y → cerraban el editor**, así que corregir una letra del buscador
   *   destruía lo escrito.
   * - **↑ y ↓ con la lista cerrada** cerraban y se comían la pulsación: ni
   *   editaban ni navegaban.
   */
  function atiendeTecla(e, editor, esMultiple) {
    var ts = editor.tomSelectInstance;
    if (!ts) return false;

    var tecla = e.key;
    var listaAbierta = !!ts.isOpen;
    var entrada = ts.control_input;
    var buscadorVacio = !entrada || entrada.value === '';

    function detener() { e.preventDefault(); e.stopPropagation(); }
    function activa() {
      return ts.activeOption ? ts.activeOption.getAttribute('data-value') : null;
    }

    if (tecla === 'Escape') {
      detener();
      editor.finishEditing(true);
      moverSeleccion(editor.hot, 0, 0);
      return true;
    }

    if (tecla === 'Enter') {
      detener();
      var valor = listaAbierta ? activa() : null;
      if (valor !== null && esOpcionCrear(valor)) {
        // El flujo de creación ya lo gobierna el listener `item_add`: añade,
        // se retira solo y abre el módulo. No se confirma aquí.
        ts.addItem(valor);
        return true;
      }
      if (valor !== null) ts.addItem(valor, true);
      editor.finishEditing();
      moverSeleccion(editor.hot, 0, 0);
      return true;
    }

    // Espacio sobre la opción resaltada: añade sin cerrar. Es la vía de teclado
    // para asignar varios, ahora que Enter termina. Solo con el buscador vacío:
    // mientras se escribe, un espacio es un espacio.
    if (esMultiple && (tecla === ' ' || tecla === 'Spacebar') && buscadorVacio && listaAbierta) {
      var valorEspacio = activa();
      if (valorEspacio !== null && !esOpcionCrear(valorEspacio)) {
        detener();
        if (ts.items.indexOf(valorEspacio) === -1) ts.addItem(valorEspacio, true);
        else ts.removeItem(valorEspacio, true);
        return true;
      }
      return false;
    }

    if (tecla === 'Tab') {
      detener();
      editor.finishEditing();
      moverSeleccion(editor.hot, 0, e.shiftKey ? -1 : 1);
      return true;
    }

    if (tecla === 'ArrowUp' || tecla === 'ArrowDown') {
      if (listaAbierta) return false;           // navegar la lista es de Tom Select
      detener();
      editor.finishEditing();
      moverSeleccion(editor.hot, tecla === 'ArrowDown' ? 1 : -1, 0);
      return true;
    }

    if (tecla === 'ArrowLeft' || tecla === 'ArrowRight') {
      if (!buscadorVacio) return false;         // el cursor se mueve dentro del texto
      detener();
      editor.finishEditing();
      moverSeleccion(editor.hot, 0, tecla === 'ArrowRight' ? 1 : -1);
      return true;
    }

    return false;
  }

  /**
   * Mantiene la ventana flotante pegada a su celda mientras se hace scroll.
   *
   * Sin esto —medido el 2026-08-18— el desplegable se quedaba clavado en el
   * mismo punto de la pantalla y acababa flotando sobre OTRA actividad, mientras
   * seguía editando la de origen. Es la vía rápida para asignar un subcontratista
   * a la fila equivocada sin enterarse.
   *
   * Si la celda deja de estar a la vista (o HOT la recicla al virtualizar las
   * filas), se confirma y se cierra: seguir editando algo invisible es peor que
   * cerrar.
   */
  function instalarReanclaje(editor) {
    var raiz = editor.hot && editor.hot.rootElement;
    var contenedor = raiz ? raiz.querySelector('.ht_master .wtHolder') : null;

    editor._reanclar = function () {
      if (!editor.tomSelectInstance) return;
      var td = editor.TD;
      if (!td || !td.isConnected) { editor.finishEditing(); return; }

      var celda = td.getBoundingClientRect();
      var limite = contenedor ? contenedor.getBoundingClientRect() : null;
      var fueraDeVista = limite
        ? (celda.bottom <= limite.top || celda.top >= limite.bottom ||
           celda.right <= limite.left || celda.left >= limite.right)
        : (celda.bottom <= 0 || celda.top >= window.innerHeight);
      if (fueraDeVista) { editor.finishEditing(); return; }

      editor.$wrapper.css({ top: celda.top + 'px', left: celda.left + 'px' });
      editor.aplicarFlip(celda);
    };

    // Quien avisa del desplazamiento es Handsontable, no el DOM.
    //
    // Medido el 2026-08-18: la grilla desplaza su viewport virtual sin que
    // llegue un `scroll` utilizable —ni escuchando en `.ht_master .wtHolder`, ni
    // en `document` en captura—, así que un oyente de DOM dejaba la ventana
    // flotante clavada mientras la fila se iba. Los ganchos propios de HOT sí
    // disparan, y son además el contrato público para esto.
    //
    // El `scroll` de `document` en captura se conserva porque cubre lo otro: que
    // se desplace la página entera por debajo de la grilla.
    editor.hot.addHook('afterScrollVertically', editor._reanclar);
    editor.hot.addHook('afterScrollHorizontally', editor._reanclar);
    document.addEventListener('scroll', editor._reanclar, true);
    window.addEventListener('resize', editor._reanclar);
    editor._contenedorScroll = contenedor;
  }

  function retirarReanclaje(editor) {
    if (!editor._reanclar) return;
    if (editor.hot && !editor.hot.isDestroyed) {
      editor.hot.removeHook('afterScrollVertically', editor._reanclar);
      editor.hot.removeHook('afterScrollHorizontally', editor._reanclar);
    }
    document.removeEventListener('scroll', editor._reanclar, true);
    window.removeEventListener('resize', editor._reanclar);
    editor._reanclar = null;
    editor._contenedorScroll = null;
  }

  /** Abre hacia arriba cuando la celda no deja sitio debajo. */
  function aplicarFlip(celda) {
    var ALTO_MAXIMO = 220;
    var ESPACIO_MINIMO = ALTO_MAXIMO + 12;
    var haciaArriba = (window.innerHeight - celda.bottom) < ESPACIO_MINIMO &&
                       celda.top >= ESPACIO_MINIMO;
    if (haciaArriba) this.$wrapper.attr('data-flip', 'up');
    else this.$wrapper.removeAttr('data-flip');
  }

  /**
   * Pinta una opción. `escape` es el escapador de Tom Select y NO se puede
   * saltar: el nombre del subcontratista lo teclea una persona en otro módulo.
   *
   * La versión anterior deshacía el escapado (`&lt;` → `<`) antes de meter el
   * texto en el DOM, de modo que un nombre con etiquetas se ejecutaba al abrir
   * el desplegable. El emoji «➕» sigue convirtiéndose en icono, pero sobre el
   * texto YA escapado, que es la única parte que puede llevar marcado.
   */
  function pintaOpcion(data, escape) {
    var esCrear = esOpcionCrear(data.value);
    var etiqueta = escape(data.text);
    if (esCrear) etiqueta = etiqueta.replace(/➕/g, '<i class="fas fa-plus"></i>');
    return '<div class="option' + (esCrear ? ' ts-create-option' : '') + '">' + etiqueta + '</div>';
  }

  function pintaItem(data, escape) {
    return '<div class="item">' + escape(data.text) + '</div>';
  }

  // ──────────────────────────────────────────────────────────────────
  // Tom Select MULTIPLE Editor
  // ──────────────────────────────────────────────────────────────────
  var TomSelectEditor = Handsontable.editors.BaseEditor.prototype.extend();

  TomSelectEditor.prototype.init = function () {
    this.$wrapper = $('<div class="htTomSelectWrapper" style="display:none; position:fixed; z-index:10000; background:transparent; padding:0; box-sizing:border-box;"></div>');
    this.$select  = $('<select multiple style="width:100%; box-sizing:border-box; display:block;"></select>');
    this.$wrapper.append(this.$select);
    this.tomSelectInstance = null;
    this._pointerdownHandler = null;
  };

  TomSelectEditor.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.tomSelectOptions || cellProperties.select2Options || [];
    this.tomOptions = this.options.map(function(opt) { return { value: opt, text: opt }; });
  };

  TomSelectEditor.prototype.open = function () {
    var _this = this;

    // Adjuntar al rootElement de HOT para que los clics sean considerados "internos"
    if (!this.$wrapper.parent().is(this.hot.rootElement)) {
      $(this.hot.rootElement).append(this.$wrapper);
    }

    // Posicionamiento con position:fixed superponiendo exactamente sobre la celda
    var tdRect = this.TD.getBoundingClientRect();
    var calculatedWidth = Math.max(300, tdRect.width);

    this.$wrapper.css({
      top:   tdRect.top + 'px',
      left:  tdRect.left + 'px',
      width: calculatedWidth + 'px',
      minHeight: tdRect.height + 'px',
      display: 'block'
    });

    // Destruir instancia previa
    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }

    // Crear un <select> fresco sin estilos de visibilidad; Tom Select lo ocultará
    this.$select.replaceWith('<select multiple style="width:100%; box-sizing:border-box;"></select>');
    this.$select = this.$wrapper.find('select');

    this.tomSelectInstance = new TomSelect(this.$select[0], {
      valueField:       'value',
      labelField:       'text',
      searchField:      'text',
      options:           this.tomOptions,
      plugins: {
        'remove_button': {},
        'clear_button': {
          title: 'Limpiar selección',
          html: function(data) {
            return '<button type="button" class="' + data.className + ' aia-clear-btn" title="' + data.title + '"><i class="fas fa-trash-alt"></i> Limpiar</button>';
          }
        }
      },
      maxOptions:        null,
      closeAfterSelect:  false,
      hideSelected:      true,
      render: { item: pintaItem, option: pintaOpcion }
    });
    // Ocultar explícitamente el <select> nativo por si Bootstrap lo sobre-expone
    this.$select.css('display', 'none');
    
    // Forzar el ancho unificado en todos los niveles del DOM de Tom Select
    if (this.tomSelectInstance) {
      this.tomSelectInstance.wrapper.style.width = calculatedWidth + 'px';
      if (this.tomSelectInstance.dropdown) {
        this.tomSelectInstance.dropdown.style.width = calculatedWidth + 'px';
        this.tomSelectInstance.dropdown.style.left = '0px';
      }
    }

    this.aplicarFlip(tdRect);
    instalarReanclaje(this);

    // Detectar opción "Crear" via item_add (Tom Select la añade en su mousedown):
    // La removemos silenciosamente y navegamos al módulo correcto.
    this.tomSelectInstance.on('item_add', function(value) {
      var isCreate = value.indexOf('\u2795') > -1 || value.indexOf('Crear') > -1;
      if (!isCreate) return;
      if (_this.tomSelectInstance) {
        _this.tomSelectInstance.removeItem(value, true);
      }
      var url = (value.indexOf('Subcontratista') > -1)
        ? '/subcontratistas' : '/profesionales';
      window.open(url, '_blank');
      setTimeout(function() { _this.finishEditing(); }, 0);
    });

    // ─── FIX PRINCIPAL ─────────────────────────────────────────────
    // pointerdown (bubble) dispara ANTES que mousedown (capture) de HOT.
    // Pre-seleccionamos el ítem aquí para que cuando HOT llame getValue(),
    // el valor ya esté actualizado en Tom Select.
    this._pointerdownHandler = function (e) {
      var optionEl = e.target.closest ? e.target.closest('.option') : null;
      if (optionEl && _this.tomSelectInstance) {
        var val = optionEl.getAttribute('data-value');
        if (val !== null) {
          // Opción especial (➕ Crear): el listener item_add lo maneja, aquí solo saltar
          if (val.indexOf('\u2795') > -1 || val.indexOf('Crear') > -1) {
            return;
          }
          // Opción normal: pre-seleccionar antes de que HOT llame getValue()
          var items = _this.tomSelectInstance.items;
          if (items.indexOf(val) === -1) {
            _this.tomSelectInstance.addItem(val, true);
          } else {
            _this.tomSelectInstance.removeItem(val, true);
          }
        }
      }
    };
    this.$wrapper[0].addEventListener('pointerdown', this._pointerdownHandler, false);
    // ───────────────────────────────────────────────────────────────

    this._keydownHandler = function (e) { atiendeTecla(e, _this, true); };
    document.addEventListener('keydown', this._keydownHandler, true);

    // Población de valor inicial
    var currentVal = this.originalValue;
    if (currentVal) {
      var arr = String(currentVal).split(',').map(function(s){ return s.trim(); });
      this.tomSelectInstance.setValue(arr, true);
    }

    setTimeout(function() {
      if (_this.tomSelectInstance) { _this.tomSelectInstance.focus(); }
    }, 10);
  };

  TomSelectEditor.prototype.close = function () {
    if (this._keydownHandler) {
      document.removeEventListener('keydown', this._keydownHandler, true);
      this._keydownHandler = null;
    }
    if (this._pointerdownHandler && this.$wrapper[0]) {
      this.$wrapper[0].removeEventListener('pointerdown', this._pointerdownHandler, false);
      this._pointerdownHandler = null;
    }
    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }
    retirarReanclaje(this);
    this.$wrapper.hide();
    if (this.hot) {
      this.hot.listen();
      devolverFocoACelda(this.hot);
    }
  };

  TomSelectEditor.prototype.getValue = function () {
    if (!this.tomSelectInstance) return '';
    var val = this.tomSelectInstance.getValue() || [];
    return Array.isArray(val) ? val.join(', ') : val;
  };

  TomSelectEditor.prototype.setValue = function (value) {
    if (!this.tomSelectInstance) return;
    if (!value) { this.tomSelectInstance.clear(true); return; }
    var arr = String(value).split(',').map(function(s){ return s.trim(); });
    this.tomSelectInstance.setValue(arr, true);
  };

  TomSelectEditor.prototype.focus = function () {
    if (this.tomSelectInstance) { this.tomSelectInstance.focus(); }
  };

  TomSelectEditor.prototype.aplicarFlip = aplicarFlip;

  Handsontable.editors.registerEditor('tomSelectMultiple', TomSelectEditor);

  // ──────────────────────────────────────────────────────────────────
  // Tom Select SINGLE Editor
  // ──────────────────────────────────────────────────────────────────
  var TomSelectSingle = Handsontable.editors.BaseEditor.prototype.extend();

  TomSelectSingle.prototype.init = function () {
    this.$wrapper = $('<div class="htTomSelectWrapper" style="display:none; position:fixed; z-index:10000; background:transparent; padding:0; box-sizing:border-box;"></div>');
    this.$select  = $('<select style="width:100%; box-sizing:border-box; display:block;"></select>');
    this.$wrapper.append(this.$select);
    this.tomSelectInstance = null;
    this._pointerdownHandler = null;
  };

  TomSelectSingle.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.tomSelectOptions || cellProperties.select2Options || [];
    this.tomOptions = this.options.map(function(opt) {
      if (typeof opt === 'object' && opt !== null) {
        return { value: opt.id || opt.value || '', text: opt.title || opt.text || opt.label || '' };
      }
      return { value: String(opt), text: String(opt) };
    });
  };

  TomSelectSingle.prototype.open = function () {
    var _this = this;

    if (!this.$wrapper.parent().is(this.hot.rootElement)) {
      $(this.hot.rootElement).append(this.$wrapper);
    }

    var tdRect = this.TD.getBoundingClientRect();
    var calculatedWidth = Math.max(300, tdRect.width);

    this.$wrapper.css({
      top:   tdRect.top + 'px',
      left:  tdRect.left + 'px',
      width: calculatedWidth + 'px',
      minHeight: tdRect.height + 'px',
      display: 'block'
    });

    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }

    this.$select.replaceWith('<select style="width:100%; box-sizing:border-box;"></select>');
    this.$select = this.$wrapper.find('select');

    this.tomSelectInstance = new TomSelect(this.$select[0], {
      valueField:  'value',
      labelField:  'text',
      searchField: 'text',
      options:      this.tomOptions,
      plugins: {
        'clear_button': {
          title: 'Limpiar selección',
          html: function(data) {
            return '<button type="button" class="' + data.className + ' aia-clear-btn" title="' + data.title + '"><i class="fas fa-trash-alt"></i> Limpiar</button>';
          }
        }
      },
      maxOptions:        null,
      closeAfterSelect:  true,
      render: { item: pintaItem, option: pintaOpcion }
    });
    this.$select.css('display', 'none');

    // Forzar el ancho unificado en todos los niveles del DOM de Tom Select
    if (this.tomSelectInstance) {
      this.tomSelectInstance.wrapper.style.width = calculatedWidth + 'px';
      if (this.tomSelectInstance.dropdown) {
        this.tomSelectInstance.dropdown.style.width = calculatedWidth + 'px';
        this.tomSelectInstance.dropdown.style.left = '0px';
      }
    }

    this.aplicarFlip(tdRect);
    instalarReanclaje(this);

    // ─── FIX PRINCIPAL ─────────────────────────────────────────────
    this._pointerdownHandler = function (e) {
      var optionEl = e.target.closest ? e.target.closest('.option') : null;
      if (optionEl && _this.tomSelectInstance) {
        var val = optionEl.getAttribute('data-value');
        if (val !== null) {
          _this.tomSelectInstance.setValue(val, false);
          _this.finishEditing();
          // Elegir con el ratón también devuelve el foco a la celda: si no, el
          // teclado queda en `<body>` y la flecha siguiente no navega.
          moverSeleccion(_this.hot, 0, 0);
        }
      }
    };
    this.$wrapper[0].addEventListener('pointerdown', this._pointerdownHandler, false);
    // ───────────────────────────────────────────────────────────────

    this._keydownHandlerSingle = function (e) { atiendeTecla(e, _this, false); };
    document.addEventListener('keydown', this._keydownHandlerSingle, true);

    var currentVal = this.originalValue;
    if (currentVal) { this.tomSelectInstance.setValue(String(currentVal).trim(), true); }

    setTimeout(function() {
      if (_this.tomSelectInstance) { _this.tomSelectInstance.focus(); }
    }, 10);
  };

  TomSelectSingle.prototype.close = function () {
    if (this._keydownHandlerSingle) {
      document.removeEventListener('keydown', this._keydownHandlerSingle, true);
      this._keydownHandlerSingle = null;
    }
    if (this._pointerdownHandler && this.$wrapper[0]) {
      this.$wrapper[0].removeEventListener('pointerdown', this._pointerdownHandler, false);
      this._pointerdownHandler = null;
    }
    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }
    retirarReanclaje(this);
    this.$wrapper.hide();
    if (this.hot) {
      this.hot.listen();
      devolverFocoACelda(this.hot);
    }
  };

  TomSelectSingle.prototype.getValue = function () {
    if (!this.tomSelectInstance) return '';
    return this.tomSelectInstance.getValue() || '';
  };

  TomSelectSingle.prototype.setValue = function (value) {
    if (!this.tomSelectInstance) return;
    this.tomSelectInstance.setValue(value ? String(value).trim() : '', true);
  };

  TomSelectSingle.prototype.focus = function () {
    if (this.tomSelectInstance) { this.tomSelectInstance.focus(); }
  };

  TomSelectSingle.prototype.aplicarFlip = aplicarFlip;

  Handsontable.editors.registerEditor('tomSelectSingle', TomSelectSingle);

})(Handsontable, jQuery);
