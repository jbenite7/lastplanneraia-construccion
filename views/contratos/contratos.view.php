<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_contratos" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="codigo" name="codigo" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100">
			<table id="dt_cliente" class="dt_infoGeneral table table-bordered table-hover table-responsive-sm table-sm w-100" cellspacing="0" width="100%">
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
						<th>SI1</th>
						<th>paqueteSI1</th>
						<th>SI2</th>
						<th>paqueteSI2</th>
						<th>SI3</th>
						<th>paqueteSI3</th>
						<th>SI4</th>
						<th>paqueteSI4</th>
						<th>SI5</th>
						<th>paqueteSI5</th>
						<th>S1</th>
						<th>paqueteS1</th>
						<th>S2</th>
						<th>paqueteS2</th>
						<th>S3</th>
						<th>paqueteS3</th>
						<th>S4</th>
						<th>paqueteS4</th>
						<th>S5</th>
						<th>paqueteS5</th>
						<th>MO1</th>
						<th>paqueteMO1</th>
						<th>MO2</th>
						<th>paqueteMO2</th>
						<th>MO3</th>
						<th>paqueteMO3</th>
						<th>MO4</th>
						<th>paqueteMO4</th>
						<th>MO5</th>
						<th>paqueteMO5</th>
						<th>Paquetes de Contratación Asociados</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<div class="modal_EditarContratos modal fade" id="modalEditarContratos" role="dialog" aria-labelledby="modal_EditarContratosLabel">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="modalEditarContratosLabel"><p class="modal-body-texto-EditarContratos" id="modal-body-texto-EditarContratos"></p></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" onclick="recargarTabla('listar')">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form class="form form-horizontal" action="" method="POST">
									<input type="hidden" id="tipoContrato" name="tipoContrato" value="">
									<input type="hidden" id="actividadModificar" name="actividadModificar" value="">
									<div class="form-group parametro_EditarContratos" id="parametro_EditarContratosS">
										<div class="form_eval form-group">
											<h3 id='form_general'>Contratos de Suministro</h3>
										</div>

										<!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->



										<!-- Se crean los inputs del formulario de contratos de suministro -->
										<div class="col-sm-12">
											<br>
											<label for="S1" class="control-label ct-col-id"></label>
											<label for="paqueteS1" class="control-label ct-col-main"><h5>Paquete de Contratación</h5></label>
											<label for="S1" class="control-label ct-col-main"><h5>Insumo / Recurso</h5></label>

											<label for="paqueteS1" class="control-label ct-col-id"><h5>1.</h5></label>
											<select id="paqueteS1" name="paqueteS1" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro 1">
												<option value=""></option>
											</select>
											<select id="S1" name="S1[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro 1">
												<option value=""></option>
											</select>

											<label for="paqueteS2" class="control-label ct-col-id"><h5>2.</h5></label>
											<select id="paqueteS2" name="paqueteS2" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro 2">
												<option value=""></option>
											</select>
											<select id="S2" name="S2[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro 2">
												<option value=""></option>
											</select>

											<label for="paqueteS3" class="control-label ct-col-id"><h5>3.</h5></label>
											<select id="paqueteS3" name="paqueteS3" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro 3">
												<option value=""></option>
											</select>
											<select id="S3" name="S3[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro 3">
												<option value=""></option>
											</select>

											<label for="paqueteS4" class="control-label ct-col-id"><h5>4.</h5></label>
											<select id="paqueteS4" name="paqueteS4" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro 4">
												<option value=""></option>
											</select>
											<select id="S4" name="S4[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro 4">
												<option value=""></option>
											</select>

											<label for="paqueteS5" class="control-label ct-col-id"><h5>5.</h5></label>
											<select id="paqueteS5" name="paqueteS5" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro 5">
												<option value=""></option>
											</select>
											<select id="S5" name="S5[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro 5">
												<option value=""></option>
											</select>
										</div>
										<br>
									</div>

									<div class="form-group parametro_EditarContratos" id="parametro_EditarContratosMO">
										<div class="form_eval form-group">
											<h3 id='form_general'>Contratos de Mano de Obra</h3>
										</div>

										<!-- Se crean los inputs del formulario de contratos de mano de obra -->
										<div class="col-sm-12">
											<br>
											<label for="MO1" class="control-label ct-col-id"></label>
											<label for="paqueteMO1" class="control-label ct-col-main"><h5>Paquete de Contratación</h5></label>
											<label for="MO1" class="control-label ct-col-main"><h5>Insumo / Recurso</h5></label>

											<label for="paqueteMO1" class="control-label ct-col-id"><h5>1.</h5></label>
											<select id="paqueteMO1" name="paqueteMO1" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Mano de Obra 1">
												<option value=""></option>
											</select>
											<select id="MO1" name="MO1[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Mano de Obra 1">
												<option value=""></option>
											</select>

											<label for="paqueteMO2" class="control-label ct-col-id"><h5>2.</h5></label>
											<select id="paqueteMO2" name="paqueteMO2" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Mano de Obra 2">
												<option value=""></option>
											</select>
											<select id="MO2" name="MO2[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Mano de Obra 2">
												<option value=""></option>
											</select>

											<label for="paqueteMO3" class="control-label ct-col-id"><h5>3.</h5></label>
											<select id="paqueteMO3" name="paqueteMO3" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Mano de Obra 3">
												<option value=""></option>
											</select>
											<select id="MO3" name="MO3[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Mano de Obra 3">
												<option value=""></option>
											</select>

											<label for="paqueteMO4" class="control-label ct-col-id"><h5>4.</h5></label>
											<select id="paqueteMO4" name="paqueteMO4" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Mano de Obra 4">
												<option value=""></option>
											</select>
											<select id="MO4" name="MO4[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Mano de Obra 4">
												<option value=""></option>
											</select>

											<label for="paqueteMO5" class="control-label ct-col-id"><h5>5.</h5></label>
											<select id="paqueteMO5" name="paqueteMO5" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Mano de Obra 5">
												<option value=""></option>
											</select>
											<select id="MO5" name="MO5[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Mano de Obra 5">
												<option value=""></option>
											</select>
										</div>
										<br>
									</div>

									<div class="form-group parametro_EditarContratos" id="parametro_EditarContratosSI">
										<div class="form_eval form-group">
											<h3 id='form_general'>Contratos de Suministro e Instalación</h3>
										</div>

										<!-- Se crean los inputs del formulario de contratos de mano de obra -->
										<div class="col-sm-12">
											<br>
											<label for="SI1" class="control-label ct-col-id"></label>
											<label for="paqueteSI1" class="control-label ct-col-main"><h5>Paquete de Contratación</h5></label>
											<label for="SI1" class="control-label ct-col-main"><h5>Insumo / Recurso</h5></label>

											<label for="paqueteSI1" class="control-label ct-col-id"><h5>1.</h5></label>
											<select id="paqueteSI1" name="paqueteSI1" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro e Instalación 1">
												<option value=""></option>
											</select>
											<select id="SI1" name="SI1[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro e Instalación 1">
												<option value=""></option>
											</select>

											<label for="paqueteSI2" class="control-label ct-col-id"><h5>2.</h5></label>
											<select id="paqueteSI2" name="paqueteSI2" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro e Instalación 2">
												<option value=""></option>
											</select>
											<select id="SI2" name="SI2[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro e Instalación 2">
												<option value=""></option>
											</select>

											<label for="paqueteSI3" class="control-label ct-col-id"><h5>3.</h5></label>
											<select id="paqueteSI3" name="paqueteSI3" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro e Instalación 3">
												<option value=""></option>
											</select>
											<select id="SI3" name="SI3[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro e Instalación 3">
												<option value=""></option>
											</select>

											<label for="paqueteSI4" class="control-label ct-col-id"><h5>4.</h5></label>
											<select id="paqueteSI4" name="paqueteSI4" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro e Instalación 4">
												<option value=""></option>
											</select>
											<select id="SI4" name="SI4[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro e Instalación 4">
												<option value=""></option>
											</select>

											<label for="paqueteSI5" class="control-label ct-col-id"><h5>5.</h5></label>
											<select id="paqueteSI5" name="paqueteSI5" type="text" class="form-control ct-col-main ct-input-flat" aria-label="Paquete Suministro e Instalación 5">
												<option value=""></option>
											</select>
											<select id="SI5" name="SI5[]" type="text" class="form-control ct-col-main ct-input-flat" multiple="multiple" aria-label="Insumos Suministro e Instalación 5">
												<option value=""></option>
											</select>
										</div>
										<br>
									</div>

									<!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
									<div class="col-sm-offset-2 col-sm-8">
										<p class="mensaje"></p>
									</div>

									<div class="form-group">
										<div class="col-sm-12">
											<input id="btn_guardar_contratos" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar contratos">
											<input id="btn_cancelar_contratos" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal" aria-label="Cancelar edición">
										</div>
									</div>
								</form>
							</div>
						</div>
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
			$('#S1,#S2,#S3,#S4,#S5,#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5,#MO1,#MO2,#MO3,#MO4,#MO5,#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5,#SI1,#SI2,#SI3,#SI4,#SI5,#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5').select2({tags: true, placeholder:'', allowClear: true});
      listar();
		}

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  recargarTabla("listar");
		  limpiar_datos();
		});

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_cancelar").on("click", function() {
		  location.reload();
		});

		var cancelarEdicionFila = function() {
		  $("#btn_cancelar_contratos").on("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
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
			var Max_Semana = document.getElementById('Max_Semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
		  
			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto";

		  var table = $("#dt_cliente").DataTable({
		    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
		    "destroy": true,
		    "ordering": false,
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
		      "url": "/api/contratos/list?db="+db+"&semana="+Max_Semana
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [
					{
					'targets': [8],
					'width':'14%',
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
							'targets': [0],
							'width':'4%',
					},
					{
							'targets': [2],
							'width':'8%',
					},
					{
							'targets': [3],
							'width':'18%',
					},
					{
							'targets': [4],
							'width':'24%',
					},
					{
							'targets': [7],
							'width':'10%',
					},
					{
							'targets': [40],
							'width':'22%',
					},
					{
							'targets': [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40],
							'render': function ( data, type, full, meta ) {
							 return data;
							},
					},
				],
				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm btn-action-gap'  title='Editar'><i class='fa fa-edit fa-xs'></i></button><!--<button type='button' class='eliminar btn btn-danger btn-sm btn-action-gap'  title='Eliminar' data-toggle='modal' data-target='#modalEliminar' ><i class='fa fa-trash-alt fa-xs'></i></button>-->"},
						{"data":"Id", "visible":false},
						{"data":"codigo"},
						{"data":"actividad"},
						{"data":"descripcionActividad"},
						{"data":"actividadInicio", "visible":false},
						{"data":"nombreActividadInicio", "visible":false},
						{"data":"fechaInicio"},
						{"data":"tipoContrato"},
						{"data":"semanaActualizacion", "visible":false},
						{"data":"SI1", "visible":false},
						{"data":"paqueteSI1", "visible":false},
						{"data":"SI2", "visible":false},
						{"data":"paqueteSI2", "visible":false},
						{"data":"SI3", "visible":false},
						{"data":"paqueteSI3", "visible":false},
						{"data":"SI4", "visible":false},
						{"data":"paqueteSI4", "visible":false},
						{"data":"SI5", "visible":false},
						{"data":"paqueteSI5", "visible":false},
						{"data":"S1", "visible":false},
						{"data":"paqueteS1", "visible":false},
						{"data":"S2", "visible":false},
						{"data":"paqueteS2", "visible":false},
						{"data":"S3", "visible":false},
						{"data":"paqueteS3", "visible":false},
						{"data":"S4", "visible":false},
						{"data":"paqueteS4", "visible":false},
						{"data":"S5", "visible":false},
						{"data":"paqueteS5", "visible":false},
						{"data":"MO1", "visible":false},
						{"data":"paqueteMO1", "visible":false},
						{"data":"MO2", "visible":false},
						{"data":"paqueteMO2", "visible":false},
						{"data":"MO3", "visible":false},
						{"data":"paqueteMO3", "visible":false},
						{"data":"MO4", "visible":false},
						{"data":"paqueteMO4", "visible":false},
						{"data":"MO5", "visible":false},
						{"data":"paqueteMO5", "visible":false},
						{"data":"contratosAsociados"}
				],
		    "language": idioma_espanol
		  });

			// Dynamic Resize Listener
			$(window).off('resize.dtContratos orientationchange.dtContratos aia:viewport-scale-change.dtContratos').on('resize.dtContratos orientationchange.dtContratos aia:viewport-scale-change.dtContratos', function() {
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

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Basic example"><button id="btn_nuevo" type="button" class="btn btn-primary btn-sm ps-btn-gap" title="Registrar nuevo contrato" data-toggle="modal" data-target="#modal_nuevo_contrato">Nuevo Contrato <i class="fas fa-plus"></i></button><button id="btn_tutorialActualizarCronograma" type="button" class="btn btn-secondary btn-sm ps-btn-gap" title="Video tutorial de la sección de \'\'Contratos\'\'" onclick="window.open(\'https://youtu.be/7zNHgNC7O_8\', \'_blank\')">Tutorial <i class="fas fa-list-ol fa-lg"></i></button></div><div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_planCompras" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=planCompras&semana='+semana+'\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaBotones .grupo_botones_semanal_madre")
				.addClass("ps-toolbar-nav-wrap")
				.html('<div class="ps-module-switcher" role="tablist" aria-label="Navegacion general"><button id="btn_Actividades" type="button" class="ps-module-tab" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'" aria-label="Ir a Actividades"><i class="fas fa-table" aria-hidden="true"></i><span>Actividades</span></button><button id="btn_contratos" type="button" class="ps-module-tab is-active" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'" aria-label="Ir a Contratos" aria-current="page"><i class="fas fa-file-alt" aria-hidden="true"></i><span>Contratos</span></button><button id="btn_planCompras" type="button" class="ps-module-tab" onclick="window.location.href=\'/construccion/cambiar_pagina.php?seccion=planCompras&semana='+semana+'\'" aria-label="Ir a Plan de Compras"><i class="fas fa-shopping-cart" aria-hidden="true"></i><span>Plan de Compras</span></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="d-flex ml-auto"><label for="input_buscador" class="sr-only">Buscar en contratos</label><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm mr-1 ml-auto max-w-60" placeholder="Filtro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso').value);
			activarBuscador("#dt_cliente tbody", table);
		  obtener_data_editar("#dt_cliente tbody", table);
		}


		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  var permiso = document.getElementById('permiso').value;
			var db = document.getElementById('baseDatos').value;
		  if (permiso=="C") {
		    var only_once = false;
		  } else {
		    var only_once = true;
		  }
		  $(tbody).one("click", "td", function(e) {
				e.stopPropagation()
				var data= table.row($(this).parents("tr")).data();
				var Id=$("#Id").val(data.Id),
						opcion = $("#opcion").val("modificar");
		    if (only_once == true) {
					var tipoContrato = data.tipoContrato;
					document.getElementById('tipoContrato').value = tipoContrato;
					document.getElementById('actividadModificar').value = data.actividad;

					$.ajax({
						method: "POST",
						url: "/api/contratos/save?db="+db,
						contenttype: "charset=utf-8",
						data: {"opcion": "actualizarListadoPaquetesContratacion", "tipoContrato": tipoContrato}
					}).done(function(info) {
						var json_info = (typeof info === 'string' ? JSON.parse(info) : info);

						$.ajax({
							method: "POST",
							url: "/api/contratos/save?db="+db,
							contenttype: "charset=utf-8",
							data: {"opcion": "actualizarInsumosRecursos", "tipoContrato": tipoContrato}
						}).done(function(info) {
							var json_info2 = (typeof info === 'string' ? JSON.parse(info) : info);
							if(tipoContrato==1){

								$("#S1, #S2, #S3, #S4, #S5").html(json_info2["listadoS"]).change();

								$("#MO1, #MO2, #MO3, #MO4, #MO5").html(json_info2["listadoMO"]).change();

								$("#SI1, #SI2, #SI3, #SI4, #SI5").html('<option value=""></option>').change();

								$("#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5").html(json_info["listadoS"]).change();

								$("#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5").html(json_info["listadoMO"]).change();

								$("#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5").html('<option value=""></option>').change();

								$("#parametro_EditarContratosSI").css('display', 'none');
								$("#parametro_EditarContratosMO").css('display', 'block');
								$("#parametro_EditarContratosS").css('display', 'block');

								$("#SI1").val('').change();
								$("#SI2").val('').change();
								$("#SI3").val('').change();
								$("#SI4").val('').change();
								$("#SI5").val('').change();
								$("#paqueteSI1").val('').change();
								$("#paqueteSI2").val('').change();
								$("#paqueteSI3").val('').change();
								$("#paqueteSI4").val('').change();
								$("#paqueteSI5").val('').change();

								var MO1 = (data.MO1 && data.MO1 != '') ? $("#MO1").val(data.MO1.split(';')).change() : '';
								var MO2 = (data.MO2 && data.MO2 != '') ? $("#MO2").val(data.MO2.split(';')).change() : '';
								var MO3 = (data.MO3 && data.MO3 != '') ? $("#MO3").val(data.MO3.split(';')).change() : '';
								var MO4 = (data.MO4 && data.MO4 != '') ? $("#MO4").val(data.MO4.split(';')).change() : '';
								var MO5 = (data.MO5 && data.MO5 != '') ? $("#MO5").val(data.MO5.split(';')).change() : '';
								$("#paqueteMO1").val(data.paqueteMO1).change();
								$("#paqueteMO2").val(data.paqueteMO2).change();
								$("#paqueteMO3").val(data.paqueteMO3).change();
								$("#paqueteMO4").val(data.paqueteMO4).change();
								$("#paqueteMO5").val(data.paqueteMO5).change();

								var S1 = (data.S1 && data.S1 != '') ? $("#S1").val(data.S1.split(';')).change() : '';
								var S2 = (data.S2 && data.S2 != '') ? $("#S2").val(data.S2.split(';')).change() : '';
								var S3 = (data.S3 && data.S3 != '') ? $("#S3").val(data.S3.split(';')).change() : '';
								var S4 = (data.S4 && data.S4 != '') ? $("#S4").val(data.S4.split(';')).change() : '';
								var S5 = (data.S5 && data.S5 != '') ? $("#S5").val(data.S5.split(';')).change() : '';
								$("#paqueteS1").val(data.paqueteS1).change();
								$("#paqueteS2").val(data.paqueteS2).change();
								$("#paqueteS3").val(data.paqueteS3).change();
								$("#paqueteS4").val(data.paqueteS4).change();
								$("#paqueteS5").val(data.paqueteS5).change();
							}else if(tipoContrato==2){

								$("#S1, #S2, #S3, #S4, #S5").html('<option value=""></option').change();

								$("#MO1, #MO2, #MO3, #MO4, #MO5").html('<option value=""></option').change();

								$("#SI1, #SI2, #SI3, #SI4, #SI5").html(json_info2["listadoSI"]).change();

								$("#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5").html('<option value=""></option').change();

								$("#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5").html('<option value=""></option').change();

								$("#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5").html(json_info["listadoSI"]).change();

								$("#parametro_EditarContratosSI").css('display', 'block');
								$("#parametro_EditarContratosMO").css('display', 'none');
								$("#parametro_EditarContratosS").css('display', 'none');

								var SI1 = (data.SI1 && data.SI1 != '') ? $("#SI1").val(data.SI1.split(';')).change() : '';
								var SI2 = (data.SI2 && data.SI2 != '') ? $("#SI2").val(data.SI2.split(';')).change() : '';
								var SI3 = (data.SI3 && data.SI3 != '') ? $("#SI3").val(data.SI3.split(';')).change() : '';
								var SI4 = (data.SI4 && data.SI4 != '') ? $("#SI4").val(data.SI4.split(';')).change() : '';
								var SI5 = (data.SI5 && data.SI5 != '') ? $("#SI5").val(data.SI5.split(';')).change() : '';
								$("#paqueteSI1").val(data.paqueteSI1).change();
								$("#paqueteSI2").val(data.paqueteSI2).change();
								$("#paqueteSI3").val(data.paqueteSI3).change();
								$("#paqueteSI4").val(data.paqueteSI4).change();
								$("#paqueteSI5").val(data.paqueteSI5).change();

								$("#MO1").val('').change();
								$("#MO2").val('').change();
								$("#MO3").val('').change();
								$("#MO4").val('').change();
								$("#MO5").val('').change();
								$("#paqueteMO1").val('').change();
								$("#paqueteMO2").val('').change();
								$("#paqueteMO3").val('').change();
								$("#paqueteMO4").val('').change();
								$("#paqueteMO5").val('').change();

								$("#S1").val('').change();
								$("#S2").val('').change();
								$("#S3").val('').change();
								$("#S4").val('').change();
								$("#S5").val('').change();
								$("#paqueteS1").val('').change();
								$("#paqueteS2").val('').change();
								$("#paqueteS3").val('').change();
								$("#paqueteS4").val('').change();
								$("#paqueteS5").val('').change();
							}
						});
					});

		      only_once = false;

					$("#modalEditarContratos").modal("show");
					$("#modal-body-texto-EditarContratos").html("Formulario de Registro de Contratos; Actividad: <b>" + data.actividad + "</b>");

					$("#btn_cancelar_editar").on("click", function(){
							only_once = true;
					});
		    }
		    cancelarEdicionFila();
		    guardar_modificar();
		  });
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar_modificar = function() {
		  $("#btn_guardar_contratos").one("click", function(e) {
		    e.preventDefault();
		    var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var Id = document.getElementById('Id').value;
				var opcion = document.getElementById('opcion').value;
		    var frm = $("form").serialize();
		    frm = frm + "&Id=" + Id + "&opcion=" + opcion + "&semana=" + semana;
		    // console.log(frm);
		    $.ajax({
		      method: "POST",
		      url: "/api/contratos/save?db="+db,
		      contenttype: "charset=utf-8",
		      data: frm,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      //mostrar_mensaje(json_info);
		      // console.log(json_info);
		      if (json_info.respuesta == "BIEN") {
		        // var posicion = $('.dataTables_scrollBody').scrollTop();
		        $("#modalEditarContratos").modal("hide");
		        // location.assign("posicion_contratos.php?posicion_contratos=" + posicion);
						recargarTabla();
		      } else {
						$(".mensaje").html(json_info["respuesta"]).css({
							"color": "#C9302C"
						});
						$(".mensaje").fadeOut(5000, function() {
							$(this).html("");
							$(this).fadeIn(3000);
						});
					}
		    });
		  });
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
			var texto = "",
				color = "success", // Default to success class
                borderClass = "success";

			if (informacion.respuesta == "BIENNuevaActividad" || informacion.respuesta == "BIENCargarExcel") {
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				borderClass = "success";
			} else if (informacion.respuesta == "ERROR") {
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				borderClass = "error";
			} else if (informacion.respuesta == "EXISTE") {
				texto = "<strong>Información!</strong> La actividad que estás intentando registrar ya existe.";
				borderClass = "error";
			} else if (informacion.respuesta == "VACIO") {
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				borderClass = "warning";
			} else if (informacion.respuesta == "NO_ELIMINAR") {
				texto = "<strong>Advertencia!</strong> No se puede eliminar esta actividad.";
				borderClass = "warning";
			} else if (informacion.respuesta == "BIEN") {
                // Generic BIEN response handling if applicable
                texto = "<strong>Bien!</strong> Operación realizada correctamente.";
                borderClass = "success";
            } else {
                // Fallback for direct text messages
                texto = informacion.respuesta || "Mensaje del sistema";
                // Simple heuristic for error messages if they contain "Error"
                if(texto.toLowerCase().includes("error")) borderClass = "error";
            }

            // Hide modals if needed
            $("#modalNuevaActividad").modal("hide");
            $("#modalCargarExcel").modal("hide");
            $("#modalEditarContratos").modal("hide");

            // Show Toast
             var toast = $("#mensajeActualizacion");
             toast.removeClass("success error warning").addClass("custom-toast " + borderClass);
             toast.html(texto);
             toast.show().delay(4000).fadeOut(1000, function(){
                 $(this).html("");
             });
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
		    // $('#dt_cliente').empty();
		    // listar();
				table.ajax.reload();
		    obtener_data_editar("#dt_cliente tbody", table);
		  } else {
		    table.ajax.reload();
		    obtener_data_editar("#dt_cliente tbody", table);
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
