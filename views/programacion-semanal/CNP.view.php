<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js" charset="utf-8"></script>
    <style>
        .filaBotones, .ps-actions-row {
            overflow: visible !important;
        }
        .ps-actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 6px;
        }
        /* Nuclear specificity to force rectangular tools for DataTable Header */
        body #dt_cliente_wrapper .filaBotones .btn, 
        body #dt_cliente_wrapper .filaBotones button, 
        body #dt_cliente_wrapper .filaMensajes .btn, 
        body #dt_cliente_wrapper .filaMensajes button, 
        body #dt_cliente_wrapper .filaMensajes .form-control, 
        body #dt_cliente_wrapper .dataTables_filter input {
            border-radius: 4px !important;
            -webkit-appearance: none !important;
            appearance: none !important;
        }
        
        /* Dropdown de Navegación por Hover - Visibility Fix */
        .ps-dropdown-nav {
            position: relative;
            display: inline-block;
            z-index: 2000;
        }

        /* Puente invisible para evitar que el dropdown se cierre en el espacio en blanco */
        .ps-dropdown-nav::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -15px;
            height: 15px;
        }

        .ps-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: #ffffff;
            min-width: 240px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.2);
            z-index: 5000 !important;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            overflow: visible !important;
            margin-top: 4px;
        }

        .ps-dropdown-nav:hover .ps-dropdown-content,
        .ps-dropdown-nav.is-open .ps-dropdown-content {
            display: block !important;
        }

        .ps-dropdown-item {
            color: #334155;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .ps-dropdown-item:last-child {
            border-bottom: none;
        }

        .ps-dropdown-item:hover {
            background-color: #f1f5f9;
            color: #1e5ea8;
        }

        .ps-dropdown-item.is-active {
            background-color: #eff6ff !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            border-left: 3px solid #1e5ea8 !important;
        }

        .ps-dropdown-item i {
            width: 18px;
            text-align: center;
            color: #64748b;
        }

        .btn-dropdown-trigger {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            padding: 0.35rem 0.75rem;
            border-radius: 4px !important;
        }

        .btn-dropdown-trigger:hover {
            background: #f8fafc !important;
            border-color: #1e5ea8 !important;
        }
    </style>
</head>

