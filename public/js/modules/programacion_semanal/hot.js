(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var reloadTimer = null;
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var pendingDropdownAutoOpen = false;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var pendingViewportState = null;
  var masterData = [];
  var weeklyAlertFilters = [];
  var weeklyPhaseKey = 'programacion';
  var pendingDeleteRow = null;
  var sanitizedOnLoad = false;
  var mobileSaveState = {};

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

  // 16 = Es_TNP (columna técnica de flag binario, no debe ser visible para usuarios)
  // 19, 20, 21 = Categoria_CNC, CNC, Observaciones_CNC (siempre internas, el detalle se ve al expandir)
  var LEGACY_HIDDEN_COLUMN_INDEXES = [0, 2, 4, 5, 8, 16, 19, 20, 21];

  var PHASE_HIDDEN_PROPS = {
    programacion: ['Ejecutado_Real', 'PAC', 'P_Completado'],
    calificacion: ['cantidad_sugerida_auto', null],
  };

  function resolvePhaseHiddenIndexes(phaseKey, columnDefs) {
    var props = PHASE_HIDDEN_PROPS[phaseKey] || [];
    if (!Array.isArray(columnDefs)) { return []; }
    return props.map(function (prop) {
      for (var i = 0; i < columnDefs.length; i++) {
        if (columnDefs[i].data === prop) { return i; }
        if (prop === null && columnDefs[i].data === null && columnDefs[i].renderer === 'psActionsRenderer') { return i; }
      }
      return -1;
    }).filter(function (idx) { return idx >= 0; });
  }

  // Task 8 (2026-08-05). Dos indices cambian de piso:
  //  - 14 «Compromiso»: rendizaba 78 px y la cabecera —una sola palabra, que no
  //    puede envolver— necesita 85. Era la ultima cabecera recortada de PS tras
  //    recuperar el relleno horizontal del `th`.
  //  - 22 «Estado Operativo» (C-49p1): rendizaba 116 px. El boton
  //    `.ops-state-zoom` declara `container-type: inline-size` y una consulta
  //    `@container (max-width: 120px)` que ESCONDE el nombre del estado y deja
  //    solo el punto de color y el contador. La caja del contenedor es el ancho
  //    interior de la celda (columna menos 20 px de relleno), asi que a 116 px
  //    media 96 y la consulta disparaba siempre: «Lista para Confirmar» no se
  //    leia nunca. El umbral NO se baja —a menos ancho el nombre saldria
  //    mutilado—: la columna sube a 144 px para que el contenedor mida 124 y la
  //    consulta deje de aplicar. OJO con la aritmetica: el contenedor NO mide lo
  //    que mide la columna. `container-type: inline-size` consulta la CAJA DE
  //    CONTENIDO del propio boton, que es la columna menos los 10 px de relleno
  //    de la celda por lado y menos los 8 px de relleno del boton por lado: 36 px
  //    menos. Medido paso a paso: con la columna en 144 el contenedor media 108;
  //    con 158 media EXACTAMENTE 120 y `max-width: 120px` sigue cumpliendose (el
  //    limite es inclusivo). 164 - 36 = 128 > 120, con holgura para el redondeo.
  // Los 36 px extra salen de «Actividad» (indice 3, `max` 460, rendiza 202 y
  // envuelve por palabra; su palabra mas larga mide 133 px).
  var columnMinWidths = [
    34, 64, 34, 210, 36, 34, 120,
    120, 36, 54, 64, 72, 80, 72, 88,
    74, 54, 62, 36, 36, 36, 160, 164, 68,
  ];
  var columnFloorWidths = [
    28, 54, 28, 160, 28, 28, 92,
    92, 28, 52, 54, 62, 78, 62, 86,
    64, 46, 52, 28, 28, 28, 122, 164, 66,
  ];
  var columnMaxWidths = [
    90, 98, 120, 460, 120, 100, 238,
    238, 120, 86, 108, 122, 136, 122, 120,
    110, 84, 96, 170, 220, 260, 250, 192, 84,
  ];
  var columnShrinkPriority = [
    21, 20, 19, 9, 6, 2, 0,
    13, 12, 14, 8, 7, 15, 16, 18,
    17, 11, 10, 5, 3, 1, 4, 23, 22,
  ];

  var WEEKLY_ALERT_MODEL = {
    programacion: [
      {
        key: 'prog-bloqueo-critico-sin-compromiso',
        label: 'RC con restricciones',
        className: 'ps-alert-critical-route',
        priority: 'p1',
        description: 'Actividad de ruta crítica con condiciones pendientes para comprometer.',
        action: 'Escalar hoy y cerrar las acciones de habilitación indicadas en la fila.',
      },
      {
        key: 'prog-ejecucion-con-restricciones',
        label: 'Ejecución con restricciones',
        className: 'ps-alert-high',
        priority: 'p1',
        description: 'Actividad con avance registrado, pero con condiciones habilitantes pendientes.',
        action: 'Revisar restricciones pendientes antes de comprometer más producción.',
      },
      {
        key: 'prog-condiciones-pendientes',
        label: 'Condiciones Pendientes',
        className: 'ps-alert-medium',
        priority: 'p2',
        description: 'La actividad requiere acciones de habilitación antes de confirmar compromiso.',
        action: 'Completar las acciones indicadas por restricción y volver a autoprogramar o validar la fila.',
      },
      {
        key: 'prog-sin-compromiso',
        label: 'Por Comprometer',
        className: 'ps-alert-medium',
        priority: 'p2',
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
        label: 'Incumplida (RC)',
        className: 'ps-alert-critical-route',
        priority: 'p1',
        description: 'Compromiso no cumplido en ruta crítica.',
        action: 'Registrar CNC y activar recuperación diaria del camino crítico.',
      },
      {
        key: 'cal-incumplida',
        label: 'Incumplida',
        className: 'ps-alert-medium',
        priority: 'p2',
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
      {
        key: 'cal-tnp',
        label: 'Trabajo No Planificado',
        className: 'ps-alert-tnp',
        priority: 'p3',
        description: 'Actividad ejecutada sin compromiso previo',
        action: 'Registrar causa de programacion',
      },
    ],
  };

  // Presentacion de cada estado, con las claves de
  // docs/design-system/state-semantics.json (modulo `programacion-semanal`).
  // El chip declara QUE estado es -matiz para la identidad, severity+urgency
  // para la prioridad- y la capa de componentes lo pinta. Antes el nivel se
  // pintaba en `.ops-state-zoom` por bucket (critical/pending/ready) y el
  // matiz del chip no existia como dato.
  //
  // El nivel no sale del `priority` (p1/p2/p3) de WEEKLY_ALERT_MODEL: p3 agrupa
  // 'Lista para Confirmar'/'Cumplida Control' (healthy) con 'Trabajo No
  // Planificado' (neutral), que no es el mismo nivel pese a compartir prioridad
  // de fila. El nivel es el que declara el contrato por ETIQUETA.
  //
  // Guard de que esta tabla no se desvie del contrato:
  // tests/design-system/ops-state-contract.test.mjs
  var LEVEL_ATTRS = {
    neutral: { severity: 'none', urgency: 'none' },
    healthy: { severity: 'low', urgency: 'none' },
    attention: { severity: 'medium', urgency: 'soon' },
    urgent: { severity: 'high', urgency: 'now' },
  };

  var statePresentation = {
    'prog-bloqueo-critico-sin-compromiso': { level: 'urgent', hue: 'red' },
    'prog-ejecucion-con-restricciones': { level: 'urgent', hue: 'orange' },
    'prog-condiciones-pendientes': { level: 'attention', hue: 'amber' },
    'prog-sin-compromiso': { level: 'attention', hue: 'amber' },
    'prog-lista-para-confirmar': { level: 'healthy', hue: 'green' },
    'cal-incumplida-critica': { level: 'urgent', hue: 'red' },
    'cal-incumplida': { level: 'attention', hue: 'amber' },
    'cal-sin-calificar': { level: 'attention', hue: 'amber' },
    'cal-cumplida-control': { level: 'healthy', hue: 'green' },
    'cal-tnp': { level: 'neutral', hue: 'blue' },
    neutral: { level: 'neutral', hue: 'neutral' },
  };

  function stateChipAttrs(state) {
    var presentation = statePresentation[state] || statePresentation.neutral;
    var pair = LEVEL_ATTRS[presentation.level];
    return ' data-aia-hue="' + presentation.hue + '"'
      + ' data-aia-severity="' + pair.severity + '"'
      + ' data-aia-urgency="' + pair.urgency + '"';
  }

  function getDb() {
    return $('#baseDatos_PHP').val() || $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_canonico').val() || '').trim().toUpperCase();
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

    if (prop === 'Ejecutado_Real') {
      return getSemanalConfirmada() !== 1 || !isSemanalEditorRole(getPermiso());
    }

    if (!isUserAllowedToEdit()) {
      return true;
    }

    if ((prop === 'Compromiso' || prop === 'Sub_Contratista' || prop === 'Responsable_AIA') && getSemanalConfirmada() === 1) {
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
    // Wire screen-reader announcement to the <p class="mensaje" aria-live="polite">
    var $sr = $('#formulario_nuevo .mensaje');
    if ($sr.length) {
      $sr.text(message || '').attr('role', 'alert');
      clearTimeout($sr.data('_srTimer'));
      $sr.data('_srTimer', setTimeout(function () { $sr.text('').attr('role', 'status'); }, 4000));
    }

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
    } else if (type === 'info') {
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
        window.AIA.Notice.badge('info', message);
      } else {
        // Fallback: show inline in the mensaje element (no alert popup)
        if ($sr.length) {
          $sr.text(message || '').attr('role', 'status').addClass('ps-feedback-info').fadeIn(120);
          clearTimeout($sr.data('_srTimer'));
          $sr.data('_srTimer', setTimeout(function () { $sr.text('').fadeOut(250); }, 3000));
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

  var CONSTRUCTION_DEFAULTS = {
    restrictions: readinessActionProps,
    labels: readinessActionLabels,
    doneTexts: readinessDoneTexts,
    matrix: readinessActionMatrix,
  };

  // Solo las restricciones DURAS generan items de habilitacion (C-49).
  // Leia `cached.restrictions` -las 7 de Construccion- cuando el fallback de
  // abajo siempre fueron las 5 duras. Las 2 blandas (`Pdto_Cons`, `Modelo`)
  // entraban en la lista, no tenian entrada en `readinessActionMatrix` y salian
  // `conflict` incluso al 100%, encendiendo el rojo del boton en 47 de 57 filas
  // medidas. Programacion Intermedia ya lo resuelve igual:
  // `programacion_intermedia/hot.js:231` -> `readinessActionProps = hardKeys.slice()`.
  // Las blandas son seguimiento operativo y no bloquean habilitacion (ver el
  // texto de la leyenda mas abajo en este archivo); se siguen viendo en su
  // columna y en el drawer.
  function getConfigRestrictions() {
    var cached = window.__RESTRICTION_CONFIG__;
    if (cached && Array.isArray(cached.hardRestrictions) && cached.hardRestrictions.length > 0) {
      var normalized = [];
      for (var i = 0; i < cached.hardRestrictions.length; i++) {
        var entry = cached.hardRestrictions[i];
        if (typeof entry === 'string') {
          normalized.push(entry);
        } else if (entry && typeof entry === 'object' && typeof entry.key === 'string') {
          normalized.push(entry.key);
        }
      }
      if (normalized.length > 0) {
        return normalized;
      }
    }
    return CONSTRUCTION_DEFAULTS.restrictions;
  }

  function getConfigLabels() {
    var cached = window.__RESTRICTION_CONFIG__;
    return (cached && cached.labels && typeof cached.labels === 'object' && Object.keys(cached.labels).length > 0)
      ? cached.labels : CONSTRUCTION_DEFAULTS.labels;
  }

  function getConfigDoneTexts() {
    var cached = window.__RESTRICTION_CONFIG__;
    return (cached && cached.doneTexts && typeof cached.doneTexts === 'object' && Object.keys(cached.doneTexts).length > 0)
      ? cached.doneTexts : CONSTRUCTION_DEFAULTS.doneTexts;
  }

  function getConfigMatrix() {
    var cached = window.__RESTRICTION_CONFIG__;
    return (cached && cached.matrix && typeof cached.matrix === 'object' && Object.keys(cached.matrix).length > 0)
      ? cached.matrix : CONSTRUCTION_DEFAULTS.matrix;
  }

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
    var config = getConfigMatrix()[prop];
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
    var config = getConfigMatrix()[prop];
    var label = getConfigLabels()[prop] || prop;
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
      var doneText = getConfigDoneTexts()[prop] || 'Condición lista.';
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
    var restrictionProps = getConfigRestrictions();
    for (var i = 0; i < restrictionProps.length; i++) {
      var prop = restrictionProps[i];
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
    var openItems = compactItems.filter(function (item) {
      return isOpenActionStatus(item && item.status);
    });

    return {
      label: getStateLabelByKey(getStateKey(row)),
      state: getStateKey(row),
      actionItems: detailItems,
      compactItems: compactItems,
      openItems: openItems,
      actions: compactItems.map(function (item) { return item.text; }),
      activity: getPlainActivityLabel(row && row.Actividad),
      id: row && row.Id,
      phase: weeklyPhaseKey,
    };
  }

  function getOperationalStateSummary(view) {
    var openItems = Array.isArray(view && view.openItems) ? view.openItems : [];
    var compactItems = Array.isArray(view && view.compactItems) ? view.compactItems : [];
    var hasCritical = openItems.some(function (item) {
      return item && (item.status === 'critical' || item.status === 'conflict');
    });
    var hasPartial = openItems.some(function (item) {
      return item && item.status === 'partial';
    });
    var status = hasCritical ? 'critical' : (hasPartial ? 'partial' : (openItems.length ? 'pending' : 'ready'));
    var countText = openItems.length ? (openItems.length + ' pend.') : 'Sin pend.';
    var countAriaText = openItems.length
      ? (openItems.length + ' ' + (openItems.length === 1 ? 'pendiente' : 'pendientes'))
      : 'Sin pendientes';
    var focus = openItems.length ? openItems[0].label : 'Listo';

    return {
      countAriaText: countAriaText,
      countText: countText,
      focus: focus,
      status: status,
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
    view = view || {};
    view.actions = Array.isArray(view.actions) ? view.actions : [];
    var summary = getOperationalStateSummary(view || {});
    var stateLabel = view.label || 'Control';
    var aria = view.actions.length ? (stateLabel + '. ' + summary.countAriaText + '. Primer foco: ' + summary.focus) : stateLabel;

    return '<button type="button" class="ops-state-zoom is-' + escapeHtml(summary.status) + '" aria-label="' + escapeHtml(aria) + '. Ver detalle operativo">'
      + '<span class="ops-state-topline">'
      + '<span class="ops-state-dot" aria-hidden="true"></span>'
      + '<span class="ops-state-chip"' + stateChipAttrs(view.state) + '>' + escapeHtml(stateLabel) + '</span>'
      + '</span>'
      + '<span class="ops-state-summary">'
      + '<span class="ops-state-count is-' + escapeHtml(summary.status) + '">' + escapeHtml(summary.countText) + '</span>'
      + '</span>'
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
    var summary = getOperationalStateSummary(view || {});
    var html = '<div class="ops-state-drawer-state"><span class="ops-state-chip"'
      + stateChipAttrs(view.state) + '>' + escapeHtml(view.label) + '</span>';
    html += '<span class="ops-state-count">' + escapeHtml(summary.countText) + '</span>';
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
      var rowData = hot && Number.isInteger(visualRow) ? getSourceRowDataByVisualRow(hot, visualRow) : null;
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

  function getAlertClassForRow(row) {
    var stateKey = getStateKey(row || {});
    if (stateKey === 'prog-ejecucion-con-restricciones' && toNumber(row && row.Critica, 0) >= 1) {
      return 'ps-alert-critical-route';
    }
    return getAlertClassByState(stateKey);
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
        title: 'Fase: Calificación de Compromisos',
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
    // La navbar legacy (.container-fluid dentro de .context-bar) murio con el rollout
    // del shell lateral: si no esta, el anclaje vivo es #shellContextBar, que ya no
    // tiene breadcrumb sino los spans de proyecto/modulo y el menu de semana.
    var $contextContainer = $('.context-bar .container-fluid.d-flex.align-items-center.justify-content-between');
    var isShellBar = false;
    if (!$contextContainer.length) {
      $contextContainer = $('#shellContextBar');
      isShellBar = $contextContainer.length > 0;
    }
    if (!$contextContainer.length) {
      return null;
    }

    $contextContainer.addClass('context-has-weekly-phase');

    var $anchor = isShellBar
      ? $contextContainer.find('#ctxModulo').first()
      : $contextContainer.find('.context-breadcrumb').first();
    var $phaseWrap = $contextContainer.find('#ctxWeeklyPhase').first();
    if (!$phaseWrap.length) {
      $phaseWrap = $('<div id="ctxWeeklyPhase" class="context-weekly-phase context-weekly-phase--programacion"><strong class="ps-weekly-phase-title">Fase: Programación de Compromisos</strong></div>');
      if ($anchor.length) {
        $phaseWrap.insertAfter($anchor);
      } else {
        $contextContainer.prepend($phaseWrap);
      }
    }

    var $weekInfo = $contextContainer.find('.context-week-info').first();
    var $rightWrap = $contextContainer.find('.context-right-info').first();
    if (!$rightWrap.length) {
      // En el shell el chip de semana ya se empuja a la derecha con margin auto:
      // el ml-auto de bootstrap aqui competiria con el y partiria la barra.
      $rightWrap = $('<div class="context-right-info d-flex align-items-center"></div>');
      if (!isShellBar) {
        $rightWrap.addClass('ml-auto');
      }
      if ($weekInfo.length) {
        $weekInfo.appendTo($rightWrap);
      }
      if (isShellBar) {
        $rightWrap.insertAfter($phaseWrap);
      } else {
        $contextContainer.append($rightWrap);
      }
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

    syncFixedContextSpacer();
  }

  function syncFixedContextSpacer() {
    var context = document.querySelector('.context-bar.fixed-top');
    if (!context) {
      return;
    }

    var spacer = context.nextElementSibling;
    if (!spacer) {
      return;
    }

    var originalStyle = spacer.getAttribute('style') || '';
    if (spacer.dataset.psContextSpacer !== 'true' && originalStyle.indexOf('height: 100px') === -1) {
      return;
    }

    var contextBottom = Math.ceil(context.getBoundingClientRect().bottom || 0);
    if (contextBottom > 0) {
      spacer.dataset.psContextSpacer = 'true';
      spacer.style.height = Math.max(100, contextBottom) + 'px';
    }
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
    if (LEGACY_HIDDEN_COLUMN_INDEXES.indexOf(index) > -1) { return true; }
    var columnDefs = hot ? hot.getSettings().columns : null;
    if (Array.isArray(columnDefs)) {
      var phaseHidden = resolvePhaseHiddenIndexes(weeklyPhaseKey, columnDefs);
      if (phaseHidden.indexOf(index) > -1) { return true; }
    }
    return false;
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
    if (Number.isFinite(zoom) && zoom > 0) {
      return zoom;
    }

    if (root.classList.contains('tablet-scale-70')) {
      return 0.85;
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

  // C-19 (2026-08-05): el `title` de cabecera solo cuando el texto se recorta
  // de verdad. El task 26 lo ponia en TODAS desde `afterGetColHeader`, y ahi
  // «Id» acababa con un tooltip que decia «Id»: ruido, no ayuda. Medir dentro
  // del renderer no sirve para condicionarlo, porque Handsontable renderiza
  // varias veces y el ancho definitivo de la columna aun no esta aplicado -se
  // ven desbordes que luego no existen-. Por eso el barrido vive en
  // `afterRender`, cuando la medida ya es la final.
  function isHeaderClipped(node) {
    // Dos cortes distintos, los dos con `overflow: hidden` en el `th`
    // (handsontable-header-global.css): el vertical lo hace `-webkit-line-clamp: 2`
    // y se ve en scrollHeight; el horizontal lo produce una palabra que no cabe
    // y no se parte (`overflow-wrap: normal`) y se ve en scrollWidth. El margen
    // de 1 px absorbe el redondeo subpixel de anchos fraccionarios.
    return node.scrollWidth > node.clientWidth + 1 || node.scrollHeight > node.clientHeight + 1;
  }

  function refreshHeaderTitles(instance) {
    var hotInstance = instance || hot;
    var root = hotInstance && hotInstance.rootElement;
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    var headers = root.querySelectorAll('thead th .colHeader');
    for (var i = 0; i < headers.length; i++) {
      var node = headers[i];
      var text = String(node.textContent || '').replace(/\s+/g, ' ').trim();
      if (text && isHeaderClipped(node)) {
        node.title = text;
      } else {
        node.removeAttribute('title');
      }
    }
  }

  function syncRenderedTableWidth(instance) {
    var hotInstance = instance || hot;
    var container = document.getElementById('hot-container');
    if (!hotInstance || !container || typeof hotInstance.countCols !== 'function' || typeof hotInstance.getColWidth !== 'function') {
      return;
    }

    var totalWidth = 0;
    var columnCount = hotInstance.countCols();
    for (var col = 0; col < columnCount; col++) {
      totalWidth += Number(hotInstance.getColWidth(col)) || 0;
    }

    totalWidth = Math.max(Math.ceil(totalWidth), getContainerAvailableWidth());
    if (!Number.isFinite(totalWidth) || totalWidth <= 0) {
      return;
    }

    var width = totalWidth + 'px';
    container.classList.add('hot-fixed-columns');
    container.style.setProperty('--hot-table-width', width);

    var nodes = container.querySelectorAll('.handsontable table.htCore, .handsontable .wtHider, .handsontable .wtSpreader');
    Array.prototype.forEach.call(nodes, function (node) {
      node.style.setProperty('width', width, 'important');
      node.style.setProperty('min-width', width, 'important');
      if (node.matches && node.matches('table.htCore')) {
        node.style.setProperty('table-layout', 'fixed', 'important');
      }
    });
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

  function getSourceRowDataByVisualRow(instance, visualRow) {
    if (!instance || !Number.isInteger(visualRow) || visualRow < 0) {
      return null;
    }

    var physicalRow = typeof instance.toPhysicalRow === 'function' ? instance.toPhysicalRow(visualRow) : visualRow;
    if (!Number.isInteger(physicalRow) || physicalRow < 0 || typeof instance.getSourceDataAtRow !== 'function') {
      return null;
    }

    return instance.getSourceDataAtRow(physicalRow) || null;
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

  function cloneHotFilterConditions(conditions) {
    if (!Array.isArray(conditions)) {
      return [];
    }

    return conditions.map(function (stack) {
      var stackConditions = Array.isArray(stack && stack.conditions) ? stack.conditions : [];

      return {
        column: stack ? stack.column : null,
        operation: (stack && stack.operation) || 'conjunction',
        conditions: stackConditions.map(function (condition) {
          return {
            name: condition ? condition.name : '',
            args: Array.isArray(condition && condition.args) ? condition.args.slice() : [],
          };
        }).filter(function (condition) {
          return condition.name;
        }),
      };
    }).filter(function (stack) {
      return Number.isInteger(stack.column) && stack.conditions.length > 0;
    });
  }

  function getHotFiltersPlugin() {
    if (!hot || typeof hot.getPlugin !== 'function') {
      return null;
    }

    try {
      return hot.getPlugin('filters') || null;
    } catch (_err) {
      return null;
    }
  }

  function captureHotFilterConditions() {
    var filtersPlugin = getHotFiltersPlugin();
    var conditionCollection = filtersPlugin && filtersPlugin.conditionCollection;

    if (!conditionCollection || typeof conditionCollection.exportAllConditions !== 'function') {
      return [];
    }

    try {
      return cloneHotFilterConditions(conditionCollection.exportAllConditions());
    } catch (_err) {
      return [];
    }
  }

  function restoreHotFilterConditions(conditions) {
    var clonedConditions = cloneHotFilterConditions(conditions);
    if (clonedConditions.length === 0) {
      return;
    }

    var filtersPlugin = getHotFiltersPlugin();
    var conditionCollection = filtersPlugin && filtersPlugin.conditionCollection;

    if (!conditionCollection) {
      return;
    }

    try {
      if (typeof conditionCollection.clean === 'function' && typeof conditionCollection.addCondition === 'function') {
        conditionCollection.clean();
        clonedConditions.forEach(function (stack) {
          stack.conditions.forEach(function (condition) {
            conditionCollection.addCondition(stack.column, condition, stack.operation);
          });
        });
      } else if (typeof conditionCollection.importAllConditions === 'function') {
        conditionCollection.importAllConditions(clonedConditions);
      } else {
        return;
      }

      if (filtersPlugin && typeof filtersPlugin.filter === 'function') {
        filtersPlugin.filter();
      }
    } catch (_err) {
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
      syncRenderedTableWidth(hot);

      if (viewportState) {
        setTimeout(function () {
          restoreViewportState(viewportState);
        }, 0);
      }
    }, Number.isFinite(delay) ? delay : 24);
  }

  function getLegacyHiddenColumnsConfig(columnDefs) {
    var phaseHidden = resolvePhaseHiddenIndexes(weeklyPhaseKey, columnDefs);
    var allHidden = LEGACY_HIDDEN_COLUMN_INDEXES.slice().concat(phaseHidden).sort(function(a, b) { return a - b; });
    return {
      columns: allHidden,
      indicators: false,
      copyPasteEnabled: false,
    };
  }

  function applyLegacyColumnVisibility() {
    if (!hot) { return; }
    var settings = hot.getSettings() || {};
    var columnDefs = settings.columns;
    if (!Array.isArray(columnDefs)) { return; }
    var currentHidden = settings.hiddenColumns && Array.isArray(settings.hiddenColumns.columns)
      ? settings.hiddenColumns.columns : [];
    var config = getLegacyHiddenColumnsConfig(columnDefs);
    if (arraysEqualNumbers(currentHidden, config.columns)) { return; }
    hot.updateSettings({ hiddenColumns: config });
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
      if (isBlank(row.Consecutivo) && isBlank(row.row_id)
          && isBlank(row.Id) && isBlank(row.Actividad)) {
        continue;
      }
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
      showFeedback('error', 'No se pudieron cargar las actividades de la semana. Recarga la página para volver a intentarlo.');
    });
  }

  function loadData() {
    weeklyPhaseKey = getPhaseKey();
    showLoading(true);

    if (!sanitizedOnLoad && canManageToolbarActions()) {
      sanitizedOnLoad = true;
      var db = getDb();
      var semana = getSemana();
      $.ajax({
        method: 'POST',
        url: '/api/semanal/save?db=' + encodeURIComponent(db),
        dataType: 'json',
        data: { opcion: 'sanear', semana: semana, _csrf_token: $('meta[name="csrf-token"]').attr('content') || '' },
      }).always(function () {
        requestList();
      });
    } else {
      requestList();
    }
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

  function requiresCnc(row, realValue) {
    var compromiso = toNumber(row && row.Compromiso, null);
    var real = toNumber(realValue, null);
    return real !== null && compromiso !== null && real < compromiso;
  }

  function queueCncSave(visualRow, rowData, prop, oldValue, newValue, saveContext) {
    window._pendingCncSave = {
      rowIndex: visualRow,
      consecutivo: rowData.Consecutivo,
      sourceRowData: rowData,
      oldValue: oldValue,
      newValue: newValue,
      prop: prop,
      mobile: Boolean(saveContext && saveContext.mobile),
    };
    var categoria = rowData.Categoria_CNC || '';
    $('#hot_cat_cnc').val(categoria);
    $('#hot_cnc').empty().append(new Option('', '')).prop('disabled', isBlank(categoria));
    $('#hot_obs_cnc').val(rowData.Observaciones_CNC || '');
    if (!isBlank(categoria)) {
      $('#hot_cat_cnc').trigger('change.psCnc', [rowData.CNC || '']);
    }
    var $cncModal = $('#modal_cnc_hot');
    $cncModal.off('.psCncState').data('psCncShown', false)
      .one('shown.bs.modal.psCncState', function () {
        $cncModal.data('psCncShown', true);
        $('#hot_cat_cnc').trigger('focus');
      })
      .one('hidden.bs.modal.psCncState', function () { $cncModal.data('psCncShown', false); })
      .modal('show');
  }

  function closeCncModal() {
    var $cncModal = $('#modal_cnc_hot');
    if ($cncModal.data('psCncShown')) {
      $cncModal.modal('hide');
      return;
    }
    $cncModal.one('shown.bs.modal.psCncDeferredClose', function () {
      $cncModal.modal('hide');
    });
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
        _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
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
        Es_TNP: rawData.Es_TNP || '',
      },
    };
  }

  function revertCell(visualRow, prop, oldValue) {
    var col = hot.propToCol(prop);
    if (col >= 0) {
      hot.setDataAtCell(visualRow, col, oldValue, 'revert');
    }
  }

  function syncMasterDataRow(row, values) {
    if (!row) { return row; }
    var rowId = String(row.Consecutivo == null ? '' : row.Consecutivo);
    for (var i = 0; i < masterData.length; i++) {
      if (String(masterData[i].Consecutivo == null ? '' : masterData[i].Consecutivo) === rowId) {
        $.extend(masterData[i], values || {});
        return masterData[i];
      }
    }
    return row;
  }

  function saveRow(visualRow, prop, oldValue, overrides, saveContext) {
    var db = getDb();
    var isMobileSave = Boolean(saveContext && saveContext.mobile);
    var physicalRow = hot.toPhysicalRow(visualRow);
    var row = hot.getSourceDataAtRow(physicalRow);
    var syncHotProp = function (targetProp, value) {
      if (!isMobileSave) {
        hot.setDataAtRowProp(visualRow, targetProp, value, 'internal-update');
      }
    };

    var payload = buildPayload(row || {}, prop, overrides || {});
    if (!payload.valid) {
      if (!isMobileSave) { revertCell(visualRow, prop, oldValue); }
      showFeedback('warning', payload.error); // Warning en lugar de Error para validaciones de negocio
      setMobileSaveState(visualRow, prop, 'error', payload.error);
      renderMobileCards(getFilteredRows());

      if (!isMobileSave) {
        var colIndex = hot.propToCol(prop);
        var td = hot.getCell(visualRow, colIndex);
        if (td) {
          td.classList.remove('ps-cell-shake');
          void td.offsetWidth;
          td.classList.add('ps-cell-shake');
        }
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
            row = syncMasterDataRow(row, overrides);
          }
          row.cantidad_sugerida_auto = calculateSuggested(row);
          row.estado_operativo = getStateDisplayText(row);
          // Forzar actualización visual de campos computados
          syncHotProp('estado_operativo', row.estado_operativo);
          syncHotProp('cantidad_sugerida_auto', row.cantidad_sugerida_auto);

          // Recalcular PAC y P_Completado localmente (skip TNP rows)
          var esTnpRow = row.Es_TNP === 1 || row.Es_TNP === '1';
          if (!esTnpRow && typeof getStateKey === 'function') {
            esTnpRow = (getStateKey(row) === 'cal-tnp');
          }
          var comp = toNumber(payload.data.Compromiso, null);
          var real = toNumber(payload.data.Real, null);
          if (!esTnpRow && comp !== null && comp > 0 && real !== null && real >= 0) {
            row.P_Completado = real / comp;
            row.PAC = (real < comp) ? 0 : 1;
            // Forzar actualización visual en celdas readOnly
            syncHotProp('PAC', row.PAC);
            syncHotProp('P_Completado', row.P_Completado);

            // Si cumplió (PAC=1), limpiar CNC visualmente
            if (row.PAC === 1) {
              row.Categoria_CNC = null;
              row.CNC = null;
              row.Observaciones_CNC = null;
              syncHotProp('Categoria_CNC', null);
              syncHotProp('CNC', null);
              syncHotProp('Observaciones_CNC', null);
            }
          }
        }

        if (response.reload_hot === true) {
            if (typeof loadData === 'function') {
                loadData();
            }
            showFeedback('success', 'Guardado. (Nuevas actividades insertadas)');
            return;
        }

        setMobileSaveState(visualRow, prop, 'success', 'Guardado');
        if (!isMobileSave) { hot.render(); }
        updateLegendCounts(getFilteredRows());
        renderMobileCards(getFilteredRows());

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
      if (!isMobileSave) { revertCell(visualRow, prop, oldValue); }
      setMobileSaveState(visualRow, prop, 'error', message);
      renderMobileCards(getFilteredRows());
      showFeedback('error', message);
    }).fail(function () {
      if (!isMobileSave) { revertCell(visualRow, prop, oldValue); }
      setMobileSaveState(visualRow, prop, 'error', 'Error de red');
      renderMobileCards(getFilteredRows());
      showFeedback('error', 'No se pudo guardar: sin conexión con el servidor. Revisa la red y vuelve a escribir el dato.');
    });
  }

  function setupRenderers() {
    if (renderersRegistered) {
      return;
    }

    Handsontable.renderers.registerRenderer('psActionsRenderer', function (instance, td, row) {
      Handsontable.dom.empty(td);
      td.classList.remove('ps-alert-critical-route', 'ps-alert-critical', 'ps-alert-high', 'ps-alert-medium', 'ps-alert-info', 'ps-alert-control', 'ps-alert-neutral');

      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var alertClass = getAlertClassForRow(rowData);

      td.classList.add('ps-row-state', alertClass);
      td.classList.add('htCenter', 'htMiddle');

      var phase = getSemanalConfirmada();
      var permiso = getPermiso();
      var html = '';
      if (phase !== 1 && permiso !== 'C') {
        html += "<button type='button' class='ps-action-btn duplicar btn btn-success btn-sm btn-action-gap' data-action='duplicate' title='Duplicar Actividad' aria-label='Duplicar Actividad'><span class='ps-action-icon' aria-hidden='true'><i class='fa fa-copy fa-xs'></i></span></button>";
        html += "<button type='button' class='ps-action-btn eliminar btn btn-danger btn-sm btn-action-gap' data-action='delete' title='Eliminar Actividad' aria-label='Eliminar Actividad'><span class='ps-action-icon' aria-hidden='true'><i class='fa fa-trash-alt fa-xs'></i></span></button>";
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
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var view = getStateView(rowData);
      td.innerHTML = renderOperationalStateCell(view);
      var trigger = td.querySelector('.ops-state-zoom');
      if (trigger) {
        trigger.setAttribute('data-row', String(row));
      }
      var accessibleStateText = view.actions.length ? (view.label + ' - ' + view.actions.join('; ')) : view.label;
      td.title = accessibleStateText;
      if (trigger) {
        trigger.setAttribute('title', accessibleStateText);
        trigger.setAttribute('aria-label', accessibleStateText + '. Ver detalle operativo');
      }
      td.classList.remove('force-wrap');
      td.classList.add('htLeft', 'htMiddle', 'ops-state-td');
    });

    Handsontable.renderers.registerRenderer('psPacRenderer', function (instance, td, row, col, prop, value) {
      // TNP exclusion: show dash instead of PAC value for TNP rows
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var esTnp = rowData.Es_TNP === 1 || rowData.Es_TNP === '1';
      if (!esTnp && typeof getStateKey === 'function') {
        var state = getStateKey(rowData);
        esTnp = (state === 'cal-tnp');
      }
      if (esTnp) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.innerHTML = '<span class="ps-muted-dash">—</span>';
        td.classList.add('htCenter');
        return;
      }
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = formatPercent(value, 1);
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psActividadRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var prefix = parseInt(rowData.alerta_crisis, 10) === 1 ? '🔥 ' : '';
      td.textContent = prefix + stripHtmlTags(value);
      td.classList.add('htLeft', 'htMiddle', 'force-wrap');
    });

    Handsontable.renderers.registerRenderer('psIdRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var badgeLabel = isManualActivity(rowData) ? 'Manual' : 'Auto';
      var badgeClass = isManualActivity(rowData) ? 'is-manual' : 'is-auto';
      td.innerHTML = '<div class="ps-id-stack"><span class="badge ps-origin-badge ' + badgeClass + '">' + badgeLabel + '</span><span class="ps-id-value">' + escapeHtml(value != null ? value : '') + '</span></div>';
      td.classList.add('htCenter', 'htMiddle');
    });

    Handsontable.renderers.registerRenderer('psRatioRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      var ratio = toNumber(value, null);
      if (ratio === null) {
        td.textContent = '';
      } else {
        var label = prop === 'Ejecutado_Fin_Semana' ? 'Ejecutado al fin de semana' : 'Ejecutado actual';
        td.innerHTML = renderExecutionRatioProgress(rowData, ratio, label);
      }
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psCompromisoRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
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
          var esTnp = rowData.Es_TNP === 1 || rowData.Es_TNP === '1';
          var realProgress = getRealExecutionProgress(rowData, numeric);
          if (esTnp) {
            td.innerHTML = "<div class='ps-progress-cell ps-progress-cell--real' title='" + escapeHtml(realProgress.label) + "'><span><span class='ps-tnp-badge'>TNP</span><span class='ps-commit-value'>" + escapeHtml(textValue) + '</span></span>'
              + renderMiniProgress(realProgress.ratio, realProgress.label, realProgress.caption) + '</div>';
          } else {
            var compromisoRow = toNumber(rowData.Compromiso, 0);
            var isRealLow = numeric < (compromisoRow - 0.0001);
            var indicatorClassReal = isRealLow ? 'ps-commit-indicator is-low' : 'ps-commit-indicator is-ok';
            var indicatorIconReal = isRealLow ? '⚠' : '✓';
            var indicatorTitleReal = isRealLow
              ? 'Ejecutado menor al compromiso (Requiere CNC)'
              : 'Compromiso cumplido';

            td.innerHTML = "<div class='ps-progress-cell ps-progress-cell--real' title='" + escapeHtml(realProgress.label) + "'><span><span class='ps-commit-value'>" + escapeHtml(textValue) + "</span><span class='" + indicatorClassReal + "' title='" + escapeHtml(indicatorTitleReal) + "' aria-label='" + escapeHtml(indicatorTitleReal) + "'>" + indicatorIconReal + '</span></span>'
              + renderMiniProgress(realProgress.ratio, realProgress.label, realProgress.caption) + '</div>';
          }
        } else {
          td.textContent = textValue;
        }
      }
      td.classList.add('htCenter');
    });

    Handsontable.renderers.registerRenderer('psPptoRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
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
      var rowData = instance && typeof instance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(instance, row) || {}) : {};
      if (isBlank(value) && isActiveRowForCommitments(rowData)) {
        td.classList.add('ps-cell-empty-alert');
        td.innerHTML = '<span class="ps-missing-assignment" title="Falta asignación (Bloquea confirmación)">⚠ Sin asignar</span>';
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
      var filterConditions = captureHotFilterConditions();
      pendingViewportState = captureViewportState();
      hot.loadData(data);
      applyLegacyColumnVisibility();
      restoreHotFilterConditions(filterConditions);
      scheduleLayoutRefresh(0, true);
      return;
    }

    var container = document.getElementById('hot-container');
    if (!container) {
      return;
    }

    var columnDefs = [
      { data: 'Consecutivo', readOnly: true, className: 'htCenter htMiddle' },
      { data: 'Id', readOnly: true, renderer: 'psIdRenderer', className: 'htCenter htMiddle' },
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
      { data: 'Es_TNP', type: 'numeric', readOnly: true, visible: false, className: 'htCenter' },
      { data: 'PAC', readOnly: true, renderer: 'psPacRenderer', className: 'htCenter htMiddle' },
      { data: 'P_Completado', readOnly: true, renderer: 'psPacRenderer', className: 'htCenter htMiddle' },
      { data: 'Categoria_CNC', type: 'text', className: 'htCenter htMiddle force-wrap' },
      { data: 'CNC', type: 'text', className: 'htLeft htMiddle force-wrap' },
      { data: 'Observaciones_CNC', type: 'text', className: 'htLeft htMiddle force-wrap' },
      { data: 'estado_operativo', readOnly: true, renderer: 'psStateRenderer', className: 'htLeft htMiddle ops-state-td' },
      { data: null, renderer: 'psActionsRenderer', readOnly: true },
    ];

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
        '',
        'PAC',
        '% Completado',
        'Categoría CNC',
        'CNC',
        'Obs. CNC',
        'Estado Operativo',
        'Acciones',
      ],
      columns: columnDefs,
      hiddenColumns: getLegacyHiddenColumnsConfig(columnDefs),
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
      colHeaderHeight: 72,
      width: '100%',
      height: getContainerAvailableHeight() || '100%',
      afterRender: function () {
        syncRenderedTableWidth(this);
        // C-19: necesita el ancho ya aplicado, por eso no va en el renderer.
        refreshHeaderTitles(this);
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
        headerNode.classList.remove('ps-header-single-word');

        if (headerText && headerText.indexOf(' ') === -1) {
          headerNode.classList.add('ps-header-single-word');
        }

        // Task 26 ponia aqui el `title`. C-19 (2026-08-05) lo movio a
        // `refreshHeaderTitles()`, que corre en `afterRender`: solo con el
        // ancho definitivo aplicado se sabe si el texto se recorta de verdad.
        // La clase de arriba si depende solo del texto y se queda.
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
        var hotInstance = (this && this.instance) || hot;
        var rowData = hotInstance && typeof hotInstance.getSourceDataAtRow === 'function' ? (getSourceRowDataByVisualRow(hotInstance, row) || {}) : {};

        var alertClass = getAlertClassForRow(rowData);
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

        if (parseInt(rowData.alerta_crisis, 10) === 1) {
          finalClass += ' ps-row-crisis';
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
                var rowData = getSourceRowDataByVisualRow(hot, toReject[0]) || {};
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

          var rowData = getSourceRowDataByVisualRow(hot, rowIndex) || {};

          if (prop === 'Ejecutado_Real') {
            var esTnpRow = rowData.Es_TNP === 1 || rowData.Es_TNP === '1';
            if (!esTnpRow && typeof getStateKey === 'function') {
              esTnpRow = (getStateKey(rowData) === 'cal-tnp');
            }
            if (esTnpRow) {
              continue;
            }
          }

          // HARD GUARD: Block real execution registration if missing assignees
          if (prop === 'Ejecutado_Real') {
            var isSubMissing = isBlank(rowData.Sub_Contratista);
            var isResMissing = isBlank(rowData.Responsable_AIA);

            if (isSubMissing || isResMissing) {
              revertCell(rowIndex, prop, oldValue);
              showFeedback('error', 'Falta Sub-Contratista o Resp. AIA para registrar avance');
              continue;
            }

            // Si el avance real es menor al compromiso, SIEMPRE pedir CNC
            if (requiresCnc(rowData, newValue)) {
                queueCncSave(rowIndex, rowData, prop, oldValue, newValue, { mobile: false });

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

    import('/js/design-system/ht-empty-state.js').then(function (mod) {
      if (!hot || hot.isDestroyed) { return; }
      mod.attachHtEmptyState(hot, {
        titulo: 'Sin actividades programadas esta semana',
        cuerpo: 'Usa «Agregar Actividad» para programar una, o «Autoprogramar Actividades» para traerlas desde la programación intermedia.',
      });
    });

    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();
    container.addEventListener('mousedown', function () {
      if (hot && !hot.isDestroyed) { hot.listen(); }
    }, true);

    bindRowActionClicks();

    if (window.LPSContextualDrawer) {
      window.LPSContextualDrawer.init(hot, 'programacion-semanal', function(rowData) {
        var view = getStateView(rowData);
        view.key = view.state;
        view.rowClass = getAlertClassForRow(rowData);
        view.rowData = rowData;
        return view;
      });
    }

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

  function updateLegendCounts(rows) {
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
      var value = counts[item.key] || 0;
      /* Un chip que marca cero no tiene nada que reclamar: se atenua con
         `is-zero` y recupera su color saturado en cuanto vuelve a contar. */
      $('#count-' + item.key)
        .text('(' + value + ')')
        .closest('.pdc-legend-item')
        .toggleClass('is-zero', value === 0);
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

    updateLegendCounts(filtered);
    updateOrInitHot(filtered);
    renderMobileCards(filtered);
  }

  function renderAlertLegend() {
    var config = getAlertConfig(weeklyPhaseKey);
    var html = '';
    for (var i = 0; i < config.length; i++) {
      var item = config[i];
      html += "<span class='pdc-legend-item " + escapeHtml(item.className) + "' data-filter='" + escapeHtml(item.key) + "' role='button' tabindex='0'><span class='indicator'></span>" +
        escapeHtml(item.label) + " <span id='count-" + escapeHtml(item.key) + "' class='count-badge'>(...)</span></span>";
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
          "<span class='ps-legend-modal-swatch ps-legend-quick-swatch " + escapeHtml(state.className) + "'></span>" +
          "<div class='ps-legend-quick-state'><strong>" + escapeHtml(state.label) + '</strong><small>' + escapeHtml(state.description || '') + '</small></div>' +
          "<div class='ps-legend-quick-action'><strong>Acción:</strong> " + escapeHtml(state.action || 'Gestionar según plan de obra.') + '</div>' +
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
      "<p class='ps-legend-quick-alert-intro mt-2'><strong>Restricciones blandas:</strong> Pdto. Constructivo y Modelo BIM son seguimiento operativo. Se muestran en ámbar cuando están pendientes, pero no bloquean autoprogramación ni habilitación.</p>" +
    '</section>' +
    '</div>';

    $('#modal_leyenda_colores_ps_Label').text('Guía Operativa - Programación Semanal (' + phaseLabel + ')');
    $('#modal_leyenda_colores_ps_body').html(html);
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
    $('#btn_tnp, #btn_reabrir_semana').removeClass('ps-runtime-hidden');
    var fechaCierre = String($('#fechaCierreCompromisos').val() || '').trim();
    var phaseInfo = getWeeklyPhaseInfo(weeklyPhaseKey, fechaCierre);

    if (weeklyPhaseKey === 'calificacion') {
      $('#phase-badge').text('Calificación').removeClass('aia-chip--info').addClass('aia-chip--warning');
      $('#weeklyPhaseMobileLabel').text(phaseInfo.mobileLabel);
      $('#btn_autoprogramar').hide();
      $('#btn_agregar_actividad').hide();
      $('#btn_cerrar_compromisos_semana').hide();
      $('#btn_informe_compromisos').show();
      if (isSemanalEditorRole(getPermiso())) {
        $('#btn_tnp').show();
      } else {
        $('#btn_tnp').hide();
      }
      if (fechaCierre) {
        $('#textoFechaCierreCompromisos').text('Compromisos cerrados el ' + fechaCierre);
      } else {
        $('#textoFechaCierreCompromisos').text('Compromisos cerrados. Semana en evaluación.');
      }
      // Admin can reopen a closed week
      if (getPermiso() === 'A') {
        $('#btn_reabrir_semana').show();
      } else {
        $('#btn_reabrir_semana').hide();
      }
    } else {
      $('#phase-badge').text('Programación').removeClass('aia-chip--warning').addClass('aia-chip--info');
      $('#weeklyPhaseMobileLabel').text(phaseInfo.mobileLabel);
      // Solo se muestran a quien puede usarlos: este .show() pisaba el display:none que
      // maestroPermisos() aplica por rol, y dejaba los tres botones visibles pero grises
      // para V, C, OT, G, S y SG.
      if (canManageToolbarActions()) {
        $('#btn_autoprogramar').show();
        $('#btn_agregar_actividad').show();
        $('#btn_cerrar_compromisos_semana').show();
      } else {
        $('#btn_autoprogramar').hide();
        $('#btn_agregar_actividad').hide();
        $('#btn_cerrar_compromisos_semana').hide();
      }
      $('#btn_informe_compromisos').hide();
      $('#btn_tnp').hide();
      $('#btn_reabrir_semana').hide();
      $('#textoFechaCierreCompromisos').text('');
    }

    var canManage = canManageToolbarActions();
    $('#btn_autoprogramar').prop('disabled', !canManage);
    $('#btn_agregar_actividad').prop('disabled', !canManage);
    $('#btn_cerrar_compromisos_semana').prop('disabled', !canManage);

    syncContextPhaseIndicator(weeklyPhaseKey, fechaCierre);

    applyLegacyColumnVisibility();

    renderAlertLegend();
    updateLegendCounts(getFilteredRows());
    renderLegendModal();
    syncLegendVisualState();
  }

  function formatWeeklyQuantity(value, unidad) {
    var number = toNumber(value, null);
    if (number === null) {
      return '0,0' + (unidad ? (' ' + unidad) : '');
    }
    return formatDecimalComma(number, 1) + (unidad ? (' ' + unidad) : '');
  }

  function clampProgressRatio(value) {
    var number = toNumber(value, null);
    if (number === null) {
      return null;
    }
    if (number < 0) {
      return 0;
    }
    if (number > 1) {
      return 1;
    }
    return number;
  }

  function getProgressCssValue(ratio) {
    var clamped = clampProgressRatio(ratio);
    return clamped === null ? '0%' : (clamped * 100).toFixed(2) + '%';
  }

  function formatExecutionRatioValue(row, ratio) {
    var clamped = clampProgressRatio(ratio);
    if (clamped === null) {
      return 'Sin dato';
    }
    var ppto = toNumber(row && row.cantidad_ppto, null);
    var unit = String((row && row.Unidad) || '%').trim() || '%';
    if (ppto === null || ppto <= 0) {
      return formatPercent(clamped, 1);
    }
    return formatDecimalComma(clamped * ppto, 1) + ' ' + unit + ' (' + formatPercent(clamped, 1) + ')';
  }

  function getTotalExecutionRatioAfterReal(row, realValue) {
    var base = clampProgressRatio(row && row.Ejecutado);
    var real = toNumber(realValue, null);
    if (real === null) {
      return base;
    }
    var ppto = toNumber(row && row.cantidad_ppto, null);
    var delta = ppto !== null && ppto > 0 ? (real / ppto) : (real / 100);
    return clampProgressRatio((base || 0) + delta);
  }

  function renderMiniProgress(ratio, label, caption) {
    var clamped = clampProgressRatio(ratio);
    var aria = label || (clamped === null ? 'Avance sin dato' : 'Avance ' + formatPercent(clamped, 1));
    var html = '<progress class="ps-progress-track" max="1" value="' + (clamped || 0) + '" aria-label="' + escapeHtml(aria) + '"></progress>';
    if (caption) {
      html += '<small class="ps-progress-caption">' + escapeHtml(caption) + '</small>';
    }
    return html;
  }

  function renderExecutionRatioProgress(row, ratio, label) {
    var clamped = clampProgressRatio(ratio);
    if (clamped === null) {
      return '';
    }
    var text = formatExecutionRatioValue(row, clamped);
    return '<div class="ps-progress-cell"><span class="ps-progress-value">' + escapeHtml(text) + '</span>'
      + renderMiniProgress(clamped, label + ': ' + formatPercent(clamped, 1), '') + '</div>';
  }

  function getRealExecutionProgress(row, realValue) {
    var unit = String((row && row.Unidad) || '%').trim() || '%';
    var total = getTotalExecutionRatioAfterReal(row, realValue);
    var realText = formatWeeklyQuantity(realValue, unit);
    var totalText = total === null ? 'sin dato' : formatPercent(total, 1);
    return {
      caption: 'Ejecutamos ' + realText + ' · Total ' + totalText,
      label: 'Ejecutamos ' + realText + ' y el avance total de la actividad quedó en ' + totalText,
      ratio: total,
    };
  }

  function renderMobileMetric(label, value, footerHtml) {
    var display = isBlank(value) ? 'Sin dato' : value;
    return '<div class="ps-mobile-metric"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(display) + '</strong>' + (footerHtml || '') + '</div>';
  }

  function getMobileSaveKey(rowIndex, prop) {
    return String(rowIndex) + ':' + String(prop || '');
  }

  function setMobileSaveState(rowIndex, prop, status, message) {
    mobileSaveState[getMobileSaveKey(rowIndex, prop)] = { status: status, message: message };
  }

  function getMobileSaveState(rowIndex, prop) {
    return mobileSaveState[getMobileSaveKey(rowIndex, prop)] || { status: 'idle', message: '' };
  }

  function renderMobileProgressMetric(label, row, prop) {
    var ratio = clampProgressRatio(row && row[prop]);
    var display = formatExecutionRatioValue(row, ratio);
    return '<div class="ps-mobile-metric ps-mobile-metric--progress"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(display) + '</strong>'
      + renderMiniProgress(ratio, label + ': ' + (ratio === null ? 'sin dato' : formatPercent(ratio, 1)), '') + '</div>';
  }

  function renderMobileEditableMetric(label, prop, row, rowIndex, footerHtml) {
    var value = row && row[prop] != null ? row[prop] : '';
    if (!editableProps[prop] || isPropReadOnly(prop)) {
      return renderMobileMetric(label, value, footerHtml);
    }
    var state = getMobileSaveState(rowIndex, prop);
    var inputId = 'ps-mobile-' + prop + '-' + rowIndex;
    var buttonLabel = prop === 'Ejecutado_Real' ? 'Guardar avance' : 'Guardar compromiso';
    var disabled = state.status === 'saving' ? ' disabled' : '';
    return '<div class="ps-mobile-metric ps-mobile-metric--editable"><label for="' + escapeHtml(inputId) + '">' + escapeHtml(label) + '</label>'
      + '<div class="ps-mobile-edit-controls"><input id="' + escapeHtml(inputId) + '" type="number" inputmode="decimal" step="0.1" min="0" data-mobile-row="' + rowIndex + '" data-mobile-prop="' + escapeHtml(prop) + '" value="' + escapeHtml(value) + '">'
      + '<button type="button" class="ps-mobile-save-button" data-mobile-save-row="' + rowIndex + '" data-mobile-save-prop="' + escapeHtml(prop) + '"' + disabled + '><i class="fas fa-save" aria-hidden="true"></i><span>' + escapeHtml(buttonLabel) + '</span></button></div>'
      + '<small class="ps-mobile-save-status is-' + escapeHtml(state.status) + '" data-mobile-save-status aria-live="polite">' + escapeHtml(state.message) + '</small>'
      + (footerHtml || '') + '</div>';
  }

  function renderMobileRealMetric(row, rowIndex) {
    var value = row && row.Ejecutado_Real != null ? row.Ejecutado_Real : '';
    var progress = getRealExecutionProgress(row, value);
    var footer = renderMiniProgress(progress.ratio, progress.label, progress.caption);
    return renderMobileEditableMetric('Ejecutado real', 'Ejecutado_Real', row, rowIndex, footer);
  }

  function renderMobileStateButton(row, rowIndex) {
    var view = getStateView(row || {});
    var summary = getOperationalStateSummary(view);
    return '<button type="button" class="ps-mobile-state ops-state-zoom is-' + escapeHtml(summary.status) + '" data-mobile-ops-row="' + rowIndex + '">'
      + '<span class="ops-state-topline"><span class="ops-state-dot" aria-hidden="true"></span><span class="ops-state-chip">' + escapeHtml(view.label) + '</span></span>'
      + '<span class="ops-state-summary"><span class="ops-state-count is-' + escapeHtml(summary.status) + '">' + escapeHtml(summary.countText) + '</span></span></button>';
  }

  function renderMobileCard(row, rowIndex) {
    var alertClass = getAlertClassForRow(row);
    var unidad = isBlank(row.Unidad) ? '' : String(row.Unidad);
    var title = getPlainActivityLabel(row.Actividad);
    var html = '<article class="ps-mobile-card ps-row-state ' + escapeHtml(alertClass) + '" data-mobile-row="' + rowIndex + '">';
    html += '<header><div><span class="ps-mobile-id">' + escapeHtml(row.Id || row.Consecutivo || '') + '</span><h3>' + escapeHtml(title) + '</h3></div>' + renderMobileStateButton(row, rowIndex) + '</header>';
    html += '<div class="ps-mobile-metrics">';
    html += renderMobileMetric('Subcontratista', row.Sub_Contratista);
    html += renderMobileMetric('Resp. AIA', row.Responsable_AIA);
    html += renderMobileMetric('Unidad', unidad || row.Unidad);
    html += renderMobileProgressMetric('Ejecutado actual', row, 'Ejecutado');
    html += renderMobileProgressMetric('Ej. fin semana', row, 'Ejecutado_Fin_Semana');
    if (weeklyPhaseKey === 'calificacion') {
      html += renderMobileEditableMetric('Compromiso', 'Compromiso', row, rowIndex);
      html += renderMobileRealMetric(row, rowIndex);
    } else {
      html += renderMobileMetric('Sugerida', formatWeeklyQuantity(row.cantidad_sugerida_auto, unidad));
      html += renderMobileEditableMetric('Compromiso', 'Compromiso', row, rowIndex);
    }
    html += '</div></article>';
    return html;
  }

  function renderMobileCards(rows) {
    var container = document.getElementById('mobile-card-view');
    if (!container) {
      return;
    }
    var list = Array.isArray(rows) ? rows : [];
    if (!list.length) {
      container.innerHTML = '<div class="ps-mobile-empty">No hay actividades con los filtros actuales.</div>';
      return;
    }
    var html = '';
    for (var i = 0; i < list.length; i++) {
      html += renderMobileCard(list[i] || {}, i);
    }
    container.innerHTML = html;
    bindMobileCardEvents();
  }

  function commitMobileCardValue(rowIndex, prop, value) {
    if (!hot || !Number.isInteger(rowIndex) || !prop) { return; }
    var row = getSourceRowDataByVisualRow(hot, rowIndex) || {};
    if (prop === 'Ejecutado_Real'
        && (isBlank(row.Sub_Contratista) || isBlank(row.Responsable_AIA))) {
      var assigneeMessage = 'Falta Sub-Contratista o Responsable AIA para registrar avance';
      setMobileSaveState(rowIndex, prop, 'error', assigneeMessage);
      showFeedback('error', assigneeMessage);
      renderMobileCards(getFilteredRows());
      return;
    }
    var normalized = normalizeCellValue(prop, value);
    if (!normalized.valid) {
      setMobileSaveState(rowIndex, prop, 'error', normalized.error);
      renderMobileCards(getFilteredRows());
      return;
    }
    if (prop === 'Ejecutado_Real' && requiresCnc(row, normalized.value)) {
      queueCncSave(rowIndex, row, prop, row[prop], normalized.value, { mobile: true });
      return;
    }
    if (toNumber(row[prop], null) === toNumber(normalized.value, null)) {
      setMobileSaveState(rowIndex, prop, 'success', 'Sin cambios');
      renderMobileCards(getFilteredRows());
      return;
    }
    setMobileSaveState(rowIndex, prop, 'saving', 'Guardando...');
    renderMobileCards(getFilteredRows());
    var overrides = {};
    overrides[prop] = normalized.value;
    saveRow(rowIndex, prop, row[prop], overrides, { mobile: true });
  }

  function bindMobileCardEvents() {
    $('#mobile-card-view').off('click.psMobileSave').on('click.psMobileSave', '[data-mobile-save-prop]', function () {
      var rowIndex = Number(this.getAttribute('data-mobile-save-row'));
      var prop = this.getAttribute('data-mobile-save-prop');
      var input = this.closest('.ps-mobile-metric').querySelector('input[data-mobile-prop]');
      commitMobileCardValue(rowIndex, prop, input ? input.value : '');
    });
    $('#mobile-card-view').off('keydown.psMobileCards').on('keydown.psMobileCards', 'input[data-mobile-prop]', function (event) {
      if (event.key !== 'Enter') { return; }
      event.preventDefault();
      var button = this.closest('.ps-mobile-metric').querySelector('[data-mobile-save-prop]');
      if (button) { button.click(); }
    });
    $('#mobile-card-view').off('click.psMobileState').on('click.psMobileState', '[data-mobile-ops-row]', function () {
      var rowIndex = Number(this.getAttribute('data-mobile-ops-row'));
      var row = hot && Number.isInteger(rowIndex) ? getSourceRowDataByVisualRow(hot, rowIndex) : null;
      if (row) {
        showOperationalStateDrawer(getStateView(row));
      }
    });
  }

  function buildCloseSummary() {
    var summary = {
      readyCount: 0,
      blockingCount: 0,
      warningLowCount: 0,
      warningRestrictedCount: 0,
      executionRestrictionsCount: 0,
      blockingCriticalCount: 0,
      blockingItems: [],
      warningLowItems: [],
      warningRestrictedItems: [],
      executionRestrictionsItems: [],
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

      var stateKey = getStateKey(row);
      if (stateKey === 'prog-ejecucion-con-restricciones') {
        summary.executionRestrictionsCount += 1;
        if (summary.executionRestrictionsItems.length < 8) {
          var execReadinessActions = getReadinessActions(row);
          summary.executionRestrictionsItems.push({
            actividad: escapeHtml(getPlainActivityLabel(row.Actividad)),
            detalle: execReadinessActions.length
              ? 'Restricciones pendientes: ' + escapeHtml(execReadinessActions.join('; '))
              : 'Actividad con avance y condiciones pendientes.',
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
      "<div class='ps-close-summary-kpi is-warning'><strong>" + summary.executionRestrictionsCount + "</strong><small>Ejecución con restricciones</small></div>" +
      "</div>";

    html += renderSummaryList('Detalle por completar', summary.blockingItems, 'is-blocking');
    html += renderSummaryList('Detalle compromiso menor a sugerido', summary.warningLowItems, 'is-warning');
    html += renderSummaryList('Detalle con condiciones pendientes', summary.warningRestrictedItems, 'is-warning');
    html += renderSummaryList('Detalle ejecución con restricciones', summary.executionRestrictionsItems, 'is-warning');
    html += "<p class='ps-close-summary-note'>Al confirmar, no se podrán modificar compromisos ni eliminar actividades.</p></div>";

    $('#cerrar_compromisos_semana').html(html);
    $('#btn_confirmar_compromisos_semana').prop('disabled', hasBlocking).toggleClass('disabled', hasBlocking);
  }

  // El borde rojo y el aviso global de #formulario_nuevo ya existian, pero nada ataba
  // el aviso al campo concreto: `aria-invalid` y `aria-describedby` venian a null, asi
  // que quien recorra el formulario campo por campo con lector de pantalla oye el aviso
  // una vez y despues no se entera de cual esta mal al posarse en el (WCAG 3.3.1 y
  // 4.1.2). Cada campo apunta a su propia ancla sr-only, siguiendo la receta del DS
  // (`aria-invalid` + `aria-describedby` a un mensaje por campo).
  //
  // #idNuevo NO esta en la lista a proposito: es type="hidden", el UA lo trata como
  // display:none y no lo expone en el arbol de accesibilidad, asi que marcarlo seria
  // ruido inerte. El que lleva la etiqueta "Id *", el borde rojo y la exposicion al
  // lector es #idNuevoDisplay (readonly y tabindex="-1", pero alcanzable en modo
  // exploracion), y por eso es el unico del par que se marca.
  var NEW_ACTIVITY_ERROR_ANCHORS = {
    '#idNuevoDisplay': 'ps-error-idNuevo',
    '#Actividad': 'ps-error-Actividad',
    '#Sub_Contratista': 'ps-error-Sub_Contratista',
    '#Responsable_AIA': 'ps-error-Responsable_AIA',
    '#Compromiso': 'ps-error-Compromiso',
  };

  function setNewActivityErrorText(errorId, message) {
    var anchor = document.getElementById(errorId);
    if (anchor) { anchor.textContent = message; }
  }

  function flagNewActivityField(selector, message) {
    var errorId = NEW_ACTIVITY_ERROR_ANCHORS[selector];
    $(selector).addClass('ps-field-error').attr('aria-invalid', 'true').attr('aria-describedby', errorId);
    setNewActivityErrorText(errorId, message);
  }

  // Poner la marca sin retirarla dejaria el campo anunciado como invalido para siempre,
  // que es peor que no marcarlo. Por eso limpiar es un solo camino, y corre tanto al
  // revalidar como al normalizar el formulario (abrir el modal y guardar con exito).
  function clearNewActivityFieldErrors() {
    var selectors = Object.keys(NEW_ACTIVITY_ERROR_ANCHORS);
    var i;
    for (i = 0; i < selectors.length; i += 1) {
      $(selectors[i]).removeClass('ps-field-error').removeAttr('aria-invalid').removeAttr('aria-describedby');
      setNewActivityErrorText(NEW_ACTIVITY_ERROR_ANCHORS[selectors[i]], '');
    }
  }

  function normalizeNewActivityForm() {
    clearNewActivityFieldErrors();
    $('#idNuevo').val('');
    $('#idNuevoDisplay').val('');
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

    // Field-level validation with visual highlighting
    var missing = [];

    // Clear previous error highlights (clase, aria-invalid y el mensaje por campo)
    clearNewActivityFieldErrors();

    if (!idNuevo) { missing.push('Id'); flagNewActivityField('#idNuevoDisplay', 'Falta el Id: selecciona una actividad de la Bandeja de No Autoprogramadas.'); }
    if (!actividad) { missing.push('Actividad'); flagNewActivityField('#Actividad', 'Campo obligatorio: falta completarlo.'); }
    if (!sub) { missing.push('Sub-Contratista'); flagNewActivityField('#Sub_Contratista', 'Campo obligatorio: falta elegir una opción.'); }
    if (!resp) { missing.push('Profesional AIA'); flagNewActivityField('#Responsable_AIA', 'Campo obligatorio: falta elegir una opción.'); }

    if (missing.length > 0) {
      showFeedback('error', 'Complete los campos: ' + missing.join(', '));
      return;
    }

    if (compromiso === null) {
      flagNewActivityField('#Compromiso', 'Cantidad inválida: escribe un número mayor que 0.');
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
        _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
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
          _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
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
      data: { categoria: categoria, area: window.__PROJECT_AREA__ || 'Construccion' },
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
        _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
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

    $('#btn_autoprogramar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verificando...');

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
        $('#btn_autoprogramar').prop('disabled', false).html('<i class="fas fa-magic"></i> Autoprogramar Actividades');
      } else {
        ejecutarAutoprogramar(db, semana);
      }
    }).fail(function () {
      ejecutarAutoprogramar(db, semana);
    });
  }

  function ejecutarAutoprogramar(db, semana) {
    $('#btn_autoprogramar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ejecutando...');

    $.ajax({
      method: 'POST',
      url: '/api/semanal/save?db=' + encodeURIComponent(db),
      dataType: 'json',
      data: { opcion: 'autoprogramar', _csrf_token: $('meta[name="csrf-token"]').attr('content') || '', semana: semana },
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
      $('#btn_autoprogramar').prop('disabled', false).html('<i class="fas fa-magic"></i> Autoprogramar Actividades');
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

    var html = '<div class="modal fade aia-modal ps-autoprogram-modal" id="modalDatosFaltantes" tabindex="-1" role="dialog">'
      + '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'
      + '<div class="modal-header bg-warning ps-autoprogram-modal-header is-warning"><h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Actividades con Datos Incompletos</h5>'
      + '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>'
      + '<div class="modal-body ps-autoprogram-modal-body"><p>Las siguientes <strong>' + faltantes.length + '</strong> actividades candidatas tienen datos faltantes:</p>'
      + '<div class="table-responsive ps-autoprogram-table-wrap"><table class="table table-sm table-bordered ps-autoprogram-table"><thead><tr><th>Id</th><th>Actividad</th><th>Campos Faltantes</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div>'
      + '<p class="text-muted mt-2 ps-autoprogram-note"><small>Estas actividades se autoprogramarán de todas formas, pero se recomienda completar los datos desde la Programación Intermedia.</small></p></div>'
      + '<div class="modal-footer ps-autoprogram-modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>'
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
      var hardText = alertas[i].RestriccionesPendientes || '';
      var softText = alertas[i].RestriccionesBlandas || '';
      var conditions = '<div class="text-danger font-weight-bold ps-autoprogram-hard-condition">' + escapeHtml(hardText) + '</div>';
      if (softText) {
        conditions += '<div class="mt-1 text-warning ps-autoprogram-soft-condition"><span class="aia-chip aia-chip--warning mr-1">Blandas</span>'
          + escapeHtml(softText)
          + '<small class="d-block text-muted">Pdto. Constructivo y Modelo BIM no bloquean autoprogramación.</small></div>';
      }
      rows += '<tr><td>' + escapeHtml(alertas[i].Id || '') + '</td>';
      rows += '<td>' + escapeHtml(alertas[i].Actividad || '') + '</td>';
      rows += '<td>' + conditions + '</td></tr>';
    }

    var html = '<div class="modal fade aia-modal ps-autoprogram-modal" id="modalRestriccionesFaltantes" tabindex="-1" role="dialog">'
      + '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'
      + '<div class="modal-header bg-danger text-white ps-autoprogram-modal-header is-danger"><h5 class="modal-title"><i class="fas fa-clipboard-list"></i> Actividades pendientes por habilitantes</h5>'
      + '<button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>'
      + '<div class="modal-body ps-autoprogram-modal-body"><p>Las siguientes <strong>' + alertas.length + '</strong> actividades se omitieron porque tienen condiciones <strong>habilitantes</strong> pendientes para comprometer:</p>'
      + '<p class="text-muted ps-autoprogram-note"><small><span class="aia-chip aia-chip--warning">Blandas</span> Pdto. Constructivo y Modelo BIM son seguimiento operativo; aparecen en ámbar y no bloquean la autoprogramación.</small></p>'
      + '<div class="table-responsive ps-autoprogram-table-wrap"><table class="table table-sm table-bordered ps-autoprogram-table"><thead><tr><th>Id</th><th>Actividad</th><th>Condiciones</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div>'
      + '<p class="text-muted mt-2 ps-autoprogram-note"><small>Cierre las acciones de habilitación duras desde la Programación Intermedia para que puedan ser autoprogramadas o agregadas manualmente.</small></p></div>'
      + '<div class="modal-footer ps-autoprogram-modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>'
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
        _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
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
    });
  }

  // Render the "Motivo" cell for the Bandeja de No Autoprogramadas.
  // Splits a comma-separated list of pending restrictions into individual chips.
  // For activities without restriction blocks, shows a neutral "Lista para..." chip.
  function renderMotivoHtml(motivo, estado, semanasInicio) {
    var text = (motivo || estado || '').toString().trim();
    if (!text) {
      return '<span class="ps-motivo-chip is-empty" title="Sin motivo registrado">No autoprogramada</span>';
    }
    var isClean = (text === 'Lista para autoprogramar');
    var parts = isClean ? [text] : text.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    var chipClass = isClean ? ' ps-motivo-chip--clean' : '';
    var inner = parts.map(function (p) {
      return '<span class="ps-motivo-restriction">' + escapeHtml(p) + '</span>';
    }).join(' ');
    var tooltipParts = ['Motivo: ' + text];
    if (estado) { tooltipParts.push('Estado: ' + estado); }
    if (semanasInicio !== null && semanasInicio !== undefined && semanasInicio !== '') {
      tooltipParts.push('Inicia en: ' + semanasInicio + ' semana(s)');
    }
    return '<span class="ps-motivo-chip' + chipClass + '" title="' + escapeHtml(tooltipParts.join(' \u2022 ')) + '">' + inner + '</span>';
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
        $tbody.html('<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-inbox fa-2x d-block mb-2 ps-empty-icon"></i>No hay actividades sin autoprogramar en la ventana de PI (6 semanas).<br><small>Puedes agregar una manualmente con el formulario.</small></td></tr>');
        // Collapse bandeja panel when empty to give form full space
        $('#formulario_nuevo .ps-nueva-actividad-col--bandeja').addClass('ps-bandeja-empty');
        return;
      }
      // Restore bandeja panel when data is present
      $('#formulario_nuevo .ps-nueva-actividad-col--bandeja').removeClass('ps-bandeja-empty');

      var html = '';
      data.forEach(function (row) {
        var motivoHtml = renderMotivoHtml(row.Motivo, row.Estado, row.Semanas_Inicio);
        html += '<tr class="ps-row-excepcion" data-id="' + escapeHtml(row.Id) + '" ' +
                'data-actividad="' + escapeHtml(row.Actividad) + '" ' +
                'data-sub="' + escapeHtml(row.Sub_Contratista || '') + '" ' +
                'data-resp="' + escapeHtml(row.Responsable_AIA || '') + '" ' +
                'data-unidad="' + escapeHtml(row.Unidad || '%') + '">';
        html += '<td class="ps-excepcion-id">' + escapeHtml(row.Id) + '</td>';
        html += '<td class="ps-excepcion-actividad">' + escapeHtml(row.Actividad) + '</td>';
        html += '<td>' + motivoHtml + '</td>';
        html += '<td class="text-right"><button type="button" class="btn btn-sm btn-outline-secondary btn_usar_excepcion"><i class="fas fa-arrow-right"></i> Usar</button></td>';
        html += '</tr>';
      });
      $tbody.html(html);
    }).fail(function () {
      $tbody.html('<tr><td colspan="4" class="text-center text-danger">Error al cargar la bandeja.</td></tr>');
    });
  }

  function useExceptionActivity(item) {
    if (!item) return;

    // Strip any legacy HTML markup from source data
    item.Actividad = (item.Actividad || '').replace(/<[^>]+>/g, '').trim();
    item.Sub_Contratista = (item.Sub_Contratista || '').replace(/<[^>]+>/g, '').trim();
    item.Responsable_AIA = (item.Responsable_AIA || '').replace(/<[^>]+>/g, '').trim();

    // El Id se toma de la Bandeja (no de un <select>): setear hidden input + display readonly
    var $idNuevo = $('#idNuevo');
    var $idNuevoDisplay = $('#idNuevoDisplay');
    if (item.Id && $idNuevo.length) {
      var idStr = String(item.Id);
      $idNuevo.val(idStr);
      var labelActividad = (item.Actividad || '').toString();
      $idNuevoDisplay.val('(' + idStr + ') - ' + labelActividad);
    }

    $('#Actividad').val(String(item.Actividad || ''));
    $('#Sub_Contratista').val(String(item.Sub_Contratista || '')).change();
    $('#Responsable_AIA').val(String(item.Responsable_AIA || '')).change();
    $('#Unidad').val(String(item.Unidad || '%'));

    // Feedback visual de selección
    $('#tbody_excepciones_no_autoprogramadas tr').removeClass('ps-row-selected');
    var $selectedRow = $('#tbody_excepciones_no_autoprogramadas tr[data-id="' + escapeHtml(item.Id) + '"]');
    $selectedRow.addClass('ps-row-selected');

    showFeedback('info', 'Actividad cargada en el formulario');

    // Auto-scroll to Guardar button and highlight it briefly
    var $guardar = $('#btn_guardar_nueva_actividad');
    if ($guardar.length) {
      $guardar.addClass('ps-btn-pulse');
      setTimeout(function () { $guardar.removeClass('ps-btn-pulse'); }, 1400);
      // Scroll the modal body so Guardar is in view
      var modalBody = document.querySelector('#formulario_nuevo .modal-body');
      if (modalBody) {
        modalBody.scrollTo({ top: modalBody.scrollHeight, behavior: 'smooth' });
      }
    }
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
      var $modal = $('#formulario_nuevo');
      $modal.attr('aria-modal', 'true');
      $modal.modal('show');
      cargarBandejaNoAutoprogramadas();

      // Focus trap: keep focus within the modal
      setTimeout(function () {
        var focusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
        if (focusable.length > 0) { focusable.first().focus(); }
      }, 300);
    });

    // Close modal on Escape and restore aria-modal
    $('#formulario_nuevo').on('hidden.bs.modal', function () {
      $(this).attr('aria-modal', 'false');
      // Return focus to trigger button
      $('#btn_agregar_actividad').focus();
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
    // Strip any legacy HTML markup from source data
    item.Actividad = (item.Actividad || '').replace(/<[^>]+>/g, '').trim();
    item.Sub_Contratista = (item.Sub_Contratista || '').replace(/<[^>]+>/g, '').trim();
    item.Responsable_AIA = (item.Responsable_AIA || '').replace(/<[^>]+>/g, '').trim();
    useExceptionActivity(item);
  }
    });

    $('#btn_guardar_nueva_actividad').off('click.psSaveNew').on('click.psSaveNew', submitNewActivity);

    $('#btn_cerrar_compromisos_semana').off('click.psClose').on('click.psClose', function () {
      renderCloseSummary();
      $('#modal_cerrar_compromisos').modal('show');
    });

    $('#btn_reabrir_semana').off('click.psReopen').on('click.psReopen', function () {
      $('#modal_reabrir_semana').modal('show');
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
    var syncDropdownAria = function () {
      $('.ps-dropdown-nav').each(function () {
        var open = $(this).hasClass('is-open');
        $(this).find('.btn-dropdown-trigger').attr('aria-expanded', open ? 'true' : 'false');
      });
    };

    $(document).off('click.psDropdown').on('click.psDropdown', '.btn-dropdown-trigger', function(e) {
      e.stopPropagation();
      var $nav = $(this).closest('.ps-dropdown-nav');
      $('.ps-dropdown-nav').not($nav).removeClass('is-open');
      $nav.toggleClass('is-open');
      syncDropdownAria();
    });

    $(document).off('click.psDropdownClose').on('click.psDropdownClose', function() {
      $('.ps-dropdown-nav').removeClass('is-open');
      syncDropdownAria();
    });

    $(document).off('keydown.psDropdownEsc').on('keydown.psDropdownEsc', function (e) {
      if (e.key !== 'Escape') { return; }
      var $open = $('.ps-dropdown-nav.is-open');
      if (!$open.length) { return; }
      $open.removeClass('is-open');
      syncDropdownAria();
      $open.find('.btn-dropdown-trigger').trigger('focus');
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
      });

    $(document)
      .off('show.bs.modal.psLegend', '#modal_leyenda_colores_ps')
      .on('show.bs.modal.psLegend', '#modal_leyenda_colores_ps', renderLegendModal);

    $('#btn_tnp').off('click.psTnp').on('click.psTnp', function () {
      if (!canManageToolbarActions()) {
        showFeedback('error', 'No tiene permisos para registrar TNP.');
        return;
      }
      if (getSemanalConfirmada() !== 1) {
        showFeedback('error', 'Solo puede registrar TNP en fase de Calificación.');
        return;
      }

      var consecutivo = '';
      var idActividad = '';
      var ejecutadoReal = '';

      if (hot) {
        var selected = hot.getSelected();
        if (selected) {
          var row = selected[0];
          var rowData = hot.getDataItem(row);
          if (rowData) {
            consecutivo = rowData.Consecutivo || '';
            idActividad = rowData.Id || '';
            ejecutadoReal = rowData.Ejecutado_Real || '';
          }
        }
      }

      $('#tnp_consecutivo').val(consecutivo);
      $('#tnp_id_actividad').val(idActividad);
      $('#tnp_ejecutado_real').val(ejecutadoReal);
      $('#tnp_categoria_cp').val('');
      $('#tnp_cp').val('');
      $('#tnp_observaciones_cp').val('');
      $('#tnp_actividad_info').hide();

      // Load activities from API
      var $select = $('#tnp_actividad_select');
      $select.find('option:not(:first)').remove();

      $.ajax({
        url: '/api/semanal/tnp-actividades',
        method: 'GET',
        data: { db: getDb(), semana: getSemana() },
        success: function (response) {
          var actividades = response.data || response || [];
          window.tnpActividadesData = actividades;

          $.each(actividades, function (i, act) {
            var text = act.Id + ' - ' + (act.Actividad || 'Actividad #' + act.Id);
            if (act.Subcontratista || act.subcontratista) {
              text += ' - ' + (act.Subcontratista || act.subcontratista);
            }
            $select.append($('<option>', { value: act.Id, text: text }));
          });

          function formatTnpOption(option) {
            if (!option.id) return option.text;
            var actividades = window.tnpActividadesData || [];
            var act = null;
            for (var i = 0; i < actividades.length; i++) {
              if (String(actividades[i].Id) === String(option.id)) {
                act = actividades[i];
                break;
              }
            }
            if (!act) return option.text;
            var $el = $('<div class="tnp-option">');
            $el.append('<div class="tnp-option-title">' + escapeHtml(act.Id) + ' - ' + escapeHtml(act.Actividad || 'Actividad #' + act.Id) + '</div>');
            var sub = '';
            if (act.Sub_Contratista) sub += '<span>Sub: ' + escapeHtml(act.Sub_Contratista) + '</span>';
            if (act.Responsable_AIA) { if (sub) sub += ' &nbsp;|&nbsp; '; sub += '<span>Resp: ' + escapeHtml(act.Responsable_AIA) + '</span>'; }
            if (sub) $el.append('<div class="tnp-option-meta">' + sub + '</div>');
            if (act.previamente_programada) {
              $el.append('<div class="tnp-option-flag-wrap"><span class="tnp-option-flag">Previamente eliminada</span></div>');
            }
            return $el;
          }

          // Initialize Select2
          if ($select.data('select2')) {
            $select.select2('destroy');
          }
          $select.select2({
            width: '100%',
            language: 'es',
            allowClear: true,
            placeholder: $select.find('option:first').text(),
            templateResult: formatTnpOption,
            templateSelection: function (option) {
              return option.text || option.id;
            },
            escapeMarkup: function (m) { return m; }
          });

          // Preselect if a row was selected
          if (idActividad) {
            $select.val(idActividad).trigger('change');
          }
        },
        error: function () {
          showFeedback('error', 'Error al cargar actividades');
        }
      });

      $('#modal_tnp').modal('show');
    });
  }

  function bindResize() {
    $(window)
      .off('resize.psHot orientationchange.psHot aia:viewport-scale-change.psHot')
      .on('resize.psHot orientationchange.psHot aia:viewport-scale-change.psHot', function () {
        scheduleLayoutRefresh(80, true);
        syncFixedContextSpacer();
      });

    // La toolbar cambia de alto en runtime (leyenda, #mensajeActualizacion,
    // botones sujetos a permisos/estado de semana) y con ello el `top` del
    // contenedor. Se delega en scheduleLayoutRefresh porque syncContainerHeight()
    // es el único que resuelve la altura, y updateSettings({ height }) debe
    // reflejarla en la grilla.
    var header = document.querySelector('.header-actions');
    if (header && window.ResizeObserver) {
      var ro = new ResizeObserver(function () {
        scheduleLayoutRefresh(80, true);
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

    $('#hot_cat_cnc').off('change.psCnc').on('change.psCnc', function(event, selectedCause) {
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
        data: { categoria: cat, area: window.__PROJECT_AREA__ || 'Construccion' },
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
        if (selectedCause) {
          $cnc.val(selectedCause);
        }
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

      if (window._pendingCncSave) {
        var pending = window._pendingCncSave;
        var currentVisualRow = -1;
        var sourceData = hot.getSourceData();
        for (var r = 0; r < sourceData.length; r++) {
          if (sourceData[r].Consecutivo === pending.consecutivo) {
            currentVisualRow = hot.toVisualRow(r);
            break;
          }
        }

        if (currentVisualRow < 0) {
          showFeedback('error', 'No se pudo localizar la fila origen. Recargue la página.');
          window._pendingCncSave = null;
          closeCncModal();
          return;
        }

        if (!pending.mobile) {
          hot.setDataAtRowProp(currentVisualRow, 'Categoria_CNC', cat, 'internal-update');
          hot.setDataAtRowProp(currentVisualRow, 'CNC', cnc, 'internal-update');
          hot.setDataAtRowProp(currentVisualRow, 'Observaciones_CNC', obs, 'internal-update');
        }

        var normalized = normalizeCellValue(pending.prop, pending.newValue);
        if (!pending.mobile) {
          hot.setDataAtRowProp(currentVisualRow, pending.prop, normalized.value, 'internal-update');
        }

        // Inyectamos overrides al request AJAX para obviar el delay asíncrono del buffer visual de HOT
        var overrides = {
            Categoria_CNC: cat,
            CNC: cnc,
            Observaciones_CNC: obs
        };
        overrides[pending.prop] = normalized.value;

        saveRow(currentVisualRow, pending.prop, pending.oldValue, overrides, {
          mobile: pending.mobile
        });

        window._pendingCncSave = null;
        closeCncModal();
      }
    });

    $('#btn_cancelar_cnc_hot').off('click.psCnc').on('click.psCnc', function() {
      window._pendingCncSave = null;
    });
  }

  function bindTnpModal() {
    $('#btn_guardar_tnp').off('click.psTnp').on('click.psTnp', function () {
      var categoria = String($('#tnp_categoria_cp').val() || '').trim();
      var ejecutadoReal = parseFloat($('#tnp_ejecutado_real').val());

      var consecutivo = $('#tnp_consecutivo').val() || '';
      var idActividad = $('#tnp_id_actividad').val() || '';

      if (!consecutivo && !idActividad) {
        showFeedback('error', 'Seleccione una actividad');
        return;
      }
      if (!categoria) {
        showFeedback('error', 'Seleccione una Causa de Programación');
        return;
      }
      if (isNaN(ejecutadoReal) || ejecutadoReal <= 0) {
        showFeedback('error', 'Ingrese un valor válido para Ejecutado Real');
        return;
      }

      var payload = {
        opcion: 'tnp',
        _csrf_token: $('meta[name="csrf-token"]').attr('content') || '',
        db: getDb(),
        semana: getSemana(),
        Consecutivo: consecutivo || null,
        Id: idActividad || null,
        Ejecutado_Real: ejecutadoReal,
        Categoria_CP: categoria,
        CP: $('#tnp_cp').val() || null,
        Observaciones_CP: $('#tnp_observaciones_cp').val() || null
      };

      $.ajax({
        url: '/api/semanal/save',
        method: 'POST',
        data: payload,
        success: function (response) {
          if (response.respuesta === "BIEN" || response.success) {
            showFeedback('success', 'TNP registrado correctamente');
            $('#modal_tnp').modal('hide');
            loadData();
          } else {
            showFeedback('error', response.message || 'Error al guardar TNP');
          }
        },
        error: function () {
          showFeedback('error', 'Error de conexión al guardar TNP');
        }
      });
    });

    $('#tnp_actividad_select').off('change.psTnp').on('change.psTnp', function () {
      var val = $(this).val();
      var $info = $('#tnp_actividad_info');

      if (!val) {
        $('#tnp_id_actividad').val('');
        $info.hide();
        return;
      }

      var actividades = window.tnpActividadesData || [];
      var act = null;
      for (var i = 0; i < actividades.length; i++) {
        if (String(actividades[i].Id) === String(val)) {
          act = actividades[i];
          break;
        }
      }

      if (!act) {
        $('#tnp_id_actividad').val('');
        $info.hide();
        return;
      }

      $('#tnp_id_actividad').val(act.Id);
      $('#tnp_consecutivo').val(act.unique_id || act.Consecutivo_en_Programa || '');
      $('#tnp_info_subcontratista').text(act.Sub_Contratista || '-');
      $('#tnp_info_residente').text(act.Responsable_AIA || '-');
      var unidadCol = act.unidad || act.Unidad || '-';
      var frenteCol = act.Frente || act.frente || '-';
      var cuantiaCol = act.Cuantia || act.cuantia || '-';
      $('#tnp_info_frente').text(frenteCol);
      $('#tnp_info_unidad').text(unidadCol);
      $('#tnp_info_cuantia').text(cuantiaCol);
      $info.removeClass('ps-runtime-hidden').show();
    });

    $('#modal_tnp').off('hidden.bs.modal.psTnp').on('hidden.bs.modal.psTnp', function () {
      var $sel = $('#tnp_actividad_select');
      if ($sel.data('select2')) {
        $sel.val(null).trigger('change');
      } else {
        $sel.val('');
      }
      $('#tnp_actividad_info').hide();
      $('#tnp_consecutivo').val('');
      $('#tnp_id_actividad').val('');
      $('#tnp_categoria_cp').val('');
      $('#tnp_cp').val('');
      $('#tnp_ejecutado_real').val('');
      $('#tnp_observaciones_cp').val('');
      window.tnpActividadesData = null;
    });
  }

  function fetchRestrictionConfig(callback) {
    if (window.__RESTRICTION_CONFIG__) {
      if (typeof callback === 'function') { callback(); }
      return;
    }

    $.ajax({
      method: 'GET',
      url: '/api/general/restriction-config',
      dataType: 'json',
      cache: true,
      timeout: 5000,
    }).done(function (response) {
      if (response && typeof response === 'object' && Array.isArray(response.restrictions) && response.restrictions.length > 0) {
        window.__RESTRICTION_CONFIG__ = response;
      }
    }).fail(function () {
      // Fallback: construction defaults remain active (already in CONSTRUCTION_DEFAULTS)
    }).always(function () {
      if (typeof callback === 'function') { callback(); }
    });
  }

  function init() {
    if (!initialized) {
      bindToolbarActions();
      bindFilters();
      bindResize();
      bindCncModal();
      bindTnpModal();
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso_canonico').val() || getPermiso());
    }

    syncPhaseUI();
    fetchRestrictionConfig(function () {
      loadData();
    });

    if (window.ChangeMonitor && typeof window.ChangeMonitor.init === 'function') {
      window.ChangeMonitor.init();
      setTimeout(function () {
        if (window.ChangeMonitor && typeof window.ChangeMonitor.run === 'function') {
          window.ChangeMonitor.run();
        }
      }, 500);
    }
  }

  window.PSHotModule = {
    init: init,
    reload: loadData,
    getHotInstance: function () { return hot; },
  };
})(window, jQuery);
