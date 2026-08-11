<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
	<?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <title>Actualizar Programa General — Last Planner AIA</title>
	<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::renderForModule('programa-general-actualizar') ?>
	<link rel="stylesheet" href="/css/programa-general-actualizar.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/programa-general-actualizar.css') ?: 'pga1')) ?>" />
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>

	<!-- Estilos Core Hot: el vendor de Handsontable llega vía attach-handsontable.css en
	     layer(vendor); un link crudo aquí queda sin capa y pisa el tema oscuro del adapter. -->
	<!-- handsontable-module.css llega vía aia-design-system.css (layer vendor); el link crudo duplicaba la cascada. -->

	<!-- Google Fonts: Montserrat & Inter -->
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

	<!-- TomSelect CSS -->
	<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
	<link rel="stylesheet" href="/css/tom-select-premium-aia.css?v=20260314" />

	<link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260313" />
	<script>
		window.getCsrfToken = window.getCsrfToken || function() {
			var meta = document.querySelector('meta[name="csrf-token"]');
			return meta && meta.content ? meta.content : '';
		};

		if (window.jQuery) {
			window.jQuery(document).ajaxSend(function (_event, xhr, settings) {
				var url = settings && settings.url ? String(settings.url) : '';
				if (url.indexOf('/api/general/') === 0) {
					xhr.setRequestHeader('X-CSRF-Token', window.getCsrfToken());
				}
			});
		}
	</script>
</head>

