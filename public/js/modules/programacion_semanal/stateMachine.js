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

  function isBlank(value) {
    if (value === null || value === undefined) {
      return true;
    }

    var text = String(value).trim();
    if (!text) {
      return true;
    }

    return text.toLowerCase() === 'null';
  }

  function toNumberOrNull(value) {
    if (isBlank(value)) {
      return null;
    }

    var normalized = normalizeNumericString(value);
    if (!normalized) {
      return null;
    }

    var parsed = parseFloat(normalized);
    return isNaN(parsed) ? null : parsed;
  }

  function getPhaseKey(semanalConfirmada) {
    return parseInt(semanalConfirmada, 10) === 1 ? 'calificacion' : 'programacion';
  }

  function hasCncComplete(rowData) {
    if (!rowData) {
      return false;
    }

    return !isBlank(rowData.Categoria_CNC) && !isBlank(rowData.CNC);
  }

  function isActiveRow(rowData) {
    if (!rowData) {
      return false;
    }

    var activa = isBlank(rowData.Activa) ? '' : String(rowData.Activa).trim().toUpperCase();
    if (!activa || activa === 'NA' || activa === '0' || activa === 'N' || activa === 'NO' || activa === 'FALSE') {
      return false;
    }

    return true;
  }

  function classifyState(rowData, phaseKey) {
    if (!isActiveRow(rowData)) {
      return 'ps-no-activa';
    }

    var ejecutado = toNumberOrNull(rowData.Ejecutado);
    var compromiso = toNumberOrNull(rowData.Compromiso);
    var ejecutadoReal = toNumberOrNull(rowData.Ejecutado_Real);
    var liberacionFlag = toNumberOrNull(rowData.Prog_Sin_Restricciones_100);
    var critica = toNumberOrNull(rowData.Critica);

    var compromisoVacio = compromiso === null || compromiso <= 0;
    var estaIncompleta = ejecutado === null || ejecutado < 0.999;
    var sinLiberacion = liberacionFlag !== null ? liberacionFlag > 0 : false;
    var isCriticalRoute = critica !== null && critica >= 1;
    var subcontratistaVacio = isBlank(rowData.Sub_Contratista);
    var responsableVacio = isBlank(rowData.Responsable_AIA);
    var faltanResponsables = subcontratistaVacio || responsableVacio;

    if (phaseKey === 'programacion') {
      if (!estaIncompleta) {
        return 'ps-no-activa';
      }

      if (!compromisoVacio && !faltanResponsables) {
        return 'prog-lista-para-confirmar';
      }

      if (compromisoVacio && sinLiberacion && isCriticalRoute) {
        return 'prog-bloqueo-critico-sin-compromiso';
      }

      return 'prog-sin-compromiso';
    }

    if (compromisoVacio || ejecutadoReal === null) {
      return 'cal-sin-calificar';
    }

    if ((ejecutadoReal + 0.0001) < compromiso) {
      return isCriticalRoute
        ? 'cal-incumplida-critica'
        : 'cal-incumplida';
    }

    return 'cal-cumplida-control';
  }

  function requiresCnc(compromiso, ejecutadoReal) {
    var compromisoNum = toNumberOrNull(compromiso);
    var ejecutadoRealNum = toNumberOrNull(ejecutadoReal);

    if (compromisoNum === null || ejecutadoRealNum === null || compromisoNum <= 0) {
      return false;
    }

    return (ejecutadoRealNum + 0.0001) < compromisoNum;
  }

  global.PSStateMachine = {
    getPhaseKey: getPhaseKey,
    toNumberOrNull: toNumberOrNull,
    isBlank: isBlank,
    hasCncComplete: hasCncComplete,
    classifyState: classifyState,
    requiresCnc: requiresCnc,
  };
})(window);