<!--Etiqueta superior-->
<body>
    <input type="hidden" id="semana_PHP" value="<?php echo $semana; ?>">
    <input type="hidden" id="db_PHP" value="<?php echo $dbName; ?>">
    <input type="hidden" id="proyecto_PHP" value="<?php echo $proyecto; ?>">
    <input type="hidden" id="Semanal_Confirmada" value="<?php echo htmlspecialchars($AIA_semana_confirmada ?? 0, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="CNP" aria-hidden="true">
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
	<div class="row tabla table-responsive-custom ps-table-wrap">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100 ps-table-container">
			<table id="dt_cliente" class="dt_CNP table table-bordered table-hover table-responsive-sm table-sm w-100 ps-table" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Actividad</th>
						<th>Descripción</th>
						<th>Ubicación</th>
						<th>¿Liberada?</th>
						<th>Profesional AIA</th>
						<th>Categoría CNP</th>
						<th>Causa de No Programación</th>
						<th>Observaciones</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
		<div class="modal fade" id="modalReprogramar" tabindex="-1" role="dialog" aria-labelledby="modalReprogramarLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modalReprogramarLabel">Reprogramar Actividad</h4>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick=listar()><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-reprogramar" id="modal-body-texto-reprogramar"></p>
		      </div>
 		      <div class="modal-footer">
 		        <button type="button" id="reprogramar-usuario" class="btn btn-primary" data-dismiss="modal" aria-label="Aceptar">Aceptar</button>
 		        <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Cancelar">Cancelar</button>
 		      </div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->
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
			var db = document.getElementById('baseDatos') ? document.getElementById('baseDatos').value : document.getElementById('db_PHP').value;
			var semana = document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value;
			
			var inputSemanalConfirmada = document.getElementById('Semanal_Confirmada');
			var Semanal_Confirmada = inputSemanalConfirmada ? inputSemanalConfirmada.value : 0;

			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto";


			if(Semanal_Confirmada==1){
					var botones_disponibles="<button type= 'button' class='editar btn btn-primary btn-sm ps-btn-tight'><i class='fa fa-edit fa-xs'></i></button>";
			}else{
					var botones_disponibles="<button type= 'button' class='editar btn btn-primary btn-sm ps-btn-tight'><i class='fa fa-edit fa-xs'></i></button><button type= 'button' class='reprogramar btn btn-success btn-sm ps-btn-tight'><i class='fa fa-undo-alt fa-xs'></i></button>";
			}
			
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
					"url":"/api/cnp/list",
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
							'targets': [6],
							'width':'10%',
						},
						{
							'targets': [7],
							'width':'14%',
						},
						{
							'targets': [8],
							'width':'12%',
						},
						{
							'targets': [9],
							'width':'14%',
						},
						{
							'targets': [10],
							'width':'24%',
						},

						{
						'targets': [2,3,4,5,7,8,9,10],
						'render': function ( data, type, full, meta ) {
										return data;
								}
						},

						{
								'targets': [6],
								'render': function ( data, type, full, meta ) {
										if(data===""){
												data="";
												return data;
										}else if(data==0){
												data="Sí";
												return data;
										}else if (data==1){
												data="No";
												return data;
										}

								},
						},

						{
								'targets': [0],
								'className': 'Botones'
						},
						{
								'targets': [7],
								'className': 'input_Responsable_AIA'
						},
						{
								'targets': [8],
								'className': 'input_Categoria_CNC'
						},
						{
								'targets': [9],
								'className': 'input_CNC'
						},
						{
								'targets': [10],
								'className': 'input_Observaciones_CNC'
						},

					],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

				"columns":[
						{"defaultContent": botones_disponibles},
						{"data":"Consecutivo", "visible":false},
						{"data":"Id"},
						{"data":"Actividad"},
						{"data":"Descripcion", "visible":false},
						{"data":"Ubicacion", "visible":false},
						{"data":"Prog_Sin_Restricciones_100"},
						{"data":"Responsable_AIA"},
						{"data":"Categoria_CNP",},
						{"data":"CNP"},
						{"data":"Observaciones_CNP"},
				],

			"createdRow": function( row, data, index ) {
				// Standardized Coloring Logic for CNP
				if(data.Atrasada==1 && data.Critica==1){
					$(row).addClass('row-critical-delay');
				} else if(data.Atrasada==1 && data.Critica==0){
					$(row).addClass('row-delayed');
				} else if(data.Critica==1){
					$(row).addClass('row-warning');
				} else if(data.Critica==0){
					// Default - could be row-active or just plain
				}
			},

				"language": idioma_espanol
			});

			// Dynamic Resize Listener
			$(window).off('resize.dtCNP orientationchange.dtCNP aia:viewport-scale-change.dtCNP').on('resize.dtCNP orientationchange.dtCNP aia:viewport-scale-change.dtCNP', function() {
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

			$("div.ps-actions-row").html('<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Grupo de botones leyenda"><button type= "button" class="leyenda_colores btn btn-secondary btn-sm ps-btn-gap" data-toggle="modal" style="border-radius: 4px !important;" data-target="#modal_leyenda_colores" aria-label="Ver leyenda de colores">Leyenda <i class="fas fa-question-circle fa-lg" aria-hidden="true"></i></button></div><div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="ps-dropdown-nav" aria-label="Navegacion Programacion Semanal"><button type="button" class="btn btn-outline-primary btn-sm btn-dropdown-trigger" style="border-radius: 4px !important;" id="dropdownTriggerSecciones"><i class="fas fa-th-list"></i> <span>Ver Secciones</span> <i class="fas fa-chevron-down ml-1"></i></button><div class="ps-dropdown-content" role="menu"><button id="btn_Actividades_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=programacion_semanal&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-table"></i> Actividades</button><button id="btn_CNP_nav" type="button" class="ps-dropdown-item is-active" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=CNP&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-calendar-times"></i> Causas No Programacion</button><button id="btn_CNC_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=CNC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-exclamation-triangle"></i> Causas No Cumplimiento</button><button id="btn_Cal_Proveedores_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=CIC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-clipboard-check"></i> Calificacion Proveedores</button></div></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="ps-toolbar-filter"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm ps-filter-input" placeholder="Filtro" aria-label="Buscar en la tabla"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger ps-filter-clear" aria-label="Limpiar búsqueda"><i class="fas fa-times-circle" aria-hidden="true"></i> Limpiar</button></div>');

			activarBuscador("#dt_cliente tbody", table);
			
			var permisoElem = document.getElementById('permiso');
			var permiso = permisoElem ? permisoElem.value : '';
			
			if (typeof maestroPermisos === "function") {
				maestroPermisos(permiso);
			}
			obtener_data_editar("#dt_cliente tbody", table);
			obtener_id_reprogramar("#dt_cliente tbody", table);
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana') ? document.getElementById('Max_Semana').value : 100;
			var semana = document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value;
			var permisoElem = document.getElementById('permiso');
			var permiso = permisoElem ? permisoElem.value : '';

			var canEdit = window.RbacCapabilities ? RbacCapabilities.canEditLps(permiso, parseInt(semana), parseInt(max_semana)) : false;
			var only_once = !canEdit;

			var inputSemanalConfirmada = document.getElementById('Semanal_Confirmada');
			var Semanal_Confirmada = inputSemanalConfirmada ? inputSemanalConfirmada.value : 0;

		  $(tbody).one("click", "td", function() {
		    if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					var Id=$("#Id").val(data.Consecutivo);
					var opcion = $("#opcion").val("modificar");

					var Responsable_AIA = <?php
					        $dbInstance = Database::getInstance();
					$db = $_SESSION["db"];
					$query = "SELECT * FROM {$db}_profesionales WHERE Activo=1";
					try {
					    $stmt = $dbInstance->query($query);
					    $Responsable_AIA = "";
					    while ($valores = $stmt->fetch(PDO::FETCH_ASSOC)) {
					        $valor = $valores["nombre"];
					        $Responsable_AIA .= "<option value='$valor'>$valor</option>";
					    };
					    echo '"' . $Responsable_AIA . '"';
					} catch (PDOException $e) {
					    echo '""';
					}
					?> ;
					var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control form-control-sm' ><option value=''></option>" + Responsable_AIA + "</select>";
					$(this).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA);
					var codigo_html_Categoria_CNC = "<select id='select_Categoria_CNC' name='Categoria_CNC' class='form-control form-control-sm'><option value='' selected></option><option value='Rendimiento'>Rendimiento</option><option value='Programación'>Programación</option><option value='Mano de Obra'>Mano de Obra</option><option value='Materiales'>Materiales</option><option value='Equipos'>Equipos</option><option value='Diseños'>Diseños</option><option value='Administrativas'>Administrativas</option><option value='Causas Exógenas'>Causas Exógenas</option></select>";
					$(this).parent().find('.input_Categoria_CNC').html(codigo_html_Categoria_CNC);
					var codigo_html_CNC = "<select id='select_CNC' name='CNC' class='form-control form-control-sm'><option value='' selected></option></select>";
					$(this).parent().find('.input_CNC').html(codigo_html_CNC);
					var codigo_html_Observaciones_CNC = "<textarea id='select_Observaciones_CNC' name='Observaciones_CNC' class='form-control form-control-sm'>'" + data.Observaciones_CNC + "'</textarea>";
					$(this).parent().find('.input_Observaciones_CNC').html(codigo_html_Observaciones_CNC);
					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm ps-btn-edit' title='Guardar Causa de No Programación'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm ps-btn-edit' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$(this).parent().find('.Botones').html(codigo_html_botones);
					$("#select_Responsable_AIA").val(data.Responsable_AIA).change();
					$("#select_Responsable_AIA").focus();
					//$("#select_CNC").val(data.CNP).change();
					$("#select_Categoria_CNC").val(data.Categoria_CNP).change();
					cnc(data.CNP);

					$('#select_CNC').html("<option value='" + data.CNP + "'>" + data.CNP + "</option>");
					$('#select_CNC').html("<option value='" + data.CNP + "'>" + data.CNP + "</option>");
					$("#select_Observaciones_CNC").val(data.Observaciones_CNP).change();

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
		      data: { "categoria": categoria },
		      success: function(data) {
		        var optionsHtml = "<option value=''></option>";
		        if (Array.isArray(data)) {
		          data.forEach(function(item) {
		            optionsHtml += "<option value='" + item.CNC + "'>" + item.CNC + "</option>";
		          });
		        }
		        $('#select_CNC').html(optionsHtml);
		        $("#select_CNC").val(CNC);
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
		      data: { "categoria": categoria },
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

		/*Toma los datos de la fila en la que se presionó el botón duplicar*/
		var obtener_id_reprogramar=function(tbody, table){
			var max_semana = document.getElementById('Max_Semana') ? document.getElementById('Max_Semana').value : 100;
			var semana = document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value;
			var elementoPermiso = document.getElementById('permiso');
			var permiso = elementoPermiso ? elementoPermiso.value : '';
			var canEdit = window.RbacCapabilities ? RbacCapabilities.canEditLps(permiso, parseInt(semana), parseInt(max_semana)) : false;

			if(canEdit){
				$(tbody).off("click", "button.reprogramar").on("click", "button.reprogramar", function(){
					$("#modalReprogramar").modal("show");
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#Id").val(data.Consecutivo);
					var semana=$("#semana").val(data.Semana);
					var opcion=$("#opcion_Reprogramar").val("reprogramar");
					var texto=$("#modal-body-texto-reprogramar").html("¿Desea reprogramar la actividad: "+data.Actividad+"?");

					reprogramar();
				});
			}

		}

		var reprogramar = function() {
			$("#reprogramar-usuario").on("click", function() {
				var db = document.getElementById('baseDatos').value;
		    var Id = $("#Id").val(),
		      opcion = $("#opcion_Reprogramar").val(),
		      semana = $("#semana").val();
		    $.ajax({
		      method: "POST",
		      url: "/api/cnp/reprogramar",
		      data: {
		        "Id": Id,
		        "semana": semana
		      }
		    }).done(function(info) {
		      recargarTabla('');
		    });
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
				frm=Id+"&"+semana+"&"+opcion+"&"+Responsable_AIA+"&"+Categoria_CNC+"&"+CNC+"&"+Observaciones_CNC;

					$.ajax({
						method: "POST",
						url: "/api/cnp/save",
						data: frm,
					}).done( function( info ){
						recargarTabla("");
					});
			});
		}

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
				var opcionReprogramar = document.getElementById('opcion_Reprogramar');
				if(opcionReprogramar.value == "reprogramar"){
					obtener_id_reprogramar("#dt_cliente tbody", table);
					opcionReprogramar.value='';
				}else{
					opcionReprogramar.value='';
				}
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
