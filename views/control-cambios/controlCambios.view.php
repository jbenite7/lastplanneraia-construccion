<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
	<?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <title>Control de Cambios — Last Planner AIA</title>
	<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
	<script src="/js/modules/aia_ui/csrf.js"></script>
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::renderForModule('control-cambios') ?>
	<link rel="stylesheet" href="/css/control-cambios.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/control-cambios.css') ?: 'cc1')) ?>" />
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body class="aia-shell aia-shell--sidebar">

	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="controlCambios" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<!-- C-46: los emite el servidor; el inyector JS los duplica todavia (Task 37 lo retira). -->
		<input type="hidden" id="baseDatos" name="baseDatos" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
		<input type="hidden" id="permiso_canonico" name="permiso_canonico" value="<?php echo htmlspecialchars($_SESSION['permiso_canonico'] ?? ($permiso ?? ''), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
		<input type="hidden" id="semana" name="semana" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
		<input type="hidden" id="codigo" name="codigo" value="" aria-hidden="true">
	</div>

	<main>
	<h1 class="aia-visually-hidden">Control de Cambios</h1>
	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro" style:"visibility: hidden">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom aia-grid-shell">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100">
			<table id="dt_cliente" class="dt_infoGeneral table aia-table w-100" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Id</th>
						<th>Solicitante</th>
						<th>Detalle Solicitante</th>
						<th>Fecha de Solicitud</th>
						<th>Prioridad</th>
						<th>Tipo de Cambio</th>
						<th>Responsable</th>
						<th>Detalle Responsable</th>
						<th>Justificación</th>
						<th>Descripción</th>
						<th>Incidencia Alcance</th>
						<th>Tiempo Cronograma</th>
						<th>Tiempo Cronograma Afectado</th>
						<th>Incidencia Cronograma</th>
						<th>Valor Presupuesto</th>
						<th>Costo Directo</th>
						<th>Costo Directo + AIU</th>
						<th>Costo Directo + AIU + IVA</th>
						<th>Valor Aprobado</th>
						<th>Incidencia Presupuesto</th>
						<th>Incidencia Calidad</th>
						<th>Incidencia Riesgo</th>
						<th>Fecha Tentativa de Definición</th>
						<th>Fecha de Entrega a Interventoría</th>
						<th>Observaciones</th>
						<th>Fecha de Definición</th>
						<th>Aprobación</th>
						<th>Soportes</th>
					</tr>
					<!-- Segunda fila (filtros) -->
					<tr>
						<th></th>
						<th></th>
						<th>
							<select class="aia-input cc-filter-80" id="buscadorSolicitanteCambio" aria-label="Filtrar por Solicitante">
								<option value="">Todas</option>
								<option value="Obra">Obra</option>
								<option value="Cliente">Cliente</option>
								<option value="Interventoría">Interventoría</option>
								<option value="Otro">Otro</option>
							</select>
						</th>
						<th></th>
						<th><input type="date" id="buscadorFechaSolicitud" class="aia-input cc-filter-80" aria-label="Filtrar por Fecha de Solicitud"></th>
						<th>
							<select id="buscadorPrioridad" class="aia-input" aria-label="Filtrar por Prioridad">
								<option value="">Todas</option>
								<option value="Alta">Alta</option>
								<option value="Media">Media</option>
								<option value="Baja">Baja</option>
							</select>
						</th>
						<th><input type="text" id="buscadorTipoCambio" placeholder="Buscar" class="aia-input cc-filter-80" aria-label="Filtrar por Tipo de Cambio"></th>
						<th>
							<select class="aia-input cc-filter-80" id="buscadorResponsableDefinicion" aria-label="Filtrar por Responsable">
								<option value="">Todas</option>
								<option value="Obra">Obra</option>
								<option value="Cliente">Cliente</option>
								<option value="Interventoría">Interventoría</option>
								<option value="Otro">Otro</option>
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
						<th></th>
						<th></th>
						<th><input type="text" id="buscadorCostoDirecto" placeholder="Buscar" class="aia-input cc-filter-80" aria-label="Filtrar por Costo Directo"></th>
						<th><input type="text" id="buscadorValorAprobado" placeholder="Buscar" class="aia-input cc-filter-80" aria-label="Filtrar por Valor Aprobado"></th>
						<th></th>
						<th></th>
						<th></th>
						<th><input type="date" id="buscadorFechaTentativaDefinicion" class="aia-input cc-filter-80" aria-label="Filtrar por Fecha Tentativa"></th>
						<th><input type="date" id="buscadorFechaEntregaInterventoria" class="aia-input cc-filter-80" aria-label="Filtrar por Fecha Entrega"></th>
						<th><input type="text" placeholder="Buscar" class="aia-input cc-filter-80" aria-label="Filtrar por Observaciones"></th>
						<th><input type="date" id="buscadorFechaDefinicion" class="aia-input cc-filter-80" aria-label="Filtrar por Fecha Definición"></th>
						<th>
							<select class="aia-input cc-filter-80" id="buscadorAprobacion" class="aia-input" aria-label="Filtrar por Aprobación">
								<option value="">Todas</option>
								<option value="En Estudio">En Estudio</option>
								<option value="Aprobado">Aprobado</option>
								<option value="Aprobado con Restricciones">Aprobado con Restricciones</option>
								<option value="No Aprobado">No Aprobado</option>
								<option value="Desistido">Desistido</option>
							</select>
						</th>
						<th></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	</main>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">

	<div class="modal_ordenDeCambio modal fade aia-modal" id="modalordenDeCambio" tabindex="-1" role="dialog" aria-labelledby="modalordenDeCambioLabel" data-keyboard="false">
				<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
					<div class="modal-content" id="modalordenDeCambioContent">
						<div class="modal-header p-0">
							<div class="col-sm-12 p-0 h-100">
								<div class="row m-0 d-flex align-items-center pt-2 pb-2">
									<div class="col-sm-3 m-auto cc-logo-col">
										<img src="/img/logoHorizontal.png" class="img-fluid" alt="Responsive image">
									</div>
									<div class="col-sm m-auto">
										<!-- h4, no h1: el h1 del documento es el de la linea 32. Este titulo de
										     modal era el unico <h1 class="modal-title"> de todo views/ (los otros 16
										     usan h4 o h5, incluido el de este mismo archivo en la 587), y dejaba la
										     pagina con DOS h1. Hallazgo B-8 del barrido del 2026-08-07. -->
										<h4 class="modal-title" id="modalordenDeCambioLabel">
											<b><p class="modal-body-texto-ordenDeCambio text-center  mb-0" id="modal-body-texto-ordenDeCambio">Orden de Cambio</p></b>
										</h4>
									</div>
									<div class="col-sm-3 m-auto cc-logo-col">
										<img src="/img/etiquetaConstructoraAIASAS.png" class="img-fluid" alt="Responsive image">
									</div>
									<button type="button" class="close col-sm-1" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" onclick='cerrarTodosModales();recargarTabla("listar")'>&times;</span></button>
								</div>
							</div>
						</div>
						<div class="modal-body">
							<form class="formOrdenCambio form-horizontal" action="" method="POST">


								<!-- Se crean los inputs del formulario de contratos de suministro -->
								<div class="col-sm-12 p-0 mb-4 cc-field-block border-top-0 rounded">
									<div class="tituloFormularioCambios form-group mb-0">
									<h3 class="form_general">Información General</h3>
									</div>
									<div class="row m-0 mb-3">
									<div class="form-group col-sm mt-0 mb-0 pt-3 pb-3 h-100">
											<label for="inputConsecutivo"><b>Número de Orden</b></label>
											<input type="number" class="aia-input" id="inputConsecutivo" name="inputConsecutivo" placeholder="Consecutivo" autocomplete="off" readonly>
										</div>
									<div class="form-group col-sm mt-0 mb-0 pt-3 pb-3 h-100">
											<label for="inputProyecto"><b>Proyecto</b></label>
											<input type="text" class="aia-input" name="inputProyecto" id="inputProyecto" placeholder="Proyecto" autocomplete="off" readonly>
										</div>
									</div>
									<div class="row m-0 cc-field-divider">
									<div class="form-group col-sm mt-0 mb-0 pb-3 h-100">
											<label for="inputDirector"><b>Director de Obra</b></label>
											<input type="text" class="aia-input" name="inputDirector" id="inputDirector" placeholder="Director" autocomplete="off" readonly>
										</div>
									<div class="form-group col-sm mt-0 mb-0 pb-3 h-100">
											<label for="inputFechaSolicitud"><b>Fecha de Solicitud</b></label>
											<input type="text" class="aia-input" name="inputFechaSolicitud" id="inputFechaSolicitud" placeholder="Fecha de Solicitud" autocomplete="off" readonly>
										</div>
									</div>
									<div class="row m-0 cc-field-divider">
									<div class="form-group col-sm-7 cc-field-edge mt-0 mb-0 pt-3 pb-3 h-100">
											<div class="form-group mb-0">
												<label for="radioSolicitanteCambio"><b>Solicitante del Cambio</b></label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioObra" value="1" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioObra">Obra</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioCliente" value="2" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioCliente">Cliente</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioInterventoria" value="3" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioInterventoria">Interventoría</label>
											</div>
											<div class="form-check form-check-inline col-sm-6" data-field-group="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioOtro" value="4" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = false;">
												<label class="form-check-label" for="inputSolicitanteCambioOtro">Otro:&nbsp&nbsp</label>
												<input type="text" class="aia-input" name="inputDetalleSolicitanteOtro" id="inputDetalleSolicitanteOtro" placeholder="¿Quien?" disabled autocomplete="off">
											</div>
										</div>
										<div class="form-group col-sm-5 mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="radioPrioridad"><b>Prioridad</b></label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadAlta" value="1">
												<label class="form-check-label" for="inputPrioridadAlta">Alta</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadMedia" value="2">
												<label class="form-check-label" for="inputPrioridadMedia">Media</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadBaja" value="3">
												<label class="form-check-label" for="inputPrioridadBaja">Baja</label>
											</div>
										</div>
									</div>
									<div class="row m-0">
										<div class="form-group col-sm-5 cc-field-edge mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="checkboxTipoCambio"><b>Tipo de Cambio</b></label>
											</div>
											<div class="row m-0">
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioAlcance" id="inputTipoCambioAlcance" value="1">
													<label class="form-check-label" for="inputTipoCambioAlcance">Alcance</label>
												</div>
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCronograma" id="inputTipoCambioCronograma" value="1">
													<label class="form-check-label" for="inputTipoCambioCronograma">Cronograma</label>
												</div>
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCosto" id="inputTipoCambioCosto" value="1">
													<label class="form-check-label" for="inputTipoCambioCosto">Costo</label>
												</div>
											</div>
											<div class="row mt-1">
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCalidad" id="inputTipoCambioCalidad" value="1">
													<label class="form-check-label" for="inputTipoCambioCalidad">Calidad</label>
												</div>
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioRiesgo" id="inputTipoCambioRiesgo" value="1">
													<label class="form-check-label" for="inputTipoCambioRiesgo">Riesgo</label>
												</div>
												<div class="form-check form-check-inline" data-field-group="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioRecurso" id="inputTipoCambioRecurso" value="1">
													<label class="form-check-label" for="inputTipoCambioRecurso">Recurso</label>
												</div>
											</div>
										</div>
										<div class="form-group col-sm-7 mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="radioResponsableSolucion"><b>Responsable de la Definición de Cambio</b></label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionObra" value="1" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionObra">Obra</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionCliente" value="2" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionCliente">Cliente</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionInterventoria" value="3" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionInterventoria">Interventoría</label>
											</div>
											<div class="form-check form-check-inline col-sm-6" data-field-group="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionOtro" value="4" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = false;">
												<label class="form-check-label" for="inputResponsableSolucionOtro">Otro:&nbsp&nbsp</label>
												<input type="text" class="aia-input" name="inputDetalleResponsableSolucion" id="inputDetalleResponsableSolucion" placeholder="¿Quien?" disabled autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 mb-4 cc-field-block border-top-0 rounded">
									<div class="tituloFormularioCambios form-group mb-0">
									<h3 class="form_general">Detalle del Cambio</h3>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputJustificacion">Justificación</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputJustificacion" id="inputJustificacion" rows="3" onkeyup="contadorTextarea(this,'contadorJustificacion',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorJustificacion" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputDescripcion">Descripción <br>del Cambio</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputDescripcion" id="inputDescripcion" rows="3" onkeyup="contadorTextarea(this,'contadorDescripcion',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorDescripcion" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputIncidenciaAlcance">Incidencia en <br>el Alcance</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaAlcance" id="inputIncidenciaAlcance" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaAlcance',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaAlcance" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold">Incidencia <br>en el <br>Cronograma</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputTiempoCronograma">Días Según Cronograma</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputTiempoCronograma" id="inputTiempoCronograma" data-type="number" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputTiempoCronogramaAfectado">Días Adicionales</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputTiempoCronogramaAfectado" id="inputTiempoCronogramaAfectado" data-type="number" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputPorcentajeAfectacionCronograma">% Afectación Cronograma</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputPorcentajeAfectacionCronograma" id="inputPorcentajeAfectacionCronograma" data-type="text" autocomplete="off" readonly>
												</div>
											</div>
											<div class="row m-0">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputIncidenciaCronograma">Observaciones</label>
												</div>
												<div class="col-sm-7 p-0 d-flex flex-column">
													<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaCronograma" id="inputIncidenciaCronograma" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaCronograma',500)" autocomplete="off"></textarea>
												</div>
											</div>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaCronograma" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold">Incidencia <br>en el <br>Presupuesto</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputValorPresupuesto">Costo Actividad Según Presupuesto (Incluye AIU + IVA)</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputValorPresupuesto" id="inputValorPresupuesto" data-type="currency" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputCostoDirecto">Costo Directo</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputCostoDirecto" id="inputCostoDirecto" data-type="currency" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputCostoDirectoAIU">Costo Directo + AIU</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputCostoDirectoAIU" id="inputCostoDirectoAIU" data-type="currency" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputCostoDirectoAIUIVA">Costo Directo + AIU + IVA</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputCostoDirectoAIUIVA" id="inputCostoDirectoAIUIVA" data-type="currency" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputValorAprobado">Valor Aprobado</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputValorAprobado" id="inputValorAprobado" data-type="currency" autocomplete="off">
												</div>
											</div>
											<div class="row m-0 cc-field-divider">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputPorcentajeAfectacionPresupuesto">% Afectación Presupuesto</label>
												</div>
												<div class="col-sm-7 p-0">
													<input type="text" class="aia-input h-100" name="inputPorcentajeAfectacionPresupuesto" id="inputPorcentajeAfectacionPresupuesto" data-type="text" autocomplete="off" readonly>
												</div>
											</div>
											<div class="row m-0">
												<div class="col-sm-5 cc-field-label p-2 d-flex align-items-center">
													<label class="mb-0 font-weight-bold text-wrap" for="inputIncidenciaPresupuesto">Observaciones</label>
												</div>
												<div class="col-sm-7 p-0 d-flex flex-column">
													<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaPresupuesto" id="inputIncidenciaPresupuesto" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaPresupuesto',500)" autocomplete="off"></textarea>
												</div>
											</div>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaPresupuesto" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputIncidenciaCalidad">Incidencia <br>en la <br>Calidad</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaCalidad" id="inputIncidenciaCalidad" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaCalidad',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaCalidad" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputIncidenciaRiesgo">Incidencia <br>en el <br>Riesgo</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaRiesgo" id="inputIncidenciaRiesgo" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaRiesgo',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaRiesgo" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0 mb-3 cc-field-row rounded shadow-sm">
										<div class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center">
											<label class="mb-0 text-center font-weight-bold" for="inputIncidenciaRecurso">Incidencia <br>en el <br>Recurso</label>
										</div>
										<div class="col-sm-9 p-0 d-flex flex-column cc-field-value">
											<textarea class="aia-input cc-textarea-fixed" name="inputIncidenciaRecurso" id="inputIncidenciaRecurso" rows="3" onkeyup="contadorTextarea(this,'contadorIncidenciaRecurso',500)" autocomplete="off"></textarea>
											<div class="d-flex justify-content-end px-2 py-1 cc-field-counter">
												<p id="contadorIncidenciaRecurso" class="mb-0 small">0 de 500 caracteres permitidos.</p>
											</div>
										</div>
									</div>
									<div class="row m-0">
										<div class="col-sm-6 d-flex justify-content-center cc-field-edge">
										<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3 h-100">
												<label for="inputFechaEntregaInterventoria"><b>Fecha de Entrega a Interventoría</b></label>
												<input type="text" class="aia-input" name="inputFechaEntregaInterventoria" id="inputFechaEntregaInterventoria" placeholder="Fecha de Entrega a Interventoría" autocomplete="off">
											</div>
										</div>
										<div class="col-sm-6 d-flex justify-content-center">
										<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3 h-100">
												<label for="inputFechaTentativaDefinicion"><b>Fecha Tentativa de Definición</b></label>
												<input type="text" class="aia-input" name="inputFechaTentativaDefinicion" id="inputFechaTentativaDefinicion" placeholder="Fecha Tentativa de Definición" autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 mb-4 cc-field-block border-top-0 rounded">
									<div class="tituloFormularioCambios form-group mb-0">
									<h3 class="form_general">Aprobación</h3>
									</div>
									<div class="row m-0">
									<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3 cc-field-edge h-100">
											<div class="form-group mb-0">
												<label for="radioAprobacion"><b>Estado de Aprobación</b></label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionEstudio" value="4">
												<label class="form-check-label" for="inputAprobacionEstudio">En Estudio</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionAprobado" value="1">
												<label class="form-check-label" for="inputAprobacionAprobado">Aprobado</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionAprobadoRestricciones" value="2">
												<label class="form-check-label" for="inputAprobacionAprobadoRestricciones">Aprobado con Restricciones</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionNoAprobado" value="3">
												<label class="form-check-label" for="inputAprobacionNoAprobado">No Aprobado</label>
											</div>
											<div class="form-check form-check-inline" data-field-group="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionDesistido" value="5">
												<label class="form-check-label" for="inputAprobacionDesistido">Desistido</label>
											</div>
										</div>
										<div class="col-sm-4 d-flex justify-content-center">
										<div class="form-group col-sm-12 mt-0 mb-0 pt-3 pb-3 h-100">
												<label for="inputFechaDefinicion"><b>Fecha de Definición</b></label>
												<input type="text" class="aia-input" name="inputFechaDefinicion" id="inputFechaDefinicion" placeholder="Fecha de Definición" autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 cc-field-block border-top-0 rounded">
									<div class="tituloFormularioCambios form-group mb-0">
									<h3 class="form_general">Archivos de Soporte</h3>
									</div>
									<div class="row mt-4 mb-4 col-sm-12">
										<div class="col-sm-11 ml-auto mr-auto">
											<table id="dt_soportes" class="table aia-table w-100">
												<thead>
													<tr>
														<th>Adjunto N°</th>
														<th>Descripción</th>
														<th>Link (URL)</th>
														<th></th>
													</tr>
												</thead>
											</table>
										</div>
										<div class="input-group col-sm-10 mt-2 ml-auto mr-auto">
											<div class="input-group col-sm-12">
												<button type="button" class="aia-btn aia-btn-primary aia-btn--sm" onclick="agregarSoporte()">Agregar <i class="fas fa-plus"></i></button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<div class="col-sm-12">
								<!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
								<div class="row m-0">
									<div class="col-sm-12">
										<p class="mensaje">mensaje</p>
									</div>
								</div>
								<div class="row m-0">
									<div class="col-sm-12 d-flex justify-content-end">
										<button id="btn_guardarOrden" type="button" class="aia-btn aia-btn-primary ml-1 mr-1" onclick="guardar_modificar()">Guardar <i class="fas fa-save fa-lg"></i></i></button>
										<button id="btn_generarPDFOrden" type="button" class="aia-btn aia-btn--secondary ml-1 mr-1">Generar PDF <i class="fas fa-download fa-lg"></i></button>
										<button id="btn_cancelarOrden" type="button" class="aia-btn aia-btn--secondary ml-1 mr-1" data-dismiss="modal" onclick='cerrarTodosModales();recargarTabla("listar")'>Cancelar <i class="fas fa-window-close fa-lg"></i></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Modal -->
		</div>

		<!-- Se crea el Modal que solicita la confirmación de eliminar una orden de cambio o no -->
		<div class="modal fade aia-modal" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel" data-keyboard="false">
		  <div class="modal-dialog modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modalEliminarLabel">Eliminar Actividad</h4>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick='cerrarTodosModales();recargarTabla("listar")'><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-eliminar" id="modal-body-texto-eliminar"></p>
		      </div>
		      <div class="modal-footer">
		        <button type="button" id="eliminarODC" class="aia-btn aia-btn--critical" data-dismiss="modal" data-toggle="modal" onclick='eliminar()'>Aceptar</button>
		        <!--data-target="#modal_CNP"-->
		        <button type="button" id="btn_cancelar_eliminar" class="aia-btn aia-btn--secondary" data-dismiss="modal" onclick='cerrarTodosModales();recargarTabla("listar")'>Cancelar</button>
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
	<script type="text/javascript" charset="utf8" src="/vendor/bootstrap/bootstrap.min.js"></script>
	<!--Iniciar DataTables-->
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
	<!--Botones de Datatables-->
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.bootstrap4.min.js"></script>
	<!--checkboxes DataTables-->
	<script type="text/javascript" src="https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
	<!--Selector de fechas -->
	<!-- jquery-ui local: la copia de public/vendor/ es EXACTAMENTE la misma version que pedia
	     el CDN (1.10.1, verificado en el banner del archivo), asi que el cambio no altera
	     comportamiento. Se cargaba DOS veces, una a cada lado de Google Charts; queda una. -->
	<script src="/vendor/jquery-ui.min.js"></script>
	<!--Google Charts-->
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<!--Any Chart-->
	<script src="https://cdn.anychart.com/releases/v8/js/anychart-base.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>
	<script src="https://cdn.anychart.com/releases/v8/js/anychart-circular-gauge.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>
	<!-- Lista desplegable con buscador -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
	<!--Formatos de números-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
	<!-- Librería jsPDF -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>
	<!-- Librería HTML2Canvas -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
	<!-- Tabulator -->
	<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.3.4/dist/js/tabulator.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		/*
		 * Cableado de la tabla de solicitudes de cambio.
		 *
		 * Esta pantalla nacio con la tabla, la fila de filtros y los `onclick` de los modales, pero sin
		 * el bloque de JS que los sostiene: nadie inicializaba la DataTable y `recargarTabla` /
		 * `cerrarTodosModales` no existian en todo el repositorio. La API (/api/control-cambios/list)
		 * si funcionaba, asi que lo que faltaba era exactamente esto.
		 */
		$(document).on("ready", function() {
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
		});

		var cargaParametros = function() {
			listar();
		}

		/* La base guarda codigos numericos; la cabecera filtra por etiqueta. Se muestran etiquetas. */
		var SOLICITANTES = { "1": "Obra", "2": "Cliente", "3": "Interventoría", "4": "Otro" };
		var PRIORIDADES = { "1": "Alta", "2": "Media", "3": "Baja" };
		var APROBACIONES = {
			"1": "Aprobado", "2": "Aprobado con Restricciones", "3": "No Aprobado",
			"4": "En Estudio", "5": "Desistido"
		};

		var escaparHtml = function(valor) {
			return $("<div>").text(valor == null ? "" : valor).html();
		}

		var etiqueta = function(mapa) {
			return function(data) {
				if (data === null || data === "") return "";
				return escaparHtml(mapa[String(data)] || data);
			};
		}

		var texto = function(data) {
			return escaparHtml(data);
		}

		var moneda = function(data) {
			var n = parseFloat(data);
			if (isNaN(n) || n === 0) return "";
			return "$" + n.toLocaleString('es-CO', { maximumFractionDigits: 0 });
		}

		/* `tipoCambio` viaja como {"tiposCambio":{"Alcance":1,...}}: se resume en la lista marcada. */
		var tiposMarcados = function(data) {
			if (!data) return "";
			var json;
			try { json = typeof data === "string" ? JSON.parse(data) : data; } catch (e) { return ""; }
			var tipos = (json && json.tiposCambio) || {};
			return escaparHtml(Object.keys(tipos).filter(function(k) {
				return tipos[k] && tipos[k] !== "0";
			}).join(", "));
		}

		function calcDataTableHeight() {
			if (window.DataTableHeightManager && typeof window.DataTableHeightManager.calcHeight === "function") {
				return window.DataTableHeightManager.calcHeight({
					container: "#cuadroTabla", internalChrome: 170, bottomMargin: 25, minHeight: 200
				});
			}
			var disponible = $(window).height() - $("#cuadroTabla").offset().top - 170 - 25;
			return (disponible > 200 ? disponible : 200) + "px";
		}

		var idioma_espanol = {
			"sProcessing": "Procesando...",
			"sZeroRecords": "Ninguna solicitud coincide con los filtros.",
			// C-33 · Redacción PROVISIONAL (frase genérica del equipo, 2026-08-05). El estado vacío
			// era el único de la app que dejaba al usuario sin salida: decía que no había nada y no
			// decía de dónde nace una solicitud. Pendiente de que el usuario ratifique la frase de
			// dominio definitiva; el resto de estados vacíos (CNC, CNP, CIC) ya siguen este patrón.
			"sEmptyTable": "Las solicitudes de cambio nacen en obra: cuando el diseño, el cliente o la interventoría piden algo distinto de lo contratado, regístralo aquí para tramitar su aprobación.",
			"sInfo": "Mostrando _TOTAL_ solicitudes",
			"sInfoEmpty": "Sin solicitudes",
			"sInfoFiltered": "(filtrado de un total de _MAX_)",
			"sSearch": "Buscar:",
			"sLoadingRecords": "Cargando...",
			"oAria": {
				"sSortAscending": ": Activar para ordenar la columna de manera ascendente",
				"sSortDescending": ": Activar para ordenar la columna de manera descendente"
			}
		}

		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var alturatabla = calcDataTableHeight();

			var table = $("#dt_cliente").DataTable({
				"dom": "<'row filaMensajes'<'col-md-12 mr-auto p-0'<'toolbarFilaMensajes'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering": false,
				"orderCellsTop": true,
				"autoWidth": false,
				"scrollX": false, /* PROHIBIDO SCROLL HORIZONTAL */
				"scrollY": alturatabla,
				"paging": false,
				"ajax": {
					"method": "POST",
					/* El prefijo va en la query string: el controlador lo lee de $_GET, no del cuerpo. */
					"url": "/api/control-cambios/list?db=" + encodeURIComponent(db),
					/* La API devuelve una fila centinela vacia cuando no hay nada: se descarta para
					   que DataTables muestre su propio estado vacio en vez de una fila fantasma. */
					"dataSrc": function(json) {
						if (json && json.error) {
							/* Sin esto un fallo del servidor se disfrazaria de "no hay solicitudes". */
							$("#mensajeActualizacion").text("No fue posible cargar las solicitudes: " + json.error);
							return [];
						}
						return ((json && json.data) || []).filter(function(fila) {
							return fila.id !== "" && fila.id != null;
						});
					}
				},
				/* 29 definiciones para las 29 columnas de la cabecera. El detalle completo vive en la
				   orden de cambio; aqui solo se deja lo que cabe a 1180 px sin scroll horizontal.
				   Los porcentajes estan repartidos para que ninguna cabecera se parta a mitad de
				   palabra: el ancho extra sale de Descripcion y Tipo de Cambio, que son texto libre
				   y reenvuelven sin perder sentido. Suma constante, asi que no reaparece scroll. */
				"columns": [
					{ "defaultContent": "<button type='button' class='ver-orden aia-btn aia-btn--secondary btn-sm' aria-label='Ver orden de cambio'><i class='fa fa-eye'></i></button>", "width": "4%" },
					{ "data": "id", "width": "5%" },
					{ "data": "solicitanteCambio", "render": etiqueta(SOLICITANTES), "width": "8.5%" },
					{ "data": "detalleSolicitanteOtro", "visible": false },
					{ "data": "fechaSolicitud", "render": texto, "width": "8%" },
					{ "data": "prioridad", "render": etiqueta(PRIORIDADES), "width": "7.5%" },
					{ "data": "tipoCambio", "render": tiposMarcados, "width": "11%" },
					{ "data": "responsableSolucion", "render": etiqueta(SOLICITANTES), "width": "10%" },
					{ "data": "detalleResponsableSolucion", "visible": false },
					{ "data": "justificacion", "visible": false },
					{ "data": "descripcion", "render": texto, "width": "13%" },
					{ "data": "incidenciaAlcance", "visible": false },
					{ "data": "tiempoCronograma", "visible": false },
					{ "data": "tiempoCronogramaAfectado", "visible": false },
					{ "data": "incidenciaCronograma", "visible": false },
					{ "data": "valorPresupuesto", "visible": false },
					{ "data": "costoDirecto", "render": moneda, "width": "9%" },
					{ "data": "costoDirectoAIU", "visible": false },
					{ "data": "costoDirectoAIUIVA", "visible": false },
					{ "data": "valorAprobado", "render": moneda, "width": "9%" },
					{ "data": "incidenciaPresupuesto", "visible": false },
					{ "data": "incidenciaCalidad", "visible": false },
					{ "data": "incidenciaRiesgo", "visible": false },
					{ "data": "fechaTentativaDefinicion", "render": texto, "width": "8%" },
					{ "data": "fechaEntregaInterventoria", "render": texto, "width": "10%" },
					{ "data": "Observaciones", "visible": false },
					{ "data": "fechaDefinicion", "render": texto, "width": "8%" },
					{ "data": "aprobacion", "render": etiqueta(APROBACIONES), "width": "10%" },
					{ "data": "soportes", "visible": false }
				],
				"language": idioma_espanol
			});

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');
			conectarFiltros(table);
			abrirOrdenAlHacerClick(table);
			return table;
		}

		/* La fila de filtros existia en el HTML desde el principio, sin nada al otro lado. */
		var conectarFiltros = function(table) {
			var filtros = {
				2: "#buscadorSolicitanteCambio",
				4: "#buscadorFechaSolicitud",
				5: "#buscadorPrioridad",
				6: "#buscadorTipoCambio",
				7: "#buscadorResponsableDefinicion",
				16: "#buscadorCostoDirecto",
				19: "#buscadorValorAprobado",
				23: "#buscadorFechaTentativaDefinicion",
				24: "#buscadorFechaEntregaInterventoria",
				26: "#buscadorFechaDefinicion",
				27: "#buscadorAprobacion"
			};
			Object.keys(filtros).forEach(function(indice) {
				var $campo = $(filtros[indice]);
				if (!$campo.length) return;
				$campo.off('.ccFiltro').on('input.ccFiltro change.ccFiltro', function() {
					table.column(Number(indice)).search(this.value).draw();
				});
			});
		}

		var abrirOrdenAlHacerClick = function(table) {
			$("#dt_cliente tbody").off('click.ccOrden').on('click.ccOrden', 'td', function() {
				var data = table.row($(this).parents('tr')).data();
				if (data) abrirOrdenDeCambio(data);
			});
		}

		var marcarOpcion = function(nombre, valor) {
			$("input[name='" + nombre + "']").prop("checked", false);
			if (valor !== null && valor !== "") {
				$("input[name='" + nombre + "'][value='" + valor + "']").prop("checked", true);
			}
		}

		/*
		 * La orden se abre en consulta. El formulario de edicion (guardar, PDF, soportes) todavia no
		 * existe en esta pantalla — es H-38 — y dejar botones que llaman a funciones inexistentes es
		 * justo el defecto que este cambio corrige, asi que se ocultan mientras tanto.
		 */
		var abrirOrdenDeCambio = function(data) {
			$("#Id").val(data.id);
			$("#inputConsecutivo").val(data.id);
			$("#inputProyecto").val($("#proyecto").val() || "");
			$("#inputFechaSolicitud").val(data.fechaSolicitud || "");

			marcarOpcion("inputSolicitanteCambio", data.solicitanteCambio);
			marcarOpcion("inputPrioridad", data.prioridad);
			marcarOpcion("inputResponsableSolucion", data.responsableSolucion);
			marcarOpcion("inputAprobacion", data.aprobacion);

			$("#inputDetalleSolicitanteOtro").val(data.detalleSolicitanteOtro || "");
			$("#inputDetalleResponsableSolucion").val(data.detalleResponsableSolucion || "");

			var tipos = {};
			try { tipos = (JSON.parse(data.tipoCambio || "{}").tiposCambio) || {}; } catch (e) { tipos = {}; }
			["Alcance", "Cronograma", "Costo", "Calidad", "Riesgo", "Recurso"].forEach(function(t) {
				$("#inputTipoCambio" + t).prop("checked", !!tipos[t] && tipos[t] !== "0");
			});

			var campos = {
				"#inputJustificacion": data.justificacion,
				"#inputDescripcion": data.descripcion,
				"#inputIncidenciaAlcance": data.incidenciaAlcance,
				"#inputTiempoCronograma": data.tiempoCronograma,
				"#inputTiempoCronogramaAfectado": data.tiempoCronogramaAfectado,
				"#inputIncidenciaCronograma": data.incidenciaCronograma,
				"#inputValorPresupuesto": moneda(data.valorPresupuesto),
				"#inputCostoDirecto": moneda(data.costoDirecto),
				"#inputCostoDirectoAIU": moneda(data.costoDirectoAIU),
				"#inputCostoDirectoAIUIVA": moneda(data.costoDirectoAIUIVA),
				"#inputValorAprobado": moneda(data.valorAprobado),
				"#inputIncidenciaPresupuesto": data.incidenciaPresupuesto,
				"#inputIncidenciaCalidad": data.incidenciaCalidad,
				"#inputIncidenciaRiesgo": data.incidenciaRiesgo,
				"#inputIncidenciaRecurso": data.incidenciaRecurso,
				"#inputFechaEntregaInterventoria": data.fechaEntregaInterventoria,
				"#inputFechaTentativaDefinicion": data.fechaTentativaDefinicion,
				"#inputFechaDefinicion": data.fechaDefinicion
			};
			Object.keys(campos).forEach(function(sel) {
				$(sel).val(campos[sel] == null ? "" : campos[sel]);
			});

			$("#modalordenDeCambio").find("input, textarea, select").prop("disabled", true);
			$("#btn_guardarOrden, #btn_generarPDFOrden, .formOrdenCambio button[onclick^='agregarSoporte']").hide();
			$("#modalordenDeCambio .mensaje").text("Consulta de la orden de cambio. La edición aún no está disponible en esta pantalla.");
			$("#modalordenDeCambio").modal("show");
		}

		var cerrarTodosModales = function() {
			$(".modal").modal("hide");
		}

		var recargarTabla = function(opcion) {
			var table = $.fn.DataTable.isDataTable("#dt_cliente") ? $("#dt_cliente").DataTable() : null;
			if (!table || opcion === "listar") {
				listar();
				return;
			}
			table.ajax.reload(null, false);
		}
	</script>

</body>
</html>
