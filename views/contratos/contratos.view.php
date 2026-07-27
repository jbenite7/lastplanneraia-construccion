<!DOCTYPE html>
<html lang="es">
<head id="head">
	<meta charset="UTF-8">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/vendor/handsontable/handsontable.full.min.css?v=14.6.1" />
	<!-- handsontable-module.css llega vía aia-design-system.css (layer vendor); el link crudo duplicaba la cascada. -->
	<link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260714theme1" />
	<link rel="stylesheet" href="/css/contratos.css?v=20260714-dark7" />
	<!--Script cque va al archivo linksComunesHead2.js-->
	<?= \App\View\Components\DesignSystemHeadComponent::render(true) ?>
	<script>window.__AIA_HANDSONTABLE_ONLY__ = true;</script>
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body class="contratos-page aia-shell aia-shell--sidebar">

	<?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_contratos" aria-hidden="true">
		<input type="hidden" id="contratoId" value="" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="codigo" name="codigo" value="" aria-hidden="true">
		<input type="hidden" id="cap_contratos_editar" value="<?php echo !empty($canEditContracts) ? '1' : '0'; ?>" aria-hidden="true">
		<input type="hidden" id="cap_contratos_auto_definir" value="<?php echo !empty($canAutoDefineContracts) ? '1' : '0'; ?>" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Profesional" -->
	<div class="row formularioRegistro">
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
			<div id="ct-table-status" class="ct-table-status" role="status" aria-live="polite" data-state="loading">Cargando contratos…</div>
			<div id="hot-container" class="w-100 hot-mobile-grid ct-hot-container"></div>
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
							<p class="aia-modal__subtitle">Configura los paquetes e insumos asociados a la familia seleccionada.</p>
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
											<label class="aia-choice ct-checkbox-item"><input type="checkbox" id="modalidadSI" name="modalidades[]" value="SI"> Suministro e Instalación</label>
											<label class="aia-choice ct-checkbox-item"><input type="checkbox" id="modalidadMO" name="modalidades[]" value="MO"> Mano de Obra</label>
											<label class="aia-choice ct-checkbox-item"><input type="checkbox" id="modalidadS" name="modalidades[]" value="S"> Suministro</label>
											<label class="aia-choice ct-checkbox-item"><input type="checkbox" id="modalidadOC" name="modalidades[]" value="OC"> Orden de servicio/compra</label>
										</div>
									</div>
									<input type="hidden" id="tipoContrato" name="tipoContrato" value="">
									<input type="hidden" id="actividadModificar" name="actividadModificar" value="">
									<?php
                                    $contractSections = [
                                        [
                                            'id' => 'parametro_EditarContratosS',
                                            'title' => 'Paquetes de suministro',
                                            'packagePrefix' => 'paqueteS',
                                            'resourcePrefix' => 'S',
                                            'packageLabel' => 'Paquete Suministro',
                                            'resourceLabel' => 'Insumos Suministro',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosMO',
                                            'title' => 'Paquetes de mano de obra',
                                            'packagePrefix' => 'paqueteMO',
                                            'resourcePrefix' => 'MO',
                                            'packageLabel' => 'Paquete Mano de Obra',
                                            'resourceLabel' => 'Insumos Mano de Obra',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosSI',
                                            'title' => 'Paquetes de suministro e instalación',
                                            'packagePrefix' => 'paqueteSI',
                                            'resourcePrefix' => 'SI',
                                            'packageLabel' => 'Paquete Suministro e Instalación',
                                            'resourceLabel' => 'Insumos Suministro e Instalación',
                                        ],
                                        [
                                            'id' => 'parametro_EditarContratosOC',
                                            'title' => 'Orden de servicio/compra',
                                            'packagePrefix' => 'paqueteOC',
                                            'resourcePrefix' => 'OC',
                                            'packageLabel' => 'Paquete orden de servicio/compra',
                                            'resourceLabel' => 'Insumos orden de servicio/compra',
                                        ],
                                    ];
									?>
								<?php foreach ($contractSections as $section): ?>
					<section class="form-group parametro_EditarContratos ct-contract-section" id="<?php echo $section['id']; ?>" data-prefix="<?php echo $section['resourcePrefix']; ?>">
										<div class="form_eval form-group ct-contract-section__banner">
											<h3 class="ct-contract-section__title"><?php echo $section['title']; ?></h3>
										</div>
										<div class="ct-contract-header" aria-hidden="true">
											<div class="ct-contract-header__spacer"></div>
											<div class="ct-contract-header__cell">Paquete de contratacion</div>
											<div class="ct-contract-header__cell">Cantidad de contratos</div>
											<div class="ct-contract-header__cell">Insumos y recursos requeridos</div>
										</div>
										<div class="ct-contract-list">
											<?php for ($i = 1; $i <= 5; $i++): ?>
												<?php
								                $packageId = $section['packagePrefix'] . $i;
											    $resourceId = $section['resourcePrefix'] . $i;
											    $quantityId = 'cantidad' . $section['resourcePrefix'] . $i;
											    ?>
						<div class="ct-contract-row<?php echo $i > 1 ? ' ct-contract-row--hidden' : ''; ?>" data-slot-index="<?php echo $i; ?>"<?php echo $i > 1 ? ' hidden' : ''; ?>>
													<label for="<?php echo $packageId; ?>" class="control-label ct-contract-index"><span class="ct-contract-index__prefix">Paquete </span><?php echo $i; ?>.</label>
													<div class="ct-contract-field">
														<label for="<?php echo $packageId; ?>" class="ct-contract-mobile-label">Paquete de contratación</label>
														<select id="<?php echo $packageId; ?>" name="<?php echo $packageId; ?>" class="form-control ct-contract-control ct-package-select" data-prefix="<?php echo $section['resourcePrefix']; ?>" aria-label="<?php echo $section['packageLabel'] . ' ' . $i; ?>">
															<option value=""></option>
														</select>
														<small class="ct-contract-mobile-help">Selecciona o escribe el paquete que se contratará.</small>
													</div>
													<div class="ct-contract-field ct-contract-field--quantity">
														<label for="<?php echo $quantityId; ?>" class="ct-contract-mobile-label">Cantidad de contratos</label>
								<input id="<?php echo $quantityId; ?>" name="<?php echo $quantityId; ?>" type="number" class="form-control ct-contract-control ct-contract-quantity" min="1" step="1" value="1" inputmode="numeric" aria-label="Cantidad de contratos <?php echo $section['resourceLabel'] . ' ' . $i; ?>">
														<small class="ct-contract-mobile-help">Número de contratos separados para este paquete.</small>
													</div>
													<div class="ct-contract-field">
														<label for="<?php echo $resourceId; ?>" class="ct-contract-mobile-label">Insumos y recursos requeridos</label>
														<select id="<?php echo $resourceId; ?>" name="<?php echo $resourceId; ?>[]" class="form-control ct-contract-control ct-contract-control--multiple" multiple="multiple" aria-label="<?php echo $section['resourceLabel'] . ' ' . $i; ?>">
															<option value=""></option>
														</select>
														<small class="ct-contract-mobile-help">Agrega los insumos o recursos que componen el paquete.</small>
													</div>
												</div>
						<?php endfor; ?>
					</div>
					<button type="button" class="btn btn-primary ct-add-package" data-prefix="<?php echo $section['resourcePrefix']; ?>" aria-label="Agregar paquete en <?php echo $section['title']; ?>">
						<span aria-hidden="true">+</span> Agregar paquete
					</button>
					<p class="ct-overplanning-alert" data-prefix="<?php echo $section['resourcePrefix']; ?>">Se usaron los 5 paquetes disponibles. Revisa si la actividad esta sobreplaneada.</p>
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
		<div class="modal fade aia-modal" id="modalDuracionesContratos" tabindex="-1" role="dialog" aria-labelledby="modalDuracionesContratosLabel" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content ct-modal-content">
					<div class="modal-header ct-modal-header">
						<div class="modal-title ct-modal-title" id="modalDuracionesContratosLabel">
							<div class="aia-modal__eyebrow">Paquetes de contratacion</div>
							<h2 class="aia-modal__headline">Duraciones pendientes</h2>
							<p class="aia-modal__subtitle">Define los dias de contratacion para continuar con el guardado.</p>
						</div>
						<button type="button" class="close ct-modal-close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body ct-modal-body">
						<div class="table-responsive">
							<table class="table table-sm table-bordered" id="tablaDuracionesContratos">
								<thead>
									<tr>
										<th>Modalidad</th>
										<th>Paquete de contratacion</th>
										<th>Pliegos</th>
										<th>Entrega</th>
										<th>Propuestas</th>
										<th>Cuadros</th>
										<th>Legalizacion</th>
										<th>Fabricacion</th>
										<th>Insumos obra</th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
						<p class="mensaje-duraciones text-danger"></p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
						<button type="button" class="btn btn-primary" id="btn_guardar_duraciones_contratos">Guardar duraciones</button>
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
	<script>
		window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
		// Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=20260708theme" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
	<script type="text/javascript" src="/js/modules/semi_auto_review.js?v=20260714-undo-context3" charset="utf-8"></script>
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
			actualizarBadgePendientesContratos();
			guardar_modificar();
			cancelarEdicionFila();
      listar();
		}

		function selectContractPackage(selector, value) {
			var contractValue = (value || '').trim();
			var $select = $(selector);
			if (contractValue === '') {
				$select.val('').change();
				return;
			}
			if ($select.find('option').filter(function() { return $(this).val() === contractValue; }).length === 0) {
				$select.append(new Option(contractValue, contractValue, true, true));
			}
			$select.val(contractValue).change();
		}

		function setPackageQuantities(data) {
			['SI', 'S', 'MO', 'OC'].forEach(function(prefix) {
				for (var i = 1; i <= 5; i++) {
					var packageValue = (data['paquete' + prefix + i] || '').trim();
					var quantity = parseInt(data['cantidad' + prefix + i] || '1', 10);
					if (!packageValue || isNaN(quantity) || quantity < 1) {
						quantity = 1;
					}
					$('#cantidad' + prefix + i).val(quantity);
				}
				updateOverplanningAlert(prefix);
			});
		}

		function reloadContractOptionsForModal() {
			var tipoContrato = $('#tipoContrato').val() || '';
			var db = document.getElementById('baseDatos').value;
			$.post('/api/contratos/save?db=' + db, {
				opcion: 'actualizarListadoPaquetesContratacion',
				tipoContrato: tipoContrato
			}).done(function(info) {
				var data = (typeof info === 'string' ? JSON.parse(info) : info);
				[['SI', 'listadoSI'], ['MO', 'listadoMO'], ['S', 'listadoS'], ['OC', 'listadoOC']].forEach(function(item) {
					var prefix = item[0], options = data[item[1]] || '<option value=""></option>';
					for (var i = 1; i <= 5; i++) {
						var $select = $('#paquete' + prefix + i);
						var current = $select.val();
						$select.html(options).val(current).change();
					}
				});
			});
			$.post('/api/contratos/save?db=' + db, {
				opcion: 'actualizarInsumosRecursos',
				tipoContrato: tipoContrato
			}).done(function(info) {
				var data = (typeof info === 'string' ? JSON.parse(info) : info);
				[['SI', 'listadoSI'], ['MO', 'listadoMO'], ['S', 'listadoS'], ['OC', 'listadoOC']].forEach(function(item) {
					var prefix = item[0], options = data[item[1]] || '<option value=""></option>';
					for (var i = 1; i <= 5; i++) {
						var $select = $('#' + prefix + i);
						var current = $select.val();
						$select.html(options).val(current).change();
					}
				});
			});
		}

		function updateOverplanningAlert(prefix) {
			var filled = 0;
			for (var i = 1; i <= 5; i++) {
				if (($('#paquete' + prefix + i).val() || '').trim() !== '') {
					filled++;
				}
			}
			$('.ct-overplanning-alert[data-prefix="' + prefix + '"]').toggle(filled >= 5);
		}

		function packageSlotHasValue(prefix, index) {
			var packageValue = ($('#paquete' + prefix + index).val() || '').trim();
			return packageValue !== '';
		}

		function syncProgressivePackageSlots(prefix, resetToData) {
			var $section = $('.ct-contract-section[data-prefix="' + prefix + '"]');
			var $rows = $section.find('.ct-contract-row');
			var filledThrough = 0;
			$rows.each(function() {
				var index = parseInt($(this).data('slot-index'), 10);
				if (packageSlotHasValue(prefix, index)) filledThrough = Math.max(filledThrough, index);
			});
			var current = resetToData ? 0 : parseInt($section.data('visible-slots') || '0', 10);
			var visible = Math.min(5, Math.max(1, current, filledThrough));
			$section.data('visible-slots', visible);
			$rows.each(function() {
				var show = parseInt($(this).data('slot-index'), 10) <= visible;
				$(this).prop('hidden', !show).toggleClass('ct-contract-row--hidden', !show);
			});
			$section.find('.ct-add-package').toggle(visible < 5);
		}

		function resetProgressivePackageSlots() {
			['SI', 'S', 'MO', 'OC'].forEach(function(prefix) {
				syncProgressivePackageSlots(prefix, true);
			});
		}

		$(document).off('click.ctAddPackage', '.ct-add-package')
			.on('click.ctAddPackage', '.ct-add-package', function() {
				var prefix = $(this).data('prefix');
				var $section = $('.ct-contract-section[data-prefix="' + prefix + '"]');
				var current = parseInt($section.data('visible-slots') || '1', 10);
				$section.data('visible-slots', Math.min(5, current + 1));
				syncProgressivePackageSlots(prefix, false);
			});

		$(document).on('change', '.ct-package-select', function() {
			var prefix = $(this).data('prefix');
			updateOverplanningAlert(prefix);
			syncProgressivePackageSlots(prefix, false);
		});

		var pendingContractSavePayload = null;
		var durationFields = [
			'diasElaboracionPliegos',
			'diasEntregaPliegos',
			'diasReciboPropuestas',
			'diasCuadrosComparativos',
			'diasLegalizacionContrato',
			'diasFabricacion',
			'diasInsumosObra'
		];

		function openDurationsModal(packages, retryPayload) {
			pendingContractSavePayload = retryPayload;
			var $tbody = $('#tablaDuracionesContratos tbody');
			$tbody.empty();
			var anyDefault = false;
			(packages || []).forEach(function(item, index) {
				var row = '<tr data-index="' + index + '">' +
					'<td class="duration-type"></td>' +
					'<td class="duration-package"></td>';
				durationFields.forEach(function(field) {
					var prefill = (typeof item[field] === 'number') ? item[field] : 1;
					if (prefill !== 1) anyDefault = true;
					row += '<td><input type="number" class="form-control form-control-sm duration-input" data-field="' + field + '" min="0" step="1" value="' + prefill + '"></td>';
				});
				row += '</tr>';
				var $row = $(row);
				$row.data('item', item);
				$row.find('.duration-type').text(item.tipoPaquete || '');
				$row.find('.duration-package').text(item.paqueteContratacion || '');
				$tbody.append($row);
			});
			$('.mensaje-duraciones').text('');
			if (anyDefault) {
				$('.mensaje-duraciones').removeClass('text-danger').addClass('text-muted')
					.text('Sugerencia: precargamos los valores estandar de tu modalidad. Ajustalos si lo necesitas antes de guardar.');
			} else {
				$('.mensaje-duraciones').removeClass('text-muted').addClass('text-danger');
			}
			$('#modalDuracionesContratos').modal('show');
		}

		function retryPendingContractSave() {
			if (!pendingContractSavePayload) {
				return;
			}
			submitContractSave(pendingContractSavePayload);
			pendingContractSavePayload = null;
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
		  $("#btn_cancelar_contratos").off("click.ctCancel").on("click.ctCancel", function(e) {
		    e.preventDefault();
		    $("#modalEditarContratos").modal("hide");
		  });
		}


		/* Inicializa la tabla principal Handsontable. */
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;

			document.getElementById('cuadroTabla').style.height = 'auto';

			// Toolbar: module switcher
			$('div.toolbarFilaBotones').html(
				window.AIAInfoGeneralNav.render('contratos', semana, 'info_contratos')
			);

			$('div.toolbarFilaMensajes').html('<p id="mensajeActualizacion"></p>');

			$('div.toolbarFiltro').html(
				'<div class="d-flex ml-auto">' +
				'<label class="sr-only">Buscar en contratos</label>' +
				'<button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern mr-1 ml-0 d-none max-w-40"><i class="fas fa-times-circle"></i> Limpiar</button>' +
				'</div>'
			);

			var permiso = document.getElementById('permiso_canonico').value;
			var canAutoDefineContracts = $('#cap_contratos_auto_definir').val() === '1';
			if (canAutoDefineContracts) {
				$('div.toolbarFilaBotones').prepend(
					'<button id="btn_auto_asignar_contratos" class="btn-pdc-modern ml-2" title="Auto-definir modalidad y paquetes para familias sin asignar">' +
					'<i class="fas fa-magic"></i> Auto-definir paquetes' +
					'<span class="badge badge-pill badge-danger ml-1 ct-pending-badge" id="badgePendientesContratos">0</span>' +
					'</button>'
				);
			}

			if (window.SemiAutoReview) {
				window.SemiAutoReview.init({
					module: 'contratos',
					anchorSelector: 'div.toolbarFilaMensajes',
					refresh: function() { recargarTabla('listar'); }
				});
			}

			maestroPermisos(document.getElementById('permiso_canonico').value);

			// Inicializar Handsontable
			if (window.ContratosHotModule) {
				window.ContratosHotModule.init();
			}
		}


		/* La apertura del modal de edición se maneja desde ContratosHotModule (hot.js) */

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
			reloadContractOptionsForModal();
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
			reloadContractOptionsForModal();
		});

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		function validatePackageQuantities() {
			var valid = true;
			$('#formularioEditarContratos .ct-contract-row:visible').each(function() {
				var packageValue = String($(this).find('.ct-package-select').val() || '').trim();
				var $quantity = $(this).find('.ct-contract-quantity');
				$quantity.removeAttr('aria-invalid').get(0).setCustomValidity('');
				if (!packageValue) return;
				var raw = String($quantity.val() || '').trim();
				if (!/^[1-9]\d*$/.test(raw)) {
					valid = false;
					$quantity.attr('aria-invalid', 'true').get(0).setCustomValidity('Ingresa un entero mayor o igual a 1.');
				}
			});
			if (!valid) {
				$('.mensaje').text('La cantidad de contratos del paquete actual debe ser un entero mayor o igual a 1.').addClass('ct-message-error');
				$('#formularioEditarContratos .ct-contract-quantity[aria-invalid="true"]').first().trigger('focus');
			}
			return valid;
		}

		var contractSaveInFlight = false;
		var guardar_modificar = function() {
		  $("#btn_guardar_contratos").off("click.ctSave").on("click.ctSave", function(e) {
		    e.preventDefault();
				if (contractSaveInFlight || !validatePackageQuantities()) return;
				var semana = document.getElementById('semana').value;
				var Id = document.getElementById('contratoId').value;
				var opcion = document.getElementById('opcion').value;
				var tipoContrato = document.getElementById('tipoContrato').value;

				// Build form data manually
					var frm = $("#formularioEditarContratos").serializeArray();
				// Remove modalidades[] from form data
				frm = frm.filter(function(f) {
					return f.name !== 'modalidades[]';
				});

				frm.push({ name: 'Id', value: Id });
				frm.push({ name: 'opcion', value: opcion });
				frm.push({ name: 'semana', value: semana });
				frm.push({ name: 'tipoContrato', value: tipoContrato });

				var frmStr = $.param(frm);
				submitContractSave(frmStr);
		  });
		}

		function submitContractSave(frmStr) {
			    var db = document.getElementById('baseDatos').value;
					contractSaveInFlight = true;
					$('#btn_guardar_contratos').prop('disabled', true);
					$('.mensaje').text('').removeClass('ct-message-error');
			    return $.ajax({
		      method: "POST",
		      url: "/api/contratos/save?db="+db,
		      contenttype: "charset=utf-8",
		      data: frmStr,
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
			      if (json_info.respuesta == "BIEN") {
			        $("#modalEditarContratos").modal("hide");
							$('#mensajeActualizacion').text('Los contratos se guardaron correctamente.').addClass('custom-toast success').show();
							recargarTabla();
		      } else if (json_info.respuesta == "DURACIONES_REQUERIDAS") {
						openDurationsModal(json_info.paquetes || [], frmStr);
		      } else {
		        var msg = json_info.mensaje || json_info.respuesta || 'Error inesperado';
			        $(".mensaje").text(msg).addClass('ct-message-error');
							$(".mensaje").fadeOut(5000, function() {
								$(this).text("");
							$(this).removeClass('ct-message-error');
							$(this).fadeIn(3000);
						});
					}
		    }).fail(function(jqXHR) {
		    	var msg = 'Error del servidor (500)';
		    	try {
		    		var json = JSON.parse(jqXHR.responseText || '{}');
		    		msg = json.mensaje || msg;
		    	} catch(e) {}
		          $(".mensaje").text(msg).addClass('ct-message-error');
				console.error('submitContractSave error:', msg, jqXHR.responseText);
			    }).always(function() {
						contractSaveInFlight = false;
						$('#btn_guardar_contratos').prop('disabled', false);
					});
			}

			$('#modalEditarContratos').off('hidden.bs.modal.ctCleanup').on('hidden.bs.modal.ctCleanup', function() {
				contractSaveInFlight = false;
				pendingContractSavePayload = null;
				$('#btn_guardar_contratos').prop('disabled', false);
				$('.mensaje').stop(true, true).text('').removeClass('ct-message-error').show();
				$('#formularioEditarContratos .ct-contract-quantity').removeAttr('aria-invalid').each(function() {
					this.setCustomValidity('');
				});
				resetProgressivePackageSlots();
			});

			var durationSaveInFlight = false;
			$("#btn_guardar_duraciones_contratos").off("click.ctDurations").on("click.ctDurations", function(e) {
				e.preventDefault();
				if (durationSaveInFlight) return;
			var db = document.getElementById('baseDatos').value;
			var duraciones = [];
			var valid = true;
			$('#tablaDuracionesContratos tbody tr').each(function() {
				var item = $(this).data('item') || {};
				var row = {
					tipoPaquete: item.tipoPaquete || '',
					paqueteContratacion: item.paqueteContratacion || ''
				};
				$(this).find('.duration-input').each(function() {
						var raw = String($(this).val() || '').trim();
						if (!/^\d+$/.test(raw)) {
							valid = false;
						}
						row[$(this).data('field')] = /^\d+$/.test(raw) ? parseInt(raw, 10) : raw;
				});
				duraciones.push(row);
			});

			if (!valid) {
				$('.mensaje-duraciones').text('Todas las duraciones deben ser numeros enteros iguales o mayores a cero.');
				return;
			}

				durationSaveInFlight = true;
				$('#btn_guardar_duraciones_contratos').prop('disabled', true);
				$.ajax({
				method: "POST",
				url: "/api/contratos/save?db="+db,
				data: {
					opcion: 'guardarDuracionesContratacion',
					duraciones: JSON.stringify(duraciones)
				}
			}).done(function(info) {
				var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
				if (json_info.respuesta === 'BIEN') {
					$('#modalDuracionesContratos').modal('hide');
					retryPendingContractSave();
					return;
				}
					$('.mensaje-duraciones').text(json_info.mensaje || 'No se pudieron guardar las duraciones.');
				}).fail(function() {
					$('.mensaje-duraciones').text('No se pudieron guardar las duraciones.');
				}).always(function() {
					durationSaveInFlight = false;
					$('#btn_guardar_duraciones_contratos').prop('disabled', false);
				});
			});

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
			$("#contratoId").val("");
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
			if (window.ContratosHotModule && typeof window.ContratosHotModule.loadData === 'function') {
				window.ContratosHotModule.loadData();
			} else {
				listar();
			}
		}



		function contratosContextoActual() {
			return {
				db: document.getElementById('baseDatos').value,
				semana: document.getElementById('semana').value
			};
		}

		var inicializarAutoAsignarContratos = function() {
			$(document).off('click.autoDefinir', '#btn_auto_asignar_contratos')
				.on('click.autoDefinir', '#btn_auto_asignar_contratos', function(e) {
					e.preventDefault();
					if (window.SemiAutoReview) {
						window.SemiAutoReview.open('contratos');
						return;
					}
					if (AIA.Notice && AIA.Notice.warningToast) {
						AIA.Notice.warningToast('El asistente moderno de contratos no está disponible. Recarga la página e inténtalo de nuevo.');
					} else {
						alert('El asistente moderno de contratos no está disponible. Recarga la página e inténtalo de nuevo.');
					}
				});
		};

		var actualizarBadgePendientesContratos = function() {
			var $badge = $('#badgePendientesContratos');
			if (!$badge.length) return;

			var ctx = contratosContextoActual();

			$.ajax({
				method: 'POST',
				url: '/api/contratos/auto/metrics?db=' + encodeURIComponent(ctx.db) + '&semana=' + encodeURIComponent(ctx.semana),
				dataType: 'json'
			}).done(function(response) {
				var total = response && response.respuesta === 'BIEN'
					? parseInt((response.coverage && response.coverage.sin_contrato) || 0, 10)
					: 0;
				if (total > 0) {
					$badge.text(total).show();
				} else {
					$badge.hide();
				}
			}).fail(function() {
				$badge.hide();
			});
		};

	</script>

	<!-- Runtime canónico de la tabla principal -->
	<script src="/vendor/handsontable/handsontable.full.min.js?v=14.6.1"></script>
	<script src="/vendor/handsontable/es-MX.js?v=14.6.1"></script>
	<script src="/js/modules/info_general_nav.js?v=20260708b"></script>
	<script src="/js/modules/contratos/hot.js?v=20260713-catalog2"></script>
</body>
</html>
