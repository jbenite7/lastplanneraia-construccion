/* Variable global para almacenar las opciones de códigos de actividad */
var global_codigos_actividad_options = "";
var global_actividades_data = []; // Store raw data for lookups

/*Configura la DataTable en idioma español*/
var idioma_espanol = {
  sProcessing: 'Procesando...',
  sLengthMenu: 'Mostrar _MENU_ registros',
  sZeroRecords: 'No se encontraron resultados',
  sEmptyTable: 'Ningún dato disponible en esta tabla =(',
  sInfo: 'Mostrando  _TOTAL_ registros',
  sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
  sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
  sInfoPostFix: '',
  sSearch: 'Buscar:',
  sUrl: '',
  sInfoThousands: ',',
  sLoadingRecords: 'Cargando...',
  oPaginate: {
    sFirst: 'Primero',
    sLast: 'Último',
    sNext: 'Siguiente',
    sPrevious: 'Anterior',
  },
  oAria: {
    sSortAscending: ': Activar para ordenar la columna de manera ascendente',
    sSortDescending: ': Activar para ordenar la columna de manera descendente',
  },
  buttons: {
    copy: 'Copiar',
    colvis: 'Visibilidad',
  },
};

/* Función para cargar los códigos de actividad asíncronamente */
var cargarCodigosActividad = function() {
    $.ajax({
        url: '/api/general/codigos',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var options = "";
                global_actividades_data = response.data; // Cache data
                response.data.forEach(function(item) {
                    var vista = item.codigo_actividad + " - " + item.actividad;
                    options += "<option value='" + item.codigo_actividad + "'>" + vista + "</option>";
                });
                global_codigos_actividad_options = options;
                // console.log("Códigos de actividad cargados via API");
            } else {
                console.error("Error cargando códigos: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX: " + error);
        }
    });
};

/* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
$(document).on("ready", function() {
  $("#formulario_nuevo").hide();
    cargarDatosGeneralesPagina(document.getElementById('seccion').value);
    cargarCodigosActividad(); // Cargar opciones en segundo plano
});


var cargaParametros = function() {
    var db = document.getElementById('baseDatos_PHP') ? document.getElementById('baseDatos_PHP').value : document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana_PHP') ? document.getElementById('semana_PHP').value : document.getElementById('semana').value;
    actualizarBarraFiltros(db, semana, "siListar");
}

/* Global Filter State */
var activeFilters = [];
var selectedStateFilter = '';

var PG_DECIMALS = 1;
var PG_RATIO_DECIMALS = 4;

function normalizeNumericString(value) {
    if (value === null || value === undefined) {
        return '';
    }

    var normalized = String(value).trim().replace(/\s+/g, '');
    if (normalized === '') {
        return '';
    }

    var commaPos = normalized.lastIndexOf(',');
    var dotPos = normalized.lastIndexOf('.');

    if (commaPos > -1 && dotPos > -1) {
        if (commaPos > dotPos) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else {
            normalized = normalized.replace(/,/g, '');
        }
    } else if (commaPos > -1) {
        normalized = normalized.replace(',', '.');
    }

    return normalized;
}

function toNumber(value, fallback) {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    var parsed = parseFloat(normalizeNumericString(value));
    return Number.isFinite(parsed) ? parsed : fallback;
}

function roundToDecimals(value, decimals) {
    var parsed = toNumber(value, null);
    if (parsed === null) {
        return null;
    }

    var factor = Math.pow(10, decimals);
    return Math.round((parsed + Number.EPSILON) * factor) / factor;
}

function formatDecimal(value, decimals) {
    var rounded = roundToDecimals(value, decimals);
    if (rounded === null) {
        return '';
    }

    return rounded.toFixed(decimals);
}

function formatDecimalComma(value, decimals) {
    var formatted = formatDecimal(value, decimals);
    return formatted === '' ? '' : formatted.replace('.', ',');
}

function formatPercentFromRatio(value) {
    var numeric = toNumber(value, null);
    if (numeric === null) {
        return '';
    }

    return formatDecimalComma(numeric * 100, PG_DECIMALS) + '%';
}

function sanitizeUnit(unit) {
    if (unit === null || unit === undefined) {
        return '';
    }

    return String(unit).trim();
}

function formatValueWithUnit(value, unit) {
    var formatted = formatDecimalComma(value, PG_DECIMALS);
    if (formatted === '') {
        return '';
    }

    var finalUnit = sanitizeUnit(unit);
    return finalUnit ? (formatted + ' ' + finalUnit) : formatted;
}

function bindDecimalInputNormalization(selector) {
    var $input = $(selector);
    if (!$input.length) {
        return;
    }

    $input.off('blur.pgDecimal').on('blur.pgDecimal', function() {
        var value = toNumber($(this).val(), null);
        if (value === null) {
            $(this).val('');
            return;
        }

        $(this).val(formatDecimalComma(value, PG_DECIMALS));
    });
}

