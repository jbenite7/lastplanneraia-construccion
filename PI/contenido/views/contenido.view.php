<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Contenido</title>
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    
    <!-- Fuentes de Google-->
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (Íconos)-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    
    
    <!--Iniciar estilos de Bootstrap-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">
	
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../css/estilos4.css">
    
	<!-- Estilos Buttons DataTables -->
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css">
    
    <!-- Checkboxes DataTables -->
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">
	
    <!--Selector de fechas -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css">
    


</head>
    
<!--Etiqueta superior-->
<body>
    
    <div class="encabezado">
        <ul>
            <?php 
            $proyecto=$_SESSION['proyecto']; 
            $db=$_SESSION['db'];
            $semana=$_SESSION['semana'];
            $permiso=$_SESSION['permiso'];
            
            require ("../conexion.php");
            $query="SELECT COUNT(*) FROM $db"."_semanas_activas";
            $resultado= mysqli_query($conexion, $query);
            $data=mysqli_fetch_assoc($resultado);
            $conteo=$data["COUNT(*)"];
            
            if($conteo==0){
                $Fecha_Inicio_Sem=date("Y-m-d");
                $Fecha_Inicio_SemYMD=$Fecha_Inicio_Sem;
                $Fecha_Fin_Sem=date("Y-m-d",strtotime($Fecha_Inicio_Sem ."+7 days"));
                $Fecha_Fin_SemYMD=$Fecha_Fin_Sem;
                
                $Fecha_Inicio_Sem=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Inicio_Sem));
                $Fecha_Fin_Sem=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Fin_Sem));
                
                $Fecha_datepicker=$Fecha_Inicio_Sem;
                //echo "$Fecha_Inicio_Sem <br> $Fecha_Fin_Sem";
 
            }else{
                $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado1= mysqli_query($conexion, $query1);
                $data1=mysqli_fetch_assoc($resultado1);
                $Fecha_Inicio_Sem=$data1["Fecha_Inicio_Sem"];
                $Fecha_Inicio_SemYMD=$Fecha_Inicio_Sem;
                $Fecha_Inicio_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Inicio_Sem"));
                
                $Fecha_Fin_Sem=$data1["Fecha_Fin_Sem"];
                $Fecha_Fin_SemYMD=$Fecha_Fin_Sem;
                $Fecha_Fin_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Fin_Sem"));
                
                require ("../conexion.php");
                $query2="SELECT Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado2= mysqli_query($conexion, $query2);
                $data2=mysqli_fetch_assoc($resultado2);
                $Fecha_datepicker=$data2["Fecha_Fin_Sem"];
                $Fecha_datepicker=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_datepicker"));
                
                $query3="SELECT Semana FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado3= mysqli_query($conexion, $query3);
                $data3=mysqli_fetch_assoc($resultado3);
                $Max_Semana=$data3["Semana"];
                //echo "<script>console.log($Max_Semana)</script>";
            }
            ?>
            <li><img src="../imagenes/logo.png" width="30%"></li>
            
            <?php
            $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
            $resultado2= mysqli_query($conexion, $query2);
            $data=mysqli_fetch_assoc($resultado2);
            $Fecha_Inicio_SemYMD=$data["Fecha_Inicio_Sem"];
            $Fecha_Fin_SemYMD=$data["Fecha_Fin_Sem"];
            ?>
            <li><h1 class="titulo">Last Planner AIA - <?php echo "$proyecto" ?></h1></li>
        </ul>
    </div>
    
    <div class="contenedor_contenido">
         
        <hr class="border">
        <div class="contenido">
