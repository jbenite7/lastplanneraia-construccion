<!DOCTYPE html>
<html lang="es">
<head id="head">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColorsV4" charset="utf-8"></script>
	<style>
		/* PDC state colors and chips are now in public/css/styles.css */

		/* Legend / Convenciones Styles */
		.pdc-legend {
			display: inline-flex;
			gap: 15px;
			margin-bottom: 0px;
			font-size: 0.85rem;
			color: #64748b; /* Slate-500 */
			vertical-align: middle;
			margin-left: 10px;
		}
		.pdc-legend-item {
			display: flex;
			align-items: center;
			gap: 6px;
			font-weight: 500;
		}
		/* Legend Container */
		.pdc-legend {
			display: flex;
			flex-wrap: nowrap; /* Force single line */
			gap: 8px;
			align-items: center;
			padding: 0; /* Removed padding for better vertical align */
			overflow-x: auto; /* Allow horizontal scroll */
			white-space: nowrap;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: thin; /* Firefox */
		}
		/* Scrollbar styling for Webkit */
		.pdc-legend::-webkit-scrollbar {
			height: 4px;
		}
		.pdc-legend::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}

		/* Chip Base Style */
		.pdc-legend-item {
			display: inline-flex;
			align-items: center;
			padding: 4px 10px; /* Smaller padding */
			border-radius: 999px;
			font-size: 0.78rem; /* Smaller font */
			font-weight: 600;
			border: 1px solid transparent;
			cursor: pointer;
			transition: all 0.2s ease;
			user-select: none;
			box-shadow: 0 1px 2px rgba(0,0,0,0.05);
		}

		/* Active/Hover Interactions */
		.pdc-legend-item:hover {
			transform: translateY(-1px);
			box-shadow: 0 4px 6px rgba(0,0,0,0.05);
			filter: brightness(0.97);
		}
		.pdc-legend-item:active {
			transform: scale(0.98);
		}
		.pdc-legend-item.inactive-filter {
			opacity: 0.4;
			filter: grayscale(0.8);
			transform: scale(0.95);
		}

		/* Chip Colors: now in public/css/styles.css */

		/* Chip Badge (Counter) */
		.count-badge {
			font-size: 0.75rem;
			margin-left: 8px;
			background-color: rgba(255,255,255,0.5);
			padding: 2px 8px;
			border-radius: 12px;
			font-weight: 700;
		}

		/* Hide old indicator span since color is now on the chip */
		.pdc-legend-item .indicator { display: none; }
		#dt_cliente thead tr.pdc-filter-row th {
			padding: 6px 6px;
			background: #f8fafc;
			vertical-align: middle;
		}
		#dt_cliente thead .pdc-column-filter {
			width: 100%;
			min-width: 110px;
			font-size: 0.8rem;
		}
		#dt_cliente thead .pdc-column-filter[type="date"] {
			min-width: 150px;
		}
		#dt_cliente thead tr.pdc-filter-row th.pdc-filter-empty {
			padding: 0;
			min-width: 0;
		}
		.pdc-toast {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 9999;
			background-color: #ffffff;
			color: #333;
			padding: 15px 20px;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			border-left: 5px solid #007bff; /* Accent color */
			font-family: 'Roboto', sans-serif;
			font-size: 14px;
			display: none; /* Hidden by default */
			max-width: 300px;
			animation: slideIn 0.3s ease-out forwards;
		}
		@keyframes slideIn {
			from { transform: translateX(100%); opacity: 0; }
			to { transform: translateX(0); opacity: 1; }
		}

		#modalContrato {
			color: #24313a;
		}

		#modalContrato .modal-dialog {
			max-width: min(1380px, calc(100vw - 2rem));
			margin: 1rem auto;
		}

		#modalContrato .modal-content {
			border: 0;
			border-radius: 1.25rem;
			overflow: hidden;
			background: #f4f1ea;
			box-shadow: var(--shadow-glass, 0 8px 32px rgba(30, 30, 30, 0.12));
		}

		#modalContrato .modal-header {
			padding: 1.5rem 1.75rem 1.25rem;
			border-bottom: 1px solid rgba(213, 229, 219, 0.32);
			background: linear-gradient(135deg, #1a3c2a 0%, #1a5633 100%);
			align-items: flex-start;
		}

		#modalContrato .modal-title {
			margin: 0;
			max-width: calc(100% - 3rem);
		}

		#modalContrato .close {
			margin: 0;
			padding: 0.25rem;
			font-size: 1.8rem;
			line-height: 1;
			color: #fafafa;
			opacity: 0.8;
		}

		#modalContrato .close:hover {
			opacity: 1;
		}

		#modalContrato .modal-body {
			padding: 1.5rem 1.75rem 1.75rem;
			overflow-x: hidden;
		}

		#modalContrato .pdc-contract-modal__eyebrow {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			margin-bottom: 0.65rem;
			padding: 0.35rem 0.75rem;
			border-radius: 999px;
			background: rgba(213, 229, 219, 0.96);
			color: #1a3c2a;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.75rem;
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
		}

		#modalContrato .modal-body-texto-Contrato {
			font-family: 'Inter', sans-serif;
			font-size: 0.96rem;
			line-height: 1.55;
			color: rgba(250, 250, 250, 0.9);
		}

		#modalContrato .modal-body-texto-Contrato b {
			color: #fafafa;
			font-family: 'Montserrat', sans-serif;
			font-weight: 700;
		}

		#modalContrato .pdc-contract-form {
			display: grid;
			gap: 1rem;
		}

		#modalContrato .pdc-contract-section {
			padding: 1.1rem 1.15rem 1.2rem;
			border: 1px solid rgba(26, 86, 51, 0.18);
			border-radius: 1rem;
			background: rgba(250, 250, 250, 0.94);
			box-shadow: 0 10px 24px rgba(36, 49, 58, 0.06);
		}

		#modalContrato .pdc-contract-section__header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
			margin-bottom: 1rem;
			padding-bottom: 0.85rem;
			border-bottom: 1px solid rgba(26, 86, 51, 0.18);
		}

		#modalContrato .pdc-contract-section__title {
			margin: 0;
			font-family: 'Montserrat', sans-serif;
			font-size: 1.06rem;
			font-weight: 700;
			line-height: 1.2;
			letter-spacing: -0.01em;
			color: #24313a;
		}

		#modalContrato .pdc-contract-section__hint {
			margin: 0;
			font-family: 'Inter', sans-serif;
			font-size: 0.88rem;
			line-height: 1.45;
			color: #6b7280;
		}

		#modalContrato .pdc-contract-section__body {
			display: grid;
			gap: 0.9rem;
		}

		#modalContrato .pdc-modal-row {
			display: grid;
			grid-template-columns: minmax(220px, 1.1fr) minmax(0, 1.6fr);
			gap: 1rem;
			align-items: start;
			padding: 0;
			border: 0;
		}

		#modalContrato .pdc-modal-label {
			inline-size: auto;
			max-inline-size: none;
			display: block;
			margin: 0;
			text-align: left;
		}

		#modalContrato .pdc-modal-label span,
		#modalContrato .labelFormularioAdjudicado,
		#modalContrato .labelFormularioSeguimientoContrato {
			display: inline-block;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.9rem;
			font-weight: 600;
			line-height: 1.45;
			color: #24313a;
		}

		#modalContrato .pdc-modal-value,
		#modalContrato .pdc-modal-field,
		#modalContrato .pdc-modal-spacer {
			inline-size: auto;
			max-inline-size: none;
			margin: 0;
		}

		#modalContrato .pdc-modal-value,
		#modalContrato .pdc-modal-field {
			padding: 0;
		}

		#modalContrato .pdc-modal-field,
		#modalContrato .pdc-modal-value .form-control[readonly],
		#modalContrato .pdc-modal-value .form-control:not(textarea) {
			min-height: 3rem;
			border-radius: 0.8rem;
			border: 1px solid rgba(104, 116, 125, 0.24);
			background: #fff;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
		}

		#modalContrato textarea.form-control,
		#modalContrato input.form-control,
		#modalContrato select.form-control {
			border-radius: 0.8rem;
			border: 1px solid rgba(104, 116, 125, 0.24);
			font-family: 'Inter', sans-serif;
			font-size: 0.95rem;
			color: #24313a;
			background: #fff;
			transition: border-color 0.2s ease, box-shadow 0.2s ease;
		}

		#modalContrato textarea.form-control:focus,
		#modalContrato input.form-control:focus,
		#modalContrato select.form-control:focus {
			border-color: rgba(26, 86, 51, 0.5);
			box-shadow: 0 0 0 0.2rem rgba(26, 86, 51, 0.18);
		}

		#modalContrato #actividadesDelContrato,
		#modalContrato #observacionesContrato {
			min-height: 8rem;
			resize: vertical;
		}

		#modalContrato .pdc-process-grid {
			display: grid;
			gap: 0.7rem;
		}

		#modalContrato .filaEncabezado,
		#modalContrato .pasoProcesoContratacion {
			display: grid;
			grid-template-columns: minmax(260px, 2.2fr) repeat(4, minmax(132px, 1fr));
			gap: 0.75rem;
			align-items: stretch;
			width: 100%;
			margin: 0;
			padding: 0;
			background: transparent;
			border: 0;
		}

		#modalContrato .filaEncabezado {
			padding: 0 0 0.2rem;
		}

		#modalContrato .labelFormularioContratos,
		#modalContrato .inputFormularioContratos,
		#modalContrato .labelFilaEncabezado {
			inline-size: auto;
			max-inline-size: none;
			margin: 0;
			height: auto;
		}

		#modalContrato .labelFormularioContratos {
			padding: 1rem 1rem 1rem 1.1rem;
			border: 1px solid rgba(26, 86, 51, 0.12);
			border-radius: 0.9rem;
			background: linear-gradient(180deg, #eef5f1 0%, #f4f1ea 100%);
			text-align: left;
		}

		#modalContrato .labelFormularioContratos span {
			display: block;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.92rem;
			font-weight: 600;
			line-height: 1.5;
			color: #24313a;
		}

		#modalContrato .labelFilaEncabezado {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 0.8rem 0.6rem;
			border-radius: 0.9rem;
			background: #1a5633;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.82rem;
			font-weight: 700;
			line-height: 1.35;
			text-align: center;
			color: #fafafa;
		}

		#modalContrato .inputFormularioContratos {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			padding: 0.8rem 0.75rem;
			border: 1px solid rgba(26, 86, 51, 0.14);
			border-radius: 0.9rem;
			background: #fff;
			box-shadow: 0 6px 16px rgba(36, 49, 58, 0.04);
		}

		#modalContrato .inputFormularioContratos input,
		#modalContrato .inputFormularioContratos input:read-only {
			max-width: none;
			flex: 1 1 auto;
			width: 100%;
			min-width: 0;
			padding: 0.55rem 0.8rem;
			border: 1px solid rgba(104, 116, 125, 0.24);
			border-radius: 0.75rem;
			background: #fff;
			font-family: 'Inter', sans-serif;
		}

		#modalContrato .inputFormularioContratos i {
			max-width: none;
			margin: 0;
			padding: 0;
			font-size: 1rem;
			color: #1a5633;
		}

		#modalContrato .pdc-bg-muted {
			background: linear-gradient(180deg, #efebe4 0%, #e7dfd3 100%);
		}

		#modalContrato .pdc-bg-muted input,
		#modalContrato .pdc-bg-muted input:read-only {
			background: transparent;
			border-style: dashed;
		}

		#modalContrato .informacionAdjudicacionProveedor,
		#modalContrato .informacionAdjudicacionContrato,
		#modalContrato .seguimientoContrato {
			padding: 1rem 1.05rem;
			border: 1px solid rgba(26, 86, 51, 0.14);
			border-radius: 0.95rem;
			background: #fffdfa;
		}

		#modalContrato .informacionAdjudicacionProveedor .form-group,
		#modalContrato .informacionAdjudicacionContrato .form-group,
		#modalContrato .seguimientoContrato .form-group {
			margin-bottom: 0.9rem;
		}

		#modalContrato .pdc-row-center h5 {
			margin: 0;
			font-family: 'Montserrat', sans-serif;
			font-size: 1rem;
			font-weight: 700;
			color: #1a3c2a;
		}

		#modalContrato .mensajeModalInformacionAdjudicado,
		#modalContrato .mensajeModalInformacionContrato,
		#modalContrato .mensajeModalSeguimientoContrato,
		#modalContrato .mensajeModalContrato {
			margin: 0;
			font-family: 'Inter', sans-serif;
			font-size: 0.88rem;
		}

		#modalContrato .pdc-provider-locked {
			border-color: rgba(26, 86, 51, 0.22) !important;
			background: linear-gradient(180deg, #eef5f1 0%, #d5e5db 100%) !important;
      		color: #1a3c2a !important;
			cursor: not-allowed;
			box-shadow: inset 0 0 0 1px rgba(26, 86, 51, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.72);
		}

		#modalContrato .pdc-provider-locked::placeholder {
			color: #8b7a69;
		}

		#modalContrato .pdc-provider-locked + .pdc-provider-lock-badge,
		#modalContrato .pdc-provider-lock-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			margin-top: 0.45rem;
			padding: 0.22rem 0.55rem;
			border-radius: 999px;
			background: rgba(26, 86, 51, 0.12);
			color: #1a3c2a;
			font-family: 'Montserrat', sans-serif;
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 0.03em;
			text-transform: uppercase;
		}

		#modalContrato .pdc-provider-lock-badge[hidden] {
			display: none !important;
		}

		#modalContrato .pdc-contract-actions {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
			flex-wrap: wrap;
			padding-top: 0.25rem;
		}

		#modalContrato .pdc-contract-actions__buttons {
			display: flex;
			gap: 0.75rem;
			flex-wrap: wrap;
		}

		#modalContrato #btn_guardar_pdc,
		#modalContrato #btn_cancelar_editar {
			min-width: 140px;
			padding: 0.8rem 1.35rem;
			border-radius: 999px;
			font-family: 'Montserrat', sans-serif;
			font-weight: 700;
			letter-spacing: 0.01em;
		}

		#modalContrato #btn_guardar_pdc {
			background: #1a5633;
			border-color: #1a5633;
			box-shadow: 0 12px 22px rgba(26, 86, 51, 0.18);
		}

		#modalContrato #btn_guardar_pdc:hover,
		#modalContrato #btn_guardar_pdc:focus {
			background: #1a3c2a;
			border-color: #1a3c2a;
		}

		#modalContrato #btn_cancelar_editar {
			background: transparent;
			border: 1px solid rgba(26, 86, 51, 0.35);
			color: #1a5633;
		}

		#modalContrato #btn_cancelar_editar:hover,
		#modalContrato #btn_cancelar_editar:focus {
			background: rgba(26, 86, 51, 0.08);
			color: #1a3c2a;
		}

		#modalContrato .pdc-contract-note {
			margin: 0;
			font-family: 'Inter', sans-serif;
			font-size: 0.84rem;
			color: #6b7280;
		}

		@media (max-width: 1199.98px) {
			#modalContrato .filaEncabezado,
			#modalContrato .pasoProcesoContratacion {
				grid-template-columns: minmax(220px, 1.7fr) repeat(2, minmax(150px, 1fr));
			}

			#modalContrato .labelFormularioContratos {
				grid-column: 1 / -1;
			}
		}

		@media (max-width: 767.98px) {
			#modalContrato .modal-dialog {
				max-width: calc(100vw - 1rem);
				margin: 0.5rem auto;
			}

			#modalContrato .modal-header,
			#modalContrato .modal-body {
				padding: 1rem;
			}

			#modalContrato .pdc-contract-section {
				padding: 1rem;
				border-radius: 0.9rem;
			}

			#modalContrato .pdc-contract-section__header {
				flex-direction: column;
				align-items: flex-start;
				gap: 0.45rem;
			}

			#modalContrato .pdc-modal-row {
				grid-template-columns: 1fr;
				gap: 0.55rem;
			}

			#modalContrato .filaEncabezado {
				display: none;
			}

			#modalContrato .pasoProcesoContratacion {
				grid-template-columns: 1fr;
				gap: 0.7rem;
				padding: 1rem;
				border: 1px solid rgba(26, 86, 51, 0.12);
				border-radius: 0.95rem;
				background: #fff;
			}

			#modalContrato .labelFormularioContratos,
			#modalContrato .inputFormularioContratos {
				padding: 0.9rem;
			}

			#modalContrato .inputFormularioContratos {
				justify-content: flex-start;
			}

			#modalContrato .pasoProcesoContratacion .inputFormularioContratos::before {
				display: block;
				width: 100%;
				margin-bottom: 0.45rem;
				font-family: 'Montserrat', sans-serif;
				font-size: 0.76rem;
				font-weight: 700;
				letter-spacing: 0.04em;
				text-transform: uppercase;
				color: #1a3c2a;
			}

			#modalContrato .pasoProcesoContratacion .inputFormularioContratos:nth-child(2)::before {
				content: 'Duracion';
			}

			#modalContrato .pasoProcesoContratacion .inputFormularioContratos:nth-child(3)::before {
				content: 'Fecha teorica';
			}

			#modalContrato .pasoProcesoContratacion .inputFormularioContratos:nth-child(4)::before {
				content: 'Fecha proyectada';
			}

			#modalContrato .pasoProcesoContratacion .inputFormularioContratos:nth-child(5)::before {
				content: 'Fecha real';
			}

			#modalContrato .pdc-contract-actions {
				align-items: stretch;
			}

			#modalContrato .pdc-contract-actions__buttons {
				width: 100%;
			}

			#modalContrato .informacionAdjudicacionProveedor .col,
			#modalContrato .informacionAdjudicacionContrato .col,
			#modalContrato .seguimientoContrato .col,
			#modalContrato .informacionAdjudicacionContrato .col-4,
			#modalContrato .informacionAdjudicacionContrato .col-8 {
				flex: 0 0 100%;
				max-width: 100%;
			}

			#modalContrato #btn_guardar_pdc,
			#modalContrato #btn_cancelar_editar {
				flex: 1 1 0;
				min-width: 0;
			}
		}
	</style>
