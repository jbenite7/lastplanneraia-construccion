(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var pendingViewportState = null;
  var pendingTabSelection = null;
  // Exponer flag para que el editor Select2 pueda señalar navegación pendiente
  window.__piPendingNav = false;
  var masterData = [];
  var activeFilters = [];
  var sharedSelectionIndex = {};

  var options = window.PI_HOT_OPTIONS || {};
  var subcontratistas = Array.isArray(options.subcontratistas) ? options.subcontratistas.slice() : [];
  var profesionales = Array.isArray(options.profesionales) ? options.profesionales.slice() : [];

  var PI_CREATE_SUB = '➕ Crear Subcontratista...';
  var PI_CREATE_PROF = '➕ Crear Profesional...';
  subcontratistas.push(PI_CREATE_SUB);
  profesionales.push(PI_CREATE_PROF);

  var editableProps = {
    D_y_E: true,
    Materiales: true,
    MdeO: true,
    Equipos: true,
    Predecesora: true,
    Pdto_Cons: true,
    Modelo: true,
    Sub_Contratista: true,
    Responsable_AIA: true,
    Observaciones: true,
  };

  var trackedStates = [
    'blocked-overdue-critical',
    'blocked-overdue',
    'blocked-due',
    'alert-1-week',
    'alert-2-3-weeks',
    'alert-4-6-weeks',
    'execution-blocked',
    'liberated-control',
  ];

  var restrictedOptions = ['', '0%', '33%', '66%', '100%', 'N/A'];
  var halfRestrictedOptions = ['', '0%', '50%', '100%', 'N/A'];
  var restrictionProps = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
  var restrictionTypeLabels = {
    D_y_E: 'Diseños y Especif.',
    Materiales: 'Materiales',
    MdeO: 'Mano de Obra',
    Equipos: 'Equipos',
    Predecesora: 'Predecesora',
    Pdto_Cons: 'Proced. Constructivo',
    Modelo: 'Modelación BIM',
  };
  // Popover content for restriction column headers (ported from legacy view)
  var popoverTitles = {
    D_y_E: 'Restricciones de Diseños y Especificaciones',
    Materiales: 'Restricciones de Materiales',
    MdeO: 'Restricciones de Mano de Obra',
    Equipos: 'Restricciones de Equipos',
    Predecesora: 'Restricciones de Actividades Predecesoras',
    Pdto_Cons: 'Restricciones de Procedimiento Constructivo',
    Modelo: 'Restricciones de Modelación BIM',
  };
  var popoverContent = {
    D_y_E: '<ul class="pl-3 mb-0"><li><b>0%:</b> No están los diseños para construcción.</li><li><b>33%:</b> Diseños entregados pero no revisados por dirección/residentes.</li><li><b>66%:</b> Diseños con visto bueno de dirección y residentes.</li><li><b>100%:</b> Diseños aprobados entregados a contratistas/maestros.</li></ul>',
    Materiales: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de aprovisionamiento.</li><li><b>33%:</b> Al día en plan de compras.</li><li><b>66%:</b> Al día en plan de aprovisionamiento.</li><li><b>100%:</b> Materiales disponibles en el proyecto.</li></ul>',
    MdeO: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de mano de obra.</li><li><b>33%:</b> Contratos existentes, recurso no ubicado.</li><li><b>66%:</b> Documentación y requisitos legales listos.</li><li><b>100%:</b> Personal ya está en el proyecto.</li></ul>',
    Equipos: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de equipos.</li><li><b>33%:</b> Al día en plan de compras.</li><li><b>66%:</b> Al día en plan de aprovisionamiento.</li><li><b>100%:</b> Equipos disponibles en el proyecto.</li></ul>',
    Predecesora: '<ul class="pl-3 mb-0"><li><b>0%:</b> Predecesoras no han iniciado o están atrasadas.</li><li><b>50%:</b> Predecesoras con rendimiento igual o superior al programa.</li><li><b>100%:</b> Predecesoras ya terminadas.</li></ul>',
    Pdto_Cons: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existe procedimiento constructivo.</li><li><b>50%:</b> Existe pero no se ha divulgado.</li><li><b>100%:</b> Divulgado y aprobado por el director.</li></ul>',
    Modelo: '<ul class="pl-3 mb-0"><li><b>0%:</b> No hay modelos en el proyecto.</li><li><b>50%:</b> Modelos existentes pero no coordinados.</li><li><b>100%:</b> Modelos coordinados para todas las disciplinas.</li><li><b>N/A:</b> La tarea no aplica para ser modelada.</li></ul>',
  };

  // Map colHeaders index to restriction prop for tooltip injection
  var headerIndexToRestrictionProp = {
    7: 'D_y_E',
    8: 'Materiales',
    9: 'MdeO',
    10: 'Equipos',
    11: 'Predecesora',
    12: 'Pdto_Cons',
    13: 'Modelo',
  };

  var sharedRestrictionValueOptions = {
    long: ['0%', '33%', '66%', '100%', 'N/A'],
    half: ['0%', '50%', '100%', 'N/A'],
  };

  var dropdownProps = {
    D_y_E: true,
    Materiales: true,
    MdeO: true,
    Equipos: true,
    Predecesora: true,
    Pdto_Cons: true,
    Modelo: true,
    Sub_Contratista: true,
    Responsable_AIA: true,
  };

  var stateLabels = {
    'blocked-overdue-critical': 'Bloqueada Vencida (Crítica)',
    'blocked-overdue': 'Bloqueada Vencida',
    'blocked-due': 'Debe Iniciar (Con Restricciones)',
    'alert-1-week': 'Alerta 1 Semana',
    'alert-2-3-weeks': 'Alerta 2-3 Semanas',
    'alert-4-6-weeks': 'Alerta 4-6 Semanas',
    'execution-blocked': 'En Ejecución (Con Restricciones)',
    'liberated-control': 'Liberada / Control',
    neutral: 'Control',
    header: 'Capítulo',
  };

  var columnMinWidths = [44, 54, 150, 130, 130, 60, 72, 74, 74, 74, 74, 82, 94, 88, 92, 180];
  var columnFloorWidths = [36, 44, 120, 100, 100, 52, 64, 64, 64, 64, 64, 70, 80, 76, 78, 130];
  var columnMaxWidths = [90, 70, 460, 240, 240, 110, 110, 120, 120, 120, 120, 130, 148, 136, 136, 380];
  var columnShrinkPriority = [15, 2, 3, 4, 14, 13, 12, 11, 9, 7, 8, 10, 6, 5, 1, 0];

  function getDb() {
    return $('#baseDatos_PHP').val() || $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_PHP').val() || $('#permiso').val() || '').trim().toUpperCase();
    return ({ P: 'D', U: 'V' }[permiso] || permiso);
  }

  function isDirectorRole(permiso) {
    return permiso === 'A' || permiso === 'D';
  }

  function isIntermediaEditorRole(permiso) {
    return permiso === 'A' || permiso === 'D' || permiso === 'R' || permiso === 'DCV';
  }

  function getMaxSemana() {
    var value = parseInt($('#Max_Semana').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function isUserAllowedToEdit() {
    var permiso = getPermiso();
    var semana = parseInt(getSemana(), 10);
    var maxSemana = getMaxSemana();

    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return isDirectorRole(permiso);
    }

    return isIntermediaEditorRole(permiso);
  }

  function toNumber(value, fallback) {
    if (window.PIStateMachine && typeof window.PIStateMachine.toNumber === 'function') {
      return window.PIStateMachine.toNumber(value, fallback);
    }

    if (value === null || value === undefined || value === '') {
      return fallback;
    }

    var normalized = String(value).trim().replace(/\s+/g, '');
    if (normalized === '' || normalized.toLowerCase() === 'null') {
      return fallback;
    }

    var commaPos = normalized.lastIndexOf(',');
    var dotPos = normalized.lastIndexOf('.');
    if (commaPos > -1 && dotPos > -1) {
      if (commaPos > dotPos) {
        normalized = normalized.replace(/\./g, '').replace(',', '.');
      } else {
        normalized = normalized.replace(/,/g, '');
      }
    } else if (commaPos > -1) {
      normalized = normalized.replace(',', '.');
    }

    var parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function formatPercent(value) {
    if (String(value || '').toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(value);
    if (ratio === null) {
      return '';
    }

    return (ratio * 100).toFixed(1).replace('.', ',') + '%';
  }

  function normalizePercentRatio(value) {
    if (value === null || value === undefined || value === '') {
      return null;
    }

    var raw = String(value).trim();
    if (!raw || raw.toUpperCase() === 'N/A') {
      return null;
    }

    var numeric = toNumber(raw, null);
    if (numeric === null) {
      return null;
    }

    if (raw.indexOf('%') > -1) {
      numeric = numeric / 100;
    }

    while (numeric > 1 && numeric <= 10000) {
      numeric = numeric / 100;
    }

    if (numeric < 0) {
      numeric = 0;
    }

    if (numeric > 1) {
      numeric = 1;
    }

    return Math.round((numeric + Number.EPSILON) * 10000) / 10000;
  }

  function getAllowedRestrictionRatios(prop) {
    if (prop === 'Predecesora' || prop === 'Pdto_Cons' || prop === 'Modelo') {
      return [0, 0.5, 1];
    }

    return [0, 0.33, 0.66, 1];
  }

  function findNearestAllowedRatio(prop, ratio) {
    var allowed = getAllowedRestrictionRatios(prop);
    var nearest = allowed[0];
    var minDiff = Math.abs(allowed[0] - ratio);

    for (var i = 1; i < allowed.length; i++) {
      var diff = Math.abs(allowed[i] - ratio);
      if (diff < minDiff) {
        minDiff = diff;
        nearest = allowed[i];
      }
    }

    return nearest;
  }

  function restrictionRatioToDisplay(ratio) {
    var percent = Math.round((ratio * 100) + Number.EPSILON);
    return String(percent) + '%';
  }

  function restrictionRatioToPayload(ratio) {
    if (ratio === 0 || ratio === 1) {
      return String(ratio);
    }

    return ratio.toString();
  }

  function normalizeRestrictionForPayload(prop, value) {
    var text = String(value === null || value === undefined ? '' : value).trim();
    if (text === '') {
      return '';
    }

    if (text.toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(text);
    if (ratio === null) {
      return null;
    }

    var nearest = findNearestAllowedRatio(prop, ratio);
    return restrictionRatioToPayload(nearest);
  }

  function calculateRestrictionStateRatio(row) {
    var total = 0;
    var count = 0;

    for (var i = 0; i < restrictionProps.length; i++) {
      var prop = restrictionProps[i];
      var payloadValue = normalizeRestrictionForPayload(prop, row[prop]);

      if (!payloadValue || payloadValue === 'N/A') {
        continue;
      }

      var numeric = toNumber(payloadValue, null);
      if (numeric === null) {
        continue;
      }

      total += numeric;
      count += 1;
    }

    if (count === 0) {
      return 1;
    }

    var ratio = total / count;
    if (ratio < 0) {
      ratio = 0;
    }
    if (ratio > 1) {
      ratio = 1;
    }

    return Math.round((ratio + Number.EPSILON) * 100000) / 100000;
  }

  function recalculateRestrictionStateForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var physicalRow = hot.toPhysicalRow(visualRow);
    if (!Number.isInteger(physicalRow) || physicalRow < 0) {
      return;
    }

    var rowData = hot.getSourceDataAtRow(physicalRow);
    if (!rowData) {
      return;
    }

    var ratio = calculateRestrictionStateRatio(rowData);
    rowData.Estado_Restricciones = ratio;
    hot.setDataAtRowProp(visualRow, 'Estado_Restricciones', ratio, 'internal-update');
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getActividadPlainText(value) {
    var raw = String(value === null || value === undefined ? '' : value);
    if (!raw) {
      return '';
    }

    var container = document.createElement('div');
    container.innerHTML = raw;
    return String(container.textContent || container.innerText || '').trim();
  }

  function sanitizeActividadHtml(value) {
    var raw = String(value === null || value === undefined ? '' : value);
    if (!raw) {
      return '';
    }

    var container = document.createElement('div');
    container.innerHTML = raw;

    var allowed = {
      B: true,
      STRONG: true,
      SMALL: true,
      BR: true,
    };

    function walkNode(node) {
      if (!node) {
        return '';
      }

      if (node.nodeType === 3) {
        return escapeHtml(node.nodeValue || '');
      }

      if (node.nodeType !== 1) {
        return '';
      }

      var tag = String(node.tagName || '').toUpperCase();
      if (tag === 'BR') {
        return '<br>';
      }

      var children = '';
      for (var i = 0; i < node.childNodes.length; i++) {
        children += walkNode(node.childNodes[i]);
      }

      if (allowed[tag]) {
        var safeTag = tag.toLowerCase();
        return '<' + safeTag + '>' + children + '</' + safeTag + '>';
      }

      return children;
    }

    var output = '';
    for (var j = 0; j < container.childNodes.length; j++) {
      output += walkNode(container.childNodes[j]);
    }

    return output;
  }

  function getState(row) {
    if (window.PIStateMachine && typeof window.PIStateMachine.getState === 'function') {
      return window.PIStateMachine.getState(row || {});
    }
    return 'neutral';
  }

  function getStateLabel(row) {
    var state = getState(row);
    return stateLabels[state] || 'Control';
  }

  function isHalfRestrictionType(restrictionType) {
    return restrictionType === 'Predecesora' || restrictionType === 'Pdto_Cons' || restrictionType === 'Modelo';
  }

  function getSharedValueOptionsForType(restrictionType) {
    return isHalfRestrictionType(restrictionType) ? sharedRestrictionValueOptions.half : sharedRestrictionValueOptions.long;
  }

  function getRowActivityId(row) {
    if (!row) {
      return '';
    }

    var candidate = row.Consecutivo_en_Programa;
    if (candidate === null || candidate === undefined || candidate === '') {
      candidate = row.Id;
    }

    var id = String(candidate === null || candidate === undefined ? '' : candidate).trim();
    return /^\d+$/.test(id) ? id : '';
  }

  function normalizeSharedSelectionValue(value) {
    return value === true || value === 1 || value === '1' || value === 'true' || value === 'TRUE';
  }

  function rebuildSharedSelectionIndex() {
    var nextIndex = {};

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      var id = getRowActivityId(row);
      var selected = normalizeSharedSelectionValue(row.__shared_selected);
      row.__shared_selected = selected;

      if (id && selected) {
        nextIndex[id] = true;
      }
    }

    sharedSelectionIndex = nextIndex;
  }

  function getMarkedActivityIds() {
    var ids = [];

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      if (!normalizeSharedSelectionValue(row.__shared_selected)) {
        continue;
      }

      var id = getRowActivityId(row);
      if (!id || ids.indexOf(id) > -1) {
        continue;
      }

      ids.push(id);
    }

    return ids;
  }

  function getVisibleActivityIds() {
    var ids = [];
    var rows = [];

    if (hot && typeof hot.getSourceData === 'function') {
      rows = hot.getSourceData() || [];
    } else {
      for (var i = 0; i < masterData.length; i++) {
        if (rowMatchesFilters(masterData[i])) {
          rows.push(masterData[i]);
        }
      }
    }

    for (var rowIndex = 0; rowIndex < rows.length; rowIndex++) {
      var id = getRowActivityId(rows[rowIndex]);
      if (!id || ids.indexOf(id) > -1) {
        continue;
      }
      ids.push(id);
    }

    return ids;
  }

  function updateSharedSelectionCountIndicator() {
    var selectedCount = getMarkedActivityIds().length;
    var $indicator = $('#shared-selection-count');

    if (!$indicator.length) {
      return;
    }

    $indicator.text(selectedCount + ' selec.');
    $indicator.toggleClass('badge-secondary', selectedCount === 0);
    $indicator.toggleClass('badge-primary', selectedCount > 0);

    var $info = $('#piSharedSelectionInfo');
    if ($info.length) {
      $info.text('Marcadas: ' + selectedCount + ' | Visibles: ' + getVisibleActivityIds().length);
    }
  }

  function syncSharedSelectionFromRow(rowData, selected) {
    if (!rowData) {
      return;
    }

    var isSelected = normalizeSharedSelectionValue(selected);
    rowData.__shared_selected = isSelected;

    var id = getRowActivityId(rowData);
    if (!id) {
      return;
    }

    if (isSelected) {
      sharedSelectionIndex[id] = true;
    } else {
      delete sharedSelectionIndex[id];
    }
  }

  function updateSharedSelectionFromVisualRow(visualRow, selected) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var physicalRow = hot.toPhysicalRow(visualRow);
    if (!Number.isInteger(physicalRow) || physicalRow < 0) {
      return;
    }

    var rowData = hot.getSourceDataAtRow(physicalRow);
    if (!rowData || getState(rowData) === 'header') {
      return;
    }

    syncSharedSelectionFromRow(rowData, selected);
    updateSharedSelectionCountIndicator();
  }

  function applySharedSelectionToIds(activityIds, selected) {
    var ids = parseActivityIdsInput(activityIds);
    if (ids.length === 0) {
      return 0;
    }

    var idSet = {};
    for (var i = 0; i < ids.length; i++) {
      idSet[ids[i]] = true;
    }

    var isSelected = Boolean(selected);
    var changed = 0;

    for (var rowIndex = 0; rowIndex < masterData.length; rowIndex++) {
      var row = masterData[rowIndex] || {};
      var id = getRowActivityId(row);
      if (!id || !idSet[id] || getState(row) === 'header') {
        continue;
      }

      if (normalizeSharedSelectionValue(row.__shared_selected) === isSelected) {
        continue;
      }

      syncSharedSelectionFromRow(row, isSelected);
      changed += 1;
    }

    if (hot) {
      hot.render();
    }

    updateSharedSelectionCountIndicator();
    return changed;
  }

  function selectVisibleRowsForSharedConstraint() {
    var visibleIds = getVisibleActivityIds();
    if (visibleIds.length === 0) {
      showFeedback('error', 'No hay filas visibles para seleccionar.');
      return;
    }

    var changed = applySharedSelectionToIds(visibleIds, true);
    showFeedback('success', 'Visibles marcadas: ' + visibleIds.length + ' (nuevas: ' + changed + ')');
  }

  function clearSharedSelection() {
    var hadSelection = false;

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      if (!normalizeSharedSelectionValue(row.__shared_selected)) {
        continue;
      }

      row.__shared_selected = false;
      hadSelection = true;
    }

    if (hadSelection) {
      sharedSelectionIndex = {};
      if (hot) {
        hot.render();
      }
    }

    updateSharedSelectionCountIndicator();
    showFeedback('success', hadSelection ? 'Seleccion de lote limpiada' : 'No habia seleccion activa');
  }

  function normalizeRestrictionValue(prop, value) {
    var text = String(value === null || value === undefined ? '' : value).trim();
    if (text === '') {
      return '';
    }

    if (text.toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(text);
    if (ratio === null) {
      return null;
    }

    var nearest = findNearestAllowedRatio(prop, ratio);
    return restrictionRatioToDisplay(nearest);
  }

  function parseActivityIdsInput(rawValue) {
    var raw = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();
    if (!raw) {
      return [];
    }

    var tokens = raw.split(/[\s,;\n\r]+/);
    var ids = [];

    for (var i = 0; i < tokens.length; i++) {
      var token = String(tokens[i] || '').trim();
      if (!token || !/^\d+$/.test(token)) {
        continue;
      }

      if (ids.indexOf(token) === -1) {
        ids.push(token);
      }
    }

    return ids;
  }

  function collectHighlightedActivityIds() {
    if (!hot || typeof hot.getSelectedRange !== 'function') {
      return [];
    }

    var ranges = hot.getSelectedRange() || [];
    var ids = [];

    for (var rangeIndex = 0; rangeIndex < ranges.length; rangeIndex++) {
      var range = ranges[rangeIndex];
      if (!range || !range.from || !range.to) {
        continue;
      }

      var fromRow = Math.min(range.from.row, range.to.row);
      var toRow = Math.max(range.from.row, range.to.row);

      for (var visualRow = fromRow; visualRow <= toRow; visualRow++) {
        var physicalRow = hot.toPhysicalRow(visualRow);
        if (!Number.isInteger(physicalRow) || physicalRow < 0) {
          continue;
        }

        var rowData = hot.getSourceDataAtRow(physicalRow);
        if (!rowData || getState(rowData) === 'header') {
          continue;
        }

        var id = getRowActivityId(rowData);
        if (!id || ids.indexOf(id) > -1) {
          continue;
        }

        ids.push(id);
      }
    }

    return ids;
  }

  function collectSelectedActivityIds() {
    var markedIds = getMarkedActivityIds();
    var highlightedIds = collectHighlightedActivityIds();

    if (markedIds.length === 0) {
      return highlightedIds;
    }

    if (highlightedIds.length === 0) {
      return markedIds;
    }

    var merged = markedIds.slice();
    for (var i = 0; i < highlightedIds.length; i++) {
      if (merged.indexOf(highlightedIds[i]) === -1) {
        merged.push(highlightedIds[i]);
      }
    }

    return merged;
  }

  function setSharedValueOptionsForType(restrictionType, keepCurrent) {
    var $select = $('#piSharedRestrictionValue');
    if (!$select.length) {
      return;
    }

    var optionsForType = getSharedValueOptionsForType(restrictionType);
    var current = String($select.val() || '').trim();
    var selectedValue = optionsForType[0] || '';

    if (keepCurrent && optionsForType.indexOf(current) > -1) {
      selectedValue = current;
    }

    $select.empty();
    for (var i = 0; i < optionsForType.length; i++) {
      var value = optionsForType[i];
      $select.append($('<option></option>').val(value).text(value));
    }

    $select.val(selectedValue);
  }

  function getPreviewValueLabel(value) {
    var formatted = formatPercent(value);
    if (formatted) {
      return formatted;
    }

    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (!raw) {
      return '-';
    }

    return raw;
  }

  function getPreviewDeltaInfo(currentValue, targetValue) {
    var currentRatio = normalizePercentRatio(currentValue);
    var targetRatio = normalizePercentRatio(targetValue);

    if (currentRatio !== null && targetRatio !== null) {
      var diff = (targetRatio - currentRatio) * 100;
      if (Math.abs(diff) < 0.05) {
        return {
          label: '0,0 pp',
          className: 'pi-shared-delta-neutral',
        };
      }

      var rounded = Math.round((diff + Number.EPSILON) * 10) / 10;
      var sign = rounded > 0 ? '+' : '';

      return {
        label: sign + rounded.toFixed(1).replace('.', ',') + ' pp',
        className: rounded > 0 ? 'pi-shared-delta-up' : 'pi-shared-delta-down',
      };
    }

    if (String(getPreviewValueLabel(currentValue)) === String(getPreviewValueLabel(targetValue))) {
      return {
        label: 'Sin cambio',
        className: 'pi-shared-delta-neutral',
      };
    }

    return {
      label: 'Ajuste',
      className: 'pi-shared-delta-neutral',
    };
  }

  function renderSharedPreviewEmpty(message) {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    $preview.html('<div class="pi-shared-empty">' + escapeHtml(message || 'Seleccione filas y pulse "Preview".') + '</div>');
  }

  function renderSharedPreviewLoading() {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    $preview.html('<div class="pi-shared-loading"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Calculando preview de impacto...</div>');
  }

  function renderSharedPreview(content, options) {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    if (!content) {
      renderSharedPreviewEmpty('Seleccione filas y pulse "Preview".');
      return;
    }

    var opts = options || {};
    var restrictionType = String(content.restriction_type || opts.restriction_type || '').trim();
    var typeLabel = restrictionTypeLabels[restrictionType] || restrictionType || '-';
    var targetRaw = (opts.target_value !== undefined) ? opts.target_value : (content.target_value !== undefined ? content.target_value : ($('#piSharedRestrictionValue').val() || ''));
    var targetLabel = getPreviewValueLabel(targetRaw);
    var countTotal = Number(content.count_total || 0);
    var countFound = Number(content.count_found || 0);
    var countMissing = Number(content.count_missing || 0);
    var missingIds = Array.isArray(content.missing_ids) ? content.missing_ids : [];
    var previewRows = Array.isArray(content.preview) ? content.preview : [];
    var coverage = countTotal > 0 ? Math.round((countFound / countTotal) * 100) : 0;

    if (coverage < 0) {
      coverage = 0;
    }
    if (coverage > 100) {
      coverage = 100;
    }

    var html = '<div class="pi-shared-preview-shell">';
    html += '<div class="pi-shared-kpis">';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Tipo</span><span class="pi-shared-kpi-value">' + escapeHtml(typeLabel) + '</span></div>';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Objetivo</span><span class="pi-shared-kpi-value">' + escapeHtml(targetLabel) + '</span></div>';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Coincidencias</span><span class="pi-shared-kpi-value">' + escapeHtml(String(countFound) + ' / ' + String(countTotal)) + '</span></div>';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">No encontradas</span><span class="pi-shared-kpi-value">' + escapeHtml(String(countMissing)) + '</span></div>';
    html += '</div>';

    html += '<div class="pi-shared-coverage">';
    html += '<div class="pi-shared-coverage-track"><div class="pi-shared-coverage-fill" style="width:' + coverage + '%;"></div></div>';
    html += '<span class="pi-shared-coverage-text">' + escapeHtml(String(coverage) + '% cobertura') + '</span>';
    html += '</div>';

    if (missingIds.length > 0) {
      html += '<div class="pi-shared-missing"><strong>No encontradas:</strong> ' + escapeHtml(missingIds.join(', ')) + '</div>';
    }

    if (previewRows.length > 0) {
      var max = Math.min(previewRows.length, 12);
      html += '<div class="pi-shared-table-wrap"><table class="pi-shared-table">';
      html += '<thead><tr><th>#</th><th>Actividad</th><th>Actual</th><th>Objetivo</th><th>Ajuste</th></tr></thead><tbody>';

      for (var i = 0; i < max; i++) {
        var item = previewRows[i] || {};
        var consecutivo = String(item.consecutivo || '-');
        var actividad = getActividadPlainText(item.actividad || '').replace(/\s+/g, ' ').trim();
        if (!actividad) {
          actividad = 'Actividad sin nombre';
        }

        if (actividad.length > 160) {
          actividad = actividad.substring(0, 157) + '...';
        }

        var valorActual = getPreviewValueLabel(item.valor_actual);
        var deltaInfo = getPreviewDeltaInfo(item.valor_actual, targetRaw);

        html += '<tr>';
        html += '<td class="pi-shared-col-id">#' + escapeHtml(consecutivo) + '</td>';
        html += '<td class="pi-shared-activity-cell">' + escapeHtml(actividad) + '</td>';
        html += '<td>' + escapeHtml(valorActual) + '</td>';
        html += '<td>' + escapeHtml(targetLabel) + '</td>';
        html += '<td><span class="pi-shared-delta ' + escapeHtml(deltaInfo.className) + '">' + escapeHtml(deltaInfo.label) + '</span></td>';
        html += '</tr>';
      }

      html += '</tbody></table></div>';

      if (previewRows.length > max) {
        html += '<div class="pi-shared-more">... +' + escapeHtml(String(previewRows.length - max)) + ' más</div>';
      }
    } else {
      html += '<div class="pi-shared-empty">No hay actividades para mostrar en el preview.</div>';
    }

    html += '</div>';
    $preview.html(html);
  }

  function resetSharedConstraintModal() {
    var selectedIds = collectSelectedActivityIds();
    var type = String($('#piSharedRestrictionType').val() || 'D_y_E');

    setSharedValueOptionsForType(type, false);
    $('#piSharedActivityIds').val(selectedIds.join(','));
    $('#piSharedNote').val('');

    if (selectedIds.length > 0) {
      renderSharedPreviewEmpty('Filas detectadas: ' + selectedIds.length + '. Pulse "Preview" para validar impacto.');
    } else {
      renderSharedPreviewEmpty('Sin actividades cargadas. Use "Cargar marcadas" o "Usar visibles" antes del preview.');
    }

    $('#btn_pi_shared_preview').prop('disabled', false);
    $('#btn_pi_shared_apply').prop('disabled', false);
    updateSharedSelectionCountIndicator();
  }

  function loadSharedIdsIntoInput(activityIds, sourceLabel) {
    var ids = parseActivityIdsInput(activityIds);
    $('#piSharedActivityIds').val(ids.join(','));

    if (ids.length === 0) {
      renderSharedPreviewEmpty('No se cargaron actividades desde ' + sourceLabel + '.');
      return;
    }

    renderSharedPreviewEmpty('Cargadas ' + ids.length + ' actividades desde ' + sourceLabel + '. Pulse "Preview" para validar impacto.');
  }

  function loadMarkedIdsForSharedConstraint() {
    loadSharedIdsIntoInput(getMarkedActivityIds(), 'marcadas');
  }

  function loadVisibleIdsForSharedConstraint() {
    loadSharedIdsIntoInput(getVisibleActivityIds(), 'visibles');
  }

  function clearSharedIdsInput() {
    $('#piSharedActivityIds').val('');
    renderSharedPreviewEmpty('Lista de consecutivos limpia. Cargue actividades y pulse "Preview".');
  }

  function buildSharedConstraintRequest(requireValue) {
    var db = getDb();
    var semana = getSemana();
    var restrictionType = String($('#piSharedRestrictionType').val() || '').trim();
    var targetValue = String($('#piSharedRestrictionValue').val() || '').trim();
    var activityIds = parseActivityIdsInput($('#piSharedActivityIds').val());
    var note = String($('#piSharedNote').val() || '').trim();

    if (!restrictionType) {
      return { valid: false, error: 'Seleccione tipo de restricción.' };
    }

    if (activityIds.length === 0) {
      return { valid: false, error: 'Seleccione al menos una actividad.' };
    }

    if (requireValue && !targetValue) {
      return { valid: false, error: 'Seleccione un valor objetivo.' };
    }

    return {
      valid: true,
      data: {
        db: db,
        semana: semana,
        restriction_type: restrictionType,
        target_value: targetValue,
        activity_ids: activityIds,
        note: note,
      },
    };
  }

  function updateRowsAfterSharedConstraintApply(updatedIds, restrictionType, targetValue) {
    if (!Array.isArray(updatedIds) || updatedIds.length === 0) {
      return;
    }

    var normalizedValue = normalizeRestrictionValue(restrictionType, targetValue);
    if (normalizedValue === null) {
      return;
    }

    var idIndex = {};
    for (var i = 0; i < updatedIds.length; i++) {
      idIndex[String(updatedIds[i])] = true;
    }

    for (var rowIndex = 0; rowIndex < masterData.length; rowIndex++) {
      var row = masterData[rowIndex] || {};
      var rowId = getRowActivityId(row);
      if (!idIndex[rowId]) {
        continue;
      }

      row[restrictionType] = normalizedValue;
      row.Estado_Restricciones = calculateRestrictionStateRatio(row);
      row.estado_operativo = getStateLabel(row);
    }
  }

  function requestSharedConstraintPreview() {
    var request = buildSharedConstraintRequest(false);
    if (!request.valid) {
      showFeedback('error', request.error);
      return;
    }

    $('#btn_pi_shared_preview').prop('disabled', true);
    renderSharedPreviewLoading();

    $.ajax({
      method: 'POST',
      url: '/programacion-intermedia/shared-constraints/preview',
      dataType: 'json',
      data: request.data,
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        renderSharedPreview(response.data || {}, {
          restriction_type: request.data.restriction_type,
          target_value: request.data.target_value,
        });
        return;
      }

      var message = (response && (response.mensaje || response.message)) || 'No se pudo calcular el preview.';
      showFeedback('error', message);
      renderSharedPreviewEmpty('No se pudo generar el preview. Corrija datos e intente nuevamente.');
    }).fail(function () {
      showFeedback('error', 'Error de red en preview de restricción compartida.');
      renderSharedPreviewEmpty('Error de red calculando preview. Intente nuevamente.');
    }).always(function () {
      $('#btn_pi_shared_preview').prop('disabled', false);
    });
  }

  function requestSharedConstraintApply() {
    var request = buildSharedConstraintRequest(true);
    if (!request.valid) {
      showFeedback('error', request.error);
      return;
    }

    $('#btn_pi_shared_apply').prop('disabled', true);

    $.ajax({
      method: 'POST',
      url: '/programacion-intermedia/shared-constraints/apply',
      dataType: 'json',
      data: request.data,
    }).done(function (response) {
      if (!(response && response.respuesta === 'BIEN')) {
        var message = (response && (response.mensaje || response.message)) || 'No se pudo aplicar la restricción compartida.';
        showFeedback('error', message);
        return;
      }

      var data = response.data || {};
      var updatedIds = Array.isArray(data.updated_ids) ? data.updated_ids : request.data.activity_ids;
      var targetValue = data.target_value !== undefined ? data.target_value : request.data.target_value;

      updateRowsAfterSharedConstraintApply(updatedIds, request.data.restriction_type, targetValue);
      pendingViewportState = captureViewportState();
      applyFiltersAndRender();
      showFeedback('success', 'Lote aplicado (' + Number(data.updated_count || updatedIds.length || 0) + ')');
      renderSharedPreview({
        restriction_type: request.data.restriction_type,
        target_value: targetValue,
        count_total: request.data.activity_ids.length,
        count_found: Number(data.updated_count || updatedIds.length || 0),
        count_missing: 0,
        preview: [],
      }, {
        target_value: targetValue,
        restriction_type: request.data.restriction_type,
      });

      setTimeout(function () {
        $('#modal_shared_constraint').modal('hide');
      }, 180);
    }).fail(function () {
      showFeedback('error', 'Error de red aplicando restricción compartida.');
    }).always(function () {
      $('#btn_pi_shared_apply').prop('disabled', false);
    });
  }

  function showLoading(show) {
    if (show) {
      $('#loading').show();
    } else {
      $('#loading').fadeOut(200);
    }
  }

  function showFeedback(type, message) {
    clearTimeout(saveBadgeTimer);
    $('#save-status').hide();
    $('#save-error').hide();

    if (type === 'success') {
      $('#save-status').text(message || 'Guardado').fadeIn(120);
      saveBadgeTimer = setTimeout(function () {
        $('#save-status').fadeOut(250);
      }, 1800);
      return;
    }

    $('#save-error').text(message || 'Error al guardar').fadeIn(120);
    saveBadgeTimer = setTimeout(function () {
      $('#save-error').fadeOut(350);
    }, 3200);
  }

  function renderLegendModal() {
    $('#modal_leyenda_colores_Label').text('Guia Operativa - Programación Intermedia (Last Planner 6 semanas)');
    $('#modal_leyenda_colores_body').html(
      "<div class='pi-legend-quick'>" +
        "<div class='pi-legend-quick-header'>" +
          "<p class='pi-legend-quick-intro'><strong>Lectura rapida:</strong> atiende primero P1, luego P2 y deja P3 en monitoreo.</p>" +
          "<div class='pi-legend-quick-scale'>" +
            "<span class='pi-legend-quick-badge is-p1'>P1 Hoy</span>" +
            "<span class='pi-legend-quick-badge is-p2'>P2 Esta semana</span>" +
            "<span class='pi-legend-quick-badge is-p3'>P3 Seguimiento</span>" +
          "</div>" +
        "</div>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P1 - Resolver hoy</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-overdue-critical'></span>" +
            "<div class='pi-legend-quick-state'><strong>Bloqueada Vencida (Critica)</strong><small>Debio iniciar y sigue bloqueada en ruta critica.</small></div>" +
            "<div class='pi-legend-quick-action'>Escalar bloqueo, reasignar frente y cerrar causa raiz hoy.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-overdue'></span>" +
            "<div class='pi-legend-quick-state'><strong>Bloqueada Vencida</strong><small>Debio iniciar y aun tiene restricciones pendientes.</small></div>" +
            "<div class='pi-legend-quick-action'>Definir responsable y fecha de destrabe en la reunion diaria.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-due'></span>" +
            "<div class='pi-legend-quick-state'><strong>Debe Iniciar (Con Restricciones)</strong><small>Inicia esta semana y no esta liberada.</small></div>" +
            "<div class='pi-legend-quick-action'>Cerrar liberacion y asegurar cuadrilla/material antes del arranque.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-execution-blocked'></span>" +
            "<div class='pi-legend-quick-state'><strong>En Ejecucion (Con Restricciones)</strong><small>Actividad iniciada pero con restricciones abiertas.</small></div>" +
            "<div class='pi-legend-quick-action'>Eliminar restricciones activas para evitar retrabajos y paradas.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
        "</section>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P2 - Gestion semanal</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-1-week'></span>" +
            "<div class='pi-legend-quick-state'><strong>Alerta 1 Semana</strong><small>Riesgo alto de no iniciar en el proximo corte.</small></div>" +
            "<div class='pi-legend-quick-action'>Cerrar compras, permisos y acceso de frente esta semana.</div>" +
            "<span class='pi-legend-quick-priority is-p2'>P2</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-2-3-weeks'></span>" +
            "<div class='pi-legend-quick-state'><strong>Alerta 2-3 Semanas</strong><small>Riesgo medio en ventana proxima.</small></div>" +
            "<div class='pi-legend-quick-action'>Plan preventivo con abastecimiento y mano de obra confirmados.</div>" +
            "<span class='pi-legend-quick-priority is-p2'>P2</span>" +
          "</div>" +
        "</section>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P3 - Seguimiento</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-4-6-weeks'></span>" +
            "<div class='pi-legend-quick-state'><strong>Alerta 4-6 Semanas</strong><small>Riesgo temprano del lookahead.</small></div>" +
            "<div class='pi-legend-quick-action'>Monitorear preparacion y anticipar restricciones emergentes.</div>" +
            "<span class='pi-legend-quick-priority is-p3'>P3</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-liberated-control'></span>" +
            "<div class='pi-legend-quick-state'><strong>Liberada / Control</strong><small>Actividad lista para iniciar o en ejecucion con liberacion completa.</small></div>" +
            "<div class='pi-legend-quick-action'>Mantener control semanal y no perder trazabilidad de compromisos.</div>" +
            "<span class='pi-legend-quick-priority is-p3'>P3</span>" +
          "</div>" +
        "</section>" +
      "</div>"
    );
  }

  function fetchFilterFlags() {
    var db = getDb();
    var semana = getSemana();

    if (!db || !semana) {
      return $.Deferred().resolve('').promise();
    }

    return $.ajax({
      method: 'POST',
      url: '/programacion-intermedia/filtros',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).then(function (info) {
      var data = info && info.data ? info.data : {};
      var params = [];

      for (var i = 0; i < trackedStates.length; i++) {
        var key = trackedStates[i].replace(/-/g, '_');
        params.push('activa_' + key + '=' + (data['activa_' + key] ? 1 : 0));
      }

      var query = '&' + params.join('&');
      $('#scriptBarraFiltros').val(query);
      return query;
    }, function () {
      return '';
    });
  }

  function buildListUrl(extraFlags) {
    return '/api/pi/list?a=1' + (extraFlags || '');
  }

  function mapRows(rows) {
    var list = [];
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i] || {};

      row.D_y_E = normalizeRestrictionValue('D_y_E', row.D_y_E);
      row.Materiales = normalizeRestrictionValue('Materiales', row.Materiales);
      row.MdeO = normalizeRestrictionValue('MdeO', row.MdeO);
      row.Equipos = normalizeRestrictionValue('Equipos', row.Equipos);
      row.Predecesora = normalizeRestrictionValue('Predecesora', row.Predecesora);
      row.Pdto_Cons = normalizeRestrictionValue('Pdto_Cons', row.Pdto_Cons);
      row.Modelo = normalizeRestrictionValue('Modelo', row.Modelo);
      row.estado_operativo = getStateLabel(row);
      row.__shared_selected = Boolean(sharedSelectionIndex[getRowActivityId(row)]);

      list.push(row);
    }
    return list;
  }

  function requestList(extraFlags) {
    $.ajax({
      url: buildListUrl(extraFlags),
      method: 'GET',
      dataType: 'json',
      cache: false,
    }).done(function (response) {
      var rawData = response && Array.isArray(response.data) ? response.data : [];
      masterData = mapRows(rawData);
      rebuildSharedSelectionIndex();
      updateSharedSelectionCountIndicator();
      applyFiltersAndRender();
      showLoading(false);
    }).fail(function () {
      showLoading(false);
      showFeedback('error', 'Error cargando datos');
    });
  }

  function loadData() {
    showLoading(true);
    fetchFilterFlags().done(function (flags) {
      requestList(flags || '');
    });
  }

  function normalizeCellValue(prop, value) {
    if (prop === 'Sub_Contratista' || prop === 'Responsable_AIA' || prop === 'Observaciones') {
      return { valid: true, value: String(value === null || value === undefined ? '' : value).trim() };
    }

    if (editableProps[prop]) {
      var normalized = normalizeRestrictionValue(prop, value);
      if (normalized === null) {
        return { valid: false, value: value, error: 'Valor inválido' };
      }
      return { valid: true, value: normalized };
    }

    return { valid: true, value: value };
  }

  function buildPayload(row) {
    var id = row.Consecutivo_en_Programa;
    if (!id) {
      return { valid: false, error: 'Id de actividad inválido' };
    }

    var normalizedRestrictions = {};

    for (var i = 0; i < restrictionProps.length; i++) {
      var field = restrictionProps[i];
      var normalized = normalizeRestrictionForPayload(field, row[field]);
      if (normalized === null) {
        return { valid: false, error: 'Valor inválido en restricciones' };
      }
      normalizedRestrictions[field] = normalized;
    }

    return {
      valid: true,
      data: {
        opcion: 'modificar',
        Id: id,
        D_y_E: normalizedRestrictions.D_y_E,
        Materiales: normalizedRestrictions.Materiales,
        MdeO: normalizedRestrictions.MdeO,
        Equipos: normalizedRestrictions.Equipos,
        Predecesora: normalizedRestrictions.Predecesora,
        Pdto_Cons: normalizedRestrictions.Pdto_Cons,
        Modelo: normalizedRestrictions.Modelo,
        Sub_Contratista: row.Sub_Contratista || '',
        Responsable_AIA: row.Responsable_AIA || '',
        Observaciones: row.Observaciones || '',
      },
    };
  }

  function revertCell(visualRow, prop, oldValue) {
    var col = hot.propToCol(prop);
    if (col >= 0) {
      hot.setDataAtCell(visualRow, col, oldValue, 'revert');
    }
  }

  function saveRow(visualRow, prop, oldValue) {
    var db = getDb();
    var semana = getSemana();
    var physicalRow = hot.toPhysicalRow(visualRow);
    var row = hot.getSourceDataAtRow(physicalRow);

    var payload = buildPayload(row || {});
    if (!payload.valid) {
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      showFeedback('error', payload.error);
      return;
    }

    $.ajax({
      method: 'POST',
      url: '/api/pi/save?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      dataType: 'json',
      data: payload.data,
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        if (row) {
          if (response.estado_restricciones !== undefined && response.estado_restricciones !== null && response.estado_restricciones !== '') {
            row.Estado_Restricciones = response.estado_restricciones;
            hot.setDataAtRowProp(visualRow, 'Estado_Restricciones', response.estado_restricciones, 'internal-update');
          }

          if (response.semanas_inicio !== undefined && response.semanas_inicio !== null && response.semanas_inicio !== '') {
            row.Semanas_Inicio = response.semanas_inicio;
            hot.setDataAtRowProp(visualRow, 'Semanas_Inicio', response.semanas_inicio, 'internal-update');
          }

          if (response.estado !== undefined && response.estado !== null && response.estado !== '') {
            row.Estado = response.estado;
          }
        }

        pendingViewportState = captureViewportState();
        applyFiltersAndRender();
        showFeedback('success', 'Guardado');
        return;
      }

      var message = (response && (response.mensaje || response.message)) || 'Error al guardar';
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      showFeedback('error', message);
    }).fail(function () {
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      showFeedback('error', 'Error de red');
    });
  }

  function setupRenderers() {
    if (renderersRegistered) {
      return;
    }

    Handsontable.renderers.registerRenderer('piPercentRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = formatPercent(value);
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('piActividadRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.innerHTML = sanitizeActividadHtml(value);
      td.classList.add('htLeft');
    });

    renderersRegistered = true;
  }

  function sumWidths(widths) {
    var total = 0;
    for (var i = 0; i < widths.length; i++) {
      total += Number(widths[i]) || 0;
    }
    return total;
  }

  function getColumnMinWidth(index) {
    return Number(columnMinWidths[index]) || 56;
  }

  function getColumnMaxWidth(index) {
    var max = Number(columnMaxWidths[index]);
    if (!Number.isFinite(max) || max <= 0) {
      max = 280;
    }
    var min = getColumnMinWidth(index);
    return max < min ? min : max;
  }

  function arraysEqualNumbers(a, b) {
    if (!Array.isArray(a) || !Array.isArray(b) || a.length !== b.length) {
      return false;
    }

    for (var i = 0; i < a.length; i++) {
      if (Number(a[i]) !== Number(b[i])) {
        return false;
      }
    }

    return true;
  }

  function getContainerAvailableWidth() {
    var container = document.getElementById('hot-container');
    if (!container) {
      return 0;
    }

    var width = Math.floor(container.clientWidth || container.offsetWidth || 0);
    if (!Number.isFinite(width) || width <= 0) {
      return 0;
    }

    return Math.max(260, width - 20);
  }

  function getViewportHeight() {
    if (window.visualViewport && Number.isFinite(window.visualViewport.height) && window.visualViewport.height > 0) {
      return Math.floor(window.visualViewport.height);
    }

    var docHeight = document.documentElement && document.documentElement.clientHeight;
    var winHeight = window.innerHeight;
    var height = Number.isFinite(winHeight) && winHeight > 0 ? winHeight : docHeight;
    return Number.isFinite(height) && height > 0 ? Math.floor(height) : 0;
  }

  function getViewportScaleFactor() {
    var root = document.documentElement;
    if (!root) {
      return 1;
    }

    var zoom = parseFloat(root.style.zoom || '');
    if (Number.isFinite(zoom) && zoom > 0 && zoom < 1) {
      return zoom;
    }

    if (root.classList.contains('tablet-scale-70') || root.classList.contains('desktop-tablet-scale-70')) {
      return 0.7;
    }

    return 1;
  }

  function syncContainerHeight() {
    var container = document.getElementById('hot-container');
    if (!container || !container.getBoundingClientRect) {
      return 0;
    }

    var rect = container.getBoundingClientRect();
    var viewportHeight = getViewportHeight();
    var scaleFactor = getViewportScaleFactor();
    if (!Number.isFinite(viewportHeight) || viewportHeight <= 0) {
      return 0;
    }

    if (scaleFactor > 0 && scaleFactor < 1) {
      viewportHeight = Math.floor(viewportHeight / scaleFactor);
    }

    var top = Math.max(0, Math.floor(rect.top || 0));
    var bottomGap = 2;
    var available = Math.floor(viewportHeight - top - bottomGap);
    var resolved = Math.max(260, available);

    container.style.height = resolved + 'px';
    return resolved;
  }

  function getContainerAvailableHeight() {
    var container = document.getElementById('hot-container');
    if (!container) {
      return 0;
    }

    syncContainerHeight();

    var height = Math.floor(container.clientHeight || container.offsetHeight || 0);
    if (!Number.isFinite(height) || height <= 0) {
      return 0;
    }

    return Math.max(220, height - 2);
  }

  function getHotHolderElement() {
    return document.querySelector('#hot-container .ht_master .wtHolder') || document.querySelector('#hot-container .wtHolder');
  }

  function captureViewportState() {
    var holder = getHotHolderElement();
    var selected = hot && typeof hot.getSelectedLast === 'function' ? hot.getSelectedLast() : null;

    return {
      pageX: window.pageXOffset || document.documentElement.scrollLeft || 0,
      pageY: window.pageYOffset || document.documentElement.scrollTop || 0,
      holderTop: holder ? holder.scrollTop : 0,
      holderLeft: holder ? holder.scrollLeft : 0,
      selected: Array.isArray(selected) ? selected.slice() : null,
    };
  }

  function restoreViewportState(state) {
    if (!state) {
      return;
    }

    window.scrollTo(state.pageX || 0, state.pageY || 0);

    var holder = getHotHolderElement();
    if (holder) {
      holder.scrollTop = Number(state.holderTop) || 0;
      holder.scrollLeft = Number(state.holderLeft) || 0;
    }

    if (hot && Array.isArray(state.selected) && state.selected.length >= 2) {
      var row = state.selected[0];
      var col = state.selected[1];
      var endRow = Number.isInteger(state.selected[2]) ? state.selected[2] : row;
      var endCol = Number.isInteger(state.selected[3]) ? state.selected[3] : col;

      if (Number.isInteger(row) && Number.isInteger(col)) {
        try {
          hot.selectCell(row, col, endRow, endCol, false, false);
        } catch (_err) {
        }
      }
    }
  }

  function getBaseColumnWidths(columnCount) {
    var widths = [];
    var plugin = hot && hot.getPlugin ? hot.getPlugin('autoColumnSize') : null;

    if (plugin) {
      try {
        if (typeof plugin.recalculateAllColumnsWidth === 'function') {
          plugin.recalculateAllColumnsWidth();
        } else if (typeof plugin.calculateVisibleColumnsWidth === 'function') {
          plugin.calculateVisibleColumnsWidth();
        }
      } catch (_err) {
      }
    }

    for (var col = 0; col < columnCount; col++) {
      var min = getColumnMinWidth(col);
      var max = getColumnMaxWidth(col);
      var width = null;

      if (plugin && typeof plugin.getColumnWidth === 'function') {
        try {
          width = plugin.getColumnWidth(col);
        } catch (_err2) {
          width = null;
        }
      }

      if (!Number.isFinite(width) || width <= 0) {
        var header = hot ? String(hot.getColHeader(col) || '') : '';
        width = (header.length * 8) + 24;
      }

      width = Math.ceil(width + 8);
      if (width < min) {
        width = min;
      }
      if (width > max) {
        width = max;
      }

      widths.push(width);
    }

    return widths;
  }

  function reduceWidthsToTarget(widths, targetWidth, lowerBounds) {
    var reducedWidths = widths.slice();
    var total = sumWidths(reducedWidths);
    if (total <= targetWidth) {
      return reducedWidths;
    }

    var excess = total - targetWidth;
    var capacities = [];
    var totalCapacity = 0;

    for (var col = 0; col < reducedWidths.length; col++) {
      var lowerBound = Number(lowerBounds[col]);
      if (!Number.isFinite(lowerBound) || lowerBound < 20) {
        lowerBound = 20;
      }
      var cap = Math.max(0, reducedWidths[col] - lowerBound);
      capacities[col] = cap;
      totalCapacity += cap;
    }

    if (totalCapacity <= 0) {
      return reducedWidths;
    }

    if (totalCapacity <= excess) {
      for (var c = 0; c < reducedWidths.length; c++) {
        reducedWidths[c] = Number(lowerBounds[c]) || 20;
      }
      return reducedWidths;
    }

    var reduced = 0;
    for (var i = 0; i < reducedWidths.length; i++) {
      var capacity = capacities[i];
      if (capacity <= 0) {
        continue;
      }

      var step = Math.floor((excess * capacity) / totalCapacity);
      if (step > capacity) {
        step = capacity;
      }
      if (step > 0) {
        reducedWidths[i] -= step;
        reduced += step;
      }
    }

    var remainder = excess - reduced;
    var guard = 0;
    while (remainder > 0 && guard < 4000) {
      for (var p = 0; p < columnShrinkPriority.length && remainder > 0; p++) {
        var index = columnShrinkPriority[p];
        if (index < 0 || index >= reducedWidths.length) {
          continue;
        }

        var bound = Number(lowerBounds[index]) || 20;
        if (reducedWidths[index] > bound) {
          reducedWidths[index] -= 1;
          remainder -= 1;
        }
      }
      guard += 1;
    }

    return reducedWidths;
  }

  function expandWidthsToTarget(widths, targetWidth, upperBounds) {
    var expandedWidths = widths.slice();
    var total = sumWidths(expandedWidths);
    if (total >= targetWidth) {
      return expandedWidths;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var growPriority = columnShrinkPriority.slice().reverse();

    while (remainder > 0 && guard < 5000) {
      var grew = false;
      for (var i = 0; i < growPriority.length && remainder > 0; i++) {
        var index = growPriority[i];
        if (index < 0 || index >= expandedWidths.length) {
          continue;
        }

        var upperBound = Number(upperBounds[index]);
        if (!Number.isFinite(upperBound) || upperBound <= 0) {
          upperBound = expandedWidths[index] + remainder;
        }

        if (expandedWidths[index] < upperBound) {
          expandedWidths[index] += 1;
          remainder -= 1;
          grew = true;
        }
      }

      if (!grew) {
        break;
      }
      guard += 1;
    }

    return expandedWidths;
  }

  function forceFillWidthsToTarget(widths, targetWidth) {
    var filled = widths.slice();
    var total = sumWidths(filled);
    if (total >= targetWidth) {
      return filled;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var growPriority = columnShrinkPriority.slice().reverse();

    while (remainder > 0 && guard < 6000) {
      for (var i = 0; i < growPriority.length && remainder > 0; i++) {
        var index = growPriority[i];
        if (index < 0 || index >= filled.length) {
          continue;
        }
        filled[index] += 1;
        remainder -= 1;
      }
      guard += 1;
    }

    return filled;
  }

  function constrainColumnWidthsToContainer(widths, targetWidth) {
    var constrained = reduceWidthsToTarget(widths, targetWidth, columnMinWidths);
    if (sumWidths(constrained) > targetWidth) {
      constrained = reduceWidthsToTarget(constrained, targetWidth, columnFloorWidths);
    }

    if (sumWidths(constrained) < targetWidth) {
      constrained = expandWidthsToTarget(constrained, targetWidth, columnMaxWidths);
    }

    if (sumWidths(constrained) < targetWidth) {
      constrained = forceFillWidthsToTarget(constrained, targetWidth);
    }

    return constrained;
  }

  function applyResponsiveColumnWidths(force) {
    if (!hot) {
      return;
    }

    var settings = hot.getSettings() || {};
    var columns = Array.isArray(settings.columns) ? settings.columns : [];
    var columnCount = columns.length;
    if (!columnCount) {
      return;
    }

    var containerWidth = getContainerAvailableWidth();
    if (!containerWidth) {
      return;
    }

    if (!force && containerWidth === lastAppliedContainerWidth && currentColumnWidths.length === columnCount) {
      return;
    }

    var baseWidths = getBaseColumnWidths(columnCount);
    var constrained = constrainColumnWidthsToContainer(baseWidths, containerWidth);

    if (!force && arraysEqualNumbers(currentColumnWidths, constrained)) {
      lastAppliedContainerWidth = containerWidth;
      return;
    }

    hot.updateSettings({ colWidths: constrained });
    currentColumnWidths = constrained.slice();
    lastAppliedContainerWidth = containerWidth;
  }

  function scheduleLayoutRefresh(delay, force) {
    clearTimeout(layoutTimer);
    layoutTimer = setTimeout(function () {
      if (!hot) {
        return;
      }

      var viewportState = pendingViewportState;
      pendingViewportState = null;

      syncContainerHeight();
      var containerHeight = getContainerAvailableHeight();
      if (containerHeight && (Boolean(force) || containerHeight !== lastAppliedContainerHeight)) {
        hot.updateSettings({ height: containerHeight });
        lastAppliedContainerHeight = containerHeight;
      }

      if (typeof hot.refreshDimensions === 'function') {
        hot.refreshDimensions();
      }

      applyResponsiveColumnWidths(Boolean(force));
      hot.render();

      if (viewportState) {
        setTimeout(function () {
          restoreViewportState(viewportState);
        }, 0);
      }
    }, Number.isFinite(delay) ? delay : 24);
  }

  function rowMatchesFilters(row) {
    var activityFilter = String($('#buscadorActividad').val() || '').trim().toLowerCase();
    var semanasFilter = String($('#buscadorSemanasInicio').val() || '').trim();
    var liberadaFilter = String($('#buscadorLiberada').val() || '').trim();
    var subFilter = String($('#buscadorSubcontratista').val() || '').trim().toLowerCase();
    var respFilter = String($('#buscadorResponsableAIA').val() || '').trim().toLowerCase();

    if (activityFilter) {
      if (getActividadPlainText(row.Actividad).toLowerCase().indexOf(activityFilter) === -1) {
        return false;
      }
    }

    if (semanasFilter) {
      var si = Math.round(toNumber(row.Semanas_Inicio, 999));
      if (semanasFilter === '7') {
        if (si < 7) {
          return false;
        }
      } else if (String(si) !== semanasFilter) {
        return false;
      }
    }

    if (liberadaFilter) {
      var er = toNumber(row.Estado_Restricciones, 0);
      if (liberadaFilter === 'NoLiberada' && er >= 0.999) {
        return false;
      }
      if (liberadaFilter === 'Liberada' && er < 0.999) {
        return false;
      }
    }

    if (subFilter) {
      if (String(row.Sub_Contratista || '').toLowerCase() !== subFilter) {
        return false;
      }
    }

    if (respFilter) {
      if (String(row.Responsable_AIA || '').toLowerCase() !== respFilter) {
        return false;
      }
    }

    var state = getState(row);
    if (activeFilters.length > 0 && activeFilters.indexOf(state) === -1) {
      return false;
    }

    return true;
  }

  function updateLegendCounts(rows) {
    var counts = {};
    for (var i = 0; i < trackedStates.length; i++) {
      counts[trackedStates[i]] = 0;
    }

    for (var r = 0; r < rows.length; r++) {
      var state = getState(rows[r]);
      if (counts[state] !== undefined) {
        counts[state] += 1;
      }
    }

    Object.keys(counts).forEach(function (key) {
      $('#count-' + key).text('(' + counts[key] + ')');
    });
  }

  function openDropdownEditorAtCell(instance, row, col, triggerEvent, reselectCell) {
    if (!instance || row < 0 || col < 0) {
      return;
    }

    var prop = instance.colToProp(col);
    if (!dropdownProps[prop] || !editableProps[prop]) {
      return;
    }

    var rowData = instance.getSourceDataAtRow(row) || {};
    if (getState(rowData) === 'header' || !isUserAllowedToEdit()) {
      return;
    }

    var currentValue = instance.getDataAtRowProp(row, prop);
    if (reselectCell !== false) {
      instance.selectCell(row, col, row, col, false, false);
    }

    setTimeout(function () {
      if (!instance) {
        return;
      }

      var editor = instance.getActiveEditor ? instance.getActiveEditor() : null;
      if (!editor) {
        return;
      }

      try {
        if (typeof editor.enableFullEditMode === 'function') {
          editor.enableFullEditMode();
        }

        editor.beginEditing(currentValue, triggerEvent || null);
        if (typeof editor.open === 'function' && (!editor.isOpened || !editor.isOpened())) {
          editor.open(triggerEvent || null);
        }
      } catch (_err) {
      }
    }, 0);
  }

  function updateOrInitHot(data) {
    setupRenderers();
    syncContainerHeight();

    if (hot) {
      hot.loadData(data);
      scheduleLayoutRefresh(0, true);
      return;
    }

    var container = document.getElementById('hot-container');
    if (!container) {
      return;
    }

    hot = new Handsontable(container, {
      data: data,
      rowHeaders: false,
      colHeaders: [
        'Id',
        'Lote',
        'Actividad',
        'Sub-Contratista',
        'Responsable AIA',
        'Semanas Inicio',
        'Ejecutado',
        'Diseños y Especif.',
        'Materiales',
        'Mano de Obra',
        'Equipos',
        'Predecesoras',
        'Proced. Constructivo',
        'Modelación BIM',
        '% Liberación',
        'Observaciones',
      ],
      columns: [
        { data: 'Id', readOnly: true, className: 'htCenter htMiddle' },
        { data: '__shared_selected', type: 'checkbox', className: 'htCenter htMiddle pi-shared-select-cell' },
        { data: 'Actividad', readOnly: true, renderer: 'piActividadRenderer', className: 'htLeft htMiddle force-wrap' },
        { data: 'Sub_Contratista', editor: 'tomSelectMultiple', tomSelectOptions: subcontratistas, className: 'htCenter htMiddle force-wrap' },
        { data: 'Responsable_AIA', editor: 'tomSelectSingle', tomSelectOptions: profesionales, className: 'htCenter htMiddle force-wrap' },
        { data: 'Semanas_Inicio', readOnly: true, className: 'htCenter htMiddle' },
        { data: 'Ejecutado', readOnly: true, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'D_y_E', type: 'dropdown', source: restrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Materiales', type: 'dropdown', source: restrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'MdeO', type: 'dropdown', source: restrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Equipos', type: 'dropdown', source: restrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Predecesora', type: 'dropdown', source: halfRestrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Pdto_Cons', type: 'dropdown', source: halfRestrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Modelo', type: 'dropdown', source: halfRestrictedOptions, strict: false, allowInvalid: false, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Estado_Restricciones', readOnly: true, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
        { data: 'Observaciones', type: 'text', className: 'htLeft htMiddle force-wrap' },
      ],
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'none',
      autoColumnSize: true,
      manualColumnResize: false,
      manualRowResize: true,
      contextMenu: true,
      dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
      filters: true,
      search: false,
      exportFile: true,
      columnSorting: false,
      wordWrap: true,
      colHeaderHeight: 48,
      width: '100%',
      height: getContainerAvailableHeight() || '100%',
      cells: function (row, col, prop) {
        var props = {};
        var rowData = this.instance.getSourceDataAtRow(row) || {};
        var state = getState(rowData);
        var columnMeta = this.instance.getSettings().columns[col] || {};
        var baseClass = columnMeta.className || '';
        var rowStateClass = (state === 'header') ? 'pdc-header' : ('pi-state-' + state);
        var isSharedSelector = prop === '__shared_selected';
        var canEdit = isSharedSelector ? (state !== 'header') : (Boolean(editableProps[prop]) && state !== 'header' && isUserAllowedToEdit());
        var isDropdownCell = Boolean(dropdownProps[prop]) && state !== 'header';
        var interactionClass = canEdit ? 'pi-cell-editable' : 'pi-cell-readonly';
        if (isSharedSelector) {
          interactionClass += ' pi-shared-selector';
        }
        if (isDropdownCell && canEdit) {
          interactionClass += ' pi-cell-dropdown';
        }

        if (normalizeSharedSelectionValue(rowData.__shared_selected) && state !== 'header') {
          interactionClass += ' pi-row-shared-picked';
        }

        props.className = (baseClass + ' ' + 'pi-row-state ' + rowStateClass + ' ' + interactionClass).trim();
        props.readOnly = !canEdit;
        return props;
      },
      afterGetColHeader: function (col, TH) {
        if (!TH || !TH.querySelector) {
          return;
        }

        var headerNode = TH.querySelector('.colHeader');
        if (!headerNode) {
          return;
        }

        var headerText = String(this.getColHeader(col) || '').replace(/\s+/g, ' ').trim();
        headerNode.classList.remove('pi-header-single-word');

        if (headerText && headerText.indexOf(' ') === -1) {
          headerNode.classList.add('pi-header-single-word');
        }

        // Inject tooltip trigger alongside changeType
        var resProp = headerIndexToRestrictionProp[col];
        if (resProp && !TH.querySelector('.pi-help-trigger')) {
          var wrapper = TH.querySelector('.relative');
          var changeBtn = wrapper ? wrapper.querySelector('.changeType') : null;
          var trigger = document.createElement('a');
          trigger.href = 'javascript:void(0);';
          trigger.className = 'pi-help-trigger';
          trigger.setAttribute('data-type', resProp);
          trigger.innerHTML = '<i class="fas fa-question-circle"></i>';
          if (changeBtn) {
            // Wrap both in a horizontal row
            var row = document.createElement('div');
            row.className = 'pi-header-controls';
            changeBtn.parentNode.insertBefore(row, changeBtn);
            row.appendChild(trigger);
            row.appendChild(changeBtn);
          } else {
            (wrapper || headerNode).appendChild(trigger);
          }
        }
      },
      beforeKeyDown: function (event) {
        if (!event) {
          return;
        }

        var key = String(event.key || '');
        var isNav = key === 'Tab' || key === 'Enter' ||
                    key === 'ArrowUp' || key === 'ArrowDown' ||
                    key === 'ArrowLeft' || key === 'ArrowRight';
        if (isNav && !event.ctrlKey && !event.metaKey && !event.altKey) {
          pendingTabSelection = true;
          return;
        }
        pendingTabSelection = false;
      },
      afterSelectionEnd: function (row, col) {
        var isKeyNav = pendingTabSelection || window.__piPendingNav;
        pendingTabSelection = false;
        window.__piPendingNav = false;

        if (!isKeyNav) {
          return;
        }

        var prop = this.colToProp(col);
        if (dropdownProps[prop]) {
          var hot = this;
          openDropdownEditorAtCell(hot, row, col, null, false);
        }
      },
      afterOnCellMouseDown: function (event, coords) {
        if (!coords || coords.row < 0 || coords.col < 0) {
          return;
        }

        if (!event || event.button !== 0 || event.shiftKey || event.ctrlKey || event.metaKey || event.altKey) {
          return;
        }

        var prop = this.colToProp(coords.col);
        if (!dropdownProps[prop] || !editableProps[prop]) {
          return;
        }

        openDropdownEditorAtCell(this, coords.row, coords.col, event, true);
      },
      afterChange: function (changes, source) {
        if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') {
          return;
        }

        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          var row = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];
          var isRestrictionChange = restrictionProps.indexOf(prop) > -1;

          if (prop === '__shared_selected') {
            if (oldValue !== newValue) {
              updateSharedSelectionFromVisualRow(row, newValue);
            }
            continue;
          }

          if (!editableProps[prop] || oldValue === newValue) {
            continue;
          }

          if (prop === 'Sub_Contratista' && newValue && newValue.indexOf(PI_CREATE_SUB) > -1) {
            var updatedValue = newValue.replace(PI_CREATE_SUB, '').replace(/,\s*,/g, ',').replace(/(^,)|(,$)/g, '').trim();
            if (updatedValue !== (oldValue || '')) {
              hot.setDataAtCell(row, hot.propToCol(prop), updatedValue, 'edit');
            } else {
              revertCell(row, prop, oldValue);
            }
            window.open('/subcontratistas', '_blank');
            continue;
          }
          if (prop === 'Responsable_AIA' && newValue && newValue.indexOf(PI_CREATE_PROF) > -1) {
            var updatedValueProf = newValue.replace(PI_CREATE_PROF, '').replace(/,\s*,/g, ',').replace(/(^,)|(,$)/g, '').trim();
            if (updatedValueProf !== (oldValue || '')) {
              hot.setDataAtCell(row, hot.propToCol(prop), updatedValueProf, 'edit');
            } else {
              revertCell(row, prop, oldValue);
            }
            window.open('/profesionales', '_blank');
            continue;
          }

          if (isRestrictionChange) {
            var rowData = this.getSourceDataAtRow(row) || {};
            var hasSub = rowData.Sub_Contratista && String(rowData.Sub_Contratista).trim() !== '' && rowData.Sub_Contratista !== PI_CREATE_SUB;
            var hasResp = rowData.Responsable_AIA && String(rowData.Responsable_AIA).trim() !== '' && rowData.Responsable_AIA !== PI_CREATE_PROF;
            if (!hasSub || !hasResp) {
              revertCell(row, prop, oldValue);
              showFeedback('error', 'No puede gestionar restricciones de una actividad sin asignar Responsable y Subcontratista');
              continue;
            }
          }

          var normalized = normalizeCellValue(prop, newValue);
          if (!normalized.valid) {
            revertCell(row, prop, oldValue);
            showFeedback('error', normalized.error);
            continue;
          }

          if (normalized.value !== newValue) {
            hot.setDataAtRowProp(row, prop, normalized.value, 'internal-update');
          }

          if (isRestrictionChange) {
            recalculateRestrictionStateForVisualRow(row);
          }

          saveRow(row, prop, oldValue);
        }

        hot.render();
      },
    });

    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();
    container.addEventListener('mousedown', function () {
      if (hot && !hot.isDestroyed) { hot.listen(); }
    }, true);

    // MutationObserver: estilar ítems "Crear" en dropdowns
    (function () {
      function styleCreateItems() {
        var editors = document.querySelectorAll('.autocompleteEditor:not([style*="display: none"]) td');
        for (var i = 0; i < editors.length; i++) {
          var txt = (editors[i].textContent || '').trim();
          if (txt.indexOf('Crear') > -1 && !editors[i].classList.contains('pi-create-option')) {
            editors[i].classList.add('pi-create-option');
          }
        }
      }

      var observer = new MutationObserver(function (mutations) {
        for (var m = 0; m < mutations.length; m++) {
          if (mutations[m].addedNodes.length > 0 || mutations[m].type === 'attributes') {
            styleCreateItems();
            break;
          }
        }
      });

      observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
    })();

    scheduleLayoutRefresh(0, true);
  }

  function applyFiltersAndRender() {
    var filtered = [];
    for (var i = 0; i < masterData.length; i++) {
      if (rowMatchesFilters(masterData[i])) {
        filtered.push(masterData[i]);
      }
    }

    updateLegendCounts(filtered);
    updateOrInitHot(filtered);
    updateSharedSelectionCountIndicator();
  }

  function syncLegendVisualState() {
    if (activeFilters.length === 0) {
      $('#piLegend .pdc-legend-item').removeClass('inactive-filter');
    } else {
      $('#piLegend .pdc-legend-item').addClass('inactive-filter');
      for (var i = 0; i < activeFilters.length; i++) {
        $("#piLegend .pdc-legend-item[data-filter='" + activeFilters[i] + "']").removeClass('inactive-filter');
      }
    }

    $('#mobileFilterCount').text(activeFilters.length);
  }

  function toggleLegendFilter(filterState, event) {
    event = event || {};
    var index = activeFilters.indexOf(filterState);
    if (!event.ctrlKey && !event.metaKey) {
      if (activeFilters.length === 1 && activeFilters[0] === filterState) {
        activeFilters = [];
      } else {
        activeFilters = [filterState];
      }
    } else if (index > -1) {
      activeFilters.splice(index, 1);
    } else {
      activeFilters.push(filterState);
    }

    syncLegendVisualState();
    applyFiltersAndRender();
  }

  function bindFilters() {
    $('#buscadorActividad').off('input.pi').on('input.pi', applyFiltersAndRender);
    $('#buscadorSemanasInicio').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorLiberada').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorSubcontratista').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorResponsableAIA').off('change.pi').on('change.pi', applyFiltersAndRender);

    $('#btn_limpiar_buscador').off('click.pi').on('click.pi', function () {
      $('#buscadorActividad').val('');
      $('#buscadorSemanasInicio').val('');
      $('#buscadorLiberada').val('');
      $('#buscadorSubcontratista').val('');
      $('#buscadorResponsableAIA').val('');
      activeFilters = [];
      syncLegendVisualState();
      applyFiltersAndRender();
    });

    $('#piLegend').off('click.pi keydown.pi')
      .on('click.pi', '.pdc-legend-item', function (event) {
        var key = $(this).data('filter');
        if (key) {
          toggleLegendFilter(String(key), event);
        }
      })
      .on('keydown.pi', '.pdc-legend-item', function (event) {
        if (event.key === 'Enter' || event.keyCode === 13 || event.keyCode === 32) {
          event.preventDefault();
          var key = $(this).data('filter');
          if (key) {
            toggleLegendFilter(String(key), event);
          }
        }
      });

    window.filterPDC = function (filterState, event) {
      toggleLegendFilter(filterState, event || {});
    };
  }

  function exportCsv() {
    if (!hot) {
      return;
    }

    hot.getPlugin('exportFile').downloadFile('csv', {
      filename: 'programacion_intermedia',
      columnHeaders: true,
      rowHeaders: false,
    });
  }

  function descargarReporte() {
    var db = getDb();
    var semana = getSemana();

    $('#btn_informe_compromisos').prop('disabled', true).text('Generando...');

    $.ajax({
      method: 'POST',
      url: '/reportes/restricciones',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).done(function (response) {
      if (response && response.url) {
        window.location.href = response.url;
      } else {
        showFeedback('error', 'No se pudo generar el reporte');
      }
    }).fail(function () {
      showFeedback('error', 'Error de red al generar reporte');
    }).always(function () {
      $('#btn_informe_compromisos').prop('disabled', false).html('Descargar Corte <i class="fas fa-download ml-1"></i>');
    });
  }

  // Interactive tooltips for restriction column headers
  var helpTooltipTimeout = null;
  var helpCurrentTrigger = null;

  function bindHeaderTooltips() {
    $('body').off('mouseenter.piHelp').on('mouseenter.piHelp', '.pi-help-trigger', function (e) {
      e.stopPropagation();
      var $this = $(this);
      if (helpCurrentTrigger && helpCurrentTrigger[0] === $this[0]) {
        clearTimeout(helpTooltipTimeout);
        return;
      }
      clearTimeout(helpTooltipTimeout);
      $('.pi-help-trigger').not($this).tooltip('hide');
      helpCurrentTrigger = $this;
      if (!$this.data('bs.tooltip')) {
        $this.tooltip({
          trigger: 'manual', html: true,
          placement: 'bottom', container: 'body',
          boundary: 'window',
          template: '<div class="tooltip pi-help-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner tooltip-inner--wide"></div></div>',
          title: function () {
            var type = $(this).data('type');
            return '<h6 class="font-weight-bold border-bottom pb-2 mb-2">' + (popoverTitles[type] || '') + '</h6>' + (popoverContent[type] || '');
          },
        });
      }
      $this.tooltip('show');
    });
    $('body').off('mouseleave.piHelp').on('mouseleave.piHelp', '.pi-help-trigger', function () {
      var $this = $(this);
      helpTooltipTimeout = setTimeout(function () {
        $this.tooltip('hide');
        helpCurrentTrigger = null;
      }, 100);
    });
    $('body').off('mouseenter.piHelpTip').on('mouseenter.piHelpTip', '.pi-help-tooltip', function () {
      clearTimeout(helpTooltipTimeout);
    });
    $('body').off('mouseleave.piHelpTip').on('mouseleave.piHelpTip', '.pi-help-tooltip', function () {
      if (helpCurrentTrigger) {
        helpTooltipTimeout = setTimeout(function () {
          helpCurrentTrigger.tooltip('hide');
          helpCurrentTrigger = null;
        }, 100);
      }
    });
  }

  function refreshDropdownSources() {
    var db = getDb();
    if (!db) { showFeedback('error', 'No hay proyecto seleccionado'); return; }
    $('#btn-refresh-listas').prop('disabled', true).text('Cargando...');

    var urlSub = '/construccion/subcontratistas/listar_subcontratistas.php?db=' + encodeURIComponent(db);
    var urlProf = '/construccion/profesionales/listar_profesionales.php?db=' + encodeURIComponent(db);

    $.when(
      $.getJSON(urlSub),
      $.getJSON(urlProf)
    ).done(function (resSub, resProf) {
      try {
        var rawSub = resSub[0];
        var rawProf = resProf[0];
        var arrSub = (rawSub && Array.isArray(rawSub.data)) ? rawSub.data : [];
        var arrProf = (rawProf && Array.isArray(rawProf.data)) ? rawProf.data : [];

        subcontratistas = ['AIA (MO Directa)'];
        arrSub.forEach(function (s) {
          var name = (s.subcontratista || '').trim();
          if (name) subcontratistas.push(name);
        });
        subcontratistas.push(PI_CREATE_SUB);

        profesionales = [];
        arrProf.forEach(function (p) {
          var name = (p.nombre || '').trim();
          if (name) profesionales.push(name);
        });
        profesionales.push(PI_CREATE_PROF);

        if (hot) {
          var cols = hot.getSettings().columns;
          cols.forEach(function (col) {
            if (col.data === 'Sub_Contratista') {
              col.tomSelectOptions = subcontratistas;
            }
            if (col.data === 'Responsable_AIA') col.tomSelectOptions = profesionales;
          });
          hot.updateSettings({ columns: cols });
        }
        showFeedback('success', 'Listas actualizadas');
      } catch (e) {
        showFeedback('error', 'Error al procesar las listas');
      }
    }).fail(function () {
      showFeedback('error', 'Error al conectar con el servidor');
    }).always(function () {
      $('#btn-refresh-listas').prop('disabled', false).text('🔄 Listas');
    });
  }

  function bindActions() {
    bindHeaderTooltips();
    $('#btn-refresh').off('click.piRefresh').on('click.piRefresh', loadData);
    $('#btn-refresh-listas').off('click.piRefreshListas').on('click.piRefreshListas', refreshDropdownSources);
    $('#btn-export').off('click.piExport').on('click.piExport', exportCsv);
    $('#btn_informe_compromisos').off('click.piReport').on('click.piReport', descargarReporte);
    $('#btn-shared-select-visible').off('click.piSharedVisible').on('click.piSharedVisible', selectVisibleRowsForSharedConstraint);
    $('#btn-shared-clear-selection').off('click.piSharedClear').on('click.piSharedClear', clearSharedSelection);
    $('#btn-shared-constraint').off('click.piSharedOpen').on('click.piSharedOpen', function () {
      if (!hot) {
        showFeedback('error', 'La tabla aun no esta lista.');
        return;
      }

      if (!isUserAllowedToEdit()) {
        showFeedback('error', 'No tiene permiso para aplicar restricciones en lote.');
        return;
      }

      resetSharedConstraintModal();
      $('#modal_shared_constraint').modal('show');
    });

    $('#piSharedRestrictionType').off('change.piSharedType').on('change.piSharedType', function () {
      setSharedValueOptionsForType(String($(this).val() || ''), true);
    });

    $('#btn_pi_shared_preview').off('click.piSharedPreview').on('click.piSharedPreview', requestSharedConstraintPreview);
    $('#btn_pi_shared_apply').off('click.piSharedApply').on('click.piSharedApply', requestSharedConstraintApply);
    $('#btn_pi_shared_use_marked').off('click.piSharedUseMarked').on('click.piSharedUseMarked', loadMarkedIdsForSharedConstraint);
    $('#btn_pi_shared_use_visible').off('click.piSharedUseVisible').on('click.piSharedUseVisible', loadVisibleIdsForSharedConstraint);
    $('#btn_pi_shared_clear_ids').off('click.piSharedClearIds').on('click.piSharedClearIds', clearSharedIdsInput);

    $('#pdcFiltersMobile')
      .off('shown.bs.collapse.piLayout hidden.bs.collapse.piLayout')
      .on('shown.bs.collapse.piLayout hidden.bs.collapse.piLayout', function () {
        scheduleLayoutRefresh(0, true);
      });

    $(document)
      .off('show.bs.modal.piLegend', '#modal_leyenda_colores')
      .on('show.bs.modal.piLegend', '#modal_leyenda_colores', renderLegendModal)
      .off('shown.bs.modal.piShared', '#modal_shared_constraint')
      .on('shown.bs.modal.piShared', '#modal_shared_constraint', function () {
        var selectedIds = collectSelectedActivityIds();
        if (selectedIds.length > 0) {
          loadSharedIdsIntoInput(selectedIds, 'seleccion actual');
        }
      });
  }

  function bindResize() {
    $(window)
      .off('resize.piHot orientationchange.piHot aia:viewport-scale-change.piHot')
      .on('resize.piHot orientationchange.piHot aia:viewport-scale-change.piHot', function () {
        scheduleLayoutRefresh(80, true);
      });
  }

  function init() {
    if (!initialized) {
      bindActions();
      bindFilters();
      bindResize();
      renderLegendModal();
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso').val() || getPermiso());
    }

    syncLegendVisualState();
    updateSharedSelectionCountIndicator();
    loadData();
  }

  window.PIHotModule = {
    init: init,
    getHotInstance: function () { return hot; },
  };
})(window, jQuery);
