<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
	<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<!-- Los <link> crudos a handsontable.full.min.css y handsontable-header-global.css
	     se retiraron al migrar el head: attach-handsontable.css importa las dos con
	     layer(vendor), mas handsontable-module.css y los adaptadores. El link crudo
	     era la segunda carga del vendor que documenta unlayered-delivery-inventory.json
	     como caso "2-handsontable-doble-carga", y entraba sin capa. -->
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script>window.__AIA_HANDSONTABLE_ONLY__ = true;</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderForModule('pdc') ?>
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation7" charset="utf-8"></script>
	<link rel="stylesheet" href="/css/pdc.css?v=20260711handsontableMobile10" />
</head>

<!--Etiqueta superior-->
<body class="pdc-page aia-shell aia-shell--sidebar">
	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="planCompras" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="modificar" aria-hidden="true">
		<input type="hidden" id="nombrePaqueteContratacion" name="nombrePaqueteContratacion" value="" aria-hidden="true">
		<input type="hidden" id="tipoPaquete" name="tipoPaquete" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100">
			<div id="pdc-hot-shell" class="pdc-hot-wrapper w-100">
				<div class="row filaBotones mb-2 align-items-center">
					<div class="col-auto mr-auto pl-0"><div class="toolbarAcciones"></div></div>
					<div class="col-auto px-1"><?= \App\View\Components\BiAccessComponent::renderLink('pdc', 'BI PDC') ?></div>
					<div class="col-auto ml-auto pr-0"><div class="toolbarNavegacion"></div></div>
				</div>
				<div class="row mt-2">
					<div class="col-12 p-0"><div class="toolbarFilaMensajes d-flex align-items-center"></div></div>
				</div>
				<div class="pdc-legend-wrap">
					<div class="pdc-legend">
						<span class="pdc-legend-item missing" onclick="filterPDC('missing', event)"><span class="indicator"></span> Informacion pendiente <span id="count-missing" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item critical" onclick="filterPDC('critical', event)"><span class="indicator"></span> Inicio de contratacion vencido <span id="count-critical" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item delayed" onclick="filterPDC('delayed', event)"><span class="indicator"></span> Contratacion atrasada <span id="count-delayed" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item completed-late" onclick="filterPDC('completed-late', event)"><span class="indicator"></span> Contratacion cerrada tarde <span id="count-completed-late" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item completed-ontime" onclick="filterPDC('completed-ontime', event)"><span class="indicator"></span> Contratacion cerrada a tiempo <span id="count-completed-ontime" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item active" onclick="filterPDC('active', event)"><span class="indicator"></span> Contratacion en curso <span id="count-active" class="count-badge">(...)</span></span>
						<span class="pdc-legend-item not-started" onclick="filterPDC('not-started', event)"><span class="indicator"></span> Contratacion pendiente de inicio <span id="count-not-started" class="count-badge">(...)</span></span>
					</div>
				</div>
				<div id="dt_cliente" class="pdc-hot-grid w-100"></div>
			</div>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<div class="modal_Contrato modal aia-modal" id="modalContrato" tabindex="-1" role="dialog" aria-labelledby="modal_ContratoLabel">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalContratoLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<div class="modal-body-texto-Contrato mb-0" id="modal-body-texto-Contrato"></div>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="listar()"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form id="formularioContrato" class="form form-horizontal pdc-contract-form" action="" method="POST">
									<section class="parametro_Contrato pdc-contract-section">
										<div class="pdc-contract-section__header">
											<div>
												<h3 class="pdc-contract-section__title">Descripcion del Proceso</h3>
												<p class="pdc-contract-section__hint">Contexto del paquete, fechas de referencia y presupuesto base del proceso.</p>
											</div>
										</div>
										<div class="pdc-contract-section__body">
											<div class="col-sm-12 pdc-modal-row">
												<label for="actividadesDelContrato" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Familias asociadas a este paquete de contratacion:</span>
												</label>
												<div id='divActividadesDelContrato' name='divActividadesDelContrato' class='pdc-modal-value'><textarea id="actividadesDelContrato" name="actividadesDelContrato" class="form-control" readonly></textarea>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="fechaActual" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Fecha de Corte:</span>
												</label>
												<div id='divFechaActual' name='divFechaActual' class='pdc-modal-value pdc-modal-value--narrow'><input id='fechaActual' name='fechaActual' class='form-control text-center' type='text' value='' placeholder='Fecha de Corte' autocomplete="off" readonly>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="fechaInicioContrato" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Inicio en obra segun cronograma:</span>
												</label>
												<div id='divFechaInicioContrato' name='divFechaInicioContrato' class='pdc-modal-value pdc-modal-value--narrow'><input id='fechaInicioContrato' name='fechaInicioContrato' class='form-control text-center' type='text' value='' placeholder='Fecha de Inicio' autocomplete="off" readonly>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="valorPresupuesto" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Valor en presupuesto de la familia:</span>
												</label>
												<div id='divValorPresupuesto' name='divValorPresupuesto' class='pdc-modal-value pdc-modal-value--narrow'><input id='valorPresupuesto' name='valorPresupuesto' class='form-control text-center' type='text' value='' placeholder='Valor en Pesos Colombianos' autocomplete="off" data-type="currency">
												</div>
											</div>
										</div>
									</section>
									<section class="parametro_Contrato pdc-contract-section">
										<div class="pdc-contract-section__header">
											<div>
												<h3 class="pdc-contract-section__title">Proceso de Contratacion</h3>
												<p class="pdc-contract-section__hint">Seguimiento por etapa con duracion, fecha teorica, proyectada y real.</p>
											</div>
										</div>
										<div class="pdc-contract-section__body pdc-process-grid">
											<div class="filaEncabezado">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold"></span>
												</div>
												<div class="labelFilaEncabezado">
													<span class="h6 font-weight-bold">Duración (Días Calendario)</span>
												</div>
												<div class="labelFilaEncabezado">
													<span class="h6 font-weight-bold">Fecha Inicio Teórica</span>
												</div>
												<div class="labelFilaEncabezado">
													<span class="h6 font-weight-bold">Fecha Inicio Proyectada</span>
												</div>
												<div class="labelFilaEncabezado">
													<span class="h6 font-weight-bold">Fecha Inicio Real</span>
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">1. Elaboración de pliegos, selección de proveedores e invitaciones a cotizar:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasElaboracionPliegos' name='diasElaboracionPliegos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaElaboracionPliegosTeorica" class='fas fa-lg'></i>
													<input id='fechaElaboracionPliegosTeorica' name='fechaElaboracionPliegosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaElaboracionPliegos" class='fas fa-lg'></i>
													<input id='fechaElaboracionPliegos' name='fechaElaboracionPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealElaboracionPliegos" class='fas fa-lg'></i>
													<input id='fechaRealElaboracionPliegos' name='fechaRealElaboracionPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('ElaboracionPliegos');" onkeyup="calcularProcesoContratacionTeorico('ElaboracionPliegos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">2. Entrega de pliegos y/o carta. Elaboración de propuesta:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasEntregaPliegos' name='diasEntregaPliegos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaEntregaPliegosTeorica" class='fas fa-lg'></i>
													<input id='fechaEntregaPliegosTeorica' name='fechaEntregaPliegosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaEntregaPliegos" class='fas fa-lg'></i>
													<input id='fechaEntregaPliegos' name='fechaEntregaPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealEntregaPliegos" class='fas fa-lg'></i>
													<input id='fechaRealEntregaPliegos' name='fechaRealEntregaPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('EntregaPliegos');" onkeyup="calcularProcesoContratacionTeorico('EntregaPliegos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">3. Recibo de propuestas:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasReciboPropuestas' name='diasReciboPropuestas' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaReciboPropuestasTeorica" class='fas fa-lg'></i>
													<input id='fechaReciboPropuestasTeorica' name='fechaReciboPropuestasTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaReciboPropuestas" class='fas fa-lg'></i>
													<input id='fechaReciboPropuestas' name='fechaReciboPropuestas' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealReciboPropuestas" class='fas fa-lg'></i>
													<input id='fechaRealReciboPropuestas' name='fechaRealReciboPropuestas' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('ReciboPropuestas');" onkeyup="calcularProcesoContratacionTeorico('ReciboPropuestas');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">4. Cuadros comparativos, análisis y adjudicación:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasCuadrosComparativos' name='diasCuadrosComparativos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaCuadrosComparativosTeorica" class='fas fa-lg'></i>
													<input id='fechaCuadrosComparativosTeorica' name='fechaCuadrosComparativosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaCuadrosComparativos" class='fas fa-lg'></i>
													<input id='fechaCuadrosComparativos' name='fechaCuadrosComparativos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealCuadrosComparativos" class='fas fa-lg'></i>
													<input id='fechaRealCuadrosComparativos' name='fechaRealCuadrosComparativos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('CuadrosComparativos');" onkeyup="calcularProcesoContratacionTeorico('CuadrosComparativos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">5. Legalización del contrato:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasLegalizacionContrato' name='diasLegalizacionContrato' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaLegalizacionContratoTeorica" class='fas fa-lg'></i>
													<input id='fechaLegalizacionContratoTeorica' name='fechaLegalizacionContratoTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaLegalizacionContrato" class='fas fa-lg'></i>
													<input id='fechaLegalizacionContrato' name='fechaLegalizacionContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealLegalizacionContrato" class='fas fa-lg'></i>
													<input id='fechaRealLegalizacionContrato' name='fechaRealLegalizacionContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('LegalizacionContrato');" onkeyup="calcularProcesoContratacionTeorico('LegalizacionContrato');">
												</div>
											</div>
											<div class="container informacionAdjudicacionProveedor" style="display:none">
												<input type="hidden" id="activoInformacionAdjudicacionProveedor" name="activoInformacionAdjudicacionProveedor" value="0">
												<input type="hidden" id="idProveedorExistente" name="idProveedorExistente" value="0">
												<div class="row pdc-row-center">
													<h5><b>Información del Proveedor Adjudicado</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="nitAdjudicado" class="labelFormularioAdjudicado">NIT (sin puntos o guiones)</label>
														<input type="number" step="1" class="form-control" id="nitAdjudicado" name="nitAdjudicado" placeholder="NIT" minlenght="9" maxlength="9" onchange="verificarProveedor('nitAdjudicado')" onkeyup="quitar_guion()">
													</div>
													<div class="form-group col">
														<label for="subcontratistaAdjudicado" class="labelFormularioAdjudicado">Nombre del Proveedor</label>
														<input type="text" class="form-control" id="subcontratistaAdjudicado" name="subcontratistaAdjudicado" placeholder="Nombre del Proveedor">
														<small class="pdc-provider-lock-badge" id="lockBadgeSubcontratistaAdjudicado" hidden>Proveedor registrado</small>
													</div>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="correoAdjudicado" class="labelFormularioAdjudicado">Correo de Contacto</label>
														<input type="email" class="form-control" id="correoAdjudicado" name="correoAdjudicado" placeholder="Correo de Contacto">
														<small class="pdc-provider-lock-badge" id="lockBadgeCorreoAdjudicado" hidden>Proveedor registrado</small>
													</div>
													<div class="form-group col">
														<label for="tipoProveedorAdjudicado" class="labelFormularioAdjudicado">Tipo de Proveedor</label>
														<input type="text" class="form-control" id="tipoProveedorAdjudicado" name="tipoProveedorAdjudicado" placeholder="Tipo de Proveedor" readonly>
														<small class="pdc-provider-lock-badge" id="lockBadgeTipoProveedorAdjudicado" hidden>Autocompletado</small>
													</div>
												</div>
								<div class="row">
									<p class="mensajeModalInformacionAdjudicado pdc-inline-msg" id="mensajeModalInformacionAdjudicado"></p>
								</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">6. Periodo de fabricación, producción, importaciones, transportes, movilización, etc:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasFabricacion' name='diasFabricacion' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaFabricacionTeorica" class='fas fa-lg'></i>
													<input id='fechaFabricacionTeorica' name='fechaFabricacionTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaFabricacion" class='fas fa-lg'></i>
													<input id='fechaFabricacion' name='fechaFabricacion' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealFabricacion" class='fas fa-lg'></i>
													<input id='fechaRealFabricacion' name='fechaRealFabricacion' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('Fabricacion');" onkeyup="calcularProcesoContratacionTeorico('Fabricacion');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">7. Anticipación de insumos en obra:</span>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasInsumosObra' name='diasInsumosObra' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInsumosObraTeorica" class='fas fa-lg'></i>
													<input id='fechaInsumosObraTeorica' name='fechaInsumosObraTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInsumosObra" class='fas fa-lg'></i>
													<input id='fechaInsumosObra' name='fechaInsumosObra' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealInsumosObra" class='fas fa-lg'></i>
													<input id='fechaRealInsumosObra' name='fechaRealInsumosObra' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('InsumosObra');" onkeyup="calcularProcesoContratacionTeorico('InsumosObra');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<span class="h6 font-weight-bold">8. Inicio en obra:</span>
												</div>
												<div class="inputFormularioContratos pdc-bg-muted">
													<input id='diasInicioProyectadaContrato' name='diasInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInicioProyectadaContratoTeorica" class='fas fa-lg'></i>
													<input id='fechaInicioProyectadaContratoTeorica' name='fechaInicioProyectadaContratoTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInicioProyectadaContrato" class='fas fa-lg'></i>
													<input id='fechaInicioProyectadaContrato' name='fechaInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealInicioProyectadaContrato" class='fas fa-lg'></i>
													<input id='fechaRealInicioProyectadaContrato' name='fechaRealInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('InicioProyectadaContrato');" onkeyup="calcularProcesoContratacionTeorico('InicioProyectadaContrato');">
												</div>
											</div>
										</div>
									</section>
									<section class="parametro_Contrato pdc-contract-section">
										<div class="pdc-contract-section__header">
											<div>
												<h3 class="pdc-contract-section__title">Diagnostico del Proceso</h3>
												<p class="pdc-contract-section__hint">Lectura rapida del estado actual frente al cronograma definido.</p>
											</div>
										</div>
										<div class="pdc-contract-section__body">
											<div class="col-sm-12 pdc-modal-row">
												<label for="estadoProceso" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Estado del Proceso:</span>
												</label>
												<div id='divEstadoProceso' name='divEstadoProceso' class='pdc-modal-field'>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="divDeberiaProceso" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">¿Donde Debería Ir Según el Cronograma?:</span>
												</label>
												<div id='divDeberiaProceso' name='divDeberiaProceso' class='pdc-modal-field'>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="divDiagnostico" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Diagnóstico:</span>
												</label>
												<div id='divDiagnostico' name='divDiagnostico' class='pdc-modal-field'>
												</div>
											</div>
											<input type="hidden" id="estadoProceso" name="estadoProceso" value="">
										</div>
									</section>
									<section class="parametro_Contrato pdc-contract-section" id="seccionSeguimientoContrato" style="display:none">
										<div class="pdc-contract-section__header">
											<div>
												<h3 class="pdc-contract-section__title">Seguimiento al Contrato</h3>
												<p class="pdc-contract-section__hint">Datos del proveedor adjudicado, contrato formalizado y devoluciones asociadas.</p>
											</div>
										</div>
										<div class="pdc-contract-section__body">
											<div class="container informacionAdjudicacionContrato" style="display:none">
												<input type="hidden" id="activoInformacionAdjudicacionContrato" name="activoInformacionAdjudicacionContrato" value="0">
												<div class="row pdc-row-center">
													<h5><b>Información del Contrato Adjudicado</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="numeroContrato" class="labelFormularioAdjudicado">Número del Contrato</label>
														<input type="text" step="1" class="form-control" id="numeroContrato" name="numeroContrato" placeholder="Numero del Contrato">
													</div>
													<div class="form-group col">
														<label for="fechaVencimientoPolizas" class="labelFormularioAdjudicado">Fecha de Vencimiento de Pólizas de Cumplimiento</label>
														<div class="row">
															<div class="form-group col-4">
																<select class="form-control" id="aplicaPolizas" name="aplicaPolizas" onchange="bloqueoFechaVencimientoPolizas()">
																	<option value=1 selected>Aplica</option>
																	<option value=0>No Aplica</option>
																</select>
															</div>
															<div class="form-group col-8">
																<input type="text" class="form-control" id="fechaVencimientoPolizas" name="fechaVencimientoPolizas" placeholder="Fecha de Vencimiento de Pólizas">
															</div>
														</div>

													</div>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="valorPrimeraNegociacion" class="labelFormularioAdjudicado">Valor Primera Negociación</label>
														<input type="text" class="form-control" id="valorPrimeraNegociacion" name="valorPrimeraNegociacion" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorAdjudicado" class="labelFormularioAdjudicado">Valor Adjudicado</label>
														<input type="text" class="form-control" id="valorAdjudicado" name="valorAdjudicado" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
												</div>
												<div class="row">
												<div class="form-group col">
														<label for="valorAnticipo" class="labelFormularioAdjudicado">Valor Anticipo</label>
														<input type="text" class="form-control" id="valorAnticipo" name="valorAnticipo" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorAhorroPerdida" class="labelFormularioSeguimientoContrato">(+) Ahorro / (-) Pérdida</label>
														<input type="text" class="form-control" id="valorAhorroPerdida" name="valorAhorroPerdida" value="" data-type="currency" placeholder="Valor en Pesos Colombianos" readonly disabled>
													</div>
												</div>
								<div class="row">
									<p class="mensajeModalInformacionContrato pdc-inline-msg" id="mensajeModalInformacionContrato"></p>
								</div>
											</div>
											<div class="container seguimientoContrato" style="display:none">
												<input type="hidden" id="activoSeguimientoContrato" name="activoSeguimientoContrato" value="0">
												<div class="row pdc-row-center">
													<h5><b>Devoluciones al Proveedor</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="valorReclamado" class="labelFormularioSeguimientoContrato">Valor en Reclamación</label>
														<input type="text" class="form-control" id="valorReclamado" name="valorReclamado" value="0" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorDevoluciones" class="labelFormularioSeguimientoContrato">Valor de Devoluciones al Proveedor</label>
														<input type="text" class="form-control" id="valorDevoluciones" name="valorDevoluciones" value="0" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="tasaDevoluciones" class="labelFormularioSeguimientoContrato">Tasa de Devoluciones al Proveedor</label>
														<input type="text" class="form-control" id="tasaDevoluciones" name="tasaDevoluciones" value="" readonly disabled>
													</div>
												</div>
												<div class="row">
													<p class="mensajeModalSeguimientoContrato pdc-inline-msg" id="mensajeModalSeguimientoContrato"></p>
												</div>
											</div>
										</div>
									</section>
									<section class="parametro_Contrato pdc-contract-section w-100">
										<div class="pdc-contract-section__header">
											<div>
												<h3 class="pdc-contract-section__title">Observaciones</h3>
												<p class="pdc-contract-section__hint">Notas complementarias para dejar trazabilidad del proceso de contratacion.</p>
											</div>
										</div>
										<div class="pdc-contract-section__body">
											<div class="col-sm-12"><textarea id="observacionesContrato" name="observacionesContrato" class="form-control"></textarea></div>
										</div>
									</section>
									<div class="pdc-contract-actions">
										<div class="pdc-contract-actions__buttons">
											<input id="btn_guardar_pdc" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar Plan de Compras y Contrataciones">
											<input id="btn_cancelar_editar" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar" aria-label="Cancelar edición">
										</div>
										<div>
											<p class="mensajeModalContrato pdc-inline-msg" id="mensajeModalContrato"></p>
											<p class="pdc-contract-note">Guarda los cambios cuando completes el seguimiento del proceso.</p>
										</div>
									</div>
								</form>
								<!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
								<div class="col-sm-offset-2 col-sm-8">
									<p class="mensaje"></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Modal -->

		<div class="modal_DefinirContrato modal aia-modal" id="modalDefinirContratos" tabindex="-1" role="dialog" aria-labelledby="modal_DefinirContratoLabel">
			<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalDefinirContratosLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h2 class="aia-modal__headline modal-body-texto-DefinirContrato" id="modal-body-texto-DefinirContrato">Definir cantidades</h2>
							<p class="aia-modal__subtitle">Configura la cantidad de contratos asociados a cada paquete antes de guardar.</p>
						</div>
						<button type="button" class="close" aria-label="Close" onClick="window.PdcHotModule && window.PdcHotModule.closeDefinirContratos()"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadroDefinirContratos" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form id="formularioDefinirContrato" class="form form-horizontal aia-modal__form" action="" method="POST">
									<section class="form-group parametro_Contrato aia-modal__section">
										<div class="aia-modal__section-header">
											<h3 class="aia-modal__section-title">Distribucion de cantidades</h3>
											<p class="aia-modal__hint">Ajusta la estructura por modalidad de contratacion y conserva el orden visual del paquete.</p>
										</div>
										<div id="dt_definirContratos_wrapper" class="pdc-hot-wrapper w-100">
											<div id="dt_definirContratos" class="pdc-hot-grid w-100"></div>
										</div>
									</section>
									<!--Se crean los botones Guardar y Listar-->
									<div class="form-group aia-modal__actions">
										<div class="col-sm-offset-2 col-sm-12 aia-modal__buttons">
											<input id="btn_guardar_definirContratos" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar cantidades">
											<input id="btn_cancelar_definirContratos" type="button" class="btn btn-danger" value="Cancelar" aria-label="Cancelar cantidades">
										</div>
										<p class="mensajeModalDefinirContrato pdc-inline-msg aia-modal__message" id="mensajeModalDefinirContrato"></p>
									</div>
								</form>
							</div>
						</div>
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
		          <h2 class="aia-modal__title">Eliminar paquete</h2>
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
		<script>
			$(document).ajaxSend(function (_event, xhr, settings) {
				var url = String((settings && settings.url) || '');
				if (url.indexOf('/api/pdc/') === 0) {
					xhr.setRequestHeader('X-CSRF-Token', $('meta[name="csrf-token"]').attr('content') || '');
				}
			});
		</script>
	<!-- Iniciar Popper-->
	<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
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
	<!--Formatos de números-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<?= \App\View\Components\BiAccessComponent::renderBootConfig('pdc') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=20260708theme" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/semi_auto_review.js?v=20260709-contract-pills1" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		var autoSyncPdcOnLoad = <?php echo !empty($autoSyncPdcOnLoad) ? 'true' : 'false'; ?>;
		var pdcSyncOrigin = <?php echo json_encode($pdcSyncOrigin ?? '', JSON_UNESCAPED_UNICODE); ?>;

		/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function() {
		  $("#formulario_nuevo").hide();
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
		});

		var obtenerSemanaPdc = function() {
			var semanaActual = parseInt(document.getElementById('semana').value, 10) || 0;
			var maxSemana = parseInt(document.getElementById('Max_Semana').value, 10) || 0;

			return semanaActual > 0 ? semanaActual : maxSemana;
		}

		var iniciarAutoActualizacionPdc = function() {
			if (!autoSyncPdcOnLoad) {
				return false;
			}

			autoSyncPdcOnLoad = false;
			actualizarPDC({
				onError: function() {
					listar();
					eliminar();
				}
			});

			return true;
		}

		var cargaParametros = function() {
			$('#S1,#S2,#S3,#S4,#S5,#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5,#MO1,#MO2,#MO3,#MO4,#MO5,#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5,#SI1,#SI2,#SI3,#SI4,#SI5,#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5').select2({tags: true, selectOnClose: true, allowClear: true});
			if (iniciarAutoActualizacionPdc()) {
				return;
			}
			listar();
			eliminar();
		}

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  listar();
		  limpiar_datos();
		});

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_cancelar").on("click", function() {
		  location.reload();
		});

		var actualizarEstadoProveedorBloqueado = function(bloqueado) {
			var campos = [
				document.getElementById('subcontratistaAdjudicado'),
				document.getElementById('correoAdjudicado'),
				document.getElementById('tipoProveedorAdjudicado')
			];
			var badges = [
				document.getElementById('lockBadgeSubcontratistaAdjudicado'),
				document.getElementById('lockBadgeCorreoAdjudicado'),
				document.getElementById('lockBadgeTipoProveedorAdjudicado')
			];

			campos.forEach(function(campo) {
				if (!campo) {
					return;
				}

				campo.classList.toggle('pdc-provider-locked', !!bloqueado);
				campo.setAttribute('aria-readonly', bloqueado ? 'true' : 'false');
			});

			badges.forEach(function(badge) {
				if (!badge) {
					return;
				}

				badge.hidden = !bloqueado;
			});
		}

		/*Verificar si un proveedor ya existe en la base de datos y si existe traer la información al formulario del contrato*/
		var verificarProveedor = function(base) {
		  var nitAdjudicado = document.getElementById('nitAdjudicado'),
		    subcontratistaAdjudicado = document.getElementById('subcontratistaAdjudicado'),
		    correoAdjudicado = document.getElementById('correoAdjudicado'),
		    actividadesDelContrato = document.getElementById('actividadesDelContrato'),
		    tipoPaquete = document.getElementById('tipoPaquete'),
		    idProveedorExistente = document.getElementById('idProveedorExistente'),
		    opcion = "verificarProveedor",
		    db = document.getElementById('baseDatos').value;
		  $.ajax({
		    method: "POST",
		    url: "/api/pdc/save",
		    contenttype: "charset=utf-8",
		    data: {
		      "idProveedorExistente": idProveedorExistente.value,
		      "nitAdjudicado": nitAdjudicado.value,
		      "subcontratistaAdjudicado": subcontratistaAdjudicado.value,
		      "correoAdjudicado": correoAdjudicado.value,
		      "actividadesDelContrato": actividadesDelContrato.value,
		      "tipoPaquete": tipoPaquete.value,
		      "opcion": opcion,
		      "db": db,
		      "base": base
		    }
		  }).done(function(info) {
		    var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
		    //console.log(json_info);
			    if (json_info == "No Existe") {
			      idProveedorExistente.value = '';
			      subcontratistaAdjudicado.value = '';
			      correoAdjudicado.value = '';
			      subcontratistaAdjudicado.removeAttribute("readonly", true);
			      correoAdjudicado.removeAttribute("readonly", true);
			      actualizarEstadoProveedorBloqueado(false);
			      $("#mensajeModalInformacionAdjudicado").removeClass('pdc-message-error pdc-message-neutral').addClass('pdc-message-success');
			      document.getElementById('mensajeModalInformacionAdjudicado').innerHTML = "Se creará un nuevo proveedor en la base de datos";
			    } else {
			      nitAdjudicado.value = json_info['data'][0]['NIT'];
			      subcontratistaAdjudicado.value = json_info['data'][0]['subcontratista'];
			      correoAdjudicado.value = json_info['data'][0]['correo_contacto'];
			      idProveedorExistente.value = json_info['data'][0]['Id'];
			      subcontratistaAdjudicado.setAttribute("readonly", true);
			      correoAdjudicado.setAttribute("readonly", true);
			      actualizarEstadoProveedorBloqueado(true);
			      $("#mensajeModalInformacionAdjudicado").removeClass('pdc-message-error pdc-message-neutral').addClass('pdc-message-success');
			      document.getElementById('mensajeModalInformacionAdjudicado').innerHTML = "Se ha encontrado un proveedor registrado en la base de datos";
		      // $("#mensajeModalInformacionAdjudicado").fadeOut(10000, function(){
		      // 	$(this).html("");
		      // 	$(this).fadeIn(3000);
		      // });
		    }
		  });
		}

		/*Funciones que configuran en tiempo real el estado del proyecto y lo muestran en pantalla*/
		var calcularProcesoContratacionTeorico = function(inputCambiado) {
		  var fechaInicioContrato = document.getElementById('fechaInicioContrato').value,
		    fechaActual = document.getElementById('fechaActual').value,
		    fechaRealInicioProyectadaContrato = null,
		    diasInsumosObra = parseInt(document.getElementById('diasInsumosObra').value),
		    fechaRealInsumosObra = null,
		    diasFabricacion = parseInt(document.getElementById('diasFabricacion').value),
		    fechaRealFabricacion = null,
		    diasLegalizacionContrato = parseInt(document.getElementById('diasLegalizacionContrato').value),
		    fechaRealLegalizacionContrato = null,
		    diasCuadrosComparativos = parseInt(document.getElementById('diasCuadrosComparativos').value),
		    fechaRealCuadrosComparativos = null,
		    diasReciboPropuestas = parseInt(document.getElementById('diasReciboPropuestas').value),
		    fechaRealReciboPropuestas = null,
		    diasEntregaPliegos = parseInt(document.getElementById('diasEntregaPliegos').value),
		    fechaRealEntregaPliegos = null,
		    diasElaboracionPliegos = parseInt(document.getElementById('diasElaboracionPliegos').value),
		    fechaRealElaboracionPliegos = "No Aplica";
		  if (isNaN(diasInsumosObra)) {
		    diasInsumosObra = 0;
		  }
		  if (isNaN(diasFabricacion)) {
		    diasFabricacion = 0;
		  }
		  if (isNaN(diasLegalizacionContrato)) {
		    diasLegalizacionContrato = 0;
		  }
		  if (isNaN(diasCuadrosComparativos)) {
		    diasCuadrosComparativos = 0;
		  }
		  if (isNaN(diasReciboPropuestas)) {
		    diasReciboPropuestas = 0;
		  }
		  if (isNaN(diasEntregaPliegos)) {
		    diasEntregaPliegos = 0;
		  }
		  if (isNaN(diasElaboracionPliegos)) {
		    diasElaboracionPliegos = 0;
		  }
		  var diasTotales = diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasElaboracionPliegos;
		  var opcion = "recalcularProcesoContratacion";
		  db = document.getElementById('baseDatos').value;
		  $.ajax({
		    method: "POST",
		    url: "/api/pdc/save",
		    contenttype: "charset=utf-8",
		    data: {
		      "db": db,
		      "opcion": opcion,
		      "fechaInicioContrato": fechaInicioContrato,
		      "fechaActual": fechaActual,
		      "fechaRealInicioProyectadaContrato": fechaRealInicioProyectadaContrato,
		      "diasTotales": diasTotales,
		      "diasInsumosObra": diasInsumosObra,
		      "diasFabricacion": diasFabricacion,
		      "diasLegalizacionContrato": diasLegalizacionContrato,
		      "diasCuadrosComparativos": diasCuadrosComparativos,
		      "diasReciboPropuestas": diasReciboPropuestas,
		      "diasEntregaPliegos": diasEntregaPliegos,
		      "diasElaboracionPliegos": diasElaboracionPliegos,
		      "fechaRealInsumosObra": fechaRealInsumosObra,
		      "fechaRealFabricacion": fechaRealFabricacion,
		      "fechaRealLegalizacionContrato": fechaRealLegalizacionContrato,
		      "fechaRealCuadrosComparativos": fechaRealCuadrosComparativos,
		      "fechaRealReciboPropuestas": fechaRealReciboPropuestas,
		      "fechaRealEntregaPliegos": fechaRealEntregaPliegos,
		      "fechaRealElaboracionPliegos": fechaRealElaboracionPliegos
		    }
		  }).done(function(info) {
		    var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
		    document.getElementById('fechaElaboracionPliegosTeorica').value = json_info["data"][0]["fechaElaboracionPliegos"];
		    document.getElementById('fechaEntregaPliegosTeorica').value = json_info["data"][0]["fechaEntregaPliegos"];
		    document.getElementById('fechaReciboPropuestasTeorica').value = json_info["data"][0]["fechaReciboPropuestas"];
		    document.getElementById('fechaCuadrosComparativosTeorica').value = json_info["data"][0]["fechaCuadrosComparativos"];
		    document.getElementById('fechaLegalizacionContratoTeorica').value = json_info["data"][0]["fechaLegalizacionContrato"];
		    document.getElementById('fechaFabricacionTeorica').value = json_info["data"][0]["fechaFabricacion"];
		    document.getElementById('fechaInsumosObraTeorica').value = json_info["data"][0]["fechaInsumosObra"];
		    document.getElementById('fechaInicioProyectadaContratoTeorica').value = json_info["data"][0]["fechaInicioProyectada"];
		    recalcularProcesoContratacion(inputCambiado);
		  });
		}

		var recalcularProcesoContratacion = function(inputCambiado) {
		  var fechaInicioContrato = document.getElementById('fechaInicioContrato').value,
		    fechaActual = document.getElementById('fechaActual').value,
		    fechaRealInicioProyectadaContrato = document.getElementById('fechaRealInicioProyectadaContrato').value,
		    diasInsumosObra = parseInt(document.getElementById('diasInsumosObra').value),
		    fechaRealInsumosObra = document.getElementById('fechaRealInsumosObra').value,
		    diasFabricacion = parseInt(document.getElementById('diasFabricacion').value),
		    fechaRealFabricacion = document.getElementById('fechaRealFabricacion').value,
		    diasLegalizacionContrato = parseInt(document.getElementById('diasLegalizacionContrato').value),
		    fechaRealLegalizacionContrato = document.getElementById('fechaRealLegalizacionContrato').value,
		    diasCuadrosComparativos = parseInt(document.getElementById('diasCuadrosComparativos').value),
		    fechaRealCuadrosComparativos = document.getElementById('fechaRealCuadrosComparativos').value,
		    diasReciboPropuestas = parseInt(document.getElementById('diasReciboPropuestas').value),
		    fechaRealReciboPropuestas = document.getElementById('fechaRealReciboPropuestas').value,
		    diasEntregaPliegos = parseInt(document.getElementById('diasEntregaPliegos').value),
		    fechaRealEntregaPliegos = document.getElementById('fechaRealEntregaPliegos').value,
		    diasElaboracionPliegos = parseInt(document.getElementById('diasElaboracionPliegos').value),
		    fechaRealElaboracionPliegos = document.getElementById('fechaRealElaboracionPliegos').value;


				var finSemanaActual = new Date(fechaActual);
			  finSemanaActual.setDate(finSemanaActual.getDate() + 7);
				var anio = finSemanaActual.getFullYear();
				var mes = (finSemanaActual.getMonth() + 1);
				mesConCero = (mes < 10) ? "0" + String(mes) : mes;
				var dia = finSemanaActual.getDate();
				diaConCero = (dia < 10) ? "0" + String(dia) : dia;
			  finSemanaActual = anio + "-" + mesConCero + "-" + diaConCero;

				//console.log(fechaActual, finSemanaActual);
		  if (true) {
		    if (isNaN(diasInsumosObra) || diasInsumosObra < 0) {
		      diasInsumosObra = 0;
		      document.getElementById('diasInsumosObra').value = "";
		    }
		    if (isNaN(diasFabricacion) || diasFabricacion < 0) {
		      diasFabricacion = 0;
		      document.getElementById('diasFabricacion').value = "";
		    }
		    if (isNaN(diasLegalizacionContrato) || diasLegalizacionContrato < 0) {
		      diasLegalizacionContrato = 0;
		      document.getElementById('diasLegalizacionContrato').value = "";
		    }
		    if (isNaN(diasCuadrosComparativos) || diasCuadrosComparativos < 0) {
		      diasCuadrosComparativos = 0;
		      document.getElementById('diasCuadrosComparativos').value = "";
		    }
		    if (isNaN(diasReciboPropuestas) || diasReciboPropuestas < 0) {
		      diasReciboPropuestas = 0;
		      document.getElementById('diasReciboPropuestas').value = "";
		    }
		    if (isNaN(diasEntregaPliegos) || diasEntregaPliegos < 0) {
		      diasEntregaPliegos = 0;
		      document.getElementById('diasEntregaPliegos').value = "";
		    }
		    if (isNaN(diasElaboracionPliegos) || diasElaboracionPliegos < 0) {
		      diasElaboracionPliegos = 0;
		      document.getElementById('diasElaboracionPliegos').value = "";
		    }
		    var diasTotales = diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasElaboracionPliegos;
		    var opcion = "recalcularProcesoContratacion";
		    db = document.getElementById('baseDatos').value;
		    $.ajax({
		      method: "POST",
		      url: "/api/pdc/save",
		      contenttype: "charset=utf-8",
		      data: {
		        "db": db,
		        "opcion": opcion,
		        "fechaInicioContrato": fechaInicioContrato,
		        "fechaActual": fechaActual,
		        "fechaRealInicioProyectadaContrato": fechaRealInicioProyectadaContrato,
		        "diasTotales": diasTotales,
		        "diasInsumosObra": diasInsumosObra,
		        "diasFabricacion": diasFabricacion,
		        "diasLegalizacionContrato": diasLegalizacionContrato,
		        "diasCuadrosComparativos": diasCuadrosComparativos,
		        "diasReciboPropuestas": diasReciboPropuestas,
		        "diasEntregaPliegos": diasEntregaPliegos,
		        "diasElaboracionPliegos": diasElaboracionPliegos,
		        "fechaRealInsumosObra": fechaRealInsumosObra,
		        "fechaRealFabricacion": fechaRealFabricacion,
		        "fechaRealLegalizacionContrato": fechaRealLegalizacionContrato,
		        "fechaRealCuadrosComparativos": fechaRealCuadrosComparativos,
		        "fechaRealReciboPropuestas": fechaRealReciboPropuestas,
		        "fechaRealEntregaPliegos": fechaRealEntregaPliegos,
		        "fechaRealElaboracionPliegos": fechaRealElaboracionPliegos
		      }
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
		      document.getElementById('fechaElaboracionPliegos').value = json_info["data"][0]["fechaElaboracionPliegos"];
		      document.getElementById('fechaEntregaPliegos').value = json_info["data"][0]["fechaEntregaPliegos"];
		      document.getElementById('fechaReciboPropuestas').value = json_info["data"][0]["fechaReciboPropuestas"];
		      document.getElementById('fechaCuadrosComparativos').value = json_info["data"][0]["fechaCuadrosComparativos"];
		      document.getElementById('fechaLegalizacionContrato').value = json_info["data"][0]["fechaLegalizacionContrato"];
		      document.getElementById('fechaFabricacion').value = json_info["data"][0]["fechaFabricacion"];
		      document.getElementById('fechaInsumosObra').value = json_info["data"][0]["fechaInsumosObra"];
		      document.getElementById('fechaInicioProyectadaContrato').value = json_info["data"][0]["fechaInicioProyectada"];
		      selectoresFecha("InsumosObra");
		      selectoresFecha("Fabricacion");
		      selectoresFecha("LegalizacionContrato");
		      selectoresFecha("CuadrosComparativos");
		      selectoresFecha("ReciboPropuestas");
		      selectoresFecha("EntregaPliegos");
		      selectoresFecha("ElaboracionPliegos");
		      selectoresFecha("InicioProyectadaContrato");
		      generarEstadoProceso();
		      mostrarInputsOcultos();
		    });
		  } else {
		    if (inputCambiado != "") {
		      if (window.AIA && window.AIA.Notice) window.AIA.Notice.warning("No se puede asignar una fecha mayor a la fecha de fin de la presente semana (" + finSemanaActual + ").");
		      document.getElementById('fechaReal' + inputCambiado).value = '';
		      selectoresFecha("InsumosObra");
		      selectoresFecha("Fabricacion");
		      selectoresFecha("LegalizacionContrato");
		      selectoresFecha("CuadrosComparativos");
		      selectoresFecha("ReciboPropuestas");
		      selectoresFecha("EntregaPliegos");
		      selectoresFecha("ElaboracionPliegos");
		      selectoresFecha("InicioProyectadaContrato");
		      generarEstadoProceso();
		      mostrarInputsOcultos();
		    }
		  }
		}

		var generarEstadoProceso = function() {
		  var divEstadoProceso = document.getElementById("divEstadoProceso");
		  var divDeberiaProceso = document.getElementById("divDeberiaProceso");
		  var divDiagnostico = document.getElementById("divDiagnostico");
		  var estadoProceso = document.getElementById("estadoProceso");
		  var fechaActual = document.getElementById("fechaActual");
		  var fechaInicioCronograma = document.getElementById("fechaInicioContrato");
		  var pasos = [
		    ["ElaboracionPliegos", "Elaborando pliegos del contrato"],
		    ["EntregaPliegos", "Entregando pliegos a los proveedores invitados"],
		    ["ReciboPropuestas", "Recibiendo propuestas de los proveedores invitados"],
		    ["CuadrosComparativos", "Elaborando cuadros comparativos, análisis y adjudicación del contrato"],
		    ["LegalizacionContrato", "En proceso de legalización del contrato"],
		    ["Fabricacion", "En periodo de fabricación, producción, importaciones, transportes, movilización, etc"],
		    ["InsumosObra", "En proceso de llegada de recursos, insumos y personal a la obra"],
		    ["InicioProyectadaContrato", "Proceso de contratación finalizado e inicio en obra registrado"]
		  ];
		  var posicion = -1;
		  var deberiaHoy = -1;
		  var fechaActual = document.getElementById("fechaActual").value;
		  var fechaEvaluar = "";
		  for (i = 0; i < 8; i++) {
		    if (document.getElementById("fechaReal" + pasos[i][0]).value != "") {
		      posicion = i;
		    }
		    var posicionDeberiaHoy = "fecha" + pasos[i][0] + "Teorica";
		    fechaEvaluar = document.getElementById(posicionDeberiaHoy).value;
		    if (fechaEvaluar <= fechaActual) {
		      deberiaHoy = i;
		    }
		  }
		  //console.log("Va en: " + posicion + "; Debería ir en: " + deberiaHoy);
		  if (posicion == -1) {
		    divEstadoProceso.innerHTML = "Proceso de contratación no iniciado";
		    if (deberiaHoy == -1) {
		      divDeberiaProceso.innerHTML = "";
		    } else {
		      divDeberiaProceso.innerHTML = pasos[deberiaHoy][1];
		    }
		  } else {
		    divEstadoProceso.innerHTML = pasos[posicion][1];
		    if (deberiaHoy == -1) {
		      divDeberiaProceso.innerHTML = "";
		    } else {
		      divDeberiaProceso.innerHTML = pasos[deberiaHoy][1];
		    }
		  }
			//console.log(posicion >= deberiaHoy);
		  var diagnosticoInterno = "";
		  if (posicion >= deberiaHoy) {
		    diagnosticoInterno = "En Curso";
		    divDiagnostico.innerHTML = displayPDCStatus(diagnosticoInterno);
				//console.log((new Date(document.getElementById("fechaReal" + pasos[posicion][0]).value) -  new Date(document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value))/(1000 * 3600 * 24));
		    // if ((posicion == -1 && deberiaHoy == -1)) {
		    //   divDiagnostico.innerHTML = "En Curso";
		    // } else if (document.getElementById("fechaReal" + pasos[posicion][0]).value <= document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value) {
		    //   divDiagnostico.innerHTML = "En Curso";
		    // } else {
		    //   divDiagnostico.innerHTML = "Atrasado!!";
		    // }
		  } else {
		    diagnosticoInterno = "Atrasado!!";
		    divDiagnostico.innerHTML = displayPDCStatus(diagnosticoInterno);
		  }
		  if (divEstadoProceso.innerHTML == pasos[7][1]) {
		    if (document.getElementById("fechaReal" + pasos[7][0]).value > document.getElementById("fecha" + pasos[7][0] + "Teorica").value) {
		      divDiagnostico.innerHTML = displayPDCStatus("Terminado con retrasos");
		      document.getElementById("estadoProceso").value = "Terminado con retrasos";
		    } else {
		      divDiagnostico.innerHTML = displayPDCStatus("Terminado a tiempo");
		      document.getElementById("estadoProceso").value = "Terminado a tiempo";
		    }
		  } else {
		    document.getElementById("estadoProceso").value = diagnosticoInterno + "; " + divEstadoProceso.innerHTML;
		  }
		  generarIconoReal(pasos, posicion);
		  for (i = posicion; i < 8; i++) {
		    if (i > -1) {
		      generarIconoProyectado(pasos[i][0]);
		    }
		  }
		}

		$("#valorPresupuesto, #valorAdjudicado").on({
			keyup: function() {
				var valorPresupuesto = numeral(document.getElementById("valorPresupuesto").value);
				var valorAdjudicado = numeral(document.getElementById("valorAdjudicado").value);
				var valorAhorroPerdida = (valorPresupuesto._value - valorAdjudicado._value);
				valorAhorroPerdida = numeral(valorAhorroPerdida).format('$0,0.00');
				document.getElementById("valorAhorroPerdida").value = valorAhorroPerdida;
			}
		});

		$("#valorDevoluciones, #valorAdjudicado").on({
			keyup: function() {
				var valorAdjudicado = numeral(document.getElementById("valorAdjudicado").value);
				var valorDevoluciones = numeral(document.getElementById("valorDevoluciones").value);
				var tasaDevoluciones = (valorDevoluciones._value / valorAdjudicado._value);
				tasaDevoluciones = numeral(tasaDevoluciones).format('0.00%');
				document.getElementById("tasaDevoluciones").value = tasaDevoluciones;
			}
		});

		var mostrarInputsOcultos = function() {
		  if (document.getElementById('fechaRealFabricacion').value == "" && document.getElementById('fechaRealInsumosObra').value == "" && document.getElementById('fechaRealInicioProyectadaContrato').value == "") {
		    if (document.getElementById('fechaRealLegalizacionContrato').value == "") {
		      $(".informacionAdjudicacionProveedor").hide("slow");
		      $("#nitAdjudicado").val("").change();
		      $("#activoInformacionAdjudicacionProveedor").val(0).change();
		    } else {
		      $(".informacionAdjudicacionProveedor").show("slow");
		      $("#activoInformacionAdjudicacionProveedor").val(1).change();
		    }

			$("#seccionSeguimientoContrato").hide("slow");
		    $(".informacionAdjudicacionContrato").hide("slow");
		    $("#fechaVencimientoPolizas").val("").change();
		    $("#activoInformacionAdjudicacionContrato").val(0).change();
			$(".seguimientoContrato").hide("slow");
		    $("#activoSeguimientoContrato").val(0).change();

		  } else {


		    $(".informacionAdjudicacionProveedor").show("slow");
		    $("#activoInformacionAdjudicacionProveedor").val(1).change();
		    $(".informacionAdjudicacionContrato").show("slow");
		    $("#activoInformacionAdjudicacionContrato").val(1).change();
		    $("#fechaVencimientoPolizas").datepicker({
		      dateFormat: 'yy-mm-dd',
		      changeMonth: true,
		      changeYear: true,
		      showOtherMonths: true,
		      selectOtherMonths: true,
		    });
			$("#seccionSeguimientoContrato").show("slow");
			if (document.getElementById('fechaRealInicioProyectadaContrato').value == "") {
				$(".seguimientoContrato").hide("slow");
				$("#valorReclamado").val(0).change();
				$("#valorDevoluciones").val(0).change();
			} else {
				$(".seguimientoContrato").show("slow");
			}

		    $("#activoSeguimientoContrato").val(1).change();
		  }
		}

		var generarIconoReal = function(pasos, posicion) {
		  var iconoAtraso = function(celda) {
		    celda.classList.remove('fa-grin-stars');
		    celda.classList.remove('iconoContratoVerde');
		    celda.classList.add('fa-skull-crossbones');
		    celda.classList.add('iconoContratoRojo');
		  }
		  var iconoATiempo = function(celda) {
		    celda.classList.remove('fa-skull-crossbones');
		    celda.classList.remove('iconoContratoRojo');
		    celda.classList.add('fa-grin-stars');
		    celda.classList.add('iconoContratoVerde');
		  }
		  var iconoVacio = function(celda) {
		    celda.classList.remove('fa-grin-stars');
		    celda.classList.remove('iconoContratoVerde');
		    celda.classList.remove('fa-skull-crossbones');
		    celda.classList.remove('iconoContratoRojo');
		  }
		  for (i = 0; i < 8; i++) {
		    if (i > -1) {
		      var paso = pasos[i][0];
		      var teorica = document.getElementById("fecha" + paso + "Teorica");
		      var proyectada = document.getElementById("fecha" + paso);
		      var real = document.getElementById("fechaReal" + paso);
		      var celda = document.getElementById("iconFechaReal" + paso);
		      if (real.value != "") {
		        if (teorica.value < real.value) {
		          iconoAtraso(celda);
		        } else {
		          iconoATiempo(celda);
		        }
		      } else {
		        if (i <= posicion) {
		          if (teorica.value < proyectada.value) {
		            iconoAtraso(celda);
		          } else {
		            iconoATiempo(celda);
		          }
		        } else {
		          iconoVacio(celda);
		        }
		      }
		    }
		  }
		}

		var generarIconoProyectado = function(paso){
			var teorica = document.getElementById("fecha" + paso + "Teorica");
			var proyectada = document.getElementById("fecha" + paso);
			var real = document.getElementById("fechaReal" + paso);
			var celda = document.getElementById("iconFecha" + paso);

			if(teorica.value < proyectada.value){
				celda.classList.remove('fa-glasses');
				celda.classList.remove('iconoContratoVerde');
				celda.classList.add('fa-glasses');
				celda.classList.add('iconoContratoNaranja');
			}else{
				celda.classList.remove('fa-glasses');
				celda.classList.remove('iconoContratoNaranja');
				celda.classList.add('fa-glasses');
				celda.classList.add('iconoContratoVerde');
			}
		}

		var actualizarPDC=function(options){
			options = options || {};
			$("#modal_spinner").modal("show");
			var db = document.getElementById('baseDatos').value;
			var semana = obtenerSemanaPdc();
			$.ajax({
				method:"POST",
				url: '/api/pdc/auto/apply-from-contratos?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
				dataType: 'json'
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
				// console.log(json_info);
				// console.log(json_info["respuesta"]);
				if(json_info["respuesta"]=="BIEN"){
					if (options.reloadOnSuccess === false) {
						$("#modal_spinner").modal("hide");
						if (typeof options.onSuccess === 'function') {
							options.onSuccess(json_info);
						}
						return;
					}

					location.assign("/pdc");
					// document.getElementById('mensaje').innerHTML = "El plan de compras se ha actualizado";
					// $("#mensaje").fadeOut(10000, function(){
					// 	$(this).html("");
					// 	$(this).fadeIn(3000);
					// });
				} else {
					$("#modal_spinner").modal("hide");
					if (typeof options.onError === 'function') {
						options.onError(json_info);
					}
				}
			}).fail(function(xhr, status, error) {
				$("#modal_spinner").modal("hide");
				if (window.AIA && window.AIA.Notice && typeof window.AIA.Notice.error === 'function') {
					window.AIA.Notice.error('No se pudo actualizar el Plan de Compras y Contrataciones' + (pdcSyncOrigin ? ' desde ' + pdcSyncOrigin : '') + '.');
				}
				if (typeof options.onError === 'function') {
					options.onError({"respuesta": "ERROR", "detalle": error || status || xhr.statusText || ''});
				}
			});
		}

		var quitar_guion=function(){
				var NIT=$("#nitAdjudicado").val();
				NIT_nuevo = NIT.replace("-","","gi");
				NIT_nuevo = NIT_nuevo.replace(".","","gi");
				$("#nitAdjudicado").val(NIT_nuevo);
		}

		var parseFechaIsoLocal = function(valor) {
			var match = /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/.exec($.trim(String(valor || '')));

			if (!match) {
				return null;
			}

			var year = parseInt(match[1], 10);
			var month = parseInt(match[2], 10) - 1;
			var day = parseInt(match[3], 10);
			var fecha = new Date(year, month, day);

			if (fecha.getFullYear() !== year || fecha.getMonth() !== month || fecha.getDate() !== day) {
				return null;
			}

			return fecha;
		}

		var selectoresFecha=function(fecha){
			var $campoReal = $("#fechaReal" + fecha);
			var fechaRealActual = parseFechaIsoLocal($campoReal.val());
			var fechaBase = parseFechaIsoLocal($("#fecha" + fecha).val());
			var fechaFallback = parseFechaIsoLocal($("#fecha" + fecha + "Teorica").val());
			var fechaInicial = fechaRealActual || fechaBase || fechaFallback;

			$campoReal.datepicker("destroy");
			$campoReal.datepicker({dateFormat: 'yy-mm-dd',
																 changeMonth: true,
																 changeYear: true,
																 showOtherMonths: true,
																 selectOtherMonths: true,
																 defaultDate: fechaInicial || null,
																 beforeShow: function(input, inst) {
																 	if (!$.trim($(input).val()) && fechaInicial) {
																 		$(input).datepicker('setDate', fechaInicial);
																 		$(input).val('');
																 	}
																 },
															});
		};

		var bloqueoFechaVencimientoPolizas = function(){
			var aplicaPolizas = document.getElementById('aplicaPolizas').value;
			if(aplicaPolizas == 0){
				document.getElementById('fechaVencimientoPolizas').disabled = true;
				document.getElementById('fechaVencimientoPolizas').value = "";
			}else{
				document.getElementById('fechaVencimientoPolizas').disabled = false;
			}
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar_modificar = function() {
			$("#btn_guardar_pdc").off("click.pdcSave").on("click.pdcSave", function(e){
				e.preventDefault();
				if(document.getElementById('aplicaPolizas').value == 0){
					var fechaVencimientoPolizas = "fechaVencimientoPolizas=";
				}else{
					var fechaVencimientoPolizas = "";
				}
				var frm = $("#formularioContrato").serialize();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var paquete = $("#nombrePaqueteContratacion").val();
				var Id = document.getElementById('Id').value;
				var opcion = document.getElementById('opcion').value;
				var tipoPaquete = document.getElementById('tipoPaquete').value;


				frm = frm + "&db=" + db + "&Id=" + Id + "&opcion=" + opcion + "&tipoPaquete=" + tipoPaquete + "&semana=" + semana + "&" + fechaVencimientoPolizas;
        		//console.log(frm);
				$.ajax({
					method: "POST",
					url: "/api/pdc/save",
          			contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
					// console.log(json_info);
					// mostrar_mensaje( json_info );
					if(json_info == "OK"){
						$("#modalContrato #cuadro4").scrollTop(0);
						cerrarModalContrato();
						recargarTabla('');

						$("#mensajeActualizacion").removeClass('pdc-message-success pdc-message-error').addClass('pdc-message-neutral');
						document.getElementById('mensajeActualizacion').innerHTML = "El paquete de contratación <b>\"" + paquete + "\"</b> se ha actualizado";
						$("#mensajeActualizacion")
							.show() // Trigger CSS Animation
							.delay(4000) // Wait
							.fadeOut(1000, function(){
								$(this).html(""); // Clean up
							});
					}else{
						$("#mensajeModalContrato").removeClass('pdc-message-success pdc-message-neutral').addClass('pdc-message-error');
						document.getElementById('mensajeModalContrato').innerHTML = json_info;
						$("#mensajeModalContrato").fadeOut(10000, function(){
							$(this).html("");
							$(this).fadeIn(3000);
						});
					}
				});
			});
		}

		function cerrarModalContrato() {
			$("#modalContrato").modal("hide");
			$("#modalContrato").removeClass("show").hide().attr("aria-hidden", "true").removeAttr("aria-modal");
			$(".modal-backdrop").remove();
			$("body").removeClass("modal-open").css("padding-right", "");
		}

		/*Toma los datos de la fila en la que se presionó el botón eliminar*/
		/*limpia los valores del formulario de registro*/
		var limpiar_datos = function() {
			$("#opcion").val("registrar");
			$("#Id").val("");
			$("#actividad").val("").focus();
			$("#descripcionActividad").val("");
			$("#fechaInicio").val("");
			$("#tipoContrato").val("");
			$("#idPaqueteContratacion").val("");
			$("#paqueteContratacion").val("");
		}

		var limpiar_datos_nueva_sem = function() {
			$("#opcion").val("registrar");
			$("#inicio_sem").val("");
		}

		// Funciones para los input de dinero
		$("input[data-type='currency']").on({
		    change: function() {
		      formatCurrency($(this));
		    },
		    blur: function() {
		      formatCurrency($(this), "blur");
		    }
		});


		function formatCurrency(input, blur) {
			input_val = input.val();
			if(input_val === null || input_val === ""){
				input.val('');
			}else{
				var myNumeral = numeral(input_val);
				myNumeral = myNumeral.format('$0,0.00');
				input.val(myNumeral);
			}
		}

		</script>
	<script src="/vendor/handsontable/handsontable.full.min.js?v=14.6.1"></script>
	<script src="/js/modules/info_general_nav.js?v=20260708b"></script>
	<script src="/js/modules/pdc/hot.js?v=<?php echo urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/js/modules/pdc/hot.js') ?: 'hot14')); ?>"></script>
</body>
</html>
