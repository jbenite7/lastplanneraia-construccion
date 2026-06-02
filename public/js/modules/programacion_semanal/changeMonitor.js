(function (window, $) {
  'use strict';

  var autoProgramLog = [];
  var isRunning = false;
  var hasRunOnce = false;
  var cmCurrentFilter = null;

  function getDb() {
    return document.getElementById('baseDatos_PHP')?.value || '';
  }

  function getSemana() {
    return parseInt(document.getElementById('semana_PHP')?.value || '0', 10);
  }

  function isRestrictionEntry(entry) {
    return (entry.accion === 'descomprometer' || entry.accion === 'insert_cnp')
      && entry.categoria_cnp === 'Programación';
  }

  function countCnpGenericas(log) {
    if (!Array.isArray(log)) return 0;
    return log.filter(isRestrictionEntry).length;
  }

  function applyFilter(log, filter) {
    if (!Array.isArray(log)) return [];
    if (filter === 'restricciones') {
      return log.filter(isRestrictionEntry);
    }
    return log;
  }

  function run(force) {
    if (isRunning || (hasRunOnce && !force)) return;
    isRunning = true;

    var db = getDb();
    var semana = getSemana();
    if (!db || semana <= 0) {
      isRunning = false;
      return;
    }

    $.ajax({
      url: '/api/semanal/auto-program?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      method: 'POST',
      data: { db: db, semana: semana },
      dataType: 'json',
      success: function (resp) {
        isRunning = false;
        hasRunOnce = true;
        if (resp && resp.success) {
          autoProgramLog = resp.log || [];
          updateBadge();
          renderTable();
          notifyRestrictionCnp();
          if (autoProgramLog.length > 0 && window.PSHotModule && typeof window.PSHotModule.reload === 'function') {
            window.PSHotModule.reload();
          }
        }
      },
      error: function () {
        isRunning = false;
        hasRunOnce = true;
      },
    });
  }

  function notifyRestrictionCnp() {
    var count = countCnpGenericas(autoProgramLog);
    if (count <= 0) {
      return;
    }
    if (!window.AIA || !window.AIA.Notice || typeof window.AIA.Notice.dialog !== 'function') {
      return;
    }

    var semana = getSemana();
    var cnpUrl = '/legacy/cambiar_pagina.php?seccion=CNP&semana=' + encodeURIComponent(semana);
    var html = count + ' actividad(es) no se pueden programar por restricciones habilitantes sin cumplir. '
      + '<a href="' + cnpUrl + '" class="cm-cnp-link">Revisa el módulo CNP</a>.';

    window.AIA.Notice.dialog({
      icon: 'warning',
      title: 'Restricciones pendientes',
      html: html,
      showDenyButton: true,
      denyButtonText: '<i class="fas fa-list"></i> Ver Actividades no programadas',
      confirmButtonText: 'Ir a Programar la semana',
      customClass: {
        popup: 'aia-glass-popup',
        title: 'swal2-title',
        htmlContainer: 'swal2-html-container',
        confirmButton: 'aia-glass-confirm-btn',
        denyButton: 'aia-glass-deny-btn'
      },
      buttonsStyling: false,
      allowOutsideClick: false
    }).then(function (result) {
      if (!result) return;
      if (result.isDenied) {
        openModal('restricciones');
      } else if (result.isConfirmed) {
        if (window.PSHotModule && typeof window.PSHotModule.reload === 'function') {
          window.PSHotModule.reload();
        } else {
          window.location.reload();
        }
      }
    });
  }

  function fetchLog() {
    var db = getDb();
    var semana = getSemana();
    if (!db || semana <= 0) return;

    $.ajax({
      url: '/api/semanal/auto-program-log?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      method: 'GET',
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.success) {
          autoProgramLog = resp.log || [];
          updateBadge();
          renderTable();
        }
      },
    });
  }

  function updateBadge() {
    var count = autoProgramLog.length;
    var $badge = $('#cm-badge');

    if ($badge.length === 0) {
      var $btn = $(
        '<button type="button" id="cm-btn-badge" class="btn-pdc-modern cm-badge-btn" title="Actividades auto-gestionadas" aria-label="Ver actividades auto-gestionadas">' +
          '<i class="fas fa-robot"></i> <span>Auto-gestionadas</span> ' +
          '<span id="cm-badge" class="badge badge-danger cm-badge-count">0</span>' +
        '</button>'
      );
      $btn.on('click', function () {
        openModal();
      });
      $btn.insertAfter('#btn_autoprogramar');
    }

    $('#cm-badge').text(count);

    if (count > 0) {
      $('#cm-btn-badge').show();
    } else {
      $('#cm-btn-badge').hide();
    }
  }

  function openModal(filter) {
    var $modal = $('#modal_change_monitor');
    if ($modal.length === 0) return;

    cmCurrentFilter = filter || null;
    ensureFilterToggle();
    ensureFilterIndicator();
    ensureRestrictionsCounter();

    var $tbody = $('#cm-table-body');
    $tbody.html('<tr><td colspan="6" class="cm-empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando registro...</td></tr>');

    fetchLog();
    $modal.modal('show');
  }

  function ensureFilterToggle() {
    var $modal = $('#modal_change_monitor');
    if ($modal.length === 0) return;
    var $header = $('#modal_change_monitor .cm-modal-header').first();
    if ($header.length === 0) return;

    if ($('#cm-filter-toggle').length === 0) {
      var $toggle = $(
        '<label class="cm-filter-toggle">' +
          '<input type="checkbox" id="cm-filter-toggle"> ' +
          '<i class="fas fa-filter"></i> Solo restricciones' +
        '</label>'
      );
      $toggle.find('input').on('change', function () {
        cmCurrentFilter = this.checked ? 'restricciones' : null;
        renderTable();
      });
      $header.append($toggle);
    }

    $('#cm-filter-toggle').prop('checked', cmCurrentFilter === 'restricciones');
  }

  function ensureFilterIndicator() {
    var $toolbar = $('#modal_change_monitor .cm-toolbar').first();
    if ($toolbar.length === 0) return;
    if ($('#cm-filter-indicator').length === 0) {
      $toolbar.append('<div id="cm-filter-indicator" style="display:none"></div>');
    }
  }

  function ensureRestrictionsCounter() {
    var $titleRow = $('#modal_change_monitor .cm-title-row').first();
    if ($titleRow.length === 0) return;
    if ($('#cm-count-restricciones-header').length === 0) {
      var $badge = $('<span class="cm-total-badge cm-restricciones-badge" id="cm-count-restricciones-header" title="Actividades con restricciones pendientes">0</span>');
      $titleRow.append($badge);
    }
  }

  function classifyEntry(entry) {
    if (entry.accion === 'comprometer') {
      return { tipo: 'reactivacion', label: 'Reactivación', icon: 'fa-undo', cls: 'badge-info' };
    }
    if (entry.accion === 'descomprometer' || entry.accion === 'insert_cnp') {
      if (entry.categoria_cnp === 'Programación') {
        return { tipo: 'restricciones', label: 'Restricciones pendientes', icon: 'fa-lock', cls: 'badge-warning' };
      }
      return { tipo: 'estado', label: 'Desprogramación', icon: 'fa-times-circle', cls: 'badge-danger' };
    }
    return { tipo: 'otro', label: entry.accion, icon: 'fa-circle', cls: 'badge-secondary' };
  }

  function renderTable() {
    var $tbody = $('#cm-table-body');
    $tbody.empty();

    var semana = getSemana();
    var filteredLog = applyFilter(autoProgramLog, cmCurrentFilter);
    var total = autoProgramLog.length;
    var restriccionesCount = countCnpGenericas(autoProgramLog);
    var showing = filteredLog.length;

    var $headerTotal = $('#cm-count-total-header');
    if ($headerTotal.length) $headerTotal.text(total);

    var $headerRestricciones = $('#cm-count-restricciones-header');
    if ($headerRestricciones.length) $headerRestricciones.text(restriccionesCount);

    var $indicator = $('#cm-filter-indicator');
    if ($indicator.length) {
      if (cmCurrentFilter === 'restricciones' && showing < total) {
        $indicator.html('<i class="fas fa-filter"></i> Mostrando ' + showing + ' de ' + total + ' (filtrado por restricciones pendientes)').show();
      } else {
        $indicator.empty().hide();
      }
    }

    if (total === 0) {
      $tbody.html('<tr><td colspan="6" class="cm-empty-state">No hay actividades auto-gestionadas en esta semana.</td></tr>');
      return;
    }

    if (showing === 0) {
      $tbody.html('<tr><td colspan="6" class="cm-empty-state">No hay actividades que coincidan con el filtro activo.</td></tr>');
      return;
    }

    filteredLog.forEach(function (entry) {
      var info = classifyEntry(entry);

      var accionLabel = '';
      var accionCls = '';
      switch (entry.accion) {
        case 'comprometer':
          accionLabel = 'Comprometida';
          accionCls = 'badge-success';
          break;
        case 'descomprometer':
          accionLabel = 'Desprogramada';
          accionCls = 'badge-danger';
          break;
        case 'insert_cnp':
          accionLabel = 'Insertada con CNP';
          accionCls = 'badge-warning';
          break;
        default:
          accionLabel = entry.accion;
          accionCls = 'badge-secondary';
      }

      var detalle = entry.detalle || '';
      if (entry.categoria_cnp || entry.cnp) {
        detalle += ' [CNP: ' + (entry.categoria_cnp || '') + ' — ' + (entry.cnp || '') + ']';
      }

      var fecha = '';
      if (entry.creado_en) {
        var normalized = String(entry.creado_en).trim().replace(' ', 'T');
        if (normalized.length === 10) {
          normalized += 'T00:00:00';
        }
        var d = new Date(normalized + '-05:00');
        if (!isNaN(d.getTime())) {
          fecha = d.toLocaleString('es-CO', { timeZone: 'America/Bogota' });
        } else {
          fecha = String(entry.creado_en);
        }
      }

      var rowCls = 'cm-row cm-row-' + entry.accion;
      if (info.tipo === 'restricciones') {
        rowCls += ' cm-row-restricciones';
      }

      var $tr = $('<tr>', { 'class': rowCls });
      $tr.append($('<td>', { 'data-label': 'ID' }).text(entry.actividad_id || '-'));
      $tr.append($('<td>', { 'class': 'cm-activity-cell', 'data-label': 'Actividad' }).html(entry.actividad_nombre || '-'));

      var $tipo = $('<td>', { 'data-label': 'Tipo' });
      $tipo.html('<span class="badge ' + info.cls + '"><i class="fas ' + info.icon + '"></i> ' + info.label + '</span>');
      $tr.append($tipo);

      $tr.append($('<td>', { 'data-label': 'Acción' }).html('<span class="badge ' + accionCls + '">' + accionLabel + '</span>'));
      $tr.append($('<td>', { 'class': 'cm-detail-cell', 'data-label': 'Detalle' }).text(detalle));
      $tr.append($('<td>', { 'data-label': 'Fecha' }).text(fecha));
      $tbody.append($tr);
    });

    if (restriccionesCount > 0 && semana > 0) {
      var cnpUrl = '/legacy/cambiar_pagina.php?seccion=CNP&semana=' + encodeURIComponent(semana);
      var $footerCta = $('#cm-footer-cta');
      if ($footerCta.length === 0) {
        $footerCta = $(
          '<div id="cm-footer-cta" class="cm-footer-cta">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            restriccionesCount + ' actividad(es) requieren atención en el módulo CNP. ' +
            '<a href="' + cnpUrl + '" class="btn btn-sm btn-warning cm-cta-btn">' +
              '<i class="fas fa-external-link-alt"></i> Ir al módulo CNP' +
            '</a>' +
          '</div>'
        );
        var $modalBody = $('#modal_change_monitor .modal-body, #modal_change_monitor .aia-modal__body');
        if ($modalBody.length) {
          $modalBody.append($footerCta);
        }
      } else {
        $footerCta.html(
          '<i class="fas fa-exclamation-triangle"></i> ' +
          restriccionesCount + ' actividad(es) requieren atención en el módulo CNP. ' +
          '<a href="' + cnpUrl + '" class="btn btn-sm btn-warning cm-cta-btn">' +
            '<i class="fas fa-external-link-alt"></i> Ir al módulo CNP' +
          '</a>'
        );
      }
    } else {
      $('#cm-footer-cta').remove();
    }
  }

  function init() {
    // run() se ejecuta desde afterDataLoaded() en hot.js
  }

  $(document).ready(function () {
    $(document).on('click', '#cm-btn-refresh', function () {
      fetchLog();
      renderTable();
    });
  });

  window.ChangeMonitor = {
    init: init,
    run: run,
    checkAndShow: function () {
      run(true);
    },
    openModal: openModal,
    fetchLog: fetchLog,
  };
})(window, jQuery);
