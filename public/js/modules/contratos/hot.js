(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var masterData = [];
  var layoutTimer = null;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var modalLoadSequence = 0;
  var COLUMN_WIDTHS = [56, 90, 220, 300, 140, 240, 340];
  var MIN_COLUMN_WIDTHS = [46, 58, 118, 138, 86, 118, 148];
  var MOBILE_CARDS_MAX_WIDTH = 700;

  // ── Helpers ──

  function getDb() {
    return $('#baseDatos').val() || '';
  }

  function getMaxSemana() {
    return $('#Max_Semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_canonico').val() || '').trim().toUpperCase();
    return { P: 'D', U: 'V' }[permiso] || permiso;
  }

  function canEditContracts() {
    return String($('#cap_contratos_editar').val() || '') === '1';
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
    if (!container) return COLUMN_WIDTHS.slice();
    var targetWidth = Math.max(Math.floor(container.clientWidth || 0) - 2, 320);
    var baseWidth = COLUMN_WIDTHS.reduce(function (sum, width) {
      return sum + width;
    }, 0);
    var widths = COLUMN_WIDTHS.map(function (width, index) {
      return Math.max(MIN_COLUMN_WIDTHS[index] || 24, Math.floor(width * targetWidth / baseWidth));
    });
    var total = widths.reduce(function (sum, width) { return sum + width; }, 0);
    if (total > targetWidth) {
      widths = COLUMN_WIDTHS.map(function (width) {
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
    container.style.setProperty('--hot-table-width', targetWidth + 'px');
    return currentColumnWidths;
  }

  // ── TIPO_CONTRATO badge mapping ──

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

  var CONTRACT_SUMMARY_CLASSES = {
    'ct-text-danger': true,
    'ct-text-info': true,
    'ct-text-success': true,
    'ct-text-dark': true,
  };

  function sanitizeContractSummaryHtml(value) {
    var template = document.createElement('template');
    template.innerHTML = String(value || '');

    Array.prototype.slice.call(template.content.querySelectorAll('*')).forEach(function (node) {
      var tag = node.tagName.toLowerCase();
      if (tag !== 'b' && tag !== 'br') {
        node.replaceWith(document.createTextNode(node.textContent || ''));
        return;
      }

      Array.prototype.slice.call(node.attributes).forEach(function (attr) {
        if (tag !== 'b' || attr.name !== 'class') {
          node.removeAttribute(attr.name);
        }
      });

      if (tag === 'b') {
        var allowedClasses = Array.prototype.slice.call(node.classList).filter(function (className) {
          return CONTRACT_SUMMARY_CLASSES[className];
        });
        node.className = allowedClasses.join(' ');
      }
    });

    return template.innerHTML;
  }

  function isMobileCardsLayout() {
    return window.matchMedia('(max-width: ' + MOBILE_CARDS_MAX_WIDTH + 'px)').matches;
  }

  function normalizeSpace(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function setTableState(state, message) {
    var status = document.getElementById('ct-table-status');
    if (!status) return;
    status.setAttribute('data-state', state);
    status.textContent = message;
  }

  function getTipoContratoCodes(value) {
    return String(value || '')
      .split(',')
      .map(function (code) { return code.trim(); })
      .filter(Boolean);
  }

  function createIcon(className) {
    var icon = document.createElement('i');
    icon.className = className;
    icon.setAttribute('aria-hidden', 'true');
    return icon;
  }

  function createTipoContratoBadgesNode(value, compact) {
    var wrapper = document.createElement('span');
    wrapper.className = 'ct-contract-badges';
    getTipoContratoCodes(value).forEach(function (code) {
      var badge = document.createElement('span');
      badge.className = 'badge ' + (TIPO_CONTRATO_BADGES[code] || 'badge-secondary');
      badge.textContent = compact ? (TIPO_CONTRATO_SHORT_LABELS[code] || code) : (TIPO_CONTRATO_LABELS[code] || code);
      badge.title = TIPO_CONTRATO_LABELS[code] || code;
      wrapper.appendChild(badge);
    });
    return wrapper;
  }

  function resetPackageSlotVisibility() {
    $('.ct-contract-section').each(function () {
      var $section = $(this);
      var filledThrough = 0;
      $section.find('.ct-contract-row').each(function () {
        var $row = $(this);
        var index = parseInt($row.attr('data-slot-index') || '0', 10);
        var packageValue = String($row.find('.ct-package-select').val() || '').trim();
        if (packageValue) {
          filledThrough = Math.max(filledThrough, index);
        }
      });
      var visible = Math.max(1, Math.min(5, filledThrough));
      $section.data('visible-slots', visible);
      $section.find('.ct-contract-row').each(function () {
        var hidden = parseInt(this.getAttribute('data-slot-index') || '0', 10) > visible;
        this.hidden = hidden;
        this.classList.toggle('ct-contract-row--hidden', hidden);
      });
      $section.find('.ct-add-package').toggle(visible < 5);
    });
  }

  function ensureMobileCardsContainer() {
    var container = document.getElementById('hot-container');
    if (!container || !container.parentNode) return null;
    var cards = document.getElementById('ct-mobile-card-list');
    if (!cards) {
      cards = document.createElement('div');
      cards.id = 'ct-mobile-card-list';
      cards.className = 'ct-mobile-card-list';
      cards.setAttribute('aria-label', 'Paquetes de contratacion');
      container.parentNode.insertBefore(cards, container.nextSibling);
    }
    return cards;
  }

  function createMobileCardField(label, content) {
    var field = document.createElement('div');
    field.className = 'ct-mobile-card__field';
    var title = document.createElement('span');
    title.className = 'ct-mobile-card__label';
    title.textContent = label;
    field.appendChild(title);
    var value = document.createElement('div');
    value.className = 'ct-mobile-card__value';
    if (content instanceof Node) {
      value.appendChild(content);
    } else {
      value.textContent = String(content || '—');
    }
    field.appendChild(value);
    return field;
  }

  function createContractSummaryNode(value) {
    var summary = document.createElement('div');
    summary.className = 'ct-mobile-card__summary';
    var html = sanitizeContractSummaryHtml(value);
    if (!normalizeSpace(html)) {
      summary.textContent = 'Sin paquetes asociados';
      return summary;
    }
    var template = document.createElement('template');
    template.innerHTML = html;
    summary.appendChild(template.content.cloneNode(true));
    return summary;
  }

  function createMobileCard(rowData) {
    var card = document.createElement('article');
    card.className = 'ct-mobile-card';
    card.dataset.recordId = String(rowData.Id || '');
    var header = document.createElement('header');
    header.className = 'ct-mobile-card__header';
    var identity = document.createElement('div');
    identity.className = 'ct-mobile-card__identity';
    var code = document.createElement('span');
    code.className = 'ct-mobile-card__code';
    code.textContent = 'Cod. ' + (rowData.codigo || '—');
    var title = document.createElement('h3');
    title.className = 'ct-mobile-card__title';
    title.textContent = rowData.actividad || 'Sin familia';
    identity.appendChild(code);
    identity.appendChild(title);
    header.appendChild(identity);
    header.appendChild(createTipoContratoBadgesNode(rowData.tipoContrato, true));
    card.appendChild(header);

    var body = document.createElement('div');
    body.className = 'ct-mobile-card__body';
    body.appendChild(createMobileCardField('Descripción', rowData.descripcionActividad));
    body.appendChild(createMobileCardField('Fecha de inicio', rowData.fechaInicio));
    body.appendChild(createMobileCardField('Paquetes asociados', createContractSummaryNode(rowData.contratosAsociados)));
    card.appendChild(body);

    if (canEditContracts()) {
      var actions = document.createElement('div');
      actions.className = 'ct-mobile-card__actions';
      var edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'btn btn-primary btn-sm ct-mobile-card__action';
      edit.title = 'Editar paquetes';
      edit.setAttribute('aria-label', 'Editar paquetes');
      edit.appendChild(createIcon('fa fa-edit fa-xs'));
      var editLabel = document.createElement('span');
      editLabel.textContent = 'Editar paquetes';
      edit.appendChild(editLabel);
      edit.addEventListener('click', function () {
        openEditModal(rowData);
      });
      actions.appendChild(edit);
      card.appendChild(actions);
    }
    return card;
  }

  function renderMobileCards() {
    var cards = ensureMobileCardsContainer();
    if (!cards) return;
    var tableContainer = document.getElementById('hot-container');
    var useCards = isMobileCardsLayout();
    cards.hidden = !useCards;
    if (tableContainer) {
      tableContainer.style.setProperty('display', useCards ? 'none' : 'block', 'important');
    }
    if (!useCards) {
      cards.replaceChildren();
      return;
    }
    var fragment = document.createDocumentFragment();
    if (!masterData.length) {
      var empty = document.createElement('p');
      empty.className = 'ct-mobile-card-list__empty';
      empty.textContent = 'No hay paquetes de contratacion para mostrar.';
      fragment.appendChild(empty);
    }
    masterData.forEach(function (rowData) {
      fragment.appendChild(createMobileCard(rowData));
    });
    cards.replaceChildren(fragment);
  }

  // ── Load data from API ──

  function loadData() {
    var db = getDb();
    var semana = getMaxSemana();
    setTableState('loading', 'Cargando contratos…');
    if (!db || !semana) {
      masterData = [];
      updateOrInitHot(masterData);
      setTableState('empty', 'Sin registros de contratos para mostrar.');
      return;
    }

    $.ajax({
      method: 'POST',
      url: '/api/contratos/list?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      dataType: 'json',
    }).done(function (response) {
      var raw = response && Array.isArray(response.data) ? response.data : [];
      masterData = raw;
      updateOrInitHot(masterData);
      setTableState(raw.length ? 'data' : 'empty', raw.length
        ? raw.length + ' registros de contratos cargados.'
        : 'Sin registros de contratos para mostrar.');
    }).fail(function () {
      masterData = [];
      updateOrInitHot(masterData);
      setTableState('error', 'No se pudieron cargar los contratos. Intenta nuevamente.');
    });
  }

  // ── Custom renderers ──

  function registerRenderers() {
    if (renderersRegistered) return;

    Handsontable.renderers.registerRenderer('ctTipoContratoRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);

      var codes = [];
      if (value && typeof value === 'string') {
        codes = value.split(',').map(function (v) { return v.trim(); }).filter(Boolean);
      }

      td.innerHTML = '';
      td.className = td.className.replace(/htRight|htLeft|htCenter/g, '') + ' htMiddle';
      td.classList.remove('ct-empty-contract-type');

      if (codes.length === 0) {
        td.textContent = '—';
        td.classList.add('ct-empty-contract-type');
        return;
      }

      td.appendChild(createTipoContratoBadgesNode(value, isMobileCardsLayout()));
    });

    Handsontable.renderers.registerRenderer('ctContractAssociationsRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.className = td.className.replace(/htRight|htCenter/g, '') + ' htLeft htMiddle force-wrap';
      if (!value) {
        td.innerHTML = '<span class="text-muted">Sin paquetes asociados</span>';
        return;
      }
      td.innerHTML = sanitizeContractSummaryHtml(value);
    });

    // ── Action button renderer ──
    Handsontable.renderers.registerRenderer('ctActionRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.innerHTML = '';
      td.className = td.className.replace(/htRight|htLeft|htCenter/g, '') + ' htCenter htMiddle';

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'editar btn btn-primary btn-sm btn-action-gap';
      btn.title = 'Editar';
      btn.setAttribute('aria-label', 'Editar paquetes');
      btn.innerHTML = '<i class="fa fa-edit fa-xs"></i>';

      if (!canEditContracts()) {
        return;
      }

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var visualRow = instance.toVisualRow(row);
        var rowData = instance.getSourceDataAtRow(row);
        if (rowData) {
          openEditModal(rowData);
        }
      });

      td.appendChild(btn);
    });

    renderersRegistered = true;
  }

  // ── Open edit modal (was obtener_data_editar logic) ──

  function openEditModal(data) {
    var db = getDb();

    if (!canEditContracts()) {
      return;
    }
    var modalLoadId = ++modalLoadSequence;

    var Id = data.Id;
    var tipoContrato = data.tipoContrato || '';
    var actividad = data.actividad || '';

    // Set form values
    var $Id = document.getElementById('contratoId');
    if ($Id) $Id.value = Id;

    var $opcion = document.getElementById('opcion');
    if ($opcion) $opcion.value = 'modificar';

    var $tipoContrato = document.getElementById('tipoContrato');
    if ($tipoContrato) $tipoContrato.value = tipoContrato;

    var $actividadModificar = document.getElementById('actividadModificar');
    if ($actividadModificar) $actividadModificar.value = actividad;

    // Parse comma-separated modalidades and set checkboxes
    var modalidades = tipoContrato.split(',').map(function (s) { return s.trim(); });
    $('#modalidadSI').prop('checked', modalidades.indexOf('SI') >= 0);
    $('#modalidadMO').prop('checked', modalidades.indexOf('MO') >= 0);
    $('#modalidadS').prop('checked', modalidades.indexOf('S') >= 0);
    $('#modalidadOC').prop('checked', modalidades.indexOf('OC') >= 0);

    $('#formularioEditarContratos .ct-package-select, #formularioEditarContratos .ct-contract-control--multiple')
      .val(null).trigger('change');
    // El estado de error de la cantidad son cuatro cosas a la vez (aria-invalid,
    // aria-describedby, el texto del ancla sr-only y el customValidity). Retirar solo
    // algunas dejaria el campo atado a un mensaje que ya no aplica, asi que este camino
    // reusa el mismo limpiador que la revalidacion y el cierre del modal, en vez de
    // repetir aqui una version que puede quedarse corta. Declarado en el <script> de
    // contratos.view.php, que carga antes que este archivo.
    $('#formularioEditarContratos .ct-contract-quantity').val('1');
    if (typeof window.clearContractQuantityErrors === 'function') window.clearContractQuantityErrors();
    // Mismo motivo que arriba: el aviso global lleva `role="alert"` y su limpiador cancela
    // ademas el repintado pendiente que reinyectaria el texto en un modal ya reseteado.
    if (typeof window.clearContractMessage === 'function') {
      window.clearContractMessage();
    } else {
      $('.mensaje').stop(true, true).text('').removeClass('ct-message-error').show();
    }
    if (typeof window.updateSections === 'function') window.updateSections();
    if (typeof window.updateCheckboxState === 'function') window.updateCheckboxState();
    resetPackageSlotVisibility();

    // Load package lists based on comma-separated modalidades
    $.ajax({
      method: 'POST',
      url: '/api/contratos/save?db=' + db,
      data: { opcion: 'actualizarListadoPaquetesContratacion', tipoContrato: tipoContrato }
    }).done(function (info) {
      if (modalLoadId !== modalLoadSequence) return;
      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);

      $.ajax({
        method: 'POST',
        url: '/api/contratos/save?db=' + db,
        data: { opcion: 'actualizarInsumosRecursos', tipoContrato: tipoContrato }
      }).done(function (info2) {
        if (modalLoadId !== modalLoadSequence) return;
        var json_info2 = (typeof info2 === 'string' ? JSON.parse(info2) : info2);

        var hasSI = modalidades.indexOf('SI') >= 0;
        var hasMO = modalidades.indexOf('MO') >= 0;
        var hasS = modalidades.indexOf('S') >= 0;
        var hasOC = modalidades.indexOf('OC') >= 0;

        // Populate SI section
        if (hasSI) {
          $('#SI1, #SI2, #SI3, #SI4, #SI5').html(json_info2.listadoSI || '<option value=""></option>').change();
          $('#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5').html(json_info.listadoSI || '<option value=""></option>').change();
          if (data.SI1 && data.SI1 !== '') $('#SI1').val(data.SI1.split(';')).change();
          if (data.SI2 && data.SI2 !== '') $('#SI2').val(data.SI2.split(';')).change();
          if (data.SI3 && data.SI3 !== '') $('#SI3').val(data.SI3.split(';')).change();
          if (data.SI4 && data.SI4 !== '') $('#SI4').val(data.SI4.split(';')).change();
          if (data.SI5 && data.SI5 !== '') $('#SI5').val(data.SI5.split(';')).change();
          selectContractPackage('#paqueteSI1', data.paqueteSI1);
          selectContractPackage('#paqueteSI2', data.paqueteSI2);
          selectContractPackage('#paqueteSI3', data.paqueteSI3);
          selectContractPackage('#paqueteSI4', data.paqueteSI4);
          selectContractPackage('#paqueteSI5', data.paqueteSI5);
        } else {
          $('#SI1, #SI2, #SI3, #SI4, #SI5').html('<option value=""></option>').change();
          $('#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5').html('<option value=""></option>').change();
          $('#SI1,#SI2,#SI3,#SI4,#SI5').val('').change();
          $('#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5').val('').change();
        }

        // Populate MO section
        if (hasMO) {
          $('#MO1, #MO2, #MO3, #MO4, #MO5').html(json_info2.listadoMO || '<option value=""></option>').change();
          $('#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5').html(json_info.listadoMO || '<option value=""></option>').change();
          if (data.MO1 && data.MO1 !== '') $('#MO1').val(data.MO1.split(';')).change();
          if (data.MO2 && data.MO2 !== '') $('#MO2').val(data.MO2.split(';')).change();
          if (data.MO3 && data.MO3 !== '') $('#MO3').val(data.MO3.split(';')).change();
          if (data.MO4 && data.MO4 !== '') $('#MO4').val(data.MO4.split(';')).change();
          if (data.MO5 && data.MO5 !== '') $('#MO5').val(data.MO5.split(';')).change();
          selectContractPackage('#paqueteMO1', data.paqueteMO1);
          selectContractPackage('#paqueteMO2', data.paqueteMO2);
          selectContractPackage('#paqueteMO3', data.paqueteMO3);
          selectContractPackage('#paqueteMO4', data.paqueteMO4);
          selectContractPackage('#paqueteMO5', data.paqueteMO5);
        } else {
          $('#MO1, #MO2, #MO3, #MO4, #MO5').html('<option value=""></option>').change();
          $('#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5').html('<option value=""></option>').change();
          $('#MO1,#MO2,#MO3,#MO4,#MO5').val('').change();
          $('#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5').val('').change();
        }

        // Populate S section
        if (hasS) {
          $('#S1, #S2, #S3, #S4, #S5').html(json_info2.listadoS || '<option value=""></option>').change();
          $('#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5').html(json_info.listadoS || '<option value=""></option>').change();
          if (data.S1 && data.S1 !== '') $('#S1').val(data.S1.split(';')).change();
          if (data.S2 && data.S2 !== '') $('#S2').val(data.S2.split(';')).change();
          if (data.S3 && data.S3 !== '') $('#S3').val(data.S3.split(';')).change();
          if (data.S4 && data.S4 !== '') $('#S4').val(data.S4.split(';')).change();
          if (data.S5 && data.S5 !== '') $('#S5').val(data.S5.split(';')).change();
          selectContractPackage('#paqueteS1', data.paqueteS1);
          selectContractPackage('#paqueteS2', data.paqueteS2);
          selectContractPackage('#paqueteS3', data.paqueteS3);
          selectContractPackage('#paqueteS4', data.paqueteS4);
          selectContractPackage('#paqueteS5', data.paqueteS5);
        } else {
          $('#S1, #S2, #S3, #S4, #S5').html('<option value=""></option>').change();
          $('#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5').html('<option value=""></option>').change();
          $('#S1,#S2,#S3,#S4,#S5').val('').change();
          $('#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5').val('').change();
        }

        // Populate OC section
        if (hasOC) {
          $('#OC1, #OC2, #OC3, #OC4, #OC5').html(json_info2.listadoOC || '<option value=""></option>').change();
          $('#paqueteOC1, #paqueteOC2, #paqueteOC3, #paqueteOC4, #paqueteOC5').html(json_info.listadoOC || '<option value=""></option>').change();
          if (data.OC1 && data.OC1 !== '') $('#OC1').val(data.OC1.split(';')).change();
          if (data.OC2 && data.OC2 !== '') $('#OC2').val(data.OC2.split(';')).change();
          if (data.OC3 && data.OC3 !== '') $('#OC3').val(data.OC3.split(';')).change();
          if (data.OC4 && data.OC4 !== '') $('#OC4').val(data.OC4.split(';')).change();
          if (data.OC5 && data.OC5 !== '') $('#OC5').val(data.OC5.split(';')).change();
          selectContractPackage('#paqueteOC1', data.paqueteOC1);
          selectContractPackage('#paqueteOC2', data.paqueteOC2);
          selectContractPackage('#paqueteOC3', data.paqueteOC3);
          selectContractPackage('#paqueteOC4', data.paqueteOC4);
          selectContractPackage('#paqueteOC5', data.paqueteOC5);
        } else {
          $('#OC1, #OC2, #OC3, #OC4, #OC5').html('<option value=""></option>').change();
          $('#paqueteOC1, #paqueteOC2, #paqueteOC3, #paqueteOC4, #paqueteOC5').html('<option value=""></option>').change();
          $('#OC1,#OC2,#OC3,#OC4,#OC5').val('').change();
          $('#paqueteOC1,#paqueteOC2,#paqueteOC3,#paqueteOC4,#paqueteOC5').val('').change();
        }

        setPackageQuantities(data);
        updateSections();
        updateCheckboxState();
        resetPackageSlotVisibility();
      });
    });

    // Show modal
    $('#modalEditarContratos').modal('show');
    $('#modal-body-texto-EditarContratos')
      .empty()
      .append($('<span class="ct-modal-title-main"></span>').text('Editar paquetes'))
      .append($('<span class="ct-modal-title-family"></span>').text(data.actividad || 'Sin familia'));
  }

  function getColumns(widths) {
    return [
      {
        data: 'Id',
        renderer: 'ctActionRenderer',
        readOnly: true,
        className: 'htCenter htMiddle',
        width: widths[0],
      },
      {
        data: 'codigo',
        readOnly: true,
        className: 'htCenter htMiddle',
        width: widths[1],
      },
      {
        data: 'actividad',
        readOnly: true,
        className: 'htLeft htMiddle force-wrap',
        width: widths[2],
      },
      {
        data: 'descripcionActividad',
        readOnly: true,
        className: 'htLeft htMiddle force-wrap',
        width: widths[3],
      },
      {
        data: 'fechaInicio',
        readOnly: true,
        type: 'date',
        dateFormat: 'YYYY-MM-DD',
        className: 'htCenter htMiddle',
        width: widths[4],
      },
      {
        data: 'tipoContrato',
        readOnly: true,
        renderer: 'ctTipoContratoRenderer',
        className: 'htCenter htMiddle',
        width: widths[5],
      },
      {
        data: 'contratosAsociados',
        readOnly: true,
        renderer: 'ctContractAssociationsRenderer',
        className: 'htLeft htMiddle force-wrap',
        width: widths[6],
      },
    ];
  }

  // ── Create or update HOT instance ──

  function updateOrInitHot(data) {
    registerRenderers();
    syncContainerHeight();

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

    hot = new Handsontable(container, {
      data: data,
      rowHeaders: false,
      colHeaders: [
        '',
        'Código',
        'Familia',
        'Descripción',
        'Fecha de Inicio',
        'Modalidad de contratación',
        'Paquetes de contratación asociados',
      ],
      columns: getColumns(fittedColumnWidths),
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'all',
      autoColumnSize: false,
      colWidths: fittedColumnWidths,
      manualColumnResize: true,
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
    });

    hot.listen();

    renderMobileCards();
    scheduleLayoutRefresh(0, true);

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
        lastAppliedContainerWidth = containerWidth;
      }
      if (Object.keys(settings).length) {
        hot.updateSettings(settings);
      }
      renderMobileCards();
      if (typeof hot.refreshDimensions === 'function') {
        hot.refreshDimensions();
      }
      hot.render();
    }, Number.isFinite(delay) ? delay : 100);
  }

  // ── recargarTabla (matches existing pattern) ──

  function recargarTabla(opcion) {
    loadData();
  }

  // ── Init ──

  function init() {
    if (initialized) {
      loadData();
      return;
    }

    $(window).off('resize.ctHot orientationchange.ctHot aia:viewport-scale-change.ctHot')
      .on('resize.ctHot orientationchange.ctHot aia:viewport-scale-change.ctHot', function () {
        scheduleLayoutRefresh(200, true);
      });

    $('#modalEditarContratos').off('hide.bs.modal.ctHotLoad').on('hide.bs.modal.ctHotLoad', function () {
      modalLoadSequence += 1;
    });

    initialized = true;
    loadData();
  }

  // ── Public API ──

  window.ContratosHotModule = {
    init: init,
    getHotInstance: function () {
      return hot;
    },
    loadData: loadData,
    recargarTabla: recargarTabla,
  };

})(window, jQuery);
