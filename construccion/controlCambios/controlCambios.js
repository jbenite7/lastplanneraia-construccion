/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
$(document).on("ready", function() {
    $("#formulario_nuevo").hide();
    cargarDatosGeneralesPagina(document.getElementById('seccion').value);
    selectoresFecha("inputFechaSolicitud");
    selectoresFecha("inputFechaEntregaInterventoria");
    selectoresFecha("inputFechaTentativaDefinicion");
    selectoresFecha("inputFechaDefinicion");
});

var cargaParametros = function() {
    $('#S1,#S2,#S3,#S4,#S5,#paqueteS1,#paqueteS2,#paqueteS3,#paqueteS4,#paqueteS5').select2({
        tags: true,
        placeholder: '',
        allowClear: true
    });
    listar();
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
    $("#btn_cancelarOrden").on("click", function(e) {
        e.preventDefault();
        recargarTabla("listar");
        //obtener_data_editar("#dt_cliente tbody", table);
    });
}


/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
var listar = function() {
    var db = document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana').value;
    var Max_Semana = document.getElementById('Max_Semana').value;
    /*Identificamos la altura de la hoja para determinar la altura de la tabla*/
    var alturahoja = $(window).height();
    var posicionInicioTabla = document.getElementById('encabezado').getBoundingClientRect().height + document.getElementById('textoDireccionSeccion').getBoundingClientRect().height;
    document.getElementById('cuadroTabla').style.height = (alturahoja - posicionInicioTabla - 200) + "px";

    var alturatabla = (alturahoja - posicionInicioTabla - 170) + "px";

    var table = $("#dt_cliente").DataTable({
        /* "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>", */
        "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>>t<'row'<'col-md-6'i>><'clear'>",
        "destroy": true,
        "ordering": false,
        "autoWidth": true,
        "fixedHeader": false,
        "scrollX": true,
        //                console.log($(document).height());
        "scrollY": alturatabla,
        /*                "scrollCollapse": false,*/
        "responsive": true,
        "paging": false,
        "ajax": {
            "method": "POST",
            "url": "../controlCambios/listar_controlCambios.php?db=" + db + "&semana=" + Max_Semana
        },
        "lengthMenu": [100, 200, 500],
        'columnDefs': [{
                'targets': [2],
                'render': function(data, type, row, meta) {
                    var solicitanteCambio = row["solicitanteCambio"];
                    var arraySolicitanteCambio = {
                        "1": "Obra",
                        "2": "Cliente",
                        "3": "Interventoría",
                        "4": "Otro",
                        "": ""
                    };
                    if (solicitanteCambio == 4) {
                        return arraySolicitanteCambio[solicitanteCambio] + " [" + row["detalleSolicitanteOtro"] + "]";
                    } else {
                        return arraySolicitanteCambio[solicitanteCambio];
                    }

                }
            },
            {
                'targets': [5],
                'render': function(data, type, row, meta) {
                    var prioridad = row["prioridad"];
                    var arrayPrioridad = {
                        "1": "Alta",
                        "2": "Media",
                        "3": "Baja",
                        "": ""
                    };
                    return arrayPrioridad[prioridad];
                }
            },
            {
                'targets': [6],
                'width': '10%',
                'render': function(data, type, row, meta) {
                    if (!row["tipoCambio"]){
                        var tipoCambio = "";
                        listaCambios = tipoCambio;
                        return listaCambios;
                    }else{
                        var tipoCambio = JSON.parse(row["tipoCambio"]);
                        tipoCambio = Array(tipoCambio["tiposCambio"]);
                        var listaCambios = "";
                        //console.log(JSON.parse(row["tipoCambio"]));
                        tipoCambio.forEach( // loop through your array 
                            function(element) {
                                for (key in element) { // for every key in the current object
                                    if (element[key] === '0') { // if it's valued to '0'

                                    } else {
                                        listaCambios += key + ", ";
                                    }
                                }
                            }

                        );
                        listaCambios = listaCambios.substring(0, listaCambios.length - 2);
                        return listaCambios;
                    }
                }
            },
            {
                'targets': [7],
                'render': function(data, type, row, meta) {
                    var responsableSolucion = row["responsableSolucion"];
                    var arrayResponsableSolucion = {
                        "1": "Obra",
                        "2": "Cliente",
                        "3": "Interventoría",
                        "4": "Otro",
                        "": ""
                    };
                    if (responsableSolucion == 4) {
                        return arrayResponsableSolucion[responsableSolucion] + " [" + row["detalleResponsableSolucion"] + "]";
                    } else {
                        return arrayResponsableSolucion[responsableSolucion];
                    }

                }
            },
            {
                'targets': [13, 14, 15, 16, 17],
                'render': function(data, type, row, meta) {
                    data = formatCurrencyTabla(data);
                    return data;
                }
            },
            {
                'targets': [27],
                'render': function(data, type, row, meta) {
                    var aprobacion = row["aprobacion"];
                    var arrayAprobacion = {
                        "1": "Aprobado",
                        "2": "Aprobado con Restricciones",
                        "3": "No Aprobado",
                        "4": "En Estudio",
                        "5": "Desistido",
                        "": ""
                    };

                    return arrayAprobacion[aprobacion]
                }
            },
        ],
        "columns": [{
                "defaultContent": "<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm'  title='Eliminar' style='margin:1px' data-toggle='modal' data-target='#modalEliminar' ><i class='fa fa-trash-alt fa-xs'></i></button>"
            },
            {
                "data": "id"
            },
            {
                "data": "solicitanteCambio"
            },
            {
                "data": "detalleSolicitanteOtro",
                "visible": false
            },
            {
                "data": "fechaSolicitud"
            },
            {
                "data": "prioridad"
            },
            {
                "data": "tipoCambio"
            },
            {
                "data": "responsableSolucion"
            },
            {
                "data": "detalleResponsableSolucion",
                "visible": false
            },
            {
                "data": "justificacion",
                "visible": false
            },
            {
                "data": "descripcion"
            },
            {
                "data": "incidenciaAlcance",
                "visible": false
            },
            {
                "data": "tiempoCronograma",
                "visible": false
            },
            {
                "data": "tiempoCronogramaAfectado",
                "visible": false
            },
            {
                "data": "incidenciaCronograma",
                "visible": false
            },
            {
                "data": "valorPresupuesto",
                "visible": false
            },
            {
                "data": "costoDirecto",
                "visible": false
            },
            {
                "data": "costoDirectoAIU",
                "visible": false
            },
            {
                "data": "costoDirectoAIUIVA"
            },
            {
                "data": "valorAprobado"
            },
            {
                "data": "incidenciaPresupuesto",
                "visible": false
            },
            {
                "data": "incidenciaCalidad",
                "visible": false
            },
            {
                "data": "incidenciaRiesgo",
                "visible": false
            },
            {
                "data": "fechaTentativaDefinicion"
            },
            {
                "data": "fechaEntregaInterventoria"
            },
            {
                "data": "Observaciones",
                "visible": false
            },
            {
                "data": "fechaDefinicion"
            },
            {
                "data": "aprobacion"
            },
            {
                "data": "soportes",
                "visible": false
            }
        ],
        "language": idioma_espanol
    });

    $("div.toolbarFilaBotones").html(`<div class="grupo_botones1" role="group" aria-label="Basic example" style="padding:5; max-width:30%;display:inline-block; "><button id="btn_nuevaODC" type="button" class="btn btn-primary btn-sm">Nueva Orden de Cambio <i class="fas fa-plus fa-lg"></i></button><button id="btn_informe_compromisos" type="button" class="btn btn-warning btn-sm" style="margin-right:5px; margin-left:5px" onclick="descargarConsolidadoODC()">Imprimir Consolidado <i class="fas fa-print fa-lg"></i></button><button id="btn_hipervinculoODC" type="button" class="btn btn-secondary btn-sm" style="margin-right:5px">Ir a Carpeta <i class="fas fa-external-link-alt"></i></button></div><div class="grupo_botones_semanal_madre"  style="padding:5; max-width:69%"></div>`);

    /* $("div.toolbarFilaMensajes").html('<p id="mensajeActualizacion"></p>');

    $("div.toolbarFiltro").html('<div style="display:flex; margin-left:auto"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm" style="margin-right:5px; margin-left:auto; max-width:60%" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger" style="margin-right:5px; margin-left:0; display: none; max-width:40%"><i class="fas fa-times-circle"></i> Limpiar</button></div>'); */

    maestroPermisos(document.getElementById('permiso').value);
    // activarBuscador("#dt_cliente tbody", table);
    obtener_data_editar("#dt_cliente tbody", table);
    obtener_id_eliminar("#dt_cliente tbody", table);
    nuevaODC();
    urlCarpetaCambios();

    // Filtros de texto
    $('#buscadorTipoCambio').on('keyup', function() {
        table.column(6).search($('#buscadorTipoCambio').val()).draw();
    });
    $('#buscadorCostoDirecto').on('keyup', function() {
        table.column(18).search($('#buscadorCostoDirecto').val()).draw();
    });
    $('#buscadorValorAprobado').on('keyup', function() {
        table.column(19).search($('#buscadorValorAprobado').val()).draw();
    });

    // Filtro por fecha
    $('#buscadorFechaSolicitud').on('change', function() {
        table.column(4).search($('#buscadorFechaSolicitud').val()).draw();
    });
    $('#buscadorFechaTentativaDefinicion').on('change', function() {
        table.column(23).search($('#buscadorFechaTentativaDefinicion').val()).draw();
    });
    $('#buscadorFechaEntregaInterventoria').on('change', function() {
        table.column(24).search($('#buscadorFechaEntregaInterventoria').val()).draw();
    });
    $('#buscadorFechaDefinicion').on('change', function() {
        table.column(26).search($('#buscadorFechaDefinicion').val()).draw();
    });

    // Filtro por lista desplegable
    $('#buscadorSolicitanteCambio').on('change', function() {
        table.column(2).search($('#buscadorSolicitanteCambio').val()).draw();
    });
    $('#buscadorPrioridad').on('change', function() {
        table.column(5).search($('#buscadorPrioridad').val()).draw();
    });
    $('#buscadorResponsableDefinicion').on('change', function() {
        table.column(7).search($('#buscadorResponsableDefinicion').val()).draw();
    });
    $('#buscadorAprobacion').on('change', function() {
        table.column(27).search($('#buscadorAprobacion').val()).draw();
    });
}

