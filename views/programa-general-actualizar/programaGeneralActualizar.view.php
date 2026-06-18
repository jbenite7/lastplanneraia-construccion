<!DOCTYPE html>
<html lang="es">
<head id="head">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColorsFresh" charset="utf-8"></script>

	<!-- Estilos Core Hot -->
	<link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
	<link rel="stylesheet" href="/css/handsontable-module.css?v=20260529a" />

	<!-- Google Fonts: Montserrat & Inter -->
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

	<!-- TomSelect CSS -->
	<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
	<link rel="stylesheet" href="/css/tom-select-premium-aia.css?v=20260314" />
	
	<!-- Custom CSS for 2026 Guidelines -->
	<style>
		/* Estilos Core 2026 para Handsontable Full Bleed */
		body.pg-page { padding: 0; margin: 0; overflow: hidden !important; background: #f8fafc; }
		.hot-full-bleed { display: flex; flex-direction: column; height: calc(100vh - 80px); --hot-gutter: 8px; width: 100%; max-width: 100%; margin: 0; padding-left: var(--hot-gutter); padding-right: var(--hot-gutter); box-sizing: border-box; overflow: hidden; }
		#hot-container { flex: 1 1 auto; min-height: 0; min-width: 0; position: relative; width: 100% !important; overflow: hidden; z-index: 1; }
		/* Utilidades para celdas Handsontable */
		.pg-page #hot-container td.force-wrap, .pg-page #hot-container th.force-wrap { white-space: pre-wrap !important; word-break: normal !important; overflow-wrap: break-word !important; hyphens: none !important; }
		/* Changetypes UI */
		.pg-page #hot-container .handsontable thead th { position: relative !important; text-align: center !important; }
		.pg-page #hot-container .handsontable thead th .relative { display: flex; flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 2px; width: 100%; padding: 0 1px; box-sizing: border-box; }
		.pg-page #hot-container .handsontable thead th .relative > .colHeader { order: 1; width: 100%; }
		.pg-page #hot-container .handsontable thead th .relative > .changeType { order: 2; align-self: flex-end; margin: 0 !important; margin-top: 1px !important; }
		.pg-page #hot-container .handsontable thead th .colHeader { display: block; padding: 0 !important; line-height: 1.15; white-space: normal; overflow: visible; text-overflow: clip; word-break: break-word; text-align: center !important; }
		.pg-page #hot-container .handsontable thead th .changeType { float: none !important; position: static !important; transform: none; width: 13px; height: 13px; border: 1px solid #cfd8e3; border-radius: 4px; background: #f4f7fb; color: #5c6b7a; display: inline-flex; align-items: center; justify-content: center; z-index: 2; font-size: 9px; }
		.pg-page #hot-container .handsontable .changeType:before { content: "\f0b0"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
		.pg-page #hot-container .handsontable thead th .changeType:hover { border-color: #7ea7d8; background: #eaf3ff; color: #1e5ea8; cursor: pointer; }
		
		/* Overrides de Z-index */
		.pg-page .htDropdownMenu:not(.htGhostTable), .pg-page .htFiltersConditionsMenu:not(.htGhostTable) { z-index: 1085; }

		/* Celdas Handsontable AIA 2026 */
		.pg-page #hot-container td.pg-cell-editable:not(.pg-state-atrasado):not(.pg-state-atrasada):not(.pg-state-restr-0):not(.pg-state-debe-iniciar):not(.pg-state-actividad-futura):not(.pg-state-en-curso):not(.pg-state-a-tiempo-en-curso):not(.pg-state-terminada):not(.pg-state-sin-datos) {
			box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.06);
			cursor: text;
		}

		.pg-page #hot-container td.pg-cell-editable.pg-state-atrasado,
		.pg-page #hot-container td.pg-cell-editable.pg-state-atrasada,
		.pg-page #hot-container td.pg-cell-editable.pg-state-restr-0,
		.pg-page #hot-container td.pg-cell-editable.pg-state-debe-iniciar,
		.pg-page #hot-container td.pg-cell-editable.pg-state-actividad-futura,
		.pg-page #hot-container td.pg-cell-editable.pg-state-en-curso,
		.pg-page #hot-container td.pg-cell-editable.pg-state-a-tiempo-en-curso,
		.pg-page #hot-container td.pg-cell-editable.pg-state-terminada,
		.pg-page #hot-container td.pg-cell-editable.pg-state-sin-datos {
			cursor: text;
		}

		.pg-page #hot-container td.pg-cell-readonly:not(.pg-state-atrasado):not(.pg-state-atrasada):not(.pg-state-restr-0):not(.pg-state-debe-iniciar):not(.pg-state-actividad-futura):not(.pg-state-en-curso):not(.pg-state-a-tiempo-en-curso):not(.pg-state-terminada):not(.pg-state-sin-datos) {
			box-shadow: inset 0 0 0 9999px rgba(148, 163, 184, 0.08);
			cursor: not-allowed;
		}

		.pg-page #hot-container td.pg-cell-readonly.pg-state-atrasado,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-atrasada,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-restr-0,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-debe-iniciar,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-actividad-futura,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-en-curso,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-a-tiempo-en-curso,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-terminada,
		.pg-page #hot-container td.pg-cell-readonly.pg-state-sin-datos {
			cursor: not-allowed;
		}

		/* Botones Filtro UI */
		.pg-actions-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
		
		@media (max-width: 991px) { .hot-full-bleed { height: calc(100vh - 250px); } }

		/* Matching confidence tiers for Cronograma Actualizar */
		.pg-page #hot-container td.pg-match-auto {
			background: rgba(26, 86, 51, 0.12) !important;
		}
		.pg-page #hot-container td.pg-match-auto::after {
			content: " ✓";
			color: #1a5633;
			margin-left: 4px;
			pointer-events: none;
		}

		.pg-page #hot-container td.pg-match-review {
			background: rgba(255, 193, 7, 0.15) !important;
		}
		.pg-page #hot-container td.pg-match-review::after {
			content: " ⚠";
			color: #b8860b;
			margin-left: 4px;
			pointer-events: none;
		}

		.pg-page #hot-container td.pg-match-new {
			background: rgba(108, 117, 125, 0.12) !important;
		}
		.pg-page #hot-container td.pg-match-new::after {
			content: " NUEVA";
			color: #6c757d;
			font-weight: bold;
			margin-left: 4px;
			pointer-events: none;
		}

		/* ========================================
		   Modal Auto-Asociación — Estilos de revisión
		   ======================================== */
		.match-stats {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: var(--spacing-md, 1rem);
			margin-bottom: var(--spacing-lg, 1.5rem);
		}
		.match-stats .match-stat-card {
			background: var(--aia-bg-alabaster, #fafafa);
			border: 1px solid var(--aia-separators, #d1d1d1);
			border-radius: var(--radius-md, 0.5rem);
			padding: var(--spacing-md, 1rem);
			text-align: center;
		}
		.match-stats .match-stat-card .match-stat-value {
			font-family: 'Montserrat', sans-serif;
			font-size: 1.75rem;
			font-weight: 700;
			color: var(--aia-green-primary, #1a5633);
			line-height: 1.2;
		}
		.match-stats .match-stat-card .match-stat-label {
			font-family: 'Inter', sans-serif;
			font-size: 0.75rem;
			color: var(--aia-text-secondary, #4a4a4d);
			text-transform: uppercase;
			letter-spacing: 0.05em;
			margin-top: var(--spacing-xs, 0.25rem);
		}
		.match-stats .match-stat-card.match-stat-none .match-stat-value {
			color: var(--aia-text-tertiary, #a9a9a9);
		}

		#review-list {
			max-height: 400px;
			overflow-y: auto;
			padding-right: var(--spacing-sm, 0.5rem);
		}
		#review-list::-webkit-scrollbar {
			width: 6px;
		}
		#review-list::-webkit-scrollbar-thumb {
			background: var(--aia-separators, #d1d1d1);
			border-radius: 3px;
		}

		.match-item {
			background: var(--aia-bg-alabaster, #fafafa);
			border: 1px solid var(--aia-separators, #d1d1d1);
			border-radius: var(--radius-md, 0.5rem);
			padding: var(--spacing-md, 1rem);
			margin-bottom: var(--spacing-md, 1rem);
		}
		.match-item:last-child {
			margin-bottom: 0;
		}
		.match-item .match-activity-name {
			font-family: 'Montserrat', sans-serif;
			font-weight: 600;
			font-size: 0.95rem;
			color: var(--aia-text-primary, #1c1c1e);
			margin-bottom: var(--spacing-sm, 0.5rem);
			padding-bottom: var(--spacing-sm, 0.5rem);
			border-bottom: 1px solid var(--aia-separators, #d1d1d1);
		}

		.match-candidate {
			display: flex;
			align-items: center;
			gap: var(--spacing-md, 1rem);
			padding: var(--spacing-sm, 0.5rem) 0;
		}
		.match-candidate + .match-candidate {
			border-top: 1px solid rgba(0, 0, 0, 0.05);
		}
		.match-candidate .match-candidate-name {
			flex: 1;
			font-family: 'Inter', sans-serif;
			font-size: 0.875rem;
			color: var(--aia-text-secondary, #4a4a4d);
			min-width: 0;
		}
		.match-candidate .match-candidate-bar-wrap {
			flex: 0 0 120px;
			display: flex;
			align-items: center;
			gap: var(--spacing-sm, 0.5rem);
		}
		.match-candidate .match-candidate-bar {
			flex: 1;
			height: 8px;
			background: var(--aia-bg-gray-light, #eaeaea);
			border-radius: 4px;
			overflow: hidden;
		}
		.match-candidate .match-candidate-bar-fill {
			height: 100%;
			border-radius: 4px;
			transition: width var(--transition-normal, 0.3s ease-in-out);
		}
		.match-candidate .match-candidate-pct {
			font-family: 'Inter', sans-serif;
			font-size: 0.75rem;
			font-weight: 600;
			min-width: 36px;
			text-align: right;
		}
		.match-candidate .match-candidate-actions {
			display: flex;
			gap: var(--spacing-xs, 0.25rem);
			flex-shrink: 0;
		}

		/* Confidence tier colors */
		.match-confidence-high .match-candidate-bar-fill {
			background: var(--aia-green-primary, #1a5633);
		}
		.match-confidence-high .match-candidate-pct {
			color: var(--aia-green-primary, #1a5633);
		}
		.match-confidence-medium .match-candidate-bar-fill {
			background: #b8860b;
		}
		.match-confidence-medium .match-candidate-pct {
			color: #b8860b;
		}
		.match-confidence-none .match-candidate-bar-fill {
			background: #6c757d;
		}
		.match-confidence-none .match-candidate-pct {
			color: #6c757d;
		}
	</style>
	<link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260313" />
	<link rel="stylesheet" href="/css/tom-select-premium-aia.css?v=20260314" />
</head>

<body class="pg-page">
	<div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

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
	$query = "SELECT Id, Actividad, Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Consecutivo ASC";
	$stmt = $dbInstance->query($query, [$semanaDropdown]);
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

	<div class="hot-full-bleed">
		<div class="row direccionSeccion" style="margin:0;">
			<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
		</div>

		<div class="header-actions" style="margin-bottom: 8px;">
			<div class="pg-actions-row">
				<div class="d-flex flex-wrap align-items-center" style="gap:6px;">
					<button id="btn_cargarCronogramaExcel" type="button" class="btn btn-success btn-sm" title="Cargar actualización del cronograma desde Excel" data-toggle="modal" data-target="#modalCargarExcel" aria-label="Cargar cronograma desde Excel">Cargar desde Excel <i class="fas fa-upload fa-lg" aria-hidden="true"></i></button>
					<button id="btn_eliminarActualizacion" type="button" class="btn btn-danger btn-sm" title="Eliminar actualización del cronograma" data-toggle="modal" data-target="#modalEliminarActualizacion" aria-label="Eliminar actualización">Eliminar Actualización <i class="far fa-trash-alt fa-lg" aria-hidden="true"></i></button>
					<button id="btn_toggleFiltroMapeo" type="button" class="btn btn-outline-primary btn-sm active" title="Alternar visualización de actividades" aria-label="Alternar visualización">Mostrando Pendientes <i class="fas fa-filter fa-lg"></i></button>
					<button id="btn_autoAsociar" type="button" class="btn btn-info btn-sm" title="Asociar automáticamente"><i class="fas fa-magic fa-lg" aria-hidden="true"></i> Auto-Asociar</button>
				</div>
				<div class="pg-status-badges">
					<span id="save-status" class="badge badge-success badge-badge-hidden">Auto-Guardado</span>
				</div>
			</div>
		</div>

		<!-- Contenedor Handsontable -->
		<div id="hot-container"></div>
		<div id="mobile-card-view" style="display:none;"></div>
		
		<!-- Data para Dropdowns (Hidden context) -->
		<script id="historicoData" type="application/json"><?php echo $opcionesDropdownJSON; ?></script>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- General el modal para descargar y cargar el CSV con el que se puede agregar el listado de actividades desde excel -->
		<div class="modal_cargarExcel modal fade aia-modal" id="modalCargarExcel" role="dialog" aria-labelledby="modal_cargarExcelLabel">
		  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
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
		                </div>
		              </div>

		              <!-- Campo de Fecha de Inicio para Proyectos Nuevos -->
		              <div id="container_f_inicio_importar" class="form-group parametro_cargarExcel" style="padding: 1px 5px 15px 5px; margin-bottom: 10px; display: none;">
		                <div class="col-sm-12">
		                  <label for="f_inicio_importar" class="control-label"><b>Fecha de Inicio de la Primera Semana:</b></label>
		                  <input type="text" name="f_inicio_importar" id="f_inicio_importar" class="form-control" readonly style="background: white; cursor: pointer;" placeholder="YYYY-MM-DD">
		                  <small class="text-muted">Como este es un proyecto nuevo, por favor define cuándo inicia la primera semana.</small>
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
		<div class="modal_eliminarActualizacion modal fade aia-modal" id="modalEliminarActualizacion" role="dialog" aria-labelledby="modal_eliminarActualizacionLabel">
		  <div class="modal-dialog modal-m modal-dialog-centered" role="document">
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

		<!-- Modal de Éxito - Marca AIA corporativa -->
		<div class="modal fade aia-modal" id="modalImportacionExitosa" role="dialog" data-backdrop="static">
		  <div class="modal-dialog modal-dialog-centered">
		    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
		      <div class="modal-body text-center" style="padding: 40px 20px;">
		        <div style="width: 80px; height: 80px; background: #d5e5db; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
		          <i class="fas fa-check" style="color: #1a5633; font-size: 40px;"></i>
		        </div>
		        <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #1a3c2a; margin-bottom: 10px;">¡Carga Exitosa!</h3>
		        <p style="font-family: 'Inter', sans-serif; color: #4a4a4d; font-size: 16px; margin-bottom: 25px;">
		          El cronograma y la primera semana han sido creados correctamente. <br>
		          Hemos preparado todo para que inicies tu seguimiento.
		        </p>
		        <button type="button" class="btn btn-lg aia-btn-primary" id="btnIrAlPrograma">
		          Ir al Programa General
		        </button>
		      </div>
		    </div>
		  </div>
		</div>


		<!-- Se crea el Modal que explica el significado de la columna 'Ejecutado Teórico' -->
		<div class='modal fade aia-modal' id='modal_Ejecutado_Teorico' role='dialog' data-backdrop='static'>
		  <div class='modal-dialog modal-lg modal-dialog-centered'>
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
		<div class="modal fade aia-modal" id="modal_cantidad_ejecutada_error" role="dialog">
			<div class="modal-dialog modal-lg modal-dialog-centered">
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
			<div class="modal fade aia-modal" id="modal_semanal_confirmada" role="dialog">
				<div class="modal-dialog modal-lg modal-dialog-centered">
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

		<!-- Modal de Revisión de Auto-Asociación -->
		<div class="modal fade aia-modal" id="modalAutoAsociar" role="dialog" aria-labelledby="modalAutoAsociarLabel" data-backdrop="static">
			<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header" style="background: #1a5633; color: white;">
						<div class="modal-title" id="modalAutoAsociarLabel">
							<div class="aia-modal__eyebrow" style="color: rgba(255,255,255,0.7);">AIA Corporativo</div>
							<h5 style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 600; color: white;">Resultados de Auto-Asociación</h5>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body" style="background: #F4F1EA;">
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

						<!-- Lista de ítems para revisar (media confianza) -->
						<div id="review-list"></div>
					</div>
					<div class="modal-footer" style="background: #FAFAFA;">
						<button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Cerrar revisión">Cerrar</button>
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
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

	<!-- TomSelect Dropdown Core UI -->
	<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

	<!-- Handsontable Core -->
	<script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
	<script src="/public/vendor/handsontable/es-MX.js"></script>
	
	<!-- Handsontable Plugin para TomSelect (Requiere Hot y TomSelect cargados) -->
	<script src="/js/HandsontableTomSelectEditor.js?v=tomselect30"></script>
	
	<!-- Módulos de Handsontable -->
	<script src="/public/js/modules/programa_actualizar/hot_actualizar.js?v=20260618a"></script>

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
