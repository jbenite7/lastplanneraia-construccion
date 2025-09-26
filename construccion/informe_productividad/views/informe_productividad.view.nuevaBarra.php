<!DOCTYPE html>
<html lang="es">
<head id="head">
	<!--Script cque va al archivo linksComunesHead2.js-->
	<script type="text/javascript" src="../funciones_generales/js/linksComunesHead2.js" charset="utf-8"></script>
</head>

<!--Etiqueta superior-->
<body>

	<div class="encabezado" id="encabezado">
		<input type="hidden" name="seccion" id="seccion" value="informe_productividad">
		<input type="hidden" id="Id" name="Id" value="0">
		<input type="hidden" id="opcion" name="opcion" value="registrar">
		<input type="hidden" id="scriptBarraFiltros" name="scriptBarraFiltros" value="">
	</div>

	<div class="row direccionSeccion">
		<div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto" id="textoDireccionSeccion" style="text-align:left">
		</div>
	</div>

  <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "formulario_nuevo", que contiene el formulario de registro de nuevas actividades, el cual permanecerá oculto hasta que se presione el botón "Agregar Actividad" -->
	<div class="row formularioRegistro">
	</div>

	<div class="row filaBotones">
		<div class="col-sm-8 col-md-8 col-lg-8 ml-auto mr-0 p-0" id="filaBotones">
		</div>
	</div>

  <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row tabla" id="contenedorInformeProductividad" style="margin:10px auto; width:100%; overflow: auto">
		<div id="div_Seguimiento_Ejecucion" style="width: 100%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
		  <form class="form form-horizontal" action="" method="POST">
		    <div class="form_eval form-group">
		      <h3 id="form_adm_Seguimiento_Ejecucion">Seguimiento Ejecución</h3>
		    </div>
		    <div class="cambio_codigo_actividad" style="width:600px; max-width:33%; display: inline-block;">
		      <label for="cambio_codigo_actividad" class="col-sm-12 control-label">Seleccione la Actividad de Seguimiento</label>
		      <div class="col-sm-12">
						<select id="cambio_codigo_actividad" name="cambio_codigo_actividad" class="form-control" onchange="listar('general')">
							<?php
                require("../conexion.php");
								$db = $_SESSION["db"];
								$semana = $_SESSION["semana"];
                $query="SELECT DISTINCT codigo_actividad FROM $db"."_programa_consolidado WHERE (codigo_actividad IS NOT NULL AND codigo_actividad != '') AND Semana<=$semana";
                $resultado= mysqli_query($conexion, $query);
                $query1="SELECT * FROM general_codigos_actividades WHERE ";
                while ($valores = mysqli_fetch_array($resultado)){
	                $codigo_actividad=$valores["codigo_actividad"];

	                $query1 .= "codigo_actividad='$codigo_actividad' OR ";
                };

                $query1 = substr($query1, 0, -3);

                $resultado1= mysqli_query($conexion, $query1);
                while ($valores1 = mysqli_fetch_array($resultado1)){
                  echo '<option value="'.$valores1["codigo_actividad"].'">'.$valores1["codigo_actividad"] . " - " .$valores1["actividad"].'</option>';
                };
                mysqli_close($conexion);
            ?> </select>
		      </div>
		    </div>
		  </form>
		  <div id="Seguimiento_Ejecucion" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		  <div id="Seguimiento_Ejecucion_Proyectado" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		</div>
		<div id="div_Seguimiento_Rendimientos" style="width: 100%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
		  <form class="form form-horizontal" action="" method="POST">
		    <div class="form_eval form-group">
		      <h3 id="form_adm_Seguimiento_Rendimientos">Rendimiento General</h3>
		    </div>
		    <div class="grupo_oficiales_teorico form-group form-inline" style="display:inline-block; margin-left:40px; margin-right:20px; margin-top:20px; padding: 5px; border-style: solid; border-width: 1px; border-color:rgba(55,68,81,1); border-radius:5px">
		      <label for="oficiales_teorico"><small>Número de Oficiales<br>Por Cuadrilla<br>Típica</small> </label>
		      <input type="number" id="oficiales_teorico" name="oficiales_teorico" class="form-control" placeholder='Oficiales por Cuadrilla Típica' value=1 autocomplete="off" style="margin-bottom:10px; width:70px">
		    </div>
		    <div class="grupo_ayudantes_teorico form-group form-inline" style="display:inline-block; margin-left:20px; margin-right:20px; margin-top:20px; padding: 5px; border-style: solid; border-width: 1px; border-color:rgba(55,68,81,1); border-radius:5px">
		      <label for="ayudantes_teorico"><small>Número de Ayudantes<br>Por Cuadrilla<br>Típica</small></label>
		      <input type="number" id="ayudantes_teorico" name="ayudantes_teorico" class="form-control" placeholder='Ayudantes por Cuadrilla Típica' value=1 autocomplete="off" style="margin-bottom:10px; width:70px">
		    </div>
		    <div class="grupo_rendimiento_cuadrilla_tipica_teorico form-group form-inline" style="display:inline-block; margin-left:20px; margin-right:20px; margin-top:20px; padding: 5px; border-style: solid; border-width: 1px; border-color:rgba(55,68,81,1); border-radius:5px">
		      <label for="rendimiento_cuadrilla_tipica_teorico" id="label_rendimiento_cuadrilla_tipica_teorico"></label>
		      <input type="number" id="rendimiento_cuadrilla_tipica_teorico" name="rendimiento_cuadrilla_tipica_teorico" class="form-control" placeholder='Rendimiento por Cuadrilla Típica' value=1 autocomplete="off" style="margin-bottom:10px; width:150px">
		    </div>
		    <div class="grupo_cuadrilla_tipica_teorico form-group form-inline" style="display:inline-block; margin-left:20px; margin-right:20px; margin-top:20px; padding: 5px; border-style: solid; border-width: 1px; border-color:rgba(55,68,81,1); border-radius:5px">
		      <label for="cuadrilla_tipica_teorico"><small>Número de Cuadrillas<br>Típicas Requeridas<br>desde Planeación</small></label>
		      <input type="number" id="cuadrilla_tipica_teorico" name="cuadrilla_tipica_teorico" class="form-control" placeholder='Cuadrillas Típicas Requeridas desde Planeación' value=1 autocomplete="off" style="margin-bottom:10px; width:70px">
		    </div>
		    <div class="comparar_cuadrilla_tipica form-group form-inline" style="display:inline-block; margin-left:5px; margin-right:30px; margin-top:20px">
		      <button id="btn_comparar_cuadrilla_tipica" type="button" class="btn btn-primary" style="margin-right:5px; margin-left:0" onclick="comparar('general');">Comparar</button>
		    </div>
		  </form>
		  <div id="Seguimiento_Rendimientos" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		</div>
		<div id="div_Composicion_Cuadrillas" style="width: 100%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
		  <form class="form form-horizontal" action="" method="POST">
		    <div class="form_eval form-group">
		      <h3 id="form_adm_Composicion_Cuadrillas">Composición de Cuadrillas</h3>
		    </div>
		  </form>
		  <div id="Composicion_Cuadrillas_Tipicas" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		  <div id="Composicion_Oficiales" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		  <div id="Composicion_Ayudantes" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		</div>
		<div id="div_Consumo_Horas_Hombre" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
		  <form class="form form-horizontal" action="" method="POST">
		    <div class="form_eval form-group">
		      <h3 id="form_adm_Consumo_Horas_Hombre">Consumo de Horas-Hombre</h3>
		    </div>
		  </form>
		  <div id="Consumo_Horas_Oficial" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		  <div id="Consumo_Horas_Ayudante" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px">
		  </div>
		</div>
	</div>

	<div class="row ventanasModalesSemana" id="ventanasModalesSemana">
	</div>

	<div class="row ventanasModalesEspecificas" id="ventanasModalesEspecificas">
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
			ocultos();
			listar("general");
		}

		/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
		var listar = function(nombre) {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;

			contenedorInformeProductividad

			var alturahoja = $(window).height();
			var posicionInicioInforme = document.getElementById('encabezado').getBoundingClientRect().height +document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;

			document.getElementById('contenedorInformeProductividad').style.height = (alturahoja - posicionInicioInforme - 30) + "px";;

			if (nombre == "general") {
			  var codigo_actividad = $("#cambio_codigo_actividad").val();
			  var jsonData = $.ajax({
			    method: "POST",
			    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
			    dataType: "json",
			    async: false,
			    data: {
			      "opcion": "importar_cuadrilla_tipica",
			      "codigo_actividad": codigo_actividad
			    }
			  }).responseText;
			  var array = JSON.parse(jsonData);
			  $("#oficiales_teorico").val(array["oficiales_tipica"]).change();
			  $("#ayudantes_teorico").val(array["ayudantes_tipica"]).change();
			  $("#rendimiento_cuadrilla_tipica_teorico").val(array["rendimiento_tipica"]).change();
			  $("#cuadrilla_tipica_teorico").val(array["numero_cuadrillas_tipicas"]).change();
			  grafico_Seguimiento_Ejecucion(nombre, db, semana);
			}
		}

		var comparar = function(nombre) {
			var db = document.getElementById('baseDatos').value;
			var semana = document.getElementById('semana').value;
		  if (nombre == "general") {
		    var codigo_actividad = $("#cambio_codigo_actividad").val();
		    var oficiales_tipica = $("#oficiales_teorico").val();
		    var ayudantes_tipica = $("#ayudantes_teorico").val();
		    var rendimiento_tipica = $("#rendimiento_cuadrilla_tipica_teorico").val();
		    var numero_cuadrillas_tipicas = $("#cuadrilla_tipica_teorico").val();
		    var jsonData = $.ajax({
		      method: "POST",
		      url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		      dataType : "json",
		      async: false,
		      data: {
		        "opcion": "exportar_cuadrilla_tipica",
		        "codigo_actividad": codigo_actividad,
		        "oficiales_tipica": oficiales_tipica,
		        "ayudantes_tipica": ayudantes_tipica,
		        "rendimiento_tipica": rendimiento_tipica,
		        "numero_cuadrillas_tipicas": numero_cuadrillas_tipicas
		      }
		    }).responseText;
		    var array = JSON.parse(jsonData);
		    grafico_Seguimiento_Ejecucion(nombre, db, semana);
		  }
		}

		var grafico_Seguimiento_Ejecucion = function(nombre, db, semana) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Ejecucion";
		  var opcion1 = "Seguimiento_Ejecucion_Proyectado";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  //console.log(codigo_actividad)
		  var jsonData0 = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": "unidad_actividad",
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad
		    }
		  }).responseText;
		  var unidad_actividad = JSON.parse(jsonData0);
		  var titulo_eje_y = "Cantidad (" + unidad_actividad + ")";
		  $("#label_rendimiento_cuadrilla_tipica_teorico").html("<small>Rendimiento<br>Planeado Por <br>Cuadrilla Típica<br><b>(" + unidad_actividad + "/Cuadrilla-día)</b></small>")
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad
		    }
		  }).responseText;
		  var jsonData1 = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion1,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var data1 = new google.visualization.DataTable(jsonData1);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'][Posicion_ultima_semana]['c'][0]['v'];
		    ultima_semana = ultima_semana.substring(7, ) * 1;
		    //console.log(ultima_semana);
		    var Semana_comparar = semana ;
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    if (Semana_comparar > ultima_semana && nombre == "general") {
		      alert("No se genera el informe hasta que las actividades comprometidas durante la semana se hayan calificado. Se redirigirá hacia la programación semanal");
		      $('#div_grafico_PAC, #div_Pareto_CNC, #div_Semana_CNC, #div_ind_compromisos, #div_restricciones, #div_calificaciones_integrales').css('display', 'none');
		      window.location.href = '../programacion_semanal/programacion_semanal.php';
		    } else {
		      if (!array['rows']) {} else {
		        //var maximo= (Math.max(array['rows'][ultima_semana-1]['c'][1]['v'], array['rows'][ultima_semana-1]['c'][3]['v'], array['rows'][ultima_semana-1]['c'][4]['v']));
		      }
		      var options = {
		        title: ("Ejecución Hasta la Semana " + Semana_comparar),
		        vAxis: {
		          title: titulo_eje_y,
		          format: '',
		          minValue: 0.0,
		          maxValue: 1.0,
		          titleTextStyle: {
		            fontSize: 16,
		            bold: true
		          },
		          gridlines: {
		            count: 10
		          }
		        },
		        hAxis: {
		          title: 'Semanas',
		          titleTextStyle: {
		            fontSize: 16,
		            bold: true
		          }
		        },
		        seriesType: 'bars',
		        series: {
		          0: {
		            color: "rgb(55,86,54)"
		          },
		          1: {
		            type: 'line',
		            color: "rgb(55,86,54)"
		          },
		          2: {
		            type: 'line',
		            color: "rgb(191,215,48)",
		            lineDashStyle: [4, 4]
		          }
		        },
		        legend: {
		          position: 'right',
		          textStyle: {
		            fontSize: 12
		          }
		        },
		        // isStacked: true
		      };
		      var options1 = {
		        title: ("Proyección Hasta la Fecha de Fin Teórica"),
		        vAxis: {
		          title: titulo_eje_y,
		          format: '',
		          minValue: 0.0,
		          maxValue: 1.0,
		          titleTextStyle: {
		            fontSize: 16,
		            bold: true
		          },
		          gridlines: {
		            count: 10
		          }
		        },
		        hAxis: {
		          title: 'Semanas',
		          titleTextStyle: {
		            fontSize: 16,
		            bold: true
		          }
		        },
		        seriesType: 'bars',
		        series: {
		          1: {
		            color: "rgb(55,86,54)"
		          },
		          2: {
		            type: 'line',
		            color: "rgb(55,86,54)"
		          },
		          3: {
		            type: 'line',
		            color: "rgb(191,215,48)",
		            lineDashStyle: [4, 4]
		          },
		          4: {
		            color: "grey"
		          },
		          0: {
		            type: 'line',
		            color: "grey",
		            lineDashStyle: [4, 4]
		          }
		        },
		        legend: {
		          position: 'right',
		          textStyle: {
		            fontSize: 12
		          }
		        },
		        // isStacked: true
		      };
		      var chart = new google.visualization.ComboChart(document.getElementById('Seguimiento_Ejecucion'));
		      chart.draw(data, options);
		      var chart1 = new google.visualization.ComboChart(document.getElementById('Seguimiento_Ejecucion_Proyectado'));
		      chart1.draw(data1, options1);
		      grafico_Seguimiento_Rendimientos(nombre, db, semana, unidad_actividad);
		    }
		  }
		}

		var grafico_Seguimiento_Rendimientos = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Rendimientos";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var oficiales_teorico = $("#oficiales_teorico").val();
		  var ayudantes_teorico = $("#ayudantes_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  var rendimiento_cuadrilla_tipica_teorico = $("#rendimiento_cuadrilla_tipica_teorico").val();
		  //console.log(oficiales_teorico, cuadrilla_tipica_teorico, rendimiento_cuadrilla_tipica_teorico);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "oficiales_teorico": oficiales_teorico,
		      "ayudantes_teorico": ayudantes_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico,
		      "rendimiento_cuadrilla_tipica_teorico": rendimiento_cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    var titulo_eje_y = unidad_actividad + " / Cuadrilla-día";
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Rendimiento Diario Por Cuadrilla Típica",
		      vAxis: {
		        title: titulo_eje_y,
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: (maximo * 10)
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Seguimiento_Rendimientos'));
		    chart.draw(data, options);
		    grafico_Seguimiento_Cuadrillas_Tipicas(nombre, db, semana, unidad_actividad);
		  }
		}
		var grafico_Seguimiento_Cuadrillas_Tipicas = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Cuadrillas_Tipicas";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var oficiales_teorico = $("#oficiales_teorico").val();
		  var ayudantes_teorico = $("#ayudantes_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  //console.log(codigo_actividad);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "oficiales_teorico": oficiales_teorico,
		      "ayudantes_teorico": ayudantes_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Cuadrillas Típicas Ejecutando la Actividad Por Día",
		      vAxis: {
		        title: 'Número de Cuadrillas Típicas Diarias',
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: 10
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Composicion_Cuadrillas_Tipicas'));
		    chart.draw(data, options);
		    grafico_Seguimiento_Oficiales(nombre, db, semana, unidad_actividad);
		  }
		}
		var grafico_Seguimiento_Oficiales = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Oficiales";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var oficiales_teorico = $("#oficiales_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  //console.log(codigo_actividad);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "oficiales_teorico": oficiales_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Oficiales Ejecutando la Actividad Por Día",
		      vAxis: {
		        title: 'Oficiales / Día',
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: 10
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Composicion_Oficiales'));
		    chart.draw(data, options);
		    grafico_Seguimiento_Ayudantes(nombre, db, semana, unidad_actividad)
		  }
		}
		var grafico_Seguimiento_Ayudantes = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Ayudantes";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var ayudantes_teorico = $("#ayudantes_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  //console.log(codigo_actividad);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "ayudantes_teorico": ayudantes_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Ayudantes Ejecutando la Actividad Por Día",
		      vAxis: {
		        title: 'Ayudantes / Día',
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: 10
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Composicion_Ayudantes'));
		    chart.draw(data, options);
		    grafico_Seguimiento_Consumo_Oficiales(nombre, db, semana, unidad_actividad);
		  }
		}
		var grafico_Seguimiento_Consumo_Oficiales = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Consumo_Oficiales";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var oficiales_teorico = $("#oficiales_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  var rendimiento_cuadrilla_tipica_teorico = $("#rendimiento_cuadrilla_tipica_teorico").val();
		  //console.log(codigo_actividad);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "oficiales_teorico": oficiales_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico,
		      "rendimiento_cuadrilla_tipica_teorico": rendimiento_cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    var titulo_eje_y = "Horas-Oficial / " + unidad_actividad;
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Consumo de Horas-Oficial Por " + unidad_actividad,
		      vAxis: {
		        title: titulo_eje_y,
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: 10
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Consumo_Horas_Oficial'));
		    chart.draw(data, options);
		    grafico_Seguimiento_Consumo_Ayudantes(nombre, db, semana, unidad_actividad);
		  }
		}
		var grafico_Seguimiento_Consumo_Ayudantes = function(nombre, db, semana, unidad_actividad) {
		  google.charts.load('current', {
		    'packages': ['corechart']
		  });
		  google.charts.setOnLoadCallback(drawVisualization);
		  var opcion = "Seguimiento_Consumo_Ayudantes";
		  var codigo_actividad = $("#cambio_codigo_actividad").val();
		  var ayudantes_teorico = $("#ayudantes_teorico").val();
		  var cuadrilla_tipica_teorico = $("#cuadrilla_tipica_teorico").val();
		  var rendimiento_cuadrilla_tipica_teorico = $("#rendimiento_cuadrilla_tipica_teorico").val();
		  //console.log(codigo_actividad);
		  var jsonData = $.ajax({
		    method: "POST",
		    url: "listar_informe_productividad.php?db=" + db + "&semana=" + semana,
		    dataType : "json",
		    async: false,
		    data: {
		      "opcion": opcion,
		      "nombre": nombre,
		      "codigo_actividad": codigo_actividad,
		      "ayudantes_teorico": ayudantes_teorico,
		      "cuadrilla_tipica_teorico": cuadrilla_tipica_teorico,
		      "rendimiento_cuadrilla_tipica_teorico": rendimiento_cuadrilla_tipica_teorico
		    }
		  }).responseText;

		  function drawVisualization() {
		    // Some raw data (not necessarily accurate)
		    var data = new google.visualization.DataTable(jsonData);
		    var array = JSON.parse(jsonData);
		    var Posicion_ultima_semana = (array['rows'].length) - 1;
		    //console.log(Posicion_ultima_semana);
		    var ultima_semana = array['rows'].length;
		    //ultima_semana = ultima_semana.substring(7,)*1;
		    //console.log(ultima_semana);
		    //console.log(nombre);
		    //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];
		    //console.log(Semana_comparar, ultima_semana, nombre);
		    var titulo_eje_y = "Horas-Ayudante / " + unidad_actividad;
		    if (!array['rows']) {} else {
		      var maximo = (Math.max(array['rows'][ultima_semana - 1]['c'][1]['v'], array['rows'][ultima_semana - 1]['c'][3]['v'], array['rows'][ultima_semana - 1]['c'][4]['v']));
		      //console.log(maximo);
		    }
		    var options = {
		      title: "Consumo de Horas-Ayudante Por " + unidad_actividad,
		      vAxis: {
		        title: titulo_eje_y,
		        format: '',
		        minValue: 0.0,
		        maxValue: maximo,
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        },
		        gridlines: {
		          count: 10
		        }
		      },
		      hAxis: {
		        title: 'Semanas',
		        titleTextStyle: {
		          fontSize: 16,
		          bold: true
		        }
		      },
		      seriesType: 'lines',
		      series: {
		        0: {
		          color: "rgb(55,86,54)",
		          pointSize: 10
		        },
		        1: {
		          type: 'line',
		          color: "rgb(191,215,48)"
		        },
		        2: {
		          type: 'line',
		          color: "grey",
		          lineDashStyle: [4, 4]
		        }
		      },
		      legend: {
		        position: 'right',
		        textStyle: {
		          fontSize: 12
		        }
		      },
		      // isStacked: true
		    };
		    var chart = new google.visualization.ComboChart(document.getElementById('Consumo_Horas_Ayudante'));
		    chart.draw(data, options);
		  }
		}

		var ocultos=function(table){
			var max_semana = document.getElementById('Max_Semana').value;
			var semana = document.getElementById('semana').value;
		  var permiso = document.getElementById('permiso').value;

			if(permiso=="R"){
					//$('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
			}else if(permiso=="G"){
					//$('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
			}else if(permiso=="S"){
					//$('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
			}else if(permiso=="V"){
					$('.nueva_sem, .eliminar_sem').css('display', 'none');
			}else if(permiso=="C"){
					$('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
			}
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

	</script>
</body>
</html>