/*Toma los datos de la fila en la que se presionó el botón editar*/
var obtener_data_editar = function(tbody, table) {
    var permiso = document.getElementById('permiso').value;
    var db = document.getElementById('baseDatos').value;
    if (permiso == "C") {
        var only_once = false;
    } else {
        var only_once = true;
    }
    $(tbody).one("click", "td", function(e) {
        e.stopPropagation();
        var data = table.row($(this).parents("tr")).data();
        var contador = table.rows(  ).count();
        var json_prueba = table.rows(  ).data();
        /* console.log(JSON.stringify(json_prueba[0])); */

       /*  console.log(json_prueba[0].fechaSolicitud = "2022-10-04"); */

        var Id = $("#Id").val(data.Id),
            opcion = $("#opcion").val("modificar");
        if (only_once == true) {

            document.getElementById("inputConsecutivo").value = data.id;
            document.getElementById("Id").value = data.id;
            document.getElementById("inputProyecto").value = document.getElementById("proyecto").value;

            $.ajax({
                type: "POST",
                url: "../controlCambios/guardar_controlCambios.php?db="+db,
                contenttype: "charset=utf-8",                                                                                                     
                data: {"opcion":"obtenerNombreDirector"},
                success: function(dataDirector) {
                    var json_info = JSON.parse(dataDirector);
                    document.getElementById("inputDirector").value = json_info;
                },
                error: function(dataDirector) {
                    document.getElementById("inputDirector").value = "Indefinido";
                }
            });

            
            document.getElementById("inputFechaSolicitud").value = data.fechaSolicitud;

            document.querySelector('input[name="inputSolicitanteCambio"][value="' + data.solicitanteCambio + '"]').checked = true;

            if (data.solicitanteCambio == 4) {
                document.getElementById("inputDetalleSolicitanteOtro").disabled = false;
                document.getElementById("inputDetalleSolicitanteOtro").value = data.detalleSolicitanteOtro;
            } else {
                document.getElementById("inputDetalleSolicitanteOtro").disabled = true;
                document.getElementById("inputDetalleSolicitanteOtro").value = '';
            }

            document.querySelector('input[name="inputPrioridad"][value="' + data.prioridad + '"]').checked = true;

            var tiposCambio = JSON.parse(data.tipoCambio);
            document.getElementById("inputTipoCambioAlcance").checked = tiposCambio["tiposCambio"]["Alcance"] == 1 ? true : false;
            document.getElementById("inputTipoCambioCronograma").checked = tiposCambio["tiposCambio"]["Cronograma"] == 1 ? true : false;
            document.getElementById("inputTipoCambioCosto").checked = tiposCambio["tiposCambio"]["Costo"] == 1 ? true : false;
            document.getElementById("inputTipoCambioCalidad").checked = tiposCambio["tiposCambio"]["Calidad"] == 1 ? true : false;
            document.getElementById("inputTipoCambioRiesgo").checked = tiposCambio["tiposCambio"]["Riesgo"] == 1 ? true : false;
            document.getElementById("inputTipoCambioRecurso").checked = tiposCambio["tiposCambio"]["Recurso"] == 1 ? true : false;

            document.querySelector('input[name="inputResponsableSolucion"][value="' + data.responsableSolucion + '"]').checked = true;

            if (data.responsableSolucion == 4) {
                document.getElementById("inputDetalleResponsableSolucion").disabled = false;
                document.getElementById("inputDetalleResponsableSolucion").value = data.detalleResponsableSolucion;
            } else {
                document.getElementById("inputDetalleResponsableSolucion").disabled = true;
                document.getElementById("inputDetalleResponsableSolucion").value = '';
            }

            document.getElementById("inputJustificacion").value = data.justificacion;
            contadorTextarea(document.getElementById("inputJustificacion"), "contadorJustificacion", 500);
            document.getElementById("inputDescripcion").value = data.descripcion;
            contadorTextarea(document.getElementById("inputDescripcion"), "contadorDescripcion", 500);

            //Incidencia en el alcance
            document.getElementById("inputIncidenciaAlcance").value = data.incidenciaAlcance;
            contadorTextarea(document.getElementById("inputIncidenciaAlcance"), "contadorIncidenciaAlcance", 500);

            //Incidencia en el cronograma
            document.querySelectorAll("#inputTiempoCronograma, #inputTiempoCronogramaAfectado").forEach(function(checkbox) {
                checkbox.addEventListener("keyup", function() {
                    document.getElementById("inputPorcentajeAfectacionCronograma").value =(((document.getElementById("inputTiempoCronogramaAfectado").value / document.getElementById("inputTiempoCronograma").value))*100).toFixed(2)+"%";
                });
            });
            document.getElementById("inputTiempoCronograma").value = data.tiempoCronograma;
            document.getElementById("inputTiempoCronogramaAfectado").value = data.tiempoCronogramaAfectado;
            document.getElementById("inputPorcentajeAfectacionCronograma").value = (((document.getElementById("inputTiempoCronogramaAfectado").value / document.getElementById("inputTiempoCronograma").value))*100).toFixed(2)+"%";
            document.getElementById("inputIncidenciaCronograma").value = data.incidenciaCronograma;
            contadorTextarea(document.getElementById("inputIncidenciaCronograma"), "contadorIncidenciaCronograma", 500);

            //Incidencia en el presupuesto
            document.querySelectorAll("#inputValorAprobado, #inputValorPresupuesto").forEach(function(checkbox) {
                checkbox.addEventListener("keyup", function() {
                    document.getElementById("inputPorcentajeAfectacionPresupuesto").value = (((document.getElementById("inputValorAprobado").value.replace(/[\$,]/g, '') / document.getElementById("inputValorPresupuesto").value.replace(/[\$,]/g, '')))*100).toFixed(2)+"%";
                });
            });
            document.getElementById("inputValorPresupuesto").value = data.valorPresupuesto;
            document.getElementById("inputCostoDirecto").value = data.costoDirecto;
            document.getElementById("inputCostoDirectoAIU").value = data.costoDirectoAIU;
            document.getElementById("inputCostoDirectoAIUIVA").value = data.costoDirectoAIUIVA;
            document.getElementById("inputValorAprobado").value = data.valorAprobado;
            document.getElementById("inputIncidenciaPresupuesto").value = data.incidenciaPresupuesto;
            document.getElementById("inputPorcentajeAfectacionPresupuesto").value = (((document.getElementById("inputValorAprobado").value.replace(/[\$,]/g, '') / document.getElementById("inputValorPresupuesto").value.replace(/[\$,]/g, '')))*100).toFixed(2)+"%";
            contadorTextarea(document.getElementById("inputIncidenciaPresupuesto"), "contadorIncidenciaPresupuesto", 500);

            //Incidencia en la calidad
            document.getElementById("inputIncidenciaCalidad").value = data.incidenciaCalidad;
            contadorTextarea(document.getElementById("inputIncidenciaCalidad"), "contadorIncidenciaCalidad", 500);

            //Incidencia en el riesgo
            document.getElementById("inputIncidenciaRiesgo").value = data.incidenciaRiesgo;
            contadorTextarea(document.getElementById("inputIncidenciaRiesgo"), "contadorIncidenciaRiesgo", 500);

            //Incidencia en el recurso
            document.getElementById("inputIncidenciaRecurso").value = data.incidenciaRecurso;
            contadorTextarea(document.getElementById("inputIncidenciaRecurso"), "contadorIncidenciaRecurso", 500);

            document.getElementById("inputFechaEntregaInterventoria").value = data.fechaEntregaInterventoria;
            document.getElementById("inputFechaTentativaDefinicion").value = data.fechaTentativaDefinicion;
            document.querySelector('input[name="inputAprobacion"][value="' + data.aprobacion + '"]').checked = true;
            document.getElementById("inputFechaDefinicion").value = data.fechaDefinicion;

            formatCurrency($("#inputValorPresupuesto"));
            formatCurrency($("#inputCostoDirecto"));
            formatCurrency($("#inputCostoDirectoAIU"));
            formatCurrency($("#inputCostoDirectoAIUIVA"));
            formatCurrency($("#inputValorAprobado"));

            $("#modalordenDeCambio").modal("show");
            //console.log(data.soportes);

            $("#modalordenDeCambio").on("shown.bs.modal", function(){
                var tableSoportesData = JSON.parse(data.soportes);
                

                var tableSoportes = $("#dt_soportes").DataTable({
                    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-12'>><'clear'>",
                    "destroy": true,
                    "ordering": false,
                    "autowidth": true,
                    "scrollX": true,

                    "fixedHeader": false,

                    "responsive": true,
                    "data": tableSoportesData.soportes,
                    /* "ajax": {
                        "method": "POST",
                        "url": "../controlCambios/listar_controlCambios.php?db=" + db + "&semana=" + Max_Semana
                    }, */
                    "lengthMenu": [100, 200, 500],
                    'columnDefs': [
                        {
                            'targets': [0, 3],
                            'className': "dt-center",
                            'width': "10%"
                        },

                        {
                            'targets': [1, 2],
                            'width': "40%"
                        },
                    ],
                    "columns": [
                        {
                            "data": "consecutivo",
                            "visible": true
                        },
                        {
                            "data": "descripcion",
                            'render': function(data, type, row, meta) {
                                data = "<input type='text' class='soporteDescripcion' style='width:98%' value=" + data + ">";
            
                                return data;
                            }
                        },
                        {
                            "data": "link",
                            'render': function(data, type, row, meta) {
                                data = "<input type='text' class='soporteLink' style='width:98%' value=" + data + ">";
            
                                return data;
                            }
                        },
                        {
                            "defaultContent": "<button type='button' class='abrirSoporte btn btn-secondary btn-sm mr-2'  title='Abrir Soporte' onclick='abrirSoporte(this.parentElement)'><i class='fas fa-external-link-alt' aria-hidden='true'></i></button><button type='button' class='borrarSoporte btn btn-danger btn-sm'  title='Borrar'><i class='fa fa-minus' aria-hidden='true'></i></button>"
                        }
                    ],
                    "language": idioma_espanol
                });

                tableSoportes.columns.adjust().draw();
                editarSoporte();
                borrarSoporte(); 
            });

            
        }
        cancelarEdicionFila();
    });
}

