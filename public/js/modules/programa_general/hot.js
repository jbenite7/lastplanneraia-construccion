(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var codigosActividad = [];
  var masterData = [];
  var activeFilters = [];
  var selectedStateFilter = '';
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var pendingViewportState = null;
  var currentFilteredRows = [];
  var _saveStatus = null;
  import('/js/design-system/save-status.js').then(function (mod) {
    _saveStatus = mod.crearSaveStatus({});
  });
  import('/js/design-system/state-tooltip.js').then(function (mod) {
    mod.activarStateTips(document);
  });
  import('/js/design-system/modal-escape.js').then(function (mod) {
    mod.activarEscapeEnModales();
  });

  // Performance cache: pre-computed row classes to avoid per-cell classifyPGRow during render
  var _rowClassCache = [];
  var _rowMetaCache = [];
  var _canEditGlobal = false;

  // Persistent classification cache keyed by the stable program unique_id.
  // The previous in-row `_classification` cache was unreliable because
  // Handsontable 14.x hands `getSourceDataAtRow` a proxy/copy of the row
  // object, so writes to the cache never landed on the canonical row and
  // the next render (or filter / scroll) re-classified a row whose
  // `Estado` had not yet been resolved, defaulting it to `sin-datos`.
  var _pgClassificationCache = new Map();
  var _pgCellMetaVersion = 0;

  function invalidatePGClassificationCache() {
    _pgClassificationCache.clear();
  }

  function getProgramUniqueId(data) {
    if (!data) {
      return null;
    }
    if (data.unique_id !== undefined && data.unique_id !== null && data.unique_id !== '') {
      return data.unique_id;
    }
    if (data.Consecutivo_en_Programa !== undefined && data.Consecutivo_en_Programa !== null && data.Consecutivo_en_Programa !== '') {
      return data.Consecutivo_en_Programa;
    }
    return null;
  }

  function getCachedPGClassification(data) {
    if (!data) {
      return null;
    }
    var key = getProgramUniqueId(data);
    if (key === undefined || key === null) {
      return null;
    }
    var entry = _pgClassificationCache.get(key);
    if (!entry) {
      return null;
    }
    if (
      entry.estado === data.Estado &&
      entry.titulo === data.Titulo &&
      entry.semanasInicio === data.Semanas_Inicio &&
      entry.ejecutado === data.Ejecutado &&
      entry.alertaCrisis === data.alerta_crisis
    ) {
      return entry.classification;
    }
    return null;
  }

  function setCachedPGClassification(data, classification) {
    if (!data) {
      return;
    }
    var key = getProgramUniqueId(data);
    if (key === undefined || key === null) {
      return;
    }
    _pgClassificationCache.set(key, {
      estado: data.Estado,
      titulo: data.Titulo,
      semanasInicio: data.Semanas_Inicio,
      ejecutado: data.Ejecutado,
      alertaCrisis: data.alerta_crisis,
      classification: classification,
    });
  }

  var editableProps = {
    codigo_actividad: true,
    Fecha_Inicio: true,
    Fecha_Fin: true,
    unidad: true,
    cantidad_ppto: true,
    EjecutadoDisplay: true,
  };

  var trackedStates = [
    'debe-iniciar',
    'actividad-futura',
    'en-curso',
    'atrasada',
    'terminada',
    'header',
  ];

  var unitOptions = ['', 'ml', 'm2', 'm3', 'un', 'gl', 'kg', '%', 'Niveles'];

  // ── Restriction config (dynamic from API, with construction defaults) ──
  var _DEFAULT_RESTRICTION_CONFIG = {
    hardRestrictions: [
      { key: 'D_y_E', threshold: 1.0 },
      { key: 'Materiales', threshold: 1.0 },
      { key: 'MdeO', threshold: 1.0 },
      { key: 'Equipos', threshold: 1.0 },
      { key: 'Predecesora', threshold: 0.5 },
    ],
    softAlerts: [
      { weeksBefore: 0, label: 'R0' },
      { weeksBefore: 1, label: 'R1' },
      { weeksBefore: 2, maxWeeks: 3, label: 'R2-3' },
      { weeksBefore: 4, maxWeeks: 6, label: 'R4-6' },
    ],
  };

  function getRestrictionConfig() {
    return window.__RESTRICTION_CONFIG__ || _DEFAULT_RESTRICTION_CONFIG;
  }

  function getRestrictionKeys() {
    var cfg = getRestrictionConfig();
    if (Array.isArray(cfg.hardRestrictions)) {
      return cfg.hardRestrictions.map(function (r) { return r.key; });
    }
    // Legacy fallback: array of strings
    if (Array.isArray(cfg.restrictionKeys)) {
      return cfg.restrictionKeys;
    }
    return _DEFAULT_RESTRICTION_CONFIG.hardRestrictions.map(function (r) { return r.key; });
  }

  function fetchRestrictionConfig() {
    var db = getDb();
    if (!db) {
      window.__RESTRICTION_CONFIG__ = _DEFAULT_RESTRICTION_CONFIG;
      return $.Deferred().resolve().promise();
    }

    return $.getJSON('/api/general/restriction-config', { db: db })
      .done(function (response) {
        if (response && response.success && response.data) {
          window.__RESTRICTION_CONFIG__ = normalizeApiConfig(response.data);
          if (masterData.length > 0) {
            applyFiltersAndRender();
          }
        } else {
          window.__RESTRICTION_CONFIG__ = _DEFAULT_RESTRICTION_CONFIG;
        }
      })
      .fail(function () {
        window.__RESTRICTION_CONFIG__ = _DEFAULT_RESTRICTION_CONFIG;
      });
  }

  function normalizeApiConfig(raw) {
    if (!raw || typeof raw !== 'object') {
      return _DEFAULT_RESTRICTION_CONFIG;
    }

    var restrictions = Array.isArray(raw.restrictions) ? raw.restrictions : [];
    var hardKeys = Array.isArray(raw.hardRestrictions) ? raw.hardRestrictions : [];
    var softKeys = Array.isArray(raw.softRestrictions) ? raw.softRestrictions : [];

    var lookup = {};
    for (var i = 0; i < restrictions.length; i++) {
      if (restrictions[i] && restrictions[i].key) {
        lookup[restrictions[i].key] = restrictions[i];
      }
    }

    // Normalize hardRestrictions: if API returns key strings, build [{key, threshold}] from lookup
    var hardGates;
    if (hardKeys.length > 0 && typeof hardKeys[0] === 'string') {
      hardGates = [];
      for (var j = 0; j < hardKeys.length; j++) {
        var key = hardKeys[j];
        var entry = lookup[key];
        hardGates.push({
          key: key,
          threshold: (entry && entry.threshold !== undefined) ? entry.threshold : 1.0,
        });
      }
    } else if (hardKeys.length > 0 && typeof hardKeys[0] === 'object') {
      // Already in [{key, threshold}] format (e.g. internal/default)
      hardGates = hardKeys;
    } else {
      hardGates = _DEFAULT_RESTRICTION_CONFIG.hardRestrictions;
    }

    var softEntries = [];
    if (softKeys.length > 0 && typeof softKeys[0] === 'string') {
      for (var k = 0; k < softKeys.length; k++) {
        var sEntry = lookup[softKeys[k]];
        softEntries.push({
          key: softKeys[k],
          label: (sEntry && sEntry.label) ? sEntry.label : softKeys[k],
          type: 'soft',
        });
      }
    }

    return {
      hardRestrictions: hardGates,
      softAlerts: Array.isArray(raw.softAlerts)
        ? raw.softAlerts
        : _DEFAULT_RESTRICTION_CONFIG.softAlerts,
      restrictions: restrictions,
      hardRestrictionKeys: hardKeys,
      softRestrictions: softKeys,
      softEntries: softEntries,
    };
  }

  function getRestrictionLabel(key) {
    var cfg = getRestrictionConfig();
    if (Array.isArray(cfg.restrictions)) {
      for (var i = 0; i < cfg.restrictions.length; i++) {
        if (cfg.restrictions[i].key === key && cfg.restrictions[i].label) {
          return cfg.restrictions[i].label;
        }
      }
    }
    return key;
  }

  // Task 8 (2026-08-05, C-31). La columna «Id» estaba topada en 56 px por su
  // `max` y el codigo jerarquico mas largo del programa de JMC («3.5.2.1.1»)
  // necesita 73 px medidos con la fuente de celda. A 56 px se recortaba en seco,
  // sin elipsis ni marca alguna, y «3.5.2.1.1» se leia «3.5.2.1» — que es OTRA
  // actividad de la misma tabla. 9 de 29 filas visibles caian en ese error.
  // El piso sube a 72 y el techo a 84; los 20 px extra salen de «Actividad»
  // (indice 2), que rendiza 182 px con un `max` de 300 y envuelve por palabra:
  // su palabra mas larga mide 133 px, asi que sigue sin partirse.
  var columnMinWidths = [76, 52, 112, 48, 96, 96, 38, 38, 52, 58, 58, 70, 58];
  var columnFloorWidths = [72, 44, 92, 42, 90, 90, 32, 32, 44, 50, 50, 58, 50];
  var columnMaxWidths = [84, 120, 300, 64, 104, 104, 56, 56, 82, 90, 90, 112, 78];
  var columnShrinkPriority = [2, 11, 8, 9, 10, 12, 3, 0, 6, 7, 4, 5, 1];
  var baseHiddenColumns = [1];
  var tabletHiddenColumns = [0, 3, 6, 9, 12];
  var mobileHiddenColumns = [0, 1, 3, 4, 5, 6, 7, 8, 9, 12];

  function getDb() {
    return $('#baseDatos_PHP').val() || $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_canonico').val() || '')
      .trim()
      .toUpperCase();
    return { P: 'D', U: 'V' }[permiso] || permiso;
  }

  function isDirectorRole(permiso) {
    return permiso === 'A' || permiso === 'D';
  }

  function isProgramaGeneralEditorRole(permiso) {
    return permiso === 'A' || permiso === 'D' || permiso === 'R' || permiso === 'DCV';
  }

  // Capacidad (no rol literal) que decide si la toolbar ofrece «Actualizar Ejecución»:
  // el mismo endpoint /api/general/update-batch exige lps.programa_general.editar en el
  // servidor (GeneralApiController::updateBatch), así que ocultar el botón solo evita el
  // viaje de red inútil para quien de todas formas recibiría 403.
  function canManageGeneralProgram() {
    var permiso = getPermiso();
    if (window.RbacCapabilities && typeof window.RbacCapabilities.canManageGeneralProgram === 'function') {
      return Boolean(window.RbacCapabilities.canManageGeneralProgram(permiso));
    }
    return isProgramaGeneralEditorRole(permiso);
  }

  function getMaxSemana() {
    var value = parseInt($('#Max_Semana').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function getSemanalConfirmada() {
    var value = parseInt($('#Semanal_Confirmada').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function isUserAllowedToEdit() {
    var permiso = getPermiso();
    var semana = parseInt(getSemana(), 10);
    var maxSemana = getMaxSemana();

    if (getSemanalConfirmada() === 1) {
      return false;
    }

    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && maxSemana - 2 >= semana) {
      return isDirectorRole(permiso);
    }

    return isProgramaGeneralEditorRole(permiso);
  }

  function normalizeEstadoLabel(value) {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value)
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function toNumber(value, fallback) {
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

  function isPercentLikeUnit(value) {
    var unit = String(value || '').trim();
    return unit === '' || unit === '%';
  }

  function formatPercent(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }
    return (numeric * 100).toFixed(1).replace('.', ',') + '%';
  }

  function formatPercentValue(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }

    // El valor interno es un ratio (0.0 a 1.0).
    // Para mostrarlo como porcentaje multiplicamos por 100.
    var displayPct = numeric * 100;

    if (displayPct < 0) {
      displayPct = 0;
    }

    if (displayPct > 100) {
      displayPct = 100;
    }

    return displayPct.toFixed(1).replace('.', ',') + '%';
  }

  function normalizePercentValue(value) {
    var ratio = normalizeRatio(value);
    if (ratio === null) {
      return null;
    }

    var percent = ratio * 100;
    return Math.round((percent + Number.EPSILON) * 10) / 10;
  }

  function normalizePercentInput(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return null;
    }

    if (numeric < 0) {
      numeric = 0;
    }

    // En AIA 2026 permitimos valores > 100 si el usuario está ingresando cantidades.
    // El backend es quien valida el rango final de ratio 0-1.

    return Math.round((numeric + Number.EPSILON) * 10) / 10;
  }

  function percentToRatio(value) {
    var percent = normalizePercentInput(value);
    if (percent === null) {
      return null;
    }

    var ratio = percent / 100;
    return Math.round((ratio + Number.EPSILON) * 10000) / 10000;
  }

  function normalizeRatio(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return null;
    }

    // AIA 2026: El motor trabaja en ratios (0-1).
    // No intentamos deducir si es porcentaje o ratio basándonos en el magnitud (numeric > 1),
    // ya que eso rompe cuando se usan unidades físicas.
    if (numeric < 0) {
      numeric = 0;
    }

    if (numeric > 1.0001 && numeric < 1.1) { // Pequeña tolerancia
        numeric = 1.0;
    }

    return Math.round((numeric + Number.EPSILON) * 10000) / 10000;
  }

  function getEjecutadoRatio(rowData) {
    return normalizeRatio(rowData ? rowData.Ejecutado : null);
  }

  function buildDisplayContext(rowData, overrides) {
    var contextOverrides = overrides || {};
    var hasOwn = Object.prototype.hasOwnProperty;
    var unidadValue = hasOwn.call(contextOverrides, 'unidad')
      ? contextOverrides.unidad
      : rowData.unidad;
    var cantidadPptoValue = hasOwn.call(contextOverrides, 'cantidad_ppto')
      ? contextOverrides.cantidad_ppto
      : rowData.cantidad_ppto;
    var ejecutadoDisplayValue = hasOwn.call(contextOverrides, 'EjecutadoDisplay')
      ? contextOverrides.EjecutadoDisplay
      : rowData.EjecutadoDisplay;

    return {
      unidad: String(unidadValue || '').trim(),
      cantidad_ppto: toNumber(cantidadPptoValue, 0),
      EjecutadoDisplay: toNumber(ejecutadoDisplayValue, null),
    };
  }

  function ratioFromDisplayContext(context) {
    if (!context || context.EjecutadoDisplay === null) {
      return null;
    }

    if (isPercentLikeUnit(context.unidad) || context.cantidad_ppto <= 0) {
      return context.EjecutadoDisplay / 100;
    }

    return context.EjecutadoDisplay / context.cantidad_ppto;
  }

  function displayFromRatioForContext(ratio, context) {
    if (ratio === null) {
      return null;
    }

    if (!context || isPercentLikeUnit(context.unidad) || context.cantidad_ppto <= 0) {
      return ratio * 100;
    }

    return ratio * context.cantidad_ppto;
  }

  function syncEjecutadoFields(rowData, ratio, overrides) {
    if (!rowData) {
      return;
    }

    var normalizedRatio = normalizeRatio(ratio);
    if (isPercentLikeUnit(rowData.unidad)) {
      rowData.cantidad_ppto = null;
    }
    rowData.Ejecutado = normalizedRatio;
    rowData.EjecutadoDisplay = displayFromRatioForContext(normalizedRatio, buildDisplayContext(rowData, overrides));
  }

  function normalizeNumberForPayload(value, decimals) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
      return '';
    }

    var factor = Math.pow(10, decimals);
    var rounded = Math.round((numeric + Number.EPSILON) * factor) / factor;
    return rounded.toFixed(decimals);
  }

  function isValidDateYmd(value) {
    if (!value) {
      return false;
    }
    return /^\d{4}-\d{2}-\d{2}$/.test(String(value).trim());
  }

  function sanitizeUnit(unit) {
    if (unit === null || unit === undefined) { return ''; }
    return String(unit).trim();
  }

  function formatDecimalComma(value, decimals) {
    var n = toNumber(value, null);
    if (n === null) { return ''; }
    return n.toFixed(decimals).replace('.', ',');
  }

  function formatValueWithUnit(value, unit) {
    var formatted = formatDecimalComma(value, 1);
    if (formatted === '') { return ''; }
    var u = sanitizeUnit(unit);
    return u ? (formatted + ' ' + u) : formatted;
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
    for (var i = 0; i < container.childNodes.length; i++) {
      output += walkNode(container.childNodes[i]);
    }

    return output;
  }

  function createTextNode(tagName, className, text) {
    var node = document.createElement(tagName);
    if (className) {
      node.className = className;
    }
    node.textContent = text;
    return node;
  }

  function normalizeEstadoToStateKey(estado) {
    switch (normalizeEstadoLabel(estado)) {
      case 'capitulo':
        return 'header';
      case 'terminada':
      case 'terminada antes':
      case 'ok':
        return 'terminada';
      case 'atrasada':
      case 'ya debio iniciar y restricciones pendientes':
        return 'atrasada';
      case 'debe iniciar':
      case 'debe iniciar esta semana':
        return 'debe-iniciar';
      case 'en curso':
      case 'a tiempo':
      case 'adelantada':
        return 'en-curso';
      case 'actividad futura':
      case 'en liberacion de restricciones':
        return 'actividad-futura';
      // «Fuera de Ventana» es el 39,3% de `programa_consolidado` (25.778 de
      // 65.633, medido el 2026-08-19) y NO estaba aqui: caia en el `default` y
      // la clasificaba `getFallbackStateKey` por heuristica, que con
      // Semanas_Inicio >= 7 devuelve `actividad-futura`. Medido en pantalla:
      // las dos se pintaban del mismo verde, pixel a pixel.
      //
      // `no requerida` es su nombre anterior -`ds-f1a-escala-estado.json` lo
      // declara con `"sustituye": "No Requerida"`-, asi que viaja con ella y no
      // se queda apuntando al estado equivocado.
      case 'fuera de ventana':
      case 'no requerida':
        return 'fuera-de-ventana';
      case 'sin datos':
        return 'sin-datos';
      default:
        return '';
    }
  }

  function getFallbackStateKey(data) {
    var ejecutado = getEjecutadoRatio(data);
    if (ejecutado === null) {
      ejecutado = 0;
    }
    var semanasInicio = Math.round(toNumber(data.Semanas_Inicio, 999));

    if (ejecutado >= 0.999) {
      return 'terminada';
    }

    if (semanasInicio > 900 && ejecutado <= 0) {
      return 'sin-datos';
    }

    if (semanasInicio < 0) {
      return 'atrasada';
    }

    if (semanasInicio === 0 && ejecutado <= 0) {
      return 'debe-iniciar';
    }

    if (ejecutado > 0 && ejecutado < 0.999) {
      return 'en-curso';
    }

    return 'actividad-futura';
  }

  function getRestrictionThresholds() {
    var cfg = getRestrictionConfig();
    var thresholds = {};

    var lookup = {};
    if (Array.isArray(cfg.restrictions)) {
      for (var i = 0; i < cfg.restrictions.length; i++) {
        var r = cfg.restrictions[i];
        if (r && r.key) {
          lookup[r.key] = r.threshold;
        }
      }
    }

    var hardEntries = Array.isArray(cfg.hardRestrictions) ? cfg.hardRestrictions : [];
    for (var j = 0; j < hardEntries.length; j++) {
      var entry = hardEntries[j];
      var key = typeof entry === 'string' ? entry : (entry && entry.key);
      if (!key) { continue; }
      var rawThreshold;
      if (typeof entry === 'object' && entry.threshold !== undefined) {
        rawThreshold = entry.threshold;
      } else if (lookup[key] !== undefined) {
        rawThreshold = lookup[key];
      } else {
        rawThreshold = 1.0;
      }
      // API threshold is 0-100; convert to ratio 0-1
      thresholds[key] = (rawThreshold > 1) ? rawThreshold / 100 : rawThreshold;
    }

    if (Object.keys(thresholds).length === 0) {
      var defaults = _DEFAULT_RESTRICTION_CONFIG.hardRestrictions;
      for (var k = 0; k < defaults.length; k++) {
        thresholds[defaults[k].key] = defaults[k].threshold;
      }
    }

    return thresholds;
  }

  function areHardRestrictionsMet(data) {
    var thresholds = getRestrictionThresholds();
    var keys = Object.keys(thresholds);
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      var val = normalizeRatio(data[key]);
      if (val !== null && val < thresholds[key]) {
        return false;
      }
    }
    return true;
  }

  function getRestrictionAlertKey(data) {
    if (!data || Number(data.Titulo) !== 0) {
      return '';
    }

    var ejecutado = getEjecutadoRatio(data);
    if (ejecutado === null) {
      ejecutado = 0;
    }

    if (areHardRestrictionsMet(data) || ejecutado >= 0.999) {
      return '';
    }

    var semanasInicio = Math.round(toNumber(data.Semanas_Inicio, 999));
    if (semanasInicio <= 0) {
      return 'r0';
    }

    if (semanasInicio === 1) {
      return 'r1';
    }

    if (semanasInicio >= 2 && semanasInicio <= 3) {
      return 'r2-3';
    }

    if (semanasInicio >= 4 && semanasInicio <= 6) {
      return 'r4-6';
    }

    return '';
  }

  // Guard de que esta tabla no se desvie del contrato:
  // tests/design-system/ops-state-contract.test.mjs
  var LEVEL_ATTRS = {
    neutral: { severity: 'none', urgency: 'none' },
    healthy: { severity: 'low', urgency: 'none' },
    attention: { severity: 'medium', urgency: 'soon' },
    urgent: { severity: 'high', urgency: 'now' },
  };

  // Siete estados, siete matices, sin repetir. Los valores salen de
  // docs/design-system/state-semantics.json y no se eligen aqui: el matiz es el
  // eje que desempata dentro de un mismo nivel. `actividad-futura` y `en-curso`
  // comparten nivel `healthy`, asi que sin matiz se pintan identicas -que es el
  // defecto que este chip viene a corregir-.
  //
  // Los NIVELES no se eligen aqui: salen de `docs/design-system/ds-f1a-escala-estado.json`,
  // medido sobre 50.976 actividades reales. `urgente`->urgent, `atencion`->attention,
  // `controlado`->healthy, y nivel `null`->neutral (ausencia de gravedad, que ese
  // contrato distingue de `controlado` por el eje matiz y no por un canal nuevo).
  //
  // `con-alerta-restricciones` SALE: no existe en ninguna de las 65.633 filas de
  // `programa_consolidado`. Entra `fuera-de-ventana`, que es el 39,3%.
  var statePresentation = {
    'actividad-futura': { level: 'healthy', hue: 'green' },
    'en-curso': { level: 'healthy', hue: 'blue' },
    terminada: { level: 'healthy', hue: 'neutral' },
    'fuera-de-ventana': { level: 'neutral', hue: 'teal' },
    'debe-iniciar': { level: 'attention', hue: 'orange' },
    atrasada: { level: 'urgent', hue: 'red' },
    'sin-datos': { level: 'neutral', hue: 'violet' },
  };

  // Etiquetas de respaldo para cuando el valor de Estado viene vacio pero la
  // fila si tiene clasificacion (Id/Consecutivo presentes). Sin esto el chip
  // podia mostrar "Sin datos" con un matiz distinto al neutral (el defecto
  // que este renderer existe para evitar). Mismo vocabulario que la leyenda.
  var STATE_KEY_LABELS = {
    'actividad-futura': 'Actividad Futura',
    'en-curso': 'En Curso',
    terminada: 'Terminada',
    'fuera-de-ventana': 'Fuera de Ventana',
    'debe-iniciar': 'Debe Iniciar',
    atrasada: 'Atrasada',
    'sin-datos': 'Sin Datos',
  };

  function stateChipAttrs(state) {
    var presentation = statePresentation[state];
    if (!presentation) {
      return '';
    }
    var pair = LEVEL_ATTRS[presentation.level];
    return ' data-aia-hue="' + presentation.hue + '"'
      + ' data-aia-severity="' + pair.severity + '"'
      + ' data-aia-urgency="' + pair.urgency + '"';
  }

  // El porque de cada estado, para el tooltip del chip (Felipe, 2026-08-20).
  // Mismas frases que la Guia Operativa para que pantalla y ayuda no diverjan.
  var STATE_TIPS = {
    atrasada: 'Debio iniciar y no registra el avance esperado. Atender ahora.',
    'debe-iniciar': 'Su semana de inicio llego: arranca o justifica. Revisar antes del siguiente hito.',
    'en-curso': 'Con ejecucion en marcha dentro de lo planificado.',
    'actividad-futura': 'Inicia dentro de la ventana de planificacion. Sin accion inmediata.',
    'fuera-de-ventana': 'Inicia mas alla de la ventana de 6 semanas: aun fuera del lookahead.',
    terminada: 'Actividad cerrada. Sin accion.',
    'sin-datos': 'Sin fecha de inicio ni ejecucion registrada. Requiere programacion.',
    'con-alerta-restricciones': 'Restricciones duras pendientes dentro de la ventana proxima.',
  };

  function classifyPGRow(data) {
    if (!data || (getProgramUniqueId(data) === null && data.Consecutivo === undefined && data.Id === undefined)) {
      return {
        key: 'sin-datos',
        baseKey: 'sin-datos',
        rowClass: 'pg-state-sin-datos',
        isCritical: false,
        restrictionAlertKey: '',
      };
    }

    // Prefer the persistent global cache so the classification survives
    // across proxy/copy boundaries that Handsontable introduces when
    // handing rows to the cells callback.
    var cached = getCachedPGClassification(data);
    if (cached) {
      return cached;
    }

    if (Number(data.Titulo) !== 0) {
      var headerResult = {
        key: 'header',
        baseKey: 'header',
        rowClass: 'pdc-header',
        isCritical: false,
        restrictionAlertKey: '',
      };
      setCachedPGClassification(data, headerResult);
      return headerResult;
    }

    var rutaCriticaRaw = String(data.Ruta_Critica === undefined ? '' : data.Ruta_Critica)
      .trim()
      .toLowerCase();
    var isCritical =
      rutaCriticaRaw === '1' ||
      rutaCriticaRaw === 'si' ||
      rutaCriticaRaw === 'sí' ||
      rutaCriticaRaw === 'true';
    var baseKey = normalizeEstadoToStateKey(data.Estado) || getFallbackStateKey(data);
    var stateKey = baseKey;
    // El vocabulario se DERIVA de `statePresentation` en vez de repetirse. Habia
    // dos listas de los mismos estados y una se quedo atras: `fuera-de-ventana`
    // entro al chip y no a la clase de fila, asi que el chip decia «teal» y la
    // fila seguia pintandose del verde de `actividad-futura`. Con una sola
    // fuente, anadir un estado no puede volver a arreglar la mitad.
    //
    // El respaldo `|| 'pg-state-actividad-futura'` sigue siendo lo que hace
    // ruidoso este defecto: un estado sin mapear no se ve raro, se ve como otro
    // estado real. Se conserva porque cambiarlo mueve el aspecto de filas sin
    // clasificar y eso no es de este cambio, pero queda dicho aqui.
    var rowClassMap = {};
    for (var stateName in statePresentation) {
      if (Object.prototype.hasOwnProperty.call(statePresentation, stateName)) {
        rowClassMap[stateName] = 'pg-state-' + stateName;
      }
    }

    var result = {
      key: stateKey,
      baseKey: baseKey,
      rowClass: rowClassMap[stateKey] || 'pg-state-actividad-futura',
      isCritical: isCritical,
      restrictionAlertKey: getRestrictionAlertKey(data),
    };

    setCachedPGClassification(data, result);
    return result;
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

    if (type === 'success') {
      if (_saveStatus) { _saveStatus.guardado(); }
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
        window.AIA.Notice.badge('success', message || 'Guardado');
      } else {
        var $el = $('#save-status');
        $el.removeClass('badge-badge-hidden').text(message || 'Guardado').fadeIn(120);
        saveBadgeTimer = setTimeout(function () {
          $el.fadeOut(250, function() { $(this).addClass('badge-badge-hidden'); });
        }, 1800);
      }
      return;
    }

    if (typeof toastr !== 'undefined') toastr.error(message || 'Error al guardar');
  }

  function fetchCodigosActividad() {
    return $.ajax({
      url: '/api/general/codigos',
      method: 'GET',
      dataType: 'json',
      cache: false,
    })
      .done(function (response) {
        if (response && response.success && Array.isArray(response.data)) {
          codigosActividad = response.data
            .map(function (item) {
              return String(item.codigo_actividad || '').trim();
            })
            .filter(function (value) {
              return value !== '';
            });
        }
      })
      .fail(function () {
        codigosActividad = [];
      });
  }

  function renderLegendModal() {
    $('#modal_leyenda_colores_Label').text(
      'Guia Operativa - Programa General'
    );

    var _cfg = getRestrictionConfig();
    var _hardKeys = getRestrictionKeys();
    var _hardLabels = [];
    for (var _ri = 0; _ri < _hardKeys.length; _ri++) {
      _hardLabels.push(getRestrictionLabel(_hardKeys[_ri]));
    }
    var _restrictionInfoHtml = _hardLabels.length > 0
      ? "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>Restricciones Obligatorias (" + _hardLabels.length + ")</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<div class='pg-legend-quick-state'><small>" + escapeHtml(_hardLabels.join(' | ')) + "</small></div>" +
        '</div>' +
        '</section>'
      : '';

    $('#modal_leyenda_colores_body').html(
      "<div class='pg-legend-quick'>" +
        "<div class='pg-legend-quick-header'>" +
        "<p class='pg-legend-quick-intro'><strong>Lectura rapida:</strong> atiende primero P1, luego P2 y deja P3 en monitoreo.</p>" +
        "<div class='pg-legend-quick-scale'>" +
        "<span class='pg-legend-quick-badge is-p1'>P1 Hoy</span>" +
        "<span class='pg-legend-quick-badge is-p2'>P2 Esta semana</span>" +
        "<span class='pg-legend-quick-badge is-p3'>P3 Seguimiento</span>" +
        '</div>' +
        '</div>' +
        "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>P1 - Resolver hoy</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-atrasada'></span>" +
        "<div class='pg-legend-quick-state'><strong>Atrasada</strong><small>Por debajo de la curva teorica al inicio de semana. <em>Si tiene ruta critica se marca con icono.</em></small></div>" +
        "<div class='pg-legend-quick-action'>Reprogramar frente y cerrar causa del atraso.</div>" +
        "<span class='pg-legend-quick-priority is-p1'>P1</span>" +
        '</div>' +
        '</section>' +
        "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>P2 - Gestion semanal</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-debe-iniciar'></span>" +
        "<div class='pg-legend-quick-state'><strong>Debe Iniciar</strong><small>Inicio dentro de la semana actual y sin avance.</small></div>" +
        "<div class='pg-legend-quick-action'>Asegurar recursos, cuadrilla y frente liberado.</div>" +
        "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-en-curso'></span>" +
        "<div class='pg-legend-quick-state'><strong>En Curso</strong><small>Ejecucion alineada o por encima de la curva teorica semanal.</small></div>" +
        "<div class='pg-legend-quick-action'>Sostener ritmo diario y control de productividad.</div>" +
        "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
        '</div>' +
        '</section>' +
        "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>P3 - Seguimiento</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-actividad-futura'></span>" +
        "<div class='pg-legend-quick-state'><strong>Actividad Futura</strong><small>Inicia dentro del horizonte de 6 semanas o fuera de el.</small></div>" +
        "<div class='pg-legend-quick-action'>Preparar compras, mano de obra y permisos.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-sin-datos'></span>" +
        "<div class='pg-legend-quick-state'><strong>Sin Datos</strong><small>Actividad sin fecha de inicio ni ejecucion registrada. Requiere programacion.</small></div>" +
        "<div class='pg-legend-quick-action'>Asignar fechas y liberar restricciones.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-terminada'></span>" +
        "<div class='pg-legend-quick-state'><strong>Terminada</strong><small>Actividad cerrada.</small></div>" +
        "<div class='pg-legend-quick-action'>Cerrar trazabilidad y liberar foco del equipo.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        '</section>' +
        _restrictionInfoHtml +
        "<section class='pg-legend-quick-alerts'>" +
        "<h6 class='pg-legend-quick-group-title'>Alertas secundarias de restricciones</h6>" +
        "<p class='pg-legend-quick-alert-intro'>R0-R1-R2/3-R4/6 no cambian el estado principal. Solo anticipan desbloqueos.</p>" +
        "<div class='pg-legend-quick-alert-grid'>" +
        "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-0'></span><strong>R0</strong><small>inicio inmediato o vencido.</small></div>" +
        "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-1'></span><strong>R1</strong><small>Debe quedar liberada en 1 semana.</small></div>" +
        "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-2-3'></span><strong>R2-3</strong><small>Riesgo medio en ventana proxima.</small></div>" +
        "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-4-6'></span><strong>R4-6</strong><small>Riesgo temprano del lookahead.</small></div>" +
        '</div>' +
        '</section>' +
        '</div>'
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
      url: '/programa-general/filtros',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).then(
      function (info) {
        var data = info && info.data ? info.data : {};
        var flags = [
          'activa_lookahead',
          'activa_no_iniciadas',
          'activa_a_tiempo',
          'activa_atrasadas',
          'activa_terminadas',
        ];

        var query = '';
        for (var i = 0; i < flags.length; i++) {
          var flag = flags[i];
          query += '&' + flag + '=' + (data[flag] ? 1 : 0);
        }

        $('#scriptBarraFiltros').val(query);
        return query;
      },
      function () {
        return '';
      }
    );
  }

  function buildListUrl(extraFlags) {
    var db = encodeURIComponent(getDb());
    var semana = encodeURIComponent(getSemana());
    return '/api/general/list?db=' + db + '&semana=' + semana + (extraFlags || '');
  }

  function requestList(extraFlags) {
    $.ajax({
      url: buildListUrl(extraFlags),
      method: 'GET',
      dataType: 'json',
      cache: false,
    })
      .done(function (response) {
        // Bust classification cache because the source data set has been
        // replaced wholesale and previous entries (keyed by
        // Consecutivo_en_Programa) might no longer correspond.
        invalidatePGClassificationCache();
        _pgCellMetaVersion++;
        masterData = response && Array.isArray(response.data) ? response.data : [];

        for (var i = 0; i < masterData.length; i++) {
          var row = masterData[i] || {};
          if (Number(row.Titulo) === 0) {
            var dbRatio = normalizeRatio(row.Ejecutado);
            var mappedUnit = String(row.unidad || '').trim();
            row.unidad = mappedUnit;
            syncEjecutadoFields(row, dbRatio);
          }
        }

        applyFiltersAndRender();
        showLoading(false);
      })
      .fail(function () {
        showLoading(false);
        showFeedback('error', 'No se pudo cargar el programa general. Recarga la página para volver a intentarlo.');
      });
  }

  function loadData() {
    if (!getDb() || !getSemana()) {
      showLoading(false);
      return;
    }

    showLoading(true);
    if (!hot) {
      updateOrInitHot([]);
    }
    var initialFlags = String($('#scriptBarraFiltros').val() || '').trim();
    requestList(initialFlags);
    fetchFilterFlags();
  }

  function normalizeCellValue(prop, value) {
    if (prop === 'EjecutadoDisplay') {
      var numeric = toNumber(value, null);
      if (numeric === null) {
        return { valid: false, value: value, error: 'Ejecutado inválido' };
      }
      return { valid: true, value: Math.round((numeric + Number.EPSILON) * 10) / 10 };
    }

    if (prop === 'cantidad_ppto') {
      var qty = toNumber(value, null);
      if (qty === null) {
        return { valid: true, value: '' };
      }
      if (qty < 0) {
        return { valid: false, value: value, error: 'Cantidad inválida' };
      }
      return { valid: true, value: Math.round((qty + Number.EPSILON) * 10) / 10 };
    }

    if (prop === 'Fecha_Inicio' || prop === 'Fecha_Fin') {
      var text = String(value || '').trim();
      if (!isValidDateYmd(text)) {
        return { valid: false, value: value, error: 'Fecha inválida (YYYY-MM-DD)' };
      }
      return { valid: true, value: text };
    }

    if (prop === 'unidad') {
      var parsedUnit = String(value || '').trim();
      return { valid: true, value: isPercentLikeUnit(parsedUnit) ? '%' : parsedUnit };
    }

    if (prop === 'codigo_actividad') {
      return { valid: true, value: String(value || '').trim() };
    }

    return { valid: true, value: value };
  }

  function buildUpdatePayload(rowData, prop, overrides) {
    var payloadRow = $.extend({}, rowData || {}, overrides || {});
    var id = getProgramUniqueId(payloadRow);
    var fechaInicio = String(payloadRow.Fecha_Inicio || '').trim();
    var fechaFin = String(payloadRow.Fecha_Fin || '').trim();
    var physicalVal = toNumber(payloadRow.EjecutadoDisplay, null);
    var unidad = String(payloadRow.unidad || '').trim();
    var cantidadPpto = toNumber(payloadRow.cantidad_ppto, null);
    var displayContext = buildDisplayContext(payloadRow);
    var canonicalRatio = normalizeRatio(payloadRow.EjecutadoRatio);
    if (canonicalRatio === null) {
      canonicalRatio = getEjecutadoRatio(payloadRow);
    }
    var ratioVal = canonicalRatio;

    if (isPercentLikeUnit(unidad)) {
      cantidadPpto = null;
      displayContext.cantidad_ppto = 0;
    }

    if (prop === 'EjecutadoDisplay' || ratioVal === null) {
      ratioVal = ratioFromDisplayContext(displayContext);
    }

    if (ratioVal !== null && prop !== 'EjecutadoDisplay') {
      physicalVal = displayFromRatioForContext(ratioVal, displayContext);
    }

    var physicalToSubmit = physicalVal;

    if (id === null || id === undefined || id === '') {
      return { valid: false, error: 'Id de fila inválido' };
    }

    if (!isValidDateYmd(fechaInicio) || !isValidDateYmd(fechaFin)) {
      return { valid: false, error: 'Fecha inválida (YYYY-MM-DD)' };
    }

    if (physicalToSubmit === null) {
      return { valid: false, error: 'Ejecutado inválido' };
    }

    if (ratioVal === null) {
      return { valid: false, error: 'Ejecutado inválido' };
    }

    if (ratioVal > 1.0001) { // Pequeño margen para precisión decimal
      var maxVal = (isPercentLikeUnit(unidad) || cantidadPpto <= 0) ? "100%" : (cantidadPpto + " " + unidad);
      return {
        valid: false,
        error: "El valor resultante (" + (ratioVal * 100).toFixed(1) + "%) excede el rango permitido (0-100%). Máximo: " + maxVal
      };
    }

    return {
      valid: true,
      data: {
        unique_id: id,
        Consecutivo_en_Programa: id,
        Id: payloadRow.Id,
        Ejecutado: physicalToSubmit.toFixed(2),
        EjecutadoRatio: ratioVal.toFixed(6),
        codigo_actividad: String(payloadRow.codigo_actividad || '').trim(),
        unidad: unidad,
        cantidad_ppto: isPercentLikeUnit(unidad) ? '' : normalizeNumberForPayload(payloadRow.cantidad_ppto, 1),
        Fecha_Inicio: fechaInicio,
        Fecha_Fin: fechaFin,
      },
    };
  }

  function buildPercentUnitPayloadOverrides(rowData, targetUnit) {
    var canonicalRatio = getEjecutadoRatio(rowData);
    var percentUnit = isPercentLikeUnit(targetUnit) ? '%' : String(targetUnit || '').trim();
    var percentDisplay = displayFromRatioForContext(canonicalRatio, {
      unidad: percentUnit,
      cantidad_ppto: 0,
    });

    return {
      unidad: percentUnit,
      cantidad_ppto: null,
      Ejecutado: canonicalRatio,
      EjecutadoRatio: canonicalRatio,
      EjecutadoDisplay: percentDisplay,
    };
  }

  function revertCell(visualRow, prop, oldValue) {
    if (!hot) {
      return;
    }

    var col = hot.propToCol(prop);
    if (col >= 0) {
      hot.setDataAtCell(visualRow, col, oldValue, 'revert');
    }
  }

  function getVisualRowDataSnapshot(instance, visualRow) {
    if (!instance || typeof instance.getDataAtRowProp !== 'function') {
      return null;
    }

    var props = [
      'unique_id', 'Consecutivo_en_Programa', 'Consecutivo', 'Id', 'Titulo', 'Estado', 'Semanas_Inicio',
      'Fecha_Inicio', 'Fecha_Fin', 'Ruta_Critica', 'Ejecutado', 'EjecutadoDisplay',
      'unidad', 'cantidad_ppto', 'codigo_actividad', 'Estado_Restricciones',
      'alerta_crisis'
    ].concat(getRestrictionKeys());
    var rowData = {};
    var hasValue = false;

    for (var i = 0; i < props.length; i++) {
      var prop = props[i];
      var value = instance.getDataAtRowProp(visualRow, prop);
      if (value !== undefined) {
        rowData[prop] = value;
        hasValue = true;
      }
    }

    return hasValue ? rowData : null;
  }

  function getSourceRowDataByVisualRow(instance, visualRow) {
    if (!instance || visualRow === null || visualRow === undefined || visualRow < 0) {
      return null;
    }
    var physicalRow = typeof instance.toPhysicalRow === 'function' ? instance.toPhysicalRow(visualRow) : visualRow;
    if (!Number.isInteger(physicalRow) || physicalRow < 0) {
      return getVisualRowDataSnapshot(instance, visualRow);
    }
    if (typeof instance.getSourceDataAtRow === 'function') {
      return instance.getSourceDataAtRow(physicalRow) || getVisualRowDataSnapshot(instance, visualRow);
    }
    var sourceData = typeof instance.getSourceData === 'function' ? instance.getSourceData() : null;
    if (Array.isArray(sourceData) && physicalRow < sourceData.length) {
      return sourceData[physicalRow] || null;
    }
    return getVisualRowDataSnapshot(instance, visualRow);
  }

  function getPGColumnProp(instance, col, prop) {
    if (prop !== undefined && prop !== null && prop !== '') {
      return prop;
    }

    return instance && typeof instance.colToProp === 'function'
      ? instance.colToProp(col)
      : prop;
  }

  function buildPGCellProperties(instance, visualRow, col, prop, rowData) {
    var rd = rowData || getSourceRowDataByVisualRow(instance, visualRow) || {};
    var resolvedProp = getPGColumnProp(instance, col, prop);
    var hdr = Number(rd.Titulo) === 1;
    var cls = classifyPGRow(rd);
    var st = cls.rowClass || 'pg-state-actividad-futura';
    var composed = 'pg-row-state ' + st;

    if (cls.restrictionAlertKey) {
      composed += ' pg-state-' + cls.restrictionAlertKey;
    }
    if (parseInt(rd.alerta_crisis, 10) === 1 && !hdr) {
      composed += ' pg-row-crisis';
    }

    var baseClass = '';
    var colSettings = instance && instance.getSettings && instance.getSettings().columns ? instance.getSettings().columns[col] : null;
    if (colSettings) {
      baseClass = colSettings.className || '';
    }

    var canEdit = Boolean(resolvedProp && editableProps[resolvedProp]) && !hdr && _canEditGlobal;
    if (canEdit && resolvedProp === 'cantidad_ppto' && isPercentLikeUnit(rd.unidad)) canEdit = false;
    if (canEdit && resolvedProp === 'EjecutadoDisplay' && cls.key === 'sin-datos') canEdit = false;
    // Ejecutado Real (EjecutadoDisplay) siempre editable, independiente de
    // _canEditGlobal. El usuario debe poder registrar avance real incluso
    // cuando la semana está confirmada o el rol no es director.
    if (resolvedProp === 'EjecutadoDisplay' && !hdr) canEdit = true;

    return {
      className: ('htMiddle ' + baseClass + ' ' + composed + ' ' + (canEdit ? 'pg-cell-editable' : 'pg-cell-readonly') + (hdr ? ' pdc-header' : '')).trim(),
      readOnly: !canEdit,
    };
  }

  function normalizeClassList(className) {
    var seen = {};
    return String(className || '')
      .split(/\s+/)
      .filter(function (item) {
        if (!item || seen[item]) {
          return false;
        }
        seen[item] = true;
        return true;
      })
      .join(' ');
  }

  function stripPGCellStateClasses(className) {
    return String(className || '')
      .replace(/\bpg-row-state\b/g, '')
      .replace(/\bpg-state-[^\s]+/g, '')
      .replace(/\bpg-cell-editable\b/g, '')
      .replace(/\bpg-cell-readonly\b/g, '')
      .replace(/\bpg-row-crisis\b/g, '')
      .replace(/\bpdc-header\b/g, '')
      .replace(/\bhtDimmed\b/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function applyPGCellDomClass(cell, cellProperties) {
    if (!cell || !cellProperties) {
      return;
    }

    var className = stripPGCellStateClasses(cell.className || '') + ' ' + (cellProperties.className || '');
    if (cellProperties.readOnly) {
      className += ' htDimmed';
    }
    cell.className = normalizeClassList(className);
  }

  // El fondo (pg-state-*) dice QUE estado es; este atributo dice CUAN grave, y
  // lo traduce la primitiva compartida severity-rail.css. Igual que Intermedia
  // y Semanal: va en el <tr> y en la PRIMERA celda rendida, nunca en todas —
  // puesto en cada celda dibuja un filete por columna y la tabla se lee como un
  // pijama (medido en Intermedia el 2026-08-19). `rail` (p. ej. 'ready') gana
  // sobre el nivel: es el marcador declarado por estado. PG lo estrena el
  // 2026-08-20 (replanteo direccion B): declaraba niveles desde el remapeo
  // 8418449a y ninguna fila los dibujaba.
  function applyPGRowSeverityAttr(element, rowSeverity) {
    if (!element) {
      return;
    }
    if (rowSeverity) {
      element.setAttribute('data-aia-severity-rail', rowSeverity);
    } else {
      element.removeAttribute('data-aia-severity-rail');
    }
  }

  function refreshVisiblePGCellMeta(instance) {
    if (!instance || typeof instance.countRows !== 'function' || typeof instance.countCols !== 'function') {
      return;
    }

    var rowCount = instance.countRows();
    var colCount = instance.countCols();
    for (var visualRow = 0; visualRow < rowCount; visualRow++) {
      var rowData = getSourceRowDataByVisualRow(instance, visualRow) || {};
      for (var col = 0; col < colCount; col++) {
        if (typeof instance.setCellMeta === 'function') {
          var cellProperties = buildPGCellProperties(instance, visualRow, col, null, rowData);
          instance.setCellMeta(visualRow, col, 'className', cellProperties.className);
          instance.setCellMeta(visualRow, col, 'readOnly', cellProperties.readOnly);
        }
      }

      if (typeof instance.getCell === 'function') {
        var clasificacion = classifyPGRow(rowData);
        var presentacion = statePresentation[clasificacion.key] || null;
        var rowSeverity = presentacion ? (presentacion.rail || presentacion.level) : null;
        var primeraCelda = null;
        for (var c = 0; c < colCount && !primeraCelda; c++) {
          primeraCelda = instance.getCell(visualRow, c, true);
        }
        if (primeraCelda) {
          applyPGRowSeverityAttr(primeraCelda, rowSeverity);
          applyPGRowSeverityAttr(primeraCelda.parentElement, rowSeverity);
        }
      }
    }
  }

  function saveRow(visualRow, prop, oldValue, source, options) {
    var db = getDb();
    var semana = getSemana();
    var rowData = getSourceRowDataByVisualRow(hot, visualRow) || {};
    var saveOptions = options || {};
    var payloadOverrides = saveOptions.payloadOverrides || null;

    if (!payloadOverrides && prop === 'EjecutadoDisplay') {
        var editedRatio = ratioFromDisplayContext(buildDisplayContext(rowData));
        hot.setDataAtRowProp(visualRow, 'Ejecutado', normalizeRatio(editedRatio), 'internal-update');
        rowData = getSourceRowDataByVisualRow(hot, visualRow) || {};
    }

    if (!payloadOverrides && (prop === 'unidad' || prop === 'cantidad_ppto')) {
        var preservedRatio = getEjecutadoRatio(rowData);
        if (preservedRatio !== null) {
          var newDisplay = displayFromRatioForContext(preservedRatio, buildDisplayContext(rowData));
          hot.setDataAtRowProp(visualRow, 'EjecutadoDisplay', newDisplay, 'internal-update');
          rowData = getSourceRowDataByVisualRow(hot, visualRow) || {};
        }
    }

    var payload = buildUpdatePayload(rowData || {}, prop, payloadOverrides);
    if (!payload.valid) {
      revertCell(visualRow, prop, oldValue);
      showFeedback('error', payload.error);
      return;
    }

    if (_saveStatus) { _saveStatus.pendiente(1); }

    $.ajax({
      method: 'POST',
      url:
        '/api/general/update?db=' +
        encodeURIComponent(db) +
        '&semana=' +
        encodeURIComponent(semana),
      dataType: 'json',
      data: payload.data,
    })
      .done(function (response) {
        if (response && response.respuesta === 'BIEN') {
          var savedViewport = captureViewportState();

          // Iniciar lote de actualizaciones para evitar múltiples re-renders sincrónicos
          hot.suspendRender();
          try {
            if (response.estado) {
              hot.setDataAtRowProp(visualRow, 'Estado', response.estado, 'internal-update');
            }
            if (response.Semanas_Inicio !== undefined && response.Semanas_Inicio !== null) {
              hot.setDataAtRowProp(visualRow, 'Semanas_Inicio', response.Semanas_Inicio, 'internal-update');
            }
            if (response.Ejecutado !== undefined && response.Ejecutado !== null) {
              var resRatio = parseFloat(response.Ejecutado);
              var updatedRowData = getSourceRowDataByVisualRow(hot, visualRow) || rowData || {};
              var mappedUnit = String((response.unidad !== undefined ? response.unidad : updatedRowData.unidad) || '').trim();
              if (response.unidad !== undefined) {
                hot.setDataAtRowProp(visualRow, 'unidad', mappedUnit, 'internal-update');
              }
              if (response.cantidad_ppto !== undefined) {
                hot.setDataAtRowProp(visualRow, 'cantidad_ppto', response.cantidad_ppto, 'internal-update');
              }

              var physicalRow = hot.toPhysicalRow(visualRow);
              if (physicalRow !== null && physicalRow >= 0) {
                if (typeof hot.getSourceDataAtRow === 'function') {
                  var rowData = hot.getSourceDataAtRow(physicalRow);
                  if (rowData) rowData.Ejecutado = normalizeRatio(resRatio);
                } else {
                  var srcData = hot.getSourceData();
                  if (srcData && srcData[physicalRow]) {
                    srcData[physicalRow].Ejecutado = normalizeRatio(resRatio);
                  }
                }
              }

              updatedRowData = getSourceRowDataByVisualRow(hot, visualRow) || updatedRowData;
              hot.setDataAtRowProp(
                visualRow,
                'EjecutadoDisplay',
                displayFromRatioForContext(normalizeRatio(resRatio), buildDisplayContext(updatedRowData)),
                'internal-update'
              );
            }

            var physicalRow = hot.toPhysicalRow(visualRow);
            var rd = getSourceRowDataByVisualRow(hot, visualRow);

            // 1. Destruir rastros de caché y pre-actualizar objeto crudo para evitar recálculos obsoletos en ciclos de HT
            if (rd) {
              if (response.estado) rd.Estado = response.estado;
              delete rd._classification;
            }
            if (typeof masterData !== 'undefined' && Array.isArray(masterData) && rd && rd.Id) {
              for (var md = 0; md < masterData.length; md++) {
                if (masterData[md] && masterData[md].Id === rd.Id) {
                   if (response.estado) masterData[md].Estado = response.estado;
                   delete masterData[md]._classification;
                   break;
                }
              }
            }

            if (physicalRow !== null && physicalRow >= 0) {
              _rowClassCache[physicalRow] = undefined;
              if (typeof _rowMetaCache !== 'undefined') _rowMetaCache[physicalRow] = undefined;
              if (typeof hot.getSourceDataAtRow === 'function') {
                var rowDataClass = hot.getSourceDataAtRow(physicalRow);
                if (rowDataClass) delete rowDataClass._classification;
              }
            }

            // 2. Notificar a Handsontable del cambio de dato formalmente
            if (response.estado) {
              hot.setDataAtRowProp(visualRow, 'Estado', response.estado, 'internal-update');
            }

            // Forzar actualización de cellMeta para toda la fila para que los colores se actualicen en vivo
            var rd = getSourceRowDataByVisualRow(hot, visualRow);
            if (rd) {
              var cls = classifyPGRow(rd);
              var hdr = Number(rd.Titulo) === 1;
              var st = cls.rowClass || 'pg-state-actividad-futura';
              var composed = 'pg-row-state ' + st;
              if (cls.restrictionAlertKey) {
                composed += ' pg-state-' + cls.restrictionAlertKey;
              }
              if (parseInt(rd.alerta_crisis, 10) === 1 && !hdr) {
                composed += ' pg-row-crisis';
              }

              var colCount = hot.countCols();
              var colsSettings = hot.getSettings().columns || [];
              for (var c = 0; c < colCount; c++) {
                var baseClass = '';
                if (colsSettings[c] && colsSettings[c].className) {
                  baseClass = colsSettings[c].className;
                }
                var p = hot.colToProp(c);
                var canEdit = Boolean(p && editableProps[p]) && !hdr && _canEditGlobal;
                if (canEdit && p === 'cantidad_ppto' && isPercentLikeUnit(rd.unidad)) canEdit = false;
                if (canEdit && p === 'EjecutadoDisplay' && cls.key === 'sin-datos') canEdit = false;
                // Ejecutado Real siempre editable, incluso cuando la semana
                // está confirmada o el rol no es director.
                if (p === 'EjecutadoDisplay' && !hdr) canEdit = true;

                var finalClass = ('htMiddle ' + baseClass + ' ' + composed + ' ' + (canEdit ? 'pg-cell-editable' : 'pg-cell-readonly') + (hdr ? ' pdc-header' : '')).trim();
                hot.setCellMeta(visualRow, c, 'className', finalClass);
              }
            }

          } finally {
            hot.resumeRender();
            hot.render();

            var fp = hot.getPlugin('filters');
            if (fp && fp.isEnabled() && fp.conditionCollection && typeof fp.conditionCollection.isEmpty === 'function' && !fp.conditionCollection.isEmpty()) {
                fp.filter();
            }
          }

          if (savedViewport) {
            setTimeout(function () { restoreViewportState(savedViewport); }, 0);
          }

          currentFilteredRows = getFilteredRows();
          updateLegendCounts(currentFilteredRows);
          renderMobileCards();
          showFeedback('success', 'Guardado');
          return;
        }

        var message = response.mensaje || 'Error al guardar';
        revertCell(visualRow, prop, oldValue);
        showFeedback('error', message);
      })
      .fail(function (jqXHR) {
        var message = 'Error de red';
        try {
          var res = JSON.parse(jqXHR.responseText);
          message = res.mensaje || res.error || message;
        } catch (e) {}
        revertCell(visualRow, prop, oldValue);
        showFeedback('error', message);
      });
  }

  function showUnitChangeConfirm(cantidadVal, onConfirm) {
    var modalId = 'modal_pg_unit_confirm';
    var $modal = $('#' + modalId);

    if (!$modal.length) {
      var html =
        '<div class="modal fade aia-modal" id="' + modalId + '" role="dialog" data-backdrop="static">' +
        '  <div class="modal-dialog modal-dialog-centered" role="document">' +
        '    <div class="modal-content">' +
        '      <div class="modal-header">' +
        '        <h4 class="modal-title"><b>Cambio de Unidad</b></h4>' +
        '        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>' +
        '      </div>' +
        '      <div class="modal-body">' +
        '        <p id="' + modalId + '_msg"></p>' +
        '      </div>' +
        '      <div class="modal-footer">' +
        '        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>' +
        '        <button type="button" class="btn btn-success" id="' + modalId + '_ok">Continuar</button>' +
        '      </div>' +
        '    </div>' +
        '  </div>' +
        '</div>';
      $('body').append(html);
      $modal = $('#' + modalId);
    }

    $('#' + modalId + '_msg').text(
      'Al cambiar la unidad a "%", se eliminará la Cantidad PPTO (' + cantidadVal + '). ¿Desea continuar?'
    );

    $('#' + modalId + '_ok').off('click').on('click', function () {
      $modal.modal('hide');
      if (typeof onConfirm === 'function') { onConfirm(); }
    });

    $modal.modal('show');
  }



  function setupRenderers() {
    if (renderersRegistered) {
      return;
    }

    Handsontable.renderers.registerRenderer('pgGenericTextRenderer', function () {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
    });

    Handsontable.renderers.registerRenderer('pgStateChipRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(instance.toPhysicalRow(row));
      var classification = rowData ? classifyPGRow(rowData) : null;
      if (!classification) {
        return;
      }
      // Misma precedencia que la insignia de estado: una fila con alerta de
      // restricciones se anuncia como tal, no por su estado de avance.
      var stateKey = classification.restrictionAlertKey
        ? 'con-alerta-restricciones'
        : classification.key;
      var attrs = stateChipAttrs(stateKey);
      if (!attrs) {
        return;
      }
      var isEmptyValue = value === null || value === undefined || value === '';
      var label;
      if (classification.restrictionAlertKey) {
        label = 'Con Alerta Restricciones';
      } else if (isEmptyValue) {
        // El texto no puede decir "Sin datos" mientras el matiz pinta otro
        // estado (p. ej. filas heredadas sin Estado pero con clasificacion
        // por fallback): la etiqueta sigue al stateKey, no al valor crudo.
        label = STATE_KEY_LABELS[stateKey] || 'Sin Datos';
      } else {
        label = String(value);
      }
      // Chip + tooltip (Felipe, 2026-08-20): PG no tiene drawer, asi que el
      // chip es focuseable (tabindex) para que el porque llegue tambien por
      // teclado, no solo por hover.
      var porQue = STATE_TIPS[stateKey] || STATE_TIPS['sin-datos'];
      if (classification.restrictionAlertKey) {
        porQue += ' Alerta ' + String(classification.restrictionAlertKey).toUpperCase() + '.';
      }
      // El porque viaja tambien en el aria-label: el tooltip visual esta
      // aria-hidden y sin esto el foco de teclado llegaba a un span mudo
      // (hallazgo P1 del audit 2026-08-20).
      td.innerHTML = '<span class="ops-state-chip" tabindex="0" role="note" aria-label="' + escapeHtml(label + '. ' + porQue) + '"' + attrs + '>'
        + '<span class="ops-chip-label">' + escapeHtml(label) + '</span>'
        + '<span class="aia-state-tip" role="tooltip" aria-hidden="true"><span class="aia-state-tip-panel">' + escapeHtml(porQue) + '</span></span>'
        + '</span>';
      td.classList.add('ops-state-td');
    });

    Handsontable.renderers.registerRenderer('pgGenericDateRenderer', function () {
      var dateRenderer = Handsontable.renderers.getRenderer('date') || Handsontable.renderers.TextRenderer;
      dateRenderer.apply(this, arguments);
    });

    Handsontable.renderers.registerRenderer('pgGenericNumericRenderer', function () {
      var numericRenderer = Handsontable.renderers.getRenderer('numeric') || Handsontable.renderers.NumericRenderer;
      numericRenderer.apply(this, arguments);
    });

    Handsontable.renderers.registerRenderer('pgGenericDropdownRenderer', function () {
      var dropdownRenderer = Handsontable.renderers.getRenderer('dropdown') || Handsontable.renderers.AutocompleteRenderer;
      dropdownRenderer.apply(this, arguments);
    });

    Handsontable.renderers.registerRenderer(
      'pgPercentRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.textContent = formatPercent(value);
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgPercentValueRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.textContent = formatPercentValue(value);
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgCriticaRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        var numeric = toNumber(value, null);
        td.textContent = numeric === 1 ? 'Sí' : numeric === 0 ? 'No' : String(value || '');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgActividadRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        var rowData = getSourceRowDataByVisualRow(instance, row) || {};
        var prefix = parseInt(rowData.alerta_crisis, 10) === 1 ? '🔥 ' : '';
        td.innerHTML = prefix + sanitizeActividadHtml(value);
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgEjecutadoTeoricoRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        var rowData = getSourceRowDataByVisualRow(instance, row) || {};
        var cantidadPpto = toNumber(rowData.cantidad_ppto, null);
        var unidad = String(rowData.unidad || '').trim();
        var ratio = toNumber(value, null);
        if (ratio === null) { td.textContent = ''; return; }
        if (isPercentLikeUnit(unidad) || cantidadPpto === null || cantidadPpto <= 0) {
          td.textContent = formatPercentValue(value);
        } else {
          var qty = Math.round((cantidadPpto * ratio + Number.EPSILON) * 10) / 10;
          td.innerHTML = "<span class='pg-cell-main'>" + formatValueWithUnit(qty, rowData.unidad) + "</span> <span class='pg-cell-meta'>(" + formatPercentValue(value) + ")</span>";
        }
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgEjecutadoRealRenderer',
      function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.NumericRenderer.apply(this, arguments);
        var rowData = getSourceRowDataByVisualRow(instance, row) || {};
        var cantidadPpto = toNumber(rowData.cantidad_ppto, null);
        var physicalVal = toNumber(value, null);
        var ratio = getEjecutadoRatio(rowData);

        if (physicalVal === null) { td.textContent = ''; return; }

        var unidad = String(rowData.unidad || '').trim();

        var physicalDisplay = formatDecimalComma(physicalVal, 1);

        if (isPercentLikeUnit(unidad) || cantidadPpto === null || cantidadPpto <= 0) {
            td.textContent = physicalDisplay + '%';
        } else {
            var percent = ratio === null ? (physicalVal / cantidadPpto * 100) : (ratio * 100);
            var percentDisplay = formatDecimalComma(percent, 1);
            td.innerHTML = "<span class='pg-cell-main'>" + formatValueWithUnit(physicalDisplay, unidad) + "</span> <span class='pg-cell-meta'>(" + percentDisplay + "%)</span>";
        }
      }
    );

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
    return Number(columnMinWidths[index]) || 48;
  }

  function getColumnMaxWidth(index) {
    var max = Number(columnMaxWidths[index]);
    if (!Number.isFinite(max) || max <= 0) {
      max = 260;
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

  function mergeHiddenColumns() {
    var seen = {};
    var merged = [];

    for (var a = 0; a < arguments.length; a++) {
      var columns = arguments[a];
      if (!Array.isArray(columns)) {
        continue;
      }

      for (var i = 0; i < columns.length; i++) {
        var value = Number(columns[i]);
        if (Number.isInteger(value) && value >= 0 && !seen[value]) {
          seen[value] = true;
          merged.push(value);
        }
      }
    }

    return merged.sort(function (left, right) {
      return left - right;
    });
  }

  function getResponsiveHiddenColumns() {
    var width = Math.max(
      window.innerWidth || 0,
      (document.documentElement && document.documentElement.clientWidth) || 0
    );

    if (width > 0 && width < 700) {
      return mergeHiddenColumns(baseHiddenColumns, mobileHiddenColumns);
    }

    if (width > 0 && width <= 991) {
      return mergeHiddenColumns(baseHiddenColumns, tabletHiddenColumns);
    }

    return baseHiddenColumns.slice();
  }

  /*
   * El corte entre grilla y tarjetas lo decide `AIAViewSwitch`, que es la misma
   * pieza que consumen Semanal e Intermedia. Hasta el 2026-08-18 este modulo
   * guardaba su propia copia -`width < 700`- mientras el CSS escondia la grilla
   * desde `max-width: 1179px` (`handsontable-module.css:324`), asi que entre 700
   * y 1179 px no enseñaba tabla, ni tarjetas, ni el aviso de vacio: la pantalla
   * quedaba en blanco (hallazgo TB-8, con la frontera medida al pixel). Un umbral
   * que existe por triplicado no coincide consigo mismo; este era el tercer sitio.
   *
   * Sin el modulo cargado se responde `false` -grilla-, que es el mismo repliegue
   * seguro que ya hacen los otros dos consumidores.
   */
  function isMobileGridViewport() {
    if (!window.AIAViewSwitch || typeof window.AIAViewSwitch.shouldRenderCards !== 'function') {
      return false;
    }

    var width = Math.max(
      window.innerWidth || 0,
      (document.documentElement && document.documentElement.clientWidth) || 0
    );

    return window.AIAViewSwitch.shouldRenderCards(width) === true;
  }

  function syncResponsiveModeClasses() {
    var container = document.getElementById('hot-container');
    if (!container) {
      return;
    }

    container.classList.remove('hot-mobile-grid');
    container.hidden = isMobileCardsLayout();

    var cards = getMobileCardsContainer();
    if (cards) {
      cards.hidden = !isMobileCardsLayout();
    }
  }

  function isMobileCardsLayout() {
    return isMobileGridViewport();
  }

  function getMobileCardsContainer() {
    return document.getElementById('mobile-card-view');
  }

  function createMobileCardField(label, value) {
    var field = document.createElement('div');
    field.className = 'pg-mobile-card__field';
    field.appendChild(createTextNode('span', 'pg-mobile-card__label', label));
    var content = document.createElement('div');
    content.className = 'pg-mobile-card__value';
    if (value instanceof Node) {
      content.appendChild(value);
    } else {
      content.textContent = value || '—';
    }
    field.appendChild(content);
    return field;
  }

  function createMobileStateBadge(rowData) {
    var classification = classifyPGRow(rowData);
    var displayState = classification && classification.restrictionAlertKey
      ? 'Con Alerta Restricciones'
      : (rowData.Estado || 'Sin datos');
    var badge = createTextNode('span', 'pg-mobile-card__state', 'Estado: ' + displayState);
    if (classification && classification.rowClass) {
      badge.classList.add(classification.rowClass);
    }
    if (classification && classification.restrictionAlertKey) {
      badge.classList.add('pg-state-' + classification.restrictionAlertKey);
    }
    return badge;
  }

  function getMobileActivityTitle(rowData) {
    return getActividadPlainText(rowData.Actividad)
      .replace(/\s*\[Cap[ií]tulo:[^\]]*\]\s*/gi, ' ')
      .replace(/\s*,\s*$/g, '')
      .replace(/\s{2,}/g, ' ')
      .trim();
  }

  function updateMobileCell(rowIndex, prop, value) {
    if (!hot || !Number.isInteger(rowIndex) || !prop) {
      return;
    }

    hot.setDataAtRowProp(rowIndex, prop, value, 'mobile-card-edit');
    window.setTimeout(renderMobileCards, 0);
  }

  function createMobileInput(rowIndex, rowData, prop, type, label) {
    var input = document.createElement('input');
    input.className = 'pg-mobile-card__input';
    input.type = type || 'text';
    input.setAttribute('aria-label', label + ': ' + (getMobileActivityTitle(rowData) || 'actividad'));
    input.value = rowData[prop] === null || rowData[prop] === undefined ? '' : String(rowData[prop]);
    input.disabled = Number(rowData.Titulo) === 1 || (prop !== 'EjecutadoDisplay' && !_canEditGlobal);
    var lastCommittedValue = input.value;
    function commitMobileInput() {
      var nextValue = input.value;
      if (nextValue === lastCommittedValue) {
        return;
      }
      lastCommittedValue = nextValue;
      updateMobileCell(rowIndex, prop, nextValue);
    }
    input.addEventListener('change', commitMobileInput);
    input.addEventListener('blur', commitMobileInput);
    return input;
  }

  function createMobileUnitSelect(rowIndex, rowData) {
    var select = document.createElement('select');
    select.className = 'pg-mobile-card__input';
    select.setAttribute('aria-label', 'Unidad: ' + (getMobileActivityTitle(rowData) || 'actividad'));
    unitOptions.forEach(function (unit) {
      var option = document.createElement('option');
      option.value = unit;
      option.textContent = unit || 'Sin unidad';
      option.selected = String(rowData.unidad || '') === String(unit);
      select.appendChild(option);
    });
    select.disabled = Number(rowData.Titulo) === 1 || !_canEditGlobal;
    select.addEventListener('change', function () {
      updateMobileCell(rowIndex, 'unidad', select.value);
    });
    return select;
  }

  function createMobileCardHeader(rowData) {
    var header = document.createElement('header');
    header.className = 'pg-mobile-card__header';
    var title = createTextNode('h3', 'pg-mobile-card__title', getMobileActivityTitle(rowData) || 'Sin actividad');
    header.appendChild(title);
    header.appendChild(createMobileStateBadge(rowData));
    return header;
  }

  function createMobileCardMeta(rowData) {
    var meta = document.createElement('div');
    meta.className = 'pg-mobile-card__meta';
    meta.appendChild(createMobileCardField('Id', rowData.Id));
    meta.appendChild(createMobileCardField('Sem. inicio', rowData.Semanas_Inicio));
    meta.appendChild(createMobileCardField('Crítica', toNumber(rowData.Ruta_Critica, 0) === 1 ? 'Sí' : 'No'));
    meta.appendChild(createMobileCardField('Lib. restr.', formatPercent(rowData.Estado_Restricciones)));
    return meta;
  }

  function createMobileCardOps(rowData, rowIndex) {
    var ops = document.createElement('div');
    ops.className = 'pg-mobile-card__ops';
    var startInput = createMobileInput(rowIndex, rowData, 'Fecha_Inicio', 'date', 'Fecha inicio');
    var endInput = createMobileInput(rowIndex, rowData, 'Fecha_Fin', 'date', 'Fecha fin');
    var qtyInput = createMobileInput(rowIndex, rowData, 'cantidad_ppto', 'number', 'Cantidad PPTO');
    var realInput = createMobileInput(rowIndex, rowData, 'EjecutadoDisplay', 'number', 'Ejecutado real');
    qtyInput.step = '0.1';
    realInput.step = '0.1';
    qtyInput.disabled = qtyInput.disabled || isPercentLikeUnit(rowData.unidad);
    ops.appendChild(createMobileCardField('Fecha inicio', startInput));
    ops.appendChild(createMobileCardField('Fecha fin', endInput));
    ops.appendChild(createMobileCardField('Unidad', createMobileUnitSelect(rowIndex, rowData)));
    ops.appendChild(createMobileCardField('Cantidad PPTO', qtyInput));
    ops.appendChild(createMobileCardField('Ejecutado real', realInput));
    return ops;
  }

  function createMobileCard(rowData, rowIndex) {
    var card = document.createElement('article');
    card.className = 'pg-mobile-card';
    var classification = classifyPGRow(rowData);
    if (classification && classification.rowClass) {
      card.classList.add(classification.rowClass);
    }
    if (classification && classification.restrictionAlertKey) {
      card.classList.add('pg-state-' + classification.restrictionAlertKey);
    }
    if (Number(rowData.Titulo) === 1) {
      card.classList.add('pg-mobile-card--chapter');
    }
    card.dataset.rowIndex = String(rowIndex);
    card.appendChild(createMobileCardHeader(rowData));
    card.appendChild(createMobileCardMeta(rowData));
    if (Number(rowData.Titulo) !== 1) {
      card.appendChild(createMobileCardOps(rowData, rowIndex));
    }
    return card;
  }

  function renderMobileCards() {
    var cards = getMobileCardsContainer();
    if (!cards) {
      return;
    }
    cards.classList.add('pg-mobile-card-list');
    cards.setAttribute('aria-label', 'Actividades del Programa General');
    var useCards = isMobileCardsLayout();
    cards.hidden = !useCards;
    if (!useCards) {
      cards.replaceChildren();
      return;
    }
    var fragment = document.createDocumentFragment();
    var rows = currentFilteredRows.length ? currentFilteredRows : [];
    var capitulosOmitidos = 0;
    var tarjetasPintadas = 0;

    rows.forEach(function (rowData, rowIndex) {
      if (Number(rowData.Titulo) === 1) {
        capitulosOmitidos += 1;
        return;
      }
      fragment.appendChild(createMobileCard(rowData, rowIndex));
      tarjetasPintadas += 1;
    });

    /*
     * Un corte sin tarjetas tiene DOS causas distintas y hasta el 2026-08-18
     * decia lo mismo en una y callaba en la otra (hallazgo TB-2): el aviso solo
     * se pintaba con `rows.length === 0`, asi que un corte con filas que fueran
     * todas de capitulo dejaba la pantalla en blanco, sin grilla, sin tarjetas y
     * sin una linea que lo explicara. Aqui se distingue el vacio real del vacio
     * por filtrado, y se dice cuantas filas se omitieron y por que.
     */
    if (!tarjetasPintadas) {
      fragment.appendChild(
        createTextNode(
          'p',
          'pg-mobile-card-list__empty',
          capitulosOmitidos
            ? 'Este corte solo trae ' + capitulosOmitidos + (capitulosOmitidos === 1 ? ' fila de capítulo' : ' filas de capítulo') + ', y los capítulos no se muestran como tarjeta. Abre el Programa General en una pantalla más ancha para ver la tabla completa.'
            : 'No hay actividades para mostrar.',
        ),
      );
    }

    cards.replaceChildren(fragment);
  }

  function getResponsiveHiddenColumnsConfig() {
    return {
      columns: getResponsiveHiddenColumns(),
      indicators: false,
      copyPasteEnabled: false,
    };
  }

  function getVisibleColumnIndexes(columnCount) {
    var hidden = getResponsiveHiddenColumns();
    var hiddenMap = {};
    var visible = [];

    for (var h = 0; h < hidden.length; h++) {
      hiddenMap[hidden[h]] = true;
    }

    for (var i = 0; i < columnCount; i++) {
      if (!hiddenMap[i]) {
        visible.push(i);
      }
    }

    return visible;
  }

  function sumWidthsByIndexes(widths, indexes) {
    if (!Array.isArray(indexes) || indexes.length === 0) {
      return sumWidths(widths);
    }

    var total = 0;
    for (var i = 0; i < indexes.length; i++) {
      total += Number(widths[indexes[i]]) || 0;
    }

    return total;
  }

  function buildResizePriority(activeIndexes, reverse) {
    var allowed = {};
    var ordered = reverse ? columnShrinkPriority.slice().reverse() : columnShrinkPriority.slice();
    var priority = [];

    for (var i = 0; i < activeIndexes.length; i++) {
      allowed[activeIndexes[i]] = true;
    }

    for (var p = 0; p < ordered.length; p++) {
      if (allowed[ordered[p]]) {
        priority.push(ordered[p]);
        delete allowed[ordered[p]];
      }
    }

    for (var a = 0; a < activeIndexes.length; a++) {
      if (allowed[activeIndexes[a]]) {
        priority.push(activeIndexes[a]);
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

    return Math.max(240, width - 24);
  }

  function getViewportHeight() {
    if (
      window.visualViewport &&
      Number.isFinite(window.visualViewport.height) &&
      window.visualViewport.height > 0
    ) {
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

  function syncRenderedTableWidth(instance) {
    var hotInstance = instance || hot;
    var container = document.getElementById('hot-container');
    if (!hotInstance || !container || typeof hotInstance.countCols !== 'function' || typeof hotInstance.getColWidth !== 'function') {
      return;
    }

    var columnCount = hotInstance.countCols();
    var visibleIndexes = getVisibleColumnIndexes(columnCount);
    var totalWidth = 0;
    for (var i = 0; i < visibleIndexes.length; i++) {
      var col = visibleIndexes[i];
      totalWidth += Number(hotInstance.getColWidth(col)) || 0;
    }

    totalWidth = Math.max(Math.ceil(totalWidth), getContainerAvailableWidth());
    if (!Number.isFinite(totalWidth) || totalWidth <= 0) {
      return;
    }

    var width = totalWidth + 'px';
    container.classList.add('hot-fixed-columns');
    // El dimensionado con !important vive en el adaptador
    // programa-general-handsontable.css (gobernado por exceptions.json);
    // aquí solo se publica la variable que consumen esas reglas.
    container.style.setProperty('--hot-table-width', width);
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

  // C-37 (2026-08-05): los gatillos `.changeType` son el indicador decorativo
  // que Handsontable inyecta en la cabecera. Los 24 nacen con `tabindex="-1"`,
  // asi que ninguno es alcanzable por teclado, pero solo 12 llevaban
  // `aria-hidden`: un lector de pantalla anunciaba esos 12 como botones sin
  // nombre e ignoraba los otros 12, identicos. Se marcan todos, sin condicion.
  // Marcar un elemento con `tabindex="-1"` no dispara `aria-hidden-focus`,
  // que solo aplica a lo que sigue siendo tabulable.
  function markDecorativeHeaderTriggers(container) {
    var triggers = container.querySelectorAll('thead th .changeType');
    Array.prototype.forEach.call(triggers, function (trigger) {
      if (trigger.getAttribute('aria-hidden') !== 'true') {
        trigger.setAttribute('aria-hidden', 'true');
      }
    });
  }

  // Ni `afterGetColHeader` ni `afterRender` sirven para C-37, y las dos vias se
  // midieron antes de descartarlas: en el hook de cabecera el boton todavia no
  // existe -lo inyecta el plugin dropdownMenu despues-, y un barrido en
  // `afterRender` si marca los 24, pero cada `render()` posterior REUSA los
  // mismos nodos de la tabla maestra y les quita el `aria-hidden` (medido:
  // 24/24 -> render() -> 12/24, con el clon superior intacto). Por eso hay que
  // vigilar tambien la mutacion de atributo, no solo la de nodos, con el mismo
  // patron que este archivo ya usa para `.htFocusCatcher`.
  function observeDecorativeHeaderTriggers(container) {
    var pending = false;
    var apply = function () {
      pending = false;
      markDecorativeHeaderTriggers(container);
    };

    markDecorativeHeaderTriggers(container);

    // Coalescer por frame: un render toca muchos nodos y no hace falta barrer
    // una vez por mutacion. La escritura es condicional, asi que reafirmar el
    // atributo no genera mutaciones nuevas y el ciclo se cierra solo.
    new MutationObserver(function (records) {
      if (pending) {
        return;
      }
      for (var i = 0; i < records.length; i++) {
        var record = records[i];
        var isTrigger =
          record.type === 'attributes' &&
          record.target.classList &&
          record.target.classList.contains('changeType');
        if (record.type === 'childList' || isTrigger) {
          pending = true;
          window.requestAnimationFrame(apply);
          return;
        }
      }
    }).observe(container, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['aria-hidden'],
    });
  }

  function syncFocusCatcherA11y(container) {
    // HOT 14 arma sus .htFocusCatcher (tabindex 0) manteniendo aria-hidden,
    // lo que dispara aria-hidden-focus en Axe. Espejo: expuesto si es
    // tabulable, oculto si no; las escrituras condicionales evitan bucles
    // del MutationObserver.
    var catchers = container.querySelectorAll('.htFocusCatcher');
    Array.prototype.forEach.call(catchers, function (catcher) {
      if (catcher.__pgA11ySynced) {
        return;
      }
      catcher.__pgA11ySynced = true;
      catcher.setAttribute('aria-label', 'Ir a la grilla del Programa General');
      var sync = function () {
        var armed = catcher.getAttribute('tabindex') === '0';
        if (armed && catcher.hasAttribute('aria-hidden')) {
          catcher.removeAttribute('aria-hidden');
        } else if (!armed && catcher.getAttribute('aria-hidden') !== 'true') {
          catcher.setAttribute('aria-hidden', 'true');
        }
      };
      sync();
      new MutationObserver(sync).observe(catcher, {
        attributes: true,
        attributeFilter: ['tabindex', 'aria-hidden'],
      });
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
    return (
      document.querySelector('#hot-container .ht_master .wtHolder') ||
      document.querySelector('#hot-container .wtHolder')
    );
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
        } catch (_err) {}
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
    } catch (_err) {}
  }

  function getBaseColumnWidths(columnCount) {
    var widths = [];
    var plugin = hot && hot.getPlugin ? hot.getPlugin('autoColumnSize') : null;

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
        width = header.length * 8 + 26;
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
        var activeIndex = activeIndexes[c];
        reducedWidths[activeIndex] = Number(lowerBounds[activeIndex]) || 20;
      }
      return reducedWidths;
    }

    var reduced = 0;
    for (var j = 0; j < activeIndexes.length; j++) {
      var activeCol = activeIndexes[j];
      var capacity = capacities[activeCol];
      if (capacity <= 0) {
        continue;
      }

      var step = Math.floor((excess * capacity) / totalCapacity);
      if (step > capacity) {
        step = capacity;
      }
      if (step > 0) {
        reducedWidths[activeCol] -= step;
        reduced += step;
      }
    }

    var remainder = excess - reduced;
    var guard = 0;
    var priority = buildResizePriority(activeIndexes, false);
    while (remainder > 0 && guard < 4000) {
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
    var growPriority = buildResizePriority(activeIndexes, true);

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

  function forceFillWidthsToTarget(widths, targetWidth, activeIndexes) {
    var filled = widths.slice();
    var total = sumWidthsByIndexes(filled, activeIndexes);
    if (total >= targetWidth) {
      return filled;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var growPriority = buildResizePriority(activeIndexes, true);

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

    if (
      !force &&
      containerWidth === lastAppliedContainerWidth &&
      currentColumnWidths.length === columnCount
    ) {
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

  function applyResponsiveColumnVisibility() {
    if (!hot) {
      return;
    }

    var settings = hot.getSettings() || {};
    var currentHidden = settings.hiddenColumns && Array.isArray(settings.hiddenColumns.columns)
      ? settings.hiddenColumns.columns
      : [];
    var config = getResponsiveHiddenColumnsConfig();

    if (arraysEqualNumbers(currentHidden, config.columns)) {
      return;
    }

    hot.updateSettings({ hiddenColumns: config });
    currentColumnWidths = [];
    lastAppliedContainerWidth = 0;
  }

  function scheduleLayoutRefresh(delay, force) {
    clearTimeout(layoutTimer);
    layoutTimer = setTimeout(
      function () {
        if (!hot) {
          return;
        }

        var viewportState = pendingViewportState;
        pendingViewportState = null;

        syncResponsiveModeClasses();
        syncContainerHeight();
        var containerHeight = getContainerAvailableHeight();
        if (containerHeight && (Boolean(force) || containerHeight !== lastAppliedContainerHeight)) {
          hot.updateSettings({ height: containerHeight });
          lastAppliedContainerHeight = containerHeight;
        }

        if (typeof hot.refreshDimensions === 'function') {
          hot.refreshDimensions();
        }

        applyResponsiveColumnVisibility();
        applyResponsiveColumnWidths(Boolean(force));
        hot.render();
        syncRenderedTableWidth(hot);
        renderMobileCards();

        if (viewportState) {
          setTimeout(function () {
            restoreViewportState(viewportState);
          }, 0);
        }
      },
      Number.isFinite(delay) ? delay : 24
    );
  }

  function getQuickFilterText() {
    return ''
      .trim()
      .toLowerCase();
  }

  function getActividadFilterText() {
    var text = String($('#buscadorActividad').val() || '').trim();
    if (!text) {
      text = '';
    }
    return text.toLowerCase();
  }

  function rowContainsQuickText(row, quickText) {
    if (!quickText) {
      return true;
    }

    var values = [
      row.codigo_actividad,
      getActividadPlainText(row.Actividad),
      row.Estado,
      row.Responsable_AIA,
      row.Sub_Contratista,
    ];

    for (var i = 0; i < values.length; i++) {
      if (
        String(values[i] || '')
          .toLowerCase()
          .indexOf(quickText) > -1
      ) {
        return true;
      }
    }

    return false;
  }

  function rowMatchesFilters(row, classification) {
    var actividadText = getActividadFilterText();
    var semanasFilter = String($('#buscadorSemanasInicio').val() || '').trim();
    var criticaFilter = String($('#buscadorCritica').val() || '').trim();
    var quickText = getQuickFilterText();

    if (actividadText) {
      var actividad = getActividadPlainText(row.Actividad).toLowerCase();
      if (actividad.indexOf(actividadText) === -1) {
        return false;
      }
    }

    if (semanasFilter) {
      var semanasInicio = Math.round(toNumber(row.Semanas_Inicio, 999));
      if (semanasFilter === '7') {
        if (semanasInicio < 7) {
          return false;
        }
      } else if (String(semanasInicio) !== semanasFilter) {
        return false;
      }
    }

    if (criticaFilter) {
      var critica = toNumber(row.Ruta_Critica, 0);
      if (criticaFilter === 'Sí' && critica !== 1) {
        return false;
      }
      if (criticaFilter === 'No' && critica === 1) {
        return false;
      }
    }

    if (!rowContainsQuickText(row, quickText)) {
      return false;
    }

    if (selectedStateFilter) {
      if (selectedStateFilter === 'con-alerta-restricciones') {
        if (!classification.restrictionAlertKey) {
          return false;
        }
      } else if (classification.key !== selectedStateFilter) {
        return false;
      }
    }

    if (activeFilters.length > 0) {
      var included = false;
      for (var i = 0; i < activeFilters.length; i++) {
        var f = activeFilters[i];
        if (f === 'con-alerta-restricciones' && classification.restrictionAlertKey) {
          included = true;
          break;
        }
        if (f === classification.key) {
          included = true;
          break;
        }
      }
      if (!included) {
        return false;
      }
    }

    return true;
  }

  function updateLegendCounts(rows) {
    var counts = {
      // `con-alerta-restricciones` NO cuenta un estado -no existe en ninguna de
      // las 65.633 filas-: cuenta el REALCE por condicion del dato, o sea las
      // 13.243 filas con restricciones duras pendientes dentro de la ventana de
      // seis semanas. Por eso sigue aqui aunque haya salido de `statePresentation`.
      'con-alerta-restricciones': 0,
      'fuera-de-ventana': 0,
      'debe-iniciar': 0,
      'actividad-futura': 0,
      'en-curso': 0,
      atrasada: 0,
      terminada: 0,
      'sin-datos': 0,
    };

    for (var i = 0; i < rows.length; i++) {
      var cls = classifyPGRow(rows[i]);
      if (counts[cls.key] !== undefined) {
        counts[cls.key] += 1;
      }
      if (cls.restrictionAlertKey) {
        counts['con-alerta-restricciones'] += 1;
      }
    }

    /* Un chip que marca cero no tiene nada que reclamar: se atenua con `is-zero`
       y recupera su color saturado en cuanto vuelve a contar algo. Misma receta
       que `setLegendCount` en programacion_intermedia/hot.js y que Programacion
       Semanal; PG se habia quedado fuera (la Task 11 de la campana solo listaba
       las hojas de PI y PS), asi que sus ceros pesaban igual que un 238. */
    Object.keys(counts).forEach(function (key) {
      $('#count-' + key)
        .text(counts[key])
        .closest('.pdc-legend-item')
        .toggleClass('is-zero', Number(counts[key]) === 0);
    });
  }

  function buildRowClassCache(data) {
    _canEditGlobal = isUserAllowedToEdit();
    _rowClassCache = new Array(data.length);
    _rowMetaCache = new Array(data.length);

    for (var i = 0; i < data.length; i++) {
      var rowData = data[i] || {};
      var classification = classifyPGRow(rowData);
      var stateClass = classification.rowClass || 'pg-state-actividad-futura';
      var composed = 'pg-row-state ' + stateClass;
      var isHeader = Number(rowData.Titulo) === 1;

      if (classification.restrictionAlertKey) {
        composed += ' pg-state-' + classification.restrictionAlertKey;
      }
      if (parseInt(rowData.alerta_crisis, 10) === 1 && !isHeader) {
        composed += ' pg-row-crisis';
      }

      _rowClassCache[i] = composed;
      _rowMetaCache[i] = {
        isHeader: isHeader,
        isPercentUnit: isPercentLikeUnit(rowData.unidad),
        sinDatos: classification.key === 'sin-datos',
      };
    }
  }

  function updateOrInitHot(data) {
    setupRenderers();
    var isEmptyInitialGrid = !hot && data.length === 0;
    if (!isEmptyInitialGrid) {
      syncContainerHeight();
    }

    if (hot) {
      var filterConditions = captureHotFilterConditions();
      pendingViewportState = captureViewportState();
      hot.loadData(data);
      restoreHotFilterConditions(filterConditions);
      hot.render();
      scheduleLayoutRefresh(0, true);
      return;
    }

    var container = document.getElementById('hot-container');
    if (!container) {
      return;
    }
    syncResponsiveModeClasses();

    hot = new Handsontable(container, {
      data: data,
      rowHeaders: false,
      colHeaders: [
        'Id',
        'Código Actividad',
        'Actividad',
        'Sem. Inicio',
        'Fecha Inicio',
        'Fecha Fin',
        'Crítica',
        'Unidad',
        'Cant. PPTO',
        'Ej. Teórico',
        'Ej. Real',
        'Estado',
        'Lib. Restr.',
      ],
      columns: [
        { data: 'Id', readOnly: true, className: 'htCenter htMiddle' },
        {
          data: 'codigo_actividad',
          type: 'dropdown',
          source: function (_q, process) {
            process(codigosActividad);
          },
          strict: false,
          allowInvalid: false,
          className: 'htCenter htMiddle',
        },
        {
          data: 'Actividad',
          readOnly: true,
          renderer: 'pgActividadRenderer',
          className: 'htLeft htMiddle force-wrap',
        },
        { data: 'Semanas_Inicio', readOnly: true, renderer: 'pgGenericTextRenderer', className: 'htCenter htMiddle' },
        {
          data: 'Fecha_Inicio',
          type: 'date',
          dateFormat: 'YYYY-MM-DD',
          correctFormat: true,
          renderer: 'pgGenericDateRenderer',
          className: 'htCenter htMiddle pg-date-cell',
        },
        {
          data: 'Fecha_Fin',
          type: 'date',
          dateFormat: 'YYYY-MM-DD',
          correctFormat: true,
          renderer: 'pgGenericDateRenderer',
          className: 'htCenter htMiddle pg-date-cell',
        },
        {
          data: 'Ruta_Critica',
          readOnly: true,
          renderer: 'pgCriticaRenderer',
          className: 'htCenter htMiddle',
        },
        {
          data: 'unidad',
          type: 'dropdown',
          source: unitOptions,
          strict: false,
          allowInvalid: false,
          renderer: 'pgGenericDropdownRenderer',
          className: 'htCenter htMiddle',
        },
        {
          data: 'cantidad_ppto',
          type: 'numeric',
          numericFormat: { pattern: '0.0' },
          renderer: 'pgGenericNumericRenderer',
          className: 'htRight htMiddle',
        },
        {
          // Task 34: es una cantidad, no un identificador — se tipa y se alinea
          // a la derecha igual que su gemela `EjecutadoDisplay`, para que las dos
          // columnas de ejecutado se lean por unidades en la misma vertical.
          data: 'Ejecutado_Teorico',
          type: 'numeric',
          readOnly: true,
          renderer: 'pgEjecutadoTeoricoRenderer',
          className: 'htRight htMiddle',
        },
        {
          data: 'EjecutadoDisplay',
          type: 'numeric',
          numericFormat: { pattern: '0.0' },
          renderer: 'pgEjecutadoRealRenderer',
          className: 'htRight htMiddle',
        },
        { data: 'Estado', readOnly: true, renderer: 'pgStateChipRenderer', className: 'htCenter htMiddle force-wrap' },
        {
          data: 'Estado_Restricciones',
          readOnly: true,
          renderer: 'pgPercentRenderer',
          className: 'htCenter htMiddle',
        },
      ],
      hiddenColumns: getResponsiveHiddenColumnsConfig(),
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'none',
      autoColumnSize: false,
      manualColumnResize: false,
      manualRowResize: true,
      contextMenu: true,
      dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
      filters: true,
      modifyFiltersMultiSelectValue: function (value, meta) {
        if (meta && (meta.prop === 'Actividad' || meta.data === 'Actividad')) {
          return getActividadPlainText(value);
        }

        return value;
      },
      search: false,
      exportFile: true,
      columnSorting: false,
      wordWrap: true,
      colWidths: function(index) {
        var container = document.getElementById('hot-container');
        var baseWidth = container ? container.clientWidth : window.innerWidth;
        // Restar 60px para acomodar el scrollbar y la barra lateral derecha (Concurrencia LPS)
        var cw = baseWidth - 60;
        // Suma exacta = 1.0 (100%)
        // [Id, Cod, Actividad, SemIni, F.Ini, F.Fin, Crit, Unidad, PPTO, Ej.Teo, Ej.Real, Estado, Lib.Restr]
        var p = [0.04, 0.05, 0.25, 0.05, 0.08, 0.08, 0.04, 0.04, 0.06, 0.07, 0.07, 0.10, 0.07];
        var w = Math.floor(cw * p[index]);
        return Math.max(w, 20);
      },
      autoRowSize: false,
      rowHeights: 45,
      renderAllRows: false,
      viewportRowRenderingOffset: 20,
      viewportColumnRenderingOffset: 10,
      colHeaderHeight: 48,
      width: '100%',
      height: isEmptyInitialGrid ? '100%' : (getContainerAvailableHeight() || '100%'),
      afterRender: function () {
        var hotInstance = this;
        window.requestAnimationFrame(function () {
          refreshVisiblePGCellMeta(hotInstance);
          syncRenderedTableWidth(hotInstance);
          // C-19: el barrido de titles necesita el ancho ya aplicado, asi que
          // va aqui y no en el renderer. (C-37 vive en `afterGetColHeader`.)
          refreshHeaderTitles(hotInstance);
          if (window.AiaComponents && window.AiaComponents.ensureScrollableRegions) {
            window.AiaComponents.ensureScrollableRegions(container);
          }
        });
      },
      // Task 26 ponia aqui el `title` del header. C-19 (2026-08-05) lo movio a
      // `refreshHeaderTitles()`, invocado desde `afterRender`: solo ahi el ancho
      // de columna es el definitivo y se puede saber si el texto se recorta de
      // verdad.
      // (C-37 se resuelve con un MutationObserver sobre el contenedor; ver
      // `observeDecorativeHeaderTriggers`.)
      className: 'htMiddle',
      cells: function (row, col, prop) {
        var hotInstance = (this && this.instance) || (window.PGHotModule && window.PGHotModule.getHotInstance && window.PGHotModule.getHotInstance());
        return buildPGCellProperties(hotInstance, row, col, prop);
      },
      afterChange: function (changes, source) {
        if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') {
          return;
        }

        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          if (!change) continue;
          var visualRow = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];

          var physicalRow = this.toPhysicalRow(visualRow);
          if (visualRow === null || visualRow < 0 || !Number.isInteger(physicalRow) || physicalRow < 0) {
            continue;
          }

          var currentRowData = typeof this.getSourceDataAtRow === 'function' ? (this.getSourceDataAtRow(physicalRow) || {}) : {};

          if (currentRowData._classification) {
            delete currentRowData._classification;
          }
          if (physicalRow >= 0) {
            _rowClassCache[physicalRow] = undefined;
          }

          if (!editableProps[prop] || oldValue === newValue) {
            continue;
          }

          var normalized = normalizeCellValue(prop, newValue);
          if (!normalized.valid) {
            revertCell(visualRow, prop, oldValue);
            showFeedback('error', normalized.error);
            continue;
          }

          if (normalized.value !== newValue) {
            hot.setDataAtRowProp(visualRow, prop, normalized.value, 'internal-update');
          }

          currentRowData = typeof this.getSourceDataAtRow === 'function' ? (this.getSourceDataAtRow(physicalRow) || {}) : {};
          var previousContext = null;
          if (prop === 'unidad' || prop === 'cantidad_ppto') {
            previousContext = buildDisplayContext(currentRowData, {
              unidad: prop === 'unidad' ? oldValue : currentRowData.unidad,
              cantidad_ppto: prop === 'cantidad_ppto' ? oldValue : currentRowData.cantidad_ppto,
            });
          }

          if (prop === 'unidad') {
            var rd = currentRowData || {};
            var isPercent = isPercentLikeUnit(normalized.value);
            var hasCantidad = rd.cantidad_ppto !== null && rd.cantidad_ppto !== '' && rd.cantidad_ppto !== undefined;

            if (isPercent && hasCantidad) {
              revertCell(visualRow, prop, oldValue);
              (function (vRow, newUnit, oldUnit, previousUnitContext, cantVal) {
                showUnitChangeConfirm(cantVal, function () {
                  var currentUnitRowData = getSourceRowDataByVisualRow(hot, vRow) || {};
                  var percentPayloadOverrides = buildPercentUnitPayloadOverrides(currentUnitRowData, newUnit);
                  saveRow(vRow, 'unidad', oldUnit, 'unit-change-confirm', {
                    previousContext: previousUnitContext,
                    payloadOverrides: percentPayloadOverrides,
                    reloadAfterSuccess: true,
                  });
                });
              })(visualRow, normalized.value, oldValue, previousContext, rd.cantidad_ppto);
              continue;
            }
          }

          if (prop === 'EjecutadoDisplay') {
            currentRowData.Estado = '';
          }

          saveRow(visualRow, prop, oldValue, source, {
            previousContext: previousContext,
          });
        }

        this.render();
      },
    });
    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();
    syncFocusCatcherA11y(container);
    observeDecorativeHeaderTriggers(container);
    hot.addHook('afterFilter', function () {
      if (!hot) return;
      window.setTimeout(function () {
        if (!hot) return;
        buildRowClassCache(hot.getSourceData());
        refreshVisiblePGCellMeta(hot);
        hot.render();
      }, 0);
    });
    hot.addHook('afterLoadData', function () {
      invalidatePGClassificationCache();
      _pgCellMetaVersion++;
      if (!hot) return;
      window.setTimeout(function () {
        if (!hot) return;
        var sourceData = hot.getSourceData();
        if (!Array.isArray(sourceData) || sourceData.length === 0) return;
        buildRowClassCache(sourceData);
        refreshVisiblePGCellMeta(hot);
        hot.render();
      }, 0);
    });
    hot.addHook('beforeRenderer', function (TD, row, col, prop, value, cellProperties) {
      if (!hot || hot.isDestroyed || !cellProperties) return;
      var rowData = getSourceRowDataByVisualRow(hot, row);
      if (!rowData) return;
      var newProps = buildPGCellProperties(hot, row, col, prop, rowData);
      cellProperties.className = newProps.className;
      if (newProps.readOnly) {
        cellProperties.readOnly = true;
      }
    });
    container.addEventListener(
      'mousedown',
      function () {
        if (hot && !hot.isDestroyed) {
          hot.listen();
        }
      },
      true
    );

    if (window.LPSContextualDrawer) {
      window.LPSContextualDrawer.init(hot, 'programa-general', classifyPGRow);
    }

    scheduleLayoutRefresh(0, true);
  }

  function getFilteredRows() {
    var filtered = [];

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      var classification = classifyPGRow(row);
      if (rowMatchesFilters(row, classification)) {
        filtered.push(row);
      }
    }

    return filtered;
  }

  function applyFiltersAndRender() {
    var filtered = getFilteredRows();
    currentFilteredRows = filtered;
    updateLegendCounts(filtered);
    updateOrInitHot(filtered);
    renderMobileCards();
  }

  function syncLegendVisualState() {
    $('#pgLegend .pg-filter-chip').attr('aria-pressed', function () {
      return activeFilters.indexOf(String($(this).data('filter'))) > -1 ? 'true' : 'false';
    });

    if (activeFilters.length === 0) {
      $('#pgLegend .pg-filter-chip').removeClass('inactive-filter');
    } else {
      $('#pgLegend .pg-filter-chip').addClass('inactive-filter');
      for (var i = 0; i < activeFilters.length; i++) {
        $("#pgLegend .pg-filter-chip[data-filter='" + activeFilters[i] + "']").removeClass(
          'inactive-filter'
        );
      }
    }

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
    $('#buscadorActividad').off('input.pg').on('input.pg', applyFiltersAndRender);

    $('#buscadorSemanasInicio').off('change.pg').on('change.pg', applyFiltersAndRender);
    $('#buscadorCritica').off('change.pg').on('change.pg', applyFiltersAndRender);
    $('#buscadorEstado')
      .off('change.pg')
      .on('change.pg', function () {
        selectedStateFilter = String($(this).val() || '').trim();
        applyFiltersAndRender();
      });

    $('#btn_limpiar_buscador')
      .off('click.pg')
      .on('click.pg', function () {
        $('#buscadorActividad').val('');

        $('#buscadorSemanasInicio').val('');
        $('#buscadorCritica').val('');
        $('#buscadorEstado').val('');
        selectedStateFilter = '';
        activeFilters = [];
        syncLegendVisualState();
        applyFiltersAndRender();
      });

    $('#pgLegend')
      .off('click.pg keydown.pg')
      .on('click.pg', '.pg-filter-chip', function (event) {
        var key = $(this).data('filter');
        if (key) {
          toggleLegendFilter(String(key), event);
        }
      })
      .on('keydown.pg', '.pg-filter-chip', function (event) {
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
      filename: 'programa_general',
      columnHeaders: true,
      rowHeaders: false,
    });
  }

  function actualizarEjecucion() {
    var db = getDb();
    var semana = getSemana();
    var fechaInicioSem = $('#Fecha_Inicio_SemYMD').val() || '';

    $('#actualizarEjecucion').prop('disabled', true).html('Actualizando... <i class="fas fa-spinner fa-spin ml-1"></i>');

    $.ajax({
      method: 'POST',
      url:
        '/api/general/update-batch?db=' +
        encodeURIComponent(db) +
        '&semana=' +
        encodeURIComponent(semana),
      dataType: 'json',
      data: {
        opcion: 'modificargrupo',
        Id1: 'Consecutivo_en_Programa > 0',
        Ejecutado: 'Ejecutado',
        inicio_semana: fechaInicioSem,
      },
    })
      .done(function (response) {
        if (response && response.respuesta === 'BIEN') {
          showFeedback('success', 'Ejecución actualizada');
        } else {
          showFeedback('error', 'Error al actualizar ejecución');
        }
      })
      .fail(function () {
        showFeedback('error', 'Error de red al actualizar');
      })
      .always(function () {
        loadData();
        $('#actualizarEjecucion')
          .prop('disabled', false)
          .html('Actualizar Ejecución <i class="fas fa-sync ml-1"></i>');
      });
  }

  function descargarCorteProgramacion() {
    var db = getDb();
    var semana = getSemana();

    $('#descargarCorteProgramacion').prop('disabled', true).text('Generando...');

    $.ajax({
      url: '/reportes/corte-programacion',
      method: 'POST',
      dataType: 'json',
      data: { db: db, semana: semana },
    })
      .done(function (response) {
        if (response && response.url) {
          window.location.href = response.url;
        } else {
          showFeedback('error', 'No se pudo generar el corte');
        }
      })
      .fail(function () {
        showFeedback('error', 'Error descargando corte');
      })
      .always(function () {
        $('#descargarCorteProgramacion')
          .prop('disabled', false)
          .html('Descargar Corte <i class="fas fa-download ml-1"></i>');
      });
  }

  function bindActions() {
    $('#btn-refresh').off('click.pgRefresh').on('click.pgRefresh', loadData);
    $('#btn-export').off('click.pgExport').on('click.pgExport', exportCsv);
    if (canManageGeneralProgram()) {
      $('#actualizarEjecucion').off('click.pgRecalc').on('click.pgRecalc', actualizarEjecucion);
    } else {
      $('#actualizarEjecucion').remove();
    }
    $('#descargarCorteProgramacion')
      .off('click.pgCut')
      .on('click.pgCut', descargarCorteProgramacion);

    $('#pdcFiltersMobile')
      .off('shown.bs.collapse.pgLayout hidden.bs.collapse.pgLayout')
      .on('shown.bs.collapse.pgLayout hidden.bs.collapse.pgLayout', function () {
        scheduleLayoutRefresh(0, true);
      });

    $('.btn-filter-toggle[data-target="#pdcFiltersMobile"]')
      .off('click.pgCollapseFallback')
      .on('click.pgCollapseFallback', function (event) {
        if ($.fn && typeof $.fn.collapse === 'function') {
          return;
        }

        var panel = document.getElementById('pdcFiltersMobile');
        if (!panel) {
          return;
        }

        event.preventDefault();
        var opened = !panel.classList.contains('show');
        panel.classList.toggle('show', opened);
        this.setAttribute('aria-expanded', opened ? 'true' : 'false');
        scheduleLayoutRefresh(0, true);
      });

    $(document)
      .off('.pgLegend', '#modal_leyenda_colores')
      .on('show.bs.modal.pgLegend', '#modal_leyenda_colores', function () {
        this.__pgLegendTrigger = document.activeElement;
        renderLegendModal();
      })
      .on('shown.bs.modal.pgLegend', '#modal_leyenda_colores', function () {
        this.focus();
      })
      .on('hidden.bs.modal.pgLegend', '#modal_leyenda_colores', function () {
        if (this.__pgLegendTrigger && document.contains(this.__pgLegendTrigger)) {
          this.__pgLegendTrigger.focus();
        }
        this.__pgLegendTrigger = null;
      });
  }

  function bindResize() {
    $(window)
      .off('resize.pgHot orientationchange.pgHot aia:viewport-scale-change.pgHot')
      .on('resize.pgHot orientationchange.pgHot aia:viewport-scale-change.pgHot', function () {
        scheduleLayoutRefresh(80, true);
      });
  }

  function init() {
    if (!hot && getDb() && getSemana()) {
      updateOrInitHot([]);
    }

    if (!initialized) {
      bindActions();
      bindFilters();
      bindResize();
      fetchCodigosActividad();
      bindAutoUpdateOnNavigation();
      // Sesión caducada: la decisión de qué hacer ante un 401 con `sessionExpired`
      // vive en AIA.SessionExpiredHandler (public/js/core/SessionExpiredHandler.js).
      if (window.AIA && window.AIA.SessionExpiredHandler) {
        window.AIA.SessionExpiredHandler.bindWithShowFeedback($, showFeedback);
      }
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso_canonico').val() || getPermiso());
    }

    syncLegendVisualState();
    var shouldAutoUpdate = shouldAutoUpdateOnEntry() && canManageGeneralProgram();
    if (shouldAutoUpdate) {
      fetchRestrictionConfig().always(function () {
        actualizarEjecucion();
      });
    } else {
      loadData();
      fetchRestrictionConfig();
    }
  }

  var PG_AUTO_UPDATE_FLAG = 'pgAutoUpdateOnNextLoad';

  function bindAutoUpdateOnNavigation() {
    try {
      window.addEventListener('pagehide', function () {
        try { sessionStorage.setItem(PG_AUTO_UPDATE_FLAG, '1'); } catch (e) { /* noop */ }
      });
    } catch (e) { /* noop */ }
  }

  function shouldAutoUpdateOnEntry() {
    try {
      var flag = sessionStorage.getItem(PG_AUTO_UPDATE_FLAG);
      if (flag === '1') {
        sessionStorage.removeItem(PG_AUTO_UPDATE_FLAG);
        return true;
      }
    } catch (e) { /* noop */ }
    return false;
  }

  window.PGHotModule = {
    init: init,
    getHotInstance: function () {
      return hot;
    },
  };
})(window, jQuery);
