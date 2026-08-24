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
    var _reviewDecisions = null;      // { consecutivo: { action: 'accept'|'skip', candidateName: '...' } }
    var _reviewResultsRef = null;     // raw results data ref for re-rendering
    var _hasShownSavePrompt = false;  // guard for double-prompt
    var _saveStatus = null;
    import('/js/design-system/save-status.js').then(function (mod) {
        _saveStatus = mod.crearSaveStatus({ etiquetaGuardado: 'Auto-Guardado' });
    });
    import('/js/design-system/modal-escape.js').then(function (mod) {
        mod.activarEscapeEnModales();
    });

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

    function getSilentFields() {
        var config = window.__RESTRICTION_CONFIG__;
        if (config && config.restrictions) {
            return config.restrictions.map(function(r) { return r.key; });
        }
        return ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
    }

    function getCsrfToken() {
        if (typeof window.getCsrfToken === 'function') {
            return window.getCsrfToken();
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta && meta.content ? meta.content : '';
    }

    /**
     * Sesión caducada: en vez de dejar que el fetch reciba el HTML del login como si
     * fueran datos, pedimos JSON explícitamente y delegamos el 401 al único sitio que
     * decide qué hacer con `sessionExpired`: `AIA.SessionExpiredHandler`
     * (public/js/core/SessionExpiredHandler.js).
     */
    function sessionExpiredFetchHeaders() {
        if (window.AIA && window.AIA.SessionExpiredHandler) {
            return window.AIA.SessionExpiredHandler.fetchHeaders();
        }
        return { 'X-AIA-Expect-Json': '1' };
    }

    function handleSessionExpiredResponse(res) {
        if (!window.AIA || !window.AIA.SessionExpiredHandler) {
            return Promise.resolve(false);
        }
        return window.AIA.SessionExpiredHandler.handleFetchResponse(res);
    }

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

        // Semana confirmada → readOnly total: ningún rol puede editar.
        if (semanalConfirmada === 1) return false;

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

            // Sin esto la tabla se queda al ancho de su contenido —361 px de los
            // 989 del wtHider, medido a 1180x820— porque handsontable-module.css
            // fuerza `table-layout: auto !important` desde layer(vendor). Ver
            // public/js/modules/aia_ui/hot_table_width.js.
            if (window.AIA && window.AIA.sincronizarAnchoTabla) {
                window.AIA.sincronizarAnchoTabla(document.getElementById('hot-container'));
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
            chipHtml = `<div class="aia-chip aia-chip--success" style="white-space: normal; word-break: break-word;">${displayValue}</div>`;
        } else if (value === '*No Asociada*') {
            chipHtml = `<div class="aia-chip aia-chip--critical" style="font-weight: 700;"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> PENDIENTE</div>`;
        }
        td.innerHTML = chipHtml;
        td.className = "htMiddle htCenter force-wrap pg-cell-editable";
        // El estado "sin asociar" se señala a nivel de fila (pg-row-unmapped, hook
        // afterRenderer): un fondo por celda dejaba columnas de colores distintos.
        td.style.backgroundColor = '';
    }

    /**
     * Renderizador estático para Columnas de Lectura (Actividad Nueva)
     */
    function ReadOnlyRenderer(instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.HtmlRenderer.apply(this, arguments);
        td.className = "htMiddle force-wrap pg-cell-readonly";
        // El tinte de fila sin asociar lo pone pg-row-unmapped (hook afterRenderer).
        td.style.backgroundColor = '';
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

        // Task 34 (ola de arreglo): es una cantidad con su porcentaje entre
        // parentesis, la gemela de `EjecutadoDisplay` en Programa General, que ya
        // va a la derecha. El renderer pisa el className de la columna, asi que la
        // alineacion tiene que cambiarse tambien aqui o no se mueve un pixel.
        td.className = (td.className || '') + ' htRight htMiddle';
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
        fetch("/api/general/codigos?db=" + db, { headers: sessionExpiredFetchHeaders() })
            .then(res => handleSessionExpiredResponse(res).then(handled => {
                if (handled) { throw new Error('session-expired'); }
                return res.json();
            }))
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
        fetch(fullUrl, { headers: sessionExpiredFetchHeaders() })
            .then(res => handleSessionExpiredResponse(res).then(handled => {
                if (handled) { throw new Error('session-expired'); }
                console.log("🔥 [MapeoManual] Status: ", res.status, "| targetSemana requested: ", targetSemana);
                if (!res.ok) throw new Error("HTTP error " + res.status);
                return res.json();
            }))
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
            $("#btn_toggleFiltroMapeo").html('Ver Programa Completo <i class="fas fa-filter fa-lg"></i>');
            $("#btn_toggleFiltroMapeo").removeClass('btn-primary btn-outline-primary active');
        } else {
            $("#btn_toggleFiltroMapeo").html('Ver solo Pendientes <i class="fas fa-list fa-lg"></i>');
            $("#btn_toggleFiltroMapeo").removeClass('btn-primary btn-outline-primary active');
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
        $saveStatus.stop(true, true).addClass('badge-badge-hidden').removeClass('aia-chip--warning aia-chip--success').fadeOut(120);

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
        var rowId = rowData.unique_id || rowData.Consecutivo_en_Programa;
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
        if (_saveStatus) { _saveStatus.pendiente(1); }

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

        // Usar el identificador estable del programa; el WBS/Id puede tener puntos.
        var cleanId = (typeof rowId === 'string' && rowId.includes('.')) ? null : rowId;
        // The previous line `var rowData = hot.getSourceDataAtRow(visualRowIndex);` was duplicated.
        // It's already defined at the beginning of the function.
        if (!cleanId && rowData) {
            cleanId = rowData.unique_id || rowData.Consecutivo_en_Programa;
        }

        var formData = new URLSearchParams();
        formData.append('unique_id', cleanId);
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
            headers: Object.assign({
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': getCsrfToken()
            }, sessionExpiredFetchHeaders())
        })
        .then(res => handleSessionExpiredResponse(res).then(handled => {
            if (handled) { throw new Error('session-expired'); }
            return res.json();
        }))
        .then(res => {
            if (res.respuesta === "BIEN") {
                $saveStatus.removeClass('aia-chip--warning').addClass('aia-chip--success');
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
                const silentFields = getSilentFields();
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
            // Señal de fila sin asociar: una sola clase en TODAS las celdas de la fila,
            // para que el fondo sea uniforme columna a columna (el tinte vive en
            // programa-general-actualizar.css como token del design system).
            afterRenderer: function(td, row, col, prop, value, cellProperties) {
                var mapped = this.getDataAtRowProp(row, 'programaAnteriorAsociar');
                td.classList.toggle('pg-row-unmapped', mapped === null || mapped === '' || mapped === '*No Asociada*');
            },
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
                { data: 'unique_id', type: 'numeric', readOnly: true },
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
                    // Task 34 (ola de arreglo): las cantidades se leen por unidades
                    // en la misma vertical que en Programa General y Programacion
                    // Semanal. Los identificadores y las fechas siguen centrados.
                    data: 'cantidad_ppto',
                    type: 'numeric',
                    numericFormat: { pattern: '0.0' },
                    className: "htRight htMiddle"
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
                    className: "htRight htMiddle",
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
                    // Task 8 (2026-08-05, ronda 1): «Restricciones» (indice 8) es una palabra
                    // sola de 80 px que no puede envolver y rendizaba una caja de 76 — se cortaba
                    // en seco. Sube de 0.078 a 0.083 (89 px medidos a 1180) y los 5 milesimos
                    // salen de «Actividad Nueva» (indice 2), que rendiza 292 px y envuelve.
                    var raw = [0.031, 0.031, 0.266, 0.233, 0.070, 0.070, 0.047, 0.062, 0.083, 0.109];
                    var sum = raw.reduce(function(a, b) { return a + b; }, 0);
                    _colWidthCache = raw.map(function(r) { return Math.max(Math.floor(cw * r / sum), 20); });
                }
                return _colWidthCache[index];
            },
            // Accesibilidad (Handsontable >= 14.0). Por defecto viene en `false`, y con esa
            // opcion apagada NO hay camino de teclado hasta el embudo de la cabecera: HOT lo
            // pinta con `tabindex="-1"` y `aria-hidden="true"` a proposito, porque espera que
            // se llegue navegando el encabezado con las flechas y se abra con Alt+Abajo.
            // Comprobado en /programa-general el 2026-08-24: sin esto, quien no usa raton no
            // puede filtrar ninguna tabla de la aplicacion.
            navigableHeaders: true,
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
                if (_saveStatus) { _saveStatus.pendiente(pendingCount); }

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
            headers: Object.assign({
                "Content-Type": "application/x-www-form-urlencoded",
                "X-CSRF-Token": getCsrfToken()
            }, sessionExpiredFetchHeaders()),
            body: new URLSearchParams({
                db: db,
                semana_objetivo: semanaObjetivo
            })
        })
        .then(function(response) {
            return handleSessionExpiredResponse(response).then(function (handled) {
                if (handled) { throw new Error('session-expired'); }
                return response.json();
            });
        })
        .then(function(data) {
            console.log("🔥 [AutoAssociate] Results:", data);
            btn.prop("disabled", false).html(originalHtml);
            if (data.success && data.data) {
                applyMatchResults(data.data);
            } else {
                if (typeof toastr !== 'undefined') { toastr.error(data.error || "Error en la asociación automática"); } else { console.error("🔥 [AutoAssociate] " + (data.error || "Error")); }
            }
        })
        .catch(function(error) {
            console.error("🔥 [AutoAssociate] Error:", error);
            btn.prop("disabled", false).html(originalHtml);
            if (typeof toastr !== 'undefined') { toastr.error("Error de conexión al asociar actividades"); } else { console.error("🔥 [AutoAssociate] Error de conexión"); }
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
     * Construye el bloque HTML con Id y fecha de inicio de un candidato.
     * Si no hay ni Id ni fecha, retorna string vacío (no se renderiza nada).
     * Estilo: línea sutil en verde AIA (#1a5633), fuente Inter, icono FA.
     * @param {string|number|null|undefined} id - Id numérico de la actividad.
     * @param {string|null|undefined} fechaInicio - Fecha en formato YYYY-MM-DD o null.
     * @returns {string} HTML string (vacío si no hay datos).
     */
    function buildCandidateMetaHtml(id, fechaInicio) {
        var parts = [];
        if (id != null && id !== '') {
            parts.push('<span style="font-weight: 500;">Id: ' + escapeHtml(String(id)) + '</span>');
        }
        if (fechaInicio) {
            parts.push('<span><i class="fas fa-calendar-alt" style="font-size: var(--ds-type-size-xs); opacity: 0.75;"></i> ' + escapeHtml(fechaInicio) + '</span>');
        }
        if (parts.length === 0) {
            return '';
        }
        return '<div class="match-candidate-meta" ' +
            'style="font-family: var(--aia-font-family-body, \'Inter\', sans-serif); font-size: var(--ds-type-size-xs); font-weight: 400; ' +
            'color: var(--aia-green-primary); margin-bottom: 4px; display: inline-flex; align-items: center; ' +
            'gap: 5px; opacity: 0.85; letter-spacing: 0.01em;">' +
            parts.join('<span style="opacity: 0.4; margin: 0 3px;">&middot;</span>') +
            '</div>';
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
            var metaHtml = buildCandidateMetaHtml(c.id, c.fecha_inicio);

            return '' +
                '<div class="match-candidate ' + tierClass + '" data-row="' + (item.row !== undefined ? item.row : '') + '" data-candidate="' + ci + '">' +
                    '<div class="match-candidate-info">' +
                        '<div class="match-candidate-name">' + escapeHtml(candidateName) + '</div>' +
                        metaHtml +
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
                var metaHtml = buildCandidateMetaHtml(c.id, c.fecha_inicio);

                return '' +
                    '<div class="match-candidate ' + tierClass + ' js-extra-candidate" data-row="' + (item.row !== undefined ? item.row : '') + '" data-candidate="' + (ci + 3) + '">' +
                        '<div class="match-candidate-info">' +
                            '<div class="match-candidate-name">' + escapeHtml(candidateName) + '</div>' +
                            metaHtml +
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
     * Muestra el modal de revisión de auto-asociación con split Pendientes/Procesadas.
     * Inicializa _reviewDecisions, renderiza tabs y bindinea eventos.
     * @param {Object} results - { identical:[], high:[], medium:[], none:[] }
     */
    function showReviewModal(results) {
        var data = results.data || results;
        _reviewResultsRef = results;

        // Resetear decisiones si es la primera apertura
        if (_reviewDecisions === null) {
            _reviewDecisions = {};
        }

        populateModalStats(data);

        var mediumItems = data.medium || [];
        var $reviewList = $('#review-list');
        $reviewList.empty();

        if (mediumItems.length === 0) {
            $reviewList.html(
                '<div class="text-center py-4" style="color: var(--aia-text-secondary);">' +
                    '<i class="fas fa-check-circle fa-2x mb-2" style="color: var(--aia-green-primary);"></i>' +
                    '<p>No hay actividades con confianza media para revisar.</p>' +
                '</div>'
            );
            $('#review-sections').hide();
        } else {
            $('#review-sections').show();
            _renderReviewSections(mediumItems);
        }

        _bindReviewModalEvents($('#modalAutoAsociar'));
        _updateGuardarBtnState();

        // Bind unsaved changes confirmation on hide
        $('#modalAutoAsociar').off('.unsavedGuard').on('hide.bs.modal.unsavedGuard', function(e) {
            _handleModalClose(e);
        });

        $('#modalAutoAsociar').modal('show');
    }

    /**
     * Intercepta el cierre del modal si hay decisiones sin guardar.
     * Usa confirm() como fallback si SweetAlert2 no está disponible.
     */
    function _handleModalClose(e) {
        if (!_reviewDecisions) return;

        var count = 0;
        var keys = Object.keys(_reviewDecisions);
        for (var i = 0; i < keys.length; i++) {
            if (_reviewDecisions[keys[i]] && _reviewDecisions[keys[i]].action !== '__revert__') {
                count++;
            }
        }
        if (count === 0) return;

        // Evitar doble prompt si ya se mostró
        if (_hasShownSavePrompt) {
            e.preventDefault();
            return;
        }
        _hasShownSavePrompt = true;

        e.preventDefault();

        var doClose = function(confirmed) {
            _hasShownSavePrompt = false;
            if (confirmed) {
                $('#modalAutoAsociar').off('.unsavedGuard');
                $('#modalAutoAsociar').modal('hide');
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Salir sin guardar?',
                text: 'Tienes ' + count + ' decision(es) sin guardar. Se perderán si cierras ahora.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--aia-red-primary, #dc3545)',
                cancelButtonColor: 'var(--aia-green-primary)'
            }).then(function(result) {
                doClose(result.isConfirmed);
            });
        } else {
            doClose(confirm('Tienes ' + count + ' decision(es) sin guardar. ¿Salir de todas formas?'));
        }
    }

    /**
     * Renderiza las dos secciones del split view: Pendientes y Procesadas.
     * @param {Array} mediumItems - Lista de ítems de confianza media.
     */
    function _renderReviewSections(mediumItems) {
        var pendingItems = [];
        var processedItems = [];

        mediumItems.forEach(function(item, idx) {
            var cons = String(item.row !== undefined ? item.row : '');
            var decision = _reviewDecisions ? _reviewDecisions[cons] : null;

            if (decision && decision.action === '__revert__') {
                // User reverted from "already associated" — show in pending
                pendingItems.push({ item: item, idx: idx });
            } else if (decision) {
                // User made a manual decision — use it
                processedItems.push({ item: item, idx: idx, decision: decision });
            } else if (item.alreadyAssociated) {
                // Already associated from a previous run — show as processed
                processedItems.push({
                    item: item,
                    idx: idx,
                    decision: {
                        action: 'accept',
                        candidateName: item.currentAssociation || '',
                        existing: true
                    }
                });
            } else {
                pendingItems.push({ item: item, idx: idx });
            }
        });

        // Render pending section
        var $pendingList = $('#review-list-pending');
        $pendingList.empty();

        if (pendingItems.length === 0) {
            $pendingList.html(
                '<div class="text-center py-3" style="color: var(--aia-text-secondary);">' +
                    '<i class="fas fa-check-circle" style="color: var(--aia-green-primary);"></i> ' +
                    'Todas las actividades han sido procesadas.' +
                '</div>'
            );
        } else {
            var pendingHtml = pendingItems.map(function(p) {
                return renderReviewItem(p.item, p.idx);
            }).join('');
            $pendingList.html(pendingHtml);
        }

        // Render processed section
        var $processedList = $('#review-list-processed');
        $processedList.empty();

        if (processedItems.length === 0) {
            $processedList.html(
                '<div class="text-center py-3" style="color: var(--aia-text-secondary);">' +
                    '<i class="fas fa-inbox"></i> ' +
                    'Aún no has procesado ninguna actividad.' +
                '</div>'
            );
        } else {
            var processedHtml = processedItems.map(function(p) {
                return _renderProcessedItem(p.item, p.idx, p.decision);
            }).join('');
            $processedList.html(processedHtml);
        }

        // Update tab badges
        $('#tab-pending-badge').text(pendingItems.length);
        $('#tab-processed-badge').text(processedItems.length);

        // If no pending, auto-switch to processed
        if (pendingItems.length === 0) {
            $('#tab-processed').tab('show');
        }

        _updateGuardarBtnState();
    }

    /**
     * Renderiza un ítem ya procesado (section Procesadas) con badge + botón Cambiar.
     * @param {Object} item - Ítem original de medium.
     * @param {number} idx - Índice original.
     * @param {Object} decision - { action: 'accept'|'skip', candidateName: '...' }
     * @returns {string} HTML.
     */
    function _renderProcessedItem(item, idx, decision) {
        var targetName = item.activityName || 'Actividad sin nombre';
        var isExisting = decision.existing === true;
        var isAccepted = decision.action === 'accept';
        var badgeClass = isExisting ? 'match-resolved-existing' : (isAccepted ? 'match-resolved-accepted' : 'match-resolved-skipped');
        var cons = String(item.row !== undefined ? item.row : '');
        var label;

        if (isExisting) {
            label = '<i class="fas fa-history" style="color: var(--aia-text-muted, #6c757d);"></i> ' +
                'Asociación previa: <strong>' + escapeHtml(decision.candidateName || '—') + '</strong>';
        } else if (isAccepted) {
            label = '<i class="fas fa-check-circle" style="color: var(--aia-green-primary);"></i> ' +
                'Asociada con: <strong>' + escapeHtml(decision.candidateName) + '</strong>';
        } else {
            label = '<i class="fas fa-times-circle"></i> Marcada como actividad nueva';
        }

        var changeButtonClass = isExisting ? 'js-revert-existing' : 'js-change-decision';
        var changeTitle = isExisting ? 'Reasignar esta actividad' : 'Volver a evaluar esta actividad';

        return '' +
            '<div class="match-item match-item-resolved ' + (isExisting ? 'match-item-existing' : (isAccepted ? 'match-item-accepted' : 'match-item-skipped')) + '" data-index="' + idx + '" data-row="' + cons + '">' +
                '<div class="match-item-header">' +
                    '<div class="match-target-label">' +
                        '<span class="match-label match-label-target"><i class="fas fa-crosshairs mr-1"></i> Actividad</span>' +
                    '</div>' +
                    '<div class="match-activity-name">' +
                        escapeHtml(targetName) +
                    '</div>' +
                '</div>' +
                '<div class="match-item-body">' +
                    '<div class="match-resolved-badge ' + badgeClass + '" style="margin-bottom: 0.5rem;">' +
                        '<span>' + label + '</span>' +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary ' + changeButtonClass + '" ' +
                        'data-row="' + cons + '" ' +
                        'data-index="' + idx + '" ' +
                        'title="' + changeTitle + '">' +
                        '<i class="fas fa-undo"></i> Cambiar' +
                    '</button>' +
                '</div>' +
            '</div>';
    }

    /**
     * Bindea eventos delegados del modal de revisión:
     * - Aceptar: guarda decisión local en _reviewDecisions
     * - Marcar Nueva: guarda decisión local
     * - Cambiar: revierte decisión local
     * - Ver más opciones: expande candidatos extra
     * - Guardar Cambios: persiste todas las decisiones en batch
     */
    function _bindReviewModalEvents($container) {
        $container.off('.reviewModal');

        // Guardar decisión de aceptar
        $container.on('click.reviewModal', '.js-accept-match', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var consecutivo = String($btn.data('row'));
            var candidateName = $btn.data('candidate-name');

            if (!consecutivo || !candidateName) return;

            _reviewDecisions[consecutivo] = {
                action: 'accept',
                candidateName: candidateName
            };

            if (typeof toastr !== 'undefined') {
                toastr.success('Asociado: ' + candidateName);
            }

            _refreshReviewUI();
        });

        // Guardar decisión de saltar (marcar como nueva)
        $container.on('click.reviewModal', '.js-skip-match', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var consecutivo = String($btn.data('row'));

            if (!consecutivo) return;

            _reviewDecisions[consecutivo] = {
                action: 'skip',
                candidateName: '*No Asociada*'
            };

            if (typeof toastr !== 'undefined') {
                toastr.info('Actividad marcada como nueva');
            }

            _refreshReviewUI();
        });

        // Cambiar decisión (revertir)
        $container.on('click.reviewModal', '.js-change-decision', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var consecutivo = String($btn.data('row'));

            if (!consecutivo) return;

            delete _reviewDecisions[consecutivo];

            if (typeof toastr !== 'undefined') {
                toastr.info('Decisión revertida. Re-evalúa la actividad.');
            }

            _refreshReviewUI();
        });

        // Revertir asociación existente a Pendientes
        $container.on('click.reviewModal', '.js-revert-existing', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var consecutivo = String($btn.data('row'));

            if (!consecutivo) return;

            _reviewDecisions[consecutivo] = { action: '__revert__' };

            if (typeof toastr !== 'undefined') {
                toastr.info('Actividad movida a Pendientes para reasignación.');
            }

            _refreshReviewUI();
        });

        // Ver más opciones (expandir candidatos extra)
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

        // Guardar Cambios batch
        $container.on('click.reviewModal', '#btn-guardar-cambios', function(e) {
            e.preventDefault();
            _saveReviewDecisions();
        });
    }

    /**
     * Re-renderiza las secciones Pendientes/Procesadas y actualiza botón Guardar.
     * Se llama tras cada decisión o cambio.
     */
    function _refreshReviewUI() {
        var data = _reviewResultsRef ? (_reviewResultsRef.data || _reviewResultsRef) : null;
        if (!data) return;

        var mediumItems = data.medium || [];
        _renderReviewSections(mediumItems);
    }

    /**
     * Persiste en batch todas las decisiones acumuladas en _reviewDecisions.
     * Escribe en la grilla y muestra resultado.
     */
    function _saveReviewDecisions() {
        var decisions = _reviewDecisions;
        var hasRealChanges = false;

        if (decisions) {
            Object.keys(decisions).forEach(function(key) {
                if (decisions[key] && decisions[key].action !== '__revert__') {
                    hasRealChanges = true;
                }
            });
        }

        if (!hasRealChanges) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('No hay cambios para guardar.');
            }
            return;
        }

        var accepted = 0;
        var skipped = 0;

        Object.keys(decisions).forEach(function(consecutivo) {
            var decision = decisions[consecutivo];
            if (!hot) return;

            var sourceData = hot.getSourceData();
            var visualRow = null;
            for (var i = 0; i < sourceData.length; i++) {
                if (String(sourceData[i].unique_id || sourceData[i].Consecutivo_en_Programa) === consecutivo) {
                    visualRow = i;
                    break;
                }
            }

            if (visualRow === null) return;

            if (decision.action === 'accept') {
                autoSaveRow(visualRow, {'programaAnteriorAsociar': decision.candidateName}, 'edit');
                hot.setDataAtRowProp(visualRow, 'programaAnteriorAsociar', decision.candidateName, 'internal');
                // Log manual review decisions
                if (window.DecisionLogger) {
                    DecisionLogger.log({
                        actividad: {
                            nombre: hot.getDataAtRowProp(visualRow, 'Actividad') || '',
                            posicion_pg: consecutivo || 0
                        },
                        sugerencia: {
                            proceso: decision.candidateName || '',
                            confianza: 0.5,
                            engine: 'manual_review'
                        },
                        decisionUsuario: {
                            accion: 'accept',
                            proceso_elegido: decision.candidateName || '*No Asociada*'
                        }
                    });
                }
                accepted++;
            } else if (decision.action === 'skip') {
                autoSaveRow(visualRow, {'programaAnteriorAsociar': '*No Asociada*'}, 'edit');
                hot.setDataAtRowProp(visualRow, 'programaAnteriorAsociar', '*No Asociada*', 'internal');
                // Log manual review decisions
                if (window.DecisionLogger) {
                    DecisionLogger.log({
                        actividad: {
                            nombre: hot.getDataAtRowProp(visualRow, 'Actividad') || '',
                            posicion_pg: consecutivo || 0
                        },
                        sugerencia: {
                            proceso: decision.candidateName || '',
                            confianza: 0.5,
                            engine: 'manual_review'
                        },
                        decisionUsuario: {
                            accion: 'skip',
                            proceso_elegido: decision.candidateName || '*No Asociada*'
                        }
                    });
                }
                skipped++;
            }
        });

        // Limpiar decisiones guardadas
        _reviewDecisions = {};
        _refreshReviewUI();

        if (typeof toastr !== 'undefined') {
            toastr.success(
                accepted + ' asociada(s), ' + skipped + ' nueva(s) — Guardadas correctamente.'
            );
        }
    }

    /**
     * Actualiza el estado del botón Guardar Cambios según decisiones pendientes.
     */
    function _updateGuardarBtnState() {
        var $btn = $('#btn-guardar-cambios');
        var count = 0;

        if (_reviewDecisions) {
            Object.keys(_reviewDecisions).forEach(function(key) {
                if (_reviewDecisions[key] && _reviewDecisions[key].action !== '__revert__') {
                    count++;
                }
            });
        }

        if (count > 0) {
            $btn.prop('disabled', false);
            $btn.find('.guardar-count').text('(' + count + ')');
        } else {
            $btn.prop('disabled', true);
            $btn.find('.guardar-count').text('');
        }
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

        // Medium items have Consecutivo_en_Programa as row identifier
        var mediumRows = {};
        (data.medium || []).forEach(function(item) {
            if (item.row) {
                mediumRows[String(item.row)] = true;
            }
        });

        // Store results and colIndex for re-application via afterRender hook
        hot._highlightData = { sourceData: sourceData, mediumRows: mediumRows, colIndex: colIndex, results: results };

        _applyHighlightClasses();
    }

    function _applyHighlightClasses() {
        if (!hot || !hot._highlightData) return;

        var hd = hot._highlightData;
        var sourceData = hd.sourceData;
        var mediumRows = hd.mediumRows;
        var colIndex = hd.colIndex;

        for (var i = 0; i < sourceData.length; i++) {
            var row = sourceData[i];
            var val = row.programaAnteriorAsociar;
            var consecutivo = String(row.unique_id || row.Consecutivo_en_Programa);
            var className;

            if (mediumRows[consecutivo]) {
                className = 'pg-match-review';
            } else if (val && val !== '*No Asociada*' && val !== '') {
                className = 'pg-match-auto';
            } else {
                className = 'pg-match-new';
            }

            var visualRow = hot.toVisualRow(i);
            if (visualRow !== null && visualRow >= 0) {
                var td = hot.getCell(visualRow, colIndex);
                if (td) {
                    td.classList.add(className);
                }
            }
        }

        // Register afterRender hook once so classes persist after scroll/edit/re-render
        if (!hot._highlightHookRegistered) {
            hot._highlightFn = function() {
                _applyHighlightClasses();
            };
            hot._highlightHookRegistered = true;
            hot.addHook('afterRender', hot._highlightFn);
        }
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

        // Show ALL rows (not only unmapped) so highlighting covers the full dataset
        showingUnmappedOnly = false;

        // Reload grid data so highlighting reflects the DB state after auto-associate
        _loadDataFetched = false;
        loadData();

        // Apply highlighting after data reloads
        setTimeout(function() {
            applyGridHighlighting(results);

            // Log auto-accepted decisions (identical + high confidence)
            if (window.DecisionLogger) {
                var autoItems = (results.data || results).identical || [];
                var highItems = (results.data || results).high || [];
                var allAuto = [].concat(autoItems, highItems);
                allAuto.forEach(function(item) {
                    var targetRow = item.target && item.target.row ? item.target.row : null;
                    if (targetRow) {
                        DecisionLogger.log({
                            actividad: {
                                nombre: item.target.name || '',
                                posicion_pg: targetRow.Consecutivo_en_Programa || 0,
                                capitulo: item.target.chapter || null
                            },
                            sugerencia: {
                                proceso: (item.matched && item.matched.name) || '',
                                confianza: item.confidence || 1.0,
                                engine: 'auto-associate',
                                regla: item.confidence >= 0.95 ? 'identical' : 'high_confidence'
                            },
                            decisionUsuario: {
                                accion: 'accept',
                                proceso_elegido: (item.matched && item.matched.name) || ''
                            }
                        });
                    }
                });
            }

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
        saveReviewDecisions: _saveReviewDecisions,
        get reviewDecisions() { return _reviewDecisions; },
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