/*Abre el formulario para registrar una nueva orden de cambio*/
var nuevaODC = function() {
    $("#btn_nuevaODC").on("click", function() {
        document.getElementById('opcion').value = "nuevo";
        document.getElementById('Id').value = "";

        var permiso = document.getElementById('permiso').value;
        var db = document.getElementById('baseDatos').value;
        if (permiso == "C") {
            var only_once = false;
        } else {
            var only_once = true;
        }
        if (only_once == true) {

        }

        $("#modalordenDeCambio").modal("show");

        var table = $("#dt_cliente").DataTable();
        document.getElementById("inputConsecutivo").value = Math.max.apply(null, table.column(1, { search: 'applied' }).data().toArray()) + 1;
        document.getElementById("inputProyecto").value = document.getElementById("proyecto").value;

        $.ajax({
            type: "POST",
            url: "../controlCambios/guardar_controlCambios.php?db="+db,
            contenttype: "charset=utf-8",                                                                                                     
            data: {"opcion":"obtenerNombreDirector"},
            success: function(dataDirector) {
                var json_info = JSON.parse(dataDirector);
                document.getElementById("inputDirector").value = json_info;
            },
            error: function(dataDirector) {
                document.getElementById("inputDirector").value = "Indefinido";
            }
        });

            
            document.getElementById("inputFechaSolicitud").value = new Date().toISOString().split('T')[0];
            
            document.getElementById("radioSolicitanteCambio").addEventListener("change", function() {
                if (document.getElementById("radioSolicitanteCambio").value == 4) {
                    document.getElementById("inputDetalleSolicitanteOtro").disabled = false;
                    document.getElementById("inputDetalleSolicitanteOtro").value = "";
                } else {
                    document.getElementById("inputDetalleSolicitanteOtro").disabled = true;
                    document.getElementById("inputDetalleSolicitanteOtro").value = "";
                }
            });

            document.getElementById("inputTipoCambioAlcance").checked = false;
            document.getElementById("inputTipoCambioCronograma").checked = false;
            document.getElementById("inputTipoCambioCosto").checked = false;
            document.getElementById("inputTipoCambioCalidad").checked = false;
            document.getElementById("inputTipoCambioRiesgo").checked = false;
            document.getElementById("inputTipoCambioRecurso").checked = false;

           
            document.getElementById("radioResponsableSolucion").addEventListener("change", function() {
                if (document.getElementById("radioResponsableSolucion").value == 4) {
                    document.getElementById("inputDetalleResponsableSolucion").disabled = false;
                    document.getElementById("inputDetalleResponsableSolucion").value = "";
                } else {
                    document.getElementById("inputDetalleResponsableSolucion").disabled = true;
                    document.getElementById("inputDetalleResponsableSolucion").value = '';
                }
            });
            

            document.getElementById("inputJustificacion").value = "";
            contadorTextarea(document.getElementById("inputJustificacion"), "contadorJustificacion", 500);
            document.getElementById("inputDescripcion").value = "";
            contadorTextarea(document.getElementById("inputDescripcion"), "contadorDescripcion", 500);
            document.getElementById("inputIncidenciaAlcance").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaAlcance"), "contadorIncidenciaAlcance", 500);
            document.getElementById("inputTiempoCronograma").value = "";
            document.getElementById("inputTiempoCronogramaAfectado").value = "";
            document.getElementById("inputPorcentajeAfectacionCronograma").value = "";
            document.querySelectorAll("#inputTiempoCronograma, #inputTiempoCronogramaAfectado").forEach(function(checkbox) {
                checkbox.addEventListener("keyup", function() {
                    document.getElementById("inputPorcentajeAfectacionCronograma").value =(((document.getElementById("inputTiempoCronogramaAfectado").value / document.getElementById("inputTiempoCronograma").value))*100).toFixed(2)+"%";
                });
            });
            document.getElementById("inputIncidenciaCronograma").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaCronograma"), "contadorIncidenciaCronograma", 500);
            document.getElementById("inputValorPresupuesto").value = "";
            document.getElementById("inputCostoDirecto").value = "";
            document.getElementById("inputCostoDirectoAIU").value = "";
            document.getElementById("inputCostoDirectoAIUIVA").value = "";
            document.getElementById("inputValorAprobado").value = "";
            document.querySelectorAll("#inputValorAprobado, #inputValorPresupuesto").forEach(function(checkbox) {
                checkbox.addEventListener("keyup", function() {
                    document.getElementById("inputPorcentajeAfectacionPresupuesto").value =(((document.getElementById("inputValorAprobado").value.replace(/[\$,]/g, '') / document.getElementById("inputValorPresupuesto").value.replace(/[\$,]/g, '')))*100).toFixed(2)+"%";
                });
            });
            document.getElementById("inputIncidenciaPresupuesto").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaPresupuesto"), "contadorIncidenciaPresupuesto", 300);
            document.getElementById("inputIncidenciaCalidad").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaCalidad"), "contadorIncidenciaCalidad", 500);
            document.getElementById("inputIncidenciaRiesgo").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaRiesgo"), "contadorIncidenciaRiesgo", 500);
            document.getElementById("inputIncidenciaRecurso").value = "";
            contadorTextarea(document.getElementById("inputIncidenciaRecurso"), "contadorIncidenciaRecurso", 500);

            document.getElementById("inputFechaEntregaInterventoria").value = "";
            document.getElementById("inputFechaTentativaDefinicion").value = "";
           
            document.getElementById("inputFechaDefinicion").value =  "";

            $("#modalordenDeCambio").on("shown.bs.modal", function(){                

                var tableSoportes = $("#dt_soportes").DataTable({
                    "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-12'>><'clear'>",
                    "destroy": true,
                    "ordering": false,
                    "autowidth": true,
                    "scrollX": true,

                    "fixedHeader": false,

                    "responsive": true,
                    "data": [{ consecutivo: "1", descripcion: "", link: "" }],

                    "lengthMenu": [100, 200, 500],
                    'columnDefs': [
                        {
                            'targets': [0, 3],
                            'className': "dt-center",
                            'width': "10%"
                        },

                        {
                            'targets': [1, 2],
                            'width': "40%"
                        },
                    ],
                    "columns": [
                        {
                            "data": "consecutivo",
                            "visible": true
                        },
                        {
                            "data": "descripcion",
                            'render': function(data, type, row, meta) {
                                data = "<input type='text' class='soporteDescripcion' style='width:98%' value=" + data + ">";
            
                                return data;
                            }
                        },
                        {
                            "data": "link",
                            'render': function(data, type, row, meta) {
                                data = "<input type='text' class='soporteLink' style='width:98%' value=" + data + ">";
            
                                return data;
                            }
                        },
                        {
                            "defaultContent": "<button type='button' class='abrirSoporte btn btn-secondary btn-sm mr-2'  title='Abrir Soporte' onclick='abrirSoporte(this.parentElement)'><i class='fas fa-external-link-alt' aria-hidden='true'></i></button><button type='button' class='borrarSoporte btn btn-danger btn-sm'  title='Borrar'><i class='fa fa-minus' aria-hidden='true'></i></button>"
                        }
                    ],
                    "language": idioma_espanol
                });

                tableSoportes.columns.adjust().draw();
                editarSoporte();
                borrarSoporte(); 
            });

            formatCurrency($("#inputValorPresupuesto"));
            formatCurrency($("#inputCostoDirecto"));
            formatCurrency($("#inputCostoDirectoAIU"));
            formatCurrency($("#inputCostoDirectoAIUIVA"));
            formatCurrency($("#inputValorAprobado"));
    });
}

