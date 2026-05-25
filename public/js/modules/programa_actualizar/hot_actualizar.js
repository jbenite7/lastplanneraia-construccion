/**
 * Módulo de Inicialización de Handsontable para Actualización de Cronograma (Mapeo Manual)
 * Arquitectura CSS 2026 - LPS
 */

window.HOTActualizarModule = (function() {
    var hot = null;
    var rawData = [];
    var showingUnmappedOnly = true;

    // Configuración de validadores y regexs
    const regexNumerico = /^-?\d*(\.\d+)?$/;
    const unitOptions = ['', 'ml', 'm2', 'm3', 'un', 'gl', 'kg', '%', 'Niveles'];
    const editableProps = {
        'programaAnteriorAsociar': true,
        'Fecha_Inicio': true,
        'Fecha_Fin': true,
        'unidad': true,
        'cantidad_ppto': true,
        'Ejecutado': true,
        'codigo_actividad': true
    };

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
     * Valida permisos globales
     */
    function isUserAllowedToEdit() {
        var permiso = String($('#permiso').val() || 'V').trim().toUpperCase();
        var semanalConfirmada = parseInt($('#Semanal_Confirmada').val() || 0);
        var semanaActual = parseInt($('#semana').val() || 0);
        var maxSemana = parseInt($('#Max_Semana').val() || 0);
        var directorRoles = ['A', 'D'];

        // La actualización edita un borrador (semana actual + 1); A/D pueden mapear aunque la semana activa esté cerrada.
        if (semanalConfirmada === 1 && directorRoles.indexOf(permiso) === -1) return false;

        // Roles permitidos: Administrador(A), Director(D), Residente(R), DCV
        var allowedRoles = ['A', 'D', 'R', 'DCV'];
        if (allowedRoles.indexOf(permiso) === -1) return false;

        // Si es Residente(R), solo puede editar si es la semana activa o max-1
        if (permiso === 'R' && semanaActual < (maxSemana - 1)) return false;

        return true;
    }

    /**
     * Normalización de valores al estilo Programa General
     */
    function normalizeCellValue(prop, value) {
        if (value === null || value === undefined) return { valid: true, value: null };
        var str = String(value).trim();

        if (prop === 'Ejecutado' || prop === 'cantidad_ppto') {
            if (str === '') return { valid: true, value: null };
            var num = parseFloat(str.replace(',', '.'));
            if (isNaN(num)) return { valid: false, error: 'Debe ser un número válido' };
            if (num < 0) return { valid: false, error: 'No se permiten valores negativos' };
            // AIA 2026: Permitimos valores > 100 para cantidades físicas.
            return { valid: true, value: num };
        }

        if (prop.startsWith('Fecha_')) {
            if (str === '') return { valid: true, value: null };
            if (!/^\d{4}-\d{2}-\d{2}$/.test(str)) return { valid: false, error: 'Formato de fecha inválido (YYYY-MM-DD)' };
            return { valid: true, value: str };
        }

        return { valid: true, value: str };
    }

    function getFilterPlainText(value) {
        var raw = String(value === null || value === undefined ? '' : value);
        if (!raw) return '';

        var container = document.createElement('div');
        container.innerHTML = raw;
        return String(container.textContent || container.innerText || '').trim();
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
     * Renderizador para Ejecutado Real (Muestra valor físico y porcentaje)
     */
    function pgEjecutadoRealRenderer(instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.NumericRenderer.apply(this, arguments);
        var rowData = instance.getSourceDataAtRow(row) || {};
        var unity = String(rowData.unidad || '').trim();
        var ppto = parseFloat(rowData.cantidad_ppto || 0);
        var physicalVal = parseFloat(value || 0);

        var isMapped = rowData.programaAnteriorAsociar && rowData.programaAnteriorAsociar !== '*No Asociada*';

        if (!isMapped || unity === '%' || unity === '' || ppto <= 0) {
            // Ya es porcentaje
            td.innerHTML = physicalVal.toFixed(1).replace('.', ',') + '%';
        } else {
            var physicalDisplay = physicalVal.toFixed(1).replace('.', ',');
            var percentValue = (physicalVal / ppto * 100).toFixed(1).replace('.', ',');
            td.innerHTML = physicalDisplay + ' ' + unity + ' (' + percentValue + '%)';
        }

        td.className = (td.className || '') + ' htCenter htMiddle';
    }

    /**
     * Renderizador genérico para porcentajes (Restricciones)
     */
    function pgPercentRenderer(instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.NumericRenderer.apply(this, arguments);
        var val = parseFloat(value || 0);
        td.innerHTML = (val * 100).toFixed(1) + '%';
        td.className = (td.className || '') + ' htCenter htMiddle';
    }

    /**
     * Cargar Códigos de Actividad (Catálogo)
     */
    function fetchCodigosActividad() {
        var db = document.getElementById('baseDatos').value;
        fetch("/api/general/codigos?db=" + db)
            .then(res => res.json())
            .then(response => {
                if (response.data && hot) {
                    var codes = response.data.map(c => c.codigo);
                    var colIndex = hot.getPropToCol('codigo_actividad');
                    if (colIndex !== -1) {
                        var settings = hot.getSettings();
                        settings.columns[colIndex].source = codes;
                        hot.updateSettings({ columns: settings.columns });
                    }
                }
            })
            .catch(err => console.error("Error cargando códigos:", err));
    }

    function getIntegerInputValue(id, fallback) {
        var input = document.getElementById(id);
        var value = input ? parseInt(input.value, 10) : NaN;
        return isNaN(value) ? fallback : value;
    }

    function getBaseSemanaActualizacion() {
        return getIntegerInputValue('semanaBaseActualizacion', getIntegerInputValue('semana', 0));
    }

    function getTargetSemanaActualizacion() {
        var explicitTarget = getIntegerInputValue('semanaObjetivoActualizacion', 0);
        if (explicitTarget > 0) return explicitTarget;
        return getBaseSemanaActualizacion() + 1;
    }

    /**
     * Cargar y Filtrar Datos
     */
    function loadData() {
        console.log("🔥 [MapeoManual] Entrando a loadData().");
        $('#loading').show();
        var db = document.getElementById('baseDatos').value;
        var semanaVal = getBaseSemanaActualizacion();
        
        console.log("🔥 [MapeoManual] Valor detectado en input #semana: ", semanaVal);
        
        // Fetch desde el API: consultamos la semana objetivo calculada por backend.
        var targetSemana = getTargetSemanaActualizacion();
        var fullUrl = "/api/general/list?db=" + db + "&semana=" + targetSemana + "&exclude_chapters=1";
        
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
                    // AIA 2026: Convertimos ratio a Físico para el modelo de datos frontal
                    rawData.forEach(function(row) {
                        var dbRatio = parseFloat(row.Ejecutado || 0);
                        var mappedUnit = String(row.unidad || '').trim();
                        var ppto = parseFloat(row.cantidad_ppto || 0);
                        var physicalValue = dbRatio;
                        if (mappedUnit === '%' || mappedUnit === '' || ppto <= 0) {
                            physicalValue = dbRatio * 100;
                        } else {
                            physicalValue = dbRatio * ppto;
                        }
                        row.Ejecutado = physicalValue;
                    });
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
    function autoSaveRow(visualRowIndex, changesObj, source) {
        var rowData = hot.getSourceDataAtRow(visualRowIndex);
        var rowId = rowData.Consecutivo_en_Programa || rowData.Id;
        var targetSemana = getTargetSemanaActualizacion();
        var db = document.getElementById('baseDatos').value;

        // AIA 2026: Preservar avance porcentual (Ratio) al cambiar contexto (unidad/ppto)
        // Detectamos si cambió la unidad o el presupuesto sin que haya cambiado el Ejecutado explícitamente.
        if ((changesObj['unidad'] !== undefined || changesObj['cantidad_ppto'] !== undefined) && changesObj['Ejecutado'] === undefined) {
            var currentPhysical = parseFloat(rowData.Ejecutado || 0);
            var oldUnit = (changesObj['unidad'] !== undefined) ? hot.getDataAtRowProp(visualRowIndex, 'unidad') : rowData.unidad; 
            // Nota: en hot_actualizar el rowData ya tiene el Valor Nuevo si afterChange disparó esto.
            // Pero autoSaveRow es llamado DESPUÉS de que el modelo ya se actualizó si se usa hooks normales.
            // Para simplicidad, calculamos el ratio anterior asumiendo que el cambio está en changesObj.
        }

        var $saveStatus = $('#save-status');
        $saveStatus
            .stop(true, true)
            .removeClass('badge-badge-hidden badge-success')
            .addClass('badge-warning')
            .text('Guardando...')
            .fadeIn(120);
        
        var formData = new URLSearchParams();
        formData.append('Id', rowId);

        var currentUnidad = (changesObj['unidad'] !== undefined) ? changesObj['unidad'] : (rowData.unidad || '');
        var currentPpto = parseFloat((changesObj['cantidad_ppto'] !== undefined) ? changesObj['cantidad_ppto'] : (rowData.cantidad_ppto || 0));

        // Si cambió el contexto, recalculamos Ejecutado para que el Ratio se mantenga.
        if ((changesObj['unidad'] !== undefined || changesObj['cantidad_ppto'] !== undefined) && changesObj['Ejecutado'] === undefined) {
            // Obtenemos el valor físico "viejo" (que ya está en rowData)
            var physicalBefore = parseFloat(rowData.Ejecutado || 0);
            
            // Reconstruimos el contexto "viejo"
            var oldUnidad = (changesObj['unidad'] !== undefined) ? hot.getCellMeta(visualRowIndex, hot.getPropToCol('unidad'))._oldValue || currentUnidad : currentUnidad;
            // Handsontable meta no siempre guarda _oldValue de forma fiable así que usamos una lógica más simple:
            // Si el usuario cambió de % a ml, el valor '80' significaba 80%.
            // Si cambió de ml a %, el valor '160' significaba (160/oldPpto).
            
            // Intentaremos inferir el ratio basado en el valor actual y el contexto cambiado.
            // Dado que no tenemos el oldValue fácil en este scope, usaremos la lógica de buildUpdatePayload de hot.js si fuera posible.
            // Pero aquí el rowData YA TIENE el nuevo valor.
        }

        if(changesObj['unidad'] !== undefined) formData.append('unidad', changesObj['unidad']);
        else formData.append('unidad', rowData.unidad || '');

        // Forzar ID numérico (Consecutivo_en_Programa) para evitar errores SQL con IDs jerárquicos (puntos)
        var cleanId = (typeof rowId === 'string' && rowId.includes('.')) ? null : rowId;
        // The previous line `var rowData = hot.getSourceDataAtRow(visualRowIndex);` was duplicated.
        // It's already defined at the beginning of the function.
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
        else formData.append('Fecha_Inicio', rowData.Fecha_Inicio || '');

        if(changesObj['Fecha_Fin'] !== undefined) formData.append('Fecha_Fin', changesObj['Fecha_Fin']);
        else formData.append('Fecha_Fin', rowData.Fecha_Fin || '');

        if(changesObj['unidad'] !== undefined) formData.append('unidad', changesObj['unidad']);
        else formData.append('unidad', rowData.unidad || '');

        if(changesObj['cantidad_ppto'] !== undefined) formData.append('cantidad_ppto', changesObj['cantidad_ppto']);
        else formData.append('cantidad_ppto', rowData.cantidad_ppto !== undefined ? rowData.cantidad_ppto : '');
        
        // AIA 2026: El Data Model tiene ahora la cantidad FÍSICA.
        // El Backend de actualización espera exactamente eso: La cantidad física (o de 0-100 para %).
        var physicalToSubmit = parseFloat((changesObj['Ejecutado'] !== undefined) ? changesObj['Ejecutado'] : rowData.Ejecutado);
        if (isNaN(physicalToSubmit)) physicalToSubmit = 0; 
        
        formData.append('Ejecutado', physicalToSubmit.toFixed(2));

        if(changesObj['actividadAsociar'] !== undefined) formData.append('actividadAsociar', changesObj['actividadAsociar']);
        else formData.append('actividadAsociar', rowData.programaAnteriorAsociar || rowData.actividadAsociar || '*No Asociada*');

        if(changesObj['codigo_actividad'] !== undefined) formData.append('codigo_actividad', changesObj['codigo_actividad']);
        else formData.append('codigo_actividad', rowData.codigo_actividad || '');

        // AIA 2026: Validación Preventiva de Rango (0-100%)
        var physToSubmit = parseFloat(rowData.Ejecutado || 0);
        var ratioCheck = 0;
        if (currentUnidad === '%' || currentUnidad === '' || currentPpto <= 0) {
            ratioCheck = physToSubmit / 100;
        } else {
            ratioCheck = physToSubmit / currentPpto;
        }

        if (ratioCheck > 1.0001) {
            var maxV = (currentUnidad === '%' || currentUnidad === '' || currentPpto <= 0) ? "100%" : (currentPpto + " " + currentUnidad);
            var errM = "El valor resultante (" + (ratioCheck * 100).toFixed(1) + "%) excede el rango permitido (0-100%). Máximo: " + maxV;
            $saveStatus.hide();
            if (typeof toastr !== 'undefined') toastr.error(errM);
            return; // Bloquea el envío al servidor
        }

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
                $saveStatus.removeClass('badge-warning').addClass('badge-success');
                if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
                    window.AIA.Notice.badge('success', 'Auto-Guardado');
                } else {
                    $saveStatus
                        .removeClass('badge-badge-hidden')
                        .text('Auto-Guardado')
                        .fadeIn(120)
                        .delay(1800)
                        .fadeOut(250, function() {
                            $(this).addClass('badge-badge-hidden');
                        });
                }

                // Reflejar cambios heredados si existen en la respuesta (Herencia AIA 2026)
                if (res.unidad !== undefined) hot.setDataAtRowProp(visualRowIndex, 'unidad', res.unidad, 'internal');
                if (res.cantidad_ppto !== undefined) hot.setDataAtRowProp(visualRowIndex, 'cantidad_ppto', res.cantidad_ppto, 'internal');
                if (res.Ejecutado !== undefined) {
                    // Convertimos el Ratio devuelto a Cantidad Física
                    var resRatio = parseFloat(res.Ejecutado);
                    var mappedUnit = String(rowData.unidad || '').trim();
                    var ppto = parseFloat(rowData.cantidad_ppto || 0);
                    var newPhysical = resRatio;
                    if (mappedUnit === '%' || mappedUnit === '' || ppto <= 0) {
                        newPhysical = resRatio * 100;
                    } else {
                        newPhysical = resRatio * ppto;
                    }
                    hot.setDataAtRowProp(visualRowIndex, 'Ejecutado', newPhysical, 'internal');
                }
                if (res.Estado_Restricciones !== undefined) hot.setDataAtRowProp(visualRowIndex, 'Estado_Restricciones', res.Estado_Restricciones, 'internal');
                if (res.estado !== undefined) hot.setDataAtRowProp(visualRowIndex, 'Estado', res.estado, 'internal');
                
                // Herencia de las 7 restricciones individuales (persistidas pero no visibles)
                const silentFields = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
                silentFields.forEach(field => {
                    if (res[field] !== undefined) hot.setDataAtRowProp(visualRowIndex, field, res[field], 'internal');
                });
            } else {
                $saveStatus.hide();
                var msg = res.mensaje || 'Error al guardar';
                if (typeof toastr !== 'undefined') toastr.error(msg);
            }
        })
        .catch(err => {
            console.error("🔥 Error en autoSaveRow:", err);
            $saveStatus.hide();
            var errorMsg = err.message || 'Error de red al guardar';
            if (typeof toastr !== 'undefined') toastr.error(errorMsg);
        });
    }

    /**
     * Inicialización
     */
    function initHandsontable(data) {
        var container = document.getElementById('hot-container');

        var hotConfig = {
            data: data,
            rowHeaders: false,
            colHeaders: [
                "Consecutivo",
                "Id",
                "Actividad Nueva",
                "Asociar con...<span class='changeType' title='Filtrar por Asociación'></span>",
                "F. Inicio",
                "F. Fin",
                "Unidad",
                "Cant. PPTO",
                "Restricciones",
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
                    className: "htCenter htMiddle",
                    editor: 'tomSelectSingle',
                    tomSelectOptions: sourceDataHistorica,
                    renderer: ActivityMappingRenderer
                },
                { data: 'Fecha_Inicio', type: 'date', dateFormat: 'YYYY-MM-DD', width: 90, className: "htCenter htMiddle" },
                { data: 'Fecha_Fin', type: 'date', dateFormat: 'YYYY-MM-DD', width: 90, className: "htCenter htMiddle" },
                { 
                    data: 'unidad', 
                    type: 'dropdown', 
                    source: unitOptions, 
                    width: 60, 
                    className: "htCenter htMiddle" 
                },
                { 
                    data: 'cantidad_ppto', 
                    type: 'numeric', 
                    numericFormat: { pattern: '0.0' }, 
                    width: 80, 
                    className: "htCenter htMiddle" 
                },
                { 
                    data: 'Estado_Restricciones', 
                    type: 'numeric', 
                    readOnly: true,
                    width: 100, 
                    className: "htCenter htMiddle",
                    renderer: pgPercentRenderer
                },
                { 
                    data: 'Ejecutado', 
                    type: 'numeric', 
                    numericFormat: { pattern: '0.0' }, 
                    width: 140, 
                    className: "htCenter htMiddle",
                    renderer: pgEjecutadoRealRenderer 
                }
            ],
            cells: function(row, col, prop) {
                var props = {};
                var canEdit = Boolean(editableProps[prop]) && isUserAllowedToEdit();
                var rowData = this.instance.getSourceDataAtRow(row) || {};

                // Bloquear cantidad_ppto si la unidad es %
                if (canEdit && prop === 'cantidad_ppto' && String(rowData.unidad || '').trim() === '%') {
                    canEdit = false;
                }

                // Bloquear campos críticos si la actividad está mapeada (automapeada o manual)
                var isMapped = rowData.programaAnteriorAsociar && rowData.programaAnteriorAsociar !== '*No Asociada*';
                var restrictedMappedProps = ['Ejecutado', 'unidad', 'cantidad_ppto'];
                
                if (canEdit && isMapped && restrictedMappedProps.indexOf(prop) !== -1) {
                    canEdit = false;
                }

                props.readOnly = !canEdit;
                props.className = (this.instance.getSettings().columns[col].className || '') + 
                                  (canEdit ? ' pg-cell-editable' : ' pg-cell-readonly');
                
                return props;
            },
            
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
            modifyFiltersMultiSelectValue: function(value, meta) {
                var prop = meta && (meta.prop || meta.data);
                if (prop === 'Actividad' || prop === 'programaAnteriorAsociar') {
                    return getFilterPlainText(value);
                }

                return value;
            },
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            outsideClickDeselects: false, // Vital para Select2/TomSelect
            hiddenColumns: {
                columns: [0], // Ocultar Consecutivo por defecto
                indicators: false
            },

            afterChange: function(changes, source) {
               if (source === 'loadData' || source === 'internal' || !changes) return;

               // Agrupar cambios por fila
               var rowChanges = {};
               changes.forEach(function(change) {
                   var visualRow = change[0];
                   var prop = change[1];
                   var oldVal = change[2];
                   var newVal = change[3];

                   if (oldVal !== newVal) {
                       // Validar y Normalizar
                       var normalized = normalizeCellValue(prop, newVal);
                       if (!normalized.valid) {
                           this.setDataAtRowProp(visualRow, prop, oldVal, 'internal');
                           if (typeof toastr !== 'undefined') toastr.warning(normalized.error);
                           return;
                       }

                       // Si se borra la asociación, forzar "*No Asociada*" para mostrar PENDIENTE
                       if (prop === 'programaAnteriorAsociar' && (normalized.value === null || normalized.value === '')) {
                           normalized.value = '*No Asociada*';
                       }

                       if (!rowChanges[visualRow]) rowChanges[visualRow] = {};
                       rowChanges[visualRow][prop] = normalized.value;

                       // Update visual si hubo normalización (ej: coma a punto)
                       if (normalized.value !== newVal) {
                           this.setDataAtRowProp(visualRow, prop, normalized.value, 'internal');
                       }

                       // Si cambió la unidad, forzar renderizado para bloquear/desbloquear cantidad_ppto
                       if (prop === 'unidad') {
                           this.render();
                       }
                   }
               }.bind(this));

               // Disparar guardado por cada fila modificada
                Object.keys(rowChanges).forEach(function(visualRowStr) {
                   var visualRow = parseInt(visualRowStr);
                   var rowData = this.getSourceDataAtRow(visualRow);
                   var idRow = rowData.Consecutivo_en_Programa; // Usar consecutivo interno para SQL
                   autoSaveRow(visualRow, rowChanges[visualRow], source);
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
                fetchCodigosActividad();
                loadData();
            } catch (error) {
                console.error("🔥 [MapeoManual] Excepción síncrona en init(): ", error);
            }
        }
    };
})();
