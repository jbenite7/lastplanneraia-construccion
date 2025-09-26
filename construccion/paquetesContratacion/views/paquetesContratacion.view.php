<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Last Planner AIA</title>
	<link rel="shortcut icon" href="../imagenes/florAIA.png">
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">

    <!-- Fuentes de Google-->
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">

    <!-- Font Awesome (Íconos)-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">


    <!--Iniciar estilos de Bootstrap-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">

    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../css/styles2.css">

	<!-- Estilos Buttons DataTables -->
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css">

    <!-- Checkboxes DataTables -->
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">

    <!--Selector de fechas -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css">

		<!-- Lista desplegable con buscador -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>




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
                $Fecha_Fin_Sem=date("Y-m-d",strtotime($Fecha_Inicio_Sem ."+6 days"));
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

                //require ("../conexion.php");
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
            mysqli_close($conexion);
            ?>
            <li><img src="../imagenes/logoHorizontal.png" width="40%"></li>

            <?php
            require("../conexion.php");
            $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
            $resultado2= mysqli_query($conexion, $query2);
            if(!$resultado2){
            }else{
                $data=mysqli_fetch_assoc($resultado2);
                $Fecha_Inicio_SemYMD=$data["Fecha_Inicio_Sem"];
                $Fecha_Fin_SemYMD=$data["Fecha_Fin_Sem"];
            }
            mysqli_close($conexion);
            ?>
            <li><h1 class="titulo">Last Planner AIA - <?php echo "$proyecto"?></h1></li>
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
                    <li><img src="../imagenes/florAIA.png" width="" class="d-inline-block align-top" alt=""></li>
                    <li class="lps">LPS</li>
                    <li class="pagina"> - Información General / Paquetes de Contratación</li>
                </ul>
              </a>
              <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                  <li class="nav-item">
                    <a class="nav-link" href="<?php echo"../cambiar_pagina.php?seccion=contenido&semana=$semana"?>">Contenido</a>
                  </li>
                  <li class="nav-item dropdown active">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Información General</a>
                    <div class="dropdown-menu show" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_profesionales&semana=$semana"?>">Profesionales AIA</a>
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_subcontratistas&semana=$semana"?>">Sub-Contratistas</a>
												<a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_listadoActividades&semana=$semana"?>">Listado de Actividades</a>
												<a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_contratos&semana=$semana"?>">Contratos</a>
												<!-- <a class="dropdown-item active" href="<?php //echo"../cambiar_pagina.php?seccion=info_paquetesContratacion&semana=$semana"?>">Paquetes de Contratación</a> -->
												<a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=planCompras&semana=$semana"?>">Plan de Compras</a>
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
                        //mysqli_close($conexion);
                        if ($conteo==0){
                        } else if($conteo>0){
                            for($i=$conteo; $i>=1; $i--){
                                //require("../conexion.php");
                                $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$i";
                                $resultado2= mysqli_query($conexion, $query2);
                                $data=mysqli_fetch_assoc($resultado2);
                                $ini=$data["Fecha_Inicio_Sem"];
                                $fin=$data["Fecha_Fin_Sem"];
                                //mysqli_close($conexion);
                                if($i==$semana){
                                    if(($Max_Semana-2)>=$i){
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=informe_productividad&semana=$i'>Indicadores de Productividad</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores de Last Planner</a>
                                            </div>
                                        </li>";
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=informe_productividad&semana=$i'>Indicadores de Productividad</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores de Last Planner</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=informe_productividad&semana=$i'>Indicadores de Productividad</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores de Last Planner</a>
                                            </div>
                                        </li>";
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=informe_productividad&semana=$i'>Indicadores de Productividad</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores de Last Planner</a>
                                            </div>
                                        </li>";
                                    }
                                }
                            };
                        };
                        mysqli_close($conexion);
                    ?>

                  <li class="nav-item">
                    <a class="nav-link" href="../cerrar.php" tabindex="-1" aria-disabled="true">Cerrar Sesión</a>
                  </li>
                </ul>
              </div>
            </nav>
        </div>
    </div>

	<div class="row">
		<div id="cuadro3" class="cuadro3 col-lg-12">
			<form class="form-botones" action="" method="POST">


                <!--Se crean los botones Guardar y Listar-->
				<!-- <div class="form-group">
					<div class="col-sm-offset-1 col-sm-8">
						<input id="btn_nuevo" type="button" class="btn btn-primary btn-sm" title="Registrar nueva actividad del proyecto" value="Nueva Actividad">
					</div>
				</div> -->

				<div class="form-group" style="margin-bottom:5px">
					<div class="col-sm-offset-1">
						<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:30%;display:inline-block; ">
								<input id="btn_nuevo" type="hidden" class="btn btn-primary btn-sm" title="Registrar nueva actividad del proyecto" value="Nueva Actividad" data-toggle='modal' data-target='#modalNuevaActividad'>
            </div>

            <div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%">
                <!-- <input id="btn_agregar_indicadores" type="button" class="btn btn-primary btn-sm" value="Indicadores Parciales" style="margin-right:5px; margin-left:0" data-toggle="modal" data-target="#modalindicadores" onClick="ind_compromisos_semana('')"> -->

                <div class="grupo_botones_semanal btn-group" role="group" aria-label="Basic example">
                    <button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href='../listadoActividades/listadoActividades.php'">Actividades <i class="fas fa-arrow-right fa-m"></i></button>
                    <button id="btn_contratos" type="button" class="btn btn-success btn-sm" onclick="window.location.href='../contratos/posicion_contratos.php?posicion_contratos=0'">Contratos <i class="fas fa-arrow-right fa-m"></i></button>
                    <!-- <button id="btn_paquetesContratacion" type="button" class="btn btn-success btn-sm active" onclick="window.location.href='../paquetesContratacion/paquetesContratacion.php'">Paquetes de Contratación <i class="fas fa-arrow-right fa-m"></i></button> -->
										<button id="btn_planCompras" type="button" class="btn btn-success btn-sm" onclick="window.location.href='../pdc/pdc.php'">Plan de Compras</button>
                </div>
            </div>

					</div>
				</div>


			</form>

            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class="col-sm-offset-2 col-sm-12">
				<p id="mensajeActualizacion"></p>
			</div>

		</div>
	</div>
    <div id="Espacio_luego_de_grupo_botones"></div>


    <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row">
		<div id="cuadro1" class="col-sm-12 col-md-12 col-lg-12">
			<div class="table-responsive table-condensed table-bordered col-sm-12">
				<table id="dt_cliente" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
	            <th></th>
							<th>Tipo de Paquete</th>
							<th>Paquete de Contratacion</th>
							<th>Contratos</th>
							<th>Fecha de Inicio</th>
							<th>Titulo</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>

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
                    </div>-->
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
					</div>
				</div>
			</div>
		</form>
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

		<!-- Lista desplegable con buscador -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->


	<script>

        /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function(){
      //$("#cuadro2").hide();
			ocultos();
      listar();
		});

    /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
    $("#btn_listar").one("click", function(){
        listar();
        limpiar_datos();
    });

    /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
    $("#btn_cancelar, #btn_cancelar1").on("click", function(){
      location.reload();
    });

    var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
      $("#inicio_sem").val("");
		}


    /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar=function(){
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/
            var alturahoja= $(window).height();
            var alturatabla= ((-0.000084 * Math.pow(alturahoja, 2)) + (0.178199 * alturahoja) - 28).toFixed(0) + "vh";

            var table = $("#dt_cliente").DataTable({
                "dom": 'ifrt',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,

//                console.log($(document).height());

                "scrollY": alturatabla,
/*                "scrollCollapse": false,*/
                "responsive":true,
                "paging":false,

                "ajax":{
                  "method":"POST",
                  "url":"../paquetesContratacion/listar_paquetesContratacion.php?db=<?php echo $db?>&Semana=<?php echo $semana ?>"
              },

                "lengthMenu": [100, 200, 500],

                'columnDefs': [
									{
											'targets': [4],
											'width':'5%',
									},
									{
											'targets': [1,2,3,4,5],
											'width':'10%',
											'render': function ( data, type, full, meta ) {
											 return "<h6>" + data + "</h6>";
											},
									},
                ],

								"createdRow": function( row, data, dataIndex){
                    if(data.titulo!=0){
                        $(row).addClass('Titulo');
                    }
                },

                "columns":[
                    {"defaultContent":"", "visible":false},
										{"data":"tipoPaquete"},
										{"data":"paqueteContratacion"},
										{"data":"contratos"},
										{"data":"fechaInicio"},
										{"data":"titulo", "visible":false},
                ],

                "language": idioma_espanol
            });
        }


        var nueva_sem=function(){
					$("#btn_guardar_nueva_sem").on("click", function(){
						f_inicio_sem=$("#inicio_sem").val(),
            opcion="nueva_sem";
            console.log(f_inicio_sem);
						$.ajax({
							method:"POST",
							url: "../paquetesContratacion/guardar_paquetesContratacion.php?db=<?php echo $db?>",
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
					url: "../paquetesContratacion/guardar_paquetesContratacion.php?db=<?php echo $db?>",
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
                $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');
            }else if(permiso=="G"){
                $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');
            }else if(permiso=="S"){
                $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');
            }else if(permiso=="SG"){
                $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');
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
            "sInfo":           "Mostrando  _TOTAL_ registros",
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
