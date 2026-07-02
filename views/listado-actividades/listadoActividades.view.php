<!DOCTYPE html>
<html lang="es">
<head id="head">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColors3" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>
	<?php
        $dbPrefixListadoActividades = $_SESSION['db'] ?? '';
	$projectIdListadoActividades = (int) ($_SESSION['project_id'] ?? ($projectId ?? 0));
	$maxSemanaListadoActividades = (int) ($_SESSION['Max_Semana'] ?? ($_SESSION['semana'] ?? 0));
	$actividadInicioOptionsHtml = '';

	if (empty($dbPrefixListadoActividades) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefixListadoActividades) || $projectIdListadoActividades <= 0) {
	    $actividadInicioOptionsHtml = '<option value="">Error: Database prefix not set</option>';
	} else {
	    try {
	        $dbInstance = Database::getInstance();
	        $queryActividadInicio = "SELECT unique_id AS Consecutivo_en_Programa, unique_id, Id, Actividad, Fecha_Inicio FROM programa_consolidado WHERE project_id = ? AND Semana=? AND Titulo=0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Fecha_Inicio ASC";
	        $stmtActividadInicio = $dbInstance->query($queryActividadInicio, [$projectIdListadoActividades, $maxSemanaListadoActividades]);
	        $actividadInicioOptions = [];

	        while ($valores = $stmtActividadInicio->fetch(PDO::FETCH_ASSOC)) {
	            $actividadValue = (string) ($valores['Consecutivo_en_Programa'] ?? '');
	            $actividadTexto = (string) ($valores['Actividad'] ?? '');
	            $actividadDisplay = html_entity_decode(strip_tags($actividadTexto), ENT_QUOTES, 'UTF-8');
	            $actividadDisplay = preg_replace('/\s+/u', ' ', $actividadDisplay);
	            $actividadDisplay = trim((string) $actividadDisplay);
	            $actividadLabel = trim(($valores['Id'] ?? '') . '. ' . $actividadDisplay . ' (Inicia el: ' . ($valores['Fecha_Inicio'] ?? '') . ')');
	            $actividadInicioOptions[] = '<option value="' . htmlspecialchars($actividadValue, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($actividadLabel, ENT_QUOTES, 'UTF-8') . '</option>';
	        }

	        $actividadInicioOptionsHtml = implode('', $actividadInicioOptions);
	    } catch (Throwable $t) {
	        $actividadInicioOptionsHtml = '<option value="">Error loading data: ' . htmlspecialchars($t->getMessage(), ENT_QUOTES, 'UTF-8') . '</option>';
	    }
	}
	?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_listadoActividades" aria-hidden="true">
		<input type="hidden" id="codigo" name="codigo" value="" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="" readonly aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="" readonly aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro" style:"visibility: hidden">
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
		<div class="modal_nuevaActividad modal fade aia-modal" id="modalNuevaActividad" tabindex="-1" role="dialog" aria-labelledby="modalNuevaActividadLabel" aria-hidden="true">
		  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalNuevaActividadLabel">
		          <div class="aia-modal__eyebrow">AIA Corporativo</div>
		          <h2 class="aia-modal__headline">Registrar Nueva Actividad</h2>
		          <p class="aia-modal__subtitle modal-body-texto-nuevaActividad" id="modal-body-texto-nuevaActividad">Completa la informacion base de la actividad y su relacion con el cronograma.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12">
		            <form class="form form-horizontal aia-modal__form" action="" method="POST">
		              <section class="form-group parametro_nuevaActividad aia-modal__section">
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Formulario de Registro de Actividades</h3>
		                  <p class="aia-modal__hint">Define la actividad, su descripcion y la actividad de referencia del cronograma.</p>
		                </div>
		                <div class="aia-modal__field-grid">
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
										<input type="hidden" id="Id" name="Id" value="">
					      <input type="hidden" id="opcion" name="opcion" value="registrar">
		                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="actividad" class="control-label aia-modal__label">Actividad</label><input id="actividad" name="actividad" type="text" class="form-control">
		                </div>
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="descripcionActividad" class="control-label aia-modal__label">Descripcion</label><input id="descripcionActividad" name="descripcionActividad" type="text" class="form-control">
		                </div>
		                <div class="col-sm-12 aia-modal__field">
			                  <label for="actividadInicio" class="control-label aia-modal__label">Tarea del Cronograma de Inicio de la Actividad</label>
			                  <select id="actividadInicio" name="actividadInicio" class="form-control" onchange="actualizarFechaInicio('nuevo')" style="width:100%">
		                    <option value=""></option><?php echo $actividadInicioOptionsHtml; ?>
		                  </select>
		                </div>
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="fechaInicio" class="control-label aia-modal__label">Fecha de Inicio</label><input id="fechaInicio" name="fechaInicio" type="text" class="form-control">
		                </div>
		                </div>
		              </section>
		              <div class="form-group aia-modal__actions">
		                <div class="col-sm-12 aia-modal__buttons">
		                  <input id="btn_guardar_actividad" type="submit" class="btn btn-primary" value="Guardar" aria-label="Guardar actividad">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal" aria-label="Cancelar actividad">
		                </div>
		                <p class="mensaje aia-modal__message"></p>
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
		<div class="modal_cargarExcel modal fade aia-modal" id="modalCargarExcel" role="dialog" aria-labelledby="modal_cargarExcelLabel">
		  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalCargarExcelLabel">
		          <div class="aia-modal__eyebrow">AIA Corporativo</div>
		          <h2 class="aia-modal__headline">Cargar Actividades desde Excel</h2>
		          <p class="aia-modal__subtitle modal-body-texto-cargarExcel" id="modal-body-texto-cargarExcel">Descarga la plantilla base y carga el archivo CSV completo del listado de actividades.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadroCargarExcel" class="cuadro4 col-sm-12 col-md-12 col-lg-12">
		            <form enctype="multipart/form-data" class="form form-horizontal aia-modal__form" id="formCargarExcel" name="formCargarExcel" action="" method="POST">
		              <section class="form-group parametro_cargarExcel aia-modal__section aia-modal__section--plain" style="border:none; box-shadow:none;">
		                <!-- <div class="form_eval form-group">
													<h3 id='form_general'>
														Descargar Archivo Base
													</h3>
												</div> -->
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Descargar Archivo Base</h3>
		                  <p class="aia-modal__hint">Usa la plantilla oficial para asegurar la estructura correcta del cargue masivo.</p>
		                </div>
		                <label for="descargarArchivoBase" class="control-label aia-modal__label">En el siguiente enlace puede descargar el archivo base para crear el listado de actividades desde Excel:</label>
		                <a id="descargarArchivoBase" class="descargarArchivoBase btn btn-primary" download="listadoActividades.csv" href="/api/listado-actividades/template">Descargar Archivo Base</a>
		              </section>
		              <section class="form-group parametro_cargarExcel aia-modal__section">
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Cargar Listado en Excel</h3>
		                  <p class="aia-modal__hint">Solo se permiten archivos en formato CSV y se procesara el contenido completo del listado.</p>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es cargarExcel-->
		                <input type="hidden" id="Id" name="Id" value="">
		                <input type="hidden" id="opcion" name="opcion" value="cargarExcel">
		                <input type="hidden" id="codigo" name="codigo" value="">
		                <!-- Se crea el input para cargar el archivo CSV que cargarà el listado de actividades del proyecto -->
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="archivoExcel" class="control-label aia-modal__label">Seleccione el archivo con el listado de actividades completo desde el equipo (solo se permiten archivos en formato CSV):</label>
		                  <input type="file" name="archivoExcel" id="archivoExcel" class="form-control form-control-lg" accept=".csv">
		                  <!-- <input type="submit" value="Enviar" name="archivoExcel"> -->
		                </div>
		              </section>
		              <div class="form-group aia-modal__actions">
		                <div class="col-sm-12 aia-modal__buttons">
		                  <input id="" type="submit" class="btn btn-success" value="Guardar">
		                  <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal">
		                </div>
		                <p class="mensaje aia-modal__message"></p>
		              </div>
		            </form>
		          </div>
		        </div>
		</div>
		</div>
	  </div>
	</div>
	<!-- Modal -->

	<!-- Modal Auto-Generar Listado de Actividades desde Programa General -->
	<div class="modal_autoGenerarListado modal fade aia-modal" id="modalAutoGenerarListado" role="dialog" aria-labelledby="modalAutoGenerarListadoLabel">
	  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <div class="modal-title" id="modalAutoGenerarListadoLabel">
	          <div class="aia-modal__eyebrow">AIA Corporativo</div>
	          <h2 class="aia-modal__headline">Auto-Generar Listado de Actividades</h2>
	          <p class="aia-modal__subtitle" id="modalAutoGenerarListadoSubtitle">Analiza el Programa General y crea actividades vinculadas automáticamente.</p>
	        </div>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      </div>
	      <div class="modal-body">
	        <div class="row">
	          <div class="cuadro4 col-sm-12 col-md-12 col-lg-12">
	            <div class="aia-modal__section aia-modal__section--plain">
	              <div class="aia-modal__section-header">
	                <h3 class="aia-modal__section-title">Resumen de Detección</h3>
	              </div>
	              <div class="alert alert-info" id="autoGenListadoResumen">
	                Presiona "Analizar" para detectar actividades en el Programa General.
	              </div>
	            </div>
	            <div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
	              <table class="table table-sm table-bordered table-hover mb-0" id="autoGenListadoTable">
	                <thead class="thead-light">
	                  <tr>
	                    <th style="width: 50px;">#</th>
	                    <th>Actividad (PG)</th>
	                    <th>Fecha Inicio</th>
	                    <th>Familia Detectada</th>
	                    <th>Confianza</th>
	                    <th>Estado</th>
	                  </tr>
	                </thead>
	                <tbody id="autoGenListadoBody"></tbody>
	              </table>
	            </div>
	          </div>
	        </div>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
	        <button type="button" class="btn btn-outline-primary" id="btnAutoGenListadoAnalizar" disabled><i class="fas fa-search"></i> Analizar</button>
	        <button type="button" class="btn-auto btn-auto-green" id="btnAutoGenListadoAplicar" disabled><i class="fas fa-magic"></i> Aplicar</button>
	      </div>
	    </div>
	  </div>
	</div>
	<!-- Modal -->

	<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
		<div class="modal fade aia-modal aia-modal__confirm" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
		  <div class="modal-dialog modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalEliminarLabel">
		          <div class="aia-modal__eyebrow">Accion sensible</div>
		          <h2 class="aia-modal__title">Eliminar Actividad</h2>
		          <p class="aia-modal__subtitle">Confirma esta accion antes de continuar.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-eliminar aia-modal__body-copy" id="modal-body-texto-eliminar"></p>
		      </div>
		      <div class="modal-footer aia-modal__buttons">
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
	<!-- TomSelect para los selectores con busqueda del modal Auto-PDC -->
	<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
	<link href="/css/tom-select-premium-aia.css?v=20260611" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/semi_auto_review.js?v=20260702" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function() {
		  $("#formulario_nuevo").hide();
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
		});

		var configurarSelectActividadInicio = function(selector, dropdownParent) {
			var $select = $(selector);
			var $fieldParent = $select.closest('.aia-modal__field');
			var $dropdownParent = $fieldParent.length ? $fieldParent : dropdownParent;

			if (!$select.length || typeof $select.select2 !== 'function') {
				return;
			}

			$select.off('.aiaActividadInicio');

			if ($select.data('select2')) {
				$select.select2('destroy');
			}

			var select2Options = {
				allowClear: true,
				placeholder: '',
				width: '100%'
			};

			if ($dropdownParent && $dropdownParent.length) {
				select2Options.dropdownParent = $dropdownParent;
			}

			$select.select2(select2Options);

			$select.on('select2:open.aiaActividadInicio', function() {
				window.setTimeout(function() {
					var $searchField = $('.select2-container--open .select2-search__field');
					if ($searchField.length) {
						$searchField.trigger('focus');
					}
				}, 0);
			});
		};

		var inicializarModalNuevaActividad = function() {
			var $modalNuevaActividad = $('#modalNuevaActividad');

			if (!$modalNuevaActividad.length) {
				return;
			}

			$modalNuevaActividad
				.off('shown.bs.modal.aiaActividadInicio')
				.on('shown.bs.modal.aiaActividadInicio', function() {
					limpiar_datos();
					configurarSelectActividadInicio('#actividadInicio', $modalNuevaActividad);
				})
				.off('hidden.bs.modal.aiaActividadInicio')
				.on('hidden.bs.modal.aiaActividadInicio', function() {
					var $selectActividadInicio = $('#actividadInicio');

					if ($selectActividadInicio.data('select2')) {
						$selectActividadInicio.select2('close');
					}
				});
		};

		var puedeEditarListadoActividades = function() {
			var rol = '';

			if (typeof readRbacRole === 'function') {
				rol = readRbacRole();
			} else {
				var permisoCanonico = document.getElementById('permiso_canonico');
				var permisoLegacy = document.getElementById('permiso_canonico');

				if (permisoCanonico && permisoCanonico.value) {
					rol = permisoCanonico.value;
				} else if (permisoLegacy && permisoLegacy.value) {
					rol = permisoLegacy.value;
				}
			}

			rol = String(rol || '').trim().toUpperCase();

			return rol === 'A' || rol === 'D' || rol === 'OT' || rol === 'R';
		};

		var escaparHtml = function(value) {
			return String(value == null ? '' : value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		};

		var inicializarAutoGenerarListado = function() {
			$(document).off('click.autoGenListado', '#btn_auto_generar_listado').on('click.autoGenListado', '#btn_auto_generar_listado', function(e) {
				e.preventDefault();
				if (window.SemiAutoReview) {
					window.SemiAutoReview.open('listado-actividades');
					return;
				}
				$('#modalAutoGenerarListado').modal('show');
			});

			$('#modalAutoGenerarListado').off('show.bs.modal.autoGenListado').on('show.bs.modal.autoGenListado', function() {
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Presiona "Analizar" para detectar actividades en el Programa General.');
				$('#autoGenListadoBody').html('');
				$('#btnAutoGenListadoAplicar').prop('disabled', true);
				$('#btnAutoGenListadoAnalizar').prop('disabled', false);
			});

			$('#btnAutoGenListadoAnalizar').off('click.autoGenListado').on('click.autoGenListado', function(e) {
				e.preventDefault();
				var btn = $(this);
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;

				btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Analizando...');
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Analizando actividades del Programa General...');
				$('#autoGenListadoBody').html('');

				$.ajax({
					method: 'POST',
					url: '/api/listado-actividades/auto-generate?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana) + '&preview=1',
					dataType: 'json'
				}).done(function(response) {
					if (!response || response.respuesta !== 'BIEN') {
						$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron cargar sugerencias.'));
						btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
						return;
					}

					var sugerencias = response.sugerencias || [];
					var gruposPreview = response.gruposPreview || [];
					var gruposCreadas = response.gruposCreadas || [];
					var gruposToShow = response.preview ? gruposPreview : gruposCreadas;
					var totalGrupos = response.totalGrupos || 0;
					var estrategia = response.estrategia || 'familia';
					var ratio = response.totalProcesadas > 0 ? (response.totalProcesadas / Math.max(response.creadas, 1)).toFixed(1) + ':1' : 'N/A';

					var msg = 'PG: <strong>' + response.totalProcesadas + '</strong> · ';
					msg += 'Familias/Grupos: <strong>' + totalGrupos + '</strong> · ';
					if (response.preview) {
						msg += 'Se crearán: <strong>' + response.creadas + '</strong> · ';
					} else {
						msg += 'Actividades consolidadas: <strong>' + response.creadas + '</strong> · ';
					}
					msg += 'Ratio: <strong>' + ratio + '</strong> · ';
					msg += 'Estrategia: <strong>' + estrategia + '</strong> · ';
					msg += 'Sin match: <strong>' + response.sinMatch + '</strong>';
					$('#autoGenListadoResumen').removeClass('alert-info alert-danger').addClass('alert-success').html(msg);

					// Separar sugerencias por tipo: sin match vs ya existia vs creada
					var sinMatchList = [];
					var yaExistiaList = [];
					for (var i = 0; i < sugerencias.length; i++) {
						var s = sugerencias[i];
						if (s.yaExistia === true) {
							yaExistiaList.push(s);
						} else if (s.creada !== true) {
							sinMatchList.push(s);
						}
					}

					// Mostrar primero los grupos consolidados (preview o creados)
					var html = '';
					if (gruposToShow.length > 0) {
						var sectionTitle = response.preview ? 'GRUPOS A CREAR' : 'GRUPOS CONSOLIDADOS CREADOS';
						var sectionIcon = response.preview ? 'fa-calculator' : 'fa-layer-group';
						html += '<tr style="background-color: #d4edda; font-weight: bold;"><td colspan="6"><i class="fas ' + sectionIcon + '"></i> ' + sectionTitle + ' (' + gruposToShow.length + ')</td></tr>';
						for (var g = 0; g < gruposToShow.length; g++) {
							var gr = gruposToShow[g];
							var badgeGrupo = '<span class="badge badge-success">+' + gr.totalActividades + ' PG</span>';
							html += '<tr style="background-color: #f0f8ff;">';
							html += '<td>' + (g + 1) + '</td>';
							html += '<td><strong>' + escaparHtml(gr.familia) + '</strong><br><small class="text-muted">' + escaparHtml((gr.descripcion || '').substring(0, 120)) + ((gr.descripcion || '').length > 120 ? '...' : '') + '</small></td>';
							html += '<td>' + escaparHtml(gr.fechaInicio || '-') + '</td>';
							html += '<td>' + escaparHtml(gr.familiaCodigo) + '</td>';
							html += '<td>' + (gr.confianzaMin || 0) + '%</td>';
							html += '<td>' + badgeGrupo + ' ' + (response.preview ? 'Preview' : 'Consolidado') + '</td>';
							html += '</tr>';
						}
					}

					// Mostrar grupos ya existentes (no son sin match, ya estaban creados)
					if (yaExistiaList.length > 0) {
						html += '<tr style="background-color: #e3f2fd; font-weight: bold;"><td colspan="6"><i class="fas fa-check-circle"></i> GRUPOS YA EXISTENTES (' + yaExistiaList.length + ')</td></tr>';
						for (var e = 0; e < yaExistiaList.length; e++) {
							var ex = yaExistiaList[e];
							var badgeEx = '<span class="badge badge-info">' + ex.totalActividades + ' PG</span>';
							html += '<tr style="background-color: #f5f5f5;">';
							html += '<td>' + (e + 1) + '</td>';
							html += '<td><strong>' + escaparHtml(ex.familia || 'N/A') + '</strong></td>';
							html += '<td>-</td>';
							html += '<td>' + escaparHtml(ex.familiaCodigo || '-') + '</td>';
							html += '<td>-</td>';
							html += '<td>' + badgeEx + ' Ya existe</td>';
							html += '</tr>';
						}
					}

					// Mostrar sugerencias sin match (solo las reales)
					if (sinMatchList.length > 0) {
						html += '<tr style="background-color: #fff3cd; font-weight: bold;"><td colspan="6"><i class="fas fa-exclamation-triangle"></i> SIN MATCH DE FAMILIA (' + sinMatchList.length + ')</td></tr>';
					}
					for (var i = 0; i < sinMatchList.length; i++) {
						var s = sinMatchList[i];
						var badge = '<span class="badge badge-secondary">Sin match</span>';
						var estado = escaparHtml(s.motivo || 'Sin familia detectada');
						html += '<tr><td>' + (i + 1) + '</td><td>' + escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : 'Sin nombre') + '</td><td>' + escaparHtml(s.fechaInicio || '-') + '</td><td>-</td><td>-</td><td>' + badge + ' ' + estado + '</td></tr>';
					}
					$('#autoGenListadoBody').html(html);
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
					// Enable Aplicar button if there are groups to create
					if (response.preview && (response.creadas > 0 || gruposPreview.length > 0)) {
						$('#btnAutoGenListadoAplicar').prop('disabled', false);
					}
				}).fail(function(xhr) {
					var mensaje = 'Error de conexión al analizar.';
					if (xhr.responseJSON && xhr.responseJSON.mensaje) {
						mensaje = xhr.responseJSON.mensaje;
					}
					$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				});
			});

			// Aplicar: create activities in DB (no preview)
			$('#btnAutoGenListadoAplicar').off('click.autoGenListado').on('click.autoGenListado', function(e) {
				e.preventDefault();
				var btn = $(this);
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;

				btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
				$('#btnAutoGenListadoAnalizar').prop('disabled', true);
				$('#autoGenListadoResumen').removeClass('alert-info alert-danger alert-success').addClass('alert-info').html('Creando actividades en el listado...');

				$.ajax({
					method: 'POST',
					url: '/api/listado-actividades/auto-generate?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
					dataType: 'json'
				}).done(function(response) {
					if (!response || response.respuesta !== 'BIEN') {
						$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron crear las actividades.'));
						btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Aplicar');
						$('#btnAutoGenListadoAnalizar').prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
						return;
					}

					var msg = 'Actividades creadas: <strong>' + response.creadas + '</strong>';
					if (response.existentes > 0) {
						msg += ' · Ya existentes: <strong>' + response.existentes + '</strong>';
					}
					if (response.sinMatch > 0) {
						msg += ' · Sin match: <strong>' + response.sinMatch + '</strong>';
					}
					msg += '. El listado se ha actualizado.';
					$('#autoGenListadoResumen').removeClass('alert-info alert-danger').addClass('alert-success').html(msg);
					$('#autoGenListadoBody').html('');

					// Close modal and refresh table after short delay
					setTimeout(function() {
						$('#modalAutoGenerarListado').modal('hide');
						recargarTabla('');
					}, 1200);

				}).fail(function(xhr) {
					var mensaje = 'Error de conexión al crear actividades.';
					if (xhr.responseJSON && xhr.responseJSON.mensaje) {
						mensaje = xhr.responseJSON.mensaje;
					}
					$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
					btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Aplicar');
					$('#btnAutoGenListadoAnalizar').prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				});
			});

			// Cleanup on modal dismiss
			$('#modalAutoGenerarListado').off('hidden.bs.modal.autoGenListado').on('hidden.bs.modal.autoGenListado', function() {
				$('#autoGenListadoBody').html('');
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success alert-warning').addClass('alert-info').html('Presiona "Analizar" para detectar actividades en el Programa General.');
				$('#btnAutoGenListadoAplicar').prop('disabled', true);
				$('#btnAutoGenListadoAnalizar').prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				recargarTabla('');
			});
		};

		var cargaParametros = function() {
			inicializarModalNuevaActividad();
			inicializarAutoGenerarListado();
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
			var semana = document.getElementById('Max_Semana').value;
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
		    "scrollX": false, /* PROHIBIDO SCROLL HORIZONTAL */
		    //                console.log($(document).height());
		    "scrollY": alturatabla,
		    /*                "scrollCollapse": false,*/
		    "responsive": true,
		    "paging": false,
		    "ajax": {
		      "method": "POST",
		      "url": "/api/listado-actividades/list?db="+db+"&semana="+Max_Semana
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [
					{
						'targets': '_all',
						'createdCell': function (td, cellData, rowData, row, col) {
							var headers = ['', 'Id', 'Id', 'Actividad', 'Descripción', 'Actividad de Inicio', 'Actividad de Inicio', 'Fecha de Inicio', 'Tipo de Contrato', 'Semana de Actualización'];
							if (headers[col]) {
								$(td).attr('data-label', headers[col]);
							}
						}
					},
				{
				'targets': [8],
				'render': function ( data, type, full, meta ) {
						if (!data || data === '') {
							return '<span class="text-muted">Sin asignar</span>';
						}
						var modalidades = data.split(',');
						var badges = {
							'SI': '<span class="badge badge-primary">Suministro e Instalación</span>',
							'MO': '<span class="badge badge-info">Mano de Obra</span>',
							'S':  '<span class="badge badge-secondary">Suministro</span>',
							'OC': '<span class="badge badge-dark">Orden de Compra</span>'
						};
						var result = [];
						for (var i = 0; i < modalidades.length; i++) {
							var m = modalidades[i].trim();
							if (badges[m]) {
								result.push(badges[m]);
							} else if (m) {
								result.push(m);
							}
						}
						return result.length > 0 ? result.join(' ') : '<span class="text-muted">Sin asignar</span>';
					},
				},
					{
						'targets': [0],
						'width':'4%',
					},
					{
						'targets': [2],
						'width':'10%',
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
						'width':'20%',
					},
					{
						'targets': [7],
						'width':'10%',
					},
					{
						'targets': [8],
						'width':'16%',
					},
					{
						'targets': [1,2,3,4,5,6,7,8,9],
						'render': function ( data, type, full, meta ) {
						 return data;
						},
					},
				],
				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm btn-action-gap'  title='Editar'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm btn-action-gap'  title='Eliminar'><i class='fa fa-trash-alt fa-xs'></i></button>"},
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

			// Dynamic Resize Listener
			$(window).off('resize.dtListado orientationchange.dtListado aia:viewport-scale-change.dtListado').on('resize.dtListado orientationchange.dtListado aia:viewport-scale-change.dtListado', function() {
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

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:50%;display:inline-block; "><button id="btn_cargarActividadesExcel" class="btn-pdc-modern" title="Cargar listado de actividades desde Excel" data-toggle="modal" data-target="#modalCargarExcel">Cargar desde Excel <i class="fas fa-upload fa-lg"></i></button><button id="btn_nueva_actividad" class="btn-pdc-modern" title="Registrar nueva actividad del proyecto" data-toggle="modal" data-target="#modalNuevaActividad" style="margin: auto 5px">Nueva Actividad <i class="fas fa-plus fa-lg"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn-pdc-modern active" onclick="window.location.href=\'/listado-actividades?semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn-pdc-modern" onclick="window.location.href=\'/contratos?semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_planCompras" type="button" class="btn-pdc-modern" onclick="window.location.href=\'/pdc?semana='+semana+'&origen=info_listadoActividades\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaBotones .grupo_botones1")
				.addClass("ps-toolbar-actions")
				.removeAttr("style");
			$("div.toolbarFilaBotones .grupo_botones1 .btn").addClass("ps-btn-gap");
			if (puedeEditarListadoActividades()) {
				$("div.toolbarFilaBotones .grupo_botones1").append('<button id="btn_auto_generar_listado" class="btn-pdc-modern ps-btn-gap" title="Auto-generar listado de actividades desde el Programa General"><i class="fas fa-magic"></i> Auto-generar Listado</button>');
			}
			if (window.SemiAutoReview) {
				window.SemiAutoReview.init({
					module: 'listado-actividades',
					anchorSelector: 'div.toolbarFilaMensajes',
					refresh: function() { recargarTabla('listar'); }
				});
			}
			$("div.toolbarFilaBotones #btn_nueva_actividad").removeAttr("style");
			$("div.toolbarFilaBotones .grupo_botones_semanal_madre")
				.addClass("ps-toolbar-nav-wrap")
				.removeAttr("style")
				.html('<div class="ps-module-switcher" role="tablist" aria-label="Navegacion general"><button id="btn_Actividades" type="button" class="ps-module-tab is-active" onclick="window.location.href=\'/listado-actividades?semana='+semana+'\'" aria-label="Ir a Actividades" aria-current="page"><i class="fas fa-table" aria-hidden="true"></i><span>Actividades</span></button><button id="btn_contratos" type="button" class="ps-module-tab" onclick="window.location.href=\'/contratos?semana='+semana+'\'" aria-label="Ir a Contratos"><i class="fas fa-file-alt" aria-hidden="true"></i><span>Contratos</span></button><button id="btn_planCompras" type="button" class="ps-module-tab" onclick="window.location.href=\'/pdc?semana='+semana+'&origen=info_listadoActividades\'" aria-label="Ir a Plan de Compras"><i class="fas fa-shopping-cart" aria-hidden="true"></i><span>Plan de Compras</span></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="d-flex ml-auto"><label class="sr-only">Buscar en listado</label><button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso_canonico').value);
		  obtener_data_editar("#dt_cliente tbody", table);
		  obtener_id_eliminar("#dt_cliente tbody", table);
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  $(tbody)
				.off("click.aiaEditarActividad", "td:not(:first-child), button.editar");

		  if (!puedeEditarListadoActividades()) {
				return;
		  }

		  var only_once = true;

		  $(tbody).on("click.aiaEditarActividad", "td:not(:first-child), button.editar", function(e) {
					e.preventDefault();
					e.stopPropagation();

					var $row = $(this).closest("tr");

				var data= table.row($row).data();

				if (!data) {
					return;
				}

				var Id=$("#Id").val(data.Id),
						opcion = $("#opcion").val("modificar"),
						codigo = $("#codigo").val(data.codigo);
		    if (only_once == true) {
					var codigo_html_Actividad =  "<input id='select_Actividad' name='Actividad' class='form-control form-control-sm' type='text' value='"+data.actividad+"'></input>";
					$row.find('td:eq(2)').html(codigo_html_Actividad);

					var codigo_html_descripcionActividad =  "<input id='select_descripcionActividad' name='descripcionActividad' class='form-control form-control-sm' type='text' value='"+data.descripcionActividad+"'></input>";
					$row.find('td:eq(3)').html(codigo_html_descripcionActividad);

					var opciones_codigo_html_actividadInicio = <?php echo json_encode($actividadInicioOptionsHtml, JSON_UNESCAPED_UNICODE); ?>;

					var codigo_html_actividadInicio =  "<select id='select_actividadInicio' name='actividadInicio' class='form-control form-control-sm' onchange=actualizarFechaInicio('actualizar')><option value=''></option>";
					codigo_html_actividadInicio = codigo_html_actividadInicio + opciones_codigo_html_actividadInicio + "</select>";
					$row.find('td:eq(4)').html(codigo_html_actividadInicio);
					configurarSelectActividadInicio('#select_actividadInicio');

					var codigo_html_fechaInicio =  "<input id='select_fechaInicio' name='fechaInicio' class='form-control form-control-sm' type='text' value='"+data.fechaInicio+"' autocomplete='off'></input>";
					$row.find('td:eq(5)').html(codigo_html_fechaInicio);

					$( "#select_fechaInicio" ).datepicker({dateFormat: 'yy-mm-dd',
																							 changeMonth: true,
																							 changeYear: true,
																							 showOtherMonths: true,
																							 selectOtherMonths: true,
																							 defaultDate:data.fechaInicio,
																							});



				var codigo_html_tipoContrato =  "<span class='text-muted'>" + escaparHtml(data.tipoContrato || 'Sin asignar') + "</span>";
				$row.find('td:eq(6)').html(codigo_html_tipoContrato);

					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm btn-action-gap' title='Guardar la edición'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm btn-action-gap' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$row.find('td:eq(0)').html(codigo_html_botones);

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
		  $(tbody).off("click.aiaEliminarActividad", "button.eliminar");

		  var canEdit = puedeEditarListadoActividades();

		  if (!canEdit) {
			// No hace nada
		  } else {
				$(tbody).on("click.aiaEliminarActividad", "button.eliminar", function(e) {
						e.preventDefault();
						e.stopPropagation();

					var data= table.row($(this).parents("tr")).data();

					if (!data) {
						return;
					}

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
				var semana = document.getElementById('Max_Semana').value;
				var frm = $(this).serialize();
				frm = frm + "&semana=" + semana;
				$.ajax({
					method: "POST",
					url: "/api/listado-actividades/save?db="+db,
					contenttype: "charset=utf-8",
					data: frm,
				}).done(function(info) {
					var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
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
				var semana = document.getElementById('Max_Semana').value;
		    var variables = new FormData($("#formCargarExcel")[0]);
		    //var frm = $(this).serialize();
		    console.log(variables);
		    $.ajax({
		      type: "POST",
		      url: "/api/listado-actividades/save?db="+db,
		      contentType: false,
		      processData: false,
		      data: variables,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
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
				var semana = document.getElementById('Max_Semana').value;
				var Id = $("#Id").serialize();
				var opcion = $("#opcion").serialize();
				var codigo = $("#codigo").serialize();
				var Actividad = $("#select_Actividad").serialize();
				var descripcionActividad = $("#select_descripcionActividad").serialize();
				var actividadInicio = $("#select_actividadInicio").serialize();
				var fechaInicio = $("#select_fechaInicio").serialize();

				frm = Id + "&" + opcion + "&" + codigo + "&" + Actividad + "&" + descripcionActividad + "&" + actividadInicio + "&" + fechaInicio + "&semana=" + semana;
				// console.log(frm);
				$.ajax({
					method: "POST",
					url: "/api/listado-actividades/save?db="+db,
					contenttype: "charset=utf-8",
					data: frm,
				}).done(function(info) {
					var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
					// console.log(json_info);
					recargarTabla('');
				});
			});
		}

		/* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
		var eliminar = function() {
		  $("#eliminar-usuario").on("click", function() {
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;
		    	var Id = $("#Id").val(),
		      	opcion = "eliminar";
				//console.log(Id, opcion);
		    $.ajax({
		      method: "POST",
		      url: "/api/listado-actividades/save?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "Id": Id,
		        "opcion": opcion
		      }
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
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
				var nombreActividad = document.getElementById("select_actividadInicio").value;
		  }

			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('Max_Semana').value;
		  //console.log(opcion, idActividad, nombreActividad, semana);
		  if (idActividad != "") {
		    $.ajax({
		      method: "POST",
		      url: "/api/listado-actividades/save?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "idActividad": idActividad,
		        "nombreActividad": nombreActividad,
		        "opcion": opcion,
		        "semana": semana
		      }
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
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
			var $formNuevaActividad = $("#modalNuevaActividad form");
			var $actividadInicio = $("#modalNuevaActividad #actividadInicio");

			if ($formNuevaActividad.length && $formNuevaActividad[0]) {
				$formNuevaActividad[0].reset();
			}

			if ($actividadInicio.length) {
				if ($actividadInicio.data('select2')) {
					$actividadInicio.val(null).trigger('change');
				} else {
					$actividadInicio.val('');
				}
			}

			$("#modalNuevaActividad #fechaInicio").val("");
			$("#modalNuevaActividad #tipoContrato").val("");
			$("#modalNuevaActividad .mensaje").html("");
			$("#modalNuevaActividad #actividad").focus();
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
