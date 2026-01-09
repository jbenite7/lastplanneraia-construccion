<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="programacion_semanal">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="">

		<input type="hidden" id="opcion_Eliminar_Duplicar" name="opcion_Eliminar_Duplicar" value="">
		<input type="hidden" id="Actividad_Eliminar_Duplicar" name="Actividad_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="Responsable_AIA_Eliminar_Duplicar" name="Responsable_AIA_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="Empresa_Eliminar_Duplicar" name="Empresa_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="Categoria_CNP_base_Eliminar_Duplicar" name="Categoria_CNP_base_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="CNP_base_Eliminar_Duplicar" name="CNP_base_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="Observaciones_CNP_base_Eliminar_Duplicar" name="Observaciones_CNP_base_Eliminar_Duplicar" value="" readonly>
		<input type="hidden" id="Activa_Eliminar_Duplicar" name="Activa_Eliminar_Duplicar" value="" readonly>
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-8 col-md-8 col-lg-8 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
		<div class="col-sm-4 col-md-4 col-lg-4 mr-0 ml-auto" id="textoFechaCierreCompromisos" style="text-align:right">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<div class="row formularioRegistro" style:"visibility: hidden">
		<div id="formulario_nuevo" class="formulario_nuevo col-sm-8 col-md-8 col-lg-8">
			<form class="form_nueva_actividad form form-horizontal" action="" method="POST">
			  <div class="form-group">
			    <h3 class="col-sm-offset-2 col-sm-8 text-center">Formulario de Registro de Nueva Actividad</h3>
			  </div>
			  <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
			  <!--                <div style="width:40%; float:left;">-->
			  <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="idNuevo" class="col-sm-8 control-label">Id *</label>
			    <div class="col-sm-8"><select id="idNuevo" name="idNuevo" class="form-control" style="width:100%;">
			        <option value=""></option> <?php
			                            require("../conexion.php");
																	$db = $_SESSION['db'];
																	$semana = $_SESSION['semana'];
			                            $query="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Semanas_Inicio<=12 AND Semanas_Inicio>=1 AND Ejecutado=0";
			                            $resultado= mysqli_query($conexion, $query);
			                            while ($valores = mysqli_fetch_assoc($resultado)){
																		$Actividad=$valores["Actividad"];
																		$Actividad=str_replace('"','\"',$Actividad);
																		$Actividad=str_replace("'","\'",$Actividad);
		                                echo '<option value="'.$valores["Id"].'">('.$valores["Id"].') - '. str_replace('<small>', '', str_replace('<b>', '', $Actividad)) .'</option>';
			                            };
			                            mysqli_close($conexion);
			                        ?>
			      </select>
			    </div>
			  </div>
			  <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Actividad" class="col-sm-8 control-label">Actividad *</label>
			    <div class="col-sm-8"><input id="Actividad" name="Actividad" class="form-control" value="" type="">
			    </div>
			  </div>
			  <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Descripcion" class="col-sm-8 control-label">Descripción</label>
			    <div class="col-sm-8"><input id="Descripcion" name="Descripcion" class="form-control" value="" type="">
			    </div>
			  </div>
			  <!-- <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Ubicacion" class="col-sm-8 control-label">Ubicación</label>
			    <div class="col-sm-8"><input id="Ubicacion" name="Ubicacion" class="form-control" value="" type="">
			    </div>
			  </div> -->
				<input id="Ubicacion" name="Ubicacion" class="form-control" value="" type="hidden">

			  <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Sub_Contratista" class="col-sm-8 control-label">Sub-Contratista *</label>
			    <div class="col-sm-8"><select id="Sub_Contratista" name="Sub_Contratista" class="form-control">
			        <option value=""></option>
							<option value="AIA (MO Directa)">AIA (MO Directa)</option><?php
			                            require("../conexion.php");
																	$db = $_SESSION['db'];
			                            $query="SELECT * FROM $db"."_subcontratistas WHERE Activo=1";
			                            $resultado= mysqli_query($conexion, $query);
			                            while ($valores = mysqli_fetch_array($resultado)){
			                                echo '<option value="'.$valores["subcontratista"].'">'.$valores["subcontratista"].'</option>';
			                            };
			                            mysqli_close($conexion);
			                        ?>
			      </select>
			    </div>
			  </div>
			  <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Responsable_AIA" class="col-sm-8 control-label">Profesional AIA *</label>
			    <div class="col-sm-8"><select id="Responsable_AIA" name="Responsable_AIA" class="form-control">
			        <option value=""></option> <?php
			                            require("../conexion.php");
																	$db = $_SESSION['db'];
			                            $query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
			                            $resultado= mysqli_query($conexion, $query);
			                            while ($valores = mysqli_fetch_array($resultado)){
			                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
			                            };
			                            mysqli_close($conexion);
			                        ?>
			      </select>
			    </div>
			  </div>
			  <!-- <div class="form-group" style="width:600px; ; max-width:49%; display:inline-block">
			    <label for="Empresa" class="col-sm-8 control-label">Empresa</label>
			    <div class="col-sm-8"><input id="Empresa" name="Empresa" class="form-control" value="" type="">
			    </div>
			  </div> -->
				<input id="Empresa" name="Empresa" class="form-control" value="" type="hidden">

			  <!-- <div class="form-group" style="width:600px; max-width:49%; display:inline-block">
			    <label for="Unidad" class="col-sm-8 control-label">Unidad de Medida *</label>
			    <div class="col-sm-4"><select id="Unidad" name="Unidad" class="form-control">
			        <option value=""></option>
			        <option value="ml">ml</option>
			        <option value="m2">m2</option>
			        <option value="m3">m3</option>
			        <option value="un">Un</option>
			        <option value="gl">Gl</option>
			        <option value="kg">kg</option>
			        <option value="%">%</option>
			        <option value="Nivel">Nivel</option>
			      </select>
			    </div>
			  </div> -->
			<div class="form-group" style="width:600px; max-width:49%; display:inline-block">
			    <label for="Unidad" class="col-sm-8 control-label">Unidad de Medida *</label>
			    <div class="col-sm-4"><input id="Unidad" name="Unidad" class="form-control" value="" type="text">
			    </div>
			  </div>
			  <div class="form-group" style="width:600px; max-width:49%; display:inline-block">
			    <label for="Compromiso" class="col-sm-8 control-label">Cantidad Comprometida *</label>
			    <div class="col-sm-4"><input id="Compromiso" name="Compromiso" class="form-control" value="" type="text">
			    </div>
			  </div>
			  <!-- <div class="form-group" style="width:600px; max-width:49%; display:inline-block">
			    <label for="Real" class="col-sm-8 control-label">Cantidad Real Ejecutada</label>
			    <div class="col-sm-4"><input id="Real" name="Real" class="form-control" value="" type="text">
			    </div>
			  </div> -->
				<input id="Real" name="Real" class="form-control" value="" type="hidden">
			  <br>
			  <!--Se crean los botones Guardar y Listar-->
			  <div class="form-group">
			    <div class="col-sm-offset-2 col-sm-8">
			      <input id="btn_guardar_nueva_actividad" type="button" class="btn btn-primary" value="Guardar">
			      <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar">
			    </div>
			  </div>
			</form>
			<!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class="col-sm-offset-2 col-sm-8">
			  <p class="mensaje"></p>
			</div>
		</div>
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla">
		<div id="cuadroTabla" class="col-sm-12 col-md-12 col-lg-12">
			<table id="dt_cliente" class="dt_programacionSemanal table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Consecutivo</th>
						<th>Id</th>
						<th>Actividad</th>
						<th>Descripción</th>
						<th>Ubicación</th>
						<th>Liberada?</th>
						<th>Contratista</th>
						<th>Profesional AIA</th>
						<th>Empresa</th>
						<th>Ejecutado Real Actual</th>
						<th>Ejecutado Teorico al Terminar Semana</th>
						<th>¿Medir productividad?</th>
						<th>Un</th>
						<th>Cant. PPTO</th>
						<th>Cant. Sugerida</th>
						<th>Cant. Compromiso</th>
						<th>Cant. Real</th>
						<th>PAC</th>
						<th>% Compl.</th>
						<th>Categoria_CNC</th>
						<th>CNC</th>
						<th>Observaciones_CNC</th>
						<th>Rendimientos</th>
						<th>Codigo Actividad</th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th><input type="text" id="buscadorActividad" placeholder="Buscar" style="width:80%"></th>
						<th></th>
						<th></th>
						<th>
							<select style="width:80%" id="buscadorLiberada">
								<option value="">Todas</option>
								<option value="Sí">Sí</option>
								<option value="No">No</option>
							</select>
						</th>
						<th>
							<select style="width:80%" id="buscadorSubcontratista">
		                      	<option value="">Todos</option> 
		                      	<option value="AIA (MO Directa)">AIA (MO Directa)</option> 
								<?php
								require("../conexion.php");
								$db = $_SESSION['db'];
								$query="SELECT * FROM $db"."_subcontratistas WHERE activo=1";
								$resultado= mysqli_query($conexion, $query);
								while ($valores = mysqli_fetch_array($resultado)){
									echo '<option value="'.$valores["subcontratista"].'">'.$valores["subcontratista"].'</option>';
								};
								mysqli_close($conexion);
								?>
		                    </select>
						</th>
						<th>
							<select style="width:80%" id="buscadorResponsableAIA">
		                      	<option value="">Todos</option> 
								<?php
								require("../conexion.php");
								$db = $_SESSION['db'];
								$query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
								$resultado= mysqli_query($conexion, $query);
								while ($valores = mysqli_fetch_array($resultado)){
									echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
								};
								mysqli_close($conexion);
								?>
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
						<th>
							<select style="width:100%" id="buscadorPAC">
								<option value="">Todas</option>
								<option value="100%">100%</option>
								<option value="0%">0%</option>
							</select>
						</th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
		<!-- Se crea el Modal que solicita la confirmación de eliminar una actividad o no -->
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
		        <button type="button" id="continuar_modal_CNP" class="btn btn-primary" data-dismiss="modal" data-toggle="modal" onclick='asignar_CNP()'>Aceptar</button>
		        <!--data-target="#modal_CNP"-->
		        <button type="button" id="btn_cancelar_eliminar" class="btn btn-default" data-dismiss="modal">Cancelar</button>
		      </div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que solicita la confirmación de duplicar una actividad o no -->
		<div class="modal fade" id="modalDuplicar" tabindex="-1" role="dialog" aria-labelledby="modalDuplicarLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modalDuplicarLabel">Duplicar Actividad</h4>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <p class="modal-body-texto-duplicar" id="modal-body-texto-duplicar"></p>
		      </div>
		      <div class="modal-footer">
		        <button type="button" id="duplicar-usuario" class="btn btn-primary" data-dismiss="modal">Aceptar</button>
		        <button type="button" id="btn_cancelar_duplicar" class="btn btn-default" data-dismiss="modal">Cancelar</button>
		      </div>
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que solicita asignar la causa de no programación a las actividades que no se van a ejecutar (que se eliminan) -->
		<div class="modal_CNP modal fade" id="modal_CNP" tabindex="-1" role="dialog" aria-labelledby="modal_CNPLabel">
		  <div class="modal-dialog modal-dialog-scrollable" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h6 class="modal-title" id="modalEliminarLabel">
		          <p class="modal-body-texto-CNP" id="modal-body-texto-CNP"></p>
		        </h6>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
		              <input type="hidden" id="Id" name="Id" value="0">
		              <input type="hidden" id="opcion" name="opcion" value="CNP">
		              <div class="form-group" style="width:100%;">
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="Responsable_AIA_CNP" class="col-sm-12 control-label">Profesional de AIA Encargado de la Actividad</label>
		                  <div class="col-sm-8"><select id="Responsable_AIA_CNP" name="Responsable_AIA_CNP" class="form-control">
		                      <option value=""></option> <?php
                                                      require("../conexion.php");
																											$db = $_SESSION['db'];
                                                      $query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
                                                      $resultado= mysqli_query($conexion, $query);
                                                      while ($valores = mysqli_fetch_array($resultado)){
                                                          echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                                                      };
                                                      mysqli_close($conexion);
                                                  		?>
		                    </select>
		                  </div>
		                </div>
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="Empresa_CNP" class="col-sm-12 control-label">Empresa Encargada de la Ejecución</label>
		                  <div class="col-sm-8"><input id="Empresa_CNP" name="Empresa_CNP" type="text" class="form-control" value=""></div>
		                </div>
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="Categoria_CNP" class="col-sm-8 control-label">Categoría</label>
		                  <div class="col-sm-8"><select id="Categoria_CNP" name="Categoria_CNP" class="form-control">
		                      <option value=""></option>
		                      <option value="Programación">Programación</option>
		                      <option value="Mano de Obra">Mano de Obra</option>
		                      <option value="Materiales">Materiales</option>
		                      <option value="Equipos">Equipos</option>
		                      <option value="Diseños">Diseños</option>
		                      <option value="Administrativas">Administrativas</option>
		                      <option value="Causas Exógenas">Causas Exógenas</option>
		                    </select>
		                  </div>
		                </div>
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="CNP" class="col-sm-8 control-label">Causa de No Programación</label>
		                  <div class="col-sm-12"><select id="CNP" name="CNP" class="form-control">
		                      <option value=''></option>
		                    </select>
		                  </div>
		                </div>
		                <div class="form-group" style="width:100%;">
		                  <label for="Observaciones_CNP" class="col-sm-12 control-label">Observaciones</label>
		                  <div class="col-sm-12"><textarea id="Observaciones_CNP" name="Observaciones_CNP" class="form-control"></textarea></div>
		                </div>
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="eliminar-actividad" type="button" class="btn btn-primary" data-dismiss="modal" value="Guardar">
		                  <input id="btn_cancelar_CNP" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar">
		                  <!--onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"-->
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
		      <!--<div class="modal-footer">
									<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
									<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
								</div>-->
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que solicita asignar la causa de no cumplimiento a las actividades que no lograron ejecutar lo que inicialmente se habia comprometido -->
		<div class="modal_CNC modal fade" id="modal_CNC" tabindex="-1" role="dialog" aria-labelledby="modal_CNCLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalEliminarLabel">
		          <p class="modal-body-texto-CNC" id="modal-body-texto-CNC"></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <input type="hidden" id="Id" name="Id" value="0">
		              <input type="hidden" id="opcion" name="opcion" value="CNP">
		              <div class="form-group" style="width:100%;">
		                <!--<div class="form-group" style="width:600px; max-width:100%">
		                                                        <label for="Responsable_AIA_CNC" class="col-sm-12 control-label">Profesional de AIA Encargado de la Actividad</label>
		                                                        <div class="col-sm-8"><select id="Responsable_AIA_CNC" name="Responsable_AIA_CNC" class="form-control" >
		                                                        <option value=""></option>
		                                                        <?php
                                                            require("../conexion.php");
																														$db = $_SESSION['db'];
                                                            $query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
                                                            $resultado= mysqli_query($conexion, $query);
                                                            while ($valores = mysqli_fetch_array($resultado)){
                                                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                                                            };
                                                            mysqli_close($conexion);
		                                                        ?>
		                                                        </select>
		                                                        </div>
		                                                    </div>-->
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="Categoria_CNC" class="col-sm-8 control-label">Categoría</label>
		                  <div class="col-sm-8"><select id="Categoria_CNC" name="Categoria_CNC" class="form-control">
		                      <option value=""></option>
		                      <option value="Programación">Programación</option>
		                      <option value="Mano de Obra">Mano de Obra</option>
		                      <option value="Materiales">Materiales</option>
		                      <option value="Equipos">Equipos</option>
		                      <option value="Diseños">Diseños</option>
		                      <option value="Administrativas">Administrativas</option>
		                      <option value="Causas Exógenas">Causas Exógenas</option>
		                    </select>
		                  </div>
		                </div>
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <label for="CNC" class="col-sm-8 control-label">Causa de No Cumplimiento</label>
		                  <div class="col-sm-12"><select id="CNC" name="CNC" class="form-control">
		                      <option value=''></option>
		                    </select>
		                  </div>
		                </div>
		                <div class="form-group" style="width:600px; max-width:100%">
		                  <p class="error-CNC" id="error-CNC"></p>
		                </div>
		                <div class="form-group" style="width:100%;">
		                  <label for="Observaciones_CNC" class="col-sm-12 control-label">Observaciones</label>
		                  <div class="col-sm-12"><textarea id="Observaciones_CNC" name="Observaciones_CNC" class="form-control"></textarea></div>
		                </div>
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_guardar_CNC" type="button" class="btn btn-primary" value="Guardar">
		                  <input id="btn_cancelar_CNC" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar">
		                  <!--onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"-->
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
		      <!--<div class="modal-footer">
									<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
									<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
								</div>-->
		    </div>
		  </div>
		</div>
		<!-- Modal -->

		<!-- Se crea el Modal que solicita asignar los registros de tasas de producción de las actividades a las que les aplica -->
		<div class="modal_rendimientos modal fade" id="modalrendimientos" tabindex="-1" role="dialog" aria-labelledby="modal_rendimientosLabel">
		  <div class="modal-dialog modal-xl" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalrendimientosLabel">
		          <p class="modal-body-texto-rendimientos" id="modal-body-texto-rendimientos"></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadroModal" class="cuadroModal col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <!--<div class="form-group">
		                                  <h3 class="col-sm-offset-2 col-sm-8 text-center"></h3>
		                              </div>
		                              -->
		              <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
		              <input type="hidden" id="Id" name="Id" value="0">
		              <input type="hidden" id="opcion" name="opcion" value="CNP">
		              <div class="form-group">
		                <div class="form-group parametro_rendimiento">
		                  <div class='form_eval form-group'>
		                    <h3 id='form_general'>Datos de Entrada</h3>
		                  </div>
		                  <div class="col-sm-12">
		                    <label for="rendimientos_costo_hora_oficial" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Costo Hora-Oficial Teórico</h5>
		                    </label>
		                    <label for="rendimientos_costo_hora_ayudante" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Costo Hora-Ayudante Teórico</h5>
		                    </label>
		                    <label for="rendimientos_factor_oficial" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Factor Hora-Oficial</h5>
		                    </label>
		                    <input id='rendimientos_costo_hora_oficial' name='costo_hora_oficial' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center;' onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_costo_hora_ayudante' name='rendimientos_costo_hora_ayudante' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center;' onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_factor_oficial' name='rendimientos_factor_oficial' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <br><br>
		                    <label for="rendimientos_unidad" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Unidad Productiva</h5>
		                    </label>
		                    <label for="rendimientos_Compromiso" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Cantidad Comprometida</h5>
		                    </label>
		                    <input id='rendimientos_unidad' name='unidad' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_Compromiso' name='Compromiso' class='form-control' type='number' value='' placeholder='Cantidad' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <br><br>
		                    <label for="rendimientos_oficial_cuadrilla_tipica" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Número de Oficiales por Cuadrilla Típica</h5>
		                    </label>
		                    <label for="rendimientos_ayudante_cuadrilla_tipica" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Número de Ayudantes por Cuadrilla Típica</h5>
		                    </label>
		                    <input id='rendimientos_oficial_cuadrilla_tipica' name='oficial_cuadrilla_tipica' class='form-control' type='number' value='' placeholder='Número de Oficiales por Cuadrilla Típica' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' autocomplete="off" value=1 onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_ayudante_cuadrilla_tipica' name='ayudante_cuadrilla_tipica' class='form-control' type='number' value='' placeholder='Número de Ayudantes por Cuadrilla Típica' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' autocomplete="off" value=1 onkeyup="sumar_rendimiento();">
		                  </div>
		                </div>
		                <div class="form-group parametro_rendimiento">
		                  <div class='form_eval form-group'>
		                    <h3 id='form_general'>Cantidades Reales Semanales</h3>
		                  </div>
		                  <div class="col-sm-12">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center"></label>
		                    <label for="rendimientos_reales" class="control-label" style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Cantidad</h5>
		                    </label>
		                    <label for="rendimientos_reales" class="control-label" style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Oficiales</h5>
		                    </label>
		                    <label for="rendimientos_reales" class="control-label" style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Ayudantes</h5>
		                    </label>
		                    <label for="rendimientos_reales" class="control-label" style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Horas Jornada</h5>
		                    </label>
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 1</h5>
		                    </label>
		                    <input id='rendimientos_real_1' name='real_1' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_1' name='recursos_of_1' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_1' name='recursos_ay_1' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_1' name='recursos_horas_1' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 2</h5>
		                    </label>
		                    <input id='rendimientos_real_2' name='real_2' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_2' name='recursos_of_2' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_2' name='recursos_ay_2' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_2' name='recursos_horas_2' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 3</h5>
		                    </label>
		                    <input id='rendimientos_real_3' name='real_3' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_3' name='recursos_of_3' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_3' name='recursos_ay_3' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_3' name='recursos_horas_3' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 4</h5>
		                    </label>
		                    <input id='rendimientos_real_4' name='real_4' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_4' name='recursos_of_4' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_4' name='recursos_ay_4' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_4' name='recursos_horas_4' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 5</h5>
		                    </label>
		                    <input id='rendimientos_real_5' name='real_5' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_5' name='recursos_of_5' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_5' name='recursos_ay_5' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_5' name='recursos_horas_5' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 6</h5>
		                    </label>
		                    <input id='rendimientos_real_6' name='real_6' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_6' name='recursos_of_6' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_6' name='recursos_ay_6' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_6' name='recursos_horas_6' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <label for="rendimientos_reales" class="control-label" style="width:150px; max-width:19% ;display:inline-block; text-align:center">
		                      <h5>Día 7</h5>
		                    </label>
		                    <input id='rendimientos_real_7' name='real_7' class='form-control' type='text' value='' placeholder='Cantidad' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_of_7' name='recursos_of_7' class='form-control' type='text' value='' placeholder='Oficiales' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_ay_7' name='recursos_ay_7' class='form-control' type='text' value='' placeholder='Ayudantes' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                    <input id='rendimientos_recursos_horas_7' name='recursos_horas_7' class='form-control' type='text' value='' placeholder='Horas' style='width:400px; max-width:19% ; padding:1px; margin:0; display:inline-block; text-align:center' autocomplete="off" onkeyup="sumar_rendimiento();">
		                  </div>
		                </div>
		                <div class="form-group parametro_rendimiento">
		                  <div class='form_eval form-group'>
		                    <h3 id='form_general'>Indicadores</h3>
		                  </div>
		                  <div class="col-sm-12">
		                    <label for="rendimientos_suma_cantidad_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Cantidad Ejecutada Semanal</h5>
		                    </label>
		                    <label for="rendimientos_suma_horas_oficial_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Horas-Oficial <br> Semanal</h5>
		                    </label>
		                    <label for="rendimientos_suma_horas_ayudante_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Horas-Ayudante Semanal</h5>
		                    </label>
		                    <input id='rendimientos_suma_cantidad_semanal' name='suma_cantidad_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_suma_horas_oficial_semanal' name='suma_horas_oficial_semana' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_suma_horas_ayudante_semanal' name='suma_horas_ayudante_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <br><br>
		                    <label for="rendimientos_suma_horas_laboradas_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Horas Laboradas Semanal</h5>
		                    </label>
		                    <label for="rendimientos_ay_prom_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Horas Promedio Diarias</h5>
		                    </label>
		                    <label for="rendimientos_eficiencia_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Eficiencia</h5>
		                    </label>
		                    <input id='rendimientos_suma_horas_laboradas_semanal' name='suma_horas_laboradas_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_horas_prom_semanal' name='horas_prom_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_eficiencia_semanal' name='eficiencia_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <br><br>
		                    <label for="rendimientos_rendimiento_semanal" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Rendimiento</h5>
		                    </label>
		                    <label for="rendimientos_consumo_hora_oficial" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Consumo <br> Horas-Oficial</h5>
		                    </label>
		                    <label for="rendimientos_consumo_hora_ayudante" class="control-label" style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Consumo <br> Horas-Ayudante</h5>
		                    </label>
		                    <input id='rendimientos_rendimiento_semanal' name='rendimiento_semanal' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_consumo_hora_oficial' name='consumo_hora_oficial' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_consumo_hora_ayudante' name='consumo_hora_ayudante' class='form-control' type='text' value='' style='width:400px; max-width:32% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <br><br>
		                    <label for="rendimientos_of_prom_semanal" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Oficiales Promedio <br> Diarios</h5>
		                    </label>
		                    <label for="rendimientos_ay_prom_semanal" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Ayudantes Promedio <br> Diarios</h5>
		                    </label>
		                    <input id='rendimientos_of_prom_semanal' name='of_prom_semanal' class='form-control' type='text' value='' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_ay_prom_semanal' name='ay_prom_semanal' class='form-control' type='text' value='' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <br><br>
		                    <label for="rendimientos_cuadrillas_tipicas_prom_semanal" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Número de Cuadrillas Típicas Por Día</h5>
		                    </label>
		                    <label for="rendimientos_cuadrillas_tipicas_exceso_deficit_semanal" class="control-label" style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center'>
		                      <h5>Exceso o Déficit de Ayudantes Por Día</h5>
		                    </label>
		                    <input id='rendimientos_cuadrillas_tipicas_prom_semanal' name='cuadrillas_tipicas_prom_semanal' class='form-control' type='text' value='' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                    <input id='rendimientos_cuadrillas_tipicas_exceso_deficit_semanal' name='cuadrillas_tipicas_exceso_deficit_semanal' class='form-control' type='text' value='' style='width:400px; max-width:45% ; padding:1px; margin:0; display:inline-block; text-align:center; background:white' readonly>
		                  </div>
		                </div>
		                <div class="form-group parametro_rendimiento" style="width:100%;">
		                  <div class='form_eval form-group'>
		                    <h3 id='form_general'>Observaciones</h3>
		                  </div>
		                  <div class="col-sm-12"><textarea id="rendimientos_observaciones" name="rendimientos_observaciones" class="form-control"></textarea></div>
		                </div>
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_guardar_rendimientos" type="button" class="btn btn-primary" value="Guardar">
		                  <input id="btn_cancelar_rendimientos" type="button" data-dismiss="modal" class="btn btn-danger" value="Cancelar">
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

		<!-- Se crea el Modal que muestra el informe parcial de la programación semanal -->
		<div class="modal_indicadores modal fade" id="modalindicadores" tabindex="-1" role="dialog" aria-labelledby="modal_indicadoresLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modalindicadoresLabel">
		          <p class="modal-body-texto-indicadores" id="modal-body-texto-indicadores">Indicadores Parciales de Programación Semanal</p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <div class="form-group">
		                <!-- <div class="form-group parametro_rendimiento">
		                                                        <div class='form_eval form-group'>
		                                                            <h3 id='form_general'>Datos de Entrada</h3>
		                                                       </div> -->
		                <div id="compromisos_semana" style="width: 100%; height: 500px; text-align: right; margin:auto; padding:-10px 0px;">
		                </div>
		                <!-- </div> -->
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_cerrar_indicadores" type="button" data-dismiss="modal" class="btn btn-primary" value="Cerrar">
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

		<!-- Se crea el Modal que permite confirmar el cierre de los compromisos de la semana (si se acepta, no se pueden cambiar los compromisos) -->
		<div class="modal_cerrar_compromisos modal fade" id="modal_cerrar_compromisos" tabindex="-1" role="dialog" aria-labelledby="modal_cerrar_compromisosLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modal_cerrar_compromisosLabel">
		          <p class="modal-body-texto-indicadores" id="modal-body-texto-cerrar_comromisos">Confirmar Compromisos Semana <?php echo $semana; ?></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <div class="form-group">
		                <div id="cerrar_compromisos_semana" style="width: 100%; margin:auto; padding:10px 20px;">
		                  <p>Al presionar el botón Confirmar, no se podrán volver a modificar los compromisos de las actividades de la presente semana.</p>
		                  <br>
		                  <p>¿Desea confirmar los compromisos?</p>
		                </div>
		                <!-- </div> -->
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_confirmar_compromisos_semana" type="button" data-dismiss="modal" class="btn btn-primary btn-lg" value="Confirmar">
		                  <input id="btn_cancelar_compromisos_semana" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cancelar">
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

		<!-- Se crea el Modal que avisa que los compromisos se han cerrado -->
		<div class="modal_aceptar_cerrar_compromisos modal fade" id="modal_aceptar_cerrar_compromisos" tabindex="-1" role="dialog" aria-labelledby="modal_aceptar_cerrar_compromisosLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modal_aceptar_cerrar_comromisosLabel">
		          <p class="modal-body-texto-aceptar_cerrar_compromisos" id="modal-body-texto-aceptar_cerrar_compromisos">Confirmar Compromisos Semana <?php echo $_SESSION["semana"]; ?></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <div class="form-group">
		                <div id="aceptar_cerrar_compromisos_semana" style="width: 100%; margin:auto; padding:10px 20px;">
		                </div>
		                <!-- </div> -->
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_cerrar_aceptar_compromisos_semana" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar">
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

		<!-- Se crea el Modal que avisa que para poder calificar las actividades, se deben confirmar primero los compromisos  -->
		<div class="modal_alerta_ejecutado_real_bloqueado modal fade" id="modal_alerta_ejecutado_real_bloqueado" tabindex="-1" role="dialog" aria-labelledby="modal_alerta_ejecutado_real_bloqueadoLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="modal_alerta_ejecutado_real_bloqueadoLabel">
		          <p class="modal-body-texto-alerta_ejecutado_real_bloqueado" id="modal-body-texto-alerta_ejecutado_real_bloqueado">Confirmar Compromisos Semana <?php echo $_SESSION["semana"]; ?></p>
		        </h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <div class="row">
		          <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
		            <form class="form form-horizontal" action="" method="POST">
		              <div class="form-group">
		                <div id="alerta_ejecutado_real_bloqueado" style="width: 100%; margin:auto; padding:10px 20px;">
		                  <p>Se debe presionar el botón <b>Confirmar Compromisos</b> para poder habilitar la calificación de las actividades.</p>
		                </div>
		                <!-- </div> -->
		              </div>
		              <!--Se crean los botones Guardar y Listar-->
		              <div class="form-group">
		                <div class="col-sm-offset-2 col-sm-8">
		                  <input id="btn_alerta_ejecutado_real_bloqueado" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar">
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

		<!-- Se crea el Modal que avisa que la cantidad que se está comprometiendo en una actividad, es inferior a la cantidad sugerida por el programa -->
		<div class="modal fade" id="modal_cantidad_comprometida_inferior" role="dialog">
		  <div class="modal-dialog modal-lg">
		    <!-- Modal content-->
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="modal_cantidad_comprometida_inferior_Label"><b>Cantidad Comprometida Inferior a la Sugerida</b></h4>
		        <button type="button" class="close" data-dismiss="modal">&times;</button>
		      </div>
		      <div class="modal-body" style="margin: auto; clear: none; display: flex; align-items: center; justify-content: center">
		        <i class="fas fa-exclamation-circle fa-5x" style="color:red;width:20%; height:100%; text-align:center"></i>
		        <div class="texto_cantidad_comprometida_inferior" style="width:79%; float:left"></div>
		      </div>
		      <div class="modal-footer">
		        <!-- <button type="button" class="btn btn-default btn-primary" id="cambiar_compromiso">Si</button>
		        <button type="button" class="btn btn-default btn-danger" id="mantener_compromiso">No</button> -->
						<input id="btn_cantidad_comprometida_inferior" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar">
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
			guardarNuevaActividad();
			$('#idNuevo').select2({allowClear: true});
			//ocultos();
			listar();
			//actualizarBarraFiltros(document.getElementById('baseDatos').value, document.getElementById('semana').value, "siListar");
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;
			var fechaCierreCompromisos = document.getElementById('fechaCierreCompromisos').value;

			/*Identificamos la altura de la hoja para determinar la altura de la tabla*/
			var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 250) + "px";

			if(Semanal_Confirmada==1){
					var botones_disponibles="<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button>";
					document.getElementById('textoFechaCierreCompromisos').innerHTML = "<p>Compromisos Cerrados el <b>" + fechaCierreCompromisos + "</b></p>";
			}else{
					var botones_disponibles="<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='duplicar btn btn-success btn-sm'  title='Duplicar Actividad' style='margin:1px'><i class='fa fa-clone fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm'  title='Eliminar Actividad' style='margin:1px'><i class='fa fa-trash-alt fa-xs'></i></button>";
			}

			var table = $("#dt_cliente").DataTable({
				/* "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>", */
				"dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>>t<'row'<'col-md-6'i>><'clear'>",
				"destroy": true,
				"ordering":false,
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
					"url":"../programacion_semanal/listar_programacion_semanal.php?db="+db+"&semana="+semana
				},
				"lengthMenu": [100, 200, 500],
				'columnDefs': [

						{
								'targets': [0,6,1,2,10,11,15,16,18,19],
								'width': "1%",
						 },
						 {
								'targets': [12,13,14],
								'width': "3%",
						 },
						{
								'targets': [4,5,7,8,17],
								'width': "6%",
						 },
						 {
 								'targets': [3],
 								'width': "9%",
 						 },
						//  {
						// 		 'targets': [2,6,9],
						// 		 'width': "3%",
						// 	},
						// 	{
						// 		'targets': [0,1,10,11,12,13,14,15,16,18,19,20,21,22,23],
						// 		'width': "1%",
						//  },
						//
						// {
						// 		'targets': [3],
						// 		'width': "15%",
						//  },

						{
								'targets': [3,4,7,8,9,13,14],
								'render': function ( data, type, full, meta ) {
												return data;
										}
						},

						{
								'targets': [6],
								'render': function ( data, type, full, meta ) {
										if(data===""){
												data="";
												return data;
										}else if(data==0){
												data="Sí";
												return data;
										}else if (data==1){
												data="No";
												return data;
										}

								},
						},

						/*{
								'targets': [15,16],
								'render': function (  data, type, row, meta ) {
												var Unidad= row['Unidad'];
												var cantidad_ppto= row['cantidad_ppto'];
												var porcentaje= (data/cantidad_ppto*100).toFixed(0);
												if(data=="" || data==null){
														return data;
												}else if(cantidad_ppto=='' || cantidad_ppto==null){
														return "<h6>" + data + "%</h6>";
												}else{
														return "<h6>" + data + " " + Unidad +  "</h6><h6 style='font-size=0.75em;'>(" + porcentaje +"%)</h6>";
												}
										}
						},*/

						{
								'targets': [17],
								'render': function ( data, type, row, meta ) {
									var Unidad= row['Unidad'];
									var cantidad_ppto= row['cantidad_ppto'];
									var Actividad= row['Actividad'];
									if(Actividad == "" || Actividad == null){
										return "";
									}else if(data==""){
											return "No Calificado";
									}else{
										data=data*1;
										data=data.toFixed(3);
										if(cantidad_ppto=='' || cantidad_ppto==null){
											return data + "%";
										}else{
											return data + ' ' + Unidad;
										}
									}

								},
						},

						{
								'targets': [19],
								'render': function ( data, type, full, meta ) {
										if(data==""){
												return data;
										}else{
												data=data*100;
												data=data.toFixed(2);
												return data + '%';
										}

								},
						},


						{
								'targets': [10,11],
										"render": function ( data, type, row, meta) {
												var Unidad= row['Unidad'];
												var cantidad_ppto= row['cantidad_ppto'];
												var Cantidad_Ejecutada= (cantidad_ppto * data).toFixed(1);


												if(data=="" || data==null){
														return data;
												}else if(cantidad_ppto=='' || cantidad_ppto==null){
														data=data*100;
														data=data.toFixed(2);
														return data + "%";
												}else{
														data=data*100;
														data=data.toFixed(2);
														return Cantidad_Ejecutada + " " + Unidad +  " (" + data +"%)";
												}
										},
						},

						{
								'targets': [15],
										"render": function ( data, type, row, meta) {
												var Ejecutado= row['Ejecutado'];
												var Unidad= row['Unidad'];
												var cantidad_ppto= row['cantidad_ppto'];
												var proyeccionSemana= row['proyeccionSemana'];
												// console.log(row["Id"] + ", diasLleva: " + row["diasLleva"] + ", diasSemana: " + row["diasSemana"] + ", diasTotales: " + row["diasTotales"] + ", proyeccionSemana: " + proyeccionSemana);

												if(data=="" || data==null){
														return data;
												}else if(cantidad_ppto=='' || cantidad_ppto==null){
														data=proyeccionSemana*100;
														if (data<0){
																data=0;
														}
														//data=data.toFixed(0);
														return data.toFixed(3) + '%';
												}else{
														data=(proyeccionSemana * cantidad_ppto);
														if (data<0){
																data=0;
														}
														//data=data.toFixed(0);
														return data.toFixed(3) + ' ' + Unidad;
												}
										},
						},

						{
								'targets': [18],
								'render': function ( data, type, full, meta ) {
										if(data=='' || data==null){
												data="";
												return data;
										}else{
												data=data*100;
												data=data.toFixed(0);
												//console.log(data);
												carita="";
												if (data >= 95){
														carita = " &nbsp;&nbsp;<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
												} else if(data < 95 && data >= 70){
														carita = " &nbsp;&nbsp;<i style='color:RGB(210,203,59)' class='fas fa-meh fa-2x'></i>";
												} else if(data < 70){
														carita = " &nbsp;&nbsp;<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
												}
												return data + '% ' + carita;
										}
								},
						},

						{
								'targets': [16],
								'render': function (  data, type, row, meta ) {
										var Ejecutado_Fin_Semana= row['Ejecutado_Fin_Semana'];
										var Ejecutado= row['Ejecutado'];
										var Unidad= row['Unidad'];
										var cantidad_ppto= row['cantidad_ppto'];
										var Actividad= row['Actividad'];
										if(cantidad_ppto=='' || cantidad_ppto==null){
											cantidad_ppto_final=100;
										}else{
											cantidad_ppto_final = cantidad_ppto;
										}
										var Cantidad_Sugerida= ((Ejecutado_Fin_Semana - Ejecutado)*cantidad_ppto_final).toFixed(3);

										if(Actividad == "" || Actividad == null){
											return "";
										}else if(data==""){
											return "No Comprometido Aún";
										}else if((Cantidad_Sugerida-data) > 0){
											data=data*1;
											data=data.toFixed(3);
											carita = " &nbsp;&nbsp;<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
											if(cantidad_ppto=='' || cantidad_ppto==null){
												return data + '%' + carita;
											}else{
												return data + ' ' + Unidad + carita;
											}
										}else{
											data=data*1;
											data=data.toFixed(3);
											carita = " &nbsp;&nbsp;<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
											if(cantidad_ppto=='' || cantidad_ppto==null){
												return data + '%' + carita;
											}else{
												return data + ' ' + Unidad + carita;
											}
										}

								},
						},

						{
								'targets': [0],
								'className': 'Botones'
						},
						{
								'targets': [4],
								'className': 'input_Descripcion'
						},
						{
								'targets': [5],
								'className': 'input_Ubicacion'
						},
						{
								'targets': [7],
								'className': 'input_Sub_Contratista'
						},
						{
								'targets': [8],
								'className': 'input_Responsable_AIA'
						},
						{
								'targets': [9],
								'className': 'input_Empresa'
						},
						{
								'targets': [13],
								'className': 'input_Unidad'
						},
						{
								'targets': [15],
								'className': 'input_Cantidad_Sugerida'
						},
						{
								'targets': [16],
								'className': 'input_Compromiso'
						},
						{
								'targets': [17],
								'className': 'input_Ejecutado_Real'
						},
						{
								'targets': [23],
								'className': 'input_Rendimientos'
						},
					],

				'select': {
					'style': 'false',
				},

				"lengthMenu": [10],

				"columns":[
						{"defaultContent": botones_disponibles},
						{"data":"Consecutivo", "visible":false},
						{"data":"Id"},
						{"data":"Actividad"},
						{"data":"Descripcion"},
						{"data":"Ubicacion", "visible":false},
						{"data":"Prog_Sin_Restricciones_100"},
						{"data":"Sub_Contratista",},
						{"data":"Responsable_AIA"},
						{"data":"Empresa", "visible":false},
						{"data":"Ejecutado"},
						{"data":"Ejecutado_Fin_Semana"},
						{"data":"medir_productividad", "visible":false},
						{"data":"Unidad"},
						{"data":"cantidad_ppto"},
						{"data":"proyeccionSemana"},
						{"data":"Compromiso"},
						{"data":"Ejecutado_Real"},
						{"data":"PAC"},
						{"data":"P_Completado"},
						{"data":"Categoria_CNC", "visible":false},
						{"data":"CNC", "visible":false},
						{"data":"Observaciones_CNC", "visible":false},
						{"data":"Rendimientos", "visible":false},
						{"data":"codigo_actividad", "visible":false}
				],

			"rowCallback": function( row, data, index ) {
				if(data.Atrasada==1 && data.Critica==1){
						$('td', row).css('background-color', '#7c1c51');
						$('td', row).css('color', '#ffffff');
				} else if(data.Atrasada==1 && data.Critica==0){
						$('td', row).css('background-color', 'rgba(255,83,51,0.8)');
				} else if(data.Critica==1){
					$('td', row).css('background-color', 'rgba(255,150,64,1)');
				} else if(data.Critica==0){
						$('td', row).css('background-color', 'rgba(255,192,51,0.5)');
				}
			},

				"language": idioma_espanol
			});

			$("div.toolbarFilaBotones").html('<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:30%;display:inline-block; "><button type= "button" class="leyenda_colores btn btn-secondary btn-sm" data-toggle="modal" data-target="#modal_leyenda_colores" style="margin-right:5px">Leyenda <i class="fas fa-question-circle fa-lg"></i></button><button id="btn_autoprogramar" class="btn btn-warning btn-sm" style="margin-right:5px">Autoprogramar Actividades <i class="fas fa-upload fa-lg"></i></button><button id="btn_agregar_actividad" type="button" class="btn btn-primary btn-sm">Agregar Actividad <i class="fas fa-plus fa-lg"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"><button id="btn_agregar_indicadores" class="btn btn-primary btn-sm" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modalindicadores" onClick="ind_compromisos_semana(\'\')">Indicadores Parciales <i class="fas fa-chart-line fa-lg"></i></button><button id="btn_cerrar_compromisos_semana" type="button" class="btn btn-danger btn-sm" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modal_cerrar_compromisos" onClick="cerrar_compromisos_semana(\'\')">Confirmar Compromisos <i class="fas fa-lock fa-lg"></i></button><button id="btn_informe_compromisos" type="button" class="btn btn-warning btn-sm" style="margin-right:5px; margin-left:0" onclick="descargarCompromisosSemana()">Imprimir  <i class="fas fa-print fa-lg"></i></button><div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example"><button id="btn_Actividades" type="button" class="btn btn-success btn-sm active" onclick="window.location.href=\'../cambiar_pagina.php?seccion=programacion_semanal&semana='+semana+'\'">Actividades <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=CNP&semana='+semana+'\'">Causas No Programación <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_CNC" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=CNC&semana='+semana+'\'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button><button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm " onclick="window.location.href=\'../cambiar_pagina.php?seccion=CIC&semana='+semana+'\'">Calificación de Proveedores <i class="fas fa-arrow-right fa-m"></i></button><!--<button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'../cambiar_pagina.php?seccion=indicadores&semana='+semana+'\'">Indicadores de Last Planner</button>--></div></div>');

			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			$("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

			if(Semanal_Confirmada==1){
				$("#btn_autoprogramar").hide();
				$("#btn_agregar_actividad").hide();
				$("#btn_cerrar_compromisos_semana").hide();
				$("#btn_informe_compromisos").show();
			}else{
				$("#btn_autoprogramar").show();
				$("#btn_agregar_actividad").show();
				$("#btn_cerrar_compromisos_semana").show();
				$("#btn_informe_compromisos").hide();
			}

			// activarBuscador("#dt_cliente tbody", table);
			maestroPermisos(document.getElementById('permiso').value);
			nueva_actividad();
			autoprogramar();
			obtener_data_editar("#dt_cliente tbody", table);
			obtener_id_eliminar("#dt_cliente tbody", table);
			obtener_id_duplicar("#dt_cliente tbody", table);
			obtener_id_generar_CNC("#dt_cliente tbody", table);
			obtener_id_rendimientos("#dt_cliente tbody", table);

			// Filtros de texto
			$('#buscadorActividad').on('keyup', function() {
				table.column(3).search($('#buscadorActividad').val()).draw();
			});
			$('#buscadorLiberada').on('change', function() {
				table.column(6).search($('#buscadorLiberada').val()).draw();
			});
			$('#buscadorSubcontratista').on('change', function() {
				table.column(7).search($('#buscadorSubcontratista').val()).draw();
			});
			$('#buscadorResponsableAIA').on('change', function() {
				table.column(8).search($('#buscadorResponsableAIA').val()).draw();
			});
			$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
				let filtro = $('#buscadorPAC').val().trim(); // Obtiene el valor del filtro
				let valorColumna = data[18].trim(); // Obtiene el valor de la columna 18			

				// Si no hay filtro, muestra todos los datos
				if (filtro === "") {
					return true;
				}

				// Compara el valor de la columna con el filtro EXACTAMENTE
				return valorColumna === filtro;
			});

			// Aplica el filtro cuando cambie el select
			$('#buscadorPAC').on('change', function() {
				table.draw();
			});

		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
		var obtener_data_editar = function(tbody, table) {
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		  var permiso = document.getElementById('permiso').value;

			if((max_semana-2)>=semana){
				if (permiso=="P"){
					var only_once = true;
				}else{
					var only_once = false;
				}
			}else{
				if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="OT" || permiso=="DCV" || permiso=="V" || permiso=="C"){
					var only_once = false;
				}else{
					var only_once = true;
				}
			}

			var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;


		  $(tbody).one("click", "td", function() {
		    if (only_once == true) {
					var data= table.row($(this).parents("tr")).data();
					var Id=$("#Id").val(data.Consecutivo);
					var opcion = $("#opcion").val("modificar");

					var Sub_Contratista = <?php
																	require("../conexion.php");
																	$db = $_SESSION['db'];
																	$query="SELECT * FROM $db"."_subcontratistas WHERE Activo=1";
																	$resultado= mysqli_query($conexion, $query);
																	$Sub_Contratista="<option value='AIA (MO Directa)'>AIA (MO Directa)</option>";
																	while ($valores = mysqli_fetch_array($resultado)){
																			$valor=$valores["subcontratista"];
																			$Sub_Contratista .="<option value='$valor'>$valor</option>";
																	};
																	echo '"'.$Sub_Contratista.'"';
																	mysqli_close($conexion);
															?>;

					var Responsable_AIA = <?php
																	require("../conexion.php");
																	$db = $_SESSION['db'];
																	$query="SELECT * FROM $db"."_profesionales WHERE Activo=1";
																	$resultado= mysqli_query($conexion, $query);
																	$Responsable_AIA="";
																	while ($valores = mysqli_fetch_array($resultado)){
																			$valor=$valores["nombre"];
																			$Responsable_AIA .="<option value='$valor'>$valor</option>";
																	};
																	echo '"'.$Responsable_AIA.'"';
																	mysqli_close($conexion);
															?>;

					if(Semanal_Confirmada==1 && permiso!='P'){
							var codigo_html_Descripcion = "<textarea id='select_Descripcion' name='Descripcion' class='form-control form-control-sm' readonly>'"+data.Descripcion+"'</textarea>";

							var codigo_html_Ubicacion = "<textarea id='select_Ubicacion' name='Ubicacion' class='form-control form-control-sm' readonly>'"+data.Ubicacion+"'</textarea>";

							var codigo_html_Unidad = "<input id='select_Unidad' name='Unidad' class='form-control form-control-sm' type='text' value='"+data.Unidad+"' readonly></input>";

							var codigo_html_Sub_Contratista = "<input id='select_Sub_Contratista' name='Sub_Contratista' class='form-control form-control-sm' type='text' value='"+data.Sub_Contratista+"' readonly></input>";

							var codigo_html_Responsable_AIA = "<input id='select_Responsable_AIA' name='Responsable_AIA' class='form-control form-control-sm' type='text' value='"+data.Responsable_AIA+"' readonly></input>";

							var codigo_html_Compromiso = "<input id='select_Compromiso' name='Compromiso' class='form-control form-control-sm' type='text' value='"+data.Compromiso+"' readonly></input>";
					}else{
							var codigo_html_Descripcion = "<textarea id='select_Descripcion' name='Descripcion' class='form-control form-control-sm'>'"+data.Descripcion+"'</textarea>";

							var codigo_html_Ubicacion = "<textarea id='select_Ubicacion' name='Ubicacion' class='form-control form-control-sm'>'"+data.Ubicacion+"'</textarea>";

							var codigo_html_Sub_Contratista = "<select id='select_Sub_Contratista' name='Sub_Contratista' class='form-control form-control-sm' ><option value=''></option>"+Sub_Contratista+"</select>";

							var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control form-control-sm' ><option value=''></option>"+Responsable_AIA+"</select>";

							var codigo_html_Unidad = "<select id='select_Unidad' name='Unidad' class='form-control form-control-sm'><option value=''></option><option value='ml'>ml</option><option value='m2'>m2</option><option value='m3'>m3</option><option value='un'>Un</option><option value='gl'>Gl</option><option value='kg'>kg</option><option value='%'>%</option><option value='Niveles'>Niveles</option></select>";

							var codigo_html_Compromiso = "<input id='select_Compromiso' name='Compromiso' class='form-control form-control-sm' type='text' value='"+data.Compromiso+"'></input><button id='btn_comprometerSugerido' class='comprometerSugerido btn btn-primary btn-sm' onclick='comprometerSugerido()'>Comprometer Sugerido</button>";
					}
					$( this ).parent().find('.input_Descripcion').html(codigo_html_Descripcion);
					$( this ).parent().find('.input_Unidad').html(codigo_html_Unidad);
					$( this ).parent().find('.input_Compromiso').html(codigo_html_Compromiso);
					$( this ).parent().find('.input_Ubicacion').html(codigo_html_Ubicacion);
					$( this ).parent().find('.input_Sub_Contratista').html(codigo_html_Sub_Contratista);
					$( this ).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA);

					var codigo_html_Empresa =  "<input id='select_Empresa' name='Empresa' class='form-control form-control-sm' type='text' value='"+data.Empresa+"'></input>";
					$( this ).parent().find('.input_Empresa').html(codigo_html_Empresa);

					var Cantidad_Sugerida= (data.proyeccionSemana);
					if(data.cantidad_ppto=='' || data.cantidad_ppto==null){
							Cantidad_Sugerida=Cantidad_Sugerida*100;
					}else{
							Cantidad_Sugerida=(Cantidad_Sugerida * data.cantidad_ppto);
					}
					if (Cantidad_Sugerida<0){
							Cantidad_Sugerida=0;
					}
					Cantidad_Sugerida=(Cantidad_Sugerida).toFixed(3);
					var codigo_html_Cantidad_Sugerida =  "<input id='select_Cantidad_Sugerida' name='Cantidad_Sugerida' class='form-control form-control-sm' type='text' value='"+Cantidad_Sugerida+"' readonly></input>";
					$( this ).parent().find('.input_Cantidad_Sugerida').html(codigo_html_Cantidad_Sugerida);



					if(Semanal_Confirmada==1){
							if(data.medir_productividad==0){
									var codigo_html_Ejecutado_Real = "<input id='select_Ejecutado_Real' name='Real' class='form-control form-control-sm' type='text' value='"+data.Ejecutado_Real+"'></input>";
							}else{
									var codigo_html_Ejecutado_Real = "<input id='select_Ejecutado_Real' name='Real' class='form-control form-control-sm' type='text' value='"+data.Ejecutado_Real+"' readonly></input><input id='btn_rendimientos' type='button' class='rendimientos btn btn-primary btn-lg' value='Rendimientos' data-toggle='modal' data-target='#modalrendimientos' >";
							}
							var codigo_html_Rendimientos =  "<input id='select_Rendimientos' name='Rendimientos' class='form-control form-control-sm' type='hidden' value='"+data.Rendimientos+"' readonly></input>";
							$( this ).parent().find('.input_Ejecutado_Real').html(codigo_html_Ejecutado_Real + codigo_html_Rendimientos);
							$("#select_Ejecutado_Real").val(data.Ejecutado_Real).change();

					}else{
							var codigo_html_Ejecutado_Real = "<input id='select_Ejecutado_Real' name='Real' class='form-control form-control-sm' type='text' value='"+data.Ejecutado_Real+"' readonly></input>"
							var codigo_html_Rendimientos =  "<input id='select_Rendimientos' name='Rendimientos' class='form-control form-control-sm' type='hidden' value='"+data.Rendimientos+"' readonly></input>";
							$( this ).parent().find('.input_Ejecutado_Real').html(codigo_html_Ejecutado_Real + codigo_html_Rendimientos);
							$("#select_Ejecutado_Real").val(data.Ejecutado_Real).change();
							$("#select_Ejecutado_Real").one("click", function(){
								$("#modal_alerta_ejecutado_real_bloqueado").modal("show");
							});
					}

					var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' style='padding:5px; margin:1px' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' style='padding:5px; margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_generar_CNC' class='generar_CNC btn btn-warning btn-sm'  title='Estado de ejecución de la Actividad' style='margin:1px' hidden='hidden'><i class='fa fa-edit fa-xs'></i></button>";
					$( this ).parent().find('.Botones').html(codigo_html_botones);
					$("#select_Descripcion").val(data.Descripcion).change();
					$("#select_Descripcion").focus();
					$("#select_Ubicacion").val(data.Ubicacion).change();
					$("#select_Sub_Contratista").val(data.Sub_Contratista).change();
					$("#select_Responsable_AIA").val(data.Responsable_AIA).change();
					$("#select_Empresa").val(data.Empresa).change();
					$("#select_Unidad").val(data.Unidad).change();
					$("#select_Compromiso").val(data.Compromiso).change();
					$("#select_Rendimientos").val(data.Rendimientos).change();

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
				guardar();
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
		//       var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		//       unidad = json_info[0];
		//       $("#select_unidad").val(unidad).change();
		//     });
		//   }
		// }



		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  recargarTabla("listar");
		  limpiar_datos();
		  $("#formulario_nuevo").slideUp("slow");
		  $("#cuadroTabla").slideDown("slow");
		});

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_cancelar_CNC").on("click", function() {

			var table = $('#dt_cliente').DataTable();
		  guardar();
			obtener_id_generar_CNC("#dt_cliente tbody", table);
		});

		$("#btn_cancelar_CNP, #btn_cancelar_eliminar, #btn_cancelar_duplicar").on("click", function() {
			;
			recargarTabla("listar");
		});

		var cancelarEdicionFila = function() {
		  $("#btn_cancelar_editar").one("click", function(e) {
		    e.preventDefault();
		    recargarTabla("listar");
		  });
		}

		var autoprogramar=function(){
			$("#btn_autoprogramar").on("click", function(){
				var opcion="autoprogramar";
				var semana=document.getElementById('semana').value;
				var db=document.getElementById('baseDatos').value;
				$("#modal_spinner").modal("show");
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
					contenttype:"charset=utf-8",
					data: {"semana": semana, "opcion": opcion}
				}).done( function( info ){
					var respuesta = (typeof info === 'string' ? JSON.parse(info) : info);
					console.log(respuesta);
					if(respuesta=="OK"){
						location.reload(true);
					}
				});
			});
		}

		/*Abre el formulario para registrar una nueva actividad*/
		var nueva_actividad = function() {
		  $("#btn_agregar_actividad").one("click", function() {
				var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;
				if (Semanal_Confirmada == 1) {
					alert("Ya fueron confirmados los compromisos para la presente semana. No se pueden agregar más actividades");
					location.reload(true);
				}else {
					document.getElementById('opcion').value = "nuevo";
					document.getElementById('Id').value = "";
					$("#Actividad").val("").change();
					$("#Descripcion").val("").change();
					$("#Ubicacion").val("").change();
					$("#Sub_Contratista").val("").change();
					$("#Responsable_AIA").val("").change();
					$("#Empresa").val("").change();
					$("#Unidad").val("").change();
					$("#Compromiso").val("").change();
					$("#Real").val("").change();
					$('#Real').attr('readonly', true);
					$('#Actividad').attr('readonly', false);
					$('#idNuevo').attr('readonly', false);
					$('#Unidad').attr('readonly', false);

					$("#formulario_nuevo").slideDown("slow");
					$("#cuadroTabla").hide("slow");

					importar_actividad_no_requerida();
				}

		  });
		}

		var guardarNuevaActividad = function(){
			$("#btn_guardar_nueva_actividad").on("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var semana = document.getElementById('semana').value;
				var opcion = document.getElementById('opcion').value;
				var frm = $(".form_nueva_actividad").serialize() + "&semana="+semana + "&opcion="+opcion;

				if($("#idNuevo").val() == '' || $("#Actividad").val() == '' || $("#Sub_Contratista").val() == '' || $("#Responsable_AIA").val() == '' || $("#Unidad").val() == '' || $("#Compromiso").val() == ''){
					$(".mensaje").html("<strong>Advertencia!</strong> debe llenar todos los campos solicitados <strong>(marcados con *)</strong>.").css({
			      "color": "#C9302C"
			    });
			    $(".mensaje").fadeOut(5000, function() {
			      $(this).html("");
			      $(this).fadeIn(1000);
			    });
				}else{
					//console.log(frm);
					$.ajax({
						method: "POST",
						url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
	          contenttype:"charset=utf-8",
						data: frm,
					}).done( function( info ){
						var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
						//console.log(json_info);
	          location.reload(true);
					});
				}
			});
		}

		var importar_actividad_no_requerida = function() {
		  $("#idNuevo").on("change", function() {
				var db = document.getElementById('baseDatos').value;
		    $("#Real").val("1").change();
		    $('#Real').attr('readonly', true);
		    $('#Actividad').attr('readonly', true);
				$('#Unidad').attr('readonly', true);

		    var opcion = "importar_actividad_no_requerida",
		      	Consecutivo = document.getElementById('idNuevo').value,
		      	semana = document.getElementById('semana').value;
		    //console.logopcion, Consecutivo)
		    $.ajax({
		      method: "POST",
		      url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "semana": semana,
		        "opcion": opcion,
		        "Consecutivo": Consecutivo
		      }
		    }).done(function(info) {
		      var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		      $("#Sub_Contratista").val(json_info["data"]["Sub_Contratista"]).change();
		      $("#Responsable_AIA").val(json_info["data"]["Responsable_AIA"]).change();
		      $("#Actividad").val(json_info["data"]["Actividad"]).change();
					if(json_info["data"]["unidad"] == ""){
						$("#Unidad").val("%").change();
					}else{
						$("#Unidad").val(json_info["data"]["unidad"]).change();
					}

		    });
		  });
		}

		var cerrar_compromisos_semana = function() {
			$("#btn_confirmar_compromisos_semana").one("click", function() {
				var db = document.getElementById('baseDatos').value ;
				var semana = document.getElementById('semana').value ;
				var fechaCierreCompromisos = new Date();
				fechaCierreCompromisos = formatDate(fechaCierreCompromisos);
				$.ajax({
					method: "POST",
					url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
					contenttype: "charset=utf-8",
					data: {
						"opcion": "bloquear_compromisos",
						"semana": semana,
						"fechaCierreCompromisos": fechaCierreCompromisos
					}
				}).done(function(info) {
					var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
					//console.logjson_info);
					var respuesta = json_info;
					$("#modal_aceptar_cerrar_compromisos").modal("show");
					if (respuesta == "Bloqueado") {
						$("#aceptar_cerrar_compromisos_semana").html("<p>Se han bloqueado los compromisos de la presente semana.</p><br><p>A partir de este momento, no se podrán modificar los compromisos o eliminar las actividades.</p>");
						$("#btn_cerrar_aceptar_compromisos_semana").one("click", function() {
							location.reload(true);
						});
					} else {
						$("#aceptar_cerrar_compromisos_semana").html("<p>Se detectaron actividades sin tener asignado su compromiso.</p><br><p>Para poder continuar con la calificación, se deben asignar todos los compromisos o eliminar las actividades y asignar las causas de no programación correspondientes.</p>");
					}
				});
			});
		}

		/*Toma los datos de la fila en la que se presionó el botón eliminar*/
		var obtener_id_eliminar=function(tbody, table){
			var permiso = document.getElementById('permiso').value;
			if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="OT" || permiso=="DCV" || permiso=="V" || permiso=="C"){
			}else{
				$(tbody).one("click", "button.eliminar", function(){
					$("#modalEliminar").modal("show");
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#Id").val(data.Consecutivo);
					var semana=$("#semana").val(data.Semana);
					var Actividad=$("#Actividad_Eliminar_Duplicar").val(data.Actividad);
					var Responsable_AIA=$("#Responsable_AIA_Eliminar_Duplicar").val(data.Responsable_AIA);
					var Empresa=$("#Empresa_Eliminar_Duplicar").val(data.Empresa);
					var Categoria_CNP=$("#Categoria_CNP_Eliminar_Duplicar").val(data.Categoria_CNP);
					var CNP=$("#CNP_Eliminar_Duplicar").val(data.CNP);
					var Observaciones_CNP=$("#Observaciones_CNP_Eliminar_Duplicar").val(data.Observaciones_CNP);
					var Activa=$("#Activa_Eliminar_Duplicar").val(data.Activa);
					var opcion=$("#opcion_Eliminar_Duplicar").val("eliminar");
					//console.log($('#CNP').val());
					var texto=$("#modal-body-texto-eliminar").html("¿Desea eliminar de la programación semanal la actividad: "+data.Actividad+"?");

					eliminar();
				});
			}


		}

		/*Toma los datos de la fila en la que se presionó el botón duplicar*/
		var obtener_id_duplicar=function(tbody, table){
			var permiso = document.getElementById('permiso').value;
			if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="OT" || permiso=="DCV" || permiso=="V" || permiso=="C"){
			}else{
				$(tbody).one("click", "button.duplicar", function(){
					$("#modalDuplicar").modal("show");
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#Id").val(data.Consecutivo);
					var semana=$("#semana").val(data.Semana);
					var Actividad=$("#Actividad_Eliminar_Duplicar").val(data.Actividad);
					var opcion=$("#opcion_Eliminar_Duplicar").val("duplicar");
					var texto=$("#modal-body-texto-duplicar").html("¿Desea duplicar la actividad: "+data.Actividad+"?");

					duplicar();
				});
			}
		}

		/* Ejecuta la funcion eliminar_duplicar, solo cuando se presionan los botones eliminar o duplicar en cada uno de los registros. La función eliminar_duplicar busca el id de el registro en el que se presinó los botones eliminar o duplicar y lo envia por medio de AJAX para que se ejecute la funcion eliminar o duplicar en guardar.php*/
		var eliminar = function() {
		  $("#eliminar-actividad").one("click", function() {
				var db = document.getElementById('baseDatos').value;
		    var Id = document.getElementById('Id').value;
		    var opcion = document.getElementById('opcion_Eliminar_Duplicar').value;
		    var semana = document.getElementById('semana').value;
		    var Responsable_AIA = document.getElementById('Responsable_AIA_CNP').value;
		    var Empresa = document.getElementById('Empresa_CNP').value;
		    var Categoria_CNP = document.getElementById('Categoria_CNP').value;
		    var CNP = document.getElementById('CNP').value;
		    var Observaciones_CNP = document.getElementById('Observaciones_CNP').value;
		    var Activa = document.getElementById('Activa_Eliminar_Duplicar').value;
		    //$("#modal_CNP").modal("hide");
		    console.log("funciona",opcion, semana, Id, Categoria_CNP, CNP, Activa);
		    $.ajax({
		      method: "POST",
		      url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "Id": Id,
		        "semana": semana,
		        "opcion": opcion,
		        "Responsable_AIA": Responsable_AIA,
		        "Empresa": Empresa,
		        "Categoria_CNP": Categoria_CNP,
		        "CNP": CNP,
		        "Observaciones_CNP": Observaciones_CNP
		      }
		    }).done(function(info) {
					// console.log("ok");
					// limpiar_datos();
					recargarTabla("");
		    });
		  });
		}

		var duplicar = function() {
		  $("#duplicar-usuario").one("click", function() {
				var db = document.getElementById('baseDatos').value;
		    var Id = document.getElementById('Id').value;
		    var opcion = document.getElementById('opcion_Eliminar_Duplicar').value;
		    var semana = document.getElementById('semana').value;
		    var Responsable_AIA = document.getElementById('Responsable_AIA_CNP').value;
		    var Empresa = document.getElementById('Empresa_CNP').value;
		    var Categoria_CNP = document.getElementById('Categoria_CNP').value;
		    var CNP = document.getElementById('CNP').value;
		    var Observaciones_CNP = document.getElementById('Observaciones_CNP').value;
		    var Activa = document.getElementById('Activa_Eliminar_Duplicar').value;
		    //$("#modal_CNP").modal("hide");
		    console.log("funciona",opcion, semana, Id, Categoria_CNP, CNP, Activa);
		    $.ajax({
		      method: "POST",
		      url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "Id": Id,
		        "semana": semana,
		        "opcion": opcion,
		        "Responsable_AIA": Responsable_AIA,
		        "Empresa": Empresa,
		        "Categoria_CNP": Categoria_CNP,
		        "CNP": CNP,
		        "Observaciones_CNP": Observaciones_CNP
		      }
		    }).done(function(info) {
					console.log("ok");
					limpiar_datos();
					recargarTabla("");
		    });
		  });
		}

		var asignar_CNP = function() {
		  var Activa = $("#Activa_Eliminar_Duplicar").val();
		  if (Activa == 'NA') {
		    $("#eliminar-actividad").click();
		  } else {
		    $("#modal_CNP").modal("show");
		    var Actividad = $("#Actividad_Eliminar_Duplicar").val();
		    var texto = $("#modal-body-texto-CNP").html("Indique la Categoría y Causa de No programación de la actividad: " + Actividad);
		    var Responsable_AIA = $("#Responsable_AIA_Eliminar_Duplicar").val();
		    $('#Responsable_AIA_CNP').val(Responsable_AIA).change();
		    var Empresa = $("#Empresa_Eliminar_Duplicar").val();
		    $('#Empresa_CNP').val(Empresa).change();
		    var CNP = $("#CNP_base_Eliminar_Duplicar").val();
		    cnp(CNP);
		    var Categoria_CNP = $("#Categoria_CNP_base_Eliminar_Duplicar").val();
		    $('#Categoria_CNP').val(Categoria_CNP).change();
		    var Observaciones_CNP = $("#Observaciones_CNP_base_Eliminar_Duplicar").val();
		    $('#Observaciones_CNP').val(Observaciones_CNP).change();
		  }
		}

		var cnp = function(CNP) {
		  $('#Categoria_CNP').on('change', function() {
		    var categoria = $("#Categoria_CNP").val();
				var opcion = "CNC";
		    //console.log(categoria);
		    if (categoria === "") {
		      $('#CNP').attr('readonly', true);
		      $('#CNP').html("<option value=''></option>");
		    } else {
		      $.ajax({
		        method: "POST",
		        url: "../programacion_semanal/guardar_CNC.php?db=login",
		        contenttype: "charset=utf-8",
		        data: {
		          "categoria": categoria,
		          "opcion": opcion
		        },
		        success: function(a) {
		          $('#CNP').html(a);
		          $("#CNP option[value='" + CNP + "']").attr('selected', true);
		          $('#CNP').attr('readonly', false);
		        }
		      });
		    }
		  });
		}

		/*Asigna el porcentaje sugerido al compromiso*/
		var comprometerSugerido = function() {
			var Cantidad_SugeridaValor=$("#select_Cantidad_Sugerida").val();
			$("#select_Compromiso").val(Cantidad_SugeridaValor).change();
			document.getElementById("select_Compromiso").focus();
		}

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function() {
			$("#btn_guardar_editar").on("click", function(e){
				e.preventDefault();
				var db = document.getElementById('baseDatos').value;
				var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

				var Id = $("#Id").serialize();
        var semana = $("#semana").serialize();
        var opcion = $("#opcion").serialize();

				var Descripcion=$("#select_Descripcion").serialize();
        var Ubicacion="Ubicacion="/*$("#select_Ubicacion").serialize()*/;
        var Sub_Contratista=$("#select_Sub_Contratista").serialize();
        var Responsable_AIA=$("#select_Responsable_AIA").serialize();
				var Sub_ContratistaValor=$("#select_Sub_Contratista").val();
        var Responsable_AIAValor=$("#select_Responsable_AIA").val();
        var Empresa="Empresa="/*$("#select_Empresa").serialize()*/;
        var Unidad=$("#select_Unidad").serialize();
				var UnidadValor=$("#select_Unidad").val();

				var table = $("#dt_cliente").DataTable();
				var data = table.row($(this).parents("tr")).data();
				var EjecutadoPorcentaje = data.Ejecutado;
				var cantidad_ppto = data.cantidad_ppto;

				if(cantidad_ppto=='' || cantidad_ppto==null){
						EjecutadoValor=EjecutadoPorcentaje*100;
						// EjecutadoValor=EjecutadoValor.toFixed(2);
						cantidad_ppto = 100;
						UnidadValor = "%";
						$("#select_Unidad").val("%");
						Unidad=$("#select_Unidad").serialize();
				}else{
						EjecutadoValor=EjecutadoPorcentaje * cantidad_ppto;
						// EjecutadoValor=EjecutadoValor.toFixed(2);
				}

				var Cantidad_Sugerida=$("#select_Cantidad_Sugerida").serialize();
				var Cantidad_SugeridaValor=$("#select_Cantidad_Sugerida").val();
				var CompromisoValor=$("#select_Compromiso").val();
				if(CompromisoValor==0){
          $("#select_Compromiso").val('');
          var Compromiso=$("#select_Compromiso").serialize();
        }else{
					var avanceTotalComprometido = Number(CompromisoValor)+Number(EjecutadoValor);
					avanceTotalComprometido = avanceTotalComprometido.toFixed(3);

					if(avanceTotalComprometido  > (Number(cantidad_ppto)+0.05)){
						$(".texto_cantidad_comprometida_inferior").html("La tarea fue comprometida en una cantidad superior a la cantidad que falta por ejecutar! ("+(CompromisoValor) +" "+UnidadValor+").\n\n<b>Para la presente semana se debe comprometer como máximo "+(cantidad_ppto-EjecutadoValor) +UnidadValor+".</b>");
						$("#modal_cantidad_comprometida_inferior_Label").html("<b>Cantidad Comprometida Superior a lo Requerido</b>");
						$("#modal_cantidad_comprometida_inferior").modal("show");
						recargarTabla("listar");
					}else{
						var Compromiso=$("#select_Compromiso").serialize();
					}
        }

				var Ejecutado_RealValor=$("#select_Ejecutado_Real").val();
				var avanceTotalReal = Number(Ejecutado_RealValor)+Number(EjecutadoValor);
				avanceTotalReal = avanceTotalReal.toFixed(3);
				if(avanceTotalReal  > (Number(cantidad_ppto)+0.05)){
					$(".texto_cantidad_comprometida_inferior").html("Se asignó una ejecución superior a la cantidad que falta por ejecutar! ("+(Ejecutado_RealValor) +" "+UnidadValor+").\n\n<b>Para la presente semana se debe ejecutar como máximo "+(cantidad_ppto-EjecutadoValor) +UnidadValor+".</b>");
					$("#modal_cantidad_comprometida_inferior_Label").html("<b>Ejecución Superior a lo Requerido</b>");
					$("#modal_cantidad_comprometida_inferior").modal("show");
					recargarTabla("listar");
				}else{
					var Ejecutado_Real=$("#select_Ejecutado_Real").serialize();
				}

        var Categoria_CNC=$("#Categoria_CNC").serialize();
        var CNC=$("#CNC").serialize();
        var Observaciones_CNC=$("#Observaciones_CNC").serialize();
        var Rendimientos=$("#select_Rendimientos").serialize();

				if((Ejecutado_RealValor - CompromisoValor)<0 && Ejecutado_RealValor !='' && CompromisoValor !=''){
						$("#btn_generar_CNC").click();
						guardar_CNC();
				}else{
					frm=Id+"&"+semana+"&"+opcion+"&"+Descripcion+"&"+Ubicacion+"&"+Sub_Contratista+"&"+Responsable_AIA+"&"+Empresa+"&"+Unidad+"&"+Cantidad_Sugerida+"&"+Compromiso+"&"+Ejecutado_Real+"&"+Rendimientos+"&Categoria_CNC=&CNC=&Observaciones_CNC=";
					//console.log(frm);



					if(Sub_ContratistaValor == '' || Sub_ContratistaValor == null){
						$(".texto_cantidad_comprometida_inferior").html("<p>Para todas las actividades se debe seleccionar un contratista. Dado el caso que la actividad se realice por medio de mano de obra directa, debe seleccionar de la lista desplegable <b>\"AIA (MO Directa)\"</b></p><p>Recuerde que desde la programación intermedia se pueden predefinir los subcontratistas encargados de cada actividad, de esta manera no tendrán que asignarlos cada semana.</p>");
						$("#modal_cantidad_comprometida_inferior_Label").html("<b>No se ha seleccionado un Subcontratista!!</b>");
						$("#modal_cantidad_comprometida_inferior").modal("show");
						recargarTabla("listar");
					}else if(Responsable_AIAValor == '' || Responsable_AIAValor == null){
						$(".texto_cantidad_comprometida_inferior").html("<p>Para todas las actividades se debe seleccionar un profesional de AIA responsable.</p><p>Recuerde que desde la programación intermedia se pueden predefinir los profesionales encargados de cada actividad, de esta manera no tendrán que asignarlos cada semana.</p>");
						$("#modal_cantidad_comprometida_inferior_Label").html("<b>No se ha seleccionado un Profesional de AIA!!</b>");
						$("#modal_cantidad_comprometida_inferior").modal("show");
						recargarTabla("listar");
					}else{
						$.ajax({
							method: "POST",
							url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
							contenttype:"charset=utf-8",
							data: frm,
						}).done( function( info ){
							//limpiar_datos();
							if((Cantidad_SugeridaValor - CompromisoValor) > 0 && CompromisoValor !='' && Semanal_Confirmada == 0){
								$(".texto_cantidad_comprometida_inferior").text("La tarea fue comprometida en una cantidad inferior a la sugerida! ("+CompromisoValor +" "+UnidadValor+").\n\nPara poder terminar la actividad en la fecha indicada por el cronograma general, se debe comprometer en la presente semana "+Cantidad_SugeridaValor +UnidadValor+".");
								$("#modal_cantidad_comprometida_inferior_Label").html("<b>Cantidad Comprometida Inferior a la Sugerida</b>");
								$("#modal_cantidad_comprometida_inferior").modal("show");
			        }

							recargarTabla("");
						});
					}
				}
			});
		}

		var obtener_id_generar_CNC = function(tbody, table) {
			$(tbody).one("click", "button.generar_CNC", function() {
				var data = table.row($(this).parents("tr")).data();
				var idusuario = $("#Id").val(data.Consecutivo);
				var semana = $("#semana").val(data.Semana);
				var Actividad = $("#Actividad_Eliminar_Duplicar").val(data.Actividad);
				var opcion = $("#opcion_Eliminar_Duplicar").val("CNC");
				var Compromiso = $("#select_Compromiso").val();
				var Ejecutado_Real = $("#select_Ejecutado_Real").val();
				var CNC = data.CNC;
				$('#CNC').attr('readonly', true);
				cnc(CNC);
				$("#Categoria_CNC").val(data.Categoria_CNC).change();
				$("#Observaciones_CNC").val(data.Observaciones_CNC).change();
				if ((Ejecutado_Real - Compromiso) >= 0) {
					var PAC = 1;
				} else {
					var PAC = 0;
				};
				var texto = $("#modal-body-texto-CNC").html("Indique la Categoría y Causa de No Cumplimiento de la actividad: " + data.Actividad);
				//console.log(Compromiso, Ejecutado_Real);
				if (PAC == 0) {
					$("#modal_CNC").modal("show");
				} else {
					//limpiar_datos();
					recargarTabla("");
				}
			});
		}

		var guardar_CNC = function() {
		  $("#btn_guardar_CNC").on("click", function() {
				var db = document.getElementById('baseDatos').value;

		    var Id = $("#Id").serialize();
		    var semana = $("#semana").serialize();
		    var opcion = $("#opcion").serialize();
		    var Descripcion = $("#select_Descripcion").serialize();
		    var Ubicacion = "Ubicacion="/*$("#select_Ubicacion").serialize()*/;
		    var Sub_Contratista = $("#select_Sub_Contratista").serialize();
		    var Responsable_AIA = $("#select_Responsable_AIA").serialize();
		    var Empresa = "Empresa=" /*$("#select_Empresa").serialize()*/;
		    var Unidad = $("#select_Unidad").serialize();
		    var Compromiso = $("#select_Compromiso").serialize();
		    var Cantidad_Sugerida = $("#select_Cantidad_Sugerida").serialize();
		    var Ejecutado_Real = $("#select_Ejecutado_Real").serialize();
		    var Categoria_CNC = $("#Categoria_CNC").serialize();
		    var CNC = $("#CNC").serialize();
		    var Observaciones_CNC = $("#Observaciones_CNC").serialize();
		    var Rendimientos = $("#select_Rendimientos").serialize();

		    if ($("#Categoria_CNC").val() != '' || $("#CNC").val() != '') {
		      frm = Id + "&" + semana + "&" + opcion + "&" + Descripcion + "&" + Ubicacion + "&" + Sub_Contratista + "&" + Responsable_AIA + "&" + Empresa + "&" + Unidad + "&" + Cantidad_Sugerida + "&" + Compromiso + "&" + Ejecutado_Real + "&" + Rendimientos + "&" + Categoria_CNC + "&" + CNC + "&" + Observaciones_CNC;
		      $.ajax({
		        method: "POST",
		        url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		        contenttype: "charset=utf-8",
		        data: frm,
		      }).done(function(info) {
						//limpiar_datos();
						recargarTabla("");
						$("#modal_CNC").modal("hide");
		      });
		    } else {
		      var texto = $("#error-CNC").text("Error: Debe definir la Categoría");
		    }
		  });
		}

		var cnc = function(CNC) {
		  $('#Categoria_CNC').on('change', function() {
		    var categoria = $("#Categoria_CNC").val();
				var opcion = "CNC";
		    //console.log(categoria);
		    if (categoria === "") {
		      $('#CNC').attr('readonly', true);
		      $('#CNC').html("<option value=''></option>");
		    } else {
		      $.ajax({
		        method: "POST",
		        url: "../programacion_semanal/guardar_CNC.php?db=login",
		        contenttype: "charset=utf-8",
		        data: {
		          "categoria": categoria,
		          "opcion": opcion
		        },
		        success: function(a) {
		          $('#CNC').html(a);
		          $("#CNC option[value='" + CNC + "']").attr('selected', true);
		          $('#CNC').attr('readonly', false);
		        }
		      });
		    }
		  });
		}

		/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function(informacion) {
		  var texto = "",
		    color = "";
		  if (informacion.respuesta == "BIEN") {
		    texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
		    color = "#379911";
		  }
		  if (informacion.respuesta == "ERROR") {
		    texto = "<strong>Error</strong>, no se ejecutó la consulta.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "EXISTE") {
		    texto = "<strong>Información!</strong> el usuario ya existe.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "VACIO") {
		    texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "CONFIRMAR") {
		    texto = "<strong>Advertencia!</strong> Por favor confirmar correctamente la dirección de correo.";
		    color = "#C9302C";
		  }
		  if (informacion.respuesta == "BIEN") {
		    $("#cuadro2").slideUp("slow");
		    $("#cuadro1").slideDown("slow");
		    $("#mensajeActualizacion").html(texto).css({
		      "color": color
		    });
		    $("#mensajeActualizacion").fadeOut(5000, function() {
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

		/*Carga los datos del formulario de tasas de producción cuando se presiona el botón "btn_rendimientos" */
		var obtener_id_rendimientos = function(tbody, table) {
		  $(tbody).on("click", "#btn_rendimientos", function() {
		    var data = table.row($(this).parents("tr")).data();
		    $("#opcion").val("rendimientos");
		    $("#rendimientos_unidad").val(data.Unidad);
		    var Compromiso = $("#select_Compromiso").val();
		    //console.log(Compromiso);
		    $("#rendimientos_Compromiso").val($("#select_Compromiso").val());
		    var Rendimientos = $("#select_Rendimientos").val();
		    //console.log(Rendimientos);
		    if (!Rendimientos) {
		      Rendimientos = ";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		    }
		    var Rendimientos_array = Rendimientos.split(";");
		    //console.log(Rendimientos);
		    var j = 0;
		    for (i = 1; i < 8; i++) {
		      $("#rendimientos_real_" + i).val(Rendimientos_array[j]).change();
		      $("#rendimientos_recursos_of_" + i).val(Rendimientos_array[j + 1]).change();
		      $("#rendimientos_recursos_ay_" + i).val(Rendimientos_array[j + 2]).change();
		      $("#rendimientos_recursos_horas_" + i).val(Rendimientos_array[j + 3]).change();
		      if (Rendimientos_array[j] == '' || Rendimientos_array[j] == 'N' || Rendimientos_array[j] == 'n') {
		        $("#rendimientos_recursos_of_" + i + ",#rendimientos_recursos_ay_" + i + ",#rendimientos_recursos_horas_" + i).attr('readonly', true);
		        $("#rendimientos_recursos_of_" + i).val('').change();
		        $("#rendimientos_recursos_ay_" + i).val('').change();
		        $("#rendimientos_recursos_horas_" + i).val('').change();
		      } else {
		        $("#rendimientos_recursos_of_" + i + ",#rendimientos_recursos_ay_" + i + ",#rendimientos_recursos_horas_" + i).attr('readonly', false);
		      }
		      j += 4;
		    }
		    $("#rendimientos_oficial_cuadrilla_tipica").val(Rendimientos_array[28]).change();
		    $("#rendimientos_ayudante_cuadrilla_tipica").val(Rendimientos_array[29]).change();
		    cargar_costos_cuadrillas();
		    sumar_rendimiento();
		    //sumar_rendimiento();
		    var texto = $("#modal-body-texto-rendimientos").html("Rendimientos de la actividad: " + data.Actividad + " - Código:" + data.codigo_actividad);
		  });
		}

		/*Función que carga los costos de hora-oficial y hora-ayudante en el formulario de tasas de producción*/
		var cargar_costos_cuadrillas = function() {
		  var costo_hora_oficial = '',
		    costo_hora_ayudante = '',
		    factor_oficial = '',
		    opcion = "cargar_costos_cuadrilla",
				db = document.getElementById('baseDatos').value;
		  $.ajax({
		    method: "POST",
		    url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		    contenttype: "charset=utf-8",
		    data: {
		      "opcion": opcion
		    }
		  }).done(function(info) {
		    var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
		    costo_hora_oficial = json_info[0];
		    costo_hora_ayudante = json_info[1];
		    if (costo_hora_oficial == 0 || costo_hora_ayudante == 0) {
		      factor_oficial = 1;
		    } else {
		      factor_oficial = (costo_hora_oficial / costo_hora_ayudante).toFixed(2);
		    }
		    //console.log(costo_hora_oficial, costo_hora_ayudante);
		    $("#rendimientos_costo_hora_oficial").val(costo_hora_oficial).change();
		    $("#rendimientos_costo_hora_ayudante").val(costo_hora_ayudante).change();
		    $("#rendimientos_factor_oficial").val(factor_oficial).change();
		    //console.log(factor_oficial);
		    actualizar_tabla_rendimientos();
		  });
		}

		/*Función que regresa el resultado de la suma de cantidades ejecutadas en cada día de la semana, y regresa el arreglo con todos los datos de rendimientos, el cual se carga en el input #select_Rendimientos*/
		var sumar_rendimiento = function() {
			var db = document.getElementById('baseDatos').value;
		  if ($("#rendimientos_Compromiso").val() == '') {
		    var rendimiento_teorico = 0;
		    $("#rendimientos_Compromiso").val(0);
		  }
		  actualizar_tabla_rendimientos();
		  $('#btn_guardar_rendimientos').click(function(e) {
		    e.preventDefault();
		    e.stopImmediatePropagation();

				document.getElementById('opcion').value = "modificar";

		    $("#modalrendimientos").modal("hide");
				$("#modalrendimientos #cuadroModal").scrollTop(0);
		    rendimiento_real_suma = 0;
		    contador = 0;
		    for (i = 1; i < 8; i++) {
		      if ($("#rendimientos_real_" + i).val() == '' || $("#rendimientos_real_" + i).val() == 'N' || $("#rendimientos_real_" + i).val() == 'n') {
		        contador += 1;
		      } else {
		        rendimiento_real_suma += parseFloat($("#rendimientos_real_" + i).val());
		        contador += 0;
		      }
		    }
		    if (contador == 7) {
		      $('#select_Ejecutado_Real').val('').change();
		    } else {
		      $('#select_Ejecutado_Real').val(rendimiento_real_suma).change();
		    }
		    $('#select_Compromiso').val($("#rendimientos_Compromiso").val()).change();
		    array_Rendimientos = $("#rendimientos_real_1").val() + ";" + $("#rendimientos_recursos_of_1").val() + ";" + $("#rendimientos_recursos_ay_1").val() + ";" + $("#rendimientos_recursos_horas_1").val() + ";" + $("#rendimientos_real_2").val() + ";" + $("#rendimientos_recursos_of_2").val() + ";" + $("#rendimientos_recursos_ay_2").val() + ";" + $("#rendimientos_recursos_horas_2").val() + ";" + $("#rendimientos_real_3").val() + ";" + $("#rendimientos_recursos_of_3").val() + ";" + $("#rendimientos_recursos_ay_3").val() + ";" + $("#rendimientos_recursos_horas_3").val() + ";" + $("#rendimientos_real_4").val() + ";" + $("#rendimientos_recursos_of_4").val() + ";" + $("#rendimientos_recursos_ay_4").val() + ";" + $("#rendimientos_recursos_horas_4").val() + ";" + $("#rendimientos_real_5").val() + ";" + $("#rendimientos_recursos_of_5").val() + ";" + $("#rendimientos_recursos_ay_5").val() + ";" + $("#rendimientos_recursos_horas_5").val() + ";" + $("#rendimientos_real_6").val() + ";" + $("#rendimientos_recursos_of_6").val() + ";" + $("#rendimientos_recursos_ay_6").val() + ";" + $("#rendimientos_recursos_horas_6").val() + ";" + $("#rendimientos_real_7").val() + ";" + $("#rendimientos_recursos_of_7").val() + ";" + $("#rendimientos_recursos_ay_7").val() + ";" + $("#rendimientos_recursos_horas_7").val() + ";" + $("#rendimientos_oficial_cuadrilla_tipica").val() + ";" + $("#rendimientos_ayudante_cuadrilla_tipica").val();
		    //console.log(array_Rendimientos);
		    $('#select_Rendimientos').val(array_Rendimientos);
		    var costo_hora_oficial = $("#rendimientos_costo_hora_oficial").val(),
		      costo_hora_ayudante = $("#rendimientos_costo_hora_ayudante").val(),
		      opcion = "guardar_costos_cuadrilla";
		    //console.log(opcion);
		    if (costo_hora_oficial == "" || costo_hora_oficial == null) {
		      costo_hora_oficial = "NULL";
		    }
		    if (costo_hora_ayudante == "" || costo_hora_ayudante == null) {
		      costo_hora_ayudante = "NULL";
		    }
		    $.ajax({
		      method: "POST",
		      url: "../programacion_semanal/guardar_programacion_semanal.php?db="+db,
		      contenttype: "charset=utf-8",
		      data: {
		        "opcion": opcion,
		        "costo_hora_oficial": costo_hora_oficial,
		        "costo_hora_ayudante": costo_hora_ayudante
		      }
		    }).done(function(info) {
		      //console.log(info);
		    });
		    $("#rendimientos_costo_hora_oficial").val('').change();
		    $("#rendimientos_costo_hora_ayudante").val('').change();
		    $("#rendimientos_factor_oficial").val('').change();
		  });
		}

		/*función que recalcula los indicadores de tasas de producción en el formulario*/
		var actualizar_tabla_rendimientos = function() {
		  var costo_hora_oficial = $("#rendimientos_costo_hora_oficial").val(),
		    costo_hora_ayudante = $("#rendimientos_costo_hora_ayudante").val();
		  if (costo_hora_oficial == "" || costo_hora_oficial == null || costo_hora_oficial == 0) {
		    factor_oficial = 1;
		  } else if (costo_hora_ayudante == "" || costo_hora_ayudante == null || costo_hora_ayudante == 0) {
		    factor_oficial = 1;
		  } else {
		    factor_oficial = costo_hora_oficial / costo_hora_ayudante;
		  }
		  $("#rendimientos_factor_oficial").val(factor_oficial).change();
		  var array_Rendimientos = null;
		  var total = 0;
		  var unidad = $("#rendimientos_unidad").val();
		  var rendimiento_teorico = parseFloat($("#rendimientos_Compromiso").val());
		  var oficiales_teorico = parseFloat($("#rendimientos_oficial_cuadrilla_tipica").val());
		  var ayudantes_teorico = parseFloat($("#rendimientos_ayudante_cuadrilla_tipica").val());
		  //console.logoficiales_teorico+"-"+ayudantes_teorico);
		  var rendimiento_real_array = [0, 0, 0, 0, 0, 0, 0];
		  var contador_dias = 0;
		  var rendimiento_real_suma = 0;
		  for (i = 1; i < 8; i++) {
		    if ($("#rendimientos_real_" + i).val() == '') {
		      rendimiento_real_array[i - 1] = 0;
		      $("#rendimientos_recursos_of_" + i + ",#rendimientos_recursos_ay_" + i + ",#rendimientos_recursos_horas_" + i).attr('readonly', true);
		      contador_dias = contador_dias + 1;
		    } else if ($("#rendimientos_real_" + i).val() == 'N' || $("#rendimientos_real_" + i).val() == 'n') {
		      rendimiento_real_array[i - 1] = 0;
		      $("#rendimientos_recursos_of_" + i + ",#rendimientos_recursos_ay_" + i + ",#rendimientos_recursos_horas_" + i).attr('readonly', true);
		      $("#rendimientos_recursos_of_" + i).val('').change();
		      $("#rendimientos_recursos_ay_" + i).val('').change();
		      $("#rendimientos_recursos_horas_" + i).val('').change();
		      contador_dias = contador_dias;
		    } else {
		      rendimiento_real_array[i - 1] = parseFloat($("#rendimientos_real_" + i).val());
		      $("#rendimientos_recursos_of_" + i + ",#rendimientos_recursos_ay_" + i + ",#rendimientos_recursos_horas_" + i).attr('readonly', false);
		      contador_dias = contador_dias + 1;
		    }
		    rendimiento_real_suma += rendimiento_real_array[i - 1];
		  }
		  var rendimiento_recursos_of_array = [0, 0, 0, 0, 0, 0, 0];
		  var contador_dias1 = 0;
		  var rendimiento_recursos_of_suma = 0;
		  for (i = 1; i < 8; i++) {
		    if ($("#rendimientos_real_" + i).val() == 'N' || $("#rendimientos_real_" + i).val() == 'n') {
		      rendimiento_recursos_of_array[i - 1] = 0;
		    } else if ($("#rendimientos_recursos_of_" + i).val() == '') {
		      rendimiento_recursos_of_array[i - 1] = 0;
		    } else {
		      rendimiento_recursos_of_array[i - 1] = parseFloat($("#rendimientos_recursos_of_" + i).val());
		    }
		    rendimiento_recursos_of_suma += rendimiento_recursos_of_array[i - 1];
		  }
		  var rendimiento_recursos_ay_array = [0, 0, 0, 0, 0, 0, 0];
		  var contador_dias2 = 0;
		  var rendimiento_recursos_ay_suma = 0;
		  for (i = 1; i < 8; i++) {
		    if ($("#rendimientos_real_" + i).val() == 'N' || $("#rendimientos_real_" + i).val() == 'n') {
		      rendimiento_recursos_ay_array[i - 1] = 0;
		    } else if ($("#rendimientos_recursos_ay_" + i).val() == '') {
		      rendimiento_recursos_ay_array[i - 1] = 0;
		    } else {
		      rendimiento_recursos_ay_array[i - 1] = parseFloat($("#rendimientos_recursos_ay_" + i).val());
		    }
		    rendimiento_recursos_ay_suma += rendimiento_recursos_ay_array[i - 1];
		  }
		  var rendimiento_recursos_horas_array = [0, 0, 0, 0, 0, 0, 0];
		  var contador_dias2 = 0;
		  var rendimiento_recursos_horas_suma = 0;
		  for (i = 1; i < 8; i++) {
		    if ($("#rendimientos_real_" + i).val() == 'N' || $("#rendimientos_real_" + i).val() == 'n') {
		      rendimiento_recursos_horas_array[i - 1] = 0;
		    } else if ($("#rendimientos_recursos_horas_" + i).val() == '') {
		      rendimiento_recursos_horas_array[i - 1] = 0;
		    } else {
		      rendimiento_recursos_horas_array[i - 1] = parseFloat($("#rendimientos_recursos_horas_" + i).val());
		    }
		    rendimiento_recursos_horas_suma += rendimiento_recursos_horas_array[i - 1];
		  }
		  var cuadrilla_tipica_oficiales = ((rendimiento_recursos_of_suma / contador_dias) / $("#rendimientos_oficial_cuadrilla_tipica").val());
		  var cuadrilla_tipica_ayudantes = ((rendimiento_recursos_ay_suma / contador_dias) / $("#rendimientos_ayudante_cuadrilla_tipica").val());
		  var eficiencia = ((rendimiento_real_suma / rendimiento_teorico) * 100).toFixed(2);
		  var rendimiento = ((rendimiento_real_suma / contador_dias) / Math.ceil(cuadrilla_tipica_oficiales)).toFixed(2);
		  var rendimientos_of_prom_semanal = (rendimiento_recursos_of_suma / contador_dias).toFixed(2);
		  var rendimientos_ay_prom_semanal = (rendimiento_recursos_ay_suma / contador_dias).toFixed(2);
		  var rendimientos_horas_prom_semanal = (rendimiento_recursos_horas_suma / contador_dias).toFixed(1);
		  if (rendimiento_recursos_horas_suma == "" || rendimiento_recursos_horas_suma == null || rendimiento_real_suma == "" || rendimiento_real_suma == null) {
		    var consumo_oficial = (0).toFixed(2);
		    var consumo_ayudante = (0).toFixed(2);
		    var horas_oficial = (0).toFixed(2);
		    var horas_ayudante = (0).toFixed(2);
		  } else {
		    var consumo_oficial = ((rendimientos_horas_prom_semanal * rendimiento_recursos_of_suma) / rendimiento_real_suma).toFixed(2);
		    var consumo_ayudante = ((rendimientos_horas_prom_semanal * rendimiento_recursos_ay_suma) / rendimiento_real_suma).toFixed(2);
		    var horas_oficial = (rendimiento_recursos_horas_suma * rendimiento_recursos_of_suma).toFixed(2);
		    var horas_ayudante = (rendimiento_recursos_horas_suma * rendimiento_recursos_ay_suma).toFixed(2);
		  }
		  //console.log(rendimiento_recursos_horas_suma + "," + rendimiento_recursos_of_suma + "," + horas_oficial);
		  //alert(total);
		  $('#rendimientos_eficiencia_semanal').val(eficiencia + "%").change();
		  $('#rendimientos_rendimiento_semanal').val(rendimiento + " " + unidad + "/Cuadrilla-día").change();
		  $('#rendimientos_of_prom_semanal').val(rendimientos_of_prom_semanal + " Of/día").change();
		  $('#rendimientos_ay_prom_semanal').val(rendimientos_ay_prom_semanal + " Ay/día").change();
		  $('#rendimientos_horas_prom_semanal').val(rendimientos_horas_prom_semanal + " Horas/día").change();
		  $('#rendimientos_consumo_hora_oficial').val(consumo_oficial + " H-Of/" + unidad).change();
		  $('#rendimientos_consumo_hora_ayudante').val(consumo_ayudante + " H-Ay/" + unidad).change();
		  $('#rendimientos_suma_horas_oficial_semanal').val((horas_oficial / contador_dias).toFixed(2) + " H-Of/Semana").change();
		  $('#rendimientos_suma_horas_ayudante_semanal').val((horas_ayudante / contador_dias).toFixed(2) + " H-Ay/Semana").change();
		  $('#rendimientos_suma_cantidad_semanal').val(rendimiento_real_suma.toFixed(2) + " " + unidad + "/Semana").change();
		  $('#rendimientos_suma_horas_laboradas_semanal').val(rendimiento_recursos_horas_suma + " Horas" + "/Semana").change();
		  if (cuadrilla_tipica_oficiales > cuadrilla_tipica_ayudantes) {
		    var exceso_deficit_ayudantes = "Déficit de " + Math.ceil((cuadrilla_tipica_oficiales - cuadrilla_tipica_ayudantes) * $("#rendimientos_ayudante_cuadrilla_tipica").val()) + " Ayudantes";
		  } else if (cuadrilla_tipica_oficiales < cuadrilla_tipica_ayudantes) {
		    var exceso_deficit_ayudantes = "Exceso de " + Math.ceil((cuadrilla_tipica_ayudantes - cuadrilla_tipica_oficiales) * $("#rendimientos_ayudante_cuadrilla_tipica").val()) + " Ayudantes";
		  } else {
		    var exceso_deficit_ayudantes = "";
		  }
		  $('#rendimientos_cuadrillas_tipicas_prom_semanal').val(Math.ceil(cuadrilla_tipica_oficiales) + " Cuadrillas de " + $("#rendimientos_oficial_cuadrilla_tipica").val() + " Of + " + $("#rendimientos_ayudante_cuadrilla_tipica").val() + " Ay").change();
		  $('#rendimientos_cuadrillas_tipicas_exceso_deficit_semanal').val(exceso_deficit_ayudantes);
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
				var opcionEliminarDuplicar = document.getElementById('opcion_Eliminar_Duplicar');
				if(opcionEliminarDuplicar.value == "eliminar"){
					obtener_id_eliminar("#dt_cliente tbody", table);
					opcionEliminarDuplicar.value='';
				}else if(opcionEliminarDuplicar.value == "duplicar"){
					obtener_id_duplicar("#dt_cliente tbody", table);
					opcionEliminarDuplicar.value='';
				}else{
					opcionEliminarDuplicar.value='';
				}
				obtener_id_generar_CNC("#dt_cliente tbody", table);
				obtener_id_rendimientos("#dt_cliente tbody", table);
		  }
		  $('#dt_cliente').on('draw.dt', function() {
		    $('.dataTables_scrollBody').scrollTop(posicion);
		  });
		}

		var ind_compromisos_semana = function(nombre) {
		  $("#compromisos_semana").empty();
		  var names = ['Tareas Críticas Comprometidas', 'Tareas No Críticas Comprometidas', 'Tareas Atrasadas Críticas Comprometidas', 'Tareas Atrasadas No Críticas Comprometidas', 'Tareas Comprometidas Sin Liberar Restricciones'];
		  var opcion = "ind_compromisos",
		    nombre = "general";
		  //console.log(ultimas_semanas);
		  /*if($('#clase_PAC').val()=="subcontratista"){
		  		$('#div_ind_compromisos').css('display', 'none');
		  }else{
		  		$('#div_ind_compromisos').css('display', 'block');
		  }*/
		  var semana = document.getElementById('semana').value;
		  var db = document.getElementById('baseDatos').value;
			console.log(db);
		  //console.logopcion, nombre, semana);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "../programacion_semanal/guardar_programacion_semanal.php?db=" + db,
		    dataType: "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "semana": semana
		    }
		  }).responseText;
		  var data = (typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData);
		  //console.logdata);
		  var dataSet = anychart.data.set(data[0]);
		  //var palette = anychart.palettes.distinctColors().items(['rgb(191,215,48)', 'rgb(211,84,0)', 'rgb(52,73,94)', 'rgb(55,86,54)', '#455a64', '#96a6a6', '#dd2c00', '#00838f', '#00bfa5', '#ffa000']);
		  var makeBarWithBar = function(gauge, radius, i, width, without_stroke) {
		    var stroke = '1 #e5e4e4';
		    if (data[1][i] == "NA") {
		      data[1][i] = 0;
		      label = "NA";
		    } else {
		      data[1][i] = data[1][i] * 100;
		      label = data[1][i].toFixed(0) + "%";
		    }
		    if (without_stroke) {
		      stroke = null;
		      gauge.label(i).text('<b><span style="color: black; font-size: 10px;" >' + names[i] + ", " + label + '</span></b>') // color: #7c868e
		        .useHtml(true);
		      gauge.label(i).hAlign('center').vAlign('middle').anchor('right-center').padding(0, 10).height(width / 2 + '%').offsetY(radius + '%').offsetX(0);
		      gauge.tooltip().format("{%value}%");
		    }
		    if (data[1][i] < 70) {
		      color_abc = "rgb(231, 76, 60)";
		    } else if (data[1][i] >= 70 && data[1][i] < 95) {
		      color_abc = "rgb(241, 196, 15)";
		    } else if (data[1][i] >= 95) {
		      color_abc = "rgb(29, 131, 72)";
		    }
		    if (data[1][4] > 30) {
		      color_d = "rgb(231, 76, 60)";
		    } else if (data[1][4] <= 30 && data[1][4] > 5) {
		      color_d = "rgb(241, 196, 15)";
		    } else if (data[1][4] <= 5) {
		      color_d = "rgb(29, 131, 72)";
		    }
		    gauge.bar(i).dataIndex(i).radius(radius).width(width * 1.03).fill(color_abc).stroke(stroke).zIndex(4);
		    gauge.bar(4).dataIndex(4).radius(radius).width(width * 1.03).fill(color_d).stroke(stroke).zIndex(4);
		    gauge.bar(i + 100).dataIndex(5).radius(radius).width(width).fill('RGB(202, 207, 210)').stroke(stroke).zIndex(3);
		    return gauge.bar(i);
		  };
		  anychart.onDocumentReady(function() {
		    var gauge = anychart.gauges.circular();
		    gauge.data(dataSet);
		    gauge.fill('#fff').stroke(null).padding(0).margin(50).startAngle(0).sweepAngle(270);
		    var axis = gauge.axis().radius(135).width(1).fill(null);
		    axis.scale().minimum(0).maximum(100).ticks({
		      interval: 5
		    }).minorTicks({
		      interval: 1
		    });
		    axis.labels().enabled(true).fontColor('black').format("{%value}%");
		    axis.ticks().enabled(false);
		    axis.minorTicks().enabled(false);
		    makeBarWithBar(gauge, 100, 0, 15, true);
		    makeBarWithBar(gauge, 80, 1, 15, true);
		    makeBarWithBar(gauge, 60, 2, 15, true);
		    makeBarWithBar(gauge, 40, 3, 15, true);
		    makeBarWithBar(gauge, 20, 4, 15, true);
		    //makeBarWithBar(gauge, 20, 4, 17, true);
		    //gauge.margin(50);
		    gauge.title().text('<span style="color:black; font-size: 16px; font-weight: bold">Indicadores Actividades Comprometidas</span>' + '<br/><span style="color:black; font-size: 14px;">(Presente Semana)</span><br/><br/>').useHtml(true);
		    gauge.title().enabled(true).hAlign('center').padding(0).margin([0, 0, 20, 0]);
		    gauge.container('compromisos_semana');
		    gauge.draw();
		    // remap data
		    var view = dataSet.mapAs();
		    // set listener on chart
		    gauge.listen(
		      // listener type
		      "pointclick",
		      // function, if listener triggers
		      function(e) {
		        view.set(e.pointIndex, // get index of clicked column
		          "x", // get parameter to update
		          //view.get(e.pointIndex, "x") // parameter updating
		        );
		      });
		  });
		}

		var descargarCompromisosSemana = function() {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
			// console.log(frm);

			$.ajax({
				method: "POST",
				url: "../programacion_semanal/descargarCompromisos.php",
				contenttype:"charset=utf-8",
				data: {"db":db, "semana":semana},
			}).done( function( info ){
				var json_info = (typeof info === 'string' ? JSON.parse(info) : info);
				console.log(json_info);
				window.location.href = json_info;
			});
		}

		function wait(ms){
			 var start = new Date().getTime();
			 var end = start;
			 while(end < start + ms) {
				 end = new Date().getTime();
			}
		}

		const formatDate = (date) => {
		  let d = new Date(date);
		  let month = (d.getMonth() + 1).toString();
		  let day = d.getDate().toString();
		  let year = d.getFullYear();
		  if (month.length < 2) {
		    month = '0' + month;
		  }
		  if (day.length < 2) {
		    day = '0' + day;
		  }
		  return [year, month, day].join('-');
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
