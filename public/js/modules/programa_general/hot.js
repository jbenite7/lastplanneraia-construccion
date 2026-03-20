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
    'adelantada',
    'en-curso',
    'atrasada-critica',
    'atrasada',
    'terminada',
    'no-requerida',
    'header',
  ];

  var unitOptions = ['', 'ml', 'm2', 'm3', 'un', 'gl', 'kg', '%', 'Niveles'];

  var columnMinWidths = [34, 52, 120, 44, 72, 72, 42, 42, 54, 64, 64, 78, 70];
  var columnFloorWidths = [28, 44, 90, 36, 60, 60, 34, 34, 44, 52, 52, 60, 56];
  var columnMaxWidths = [84, 156, 420, 110, 132, 132, 86, 86, 128, 138, 138, 260, 190];
  var columnShrinkPriority = [2, 11, 1, 12, 8, 9, 10, 4, 5, 3, 0, 6, 7];

  function getDb() {
    return $('#baseDatos_PHP').val() || $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_PHP').val() || $('#permiso').val() || '')
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

    if (getSemanalConfirmada() === 1 && !isDirectorRole(permiso)) {
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
      case 'debe iniciar esta semana':
      case 'debe iniciar esta semana y restricciones pendientes':
        return 'debe-iniciar';
      case 'adelantada':
        return 'adelantada';
      case 'en curso':
      case 'a tiempo':
        return 'en-curso';
      case 'actividad futura':
      case 'en liberacion de restricciones':
        return 'actividad-futura';
      case 'no requerida':
      case 'ni':
        return 'no-requerida';
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

    if (semanasInicio < 0 && ejecutado < 0.999) {
      return 'atrasada';
    }

    if (semanasInicio === 0 && ejecutado <= 0) {
      return 'debe-iniciar';
    }

    if (semanasInicio > 0 && semanasInicio <= 6 && ejecutado <= 0) {
      return 'actividad-futura';
    }

    if (semanasInicio > 6 && ejecutado <= 0) {
      return 'no-requerida';
    }

    if (ejecutado > 0 && ejecutado < 0.999) {
      return 'en-curso';
    }

    return 'no-requerida';
  }

  function getRestrictionAlertKey(data) {
    if (!data || Number(data.Titulo) !== 0) {
      return '';
    }

    var estadoRestricciones = normalizeRatio(data.Estado_Restricciones);
    if (estadoRestricciones === null) {
      estadoRestricciones = 1;
    }

    var ejecutado = getEjecutadoRatio(data);
    if (ejecutado === null) {
      ejecutado = 0;
    }

    if (estadoRestricciones >= 0.999 || ejecutado >= 0.999) {
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

  function classifyPGRow(data) {
    if (!data || Number(data.Titulo) !== 0) {
      return {
        key: 'header',
        rowClass: 'pdc-header',
        restrictionAlertKey: '',
      };
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
    var stateKey = baseKey === 'atrasada' && isCritical ? 'atrasada-critica' : baseKey;
    var rowClassMap = {
      'atrasada-critica': 'pg-state-atrasada-critica',
      atrasada: 'pg-state-atrasada',
      'debe-iniciar': 'pg-state-debe-iniciar',
      'actividad-futura': 'pg-state-actividad-futura',
      adelantada: 'pg-state-adelantada',
      'en-curso': 'pg-state-en-curso',
      terminada: 'pg-state-terminada',
      'no-requerida': 'pg-state-no-requerida',
    };

    return {
      key: stateKey,
      rowClass: rowClassMap[stateKey] || 'pg-state-no-requerida',
      restrictionAlertKey: getRestrictionAlertKey(data),
    };
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
      window.AIA.Notice.badge('success', message || 'Guardado');
      return;
    }
    window.AIA.Notice.error(message || 'Error al guardar');
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
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-atrasada-critica'></span>" +
        "<div class='pg-legend-quick-state'><strong>Atrasada (Critica)</strong><small>Debajo del teorico semanal en ruta critica.</small></div>" +
        "<div class='pg-legend-quick-action'>Escalar bloqueo y activar recuperacion.</div>" +
        "<span class='pg-legend-quick-priority is-p1'>P1</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-atrasada'></span>" +
        "<div class='pg-legend-quick-state'><strong>Atrasada</strong><small>Por debajo de la curva teorica al inicio de semana.</small></div>" +
        "<div class='pg-legend-quick-action'>Reprogramar frente y cerrar causa del atraso.</div>" +
        "<span class='pg-legend-quick-priority is-p1'>P1</span>" +
        '</div>' +
        '</section>' +
        "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>P2 - Gestion semanal</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-debe-iniciar'></span>" +
        "<div class='pg-legend-quick-state'><strong>Debe Iniciar esta Semana</strong><small>Inicio dentro de la semana actual y sin avance.</small></div>" +
        "<div class='pg-legend-quick-action'>Asegurar recursos, cuadrilla y frente liberado.</div>" +
        "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-en-curso'></span>" +
        "<div class='pg-legend-quick-state'><strong>En Curso</strong><small>Ejecucion alineada con la curva teorica semanal.</small></div>" +
        "<div class='pg-legend-quick-action'>Sostener ritmo diario y control de productividad.</div>" +
        "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
        '</div>' +
        '</section>' +
        "<section class='pg-legend-quick-group'>" +
        "<h6 class='pg-legend-quick-group-title'>P3 - Seguimiento</h6>" +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-adelantada'></span>" +
        "<div class='pg-legend-quick-state'><strong>Adelantada</strong><small>En curso por encima del cronograma teorico.</small></div>" +
        "<div class='pg-legend-quick-action'>Proteger el adelanto para no perder rendimiento.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-actividad-futura'></span>" +
        "<div class='pg-legend-quick-state'><strong>Actividad Futura</strong><small>Inicia dentro del horizonte de 6 semanas.</small></div>" +
        "<div class='pg-legend-quick-action'>Preparar compras, mano de obra y permisos.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-terminada'></span>" +
        "<div class='pg-legend-quick-state'><strong>Terminada</strong><small>Actividad cerrada (a tiempo o adelantada).</small></div>" +
        "<div class='pg-legend-quick-action'>Cerrar trazabilidad y liberar foco del equipo.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        "<div class='pg-legend-quick-row'>" +
        "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-no-requerida'></span>" +
        "<div class='pg-legend-quick-state'><strong>No Requerida</strong><small>Fuera del lookahead de 6 semanas.</small></div>" +
        "<div class='pg-legend-quick-action'>Mantener en monitoreo de mediano plazo.</div>" +
        "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
        '</div>' +
        '</section>' +
        "<section class='pg-legend-quick-alerts'>" +
        "<h6 class='pg-legend-quick-group-title'>Alertas secundarias de restricciones</h6>" +
        "<p class='pg-legend-quick-alert-intro'>R0-R1-R2/3-R4/6 no cambian el estado principal. Solo anticipan desbloqueos.</p>" +
        "<div class='pg-legend-quick-alert-grid'>" +
        "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-0'></span><strong>R0</strong><small>Arranque inmediato o vencido.</small></div>" +
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
          'activa_no_requeridas',
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
        showFeedback('error', 'Error cargando datos');
      });
  }

  function loadData() {
    if (!getDb() || !getSemana()) {
      showLoading(false);
      return;
    }

    showLoading(true);
    fetchFilterFlags().done(function (flags) {
      requestList(flags || '');
    });
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
    var id = payloadRow.Consecutivo_en_Programa || payloadRow.Id;
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

    if (!id) {
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
        Id: id,
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

  function getPhysicalRowIndex(visualRow) {
    if (!hot || typeof hot.toPhysicalRow !== 'function') {
      return visualRow;
    }

    var physicalRow = hot.toPhysicalRow(visualRow);
    return Number.isInteger(physicalRow) && physicalRow >= 0 ? physicalRow : visualRow;
  }

  function getRowDataByVisualRow(visualRow) {
    if (!hot) {
      return null;
    }

    return hot.getSourceDataAtRow(getPhysicalRowIndex(visualRow));
  }

  function saveRow(visualRow, prop, oldValue, source, options) {
    var db = getDb();
    var semana = getSemana();
    var rowData = getRowDataByVisualRow(visualRow) || {};
    var saveOptions = options || {};
    var payloadOverrides = saveOptions.payloadOverrides || null;

    if (!payloadOverrides && prop === 'EjecutadoDisplay') {
        var editedRatio = ratioFromDisplayContext(buildDisplayContext(rowData));
        hot.setDataAtRowProp(visualRow, 'Ejecutado', normalizeRatio(editedRatio), 'internal-update');
        rowData = getRowDataByVisualRow(visualRow) || {};
    }

    if (!payloadOverrides && (prop === 'unidad' || prop === 'cantidad_ppto')) {
        var preservedRatio = getEjecutadoRatio(rowData);
        if (preservedRatio !== null) {
          var newDisplay = displayFromRatioForContext(preservedRatio, buildDisplayContext(rowData));
          hot.setDataAtRowProp(visualRow, 'EjecutadoDisplay', newDisplay, 'internal-update');
          rowData = getRowDataByVisualRow(visualRow) || {};
        }
    }

    var payload = buildUpdatePayload(rowData || {}, prop, payloadOverrides);
    if (!payload.valid) {
      revertCell(visualRow, prop, oldValue);
      showFeedback('error', payload.error);
      return;
    }

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
          if (response.estado) {
            hot.setDataAtRowProp(visualRow, 'Estado', response.estado, 'internal-update');
          }
          if (response.Semanas_Inicio !== undefined && response.Semanas_Inicio !== null) {
            hot.setDataAtRowProp(visualRow, 'Semanas_Inicio', response.Semanas_Inicio, 'internal-update');
          }
          if (response.Ejecutado !== undefined && response.Ejecutado !== null) {
            var resRatio = parseFloat(response.Ejecutado);
            var updatedRowData = getRowDataByVisualRow(visualRow) || rowData || {};
            var mappedUnit = String((response.unidad !== undefined ? response.unidad : updatedRowData.unidad) || '').trim();
            if (response.unidad !== undefined) {
              hot.setDataAtRowProp(visualRow, 'unidad', mappedUnit, 'internal-update');
            }
            if (response.cantidad_ppto !== undefined) {
              hot.setDataAtRowProp(visualRow, 'cantidad_ppto', response.cantidad_ppto, 'internal-update');
            }
            hot.setDataAtRowProp(visualRow, 'Ejecutado', normalizeRatio(resRatio), 'internal-update');
            updatedRowData = getRowDataByVisualRow(visualRow) || updatedRowData;
            hot.setDataAtRowProp(
              visualRow,
              'EjecutadoDisplay',
              displayFromRatioForContext(normalizeRatio(resRatio), buildDisplayContext(updatedRowData)),
              'internal-update'
            );
          }

          hot.render();
          updateLegendCounts(masterData);
          if (prop === 'unidad' || prop === 'cantidad_ppto' || saveOptions.reloadAfterSuccess) {
            loadData();
          }
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
        '<div class="modal fade" id="' + modalId + '" role="dialog" data-backdrop="static">' +
        '  <div class="modal-dialog" role="document">' +
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

    Handsontable.renderers.registerRenderer(
      'pgPercentRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.textContent = formatPercent(value);
        td.classList.add('htCenter');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgPercentValueRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.textContent = formatPercentValue(value);
        td.classList.add('htCenter');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgCriticaRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        var numeric = toNumber(value, null);
        td.textContent = numeric === 1 ? 'Sí' : numeric === 0 ? 'No' : String(value || '');
        td.classList.add('htCenter');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgActividadRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.innerHTML = sanitizeActividadHtml(value);
        td.classList.add('htLeft');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgEjecutadoTeoricoRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        var rowData = instance.getSourceDataAtRow(row) || {};
        var cantidadPpto = toNumber(rowData.cantidad_ppto, null);
        var unidad = String(rowData.unidad || '').trim();
        var ratio = toNumber(value, null);
        if (ratio === null) { td.textContent = ''; td.classList.add('htCenter'); return; }
        if (isPercentLikeUnit(unidad) || cantidadPpto === null || cantidadPpto <= 0) {
          td.textContent = formatPercentValue(value);
        } else {
          var qty = Math.round((cantidadPpto * ratio + Number.EPSILON) * 10) / 10;
          td.innerHTML = "<span class='pg-cell-main'>" + formatValueWithUnit(qty, rowData.unidad) + "</span> <span class='pg-cell-meta'>(" + formatPercentValue(value) + ")</span>";
        }
        td.classList.add('htCenter');
      }
    );

    Handsontable.renderers.registerRenderer(
      'pgEjecutadoRealRenderer',
      function (instance, td, row, col, prop, value) {
        Handsontable.renderers.NumericRenderer.apply(this, arguments);
        var rowData = instance.getSourceDataAtRow(row) || {};
        var cantidadPpto = toNumber(rowData.cantidad_ppto, null);
        var physicalVal = toNumber(value, null);
        var ratio = getEjecutadoRatio(rowData);
        
        if (physicalVal === null) { td.textContent = ''; td.classList.add('htCenter'); return; }
        
        var unidad = String(rowData.unidad || '').trim();
        
        // Formateo natural de display
        var physicalDisplay = formatDecimalComma(physicalVal, 1);
        
        if (isPercentLikeUnit(unidad) || cantidadPpto === null || cantidadPpto <= 0) {
            // Si es %, el valor físico YA ES el porcentaje
            td.textContent = physicalDisplay + '%';
        } else {
            // Calcular porcentaje pasivo de lectura
            var percent = ratio === null ? (physicalVal / cantidadPpto * 100) : (ratio * 100);
            var percentDisplay = formatDecimalComma(percent, 1);
            td.innerHTML = "<span class='pg-cell-main'>" + formatValueWithUnit(physicalDisplay, unidad) + "</span> <span class='pg-cell-meta'>(" + percentDisplay + "%)</span>";
        }
        td.classList.add('htCenter');
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
    if (Number.isFinite(zoom) && zoom > 0 && zoom < 1) {
      return zoom;
    }

    if (
      root.classList.contains('tablet-scale-70') ||
      root.classList.contains('desktop-tablet-scale-70')
    ) {
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
      } catch (_err) {}
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

    if (
      !force &&
      containerWidth === lastAppliedContainerWidth &&
      currentColumnWidths.length === columnCount
    ) {
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
    layoutTimer = setTimeout(
      function () {
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
      },
      Number.isFinite(delay) ? delay : 24
    );
  }

  function getQuickFilterText() {
    return String($('#input_buscador').val() || '')
      .trim()
      .toLowerCase();
  }

  function getActividadFilterText() {
    var text = String($('#buscadorActividad').val() || '').trim();
    if (!text) {
      text = String($('#input_buscador').val() || '').trim();
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
      'con-alerta-restricciones': 0,
      'debe-iniciar': 0,
      'actividad-futura': 0,
      adelantada: 0,
      'en-curso': 0,
      'atrasada-critica': 0,
      atrasada: 0,
      terminada: 0,
      'no-requerida': 0,
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

    Object.keys(counts).forEach(function (key) {
      $('#count-' + key).text('(' + counts[key] + ')');
    });
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
        'Código Actividad',
        'Actividad',
        'Semanas Inicio',
        'Fecha Inicio',
        'Fecha Fin',
        'Crítica',
        'Unidad',
        'Cantidad PPTO',
        'Ejecutado Teórico',
        'Ejecutado Real',
        'Estado',
        'Liberación Restricciones',
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
        { data: 'Semanas_Inicio', readOnly: true, className: 'htCenter htMiddle' },
        {
          data: 'Fecha_Inicio',
          type: 'date',
          dateFormat: 'YYYY-MM-DD',
          correctFormat: true,
          className: 'htCenter htMiddle',
        },
        {
          data: 'Fecha_Fin',
          type: 'date',
          dateFormat: 'YYYY-MM-DD',
          correctFormat: true,
          className: 'htCenter htMiddle',
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
          className: 'htCenter htMiddle',
        },
        {
          data: 'cantidad_ppto',
          type: 'numeric',
          numericFormat: { pattern: '0.0' },
          className: 'htCenter htMiddle',
        },
        {
          data: 'Ejecutado_Teorico',
          readOnly: true,
          renderer: 'pgEjecutadoTeoricoRenderer',
          className: 'htCenter htMiddle',
        },
        {
          data: 'EjecutadoDisplay',
          type: 'numeric',
          numericFormat: { pattern: '0.0' },
          renderer: 'pgEjecutadoRealRenderer',
          className: 'htCenter htMiddle',
        },
        { data: 'Estado', readOnly: true, className: 'htCenter htMiddle force-wrap' },
        {
          data: 'Estado_Restricciones',
          readOnly: true,
          renderer: 'pgPercentRenderer',
          className: 'htCenter htMiddle',
        },
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
      className: 'htMiddle',
      cells: function (row, col, prop) {
        var props = {};
        var rowData = this.instance.getSourceDataAtRow(row) || {};
        var classification = classifyPGRow(rowData);
        var stateClass = classification.rowClass || 'pg-state-no-requerida';
        var composed = 'pg-row-state ' + stateClass;
        var columnMeta = this.instance.getSettings().columns[col] || {};
        var baseClass = columnMeta.className || '';
        var isHeader = Number(rowData.Titulo) === 1;
        var canEdit = Boolean(editableProps[prop]) && !isHeader && isUserAllowedToEdit();

        // Bloquear cantidad_ppto si la unidad es %
        if (canEdit && prop === 'cantidad_ppto' && isPercentLikeUnit(rowData.unidad)) {
          canEdit = false;
        }

        if (classification.restrictionAlertKey) {
          composed += ' pg-state-' + classification.restrictionAlertKey;
        }

        props.className = (
          baseClass +
          ' ' +
          composed +
          ' ' +
          (canEdit ? 'pg-cell-editable' : 'pg-cell-readonly')
        ).trim();
        props.readOnly = !canEdit;

        return props;
      },
      afterChange: function (changes, source) {
        if (
          !changes ||
          source === 'loadData' ||
          source === 'revert' ||
          source === 'internal-update'
        ) {
          return;
        }

        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          var row = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];

          if (!editableProps[prop] || oldValue === newValue) {
            continue;
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

          var currentRowData = getRowDataByVisualRow(row) || {};
          var previousContext = null;
          if (prop === 'unidad' || prop === 'cantidad_ppto') {
            previousContext = buildDisplayContext(currentRowData, {
              unidad: prop === 'unidad' ? oldValue : currentRowData.unidad,
              cantidad_ppto: prop === 'cantidad_ppto' ? oldValue : currentRowData.cantidad_ppto,
            });
          }

          // Auto-clear cantidad_ppto al cambiar unidad a %
          if (prop === 'unidad') {
            var rd = getRowDataByVisualRow(row) || {};
            var isPercent = isPercentLikeUnit(normalized.value);
            var hasCantidad = rd.cantidad_ppto !== null && rd.cantidad_ppto !== '' && rd.cantidad_ppto !== undefined;

            if (isPercent && hasCantidad) {
              revertCell(row, prop, oldValue);
              (function (vRow, newUnit, oldUnit, previousUnitContext, cantVal) {
                showUnitChangeConfirm(cantVal, function () {
                  var currentUnitRowData = getRowDataByVisualRow(vRow) || {};
                  var percentPayloadOverrides = buildPercentUnitPayloadOverrides(currentUnitRowData, newUnit);
                  saveRow(vRow, 'unidad', oldUnit, 'unit-change-confirm', {
                    previousContext: previousUnitContext,
                    payloadOverrides: percentPayloadOverrides,
                    reloadAfterSuccess: true,
                  });
                });
              })(row, normalized.value, oldValue, previousContext, rd.cantidad_ppto);
              continue;
            }
          }

          saveRow(row, prop, oldValue, source, {
            previousContext: previousContext,
          });
        }
      },
    });

    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();
    container.addEventListener(
      'mousedown',
      function () {
        if (hot && !hot.isDestroyed) {
          hot.listen();
        }
      },
      true
    );

    scheduleLayoutRefresh(0, true);
  }

  function applyFiltersAndRender() {
    var filtered = [];

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      var classification = classifyPGRow(row);
      if (rowMatchesFilters(row, classification)) {
        filtered.push(row);
      }
    }

    updateLegendCounts(filtered);
    updateOrInitHot(filtered);
  }

  function syncLegendVisualState() {
    if (activeFilters.length === 0) {
      $('#pgLegend .pdc-legend-item').removeClass('inactive-filter');
    } else {
      $('#pgLegend .pdc-legend-item').addClass('inactive-filter');
      for (var i = 0; i < activeFilters.length; i++) {
        $("#pgLegend .pdc-legend-item[data-filter='" + activeFilters[i] + "']").removeClass(
          'inactive-filter'
        );
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
    $('#buscadorActividad').off('input.pg').on('input.pg', applyFiltersAndRender);
    $('#input_buscador').off('input.pgquick').on('input.pgquick', applyFiltersAndRender);
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
        $('#input_buscador').val('');
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
      .on('click.pg', '.pdc-legend-item', function (event) {
        var key = $(this).data('filter');
        if (key) {
          toggleLegendFilter(String(key), event);
        }
      })
      .on('keydown.pg', '.pdc-legend-item', function (event) {
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

    $('#actualizarEjecucion').prop('disabled', true).text('Actualizando...');

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
          loadData();
        } else {
          showFeedback('error', 'Error al actualizar ejecución');
        }
      })
      .fail(function () {
        showFeedback('error', 'Error de red al actualizar');
      })
      .always(function () {
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
    $('#actualizarEjecucion').off('click.pgRecalc').on('click.pgRecalc', actualizarEjecucion);
    $('#descargarCorteProgramacion')
      .off('click.pgCut')
      .on('click.pgCut', descargarCorteProgramacion);

    $('#pdcFiltersMobile')
      .off('shown.bs.collapse.pgLayout hidden.bs.collapse.pgLayout')
      .on('shown.bs.collapse.pgLayout hidden.bs.collapse.pgLayout', function () {
        scheduleLayoutRefresh(0, true);
      });

    $(document)
      .off('show.bs.modal.pgLegend', '#modal_leyenda_colores')
      .on('show.bs.modal.pgLegend', '#modal_leyenda_colores', renderLegendModal);
  }

  function bindResize() {
    $(window)
      .off('resize.pgHot orientationchange.pgHot aia:viewport-scale-change.pgHot')
      .on('resize.pgHot orientationchange.pgHot aia:viewport-scale-change.pgHot', function () {
        scheduleLayoutRefresh(80, true);
      });
  }

  function init() {
    if (!initialized) {
      bindActions();
      bindFilters();
      bindResize();
      fetchCodigosActividad();
      renderLegendModal();
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso').val() || getPermiso());
    }

    syncLegendVisualState();
    loadData();
  }

  window.PGHotModule = {
    init: init,
    getHotInstance: function () {
      return hot;
    },
  };
})(window, jQuery);
