<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="controlCambios">
		<input type="hidden" id="Id" name="Id" value="">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="codigo" name="codigo" value="">
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
						<th>Id</th>
						<th>Solicitante</th>
						<th>Detalle Solicitante</th>
						<th>Fecha de Solicitud</th>
						<th>Prioridad</th>
						<th>Tipo de Cambio</th>
						<th>Responsable</th>
						<th>Detalle</th>
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
							<select style="width:80%" id="buscadorSolicitanteCambio">
								<option value="">Todas</option>
								<option value="Obra">Obra</option>
								<option value="Cliente">Cliente</option>
								<option value="Interventoría">Interventoría</option>
								<option value="Otro">Otro</option>
							</select>
						</th>
						<th></th>
						<th><input type="date" id="buscadorFechaSolicitud" style="width:80%"></th>
						<th>
							<select id="buscadorPrioridad">
								<option value="">Todas</option>
								<option value="Alta">Alta</option>
								<option value="Media">Media</option>
								<option value="Baja">Baja</option>
							</select>
						</th>
						<th><input type="text" id="buscadorTipoCambio" placeholder="Buscar" style="width:80%"></th>
						<th>
							<select style="width:80%" id="buscadorResponsableDefinicion">
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
						<th><input type="text" id="buscadorCostoDirecto" placeholder="Buscar" style="width:80%"></th>
						<th><input type="text" id="buscadorValorAprobado" placeholder="Buscar" style="width:80%"></th>
						<th></th>
						<th></th>
						<th></th>
						<th><input type="date" id="buscadorFechaTentativaDefinicion" style="width:80%"></th>
						<th><input type="date" id="buscadorFechaEntregaInterventoria" style="width:80%"></th>
						<th><input type="text" placeholder="Buscar" style="width:80%"></th>
						<th><input type="date" id="buscadorFechaDefinicion" style="width:80%"></th>
						<th>
							<select style="width:80%" id="buscadorAprobacion">
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

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">

		<div class="modal_ordenDeCambio modal fade" id="modalordenDeCambio" role="dialog" aria-labelledby="modal_ordenDeCambioLabel" data-keyboard="false">
				<div class="modal-dialog modal-xl" role="document">
					<div class="modal-content" id="modalordenDeCambioContent">
						<div class="modal-header p-0">
							<div class="col-sm-12 p-0 h-100">
								<div class="row d-flex align-items-center pt-2 pb-2">
									<div class="col-sm-3 m-auto" style="max-width: 50%">
										<img src="../imagenes/logoHorizontal.png" class="img-fluid" alt="Responsive image">
									</div>
									<div class="col-sm m-auto">
										<h1 class="modal-title" id="modalordenDeCambioLabel">
											<b><p class="modal-body-texto-ordenDeCambio text-center  mb-0" id="modal-body-texto-ordenDeCambio">Orden de Cambio</p></b>
										</h1>
									</div>
									<div class="col-sm-3 m-auto" style="max-width: 50%">
										<img src="../imagenes/etiquetaConstructoraAIASAS.png" class="img-fluid" alt="Responsive image">
									</div>
									<button type="button" class="close col-sm-1" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" onclick='cerrarTodosModales();recargarTabla("listar")'>&times;</span></button>
								</div>
							</div>
						</div>
						<div class="modal-body">
							<form class="formOrdenCambio form-horizontal" action="" method="POST">
								

								<!-- Se crean los inputs del formulario de contratos de suministro -->
								<div class="col-sm-12 p-0 mb-4 border-2 border-top-0 rounded border-dark">
									<div class="tituloFormularioCambios form-group mb-0">
										<h3 id='form_general'>Información General</h3>
									</div>
									<div class="row mb-3">
										<div class="form-group col-sm mt-0 mb-0 pt-3 pb-3" style="height:100%">
											<label for="inputConsecutivo"><b>Número de Orden</b></label>
											<input type="number" class="form-control" id="inputConsecutivo" name="inputConsecutivo" placeholder="Consecutivo" autocomplete="off" readonly>
										</div>
										<div class="form-group col-sm mt-0 mb-0 pt-3 pb-3" style="height:100%">
											<label for="inputProyecto"><b>Proyecto</b></label>
											<input type="text" class="form-control" name="inputProyecto" id="inputProyecto" placeholder="Proyecto" autocomplete="off" readonly>
										</div>
									</div>
									<div class="row border-bottom">
										<div class="form-group col-sm mt-0 mb-0 pb-3" style="height:100%">
											<label for="inputDirector"><b>Director de Obra</b></label>
											<input type="text" class="form-control" name="inputDirector" id="inputDirector" placeholder="Director" autocomplete="off" readonly>
										</div>
										<div class="form-group col-sm mt-0 mb-0 pb-3" style="height:100%">
											<label for="inputFechaSolicitud"><b>Fecha de Solicitud</b></label>
											<input type="text" class="form-control" name="inputFechaSolicitud" id="inputFechaSolicitud" placeholder="Fecha de Solicitud" autocomplete="off" readonly>
										</div>
									</div>
									<div class="row border-bottom">
										<div class="form-group col-sm-7 border-right mt-0 mb-0 pt-3 pb-3" style="height:100%">
											<div class="form-group mb-0">
												<label for="radioSolicitanteCambio"><b>Solicitante del Cambio</b></label>
											</div>
											<div class="form-check form-check-inline" id="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioObra" value="1" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioObra">Obra</label>
											</div>
											<div class="form-check form-check-inline" id="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioCliente" value="2" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioCliente">Cliente</label>
											</div>
											<div class="form-check form-check-inline" id="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioInterventoria" value="3" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = true; document.getElementById('inputDetalleSolicitanteOtro').value=''">
												<label class="form-check-label" for="inputSolicitanteCambioInterventoria">Interventoría</label>
											</div>
											<div class="form-check form-check-inline col-sm-6" id="radioSolicitanteCambio">
												<input type="radio" class="form-check-input" name="inputSolicitanteCambio" id="inputSolicitanteCambioOtro" value="4" onclick="document.getElementById('inputDetalleSolicitanteOtro').disabled = false;">
												<label class="form-check-label" for="inputSolicitanteCambioOtro">Otro:&nbsp&nbsp</label>
												<input type="text" class="form-control" name="inputDetalleSolicitanteOtro" id="inputDetalleSolicitanteOtro" placeholder="¿Quien?" disabled autocomplete="off">
											</div>
										</div>
										<div class="form-group col-sm-5 mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="radioPrioridad"><b>Prioridad</b></label>
											</div>
											<div class="form-check form-check-inline" id="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadAlta" value="1">
												<label class="form-check-label" for="inputPrioridadAlta">Alta</label>
											</div>
											<div class="form-check form-check-inline" id="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadMedia" value="2">
												<label class="form-check-label" for="inputPrioridadMedia">Media</label>
											</div>
											<div class="form-check form-check-inline" id="radioPrioridad">
												<input type="radio" class="form-check-input" name="inputPrioridad" id="inputPrioridadBaja" value="3">
												<label class="form-check-label" for="inputPrioridadBaja">Baja</label>
											</div>
										</div>
									</div>
									<div class="row">									
										<div class="form-group col-sm-5 border-right mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="checkboxTipoCambio"><b>Tipo de Cambio</b></label>
											</div>
											<div class="row">
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioAlcance" id="inputTipoCambioAlcance" value="1">
													<label class="form-check-label" for="inputTipoCambioAlcance">Alcance</label>
												</div>
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCronograma" id="inputTipoCambioCronograma" value="1">
													<label class="form-check-label" for="inputTipoCambioCronograma">Cronograma</label>
												</div>	
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCosto" id="inputTipoCambioCosto" value="1">
													<label class="form-check-label" for="inputTipoCambioCosto">Costo</label>
												</div>	
											</div>
											<div class="row mt-1">
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioCalidad" id="inputTipoCambioCalidad" value="1">
													<label class="form-check-label" for="inputTipoCambioCalidad">Calidad</label>
												</div>	
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioRiesgo" id="inputTipoCambioRiesgo" value="1">
													<label class="form-check-label" for="inputTipoCambioRiesgo">Riesgo</label>
												</div>	
												<div class="form-check form-check-inline" id="checkboxTipoCambio">
													<input type="checkbox" class="form-check-input" name="inputTipoCambioRecurso" id="inputTipoCambioRecurso" value="1">
													<label class="form-check-label" for="inputTipoCambioRecurso">Recurso</label>
												</div>	
											</div>								
										</div>
										<div class="form-group col-sm-7 mt-0 mb-0 pt-3 pb-3">
											<div class="form-group mb-0">
												<label for="radioResponsableSolucion"><b>Responsable de la Definición de Cambio</b></label>
											</div>
											<div class="form-check form-check-inline" id="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionObra" value="1" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionObra">Obra</label>
											</div>
											<div class="form-check form-check-inline" id="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionCliente" value="2" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionCliente">Cliente</label>
											</div>
											<div class="form-check form-check-inline" id="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionInterventoria" value="3" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = true; document.getElementById('inputDetalleResponsableSolucion').value=''">
												<label class="form-check-label" for="inputResponsableSolucionInterventoria">Interventoría</label>
											</div>
											<div class="form-check form-check-inline col-sm-6" id="radioResponsableSolucion">
												<input type="radio" class="form-check-input" name="inputResponsableSolucion" id="inputResponsableSolucionOtro" value="4" onclick="document.getElementById('inputDetalleResponsableSolucion').disabled = false;">
												<label class="form-check-label" for="inputResponsableSolucionOtro">Otro:&nbsp&nbsp</label>
												<input type="text" class="form-control col-sm-11" name="inputDetalleResponsableSolucion" id="inputDetalleResponsableSolucion" placeholder="¿Quien?" disabled autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 mb-4 border-2 border-top-0 rounded border-dark">
									<div class="tituloFormularioCambios form-group mb-0">
										<h3 id='form_general'>Detalle del Cambio</h3>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Justificación</b></span>
												</div>
												<textarea class="form-control" name="inputJustificacion" id="inputJustificacion" rows="4" onkeyup="contadorTextarea(this,'contadorJustificacion',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorJustificacion" class="">0 de 500 caracteres permitidos.</p>
											</div>						
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Descripción <br>del Cambio</b></span>
												</div>
												<textarea class="form-control" name="inputDescripcion" id="inputDescripcion" rows="4" onkeyup="contadorTextarea(this,'contadorDescripcion',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorDescripcion" class="">0 de 500 caracteres permitidos.</p>
											</div>										
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Incidencia en <br>el Alcance</b></span>
												</div>
												<textarea class="form-control" name="inputIncidenciaAlcance" id="inputIncidenciaAlcance" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaAlcance',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaAlcance" class="">0 de 500 caracteres permitidos.</p>
											</div>										
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-0">
											<div class="input-group-prepend ml-3 mr-0 pr-0">
												<span class="input-group-text w-100 d-flex justify-content-center"><b>Incidencia <br>en el <br>Cronograma</b></span>
											</div>
											<div class="input-group ml-0 pl-0" style="width:87%">
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0 w-75">
													<div class="input-group col-sm-8 w-100">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Días Según Cronograma</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputTiempoCronograma" id="inputTiempoCronograma" data-type="number" autocomplete="off">
													</div>									
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0 w-75">
													<div class="input-group col-sm-8 w-100">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Días Adicionales</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputTiempoCronogramaAfectado" id="inputTiempoCronogramaAfectado" data-type="number" autocomplete="off">
													</div>									
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0 w-75">
													<div class="input-group col-sm-8 w-100">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>% Afectación Cronograma</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputPorcentajeAfectacionCronograma" id="inputPorcentajeAfectacionCronograma" data-type="text" autocomplete="off" readonly>
													</div>									
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-12">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Observaciones</b></span>
														</div>
														<textarea class="form-control" name="inputIncidenciaCronograma" id="inputIncidenciaCronograma" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaCronograma',500)" autocomplete="off"></textarea>
													</div>									
												</div>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaCronograma" class="">0 de 500 caracteres permitidos.</p>
											</div>	
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-0">
											<div class="input-group-prepend ml-3 mr-0 pr-0">
												<span class="input-group-text w-100 d-flex justify-content-center"><b>Incidencia <br>en el <br>Presupuesto</b></span>
											</div>
											<div class="input-group ml-0 pl-0" style="width:87%">
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0 w-100">
													<div class="input-group col-sm-8 w-100">
														<div class="input-group-prepend" style="width:600px;max-width:70%">
															<span class="input-group-text w-100"><b>Costo Actividad Según Presupuesto (Incluye AIU + IVA)</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:30%" name="inputValorPresupuesto" id="inputValorPresupuesto" data-type="currency" autocomplete="off">
													</div>									
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0 w-75">
													<div class="input-group col-sm-8 w-100">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Costo Directo</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputCostoDirecto" id="inputCostoDirecto" data-type="currency" autocomplete="off">
													</div>									
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-8">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Costo Directo + AIU</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputCostoDirectoAIU" id="inputCostoDirectoAIU" data-type="currency" autocomplete="off">
													</div>										
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-8">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Costo Directo + AIU + IVA</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputCostoDirectoAIUIVA" id="inputCostoDirectoAIUIVA" data-type="currency" autocomplete="off">
													</div>										
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-8">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Valor Aprobado</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputValorAprobado" id="inputValorAprobado" data-type="currency" autocomplete="off">
													</div>										
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-8">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>% Afectación Presupuesto</b></span>
														</div>
														<input type="text" class="form-control col-sm-11" style="width:200px;max-width:40%" name="inputPorcentajeAfectacionPresupuesto" id="inputPorcentajeAfectacionPresupuesto" data-type="text" autocomplete="off" readonly>
													</div>										
												</div>
												<div class="input-group col-sm-12 m-0 pb-1 pt-0 pr-0 pl-0">
													<div class="input-group col-sm-12">
														<div class="input-group-prepend" style="width:200px;max-width:40%">
															<span class="input-group-text w-100"><b>Observaciones</b></span>
														</div>
														<textarea class="form-control" name="inputIncidenciaPresupuesto" id="inputIncidenciaPresupuesto" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaPresupuesto',500)" autocomplete="off"></textarea>
													</div>									
												</div>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaPresupuesto" class="">0 de 500 caracteres permitidos.</p>
											</div>	
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Incidencia <br>en la <br>Calidad</b></span>
												</div>
												<textarea class="form-control" name="inputIncidenciaCalidad" id="inputIncidenciaCalidad" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaCalidad',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaCalidad" class="">0 de 500 caracteres permitidos.</p>
											</div>										
										</div>
									</div>
									<div class="row">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Incidencia <br>en el <br>Riesgo</b></span>
												</div>
												<textarea class="form-control" name="inputIncidenciaRiesgo" id="inputIncidenciaRiesgo" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaRiesgo',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaRiesgo" class="">0 de 500 caracteres permitidos.</p>
											</div>										
										</div>
									</div>
									<div class="row border-bottom">
										<div class="input-group col-sm-12 mt-0 mb-0 pt-2 pb-2">
											<div class="input-group col-sm-12">
												<div class="input-group-prepend">
													<span class="input-group-text"><b>Incidencia <br>en el <br>Recurso</b></span>
												</div>
												<textarea class="form-control" name="inputIncidenciaRecurso" id="inputIncidenciaRecurso" rows="4" onkeyup="contadorTextarea(this,'contadorIncidenciaRecurso',500)" autocomplete="off"></textarea>
											</div>
											<div class="input-group col-sm-12 d-flex justify-content-end">
												<p id="contadorIncidenciaRecurso" class="">0 de 500 caracteres permitidos.</p>
											</div>										
										</div>
									</div>
									<div class="row">
										<div class="col-sm-6 d-flex justify-content-center border-right">
											<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3" style="height:100%">
												<label for="inputFechaEntregaInterventoria"><b>Fecha de Entrega a Interventoría</b></label>
												<input type="text" class="form-control" name="inputFechaEntregaInterventoria" id="inputFechaEntregaInterventoria" placeholder="Fecha de Entrega a Interventoría" autocomplete="off">
											</div>
										</div>
										<div class="col-sm-6 d-flex justify-content-center">
											<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3" style="height:100%">
												<label for="inputFechaTentativaDefinicion"><b>Fecha Tentativa de Definición</b></label>
												<input type="text" class="form-control" name="inputFechaTentativaDefinicion" id="inputFechaTentativaDefinicion" placeholder="Fecha Tentativa de Definición" autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 mb-4 border-2 border-top-0 rounded border-dark">
									<div class="tituloFormularioCambios form-group mb-0">
										<h3 id='form_general'>Aprobación</h3>
									</div>
									<div class="row">
										<div class="form-group col-sm-8 mt-0 mb-0 pt-3 pb-3 border-right" style="height:100%">
											<div class="form-group mb-0">
												<label for="radioAprobacion"><b>Estado de Aprobación</b></label>
											</div>
											<div class="form-check form-check-inline" id="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionEstudio" value="4">
												<label class="form-check-label" for="inputAprobacionEstudio">En Estudio</label>
											</div>
											<div class="form-check form-check-inline" id="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionAprobado" value="1">
												<label class="form-check-label" for="inputAprobacionAprobado">Aprobado</label>
											</div>
											<div class="form-check form-check-inline" id="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionAprobadoRestricciones" value="2">
												<label class="form-check-label" for="inputAprobacionAprobadoRestricciones">Aprobado con Restricciones</label>
											</div>
											<div class="form-check form-check-inline" id="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionNoAprobado" value="3">
												<label class="form-check-label" for="inputAprobacionNoAprobado">No Aprobado</label>
											</div>
											<div class="form-check form-check-inline" id="radioAprobacion">
												<input type="radio" class="form-check-input" name="inputAprobacion" id="inputAprobacionDesistido" value="5">
												<label class="form-check-label" for="inputAprobacionDesistido">Desistido</label>
											</div>
										</div>
										<div class="col-sm-4 d-flex justify-content-center">
											<div class="form-group col-sm-12 mt-0 mb-0 pt-3 pb-3" style="height:100%">
												<label for="inputFechaDefinicion"><b>Fecha de Definición</b></label>
												<input type="text" class="form-control" name="inputFechaDefinicion" id="inputFechaDefinicion" placeholder="Fecha de Definición" autocomplete="off">
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-12 p-0 border-2 border-top-0 rounded border-dark">
									<div class="tituloFormularioCambios form-group mb-0">
										<h3 id='form_general'>Archivos de Soporte</h3>
									</div>
									<div class="row mt-4 mb-4 col-sm-12">
										<div class="col-sm-11 ml-auto mr-auto">
											<table id="dt_soportes" class="table table-bordered table-responsive-sm" style="width:100%">
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
												<button type="button" class="btn btn-primary btn-sm" onclick="agregarSoporte()">Agregar <i class="fas fa-plus"></i></button>
											</div>
										</div>	
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<div class="col-sm-12">
								<!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
								<div class="row">
									<div class="col-sm-12">
										<p class="mensaje">mensaje</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12 d-flex justify-content-end">
										<button id="btn_guardarOrden" type="button" class="btn btn-success ml-1 mr-1" onclick="guardar_modificar()">Guardar <i class="fas fa-save fa-lg"></i></i></button>
										<button id="btn_generarPDFOrden" type="button" class="btn btn-secondary ml-1 mr-1">Generar PDF <i class="fas fa-download fa-lg"></i></button>
										<button id="btn_cancelarOrden" type="button" class="btn btn-danger ml-1 mr-1" data-dismiss="modal" onclick='cerrarTodosModales();recargarTabla("listar")'>Cancelar <i class="fas fa-window-close fa-lg"></i></button>
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
		<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel" data-keyboard="false">
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
		        <button type="button" id="eliminarODC" class="btn btn-primary" data-dismiss="modal" data-toggle="modal" onclick='eliminar()'>Aceptar</button>
		        <!--data-target="#modal_CNP"-->
		        <button type="button" id="btn_cancelar_eliminar" class="btn btn-default" data-dismiss="modal" onclick='cerrarTodosModales();recargarTabla("listar")'>Cancelar</button>
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
	<!-- Librería jsPDF -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>
	<!-- Librería HTML2Canvas -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
	<!-- Tabulator -->
	<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.3.4/dist/js/tabulator.min.js"></script>
	<!--Script con la funcion que carga los datos generales del archivo-->
	<script type="text/javascript" src="../funciones_generales/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="../funciones_generales/js/funcionesGenerales6.js" charset="utf-8"></script>
	<!-- Bloquear el click derecho-->
	<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script type="text/javascript" src="controlCambios.js" charset="utf-8"></script>
</body>
</html>