<!--            <div class="row_fondo">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <h1 class="text-center  text-uppercase">Programa General</h1>
                </div>
            </div>-->
            <!--Barra de navegación-->
            <nav class="navbar navbar-expand-m navbar-dark ">
              <a class="navbar-brand" href="#">
                <ul>
                    <li><img src="../imagenes/logo2.png" width="" class="d-inline-block align-top" alt=""></li>
                    <li class="lps">LPS</li>
                    <li class="pagina"> - Contenido</li>
                </ul>
              </a>
              <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                  <li class="nav-item active">
                    <a class="nav-link" href="<?php echo"../cambiar_pagina.php?seccion=contenido&semana=$semana"?>">Contenido</a>
                  </li>
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Información General</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_profesionales&semana=$semana"?>">Profesionales AIA</a>
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_subcontratistas&semana=$semana"?>">Terceros</a>
                    </div>
                  </li>
                  <li class="nav-item"> 
                    <a class='dropdown-item' style='padding: 16px 0px 8px 16px'>      
                        <button type= 'button' class='nueva_sem btn btn-primary' title='Crear nueva semana de la programación semanal' onclick=nueva_sem() data-toggle='modal' data-target='#modal_nueva_sem'><i class="fa fa-plus fa-lg"></i> Nueva Semana</button>
                    </a>
                  </li>
                    
                    <?php
                        require("../conexion.php");
                        $query="SELECT  COUNT(*) FROM $db"."_semanas_activas";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        mysqli_close($conexion);
                        if ($conteo==0){  
                        } else if($conteo>0){
                            for($i=$conteo; $i>=1; $i--){
                                require("../conexion.php");
                                $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$i";
                                $resultado2= mysqli_query($conexion, $query2);
                                $data=mysqli_fetch_assoc($resultado2);
                                $ini=$data["Fecha_Inicio_Sem"];
                                $fin=$data["Fecha_Fin_Sem"];
                                mysqli_close($conexion);
                                if($i==$semana){
                                    if(($Max_Semana-2)>=$i){
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>       
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas&semana=$i'>Tareas Periódicas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas&semana=$i'>Tareas Periódicas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }
                                       
                                } else{
                                    if(($Max_Semana-2)>=$i){
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>       
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas&semana=$i'>Tareas Periódicas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas&semana=$i'>Tareas Periódicas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>"; 
                                    }
                                }
                            };
                        };

                    ?>
                    
                  <li class="nav-item">
                    <a class="nav-link" href="../cerrar.php" tabindex="-1" aria-disabled="true">Cerrar Sesión</a>
                  </li>
                </ul>
              </div>
            </nav>
        </div>
    </div>




    
    
    <!-- Iniciar Jquery-->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.12.4.js"></script>
    
    <!-- Iniciar Popper-->
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    
    <!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    
    <!--Iniciar DataTables-->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>

    <!--Botones de Datatables-->

	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.bootstrap4.min.js"></script>
   
	<!--checkboxes DataTables-->	
    <script type="text/javascript" src="https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
    
    <!--Selector de fechas -->
    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    
    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->
    

	<script>
        
        /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function(){
            ocultos();
			listar();
            guardar();
            eliminar();
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_listar").on("click", function(){
            listar();
        });
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            location.reload(true);
            //listar();
            
            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$("form").on("submit", function(e){
				e.preventDefault();
				var frm = $(this).serialize();
                console.log(frm);
				$.ajax({
					method: "POST",
					url: "../contenido/guardar_contenido.php?db=<?php echo $db?>",
					data: frm,
				}).done( function( info ){	
					var json_info = JSON.parse( info );
                    mostrar_mensaje( json_info );
					limpiar_datos();
					listar();
				});
			});
		}
        
        
        /* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
        var eliminar = function(){
			$("#eliminar-usuario").on("click", function(){
				var Id = $("#frmEliminarUsuario #Id").val(),
					opcion = $("#frmEliminarUsuario #opcion").val();
				$.ajax({
					method:"POST",
					url: "../contenido/guardar_contenido.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"Id": Id, "opcion": opcion}
				}).done( function( info ){
					var json_info = JSON.parse( info );
					mostrar_mensaje( json_info );
					limpiar_datos();
					listar();
				});
			});
		}
        
        /*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function( informacion ){
			var texto = "", color = "";
			if( informacion.respuesta == "BIEN" ){
					texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
					color = "#379911";
			}else if( informacion.respuesta == "ERROR"){
					texto = "<strong>Error</strong>, no se ejecutó la consulta.";
					color = "#C9302C";
			}else if( informacion.respuesta == "EXISTE" ){
					texto = "<strong>Información!</strong> el usuario ya existe.";
					color = "#5b94c5";
			}else if( informacion.respuesta == "VACIO" ){
					texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
					color = "#ddb11d";
			}else if( informacion.respuesta == "OPCION_VACIA" ){
					texto = "<strong>Advertencia!</strong> la opción no existe o esta vacia, recargar la página.";
					color = "#ddb11d";
			}

			$(".mensaje").html( texto ).css({"color": color });
			$(".mensaje").fadeOut(5000, function(){
					$(this).html("");
					$(this).fadeIn(3000);
			});			
		}
        
        
        /*limpia los valores del formulario de registro*/
		var limpiar_datos = function(){
			$("#opcion").val("registrar");
            $("#Id").val("").focus();
			$("#Id1").val("").focus();
			$("#Actividad").val("");
			$("#Fecha_Inicio").val("");
			$("#Fecha_Fin").val("");
            $("#Critica").val("");
            $("#Ejecutado").val("");
            $("#Estado").val("");
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");
		}
        
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar=function(){
            $("#cuadro2").slideUp("slow");
            $("#cuadro1").slideDown("slow");
            var table = $("#dt_cliente").DataTable({
                "dom": '<"top"iflp<"clear">>rt<"bottom"iflp<"clear">>',
                "destroy":true,
                "order":false,
                "autoWidth": true,

                "ajax":{
                  "method":"POST",
                  "url":"../contenido/listar_contenido.php?db=<?php echo $db?>"
              },
                
                
                'columnDefs': [
                     {
                        'targets': 0,
                        'width': "5%",
                        'checkboxes': {
                           'selectRow': false,
                        }
                     },
                    
                     {
                        'targets': [1,2,3,4,5,6,7,8,9,10],
                        'width': "8%",
                     },
                    
                        {
                        'targets': [7,9],
                        'render': function ( data, type, full, meta ) {
                            data=data*100;
                            data=data.toFixed(2);
                            return +data+'%';
                        },
                    },
                  ],
                

                
                'select': {
                 'style': 'false',
                },  
                
                "lengthMenu": [100],

                "columns":[
                    {"defaultContent":""},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Titulo", "visible":false},
                    {"data":"Fecha_Inicio"},
                    {"data":"Fecha_Fin"},
                    {"data":"Ruta_Critica"},
                    {"data":"Ejecutado"},
                    {"data":"Estado"},
                    {"data":"Estado_Restricciones"},
                    {"defaultContent":"<button type= 'button' class='editar btn btn-primary'><i class='fa fa-pencil-square-o'></i></button><button type='button' class='eliminar btn btn-danger' data-toggle='modal' data-target='#modalEliminar' ><i class='fa fa-trash-o'></i></button>"},
                ],
                


                

                "createdRow": function( row, data, dataIndex){
                    var inicio_sem = new Date(2019,9,23);
                    var fin_sem = new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+7);


                    var inicio_act = new Date(data.Fecha_Inicio );
                    var fin_act = new Date(data.Fecha_Fin );

                    var fecha_intermedia= new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+42);

                    /*console.log(inicio_sem, fin_sem, inicio_act, fin_act, fecha_intermedia);*/
                    
                    if(((fin_act>=fin_sem && inicio_act<=fin_sem) || (inicio_act>=inicio_sem && inicio_act<=fin_sem)) && data.Ruta_Critica==="1" && data.Estado!="Terminada Antes" ){
                        $(row).addClass('Semanal_Critica');

                    } 

                    else if(((fin_act>=fin_sem && inicio_act<=fin_sem) || (inicio_act>=inicio_sem && inicio_act<=fin_sem)) && data.Ruta_Critica==="0" && data.Estado!="Terminada Antes"){
                        $(row).addClass('Semanal_No_Critica');
                    } 

                    else if(data.Estado==="Atrasada"){
                        $(row).addClass('Semanal_Atrasada');
                    }

                    else if(inicio_act<=fecha_intermedia && inicio_act>=inicio_sem ){
                        $(row).addClass('Intermedia');
                    }

                },

                "language": idioma_espanol
            });
            
            
            obtener_data_editar("#dt_cliente tbody", table);
            obtener_id_eliminar("#dt_cliente tbody", table);
            obtener_id_editar("#dt_cliente tbody", table);
        }
        
