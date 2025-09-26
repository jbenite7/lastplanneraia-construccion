<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="CNC">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="">

		<input type="hidden" id="opcion_Reprogramar" name="opcion_Reprogramar" value="">

		<input type="hidden" id="Activa_Reprogramar" name="Activa_Reprogramar" value="" readonly>
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_CNP table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Actividad</th>
						<th>Descripción</th>
						<th>Ubicación</th>
						<th>Categoría CNC</th>
						<th>Causa de No Cumplimiento</th>
						<th>Observaciones</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
	</div>

	<!-- Iniciar Jquery-->
	<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<!-- Iniciar Popper-->
	<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
	<!--Iniciar DataTables-->
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
	<!--Botones de Datatables-->
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.bootstrap4.min.js"></script>
	<!--checkboxes DataTables-->
	<script type="text/javascript" src="https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
	<!--Selector de fechas -->
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
	<!--Google Charts-->
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
	<!--Any Chart-->
	<script src="https://cdn.anychart.com/releases/v8/js/anychart-base.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>
	<script src="https://cdn.anychart.com/releases/v8/js/anychart-circular-gauge.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>
	<!-- Lista desplegable con buscador -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script type="text/javascript" src="../funciones_generales/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="../funciones_generales/js/funcionesGenerales6.js" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function() {
		  $("#formulario_nuevo").hide();
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
		});

		var cargaParametros = function() {
			//ocultos();
			listar();
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;


			/*Identificamos la altura de la hoja para determinar la altura de la tabla*/
			var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 180) + "px";

			var table = $("#dt_cliente").DataTable({
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering":false,
				/*                "autoWidth": true,*/
				"fixedHeader": false,
				"scrollX": true,
				//                console.log($(document).height());
				"scrollY": alturatabla,
				/*                "scrollCollapse": false,*/
				"responsive": true,
				"paging": false,
				"ajax": {
					"method": "POST",
					"url":"../programacion_semanal/listar_CNC.php?db="+db+"&semana="+semana
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [

						{
						'targets': [2,3,4,5,6,7,8],
						'render': function ( data, type, full, meta ) {
										return data;
								}
						},

						{
								'targets': [0],
								'className': 'Botones'
						},
						{
								'targets': [6],
								'className': 'input_Categoria_CNC'
						},
						{
								'targets': [7],
								'className': 'input_CNC'
						},
						{
								'targets': [8],
								'className': 'input_Observaciones_CNC'
						}
					],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

				"columns":[
					{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'><i class='fa fa-edit'></i></button>"},
					{"data":"Consecutivo", "visible":false},
					{"data":"Id"},
					{"data":"Actividad"},
					{"data":"Descripcion"},
					{"data":"Ubicacion", "visible":false},
					{"data":"Categoria_CNC",},
					{"data":"CNC"},
					{"data":"Observaciones_CNC"},
				],

			"rowCallback": function( row, data, index ) {
				if(data.Atrasada==1 && data.Critica==1){
						$('td', row).css('background-color', '#7c1c51');
						$('td', row).css('color', '#ffffff');
				} else if(data.Atrasada==1 && data.Critica==0){
						$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
				} else if(data.Critica==1){
					$('td', row).css('background-color', 'rgba(255,150,64,1)');
				} else if(data.Critica==0){
						$('td', row).css('background-color', 'rgba(255,192,51,0.5)');
				}
			},

				"language": idioma_espanol
			});

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:30%;display:inline-block; "><button type= "button" class="leyenda_colores btn btn-secondary btn-sm" data-toggle="modal" data-target="#modal_leyenda_colores" style="margin-right:5px">Leyenda <i class="fas fa-question-circle fa-lg"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><!--<input id="btn_agregar_indicadores" type="button" class="btn btn-primary btn-sm" value="Indicadores Parciales" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modalindicadores" onClick="ind_compromisos_semana(\'\')"><input id="btn_cerrar_compromisos_semana" type="button" class="btn btn-danger btn-sm" value="Confirmar Compromisos" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modal_cerrar_compromisos" onClick="cerrar_compromisos_semana(\'\')"><button id="btn_informe_compromisos" type="button" class="btn btn-warning btn-sm" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modal_informe_compromisos">Imprimir  <i class="fas fa-print fa-lg"></i></button>--><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=programacion_semanal&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=CNP&semana='+semana+'\'">Causas No Programación <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_CNC" type="button" class="btn btn-success btn-sm  active" onclick="window.location.href=\'../cambiar_pagina.php?seccion=CNC&semana='+semana+'\'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm " onclick="window.location.href=\'../cambiar_pagina.php?seccion=CIC&semana='+semana+'\'">Calificación de Proveedores <i class="fas fa-arrow-right fa-m"></i></button><!--<button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=indicadores&semana='+semana+'\'">Indicadores de Last Planner</button>--></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			activarBuscador("#dt_cliente tbody", table);
			maestroPermisos(document.getElementById('permiso').value);
			obtener_data_editar("#dt_cliente tbody", table);
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		  var permiso = document.getElementById('permiso').value;

			if((max_semana-2)>=semana){
				if (permiso=="P"){
					var only_once = true;
				}else{
					var only_once = false;
				}
			}else{
				if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="OT" || permiso=="DCV" || permiso=="V" || permiso=="C"){
					var only_once = false;
				}else{
					var only_once = true;
				}
			}

			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

		  $(tbody).one("click", "td", function() {
		    if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					var Id=$("#Id").val(data.Consecutivo);
					var opcion = $("#opcion").val("modificar");

					var codigo_html_Categoria_CNC = "<select id='select_Categoria_CNC' name='Categoria_CNC' class='form-control form-control-sm'><option value='' selected></option><option value='Rendimiento'>Rendimiento</option><option value='Programación'>Programación</option><option value='Mano de Obra'>Mano de Obra</option><option value='Materiales'>Materiales</option><option value='Equipos'>Equipos</option><option value='Diseños'>Diseños</option><option value='Administrativas'>Administrativas</option><option value='Causas Exógenas'>Causas Exógenas</option></select>";
					$(this).parent().find('.input_Categoria_CNC').html(codigo_html_Categoria_CNC);
					var codigo_html_CNC = "<select id='select_CNC' name='CNC' class='form-control form-control-sm'><option value='' selected></option></select>";
					$(this).parent().find('.input_CNC').html(codigo_html_CNC);
					var codigo_html_Observaciones_CNC = "<textarea id='select_Observaciones_CNC' name='Observaciones_CNC' class='form-control form-control-sm'>'" + data.Observaciones_CNC + "'</textarea>";
					$(this).parent().find('.input_Observaciones_CNC').html(codigo_html_Observaciones_CNC);
					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' style='padding:5px; margin:1px' title='Guardar Causa de No Programación'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' style='padding:5px; margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$(this).parent().find('.Botones').html(codigo_html_botones);
					//$("#select_CNC").val(data.CNC).change();
					$("#select_Categoria_CNC").val(data.Categoria_CNC).change();
					$("#select_Categoria_CNC").focus();
					cnc(data.CNC);
					$('#select_CNC').html("<option value='" + data.CNC + "'>" + data.CNC + "</option>");
					$("#select_Observaciones_CNC").val(data.Observaciones_CNC).change();

					only_once = false;
					$("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
							if(e.keyCode==13){
									$("#btn_guardar_editar").click();
									only_once = true;
							}
					});
					$("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
							if(e.keyCode==27){
									$("#btn_cancelar_editar").click();
									only_once = true;
							}
					});
				}
				cancelarEdicionFila();
				guardar();
				cnc1();
		  });
		}

		var cnc = function(causa) {
		  var categoria = $("#select_Categoria_CNC").val(),
		    opcion = "CNC",
		    CNC = causa;
		  //console.log(CNC);
		  if (categoria === "") {
		    $('#select_CNC').attr('readonly', true);
		    $('#select_CNC').html("<option value=''></option>");
		  } else {
		    $('#select_CNC').attr('readonly', false);
		    $.ajax({
		      method: "POST",
		      url: "../programacion_semanal/guardar_CNP.php?db=login",
		      contenttype: "charset=utf-8",
		      data: {
		        "categoria": categoria,
		        "opcion": opcion
		      },
		      success: function(a) {
		        //console.log(a);
		        $('#select_CNC').html(a);
		        $("#select_CNC option[value='" + CNC + "']").attr('selected', true);
		      }
		    });
		  }
		}

		var cnc1 = function() {
		  $('#select_Categoria_CNC').on('change', function() {
		    var categoria = $("#select_Categoria_CNC").val(),
		      opcion = "CNC";
		    //console.log(CNC);
		    if (categoria === "") {
		      $('#select_CNC').attr('readonly', true);
		      $('#select_CNC').html("<option value=''></option>");
		    } else {
		      $('#select_CNC').attr('readonly', false);
		      $.ajax({
		        method: "POST",
		        url: "../programacion_semanal/guardar_CNP.php?db=login",
		        contenttype: "charset=utf-8",
		        data: {
		          "categoria": categoria,
		          "opcion": opcion
		        },
		        success: function(a) {
		          //console.log(a);
		          $('#select_CNC').html(a);
		          //$("#select_CNC option[value='"+CNC+"']").attr('selected', true);
		        }
		      });
		    }
		  });
		}


		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_cancelar").on("click", function() {
		  location.reload();
		});

		var cancelarEdicionFila = function() {
		  $("#btn_cancelar_editar").one("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function() {
			$("#btn_guardar_editar").one("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;

				var Id = $("#Id").serialize();
        var semana = $("#semana").serialize();
        var opcion = $("#opcion").serialize();

				var Responsable_AIA=$("#select_Responsable_AIA").serialize();
				var Categoria_CNC=$("#select_Categoria_CNC").serialize();
				var CNC=$("#select_CNC").serialize();
				var Observaciones_CNC=$("#select_Observaciones_CNC").serialize();
				frm=Id+"&"+semana+"&"+opcion+"&"+Categoria_CNC+"&"+CNC+"&"+Observaciones_CNC;

					$.ajax({
						method: "POST",
						url: "../programacion_semanal/guardar_CNC.php?db="+db,
						contenttype:"charset=utf-8",
						data: frm,
					}).done( function( info ){
						recargarTabla("");
					});
			});
		}

		var limpiar_datos_nueva_sem = function() {
		  $("#opcion").val("registrar");
		  $("#inicio_sem").val("");
		}

		var recargarTabla = function(opcion) {
		  var posicion = $('.dataTables_scrollBody').scrollTop();
		  var table = $('#dt_cliente').DataTable();
		  if (opcion == "listar") {
		    $('#dt_cliente').empty();
				listar();
				//actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
				//listar();
		  } else {
		    table.ajax.reload();
				// actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "noListar");
		    obtener_data_editar("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
		}

		function wait(ms){
			 var start = new Date().getTime();
			 var end = start;
			 while(end < start + ms) {
				 end = new Date().getTime();
			}
		}
		/*Configura la DataTable en idioma español*/
		var idioma_espanol = {
		  "sProcessing": "Procesando...",
		  "sLengthMenu": "Mostrar _MENU_ registros",
		  "sZeroRecords": "No se encontraron resultados",
		  "sEmptyTable": "Ningún dato disponible en esta tabla =(",
		  "sInfo": "Mostrando  _TOTAL_ registros",
		  "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
		  "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
		  "sInfoPostFix": "",
		  "sSearch": "Buscar:",
		  "sUrl": "",
		  "sInfoThousands": ",",
		  "sLoadingRecords": "Cargando...",
		  "oPaginate": {
		    "sFirst": "Primero",
		    "sLast": "Último",
		    "sNext": "Siguiente",
		    "sPrevious": "Anterior"
		  },
		  "oAria": {
		    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
		    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
		  },
		  "buttons": {
		    "copy": "Copiar",
		    "colvis": "Visibilidad"
		  }
		}

	</script>
</body>
</html>
