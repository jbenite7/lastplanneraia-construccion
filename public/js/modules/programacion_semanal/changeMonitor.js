(function (window, $) {
  'use strict';

  var autoProgramLog = [];
  var isRunning = false;
  var hasRunOnce = false;

  function getDb() {
    return document.getElementById('baseDatos_PHP')?.value || '';
  }

  function getSemana() {
    return parseInt(document.getElementById('semana_PHP')?.value || '0', 10);
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

  function openModal() {
    var $modal = $('#modal_change_monitor');
    if ($modal.length === 0) return;

    var $tbody = $('#cm-table-body');
    $tbody.html('<tr><td colspan="5" class="cm-empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando registro...</td></tr>');

    fetchLog();
    $modal.modal('show');
  }

  function renderTable() {
    var $tbody = $('#cm-table-body');
    $tbody.empty();

    var $headerTotal = $('#cm-count-total-header');
    if ($headerTotal.length) $headerTotal.text(autoProgramLog.length);

    if (autoProgramLog.length === 0) {
      $tbody.html('<tr><td colspan="5" class="cm-empty-state">No hay actividades auto-gestionadas en esta semana.</td></tr>');
      return;
    }

    autoProgramLog.forEach(function (entry) {
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

      var $tr = $('<tr>', { 'class': 'cm-row cm-row-' + entry.accion });
      $tr.append($('<td>', { 'data-label': 'ID' }).text(entry.actividad_id || '-'));
      $tr.append($('<td>', { 'class': 'cm-activity-cell', 'data-label': 'Actividad' }).html(entry.actividad_nombre || '-'));
      $tr.append($('<td>', { 'data-label': 'Acción' }).html('<span class="badge ' + accionCls + '">' + accionLabel + '</span>'));
      $tr.append($('<td>', { 'class': 'cm-detail-cell', 'data-label': 'Detalle' }).text(detalle));
      $tr.append($('<td>', { 'data-label': 'Fecha' }).text(fecha));
      $tbody.append($tr);
    });
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
