<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="/js/linksComunesHead2.js?v=piStateColors3" charset="utf-8"></script>
    <style>
        .filaBotones, .ps-actions-row {
            overflow: visible !important;
        }
        .ps-actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 6px;
        }
        /* Nuclear specificity to force rectangular tools for DataTable Header */
        body #dt_cliente_wrapper .filaBotones .btn, 
        body #dt_cliente_wrapper .filaBotones button, 
        body #dt_cliente_wrapper .filaMensajes .btn, 
        body #dt_cliente_wrapper .filaMensajes button, 
        body #dt_cliente_wrapper .filaMensajes .form-control, 
        body #dt_cliente_wrapper .dataTables_filter input {
            border-radius: 4px !important;
            -webkit-appearance: none !important;
            appearance: none !important;
        }
        
        /* Dropdown de Navegación por Hover - Visibility Fix */
        .ps-dropdown-nav {
            position: relative;
            display: inline-block;
            z-index: 1000;
        }

        /* Puente invisible para evitar que el dropdown se cierre en el espacio en blanco */
        .ps-dropdown-nav::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -15px;
            height: 15px;
        }

        .ps-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: #ffffff;
            min-width: 240px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.2);
            z-index: 1001;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            overflow: visible !important;
            margin-top: 4px;
        }

        .ps-dropdown-nav:hover .ps-dropdown-content,
        .ps-dropdown-nav.is-open .ps-dropdown-content {
            display: block !important;
        }

        .ps-dropdown-item {
            color: #334155;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .ps-dropdown-item:last-child {
            border-bottom: none;
        }

        .ps-dropdown-item:hover {
            background-color: #f1f5f9;
            color: #1e5ea8;
        }

        .ps-dropdown-item.is-active {
            background-color: #eff6ff !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            border-left: 3px solid #1e5ea8 !important;
        }

        .ps-dropdown-item i {
            width: 18px;
            text-align: center;
            color: #64748b;
        }

        .btn-dropdown-trigger {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            padding: 0.35rem 0.75rem;
            border-radius: 4px !important;
        }

        .btn-dropdown-trigger:hover {
            background: #f8fafc !important;
            border-color: #1e5ea8 !important;
        }
    </style>
</head>

