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

  function getState(data) {
    if (!data || data.Titulo != 0) {
      return 'header';
    }

    var si = Math.round(toNumber(data.Semanas_Inicio, 999));
    var er = toNumber(data.Estado_Restricciones, 0);
    var ej = toNumber(data.Ejecutado, 0);
    var critical = isCriticalRoute(data.Ruta_Critica);

    var isLiberated = er >= 0.999;
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
  };
})(window);
