<!DOCTYPE html>
<html lang="es">
<head id="head">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColorsSuperFresh3" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_contratos" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="codigo" name="codigo" value="" aria-hidden="true">
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
						<th>SI1</th>
						<th>paqueteSI1</th>
						<th>SI2</th>
						<th>paqueteSI2</th>
						<th>SI3</th>
						<th>paqueteSI3</th>
						<th>SI4</th>
						<th>paqueteSI4</th>
						<th>SI5</th>
						<th>paqueteSI5</th>
						<th>S1</th>
						<th>paqueteS1</th>
						<th>S2</th>
						<th>paqueteS2</th>
						<th>S3</th>
						<th>paqueteS3</th>
						<th>S4</th>
						<th>paqueteS4</th>
						<th>S5</th>
						<th>paqueteS5</th>
						<th>MO1</th>
						<th>paqueteMO1</th>
						<th>MO2</th>
						<th>paqueteMO2</th>
						<th>MO3</th>
						<th>paqueteMO3</th>
						<th>MO4</th>
						<th>paqueteMO4</th>
						<th>MO5</th>
						<th>paqueteMO5</th>
						<th>OC1</th>
						<th>paqueteOC1</th>
						<th>OC2</th>
						<th>paqueteOC2</th>
						<th>OC3</th>
						<th>paqueteOC3</th>
						<th>OC4</th>
						<th>paqueteOC4</th>
						<th>OC5</th>
						<th>paqueteOC5</th>
						<th>Paquetes de Contratación Asociados</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<div class="modal_EditarContratos modal fade aia-modal" id="modalEditarContratos" tabindex="-1" role="dialog" aria-labelledby="modalEditarContratosLabel" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable ct-modal-dialog" role="document">
				<div class="modal-content ct-modal-content">
					<div class="modal-header ct-modal-header">
						<div class="modal-title ct-modal-title" id="modalEditarContratosLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h2 class="aia-modal__headline"><span class="modal-body-texto-EditarContratos" id="modal-body-texto-EditarContratos"></span></h2>
							<p class="aia-modal__subtitle">Configura los paquetes e insumos asociados a la actividad seleccionada.</p>
						</div>
						<button type="button" class="close ct-modal-close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true" onclick="recargarTabla('listar')">&times;</span>
						</button>
					</div>
					<div class="modal-body ct-modal-body">
						<div class="row">
							<div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12">
								<form id="formularioEditarContratos" class="form form-horizontal ct-contract-form" action="" method="POST" autocomplete="off">
									<div class="form-group ct-modalidad-group">
										<label class="ct-modalidad-label">Modalidad de Contratación</label>
										<div class="ct-checkbox-group">
											<label class="ct-checkbox-item"><input type="checkbox" id="modalidadSI" name="modalidades[]" value="SI"> Suministro e Instalación</label>
											<label class="ct-checkbox-item"><input type="checkbox" id="modalidadMO" name="modalidades[]" value="MO"> Mano de Obra</label>
											<label class="ct-checkbox-item"><input type="checkbox" id="modalidadS" name="modalidades[]" value="S"> Suministro</label>
											<label class="ct-checkbox-item"><input type="checkbox" id="modalidadOC" name="modalidades[]" value="OC"> Orden de Compra</label>
										</div>
									</div>
									<input type="hidden" id="tipoContrato" name="tipoContrato" value="">
									<input type="hidden" id="actividadModificar" name="actividadModificar" value="">
									<?php
                                    $contractSections = [
                                        [
                                            'id' => 'parametro_EditarContratosS',
                                            'title' => 'Contratos de Suministro',
                                            'packagePrefix' => 'paqueteS',
                                            'resourcePrefix' => 'S',
                                            'packageLabel' => 'Paquete Suministro',
                                            'resourceLabel' => 'Insumos Suministro',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosMO',
                                            'title' => 'Contratos de Mano de Obra',
                                            'packagePrefix' => 'paqueteMO',
                                            'resourcePrefix' => 'MO',
                                            'packageLabel' => 'Paquete Mano de Obra',
                                            'resourceLabel' => 'Insumos Mano de Obra',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosSI',
                                            'title' => 'Contratos de Suministro e Instalación',
                                            'packagePrefix' => 'paqueteSI',
                                            'resourcePrefix' => 'SI',
                                            'packageLabel' => 'Paquete Suministro e Instalación',
                                            'resourceLabel' => 'Insumos Suministro e Instalación',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosOC',
                                            'title' => 'Orden de Compra',
                                            'packagePrefix' => 'paqueteOC',
                                            'resourcePrefix' => 'OC',
                                            'packageLabel' => 'Paquete Orden de Compra',
                                            'resourceLabel' => 'Insumos Orden de Compra',
                                        ],
                                    ];
									?>
								<?php foreach ($contractSections as $section): ?>
									<section class="form-group parametro_EditarContratos ct-contract-section" id="<?php echo $section['id']; ?>">
										<div class="form_eval form-group ct-contract-section__banner">
											<h3 class="ct-contract-section__title"><?php echo $section['title']; ?></h3>
										</div>
										<div class="ct-contract-header" aria-hidden="true">
											<div class="ct-contract-header__spacer"></div>
											<div class="ct-contract-header__cell">Paquete de Contratación</div>
											<div class="ct-contract-header__cell">Insumo / Recurso</div>
										</div>
										<div class="ct-contract-list">
											<?php for ($i = 1; $i <= 5; $i++): ?>
												<?php
								                $packageId = $section['packagePrefix'] . $i;
											    $resourceId = $section['resourcePrefix'] . $i;
											    ?>
												<div class="ct-contract-row">
													<label for="<?php echo $packageId; ?>" class="control-label ct-contract-index"><?php echo $i; ?>.</label>
													<div class="ct-contract-field">
														<select id="<?php echo $packageId; ?>" name="<?php echo $packageId; ?>" class="form-control ct-contract-control" aria-label="<?php echo $section['packageLabel'] . ' ' . $i; ?>">
															<option value=""></option>
														</select>
													</div>
													<div class="ct-contract-field">
														<select id="<?php echo $resourceId; ?>" name="<?php echo $resourceId; ?>[]" class="form-control ct-contract-control ct-contract-control--multiple" multiple="multiple" aria-label="<?php echo $section['resourceLabel'] . ' ' . $i; ?>">
															<option value=""></option>
														</select>
													</div>
												</div>
											<?php endfor; ?>
										</div>
									</section>
								<?php endforeach; ?>

									<div class="form-group ct-contract-actions">
										<div class="col-sm-12 ct-contract-actions__buttons">
											<input id="btn_guardar_contratos" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar contratos">
											<input id="btn_cancelar_contratos" type="button" class="btn btn-danger" value="Cancelar" data-dismiss="modal" aria-label="Cancelar edición">
										</div>
									</div>

									<div class="col-sm-12 ct-contract-message-wrap">
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
	</div>

	<!-- Modal Auto-Definir Contratos -->
	<div class="modal fade aia-modal" id="modalAutoAsignarContratos" tabindex="-1" role="dialog" aria-labelledby="modalAutoAsignarContratosLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<div class="modal-title" id="modalAutoAsignarContratosLabel">
						<div class="aia-modal__eyebrow">AIA Corporativo</div>
						<h2 class="aia-modal__headline">Auto-Definir Contratos</h2>
						<p class="aia-modal__subtitle">Detecta automáticamente el tipo de contrato y paquetes para actividades sin asignar.</p>
					</div>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="alert alert-info" id="autoAsignarResumen">
						Presiona "Analizar" para detectar actividades pendientes de asignación.
					</div>
					<div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
						<table class="table table-sm table-bordered table-hover mb-0" id="autoAsignarTable">
							<thead class="thead-light">
								<tr>
									<th style="width: 50px;">#</th>
									<th>Actividad</th>
									<th>Familia</th>
									<th>Tipo Contrato</th>
									<th>Paquetes</th>
									<th>Estado</th>
								</tr>
							</thead>
							<tbody id="autoAsignarBody"></tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
					<button type="button" class="btn btn-outline-primary" id="btnAutoAsignarAnalizar"><i class="fas fa-search"></i> Analizar</button>
					<button type="button" class="btn-auto btn-auto-orange" id="btnAutoAsignarAplicar" disabled><i class="fas fa-magic"></i> Aplicar</button>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal -->

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

		var cargaParametros = function() {
			var $contractSelects = $('#S1,#S2,#S3,#S4,#S5,#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5,#MO1,#MO2,#MO3,#MO4,#MO5,#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5,#SI1,#SI2,#SI3,#SI4,#SI5,#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5,#OC1,#OC2,#OC3,#OC4,#OC5,#paqueteOC1,#paqueteOC2,#paqueteOC3,#paqueteOC4,#paqueteOC5');

			$contractSelects.each(function() {
				var $select = $(this);

				if ($select.data('select2')) {
					$select.select2('destroy');
				}

				$select.select2({
					tags: true,
					placeholder: '',
					allowClear: true,
					width: '100%',
					dropdownParent: $('#modalEditarContratos')
				});
			});

			inicializarAutoAsignarContratos();
      listar();
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
		  $("#btn_cancelar_contratos").on("click", function(e) {
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
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
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
		    "scrollX": false,
		    //                console.log($(document).height());
		    "scrollY": alturatabla,
		    /*                "scrollCollapse": false,*/
		    "responsive": true,
		    "paging": false,
		    "ajax": {
		      "method": "POST",
		      "url": "/api/contratos/list?db="+db+"&semana="+Max_Semana
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [
				{
				'targets': [8],
				'width':'14%',
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
							'width':'8%',
					},
					{
							'targets': [3],
							'width':'18%',
					},
					{
							'targets': [4],
							'width':'24%',
					},
					{
							'targets': [7],
							'width':'10%',
					},
					{
							'targets': [50],
							'width':'22%',
					},
					{
							'targets': [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50],
							'render': function ( data, type, full, meta ) {
							 return data;
							},
					},
				],
				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm btn-action-gap'  title='Editar'><i class='fa fa-edit fa-xs'></i></button><!--<button type='button' class='eliminar btn btn-danger btn-sm btn-action-gap'  title='Eliminar' data-toggle='modal' data-target='#modalEliminar' ><i class='fa fa-trash-alt fa-xs'></i></button>-->"},
						{"data":"Id", "visible":false},
						{"data":"codigo"},
						{"data":"actividad"},
						{"data":"descripcionActividad"},
						{"data":"actividadInicio", "visible":false},
						{"data":"nombreActividadInicio", "visible":false},
						{"data":"fechaInicio"},
						{"data":"tipoContrato"},
						{"data":"semanaActualizacion", "visible":false},
						{"data":"SI1", "visible":false},
						{"data":"paqueteSI1", "visible":false},
						{"data":"SI2", "visible":false},
						{"data":"paqueteSI2", "visible":false},
						{"data":"SI3", "visible":false},
						{"data":"paqueteSI3", "visible":false},
						{"data":"SI4", "visible":false},
						{"data":"paqueteSI4", "visible":false},
						{"data":"SI5", "visible":false},
						{"data":"paqueteSI5", "visible":false},
						{"data":"S1", "visible":false},
						{"data":"paqueteS1", "visible":false},
						{"data":"S2", "visible":false},
						{"data":"paqueteS2", "visible":false},
						{"data":"S3", "visible":false},
						{"data":"paqueteS3", "visible":false},
						{"data":"S4", "visible":false},
						{"data":"paqueteS4", "visible":false},
						{"data":"S5", "visible":false},
						{"data":"paqueteS5", "visible":false},
						{"data":"MO1", "visible":false},
						{"data":"paqueteMO1", "visible":false},
						{"data":"MO2", "visible":false},
						{"data":"paqueteMO2", "visible":false},
						{"data":"MO3", "visible":false},
						{"data":"paqueteMO3", "visible":false},
						{"data":"MO4", "visible":false},
						{"data":"paqueteMO4", "visible":false},
						{"data":"MO5", "visible":false},
						{"data":"paqueteMO5", "visible":false},
						{"data":"OC1", "visible":false},
						{"data":"paqueteOC1", "visible":false},
						{"data":"OC2", "visible":false},
						{"data":"paqueteOC2", "visible":false},
						{"data":"OC3", "visible":false},
						{"data":"paqueteOC3", "visible":false},
						{"data":"OC4", "visible":false},
						{"data":"paqueteOC4", "visible":false},
						{"data":"OC5", "visible":false},
						{"data":"paqueteOC5", "visible":false},
						{"data":"contratosAsociados"}
				],
		    "language": idioma_espanol
		  });

			// Dynamic Resize Listener
			$(window).off('resize.dtContratos orientationchange.dtContratos aia:viewport-scale-change.dtContratos').on('resize.dtContratos orientationchange.dtContratos aia:viewport-scale-change.dtContratos', function() {
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

			$("div.toolbarFilaBotones").html('<div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_contratos" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'">Contratos <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_planCompras" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=planCompras&semana='+semana+'&origen=info_contratos\'">Plan de Compras</button></div></div>');

			$("div.toolbarFilaBotones .grupo_botones_semanal_madre")
				.addClass("ps-toolbar-nav-wrap")
				.html('<div class="ps-module-switcher" role="tablist" aria-label="Navegacion general"><button id="btn_Actividades" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'" aria-label="Ir a Actividades"><i class="fas fa-table" aria-hidden="true"></i><span>Actividades</span></button><button id="btn_contratos" type="button" class="ps-module-tab is-active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'" aria-label="Ir a Contratos" aria-current="page"><i class="fas fa-file-alt" aria-hidden="true"></i><span>Contratos</span></button><button id="btn_planCompras" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=planCompras&semana='+semana+'&origen=info_contratos\'" aria-label="Ir a Plan de Compras"><i class="fas fa-shopping-cart" aria-hidden="true"></i><span>Plan de Compras</span></button></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="d-flex ml-auto"><label for="input_buscador" class="sr-only">Buscar en contratos</label><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm mr-1 ml-auto max-w-60" placeholder="Filtro"><button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			var permiso = document.getElementById('permiso_canonico').value;
			if (permiso === 'A' || permiso === 'D' || permiso === 'OT') {
				$("div.toolbarFilaMensajes").before('<div class="row mb-2"><div class="col-12"><button id="btn_auto_asignar_contratos" class="btn-pdc-modern" title="Auto-definir tipo de contrato y paquetes para actividades sin asignar"><i class="fas fa-magic"></i> Auto-Definir Contratos</button></div></div>');
			}

			maestroPermisos(document.getElementById('permiso_canonico').value);
			activarBuscador("#dt_cliente tbody", table);
		  obtener_data_editar("#dt_cliente tbody", table);
		}


		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  var permiso = document.getElementById('permiso_canonico').value;
			var db = document.getElementById('baseDatos').value;
		  if (permiso=="C") {
		    var only_once = false;
		  } else {
		    var only_once = true;
		  }
		  $(tbody).one("click", "td", function(e) {
				e.stopPropagation()
				var data= table.row($(this).parents("tr")).data();
				var Id=$("#Id").val(data.Id),
						opcion = $("#opcion").val("modificar");
		    if (only_once == true) {
					var tipoContrato = data.tipoContrato || '';
					document.getElementById('tipoContrato').value = tipoContrato;
					document.getElementById('actividadModificar').value = data.actividad;

					// Parse comma-separated modalidades and set checkboxes
					var modalidades = tipoContrato.split(',').map(function(s) { return s.trim(); });
					$('#modalidadSI').prop('checked', modalidades.indexOf('SI') >= 0);
					$('#modalidadMO').prop('checked', modalidades.indexOf('MO') >= 0);
					$('#modalidadS').prop('checked', modalidades.indexOf('S') >= 0);
					$('#modalidadOC').prop('checked', modalidades.indexOf('OC') >= 0);

					// Load package lists based on comma-separated modalidades
					$.ajax({
						method: "POST",
						url: "/api/contratos/save?db="+db,
						contenttype: "charset=utf-8",
						data: {"opcion": "actualizarListadoPaquetesContratacion", "tipoContrato": tipoContrato}
					}).done(function(info) {
						var json_info = (typeof info === 'string' ? JSON.parse(info) : info);

						$.ajax({
							method: "POST",
							url: "/api/contratos/save?db="+db,
							contenttype: "charset=utf-8",
							data: {"opcion": "actualizarInsumosRecursos", "tipoContrato": tipoContrato}
						}).done(function(info2) {
							var json_info2 = (typeof info2 === 'string' ? JSON.parse(info2) : info2);

							var hasSI = modalidades.indexOf('SI') >= 0;
							var hasMO = modalidades.indexOf('MO') >= 0;
							var hasS = modalidades.indexOf('S') >= 0;
							var hasOC = modalidades.indexOf('OC') >= 0;

							// Populate SI section
							if (hasSI) {
								$("#SI1, #SI2, #SI3, #SI4, #SI5").html(json_info2["listadoSI"] || '<option value=""></option>').change();
								$("#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5").html(json_info["listadoSI"] || '<option value=""></option>').change();
								var SI1 = (data.SI1 && data.SI1 != '') ? $("#SI1").val(data.SI1.split(';')).change() : '';
								var SI2 = (data.SI2 && data.SI2 != '') ? $("#SI2").val(data.SI2.split(';')).change() : '';
								var SI3 = (data.SI3 && data.SI3 != '') ? $("#SI3").val(data.SI3.split(';')).change() : '';
								var SI4 = (data.SI4 && data.SI4 != '') ? $("#SI4").val(data.SI4.split(';')).change() : '';
								var SI5 = (data.SI5 && data.SI5 != '') ? $("#SI5").val(data.SI5.split(';')).change() : '';
								$("#paqueteSI1").val(data.paqueteSI1).change();
								$("#paqueteSI2").val(data.paqueteSI2).change();
								$("#paqueteSI3").val(data.paqueteSI3).change();
								$("#paqueteSI4").val(data.paqueteSI4).change();
								$("#paqueteSI5").val(data.paqueteSI5).change();
							} else {
								$("#SI1, #SI2, #SI3, #SI4, #SI5").html('<option value=""></option>').change();
								$("#paqueteSI1, #paqueteSI2, #paqueteSI3, #paqueteSI4, #paqueteSI5").html('<option value=""></option>').change();
								$("#SI1,#SI2,#SI3,#SI4,#SI5").val('').change();
								$("#paqueteSI1,#paqueteSI2,#paqueteSI3,#paqueteSI4,#paqueteSI5").val('').change();
							}

							// Populate MO section
							if (hasMO) {
								$("#MO1, #MO2, #MO3, #MO4, #MO5").html(json_info2["listadoMO"] || '<option value=""></option>').change();
								$("#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5").html(json_info["listadoMO"] || '<option value=""></option>').change();
								var MO1 = (data.MO1 && data.MO1 != '') ? $("#MO1").val(data.MO1.split(';')).change() : '';
								var MO2 = (data.MO2 && data.MO2 != '') ? $("#MO2").val(data.MO2.split(';')).change() : '';
								var MO3 = (data.MO3 && data.MO3 != '') ? $("#MO3").val(data.MO3.split(';')).change() : '';
								var MO4 = (data.MO4 && data.MO4 != '') ? $("#MO4").val(data.MO4.split(';')).change() : '';
								var MO5 = (data.MO5 && data.MO5 != '') ? $("#MO5").val(data.MO5.split(';')).change() : '';
								$("#paqueteMO1").val(data.paqueteMO1).change();
								$("#paqueteMO2").val(data.paqueteMO2).change();
								$("#paqueteMO3").val(data.paqueteMO3).change();
								$("#paqueteMO4").val(data.paqueteMO4).change();
								$("#paqueteMO5").val(data.paqueteMO5).change();
							} else {
								$("#MO1, #MO2, #MO3, #MO4, #MO5").html('<option value=""></option>').change();
								$("#paqueteMO1, #paqueteMO2, #paqueteMO3, #paqueteMO4, #paqueteMO5").html('<option value=""></option>').change();
								$("#MO1,#MO2,#MO3,#MO4,#MO5").val('').change();
								$("#paqueteMO1,#paqueteMO2,#paqueteMO3,#paqueteMO4,#paqueteMO5").val('').change();
							}

							// Populate S section
							if (hasS) {
								$("#S1, #S2, #S3, #S4, #S5").html(json_info2["listadoS"] || '<option value=""></option>').change();
								$("#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5").html(json_info["listadoS"] || '<option value=""></option>').change();
								var S1 = (data.S1 && data.S1 != '') ? $("#S1").val(data.S1.split(';')).change() : '';
								var S2 = (data.S2 && data.S2 != '') ? $("#S2").val(data.S2.split(';')).change() : '';
								var S3 = (data.S3 && data.S3 != '') ? $("#S3").val(data.S3.split(';')).change() : '';
								var S4 = (data.S4 && data.S4 != '') ? $("#S4").val(data.S4.split(';')).change() : '';
								var S5 = (data.S5 && data.S5 != '') ? $("#S5").val(data.S5.split(';')).change() : '';
								$("#paqueteS1").val(data.paqueteS1).change();
								$("#paqueteS2").val(data.paqueteS2).change();
								$("#paqueteS3").val(data.paqueteS3).change();
								$("#paqueteS4").val(data.paqueteS4).change();
								$("#paqueteS5").val(data.paqueteS5).change();
							} else {
								$("#S1, #S2, #S3, #S4, #S5").html('<option value=""></option>').change();
								$("#paqueteS1, #paqueteS2, #paqueteS3, #paqueteS4, #paqueteS5").html('<option value=""></option>').change();
								$("#S1,#S2,#S3,#S4,#S5").val('').change();
								$("#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5").val('').change();
							}

							// Populate OC section
							if (hasOC) {
								$("#OC1, #OC2, #OC3, #OC4, #OC5").html(json_info2["listadoOC"] || '<option value=""></option>').change();
								$("#paqueteOC1, #paqueteOC2, #paqueteOC3, #paqueteOC4, #paqueteOC5").html(json_info["listadoOC"] || '<option value=""></option>').change();
								var OC1 = (data.OC1 && data.OC1 != '') ? $("#OC1").val(data.OC1.split(';')).change() : '';
								var OC2 = (data.OC2 && data.OC2 != '') ? $("#OC2").val(data.OC2.split(';')).change() : '';
								var OC3 = (data.OC3 && data.OC3 != '') ? $("#OC3").val(data.OC3.split(';')).change() : '';
								var OC4 = (data.OC4 && data.OC4 != '') ? $("#OC4").val(data.OC4.split(';')).change() : '';
								var OC5 = (data.OC5 && data.OC5 != '') ? $("#OC5").val(data.OC5.split(';')).change() : '';
								$("#paqueteOC1").val(data.paqueteOC1).change();
								$("#paqueteOC2").val(data.paqueteOC2).change();
								$("#paqueteOC3").val(data.paqueteOC3).change();
								$("#paqueteOC4").val(data.paqueteOC4).change();
								$("#paqueteOC5").val(data.paqueteOC5).change();
							} else {
								$("#OC1, #OC2, #OC3, #OC4, #OC5").html('<option value=""></option>').change();
								$("#paqueteOC1, #paqueteOC2, #paqueteOC3, #paqueteOC4, #paqueteOC5").html('<option value=""></option>').change();
								$("#OC1,#OC2,#OC3,#OC4,#OC5").val('').change();
								$("#paqueteOC1,#paqueteOC2,#paqueteOC3,#paqueteOC4,#paqueteOC5").val('').change();
							}

							// Update section visibility
							updateSections();

							// Update checkbox enable/disable state
							updateCheckboxState();
						});
					});

		      only_once = false;

					$("#modalEditarContratos").modal("show");
					$("#modal-body-texto-EditarContratos").html("Formulario de Registro de Contratos; Actividad: <b>" + data.actividad + "</b>");

					$("#btn_cancelar_editar").on("click", function(){
							only_once = true;
					});
		    }
		    cancelarEdicionFila();
		    guardar_modificar();
		  });
		}

		/* Checkbox modalidad logic: SI exclusive, MO/S/OC combinable */
		function syncHiddenTipoContrato() {
			var parts = [];
			if ($('#modalidadSI').is(':checked')) parts.push('SI');
			if ($('#modalidadMO').is(':checked')) parts.push('MO');
			if ($('#modalidadS').is(':checked')) parts.push('S');
			if ($('#modalidadOC').is(':checked')) parts.push('OC');
			$('#tipoContrato').val(parts.join(','));
		}

		function updateSections() {
			var tc = $('#tipoContrato').val() || '';
			var codes = tc.split(',').map(function(s) { return s.trim(); });
			var hasSI = codes.indexOf('SI') >= 0;
			var hasMO = codes.indexOf('MO') >= 0;
			var hasS = codes.indexOf('S') >= 0;
			var hasOC = codes.indexOf('OC') >= 0;

			$('#parametro_EditarContratosSI').toggle(hasSI);
			$('#parametro_EditarContratosMO').toggle(hasMO);
			$('#parametro_EditarContratosS').toggle(hasS);
			$('#parametro_EditarContratosOC').toggle(hasOC);
		}

		function updateCheckboxState() {
			var siChecked = $('#modalidadSI').is(':checked');
			var anyOther = $('#modalidadMO').is(':checked') || $('#modalidadS').is(':checked') || $('#modalidadOC').is(':checked');

			if (siChecked) {
				$('#modalidadMO, #modalidadS, #modalidadOC').prop('disabled', true);
			} else if (anyOther) {
				$('#modalidadSI').prop('disabled', true);
			} else {
				$('#modalidadSI, #modalidadMO, #modalidadS, #modalidadOC').prop('disabled', false);
			}
		}

		// SI checkbox: if checked → uncheck + disable MO/S/OC
		$(document).on('change', '#modalidadSI', function() {
			if ($(this).is(':checked')) {
				$('#modalidadMO, #modalidadS, #modalidadOC').prop('checked', false).prop('disabled', true);
			} else {
				$('#modalidadMO, #modalidadS, #modalidadOC').prop('disabled', false);
			}
			syncHiddenTipoContrato();
			updateSections();
		});

		// MO/S/OC checkboxes: if any checked → uncheck + disable SI
		$(document).on('change', '#modalidadMO, #modalidadS, #modalidadOC', function() {
			var anyChecked = $('#modalidadMO').is(':checked') || $('#modalidadS').is(':checked') || $('#modalidadOC').is(':checked');
			if (anyChecked) {
				$('#modalidadSI').prop('checked', false).prop('disabled', true);
			} else {
				$('#modalidadSI').prop('disabled', false);
			}
			syncHiddenTipoContrato();
			updateSections();
		});

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar_modificar = function() {
		  $("#btn_guardar_contratos").one("click", function(e) {
		    e.preventDefault();
		    var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var Id = document.getElementById('Id').value;
				var opcion = document.getElementById('opcion').value;
				var tipoContrato = document.getElementById('tipoContrato').value;

				// Build form data manually
				var frm = $("form").serializeArray();
				// Remove modalidades[] from form data
				frm = frm.filter(function(f) {
					return f.name !== 'modalidades[]';
				});

				frm.push({ name: 'Id', value: Id });
				frm.push({ name: 'opcion', value: opcion });
				frm.push({ name: 'semana', value: semana });
				frm.push({ name: 'tipoContrato', value: tipoContrato });

				var frmStr = $.param(frm);

		    $.ajax({
		      method: "POST",
		      url: "/api/contratos/save?db="+db,
		      contenttype: "charset=utf-8",
		      data: frmStr,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      if (json_info.respuesta == "BIEN") {
		        $("#modalEditarContratos").modal("hide");
						recargarTabla();
		      } else {
						$(".mensaje").html(json_info["respuesta"]).css({
							"color": "#C9302C"
						});
						$(".mensaje").fadeOut(5000, function() {
							$(this).html("");
							$(this).fadeIn(3000);
						});
					}
		    });
		  });
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
			var texto = "",
				color = "success", // Default to success class
                borderClass = "success";

			if (informacion.respuesta == "BIENNuevaActividad" || informacion.respuesta == "BIENCargarExcel") {
				texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
				borderClass = "success";
			} else if (informacion.respuesta == "ERROR") {
				texto = "<strong>Error</strong>, no se ejecutó la consulta.";
				borderClass = "error";
			} else if (informacion.respuesta == "EXISTE") {
				texto = "<strong>Información!</strong> La actividad que estás intentando registrar ya existe.";
				borderClass = "error";
			} else if (informacion.respuesta == "VACIO") {
				texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
				borderClass = "warning";
			} else if (informacion.respuesta == "NO_ELIMINAR") {
				texto = "<strong>Advertencia!</strong> No se puede eliminar esta actividad.";
				borderClass = "warning";
			} else if (informacion.respuesta == "BIEN") {
                // Generic BIEN response handling if applicable
                texto = "<strong>Bien!</strong> Operación realizada correctamente.";
                borderClass = "success";
            } else {
                // Fallback for direct text messages
                texto = informacion.respuesta || "Mensaje del sistema";
                // Simple heuristic for error messages if they contain "Error"
                if(texto.toLowerCase().includes("error")) borderClass = "error";
            }

            // Hide modals if needed
            $("#modalNuevaActividad").modal("hide");
            $("#modalCargarExcel").modal("hide");
            $("#modalEditarContratos").modal("hide");

            // Show Toast
             var toast = $("#mensajeActualizacion");
             toast.removeClass("success error warning").addClass("custom-toast " + borderClass);
             toast.html(texto);
             toast.show().delay(4000).fadeOut(1000, function(){
                 $(this).html("");
             });
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
		    // $('#dt_cliente').empty();
		    // listar();
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

		/* Auto-Asignar Contratos */
		var inicializarAutoAsignarContratos = function() {
			$(document).off('click.autoAsignar', '#btn_auto_asignar_contratos').on('click.autoAsignar', '#btn_auto_asignar_contratos', function(e) {
				e.preventDefault();
				$('#modalAutoAsignarContratos').modal('show');
			});

			$('#modalAutoAsignarContratos').off('show.bs.modal.autoAsignar').on('show.bs.modal.autoAsignar', function() {
				$('#autoAsignarResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Presiona "Analizar" para detectar actividades pendientes de asignación.');
				$('#autoAsignarBody').html('');
				$('#btnAutoAsignarAplicar').prop('disabled', true);
				$('#btnAutoAsignarAnalizar').prop('disabled', false);
			});

			$('#btnAutoAsignarAnalizar').off('click.autoAsignar').on('click.autoAsignar', function(e) {
				e.preventDefault();
				var btn = $(this);
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;

				btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Analizando...');
				$('#autoAsignarResumen').removeClass('alert-danger alert-success').addClass('alert-info').html('Analizando actividades pendientes...');
				$('#autoAsignarBody').html('');

				$.ajax({
					method: 'POST',
					url: '/api/contratos/auto-assign?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
					dataType: 'json'
				}).done(function(response) {
					if (!response || response.respuesta !== 'BIEN') {
						$('#autoAsignarResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml((response && response.mensaje) || 'No se pudieron cargar sugerencias.'));
						btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
						return;
					}

					var sugerencias = response.sugerencias || [];
					var msg = 'Total pendientes: <strong>' + response.total + '</strong> · Asignadas: <strong>' + response.asignadas + '</strong> · Sin match: <strong>' + response.sinMatch + '</strong>';
					$('#autoAsignarResumen').removeClass('alert-info alert-danger').addClass('alert-success').html(msg);

					var html = '';
					for (var i = 0; i < sugerencias.length; i++) {
						var s = sugerencias[i];
						var badge = '';
						var estado = '';
						if (!s.match) {
							badge = '<span class="badge badge-secondary">Sin match</span>';
							estado = escaparHtml(s.motivo || 'Sin familia detectada');
						} else if (s.asignada) {
							badge = '<span class="badge badge-success">Asignada</span>';
							estado = '<strong>' + escaparHtml(s.tipoContratoLabel || '') + '</strong>: ' + (s.paquetes || []).map(function(p) { return escaparHtml(p.paqueteNombre); }).join(', ');
						} else if (s.motivo) {
							badge = '<span class="badge badge-warning">Pendiente</span>';
							estado = escaparHtml(s.motivo);
						}
						var familia = s.familia ? escaparHtml(s.familia) : '-';
						html += '<tr><td>' + (i + 1) + '</td><td>' + escaparHtml(s.actividad || '') + '</td><td>' + familia + '</td><td>' + (s.tipoContratoLabel ? escaparHtml(s.tipoContratoLabel) : '-') + '</td><td>' + (s.paquetes ? s.paquetes.map(function(p) { return escaparHtml(p.paqueteNombre); }).join(', ') : '-') + '</td><td>' + badge + ' ' + estado + '</td></tr>';
					}
					$('#autoAsignarBody').html(html);
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				}).fail(function(xhr) {
					var mensaje = 'Error de conexión al analizar.';
					if (xhr.responseJSON && xhr.responseJSON.mensaje) {
						mensaje = xhr.responseJSON.mensaje;
					}
					$('#autoAsignarResumen').removeClass('alert-info alert-success').addClass('alert-danger').html(escaparHtml(mensaje));
					btn.prop('disabled', false).html('<i class="fas fa-search"></i> Analizar');
				});
			});
		};

		function escaparHtml(texto) {
			if (!texto) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(texto));
			return div.innerHTML;
		}

	</script>
</body>
</html>
