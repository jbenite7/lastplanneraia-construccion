<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Last Planner PI AIA</title>
    <link rel="icon" href="../imagenes/logo2.png">
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    
    <!-- Fuentes de Google-->
    <link href="https://fonts.googleapis.com/css?family=Roboto|Bree+Serif&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (Íconos)-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
    
    
    <!--Iniciar estilos de Bootstrap-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">
	
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../css/estilos4.css">
    
	<!-- Estilos Buttons DataTables -->
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css">
    
    <!-- Checkboxes DataTables -->
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css">




</head>
    
<!--Etiqueta superior-->
<body>
    
<header class="header">
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
            <li><h1 class="titulo">LPS Proyectos Inmobiliarios AIA - <?php echo "$proyecto, Semana $semana " ?><h2 class="titulo_pequeño"><?php echo"(del $Fecha_Inicio_SemYMD al $Fecha_Fin_SemYMD)"?></h2></h1></li>
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
                    <li class="pagina"> - Tareas Periódicas Compuestas / Semana <?php echo $semana ?></li>
                </ul>
              </a>
              <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                  <li class="nav-item contenido_link">
                    <a class="nav-link" href="<?php echo"../cambiar_pagina.php?seccion=contenido&semana=$semana"?>">Contenido</a>
                  </li>
                  <li class="nav-item dropdown informacion_general">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Información General</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_profesionales&semana=$semana"?>">Profesionales AIA</a>
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_subcontratistas&semana=$semana"?>">Terceros</a>
                    </div>
                  </li>
                  <li class="nav-item active"> 
                    <a class='dropdown-item' style='padding: 16px 0px 8px 16px'>      
                        <button type= 'button' class='nueva_sem btn btn-primary' title='Crear nueva semana de la programación semanal'  onclick=nueva_sem() data-toggle='modal' data-target='#modal_nueva_sem'><i class="fa fa-plus fa-lg"></i> Nueva Semana</button>
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
                                        <li class='nav-item dropdown active' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>       
                                            </a>
                                            <div class='dropdown-menu show' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown active' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu show' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
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



    <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
        <div class="modal fade" id="modal_checklist" role="dialog">
        <div class="modal-dialog modal-lg">

          <!-- Modal content-->
          <div class="modal-content">
            <div class="modal-header">
              <p class="modal-title" id="modal_checklist_Label" style="font-weight:bold">Checklist</p>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="cuadro2" class="cuadro2">
                    <form class="form checklist form-horizontal" action="" method="POST">
                        <div class="form-group">
                            <h3 class="col-sm-offset-2 col-sm-12 text-center" id="actualizacion"></h3>
                        </div>

                        <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
                        <input type="hidden" id="Id" name="Id" value="0">
                        <input type="hidden" id="semana" name="semana" value="" readonly>
                        <input type="hidden" id="checklist" name="checklist" value="" readonly>
                        <input type="hidden" id="opcion" name="opcion" value="registrar">


        <!--                <div style="width:40%; float:left;">-->
                            <div class="parametro form-group" id="requerimientos">                


                            </div>

                            <div class="parametro form-group" id="observaciones_checklist">                
                                <div class="form_eval form-group">
                                    <h3 id="form_obs">Observaciones</h3>
                                </div>

                                <div class="pregunta form-group">
                                    <div ><textarea id="Observaciones" name="Observaciones" class="form-control" ></textarea></div>
                                </div>
                            </div>
                        
                            <!--Se crean los botones Guardar y Listar-->
                            <div class="form-group" id="botones_checklist">
                                <div class="botones">
                                    <input id="btn_guardar" type="submit" class="btn btn-primary" value="Guardar">
                                    <input id="btn_listar" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar">
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
    <!-- Modal -->
    <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "cuadro2", la cual permanecerá oculta hasta que se presione el botón editar en alguna fila de la datatable -->
	<div class="row">
		
	</div>
</header>
    
