<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="programa_general">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-8 col-md-8 col-lg-8 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
		<div class="col-sm-4 col-md-4 col-lg-4 mr-0 ml-auto" id="textoFechaCreacionSemana" style="text-align:right">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_programaGeneral table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Codigo Actividad</th>
						<th>Actividad</th>
						<th>Título</th>
						<th>Semana Para Iniciar</th>
						<th>Fecha Inicio</th>
						<th>Fecha Fin</th>
						<th>Crítica</th>
						<th>Unidad</th>
						<th>Cantidad en Presupuesto</th>
						<th>Ejecutado Teórico<button type= 'button' class='pregunta_Ejecutado_Teorico btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_Ejecutado_Teorico' style='padding:0; margin-left:5px; display:inline-block'><i style='color:white' class='fas fa-question-circle fa-sm'></i></button></th>
						<th>Ejecutado Real</th>
						<th>Estado</th>
						<th>Liberación Restricciones</th>
						<th>Responsable AIA</th>
						<th>Sub-Contratista</th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th><input type="text" id="buscadorActividad" placeholder="Buscar" style="width:80%"></th>
						<th></th>
						<th>
							<select style="width:80%" id="buscadorSemanasInicio">
								<option value="">Todas</option>
								<option value="0">0</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">Más de 6</option>
							</select>
						</th>
						<th></th>
						<th></th>
						<th>
							<select style="width:80%" id="buscadorCritica">
								<option value="">Todas</option>
								<option value="Sí">Crítica</option>
								<option value="No">No Crítica</option>
							</select>
						</th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th>
							<select style="width:80%" id="buscadorEstado">
								<option value="">Todas</option>
								<option value="A Tiempo">A Tiempo</option>
								<option value="Debe Iniciar esta Semana">Debe Iniciar esta Semana</option>
								<option value="Debe Iniciar esta Semana y Restricciones Pendientes">Debe Iniciar esta Semana y Restricciones Pendientes</option>
								<option value="Ya Debió Iniciar y Restricciones Pendientes">Ya Debió Iniciar y Restricciones Pendientes</option>
								<option value="Atrasada">Atrasada</option>
								<option value="Terminada">Terminada</option>
								<option value="Terminada Antes">Terminada Antes</option>
								<option value="En Liberación de Restricciones">En Liberación de Restricciones</option>
								<option value="No Requerida">No Requerida</option>
							</select>
						</th>
						<th></th>
						<th></th>
						<th></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- Se crea el Modal que explica el significado de la columna 'Ejecutado Teórico' -->
		<div class='modal fade' id='modal_Ejecutado_Teorico' role='dialog' data-backdrop='static'>
		  <div class='modal-dialog modal-lg'>
		    <!-- Modal content-->
		    <div class='modal-content'>
		      <div class='modal-header'>
		        <h4 class='modal-title' id='modal_Ejecutado_Teorico_Label'>Ejecutado Teórico</h4><button type='button' class='close' data-dismiss='modal' onclick='actualizarBarraFiltros(document.getElementById("baseDatos").value, document.getElementById("semana").value, "siListar")'>&times;</button>
		      </div>
		      <div class='modal-body'>
		        <ul style='padding:0% 5%; margin:0'>
		          <p>Requerimiento lineal (en cantidad) del tiempo transcurrido de la actividad sobre la duración total de la misma.</p>
		          <div><img src='../imagenes/formula_ejecutado_teorico.png' style='width:90%; margin:0 5% 0 5%' class='d-inline-block align-top' alt=''></div>
		        </ul>
		      </div>
		      <div class='modal-footer'><button type='button' class='btn btn-default btn-primary' data-dismiss='modal' onclick='actualizarBarraFiltros(document.getElementById("baseDatos").value, document.getElementById("semana").value, "siListar")'>Close</button></div>
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
						<input id="btn_cantidad_ejecutada_error" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar">
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
							<input id="btn_semanal_confirmada" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar">
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
			actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var scriptBarraFiltros = document.getElementById('scriptBarraFiltros').value;
			var fechaCreacionSemana = document.getElementById('fechaCreacionSemana').value;
			var versionCronograma = document.getElementById('versionCronograma').value;
			if(fechaCreacionSemana=='' || fechaCreacionSemana==null){
			}else{
				document.getElementById('textoFechaCreacionSemana').innerHTML = "<p>Semana Creada el <b>" + fechaCreacionSemana + "</b>&nbsp&nbsp&nbsp(Cronograma <b>V"+ versionCronograma +"</b>)</p>";
			}



			/*Identificamos la altura de la hoja para determinar la altura de la tabla*/
			var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 220) + "px";
			var table = $("#dt_cliente").DataTable({
				/* "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>", */
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>>t<'row'<'col-md-6'i>><'clear'>",
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
					"url":"../programa_general/listar_programa_general.php?db="+db+"&semana="+semana+scriptBarraFiltros
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [
					{
						'targets': 0,
						'width': "5%",
						'checkboxes': {
							'selectRow': false,
							'visible':false,
						}
					},

					{
						'targets': [1],
						'width': "2%",
					},

					{
						'targets': [5],
						'width': "15%",
					},

					{
						'targets': [2,3,4,6,7,8,9,10,11,12,13,14,15,16,17,18],
						'width': "5%",
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
						'targets': [10],
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
						'targets': [12],
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
						'targets': [13],
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
						'targets': [14],
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
						'targets': [16],
						'render': function ( data, type, row, meta ) {
							var Titulo= row['Titulo'];
							if(data=="" || data==null || Titulo==1){
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
							'targets': [8],
							'className': 'input_Fecha_Inicio'
					},
					{
							'targets': [9],
							'className': 'input_Fecha_Fin'
					},
					{
							'targets': [11],
							'className': 'input_unidad'
					},
					{
							'targets': [12],
							'className': 'input_cantidad_ppto'
					},
					{
							'targets': [14],
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
				{"data":"Titulo", "visible":false},
				{"data":"Semanas_Inicio"},
				{"data":"Fecha_Inicio"},
				{"data":"Fecha_Fin"},
				{"data":"Ruta_Critica"},
				{"data":"unidad"},
				{"data":"cantidad_ppto"},
				{"data":"Ejecutado_Teorico"},
				{"data":"Ejecutado"},
				{"data":"Estado",
					"render": function ( data, type, row, meta) {
						var Titulo= row['Titulo'];
						var Ruta_Critica= row['Ruta_Critica'];
						var Estado= row['Estado'];
						var Ejecutado= row['Ejecutado'];

						if(Titulo == 0 && Ruta_Critica == 1 && (Estado == 'A Tiempo' || Estado == 'Debe Iniciar esta Semana' || Estado == 'Debe Iniciar esta Semana y Restricciones Pendientes')){
							var icono = "<i class='fas fa-bell fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else if(Titulo == 0 && Ruta_Critica == 0 && (Estado == 'A Tiempo' || Estado == 'Debe Iniciar esta Semana' || Estado == 'Debe Iniciar esta Semana y Restricciones Pendientes')){
							var icono = "<i class='fas fa-exclamation fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else if(Titulo == 0 && Ruta_Critica == 1 && (Estado == 'Atrasada' || Estado == 'Ya Debió Iniciar y Restricciones Pendientes')){
							var icono = "<i class='fas fa-skull-crossbones fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else if(Titulo == 0 && Ruta_Critica == 0 && (Estado == 'Atrasada' || Estado == 'Ya Debió Iniciar y Restricciones Pendientes')){
							var icono = "<i class='fas fa-radiation fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else if(Titulo == 0 && (Estado == 'Terminada' || Estado == 'Terminada Antes')){
							var icono = "<i class='fas fa-check-circle fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else if(Titulo == 0 && Estado == 'En Liberación de Restricciones'){
							var icono = "<i class='fas fa-clock fa-xl' style='margin-left:2px; display:inline-block'></i>";
						}else{
							var icono = '';
						}
						return data + "&nbsp" + icono;
					},
				},
				{"data":"Estado_Restricciones"},
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

				if(data.Titulo!=0){
					$('td', row).css('background-color', 'rgba(3,87,102,1)');
					$('td', row).css('color', '#ffffff');
				}else if(data.Ruta_Critica==="1" && (data.Estado=="Atrasada" || data.Estado=="Ya Debió Iniciar y Restricciones Pendientes") && data.Ejecutado<1){
					$('td', row).css('background-color', '#7c1c51');
					$('td', row).css('color', '#ffffff');
				}else if(data.Ruta_Critica==="0" && (data.Estado=="Atrasada" || data.Estado=="Ya Debió Iniciar y Restricciones Pendientes") && data.Ejecutado<1){
					$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
				}else if(data.Estado == "A Tiempo" || data.Estado == "Debe Iniciar esta Semana"  || data.Estado == "Debe Iniciar esta Semana y Restricciones Pendientes"){
					if(data.Ruta_Critica==="1" && data.Ejecutado<1){
						$('td', row).css('background-color', 'rgba(255,150,64,1)');
					}else if(data.Ruta_Critica==="0" && data.Ejecutado<1){
						$('td', row).css('background-color', 'rgba(255,192,51,0.5)');
					}
				}else if(data.Estado == "En Liberación de Restricciones" && data.Ejecutado<1){
					$('td', row).css('background-color', 'rgba(0,191,114,0.9)');
				}else{
					$('td', row).css('color', '#9e9e9e');
				}
			},

				"language": idioma_espanol
			});

			$("div.toolbarFilaBotones").html('<button type= "button" class="leyenda_colores btn btn-secondary btn-sm" style="max-width:10%; margin-right:5px" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle fa-lg"></i></button><button type= "button" id="actualizarEjecucion" class="actualizarEjecucion btn btn-secondary btn-sm" style="max-width:15%; margin-right:5px" onclick="actualizarEjecucion()">Actualizar Ejecución <i class="fas fa-sync fa-lg"></i></button><button type= "button" id="descargarCorteProgramacion" class="descargarCorteProgramacion btn btn-secondary btn-sm" style="max-width:10%; margin-right:5px" onclick="descargarCorteProgramacion()">Descargar Corte <i class="fas fa-download fa-lg"></i></button><div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:2px; margin:0 1%; max-width:90%"><button id="btn_total" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'total\')"><h6>Totales</h6></button><button id="btn_no_requeridas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'no_requeridas\')"></button><button id="btn_lookahead" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'lookahead\')"></button><button id="btn_no_iniciadas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'no_iniciadas\')"></button><button id="btn_a_tiempo" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'a_tiempo\')"></button><button id="btn_atrasadas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'atrasadas\')"></button><button id="btn_terminadas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'terminadas\')"></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			// activarBuscador("#dt_cliente tbody", table);
			// ocultos(table);
			maestroPermisos(document.getElementById('permiso').value);
			obtener_data_editar("#dt_cliente tbody", table);
			//obtener_id_editar("#dt_general tbody", table);

			// Filtros de texto
			$('#buscadorActividad').on('keyup', function() {
				table.column(5).search($('#buscadorActividad').val()).draw();
			});

			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				let filtro = $('#buscadorSemanasInicio').val().trim(); // Obtiene el valor del filtro
				let valorColumna = data[7].trim() || 0; // Obtiene el valor de la columna			
				
				if (filtro === "" || (filtro == valorColumna) || (filtro == "7" && valorColumna > 6)) {
					return true;
				}
				return false;
			});

			// Aplica el filtro cuando cambie el select
			$('#buscadorSemanasInicio').on('change', function() {
				table.draw();
			});

			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				let filtro = $('#buscadorCritica').val().trim(); // Obtiene el valor del filtro
				let valorColumna = data[10].trim(); // Obtiene el valor de la columna			
				
				// Si no hay filtro, muestra todos los datos
				if (filtro === "") {
					return true;
				}

				// Compara el valor de la columna con el filtro EXACTAMENTE
				return valorColumna === filtro;
			});

			// Aplica el filtro cuando cambie el select
			$('#buscadorCritica').on('change', function() {
				table.draw();
			});
			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				let filtro = $('#buscadorEstado').val().trim(); // Obtiene el valor del filtro
				let valorColumna = data[15].trim(); // Obtiene el valor de la columna			
				
				// Si no hay filtro, muestra todos los datos
				if (filtro === "") {
					return true;
				}

				// Compara el valor de la columna con el filtro EXACTAMENTE
				return valorColumna === filtro;
			});

			// Aplica el filtro cuando cambie el select
			$('#buscadorEstado').on('change', function() {
				table.draw();
			});
		}

		var actualizarBarraFiltros = function(db, semana, opcionListar){
			$.ajax({
				method: "POST",
				url: "../programa_general/actualizarFiltros.php",
				contenttype:"charset=utf-8",
				data: {"db":db, "semana":semana},
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
				var no_requeridas=json_info["data"]["no_requeridas"];
				var lookahead=json_info["data"]["lookahead"];
				var no_iniciadas=json_info["data"]["no_iniciadas"];
				var a_tiempo=json_info["data"]["a_tiempo"];
				var atrasadas=json_info["data"]["atrasadas"];
				var terminadas=json_info["data"]["terminadas"];
				var total=json_info["data"]["total"]*1;
				var activa_no_requeridas=json_info["data"]["activa_no_requeridas"];
				var activa_lookahead=json_info["data"]["activa_lookahead"];
				var activa_no_iniciadas=json_info["data"]["activa_no_iniciadas"];
				var activa_a_tiempo=json_info["data"]["activa_a_tiempo"];
				var activa_atrasadas=json_info["data"]["activa_atrasadas"];
				var activa_terminadas=json_info["data"]["activa_terminadas"];
				var scriptBarraFiltros = "&activa_no_requeridas="+activa_no_requeridas+"&activa_lookahead="+activa_lookahead+"&activa_no_iniciadas="+activa_no_iniciadas+"&activa_a_tiempo="+activa_a_tiempo+"&activa_atrasadas="+activa_atrasadas+"&activa_terminadas="+activa_terminadas;

				document.getElementById('scriptBarraFiltros').value = scriptBarraFiltros;

				//console.log(no_requeridas, lookahead, no_iniciadas, a_tiempo, terminadas, total)
				if(total!=0){
					var p_no_requeridas=(no_requeridas/total*100).toFixed(2) +'%';
					var p_lookahead=(lookahead/total*100).toFixed(2) +'%';
					var p_no_iniciadas=(no_iniciadas/total*100).toFixed(2) +'%';
					var p_a_tiempo=(a_tiempo/total*100).toFixed(2) +'%';
					var p_atrasadas=(atrasadas/total*100).toFixed(2) +'%';
					var p_terminadas=(terminadas/total*100).toFixed(2) +'%';
				}else{
					var p_no_requeridas='0%';
					var p_lookahead='0%';
					var p_no_iniciadas='0%';
					var p_a_tiempo='0%';
					var p_atrasadas='0%';
					var p_terminadas='0%';
				}

				if(opcionListar == "siListar"){
					listar();
				}


				$("#btn_no_requeridas").html("<p style='font-size:1.2em; padding:0; margin:0'>No Requeridas <br>"+p_no_requeridas+"</p>");
				$("#btn_lookahead").html("<p style='font-size:1.2em; padding:0; margin:0'>En Restricciones <br>"+p_lookahead+"</p>");
				$("#btn_no_iniciadas").html("<p style='font-size:1.2em; padding:0; margin:0'>Deben Iniciar <br>"+p_no_iniciadas+"</p>");
				$("#btn_a_tiempo").html("<p style='font-size:1.2em; padding:0; margin:0'>A Tiempo <br>"+p_a_tiempo+"</p>");
				$("#btn_atrasadas").html("<p style='font-size:1.2em; padding:0; margin:0'>Atrasadas <br>"+p_atrasadas+"</p>");
				$("#btn_terminadas").html("<p style='font-size:1.2em; padding:0; margin:0'>Terminadas <br>"+p_terminadas+"</p>");

				if(activa_no_requeridas==1){
					$("#btn_no_requeridas").addClass('btn-success');
				}
				if(activa_lookahead==1){
					$("#btn_lookahead").addClass('btn-success');
				}
				if(activa_no_iniciadas==1){
					$("#btn_no_iniciadas").addClass('btn-success');
				}
				if(activa_a_tiempo==1){
					$("#btn_a_tiempo").addClass('btn-success');
				}
				if(activa_atrasadas==1){
					$("#btn_atrasadas").addClass('btn-success');
				}
				if(activa_terminadas==1){
					$("#btn_terminadas").addClass('btn-success');
				}
				if(activa_no_requeridas==0 && activa_lookahead==0 && activa_no_iniciadas==0 && activa_a_tiempo==0 && activa_atrasadas==0 && activa_terminadas==0){
					$("#btn_total").addClass('btn-success');
				}
			});
		}

		var cambiarClaseBarraFiltros=function(p){
			//console.log(p);
			if(p=='no_requeridas'){
				if($('#btn_no_requeridas').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
						p = 'total';
					}
				}
			}else if(p=='lookahead'){
				if($('#btn_lookahead').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
							p = 'total';
					}
				}
			}else if(p=='no_iniciadas'){
				if($('#btn_no_iniciadas').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
						p = 'total';
					}
				}
			}else if(p=='a_tiempo'){
				if($('#btn_a_tiempo').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
						p = 'total';
					}
				}
			}else if(p=='atrasadas'){
				if($('#btn_atrasadas').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
						p = 'total';
					}
				}
			}else if(p=='terminadas'){
				if($('#btn_terminadas').hasClass('btn-success')==true){
					var activa = 0;
				}else{
					var activa = 1;
					if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true){
						p = 'total';
					}
				}
			}
			location.assign("clase_filtro.php?clase="+p+"&activa="+activa);
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
				if(Semanal_Confirmada == 1 && permiso!="P"){
					if (only_once == true) {
						$(".texto_semanal_confirmada").html("<p>En esta Semana los compromisos de la <b>Programación Semanal</b> ya fueron confirmados. Por esto, el programa general ya no puede ser modificado hasta que se cree la <b>Semana "+(Number(semana)+1)+"</b>.</p><p> Recuerde que el procedimiento de Last Planner debe seguirse con la siguiente metodología: </p><p><b>1.</b> Calificar la semana que se termina (En este caso la Semana "+(Number(semana))+").<br><b>2.</b> Abrir la pestaña <b>\"Semanas del Proyecto\"</b> y crear la nueva Semana (En este caso se debe crear la Semana "+(Number(semana)+1)+").<br><b>3.</b> Actualizar el estado de ejecución de las actividades en el <b>\"Programa General\"</b>, en la semana creada (Semana "+(Number(semana)+1)+").<br><b>4.</b> Actualizar la <b>\"Liberación de Restricciones\"</b> de la semana creada (Semana "+(Number(semana)+1)+").<br><b>5.</b> Generar los compromisos de la <b>\"Programación Semanal\"</b> de la semana creada (Semana "+(Number(semana)+1)+").</p>");
						$("#modal_semanal_confirmada_Label").html("<b>Programa General Bloqueado!!</b>");
						$("#modal_semanal_confirmada").modal("show");
						recargarTabla("listar");
					}
				}else{
					//console.log("hola");
			    if (only_once == true) {
						var data= table.row($(this).parents("tr")).data();
						if(data.Titulo==0){
							var Id=$("#Id").val(data.Consecutivo_en_Programa),
							//medir_productividad=$("#medir_productividad").val(data.medir_productividad),
							opcion = $("#opcion").val("modificar");
							var codigo_html_unidad = "<select id='select_unidad' name='unidad' class='form-control form-control-sm'><option value=''></option><option value='ml'>ml</option><option value='m2'>m2</option><option value='m3'>m3</option><option value='un'>Un</option><option value='gl'>Gl</option><option value='kg'>kg</option><option value='%'>%</option><option value='Niveles'>Niveles</option></select>";
							$( this ).parent().find('.input_unidad').html(codigo_html_unidad);

							var codigo_html_cantidad_ppto = "<input id='input_cantidad_ppto' class='form-control form-control-sm' type='number' min='0' step='0.01' value='"+(data.cantidad_ppto)+"'></input>";
							$( this ).parent().find('.input_cantidad_ppto').html(codigo_html_cantidad_ppto);

							var codigo_actividad = <?php
								require_once("../conexion.php");
								$query="SELECT * FROM general_codigos_actividades";
								$stmt = $db->query($query);
								$codigo_actividad="";
								while ($valores = $stmt->fetch()){
								$valor=$valores["codigo_actividad"];
								$actividad=$valores["actividad"];
								$vista=$valor . " - " . $actividad;
								$codigo_actividad .="<option value='$valor'>$vista</option>";
								};
								echo '"'.$codigo_actividad.'"';
							?>;
							var codigo_html_codigo_actividad = "<select id='select_codigo_actividad' name='codigo_actividad' class='form-control form-control-sm' onchange=bloquear_unidad()><option value=''></option>"+codigo_actividad+"</select>";
							if (permiso=="P" || permiso=="A"){
								$( this ).parent().find('.input_codigo_actividad').html(codigo_html_codigo_actividad);
								$("#select_codigo_actividad").val(data.codigo_actividad).change();
							}else{
								codigo_html_codigo_actividad = $( this ).parent().find('.input_Actividad').html() + codigo_html_codigo_actividad;
								$( this ).parent().find('.input_Actividad').html(codigo_html_codigo_actividad);
								$("#select_codigo_actividad").val(data.codigo_actividad).change();
								$("#select_codigo_actividad").attr('disabled', true);
								$("#select_codigo_actividad").hide();
							}


							if(data.cantidad_ppto == null || data.cantidad_ppto == ""){
								var Ejecutado = (data.Ejecutado * 100).toFixed(2);
							}else{
								var Ejecutado = (data.Ejecutado * data.cantidad_ppto).toFixed(2);
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
							only_once = false;
							$("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
									if(e.keyCode==13){
											$("#btn_guardar_editar").click();
											only_once = true;
									}
							});
							$("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
									if(e.keyCode==27){
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
				}
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
		      url: "../programa_general/guardar_programa_general.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "opcion": opcion,
		        "codigo_actividad": codigo_actividad
		      }
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      unidad = json_info[0];
		      $("#select_unidad").val(unidad).change();
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

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function() {
			$("#btn_guardar_editar").one("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				if($("#input_cantidad_ppto").val() == null || $("#input_cantidad_ppto").val() == ""){
					var input_Ejecutado_Editar = $("#input_Ejecutado_Editar").val()/100;
				}else{
					var input_Ejecutado_Editar = $("#input_Ejecutado_Editar").val()/$("#input_cantidad_ppto").val();
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

				frm=frm+"&Ejecutado="+input_Ejecutado_Editar+"&codigo_actividad="+($("#select_codigo_actividad").val())+"&unidad="+($("#select_unidad").val())+"&cantidad_ppto="+($("#input_cantidad_ppto").val())+"&editarActividadAsociar=0";
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
						url: "../programa_general/guardar_programa_general.php?db="+db+"&semana="+semana,
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
				actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
				//listar();
		  } else {
		    table.ajax.reload();
				actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "noListar");
		    obtener_data_editar("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
		}

		var actualizarEjecucion = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var f_inicio_sem = document.getElementById('Fecha_Inicio_SemYMD').value;
			var maxSemana = document.getElementById('Max_Semana').value;
			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;
			// console.log(f_inicio_sem, maxSemana);
			if(maxSemana == semana){
				if(Semanal_Confirmada == 0){
					if(semana>1){
						$("#modal_spinner").modal("show");
						$.ajax({
							method: "POST",
							url: "../funciones_generales/php/actualizarEjecucion.php",
							contenttype: "charset=utf-8",
							data: {
								"db": db,
								"semana": semana,
								"f_inicio_sem": f_inicio_sem
							}
						}).done(function(info) {
							var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
							var actualizado = json_info[2];
							if(actualizado == 1){
								$(".iconoAlertaSemanalConfirmada").html('<div class="texto_semanal_confirmada" style="width:100%; float:left"></div>');
								$(".texto_semanal_confirmada").html("<p>Se actualizó el estado de ejecución de las actividades.</p>");
								$("#modal_semanal_confirmada_Label").html("<b>Semana Actualizada!!</b>");
								$("#modal_semanal_confirmada").modal("show");
							}else{
								$(".texto_semanal_confirmada").html("<p>Se actualizó el estado de ejecución de las actividades.</p>");
								$("#modal_semanal_confirmada_Label").html("<b>Semana Actualizada!!</b>");
								$("#modal_semanal_confirmada").modal("show");
							}
							recargarTabla("listar");
							$("#modal_spinner").modal("hide");
						});
					}else{
						$(".texto_semanal_confirmada").html("<p>Se actualizó el estado de ejecución de las actividades.</p>");
						$("#modal_semanal_confirmada_Label").html("<b>Semana Actualizada!!</b>");
						$("#modal_semanal_confirmada").modal("show");
						recargarTabla("listar");
						$("#modal_spinner").modal("hide");
					}
				}else{
					$(".texto_semanal_confirmada").html("<p>En esta Semana los compromisos de la <b>Programación Semanal</b> ya fueron confirmados. Por esto, el programa general ya no puede ser modificado hasta que se cree la <b>Semana "+(Number(semana)+1)+"</b>.</p><p> Recuerde que el procedimiento de Last Planner debe seguirse con la siguiente metodología: </p><p><b>1.</b> Calificar la semana que se termina (En este caso la Semana "+(Number(semana))+").<br><b>2.</b> Abrir la pestaña <b>\"Semanas del Proyecto\"</b> y crear la nueva Semana (En este caso se debe crear la Semana "+(Number(semana)+1)+").<br><b>3.</b> Actualizar el estado de ejecución de las actividades en el <b>\"Programa General\"</b>, en la semana creada (Semana "+(Number(semana)+1)+").<br><b>4.</b> Actualizar la <b>\"Liberación de Restricciones\"</b> de la semana creada (Semana "+(Number(semana)+1)+").<br><b>5.</b> Generar los compromisos de la <b>\"Programación Semanal\"</b> de la semana creada (Semana "+(Number(semana)+1)+").</p>");
					$("#modal_semanal_confirmada_Label").html("<b>Programa General Bloqueado!!</b>");
					$("#modal_semanal_confirmada").modal("show");
					recargarTabla("listar");
					$("#modal_spinner").modal("hide");
				}
			}else{
				$(".texto_semanal_confirmada").html("<p>La <b>Semana " + semana + "</b> no se puede actualizar debido a que la última semana registrada del proyecto es la <b>Semana " + maxSemana + "</b>.</p><p>Diríjase a la <b>Semana " + maxSemana + "</b> para continuar.</p>");
				$("#modal_semanal_confirmada_Label").html("<b>No se encuentra en la Semana Actual!!</b>");
				$("#modal_semanal_confirmada").modal("show");
				recargarTabla("listar");
				$("#modal_spinner").modal("hide");
			}


		}

		var descargarCorteProgramacion = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			// console.log(frm);

			$.ajax({
				method: "POST",
				url: "../programa_general/descargarCorteProgramacion.php",
				contenttype:"charset=utf-8",
				data: {"db":db, "semana":semana},
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
				window.location.href = json_info;
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
