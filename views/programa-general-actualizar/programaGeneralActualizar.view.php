<!DOCTYPE html>
<html lang="es">
<head id="head">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260325a" charset="utf-8"></script>

	<!-- Estilos Core Hot -->
	<link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
	<link rel="stylesheet" href="/css/handsontable-module.css?v=20260522" />

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
		.pg-page #hot-container td.pg-cell-editable {
			box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.06);
			cursor: text;
		}

		.pg-page #hot-container td.pg-cell-readonly {
			box-shadow: inset 0 0 0 9999px rgba(148, 163, 184, 0.08);
			cursor: not-allowed;
		}

		/* Botones Filtro UI */
		.pg-actions-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
		
		@media (max-width: 991px) { .hot-full-bleed { height: calc(100vh - 250px); } }
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
	$semana = $_SESSION["semana"] ?? 0;
	$dbPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $dbPrefix);

	// Buscar actividades en la semana anterior
	$semanaDropdown = max(1, (int)$semana - 1);
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
					"subtitle" => "Inicia: " . $row['Fecha_Inicio']
			];
	}
	$opcionesDropdownJSON = json_encode($actividadesPrevias, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
	?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" id="baseDatos" name="baseDatos" value="<?php echo $dbPrefix; ?>" aria-hidden="true">
		<input type="hidden" id="semana" name="semana" value="<?php echo $semana; ?>" aria-hidden="true">
		<input type="hidden" id="permiso" name="permiso" value="<?php echo $permiso; ?>" aria-hidden="true">
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

		<!-- Modal de Éxito - Marca AIA (Línea Construcción) -->
		<div class="modal fade" id="modalImportacionExitosa" role="dialog" data-backdrop="static">
		  <div class="modal-dialog modal-dialog-centered">
		    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
		      <div class="modal-body text-center" style="padding: 40px 20px;">
		        <div style="width: 80px; height: 80px; background: #fbead9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
		          <i class="fas fa-check" style="color: #b55211; font-size: 40px;"></i>
		        </div>
		        <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #1a3c2a; margin-bottom: 10px;">¡Carga Exitosa!</h3>
		        <p style="font-family: 'Inter', sans-serif; color: #4a4a4d; font-size: 16px; margin-bottom: 25px;">
		          El cronograma y la primera semana han sido creados correctamente. <br>
		          Hemos preparado todo para que inicies tu seguimiento.
		        </p>
		        <button type="button" class="btn btn-lg" id="btnIrAlPrograma" style="background: #b55211; color: white; border-radius: 8px; padding: 12px 30px; font-weight: 600; border: none; transition: background 0.3s;">
		          Ir al Programa General
		        </button>
		      </div>
		    </div>
		  </div>
		</div>


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
	
	<!--Selector de fechas -->
	<script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>

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
	<script src="/public/js/modules/programa_actualizar/hot_actualizar.js?v=202603242"></script>

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
				var semana = document.getElementById('semana').value;
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

							if (typeof cambiarSemanaSesion === 'function') {
								cambiarSemanaSesion(semana_json, location.pathname);
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
				var semana = document.getElementById('semana').value;
		    var variables = new FormData($("#formEliminarActualizacion")[0]);
		    
		    $.ajax({
		      type: "POST",
		      url: "/api/general/delete-update?db="+db+"&semana="+(Number(semana)+1),
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
		});
	</script>
</body>
</html>
