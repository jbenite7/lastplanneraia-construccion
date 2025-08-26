<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_listadoActividades">
		<input type="hidden" id="codigo" name="codigo" value="">
		<input type="hidden" id="Id" name="Id" value="" readonly>
		<input type="hidden" id="opcion" name="opcion" value="" readonly>
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro" style:"visibility: hidden">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_infoGeneral table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Id</th>
						<th>Id</th>
						<th>Actividad</th>
						<th>Descripción</th>
						<th>Actividad de Inicio En Programa</th>
						<th>Actividad de Inicio En Programa</th>
						<th>Fecha de Inicio</th>
						<th>Tipo de Contrato</th>
						<th>Semana de Actualizacion</th>
						<!-- <th>Id Paquete de Contratación</th>
						<th>Paquete de Contratación</th> -->
					</tr>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!--Genera el modal con el formulario de registro de una nueva actividad para el proyecto-->
		<div class="modal_nuevaActividad modal fade" id="modalNuevaActividad" role="dialog" aria-labelledby="modal_nuevaActividadLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalNuevaActividadLabel">
		          <p class="modal-body-texto-nuevaActividad" id="modal-body-texto-nuevaActividad">Registrar Nueva Actividad</p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <div class="form-group parametro_nuevaActividad">
		                <div class="form_eval form-group">
		                  <h3 id='form_general'> Formulario de Registro de Actividades</h3>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
										<input type="hidden" id="Id" name="Id" value="">
							      <input type="hidden" id="opcion" name="opcion" value="registrar">
		                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
		                <div class="col-sm-12">
		                  <label for="actividad" class="control-label">Actividad</label><input id="actividad" name="actividad" type="text" class="form-control">
		                </div>
		                <br>
		                <div class="col-sm-12">
		                  <label for="descripcionActividad" class="control-label">Descripción</label><input id="descripcionActividad" name="descripcionActividad" type="text" class="form-control">
		                </div>
		                <br>
		                <div class="col-sm-12">
		                  <label for="actividadInicio" class="control-label">Tarea del Cronograma de Inicio de la Actividad</label>
		                  <select id="actividadInicio" name="actividadInicio" class="form-control" onchange="actualizarFechaInicio('nuevo')" style="width:100%; border-color:rgb(206, 212, 218, 0)">
		                    <option value=""></option> <?php
																	require("../conexion.php");
																	$db = $_SESSION["db"];
																	$semana = $_SESSION["semana"];
																	$query="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Fecha_Inicio ASC";
																	$resultado= mysqli_query($conexion, $query);
																	while ($valores = mysqli_fetch_assoc($resultado)){
																			$Actividad=$valores["Actividad"];
																			$Actividad=str_replace('"','\"',$Actividad);
																			$Actividad=str_replace("'","\'",$Actividad);
																			echo '<option value="'.$Actividad.'">'.$valores["Id"].'.  <b>'.$Actividad.'</b> <small>(Inicia el: '.$valores["Fecha_Inicio"].')</small></option>';
																	};
																	mysqli_close($conexion);
															?>
		                  </select>
		                </div>
		                <br>
		                <div class="col-sm-12">
		                  <label for="fechaInicio" class="control-label">Fecha de Inicio</label><input id="fechaInicio" name="fechaInicio" type="text" class="form-control">
		                </div>
		                <br>
		                <div class="col-sm-12">
		                  <label for="tipoContrato" class="control-label">Tipo de Contrato</label>
		                  <select id="tipoContrato" name="tipoContrato" class="form-control">
		                    <option value=""></option>
		                    <option value=1>Mano de Obra y Suministro Por Separado</option>
		                    <option value=2>Suministro e Instalación</option>
		                  </select>
		                </div>
		              </div>
		              <br>
									<br>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input id="" type="submit" class="btn btn-primary" value="Guardar">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal">
		                </div>
		              </div>
		              <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
		              <div class="col-sm-offset-2 col-sm-8">
		                <p class="mensaje"></p>
		              </div>
		            </form>
		          </div>
		        </div>
		      </div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- General el modal para descargar y cargar el CSV con el que se puede agregar el listado de actividades desde excel -->
		<div class="modal_cargarExcel modal fade" id="modalCargarExcel" role="dialog" aria-labelledby="modal_cargarExcelLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalCargarExcelLabel">
		          <p class="modal-body-texto-cargarExcel" id="modal-body-texto-cargarExcel">Cargar Listado de Actividades desde Excel</p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form enctype="multipart/form-data" class="form form-horizontal" id="formCargarExcel" name="formCargarExcel" action="" method="POST">
		              <div class="form-group parametro_cargarExcel" style="padding: 1px 5px 10px 5px; margin-bottom: 10px; border:none">
		                <!-- <div class="form_eval form-group">
													<h3 id='form_general'>
														Descargar Archivo Base
													</h3>
												</div> -->
		                <label for="descargarArchivoBase" class="control-label">En el siguiente enlace puede descargar el archivo base para crear el listado de actividades desde Excel:</label>
		                <a id="descargarArchivoBase" class="descargarArchivoBase btn btn-primary" download="listadoActividades.csv" href="../archivosBase/listadoActividades.csv">Descargar Archivo Base</a>
		              </div>
		              <div class="form-group parametro_cargarExcel" style="padding: 1px 5px 15px 5px; margin-bottom: 10px">
		                <div class="form_eval form-group">
		                  <h3 id='form_general'> Cargar Listado en Excel </h3>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es cargarExcel-->
		                <input type="hidden" id="Id" name="Id" value="">
		                <input type="hidden" id="opcion" name="opcion" value="cargarExcel">
		                <input type="hidden" id="codigo" name="codigo" value="">
		                <!-- Se crea el input para cargar el archivo CSV que cargarà el listado de actividades del proyecto -->
		                <div class="col-sm-12">
		                  <label for="archivoExcel" class="control-label">Seleccione el archivo con el listado de actividades completo desde el equipo (Solo se permiten archivos en formato CSV):</label>
		                  <input type="file" name="archivoExcel" id="archivoExcel" class="form-control form-control-lg" accept=".csv">
		                  <!-- <input type="submit" value="Enviar" name="archivoExcel"> -->
		                </div>
		              </div>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input id="" type="submit" class="btn btn-success" value="Guardar">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal">
		                </div>
		              </div>
		              <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
		              <div class="col-sm-offset-2 col-sm-8">
		                <p class="mensaje"></p>
		              </div>
		            </form>
		          </div>
		        </div>
		      </div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
		<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modalEliminarLabel">Eliminar Actividad</h4>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-eliminar" id="modal-body-texto-eliminar"></p>
		      </div>
		      <div class="modal-footer">
		        <button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" data-toggle="modal">Aceptar</button>
		        <!--data-target="#modal_CNP"-->
		        <button type="button" class="btn btn-default" data-dismiss="modal" onClick='listar()'>Cancelar</button>
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
			$('#actividadInicio').select2({allowClear: true});
      listar();
			guardarNuevaActividad();
			guardarCargarExcel();
      eliminar();
		}

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  recargarTabla("listar");
		  limpiar_datos();
		  $("#formulario_nuevo").slideUp("slow");
		  $("#cuadroTabla").slideDown("slow");
		});

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_cancelar").on("click", function() {
		  location.reload();
		});

		var cancelarEdicionFila = function() {
		  $("#btn_cancelar_editar").on("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}


		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
		  /*Identificamos la altura de la hoja para determinar la altura de la tabla*/
		  var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 170) + "px";

		  var table = $("#dt_cliente").DataTable({
		    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
		    "destroy": true,
		    "ordering": false,
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
		      "url": "../listadoActividades/listar_listadoActividades.php?db="+db+"&semana="+Max_Semana
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [
					{
					'targets': [8],
					'render': function ( data, type, full, meta ) {
							if(data==1){
								return "Mano de Obra y Suministro Por Separado";
							}else if(data==2){
								return "Suministro e Instalación";
							}else{
								return data;
							}

						},
					},
					{
							'targets': [0,1,2],
							'width':'1%',
					},
					{
							'targets': [1,2,3,4,5,6,7,8,9],
							'width':'10%',
							'render': function ( data, type, full, meta ) {
							 return data;
							},
					},
				],
				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm'  title='Eliminar' style='margin:1px'><i class='fa fa-trash-alt fa-xs'></i></button>"},
						{"data":"Id", "visible":false},
						{"data":"codigo"},
						{"data":"actividad"},
						{"data":"descripcionActividad"},
						{"data":"actividadInicio", "visible":false},
						{"data":"nombreActividadInicio"},
						{"data":"fechaInicio"},
						{"data":"tipoContrato"},
						{"data":"semanaActualizacion", "visible":false},
						// {"data":"idPaqueteContratacion", "visible":false},
						// {"data":"paqueteContratacion"}
				],
		    "language": idioma_espanol
		  });

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:50%;display:inline-block; "><button id="btn_tutorialActualizarCronograma" type="button" class="btn btn-secondary btn-sm" style="margin-right:5px" title="Video tutorial de la sección de \'\'Actividades del Proyecto\'\'" onclick="window.open(\'https://youtu.be/DnjTRfFupSA\', \'_blank\')">Tutorial <i class="fas fa-list-ol fa-lg"></i></button><button id="btn_cargarActividadesExcel" class="btn btn-secondary btn-sm" title="Cargar listado de actividades desde Excel" data-toggle="modal" data-target="#modalCargarExcel">Cargar desde Excel <i class="fas fa-upload fa-lg"></i></button><button id="btn_nueva_actividad" class="btn btn-primary btn-sm" title="Registrar nueva actividad del proyecto" data-toggle="modal" data-target="#modalNuevaActividad" style="margin: auto 5px">Nueva Actividad <i class="fas fa-plus fa-lg"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><!-- <button id="btn_paquetesContratacion" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_paquetesContratacion&semana='+semana+'\'">Paquetes de Contratación <i class="fas fa-arrow-right fa-m"></i></button> --><button id="btn_planCompras" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=planCompras&semana='+semana+'\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso').value);
			activarBuscador("#dt_cliente tbody", table);
		  obtener_data_editar("#dt_cliente tbody", table);
		  obtener_id_eliminar("#dt_cliente tbody", table);
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  var permiso = document.getElementById('permiso').value;
		  if (permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="R" || permiso=="DCV" || permiso=="V" || permiso=="C") {
		    var only_once = false;
		  } else {
		    var only_once = true;
		  }
		  $(tbody).one("click", "td", function() {
				var data= table.row($(this).parents("tr")).data();
				var Id=$("#Id").val(data.Id),
						opcion = $("#opcion").val("modificar"),
						codigo = $("#codigo").val(data.codigo);
		    if (only_once == true) {
					var codigo_html_Actividad =  "<input id='select_Actividad' name='Actividad' class='form-control form-control-sm' type='text' value='"+data.actividad+"'></input>";
					$( this ).parent().find('td:eq(2)').html(codigo_html_Actividad);

					var codigo_html_descripcionActividad =  "<input id='select_descripcionActividad' name='descripcionActividad' class='form-control form-control-sm' type='text' value='"+data.descripcionActividad+"'></input>";
					$( this ).parent().find('td:eq(3)').html(codigo_html_descripcionActividad);

					var opciones_codigo_html_actividadInicio = "<?php
							require("../conexion.php");
							$db = $_SESSION["db"];
							$semana = $_SESSION["semana"];
							$query="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Fecha_Inicio ASC";
							$resultado= mysqli_query($conexion, $query);
							while ($valores = mysqli_fetch_assoc($resultado)){
									$Consecutivo_en_Programa=$valores['Consecutivo_en_Programa'];
									$Actividad=$valores['Actividad'];
									$Actividad=str_replace('"','\"',$Actividad);
									$Actividad=str_replace("'","\'",$Actividad);
									$Fecha_Inicio=$valores['Fecha_Inicio'];
									$Id=$valores['Id'];
									echo "<option value='".$Actividad."'>".$Id.". <b>".$Actividad."</b> <small>(Inicia el: ".$Fecha_Inicio.")</small></option>";
							};
							mysqli_close($conexion);
					?>";

					var codigo_html_actividadInicio =  "<select id='select_actividadInicio' name='actividadInicio' class='form-control form-control-sm' onchange=actualizarFechaInicio('actualizar')><option value=''></option>";
					codigo_html_actividadInicio = codigo_html_actividadInicio + opciones_codigo_html_actividadInicio + "</select>";
					$( this ).parent().find('td:eq(4)').html(codigo_html_actividadInicio);
					$('#select_actividadInicio').select2({allowClear: true});

					var codigo_html_fechaInicio =  "<input id='select_fechaInicio' name='fechaInicio' class='form-control form-control-sm' type='text' value='"+data.fechaInicio+"' autocomplete='off'></input>";
					$( this ).parent().find('td:eq(5)').html(codigo_html_fechaInicio);

					$( "#select_fechaInicio" ).datepicker({dateFormat: 'yy-mm-dd',
																							 changeMonth: true,
																							 changeYear: true,
																							 showOtherMonths: true,
																							 selectOtherMonths: true,
																							 defaultDate:data.fechaInicio,
																							});



					var codigo_html_tipoContrato =  "<select id='select_tipoContrato' name='tipoContrato' class='form-control form-control-sm' ><option value=''></option><option value=1>Mano de Obra y Suministro Por Separado</option><option value=2>Suministro e Instalación</option></select>";
					$( this ).parent().find('td:eq(6)').html(codigo_html_tipoContrato);

					// var codigo_html_paqueteContratacion =  "<select id='select_paqueteContratacion' name='paqueteContratacion' class='form-control form-control-sm' ><option value=''></option><option value='1'>(1) - Paquete 1</option><option value='2'>(2) - Paquete 2</option><option value='3'>(3) - Paquete 3</option></select>";
					// $( this ).parent().find('td:eq(6)').html(codigo_html_paqueteContratacion);

					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' style='margin:1px' title='Guardar la edición'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' style='margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$( this ).parent().find('td:eq(0)').html(codigo_html_botones);

					$("#select_tipoContrato").val(data.tipoContrato).change();

					$("#select_paqueteContratacion").val(data.idPaqueteContratacion).change();

					$("#select_actividadInicio").val(data.actividadInicio).change();

					// var sel = document.getElementById("select_paqueteContratacion");
					// var text= sel.options[sel.selectedIndex].text;
					// console.log(text);

					$("#select_Actividad").focus();

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
		    guardar_modificar();
		  });
		}

		/*Toma los datos de la fila en la que se presionó el botón eliminar*/
		var obtener_id_eliminar = function(tbody, table) {
			var permiso = document.getElementById('permiso').value;
		  if (permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="R" || permiso=="DCV" || permiso=="V" || permiso=="C") {
		  } else {
				$(tbody).one("click", "button.eliminar", function() {
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#Id").val(data.Id);
					var opcion=$("#opcion").val("eliminar");
					$("#modalEliminar").modal("show");
					var texto=$("#modal-body-texto-eliminar").html("¿Desea eliminar la actividad <b>"+data.actividad+"</b> definitivamente del proyecto?");
			  });
		  }
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardarNuevaActividad = function() {
			$("#modalNuevaActividad form").on("submit", function(e) {
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var frm = $(this).serialize();
				frm = frm + "&semana=" + semana;
				$.ajax({
					method: "POST",
					url: "../listadoActividades/guardar_listadoActividades.php?db="+db,
					contenttype: "charset=utf-8",
					data: frm,
				}).done(function(info) {
					var json_info = JSON.parse(info);
					if (json_info.respuesta == "BIEN") {
						limpiar_datos();
						json_info.respuesta = json_info.respuesta + "NuevaActividad";
					}
					mostrar_mensaje( json_info );
					limpiar_datos();
					recargarTabla("");
				});
			});
		}

		var guardarCargarExcel = function() {
		  $("#modalCargarExcel form").on("submit", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
		    var variables = new FormData($("#formCargarExcel")[0]);
		    //var frm = $(this).serialize();
		    console.log(variables);
		    $.ajax({
		      type: "POST",
		      url: "../listadoActividades/guardar_listadoActividades.php?db="+db,
		      contentType: false,
		      processData: false,
		      data: variables,
		    }).done(function(info) {
		      var json_info = JSON.parse(info);
		      if (json_info.respuesta == "BIEN") {
		        limpiar_datos();
		        json_info.respuesta = json_info.respuesta + "CargarExcel";
		      }
		      mostrar_mensaje(json_info);
		      recargarTabla('');
		    });
		  });
		}

		var guardar_modificar = function() {
			$("#btn_guardar_editar").one("click", function(e) {
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var Id = $("#Id").serialize();
				var opcion = $("#opcion").serialize();
				var codigo = $("#codigo").serialize();
				var Actividad = $("#select_Actividad").serialize();
				var descripcionActividad = $("#select_descripcionActividad").serialize();
				var actividadInicio = $("#select_actividadInicio").serialize();
				var fechaInicio = $("#select_fechaInicio").serialize();
				var tipoContrato = $("#select_tipoContrato").serialize();

				frm = Id + "&" + opcion + "&" + codigo + "&" + Actividad + "&" + descripcionActividad + "&" + actividadInicio + "&" + fechaInicio + "&" + tipoContrato + "&semana=" + semana;
				// console.log(frm);
				$.ajax({
					method: "POST",
					url: "../listadoActividades/guardar_listadoActividades.php?db="+db,
					contenttype: "charset=utf-8",
					data: frm,
				}).done(function(info) {
					var json_info = JSON.parse(info);
					// console.log(json_info);
					recargarTabla('');
				});
			});
		}

		/* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
		var eliminar = function() {
		  $("#eliminar-usuario").on("click", function() {
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
		    	var Id = $("#Id").val(),
		      	opcion = "eliminar";
				//console.log(Id, opcion);
		    $.ajax({
		      method: "POST",
		      url: "../listadoActividades/guardar_listadoActividades.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "Id": Id,
		        "opcion": opcion
		      }
		    }).done(function(info) {
		      var json_info = JSON.parse(info);
		      mostrar_mensaje(json_info);
		      limpiar_datos();
		      recargarTabla('');
		      // listar();
		    });
		  });
		}

		/*Actualiza la fecha de inicio de la actividad, según el cronograma*/
		var actualizarFechaInicio = function(funcion) {
		  var opcion = "actualizarFechaInicio";
		  if (funcion == "nuevo") {
		    var idActividad = $("#actividadInicio").val();
				var sel = document.getElementById("actividadInicio");
				var nombreActividad = document.getElementById("actividadInicio").value;
		  } else {
		    var idActividad = $("#select_actividadInicio").val();
				var sel = document.getElementById("select_actividadInicio");
				var nombreActividad = document.getElementById("actividadInicio").value;
		  }

			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
		  //console.log(opcion, idActividad, nombreActividad, semana);
		  if (idActividad != "") {
		    $.ajax({
		      method: "POST",
		      url: "../listadoActividades/guardar_listadoActividades.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "idActividad": idActividad,
		        "nombreActividad": nombreActividad,
		        "opcion": opcion,
		        "semana": semana
		      }
		    }).done(function(info) {
		      var json_info = JSON.parse(info);
		      if (funcion == "nuevo") {
		        $("#fechaInicio").val(json_info["data"]["Fecha_Inicio"]);
		      } else {
		        $("#select_fechaInicio").val(json_info["data"]["Fecha_Inicio"]);
		      }
		    });
		  }
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
			var texto = "",
				color = "";
			if (informacion.respuesta == "BIENNuevaActividad" || informacion.respuesta == "BIENCargarExcel") {
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				color = "#379911";
			}
			if (informacion.respuesta == "ERROR") {
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				color = "#C9302C";
			}
			if (informacion.respuesta == "EXISTE") {
				texto = "<strong>Información!</strong> La actividad que estás intentando registrar ya existe.";
				color = "#C9302C";
			}
			if (informacion.respuesta == "VACIO") {
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				color = "#C9302C";
			}
			if (informacion.respuesta == "NO_ELIMINAR") {
				texto = "<strong>Advertencia!</strong> No se puede eliminar esta actividad.";
				color = "#C9302C";
			}
			if (informacion.respuesta == "BIENNuevaActividad") {
				//$("#cuadro2").slideUp("slow");
				//$("#cuadro1").slideDown("slow");
				//$("#cuadro3").slideDown("slow");
				$("#modalNuevaActividad").modal("hide");
				$("#mensajeActualizacion").html(texto).css({
					"color": color
				});
				$("#mensajeActualizacion").fadeOut(10000, function() {
					$(this).html("");
					$(this).fadeIn(3000);
				});
			} else if (informacion.respuesta == "BIENCargarExcel") {
				//$("#cuadro2").slideUp("slow");
				//$("#cuadro1").slideDown("slow");
				//$("#cuadro3").slideDown("slow");
				$("#modalCargarExcel").modal("hide");
				$("#mensajeActualizacion").html(texto).css({
					"color": color
				});
				$("#mensajeActualizacion").fadeOut(10000, function() {
					$(this).html("");
					$(this).fadeIn(3000);
				});
			} else if (informacion.respuesta == "NO_ELIMINAR") {
				$("#mensajeActualizacion").html(texto).css({
					"color": color
				});
				$("#mensajeActualizacion").fadeOut(10000, function() {
					$(this).html("");
					$(this).fadeIn(3000);
				});
			} else {
				$(".mensaje").html(texto).css({
					"color": color
				});
				$(".mensaje").fadeOut(5000, function() {
					$(this).html("");
					$(this).fadeIn(3000);
				});
			}
		}

		/*limpia los valores del formulario de registro*/
		var limpiar_datos = function() {
			$("#opcion").val("registrar");
			$("#Id").val("");
			$("#actividad").val("").focus();
			$("#descripcionActividad").val("");
			$("#fechaInicio").val("");
			$("#tipoContrato").val("");
			$("#idPaqueteContratacion").val("");
			$("#paqueteContratacion").val("");
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
		  } else {
		    table.ajax.reload();
		    obtener_data_editar("#dt_cliente tbody", table);
				obtener_id_eliminar("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
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