<!--Etiqueta superior-->
<body>
    <input type="hidden" id="semana_PHP" value="<?php echo $semana; ?>">
    <input type="hidden" id="db_PHP" value="<?php echo $dbName; ?>">
    <input type="hidden" id="proyecto_PHP" value="<?php echo $proyecto; ?>">

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="CIC" aria-hidden="true">
		<input type="hidden" id="Id" name="Id" value="0" aria-hidden="true">
		<input type="hidden" id="ultimaSemanaContratista" name="ultimaSemanaContratista" value="" aria-hidden="true">
		<input type="hidden" id="opcion" name="opcion" value="registrar" aria-hidden="true">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="" aria-hidden="true">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<div class="row formularioRegistro">
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla table-responsive-custom ps-table-wrap">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12 p-0 w-100 ps-table-container">
			<table id="dt_cliente" class="dt_programacionSemanal table table-bordered table-hover table-responsive-sm table-sm w-100 ps-table" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Id</th>
						<th>Semanas en el Proyecto</th>
						<th>Última Semana</th>
						<th>Sub-Contratista</th>
						<th>Alcance</th>
						<th>Tipo de Proveedor</th>
						<th>PAC</th>
						<th>% Completado</th>
						<th>Calidad</th>
						<th>Gestión Socio-Ambiental</th>
						<th>SST</th>
						<th>Administración del Contrato</th>
						<th>Calificación Integral</th>
						<th>Observaciones</th>
						<th>mdo_cal_1</th>
						<th>mdo_cal_2</th>
						<th>mdo_cal_3</th>
						<th>mdo_adm_1</th>
						<th>mdo_adm_2</th>
						<th>mdo_adm_3</th>
						<th>mdo_adm_4</th>
						<th>mdo_adm_5</th>
						<th>mdo_gsa_1</th>
						<th>mdo_gsa_2</th>
						<th>mdo_gsa_3</th>
						<th>mdo_gsa_4</th>
						<th>mdo_gsa_5</th>
						<th>mdo_gsa_6</th>
						<th>mdo_gsa_7</th>
						<th>mdo_gsa_8</th>
						<th>mdo_sst_1</th>
						<th>mdo_sst_2</th>
						<th>mdo_sst_3</th>
						<th>mdo_sst_4</th>
						<th>mdo_sst_5</th>
						<th>mdo_sst_6</th>
						<th>mdo_sst_7</th>
						<th>mdo_sst_8</th>
						<th>mdo_sst_9</th>
						<th>mdo_sst_10</th>
						<th>si_cal_1</th>
						<th>si_cal_2</th>
						<th>si_cal_3</th>
						<th>si_adm_1</th>
						<th>si_adm_2</th>
						<th>si_adm_3</th>
						<th>si_adm_4</th>
						<th>si_adm_5</th>
						<th>si_adm_6</th>
						<th>si_gsa_1</th>
						<th>si_gsa_2</th>
						<th>si_gsa_3</th>
						<th>si_gsa_4</th>
						<th>si_gsa_5</th>
						<th>si_gsa_6</th>
						<th>si_gsa_7</th>
						<th>si_gsa_8</th>
						<th>si_gsa_9</th>
						<th>si_gsa_10</th>
						<th>si_gsa_11</th>
						<th>si_gsa_12</th>
						<th>si_gsa_13</th>
						<th>si_gsa_14</th>
						<th>si_sst_1</th>
						<th>si_sst_2</th>
						<th>si_sst_3</th>
						<th>si_sst_4</th>
						<th>si_sst_5</th>
						<th>si_sst_6</th>
						<th>si_sst_7</th>
						<th>si_sst_8</th>
						<th>si_sst_9</th>
						<th>si_sst_10</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- Se crea el Modal que solicita calificar a un contratista de Suministro e Instalación -->
		<div class="modal_cic_si modal fade aia-modal" id="modalcic_si" tabindex="-1" role="dialog" aria-labelledby="modal_cic_siLabel">
		  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalcic_siLabel">
		          <p class="modal-body-texto-cic_si" id="modal-body-texto-cic_si"></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="recargarTabla('listar')"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadroModal" class="cuadroModal col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal cic_si" id="formulario_cic_si" action="" method="POST">
									<div class="form-group">
										<h2 class="col-sm-offset-2 col-sm-12 text-center" id="actualizacion"></h2>
									</div>
		              <div class="form-group">
										<div class="parametro_cic form-group" id="si_cal">
												<div class="form_eval form-group">
														<h3 id="form_calidad">Calidad</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La calidad del producto suministrado e instalado:</legend>
															<input type="radio" name="si_cal_1" id="si_cal_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_1" id="si_cal_1_0" value="0"><label class="form-check-label font-weight-normal" for="si_cal_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_1" id="si_cal_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_cal_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_1" id="si_cal_1_100" value="1"><label class="form-check-label font-weight-normal" for="si_cal_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_1" id="si_cal_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_cal_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La entrega de procedimientos y/o protocolos para asegurar el cumplimiento de requisitos:</legend>
															<input type="radio" name="si_cal_2" id="si_cal_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_2" id="si_cal_2_0" value="0"><label class="form-check-label font-weight-normal" for="si_cal_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_2" id="si_cal_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_cal_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_2" id="si_cal_2_100" value="1"><label class="form-check-label font-weight-normal" for="si_cal_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_2" id="si_cal_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_cal_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La entrega oportuna de los certificados de calibración de los equipos de medición, certificaciones y permisos ambientales:</legend>
															<input type="radio" name="si_cal_3" id="si_cal_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_3" id="si_cal_3_0" value="0"><label class="form-check-label font-weight-normal" for="si_cal_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_3" id="si_cal_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_cal_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_3" id="si_cal_3_100" value="1"><label class="form-check-label font-weight-normal" for="si_cal_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_cal_3" id="si_cal_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_cal_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="si_adm">
												<div class="form_eval form-group">
														<h3 id="form_adm">Administración del Contrato</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">El cumplimiento de las necesidades y oportunidad de personal en la obra:</legend>
															<input type="radio" name="si_adm_1" id="si_adm_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_1" id="si_adm_1_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_1" id="si_adm_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_1" id="si_adm_1_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_1" id="si_adm_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La disponibilidad, oportunidad y estado de la maquinaria, equipo y herramienta de trabajo:</legend>
															<input type="radio" name="si_adm_2" id="si_adm_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_2" id="si_adm_2_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_2" id="si_adm_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_2" id="si_adm_2_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_2" id="si_adm_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La atención de solicitudes, quejas y reclamos:</legend>
															<input type="radio" name="si_adm_3" id="si_adm_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_3" id="si_adm_3_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_3" id="si_adm_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_3" id="si_adm_3_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_3" id="si_adm_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Los procedimientos administrativos y legales de la obra:</legend>
															<input type="radio" name="si_adm_4" id="si_adm_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_4" id="si_adm_4_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_4" id="si_adm_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_4" id="si_adm_4_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_4" id="si_adm_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">El cumplimiento del procedimiento de facturación:</legend>
															<input type="radio" name="si_adm_5" id="si_adm_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_5" id="si_adm_5_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_5" id="si_adm_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_5" id="si_adm_5_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_5" id="si_adm_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">El tiempo establecido para la liquidación del contrato:</legend>
															<input type="radio" name="si_adm_6" id="si_adm_6_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_6" id="si_adm_6_0" value="0"><label class="form-check-label font-weight-normal" for="si_adm_6_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_6" id="si_adm_6_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_adm_6_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_6" id="si_adm_6_100" value="1"><label class="form-check-label font-weight-normal" for="si_adm_6_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_adm_6" id="si_adm_6_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_adm_6_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="si_gsa">
												<div class="form_eval form-group">
														<h3 id="form_GSA">Gestión Socio-Ambiental</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Presentar certificados durante los 15 primeros días del mes en donde se relacionen las volquetas  con PIN (Para Bogotá y cartagena) y Medellín los primeros 5 días del generador y sitio de disposición final con cantidades.  Las volquetas deben contar con número de PIN en Bogotá y Cartagena. Suministrar el control de los residuos que se han salido de la obra, con placa, fecha, cantidad, sitio de disposición mensual. Presentar volqueta con modelos superiores al año 2012. Contar con auxiliares de tránsito certificados para facilitar el movimiento interno y externo de los vehículos. El sitio de disposición final debe estar inscrito ante autoridad ambiental de acuerdo a la clasificación de la resolución 472 de 2017:</legend>
															<input type="radio" name="si_gsa_1" id="si_gsa_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_1" id="si_gsa_1_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_1" id="si_gsa_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_1" id="si_gsa_1_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_1" id="si_gsa_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">En caso de contar con la posibilidad de realizar el aprovechamiento de este material, notificar antes de realizar la actividad en la obra para verificar la legalidad de la situación:</legend>
															<input type="radio" name="si_gsa_2" id="si_gsa_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_2" id="si_gsa_2_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_2" id="si_gsa_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_2" id="si_gsa_2_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_2" id="si_gsa_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Las volquetas deben estar cubiertas con material completamente hermetico, y con carpado automatico. En caso de no llegar en estas condiciones no se permitirá el ingreso a obra, entrar y salir con las volcos cubiertas, compuertas y puertas cerradas y demás:</legend>
															<input type="radio" name="si_gsa_3" id="si_gsa_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_3" id="si_gsa_3_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_3" id="si_gsa_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_3" id="si_gsa_3_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_3" id="si_gsa_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Presentar los permisos ambientales correspondientes a la actividad (Licencias, títulos mineros, Plan de manejo ambiental, rucom, y demás permisos para operación, ) y los certificados mensuales de la entrega en obra:</legend>
															<input type="radio" name="si_gsa_4" id="si_gsa_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_4" id="si_gsa_4_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_4" id="si_gsa_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_4" id="si_gsa_4_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_4" id="si_gsa_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Presentar el certificado en donde se evidencie que el material de suministro presenta algún porcentaje (%) de material reciclable, cuando aplique:</legend>
															<input type="radio" name="si_gsa_5" id="si_gsa_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_5" id="si_gsa_5_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_5" id="si_gsa_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_5" id="si_gsa_5_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_5" id="si_gsa_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Suministrar en caso de ingreso de maquinaria: SOAT; revisión tecnicomecanica, hoja de vida (donde se incluya el mantenimiento preventido), programación de mantenimientos y matricula, poliza de terceros:</legend>
															<input type="radio" name="si_gsa_6" id="si_gsa_6_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_6" id="si_gsa_6_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_6_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_6" id="si_gsa_6_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_6_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_6" id="si_gsa_6_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_6_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_6" id="si_gsa_6_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_6_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Mantener la rotulación, clasificación y almacenamiento de  los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:</legend>
															<input type="radio" name="si_gsa_7" id="si_gsa_7_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_7" id="si_gsa_7_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_7_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_7" id="si_gsa_7_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_7_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_7" id="si_gsa_7_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_7_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_7" id="si_gsa_7_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_7_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar la adecuada separación, almacenamiento y  disposición interna y externa (cuando aplique) de los residuos generados en obra:</legend>
															<input type="radio" name="si_gsa_8" id="si_gsa_8_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_8" id="si_gsa_8_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_8_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_8" id="si_gsa_8_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_8_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_8" id="si_gsa_8_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_8_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_8" id="si_gsa_8_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_8_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):</legend>
															<input type="radio" name="si_gsa_9" id="si_gsa_9_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_9" id="si_gsa_9_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_9_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_9" id="si_gsa_9_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_9_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_9" id="si_gsa_9_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_9_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_9" id="si_gsa_9_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_9_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:</legend>
															<input type="radio" name="si_gsa_10" id="si_gsa_10_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_10" id="si_gsa_10_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_10_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_10" id="si_gsa_10_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_10_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_10" id="si_gsa_10_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_10_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_10" id="si_gsa_10_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_10_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:</legend>
															<input type="radio" name="si_gsa_11" id="si_gsa_11_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_11" id="si_gsa_11_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_11_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_11" id="si_gsa_11_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_11_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_11" id="si_gsa_11_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_11_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_11" id="si_gsa_11_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_11_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):</legend>
															<input type="radio" name="si_gsa_12" id="si_gsa_12_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_12" id="si_gsa_12_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_12_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_12" id="si_gsa_12_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_12_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_12" id="si_gsa_12_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_12_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_12" id="si_gsa_12_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_12_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:</legend>
															<input type="radio" name="si_gsa_13" id="si_gsa_13_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_13" id="si_gsa_13_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_13_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_13" id="si_gsa_13_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_13_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_13" id="si_gsa_13_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_13_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_13" id="si_gsa_13_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_13_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Acatar las acciones recomendadas durante recorridos de obra:</legend>
															<input type="radio" name="si_gsa_14" id="si_gsa_14_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_14" id="si_gsa_14_0" value="0"><label class="form-check-label font-weight-normal" for="si_gsa_14_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_14" id="si_gsa_14_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_gsa_14_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_14" id="si_gsa_14_100" value="1"><label class="form-check-label font-weight-normal" for="si_gsa_14_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_gsa_14" id="si_gsa_14_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_gsa_14_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="si_sst">
												<div class="form_eval form-group">
														<h3 id="form_sst">Seguridad y Salud en el Trabajo</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:</legend>
															<input type="radio" name="si_sst_1" id="si_sst_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_1" id="si_sst_1_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_1" id="si_sst_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_1" id="si_sst_1_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_1" id="si_sst_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:</legend>
															<input type="radio" name="si_sst_2" id="si_sst_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_2" id="si_sst_2_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_2" id="si_sst_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_2" id="si_sst_2_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_2" id="si_sst_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:</legend>
															<input type="radio" name="si_sst_3" id="si_sst_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_3" id="si_sst_3_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_3" id="si_sst_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_3" id="si_sst_3_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_3" id="si_sst_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:</legend>
															<input type="radio" name="si_sst_4" id="si_sst_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_4" id="si_sst_4_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_4" id="si_sst_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_4" id="si_sst_4_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_4" id="si_sst_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:</legend>
															<input type="radio" name="si_sst_5" id="si_sst_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_5" id="si_sst_5_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_5" id="si_sst_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_5" id="si_sst_5_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_5" id="si_sst_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:</legend>
															<input type="radio" name="si_sst_6" id="si_sst_6_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_6" id="si_sst_6_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_6_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_6" id="si_sst_6_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_6_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_6" id="si_sst_6_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_6_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_6" id="si_sst_6_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_6_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con la asistencia a las  capacitaciones y charlas de seguridad y salud en el trabajo:</legend>
															<input type="radio" name="si_sst_7" id="si_sst_7_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_7" id="si_sst_7_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_7_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_7" id="si_sst_7_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_7_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_7" id="si_sst_7_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_7_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_7" id="si_sst_7_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_7_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:</legend>
															<input type="radio" name="si_sst_8" id="si_sst_8_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_8" id="si_sst_8_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_8_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_8" id="si_sst_8_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_8_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_8" id="si_sst_8_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_8_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_8" id="si_sst_8_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_8_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cuenta con una persona de seguridad y salud en el trabajo:</legend>
															<input type="radio" name="si_sst_9" id="si_sst_9_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_9" id="si_sst_9_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_9_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_9" id="si_sst_9_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_9_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_9" id="si_sst_9_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_9_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_9" id="si_sst_9_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_9_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:</legend>
															<input type="radio" name="si_sst_10" id="si_sst_10_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_10" id="si_sst_10_0" value="0"><label class="form-check-label font-weight-normal" for="si_sst_10_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_10" id="si_sst_10_50" value="0.5"><label class="form-check-label font-weight-normal" for="si_sst_10_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_10" id="si_sst_10_100" value="1"><label class="form-check-label font-weight-normal" for="si_sst_10_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="si_sst_10" id="si_sst_10_NA" value="NA"><label class="form-check-label font-weight-normal" for="si_sst_10_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group">
												<div class="form_eval form-group">
														<h3 id="form_obs">Observaciones</h3>
												</div>

												<div class="pregunta form-group">
														<div class="col-sm-12"><textarea id="si_Observaciones" name="si_Observaciones" class="form-control" ></textarea></div>
												</div>
										 </div>
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-1 col-sm-3 mr-auto ml-0">
		                  <input id="btn_guardar_cic_si" type="button" data-dismiss="modal" class="btn btn-primary" value="Guardar" aria-label="Guardar Calificación SI">
		                  <input id="btn_cancelar_cic_si" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar" aria-label="Cancelar Calificación SI">
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

		<!-- Se crea el Modal que solicita calificar a un contratista de Mano de Obra -->
		<div class="modal_cic_mdo modal fade aia-modal" id="modalcic_mdo" tabindex="-1" role="dialog" aria-labelledby="modal_cic_mdoLabel">
		  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalcic_mdoLabel">
		          <p class="modal-body-texto-cic_mdo" id="modal-body-texto-cic_mdo"></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="recargarTabla('listar')"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadroModal" class="cuadroModal col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal cic_mdo" id="formulario_cic_mdo" action="" method="POST">
									<div class="form-group">
										<h2 class="col-sm-offset-2 col-sm-12 text-center" id="actualizacion"></h2>
									</div>
		              <div class="form-group">
										<div class="parametro_cic form-group" id="mdo_cal">
												<div class="form_eval form-group">
														<h3 id="form_calidad">Calidad</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La calidad del producto suministrado e instalado:</legend>
															<input type="radio" name="mdo_cal_1" id="mdo_cal_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_1" id="mdo_cal_1_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_cal_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_1" id="mdo_cal_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_cal_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_1" id="mdo_cal_1_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_cal_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_1" id="mdo_cal_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_cal_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Las condiciones de almacenamiento de los materiales, insumos, maquinaria y equipos:</legend>
															<input type="radio" name="mdo_cal_2" id="mdo_cal_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_2" id="mdo_cal_2_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_cal_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_2" id="mdo_cal_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_cal_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_2" id="mdo_cal_2_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_cal_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_2" id="mdo_cal_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_cal_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Entrega de certificaciones / procedimientos asociadas a la actividad desarrollada:</legend>
															<input type="radio" name="mdo_cal_3" id="mdo_cal_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_3" id="mdo_cal_3_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_cal_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_3" id="mdo_cal_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_cal_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_3" id="mdo_cal_3_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_cal_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_cal_3" id="mdo_cal_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_cal_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="mdo_adm">
												<div class="form_eval form-group">
														<h3 id="form_adm">Administración del Contrato</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Los procedimientos administrativos y legales de AIA:</legend>
															<input type="radio" name="mdo_adm_1" id="mdo_adm_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_1" id="mdo_adm_1_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_adm_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_1" id="mdo_adm_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_adm_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_1" id="mdo_adm_1_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_adm_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_1" id="mdo_adm_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_adm_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La competencia y disponibilidad oportuna del personal en la obra:</legend>
															<input type="radio" name="mdo_adm_2" id="mdo_adm_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_2" id="mdo_adm_2_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_adm_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_2" id="mdo_adm_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_adm_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_2" id="mdo_adm_2_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_adm_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_2" id="mdo_adm_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_adm_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La disponibilidad oportuna y suficiente de los recursos: maquinaria, equipo y herramienta:</legend>
															<input type="radio" name="mdo_adm_3" id="mdo_adm_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_3" id="mdo_adm_3_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_adm_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_3" id="mdo_adm_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_adm_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_3" id="mdo_adm_3_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_adm_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_3" id="mdo_adm_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_adm_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">La atención a solicitudes, quejas y reclamos:</legend>
															<input type="radio" name="mdo_adm_4" id="mdo_adm_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_4" id="mdo_adm_4_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_adm_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_4" id="mdo_adm_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_adm_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_4" id="mdo_adm_4_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_adm_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_4" id="mdo_adm_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_adm_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Los requisitos legales de calidad, ambiental y seguridad y salud en el trabajo:</legend>
															<input type="radio" name="mdo_adm_5" id="mdo_adm_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_5" id="mdo_adm_5_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_adm_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_5" id="mdo_adm_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_adm_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_5" id="mdo_adm_5_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_adm_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_adm_5" id="mdo_adm_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_adm_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="mdo_gsa">
												<div class="form_eval form-group">
														<h3 id="form_GSA">Gestión Socio-Ambiental</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Mantener la rotulación, clasificación y almacenamiento de  los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:</legend>
															<input type="radio" name="mdo_gsa_1" id="mdo_gsa_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_1" id="mdo_gsa_1_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_1" id="mdo_gsa_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_1" id="mdo_gsa_1_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_1" id="mdo_gsa_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar la adecuada separación, almacenamiento y  disposición interna y externa (cuando aplique) de los residuos generados en obra:</legend>
															<input type="radio" name="mdo_gsa_2" id="mdo_gsa_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_2" id="mdo_gsa_2_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_2" id="mdo_gsa_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_2" id="mdo_gsa_2_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_2" id="mdo_gsa_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):</legend>
															<input type="radio" name="mdo_gsa_3" id="mdo_gsa_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_3" id="mdo_gsa_3_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_3" id="mdo_gsa_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_3" id="mdo_gsa_3_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_3" id="mdo_gsa_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:</legend>
															<input type="radio" name="mdo_gsa_4" id="mdo_gsa_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_4" id="mdo_gsa_4_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_4" id="mdo_gsa_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_4" id="mdo_gsa_4_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_4" id="mdo_gsa_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:</legend>
															<input type="radio" name="mdo_gsa_5" id="mdo_gsa_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_5" id="mdo_gsa_5_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_5" id="mdo_gsa_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_5" id="mdo_gsa_5_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_5" id="mdo_gsa_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):</legend>
															<input type="radio" name="mdo_gsa_6" id="mdo_gsa_6_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_6" id="mdo_gsa_6_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_6_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_6" id="mdo_gsa_6_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_6_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_6" id="mdo_gsa_6_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_6_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_6" id="mdo_gsa_6_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_6_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:</legend>
															<input type="radio" name="mdo_gsa_7" id="mdo_gsa_7_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_7" id="mdo_gsa_7_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_7_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_7" id="mdo_gsa_7_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_7_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_7" id="mdo_gsa_7_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_7_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_7" id="mdo_gsa_7_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_7_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Acatar las acciones recomendadas durante recorridos de obra:</legend>
															<input type="radio" name="mdo_gsa_8" id="mdo_gsa_8_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_8" id="mdo_gsa_8_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_gsa_8_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_8" id="mdo_gsa_8_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_gsa_8_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_8" id="mdo_gsa_8_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_gsa_8_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_gsa_8" id="mdo_gsa_8_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_gsa_8_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group" id="mdo_sst">
												<div class="form_eval form-group">
														<h3 id="form_sst">Seguridad y Salud en el Trabajo</h3>
												</div>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:</legend>
															<input type="radio" name="mdo_sst_1" id="mdo_sst_1_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_1" id="mdo_sst_1_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_1_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_1" id="mdo_sst_1_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_1_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_1" id="mdo_sst_1_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_1_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_1" id="mdo_sst_1_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_1_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:</legend>
															<input type="radio" name="mdo_sst_2" id="mdo_sst_2_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_2" id="mdo_sst_2_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_2_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_2" id="mdo_sst_2_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_2_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_2" id="mdo_sst_2_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_2_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_2" id="mdo_sst_2_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_2_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:</legend>
															<input type="radio" name="mdo_sst_3" id="mdo_sst_3_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_3" id="mdo_sst_3_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_3_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_3" id="mdo_sst_3_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_3_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_3" id="mdo_sst_3_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_3_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_3" id="mdo_sst_3_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_3_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:</legend>
															<input type="radio" name="mdo_sst_4" id="mdo_sst_4_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_4" id="mdo_sst_4_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_4_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_4" id="mdo_sst_4_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_4_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_4" id="mdo_sst_4_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_4_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_4" id="mdo_sst_4_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_4_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:</legend>
															<input type="radio" name="mdo_sst_5" id="mdo_sst_5_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_5" id="mdo_sst_5_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_5_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_5" id="mdo_sst_5_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_5_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_5" id="mdo_sst_5_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_5_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_5" id="mdo_sst_5_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_5_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:</legend>
															<input type="radio" name="mdo_sst_6" id="mdo_sst_6_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_6" id="mdo_sst_6_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_6_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_6" id="mdo_sst_6_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_6_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_6" id="mdo_sst_6_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_6_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_6" id="mdo_sst_6_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_6_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con la asistencia a las  capacitaciones y charlas de seguridad y salud en el trabajo:</legend>
															<input type="radio" name="mdo_sst_7" id="mdo_sst_7_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_7" id="mdo_sst_7_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_7_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_7" id="mdo_sst_7_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_7_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_7" id="mdo_sst_7_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_7_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_7" id="mdo_sst_7_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_7_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:</legend>
															<input type="radio" name="mdo_sst_8" id="mdo_sst_8_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_8" id="mdo_sst_8_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_8_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_8" id="mdo_sst_8_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_8_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_8" id="mdo_sst_8_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_8_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_8" id="mdo_sst_8_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_8_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cuenta con una persona de seguridad y salud en el trabajo:</legend>
															<input type="radio" name="mdo_sst_9" id="mdo_sst_9_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_9" id="mdo_sst_9_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_9_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_9" id="mdo_sst_9_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_9_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_9" id="mdo_sst_9_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_9_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_9" id="mdo_sst_9_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_9_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

												<fieldset class="pregunta form-group">

														<legend class="h6 mb-2" style="font-size:1rem;">Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:</legend>
															<input type="radio" name="mdo_sst_10" id="mdo_sst_10_NR" value="NR" checked class="d-none" aria-label="No responde">
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_10" id="mdo_sst_10_0" value="0"><label class="form-check-label font-weight-normal" for="mdo_sst_10_0" style="cursor: pointer;">0%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_10" id="mdo_sst_10_50" value="0.5"><label class="form-check-label font-weight-normal" for="mdo_sst_10_50" style="cursor: pointer;">50%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_10" id="mdo_sst_10_100" value="1"><label class="form-check-label font-weight-normal" for="mdo_sst_10_100" style="cursor: pointer;">100%</label></div>
															<div class="form-check mb-1 pl-4"><input class="form-check-input" type="radio" name="mdo_sst_10" id="mdo_sst_10_NA" value="NA"><label class="form-check-label font-weight-normal" for="mdo_sst_10_NA" style="cursor: pointer;">N/A</label></div>
												