//        var contar_cajas_checkeadas=function()
        
        
        /*Para agregar un nuevo usuario en la base de datos*/
        var agregar_nuevo_usuario = function(){
            limpiar_datos();
            $("#cuadro2").slideDown("slow");
            $("#cuadro1").slideUp("slow");   
        }
        
        /*Toma los datos de la fila en la que se presionó el botón editar*/
        var obtener_data_editar=function(tbody, table){
            $(tbody).on("click", "button.editar", function(){
                var data= table.row($(this).parents("tr")).data();
                var Id=$("#Id").val(data.Id),
                    Id1=$("#Id1").val(data.Id),
                    Actividad=$("#Actividad").val(data.Actividad),
                    Fecha_Inicio=$("#Fecha_Inicio").val(data.Fecha_Inicio),
                    Fecha_Fin=$("#Fecha_Fin").val(data.Fecha_Fin),
                    Critica=$("#Critica").val(data.Ruta_Critica),
                    Ejecutado=$("#Ejecutado").val(data.Ejecutado),
                    Estado=$("#Estado").val(data.Estado),
                    
				    opcion = $("#opcion").val("modificar");

                    $("#cuadro2").slideDown("slow");
                    $("#cuadro1").slideUp("slow");   
            });
        }

        /*Toma los datos de la fila en la que se presionó el botón eliminar*/
        var obtener_id_eliminar=function(tbody, table){
            $(tbody).on("click", "button.eliminar", function(){
                var data= table.row($(this).parents("tr")).data();
                var idusuario=$("#frmEliminarUsuario #Id").val(data.Id);
            });
        }
        
        
        
        /*crea los Id que se deben actualizar en la base de datos*/
        var obtener_id_editar=function(tbody, table){
            var script="";
            $("#btn_editar").on("click", function(){
                $("td input").each(function(){
                    
                    if($(this).is(':checked')){
                        var valor_actual;
                        var data= table.row($(this).parents("tr")).data();
                        valor_actual=data.Id;
                        if (script==="" && valor_actual!=""){
                            script ="Id='"+valor_actual+"'";
                        } else if (script!="" && valor_actual===""){
                            script=script;
                        } else if (script!="" && valor_actual!=""){
                            script=script+" OR Id='"+valor_actual+"'";
                        }                    
                    }
                });
                console.log(script);
                $("#cuadro2").slideDown("slow");
                $("#cuadro1").slideUp("slow"); 
            });    
        }
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../contenido/guardar_contenido.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"f_inicio_sem": f_inicio_sem, "opcion": opcion}
				}).done( function( info ){
					var json_info = JSON.parse( info );
					mostrar_mensaje( json_info );
					limpiar_datos_nueva_sem();
                    location.reload(true);
					listar();
				});
            });
        }
        
        var eliminar_sem=function(semana){
            $("#modal-eliminar-semana-body-texto").text("¿Desea Eliminar la Semana "+semana+"?");
            $("#btn_eliminar_sem").on("click", function(){            
                opcion="eliminar_sem";
				$.ajax({
					method:"POST",
					url: "../contenido/guardar_contenido.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"semana": semana, "opcion": opcion}
				}).done( function( info ){
                    location.reload(true);
					listar();
				});
            });
        }
        
        $(function() {
            var dia=new Date(<?php echo $Fecha_datepicker; ?>);
            //dia = new Date(dia.getFullYear(),dia.getMonth(),dia.getDate());
            
            $( "#inicio_sem" ).val(dia.getFullYear()+"-"+(dia.getMonth()+1)+"-"+dia.getDate());
            
            $( "#inicio_sem" )
                .datepicker({dateFormat: 'yy-mm-dd',
                                           changeMonth: true,
                                           changeYear: true,
                                           showOtherMonths: true,
                                           selectOtherMonths: true,
                                           defaultDate: dia,
                                          });
        });
        

        var ocultos=function(){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
            }else if(permiso=="G"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
            }else if(permiso=="S"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia').css('display', 'none');
                window.location.href='../programacion_semanal/programacion_semanal.php';
            }
        }

        

        /*Configura la DataTable en idioma español*/
       var idioma_espanol={
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla =(",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
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