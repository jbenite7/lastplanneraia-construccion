<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script que va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="info_subcontratistas">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de profesionales, el cual permanecerá oculto hasta que se presione el botón "Registrar Sub-Contratista" -->
	<div class="row formularioRegistro" id="formularioRegistro" style="overflow-y: scroll">
	  <div id="formulario_nuevo" class="formulario_nuevo col-sm-12 col-md-12 col-lg-12">
	    <form class="form form-horizontal" action="" method="POST">
	      <div class="form-group">
	        <h3 class="col-sm-offset-2 col-sm-12 text-center"> Formulario de Registro de Nuevos Sub-Contratistas</h3>
	      </div>
	      <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
	      <input type="hidden" id="Id" name="Id" value="">
	      <input type="hidden" id="opcion" name="opcion" value="registrar">
	      <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
	      <div class="form-group col-sm-6">
	        <label for="Subcontratista" class="col-sm-4 control-label">Nombre Empresa</label>
	        <div class="col-sm-12"><input id="Subcontratista" name="Subcontratista" type="text" class="form-control"></div>
	      </div>
	      <div class="form-group col-sm-6">
	        <label for="Correo" class="col-sm-4 control-label">Correo de Contacto</label>
	        <div class="col-sm-12"><input id="Correo" name="Correo" type="text" class="form-control"></div>
	      </div>
	      <div class="form-group col-sm-6">
	        <label for="Confirmar_Correo" class="col-sm-4 control-label">Confirmar Correo</label>
	        <div class="col-sm-12"><input id="Confirmar_Correo" name="Confirmar_Correo" type="text" class="form-control"></div>
	      </div>
	      <div class="form-group col-sm-6">
	        <label for="NIT" class="col-sm-4 control-label">NIT (sin puntos o guiones)</label>
	        <div class="col-sm-12"><input id="NIT" name="NIT" type="number" step="1" class="form-control" onkeyup="quitar_guion()"></div>
	      </div>
	      <div class="form-group col-sm-6">
	        <label for="alcance" class="col-sm-4 control-label">Alcance (objeto del contrato)</label>
	        <div class="col-sm-12"><input id="alcance" name="alcance" type="text" class="form-control"></div>
	      </div>
	      <div class="form-group col-sm-6">
	        <label for="tipo_proveedor" class="col-sm-4 control-label">Tipo de Proveedor</label>
	        <div class="col-sm-12">
	          <select id="tipo_proveedor" name="tipo_proveedor" class="form-control">
	            <option value="Mano de Obra">Mano de Obra</option>
	            <option value="Suministro e Instalación">Suministro e Instalación</option>
	            <option value="Suministro de Materiales, Herramientas o Equipos">Suministro de Materiales, Herramientas o Equipos</option>
	          </select>
	        </div>
	      </div>
	      <!--Se crean los botones Guardar y Listar-->
	      <div class="form-group">
	        <div class="col-sm-offset-2 col-sm-8">
	          <input id="" type="submit" class="btn btn-primary" value="Guardar">
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
			<table id="dt_cliente" class="dt_infoGeneral table table-bordered table-hover table-responsive-sm table-sm" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th></th>
						<th>Id</th>
						<th>Sub-Contratista</th>
						<th>Correo de Contacto</th>
						<th>NIT</th>
						<th>Alcance</th>
						<th>Tipo de Proveedor</th>
						<th>Activo</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
	  <!--Se crea un div. Acá se agregará un form llamado "frmEliminarUsuario", el cual permanecerá oculto. En este form se crean 2 inputs que contienen el id del registro que se va a eliminar, y el switch que dice si hay que eliminar  -->
	  <div>
	    <form id="frmEliminarUsuario" action="" method="POST">
	      <input type="hidden" id="Id" name="Id" value="" readonly>
	      <input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>
	      <!-- Se crea el Modal que solicita la confirmación de eliminar un profesional o no -->
	      <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
	        <div class="modal-dialog" role="document">
	          <div class="modal-content">
	            <div class="modal-header">
	              <h4 class="modal-title" id="modalEliminarLabel">Eliminar Sub-Contratista</h4>
	              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	            </div>
	            <div class="modal-body">
	              <p class="modal-body-texto-eliminar" id="modal-body-texto-eliminar"></p>
	            </div>
	            <div class="modal-footer">
	              <button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" data-toggle="modal">Aceptar</button>
	              <button type="button" class="btn btn-default" data-dismiss="modal" onClick='listar()'>Cancelar</button>
	            </div>
	          </div>
	        </div>
	      </div>
	    </form>
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
      listar();
      guardar();
      eliminar();
		}

		/* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
		$("#btn_listar").on("click", function() {
		  recargarTabla("listar");
		  limpiar_datos();
		  $("#formulario_nuevo").slideUp("slow");
		  $("#cuadroTabla").slideDown("slow");
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

		/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$("form").on("submit", function(e){
				e.preventDefault();
				var frm = $(this).serialize();
				//console.log(frm);
				var db = document.getElementById('baseDatos').value;
				$.ajax({
					method: "POST",
					url: "../subcontratistas/guardar_subcontratistas.php?db="+db,
										contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
						mostrar_mensaje( json_info );
						//console.log(json_info);
						if (json_info.respuesta=="BIEN"){
								limpiar_datos();
								recargarTabla("");
						}
				});
			});
		}

		var guardar_modificar = function(){
			$("#btn_guardar_editar").one("click", function(e){
				e.preventDefault();
				var Id = $("#formulario_nuevo form #Id").serialize();
				var opcion = $("#formulario_nuevo form #opcion").serialize();

				var Subcontratista=$("#select_Subcontratista").serialize();
				var Correo_Contacto=$("#select_Correo_Contacto").serialize();
				var NIT=$("#select_NIT").serialize();
				var Alcance=$("#select_Alcance").serialize();
				var Tipo_Proveedor=$("#select_Tipo_Proveedor").serialize();
				var Activo=$("#select_Activo").serialize();

				frm=Id+"&"+opcion+"&"+Subcontratista+"&"+Correo_Contacto+"&"+NIT+"&"+Alcance+"&"+Tipo_Proveedor+"&"+Activo;
				// console.log(frm);
				var db = document.getElementById('baseDatos').value;
				$.ajax({
						method: "POST",
						url: "../subcontratistas/guardar_subcontratistas.php?db="+db,
						contenttype:"charset=utf-8",
						data: frm,
				}).done( function( info ){
						var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
						// console.log(json_info);
						mostrar_mensaje( json_info );
						limpiar_datos();
						recargarTabla("");
				});
			});
		}

		/* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
		var eliminar = function(){
			$("#eliminar-usuario").on("click", function(){
				var Id = $("#frmEliminarUsuario #Id").val(),
					opcion = $("#frmEliminarUsuario #opcion").val();
				var db = document.getElementById('baseDatos').value;
				$.ajax({
					method:"POST",
					url: "../subcontratistas/guardar_subcontratistas.php?db="+db,
										contenttype:"charset=utf-8",
					data: {"Id": Id, "opcion": opcion}
				}).done( function( info ){
					var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
					mostrar_mensaje( json_info );
					limpiar_datos();
					recargarTabla("");
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
			if( informacion.respuesta == "NO_CORREO" ){
				texto = "<strong>Advertencia!</strong> Por favor asignar una dirección de correo real (ej. correo@aia.com.co).";
				color = "#C9302C";
			}
			if( informacion.respuesta == "NOMBRE_REPETIDO" ){
				texto = "<strong>Advertencia!</strong> Ya existe un contratista con ese nombre.";
				color = "#C9302C";
			}
			if( informacion.respuesta == "NIT_REPETIDO" ){
				texto = "<strong>Advertencia!</strong> Ya existe un contratista con ese NIT.";
				color = "#C9302C";
			}
			if( informacion.respuesta == "NIT_INCOMPLETO" ){
				texto = "<strong>Advertencia!</strong> Por favor digite un NIT de 7 a 10 dígitos, sin dígito de verificación y sin guiones o puntos.";
				color = "#C9302C";
			}
			if( informacion.respuesta == "NIT_INCORRECTO" ){
				texto = "<strong>Advertencia!</strong> Por favor digite el NIT real del subcontratista.";
				color = "#C9302C";
			}
			if( informacion.respuesta == "NO_ELIMINAR" ){
				texto = "<strong>Advertencia!</strong> Este Subcontratista no se debe eliminar porque ya se han comprometido tareas en alguna semana del proyecto.";
				color = "#C9302C";
			}

			if(informacion.respuesta=="BIEN"){
				$("#formulario_nuevo").slideUp("slow");
				$("#cuadroTabla").slideDown("slow");
				$(".mensaje, #mensajeActualizacion").html( texto ).css({"color": color });
				$(".mensaje, #mensajeActualizacion").fadeOut(10000, function(){
					$(this).html("");
					$(this).fadeIn(3000);
				});
			}else if(informacion.respuesta=="NO_ELIMINAR"){
				$(".mensaje, #mensajeActualizacion").html( texto ).css({"color": color });
				$(".mensaje, #mensajeActualizacion").fadeOut(10000, function(){
					$(this).html("");
					$(this).fadeIn(3000);
				});
			}else{
				$(".mensaje, #mensajeActualizacion").html( texto ).css({"color": color });
				$(".mensaje, #mensajeActualizacion").fadeOut(10000, function(){
					$(this).html("");
					$(this).fadeIn(3000);
				});
			}
		}

		/*limpia los valores del formulario de registro*/
		var limpiar_datos = function(){
			$("#opcion").val("registrar");
			$("#Id").val("");
			$("#Subcontratista").val("").focus();
			$("#Correo").val("");
			$("#Confirmar_Correo").val("");
			$("#NIT").val("");
			$("#alcance").val("");
			$("#tipo_proveedor").val("");
		}

		var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
			$("#inicio_sem").val("");
		}


		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function() {
		  /*Identificamos la altura de la hoja para determinar la altura de la tabla*/
		  var alturahoja = $(window).height();
			var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
			document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";
			document.getElementById('formularioRegistro').style.maxHeight = (alturahoja - posicionInicioTabla) + "px";

			var alturatabla = (alturahoja - posicionInicioTabla - 190) + "px";
			var db = document.getElementById('baseDatos').value;
		  var table = $("#dt_cliente").DataTable({
		    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-6 ml-auto'f>>t<'row'<'col-md-6'i>><'clear'>",
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
		      "url": "../subcontratistas/listar_subcontratistas.php?db="+db
		    },
		    "lengthMenu": [100, 200, 500],
				'columnDefs': [
						{
								'targets': [0],
								'className': 'Botones'
						},
						{
								'targets': [2],
								'className': 'input_Subcontratista'
						},
						{
								'targets': [3],
								'className': 'input_Correo_Contacto'
						},
						{
								'targets': [4],
								'width':"10%",
								'className': 'input_NIT'
						},
						{
								'targets': [5],
								'width':"20%",
								'className': 'input_Alcance'
						},
						{
								'targets': [6],
								'className': 'input_Tipo_Proveedor'
						},
						{
								'targets': [7],
								'className': 'input_Activo',
								'render': function ( data, type, full, meta ) {
									if (data == "" || data == null) {
					          return "";
					        } else if (data == 0) {
					          return "No";
					        } else if (data == 1) {
										return "Si";
									}

								},
						},
				],

				"columns":[
						{"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm'  title='Eliminar' style='margin:1px'><i class='fa fa-trash-alt fa-xs'></i></button>"},
						{"data":"Id", "visible":false},
						{"data":"subcontratista"},
						{"data":"correo_contacto"},
						{"data":"NIT"},
						{"data":"alcance"},
						{"data":"tipo_proveedor"},
						{"data":"activo"},
				],
		    "language": idioma_espanol
		  });

			$("div.toolbarFilaBotones").html('<button id="btn_nuevo" class="btn btn-primary btn-sm" title="Registrar nuevo Sub-Contratista para el proyecto">Registrar Sub-Contratista <i class="fas fa-plus fa-lg"></i></button>');
			$("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

			maestroPermisos(document.getElementById('permiso').value);
		  obtener_data_editar("#dt_cliente tbody", table);
		  obtener_id_eliminar("#dt_cliente tbody", table);
			nuevo_subcontratista();
		}

		/*Toma los datos de la fila en la que se presionó el botón editar*/
    var obtener_data_editar=function(tbody, table){
      var permiso = document.getElementById('permiso').value;
      if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="DCV" || permiso=="V" || permiso=="C"){
          var only_once = false;
      }else{
          var only_once = true;
      }
      $(tbody).one("click", "td", function(){
        var data= table.row($(this).parents("tr")).data();
        var Id=$("#Id").val(data.Id),
		    opcion = $("#opcion").val("modificar");
        if(only_once == true){
          var codigo_html_Subcontratista =  "<input id='select_Subcontratista' name='Subcontratista' class='form-control' type='text' value='"+data.subcontratista+"'></input>";
          $( this ).parent().find('.input_Subcontratista').html(codigo_html_Subcontratista);
          var codigo_html_Correo_Contacto =  "<input id='select_Correo_Contacto' name='Correo_Contacto' class='form-control' type='text' value='"+data.correo_contacto+"'></input>";
          $( this ).parent().find('.input_Correo_Contacto').html(codigo_html_Correo_Contacto);
          var codigo_html_NIT =  "<input id='select_NIT' name='NIT' class='form-control' type='number' step='1' value='"+data.NIT+"' onkeyup='quitar_guion_directo()''></input>";
          $( this ).parent().find('.input_NIT').html(codigo_html_NIT);
          var codigo_html_Alcance =  "<input id='select_Alcance' name='Alcance' class='form-control' type='text' value='"+data.alcance+"'></input>";
          $( this ).parent().find('.input_Alcance').html(codigo_html_Alcance);
          var codigo_html_Tipo_Proveedor =  "<select id='select_Tipo_Proveedor' name='Tipo_Proveedor' class='form-control'><option value='Mano de Obra'>Mano de Obra</option><option value='Suministro e Instalación'>Suministro e Instalación</option><option value='Suministro de Materiales, Herramientas o Equipos'>Suministro de Materiales, Herramientas o Equipos</option></select>";
          $( this ).parent().find('.input_Tipo_Proveedor').html(codigo_html_Tipo_Proveedor);
					var codigo_html_Activo =  "<select id='select_Activo' name='Activo' class='form-control'><option value='1'>Si</option><option value='0'>No</option></select>";
					$( this ).parent().find('.input_Activo').html(codigo_html_Activo);
          var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' style='margin:1px' title='Guardar la edición'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' style='margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
          $( this ).parent().find('.Botones').html(codigo_html_botones);
          $("#select_Subcontratista").val(data.subcontratista).change();
          $("#select_Subcontratista").focus();
          $("#select_Correo_Contacto").val(data.correo_contacto).change();
          $("#select_NIT").val(data.NIT).change();
          $("#select_Alcance").val(data.alcance).change();
          $("#select_Tipo_Proveedor").val(data.tipo_proveedor).change();
					$("#select_Activo").val(data.activo).change();
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
        guardar_modificar();
      });
    }

		/*Toma los datos de la fila en la que se presionó el botón eliminar*/
		var obtener_id_eliminar=function(tbody, table){
			var permiso = document.getElementById('permiso').value;
		  if (permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="DCV" || permiso=="V" || permiso=="C") {
		  } else {
				$(tbody).one("click", "button.eliminar", function(){
					$("#modalEliminar").modal("show");
					var data= table.row($(this).parents("tr")).data();
					var idusuario=$("#frmEliminarUsuario #Id").val(data.Id);
					var texto=$("#modal-body-texto-eliminar").html("¿Desea eliminar al Sub-Contratista: "+data.subcontratista+"?");
				});
			}

		}

		/*Abre el formulario para registrar un nuevo profesional*/
		var nuevo_subcontratista=function(){
			$("#btn_nuevo").on("click", function(){
			$("#formulario_nuevo").slideDown("slow");
			$("#cuadroTabla").hide("slow");
			opcion = $("#opcion").val("registrar");
			});
		}

		var quitar_guion=function(){
			var NIT=$("#NIT").val();
			NIT_nuevo = NIT.replace("-","","gi");
			NIT_nuevo = NIT_nuevo.replace(".","","gi");
			$("#NIT").val(NIT_nuevo);
		}

		var quitar_guion_directo=function(){
			var NIT=$("#select_NIT").val();
			NIT_nuevo = NIT.replace("-","","gi");
			NIT_nuevo = NIT_nuevo.replace(".","","gi");
			$("#select_NIT").val(NIT_nuevo);
		}

		var recargarTabla = function(opcion) {
		  var posicion = $('.dataTables_scrollBody').scrollTop();
		  var table = $('#dt_cliente').DataTable();
		  if (opcion == "listar") {
		    $('#dt_cliente').empty();
		    listar();
		  } else {
		    table.ajax.reload();
		    obtener_data_editar("#dt_cliente tbody", table);
				obtener_id_eliminar("#dt_cliente tbody", table);
				nuevo_subcontratista();
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

	</script>
</body>
</html>