var agregarSoporte = function(){
    var tableSoportes = $("#dt_soportes").DataTable();
    var contador = tableSoportes.rows(  ).count() + 1;
    tableSoportes.rows.add([{'consecutivo': contador, 'descripcion': '', 'link': ''}]).draw();    
}

var abrirSoporte = function( soporte ){
    var tableSoportes = $("#dt_soportes").DataTable();

    var link = tableSoportes.row( soporte ).data().link;
    if(link != ""){
        window.open(link,'_blank');
    }
}


var editarSoporte = function(){
    var tableSoportes = $("#dt_soportes").DataTable();

    $("#dt_soportes tbody").on("change", "td", function(e) {
        e.stopPropagation();
        //var colIndex = tableSoportes.cell(this).index().column;
        //var rowIndex = tableSoportes.cell(this).index().row;

        var nuevoValor = this.children[0].value;

        tableSoportes.cell(this).data(nuevoValor).draw();
    });
}

var borrarSoporte = function(){
    var tableSoportes = $("#dt_soportes").DataTable();

    $("#dt_soportes tbody").on("click", ".borrarSoporte", function(e) {
        e.stopPropagation();
        var rowIndex = tableSoportes.cell( this.parentElement ).index().row;

        var tableLength =  tableSoportes.rows(  ).count();

        if(tableLength > rowIndex){
            for(i = (rowIndex + 1); i < (tableLength); i++){
                tableSoportes.cell(i, 0).data(i).draw();
            }
        }
        tableSoportes.row( this.parentElement ).remove().draw()
    });
}


/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
var guardar_modificar = function() {

    var db = document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana').value;
    var opcion = document.getElementById('opcion').value;
    var frm = $(".formOrdenCambio").serialize();
    var tableSoportes = $("#dt_soportes").DataTable();
    var soportes = "";
    var j = 1;
    for(i = 0; i < tableSoportes.rows(  ).count(); i++){
        if(tableSoportes.row( i ).data().descripcion != "" && tableSoportes.row( i ).data().link != ""){
            var soporte = tableSoportes.row( i ).data();
            soporte.consecutivo = (j);
            soportes += JSON.stringify(soporte) + ",";
            j++;
        }
    }
    soportes = "{\"soportes\": [" + soportes.substring(0, (soportes.length - 1)) + "]}";
    frm = frm + "&soportes=" + soportes + "&opcion=" + opcion + "&semana=" + semana;
    //console.log(frm);
    $.ajax({
        method: "POST",
        url: "../controlCambios/guardar_controlCambios.php?db=" + db,
        contenttype: "charset=utf-8",
        data: frm,
    }).done(function(info) {
        //console.log(info);
        var json_info = JSON.parse(info);
        mostrar_mensaje(json_info);

        if (json_info.respuesta == "BIEN") {
            // var posicion = $('.dataTables_scrollBody').scrollTop();
            $("#modalordenDeCambio").modal("hide");
            // location.assign("posicion_contratos.php?posicion_contratos=" + posicion);
            recargarTabla();
        } else {
            $(".mensaje").html(json_info["respuesta"]).css({
                "color": "#C9302C"
            });
            $(".mensaje").fadeOut(5000, function() {
                $(this).html("");
                $(this).fadeIn(3000);
            });
        }
    });
}

