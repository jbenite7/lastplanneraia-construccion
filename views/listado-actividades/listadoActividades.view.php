<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
	<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<script>window.__AIA_HANDSONTABLE_ONLY__ = true;</script>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::render(true) ?>
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711listadoCssPurge3" charset="utf-8"></script>
	<link rel="stylesheet" href="/vendor/handsontable/handsontable.full.min.css?v=14.6.1" />
	<link rel="stylesheet" href="/css/handsontable-module.css?v=20260711foundation5" />
	<link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260313" />
	<link rel="stylesheet" href="/css/listado-actividades.css?v=20260711listadoSprint2" />
</head>

<!--Etiqueta superior-->
<body>
	<?php
        $dbPrefixListadoActividades = $_SESSION['db'] ?? '';
	$projectIdListadoActividades = (int) ($_SESSION['project_id'] ?? ($projectId ?? 0));
	$requestSemanaListadoActividades = filter_input(INPUT_GET, 'semana', FILTER_VALIDATE_INT) ?: 0;
	$maxSemanaListadoActividades = (int) ($_SESSION['Max_Semana'] ?? ($_SESSION['semana'] ?? $requestSemanaListadoActividades));
	$semanaContextListadoActividades = $requestSemanaListadoActividades > 0 ? $requestSemanaListadoActividades : $maxSemanaListadoActividades;
	$actividadInicioOptionsHtml = '';

	if (empty($dbPrefixListadoActividades) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefixListadoActividades) || $projectIdListadoActividades <= 0) {
	    $actividadInicioOptionsHtml = '<option value="">Error: Database prefix not set</option>';
	} else {
	    try {
	        $dbInstance = Database::getInstance();
	        $queryActividadInicio = "SELECT unique_id AS Consecutivo_en_Programa, unique_id, Id, Actividad, Fecha_Inicio FROM programa_consolidado WHERE project_id = ? AND Semana=? AND Titulo=0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Fecha_Inicio ASC";
	        $stmtActividadInicio = $dbInstance->query($queryActividadInicio, [$projectIdListadoActividades, $semanaContextListadoActividades]);
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
	<script>
		window.__LISTADO_ACTIVIDADES_CONTEXT__ = <?php echo json_encode([
		    'db' => $dbPrefixListadoActividades,
		    'semana' => (string) $semanaContextListadoActividades,
		    'permiso' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
		    'proyecto' => (string) ($_SESSION['proyecto'] ?? ''),
		], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
		window.applyListadoActividadesContextFallback = function() {
			var ctx = window.__LISTADO_ACTIVIDADES_CONTEXT__ || {};
			[['baseDatos', ctx.db], ['Max_Semana', ctx.semana], ['semana', ctx.semana], ['proyecto', ctx.proyecto], ['permiso_canonico', ctx.permiso]].forEach(function(item) {
				var input = document.getElementById(item[0]);
				if (input && !input.value && item[1]) {
					input.value = item[1];
				}
			});
			if (document.getElementById('ctxProyecto') && ctx.proyecto) {
				document.getElementById('ctxProyecto').textContent = ctx.proyecto;
			}
			if (document.getElementById('ctxModulo')) {
				document.getElementById('ctxModulo').textContent = 'Familias de obra';
			}
			if (document.getElementById('ctxSemanaTexto') && ctx.semana) {
				document.getElementById('ctxSemanaTexto').textContent = 'Semana ' + ctx.semana;
			}
		};
	</script>

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
	<div class="row formularioRegistro la-hidden-registration">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row filaBotones mb-2 align-items-center">
		<div class="col-12 p-0">
			<div class="toolbarFilaBotones"></div>
		</div>
	</div>
	<div class="row filaMensajes">
		<div class="col-sm-8 mr-auto p-0">
			<div class="toolbarFilaMensajes"></div>
		</div>
		<div class="col-sm-4 ml-auto p-0">
			<div class="toolbarFiltro"></div>
		</div>
	</div>
	<div class="row tabla table-responsive-custom">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100">
			<div id="hot-container" class="w-100 hot-mobile-grid la-hot-container"></div>
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
		          <h2 class="aia-modal__headline">Registrar Nueva Familia</h2>
		          <p class="aia-modal__subtitle modal-body-texto-nuevaActividad" id="modal-body-texto-nuevaActividad">Completa la informacion base de la familia y su relacion con el cronograma.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12">
		            <form class="form form-horizontal aia-modal__form" action="" method="POST">
		              <section class="form-group parametro_nuevaActividad aia-modal__section">
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Formulario de Registro de Familias</h3>
		                  <p class="aia-modal__hint">Define la familia, su descripcion y la referencia de inicio en obra segun cronograma.</p>
		                </div>
		                <div class="aia-modal__field-grid">
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
										<input type="hidden" id="nuevaActividadId" name="Id" value="">
					      <input type="hidden" id="nuevaActividadOpcion" name="opcion" value="registrar">
		                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="actividad" class="control-label aia-modal__label">Familia</label><input id="actividad" name="actividad" type="text" class="form-control">
		                </div>
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="descripcionActividad" class="control-label aia-modal__label">Descripcion</label><input id="descripcionActividad" name="descripcionActividad" type="text" class="form-control">
		                </div>
		                <div class="col-sm-12 aia-modal__field">
			                  <label for="actividadInicio" class="control-label aia-modal__label">Inicio en obra segun cronograma</label>
			                  <select id="actividadInicio" name="actividadInicio" class="form-control la-full-width" onchange="actualizarFechaInicio('nuevo')">
		                    <option value=""></option><?php echo $actividadInicioOptionsHtml; ?>
		                  </select>
		                </div>
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="fechaInicio" class="control-label aia-modal__label">Fecha de Inicio</label><input id="fechaInicio" name="fechaInicio" type="text" class="form-control">
		                </div>
		                <div class="col-sm-12 aia-modal__field">
		                  <label class="control-label aia-modal__label">Modalidad de contratacion <span class="text-danger" aria-hidden="true">*</span></label>
		                  <div id="tipoContratoPillsContainer">
		                    <?php
		                    $tipoContratoOpciones = [
		                        ['value' => 'S',  'label' => 'Suministro'],
		                        ['value' => 'MO', 'label' => 'Mano de Obra'],
		                        ['value' => 'SI', 'label' => 'Suministro e Instalación'],
		                        ['value' => 'OC', 'label' => 'Orden de servicio/compra'],
		                    ];
		                    ?>
		                  </div>
		                  <input type="hidden" id="tipoContrato" name="tipoContrato" value="">
		                  <small class="aia-tipo-hint" id="tipoContratoHint">Puedes combinar varias modalidades. Si eliges <strong>Suministro e Instalación</strong>, las demás se bloquean porque ya las incluye.</small>
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
		          <h2 class="aia-modal__headline">Cargar Familias de obra desde Excel</h2>
		          <p class="aia-modal__subtitle modal-body-texto-cargarExcel" id="modal-body-texto-cargarExcel">Descarga la plantilla base y carga el archivo CSV completo de familias de obra.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadroCargarExcel" class="cuadro4 col-sm-12 col-md-12 col-lg-12">
		            <form enctype="multipart/form-data" class="form form-horizontal aia-modal__form" id="formCargarExcel" name="formCargarExcel" action="" method="POST">
		              <section class="form-group parametro_cargarExcel aia-modal__section aia-modal__section--plain">
		                <!-- <div class="form_eval form-group">
													<h3 id='form_general'>
														Descargar Archivo Base
													</h3>
												</div> -->
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Descargar Archivo Base</h3>
		                  <p class="aia-modal__hint">Usa la plantilla oficial para asegurar la estructura correcta del cargue masivo.</p>
		                </div>
		                <label for="descargarArchivoBase" class="control-label aia-modal__label">En el siguiente enlace puede descargar el archivo base para crear familias de obra desde Excel:</label>
		                <a id="descargarArchivoBase" class="descargarArchivoBase btn btn-primary" download="listadoActividades.csv" href="/api/listado-actividades/template">Descargar Archivo Base</a>
		              </section>
		              <section class="form-group parametro_cargarExcel aia-modal__section">
		                <div class="aia-modal__section-header">
		                  <h3 class="aia-modal__section-title">Cargar Familias en Excel</h3>
		                  <p class="aia-modal__hint">Solo se permiten archivos en formato CSV y se procesara el contenido completo de familias de obra.</p>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es cargarExcel-->
		                <input type="hidden" id="cargarExcelId" name="Id" value="">
		                <input type="hidden" id="cargarExcelOpcion" name="opcion" value="cargarExcel">
		                <input type="hidden" id="cargarExcelCodigo" name="codigo" value="">
		                <!-- Se crea el input para cargar el archivo CSV que cargarà el listado de actividades del proyecto -->
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="archivoExcel" class="control-label aia-modal__label">Seleccione el archivo con las familias de obra completas desde el equipo (solo se permiten archivos en formato CSV):</label>
		                  <input type="file" name="archivoExcel" id="archivoExcel" class="form-control form-control-lg" accept=".csv,text/csv" required>
		                  <!-- <input type="submit" value="Enviar" name="archivoExcel"> -->
		                </div>
		              </section>
		              <div class="form-group aia-modal__actions">
		                <div class="col-sm-12 aia-modal__buttons">
		                  <input id="" type="submit" class="btn btn-success" value="Guardar">
		                  <input id="btn_cancelar_carga" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal">
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

	<!-- Modal Auto-Generar Familias desde Programa General -->
	<div class="modal_autoGenerarListado modal fade aia-modal" id="modalAutoGenerarListado" role="dialog" aria-labelledby="modalAutoGenerarListadoLabel">
	  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <div class="modal-title" id="modalAutoGenerarListadoLabel">
	          <div class="aia-modal__eyebrow">AIA Corporativo</div>
	          <h2 class="aia-modal__headline">Auto-generar Familias</h2>
	          <p class="aia-modal__subtitle" id="modalAutoGenerarListadoSubtitle">Analiza el Programa General y crea familias de obra vinculadas automaticamente.</p>
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
	                Presiona "Analizar" para detectar familias en el Programa General.
	              </div>
	            </div>
	            <div class="table-responsive la-preview-table-shell">
	              <table class="table table-sm table-bordered table-hover mb-0" id="autoGenListadoTable">
	                <thead class="thead-light">
	                  <tr>
	                    <th class="la-preview-index">#</th>
	                    <th>Familia (PG)</th>
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
		          <h2 class="aia-modal__title">Eliminar Familia</h2>
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
	<script type="text/javascript" charset="utf8" src="/vendor/jquery.min.js"></script>
	<!-- Iniciar Popper-->
	<script type="text/javascript" charset="utf8" src="/vendor/popper.min.js"></script>
	<!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="/vendor/bootstrap/bootstrap.min.js"></script>
	<!--Selector de fechas -->
	<script src="/vendor/jquery-ui.min.js"></script>
	<!--Google Charts-->
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=20260708theme" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/semi_auto_review.js?v=20260711-undo-run1" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		window.getCsrfToken = window.getCsrfToken || function() {
			var meta = document.querySelector('meta[name="csrf-token"]');
			return meta && meta.content ? meta.content : '';
		};

		document.addEventListener('DOMContentLoaded', function() {
			if (!window.jQuery) {
				return;
			}

			window.jQuery(document).ajaxSend(function (_event, xhr, settings) {
				var url = settings && settings.url ? String(settings.url) : '';
				if (url.indexOf('/api/listado-actividades/') === 0) {
					xhr.setRequestHeader('X-CSRF-Token', window.getCsrfToken());
				}
			});
		});

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

		var etiquetaModalidadContratacion = function(value) {
			var etiquetas = {
				SI: 'Suministro e Instalación',
				MO: 'Mano de Obra',
				S: 'Suministro',
				OC: 'Orden de servicio/compra'
			};
			return String(value || '').split(',').map(function(item) {
				var key = item.trim();
				return etiquetas[key] || key;
			}).filter(Boolean).join(', ');
		};

		/* Construye el HTML del toggle de modalidad (compartido por modal e inline).
		 * - variant: 'modal' (vertical full) o 'inline' (grid 2x2 compacto)
		 * - selectedCodes: array con los codigos pre-seleccionados (ej: ['S','MO'])
		 * - disabled: bool - si true, todas las pills se renderizan deshabilitadas
		 */
		var buildTipoContratoPills = function(variant, selectedCodes, disabled) {
			var layoutClass = variant === 'inline' ? 'aia-tipo-toggle aia-tipo-toggle--inline' : 'aia-tipo-toggle';
			var opcs = [
				{ code: 'S',  cls: 'aia-tipo-pill--s',  label: 'Suministro' },
				{ code: 'MO', cls: 'aia-tipo-pill--mo', label: 'Mano de Obra' },
				{ code: 'SI', cls: 'aia-tipo-pill--si', label: 'Suministro e Instalación' },
				{ code: 'OC', cls: 'aia-tipo-pill--oc', label: 'Orden de servicio/compra' },
			];
			var sel = Array.isArray(selectedCodes) ? selectedCodes : [];
			var siSelected = sel.indexOf('SI') !== -1;
			var h = '<div class="' + layoutClass + '" role="group" aria-label="Modalidad de contratacion">';
			for (var i = 0; i < opcs.length; i++) {
				var o = opcs[i];
				var checked = sel.indexOf(o.code) !== -1;
				var dis = (disabled || (siSelected && o.code !== 'SI')) ? ' disabled' : '';
				var stateCls = checked ? ' is-checked' : '';
				var disCls = (disabled || (siSelected && o.code !== 'SI')) ? ' is-disabled' : '';
				h += '<label class="aia-tipo-pill ' + o.cls + stateCls + disCls + '"'
					+ ' data-tipo-code="' + o.code + '">'
					+ '<input type="checkbox" name="tipoContratoCheck" value="' + o.code + '"'
					+ (checked ? ' checked' : '') + dis + ' aria-label="' + escaparHtml(o.label) + '">'
					+ '<span class="aia-tipo-pill__label">' + escaparHtml(o.label) + '</span>'
					+ '<span class="aia-tipo-pill__code">' + o.code + '</span>'
					+ '</label>';
			}
			h += '</div>';
			return h;
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
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Presiona "Analizar" para detectar familias en el Programa General.');
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
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Analizando familias del Programa General...');
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
						msg += 'Familias consolidadas: <strong>' + response.creadas + '</strong> · ';
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
						html += '<tr class="la-preview-section la-preview-section-created"><td colspan="6"><i class="fas ' + sectionIcon + '"></i> ' + sectionTitle + ' (' + gruposToShow.length + ')</td></tr>';
						for (var g = 0; g < gruposToShow.length; g++) {
							var gr = gruposToShow[g];
							var badgeGrupo = '<span class="badge badge-success">+' + gr.totalActividades + ' PG</span>';
							html += '<tr class="la-preview-row-created">';
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
						html += '<tr class="la-preview-section la-preview-section-existing"><td colspan="6"><i class="fas fa-check-circle"></i> GRUPOS YA EXISTENTES (' + yaExistiaList.length + ')</td></tr>';
						for (var e = 0; e < yaExistiaList.length; e++) {
							var ex = yaExistiaList[e];
							var badgeEx = '<span class="badge badge-info">' + ex.totalActividades + ' PG</span>';
							html += '<tr class="la-preview-row-existing">';
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
						html += '<tr class="la-preview-section la-preview-section-warning"><td colspan="6"><i class="fas fa-exclamation-triangle"></i> SIN MATCH DE FAMILIA (' + sinMatchList.length + ')</td></tr>';
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
				$('#autoGenListadoResumen').removeClass('alert-info alert-danger alert-success').addClass('alert-info').html('Creando familias de obra...');

				$.ajax({
					method: 'POST',
					url: '/api/listado-actividades/auto-generate?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
					dataType: 'json'
				}).done(function(response) {
					if (!response || response.respuesta !== 'BIEN') {
						$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron crear las familias.'));
						btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Aplicar');
						$('#btnAutoGenListadoAnalizar').prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
						return;
					}

					var msg = 'Familias creadas: <strong>' + response.creadas + '</strong>';
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
				$('#autoGenListadoResumen').removeClass('alert-danger alert-success alert-warning').addClass('alert-info').html('Presiona "Analizar" para detectar familias en el Programa General.');
				$('#btnAutoGenListadoAplicar').prop('disabled', true);
				$('#btnAutoGenListadoAnalizar').prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				recargarTabla('');
			});
		};

		var listadoParametrosInitialized = false;
		var bootstrapListadoActividades = function() {
			if (listadoParametrosInitialized) {
				if (window.ListadoActividadesHotModule && !window.ListadoActividadesHotModule.getHotInstance()) {
					window.ListadoActividadesHotModule.init();
				}
				return;
			}
			listadoParametrosInitialized = true;
				inicializarModalNuevaActividad();
				inicializarAutoGenerarListado();
	      listar();
				inicializarFormulariosListado();
      eliminar();
		}
		var cargaParametros = bootstrapListadoActividades;

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

		/*Inicializa la única tabla runtime del módulo: Handsontable.*/
		var listar = function() {
			var puedeEditar = typeof puedeEditarListadoActividades === 'function' && puedeEditarListadoActividades();
			var botonesEdicion = puedeEditar
				? '<button id="btn_cargarActividadesExcel" class="btn-pdc-modern" title="Cargar familias de obra desde Excel" data-toggle="modal" data-target="#modalCargarExcel"><span class="la-btn-full">Cargar desde Excel</span><span class="la-btn-short">Excel</span> <i class="fas fa-upload fa-lg"></i></button>' +
				  '<button id="btn_nueva_actividad" class="btn-pdc-modern la-btn-gap" title="Registrar nueva familia" data-toggle="modal" data-target="#modalNuevaActividad"><span class="la-btn-full">Nueva Familia</span><span class="la-btn-short">Nueva</span> <i class="fas fa-plus fa-lg"></i></button>'
				: '';
			$("div.toolbarFilaBotones").html(
				'<div class="grupo_botones1 la-toolbar-actions" role="group" aria-label="Basic example">' +
				botonesEdicion +
				'</div>' +
				'<div class="grupo_botones_semanal_madre la-toolbar-switcher">' +
				'<div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example">' +
				'<button id="btn_Actividades" type="button" class="btn-pdc-modern active" onclick="window.location.href=\'/listado-actividades?semana=\' + document.getElementById(\'Max_Semana\').value + \'\'">Familias de obra <i class="fas fa-arrow-right fa-m"></i></button>' +
				'<button id="btn_contratos" type="button" class="btn-pdc-modern" onclick="window.location.href=\'/contratos?semana=\' + document.getElementById(\'Max_Semana\').value + \'\'">Paquetes de contratacion <i class="fas fa-arrow-right fa-m"></i></button>' +
				'<button id="btn_planCompras" type="button" class="btn-pdc-modern" onclick="window.location.href=\'/pdc?semana=\' + document.getElementById(\'Max_Semana\').value + \'&origen=info_listadoActividades\'">Plan de Compras y Contrataciones</button>' +
				'</div></div>'
			);

			$("div.toolbarFilaBotones .grupo_botones1")
				.addClass("ps-toolbar-actions")
				.removeAttr("style");
			$("div.toolbarFilaBotones .grupo_botones1 .btn").addClass("ps-btn-gap");
			if (puedeEditar) {
				$("div.toolbarFilaBotones .grupo_botones1").append('<button id="btn_auto_generar_listado" class="btn-pdc-modern ps-btn-gap" title="Auto-generar familias desde el Programa General"><i class="fas fa-magic"></i> <span class="la-btn-full">Auto-generar Familias</span><span class="la-btn-short">Auto</span></button>');
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
				.html(window.AIAInfoGeneralNav.render('listado', document.getElementById('Max_Semana').value, 'info_listadoActividades'));

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="d-flex ml-auto"><label class="sr-only">Buscar en listado</label><button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso_canonico').value);

			// Inicializar Handsontable
			if (window.ListadoActividadesHotModule) {
				window.ListadoActividadesHotModule.init();
			}
		}

		// Handsontable gestiona la edición inline y el botón de cada fila abre la confirmación de eliminación.

			var inicializarFormulariosListado = function() {
			  $("#modalCargarExcel form").off("submit.laListado").on("submit.laListado", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;
		    var variables = new FormData($("#formCargarExcel")[0]);
		    $.ajax({
		      type: "POST",
		      url: "/api/listado-actividades/save?db="+db+"&semana="+encodeURIComponent(semana),
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
		      if (json_info && json_info.respuesta == "BIEN") recargarTabla('');
		    }).fail(function() {
		      mostrar_mensaje({ respuesta: 'ERROR', mensaje: 'No fue posible cargar el archivo.' });
		    });
		  });

			  $("#modalNuevaActividad form").off("submit.laListado").on("submit.laListado", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;
		    var $form = $("#modalNuevaActividad form");
		    var checks = $form.find('input[name="tipoContratoCheck"]:checked').map(function() {
		      return $(this).val();
		    }).get();
		    var tipoContrato = checks.join(',');

		    $form.find('input[name="tipoContrato"]').val(tipoContrato);

		    if (!tipoContrato) {
		      mostrar_mensaje({ respuesta: 'VACIO' });
		      $form.find('input[name="tipoContratoCheck"]').first().focus();
		      return;
		    }

		    var variables = new FormData($form[0]);
		    $.ajax({
		      type: "POST",
		      url: "/api/listado-actividades/save?db="+db+"&semana="+encodeURIComponent(semana),
		      contentType: false,
		      processData: false,
		      data: variables,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      if (json_info && json_info.respuesta == "BIEN") {
		        limpiar_datos();
		        json_info.respuesta = json_info.respuesta + "NuevaActividad";
		      }
		      mostrar_mensaje(json_info || { respuesta: 'ERROR' });
		      recargarTabla('');
		    }).fail(function() {
		      mostrar_mensaje({ respuesta: 'ERROR' });
		    });
		  });

		  var sincronizarBloqueoSi = function($container) {
		    var $ctx = $container ? $container : $("#modalNuevaActividad");
		    var $siPill = $ctx.find('.aia-tipo-pill[data-tipo-code="SI"]');
		    var $siCheckbox = $siPill.find('input[type="checkbox"]');
		    var $otherPills = $ctx.find('.aia-tipo-pill').not($siPill);
		    if ($siCheckbox.is(':checked')) {
		      $otherPills
		        .removeClass('is-checked')
		        .addClass('is-disabled')
		        .find('input[type="checkbox"]').prop('checked', false).prop('disabled', true);
		      $siPill.addClass('is-checked');
		      var $hint = $ctx.find('#tipoContratoHint');
		      if ($hint.length) {
		        $hint.html('Suministro e Instalación ya incluye las demas modalidades, asi que quedan bloqueadas.');
		      }
		    } else {
		      $otherPills
		        .removeClass('is-disabled')
		        .find('input[type="checkbox"]').prop('disabled', false);
		      var $hint2 = $ctx.find('#tipoContratoHint');
		      if ($hint2.length) {
		        $hint2.html('Puedes combinar varias modalidades. Si eliges <strong>Suministro e Instalación</strong>, las demás se bloquean porque ya las incluye.');
		      }
		    }
		  };
		  /* Sincronizar visual is-checked al cambiar cualquier checkbox */
		  var sincronizarVisualChecks = function($container) {
		    var $ctx = $container ? $container : $(document);
		    $ctx.find('input[name="tipoContratoCheck"]').each(function() {
		      var $cb = $(this);
		      var $pill = $cb.closest('.aia-tipo-pill');
		      if ($cb.is(':checked')) {
		        $pill.addClass('is-checked');
		      } else {
		        $pill.removeClass('is-checked');
		      }
		    });
		  };
		  var onCheckChange = function() {
		    sincronizarBloqueoSi($(this).closest('.aia-tipo-toggle').length ? $(this).closest('.aia-tipo-toggle') : $("#modalNuevaActividad"));
		    sincronizarVisualChecks();
		  };
		  $("#modalNuevaActividad").off('change.laListado', 'input[name="tipoContratoCheck"]')
		    .on('change.laListado', 'input[name="tipoContratoCheck"]', onCheckChange);
		  sincronizarBloqueoSi($("#modalNuevaActividad"));
		  sincronizarVisualChecks();

		  /* Inline edit: SI bloquea demas checkboxes (event delegation) */
		  $(document).off('change.laListadoSi', '.aia-tipo-pill[data-tipo-code="SI"] input[type="checkbox"]')
		    .on('change.laListadoSi', '.aia-tipo-pill[data-tipo-code="SI"] input[type="checkbox"]', function() {
		    var $toggle = $(this).closest('.aia-tipo-toggle');
		    sincronizarBloqueoSi($toggle);
		    sincronizarVisualChecks($toggle);
		  });
		  $(document).off('change.laListadoChecks', '.aia-tipo-pill input[name="tipoContratoCheck"]')
		    .on('change.laListadoChecks', '.aia-tipo-pill input[name="tipoContratoCheck"]', function() {
		    sincronizarVisualChecks($(this).closest('.aia-tipo-toggle'));
		  });
		}

		/* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
		var eliminar = function() {
		  $("#eliminar-usuario").off("click.laListado").on("click.laListado", function() {
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
		function showListadoMessage(selector, texto, messageClass, delayMs) {
			var $target = $(selector);
			$target
				.removeClass('la-message-success la-message-error')
				.addClass(messageClass)
				.html(texto);
			$target.fadeOut(delayMs, function() {
				$(this).html("");
				$(this).removeClass('la-message-success la-message-error');
				$(this).fadeIn(3000);
			});
		}

		var mostrar_mensaje = function(informacion) {
			var texto = "",
				messageClass = "";
			if (informacion.respuesta == "BIENNuevaActividad" || informacion.respuesta == "BIENCargarExcel") {
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				messageClass = "la-message-success";
			}
			if (informacion.respuesta == "ERROR") {
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				messageClass = "la-message-error";
			}
			if (informacion.respuesta == "EXISTE") {
				texto = "<strong>Información!</strong> La actividad que estás intentando registrar ya existe.";
				messageClass = "la-message-error";
			}
			if (informacion.respuesta == "VACIO") {
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				messageClass = "la-message-error";
			}
			if (informacion.respuesta == "NO_ELIMINAR") {
				texto = "<strong>Advertencia!</strong> No se puede eliminar esta actividad.";
				messageClass = "la-message-error";
			}
			if (informacion.respuesta == "BIENNuevaActividad") {
				//$("#cuadro2").slideUp("slow");
				//$("#cuadro1").slideDown("slow");
				//$("#cuadro3").slideDown("slow");
				$("#modalNuevaActividad").modal("hide");
				showListadoMessage("#mensajeActualizacion", texto, messageClass, 10000);
			} else if (informacion.respuesta == "BIENCargarExcel") {
				//$("#cuadro2").slideUp("slow");
				//$("#cuadro1").slideDown("slow");
				//$("#cuadro3").slideDown("slow");
				$("#modalCargarExcel").modal("hide");
				showListadoMessage("#mensajeActualizacion", texto, messageClass, 10000);
			} else if (informacion.respuesta == "NO_ELIMINAR") {
				showListadoMessage("#mensajeActualizacion", texto, messageClass, 10000);
			} else {
				showListadoMessage(".mensaje", texto, messageClass, 5000);
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
			$("#modalNuevaActividad #tipoContratoPillsContainer").html(buildTipoContratoPills('modal', [], false));
			$("#modalNuevaActividad #tipoContratoHint").html('Puedes combinar varias modalidades. Si eliges <strong>Suministro e Instalación</strong>, las demás se bloquean porque ya las incluye.');
			$("#modalNuevaActividad .mensaje").html("");
			$("#modalNuevaActividad #actividad").focus();
		}

		var limpiar_datos_nueva_sem = function() {
			$("#opcion").val("registrar");
			$("#inicio_sem").val("");
		}

		var recargarTabla = function(opcion) {
		  if (window.ListadoActividadesHotModule && typeof window.ListadoActividadesHotModule.loadData === 'function') {
		    window.ListadoActividadesHotModule.loadData();
		  } else if (opcion == "listar") {
		    listar();
		  }
		}

	</script>

	<!-- Handsontable 14.6.1 (vendored) -->
	<script type="text/javascript" src="/vendor/handsontable/handsontable.full.min.js" charset="utf-8"></script>
	<script type="text/javascript" src="/vendor/handsontable/es-MX.js?v=14.6.1" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/info_general_nav.js?v=20260708b" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/listado_actividades/hot.js?v=20260712listadoAudit3" charset="utf-8"></script>
	<script>
		if (window.applyListadoActividadesContextFallback) {
			window.applyListadoActividadesContextFallback();
		}
		if (typeof bootstrapListadoActividades === 'function') {
			bootstrapListadoActividades();
		} else if (typeof listar === 'function' && !document.querySelector('.toolbarFilaBotones .la-toolbar-actions')) {
			listar();
		} else if (window.ListadoActividadesHotModule) {
			window.ListadoActividadesHotModule.init();
		}
	</script>
</body>
</html>
