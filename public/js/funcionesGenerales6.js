document.getElementById('ventanasModalesSemana').innerHTML =
  "<!-- Se crea el Modal que contiene el formulario para crear una nueva semana en el proyecto --><div class='modal_nueva_sem modal fade' id='modal_nueva_sem' tabindex='-1' role='dialog' aria-labelledby='modal_nueva_semLabel'><div class='modal-dialog' role='document'><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modalEliminarLabel'>Crear Semana LPS</h4><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div><div class='modal-body'><div class='row'><div id='cuadroModal' class='cuadroModal col-sm-12 col-md-12 col-lg-12 '><form class='form form-horizontal' action='' method='POST'><input type='hidden' id='Id' name='Id' value='0'><input type='hidden' id='opcion' name='opcion' value='registrar'><div class='form-group' style='width:100%;'><label for='inicio_sem' class='col-sm-12 control-label'>Seleccione Fecha de Inicio de la Semana</label><div class='col-sm-6'><input id='inicio_sem' name='inicio_sem' class='form-control' type='text' readonly></div></div><!--Se crean los botones Guardar y Listar--><div class='form-group'><div class='col-sm-offset-2 col-sm-8'><input id='btn_guardar_nueva_sem' type='button' class='btn btn-primary' data-dismiss='modal' value='Guardar'><input id='btn_cancelar' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar' ></div></div></form></div></div></div><!-- <div class='modal-footer'><button type='button' id='eliminar-usuario' class='btn btn-primary' data-dismiss='modal' >Aceptar</button><button type='button' class='btn btn-default' data-dismiss='modal'>Cancelar</button></div> --></div></div></div><!-- Modal --><!-- Se crea el Modal que solicita la confirmación de eliminar una semana del proyecto --><div class='modal_eliminar_sem modal fade' id='modal_eliminar_sem' tabindex='-1' role='dialog' aria-labelledby='modal_nueva_semLabel'><div class='modal-dialog' role='document'><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modalEliminarLabel'>Eliminar Semana LPS</h4><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div><div class='modal-body'><div class='row'><div id='cuadro5' class='cuadro5 col-sm-12 col-md-12 col-lg-12 '><form class='form form-horizontal' action='' method='POST'><p class='modal-eliminar-semana-body-texto' id='modal-eliminar-semana-body-texto'></p><!--Se crean los botones Guardar y Listar--><div class='form-group'><div class='col-sm-offset-2 col-sm-8'><input id='btn_eliminar_sem' type='button' class='btn btn-primary' data-dismiss='modal' value='Eliminar'><input id='btn_cancelar_semana' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar' ></div></div></form></div></div></div></div></div></div><!-- Modal --><!-- Se crea el modal que explica la leyenda de colores de las tablas --><div class='modal fade' id='modal_leyenda_colores' role='dialog' data-backdrop='static'><div class='modal-dialog modal-lg'><!-- Modal content--><div class='modal-content'><div class='modal-header'><h4 class='modal-title' id='modal_leyenda_colores_Label'>Leyenda de Colores de Las Actividades</h4><button type='button' class='close' data-dismiss='modal'>&times;</button></div><div class='modal-body' style='margin:auto'><img src='/img/Leyenda_Actividades.png' class='d-inline-block align-top' style='margin:auto; width:100%; max-width:800px' alt=''></div><div class='modal-footer'><button type='button' class='btn btn-default btn-primary' data-dismiss='modal'>Close</button></div></div></div></div><!-- Modal --><!-- Se crea el Modal que coloca el spinner cuando se está ejecutando un código que se demora varios segundos --><div class='modal fade' id='modal_spinner' role='dialog'><div class='modal-dialog modal-sm modal-dialog-centered'><!-- Modal content--><div class='spinner-border' style='width: 300px; height: 300px; color: white' role='status'><span class='sr-only'>Cargando...</span></div></div></div></div>";