<body class="aia-shell aia-shell--sidebar pg-page">
	<div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

	<?php
    // PRE-CARGA JSON: Opciones del cronograma anterior
    $dbInstance = Database::getInstance();
	$dbPrefix = $_SESSION["db"] ?? '';
	$semanaBaseActualizacion = (int) ($semanaBaseActualizacion ?? ($_SESSION["semana"] ?? 0));
	$semanaObjetivoActualizacion = (int) ($semanaObjetivoActualizacion ?? ($semanaBaseActualizacion + 1));
	$semana = $semanaBaseActualizacion;
	$dbPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $dbPrefix);

	// Buscar actividades del cronograma activo actual para mapearlas contra el borrador actualizado.
	$semanaDropdown = max(1, $semanaBaseActualizacion);
	$tableName = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');
	$projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
		$query = "SELECT Id, Actividad, Fecha_Inicio FROM {$tableName} WHERE project_id = ? AND Semana = ? AND Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Consecutivo ASC";
		$stmt = $dbInstance->queryWithProject($query, [$projectId, $semanaDropdown], $projectId);
	$actividadesPrevias = [];
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	    // Formato TomSelect requerido: id, title, label
	    $cleanName = strip_tags($row['Actividad']);
	    $title = $row['Id'] . ". " . $cleanName;
	    $actividadesPrevias[] = [
	        "id" => $cleanName, // Guardamos el nombre limpio para evitar etiquetas en DB
	        "title" => $title,
	        "subtitle" => "Inicia: " . $row['Fecha_Inicio'],
	    ];
	}
	$opcionesDropdownJSON = json_encode($actividadesPrevias, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
	?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" id="baseDatos" name="baseDatos" value="<?php echo $dbPrefix; ?>" aria-hidden="true">
		<input type="hidden" id="semana" name="semana" value="<?php echo $semana; ?>" aria-hidden="true">
		<input type="hidden" id="semanaBaseActualizacion" name="semanaBaseActualizacion" value="<?php echo $semanaBaseActualizacion; ?>" aria-hidden="true">
		<input type="hidden" id="semanaObjetivoActualizacion" name="semanaObjetivoActualizacion" value="<?php echo $semanaObjetivoActualizacion; ?>" aria-hidden="true">
		<input type="hidden" id="permiso_canonico" name="permiso_canonico" value="<?php echo $permiso; ?>" aria-hidden="true">
		<input type="hidden" id="Max_Semana" name="Max_Semana" value="<?php echo $maxSemana; ?>" aria-hidden="true">
		<input type="hidden" id="Semanal_Confirmada" name="Semanal_Confirmada" value="<?php echo $semanalConfirmada; ?>" aria-hidden="true">
		<input type="hidden" name="seccion" id="seccion" value="actualizarCronograma" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">
	</div>

	<main class="hot-full-bleed">
	<h1 class="aia-visually-hidden">Actualizar Programa General</h1>
		<div class="row direccionSeccion">
			<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
		</div>

		<div class="header-actions action-bar">
			<div class="pg-actions-row">
				<!-- pg-toolbar-buttons activa el piso compacto de 24px del componente
				     compartido (design-system/components/toolbar-controls.css). -->
				<div class="aia-action-group pg-toolbar-buttons" role="group" aria-label="Acciones de actualización">
					<button id="btn_cargarCronogramaExcel" type="button" class="aia-btn aia-btn-primary" title="Cargar actualización del cronograma desde Excel" data-toggle="modal" data-target="#modalCargarExcel" aria-label="Cargar cronograma desde Excel">Cargar desde Excel <i class="fas fa-upload fa-lg" aria-hidden="true"></i></button>
					<button id="btn_eliminarActualizacion" type="button" class="aia-btn aia-btn-ghost" title="Eliminar actualización del cronograma" data-toggle="modal" data-target="#modalEliminarActualizacion" aria-label="Eliminar actualización">Eliminar Actualización <i class="far fa-trash-alt fa-lg" aria-hidden="true"></i></button>
					<button id="btn_toggleFiltroMapeo" type="button" class="aia-btn aia-btn-ghost" title="Alternar visualización de actividades" aria-label="Alternar visualización">Ver Programa Completo <i class="fas fa-filter fa-lg"></i></button>
					<button id="btn_autoAsociar" type="button" class="aia-btn aia-btn-ghost" title="Asociar automáticamente"><i class="fas fa-magic" aria-hidden="true"></i> Auto-Asociar</button>
				</div>
				<div class="pg-status-badges">
					<span id="save-status" class="aia-chip badge-badge-hidden" data-aia-severity="success">Auto-Guardado</span>
				</div>
			</div>
		</div>

		<!-- Contenedor Handsontable -->
		<div id="hot-container"></div>
		<div id="mobile-card-view" hidden></div>

		<!-- Data para Dropdowns (Hidden context) -->
		<script id="historicoData" type="application/json"><?php echo $opcionesDropdownJSON; ?></script>
	</main>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- General el modal para descargar y cargar el CSV con el que se puede agregar el listado de actividades desde excel -->
		<div class="modal_cargarExcel modal fade aia-modal" id="modalCargarExcel" tabindex="-1" role="dialog" aria-labelledby="modalCargarExcelLabel">
		  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalCargarExcelLabel">
		          <div class="aia-modal__eyebrow">AIA Corporativo</div>
		          <h5 class="aia-modal__title">Cargar Cronograma desde Excel</h5>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form enctype="multipart/form-data" class="form form-horizontal" id="formCargarExcel" name="formCargarExcel" action="" method="POST">
		              <div class="form-group parametro_cargarExcel">

		                <label for="descargarArchivoBase" class="control-label">En el siguiente enlace puede descargar el archivo base para cargar una actualización del cronograma desde Excel:</label>
		                <a id="descargarArchivoBase" class="descargarArchivoBase aia-btn aia-btn-secondary" download="actualizacionCronogramaLPS.xlsx" href="/archivosBase/actualizacionCronogramaLPS.xlsx">Descargar Archivo Base</a>
		              </div>
		              <div class="form-group parametro_cargarExcel">
		                <div class="form_eval form-group">
		                  <h3 id='form_general'> Cargar Cronograma en Excel </h3>
		                </div>
		                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es cargarExcel-->
		                <input type="hidden" id="modalCargarExcelId" name="Id" value="">
		                <input type="hidden" id="modalCargarExcelOpcion" name="opcion" value="cargarExcel">
		                <input type="hidden" id="codigo" name="codigo" value="">
		                <!-- Se crea el input para cargar el archivo CSV que cargarà el listado de actividades del proyecto -->
		                <div class="col-sm-12">
		                  <label for="archivoExcel" class="control-label">Seleccione el archivo con el cronograma completo desde el equipo (Solo se permiten archivos en formato XLSX):</label>
		                  <input type="file" name="archivoExcel" id="archivoExcel" class="aia-input" accept=".xlsx">
		                </div>
		              </div>

		              <!-- Campo de Fecha de Inicio para Proyectos Nuevos -->
		              <div id="container_f_inicio_importar" class="form-group parametro_cargarExcel" hidden>
		                <div class="col-sm-12">
		                  <label for="f_inicio_importar" class="control-label"><b>Fecha de Inicio de la Primera Semana:</b></label>
		                  <input type="text" name="f_inicio_importar" id="f_inicio_importar" class="aia-input pga-datepicker-input" readonly placeholder="YYYY-MM-DD">
		                  <small class="text-muted">Como este es un proyecto nuevo, por favor define cuándo inicia la primera semana.</small>
		                </div>
		              </div>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input type="submit" class="aia-btn aia-btn-primary" value="Guardar" aria-label="Guardar carga de Excel">
		                  <input id="btn_listar" type="button" class="aia-btn aia-btn-ghost" value="Cancelar" data-dismiss="modal" aria-label="Cancelar carga">
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
		<div class="modal_eliminarActualizacion modal fade aia-modal" id="modalEliminarActualizacion" tabindex="-1" role="dialog" aria-labelledby="modalEliminarActualizacionLabel">
		  <div class="modal-dialog modal-m modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalEliminarActualizacionLabel">
		          <div class="aia-modal__eyebrow">AIA Corporativo</div>
		          <h5 class="aia-modal__title">Eliminar Actualizacion del Cronograma</h5>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4_eliminarActualizacion" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form enctype="multipart/form-data" class="form form-horizontal" id="formEliminarActualizacion" name="formEliminarActualizacion" action="" method="POST">
		              <div class="form-group parametro_cargarExcel">
										<p class='modal-eliminar-semana-body-texto' id='modal-eliminar-semana-body-texto'>¿Desea eliminar esta actualización del cronograma del proyecto?</p>
		              </div>
		              <div class="form-group">
		                <div class="col-sm-12">
		                  <input type="submit" class="aia-btn aia-btn-primary" value="Aceptar" aria-label="Aceptar eliminar actualización">
		                  <input id="btn_listar_eliminarActualizacion" type="button" class="aia-btn aia-btn-ghost" value="Cancelar" data-dismiss="modal" aria-label="Cancelar eliminar">
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

		<!-- Modal de Éxito - Marca AIA corporativa -->
		<div class="modal fade aia-modal" id="modalImportacionExitosa" tabindex="-1" role="dialog" data-backdrop="static">
		  <div class="modal-dialog modal-dialog-centered">
		    <div class="modal-content">
		      <div class="modal-body text-center pga-success-body">
		        <div class="pga-success-badge">
		          <i class="fas fa-check"></i>
		        </div>
		        <h3 class="pga-success-title">¡Carga Exitosa!</h3>
		        <p class="pga-success-copy">
		          El cronograma y la primera semana han sido creados correctamente. <br>
		          Hemos preparado todo para que inicies tu seguimiento.
		        </p>
		        <button type="button" class="aia-btn aia-btn-primary aia-btn--lg" id="btnIrAlPrograma">
		          Ir al Programa General
		        </button>
		      </div>
		    </div>
		  </div>
		</div>


		<!-- Se crea el Modal que explica el significado de la columna 'Ejecutado Teórico' -->
		<div class='modal fade aia-modal' id='modal_Ejecutado_Teorico' tabindex='-1' role='dialog' aria-labelledby='modal_Ejecutado_Teorico_Label' data-backdrop='static'>
		  <div class='modal-dialog modal-lg modal-dialog-centered'>
		    <!-- Modal content-->
		    <div class='modal-content'>
		      <div class='modal-header'>
		        <div class="modal-title" id='modal_Ejecutado_Teorico_Label'>
		          <div class="aia-modal__eyebrow">AIA Corporativo</div>
		          <h5 class="aia-modal__title">Ejecutado Teórico</h5>
		        </div>
		        <button type='button' class='close' data-dismiss='modal'>&times;</button>
		      </div>
		      <div class='modal-body'>
		        <ul class='pga-formula-list'>
		          <p>Requerimiento lineal (en cantidad) del tiempo transcurrido de la actividad sobre la duración total de la misma.</p>
		          <div><img src='/img/formula_ejecutado_teorico.png' class='pga-formula-img d-inline-block align-top' alt=''></div>
		        </ul>
		      </div>
		      <div class='modal-footer'><button type='button' class='aia-btn aia-btn-ghost' data-dismiss='modal' >Cerrar</button></div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que avisa que la cantidad que se está comprometiendo en una actividad, es inferior a la cantidad sugerida por el programa -->
		<div class="modal fade aia-modal" id="modal_cantidad_ejecutada_error" tabindex="-1" role="dialog" aria-labelledby="modal_cantidad_ejecutada_error_Label">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modal_cantidad_ejecutada_error_Label">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h5 class="aia-modal__title">Cantidad Ejecutada Mayor</h5>
						</div>
						<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body pga-alert-body">
						<i class="fas fa-exclamation-circle fa-5x pga-alert-icon" aria-hidden="true"></i>
						<div class="texto_cantidad_ejecutada_error pga-alert-copy"></div>
					</div>
					<div class="modal-footer">

						<input id="btn_cantidad_ejecutada_error" type="button" data-dismiss="modal" class="aia-btn aia-btn-ghost aia-btn--lg" value="Cerrar" aria-label="Cerrar alerta">
					</div>
				</div>
			</div>
		</div>

			<!-- Se crea el Modal que avisa que la cantidad que se está comprometiendo en una actividad, es inferior a la cantidad sugerida por el programa -->
			<div class="modal fade aia-modal" id="modal_semanal_confirmada" tabindex="-1" role="dialog" aria-labelledby="modal_semanal_confirmada_Label">
				<div class="modal-dialog modal-lg modal-dialog-centered">
					<!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
						<div class="modal-title" id="modal_semanal_confirmada_Label">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h5 class="aia-modal__title">Programa General Bloqueado</h5>
						</div>
						<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
						<div class="modal-body iconoAlertaSemanalConfirmada pga-alert-body">
							<i class="fas fa-exclamation-circle fa-5x pga-alert-icon" aria-hidden="true"></i>
							<div class="texto_semanal_confirmada pga-alert-copy"></div>
						</div>
						<div class="modal-footer">

							<input id="btn_semanal_confirmada" type="button" data-dismiss="modal" class="aia-btn aia-btn-ghost aia-btn--lg" value="Cerrar" aria-label="Cerrar alerta confirmación">
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal de Revisión de Auto-Asociación (Opción C: split Pendientes/Procesadas + Guardar Cambios batch) -->
		<div class="modal fade aia-modal" id="modalAutoAsociar" tabindex="-1" role="dialog" aria-labelledby="modalAutoAsociarLabel" data-backdrop="static">
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalAutoAsociarLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h5 class="aia-modal__title">Resultados de Auto-Asociación</h5>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<!-- Resumen estadístico -->
						<div class="match-stats" id="match-stats">
							<div class="match-stat-card">
								<div class="match-stat-value" id="stat_identical">0</div>
								<div class="match-stat-label">Idénticas</div>
							</div>
							<div class="match-stat-card">
								<div class="match-stat-value" id="stat_high">0</div>
								<div class="match-stat-label">Alta confianza</div>
							</div>
							<div class="match-stat-card">
								<div class="match-stat-value" id="stat_medium">0</div>
								<div class="match-stat-label">Media confianza</div>
							</div>
							<div class="match-stat-card match-stat-none">
								<div class="match-stat-value" id="stat_none">0</div>
								<div class="match-stat-label">Sin coincidencia</div>
							</div>
						</div>

						<!-- Split view: tabs Pendientes / Procesadas -->
						<div id="review-sections">
							<ul class="nav nav-tabs review-tabs" id="reviewTabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link active" id="tab-pending" data-toggle="tab" href="#review-pending" role="tab" aria-controls="review-pending" aria-selected="true">
										<i class="fas fa-clock"></i> Pendientes
										<span class="aia-chip ml-1" data-aia-severity="warning" id="tab-pending-badge">0</span>
									</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="tab-processed" data-toggle="tab" href="#review-processed" role="tab" aria-controls="review-processed" aria-selected="false">
										<i class="fas fa-check-circle"></i> Procesadas
										<span class="aia-chip ml-1" data-aia-severity="success" id="tab-processed-badge">0</span>
									</a>
								</li>
							</ul>
							<div class="tab-content review-tab-content" id="reviewContent">
								<div class="tab-pane fade show active" id="review-pending" role="tabpanel" aria-labelledby="tab-pending">
									<div id="review-list-pending"></div>
								</div>
								<div class="tab-pane fade" id="review-processed" role="tabpanel" aria-labelledby="tab-processed">
									<div id="review-list-processed"></div>
								</div>
							</div>
						</div>

						<!-- Fallback single list (sin split) cuando no hay ítems -->
						<div id="review-list" hidden></div>
					</div>
					<div class="modal-footer">
						<div>
							<button type="button" class="aia-btn aia-btn-primary" id="btn-guardar-cambios" disabled>
								<i class="fas fa-save"></i> Guardar Cambios
								<span class="guardar-count ml-1"></span>
							</button>
						</div>
						<div>
							<button type="button" class="aia-btn aia-btn-ghost" data-dismiss="modal" aria-label="Cerrar revisión">Cerrar</button>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

	<!-- Iniciar Popper-->
	<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

	<!--Selector de fechas -->

	<!--Global AJAX Loaders-->
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/hot_table_width.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

	<!-- TomSelect Dropdown Core UI -->
	<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

	<!-- Handsontable Core -->
	<script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
	<script src="/public/vendor/handsontable/es-MX.js"></script>

	<!-- Handsontable Plugin para TomSelect (Requiere Hot y TomSelect cargados) -->
	<script src="/js/HandsontableTomSelectEditor.js?v=tomselect30"></script>

	<!-- Reglas Progresivas ML-Ready -->
	<script src="/public/js/modules/programa_actualizar/rule_engine.js?v=20260622"></script>
	<script src="/public/js/modules/programa_actualizar/decision_logger.js?v=20260622"></script>

	<!-- Módulos de Handsontable -->
	<script src="/public/js/core/SessionExpiredHandler.js?v=20260811a"></script>
	<script src="/public/js/modules/programa_actualizar/hot_actualizar.js?v=20260806b"></script>

	<script>
		/* Funciones Legacy requeridas a nivel global */
		var listar = function() {};
		var cargaParametros = function() {
			if (typeof guardarCargarExcel === 'function') { guardarCargarExcel(); }
			if (typeof guardarEliminarActualizacion === 'function') { guardarEliminarActualizacion(); }

			// Init global Hot Module si existe en el entorno
			if (window.HOTActualizarModule) {
				window.HOTActualizarModule.init();
			}
		};

		// Handlers de Importación que antes estaban integrados directamente
		var guardarCargarExcel = function() {
		  $("#modalCargarExcel form").off("submit").on("submit", function(e) {
		    e.preventDefault();

				// Acuse de recibo de la operacion mas larga de la app: sin esto, entre
				// pulsar «Guardar» y ver el resultado no cambia nada en pantalla, y un
				// segundo clic lanza una segunda importacion sobre el mismo cronograma.
				// Mismo patron que «Crear Semana LPS» (funcionesGenerales6.js:68-69).
				var $submit = $(this).find('input[type="submit"]');
				$submit.prop('disabled', true);
				$('#modal_spinner').modal('show');

				var db = document.getElementById('baseDatos').value;
				var semanaBaseInput = document.getElementById('semanaBaseActualizacion') || document.getElementById('semana');
				var semana = semanaBaseInput ? semanaBaseInput.value : document.getElementById('semana').value;
		    var variables = new FormData($("#formCargarExcel")[0]);
				var inputFecha = document.getElementById('f_inicio_importar');
				var f_inicio_sem = inputFecha ? (inputFecha.value) : '';

		    $.ajax({
		      type: "POST",
		      url: "/api/general/import?db="+db+"&semana="+semana+"&f_inicio_sem="+f_inicio_sem,
		      contentType: false,
		      processData: false,
		      data: variables,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      var semana_json = json_info[0];

		      if (json_info.respuesta == "BIEN") {
		        $("#modalCargarExcel").modal("hide");

		        if (semana_json == 1 && Number(semana) == 0) {
		          // Carga inicial
		          $("#modalImportacionExitosa").modal("show");
		          $("#btnIrAlPrograma").off("click").on("click", function() {
								if (typeof cambiarSemanaSesion === 'function') {
									cambiarSemanaSesion(semana_json, "/programa-general");
								} else {
									location.assign("/programa-general");
								}
		          });

		          setTimeout(function() {
		            if ($("#modalImportacionExitosa").is(":visible")) {
									if (typeof cambiarSemanaSesion === 'function') {
										cambiarSemanaSesion(semana_json, "/programa-general");
									} else {
										location.assign("/programa-general");
									}
		            }
		          }, 10000);
		        } else {
		        // Actualización de cronograma
		          if (window.AIA && window.AIA.Notice) {
		            window.AIA.Notice.badge('success', "¡Cronograma cargado con éxito en la Semana " + semana_json + "! Ahora puedes realizar el mapeo de actividades.");
		          }

							// Trigger auto-associate after page reload
							sessionStorage.setItem('autoAssociatePending', '1');

							if (typeof cambiarSemanaSesion === 'function') {
								var semanaBase = Number(json_info.semana_base || Math.max(0, Number(semana_json) - 1));
								cambiarSemanaSesion(semanaBase, location.pathname);
							} else {
								location.reload();
							}
		        }
		      } else {
		        if (json_info.respuesta == "ERROR") {
		          if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error: " + json_info.mensaje);
		        } else {
		          location.reload();
		        }
		      }
		    }).fail(function(xhr) {
				var mensaje = 'No se pudo importar el cronograma.';
				var respuesta = xhr.responseJSON;

				if (!respuesta && xhr.responseText) {
					try {
						respuesta = JSON.parse(xhr.responseText);
					} catch (error) {
						respuesta = null;
					}
				}

				if (respuesta && respuesta.mensaje) {
					mensaje += ' ' + respuesta.mensaje;
				}

				if (window.AIA && window.AIA.Notice) {
					window.AIA.Notice.error(mensaje);
				} else {
					alert(mensaje);
				}
		    }).always(function () {
		      // Pase lo que pase —exito, error de red o 500— el spinner se cierra y
		      // el boton vuelve. Sin este `always` un fallo deja la pantalla
		      // bloqueada para siempre y la unica salida es recargar.
		      $('#modal_spinner').modal('hide');
		      $submit.prop('disabled', false);
		    });
		  });

		  // Modal trigger Logic
		  $('#modalCargarExcel').off('show.bs.modal').on('show.bs.modal', function () {
				var semanaActual = parseInt(document.getElementById('semana').value);
				if (semanaActual === 0) {
					$('#container_f_inicio_importar').show();
					$("#f_inicio_importar").datepicker({
						dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, showOtherMonths: true, selectOtherMonths: true
					}).datepicker("setDate", new Date());
				} else {
					$('#container_f_inicio_importar').hide();
					$("#f_inicio_importar").val('');
				}
		  });
		}

		var guardarEliminarActualizacion = function() {
		  $("#modalEliminarActualizacion form").off("submit").on("submit", function(e) {
		    e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semanaBaseInput = document.getElementById('semanaBaseActualizacion') || document.getElementById('semana');
				var semanaObjetivoInput = document.getElementById('semanaObjetivoActualizacion');
				var semana = semanaBaseInput ? semanaBaseInput.value : document.getElementById('semana').value;
				var semanaObjetivo = semanaObjetivoInput ? semanaObjetivoInput.value : (Number(semana) + 1);
		    var variables = new FormData($("#formEliminarActualizacion")[0]);

		    $.ajax({
		      type: "POST",
		      url: "/api/general/delete-update?db="+db+"&semana_objetivo="+semanaObjetivo,
		      data: variables,
					processData: false,
					contentType: false,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      if (json_info.respuesta == "BIEN") {
						if (typeof cambiarSemanaSesion === 'function') {
							cambiarSemanaSesion(json_info.semana_activa, location.pathname);
						} else {
							location.reload();
						}
		      } else {
		        location.reload();
		      }
		    });
		  });
		}

		$(document).ready(function() {
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
			guardarCargarExcel();
			guardarEliminarActualizacion();

			// Fallback: inicializar Handsontable aunque el AJAX legacy falle.
			// cargaParametros() también lo llama; HOTActualizarModule.init() es idempotente.
			setTimeout(function() {
				if (window.HOTActualizarModule && !window.HOTActualizarModule._initialized) {
					console.warn("⚠️ [Fallback] cargaParametros no fue invocado por AJAX legacy. Inicializando Handsontable directamente.");
					window.HOTActualizarModule.init();
				}
			}, 3000);
		});
	</script>
</body>
</html>
