document.getElementById('ventanasModalesSemana').innerHTML =
  "<!-- Se crea el Modal que contiene el formulario para crear una nueva semana en el proyecto --><div class='modal_nueva_sem modal fade' id='modal_nueva_sem' tabindex='-1' role='dialog' aria-labelledby='modal_nueva_semLabel'><div class='modal-dialog' role='document'><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modal_nueva_semLabel'>Crear Semana LPS</h4><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div><div class='modal-body'><div class='row'><div id='cuadroModal' class='cuadroModal col-sm-12 col-md-12 col-lg-12 '><form class='form form-horizontal' action='' method='POST'><input type='hidden' id='Id' name='Id' value='0'><input type='hidden' id='opcion' name='opcion' value='registrar'><div class='form-group' style='width:100%;'><label for='inicio_sem' class='col-sm-12 control-label'>Seleccione Fecha de Inicio de la Semana</label><div class='col-sm-6'><input id='inicio_sem' name='inicio_sem' class='form-control' type='text' readonly></div></div><!--Se crean los botones Guardar y Listar--><div class='form-group'><div class='col-sm-offset-2 col-sm-8'><input id='btn_guardar_nueva_sem' type='button' class='btn btn-primary' data-dismiss='modal' value='Guardar'><input id='btn_cancelar' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar' ></div></div></form></div></div></div><!-- <div class='modal-footer'><button type='button' id='eliminar-usuario' class='btn btn-primary' data-dismiss='modal' >Aceptar</button><button type='button' class='btn btn-default' data-dismiss='modal'>Cancelar</button></div> --></div></div></div><!-- Modal --><!-- Se crea el Modal que solicita la confirmación de eliminar una semana del proyecto --><div class='modal_eliminar_sem modal fade' id='modal_eliminar_sem' tabindex='-1' role='dialog' aria-labelledby='modal_eliminar_semLabel'><div class='modal-dialog' role='document'><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modal_eliminar_semLabel'>Eliminar Semana LPS</h4><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div><div class='modal-body'><div class='row'><div id='cuadro5' class='cuadro5 col-sm-12 col-md-12 col-lg-12 '><form class='form form-horizontal' action='' method='POST'><p class='modal-eliminar-semana-body-texto' id='modal-eliminar-semana-body-texto'></p><!--Se crean los botones Guardar y Listar--><div class='form-group'><div class='col-sm-offset-2 col-sm-8'><input id='btn_eliminar_sem' type='button' class='btn btn-primary' data-dismiss='modal' value='Eliminar'><input id='btn_cancelar_semana' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar' ></div></div></form></div></div></div></div></div></div><!-- Modal --><!-- Se crea el modal que explica la leyenda de colores de las tablas --><div class='modal fade' id='modal_leyenda_colores' role='dialog' data-backdrop='static'><div class='modal-dialog modal-lg'><!-- Modal content--><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modal_leyenda_colores_Label'>Leyenda de Colores de Las Actividades</h4><button type='button' class='close' data-dismiss='modal'>&times;</button></div><div class='modal-body' style='margin:auto'><img src='/img/Leyenda_Actividades.png' class='d-inline-block align-top' style='margin:auto; width:100%; max-width:800px' alt=''></div><div class='modal-footer'><button type='button' class='btn btn-default btn-primary' data-dismiss='modal'>Close</button></div></div></div></div><!-- Modal --><!-- Se crea el Modal que coloca el spinner cuando se está ejecutando un código que se demora varios segundos --><div class='modal fade' id='modal_spinner' role='dialog'><div class='modal-dialog modal-sm modal-dialog-centered'><!-- Modal content--><div class='spinner-border' style='width: 300px; height: 300px; color: white' role='status'><span class='sr-only'>Cargando...</span></div></div></div></div>";

(function normalizeAiaGeneratedModals() {
  var container = document.getElementById('ventanasModalesSemana');
  if (!container) {
    return;
  }

  if (container.dataset.skipLegacyLegend === 'true') {
    var legacyLegend = container.querySelector('#modal_leyenda_colores');
    if (legacyLegend) {
      legacyLegend.remove();
    }
  }

  Array.prototype.forEach.call(container.querySelectorAll('.modal'), function (modal) {
    modal.classList.add('aia-modal');
    var dialog = modal.querySelector('.modal-dialog');
    if (dialog) {
      dialog.classList.add('modal-dialog-centered');
    }
  });

  var spinner = document.getElementById('modal_spinner');
  if (!spinner) {
    return;
  }

  spinner.classList.add('aia-modal');
  spinner.setAttribute('role', 'dialog');
  spinner.setAttribute('aria-live', 'polite');
  spinner.setAttribute('aria-label', 'Procesando');
  spinner.innerHTML = "<div class='modal-dialog modal-sm modal-dialog-centered' role='document'><div class='modal-content'><div class='modal-body text-center'><div class='spinner-border text-success' role='status' aria-hidden='true'></div><p class='aia-modal__message mt-3 mb-0'>Procesando...</p></div></div></div>";
})();

function aiaNoticeText(message, title) {
  return title ? title + '\n\n' + (message || '') : (message || '');
}

function aiaNoticeInvoke(method, message, title) {
  if (window.AIA && window.AIA.Notice && typeof window.AIA.Notice[method] === 'function') {
    return window.AIA.Notice[method](message, title);
  }

  if (typeof window.alert === 'function') {
    window.alert(aiaNoticeText(message, title));
  }

  return Promise.resolve();
}

