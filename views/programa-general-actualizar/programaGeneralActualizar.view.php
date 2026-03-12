<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="actualizarCronograma" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-8 col-md-8 col-lg-8 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100">
			<table id="dt_cliente" class="dt_programaGeneral table table-bordered table-hover table-responsive-sm table-sm w-100" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Codigo Actividad</th>
						<th>Actividad Nueva</th>
						<th>Actividad a Asociar</th>
						<th>Título</th>
						<th>Semana Para Iniciar</th>
						<th>Fecha Inicio</th>
						<th>Fecha Fin</th>
						<th>Crítica</th>
						<th>Unidad</th>
						<th>Cantidad en Presupuesto</th>
						<th>Ejecutado Teórico<button type= 'button' class='pregunta_Ejecutado_Teorico btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_Ejecutado_Teorico' aria-label="Información Ejecutado Teórico" style='padding:0; margin-left:5px; display:inline-block'><i style='color:white' class='fas fa-question-circle fa-sm' aria-hidden="true"></i></button></th>
						<th>Ejecutado Real</th>
						<th>Estado</th>
						<th>Liberación Restricciones</th>
						<th>Responsable AIA</th>
						<th>Sub-Contratista</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- General el modal para descargar y cargar el CSV con el que se puede agregar el listado de actividades desde excel -->
		<div class="modal_cargarExcel modal fade" id="modalCargarExcel" role="dialog" aria-labelledby="modal_cargarExcelLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalCargarExcelLabel">
		          <p class="modal-body-texto-cargarExcel" id="modal-body-texto-cargarExcel">Cargar Cronograma desde Excel</p>
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
		                <label for="descargarArchivoBase" class="control-label">En el siguiente enlace puede descargar el archivo base para cargar una actualización del cronograma desde Excel:</label>
		                <a id="descargarArchivoBase" class="descargarArchivoBase btn btn-primary" download="actualizacionCronogramaLPS.xlsx" href="/archivosBase/actualizacionCronogramaLPS.xlsx">Descargar Archivo Base</a>
		              </div>
		              <div class="form-group parametro_cargarExcel" style="padding: 1px 5px 15px 5px; margin-bottom: 10px">
		                <div class="form_eval form-group">
		                  <h3 id='form_general'> Cargar Cronograma en Excel </h3>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es cargarExcel-->
		                <input type="hidden" id="Id" name="Id" value="">
		                <input type="hidden" id="opcion" name="opcion" value="cargarExcel">
		                <input type="hidden" id="codigo" name="codigo" value="">
		                <!-- Se crea el input para cargar el archivo CSV que cargarà el listado de actividades del proyecto -->
		                <div class="col-sm-12">
		                  <label for="archivoExcel" class="control-label">Seleccione el archivo con el cronograma completo desde el equipo (Solo se permiten archivos en formato XLSX):</label>
		                  <input type="file" name="archivoExcel" id="archivoExcel" class="form-control form-control-lg" accept=".xlsx">
		                  <!-- <input type="submit" value="Enviar" name="archivoExcel"> -->
		                </div>
		              </div>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input id="" type="submit" class="btn btn-success" value="Guardar" aria-label="Guardar carga de Excel">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal" aria-label="Cancelar carga">
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
		<div class="modal_eliminarActualizacion modal fade" id="modalEliminarActualizacion" role="dialog" aria-labelledby="modal_eliminarActualizacionLabel">
		  <div class="modal-dialog modal-m" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalEliminarActualizacionLabel">
		          <p class="modal-body-texto-eliminarActualizacion" id="modal-body-texto-eliminarActualizacion">Eliminar Actualizacion del Cronograma</p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form enctype="multipart/form-data" class="form form-horizontal" id="formEliminarActualizacion" name="formEliminarActualizacion" action="" method="POST">
		              <div class="form-group parametro_cargarExcel" style="padding: 1px 5px 10px 5px; margin-bottom: 10px; border:none">
										<p class='modal-eliminar-semana-body-texto' id='modal-eliminar-semana-body-texto'>¿Desea eliminar esta actualización del cronograma del proyecto?</p>
		              </div>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input id="" type="submit" class="btn btn-primary" value="Aceptar" aria-label="Aceptar eliminar actualización">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal" aria-label="Cancelar eliminar">
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


		<!-- Se crea el Modal que explica el significado de la columna 'Ejecutado Teórico' -->
		<div class='modal fade' id='modal_Ejecutado_Teorico' role='dialog' data-backdrop='static'>
		  <div class='modal-dialog modal-lg'>
		    <!-- Modal content-->
		    <div class='modal-content'>
		      <div class='modal-header'>
		        <h4 class='modal-title' id='modal_Ejecutado_Teorico_Label'>Ejecutado Teórico</h4><button type='button' class='close' data-dismiss='modal'>&times;</button>
		      </div>
		      <div class='modal-body'>
		        <ul style='padding:0% 5%; margin:0'>
		          <p>Requerimiento lineal (en cantidad) del tiempo transcurrido de la actividad sobre la duración total de la misma.</p>
		          <div><img src='/img/formula_ejecutado_teorico.png' style='width:90%; margin:0 5% 0 5%' class='d-inline-block align-top' alt=''></div>
		        </ul>
		      </div>
		      <div class='modal-footer'><button type='button' class='btn btn-default btn-primary' data-dismiss='modal' >Close</button></div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que avisa que la cantidad que se está comprometiendo en una actividad, es inferior a la cantidad sugerida por el programa -->
		<div class="modal fade" id="modal_cantidad_ejecutada_error" role="dialog">
			<div class="modal-dialog modal-lg">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="modal_cantidad_ejecutada_error_Label"><b>Cantidad Ejecutada Mayor</b></h4>
						<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body" style="margin: auto; clear: none; display: flex; align-items: center; justify-content: center">
						<i class="fas fa-exclamation-circle fa-5x" style="color:red;width:20%; height:100%; text-align:center"></i>
						<div class="texto_cantidad_ejecutada_error" style="width:79%; float:left"></div>
					</div>
					<div class="modal-footer">
						<!-- <button type="button" class="btn btn-default btn-primary" id="cambiar_compromiso">Si</button>
						<button type="button" class="btn btn-default btn-danger" id="mantener_compromiso">No</button> -->
						<input id="btn_cantidad_ejecutada_error" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar" aria-label="Cerrar alerta">
					</div>
				</div>
			</div>
		</div>

			<!-- Se crea el Modal que avisa que la cantidad que se está comprometiendo en una actividad, es inferior a la cantidad sugerida por el programa -->
			<div class="modal fade" id="modal_semanal_confirmada" role="dialog">
				<div class="modal-dialog modal-lg">
					<!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modal_semanal_confirmada_Label"><b>Programa General Bloqueado</b></h4>
							<button type="button" class="close" data-dismiss="modal">&times;</button>
						</div>
						<div class="modal-body iconoAlertaSemanalConfirmada" style="margin: auto; clear: none; display: flex; align-items: center; justify-content: center">
							<i class="fas fa-exclamation-circle fa-5x" style="color:red;width:20%; height:100%; text-align:center"></i>
							<div class="texto_semanal_confirmada" style="width:79%; float:left"></div>
						</div>
						<div class="modal-footer">
							<!-- <button type="button" class="btn btn-default btn-primary" id="cambiar_compromiso">Si</button>
							<button type="button" class="btn btn-default btn-danger" id="mantener_compromiso">No</button> -->
							<input id="btn_semanal_confirmada" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar" aria-label="Cerrar alerta confirmación">
						</div>
					</div>
				</div>
			</div>
		</div>

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
			listar();
			guardarCargarExcel();
			eliminarActualizacion();
			eliminarActualizacion();
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
			var semana = document.getElementById('semana').value;

			var semana = document.getElementById('semana').value;

			
			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto";
			var table = $("#dt_cliente").DataTable({
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering":false,
				"autoWidth": false,
				"fixedHeader": false,
				"scrollX": false,
				//                console.log($(document).height());
				"scrollY": alturatabla,
				/*                "scrollCollapse": false,*/
				"responsive": true,
				"paging": false,
				"ajax": {
					"method": "POST",
					"url":"/legacy/programaGeneralActualizar/listar_programaGeneralActualizar.php?db="+db+"&semana="+semana
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [
					{
						'targets': 0,
						'checkboxes': {
							'selectRow': false,
							'visible':false,
						}
					},

					{
						'targets': [1],
						'width': "4%",
					},

					{
						'targets': [3],
						'width': "5%",
					},

					{
						'targets': [4],
						'width': "7%",
					},

					{
						'targets': [5],
						'width': "16%",
					},

					{
						'targets': [6],
						'width': "13%",
					},

					{
						'targets': [8],
						'width': "6%",
					},

					{
						'targets': [9],
						'width': "8%",
					},

					{
						'targets': [10],
						'width': "8%",
					},

					{
						'targets': [11],
						'width': "4%",
					},

					{
						'targets': [12],
						'width': "5%",
					},

					{
						'targets': [13],
						'width': "8%",
					},

					{
						'targets': [14],
						'width': "8%",
					},

					{
						'targets': [15],
						'width': "8%",
					},


					{
						'targets': [4],
						'render': function ( data, type, full, meta ) {
							if(data=="" || data==null){
								data="";
							}else{
							}
							return data;
						},
					},

					{
						'targets': [6],
						'render': function ( data, type, full, meta ) {
							if(data=="*No Asociada*"){
								data="No Asociada";
							}
							return data;
						},
					},

					{
						'targets': [11],
						'render': function ( data, type, full, meta ) {
							if(data===""){
								data="";
							}else if(data==1){
								data="Sí";
							}else if (data==0){
								data="No";
							}
							return data;
						},
					},

					{
						'targets': [13],
						'render': function ( data, type, row, meta) {
							var Unidad= row['unidad'];
							if(Unidad=='' || Unidad==null){
								Unidad='';
							};
							if(data=="" || data==null){
								return data;
							}else{
								return data + " " + Unidad;
							}
						},
					},

					{
						'targets': [14],
						'render': function ( data, type, row, meta) {
							var Unidad= row['unidad'];
							if(Unidad=='' || Unidad==null){
								Unidad='';
							};
							var cantidad_ppto= row['cantidad_ppto'];
							var Cantidad_Ejecutada= (cantidad_ppto * data).toFixed(2);
							if(data=="" || data==null){
								return data;
							}else if(cantidad_ppto=='' || cantidad_ppto==null){
								data=data*100;
								data=data.toFixed(2);
								return data + "%";
							}else{
								data=data*100;
								data=data.toFixed(2);
								return "<p style='margin:0 0 2px 0'>" + Cantidad_Ejecutada + " " + Unidad + "</p><p style='margin:0; font-size:0.9em; color:grey'>(" + data + "%)<p>";
							}
						},
					},

					{
						'targets': [15],
						'render': function ( data, type, row, meta) {
							var Unidad= row['unidad'];
							if(Unidad=='' || Unidad==null){
								Unidad='';
							};
							var cantidad_ppto= row['cantidad_ppto'];
							var Cantidad_Ejecutada=  data ;
							//console.log(data.toFixed(0));
							if(data=="" || data==null){
								return data;
							}else if(cantidad_ppto=='' || cantidad_ppto==null){
								data=data*100;
								data=data.toFixed(2);
								return data + "%";
							}else{
								data=data*100;
								data=data.toFixed(2);
								Cantidad_Ejecutada=(Cantidad_Ejecutada * cantidad_ppto).toFixed(2);
								return "<p style='margin:0 0 2px 0'>" + Cantidad_Ejecutada + " " + Unidad + "</p><p style='margin:0; font-size:0.9em; color:grey'>(" + data + "%)<p>";
							}
						},
					},

					{
						'targets': [17],
						'render': function ( data, type, row, meta ) {
							if(data=="" || data==null){
								data="";
							}else{
								data=data*100;
								data=data.toFixed(0);
							}
							if(row["Titulo"] == 1){
								return data;
							}else{
								return data + "%";
							}

						},
					},

					{
						'targets': [1],
						'render': function ( data, type, full, meta ) {
							var permiso=document.getElementById("permiso").value;
							if(data=="Boton"){
								boton="";
							}else{
								boton="";
							}
							return boton;
						},
					},

					{
							'targets': [1],
							'className': 'Botones'
					},
					{
							'targets': [4],
							'className': 'input_codigo_actividad'
					},
					{
							'targets': [5],
							'className': 'input_Actividad'
					},
					{
							'targets': [6],
							'className': 'select_actividadAsociar'
					},
					{
							'targets': [9],
							'className': 'input_Fecha_Inicio'
					},
					{
							'targets': [10],
							'className': 'input_Fecha_Fin'
					},
					{
							'targets': [12],
							'className': 'input_unidad'
					},
					{
							'targets': [13],
							'className': 'input_cantidad_ppto'
					},
					{
							'targets': [15],
							'className': 'input_Ejecutado'
					},
				],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

			"columns":[
				{"defaultContent":"", "visible":false},
				{"data":"boton"},
				{"data":"Consecutivo_en_Programa", "visible":false},
				{"data":"Id"},
				{"data":"codigo_actividad"},
				{"data":"Actividad"},
				{"data":"programaAnteriorAsociar"},
				{"data":"Titulo", "visible":false},
				{"data":"Semanas_Inicio"},
				{"data":"Fecha_Inicio"},
				{"data":"Fecha_Fin"},
				{"data":"Ruta_Critica"},
				{"data":"unidad"},
				{"data":"cantidad_ppto"},
				{"data":"Ejecutado_Teorico"},
				{"data":"Ejecutado"},
				{"data":"Estado", "visible":false,
					"render": function ( data, type, row, meta) {
						var Titulo= row['Titulo'];
						var Ruta_Critica= row['Ruta_Critica'];
						var Estado= row['Estado'];
						var Ejecutado= row['Ejecutado'];

					if(Titulo == 0 && Ruta_Critica == 1 && (
						Estado == 'En Curso' || Estado == 'Adelantada' || Estado == 'Debe Iniciar esta Semana' || Estado == 'Actividad Futura' ||
						Estado == 'A Tiempo' || Estado == 'Debe Iniciar esta Semana y Restricciones Pendientes' || Estado == 'En Liberación de Restricciones'
					)){
							var icono = "<i class='fas fa-bell fa-xl' style='margin-left:2px; display:inline-block'></i>";
					}else if(Titulo == 0 && Ruta_Critica == 0 && (
						Estado == 'En Curso' || Estado == 'Adelantada' || Estado == 'Debe Iniciar esta Semana' || Estado == 'Actividad Futura' ||
						Estado == 'A Tiempo' || Estado == 'Debe Iniciar esta Semana y Restricciones Pendientes' || Estado == 'En Liberación de Restricciones'
					)){
							var icono = "<i class='fas fa-exclamation fa-xl' style='margin-left:2px; display:inline-block'></i>";
					}else if(Titulo == 0 && Ruta_Critica == 1 && (Estado == 'Atrasada' || Estado == 'Ya Debió Iniciar y Restricciones Pendientes')){
							var icono = "<i class='fas fa-skull-crossbones fa-xl' style='margin-left:2px; display:inline-block'></i>";
					}else if(Titulo == 0 && Ruta_Critica == 0 && (Estado == 'Atrasada' || Estado == 'Ya Debió Iniciar y Restricciones Pendientes')){
							var icono = "<i class='fas fa-radiation fa-xl' style='margin-left:2px; display:inline-block'></i>";
					}else if(Titulo == 0 && (Estado == 'Terminada' || Estado == 'Terminada Antes')){
							var icono = "<i class='fas fa-check-circle fa-xl' style='margin-left:2px; display:inline-block'></i>";
					}else{
							var icono = '';
					}
						return data + "&nbsp" + icono;
					},
				},
				{"data":"Estado_Restricciones", "visible":false},
				{"data":"Responsable_AIA", "visible":false},
				{"data":"Sub_Contratista", "visible":false}
			],

			"rowCallback": function( row, data, index ) {
				var inicio_sem = new Date(document.getElementById('Fecha_Inicio_SemYMD').value);

				var fin_sem = new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+7);

				var inicio_act = new Date(data.Fecha_Inicio);
				var inicio_act = new Date(inicio_act.getFullYear(),inicio_act.getMonth(),inicio_act.getDate()+1);

				var fin_act = new Date(data.Fecha_Fin );
				var fin_act = new Date(fin_act.getFullYear(),fin_act.getMonth(),fin_act.getDate()+1);

				var fecha_intermedia= new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+49);

				if(data.Ejecutado == ""){
					if(data.programaAnteriorAsociar == "*No Asociada*"){
						$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
					}else{
						$('td', row).css('color', '#9e9e9e');
						$('td', row).eq(4).css('background-color', 'rgba(0,191,114,0.9)');
						$('td', row).eq(4).css('color', '#000000');
					}
				}else{
					$('td', row).css('color', '#9e9e9e');
					$('td', row).eq(12).css('background-color', 'rgba(0,191,114,0.9)');
					$('td', row).eq(12).css('color', '#000000');
				}
			},

				"language": idioma_espanol
			});

			// Dynamic Resize Listener
			$(window).off('resize.dtProgGenAct orientationchange.dtProgGenAct aia:viewport-scale-change.dtProgGenAct').on('resize.dtProgGenAct orientationchange.dtProgGenAct aia:viewport-scale-change.dtProgGenAct', function() {
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

			$("div.toolbarFilaBotones").html('<button id="btn_tutorialActualizarCronograma" type="button" class="btn btn-secondary btn-sm" style="max-width:15%; margin-right:5px" onclick="window.open(\'https://youtu.be/meWse2M9ZNg\', \'_blank\')" aria-label="Ver Tutorial: Actualizar Cronograma">Tutorial <i class="fas fa-list-ol fa-lg" aria-hidden="true"></i></button><button id="btn_cargarCronogramaExcel" type="button" class="btn btn-success btn-sm" style="max-width:15%; margin-right:5px" title="Cargar actualización del cronograma desde Excel" data-toggle="modal" data-target="#modalCargarExcel" aria-label="Cargar cronograma desde Excel">Cargar desde Excel <i class="fas fa-upload fa-lg" aria-hidden="true"></i></button><button id="btn_eliminarActualizacion" type="button" class="btn btn-danger btn-sm" style="max-width:15%; margin-right:5px" title="Eliminar actualización del cronograma" data-toggle="modal" data-target="#modalEliminarActualizacion" aria-label="Eliminar actualización">Eliminar Actualización <i class="far fa-trash-alt fa-lg" aria-hidden="true"></i></button>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			activarBuscador("#dt_cliente tbody", table);
			maestroPermisos(document.getElementById('permiso').value);
			obtener_data_editar("#dt_cliente tbody", table);
			//obtener_id_editar("#dt_general tbody", table);
		}


		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		  var permiso = document.getElementById('permiso').value;

			var canEdit = RbacCapabilities.canEditMga(permiso, parseInt(semana), parseInt(max_semana));
			var only_once = !canEdit;

		  $(tbody).one("click", "td", function() {
				//console.log("hola");
				if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					if(data.Titulo==0){
						var Id=$("#Id").val(data.Consecutivo_en_Programa),
						//medir_productividad=$("#medir_productividad").val(data.medir_productividad),
						opcion = $("#opcion").val("modificar");
						var codigo_html_unidad = "<select id='select_unidad' name='unidad' class='form-control form-control-sm'><option value=''></option><option value='ml'>ml</option><option value='m2'>m2</option><option value='m3'>m3</option><option value='un'>Un</option><option value='gl'>Gl</option><option value='kg'>kg</option><option value='%'>%</option><option value='Niveles'>Niveles</option></select>";
						$( this ).parent().find('.input_unidad').html(codigo_html_unidad);

						// Sanitizar cantidad_ppto para evitar value='null'
						var cantidad_ppto_safe = (data.cantidad_ppto === null) ? "" : data.cantidad_ppto;
						var codigo_html_cantidad_ppto = "<input id='input_cantidad_ppto' class='form-control form-control-sm' type='number' min='0' step='0.01' value='"+cantidad_ppto_safe+"'></input>";
						$( this ).parent().find('.input_cantidad_ppto').html(codigo_html_cantidad_ppto);

						var codigo_actividad = <?php
                            // DB available via Database::getInstance() from autoloader
						$dbInstance = Database::getInstance();
						$query = "SELECT * FROM general_codigos_actividades";
						$stmt = $dbInstance->query($query);
						$codigo_actividad = "";
						while ($valores = $stmt->fetch(PDO::FETCH_ASSOC)) {
						    $valor = $valores["codigo_actividad"];
						    $actividad = $valores["actividad"];
						    $vista = $valor . " - " . $actividad;
						    $codigo_actividad .= "<option value='$valor'>$vista</option>";
						};
						echo '"'.$codigo_actividad.'"';
						?>;
						var codigo_html_codigo_actividad = "<select id='select_codigo_actividad' name='codigo_actividad' class='form-control form-control-sm' onchange=bloquear_unidad()><option value=''></option>"+codigo_actividad+"</select>";
						if (permiso=="P"){
							$( this ).parent().find('.input_codigo_actividad').html(codigo_html_codigo_actividad);
							$("#select_codigo_actividad").val(data.codigo_actividad).change();
						}else{
							codigo_html_codigo_actividad = $( this ).parent().find('.input_Actividad').html() + codigo_html_codigo_actividad;
							$( this ).parent().find('.input_Actividad').html(codigo_html_codigo_actividad);
							$("#select_codigo_actividad").val(data.codigo_actividad).change();
							$("#select_codigo_actividad").attr('disabled', true);
							$("#select_codigo_actividad").hide();
						}

						var opciones_codigo_html_actividadAsociar = "<?php
						        // DB available via Database::getInstance() from autoloader
						$dbInstance = Database::getInstance();
						$db = $_SESSION["db"] ?? '';
						$semana = $_SESSION["semana"] ?? 0;

						// Validate db
						$db = preg_replace('/[^a-zA-Z0-9_]/', '', $db);

						$query = "SELECT * FROM {$db}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Consecutivo ASC";
						//AND (D_y_E != 0 OR Materiales != 0 OR MdeO != 0 OR Equipos != 0 OR Predecesora != 0 OR Pdto_Cons != 0 OR Modelo != 0 OR Sub_Contratista IS NOT NULL OR Responsable_AIA IS NOT NULL OR (Observaciones != '' OR Observaciones IS NOT NULL) OR codigo_actividad IS NOT NULL OR medir_productividad = 1 OR cantidad_ppto IS NOT NULL)
						$stmt = $dbInstance->query($query, [$semana]);
						echo "<option value='*No Asociada*'>No Asociada</option>";
						while ($valores = $stmt->fetch(PDO::FETCH_ASSOC)) {
						    $Consecutivo_en_Programa = $valores['Consecutivo_en_Programa'];
						    $Actividad = $valores['Actividad'];
						    $Actividad = str_replace('"', '\"', $Actividad);
						    $Actividad = str_replace("'", "\'", $Actividad);
						    $Fecha_Inicio = $valores['Fecha_Inicio'];
						    $Id = $valores['Id'];
						    echo "<option value='".$Actividad."'>".$Id.". <b>".$Actividad."</b> <small>(Inicia el: ".$Fecha_Inicio.")</small></option>";
						};
						?>";

						var codigo_html_actividadAsociar =  "<select id='select_actividadAsociar' name='actividadAsociar' class='form-control form-control-sm' onchange=actividadAnteriorAsociar()><option value=''></option>";
						codigo_html_actividadAsociar = codigo_html_actividadAsociar + opciones_codigo_html_actividadAsociar + "</select>";
						$( this ).parent().find('.select_actividadAsociar').html(codigo_html_actividadAsociar);
						$('#select_actividadAsociar').select2({
							placeholder: '',
							allowClear: true   // Shows an X to allow the user to clear the value.
						});


						if(data.Ejecutado == null || data.Ejecutado == ""){
							var Ejecutado = "";
						}else{
							if(data.cantidad_ppto == null || data.cantidad_ppto == ""){
								var Ejecutado = (data.Ejecutado * 100).toFixed(2);
							}else{
								var Ejecutado = (data.Ejecutado * data.cantidad_ppto).toFixed(2);
							}
						}

						var codigo_html_ejecutado = "<input id='input_Ejecutado_Editar' name='Ejecutado_Editar' class='form-control form-control-sm' type='number' min='0' max='' step='0.1' value='"+Ejecutado+"'></input>";
						$( this ).parent().find('.input_Ejecutado').html(codigo_html_ejecutado);

						var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><!--<button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>-->";
						$( this ).parent().find('.Botones').html(codigo_html_botones);

						var codigo_html_Fecha_Inicio =  "<input id='select_Fecha_Inicio' name='Fecha_Inicio' class='form-control form-control-sm' type='text' value='"+data.Fecha_Inicio+"'></input>";
						$( this ).parent().find('.input_Fecha_Inicio').html(codigo_html_Fecha_Inicio);


						$( "#select_Fecha_Inicio" ).datepicker({dateFormat: 'yy-mm-dd',
																								 changeMonth: true,
																								 changeYear: true,
																								 showOtherMonths: true,
																								 selectOtherMonths: true,
																								 defaultDate:data.Fecha_Inicio,
																								});

						var codigo_html_Fecha_Fin =  "<input id='select_Fecha_Fin' name='Fecha_Fin' class='form-control form-control-sm' type='text' value='"+data.Fecha_Fin+"'></input>";
						$( this ).parent().find('.input_Fecha_Fin').html(codigo_html_Fecha_Fin);

						$( "#select_Fecha_Fin" ).datepicker({dateFormat: 'yy-mm-dd',
																								 changeMonth: true,
																								 changeYear: true,
																								 showOtherMonths: true,
																								 selectOtherMonths: true,
																								 defaultDate:data.Fecha_Fin,
																								});



						$("#select_medir_productividad").val(data.medir_productividad).change();
						if(data.unidad == "" || data.unidad == null){
							data.unidad = "%";
						}
						$("#select_unidad").val(data.unidad).change();
						$("#input_Ejecutado_Editar").focus();
						$("#input_Ejecutado_Editar").select();
						$("#select_actividadAsociar").val(data.programaAnteriorAsociar).change();
						only_once = false;
						$(document).keyup(function(e){
								if(e.keyCode==13){
									$("#input_Ejecutado_Editar").select();
									$('#select_actividadAsociar').select2('close');
									$("#btn_guardar_editar").click();
									only_once = true;
								}
						});
						$(document).keyup(function(e){
								if(e.keyCode==27){
									$("#input_Ejecutado_Editar").select();
									$('#select_actividadAsociar').select2('close');
									$("#btn_guardar_editar").click();
										//$("#btn_cancelar_editar").click();
									only_once = true;
								}
						});
					}else{
						obtener_data_editar("#dt_cliente tbody", table);
					}
				}
				cancelarEdicionFila();
				guardar();
		  });
		}

		var bloquear_unidad = function() {
			var db = document.getElementById('baseDatos').value
		  if ($("#select_codigo_actividad").val() == '') {
		    $("#select_unidad").attr('disabled', false);
		  } else {
		    $("#select_unidad").attr('disabled', true);
		    opcion = "cargar_unidad";
		    codigo_actividad = $("#select_codigo_actividad").val();
		    $.ajax({
		      method: "POST",
		      url: "/legacy/programa_general/guardar_programa_general.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "opcion": opcion,
		        "codigo_actividad": codigo_actividad
		      }
		    }).done(function(info) {
		      console.log("=== DEBUG: RESPUESTA CRUDA ===", info, "=== TIPO ===", typeof info);
		      try {
		        var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		        unidad = json_info[0];
		        $("#select_unidad").val(unidad).change();
		      } catch(e) {
		        console.error("=== ERROR AL PARSEAR JSON ===");
		        console.error("Error:", e.message);
		        console.error("Contenido recibido (primeros 1000 chars):", typeof info === 'string' ? info.substring(0, 1000) : info);
		        alert("Error: La respuesta del servidor no es JSON válido. Revisa la consola para más detalles.");
		      }
		    });
		  }
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
		  $("#btn_cancelar_editar").one("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}

		var guardarCargarExcel = function() {
		  $("#modalCargarExcel form").on("submit", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
		    var variables = new FormData($("#formCargarExcel")[0]);
				var f_inicio_sem = document.getElementById('Fecha_Inicio_SemYMD').value;
		    //var frm = $(this).serialize();
		    $.ajax({
		      type: "POST",
		      url: "/legacy/programaGeneralActualizar/guardar_programaGeneralActualizar.php?db="+db+"&f_inicio_sem="+f_inicio_sem,
		      contentType: false,
		      processData: false,
		      data: variables,
		    }).done(function(info) {
		      console.log("DEBUG EXCEL:", info, typeof info);
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
					var semana_json = json_info[0];
		      if (semana_json == (Number(semana)+1)) {
		        location.reload();
						$("#modalCargarExcel").modal("hide");
		      }
		      // mostrar_mensaje(json_info);
		      // recargarTabla('');
		    });
		  });
		}

		var eliminarActualizacion = function() {
		  $("#modalEliminarActualizacion form").on("submit", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
		    var opcion = "eliminarActualizacion";
				// console.log(semana);
		    //var frm = $(this).serialize();
		    $.ajax({
		      type: "POST",
		      url: "/legacy/programaGeneralActualizar/guardar_programaGeneralActualizar.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {"semana": semana, "opcion": opcion},
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
					console.log(json_info);
		      if (json_info.respuesta == "BIEN") {
		        location.reload();
						$("#modalEliminarActualizacion").modal("hide");
		      }
		      // mostrar_mensaje(json_info);
		      // recargarTabla('');
		    });
		  });
		}

		var actividadAnteriorAsociar = function() {
			var actividadAsociar = $("#select_actividadAsociar").val();
			if(actividadAsociar == "" || actividadAsociar == null || actividadAsociar == "*No Asociada*"){
				$("#select_codigo_actividad, #select_Fecha_Inicio, #select_Fecha_Fin, #select_unidad, #input_cantidad_ppto, #input_Ejecutado_Editar").attr('disabled', false);
				$("#input_Ejecutado_Editar").prop('readonly', false);
			}else{
				$("#select_codigo_actividad, #select_Fecha_Inicio, #select_Fecha_Fin, #select_unidad, #input_cantidad_ppto, #input_Ejecutado_Editar").attr('disabled', true);
				$("#input_Ejecutado_Editar").prop('readonly', true);
				$("#select_codigo_actividad, #select_unidad, #input_cantidad_ppto, #input_Ejecutado_Editar").val("");
			}
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function() {
			$("#btn_guardar_editar").one("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				if($("#input_Ejecutado_Editar").val() == null || $("#input_Ejecutado_Editar").val() == ""){
					var input_Ejecutado_Editar = "Nulo";
				}else{
					if($("#input_cantidad_ppto").val() == null || $("#input_cantidad_ppto").val() == ""){
						var input_Ejecutado_Editar = $("#input_Ejecutado_Editar").val()/100;
					}else{
						var input_Ejecutado_Editar = $("#input_Ejecutado_Editar").val()/$("#input_cantidad_ppto").val();
					}
				}

				if($("#select_actividadAsociar").val() == null || $("#select_actividadAsociar").val() == ""){
					$("#select_actividadAsociar").val("*No Asociada*");
				}

				//console.log(input_Ejecutado_Editar);
				//var frm = $(".form_modificar").serialize();
				if($("#select_unidad").val() == "" || $("#select_unidad").val() == null){
					var UnidadValor = "%";
					$("#select_unidad").val(UnidadValor).change();
				}else{
					var UnidadValor = $("#select_unidad").val();
				}

				frm="Id="+($("#Id").val())+"&opcion="+($("#opcion").val())+"&Fecha_Inicio="+($("#select_Fecha_Inicio").val())+"&Fecha_Fin="+($("#select_Fecha_Fin").val());

				frm=frm+"&Ejecutado="+input_Ejecutado_Editar+"&codigo_actividad="+($("#select_codigo_actividad").val())+"&unidad="+($("#select_unidad").val())+"&cantidad_ppto="+($("#input_cantidad_ppto").val())+"&actividadAsociar="+($("#select_actividadAsociar").val())+"&editarActividadAsociar=1";
				// console.log(frm);

				if(input_Ejecutado_Editar > 1){
					if($("#input_cantidad_ppto").val() != null && $("#input_cantidad_ppto").val() != ""){
						$(".texto_cantidad_ejecutada_error").html("<p>La cantidad ejecutada no debe ser mayor a la cantidad del presupuesto!! (La cantidad en presupuesto es de <b>"+ ($("#input_cantidad_ppto").val()) + UnidadValor + "</b>, y se está asignando una ejecución de <b>" + (input_Ejecutado_Editar * $("#input_cantidad_ppto").val()) + UnidadValor + "</b>).</p>");
						$("#modal_cantidad_ejecutada_error").modal("show");
					}else{
						$(".texto_cantidad_ejecutada_error").html("<p>La cantidad ejecutada no debe ser mayor al 100% !! (Se está asignando una ejecución de <b>" + (input_Ejecutado_Editar * 100) + UnidadValor + "</b>).</p>");
						$("#modal_cantidad_ejecutada_error").modal("show");
					}
					recargarTabla("listar");
				}else if($("#input_Ejecutado_Editar").val() < 0 /*|| $("#input_Ejecutado_Editar").val() > 100*/){
					$(".texto_cantidad_ejecutada_error").html("<p>La cantidad ejecutada no debe ser un número negativo!! (Se está asignando una ejecución de <b>" + (input_Ejecutado_Editar * 100) + UnidadValor + "</b>).</p>");
					$("#modal_cantidad_ejecutada_error_Label").html("<b>Cantidad Ejecutada Negativa</b>");
					$("#modal_cantidad_ejecutada_error").modal("show");
					recargarTabla("listar");
				}else{
					$.ajax({
						method: "POST",
						url: "/legacy/programa_general/guardar_programa_general.php?db="+db+"&semana="+(Number(semana)+1),
						contenttype:"charset=utf-8",
						data: frm,
					}).done( function( info ){
						limpiar_datos();
						recargarTabla("");
					});
				}
			});
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function( informacion ){
			var texto = "", color = "";
			if( informacion.respuesta == "BIEN" ){
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				color = "#379911";
			}else if( informacion.respuesta == "ERROR"){
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				color = "#C9302C";
			}else if( informacion.respuesta == "EXISTE" ){
				texto = "<strong>Información!</strong> el usuario ya existe.";
				color = "#5b94c5";
			}else if( informacion.respuesta == "VACIO" ){
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				color = "#ddb11d";
			}else if( informacion.respuesta == "OPCION_VACIA" ){
				texto = "<strong>Advertencia!</strong> la opción no existe o esta vacia, recargar la página.";
				color = "#ddb11d";
			}

			$(".mensaje").html( texto ).css({"color": color });
			$(".mensaje").fadeOut(5000, function(){
				$(this).html("");
				$(this).fadeIn(3000);
			});
		}

		/*limpia los valores del formulario de registro*/
		var limpiar_datos = function() {
			$("#opcion").val("registrar");
			$("#Id").val("");
			$("#Ejecutado").val("").focus();
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
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
		}

		var ocultos=function(table){
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		  var permiso = document.getElementById('permiso').value;

			var canEdit = RbacCapabilities.canEditMga(permiso, parseInt(semana), parseInt(max_semana));
			var isReadOnly = RbacCapabilities.isReadOnly(permiso);

			if (!canEdit) {
				$('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
				table.column( 4 ).visible( false );
				table.column( 1 ).visible( false );
			}
			
			if (isReadOnly || permiso == "C") {
				if (permiso == "C") {
					$('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia').css('display', 'none');
					table.column( 4 ).visible( false );
					table.column( 1 ).visible( false );
				}
			}

			if (permiso == "A" || permiso == "R") {
				table.column( 4 ).visible( false );
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