</head>

<!--Etiqueta superior-->
<body>

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
			<div class="table-responsive">
				<table id="dt_cliente" class="dt_infoGeneral table table-bordered table-hover table-sm w-100" cellspacing="0" width="100%">
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
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<div class="modal_Contrato modal fade aia-modal" id="modalContrato" tabindex="-1" role="dialog" aria-labelledby="modal_ContratoLabel">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalContratoLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<div class="modal-body-texto-Contrato mb-0" id="modal-body-texto-Contrato"></div>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="recargarTabla('')"><span aria-hidden="true">&times;</span></button>
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
													<span class="h6 font-weight-bold">Actividades del programa de obra en este paquete de contratación:</span>
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
													<span class="h6 font-weight-bold">Fecha de inicio del contrato según el cronograma:</span>
												</label>
												<div id='divFechaInicioContrato' name='divFechaInicioContrato' class='pdc-modal-value pdc-modal-value--narrow'><input id='fechaInicioContrato' name='fechaInicioContrato' class='form-control text-center' type='text' value='' placeholder='Fecha de Inicio' autocomplete="off" readonly>
												</div>
											</div>
											<div class="col-sm-12 pdc-modal-row">
												<label for="valorPresupuesto" class="control-label pdc-modal-label">
													<span class="h6 font-weight-bold">Valor en presupuesto de la actividad:</span>
												</label>
												<div id='divValorPresupuesto' name='divValorPresupuesto' class='pdc-modal-value pdc-modal-value--narrow'><input id='valorPresupuesto' name='valorPresupuesto' class='form-control bg-white text-center' type='text' value='' placeholder='Valor en Pesos Colombianos' autocomplete="off" data-type="currency">
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
													<span class="h6 font-weight-bold">Duración (días)</span>
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
													<span class="h6 font-weight-bold">2. Entrega de pliegos y/o carta. Elaboración de propuesta:</span>
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
													<span class="h6 font-weight-bold">3. Recibo de propuestas:</span>
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
													<span class="h6 font-weight-bold">4. Cuadros comparativos, análisis y adjudicación:</span>
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
													<span class="h6 font-weight-bold">5. Legalización del contrato:</span>
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
													<span class="h6 font-weight-bold">7. Anticipación de insumos en obra:</span>
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
													<span class="h6 font-weight-bold">8. Comienzo de las actividades en la obra:</span>
												</div>
												<div class="inputFormularioContratos pdc-bg-muted">
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
											<input id="btn_guardar_pdc" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar Plan de Compras">
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

		<div class="modal_DefinirContrato modal fade aia-modal" id="modalDefinirContratos" tabindex="-1" role="dialog" aria-labelledby="modal_DefinirContratoLabel">
			<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalDefinirContratosLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h2 class="aia-modal__headline modal-body-texto-DefinirContrato" id="modal-body-texto-DefinirContrato">Definir Contratos</h2>
							<p class="aia-modal__subtitle">Configura el numero de contratos asociados a cada paquete antes de guardar.</p>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.reload()"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="cuadroDefinirContratos" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
								<form id="formularioDefinirContrato" class="form form-horizontal aia-modal__form" action="" method="POST">
									<section class="form-group parametro_Contrato aia-modal__section">
										<div class="aia-modal__section-header">
											<h3 class="aia-modal__section-title">Distribucion de Contratos</h3>
											<p class="aia-modal__hint">Ajusta la estructura por tipo de contrato y conserva el orden visual del paquete.</p>
										</div>
											<div class="table-responsive">
												<table id="dt_definirContratos" class="table table-bordered w-100">
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
									</section>
									<!--Se crean los botones Guardar y Listar-->
									<div class="form-group aia-modal__actions">
										<div class="col-sm-offset-2 col-sm-12 aia-modal__buttons">
											<input id="btn_guardar_definirContratos" type="button" class="btn btn-primary" value="Guardar" onclick="guardar_DefinirContratos()" aria-label="Guardar definición de contratos">
											<input id="btn_cancelar_definirContratos" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar" onClick="location.reload()" aria-label="Cancelar definición">
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

		<!-- Wizard PDC -->
		<div class="modal fade aia-modal" id="modalPdcWizard" tabindex="-1" role="dialog" aria-labelledby="modalPdcWizardLabel">
			<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<div class="modal-title" id="modalPdcWizardLabel">
							<div class="aia-modal__eyebrow">AIA Plan de Compras</div>
							<h2 class="aia-modal__headline">Configuración Rápida del Plan de Compras</h2>
							<p class="aia-modal__subtitle">Genera el PDC completo en 4 pasos.</p>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<ul class="nav nav-pills mb-3" id="wizardSteps" role="tablist">
							<li class="nav-item"><a class="nav-link active" data-step="1" href="#">1. Actividades</a></li>
							<li class="nav-item"><a class="nav-link disabled" data-step="2" href="#">2. Familias</a></li>
							<li class="nav-item"><a class="nav-link disabled" data-step="3" href="#">3. Duraciones</a></li>
							<li class="nav-item"><a class="nav-link disabled" data-step="4" href="#">4. Confirmar</a></li>
						</ul>
						<div id="wizardContent">
							<div id="wizardStep1" class="wizard-pane">
								<div id="wizardActividadesResumen" class="alert alert-info mb-3">Cargando actividades...</div>
								<div id="wizardActividadesList" style="max-height:400px;overflow-y:auto;"></div>
							</div>
							<div id="wizardStep2" class="wizard-pane d-none">
								<div id="wizardFamiliasResumen" class="alert alert-info mb-3"></div>
								<div id="wizardFamiliasList" style="max-height:400px;overflow-y:auto;"></div>
							</div>
							<div id="wizardStep3" class="wizard-pane d-none">
								<div id="wizardDuracionesResumen" class="alert alert-info mb-3"></div>
								<div id="wizardDuracionesList" style="max-height:400px;overflow-y:auto;"></div>
							</div>
							<div id="wizardStep4" class="wizard-pane d-none">
								<div id="wizardConfirmResumen" class="alert alert-warning mb-3"></div>
								<div id="wizardConfirmList" style="max-height:400px;overflow-y:auto;"></div>
							</div>
						</div>
					</div>
					<div class="modal-footer aia-modal__buttons">
						<button type="button" id="btn_wizard_prev" class="btn btn-secondary" disabled>Anterior</button>
						<button type="button" id="btn_wizard_next" class="btn btn-primary">Siguiente</button>
						<button type="button" id="btn_wizard_generate" class="btn btn-success d-none">Generar Plan de Compras</button>
						<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
	<!--Script con las funciones NUEVA SEMANA y ELIMINAR SEMANA-->
	<script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>
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



		var table; // Global table variable
		var activePDCFilters = []; // Store active filter states (Array)
		var PDC_ROW_STATE_CLASSES = 'Titulo pdc-header pdc-missing-data pdc-critical-delay pdc-delayed pdc-completed-delayed pdc-completed-ontime pdc-not-started pdc-active';
		var PDC_STATE_ROW_CLASS_MAP = {
			header: 'Titulo pdc-header',
			missing: 'pdc-missing-data',
			critical: 'pdc-critical-delay',
			delayed: 'pdc-delayed',
			'completed-late': 'pdc-completed-delayed',
			'completed-ontime': 'pdc-completed-ontime',
			'not-started': 'pdc-not-started',
			active: 'pdc-active'
		};
		var pdcColumnFilterConfig = {
			3: { type: 'select', dataKey: 'tipoPaquete', ariaLabel: 'Filtrar tipo de contrato' },
			4: { type: 'text', ariaLabel: 'Filtrar paquete de contratacion', placeholder: 'Filtrar' },
			5: { type: 'text', ariaLabel: 'Filtrar actividades del proyecto', placeholder: 'Filtrar' },
			6: { type: 'select', dataKey: 'estado', ariaLabel: 'Filtrar estado' },
			7: { type: 'date', ariaLabel: 'Filtrar inicio del proceso de contratacion' },
			31: { type: 'date', ariaLabel: 'Filtrar inicio actividades cronograma' },
			32: { type: 'date', ariaLabel: 'Filtrar inicio actividades proyectado' },
			33: { type: 'date', ariaLabel: 'Filtrar inicio actividades real' },
			34: { type: 'text', ariaLabel: 'Filtrar observaciones', placeholder: 'Filtrar' }
		};

		function normalizePDCStatusDisplay(value) {
			return String(value || '').replace(/^A tiempo(?=;|$)/, 'En Curso');
		}

		function isPDCMissingData(data) {
			var fechaInicioProyectada = $.trim(String((data && data.fechaInicioProyectada) || ''));
			var valorPresupuesto = data ? data.valorPresupuesto : null;

			return fechaInicioProyectada === '' || valorPresupuesto === '' || valorPresupuesto === null || typeof valorPresupuesto === 'undefined';
		}

		/* Centralized Status Logic */
		function getPDCState(data) {
			if (Number(data.titulo) !== 0) return 'header';
			if (isPDCMissingData(data)) return 'missing';
			
			let estado = normalizePDCStatusDisplay(data.estado || '');
			if(estado.includes("Atrasado") && (estado.includes("no iniciado") || estado.includes("No iniciado"))) return 'critical';
			if(estado.includes("Atrasado")) return 'delayed';
			if(estado.includes("Terminado con retrasos")) return 'completed-late';
			if(estado.includes("Terminado a tiempo")) return 'completed-ontime';
			if(estado.includes("no iniciado") || estado.includes("No iniciado")) return 'not-started';
			if(estado.includes("En Curso")) return 'active';
			
			return 'standard';
		}

		function applyPDCRowState(row, data) {
			var state = getPDCState(data);
			var className = PDC_STATE_ROW_CLASS_MAP[state] || '';

			$(row).removeClass(PDC_ROW_STATE_CLASSES);

			if (className !== '') {
				$(row).addClass(className);
			}
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
		} // Min height 200px

		function escapePdcRegex(value) {
			if ($.fn.dataTable && $.fn.dataTable.util && typeof $.fn.dataTable.util.escapeRegex === 'function') {
				return $.fn.dataTable.util.escapeRegex(value);
			}

			return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		}

		function buildPdcFilterControl(columnIndex, config) {
			var baseAttributes = 'class="pdc-column-filter form-control form-control-sm" data-column-index="' + columnIndex + '" data-filter-type="' + config.type + '" aria-label="' + config.ariaLabel + '"';

			if (config.type === 'select') {
				return '<select ' + baseAttributes + '><option value="">Todos</option></select>';
			}

			if (config.type === 'date') {
				return '<input ' + baseAttributes + ' type="date">';
			}

			return '<input ' + baseAttributes + ' type="text" placeholder="' + (config.placeholder || 'Filtrar') + '">';
		}

		function renderPdcColumnFilterRow() {
			var $thead = $('#dt_cliente thead');
			var $baseRow = $thead.find('tr').first();
			var totalColumns = $baseRow.children('th').length;
			var $filterRow = $('<tr class="pdc-filter-row"></tr>');

			$thead.find('tr.pdc-filter-row').remove();

			for (var columnIndex = 0; columnIndex < totalColumns; columnIndex++) {
				var config = pdcColumnFilterConfig[columnIndex] || null;
				var $cell = $('<th></th>');

				if (config) {
					$cell.html(buildPdcFilterControl(columnIndex, config));
				} else {
					$cell.addClass('pdc-filter-empty');
				}

				$filterRow.append($cell);
			}

			$thead.append($filterRow);
		}

		function getPdcFilterHeads(dataTable) {
			var $heads = $('#dt_cliente thead');

			if (dataTable && typeof dataTable.table === 'function') {
				var $container = $(dataTable.table().container());
				var $scrollHead = $container.find('div.dataTables_scrollHead thead');

				if ($scrollHead.length) {
					$heads = $heads.add($scrollHead);
				}
			}

			return $heads;
		}

		function populatePdcSelectFilterOptions(dataTable) {
			var $filterHeads = getPdcFilterHeads(dataTable);

			Object.keys(pdcColumnFilterConfig).forEach(function(key) {
				var columnIndex = Number(key);
				var config = pdcColumnFilterConfig[columnIndex];
				var $filter = $filterHeads.find('.pdc-column-filter[data-column-index="' + columnIndex + '"]');

				if (!$filter.length || config.type !== 'select') {
					return;
				}

				var values = [];
				var seen = {};

				dataTable.rows().every(function() {
					var rowData = this.data() || {};
					var value = $.trim(String(rowData[config.dataKey] || ''));

					if (config.dataKey === 'estado') {
						value = normalizePDCStatusDisplay(value);
					}

					if (Number(rowData.titulo) !== 0 || value === '' || seen[value]) {
						return;
					}

					seen[value] = true;
					values.push(value);
				});

				values.sort(function(a, b) {
					return a.localeCompare(b);
				});

				$filter.empty().append('<option value="">Todos</option>');

				values.forEach(function(value) {
					$filter.append($('<option></option>').val(value).text(value));
				});
			});
		}

		function bindPdcColumnFilters(dataTable) {
			var $filterRow = getPdcFilterHeads(dataTable).find('tr.pdc-filter-row');

			$filterRow.off('.pdcColumnFilters');
			$filterRow.on('click.pdcColumnFilters', '.pdc-column-filter', function(e) {
				e.stopPropagation();
			});
			$filterRow.on('keyup.pdcColumnFilters input.pdcColumnFilters change.pdcColumnFilters', '.pdc-column-filter', function() {
				var $control = $(this);
				var columnIndex = Number($control.data('column-index'));
				var filterType = $control.data('filter-type');
				var value = $.trim(String($control.val() || ''));

				if (filterType === 'select' || filterType === 'date') {
					if (value === '') {
						dataTable.column(columnIndex).search('').draw();
						return;
					}

					dataTable.column(columnIndex).search('^' + escapePdcRegex(value) + '$', true, false).draw();
					return;
				}

				dataTable.column(columnIndex).search(value).draw();
			});
		}

		/* DataTables Filtering Logic (Multi-Select) */
		$.fn.dataTable.ext.search.push(
			function(settings, data, dataIndex, rowData) {
				if (activePDCFilters.length === 0) {
					return true;
				}
				var state = getPDCState(rowData);
				return activePDCFilters.includes(state);
			}
		);

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;
		  
			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto"; // Let it grow/shrink
			renderPdcColumnFilterRow();


		  table = $("#dt_cliente").DataTable({
		    "dom": "<'row filaBotones mb-2 align-items-center'<'col-auto mr-auto pl-0'<'toolbarAcciones'>><'col-auto ml-auto pr-0'<'toolbarNavegacion'>>><'row mt-2'<'col-12 p-0'<'toolbarFilaMensajes d-flex align-items-center'>>>t<'row'<'col-md-6'i>><'clear'>",
		    "destroy": true,
				"orderFixed":[32, "asc"],

		    "autoWidth": false,
				"orderCellsTop": true,
		    "fixedHeader": false,
		    "scrollX": false,
		    //                console.log($(document).height());
		    "scrollY": alturatabla,
		    /*                "scrollCollapse": false,*/
		    "responsive": true,
		    "paging": false,
		    "ajax": {
		      "method": "POST",
		      "url": "/api/pdc/list?db="+db+"&semana="+Max_Semana+"&definirContratos=0"
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [

            // { orderable: true, className: 'reorder', targets: 32 },
            { orderable: false, targets: '_all' },

					{
						'targets': [1,2,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,32],
						'render': function ( data, type, full, meta ) {
						 return data;
						 },
					},

					{
						'targets': [3],
						'width':'8%',
						'render': function ( data, type, full, meta ) {
							return data;
						 },
					},

					{
						'targets': [4],
						'width':'16%',
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
						'targets': [5],
						'width':'12%',
						'render': function ( data, type, full, meta ) {
							return data;
						},
					},
					{
						'targets': [28,29,30],
						'width':'9%',
						'render': function ( data, type, full, meta ) {
							return data;
						},
					},
					{
						'targets': [31],
						'width':'13%',
						'render': function ( data, type, full, meta ) {
							return data;
						},
					},

				{
					'targets': [6],
					'width':'11%',
					'render': function ( data, type, row, meta ) {
						var texto = normalizePDCStatusDisplay(row["estado"] || '');
						var diasDelta = row["diasDelta"] || 0;

						if (type !== 'display') {
							return texto || '';
						}

						var titulo=row["titulo"];
							var id = row["id"];
							var procesoIniciado=row["procesoIniciado"];
							var fechaInicioProceso= new Date(row["fechaElaboracionPliegos"]);
							var fechaInicioSemana = new Date(document.getElementById('Fecha_Inicio_SemYMD').value);
							var dias = (fechaInicioProceso - fechaInicioSemana)/(1000 * 3600 * 24);
							var deltaHtml = '';
							if (id != "" && titulo == 0 && diasDelta !== 0) {
								if (diasDelta > 0) {
									deltaHtml = ' <span class="pdc-delta pdc-delta--ahead">' + diasDelta + ' días de adelanto</span>';
								} else {
									deltaHtml = ' <span class="pdc-delta pdc-delta--delay">' + Math.abs(diasDelta) + ' días de retraso</span>';
								}
							}
							if (id != ""){
								if(titulo==0){
									if(texto.includes("Terminado a tiempo")){
										return "<i class='fas fa-grin-stars fa-lg pdc-icon-state pdc-icon-ok'></i>  " + texto + deltaHtml;
										}else if(texto.includes("Terminado con retrasos")){
											return "<i class='fas fa-sad-cry fa-lg pdc-icon-state pdc-icon-amber'></i>  " + texto + deltaHtml;
										}else if(texto.includes("En Curso") && texto.includes("Proceso de contratación no iniciado")){
											return texto;
										}else if(texto.includes("En Curso")){
											return "<i class='fas fa-glasses fa-lg pdc-icon-state pdc-icon-info'></i>  " + texto + deltaHtml;
										}else if(texto.includes("Atrasado!!")){
											return "<i class='fas fa-skull-crossbones fa-lg pdc-icon-state pdc-icon-danger'></i>  " + texto + deltaHtml;
										}else{
											return "<b>No Registrado</b>";
										}
									}else{
										return texto;
									}
								}else{
									return "";
								}
							},
						},

					{
						'targets': [7],
						'width':'9%',
						'render': function ( data, type, row, meta ) {
							if (type !== 'display') {
								return data || '';
							}

							var titulo=row["titulo"];
								var procesoIniciado=row["procesoIniciado"]*1;
								var fechaInicioProceso= new Date(row["fechaElaboracionPliegos"]);
								var fechaInicioSemana = new Date(document.getElementById('Fecha_Inicio_SemYMD').value);
								var dias = (fechaInicioProceso - fechaInicioSemana)/(1000 * 3600 * 24);
								if (titulo == 0){
									if(fechaInicioSemana != ""){
										if(procesoIniciado == 0 && dias <= 7 && dias >= 0){
											return "<i class='fas fa-glasses fa-lg pdc-icon-state pdc-icon-warn'></i>  <b>" + data + "</b> (Debe comenzar en "+Math.floor(dias/7)+ " semana)";
										}else if(procesoIniciado == 0 && dias > 7){
											return "<i class='fas fa-glasses fa-lg pdc-icon-state pdc-icon-info'></i>  <b>" + data + "</b> (Debe comenzar en "+Math.floor(dias/7)+ " semanas)";
										}else if(procesoIniciado == 0 && dias < 0){
											return "<i class='fas fa-skull-crossbones fa-lg pdc-icon-state pdc-icon-danger'></i>  <b>" + data + "</b> (Ya debió comenzar)";
										}else if(procesoIniciado == 1){
											return "<i class='fas fa-check fa-lg pdc-icon-state pdc-icon-ok'></i>  <b>" + data + "</b>";
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
							'width':'4%',
							'render': function ( data, type, row, meta ) {
								var titulo=row["titulo"];
									var id = row["id"];
									var subcontratoPaquete = row["subcontratoPaquete"];
									var necesitaConfiguracion = row["necesitaConfiguracion"] || 0;
									var listoParaIniciar = row["listoParaIniciar"] || 0;
									var diasDelta = row["diasDelta"] || 0;
									if (id != ""){
										if(titulo==0){
											var readyIcon = '';
											if (listoParaIniciar === 1) {
												readyIcon = '<i class="fas fa-check-circle pdc-ready-icon pdc-ready-icon--ok" title="Listo para iniciar"></i>';
											} else if (necesitaConfiguracion === 1) {
												readyIcon = '<i class="fas fa-cog pdc-ready-icon pdc-ready-icon--config" title="Necesita configurar duraciones"></i>';
											} else if (diasDelta < 0) {
												readyIcon = '<i class="fas fa-exclamation-triangle pdc-ready-icon pdc-ready-icon--risk" title="En riesgo de retraso"></i>';
											}
											if(subcontratoPaquete > 1){
												boton=readyIcon + "<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' class='ps-btn-tight'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm' title='Eliminar' class='ps-btn-tight'><i class='fa fa-trash-alt fa-xs'></i></button>"
											}else{
												boton=readyIcon + "<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' class='ps-btn-tight'><i class='fa fa-edit fa-xs'></i></button>"	
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
								'targets': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32],
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
						applyPDCRowState(row, data);
				},
				"rowCallback": function(row, data) {
						applyPDCRowState(row, data);
				},
				"drawCallback": function(settings) {
					// Count visible rows by state
					var counts = {
						'missing': 0, 'critical': 0, 'delayed': 0, 
						'completed-late': 0, 'completed-ontime': 0, 
						'active': 0, 'not-started': 0
					};
					
					var api = this.api();
					var allData = api.rows({search:'applied'}).data(); 

					allData.each(function(rowData) {
						var s = getPDCState(rowData);
						if(counts[s] !== undefined) counts[s]++;
					});

					// Update Badges
					for(var k in counts) {
						$('#count-'+k).text('('+counts[k]+')');
					}
				},
				"initComplete": function() {
					var api = this.api();
					populatePdcSelectFilterOptions(api);
					bindPdcColumnFilters(api);
				},

		    "language": idioma_espanol
		  });

			// Dynamic Resize Listener
			$(window).off('resize.dtPDC orientationchange.dtPDC aia:viewport-scale-change.dtPDC').on('resize.dtPDC orientationchange.dtPDC aia:viewport-scale-change.dtPDC', function() {
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
				var tableSettings = table.settings()[0];
				var scrollBody = tableSettings && tableSettings.nScrollBody ? tableSettings.nScrollBody : null;
				if (scrollBody) {
					scrollBody.style.height = newHeight;
					scrollBody.style.maxHeight = newHeight;
				}
				if (tableSettings && tableSettings.oScroll) {
					tableSettings.oScroll.sY = newHeight;
				}
				table.columns.adjust().draw(false);
			});

			// 1. Action Buttons (Left)
			$("div.toolbarAcciones").html('<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Actions"><button id="btn_actualizarPDC" class="btn btn-primary btn-sm ps-btn-gap" title="Actualizar items" onclick="actualizarPDC()">Actualizar <i class="fas fa-sync fa-lg"></i></button><button id="btn_definirContratosPDC" class="btn btn-warning btn-sm ps-btn-gap" title="Desglosar Subcontratos" onclick="obtener_data_definirContratos()">Desglosar <i class="fa fa-list-ol fa-lg" aria-hidden="true"></i></button><button id="btn_pdc_wizard" class="btn btn-success btn-sm ps-btn-gap" title="Configuración rápida del Plan de Compras" onclick="$(\'#modalPdcWizard\').modal(\'show\')"><i class="fas fa-hat-wizard"></i> Wizard</button><button id="btn_soloAlertas" class="btn btn-sm ps-btn-gap pdc-btn-alertas" title="Mostrar solo paquetes que necesitan atención" onclick="toggleSoloAlertas()"><i class="fas fa-bell fa-lg"></i> Solo Alertas <span id="count-alertas" class="badge badge-light"></span></button></div>');
			
			// 2. Navigation Bar (Center/Middle)
			$("div.toolbarNavegacion").html('<div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="ps-module-switcher" role="tablist" aria-label="Navegacion general"><button id="btn_Actividades" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_listadoActividades&semana='+semana+'\'" aria-label="Ir a Actividades"><i class="fas fa-table" aria-hidden="true"></i><span>Actividades</span></button><button id="btn_contratos" type="button" class="ps-module-tab" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=info_contratos&semana='+semana+'\'" aria-label="Ir a Contratos"><i class="fas fa-file-alt" aria-hidden="true"></i><span>Contratos</span></button><button id="btn_planCompras" type="button" class="ps-module-tab is-active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=planCompras&semana='+semana+'&origen=planCompras\'" aria-label="Ir a Plan de Compras" aria-current="page"><i class="fas fa-shopping-cart" aria-hidden="true"></i><span>Plan de Compras</span></button></div></div>');

			$("div.toolbarFilaMensajes").html(`
				<div class="pdc-legend">
					<span class="pdc-legend-item missing" onclick="filterPDC('missing', event)"><span class="indicator"></span> Datos Faltantes <span id="count-missing" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item critical" onclick="filterPDC('critical', event)"><span class="indicator"></span> Crítico (No Iniciado) <span id="count-critical" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item delayed" onclick="filterPDC('delayed', event)"><span class="indicator"></span> Atrasado <span id="count-delayed" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item completed-late" onclick="filterPDC('completed-late', event)"><span class="indicator"></span> Terminado con Retraso <span id="count-completed-late" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item completed-ontime" onclick="filterPDC('completed-ontime', event)"><span class="indicator"></span> Terminado a Tiempo <span id="count-completed-ontime" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item active" onclick="filterPDC('active', event)"><span class="indicator"></span> En Curso <span id="count-active" class="count-badge">(...)</span></span>
					<span class="pdc-legend-item not-started" onclick="filterPDC('not-started', event)"><span class="indicator"></span> No Iniciado <span id="count-not-started" class="count-badge">(...)</span></span>
				</div>
				</div>
				<!-- Removed inline message -->
			`);
			
			// Inject Toast Container if not exists
			if ($("#mensajeActualizacion").length === 0) {
				$("body").append('<div id="mensajeActualizacion" class="pdc-toast"></div>');
			}
			
			// Click Handler Function (Multi-Select with Ctrl/Cmd)
			window.filterPDC = function(filterState, e) {
				var index = activePDCFilters.indexOf(filterState);
				
				// Standard Click (Exclusive Selection)
				if (!e.ctrlKey && !e.metaKey) {
					if (activePDCFilters.length === 1 && activePDCFilters[0] === filterState) {
						// Clicked the only active filter -> Toggle off (Show All)
						activePDCFilters = [];
					} else {
						// Select only this one
						activePDCFilters = [filterState];
					}
				} 
				// Ctrl/Cmd Click (Multi-Selection)
				else {
					if (index > -1) {
						activePDCFilters.splice(index, 1); // Remove
					} else {
						activePDCFilters.push(filterState); // Add
					}
				}

				// Update Visuals
				if (activePDCFilters.length === 0) {
					// No filters active -> Show all full opacity
					$('.pdc-legend-item').removeClass('inactive-filter');
				} else {
					// Filters active -> Dim all, then highlight selected
					$('.pdc-legend-item').addClass('inactive-filter');
					activePDCFilters.forEach(function(state) {
						$('.pdc-legend-item.' + state).removeClass('inactive-filter');
					});
				}
				table.draw();
			};

			// Solo Alertas Mode
			var soloAlertasActive = false;

			window.toggleSoloAlertas = function() {
				soloAlertasActive = !soloAlertasActive;
				var $btn = $('#btn_soloAlertas');
				
				if (soloAlertasActive) {
					$btn.addClass('active');
					activePDCFilters = ['delayed', 'critical', 'needs-config'];
				} else {
					$btn.removeClass('active');
					activePDCFilters = [];
				}
				
				table.draw();
			};

			// Custom search function for Solo Alertas
			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				if (!soloAlertasActive) {
					return true; // Show all when not active
				}
				
				var rowData = table.row(dataIndex).data();
				if (!rowData || rowData.titulo !== 0) {
					return true; // Always show headers
				}
				
				var state = getPDCState(rowData);
				var necesitaConfig = rowData.necesitaConfiguracion || 0;
				
				return state === 'delayed' || 
					   state === 'critical' || 
					   necesitaConfig === 1;
			});

			maestroPermisos(document.getElementById('permiso_canonico').value);
			obtener_data_editar("#dt_cliente tbody", table);
			obtener_id_eliminar("#dt_cliente tbody", table);
		}


		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
		  var permiso = document.getElementById('permiso_canonico').value;
			var semana = document.getElementById('semana').value;
		  if (permiso=="C") {
		    var only_once = false;
		  } else {
		    var only_once = true;
		  }
		  $(tbody).one("click", "td.celdaContenido, button.editar", function(e) {
				e.stopPropagation();	
				if (only_once == true) {
					$("#nombrePaqueteContratacion, #tipoPaquete, #tipoProveedorAdjudicado, #idProveedorExistente, #numeroContrato, #aplicaPolizas, #fechaVencimientoPolizas, #valorPresupuesto, #valorPrimeraNegociacion, #valorAdjudicado, #valorAnticipo, #actividadesDelContrato, #fechaInicioContrato, #fechaActual, #diasElaboracionPliegos, #diasEntregaPliegos, #diasReciboPropuestas, #diasCuadrosComparativos, #diasLegalizacionContrato, #diasFabricacion, #diasInsumosObra, #fechaRealElaboracionPliegos, #fechaRealEntregaPliegos, #fechaRealReciboPropuestas, #fechaRealCuadrosComparativos, #fechaRealLegalizacionContrato, #fechaRealFabricacion, #fechaRealInsumosObra, #fechaRealInicioProyectadaContrato, #observacionesContrato").val("");
					actualizarEstadoProveedorBloqueado(false);
					document.getElementById('mensajeModalInformacionAdjudicado').innerHTML = '';

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
						$("#diasEntregaPliegos").val(data.diasEntregaPliegos);
						$("#diasReciboPropuestas").val(data.diasReciboPropuestas);
						$("#diasCuadrosComparativos").val(data.diasCuadrosComparativos);
						$("#diasLegalizacionContrato").val(data.diasLegalizacionContrato);
						$("#diasFabricacion").val(data.diasFabricacion);
						$("#diasInsumosObra").val(data.diasInsumosObra);

						$("#fechaRealElaboracionPliegos").val(data.fechaRealElaboracionPliegos);
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
							actualizarEstadoProveedorBloqueado(false);
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
			var permiso = document.getElementById('permiso_canonico').value;
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Max_Semana = document.getElementById('Max_Semana').value;

			var definirContratosLayoutOptions = {
				container: "#modalDefinirContratos .modal-body",
				internalChrome: 230,
				bottomMargin: 18,
				minHeight: 180,
			};

			var getDefinirContratosTableHeight = function() {
				if (window.DataTableHeightManager && typeof window.DataTableHeightManager.calcHeight === "function") {
					return window.DataTableHeightManager.calcHeight(definirContratosLayoutOptions);
				}

				var alturahoja = $(window).height();
				var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height + document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
				return (alturahoja - posicionInicioTabla - 270) + "px";
			};

			$("#modalDefinirContratos").off("shown.bs.modal.dtDefinirContratos").on("shown.bs.modal.dtDefinirContratos", function(){
				var alturatabla = getDefinirContratosTableHeight();
				var tableDefinirContratos = $("#dt_definirContratos").DataTable({
					"dom": "t<'row'<'col-md-12'>><'clear'>",
					"destroy": true,
					"orderFixed":[4, "asc"],
					"autoWidth": false,
					"fixedHeader": false,
					"scrollX": false,
					//                console.log($(document).height());
					"scrollY": alturatabla,
					"scrollCollapse": true,
					"responsive": true,
					"paging": false,
					"ajax": {
					"method": "POST",
					"url": "/api/pdc/list?db="+db+"&semana="+Max_Semana+"&definirContratos=1"
					},
					"lengthMenu": [100, 200, 500],
					'columnDefs': [
						{ orderable: false, targets: '_all' },

						{
							'targets': [1],
							'width':'25%',
						},
						{
							'targets': [2],
							'width':'50%',
						},
						{
							'targets': [3],
							'width':'25%',
						},
					],

					"columns":[
						{"data":"consecutivo", "visible":false},
						{"data":"tipoPaquete"},
						{"data":"paqueteContratacion"},
						{"data":"numeroSubcontratos",
							'render': function(data, type, row, meta) {
								data = "<input type='number' class='numeroSubcontratos w-80' step='1' min='1' value=" + data + ">";
			
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

				if (window.DataTableHeightManager && typeof window.DataTableHeightManager.applyToDataTable === "function") {
					window.DataTableHeightManager.applyToDataTable(tableDefinirContratos, definirContratosLayoutOptions);
				}

				$(window).off('resize.dtDefinirContratos orientationchange.dtDefinirContratos aia:viewport-scale-change.dtDefinirContratos').on('resize.dtDefinirContratos orientationchange.dtDefinirContratos aia:viewport-scale-change.dtDefinirContratos', function() {
					if (!$('#modalDefinirContratos').hasClass('show')) {
						return;
					}

					if (window.DataTableHeightManager && typeof window.DataTableHeightManager.applyToDataTable === "function") {
						window.DataTableHeightManager.applyToDataTable(tableDefinirContratos, definirContratosLayoutOptions);
						return;
					}

					var newHeight = getDefinirContratosTableHeight();
					var modalTableSettings = tableDefinirContratos.settings()[0];
					var modalScrollBody = modalTableSettings && modalTableSettings.nScrollBody ? modalTableSettings.nScrollBody : null;
					if (modalScrollBody) {
						modalScrollBody.style.height = newHeight;
						modalScrollBody.style.maxHeight = newHeight;
					}
					if (modalTableSettings && modalTableSettings.oScroll) {
						modalTableSettings.oScroll.sY = newHeight;
					}
					tableDefinirContratos.columns.adjust().draw(false);
				});

				editarDefinirContratos();
			});
			
			$("#modalDefinirContratos").modal("show");

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
				url: "/api/pdc/save",
				contenttype:"charset=utf-8",
				data: frm,
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
				if(json_info == "sinModificaciones" || json_info == "conModificaciones"){
					$("#modalDefinirContratos").modal("hide");
					actualizarPDC();
				}
			});
		}

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
			      document.getElementById('mensajeModalInformacionAdjudicado').style.color = "#1a3c2a";
			      document.getElementById('mensajeModalInformacionAdjudicado').innerHTML = "Se creará un nuevo proveedor en la base de datos";
			    } else {
			      nitAdjudicado.value = json_info['data'][0]['NIT'];
			      subcontratistaAdjudicado.value = json_info['data'][0]['subcontratista'];
			      correoAdjudicado.value = json_info['data'][0]['correo_contacto'];
			      idProveedorExistente.value = json_info['data'][0]['Id'];
			      subcontratistaAdjudicado.setAttribute("readonly", true);
			      correoAdjudicado.setAttribute("readonly", true);
			      actualizarEstadoProveedorBloqueado(true);
			      document.getElementById('mensajeModalInformacionAdjudicado').style.color = "#1a3c2a";
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
		  if (finSemanaActual >= fechaRealInicioProyectadaContrato && finSemanaActual >= fechaRealInsumosObra && finSemanaActual >= fechaRealFabricacion && finSemanaActual >= fechaRealLegalizacionContrato && finSemanaActual >= fechaRealCuadrosComparativos && finSemanaActual >= fechaRealReciboPropuestas && finSemanaActual >= fechaRealEntregaPliegos && finSemanaActual >= fechaRealElaboracionPliegos) {
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
		    ["InicioProyectadaContrato", "Proceso de contratación finalizado y actividades del contrato iniciadas"]
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
		  if (posicion >= deberiaHoy) {
		    divDiagnostico.innerHTML = "En Curso";
				//console.log((new Date(document.getElementById("fechaReal" + pasos[posicion][0]).value) -  new Date(document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value))/(1000 * 3600 * 24));
		    // if ((posicion == -1 && deberiaHoy == -1)) {
		    //   divDiagnostico.innerHTML = "En Curso";
		    // } else if (document.getElementById("fechaReal" + pasos[posicion][0]).value <= document.getElementById("fecha" + pasos[posicion][0] + "Teorica").value) {
		    //   divDiagnostico.innerHTML = "En Curso";
		    // } else {
		    //   divDiagnostico.innerHTML = "Atrasado!!";
		    // }
		  } else {
		    divDiagnostico.innerHTML = "Atrasado!!";
		  }
		  if (divEstadoProceso.innerHTML == pasos[7][1]) {
		    if (document.getElementById("fechaReal" + pasos[7][0]).value > document.getElementById("fecha" + pasos[7][0] + "Teorica").value) {
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
				url: "/legacy/pdc/actualizar_pdc.php?db="+db,
				contenttype:"charset=utf-8",
				data: {"db": db, "semana": semana}
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
					// document.getElementById('mensaje').style.color = "blue";
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
					window.AIA.Notice.error('No se pudo actualizar el Plan de Compras' + (pdcSyncOrigin ? ' desde ' + pdcSyncOrigin : '') + '.');
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
					url: "/api/pdc/save",
          			contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
					// console.log(json_info);
					// mostrar_mensaje( json_info );
					if(json_info == "OK"){
						$("#modalContrato #cuadro4").scrollTop(0);
						$("#modalContrato").modal("hide");
						recargarTabla('');

						document.getElementById('mensajeActualizacion').style.color = "#333"; // Use dark text for card
						document.getElementById('mensajeActualizacion').innerHTML = "El paquete de contratación <b>\"" + paquete + "\"</b> se ha actualizado";
						$("#mensajeActualizacion")
							.show() // Trigger CSS Animation
							.delay(4000) // Wait
							.fadeOut(1000, function(){
								$(this).html(""); // Clean up
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
			// Usar window.rbacCapabilities si está inyectado, de lo contrario fallback a la variable JS permiso local.
			var canEdit = false;
			if (typeof window.rbacCapabilities !== 'undefined') {
				canEdit = window.rbacCapabilities.canManagePdC || window.rbacCapabilities.canManageContracts || false;
			} else {
				var permiso = document.getElementById('permiso_canonico').value;
		  		if (!(permiso==="G" || permiso==="S" || permiso==="SG" || permiso==="R" || permiso==="DCV" || permiso==="V" || permiso==="C")) {
					canEdit = true;
				}
			}

		  	if (!canEdit) {
				// No hace nada
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
					url: "/api/pdc/save?",
					contenttype: "charset=utf-8",
					data: {
						"Id": Id,
						"opcion": opcion,
						"paqueteContratacion": paqueteContratacion,
						"db": db,
						"semana": semana
					}
				}).done(function(info) {
					var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
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
							$("#cuadroTabla").slideDown("slow");
								$("#cuadro3").slideDown("slow");
								$("#mensajeActualizacion").html( texto ).css({"color": "#333"});
								$("#mensajeActualizacion")
									.show()
									.delay(4000)
									.fadeOut(1000, function(){
										$(this).html(""); 
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

		/* Wizard PDC */
		var pdcWizard = { step: 1, suggestions: [], selected: [], durations: {} };

		var wizardShowStep = function(step) {
			pdcWizard.step = step;
			$('.wizard-pane').addClass('d-none');
			$('#wizardStep' + step).removeClass('d-none');
			$('#wizardSteps .nav-link').removeClass('active').addClass('disabled');
			$('#wizardSteps .nav-link[data-step="' + step + '"]').addClass('active').removeClass('disabled');
			$('#btn_wizard_prev').prop('disabled', step === 1);
			$('#btn_wizard_next').toggleClass('d-none', step >= 3);
			$('#btn_wizard_generate').toggleClass('d-none', step < 3);
			if (step === 4) {
				$('#btn_wizard_next').addClass('d-none');
				$('#btn_wizard_generate').addClass('d-none');
				$('#btn_wizard_prev').prop('disabled', true);
			}
		};

		var wizardLoadSuggestions = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('Max_Semana').value;
			$('#wizardActividadesResumen').removeClass('alert-danger').addClass('alert-info').html('Analizando programa general...');
			$('#wizardActividadesList').html('');

			$.ajax({
				method: 'POST',
				url: '/api/pdc/auto/suggest?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
				dataType: 'json'
			}).done(function(response) {
				if (!response || response.respuesta !== 'BIEN') {
					$('#wizardActividadesResumen').removeClass('alert-info').addClass('alert-danger').html(response.mensaje || 'Error al cargar sugerencias.');
					return;
				}
				var data = response.data || {};
				pdcWizard.suggestions = data.suggestions || [];
				pdcWizard.manualReview = data.manualReview || [];
				var total = data.totalActividades || 0;
				var auto = data.autoSeleccionadas || 0;
				var familias = data.familiasSugeridas || 0;
				var coverage = total > 0 ? Math.round(((total - (pdcWizard.manualReview || []).length) / total) * 100) : 0;

				$('#wizardActividadesResumen').html(
					'<strong>' + total + '</strong> actividades encontradas. ' +
					'<strong>' + familias + '</strong> familias detectadas. ' +
					'<strong>' + auto + '</strong> auto-seleccionadas. ' +
					'Cobertura: <strong>' + coverage + '%</strong>'
				);

				var html = '';
				pdcWizard.suggestions.forEach(function(sug, i) {
					var selected = sug.selected ? 'checked' : '';
					html += '<div class="card mb-2 pdc-wizard-card" data-index="' + i + '">';
					html += '<div class="card-body d-flex align-items-center">';
					html += '<input type="checkbox" class="mr-2 pdc-wizard-check" data-index="' + i + '" ' + selected + '>';
					html += '<div class="flex-grow-1">';
					html += '<strong>' + (sug.familiaNombre || 'Familia ' + sug.familiaId) + '</strong>';
					html += ' <span class="badge badge-' + (sug.confianza >= 70 ? 'success' : sug.confianza >= 40 ? 'warning' : 'secondary') + '">' + (sug.confianza || 0) + '%</span>';
					html += ' <small class="text-muted">(' + (sug.actividades || []).length + ' actividades)</small>';
					html += '</div></div></div>';
				});
				$('#wizardActividadesList').html(html);

				$(document).off('change.wizardCheck', '.pdc-wizard-check').on('change.wizardCheck', '.pdc-wizard-check', function() {
					var idx = parseInt($(this).data('index'), 10);
					if (pdcWizard.suggestions[idx]) {
						pdcWizard.suggestions[idx].selected = $(this).is(':checked');
					}
				});
			}).fail(function() {
				$('#wizardActividadesResumen').removeClass('alert-info').addClass('alert-danger').html('Error de conexión.');
			});
		};

		var wizardLoadFamilies = function() {
			pdcWizard.selected = pdcWizard.suggestions.filter(function(s) { return s.selected && s.optionId; });
			if (pdcWizard.selected.length === 0) {
				AIA.Notice.warning('Selecciona al menos una familia.');
				return false;
			}

			var html = '';
			pdcWizard.selected.forEach(function(sug, i) {
				var options = sug.options || [];
				var optionHtml = '<select class="form-control form-control-sm pdc-wizard-option" data-index="' + i + '">';
				options.forEach(function(opt) {
					var sel = opt.id === sug.optionId ? 'selected' : '';
					optionHtml += '<option value="' + opt.id + '" ' + sel + '>' + (opt.nombre || opt.tipoContrato || 'Opción') + '</option>';
				});
				optionHtml += '</select>';

				html += '<div class="card mb-2"><div class="card-body">';
				html += '<div class="d-flex justify-content-between align-items-center">';
				html += '<div><strong>' + (sug.familiaNombre || 'Familia') + '</strong>';
				html += ' <span class="badge badge-' + (sug.confianza >= 70 ? 'success' : 'warning') + '">' + (sug.confianza || 0) + '%</span></div>';
				html += '<div class="w-50">' + optionHtml + '</div>';
				html += '</div>';
				html += '<small class="text-muted mt-1 d-block">' + (sug.actividades || []).length + ' actividades asignadas</small>';
				html += '</div></div>';
			});

			$('#wizardFamiliasResumen').html('<strong>' + pdcWizard.selected.length + '</strong> familias seleccionadas. Revisa las opciones de contrato:');
			$('#wizardFamiliasList').html(html);

			$(document).off('change.wizardOption', '.pdc-wizard-option').on('change.wizardOption', '.pdc-wizard-option', function() {
				var idx = parseInt($(this).data('index'), 10);
				var optId = parseInt($(this).val(), 10);
				if (pdcWizard.selected[idx]) {
					pdcWizard.selected[idx].optionId = optId;
				}
			});

			return true;
		};

		var wizardLoadDurations = function() {
			var html = '<table class="table table-sm table-striped"><thead><tr>' +
				'<th>Paquete</th><th>Tipo</th><th>Elab.</th><th>Entrega</th><th>Recibo</th>' +
				'<th>Cuadros</th><th>Legal.</th><th>Fab.</th><th>Insumos</th>' +
				'</tr></thead><tbody>';

			var fetches = [];
			pdcWizard.selected.forEach(function(sug, i) {
				var selectedOption = null;
				(sug.options || []).forEach(function(opt) {
					if (opt.id === sug.optionId) selectedOption = opt;
				});
				var items = (selectedOption && selectedOption.items) || [];
				if (items.length === 0) {
					items = [{ paqueteContratacion: sug.familiaNombre || 'Paquete', tipoContrato: selectedOption ? selectedOption.tipoContrato : '' }];
				}
				items.forEach(function(item) {
					var paquete = item.paqueteContratacion || item.nombre || sug.familiaNombre || '';
					var tipo = item.tipoContrato || '';
					html += '<tr data-wizard-row="' + i + '">';
					html += '<td>' + paquete + '</td><td>' + tipo + '</td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasElaboracionPliegos" data-idx="' + i + '" value="8" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasEntregaPliegos" data-idx="' + i + '" value="7" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasReciboPropuestas" data-idx="' + i + '" value="1" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasCuadrosComparativos" data-idx="' + i + '" value="5" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasLegalizacionContrato" data-idx="' + i + '" value="10" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasFabricacion" data-idx="' + i + '" value="0" min="0" style="width:60px"></td>';
					html += '<td><input type="number" class="form-control form-control-sm pdc-wiz-dur" data-field="diasInsumosObra" data-idx="' + i + '" value="0" min="0" style="width:60px"></td>';
					html += '</tr>';

					fetches.push({ index: i, paquete: paquete, tipo: tipo });
				});
			});

			html += '</tbody></table>';
			$('#wizardDuracionesResumen').html('Ajusta las duraciones antes de generar:');
			$('#wizardDuracionesList').html(html);

			fetches.forEach(function(f) {
				$.ajax({
					method: 'GET',
					url: '/api/pdc/duracion-sugerida?paquete=' + encodeURIComponent(f.paquete) + '&tipoPaquete=' + encodeURIComponent(f.tipo),
					dataType: 'json'
				}).done(function(resp) {
					if (resp && resp.respuesta === 'BIEN' && resp.duracion) {
						var d = resp.duracion;
						var row = $('tr[data-wizard-row="' + f.index + '"]');
						row.find('[data-field="diasElaboracionPliegos"]').val(d.dias_elaboracion || 8);
						row.find('[data-field="diasEntregaPliegos"]').val(d.dias_entrega || 7);
						row.find('[data-field="diasReciboPropuestas"]').val(d.dias_recibo || 1);
						row.find('[data-field="diasCuadrosComparativos"]').val(d.dias_cuadros || 5);
						row.find('[data-field="diasLegalizacionContrato"]').val(d.dias_legalizacion || 10);
						row.find('[data-field="diasFabricacion"]').val(d.dias_fabricacion || 0);
						row.find('[data-field="diasInsumosObra"]').val(d.dias_insumos || 0);
					}
				});
			});
		};

		var wizardBuildConfirm = function() {
			var totalPackages = 0;
			var summaryHtml = '<ul class="list-group">';
			pdcWizard.selected.forEach(function(sug) {
				totalPackages++;
				summaryHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
				summaryHtml += (sug.familiaNombre || 'Familia');
				summaryHtml += '<span class="badge badge-primary">' + ((sug.actividades || []).length) + ' act.</span>';
				summaryHtml += '</li>';
			});
			summaryHtml += '</ul>';

			$('#wizardConfirmResumen').html(
				'<strong>Resumen:</strong> Se crearán <strong>' + totalPackages + '</strong> paquetes de contratación en el Plan de Compras.'
			);
			$('#wizardConfirmList').html(summaryHtml);
		};

		var wizardGenerate = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('Max_Semana').value;

			$('tr[data-wizard-row]').each(function() {
				var idx = parseInt($(this).attr('data-wizard-row'), 10);
				if (!pdcWizard.selected[idx]) return;
				if (!pdcWizard.selected[idx].durationOverrides) {
					pdcWizard.selected[idx].durationOverrides = {};
				}
				$(this).find('.pdc-wiz-dur').each(function() {
					var field = $(this).data('field');
					pdcWizard.selected[idx].durationOverrides[field] = parseInt($(this).val(), 10) || 0;
				});
			});

			var payload = { suggestions: pdcWizard.selected.map(function(sug) {
				var s = {
					familiaId: sug.familiaId,
					optionId: sug.optionId,
					selected: true,
					familiaNombre: sug.familiaNombre,
					confianza: sug.confianza,
					actividades: sug.actividades || []
				};
				if (sug.durationOverrides) {
					s.durationOverrides = sug.durationOverrides;
				}
				return s;
			})};

			$('#btn_wizard_generate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando...');

			$.ajax({
				method: 'POST',
				url: '/api/pdc/auto/apply?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				data: JSON.stringify(payload)
			}).done(function(response) {
				if (!response || response.respuesta !== 'BIEN') {
					AIA.Notice.error((response && response.mensaje) || 'Error al generar PDC.');
					return;
				}
				var insertados = response.insertados || 0;
				var omitidos = response.omitidos || 0;
				AIA.Notice.success('PDC generado: ' + insertados + ' paquetes creados, ' + omitidos + ' omitidos.');
				wizardShowStep(4);
				$('#wizardConfirmResumen').removeClass('alert-warning').addClass('alert-success').html(
					'<strong>¡Completado!</strong> ' + insertados + ' paquetes creados exitosamente.'
				);
				$('#wizardConfirmList').html('<p>Los paquetes ya están disponibles en el Plan de Compras.</p>');
				if (typeof listar === 'function') {
					listar();
				}
			}).fail(function() {
				AIA.Notice.error('Error de conexión al generar PDC.');
			}).always(function() {
				$('#btn_wizard_generate').prop('disabled', false).html('Generar Plan de Compras');
			});
		};

		$('#btn_wizard_next').on('click', function() {
			if (pdcWizard.step === 1) {
				if (wizardLoadFamilies()) {
					wizardShowStep(2);
				}
			} else if (pdcWizard.step === 2) {
				wizardLoadDurations();
				wizardShowStep(3);
			} else if (pdcWizard.step === 3) {
				wizardBuildConfirm();
				wizardShowStep(4);
			}
		});

		$('#btn_wizard_prev').on('click', function() {
			if (pdcWizard.step > 1) {
				wizardShowStep(pdcWizard.step - 1);
			}
		});

		$('#btn_wizard_generate').on('click', function() {
			wizardGenerate();
		});

		$('#modalPdcWizard').on('show.bs.modal', function() {
			pdcWizard = { step: 1, suggestions: [], selected: [], durations: {} };
			wizardShowStep(1);
			wizardLoadSuggestions();
		});

	</script>
</body>
</html>