function normalizeEstadoLabel(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function getRestrictionAlertKey(data) {
    if (!data || Number(data.Titulo) !== 0) {
        return '';
    }

    var estadoRestricciones = toNumber(data.Estado_Restricciones, 1);
    var ejecutado = toNumber(data.Ejecutado, 0);

    if (estadoRestricciones >= 0.999 || ejecutado >= 0.999) {
        return '';
    }

    var semanasInicio = Math.round(toNumber(data.Semanas_Inicio, 999));
    if (semanasInicio <= 0) {
        return 'r0';
    }

    if (semanasInicio === 1) {
        return 'r1';
    }

    if (semanasInicio >= 2 && semanasInicio <= 3) {
        return 'r2-3';
    }

    if (semanasInicio >= 4 && semanasInicio <= 6) {
        return 'r4-6';
    }

    return '';
}

function getRestrictionAlertLabel(alertKey) {
    var labels = {
        'r0': 'R0',
        'r1': 'R1',
        'r2-3': 'R2-3',
        'r4-6': 'R4-6',
    };

    return labels[alertKey] || '';
}

function normalizeEstadoToStateKey(estado) {
    switch (normalizeEstadoLabel(estado)) {
        case 'capitulo':
            return 'header';
        case 'terminada':
        case 'terminada antes':
        case 'ok':
            return 'terminada';
        case 'atrasada':
        case 'ya debio iniciar y restricciones pendientes':
            return 'atrasada';
        case 'debe iniciar esta semana':
        case 'debe iniciar esta semana y restricciones pendientes':
            return 'debe-iniciar';
        case 'adelantada':
            return 'adelantada';
        case 'en curso':
        case 'a tiempo':
            return 'en-curso';
        case 'actividad futura':
        case 'en liberacion de restricciones':
            return 'actividad-futura';
        case 'no requerida':
        case 'ni':
            return 'no-requerida';
        default:
            return '';
    }
}

function getFallbackStateKey(data) {
    var ejecutado = toNumber(data.Ejecutado, 0);
    var semanasInicio = Math.round(toNumber(data.Semanas_Inicio, 999));

    if (ejecutado >= 0.999) {
        return 'terminada';
    }

    if (semanasInicio < 0 && ejecutado < 0.999) {
        return 'atrasada';
    }

    if (semanasInicio === 0 && ejecutado <= 0) {
        return 'debe-iniciar';
    }

    if (semanasInicio > 0 && semanasInicio <= 6 && ejecutado <= 0) {
        return 'actividad-futura';
    }

    if (semanasInicio > 6 && ejecutado <= 0) {
        return 'no-requerida';
    }

    if (ejecutado > 0 && ejecutado < 0.999) {
        return 'en-curso';
    }

    return 'no-requerida';
}

/* Centralized State Logic for Programa General (UI = estado persistido) */
function classifyPGRow(data) {
    if (!data || Number(data.Titulo) !== 0) {
        return {
            key: 'header',
            baseKey: 'header',
            rowClass: 'pdc-header',
            isCritical: false,
            restrictionAlertKey: '',
        };
    }

    var rutaCriticaRaw = String(data.Ruta_Critica === undefined ? '' : data.Ruta_Critica).trim().toLowerCase();
    var isCritical = (rutaCriticaRaw === '1' || rutaCriticaRaw === 'si' || rutaCriticaRaw === 'sí' || rutaCriticaRaw === 'true');
    var baseKey = normalizeEstadoToStateKey(data.Estado) || getFallbackStateKey(data);
    var stateKey = (baseKey === 'atrasada' && isCritical) ? 'atrasada-critica' : baseKey;
    var rowClassMap = {
        'atrasada-critica': 'pg-state-atrasada-critica',
        'atrasada': 'pg-state-atrasada',
        'debe-iniciar': 'pg-state-debe-iniciar',
        'actividad-futura': 'pg-state-actividad-futura',
        'adelantada': 'pg-state-adelantada',
        'en-curso': 'pg-state-en-curso',
        'terminada': 'pg-state-terminada',
        'no-requerida': 'pg-state-no-requerida',
    };

    return {
        key: stateKey,
        baseKey: baseKey,
        rowClass: rowClassMap[stateKey] || 'pg-state-no-requerida',
        isCritical: isCritical,
        restrictionAlertKey: getRestrictionAlertKey(data),
    };
}

function getProgGenState(data) {
    return classifyPGRow(data).key;
}

function getProgGenStateLabel(stateKey) {
    var labels = {
        'header': 'Capítulo',
        'debe-iniciar': 'Debe Iniciar esta Semana',
        'actividad-futura': 'Actividad Futura',
        'adelantada': 'Adelantada',
        'en-curso': 'En Curso',
        'atrasada-critica': 'Atrasada (Crítica)',
        'atrasada': 'Atrasada',
        'terminada': 'Terminada',
        'no-requerida': 'No Requerida',
    };

    return labels[stateKey] || 'En Curso';
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getProgGenStateIcon(stateKey, isCritical) {
    if (stateKey === 'atrasada-critica') {
        return "<i class='fas fa-skull-crossbones fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'atrasada') {
        return "<i class='fas fa-radiation fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'debe-iniciar') {
        return "<i class='fas fa-clock fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'actividad-futura') {
        return "<i class='fas fa-calendar-alt fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'adelantada') {
        return "<i class='fas fa-forward fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'en-curso') {
        return isCritical
            ? "<i class='fas fa-bell fa-xl pg-state-icon'></i>"
            : "<i class='fas fa-exclamation fa-xl pg-state-icon'></i>";
    }

    if (stateKey === 'terminada') {
        return "<i class='fas fa-check-circle fa-xl pg-state-icon'></i>";
    }

    return '';
}

function renderProgramaGeneralLegendModal() {
    var $modal = $('#modal_leyenda_colores');
    if (!$modal.length) {
        return;
    }

    $modal.find('.modal-dialog').addClass('modal-lg');
    $modal.find('.modal-header').addClass('pg-legend-modal-header');
    $modal.find('#modal_leyenda_colores_Label').text('Guía Operativa - Programa General');

    $modal.find('.modal-body').html(
        "<div class='pg-legend-quick-guide'>" +
            // Encabezado
            "<div class='pg-legend-quick-header'>" +
                "<div class='pg-legend-quick-scale'>" +
                    "<span class='pg-legend-quick-badge is-p1'>P1 Hoy</span>" +
                    "<span class='pg-legend-quick-badge is-p2'>P2 Esta semana</span>" +
                    "<span class='pg-legend-quick-badge is-p3'>P3 Seguimiento</span>" +
                "</div>" +
            "</div>" +

            // Grupo P1
            "<section class='pg-legend-quick-group'>" +
                "<h6 class='pg-legend-quick-group-title'>P1 - Resolver hoy</h6>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-atrasada-critica'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Atrasada (Critica)</strong><small>Debajo del teorico semanal en ruta critica.</small></div>" +
                    "<div class='pg-legend-quick-action'>Escalar bloqueo y activar recuperacion.</div>" +
                    "<span class='pg-legend-quick-priority is-p1'>P1</span>" +
                "</div>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-atrasada'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Atrasada</strong><small>Por debajo de la curva teorica al inicio de semana.</small></div>" +
                    "<div class='pg-legend-quick-action'>Reprogramar frente y cerrar causa del atraso.</div>" +
                    "<span class='pg-legend-quick-priority is-p1'>P1</span>" +
                "</div>" +
            "</section>" +

            // Grupo P2
            "<section class='pg-legend-quick-group'>" +
                "<h6 class='pg-legend-quick-group-title'>P2 - Gestion semanal</h6>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-debe-iniciar'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Debe Iniciar esta Semana</strong><small>Inicio dentro de la semana actual y sin avance.</small></div>" +
                    "<div class='pg-legend-quick-action'>Asegurar recursos, cuadrilla y frente liberado.</div>" +
                    "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
                "</div>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-en-curso'></span>" +
                    "<div class='pg-legend-quick-state'><strong>En Curso</strong><small>Ejecucion alineada con la curva teorica semanal.</small></div>" +
                    "<div class='pg-legend-quick-action'>Sostener ritmo diario y control de productividad.</div>" +
                    "<span class='pg-legend-quick-priority is-p2'>P2</span>" +
                "</div>" +
            "</section>" +

            // Grupo P3
            "<section class='pg-legend-quick-group'>" +
                "<h6 class='pg-legend-quick-group-title'>P3 - Seguimiento</h6>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-adelantada'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Adelantada</strong><small>En curso por encima del cronograma teorico.</small></div>" +
                    "<div class='pg-legend-quick-action'>Proteger el adelanto para no perder rendimiento.</div>" +
                    "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
                "</div>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-actividad-futura'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Actividad Futura</strong><small>Inicia dentro del horizonte de 6 semanas.</small></div>" +
                    "<div class='pg-legend-quick-action'>Preparar compras, mano de obra y permisos.</div>" +
                    "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
                "</div>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-terminada'></span>" +
                    "<div class='pg-legend-quick-state'><strong>Terminada</strong><small>Actividad cerrada (a tiempo o adelantada).</small></div>" +
                    "<div class='pg-legend-quick-action'>Cerrar trazabilidad y liberar foco del equipo.</div>" +
                    "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
                "</div>" +
                "<div class='pg-legend-quick-row'>" +
                    "<span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-no-requerida'></span>" +
                    "<div class='pg-legend-quick-state'><strong>No Requerida</strong><small>Fuera del lookahead de 6 semanas.</small></div>" +
                    "<div class='pg-legend-quick-action'>Mantener en monitoreo de mediano plazo.</div>" +
                    "<span class='pg-legend-quick-priority is-p3'>P3</span>" +
                "</div>" +
            "</section>" +

            // Alertas
            "<section class='pg-legend-quick-alerts'>" +
                "<h6 class='pg-legend-quick-group-title'>Alertas secundarias de restricciones</h6>" +
                "<p class='pg-legend-quick-alert-intro'>R0-R1-R2/3-R4/6 no cambian el estado principal. Solo anticipan desbloqueos.</p>" +
                "<div class='pg-legend-quick-alert-grid'>" +
                    "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-0'></span><strong>R0</strong><small>Arranque inmediato o vencido.</small></div>" +
                    "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-1'></span><strong>R1</strong><small>Debe quedar liberada en 1 semana.</small></div>" +
                    "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-2-3'></span><strong>R2-3</strong><small>Riesgo medio en ventana proxima.</small></div>" +
                    "<div class='pg-legend-quick-alert-item'><span class='pg-legend-modal-swatch pg-legend-quick-swatch pg-state-restr-4-6'></span><strong>R4-6</strong><small>Riesgo temprano del lookahead.</small></div>" +
                "</div>" +
            "</section>" +
        "</div>"
    );
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
/*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/
var listar = function() {
    var db = document.getElementById('baseDatos_PHP') ? document.getElementById('baseDatos_PHP').value : document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana_PHP') ? document.getElementById('semana_PHP').value : document.getElementById('semana').value;
    var scriptBarraFiltros = document.getElementById('scriptBarraFiltros').value;
    var fechaCreacionSemana = document.getElementById('fechaCreacionSemana_PHP') ? document.getElementById('fechaCreacionSemana_PHP').value : document.getElementById('fechaCreacionSemana').value;
    var versionCronograma = document.getElementById('versionCronograma_PHP') ? document.getElementById('versionCronograma_PHP').value : document.getElementById('versionCronograma').value;
    
    if(fechaCreacionSemana=='' || fechaCreacionSemana==null){
    }else{
        // Inyectar en la Barra de Contexto Global (sin sobreescribir el badge)
        var contextWeekInfo = document.querySelector('.context-week-info');
        if (contextWeekInfo) {
            // Verificar si ya existe para no duplicar
            var existingInfo = document.getElementById('infoFechaCreacionExterna');
            if (!existingInfo) {
                var htmlFecha = "<span id='infoFechaCreacionExterna' class='d-inline-block text-nowrap ml-3 context-cierre-info text-muted'>Semana Creada el <b>" + fechaCreacionSemana + "</b>&nbsp;&nbsp;(v<b>"+ versionCronograma +"</b>)</span>";
                contextWeekInfo.insertAdjacentHTML('beforeend', htmlFecha);
            }
        }
    }

    // Initial Height Calculation
    var alturatabla = calcDataTableHeight();
    document.getElementById('cuadroTabla').style.height = "auto";

    var table = $("#dt_cliente").DataTable({
        /* "dom": "<'row filaBotones'<'col-md-12 mr-auto p-0'<'toolbarFilaBotones'>>><'row filaMensajes'<'col-md-6 mr-auto p-0'<'toolbarFilaMensajes'>><'col-md-2 ml-auto p-0'<'toolbarResetFiltro'>><'col-md-2 ml-auto p-0'<'toolbarFiltro'>>>t<'row'<'col-md-6'i>><'clear'>", */
        "dom": "<'row filaBotones align-items-center'<'col-auto'<'toolbarFilaBotones'>><'col col-flexible-legend'<'toolbarFilaMensajes'>>>t<'row'<'col-md-6'i>><'clear'>",
        "destroy": true,
        "ordering":false,
        "autoWidth": false,
        "fixedHeader": false,
        "scrollX": false,
        //                console.log($(document).height());
        "scrollY": false,
        "scrollCollapse": false,
        "responsive": false,
        "paging": false,
        "ajax": {
            "method": "GET",
            "url":"/api/general/list?db="+db+"&semana="+semana+scriptBarraFiltros
        },
        "lengthMenu": [100, 200, 500],
        'columnDefs': [
            {
                'targets': '_all',
                'createdCell': function (td, cellData, rowData, row, col) {
                    var headers = ['', '', 'Consecutivo', 'Id', 'Código Actividad', 'Actividad', 'Título', 'Semana Inicio', 'Fecha Inicio', 'Fecha Fin', 'Crítica', 'Unidad', 'Cant. Ppto', 'Ejecutado Teórico', 'Ejecutado Real', 'Estado', 'Liberación', 'Responsable AIA', 'Sub-Contratista'];
                    if (headers[col]) {
                        $(td).attr('data-label', headers[col]);
                    }
                }
            },
            {
                'targets': 0,
                'checkboxes': {
                    'selectRow': false,
                    'visible':false,
                }
            },


            {
                'targets': [1],
                    'width': '3%'
            },
            {
                    'targets': [3],
                    'width': '4%'
            },
            {
                    'targets': [4],
                    'width': '7%'
            },
            {
                    'targets': [5],
                    'width': '20%'
            },
            {
                    'targets': [7],
                    'width': '6%'
            },
            {
                    'targets': [8],
                    'width': '8%'
            },
            {
                    'targets': [9],
                    'width': '8%'
            },
            {
                    'targets': [10],
                    'width': '4%'
            },
            {
                    'targets': [11],
                    'width': '5%'
            },
            {
                    'targets': [12],
                    'width': '8%'
            },
            {
                    'targets': [13],
                    'width': '8%'
            },
            {
                    'targets': [14],
                    'width': '8%'
            },
            {
                    'targets': [15],
                    'width': '6%'
            },
            {
                    'targets': [16],
                    'width': '5%'
            },


            {
                'targets': [4],
                'render': function ( data, type, full, meta ) {
                    if(data=="" || data==null){
                        data="";
                    }else{
                    }
                    return data;
                },
            },

            {
                'targets': [10],
                'render': function ( data, type, full, meta ) {
                    if(data===""){
                        data="";
                    }else if(data==1){
                        data="Sí";
                    }else if (data==0){
                        data="No";
                    }
                    return data;
                },
            },

            {
                'targets': [12],
                'render': function ( data, type, row, meta) {
                    if(data=="" || data==null){
                        return data;
                    }

                    return formatValueWithUnit(data, row['unidad']);
                },
            },

            {
                'targets': [13],
                'render': function ( data, type, row, meta) {
                    if(data=="" || data==null){
                        return data;
                    }

                    var cantidadPpto = toNumber(row['cantidad_ppto'], null);
                    var ejecutadoTeorico = toNumber(data, null);

                    if (ejecutadoTeorico === null) {
                        return data;
                    }

                    if (cantidadPpto === null) {
                        return formatPercentFromRatio(ejecutadoTeorico);
                    }

                    var cantidadEjecutada = roundToDecimals(cantidadPpto * ejecutadoTeorico, PG_DECIMALS);
                    return "<p class='pg-cell-main'>" + formatValueWithUnit(cantidadEjecutada, row['unidad']) + "</p><p class='pg-cell-meta'>(" + formatPercentFromRatio(ejecutadoTeorico) + ")</p>";
                },
            },

            {
                'targets': [14],
                'render': function ( data, type, row, meta) {
                    if(data=="" || data==null){
                        return data;
                    }

                    var cantidadPpto = toNumber(row['cantidad_ppto'], null);
                    var ejecutadoReal = toNumber(data, null);

                    if (ejecutadoReal === null) {
                        return data;
                    }

                    if (cantidadPpto === null) {
                        return formatPercentFromRatio(ejecutadoReal);
                    }

                    var cantidadEjecutada = roundToDecimals(ejecutadoReal * cantidadPpto, PG_DECIMALS);
                    return "<p class='pg-cell-main'>" + formatValueWithUnit(cantidadEjecutada, row['unidad']) + "</p><p class='pg-cell-meta'>(" + formatPercentFromRatio(ejecutadoReal) + ")</p>";
                },
            },

            {
                'targets': [16],
                'render': function ( data, type, row, meta ) {
                    var Titulo= row['Titulo'];
                    if(data=="" || data==null || Titulo==1){
                        data="";
                    }else{
                        data = formatPercentFromRatio(data);
                    }
                    if(row["Titulo"] == 1){
                        return data;
                    }else{
                        return data;
                    }

                },
            },

            {
                'targets': [1],
                'render': function ( data, type, full, meta ) {
                    var permiso=document.getElementById("permiso").value;
                    if(data=="Boton"){
                        boton="";
                    }else{
                        boton="";
                    }
                    return boton;
                },
            },

            {
                    'targets': [1],
                    'className': 'Botones dt-nowrap'
            },
            {
                    'targets': [4],
                    'className': 'input_codigo_actividad dt-nowrap'
            },
            {
                    'targets': [5],
                    'className': 'input_Actividad' /* Relaxed width */
            },
            {
                    'targets': [8],
                    'className': 'input_Fecha_Inicio dt-nowrap'
            },
            {
                    'targets': [9],
                    'className': 'input_Fecha_Fin dt-nowrap'
            },
            {
                    'targets': [11],
                    'className': 'input_unidad'
            },
            {
                    'targets': [12],
                    'className': 'input_cantidad_ppto'
            },
            {
                    'targets': [14],
                    'className': 'input_Ejecutado'
            },
        ],

        'select': {
            'style': 'false',
        },

        "lengthMenu": [10],

    "columns":[
        {"defaultContent":"", "visible":false},
        {"data":"boton"},
        {"data":"Consecutivo_en_Programa", "visible":false},
        {"data":"Id"},
        {"data":"codigo_actividad"},
        {"data":"Actividad"},
        {"data":"Titulo", "visible":false},
        {"data":"Semanas_Inicio"},
        {"data":"Fecha_Inicio"},
        {"data":"Fecha_Fin"},
        {"data":"Ruta_Critica"},
        {"data":"unidad"},
        {"data":"cantidad_ppto"},
        {"data":"Ejecutado_Teorico"},
        {"data":"Ejecutado"},
        {"data":"Estado",
            "render": function ( data, type, row, meta) {
                var classification = classifyPGRow(row);
                var icono = getProgGenStateIcon(classification.key, classification.isCritical);
                var estadoTexto = getProgGenStateLabel(classification.baseKey || classification.key);
                var estadoTextoFiltro = getProgGenStateLabel(classification.key);

                if (type === 'sort' || type === 'type' || type === 'filter') {
                    return estadoTextoFiltro;
                }

                var estadoOriginal = (data === null || data === undefined) ? '' : String(data).trim();
                var tooltip = '';
                if (estadoOriginal && estadoOriginal !== estadoTexto) {
                    tooltip = " title=\"Estado origen: " + escapeHtml(estadoOriginal) + "\"";
                }

                var badges = '';
                if (classification.key === 'atrasada-critica') {
                    badges += " <span class='pg-alert-badge pg-alert-critical'>Crítica</span>";
                }

                if (classification.restrictionAlertKey) {
                    var alertLabel = getRestrictionAlertLabel(classification.restrictionAlertKey);
                    if (alertLabel) {
                        badges += " <span class='pg-alert-badge pg-alert-" + classification.restrictionAlertKey + "'>" + alertLabel + "</span>";
                    }
                }

                return "<span" + tooltip + ">" + estadoTexto + "</span>" + badges + (icono ? "&nbsp" + icono : '');
            },
        },
        {"data":"Estado_Restricciones"},
        {"data":"Responsable_AIA", "visible":false},
        {"data":"Sub_Contratista", "visible":false}
    ],

    "createdRow": function( row, data, index ) {
        var classification = classifyPGRow(data);
        $(row).addClass(classification.rowClass);
    },
    "drawCallback": function(settings) {
        var counts = {
            'con-alerta-restricciones': 0,
            'debe-iniciar': 0,
            'actividad-futura': 0,
            'adelantada': 0,
            'en-curso': 0,
            'atrasada-critica': 0,
            'atrasada': 0,
            'terminada': 0,
            'no-requerida': 0,
        };
        
        var api = this.api();
        var allData = api.rows({search:'applied'}).data(); 

        allData.each(function(rowData) {
            var classification = classifyPGRow(rowData);
            var s = classification.key;
            if(counts[s] !== undefined) counts[s]++;
            if (classification.restrictionAlertKey) {
                counts['con-alerta-restricciones']++;
            }
        });

        // Update Badges
        for(var k in counts) {
            $('#count-'+k).text('('+counts[k]+')');
        }

        // Re-attach edit listeners on every draw (init, page change, reload)
        obtener_data_editar("#dt_cliente tbody", api);
    },
    "initComplete": function() {
        var api = this.api();
        setTimeout(function() {
            if (window.DataTableHeightManager && typeof window.DataTableHeightManager.applyToDataTable === "function") {
                window.DataTableHeightManager.applyToDataTable(api, {
                    container: "#cuadroTabla",
                    internalChrome: 170,
                    bottomMargin: 25,
                    minHeight: 200,
                });
            }
            api.columns.adjust().draw(false);
        }, 100);
    },

        "language": idioma_espanol
    });

    // Dynamic Resize Listener
    $(window).off('resize.dtProgGen orientationchange.dtProgGen aia:viewport-scale-change.dtProgGen').on('resize.dtProgGen orientationchange.dtProgGen aia:viewport-scale-change.dtProgGen', function() {
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

    $("div.toolbarFilaBotones").html(`
        <div class="pg-toolbar-shell d-flex flex-wrap align-items-center">
            <div class="pg-toolbar-actions d-flex flex-wrap align-items-center h-100 w-auto">
                <button type="button" class="leyenda_colores btn-pdc-modern mr-1 flex-fill flex-md-grow-0 justify-content-center" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle ml-2"></i></button>
                <button type="button" id="actualizarEjecucion" class="actualizarEjecucion btn-pdc-modern flex-fill flex-md-grow-0 justify-content-center" onclick="actualizarEjecucion()">Actualizar Ejecución <i class="fas fa-sync ml-2"></i></button>
                <button type="button" id="descargarCorteProgramacion" class="descargarCorteProgramacion btn-pdc-modern flex-fill flex-md-grow-0 justify-content-center" onclick="descargarCorteProgramacion()">Descargar Corte <i class="fas fa-download ml-2"></i></button>
            </div>
            <!-- Mobile Toggle Button -->
            <button class="btn-filter-toggle pdc-mobile-toggle" type="button" data-toggle="collapse" data-target="#pdcFiltersMobile" aria-expanded="false" aria-controls="pdcFiltersMobile">
                <i class="fas fa-filter"></i> Filtros <span class="badge badge-light" id="mobileFilterCount">0</span> <i class="fas fa-chevron-down ml-auto"></i>
            </button>
        </div>
    `);

    $("div.toolbarFilaMensajes").html(`
        <div class="collapse d-md-block" id="pdcFiltersMobile">
            <div class="pdc-legend pg-legend h-100">
                <span class="pdc-legend-item alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0" onclick="filterPDC('con-alerta-restricciones', event)" onkeypress="if(event.key === 'Enter') filterPDC('con-alerta-restricciones', event)"><span class="indicator"></span> Con Alerta Restricciones <span id="count-con-alerta-restricciones" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0" onclick="filterPDC('debe-iniciar', event)" onkeypress="if(event.key === 'Enter') filterPDC('debe-iniciar', event)"><span class="indicator"></span> Debe Iniciar <span id="count-debe-iniciar" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item actividad-futura" data-filter="actividad-futura" role="button" tabindex="0" onclick="filterPDC('actividad-futura', event)" onkeypress="if(event.key === 'Enter') filterPDC('actividad-futura', event)"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item adelantada" data-filter="adelantada" role="button" tabindex="0" onclick="filterPDC('adelantada', event)" onkeypress="if(event.key === 'Enter') filterPDC('adelantada', event)"><span class="indicator"></span> Adelantada <span id="count-adelantada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item en-curso" data-filter="en-curso" role="button" tabindex="0" onclick="filterPDC('en-curso', event)" onkeypress="if(event.key === 'Enter') filterPDC('en-curso', event)"><span class="indicator"></span> En Curso <span id="count-en-curso" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada-critica" data-filter="atrasada-critica" role="button" tabindex="0" onclick="filterPDC('atrasada-critica', event)" onkeypress="if(event.key === 'Enter') filterPDC('atrasada-critica', event)"><span class="indicator"></span> Atrasada (Crítica) <span id="count-atrasada-critica" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada" data-filter="atrasada" role="button" tabindex="0" onclick="filterPDC('atrasada', event)" onkeypress="if(event.key === 'Enter') filterPDC('atrasada', event)"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item terminada" data-filter="terminada" role="button" tabindex="0" onclick="filterPDC('terminada', event)" onkeypress="if(event.key === 'Enter') filterPDC('terminada', event)"><span class="indicator"></span> Terminada <span id="count-terminada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item no-requerida" data-filter="no-requerida" role="button" tabindex="0" onclick="filterPDC('no-requerida', event)" onkeypress="if(event.key === 'Enter') filterPDC('no-requerida', event)"><span class="indicator"></span> No Requerida <span id="count-no-requerida" class="count-badge">(...)</span></span>
            </div>
        </div>
    `);

    renderProgramaGeneralLegendModal();

    if (window.DataTableHeightManager && typeof window.DataTableHeightManager.applyToDataTable === "function") {
        window.DataTableHeightManager.applyToDataTable(table, {
            container: "#cuadroTabla",
            internalChrome: 170,
            bottomMargin: 25,
            minHeight: 200,
        });
    } else {
        table.columns.adjust().draw(false);
    }

    $("div.toolbarFiltro").html('<div class="pg-toolbar-filter"><input id="input_buscador" type="text" class="input_buscador form-control form-control-sm pg-filter-input" placeholder="Fitro"><button id="btn_limpiar_buscador" type="button" class="btn btn-danger pg-filter-clear"><i class="fas fa-times-circle"></i> Limpiar</button></div>');

    // activarBuscador("#dt_cliente tbody", table);
    // ocultos(table);
    // obtener_data_editar("#dt_cliente tbody", table); // Moved to drawCallback
    //obtener_id_editar("#dt_general tbody", table);

    // Custom Filter Function
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex, rowData) {
            var classification = classifyPGRow(rowData);
            var state = classification.key;
            var hasRestrictionAlert = Boolean(classification.restrictionAlertKey);

            if (selectedStateFilter) {
                if (selectedStateFilter === 'con-alerta-restricciones') {
                    if (!hasRestrictionAlert) {
                        return false;
                    }
                } else if (state !== selectedStateFilter) {
                    return false;
                }
            }

            if (activeFilters.length === 0) {
                return true;
            }

            return activeFilters.some(function(filterState) {
                if (filterState === 'con-alerta-restricciones') {
                    return hasRestrictionAlert;
                }
                return filterState === state;
            });
        }
    );

    // Filters of text and columns (existing)
    $('#buscadorActividad').on('keyup', function() {
        table.column(5).search($('#buscadorActividad').val()).draw();
    });

    $('#buscadorSemanasInicio').on('change', function() {
        var value = String($(this).val() || '').trim();

        if (!value) {
            table.column(7).search('').draw();
            return;
        }

        if (value === '7') {
            table.column(7).search('^(?:[7-9]|[1-9][0-9]+)$', true, false).draw();
            return;
        }

        table.column(7).search('^' + value + '$', true, false).draw();
    });

    $('#buscadorCritica').on('change', function() {
        var value = String($(this).val() || '').trim();
        if (!value) {
            table.column(10).search('').draw();
            return;
        }
        table.column(10).search('^' + value + '$', true, false).draw();
    });

    $('#buscadorEstado').on('change', function() {
        selectedStateFilter = String($(this).val() || '').trim();
        table.draw();
    });
    
    // Click Handler for Legend
    window.filterPDC = function(filterState, e) {
        var index = activeFilters.indexOf(filterState);
        if (!e.ctrlKey && !e.metaKey) {
            if (activeFilters.length === 1 && activeFilters[0] === filterState) activeFilters = [];
            else activeFilters = [filterState];
        } else {
            if (index > -1) activeFilters.splice(index, 1);
            else activeFilters.push(filterState);
        }

        if (activeFilters.length === 0) {
            $('.pdc-legend-item').removeClass('inactive-filter');
        } else {
            $('.pdc-legend-item').addClass('inactive-filter');
            activeFilters.forEach(function(state) {
                $(".pdc-legend-item[data-filter='" + state + "']").removeClass('inactive-filter');
            });
        }

        // Update Mobile Badge & Button Styles
        $('#mobileFilterCount').text(activeFilters.length);
        if (activeFilters.length > 0) {
            $('.btn-filter-toggle .badge').css('background-color', '#fff').css('color', '#b55211');
            $('.btn-filter-toggle').css('background-color', '#b55211').css('color', '#ffffff').css('border-color', '#b55211');
        } else {
            // Reset to default
            $('.btn-filter-toggle .badge').css('background-color', '#b55211').css('color', '#ffffff');
            $('.btn-filter-toggle').css('background-color', '#ffffff').css('color', '#475569').css('border-color', '#cbd5e1');
        }

        table.draw();
    };
}