</fieldset>

										</div>

										<div class="parametro_cic form-group">
												<div class="form_eval form-group">
														<h3 id="form_obs">Observaciones</h3>
												</div>

												<div class="pregunta form-group">
														<div class="col-sm-12"><textarea id="mdo_Observaciones" name="mdo_Observaciones" class="form-control" ></textarea></div>
												</div>
										 </div>
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-1 col-sm-3 mr-auto ml-0">
		                  <input id="btn_guardar_cic_mdo" type="button" data-dismiss="modal" class="btn btn-primary" value="Guardar" aria-label="Guardar Calificación MDO">
		                  <input id="btn_cancelar_cic_mdo" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar" aria-label="Cancelar Calificación MDO">
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
			//ocultos();
			listar();
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
			var semana = document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value;
			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;


			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;


			// Initial Height Calculation
			var alturatabla = calcDataTableHeight();
			document.getElementById('cuadroTabla').style.height = "auto";


			var table = $("#dt_cliente").DataTable({
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'ps-actions-row'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering":false,
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
					"url":"/api/cic/list",
					"data": { "db": db, "semana": semana }
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [
						{
							'targets': [0],
							'width':'4%',
						},
						{
							'targets': [2],
							'width':'6%',
						},
						{
							'targets': [3],
							'width':'5%',
						},
						{
							'targets': [4],
							'width':'12%',
						},
						{
							'targets': [5],
							'width':'14%',
						},
						{
							'targets': [6],
							'width':'10%',
						},
						{
							'targets': [7],
							'width':'6%',
						},
						{
							'targets': [8],
							'width':'6%',
						},
						{
							'targets': [9],
							'width':'5%',
						},
						{
							'targets': [10],
							'width':'5%',
						},
						{
							'targets': [11],
							'width':'5%',
						},
						{
							'targets': [12],
							'width':'5%',
						},
						{
							'targets': [13],
							'width':'7%',
						},
						{
							'targets': [14],
							'width':'10%',
						},


						 // {
							// 	'targets': [2,3,4,5,6,7,8,9,10,11,12],
							// 	'max-width': "9%",
						 // },

						{
						'targets': [4,5,6],
						'render': function ( data, type, full, meta ) {
										return data;
								}
						},

						{
								'targets': [7,8,9,10,11,12,13],
								'render': function ( data, type, row, meta ) {
										var semanasEnProyecto= row['semanasEnProyecto'];
										var Calidad= row['Calidad'];
										var GSA= row['GSA'];
										var SST= row['SST'];
										var ADM= row['ADM'];
										if(data=='NA'){
												data="No Aplica";
												return data;
										}else if(data=='NR'){
												data="Falta Calificar";
												if((semanasEnProyecto % 8) == 0 && (Calidad == 'NR' || GSA == 'NR' || SST == 'NR' || ADM == 'NR')){
													return "<p class='cic-text-dark'>"+data+"</p>";
												}else{
													return "<p class='text-danger'>"+data+"</p>";
												}
										}else{
												data=data*100;
												data=data.toFixed(0);
												//console.log(data);
												carita="";
												if (data >= 95){
														carita = "<i class='fas fa-grin-stars fa-2x icon-status-ok'></i>";
												} else if(data < 95 && data >= 70){
														carita = "<i class='fas fa-meh fa-2x icon-status-warning'></i>";
												} else if(data < 70){
														carita = "<i class='fas fa-sad-cry fa-2x icon-status-critical'></i>";
												}
												return data + "% <small> "+carita+"</small>";
										}
								},
						},
					],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'><i class='fa fa-edit'></i></button>"},
						{"data":"Id", "visible":false},
						{"data":"semanasEnProyecto"},
						{"data":"Semana"},
						{"data":"subcontratista"},
						{"data":"alcance"},
						{"data":"tipo_proveedor",},
						{"data":"PAC"},
						{"data":"P_Completado"},
						{"data":"Calidad"},
						{"data":"GSA"},
						{"data":"SST"},
						{"data":"ADM"},
						{"data":"Cal_Integral"},
						{"data":"Observaciones"},
						{"data":"mdo_cal_1","visible":false},
						{"data":"mdo_cal_2","visible":false},
						{"data":"mdo_cal_3","visible":false},
						{"data":"mdo_adm_1","visible":false},
						{"data":"mdo_adm_2","visible":false},
						{"data":"mdo_adm_3","visible":false},
						{"data":"mdo_adm_4","visible":false},
						{"data":"mdo_adm_5","visible":false},
						{"data":"mdo_gsa_1","visible":false},
						{"data":"mdo_gsa_2","visible":false},
						{"data":"mdo_gsa_3","visible":false},
						{"data":"mdo_gsa_4","visible":false},
						{"data":"mdo_gsa_5","visible":false},
						{"data":"mdo_gsa_6","visible":false},
						{"data":"mdo_gsa_7","visible":false},
						{"data":"mdo_gsa_8","visible":false},
						{"data":"mdo_sst_1","visible":false},
						{"data":"mdo_sst_2","visible":false},
						{"data":"mdo_sst_3","visible":false},
						{"data":"mdo_sst_4","visible":false},
						{"data":"mdo_sst_5","visible":false},
						{"data":"mdo_sst_6","visible":false},
						{"data":"mdo_sst_7","visible":false},
						{"data":"mdo_sst_8","visible":false},
						{"data":"mdo_sst_9","visible":false},
						{"data":"mdo_sst_10","visible":false},
						{"data":"si_cal_1","visible":false},
						{"data":"si_cal_2","visible":false},
						{"data":"si_cal_3","visible":false},
						{"data":"si_adm_1","visible":false},
						{"data":"si_adm_2","visible":false},
						{"data":"si_adm_3","visible":false},
						{"data":"si_adm_4","visible":false},
						{"data":"si_adm_5","visible":false},
						{"data":"si_adm_6","visible":false},
						{"data":"si_gsa_1","visible":false},
						{"data":"si_gsa_2","visible":false},
						{"data":"si_gsa_3","visible":false},
						{"data":"si_gsa_4","visible":false},
						{"data":"si_gsa_5","visible":false},
						{"data":"si_gsa_6","visible":false},
						{"data":"si_gsa_7","visible":false},
						{"data":"si_gsa_8","visible":false},
						{"data":"si_gsa_9","visible":false},
						{"data":"si_gsa_10","visible":false},
						{"data":"si_gsa_11","visible":false},
						{"data":"si_gsa_12","visible":false},
						{"data":"si_gsa_13","visible":false},
						{"data":"si_gsa_14","visible":false},
						{"data":"si_sst_1","visible":false},
						{"data":"si_sst_2","visible":false},
						{"data":"si_sst_3","visible":false},
						{"data":"si_sst_4","visible":false},
						{"data":"si_sst_5","visible":false},
						{"data":"si_sst_6","visible":false},
						{"data":"si_sst_7","visible":false},
						{"data":"si_sst_8","visible":false},
						{"data":"si_sst_9","visible":false},
						{"data":"si_sst_10","visible":false}
				],

			"createdRow": function( row, data, index ) {
				// Standardized Coloring Logic for CIC (Missing Evaluations)
				if((data.semanasEnProyecto % 8) == 0 && (data.Calidad == 'NR' || data.GSA == 'NR' || data.SST == 'NR' || data.ADM == 'NR')){
					$(row).addClass('row-delayed'); // Use standard "Late/Warning" style
				}
			},

			// "rowCallback": function( row, data, index ) {
			// 	if(data.Atrasada==1 && data.Critica==1){
			// 			$('td', row).css('background-color', '#7c1c51');
			// 			$('td', row).css('color', '#ffffff');
			// 	} else if(data.Atrasada==1 && data.Critica==0){
			// 			$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
			// 	} else if(data.Critica==1){
			// 		$('td', row).css('background-color', 'rgba(255,150,64,1)');
			// 	} else if(data.Critica==0){
			// 			$('td', row).css('background-color', 'rgba(255,192,51,0.5)');
			// 	}
			// },

				"language": idioma_espanol
			});

			// Dynamic Resize Listener
			$(window).off('resize.dtCIC orientationchange.dtCIC aia:viewport-scale-change.dtCIC').on('resize.dtCIC orientationchange.dtCIC aia:viewport-scale-change.dtCIC', function() {
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

			$("div.ps-actions-row").html('<div class="grupo_botones1 ps-toolbar-actions" role="group" aria-label="Grupo de botones leyenda"><button id="btn_tutorialCIC" type="button" class="btn-pdc-modern ps-btn-gap" title="Video tutorial de la Calificación de Subcontratistas" onclick="window.open(\'https://youtu.be/OJrd5qlgFm4\', \'_blank\')" aria-label="Ver Tutorial Calificación de Proveedores">Tutorial <i class="fas fa-list-ol fa-lg" aria-hidden="true"></i></button></div><div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap"><div class="ps-dropdown-nav" aria-label="Navegacion Programacion Semanal"><button type="button" class="btn-pdc-modern btn-dropdown-trigger" id="dropdownTriggerSecciones"><i class="fas fa-th-list"></i> <span>Ver Secciones</span> <i class="fas fa-chevron-down ml-1"></i></button><div class="ps-dropdown-content" role="menu"><button id="btn_Actividades_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=programacion_semanal&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-table"></i> Actividades</button><button id="btn_CNP_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CNP&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-calendar-times"></i> Causas No Programacion</button><button id="btn_CNC_nav" type="button" class="ps-dropdown-item" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CNC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-exclamation-triangle"></i> Causas No Cumplimiento</button><button id="btn_Cal_Proveedores_nav" type="button" class="ps-dropdown-item is-active" onclick="window.location.href=\'/legacy/cambiar_pagina.php?seccion=CIC&semana=\'+(document.getElementById(\'semana\') ? document.getElementById(\'semana\').value : document.getElementById(\'semana_PHP\').value)" role="menuitem"><i class="fas fa-clipboard-check"></i> Calificacion Proveedores</button></div></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div class="ps-toolbar-filter"><button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern ps-filter-clear" aria-label="Limpiar búsqueda"><i class="fas fa-times-circle" aria-hidden="true"></i> Limpiar</button></div>');
			maestroPermisos(document.getElementById('permiso_canonico').value);
			obtener_data_editar("#dt_cliente tbody", table);
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value;
		  var permiso = document.getElementById('permiso_canonico').value;

			var only_once = true;

			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

		  $(tbody).one("click", "td", function() {
		    if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					//console.log(data.tipo_proveedor);
					if(data.tipo_proveedor=="Mano de Obra"){
							var Id=$("#Id").val(data.Id);
							var ultimaSemanaContratista=$("#ultimaSemanaContratista").val(data.Semana);
							var opcion = $("#cic_mdo, #opcion").val("modificar_mdo");
							$("#cic_mdo, #semana").val(semana).change();
							$("input[name=mdo_cal_1][value='"+data.mdo_cal_1+"']").prop("checked",true);
							$("input[name=mdo_cal_2][value='"+data.mdo_cal_2+"']").prop("checked",true);
							$("input[name=mdo_cal_3][value='"+data.mdo_cal_3+"']").prop("checked",true);
							$("input[name=mdo_adm_1][value='"+data.mdo_adm_1+"']").prop("checked",true);
							$("input[name=mdo_adm_2][value='"+data.mdo_adm_2+"']").prop("checked",true);
							$("input[name=mdo_adm_3][value='"+data.mdo_adm_3+"']").prop("checked",true);
							$("input[name=mdo_adm_4][value='"+data.mdo_adm_4+"']").prop("checked",true);
							$("input[name=mdo_adm_5][value='"+data.mdo_adm_5+"']").prop("checked",true);
							$("input[name=mdo_gsa_1][value='"+data.mdo_gsa_1+"']").prop("checked",true);
							$("input[name=mdo_gsa_2][value='"+data.mdo_gsa_2+"']").prop("checked",true);
							$("input[name=mdo_gsa_3][value='"+data.mdo_gsa_3+"']").prop("checked",true);
							$("input[name=mdo_gsa_4][value='"+data.mdo_gsa_4+"']").prop("checked",true);
							$("input[name=mdo_gsa_5][value='"+data.mdo_gsa_5+"']").prop("checked",true);
							$("input[name=mdo_gsa_6][value='"+data.mdo_gsa_6+"']").prop("checked",true);
							$("input[name=mdo_gsa_7][value='"+data.mdo_gsa_7+"']").prop("checked",true);
							$("input[name=mdo_gsa_8][value='"+data.mdo_gsa_8+"']").prop("checked",true);
							$("input[name=mdo_sst_1][value='"+data.mdo_sst_1+"']").prop("checked",true);
							$("input[name=mdo_sst_2][value='"+data.mdo_sst_2+"']").prop("checked",true);
							$("input[name=mdo_sst_3][value='"+data.mdo_sst_3+"']").prop("checked",true);
							$("input[name=mdo_sst_4][value='"+data.mdo_sst_4+"']").prop("checked",true);
							$("input[name=mdo_sst_5][value='"+data.mdo_sst_5+"']").prop("checked",true);
							$("input[name=mdo_sst_6][value='"+data.mdo_sst_6+"']").prop("checked",true);
							$("input[name=mdo_sst_7][value='"+data.mdo_sst_7+"']").prop("checked",true);
							$("input[name=mdo_sst_8][value='"+data.mdo_sst_8+"']").prop("checked",true);
							$("input[name=mdo_sst_9][value='"+data.mdo_sst_9+"']").prop("checked",true);
							$("input[name=mdo_sst_10][value='"+data.mdo_sst_10+"']").prop("checked",true);
							$("#mdo_Observaciones").val(data.Observaciones);

					} else if (data.tipo_proveedor=="Suministro e Instalación"){
							var Id=$("#Id").val(data.Id);
							var ultimaSemanaContratista=$("#ultimaSemanaContratista").val(data.Semana);
							var opcion = $("#cic_si, #opcion").val("modificar_si");
							$("#cic_si, #semana").val(semana).change();
							$("input[name=si_cal_1][value='"+data.si_cal_1+"']").prop("checked",true);
							$("input[name=si_cal_2][value='"+data.si_cal_2+"']").prop("checked",true);
							$("input[name=si_cal_3][value='"+data.si_cal_3+"']").prop("checked",true);
							$("input[name=si_adm_1][value='"+data.si_adm_1+"']").prop("checked",true);
							$("input[name=si_adm_2][value='"+data.si_adm_2+"']").prop("checked",true);
							$("input[name=si_adm_3][value='"+data.si_adm_3+"']").prop("checked",true);
							$("input[name=si_adm_4][value='"+data.si_adm_4+"']").prop("checked",true);
							$("input[name=si_adm_5][value='"+data.si_adm_5+"']").prop("checked",true);
							$("input[name=si_adm_6][value='"+data.si_adm_6+"']").prop("checked",true);
							$("input[name=si_gsa_1][value='"+data.si_gsa_1+"']").prop("checked",true);
							$("input[name=si_gsa_2][value='"+data.si_gsa_2+"']").prop("checked",true);
							$("input[name=si_gsa_3][value='"+data.si_gsa_3+"']").prop("checked",true);
							$("input[name=si_gsa_4][value='"+data.si_gsa_4+"']").prop("checked",true);
							$("input[name=si_gsa_5][value='"+data.si_gsa_5+"']").prop("checked",true);
							$("input[name=si_gsa_6][value='"+data.si_gsa_6+"']").prop("checked",true);
							$("input[name=si_gsa_7][value='"+data.si_gsa_7+"']").prop("checked",true);
							$("input[name=si_gsa_8][value='"+data.si_gsa_8+"']").prop("checked",true);
							$("input[name=si_gsa_9][value='"+data.si_gsa_9+"']").prop("checked",true);
							$("input[name=si_gsa_10][value='"+data.si_gsa_10+"']").prop("checked",true);
							$("input[name=si_gsa_11][value='"+data.si_gsa_11+"']").prop("checked",true);
							$("input[name=si_gsa_12][value='"+data.si_gsa_12+"']").prop("checked",true);
							$("input[name=si_gsa_13][value='"+data.si_gsa_13+"']").prop("checked",true);
							$("input[name=si_gsa_14][value='"+data.si_gsa_14+"']").prop("checked",true);
							$("input[name=si_sst_1][value='"+data.si_sst_1+"']").prop("checked",true);
							$("input[name=si_sst_2][value='"+data.si_sst_2+"']").prop("checked",true);
							$("input[name=si_sst_3][value='"+data.si_sst_3+"']").prop("checked",true);
							$("input[name=si_sst_4][value='"+data.si_sst_4+"']").prop("checked",true);
							$("input[name=si_sst_5][value='"+data.si_sst_5+"']").prop("checked",true);
							$("input[name=si_sst_6][value='"+data.si_sst_6+"']").prop("checked",true);
							$("input[name=si_sst_7][value='"+data.si_sst_7+"']").prop("checked",true);
							$("input[name=si_sst_8][value='"+data.si_sst_8+"']").prop("checked",true);
							$("input[name=si_sst_9][value='"+data.si_sst_9+"']").prop("checked",true);
							$("input[name=si_sst_10][value='"+data.si_sst_10+"']").prop("checked",true);
							$("#si_Observaciones").val(data.Observaciones);
					}

					if(data.tipo_proveedor=="Mano de Obra"){
							$("#modalcic_mdo").modal("show");
							document.getElementById('modal-body-texto-cic_mdo').innerHTML = "<div>Formulario de Calificación de proveedores de Mano de Obra.</div><div class='mt-2'>Sub-Contratista: <strong>"+data.subcontratista+"</strong></div>";
							cancelarEdicionMDO();
							guardarMDO();
					} else if (data.tipo_proveedor=="Suministro e Instalación"){
							$("#modalcic_si").modal("show");
							document.getElementById('modal-body-texto-cic_si').innerHTML = "<div>Formulario de Calificación de proveedores de Suministro e Instalación.</div><div class='mt-2'>Sub-Contratista: <strong>"+data.subcontratista+"</strong></div>";
							cancelarEdicionSI();
							guardarSI();
					};
				}
		  });
		}

		// var bloquear_unidad = function() {
		// 	var db = document.getElementById('baseDatos').value
		//   if ($("#select_codigo_actividad").val() == '') {
		//     $("#select_unidad").attr('disabled', false);
		//   } else {
		//     $("#select_unidad").attr('disabled', true);
		//     opcion = "cargar_unidad";
		//     codigo_actividad = $("#select_codigo_actividad").val();
		//     $.ajax({
		//       method: "POST",
		//       url: "../programa_general/guardar_programa_general.php?db="+db,
		//       contenttype: "charset=utf-8",
		//       data: {
		//         "opcion": opcion,
		//         "codigo_actividad": codigo_actividad
		//       }
		//     }).done(function(info) {
		//       var json_info = JSON.parse(info);
		//       unidad = json_info[0];
		//       $("#select_unidad").val(unidad).change();
		//     });
		//   }
		// }

		var cancelarEdicionSI = function() {
		  $("#btn_cancelar_cic_si").one("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}

		var cancelarEdicionMDO = function() {
		  $("#btn_cancelar_cic_mdo").one("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardarSI = function() {
			$("#btn_guardar_cic_si").one("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var opcion = document.getElementById('opcion').value;
				var Id = document.getElementById('Id').value;
				var semana = document.getElementById('ultimaSemanaContratista') ? document.getElementById('ultimaSemanaContratista').value : (document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value);
				var frm = $("#formulario_cic_si").serialize() + "&opcion="+opcion + "&Id="+Id + "&semana="+semana;
				$.ajax({
					method: "POST",
					url: "/api/cic/save",
					contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
					$("#modalcic_si #cuadroModal").scrollTop(0);
					recargarTabla('');
				});
			});
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardarMDO = function() {
			$("#btn_guardar_cic_mdo").one("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var opcion = document.getElementById('opcion').value;
				var Id = document.getElementById('Id').value;
				var semana = document.getElementById('ultimaSemanaContratista') ? document.getElementById('ultimaSemanaContratista').value : (document.getElementById('semana') ? document.getElementById('semana').value : document.getElementById('semana_PHP').value);
				var frm = $("#formulario_cic_mdo").serialize() + "&opcion="+opcion + "&Id="+Id + "&semana="+semana;
				$.ajax({
					method: "POST",
					url: "/api/cic/save",
					contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
					$("#modalcic_mdo #cuadroModal").scrollTop(0);
					recargarTabla('');
				});
			});
		}

		/*limpia los valores del formulario de registro*/
		var limpiar_datos = function() {
			$("#opcion").val("registrar");
			$("#Id").val("");
			$("#Ejecutado").val("").focus();
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
				//actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
				//listar();
		  } else {
		    table.ajax.reload();
				// actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "noListar");
		    obtener_data_editar("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
		}

		function wait(ms){
			 var start = new Date().getTime();
			 var end = start;
			 while(end < start + ms) {
				 end = new Date().getTime();
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

        /* Lógica para Dropdown de navegación */
        $(document).off('click.psDropdown').on('click.psDropdown', '.btn-dropdown-trigger', function(e) {
          e.stopPropagation();
          var $nav = $(this).closest('.ps-dropdown-nav');
          $('.ps-dropdown-nav').not($nav).removeClass('is-open');
          $nav.toggleClass('is-open');
        });

        $(document).off('click.psDropdownClose').on('click.psDropdownClose', function() {
          $('.ps-dropdown-nav').removeClass('is-open');
        });

	</script>
</body>
</html>
