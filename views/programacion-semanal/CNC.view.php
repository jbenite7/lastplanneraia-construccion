<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
    <title>Causas de No Cumplimiento — Last Planner AIA</title>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::renderForModule('programacion-semanal') ?>
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
    <?php $psCssVersion = @filemtime(dirname(__DIR__, 2) . '/public/css/programacion-semanal.css') ?: 'ps1'; ?>
    <link rel="stylesheet" href="/css/programacion-semanal.css?v=<?= urlencode((string) $psCssVersion) ?>">
</head>

<!--Etiqueta superior-->
<body class="aia-shell aia-shell--sidebar ps-page">

	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <input type="hidden" id="semana_PHP" value="<?php echo $semana; ?>">
    <input type="hidden" id="db_PHP" value="<?php echo $dbName; ?>">
    <input type="hidden" id="proyecto_PHP" value="<?php echo $proyecto; ?>">

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="CNC" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">

		<input type="hidden" id="opcion_Reprogramar" name="opcion_Reprogramar" value="" aria-hidden="true">

		<input type="hidden" id="Activa_Reprogramar" name="Activa_Reprogramar" value="" readonly aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom ps-table-wrap aia-grid-shell">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100 ps-table-container">
			<table id="dt_cliente" class="dt_CNP table aia-table w-100 ps-table" cellspacing="0" width="100%">
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
	<script type="text/javascript" charset="utf8" src="/public/vendor/popper.min.js"></script>
	<!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="/public/vendor/bootstrap/bootstrap.min.js"></script>
	<!--Iniciar DataTables-->
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
	<script src="/js/modules/programacion_semanal/legacyCards.js?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/js/modules/programacion_semanal/legacyCards.js') ?: 'ps1')) ?>"></script>
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
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
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

		/* Dynamic Table Height Calculation */
		function calcDataTableHeight() {
			if (window.DataTableHeightManager && typeof window.DataTableHeightManager.calcHeight === "function") {
				return window.DataTableHeightManager.calcHeight({
					container: "#cuadroTabla",
					internalChrome: 170,
					bottomMargin: 25,
					minHeight: 200
				});
			}

			var windowHeight = $(window).height();
			var topOffset = $("#cuadroTabla").offset().top;
			var internalChrome = 170;
			var bottomMargin = 25;
			var availableHeight = windowHeight - topOffset - internalChrome - bottomMargin;
			return (availableHeight > 200 ? availableHeight : 200) + "px";
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana_PHP').value;
			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;


			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto";


			var table = $("#dt_cliente").DataTable({
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'ps-actions-row'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering":false,
				"autoWidth": false,
				"fixedHeader": false,
				"scrollX": false, /* PROHIBIDO SCROLL HORIZONTAL */
				//                console.log($(document).height());
				"scrollY": alturatabla,
				/*                "scrollCollapse": false,*/
				"responsive": true,
				"paging": false,
				"ajax": {
					"method": "POST",
					"url":"/api/cnc/list",
					"data": { "db": db, "semana": semana }
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [
						{
							'targets': [0],
							'width':'4%',
						},
						{
							'targets': [2],
							'width':'6%',
						},
						{
							'targets': [3],
							'width':'16%',
						},
						{
							'targets': [4],
							'width':'24%',
						},
						{
							'targets': [6],
							'width':'14%',
						},
						{
							'targets': [7],
							'width':'16%',
						},
						{
							'targets': [8],
							'width':'20%',
						},

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
					{"defaultContent":"<button type= 'button' class='editar aia-btn aia-btn--primary btn-sm' aria-label='Editar actividad' title='Editar actividad'><i class='fa fa-edit' aria-hidden='true'></i></button>"},
					{"data":"Consecutivo", "visible":false},
					{"data":"Id"},
					{"data":"Actividad"},
					{"data":"Descripcion"},
					{"data":"Ubicacion", "visible":false},
					{"data":"Categoria_CNC",},
					{"data":"CNC"},
					{"data":"Observaciones_CNC"},
				],

			"createdRow": function( row, data, index ) {
				// Standardized Coloring Logic for CNC
				if(data.Atrasada==1 && data.Critica==1){
					$(row).addClass('row-critical-delay');
				} else if(data.Atrasada==1 && data.Critica==0){
					$(row).addClass('row-delayed');
				} else if(data.Critica==1){
					$(row).addClass('row-warning');
				} else if(data.Critica==0){
					// Default state
				}
			},

				"language": idioma_espanol
			});


			// Dynamic Resize Listener
			$(window).off('resize.dtCNC orientationchange.dtCNC aia:viewport-scale-change.dtCNC').on('resize.dtCNC orientationchange.dtCNC aia:viewport-scale-change.dtCNC', function() {
				var opts = {
					container: "#cuadroTabla",
					internalChrome: 170,
					bottomMargin: 25,
					minHeight: 200
				};

				if (window.DataTableHeightManager && typeof window.DataTableHeightManager.applyToDataTable === "function") {
					window.DataTableHeightManager.applyToDataTable(table, opts);
					return;
				}

				var newHeight = calcDataTableHeight();
				$('div.dataTables_scrollBody').css('height', newHeight);
				$('div.dataTables_scrollBody').css('max-height', newHeight);
				table.settings()[0].oScroll.sY = newHeight;
				table.columns.adjust();
			});

			$("div.ps-actions-row").html('<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Grupo de botones leyenda"><button type= "button" class="leyenda_colores aia-btn aia-btn--secondary ps-btn-gap" data-toggle="modal" data-target="#modal_leyenda_colores" aria-label="Ver leyenda de colores">Leyenda <i class="fas fa-question-circle fa-lg" aria-hidden="true"></i></button></div><div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="ps-dropdown-nav" aria-label="Navegacion Programacion Semanal"><button type="button" class="aia-btn aia-btn--secondary btn-dropdown-trigger" id="dropdownTriggerSecciones"><i class="fas fa-th-list"></i> <span>Ver Secciones</span> <i class="fas fa-chevron-down ml-1"></i></button><div class="ps-dropdown-content" role="menu"><button id="btn_Actividades_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=programacion_semanal&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-table"></i> Actividades</button><button id="btn_CNP_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CNP&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-calendar-times"></i> Causas No Programacion</button><button id="btn_CNC_nav" type="button" class="ps-dropdown-item is-active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CNC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-exclamation-triangle"></i> Causas No Cumplimiento</button><button id="btn_Cal_Proveedores_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CIC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-clipboard-check"></i> Calificacion Proveedores</button></div></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="ps-toolbar-filter"><button id="btn_limpiar_buscador" type="button" class="aia-btn aia-btn--secondary ps-filter-clear" aria-label="Limpiar búsqueda"><i class="fas fa-times-circle" aria-hidden="true"></i> Limpiar</button></div>');
			maestroPermisos(document.getElementById('permiso_canonico').value);
			obtener_data_editar("#dt_cliente tbody", table);
			if (window.PSLegacyCards) window.PSLegacyCards.attach(table, 'cnc');
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana_PHP').value;
		  var permiso = document.getElementById('permiso_canonico').value;

			var canEdit = RbacCapabilities.canEditLps(permiso, parseInt(semana), parseInt(max_semana));
			var only_once = canEdit;

			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

		  $(tbody).off("click.cncEdit", "button.editar").on("click.cncEdit", "button.editar", function(e) {
		    e.preventDefault();
		    e.stopPropagation();
		    if (only_once == true) {
					var $row = $(this).closest("tr");
					var data = table.row($row).data();
					var Id=$("#Id").val(data.Consecutivo);
					var opcion = $("#opcion").val("modificar");

				var codigo_html_Categoria_CNC = "<select id='select_Categoria_CNC' name='Categoria_CNC' class='aia-input'><option value='' selected></option></select>";
				$row.find('.input_Categoria_CNC').html(codigo_html_Categoria_CNC);
				cargarCategoriasCNC(data.Categoria_CNC);
					var codigo_html_CNC = "<select id='select_CNC' name='CNC' class='aia-input'><option value='' selected></option></select>";
					$row.find('.input_CNC').html(codigo_html_CNC);
					var codigo_html_Observaciones_CNC = "<textarea id='select_Observaciones_CNC' name='Observaciones_CNC' class='aia-input'>'" + data.Observaciones_CNC + "'</textarea>";
					$row.find('.input_Observaciones_CNC').html(codigo_html_Observaciones_CNC);
					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar aia-btn aia-btn--success btn-sm ps-btn-edit' title='Guardar Causa de No Programación'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar aia-btn aia-btn--danger btn-sm ps-btn-edit' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$row.find('.Botones').html(codigo_html_botones);
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
				db = document.getElementById('baseDatos').value,
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
		      url: "/api/cnc/reasons",
			      data: { "categoria": categoria, "area": window.__PROJECT_AREA__ || 'Construccion' },
			      success: function(data) {
			        var $select = $('#select_CNC').empty().append($('<option>', { value: '', text: '' }));
			        var values = [];
			        if (Array.isArray(data)) {
			          data.forEach(function(item) {
			            values.push(item.CNC);
			            $select.append($('<option>', { value: item.CNC, text: item.CNC }));
			          });
			        }
			        if (CNC && values.indexOf(CNC) === -1) {
			          $select.append($('<option>', { value: CNC, text: CNC }));
			        }
			        $select.val(CNC);
		      }
		    });
		  }
		}

		var cnc1 = function() {
		  $('#select_Categoria_CNC').on('change', function() {
		    var categoria = $("#select_Categoria_CNC").val(),
					db = document.getElementById('baseDatos').value,
		      opcion = "CNC";
		    //console.log(CNC);
		    if (categoria === "") {
		      $('#select_CNC').attr('readonly', true);
		      $('#select_CNC').html("<option value=''></option>");
		    } else {
		      $('#select_CNC').attr('readonly', false);
		    $.ajax({
		      method: "POST",
		      url: "/api/cnc/reasons",
		      data: { "categoria": categoria, "area": window.__PROJECT_AREA__ || 'Construccion' },
		      success: function(data) {
		        var optionsHtml = "<option value=''></option>";
		        if (Array.isArray(data)) {
		          data.forEach(function(item) {
		            optionsHtml += "<option value='" + item.CNC + "'>" + item.CNC + "</option>";
		          });
		        }
		        $('#select_CNC').html(optionsHtml);
		        if (typeof CNC !== 'undefined' && CNC) {
		          $("#select_CNC").val(CNC);
		        }
		      }
		    });
		    }
		  });
		}

		var cargarCategoriasCNC = function(selectedValue) {
		  var categorias = ['Rendimiento', 'Programación', 'Mano de Obra', 'Materiales', 'Equipos', 'Diseños', 'Administrativas', 'Causas Exógenas'];
		  if (selectedValue && categorias.indexOf(selectedValue) === -1) categorias.unshift(selectedValue);
		  var optionsHtml = "<option value='' selected></option>";
		  categorias.forEach(function(cat) {
		    optionsHtml += "<option value='" + cat + "'>" + cat + "</option>";
		  });
		  $('#select_Categoria_CNC').html(optionsHtml);
		  if (selectedValue) {
		    $('#select_Categoria_CNC').val(selectedValue);
		  }
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

				/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
			var texto = "",
				borderClass = "success";

			if (informacion.respuesta == "BIEN") {
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				borderClass = "success";
			} else if (informacion.respuesta == "ERROR") {
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				borderClass = "error";
			} else if (informacion.respuesta == "EXISTE") {
				texto = "<strong>Información!</strong> el usuario ya existe.";
				borderClass = "error";
			} else if (informacion.respuesta == "VACIO") {
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				borderClass = "warning";
			} else {
                 texto = informacion.respuesta || "Mensaje del sistema";
                 if(texto.toLowerCase().includes("error")) borderClass = "error";
            }

            var toast = $("#mensajeActualizacion");
            toast.removeClass("success error warning").addClass("custom-toast " + borderClass);
            toast.html(texto);
            toast.show().delay(4000).fadeOut(1000, function(){
                $(this).html("");
            });
		}
				var frm = {
					Consecutivo: $("#Id").val(),
					semana: $("#semana_PHP").val(),
					Categoria_CNC: $("#select_Categoria_CNC").val(),
					CNC: $("#select_CNC").val(),
					Observaciones_CNC: $("#select_Observaciones_CNC").val()
				};

					$.ajax({
						method: "POST",
						url: "/api/cnc/save",
						data: frm,
						dataType: "json"
					}).done( function( info ){
						mostrar_mensaje(info);
						if (info.respuesta === "BIEN") recargarTabla("");
					}).fail(function() {
						mostrar_mensaje({
							respuesta: "ERROR",
							mensaje: "No fue posible guardar la causa."
						});
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
		  "sEmptyTable": "Sin causas de no cumplimiento esta semana. Se registran al justificar un avance menor al compromiso en Programación Semanal.",
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

        /* Lógica para Dropdown de navegación */
        $(document).off('click.psDropdown').on('click.psDropdown', '.btn-dropdown-trigger', function(e) {
          e.stopPropagation();
          var $nav = $(this).closest('.ps-dropdown-nav');
          $('.ps-dropdown-nav').not($nav).removeClass('is-open');
          $nav.toggleClass('is-open');
        });

        $(document).off('click.psDropdownClose').on('click.psDropdownClose', function() {
          $('.ps-dropdown-nav').removeClass('is-open');
        });

	</script>
</body>
</html>