var actualizarBarraFiltros = function(db, semana, opcionListar){
    $.ajax({
        method: "POST",
        url: "/programa-general/filtros",
        contenttype:"charset=utf-8",
        data: {"db":db, "semana":semana},
    }).done( function( info ){
        var json_info = (typeof info === 'string' ? JSON.parse( info ) : info);
        var no_requeridas=json_info["data"]["no_requeridas"];
        var lookahead=json_info["data"]["lookahead"];
        var no_iniciadas=json_info["data"]["no_iniciadas"];
        var a_tiempo=json_info["data"]["a_tiempo"];
        var atrasadas=json_info["data"]["atrasadas"];
        var terminadas=json_info["data"]["terminadas"];
        var total=json_info["data"]["total"]*1;
        var activa_no_requeridas=json_info["data"]["activa_no_requeridas"];
        var activa_lookahead=json_info["data"]["activa_lookahead"];
        var activa_no_iniciadas=json_info["data"]["activa_no_iniciadas"];
        var activa_a_tiempo=json_info["data"]["activa_a_tiempo"];
        var activa_atrasadas=json_info["data"]["activa_atrasadas"];
        var activa_terminadas=json_info["data"]["activa_terminadas"];
        var scriptBarraFiltros = "&activa_no_requeridas="+activa_no_requeridas+"&activa_lookahead="+activa_lookahead+"&activa_no_iniciadas="+activa_no_iniciadas+"&activa_a_tiempo="+activa_a_tiempo+"&activa_atrasadas="+activa_atrasadas+"&activa_terminadas="+activa_terminadas;

        document.getElementById('scriptBarraFiltros').value = scriptBarraFiltros;

        //console.log(no_requeridas, lookahead, no_iniciadas, a_tiempo, terminadas, total)
        if(total!=0){
            var p_no_requeridas = formatDecimalComma((no_requeridas/total*100), PG_DECIMALS) +'%';
            var p_lookahead = formatDecimalComma((lookahead/total*100), PG_DECIMALS) +'%';
            var p_no_iniciadas = formatDecimalComma((no_iniciadas/total*100), PG_DECIMALS) +'%';
            var p_a_tiempo = formatDecimalComma((a_tiempo/total*100), PG_DECIMALS) +'%';
            var p_atrasadas = formatDecimalComma((atrasadas/total*100), PG_DECIMALS) +'%';
            var p_terminadas = formatDecimalComma((terminadas/total*100), PG_DECIMALS) +'%';
        }else{
            var p_no_requeridas='0,0%';
            var p_lookahead='0,0%';
            var p_no_iniciadas='0,0%';
            var p_a_tiempo='0,0%';
            var p_atrasadas='0,0%';
            var p_terminadas='0,0%';
        }

        if(opcionListar == "siListar"){
            listar();
        }


        $("#btn_no_requeridas").html("<p class='pg-kpi-line'>No Requeridas <br>"+p_no_requeridas+"</p>");
        $("#btn_lookahead").html("<p class='pg-kpi-line'>Actividad Futura <br>"+p_lookahead+"</p>");
        $("#btn_no_iniciadas").html("<p class='pg-kpi-line'>Debe Iniciar <br>"+p_no_iniciadas+"</p>");
        $("#btn_a_tiempo").html("<p class='pg-kpi-line'>En Curso + Adelant. <br>"+p_a_tiempo+"</p>");
        $("#btn_atrasadas").html("<p class='pg-kpi-line'>Atrasadas <br>"+p_atrasadas+"</p>");
        $("#btn_terminadas").html("<p class='pg-kpi-line'>Terminadas <br>"+p_terminadas+"</p>");

        if(activa_no_requeridas==1){
            $("#btn_no_requeridas").addClass('btn-success');
        }
        if(activa_lookahead==1){
            $("#btn_lookahead").addClass('btn-success');
        }
        if(activa_no_iniciadas==1){
            $("#btn_no_iniciadas").addClass('btn-success');
        }
        if(activa_a_tiempo==1){
            $("#btn_a_tiempo").addClass('btn-success');
        }
        if(activa_atrasadas==1){
            $("#btn_atrasadas").addClass('btn-success');
        }
        if(activa_terminadas==1){
            $("#btn_terminadas").addClass('btn-success');
        }
        if(activa_no_requeridas==0 && activa_lookahead==0 && activa_no_iniciadas==0 && activa_a_tiempo==0 && activa_atrasadas==0 && activa_terminadas==0){
            $("#btn_total").addClass('btn-success');
        }
    });
}

