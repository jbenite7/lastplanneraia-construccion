(function (global) {
  'use strict';

  function normalizeNumericString(value) {
    if (value === null || value === undefined) {
      return '';
    }

    var normalized = String(value).trim().replace(/\s+/g, '');
    if (!normalized || normalized.toLowerCase() === 'null') {
      return '';
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

    return normalized;
  }

  function toNumber(value, fallback) {
    if (value === null || value === undefined || value === '') {
      return fallback;
    }

    var normalized = normalizeNumericString(value);
    if (!normalized) {
      return fallback;
    }

    var parsed = parseFloat(normalized);
    return isFinite(parsed) ? parsed : fallback;
  }

  function normalizeEstado(value) {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value)
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function isCriticalRoute(value) {
    var normalized = String(value === undefined ? '' : value).trim().toLowerCase();
    return normalized === '1' || normalized === 'si' || normalized === 'sí';
  }

  function isNotApplicable(value) {
    var normalized = String(value === null || value === undefined ? '' : value).trim().toUpperCase();
    return normalized === 'N/A' || normalized === 'NO APLICA';
  }

  function restrictionValue(data, prop) {
    if (!data) {
      return null;
    }
    if (data[prop] !== undefined && data[prop] !== null && data[prop] !== '') {
      return data[prop];
    }
    var fallback = data['restr_' + prop];
    if (window.__PI_DEBUG_COLOR && fallback !== undefined) {
      console.warn('[PI-DEBUG] restrictionValue fallback:', { prop: prop, direct: data[prop], fallback: fallback });
    }
    return fallback;
  }

  function toRestrictionRatio(value) {
    if (value === null || value === undefined || value === '' || isNotApplicable(value)) {
      return null;
    }

    var raw = String(value).trim();
    var normalized = normalizeNumericString(raw.replace(/%/g, ''));
    if (!normalized) {
      return null;
    }

    var parsed = parseFloat(normalized);
    if (!isFinite(parsed)) {
      return null;
    }

    if (raw.indexOf('%') > -1) {
      parsed = parsed / 100;
    }
    while (parsed > 1 && parsed <= 10000) {
      parsed = parsed / 100;
    }

    if (parsed < 0) {
      return 0;
    }
    if (parsed > 1) {
      return 1;
    }
    return parsed;
  }

  function restrictionMeets(data, prop, minimum) {
    var value = restrictionValue(data, prop);
    if (isNotApplicable(value)) {
      return true;
    }
    var ratio = toRestrictionRatio(value);
    return ratio !== null && ratio + 0.0001 >= minimum;
  }

  function getHardRestrictions() {
    var config = window.__RESTRICTION_CONFIG__;
    if (config && Array.isArray(config.hardRestrictions) && Array.isArray(config.restrictions)) {
      var byKey = {};
      for (var i = 0; i < config.restrictions.length; i++) {
        byKey[config.restrictions[i].key] = config.restrictions[i];
      }
      var result = [];
      for (var j = 0; j < config.hardRestrictions.length; j++) {
        var key = config.hardRestrictions[j];
        var entry = byKey[key];
        if (entry) {
          result.push({ name: key, threshold: (entry.threshold || 100) / 100 });
        }
      }
      if (result.length > 0) {
        return result;
      }
    }
    // Fallback to Construction defaults
    return [
      { name: 'D_y_E', threshold: 1 },
      { name: 'Materiales', threshold: 1 },
      { name: 'MdeO', threshold: 1 },
      { name: 'Equipos', threshold: 1 },
      { name: 'Predecesora', threshold: 0.5 }
    ];
  }

  function isReadyToCommit(data) {
    var hardRestrictions = getHardRestrictions();
    for (var i = 0; i < hardRestrictions.length; i++) {
      var r = hardRestrictions[i];
      if (!restrictionMeets(data, r.name, r.threshold)) {
        return false;
      }
    }
    return true;
  }

  function getState(data) {
    if (!data || data.Consecutivo_en_Programa === undefined) {
      return 'neutral';
    }
    if (Number(data.Titulo) !== 0) {
      return 'header';
    }

    var si = Math.round(toNumber(data.Semanas_Inicio, 999));
    var ej = toNumber(data.Ejecutado, 0);
    var critical = isCriticalRoute(data.Ruta_Critica);

    var isLiberated = isReadyToCommit(data);
    var isStarted = ej > 0 && ej < 0.999;
    var isNotStarted = ej <= 0;
    var isOverdueSignal = si < 0;

    if (isStarted) {
      return isLiberated ? 'liberated-control' : 'execution-blocked';
    }

    if (si <= 0 && isNotStarted) {
      if (isLiberated) {
        return 'liberated-control';
      }

      if (isOverdueSignal) {
        return critical ? 'blocked-overdue-critical' : 'blocked-overdue';
      }

      return 'blocked-due';
    }

    if (si === 1 && isNotStarted && !isLiberated) {
      return 'alert-1-week';
    }

    if (si >= 2 && si <= 3 && isNotStarted && !isLiberated) {
      return 'alert-2-3-weeks';
    }

    if (si >= 4 && si <= 6 && isNotStarted && !isLiberated) {
      return 'alert-4-6-weeks';
    }

    if (isNotStarted && isLiberated && si > 0 && si <= 6) {
      return 'liberated-control';
    }

    return 'neutral';
  }

  global.PIStateMachine = {
    toNumber: toNumber,
    getState: getState,
    isReadyToCommit: isReadyToCommit,
  };
})(window);
