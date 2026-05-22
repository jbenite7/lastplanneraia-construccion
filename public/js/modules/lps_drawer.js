/**
 * public/js/modules/lps_drawer.js
 * ================================
 * Cajón Contextual LPS Premium para control de crisis, comentarios en hilos (Slack-style),
 * cálculo del Termómetro Habilitador de Restricciones (ITR) y escalamiento jerárquico SOS.
 */

window.LPSContextualDrawer = (function() {
  let activeHot = null;
  let activeModuleKey = null;
  let activeStateAdapter = null;
  let activeRowIndex = null;
  let activeConsecutivo = null;
  let activeParentId = null;
  let activeAlertaId = null;

  const HARD_RESTRICTIONS = [
    { key: 'D_y_E', aliases: ['D_y_E', 'restr_D_y_E'], label: 'Diseños y Especif.', threshold: 1 },
    { key: 'Materiales', aliases: ['Materiales', 'restr_Materiales'], label: 'Materiales', threshold: 1 },
    { key: 'MdeO', aliases: ['MdeO', 'restr_MdeO'], label: 'Mano de Obra', threshold: 1 },
    { key: 'Equipos', aliases: ['Equipos', 'restr_Equipos'], label: 'Equipos', threshold: 1 },
    { key: 'Predecesora', aliases: ['Predecesora', 'restr_Predecesora'], label: 'Predecesora', threshold: 0.5 }
  ];

  const SOFT_RESTRICTIONS = [
    { key: 'Pdto_Cons', aliases: ['Pdto_Cons', 'restr_Pdto_Cons'], label: 'Procedimiento Constructivo', threshold: 1 },
    { key: 'Modelo', aliases: ['Modelo', 'restr_Modelo'], label: 'Modelación BIM', threshold: 1 }
  ];

  const PG_STATE_LABELS = {
    'debe-iniciar': 'Debe iniciar esta semana',
    'actividad-futura': 'Actividad futura',
    adelantada: 'Adelantada',
    'en-curso': 'En curso',
    'atrasada-critica': 'Atrasada crítica',
    atrasada: 'Atrasada',
    terminada: 'Terminada',
    'no-requerida': 'No requerida',
    header: 'Capítulo'
  };

  const ROUTINE_STATE_KEYS = [
    'liberated-control',
    'neutral',
    'terminada',
    'no-requerida',
    'prog-lista-para-confirmar',
    'cal-cumplida-control',
    'ps-no-activa',
    'header'
  ];

  const WEEKLY_ESCALATION_STATE_KEYS = [
    'prog-bloqueo-critico-sin-compromiso',
    'cal-incumplida-critica'
  ];

  const SEVERITY_VISUALS = {
    critical: {
      badgeClass: 'lps-badge-p1',
      cardClass: 'lps-state-p1',
      label: 'Crítico',
      sidebarClass: 'has-crisis',
      badgeText: '🔥'
    },
    attention: {
      badgeClass: 'lps-badge-p2',
      cardClass: 'lps-state-p2',
      label: 'Atención',
      sidebarClass: 'has-attention',
      badgeText: '!'
    },
    info: {
      badgeClass: 'lps-badge-info',
      cardClass: 'lps-state-info',
      label: 'Info',
      sidebarClass: '',
      badgeText: ''
    },
    neutral: {
      badgeClass: 'lps-badge-neutral',
      cardClass: 'lps-state-neutral',
      label: 'Neutral',
      sidebarClass: '',
      badgeText: ''
    },
    normal: {
      badgeClass: 'lps-badge-p3',
      cardClass: 'lps-state-p3',
      label: 'Control',
      sidebarClass: '',
      badgeText: ''
    }
  };

  // Cargar/Inicializar modo simulación
  if (localStorage.getItem('lps_simulated_mode') === null) {
    localStorage.setItem('lps_simulated_mode', 'true');
  }

  function getSessionContext() {
    const dbEl = document.getElementById('baseDatos_PHP');
    const semEl = document.getElementById('semana_PHP');
    const permEl = document.getElementById('permiso_PHP');
    return {
      dbName: dbEl ? dbEl.value : '',
      semana: semEl ? parseInt(semEl.value, 10) : 0,
      permiso: permEl ? permEl.value : ''
    };
  }

  function bindEvents() {
    const overlay = document.getElementById('lps_drawer_overlay');
    const drawer = document.getElementById('lps_drawer');
    const closeBtn = document.getElementById('lps_drawer_close');
    const toggle = document.getElementById('lps_sim_mode_toggle');
    const sidebarTrigger = document.getElementById('lps_sidebar_trigger');

    if (sidebarTrigger) {
      sidebarTrigger.addEventListener('click', () => {
        if (drawer) {
          if (drawer.classList.contains('open')) {
            drawerClose();
          } else {
            drawerOpen();
          }
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        drawerClose();
      });
    }
    if (overlay) {
      overlay.addEventListener('click', () => {
        drawerClose();
      });
    }

    // Sync toggle modo simulación
    if (toggle) {
      toggle.checked = localStorage.getItem('lps_simulated_mode') === 'true';
      toggle.addEventListener('change', function() {
        localStorage.setItem('lps_simulated_mode', this.checked ? 'true' : 'false');
        showNotification(this.checked ? 'Modo Simulación Activado (Envíos Bloqueados)' : 'Modo Envíos Activos (Notificaciones Reales)');
        refreshDrawerData();
      });
    }

    // Botones de Escalamiento SOS
    const btnWa = document.getElementById('lps_btn_whatsapp');
    const btnEmail = document.getElementById('lps_btn_email');
    if (btnWa) btnWa.addEventListener('click', () => triggerEscalate('whatsapp'));
    if (btnEmail) btnEmail.addEventListener('click', () => triggerEscalate('email'));

    // Botón enviar comentario
    const btnSend = document.getElementById('lps_btn_send_comment');
    if (btnSend) btnSend.addEventListener('click', postComment);

    // Cancelar responder en hilo
    const btnCancelReply = document.getElementById('lps_btn_cancel_reply');
    if (btnCancelReply) {
      btnCancelReply.addEventListener('click', () => {
        activeParentId = null;
        document.getElementById('lps_thread_replying_indicator').style.display = 'none';
      });
    }

    // Validación interactiva de justificación de cierre
    const closureInput = document.getElementById('lps_closure_justification');
    const closureBtn = document.getElementById('lps_btn_close_crisis');
    if (closureInput && closureBtn) {
      closureInput.addEventListener('input', function() {
        const len = this.value.trim().length;
        const counter = document.getElementById('lps_closure_char_count');
        if (counter) {
          counter.textContent = `${len} / 100 caracteres`;
          if (len >= 100) {
            counter.style.color = '#198754';
          } else {
            counter.style.color = '#dc3545';
          }
        }
        closureBtn.disabled = len < 100;
      });

      closureBtn.addEventListener('click', closeCrisisAlert);
    }

    // Digest Semanal
    const btnDigest = document.getElementById('lps_btn_digest');
    const btnCopyDigest = document.getElementById('lps_btn_copy_digest');
    if (btnDigest) btnDigest.addEventListener('click', compileWeeklyDigest);
    if (btnCopyDigest) btnCopyDigest.addEventListener('click', copyDigestToClipboard);
  }

  function showNotification(message) {
    if (window.toastr) {
      window.toastr.info(message);
      return;
    }
    const toast = document.createElement('div');
    toast.style.cssText = `
      position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
      background: rgba(26, 60, 42, 0.96); color: #ffffff; padding: 10px 20px;
      border-radius: 8px; font-size: 0.85rem; font-weight: 600; z-index: 99999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15); pointer-events: none; transition: opacity 0.3s;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 2500);
  }

  function isBlankValue(value) {
    if (value === null || value === undefined) return true;
    const text = String(value).trim();
    return text === '' || text.toLowerCase() === 'null';
  }

  function firstValue(rowData, keys) {
    if (!rowData) return undefined;
    for (let i = 0; i < keys.length; i++) {
      const value = rowData[keys[i]];
      if (!isBlankValue(value)) return value;
    }
    return undefined;
  }

  function firstExistingValue(rowData, keys) {
    if (!rowData) return { found: false, value: undefined, key: '' };
    let blankCandidate = null;
    for (let i = 0; i < keys.length; i++) {
      const key = keys[i];
      if (!Object.prototype.hasOwnProperty.call(rowData, key)) continue;
      const value = rowData[key];
      if (!isBlankValue(value)) return { found: true, value, key };
      if (blankCandidate === null) blankCandidate = { found: true, value, key };
    }
    return blankCandidate || { found: false, value: undefined, key: '' };
  }

  function normalizeNumericString(value) {
    let normalized = String(value === null || value === undefined ? '' : value).trim().replace(/\s+/g, '');
    if (!normalized || normalized.toLowerCase() === 'null') return '';

    const commaPos = normalized.lastIndexOf(',');
    const dotPos = normalized.lastIndexOf('.');
    if (commaPos > -1 && dotPos > -1) {
      normalized = commaPos > dotPos
        ? normalized.replace(/\./g, '').replace(',', '.')
        : normalized.replace(/,/g, '');
    } else if (commaPos > -1) {
      normalized = normalized.replace(',', '.');
    }

    return normalized;
  }

  function parseNumber(value, fallback) {
    if (isBlankValue(value)) return fallback;
    const parsed = parseFloat(normalizeNumericString(value));
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function isNotApplicable(value) {
    const normalized = String(value === null || value === undefined ? '' : value).trim().toUpperCase();
    return normalized === 'N/A' || normalized === 'NA' || normalized === 'NO APLICA';
  }

  function parseRatioValue(value) {
    if (isBlankValue(value) || isNotApplicable(value)) return null;

    const raw = String(value).trim();
    const hasPercent = raw.indexOf('%') > -1;
    const normalized = normalizeNumericString(raw.replace(/%/g, ''));
    if (!normalized) return null;

    let ratio = parseFloat(normalized);
    if (!Number.isFinite(ratio)) return null;
    if (hasPercent) ratio = ratio / 100;
    while (ratio > 1 && ratio <= 10000) ratio = ratio / 100;
    if (ratio < 0) return 0;
    if (ratio > 1) return 1;
    return Math.round((ratio + Number.EPSILON) * 10000) / 10000;
  }

  function formatPercentFromRatio(ratio) {
    if (ratio === null || ratio === undefined || !Number.isFinite(Number(ratio))) return '0%';
    return `${Math.round(Number(ratio) * 100)}%`;
  }

  function normalizeFlag(value) {
    if (value === true) return true;
    if (value === false || value === null || value === undefined) return false;
    if (typeof value === 'number') return value >= 1;
    const normalized = String(value).trim().toLowerCase();
    return normalized === '1' || normalized === 'si' || normalized === 'sí' || normalized === 'true' || normalized === 'p1';
  }

  function isCriticalRoute(rowData) {
    if (!rowData) return false;
    const priority = String(firstValue(rowData, ['prioridad', 'Prioridad']) || '').trim().toUpperCase();
    const flagKeys = ['Ruta_Critica', 'ruta_critica', 'Critica', 'critica', 'p1', 'P1'];
    return priority === 'P1' || flagKeys.some(key => normalizeFlag(rowData[key]));
  }

  function isHeaderRow(rowData, stateView) {
    if (!rowData) return false;
    if (Number(rowData.Titulo) !== 0 && rowData.Titulo !== undefined && rowData.Titulo !== null && rowData.Titulo !== '') return true;
    const stateKey = getStateKey(stateView);
    return stateKey === 'header';
  }

  function getCanonicalConsecutivo(rowData) {
    return firstValue(rowData, [
      'Consecutivo_en_Programa',
      'Consecutivo_En_Programa',
      'consecutivo_en_programa',
      'Consecutivo',
      'Id',
      'id'
    ]) || 'N/A';
  }

  function getActivityTitle(rowData) {
    return firstValue(rowData, ['Actividad', 'nombre', 'Nombre']) || 'Tarea sin nombre';
  }

  function getPlainText(value) {
    const container = document.createElement('div');
    container.innerHTML = String(value === null || value === undefined ? '' : value);
    return String(container.textContent || container.innerText || '').trim();
  }

  function getSubcontractor(rowData) {
    return firstValue(rowData, ['Sub_Contratista', 'Subcontratista', 'subcontratista', 'responsable']) || 'Sin Asignar';
  }

  function getResponsible(rowData) {
    return firstValue(rowData, ['Responsable_AIA', 'Responsable', 'responsable_aia', 'responsable']) || 'Sin Asignar';
  }

  function getRestrictionSummary(rowData, itr) {
    const explicit = firstValue(rowData, ['Restriccion', 'causa_no_cumplimiento', 'CNC', 'CNP']);
    if (explicit) return explicit;

    const pending = (itr && Array.isArray(itr.items) ? itr.items : [])
      .filter(item => item.applicable && !item.met)
      .map(item => `${item.label} ${formatPercentFromRatio(item.ratio || 0)}`);
    return pending.length ? pending.join(', ') : 'Sin restricciones habilitantes pendientes';
  }

  function getRestrictionInfo(rowData, config) {
    const candidate = firstExistingValue(rowData, config.aliases);
    const raw = candidate.value;
    if (!candidate.found || isNotApplicable(raw)) {
      return {
        key: config.key,
        label: config.label,
        raw,
        ratio: null,
        threshold: config.threshold,
        applicable: false,
        met: true,
        progress: 1
      };
    }

    const ratio = isBlankValue(raw) ? 0 : parseRatioValue(raw);
    const numericRatio = ratio === null ? 0 : ratio;
    const progress = Math.max(0, Math.min(numericRatio / config.threshold, 1));
    return {
      key: config.key,
      label: config.label,
      raw,
      ratio: numericRatio,
      threshold: config.threshold,
      applicable: true,
      met: numericRatio + 0.0001 >= config.threshold,
      progress
    };
  }

  function getModuleStateView(rowData) {
    if (typeof activeStateAdapter !== 'function') return null;
    try {
      return activeStateAdapter(rowData) || null;
    } catch (err) {
      console.warn('No se pudo resolver el estado operativo LPS:', err);
      return null;
    }
  }

  function getStateKey(stateView) {
    if (!stateView) return '';
    return String(stateView.state || stateView.key || '').trim();
  }

  function getStateLabel(rowData, stateView) {
    if (stateView && stateView.label) return stateView.label;
    const stateKey = getStateKey(stateView);
    if (PG_STATE_LABELS[stateKey]) return PG_STATE_LABELS[stateKey];
    const rawDisplay = firstValue(rowData, ['estado_operativo', 'Estado']);
    if (rawDisplay) return String(rawDisplay).split('\n')[0].trim();
    return 'Control';
  }

  function getStateActions(stateView) {
    if (!stateView) return [];
    if (Array.isArray(stateView.actions)) return stateView.actions.filter(Boolean);
    if (Array.isArray(stateView.actionItems)) {
      return stateView.actionItems.map(item => item && item.text).filter(Boolean);
    }
    if (Array.isArray(stateView.compactItems)) {
      return stateView.compactItems.map(item => item && item.text).filter(Boolean);
    }
    return [];
  }

  function isRoutineState(stateKey) {
    return !stateKey || ROUTINE_STATE_KEYS.indexOf(stateKey) > -1;
  }

  function isWeeklyModule(context) {
    return context && context.moduleKey === 'programacion-semanal';
  }

  function getSeverityVisualState(severity) {
    return SEVERITY_VISUALS[severity] || SEVERITY_VISUALS.normal;
  }

  function hasDeepRestrictionGap(itr) {
    const items = itr && Array.isArray(itr.items) ? itr.items : [];
    return items.some(item => {
      if (!item || !item.applicable || item.met) return false;
      const ratio = Number(item.ratio || 0);
      return item.key === 'Predecesora' ? ratio < 0.5 : ratio < 0.66;
    });
  }

  function getStateItems(context) {
    const view = context && context.stateView ? context.stateView : {};
    const items = [];
    if (Array.isArray(view.actionItems)) items.push.apply(items, view.actionItems);
    if (Array.isArray(view.compactItems)) items.push.apply(items, view.compactItems);
    return items.filter(Boolean);
  }

  function hasStateItemStatus(context, statuses) {
    const allowed = Array.isArray(statuses) ? statuses : [];
    return getStateItems(context).some(item => allowed.indexOf(String(item.status || '').trim()) > -1);
  }

  function getWeeklySeverity(context) {
    const state = context.stateKey;
    if (state === 'prog-bloqueo-critico-sin-compromiso' || state === 'cal-incumplida-critica') return 'critical';
    if (state === 'prog-ejecucion-con-restricciones') return context.isCritical ? 'critical' : 'attention';
    if (state === 'cal-incumplida' || state === 'cal-sin-calificar' || state === 'prog-condiciones-pendientes' || state === 'prog-sin-compromiso') return 'attention';
    if (hasStateItemStatus(context, ['critical'])) return 'critical';
    if (hasStateItemStatus(context, ['pending', 'partial', 'conflict'])) return 'attention';
    if (state === 'prog-lista-para-confirmar' || state === 'cal-cumplida-control') return 'normal';
    if (state === 'ps-no-activa') return 'neutral';
    return 'normal';
  }

  function getPlanSeverity(context) {
    const weeks = context.semanasInicio;
    const state = context.stateKey;

    if (state === 'header') return 'neutral';
    if (state === 'no-requerida' || state === 'neutral') return 'neutral';
    if (state === 'terminada' || state === 'adelantada') return 'normal';
    if (state === 'atrasada-critica') return 'critical';
    if (state === 'atrasada' || state === 'blocked-overdue') return 'attention';
    if (state === 'blocked-overdue-critical') return 'critical';
    if (state === 'execution-blocked') return context.isCritical ? 'critical' : 'attention';

    if (context.isStartedByProgress && !context.isLiberada) {
      return context.isCritical && context.isDueOrOverdue ? 'critical' : 'attention';
    }

    if (weeks !== null && weeks <= 0 && !context.isLiberada && context.progressRatio < 0.999) {
      return context.isCritical ? 'critical' : 'attention';
    }

    if (weeks !== null && weeks <= 0 && context.isLiberada && !context.isStartedByProgress) return 'attention';
    if (weeks === 1 && !context.isLiberada) return 'attention';

    if (weeks !== null && weeks >= 2 && weeks <= 3 && !context.isLiberada) {
      return context.deepGap ? 'attention' : 'normal';
    }

    if (weeks !== null && weeks >= 4 && weeks <= 6 && !context.isLiberada) return 'info';
    if (!context.isLiberada && context.isActionableState) return 'attention';
    if (context.isLiberada || state === 'en-curso' || state === 'liberated-control') return 'normal';

    return 'neutral';
  }

  function getDrawerSeverity(context) {
    if (!context || context.isHeader) return 'neutral';
    if (context.isSOS) return 'critical';
    if (context.moduleKey === 'programacion-semanal') return getWeeklySeverity(context);
    return getPlanSeverity(context);
  }

  function shouldShowEscalation(context) {
    if (!context || context.isHeader) return false;
    return context.severity === 'critical';
  }

  function getWeeklyCommitmentGaps(rowData) {
    const gaps = [];
    const compromiso = parseNumber(firstValue(rowData, ['Compromiso']), null);
    if (compromiso === null || compromiso <= 0) gaps.push('definir compromiso mayor a cero');
    if (isBlankValue(firstValue(rowData, ['Responsable_AIA', 'Responsable']))) gaps.push('asignar Responsable AIA');
    if (isBlankValue(firstValue(rowData, ['Sub_Contratista', 'Subcontratista']))) gaps.push('asignar Sub-Contratista');
    return gaps;
  }

  function getProgressRatio(rowData) {
    return parseRatioValue(firstValue(rowData, ['Ejecutado', 'ejecutado'])) || 0;
  }

  function getProgressDisplay(rowData) {
    const ratio = getProgressRatio(rowData);
    const unit = String(firstValue(rowData, ['unidad', 'Unidad']) || '%').trim() || '%';
    const display = firstValue(rowData, ['EjecutadoDisplay']);
    const quantity = parseNumber(display, null);

    if (quantity !== null && unit !== '%') {
      return `${quantity.toLocaleString('es-CO', { maximumFractionDigits: 1 })} ${unit} (${formatPercentFromRatio(ratio)})`;
    }

    return formatPercentFromRatio(ratio);
  }

  function getActiveRowData() {
    if (!activeHot || activeRowIndex === null) return null;
    const physicalRow = typeof activeHot.toPhysicalRow === 'function'
      ? activeHot.toPhysicalRow(activeRowIndex)
      : activeRowIndex;
    if (!Number.isInteger(physicalRow) || physicalRow < 0) return null;
    return activeHot.getSourceDataAtRow(physicalRow) || null;
  }

  function hideActivityCards() {
    ['lps_itr_card', 'lps_action_card', 'lps_comments_card', 'lps_closure_card', 'lps_sim_clipboard_card'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });
  }

  function buildDrawerContext(rowData, moduleKey) {
    const stateView = getModuleStateView(rowData);
    const stateKey = getStateKey(stateView);
    const itr = calculateITR(rowData);
    const deepGap = hasDeepRestrictionGap(itr);
    const semanasInicio = parseNumber(firstValue(rowData, ['Semanas_Inicio', 'semanas_inicio']), null);
    const progressRatio = getProgressRatio(rowData);
    const isSOS = parseInt(rowData.alerta_crisis, 10) === 1 || rowData.alerta_crisis === true;
    const isCritical = isCriticalRoute(rowData);
    const isLiberada = itr.isComplete;
    const isStartedByProgress = progressRatio > 0.001;
    const isDueOrOverdue = semanasInicio !== null && semanasInicio <= 0;
    const atraso = parseNumber(firstValue(rowData, ['atraso', 'Atraso']), 0);
    const isActionableState = !isRoutineState(stateKey);

    const context = {
      moduleKey,
      rowData,
      stateView,
      stateKey,
      stateLabel: getStateLabel(rowData, stateView),
      stateActions: getStateActions(stateView),
      itr,
      consecutivo: getCanonicalConsecutivo(rowData),
      actividad: getActivityTitle(rowData),
      actividadTexto: getPlainText(getActivityTitle(rowData)),
      subcontratista: getSubcontractor(rowData),
      responsable: getResponsible(rowData),
      semanasInicio,
      progressRatio,
      progressDisplay: getProgressDisplay(rowData),
      isHeader: isHeaderRow(rowData, stateView),
      isCritical,
      isLiberada,
      deepGap,
      isStartedByProgress,
      isDueOrOverdue,
      isStartOverdue: semanasInicio !== null && semanasInicio < 0 && !isStartedByProgress,
      isSOS,
      isPredictiveCrisis: false,
      isReactiveCrisis: false,
      isCrisis: false,
      isActionableState,
      phase: stateView && stateView.phase ? stateView.phase : null
    };

    context.severity = getDrawerSeverity(context);
    context.severityVisual = getSeverityVisualState(context.severity);
    context.isCrisis = context.severity === 'critical';
    context.isPredictiveCrisis = context.isCrisis && context.moduleKey !== 'programacion-semanal' && !context.isLiberada;
    context.isReactiveCrisis = context.isCrisis && context.moduleKey !== 'programacion-semanal' && context.isLiberada && context.isStartedByProgress && context.isDueOrOverdue && (atraso >= 10 || semanasInicio <= -2 || stateKey === 'atrasada-critica');

    return context;
  }

  function calculateITR(rowData) {
    const items = HARD_RESTRICTIONS.map(config => getRestrictionInfo(rowData, config));
    const applicableItems = items.filter(item => item.applicable);
    const aplicables = applicableItems.length;
    const liberadas = applicableItems.filter(item => item.met).length;
    const computedRatio = aplicables > 0
      ? applicableItems.reduce((sum, item) => sum + item.progress, 0) / aplicables
      : 1.0;
    const aggregateRatio = parseRatioValue(firstValue(rowData, ['Estado_Restricciones', 'estado_restricciones']));
    const porcentaje = aplicables > 0 ? computedRatio : (aggregateRatio !== null ? aggregateRatio : computedRatio);

    return {
      porcentaje: Math.round(porcentaje * 100),
      ratio: porcentaje,
      liberadas,
      aplicables,
      isComplete: aplicables === 0 ? porcentaje >= 0.999 : liberadas === aplicables,
      items
    };
  }

  function updateITRVisuals(itr, rowData) {
    const card = document.getElementById('lps_itr_card');
    const bar = document.getElementById('lps_itr_bar');
    const valText = document.getElementById('lps_itr_value');
    const details = document.getElementById('lps_itr_details');

    if (!card) return;
    card.style.display = 'block';

    if (bar) {
      bar.style.width = `${itr.porcentaje}%`;
      // Gradiente o colores según el porcentaje
      if (itr.porcentaje >= 80) {
        bar.style.background = '#198754'; // Verde
      } else if (itr.porcentaje >= 50) {
        bar.style.background = '#ffc107'; // Amarillo
      } else {
        bar.style.background = '#dc3545'; // Rojo
      }
    }
    if (valText) valText.textContent = `${itr.porcentaje}%`;
    if (details) {
      details.textContent = `${itr.liberadas} de ${itr.aplicables} restricciones habilitantes en umbral. Liberación ponderada: ${itr.porcentaje}%.`;
    }

    // Buscar o crear contenedor de restricciones blandas en lps_itr_card
    let softContainer = document.getElementById('lps_soft_restrictions_container');
    if (!softContainer && card) {
      softContainer = document.createElement('div');
      softContainer.id = 'lps_soft_restrictions_container';
      softContainer.style.cssText = 'margin-top: 10px; border-top: 1px dashed rgba(26, 60, 42, 0.15); padding-top: 8px;';
      card.appendChild(softContainer);
    }

    if (softContainer && rowData) {
      softContainer.innerHTML = '';
      const softRestrictions = SOFT_RESTRICTIONS;

      let hasSoft = false;
      let html = '<div style="font-size: 0.75rem; font-weight: 700; color: #1a3c2a; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.3px;">Restricciones Blandas (Informativas)</div><div style="display:flex; flex-direction:column; gap:4px;">';

      softRestrictions.forEach(r => {
        const val = firstValue(rowData, r.aliases);
        if (val !== undefined && val !== null) {
          const strVal = String(val).trim().toUpperCase();
          if (strVal !== 'N/A' && strVal !== 'NA' && strVal !== '') {
            hasSoft = true;
            const ratio = parseRatioValue(strVal);
            const percent = ratio === null ? 0 : Math.round(ratio * 100);

            let badgeColor = '#dc3545'; // Rojo
            if (percent >= 100) {
              badgeColor = '#198754'; // Verde
            } else if (percent > 0) {
              badgeColor = '#ffc107'; // Amarillo
            }

            html += `
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#495057;">
                <span>${r.label}:</span>
                <span class="lps-badge" style="background:${badgeColor}; color:${percent > 0 && percent < 100 ? '#212529' : '#fff'}; padding:2px 6px; font-size:0.68rem; border-radius:4px; font-weight:700;">${percent}%</span>
              </div>
            `;
          }
        }
      });

      html += '</div>';

      if (hasSoft) {
        softContainer.innerHTML = html;
        softContainer.style.display = 'block';
      } else {
        softContainer.style.display = 'none';
      }
    }
  }

  function refreshDrawerData() {
    if (activeConsecutivo === null) return;
    loadCommentsAndCrisis();
  }

  function loadCommentsAndCrisis() {
    const container = document.getElementById('lps_comments_container');
    if (container) {
      container.innerHTML = '<div style="font-size:0.8rem; color:#666;">Cargando bitácora de hilos...</div>';
    }

    fetch(`/api/lps/comments?consecutivo=${activeConsecutivo}`)
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          renderCommentsTree(response.data);
          detectActiveCrisis(response.data);
        } else {
          if (container) container.innerHTML = `<div style="color:#dc3545; font-size:0.8rem;">Error: ${response.mensaje}</div>`;
        }
      })
      .catch(err => {
        console.error("Error al cargar comentarios:", err);
        if (container) container.innerHTML = '<div style="color:#dc3545; font-size:0.8rem;">Error de conexión.</div>';
      });
  }

  function detectActiveCrisis(commentsData) {
    // Buscar si hay algún escalamiento activo referenciado en los comentarios o consultar API
    // En su defecto, comprobamos si el rowData de la fila tiene alerta_crisis = 1
    const rowData = getActiveRowData();
    const context = rowData ? buildDrawerContext(rowData, activeModuleKey) : null;
    const isCrisis = context && context.isSOS;

    const closureCard = document.getElementById('lps_closure_card');
    const actCard = document.getElementById('lps_action_card');

    if (isCrisis) {
      if (closureCard) closureCard.style.display = 'block';
      if (actCard) actCard.style.display = 'block';

      // Obtener el ID de la alerta desde los comentarios o setear fallback temporal
      activeAlertaId = null;
      for (let c of (Array.isArray(commentsData) ? commentsData : [])) {
        if (c.escalamiento_id) {
          activeAlertaId = c.escalamiento_id;
          break;
        }
      }
      // Fallback: Buscar en la base de datos a través de una llamada corta si es necesario o asumir el ID en caliente
      if (!activeAlertaId && rowData) {
        activeAlertaId = rowData.escalamiento_id || rowData.alerta_id || null;
      }
    } else {
      if (closureCard) closureCard.style.display = 'none';
      if (actCard) {
        actCard.style.display = shouldShowEscalation(context) ? 'block' : 'none';
      }
    }
  }

  function renderCommentsTree(comments) {
    const container = document.getElementById('lps_comments_container');
    const card = document.getElementById('lps_comments_card');
    if (!container || !card) return;

    card.style.display = 'block';
    container.innerHTML = '';

    if (!comments || comments.length === 0) {
      container.innerHTML = '<div style="font-size:0.8rem; color:#888; text-align:center; padding:10px;">Sin comentarios registrados. Escribe uno para iniciar la bitácora.</div>';
      return;
    }

    comments.forEach(c => {
      const isSystem = c.usuario_id === 0 || c.autor_nombre === 'Sistema' || !c.autor_nombre;
      const autor = isSystem ? 'Sistema AIA' : `${c.autor_nombre} (${c.autor_cargo || 'Cargo'})`;
      
      const commentDiv = document.createElement('div');
      commentDiv.className = 'lps-comment';
      commentDiv.style.cssText = `
        padding: 8px 10px; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 3px solid #1a3c2a;
        margin-bottom: 8px; font-size: 0.82rem;
      `;
      if (isSystem) {
        commentDiv.style.borderLeftColor = '#dc3545';
        commentDiv.style.background = 'rgba(220,53,69,0.03)';
      }

      // Reemplazar @D, @OT, etc. con badges
      let commentText = escapeHtml(c.comentario);
      commentText = commentText.replace(/@([A-Z]+)/g, '<span class="lps-mention-badge">@$1</span>');

      commentDiv.innerHTML = `
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
          <strong style="color:#1a3c2a;">${escapeHtml(autor)}</strong>
          <span style="font-size:0.7rem; color:#888;">${c.created_at}</span>
        </div>
        <div style="color:#2d3748; line-height:1.4; white-space:pre-wrap;">${commentText}</div>
        <div style="margin-top:6px; display:flex; gap:12px; font-size:0.72rem;">
          <a href="#" class="lps-reply-trigger" data-id="${c.id}" style="color:#198754; font-weight:700; text-decoration:none;">Responder</a>
        </div>
        <div class="lps-replies-container" style="margin-left: 16px; margin-top: 8px; border-left: 1px dashed rgba(0,0,0,0.08); padding-left: 10px;"></div>
      `;

      // Renderizar respuestas del hilo
      const repliesContainer = commentDiv.querySelector('.lps-replies-container');
      if (c.respuestas && c.respuestas.length > 0) {
        c.respuestas.forEach(r => {
          const rDiv = document.createElement('div');
          rDiv.style.cssText = 'padding: 5px 8px; background: #ffffff; border-radius: 6px; margin-top: 4px; font-size: 0.8rem; box-shadow: 0 1px 2px rgba(0,0,0,0.02);';
          
          let replyText = escapeHtml(r.comentario);
          replyText = replyText.replace(/@([A-Z]+)/g, '<span class="lps-mention-badge">@$1</span>');

          const rSystem = r.usuario_id === 0 || !r.autor_nombre;
          const rAutor = rSystem ? 'Sistema AIA' : `${r.autor_nombre} (${r.autor_cargo || 'Cargo'})`;

          rDiv.innerHTML = `
            <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <strong style="color: #495057;">${escapeHtml(rAutor)}</strong>
              <span style="font-size:0.68rem; color:#aaa;">${r.created_at}</span>
            </div>
            <div style="color:#333; line-height:1.35; white-space:pre-wrap;">${replyText}</div>
          `;
          repliesContainer.appendChild(rDiv);
        });
      }

      container.appendChild(commentDiv);
    });

    // Agregar listeners a los enlaces de respuesta
    container.querySelectorAll('.lps-reply-trigger').forEach(el => {
      el.addEventListener('click', function(e) {
        e.preventDefault();
        activeParentId = parseInt(this.getAttribute('data-id'), 10);
        
        const indicator = document.getElementById('lps_thread_replying_indicator');
        if (indicator) {
          indicator.style.display = 'flex';
          const authorName = this.closest('.lps-comment').querySelector('strong').textContent;
          indicator.querySelector('span').textContent = `Respondiendo al hilo de ${authorName}`;
        }
        
        const input = document.getElementById('lps_comment_input');
        if (input) input.focus();
      });
    });
  }

  function postComment() {
    const input = document.getElementById('lps_comment_input');
    if (!input) return;
    const comentario = input.value.trim();
    if (!comentario) return;

    // Detectar menciones de roles
    const menciones = [];
    const matches = comentario.match(/@([A-Z]+)/g);
    if (matches) {
      matches.forEach(m => {
        const rol = m.substring(1);
        if (!menciones.includes(rol)) menciones.push(rol);
      });
    }

    const formData = new FormData();
    formData.append('consecutivo', activeConsecutivo);
    formData.append('comentario', comentario);
    if (activeParentId) formData.append('parent_id', activeParentId);
    if (activeAlertaId) formData.append('escalamiento_id', activeAlertaId);
    if (menciones.length > 0) formData.append('menciones', JSON.stringify({ roles: menciones }));

    fetch('/api/lps/comments/add', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          input.value = '';
          activeParentId = null;
          const indicator = document.getElementById('lps_thread_replying_indicator');
          if (indicator) indicator.style.display = 'none';
          
          showNotification('Comentario registrado.');
          refreshDrawerData();
        } else {
          showNotification(`Error: ${response.mensaje}`);
        }
      })
      .catch(err => {
        console.error("Error al enviar comentario:", err);
        showNotification('Error de conexión al enviar comentario.');
      });
  }

  function closeCrisisAlert() {
    const input = document.getElementById('lps_closure_justification');
    if (!input || !activeAlertaId) return;

    const justificacion = input.value.trim();
    if (justificacion.length < 100) {
      showNotification('La justificación debe tener al menos 100 caracteres.');
      return;
    }

    const formData = new FormData();
    formData.append('alerta_id', activeAlertaId);
    formData.append('justificacion', justificacion);

    fetch('/api/lps/crisis/close', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          showNotification('¡Crisis mitigada y cerrada formalmente!');
          input.value = '';
          
          // Limpiar banderas en caliente en Handsontable
          if (activeHot && activeRowIndex !== null) {
            activeHot.setDataAtRowProp(activeRowIndex, 'alerta_crisis', 0);
          }

          drawerClose();
        } else {
          showNotification(`Error: ${response.mensaje}`);
        }
      })
      .catch(err => {
        console.error("Error al cerrar crisis:", err);
        showNotification('Error de conexión al cerrar la crisis.');
      });
  }

  function drawerOpen() {
    const drawer = document.getElementById('lps_drawer');
    const overlay = document.getElementById('lps_drawer_overlay');
    if (!drawer) return;

    drawer.classList.add('open');

    // Desplazamiento adaptable en desktop
    if (window.innerWidth >= 992) {
      document.body.classList.add('lps-drawer-open');
      // Redibujado diferido tras la transición de apertura
      setTimeout(() => {
        if (activeHot) activeHot.render();
      }, 300);
    } else if (overlay) {
      overlay.classList.add('active');
    }
  }

  function drawerClose() {
    const drawer = document.getElementById('lps_drawer');
    const overlay = document.getElementById('lps_drawer_overlay');
    if (drawer) drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('active');

    // Quitar desplazamiento adaptable en desktop
    document.body.classList.remove('lps-drawer-open');
    // Redibujado diferido tras la transición de cierre
    setTimeout(() => {
      if (activeHot) {
        activeHot.render();
      }
    }, 300);
  }

  function triggerEscalate(type) {
    if (activeRowIndex === null || !activeHot) return;
    const rowData = getActiveRowData();
    if (!rowData) return;
    const context = buildDrawerContext(rowData, activeModuleKey);
    const simulated = localStorage.getItem('lps_simulated_mode') === 'true';

    const consecutivo = context.consecutivo;
    const actividad = context.actividadTexto || 'Actividad sin nombre';
    const subcontratista = context.subcontratista;
    const restriccion = getRestrictionSummary(rowData, context.itr);
    const telefono = rowData.Telefono || rowData.telefono_subcontratista || '';
    const correo = rowData.Correo || rowData.correo_responsable || '';

    // Jerarquía de escalamiento SOS
    const rolesNombres = { 1: 'Residente', 2: 'Director', 3: 'Coordinador de Integración', 4: 'Gerente de Construcción', 5: 'Gerente General' };
    const nivelActual = parseInt(rowData.nivel_actual || 1, 10);
    const siguienteNivel = Math.min(nivelActual + 1, 5);
    const rolSuperior = rolesNombres[siguienteNivel];

    const text = `🚨 [ALERTA SOS - CRISIS AIA] 🚨\n\nEstimado superior en calidad de ${rolSuperior}, se notifica bloqueo crítico en la obra.\n• Actividad: #${consecutivo} - ${actividad}\n• Subcontratista: ${subcontratista}\n• Restricción/Causa: ${restriccion}\n\nSe solicita intervención jerárquica urgente para liberar el frente y evitar retrasos acumulados en la línea base teórica. - Last Planner AIA`;

    if (simulated) {
      navigator.clipboard.writeText(text).then(() => {
        showNotification('¡SOS copiado en Modo Simulación al portapapeles!');
      });
    } else {
      // Registrar la detonación del escalamiento en la base de datos
      const formData = new FormData();
      formData.append('consecutivo', consecutivo);
      formData.append('modulo', activeModuleKey === 'programa-general' ? 'PG' : (activeModuleKey === 'programacion-intermedia' ? 'PI' : 'PS'));
      formData.append('trigger', `SOS-${rolSuperior.substring(0, 3).toUpperCase()}`);

      fetch('/api/lps/crisis/register', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(response => {
          if (response.respuesta === 'OK') {
            // Actualizar Handsontable
            activeHot.setDataAtRowProp(activeRowIndex, 'alerta_crisis', 1);
            showNotification('Alerta SOS registrada.');
            refreshDrawerData();
          }
        })
        .catch(err => console.error("Error al registrar crisis por SOS:", err));

      if (type === 'whatsapp') {
        if (!telefono) {
          showNotification('⚠️ Sin teléfono asignado. Usando copiado al portapapeles.');
          navigator.clipboard.writeText(text);
          return;
        }
        const waUrl = `https://api.whatsapp.com/send?phone=${telefono.replace(/\s+/g, '')}&text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank');
      } else {
        if (!correo) {
          showNotification('⚠️ Sin correo asignado. Usando copiado al portapapeles.');
          navigator.clipboard.writeText(text);
          return;
        }
        const mailUrl = `mailto:${correo}?subject=${encodeURIComponent('[SOS CRISIS LPS] Intervención Jerárquica Requeria')}&body=${encodeURIComponent(text)}`;
        window.open(mailUrl, '_blank');
      }
    }
  }

  function compileWeeklyDigest() {
    if (!activeHot) return;
    const sourceData = activeHot.getSourceData();
    const criticallyBlocked = {};

    sourceData.forEach((row, idx) => {
      const safeRow = row || {};
      const itr = calculateITR(safeRow);
      const isCritical = isCriticalRoute(safeRow);
      const subcontratista = getSubcontractor(safeRow);
      const consecutivo = getCanonicalConsecutivo(safeRow) || idx + 1;
      const actividad = getPlainText(getActivityTitle(safeRow)) || 'Tarea';
      const restriccion = getRestrictionSummary(safeRow, itr);

      const hasBottleneck = !itr.isComplete || safeRow.atraso > 0 || safeRow.Restriccion || safeRow.causa_no_cumplimiento || safeRow.compromiso_vencido || parseInt(safeRow.alerta_crisis, 10) === 1;

      if (isCritical && hasBottleneck) {
        if (!criticallyBlocked[subcontratista]) {
          criticallyBlocked[subcontratista] = [];
        }
        criticallyBlocked[subcontratista].push(`Actividad #${consecutivo} (${actividad}) - Restricción: ${restriccion}`);
      }
    });

    const subcontratistasKeys = Object.keys(criticallyBlocked);
    const preview = document.getElementById('lps_digest_text_preview');
    const resultCard = document.getElementById('lps_digest_result_card');

    if (subcontratistasKeys.length === 0) {
      if (preview) preview.textContent = "Excelente. No se encontraron bloqueos críticos en actividades P1 (Ruta Crítica) para esta semana.";
      if (resultCard) resultCard.style.display = 'block';
      return;
    }

    let digestText = `📋 REPORTE CONSOLIDADO DE BLOQUEOS LPS - OBRA AIA\n`;
    digestText += `Semana de Control: ${new Date().toLocaleDateString()}\n`;
    digestText += `==============================================\n\n`;

    subcontratistasKeys.forEach(sub => {
      digestText += `▶️ RESPONSABLE: ${sub}\n`;
      criticallyBlocked[sub].forEach(task => {
        digestText += `  • ${task}\n`;
      });
      digestText += `\n`;
    });

    digestText += `----------------------------------------------\n`;
    digestText += `Solicitamos a los líderes de frente asegurar recursos y coordinar la liberación de frentes para evitar atrasos en la línea base teórica.`;

    if (preview) preview.textContent = digestText;
    if (resultCard) resultCard.style.display = 'block';
    showNotification('¡Digest consolidado semanal compilado!');
  }

  function copyDigestToClipboard() {
    const preview = document.getElementById('lps_digest_text_preview');
    if (!preview) return;
    navigator.clipboard.writeText(preview.textContent).then(() => {
      showNotification('¡Digest copiado al portapapeles!');
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function actionSentence(context) {
    const actions = (context.stateActions || []).slice(0, 3).map(escapeHtml);
    return actions.length ? ` Acción sugerida: ${actions.join('; ')}.` : '';
  }

  function renderWeeklyDiagnosis(context, descEl) {
    const state = context.stateKey;
    const label = escapeHtml(context.stateLabel || 'Control semanal');
    const itrText = `${context.itr.porcentaje}%`;
    const actionText = actionSentence(context);
    const compromiso = firstValue(context.rowData, ['Compromiso']);
    const real = firstValue(context.rowData, ['Ejecutado_Real']);
    const phaseLabel = context.phase === 'calificacion' ? 'Calificación semanal' : 'Programación semanal';

    if (state === 'prog-bloqueo-critico-sin-compromiso') {
      descEl.innerHTML = `🚨 <strong>${label}.</strong> ${phaseLabel}: actividad de ruta crítica con condiciones habilitantes pendientes (ITR: ${itrText}) y sin compromiso confiable. Escalar liberación antes de confirmar producción.${actionText}`;
      return;
    }
    if (state === 'prog-ejecucion-con-restricciones') {
      const escalationText = context.severity === 'critical'
        ? ' Escalar continuidad del frente por impacto sobre ruta crítica.'
        : ' Gestionar cierre operativo sin escalamiento directivo por defecto.';
      descEl.innerHTML = `⚠️ <strong>${label}.</strong> ${phaseLabel}: existe avance acumulado (${escapeHtml(context.progressDisplay)}), pero aún hay restricciones habilitantes pendientes (ITR: ${itrText}). No comprometer más producción sin cerrar condiciones.${escalationText}${actionText}`;
      return;
    }
    if (state === 'prog-condiciones-pendientes') {
      descEl.innerHTML = `🟠 <strong>${label}.</strong> ${phaseLabel}: la actividad requiere cerrar condiciones de habilitación antes de comprometerse (ITR: ${itrText}).${actionText}`;
      return;
    }
    if (state === 'prog-sin-compromiso') {
      const gaps = getWeeklyCommitmentGaps(context.rowData);
      const gapText = gaps.length ? gaps.join('; ') : 'validar compromiso semanal';
      const criticalNote = context.isCritical ? ' Es ruta crítica, por lo que conviene comprometerla con prioridad, pero no requiere escalamiento mientras esté habilitada.' : '';
      descEl.innerHTML = `🟡 <strong>${label}.</strong> ${phaseLabel}: actividad habilitada para plan semanal (ITR: ${itrText}). Pendiente operativo: ${escapeHtml(gapText)}.${criticalNote}${actionText}`;
      return;
    }
    if (state === 'prog-lista-para-confirmar') {
      descEl.innerHTML = `🟢 <strong>${label}.</strong> ${phaseLabel}: compromiso, responsable y subcontratista listos. Mantener verificación final antes del cierre semanal.`;
      return;
    }
    if (state === 'cal-incumplida-critica' || state === 'cal-incumplida') {
      const verb = context.severity === 'critical' ? 'Registrar CNC y activar recuperación hoy.' : 'Registrar CNC y plan correctivo.';
      descEl.innerHTML = `🔴 <strong>${label}.</strong> ${phaseLabel}: el ejecutado real (${escapeHtml(real || 'sin dato')}) está por debajo del compromiso (${escapeHtml(compromiso || 'sin dato')}). ${verb}${actionText}`;
      return;
    }
    if (state === 'cal-sin-calificar') {
      descEl.innerHTML = `🟡 <strong>${label}.</strong> ${phaseLabel}: falta registrar ejecutado real para evaluar PAC y CNC. Completar calificación antes del cierre.`;
      return;
    }
    if (state === 'cal-cumplida-control') {
      descEl.innerHTML = `🟢 <strong>${label}.</strong> ${phaseLabel}: compromiso cumplido o superado. Documentar aprendizaje y sostener ritmo.`;
      return;
    }

    descEl.innerHTML = `🟢 <strong>${label}.</strong> ${phaseLabel}: estado operativo semanal sin alertas críticas activas. ITR actual: ${itrText}.${actionText}`;
  }

  function renderStandardDiagnosis(context, descEl) {
    const stateLabel = escapeHtml(context.stateLabel || 'Control');
    const restrictionSummary = escapeHtml(getRestrictionSummary(context.rowData, context.itr));
    const itrText = `${context.itr.porcentaje}%`;
    const weeks = context.semanasInicio;
    const actionText = actionSentence(context);

    if (context.isSOS) {
      descEl.innerHTML = `🔥 <strong>CRISIS ACTIVA POR ESCALAMIENTO SOS.</strong> El frente está escalado para intervención jerárquica. Bloqueo reportado: [${restrictionSummary}]. Se requiere acción directiva inmediata.`;
      return;
    }

    if (context.severity === 'critical') {
      if (context.isReactiveCrisis) {
        descEl.innerHTML = `⚡ <strong>CRISIS REACTIVA: DESVIACIÓN DE AVANCE.</strong> Actividad P1 con desviación crítica. Avance actual: ${escapeHtml(context.progressDisplay)}. Revisar rendimientos, cuadrillas y reprogramación de frentes.`;
      } else {
        const timing = weeks === null ? 'sin fecha confiable de inicio' : (weeks < 0 ? `debió iniciar hace ${Math.abs(weeks)} semana(s)` : (weeks === 0 ? 'debe iniciar hoy' : `inicia en ${weeks} semana(s)`));
        descEl.innerHTML = `🚨 <strong>${stateLabel}: BLOQUEO CRÍTICO.</strong> Actividad P1 ${timing} con restricciones habilitantes pendientes (ITR: ${itrText}). Pendientes: ${restrictionSummary}. Escalar recuperación y destrabe inmediato.${actionText}`;
      }
      return;
    }

    if (context.severity === 'attention') {
      const timing = context.isStartOverdue ? ` Debió iniciar hace ${Math.abs(weeks)} semana(s).` : '';
      const routeNote = context.isCritical ? ' Es P1, pero no cumple condición de crisis directiva según la matriz temporal.' : '';
      descEl.innerHTML = `🟡 <strong>${stateLabel}.</strong> Atención operativa prioritaria.${timing}${routeNote} ITR actual: ${itrText}. Pendientes: ${restrictionSummary}.${actionText}`;
      return;
    }

    if (context.severity === 'info') {
      const timing = weeks === null ? '' : ` Inicia en ${weeks} semana(s).`;
      descEl.innerHTML = `🔵 <strong>${stateLabel}.</strong> Preparación temprana sin escalamiento.${timing} ITR actual: ${itrText}. Mantener seguimiento lookahead y restricciones blandas como información.${actionText}`;
      return;
    }

    if (context.isCritical && context.isLiberada) {
      const timing = weeks !== null && weeks > 0 ? ` Inicia en ${weeks} semana(s).` : '';
      descEl.innerHTML = `🟢 <strong>P1 EN CONTROL.</strong>${timing} Actividad crítica liberada de restricciones habilitantes. Mantener control de productividad y verificación de arranque.`;
      return;
    }

    descEl.innerHTML = `🟢 <strong>SEGUIMIENTO RUTINARIO.</strong> Actividad sin bloqueos habilitantes críticos. ITR actual: ${itrText}. Mantener control diario de obra.`;
  }

  function renderDiagnosis(context, descEl) {
    if (!descEl) return;
    if (context.moduleKey === 'programacion-semanal') {
      renderWeeklyDiagnosis(context, descEl);
      return;
    }
    renderStandardDiagnosis(context, descEl);
  }

  return {
    init: function(hot, moduleKey, stateAdapter) {
      activeHot = hot;
      activeModuleKey = moduleKey;
      activeStateAdapter = stateAdapter;

      bindEvents();

      // Interceptar clics y selección en Handsontable
      hot.addHook('afterSelectionEnd', function(r, c, r2, c2) {
        if (r < 0) return;
        activeRowIndex = r;
        const physicalRow = typeof hot.toPhysicalRow === 'function' ? hot.toPhysicalRow(r) : r;
        const rowData = Number.isInteger(physicalRow) && physicalRow >= 0 ? hot.getSourceDataAtRow(physicalRow) : null;
        LPSContextualDrawer.updateContext(rowData, moduleKey);
      });
    },

    updateContext: function(rowData, moduleKey) {
      if (!rowData) return;

      const drawer = document.getElementById('lps_drawer');
      const overlay = document.getElementById('lps_drawer_overlay');
      if (!drawer) return;

      const isDrawerOpen = drawer.classList.contains('open');
      if (isDrawerOpen) {
        if (window.innerWidth >= 992) {
          document.body.classList.add('lps-drawer-open');
          setTimeout(() => {
            if (activeHot) activeHot.render();
          }, 300);
        } else if (overlay) {
          overlay.classList.add('active');
        }
      }

      const context = buildDrawerContext(rowData, moduleKey || activeModuleKey);
      activeConsecutivo = context.consecutivo;

      const sidebarTrigger = document.getElementById('lps_sidebar_trigger');
      const sidebarBadge = document.getElementById('lps_sidebar_badge');
      if (sidebarTrigger) {
        sidebarTrigger.classList.remove('has-crisis', 'has-attention');
        const sidebarClass = context.severityVisual.sidebarClass;
        if (sidebarClass) {
          sidebarTrigger.classList.add(sidebarClass);
        }

        if (context.severity === 'critical' || context.severity === 'attention') {
          if (sidebarBadge) {
            sidebarBadge.textContent = context.severityVisual.badgeText;
            sidebarBadge.style.display = 'flex';
          }
        } else {
          if (sidebarBadge) sidebarBadge.style.display = 'none';
        }
      }

      const titleEl = document.getElementById('lps_actividad_title');
      const consecEl = document.getElementById('lps_consecutivo');
      const priorityBadge = document.getElementById('lps_badge_priority');
      const diagCard = document.getElementById('lps_diagnostic_card');
      const descEl = document.getElementById('lps_diagnostico_desc');
      const rolBadge = document.getElementById('lps_badge_rol');

      if (titleEl) titleEl.innerHTML = context.actividad;
      if (consecEl) consecEl.textContent = context.isHeader ? 'Capítulo' : `Actividad #${activeConsecutivo}`;

      const rolesNombres = { 1: 'Residente', 2: 'Director', 3: 'Coordinador de Integración', 4: 'Gerente de Construcción', 5: 'Gerente General' };
      const nivelActual = parseInt(rowData.nivel_actual || 1, 10);
      const siguienteNivel = Math.min(nivelActual + 1, 5);
      if (rolBadge) {
        if (shouldShowEscalation(context)) {
          rolBadge.textContent = `Escalamiento: Superior Inmediato (${rolesNombres[siguienteNivel]})`;
          rolBadge.className = 'lps-badge lps-badge-p1';
        } else {
          rolBadge.textContent = isWeeklyModule(context) ? 'Seguimiento operativo semanal' : 'Seguimiento operativo LPS';
          rolBadge.className = 'lps-badge ' + context.severityVisual.badgeClass;
        }
      }

      if (context.isHeader) {
        if (priorityBadge) {
          priorityBadge.className = 'lps-badge lps-badge-p3';
          priorityBadge.textContent = 'Capítulo';
        }
        if (diagCard) {
          diagCard.className = 'lps-card-glass lps-state-p3';
          diagCard.style.borderLeft = '4px solid #6c757d';
        }
        if (descEl) {
          descEl.innerHTML = `ℹ️ <strong>FILA DE CAPÍTULO.</strong> Selecciona una actividad específica para ver diagnóstico LPS, restricciones, comentarios y escalamiento.`;
        }
        hideActivityCards();
        return;
      }

      if (priorityBadge) {
        priorityBadge.className = 'lps-badge ' + context.severityVisual.badgeClass;
        if (isWeeklyModule(context)) {
          priorityBadge.textContent = context.stateLabel || 'Estado semanal';
        } else {
          priorityBadge.textContent = context.isCritical
            ? `P1 ${context.severityVisual.label}`
            : context.severityVisual.label;
        }
      }

      if (diagCard) {
        diagCard.className = 'lps-card-glass ' + context.severityVisual.cardClass;
        diagCard.style.borderLeft = '';
      }

      renderDiagnosis(context, descEl);
      updateITRVisuals(context.itr, rowData);
      loadCommentsAndCrisis();

      const simulated = localStorage.getItem('lps_simulated_mode') === 'true';
      const simCard = document.getElementById('lps_sim_clipboard_card');
      const simPreview = document.getElementById('lps_alert_text_preview');
      if (simCard && simPreview) {
        if (simulated && shouldShowEscalation(context)) {
          simCard.style.display = 'block';
          const rolSuperior = rolesNombres[siguienteNivel];
          simPreview.textContent = `🚨 [ALERTA SOS - CRISIS AIA] 🚨\nEstimado superior en calidad de ${rolSuperior}, se notifica bloqueo operativo en la obra.\n• Actividad: #${activeConsecutivo} - ${context.actividadTexto}\n• Estado: ${context.stateLabel}\n• Restricción/Causa: ${getRestrictionSummary(rowData, context.itr)}`;
        } else {
          simCard.style.display = 'none';
        }
      }

      activeParentId = null;
      const indicator = document.getElementById('lps_thread_replying_indicator');
      if (indicator) indicator.style.display = 'none';
    }
  };
})();
