<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="planCompras">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="modificar">
		<input type="hidden" id="nombrePaqueteContratacion" name="nombrePaqueteContratacion" value="">
		<input type="hidden" id="tipoPaquete" name="tipoPaquete" value="">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro" style:"visibility: hidden">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_infoGeneral table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>consecutivo</th>
						<th>ID</th>
						<th>TIPO DE CONTRATO</th>
						<th>PAQUETE DE CONTRATACIÓN</th>
						<th>ACTIVIDADES DEL PROYECTO</th>
						<th>A TIEMPO / ATRASADO</th>
						<th>INICIO DEL PROCESO DE CONTRATACIÓN</th>
						<th></th>
						<th></th>
						<th>INGRESO A PLATAFORMA LICIFY</th>
						<th></th>
						<th></th>
						<th>ENTREGA DE PLIEGOS Y/O CARTA, ELABORACIÓN DE PROPUESTA</th>
						<th></th>
						<th></th>
						<th>RECIBO DE PROPUESTAS</th>
						<th></th>
						<th></th>
						<th>CUADROS COMPARATIVOS, ANÁLISIS Y ADJUDICACIÓN</th>
						<th></th>
						<th></th>
						<th>LEGALIZACIÓN CONTRATO</th>
						<th></th>
						<th></th>
						<th>PERIODO DE FABRICACIÓN, PRODUCCIÓN, IMPORTACIONES, TRANSPORTES, MOVILIZACIÓN, ETC</th>
						<th></th>
						<th></th>
						<th>INICIO ANTICIPO INSUMOS EN OBRA</th>
						<th></th>
						<th></th>
						<th>INICIO ACTIVIDADES CRONOGRAMA</th>
						<th>INICIO ACTIVIDADES PROYECTADO</th>
						<th>INICIO ACTIVIDADES REAL</th>
						<th>OBSERVACIONES</th>
						<th>ORDEN VISUAL</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<div class="modal_Contrato modal fade" id="modalContrato" tabindex="-1" role="dialog" aria-labelledby="modal_ContratoLabel">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="modalContratoLabel">
							<p class="modal-body-texto-Contrato" id="modal-body-texto-Contrato" style="margin-bottom:0"></p>
						</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="recargarTabla('')"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form id="formularioContrato" class="form form-horizontal" action="" method="POST">
									<div class="form-group">
										<div class="form-group parametro_Contrato">
											<div class='form_eval form-group'>
												<h3 id='form_general'>Descripción del Proceso</h3>
											</div>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Actividades del programa de obra en este paquete de contratación:</h6>
												</label>
												<div id='divActividadesDelContrato' name='divActividadesDelContrato' style='width:1200px; max-width:60% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'><textarea id="actividadesDelContrato" name="actividadesDelContrato" class="form-control" readonly></textarea>
												</div>
											</div>
											<br>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Fecha de Corte:</h6>
												</label>
												<div id='divFechaActual' name='divFechaActual' style='width:1200px; max-width:29% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'><input id='fechaActual' name='fechaActual' class='form-control' type='text' value='' placeholder='Fecha de Corte' style='text-align:center' autocomplete="off" readonly>
												</div>
												<div style='width:1200px; max-width:25% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'>
												</div>
											</div>
											<br>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Fecha de inicio del contrato según el cronograma:</h6>
												</label>
												<div id='divFechaInicioContrato' name='divFechaInicioContrato' style='width:1200px; max-width:29% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'><input id='fechaInicioContrato' name='fechaInicioContrato' class='form-control' type='text' value='' placeholder='Fecha de Inicio' style='text-align:center' autocomplete="off" readonly>
												</div>
												<div style='width:1200px; max-width:25% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'>
												</div>
											</div>
											<br>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Valor en presupuesto de la actividad:</h6>
												</label>
												<div id='divValorPresupuesto' name='divValorPresupuesto' style='width:1200px; max-width:29% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'><input id='valorPresupuesto' name='valorPresupuesto' class='form-control' type='text' value='' placeholder='Valor en Pesos Colombianos' style='background:white; text-align:center' autocomplete="off" data-type="currency">
												</div>
												<div style='width:1200px; max-width:25% ; padding:1px; margin:0 auto; display:inline-block; text-align:center'>
												</div>
											</div>
										</div>
										<div class="form-group parametro_Contrato">
											<div class='form_eval form-group'>
												<h3 id='form_general'>Proceso de Contratación</h3>
											</div>
											<div class="filaEncabezado">
												<div class="labelFormularioContratos">
													<h6></h6>
												</div>
												<div class="labelFilaEncabezado">
													<h6>Duración (días)</h6>
												</div>
												<div class="labelFilaEncabezado">
													<h6>Fecha Inicio Teórica</h6>
												</div>
												<div class="labelFilaEncabezado">
													<h6>Fecha Inicio Proyectada</h6>
												</div>
												<div class="labelFilaEncabezado">
													<h6>Fecha Inicio Real</h6>
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>1. Elaboración de pliegos, selección de proveedores e invitaciones a cotizar:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasElaboracionPliegos' name='diasElaboracionPliegos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaElaboracionPliegosTeorica" class='fas fa-lg'></i>
													<input id='fechaElaboracionPliegosTeorica' name='fechaElaboracionPliegosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaElaboracionPliegos" class='fas fa-lg'></i>
													<input id='fechaElaboracionPliegos' name='fechaElaboracionPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealElaboracionPliegos" class='fas fa-lg'></i>
													<input id='fechaRealElaboracionPliegos' name='fechaRealElaboracionPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('ElaboracionPliegos');" onkeyup="calcularProcesoContratacionTeorico('ElaboracionPliegos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>2. Ingreso a plataforma Licify:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasIngresoLicify' name='diasIngresoLicify' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaIngresoLicifyTeorica" class='fas fa-lg'></i>
													<input id='fechaIngresoLicifyTeorica' name='fechaIngresoLicifyTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaIngresoLicify" class='fas fa-lg'></i>
													<input id='fechaIngresoLicify' name='fechaIngresoLicify' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealIngresoLicify" class='fas fa-lg'></i>
													<input id='fechaRealIngresoLicify' name='fechaRealIngresoLicify' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('IngresoLicify');" onkeyup="calcularProcesoContratacionTeorico('IngresoLicify');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>3. Entrega de pliegos y/o carta. Elaboración de propuesta:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasEntregaPliegos' name='diasEntregaPliegos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaEntregaPliegosTeorica" class='fas fa-lg'></i>
													<input id='fechaEntregaPliegosTeorica' name='fechaEntregaPliegosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaEntregaPliegos" class='fas fa-lg'></i>
													<input id='fechaEntregaPliegos' name='fechaEntregaPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealEntregaPliegos" class='fas fa-lg'></i>
													<input id='fechaRealEntregaPliegos' name='fechaRealEntregaPliegos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('EntregaPliegos');" onkeyup="calcularProcesoContratacionTeorico('EntregaPliegos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>4. Recibo de propuestas:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasReciboPropuestas' name='diasReciboPropuestas' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaReciboPropuestasTeorica" class='fas fa-lg'></i>
													<input id='fechaReciboPropuestasTeorica' name='fechaReciboPropuestasTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaReciboPropuestas" class='fas fa-lg'></i>
													<input id='fechaReciboPropuestas' name='fechaReciboPropuestas' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealReciboPropuestas" class='fas fa-lg'></i>
													<input id='fechaRealReciboPropuestas' name='fechaRealReciboPropuestas' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('ReciboPropuestas');" onkeyup="calcularProcesoContratacionTeorico('ReciboPropuestas');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>5. Cuadros comparativos, análisis y adjudicación:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasCuadrosComparativos' name='diasCuadrosComparativos' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaCuadrosComparativosTeorica" class='fas fa-lg'></i>
													<input id='fechaCuadrosComparativosTeorica' name='fechaCuadrosComparativosTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaCuadrosComparativos" class='fas fa-lg'></i>
													<input id='fechaCuadrosComparativos' name='fechaCuadrosComparativos' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealCuadrosComparativos" class='fas fa-lg'></i>
													<input id='fechaRealCuadrosComparativos' name='fechaRealCuadrosComparativos' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('CuadrosComparativos');" onkeyup="calcularProcesoContratacionTeorico('CuadrosComparativos');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>6. Legalización del contrato:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasLegalizacionContrato' name='diasLegalizacionContrato' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaLegalizacionContratoTeorica" class='fas fa-lg'></i>
													<input id='fechaLegalizacionContratoTeorica' name='fechaLegalizacionContratoTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaLegalizacionContrato" class='fas fa-lg'></i>
													<input id='fechaLegalizacionContrato' name='fechaLegalizacionContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealLegalizacionContrato" class='fas fa-lg'></i>
													<input id='fechaRealLegalizacionContrato' name='fechaRealLegalizacionContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('LegalizacionContrato');" onkeyup="calcularProcesoContratacionTeorico('LegalizacionContrato');">
												</div>
											</div>
											<div class="container informacionAdjudicacionProveedor">
												<input type="hidden" id="activoInformacionAdjudicacionProveedor" name="activoInformacionAdjudicacionProveedor" value="0">
												<input type="hidden" id="idProveedorExistente" name="idProveedorExistente" value="0">
												<div class="row" style="text-align:center; margin:0 auto 10px">
													<h5><b>Información del Proveedor Adjudicado</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="nitAdjudicado" class"labelFormularioAdjudicado">NIT (sin puntos o guiones)</label>
														<input type="number" step="1" class="form-control" id="nitAdjudicado" name="nitAdjudicado" placeholder="NIT" minlenght="9" maxlength="9" onchange="verificarProveedor('nitAdjudicado')" onkeyup="quitar_guion()">
													</div>
													<div class="form-group col">
														<label for="subcontratistaAdjudicado" class"labelFormularioAdjudicado">Nombre del Proveedor</label>
														<input type="text" class="form-control" id="subcontratistaAdjudicado" name="subcontratistaAdjudicado" placeholder="Nombre del Proveedor">
													</div>
												</div>
												<br>
												<div class="row">
													<div class="form-group col">
														<label for="correoAdjudicado" class"labelFormularioAdjudicado">Correo de Contacto</label>
														<input type="email" class="form-control" id="correoAdjudicado" name="correoAdjudicado" placeholder="Correo de Contacto">
													</div>
													<div class="form-group col">
														<label for="tipoProveedorAdjudicado" class"labelFormularioAdjudicado">Tipo de Proveedor</label>
														<input type="text" class="form-control" id="tipoProveedorAdjudicado" name="tipoProveedorAdjudicado" placeholder="Tipo de Proveedor" readonly>
													</div>
												</div>
												<div class="row">
													<p class="mensajeModalInformacionAdjudicado" id="mensajeModalInformacionAdjudicado" style="margin-left:5px; display:inline-block"></p>
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>7. Periodo de fabricación, producción, importaciones, transportes, movilización, etc:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasFabricacion' name='diasFabricacion' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaFabricacionTeorica" class='fas fa-lg'></i>
													<input id='fechaFabricacionTeorica' name='fechaFabricacionTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaFabricacion" class='fas fa-lg'></i>
													<input id='fechaFabricacion' name='fechaFabricacion' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealFabricacion" class='fas fa-lg'></i>
													<input id='fechaRealFabricacion' name='fechaRealFabricacion' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('Fabricacion');" onkeyup="calcularProcesoContratacionTeorico('Fabricacion');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>8. Anticipación de insumos en obra:</h6>
												</div>
												<div class="inputFormularioContratos">
													<input id='diasInsumosObra' name='diasInsumosObra' class='form-control' type='text' value='' placeholder='Duración' autocomplete="off" onkeyup="calcularProcesoContratacionTeorico('');">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInsumosObraTeorica" class='fas fa-lg'></i>
													<input id='fechaInsumosObraTeorica' name='fechaInsumosObraTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInsumosObra" class='fas fa-lg'></i>
													<input id='fechaInsumosObra' name='fechaInsumosObra' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealInsumosObra" class='fas fa-lg'></i>
													<input id='fechaRealInsumosObra' name='fechaRealInsumosObra' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('InsumosObra');" onkeyup="calcularProcesoContratacionTeorico('InsumosObra');">
												</div>
											</div>
											<div class="pasoProcesoContratacion">
												<div class="labelFormularioContratos">
													<h6>9. Comienzo de las actividades en la obra:</h6>
												</div>
												<div class="inputFormularioContratos" style="background:#c7c7c7">
													<input id='diasInicioProyectadaContrato' name='diasInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='' autocomplete="off" readonly>
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInicioProyectadaContratoTeorica" class='fas fa-lg'></i>
													<input id='fechaInicioProyectadaContratoTeorica' name='fechaInicioProyectadaContratoTeorica' class='form-control' type='text' value='' placeholder='Fecha Inicio Teórica' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaInicioProyectadaContrato" class='fas fa-lg'></i>
													<input id='fechaInicioProyectadaContrato' name='fechaInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Proyectada' autocomplete="off">
												</div>
												<div class="inputFormularioContratos">
													<i id="iconFechaRealInicioProyectadaContrato" class='fas fa-lg'></i>
													<input id='fechaRealInicioProyectadaContrato' name='fechaRealInicioProyectadaContrato' class='form-control' type='text' value='' placeholder='Fecha Inicio Real' autocomplete="off" onchange="calcularProcesoContratacionTeorico('InicioProyectadaContrato');" onkeyup="calcularProcesoContratacionTeorico('InicioProyectadaContrato');">
												</div>
											</div>
										</div>
										<div class="form-group parametro_Contrato">
											<div class='form_eval form-group'>
												<h3 id='form_general'>Diagnóstico del Proceso</h3>
											</div>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Estado del Proceso:</h6>
												</label>
												<div id='divEstadoProceso' name='divEstadoProceso' style='width:1200px; max-width:60% ; padding:1px; margin:0 auto; display:inline-block; text-align:left; min-height: calc(1.5em + 0.75rem + 2px); padding: 0.375rem 0.75rem; font-size: 1rem; font-weight: 400; line-height: 1.5; color:#495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;'>
												</div>
											</div>
											<br>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>¿Donde Debería Ir Según el Cronograma?:</h6>
												</label>
												<div id='divDeberiaProceso' name='divDeberiaProceso' style='width:1200px; max-width:60% ; padding:1px; margin:0 auto; display:inline-block; text-align:left; min-height: calc(1.5em + 0.75rem + 2px); padding: 0.375rem 0.75rem; font-size: 1rem; font-weight: 400; line-height: 1.5; color:#495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;'>
												</div>
											</div>
											<br>
											<div class="col-sm-12" style="display:flex; align-items:center; border-bottom-color:black">
												<label for="Contrato" class="control-label" style="width:250px; max-width:40% ;display:inline-block; text-align:center; margin:0 auto">
													<h6>Diagnóstico:</h6>
												</label>
												<div id='divDiagnostico' name='divDiagnostico' style='width:1200px; max-width:60% ; padding:1px; margin:0 auto; display:inline-block; text-align:left; min-height: calc(1.5em + 0.75rem + 2px); padding: 0.375rem 0.75rem; font-size: 1rem; font-weight: 400; line-height: 1.5; color:#495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;'>
												</div>
											</div>
											<input type="hidden" id="estadoProceso" name="estadoProceso" value="">
										</div>
										<div class="form-group parametro_Contrato" id="seccionSeguimientoContrato">
											<div class='form_eval form-group'>
												<h3 id='form_general'>Seguimiento al Contrato</h3>
											</div>
											<div class="container informacionAdjudicacionContrato">
												<input type="hidden" id="activoInformacionAdjudicacionContrato" name="activoInformacionAdjudicacionContrato" value="0">
												<div class="row" style="text-align:center; margin:0 auto 10px">
													<h5><b>Información del Contrato Adjudicado</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="numeroContrato" class"labelFormularioAdjudicado">Número del Contrato</label>
														<input type="text" step="1" class="form-control" id="numeroContrato" name="numeroContrato" placeholder="Numero del Contrato">
													</div>
													<div class="form-group col">
														<label for="fechaVencimientoPolizas" class"labelFormularioAdjudicado">Fecha de Vencimiento de Pólizas de Cumplimiento</label>
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
												<br>
												<div class="row">
													<div class="form-group col">
														<label for="valorPrimeraNegociacion" class"labelFormularioAdjudicado">Valor Primera Negociación</label>
														<input type="text" class="form-control" id="valorPrimeraNegociacion" name="valorPrimeraNegociacion" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorAdjudicado" class"labelFormularioAdjudicado">Valor Adjudicado</label>
														<input type="text" class="form-control" id="valorAdjudicado" name="valorAdjudicado" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
												</div>
												<div class="row">
												<div class="form-group col">
														<label for="valorAnticipo" class"labelFormularioAdjudicado">Valor Anticipo</label>
														<input type="text" class="form-control" id="valorAnticipo" name="valorAnticipo" value="" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorAhorroPerdida" class"labelFormularioSeguimientoContrato">(+) Ahorro / (-) Pérdida</label>
														<input type="text" class="form-control" id="valorAhorroPerdida" name="valorAhorroPerdida" value="" data-type="currency" placeholder="Valor en Pesos Colombianos" readonly disabled>
													</div>
												</div>
												<div class="row">
													<p class="mensajeModalInformacionAdjudicado" id="mensajeModalInformacionAdjudicado" style="margin-left:5px; display:inline-block"></p>
												</div>
											</div>
											<div class="container seguimientoContrato">
												<input type="hidden" id="activoSeguimientoContrato" name="activoSeguimientoContrato" value="0">
												<div class="row" style="text-align:center; margin:0 auto 10px">
													<h5><b>Devoluciones al Proveedor</b></h5>
												</div>
												<div class="row">
													<div class="form-group col">
														<label for="valorReclamado" class"labelFormularioSeguimientoContrato">Valor en Reclamación</label>
														<input type="text" class="form-control" id="valorReclamado" name="valorReclamado" value="0" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="valorDevoluciones" class"labelFormularioSeguimientoContrato">Valor de Devoluciones al Proveedor</label>
														<input type="text" class="form-control" id="valorDevoluciones" name="valorDevoluciones" value="0" data-type="currency" placeholder="Valor en Pesos Colombianos">
													</div>
													<div class="form-group col">
														<label for="tasaDevoluciones" class"labelFormularioSeguimientoContrato">Tasa de Devoluciones al Proveedor</label>
														<input type="text" class="form-control" id="tasaDevoluciones" name="tasaDevoluciones" value="" readonly disabled>
													</div>
												</div>
												<div class="row">
													<p class="mensajeModalSeguimientoContrato" id="mensajeModalSeguimientoContrato" style="margin-left:5px; display:inline-block"></p>
												</div>
											</div>
										</div>
										<div class="form-group parametro_Contrato" style="width:100%;">
											<div class='form_eval form-group'>
												<h3 id='form_general'>Observaciones</h3>
											</div>
											<div class="col-sm-12"><textarea id="observacionesContrato" name="observacionesContrato" class="form-control"></textarea></div>
										</div>
									</div>
									<!--Se crean los botones Guardar y Listar-->
									<div class="form-group">
										<div class="col-sm-offset-2 col-sm-12">
											<input id="btn_guardar_pdc" type="button" class="btn btn-primary" value="Guardar">
											<input id="btn_cancelar_editar" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar">
											<p class="mensajeModalContrato" id="mensajeModalContrato" style="margin-left:5px; display:inline-block"></p>
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

		<div class="modal_DefinirContrato modal fade" id="modalDefinirContratos" tabindex="-1" role="dialog" aria-labelledby="modal_DefinirContratoLabel">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="modalDefinirContratosLabel">
							<p class="modal-body-texto-DefinirContrato" id="modal-body-texto-DefinirContrato">Definir Contratos</p>
						</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.reload()"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form id="formularioDefinirContrato" class="form form-horizontal" action="" method="POST">
									<div class="form-group">
										<div class="form-group parametro_Contrato">
											<table id="dt_definirContratos" class="table table-bordered table-responsive-sm" style="width:100%">
												<thead class="thead-dark">
													<tr>
														<th>Consecutivo</th>
														<th>Tipo de Contrato</th>
														<th>Paquete de Contratación</th>
														<th>Número de Contratos Asociados</th>
														<th>subcontratoPaquete</th>
														<th>ordenVisual</th>
													</tr>
												</thead>
											</table>
										</div>
									</div>
									<!--Se crean los botones Guardar y Listar-->
									<div class="form-group">
										<div class="col-sm-offset-2 col-sm-12">
											<input id="btn_guardar_definirContratos" type="button" class="btn btn-primary" value="Guardar" onclick="guardar_DefinirContratos()">
											<input id="btn_cancelar_definirContratos" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar" onClick="location.reload()">
											<p class="mensajeModalDefinirContrato" id="mensajeModalDefinirContrato" style="margin-left:5px; display:inline-block"></p>
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

		<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
		<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modalEliminarLabel">Eliminar Actividad</h4>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-eliminar" id="modal-body-texto-eliminar"></p>
		      </div>
		      <div class="modal-footer">
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
	<!--Formatos de números-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script type="text/javascript" src="../funciones_generales/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="../funciones_generales/js/funcionesGenerales6.js" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
		/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function() {
		  $("#formulario_nuevo").hide();
			cargarDatosGeneralesPagina(document.getElementById('seccion').value);
		});

		var cargaParametros = function() {
			$('#S1,#S2,#S3,#S4,#S5,#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5,#MO1,#MO2,#MO3,#MO4,#MO5,#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5,#SI1,#SI2,#SI3,#SI4,#SI5,#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5').select2({tags: true, selectOnClose: true, allowClear: true});
			listar();
			eliminar();
		}

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  recargarTabla("listar");
		  limpiar_datos();
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


		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
		  /*Identificamos la altura de la hoja para determinar la altura de la tabla*/
		  var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 220) + "px";

		  var table = $("#dt_cliente").DataTable({
		    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
		    "destroy": true,
				"orderFixed":[35, "asc"],

		    /*                "autoWidth": true,*/
		    "fixedHeader": false,
		    "scrollX": true,
		    //                console.log($(document).height());
		    "scrollY": alturatabla,
		    /*                "scrollCollapse": false,*/
		    "responsive": true,
		    "paging": false,
		    "ajax": {
		      "method": "POST",
		      "url": "../pdc/listar_pdc.php?db="+db+"&semana="+Max_Semana+"&definirContratos=0"
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [

            // { orderable: true, className: 'reorder', targets: 35 },
            { orderable: false, targets: '_all' },

						{
							'targets': [1,2,3,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,35],
							'width':'1%',
							'render': function ( data, type, full, meta ) {
							 return data;
							 },
						},

						{
							'targets': [4],
							'width':'5%',
							'render': function ( data, type, row, meta ) {
								var subcontratoPaquete = row["subcontratoPaquete"];
								if(subcontratoPaquete > 1){
									return data + " (Subcontrato " + subcontratoPaquete + ")";
								}else{
									return data;
								}
							},
						},
						{
							'targets': [4,5,34],
							'width':'5%',
							'render': function ( data, type, full, meta ) {
								return data;
							},
						},
						{
							'targets': [6],
							'width':'5%',
							'render': function ( data, type, row, meta ) {
								var titulo=row["titulo"];
								var texto = row["estado"];
								var id = row["id"];
								var procesoIniciado=row["procesoIniciado"];
								var fechaInicioProceso= new Date(row["fechaElaboracionPliegos"]);
								var fechaInicioSemana = new Date(document.getElementById('Fecha_Inicio_SemYMD').value);
								var dias = (fechaInicioProceso - fechaInicioSemana)/(1000 * 3600 * 24);
								if (id != ""){
									if(titulo==0){
										if(texto.includes("Terminado a tiempo")){
											return "<i class='fas fa-grin-stars fa-lg' style='margin-right:5px; display:inline-block; color:#2fc231'></i>  " + data;
										}else if(texto.includes("Terminado con retrasos")){
											return "<i class='fas fa-sad-cry fa-lg' style='margin-right:5px; display:inline-block; color:#8cd192'></i>  " + data;
										}else if(texto.includes("A tiempo") && texto.includes("Proceso de contratación no iniciado")){
											return "" + data;
										}else if(texto.includes("A tiempo")){
											return "<i class='fas fa-glasses fa-lg' style='margin-right:5px; display:inline-block; color:#c2ac2f'></i>  " + data;
										}else if(texto.includes("Atrasado!!")){
											return "<i class='fas fa-skull-crossbones fa-lg' style='margin-right:5px; display:inline-block; color:#c22f2f'></i>  " + data;
										}else{
											return "<b>No Registrado</b>";
										}
									}else{
										return data;
									}
								}else{
									return "";
								}
							},
						},

						{
							'targets': [7],
							'width':'1%',
							'render': function ( data, type, row, meta ) {
								var titulo=row["titulo"];
								var procesoIniciado=row["procesoIniciado"]*1;
								var fechaInicioProceso= new Date(row["fechaElaboracionPliegos"]);
								var fechaInicioSemana = new Date(document.getElementById('Fecha_Inicio_SemYMD').value);
								var dias = (fechaInicioProceso - fechaInicioSemana)/(1000 * 3600 * 24);
								if (titulo == 0){
									if(fechaInicioSemana != ""){
										if(procesoIniciado == 0 && dias <= 7 && dias >= 0){
											return "<i class='fas fa-glasses fa-lg' style='margin-right:5px; display:inline-block; color:#c2ac2f'></i>  <b>" + data + "</b> (Debe comenzar en "+Math.floor(dias/7)+ " semana)";
										}else if(procesoIniciado == 0 && dias > 7){
											return "<i class='fas fa-glasses fa-lg' style='margin-right:5px; display:inline-block; color:#2f80c2'></i>  <b>" + data + "</b> (Debe comenzar en "+Math.floor(dias/7)+ " semanas)";
										}else if(procesoIniciado == 0 && dias < 0){
											return "<i class='fas fa-skull-crossbones fa-lg' style='margin-right:5px; display:inline-block; color:#c22f2f'></i>  <b>" + data + "</b> (Ya debió comenzar)";
										}else if(procesoIniciado == 1){
											return "<i class='fas fa-check fa-lg' style='margin-right:5px; display:inline-block; color:#2fc231'></i>  <b>" + data + "</b>";
										}else{
											return data;
										}
									}else{
										return data;
									}
								}else{
									return data;
								}
							},
						},

						{
								'targets': [0],
								'width':'1%',
								'render': function ( data, type, row, meta ) {
									var titulo=row["titulo"];
									var id = row["id"];
									var subcontratoPaquete = row["subcontratoPaquete"];
									if (id != ""){
										if(titulo==0){
											if(subcontratoPaquete > 1){
												boton="<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm' title='Eliminar' style='margin:1px'><i class='fa fa-trash-alt fa-xs'></i></button>"
											}else{
												boton="<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button>"	
											}
										}else{
												boton="";
										}
									}else{
										boton="";
									}
									return boton;
								},
						},

						{
								'targets': [0],
								'className': 'celdaBotones'
						},
						{
								'targets': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35],
								'className': 'celdaContenido'
						},
					],

					"columns":[
							{"data":"boton"},
							{"data":"consecutivo", "visible":false},
							{"data":"id", "visible":false},
							{"data":"tipoPaquete"},
							{"data":"paqueteContratacion"},
							{"data":"contratos"},
							{"data":"estado"},
							{"data":"fechaElaboracionPliegos"},
							{"data":"diasElaboracionPliegos", "visible":false},
							{"data":"fechaRealElaboracionPliegos", "visible":false},
							{"data":"fechaIngresoLicify", "visible":false},
							{"data":"diasIngresoLicify", "visible":false},
							{"data":"fechaRealIngresoLicify", "visible":false},
							{"data":"fechaEntregaPliegos", "visible":false},
							{"data":"diasEntregaPliegos", "visible":false},
							{"data":"fechaRealEntregaPliegos", "visible":false},
							{"data":"fechaReciboPropuestas", "visible":false},
							{"data":"diasReciboPropuestas", "visible":false},
							{"data":"fechaRealReciboPropuestas", "visible":false},
							{"data":"fechaCuadrosComparativos", "visible":false},
							{"data":"diasCuadrosComparativos", "visible":false},
							{"data":"fechaRealCuadrosComparativos", "visible":false},
							{"data":"fechaLegalizacionContrato", "visible":false},
							{"data":"diasLegalizacionContrato", "visible":false},
							{"data":"fechaRealLegalizacionContrato", "visible":false},
							{"data":"fechaFabricacion", "visible":false},
							{"data":"diasFabricacion", "visible":false},
							{"data":"fechaRealFabricacion", "visible":false},
							{"data":"fechaInsumosObra", "visible":false},
							{"data":"diasInsumosObra", "visible":false},
							{"data":"fechaRealInsumosObra", "visible":false},
							{"data":"fechaInicio"},
							{"data":"fechaInicioProyectada"},
							{"data":"fechaRealInicio"},
							{"data":"observacionesContrato"},
							{"data":"ordenVisual", "visible":false},
					],

				"createdRow": function( row, data, dataIndex){
						if(data.titulo!=0){
							$(row).addClass('Titulo');
							$('td', row).css('background-color', 'rgba(3,87,102,1)');
							$('td', row).css('color', '#ffffff');
						}else{
							if(data.fechaInicioProyectada == "" || data.valorPresupuesto == "" || data.valorPresupuesto == null){
								$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
							}
						}
							

						
				},

		    "language": idioma_espanol
		  });

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:50%;display:inline-block; "><button id="btn_tutorialActualizarCronograma" type="button" class="btn btn-secondary btn-sm" style="margin-right:5px" title="Video tutorial de la sección de \'\'Plan de Compras\'\'" onclick="window.open(\'https://youtu.be/3vqOnCOABzI\', \'_blank\')">Tutorial <i class="fas fa-list-ol fa-lg"></i></button><button id="btn_actualizarPDC" class="btn btn-primary btn-sm" title="Actualizar items del plan de compras" onclick="actualizarPDC()" style="margin-right:5px">Actualizar Plan de Compras <i class="fas fa-sync fa-lg"></i></button><button id="btn_definirContratosPDC" class="btn btn-warning btn-sm" title="Definir el número de contratos para cada paquete" onclick="obtener_data_definirContratos()">Desglosar Subcontratos <i class="fa fa-list-ol fa-lg" aria-hidden="true"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><!-- <button id="btn_paquetesContratacion" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=info_paquetesContratacion&semana='+semana+'\'">Paquetes de Contratación <i class="fas fa-arrow-right fa-m"></i></button> --><button id="btn_planCompras" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'../cambiar_pagina.php?seccion=planCompras&semana='+semana+'\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			maestroPermisos(document.getElementById('permiso').value);
			activarBuscador("#dt_cliente tbody", table);
			obtener_data_editar("#dt_cliente tbody", table);
			obtener_id_eliminar("#dt_cliente tbody", table);
		}


		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  var permiso = document.getElementById('permiso').value;
			var semana = document.getElementById('semana').value;
		  if (permiso=="C") {
		    var only_once = false;
		  } else {
		    var only_once = true;
		  }
		  $(tbody).one("click", "td.celdaContenido, button.editar", function(e) {
				e.stopPropagation();	
				if (only_once == true) {
					$("#nombrePaqueteContratacion, #tipoPaquete, #tipoProveedorAdjudicado, #idProveedorExistente, #numeroContrato, #aplicaPolizas, #fechaVencimientoPolizas, #valorPresupuesto, #valorPrimeraNegociacion, #valorAdjudicado, #valorAnticipo, #actividadesDelContrato, #fechaInicioContrato, #fechaActual, #diasElaboracionPliegos, #diasIngresoLicify, #diasEntregaPliegos, #diasReciboPropuestas, #diasCuadrosComparativos, #diasLegalizacionContrato, #diasFabricacion, #diasInsumosObra, #fechaRealElaboracionPliegos, #fechaRealIngresoLicify, #fechaRealEntregaPliegos, #fechaRealReciboPropuestas, #fechaRealCuadrosComparativos, #fechaRealLegalizacionContrato, #fechaRealFabricacion, #fechaRealInsumosObra, #fechaRealInicioProyectadaContrato, #observacionesContrato").val("");

					$("#valorReclamado, #valorDevoluciones").val(0);

					var data= table.row($(this).parents("tr")).data();
					if(data.titulo==0){
						$("#modalContrato").modal("show");
						$("#Id").val(data.consecutivo);
						$("#opcion").val("modificar");
						$("#nombrePaqueteContratacion").val(data.paqueteContratacion);
						$("#tipoPaquete").val(data.tipoPaquete);
						$("#tipoProveedorAdjudicado").val(data.tipoPaquete);
						$("#idProveedorExistente").val(data.idProveedorAdjudicado);
						$("#numeroContrato").val(data.numeroContrato);
						$("#aplicaPolizas").val(data.aplicaPolizas).change();
						$("#fechaVencimientoPolizas").val(data.fechaVencimientoPolizas);
						$("#valorPresupuesto").val(data.valorPresupuesto);
						$("#valorPrimeraNegociacion").val(data.valorPrimeraNegociacion);
						$("#valorAdjudicado").val(data.valorAdjudicado).change();
						$("#valorAnticipo").val(data.valorAnticipo);
						$("#valorReclamado").val(data.valorReclamado);
						$("#valorDevoluciones").val(data.valorDevoluciones).change();

						$("#valorAdjudicado").keyup();
						$("#valorDevoluciones").keyup();
						formatCurrency($("#valorPresupuesto"));
						formatCurrency($("#valorPrimeraNegociacion"));
						formatCurrency($("#valorAdjudicado"));
						formatCurrency($("#valorAnticipo"));
						formatCurrency($("#valorReclamado"));
						formatCurrency($("#valorDevoluciones"));



						$("#actividadesDelContrato").val(data.contratos);
						$("#fechaInicioContrato").val(data.fechaInicio);
						$("#fechaActual").val(document.getElementById("Fecha_Inicio_SemYMD").value);


						$("#diasElaboracionPliegos").val(data.diasElaboracionPliegos);
						$("#diasIngresoLicify").val(data.diasIngresoLicify);
						$("#diasEntregaPliegos").val(data.diasEntregaPliegos);
						$("#diasReciboPropuestas").val(data.diasReciboPropuestas);
						$("#diasCuadrosComparativos").val(data.diasCuadrosComparativos);
						$("#diasLegalizacionContrato").val(data.diasLegalizacionContrato);
						$("#diasFabricacion").val(data.diasFabricacion);
						$("#diasInsumosObra").val(data.diasInsumosObra);

						$("#fechaRealElaboracionPliegos").val(data.fechaRealElaboracionPliegos);
						$("#fechaRealIngresoLicify").val(data.fechaRealIngresoLicify);
						$("#fechaRealEntregaPliegos").val(data.fechaRealEntregaPliegos);
						$("#fechaRealReciboPropuestas").val(data.fechaRealReciboPropuestas);
						$("#fechaRealCuadrosComparativos").val(data.fechaRealCuadrosComparativos);
						$("#fechaRealLegalizacionContrato").val(data.fechaRealLegalizacionContrato);
						$("#fechaRealFabricacion").val(data.fechaRealFabricacion);
						$("#fechaRealInsumosObra").val(data.fechaRealInsumosObra);
						$("#fechaRealInicioProyectadaContrato").val(data.fechaRealInicio);

						$("#observacionesContrato").val(data.observacionesContrato);

						calcularProcesoContratacionTeorico('');

						if(data.fechaRealLegalizacionContrato != "" || data.fechaRealFabricacion != "" || data.fechaRealInsumosObra != "" || data.fechaRealInicio != ""){
							if (data.idProveedorAdjudicado != "") {
								verificarProveedor('idProveedorExistente');
							}else {
								verificarProveedor('actividadesDelContrato');
							}
						}else{
							document.getElementById('nitAdjudicado').value = ""
						}

						guardar_modificar();

						$("#modal-body-texto-Contrato").html("Estado del Proceso de Contratación: <b>" + data.paqueteContratacion + "</b><br>Tipo de Contrato: <b>" + data.tipoPaquete + "</b>");

						only_once = false;

						$("#btn_cancelar_editar").on("click", function(){
								only_once = true;
						});
					}	
				}
				cancelarEdicionFila();
		  	});
		}

		/*Toma los datos de la fila en la que se presionó el Definir Contratos*/
		var obtener_data_definirContratos = function() {
		  	var permiso = document.getElementById('permiso').value;
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
			var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			var alturatabla = (alturahoja - posicionInicioTabla - 270) + "px";
			$("#modalDefinirContratos").on("shown.bs.modal", function(){
				var tableDefinirContratos = $("#dt_definirContratos").DataTable({
					"dom": "<'row filaMensajesDefinirContratos'<'col-md-2 ml-auto p-0'<'toolbarResetFiltroDefinirContratos'>><'col-md-4 ml-auto mr-2 p-0'<'toolbarFiltroDefinirContratos'>>>t<'row'<'col-md-12'>><'clear'>",
					"destroy": true,
					"orderFixed":[4, "asc"],
					/*                "autoWidth": true,*/
					"fixedHeader": false,
					"scrollX": true,				
					//                console.log($(document).height());
					"scrollY": alturatabla,
					/*                "scrollCollapse": false,*/
					"responsive": true,
					"paging": false,
					"ajax": {
					"method": "POST",
					"url": "../pdc/listar_pdc.php?db="+db+"&semana="+Max_Semana+"&definirContratos=1"
					},
					"lengthMenu": [100, 200, 500],
					'columnDefs': [
						{ orderable: false, targets: '_all' },

						{
							'targets': [1],
							'width':'30%',
						},
						{
							'targets': [2],
							'width':'50%',
						},
						{
							'targets': [3],
							'width':'20%',
						},
					],

					"columns":[
						{"data":"consecutivo", "visible":false},
						{"data":"tipoPaquete"},
						{"data":"paqueteContratacion"},
						{"data":"numeroSubcontratos",
							'render': function(data, type, row, meta) {
								data = "<input type='number' class='numeroSubcontratos' style='width:80%' step='1' min='1' value=" + data + ">";
			
								return data;
							}
						},
						{"data":"subcontratoPaquete", "visible":false},
						{"data":"ordenVisual", "visible":false},
					],

					"createdRow": function( row, data, dataIndex){			
					},

					"language": idioma_espanol
				});

				$("div.toolbarFiltroDefinirContratos").html('<div style="display:flex; margin-left:auto"><input id="input_buscadorDefinirContratos" type="text" class="input_buscadorDefinirContratos form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"></div>');

				activarBuscadorDefinirContratos("#dt_definirContratos tbody", tableDefinirContratos);
				editarDefinirContratos();
			});
			
			$("#modalDefinirContratos").modal("show");

		}

		var activarBuscadorDefinirContratos = function(tbody, table){
			$('#input_buscadorDefinirContratos').on("keyup", function (e) {
				table.search( this.value ).draw();
			});

			if($("#input_buscadorDefinirContratos").val() != ''){
				table.search( $("#input_buscadorDefinirContratos").val()).draw();
			}
		}

		var editarDefinirContratos = function(){
			var tableDefinirContratos = $("#dt_definirContratos").DataTable();

			$("#dt_definirContratos tbody").on("change", "td", function(e) {
				e.stopPropagation();
				var colIndex =tableDefinirContratos.cell(this).index().column;
				var rowIndex =tableDefinirContratos.cell(this).index().row;

				var nuevoValor = this.children[0].value;

				tableDefinirContratos.cell(this).data(nuevoValor).draw();
			});
		}

		var guardar_DefinirContratos = function() {

			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var opcion = "guardar_DefinirContratos";
			var tableDefinirContratos = $("#dt_definirContratos").DataTable();
			var numeroSubcontratosFinal = "";
			for(i = 0; i < tableDefinirContratos.rows(  ).count(); i++){
				if(tableDefinirContratos.row( i ).data().numeroSubcontratos > 1){
					numeroSubcontratosFinal += "{\"consecutivo\":\"" + tableDefinirContratos.row( i ).data().consecutivo + "\", \"numeroSubcontratos\":\"" + tableDefinirContratos.row( i ).data().numeroSubcontratos + "\"},";
				}
			}
			numeroSubcontratosFinal = "{\"numeroSubcontratos\": [" + numeroSubcontratosFinal.substring(0, (numeroSubcontratosFinal.length - 1)) + "]}";
			frm = "numeroSubcontratos=" + numeroSubcontratosFinal + "&opcion=" + opcion + "&semana=" + semana + "&db=" + db;
			console.log(frm);

			$.ajax({
				method: "POST",
				url: "../pdc/guardar_pdc.php",
				contenttype:"charset=utf-8",
				data: frm,
			}).done( function( info ){
				var json_info = JSON.parse( info );
				if(json_info == "sinModificaciones" || json_info == "conModificaciones"){
					$("#modalDefinirContratos").modal("hide");
					actualizarPDC();
				}
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
		    url: "../pdc/guardar_pdc.php",
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
		    var json_info = JSON.parse(info);
		    //console.log(json_info);
		    if (json_info == "No Existe") {
		      idProveedorExistente.value = '';
		      subcontratistaAdjudicado.value = '';
		      correoAdjudicado.value = '';
		      subcontratistaAdjudicado.removeAttribute("readonly", true);
		      correoAdjudicado.removeAttribute("readonly", true);
		      document.getElementById('mensajeModalInformacionAdjudicado').innerHTML = "Se creará un nuevo proveedor en la base de datos";
		    } else {
		      nitAdjudicado.value = json_info['data'][0]['NIT'];
		      subcontratistaAdjudicado.value = json_info['data'][0]['subcontratista'];
		      correoAdjudicado.value = json_info['data'][0]['correo_contacto'];
		      idProveedorExistente.value = json_info['data'][0]['Id'];
		      subcontratistaAdjudicado.setAttribute("readonly", true);
		      correoAdjudicado.setAttribute("readonly", true);
		      document.getElementById('mensajeModalInformacionAdjudicado').style.color = "blue";
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
		    diasIngresoLicify = parseInt(document.getElementById('diasIngresoLicify').value),
		    fechaRealIngresoLicify = null,
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
		  if (isNaN(diasIngresoLicify)) {
		    diasIngresoLicify = 0;
		  }
		  if (isNaN(diasElaboracionPliegos)) {
		    diasElaboracionPliegos = 0;
		  }
		  var diasTotales = diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify + diasElaboracionPliegos;
		  var opcion = "recalcularProcesoContratacion";
		  db = document.getElementById('baseDatos').value;
		  $.ajax({
		    method: "POST",
		    url: "../pdc/guardar_pdc.php",
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
		      "diasIngresoLicify": diasIngresoLicify,
		      "diasElaboracionPliegos": diasElaboracionPliegos,
		      "fechaRealInsumosObra": fechaRealInsumosObra,
		      "fechaRealFabricacion": fechaRealFabricacion,
		      "fechaRealLegalizacionContrato": fechaRealLegalizacionContrato,
		      "fechaRealCuadrosComparativos": fechaRealCuadrosComparativos,
		      "fechaRealReciboPropuestas": fechaRealReciboPropuestas,
		      "fechaRealEntregaPliegos": fechaRealEntregaPliegos,
		      "fechaRealIngresoLicify": fechaRealIngresoLicify,
		      "fechaRealElaboracionPliegos": fechaRealElaboracionPliegos
		    }
		  }).done(function(info) {
		    var json_info = JSON.parse(info);
		    document.getElementById('fechaElaboracionPliegosTeorica').value = json_info["data"][0]["fechaElaboracionPliegos"];
		    document.getElementById('fechaIngresoLicifyTeorica').value = json_info["data"][0]["fechaIngresoLicify"];
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
		    diasIngresoLicify = parseInt(document.getElementById('diasIngresoLicify').value),
		    fechaRealIngresoLicify = document.getElementById('fechaRealIngresoLicify').value,
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
		  if (finSemanaActual >= fechaRealInicioProyectadaContrato && finSemanaActual >= fechaRealInsumosObra && finSemanaActual >= fechaRealFabricacion && finSemanaActual >= fechaRealLegalizacionContrato && finSemanaActual >= fechaRealCuadrosComparativos && finSemanaActual >= fechaRealReciboPropuestas && finSemanaActual >= fechaRealEntregaPliegos && finSemanaActual >= fechaRealIngresoLicify && finSemanaActual >= fechaRealElaboracionPliegos) {
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
		    if (isNaN(diasIngresoLicify) || diasIngresoLicify < 0) {
		      diasIngresoLicify = 0;
		      document.getElementById('diasIngresoLicify').value = "";
		    }
		    if (isNaN(diasElaboracionPliegos) || diasElaboracionPliegos < 0) {
		      diasElaboracionPliegos = 0;
		      document.getElementById('diasElaboracionPliegos').value = "";
		    }
		    var diasTotales = diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify + diasElaboracionPliegos;
		    var opcion = "recalcularProcesoContratacion";
		    db = document.getElementById('baseDatos').value;
		    $.ajax({
		      method: "POST",
		      url: "../pdc/guardar_pdc.php",
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
		        "diasIngresoLicify": diasIngresoLicify,
		        "diasElaboracionPliegos": diasElaboracionPliegos,
		        "fechaRealInsumosObra": fechaRealInsumosObra,
		        "fechaRealFabricacion": fechaRealFabricacion,
		        "fechaRealLegalizacionContrato": fechaRealLegalizacionContrato,
		        "fechaRealCuadrosComparativos": fechaRealCuadrosComparativos,
		        "fechaRealReciboPropuestas": fechaRealReciboPropuestas,
		        "fechaRealEntregaPliegos": fechaRealEntregaPliegos,
		        "fechaRealIngresoLicify": fechaRealIngresoLicify,
		        "fechaRealElaboracionPliegos": fechaRealElaboracionPliegos
		      }
		    }).done(function(info) {
		      var json_info = JSON.parse(info);
		      document.getElementById('fechaElaboracionPliegos').value = json_info["data"][0]["fechaElaboracionPliegos"];
		      document.getElementById('fechaIngresoLicify').value = json_info["data"][0]["fechaIngresoLicify"];
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
		      selectoresFecha("IngresoLicify");
		      selectoresFecha("ElaboracionPliegos");
		      selectoresFecha("InicioProyectadaContrato");
		      generarEstadoProceso();
		      mostrarInputsOcultos();
		    });
		  } else {
		    if (inputCambiado != "") {
		      window.alert("No se puede asignar una fecha mayor a la fecha de fin de la presente semana (" + finSemanaActual + ").");
		      document.getElementById('fechaReal' + inputCambiado).value = '';
		      selectoresFecha("InsumosObra");
		      selectoresFecha("Fabricacion");
		      selectoresFecha("LegalizacionContrato");
		      selectoresFecha("CuadrosComparativos");
		      selectoresFecha("ReciboPropuestas");
		      selectoresFecha("EntregaPliegos");
		      selectoresFecha("IngresoLicify");
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
		    ["IngresoLicify", "Ingresando el contrato a Licify"],
		    ["EntregaPliegos", "Entregando pliegos a los proveedores invitados"],
		    ["ReciboPropuestas", "Recibiendo propuestas de los proveedores invitados"],
		    ["CuadrosComparativos", "Elaborando cuadros comparativos, análisis y adjudicación del contrato"],
		    ["LegalizacionContrato", "En proceso de legalización del contrato"],
		    ["Fabricacion", "En periodo de fabricación, producción, importaciones, transportes, movilización, etc"],
		    ["InsumosObra", "En proceso de llegada de recursos, insumos y personal a la obra"],
		    ["InicioProyectadaContrato", "Proceso de contratación finalizado y actividades del contrato iniciadas"]
		  ];
		  var posicion = -1;
		  var deberiaHoy = -1;
		  var fechaActual = document.getElementById("fechaActual").value;
		  var fechaEvaluar = "";
		  for (i = 0; i < 9; i++) {
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
		  if (posicion >= deberiaHoy) {
		    divDiagnostico.innerHTML = "A tiempo";
				//console.log((new Date(document.getElementById("fechaReal" + pasos[posicion][0]).value) -  new Date(document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value))/(1000 * 3600 * 24));
		    // if ((posicion == -1 && deberiaHoy == -1)) {
		    //   divDiagnostico.innerHTML = "A tiempo";
		    // } else if (document.getElementById("fechaReal" + pasos[posicion][0]).value <= document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value) {
		    //   divDiagnostico.innerHTML = "A tiempo";
		    // } else {
		    //   divDiagnostico.innerHTML = "Atrasado!!";
		    // }
		  } else {
		    divDiagnostico.innerHTML = "Atrasado!!";
		  }
		  if (divEstadoProceso.innerHTML == pasos[8][1]) {
		    if (document.getElementById("fechaReal" + pasos[8][0]).value > document.getElementById("fecha" + pasos[8][0] + "Teorica").value) {
		      divDiagnostico.innerHTML = "Terminado con retrasos";
		      document.getElementById("estadoProceso").value = "Terminado con retrasos";
		    } else {
		      divDiagnostico.innerHTML = "Terminado a tiempo";
		      document.getElementById("estadoProceso").value = "Terminado a tiempo";
		    }
		  } else {
		    document.getElementById("estadoProceso").value = divDiagnostico.innerHTML + "; " + divEstadoProceso.innerHTML;
		  }
		  generarIconoReal(pasos, posicion);
		  for (i = posicion; i < 9; i++) {
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
			  console.log("hola");
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
		  for (i = 0; i < 9; i++) {
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

		var actualizarPDC=function(){
			$("#modal_spinner").modal("show");
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			$.ajax({
				method:"POST",
				url: "../pdc/actualizar_pdc.php?db="+db,
				contenttype:"charset=utf-8",
				data: {"db": db, "semana": semana}
			}).done( function( info ){
				var json_info = JSON.parse( info );
				// console.log(json_info);
				// console.log(json_info["respuesta"]);
				if(json_info["respuesta"]=="BIEN"){
					location.assign("pdc.php");
					// document.getElementById('mensaje').style.color = "blue";
					// document.getElementById('mensaje').innerHTML = "El plan de compras se ha actualizado";
					// $("#mensaje").fadeOut(10000, function(){
					// 	$(this).html("");
					// 	$(this).fadeIn(3000);
					// });
				}
			});
		}

		var quitar_guion=function(){
				var NIT=$("#nitAdjudicado").val();
				NIT_nuevo = NIT.replace("-","","gi");
				NIT_nuevo = NIT_nuevo.replace(".","","gi");
				$("#nitAdjudicado").val(NIT_nuevo);
		}

		var selectoresFecha=function(fecha){
			$("#fechaReal" + fecha).datepicker( "destroy" );
			dia = new Date($( "#fecha" + fecha ).val());
			dia = new Date(dia.getFullYear()+"-"+(dia.getMonth()+1)+"-"+(dia.getDate()+1));
			$( "#fechaReal" + fecha ).datepicker({dateFormat: 'yy-mm-dd',
																					 changeMonth: true,
																					 changeYear: true,
																					 showOtherMonths: true,
																					 selectOtherMonths: true,
																					 defaultDate:dia,
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
			$("#btn_guardar_pdc").one("click", function(e){
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
					url: "../pdc/guardar_pdc.php",
          			contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					var json_info = JSON.parse( info );
					// console.log(json_info);
					// mostrar_mensaje( json_info );
					if(json_info == "OK"){
						$("#modalContrato #cuadro4").scrollTop(0);
						$("#modalContrato").modal("hide");
						recargarTabla('');

						document.getElementById('mensajeActualizacion').style.color = "blue";
						document.getElementById('mensajeActualizacion').innerHTML = "El paquete de contratación <b>\"" + paquete + "\"</b> se ha actualizado";
						$("#mensajeActualizacion").fadeOut(10000, function(){
							$(this).html("");
							$(this).fadeIn(3000);
						});
					}else{
						document.getElementById('mensajeModalContrato').style.color = "red";
						document.getElementById('mensajeModalContrato').innerHTML = json_info;
						$("#mensajeModalContrato").fadeOut(10000, function(){
							$(this).html("");
							$(this).fadeIn(3000);
						});
						guardar_modificar();
					}
				});
			});
		}

		/*Toma los datos de la fila en la que se presionó el botón eliminar*/
		var obtener_id_eliminar = function(tbody, table) {
			var permiso = document.getElementById('permiso').value;
		  	if (permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="R" || permiso=="DCV" || permiso=="V" || permiso=="C") {
		  	} else {
				$(tbody).on("click", "button.eliminar", function() {
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#Id").val(data.consecutivo);
					var opcion=$("#opcion").val("eliminar");
					var paqueteContratacion=$("#nombrePaqueteContratacion").val(data.paqueteContratacion);
					var texto=$("#modal-body-texto-eliminar").html("¿Desea eliminar el subcontrato <b>" + data.paqueteContratacion + " (Subcontrato " + data.subcontratoPaquete + ")" + "</b> definitivamente del proyecto?");
					$("#modalEliminar").modal("show");
			  	});
		  	}
		}

		/* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
		var eliminar = function() {
		  	$("#eliminar-usuario").on("click", function() {
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
		    	var Id = $("#Id").val(),
				paqueteContratacion=$("#nombrePaqueteContratacion").val(),
		      	opcion = "eliminar";
				console.log(Id, opcion);
		    	$.ajax({
					method: "POST",
					url: "../pdc/guardar_pdc.php?",
					contenttype: "charset=utf-8",
					data: {
						"Id": Id,
						"opcion": opcion,
						"paqueteContratacion": paqueteContratacion,
						"db": db,
						"semana": semana
					}
				}).done(function(info) {
					var json_info = JSON.parse(info);
					//console.log(json_info)
					mostrar_mensaje(json_info);
					recargarTabla('');
				});
			});
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function( informacion ){
			var texto = "", color = "";
			if( informacion.respuesta == "BIEN" ){
					texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
					color = "#379911";
			}

						if( informacion.respuesta == "ERROR"){
					texto = "<strong>Error</strong>, no se ejecutó la consulta.";
					color = "#C9302C";
			}

						if( informacion.respuesta == "EXISTE" ){
					texto = "<strong>Información!</strong> el usuario ya existe.";
					color = "#C9302C";
			}

						if( informacion.respuesta == "VACIO" ){
					texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
					color = "#C9302C";
			}
						if( informacion.respuesta == "CONFIRMAR" ){
					texto = "<strong>Advertencia!</strong> Por favor confirmar correctamente la dirección de correo.";
					color = "#C9302C";
			}
						if(informacion.respuesta=="BIEN"){
								$("#cuadro2").slideUp("slow");
								$("#cuadro1").slideDown("slow");
								$("#cuadro3").slideDown("slow");
								$("#mensajeActualizacion").html( texto ).css({"color": color });
								$("#mensajeActualizacion").fadeOut(10000, function(){
												$(this).html("");
												$(this).fadeIn(3000);
					 });
						} else{
								$(".mensaje").html( texto ).css({"color": color });
								$(".mensaje").fadeOut(10000, function(){
												$(this).html("");
												$(this).fadeIn(3000);
					 });
						}
				}

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

		var recargarTabla = function(opcion) {
		  var posicion = $('.dataTables_scrollBody').scrollTop();
		  var table = $('#dt_cliente').DataTable();
		  if (opcion == "listar") {
		    //$('#dt_cliente').empty();
		    //listar();
				table.ajax.reload();
				obtener_data_editar("#dt_cliente tbody", table);
		  } else {
		    table.ajax.reload();
		    obtener_data_editar("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
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
