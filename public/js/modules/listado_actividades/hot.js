(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var masterData = [];
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var _canEditGlobal = false;
  var _rowClassCache = [];
  var activeMobileEditRowIndex = null;
  var activeLoadRequest = null;
  var COLUMN_WIDTHS = [72, 90, 220, 300, 340, 140, 240];
  var MIN_COLUMN_WIDTHS = [46, 58, 118, 138, 148, 86, 118];
  var TABLET_COLUMN_WIDTHS = [60, 64, 175, 150, 300, 105, 100];
  var TABLET_MIN_COLUMN_WIDTHS = [44, 52, 118, 92, 190, 74, 78];
  var MOBILE_COLUMN_WIDTHS = [54, 48, 210, 210, 82, 66];
  var MOBILE_MIN_COLUMN_WIDTHS = [42, 42, 120, 138, 70, 54];
  var TABLET_LAYOUT_MAX_WIDTH = 1100;
  var MOBILE_LAYOUT_MAX_WIDTH = 700;

  // ── Editable properties (must match updateCell whitelist) ──
  var EDITABLE_PROPS = {
    codigo: true,
    descripcionActividad: true,
    nombreActividadInicio: true,
    tipoContrato: true,
  };

  // ── TipoContrato mapping ──
  var TIPO_CONTRATO_OPTIONS = ['SI', 'MO', 'S', 'OC'];
  var TIPO_CONTRATO_MOBILE_ORDER = ['MO', 'S', 'SI', 'OC'];
  var TIPO_CONTRATO_LABELS = {
    SI: 'Suministro e Instalación',
    MO: 'Mano de Obra',
    S: 'Suministro',
    OC: 'Orden de servicio/compra',
  };
  var TIPO_CONTRATO_SHORT_LABELS = {
    SI: 'S+I',
    MO: 'MO',
    S: 'S',
    OC: 'OC',
  };
  var TIPO_CONTRATO_BADGES = {
    SI: 'badge-primary',
    MO: 'badge-info',
    S: 'badge-secondary',
    OC: 'badge-dark',
  };
  var actividadInicioOptionsCache = null;

  // ── Helpers ──

  function getDb() {
    var fallback = window.__LISTADO_ACTIVIDADES_CONTEXT__ || {};
    return $('#baseDatos').val() || fallback.db || '';
  }

  function getSemana() {
    var fallback = window.__LISTADO_ACTIVIDADES_CONTEXT__ || {};
    return $('#Max_Semana').val() || fallback.semana || '';
  }

  function getPermiso() {
    var fallback = window.__LISTADO_ACTIVIDADES_CONTEXT__ || {};
    var permiso = String($('#permiso_canonico').val() || fallback.permiso || '').trim().toUpperCase();
    return { P: 'D', U: 'V' }[permiso] || permiso;
  }

  function puedeEditarListadoActividades() {
    try {
      if (typeof window.puedeEditarListadoActividades === 'function') {
        return window.puedeEditarListadoActividades();
      }
      return ['A', 'D', 'OT', 'R'].indexOf(getPermiso()) !== -1;
    } catch (e) {
      return false;
    }
  }

  function isUserAllowedToEdit() {
    return puedeEditarListadoActividades();
  }

  function normalizeSpace(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = String(value || '');
    return div.innerHTML;
  }

  function getPlainText(value) {
    var template = document.createElement('template');
    template.innerHTML = String(value || '').replace(/<br\s*\/?>/gi, ' ');
    return normalizeSpace(template.content.textContent || '');
  }

  function isMobileTableLayout(container) {
    var width = container && container.clientWidth ? container.clientWidth : (window.innerWidth || document.documentElement.clientWidth || 0);
    return width > 0 && width <= MOBILE_LAYOUT_MAX_WIDTH;
  }

  function isTabletTableLayout(container) {
    var width = container && container.clientWidth ? container.clientWidth : (window.innerWidth || document.documentElement.clientWidth || 0);
    return width > MOBILE_LAYOUT_MAX_WIDTH && width <= TABLET_LAYOUT_MAX_WIDTH;
  }

  function isCompactTableLayout(container) {
    var width = container && container.clientWidth ? container.clientWidth : (window.innerWidth || document.documentElement.clientWidth || 0);
    return width > 0 && width <= TABLET_LAYOUT_MAX_WIDTH;
  }

  function isMobileCardsLayout() {
    return window.matchMedia('(max-width: 43.75rem)').matches;
  }

  function getActividadInicioOptions() {
    if (actividadInicioOptionsCache) return actividadInicioOptionsCache;

    actividadInicioOptionsCache = Array.prototype.slice.call(document.querySelectorAll('#actividadInicio option'))
      .map(function (option) {
        var label = getPlainActividadInicioLabel(option.textContent || '');
        var dateMatch = label.match(/Inicia(?:\s+el|\s+en):\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i);
        return {
          value: String(option.value || '').trim(),
          label: label,
          fechaInicio: dateMatch ? dateMatch[1] : '',
        };
      })
      .filter(function (option) {
        return option.value !== '' && option.label !== '';
      });

    return actividadInicioOptionsCache;
  }

  function getActividadInicioLabels() {
    return getActividadInicioOptions().map(function (option) { return option.label; });
  }

  function normalizeActividadInicioRows(rows) {
    return (Array.isArray(rows) ? rows : []).map(function (row) {
      if (!row) return row;
      row.actividad = getPlainText(row.actividad);
      row.descripcionActividad = getPlainText(row.descripcionActividad);
      row.fechaInicio = getPlainText(row.fechaInicio);
      var option = findActividadInicioOptionByValue(row.actividadInicio);
      row.nombreActividadInicio = option ? option.label : getPlainActividadInicioLabel(row.nombreActividadInicio);
      return row;
    });
  }

  function findActividadInicioOptionByLabel(label) {
    var normalized = normalizeSpace(label);
    return getActividadInicioOptions().find(function (option) {
      return normalizeSpace(option.label) === normalized;
    }) || null;
  }

  function findActividadInicioOptionByValue(value) {
    var normalized = String(value || '').trim();
    return getActividadInicioOptions().find(function (option) {
      return String(option.value) === normalized;
    }) || null;
  }

  function sanitizeActividadInicioHtml(value) {
    var template = document.createElement('template');
    template.innerHTML = String(value || '');

    Array.prototype.slice.call(template.content.querySelectorAll('*')).forEach(function (node) {
      var tag = node.tagName.toLowerCase();
      if (tag === 'br') {
        node.replaceWith(document.createTextNode(' '));
        return;
      }
      if (['b', 'small', 'br'].indexOf(tag) < 0) {
        node.replaceWith(document.createTextNode(node.textContent || ''));
        return;
      }
      Array.prototype.slice.call(node.attributes).forEach(function (attr) {
        node.removeAttribute(attr.name);
      });
    });

    return template.innerHTML;
  }

  function getPlainActividadInicioLabel(value) {
    var template = document.createElement('template');
    template.innerHTML = sanitizeActividadInicioHtml(value);
    return normalizeSpace(template.content.textContent || value);
  }

  function compactActividadInicioHtml(value) {
    var template = document.createElement('template');
    template.innerHTML = sanitizeActividadInicioHtml(value);
    var text = normalizeSpace(template.content.textContent || '');
    text = text
      .replace(/\s*\[Capítulo:[^\]]*\]\s*/i, ' ')
      .replace(/\s*\(Inicia\s+(?:el|en):\s*[0-9]{4}-[0-9]{2}-[0-9]{2}\)\s*/i, ' ');
    text = normalizeSpace(text);
    return escapeHtml(text);
  }

  function compactActividadInicioText(value) {
    var text = getPlainActividadInicioLabel(value);
    text = text
      .replace(/\s*\[Capítulo:[^\]]*\]\s*/i, ' ')
      .replace(/\s*\(Inicia\s+(?:el|en):\s*[0-9]{4}-[0-9]{2}-[0-9]{2}\)\s*/i, ' ');
    return normalizeSpace(text);
  }

  function ensureMobileCardsContainer() {
    var container = document.getElementById('hot-container');
    if (!container || !container.parentNode) return null;
    var cards = document.getElementById('la-mobile-card-list');
    if (!cards) {
      cards = document.createElement('div');
      cards.id = 'la-mobile-card-list';
      cards.className = 'la-mobile-card-list';
      cards.setAttribute('aria-label', 'Familias de obra');
      container.parentNode.insertBefore(cards, container.nextSibling);
    }
    return cards;
  }

  function setTableState(state, message) {
    var container = document.getElementById('hot-container');
    if (!container || !container.parentNode) return;
    var status = document.getElementById('la-table-state');
    if (!status) {
      status = document.createElement('p');
      status.id = 'la-table-state';
      status.className = 'la-table-state';
      container.parentNode.insertBefore(status, container);
    }
    status.setAttribute('role', state === 'error' ? 'alert' : 'status');
    status.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
    status.dataset.state = state;
    status.textContent = message || '';
    status.hidden = state === 'ready';
    container.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
    if (state !== 'ready') {
      setImportantStyle(container, 'display', 'none');
      var cards = document.getElementById('la-mobile-card-list');
      if (cards) cards.hidden = true;
      return;
    }
    renderMobileCards();
  }

  function getTipoContratoCodes(value) {
    return String(value || '')
      .split(',')
      .map(function (code) { return code.trim(); })
      .filter(Boolean);
  }

  function normalizeTipoContratoCodes(value) {
    var selected = {};
    getTipoContratoCodes(value).forEach(function (code) {
      if (TIPO_CONTRATO_OPTIONS.indexOf(code) !== -1) selected[code] = true;
    });
    if (selected.SI) return ['SI'];
    return TIPO_CONTRATO_OPTIONS.filter(function (code) {
      return code !== 'SI' && selected[code];
    });
  }

  function normalizeTipoContratoValue(value) {
    return normalizeTipoContratoCodes(value).join(',');
  }

  function createIcon(className) {
    var icon = document.createElement('i');
    icon.className = className;
    icon.setAttribute('aria-hidden', 'true');
    return icon;
  }

  function createTipoContratoBadgesNode(value, compact) {
    var wrapper = document.createElement('span');
    wrapper.className = 'la-contract-badges';
    getTipoContratoCodes(value).forEach(function (code) {
      var badge = document.createElement('span');
      badge.className = 'badge ' + (TIPO_CONTRATO_BADGES[code] || 'badge-secondary');
      badge.textContent = compact ? (TIPO_CONTRATO_SHORT_LABELS[code] || code) : (TIPO_CONTRATO_LABELS[code] || code);
      badge.title = TIPO_CONTRATO_LABELS[code] || code;
      wrapper.appendChild(badge);
    });
    return wrapper;
  }

  function getContainerAvailableWidth() {
    var container = document.getElementById('hot-container');
    if (!container) return 0;
    return container.clientWidth;
  }

  function getContainerAvailableHeight() {
    var el = document.querySelector('.tabla');
    if (!el) return 400;
    var rect = el.getBoundingClientRect();
    var windowHeight = window.innerHeight || document.documentElement.clientHeight;
    var available = windowHeight - rect.top - 60;
    return Math.max(available, 200);
  }

  function syncContainerHeight() {
    var container = document.getElementById('hot-container');
    if (!container) return;
    var h = getContainerAvailableHeight();
    container.style.height = h + 'px';
  }

  function getFittedColumnWidths(container) {
    var mobile = isMobileTableLayout(container);
    var tablet = isTabletTableLayout(container);
    var baseColumns = mobile ? MOBILE_COLUMN_WIDTHS : (tablet ? TABLET_COLUMN_WIDTHS : COLUMN_WIDTHS);
    var minColumns = mobile ? MOBILE_MIN_COLUMN_WIDTHS : (tablet ? TABLET_MIN_COLUMN_WIDTHS : MIN_COLUMN_WIDTHS);
    if (!container) return baseColumns.slice();
    var targetWidth = Math.max(Math.floor(container.clientWidth || 0) - 2, 320);
    var baseWidth = baseColumns.reduce(function (sum, width) {
      return sum + width;
    }, 0);
    var widths = baseColumns.map(function (width, index) {
      return Math.max(minColumns[index] || 24, Math.floor(width * targetWidth / baseWidth));
    });
    var total = widths.reduce(function (sum, width) { return sum + width; }, 0);
    if (total > targetWidth) {
      widths = baseColumns.map(function (width) {
        return Math.max(24, Math.floor(width * targetWidth / baseWidth));
      });
      total = widths.reduce(function (sum, width) { return sum + width; }, 0);
    }
    widths[widths.length - 1] += targetWidth - total;
    return widths;
  }

  function syncTableWidth(container) {
    if (!container) return COLUMN_WIDTHS.slice();
    currentColumnWidths = getFittedColumnWidths(container);
    var targetWidth = currentColumnWidths.reduce(function (sum, width) {
      return sum + width;
    }, 0);
    container.classList.add('hot-fixed-columns');
    container.classList.toggle('la-hot-mobile-compact', isMobileTableLayout(container));
    container.classList.toggle('la-hot-tablet-compact', isTabletTableLayout(container));
    container.style.setProperty('--hot-table-width', targetWidth + 'px');
    return currentColumnWidths;
  }

  function setImportantStyle(element, prop, value) {
    if (element && element.style && typeof element.style.setProperty === 'function') {
      element.style.setProperty(prop, value, 'important');
    }
  }

  function syncToolbarSwitcherWidth() {
    var switcher = document.querySelector('.filaBotones .toolbarFilaBotones > .la-toolbar-switcher.ps-toolbar-nav-wrap');
    if (!switcher) return;
    var unitCount = window.matchMedia('(max-width: 36rem)').matches ? 4 : 5;
    var tokenWidth = Array(unitCount).fill('var(--ds-target-min)').join(' + ');
    var value = 'calc(' + tokenWidth + ')';
    ['flex-basis', 'inline-size', 'width', 'max-width'].forEach(function (prop) {
      setImportantStyle(switcher, prop, value);
    });
    setImportantStyle(switcher, 'flex-grow', '0');
    setImportantStyle(switcher, 'flex-shrink', '1');
    switcher.style.setProperty('--la-switcher-width-balanced', value);
  }

  function applyRenderedTableLayout(container, widths) {
    if (!container || !Array.isArray(widths) || !widths.length) return;
    var targetWidth = widths.reduce(function (sum, width) {
      return sum + width;
    }, 0);
    Array.prototype.slice.call(container.querySelectorAll('.handsontable table.htCore')).forEach(function (table) {
      setImportantStyle(table, 'display', 'table');
      setImportantStyle(table, 'table-layout', 'fixed');
      setImportantStyle(table, 'width', targetWidth + 'px');
      setImportantStyle(table, 'min-width', targetWidth + 'px');
    });
    Array.prototype.slice.call(container.querySelectorAll('.handsontable tr')).forEach(function (row) {
      ['margin', 'padding', 'border-radius', 'box-shadow'].forEach(function (prop) {
        setImportantStyle(row, prop, prop === 'box-shadow' ? 'none' : '0');
      });
    });
  }

  // ── Load data from API ──

  function loadData() {
    var db = getDb();
    var semana = getSemana();
    if (!db || !semana) {
      masterData = [];
      updateOrInitHot(masterData);
      setTableState('error', 'No se pudo determinar el proyecto o la semana.');
      return;
    }

    if (activeLoadRequest && activeLoadRequest.readyState !== 4) {
      activeLoadRequest.abort();
    }
    setTableState('loading', 'Cargando familias de obra…');
    var request = $.ajax({
      method: 'POST',
      url: '/api/listado-actividades/list?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      dataType: 'json',
    }).done(function (response) {
      if (!response || !Array.isArray(response.data)) {
        masterData = [];
        updateOrInitHot(masterData);
        setTableState('error', 'La respuesta del listado no es válida. Intenta nuevamente.');
        return;
      }
      masterData = normalizeActividadInicioRows(response.data);
      updateOrInitHot(masterData);
      setTableState(masterData.length ? 'ready' : 'empty', masterData.length ? '' : 'No hay familias de obra para mostrar.');
    }).fail(function (_xhr, status) {
      if (status === 'abort') return;
      masterData = [];
      updateOrInitHot(masterData);
      setTableState('error', 'No fue posible cargar las familias de obra. Intenta nuevamente.');
    }).always(function () {
      if (activeLoadRequest === request) activeLoadRequest = null;
    });
    activeLoadRequest = request;
  }

  // ── Normalize cell value before save ──

  function normalizeCellValue(prop, value) {
    if (prop === 'fechaInicio') {
      if (!value || String(value).trim() === '') {
        return { valid: true, value: null };
      }
      var d = new Date(value);
      if (isNaN(d.getTime())) {
        return { valid: false, error: 'Fecha inválida' };
      }
      var yyyy = d.getFullYear();
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var dd = String(d.getDate()).padStart(2, '0');
      return { valid: true, value: yyyy + '-' + mm + '-' + dd };
    }
    if (prop === 'codigo') {
      return { valid: true, value: String(value).trim() };
    }
    if (prop === 'descripcionActividad') {
      return { valid: true, value: String(value).trim() };
    }
    if (prop === 'tipoContrato') {
      var codes = getTipoContratoCodes(value);
      var invalid = codes.some(function (code) { return TIPO_CONTRATO_OPTIONS.indexOf(code) === -1; });
      if (!codes.length || invalid) {
        return { valid: false, error: 'Usa únicamente MO, S, SI u OC.' };
      }
      return { valid: true, value: normalizeTipoContratoValue(value) };
    }
    return { valid: true, value: value };
  }

  // ── Save single cell via updateCell API ──

  function saveCell(visualRow, prop, oldValue, newValue) {
    var db = getDb();
    var rowData = hot.getSourceDataAtRow(hot.toPhysicalRow(visualRow));
    if (!rowData || !rowData.Id) return;

    var id = rowData.Id;

    $.ajax({
      method: 'POST',
      url: '/api/listado-actividades/update-cell?db=' + encodeURIComponent(db),
      data: { id: id, prop: prop, value: newValue },
      dataType: 'json',
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        // Update local data to reflect saved value
        rowData[prop] = response.valor || newValue;
        showFeedback('success', 'Celda actualizada');
      } else {
        revertCell(visualRow, prop, oldValue);
        showFeedback('error', 'Error al guardar: ' + (response && response.mensaje ? response.mensaje : 'Error desconocido'));
      }
    }).fail(function (xhr) {
      revertCell(visualRow, prop, oldValue);
      var message = xhr && xhr.responseJSON && xhr.responseJSON.mensaje;
      showFeedback('error', message || 'Error de red al guardar');
    });
  }

  function saveActividadInicio(visualRow, oldValue, option) {
    var db = getDb();
    var rowData = hot.getSourceDataAtRow(hot.toPhysicalRow(visualRow));
    if (!rowData || !rowData.Id || !option) return;

    $.ajax({
      method: 'POST',
      url: '/api/listado-actividades/update-cell?db=' + encodeURIComponent(db),
      data: { id: rowData.Id, prop: 'actividadInicio', value: option.value },
      dataType: 'json',
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        var nextActividadInicio = String(response.valor || option.value);
        var nextNombreActividadInicio = response.nombreActividadInicio || option.label;
        var nextFechaInicio = response.fechaInicio || option.fechaInicio || '';
        setSourceRowProp(visualRow, 'actividadInicio', nextActividadInicio);
        setSourceRowProp(visualRow, 'nombreActividadInicio', nextNombreActividadInicio);
        setSourceRowProp(visualRow, 'fechaInicio', nextFechaInicio);
        hot.render();
        showFeedback('success', 'Inicio en obra actualizado');
      } else {
        revertCell(visualRow, 'nombreActividadInicio', oldValue);
        showFeedback('error', 'Error al guardar: ' + (response && response.mensaje ? response.mensaje : 'Error desconocido'));
      }
    }).fail(function () {
      revertCell(visualRow, 'nombreActividadInicio', oldValue);
      showFeedback('error', 'Error de red al guardar');
    });
  }

  function revertCell(visualRow, prop, oldValue) {
    if (!hot) return;
    hot.setDataAtRowProp(visualRow, prop, oldValue, 'revert');
  }

  function setSourceRowProp(visualRow, prop, value) {
    if (!hot) return;
    var physicalRow = hot.toPhysicalRow(visualRow);
    if (typeof hot.setSourceDataAtCell === 'function') {
      hot.setSourceDataAtCell(physicalRow, prop, value, 'internal-update');
      return;
    }
    var rowData = hot.getSourceDataAtRow(physicalRow);
    if (rowData) rowData[prop] = value;
  }

  function setSourceRowByIndex(rowIndex, prop, value) {
    var rowData = masterData[rowIndex];
    if (rowData) rowData[prop] = value;
    if (hot && typeof hot.setSourceDataAtCell === 'function') {
      hot.setSourceDataAtCell(rowIndex, prop, value, 'internal-update');
    }
  }

  function applyMobileCardResponse(rowIndex, response, option) {
    var nextActividadInicio = String(response.actividadInicio || option.value);
    var nextNombre = response.nombreActividadInicio || option.label;
    var nextFecha = response.fechaInicio || option.fechaInicio || '';
    setSourceRowByIndex(rowIndex, 'actividadInicio', nextActividadInicio);
    setSourceRowByIndex(rowIndex, 'nombreActividadInicio', nextNombre);
    setSourceRowByIndex(rowIndex, 'fechaInicio', nextFecha);
    setSourceRowByIndex(rowIndex, 'tipoContrato', normalizeTipoContratoValue(response.tipoContrato));
  }

  function saveMobileCard(rowIndex, select, toggle, saveButton) {
    var rowData = masterData[rowIndex];
    var option = findActividadInicioOptionByValue(select ? select.value : '');
    var nextValue = toggle ? getTipoContratoToggleValue(toggle) : '';
    if (!rowData || !rowData.Id || !option || !nextValue) {
      showFeedback('error', 'Seleccione una actividad y al menos una modalidad.');
      return;
    }

    saveButton.disabled = true;
    if (select) select.disabled = true;
    $.ajax({
      method: 'POST',
      url: '/api/listado-actividades/update-card?db=' + encodeURIComponent(getDb()),
      data: {
        id: rowData.Id,
        actividadInicio: option.value,
        tipoContrato: nextValue,
      },
      dataType: 'json',
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        applyMobileCardResponse(rowIndex, response, option);
        activeMobileEditRowIndex = null;
        if (hot) hot.render();
        renderMobileCards();
        showFeedback('success', 'Cambios guardados');
        return;
      }
      showFeedback('error', 'Error al guardar: ' + (response && response.mensaje ? response.mensaje : 'Error desconocido'));
    }).fail(function () {
      showFeedback('error', 'Error de red al guardar');
    }).always(function () {
      if (activeMobileEditRowIndex === rowIndex) {
        saveButton.disabled = false;
        if (select) select.disabled = false;
      }
    });
  }

  // ── Feedback message ──

  function showFeedback(type, message) {
    var $el = $('#mensajeActualizacion');
    if (!$el.length) return;

    var messageClass = type === 'success' ? 'la-message-success' : 'la-message-error';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    var iconNode = createIcon('fas ' + icon + ' mr-1');
    var messageNode = document.createElement('span');
    messageNode.textContent = String(message || '');
    $el.removeClass('la-message-success la-message-error').addClass(messageClass);
    $el[0].replaceChildren(iconNode, messageNode);

    clearTimeout(saveBadgeTimer);
    saveBadgeTimer = setTimeout(function () {
      $el.fadeOut(3000, function () {
        this.replaceChildren();
        $(this).removeClass('la-message-success la-message-error').fadeIn(100);
      });
    }, 3000);
  }

  function createMobileCardField(label, content) {
    var field = document.createElement('div');
    field.className = 'la-mobile-card__field';
    var title = document.createElement('span');
    title.className = 'la-mobile-card__label';
    title.textContent = label;
    field.appendChild(title);
    var value = document.createElement('div');
    value.className = 'la-mobile-card__value';
    if (content instanceof Node) {
      value.appendChild(content);
    } else {
      value.textContent = String(content || '—');
    }
    field.appendChild(value);
    return field;
  }

  function getTipoContratoToggleValue(toggle) {
    var selected = Array.prototype.slice.call(toggle.querySelectorAll('input[type="checkbox"]:checked'))
      .map(function (input) { return input.value; });
    return normalizeTipoContratoValue(selected.join(','));
  }

  function updateTipoContratoToggleState(toggle, value, saving) {
    var codes = normalizeTipoContratoCodes(value);
    var selected = {};
    codes.forEach(function (code) { selected[code] = true; });
    var normalizedValue = codes.join(',');
    toggle.dataset.currentValue = normalizedValue;
    if (!saving) toggle.dataset.previousValue = normalizedValue;
    toggle.classList.toggle('is-saving', !!saving);
    Array.prototype.slice.call(toggle.querySelectorAll('input[type="checkbox"]')).forEach(function (input) {
      var code = input.value;
      input.checked = !!selected[code];
      input.disabled = !!saving || !isUserAllowedToEdit() || (!!selected.SI && code !== 'SI');
      var pill = input.closest('.la-mobile-tipo-pill');
      if (pill) {
        pill.classList.toggle('is-checked', !!selected[code]);
        pill.classList.toggle('is-disabled', input.disabled);
      }
    });
  }

  function createTipoContratoPill(code) {
    var pill = document.createElement('label');
    pill.className = 'la-mobile-tipo-pill';
    pill.dataset.tipoCode = code;
    var input = document.createElement('input');
    input.type = 'checkbox';
    input.value = code;
    input.name = 'tipoContratoMobile';
    input.setAttribute('aria-label', TIPO_CONTRATO_LABELS[code] || code);
    pill.appendChild(input);
    var codeNode = document.createElement('span');
    codeNode.className = 'la-mobile-tipo-pill__code';
    codeNode.textContent = TIPO_CONTRATO_SHORT_LABELS[code] || code;
    pill.appendChild(codeNode);
    var labelNode = document.createElement('span');
    labelNode.className = 'la-mobile-tipo-pill__label';
    labelNode.textContent = TIPO_CONTRATO_LABELS[code] || code;
    pill.appendChild(labelNode);
    return pill;
  }

  function handleTipoContratoToggleChange(toggle, input) {
    if (input.value === 'SI' && input.checked) {
      Array.prototype.slice.call(toggle.querySelectorAll('input[type="checkbox"]')).forEach(function (item) {
        item.checked = item.value === 'SI';
      });
    } else if (input.value !== 'SI' && input.checked) {
      var si = toggle.querySelector('input[value="SI"]');
      if (si) si.checked = false;
    }
    var nextValue = getTipoContratoToggleValue(toggle);
    toggle.dataset.currentValue = nextValue;
    Array.prototype.slice.call(toggle.querySelectorAll('input[type="checkbox"]')).forEach(function (item) {
      var pill = item.closest('.la-mobile-tipo-pill');
      item.disabled = nextValue === 'SI' && item.value !== 'SI';
      if (pill) {
        pill.classList.toggle('is-checked', item.checked);
        pill.classList.toggle('is-disabled', item.disabled);
      }
    });
  }

  function createTipoContratoToggle(rowData) {
    var toggle = document.createElement('div');
    toggle.className = 'la-mobile-tipo-toggle';
    toggle.setAttribute('role', 'group');
    toggle.setAttribute('aria-label', 'Modalidad de contratación');
    TIPO_CONTRATO_MOBILE_ORDER.forEach(function (code) {
      var pill = createTipoContratoPill(code);
      var input = pill.querySelector('input');
      input.addEventListener('change', function () {
        handleTipoContratoToggleChange(toggle, input);
      });
      toggle.appendChild(pill);
    });
    updateTipoContratoToggleState(toggle, rowData.tipoContrato, false);
    return toggle;
  }

  function createActividadInicioSelect(rowData, rowIndex) {
    var select = document.createElement('select');
    select.className = 'la-mobile-card__select';
    select.dataset.rowIndex = String(rowIndex);
    select.dataset.previousValue = String(rowData.actividadInicio || '');
    select.disabled = !isUserAllowedToEdit();
    var currentValue = String(rowData.actividadInicio || '');
    var matched = false;
    getActividadInicioOptions().forEach(function (option) {
      var item = document.createElement('option');
      item.value = option.value;
      item.textContent = compactActividadInicioText(option.label);
      item.selected = String(option.value) === currentValue;
      matched = matched || item.selected;
      select.appendChild(item);
    });
    if (!matched) {
      var empty = document.createElement('option');
      empty.value = '';
      empty.textContent = 'Sin actividad seleccionada';
      empty.selected = true;
      select.insertBefore(empty, select.firstChild);
    }
    return select;
  }

  function getMobileActividadInicioDisplay(rowData) {
    var label = rowData.nombreActividadInicio;
    if (!label && rowData.actividadInicio) {
      var option = findActividadInicioOptionByValue(rowData.actividadInicio);
      label = option ? option.label : '';
    }
    return compactActividadInicioText(label) || 'Sin actividad seleccionada';
  }

  function openMobileDelete(rowData) {
    $('#Id').val(rowData.Id || '');
    $('#modal-body-texto-eliminar')
      .html('¿Desea eliminar la familia <b>' + escapeHtml(rowData.actividad || '') + '</b> definitivamente del proyecto?');
    $('#modalEliminar').modal('show');
  }

  function createMobileCardActions(rowData, rowIndex) {
    var actions = document.createElement('div');
    actions.className = 'la-mobile-card__actions';
    if (!isUserAllowedToEdit()) return actions;
    var isEditing = activeMobileEditRowIndex === rowIndex;

    if (isEditing) {
      var save = document.createElement('button');
      save.type = 'button';
      save.className = 'btn btn-success btn-sm la-mobile-card__action la-mobile-card__action--save';
      save.title = 'Guardar cambios';
      save.appendChild(createIcon('fa fa-check fa-xs'));
      var saveText = document.createElement('span');
      saveText.className = 'la-mobile-card__action-label';
      saveText.textContent = 'Guardar';
      save.appendChild(saveText);
      save.addEventListener('click', function () {
        var card = actions.closest('.la-mobile-card');
        var select = card ? card.querySelector('.la-mobile-card__select') : null;
        var toggle = card ? card.querySelector('.la-mobile-tipo-toggle') : null;
        var nextValue = toggle ? getTipoContratoToggleValue(toggle) : '';
        if (!nextValue) {
          showFeedback('error', 'Seleccione al menos una modalidad.');
          return;
        }
        saveMobileCard(rowIndex, select, toggle, save);
      });
      actions.appendChild(save);

      var cancel = document.createElement('button');
      cancel.type = 'button';
      cancel.className = 'btn btn-secondary btn-sm la-mobile-card__action la-mobile-card__action--cancel';
      cancel.title = 'Cancelar edición';
      cancel.appendChild(createIcon('fa fa-times fa-xs'));
      var cancelText = document.createElement('span');
      cancelText.className = 'la-mobile-card__action-label';
      cancelText.textContent = 'Cancelar';
      cancel.appendChild(cancelText);
      cancel.addEventListener('click', function () {
        activeMobileEditRowIndex = null;
        renderMobileCards();
      });
      actions.appendChild(cancel);
      return actions;
    }

    var edit = document.createElement('button');
    edit.type = 'button';
    edit.className = 'btn btn-primary btn-sm la-mobile-card__action';
    edit.title = 'Editar familia';
    edit.setAttribute('aria-expanded', 'false');
    edit.appendChild(createIcon('fa fa-edit fa-xs'));
    edit.addEventListener('click', function () {
      activeMobileEditRowIndex = rowIndex;
      renderMobileCards();
    });
    actions.appendChild(edit);
    var del = document.createElement('button');
    del.type = 'button';
    del.className = 'btn btn-danger btn-sm la-mobile-card__action';
    del.title = 'Eliminar familia';
    del.appendChild(createIcon('fa fa-trash-alt fa-xs'));
    del.addEventListener('click', function () { openMobileDelete(rowData); });
    actions.appendChild(del);
    return actions;
  }

  function createMobileCardHeader(rowData) {
    var header = document.createElement('header');
    header.className = 'la-mobile-card__header';
    var identity = document.createElement('div');
    identity.className = 'la-mobile-card__identity';
    var code = document.createElement('span');
    code.className = 'la-mobile-card__code';
    code.textContent = 'Cod. ' + (rowData.codigo || '—');
    var title = document.createElement('h3');
    title.className = 'la-mobile-card__title';
    title.textContent = rowData.actividad || 'Sin familia';
    identity.appendChild(code);
    identity.appendChild(title);
    header.appendChild(identity);
    header.appendChild(createTipoContratoBadgesNode(rowData.tipoContrato, true));
    return header;
  }

  function createMobileCard(rowData, rowIndex) {
    var card = document.createElement('article');
    card.className = 'la-mobile-card';
    card.dataset.rowIndex = String(rowIndex);
    card.dataset.rowId = String(rowData.Id || '');
    var isEditing = activeMobileEditRowIndex === rowIndex;
    card.classList.toggle('is-editing', isEditing);
    var body = document.createElement('div');
    body.className = 'la-mobile-card__body';
    if (rowData.descripcionActividad) {
      body.appendChild(createMobileCardField('Descripción', rowData.descripcionActividad));
    }
    if (isEditing) {
      var tipoField = createMobileCardField('Modalidad de contratación', createTipoContratoToggle(rowData));
      tipoField.classList.add('la-mobile-card__edit-field');
      body.appendChild(tipoField);
    }
    body.appendChild(createMobileCardField(
      'Inicio en obra',
      isEditing ? createActividadInicioSelect(rowData, rowIndex) : getMobileActividadInicioDisplay(rowData)
    ));
    body.appendChild(createMobileCardField('Fecha de inicio', rowData.fechaInicio));
    card.appendChild(createMobileCardHeader(rowData));
    card.appendChild(body);
    card.appendChild(createMobileCardActions(rowData, rowIndex));
    return card;
  }

  function renderMobileCards() {
    var cards = ensureMobileCardsContainer();
    if (!cards) return;
    var tableContainer = document.getElementById('hot-container');
    var useCards = isMobileCardsLayout();
    cards.hidden = !useCards;
    if (tableContainer) {
      setImportantStyle(tableContainer, 'display', useCards ? 'none' : 'block');
    }
    if (!useCards) {
      cards.replaceChildren();
      return;
    }
    var fragment = document.createDocumentFragment();
    masterData.forEach(function (rowData, rowIndex) {
      fragment.appendChild(createMobileCard(rowData, rowIndex));
    });
    cards.replaceChildren(fragment);
  }

  // ── Custom renderers ──

  function registerRenderers() {
    if (renderersRegistered) return;

    Handsontable.renderers.registerRenderer('laTipoContratoRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var compact = isCompactTableLayout(document.getElementById('hot-container'));

      var codes = [];
      if (value && typeof value === 'string') {
        codes = value.split(',').map(function (v) { return v.trim(); }).filter(Boolean);
      }

      td.innerHTML = '';
      td.className = td.className.replace(/htRight|htLeft|htCenter/g, '') + ' htMiddle';
      td.classList.remove('la-empty-contract-type');

      if (codes.length === 0) {
        td.textContent = '—';
        td.classList.add('la-empty-contract-type');
        return;
      }

      var wrapper = document.createElement('span');
      wrapper.className = 'la-contract-badges';

      for (var i = 0; i < codes.length; i++) {
        var badge = document.createElement('span');
        badge.className = 'badge ' + (TIPO_CONTRATO_BADGES[codes[i]] || 'badge-secondary');
        badge.textContent = compact ? (TIPO_CONTRATO_SHORT_LABELS[codes[i]] || codes[i]) : (TIPO_CONTRATO_LABELS[codes[i]] || codes[i]);
        badge.title = TIPO_CONTRATO_LABELS[codes[i]] || codes[i];
        wrapper.appendChild(badge);
      }

      td.appendChild(wrapper);
    });

    Handsontable.renderers.registerRenderer('laActividadInicioRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(instance.toPhysicalRow(row)) || {};
      var option = findActividadInicioOptionByValue(rowData.actividadInicio);
      var displayValue = value || (option ? option.label : '');
      var compact = isCompactTableLayout(document.getElementById('hot-container'));
      td.className = td.className.replace(/htRight|htCenter/g, '') + ' htLeft htMiddle force-wrap';
      td.innerHTML = compact ? compactActividadInicioHtml(displayValue) : sanitizeActividadInicioHtml(displayValue);
      td.title = normalizeSpace(td.textContent || '');
    });

    Handsontable.renderers.registerRenderer('laActionRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.innerHTML = '';
      td.className = td.className.replace(/htRight|htLeft|htCenter/g, '') + ' htCenter htMiddle';

      var rowData = instance.getSourceDataAtRow(instance.toPhysicalRow(row)) || {};
      var wrap = document.createElement('div');
      wrap.className = 'la-row-actions';

      if (!isUserAllowedToEdit()) {
        td.appendChild(wrap);
        return;
      }

      var del = document.createElement('button');
      del.type = 'button';
      del.className = 'eliminar btn btn-danger btn-sm btn-action-gap';
      del.title = 'Eliminar';
      del.innerHTML = '<i class="fa fa-trash-alt fa-xs"></i>';
      del.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('#Id').val(rowData.Id || '');
        $('#modal-body-texto-eliminar').html('¿Desea eliminar la familia <b>' + escapeHtml(rowData.actividad) + '</b> definitivamente del proyecto?');
        $('#modalEliminar').modal('show');
      });
      wrap.appendChild(del);

      td.appendChild(wrap);
    });

    renderersRegistered = true;
  }

  // ── Save cell on edit ──

  function handleAfterChange(changes, source) {
    if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') return;

    for (var i = 0; i < changes.length; i++) {
      var change = changes[i];
      if (!change) continue;
      var visualRow = change[0];
      var prop = change[1];
      var oldValue = change[2];
      var newValue = change[3];

      if (oldValue === newValue) continue;

      if (prop === 'nombreActividadInicio') {
        var option = findActividadInicioOptionByLabel(newValue);
        if (!option) {
          revertCell(visualRow, prop, oldValue);
          showFeedback('error', 'Seleccione una actividad válida del cronograma.');
          continue;
        }
        saveActividadInicio(visualRow, oldValue, option);
        continue;
      }

      if (!EDITABLE_PROPS[prop]) continue;

      var normalized = normalizeCellValue(prop, newValue);
      if (!normalized.valid) {
        revertCell(visualRow, prop, oldValue);
        showFeedback('error', normalized.error);
        continue;
      }

      if (normalized.value !== newValue) {
        hot.setDataAtRowProp(visualRow, prop, normalized.value, 'internal-update');
      }

      saveCell(visualRow, prop, oldValue, normalized.value !== undefined ? normalized.value : newValue);
    }
    if (hot && changes.length > 0) {
      hot.render();
    }
  }

  // ── cells() callback — dynamic readOnly ──

  function buildCellProperties(row, col, prop) {
    var cellMeta = {};
    var canEdit = isUserAllowedToEdit();

    if (prop === 'codigo' || prop === 'descripcionActividad' || prop === 'nombreActividadInicio' || prop === 'tipoContrato') {
      cellMeta.readOnly = !canEdit;
    } else {
      cellMeta.readOnly = true;
    }

    return cellMeta;
  }

  function getColumnHeaders(widths) {
    if (widths.length === MOBILE_COLUMN_WIDTHS.length) {
      return ['', 'Cod.', 'Familia', 'Inicio en obra', 'Fecha', 'Tipo'];
    }
    return [
      '',
      'Código',
      'Familia',
      'Descripción',
      'Inicio en obra (cronograma)',
      'Fecha de Inicio',
      'Modalidad de contratación',
    ];
  }

  function getColumns(widths) {
    var mobile = widths.length === MOBILE_COLUMN_WIDTHS.length;
    var columns = [
      {
        data: 'Id',
        renderer: 'laActionRenderer',
        readOnly: true,
        className: 'htCenter htMiddle',
        width: widths[0],
      },
      {
        data: 'codigo',
        type: 'text',
        className: 'htCenter htMiddle',
        width: widths[1],
      },
      {
        data: 'actividad',
        readOnly: true,
        className: 'htLeft htMiddle force-wrap',
        width: widths[2],
      },
    ];

    if (!mobile) {
      columns.push(
      {
        data: 'descripcionActividad',
        type: 'text',
        className: 'htLeft htMiddle force-wrap',
        width: widths[3],
      });
    }

    columns.push(
      {
        data: 'nombreActividadInicio',
        type: 'dropdown',
        source: function (_query, process) {
          process(getActividadInicioLabels());
        },
        strict: true,
        allowInvalid: false,
        renderer: 'laActividadInicioRenderer',
        className: 'htLeft htMiddle force-wrap',
        width: widths[mobile ? 3 : 4],
      },
      {
        data: 'fechaInicio',
        type: 'date',
        dateFormat: 'YYYY-MM-DD',
        correctFormat: true,
        className: 'htCenter htMiddle',
        width: widths[mobile ? 4 : 5],
      },
      {
        data: 'tipoContrato',
        type: 'text',
        renderer: 'laTipoContratoRenderer',
        className: 'htCenter htMiddle',
        width: widths[mobile ? 5 : 6],
      });

    return columns;
  }

  // ── Create or update HOT instance ──

  function updateOrInitHot(data) {
    registerRenderers();
    syncContainerHeight();
    _canEditGlobal = isUserAllowedToEdit();

    if (hot) {
      hot.loadData(data);
      hot.render();
      renderMobileCards();
      scheduleLayoutRefresh(0, true);
      return;
    }

    var container = document.getElementById('hot-container');
    if (!container) return;
    var fittedColumnWidths = syncTableWidth(container);

    // Guard container reference for internal use
    var $container = $(container);

    hot = new Handsontable(container, {
      data: data,
      rowHeaders: false,
      colHeaders: getColumnHeaders(fittedColumnWidths),
      columns: getColumns(fittedColumnWidths),
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'none',
      autoColumnSize: false,
      colWidths: fittedColumnWidths,
      manualColumnResize: false,
      manualRowResize: true,
      contextMenu: true,
      dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
      filters: true,
      search: false,
      columnSorting: false,
      wordWrap: true,
      autoRowSize: false,
      rowHeights: 45,
      colHeaderHeight: 48,
      width: '100%',
      height: getContainerAvailableHeight(),
      renderAllRows: false,
      viewportRowRenderingOffset: 15,
      viewportColumnRenderingOffset: 5,
      className: 'htMiddle',
      cells: function (row, col, prop) {
        return buildCellProperties(row, col, prop);
      },
      afterChange: handleAfterChange,
      afterFilter: function (conditionsStack) {
        $('#btn_limpiar_buscador').toggleClass('d-none', !conditionsStack || conditionsStack.length === 0);
      },
      afterRender: function () {
        var renderContainer = document.getElementById('hot-container');
        applyRenderedTableLayout(renderContainer, currentColumnWidths);
      },
    });

    hot.listen();

    scheduleLayoutRefresh(0, true);
    renderMobileCards();

    // Re-listen on container mousedown (Bootstrap steals focus)
    container.addEventListener('mousedown', function () {
      if (hot && !hot.isDestroyed) {
        hot.listen();
      }
    }, true);
  }

  // ── Layout refresh ──

  function scheduleLayoutRefresh(delay, force) {
    clearTimeout(layoutTimer);
    layoutTimer = setTimeout(function () {
      if (!hot) return;
      syncContainerHeight();
      var container = document.getElementById('hot-container');
      var containerWidth = container ? container.clientWidth : 0;
      var fittedColumnWidths = syncTableWidth(container);
      var containerHeight = getContainerAvailableHeight();
      var settings = {};
      if (containerHeight && (force || containerHeight !== lastAppliedContainerHeight)) {
        settings.height = containerHeight;
        lastAppliedContainerHeight = containerHeight;
      }
      if (fittedColumnWidths.length && (force || containerWidth !== lastAppliedContainerWidth)) {
        settings.colWidths = fittedColumnWidths;
        settings.columns = getColumns(fittedColumnWidths);
        settings.colHeaders = getColumnHeaders(fittedColumnWidths);
        lastAppliedContainerWidth = containerWidth;
      }
      if (Object.keys(settings).length) {
        hot.updateSettings(settings);
      }
      if (typeof hot.refreshDimensions === 'function') {
        hot.refreshDimensions();
      }
      hot.render();
      applyRenderedTableLayout(container, fittedColumnWidths);
      renderMobileCards();
    }, Number.isFinite(delay) ? delay : 100);
  }

  // ── Expose recargarTabla to match existing pattern ──

  function recargarTabla(opcion) {
    loadData();
  }

  function bindFilterReset() {
    $('#btn_limpiar_buscador').off('click.laHot').on('click.laHot', function () {
      if (!hot) return;
      var filters = hot.getPlugin('filters');
      filters.clearConditions();
      filters.filter();
      $(this).addClass('d-none');
      hot.render();
    });
  }

  // ── Init ──

  function init() {
    if (initialized) {
      syncToolbarSwitcherWidth();
      bindFilterReset();
      return;
    }

    // Bind resize
    $(window).off('resize.laHot orientationchange.laHot aia:viewport-scale-change.laHot')
      .on('resize.laHot orientationchange.laHot aia:viewport-scale-change.laHot', function () {
        syncToolbarSwitcherWidth();
        renderMobileCards();
        scheduleLayoutRefresh(200, true);
      });

    initialized = true;
    syncToolbarSwitcherWidth();
    bindFilterReset();
    loadData();
  }

  // ── Public API ──

  window.ListadoActividadesHotModule = {
    init: init,
    getHotInstance: function () {
      return hot;
    },
    loadData: loadData,
    recargarTabla: recargarTabla,
  };

})(window, jQuery);
