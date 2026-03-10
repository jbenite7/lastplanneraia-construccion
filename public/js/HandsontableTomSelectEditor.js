/**
 * Custom Handsontable Editor for Multiple and Single Select using Tom Select
 * Replaces Select2 to fix severe scroll-locking and DOM mutation bugs.
 */
(function (Handsontable, $) {
  'use strict';

  // ──────────────────────────────────────────────
  // Tom Select MULTIPLE Editor
  // ──────────────────────────────────────────────
  var TomSelectEditor = Handsontable.editors.BaseEditor.prototype.extend();

  TomSelectEditor.prototype.init = function () {
    this.$wrapper = $('<div class="htTomSelectWrapper" style="display:none; position:absolute; z-index:99999; background:#fff; padding: 4px; box-sizing: border-box; border: 1px solid #5292f7;"></div>');
    this.$select = $('<select multiple placeholder="Seleccione..." style="width: 100%; box-sizing: border-box; min-width: 200px; display: block;"></select>');
    this.$wrapper.append(this.$select);
    $('body').append(this.$wrapper);
    this.tomSelectInstance = null;
  };

  TomSelectEditor.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.select2Options || cellProperties.tomSelectOptions || [];
    
    // Preparar opciones en formato Tom Select: [{value: 'A', text: 'A'}, ...]
    this.tomOptions = this.options.map(function(opt) {
        return { value: opt, text: opt };
    });
  };

  TomSelectEditor.prototype.open = function () {
    var _this = this;

    // Posicionar el wrapper sobre la celda activa flotando en el body
    var tdOffset = $(this.TD).offset();
    var tdWidth = $(this.TD).outerWidth();
    var tdHeight = $(this.TD).outerHeight();
    var wrapperWidth = Math.max(260, tdWidth);

    this.$wrapper.css({
      top: tdOffset.top + 'px',
      left: tdOffset.left + 'px',
      width: wrapperWidth + 'px',
      minHeight: tdHeight + 'px',
      display: 'block'
    });

    // Barrera de Cristal: Vital para evitar que al clickear opciones HOT cierre la celda.
    this.$wrapper.off('mousedown.htAislado').on('mousedown.htAislado', function(e) {
      e.stopPropagation();
      // Si el usuario hace clic en el área blanca sobrante del editor, enfocar el select
      if (!$(e.target).closest('.ts-wrapper').length) {
         if (_this.tomSelectInstance) {
             setTimeout(function() { _this.tomSelectInstance.focus(); }, 10);
         }
      }
    });

    // Destruir instancia previa si por si acaso quedó viva
    if (this.tomSelectInstance) {
        this.tomSelectInstance.destroy();
    }

    // Inicializar Tom Select
    this.tomSelectInstance = new TomSelect(this.$select[0], {
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        options: this.tomOptions,
        plugins: ['remove_button', 'clear_button'],
        maxOptions: null,
        closeAfterSelect: false,
        hideSelected: true,
        render: {
            option: function(data, escape) {
                // Resaltar opciones de Creación (➕ Crear...)
                if (data.text.indexOf('➕') > -1) {
                    return '<div style="color: #059669; font-weight: 600; background-color: #ecfdf5; border-top: 1px solid #d1fae5; padding: 8px 12px;">' + escape(data.text) + '</div>';
                }
                return '<div style="padding: 8px 12px;">' + escape(data.text) + '</div>';
            }
        }
    });

    // Eventos
    this.tomSelectInstance.on('item_add', function(value) {
        if (value.indexOf('➕') > -1) {
            _this.finishEditing();
        }
    });

    // Timeout para asegurar que se abre sin bloqueos visuales
    setTimeout(function() {
        if (_this.tomSelectInstance) {
            _this.tomSelectInstance.focus();
        }
    }, 10);

    // Capturar teclado para navegación nativa de Handsontable
    this._keydownCaptureHandler = function (e) {
      var isTab = e.keyCode === 9;
      var isEsc = e.keyCode === 27;
      var isArrow = (e.keyCode >= 37 && e.keyCode <= 40);

      if (isTab || isEsc || isArrow) {
        // Permitir flechas Arriba/Abajo si el dropdown de Tom Select está abierto
        if ((e.keyCode === 38 || e.keyCode === 40) && _this.tomSelectInstance && _this.tomSelectInstance.isOpen) {
            return; // Dejar que Tom Select navegue sus opciones
        }

        // Si es Tab, Esc, o flechas (estando cerrado), delegar a Handsontable
        e.preventDefault();
        e.stopPropagation();
        _this.finishEditing();

        var instance = _this.hot;
        if (instance) {
            instance.listen(); // Despertar Handsontable
            if (isTab || isArrow) {
                var moveCol = 0;
                var moveRow = 0;
                if (isTab) {
                    moveCol = e.shiftKey ? -1 : 1;
                } else {
                    if (e.keyCode === 37) moveCol = -1;
                    else if (e.keyCode === 39) moveCol = 1;
                    else if (e.keyCode === 38) moveRow = -1;
                    else if (e.keyCode === 40) moveRow = 1;
                }

                var newCol = _this.col + moveCol;
                var newRow = _this.row + moveRow;

                if (newCol >= instance.countCols()) { newCol = 0; newRow += 1; }
                else if (newCol < 0) { newCol = instance.countCols() - 1; newRow -= 1; }

                if (newRow >= 0 && newRow < instance.countRows()) {
                    if (typeof window !== 'undefined') window.__piPendingNav = true;
                    instance.selectCell(newRow, newCol, newRow, newCol, true, false);
                }
            }
        }
      }
    };
    
    document.addEventListener('keydown', this._keydownCaptureHandler, true);
  };

  TomSelectEditor.prototype.close = function () {
    if (this._keydownCaptureHandler) {
      document.removeEventListener('keydown', this._keydownCaptureHandler, true);
      this._keydownCaptureHandler = null;
    }

    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }
    
    this.$wrapper.hide();
    
    // Resucitar el Event Manager de Handsontable para evitar el Freeze total
    if (this.hot) {
      this.hot.listen();
    }
  };

  TomSelectEditor.prototype.getValue = function () {
    if (!this.tomSelectInstance) return '';
    var val = this.tomSelectInstance.getValue() || [];
    // Tom Select returns an array for multiple. HOT expects a comma-separated string.
    return Array.isArray(val) ? val.join(', ') : val;
  };

  TomSelectEditor.prototype.setValue = function (value) {
    if (!this.tomSelectInstance) return;
    if (!value) {
      this.tomSelectInstance.clear(true);
    } else {
      var selectedArr = String(value)
        .split(',')
        .map(function (item) {
          return item.trim();
        });
      this.tomSelectInstance.setValue(selectedArr, true);
    }
  };

  TomSelectEditor.prototype.focus = function () {
    if (this.tomSelectInstance) {
        this.tomSelectInstance.focus();
    }
  };

  Handsontable.editors.registerEditor('tomSelectMultiple', TomSelectEditor);

  // ──────────────────────────────────────────────
  // Tom Select SINGLE Editor
  // ──────────────────────────────────────────────
  var TomSelectSingle = Handsontable.editors.BaseEditor.prototype.extend();

  TomSelectSingle.prototype.init = function () {
    this.$wrapper = $('<div class="htTomSelectWrapper" style="display:none; position:absolute; z-index:99999; background:#fff; padding: 4px; box-sizing: border-box; border: 1px solid #5292f7;"></div>');
    this.$select = $('<select placeholder="Seleccione..." style="width: 100%; box-sizing: border-box; min-width: 200px; display: block;"></select>');
    this.$wrapper.append(this.$select);
    $('body').append(this.$wrapper);
    this.tomSelectInstance = null;
  };

  TomSelectSingle.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.select2Options || cellProperties.tomSelectOptions || [];
    
    this.tomOptions = this.options.map(function(opt) {
        return { value: opt, text: opt };
    });
  };

  TomSelectSingle.prototype.open = function () {
    var _this = this;

    var tdOffset = $(this.TD).offset();
    var tdWidth = $(this.TD).outerWidth();
    var tdHeight = $(this.TD).outerHeight();
    var wrapperWidth = Math.max(260, tdWidth);

    this.$wrapper.css({
      top: tdOffset.top + 'px',
      left: tdOffset.left + 'px',
      width: wrapperWidth + 'px',
      minHeight: tdHeight + 'px',
      display: 'block'
    });

    // Barrera de Cristal: Vital para evitar que al clickear opciones HOT cierre la celda.
    this.$wrapper.off('mousedown.htAislado').on('mousedown.htAislado', function(e) {
      e.stopPropagation();
      // Si el usuario hace clic en el área blanca sobrante del editor, enfocar el select
      if (!$(e.target).closest('.ts-wrapper').length) {
         if (_this.tomSelectInstance) {
             setTimeout(function() { _this.tomSelectInstance.focus(); }, 10);
         }
      }
    });
    if (this.tomSelectInstance) {
        this.tomSelectInstance.destroy();
    }

    this.tomSelectInstance = new TomSelect(this.$select[0], {
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        options: this.tomOptions,
        plugins: ['clear_button'],
        maxOptions: null,
        render: {
            option: function(data, escape) {
                if (data.text.indexOf('➕') > -1) {
                    return '<div style="color: #059669; font-weight: 600; background-color: #ecfdf5; border-top: 1px solid #d1fae5; padding: 8px 12px;">' + escape(data.text) + '</div>';
                }
                return '<div style="padding: 8px 12px;">' + escape(data.text) + '</div>';
            }
        }
    });

    this.tomSelectInstance.on('item_add', function(value) {
        _this.finishEditing();
    });

    setTimeout(function() {
        if (_this.tomSelectInstance) {
            _this.tomSelectInstance.focus();
        }
    }, 10);

    this._keydownCaptureHandlerSingle = function (e) {
      var isTab = e.keyCode === 9;
      var isEsc = e.keyCode === 27;
      var isArrow = (e.keyCode >= 37 && e.keyCode <= 40);

      if (isTab || isEsc || isArrow) {
        if ((e.keyCode === 38 || e.keyCode === 40) && _this.tomSelectInstance && _this.tomSelectInstance.isOpen) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        _this.finishEditing();

        var instance = _this.hot;
        if (instance) {
            instance.listen();
            if (isTab || isArrow) {
                var moveCol = 0;
                var moveRow = 0;
                if (isTab) {
                    moveCol = e.shiftKey ? -1 : 1;
                } else {
                    if (e.keyCode === 37) moveCol = -1;
                    else if (e.keyCode === 39) moveCol = 1;
                    else if (e.keyCode === 38) moveRow = -1;
                    else if (e.keyCode === 40) moveRow = 1;
                }

                var newCol = _this.col + moveCol;
                var newRow = _this.row + moveRow;

                if (newCol >= instance.countCols()) { newCol = 0; newRow += 1; }
                else if (newCol < 0) { newCol = instance.countCols() - 1; newRow -= 1; }

                if (newRow >= 0 && newRow < instance.countRows()) {
                    if (typeof window !== 'undefined') window.__piPendingNav = true;
                    instance.selectCell(newRow, newCol, newRow, newCol, true, false);
                }
            }
        }
      }
    };
    
    document.addEventListener('keydown', this._keydownCaptureHandlerSingle, true);
  };

  TomSelectSingle.prototype.close = function () {
    if (this._keydownCaptureHandlerSingle) {
      document.removeEventListener('keydown', this._keydownCaptureHandlerSingle, true);
      this._keydownCaptureHandlerSingle = null;
    }

    if (this.tomSelectInstance) {
      this.tomSelectInstance.destroy();
      this.tomSelectInstance = null;
    }
    
    this.$wrapper.hide();
    
    if (this.hot) {
      this.hot.listen();
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
    if (this.tomSelectInstance) {
        this.tomSelectInstance.focus();
    }
  };

  Handsontable.editors.registerEditor('tomSelectSingle', TomSelectSingle);

})(Handsontable, jQuery);