/*Toma los datos de la fila en la que se presionó el botón eliminar*/
var obtener_id_eliminar=function(tbody, table){
    var permiso = document.getElementById('permiso').value;
    if(permiso=="G" || permiso=="S" || permiso=="SG" || permiso=="OT" || permiso=="DCV" || permiso=="V" || permiso=="C"){
    }else{
        $(tbody).on("click", "button.eliminar", function(){
            $("#modalEliminar").modal("show");
            var data= table.row($(this).parents("tr")).data();
            var idEliminar=$("#Id").val(data.id);
            setTimeout(() => {
                var opcion=$("#opcion").val("eliminar");
                console.log(idEliminar.val(), opcion.val());
            }, 500);
            
            $("#modal-body-texto-eliminar").html("¿Desea eliminar de la programación semanal la orden de cambio Número "+idEliminar.val()+"?");
        });
    }
}

/* Ejecuta la funcion eliminar_duplicar, solo cuando se presionan los botones eliminar o duplicar en cada uno de los registros. La función eliminar_duplicar busca el id de el registro en el que se presinó los botones eliminar o duplicar y lo envia por medio de AJAX para que se ejecute la funcion eliminar o duplicar en guardar.php*/
var eliminar = function() {
    var db = document.getElementById('baseDatos').value;
    var Id = document.getElementById('Id').value;
    var opcion = "eliminar";
    console.log("funciona", opcion, Id);
    $.ajax({
        method: "POST",
        url: "../controlCambios/guardar_controlCambios.php?db="+db,
        contenttype: "charset=utf-8",
        data: {
        "Id": Id,
        "opcion": opcion
    }
    }).done(function(info) {
        // console.log("ok");
        // limpiar_datos();
        recargarTabla("listar");
        $("#modalordenDeCambio").modal("hide");
        $("#modalEliminar").modal("hide");
    });
}

