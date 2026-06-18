/**
 * Módulo de Inicialización de Handsontable para Actualización de Cronograma (Mapeo Manual)
 * Arquitectura CSS 2026 - LPS
 */

window.HOTActualizarModule = (function() {
    var hot = null;
    var rawData = [];
    var showingUnmappedOnly = true;
    var layoutTimer = null;
    var _canEditGlobal = false;
    var _rowMetaCache = {};
    var _pendingChanges = {};
    var _saveTimer = null;
    var _lastAppliedContainerHeight = 0;
    var _colWidthCache = null;
    var _colContainerWidth = 0;
    var _cachedColumns = null;
    var _cachedSourceData = null;
    var _initDone = false;
    var _loadDataFetched = false;

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
        var permiso = String($('#permiso_canonico').val() || 'V').trim().toUpperCase();
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

    function getColumnIndexByProp(prop) {
        if (!hot) return -1;

        if (typeof hot.propToCol === 'function') {
            var mappedCol = hot.propToCol(prop);
            if (typeof mappedCol === 'number' && mappedCol >= 0) return mappedCol;
        }

        var settings = typeof hot.getSettings === 'function' ? hot.getSettings() : {};
        var columns = Array.isArray(settings.columns) ? settings.columns : [];
        for (var i = 0; i < columns.length; i++) {
            if (columns[i] && columns[i].data === prop) return i;
        }

        return -1;
    }

    function getViewportHeight() {
        if (window.visualViewport && Number.isFinite(window.visualViewport.height) && window.visualViewport.height > 0) {
            return Math.floor(window.visualViewport.height);
        }

        var docHeight = document.documentElement && document.documentElement.clientHeight;
        var winHeight = window.innerHeight;
        var height = Number.isFinite(winHeight) && winHeight > 0 ? winHeight : docHeight;
        return Number.isFinite(height) && height > 0 ? Math.floor(height) : 0;
    }

    function syncContainerHeight() {
        var container = document.getElementById('hot-container');
        if (!container || !container.getBoundingClientRect) return 0;

        var viewportHeight = getViewportHeight();
        if (!viewportHeight) return 0;

        var rect = container.getBoundingClientRect();
        var top = Math.max(0, Math.floor(rect.top || 0));
        var resolved = Math.max(260, Math.floor(viewportHeight - top - 2));

        container.style.height = resolved + 'px';
        return resolved;
    }

    function refreshHotLayout(delay) {
        clearTimeout(layoutTimer);
        layoutTimer = setTimeout(function() {
            if (!hot) return;

            var containerHeight = syncContainerHeight();
            if (containerHeight > 0) {
                var newHeight = Math.max(220, containerHeight - 2);
                if (newHeight !== _lastAppliedContainerHeight) {
                    _lastAppliedContainerHeight = newHeight;
                    hot.updateSettings({ height: newHeight });
                }
            }

            // refreshDimensions recalculates the visible area after settings changes.
            // updateSettings already triggers a render, so no explicit hot.render() needed.
            if (typeof hot.refreshDimensions === 'function') {
                hot.refreshDimensions();
            }
        }, Number.isFinite(delay) ? delay : 0);
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
        var physicalRow = typeof instance.toPhysicalRow === 'function' ? instance.toPhysicalRow(row) : row;
        var sourceData = _cachedSourceData || (typeof instance.getSourceData === 'function' ? instance.getSourceData() : null);
        var rowData = (Array.isArray(sourceData) && physicalRow !== null && physicalRow >= 0 && physicalRow < sourceData.length) ? (sourceData[physicalRow] || {}) : {};
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
        td.textContent = (val * 100).toFixed(1) + '%';
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
                    var codes = response.data.map(c => c.codigo_actividad || c.codigo || '').filter(Boolean);
                    var colIndex = getColumnIndexByProp('codigo_actividad');
                    if (colIndex !== -1) {
                        var settings = hot.getSettings();
                        settings.columns[colIndex].source = codes;
                        hot.updateSettings({ columns: settings.columns });
                        refreshHotLayout(0);
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
        if (_loadDataFetched) {
            console.log("🔥 [MapeoManual] loadData() ya ejecutado. Saltando fetch duplicado.");
            return;
        }
        _loadDataFetched = true;
        console.log("🔥 [MapeoManual] Entrando a loadData().");
        $('#loading').show();
        var db = document.getElementById('baseDatos').value;
        var semanaVal = getBaseSemanaActualizacion();
        
        console.log("🔥 [MapeoManual] Valor detectado en input #semana: ", semanaVal);
        
        // Fetch desde el API: consultamos la semana objetivo calculada por backend.
        var targetSemana = getTargetSemanaActualizacion();
        var fullUrl = "/api/general/list?db=" + db + "&semana_objetivo=" + targetSemana + "&exclude_chapters=1";
        
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

                // Auto-associate trigger after XLSX upload
                if (sessionStorage.getItem('autoAssociatePending') === '1') {
                    sessionStorage.removeItem('autoAssociatePending');
                    setTimeout(runAutoAssociate, 1500);
                }
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
                    refreshHotLayout(0);
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
            _cachedSourceData = hot.getSourceData();
            _rowMetaCache = {};
            refreshHotLayout(0);
        } else {
            initHandsontable(filteredData);
            refreshHotLayout(0);
            console.log("🔥 [MapeoManual] Handsontable inicializado.");
        }
    }

    /**
     * Flush de cambios pendientes (debounce). Agrupa cambios por fila y dispara autoSaveRow.
     */
    function flushPendingChanges() {
        var keys = Object.keys(_pendingChanges);
        if (keys.length === 0) return;

        var changesToSend = _pendingChanges;
        _pendingChanges = {};
        _saveTimer = null;

        // Ocultar badge de guardando
        var $saveStatus = $('#save-status');
        $saveStatus.stop(true, true).addClass('badge-badge-hidden').removeClass('badge-warning badge-success').fadeOut(120);

        keys.forEach(function(visualRowStr) {
            var visualRow = parseInt(visualRowStr);
            var group = changesToSend[visualRowStr];
            delete _rowMetaCache[hot.toPhysicalRow(visualRow)];
            autoSaveRow(visualRow, group, 'debounced');
        });
    }

    /**
     * Guardado al Vuelo (AJAX Update)
     */
    function autoSaveRow(visualRowIndex, changesObj, source) {
        var physicalRow = typeof hot.toPhysicalRow === 'function' ? hot.toPhysicalRow(visualRowIndex) : visualRowIndex;
        var sourceData = typeof hot.getSourceData === 'function' ? hot.getSourceData() : null;
        var rowData = (Array.isArray(sourceData) && physicalRow !== null && physicalRow >= 0 && physicalRow < sourceData.length) ? (sourceData[physicalRow] || {}) : {};
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
            var unidadCol = getColumnIndexByProp('unidad');
            var oldUnidad = (changesObj['unidad'] !== undefined && unidadCol >= 0) ? hot.getCellMeta(visualRowIndex, unidadCol)._oldValue || currentUnidad : currentUnidad;
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

        fetch("/api/general/update?db=" + db + "&semana_objetivo=" + targetSemana, {
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
                var editorOpen = hot.getActiveEditor && hot.getActiveEditor() && hot.getActiveEditor().isOpened && hot.getActiveEditor().isOpened();
                if (editorOpen) { return; }

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
        var initialHeight = syncContainerHeight() || '100%';

        function getRowMeta(physicalRow, rowData) {
            if (Number.isInteger(physicalRow) && physicalRow >= 0 && _rowMetaCache[physicalRow]) {
                return _rowMetaCache[physicalRow];
            }

            var isMapped = rowData.programaAnteriorAsociar && rowData.programaAnteriorAsociar !== '*No Asociada*';
            var meta = { isMapped: Boolean(isMapped) };

            if (Number.isInteger(physicalRow) && physicalRow >= 0) {
                _rowMetaCache[physicalRow] = meta;
            }

            return meta;
        }

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
                { data: 'Consecutivo_en_Programa', type: 'numeric', readOnly: true },
                { data: 'Id', type: 'text', readOnly: true },
                { data: 'Actividad', type: 'text', readOnly: true, renderer: ReadOnlyRenderer },
                { 
                    data: 'programaAnteriorAsociar', 
                    type: 'text',
                    className: "htCenter htMiddle",
                    editor: 'tomSelectSingle',
                    tomSelectOptions: sourceDataHistorica,
                    renderer: ActivityMappingRenderer
                },
                { data: 'Fecha_Inicio', type: 'date', dateFormat: 'YYYY-MM-DD', className: "htCenter htMiddle" },
                { data: 'Fecha_Fin', type: 'date', dateFormat: 'YYYY-MM-DD', className: "htCenter htMiddle" },
                { 
                    data: 'unidad', 
                    type: 'dropdown', 
                    source: unitOptions, 
                    className: "htCenter htMiddle" 
                },
                { 
                    data: 'cantidad_ppto', 
                    type: 'numeric', 
                    numericFormat: { pattern: '0.0' }, 
                    className: "htCenter htMiddle" 
                },
                { 
                    data: 'Estado_Restricciones', 
                    type: 'numeric', 
                    readOnly: true,
                    className: "htCenter htMiddle",
                    renderer: pgPercentRenderer
                },
                { 
                    data: 'Ejecutado', 
                    type: 'numeric', 
                    numericFormat: { pattern: '0.0' }, 
                    className: "htCenter htMiddle",
                    renderer: pgEjecutadoRealRenderer 
                }
            ],
            cells: function(row, col, prop) {
                // AIA 2026: "Asociar con..." SIEMPRE es editable — el mapeo de actividades
                // es la función principal de esta página y no depende del estado de confirmación semanal.
                var canEdit = (prop === 'programaAnteriorAsociar') || (Boolean(editableProps[prop]) && _canEditGlobal);
                var physicalRow = this.instance.toPhysicalRow(row);

                var sourceData = _cachedSourceData;
                if (!sourceData) {
                    sourceData = typeof this.instance.getSourceData === 'function' ? this.instance.getSourceData() : null;
                }
                var rowData = (Array.isArray(sourceData) && physicalRow !== null && physicalRow >= 0 && physicalRow < sourceData.length) ? (sourceData[physicalRow] || {}) : {};

                if (canEdit && prop === 'cantidad_ppto' && String(rowData.unidad || '').trim() === '%') {
                    canEdit = false;
                }

                var meta = getRowMeta(physicalRow, rowData);
                if (canEdit && meta.isMapped && (prop === 'Ejecutado' || prop === 'unidad' || prop === 'cantidad_ppto')) {
                    canEdit = false;
                }

                var columns = _cachedColumns || (this.instance.getSettings().columns || []);
                var columnMeta = columns[col] || {};
                return {
                    readOnly: !canEdit,
                    className: (columnMeta.className || '') + (canEdit ? ' pg-cell-editable' : ' pg-cell-readonly'),
                };
            },
            
            // Características UX AIA 2026
            stretchH: 'none',
            autoWrapRow: false,
            autoWrapCol: false,
            autoRowSize: false,
            autoColumnSize: false,
            rowHeights: 28,
            renderAllRows: false,
            viewportRowRenderingOffset: 20,
            viewportColumnRenderingOffset: 10,
            language: 'es-MX',
            colHeaderHeight: 48,
            width: '100%',
            height: initialHeight,
            colWidths: function(index) {
                var container = document.getElementById('hot-container');
                var baseWidth = container ? container.clientWidth : window.innerWidth;
                var cw = baseWidth - 20;

                if (_colWidthCache === null || _colContainerWidth !== cw) {
                    _colContainerWidth = cw;
                    // Original ratios: [0.031, 0.031, 0.271, 0.233, 0.070, 0.070, 0.047, 0.062, 0.078, 0.109] → sum ~1.002
                    // Normalized to sum exactly 1.0 to eliminate right-side gap
                    var raw = [0.031, 0.031, 0.271, 0.233, 0.070, 0.070, 0.047, 0.062, 0.078, 0.109];
                    var sum = raw.reduce(function(a, b) { return a + b; }, 0);
                    _colWidthCache = raw.map(function(r) { return Math.max(Math.floor(cw * r / sum), 20); });
                }
                return _colWidthCache[index];
            },
            licenseKey: 'non-commercial-and-evaluation',
            wordWrap: false,
            manualColumnResize: false,
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
                columns: [0],
                indicators: false
            },

            afterOnCellMouseDown: function(event, coords) {
                if (!coords || coords.row < 0 || coords.col < 0) return;
                if (!event || event.button !== 0 || event.shiftKey || event.ctrlKey || event.metaKey || event.altKey) return;

                var prop = this.colToProp(coords.col);
                if (prop !== 'programaAnteriorAsociar') return;

                var instance = this;
                var currentValue = instance.getDataAtRowProp(coords.row, prop);

                instance.selectCell(coords.row, coords.col, coords.row, coords.col, false, false);

                setTimeout(function() {
                    if (!instance) return;
                    var editor = instance.getActiveEditor ? instance.getActiveEditor() : null;
                    if (!editor) { return; }
                    try {
                        if (typeof editor.enableFullEditMode === 'function') editor.enableFullEditMode();
                        editor.beginEditing(currentValue, event);
                        if (typeof editor.open === 'function' && (!editor.isOpened || !editor.isOpened())) {
                            editor.open(event);
                        }
                    } catch(_err) {}
                }, 0);
            },

            afterChange: function(changes, source) {
               if (source === 'loadData' || source === 'internal' || !changes) return;

               // Agrupar cambios por fila
               var rowChanges = {};
               changes.forEach(function(change) {
                   if (!change) return;
                   var physicalRow = change[0];
                   var prop = change[1];
                   var oldVal = change[2];
                   var newVal = change[3];

                   var visualRow = this.toVisualRow(physicalRow);
                   if (visualRow === null || visualRow < 0) {
                       return;
                   }

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

                       if (!rowChanges[physicalRow]) {
                           rowChanges[physicalRow] = {
                               visualRow: visualRow,
                               changes: {}
                           };
                       }
                       rowChanges[physicalRow].changes[prop] = normalized.value;

                       // Update visual si hubo normalización (ej: coma a punto)
                       if (normalized.value !== newVal) {
                           this.setDataAtRowProp(visualRow, prop, normalized.value, 'internal');
                       }

                   }
               }.bind(this));

                // Cola de cambios con debounce (800ms)
                Object.keys(rowChanges).forEach(function(physicalRowStr) {
                    var physicalRow = parseInt(physicalRowStr);
                    var group = rowChanges[physicalRow];
                    delete _rowMetaCache[physicalRow];
                    _pendingChanges[group.visualRow] = Object.assign(_pendingChanges[group.visualRow] || {}, group.changes);
                });

                clearTimeout(_saveTimer);
                _saveTimer = setTimeout(flushPendingChanges, 800);

                var pendingCount = Object.keys(_pendingChanges).length;
                var $saveStatus = $('#save-status');
                $saveStatus
                    .stop(true, true)
                    .removeClass('badge-badge-hidden badge-success')
                    .addClass('badge-warning')
                    .text('Guardando... (' + pendingCount + ')')
                    .fadeIn(120);

            }
        };

        _canEditGlobal = isUserAllowedToEdit();
        hot = new Handsontable(container, hotConfig);
        _cachedColumns = hot.getSettings().columns || [];
        _cachedSourceData = hot.getSourceData();
        refreshHotLayout(0);

        // Bind Custom Togglers
        $("#btn_toggleFiltroMapeo").on("click", function() {
            showingUnmappedOnly = !showingUnmappedOnly;
            applyFilterAndRender();
        });

        $("#btn_autoAsociar").on("click", runAutoAssociate);

        $(window)
            .off('resize.hotActualizar orientationchange.hotActualizar')
            .on('resize.hotActualizar orientationchange.hotActualizar', function() {
                _colWidthCache = null;
                refreshHotLayout(80);
            });

        // Wave 3 Task 7: beforeunload handler — flush pending changes on navigation
        $(window).on('beforeunload.hotActualizar', function() {
            if (Object.keys(_pendingChanges).length > 0) {
                if (_saveTimer) {
                    clearTimeout(_saveTimer);
                    _saveTimer = null;
                }
                flushPendingChanges();
            }
        });
    }

    /**
     * Ejecuta la asociación automática de actividades vía API.
     * Lee baseDatos y semanaObjetivoActualizacion de hidden inputs.
     */
    function runAutoAssociate() {
        var btn = $("#btn_autoAsociar");
        var db = $("#baseDatos").val();
        var semanaObjetivo = $("#semanaObjetivoActualizacion").val();

        if (!db || !semanaObjetivo) {
            toastr.error("Faltan datos de contexto (base de datos o semana objetivo).");
            return;
        }

        var originalHtml = btn.html();
        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        fetch("/api/general/auto-associate", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                db: db,
                semana_objetivo: semanaObjetivo
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            console.log("🔥 [AutoAssociate] Results:", data);
            btn.prop("disabled", false).html(originalHtml);
            if (data.success && data.data) {
                applyMatchResults(data.data);
            } else {
                toastr.error(data.error || "Error en la asociación automática");
            }
        })
        .catch(function(error) {
            console.error("🔥 [AutoAssociate] Error:", error);
            btn.prop("disabled", false).html(originalHtml);
            toastr.error("Error de conexión al asociar actividades");
        });
    }

    /**
     * Popula las 4 tarjetas estadísticas del modal de revisión.
     * @param {Object} data - data identical/high/medium/none arrays.
     */
    function populateModalStats(data) {
        var identicalCount = typeof data.identical === 'number' ? data.identical : (data.identical || []).length;
        var highCount = typeof data.high === 'number' ? data.high : (data.high || []).length;
        var mediumCount = typeof data.medium === 'number' ? data.medium : (data.medium || []).length;
        var noneCount = typeof data.none === 'number' ? data.none : (data.none || []).length;

        $('#stat_identical').text(identicalCount);
        $('#stat_high').text(highCount);
        $('#stat_medium').text(mediumCount);
        $('#stat_none').text(noneCount);
    }

    /**
     * Determina la clase CSS de confianza para una barra.
     * @param {number} confidence - Score 0..1.
     * @returns {string} 'match-confidence-high' | 'match-confidence-medium' | 'match-confidence-none'
     */
    function confidenceTierClass(confidence) {
        if (confidence >= 0.8) return 'match-confidence-high';
        if (confidence >= 0.5) return 'match-confidence-medium';
        return 'match-confidence-none';
    }

    /**
     * Renderiza el HTML para un ítem de revisión (actividad con confianza media).
     * Muestra nombre de actividad + top-3 candidatos con barra de confianza.
     * @param {Object} item - { activityName, candidates, row, rowId }
     * @param {number} index - Índice del ítem en la lista (para IDs únicos).
     * @returns {string} HTML string.
     */
    function renderReviewItem(item, index) {
        var candidates = item.candidates || [];
        var topCandidates = candidates.slice(0, 3);
        var targetName = item.activityName || 'Actividad sin nombre';

        var candidatesHtml = topCandidates.map(function(c, ci) {
            var pct = (c.confidence * 100).toFixed(0);
            var tierClass = confidenceTierClass(c.confidence);
            var candidateName = c.name || c.actividad || c.title || 'Sin nombre';
            var displayConf = c.display_confidence !== undefined ? c.display_confidence : pct;

            return '' +
                '<div class="match-candidate ' + tierClass + '" data-row="' + (item.row !== undefined ? item.row : '') + '" data-candidate="' + ci + '">' +
                    '<div class="match-candidate-info">' +
                        '<div class="match-candidate-name">' + escapeHtml(candidateName) + '</div>' +
                        '<div class="match-candidate-bar-wrap">' +
                            '<div class="match-candidate-bar">' +
                                '<div class="match-candidate-bar-fill" style="width: ' + pct + '%;"></div>' +
                            '</div>' +
                            '<span class="match-candidate-pct">' + displayConf + '%</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="match-candidate-actions">' +
                        '<button type="button" class="btn btn-sm btn-success js-accept-match" ' +
                            'data-row="' + (item.row !== undefined ? item.row : '') + '" ' +
                            'data-candidate-name="' + escapeAttr(candidateName) + '" ' +
                            'data-index="' + index + '" ' +
                            'title="Asociar con este candidato">' +
                            '<i class="fas fa-check"></i> Aceptar' +
                        '</button>' +
                    '</div>' +
                '</div>';
        }).join('');

        var extraHtml = '';
        if (candidates.length > 3) {
            var extraCandidates = candidates.slice(3);
            var extraHtmlItems = extraCandidates.map(function(c, ci) {
                var pct = (c.confidence * 100).toFixed(0);
                var tierClass = confidenceTierClass(c.confidence);
                var candidateName = c.name || c.actividad || c.title || 'Sin nombre';
                var displayConf = c.display_confidence !== undefined ? c.display_confidence : pct;

                return '' +
                    '<div class="match-candidate ' + tierClass + ' js-extra-candidate" data-row="' + (item.row !== undefined ? item.row : '') + '" data-candidate="' + (ci + 3) + '" style="display:none;">' +
                        '<div class="match-candidate-info">' +
                            '<div class="match-candidate-name">' + escapeHtml(candidateName) + '</div>' +
                            '<div class="match-candidate-bar-wrap">' +
                                '<div class="match-candidate-bar">' +
                                    '<div class="match-candidate-bar-fill" style="width: ' + pct + '%;"></div>' +
                                '</div>' +
                                '<span class="match-candidate-pct">' + displayConf + '%</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="match-candidate-actions">' +
                            '<button type="button" class="btn btn-sm btn-success js-accept-match" ' +
                                'data-row="' + (item.row !== undefined ? item.row : '') + '" ' +
                                'data-candidate-name="' + escapeAttr(candidateName) + '" ' +
                                'data-index="' + index + '" ' +
                                'title="Asociar con este candidato">' +
                                '<i class="fas fa-check"></i> Aceptar' +
                            '</button>' +
                        '</div>' +
                    '</div>';
            }).join('');

            extraHtml = '' +
                '<div class="text-center mt-2">' +
                    '<button type="button" class="btn btn-sm btn-link js-toggle-extra" data-index="' + index + '">' +
                        'Ver más opciones (' + candidates.length + ' total)' +
                    '</button>' +
                '</div>' +
                '<div class="js-extra-options" data-index="' + index + '" style="display:none;">' +
                    extraHtmlItems +
                '</div>';
        }

        return '' +
            '<div class="match-item" data-index="' + index + '" data-row="' + (item.row !== undefined ? item.row : '') + '">' +
                '<div class="match-item-header">' +
                    '<div class="match-target-label">' +
                        '<span class="match-label match-label-target"><i class="fas fa-crosshairs mr-1"></i> Actividad a asociar</span>' +
                    '</div>' +
                    '<div class="match-activity-name">' +
                        escapeHtml(targetName) +
                    '</div>' +
                '</div>' +
                '<div class="match-item-body">' +
                    '<div class="match-source-label">' +
                        '<span class="match-label match-label-source"><i class="fas fa-database mr-1"></i> Candidatos de la semana anterior</span>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger js-skip-match" ' +
                            'data-row="' + (item.row !== undefined ? item.row : '') + '" ' +
                            'data-index="' + index + '" ' +
                            'title="Marcar como actividad nueva (sin asociar)">' +
                            '<i class="fas fa-times"></i> Sin coincidencia' +
                        '</button>' +
                    '</div>' +
                    '<div class="match-candidates-list">' +
                        candidatesHtml +
                        extraHtml +
                    '</div>' +
                '</div>' +
                '<div class="match-item-status" data-index="' + index + '" style="display:none;"></div>' +
            '</div>';
    }

    /**
     * Escapa HTML básico para prevenir XSS en texto de candidatos.
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Escapa atributos HTML.
     * @param {string} str
     * @returns {string}
     */
    function escapeAttr(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /**
     * Muestra el modal de revisión de auto-asociación.
     * Puebla stats, renderiza ítems de media confianza, y bindinea eventos de accept/skip/expand.
     * @param {Object} results - { identical:[], high:[], medium:[], none:[] }
     */
    function showReviewModal(results) {
        var data = results.data || results;

        populateModalStats(data);

        var mediumItems = data.medium || [];
        var $reviewList = $('#review-list');
        $reviewList.empty();

        if (mediumItems.length === 0) {
            $reviewList.html(
                '<div class="text-center py-4" style="color: var(--aia-text-secondary, #4a4a4d);">' +
                    '<i class="fas fa-check-circle fa-2x mb-2" style="color: var(--aia-green-primary, #1a5633);"></i>' +
                    '<p>No hay actividades con confianza media para revisar.</p>' +
                '</div>'
            );
        } else {
            var html = mediumItems.map(function(item, idx) {
                return renderReviewItem(item, idx);
            }).join('');
            $reviewList.html(html);
        }

        _bindReviewModalEvents($reviewList);

        $('#modalAutoAsociar').modal('show');
    }

    /**
     * Bindea eventos delegados en el contenedor de revisión:
     * - Aceptar: asocia la actividad con el candidato seleccionado
     * - Marcar Nueva: omite la actividad
     * - Ver más opciones: expande candidatos extra
     * @param {jQuery} $container - El contenedor #review-list
     */
    function _bindReviewModalEvents($container) {
        $container.off('.reviewModal');

        $container.on('click.reviewModal', '.js-accept-match', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var physicalRow = parseInt($btn.data('row'), 10);
            var candidateName = $btn.data('candidate-name');
            var itemIndex = $btn.data('index');

            if (isNaN(physicalRow) || !candidateName) return;

            if (hot) {
                var visualRow = hot.toVisualRow(physicalRow);
                if (visualRow !== null && visualRow >= 0) {
                    hot.setDataAtRowProp(visualRow, 'programaAnteriorAsociar', candidateName, 'edit');
                }
            }

            var $item = $btn.closest('.match-item');
            $item.addClass('match-item-resolved match-item-accepted');
            $item.find('.js-accept-match, .js-skip-match').prop('disabled', true);

            var $status = $item.find('.match-item-status');
            $status.html(
                '<div class="match-resolved-badge match-resolved-accepted">' +
                    '<i class="fas fa-check-circle"></i> ' +
                    '<span>Asociada con: <strong>' + escapeHtml(candidateName) + '</strong></span>' +
                '</div>'
            ).show();

            $item.find('.match-candidate').css('opacity', '0.4');
            $btn.closest('.match-candidate').css('opacity', '1');

            if (typeof toastr !== 'undefined') {
                toastr.success('Asociado: ' + candidateName);
            }
        });

        $container.on('click.reviewModal', '.js-skip-match', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var physicalRow = parseInt($btn.data('row'), 10);
            var itemIndex = $btn.data('index');

            if (isNaN(physicalRow)) return;

            if (hot) {
                var visualRow = hot.toVisualRow(physicalRow);
                if (visualRow !== null && visualRow >= 0) {
                    hot.setDataAtRowProp(visualRow, 'programaAnteriorAsociar', '*No Asociada*', 'edit');
                }
            }

            var $item = $btn.closest('.match-item');
            $item.addClass('match-item-resolved match-item-skipped');
            $item.find('.js-accept-match, .js-skip-match').prop('disabled', true);

            var $status = $item.find('.match-item-status');
            $status.html(
                '<div class="match-resolved-badge match-resolved-skipped">' +
                    '<i class="fas fa-times-circle"></i> ' +
                    '<span>Marcada como actividad nueva</span>' +
                '</div>'
            ).show();

            if (typeof toastr !== 'undefined') {
                toastr.info('Actividad marcada como nueva');
            }
        });

        $container.on('click.reviewModal', '.js-toggle-extra', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var idx = $btn.data('index');
            var $extras = $container.find('.js-extra-options[data-index="' + idx + '"]');
            var isVisible = $extras.is(':visible');

            if (isVisible) {
                $extras.slideUp(200);
                $btn.text($btn.text().replace('Ocultar', 'Ver más'));
            } else {
                $extras.slideDown(200);
                $btn.text($btn.text().replace('Ver más', 'Ocultar'));
            }
        });
    }

    /**
     * Aplica clases CSS de confianza a las celdas de la grilla según los resultados de asociación.
     * - pg-match-auto: identical + high confidence
     * - pg-match-review: medium confidence
     * - pg-match-new: no match (none)
     * @param {Object} results - Resultados con data.identical, data.high, data.medium, data.none.
     */
    function applyGridHighlighting(results) {
        if (!hot) return;

        var data = results.data || results;
        var colIndex = hot.propToCol('programaAnteriorAsociar');
        if (typeof colIndex !== 'number' || colIndex < 0) return;

        var sourceData = hot.getSourceData();

        // Medium items have row indices from the API
        var mediumRows = {};
        (data.medium || []).forEach(function(item) {
            if (item.row !== undefined && item.row >= 0) {
                mediumRows[item.row] = true;
            }
        });

        // Highlight based on actual grid state after auto-associate
        for (var i = 0; i < sourceData.length; i++) {
            var row = sourceData[i];
            var val = row.programaAnteriorAsociar;
            var className;

            if (mediumRows[i]) {
                className = 'pg-match-review';
            } else if (val && val !== '*No Asociada*' && val !== '') {
                className = 'pg-match-auto';
            } else {
                className = 'pg-match-new';
            }

            var visualRow = hot.toVisualRow(i);
            if (visualRow !== null && visualRow >= 0) {
                hot.setCellMeta(visualRow, colIndex, 'className', className);
            }
        }

        hot.render();
    }

    /**
     * Aplica resultados de asociación en la UI: resalta grilla + abre modal si hay media confianza.
     * @param {Object} results - Resultados de la asociación automática.
     */
    function applyMatchResults(results) {
        console.log("🔥 [AutoAssociate] applyMatchResults called:", results);

        if (!results || (!results.data && !results.identical && !results.high)) {
            console.warn("🔥 [AutoAssociate] No results to display.");
            return;
        }

        var data = results.data || results;
        var mediumItems = data.medium || [];

        // Reload grid data so highlighting reflects the DB state after auto-associate
        _loadDataFetched = false;
        loadData();

        // Apply highlighting after data reloads
        setTimeout(function() {
            applyGridHighlighting(results);

            if (mediumItems.length > 0) {
                showReviewModal(results);
            } else {
                var identicalCount = typeof data.identical === 'number' ? data.identical : (data.identical || []).length;
                var highCount = typeof data.high === 'number' ? data.high : (data.high || []).length;
                var noneCount = typeof data.none === 'number' ? data.none : (data.none || []).length;
                var autoCount = identicalCount + highCount;

                if (typeof toastr !== 'undefined') {
                    toastr.success(
                        autoCount + ' asociadas automáticamente, ' +
                        noneCount + ' nuevas detectadas'
                    );
                }
            }
        }, 2000);
    }

    return {
        get hot() { return hot; },
        runAutoAssociate: runAutoAssociate,
        applyMatchResults: applyMatchResults,
        applyGridHighlighting: applyGridHighlighting,
        showReviewModal: showReviewModal,
        populateModalStats: populateModalStats,
        renderReviewItem: renderReviewItem,
        init: function() {
            console.log("🔥 [MapeoManual] HOTActualizarModule.init() alcanzado.");
            if (_initDone) {
                console.log("🔥 [MapeoManual] init() ya fue ejecutado. Saltando.");
                return;
            }
            _initDone = true;
            window.HOTActualizarModule._initialized = true;
            try {
                fetchCodigosActividad();
                loadData();
            } catch (error) {
                console.error("🔥 [MapeoManual] Excepción síncrona en init(): ", error);
            }
        }
    };
})();