var cambiarClaseBarraFiltros=function(p){
    //console.log(p);
    if(p=='no_requeridas'){
        if($('#btn_no_requeridas').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                p = 'total';
            }
        }
    }else if(p=='lookahead'){
        if($('#btn_lookahead').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                    p = 'total';
            }
        }
    }else if(p=='no_iniciadas'){
        if($('#btn_no_iniciadas').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                p = 'total';
            }
        }
    }else if(p=='a_tiempo'){
        if($('#btn_a_tiempo').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                p = 'total';
            }
        }
    }else if(p=='atrasadas'){
        if($('#btn_atrasadas').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                p = 'total';
            }
        }
    }else if(p=='terminadas'){
        if($('#btn_terminadas').hasClass('btn-success')==true){
            var activa = 0;
        }else{
            var activa = 1;
            if($('#btn_no_requeridas').hasClass('btn-success')==true && $('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_a_tiempo').hasClass('btn-success')==true && $('#btn_atrasadas').hasClass('btn-success')==true){
                p = 'total';
            }
        }
    }
    location.assign("/programa-general/set-filtro?clase="+p+"&activa="+activa);
}

/*Toma los datos de la fila en la que se presionó el botón editar*/
var obtener_data_editar = function(tbody, table) {
    var max_semana = document.getElementById('Max_Semana').value;
    var semana = document.getElementById('semana').value;
    var permiso = document.getElementById('permiso').value;

    var only_once = true;

    if (typeof window.rbacCapabilities !== 'undefined') {
        // En PG, Semanas pasadas (< max_semana - 2) solo se editan bajo reglas muy particulares o si eres Admin root (P, Root)
        // En este contexto legacy priorizamos usar la Capability, pero si no la hay, mantenemos el fallback original.
        if ((max_semana - 2) >= semana) {
            only_once = window.rbacCapabilities.canEditPastGeneralProgram ? false : true;
        } else {
            only_once = !window.rbacCapabilities.canEditGeneralProgram;
        }
    } else {
        if ((max_semana - 2) >= semana) {
            if (permiso == "P") {
                only_once = true;
            } else {
                only_once = false;
            }
        } else {
            if (permiso == "G" || permiso == "S" || permiso == "SG" || permiso == "OT" || permiso == "DCV" || permiso == "V" || permiso == "C") {
                only_once = false;
            } else {
                only_once = true;
            }
        }
    }

    var Semanal_Confirmada = document.getElementById('Semanal_Confirmada').value;

  $(tbody).one("click", "td", function() {
        if(Semanal_Confirmada == 1 && permiso!="P"){
            if (only_once == true) {
                $(".texto_semanal_confirmada").html("<p>En esta Semana los compromisos de la <b>Programación Semanal</b> ya fueron confirmados. Por esto, el programa general ya no puede ser modificado hasta que se cree la <b>Semana "+(Number(semana)+1)+"</b>.</p><p> Recuerde que el procedimiento de Last Planner debe seguirse con la siguiente metodología: </p><p><b>1.</b> Calificar la semana que se termina (En este caso la Semana "+(Number(semana))+").<br><b>2.</b> Abrir la pestaña <b>\"Semanas del Proyecto\"</b> y crear la nueva Semana (En este caso se debe crear la Semana "+(Number(semana)+1)+").<br><b>3.</b> Actualizar el estado de ejecución de las actividades en el <b>\"Programa General\"</b>, en la semana creada (Semana "+(Number(semana)+1)+").<br><b>4.</b> Actualizar la <b>\"Liberación de Restricciones\"</b> de la semana creada (Semana "+(Number(semana)+1)+").<br><b>5.</b> Generar los compromisos de la <b>\"Programación Semanal\"</b> de la semana creada (Semana "+(Number(semana)+1)+").</p>");
                $("#modal_semanal_confirmada_Label").html("<b>Programa General Bloqueado!!</b>");
                $("#modal_semanal_confirmada").modal("show");
                recargarTabla("listar");
            }
        }else{
            //console.log("hola");
        if (only_once == true) {
                var data= table.row($(this).parents("tr")).data();
                if(data.Titulo==0){
                    var Id=$("#Id").val(data.Consecutivo_en_Programa),
                    //medir_productividad=$("#medir_productividad").val(data.medir_productividad),
                    opcion = $("#opcion").val("modificar");
                    var codigo_html_unidad = "<select id='select_unidad' name='unidad' class='form-control form-control-sm'><option value=''></option><option value='ml'>ml</option><option value='m2'>m2</option><option value='m3'>m3</option><option value='un'>Un</option><option value='gl'>Gl</option><option value='kg'>kg</option><option value='%'>%</option><option value='Niveles'>Niveles</option></select>";
                    $( this ).parent().find('.input_unidad').html(codigo_html_unidad);

                    var cantidadPptoNumero = toNumber(data.cantidad_ppto, null);
                    var cantidad_ppto_safe = (cantidadPptoNumero === null) ? "" : formatDecimalComma(cantidadPptoNumero, PG_DECIMALS);
                    var codigo_html_cantidad_ppto = "<input id='input_cantidad_ppto' class='form-control form-control-sm' type='text' inputmode='decimal' value='"+cantidad_ppto_safe+"'></input>";
                    $( this ).parent().find('.input_cantidad_ppto').html(codigo_html_cantidad_ppto);

                    var codigo_actividad = global_codigos_actividad_options;
                    var codigo_html_codigo_actividad = "<select id='select_codigo_actividad' name='codigo_actividad' class='form-control form-control-sm' onchange=bloquear_unidad()><option value=''></option>"+codigo_actividad+"</select>";
                    if (permiso=="P" || permiso=="A"){
                        $( this ).parent().find('.input_codigo_actividad').html(codigo_html_codigo_actividad);
                        $("#select_codigo_actividad").val(data.codigo_actividad).change();
                    }else{
                        codigo_html_codigo_actividad = $( this ).parent().find('.input_Actividad').html() + codigo_html_codigo_actividad;
                        $( this ).parent().find('.input_Actividad').html(codigo_html_codigo_actividad);
                        $("#select_codigo_actividad").val(data.codigo_actividad).change();
                        $("#select_codigo_actividad").attr('disabled', true);
                        $("#select_codigo_actividad").hide();
                    }


                    var ejecutadoMostrar = 0;
                    if (cantidadPptoNumero === null) {
                        ejecutadoMostrar = toNumber(data.Ejecutado, 0) * 100;
                    } else {
                        ejecutadoMostrar = toNumber(data.Ejecutado, 0) * cantidadPptoNumero;
                    }
                    var Ejecutado = formatDecimalComma(ejecutadoMostrar, PG_DECIMALS);
                    var codigo_html_ejecutado = "<input id='input_Ejecutado_Editar' name='Ejecutado_Editar' class='form-control form-control-sm' type='text' inputmode='decimal' value='"+Ejecutado+"'></input>";
                    $( this ).parent().find('.input_Ejecutado').html(codigo_html_ejecutado);
                    bindDecimalInputNormalization("#input_cantidad_ppto");
                    bindDecimalInputNormalization("#input_Ejecutado_Editar");

                    var codigo_html_botones = "<button type= 'button' id='btn_guardar_editar' class='guardar btn btn-success btn-sm' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><!--<button type= 'button' id='btn_cancelar_editar' class='cancelar btn btn-danger btn-sm' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>-->";
                    $( this ).parent().find('.Botones').html(codigo_html_botones);

                    var fecha_inicio_safe = (data.Fecha_Inicio === null) ? "" : data.Fecha_Inicio;
                    var codigo_html_Fecha_Inicio =  "<input id='select_Fecha_Inicio' name='Fecha_Inicio' class='form-control form-control-sm' type='text' value='"+fecha_inicio_safe+"'></input>";
                    $( this ).parent().find('.input_Fecha_Inicio').html(codigo_html_Fecha_Inicio);


                    $( "#select_Fecha_Inicio" ).datepicker({dateFormat: 'yy-mm-dd',
                                                                                            changeMonth: true,
                                                                                            changeYear: true,
                                                                                            showOtherMonths: true,
                                                                                            selectOtherMonths: true,
                                                                                            defaultDate:data.Fecha_Inicio,
                                                                                        });

                    var fecha_fin_safe = (data.Fecha_Fin === null) ? "" : data.Fecha_Fin;
                    var codigo_html_Fecha_Fin =  "<input id='select_Fecha_Fin' name='Fecha_Fin' class='form-control form-control-sm' type='text' value='"+fecha_fin_safe+"'></input>";
                    $( this ).parent().find('.input_Fecha_Fin').html(codigo_html_Fecha_Fin);

                    $( "#select_Fecha_Fin" ).datepicker({dateFormat: 'yy-mm-dd',
                                                                                            changeMonth: true,
                                                                                            changeYear: true,
                                                                                            showOtherMonths: true,
                                                                                            selectOtherMonths: true,
                                                                                            defaultDate:data.Fecha_Fin,
                                                                                        });



                    $("#select_medir_productividad").val(data.medir_productividad).change();
                    if(data.unidad == "" || data.unidad == null){
                        data.unidad = "%";
                    }
                    $("#select_unidad").val(data.unidad).change();
                    $("#input_Ejecutado_Editar").focus();
                    $("#input_Ejecutado_Editar").select();
                    only_once = false;
                    $("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
                            if(e.keyCode==13){
                                    $("#btn_guardar_editar").click();
                                    only_once = true;
                            }
                    });
                    $("#dt_cliente td input, #dt_cliente td select, #dt_cliente td textarea").keydown(function(e){
                            if(e.keyCode==27){
                                $("#btn_guardar_editar").click();
                                    //$("#btn_cancelar_editar").click();
                                only_once = true;
                            }
                    });
                }else{
                    obtener_data_editar("#dt_cliente tbody", table);
                }
            }
            cancelarEdicionFila();
            guardar();
        }
  });
}

