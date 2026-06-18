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
	$maxSemanaListadoActividades = (int) ($_SESSION['Max_Semana'] ?? ($_SESSION['semana'] ?? 0));
	$actividadInicioOptionsHtml = '';

	if (empty($dbPrefixListadoActividades) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefixListadoActividades)) {
	    $actividadInicioOptionsHtml = '<option value="">Error: Database prefix not set</option>';
	} else {
	    try {
	        $dbInstance = Database::getInstance();
	        $queryActividadInicio = "SELECT Consecutivo_en_Programa, Id, Actividad, Fecha_Inicio FROM {$dbPrefixListadoActividades}_programa_consolidado WHERE Semana=? AND Titulo=0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL ORDER BY Fecha_Inicio ASC";
	        $stmtActividadInicio = $dbInstance->query($queryActividadInicio, [$maxSemanaListadoActividades]);
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
		                <div class="col-sm-12 aia-modal__field">
		                  <label for="tipoContrato" class="control-label aia-modal__label">Tipo de Contrato</label>
		                  <select id="tipoContrato" name="tipoContrato" class="form-control">
		                    <option value=""></option>
		                    <option value=1>Mano de Obra y Suministro por separado</option>
		                    <option value=2>Suministro e Instalación</option>
		                  </select>
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
	        <button type="button" class="btn btn-warning" id="btnAutoGenListadoAplicar" disabled><i class="fas fa-magic"></i> Aplicar</button>
	      </div>
	    </div>
	  </div>
	</div>
	<!-- Modal -->

	<style>
	/* Modal Auto-PDC: estilos mobile-first */
		#modalPdcAutoGenerar .modal-body {
			max-height: calc(100vh - 230px);
			overflow-y: auto;
			-webkit-overflow-scrolling: touch;
		}
		.pdc-auto-card {
			margin-bottom: 1rem;
			border-left: 4px solid #6c757d;
		}
		.pdc-auto-card--auto { border-left-color: #28a745; }
		.pdc-auto-card--warning { border-left-color: #ffc107; }
		.pdc-auto-card--manual { border-left-color: #dc3545; }
		.pdc-auto-card__header {
			display: flex;
			flex-direction: column;
			gap: .25rem;
		}
		.pdc-auto-card__check {
			display: flex;
			align-items: center;
			gap: .5rem;
			min-height: 44px;
			margin-bottom: 0;
		}
		.pdc-auto-card__check input[type="checkbox"] {
			width: 1.25rem;
			height: 1.25rem;
			flex-shrink: 0;
		}
		.pdc-auto-card__badges {
			display: flex;
			align-items: center;
			gap: .5rem;
		}
		.pdc-auto-fuente { color: #6c757d; }
		.pdc-auto-dias {
			display: flex;
			flex-wrap: wrap;
			gap: .35rem;
			margin: .5rem 0;
		}
		.pdc-auto-dias__chip {
			background: #f4f1ea;
			border-radius: 999px;
			padding: .2rem .6rem;
			font-size: .75rem;
			white-space: nowrap;
		}
		.pdc-auto-dias--option .pdc-auto-dias__chip { font-size: .7rem; }
		#modalPdcAutoGenerar details > summary {
			min-height: 44px;
			padding: .6rem 0;
			cursor: pointer;
		}
		#modalPdcAutoGenerar .ts-control { min-height: 44px; }
		.pdc-auto-manual {
			margin-top: 1rem;
			padding-top: .5rem;
			border-top: 1px solid #dee2e6;
		}
		@media (min-width: 768px) {
			.pdc-auto-card__header {
				flex-direction: row;
				justify-content: space-between;
				align-items: flex-start;
			}
		}
	</style>

	<!-- Wizard para auto-generar PDC desde el Programa General -->
		<div class="modal fade aia-modal" id="modalPdcAutoGenerar" tabindex="-1" role="dialog" aria-labelledby="modalPdcAutoGenerarLabel" aria-hidden="true">
		  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <div class="modal-title" id="modalPdcAutoGenerarLabel">
		          <div class="aia-modal__eyebrow">AIA Plan de Compras</div>
		          <h2 class="aia-modal__headline">Auto-generar Plan de Compras desde Programa General</h2>
		          <p class="aia-modal__subtitle">Revisa las familias detectadas en el Programa General y confirma las modalidades antes de crear paquetes.</p>
		        </div>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div id="pdcAutoResumen" class="alert alert-info mb-3">Presiona cargar sugerencias para analizar el programa general.</div>
		        <div id="pdcAutoListado" class="pdc-auto-list"></div>
		      </div>
		      <div class="modal-footer aia-modal__buttons">
		        <button type="button" id="btn_pdc_auto_recargar" class="btn btn-secondary">Recargar sugerencias</button>
		        <button type="button" id="btn_pdc_auto_aplicar" class="btn btn-primary" disabled>Crear paquetes seleccionados</button>
		        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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

			return rol === 'A' || rol === 'D' || rol === 'OT';
		};

		var pdcAutoSuggestions = [];
		var pdcAutoTomSelects = [];

		var escaparHtml = function(value) {
			return String(value == null ? '' : value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		};

		/* El backend envía la actividad con HTML embebido (<b>, <small>, &Oacute;...):
		   se limpian tags y se decodifican entidades antes de volver a escapar. */
		var limpiarTextoActividad = function(value) {
			var sinTags = String(value == null ? '' : value).replace(/<[^>]*>/g, ' ');
			var decodificado = $('<textarea>').html(sinTags).text();
			return decodificado.replace(/\s+/g, ' ').trim();
		};

		var obtenerBadgeConfianzaPdc = function(confianza) {
			var valor = parseInt(confianza, 10) || 0;
			if (valor >= 80) {
				return { clase: 'badge-success', texto: 'Auto', mod: 'auto' };
			}
			if (valor >= 50) {
				return { clase: 'badge-warning', texto: 'Revisión', mod: 'warning' };
			}
			return { clase: 'badge-danger', texto: 'Manual', mod: 'manual' };
		};

		var iconoFuentePdc = function(matchedBy) {
			var mapa = {
				nombre: { icono: 'fa-tag', titulo: 'Detectada por nombre de actividad' },
				breadcrumb: { icono: 'fa-layer-group', titulo: 'Detectada por capítulo (breadcrumb)' },
				capitulo: { icono: 'fa-sitemap', titulo: 'Detectada por capítulo padre' }
			};
			var fuente = mapa[matchedBy];
			if (!fuente) {
				return '';
			}
			return '<i class="fas ' + fuente.icono + ' pdc-auto-fuente" title="' + fuente.titulo + '"></i>';
		};

		var fuenteDominantePdc = function(actividades) {
			var conteo = {};
			var dominante = null;
			var maximo = 0;
			(actividades || []).forEach(function(actividad) {
				if (!actividad.matchedBy) {
					return;
				}
				conteo[actividad.matchedBy] = (conteo[actividad.matchedBy] || 0) + 1;
				if (conteo[actividad.matchedBy] > maximo) {
					maximo = conteo[actividad.matchedBy];
					dominante = actividad.matchedBy;
				}
			});
			return dominante;
		};

		var pdcAutoEtiquetasDias = {
			dias_elaboracion: 'Elaboración',
			dias_entrega: 'Entrega',
			dias_recibo: 'Recibo',
			dias_cuadros: 'Cuadros',
			dias_legalizacion: 'Legalización',
			dias_fabricacion: 'Fabricación',
			dias_insumos: 'Insumos'
		};

		var renderizarDiasPdc = function(dias) {
			var chips = [];
			Object.keys(pdcAutoEtiquetasDias).forEach(function(clave) {
				var valor = parseInt(dias && dias[clave], 10) || 0;
				if (valor > 0) {
					chips.push('<span class="pdc-auto-dias__chip">' + pdcAutoEtiquetasDias[clave] + ': ' + valor + ' d</span>');
				}
			});
			if (!chips.length) {
				return '<small class="text-muted">Sin duraciones configuradas</small>';
			}
			return chips.join('');
		};

		var construirOpcionesSelectPdc = function(item) {
			var grupos = {};
			var ordenGrupos = [];
			(item.opciones || []).forEach(function(opcion) {
				var grupo = (opcion.tipoContratoNombre || '').trim() || 'Otras modalidades';
				if (!grupos[grupo]) {
					grupos[grupo] = [];
					ordenGrupos.push(grupo);
				}
				var textParts = [];
				if (opcion.tipoPaquete && opcion.tipoPaquete.trim()) {
					textParts.push(escaparHtml(opcion.tipoPaquete));
				}
				var items = (opcion.items || []).map(function(paquete) {
					var parts = [];
					if (paquete.tipoPaquete && paquete.tipoPaquete.trim()) {
						parts.push(escaparHtml(paquete.tipoPaquete));
					}
					if (paquete.paqueteNombre && paquete.paqueteNombre.trim()) {
						parts.push(escaparHtml(paquete.paqueteNombre));
					}
					return parts.join(': ');
				}).filter(function(v) { return v; }).join(' / ');
				if (items) {
					textParts.push(items);
				}
				var selected = parseInt(opcion.optionId, 10) === parseInt(item.optionId, 10) ? ' selected' : '';
				grupos[grupo].push('<option value="' + opcion.optionId + '"' + selected + '>' + textParts.join(' · ') + '</option>');
			});
			return ordenGrupos.map(function(grupo) {
				return '<optgroup label="' + escaparHtml(grupo) + '">' + grupos[grupo].join('') + '</optgroup>';
			}).join('');
		};

		var buscarOpcionPdc = function(item, optionId) {
			var encontrada = null;
			(item && item.opciones || []).forEach(function(opcion) {
				if (parseInt(opcion.optionId, 10) === parseInt(optionId, 10)) {
					encontrada = opcion;
				}
			});
			return encontrada;
		};

		var destruirTomSelectsPdc = function() {
			pdcAutoTomSelects.forEach(function(ts) {
				try { ts.destroy(); } catch (e) {}
			});
			pdcAutoTomSelects = [];
		};

		var actualizarDiasPdc = function(index) {
			var item = pdcAutoSuggestions[index];
			if (!item) {
				return;
			}
			var opcion = buscarOpcionPdc(item, item.optionId);
			$('#pdcAutoDias-' + index).html(renderizarDiasPdc(opcion ? opcion.dias : null));
		};

		var sincronizarOpcionPdc = function(index, value) {
			if (isNaN(index) || !pdcAutoSuggestions[index]) {
				return;
			}
			var optionId = parseInt(value, 10) || null;
			pdcAutoSuggestions[index].optionId = optionId;
			pdcAutoSuggestions[index].selected = !!optionId;
			$('.pdc-auto-selected[data-index="' + index + '"]').prop('checked', !!optionId);
			actualizarDiasPdc(index);
		};

		var inicializarTomSelectsPdc = function() {
			if (typeof window.TomSelect !== 'function') {
				return;
			}
			$('#pdcAutoListado .pdc-auto-option').each(function() {
				var select = this;
				var index = parseInt($(select).data('index'), 10);
				if (select.disabled || isNaN(index)) {
					return;
				}
				var ts = new TomSelect(select, {
					maxItems: 1,
					create: false,
					allowEmptyOption: false,
					placeholder: 'Selecciona modalidad...',
					searchField: ['text', 'optgroup'],
					render: {
						option: function(dataOpt, escape) {
							var opcion = buscarOpcionPdc(pdcAutoSuggestions[index], dataOpt.value);
							var dias = opcion ? '<div class="pdc-auto-dias pdc-auto-dias--option">' + renderizarDiasPdc(opcion.dias) + '</div>' : '';
							return '<div>' + escape(dataOpt.text) + dias + '</div>';
						}
					},
					onChange: function(value) {
						sincronizarOpcionPdc(index, value);
					}
				});
				pdcAutoTomSelects.push(ts);
			});
		};

		var inicializarAutoPdc = function() {
			$(document).off('click.pdcAutoOpen', '#btn_auto_generar_pdc').on('click.pdcAutoOpen', '#btn_auto_generar_pdc', function(e) {
				e.preventDefault();
				$('#modalPdcAutoGenerar').modal('show');
				cargarSugerenciasPdc();
			});

			$('#btn_pdc_auto_recargar').off('click.pdcAutoReload').on('click.pdcAutoReload', function(e) {
				e.preventDefault();
				cargarSugerenciasPdc();
			});

			$('#btn_pdc_auto_aplicar').off('click.pdcAutoApply').on('click.pdcAutoApply', function(e) {
				e.preventDefault();
				aplicarSugerenciasPdc();
			});

			/* Ruta de degradación: si el CDN de TomSelect falla, el select nativo sigue
			   sincronizando estado por este handler delegado. */
			$(document).off('change.pdcAutoOption', '.pdc-auto-option').on('change.pdcAutoOption', '.pdc-auto-option', function() {
				sincronizarOpcionPdc(parseInt($(this).data('index'), 10), $(this).val());
			});

			$(document).off('change.pdcAutoSelected', '.pdc-auto-selected').on('change.pdcAutoSelected', '.pdc-auto-selected', function() {
				var index = parseInt($(this).data('index'), 10);
				if (!isNaN(index) && pdcAutoSuggestions[index]) {
					pdcAutoSuggestions[index].selected = $(this).is(':checked');
				}
			});

			$('#modalPdcAutoGenerar').off('hidden.bs.modal.pdcAuto').on('hidden.bs.modal.pdcAuto', function() {
				destruirTomSelectsPdc();
			});
		};

		var inicializarBreadcrumbPg = function() {
			$(document).off('click.pgBreadcrumb', '#btn_estandarizar_pg').on('click.pgBreadcrumb', '#btn_estandarizar_pg', function(e) {
				e.preventDefault();
				var btn = $(this);
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('Max_Semana').value;

				AIA.Notice.confirm(
					'Esto mejorará los breadcrumbs del Programa General para que el motor de detección funcione mejor. ¿Continuar?',
					'Estandarizar PG'
				).then(function(confirmed) {
					if (!confirmed) return;

					btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

					$.ajax({
						method: 'POST',
						url: '/api/pg/breadcrumb-estandarizar?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
						dataType: 'json'
					}).done(function(response) {
						if (!response || response.respuesta !== 'BIEN') {
							AIA.Notice.error((response && response.mensaje) || 'Error al estandarizar.');
							return;
						}
						var msg = 'Actualizados: ' + response.actualizados +
							', Ya tenían: ' + response.yaTenian +
							', Sin jerarquía: ' + response.sinId;
						AIA.Notice.success(msg);
					}).fail(function() {
						AIA.Notice.error('Error de conexión al estandarizar.');
					}).always(function() {
						btn.prop('disabled', false).html('Estandarizar PG <i class="fas fa-sitemap"></i>');
					});
				});
			});
		};

		var inicializarAutoGenerarListado = function() {
			var pdcAutoListadoSuggestions = [];

			$(document).off('click.autoGenListado', '#btn_auto_generar_listado').on('click.autoGenListado', '#btn_auto_generar_listado', function(e) {
				e.preventDefault();
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
					url: '/api/listado-actividades/auto-generate?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
					dataType: 'json'
				}).done(function(response) {
					if (!response || response.respuesta !== 'BIEN') {
						$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron cargar sugerencias.'));
						btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
						return;
					}

					var sugerencias = response.sugerencias || [];
					var gruposCreadas = response.gruposCreadas || [];
					var totalGrupos = response.totalGrupos || 0;
					var estrategia = response.estrategia || 'familia';
					var ratio = response.totalProcesadas > 0 ? (response.totalProcesadas / Math.max(response.creadas, 1)).toFixed(1) + ':1' : 'N/A';

					var msg = 'PG: <strong>' + response.totalProcesadas + '</strong> · ';
					msg += 'Familias/Grupos: <strong>' + totalGrupos + '</strong> · ';
					msg += 'Actividades consolidadas: <strong>' + response.creadas + '</strong> · ';
					msg += 'Ratio: <strong>' + ratio + '</strong> · ';
					msg += 'Estrategia: <strong>' + estrategia + '</strong> · ';
					msg += 'Sin match: <strong>' + response.sinMatch + '</strong>';
					$('#autoGenListadoResumen').removeClass('alert-info alert-danger').addClass('alert-success').html(msg);

					// Mostrar primero los grupos consolidados creados
					var html = '';
					if (gruposCreadas.length > 0) {
						html += '<tr style="background-color: #d4edda; font-weight: bold;"><td colspan="6"><i class="fas fa-layer-group"></i> GRUPOS CONSOLIDADOS CREADOS (' + gruposCreadas.length + ')</td></tr>';
						for (var g = 0; g < gruposCreadas.length; g++) {
							var gr = gruposCreadas[g];
							var badgeGrupo = '<span class="badge badge-success">+' + gr.totalActividades + ' PG</span>';
							html += '<tr style="background-color: #f0f8ff;">';
							html += '<td>' + (g + 1) + '</td>';
							html += '<td><strong>' + escaparHtml(gr.familia) + '</strong><br><small class="text-muted">' + escaparHtml(gr.descripcion.substring(0, 120)) + (gr.descripcion.length > 120 ? '...' : '') + '</small></td>';
							html += '<td>' + escaparHtml(gr.fechaInicio || '-') + '</td>';
							html += '<td>' + escaparHtml(gr.familiaCodigo) + '</td>';
							html += '<td>' + (gr.confianzaMin || 0) + '%</td>';
							html += '<td>' + badgeGrupo + ' Consolidado</td>';
							html += '</tr>';
						}
						if (sugerencias.length > 0) {
							html += '<tr style="background-color: #fff3cd;"><td colspan="6"><i class="fas fa-exclamation-triangle"></i> SIN MATCH DE FAMILIA (' + sugerencias.length + ')</td></tr>';
						}
					}

					// Mostrar sugerencias sin match
					for (var i = 0; i < sugerencias.length; i++) {
						var s = sugerencias[i];
						var badge = '<span class="badge badge-secondary">Sin match</span>';
						var estado = escaparHtml(s.motivo || 'Sin familia detectada');
						html += '<tr><td>' + (i + 1) + '</td><td>' + escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : '') + '</td><td>' + escaparHtml(s.fechaInicio || '-') + '</td><td>-</td><td>-</td><td>' + badge + ' ' + estado + '</td></tr>';
					}
					$('#autoGenListadoBody').html(html);
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				}).fail(function(xhr) {
					var mensaje = 'Error de conexión al analizar.';
					if (xhr.responseJSON && xhr.responseJSON.mensaje) {
						mensaje = xhr.responseJSON.mensaje;
					}
					$('#autoGenListadoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				});
			});
		};

		var cargarSugerenciasPdc = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('Max_Semana').value;
			$('#pdcAutoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Analizando actividades del programa general...');
			destruirTomSelectsPdc();
			$('#pdcAutoListado').html('');
			$('#btn_pdc_auto_aplicar').prop('disabled', true);

			$.ajax({
				method: 'POST',
				url: '/api/pdc/auto/suggest?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
				dataType: 'json'
			}).done(function(response) {
				if (!response || response.respuesta !== 'BIEN') {
					$('#pdcAutoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron cargar sugerencias.'));
					return;
				}

				pdcAutoSuggestions = response.data.suggestions || [];
				renderizarSugerenciasPdc(response.data);
			}).fail(function(xhr) {
				var mensaje = 'No se pudieron cargar sugerencias.';
				if (xhr.responseJSON && xhr.responseJSON.mensaje) {
					mensaje = xhr.responseJSON.mensaje;
				}
				$('#pdcAutoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
			});
		};

		var renderizarCardSugerenciaPdc = function(item, index) {
			var badge = obtenerBadgeConfianzaPdc(item.confianza);
			var fuenteFamilia = iconoFuentePdc(fuenteDominantePdc(item.actividades));
			var motivos = (item.motivosRevision || []).map(escaparHtml).join('<br>');
			var tieneOpciones = (item.opciones || []).length > 0;
			var opcionActiva = buscarOpcionPdc(item, item.optionId);
			var totalActividades = (item.actividades || []).length;

			var selectHtml;
			if (tieneOpciones) {
				selectHtml = '<select class="form-control pdc-auto-option" id="pdcAutoOption-' + index + '" data-index="' + index + '">' + construirOpcionesSelectPdc(item) + '</select>';
			} else {
				selectHtml = '<select class="form-control pdc-auto-option" id="pdcAutoOption-' + index + '" data-index="' + index + '" disabled><option value="">Sin opciones predefinidas (gestión en Fase 4)</option></select>';
			}

			var actividades = (item.actividades || []).map(function(actividad) {
				var icono = iconoFuentePdc(actividad.matchedBy);
				return '<li>' + (icono ? icono + ' ' : '') + escaparHtml(actividad.id || actividad.consecutivoPrograma) + ' · ' + escaparHtml(limpiarTextoActividad(actividad.actividad)) + ' <small>(' + escaparHtml(actividad.fechaInicio || 'sin fecha') + ')</small></li>';
			}).join('');

			return '<div class="card pdc-auto-card pdc-auto-card--' + badge.mod + '">'
				+ '<div class="card-body">'
				+ '<div class="pdc-auto-card__header">'
				+ '<label class="pdc-auto-card__check"><input type="checkbox" class="pdc-auto-selected" data-index="' + index + '"' + (item.selected ? ' checked' : '') + (tieneOpciones ? '' : ' disabled') + '> <strong>' + escaparHtml(item.familiaNombre) + '</strong></label>'
				+ '<div class="pdc-auto-card__badges"><span class="badge ' + badge.clase + '">' + badge.texto + '</span>' + fuenteFamilia + '</div>'
				+ '</div>'
				+ '<div class="pdc-auto-card__meta"><small>' + escaparHtml(item.categoria || 'Familia constructiva') + ' · Confianza ' + (parseInt(item.confianza, 10) || 0) + '% · Inicio ' + escaparHtml(item.fechaInicio || 'sin fecha') + '</small></div>'
				+ '<div class="form-group mt-2 mb-1"><label class="mb-1" for="pdcAutoOption-' + index + '">Modalidad / paquetes</label>' + selectHtml + '</div>'
				+ '<div class="pdc-auto-dias" id="pdcAutoDias-' + index + '">' + renderizarDiasPdc(opcionActiva ? opcionActiva.dias : null) + '</div>'
				+ (motivos ? '<div class="alert alert-warning py-2 mb-2"><small>' + motivos + '</small></div>' : '')
				+ '<details class="pdc-auto-detalle"><summary>Actividades asociadas (' + totalActividades + ')</summary><ul class="mt-2 mb-0 pl-3">' + actividades + '</ul></details>'
				+ '</div></div>';
		};

		var renderizarManualReviewPdc = function(lista) {
			if (!(lista || []).length) {
				return '';
			}
			var items = lista.map(function(actividad) {
				var detalle = [];
				detalle.push(escaparHtml(actividad.fechaInicio || 'sin fecha'));
				if (actividad.motivo) {
					detalle.push(escaparHtml(actividad.motivo));
				}
				return '<li>' + escaparHtml(actividad.id || actividad.consecutivoPrograma) + ' · ' + escaparHtml(limpiarTextoActividad(actividad.actividad)) + ' <small>(' + detalle.join(' · ') + ')</small></li>';
			}).join('');
			return '<details class="pdc-auto-manual"><summary><i class="fas fa-question-circle"></i> Actividades sin familia detectada (' + lista.length + ')</summary><ul class="mt-2 mb-0">' + items + '</ul></details>';
		};

		var renderizarSugerenciasPdc = function(data) {
			destruirTomSelectsPdc();

			var resumen = 'Actividades del Programa General: <strong>' + data.totalActividades + '</strong> · Familias detectadas: <strong>' + data.familiasSugeridas + '</strong> · Auto-seleccionadas: <strong>' + data.autoSeleccionadas + '</strong> · Revisión: <strong>' + data.requierenRevision + '</strong> · Sin mapeo: <strong>' + data.sinMapeo + '</strong>';
			$('#pdcAutoResumen').removeClass('alert-danger').addClass('alert-info').html(resumen);

			if (!pdcAutoSuggestions.length) {
				$('#pdcAutoListado').html('<div class="alert alert-warning">No se encontraron familias configuradas para crear paquetes.</div>' + renderizarManualReviewPdc(data.manualReview));
				return;
			}

			var html = pdcAutoSuggestions.map(renderizarCardSugerenciaPdc).join('') + renderizarManualReviewPdc(data.manualReview);
			$('#pdcAutoListado').html(html);
			inicializarTomSelectsPdc();
			$('#btn_pdc_auto_aplicar').prop('disabled', false);
		};

		var aplicarSugerenciasPdc = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('Max_Semana').value;
			var seleccionadas = pdcAutoSuggestions.filter(function(item) {
				return item.selected && item.optionId;
			});

			if (!seleccionadas.length) {
				$('#pdcAutoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html('Selecciona al menos una familia con modalidad válida.');
				return;
			}

			$('#btn_pdc_auto_aplicar').prop('disabled', true);
			$('#pdcAutoResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Creando paquetes en el PDC...');

			$.ajax({
				method: 'POST',
				url: '/api/pdc/auto/apply?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				data: JSON.stringify({ suggestions: seleccionadas })
			}).done(function(response) {
				if (!response || response.respuesta !== 'BIEN') {
					$('#pdcAutoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron crear los paquetes.'));
					$('#btn_pdc_auto_aplicar').prop('disabled', false);
					return;
				}

				var msg = 'Paquetes creados: <strong>' + response.insertados + '</strong> · Omitidos: <strong>' + response.omitidos + '</strong>';
				if (typeof response.writeBacks === 'number') {
					msg += ' · Sincronizados: <strong>' + response.writeBacks + '</strong>';
				}
				msg += '. Revisa la pestaña Plan de Compras.';

				var conflictos = response.conflictos || [];
				if (conflictos.length > 0) {
					msg += '<br><br><strong><i class="fas fa-exclamation-triangle text-warning"></i> Conflictos detectados (' + conflictos.length + '):</strong>';
					msg += '<div class="table-responsive mt-1"><table class="table table-sm table-bordered mb-0" style="font-size:0.82rem">';
					msg += '<thead><tr><th>Actividad</th><th>Paquete intentado</th><th>Motivo</th></tr></thead><tbody>';
					for (var c = 0; c < conflictos.length; c++) {
						var cf = conflictos[c];
						msg += '<tr><td>' + escaparHtml(cf.actividad || ('ID ' + cf.actividadId)) + '</td>';
						msg += '<td>' + escaparHtml(cf.paqueteIntentado || '') + '</td>';
						msg += '<td>' + escaparHtml(cf.motivo || 'Columnas ocupadas') + '</td></tr>';
					}
					msg += '</tbody></table></div>';
					$('#pdcAutoResumen').removeClass('alert-info alert-danger').addClass('alert-warning').html(msg);
				} else {
					$('#pdcAutoResumen').removeClass('alert-info alert-danger').addClass('alert-success').html(msg);
				}
			}).fail(function(xhr) {
				var mensaje = 'No se pudieron crear los paquetes.';
				if (xhr.responseJSON && xhr.responseJSON.mensaje) {
					mensaje = xhr.responseJSON.mensaje;
				}
				$('#pdcAutoResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
				$('#btn_pdc_auto_aplicar').prop('disabled', false);
			});
		};

		var cargaParametros = function() {
			inicializarModalNuevaActividad();
			inicializarAutoPdc();
			inicializarBreadcrumbPg();
			inicializarAutoGenerarListado();
      listar();
			guardarNuevaActividad();
			guardarCargarExcel();
      eliminar();
			$(document).off('click.agregarFila').on('click.agregarFila', '#btn_agregar_fila', function(e) {
				e.preventDefault();
				agregarFilaVacia();
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
							if(data==1){
								return "Mano de Obra y Suministro por separado";
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

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:50%;display:inline-block; "><button id="btn_agregar_fila" class="btn btn-success btn-sm" title="Agregar fila vacía directamente en la tabla" style="margin: auto 5px">Agregar fila <i class="fas fa-plus fa-xs"></i></button><button id="btn_cargarActividadesExcel" class="btn btn-secondary btn-sm" title="Cargar listado de actividades desde Excel" data-toggle="modal" data-target="#modalCargarExcel">Cargar desde Excel <i class="fas fa-upload fa-lg"></i></button><button id="btn_nueva_actividad" class="btn btn-primary btn-sm" title="Registrar nueva actividad del proyecto" data-toggle="modal" data-target="#modalNuevaActividad" style="margin: auto 5px">Nueva Actividad <i class="fas fa-plus fa-lg"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_planCompras" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=planCompras&semana='+semana+'&origen=info_listadoActividades\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaBotones .grupo_botones1")
				.addClass("ps-toolbar-actions")
				.removeAttr("style");
			$("div.toolbarFilaBotones .grupo_botones1 .btn").addClass("ps-btn-gap");
			if (puedeEditarListadoActividades()) {
				$("div.toolbarFilaBotones .grupo_botones1").append('<button id="btn_auto_generar_pdc" class="btn btn-info btn-sm ps-btn-gap" title="Auto-generar paquetes del Plan de Compras desde el Programa General">Auto-generar Plan de Compras <i class="fas fa-magic fa-lg"></i></button>');
				$("div.toolbarFilaBotones .grupo_botones1").append('<button id="btn_auto_generar_listado" class="btn btn-warning btn-sm ps-btn-gap" title="Auto-generar listado de actividades desde el Programa General">Auto-generar Listado <i class="fas fa-list fa-lg"></i></button>');
				$("div.toolbarFilaBotones .grupo_botones1").append('<button id="btn_estandarizar_pg" class="btn btn-outline-secondary btn-sm ps-btn-gap" title="Estandarizar breadcrumbs del Programa General para mejorar la detección de familias">Estandarizar PG <i class="fas fa-sitemap"></i></button>');
			}
			$("div.toolbarFilaBotones #btn_nueva_actividad").removeAttr("style");
			$("div.toolbarFilaBotones .grupo_botones_semanal_madre")
				.addClass("ps-toolbar-nav-wrap")
				.removeAttr("style")
				.html('<div class="ps-module-switcher" role="tablist" aria-label="Navegacion general"><button id="btn_Actividades" type="button" class="ps-module-tab is-active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'" aria-label="Ir a Actividades" aria-current="page"><i class="fas fa-table" aria-hidden="true"></i><span>Actividades</span></button><button id="btn_contratos" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'" aria-label="Ir a Contratos"><i class="fas fa-file-alt" aria-hidden="true"></i><span>Contratos</span></button><button id="btn_planCompras" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=planCompras&semana='+semana+'&origen=info_listadoActividades\'" aria-label="Ir a Plan de Compras"><i class="fas fa-shopping-cart" aria-hidden="true"></i><span>Plan de Compras</span></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="d-flex ml-auto"><label for="input_buscador" class="sr-only">Buscar en listado</label><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm mr-1 ml-auto max-w-60" placeholder="Filtro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso_canonico').value);
			activarBuscador("#dt_cliente tbody", table);
		  obtener_data_editar("#dt_cliente tbody", table);
		  obtener_id_eliminar("#dt_cliente tbody", table);
		}

		/* Agrega una fila vacía al inicio de la tabla con campos inline para creación directa */
		var agregarFilaVacia = function() {
			if (!puedeEditarListadoActividades()) {
				return;
			}
			var table = $('#dt_cliente').DataTable();
			var opcionesSelect = <?php echo json_encode($actividadInicioOptionsHtml, JSON_UNESCAPED_UNICODE); ?>;
			var db = document.getElementById('baseDatos').value;

			var newRowData = {
				Id: '', codigo: '', actividad: '', descripcionActividad: '',
				actividadInicio: '', nombreActividadInicio: '',
				fechaInicio: '', tipoContrato: '', semanaActualizacion: ''
			};
			var newNode = table.row.add(newRowData).draw(false).node();
			$(newNode).addClass('aia-inline-new');

			var htmlActividad = "<input id='new_Actividad' name='Actividad' class='form-control form-control-sm' type='text' placeholder='Nombre actividad' autocomplete='off'>";
			$(newNode).find('td:eq(2)').html(htmlActividad);

			var htmlDesc = "<input id='new_descripcionActividad' name='descripcionActividad' class='form-control form-control-sm' type='text' placeholder='Descripción' autocomplete='off'>";
			$(newNode).find('td:eq(3)').html(htmlDesc);

			var htmlActInicio = "<select id='new_actividadInicio' name='actividadInicio' class='form-control form-control-sm'><option value=''></option>" + opcionesSelect + "</select>";
			$(newNode).find('td:eq(4)').html(htmlActInicio);
			$(newNode).find('td:eq(5)').html('');

			var htmlTipo = "<select id='new_tipoContrato' name='tipoContrato' class='form-control form-control-sm'><option value=''></option><option value=1>Mano de Obra y Suministro por separado</option><option value=2>Suministro e Instalación</option></select>";
			$(newNode).find('td:eq(6)').html(htmlTipo);

			var htmlBotones = "<button type='button' id='btn_guardar_nueva' class='btn btn-success btn-sm btn-action-gap' title='Guardar'><i class='fa fa-save fa-xs'></i></button><button type='button' id='btn_cancelar_nueva' class='btn btn-danger btn-sm btn-action-gap' title='Cancelar'><i class='fa fa-undo fa-xs'></i></button>";
			$(newNode).find('td:eq(0)').html(htmlBotones);

			configurarSelectActividadInicio('#new_actividadInicio');
			$('#new_Actividad').focus();

			$(newNode).on('click', '#btn_guardar_nueva', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var actividad = $('#new_Actividad').val();
				var desc = $('#new_descripcionActividad').val();
				var actInicio = $('#new_actividadInicio').val();
				var tipo = $('#new_tipoContrato').val();
				if (!actividad || !desc || !actInicio || !tipo) {
					if (typeof AIA !== 'undefined' && AIA.Notice) {
						AIA.Notice.show({ type: 'warning', title: 'Campos incompletos', message: 'Complete todos los campos para crear la actividad.' });
					}
					return;
				}
				var semana = document.getElementById('Max_Semana').value;
				$.ajax({
					method: "POST",
					url: "/api/listado-actividades/save?db=" + db,
					data: {
						actividad: actividad,
						descripcionActividad: desc,
						actividadInicio: actInicio,
						tipoContrato: tipo,
						opcion: "registrar",
						semana: semana
					}
				}).done(function(info) {
					var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
					if (json_info.respuesta == "BIEN") {
						recargarTabla('');
					}
					if (typeof mostrar_mensaje === 'function') {
						mostrar_mensaje(json_info);
					}
				});
			});

			$(newNode).on('click', '#btn_cancelar_nueva', function(e) {
				e.preventDefault();
				e.stopPropagation();
				table.row(newNode).remove().draw(false);
			});

			$(newNode).on('keydown', '#new_Actividad, #new_descripcionActividad, #new_actividadInicio, #new_tipoContrato', function(e) {
				if (e.keyCode === 13) {
					$('#btn_guardar_nueva', newNode).click();
				} else if (e.keyCode === 27) {
					$('#btn_cancelar_nueva', newNode).click();
				}
			});
		};

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



					var codigo_html_tipoContrato =  "<select id='select_tipoContrato' name='tipoContrato' class='form-control form-control-sm' ><option value=''></option><option value=1>Mano de Obra y Suministro por separado</option><option value=2>Suministro e Instalación</option></select>";
					$row.find('td:eq(6)').html(codigo_html_tipoContrato);

					// var codigo_html_paqueteContratacion =  "<select id='select_paqueteContratacion' name='paqueteContratacion' class='form-control form-control-sm' ><option value=''></option><option value='1'>(1) - Paquete 1</option><option value='2'>(2) - Paquete 2</option><option value='3'>(3) - Paquete 3</option></select>";
					// $row.find('td:eq(6)').html(codigo_html_paqueteContratacion);

					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm btn-action-gap' title='Guardar la edición'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm btn-action-gap' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
					$row.find('td:eq(0)').html(codigo_html_botones);

					$("#select_tipoContrato").val(data.tipoContrato).change();

					$("#select_paqueteContratacion").val(data.idPaqueteContratacion).change();

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
				var tipoContrato = $("#select_tipoContrato").serialize();

				frm = Id + "&" + opcion + "&" + codigo + "&" + Actividad + "&" + descripcionActividad + "&" + actividadInicio + "&" + fechaInicio + "&" + tipoContrato + "&semana=" + semana;
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