var nueva_sem = function (db, carpeta, seccion) {

  var semanaActual = document.getElementById('semana').value;
  var permisoCanon = document.getElementById('permiso_canonico') ? document.getElementById('permiso_canonico').value : '';
  var esAdmin = (permisoCanon === 'A');
  $('#btn_guardar_nueva_sem').off('click').on('click', function () {
    $('#btn_guardar_nueva_sem').prop('disabled', true);
    $('#modal_spinner').modal('show');
    var f_inicio_sem = $('#inicio_sem').val(),
      opcion = 'nueva_sem';
    var url = '/legacy/funciones_generales/php/nueva_semana.php?db=' + db;
    $.ajax({
      method: 'POST',
      url: '/legacy/funciones_generales/php/verificarCICActualizada.php',
      dataType: 'json',
      data: { db: db, semana: semanaActual },
    }).done(function (info) {
      var faltaCalificar = info;
      if (faltaCalificar != 0) {
        aiaNoticeInvoke(
          'warning',
          'No se pueden crear nuevas semanas hasta que se realicen las Calificaciones Integrales (Calidad, Gestión Social - Ambiental, SST y Administración) ' +
            faltaCalificar +
            ', las cuales se deben realizar como mínimo cada 2 meses.',
          'Calificación Pendiente'
        ).then(() => {
          location.assign('/legacy/cambiar_pagina.php?seccion=CIC&semana=' + semanaActual);
        });
      } else {
        $.ajax({
          method: 'POST',
          url: url,
          dataType: 'json',
          data: { f_inicio_sem: f_inicio_sem, opcion: opcion, _csrf_token: window.__lpsWeekCsrf || '' },
        }).done(function (info) {
          var json_info = info;
          if (json_info && json_info.respuesta === 'ERROR') {
            $('#modal_spinner').modal('hide');
            aiaNoticeInvoke('error', json_info.mensaje).then(() => {
              location.reload();
            });
            return;
          }
          var semana = json_info[0];
          var pdcConteo = json_info[1];
          var semanalConfirmada = json_info[3];
          
          if (semanalConfirmada == 0 && Number(semana) > 0 && !esAdmin) {
            aiaNoticeInvoke(
              'warning',
              'No se puede crear la Semana ' +
                (Number(semana) + 1) +
                ' hasta que se confirmen los compromisos en la Semana ' +
                semana,
              'Semana Bloqueada'
            ).then(() => {
              location.assign(
                '/legacy/cambiar_pagina.php?seccion=programacion_semanal&semana=' + semana
              );
            });
          } else {
              location.assign(
                '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
              );
          }
        }).fail(function (xhr, status, error) {
          $('#modal_spinner').modal('hide');
          var msg = 'Error al crear la semana';
          try {
            var errJson = xhr.responseJSON || JSON.parse(xhr.responseText);
            msg = errJson.mensaje || msg;
          } catch (e) {}
          aiaNoticeInvoke('error', msg, 'Error');
        }).always(function() {
          $('#btn_guardar_nueva_sem').prop('disabled', false);
        });
      }
    }).fail(function (xhr, status, error) {
      $('#modal_spinner').modal('hide');
      $('#btn_guardar_nueva_sem').prop('disabled', false);
      aiaNoticeInvoke('error', 'Error al verificar Calificaciones CIC: ' + (error || status));
    });
  });
};

var eliminar_sem = function (semana, db, carpeta, seccion) {
  $('#modal-eliminar-semana-body-texto').text('¿Desea Eliminar la Semana ' + semana + '?');
  $('#btn_eliminar_sem').off('click').on('click', function () {
    $('#modal_spinner').modal('show');
    var semanaFinal = semana - 1,
      opcion = 'eliminar_sem';
    var url = '/legacy/funciones_generales/php/eliminar_semana.php?db=' + db;
    $.ajax({
      method: 'POST',
      url: url,
      contenttype: 'charset=utf-8',
      data: { semana: semana, opcion: opcion, _csrf_token: window.__lpsWeekCsrf || '' },
    })
      .done(function (info) {
        var json_info = typeof info === 'string' ? JSON.parse(info) : info;
        if (json_info['puedeEliminar'] == 'SI') {
          location.assign(
            '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semanaFinal
          );
        } else {
          aiaNoticeInvoke(
            'warning',
            'No se puede eliminar una semana menor a la máxima del proyecto (Semana ' +
              json_info['maxSemana'] +
              ')',
            'Acción No Permitida'
          ).then(function () {
            location.reload();
          });
        }
      })
      .fail(function (xhr, status, error) {
        var msg = 'Error al eliminar la semana';
        try {
          var errJson = JSON.parse(xhr.responseText);
          msg = errJson.mensaje || msg;
        } catch (e) {}
        aiaNoticeInvoke('error', msg, 'Error');
      })
      .always(function () {
        $('#modal_spinner').modal('hide');
      });
  });
};

var fechaNuevaSemana = function () {
  var fechaFinRaw = document.getElementById('Fecha_Fin_SemYMD').value;
  var dpOptions = {
    dateFormat: 'yy-mm-dd',
    changeMonth: true,
    changeYear: true,
    showOtherMonths: true,
    selectOtherMonths: true,
  };
  if (fechaFinRaw && fechaFinRaw.trim() !== '') {
    var dia = new Date(fechaFinRaw);
    dia.setDate(dia.getDate() + 1);
    var mes = ('0' + (dia.getMonth() + 1)).slice(-2);
    var d = ('0' + dia.getDate()).slice(-2);
    $('#inicio_sem').val(dia.getFullYear() + '-' + mes + '-' + d);
    dpOptions.defaultDate = dia;
  }
  $('#inicio_sem').datepicker(dpOptions);
};