var bloquear_unidad = function() {
    var codigo = $("#select_codigo_actividad").val();
    
    if (codigo == '') {
        $("#select_unidad").attr('disabled', false);
    } else {
        $("#select_unidad").attr('disabled', true);
        
        // Find unit in local cache
        var actividad = global_actividades_data.find(function(item) {
            return item.codigo_actividad == codigo;
        });
        
        if (actividad) {
            $("#select_unidad").val(actividad.unidad).change();
        }
    }
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
  $("#btn_cancelar_editar").one("click", function(e) {
    e.preventDefault();
    recargarTabla("listar");
  });
}

/* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
var guardar = function() {
    $("#btn_guardar_editar").one("click", function(e){
        e.preventDefault();
        var db = document.getElementById('baseDatos').value;
        var semana = document.getElementById('semana').value;

        var cantidadPptoRaw = $("#input_cantidad_ppto").val();
        var ejecutadoRaw = $("#input_Ejecutado_Editar").val();

        var cantidadPptoValor = toNumber(cantidadPptoRaw, null);
        if (cantidadPptoValor !== null) {
            cantidadPptoValor = roundToDecimals(cantidadPptoValor, PG_DECIMALS);
            if (cantidadPptoValor <= 0) {
                cantidadPptoValor = null;
            }
        }

        var ejecutadoValorIngresado = toNumber(ejecutadoRaw, null);
        if (ejecutadoValorIngresado !== null) {
            ejecutadoValorIngresado = roundToDecimals(ejecutadoValorIngresado, PG_DECIMALS);
            $("#input_Ejecutado_Editar").val(formatDecimalComma(ejecutadoValorIngresado, PG_DECIMALS));
        }

        if (cantidadPptoValor !== null) {
            $("#input_cantidad_ppto").val(formatDecimalComma(cantidadPptoValor, PG_DECIMALS));
        }

        var ejecutadoRatioValor = null;
        if (ejecutadoValorIngresado !== null) {
            if (cantidadPptoValor === null) {
                ejecutadoRatioValor = ejecutadoValorIngresado / 100;
            } else {
                ejecutadoRatioValor = ejecutadoValorIngresado / cantidadPptoValor;
            }
        }

        var input_Ejecutado_Editar = "Nulo";
        if (ejecutadoRatioValor !== null) {
            ejecutadoRatioValor = roundToDecimals(ejecutadoRatioValor, PG_RATIO_DECIMALS);
            input_Ejecutado_Editar = formatDecimal(ejecutadoRatioValor, PG_RATIO_DECIMALS);
        }

        var cantidadPptoPayload = (cantidadPptoValor === null) ? "" : formatDecimal(cantidadPptoValor, PG_DECIMALS);

        if($("#select_unidad").val() == "" || $("#select_unidad").val() == null){
            var UnidadValor = "%";
            $("#select_unidad").val(UnidadValor).change();
        }else{
            var UnidadValor = $("#select_unidad").val();
        }

        frm="Id="+($("#Id").val())+"&opcion="+($("#opcion").val())+"&Fecha_Inicio="+($("#select_Fecha_Inicio").val())+"&Fecha_Fin="+($("#select_Fecha_Fin").val());

        frm=frm+"&Ejecutado="+encodeURIComponent(input_Ejecutado_Editar)+"&codigo_actividad="+($("#select_codigo_actividad").val())+"&unidad="+($("#select_unidad").val())+"&cantidad_ppto="+encodeURIComponent(cantidadPptoPayload)+"&editarActividadAsociar=0";
        // console.log(frm);

        if(ejecutadoRatioValor !== null && ejecutadoRatioValor > 1){
            if(cantidadPptoValor !== null){
                var cantidadEjecutadaCalculada = roundToDecimals(ejecutadoRatioValor * cantidadPptoValor, PG_DECIMALS);
                $(".texto_cantidad_ejecutada_error").html("<p>La cantidad ejecutada no debe ser mayor a la cantidad del presupuesto!! (La cantidad en presupuesto es de <b>"+ formatDecimalComma(cantidadPptoValor, PG_DECIMALS) + UnidadValor + "</b>, y se está asignando una ejecución de <b>" + formatDecimalComma(cantidadEjecutadaCalculada, PG_DECIMALS) + UnidadValor + "</b>).</p>");
                $("#modal_cantidad_ejecutada_error").modal("show");
            }else{
                var porcentajeEjecutado = roundToDecimals(ejecutadoRatioValor * 100, PG_DECIMALS);
                $(".texto_cantidad_ejecutada_error").html("<p>La cantidad ejecutada no debe ser mayor a la cantidad del presupuesto!! (La cantidad en presupuesto es de <b>100,0%</b>, y se está asignando una ejecución de <b>" + formatDecimalComma(porcentajeEjecutado, PG_DECIMALS) + "%</b>).</p>");
                $("#modal_cantidad_ejecutada_error").modal("show");
            }
        }else{
            $.ajax({
                method: "POST",
                url: "/api/general/update?db="+db+"&semana="+semana,
                contenttype:"charset=utf-8",
                data: frm
            }).done(function(info){
                recargarTabla("listar");
            });
        }
    });
}

var actualizarEjecucion = function() {
    var db = document.getElementById('baseDatos_PHP') ? document.getElementById('baseDatos_PHP').value : document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana_PHP') ? document.getElementById('semana_PHP').value : document.getElementById('semana').value;
    var fechaInicioSem = document.getElementById('Fecha_Inicio_SemYMD_PHP') ? document.getElementById('Fecha_Inicio_SemYMD_PHP').value : document.getElementById('Fecha_Inicio_SemYMD').value;

    $("#actualizarEjecucion").attr('disabled', true);
    $("#actualizarEjecucion").html('Actualizando... <i class="fas fa-spinner fa-spin ml-2"></i>');

    $.ajax({
        method: "POST",
        url: "/api/general/update-batch?db="+db+"&semana="+semana,
        contenttype:"charset=utf-8",
        dataType: "json",
        data: {"opcion": "modificargrupo", "Id1": "Consecutivo_en_Programa > 0", "Ejecutado": "Ejecutado", "inicio_semana": fechaInicioSem}
    }).done(function(info){
        if(info && info.respuesta == "BIEN"){
            recargarTabla("listar");
            if (window.AIA && window.AIA.Notice) window.AIA.Notice.badge('success', 'Ejecución Actualizada Correctamente'); else alert("Ejecución Actualizada Correctamente");
        } else {
            if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error al actualizar la ejecución"); else alert("Error al actualizar la ejecución");
        }
    }).fail(function(xhr){
        console.error("Error en update_batch:", xhr && xhr.responseText ? xhr.responseText : xhr);
        if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error al actualizar la ejecución"); else alert("Error al actualizar la ejecución");
    }).always(function(){
        $("#actualizarEjecucion").attr('disabled', false);
        $("#actualizarEjecucion").html('Actualizar Ejecución <i class="fas fa-sync fa-lg ml-2"></i>');
    });
}

var descargarCorteProgramacion = function() {
    var db = document.getElementById('baseDatos_PHP') ? document.getElementById('baseDatos_PHP').value : document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana_PHP') ? document.getElementById('semana_PHP').value : document.getElementById('semana').value;
    
    $("#descargarCorteProgramacion").attr('disabled', true);
    $("#descargarCorteProgramacion").html('Generando... <i class="fas fa-spinner fa-spin ml-2"></i>');

    $.ajax({
        url: "/reportes/corte-programacion",
        method: "POST",
        data: { db: db, semana: semana },
        dataType: "json",
        success: function(response) {
            if (response.url) {
                var link = document.createElement('a');
                link.href = response.url;
                link.download = response.url.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error: No se pudo obtener el archivo."); else alert("Error: No se pudo obtener el archivo.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error descargando corte:", error);
            if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error al generar el corte de programación."); else alert("Error al generar el corte de programación.");
        },
        complete: function() {
            $("#descargarCorteProgramacion").attr('disabled', false);
            $("#descargarCorteProgramacion").html('Descargar Corte <i class="fas fa-download fa-lg ml-2"></i>');
        }
    });
}

var recargarTabla = function(accion){
    var table = $('#dt_cliente').DataTable();
    var db = document.getElementById('baseDatos_PHP') ? document.getElementById('baseDatos_PHP').value : document.getElementById('baseDatos').value;
    var semana = document.getElementById('semana_PHP') ? document.getElementById('semana_PHP').value : document.getElementById('semana').value;
    var scriptBarraFiltros = document.getElementById('scriptBarraFiltros').value;
    
    if(accion == "listar"){
        table.ajax.url("/api/general/list?db="+db+"&semana="+semana+scriptBarraFiltros).load();
    }
}

var limpiar_datos = function(){
    $("#opcion").val("registrar");
    $("#Id").val("");
}