<main class="main">
    
        <!--Línea donde se inserta el botón para editar los registros -->
	<div class="row">
		<div id="cuadro3" class="cuadro3  col-lg-12">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 ">
						<button type= 'button' class='leyenda_colores btn btn-primary btn-sm' data-toggle='modal' data-target='#modal_leyenda_colores'>Leyenda <i class='fas fa-question-circle fa-lg'></i>
						</button>
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:10; float:right; margin-left:2%; margin-right:35%">
                            <button id="btn_total" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('total')"><h6>Totales</h6></button>
                            <button id="btn_lookahead" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('lookahead')"></button>
                            <button id="btn_no_iniciadas" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('no_iniciadas')"></button>
                            <button id="btn_en_ejecucion" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('en_ejecucion')"></button>
                            <button id="btn_terminadas" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('terminadas')"></button>
                        </div> 
					</div>
				</div>
			</form>
            
            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class=" col-sm-8">
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
			<div class="table-responsive col-sm-12">		
				<table id="dt_general" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
                            <th></th>
                            <th>Consecutivo</th>
							<th>Id</th>
							<th>Actividad</th>
							<th>Título</th>
                            <th>Dias al Inicio</th>
                            <th>Lookahead</th>
                            <th>Checklist</th>
                            <th>Periodicidad</th>
                            <th>Relevancia</th>
                            <th>Ejecución</th>
                            <th>Estado</th>
                            <th>Estado de Checklist</th>
                            <th>Requerimiento 1</th>
                            <th>Requerimiento 2</th>
                            <th>Requerimiento 3</th>
                            <th>Requerimiento 4</th>
                            <th>Requerimiento 5</th>
                            <th>Requerimiento 6</th>
                            <th>Requerimiento 7</th>
                            <th>Requerimiento 8</th>
                            <th>Requerimiento 9</th>
                            <th>Requerimiento 10</th>
                            <th>Requerimiento 11</th>
                            <th>Requerimiento 12</th>
                            <th>Requerimiento 13</th>
                            <th>Requerimiento 14</th>
                            <th>Requerimiento 15</th>
                            <th>Requerimiento 16</th>
                            <th>Requerimiento 17</th>
                            <th>Requerimiento 18</th>
                            <th>Requerimiento 19</th>
                            <th>Requerimiento 20</th>
                            <th>Requerimiento 21</th>
                            <th>Requerimiento 22</th>
                            <th>Requerimiento 23</th>
                            <th>Requerimiento 24</th>
                            <th>Requerimiento 25</th>
                            <th>Observaciones</th>

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
            <input type="hidden" id="semana" name="semana" value="" readonly>
            <input type="hidden" id="Actividad" name="Actividad" value="" readonly>
			<input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>
            
            
            
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
			<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEliminarLabel">Eliminar Usuario</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
            <!-- Se crea el Modal que solicita la información para crear una nueva semana -->
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
                                                    <input id="btn_cancelar" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" >
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
            <!--Se crea un div. Acá se agregará un form llamado "frmEliminarUsuario", el cual permanecerá oculto. En este form se crean 2 inputs que contienen el id del registro que se va a eliminar, y el switch que dice si hay que eliminar  -->  
            <div>     
                <form id="frmEliminarUsuario" action="" method="POST">
                    <input type="hidden" id="Id" name="Id" value="" readonly>
                    <input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>



                    <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
                    <div class="modal_eliminar_sem modal fade" id="modal_eliminar_sem" tabindex="-1" role="dialog" aria-labelledby="modal_nueva_semLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="modalEliminarLabel">Eliminar Semana LPS</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">							
                                    <div class="row">
                                            <div id="cuadro5" class="cuadro5 col-sm-12 col-md-12 col-lg-12 ">
                                                <form class="form form-horizontal" action="" method="POST">
                                                    <!--<div class="form-group">
                                                        <h3 class="col-sm-offset-2 col-sm-8 text-center"></h3>
                                                    </div>
        -->
                                                   <p class="modal-eliminar-semana-body-texto" id="modal-eliminar-semana-body-texto"></p>


                                                    <!--Se crean los botones Guardar y Listar-->
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-8">
                                                            <input id="btn_eliminar_sem" type="button" class="btn btn-primary" data-dismiss="modal" value="Eliminar">
                                                            <input id="btn_cancelar1" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" >
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
		</form>
	</div>
