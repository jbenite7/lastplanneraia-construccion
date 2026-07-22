(function (window, $) {
  'use strict';

  var mobileQuery = window.matchMedia('(max-width: 767px)');
  var responsiveBindings = Object.create(null);

  function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
  }

  function text(value, fallback) {
    var normalized = value == null ? '' : String(value).trim();
    return normalized || fallback || 'Sin dato';
  }

  function plainText(value) {
    var holder = document.createElement('div');
    holder.innerHTML = value == null ? '' : String(value);
    Array.prototype.forEach.call(holder.querySelectorAll('script, style'), function (node) {
      node.remove();
    });
    return (holder.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function percent(value) {
    var numeric = Number(value);
    if (!Number.isFinite(numeric)) return text(value);
    return (numeric <= 1 ? numeric * 100 : numeric).toFixed(0) + '%';
  }

  function metric(label, value, wide) {
    return '<div class="ps-legacy-card-metric' + (wide ? ' is-wide' : '') + '">'
      + '<span>' + escapeHtml(label) + '</span>'
      + '<strong>' + escapeHtml(text(value)) + '</strong></div>';
  }

  function action(icon, label, action, index, secondary) {
    return '<button type="button" class="ps-legacy-card-action' + (secondary ? ' is-secondary' : '') + '"'
      + ' data-legacy-action="' + action + '" data-legacy-row="' + index + '">'
      + '<i class="fas ' + icon + '" aria-hidden="true"></i><span>' + label + '</span></button>';
  }

  function stateClass(row) {
    if (Number(row.Atrasada) === 1 && Number(row.Critica) === 1) return 'is-critical';
    if (Number(row.Atrasada) === 1 || Number(row.Critica) === 1) return 'is-warning';
    return 'is-neutral';
  }

  function cnpStateClass(row) {
    var isOverdue = Number(row.Atrasada) === 1;
    var isCritical = Number(row.Critica) === 1;
    if (isOverdue) {
      return isCritical ? 'is-cnp-overdue-critical' : 'is-cnp-overdue-non-critical';
    }
    return isCritical ? 'is-cnp-critical' : 'is-cnp-non-critical';
  }

  function canEditCurrentWeek() {
    var roleNode = document.getElementById('permiso_canonico');
    var weekNode = document.querySelector('#semana, #semana_PHP');
    var maxWeekNode = document.getElementById('Max_Semana');
    var role = String(roleNode ? roleNode.value : '').toUpperCase();
    var week = Number(weekNode ? weekNode.value : 0);
    var maxWeek = Number(maxWeekNode ? maxWeekNode.value : 0);
    if (window.RbacCapabilities) {
      return Boolean(window.RbacCapabilities.canEditLps(role, week, maxWeek));
    }
    if (role === 'A' || role === 'D') return true;
    return (role === 'R' || role === 'DCV') && maxWeek - 2 < week;
  }

  function currentRole() {
    var node = document.getElementById('permiso_canonico');
    return String(node ? node.value : '').trim().toUpperCase();
  }

  function canEditCic() {
    var role = currentRole();
    if (['A', 'D', 'G', 'S', 'SG', 'OT'].indexOf(role) !== -1) return true;
    return role === 'R' && canEditCurrentWeek();
  }

  function renderCnp(row, index, canEdit) {
    var canReprogram = Number($('#Semanal_Confirmada').val() || 0) !== 1;
    var html = metric('Responsable AIA', row.Responsable_AIA);
    html += metric('Programación liberada', String(row.Prog_Sin_Restricciones_100) === '0' ? 'Sí' : 'No');
    html += metric('Categoría CNP', row.Categoria_CNP, true);
    html += metric('Causa', row.CNP, true);
    html += metric('Observaciones', row.Observaciones_CNP, true);
    var actions = canEdit ? action('fa-edit', 'Editar causa', 'edit', index, false) : '';
    if (canEdit && canReprogram) actions += action('fa-undo-alt', 'Reprogramar', 'reprogram', index, true);
    return card(row.Id, row.Actividad, 'Causa no programación', html, actions, cnpStateClass(row));
  }

  function renderCnc(row, index, canEdit) {
    var html = metric('Descripción', row.Descripcion, true);
    html += metric('Categoría CNC', row.Categoria_CNC, true);
    html += metric('Causa', row.CNC, true);
    html += metric('Observaciones', row.Observaciones_CNC, true);
    var actions = canEdit ? action('fa-edit', 'Editar causa', 'edit', index, false) : '';
    return card(row.Id, row.Actividad, 'Causa no cumplimiento', html, actions, stateClass(row));
  }

  function renderCic(row, index, canEdit) {
    var html = metric('Alcance', row.alcance, true);
    html += metric('Tipo de proveedor', row.tipo_proveedor, true);
    html += metric('Semana', row.Semana);
    html += metric('PPC', percent(row.PAC));
    html += metric('Calidad', percent(row.Calidad));
    html += metric('Gestión', percent(row.GSA));
    html += metric('SST', percent(row.SST));
    html += metric('Administración', percent(row.ADM));
    html += metric('Calificación integral', percent(row.Cal_Integral), true);
    html += metric('Observaciones', row.Observaciones, true);
    var supportedProvider = row.tipo_proveedor === 'Mano de Obra'
      || row.tipo_proveedor === 'Suministro e Instalación';
    var actions = canEdit && supportedProvider
      ? action('fa-clipboard-check', 'Calificar proveedor', 'edit', index, false)
      : '';
    return card(row.Semana, row.subcontratista, 'Calificación proveedor', html, actions, 'is-neutral');
  }

  function card(id, title, eyebrow, metrics, actions, state) {
    return '<article class="ps-legacy-card ' + state + '"><header><div>'
      + '<span class="ps-legacy-card-id">' + escapeHtml(text(id)) + '</span>'
      + '<h3>' + escapeHtml(text(plainText(title))) + '</h3></div>'
      + '<span class="ps-legacy-card-eyebrow">' + escapeHtml(eyebrow) + '</span></header>'
      + '<div class="ps-legacy-card-metrics">' + metrics + '</div>'
      + '<footer>' + actions + '</footer></article>';
  }

  function render(table, type, container) {
    if (!mobileQuery.matches) return;
    var rows = table.rows({ search: 'applied' });
    var data = rows.data().toArray();
    var canEdit = type === 'cic' ? canEditCic() : canEditCurrentWeek();
    if (!data.length) {
      container.innerHTML = '<div class="ps-legacy-card-empty">No hay registros para los filtros actuales.</div>';
      return;
    }
    container.innerHTML = data.map(function (row, index) {
      if (type === 'cnp') return renderCnp(row, index, canEdit);
      if (type === 'cnc') return renderCnc(row, index, canEdit);
      return renderCic(row, index, canEdit);
    }).join('');
  }

  function closeMobileEditor(container) {
    $(container).find('#ps-legacy-mobile-editor').remove();
  }

  function weekValue() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function showFeedback(response) {
    var ok = response && response.respuesta === 'BIEN';
    var message = ok ? 'Los cambios se guardaron correctamente.'
      : 'Error: ' + text(response && response.mensaje, 'no se guardaron los cambios.');
    $('#mensajeActualizacion').text(message)
      .removeClass('success error').addClass(ok ? 'success' : 'error').show();
  }

  function fillOptions($select, values, selected) {
    $select.empty().append($('<option>', { value: '', text: '' }));
    values.forEach(function (value) {
      if (value) $select.append($('<option>', { value: value, text: value }));
    });
    if (selected && values.indexOf(selected) === -1) {
      $select.append($('<option>', { value: selected, text: selected }));
    }
    $select.val(selected || '');
  }

  function loadReasons($select, category, selected) {
    if (!category) {
      fillOptions($select, [], '');
      return;
    }
    $.ajax({ method: 'POST', url: '/api/cnc/reasons', dataType: 'json',
      data: { categoria: category, area: window.__PROJECT_AREA__ || 'Construccion' } })
      .done(function (rows) {
        fillOptions($select, (rows || []).map(function (row) { return row.CNC; }), selected);
      });
  }

  function editorField(label, $control) {
    return $('<div>', { class: 'ps-legacy-mobile-editor-field' })
      .append($('<label>', { for: $control.attr('id'), text: label }), $control);
  }

  function causeCategories(selected) {
    var values = ['Rendimiento', 'Programación', 'Mano de Obra', 'Materiales',
      'Equipos', 'Diseños', 'Administrativas', 'Causas Exógenas'];
    if (selected && values.indexOf(selected) === -1) values.unshift(selected);
    return values;
  }

  function causeEditorControls(row, type) {
    var categoryValue = type === 'cnp' ? row.Categoria_CNP : row.Categoria_CNC;
    var causeValue = type === 'cnp' ? row.CNP : row.CNC;
    var observation = type === 'cnp' ? row.Observaciones_CNP : row.Observaciones_CNC;
    var $category = $('<select>', { id: 'select_Categoria_CNC', class: 'form-control form-control-sm' });
    var $cause = $('<select>', { id: 'select_CNC', class: 'form-control form-control-sm' });
    var $observation = $('<textarea>', { id: 'select_Observaciones_CNC',
      class: 'form-control form-control-sm' }).val(observation || '');
    fillOptions($category, causeCategories(categoryValue), categoryValue);
    loadReasons($cause, categoryValue, causeValue);
    return { category: $category, cause: $cause, observation: $observation,
      categoryValue: categoryValue, causeValue: causeValue };
  }

  function createCauseEditorPanel(row, type) {
    var controls = causeEditorControls(row, type);
    var $panel = $('<section>', { id: 'ps-legacy-mobile-editor',
      class: 'ps-legacy-mobile-editor', role: 'dialog',
      'aria-labelledby': 'ps-legacy-mobile-editor-title' });
    $panel.append($('<h3>', { id: 'ps-legacy-mobile-editor-title', text: 'Editar causa' }));
    if (type === 'cnp') {
      var professionals = (window.PS_CNP_PROFESSIONALS || []).slice();
      var $responsible = $('<select>', { id: 'select_Responsable_AIA', class: 'form-control form-control-sm' });
      fillOptions($responsible, professionals, row.Responsable_AIA || '');
      $panel.append(editorField('Responsable AIA', $responsible));
      controls.responsible = $responsible;
    }
    $panel.append(editorField('Categoría', controls.category));
    $panel.append(editorField('Causa', controls.cause));
    $panel.append(editorField('Observaciones', controls.observation));
    controls.panel = $panel;
    return controls;
  }

  function causeSavePayload(row, type, controls) {
    var payload = { Consecutivo: row.row_id || row.Consecutivo,
      semana: weekValue() };
    if (type === 'cnp') {
      payload.Responsable_AIA = controls.responsible.val();
      payload.Categoria_CNP = controls.category.val();
      payload.CNP = controls.cause.val();
      payload.Observaciones_CNP = controls.observation.val();
    } else {
      payload.Categoria_CNC = controls.category.val();
      payload.CNC = controls.cause.val();
      payload.Observaciones_CNC = controls.observation.val();
    }
    return payload;
  }

  function appendCauseEditorActions(controls, container) {
    var $actions = $('<div>', { class: 'ps-legacy-mobile-editor-actions' });
    var $save = $('<button>', { type: 'button', id: 'btn_guardar_editar',
      class: 'guardar btn btn-success btn-sm ps-btn-edit', 'aria-label': 'Guardar cambios' })
      .append('<i class="fa fa-save" aria-hidden="true"></i><span>Guardar</span>');
    var $cancel = $('<button>', { type: 'button', id: 'btn_cancelar_editar',
      class: 'cancelar btn btn-danger btn-sm ps-btn-edit', 'aria-label': 'Cancelar edición' })
      .append('<i class="fa fa-undo" aria-hidden="true"></i><span>Cancelar</span>');
    $actions.append($save, $cancel);
    controls.panel.append($actions);
    $cancel.on('click.psLegacyCardsEditor', function () { closeMobileEditor(container); });
    controls.category.on('change.psLegacyCardsEditor', function () {
      loadReasons(controls.cause, controls.category.val(), '');
    });
    return $save;
  }

  function openDirectCauseEditor(row, type, container, table) {
    closeMobileEditor(container);
    $('#Id').val(row.row_id || row.Consecutivo);
    var controls = createCauseEditorPanel(row, type);
    var $save = appendCauseEditorActions(controls, container);
    $(container).prepend(controls.panel);
    $save.on('click.psLegacyCardsEditor', function () {
      $.ajax({ method: 'POST', url: type === 'cnp' ? '/api/cnp/save' : '/api/cnc/save',
        dataType: 'json', data: causeSavePayload(row, type, controls) })
        .done(function (response) {
          showFeedback(response);
          if (response && response.respuesta === 'BIEN') table.ajax.reload(null, false);
        }).fail(function (xhr) {
          showFeedback((xhr.responseJSON || { respuesta: 'ERROR' }));
        });
    });
    controls.panel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function openDirectReprogram(row, table) {
    var id = row.row_id || row.Consecutivo;
    $('#Id').val(id);
    $('#semana').val(row.Semana || weekValue());
    $('#modal-body-texto-reprogramar').text(
      '¿Desea reprogramar la actividad: ' + plainText(row.Actividad) + '?'
    );
    $('#reprogramar-usuario').off('click.psLegacyCards').one('click.psLegacyCards', function () {
      $.ajax({ method: 'POST', url: '/api/cnp/reprogramar', dataType: 'json',
        data: { Id: id, semana: row.Semana || weekValue() } })
        .done(function (response) {
          showFeedback(response);
          if (response && response.respuesta === 'BIEN') table.ajax.reload(null, false);
        }).fail(function (xhr) { showFeedback(xhr.responseJSON || { respuesta: 'ERROR' }); });
    });
    $('#modalReprogramar').modal('show');
  }

  function setCicFormValues(row, prefix) {
    Object.keys(row).forEach(function (key) {
      if (key.indexOf(prefix + '_') !== 0) return;
      var value = row[key];
      if (value === null || value === undefined || value === '') return;
      $('input[name="' + key + '"][value="' + String(value) + '"]').prop('checked', true);
    });
    $('#' + prefix + '_Observaciones').val(row.Observaciones || '');
  }

  function cicFormPayload(prefix, row) {
    return $('#' + (prefix === 'mdo' ? 'formulario_cic_mdo' : 'formulario_cic_si')).serialize()
      + '&opcion=modificar_' + prefix + '&Id=' + encodeURIComponent(row.Id)
      + '&semana=' + encodeURIComponent(row.Semana || weekValue());
  }

  function openDirectCicEditor(row, table) {
    var prefix = row.tipo_proveedor === 'Mano de Obra' ? 'mdo' : 'si';
    var modalId = prefix === 'mdo' ? '#modalcic_mdo' : '#modalcic_si';
    $('#Id').val(row.Id);
    $('#ultimaSemanaContratista').val(row.Semana);
    $('#opcion').val('modificar_' + prefix);
    setCicFormValues(row, prefix);
    $('#modal-body-texto-cic_' + prefix).empty()
      .append($('<div>', { text: 'Formulario de Calificación de proveedores.' }))
      .append($('<div>', { class: 'mt-2', text: 'Sub-Contratista: ' + text(row.subcontratista) }));
    $('#btn_guardar_cic_' + prefix).off('click.psLegacyCards').one('click.psLegacyCards', function () {
      $.ajax({ method: 'POST', url: '/api/cic/save', dataType: 'json',
        data: cicFormPayload(prefix, row) }).done(function (response) {
          showFeedback(response);
          if (response && response.respuesta === 'BIEN') table.ajax.reload(null, false);
        }).fail(function (xhr) { showFeedback(xhr.responseJSON || { respuesta: 'ERROR' }); });
    });
    $(modalId).modal('show');
  }

  function syncTableVisibility(table) {
    var hidden = mobileQuery.matches;
    var node = table.table().node();
    var container = table.table().container();
    $(node).toggleClass('ps-legacy-table-hidden', hidden);
    $(container).find('.dataTables_scrollHead, .dataTables_scrollBody, .dataTables_info')
      .toggleClass('ps-legacy-table-hidden', hidden);
  }

  function syncResponsiveBinding(binding) {
    if (!binding || !$.fn.dataTable.isDataTable(binding.node)) return;
    syncTableVisibility(binding.table);
    render(binding.table, binding.type, binding.container);
  }

  mobileQuery.addEventListener('change', function () {
    Object.keys(responsiveBindings).forEach(function (type) {
      syncResponsiveBinding(responsiveBindings[type]);
    });
  });

  function searchLabel(type) {
    // Cortas a propósito: el input a 1180px trunca los rótulos largos.
    if (type === 'cnp') return 'Buscar actividad o causa';
    if (type === 'cnc') return 'Buscar actividad o causa';
    return 'Buscar subcontratista';
  }

  function installSearch(table, type) {
    var $toolbar = $('div.toolbarFiltro').first();
    if (!$toolbar.length) return;
    var label = searchLabel(type);
    $toolbar.html('<div class="ps-toolbar-filter">'
      + '<label class="sr-only" for="ps_legacy_search">' + escapeHtml(label) + '</label>'
      + '<div class="ps-toolbar-search-controls"><input id="ps_legacy_search" type="search"'
      + ' class="form-control" placeholder="' + escapeHtml(label) + '">'
      + '<button id="btn_limpiar_buscador" type="button" class="btn-pdc-modern ps-filter-clear"'
      + ' aria-label="Borrar búsqueda" disabled><i class="fas fa-times-circle" aria-hidden="true"></i>'
      + ' <span>Borrar</span></button></div></div>');
    var $input = $toolbar.find('#ps_legacy_search');
    var $clear = $toolbar.find('#btn_limpiar_buscador');
    $input.off('input.psLegacyCards').on('input.psLegacyCards', function () {
      var value = String(this.value || '');
      $clear.prop('disabled', value.length === 0);
      table.search(value).draw();
    });
    $clear.off('click.psLegacyCards').on('click.psLegacyCards', function () {
      $input.val('');
      $clear.prop('disabled', true);
      table.search('').draw();
      $input.trigger('focus');
    });
  }

  function attach(table, type) {
    var shell = document.getElementById('cuadroTabla');
    if (!shell || !table) return;
    var container = document.getElementById('ps-legacy-card-view');
    if (!container) {
      container = document.createElement('div');
      container.id = 'ps-legacy-card-view';
      container.className = 'ps-legacy-card-view';
      shell.appendChild(container);
    }
    responsiveBindings[type] = {
      container: container,
      node: table.table().node(),
      table: table,
      type: type
    };
    installSearch(table, type);
    table.off('draw.psLegacyCards').on('draw.psLegacyCards', function () {
      syncTableVisibility(table);
      render(table, type, container);
    });
    $(container).off('click.psLegacyCards').on('click.psLegacyCards', '[data-legacy-action]', function () {
      var actionName = this.dataset.legacyAction;
      var rows = table.rows({ search: 'applied' }).data().toArray();
      var row = rows[Number(this.dataset.legacyRow)];
      if (!row) return;
      if (actionName === 'reprogram') {
        openDirectReprogram(row, table);
      } else if (type === 'cic') {
        openDirectCicEditor(row, table);
      } else {
        openDirectCauseEditor(row, type, container, table);
      }
    });
    syncTableVisibility(table);
    render(table, type, container);
  }

  window.PSLegacyCards = { attach: attach, plainText: plainText };
})(window, window.jQuery);
