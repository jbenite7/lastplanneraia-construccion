(function (window, $) {
  'use strict';

  var mainHot = null;
  var definirHot = null;
  var initialized = false;
  var mainRows = [];
  var definirRows = [];
  var activeFilters = [];
  var soloAlertas = false;
  var deleteBound = false;
  var mobileCardsBound = false;
  var semiAutoInitialized = false;

  var MAIN_HOT_ID = 'dt_cliente';
  var DEFINIR_HOT_ID = 'dt_definirContratos';
  var MAIN_COLUMN_WIDTHS = [98, 190, 240, 180, 220, 180, 160, 160, 140, 220];
  var MAIN_COLUMN_MIN_WIDTHS = [38, 96, 116, 96, 122, 110, 92, 92, 80, 92];
  var DEFINIR_COLUMN_WIDTHS = [180, 260, 150];
  var DEFINIR_COLUMN_MIN_WIDTHS = [110, 130, 88];
  var ROW_STATE_CLASS_MAP = {
    header: 'Titulo pdc-header',
    missing: 'pdc-missing-data',
    critical: 'pdc-critical-delay',
    delayed: 'pdc-delayed',
    'completed-late': 'pdc-completed-delayed',
    'completed-ontime': 'pdc-completed-ontime',
    active: 'pdc-active',
    'not-started': 'pdc-not-started',
    standard: '',
  };

  function getDb() {
    return $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana').val() || $('#Max_Semana').val() || '';
  }

  function getMaxSemana() {
    return $('#Max_Semana').val() || getSemana();
  }

  function canEdit() {
    if (window.rbacCapabilities && typeof window.rbacCapabilities.canManagePdC !== 'undefined') {
      return !!(window.rbacCapabilities.canManagePdC || window.rbacCapabilities.canManageContracts);
    }

    var permiso = String($('#permiso_canonico').val() || '').trim().toUpperCase();
    return !/^(G|S|SG|R|DCV|V|C)$/.test(permiso);
  }

  function normalizeText(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
  }

  function displayModality(value) {
    var raw = String(value || '');
    var tokens = raw.split(',').map(function (item) { return $.trim(item); }).filter(Boolean);
    if (!tokens.length) return '';
    return tokens.map(function (token) {
      if (token === 'OC' || token === 'Orden de Compra') return 'Orden de servicio/compra';
      return token;
    }).join(', ');
  }

  function displayStatus(value) {
    var original = String(value || '');
    var normalized = normalizeText(original);

    if (!normalized) return '';
    if (normalized.includes('atrasado: contratacion sin iniciar')) return 'Atrasado: contratación sin iniciar';
    if (normalized.includes('en curso: contratacion sin iniciar')) return 'En curso: contratación sin iniciar';
    if (normalized.includes('atrasado') && normalized.includes('proceso de contratacion no iniciado')) return 'Atrasado: contratación sin iniciar';
    if ((normalized.includes('en curso') || normalized.startsWith('a tiempo')) && normalized.includes('proceso de contratacion no iniciado')) return 'En curso: contratación sin iniciar';
    if (normalized.includes('inicio de contratacion vencido')) return 'Inicio de contratación vencido';
    if (normalized.includes('contratacion atrasada')) return 'Contratación atrasada';
    if (normalized.includes('contratacion cerrada tarde') || normalized.includes('terminado con retras')) return 'Contratación cerrada tarde';
    if (normalized.includes('contratacion cerrada a tiempo') || normalized.includes('terminado a tiempo')) return 'Contratación cerrada a tiempo';
    if (normalized.includes('contratacion pendiente de inicio')) return 'Contratación pendiente de inicio';
    if (normalized.includes('contratacion en curso')) return 'Contratación en curso';
    if (normalized.includes('atrasado')) return 'Contratación atrasada';
    if (normalized.includes('proceso de contratacion no iniciado')) return 'Contratación pendiente de inicio';
    if (normalized.includes('en curso') || normalized.startsWith('a tiempo')) return 'Contratación en curso';

    return original;
  }

  function getStatusState(value) {
    if (typeof window.getPDCStatusState === 'function') {
      return window.getPDCStatusState(value);
    }

    var normalized = normalizeText(value);
    if (normalized.includes('inicio de contratacion vencido')) return 'critical';
    if (normalized.includes('contratacion atrasada') || normalized.includes('atrasado')) return 'delayed';
    if (normalized.includes('contratacion cerrada tarde') || normalized.includes('terminado con retras')) return 'completed-late';
    if (normalized.includes('contratacion cerrada a tiempo') || normalized.includes('terminado a tiempo')) return 'completed-ontime';
    if (normalized.includes('contratacion pendiente de inicio') || normalized.includes('proceso de contratacion no iniciado')) return 'not-started';
    if (normalized.includes('contratacion en curso') || normalized.includes('en curso') || normalized.startsWith('a tiempo')) return 'active';
    return '';
  }

  function isMissingData(row) {
    if (typeof window.isPDCMissingData === 'function') {
      return window.isPDCMissingData(row);
    }

    return !row || $.trim(String(row.fechaInicioProyectada || '')) === '' || row.valorPresupuesto === '' || row.valorPresupuesto === null || typeof row.valorPresupuesto === 'undefined';
  }

  function getRowState(row) {
    if (!row) return 'standard';
    if (Number(row.titulo) !== 0) return 'header';

    if (isMissingData(row)) return 'missing';
    var statusState = getStatusState(row.estado || '');
    if (statusState) return statusState;
    return 'standard';
  }

  function getRowStateClass(row) {
    return ROW_STATE_CLASS_MAP[getRowState(row)] || '';
  }

  function matchesFilter(row, state) {
    if (Number(row.titulo) !== 0) return true;
    if (state === 'missing') return isMissingData(row);
    return getRowState(row) === state;
  }

  function getVisibleRows(sourceRows) {
    var rows = Array.isArray(sourceRows) ? sourceRows.slice() : [];
    if (soloAlertas) {
      rows = rows.filter(function (row) {
        return Number(row.titulo) !== 0 ? true : ['missing', 'critical', 'delayed', 'completed-late'].indexOf(getRowState(row)) >= 0;
      });
    }
    if (!activeFilters.length) return rows;
    return rows.filter(function (row) {
      if (Number(row.titulo) !== 0) return true;
      return activeFilters.some(function (state) { return matchesFilter(row, state); });
    });
  }

  function isMobileCardsLayout() {
    return window.matchMedia('(max-width: 767px)').matches;
  }

  function updateLegendCounts() {
    var counters = {
      missing: 0,
      critical: 0,
      delayed: 0,
      'completed-late': 0,
      'completed-ontime': 0,
      active: 0,
      'not-started': 0,
    };

    mainRows.forEach(function (row) {
      if (Number(row.titulo) !== 0) return;
      var state = getRowState(row);
      if (counters[state] !== undefined) {
        counters[state] += 1;
      }
    });

    Object.keys(counters).forEach(function (state) {
      $('#count-' + state).text(counters[state]);
    });

    $('#count-alertas').text(mainRows.filter(function (row) {
      return Number(row.titulo) === 0 && ['missing', 'critical', 'delayed', 'completed-late'].indexOf(getRowState(row)) >= 0;
    }).length);
  }

  function updateLegendState() {
    $('.pdc-legend-item').removeClass('inactive-filter');
    if (!activeFilters.length) return;

    $('.pdc-legend-item').addClass('inactive-filter');
    activeFilters.forEach(function (state) {
      $('.pdc-legend-item.' + state).removeClass('inactive-filter');
    });
  }

  function ensureMessageBox() {
    if (!$('#mensajeActualizacion').length) {
      $('body').append('<div id="mensajeActualizacion" class="pdc-toast"></div>');
    }
  }

  function setMainTableState(state, message) {
    var container = document.getElementById(MAIN_HOT_ID);
    if (!container || !container.parentNode) return;
    var status = document.getElementById('pdc-table-state');
    if (!status) {
      status = document.createElement('p');
      status.id = 'pdc-table-state';
      container.parentNode.insertBefore(status, container);
    }
    status.className = 'pdc-toolbar-message ' + (state === 'error' ? 'pdc-message-error' : 'pdc-message-neutral');
    status.setAttribute('role', state === 'error' ? 'alert' : 'status');
    status.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
    status.dataset.state = state;
    status.textContent = message || '';
    status.hidden = state === 'ready';
  }

  function showNotice(type, message) {
    ensureMessageBox();
    var $box = $('#mensajeActualizacion');
    var ok = type === 'success';
    $box.stop(true, true).show();
    $box
      .removeClass('pdc-message-success pdc-message-error pdc-message-neutral')
      .addClass(ok ? 'pdc-message-success' : 'pdc-message-error');
    $box.html('<i class="fas ' + (ok ? 'fa-check-circle' : 'fa-exclamation-circle') + ' mr-1"></i>' + message);
    clearTimeout(showNotice._timer);
    showNotice._timer = setTimeout(function () {
      $box.fadeOut(300, function () {
        $box.html('').removeClass('pdc-message-success pdc-message-error pdc-message-neutral').show();
      });
    }, 2500);
  }

  function getMainHeight() {
    var top = $('#cuadroTabla').offset();
    var viewport = window.innerHeight || document.documentElement.clientHeight || 800;
    var used = top ? top.top : 300;
    return Math.max(viewport - used - 140, 320);
  }

  function getFittedColumnWidths(container, baseWidths, minWidths) {
    if (!container) return baseWidths.slice();
    var gridChrome = window.matchMedia('(max-width: 767px)').matches ? 2 : 48;
    var targetWidth = Math.max(Math.floor(container.clientWidth || 0) - gridChrome, 320);
    var baseWidth = baseWidths.reduce(function (sum, width) {
      return sum + width;
    }, 0);
    var widths = baseWidths.map(function (width, index) {
      return Math.max(minWidths[index] || 24, Math.floor(width * targetWidth / baseWidth));
    });
    var total = widths.reduce(function (sum, width) { return sum + width; }, 0);
    if (total > targetWidth) {
      widths = baseWidths.map(function (width) {
        return Math.max(24, Math.floor(width * targetWidth / baseWidth));
      });
      total = widths.reduce(function (sum, width) { return sum + width; }, 0);
    }
    widths[widths.length - 1] += targetWidth - total;
    return widths;
  }

  function applyColumnWidths(columns, widths) {
    return columns.map(function (column, index) {
      return $.extend({}, column, { width: widths[index] || column.width });
    });
  }

  function getSourceRow(instance, visualRow) {
    var physicalRow = typeof instance.toPhysicalRow === 'function'
      ? instance.toPhysicalRow(visualRow)
      : visualRow;
    return instance.getSourceDataAtRow(physicalRow) || {};
  }

  function getModalHeight() {
    var modal = document.querySelector('#modalDefinirContratos .modal-body');
    if (!modal) return 260;
    return Math.max(window.innerHeight - modal.getBoundingClientRect().top - 180, 220);
  }

  function renderToolbar() {
    var semana = getSemana();
    var compact = window.matchMedia('(max-width: 767px)').matches;
    var updateLabel = compact ? 'Recargar' : 'Actualizar';
    var alertsLabel = compact ? 'Alertas' : 'Ver alertas';
    var editorActions = canEdit()
      ? '<button id="btn_actualizarPDC" class="btn-pdc-modern ps-btn-gap" title="Actualizar items" onclick="actualizarPDC()">' + updateLabel + ' <i class="fas fa-sync fa-lg"></i></button>'
      : '';
    var automationAction = canEdit()
      ? '<button id="btn_pdcSemiAuto" class="btn-pdc-modern ps-btn-gap" title="Revisar propuestas automáticas"><i class="fas fa-magic" aria-hidden="true"></i> Analizar propuestas</button>'
      : '';
    $('div.toolbarAcciones').html(
      '<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Actions">' +
      editorActions +
      '<button id="btn_definirContratosPDC" class="btn-pdc-modern ps-btn-gap" title="Desglosar paquetes" onclick="obtener_data_definirContratos()">Desglosar <i class="fa fa-list-ol fa-lg" aria-hidden="true"></i></button>' +
      '<button id="btn_soloAlertas" class="btn-pdc-modern ps-btn-gap pdc-btn-alertas' + (soloAlertas ? ' is-active' : '') + '" title="Mostrar solo paquetes que necesitan atención" onclick="toggleSoloAlertas()"><i class="fas fa-bell fa-lg"></i> ' + alertsLabel + ' <span id="count-alertas" class="badge badge-light"></span></button>' +
      '<button id="btn_limpiarFiltrosPDC" class="btn-pdc-modern ps-btn-gap" title="Limpiar todos los filtros" onclick="clearPdcFilters()"><i class="fas fa-undo" aria-hidden="true"></i> Limpiar filtros</button>' +
      automationAction +
      '</div>'
    );

    $('#btn_pdcSemiAuto').off('click.pdcSemiAuto').on('click.pdcSemiAuto', function () {
      if (window.SemiAutoReview) window.SemiAutoReview.open('pdc');
    });

    $('div.toolbarNavegacion').html(
      window.AIAInfoGeneralNav.render('pdc', semana, 'planCompras')
    );

    if (!$('.toolbarFilaMensajes .pdc-toolbar-message').length) {
      $('.toolbarFilaMensajes').html('<div class="pdc-toolbar-message"></div>');
    }
    ensureMessageBox();
  }

  function initSemiAutoReview() {
    if (semiAutoInitialized || !window.SemiAutoReview) return;
    semiAutoInitialized = true;
    window.SemiAutoReview.init({
      module: 'pdc',
      anchorSelector: 'div.toolbarFilaMensajes',
      refresh: refreshMain,
    });
  }

  function ensureCalcDateBadge() {
    var weekInfo = document.querySelector('.context-week-info');
    if (!weekInfo || document.getElementById('ctxPdcCalcDate')) return;
    var parent = weekInfo.parentNode;
    if (!parent) return;
    var wrapper = document.createElement('div');
    wrapper.className = 'context-right-group';
    var badge = document.createElement('span');
    badge.id = 'ctxPdcCalcDate';
    badge.className = 'badge p-2 mr-2';
    badge.innerHTML = '<i class="fas fa-calendar-alt mr-1"></i> Estado calculado al <strong class="pdc-calc-date">--/--/----</strong>';
    parent.insertBefore(wrapper, weekInfo);
    wrapper.appendChild(badge);
    wrapper.appendChild(weekInfo);
  }

  function updateCalcDateBadge() {
    var row = mainRows.find(function (item) { return item && item.fechaCalculo; });
    $('.pdc-calc-date').text(row ? row.fechaCalculo : '--/--/----');
  }

  function renderMainHeight() {
    var container = document.getElementById(MAIN_HOT_ID);
    if (container) {
      container.style.height = getMainHeight() + 'px';
    }
  }

  function renderDefinirHeight() {
    var container = document.getElementById(DEFINIR_HOT_ID);
    if (container) {
      container.style.height = getModalHeight() + 'px';
    }
  }

  function actionRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    td.innerHTML = '';
    td.className = td.className.replace(/htLeft|htRight|htCenter/g, '') + ' htCenter htMiddle';

    var rowData = getSourceRow(instance, row);
    var isHeader = Number(rowData.titulo) !== 0;
    var wrap = document.createElement('div');
    wrap.className = 'pdc-row-actions';

    if (Number(rowData.listoParaIniciar) === 1) {
      var ok = document.createElement('i');
      ok.className = 'fas fa-check-circle pdc-ready-icon pdc-ready-icon--ok';
      ok.title = 'Listo para iniciar';
      wrap.appendChild(ok);
    } else if (Number(rowData.necesitaConfiguracion) === 1) {
      var cfg = document.createElement('i');
      cfg.className = 'fas fa-cog pdc-ready-icon pdc-ready-icon--config';
      cfg.title = 'Necesita configurar duraciones';
      wrap.appendChild(cfg);
    } else if (Number(rowData.diasDelta || 0) < 0) {
      var risk = document.createElement('i');
      risk.className = 'fas fa-exclamation-triangle pdc-ready-icon pdc-ready-icon--risk';
      risk.title = 'En riesgo de retraso';
      wrap.appendChild(risk);
    }

    if (!isHeader && canEdit()) {
      var edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'editar pdc-row-action pdc-row-action--edit';
      edit.title = 'Editar actividad';
      edit.setAttribute('aria-label', 'Editar actividad');
      edit.dataset.pdcConsecutivo = rowData.consecutivo || '';
      edit.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i>';
      edit.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openEditModal(rowData);
      });
      wrap.appendChild(edit);

      if (Number(rowData.subcontratoPaquete || 0) > 1) {
        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'eliminar pdc-row-action pdc-row-action--delete';
        del.title = 'Eliminar';
        del.setAttribute('aria-label', 'Eliminar');
        del.dataset.pdcConsecutivo = rowData.consecutivo || '';
        del.innerHTML = '<i class="fas fa-trash-alt" aria-hidden="true"></i>';
        del.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          openDeleteModal(rowData);
        });
        wrap.appendChild(del);
      }
    } else if (!isHeader) {
      var view = document.createElement('button');
      view.type = 'button';
      view.className = 'editar pdc-row-action pdc-row-action--edit';
      view.title = 'Ver actividad';
      view.setAttribute('aria-label', 'Ver actividad');
      view.dataset.pdcConsecutivo = rowData.consecutivo || '';
      view.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';
      view.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openEditModal(rowData);
      });
      wrap.appendChild(view);
    }

    td.appendChild(wrap);
  }

  function modalityRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    td.textContent = displayModality(value);
  }

  function packageRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    var rowData = getSourceRow(instance, row);
    var text = String(value || '');
    if (Number(rowData.subcontratoPaquete || 0) > 1 && text) {
      text += ' (Subcontrato ' + rowData.subcontratoPaquete + ')';
    }
    td.textContent = text;
  }

  function appendStatusDelta(td, rowData) {
    var diasDelta = Number(rowData.diasDelta || 0);
    if (Number(rowData.titulo) !== 0 || !diasDelta || rowData.id === '') return;
    var delta = document.createElement('span');
    var deberiaHoyDate = String(rowData.deberiaHoyDate || '');
    delta.className = 'pdc-delta ' + (diasDelta > 0 ? 'pdc-delta--ahead' : 'pdc-delta--delay');
    delta.title = deberiaHoyDate
      ? 'Días desde la fecha teórica del último paso vencido (' + deberiaHoyDate + ')'
      : 'Días de diferencia respecto al cronograma';
    delta.textContent = Math.abs(diasDelta) + (diasDelta > 0 ? ' días de adelanto' : ' días de retraso');
    td.appendChild(document.createTextNode(' '));
    td.appendChild(delta);
  }

  function statusRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    var rowData = getSourceRow(instance, row);
    var texto = typeof window.displayPDCStatus === 'function' ? window.displayPDCStatus(rowData.estado || '') : String(rowData.estado || '');
    var estado = getRowState(rowData);
    var iconClass = '';
    if (Number(rowData.titulo) !== 0) {
      td.textContent = texto;
      return;
    }
    if (estado === 'missing') texto = 'Información pendiente';
    if (estado === 'completed-ontime') iconClass = 'fas fa-grin-stars fa-lg pdc-icon-state pdc-icon-ok';
    if (estado === 'completed-late') iconClass = 'fas fa-sad-cry fa-lg pdc-icon-state pdc-icon-amber';
    if (estado === 'active' && normalizeText(texto) !== 'en curso: contratacion sin iniciar') iconClass = 'fas fa-glasses fa-lg pdc-icon-state pdc-icon-info';
    if (estado === 'critical' || estado === 'delayed') iconClass = 'fas fa-skull-crossbones fa-lg pdc-icon-state pdc-icon-danger';
    td.innerHTML = '';
    if (iconClass) {
      var icon = document.createElement('i');
      icon.className = iconClass;
      icon.setAttribute('aria-hidden', 'true');
      td.appendChild(icon);
      td.appendChild(document.createTextNode(' '));
    }
    td.appendChild(document.createTextNode(texto || 'No Registrado'));
    appendStatusDelta(td, rowData);
  }

  function dateRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    td.textContent = value || '';
  }

  function inicioObraRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    var rowData = getSourceRow(instance, row);
    var inicioSemana = $('#Fecha_Inicio_SemYMD').val() || '';
    var inicioProceso = new Date(rowData.fechaElaboracionPliegos);
    var fechaSemana = new Date(inicioSemana);
    var dias = (inicioProceso - fechaSemana) / (1000 * 3600 * 24);
    var procesoIniciado = Number(rowData.procesoIniciado || 0);
    var iconClass = '';
    var aviso = '';

    td.innerHTML = '';
    if (Number(rowData.titulo) !== 0 || !inicioSemana || isNaN(dias)) {
      td.textContent = value || '';
      return;
    }
    if (procesoIniciado === 0 && dias <= 7 && dias >= 0) {
      iconClass = 'fas fa-glasses fa-lg pdc-icon-state pdc-icon-warn';
      aviso = ' (Debe comenzar en ' + Math.floor(dias / 7) + ' semana)';
    } else if (procesoIniciado === 0 && dias > 7) {
      iconClass = 'fas fa-glasses fa-lg pdc-icon-state pdc-icon-info';
      aviso = ' (Debe comenzar en ' + Math.floor(dias / 7) + ' semanas)';
    } else if (procesoIniciado === 0 && dias < 0) {
      iconClass = 'fas fa-skull-crossbones fa-lg pdc-icon-state pdc-icon-danger';
      aviso = ' (Ya debió comenzar)';
    } else if (procesoIniciado === 1) {
      iconClass = 'fas fa-check fa-lg pdc-icon-state pdc-icon-ok';
    }
    if (iconClass) {
      var icon = document.createElement('i');
      icon.className = iconClass;
      icon.setAttribute('aria-hidden', 'true');
      td.appendChild(icon);
      td.appendChild(document.createTextNode(' '));
    }
    var text = document.createElement('b');
    text.textContent = value || '';
    td.appendChild(text);
    td.appendChild(document.createTextNode(aviso));
  }

  function numberRenderer(instance, td, row, col, prop, value) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    td.textContent = value === null || value === undefined ? '' : String(value);
  }

  function getMainColumns() {
    return [
      { title: '', data: 'boton', readOnly: true, renderer: actionRenderer, width: 98 },
      { title: 'MODALIDAD DE CONTRATACION', data: 'tipoPaquete', readOnly: true, width: 190, renderer: modalityRenderer },
      { title: 'PAQUETE DE CONTRATACION', data: 'paqueteContratacion', readOnly: true, width: 240, renderer: packageRenderer },
      { title: 'FAMILIAS ASOCIADAS', data: 'contratos', readOnly: true, width: 180, renderer: dateRenderer },
      { title: 'ESTADO DEL PROCESO', data: 'estado', readOnly: true, width: 220, renderer: statusRenderer },
      { title: 'INICIO DEL PROCESO DE CONTRATACIÓN', data: 'fechaElaboracionPliegos', readOnly: true, width: 180, renderer: dateRenderer },
      { title: 'INICIO EN OBRA SEGUN CRONOGRAMA', data: 'fechaInicio', readOnly: true, width: 160, renderer: inicioObraRenderer },
      { title: 'INICIO EN OBRA PROYECTADO', data: 'fechaInicioProyectada', readOnly: true, width: 160, renderer: dateRenderer },
      { title: 'INICIO EN OBRA REAL', data: 'fechaRealInicio', readOnly: true, width: 140, renderer: dateRenderer },
      { title: 'OBSERVACIONES', data: 'observacionesContrato', readOnly: true, width: 220, renderer: dateRenderer },
    ];
  }

  function getMainLayout() {
    var columns = getMainColumns();
    if (window.matchMedia('(max-width: 767px)').matches) {
      var indexes = [0, 2, 4, 6];
      var compactColumns = indexes.map(function (index) { return $.extend({}, columns[index]); });
      compactColumns[1].title = 'PAQUETE';
      compactColumns[2].title = 'ESTADO';
      compactColumns[3].title = 'INICIO EN OBRA';
      return {
        columns: compactColumns,
        widths: [54, 132, 122, 80],
        minimums: [44, 112, 104, 72],
      };
    }
    return { columns: columns, widths: MAIN_COLUMN_WIDTHS, minimums: MAIN_COLUMN_MIN_WIDTHS };
  }

  function mobileCardField(label, value) {
    var field = document.createElement('div');
    field.className = 'pdc-mobile-card__field';
    var term = document.createElement('span');
    term.className = 'pdc-mobile-card__label';
    term.textContent = label;
    var detail = document.createElement('strong');
    detail.textContent = value || 'Sin dato';
    field.appendChild(term);
    field.appendChild(detail);
    return field;
  }

  function mobileAction(kind, row) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'pdc-mobile-card__action pdc-mobile-card__action--' + kind;
    button.dataset.pdcMobileAction = kind;
    button.dataset.pdcConsecutivo = row.consecutivo;
    button.setAttribute('aria-label', kind === 'edit' ? 'Editar paquete' : 'Eliminar subcontrato');
    button.innerHTML = '<i class="fas ' + (kind === 'edit' ? 'fa-pencil-alt' : 'fa-trash-alt') + '" aria-hidden="true"></i>';
    return button;
  }

  function createMobileCard(row) {
    var card = document.createElement('article');
    card.className = 'pdc-mobile-card pdc-mobile-card--' + getRowState(row);
    card.dataset.pdcConsecutivo = row.consecutivo;
    var title = document.createElement('h3');
    title.className = 'pdc-mobile-card__title';
    title.textContent = row.paqueteContratacion || 'Paquete sin nombre';
    card.appendChild(title);
    card.appendChild(mobileCardField('Estado', displayStatus(row.estado)));
    card.appendChild(mobileCardField('Modalidad', displayModality(row.tipoPaquete)));
    card.appendChild(mobileCardField('Familias', row.contratos));
    card.appendChild(mobileCardField('Inicio en obra', row.fechaInicio));
    var actions = document.createElement('div');
    actions.className = 'pdc-mobile-card__actions';
    actions.appendChild(mobileAction('edit', row));
    if (canEdit() && Number(row.subcontratoPaquete) > 1) {
      actions.appendChild(mobileAction('delete', row));
    }
    card.appendChild(actions);
    return card;
  }

  function renderMobileCards(rows) {
    var root = document.getElementById('pdc-mobile-card-view');
    if (!root) return;
    root.replaceChildren();
    rows.filter(function (row) { return Number(row.titulo) === 0; })
      .forEach(function (row) { root.appendChild(createMobileCard(row)); });
  }

  function bindMobileCards() {
    if (mobileCardsBound) return;
    mobileCardsBound = true;
    $('#pdc-mobile-card-view').on('click', '[data-pdc-mobile-action]', function () {
      var row = findMainRow($(this).data('pdcConsecutivo'));
      if ($(this).data('pdcMobileAction') === 'delete') openDeleteModal(row);
      else openEditModal(row);
    });
  }

  function loadMainData() {
    var db = getDb();
    var semana = getMaxSemana();
    if (!db || !semana) {
      mainRows = [];
      renderMainGrid();
      setMainTableState('error', 'No se pudo determinar el proyecto o la semana.');
      return;
    }

    setMainTableState('loading', 'Cargando paquetes de contratación…');
    $.ajax({
      method: 'POST',
      url: '/api/pdc/list?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana) + '&definirContratos=0',
      dataType: 'json',
    }).done(function (response) {
      mainRows = response && Array.isArray(response.data) ? response.data : [];
      renderMainGrid();
      // El caso vacío ya no usa el párrafo suelto (`pdc-table-state`): lo cubre
      // `attachHtEmptyState` dentro de la malla (ver renderMainGrid). Aquí solo
      // se retira el mensaje de "Cargando…"; 'ready' oculta el párrafo siempre.
      setMainTableState('ready', '');
    }).fail(function () {
      mainRows = [];
      renderMainGrid();
      setMainTableState('error', 'No fue posible cargar los paquetes de contratación. Intenta nuevamente.');
    });
  }

  function renderMainGrid() {
    renderToolbar();
    initSemiAutoReview();
    renderMainHeight();
    ensureCalcDateBadge();
    updateCalcDateBadge();
    updateLegendCounts();
    updateLegendState();

    var container = document.getElementById(MAIN_HOT_ID);
    if (!container) return;

    var data = getVisibleRows(mainRows);
    var layout = getMainLayout();
    var mainColumnWidths = getFittedColumnWidths(container, layout.widths, layout.minimums);
    var columns = applyColumnWidths(layout.columns, mainColumnWidths);
    var syncHeaderWidth = function (column, th) {
      if (column < 0 || !th || !mainColumnWidths[column]) return;
      var width = mainColumnWidths[column] + 'px';
      th.style.setProperty('box-sizing', 'border-box', 'important');
      th.style.setProperty('width', width, 'important');
      th.style.setProperty('min-width', width, 'important');
      th.style.setProperty('max-width', width, 'important');
    };

    if (!mainHot) {
      mainHot = new Handsontable(container, {
        data: data,
        colHeaders: columns.map(function (column) { return column.title; }),
        columns: columns,
        colWidths: mainColumnWidths,
        rowHeaders: false,
        stretchH: 'none',
        width: '100%',
        height: getMainHeight(),
        manualColumnResize: true,
        manualRowResize: false,
        fillHandle: false,
        readOnly: true,
        contextMenu: false,
        dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
        filters: true,
        afterGetColHeader: syncHeaderWidth,
        licenseKey: 'non-commercial-and-evaluation',
        cells: function (row, col) {
          var cellProperties = {};
          var rowData = getSourceRow(this.instance, row);
          cellProperties.className = getRowStateClass(rowData);
          return cellProperties;
        },
      });
    } else {
      mainHot.updateSettings({
        data: data,
        colHeaders: columns.map(function (column) { return column.title; }),
        columns: columns,
        colWidths: mainColumnWidths,
        afterGetColHeader: syncHeaderWidth,
        height: getMainHeight(),
      });
      mainHot.loadData(data);
      mainHot.render();
    }

    window.table = mainHot;

    import('/js/design-system/ht-empty-state.js').then(function (mod) {
      if (!mainHot || mainHot.isDestroyed) { return; }
      mod.attachHtEmptyState(mainHot, {
        titulo: 'No hay paquetes de contratación',
        cuerpo: 'Los paquetes se arman desde el maestro de insumos, en la pestaña «Paquetes» del plan de compras.',
      });
    });
  }

  function loadDefinirData(done) {
    var db = getDb();
    var semana = getMaxSemana();
    if (!db || !semana) {
      definirRows = [];
      renderDefinirGrid();
      if (typeof done === 'function') done();
      return;
    }

    $.ajax({
      method: 'POST',
      url: '/api/pdc/list?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana) + '&definirContratos=1',
      dataType: 'json',
    }).done(function (response) {
      definirRows = response && Array.isArray(response.data) ? response.data : [];
      renderDefinirGrid();
      if (typeof done === 'function') done();
    }).fail(function () {
      definirRows = [];
      renderDefinirGrid();
      if (typeof done === 'function') done();
    });
  }

  function validarNumeroSubcontratos(value, callback) {
    var cantidad = Number(value);
    callback(String(value).trim() !== '' && Number.isInteger(cantidad) && cantidad >= 1);
  }

  function renderDefinirGrid() {
    renderDefinirHeight();
    $('#btn_guardar_definirContratos').prop('disabled', !canEdit());
    var container = document.getElementById(DEFINIR_HOT_ID);
    if (!container) return;

    var definirColumnWidths = getFittedColumnWidths(container, DEFINIR_COLUMN_WIDTHS, DEFINIR_COLUMN_MIN_WIDTHS);
    var syncDefinirHeaderWidth = function (column, th) {
      if (column < 0 || !th || !definirColumnWidths[column]) return;
      var width = definirColumnWidths[column] + 'px';
      th.style.setProperty('box-sizing', 'border-box', 'important');
      th.style.setProperty('width', width, 'important');
      th.style.setProperty('min-width', width, 'important');
      th.style.setProperty('max-width', width, 'important');
    };
    var columns = applyColumnWidths([
      { title: 'Modalidad de contratacion', data: 'tipoPaquete', readOnly: true, width: 180, renderer: function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.textContent = displayModality(value);
      } },
      { title: 'Paquete de contratacion', data: 'paqueteContratacion', readOnly: true, width: 260 },
      { title: 'Cantidad de contratos', data: 'numeroSubcontratos', type: 'numeric', numericFormat: { pattern: '0' }, width: 150, readOnly: !canEdit(), validator: validarNumeroSubcontratos, allowInvalid: false },
    ], definirColumnWidths);

    if (!definirHot) {
      definirHot = new Handsontable(container, {
        data: definirRows,
        colHeaders: columns.map(function (column) { return column.title; }),
        columns: columns,
        colWidths: definirColumnWidths,
        rowHeaders: false,
        stretchH: 'none',
        width: '100%',
        height: getModalHeight(),
        manualColumnResize: true,
        manualRowResize: false,
        fillHandle: false,
        contextMenu: false,
        afterGetColHeader: syncDefinirHeaderWidth,
        licenseKey: 'non-commercial-and-evaluation',
      });
    } else {
      definirHot.updateSettings({
        data: definirRows,
        colHeaders: columns.map(function (column) { return column.title; }),
        columns: columns,
        colWidths: definirColumnWidths,
        afterGetColHeader: syncDefinirHeaderWidth,
        height: getModalHeight(),
      });
      definirHot.loadData(definirRows);
      definirHot.render();
    }

    window.definirHot = definirHot;
  }

  function refreshMain() {
    loadMainData();
  }

  function setEditModalMode(editable) {
    $('#formularioContrato').find('input:not([type="hidden"]), select, textarea')
      .prop('disabled', !editable);
    $('#btn_guardar_pdc').prop('disabled', !editable).toggle(editable);
    $('#modalContrato').attr('data-mode', editable ? 'edit' : 'view');
  }

  function openEditModal(row) {
    if (!row || Number(row.titulo) !== 0) return;

    $('#nombrePaqueteContratacion, #tipoPaquete, #tipoProveedorAdjudicado, #idProveedorExistente, #nitAdjudicado, #subcontratistaAdjudicado, #emailAdjudicado, #alcanceAdjudicado, #numeroContrato, #aplicaPolizas, #fechaVencimientoPolizas, #valorPresupuesto, #valorPrimeraNegociacion, #valorAdjudicado, #valorAnticipo, #valorReclamado, #valorDevoluciones, #actividadesDelContrato, #fechaInicioContrato, #fechaActual, #diasElaboracionPliegos, #diasEntregaPliegos, #diasReciboPropuestas, #diasCuadrosComparativos, #diasLegalizacionContrato, #diasFabricacion, #diasInsumosObra, #fechaRealElaboracionPliegos, #fechaRealEntregaPliegos, #fechaRealReciboPropuestas, #fechaRealCuadrosComparativos, #fechaRealLegalizacionContrato, #fechaRealFabricacion, #fechaRealInsumosObra, #fechaRealInicioProyectadaContrato, #observacionesContrato').val('');
    $('#valorReclamado, #valorDevoluciones').val(0);
    if (typeof window.actualizarEstadoProveedorBloqueado === 'function') {
      window.actualizarEstadoProveedorBloqueado(false);
    }

    $('#Id').val(row.consecutivo);
    $('#opcion').val('modificar');
    $('#nombrePaqueteContratacion').val(row.paqueteContratacion || '');
    $('#tipoPaquete').val(row.tipoPaquete || '');
    $('#tipoProveedorAdjudicado').val(row.tipoPaquete || '');
    $('#idProveedorExistente').val(row.idProveedorAdjudicado || '');
    $('#numeroContrato').val(row.numeroContrato || '');
    $('#aplicaPolizas').val(row.aplicaPolizas || 0).change();
    $('#fechaVencimientoPolizas').val(row.fechaVencimientoPolizas || '');
    $('#valorPresupuesto').val(row.valorPresupuesto || '');
    $('#valorPrimeraNegociacion').val(row.valorPrimeraNegociacion || '');
    $('#valorAdjudicado').val(row.valorAdjudicado || '').change();
    $('#valorAnticipo').val(row.valorAnticipo || '');
    $('#valorReclamado').val(row.valorReclamado || '');
    $('#valorDevoluciones').val(row.valorDevoluciones || '').change();
    $('#actividadesDelContrato').val(row.contratos || '');
    $('#fechaInicioContrato').val(row.fechaInicio || '');
    $('#fechaActual').val($('#Fecha_Inicio_SemYMD').val() || '');
    $('#diasElaboracionPliegos').val(row.diasElaboracionPliegos || '');
    $('#diasEntregaPliegos').val(row.diasEntregaPliegos || '');
    $('#diasReciboPropuestas').val(row.diasReciboPropuestas || '');
    $('#diasCuadrosComparativos').val(row.diasCuadrosComparativos || '');
    $('#diasLegalizacionContrato').val(row.diasLegalizacionContrato || '');
    $('#diasFabricacion').val(row.diasFabricacion || '');
    $('#diasInsumosObra').val(row.diasInsumosObra || '');
    $('#fechaRealElaboracionPliegos').val(row.fechaRealElaboracionPliegos || '');
    $('#fechaRealEntregaPliegos').val(row.fechaRealEntregaPliegos || '');
    $('#fechaRealReciboPropuestas').val(row.fechaRealReciboPropuestas || '');
    $('#fechaRealCuadrosComparativos').val(row.fechaRealCuadrosComparativos || '');
    $('#fechaRealLegalizacionContrato').val(row.fechaRealLegalizacionContrato || '');
    $('#fechaRealFabricacion').val(row.fechaRealFabricacion || '');
    $('#fechaRealInsumosObra').val(row.fechaRealInsumosObra || '');
    $('#fechaRealInicioProyectadaContrato').val(row.fechaRealInicio || '');
    $('#observacionesContrato').val(row.observacionesContrato || '');

    if (typeof window.formatCurrency === 'function') {
      window.formatCurrency($('#valorPresupuesto'));
      window.formatCurrency($('#valorPrimeraNegociacion'));
      window.formatCurrency($('#valorAdjudicado'));
      window.formatCurrency($('#valorAnticipo'));
      window.formatCurrency($('#valorReclamado'));
      window.formatCurrency($('#valorDevoluciones'));
    }
    $('#valorAdjudicado').keyup();
    $('#valorDevoluciones').keyup();

    if (typeof window.calcularProcesoContratacionTeorico === 'function') {
      window.calcularProcesoContratacionTeorico('');
    }

    if (row.fechaRealLegalizacionContrato || row.fechaRealFabricacion || row.fechaRealInsumosObra || row.fechaRealInicio) {
      if (row.idProveedorAdjudicado) {
        if (typeof window.verificarProveedor === 'function') {
          window.verificarProveedor('idProveedorExistente');
        }
      } else if (typeof window.verificarProveedor === 'function') {
        window.verificarProveedor('actividadesDelContrato');
      }
    } else if (document.getElementById('nitAdjudicado')) {
      document.getElementById('nitAdjudicado').value = '';
      if (typeof window.actualizarEstadoProveedorBloqueado === 'function') {
        window.actualizarEstadoProveedorBloqueado(false);
      }
    }

    var editable = canEdit();
    setEditModalMode(editable);
    if (editable && typeof window.guardar_modificar === 'function') {
      window.guardar_modificar();
    } else {
      $('#btn_guardar_pdc').off('click.pdcSave');
    }

    $('#modal-body-texto-Contrato').text(
      (editable ? 'Editar' : 'Consultar') + ' ' + (row.paqueteContratacion || '') +
      ' · ' + displayModality(row.tipoPaquete || '')
    );
    $('#modalContrato').modal('show');
    $('#btn_cancelar_editar').off('click.pdcHot').on('click.pdcHot', function () {
      $('#Id').val('');
      $('#btn_guardar_pdc').off('click.pdcSave');
    });
  }

  function openDeleteModal(row) {
    if (!row || Number(row.subcontratoPaquete || 0) <= 1) return;
    $('#Id').val(row.consecutivo || '');
    $('#opcion').val('eliminar_actividad_pdc');
    $('#nombrePaqueteContratacion').val(row.paqueteContratacion || '');
    $('#modalEliminar').data('pdcSubcontratoPaquete', Number(row.subcontratoPaquete));
    $('#modal-body-texto-eliminar').text(
      '¿Desea eliminar el subcontrato ' + (row.paqueteContratacion || '') +
      ' (Subcontrato ' + (row.subcontratoPaquete || '') + ') del proyecto?'
    );
    $('#modalEliminar').modal('show');
  }

  function bindDeleteHandler() {
    if (deleteBound) return;
    deleteBound = true;
    $('#eliminar-usuario').off('click.pdcHot').on('click.pdcHot', function () {
      if (Number($('#modalEliminar').data('pdcSubcontratoPaquete') || 0) <= 1) {
        $('#modalEliminar').modal('hide');
        showNotice('error', 'Solo se pueden eliminar subcontratos adicionales.');
        return;
      }
      var db = getDb();
      var semana = getSemana();
      var id = $('#Id').val();
      var paqueteContratacion = $('#nombrePaqueteContratacion').val();

      $.ajax({
        method: 'POST',
        url: '/api/pdc/save',
        contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
        dataType: 'json',
        data: {
          Id: id,
          opcion: 'eliminar_actividad_pdc',
          paqueteContratacion: paqueteContratacion,
          db: db,
          semana: semana,
        },
      }).done(function (info) {
        $('#modalEliminar').modal('hide');
        if (info && info.respuesta === 'BIEN') {
          showNotice('success', 'Se eliminaron los cambios correctamente.');
          refreshMain();
        } else {
          showNotice('error', 'No se pudo eliminar el registro.');
        }
      }).fail(function () {
        showNotice('error', 'No se pudo eliminar el registro.');
      });
    });
  }

  function filterPDC(state, event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    if (activeFilters.length === 1 && activeFilters[0] === state) {
      activeFilters = [];
    } else {
      activeFilters = [state];
    }

    updateLegendState();
    renderMainGrid();
  }

  function toggleSoloAlertas() {
    soloAlertas = !soloAlertas;
    $('#btn_soloAlertas').toggleClass('is-active', soloAlertas);
    renderMainGrid();
  }

  function clearPdcFilters() {
    activeFilters = [];
    soloAlertas = false;
    $('#btn_soloAlertas').removeClass('is-active');
    if (mainHot) {
      var filters = mainHot.getPlugin('filters');
      filters.clearConditions();
      filters.filter();
    }
    updateLegendState();
    renderMainGrid();
  }

  function openDefinirContratos() {
    var $modal = $('#modalDefinirContratos');
    if (!$modal.length) return;
    $modal.addClass('show').show().attr('aria-hidden', 'false').attr('aria-modal', 'true');
    $('body').addClass('modal-open');
    if (!$('[data-pdc-definir-backdrop]').length) {
      $('body').append('<div class="modal-backdrop fade show" data-pdc-definir-backdrop></div>');
    }
    loadDefinirData(function () {
      renderDefinirHeight();
      definirHot.updateSettings({ height: getModalHeight() });
      definirHot.render();
    });
  }

  function closeDefinirContratos() {
    var $modal = $('#modalDefinirContratos');
    $modal.removeClass('show').hide().attr('aria-hidden', 'true').removeAttr('aria-modal');
    $('[data-pdc-definir-backdrop]').remove();
    if (!$('.modal.show').length) {
      $('body').removeClass('modal-open').css('padding-right', '');
    }
  }

  function bindDefinirContratosHandlers() {
    $('#btn_guardar_definirContratos').off('click.pdcHot').on('click.pdcHot', function () {
      saveDefinirContratos();
    });
    $('#btn_cancelar_definirContratos').off('click.pdcHot').on('click.pdcHot', closeDefinirContratos);
  }

  function saveDefinirContratos() {
    var rows = definirHot ? definirHot.getSourceData() : definirRows;
    var payload = [];

    if (!canEdit()) {
      showNotice('error', 'No tienes permiso para modificar la distribución de contratos.');
      return;
    }

    for (var index = 0; index < rows.length; index += 1) {
      var row = rows[index];
      var cantidad = Number(row.numeroSubcontratos);
      if (String(row.numeroSubcontratos).trim() === '' || !Number.isInteger(cantidad) || cantidad < 1) {
        if (definirHot) {
          definirHot.selectCell(index, 2);
          definirHot.listen();
        }
        showNotice('error', 'La cantidad de contratos debe ser un entero mayor o igual a 1.');
        return;
      }
      payload.push({
        consecutivo: row.consecutivo,
        numeroSubcontratos: cantidad,
      });
    }

    var db = getDb();
    var semana = getSemana();
    $.ajax({
      method: 'POST',
      url: '/api/pdc/save',
      contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      dataType: 'json',
      data: {
        numeroSubcontratos: JSON.stringify({ numeroSubcontratos: payload }),
        opcion: 'guardar_DefinirContratos',
        semana: semana,
        db: db,
      },
    }).done(function (info) {
      if (info === 'sinModificaciones' || info === 'conModificaciones') {
        closeDefinirContratos();
        refreshMain();
      } else {
        showNotice('error', info && info.mensaje ? info.mensaje : 'No se pudo guardar la distribución de contratos.');
      }
    }).fail(function (xhr) {
      var message = xhr && xhr.responseJSON && xhr.responseJSON.mensaje;
      showNotice('error', message || 'No se pudo guardar la distribución de contratos.');
    });
  }

  function start() {
    if (initialized) {
      renderToolbar();
      renderMainGrid();
      return;
    }

    initialized = true;
    renderToolbar();
    bindDeleteHandler();
    bindDefinirContratosHandlers();
    loadMainData();
    $(window).off('resize.pdcHot orientationchange.pdcHot').on('resize.pdcHot orientationchange.pdcHot', function () {
      renderMainGrid();
      if (definirHot) {
        renderDefinirGrid();
      }
    });
  }

  window.PdcHotModule = {
    start: start,
    reload: refreshMain,
    clearFilters: clearPdcFilters,
    openDefinirContratos: openDefinirContratos,
    closeDefinirContratos: closeDefinirContratos,
    saveDefinirContratos: saveDefinirContratos,
  };

  window.displayPDCStatus = displayStatus;

  window.listar = start;
  window.recargarTabla = refreshMain;
  window.filterPDC = filterPDC;
  window.toggleSoloAlertas = toggleSoloAlertas;
  window.clearPdcFilters = clearPdcFilters;
  window.obtener_data_definirContratos = openDefinirContratos;
  window.guardar_DefinirContratos = saveDefinirContratos;
  window.eliminar = bindDeleteHandler;
  window.obtener_data_editar = function () {};
  window.obtener_id_eliminar = function () {};
})(window, window.jQuery);