var nueva_sem = function (db, carpeta, seccion) {
  var semanaActual = document.getElementById('semana').value;
  var permiso = document.getElementById('permiso').value;
  console.log(permiso);
  $('#btn_guardar_nueva_sem').off('click').on('click', function () {
    $('#btn_guardar_nueva_sem').prop('disabled', true);
    $('#modal_spinner').modal('show');
    var f_inicio_sem = $('#inicio_sem').val(),
      opcion = 'nueva_sem';
    if (carpeta == 1) {
      var url = '/legacy/funciones_generales/php/nueva_semana.php?db=' + db;
    } else {
      var url = '/legacy/funciones_generales/php/nueva_semana.php?db=' + db;
    }
    $.ajax({
      method: 'POST',
      url: '/legacy/funciones_generales/php/verificarCICActualizada.php?',
      contenttype: 'charset=utf-8',
      data: { db: db, semana: semanaActual },
    }).done(function (info) {
      var faltaCalificar = typeof info === 'string' ? JSON.parse(info) : info;
      if (faltaCalificar != 0) {
        window.alert(
          'No se pueden crear nuevas semanas hasta que se realicen las Calificaciónes Integrales (Calidad, Gestión Social - Ambiental, SST y Administración)' +
            faltaCalificar +
            ', las cuales se deben realizar mínimo cada 2 meses.'
        );
        location.assign('/legacy/cambiar_pagina.php?seccion=CIC&semana=' + semanaActual);
      } else {
        $.ajax({
          method: 'POST',
          url: url,
          contenttype: 'charset=utf-8',
          data: { f_inicio_sem: f_inicio_sem, opcion: opcion, permiso: permiso },
        }).done(function (info) {
          var json_info = typeof info === 'string' ? JSON.parse(info) : info;
          var semana = json_info[0];
          var pdcConteo = json_info[1];
          var semanalConfirmada = json_info[3];
          // console.log(semanalConfirmada);
          if (semanalConfirmada == 0 && permiso != 'P') {
            window.alert(
              'No se pueden crear la Semana ' +
                (Number(semana) + 1) +
                ' hasta que se confirmen los compromisos en la Semana ' +
                semana
            );
            location.assign(
              '/legacy/cambiar_pagina.php?seccion=programacion_semanal&semana=' + semana
            );
          } else {
            if (pdcConteo == 0) {
              if (carpeta == 1) {
                location.assign(
                  '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                );
              } else {
                location.assign(
                  '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                );
              }
            } else {
              if (semana > 1) {
                $.ajax({
                  method: 'POST',
                  url: '/legacy/pdc/actualizar_pdc.php',
                  contenttype: 'charset=utf-8',
                  data: { db: db, semana: semana },
                }).done(function (info) {
                  var json_info = typeof info === 'string' ? JSON.parse(info) : info;
                  if (carpeta == 1) {
                    location.assign(
                      '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                    );
                  } else {
                    location.assign(
                      '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                    );
                  }
                });
              } else {
                if (carpeta == 1) {
                  location.assign(
                    '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                  );
                } else {
                  location.assign(
                    '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semana
                  );
                }
              }
            }
          }
        }).always(function() {
          $('#btn_guardar_nueva_sem').prop('disabled', false);
        });
      }
    });
  });
};

var eliminar_sem = function (semana, db, carpeta, seccion) {
  $('#modal-eliminar-semana-body-texto').text('¿Desea Eliminar la Semana ' + semana + '?');
  $('#btn_eliminar_sem').on('click', function () {
    $('#modal_spinner').modal('show');
    var semanaFinal = semana - 1,
      opcion = 'eliminar_sem';
    if (carpeta == 1) {
      var url = '/legacy/funciones_generales/php/eliminar_semana.php?db=' + db;
    } else {
      var url = '/legacy/funciones_generales/php/eliminar_semana.php?db=' + db;
    }
    $.ajax({
      method: 'POST',
      url: url,
      contenttype: 'charset=utf-8',
      data: { semana: semana, opcion: opcion },
    }).done(function (info) {
      var json_info = typeof info === 'string' ? JSON.parse(info) : info;
      if (json_info['puedeEliminar'] == 'SI') {
        if (carpeta == 1) {
          location.assign(
            '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semanaFinal
          );
        } else {
          location.assign(
            '/legacy/cambiar_pagina.php?seccion=programa_general&semana=' + semanaFinal
          );
        }
      } else {
        window.alert(
          'No se puede eliminar una semana menor a la máxima del proyecto (Semana ' +
            json_info['maxSemana'] +
            ')'
        );
        location.reload();
      }
    });
  });
};

var fechaNuevaSemana = function () {
  //console.log(document.getElementById('Fecha_Fin_SemYMD').value);
  var dia = new Date("'" + document.getElementById('Fecha_Fin_SemYMD').value + "''");
  dia.setDate(dia.getDate() + 1);
  $('#inicio_sem').val(dia.getFullYear() + '-' + (dia.getMonth() + 1) + '-' + dia.getDate());
  $('#inicio_sem').datepicker({
    dateFormat: 'yy-mm-dd',
    changeMonth: true,
    changeYear: true,
    showOtherMonths: true,
    selectOtherMonths: true,
    defaultDate: dia,
  });
};

var activarBuscador = function (tbody, table) {
  $('#input_buscador').on('keyup', function (e) {
    table.search(this.value).draw();
    if ($('#input_buscador').val() != '') {
      $('#btn_limpiar_buscador').show();
      $('#btn_actualizar_buscador').hide();
    } else {
      $('#btn_limpiar_buscador').hide();
      $('#btn_actualizar_buscador').hide();
    }
  });

  limpiar_buscador('#dt_cliente tbody', table);

  if ($('#input_buscador').val() != '') {
    table.search($('#input_buscador').val()).draw();
  }
};

var limpiar_buscador = function (tbody, table) {
  $('#btn_limpiar_buscador').one('click', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    //table.search( this.value ).draw();
    //$("#input_buscador").val('');
    var buscadorTabla = $('#input_buscador').val();
    $('#dt_cliente').empty();

    $.ajax({
      method: 'POST',
      url: '/legacy/funciones_generales/php/buscadorTabla.php',
      contenttype: 'charset=utf-8',
      data: { buscadorTabla: buscadorTabla },
    }).done(function (info) {
      var json_info = typeof info === 'string' ? JSON.parse(info) : info;
      console.log(json_info);
      if (json_info != '') {
        $('#btn_limpiar_buscador').show();
        $('#btn_actualizar_buscador').hide();
      } else {
        $('#btn_limpiar_buscador').hide();
        $('#btn_actualizar_buscador').show();
      }
      recargarTabla('listar');
    });
  });
};
