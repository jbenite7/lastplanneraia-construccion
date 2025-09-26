<!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "cuadro2", la cual permanecerá oculta hasta que se presione el botón editar en alguna fila de la datatable -->
	<div class="row">
		<div id="cuadro2" class="col-sm-12 col-md-12 col-lg-12 ">
			<form class="form form-horizontal" action="" method="POST">
				<div class="form-group">
					<h3 class="col-sm-offset-2 col-sm-8 text-center">					
					Formulario de Registro de Usuarios</h3>
				</div>
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id" name="Id" value="0">
				<input type="hidden" id="opcion" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
				<div class="form-group">
					<label for="Id1" class="col-sm-2 control-label">Id</label>
					<div class="col-sm-8"><input id="Id1" name="Id1" type="text" class="form-control"  autofocus></div>				
				</div>     
				<div class="form-group">
					<label for="Actividad" class="col-sm-2 control-label">Actividad</label>
					<div class="col-sm-8"><input id="Actividad" name="Actividad" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Fecha_Inicio" class="col-sm-2 control-label">Fecha Inicio</label>
					<div class="col-sm-8"><input id="Fecha_Inicio" name="Fecha_Inicio" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Fecha_Fin" class="col-sm-2 control-label">Fecha Fin</label>
					<div class="col-sm-8"><input id="Fecha_Fin" name="Fecha_Fin" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Critica" class="col-sm-2 control-label">Crítica</label>
					<div class="col-sm-8"><input id="Critica" name="Critica" type="text" class="form-control" maxlength="1"></div>
				</div>
				<div class="form-group">
					<label for="Ejecutado" class="col-sm-2 control-label">Ejecutado</label>
					<div class="col-sm-8"><input id="Ejecutado" name="Ejecutado" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Estado" class="col-sm-2 control-label">Estado</label>
					<div class="col-sm-8"><input id="Estado" name="Estado" type="text" class="form-control" ></div>
				</div>
                
                <!--Se crean los botones Guardar y Listar-->
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-8">
						<input id="" type="submit" class="btn btn-primary" value="Guardar">
						<input id="btn_listar" type="button" class="btn btn-primary" value="Listar">
					</div>
				</div>
			</form>
            
            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class="col-sm-offset-2 col-sm-8">
				<p class="mensaje"></p>
			</div>
			
		</div>
	</div>
    
        <!-- -->
	<div class="row">
		<div id="cuadro3" class="cuadro3 col-lg-12 ">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crean los botones Guardar y Listar-->
				<div class="form-group">
					<div class="col-sm-offset-1 col-sm-8">
						<input id="btn_nuevo" type="button" class="btn btn-success btn-sm" value="Nuevo">
						<input id="btn_editar" type="button" class="btn btn-primary btn-sm" value="Editar">
                        <input id="btn_eliminar" type="button" class="btn btn-danger btn-sm" value="Eliminar">
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
	<div class="row">
		<div id="cuadro1" class="col-sm-12 col-md-12 col-lg-12">
			<div class="col-sm-offset-2 col-sm-8">
				<h3 class="text-center"> <small class="mensaje"></small></h3>
			</div>
			<div class="table-responsive table-condensed table-bordered col-sm-12">		
				<table id="dt_cliente" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
                            <th></th>
							<th>Id</th>
							<th>Actividad</th>
							<th>Título</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Crítica</th>
                            <th>Ejecutado</th>
                            <th>Estado</th>
                            <th>Estado Restricciones</th>
							<th></th>											
						</tr>
					</thead>					
				</table>
			</div>			
		</div>		
	</div>
	
    <!--Se crea un div. Acá se agregará un form llamado "frmEliminarUsuario", el cual permanecerá oculto. En este form se crean 2 inputs que contienen el id del registro que se va a eliminar, y el switch que dice si hay que eliminar  -->  
    <div>     
		<form id="frmEliminarUsuario" action="" method="POST">
			<input type="hidden" id="Id" name="Id" value="" readonly>
			<input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>
            
            
            
			<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
			<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="modalEliminarLabel">Eliminar Usuario</h4>
						</div>
						<div class="modal-body">							
							¿Está seguro de eliminar al usuario?<strong data-name=""></strong>
						</div>
						<div class="modal-footer">
							<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
						</div>
					</div>
				</div>
			</div>
			<!-- Modal -->
			<!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
			<div class="modal_nueva_sem modal fade" id="modal_nueva_sem" tabindex="-1" role="dialog" aria-labelledby="modal_nueva_semLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEliminarLabel">Crear Semana LPS</h4>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">							
                            <div class="row">
                                    <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
                                        <form class="form form-horizontal" action="" method="POST">
                                            <!--<div class="form-group">
                                                <h3 class="col-sm-offset-2 col-sm-8 text-center"></h3>
                                            </div>
-->
                                            <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
                                            <input type="hidden" id="Id" name="Id" value="0">
                                            <input type="hidden" id="opcion" name="opcion" value="registrar">

                                            <div class="form-group" style="width:100%;">
                                                <label for="inicio_sem" class="col-sm-12 control-label">Seleccione Fecha de Inicio de la Semana</label>
                                                <div class="col-sm-6"><input id="inicio_sem" name="inicio_sem" class="form-control" type="text" readonly></div>
                                            </div>


                                            <!--Se crean los botones Guardar y Listar-->
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-8">
                                                    <input id="btn_guardar_nueva_sem" type="button" class="btn btn-primary" data-dismiss="modal" value="Guardar">
                                                    <input id="btn_cancelar_nueva_sem" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" >
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
<!--						<div class="modal-footer">
							<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
						</div>-->
					</div>
				</div>
			</div>
			<!-- Modal -->
		</form>
	</div>