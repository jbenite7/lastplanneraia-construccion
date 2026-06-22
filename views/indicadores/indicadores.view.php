<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColorsFresh" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="indicadores" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<!-- <div class="row formularioRegistro">
	</div> -->

	<div class="row filaBotones">
		<div class="col-sm-12 col-md-12 col-lg-12 ml-auto mr-auto p-0" id="filaBotones" style="text-align: center; margin:5px auto 2px auto; width:100%; max-width:1300px">
			<div class="grupo_botones_informes btn-group" id="grupo_botones_informes" role="group" aria-label="Basic example" style="margin: 0 auto">
				<button id="btn_informeFichaResumen" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar('informeFichaResumen')" disabled>Resumen</button>
				<button id="btn_informeProgramaGeneral" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar('informeProgramaGeneral')" disabled>Programa General</button>
				<button id="btn_informeProgramacionIntermedia" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar('informeProgramacionIntermedia')">Liberación de Restricciones</button>
				<button id="btn_informeProgramacionSemanal" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar('informeProgramacionSemanal')">Programación Semanal</button>
			</div>
		</div>
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="tabla" id="contenedorInformeDataStudio" style="text-align: center; margin:2px auto 10px auto; width:100%; max-width:1300px">
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
			listar('informeFichaResumen');
			ocultos();
			maestroPermisos(document.getElementById('permiso_canonico').value);
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function(seccion) {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
			var proyecto = document.getElementById('proyecto').value;
			var permiso = document.getElementById('permiso_canonico').value;
			const permisos = ["G", "S", "SG", "C"];
			if(permisos.includes(permiso)){
				seccion = "informeProveedores";
			}

			var alturahoja = $(window).height();
			var posicionInicioInforme = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;

			if (seccion == 'informeProgramacionSemanal') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_hmejfblomc' + '?params=%7B"df49":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '",';

				if(semana > 6){
					url = url + '"df95":"include%25EE%2580%25801%25EE%2580%2580BT%25EE%2580%2580'+ (semana-5) + '%25EE%2580%2580'+ semana + '"%7D';
				}
				else{
					url = url + '"df95":"include%25EE%2580%25801%25EE%2580%2580' + 'LTE%25EE%2580%2580'+ (semana) + '"%7D';
				}
			}else if (seccion == 'informeProgramacionIntermedia') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_v8jvkiw2rc' + '?params=%7B"df79":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '",';

				url = url + '"df65":"include%25EE%2580%25801%25EE%2580%2580IN%25EE%2580%2580'+ semana +'"%7D';
			}else if (seccion == 'informeFichaResumen') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_orh49lctld' + '?params=%7B"df466":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '"%7D';
			}else if (seccion == 'informeProgramaGeneral') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_4yvejh07oc' + '?params=%7B"df54":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '"%7D';
			}else if (seccion == 'informePDC') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_o2fbmajg1c' + '?params=%7B"df297":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '"%7D';
			}else if (seccion == 'informeProveedores') {
				var url = 'https://datastudio.google.com/embed/reporting/aebe8495-3e8a-47be-a294-85c4b6384338/page/p_23433wqtuc' + '?params=%7B"df173":"include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580' + proyecto + '"%7D';
			}

			console.log(url);
			var informe = '<iframe width="100%" height="100%" src=\''+ url +'\' frameborder="0" style="border:0" allowfullscreen></iframe>';

			var filaBotones = '<button id="btn_informeFichaResumen" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informeFichaResumen\')">Resumen</button><button id="btn_informeProgramaGeneral" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informeProgramaGeneral\')">Programa General</button><button id="btn_informeProgramacionIntermedia" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informeProgramacionIntermedia\')">Liberación de Restricciones</button><button id="btn_informeProgramacionSemanal" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informeProgramacionSemanal\')">Programación Semanal</button><button id="btn_informePDC" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informePDC\')">Plan de Compras</button><button id="btn_informeProveedores" type="button" class="btn btn-outline-secondary btn-sm" onclick="listar(\'informeProveedores\')">Calificación de Subcontratistas</button>';


			document.getElementById('contenedorInformeDataStudio').innerHTML = informe;
			document.getElementById('contenedorInformeDataStudio').style.height = (alturahoja - posicionInicioInforme - 50) + "px";

			document.getElementById('grupo_botones_informes').innerHTML = filaBotones;
			// document.getElementById('btn_programacionSemanal').classList.remove("active");
			// document.getElementById('btn_programacionIntermedia').classList.remove("active");
			// document.getElementById('btn_programaGeneral').classList.remove("active");
			if(document.getElementById('pdcActivo').value == 0){
				document.getElementById('btn_informePDC').style.display = "none";
			}
			document.getElementById('btn_' + seccion).classList.add("active");

			//console.log(informe);

		}


		var ocultos=function(table){
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		    var permiso = document.getElementById('permiso_canonico').value;

            if (typeof window.rbacCapabilities !== "undefined") {
                if (!window.rbacCapabilities.canManageWeeks) {
                    $('.nueva_sem, .eliminar_sem').css('display', 'none');
                }
                if (!window.rbacCapabilities.canManageGeneralProgram) {
                    $('#btn_autoprogramar, #btn_agregar_actividad, .programa_general, .informacion_general').css('display', 'none');
                }
                if (!window.rbacCapabilities.canManageMediumTermProgram) {
                    $('.programacion_intermedia').css('display', 'none');
                }
                if (!window.rbacCapabilities.canManageWeeklyProgram) {
                    $('#btn_CNP').css('display', 'none');
                }
            } else {
                // Fallback Legacy
                if(permiso=="R"){
                        //$('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
                }else if(permiso=="G"){
                        //$('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
                }else if(permiso=="S"){
                        //$('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
                }else if(permiso=="V"){
                        $('.nueva_sem, .eliminar_sem').css('display', 'none');
                }else if(permiso=="C"){
                        $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
                }
            }
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
