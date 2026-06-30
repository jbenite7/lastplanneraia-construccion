(function (window, $) {
  'use strict';

  var MODULES = {
    'listado-actividades': {
      label: 'Listado de Actividades',
      base: '/api/listado-actividades/auto',
      empty: 'No hay actividades nuevas para proponer en esta semana.'
    },
    contratos: {
      label: 'Contratos',
      base: '/api/contratos/auto',
      empty: 'No hay contratos pendientes por sugerir en esta semana.'
    },
    pdc: {
      label: 'Plan de Compras',
      base: '/api/pdc/auto',
      empty: 'No hay paquetes pendientes por proponer en esta semana.'
    }
  };

  var FIELD_LABELS = {
    actividad: 'Actividad',
    descripcionActividad: 'Descripción',
    actividadInicio: 'Actividad de inicio',
    fechaInicio: 'Fecha de inicio',
    fechaInicioProyectada: 'Inicio proyectado',
    semanaActualizacion: 'Semana',
    tipoContrato: 'Modalidad de contratación',
    numeroSubcontratos: 'Número de subcontratos',
    contratos: 'Contratos asociados',
    estado: 'Estado',
    tipoPaquete: 'Tipo de paquete',
    paqueteContratacion: 'Paquete de contratación',
    fechaElaboracionPliegos: 'Inicio de elaboración de pliegos',
    fechaEntregaPliegos: 'Entrega de pliegos',
    fechaReciboPropuestas: 'Recibo de propuestas',
    fechaCuadrosComparativos: 'Cuadros comparativos',
    fechaLegalizacionContrato: 'Legalización del contrato',
    fechaFabricacion: 'Fabricación',
    fechaInsumosObra: 'Insumos en obra'
  };

  var TECHNICAL_FIELDS = {
    confianza_deteccion: true
  };

  var ACTION_LABELS = {
    create_activity: 'Crear actividad',
    update_contracts: 'Definir contratos',
    create_pdc_package: 'Crear paquete',
    update_pdc_package: 'Actualizar paquete',
    review_no_match: 'Revisar manualmente'
  };

  var ACTION_MESSAGES = {
    create_activity: 'El sistema agrupó actividades del programa y propone crear una actividad de seguimiento.',
    update_contracts: 'El sistema encontró una familia de actividad y propone los paquetes de contratación.',
    create_pdc_package: 'Este paquete aparece en Contratos y todavía no está en el Plan de Compras.',
    update_pdc_package: 'El paquete ya existe. Revisa los cambios propuestos antes de actualizarlo.',
    review_no_match: 'No hay suficiente información para aplicar un cambio automático.'
  };

  var GROUPS = {
    ready: {
      title: 'Listo para aplicar',
      short: 'Listo',
      description: 'Propuestas de alta seguridad. Ya están marcadas para aplicar.',
      icon: 'fa-check-circle'
    },
    review: {
      title: 'Requiere revisión',
      short: 'Revisar',
      description: 'Propuestas útiles, pero conviene revisarlas antes de aplicarlas.',
      icon: 'fa-eye'
    },
    conflict: {
      title: 'Conflictos',
      short: 'Conflictos',
      description: 'No se aplican automáticamente. Requieren decisión manual.',
      icon: 'fa-exclamation-triangle'
    }
  };

  var instances = {};

  function ctxQuery() {
    var db = ($('#baseDatos').val() || '').trim();
    var semana = ($('#semana').val() || $('#Max_Semana').val() || '').trim();
    var parts = [];
    if (db) parts.push('db=' + encodeURIComponent(db));
    if (semana) parts.push('semana=' + encodeURIComponent(semana));
    return parts.length ? '?' + parts.join('&') : '';
  }

  function endpoint(module, action) {
    return MODULES[module].base + '/' + action + ctxQuery();
  }

  function isAdmin() {
    return String($('#permiso_canonico').val() || '').toUpperCase() === 'A';
  }

  function ensureStyles() {
    if (document.getElementById('semi-auto-review-style')) return;
    var css = ''
      + '.semi-auto-review{margin:12px 0 14px;border:1px solid #d5e5db;border-radius:8px;background:#fff;box-shadow:0 4px 14px rgba(36,49,58,.08);font-family:Inter,Arial,sans-serif;}'
      + '.semi-auto-review[hidden]{display:none!important}.sar-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;}'
      + '.sar-title{font-weight:800;color:#24313a;font-size:1.05rem}.sar-meta{font-size:.9rem;color:#64748b;margin-top:3px;max-width:680px}.sar-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.sar-actions .btn-pdc-modern,.sar-actions .btn{white-space:nowrap}.sar-body{padding:14px 16px}.sar-foot{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;background:#fafafa}.sar-status{font-weight:600}.sar-error{color:#991b1b}.sar-ok{color:#166534}'
      + '.sar-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:0 0 14px}.sar-step{display:flex;gap:8px;align-items:center;border:1px solid #dbe7ef;border-radius:8px;padding:9px 10px;color:#475569;background:#fff}.sar-step.is-active{border-color:#2c7a4b;background:#f0f9f3;color:#14532d}.sar-step-num{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:#e2e8f0;font-weight:800}.sar-step.is-active .sar-step-num{background:#2c7a4b;color:#fff}.sar-step-title{font-weight:700}.sar-step-copy{display:block;font-size:.78rem;color:#64748b;line-height:1.25}'
      + '.sar-filters{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px}.sar-filters select,.sar-filters input{height:34px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 8px;font-size:.86rem;background:#fff}.sar-summary{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;color:#334155}.sar-summary-pill{border:1px solid #dbe7ef;background:#f8fafc;border-radius:999px;padding:5px 10px;font-weight:700}.sar-summary-pill.ready{border-color:#bbf7d0;background:#f0fdf4;color:#166534}.sar-summary-pill.review{border-color:#fed7aa;background:#fff7ed;color:#9a3412}.sar-summary-pill.conflict{border-color:#fecaca;background:#fef2f2;color:#991b1b}'
      + '.sar-groups{display:flex;flex-direction:column;gap:12px}.sar-group{border:1px solid #e2e8f0;border-radius:8px;background:#fff;overflow:hidden}.sar-group-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0}.sar-group-title{font-weight:800;color:#24313a}.sar-group-desc{font-size:.82rem;color:#64748b}.sar-group-count{font-weight:800;border-radius:999px;background:#e2e8f0;color:#334155;padding:4px 9px}.sar-card-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:10px;padding:10px}.sar-card{border:1px solid #e2e8f0;border-radius:8px;background:#fff;display:flex;flex-direction:column;min-height:150px}.sar-card.is-ready{border-color:#bbf7d0}.sar-card.is-review{border-color:#fed7aa}.sar-card.is-conflict{border-color:#fecaca;opacity:.9}.sar-card-top{display:flex;gap:10px;padding:11px 12px;border-bottom:1px solid #edf2f7}.sar-card-check{margin-top:4px}.sar-card-main{min-width:0;flex:1}.sar-card-title-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.sar-card-title{font-weight:800;color:#24313a}.sar-card-subtitle{color:#64748b;margin-top:2px;word-break:break-word}.sar-card-message{font-size:.86rem;color:#475569;margin-top:7px;line-height:1.35}.sar-badge{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 8px;font-size:.78rem;font-weight:800}.sar-badge.ready{background:#dcfce7;color:#166534}.sar-badge.review{background:#ffedd5;color:#9a3412}.sar-badge.conflict{background:#fee2e2;color:#991b1b}.sar-change-summary{padding:10px 12px;color:#334155;font-size:.86rem}.sar-change-line{display:flex;gap:7px;align-items:flex-start;margin-bottom:6px}.sar-change-label{font-weight:800;min-width:132px}.sar-change-flow{color:#475569}.sar-old{color:#64748b}.sar-new{color:#166534;font-weight:700}.sar-card-actions{display:flex;gap:8px;justify-content:flex-end;padding:0 12px 12px;margin-top:auto}.sar-review-btn,.sar-tech-btn{border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#334155;padding:5px 9px;font-weight:700}.sar-detail{display:none;border-top:1px solid #edf2f7;padding:10px 12px;background:#fcfcfd}.sar-card.is-open .sar-detail{display:block}.sar-edit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:9px}.sar-edit-field label{display:block;font-weight:700;font-size:.8rem;color:#475569;margin-bottom:3px}.sar-inline-edit{width:100%;border:1px solid #cbd5e1;border-radius:5px;padding:6px 8px;font-size:.86rem;background:#fff}.sar-empty{padding:22px;text-align:center;color:#64748b;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc}.sar-tech-wrap{display:none;margin-top:12px;border:1px dashed #94a3b8;border-radius:8px;background:#f8fafc;padding:10px}.sar-tech-wrap.is-open{display:block}.sar-tech-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:8px}.sar-tech-card{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px;font-size:.78rem;color:#334155}.sar-tech-card code{white-space:normal;word-break:break-word;color:#be185d}.sar-hidden{display:none!important}'
      + '.sar-analysis{border:1px solid #dbe7ef;border-radius:8px;background:#fff;margin:0 0 14px;padding:10px 12px}.sar-analysis-head{display:flex;justify-content:space-between;gap:10px}.sar-analysis-title{font-weight:800;color:#24313a}.sar-analysis-copy{display:block;color:#64748b;font-size:.82rem}.sar-analysis-progress{font-weight:800;color:#2f7d4d}.sar-analysis-bar{height:7px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:9px 0}.sar-analysis-bar span{display:block;height:100%;background:#2f7d4d;width:0}.sar-analysis-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:7px}.sar-analysis-step{border:1px solid #e2e8f0;border-radius:6px;padding:7px;background:#f8fafc}.sar-analysis-step strong{display:block;color:#334155}.sar-analysis-step small{display:block;color:#64748b}.sar-analysis-step.is-running{border-color:#7dd3fc;background:#f0f9ff}.sar-analysis-step.is-done{border-color:#bbf7d0;background:#f0fdf4}.sar-analysis-step.is-error{border-color:#fecaca;background:#fef2f2}.sar-analysis-summary{margin-top:9px;color:#334155;font-size:.84rem}.sar-suggestion-analysis{border-top:1px solid #edf2f7;margin-top:10px;padding-top:10px;color:#334155}'
      + '.semi-auto-review{border-color:#c9ded4;border-top:4px solid #2f7d4d}.sar-head{background:linear-gradient(180deg,#fbfdfc 0%,#f5f8fa 100%)}.sar-title{font-size:1.12rem;letter-spacing:0}.sar-actions .sar-btn-apply:not(:disabled){background:#2f7d4d;color:#fff;border-color:#2f7d4d}.sar-actions .sar-btn-preview{border-color:#86a3b8}.sar-steps{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px}.sar-step{box-shadow:0 1px 2px rgba(15,23,42,.04)}.sar-step.is-active{box-shadow:0 0 0 2px rgba(47,125,77,.12)}.sar-summary-pill{box-shadow:0 1px 2px rgba(15,23,42,.04)}.sar-group{box-shadow:0 4px 14px rgba(15,23,42,.05)}.sar-group-ready .sar-group-head{background:#f0fdf4}.sar-group-review .sar-group-head{background:#fff7ed}.sar-group-conflict .sar-group-head{background:#fef2f2}.sar-card{box-shadow:0 3px 10px rgba(15,23,42,.04);transition:box-shadow .15s ease,transform .15s ease}.sar-card:hover{box-shadow:0 8px 20px rgba(15,23,42,.08);transform:translateY(-1px)}.sar-card.is-ready{border-left:4px solid #2f7d4d}.sar-card.is-review{border-left:4px solid #d97706}.sar-card.is-conflict{border-left:4px solid #b91c1c}.sar-card-check{width:18px;height:18px}.sar-change-heading{font-weight:800;color:#24313a;margin-bottom:7px}.sar-change-line{background:#f8fafc;border:1px solid #edf2f7;border-radius:6px;padding:6px 7px}.sar-review-btn:hover,.sar-tech-btn:hover{background:#eef6f2;border-color:#9bc7b1}.sar-foot{border-bottom-left-radius:8px;border-bottom-right-radius:8px}'
      + '@media(max-width:768px){.sar-head{display:block}.sar-actions{justify-content:flex-start;margin-top:10px}.sar-steps{grid-template-columns:1fr}.sar-card-list{grid-template-columns:1fr}.sar-foot{display:block}.sar-foot label{display:block;margin-top:8px}.sar-change-line{display:block}.sar-change-label{min-width:0}.semi-auto-review{margin-left:0;margin-right:0}}';
    $('<style id="semi-auto-review-style"></style>').text(css).appendTo('head');
  }

  function panelHtml(module) {
    var label = MODULES[module].label;
    return ''
      + '<section class="semi-auto-review" id="semiAutoReview-' + module + '" hidden>'
      + '<div class="sar-head"><div><div class="sar-title">Asistente de propuestas - ' + escapeHtml(label) + '</div><div class="sar-meta">Revisa lo que el sistema encontró, confirma lo que está listo y aplica solo los cambios seleccionados.</div></div>'
      + '<div class="sar-actions"><button type="button" class="btn-pdc-modern sar-btn-preview"><i class="fas fa-search"></i> Analizar propuestas</button><button type="button" class="btn-pdc-modern sar-btn-apply" disabled><i class="fas fa-magic"></i> Aplicar 0 cambios seleccionados</button><button type="button" class="btn-pdc-modern sar-btn-undo" disabled><i class="fas fa-undo"></i> Deshacer última aplicación</button><button type="button" class="btn btn-sm btn-light sar-btn-close">Cerrar</button></div></div>'
      + '<div class="sar-body">' + stepHtml('analyze') + '<div class="sar-analysis-wrap"></div><div class="sar-filters"><select class="sar-filter-band"><option value="">Todas las propuestas</option><option value="ready">Listas para aplicar</option><option value="review">Requieren revisión</option><option value="conflict">Conflictos</option></select><input class="sar-filter-text" type="search" placeholder="Buscar por paquete o actividad"></div><div class="sar-summary"><span class="sar-summary-pill">Aún no se ha analizado</span></div><div class="sar-groups"><div class="sar-empty">Presiona “Analizar propuestas” para empezar.</div></div><div class="sar-tech-wrap"></div></div>'
      + '<div class="sar-foot"><span class="sar-status">Pendiente de análisis.</span><label class="mb-0"><input type="checkbox" class="sar-check-all"> Seleccionar propuestas visibles</label></div>'
      + '</section>';
  }

  function stepHtml(active) {
    var steps = [
      ['analyze', 'Analizar', 'Buscar propuestas'],
      ['review', 'Revisar', 'Confirmar cambios'],
      ['apply', 'Aplicar', 'Guardar selección']
    ];
    return '<div class="sar-steps">' + steps.map(function (step, index) {
      return '<div class="sar-step ' + (step[0] === active ? 'is-active' : '') + '"><span class="sar-step-num">' + (index + 1) + '</span><div><span class="sar-step-title">' + step[1] + '</span><span class="sar-step-copy">' + step[2] + '</span></div></div>';
    }).join('') + '</div>';
  }

  function init(options) {
    ensureStyles();
    var module = options.module;
    if (!MODULES[module]) return null;
    var id = 'semiAutoReview-' + module;
    var $panel = $('#' + id);
    if (!$panel.length) {
      $panel = $(panelHtml(module));
      var $anchor = $(options.anchorSelector || '#cuadroTabla').first();
      if ($anchor.length) {
        var $rowAnchor = $anchor.closest('.row');
        if ($rowAnchor.length) $rowAnchor.after($panel);
        else $anchor.after($panel);
      }
      else $('body').append($panel);
    }
    instances[module] = { module: module, panel: $panel, runId: null, refresh: options.refresh || function () {}, selectedIds: {}, pollTimer: null };
    bind(instances[module]);
    return instances[module];
  }

  function bind(instance) {
    var $panel = instance.panel;
    $panel.off('.semiAuto');
    $panel.on('click.semiAuto', '.sar-btn-close', function () { stopStatusPolling(instance); $panel.attr('hidden', true); });
    $panel.on('click.semiAuto', '.sar-btn-preview', function () { loadPreview(instance); });
    $panel.on('click.semiAuto', '.sar-btn-apply', function () { applySelected(instance); });
    $panel.on('click.semiAuto', '.sar-btn-undo', function () { undoRun(instance); });
    $panel.on('click.semiAuto', '.sar-review-btn', function () {
      $(this).closest('.sar-card').toggleClass('is-open');
      $(this).text($(this).closest('.sar-card').hasClass('is-open') ? 'Ocultar revisión' : 'Revisar');
    });
    $panel.on('click.semiAuto', '.sar-tech-btn', function () {
      var $tech = $panel.find('.sar-tech-wrap');
      $tech.toggleClass('is-open');
      $(this).text($tech.hasClass('is-open') ? 'Ocultar detalle técnico' : 'Detalle técnico');
    });
    $panel.on('change.semiAuto', '.sar-check-all', function () {
      $panel.find('.sar-row-check:not(:disabled)').prop('checked', this.checked).trigger('change');
    });
    $panel.on('change.semiAuto', '.sar-row-check', function () {
      instance.selectedIds[this.value] = this.checked;
      syncApplyState(instance);
    });
    $panel.on('change.semiAuto keyup.semiAuto', '.sar-filter-band,.sar-filter-text', function () {
      renderSuggestions(instance, instance.lastResponse || { suggestions: [] });
    });
    $panel.on('change.semiAuto', '.sar-inline-edit', function () {
      saveInlineFeedback(instance, this);
    });
  }

  function open(module) {
    var instance = instances[module] || init({ module: module });
    if (!instance) return;
    instance.panel.removeAttr('hidden');
    loadPreview(instance);
    $('html, body').animate({ scrollTop: instance.panel.offset().top - 80 }, 200);
  }

  function loadPreview(instance) {
    stopStatusPolling(instance);
    instance.runId = newRunId();
    instance.selectedIds = {};
    setStatus(instance, 'Analizando propuestas...', '');
    instance.panel.removeAttr('hidden').find('.sar-btn-preview').prop('disabled', true);
    instance.panel.find('.sar-body .sar-steps').replaceWith(stepHtml('analyze'));
    renderAnalysisProcess(instance, initialAnalysis());
    startStatusPolling(instance);
    $.ajax({
      method: 'POST',
      url: endpoint(instance.module, 'preview'),
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ run_id: instance.runId })
    })
      .done(function (response) {
        if (!response || response.respuesta !== 'BIEN') {
          renderError(instance, (response && response.mensaje) || 'No se pudo generar el análisis.');
          return;
        }
        instance.runId = response.run_id;
        instance.selectedIds = {};
        (response.suggestions || []).forEach(function (s) {
          instance.selectedIds[s.suggestion_id] = !!s.preselected && classifySuggestion(s) === 'ready';
        });
        renderAnalysisProcess(instance, response.analysis || {});
        renderSuggestions(instance, response);
        setStatus(instance, 'Análisis listo. Revisa las propuestas antes de aplicar.', 'sar-ok');
      })
      .fail(function (xhr) {
        renderError(instance, (xhr.responseJSON && xhr.responseJSON.mensaje) || 'No se pudo conectar con el servidor.');
      })
      .always(function () {
        stopStatusPolling(instance);
        instance.panel.find('.sar-btn-preview').prop('disabled', false);
      });
  }

  function newRunId() {
    if (window.crypto && window.crypto.getRandomValues) {
      var bytes = new Uint8Array(8);
      window.crypto.getRandomValues(bytes);
      return 'run_' + Array.prototype.map.call(bytes, function (b) {
        return ('0' + b.toString(16)).slice(-2);
      }).join('');
    }
    return 'run_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
  }

  function startStatusPolling(instance) {
    if (!instance.runId) return;
    instance.pollTimer = window.setInterval(function () {
      $.ajax({
        method: 'POST',
        url: endpoint(instance.module, 'status'),
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({ run_id: instance.runId })
      }).done(function (response) {
        if (!response || response.respuesta !== 'BIEN') return;
        renderAnalysisProcess(instance, response);
        if (response.status === 'previewed' || response.status === 'error') {
          stopStatusPolling(instance);
        }
      });
    }, 700);
  }

  function stopStatusPolling(instance) {
    if (instance.pollTimer) {
      window.clearInterval(instance.pollTimer);
      instance.pollTimer = null;
    }
  }

  function initialAnalysis() {
    return {
      progress: 1,
      active_step: 'context',
      steps: [
        { id: 'context', label: 'Contexto', description: 'Proyecto y semana', status: 'running', message: 'Preparando análisis.' },
        { id: 'data', label: 'Datos', description: 'Filas de origen', status: 'pending' },
        { id: 'rules', label: 'Reglas', description: 'Criterios disponibles', status: 'pending' },
        { id: 'matches', label: 'Coincidencias', description: 'Cruces y familias', status: 'pending' },
        { id: 'suggestions', label: 'Propuestas', description: 'Cambios sugeridos', status: 'pending' },
        { id: 'stored', label: 'Preview', description: 'Guardado para revisión', status: 'pending' }
      ],
      summary: {}
    };
  }

  function renderAnalysisProcess(instance, analysis) {
    var progress = Math.max(0, Math.min(100, parseInt(analysis.progress || 0, 10)));
    var steps = analysis.steps || [];
    var html = '<div class="sar-analysis"><div class="sar-analysis-head"><div>'
      + '<span class="sar-analysis-title">Proceso de análisis</span>'
      + '<span class="sar-analysis-copy">Origen, reglas, coincidencias y propuestas revisadas por el sistema.</span>'
      + '</div><span class="sar-analysis-progress">' + progress + '%</span></div>'
      + '<div class="sar-analysis-bar"><span style="width:' + progress + '%"></span></div>'
      + '<div class="sar-analysis-steps">' + steps.map(analysisStepHtml).join('') + '</div>'
      + analysisSummaryHtml(analysis.summary || {}) + '</div>';
    instance.panel.find('.sar-analysis-wrap').html(html);
  }

  function analysisStepHtml(step) {
    var status = step.status || 'pending';
    var counts = step.counts || {};
    var countText = Object.keys(counts).map(function (key) {
      return humanizeField(key) + ': ' + counts[key];
    }).join(' · ');
    return '<div class="sar-analysis-step is-' + escapeHtml(status) + '">'
      + '<strong>' + escapeHtml(step.label || step.id || 'Paso') + '</strong>'
      + '<small>' + escapeHtml(step.message || step.description || '') + '</small>'
      + (countText ? '<small>' + escapeHtml(countText) + '</small>' : '')
      + '</div>';
  }

  function analysisSummaryHtml(summary) {
    if (!summary || !Object.keys(summary).length) return '';
    var message = summary.message || '';
    var total = summary.total_suggestions;
    var preselected = summary.preselected;
    var parts = [];
    if (typeof total !== 'undefined') parts.push(total + ' propuestas');
    if (typeof preselected !== 'undefined') parts.push(preselected + ' preseleccionadas');
    return '<div class="sar-analysis-summary">'
      + (message ? escapeHtml(message) : '')
      + (parts.length ? ' <strong>' + escapeHtml(parts.join(' · ')) + '</strong>' : '')
      + '</div>';
  }

  function renderSuggestions(instance, response) {
    instance.lastResponse = response;
    var suggestions = (response.suggestions || []).map(enrichSuggestion);
    var visible = filterSuggestions(instance, suggestions);
    var counts = countGroups(suggestions);
    instance.panel.find('.sar-body .sar-steps').replaceWith(stepHtml(suggestions.length ? 'review' : 'analyze'));
    instance.panel.find('.sar-summary').html(summaryHtml(suggestions.length, visible.length, counts));
    instance.panel.find('.sar-btn-undo').prop('disabled', !response.run_id);

    var html = ['ready', 'review', 'conflict'].map(function (group) {
      var items = visible.filter(function (s) { return s.group === group; });
      return groupHtml(instance, group, items);
    }).join('');

    instance.panel.find('.sar-groups').html(html || '<div class="sar-empty">' + escapeHtml(MODULES[instance.module].empty) + '</div>');
    renderTech(instance, suggestions);
    syncApplyState(instance);
  }

  function enrichSuggestion(suggestion) {
    var copy = $.extend(true, {}, suggestion);
    copy.group = classifySuggestion(copy);
    copy.visibleDiff = (copy.diff || []).filter(function (d) {
      return d && d.field && !TECHNICAL_FIELDS[d.field];
    });
    return copy;
  }

  function classifySuggestion(suggestion) {
    var action = String(suggestion.action || '');
    if (action.indexOf('review_no_match') === 0 || !suggestion.diff || !suggestion.diff.length || suggestion.confidence_band === 'low') {
      return 'conflict';
    }
    if (suggestion.preselected && suggestion.confidence_band === 'high') {
      return 'ready';
    }
    return 'review';
  }

  function filterSuggestions(instance, suggestions) {
    var group = instance.panel.find('.sar-filter-band').val();
    var text = normalizeText(instance.panel.find('.sar-filter-text').val());
    return suggestions.filter(function (s) {
      if (group && s.group !== group) return false;
      if (!text) return true;
      return normalizeText([s.title, s.subtitle, humanReason(s), readableChangesText(s)].join(' ')).indexOf(text) !== -1;
    });
  }

  function countGroups(suggestions) {
    return suggestions.reduce(function (counts, s) {
      counts[s.group] = (counts[s.group] || 0) + 1;
      return counts;
    }, { ready: 0, review: 0, conflict: 0 });
  }

  function summaryHtml(total, visible, counts) {
    return ''
      + '<span class="sar-summary-pill">Encontramos ' + total + ' propuestas</span>'
      + '<span class="sar-summary-pill ready">' + counts.ready + ' listas</span>'
      + '<span class="sar-summary-pill review">' + counts.review + ' por revisar</span>'
      + '<span class="sar-summary-pill conflict">' + counts.conflict + ' conflictos</span>'
      + '<span class="sar-summary-pill">' + visible + ' visibles</span>';
  }

  function groupHtml(instance, group, items) {
    var meta = GROUPS[group];
    return ''
      + '<section class="sar-group sar-group-' + group + '">'
      + '<div class="sar-group-head"><div><div class="sar-group-title"><i class="fas ' + meta.icon + '"></i> ' + meta.title + '</div><div class="sar-group-desc">' + meta.description + '</div></div><span class="sar-group-count">' + items.length + '</span></div>'
      + '<div class="sar-card-list">' + (items.length ? items.map(function (s) { return cardHtml(instance, s); }).join('') : '<div class="sar-empty">Sin propuestas en este grupo.</div>') + '</div>'
      + '</section>';
  }

  function cardHtml(instance, suggestion) {
    var disabled = suggestion.group === 'conflict';
    var checked = !!instance.selectedIds[suggestion.suggestion_id] && !disabled;
    var changes = visibleChanges(suggestion);
    return ''
      + '<article class="sar-card is-' + suggestion.group + '" data-suggestion-id="' + escapeHtml(suggestion.suggestion_id) + '">'
      + '<div class="sar-card-top">'
      + '<input type="checkbox" class="sar-row-check sar-card-check" value="' + escapeHtml(suggestion.suggestion_id) + '"' + (checked ? ' checked' : '') + (disabled ? ' disabled' : '') + '>'
      + '<div class="sar-card-main">'
      + '<div class="sar-card-title-row"><span class="sar-card-title">' + escapeHtml(actionLabel(suggestion)) + '</span>' + badgeHtml(suggestion.group) + '</div>'
      + '<div class="sar-card-subtitle">' + escapeHtml(suggestion.subtitle || suggestion.title || 'Sin nombre') + '</div>'
      + '<div class="sar-card-message">' + escapeHtml(humanReason(suggestion)) + '</div>'
      + '</div></div>'
      + '<div class="sar-change-summary"><div class="sar-change-heading">Cambios propuestos</div>' + changeSummaryHtml(changes) + '</div>'
      + '<div class="sar-detail">' + suggestionAnalysisHtml(suggestion) + editFieldsHtml(suggestion, changes) + '</div>'
      + '<div class="sar-card-actions"><button type="button" class="sar-review-btn">' + (changes.length ? 'Revisar' : 'Ver detalle') + '</button></div>'
      + '</article>';
  }

  function badgeHtml(group) {
    var labels = {
      ready: 'Alta seguridad',
      review: 'Revisar',
      conflict: 'No recomendado'
    };
    return '<span class="sar-badge ' + group + '">' + labels[group] + '</span>';
  }

  function actionLabel(suggestion) {
    return ACTION_LABELS[suggestion.action] || suggestion.title || 'Propuesta';
  }

  function humanReason(suggestion) {
    return ACTION_MESSAGES[suggestion.action] || suggestion.reason || 'Revisa esta propuesta antes de aplicarla.';
  }

  function visibleChanges(suggestion) {
    return (suggestion.visibleDiff || []).slice(0, 8);
  }

  function changeSummaryHtml(changes) {
    if (!changes.length) {
      return '<span class="text-muted">Sin cambios automáticos aplicables. Requiere revisión manual.</span>';
    }
    return changes.slice(0, 3).map(function (change) {
      return '<div class="sar-change-line"><span class="sar-change-label">' + escapeHtml(fieldLabel(change.field)) + '</span><span class="sar-change-flow"><span class="sar-old">' + formatValueForField(change.field, change.from) + '</span> &rarr; <span class="sar-new">' + formatValueForField(change.field, change.to) + '</span></span></div>';
    }).join('') + (changes.length > 3 ? '<div class="text-muted">+' + (changes.length - 3) + ' cambios más para revisar</div>' : '');
  }

  function suggestionAnalysisHtml(suggestion) {
    var user = suggestion.analysis && suggestion.analysis.user ? suggestion.analysis.user : null;
    if (!user) return '';
    return '<div class="sar-suggestion-analysis"><strong>Cómo llegó a esta propuesta</strong>'
      + '<div>Origen: ' + escapeHtml(user.origin || 'Automatización') + '</div>'
      + '<div>Regla: ' + escapeHtml(user.rule || 'Sin regla registrada') + '</div>'
      + '<div>Decisión: ' + escapeHtml(user.decision || humanReason(suggestion)) + '</div>'
      + '<div>Confianza: ' + escapeHtml(String(user.confidence == null ? suggestion.confidence : user.confidence)) + '%</div>'
      + '</div>';
  }

  function editFieldsHtml(suggestion, changes) {
    if (!changes.length) {
      return '<div class="text-muted">Esta propuesta no tiene campos editables desde el asistente.</div>';
    }
    return '<div class="sar-edit-grid">' + changes.map(function (change) {
      return '<div class="sar-edit-field"><label>' + escapeHtml(fieldLabel(change.field)) + '</label>' + editControlHtml(suggestion, change) + '</div>';
    }).join('') + '</div>';
  }

  function editControlHtml(suggestion, change) {
    var field = change.field || '';
    var value = change.to == null ? '' : String(change.to);
    var attrs = ' class="sar-inline-edit" data-suggestion-id="' + escapeHtml(suggestion.suggestion_id) + '" data-field="' + escapeHtml(field) + '"';
    if (field === 'tipoContrato') {
      var options = [
        ['', 'Sin dato'],
        ['MO,S', 'Mano de obra + suministro'],
        ['SI', 'Suministro e instalación'],
        ['S', 'Suministro'],
        ['MO', 'Mano de obra'],
        ['OC', 'Orden de compra']
      ];
      return '<select' + attrs + '>' + options.map(function (option) {
        return '<option value="' + escapeHtml(option[0]) + '"' + (normalizeModality(value) === option[0] ? ' selected' : '') + '>' + escapeHtml(option[1]) + '</option>';
      }).join('') + '</select>';
    }
    return '<input' + attrs + ' value="' + escapeHtml(value) + '">';
  }

  function renderTech(instance, suggestions) {
    var $tech = instance.panel.find('.sar-tech-wrap');
    if (!isAdmin()) {
      $tech.removeClass('is-open').empty();
      instance.panel.find('.sar-tech-btn').remove();
      return;
    }

    if (!instance.panel.find('.sar-tech-btn').length) {
      instance.panel.find('.sar-actions').append('<button type="button" class="btn btn-sm btn-light sar-tech-btn">Detalle técnico</button>');
    }

    var response = instance.lastResponse || {};
    var cards = suggestions.slice(0, 12).map(function (s) {
      var technical = (s.analysis && s.analysis.technical) || {};
      return '<div class="sar-tech-card"><strong>' + escapeHtml(actionLabel(s)) + '</strong><br>'
        + 'run_id: <code>' + escapeHtml(response.run_id || '') + '</code><br>'
        + 'suggestion_id: <code>' + escapeHtml(s.suggestion_id || '') + '</code><br>'
        + 'source: <code>' + escapeHtml(s.match_source || '') + '</code><br>'
        + 'action: <code>' + escapeHtml(s.action || '') + '</code><br>'
        + 'trace: <code>' + escapeHtml(JSON.stringify(technical)) + '</code></div>';
    }).join('');
    var emptyCard = '<div class="sar-tech-card"><strong>Análisis</strong><br>'
      + 'run_id: <code>' + escapeHtml(response.run_id || '') + '</code><br>'
      + 'Sin propuestas técnicas para mostrar.</div>';
    $tech.html('<div class="sar-tech-grid">' + (cards || emptyCard) + '</div>');
  }

  function saveInlineFeedback(instance, input) {
    if (!instance.runId) return;
    var suggestionId = $(input).data('suggestion-id');
    var field = $(input).data('field');
    var corrected = {};
    corrected[field] = $(input).val();
    setStatus(instance, 'Guardando ajuste...', '');
    $.ajax({
      method: 'POST',
      url: endpoint(instance.module, 'feedback'),
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        run_id: instance.runId,
        suggestion_id: suggestionId,
        feedback_type: 'inline_correction',
        corrected: corrected
      })
    }).done(function (response) {
      if (!response || response.respuesta !== 'BIEN') {
        renderError(instance, (response && response.mensaje) || 'No se pudo guardar el ajuste.');
        return;
      }
      updateLocalSuggestion(instance, suggestionId, field, corrected[field]);
      setStatus(instance, response.updated_suggestion ? 'Ajuste guardado.' : 'Comentario registrado.', 'sar-ok');
    }).fail(function (xhr) {
      renderError(instance, (xhr.responseJSON && xhr.responseJSON.mensaje) || 'Error guardando el ajuste.');
    });
  }

  function updateLocalSuggestion(instance, suggestionId, field, value) {
    var suggestions = (instance.lastResponse && instance.lastResponse.suggestions) || [];
    suggestions.forEach(function (s) {
      if (s.suggestion_id !== suggestionId) return;
      (s.diff || []).forEach(function (d) {
        if (d.field === field) d.to = value;
      });
      if (s.proposed) s.proposed[field] = value;
    });
  }

  function applySelected(instance) {
    var ids = instance.panel.find('.sar-row-check:checked').map(function () { return this.value; }).get();
    if (!instance.runId || !ids.length) return;
    setStatus(instance, 'Aplicando cambios seleccionados...', '');
    instance.panel.find('.sar-btn-apply').prop('disabled', true);
    instance.panel.find('.sar-body .sar-steps').replaceWith(stepHtml('apply'));
    $.ajax({
      method: 'POST',
      url: endpoint(instance.module, 'apply'),
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ run_id: instance.runId, suggestion_ids: ids })
    }).done(function (response) {
      if (!response || response.respuesta !== 'BIEN') {
        renderError(instance, (response && response.mensaje) || 'No se pudo aplicar la selección.');
        return;
      }
      setStatus('' + (response.aplicadas || 0) + ' cambios aplicados. Puedes deshacer la última aplicación si lo necesitas.', 'sar-ok', instance);
      instance.refresh();
      loadPreview(instance);
    }).fail(function (xhr) {
      renderError(instance, (xhr.responseJSON && xhr.responseJSON.mensaje) || 'Error de conexión al aplicar.');
    });
  }

  function undoRun(instance) {
    if (!instance.runId) return;
    setStatus(instance, 'Deshaciendo la última aplicación...', '');
    $.ajax({
      method: 'POST',
      url: endpoint(instance.module, 'undo'),
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ run_id: instance.runId })
    }).done(function (response) {
      if (!response || response.respuesta !== 'BIEN') {
        renderError(instance, (response && response.mensaje) || 'No se pudo deshacer.');
        return;
      }
      setStatus(instance, '' + (response.revertidas || 0) + ' cambios revertidos.', 'sar-ok');
      instance.refresh();
      loadPreview(instance);
    }).fail(function (xhr) {
      renderError(instance, (xhr.responseJSON && xhr.responseJSON.mensaje) || 'Error de conexión al deshacer.');
    });
  }

  function syncApplyState(instance) {
    var count = instance.panel.find('.sar-row-check:checked').length;
    instance.panel.find('.sar-btn-apply')
      .prop('disabled', count === 0)
      .html('<i class="fas fa-magic"></i> Aplicar ' + count + ' cambios seleccionados');
    var visibleChecked = instance.panel.find('.sar-row-check:not(:disabled)').length > 0
      && instance.panel.find('.sar-row-check:not(:disabled)').length === instance.panel.find('.sar-row-check:not(:disabled):checked').length;
    instance.panel.find('.sar-check-all').prop('checked', visibleChecked);
  }

  function renderError(instance, message) {
    instance.panel.find('.sar-groups').html('<div class="sar-empty sar-error">' + escapeHtml(message) + '</div>');
    setStatus(instance, message, 'sar-error');
    syncApplyState(instance);
  }

  function setStatus(instanceOrText, textOrCls, clsOrInstance) {
    var instance = typeof instanceOrText === 'object' ? instanceOrText : clsOrInstance;
    var text = typeof instanceOrText === 'object' ? textOrCls : instanceOrText;
    var cls = typeof instanceOrText === 'object' ? clsOrInstance : textOrCls;
    instance.panel.find('.sar-status').removeClass('sar-error sar-ok').addClass(cls || '').text(text || '');
  }

  function fieldLabel(field) {
    if (FIELD_LABELS[field]) return FIELD_LABELS[field];
    var packageMatch = String(field || '').match(/^paquete(SI|S|MO|OC)(\d)$/);
    if (packageMatch) {
      var packageNames = { SI: 'Suministro e instalación', S: 'Suministro', MO: 'Mano de obra', OC: 'Orden de compra' };
      return packageNames[packageMatch[1]] + ' ' + packageMatch[2];
    }
    var resourceMatch = String(field || '').match(/^(SI|S|MO|OC)(\d)$/);
    if (resourceMatch) {
      var resourceNames = { SI: 'Proveedor suministro e instalación', S: 'Proveedor suministro', MO: 'Proveedor mano de obra', OC: 'Proveedor orden de compra' };
      return resourceNames[resourceMatch[1]] + ' ' + resourceMatch[2];
    }
    return humanizeField(field);
  }

  function humanizeField(field) {
    return String(field || '')
      .replace(/_/g, ' ')
      .replace(/([a-z])([A-Z])/g, '$1 $2')
      .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
  }

  function formatValue(value) {
    if (value === null || typeof value === 'undefined' || value === '') return 'Sin dato';
    var text = String(value);
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) {
      var parts = text.split('-');
      return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    if (text === 'SI') return 'Suministro e instalación';
    if (text === 'S') return 'Suministro';
    if (text === 'MO') return 'Mano de obra';
    if (text === 'OC') return 'Orden de compra';
    return escapeHtml(text.length > 80 ? text.slice(0, 77) + '...' : text);
  }

  function formatValueForField(field, value) {
    if (field === 'tipoContrato') {
      var modality = normalizeModality(value);
      var labels = {
        '1': 'Mano de obra + suministro',
        '2': 'Suministro e instalación',
        '3': 'Suministro',
        '4': 'Mano de obra',
        '5': 'Orden de compra',
        SI: 'Suministro e instalación',
        S: 'Suministro',
        MO: 'Mano de obra',
        OC: 'Orden de compra',
        'MO,S': 'Mano de obra + suministro',
        'M_O,S': 'Mano de obra + suministro'
      };
      return escapeHtml(labels[modality] || modality || 'Sin dato');
    }
    return formatValue(value);
  }

  function normalizeModality(value) {
    var modality = String(value == null ? '' : value).trim();
    var aliases = {
      '1': 'MO,S',
      '2': 'SI',
      '3': 'S',
      '4': 'MO',
      '5': 'OC',
      'M_O,S': 'MO,S'
    };
    return aliases[modality] || modality;
  }

  function readableChangesText(suggestion) {
    return (suggestion.visibleDiff || suggestion.diff || []).map(function (d) {
      return fieldLabel(d.field) + ' ' + formatValueForField(d.field, d.from) + ' ' + formatValueForField(d.field, d.to);
    }).join(' ');
  }

  function normalizeText(value) {
    return String(value == null ? '' : value).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
    });
  }

  window.SemiAutoReview = { init: init, open: open };
})(window, window.jQuery);
