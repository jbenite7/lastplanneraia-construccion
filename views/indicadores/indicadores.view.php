<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
    <title>Indicadores LPS — Last Planner AIA</title>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::renderForModule('indicadores') ?>
	<link rel="stylesheet" href="/css/indicadores.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/indicadores.css') ?: 'ind1')) ?>" />
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body class="aia-shell aia-shell--sidebar">

	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="indicadores" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<!-- C-46: los emite el servidor; el inyector JS los duplica todavia (Task 37 lo retira). -->
		<input type="hidden" id="permiso_canonico" name="permiso_canonico" value="<?php echo htmlspecialchars($_SESSION['permiso_canonico'] ?? ($permiso ?? ''), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
		<input type="hidden" id="Max_Semana" name="Max_Semana" value="<?php echo (int) ($maxSemana ?? 0); ?>" aria-hidden="true">
		<input type="hidden" id="semana" name="semana" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" class="ind-section-title" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="tabla aia-grid-shell" id="contenedorInformePowerBI">
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
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\BiAccessComponent::renderBootConfig('indicadores') ?>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<?php $indCargarDatosVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/cargarDatosGeneralesPagina2.js') ?: 'ind1'; ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=<?php echo urlencode((string) $indCargarDatosVersion); ?>" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<?php $indGeneralJsVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/funcionesGenerales6.js') ?: 'ind1'; ?>
	<script type="text/javascript" src="/js/funcionesGenerales6.js?v=<?php echo urlencode((string) $indGeneralJsVersion); ?>" charset="utf-8"></script>
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

		/*
		 * Informe de indicadores de Last Planner.
		 *
		 * HOTFIX 2026-07: se deprecó el reporte de Google Data Studio (filtrado por
		 * proyecto vía query string) y se embebe el reporte de Power BI.
		 *
		 * Limitaciones conocidas del embed actual (Power BI "publish to web"), a
		 * resolver en la fase de Power BI Embedded (app-owns-data + embed-token):
		 *   - Es público por link y NO admite filtrado por proyecto vía URL ni control
		 *     con la JS API de Power BI; por eso, de momento, todos los proyectos ven
		 *     el mismo reporte.
		 *   - Para no exponer el dashboard completo a perfiles externos, los roles
		 *     restringidos no ven el reporte hasta que exista una versión con alcance
		 *     por rol/proyecto.
		 */
		var POWER_BI_REPORT_URL = 'https://app.powerbi.com/view?r=eyJrIjoiN2ZhODkwNzMtMDg0ZC00MTIzLWFiMjAtOTk0ZGM0MTUzOGY5IiwidCI6IjQxZjUxNDhjLThlNGMtNGE5Ny05M2Q5LWNhMzJhNDJhYzUyOCIsImMiOjR9';
		// Roles restringidos (Ambiental, SST, SG, Subcontratista): antes solo veían el
		// informe de proveedores; con el embed único no deben ver el dashboard completo.
		var ROLES_SIN_INFORME_INDICADORES = ["G", "S", "SG", "C"];

		// Proporción (ancho/alto) del reporte de Power BI para conservar su forma.
		var REPORTE_ASPECTO = 980 / 600;

		// Ajusta el tamaño del reporte según la ALTURA libre visible: el reporte tiene
		// forma fija, así que fijamos su alto al espacio vertical disponible (viewport
		// menos lo que hay encima del contenedor) y derivamos el ancho por su
		// proporción, con tope del 95% del ancho (holgura lateral). Así llena el alto
		// visible sin cortarse y toma el mayor ancho posible dentro de ese límite.
		//
		// Shell sidebar (DS-027): el contenedor ya NO usa el hack full-bleed
		// 100vw/margin-left:-50vw (rompía bajo/sobre el rail izquierdo). Al ser un
		// bloque normal dentro de body.aia-shell--sidebar, su ancho ya respeta el
		// padding-left del rail; por eso el ancho disponible se mide del propio
		// contenedor (clientWidth) y NO de window.innerWidth, que ignora el rail.
		function ajustarInformePowerBI() {
			var contenedor = document.getElementById('contenedorInformePowerBI');
			if (!contenedor) { return; }
			var iframe = contenedor.querySelector('iframe');
			if (!iframe) { return; }
			var margenSuperior = contenedor.getBoundingClientRect().top; // context-bar del shell, etc.
			var margenInferior = 16;
			var alturaLibre = window.innerHeight - margenSuperior - margenInferior;
			if (alturaLibre < 320) { alturaLibre = 320; } // piso razonable
			var anchoDisponible = contenedor.clientWidth || document.documentElement.clientWidth;
			var anchoMax = anchoDisponible * 0.95;         // 5% de holgura lateral
			var ancho = Math.min(alturaLibre * REPORTE_ASPECTO, anchoMax);
			iframe.style.width = Math.round(ancho) + 'px';
			iframe.style.height = Math.round(ancho / REPORTE_ASPECTO) + 'px';
		}

		var listar = function(seccion) {
			var contenedor = document.getElementById('contenedorInformePowerBI');
			if (!contenedor) { return; }

			var permiso = document.getElementById('permiso_canonico').value;
			if (ROLES_SIN_INFORME_INDICADORES.includes(permiso)) {
				contenedor.innerHTML = '<p class="ind-powerbi-denied">El informe de indicadores no está disponible para tu perfil.</p>';
				return;
			}
			contenedor.innerHTML = '<iframe title="Last Planner AIA - Power BI" src="' + POWER_BI_REPORT_URL + '" frameborder="0" allowfullscreen="true" onload="ajustarInformePowerBI()" class="ind-powerbi-frame"></iframe>';
			ajustarInformePowerBI();
		}

		// Reajusta al redimensionar la ventana (debounce ligero).
		var _ajusteInformeTO;
		window.addEventListener('resize', function () {
			clearTimeout(_ajusteInformeTO);
			_ajusteInformeTO = setTimeout(ajustarInformePowerBI, 120);
		});

		// Shell sidebar (DS-027): colapsar/expandir anima `padding-left` del body
		// (rail) y NO redimensiona la ventana, así que 'resize' no lo detecta.
		// Tampoco basta con escuchar el click del toggle: sidebar_navigation.js
		// también cambia data-sidebar-state al aplicar el estado persistido en
		// localStorage al cargar la página (si el usuario dejó el rail expandido
		// en una visita anterior), sin que medie ningún click — y ese cambio de
		// ancho ocurría DESPUÉS del primer ajustarInformePowerBI() (el del onload
		// del iframe), dejando el reporte dimensionado para el ancho equivocado.
		// Un MutationObserver sobre data-sidebar-state cubre ambos orígenes del
		// cambio de estado. Reajusta dos veces: de inmediato (prefers-reduced-motion
		// no anima, cambia al instante) y otra al terminar la transición (220ms,
		// --ds-motion-standard) para el caso animado.
		function reajustarInformeConDemora() {
			ajustarInformePowerBI();
			clearTimeout(_ajusteInformeTO);
			_ajusteInformeTO = setTimeout(ajustarInformePowerBI, 260);
		}
		(function observarEstadoSidebar() {
			var shellNav = document.querySelector('[data-shell-pattern="sidebar"]');
			if (!shellNav) { return; }
			new MutationObserver(reajustarInformeConDemora).observe(shellNav, {
				attributes: true,
				attributeFilter: ['data-sidebar-state'],
			});
		})();


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
