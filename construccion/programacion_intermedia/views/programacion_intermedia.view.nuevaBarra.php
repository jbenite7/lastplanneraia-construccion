<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="programacion_intermedia">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_programacionIntermedia table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Actividad</th>
						<th>Título</th>
						<th>Semanas al Inicio</th>
						<th>Ejecutado</th>
						<th>Diseños y Especif.</th>
						<th>Materiales</th>
						<th>Mano de Obra</th>
						<th>Equipos</th>
						<th>Predecesoras</th>
						<th>Proced. Constructivo</th>
						<th>Modelación BIM</th>
						<th>% Liberación</th>
						<th>Sub-Contratista</th>
						<th>Responsable AIA</th>
						<th>Observaciones</th>
					</tr>
					<tr>
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
							</select>
						</th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th>
							<select style="width:80%" id="buscadorLiberada">
								<option value="">Todas</option>
								<option value="Liberada">Liberada</option>
								<option value="NoLiberada">No Liberada</option>
							</select>
						</th>
						<th>
							<select style="width:80%" id="buscadorSubcontratista">
		                      	<option value="">Todos</option> 
		                      	<option value="AIA (MO Directa)">AIA (MO Directa)</option> 
								<?php
								require("../conexion.php");
								$db = $_SESSION['db'];
								$query="SELECT * FROM $db"."_subcontratistas WHERE activo=1";
								$resultado= mysqli_query($conexion, $query);
								while ($valores = mysqli_fetch_array($resultado)){
									echo '<option value="'.$valores["subcontratista"].'">'.$valores["subcontratista"].'</option>';
								};
								mysqli_close($conexion);
								?>
		                    </select>
						</th>
						<th>
							<select style="width:80%" id="buscadorResponsableAIA">
		                      	<option value="">Todos</option> 
								<?php
								require("../conexion.php");
								$db = $_SESSION['db'];
								$query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
								$resultado= mysqli_query($conexion, $query);
								while ($valores = mysqli_fetch_array($resultado)){
									echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
								};
								mysqli_close($conexion);
								?>
		                    </select>
						</th>
						<th></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- Se crea el Modal con la guía para liberar restricciones de "Diseños y Especificaciones" -->
	  <div class="modal fade" id="modal_pregunta_D_y_E" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_D_y_E_Label">Restricciones de Diseños y Especificaciones</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> Si en el proyecto no están los diseños para la construcción de algún elemento.</li><br>
	            <li align="justify"><b style="font-size:125%">33%:</b> Si los diseños (Con sello para construcción) ya fueron entregados al equipo de construcción, pero no han sido revisados en profundidad ni por el director ni por los residentes.</li><br>
	            <li align="justify"><b style="font-size:125%">66%:</b> Una vez los diseños están en el proyecto y cuentan con el visto bueno del director y de los residentes.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> Una vez que los diseños que están aprobados por dirección de obra fueron entregados a los contratistas y/o maestros de obra.</li><br>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Materiales" -->
	  <div class="modal fade" id="modal_pregunta_Materiales" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_Materiales_Label">Restricciones de Materiales</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de aprovisionamiento de los materiales que se necesitan para ejecutar la actividad.</li><br>
	            <li align="justify"><b style="font-size:125%">33%:</b> La actividad esta al día de acuerdo al plan de compras.</li><br>
	            <li align="justify"><b style="font-size:125%">66%:</b> La actividad esta al día de acuerdo al plan de aprovisionamiento.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> Los materiales que se necesitan ya están en el proyecto disponibles para su uso.</li><br>
	            <p align="justify"><b style="font-size:125%">Nota 1:</b> se debe aclarar que una actividad no se puede liberar completamente, si <b><u>mínimo uno</u></b> de los materiales que necesita para ser ejecutada no ha llegado al proyecto.</p>
	            <p align="justify"><b style="font-size:125%">Nota 2:</b> Ver formatos de plan de compras y plan de aprovisionamiento.</p>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Mano de Obra" -->
	  <div class="modal fade" id="modal_pregunta_MdeO" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_MdeO_Label">Restricciones de Mano de Obra</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de mano de obra para la actividad.</li><br>
	            <li align="justify"><b style="font-size:125%">33%:</b> Existen los contratos de mano de obra, pero el recurso de personal todavía no esta ubicado en el proyecto.</li><br>
	            <li align="justify"><b style="font-size:125%">66%:</b> Existe en el proyecto documentación y cumplimiento de requisitos legales para ingresar al proyecto, además de toda la adecuación de campamentos necesaria.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> El recurso de personal de el o los contratistas seleccionados para la actividad ya están en el proyecto.</li><br>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Equipos" -->
	  <div class="modal fade" id="modal_pregunta_Equipos" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_Equipos_Label">Restricciones de Equipos</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de aprovisionamiento de los equipos que se necesitan para ejecutar la actividad.</li><br>
	            <li align="justify"><b style="font-size:125%">33%:</b> La actividad esta al día de acuerdo al plan de compras.</li><br>
	            <li align="justify"><b style="font-size:125%">66%:</b> La actividad esta al día de acuerdo al plan de aprovisionamiento.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> Los equipos que se necesitan ya están en el proyecto disponibles para su uso.</li><br>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Predecesoras" -->
	  <div class="modal fade" id="modal_pregunta_Predecesora" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_Predecesora_Label">Restricciones de Actividades Predecesoras</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> las actividades predecesoras que restringen el inicio de la actividad no han iniciado o están atrasadas de acuerdo al programa.</li><br>
	            <li align="justify"><b style="font-size:125%">50%:</b> las actividades predecesoras que restringen el inicio de la actividad van con un rendimiento igual o superior al que demanda el programa.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> las actividades predecesoras que restringen el inicio de la actividad ya están acabadas.</li><br>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Procedimiento Constructivo" -->
	  <div class="modal fade" id="modal_pregunta_Pdto_Cons" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_Pdto_Cons_Label">Restricciones de Procedimiento Constructivo</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> No existe procedimiento constructivo para la actividad.</li><br>
	            <li align="justify"><b style="font-size:125%">50%:</b> Existe procedimiento constructivo pero no se ha divulgado con el grupo profesional de la obra.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> El procedimiento fue divulgado en la obra y aprobado por el director.</li><br>
	            <p align="justify"><b style="font-size:125%">Nota:</b> Ver formato para elaborar procedimiento constructivo.</p>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>
	  <!-- Modal -->

	  <!-- Se crea el Modal con la guía para liberar restricciones de "Modelación BIM" -->
	  <div class="modal fade" id="modal_pregunta_Modelo" role="dialog">
	    <div class="modal-dialog modal-lg">
	      <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	          <h4 class="modal-title" id="modal_pregunta_Modelo_Label">Restricciones de Modelación BIM</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	          <ul style="padding:0% 5%; margin:0">
	            <li align="justify"><b style="font-size:125%">0%:</b> No hay modelos en el proyecto.</li><br>
	            <li align="justify"><b style="font-size:125%">50%:</b> Existen los modelos pero no están coordinados.</li><br>
	            <li align="justify"><b style="font-size:125%">100%:</b> Existen modelos coordinados para todas las disciplinas.</li><br>
	            <li align="justify"><b style="font-size:125%">No Aplica:</b> La tarea no aplica para ser modelada.</li><br>
	          </ul>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
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
			//ocultos();
			actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var scriptBarraFiltros = document.getElementById('scriptBarraFiltros').value;


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
					"url":"../programacion_intermedia/listar_programacion_intermedia.php?db="+db+"&semana="+semana+scriptBarraFiltros
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [

						{
								'targets': [0],
								'width':'5%',
						},
						{
								'targets': [4,6,7,8,9,10,11,12,13,15,16,17],
								'width':'5%',
						},

						{
								'targets': [1,2,5,14],
								'width':'1%',
						},

						{
								'targets': [3],
								'width':'10%',
						},

						{
								'targets': [6,7,8,9,10,11,12,13],
								'render': function ( data, type, full, meta ) {
										if(data=="N/A"){
												return data;
										}else if(data==null || data==""){
												data="";
												return data;
										}else{
												data=data*100;
												data=data.toFixed(0);
												return data + "%";
										};

								},
						},

						{
								'targets': [15,16,17],
								'render': function ( data, type, full, meta ) {
												return data;
										}
						},

						{
								'targets': [2,3,5],
								'render': function ( data, type, full, meta ) {
												return data;
										}
						},

						{
								'targets': [0],
								'render': function ( data, type, full, meta ) {
										if(data=="Boton"){
												boton=""/*<button type= 'button' class='editar btn btn-primary btn-sm' title='Editar el estado de las restricciones de la actividad seleccionada'><i class='fa fa-edit' aria-hidden='true' ></i></button>*/;
										}else{
												boton="";
										}
										return boton;

								},
						},

						{
								'targets': [14],
								'render': function ( data, type, full, meta ) {
										if(data==0){
												icono="<i style='color:red' class='fa fa-exclamation-triangle fa-lg' aria-hidden='true' ></i></button>"/*<button type= 'button' class='editar btn btn-primary btn-sm' title='Editar el estado de las restricciones de la actividad seleccionada'><i class='fa fa-edit' aria-hidden='true' ></i></button>*/;
										}else if(data>0 && data<1){
												icono="<i style='color:RGB(210,203,59)' class='fa fa-minus-circle fa-lg' aria-hidden='true' ></i></button>";
										}else if(data==1){
												icono="<i style='color:green' class='fa fa-check-square fa-lg' aria-hidden='true' ></i></button>";
										}
										if(data==null || data==""){
												data="";
												return data;
										}else{
												data=data*100;
												data=data.toFixed(0);
												return data + "% " + icono;
										}

								},
						},

						{
								'targets': [0],
								'className': 'Botones'
						},
						{
								'targets': [7],
								'className': 'input_D_y_E'
						},
						{
								'targets': [8],
								'className': 'input_Materiales'
						},
						{
								'targets': [9],
								'className': 'input_MdeO'
						},
						{
								'targets': [10],
								'className': 'input_Equipos'
						},
						{
								'targets': [11],
								'className': 'input_Predecesora'
						},
						{
								'targets': [12],
								'className': 'input_Pdto_Cons'
						},
						{
								'targets': [13],
								'className': 'input_Modelo'
						},
						{
								'targets': [15],
								'className': 'input_Sub_Contratista'
						},
						{
								'targets': [16],
								'className': 'input_Responsable_AIA'
						},
						{
								'targets': [17],
								'className': 'input_Observaciones'
						},
					],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

			"columns":[
					{"data":"boton"},
					{"data":"Consecutivo_en_Programa", "visible":false},
					{"data":"Id"},
					{"data":"Actividad"},
					{"data":"Titulo", "visible":false},
					{"data":"Semanas_Inicio"},
					{"data":"Ejecutado"},
					{"data":"D_y_E"},
					{"data":"Materiales"},
					{"data":"MdeO"},
					{"data":"Equipos"},
					{"data":"Predecesora"},
					{"data":"Pdto_Cons"},
					{"data":"Modelo"},
					{"data":"Estado_Restricciones"},
					{"data":"Sub_Contratista"},
					{"data":"Responsable_AIA"},
					{"data":"Observaciones"},
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

			$("div.toolbarFilaBotones").html('<button type= "button" class="leyenda_colores btn btn-secondary btn-sm" style="max-width:10%" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle fa-lg"></i></button><button id="btn_informe_compromisos" type="button" class="btn btn-secondary btn-sm" style="margin-left:5px; margin-right:5px" onclick="descargarRestricciones()">Descargar Corte  <i class="fas fa-download fa-lg"></i></button><div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:2px; margin:0 1%; max-width:90%"><button id="btn_total" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'total\')"></button><button id="btn_lookahead" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'lookahead\')"></button><button id="btn_no_iniciadas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'no_iniciadas\')"></button><button id="btn_en_ejecucion" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'en_ejecucion_pendientes\')"></button><button id="btn_terminadas" type="button" class="btn btn-secondary btn-sm" style="padding:1px 5px; margin: 0 1px" data-toggle="button"  aria-pressed="true" onclick="cambiarClaseBarraFiltros(\'en_ejecucion_terminadas\')"></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			//activarBuscador("#dt_cliente tbody", table);
			maestroPermisos(document.getElementById('permiso').value);
			obtener_data_editar("#dt_cliente tbody", table);
			//obtener_id_editar("#dt_general tbody", table);

			// Filtros de texto
			$('#buscadorActividad').on('keyup', function() {
				table.column(3).search($('#buscadorActividad').val()).draw();
			});
			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				let filtro = $('#buscadorSemanasInicio').val().trim(); // Obtiene el valor del filtro
				let valorColumna = data[5].trim() || 0; // Obtiene el valor de la columna			
				
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
				let filtro = $('#buscadorLiberada').val().trim(); // Obtiene el valor del filtro
				let valorColumna = parseInt(data[14].trim()) || 0; // Obtiene el valor de la columna			
				
				if (filtro === "" || (filtro === "NoLiberada" && valorColumna < 100) || (filtro === "Liberada" && valorColumna >= 100)) {
					return true;
				}
				return false;
			});
			// Aplica el filtro cuando cambie el select
			$('#buscadorLiberada').on('change', function() {
				table.draw();
			});
			$('#buscadorSubcontratista').on('change', function() {
				table.column(15).search($('#buscadorSubcontratista').val()).draw();
			});
			$('#buscadorResponsableAIA').on('change', function() {
				table.column(16).search($('#buscadorResponsableAIA').val()).draw();
			});
		}

		var actualizarBarraFiltros = function(db, semana, opcionListar){
			$.ajax({
				method: "POST",
				url: "../programacion_intermedia/actualizarFiltros.php",
				contenttype:"charset=utf-8",
				data: {"db":db, "semana":semana},
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
				var no_requeridas=json_info["data"]["no_requeridas"];
				var lookahead=json_info["data"]["lookahead"];
				var no_iniciadas=json_info["data"]["no_iniciadas"];
				var en_ejecucion_pendientes=json_info["data"]["en_ejecucion_pendientes"];
				var en_ejecucion_terminadas=json_info["data"]["en_ejecucion_terminadas"];
				var total=json_info["data"]["total"]*1;
				//var activa_no_requeridas=json_info["data"]["activa_no_requeridas"];
				var activa_lookahead=json_info["data"]["activa_lookahead"];
				var activa_no_iniciadas=json_info["data"]["activa_no_iniciadas"];
				var activa_en_ejecucion_pendientes=json_info["data"]["activa_en_ejecucion_pendientes"];
				var activa_en_ejecucion_terminadas=json_info["data"]["activa_en_ejecucion_terminadas"];
				var scriptBarraFiltros = "&activa_lookahead="+activa_lookahead+"&activa_no_iniciadas="+activa_no_iniciadas+"&activa_en_ejecucion_pendientes="+activa_en_ejecucion_pendientes+"&activa_en_ejecucion_terminadas="+activa_en_ejecucion_terminadas;

				document.getElementById('scriptBarraFiltros').value = scriptBarraFiltros;

				//console.log(no_requeridas, lookahead, no_iniciadas, en_ejecucion, terminadas, total)
				if(total!=0){
						/*var p_no_requeridas=(no_requeridas/total*100).toFixed(2) +'%'*/;
						var p_lookahead=(lookahead/total*100).toFixed(2) +'%';
						var p_no_iniciadas=(no_iniciadas/total*100).toFixed(2) +'%';
						var p_en_ejecucion_pendientes=(en_ejecucion_pendientes/total*100).toFixed(2) +'%';
						var p_en_ejecucion_terminadas=(en_ejecucion_terminadas/total*100).toFixed(2) +'%';

				}else{
						/*var p_no_requeridas='0%';*/
						var p_lookahead='0%';
						var p_no_iniciadas='0%';
						var p_en_ejecucion_pendientes='0%';
						var p_en_ejecucion_terminadas='0%';
				}

				if(opcionListar == "siListar"){
					listar();
				}

				/*$("#btn_no_requeridas").html("No Requeridas"+p_no_requeridas+"");*/
				$("#btn_total").html("<p style='font-size:1.2em; padding:0; margin:0'> Totales <br>"+total + "</p>");
				$("#btn_lookahead").html("<p style='font-size:1.2em; padding:0; margin:0'> En Restricciones <br>"+lookahead +" ("+ p_lookahead+") </p>");
				$("#btn_no_iniciadas").html("<p style='font-size:1.2em; padding:0; margin:0'> No Iniciadas <br>"+no_iniciadas +" ("+ p_no_iniciadas+") </p>");
				$("#btn_en_ejecucion").html("<p style='font-size:1.2em; padding:0; margin:0'> A Tiempo [Restricciones Pendientes] <br>"+en_ejecucion_pendientes +" ("+ p_en_ejecucion_pendientes+") </p>");
				$("#btn_terminadas").html("<p style='font-size:1.2em; padding:0; margin:0'> A Tiempo [Restricciones 100%] <br>"+en_ejecucion_terminadas +" ("+ p_en_ejecucion_terminadas+") </p>");

				/*if(activa_no_requeridas==1){
						$("#btn_no_requeridas").addClass('btn-success');
				}*/
				if(activa_lookahead==1){
						$("#btn_lookahead").addClass('btn-success');
				}
				if(activa_no_iniciadas==1){
						$("#btn_no_iniciadas").addClass('btn-success');
				}
				if(activa_en_ejecucion_pendientes==1){
						$("#btn_en_ejecucion").addClass('btn-success');
				}
				if(activa_en_ejecucion_terminadas==1){
						$("#btn_terminadas").addClass('btn-success');
				}
				if(/*activa_no_requeridas==0 && */activa_lookahead==0 && activa_no_iniciadas==0 && activa_en_ejecucion_pendientes==0 && activa_en_ejecucion_terminadas==0){
						$("#btn_total").addClass('btn-success');
				}
			});
		}

		var cambiarClaseBarraFiltros=function(p){
			//console.log(p);
			if(p=='lookahead'){
					if($('#btn_lookahead').hasClass('btn-success')==true){
							var activa = 0;
					}else{
							var activa = 1;
							if($('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
									p = 'total';
							}
					}
			}else if(p=='no_iniciadas'){
					if($('#btn_no_iniciadas').hasClass('btn-success')==true){
							var activa = 0;
					}else{
							var activa = 1;
							if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
									p = 'total';
							}
					}
			}else if(p=='en_ejecucion_pendientes'){
					if($('#btn_en_ejecucion').hasClass('btn-success')==true){
							var activa = 0;
					}else{
							var activa = 1;
							if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
									p = 'total';
							}
					}
			}else if(p=='en_ejecucion_terminadas'){
					if($('#btn_terminadas').hasClass('btn-success')==true){
							var activa = 0;
					}else{
							var activa = 1;
							if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true){
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

		  $(tbody).one("click", "td", function() {
		    if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					if(data.Titulo==0){
						var Id=$("#Id").val(data.Consecutivo_en_Programa),
								opcion = $("#opcion").val("modificar");

						var codigo_html_D_y_E = "<select id='select_D_y_E' name='D_y_E' class='form-control form-control-sm' style='display:inline-block' tabindex='1'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_D_y_E'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_D_y_E').html(codigo_html_D_y_E);

						var codigo_html_Materiales = "<select id='select_Materiales' name='Materiales' class='form-control form-control-sm' style='display:inline-block' tabindex='2'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Materiales btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Materiales'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_Materiales').html(codigo_html_Materiales);

						var codigo_html_MdeO = "<select id='select_MdeO' name='MdeO' class='form-control form-control-sm' style='display:inline-block' tabindex='3'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_MdeO btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_MdeO'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_MdeO').html(codigo_html_MdeO);

						var codigo_html_Equipos = "<select id='select_Equipos' name='Equipos' class='form-control form-control-sm' style='display:inline-block' tabindex='4'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Equipos btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Equipos'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_Equipos').html(codigo_html_Equipos);

						var codigo_html_Predecesora = "<select id='select_Predecesora' name='Predecesora' class='form-control form-control-sm' style='display:inline-block' tabindex='5'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Predecesora btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Predecesora'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_Predecesora').html(codigo_html_Predecesora);

						var codigo_html_Pdto_Cons = "<select id='select_Pdto_Cons' name='Pdto_Cons' class='form-control form-control-sm' style='display:inline-block' tabindex='6'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Pdto_Cons btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Pdto_Cons'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_Pdto_Cons').html(codigo_html_Pdto_Cons);

						var codigo_html_Modelo = "<select id='select_Modelo' name='Modelo' class='form-control form-control-sm' style='display:inline-block' tabindex='7'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Modelo btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Modelo'><i class='fas fa-question-circle fa-sm'></i></button>";
						$( this ).parent().find('.input_Modelo').html(codigo_html_Modelo);

						 var Sub_Contratista = <?php
																		require("../conexion.php");
																		$db = $_SESSION["db"];
																		$query="SELECT * FROM $db"."_subcontratistas WHERE Activo=1";
																		$resultado= mysqli_query($conexion, $query);
																		$Sub_Contratista="<option value='AIA (MO Directa)'>AIA (MO Directa)</option>";
																		while ($valores = mysqli_fetch_array($resultado)){
																				$valor=$valores["subcontratista"];
																				$Sub_Contratista .="<option value='$valor'>$valor</option>";
																		};
																		echo '"'.$Sub_Contratista.'"';
																		mysqli_close($conexion);
																?>;
						var codigo_html_Sub_Contratista = "<select id='select_Sub_Contratista' name='Sub_Contratista' class='form-control form-control-sm'  tabindex='8'><option value=''></option>"+Sub_Contratista+"</select>";
						$( this ).parent().find('.input_Sub_Contratista').html(codigo_html_Sub_Contratista);

						var Responsables_AIA = <?php
																		require("../conexion.php");
																		$db = $_SESSION["db"];
																		$query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
																		$resultado= mysqli_query($conexion, $query);
																		$Responsable_AIA="";
																		while ($valores = mysqli_fetch_array($resultado)){
																				$valor=$valores["nombre"];
																				$Responsable_AIA .="<option value='$valor'>$valor</option>";
																		};
																		echo '"'.$Responsable_AIA.'"';
																		mysqli_close($conexion);
																?>;
						var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control form-control-sm' tabindex='9'><option value=''></option>"+Responsables_AIA+"</select>";
						$( this ).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA);

						var codigo_html_Observaciones = "<textarea id='select_Observaciones' name='Observaciones' class='form-control form-control-sm' tabindex='10'></textarea>";
						$( this ).parent().find('.input_Observaciones').html(codigo_html_Observaciones);


						var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' style='padding:5px; margin:1px' title='Guardar el porcentaje de ejecución asignado' tabindex='11'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><br><button type= 'button' id='btn_liberarTodas' class='liberarTodas btn btn-primary btn-sm' style='padding:2px; margin:1px' title='Liberar al 100% todas las restricciones de la actividad' tabindex='11' onclick='liberarTodasRestricciones()'>Liberar Todo</button><!--<button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' style='padding:5px; margin:1px' title='Cancelar la edición' tabindex='12'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>-->";
						$( this ).parent().find('.Botones').html(codigo_html_botones);
						$("#select_D_y_E").val(data.D_y_E).change();
						$("#select_D_y_E").focus();
						$("#select_Materiales").val(data.Materiales).change();
						$("#select_MdeO").val(data.MdeO).change();
						$("#select_Equipos").val(data.Equipos).change();
						$("#select_Predecesora").val(data.Predecesora).change();
						$("#select_Pdto_Cons").val(data.Pdto_Cons).change();
						$("#select_Modelo").val(data.Modelo).change();
						$("#select_Sub_Contratista").val(data.Sub_Contratista).change();
						$("#select_Responsable_AIA").val(data.Responsable_AIA).change();
						$("#select_Observaciones").val(data.Observaciones).change();

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
					}
				}
				cancelarEdicionFila();
				guardar();
		  });
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

		var liberarTodasRestricciones = function() {
			$("#select_D_y_E").val(1).change();
			$("#select_Materiales").val(1).change();
			$("#select_MdeO").val(1).change();
			$("#select_Equipos").val(1).change();
			$("#select_Predecesora").val(1).change();
			$("#select_Pdto_Cons").val(1).change();
			$("#select_Modelo").val(1).change();
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function() {
			$("#btn_guardar_editar").one("click", function(e){
				e.preventDefault();
				e.stopPropagation();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;

				var D_y_E=$("#select_D_y_E").serialize();
				var Materiales=$("#select_Materiales").serialize();
				var MdeO=$("#select_MdeO").serialize();
				var Equipos=$("#select_Equipos").serialize();
				var Predecesora=$("#select_Predecesora").serialize();
				var Pdto_Cons=$("#select_Pdto_Cons").serialize();
				var Modelo=$("#select_Modelo").serialize();
				var Responsable_AIA=$("#select_Responsable_AIA").serialize();
				var Sub_Contratista=$("#select_Sub_Contratista").serialize();
				var Observaciones=$("#select_Observaciones").serialize();

				frm="Id="+($("#Id").val())+"&opcion="+($("#opcion").val())+"&"+D_y_E+"&"+Materiales+"&"+MdeO+"&"+Equipos+"&"+Predecesora+"&"+Pdto_Cons+"&"+Modelo+"&"+Sub_Contratista+"&"+Responsable_AIA+"&"+Observaciones;
				console.log(frm);
				$.ajax({
					method: "POST",
					url: "../programacion_intermedia/guardar_programacion_intermedia.php?db="+db+"&semana="+semana,
					contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					limpiar_datos();
					recargarTabla("");
				});
			});
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
		  var texto = "",
		    color = "";
		  if (informacion.respuesta == "BIEN") {
		    texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
		    color = "#379911";
		  }
		  if (informacion.respuesta == "ERROR") {
		    texto = "<strong>Error</strong>, no se ejecutó la consulta.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "EXISTE") {
		    texto = "<strong>Información!</strong> el usuario ya existe.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "VACIO") {
		    texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "CONFIRMAR") {
		    texto = "<strong>Advertencia!</strong> Por favor confirmar correctamente la dirección de correo.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "BIEN") {
		    $("#cuadro2").slideUp("slow");
		    $("#cuadro1").slideDown("slow");
		    $("#cuadro3").slideDown("slow");
		    $("#mensajeActualizacion").html(texto).css({
		      "color": color
		    });
		    $("#mensajeActualizacion").fadeOut(5000, function() {
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

		var descargarRestricciones = function() {
			$("#modal_spinner").modal("show");
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			// console.log(frm);

			$.ajax({
				method: "POST",
				url: "../programacion_intermedia/descargarRestricciones.php",
				contenttype:"charset=utf-8",
				data: {"db":db, "semana":semana},
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
				console.log(json_info);
				window.location.href = json_info;
				$("#modal_spinner").modal("hide");
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
