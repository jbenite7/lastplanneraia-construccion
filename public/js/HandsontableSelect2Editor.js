/**
 * Custom Handsontable Editor for Multiple Select using jQuery Select2
 * Allows selecting multiple options in a single cell, storing them as a comma-separated string.
 */
(function (Handsontable, $) {
  'use strict';

  var Select2Editor = Handsontable.editors.BaseEditor.prototype.extend();

  Select2Editor.prototype.init = function () {
    // Mejoras UI/UX 2026: Se quitan estilos hardcodeados y se añade id/clase base limpia.
    // Los estilos se inyectarán dinámicamente o por clases.
    this.$wrapper = $('<div class="htSelect2Wrapper ht-select2-modern"></div>');
    this.$select = $('<select multiple="multiple" class="ht-select2-field"></select>');
    this.$wrapper.append(this.$select);

    $('body').append(this.$wrapper);

    // Inyectar CSS si no existe
    if ($('#ht-select2-modern-styles').length === 0) {
      var modernStyles = `
                <style id="ht-select2-modern-styles">
                    .ht-select2-modern {
                        display: none;
                        position: absolute;
                        z-index: 10050; /* Por encima de modal-backdrops si hay */
                        background: #ffffff;
                        padding: 0;
                        border: none;
                        border-radius: 8px;
                        box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
                        overflow: visible;
                    }
                    .ht-select2-modern[style*="display: block"] {
                        display: flex !important;
                        flex-direction: column;
                    }
                    /* Overrides para select2-container dentro de Handsontable */
                    .ht-select2-modern .select2-container {
                        position: relative !important;
                        top: auto !important;
                        left: auto !important;
                        width: 100% !important;
                        box-sizing: border-box;
                    }
                    .ht-select2-modern .select2-dropdown {
                        width: 100% !important;
                        margin-top: 0;
                    }
                    .select2-container--default .select2-selection--multiple {
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        min-height: 36px;
                        max-height: 120px;
                        overflow-y: auto;
                        padding: 4px 6px;
                        background: #f8fafc;
                        transition: all 0.2s ease;
                    }
                    .select2-container--default.select2-container--focus .select2-selection--multiple {
                        border-color: #3b82f6;
                        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
                        background: #ffffff;
                    }
                    .select2-container--default.select2-container--open .select2-selection--multiple {
                        border-radius: 8px 8px 0 0;
                        border-bottom-color: transparent;
                    }
                    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 4px;
                        padding: 2px 4px;
                    }
                    .select2-container--default .select2-selection--multiple .select2-selection__choice {
                        display: inline-flex;
                        align-items: center;
                        background-color: #ebf5ff;
                        border: 1px solid #bfdbfe;
                        border-radius: 6px;
                        color: #1e3a8a;
                        margin: 0;
                        padding: 2px 8px;
                        font-weight: 500;
                    }
                    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                        color: #3b82f6;
                        margin-right: 5px;
                        border-right: 1px solid #bfdbfe;
                        padding-right: 5px;
                    }
                    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
                        background-color: transparent;
                        color: #dc2626;
                    }
                    /* Estilos para Select2 SINGLE (Responsable AIA) */
                    .select2-container--default .select2-selection--single {
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        height: 38px;
                        padding: 4px 8px;
                        background: #f8fafc;
                        transition: all 0.2s ease;
                    }
                    .select2-container--default.select2-container--focus .select2-selection--single,
                    .select2-container--default.select2-container--open .select2-selection--single {
                        border-color: #3b82f6;
                        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
                        background: #ffffff;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__rendered {
                        line-height: 30px;
                        color: #1e293b;
                        font-size: 14px;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__placeholder {
                        color: #94a3b8;
                    }
                    .select2-dropdown {
                        border: 1px solid #e2e8f0;
                        border-radius: 0 0 8px 8px;
                        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                        overflow: visible;
                    }
                    .select2-container--default .select2-results__option {
                        padding: 8px 12px;
                        font-size: 14px;
                        color: #334155;
                        border-bottom: 1px solid #f1f5f9;
                    }
                    .select2-container--default .select2-results__option--highlighted[aria-selected] {
                        background-color: #eff6ff;
                        color: #1d4ed8;
                        font-weight: 500;
                    }
                    .select2-container--default .select2-results__option[aria-selected=true] {
                        background-color: #f8fafc;
                        color: #64748b;
                        font-style: italic;
                    }
                    
                    /* Específico para opciones de Creación (+ Crear...) */
                    .select2-results__option[id*="➕"],
                    .select2-results__option[aria-label*="➕"] {
                        color: #059669 !important;
                        font-weight: 600 !important;
                        background-color: #ecfdf5 !important;
                        border-top: 1px solid #d1fae5;
                    }
                    .select2-results__option--highlighted[id*="➕"],
                    .select2-results__option--highlighted[aria-label*="➕"] {
                       background-color: #d1fae5 !important;
                       color: #047857 !important;
                    }
                </style>
            `;
      $('head').append(modernStyles);
    }
  };

  Select2Editor.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.select2Options || [];

    this.$select.empty();
    for (var i = 0; i < this.options.length; i++) {
      this.$select.append(
        '<option value="' + this.options[i] + '">' + this.options[i] + '</option>'
      );
    }
  };

  Select2Editor.prototype.open = function () {
    var _this = this;
    this.closeTimeout = null;

    // ── Montar el wrapper DENTRO del TD activo ──
    $(this.TD).css('position', 'relative').css('overflow', 'visible');
    this.$wrapper.detach().appendTo(this.TD);

    var tdWidth = $(this.TD).outerWidth();
    var wrapperWidth = Math.max(260, tdWidth);

    this.$wrapper.css({
      position: 'absolute',
      top: $(this.TD).outerHeight() + 'px',
      left: '0',
      width: wrapperWidth + 'px',
      display: 'block',
      zIndex: 10050,
    });

    this.$select
      .select2({
        dropdownParent: this.$wrapper,
        allowClear: true,
        placeholder: 'Seleccione...',
        width: '100%',
        closeOnSelect: false,
      })
      .on('select2:close', function () {
        // Reducir latencia de cierre para navegación rápida
        _this.closeTimeout = setTimeout(function () {
          if (
            _this.$select &&
            _this.$select.data('select2') &&
            !_this.$select.data('select2').isOpen()
          ) {
            _this.finishEditing();
          }
        }, 5);
      })
      .on('select2:open', function () {
        if (_this.closeTimeout) {
          clearTimeout(_this.closeTimeout);
        }
        
        // ── El Silver Bullet (Scroll Locking de Select2 Multiple) ──
        setTimeout(function() {
            $(document).off('mousewheel.select2 DOMMouseScroll.select2 wheel.select2 scroll.select2');
            $('html, body').add(window).off('mousewheel.select2 DOMMouseScroll.select2 wheel.select2 scroll.select2');
        }, 0);

        // Desactivar visualmente el buscador sin perder el focus
        _this.$wrapper.find('.select2-search__field').prop('readonly', true).css({
          width: '0px',
          padding: '0',
          border: 'none',
          color: 'transparent',
        });
      });

    // Intercepción NATIVA en fase de captura
    this._keydownCaptureHandler = function (e) {
      var isTab = e.keyCode === 9;
      var isEsc = e.keyCode === 27;
      var isArrow = e.keyCode >= 37 && e.keyCode <= 40;

      if (isTab || isEsc || isArrow) {
        var isOurEditor = $(e.target).closest('.htSelect2Wrapper').length > 0 ||
                          $(e.target).closest('.select2-container').length > 0 ||
                          $(e.target).hasClass('select2-search__field');

        if (!isOurEditor) return;

        var isDropdownOpen = _this.$select && _this.$select.data('select2') && _this.$select.data('select2').isOpen();
        if ((e.keyCode === 38 || e.keyCode === 40) && isDropdownOpen) {
           return;
        }

        var row = _this.row;
        var col = _this.col;
        var instance = _this.hot;
        var shiftKey = e.shiftKey;

        e.preventDefault();
        e.stopImmediatePropagation();

        if (_this.closeTimeout) {
          clearTimeout(_this.closeTimeout);
          _this.closeTimeout = null;
        }

        if (_this.$select && _this.$select.data('select2')) _this.$select.select2('close');

        if (_this.closeTimeout) {
          clearTimeout(_this.closeTimeout);
          _this.closeTimeout = null;
        }

        if (instance) instance.listen();

        _this.finishEditing();

        if (instance && (isTab || isArrow)) {
          var moveCol = 0;
          var moveRow = 0;

          if (isTab) {
            moveCol = shiftKey ? -1 : 1;
          } else {
            if (e.keyCode === 37) moveCol = -1;
            else if (e.keyCode === 39) moveCol = 1;
            else if (e.keyCode === 38) moveRow = -1;
            else if (e.keyCode === 40) moveRow = 1;
          }

          var newCol = col + moveCol;
          var newRow = row + moveRow;

          if (newCol >= instance.countCols()) { newCol = 0; newRow += 1; }
          else if (newCol < 0) { newCol = instance.countCols() - 1; newRow -= 1; }

          if (newRow >= 0 && newRow < instance.countRows()) {
            if (typeof window !== 'undefined') window.__piPendingNav = true;
            instance.selectCell(newRow, newCol, newRow, newCol, true, false);
            instance.listen();
          }
        }
      }
    };
    document.addEventListener('keydown', this._keydownCaptureHandler, true);


    // Deseleccionar un pill: forzar reapertura del dropdown
    this.$select.on('select2:unselect', function (e) {
      if (_this.closeTimeout) {
        clearTimeout(_this.closeTimeout);
      }
      setTimeout(function () {
        if (_this.$select && _this.$select.data('select2')) {
          _this.$select.select2('open');
        }
      }, 10);
    });

    // Opción especial de redirección (➕ Crear...)
    this.$select.on('select2:select', function (e) {
      if (e.params && e.params.data && e.params.data.id && e.params.data.id.indexOf('➕') > -1) {
        if (_this.closeTimeout) {
          clearTimeout(_this.closeTimeout);
        }
        _this.finishEditing();
      }
    });

    this.$select.select2('open');
  };

  Select2Editor.prototype.close = function () {
    if (this._keydownCaptureHandler) {
      document.removeEventListener('keydown', this._keydownCaptureHandler, true);
      this._keydownCaptureHandler = null;
    }

    if (this.closeTimeout) {
      clearTimeout(this.closeTimeout);
    }

    if (this.$select.data('select2')) {
      try { this.$select.select2('close'); } catch(e) {}
      try { this.$select.select2('destroy'); } catch(e) {}
    }
    
    // ── Limpieza masiva de namespaces nativos de Select2 y reset de flags de CSS ──
    $(window).off('.select2');
    $(document).off('.select2');
    $('html, body').off('.select2').css({ overflow: '', 'overflow-y': '' });

    this.$wrapper.hide();
    // Devolver wrapper a body para no contaminar el TD
    this.$wrapper.detach().appendTo('body');
    if (this.TD) {
      $(this.TD).css('overflow', '');
      $(this.TD).css('position', '');
    }
    
    // ── Resucitar Handsontable ──
    // Fuerza a Walkontable a reañadir los event listeners (`wheel`, `keydown`) de la tabla principal
    if (this.hot) {
      this.hot.listen();
    }
  };

  Select2Editor.prototype.getValue = function () {
    var val = this.$select.val() || [];
    return val.join(', ');
  };

  Select2Editor.prototype.setValue = function (value) {
    if (!value) {
      this.$select.val(null);
    } else {
      var selectedArr = String(value)
        .split(',')
        .map(function (item) {
          return item.trim();
        });
      this.$select.val(selectedArr);
    }
  };

  Select2Editor.prototype.focus = function () {
    this.$select.select2('focus');
  };

  Handsontable.editors.registerEditor('select2Multiple', Select2Editor);

  // ──────────────────────────────────────────────
  // Select2 SINGLE Editor (para Responsable AIA)
  // ──────────────────────────────────────────────
  var Select2Single = Handsontable.editors.BaseEditor.prototype.extend();

  Select2Single.prototype.init = function () {
    this.$wrapper = $('<div class="htSelect2Wrapper ht-select2-modern"></div>');
    this.$select = $('<select class="ht-select2-field"></select>');
    this.$wrapper.append(this.$select);
    $('body').append(this.$wrapper);
  };

  Select2Single.prototype.prepare = function (row, col, prop, td, originalValue, cellProperties) {
    Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
    this.options = cellProperties.select2Options || [];
    this.$select.empty();
    this.$select.append('<option></option>');
    for (var i = 0; i < this.options.length; i++) {
      this.$select.append(
        '<option value="' + this.options[i] + '">' + this.options[i] + '</option>'
      );
    }
  };

  Select2Single.prototype.open = function () {
    var _this = this;

    // Montar dentro del TD activo para que HOT no detecte "outside click"
    $(this.TD).css('position', 'relative').css('overflow', 'visible');
    this.$wrapper.detach().appendTo(this.TD);

    var tdWidth = $(this.TD).outerWidth();
    var wrapperWidth = Math.max(260, tdWidth);

    this.$wrapper.css({
      position: 'absolute',
      top: $(this.TD).outerHeight() + 'px',
      left: '0',
      width: wrapperWidth + 'px',
      display: 'block',
      zIndex: 10050,
    });

    this.$select
      .select2({
        dropdownParent: this.$wrapper,
        allowClear: true,
        placeholder: 'Seleccione...',
        width: '100%',
      })
      .on('select2:select', function (e) {
        if (e.params && e.params.data && e.params.data.id && e.params.data.id.indexOf('➕') > -1) {
          _this.finishEditing();
          return;
        }
        _this.finishEditing();
      })
      .on('select2:open', function () {
        setTimeout(function() {
            $(document).off('mousewheel.select2 DOMMouseScroll.select2 wheel.select2 scroll.select2');
            $('html, body').add(window).off('mousewheel.select2 DOMMouseScroll.select2 wheel.select2 scroll.select2');
        }, 0);
      })
      .on('select2:close', function () {
        setTimeout(function () {
          if (!_this.$select.data('select2') || !_this.$select.data('select2').isOpen()) {
            _this.finishEditing();
          }
        }, 5);
      });
    // Intercepción NATIVA en fase de captura
    this._keydownCaptureHandlerSingle = function (e) {
      var isTab = e.keyCode === 9;
      var isEsc = e.keyCode === 27;
      var isArrow = e.keyCode >= 37 && e.keyCode <= 40;

      if (isTab || isEsc || isArrow) {
        var isOurEditor = $(e.target).closest('.htSelect2Wrapper').length > 0 ||
                          $(e.target).closest('.select2-container').length > 0 ||
                          $(e.target).hasClass('select2-search__field');

        if (!isOurEditor) return;

        var isDropdownOpen = _this.$select && _this.$select.data('select2') && _this.$select.data('select2').isOpen();
        if ((e.keyCode === 38 || e.keyCode === 40) && isDropdownOpen) {
          return;
        }

        var row = _this.row;
        var col = _this.col;
        var instance = _this.hot;
        var shiftKey = e.shiftKey;

        e.preventDefault();
        e.stopImmediatePropagation();

        if (_this.$select && _this.$select.data('select2')) _this.$select.select2('close');

        if (instance) instance.listen();

        _this.finishEditing();

        if (instance && (isTab || isArrow)) {
          var moveCol = 0;
          var moveRow = 0;

          if (isTab) {
            moveCol = shiftKey ? -1 : 1;
          } else {
            if (e.keyCode === 37) moveCol = -1;
            else if (e.keyCode === 39) moveCol = 1;
            else if (e.keyCode === 38) moveRow = -1;
            else if (e.keyCode === 40) moveRow = 1;
          }

          var newCol = col + moveCol;
          var newRow = row + moveRow;

          if (newCol >= instance.countCols()) { newCol = 0; newRow += 1; }
          else if (newCol < 0) { newCol = instance.countCols() - 1; newRow -= 1; }

          if (newRow >= 0 && newRow < instance.countRows()) {
            if (typeof window !== 'undefined') window.__piPendingNav = true;
            instance.selectCell(newRow, newCol, newRow, newCol, true, false);
            instance.listen();
          }
        }
      }
    };
    document.addEventListener('keydown', this._keydownCaptureHandlerSingle, true);

    this.$select.select2('open');
  };

  Select2Single.prototype.close = function () {
    if (this._keydownCaptureHandlerSingle) {
      document.removeEventListener('keydown', this._keydownCaptureHandlerSingle, true);
      this._keydownCaptureHandlerSingle = null;
    }

    if (this.$select.data('select2')) {
      try { this.$select.select2('close'); } catch(e) {}
      try { this.$select.select2('destroy'); } catch(e) {}
    }
    
    // ── Limpieza masiva de namespaces nativos de Select2 y reset de flags de CSS ──
    $(window).off('.select2');
    $(document).off('.select2');
    $('html, body').off('.select2').css({ overflow: '', 'overflow-y': '' });

    this.$wrapper.hide();
    this.$wrapper.detach().appendTo('body');
    if (this.TD) {
      $(this.TD).css('overflow', '');
      $(this.TD).css('position', '');
    }
    
    // ── Resucitar Handsontable ──
    if (this.hot) {
      this.hot.listen();
    }
  };

  Select2Single.prototype.getValue = function () {
    return this.$select.val() || '';
  };

  Select2Single.prototype.setValue = function (value) {
    this.$select.val(value ? String(value).trim() : null);
  };

  Select2Single.prototype.focus = function () {
    this.$select.select2('focus');
  };

  Handsontable.editors.registerEditor('select2Single', Select2Single);
})(Handsontable, jQuery);
