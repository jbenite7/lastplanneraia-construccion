/**
 * HandsontableTomSelectEditor.js
 * Reemplazo de Select2. Usa pointerdown (dispara ANTES que mousedown/capture de HOT)
 * para pre-seleccionar el ítem en Tom Select antes de que HOT llame finishEditing() / getValue().
 * Sin stopPropagation / stopImmediatePropagation — no se bloquea ningún evento.
 */
(function (Handsontable, $) {
  'use strict';

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
      hideSelected:      true
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
      var optionEl = e.target.closest ? e.target.closest('.ts-option') : null;
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

    // Keydown: Tab/Esc/Flechas fuera del dropdown → delegar a HOT
    this._keydownHandler = function (e) {
      var isTab   = e.keyCode === 9;
      var isEsc   = e.keyCode === 27;
      var isArrow = e.keyCode >= 37 && e.keyCode <= 40;
      if (!isTab && !isEsc && !isArrow) return;
      if ((e.keyCode === 38 || e.keyCode === 40) && _this.tomSelectInstance && _this.tomSelectInstance.isOpen) return;
      e.preventDefault();
      e.stopPropagation();
      _this.finishEditing();
      if (_this.hot) { _this.hot.listen(); }
    };
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
    this.$wrapper.hide();
    if (this.hot) { this.hot.listen(); }
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
    this.tomOptions = this.options.map(function(opt) { return { value: opt, text: opt }; });
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
      maxOptions:   null
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

    // ─── FIX PRINCIPAL ─────────────────────────────────────────────
    this._pointerdownHandler = function (e) {
      var optionEl = e.target.closest ? e.target.closest('.ts-option') : null;
      if (optionEl && _this.tomSelectInstance) {
        var val = optionEl.getAttribute('data-value');
        if (val !== null) {
          _this.tomSelectInstance.setValue(val, true); // silent
        }
      }
    };
    this.$wrapper[0].addEventListener('pointerdown', this._pointerdownHandler, false);
    // ───────────────────────────────────────────────────────────────

    this._keydownHandlerSingle = function (e) {
      var isTab   = e.keyCode === 9;
      var isEsc   = e.keyCode === 27;
      var isArrow = e.keyCode >= 37 && e.keyCode <= 40;
      if (!isTab && !isEsc && !isArrow) return;
      if ((e.keyCode === 38 || e.keyCode === 40) && _this.tomSelectInstance && _this.tomSelectInstance.isOpen) return;
      e.preventDefault();
      e.stopPropagation();
      _this.finishEditing();
      if (_this.hot) { _this.hot.listen(); }
    };
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
    this.$wrapper.hide();
    if (this.hot) { this.hot.listen(); }
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

  Handsontable.editors.registerEditor('tomSelectSingle', TomSelectSingle);

})(Handsontable, jQuery);