/*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
var mostrar_mensaje = function(informacion) {
    var texto = "",
        color = "";
    if (informacion.respuesta == "BIENNuevaActividad" || informacion.respuesta == "BIENCargarExcel") {
        texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
        color = "#379911";
    }
    if (informacion.respuesta == "ERROR") {
        texto = "<strong>Error</strong>, no se ejecutó la consulta.";
        color = "#C9302C";
    }
    if (informacion.respuesta == "EXISTE") {
        texto = "<strong>Información!</strong> La actividad que estás intentando registrar ya existe.";
        color = "#C9302C";
    }
    if (informacion.respuesta == "VACIO") {
        texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
        color = "#C9302C";
    }
    if (informacion.respuesta == "NO_ELIMINAR") {
        texto = "<strong>Advertencia!</strong> No se puede eliminar esta actividad.";
        color = "#C9302C";
    }
    if (informacion.respuesta == "BIENNuevaActividad") {
        //$("#cuadro2").slideUp("slow");
        //$("#cuadro1").slideDown("slow");
        //$("#cuadro3").slideDown("slow");
        $("#modalNuevaActividad").modal("hide");
        $("#mensajeActualizacion").html(texto).css({
            "color": color
        });
        $("#mensajeActualizacion").fadeOut(10000, function() {
            $(this).html("");
            $(this).fadeIn(3000);
        });
    } else if (informacion.respuesta == "BIENCargarExcel") {
        //$("#cuadro2").slideUp("slow");
        //$("#cuadro1").slideDown("slow");
        //$("#cuadro3").slideDown("slow");
        $("#modalCargarExcel").modal("hide");
        $("#mensajeActualizacion").html(texto).css({
            "color": color
        });
        $("#mensajeActualizacion").fadeOut(10000, function() {
            $(this).html("");
            $(this).fadeIn(3000);
        });
    } else if (informacion.respuesta == "NO_ELIMINAR") {
        $("#mensajeActualizacion").html(texto).css({
            "color": color
        });
        $("#mensajeActualizacion").fadeOut(10000, function() {
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
        // $('#dt_cliente').empty();
        // listar();
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

// Calcula cuantos caracteres faltan para llegar al límite de los textarea
function contadorTextarea(field, field2, maxlimit) {
    var countfield = document.getElementById(field2);
    if (field.value.length > maxlimit) {
        field.value = field.value.substring(0, maxlimit);
        return false;
    } else {
        countfield.innerHTML = (field.value.length) + " de " + maxlimit + " caracteres permitidos.";
    }
}

// Agrega la URL de la carpeta en drive donde están las ordenes de cambio guardadas
function urlCarpetaCambios() {
    var db = document.getElementById('baseDatos').value;
    $.ajax({
        type: "POST",
        url: "../controlCambios/guardar_controlCambios.php?db="+db,
        contenttype: "charset=utf-8",                                                                                                     
        data: {"opcion":"obtenerURLCambios"},
        success: function(dataURLCambios) {
            var json_info = JSON.parse(dataURLCambios);
            console.log(json_info);
            if (json_info === null){
                document.getElementById("btn_hipervinculoODC").onclick = function() {
                    window.open('https://www.aia.com.co', '_blank');
                };
            }else{
                document.getElementById("btn_hipervinculoODC").onclick = function() {
                    window.open(json_info, '_blank');
                };
            }
        },
        error: function(dataURLCambios) {
            document.getElementById("btn_hipervinculoODC").onclick = function() {
                window.open('https://www.aia.com.co', '_blank');
            };
        }
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


function formatCurrencyTabla(input, blur) {
    if (input == null || input == "") {
        return '';
    } else {
        var myNumeral = numeral(input);
        myNumeral = myNumeral.format('$0,0');
        return myNumeral;
    }
}

function formatCurrency(input, blur) {
    input_val = input.val();
    if (input_val == null || input_val == "") {
        input.val('');
    } else {
        var myNumeral = numeral(input_val);
        myNumeral = myNumeral.format('$0,0');
        input.val(myNumeral);
    }

}

//Crear PDF
document.getElementById("btn_generarPDFOrden").onclick = function() {
    var w = document.getElementById("modalordenDeCambioContent").offsetWidth;
    var h = document.getElementById("modalordenDeCambioContent").offsetHeight;
    var doc = new jsPDF('portrait', 'px', 'letter');

    var width = doc.internal.pageSize.getWidth();
    var height = doc.internal.pageSize.getHeight();


    //Añadir Logo de AIA
    var logoAIA = new Image();
    logoAIA.src = '../imagenes/logoHorizontal.png';
    var wLogoAIA = 90;
    var hLogoAIA = (89.51 / 254.44) * wLogoAIA;
    doc.addImage(logoAIA, 'PNG', 30, 30, wLogoAIA, hLogoAIA, 'logoAIA', 'NONE', 0);

    //Añadir Etiqueta de Constructora AIA
    var etiquetaConstructoraAIASAS = new Image();
    etiquetaConstructoraAIASAS.src = '../imagenes/etiquetaConstructoraAIASAS.png';
    var wEtiquetaConstructoraAIASAS = 90;
    var hEtiquetaConstructoraAIASAS = (21.37 / 254.44) * wEtiquetaConstructoraAIASAS;
    doc.addImage(etiquetaConstructoraAIASAS, 'PNG', (width - 30 - wEtiquetaConstructoraAIASAS), 40, wEtiquetaConstructoraAIASAS, hEtiquetaConstructoraAIASAS, 'etiquetaConstructoraAIASAS', 'NONE', 0);

    //Añadir Título "Orden de Cambio"
    doc.setFontSize(26);
    doc.setFont('Helvetica', 'Bold');
    doc.text("Orden de Cambio", (width / 2), 50, {
        baseline: "middle",
        align: "center"
    });

    //Crear Primer Contenedor
    doc.setLineWidth(1);
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(55, 68, 81);
    doc.roundedRect(20, 70, (width - 20 - 20), 15, 3, 3, 'FD'); //  Barra Azul de titulo del primer contenedor
    doc.setFontSize(18);
    doc.setTextColor(250, 250, 250);
    doc.text("Información General", (width / 2), 82, {
        baseline: "middle",
        align: "center"
    });
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(20, 70, (width - 20 - 20), 200, 3, 3, 'S'); //  Primer Contenedor

    //Input Numero de Orden
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Número de Orden", 30, 100, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 105, 120, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputConsecutivo").value, 35, 115, {
        baseline: "middle",
        align: "left"
    });

    //Input Proyecto
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Proyecto", (width - 30 - 190), 100, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((width - 30 - 190), 105, 190, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputProyecto").value, (width - 25 - 190), 115, {
        baseline: "middle",
        align: "left"
    });

    //Input Director de Obra
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Director de Obra", 30, 140, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 145, 190, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputDirector").value, 35, 155, {
        baseline: "middle",
        align: "left"
    });

    //Input Fecha de Solicitud
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Fecha de Solicitud", (width - 30 - 190), 140, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((width - 30 - 190), 145, 190, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputFechaSolicitud").value, (width - 25 - 190), 155, {
        baseline: "middle",
        align: "left"
    });

    //Input Solicitante del Cambio
    doc.line(20, 170, (width - 20), 170);
    doc.line((width * 4 / 6), 170, (width * 4 / 6), 210);
    doc.line(20, 210, (width - 20), 210);
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Solicitante del Cambio", 30, 185, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(35, 195, 4, 'FD'); // Select Solicitante Obra
    doc.text("Obra", 41, 197, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(70, 195, 4, 'FD'); // Select Solicitante Cliente
    doc.text("Cliente", 76, 197, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(110, 195, 4, 'FD'); // Select Solicitante Interventoría
    doc.text("Interventoría", 116, 197, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(170, 195, 4, 'FD'); // Select Solicitante Otro
    doc.text("Otro:", 176, 197, {
        baseline: "middle",
        align: "left"
    });

    // Seleccion Solicitante del Cambio
    var inputValor = !document.querySelector('input[name="inputSolicitanteCambio"]:checked') ? "" : document.querySelector('input[name="inputSolicitanteCambio"]:checked').value;
    var posicionesX = {
        1: 35,
        2: 70,
        3: 110,
        4: 170
    };
    doc.setDrawColor(0, 123, 255);
    doc.setFillColor(0, 123, 255);
    if (inputValor != "") {
        doc.circle(posicionesX[inputValor], 195, 2, 'FD');
    }


    // Activar/Desactivar Detalle Solicitante Otro
    if (inputValor == 4) {
        doc.setDrawColor(206, 212, 218);
        doc.setFillColor(250, 250, 250);
        doc.roundedRect(195, 185, 105, 15, 3, 3, 'FD');
        doc.setFont('Helvetica', 'normal');
        doc.setFontSize(9);
        doc.text(document.getElementById("inputDetalleSolicitanteOtro").value, (195 + 5), 195, {
            baseline: "middle",
            align: "left",
            maxWidth: 95
        });
    } else {
        doc.setDrawColor(206, 212, 218);
        doc.setFillColor(233, 236, 239);
        doc.roundedRect(195, 185, 105, 15, 3, 3, 'FD');
    }


    //Input Prioridad
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Prioridad", (width - 30 - 115), 185, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 115), 195, 4, 'FD'); // Select Prioridad Alta
    doc.text("Alta", (width - 30 - 109), 197, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 85), 195, 4, 'FD'); // Select Prioridad Media
    doc.text("Media", (width - 30 - 79), 197, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 45), 195, 4, 'FD'); // Select Prioridad Baja
    doc.text("Baja", (width - 30 - 39), 197, {
        baseline: "middle",
        align: "left"
    });

    // Seleccion Prioridad
    var inputValor = !document.querySelector('input[name="inputPrioridad"]:checked') ? "" : document.querySelector('input[name="inputPrioridad"]:checked').value;;
    var posicionesX = {
        1: (width - 30 - 115),
        2: (width - 30 - 85),
        3: (width - 30 - 45)
    };
    doc.setDrawColor(0, 123, 255);
    doc.setFillColor(0, 123, 255);
    if (inputValor != "") {
        doc.circle(posicionesX[inputValor], 195, 2, 'FD');
    }

    //Input Tipo de Cambio
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.line((width * 0.35), 210, (width * 0.35), 270);
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Tipo de Cambio", 30, 225, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 235, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Alcance
    doc.text("Alcance", 40, 242, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(72, 235, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Cronograma
    doc.text("Cronograma", 82, 242, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(130, 235, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Costo
    doc.text("Costo", 140, 242, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 247, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Calidad
    doc.text("Calidad", 40, 254, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(70, 247, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Riesgo
    doc.text("Riesgo", 80, 254, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(108, 247, 8, 8, 2, 2, 'FD'); // Select Tipo de Cambio Recurso
    doc.text("Recurso", 118, 254, {
        baseline: "middle",
        align: "left"
    });

    // Seleccion Tipo de Cambio
    var inputTipoCambioAlcance = !document.querySelector('#inputTipoCambioAlcance:checked') ? "" : document.querySelector('#inputTipoCambioAlcance').value,
        inputTipoCambioCronograma = !document.querySelector('#inputTipoCambioCronograma:checked') ? "" : document.querySelector('#inputTipoCambioCronograma').value,
        inputTipoCambioCosto = !document.querySelector('#inputTipoCambioCosto:checked') ? "" : document.querySelector('#inputTipoCambioCosto').value,
        inputTipoCambioCalidad = !document.querySelector('#inputTipoCambioCalidad:checked') ? "" : document.querySelector('#inputTipoCambioCalidad').value,
        inputTipoCambioRiesgo = !document.querySelector('#inputTipoCambioRiesgo:checked') ? "" : document.querySelector('#inputTipoCambioRiesgo').value,
        inputTipoCambioRecurso = !document.querySelector('#inputTipoCambioRecurso:checked') ? "" : document.querySelector('#inputTipoCambioRecurso').value;

    var inputsTipoCambio = [
        [inputTipoCambioAlcance, 30, 235],
        [inputTipoCambioCronograma, 72, 235],
        [inputTipoCambioCosto, 130, 235],
        [inputTipoCambioCalidad, 30, 247],
        [inputTipoCambioRiesgo, 70, 247],
        [inputTipoCambioRecurso, 108, 247]
    ];

    inputsTipoCambio.forEach(element => {
        var inputTipoCambio = element[0];
        doc.setDrawColor(0, 123, 255);
        doc.setFillColor(0, 123, 255);
        if (inputTipoCambio != "") {
            doc.roundedRect(element[1] + 2, element[2] + 2, 4, 4, 1, 1, 'FD');
        }
    });

    //Input Responsable de la Definición del Cambio
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Responsable de la Definición del Cambio", (width - 30 - 260), 225, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 255), 245, 4, 'FD'); // Select Responsable Obra
    doc.text("Obra", (width - 30 - 249), 247, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 220), 245, 4, 'FD'); // Select Responsable Cliente
    doc.text("Cliente", (width - 30 - 214), 247, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 180), 245, 4, 'FD'); // Select Responsable Interventoría
    doc.text("Interventoría", (width - 30 - 174), 247, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle((width - 30 - 120), 245, 4, 'FD'); // Select Responsable Otro
    doc.text("Otro:", (width - 30 - 114), 247, {
        baseline: "middle",
        align: "left"
    });

    // Seleccion Responsable de la Definición del Cambio
    var inputValor = !document.querySelector('input[name="inputResponsableSolucion"]:checked') ? "" : document.querySelector('input[name="inputResponsableSolucion"]:checked').value;
    var posicionesX = {
        1: (width - 30 - 255),
        2: (width - 30 - 220),
        3: (width - 30 - 180),
        4: (width - 30 - 120)
    };
    doc.setDrawColor(0, 123, 255);
    doc.setFillColor(0, 123, 255);
    if (inputValor != "") {
        doc.circle(posicionesX[inputValor], 245, 2, 'FD');
    }


    // Activar/Desactivar Detalle Responsable de Definición Otro
    if (inputValor == 4) {
        doc.setDrawColor(206, 212, 218);
        doc.setFillColor(250, 250, 250);
        doc.roundedRect((width - 30 - 95), 235, 100, 15, 3, 3, 'FD');
        doc.setFont('Helvetica', 'normal');
        doc.setFontSize(9);
        doc.text(document.getElementById("inputDetalleResponsableSolucion").value, (width - 30 - 95 + 5), 245, {
            baseline: "top",
            align: "left",
            maxWidth: 90
        });
    } else {
        doc.setDrawColor(206, 212, 218);
        doc.setFillColor(233, 236, 239);
        doc.roundedRect((width - 30 - 95), 235, 100, 15, 3, 3, 'FD');
    }



    //Crear Segundo Contenedor
    doc.setLineWidth(1);
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(55, 68, 81);
    doc.roundedRect(20, 280, (width - 20 - 20), 15, 3, 3, 'FD'); //  Barra Azul de titulo del segundo contenedor
    doc.setFontSize(18);
    doc.setFont('Helvetica', 'Bold');
    doc.setTextColor(250, 250, 250);
    doc.text("Detalle del Cambio", (width / 2), 292, {
        baseline: "middle",
        align: "center"
    });
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(20, 280, (width - 20 - 20), (height - 20 - 310), 3, 3, 'S'); //  Segundo Contenedor

    //Input Justificación
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 305, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 305, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Justificación", (30 + 50 / 2), (305 + 45 / 2), {
        baseline: "middle",
        align: "center"
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputJustificacion").value, (20 + 10 + 50 + 5), 312, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Descripción
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 360, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 360, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Descripción", (30 + 50 / 2), (360 + 45 / 2), {
        baseline: "middle",
        align: "center"
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputDescripcion").value, (20 + 10 + 50 + 5), 367, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Indicencia en el Alcance
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 415, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 415, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Indicencia en el Alcance", (30 + 50 / 2), (415 + 45 / 3), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaAlcance").value, (20 + 10 + 50 + 5), 422, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Indicencia en el Cronograma
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 470, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 470, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Indicencia en el Cronograma", (30 + 50 / 2), (470 + 45 / 3), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaCronograma").value, (20 + 10 + 50 + 5), 477, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Crear nueva página
    doc.addPage('letter', 'portrait');

    //Crear Tercer Contenedor
    doc.setLineWidth(1);
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(20, 20, (width - 20 - 20), 375, 3, 3, 'S'); //  Tercer Contenedor

    //Titulo Incidencia en el Presupuesto
    doc.setFont('Helvetica', 'Bold');
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 30, 50, 135, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Incidencia en el", (30 + 50 / 2), (30 + 60), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.text("Presupuesto", (30 + 50 / 2), (30 + 60 + 15), {
        baseline: "middle",
        align: "center",
        maxWidth: 48
    });

    //Input Costo Directo
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((30 + 50 + 5), 30, 230, 20, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((30 + 50 + 5), 30, 110, 20, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Costo Directo", (30 + 50 + 5 + 110 / 2), (30 + 12), {
        baseline: "middle",
        align: "center",
        maxWidth: 110
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputCostoDirecto").value, (30 + 50 + 5 + 110 + 5), (30 + 12), {
        baseline: "top",
        align: "left",
        maxWidth: 110
    });

    //Input Costo Directo + AIU
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((30 + 50 + 5), 55, 230, 20, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((30 + 50 + 5), 55, 110, 20, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Costo Directo + AIU", (30 + 50 + 5 + 110 / 2), (55 + 12), {
        baseline: "middle",
        align: "center",
        maxWidth: 110
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputCostoDirectoAIU").value, (30 + 50 + 5 + 110 + 5), (55 + 12), {
        baseline: "top",
        align: "left",
        maxWidth: 110
    });

    //Input Costo Directo + AIU + IVA
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((30 + 50 + 5), 80, 230, 20, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((30 + 50 + 5), 80, 110, 20, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Costo Directo + AIU + IVA", (30 + 50 + 5 + 110 / 2), (80 + 12), {
        baseline: "middle",
        align: "center",
        maxWidth: 110
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputCostoDirectoAIUIVA").value, (30 + 50 + 5 + 110 + 5), (80 + 12), {
        baseline: "top",
        align: "left",
        maxWidth: 110
    });

    //Input Valor Aprobado
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((30 + 50 + 5), 105, 230, 20, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((30 + 50 + 5), 105, 110, 20, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Valor Aprobado", (30 + 50 + 5 + 110 / 2), (105 + 12), {
        baseline: "middle",
        align: "center",
        maxWidth: 110
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputValorAprobado").value, (30 + 50 + 5 + 110 + 5), (105 + 12), {
        baseline: "top",
        align: "left",
        maxWidth: 110
    });

    //Input Observaciones Incidencia en el Presupuesto
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((30 + 50 + 5), 130, (width - 30 - 50 - 5 - 30), 35, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((30 + 50 + 5), 130, 110, 35, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Observaciones", (30 + 50 + 5 + 110 / 2), (130 + 35 / 2), {
        baseline: "middle",
        align: "center",
        maxWidth: 110
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaPresupuesto").value, (30 + 50 + 5 + 110 + 5), (130 + 7), {
        baseline: "top",
        align: "left",
        maxWidth: (width - 30 - 50 - 5 - 30 - 110 - 5)
    });

    //Input Incidencia en la Calidad
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 175, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 175, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Incidencia en la Calidad", (30 + 50 / 2), (175 + 45 / 3), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaCalidad").value, (20 + 10 + 50 + 5), 182, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Incidencia en el Riesgo
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 230, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 230, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Incidencia en el Riesgo", (30 + 50 / 2), (230 + 45 / 3), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaRiesgo").value, (20 + 10 + 50 + 5), 237, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Incidencia en el Recurso
    doc.setFont('Helvetica', 'Bold');
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 285, (width - 30 - 30), 45, 3, 3, 'FD');
    doc.setFillColor(233, 236, 239);
    doc.roundedRect(30, 285, 50, 45, 3, 3, 'FD');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text("Incidencia en el Recurso", (30 + 50 / 2), (285 + 45 / 3), {
        baseline: "middle",
        align: "center",
        maxWidth: 45
    });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(document.getElementById("inputIncidenciaRecurso").value, (20 + 10 + 50 + 5), 292, {
        baseline: "top",
        align: "left",
        maxWidth: (width - 20 - 20 - 10 - 10 - 5 - 5 - 50)
    });

    //Input Fecha de Entrega a Interventoría
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.line(20, 350, (width - 20), 350);
    doc.line((width / 2), 350, (width / 2), 395);
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Fecha de Entrega a Interventoría", 30, 365, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(30, 370, 120, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputFechaEntregaInterventoria").value, 35, 380, {
        baseline: "middle",
        align: "left"
    });

    //Input Fecha Tentativa de Definición
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Fecha Tentativa de Definición", (width - 30 - 190), 365, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((width - 30 - 190), 370, 120, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputFechaTentativaDefinicion").value, (width - 25 - 190), 380, {
        baseline: "middle",
        align: "left"
    });

    //Crear Cuarto Contenedor
    doc.setLineWidth(1);
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(55, 68, 81);
    doc.roundedRect(20, 405, (width - 20 - 20), 15, 3, 3, 'FD'); //  Barra Azul de titulo del cuarto contenedor
    doc.setFontSize(18);
    doc.setFont('Helvetica', 'Bold');
    doc.setTextColor(250, 250, 250);
    doc.text("Aprobación", (width / 2), 417, {
        baseline: "middle",
        align: "center"
    });
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(20, 405, (width - 20 - 20), (height - 405 - 20 - 30), 3, 3, 'S'); //  Cuarto Contenedor

    //Input Estado de Aprobación
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.line((20 + (width - 20 - 20) / 3 * 2), 420, (20 + (width - 20 - 20) / 3 * 2), 460);
    doc.line(20, 460, (width - 20), 460);
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Estado de Aprobación", 30, 435, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(33, 445, 4, 'FD'); // Select Estado de Aprobación En Estudio
    doc.text("En Estudio", 39, 447, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(85, 445, 4, 'FD'); // Select Estado de Aprobación Aprobado
    doc.text("Aprobado", 91, 447, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(133, 445, 4, 'FD'); // Select Estado de Aprobación Aprobado con Restricciones
    doc.text("Aprobado con Restricciones", 139, 447, {
        baseline: "middle",
        align: "left",
        maxWidth: 55
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(196, 445, 4, 'FD'); // Select Estado de Aprobación No Aprobado
    doc.text("No Aprobado", 202, 447, {
        baseline: "middle",
        align: "left"
    });
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.circle(256, 445, 4, 'FD'); // Select Estado de Aprobación Desistido
    doc.text("Desistido", 262, 447, {
        baseline: "middle",
        align: "left"
    });

    // Seleccion Solicitante del Cambio
    var inputValor = !document.querySelector('input[name="inputAprobacion"]:checked') ? "" : document.querySelector('input[name="inputAprobacion"]:checked').value;
    var posicionesX = {
        4: 33,
        1: 85,
        2: 133,
        3: 196,
        5: 256
    };
    doc.setDrawColor(0, 123, 255);
    doc.setFillColor(0, 123, 255);
    if (inputValor != "") {
        doc.circle(posicionesX[inputValor], 445, 2, 'FD');
    }

    //Input Fecha de Definición
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Fecha de Definición", (20 + (width - 20 - 20) / 3 * 2 + 10), 435, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect((20 + (width - 20 - 20) / 3 * 2 + 10), 440, 120, 15, 3, 3, 'S');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(document.getElementById("inputFechaDefinicion").value, (20 + (width - 20 - 20) / 3 * 2 + 15), 450, {
        baseline: "middle",
        align: "left"
    });

    //Firmas
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    doc.text("Firmas", 30, 470, {
        baseline: "middle",
        align: "left"
    });
    doc.setLineWidth(0.75);
    doc.setDrawColor(0, 0, 0);
    doc.line((20 + 10), 510, (20 + 10 + 120), 510);
    doc.line((width / 2 - 120 / 2), 510, (width / 2 + 120 / 2), 510);
    doc.line((width - 20 - 10 - 120), 510, (width - 20 - 10), 510);
    doc.setFontSize(9);
    doc.text("Nombre:", (20 + 10), 520, {
        baseline: "top",
        align: "left"
    });
    doc.text("Nombre:", (width / 2 - 120 / 2), 520, {
        baseline: "top",
        align: "left"
    });
    doc.text("Nombre:", (width - 20 - 10 - 120), 520, {
        baseline: "top",
        align: "left"
    });
    doc.setFont('Helvetica', 'normal');
    doc.text("Coordinador o Director de Obra", (20 + 10 + 120 / 2), 540, {
        baseline: "top",
        align: "center"
    });
    doc.text("Interventoría", (width / 2), 540, {
        baseline: "top",
        align: "center"
    });
    doc.text("Cliente o Socio", (width - 20 - 10 - 120 / 2), 540, {
        baseline: "top",
        align: "center"
    });

    //Crear nueva página
    doc.addPage('letter', 'portrait');

    //Crear Quinto Contenedor
    doc.setLineWidth(1);
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(55, 68, 81);
    doc.roundedRect(20, 20, (width - 20 - 20), 15, 3, 3, 'FD'); //  Barra Azul de titulo del quinto contenedor
    doc.setFontSize(18);
    doc.setFont('Helvetica', 'Bold');
    doc.setTextColor(250, 250, 250);
    doc.text("Archivos de Soporte", (width / 2), 32, {
        baseline: "middle",
        align: "center"
    });
    doc.setDrawColor(41, 49, 56);
    doc.setFillColor(250, 250, 250);
    doc.roundedRect(20, 20, (width - 20 - 20), (height - 20 - 50), 3, 3, 'S'); //  Quinto Contenedor

    //Encabezado tabla de archivos de soporte
    doc.setLineWidth(0.75);
    doc.setDrawColor(206, 212, 218);
    doc.setFillColor(233, 236, 239);
    doc.roundedRect((20 + 10), 45, (width - 20 - 20 - 10 - 10), 20, 1, 1, 'FD');
    doc.line((20 + 10 + 2 * (width - 20 - 10 - 20 - 10) / 12), 45, (20 + 10 + 2 * (width - 20 - 10 - 20 - 10) / 12), 65);
    doc.line((20 + 10 + 7 * (width - 20 - 10 - 20 - 10) / 12), 45, (20 + 10 + 7 * (width - 20 - 10 - 20 - 10) / 12), 65);
    doc.setFont('Helvetica', 'Bold');
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    doc.text("Adjunto N°", (20 + 10 + (width - 20 - 10 - 20 - 10) / 12), 58, {
        baseline: "middle",
        align: "center"
    });
    doc.text("Descripción", (20 + 10 + 4.5 * (width - 20 - 10 - 20 - 10) / 12), 58, {
        baseline: "middle",
        align: "center"
    });
    doc.text("Link (URL)", (20 + 10 + 9.5 * (width - 20 - 10 - 20 - 10) / 12), 58, {
        baseline: "middle",
        align: "center"
    });

    let baseY = 65;
    let baseX = 30;
    for (i = 1; i < (5 + 1); i++) {
        doc.setLineWidth(0.75);
        doc.setDrawColor(206, 212, 218);
        doc.setFillColor(250, 250, 250);
        doc.roundedRect((baseX), baseY, (width - 20 - 20 - 10 - 10), 20, 1, 1, 'FD');
        doc.line((baseX + 2 * (width - 20 - 10 - 20 - 10) / 12), baseY, (baseX + 2 * (width - 20 - 10 - 20 - 10) / 12), (baseY + 20));
        doc.line((baseX + 7 * (width - 20 - 10 - 20 - 10) / 12), baseY, (baseX + 7 * (width - 20 - 10 - 20 - 10) / 12), (baseY + 20));

        baseY += 20;
    }

    //Guardar el Archivo
    var nombre = document.getElementById("proyecto").value + "_Orden_de_Cambio_" + document.getElementById("inputConsecutivo").value + ".pdf";
    var blob = doc.output('blob');

    var formData = new FormData();
    formData.append('pdf', blob);
    formData.append('nombreArchivo', nombre);

    var height = window.innerHeight * 0.9;
    //console.log(height);
    var width = document.body.clientWidth * 0.7;

    $.ajax('ordenes/cargarPDFServidor.php', {
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data) {
            var json_info = JSON.parse(data);
            window.open("ordenes/" + json_info["nombre"], json_info["nombre"], "toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width="+width+",height="+height);
        },
        error: function(data) {
            console.log(data)
        }
    });
}

var selectoresFecha = function(input) {
    // $("#" + input).datepicker( "destroy" );
    dia = new Date($("#" + input).val());
    dia = new Date(dia.getFullYear() + "-" + (dia.getMonth() + 1) + "-" + (dia.getDate() + 1));
    $("#" + input).datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showOtherMonths: true,
        selectOtherMonths: true,
        defaultDate: dia,
    });
};

var descargarConsolidadoODC = function() {
    var db = document.getElementById('baseDatos').value;
    // console.log(frm);

    $.ajax({
        method: "POST",
        url: "descargarConsolidadoODC.php",
        contenttype:"charset=utf-8",
        data: {"db":db},
    }).done( function( info ){
        //console.log(info);
        var json_info = JSON.parse(info);
        window.location.href = json_info;
    });
}

var cerrarTodosModales = function() {
    $('.modal').modal('hide'); // Cierra todos los modales abiertos
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