/**
 * Módulo de Inicialización de Handsontable para Actualización de Cronograma (Mapeo Manual)
 * Arquitectura CSS 2026 - LPS
 */

window.HOTActualizarModule = (function() {
    var hot = null;
    var rawData = [];
    var showingUnmappedOnly = false;

    // Configuración de validadores y regexs
    const regexNumerico = /^-?\d*(\.\d+)?$/;

    // Obtenemos el JSON de opciones pre-cargado desde PHP
    var sourceDataHistorica = [];
    try {
        var historicoJsonNode = document.getElementById("historicoData");
        if (historicoJsonNode) {
            sourceDataHistorica = JSON.parse(historicoJsonNode.textContent);
        }
    } catch(e) {
        console.error("Error cargando data histórica para el Dropdown:", e);
    }

    /**
     * Renderizador Custom para la Actividad a Asociar
     * - Si está vacía o "*No Asociada*" => Fondo Naranja Corporativo
     * - Si está mapeada => Celda Verde, Texto normal
     */
    function ActivityMappingRenderer(instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.HtmlRenderer.apply(this, arguments);
        
        var displayValue = (value || '').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        var chipHtml = displayValue;

        if (value && value !== '*No Asociada*') {
            chipHtml = `<div class="aia-table-chip" style="background: rgba(181,82,17,0.08); color: #8b4011; border: 1px solid rgba(181,82,17,0.2); border-radius: 4px; padding: 2px 8px; font-size: 0.8rem; line-height: 1.3; white-space: normal; word-break: break-word;">${displayValue}</div>`;
        } else if (value === '*No Asociada*') {
            chipHtml = `<div style="color: #c90000; font-weight: 700; font-size: 0.8rem;"><i class="fas fa-exclamation-triangle"></i> PENDIENTE</div>`;
        }
        td.innerHTML = chipHtml;
        td.className = "htMiddle htCenter force-wrap pg-cell-editable";

        if (value === null || value === '' || value === '*No Asociada*') {
            td.style.backgroundColor = 'rgba(235, 64, 52, 0.05)'; 
        } else {
            td.style.backgroundColor = 'rgba(26, 86, 51, 0.05)'; 
        }
    }

    /**
     * Renderizador estático para Columnas de Lectura (Actividad Nueva)
     */
    function ReadOnlyRenderer(instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.HtmlRenderer.apply(this, arguments);
        td.className = "htMiddle force-wrap pg-cell-readonly";
        
        // Colorear toda la fila sutilmente si la actividad no está asociada
        var isMapped = instance.getDataAtRowProp(row, 'programaAnteriorAsociar');
        if (isMapped === null || isMapped === '' || isMapped === '*No Asociada*') {
            td.style.backgroundColor = 'rgba(255,83,51,0.05)'; 
        } else {
            td.style.backgroundColor = '#f8fafc'; // Color por defecto readonly
        }
    }

    /**
     * Cargar y Filtrar Datos
     */
    function loadData() {
        console.log("🔥 [MapeoManual] Entrando a loadData().");
        $('#loading').show();
        var db = document.getElementById('baseDatos').value;
        var semanaInput = document.getElementById('semana');
        var semanaVal = semanaInput ? semanaInput.value : 'MISSING';
        
        console.log("🔥 [MapeoManual] Valor detectado en input #semana: ", semanaVal);
        
        // Fetch desde el API: Consultamos la semana de borrador (siguiente a la activa)
        var targetSemana = parseInt(semanaVal) + 1;
        var fullUrl = "/api/general/list?db=" + db + "&semana=" + targetSemana;
        
        console.log("🔥 [MapeoManual] targetSemana calculado: ", targetSemana);
        console.log("🔥 [MapeoManual] Iniciando fetch GET: ", fullUrl);
        fetch(fullUrl)
            .then(res => {
                console.log("🔥 [MapeoManual] Status: ", res.status, "| targetSemana requested: ", targetSemana);
                if (!res.ok) throw new Error("HTTP error " + res.status);
                return res.json();
            })
            .then(response => {
                console.log("🔥 [MapeoManual] JSON parseado. Data length: ", (response.data ? response.data.length : 'NULL'));
                if (response.data) {
                    rawData = response.data;
                } else {
                    rawData = []; 
                }
                applyFilterAndRender();
            })
            .catch(err => {
                console.error("🔥 [MapeoManual] Error en fetch: ", err);
                if (typeof toastr !== 'undefined') toastr.error("Error de conexión al cargar datos.");
                rawData = [];
                applyFilterAndRender();
            })
            .finally(() => {
                console.log("🔥 [MapeoManual] Finally alcanzado. Ocultando loader.");
                setTimeout(() => {
                    $('#loading').hide();
                    console.log("🔥 [MapeoManual] Loader ocultado.");
                }, 500);
            });
    }

    function applyFilterAndRender() {
        var filteredData = rawData;
        
        if (showingUnmappedOnly) {
            filteredData = rawData.filter(function(row) {
                return row.programaAnteriorAsociar === '*No Asociada*' || row.programaAnteriorAsociar === null || row.programaAnteriorAsociar === '';
            });
            $("#btn_toggleFiltroMapeo").html('Mostrando Pendientes <i class="fas fa-filter fa-lg"></i>');
            $("#btn_toggleFiltroMapeo").addClass('btn-outline-primary active').removeClass('btn-primary');
        } else {
            $("#btn_toggleFiltroMapeo").html('Mostrando Todas <i class="fas fa-list fa-lg"></i>');
            $("#btn_toggleFiltroMapeo").addClass('btn-primary').removeClass('btn-outline-primary active');
        }

        if (filteredData.length === 0) {
            console.warn("⚠️ [MapeoManual] Sin datos para mostrar.");
        }

        console.log("🔥 [MapeoManual] Renderizando tabla. Datos mostrados: ", filteredData.length);
        if (hot) {
            hot.loadData(filteredData);
        } else {
            initHandsontable(filteredData);
            console.log("🔥 [MapeoManual] Handsontable inicializado.");
        }
    }

    /**
     * Guardado al Vuelo (AJAX Update)
     */
    function autoSaveRow(rowId, changesObj, visualRowIndex) {
        var db = document.getElementById('baseDatos').value;
        var semana = document.getElementById('semana').value;
        
        // Mostrar badge "Guardando..."
        $('#save-status').text('Guardando...').removeClass('badge-success badge-danger').addClass('badge-warning').show();

        // El endpoint requiere semana destino (semana + 1 al ser importador)
        var targetSemana = parseInt(semana) + 1;

        // Forzar ID numérico (Consecutivo_en_Programa) para evitar errores SQL con IDs jerárquicos (puntos)
        var cleanId = (typeof rowId === 'string' && rowId.includes('.')) ? null : rowId;
        var rowData = hot.getSourceDataAtRow(visualRowIndex);
        if (!cleanId && rowData) {
            cleanId = rowData.Consecutivo_en_Programa;
        }

        var formData = new URLSearchParams();
        formData.append('Id', cleanId);
        formData.append('opcion', 'editar');
        formData.append('editarActividadAsociar', '1');
        
        // Mapear campos HOT a keys de backend:
        // Hot Prop -> Backend Param
        // Actividad -> No se envía (es de lectura)
        if(changesObj['programaAnteriorAsociar'] !== undefined) formData.append('actividadAsociar', changesObj['programaAnteriorAsociar']);
        if(changesObj['Fecha_Inicio'] !== undefined) formData.append('Fecha_Inicio', changesObj['Fecha_Inicio']);
        if(changesObj['Fecha_Fin'] !== undefined) formData.append('Fecha_Fin', changesObj['Fecha_Fin']);
        if(changesObj['unidad'] !== undefined) formData.append('unidad', changesObj['unidad']);
        if(changesObj['cantidad_ppto'] !== undefined) formData.append('cantidad_ppto', changesObj['cantidad_ppto']);
        if(changesObj['Ejecutado'] !== undefined) formData.append('Ejecutado', changesObj['Ejecutado']);

        // Mapear campos: Prioridad al cambio reciente, sino valor actual de la fila
        formData.append('actividadAsociar', changesObj['programaAnteriorAsociar'] !== undefined ? changesObj['programaAnteriorAsociar'] : (rowData.programaAnteriorAsociar || '*No Asociada*'));
        formData.append('Fecha_Inicio', changesObj['Fecha_Inicio'] !== undefined ? changesObj['Fecha_Inicio'] : (rowData.Fecha_Inicio || ''));
        formData.append('Fecha_Fin', changesObj['Fecha_Fin'] !== undefined ? changesObj['Fecha_Fin'] : (rowData.Fecha_Fin || ''));
        formData.append('unidad', changesObj['unidad'] !== undefined ? changesObj['unidad'] : (rowData.unidad || '%'));
        formData.append('cantidad_ppto', changesObj['cantidad_ppto'] !== undefined ? changesObj['cantidad_ppto'] : (rowData.cantidad_ppto || ''));
        
        // Lógica de Ejecutado vs Cantidad Presupuesto
        var editedEjecutado = changesObj['Ejecutado'] !== undefined ? changesObj['Ejecutado'] : rowData.Ejecutado;
        var currentPpto = changesObj['cantidad_ppto'] !== undefined ? changesObj['cantidad_ppto'] : rowData.cantidad_ppto;

        if (editedEjecutado === null || editedEjecutado === "") {
            formData.append('Ejecutado', "Nulo");
        } else {
            var pptoFactor = (currentPpto == null || currentPpto == "" || parseFloat(currentPpto) === 0) ? 100 : parseFloat(currentPpto);
            formData.append('Ejecutado', (parseFloat(editedEjecutado) / pptoFactor).toFixed(4));
        }
        
        formData.append('codigo_actividad', rowData.codigo_actividad || '');

        fetch("/api/general/update?db=" + db + "&semana=" + targetSemana, {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.respuesta === "BIEN") {
                $('#save-status').text('Autoguardado').removeClass('badge-warning').addClass('badge-success');
                setTimeout(() => $('#save-status').fadeOut(), 2000);
            } else {
                $('#save-status').hide();
                $('#save-error').text(res.mensaje || 'Error al guardar').show();
                toastr.error("Error al guardar fila ID: " + rowId);
            }
        })
        .catch(err => {
            console.error(err);
            $('#save-status').hide();
            $('#save-error').show();
        });
    }

    /**
     * Inicialización
     */
    function initHandsontable(data) {
        var container = document.getElementById('hot-container');

        var hotConfig = {
            data: data,
            rowHeaders: true,
            colHeaders: [
                "Consecutivo",
                "Id",
                "Actividad Nueva",
                "Asociar con...<span class='changeType' title='Filtrar por Asociación'></span>",
                "F. Inicio",
                "F. Fin",
                "Unidad",
                "Cant. PPTO",
                "Ejec. Real"
            ],
            columns: [
                { data: 'Consecutivo_en_Programa', type: 'numeric', readOnly: true, width: 40 },
                { data: 'Id', type: 'text', readOnly: true, width: 40 },
                { data: 'Actividad', type: 'text', readOnly: true, width: 350, renderer: ReadOnlyRenderer },
                { 
                    data: 'programaAnteriorAsociar', 
                    type: 'text',
                    width: 300, 
                    editor: 'tomSelectSingle',
                    tomSelectOptions: sourceDataHistorica,
                    renderer: ActivityMappingRenderer
                },
                { data: 'Fecha_Inicio', type: 'date', dateFormat: 'YYYY-MM-DD', width: 90, className: "htCenter htMiddle pg-cell-editable" },
                { data: 'Fecha_Fin', type: 'date', dateFormat: 'YYYY-MM-DD', width: 90, className: "htCenter htMiddle pg-cell-editable" },
                { data: 'unidad', type: 'text', width: 60, className: "htCenter htMiddle pg-cell-editable" },
                { data: 'cantidad_ppto', type: 'numeric', width: 80, className: "htCenter htMiddle pg-cell-editable" },
                { data: 'Ejecutado', type: 'numeric', width: 80, className: "htCenter htMiddle pg-cell-editable" }
            ],
            
            // Características UX AIA 2026
            stretchH: 'all',
            autoWrapRow: false,
            autoWrapCol: false,
            autoRowSize: true, // Habilitar cálculo de altura automática por contenido
            width: '100%',
            height: '100%',
            licenseKey: 'non-commercial-and-evaluation',
            wordWrap: false,
            manualColumnResize: true,
            filters: true,
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            outsideClickDeselects: false, // Vital para Select2/TomSelect
            hiddenColumns: {
                columns: [0], // Ocultar Consecutivo por defecto
                indicators: false
            },

            afterChange: function(changes, source) {
               if (source === 'loadData' || !changes) return;

               // Agrupar cambios por fila
               var rowChanges = {};
               changes.forEach(function(change) {
                   var visualRow = change[0];
                   var prop = change[1];
                   var oldVal = change[2];
                   var newVal = change[3];

                   if (oldVal !== newVal) {
                       if (!rowChanges[visualRow]) rowChanges[visualRow] = {};
                       rowChanges[visualRow][prop] = newVal;
                   }
               });

               // Disparar guardado por cada fila modificada
                Object.keys(rowChanges).forEach(function(visualRowStr) {
                   var visualRow = parseInt(visualRowStr);
                   var rowData = this.getSourceDataAtRow(visualRow);
                   var idRow = rowData.Consecutivo_en_Programa; // Usar consecutivo interno para SQL
                   autoSaveRow(idRow, rowChanges[visualRow], visualRow);
                }.bind(this));

               // Si el cambio fue en asociación, re-renderizar para actualizar el fondo de la fila entera
               if (changes.some(c => c[1] === 'programaAnteriorAsociar')) {
                   this.render();
               }
           }
        };

        hot = new Handsontable(container, hotConfig);

        // Bind Custom Togglers
        $("#btn_toggleFiltroMapeo").on("click", function() {
            showingUnmappedOnly = !showingUnmappedOnly;
            applyFilterAndRender();
        });
    }

    return {
        init: function() {
            console.log("🔥 [MapeoManual] HOTActualizarModule.init() alcanzado.");
            try {
                loadData();
            } catch (error) {
                console.error("🔥 [MapeoManual] Excepción síncrona en init(): ", error);
            }
        }
    };
})();