</main>
    
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
    <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
    

    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    
    
    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
        
        /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function(){
            listar();
            guardar();
           
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_listar").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            location.assign("../scroll_general.php?scroll="+posicion+"&seccion=periodicas_compuestas")
            //$("#cuadro2").hide();
            //$("#cuadro1").slideDown();
            $("#cuadro3").slideDown();
        });
        
        $("#btn_listar1").on("click", function(){
            listar();
            $("#cuadro4").hide();
            $("#cuadro1").slideDown();
            $("#cuadro3").slideDown();
        });
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar").on("click", function(){
            location.reload(true);
            //listar();
            
            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
    
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$(".checklist").on("submit", function(e){
				e.preventDefault();
				var frm = $(this).serialize();
                
                if($("#opcion").val()=="modificar_checklist"){
                    console.log(frm);
                    $.ajax({
                        method: "POST",
                        url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
                        contenttype:"charset=utf-8",
                        data: frm,
                    }).done( function( info ){
                        //console.log(info)
                        //var json_info = JSON.parse( info );
                        //mostrar_mensaje( json_info );
                        //limpiar_datos();
                        //listar();
                        var posicion=$('.dataTables_scrollBody').scrollTop();
                        location.assign("../scroll_general.php?scroll="+posicion+"&seccion=periodicas_compuestas")
                        //location.reload(true);
                    });    
                }else{
                    var descripcion= $("#descripcion").val();
                    var requerimiento= $("#requerimiento").val();
                    if(descripcion=='' && !requerimiento){
                        window.alert('El campo "Descripción" no puede estar vacío');
                    }else{
                        $.ajax({
                            method: "POST",
                            url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
                            contenttype:"charset=utf-8",
                            data: frm,
                        }).done( function( info ){
                            if($("#opcion").val()=="comprometer_requerimiento"){
                                if(info=="duplicado"){
                                    window.alert("Este requerimiento ya fue comprometido");
                                    $("#btn_cancelar_nuevo_requerimiento").click();
                                }else{
                                    window.alert("Requerimiento Comprometido (Ir a sección de Programación Semanal)");
                                    $("#btn_cancelar_nuevo_requerimiento").click();
                                    //var posicion=$('.dataTables_scrollBody').scrollTop();
                                    //location.assign("../scroll_general.php?scroll="+posicion+"&seccion=periodicas_compuestas")
                                }
                            }else{
                                window.alert("Nuevo requerimiento agregado a la checklist");
                                $("#btn_cancelar_nuevo_requerimiento").click();
                                //var posicion=$('.dataTables_scrollBody').scrollTop();
                                //location.assign("../scroll_general.php?scroll="+posicion+"&seccion=periodicas_compuestas")
                            }
                            //var json_info = JSON.parse( info );
                            //mostrar_mensaje( json_info );
                            //limpiar_datos();
                            //listar();
                            //location.reload(true);
                        });
                    }
                    
                }
				
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
                //$("#cuadro2").slideUp("slow");
                $("#cuadro4").slideUp("slow");
                $("#cuadro1").slideDown("slow");
                $("#cuadro3").slideDown("slow");
                $(".mensaje2").html( texto ).css({"color": color });
                $(".mensaje2").fadeOut(5000, function(){
                        $(this).html("");
                        $(this).fadeIn(3000);
			     });
            } else{
                $(".mensaje").html( texto ).css({"color": color });
                $(".mensaje").fadeOut(5000, function(){
                        $(this).html("");
                        $(this).fadeIn(3000);
			     });
            }
        }
        
        
        /*limpia los valores del formulario de registro*/
		var limpiar_datos = function(){
			$("#opcion").val("registrar");
            $("#Actividad").val("");
            $("#Categoria_CNC").val("").focus();
            $("#CNC").val("");
            $("#Observaciones_CNC").val("");
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");

		}
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/

            
        var listar=function(){
            var posicion= <?php 
            if(isset($_SESSION["scroll"])){
                echo $_SESSION["scroll"];
            }else{
                echo 0;
            }
            ?>;
            
            <?php
                unset($_SESSION["lookahead_tram"],
                $_SESSION["no_iniciadas_tram"],
                $_SESSION["en_ejecucion_tram"],
                $_SESSION["terminadas_tram"],
                $_SESSION["total_tram"],
                $_SESSION["lookahead_cons"],
                $_SESSION["no_iniciadas_cons"],
                $_SESSION["en_ejecucion_cons"],
                $_SESSION["terminadas_cons"],
                $_SESSION["total_cons"],
                $_SESSION["lookahead_tps"],
                $_SESSION["no_iniciadas_tps"],
                $_SESSION["en_ejecucion_tps"],
                $_SESSION["terminadas_tps"],
                $_SESSION["total_tps"],
                $_SESSION["lookahead_tpr"],
                $_SESSION["no_iniciadas_tpr"],
                $_SESSION["en_ejecucion_tpr"],
                $_SESSION["terminadas_tpr"],
                $_SESSION["total_tpr"]);
            ?>
            var activa_lookahead= <?php 
            if(isset($_SESSION['lookahead_tpc'])){
                if($_SESSION['lookahead_tpc']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_no_iniciadas= <?php 
            if(isset($_SESSION['no_iniciadas_tpc'])){
                if($_SESSION['no_iniciadas_tpc']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_en_ejecucion= <?php 
            if(isset($_SESSION['en_ejecucion_tpc'])){
                if($_SESSION['en_ejecucion_tpc']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_terminadas= <?php 
            if(isset($_SESSION['terminadas_tpc'])){
                if($_SESSION['terminadas_tpc']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            <?php 
                require("../conexion.php");
                $query="SELECT (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_compuestas' AND Dias_Inicio>7 AND Dias_Inicio<=Lookahead AND Ejecutado=0) AS 'lookahead', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_compuestas' AND Dias_Inicio<=7 AND Ejecutado=0) AS 'no_iniciadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_compuestas' AND Ejecutado>0 AND Ejecutado<1) AS 'en_ejecucion', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_compuestas' AND Ejecutado=1) AS 'terminadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_compuestas' AND (Dias_Inicio<=Lookahead OR Ejecutado>0)) AS 'total'";
                $resultado= mysqli_query($conexion, $query);
                $data=mysqli_fetch_assoc($resultado);
                $lookahead=$data['lookahead'];
                $no_iniciadas=$data['no_iniciadas'];
                $en_ejecucion=$data['en_ejecucion'];
                $terminadas=$data['terminadas'];
                $total=$data['total'];
            ?>
            
            var lookahead=<?php echo $lookahead ?>;
            var no_iniciadas=<?php echo $no_iniciadas ?>;
            var en_ejecucion=<?php echo $en_ejecucion ?>;
            var terminadas=<?php echo $terminadas ?>;
            var total=<?php echo $total ?>;
            
            if(total!=0){
                var p_lookahead=Math.round(lookahead/total*100) +'%';
                var p_no_iniciadas=Math.round(no_iniciadas/total*100) +'%';
                var p_en_ejecucion=Math.round(en_ejecucion/total*100) +'%';
                var p_terminadas=Math.round(terminadas/total*100) +'%';
                
            }else{
                var p_lookahead='0%';
                var p_no_iniciadas='0%';
                var p_en_ejecucion='0%';
                var p_terminadas='0%';
            }
            
            $("#btn_lookahead").html("<h6 style=margin:0; min-width:100px>En Lookahead</h6><h6 style=margin:0>"+p_lookahead+"</h6>");
            $("#btn_no_iniciadas").html("<h6 style=margin:0; min-width:100px>Pendientes de Iniciar</h6><h6 style=margin:0>"+p_no_iniciadas+"</h6>");
            $("#btn_en_ejecucion").html("<h6 style=margin:0; min-width:100px>En Ejecución</h6><h6 style=margin:0>"+p_en_ejecucion+"</h6>");
            $("#btn_terminadas").html("<h6 style=margin:0; min-width:100px>Terminadas</h6><h6 style=margin:0>"+p_terminadas+"</h6>");
            
            if(activa_lookahead==1){
                $("#btn_lookahead").addClass('btn-success');
            }
            if(activa_no_iniciadas==1){
                $("#btn_no_iniciadas").addClass('btn-success');
            }
            if(activa_en_ejecucion==1){
                $("#btn_en_ejecucion").addClass('btn-success');
            }
            if(activa_terminadas==1){
                $("#btn_terminadas").addClass('btn-success');
            }
            if(activa_lookahead==0 && activa_no_iniciadas==0 && activa_en_ejecucion==0 && activa_terminadas==0){
                $("#btn_total").addClass('btn-success');
            }
            
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/            

                var alturahoja= $(window).height();
                var alturatabla= ((1.28*alturahoja)-513.21);

                //$("#cuadro2").slideUp("slow");
                //$("#cuadro4").slideUp("slow");
                $("#cuadro3").slideDown("slow");
    //            $("#cuadro1").slideDown("slow");
                var table = $("#dt_general").DataTable({
                    "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                    "destroy":true,
    //                "order":false,
    /*                "autoWidth": true,*/
    /*                "fixedHeader": false,*/
                    "scrollX": true,

    //                console.log($(document).height());
                    "scrollY": alturatabla,
    /*                "scrollCollapse": false,*/
    //                "responsive":true,
                    "paging":false,


                    "ajax":{
                      "method":"POST",
                      "url":"../tareas_periodicas_compuestas/listar_tareas_periodicas_compuestas.php<?php echo "?db=$db&semana=$semana"?>&activa_lookahead="+activa_lookahead+"&activa_no_iniciadas="+activa_no_iniciadas+"&activa_en_ejecucion="+activa_en_ejecucion+"&activa_terminadas="+activa_terminadas
                  },


                    'columnDefs': [
                         {
                            'targets': [0],
                            'width': "1%",
                         },

                         {
                            'targets': [3],
                            'width': "25%",
                         },
                        
                         {
                            'targets': [38],
                            'width': "15%",
                         },

                         {
                            'targets': [0,1,2,4,5,6,7,8,9,10,11,12],
                            'width': "6%",
                         },
                        
                        {
                            'targets': [8],
                            'render': function ( data, type, full, meta ) {
                                var permiso="<?php echo $permiso?>";
                                if(data=="NA" || data==''){
                                    resultado="No Asignado";
                                }else{
                                    resultado=data;
                                }
                                return "<h6>" + resultado + "</h6>";

                            },
                        },
                        
                        {
                            'targets': [9],
                            'render': function ( data, type, full, meta ) {
                                var permiso="<?php echo $permiso?>";
                                if(data=="NA"){
                                    resultado="No Asignado";
                                }else{
                                    resultado=data;
                                }
                                return "<h6>" + resultado + "</h6>";

                            },
                        },
                        
                        {
                            'targets': [10],
                            'render': function ( data, type, full, meta ) {
                                if(data=="" || data==null){
                                    data="";
                                    return "<h6>" + data + "</h6>";
                                }else if(data==0){
                                    data= "No Iniciado"
                                    return "<h6>" + data + "</h6>";
                                }else if(data==0.5){
                                    data= "En Ejecución"
                                    return "<h6>" + data + "</h6>";
                                }else if(data==0.75){
                                    data= "Periodicidad Activa"
                                    return "<h6>" + data + "</h6>";
                                }else if(data==1){
                                    data= "Finalizado"
                                    return "<h6>" + data + "</h6>";
                                }

                            },
                        },
                        
                        {
                            'targets': [3,4,5,6,7,11,38],
                            'render': function ( data, type, full, meta ) {
                                    return "<h6>" + data + "</h6>";
                                }
                        },
                        
                        {
                            'targets': [12],
                            'render': function ( data, type, full, meta ) {
                                data=data*100;
                                data=data.toFixed(0);
                                return "<h6>" + data + "%</h6>";                              
                            },
                        },
                      ],



                    'select': {
                     'style': 'false',
                    },  

                    "lengthMenu": [100],

                    
                "columns":[
                    {"defaultContent":"", "visible":false},
                    {"data":"Consecutivo_en_Programa", "visible":false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Titulo", "visible":false},
                    {"data":"Dias_Inicio"},
                    {"data":"Lookahead"},
                    {"data":"Checklist", "visible":false},
                    {"data":"Periodicidad"},
                    {"data":"Relevancia"},
                    {"data":"Ejecutado"},
                    {"data":"Estado"},
                    {"data":"Estado_Restricciones"},
                    {"data":"R1", "visible":false},
                    {"data":"R2", "visible":false},
                    {"data":"R3", "visible":false},
                    {"data":"R4", "visible":false},
                    {"data":"R5", "visible":false},
                    {"data":"R6", "visible":false},
                    {"data":"R7", "visible":false},
                    {"data":"R8", "visible":false},
                    {"data":"R9", "visible":false},
                    {"data":"R10", "visible":false},
                    {"data":"R11", "visible":false},
                    {"data":"R12", "visible":false},
                    {"data":"R13", "visible":false},
                    {"data":"R14", "visible":false},
                    {"data":"R15", "visible":false},
                    {"data":"R16", "visible":false},
                    {"data":"R17", "visible":false},
                    {"data":"R18", "visible":false},
                    {"data":"R19", "visible":false},
                    {"data":"R20", "visible":false},
                    {"data":"R22", "visible":false},
                    {"data":"R22", "visible":false},
                    {"data":"R23", "visible":false},
                    {"data":"R24", "visible":false},
                    {"data":"R25", "visible":false},
                    {"data":"Observaciones"}                    
                ],
                    
                "createdRow": function( row, data, dataIndex){
                    
                    var inicio_sem = new Date(<?php echo $Fecha_Inicio_Sem ?>);
                    
                    var fin_sem = new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+7);
                                      


                    var inicio_act = new Date(data.Fecha_Inicio );
                    var inicio_act = new Date(inicio_act.getFullYear(),inicio_act.getMonth(),inicio_act.getDate()+1);
                    
                    var fin_act = new Date(data.Fecha_Fin );
                    var fin_act = new Date(fin_act.getFullYear(),fin_act.getMonth(),fin_act.getDate()+1);

                    var fecha_intermedia= new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+49);

/*                    console.log(data.Id,inicio_sem, fin_sem, inicio_act, fin_act, fecha_intermedia);*/
                    
                    if(data.Titulo!=0){
                        $(row).addClass('Titulo');
                    }
                    else if((data.Estado==='Pendiente de Iniciar' || data.Ejecutado>0) && data.Ruta_Critica==="1" && data.Estado!="Terminada Antes" && data.Estado!='Atrasada' && data.Ejecutado<1){
                        $(row).addClass('Semanal_Critica');

                    } 

                    else if((data.Estado==='Pendiente de Iniciar' || data.Estado==='No Puede Comenzar' || data.Ejecutado>0) && data.Ruta_Critica==="0" && data.Estado!="Terminada Antes" && data.Estado!='Atrasada' && data.Ejecutado<1){
                        $(row).addClass('Semanal_No_Critica');
                    } 

                    else if(data.Estado==="Atrasada"){
                        $(row).addClass('Semanal_Atrasada');
                    }

                    else if((data.Dias_Inicio - data.Lookahead)<=0 && data.Estado!="Terminada Antes" && data.Ejecutado<1){
                        $(row).addClass('Intermedia');
                    }
                    
                    else{
                        $(row).addClass('No_Activa');
                    }

                },

                    "language": idioma_espanol
                });
            
                ocultos(table);
                cambiar_posicion(posicion);
                <?php $_SESSION["scroll"]=0 ?>;
                obtener_data_editar("#dt_general tbody", table);
                generar();
        }
        
//        var contar_cajas_checkeadas=function()
        
        
        /*Para agregar un nuevo usuario en la base de datos*/
        var agregar_nuevo_usuario = function(){
            limpiar_datos();
            //$("#cuadro2").slideDown("slow");
            $("#cuadro4").slideDown("slow");
            $("#cuadro3").slideUp("slow");
//            $("#cuadro1").slideUp("slow");   
        }
        
        /*Toma los datos de la fila en la que se presionó el botón editar*/
        var obtener_data_editar=function(tbody, table){
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            var permiso="<?php echo $permiso?>";
            
            if((max_semana-2)>=semana){
                var only_once = false;
            }else{
                if(permiso=="G" || permiso=="S" || permiso=="V" || permiso=="C"){
                    var only_once = false;
                }else{
                    var only_once = true;
                }
            }
            if(only_once==true){
                $(tbody).on("click", "td", function(){
                    var data= table.row($(this).parents("tr")).data();
                    Id=data.Consecutivo_en_Programa;
                    checklist=data.Checklist;
                    opcion="checklist";
                    semana=<?php echo $semana?>;
                    $.ajax({
                        method:"POST",
                        url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
                        contenttype:"charset=utf-8",
                        data: {"Id": Id, "checklist": checklist, "semana": semana, "opcion": opcion}
                    }).done( function( info ){
                        $('#requerimientos').html(info);
                        data_checklist(data);
                    });
                         
                });  
                var only_once = false;
            }
            
        }
        
        var data_checklist=function(data){
            var Id=$(".checklist #Id").val(data.Consecutivo_en_Programa);
            var opcion=$(".checklist #opcion").val("modificar_checklist");
            $(".checklist #checklist").val(data.Checklist).change();
            $("#modal_checklist_Label").html(data.Actividad);
            $(".checklist #semana").val(<?php echo $semana?>).change();
            $("#ejecutado").val(data.Ejecutado).change();
            $("#relevancia").val(data.Relevancia).change();
            $("#periodicidad").val(data.Periodicidad).change();
            $("input[name=R1][value='"+data.R1+"']").prop("checked",true);
            $("input[name=R2][value='"+data.R2+"']").prop("checked",true);
            $("input[name=R3][value='"+data.R3+"']").prop("checked",true);
            $("input[name=R4][value='"+data.R4+"']").prop("checked",true);
            $("input[name=R5][value='"+data.R5+"']").prop("checked",true);
            $("input[name=R6][value='"+data.R6+"']").prop("checked",true);
            $("input[name=R7][value='"+data.R7+"']").prop("checked",true);
            $("input[name=R8][value='"+data.R8+"']").prop("checked",true);
            $("input[name=R9][value='"+data.R9+"']").prop("checked",true);
            $("input[name=R10][value='"+data.R10+"']").prop("checked",true);
            $("input[name=R11][value='"+data.R11+"']").prop("checked",true);
            $("input[name=R12][value='"+data.R12+"']").prop("checked",true);
            $("input[name=R13][value='"+data.R13+"']").prop("checked",true);
            $("input[name=R14][value='"+data.R14+"']").prop("checked",true);
            $("input[name=R15][value='"+data.R15+"']").prop("checked",true);
            $("input[name=R16][value='"+data.R16+"']").prop("checked",true);
            $("input[name=R17][value='"+data.R17+"']").prop("checked",true);
            $("input[name=R18][value='"+data.R18+"']").prop("checked",true);
            $("input[name=R19][value='"+data.R19+"']").prop("checked",true);
            $("input[name=R20][value='"+data.R20+"']").prop("checked",true);
            $("input[name=R21][value='"+data.R21+"']").prop("checked",true);
            $("input[name=R22][value='"+data.R22+"']").prop("checked",true);
            $("input[name=R23][value='"+data.R23+"']").prop("checked",true);
            $("input[name=R24][value='"+data.R24+"']").prop("checked",true);
            $("input[name=R25][value='"+data.R25+"']").prop("checked",true);
            $("#Observaciones").val(data.Observaciones);

            $("#modal_checklist").modal("show");
            /*$("#cuadro2").slideDown("slow");
            $("#cuadro3").slideUp("slow");
            $("#cuadro1").slideUp("slow");*/ 
        }
        
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
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
        var generar=function(){
            $("#btn_generar_cal").on("click", function(){            
                opcion="generar";
                semana=<?php echo $semana?>;
                console.log(opcion);
				$.ajax({
					method:"POST",
					url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"semana": semana, "opcion": opcion}
				}).done( function( info ){
					var json_info = JSON.parse( info );
					console.log(json_info);
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
					url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=<?php echo $db?>",
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
        
        
        var cambiar_clase=function(p){
            console.log(p);
            if(p=='lookahead'){
                if($('#btn_lookahead').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }else if(p=='no_iniciadas'){
                if($('#btn_no_iniciadas').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }else if(p=='en_ejecucion'){
                if($('#btn_en_ejecucion').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }                    
                }
            }else if(p=='terminadas'){
                if($('#btn_terminadas').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }
            location.assign("clase_filtro.php?clase="+p+"&activa="+activa);
        }
        
        var nuevo_requerimiento=function(){
            var html_requerimiento="<div class='form_eval form-group'><h3 id='form_tarea'>Nuevo Requerimiento de Checklist</h3></div><div class='pregunta form-group'><p>Descripción:</p><input type='text' name='descripcion' id='descripcion' style='width:100%; max-width:500px; height:30px; font-size:1em; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey; border-width:1px'><br></div>";
            var html_botones_checklist="<div class='form-group' id='botones_checklist'><div class='botones'><input id='btn_guardar_nuevo_requerimiento' type='submit' class='btn btn-primary' value='Guardar'><input id='btn_cancelar_nuevo_requerimiento' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar'></div></div>";
            
            
            
            
            var Id=$(".checklist #Id").val();
            var opcion=$(".checklist #opcion").val(),
                checklist=$(".checklist #checklist").val(),
                actividad=$("#modal_checklist_Label").html(),
                R1=$('input[name="R1"]:checked').val(),
                R2=$('input[name="R2"]:checked').val(),
                R3=$('input[name="R3"]:checked').val(),
                R4=$('input[name="R4"]:checked').val(),
                R5=$('input[name="R5"]:checked').val(),
                R6=$('input[name="R6"]:checked').val(),
                R7=$('input[name="R7"]:checked').val(),
                R8=$('input[name="R8"]:checked').val(),
                R9=$('input[name="R9"]:checked').val(),
                R10=$('input[name="R10"]:checked').val(),
                R11=$('input[name="R11"]:checked').val(),
                R12=$('input[name="R12"]:checked').val(),
                R13=$('input[name="R13"]:checked').val(),
                R14=$('input[name="R14"]:checked').val(),
                R15=$('input[name="R15"]:checked').val(),
                R16=$('input[name="R16"]:checked').val(),
                R17=$('input[name="R17"]:checked').val(),
                R18=$('input[name="R18"]:checked').val(),
                R19=$('input[name="R19"]:checked').val(),
                R20=$('input[name="R20"]:checked').val(),
                R21=$('input[name="R21"]:checked').val(),
                R22=$('input[name="R22"]:checked').val(),
                R23=$('input[name="R23"]:checked').val(),
                R24=$('input[name="R24"]:checked').val(),
                R25=$('input[name="R25"]:checked').val(),
                observaciones=$('#Observaciones').val(),
                html_previo=$('#modal_checklist').html();
                
                var requerimientos = [R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25];
                var contador=0
                for(i=0; i<25; i++){
                    if(!requerimientos[i]){
                        contador=contador;
                    }else{
                        contador++;
                    }
                }
                
                var html_previo1= html_previo.substring(0,(html_previo.length-1950));
                var html_previo3= html_previo.substring((html_previo.length-1950), html_previo.length);
                //console.log(html_previo1 + html_previo3);

            $('.checklist').removeClass('checklist').addClass('nuevo_requerimiento');
            $('#requerimientos').html(html_requerimiento);
            $( '#botones_checklist' ).html(html_botones_checklist);
            $( '#observaciones_checklist' ).remove();
            $(".nuevo_requerimiento #opcion").val("nuevo_requerimiento");
            
            regresar_checklist(html_previo1, html_previo3, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, contador);
        }
        
        var comprometer_requerimiento=function(consecutivo_requerimiento){        
            var id_tarea=$("#Id_tarea").val();
            var requerimiento="#nombre_R" + consecutivo_requerimiento;
            requerimiento=$(requerimiento).val();
            var checklist_requerimiento=$("#checklist_requerimiento").val();
            var opcion="comprometer_requerimiento";
            console.log(checklist_requerimiento, consecutivo_requerimiento, requerimiento);
            
            var html_comprometer_requerimiento="<div class='form_eval form-group'><h3 id='form_tarea'>Comprometer Requerimiento de Checklist</h3></div><div class='pregunta form-group'><p>¿Desea comprometer el requerimiento ''" + requerimiento + "'' para la presente semana?</p></div><input type='hidden' id='id_tarea' name='id_tarea' value='" + id_tarea + "' </input><input type='hidden' id='requerimiento' name='requerimiento' value='" + requerimiento + "' </input><input type='hidden' id='checklist_requerimiento' name='checklist_requerimiento' value='" + checklist_requerimiento + "' </input><input type='hidden' id='consecutivo_requerimiento' name='consecutivo_requerimiento' value='" + consecutivo_requerimiento + "' </input>";
            var html_botones_checklist="<div class='form-group' id='botones_comprometer_requerimiento'><div class='botones'><input id='btn_guardar_comprometer_requerimiento' type='submit' class='btn btn-primary' value='Comprometer'><input id='btn_cancelar_nuevo_requerimiento' type='button' class='btn btn-danger' data-dismiss='modal' value='Cancelar'></div></div>";
            
            var Id=$(".checklist #Id").val();
            var opcion=$(".checklist #opcion").val(),
                checklist=$(".checklist #checklist").val(),
                actividad=$("#modal_checklist_Label").html(),
                R1=$('input[name="R1"]:checked').val(),
                R2=$('input[name="R2"]:checked').val(),
                R3=$('input[name="R3"]:checked').val(),
                R4=$('input[name="R4"]:checked').val(),
                R5=$('input[name="R5"]:checked').val(),
                R6=$('input[name="R6"]:checked').val(),
                R7=$('input[name="R7"]:checked').val(),
                R8=$('input[name="R8"]:checked').val(),
                R9=$('input[name="R9"]:checked').val(),
                R10=$('input[name="R10"]:checked').val(),
                R11=$('input[name="R11"]:checked').val(),
                R12=$('input[name="R12"]:checked').val(),
                R13=$('input[name="R13"]:checked').val(),
                R14=$('input[name="R14"]:checked').val(),
                R15=$('input[name="R15"]:checked').val(),
                R16=$('input[name="R16"]:checked').val(),
                R17=$('input[name="R17"]:checked').val(),
                R18=$('input[name="R18"]:checked').val(),
                R19=$('input[name="R19"]:checked').val(),
                R20=$('input[name="R20"]:checked').val(),
                R21=$('input[name="R21"]:checked').val(),
                R22=$('input[name="R22"]:checked').val(),
                R23=$('input[name="R23"]:checked').val(),
                R24=$('input[name="R24"]:checked').val(),
                R25=$('input[name="R25"]:checked').val(),
                observaciones=$('#Observaciones').val(),
                html_previo=$('#modal_checklist').html();
            
                var html_previo1= html_previo.substring(0,(html_previo.length-1950));
                var html_previo3= html_previo.substring((html_previo.length-1950), html_previo.length);
            
            $('.checklist').removeClass('checklist').addClass('comprometer_requerimiento');
            $('#requerimientos').html(html_comprometer_requerimiento);
            $( '#botones_checklist' ).html(html_botones_checklist);
            $( '#observaciones_checklist' ).remove();
            $(".comprometer_requerimiento #opcion").val("comprometer_requerimiento");
            
            regresar_checklist(html_previo1, html_previo3, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, 'inactivo');        
        }
        
        var regresar_checklist=function(html_previo1, html_previo3, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, contador){
            $("#btn_cancelar_nuevo_requerimiento").on("click", function(){
                var descripcion=$("#descripcion").val();
                if(descripcion=='' || contador=='inactivo'){
                    contador='inactivo';   
                }else{
                    contador++;
                }
                if(contador=='inactivo'){
                    html_previo2='';
                }else{
                    html_previo2="<div class='pregunta form-group'><div class='cuerpo_pregunta' style='width:73%; padding:0; margin:0; display:inline-block'><p>"+descripcion+":</p><input type='radio' name='R"+contador+"' id='R"+contador+"' value='0' checked=''> No<br><input type='radio' name='R"+contador+"' id='R"+contador+"' value='1'> Si<br><input type='radio' name='R"+contador+"' id='R"+contador+"' value='D'> No Aplica<br><br><p>Indique la dirección del archivo (si aplica):</p><input type='url' name='url_R"+contador+"' id='url_R"+contador+"' placeholder='https://...' ;='' style='width:100%; max-width:500px; height:30px; font-size:1em; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey; border-width:1px' value=''><br></div><div class='boton_comprometer_tarea' style='width:25%; margin-left:5px; display:inline-block'><button id='btn_comprometer_R"+contador+"' type='button' class='btn btn-success btn-sm' aria-pressed='true' onclick='comprometer_requerimiento("+contador+")' style='float:left; margin:0px 0px 10px 10px'><i class='fas fa-handshake'></i> Comprometer</button></div><input type='hidden' id='nombre_R"+contador+"' value='hola1' <='' input=''></div>";
                }
                //console.log("html_previo: " + html_previo2);
                
                $('#modal_checklist').html(html_previo1+html_previo2+html_previo3);
                $("input[name=R1][value='"+R1+"']").prop("checked",true);
                $("input[name=R2][value='"+R2+"']").prop("checked",true);
                $("input[name=R3][value='"+R3+"']").prop("checked",true);
                $("input[name=R4][value='"+R4+"']").prop("checked",true);
                $("input[name=R5][value='"+R5+"']").prop("checked",true);
                $("input[name=R6][value='"+R6+"']").prop("checked",true);
                $("input[name=R7][value='"+R7+"']").prop("checked",true);
                $("input[name=R8][value='"+R8+"']").prop("checked",true);
                $("input[name=R9][value='"+R9+"']").prop("checked",true);
                $("input[name=R10][value='"+R10+"']").prop("checked",true);
                $("input[name=R11][value='"+R11+"']").prop("checked",true);
                $("input[name=R12][value='"+R12+"']").prop("checked",true);
                $("input[name=R13][value='"+R13+"']").prop("checked",true);
                $("input[name=R14][value='"+R14+"']").prop("checked",true);
                $("input[name=R15][value='"+R15+"']").prop("checked",true);
                $("input[name=R16][value='"+R16+"']").prop("checked",true);
                $("input[name=R17][value='"+R17+"']").prop("checked",true);
                $("input[name=R18][value='"+R18+"']").prop("checked",true);
                $("input[name=R19][value='"+R19+"']").prop("checked",true);
                $("input[name=R20][value='"+R20+"']").prop("checked",true);
                $("input[name=R21][value='"+R21+"']").prop("checked",true);
                $("input[name=R22][value='"+R22+"']").prop("checked",true);
                $("input[name=R23][value='"+R23+"']").prop("checked",true);
                $("input[name=R24][value='"+R24+"']").prop("checked",true);
                $("input[name=R25][value='"+R25+"']").prop("checked",true);
                
                guardar();
            });
        }
        
        var cnc=function(causa){
            var categoria=$("#Categoria_CNC").val(),
                opcion="CNC",
                CNC=causa;
                console.log(CNC);
                if(categoria===""){
                    $('#CNC').attr('disabled', true); 
                } else{
                    $('#CNC').attr('disabled', false); 
                    $.ajax({
                        method:"POST",
                        url: "../tareas_periodicas_compuestas/guardar_tareas_periodicas_compuestas.php?db=login",
                        contenttype:"charset=utf-8",
                        data: {"categoria": categoria, "opcion":opcion},
                        success:function(a){
                            $('#CNC').html(a);
                            $("#CNC option[value='"+CNC+"']").attr('selected', true);
                        }
                    }); 
                }  
            }
        
        var cambiar_posicion=function(p){
            $('#dt_general').on( 'draw.dt', function () {
                $('.dataTables_scrollBody').scrollTop(p);
            } );
        }
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
            }else if(permiso=="G"){
                $('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
            }else if(permiso=="S"){
                $('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
            }else if(permiso=="V"){
                $('#btn_guardar, #btn_guardar1').css('display', 'none');
                $('#btn_listar, #btn_listar1').removeClass('btn btn-danger').addClass('btn btn-primary');
                $('#btn_listar, #btn_listar1').val('Cerrar').change();
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="C"){
                $('#btn_guardar, #btn_guardar1').css('display', 'none');
                $('#btn_listar, #btn_listar1').removeClass('btn btn-danger').addClass('btn btn-primary');
                $('#btn_listar, #btn_listar1').val('Cerrar').change();
                $('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
                table.column( 0 ).visible( false );
            }
            
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            
            if((max_semana-2)>=semana){
                //$('#btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
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