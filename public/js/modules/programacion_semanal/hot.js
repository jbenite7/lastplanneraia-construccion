(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var reloadTimer = null;
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var toolbarFitTimer = null;
  var pendingDropdownAutoOpen = false;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var pendingViewportState = null;
  var masterData = [];
  var weeklyAlertFilters = [];
  var weeklyPhaseKey = 'programacion';
  var pendingDeleteRow = null;

  var options = window.PS_HOT_OPTIONS || {};
  var subcontratistas = Array.isArray(options.subcontratistas) ? options.subcontratistas : [];
  var profesionales = Array.isArray(options.profesionales) ? options.profesionales : [];

  var editableProps = {
    Descripcion: true,
    Ubicacion: true,
    Sub_Contratista: true,
    Responsable_AIA: true,
    Compromiso: true,
    Ejecutado_Real: true,
    Categoria_CNC: true,
    CNC: true,
    Observaciones_CNC: true,
  };

  var LEGACY_HIDDEN_COLUMN_INDEXES = [0, 2, 4, 5, 8, 18, 19, 20];

  var columnMinWidths = [
    34, 64, 34, 210, 36, 34, 120,
    120, 36, 50, 64, 72, 78, 72, 80,
    74, 54, 62, 36, 36, 36, 160, 84,
  ];
  var columnFloorWidths = [
    28, 54, 28, 160, 28, 28, 92,
    92, 28, 42, 54, 62, 68, 62, 70,
    64, 46, 52, 28, 28, 28, 122, 72,
  ];
  var columnMaxWidths = [
    90, 98, 120, 460, 120, 100, 238,
    238, 120, 86, 108, 122, 136, 122, 120,
    110, 84, 96, 170, 220, 260, 250, 130,
  ];
  var columnShrinkPriority = [
    21, 20, 19, 9, 6, 2, 0,
    13, 12, 14, 8, 7, 15, 16, 18,
    17, 11, 10, 5, 3, 1, 4, 22,
  ];

  var WEEKLY_ALERT_MODEL = {
    programacion: [
      {
        key: 'prog-bloqueo-critico-sin-compromiso',
        label: 'Ruta Crítica por Habilitar',
        className: 'ps-alert-critical-route',
        priority: 'p1',
        description: 'Actividad de ruta crítica con condiciones pendientes para comprometer.',
        action: 'Escalar hoy y cerrar las acciones de habilitación indicadas en la fila.',
      },
      {
        key: 'prog-condiciones-pendientes',
        label: 'Condiciones Pendientes',
        className: 'ps-alert-critical',
        priority: 'p1',
        description: 'La actividad requiere acciones de habilitación antes de confirmar compromiso.',
        action: 'Completar las acciones indicadas por restricción y volver a autoprogramar o validar la fila.',
      },
      {
        key: 'prog-sin-compromiso',
        label: 'Compromiso por Completar',
        className: 'ps-alert-critical',
        priority: 'p1',
        description: 'Actividad habilitada, pero sin compromiso semanal o sin asignaciones obligatorias.',
        action: 'Definir cantidad, Responsable AIA y Sub-Contratista antes del cierre semanal.',
      },
      {
        key: 'prog-lista-para-confirmar',
        label: 'Lista para Confirmar',
        className: 'ps-alert-control',
        priority: 'p3',
        description: 'Compromiso cargado con Responsable AIA y Sub-Contratista definidos.',
        action: 'Verificar recursos, asignaciones y confirmar en el comite semanal.',
      },
    ],
    calificacion: [
      {
        key: 'cal-incumplida-critica',
        label: 'Incumplida (Ruta Crítica)',
        className: 'ps-alert-critical-route',
        priority: 'p1',
        description: 'Compromiso no cumplido en ruta crítica.',
        action: 'Registrar CNC y activar recuperación diaria del camino crítico.',
      },
      {
        key: 'cal-incumplida',
        label: 'Incumplida',
        className: 'ps-alert-critical',
        priority: 'p1',
        description: 'Compromiso no cumplido.',
        action: 'Registrar CNC y ejecutar plan correctivo de corto plazo.',
      },
      {
        key: 'cal-sin-calificar',
        label: 'Sin Calificar',
        className: 'ps-alert-medium',
        priority: 'p2',
        description: 'Falta registrar ejecutado real.',
        action: 'Completar ejecutado real hoy para cerrar evaluación PAC.',
      },
      {
        key: 'cal-cumplida-control',
        label: 'Cumplida Control',
        className: 'ps-alert-control',
        priority: 'p3',
        description: 'Compromiso cumplido o superado.',
        action: 'Documentar práctica efectiva y sostener ritmo de producción.',
      },
    ],
  };

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

  function isSemanalEditorRole(permiso) {
    return permiso === 'A' || permiso === 'D' || permiso === 'R' || permiso === 'DCV';
  }

  function getMaxSemana() {
    var value = parseInt($('#Max_Semana').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function getSemanalConfirmada() {
    var value = parseInt($('#Semanal_Confirmada').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function getPhaseKey() {
    if (window.PSStateMachine && typeof window.PSStateMachine.getPhaseKey === 'function') {
      return window.PSStateMachine.getPhaseKey(getSemanalConfirmada());
    }
    return getSemanalConfirmada() === 1 ? 'calificacion' : 'programacion';
  }

  function isBlank(value) {
    if (window.PSStateMachine && typeof window.PSStateMachine.isBlank === 'function') {
      return window.PSStateMachine.isBlank(value);
    }

    if (value === null || value === undefined) {
      return true;
    }
    var text = String(value).trim();
    return !text || text.toLowerCase() === 'null';
  }

  function toNumber(value, fallback) {
    if (window.PSStateMachine && typeof window.PSStateMachine.toNumberOrNull === 'function') {
      var parsedState = window.PSStateMachine.toNumberOrNull(value);
      if (parsedState === null || parsedState === undefined) {
        return fallback;
      }
      return parsedState;
    }

    if (value === null || value === undefined || value === '') {
      return fallback;
    }

    var normalized = String(value).trim().replace(/\s+/g, '');
    if (!normalized || normalized.toLowerCase() === 'null') {
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

  function formatDecimalComma(value, decimals) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }
    return numeric.toFixed(decimals).replace('.', ',');
  }

  function formatPercent(value, decimals) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }
    return (numeric * 100).toFixed(decimals).replace('.', ',') + '%';
  }

  function normalizePositive(value) {
    var numeric = toNumber(value, null);
    if (numeric === null || numeric <= 0) {
      return null;
    }
    return Math.round((numeric + Number.EPSILON) * 10) / 10;
  }

  function normalizeNullableNumber(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return null;
    }
    return Math.round((numeric + Number.EPSILON) * 10) / 10;
  }

  function numberPayload(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }
    return numeric.toFixed(1);
  }

  function isUserAllowedToEdit() {
    var permiso = getPermiso();
    var semana = parseInt(getSemana(), 10);
    var maxSemana = getMaxSemana();

    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return isDirectorRole(permiso);
    }

    return isSemanalEditorRole(permiso);
  }

  function canManageToolbarActions() {
    return isUserAllowedToEdit();
  }

  function isPropReadOnly(prop) {
    if (!editableProps[prop]) {
      return true;
    }

    if (!isUserAllowedToEdit()) {
      return true;
    }

    if ((prop === 'Compromiso' || prop === 'Sub_Contratista' || prop === 'Responsable_AIA') && getSemanalConfirmada() === 1) {
      return true;
    }

    if (prop === 'Ejecutado_Real' && getSemanalConfirmada() !== 1) {
      return true;
    }

    return false;
  }

  function showLoading(show) {
    if (show) {
      $('#loading').show();
    } else {
      $('#loading').fadeOut(200);
    }
  }

  function showFeedback(type, message) {
    if (type === 'success') {
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
        window.AIA.Notice.badge('success', message);
      } else {
        // Fallback
        var $el = $('#save-status');
        if ($el.length) {
          $el.removeClass('badge-badge-hidden').text(message || 'Guardado').fadeIn(120);
          setTimeout(function () {
            $el.fadeOut(250, function() { $(this).addClass('badge-badge-hidden'); });
          }, 1800);
        }
      }
    } else if (type === 'warning') {
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.warning) {
        window.AIA.Notice.warning(message);
      } else if (typeof window.alert === 'function') {
        window.alert(message || 'Atencion');
      }
    } else {
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.error) {
        window.AIA.Notice.error(message || 'Error al guardar');
      } else if (typeof window.alert === 'function') {
        window.alert(message || 'Error al guardar');
      }
    }
  }

  function parseResponse(response) {
    if (response && typeof response === 'object') {
      return response;
    }

    if (typeof response === 'string') {
      try {
        return JSON.parse(response);
      } catch (error) {
        return { respuesta: response };
      }
    }

    return { respuesta: '' };
  }

  function escapeHtml(text) {
    return String(text === null || text === undefined ? '' : text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function stripHtmlTags(text) {
    var value = String(text === null || text === undefined ? '' : text);
    return value
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getPlainActivityLabel(value) {
    var plain = stripHtmlTags(value);
    return plain || 'Actividad';
  }

  function normalizeRestrictionRatio(value) {
    if (value === null || value === undefined || value === '') {
      return null;
    }

    var raw = String(value).trim();
    var upper = raw.toUpperCase();
    if (!raw || upper === 'N/A' || upper === 'NO APLICA') {
      return null;
    }

    var numeric = toNumber(raw.replace(/%/g, ''), null);
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
      return 0;
    }
    if (numeric > 1) {
      return 1;
    }
    return Math.round((numeric + Number.EPSILON) * 10000) / 10000;
  }

  var readinessActionProps = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'];
  var readinessActionLabels = {
    D_y_E: 'Diseños',
    Materiales: 'Materiales',
    MdeO: 'MO',
    Equipos: 'Equipos',
    Predecesora: 'Pred.',
  };
  var readinessDoneTexts = {
    D_y_E: 'Diseños listos para construcción.',
    Materiales: 'Materiales disponibles en el proyecto.',
    MdeO: 'Personal disponible en el proyecto.',
    Equipos: 'Equipos disponibles en el proyecto.',
    Predecesora: 'Predecesora con avance suficiente.',
  };
  var readinessActionMatrix = {
    D_y_E: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Solicitar diseños para construcción.' },
        { max: 0.5, text: 'Revisar diseños con dirección y residentes.' },
        { max: 1, text: 'Aprobar y entregar diseños a contratistas/maestros.' },
      ],
    },
    Materiales: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de aprovisionamiento.' },
        { max: 0.5, text: 'Pasar de plan de compras a plan de aprovisionamiento.' },
        { max: 1, text: 'Confirmar materiales disponibles en el proyecto.' },
      ],
    },
    MdeO: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de mano de obra.' },
        { max: 0.5, text: 'Ubicar y confirmar recurso de mano de obra.' },
        { max: 1, text: 'Movilizar personal al proyecto.' },
      ],
    },
    Equipos: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de equipos.' },
        { max: 0.5, text: 'Pasar de plan de compras a plan de aprovisionamiento de equipos.' },
        { max: 1, text: 'Confirmar equipos disponibles en el proyecto.' },
      ],
    },
    Predecesora: {
      threshold: 0.5,
      actions: [
        { max: 0.5, text: 'Recuperar o iniciar actividad predecesora.' },
      ],
    },
  };

  function getRestrictionSourceValue(row, prop) {
    if (!row) {
      return null;
    }
    var aliased = row['restr_' + prop];
    if (aliased !== undefined && aliased !== null && aliased !== '') {
      return aliased;
    }
    return row[prop];
  }

  function makeActionItem(label, text, value, status, key) {
    var iconMap = {
      done: '✓',
      pending: '!',
      partial: '…',
      critical: '!',
      conflict: '!',
      info: 'i',
      na: '—',
    };

    return {
      key: key || label,
      label: label,
      text: text,
      value: value || '',
      status: status || 'pending',
      icon: iconMap[status] || '!',
    };
  }

  function isOpenActionStatus(status) {
    return status === 'pending' || status === 'partial' || status === 'critical' || status === 'conflict';
  }

  function getActionValueDisplay(value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (!raw) {
      return '0,0%';
    }
    var upper = raw.toUpperCase();
    if (upper === 'N/A' || upper === 'NO APLICA') {
      return 'N/A';
    }
    return formatPercent(raw, 1) || raw;
  }

  function getActivaText(row) {
    return isBlank(row && row.Activa) ? '' : String(row.Activa).trim().toUpperCase();
  }

  function isInactiveByCnp(row) {
    var activa = getActivaText(row);
    return activa === '0' || activa === 'NO' || activa === 'N' || activa === 'FALSE';
  }

  function isManualActivity(row) {
    return getActivaText(row) === 'NA';
  }

  function getReadinessAction(prop, value) {
    var config = readinessActionMatrix[prop];
    if (!config) {
      return '';
    }

    var raw = String(value === null || value === undefined ? '' : value).trim();
    var upper = raw.toUpperCase();
    if (upper === 'N/A' || upper === 'NO APLICA') {
      return '';
    }

    var ratio = raw === '' ? 0 : normalizeRestrictionRatio(raw);
    if (ratio === null || ratio + 0.0001 >= config.threshold) {
      return '';
    }

    for (var i = 0; i < config.actions.length; i++) {
      if (ratio < config.actions[i].max) {
        return config.actions[i].text;
      }
    }

    return config.actions.length ? config.actions[config.actions.length - 1].text : '';
  }

  function getReadinessStatusItem(prop, row) {
    var config = readinessActionMatrix[prop];
    var label = readinessActionLabels[prop] || prop;
    var value = getRestrictionSourceValue(row, prop);
    var raw = String(value === null || value === undefined ? '' : value).trim();
    var upper = raw.toUpperCase();

    if (upper === 'N/A' || upper === 'NO APLICA') {
      return makeActionItem(label, 'No aplica para esta actividad.', 'N/A', 'na', prop);
    }

    var ratio = raw === '' ? 0 : normalizeRestrictionRatio(raw);
    if (!config || ratio === null) {
      return makeActionItem(label, 'Revisar valor de la condición.', raw || 'Sin dato', 'conflict', prop);
    }

    if (ratio + 0.0001 >= config.threshold) {
      var doneText = readinessDoneTexts[prop] || 'Condición lista.';
      if (prop === 'Predecesora' && ratio >= 0.999) {
        doneText = 'Predecesora terminada.';
      }
      return makeActionItem(label, doneText, getActionValueDisplay(value), 'done', prop);
    }

    return makeActionItem(label, getReadinessAction(prop, value), getActionValueDisplay(value), 'pending', prop);
  }

  function getReadinessActions(row) {
    return getReadinessActionItems(row).map(function (item) { return item.text; });
  }

  function getReadinessActionItems(row) {
    return getReadinessStatusItems(row).filter(function (item) {
      return isOpenActionStatus(item.status);
    });
  }

  function getReadinessStatusItems(row) {
    var items = [];
    for (var i = 0; i < readinessActionProps.length; i++) {
      var prop = readinessActionProps[i];
      items.push(getReadinessStatusItem(prop, row));
    }
    return items;
  }

  function getCommitmentActionItems(row) {
    return getCommitmentStatusItems(row).filter(function (item) {
      return isOpenActionStatus(item.status);
    });
  }

  function getCommitmentStatusItems(row) {
    var items = [];
    var compromiso = toNumber(row && row.Compromiso, null);
    if (compromiso === null || compromiso <= 0) {
      items.push(makeActionItem('Compromiso', 'Definir compromiso mayor a cero.', '', 'pending', 'Compromiso'));
    } else {
      items.push(makeActionItem('Compromiso', 'Compromiso definido.', formatDecimalComma(compromiso, 1), 'done', 'Compromiso'));
    }
    if (isBlank(row && row.Responsable_AIA)) {
      items.push(makeActionItem('Responsable', 'Asignar Responsable AIA.', '', 'pending', 'Responsable_AIA'));
    } else {
      items.push(makeActionItem('Responsable', 'Responsable AIA asignado.', row.Responsable_AIA, 'done', 'Responsable_AIA'));
    }
    if (isBlank(row && row.Sub_Contratista)) {
      items.push(makeActionItem('Subcontratista', 'Asignar Sub-Contratista.', '', 'pending', 'Sub_Contratista'));
    } else {
      items.push(makeActionItem('Subcontratista', 'Sub-Contratista asignado.', row.Sub_Contratista, 'done', 'Sub_Contratista'));
    }
    return items;
  }

  function getCommitmentActions(row) {
    return getCommitmentActionItems(row).map(function (item) { return item.text; });
  }

  function getCnpStatusItem(row) {
    var hasCategory = !isBlank(row && row.Categoria_CNP);
    var hasCause = !isBlank(row && row.CNP);
    var hasObservations = !isBlank(row && row.Observaciones_CNP);
    var hasAny = hasCategory || hasCause || hasObservations;

    if (isManualActivity(row)) {
      return makeActionItem('CNP', 'CNP no requerida para actividad manual.', '', 'na', 'CNP');
    }

    if (isInactiveByCnp(row)) {
      if (hasCategory && hasCause) {
        return makeActionItem('CNP', hasObservations ? 'CNP documentada.' : 'CNP registrada.', row.CNP || '', 'done', 'CNP');
      }
      if (hasAny) {
        return makeActionItem('CNP', 'Completar categoría y causa de no programación.', row.CNP || row.Categoria_CNP || '', 'partial', 'CNP');
      }
      return makeActionItem('CNP', 'Registrar CNP.', '', 'pending', 'CNP');
    }

    if (hasAny) {
      return makeActionItem('CNP', 'Revisar: actividad activa tiene CNP asignada.', row.CNP || row.Categoria_CNP || '', 'conflict', 'CNP');
    }

    return makeActionItem('CNP', 'CNP no requerida.', '', 'na', 'CNP');
  }

  function getCncStatusItem(row) {
    var hasCategory = !isBlank(row && row.Categoria_CNC);
    var hasCause = !isBlank(row && row.CNC);
    var hasObservations = !isBlank(row && row.Observaciones_CNC);
    var hasAny = hasCategory || hasCause || hasObservations;
    var real = toNumber(row && row.Ejecutado_Real, null);
    var needsCnc = window.PSStateMachine && typeof window.PSStateMachine.requiresCnc === 'function'
      ? window.PSStateMachine.requiresCnc(row && row.Compromiso, row && row.Ejecutado_Real)
      : false;
    var critical = toNumber(row && row.Critica, 0) >= 1;

    if (real === null) {
      return makeActionItem('CNC', 'CNC se evalúa después de registrar ejecutado real.', '', 'na', 'CNC');
    }

    if (needsCnc) {
      if (hasCategory && hasCause) {
        return makeActionItem('CNC', hasObservations ? 'CNC documentada.' : 'CNC registrada.', row.CNC || '', 'done', 'CNC');
      }
      if (hasAny) {
        return makeActionItem('CNC', 'Completar causa de no cumplimiento.', row.CNC || row.Categoria_CNC || '', 'partial', 'CNC');
      }
      return makeActionItem('CNC', critical ? 'Registrar CNC hoy y activar recuperación.' : 'Registrar CNC y plan correctivo.', '', critical ? 'critical' : 'pending', 'CNC');
    }

    if (hasAny) {
      return makeActionItem('CNC', 'Revisar CNC: no parece requerida porque la actividad está cumplida.', row.CNC || row.Categoria_CNC || '', 'conflict', 'CNC');
    }

    return makeActionItem('CNC', 'CNC no requerida.', '', 'na', 'CNC');
  }

  function getRealStatusItem(row) {
    var real = toNumber(row && row.Ejecutado_Real, null);
    if (real === null) {
      return makeActionItem('Real', 'Registrar ejecutado real.', '', 'pending', 'Ejecutado_Real');
    }
    return makeActionItem('Real', 'Ejecutado real registrado.', formatDecimalComma(real, 1), 'done', 'Ejecutado_Real');
  }

  function getOperationalActionItems(row) {
    if (!row) {
      return [];
    }
    if (getStateKey(row) === 'ps-no-activa') {
      var cnpCompact = getCnpStatusItem(row);
      return isOpenActionStatus(cnpCompact.status) || cnpCompact.status === 'done' ? [cnpCompact] : [];
    }
    if (weeklyPhaseKey === 'calificacion') {
      var cnpStatus = getCnpStatusItem(row);
      var calItems = [getRealStatusItem(row), getCncStatusItem(row)];
      if (isOpenActionStatus(cnpStatus.status) || cnpStatus.status === 'done') {
        calItems.push(cnpStatus);
      }
      return calItems.filter(function (item) {
        return isOpenActionStatus(item.status) || ((item.key === 'CNC' || item.key === 'CNP') && item.status === 'done');
      });
    }

    return getReadinessActionItems(row).concat(getCommitmentActionItems(row), [getCnpStatusItem(row)].filter(function (item) {
      return isOpenActionStatus(item.status) || item.status === 'done';
    }));
  }

  function getOperationalDetailItems(row) {
    if (!row) {
      return [];
    }

    if (getStateKey(row) === 'ps-no-activa') {
      return [getCnpStatusItem(row)];
    }

    if (weeklyPhaseKey === 'calificacion') {
      return [getRealStatusItem(row), getCncStatusItem(row), getCnpStatusItem(row)];
    }

    return getReadinessStatusItems(row).concat(getCommitmentStatusItems(row), [getCnpStatusItem(row)]);
  }

  function getOperationalActions(row) {
    return getOperationalActionItems(row).map(function (item) { return item.text; });
  }

  function getStateDisplayText(row) {
    var label = getStateLabelByKey(getStateKey(row));
    var actions = getOperationalActions(row);
    if (!actions.length) {
      return label;
    }
    var actionPrefix = weeklyPhaseKey === 'calificacion' ? 'Acción: ' : 'Acciones de habilitación: ';
    return label + '\n' + actionPrefix + actions.join('; ');
  }

  function getStateView(row) {
    var compactItems = getOperationalActionItems(row);
    var detailItems = getOperationalDetailItems(row);
    return {
      label: getStateLabelByKey(getStateKey(row)),
      state: getStateKey(row),
      actionItems: detailItems,
      compactItems: compactItems,
      actions: compactItems.map(function (item) { return item.text; }),
      activity: getPlainActivityLabel(row && row.Actividad),
      id: row && row.Id,
      phase: weeklyPhaseKey,
    };
  }

  function renderStatePills(actionItems, visibleLimit) {
    var items = Array.isArray(actionItems) ? actionItems : [];
    var limit = visibleLimit || 2;
    var html = '';
    for (var i = 0; i < Math.min(items.length, limit); i++) {
      html += '<span class="ops-state-pill is-' + escapeHtml(items[i].status || 'pending') + '" title="' + escapeHtml(items[i].text) + '">'
        + '<span class="ops-state-pill-icon">' + escapeHtml(items[i].icon || '!') + '</span>'
        + escapeHtml(items[i].label) + '</span>';
    }
    if (items.length > limit) {
      html += '<span class="ops-state-more">+' + (items.length - limit) + '</span>';
    }
    return html;
  }

  function renderOperationalStateCell(view) {
    var count = view.compactItems.length;
    var countBadge = count > 0 ? '<span class="ops-state-count">' + count + '</span>' : '';
    var pills = count > 0 ? '<span class="ops-state-pills">' + renderStatePills(view.compactItems, 2) + '</span>' : '';

    return '<button type="button" class="ops-state-zoom" aria-label="Ver detalle operativo">'
      + '<span class="ops-state-topline"><span class="ops-state-chip">' + escapeHtml(view.label) + '</span>' + countBadge + '</span>'
      + pills
      + '</button>';
  }

  function ensureOperationalStateDrawer() {
    if (document.getElementById('psOperationalStateDrawer')) {
      return $('#psOperationalStateDrawer');
    }

    var html = '<div id="psOperationalStateDrawer" class="ops-state-drawer" aria-hidden="true">'
      + '<div class="ops-state-backdrop" data-ops-close="1"></div>'
      + '<aside class="ops-state-panel" role="dialog" aria-modal="false" aria-labelledby="psOpsDrawerTitle">'
      + '<div class="ops-state-panel-header">'
      + '<div><span class="ops-state-eyebrow">Detalle operativo</span><h5 id="psOpsDrawerTitle">Estado operativo</h5></div>'
      + '<button type="button" class="ops-state-close" data-ops-close="1" aria-label="Cerrar">&times;</button>'
      + '</div>'
      + '<div class="ops-state-panel-body"></div>'
      + '</aside>'
      + '</div>';
    $('body').append(html);
    return $('#psOperationalStateDrawer');
  }

  function renderOperationalStateDrawerBody(view) {
    var activity = view.activity || 'Actividad';
    var id = view.id ? ('<span class="ops-state-activity-id">' + escapeHtml(view.id) + '</span>') : '';
    var actionTitle = view.phase === 'calificacion' ? 'Acciones de calificación' : 'Acciones de habilitación';
    var html = '<div class="ops-state-drawer-state"><span class="ops-state-chip">' + escapeHtml(view.label) + '</span>';
    if (view.compactItems.length) {
      html += '<span class="ops-state-count">' + view.compactItems.length + ' acciones</span>';
    }
    html += '</div>';
    html += '<div class="ops-state-activity">' + id + '<strong>' + escapeHtml(activity) + '</strong></div>';

    if (!view.actionItems.length) {
      html += '<div class="ops-state-empty-detail">Sin acciones pendientes.</div>';
      return html;
    }

    html += '<h6>' + escapeHtml(actionTitle) + '</h6><ul class="ops-state-action-list">';
    for (var i = 0; i < view.actionItems.length; i++) {
      var item = view.actionItems[i];
      html += '<li class="is-' + escapeHtml(item.status || 'pending') + '"><span class="ops-state-action-label"><span class="ops-state-action-icon">' + escapeHtml(item.icon || '!') + '</span>' + escapeHtml(item.label) + '</span>'
        + '<span class="ops-state-action-text">' + escapeHtml(item.text) + '</span>'
        + '<span class="ops-state-action-value">' + escapeHtml(item.value || '') + '</span></li>';
    }
    html += '</ul>';
    return html;
  }

  function openOperationalStateDrawer(rowData) {
    var view = getStateView(rowData || {});
    var $drawer = ensureOperationalStateDrawer();
    $drawer.find('#psOpsDrawerTitle').text(view.label);
    $drawer.find('.ops-state-panel-body').html(renderOperationalStateDrawerBody(view));
    $drawer.addClass('is-open').attr('aria-hidden', 'false');
  }

  function closeOperationalStateDrawer() {
    $('#psOperationalStateDrawer').removeClass('is-open').attr('aria-hidden', 'true');
  }

  function bindOperationalStateDrawer() {
    $('#hot-container').off('click.psOpsState').on('click.psOpsState', '.ops-state-zoom', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var visualRow = parseInt($(this).data('row'), 10);
      var rowData = hot && Number.isInteger(visualRow) ? hot.getSourceDataAtRow(visualRow) : null;
      openOperationalStateDrawer(rowData || {});
    });

    $(document)
      .off('click.psOpsStateClose')
      .on('click.psOpsStateClose', '#psOperationalStateDrawer [data-ops-close="1"]', closeOperationalStateDrawer)
      .off('keydown.psOpsStateClose')
      .on('keydown.psOpsStateClose', function (event) {
        if (event.key === 'Escape') {
          closeOperationalStateDrawer();
        }
      });
  }

  function getAlertConfig(phaseKey) {
    return WEEKLY_ALERT_MODEL[phaseKey] || WEEKLY_ALERT_MODEL.programacion;
  }

  function getAlertClassByState(stateKey) {
    if (!stateKey || stateKey === 'ps-no-activa') {
      return 'ps-alert-neutral';
    }

    var phaseConfig = getAlertConfig(weeklyPhaseKey);
    for (var i = 0; i < phaseConfig.length; i++) {
      if (phaseConfig[i].key === stateKey) {
        return phaseConfig[i].className;
      }
    }

    var phases = ['programacion', 'calificacion'];
    for (var p = 0; p < phases.length; p++) {
      var config = getAlertConfig(phases[p]);
      for (var j = 0; j < config.length; j++) {
        if (config[j].key === stateKey) {
          return config[j].className;
        }
      }
    }

    return 'ps-alert-neutral';
  }

  function getStateLabelByKey(stateKey) {
    if (!stateKey || stateKey === 'ps-no-activa') {
      return 'Programada Manualmente';
    }

    var phases = ['programacion', 'calificacion'];
    for (var p = 0; p < phases.length; p++) {
      var config = getAlertConfig(phases[p]);
      for (var i = 0; i < config.length; i++) {
        if (config[i].key === stateKey) {
          return config[i].label;
        }
      }
    }

    return 'Control';
  }

  function getWeeklyPhaseInfo(phaseKey, fechaCierreCompromisos) {
    if (phaseKey === 'calificacion') {
      var detail = 'Compromisos confirmados';
      if (!isBlank(fechaCierreCompromisos)) {
        detail += ' el ' + fechaCierreCompromisos;
      }
      detail += '. Registre y revise la calificación de actividades.';

      return {
        key: 'calificacion',
        title: 'Fase: Calificación de Actividades',
        detail: detail,
        mobileLabel: 'Calificación',
      };
    }

    return {
      key: 'programacion',
      title: 'Fase: Programación de Compromisos',
      detail: 'Compromisos abiertos. Defina cantidades, revise riesgos y confirme la semana.',
      mobileLabel: 'Programación',
    };
  }

  function ensureContextPhaseShell() {
    var $contextContainer = $('.context-bar .container-fluid.d-flex.align-items-center.justify-content-between');
    if (!$contextContainer.length) {
      return null;
    }

    $contextContainer.addClass('context-has-weekly-phase');

    var $breadcrumb = $contextContainer.find('.context-breadcrumb').first();
    var $phaseWrap = $contextContainer.find('#ctxWeeklyPhase').first();
    if (!$phaseWrap.length) {
      $phaseWrap = $('<div id="ctxWeeklyPhase" class="context-weekly-phase context-weekly-phase--programacion"><strong class="ps-weekly-phase-title">Fase: Programación de Compromisos</strong></div>');
      if ($breadcrumb.length) {
        $phaseWrap.insertAfter($breadcrumb);
      } else {
        $contextContainer.prepend($phaseWrap);
      }
    }

    var $weekInfo = $contextContainer.find('.context-week-info').first();
    var $rightWrap = $contextContainer.find('.context-right-info').first();
    if (!$rightWrap.length) {
      $rightWrap = $('<div class="context-right-info d-flex align-items-center ml-auto"></div>');
      if ($weekInfo.length) {
        $weekInfo.appendTo($rightWrap);
      }
      $contextContainer.append($rightWrap);
    }

    return {
      contextContainer: $contextContainer,
      phaseWrap: $phaseWrap,
      rightWrap: $rightWrap,
    };
  }

  function syncContextPhaseIndicator(phaseKey, fechaCierreCompromisos) {
    var shell = ensureContextPhaseShell();
    if (!shell) {
      return;
    }

    var info = getWeeklyPhaseInfo(phaseKey, fechaCierreCompromisos);
    shell.phaseWrap
      .removeClass('context-weekly-phase--programacion context-weekly-phase--calificacion')
      .addClass('context-weekly-phase--' + info.key)
      .find('.ps-weekly-phase-title')
      .text(info.title);

    var $fechaCierre = $('#textoFechaCierreCompromisos');
    if ($fechaCierre.length) {
      var cierreText = String($fechaCierre.text() || '').trim();
      if (cierreText) {
        $fechaCierre.removeClass('d-none').addClass('context-cierre-info text-muted ml-2');
        $fechaCierre.appendTo(shell.rightWrap);
      } else {
        $fechaCierre.addClass('d-none');
      }
    }
  }

  function fitActionsRowSingleLine() {
    return; // DISABLED: Responsive scaling is now perfectly handled by CSS @media queries
    var shell = document.querySelector('.ps-hot-toolbar-shell');
    var row = document.querySelector('.ps-actions-row');
    if (!shell || !row) {
      return;
    }

    var actions = row.querySelector('.ps-hot-toolbar-actions');
    var badges = row.querySelector('.ps-hot-status-badges');
    var switcher = row.querySelector('.ps-module-switcher');
    var mobileToggle = row.querySelector('.pdc-mobile-toggle');
    var minScale = 0.22;
    var maxScale = 1;
    var tolerance = 2;
    var scale = maxScale;
    var rowStyle = window.getComputedStyle(row);
    var gap = parseFloat(rowStyle.columnGap || rowStyle.gap || '0') || 0;

    var setScale = function (value) {
      shell.style.setProperty('--ps-hot-scale', String(value));
    };

    var getElementWidth = function (element) {
      if (!element) {
        return 0;
      }

      var style = window.getComputedStyle(element);
      if (style.display === 'none' || style.visibility === 'hidden') {
        return 0;
      }

      var marginLeft = parseFloat(style.marginLeft || '0') || 0;
      var marginRight = parseFloat(style.marginRight || '0') || 0;
      var width = Math.max(element.scrollWidth || 0, element.offsetWidth || 0, element.clientWidth || 0);

      return Math.ceil(width + marginLeft + marginRight);
    };

    var getRequiredWidth = function () {
      var parts = [actions, badges, switcher, mobileToggle];
      var used = [];

      for (var i = 0; i < parts.length; i++) {
        var width = getElementWidth(parts[i]);
        if (width > 0) {
          used.push(width);
        }
      }

      if (!used.length) {
        return 0;
      }

      var total = 0;
      for (var j = 0; j < used.length; j++) {
        total += used[j];
      }

      if (used.length > 1 && gap > 0) {
        total += gap * (used.length - 1);
      }

      return total;
    };

    var fits = function () {
      return getRequiredWidth() <= (row.clientWidth + tolerance);
    };

    row.classList.remove('ps-actions-stacked');
    setScale(scale.toFixed(3));

    var guard = 0;
    while (!fits() && scale > minScale && guard < 80) {
      scale = Math.max(minScale, scale - 0.02);
      setScale(scale.toFixed(3));
      guard += 1;
    }

    if (!fits()) {
      setScale(minScale.toFixed(3));
      row.classList.add('ps-actions-stacked');
      return;
    }

    guard = 0;
    while (scale < maxScale && guard < 80) {
      var next = Math.min(maxScale, scale + 0.01);
      setScale(next.toFixed(3));
      if (!fits()) {
        setScale(scale.toFixed(3));
        break;
      }
      scale = next;
      guard += 1;
    }
  }

  function scheduleActionsRowFit(delay) {
    clearTimeout(toolbarFitTimer);
    toolbarFitTimer = setTimeout(function () {
      fitActionsRowSingleLine();
    }, Number.isFinite(delay) ? delay : 0);
  }

  function isDropdownCellAt(visualRow, visualCol) {
    if (!hot) {
      return false;
    }

    var settings = hot.getSettings() || {};
    var columns = Array.isArray(settings.columns) ? settings.columns : [];
    var columnMeta = columns[visualCol] || {};
    var cellMeta = hot.getCellMeta(visualRow, visualCol) || {};
    var prop = columnMeta.data;

    var isDropdown = cellMeta.type === 'dropdown' || columnMeta.type === 'dropdown';
    if (!isDropdown) {
      return false;
    }

    if (cellMeta.readOnly === true) {
      return false;
    }

    if (prop && isPropReadOnly(prop)) {
      return false;
    }

    return true;
  }

  function tryAutoOpenDropdownSelection() {
    if (!hot) {
      return;
    }

    var selection = hot.getSelectedLast();
    if (!Array.isArray(selection) || selection.length < 2) {
      return;
    }

    var visualRow = selection[0];
    var visualCol = selection[1];

    if (!Number.isInteger(visualRow) || !Number.isInteger(visualCol)) {
      return;
    }

    if (!isDropdownCellAt(visualRow, visualCol)) {
      return;
    }

    hot.listen();

    var activeEditor = hot.getActiveEditor();
    if (!activeEditor || !activeEditor.isOpened || !activeEditor.isOpened()) {
      hot.selectCell(visualRow, visualCol, visualRow, visualCol, false, false);
      activeEditor = hot.getActiveEditor();
    }

    if (activeEditor && typeof activeEditor.beginEditing === 'function') {
      activeEditor.beginEditing('');
      if (typeof activeEditor.open === 'function') {
        activeEditor.open();
      }
    }
  }

  function getStateKey(row) {
    if (window.PSStateMachine && typeof window.PSStateMachine.classifyState === 'function') {
      return window.PSStateMachine.classifyState(row || {}, weeklyPhaseKey);
    }
    return 'ps-no-activa';
  }

  function isLegacyHiddenColumn(index) {
    return LEGACY_HIDDEN_COLUMN_INDEXES.indexOf(index) > -1;
  }

  function getVisibleColumnIndexes(columnCount) {
    var visible = [];
    for (var i = 0; i < columnCount; i++) {
      if (!isLegacyHiddenColumn(i)) {
        visible.push(i);
      }
    }
    return visible;
  }

  function sumWidths(widths) {
    var total = 0;
    for (var i = 0; i < widths.length; i++) {
      total += Number(widths[i]) || 0;
    }
    return total;
  }

  function sumWidthsByIndexes(widths, indexes) {
    if (!Array.isArray(indexes) || indexes.length === 0) {
      return sumWidths(widths);
    }

    var total = 0;
    for (var i = 0; i < indexes.length; i++) {
      var index = indexes[i];
      total += Number(widths[index]) || 0;
    }

    return total;
  }

  function getColumnMinWidth(index) {
    var value = Number(columnMinWidths[index]);
    if (Number.isFinite(value) && value > 20) {
      return value;
    }
    return 48;
  }

  function getColumnFloorWidth(index) {
    var value = Number(columnFloorWidths[index]);
    if (Number.isFinite(value) && value > 20) {
      return value;
    }
    return 36;
  }

  function getColumnMaxWidth(index) {
    var value = Number(columnMaxWidths[index]);
    if (Number.isFinite(value) && value > 48) {
      return value;
    }
    return 260;
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

  function buildResizePriority(activeIndexes, reverse) {
    var map = {};
    var priority = [];
    var ordered = reverse ? columnShrinkPriority.slice().reverse() : columnShrinkPriority.slice();

    for (var i = 0; i < activeIndexes.length; i++) {
      map[activeIndexes[i]] = true;
    }

    for (var j = 0; j < ordered.length; j++) {
      var idx = ordered[j];
      if (map[idx]) {
        priority.push(idx);
        delete map[idx];
      }
    }

    for (var k = 0; k < activeIndexes.length; k++) {
      var index = activeIndexes[k];
      if (map[index]) {
        priority.push(index);
      }
    }

    return priority;
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

    return Math.max(320, width - 20);
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
        width = (header.length * 8) + 26;
      }

      width = Math.ceil(width + 8);
      if (width < min) {
        width = min;
      }
      if (width > max) {
        width = max;
      }

      if (isLegacyHiddenColumn(col)) {
        width = getColumnFloorWidth(col);
      }

      widths.push(width);
    }

    return widths;
  }

  function reduceWidthsToTarget(widths, targetWidth, lowerBounds, activeIndexes) {
    var reducedWidths = widths.slice();
    var total = sumWidthsByIndexes(reducedWidths, activeIndexes);
    if (total <= targetWidth) {
      return reducedWidths;
    }

    var excess = total - targetWidth;
    var capacities = [];
    var totalCapacity = 0;

    for (var i = 0; i < activeIndexes.length; i++) {
      var col = activeIndexes[i];
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
      for (var c = 0; c < activeIndexes.length; c++) {
        var indexCap = activeIndexes[c];
        reducedWidths[indexCap] = Number(lowerBounds[indexCap]) || 20;
      }
      return reducedWidths;
    }

    var reduced = 0;
    for (var j = 0; j < activeIndexes.length; j++) {
      var activeIndex = activeIndexes[j];
      var capacity = capacities[activeIndex];
      if (capacity <= 0) {
        continue;
      }

      var step = Math.floor((excess * capacity) / totalCapacity);
      if (step > capacity) {
        step = capacity;
      }
      if (step > 0) {
        reducedWidths[activeIndex] -= step;
        reduced += step;
      }
    }

    var remainder = excess - reduced;
    var guard = 0;
    var priority = buildResizePriority(activeIndexes, false);
    while (remainder > 0 && guard < 5000) {
      for (var p = 0; p < priority.length && remainder > 0; p++) {
        var index = priority[p];
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

  function expandWidthsToTarget(widths, targetWidth, upperBounds, activeIndexes) {
    var expandedWidths = widths.slice();
    var total = sumWidthsByIndexes(expandedWidths, activeIndexes);
    if (total >= targetWidth) {
      return expandedWidths;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var priority = buildResizePriority(activeIndexes, true);

    while (remainder > 0 && guard < 6000) {
      var grew = false;
      for (var i = 0; i < priority.length && remainder > 0; i++) {
        var index = priority[i];
        var upperBound = Number(upperBounds[index]);
        if (!Number.isFinite(upperBound) || upperBound <= 0) {
          upperBound = getColumnMaxWidth(index);
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

  function forceFillWidthsToTarget(widths, targetWidth, activeIndexes) {
    var filledWidths = widths.slice();
    var total = sumWidthsByIndexes(filledWidths, activeIndexes);
    if (total >= targetWidth) {
      return filledWidths;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var priority = buildResizePriority(activeIndexes, true);

    while (remainder > 0 && guard < 7000) {
      for (var i = 0; i < priority.length && remainder > 0; i++) {
        var index = priority[i];
        filledWidths[index] += 1;
        remainder -= 1;
      }
      guard += 1;
    }

    return filledWidths;
  }

  function constrainColumnWidthsToContainer(widths, targetWidth, activeIndexes) {
    var constrained = reduceWidthsToTarget(widths, targetWidth, columnMinWidths, activeIndexes);
    if (sumWidthsByIndexes(constrained, activeIndexes) > targetWidth) {
      constrained = reduceWidthsToTarget(constrained, targetWidth, columnFloorWidths, activeIndexes);
    }

    if (sumWidthsByIndexes(constrained, activeIndexes) < targetWidth) {
      constrained = expandWidthsToTarget(constrained, targetWidth, columnMaxWidths, activeIndexes);
    }

    if (sumWidthsByIndexes(constrained, activeIndexes) < targetWidth) {
      constrained = forceFillWidthsToTarget(constrained, targetWidth, activeIndexes);
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

    var visibleIndexes = getVisibleColumnIndexes(columnCount);
    if (visibleIndexes.length === 0) {
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
    var constrained = constrainColumnWidthsToContainer(baseWidths, containerWidth, visibleIndexes);

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

      applyLegacyColumnVisibility();
      applyResponsiveColumnWidths(Boolean(force));
      hot.render();

      if (viewportState) {
        setTimeout(function () {
          restoreViewportState(viewportState);
        }, 0);
      }
    }, Number.isFinite(delay) ? delay : 24);
  }

  function getLegacyHiddenColumnsConfig() {
    return {
      columns: LEGACY_HIDDEN_COLUMN_INDEXES.slice(),
      indicators: false,
      copyPasteEnabled: false,
    };
  }

  function applyLegacyColumnVisibility() {
    if (!hot) {
      return;
    }

    var settings = hot.getSettings() || {};
    var currentHidden = settings.hiddenColumns && Array.isArray(settings.hiddenColumns.columns)
      ? settings.hiddenColumns.columns
      : [];

    if (arraysEqualNumbers(currentHidden, LEGACY_HIDDEN_COLUMN_INDEXES)) {
      return;
    }

    hot.updateSettings({
      hiddenColumns: getLegacyHiddenColumnsConfig(),
    });
  }

  function calculateSuggested(row) {
    var ppto = toNumber(row.cantidad_ppto, null);
    var proyeccion = toNumber(row.proyeccionSemana, 0);
    var suggested;

    if (ppto === null || ppto <= 0) {
      suggested = proyeccion * 100;
    } else {
      suggested = proyeccion * ppto;
    }

    if (!Number.isFinite(suggested) || suggested < 0) {
      suggested = 0;
    }

    return Math.round((suggested + Number.EPSILON) * 10) / 10;
  }

  function mapRows(rows) {
    var output = [];
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i] || {};
      row.cantidad_sugerida_auto = calculateSuggested(row);
      row.estado_operativo = getStateDisplayText(row);
      output.push(row);
    }
    return output;
  }

  function requestList() {
    var db = getDb();
    var semana = getSemana();

    $.ajax({
      method: 'GET',
      url: '/api/semanal/list?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      dataType: 'json',
      cache: false,
    }).done(function (response) {
      masterData = mapRows(response && Array.isArray(response.data) ? response.data : []);
      applyFiltersAndRender();
      showLoading(false);
      syncPhaseUI();
    }).fail(function () {
      showLoading(false);
      showFeedback('error', 'Error cargando datos');
    });
  }

  function loadData() {
    weeklyPhaseKey = getPhaseKey();
    showLoading(true);
    requestList();
  }

  function scheduleReload() {
    clearTimeout(reloadTimer);
    reloadTimer = setTimeout(function () {
      loadData();
    }, 750);
  }

  function normalizeCellValue(prop, value) {
    if (prop === 'Compromiso') {
      var compromiso = normalizePositive(value);
      if (compromiso === null) {
        return { valid: false, value: value, error: 'Compromiso inválido (debe ser > 0)' };
      }
      return { valid: true, value: compromiso };
    }

    if (prop === 'Ejecutado_Real') {
      var real = normalizeNullableNumber(value);
      if (real === null) {
        return { valid: true, value: '' };
      }
      if (real < 0) {
        return { valid: false, value: value, error: 'Ejecutado real inválido' };
      }
      return { valid: true, value: real };
    }

    if (prop === 'Sub_Contratista' || prop === 'Responsable_AIA' || prop === 'Descripcion' || prop === 'Ubicacion' || prop === 'Categoria_CNC' || prop === 'CNC' || prop === 'Observaciones_CNC') {
      return { valid: true, value: String(value === null || value === undefined ? '' : value).trim() };
    }

    return { valid: true, value: value };
  }

  function buildPayload(row, editedProp, overrides) {
    var rawData = $.extend({}, row, overrides || {});
    var compromiso = normalizePositive(rawData.Compromiso);
    
    if (editedProp === 'Compromiso' && compromiso === null) {
      return { valid: false, error: 'Compromiso inválido (debe ser > 0)' };
    }

    var otrosSumaC = 0, otrosSumaR = 0;
    for (var i = 0; i < masterData.length; i++) {
      if (masterData[i].Id === rawData.Id && masterData[i].Consecutivo !== rawData.Consecutivo) {
        otrosSumaC += toNumber(masterData[i].Compromiso, 0);
        otrosSumaR += toNumber(masterData[i].Ejecutado_Real, 0);
      }
    }

    // Validar techo: Compromiso + Ejecutado_Actual no debe superar el límite
    if (editedProp === 'Compromiso' && compromiso !== null) {
      var ejecutadoRatio = toNumber(rawData.Ejecutado, 0); // 0-1 decimal
      var unidad = String(rawData.Unidad || '%').trim();
      var ppto = toNumber(rawData.cantidad_ppto, null);

      if (unidad === '%') {
        var ejecutadoPct = ejecutadoRatio * 100;
        if (ejecutadoPct + otrosSumaC + compromiso > 100) {
          var maxComp = Math.max(0, 100 - ejecutadoPct - otrosSumaC);
          return { valid: false, error: 'Suma compromisos supera 100%. Límite: ' + maxComp.toFixed(1) + '%' };
        }
      } else if (ppto !== null && ppto > 0) {
        var ejecutadoCant = ejecutadoRatio * ppto;
        if (ejecutadoCant + otrosSumaC + compromiso > ppto) {
          var maxComp2 = Math.max(0, ppto - ejecutadoCant - otrosSumaC);
          return { valid: false, error: 'Suma compromisos supera Cant. PPTO. Límite: ' + maxComp2.toFixed(1) };
        }
      }
    }

    var realActual = toNumber(rawData.Ejecutado_Real, null);
    if (editedProp === 'Ejecutado_Real' && realActual !== null) {
      var ejecutadoRatio = toNumber(rawData.Ejecutado, 0);
      var unidad = String(rawData.Unidad || '%').trim();
      var ppto = toNumber(rawData.cantidad_ppto, null);

      if (unidad === '%') {
        var ejecutadoPct = ejecutadoRatio * 100;
        if (ejecutadoPct + otrosSumaR + realActual > 100) {
          var maxReal = Math.max(0, 100 - ejecutadoPct - otrosSumaR);
          return { valid: false, error: 'Suma avance real supera 100%. Límite: ' + maxReal.toFixed(1) + '%' };
        }
      } else if (ppto !== null && ppto > 0) {
        var ejecutadoCant = ejecutadoRatio * ppto;
        if (ejecutadoCant + otrosSumaR + realActual > ppto) {
          var maxReal2 = Math.max(0, ppto - ejecutadoCant - otrosSumaR);
          return { valid: false, error: 'Suma avance real supera Cant. PPTO. Límite: ' + maxReal2.toFixed(1) };
        }
      }
    }

    // Guardia CNC: bloquear envío si Real < Compromiso sin justificación
    var realCheck = toNumber(rawData.Ejecutado_Real, null);
    if (compromiso !== null && realCheck !== null && realCheck < compromiso) {
      var _cncCheck = String(rawData.CNC || '').trim();
      var _isStandard = _cncCheck && _cncCheck !== 'Otra' && _cncCheck !== 'Otra...' && _cncCheck !== 'Otros' && _cncCheck !== 'Otros...';
      var _obsCheck = String(rawData.Observaciones_CNC || '').trim();

      if (isBlank(rawData.Categoria_CNC)) {
        return { valid: false, error: 'Actividad incumplida: debe registrar Categoría de Causa.' };
      }
      if (!_isStandard && !_obsCheck) {
        return { valid: false, error: 'Actividad incumplida: debe registrar Causa CNC u Observaciones justificativas.' };
      }
    }

    return {
      valid: true,
      data: {
        opcion: 'modificar',
        Id: rawData.Consecutivo,
        semana: getSemana(),
        Descripcion: rawData.Descripcion || '',
        Ubicacion: rawData.Ubicacion || '',
        Sub_Contratista: rawData.Sub_Contratista || '',
        Responsable_AIA: rawData.Responsable_AIA || '',
        Empresa: rawData.Empresa || '',
        Unidad: rawData.Unidad || '%',
        Compromiso: compromiso === null ? '' : numberPayload(compromiso),
        Cantidad_Sugerida: numberPayload(rawData.cantidad_sugerida_auto),
        Real: numberPayload(rawData.Ejecutado_Real),
        Categoria_CNC: rawData.Categoria_CNC || '',
        CNC: rawData.CNC || '',
        Observaciones_CNC: rawData.Observaciones_CNC || '',
        Rendimientos: rawData.Rendimientos || '',
      },
    };
  }

  function revertCell(visualRow, prop, oldValue) {
    var col = hot.propToCol(prop);
    if (col >= 0) {
      hot.setDataAtCell(visualRow, col, oldValue, 'revert');
    }
  }

  function saveRow(visualRow, prop, oldValue, overrides) {
    var db = getDb();
    var physicalRow = hot.toPhysicalRow(visualRow);
    var row = hot.getSourceDataAtRow(physicalRow);

    var payload = buildPayload(row || {}, prop, overrides || {});
    if (!payload.valid) {
      revertCell(visualRow, prop, oldValue);
      showFeedback('warning', payload.error); // Warning en lugar de Error para validaciones de negocio
      
      var colIndex = hot.propToCol(prop);
      var td = hot.getCell(visualRow, colIndex);
      if (td) {
        td.classList.remove('ps-cell-shake');
        void td.offsetWidth; // Disparar reflow para reiniciar animación CSS
        td.classList.add('ps-cell-shake');
      }
      return;
    }

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: payload.data,
    }).done(function (rawResponse) {
      var response = parseResponse(rawResponse);
      if (response && response.respuesta === 'BIEN') {
        if (row) {
          // Sincronizar row con los overrides enviados al backend
          if (overrides) {
            $.extend(row, overrides);
          }
          row.cantidad_sugerida_auto = calculateSuggested(row);
          row.estado_operativo = getStateDisplayText(row);
          // Forzar actualización visual de campos computados
          hot.setDataAtRowProp(visualRow, 'estado_operativo', row.estado_operativo, 'internal-update');
          hot.setDataAtRowProp(visualRow, 'cantidad_sugerida_auto', row.cantidad_sugerida_auto, 'internal-update');

          // Recalcular PAC y P_Completado localmente
          var comp = toNumber(payload.data.Compromiso, null);
          var real = toNumber(payload.data.Real, null);
          if (comp !== null && comp > 0 && real !== null && real >= 0) {
            row.P_Completado = real / comp;
            row.PAC = (real < comp) ? 0 : 1;
            // Forzar actualización visual en celdas readOnly
            hot.setDataAtRowProp(visualRow, 'PAC', row.PAC, 'internal-update');
            hot.setDataAtRowProp(visualRow, 'P_Completado', row.P_Completado, 'internal-update');

            // Si cumplió (PAC=1), limpiar CNC visualmente
            if (row.PAC === 1) {
              row.Categoria_CNC = null;
              row.CNC = null;
              row.Observaciones_CNC = null;
              hot.setDataAtRowProp(visualRow, 'Categoria_CNC', null, 'internal-update');
              hot.setDataAtRowProp(visualRow, 'CNC', null, 'internal-update');
              hot.setDataAtRowProp(visualRow, 'Observaciones_CNC', null, 'internal-update');
            }
          }
        }

        pendingViewportState = captureViewportState();

        if (response.reload_hot === true) {
            // Se desdobló la actividad por tener +1 subcontratista,
            // la fila original se guardó, pero para ver los clones se recarga todo.
            if (typeof loadData === 'function') {
                loadData();
            }
            showFeedback('success', 'Guardado. (Nuevas actividades insertadas)');
            return;
        }

        applyFiltersAndRender();

        if (response.alerta_bolsa) {
            showFeedback('warning', response.alerta_bolsa);
        } else if (prop === 'Compromiso' && isCommitmentBelowSuggested(row)) {
          showFeedback('success', 'Aviso: compromiso menor a sugerido. Se guardó correctamente.');
        } else {
          showFeedback('success', 'Guardado');
        }
        return;
      }

      var message = (response && (response.mensaje || response.message || response.respuesta || response.alerta_bolsa)) || 'Error al guardar';
      revertCell(visualRow, prop, oldValue);
      showFeedback('error', message);
    }).fail(function () {
      revertCell(visualRow, prop, oldValue);
      showFeedback('error', 'Error de red');
    });
  }

  function setupRenderers() {
    if (renderersRegistered) {
      return;
    }

    Handsontable.renderers.registerRenderer('psActionsRenderer', function (instance, td, row) {
      Handsontable.dom.empty(td);
      td.classList.remove('ps-alert-critical-route', 'ps-alert-critical', 'ps-alert-high', 'ps-alert-medium', 'ps-alert-info', 'ps-alert-control', 'ps-alert-neutral');

      var rowData = instance.getSourceDataAtRow(row) || {};
      var stateKey = getStateKey(rowData);
      var alertClass = getAlertClassByState(stateKey);

      td.classList.add('ps-row-state', alertClass);
      td.classList.add('htCenter', 'htMiddle');

      var phase = getSemanalConfirmada();
      var permiso = getPermiso();
      var html = '';
      if (phase !== 1 && permiso !== 'C') {
        html += "<button type='button' class='ps-action-btn duplicar btn btn-success btn-sm btn-action-gap' data-action='duplicate' title='Duplicar Actividad'><i class='fa fa-copy fa-xs'></i></button>";
        html += "<button type='button' class='ps-action-btn eliminar btn btn-danger btn-sm btn-action-gap' data-action='delete' title='Eliminar Actividad'><i class='fa fa-trash-alt fa-xs'></i></button>";
      }
      td.innerHTML = html;
    });

    Handsontable.renderers.registerRenderer('psLiberadaRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var numeric = toNumber(value, null);
      td.textContent = (numeric === 0) ? 'Sí' : ((numeric === 1) ? 'No' : '');
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psStateRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(row) || {};
      var view = getStateView(rowData);
      td.innerHTML = renderOperationalStateCell(view);
      var trigger = td.querySelector('.ops-state-zoom');
      if (trigger) {
        trigger.setAttribute('data-row', String(row));
      }
      td.title = view.actions.length ? (view.label + ' - ' + view.actions.join('; ')) : view.label;
      td.classList.add('htLeft', 'htMiddle', 'force-wrap', 'ops-state-td');
    });

    Handsontable.renderers.registerRenderer('psPacRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = formatPercent(value, 1);
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psActividadRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = stripHtmlTags(value);
      td.classList.add('htLeft', 'htMiddle', 'force-wrap');
    });

    Handsontable.renderers.registerRenderer('psRatioRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(row) || {};
      var ratio = toNumber(value, null);
      if (ratio === null) {
        td.textContent = '';
      } else {
        var ppto = toNumber(rowData.cantidad_ppto, null);
        var unit = String(rowData.Unidad || '%').trim() || '%';
        if (ppto === null || ppto <= 0) {
          td.textContent = formatPercent(ratio, 1);
        } else {
          td.textContent = formatDecimalComma(ratio * ppto, 1) + ' ' + unit + ' (' + formatPercent(ratio, 1) + ')';
        }
      }
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psCompromisoRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(row) || {};
      var numeric = toNumber(value, null);
      if (numeric === null) {
        td.textContent = '';
      } else {
        var ppto = toNumber(rowData.cantidad_ppto, null);
        var unit = String(rowData.Unidad || '%').trim() || '%';
        var textValue = (ppto === null || ppto <= 0)
          ? formatDecimalComma(numeric, 1) + '%'
          : formatDecimalComma(numeric, 1) + ' ' + unit;

        if (prop === 'Compromiso' && weeklyPhaseKey === 'programacion') {
          var isLow = isCommitmentBelowSuggested(rowData);
          var indicatorClass = isLow ? 'ps-commit-indicator is-low' : 'ps-commit-indicator is-ok';
          var indicatorIcon = isLow ? '⚠' : '✓';
          var indicatorTitle = isLow
            ? 'Compromiso menor al sugerido (informativo)'
            : 'Compromiso igual o mayor al sugerido';

          td.innerHTML = "<span class='ps-commit-value'>" + escapeHtml(textValue) + "</span><span class='" + indicatorClass + "' title='" + escapeHtml(indicatorTitle) + "' aria-label='" + escapeHtml(indicatorTitle) + "'>" + indicatorIcon + '</span>';
        } else if (prop === 'Ejecutado_Real' && weeklyPhaseKey === 'calificacion') {
          var compromisoRow = toNumber(rowData.Compromiso, 0);
          var isRealLow = numeric < (compromisoRow - 0.0001);
          var indicatorClassReal = isRealLow ? 'ps-commit-indicator is-low' : 'ps-commit-indicator is-ok';
          var indicatorIconReal = isRealLow ? '⚠' : '✓';
          var indicatorTitleReal = isRealLow
            ? 'Ejecutado menor al compromiso (Requiere CNC)'
            : 'Compromiso cumplido';

          td.innerHTML = "<span class='ps-commit-value'>" + escapeHtml(textValue) + "</span><span class='" + indicatorClassReal + "' title='" + escapeHtml(indicatorTitleReal) + "' aria-label='" + escapeHtml(indicatorTitleReal) + "'>" + indicatorIconReal + '</span>';
        } else {
          td.textContent = textValue;
        }
      }
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psPptoRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(row) || {};
      var unit = String(rowData.Unidad || '').trim();
      if (unit === '%') {
        td.textContent = 'N/A';
      } else {
        td.textContent = isBlank(value) ? '' : value;
      }
      td.classList.add('htCenter', 'htMiddle');
    });

    Handsontable.renderers.registerRenderer('psResponsableRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.DropdownRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(row) || {};
      if (isBlank(value) && isActiveRowForCommitments(rowData)) {
        td.classList.add('ps-cell-empty-alert');
        td.innerHTML = '<span style="color:#d32f2f;font-size:14px" title="Falta asignación (Bloquea confirmación)">⚠ Sin asignar</span>';
      } else {
        td.classList.remove('ps-cell-empty-alert');
        td.style.backgroundColor = '';
        td.title = '';
      }
    });

    bindOperationalStateDrawer();
    renderersRegistered = true;
  }

  function updateOrInitHot(data) {
    setupRenderers();
    syncContainerHeight();

    if (hot) {
      pendingViewportState = captureViewportState();
      hot.loadData(data);
      applyLegacyColumnVisibility();
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
        'Consecutivo',
        'Id',
        'Código Actividad',
        'Actividad',
        'Ubicación',
        'Liberada',
        'Sub-Contratista',
        'Responsable AIA',
        'Empresa',
        'Unidad',
        'Cant. PPTO',
        'Ejecutado Actual',
        'Ejecutado Fin Semana',
        'Cant. Sugerida',
        'Compromiso',
        'Real',
        'PAC',
        '% Completado',
        'Categoría CNC',
        'CNC',
        'Obs. CNC',
        'Estado Operativo',
        'Acciones',
      ],
      columns: [
        { data: 'Consecutivo', readOnly: true, className: 'htCenter htMiddle' },
        { data: 'Id', readOnly: true, className: 'htCenter htMiddle' },
        { data: 'codigo_actividad', readOnly: true, className: 'htCenter htMiddle' },
        { data: 'Actividad', readOnly: true, renderer: 'psActividadRenderer', className: 'htLeft htMiddle force-wrap' },
        { data: 'Ubicacion', type: 'text', className: 'htLeft htMiddle force-wrap' },
        { data: 'Prog_Sin_Restricciones_100', readOnly: true, renderer: 'psLiberadaRenderer', className: 'htCenter htMiddle' },
        { data: 'Sub_Contratista', type: 'dropdown', source: subcontratistas, strict: false, allowInvalid: false, renderer: 'psResponsableRenderer', className: 'htCenter htMiddle force-wrap' },
        { data: 'Responsable_AIA', type: 'dropdown', source: profesionales, strict: false, allowInvalid: false, renderer: 'psResponsableRenderer', className: 'htCenter htMiddle force-wrap' },
        { data: 'Empresa', readOnly: true, className: 'htCenter htMiddle force-wrap' },
        { data: 'Unidad', readOnly: true, className: 'htCenter htMiddle' },
        { data: 'cantidad_ppto', readOnly: true, renderer: 'psPptoRenderer', className: 'htCenter htMiddle' },
        { data: 'Ejecutado', readOnly: true, renderer: 'psRatioRenderer', className: 'htCenter htMiddle' },
        { data: 'Ejecutado_Fin_Semana', readOnly: true, renderer: 'psRatioRenderer', className: 'htCenter htMiddle' },
        { data: 'cantidad_sugerida_auto', readOnly: true, renderer: 'psCompromisoRenderer', className: 'htCenter htMiddle' },
        { data: 'Compromiso', type: 'numeric', numericFormat: { pattern: '0.0' }, renderer: 'psCompromisoRenderer', className: 'htCenter htMiddle' },
        { data: 'Ejecutado_Real', type: 'numeric', numericFormat: { pattern: '0.0' }, renderer: 'psCompromisoRenderer', className: 'htCenter htMiddle' },
        { data: 'PAC', readOnly: true, renderer: 'psPacRenderer', className: 'htCenter htMiddle' },
        { data: 'P_Completado', readOnly: true, renderer: 'psPacRenderer', className: 'htCenter htMiddle' },
        { data: 'Categoria_CNC', type: 'text', className: 'htCenter htMiddle force-wrap' },
        { data: 'CNC', type: 'text', className: 'htLeft htMiddle force-wrap' },
        { data: 'Observaciones_CNC', type: 'text', className: 'htLeft htMiddle force-wrap' },
        { data: 'estado_operativo', readOnly: true, renderer: 'psStateRenderer', className: 'htLeft htMiddle force-wrap' },
        { data: null, renderer: 'psActionsRenderer', readOnly: true },
      ],
      hiddenColumns: getLegacyHiddenColumnsConfig(),
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'none',
      autoColumnSize: true,
      manualColumnResize: false,
      manualRowResize: true,
      contextMenu: true,
      dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
      filters: true,
      modifyFiltersMultiSelectValue: function (value, meta) {
        if (meta && (meta.prop === 'Actividad' || meta.data === 'Actividad')) {
          return stripHtmlTags(value);
        }

        return value;
      },
      search: false,
      exportFile: true,
      columnSorting: false,
      wordWrap: true,
      colHeaderHeight: 48,
      width: '100%',
      height: getContainerAvailableHeight() || '100%',
      afterGetColHeader: function (col, TH) {
        if (!TH || !TH.querySelector) {
          return;
        }

        var headerNode = TH.querySelector('.colHeader');
        if (!headerNode) {
          return;
        }

        var headerText = String(this.getColHeader(col) || '').replace(/\s+/g, ' ').trim();
        headerNode.classList.remove('ps-header-single-word');

        if (headerText && headerText.indexOf(' ') === -1) {
          headerNode.classList.add('ps-header-single-word');
        }
      },
      beforeKeyDown: function (event) {
        if (!event) {
          return;
        }

        var key = event.key || '';
        if ((key === 'Tab' || key === 'Enter') && !event.ctrlKey && !event.metaKey && !event.altKey) {
          pendingDropdownAutoOpen = true;
          return;
        }

        pendingDropdownAutoOpen = false;
      },
      afterSelectionEnd: function () {
        if (!pendingDropdownAutoOpen) {
          return;
        }

        pendingDropdownAutoOpen = false;
        setTimeout(function () {
          tryAutoOpenDropdownSelection();
        }, 0);
      },
      cells: function (row, col, prop) {
        var props = {};
        var rowData = this.instance.getSourceDataAtRow(row) || {};
        var stateKey = getStateKey(rowData);
        var alertClass = getAlertClassByState(stateKey);
        var columnMeta = this.instance.getSettings().columns[col] || {};
        var baseClass = columnMeta.className || '';

        var isReadOnly = false;
        var finalClass = (baseClass + ' ' + 'ps-row-state ' + alertClass).trim();

        if (columnMeta.renderer === 'psActionsRenderer') {
          isReadOnly = true;
        } else {
          isReadOnly = isPropReadOnly(prop);
        }

        if (isReadOnly) {
          finalClass += ' ps-cell-readonly';
        }

        props.className = finalClass.trim();
        props.readOnly = isReadOnly;
        
        return props;
      },
      beforeChange: function (changes, source) {
        if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') {
          return;
        }

        var toReject = [];
        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          var rowIndex = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];

          if (prop === 'Compromiso' && newValue !== oldValue && newValue !== null && newValue !== '') {
            var numericVal = window.PSStateMachine && typeof window.PSStateMachine.toNumberOrNull === 'function' 
              ? window.PSStateMachine.toNumberOrNull(newValue) 
              : parseFloat(String(newValue).replace(',', '.'));
              
            if (numericVal !== null && !isNaN(numericVal) && numericVal >= 0 && numericVal < 0.001) {
                changes[i] = null; // Rechazar el cambio
                toReject.push(rowIndex);
            }
          }
        }

        if (toReject.length > 0) {
            setTimeout(function() {
                var rowData = hot.getSourceDataAtRow(toReject[0]) || {};
                var msg = 'Al asignar una cantidad 0 para esta actividad, debe analizar la Causa de No Programación (CNP). Al continuar, la actividad será desprogramada.';
                
                if (typeof AIA !== 'undefined' && AIA.Notice) {
                    AIA.Notice.dialog({
                        title: 'Compromiso Cero (CNP Obligatoria)',
                        text: msg,
                        icon: 'warning',
                        confirmButtonText: 'Justificar CNP',
                        showCancelButton: false,
                        allowOutsideClick: false
                    }).then(function() {
                        deleteActivity(rowData);
                    });
                } else {
                    deleteActivity(rowData);
                }
            }, 100);
        }
      },
      afterChange: function (changes, source) {
        if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') {
          return;
        }

        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          var rowIndex = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];

          if (!editableProps[prop] || oldValue === newValue) {
            continue;
          }

          if (isPropReadOnly(prop)) {
            revertCell(rowIndex, prop, oldValue);
            continue;
          }

          var rowData = hot.getSourceDataAtRow(rowIndex) || {};

          // HARD GUARD: Block real execution registration if missing assignees
          if (prop === 'Ejecutado_Real') {
            var isSubMissing = isBlank(rowData.Sub_Contratista);
            var isResMissing = isBlank(rowData.Responsable_AIA);
            
            if (isSubMissing || isResMissing) {
              revertCell(rowIndex, prop, oldValue);
              showFeedback('error', 'Falta Sub-Contratista o Resp. AIA para registrar avance');
              continue;
            }

            var compromiso = toNumber(rowData.Compromiso, null);
            var realVal = toNumber(newValue, null);
            
            // Si el avance real es menor al compromiso, SIEMPRE pedir CNC
            if (realVal !== null && compromiso !== null && realVal < compromiso) {
                // Prevenir guardado y abrir modal CNC (nuevo o actualización)
                window._pendingCncSave = {
                  rowIndex: rowIndex,
                  oldValue: oldValue,
                  newValue: newValue,
                  prop: prop
                };
                
                // Pre-cargar datos CNC existentes si los hay
                $('#hot_cat_cnc').val(rowData.Categoria_CNC || '');
                $('#hot_cnc').val(rowData.CNC || '').prop('disabled', isBlank(rowData.Categoria_CNC));
                $('#hot_obs_cnc').val(rowData.Observaciones_CNC || '');

                // Cargar causas si ya hay categoría seleccionada
                if (!isBlank(rowData.Categoria_CNC)) {
                  $('#hot_cat_cnc').trigger('change.psCnc');
                }

                $('#modal_cnc_hot').modal('show');
                
                revertCell(rowIndex, prop, oldValue);
                continue;
            }
          }

          var normalized = normalizeCellValue(prop, newValue);
          if (!normalized.valid) {
            revertCell(rowIndex, prop, oldValue);
            showFeedback('error', normalized.error);
            continue;
          }

          if (normalized.value !== newValue) {
            hot.setDataAtRowProp(rowIndex, prop, normalized.value, 'internal-update');
          }

          saveRow(rowIndex, prop, oldValue);
        }
      },
    });

    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();
    container.addEventListener('mousedown', function () {
      if (hot && !hot.isDestroyed) { hot.listen(); }
    }, true);

    bindRowActionClicks();
    scheduleLayoutRefresh(0, true);
  }

  function isActiveRowForCommitments(rowData) {
    if (!rowData) {
      return false;
    }

    var activaTexto = isBlank(rowData.Activa) ? '' : String(rowData.Activa).trim().toUpperCase();
    if (activaTexto === 'NA' || activaTexto === '0' || activaTexto === 'NO' || activaTexto === 'N' || activaTexto === 'FALSE' || activaTexto === '') {
      return false;
    }

    return true;
  }

  function isCommitmentBelowSuggested(row) {
    if (!row || weeklyPhaseKey !== 'programacion') {
      return false;
    }

    if (!isActiveRowForCommitments(row)) {
      return false;
    }

    var compromiso = toNumber(row.Compromiso, null);
    if (compromiso === null || compromiso <= 0) {
      return false;
    }

    var target = toNumber(row.cantidad_sugerida_auto, null);
    if (target === null || target <= 0) {
      target = calculateSuggested(row);
    }

    if (!Number.isFinite(target) || target <= 0) {
      return false;
    }

    return (compromiso + 0.0001) < target;
  }

  function rowMatchesFilters(row) {
    if (weeklyAlertFilters.length > 0) {
      var stateKey = getStateKey(row);
      if (weeklyAlertFilters.indexOf(stateKey) === -1) {
        return false;
      }
    }

    return true;
  }

  function updateAlertCounts(rows) {
    var config = getAlertConfig(weeklyPhaseKey);
    var counts = {};

    for (var i = 0; i < config.length; i++) {
      counts[config[i].key] = 0;
    }

    for (var r = 0; r < rows.length; r++) {
      var row = rows[r];
      var state = getStateKey(row);
      if (counts[state] !== undefined) {
        counts[state] += 1;
      }

    }

    for (var j = 0; j < config.length; j++) {
      var item = config[j];
      $('#count-' + item.key).text('(' + (counts[item.key] || 0) + ')');
    }
  }

  function getFilteredRows() {
    var filtered = [];
    for (var i = 0; i < masterData.length; i++) {
      if (rowMatchesFilters(masterData[i])) {
        filtered.push(masterData[i]);
      }
    }
    return filtered;
  }

  function applyFiltersAndRender() {
    var filtered = getFilteredRows();

    updateAlertCounts(filtered);
    updateOrInitHot(filtered);
  }

  function renderAlertLegend() {
    var config = getAlertConfig(weeklyPhaseKey);
    var html = '';
    for (var i = 0; i < config.length; i++) {
      var item = config[i];
      html += "<span class='pdc-legend-item " + item.className + "' data-filter='" + item.key + "' role='button' tabindex='0'><span class='indicator'></span>" +
        item.label + " <span id='count-" + item.key + "' class='count-badge'>(...)</span></span>";
    }
    $('#psAlertsLegend').html(html);
  }

  function renderLegendModal() {
    var config = getAlertConfig(weeklyPhaseKey);
    var phaseLabel = weeklyPhaseKey === 'calificacion' ? 'Calificación de Actividades' : 'Programación de Compromisos';
    var intro = weeklyPhaseKey === 'calificacion'
      ? 'Cierre incumplidas con CNC, ejecute recuperación y proteja la siguiente semana.'
      : 'Defina compromisos viables, cierre condiciones pendientes y confirme solo actividades ejecutables.';

    var groupTitleMap = {
      p1: 'Resolver hoy',
      p2: 'Gestión semanal',
      p3: 'Seguimiento',
    };

    var grouped = { p1: [], p2: [], p3: [] };
    for (var i = 0; i < config.length; i++) {
      var item = config[i];
      var priority = item.priority || 'p2';
      if (!grouped[priority]) {
        priority = 'p2';
      }
      grouped[priority].push(item);
    }

    var html = "<div class='ps-legend-quick'>" +
      "<div class='ps-legend-quick-header'>" +
        "<p class='ps-legend-quick-intro'><strong>Lectura rápida:</strong> " + intro + "</p>" +
      '</div>';

    var priorityOrder = ['p1', 'p2', 'p3'];
    for (var p = 0; p < priorityOrder.length; p++) {
      var priorityKey = priorityOrder[p];
      var list = grouped[priorityKey];
      if (!list || list.length === 0) {
        continue;
      }

      html += "<section class='ps-legend-quick-group'>" +
        "<h6 class='ps-legend-quick-group-title'>" + groupTitleMap[priorityKey] + '</h6>';

      for (var j = 0; j < list.length; j++) {
        var state = list[j];
        html += "<div class='ps-legend-quick-row'>" +
          "<span class='ps-legend-modal-swatch ps-legend-quick-swatch " + state.className + "'></span>" +
          "<div class='ps-legend-quick-state'><strong>" + state.label + '</strong><small>' + (state.description || '') + '</small></div>' +
          "<div class='ps-legend-quick-action'><strong>Acción:</strong> " + (state.action || 'Gestionar según plan de obra.') + '</div>' +
        '</div>';
      }

      html += '</section>';
    }

    html += "<section class='ps-legend-quick-alerts'>" +
      "<h6 class='ps-legend-quick-group-title'>Cómo leer la criticidad</h6>" +
      "<p class='ps-legend-quick-alert-intro'>Primero atienda actividades de <strong>ruta crítica</strong>. Luego gestione las demás por severidad semanal.</p>" +
      "<div class='ps-legend-quick-alert-grid'>" +
        "<div class='ps-legend-quick-alert-item'><span class='ps-legend-modal-swatch ps-legend-quick-swatch ps-alert-critical-route'></span><strong>Ruta crítica</strong><small>Escale de inmediato, defina responsable y fecha de cierre.</small></div>" +
        "<div class='ps-legend-quick-alert-item'><span class='ps-legend-modal-swatch ps-legend-quick-swatch ps-alert-critical'></span><strong>Fuera de ruta crítica</strong><small>Gestione en el ciclo semanal y evite escalamiento.</small></div>" +
      '</div>' +
    '</section>' +
    '</div>';

    $('#modal_leyenda_colores_Label').text('Guía Operativa - Programación Semanal (' + phaseLabel + ')');
    $('#modal_leyenda_colores_body').html(html);
  }

  function syncLegendVisualState() {
    var $items = $('#psAlertsLegend .pdc-legend-item');
    if (weeklyAlertFilters.length === 0) {
      $items.removeClass('inactive-filter');
    } else {
      $items.addClass('inactive-filter');
      for (var i = 0; i < weeklyAlertFilters.length; i++) {
        $("#psAlertsLegend .pdc-legend-item[data-filter='" + weeklyAlertFilters[i] + "']").removeClass('inactive-filter');
      }
    }

    $('#mobileAlertCount').text(weeklyAlertFilters.length);
  }

  function toggleWeeklyAlertFilter(filterKey, event) {
    event = event || {};
    var index = weeklyAlertFilters.indexOf(filterKey);

    if (!event.ctrlKey && !event.metaKey) {
      if (weeklyAlertFilters.length === 1 && weeklyAlertFilters[0] === filterKey) {
        weeklyAlertFilters = [];
      } else {
        weeklyAlertFilters = [filterKey];
      }
    } else if (index > -1) {
      weeklyAlertFilters.splice(index, 1);
    } else {
      weeklyAlertFilters.push(filterKey);
    }

    syncLegendVisualState();
    applyFiltersAndRender();
  }

  function syncPhaseUI() {
    weeklyPhaseKey = getPhaseKey();
    var fechaCierre = String($('#fechaCierreCompromisos').val() || '').trim();
    var phaseInfo = getWeeklyPhaseInfo(weeklyPhaseKey, fechaCierre);

    if (weeklyPhaseKey === 'calificacion') {
      $('#phase-badge').text('Calificación').removeClass('badge-info').addClass('badge-warning');
      $('#weeklyPhaseMobileLabel').text(phaseInfo.mobileLabel);
      $('#btn_autoprogramar').hide();
      $('#btn_agregar_actividad').hide();
      $('#btn_cerrar_compromisos_semana').hide();
      $('#btn_informe_compromisos').show();
      if (fechaCierre) {
        $('#textoFechaCierreCompromisos').text('Compromisos cerrados el ' + fechaCierre);
      } else {
        $('#textoFechaCierreCompromisos').text('Compromisos cerrados. Semana en evaluación.');
      }
    } else {
      $('#phase-badge').text('Programación').removeClass('badge-warning').addClass('badge-info');
      $('#weeklyPhaseMobileLabel').text(phaseInfo.mobileLabel);
      $('#btn_autoprogramar').show();
      $('#btn_agregar_actividad').show();
      $('#btn_cerrar_compromisos_semana').show();
      $('#btn_informe_compromisos').hide();
      $('#textoFechaCierreCompromisos').text('');
    }

    var canManage = canManageToolbarActions();
    $('#btn_autoprogramar').prop('disabled', !canManage);
    $('#btn_agregar_actividad').prop('disabled', !canManage);
    $('#btn_cerrar_compromisos_semana').prop('disabled', !canManage);

    syncContextPhaseIndicator(weeklyPhaseKey, fechaCierre);

    renderAlertLegend();
    updateAlertCounts(getFilteredRows());
    renderLegendModal();
    syncLegendVisualState();
    scheduleActionsRowFit(0);
  }

  function formatWeeklyQuantity(value, unidad) {
    var number = toNumber(value, null);
    if (number === null) {
      return '0,0' + (unidad ? (' ' + unidad) : '');
    }
    return formatDecimalComma(number, 1) + (unidad ? (' ' + unidad) : '');
  }

  function buildCloseSummary() {
    var summary = {
      readyCount: 0,
      blockingCount: 0,
      warningLowCount: 0,
      warningRestrictedCount: 0,
      blockingCriticalCount: 0,
      blockingItems: [],
      warningLowItems: [],
      warningRestrictedItems: [],
    };

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      if (!isActiveRowForCommitments(row)) {
        continue;
      }

      var compromiso = toNumber(row.Compromiso, null);
      var hasCommitment = compromiso !== null && compromiso > 0;
      var unidad = isBlank(row.Unidad) ? '' : String(row.Unidad);
      var target = toNumber(row.cantidad_sugerida_auto, null);
      if (target === null || target <= 0) {
        target = calculateSuggested(row);
      }
      var liberada = toNumber(row.Prog_Sin_Restricciones_100, null);
      var critica = toNumber(row.Critica, 0) >= 1;

      var subcontratistaFalta = isBlank(row.Sub_Contratista);
      var responsableFalta = isBlank(row.Responsable_AIA);

      if (!hasCommitment || subcontratistaFalta || responsableFalta) {
        summary.blockingCount += 1;
        var detalleBloqueo = [];
        
        if (!hasCommitment) {
          detalleBloqueo.push(critica ? 'En ruta crítica sin compromiso' : 'Compromiso sin definir');
          if (critica) {
            summary.blockingCriticalCount += 1;
          }
        }
        
        if (subcontratistaFalta) detalleBloqueo.push('Falta Sub-Contratista');
        if (responsableFalta) detalleBloqueo.push('Falta Responsable AIA');

        if (summary.blockingItems.length < 8) {
          summary.blockingItems.push({
            actividad: escapeHtml(getPlainActivityLabel(row.Actividad)),
            detalle: detalleBloqueo.join(' / '),
          });
        }
        continue;
      }

      summary.readyCount += 1;

      if (target > 0 && (compromiso + 0.0001) < target) {
        summary.warningLowCount += 1;
        if (summary.warningLowItems.length < 8) {
          summary.warningLowItems.push({
            actividad: escapeHtml(getPlainActivityLabel(row.Actividad)),
            detalle: 'Compromiso ' + formatWeeklyQuantity(compromiso, unidad) + ' / Sugerido ' + formatWeeklyQuantity(target, unidad),
          });
        }
      }

      if (liberada !== null && liberada > 0) {
        summary.warningRestrictedCount += 1;
        if (summary.warningRestrictedItems.length < 8) {
          var readinessActions = getReadinessActions(row);
          summary.warningRestrictedItems.push({
            actividad: escapeHtml(getPlainActivityLabel(row.Actividad)),
            detalle: readinessActions.length
              ? 'Acciones de habilitación: ' + escapeHtml(readinessActions.join('; '))
              : 'Actividad comprometida con condiciones pendientes.',
          });
        }
      }
    }

    return summary;
  }

  function renderSummaryList(title, items, extraClass) {
    if (!items || items.length === 0) {
      return '';
    }

    var html = "<div class='ps-close-summary-block " + extraClass + "'><h6>" + title + "</h6><ul class='ps-close-summary-list'>";
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      html += '<li><strong>' + item.actividad + '</strong><br><small>' + item.detalle + '</small></li>';
    }
    html += '</ul></div>';
    return html;
  }

  function renderCloseSummary() {
    var summary = buildCloseSummary();
    var hasBlocking = summary.blockingCount > 0;

    var html = "<div class='ps-close-summary'>" +
      "<div class='ps-close-summary-kpis'>" +
      "<div class='ps-close-summary-kpi is-blocking'><strong>" + summary.blockingCount + "</strong><small>Por completar</small></div>" +
      "<div class='ps-close-summary-kpi is-ready'><strong>" + summary.readyCount + "</strong><small>Listas para confirmar</small></div>" +
      "<div class='ps-close-summary-kpi is-warning'><strong>" + summary.warningLowCount + "</strong><small>Compromiso menor a sugerido</small></div>" +
      "<div class='ps-close-summary-kpi is-warning'><strong>" + summary.warningRestrictedCount + "</strong><small>Compromiso con condiciones pendientes</small></div>" +
      "</div>";

    html += renderSummaryList('Detalle por completar', summary.blockingItems, 'is-blocking');
    html += renderSummaryList('Detalle compromiso menor a sugerido', summary.warningLowItems, 'is-warning');
    html += renderSummaryList('Detalle con condiciones pendientes', summary.warningRestrictedItems, 'is-warning');
    html += "<p class='ps-close-summary-note'>Al confirmar, no se podrán modificar compromisos ni eliminar actividades.</p></div>";

    $('#cerrar_compromisos_semana').html(html);
    $('#btn_confirmar_compromisos_semana').prop('disabled', hasBlocking).toggleClass('disabled', hasBlocking);
  }

  function normalizeNewActivityForm() {
    $('#idNuevo').val('');
    $('#Actividad').val('');
    $('#Descripcion').val('');
    $('#Ubicacion').val('');
    $('#Sub_Contratista').val('');
    $('#Responsable_AIA').val('');
    $('#Empresa').val('');
    $('#Unidad').val('%');
    $('#Compromiso').val('');
  }

  function submitNewActivity() {
    var db = getDb();
    var semana = getSemana();
    var idNuevo = String($('#idNuevo').val() || '').trim();
    var actividad = String($('#Actividad').val() || '').trim();
    var sub = String($('#Sub_Contratista').val() || '').trim();
    var resp = String($('#Responsable_AIA').val() || '').trim();
    var compromiso = normalizePositive($('#Compromiso').val());

    if (!idNuevo || !actividad || !sub || !resp) {
      showFeedback('error', 'Complete todos los campos obligatorios');
      return;
    }

    if (compromiso === null) {
      showFeedback('error', 'Compromiso inválido (debe ser > 0)');
      return;
    }

    $('#btn_guardar_nueva_actividad').prop('disabled', true).val('Guardando...');

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: {
        opcion: 'nuevo',
        semana: semana,
        idNuevo: idNuevo,
        Actividad: actividad,
        Descripcion: $('#Descripcion').val() || '',
        Ubicacion: $('#Ubicacion').val() || '',
        Sub_Contratista: sub,
        Responsable_AIA: resp,
        Empresa: $('#Empresa').val() || '',
        Unidad: $('#Unidad').val() || '%',
        Compromiso: numberPayload(compromiso),
      },
    }).done(function (raw) {
      var response = parseResponse(raw);
      if (response && (response.respuesta === 'BIEN' || response.respuesta === 'OK')) {
        $('#formulario_nuevo').modal('hide');
        showFeedback('success', 'Actividad guardada');
        normalizeNewActivityForm();
        loadData();
      } else {
        var msg = (response && (response.mensaje || response.message || response.respuesta)) || 'No se pudo guardar la actividad';
        showFeedback('error', msg);
      }
    }).fail(function () {
      showFeedback('error', 'Error guardando nueva actividad');
    }).always(function () {
      $('#btn_guardar_nueva_actividad').prop('disabled', false).val('Guardar');
    });
  }

  function duplicateActivity(row) {
    var db = getDb();
    var semana = getSemana();

    var doAjax = function() {
      $.ajax({
        method: 'POST',
        url: '/api/semanal/save?db=' + encodeURIComponent(db),
        dataType: 'json',
        data: {
          opcion: 'duplicar',
          Id: row.Consecutivo,
          semana: semana,
        },
      }).done(function (raw) {
        var response = parseResponse(raw);
        if (response && response.respuesta === 'BIEN') {
          showFeedback('success', 'Actividad duplicada');
          loadData();
        } else {
          showFeedback('error', 'Error al duplicar la actividad');
        }
      }).fail(function () {
        showFeedback('error', 'Error al duplicar la actividad');
      });
    };

    if (typeof AIA !== 'undefined' && AIA.Notice) {
      AIA.Notice.confirm('¿Desea duplicar esta actividad?', 'Duplicar Actividad').then(function(confirmed) {
        if (confirmed) {
          doAjax();
        }
      });
    }
  }

  function deleteActivity(row) {
    if (!row || !row.Consecutivo) {
      showFeedback('error', 'Actividad invalida para eliminar');
      return;
    }

    pendingDeleteRow = row;
    if (isActiveRowForCommitments(row)) {
      $('#psDeleteModalText').html('Indique la CNP para eliminar la actividad: <strong>' + escapeHtml(getPlainActivityLabel(row.Actividad)) + '</strong>');
    } else {
      $('#psDeleteModalText').html('Confirme la eliminacion de la actividad programada manualmente: <strong>' + escapeHtml(getPlainActivityLabel(row.Actividad)) + '</strong>');
    }
    $('#psDeleteResponsableAIA').val(String(row.Responsable_AIA || '')).change();
    $('#psDeleteEmpresa').val(String(row.Empresa || ''));
    $('#psDeleteObservacionesCNP').val(String(row.Observaciones_CNP || ''));

    var categoria = String(row.Categoria_CNP || '').trim();
    $('#psDeleteCategoriaCNP').val(categoria).change();
    loadDeleteCnpOptions(categoria, String(row.CNP || '').trim());

    $('#modal_eliminar_actividad').modal('show');
  }

  function loadDeleteCnpOptions(categoria, selected) {
    var db = getDb();
    var $cnp = $('#psDeleteCNP');

    if (!categoria) {
      $cnp.html("<option value=''></option>");
      return;
    }

    $.ajax({
      method: 'POST',
      url: '/api/cnc/reasons',
      data: { categoria: categoria },
    }).done(function (data) {
      var optionsHtml = "<option value=''></option>";
      if (Array.isArray(data)) {
        data.forEach(function(item) {
          var val = item.CNC;
          optionsHtml += "<option value='" + escapeHtml(val) + "'>" + escapeHtml(val) + "</option>";
        });
      }
      $cnp.html(optionsHtml);
      if (selected) {
        $cnp.val(selected);
      }
    }).fail(function () {
      $cnp.html("<option value=''></option>");
    });
  }

  function confirmDeleteActivity() {
    var db = getDb();
    var semana = getSemana();
    var row = pendingDeleteRow;

    if (!row || !row.Consecutivo) {
      $('#modal_eliminar_actividad').modal('hide');
      return;
    }

    var responsableAIA = String($('#psDeleteResponsableAIA').val() || row.Responsable_AIA || '').trim();
    var categoria = String($('#psDeleteCategoriaCNP').val() || '').trim();
    var cnp = String($('#psDeleteCNP').val() || '').trim();
    var observaciones = String($('#psDeleteObservacionesCNP').val() || '').trim();
    var requiresCnp = isActiveRowForCommitments(row);

    if (requiresCnp && (!categoria || !cnp)) {
      showFeedback('error', 'Complete Categoria y CNP para eliminar');
      return;
    }

    $('#btn_confirmar_eliminar_actividad').prop('disabled', true).text('Guardando...');

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: {
        opcion: 'eliminar',
        Id: row.Consecutivo,
        semana: semana,
        Responsable_AIA: responsableAIA,
        Categoria_CNP: categoria,
        CNP: cnp,
        Observaciones_CNP: observaciones,
      },
    }).done(function (raw) {
      var response = parseResponse(raw);
      if (response && response.respuesta === 'BIEN') {
        $('#modal_eliminar_actividad').modal('hide');
        pendingDeleteRow = null;
        showFeedback('success', 'Actividad eliminada');
        loadData();
      } else {
        showFeedback('error', 'No se pudo eliminar la actividad');
      }
    }).fail(function () {
      showFeedback('error', 'Error eliminando actividad');
    }).always(function () {
      $('#btn_confirmar_eliminar_actividad').prop('disabled', false).text('Guardar y Eliminar');
    });
  }

  function bindRowActionClicks() {
    $('#hot-container').off('click.psRowActions').on('click.psRowActions', '.ps-action-btn', function (event) {
      if (!hot) {
        return;
      }

      var $btn = $(this);
      var action = String($btn.data('action') || '');
      var td = $btn.closest('td')[0];
      if (!td) {
        return;
      }

      var coords = hot.getCoords(td);
      if (!coords || coords.row < 0) {
        return;
      }

      var physicalRow = hot.toPhysicalRow(coords.row);
      var row = hot.getSourceDataAtRow(physicalRow) || {};

      if (!canManageToolbarActions() || getPermiso() === 'C' || getSemanalConfirmada() === 1) {
        showFeedback('error', 'Acción bloqueada: No tiene permisos de edición');
        return;
      }

      if (action === 'duplicate') {
        duplicateActivity(row);
        return;
      }

      if (action === 'delete') {
        deleteActivity(row);
      }
    });
  }

  function bindFilters() {
    $('#psAlertsLegend')
      .off('click.psLegend keydown.psLegend')
      .on('click.psLegend', '.pdc-legend-item', function (event) {
        var key = $(this).data('filter');
        if (key) {
          toggleWeeklyAlertFilter(String(key), event);
        }
      })
      .on('keydown.psLegend', '.pdc-legend-item', function (event) {
        if (event.key === 'Enter' || event.keyCode === 13 || event.keyCode === 32) {
          event.preventDefault();
          var key = $(this).data('filter');
          if (key) {
            toggleWeeklyAlertFilter(String(key), event);
          }
        }
      });

    window.toggleWeeklyAlertFilter = function (filterKey, event) {
      toggleWeeklyAlertFilter(filterKey, event || {});
    };
  }

  function exportCsv() {
    if (!hot) {
      return;
    }

    hot.getPlugin('exportFile').downloadFile('csv', {
      filename: 'programacion_semanal',
      columnHeaders: true,
      rowHeaders: false,
    });
  }

  function autoprogramar() {
    if (!canManageToolbarActions()) {
      showFeedback('error', 'No tiene permisos para autoprogramar');
      return;
    }

    var db = getDb();
    var semana = getSemana();

    $('#btn_autoprogramar').prop('disabled', true).text('Verificando...');
    scheduleActionsRowFit(0);

    // Preflight: detectar datos faltantes
    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: { opcion: 'listar_excepciones_autoprogramacion', semana: semana },
    }).done(function (raw) {
      var response = parseResponse(raw);
      var faltantes = (response && Array.isArray(response.datos_faltantes)) ? response.datos_faltantes : [];

      if (faltantes.length > 0) {
        showDatosFaltantesModal(faltantes, function () {
          ejecutarAutoprogramar(db, semana);
        });
        $('#btn_autoprogramar').prop('disabled', false).html('Autoprogramar Actividades <i class="fas fa-upload"></i>');
        scheduleActionsRowFit(0);
      } else {
        ejecutarAutoprogramar(db, semana);
      }
    }).fail(function () {
      ejecutarAutoprogramar(db, semana);
    });
  }

  function ejecutarAutoprogramar(db, semana) {
    $('#btn_autoprogramar').prop('disabled', true).text('Ejecutando...');
    scheduleActionsRowFit(0);

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: { opcion: 'autoprogramar', semana: semana },
    }).done(function (raw) {
      var response = parseResponse(raw);
      var answer = (response && typeof response === 'object') ? String(response.respuesta || '') : String(response || '');
      if (answer === 'BIEN' || answer === 'OK') {
        showFeedback('success', 'Autoprogramación ejecutada');
        
        var alertas = (response && Array.isArray(response.alertasRestricciones)) ? response.alertasRestricciones : [];
        if (alertas.length > 0) {
          showRestriccionesFaltantesModal(alertas);
        }

        loadData();
        return;
      }
      var message = (response && typeof response === 'object')
        ? (response.mensaje || response.message || response.respuesta)
        : 'No se pudo completar la autoprogramación';
      showFeedback('error', message || 'No se pudo completar la autoprogramación');
    }).fail(function () {
      showFeedback('error', 'Error ejecutando autoprogramación');
    }).always(function () {
      $('#btn_autoprogramar').prop('disabled', false).html('Autoprogramar Actividades <i class="fas fa-upload"></i>');
      scheduleActionsRowFit(0);
    });
  }

  function showDatosFaltantesModal(faltantes, onConfirm) {
    var existingModal = document.getElementById('modalDatosFaltantes');
    if (existingModal) { existingModal.remove(); }

    var rows = '';
    for (var i = 0; i < faltantes.length; i++) {
      rows += '<tr><td>' + escapeHtml(faltantes[i].Id || '') + '</td>';
      rows += '<td>' + escapeHtml(faltantes[i].Actividad || '') + '</td>';
      rows += '<td class="text-danger">' + escapeHtml(faltantes[i].CamposFaltantes || '') + '</td></tr>';
    }

    var html = '<div class="modal fade" id="modalDatosFaltantes" tabindex="-1" role="dialog">'
      + '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'
      + '<div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Actividades con Datos Incompletos</h5>'
      + '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>'
      + '<div class="modal-body"><p>Las siguientes <strong>' + faltantes.length + '</strong> actividades candidatas tienen datos faltantes:</p>'
      + '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Id</th><th>Actividad</th><th>Campos Faltantes</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div>'
      + '<p class="text-muted mt-2"><small>Estas actividades se autoprogramarán de todas formas, pero se recomienda completar los datos desde la Programación Intermedia.</small></p></div>'
      + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>'
      + '<button type="button" class="btn btn-warning" id="btnConfirmarAutoprogramar">Continuar de todas formas</button></div>'
      + '</div></div></div>';

    $('body').append(html);
    var $modal = $('#modalDatosFaltantes');
    $modal.modal('show');

    $('#btnConfirmarAutoprogramar').off('click').on('click', function () {
      $modal.modal('hide');
      if (typeof onConfirm === 'function') { onConfirm(); }
    });

    $modal.on('hidden.bs.modal', function () { $modal.remove(); });
  }

  function showRestriccionesFaltantesModal(alertas) {
    var existingModal = document.getElementById('modalRestriccionesFaltantes');
    if (existingModal) { existingModal.remove(); }

    var rows = '';
    for (var i = 0; i < alertas.length; i++) {
      rows += '<tr><td>' + escapeHtml(alertas[i].Id || '') + '</td>';
      rows += '<td>' + escapeHtml(alertas[i].Actividad || '') + '</td>';
      rows += '<td class="text-danger">' + escapeHtml(alertas[i].RestriccionesPendientes || '') + '</td></tr>';
    }

    var html = '<div class="modal fade" id="modalRestriccionesFaltantes" tabindex="-1" role="dialog">'
      + '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'
      + '<div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-clipboard-list"></i> Actividades pendientes para autoprogramar</h5>'
      + '<button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>'
      + '<div class="modal-body"><p>Las siguientes <strong>' + alertas.length + '</strong> actividades se omitieron porque tienen condiciones pendientes para comprometer:</p>'
      + '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Id</th><th>Actividad</th><th>Condiciones pendientes</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div>'
      + '<p class="text-muted mt-2"><small>Cierre las acciones de habilitación desde la Programación Intermedia para que puedan ser autoprogramadas o agregadas manualmente.</small></p></div>'
      + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>'
      + '</div></div></div>';

    $('body').append(html);
    var $modal = $('#modalRestriccionesFaltantes');
    $modal.modal('show');

    $modal.on('hidden.bs.modal', function () { $modal.remove(); });
  }

  function confirmCommitments() {
    var db = getDb();
    var semana = getSemana();
    var today = new Date();
    var dateText = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

    $('#btn_confirmar_compromisos_semana').prop('disabled', true).val('Confirmando...');

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: {
        opcion: 'bloquear_compromisos',
        semana: semana,
        fechaCierreCompromisos: dateText,
      },
    }).done(function (raw) {
      var response = parseResponse(raw);
      var answerRaw = (response && typeof response === 'object' && response.respuesta !== undefined)
        ? response.respuesta
        : response;
      var answer = String(answerRaw === null || answerRaw === undefined ? '' : answerRaw).trim();

      if (answer === 'ERROR' || answer === 'Error') {
        var errorMessage = (response && typeof response === 'object')
          ? (response.mensaje || response.message)
          : '';
        showFeedback('error', errorMessage || 'No se pudieron confirmar los compromisos');
        return;
      }

      if (answer !== 'Bloqueado' && answer !== 'No_Bloqueado') {
        showFeedback('error', 'Respuesta inesperada al confirmar compromisos');
        return;
      }

      $('#modal_cerrar_compromisos').modal('hide');
      $('#modal_aceptar_cerrar_compromisos').modal('show');

      if (answer === 'Bloqueado') {
        $('#aceptar_cerrar_compromisos_semana').html('<p>Se han bloqueado los compromisos de la presente semana.</p><p>Ya no se pueden modificar compromisos ni eliminar actividades.</p>');
        $('#Semanal_Confirmada').val('1');
        syncPhaseUI();
        loadData();
      } else {
        $('#aceptar_cerrar_compromisos_semana').html('<p>Se detectaron actividades sin compromiso o sin asignaciones obligatorias.</p><p>Asigne compromisos > 0, Responsable AIA y Sub-Contratista en todas las actividades activas para continuar.</p>');
      }
    }).fail(function () {
      showFeedback('error', 'Error confirmando compromisos');
    }).always(function () {
      $('#btn_confirmar_compromisos_semana').prop('disabled', false).val('Confirmar');
    });
  }

  function descargarReporte() {
    var db = getDb();
    var semana = getSemana();

    $('#btn_informe_compromisos').prop('disabled', true).text('Generando...');
    scheduleActionsRowFit(0);

    $.ajax({
      method: 'POST',
      url: '/reportes/compromisos',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).done(function (response) {
      if (response && response.url) {
        window.location.href = response.url;
      } else {
        showFeedback('error', 'No se pudo generar el informe');
      }
    }).fail(function () {
      showFeedback('error', 'Error generando informe');
    }).always(function () {
      $('#btn_informe_compromisos').prop('disabled', false).html('Imprimir <i class="fas fa-print"></i>');
      scheduleActionsRowFit(0);
    });
  }

  function cargarBandejaNoAutoprogramadas() {
    var db = getDb();
    var semana = getSemana();
    var $tbody = $('#tbody_excepciones_no_autoprogramadas');

    $tbody.html('<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: {
        opcion: 'listar_excepciones_autoprogramacion',
        semana: semana,
      },
    }).done(function (raw) {
      var response = parseResponse(raw);
      var data = (response && Array.isArray(response.data)) ? response.data : [];
      
      if (data.length === 0) {
        $tbody.html('<tr><td colspan="4" class="text-center text-muted">No se encontraron actividades pendientes.</td></tr>');
        return;
      }

      var html = '';
      data.forEach(function (row) {
        html += '<tr class="ps-row-excepcion" data-id="' + escapeHtml(row.Id) + '" ' +
                'data-actividad="' + escapeHtml(row.Actividad) + '" ' +
                'data-sub="' + escapeHtml(row.Sub_Contratista || '') + '" ' +
                'data-resp="' + escapeHtml(row.Responsable_AIA || '') + '" ' +
                'data-unidad="' + escapeHtml(row.Unidad || '%') + '">';
        html += '<td class="ps-excepcion-id">' + escapeHtml(row.Id) + '</td>';
        html += '<td class="ps-excepcion-actividad">' + escapeHtml(row.Actividad) + '</td>';
        html += '<td><span class="ps-motivo-chip">' + escapeHtml(row.Motivo) + '</span></td>';
        html += '<td class="text-right"><button type="button" class="btn btn-sm btn-outline-primary btn_usar_excepcion">Usar</button></td>';
        html += '</tr>';
      });
      $tbody.html(html);
    }).fail(function () {
      $tbody.html('<tr><td colspan="4" class="text-center text-danger">Error al cargar la bandeja.</td></tr>');
    });
  }

  function useExceptionActivity(item) {
    if (!item) return;
    
    $('#idNuevo').val(String(item.Id)).change();
    $('#Actividad').val(String(item.Actividad || ''));
    $('#Sub_Contratista').val(String(item.Sub_Contratista || '')).change();
    $('#Responsable_AIA').val(String(item.Responsable_AIA || '')).change();
    $('#Unidad').val(String(item.Unidad || '%'));
    
    // Feedback visual de selección
    $('#tbody_excepciones_no_autoprogramadas tr').removeClass('ps-row-selected');
    $('#tbody_excepciones_no_autoprogramadas tr[data-id="' + escapeHtml(item.Id) + '"]').addClass('ps-row-selected');
    
    showFeedback('info', 'Actividad cargada en el formulario');
  }

  function bindToolbarActions() {
    $('#btn-refresh').off('click.psRefresh').on('click.psRefresh', loadData);
    $('#btn-export').off('click.psExport').on('click.psExport', exportCsv);
    $('#btn_autoprogramar').off('click.psAutoprogram').on('click.psAutoprogram', autoprogramar);
    $('#btn_informe_compromisos').off('click.psReport').on('click.psReport', descargarReporte);

    $('#btn_agregar_actividad').off('click.psNew').on('click.psNew', function () {
      if (!canManageToolbarActions() || getSemanalConfirmada() === 1) {
        showFeedback('error', 'No se pueden agregar actividades en esta fase');
        return;
      }
      normalizeNewActivityForm();
      $('#formulario_nuevo').modal('show');
      cargarBandejaNoAutoprogramadas();
    });

    $('#btn_recargar_bandeja_no_autoprogramadas').off('click.psBandeja').on('click.psBandeja', cargarBandejaNoAutoprogramadas);

    $('#filtro_excepciones_no_autoprogramadas').off('input.psBandeja').on('input.psBandeja', function () {
      var val = String($(this).val()).toLowerCase().trim();
      $('#tbody_excepciones_no_autoprogramadas tr').each(function () {
        var $row = $(this);
        var text = $row.text().toLowerCase();
        $row.toggle(text.indexOf(val) > -1);
      });
    });

    $('#tbody_excepciones_no_autoprogramadas').off('click.psBandeja').on('click.psBandeja', '.btn_usar_excepcion, tr', function (e) {
      var $tr = $(this).closest('tr');
      var item = {
        Id: $tr.data('id'),
        Actividad: $tr.data('actividad'),
        Sub_Contratista: $tr.data('sub'),
        Responsable_AIA: $tr.data('resp'),
        Unidad: $tr.data('unidad')
      };
      if (item.Id) {
        useExceptionActivity(item);
      }
    });

    $('#btn_guardar_nueva_actividad').off('click.psSaveNew').on('click.psSaveNew', submitNewActivity);

    $('#btn_cerrar_compromisos_semana').off('click.psClose').on('click.psClose', function () {
      renderCloseSummary();
      $('#modal_cerrar_compromisos').modal('show');
    });

    $('#btn_confirmar_compromisos_semana').off('click.psConfirm').on('click.psConfirm', confirmCommitments);

    $('#psDeleteCategoriaCNP').off('change.psDelete').on('change.psDelete', function () {
      loadDeleteCnpOptions(String($(this).val() || '').trim(), '');
    });

    $('#btn_confirmar_eliminar_actividad').off('click.psDelete').on('click.psDelete', confirmDeleteActivity);

    $('#modal_eliminar_actividad').off('hidden.bs.modal.psDelete').on('hidden.bs.modal.psDelete', function () {
      pendingDeleteRow = null;
      $('#psDeleteModalText').empty();
      $('#psDeleteResponsableAIA').val('');
      $('#psDeleteEmpresa').val('');
      $('#psDeleteCategoriaCNP').val('');
      $('#psDeleteCNP').html("<option value=''></option>");
      $('#psDeleteObservacionesCNP').val('');
      $('#btn_confirmar_eliminar_actividad').prop('disabled', false).text('Guardar y Eliminar');
    });

    // Dropdown interactividad (Click fallback con delegación robusta)
    $(document).off('click.psDropdown').on('click.psDropdown', '.btn-dropdown-trigger', function(e) {
      e.stopPropagation();
      var $nav = $(this).closest('.ps-dropdown-nav');
      $('.ps-dropdown-nav').not($nav).removeClass('is-open');
      $nav.toggleClass('is-open');
    });

    $(document).off('click.psDropdownClose').on('click.psDropdownClose', function() {
      $('.ps-dropdown-nav').removeClass('is-open');
    });

    $(document).off('click.psNavItems').on('click.psNavItems', '.ps-dropdown-item', function() {
        var id = $(this).attr('id');
        var semanaActual = typeof getSemana === 'function' ? getSemana() : '';
        var sufijo = semanaActual ? '&semana=' + semanaActual : '';
        
        if (id === 'btn_Actividades') window.location.href = '/legacy/cambiar_pagina.php?seccion=programacion_semanal' + sufijo;
        else if (id === 'btn_CNP') window.location.href = '/legacy/cambiar_pagina.php?seccion=CNP' + sufijo;
        else if (id === 'btn_CNC') window.location.href = '/legacy/cambiar_pagina.php?seccion=CNC' + sufijo;
        else if (id === 'btn_Cal_Proveedores') window.location.href = '/legacy/cambiar_pagina.php?seccion=CIC' + sufijo;
    });
    $('#psAlertsMobile')
      .off('shown.bs.collapse.psLayout hidden.bs.collapse.psLayout')
      .on('shown.bs.collapse.psLayout hidden.bs.collapse.psLayout', function () {
        scheduleLayoutRefresh(0, true);
        scheduleActionsRowFit(0);
      });

    $(document)
      .off('show.bs.modal.psLegend', '#modal_leyenda_colores')
      .on('show.bs.modal.psLegend', '#modal_leyenda_colores', renderLegendModal);
  }

  function updateTableHeight() {
    if (!hot) return;
    var header = document.querySelector('.header-actions');
    var nav = document.querySelector('.ps-dropdown-nav');
    if (!header) return;
    
    var headerHeight = header.offsetHeight || 0;
    var vh = window.innerHeight;
    var offset = 180; // Offset base para otros elementos (Navbar, Breadcrumb, etc)
    
    if (window.innerWidth <= 991) {
      offset = 220; // Ajuste para móviles
    }
    
    var availableHeight = vh - headerHeight - offset;
    availableHeight = Math.max(300, availableHeight); // Mínimo de seguridad
    
    var container = document.getElementById('hot-container');
    if (container) {
      container.style.height = availableHeight + 'px';
      hot.refreshDimensions();
    }
  }

  function bindResize() {
    $(window)
      .off('resize.psHot orientationchange.psHot aia:viewport-scale-change.psHot')
      .on('resize.psHot orientationchange.psHot aia:viewport-scale-change.psHot', function () {
        scheduleLayoutRefresh(80, true);
        scheduleActionsRowFit(80);
        updateTableHeight();
      });
      
    var header = document.querySelector('.header-actions');
    if (header && window.ResizeObserver) {
      var ro = new ResizeObserver(function() {
        updateTableHeight();
      });
      ro.observe(header);
    }
  }

  function bindCncModal() {
    if (window.PS_HOT_OPTIONS && window.PS_HOT_OPTIONS.categoriasCnc) {
      var $cat = $('#hot_cat_cnc');
      $cat.empty().append(new Option('', ''));
      window.PS_HOT_OPTIONS.categoriasCnc.forEach(function(cat) {
        $cat.append(new Option(cat, cat));
      });
    }

    $('#hot_cat_cnc').off('change.psCnc').on('change.psCnc', function() {
      var cat = $(this).val();
      var $cnc = $('#hot_cnc');
      $cnc.empty().append(new Option('', ''));
      if (!cat) {
        $cnc.prop('disabled', true);
        return;
      }
      $cnc.prop('disabled', false);

      var db = getDb();
      $.ajax({
        method: 'POST',
        url: '/api/cnc/reasons',
        data: { categoria: cat },
      }).done(function (data) {
        var optionsHtml = "<option value=''></option>";
        if (Array.isArray(data)) {
          data.forEach(function(item) {
            var val = item.CNC;
            optionsHtml += "<option value='" + escapeHtml(val) + "'>" + escapeHtml(val) + "</option>";
          });
        }
        $cnc.html(optionsHtml);
        $cnc.append(new Option('Otra', 'Otra'));
      }).fail(function () {
        showFeedback('error', 'Error al cargar causas CNC.');
      });
    });

    $('#btn_guardar_cnc_hot').off('click.psCnc').on('click.psCnc', function() {
      var cat = String($('#hot_cat_cnc').val() || '').trim();
      var cnc = String($('#hot_cnc').val() || '').trim();
      var obs = String($('#hot_obs_cnc').val() || '').trim();
      
      var isCncStandard = cnc && cnc !== 'Otra' && cnc !== 'Otra...' && cnc !== 'Otros' && cnc !== 'Otros...';

      if (!cat) {
        showFeedback('error', 'Debe seleccionar una Categoría obligatoriamente.');
        return;
      }

      // Nueva regla (Permisiva): 
      // Se puede guardar si (Hay una Causa Estándar) OR (No hay Causa estándar, pero SÍ hay Observación explícita)
      if (!isCncStandard && !obs) {
        showFeedback('error', 'Debe brindar Observaciones obligatoriamente si no asigna una Causa específica.');
        return;
      }

      if (window._pendingCncSave && hot) {
        var pending = window._pendingCncSave;
        
        // Inyectamos los datos de CNC en la fila de Handsontable de forma visual
        hot.setDataAtRowProp(pending.rowIndex, 'Categoria_CNC', cat, 'internal-update');
        hot.setDataAtRowProp(pending.rowIndex, 'CNC', cnc, 'internal-update');
        hot.setDataAtRowProp(pending.rowIndex, 'Observaciones_CNC', obs, 'internal-update');
        
        var normalized = normalizeCellValue(pending.prop, pending.newValue);
        hot.setDataAtRowProp(pending.rowIndex, pending.prop, normalized.value, 'internal-update');
        
        // Inyectamos overrides al request AJAX para obviar el delay asíncrono del buffer visual de HOT
        var overrides = {
            Categoria_CNC: cat,
            CNC: cnc,
            Observaciones_CNC: obs
        };
        overrides[pending.prop] = normalized.value;
        
        saveRow(pending.rowIndex, pending.prop, pending.oldValue, overrides);
        
        window._pendingCncSave = null;
        $('#modal_cnc_hot').modal('hide');
      }
    });

    $('#btn_cancelar_cnc_hot').off('click.psCnc').on('click.psCnc', function() {
      window._pendingCncSave = null;
    });
  }

  function init() {
    if (!initialized) {
      bindToolbarActions();
      bindFilters();
      bindResize();
      bindCncModal();
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso').val() || getPermiso());
    }

    syncPhaseUI();
    loadData();
    scheduleActionsRowFit(0);
  }

  window.PSHotModule = {
    init: init,
    getHotInstance: function () { return hot; },
  };
})(window, jQuery);
