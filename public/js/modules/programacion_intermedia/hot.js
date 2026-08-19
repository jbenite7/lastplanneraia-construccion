(function (window, $) {
  'use strict';

  var hot = null;
  var initialized = false;
  var renderersRegistered = false;
  var saveBadgeTimer = null;
  var layoutTimer = null;
  var lastAppliedContainerWidth = 0;
  var lastAppliedContainerHeight = 0;
  var currentColumnWidths = [];
  var pendingViewportState = null;
  var pendingTabSelection = null;
  // Exponer flag para que el editor Select2 pueda señalar navegación pendiente
  window.__piPendingNav = false;
  var masterData = [];
  var visibleRows = [];
  var activeFilters = [];

  /* Frente contadores-cero. Unico punto de reversion: en false vuelve el
     comportamiento anterior -las ocho etiquetas visibles, las que marcan cero
     atenuadas por `is-zero` (C-24)- sin tocar el HTML ni el CSS.

     Por que no basta con mirar el numero: `updateLegendCounts` recibe las filas
     YA filtradas, asi que con un filtro puesto las otras siete categorias
     marcan (0) aunque tengan contenido. Ese cero significa "no en esta vista",
     no "vacio". Y como cada etiqueta es tambien el boton de ese filtro,
     ocultarlas por el valor a secas dejaria al usuario encerrado en el filtro
     activo, sin ningun otro filtro al que saltar y con la pantalla pareciendo
     vacia. Por eso solo se oculta el cero que significa vacio de verdad. */
  var OCULTAR_CONTADORES_EN_CERO = true;

  var sharedSelectionIndex = {};
  var lastSharedPreviewKey = null;
  var lastSharedPreviewStats = null;
  var _rowStateCache = [];
  var _rowClassCache = [];
  var _rowMetaCache = [];
  var _stateViewCache = [];
  var _saveStatus = null;
  import('/js/design-system/save-status.js').then(function (mod) {
    _saveStatus = mod.crearSaveStatus({ claseOculta: 'pi-status-badge-hidden' });
  });
  import('/js/design-system/modal-escape.js').then(function (mod) {
    mod.activarEscapeEnModales();
  });

  var options = window.PI_HOT_OPTIONS || {};
  var subcontratistas = Array.isArray(options.subcontratistas) ? options.subcontratistas.slice() : [];
  var profesionales = Array.isArray(options.profesionales) ? options.profesionales.slice() : [];

  var PI_CREATE_SUB = '➕ Crear Subcontratista...';
  var PI_CREATE_PROF = '➕ Crear Profesional...';
  subcontratistas.push(PI_CREATE_SUB);
  profesionales.push(PI_CREATE_PROF);

  // N-1 (Task 38, 2026-08-05): vocabulario unico del bloqueo por falta de
  // Responsable AIA. «Responsable AIA» es el termino del dominio (GLOSARIO.md).
  // El candado va en Font Awesome, no en emoji: un emoji ignora `color` y la
  // senal quedaria fuera del sistema de tokens (y sin contraste medible).
  var PI_MISSING_RESP_LABEL = 'Falta Responsable AIA';
  var PI_LOCK_REASON = 'Restricciones bloqueadas: asigne el Responsable AIA de esta actividad';

  function buildLockGlyph() {
    var icon = document.createElement('i');
    icon.className = 'fas fa-lock pi-lock-mark';
    icon.setAttribute('aria-hidden', 'true');
    return icon;
  }

  // --- Restriction Config (dynamic from API, fallback: construction defaults) ---
  var _activeConfig = null;
  var _activeRestrictions = [];

  var CONSTRUCTION_DEFAULTS = {
    area: 'Construccion',
    restrictions: [
      { key: 'D_y_E', label: 'Diseños y Especif.', type: 'hard', threshold: 100, options: ['0%', '33%', '66%', '100%', 'N/A'] },
      { key: 'Materiales', label: 'Materiales', type: 'hard', threshold: 100, options: ['0%', '33%', '66%', '100%', 'N/A'] },
      { key: 'MdeO', label: 'Mano de Obra', type: 'hard', threshold: 100, options: ['0%', '33%', '66%', '100%', 'N/A'] },
      { key: 'Equipos', label: 'Equipos', type: 'hard', threshold: 100, options: ['0%', '33%', '66%', '100%', 'N/A'] },
      { key: 'Predecesora', label: 'Predecesora', type: 'hard', threshold: 50, options: ['0%', '50%', '100%', 'N/A'] },
      { key: 'Pdto_Cons', label: 'Pdto. Constructivo (blanda)', type: 'soft', threshold: 100, options: ['0%', '50%', '100%', 'N/A'] },
      { key: 'Modelo', label: 'Modelo BIM (blanda)', type: 'soft', threshold: 100, options: ['0%', '50%', '100%', 'N/A'] },
    ],
    hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
    softRestrictions: ['Pdto_Cons', 'Modelo'],
  };

  // --- Dynamic restriction variables (populated by applyRestrictionConfig) ---
  var editableProps = {};
  var restrictedOptions = [];
  var halfRestrictedOptions = [];
  var restrictionProps = [];
  var hardRestrictionProps = [];
  var softRestrictionProps = [];
  var hardRestrictionThresholds = {};
  var restrictionTypeLabels = {};
  var sharedRestrictionTypes = [];
  var popoverTitles = {};
  var popoverContent = {};
  var readinessActionProps = [];
  var readinessActionLabels = {};
  var readinessActionMatrix = {};
  var headerIndexToRestrictionProp = {};
  var dropdownProps = {};

  // Popover content defaults for construction (detailed educational text)
  var DEFAULT_POPOVER_CONTENT = {
    D_y_E: '<ul class="pl-3 mb-0"><li><b>0%:</b> No están los diseños para construcción.</li><li><b>33%:</b> Diseños entregados pero no revisados por dirección/residentes.</li><li><b>66%:</b> Diseños con visto bueno de dirección y residentes.</li><li><b>100%:</b> Diseños aprobados entregados a contratistas/maestros.</li></ul>',
    Materiales: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de aprovisionamiento.</li><li><b>33%:</b> Al día en plan de compras.</li><li><b>66%:</b> Al día en plan de aprovisionamiento.</li><li><b>100%:</b> Materiales disponibles en el proyecto.</li></ul>',
    MdeO: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de mano de obra.</li><li><b>33%:</b> Contratos existentes, recurso no ubicado.</li><li><b>66%:</b> Documentación y requisitos legales listos.</li><li><b>100%:</b> Personal ya está en el proyecto.</li></ul>',
    Equipos: '<ul class="pl-3 mb-0"><li><b>0%:</b> No existen contratos de equipos.</li><li><b>33%:</b> Al día en plan de compras.</li><li><b>66%:</b> Al día en plan de aprovisionamiento.</li><li><b>100%:</b> Equipos disponibles en el proyecto.</li></ul>',
    Predecesora: '<ul class="pl-3 mb-0"><li><b>0%:</b> Predecesoras no han iniciado o están atrasadas.</li><li><b>50%:</b> Predecesoras con rendimiento igual o superior al programa.</li><li><b>100%:</b> Predecesoras ya terminadas.</li></ul>',
    Pdto_Cons: '<p class="mb-1"><b>Restricción blanda:</b> no bloquea habilitación ni autoprogramación.</p><ul class="pl-3 mb-0"><li><b>0%:</b> No existe procedimiento constructivo.</li><li><b>50%:</b> Existe pero no se ha divulgado.</li><li><b>100%:</b> Divulgado y aprobado por el director.</li></ul>',
    Modelo: '<p class="mb-1"><b>Restricción blanda:</b> no bloquea habilitación ni autoprogramación.</p><ul class="pl-3 mb-0"><li><b>0%:</b> No hay modelos en el proyecto.</li><li><b>50%:</b> Modelos existentes pero no coordinados.</li><li><b>100%:</b> Modelos coordinados para todas las disciplinas.</li><li><b>N/A:</b> La tarea no aplica para ser modelada.</li></ul>',
  };

  var DEFAULT_POPOVER_TITLES = {
    D_y_E: 'Restricciones de Diseños y Especificaciones',
    Materiales: 'Restricciones de Materiales',
    MdeO: 'Restricciones de Mano de Obra',
    Equipos: 'Restricciones de Equipos',
    Predecesora: 'Restricciones de Actividades Predecesoras',
    Pdto_Cons: 'Restricción blanda: Pdto. Constructivo',
    Modelo: 'Restricción blanda: Modelo BIM',
  };

  // Readiness action defaults for construction (detailed action texts)
  var DEFAULT_READINESS_ACTION_LABELS = {
    D_y_E: 'Diseños',
    Materiales: 'Materiales',
    MdeO: 'MO',
    Equipos: 'Equipos',
    Predecesora: 'Pred.',
  };

  var DEFAULT_READINESS_ACTION_MATRIX = {
    D_y_E: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Solicitar diseños para construcción.' },
        { max: 0.5, text: 'Revisar diseños con dirección y residentes.' },
        { max: 1, text: 'Aprobar y entregar diseños a contratistas/maestros.' },
      ],
    },
    Materiales: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de aprovisionamiento.' },
        { max: 0.5, text: 'Pasar de plan de compras a plan de aprovisionamiento.' },
        { max: 1, text: 'Confirmar materiales disponibles en el proyecto.' },
      ],
    },
    MdeO: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de mano de obra.' },
        { max: 0.5, text: 'Ubicar y confirmar recurso de mano de obra.' },
        { max: 1, text: 'Movilizar personal al proyecto.' },
      ],
    },
    Equipos: {
      threshold: 1,
      actions: [
        { max: 0.01, text: 'Gestionar contratos de equipos.' },
        { max: 0.5, text: 'Pasar de plan de compras a plan de aprovisionamiento de equipos.' },
        { max: 1, text: 'Confirmar equipos disponibles en el proyecto.' },
      ],
    },
    Predecesora: {
      threshold: 0.5,
      actions: [
        { max: 0.5, text: 'Recuperar o iniciar actividad predecesora.' },
      ],
    },
  };

  var trackedStates = [
    'blocked-overdue-critical',
    'blocked-overdue',
    'blocked-due',
    'alert-1-week',
    'alert-2-3-weeks',
    'alert-4-6-weeks',
    'execution-blocked',
    'liberated-control',
  ];

  function _findRestrictionByKey(key) {
    if (!_activeRestrictions) return null;
    for (var i = 0; i < _activeRestrictions.length; i++) {
      if (_activeRestrictions[i].key === key) return _activeRestrictions[i];
    }
    return null;
  }

  function applyRestrictionConfig(config) {
    _activeConfig = config;
    var restrictions = config.restrictions || CONSTRUCTION_DEFAULTS.restrictions;
    var hardKeys = config.hardRestrictions || CONSTRUCTION_DEFAULTS.hardRestrictions;
    var softKeys = config.softRestrictions || CONSTRUCTION_DEFAULTS.softRestrictions;

    _activeRestrictions = restrictions;

    var byKey = {};
    for (var i = 0; i < restrictions.length; i++) {
      byKey[restrictions[i].key] = restrictions[i];
    }

    // Core arrays
    restrictionProps = restrictions.map(function (r) { return r.key; });
    hardRestrictionProps = hardKeys.slice();
    softRestrictionProps = softKeys.slice();
    sharedRestrictionTypes = restrictionProps.slice();

    // Editable / dropdown props
    editableProps = {};
    dropdownProps = {};
    for (var j = 0; j < restrictionProps.length; j++) {
      editableProps[restrictionProps[j]] = true;
      dropdownProps[restrictionProps[j]] = true;
    }
    editableProps.Sub_Contratista = true;
    editableProps.Responsable_AIA = true;
    editableProps.Observaciones = true;
    dropdownProps.Sub_Contratista = true;
    dropdownProps.Responsable_AIA = true;

    // Options arrays (fallback for shared-value drawers)
    var firstHard = byKey[hardKeys[0]];
    var firstSoft = byKey[softKeys[0]];
    restrictedOptions = [''].concat(firstHard ? firstHard.options : ['0%', '33%', '66%', '100%', 'N/A']);
    halfRestrictedOptions = [''].concat(firstSoft ? firstSoft.options : ['0%', '50%', '100%', 'N/A']);

    // Thresholds (convert from percentage → ratio)
    hardRestrictionThresholds = {};
    for (var k = 0; k < hardKeys.length; k++) {
      var hr = byKey[hardKeys[k]];
      if (hr) {
        hardRestrictionThresholds[hardKeys[k]] = (hr.threshold || 100) / 100;
      }
    }

    // Labels
    restrictionTypeLabels = {};
    readinessActionLabels = {};
    for (var m = 0; m < restrictions.length; m++) {
      restrictionTypeLabels[restrictions[m].key] = restrictions[m].label;
    }
    // Use construction-default short labels when available, else derive from full label
    for (var n = 0; n < hardKeys.length; n++) {
      readinessActionLabels[hardKeys[n]] = DEFAULT_READINESS_ACTION_LABELS[hardKeys[n]] || restrictionTypeLabels[hardKeys[n]] || hardKeys[n];
    }

    // Popover titles & content: prefer construction defaults, then API label, then key
    popoverTitles = {};
    popoverContent = {};
    for (var p = 0; p < restrictions.length; p++) {
      var rk = restrictions[p].key;
      popoverTitles[rk] = DEFAULT_POPOVER_TITLES[rk] || ('Restricción: ' + restrictions[p].label);
      popoverContent[rk] = DEFAULT_POPOVER_CONTENT[rk] || ('<p class="mb-1">' + restrictions[p].label + '</p>');
    }

    // Readiness action props = hard restrictions
    readinessActionProps = hardKeys.slice();

    // Readiness action matrix: prefer construction defaults, else generic
    readinessActionMatrix = {};
    for (var q = 0; q < hardKeys.length; q++) {
      var hk = hardKeys[q];
      if (DEFAULT_READINESS_ACTION_MATRIX[hk]) {
        readinessActionMatrix[hk] = DEFAULT_READINESS_ACTION_MATRIX[hk];
      } else {
        var thr = (byKey[hk] && byKey[hk].threshold) || 100;
        readinessActionMatrix[hk] = {
          threshold: thr / 100,
          actions: [
            { max: 0.01, text: 'Iniciar gestión de ' + (restrictionTypeLabels[hk] || hk).toLowerCase() + '.' },
            { max: 0.5, text: 'Avanzar en resolución de ' + (restrictionTypeLabels[hk] || hk).toLowerCase() + '.' },
            { max: 1, text: 'Completar ' + (restrictionTypeLabels[hk] || hk).toLowerCase() + '.' },
          ],
        };
      }
    }

    // Header-index → restriction prop (7 fixed cols before restriction cols)
    headerIndexToRestrictionProp = {};
    for (var t = 0; t < restrictions.length; t++) {
      headerIndexToRestrictionProp[7 + t] = restrictions[t].key;
    }

    // Rebuild column sizing arrays for the current restriction count
    buildColumnSizing();
  }

  function fetchRestrictionConfig(callback) {
    if (window.__RESTRICTION_CONFIG__) {
      applyRestrictionConfig(window.__RESTRICTION_CONFIG__);
      if (typeof callback === 'function') { callback(); }
      return;
    }

    $.ajax({
      method: 'GET',
      url: '/api/general/restriction-config',
      dataType: 'json',
      cache: true,
      timeout: 5000,
    }).done(function (response) {
      if (response && typeof response === 'object' && Array.isArray(response.restrictions) && response.restrictions.length > 0) {
        window.__RESTRICTION_CONFIG__ = response;
        applyRestrictionConfig(response);
      } else {
        applyRestrictionConfig(CONSTRUCTION_DEFAULTS);
      }
    }).fail(function () {
      applyRestrictionConfig(CONSTRUCTION_DEFAULTS);
    }).always(function () {
      if (typeof callback === 'function') { callback(); }
    });
  }

  function buildColumnHeaders() {
    var config = _activeConfig || CONSTRUCTION_DEFAULTS;
    var restrictions = config.restrictions || CONSTRUCTION_DEFAULTS.restrictions;
    var headers = [
      'Id', 'Lote', 'Actividad', 'Sub-Contratista', 'Responsable AIA',
      'Semanas Inicio', 'Ejecutado',
    ];
    for (var i = 0; i < restrictions.length; i++) {
      headers.push(restrictions[i].label);
    }
    headers.push('% Liberación', 'Estado Operativo', 'Observaciones');
    return headers;
  }

  function buildColumnDefinitions() {
    var config = _activeConfig || CONSTRUCTION_DEFAULTS;
    var restrictions = config.restrictions || CONSTRUCTION_DEFAULTS.restrictions;
    var softKeys = config.softRestrictions || CONSTRUCTION_DEFAULTS.softRestrictions;
    var cols = [
      { data: 'Id', readOnly: true, className: 'htCenter htMiddle' },
      { data: '__shared_selected', type: 'checkbox', className: 'htCenter htMiddle pi-shared-select-cell' },
      { data: 'Actividad', readOnly: true, renderer: 'piActividadRenderer', className: 'htLeft htMiddle force-wrap' },
      { data: 'Sub_Contratista', editor: 'tomSelectMultiple', tomSelectOptions: subcontratistas, className: 'htCenter htMiddle force-wrap' },
      { data: 'Responsable_AIA', editor: 'tomSelectSingle', tomSelectOptions: profesionales, renderer: 'piResponsableRenderer', className: 'htCenter htMiddle force-wrap' },
      { data: 'Semanas_Inicio', readOnly: true, className: 'htCenter htMiddle' },
      { data: 'Ejecutado', readOnly: true, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
    ];
    for (var i = 0; i < restrictions.length; i++) {
      var r = restrictions[i];
      var isSoft = softKeys.indexOf(r.key) > -1;
      cols.push({
        data: r.key,
        type: 'dropdown',
        source: [''].concat(r.options || []),
        strict: false,
        allowInvalid: false,
        renderer: 'piRestrictionRenderer',
        className: 'htCenter htMiddle' + (isSoft ? ' pi-soft-restriction-cell' : ''),
      });
    }
    cols.push(
      { data: 'Estado_Restricciones', readOnly: true, renderer: 'piPercentRenderer', className: 'htCenter htMiddle' },
      { data: 'estado_operativo', readOnly: true, renderer: 'piStateRenderer', className: 'htLeft htMiddle force-wrap' },
      { data: 'Observaciones', type: 'text', className: 'htLeft htMiddle force-wrap' },
    );
    return cols;
  }

  /**
   * Ancho que necesita una cabecera para leerse ENTERA, medido, no estimado.
   *
   * Task 8 (2026-08-05, Step 1-bis). Las cabeceras de las columnas de restriccion
   * no son texto del repo: salen de `general` via `/api/general/restriction-config`
   * y cambian por proyecto («Diseños y Especificaciones», «Procedimiento
   * Constructivo», «Equipos y Herramienta»…). Fijar numeros a mano aqui los
   * dejaria mal en cuanto un proyecto renombrara una restriccion, asi que el piso
   * se calcula del propio texto.
   *
   * Se mide la PALABRA mas larga, no la frase: el `.colHeader` envuelve por
   * palabra y admite dos lineas, asi que «Equipos y Herramienta» cabe en
   * `max("Equipos", "y", "Herramienta")`; lo que nunca cabe —y es lo que se
   * recortaba— es una palabra sola mas ancha que la columna.
   */
  function anchoMinimoCabecera(texto) {
    var etiqueta = String(texto == null ? '' : texto).trim();
    if (!etiqueta) return 0;

    var canvas;
    if (!anchoMinimoCabecera._ctx) {
      canvas = document.createElement('canvas');
      anchoMinimoCabecera._ctx = canvas.getContext ? canvas.getContext('2d') : null;
    }
    var ctx = anchoMinimoCabecera._ctx;
    if (!ctx) {
      // Sin canvas se cae a la heuristica previa del modulo (~6 px por caracter).
      return 0;
    }

    var raiz = document.documentElement;
    var estilos = window.getComputedStyle(raiz);
    var fuente = window.getComputedStyle(document.body || raiz).fontFamily || 'sans-serif';
    var tamano = (estilos.getPropertyValue('--ds-table-header-font-size') || '0.75rem').trim();
    var relleno = (estilos.getPropertyValue('--ds-table-header-pad-x') || '0.1875rem').trim();
    var aRem = function (valor, porDefecto) {
      var n = parseFloat(valor);
      if (!Number.isFinite(n)) return porDefecto;
      return /rem|em/.test(valor) ? n * 16 : n;
    };
    var px = aRem(tamano, 12);
    var pad = aRem(relleno, 3) * 2;

    ctx.font = '600 ' + px + 'px ' + fuente;
    var palabras = etiqueta.split(/\s+/);
    var mayor = 0;
    var i;
    var w;
    for (i = 0; i < palabras.length; i++) {
      w = ctx.measureText(palabras[i]).width;
      if (w > mayor) mayor = w;
    }

    // +2 px de holgura: el redondeo del colgroup y el borde de 1px del `th`.
    return Math.ceil(mayor + pad) + 2;
  }

  function buildColumnSizing() {
    var config = _activeConfig || CONSTRUCTION_DEFAULTS;
    var restrictions = config.restrictions || CONSTRUCTION_DEFAULTS.restrictions;
    var numRestrictions = restrictions.length;

    // Task 8 (2026-08-05, C-31): «Id» pasa de 36 px (su piso) a 74. Los codigos
    // jerarquicos de JMC llegan a «2.4.1.3.1», que mide 69 px; a 36 px se veian
    // «2.4» — 27 de las 28 filas visibles mostraban un id que no era el suyo.
    // «Sub-Contratista» sube su piso de 100 a 126: «CONCREACEROS» mide 122 px y
    // se cortaba a mitad de palabra en 3 filas.
    // Fixed leading columns (7): Id, Lote, Actividad, Sub, Resp, Semana, Ejecutado
    var fixedLeading = {
      min:   [76, 54, 150, 130, 130, 60, 72],
      floor: [74, 44, 120, 126, 100, 52, 64],
      max:   [96, 70, 460, 240, 240, 110, 110],
      ratio: [0.032, 0.024, 0.144, 0.08, 0.08, 0.048, 0.048],
    };

    // Fixed trailing columns (3): Estado_Restricciones, estado_operativo, Observaciones
    var fixedTrailing = {
      min:   [92, 150, 180],
      floor: [78, 118, 130],
      max:   [136, 240, 380],
      ratio: [0.05, 0.096, 0.088],
    };

    // Dynamic restriction columns: uniform sizing
    var restrictionMin = [];
    var restrictionFloor = [];
    var restrictionMax = [];
    var restrictionRatio = [];
    for (var i = 0; i < numRestrictions; i++) {
      restrictionMin.push(74);
      restrictionFloor.push(64);
      restrictionMax.push(130);
    }

    // Compute ratio budget for restriction cols (reserve 1 - fixedLeading - fixedTrailing)
    var fixedRatioSum = 0;
    for (var fi = 0; fi < fixedLeading.ratio.length; fi++) { fixedRatioSum += fixedLeading.ratio[fi]; }
    for (var ft = 0; ft < fixedTrailing.ratio.length; ft++) { fixedRatioSum += fixedTrailing.ratio[ft]; }
    var restrictionBudget = Math.max(0, 1 - fixedRatioSum);
    var perRestrictionRatio = numRestrictions > 0 ? restrictionBudget / numRestrictions : 0;
    for (var ri = 0; ri < numRestrictions; ri++) {
      restrictionRatio.push(perRestrictionRatio);
    }

    // Assemble full arrays
    columnMinWidths = fixedLeading.min.concat(restrictionMin, fixedTrailing.min);
    columnFloorWidths = fixedLeading.floor.concat(restrictionFloor, fixedTrailing.floor);
    columnMaxWidths = fixedLeading.max.concat(restrictionMax, fixedTrailing.max);
    columnWidthRatios = fixedLeading.ratio.concat(restrictionRatio, fixedTrailing.ratio);

    /* Task 8 (2026-08-05, Step 1-bis): ninguna columna baja del ancho que su
       propia cabecera necesita para leerse entera. Se aplica al PISO (el limite
       duro que usa `reduceWidthsToTarget` en el segundo pase) y de rebote al
       `min` y al `max`, para que el reparto responsivo no pueda deshacerlo.
       Ocho cabeceras de PI se recortaban en seco —«Diseños y Especificaciones»
       mostraba «Diseños y Especifi…»— y siete de ellas son etiquetas de
       restriccion que vienen de la base de datos, no del repo. */
    var cabeceras = buildColumnHeaders();
    var hc;
    var necesita;
    for (hc = 0; hc < columnFloorWidths.length; hc++) {
      necesita = anchoMinimoCabecera(cabeceras[hc]);
      if (!necesita) continue;
      if (columnFloorWidths[hc] < necesita) columnFloorWidths[hc] = necesita;
      if (columnMinWidths[hc] < necesita) columnMinWidths[hc] = necesita;
      if (columnMaxWidths[hc] < necesita) columnMaxWidths[hc] = necesita;
    }

    // Shrink priority: lower index = shrinks first; trailing Observaciones shrinks last (0)
    var totalCols = 7 + numRestrictions + 3;
    columnShrinkPriority = [];
    for (var sp = 0; sp < totalCols; sp++) {
      columnShrinkPriority.push(totalCols - 1 - sp);
    }
    // Observaciones (last col) always shrinks last
    columnShrinkPriority[totalCols - 1] = 0;
  }

  // Etiquetas visibles de cada estado. Proyeccion literal de los `label` del
  // contrato (docs/design-system/state-semantics.json, modulo
  // `programacion-intermedia`): no es una segunda fuente. Seis de ellas
  // divergian del contrato -y dos contradecian a la leyenda de la propia
  // vista, que si usaba la forma contractual-, asi que el mismo estado se
  // llamaba de dos maneras en la misma pantalla. `ops-state-contract.test.mjs`
  // lo impide desde 2026-08-11.
  var stateLabels = {
    'blocked-overdue-critical': 'RC inicio vencido',
    'blocked-overdue': 'Inicio vencido',
    'blocked-due': 'Inicio por Habilitar',
    'alert-1-week': 'Alistamiento Urgente',
    'alert-2-3-weeks': 'Alistamiento en Riesgo',
    'alert-4-6-weeks': 'Alistamiento Pendiente',
    'execution-blocked': 'En Ejecución Pendiente',
    'liberated-control': 'Listo para Comprometer',
    neutral: 'Control',
    header: 'Capítulo',
  };

  // Presentacion de cada estado, con las claves de
  // docs/design-system/state-semantics.json (modulo `programacion-intermedia`).
  // El chip declara QUE estado es -matiz para la identidad, severity+urgency
  // para la prioridad- y la capa de componentes lo pinta. Antes cada estado
  // llevaba su propia regla de color en la hoja del modulo: diez reglas que
  // repetian el mapa que ya vive en el contrato.
  //
  // El nivel viaja como el par severity+urgency y no como su nombre porque es
  // lo que `states-feedback.css` ya consumia desde antes del eje de matiz.
  //
  // Guard de que esta tabla no se desvie del contrato:
  // tests/design-system/ops-state-contract.test.mjs
  var LEVEL_ATTRS = {
    neutral: { severity: 'none', urgency: 'none' },
    healthy: { severity: 'low', urgency: 'none' },
    attention: { severity: 'medium', urgency: 'soon' },
    urgent: { severity: 'high', urgency: 'now' },
  };

  // Ocho estados, ocho matices, sin repetir. La paleta publica un solo tinte
  // por matiz, asi que dos estados que compartan matiz pintan el mismo fondo:
  // antes habia tres rojos y tres ambares aqui y `Alistamiento Urgente` y
  // `Alistamiento en Riesgo` eran bit-identicos en pantalla. La justificacion de
  // cada asignacion esta en public/css/programacion-intermedia.css.
  //
  // `neutral` (fila sin clasificar) no toma tinte de estado: usa la superficie
  // elevada, que no es un matiz.
  // Los NIVELES se revisaron uno por uno con el usuario el 2026-08-18 y cuatro
  // de los ocho cambiaron. Los matices NO se tocaron: siguen siendo los ocho del
  // catalogo, uno por estado. La procedencia de cada nivel —cual decidio el
  // usuario y cuales propuso el implementador y el confirmo— esta en
  // goals/bug-coloreado-severidad/respuestas-ds-f1.md, y se conserva a proposito.
  var statePresentation = {
    'blocked-overdue-critical': { level: 'urgent', hue: 'red' },
    'blocked-overdue': { level: 'urgent', hue: 'orange' },
    'blocked-due': { level: 'urgent', hue: 'violet' },
    'alert-1-week': { level: 'attention', hue: 'amber' },
    'alert-2-3-weeks': { level: 'attention', hue: 'teal' },
    'alert-4-6-weeks': { level: 'healthy', hue: 'neutral' },
    'execution-blocked': { level: 'urgent', hue: 'blue' },
    'liberated-control': { level: 'healthy', hue: 'green' },
    neutral: { level: 'neutral', hue: 'neutral' },
  };

  function stateChipAttrs(state) {
    var presentation = statePresentation[state];
    if (!presentation) {
      return '';
    }
    var pair = LEVEL_ATTRS[presentation.level];
    return ' data-aia-hue="' + presentation.hue + '"'
      + ' data-aia-severity="' + pair.severity + '"'
      + ' data-aia-urgency="' + pair.urgency + '"';
  }

  var columnMinWidths = [44, 54, 150, 130, 130, 60, 72, 74, 74, 74, 74, 82, 94, 88, 92, 150, 180];
  var columnFloorWidths = [36, 44, 120, 100, 100, 52, 64, 64, 64, 64, 64, 70, 80, 76, 78, 118, 130];
  var columnMaxWidths = [90, 70, 460, 240, 240, 110, 110, 120, 120, 120, 120, 130, 148, 136, 136, 240, 380];
  var columnShrinkPriority = [16, 2, 3, 4, 15, 14, 13, 11, 10, 8, 9, 7, 6, 5, 1, 12, 0];
  // La suma debe ser 1.0: colWidths ya reserva 60px para scrollbar/sidebar LPS.
  var columnWidthRatios = [0.032, 0.024, 0.144, 0.08, 0.08, 0.048, 0.048, 0.042, 0.042, 0.042, 0.042, 0.046, 0.05, 0.046, 0.05, 0.096, 0.088];

  function getDb() {
    return $('#baseDatos_PHP').val() || $('#baseDatos').val() || '';
  }

  function getSemana() {
    return $('#semana_PHP').val() || $('#semana').val() || '';
  }

  function getPermiso() {
    var permiso = String($('#permiso_canonico').val() || '').trim().toUpperCase();
    return ({ P: 'D', U: 'V' }[permiso] || permiso);
  }

  function getMaxSemana() {
    var value = parseInt($('#Max_Semana').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  function getSemanalConfirmada() {
    var value = parseInt($('#Semanal_Confirmada').val(), 10);
    return Number.isFinite(value) ? value : 0;
  }

  // Igual que en programacion_semanal/hot.js: el modulo de reglas se carga con
  // `type="module"` (diferido) mientras este archivo es un script clasico, asi
  // que sin este guard un fallo de carga del modulo tumbaria cada celda con una
  // excepcion. El fallback deniega: solo lectura, sin acciones de barra.
  var REGLAS_DENEGADAS_PI = {
    isUserAllowedToEdit: function () { return false; },
    puedeEditarCelda: function () { return false; },
  };

  function reglasIntermediaActuales() {
    var fabrica = window.AIAEnablementRules && window.AIAEnablementRules.crearReglasIntermedia;
    if (typeof fabrica !== 'function') {
      return REGLAS_DENEGADAS_PI;
    }

    return fabrica({
      permiso: getPermiso(),
      semana: getSemana(),
      maxSemana: getMaxSemana(),
      semanalConfirmada: getSemanalConfirmada(),
      editableProps: editableProps,
    });
  }

  function isUserAllowedToEdit() {
    return reglasIntermediaActuales().isUserAllowedToEdit();
  }

  function toNumber(value, fallback) {
    if (window.PIStateMachine && typeof window.PIStateMachine.toNumber === 'function') {
      return window.PIStateMachine.toNumber(value, fallback);
    }

    if (value === null || value === undefined || value === '') {
      return fallback;
    }

    var normalized = String(value).trim().replace(/\s+/g, '');
    if (normalized === '' || normalized.toLowerCase() === 'null') {
      return fallback;
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

    var parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function formatPercent(value) {
    if (String(value || '').toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(value);
    if (ratio === null) {
      return '';
    }

    return (ratio * 100).toFixed(1).replace('.', ',') + '%';
  }

  function normalizePercentRatio(value) {
    if (value === null || value === undefined || value === '') {
      return null;
    }

    var raw = String(value).trim();
    if (!raw || raw.toUpperCase() === 'N/A') {
      return null;
    }

    var numeric = toNumber(raw, null);
    if (numeric === null) {
      return null;
    }

    if (raw.indexOf('%') > -1) {
      numeric = numeric / 100;
    }

    while (numeric > 1 && numeric <= 10000) {
      numeric = numeric / 100;
    }

    if (numeric < 0) {
      numeric = 0;
    }

    if (numeric > 1) {
      numeric = 1;
    }

    return Math.round((numeric + Number.EPSILON) * 10000) / 10000;
  }

  function getAllowedRestrictionRatios(prop) {
    var r = _findRestrictionByKey(prop);
    if (r && r.options && r.options.indexOf('50%') > -1 && r.options.indexOf('33%') === -1) {
      return [0, 0.5, 1];
    }
    return [0, 0.33, 0.66, 1];
  }

  function findNearestAllowedRatio(prop, ratio) {
    var allowed = getAllowedRestrictionRatios(prop);
    var nearest = allowed[0];
    var minDiff = Math.abs(allowed[0] - ratio);

    for (var i = 1; i < allowed.length; i++) {
      var diff = Math.abs(allowed[i] - ratio);
      if (diff < minDiff) {
        minDiff = diff;
        nearest = allowed[i];
      }
    }

    return nearest;
  }

  function restrictionRatioToDisplay(ratio) {
    var percent = Math.round((ratio * 100) + Number.EPSILON);
    return String(percent) + '%';
  }

  function restrictionRatioToPayload(ratio) {
    if (ratio === 0 || ratio === 1) {
      return String(ratio);
    }

    return ratio.toString();
  }

  function normalizeRestrictionForPayload(prop, value) {
    var text = String(value === null || value === undefined ? '' : value).trim();
    if (text === '') {
      return '';
    }

    if (text.toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(text);
    if (ratio === null) {
      return null;
    }

    var nearest = findNearestAllowedRatio(prop, ratio);
    return restrictionRatioToPayload(nearest);
  }

  function areHardRestrictionsMet(row) {
    for (var i = 0; i < hardRestrictionProps.length; i++) {
      var prop = hardRestrictionProps[i];
      var payloadValue = normalizeRestrictionForPayload(prop, row[prop]);
      if (!payloadValue || payloadValue === 'N/A') {
        continue;
      }
      var numeric = toNumber(payloadValue, null);
      if (numeric === null || numeric < (hardRestrictionThresholds[prop] || 0)) {
        return false;
      }
    }
    return true;
  }

  function calculateRestrictionStateRatio(row) {
    var total = 0;
    var count = 0;

    for (var i = 0; i < restrictionProps.length; i++) {
      var prop = restrictionProps[i];
      var payloadValue = normalizeRestrictionForPayload(prop, row[prop]);

      if (!payloadValue || payloadValue === 'N/A') {
        continue;
      }

      var numeric = toNumber(payloadValue, null);
      if (numeric === null) {
        continue;
      }

      total += Math.min(numeric / (hardRestrictionThresholds[prop] || 1), 1);
      count += 1;
    }

    if (count === 0) {
      return 1;
    }

    var ratio = total / count;
    if (ratio < 0) {
      ratio = 0;
    }
    if (ratio > 1) {
      ratio = 1;
    }
    return Math.round((ratio + Number.EPSILON) * 100000) / 100000;
  }

  function getSourceRowDataByVisualRow(instance, visualRow) {
    if (!instance || !Number.isInteger(visualRow) || visualRow < 0) {
      return null;
    }

    var physicalRow = typeof instance.toPhysicalRow === 'function' ? instance.toPhysicalRow(visualRow) : visualRow;
    if (!Number.isInteger(physicalRow) || physicalRow < 0) {
      return null;
    }

    if (typeof instance.getSourceDataAtRow === 'function') {
      return instance.getSourceDataAtRow(physicalRow) || null;
    }

    return null;
  }

  function resetPIRowCaches() {
    _rowStateCache = [];
    _rowClassCache = [];
    _rowMetaCache = [];
    _stateViewCache = [];
  }

  function invalidatePIRowCache(physicalRow, rowData) {
    if (Number.isInteger(physicalRow) && physicalRow >= 0) {
      _rowStateCache[physicalRow] = undefined;
      _rowClassCache[physicalRow] = undefined;
      _rowMetaCache[physicalRow] = undefined;
      _stateViewCache[physicalRow] = undefined;
    }

    if (rowData && rowData._piStateCache) {
      delete rowData._piStateCache;
    }
  }

  function getPhysicalRowFromVisualRow(instance, visualRow) {
    if (!instance || !Number.isInteger(visualRow) || visualRow < 0) {
      return null;
    }

    var physicalRow = typeof instance.toPhysicalRow === 'function' ? instance.toPhysicalRow(visualRow) : visualRow;
    return Number.isInteger(physicalRow) && physicalRow >= 0 ? physicalRow : null;
  }

  function buildPIRowMeta(rowData, state) {
    var resolvedState = state || getState(rowData || {});
    var isHeader = resolvedState === 'header';
    var rowStateClass = isHeader ? 'pdc-header' : ('pi-state-' + resolvedState);
    var rowClass = 'pi-row-state ' + rowStateClass;

    if (normalizeSharedSelectionValue(rowData && rowData.__shared_selected) && !isHeader) {
      rowClass += ' pi-row-shared-picked';
    }
    if (parseInt(rowData && rowData.alerta_crisis, 10) === 1 && !isHeader) {
      rowClass += ' pi-row-crisis';
    }

    return {
      state: resolvedState,
      isHeader: isHeader,
      rowStateClass: rowStateClass,
      rowClass: rowClass,
      // N-1 (Task 38, 2026-08-05): sin Responsable AIA no se gestionan
      // restricciones. Antes se dejaba escribir y se revertia despues; ahora
      // la fila nace sabiendo si esta bloqueada, y `cells()` lo traduce a
      // readOnly + senal visible.
      hasResponsable: isHeader || hasAssignedValue(rowData && rowData.Responsable_AIA, PI_CREATE_PROF),
    };
  }

  function getPIRowMeta(physicalRow, rowData) {
    if (Number.isInteger(physicalRow) && physicalRow >= 0 && _rowMetaCache[physicalRow]) {
      if (window.__PI_DEBUG_COLOR && (physicalRow === 2 || physicalRow === 52 || physicalRow === 77 || physicalRow < 7)) {
        console.log('[PI-DEBUG] getPIRowMeta cache HIT:', {
          physicalRow: physicalRow,
          rowDataId: (rowData || {}).Id,
          cachedState: _rowMetaCache[physicalRow].state,
          cachedClass: _rowMetaCache[physicalRow].rowClass,
          rowClassCache: _rowClassCache[physicalRow],
        });
      }
      return _rowMetaCache[physicalRow];
    }

    var state = Number.isInteger(physicalRow) && physicalRow >= 0 ? _rowStateCache[physicalRow] : null;
    if (!state) {
      if (window.__PI_DEBUG_COLOR) {
        console.warn('[PI-DEBUG] getPIRowMeta cache miss:', {
          physicalRow: physicalRow,
          cacheLength: _rowMetaCache.length,
          rowDataId: (rowData || {}).Id,
          estado_operativo: (rowData || {}).estado_operativo,
        });
      }
      state = getState(rowData || {});
    } else if (window.__PI_DEBUG_COLOR && (physicalRow === 2 || physicalRow === 52 || physicalRow === 77 || physicalRow < 7)) {
      console.log('[PI-DEBUG] getPIRowMeta recompute:', {
        physicalRow: physicalRow,
        rowDataId: (rowData || {}).Id,
        computedState: state,
        rowClassCache: _rowClassCache[physicalRow],
      });
    }

    var meta = buildPIRowMeta(rowData || {}, state);
    if (Number.isInteger(physicalRow) && physicalRow >= 0) {
      _rowStateCache[physicalRow] = meta.state;
      _rowClassCache[physicalRow] = meta.rowClass;
      _rowMetaCache[physicalRow] = meta;
    }

    return meta;
  }

  function getCachedStateView(rowData, physicalRow) {
    if (Number.isInteger(physicalRow) && physicalRow >= 0 && _stateViewCache[physicalRow]) {
      return _stateViewCache[physicalRow];
    }

    var meta = getPIRowMeta(physicalRow, rowData || {});
    var view = getStateView(rowData || {}, meta.state);
    if (Number.isInteger(physicalRow) && physicalRow >= 0) {
      _stateViewCache[physicalRow] = view;
    }

    return view;
  }

  function getColumnBaseClass(instance, col) {
    var settings = instance && typeof instance.getSettings === 'function' ? instance.getSettings() : {};
    var columns = Array.isArray(settings.columns) ? settings.columns : [];
    return columns[col] && columns[col].className ? columns[col].className : '';
  }

  function buildPICellProperties(baseClass, prop, meta) {
    var isSharedSelector = prop === '__shared_selected';
    var isRestrictionCell = restrictionProps.indexOf(prop) > -1 && !meta.isHeader;
    var isLockedByResponsable = isRestrictionCell && meta.hasResponsable === false;
    var canEdit = reglasIntermediaActuales().puedeEditarCelda({
      prop: prop,
      esHeader: meta.isHeader,
      tieneResponsable: meta.hasResponsable,
      esRestriccion: isRestrictionCell,
    });
    var isDropdownCell = Boolean(dropdownProps[prop]) && !meta.isHeader && !isLockedByResponsable;
    var interactionClass = canEdit ? 'pi-cell-editable' : 'pi-cell-readonly';

    if (isLockedByResponsable) {
      interactionClass += ' pi-cell-locked-resp';
    }

    if (isSharedSelector) {
      interactionClass += ' pi-shared-selector';
    }
    if (isDropdownCell && canEdit) {
      interactionClass += ' pi-cell-dropdown';
    }

    return {
      className: ('htMiddle ' + baseClass + ' ' + meta.rowClass + ' ' + interactionClass).trim(),
      readOnly: !canEdit,
    };
  }

  function stripPIRowStateClasses(className) {
    return String(className || '')
      .replace(/\bpi-row-state\b/g, '')
      .replace(/\bpi-state-[^\s]+/g, '')
      .replace(/\bpdc-header\b/g, '')
      .replace(/\bpi-row-shared-picked\b/g, '')
      .replace(/\bpi-row-crisis\b/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function applyPIRowStateClass(element, rowClass) {
    if (!element || !rowClass) {
      return;
    }

    var cleanClass = stripPIRowStateClasses(element.className || '');
    element.className = (cleanClass + ' ' + rowClass).trim();
  }

  function applyRowClassesToDOM(instance) {
    if (!instance || !instance.rootElement || instance.rootElement.getClientRects().length === 0) return;
    var data = instance.getSourceData();
    if (!data) return;
    for (var i = 0; i < data.length; i++) {
      var visualRow = typeof instance.toVisualRow === 'function' ? instance.toVisualRow(i) : i;
      if (!Number.isInteger(visualRow) || visualRow < 0) continue;
      var meta = _rowMetaCache[i];
      if (!meta || !meta.rowClass) continue;
      var td = instance.getCell(visualRow, 0);
      if (!td) continue;
      var tr = td.closest ? td.closest('tr') : td.parentNode;
      if (!tr) continue;
      applyPIRowStateClass(tr, meta.rowClass);

      var cells = tr.querySelectorAll ? tr.querySelectorAll('td') : [];
      for (var col = 0; col < cells.length; col++) {
        applyPIRowStateClass(cells[col], meta.rowClass);
      }
    }
  }

  /**
   * N-1 (Task 38): al asignar (o borrar) el Responsable AIA, las celdas de
   * restriccion de esa fila deben abrirse (o cerrarse) en el acto.
   * `cells()` no se re-evalua sobre cellMeta ya cacheado, asi que aqui se
   * escribe `readOnly` y `className` directo, que es la via que ya usa el
   * modulo para saltarse ese cache.
   */
  function syncRestrictionLockForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var rowData = getSourceRowDataByVisualRow(hot, visualRow);
    if (!rowData) {
      return;
    }

    var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
    invalidatePIRowCache(physicalRow, rowData);
    var meta = getPIRowMeta(physicalRow, rowData);
    if (meta.isHeader) {
      return;
    }

    for (var i = 0; i < restrictionProps.length; i++) {
      var prop = restrictionProps[i];
      var col = typeof hot.propToCol === 'function' ? hot.propToCol(prop) : -1;
      if (!Number.isInteger(col) || col < 0) {
        continue;
      }

      var props = buildPICellProperties(getColumnBaseClass(hot, col), prop, meta);
      hot.setCellMeta(visualRow, col, 'readOnly', props.readOnly);
      hot.setCellMeta(visualRow, col, 'className', props.className);
    }

    if (typeof hot.render === 'function') {
      hot.render();
    }
  }

  function refreshCellMetaForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var colCount = typeof hot.countCols === 'function' ? hot.countCols() : 0;
    for (var col = 0; col < colCount; col++) {
      if (typeof hot.removeCellMeta === 'function') {
        try {
          hot.removeCellMeta(visualRow, col, 'className');
        } catch (e) {
          // removeCellMeta espera índice visual; si falla (e.g. el índice visual
          // no es el último que conoce HT tras filtro), lo ignoramos.
        }
      }
    }
  }

  function buildRowClassCache(data) {
    var rows = Array.isArray(data) ? data : [];
    _rowStateCache = new Array(rows.length);
    _rowClassCache = new Array(rows.length);
    _rowMetaCache = new Array(rows.length);
    _stateViewCache = new Array(rows.length);

    for (var i = 0; i < rows.length; i++) {
      var rowData = rows[i] || {};
      var state = getState(rowData);
      if (window.__PI_DEBUG_COLOR) {
        console.log('[PI-DEBUG] buildRowClassCache[' + i + ']:', {
          Id: rowData.Id,
          Semanas_Inicio: rowData.Semanas_Inicio,
          Ejecutado: rowData.Ejecutado,
          D_y_E: rowData.D_y_E,
          Materiales: rowData.Materiales,
          MdeO: rowData.MdeO,
          Equipos: rowData.Equipos,
          Predecesora: rowData.Predecesora,
          isReadyToCommit: window.PIStateMachine.isReadyToCommit(rowData),
          state: state,
          estado_operativo_label: rowData.estado_operativo,
        });
      }
      var meta = buildPIRowMeta(rowData, state);
      _rowStateCache[i] = state;
      _rowClassCache[i] = meta.rowClass;
      _rowMetaCache[i] = meta;
      _stateViewCache[i] = getStateView(rowData, state);
    }
  }

  function hasAssignedValue(value, createPlaceholder) {
    var normalized = String(value === null || value === undefined ? '' : value).trim();
    return normalized !== '' && normalized !== createPlaceholder;
  }

  function recalculateRestrictionStateForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var rowData = getSourceRowDataByVisualRow(hot, visualRow);
    if (!rowData) {
      return;
    }

    var ratio = calculateRestrictionStateRatio(rowData);
    var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
    rowData.Estado_Restricciones = ratio;
    invalidatePIRowCache(physicalRow, rowData);
    hot.setDataAtRowProp(visualRow, 'Estado_Restricciones', ratio, 'internal-update');
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getActividadPlainText(value) {
    var raw = String(value === null || value === undefined ? '' : value);
    if (!raw) {
      return '';
    }

    var container = document.createElement('div');
    container.innerHTML = raw;
    return String(container.textContent || container.innerText || '').trim();
  }

  function sanitizeActividadHtml(value) {
    var raw = String(value === null || value === undefined ? '' : value);
    if (!raw) {
      return '';
    }

    var container = document.createElement('div');
    container.innerHTML = raw;

    var allowed = {
      B: true,
      STRONG: true,
      SMALL: true,
      BR: true,
    };

    function walkNode(node) {
      if (!node) {
        return '';
      }

      if (node.nodeType === 3) {
        return escapeHtml(node.nodeValue || '');
      }

      if (node.nodeType !== 1) {
        return '';
      }

      var tag = String(node.tagName || '').toUpperCase();
      if (tag === 'BR') {
        return '<br>';
      }

      var children = '';
      for (var i = 0; i < node.childNodes.length; i++) {
        children += walkNode(node.childNodes[i]);
      }

      if (allowed[tag]) {
        var safeTag = tag.toLowerCase();
        return '<' + safeTag + '>' + children + '</' + safeTag + '>';
      }

      return children;
    }

    var output = '';
    for (var j = 0; j < container.childNodes.length; j++) {
      output += walkNode(container.childNodes[j]);
    }

    return output;
  }


  function getState(row) {
    if (window.PIStateMachine && typeof window.PIStateMachine.getState === 'function') {
      return window.PIStateMachine.getState(row || {});
    }
    return 'neutral';
  }

  function getStateLabel(row, state) {
    var resolvedState = state || getState(row);
    return stateLabels[resolvedState] || 'Control';
  }

  function getReadinessAction(prop, value) {
    var config = readinessActionMatrix[prop];
    if (!config) {
      return '';
    }

    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (raw.toUpperCase() === 'N/A' || raw.toUpperCase() === 'NO APLICA') {
      return '';
    }

    var ratio = raw === '' ? 0 : normalizePercentRatio(raw);
    if (ratio === null || ratio + 0.0001 >= config.threshold) {
      return '';
    }

    for (var i = 0; i < config.actions.length; i++) {
      if (ratio < config.actions[i].max) {
        return config.actions[i].text;
      }
    }

    return config.actions.length ? config.actions[config.actions.length - 1].text : '';
  }

  function getReadinessActions(row) {
    return getReadinessActionItems(row).map(function (item) { return item.text; });
  }

  function getReadinessActionItems(row, state) {
    var items = [];
    if (!row || (state || getState(row)) === 'header') {
      return items;
    }

    for (var i = 0; i < readinessActionProps.length; i++) {
      var prop = readinessActionProps[i];
      var value = row[prop];
      var action = getReadinessAction(prop, value);
      if (action) {
        items.push({
          key: prop,
          label: readinessActionLabels[prop] || prop,
          text: action,
          value: formatPercent(value || 0) || '0,0%',
        });
      }
    }

    return items;
  }

  function getStateView(row, state) {
    var resolvedState = state || getState(row);
    var actionItems = getReadinessActionItems(row, resolvedState);
    return {
      label: getStateLabel(row, resolvedState),
      state: resolvedState,
      actions: actionItems.map(function (item) { return item.text; }),
      actionItems: actionItems,
      activity: getActividadPlainText(row && row.Actividad),
      id: row && row.Id,
    };
  }

  function getStateDisplay(row) {
    var view = getStateView(row);
    if (!view.actions.length) {
      return view.label;
    }
    return view.label + '\nAcciones de habilitación: ' + view.actions.join('; ');
  }

  function renderStatePills(actionItems, visibleLimit) {
    var items = Array.isArray(actionItems) ? actionItems : [];
    var limit = visibleLimit || 2;
    var html = '';
    for (var i = 0; i < Math.min(items.length, limit); i++) {
      html += '<span class="ops-state-pill" title="' + escapeHtml(items[i].text) + '">' + escapeHtml(items[i].label) + '</span>';
    }
    if (items.length > limit) {
      html += '<span class="ops-state-more">+' + (items.length - limit) + '</span>';
    }
    return html;
  }

  function renderOperationalStateCell(view) {
    var pills = view.actionItems.length > 0 ? '<span class="ops-state-pills">' + renderStatePills(view.actionItems, 2) + '</span>' : '';

    return '<button type="button" class="ops-state-zoom" aria-label="Ver detalle operativo">'
      + '<span class="ops-state-topline"><span class="ops-state-chip"' + stateChipAttrs(view.state) + '>'
      + escapeHtml(view.label) + '</span></span>'
      + pills
      + '</button>';
  }

  function ensureOperationalStateDrawer() {
    if (document.getElementById('piOperationalStateDrawer')) {
      return $('#piOperationalStateDrawer');
    }

    var html = '<div id="piOperationalStateDrawer" class="ops-state-drawer" aria-hidden="true">'
      + '<div class="ops-state-backdrop" data-ops-close="1"></div>'
      + '<aside class="ops-state-panel" role="dialog" aria-modal="false" aria-labelledby="piOpsDrawerTitle">'
      + '<div class="ops-state-panel-header">'
      + '<div><span class="ops-state-eyebrow">Detalle operativo</span><h5 id="piOpsDrawerTitle">Estado operativo</h5></div>'
      + '<button type="button" class="ops-state-close" data-ops-close="1" aria-label="Cerrar">&times;</button>'
      + '</div>'
      + '<div class="ops-state-panel-body"></div>'
      + '</aside>'
      + '</div>';
    $('body').append(html);
    return $('#piOperationalStateDrawer');
  }

  function renderOperationalStateDrawerBody(view) {
    var activity = view.activity || 'Actividad';
    var id = view.id ? ('<span class="ops-state-activity-id">' + escapeHtml(view.id) + '</span>') : '';
    var html = '<div class="ops-state-drawer-state"><span class="ops-state-chip"'
      + stateChipAttrs(view.state) + '>' + escapeHtml(view.label) + '</span>';
    if (view.actionItems.length) {
      html += '<span class="ops-state-count">' + view.actionItems.length + ' acciones</span>';
    }
    html += '</div>';
    html += '<div class="ops-state-activity">' + id + '<strong>' + escapeHtml(activity) + '</strong></div>';

    if (!view.actionItems.length) {
      html += '<div class="ops-state-empty-detail">Sin acciones de habilitación pendientes.</div>';
      return html;
    }

    html += '<h6>Acciones de habilitación</h6><ul class="ops-state-action-list">';
    for (var i = 0; i < view.actionItems.length; i++) {
      var item = view.actionItems[i];
      html += '<li><span class="ops-state-action-label">' + escapeHtml(item.label) + '</span>'
        + '<span class="ops-state-action-text">' + escapeHtml(item.text) + '</span>'
        + '<span class="ops-state-action-value">' + escapeHtml(item.value || '') + '</span></li>';
    }
    html += '</ul>';
    return html;
  }

  function openOperationalStateDrawer(rowData) {
    var view = getStateView(rowData || {});
    var $drawer = ensureOperationalStateDrawer();
    $drawer.find('#piOpsDrawerTitle').text(view.label);
    $drawer.find('.ops-state-panel-body').html(renderOperationalStateDrawerBody(view));
    $drawer.addClass('is-open').attr('aria-hidden', 'false');
  }

  function closeOperationalStateDrawer() {
    $('#piOperationalStateDrawer').removeClass('is-open').attr('aria-hidden', 'true');
  }

  function bindOperationalStateDrawer() {
    $('#hot-container').off('click.piOpsState').on('click.piOpsState', '.ops-state-zoom', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var visualRow = parseInt($(this).data('row'), 10);
      var rowData = null;
      if (hot && Number.isInteger(visualRow) && visualRow >= 0) {
        rowData = getSourceRowDataByVisualRow(hot, visualRow);
      }
      openOperationalStateDrawer(rowData || {});
    });

    $(document)
      .off('click.piOpsStateClose')
      .on('click.piOpsStateClose', '#piOperationalStateDrawer [data-ops-close="1"]', closeOperationalStateDrawer)
      .off('keydown.piOpsStateClose')
      .on('keydown.piOpsStateClose', function (event) {
        if (event.key === 'Escape') {
          closeOperationalStateDrawer();
        }
      });
  }

  function isHalfRestrictionType(restrictionType) {
    var r = _findRestrictionByKey(restrictionType);
    return r && r.options && r.options.indexOf('50%') > -1 && r.options.indexOf('33%') === -1;
  }

  function getSharedValueOptionsForType(restrictionType) {
    var r = _findRestrictionByKey(restrictionType);
    if (r && r.options && r.options.length > 0) {
      return r.options;
    }
    return isHalfRestrictionType(restrictionType) ? ['0%', '50%', '100%', 'N/A'] : ['0%', '33%', '66%', '100%', 'N/A'];
  }

  function getRowActivityId(row) {
    if (!row) {
      return '';
    }

    var candidate = row.unique_id || row.Consecutivo_en_Programa;
    if (candidate === null || candidate === undefined || candidate === '') {
      candidate = row.Id;
    }

    var id = String(candidate === null || candidate === undefined ? '' : candidate).trim();
    return /^\d+$/.test(id) ? id : '';
  }

  function normalizeSharedSelectionValue(value) {
    return value === true || value === 1 || value === '1' || value === 'true' || value === 'TRUE';
  }

  function rebuildSharedSelectionIndex() {
    var nextIndex = {};

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      var id = getRowActivityId(row);
      var selected = normalizeSharedSelectionValue(row.__shared_selected);
      row.__shared_selected = selected;

      if (id && selected) {
        nextIndex[id] = true;
      }
    }

    sharedSelectionIndex = nextIndex;
  }

  function getMarkedActivityIds() {
    var ids = [];

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      if (!normalizeSharedSelectionValue(row.__shared_selected)) {
        continue;
      }

      var id = getRowActivityId(row);
      if (!id || ids.indexOf(id) > -1) {
        continue;
      }

      ids.push(id);
    }

    return ids;
  }

  function getVisibleActivityIds() {
    var ids = [];

    if (hot && typeof hot.countRows === 'function') {
      var visualRowCount = hot.countRows();
      for (var visualRow = 0; visualRow < visualRowCount; visualRow++) {
        var rowData = getSourceRowDataByVisualRow(hot, visualRow);
        var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
        if (!rowData || getPIRowMeta(physicalRow, rowData).state === 'header') {
          continue;
        }

        var id = getRowActivityId(rowData);
        if (!id || ids.indexOf(id) > -1) {
          continue;
        }

        ids.push(id);
      }

      return ids;
    }

    for (var rowIndex = 0; rowIndex < masterData.length; rowIndex++) {
      if (!rowMatchesFilters(masterData[rowIndex]) || getState(masterData[rowIndex]) === 'header') {
        continue;
      }

      var id = getRowActivityId(masterData[rowIndex]);
      if (!id || ids.indexOf(id) > -1) {
        continue;
      }
      ids.push(id);
    }

    return ids;
  }

  function updateSharedSelectionCountIndicator() {
    var selectedCount = getMarkedActivityIds().length;
    var $indicator = $('#shared-selection-count');

    if (!$indicator.length) {
      return;
    }

    $indicator.text(selectedCount + ' selec.');
    $indicator.toggleClass('badge-secondary', selectedCount === 0);
    $indicator.toggleClass('badge-primary', selectedCount > 0);

    var $info = $('#piSharedSelectionInfo');
    if ($info.length) {
      $info.text('Marcadas: ' + selectedCount + ' | Visibles: ' + getVisibleActivityIds().length);
    }
  }

  function syncSharedSelectionFromRow(rowData, selected) {
    if (!rowData) {
      return;
    }

    var isSelected = normalizeSharedSelectionValue(selected);
    rowData.__shared_selected = isSelected;

    var id = getRowActivityId(rowData);
    if (!id) {
      return;
    }

    if (isSelected) {
      sharedSelectionIndex[id] = true;
    } else {
      delete sharedSelectionIndex[id];
    }
  }

  function updateSharedSelectionFromVisualRow(visualRow, selected) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }

    var rowData = getSourceRowDataByVisualRow(hot, visualRow);
    if (!rowData || getState(rowData) === 'header') {
      return;
    }

    syncSharedSelectionFromRow(rowData, selected);
    invalidatePIRowCache(getPhysicalRowFromVisualRow(hot, visualRow), rowData);
    refreshCellMetaForVisualRow(visualRow);
    hot.render();
    updateSharedSelectionCountIndicator();
  }

  function applySharedSelectionToIds(activityIds, selected) {
    var ids = parseActivityIdsInput(activityIds);
    if (ids.length === 0) {
      return 0;
    }

    var idSet = {};
    for (var i = 0; i < ids.length; i++) {
      idSet[ids[i]] = true;
    }

    var isSelected = Boolean(selected);
    var changed = 0;

    for (var rowIndex = 0; rowIndex < masterData.length; rowIndex++) {
      var row = masterData[rowIndex] || {};
      var id = getRowActivityId(row);
      if (!id || !idSet[id] || getState(row) === 'header') {
        continue;
      }

      if (normalizeSharedSelectionValue(row.__shared_selected) === isSelected) {
        continue;
      }

      syncSharedSelectionFromRow(row, isSelected);
      changed += 1;
    }

    if (hot) {
      resetPIRowCaches();
      hot.render();
    }

    updateSharedSelectionCountIndicator();
    return changed;
  }

  function selectVisibleRowsForSharedConstraint() {
    var visibleIds = getVisibleActivityIds();
    if (visibleIds.length === 0) {
      showFeedback('error', 'No hay filas visibles para seleccionar.');
      return;
    }

    var changed = applySharedSelectionToIds(visibleIds, true);
    showFeedback('success', 'Visibles marcadas: ' + visibleIds.length + ' (nuevas: ' + changed + ')');
  }

  function clearSharedSelection() {
    var hadSelection = false;

    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      if (!normalizeSharedSelectionValue(row.__shared_selected)) {
        continue;
      }

      row.__shared_selected = false;
      hadSelection = true;
    }

    if (hadSelection) {
      sharedSelectionIndex = {};
      if (hot) {
        resetPIRowCaches();
        hot.render();
      }
    }

    updateSharedSelectionCountIndicator();
    showFeedback('success', hadSelection ? 'Seleccion de lote limpiada' : 'No habia seleccion activa');
  }

  function normalizeRestrictionValue(prop, value) {
    var text = String(value === null || value === undefined ? '' : value).trim();
    if (text === '') {
      return '';
    }

    if (text.toUpperCase() === 'N/A') {
      return 'N/A';
    }

    var ratio = normalizePercentRatio(text);
    if (ratio === null) {
      return null;
    }

    var nearest = findNearestAllowedRatio(prop, ratio);
    return restrictionRatioToDisplay(nearest);
  }

  function parseActivityIdsInput(rawValue) {
    var raw = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();
    if (!raw) {
      return [];
    }

    var tokens = raw.split(/[\s,;\n\r]+/);
    var ids = [];

    for (var i = 0; i < tokens.length; i++) {
      var token = String(tokens[i] || '').trim();
      if (!token || !/^\d+$/.test(token)) {
        continue;
      }

      if (ids.indexOf(token) === -1) {
        ids.push(token);
      }
    }

    return ids;
  }

  function collectHighlightedActivityIds() {
    if (!hot || typeof hot.getSelectedRange !== 'function') {
      return [];
    }

    var ranges = hot.getSelectedRange() || [];
    var ids = [];

    for (var rangeIndex = 0; rangeIndex < ranges.length; rangeIndex++) {
      var range = ranges[rangeIndex];
      if (!range || !range.from || !range.to) {
        continue;
      }

      var fromRow = Math.min(range.from.row, range.to.row);
      var toRow = Math.max(range.from.row, range.to.row);

      for (var visualRow = fromRow; visualRow <= toRow; visualRow++) {
        var rowData = getSourceRowDataByVisualRow(hot, visualRow);
        if (!rowData || getState(rowData) === 'header') {
          continue;
        }

        var id = getRowActivityId(rowData);
        if (!id || ids.indexOf(id) > -1) {
          continue;
        }

        ids.push(id);
      }
    }

    return ids;
  }

  function collectSelectedActivityIds() {
    var markedIds = getMarkedActivityIds();
    var highlightedIds = collectHighlightedActivityIds();

    if (markedIds.length === 0) {
      return highlightedIds;
    }

    if (highlightedIds.length === 0) {
      return markedIds;
    }

    var merged = markedIds.slice();
    for (var i = 0; i < highlightedIds.length; i++) {
      if (merged.indexOf(highlightedIds[i]) === -1) {
        merged.push(highlightedIds[i]);
      }
    }

    return merged;
  }

  function populateSharedRestrictionValueSelect($select, restrictionType, keepCurrent) {
    if (!$select || !$select.length) {
      return;
    }

    var optionsForType = getSharedValueOptionsForType(restrictionType);
    var current = String($select.val() || '').trim();
    var selectedValue = optionsForType[0] || '';

    if (keepCurrent && optionsForType.indexOf(current) > -1) {
      selectedValue = current;
    }

    $select.empty();
    for (var i = 0; i < optionsForType.length; i++) {
      var value = optionsForType[i];
      $select.append($('<option></option>').val(value).text(value));
    }

    $select.val(selectedValue);
  }

  function populateSharedRestrictionGrid() {
    $('.pi-shared-restriction-value').each(function () {
      var $select = $(this);
      populateSharedRestrictionValueSelect($select, String($select.data('restrictionType') || ''), true);
    });
  }

  function syncSharedRestrictionRows() {
    var applyRestriction = $('#piSharedApplyRestriction').is(':checked');

    $('.pi-shared-restriction-check').each(function () {
      var $check = $(this);
      var restrictionType = String($check.data('restrictionType') || '').trim();
      var checked = $check.is(':checked');
      var $row = $('[data-restriction-row="' + restrictionType + '"]');
      var $select = $('.pi-shared-restriction-value[data-restriction-type="' + restrictionType + '"]');

      $check.prop('disabled', !applyRestriction);
      $select.prop('disabled', !applyRestriction || !checked);
      $row.toggleClass('is-disabled', !applyRestriction || !checked);
    });
  }

  function setSharedRestrictionSelection(selected) {
    $('.pi-shared-restriction-check').prop('checked', Boolean(selected));
    syncSharedRestrictionRows();
    renderSharedPreviewEmpty('Restricciones actualizadas. Pulse "Ver Conflictos" para validar impacto.');
  }

  function normalizeSharedRestrictionList(rawValue) {
    var raw = rawValue;
    var parsed = [];

    if (typeof raw === 'string') {
      var text = raw.trim();
      if (!text) {
        return [];
      }
      try {
        raw = JSON.parse(text);
      } catch (_err) {
        return [];
      }
    }

    if (!Array.isArray(raw)) {
      return [];
    }

    for (var i = 0; i < raw.length; i++) {
      var item = raw[i] || {};
      var type = String(item.type || item.restriction_type || '').trim();
      var value = item.value !== undefined ? item.value : item.target_value;
      value = String(value === null || value === undefined ? '' : value).trim();

      if (!type || restrictionProps.indexOf(type) === -1) {
        continue;
      }

      parsed.push({ type: type, value: value });
    }

    return parsed;
  }

  function collectSharedRestrictions(requireValue) {
    var restrictions = [];

    $('.pi-shared-restriction-check:checked').each(function () {
      var type = String($(this).data('restrictionType') || '').trim();
      var value = String($('.pi-shared-restriction-value[data-restriction-type="' + type + '"]').val() || '').trim();

      if (!type || restrictionProps.indexOf(type) === -1) {
        return;
      }

      if (requireValue && !value) {
        restrictions = null;
        return false;
      }

      restrictions.push({ type: type, value: value });
    });

    if (restrictions === null) {
      return { valid: false, error: 'Seleccione valor objetivo en todas las restricciones marcadas.', items: [] };
    }

    if (restrictions.length === 0) {
      return { valid: false, error: 'Seleccione al menos una restricción para aplicar.', items: [] };
    }

    return { valid: true, error: '', items: restrictions };
  }

  function getSharedRestrictionsFromPreview(content, opts) {
    var restrictions = normalizeSharedRestrictionList(opts && opts.restrictions !== undefined ? opts.restrictions : null);
    if (restrictions.length > 0) {
      return restrictions;
    }

    restrictions = normalizeSharedRestrictionList(content && content.restrictions !== undefined ? content.restrictions : null);
    if (restrictions.length > 0) {
      return restrictions;
    }

    var fallbackType = String((content && content.restriction_type) || (opts && opts.restriction_type) || '').trim();
    var fallbackValue = (opts && opts.target_value !== undefined) ? opts.target_value : (content && content.target_value !== undefined ? content.target_value : '');
    if (fallbackType && restrictionProps.indexOf(fallbackType) > -1) {
      return [{ type: fallbackType, value: String(fallbackValue === null || fallbackValue === undefined ? '' : fallbackValue).trim() }];
    }

    return [];
  }

  function getSharedRestrictionSummary(restrictions) {
    if (!Array.isArray(restrictions) || restrictions.length === 0) {
      return 'Sin cambio';
    }

    if (restrictions.length === 1) {
      var single = restrictions[0] || {};
      return (restrictionTypeLabels[single.type] || single.type || '-') + ' a ' + getPreviewValueLabel(single.value);
    }

    return restrictions.length + ' restricciones';
  }

  function renderSharedRestrictionChanges(item, restrictions) {
    var actualValues = item && item.restricciones_actuales ? item.restricciones_actuales : {};
    var html = '<div class="pi-shared-restriction-changes">';

    for (var i = 0; i < restrictions.length; i++) {
      var target = restrictions[i] || {};
      var type = target.type;
      var label = restrictionTypeLabels[type] || type || '-';
      var fallbackValue = item && item.valor_actual !== undefined ? item.valor_actual : '';
      var currentValue = actualValues[type] !== undefined ? actualValues[type] : fallbackValue;
      var targetValue = target.value;
      var deltaInfo = getPreviewDeltaInfo(currentValue, targetValue);

      html += '<div class="pi-shared-restriction-change">';
      html += '<strong>' + escapeHtml(label) + ':</strong> ';
      html += escapeHtml(getPreviewValueLabel(currentValue)) + ' → ' + escapeHtml(getPreviewValueLabel(targetValue)) + ' ';
      html += '<span class="pi-shared-delta ' + escapeHtml(deltaInfo.className) + '">' + escapeHtml(deltaInfo.label) + '</span>';
      html += '</div>';
    }

    html += '</div>';
    return html;
  }

  function appendSharedSelectOptions($select, values, placeholder, excludedValue) {
    if (!$select || !$select.length) {
      return;
    }

    var current = String($select.val() || '').trim();
    var seen = {};
    $select.empty().append($('<option></option>').val('').text(placeholder));

    for (var i = 0; i < values.length; i++) {
      var value = String(values[i] || '').trim();
      if (!value || value === excludedValue || seen[value]) {
        continue;
      }

      seen[value] = true;
      $select.append($('<option></option>').val(value).text(value));
    }

    if (current && seen[current]) {
      $select.val(current);
    } else {
      $select.val('');
    }
  }

  function populateSharedAssignmentOptions() {
    appendSharedSelectOptions($('#piSharedSubContratista'), subcontratistas, 'Sin cambio de Sub-Contratista', PI_CREATE_SUB);
    appendSharedSelectOptions($('#piSharedResponsableAIA'), profesionales, 'Sin cambio de Responsable AIA', PI_CREATE_PROF);
  }

  function syncSharedOperationControls() {
    var applyRestriction = $('#piSharedApplyRestriction').is(':checked');
    var applyAssignments = $('#piSharedApplyAssignments').is(':checked');

    syncSharedRestrictionRows();
    $('#piSharedAssignmentsFields').toggleClass('d-none', !applyAssignments);
    $('#piSharedSubContratista, #piSharedResponsableAIA').prop('disabled', !applyAssignments);

    if (!applyAssignments) {
      $('#piSharedSubContratista, #piSharedResponsableAIA').val('');
    }
  }

  function resolvePreviewFlag(content, opts, key, defaultValue) {
    if (opts && opts[key] !== undefined) {
      return normalizeSharedSelectionValue(opts[key]);
    }

    if (content && content[key] !== undefined) {
      return normalizeSharedSelectionValue(content[key]);
    }

    return Boolean(defaultValue);
  }

  function getPreviewValueLabel(value) {
    var formatted = formatPercent(value);
    if (formatted) {
      return formatted;
    }

    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (!raw) {
      return '-';
    }

    return raw;
  }

  function getPreviewDeltaInfo(currentValue, targetValue) {
    var currentRatio = normalizePercentRatio(currentValue);
    var targetRatio = normalizePercentRatio(targetValue);

    if (currentRatio !== null && targetRatio !== null) {
      var diff = (targetRatio - currentRatio) * 100;
      if (Math.abs(diff) < 0.05) {
        return {
          label: '0,0 pp',
          className: 'pi-shared-delta-neutral',
        };
      }

      var rounded = Math.round((diff + Number.EPSILON) * 10) / 10;
      var sign = rounded > 0 ? '+' : '';

      return {
        label: sign + rounded.toFixed(1).replace('.', ',') + ' pp',
        className: rounded > 0 ? 'pi-shared-delta-up' : 'pi-shared-delta-down',
      };
    }

    if (String(getPreviewValueLabel(currentValue)) === String(getPreviewValueLabel(targetValue))) {
      return {
        label: 'Sin cambio',
        className: 'pi-shared-delta-neutral',
      };
    }

    return {
      label: 'Ajuste',
      className: 'pi-shared-delta-neutral',
    };
  }

  function getCurrentSharedConfigKey() {
    var applyRestriction = $('#piSharedApplyRestriction').is(':checked') ? 1 : 0;
    var applyAssignments = $('#piSharedApplyAssignments').is(':checked') ? 1 : 0;
    var restrictions = [];
    if (applyRestriction) {
      $('.pi-shared-restriction-check:checked').each(function () {
        var $row = $(this).closest('.pi-shared-restriction-row');
        var value = $row.find('.pi-shared-restriction-value').val() || '';
        restrictions.push($(this).data('restriction-type') + '=' + value);
      });
    }
    var ids = parseActivityIdsInput($('#piSharedActivityIds').val()).join(',');
    var sub = String($('#piSharedSubContratista').val() || '').trim();
    var resp = String($('#piSharedResponsableAIA').val() || '').trim();
    var note = String($('#piSharedNote').val() || '').trim();
    return [applyRestriction, applyAssignments, restrictions.sort().join('|'), ids, sub, resp, note].join('###');
  }

  function computeSharedConflicts(previewRows, applyAssignments, targetSub, targetResp) {
    var subConflicts = 0;
    var respConflicts = 0;
    var rows = Array.isArray(previewRows) ? previewRows : [];
    if (!applyAssignments) {
      return { sub: 0, resp: 0, total: 0, hasAny: false };
    }
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i] || {};
      if (targetSub) {
        var cur = String(row.sub_contratista_actual == null ? '' : row.sub_contratista_actual).trim();
        if (cur && cur !== targetSub) {
          subConflicts++;
        }
      }
      if (targetResp) {
        var curR = String(row.responsable_aia_actual == null ? '' : row.responsable_aia_actual).trim();
        if (curR && curR !== targetResp) {
          respConflicts++;
        }
      }
    }
    return {
      sub: subConflicts,
      resp: respConflicts,
      total: subConflicts + respConflicts,
      hasAny: (subConflicts + respConflicts) > 0,
    };
  }

  function getPreviewRelevance() {
    var applyAssignments = !!$('#piSharedApplyAssignments').is(':checked');

    if (!applyAssignments) {
      return 'no-assignments';
    }

    var currentKey = getCurrentSharedConfigKey();
    if (lastSharedPreviewKey === null) {
      return 'no-preview';
    }
    if (lastSharedPreviewKey !== currentKey) {
      return 'stale';
    }
    var stats = lastSharedPreviewStats || {};
    var conflicts = stats.conflicts || { total: 0 };
    return conflicts.total > 0 ? 'fresh-with-conflicts' : 'fresh-no-conflicts';
  }

  function buildApplyConfirmationContent(relevance, requestData, stats) {
    var activityIds = Array.isArray(requestData.activity_ids) ? requestData.activity_ids.length : 0;
    var semana = requestData.semana || '';
    var sub = String(requestData.sub_contratista || '').trim();
    var resp = String(requestData.responsable_aia || '').trim();
    var conflicts = (stats && stats.conflicts) || { sub: 0, resp: 0 };
    var actividadLabel = activityIds === 1 ? 'actividad' : 'actividades';

    if (relevance === 'no-assignments') {
      return {
        title: 'Aplicar restricción compartida',
        html: 'Se aplicar' + (activityIds === 1 ? 'á' : 'án') + ' las restricciones marcadas a <b>' + activityIds + ' ' + actividadLabel + '</b> en la semana <b>' + escapeHtml(String(semana)) + '</b>. Sub-Contratista y Responsable AIA <b>no se modificarán</b>.<br><br>¿Continuar?',
        icon: 'info',
      };
    }

    if (relevance === 'fresh-with-conflicts') {
      var parts = [];
      if (sub && conflicts.sub > 0) {
        parts.push('<li><b>' + conflicts.sub + '</b> con Sub-Contratista distinto a "' + escapeHtml(sub) + '"</li>');
      }
      if (resp && conflicts.resp > 0) {
        parts.push('<li><b>' + conflicts.resp + '</b> con Responsable AIA distinto a "' + escapeHtml(resp) + '"</li>');
      }
      var html = 'Vas a <b>unificar Sub-Contratista y/o Responsable AIA</b> que antes eran diferentes. Esto reemplazará los valores actuales en <b>' + activityIds + ' ' + actividadLabel + '</b>:<br>';
      html += '<ul style="text-align:left;margin:8px 0 0 18px;">';
      html += parts.join('');
      html += '</ul>';
      html += '<br><small style="color:var(--aia-text-muted, #666);">Esta acción no se puede deshacer desde este modal.</small><br><br>¿Continuar de todos modos?';
      return { title: 'Conflictos de asignación', html: html, icon: 'warning' };
    }

    if (relevance === 'fresh-no-conflicts') {
      return {
        title: 'Aplicar restricción compartida',
        html: 'Se aplicar' + (activityIds === 1 ? 'á' : 'án') + ' la configuración a <b>' + activityIds + ' ' + actividadLabel + '</b> en la semana <b>' + escapeHtml(String(semana)) + '</b>.<br><br>Sub-Contratista y Responsable AIA <b>todas las actividades</b> ya tienen el mismo valor objetivo. No habrá cambios en asignaciones.<br><br>¿Continuar?',
        icon: 'info',
      };
    }

    if (relevance === 'stale') {
      return {
        title: 'Preview desactualizado',
        html: 'La configuración cambió después de la última validación de conflictos (botón <b>"Ver Conflictos"</b>).<br><br>Se aplicar' + (activityIds === 1 ? 'á' : 'án') + ' los nuevos valores a <b>' + activityIds + ' ' + actividadLabel + '</b> <b>sin re-validar</b>.<br><br>¿Continuar sin re-validar, o cancelar para ejecutar "Ver Conflictos" primero?',
        icon: 'warning',
        confirmText: 'Continuar sin re-validar',
        cancelText: 'Cancelar',
      };
    }

    return {
      title: 'No se validaron conflictos',
      html: 'No se ejecutó <b>"Ver Conflictos"</b> antes de aplicar.<br><br>Se modificar' + (activityIds === 1 ? 'á' : 'án') + ' <b>' + activityIds + ' ' + actividadLabel + '</b> con la configuración actual. Si hay Sub-Contratistas o Responsables AIA distintos, <b>también se sobreescribirán</b>.<br><br>¿Continuar de todos modos, o cancelar para ejecutar "Ver Conflictos" primero?',
      icon: 'warning',
      confirmText: 'Continuar de todos modos',
      cancelText: 'Cancelar',
    };
  }

  function renderSharedPreviewEmpty(message) {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    $preview.html('<div class="pi-shared-empty">' + escapeHtml(message || 'Seleccione filas y pulse "Ver Conflictos".') + '</div>');
  }

  function renderSharedPreviewLoading() {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    $preview.html('<div class="pi-shared-loading"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Calculando conflictos de asignación...</div>');
  }

  function renderSharedPreview(content, options) {
    var $preview = $('#piSharedPreview');
    if (!$preview.length) {
      return;
    }

    if (!content) {
      renderSharedPreviewEmpty('Seleccione filas y pulse "Ver Conflictos".');
      return;
    }

    var opts = options || {};
    var restrictions = getSharedRestrictionsFromPreview(content, opts);
    var applyRestriction = resolvePreviewFlag(content, opts, 'apply_restriction', true) && restrictions.length > 0;
    var applyAssignments = resolvePreviewFlag(content, opts, 'apply_assignments', false);
    var targetSubContratista = String((opts.sub_contratista !== undefined) ? opts.sub_contratista : (content.sub_contratista || '')).trim();
    var targetResponsableAia = String((opts.responsable_aia !== undefined) ? opts.responsable_aia : (content.responsable_aia || '')).trim();
    var updateSubContratista = applyAssignments && targetSubContratista !== '';
    var updateResponsableAia = applyAssignments && targetResponsableAia !== '';
    var countTotal = Number(content.count_total || 0);
    var countFound = Number(content.count_found || 0);
    var countMissing = Number(content.count_missing || 0);
    var missingIds = Array.isArray(content.missing_ids) ? content.missing_ids : [];
    var previewRows = Array.isArray(content.preview) ? content.preview : [];
    var coverage = countTotal > 0 ? Math.round((countFound / countTotal) * 100) : 0;
    var conflicts = computeSharedConflicts(previewRows, applyAssignments, targetSubContratista, targetResponsableAia);

    if (coverage < 0) {
      coverage = 0;
    }
    if (coverage > 100) {
      coverage = 100;
    }

    var targetSummary = 'Sin cambio';
    if (applyRestriction) {
      var targetParts = [];
      for (var targetIndex = 0; targetIndex < restrictions.length; targetIndex++) {
        var target = restrictions[targetIndex] || {};
        targetParts.push((restrictionTypeLabels[target.type] || target.type || '-') + ': ' + getPreviewValueLabel(target.value));
      }
      targetSummary = targetParts.join(' | ');
    }

    var html = '<div class="pi-shared-preview-shell">';
    html += '<div class="pi-shared-kpis">';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Restricciones</span><span class="pi-shared-kpi-value">' + escapeHtml(applyRestriction ? getSharedRestrictionSummary(restrictions) : 'Sin cambio') + '</span></div>';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Objetivos</span><span class="pi-shared-kpi-value">' + escapeHtml(targetSummary) + '</span></div>';
    if (updateSubContratista) {
      html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Sub-Contratista</span><span class="pi-shared-kpi-value">' + escapeHtml(targetSubContratista) + '</span></div>';
    }
    if (updateResponsableAia) {
      html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Responsable AIA</span><span class="pi-shared-kpi-value">' + escapeHtml(targetResponsableAia) + '</span></div>';
    }
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">Coincidencias</span><span class="pi-shared-kpi-value">' + escapeHtml(String(countFound) + ' / ' + String(countTotal)) + '</span></div>';
    html += '<div class="pi-shared-kpi"><span class="pi-shared-kpi-label">No encontradas</span><span class="pi-shared-kpi-value">' + escapeHtml(String(countMissing)) + '</span></div>';
    if (applyAssignments && (updateSubContratista || updateResponsableAia)) {
      var conflictKpiClass = conflicts.hasAny ? ' pi-shared-kpi-conflict' : '';
      var conflictParts = [];
      if (updateSubContratista) {
        conflictParts.push(escapeHtml(String(conflicts.sub) + ' Sub'));
      }
      if (updateResponsableAia) {
        conflictParts.push(escapeHtml(String(conflicts.resp) + ' Resp'));
      }
      html += '<div class="pi-shared-kpi' + conflictKpiClass + '"><span class="pi-shared-kpi-label">Conflictos asignación</span><span class="pi-shared-kpi-value">' + conflictParts.join(' | ') + '</span></div>';
    }
    html += '</div>';

    html += '<div class="pi-shared-coverage">';
    html += '<div class="pi-shared-coverage-track"><div class="pi-shared-coverage-fill" style="width:' + coverage + '%;"></div></div>';
    html += '<span class="pi-shared-coverage-text">' + escapeHtml(String(coverage) + '% cobertura') + '</span>';
    html += '</div>';

    if (missingIds.length > 0) {
      html += '<div class="pi-shared-missing"><strong>No encontradas:</strong> ' + escapeHtml(missingIds.join(', ')) + '</div>';
    }

    if (conflicts.hasAny) {
      var parts = [];
      if (conflicts.sub > 0 && updateSubContratista) {
        parts.push('<span class="pi-shared-conflict-badge"><b>' + escapeHtml(String(conflicts.sub)) + '</b> con Sub-Contratista distinto a "' + escapeHtml(targetSubContratista) + '"</span>');
      }
      if (conflicts.resp > 0 && updateResponsableAia) {
        parts.push('<span class="pi-shared-conflict-badge"><b>' + escapeHtml(String(conflicts.resp)) + '</b> con Responsable AIA distinto a "' + escapeHtml(targetResponsableAia) + '"</span>');
      }
      html += '<div class="pi-shared-conflicts">';
      html += '<div class="pi-shared-conflicts-title">Se unificarán asignaciones heterogéneas:</div>';
      html += parts.join(' ');
      html += '<div class="pi-shared-conflicts-hint">Al aplicar, estos valores se sobreescribirán con los del formulario. El sistema pedirá confirmación explícita antes de continuar.</div>';
      html += '</div>';
    }

    if (previewRows.length > 0) {
      var max = Math.min(previewRows.length, 12);
      html += '<div class="pi-shared-table-wrap"><table class="pi-shared-table">';
      html += '<thead><tr><th>#</th><th>Actividad</th>';
      if (applyRestriction) {
        html += '<th>Cambios de restricciones</th>';
      }
      if (updateSubContratista) {
        html += '<th>Sub actual</th><th>Sub objetivo</th>';
      }
      if (updateResponsableAia) {
        html += '<th>Resp actual</th><th>Resp objetivo</th>';
      }
      html += '</tr></thead><tbody>';

      for (var i = 0; i < max; i++) {
        var item = previewRows[i] || {};
        var consecutivo = String(item.consecutivo || '-');
        var actividad = getActividadPlainText(item.actividad || '').replace(/\s+/g, ' ').trim();
        if (!actividad) {
          actividad = 'Actividad sin nombre';
        }

        if (actividad.length > 160) {
          actividad = actividad.substring(0, 157) + '...';
        }

        var rowSubCurrent = String(item.sub_contratista_actual == null ? '' : item.sub_contratista_actual).trim();
        var rowRespCurrent = String(item.responsable_aia_actual == null ? '' : item.responsable_aia_actual).trim();
        var rowSubConflict = updateSubContratista && rowSubCurrent && rowSubCurrent !== targetSubContratista;
        var rowRespConflict = updateResponsableAia && rowRespCurrent && rowRespCurrent !== targetResponsableAia;
        var rowClass = '';
        if (rowSubConflict || rowRespConflict) {
          rowClass = ' class="pi-shared-row-conflict"';
        }

        html += '<tr' + rowClass + '>';
        html += '<td class="pi-shared-col-id">#' + escapeHtml(consecutivo) + '</td>';
        html += '<td class="pi-shared-activity-cell">' + escapeHtml(actividad) + '</td>';
        if (applyRestriction) {
          html += '<td>' + renderSharedRestrictionChanges(item, restrictions) + '</td>';
        }
        if (updateSubContratista) {
          html += '<td' + (rowSubConflict ? ' class="pi-shared-cell-conflict"' : '') + '>' + escapeHtml(getPreviewValueLabel(item.sub_contratista_actual)) + '</td>';
          html += '<td>' + escapeHtml(targetSubContratista) + '</td>';
        }
        if (updateResponsableAia) {
          html += '<td' + (rowRespConflict ? ' class="pi-shared-cell-conflict"' : '') + '>' + escapeHtml(getPreviewValueLabel(item.responsable_aia_actual)) + '</td>';
          html += '<td>' + escapeHtml(targetResponsableAia) + '</td>';
        }
        html += '</tr>';
      }

      html += '</tbody></table></div>';

      if (previewRows.length > max) {
        html += '<div class="pi-shared-more">... +' + escapeHtml(String(previewRows.length - max)) + ' más</div>';
      }
    } else {
      html += '<div class="pi-shared-empty">No hay actividades para mostrar en el preview.</div>';
    }

    html += '</div>';
    $preview.html(html);

    lastSharedPreviewKey = getCurrentSharedConfigKey();
    lastSharedPreviewStats = {
      applyRestriction: applyRestriction,
      applyAssignments: applyAssignments,
      updateSubContratista: updateSubContratista,
      updateResponsableAia: updateResponsableAia,
      targetSubContratista: targetSubContratista,
      targetResponsableAia: targetResponsableAia,
      conflicts: conflicts,
      countFound: countFound,
      countTotal: countTotal,
      countMissing: countMissing,
    };
    var $apply = $('#btn_pi_shared_apply');
    if ($apply.length && conflicts.hasAny) {
      $apply.attr('data-conflicts', 'true');
    } else {
      $apply.removeAttr('data-conflicts');
    }
  }

  function autoPopulateHomogeneousRestrictions(selectedIds) {
    if (!Array.isArray(selectedIds) || selectedIds.length < 2) {
      return { populated: 0, total: sharedRestrictionTypes.length };
    }

    var byId = {};
    for (var i = 0; i < masterData.length; i++) {
      var row = masterData[i] || {};
      var id = getRowActivityId(row);
      if (id) byId[id] = row;
    }

    var populated = 0;
    for (var t = 0; t < sharedRestrictionTypes.length; t++) {
      var type = sharedRestrictionTypes[t];
      var $check = $('#piSharedRestriction_' + type);
      var $select = $('.pi-shared-restriction-value[data-restriction-type="' + type + '"]');
      if (!$check.length || !$select.length) {
        continue;
      }

      var values = [];
      for (var j = 0; j < selectedIds.length; j++) {
        var row = byId[selectedIds[j]];
        if (!row) {
          continue;
        }
        var raw = row[type];
        var normalized = raw == null ? '' : String(raw).trim();
        values.push(normalized);
      }

      if (values.length < 2) {
        $check.prop('checked', false);
        continue;
      }

      var first = values[0];
      var allEqual = true;
      for (var k = 1; k < values.length; k++) {
        if (values[k] !== first) {
          allEqual = false;
          break;
        }
      }

      if (allEqual && first !== '' && $select.find('option[value="' + first + '"]').length > 0) {
        $check.prop('checked', true);
        $select.val(first);
        populated++;
      } else {
        $check.prop('checked', false);
      }
    }

    return { populated: populated, total: sharedRestrictionTypes.length };
  }

  function resetSharedConstraintModal() {
    var selectedIds = collectSelectedActivityIds();

    populateSharedAssignmentOptions();
    populateSharedRestrictionGrid();
    $('.pi-shared-restriction-check').prop('checked', false);
    $('#piSharedApplyRestriction').prop('checked', true);
    $('#piSharedApplyAssignments').prop('checked', false);
    $('#piSharedSubContratista, #piSharedResponsableAIA').val('');
    syncSharedOperationControls();
    $('#piSharedActivityIds').val(selectedIds.join(','));
    $('#piSharedNote').val('');

    var autoResult = { populated: 0, total: sharedRestrictionTypes.length };
    if (selectedIds.length >= 2) {
      autoResult = autoPopulateHomogeneousRestrictions(selectedIds);
    }
    syncSharedOperationControls();

    if (selectedIds.length === 0) {
      renderSharedPreviewEmpty('Sin actividades cargadas. Use "Cargar marcadas" o "Usar visibles" antes del preview.');
    } else if (autoResult.populated > 0) {
      renderSharedPreviewEmpty('Filas detectadas: ' + selectedIds.length + '. Se pre-cargaron ' + autoResult.populated + ' restricción(es) con valor uniforme en todas las actividades. Pulse "Ver Conflictos" para validar impacto.');
    } else {
      renderSharedPreviewEmpty('Filas detectadas: ' + selectedIds.length + '. Las restricciones seleccionadas tienen valores heterogéneos. Marque manualmente las casillas que desea unificar. Pulse "Ver Conflictos" para validar impacto.');
    }

    $('#btn_pi_shared_preview').prop('disabled', false);
    lastSharedPreviewKey = null;
    lastSharedPreviewStats = null;
    var $applyReset = $('#btn_pi_shared_apply');
    if ($applyReset.length) {
      $applyReset.removeAttr('data-conflicts');
    }
    updateSharedSelectionCountIndicator();
  }

  function loadSharedIdsIntoInput(activityIds, sourceLabel) {
    var ids = parseActivityIdsInput(activityIds);
    $('#piSharedActivityIds').val(ids.join(','));

    if (ids.length === 0) {
      renderSharedPreviewEmpty('No se cargaron actividades desde ' + sourceLabel + '.');
      return;
    }

    renderSharedPreviewEmpty('Cargadas ' + ids.length + ' actividades desde ' + sourceLabel + '. Pulse "Ver Conflictos" para validar impacto.');
  }

  function loadMarkedIdsForSharedConstraint() {
    loadSharedIdsIntoInput(getMarkedActivityIds(), 'marcadas');
  }

  function loadVisibleIdsForSharedConstraint() {
    loadSharedIdsIntoInput(getVisibleActivityIds(), 'visibles');
  }

  function clearSharedIdsInput() {
    $('#piSharedActivityIds').val('');
    renderSharedPreviewEmpty('Lista de consecutivos limpia. Cargue actividades y pulse "Ver Conflictos".');
  }

  /**
   * N-1: consecutivos, de entre los pedidos, cuya fila conocida no tiene Responsable AIA.
   * Solo mira lo que el cliente ya tiene cargado; quien manda es el servidor.
   */
  function findActivityIdsWithoutResponsable(activityIds) {
    var pedidos = {};
    for (var i = 0; i < activityIds.length; i++) {
      pedidos[String(activityIds[i])] = true;
    }

    var bloqueadas = [];
    for (var j = 0; j < masterData.length; j++) {
      var row = masterData[j] || {};
      var id = getRowActivityId(row);
      if (!id || !pedidos[id] || bloqueadas.indexOf(id) > -1) {
        continue;
      }
      if (getState(row) === 'header') {
        continue;
      }
      if (!hasAssignedValue(row.Responsable_AIA, PI_CREATE_PROF)) {
        bloqueadas.push(id);
      }
    }

    return bloqueadas;
  }

  function buildSharedConstraintRequest(requireValue) {
    var db = getDb();
    var semana = getSemana();
    var applyRestriction = $('#piSharedApplyRestriction').is(':checked');
    var applyAssignments = $('#piSharedApplyAssignments').is(':checked');
    var restrictionPayload = applyRestriction ? collectSharedRestrictions(requireValue) : { valid: true, items: [] };
    var restrictions = restrictionPayload.items || [];
    var firstRestriction = restrictions[0] || {};
    var restrictionType = applyRestriction ? String(firstRestriction.type || '').trim() : '';
    var targetValue = applyRestriction ? String(firstRestriction.value || '').trim() : '';
    var activityIds = parseActivityIdsInput($('#piSharedActivityIds').val());
    var note = String($('#piSharedNote').val() || '').trim();
    var subContratista = String($('#piSharedSubContratista').val() || '').trim();
    var responsableAia = String($('#piSharedResponsableAIA').val() || '').trim();

    if (!applyRestriction && !applyAssignments) {
      return { valid: false, error: 'Active al menos una operación de lote.' };
    }

    if (applyRestriction && !restrictionPayload.valid) {
      return { valid: false, error: restrictionPayload.error || 'Seleccione al menos una restricción.' };
    }

    if (activityIds.length === 0) {
      return { valid: false, error: 'Seleccione al menos una actividad.' };
    }

    if (applyAssignments && !subContratista && !responsableAia) {
      return { valid: false, error: 'Active "Aplicar asignaciones comunes" y seleccione Sub-Contratista o Responsable AIA, o desactive el check.' };
    }

    // N-1: el lote no puede escribir restricciones donde la celda muestra candado.
    // Se permite si el mismo lote asigna Responsable AIA, que es como se desbloquean.
    if (applyRestriction && !(applyAssignments && hasAssignedValue(responsableAia, PI_CREATE_PROF))) {
      var bloqueadas = findActivityIdsWithoutResponsable(activityIds);
      if (bloqueadas.length > 0) {
        return {
          valid: false,
          error: 'Falta el Responsable AIA en ' + bloqueadas.length + ' actividad(es) (' + bloqueadas.slice(0, 10).join(', ') + (bloqueadas.length > 10 ? ', …' : '') + '). Asigne el Responsable AIA antes de aplicar restricciones, o márquelo en "Aplicar asignaciones comunes".',
        };
      }
    }

    return {
      valid: true,
      data: {
        db: db,
        semana: semana,
        apply_restriction: applyRestriction ? 1 : 0,
        apply_assignments: applyAssignments ? 1 : 0,
        restriction_type: restrictionType,
        target_value: targetValue,
        restrictions: JSON.stringify(restrictions),
        sub_contratista: subContratista,
        responsable_aia: responsableAia,
        activity_ids: activityIds,
        note: note,
      },
    };
  }

  function updateRowsAfterSharedConstraintApply(updatedIds, responseData, requestData) {
    if (!Array.isArray(updatedIds) || updatedIds.length === 0) {
      return;
    }

    var data = responseData || {};
    var request = requestData || {};
    var applyRestriction = resolvePreviewFlag(data, request, 'apply_restriction', true);
    var applyAssignments = resolvePreviewFlag(data, request, 'apply_assignments', false);
    var restrictions = getSharedRestrictionsFromPreview(data, request);
    var subContratista = String(data.sub_contratista !== undefined ? data.sub_contratista : (request.sub_contratista || '')).trim();
    var responsableAia = String(data.responsable_aia !== undefined ? data.responsable_aia : (request.responsable_aia || '')).trim();
    var normalizedRestrictions = [];

    var idIndex = {};
    for (var i = 0; i < updatedIds.length; i++) {
      idIndex[String(updatedIds[i])] = true;
    }

    if (applyRestriction) {
      for (var restrictionIndex = 0; restrictionIndex < restrictions.length; restrictionIndex++) {
        var restriction = restrictions[restrictionIndex] || {};
        var normalizedValue = normalizeRestrictionValue(restriction.type, restriction.value);
        if (normalizedValue !== null) {
          normalizedRestrictions.push({ type: restriction.type, value: normalizedValue });
        }
      }

      if (normalizedRestrictions.length === 0) {
        applyRestriction = false;
      }
    }

    for (var rowIndex = 0; rowIndex < masterData.length; rowIndex++) {
      var row = masterData[rowIndex] || {};
      var rowId = getRowActivityId(row);
      if (!idIndex[rowId]) {
        continue;
      }

      if (applyRestriction) {
        for (var updateIndex = 0; updateIndex < normalizedRestrictions.length; updateIndex++) {
          row[normalizedRestrictions[updateIndex].type] = normalizedRestrictions[updateIndex].value;
        }
        row.Estado_Restricciones = calculateRestrictionStateRatio(row);
        row.estado_operativo = getStateDisplay(row);
      }
      if (applyAssignments && subContratista) {
        row.Sub_Contratista = subContratista;
      }
      if (applyAssignments && responsableAia) {
        row.Responsable_AIA = responsableAia;
      }
    }
  }

  function requestSharedConstraintPreview() {
    var request = buildSharedConstraintRequest(false);
    if (!request.valid) {
      showFeedback('error', request.error);
      return;
    }

    $('#btn_pi_shared_preview').prop('disabled', true);
    renderSharedPreviewLoading();

    $.ajax({
      method: 'POST',
      url: '/programacion-intermedia/shared-constraints/preview',
      dataType: 'json',
      data: request.data,
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        renderSharedPreview(response.data || {}, {
          apply_restriction: request.data.apply_restriction,
          apply_assignments: request.data.apply_assignments,
          restriction_type: request.data.restriction_type,
          target_value: request.data.target_value,
          restrictions: request.data.restrictions,
          sub_contratista: request.data.sub_contratista,
          responsable_aia: request.data.responsable_aia,
        });
        return;
      }

      var message = (response && (response.mensaje || response.message)) || 'No se pudo calcular el preview.';
      showFeedback('error', message);
      renderSharedPreviewEmpty('No se pudo generar el preview. Corrija datos e intente nuevamente.');
    }).fail(function () {
      showFeedback('error', 'Error de red en preview de restricción compartida.');
      renderSharedPreviewEmpty('Error de red calculando preview. Intente nuevamente.');
    }).always(function () {
      $('#btn_pi_shared_preview').prop('disabled', false);
    });
  }

  function requestSharedConstraintApply() {
    var request = buildSharedConstraintRequest(true);
    if (!request.valid) {
      showFeedback('error', request.error);
      return;
    }

    var relevance = getPreviewRelevance();
    var stats = lastSharedPreviewStats || {};

    function proceedApply() {
      $('#btn_pi_shared_apply').prop('disabled', true);

      $.ajax({
        method: 'POST',
        url: '/programacion-intermedia/shared-constraints/apply',
        dataType: 'json',
        data: request.data,
      }).done(function (response) {
        if (!(response && response.respuesta === 'BIEN')) {
          var message = (response && (response.mensaje || response.message)) || 'No se pudo aplicar la restricción compartida.';
          showFeedback('error', message);
          return;
        }

        var data = response.data || {};
        var updatedIds = Array.isArray(data.updated_ids) ? data.updated_ids : request.data.activity_ids;
        var restrictions = data.restrictions !== undefined ? data.restrictions : request.data.restrictions;

        updateRowsAfterSharedConstraintApply(updatedIds, data, request.data);
        pendingViewportState = captureViewportState();
        applyFiltersAndRender();
        showFeedback('success', 'Lote aplicado (' + Number(data.updated_count || updatedIds.length || 0) + ')');
        renderSharedPreview({
          restriction_type: request.data.restriction_type,
          target_value: data.target_value !== undefined ? data.target_value : request.data.target_value,
          restrictions: restrictions,
          apply_restriction: request.data.apply_restriction,
          apply_assignments: request.data.apply_assignments,
          sub_contratista: data.sub_contratista !== undefined ? data.sub_contratista : request.data.sub_contratista,
          responsable_aia: data.responsable_aia !== undefined ? data.responsable_aia : request.data.responsable_aia,
          count_total: request.data.activity_ids.length,
          count_found: Number(data.updated_count || updatedIds.length || 0),
          count_missing: 0,
          preview: [],
        }, {
          target_value: data.target_value !== undefined ? data.target_value : request.data.target_value,
          restriction_type: request.data.restriction_type,
          restrictions: restrictions,
          apply_restriction: request.data.apply_restriction,
          apply_assignments: request.data.apply_assignments,
          sub_contratista: data.sub_contratista !== undefined ? data.sub_contratista : request.data.sub_contratista,
          responsable_aia: data.responsable_aia !== undefined ? data.responsable_aia : request.data.responsable_aia,
        });

        setTimeout(function () {
          $('#modal_shared_constraint').modal('hide');
        }, 180);
      }).fail(function () {
        showFeedback('error', 'Error de red aplicando restricción compartida.');
      }).always(function () {
        $('#btn_pi_shared_apply').prop('disabled', false);
      });
    }

    if (!window.AIA || !window.AIA.Notice || typeof window.AIA.Notice.confirm !== 'function') {
      proceedApply();
      return;
    }

    if (relevance === 'no-assignments') {
      proceedApply();
      return;
    }

    var confirmContent = buildApplyConfirmationContent(relevance, request.data, stats);
    window.AIA.Notice.confirm({
      title: confirmContent.title,
      html: confirmContent.html,
      icon: confirmContent.icon,
      confirmButtonText: confirmContent.confirmText || 'Confirmar',
      cancelButtonText: confirmContent.cancelText || 'Cancelar',
    }).then(function (confirmed) {
      if (confirmed) {
        proceedApply();
      }
    });
  }

  function showLoading(show) {
    if (show) {
      $('#loading').show();
    } else {
      $('#loading').fadeOut(200);
    }
  }

  function showFeedback(type, message, options) {
    options = options || {};
    clearTimeout(saveBadgeTimer);
    $('#save-status').hide();
    $('#save-error').hide();

    if (type === 'success') {
      if (_saveStatus) { _saveStatus.guardado(); }
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
        window.AIA.Notice.badge('success', message);
      } else {
        // Fallback robusto
        var $el = $('#save-status');
        $el.removeClass('badge-badge-hidden').text(message || 'Guardado').fadeIn(120);
        saveBadgeTimer = setTimeout(function () {
          $el.fadeOut(250, function() { $(this).addClass('badge-badge-hidden'); });
        }, 1800);
      }
    } else {
      if (window.AIA && window.AIA.Notice && window.AIA.Notice.error) {
        window.AIA.Notice.error(message || 'Error al guardar', options.title);
      } else {
        var $error = $('#save-error');
        if ($error.length) {
          $error.text(message || 'Error al guardar').fadeIn(120);
          saveBadgeTimer = setTimeout(function () {
            $error.fadeOut(350);
          }, 3200);
        } else if (typeof window.alert === 'function') {
          window.alert(message || 'Error al guardar');
        }
      }
    }
  }

  function renderLegendModal() {
    $('#modal_leyenda_colores_Label').text('Guía Operativa - Programación Intermedia (Last Planner 6 semanas)');
    $('#modal_leyenda_colores_body').html(
      "<div class='pi-legend-quick'>" +
        "<div class='pi-legend-quick-header'>" +
          "<p class='pi-legend-quick-intro'><strong>Lectura rápida:</strong> atiende primero P1, luego P2 y deja P3 en monitoreo.</p>" +
          "<div class='pi-legend-quick-scale'>" +
            "<span class='pi-legend-quick-badge is-p1'>P1 Hoy</span>" +
            "<span class='pi-legend-quick-badge is-p2'>P2 Esta semana</span>" +
            "<span class='pi-legend-quick-badge is-p3'>P3 Seguimiento</span>" +
          "</div>" +
        "</div>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P1 - Resolver hoy</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-overdue-critical'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['blocked-overdue-critical']) + "</strong><small>Debió iniciar y requiere condiciones de habilitación en ruta crítica.</small></div>" +
            "<div class='pi-legend-quick-action'>Escalar hoy, asignar responsable y cerrar las acciones de habilitación.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-overdue'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['blocked-overdue']) + "</strong><small>Debió iniciar y aún tiene condiciones pendientes.</small></div>" +
            "<div class='pi-legend-quick-action'>Definir responsable y fecha de cierre en la reunión diaria.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-blocked-due'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['blocked-due']) + "</strong><small>Inicia esta semana y requiere condiciones para comprometer.</small></div>" +
            "<div class='pi-legend-quick-action'>Cerrar acciones de habilitación antes del inicio.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-execution-blocked'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['execution-blocked']) + "</strong><small>Actividad iniciada con acciones de habilitación abiertas.</small></div>" +
            "<div class='pi-legend-quick-action'>Cerrar condiciones pendientes para evitar retrabajos y paradas.</div>" +
            "<span class='pi-legend-quick-priority is-p1'>P1</span>" +
          "</div>" +
        "</section>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P2 - Gestión semanal</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-1-week'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['alert-1-week']) + "</strong><small>Inicia en una semana y requiere acciones de habilitación.</small></div>" +
            "<div class='pi-legend-quick-action'>Cerrar las condiciones pendientes esta semana.</div>" +
            "<span class='pi-legend-quick-priority is-p2'>P2</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-2-3-weeks'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['alert-2-3-weeks']) + "</strong><small>Inicia en 2 a 3 semanas y aún requiere preparación.</small></div>" +
            "<div class='pi-legend-quick-action'>Ejecutar plan preventivo de abastecimiento y recursos.</div>" +
            "<span class='pi-legend-quick-priority is-p2'>P2</span>" +
          "</div>" +
        "</section>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>P3 - Seguimiento</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-alert-4-6-weeks'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['alert-4-6-weeks']) + "</strong><small>Inicia en 4 a 6 semanas y requiere seguimiento temprano.</small></div>" +
            "<div class='pi-legend-quick-action'>Monitorear preparación y anticipar condiciones pendientes.</div>" +
            "<span class='pi-legend-quick-priority is-p3'>P3</span>" +
          "</div>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch pi-state-liberated-control'></span>" +
            "<div class='pi-legend-quick-state'><strong>" + escapeHtml(stateLabels['liberated-control']) + "</strong><small>Cumple la matriz de habilitación para pasar a Programación Semanal.</small></div>" +
            "<div class='pi-legend-quick-action'>Mantener control semanal y preparar compromiso viable.</div>" +
            "<span class='pi-legend-quick-priority is-p3'>P3</span>" +
          "</div>" +
        "</section>" +

        "<section class='pi-legend-quick-group'>" +
          "<h6 class='pi-legend-quick-group-title'>Restricciones blandas</h6>" +
          "<div class='pi-legend-quick-row'>" +
            "<span class='pi-legend-modal-swatch pi-legend-quick-swatch' style='background:var(--aia-warning-soft-bg, #fef3c7);border-color:var(--aia-warning-border, #f59e0b);'></span>" +
            "<div class='pi-legend-quick-state'><strong>Pdto. Constructivo y Modelo BIM</strong><small>Seguimiento blando: no bloquean habilitación, estado operativo ni autoprogramación.</small></div>" +
            "<div class='pi-legend-quick-action'>Completar para control técnico, sin detener compromisos listos.</div>" +
            "<span class='pi-legend-quick-priority is-p3'>Blanda</span>" +
          "</div>" +
        "</section>" +
      "</div>"
    );
  }

  var piViewAll = false;
  var piServerCounts = {};

  function fetchFilterFlags() {
    var db = getDb();
    var semana = getSemana();

    if (!db || !semana) {
      return $.Deferred().resolve('').promise();
    }

    return $.ajax({
      method: 'POST',
      url: '/programacion-intermedia/filtros',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).then(function (info) {
      var data = info && info.data ? info.data : {};
      var params = [];
      piViewAll = !!data.view_all;
      piServerCounts = {};

      for (var i = 0; i < trackedStates.length; i++) {
        var key = trackedStates[i].replace(/-/g, '_');
        params.push('activa_' + key + '=' + (data['activa_' + key] ? 1 : 0));
        piServerCounts[trackedStates[i]] = Number(data['count_' + key] || 0);
      }

      var query = '&' + params.join('&');
      $('#scriptBarraFiltros').val(query);
      return query;
    }, function () {
      return '';
    });
  }

  function updateLegendCountsFromServer() {
    Object.keys(piServerCounts).forEach(function (key) {
      setLegendCount(key, piServerCounts[key]);
    });
  }

  /* Un chip que marca cero no tiene nada que reclamar: se atenua con `is-zero`
     y recupera su color saturado en cuanto vuelve a contar algo. */
  function setLegendCount(key, value) {
    var count = Number(value) || 0;
    /* `is-zero` atenua (C-24) y `is-empty` ademas oculta. La guarda de
       `activeFilters` es la que separa "vacio" de "cero porque estoy mirando
       otra cosa"; el porque esta junto a OCULTAR_CONTADORES_EN_CERO. En
       `view_all` los conteos llegan del servidor y si cubren el conjunto
       entero, y ahi `activeFilters` esta vacio igualmente. */
    var esVacioReal = count === 0 && activeFilters.length === 0;

    $('#count-' + key)
      .text('(' + value + ')')
      .closest('.pdc-legend-item')
      .toggleClass('is-zero', count === 0)
      .toggleClass('is-empty', OCULTAR_CONTADORES_EN_CERO && esVacioReal);
  }

  function buildListUrl(extraFlags) {
    return '/api/pi/list?a=1' + (extraFlags || '');
  }

  function mapRows(rows) {
    var list = [];
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i] || {};

      for (var j = 0; j < restrictionProps.length; j++) {
        var prop = restrictionProps[j];
        row[prop] = normalizeRestrictionValue(prop, row[prop]);
      }
      row.Estado_Restricciones = calculateRestrictionStateRatio(row);
      row.estado_operativo = getStateDisplay(row);
      row.__shared_selected = Boolean(sharedSelectionIndex[getRowActivityId(row)]);

      list.push(row);
    }
    return list;
  }

  function requestList(extraFlags) {
    $.ajax({
      url: buildListUrl(extraFlags),
      method: 'GET',
      dataType: 'json',
      cache: false,
    }).done(function (response) {
      var rawData = response && Array.isArray(response.data) ? response.data : [];
      masterData = mapRows(rawData);
      rebuildSharedSelectionIndex();
      updateSharedSelectionCountIndicator();
      applyFiltersAndRender();
      showLoading(false);
    }).fail(function () {
      showLoading(false);
      showFeedback('error', 'No se pudieron cargar las actividades. Recarga la página para volver a intentarlo.');
    });
  }

  function loadData() {
    showLoading(true);
    fetchFilterFlags().done(function (flags) {
      requestList(flags || '');
    });
  }

  function normalizeCellValue(prop, value) {
    if (prop === 'Sub_Contratista' || prop === 'Responsable_AIA' || prop === 'Observaciones') {
      return { valid: true, value: String(value === null || value === undefined ? '' : value).trim() };
    }

    if (editableProps[prop]) {
      var normalized = normalizeRestrictionValue(prop, value);
      if (normalized === null) {
        return { valid: false, value: value, error: 'Valor inválido' };
      }
      return { valid: true, value: normalized };
    }

    return { valid: true, value: value };
  }

  function buildPayload(row) {
    var id = row.unique_id || row.Consecutivo_en_Programa;
    if (!id) {
      return { valid: false, error: 'Id de actividad inválido' };
    }

    var normalizedRestrictions = {};

    for (var i = 0; i < restrictionProps.length; i++) {
      var field = restrictionProps[i];
      var normalized = normalizeRestrictionForPayload(field, row[field]);
      if (normalized === null) {
        return { valid: false, error: 'Valor inválido en restricciones' };
      }
      normalizedRestrictions[field] = normalized;
    }

    return {
      valid: true,
      data: (function () {
        var d = { opcion: 'modificar', Id: id };
        for (var k = 0; k < restrictionProps.length; k++) {
          d[restrictionProps[k]] = normalizedRestrictions[restrictionProps[k]];
        }
        d.Sub_Contratista = row.Sub_Contratista || '';
        d.Responsable_AIA = row.Responsable_AIA || '';
        d.Observaciones = row.Observaciones || '';
        return d;
      })(),
    };
  }

  function revertCell(visualRow, prop, oldValue) {
    if (!hot) {
      // Sin grilla montada (E4), el modelo es `visibleRows`, la misma
      // fuente que pinta la tarjeta. Revisor N-1 (2026-08-14, hallazgo 1):
      // sin este revert, un valor rechazado por el servidor sobrevivia en
      // `fila[prop]` y viajaba en el siguiente POST de `buildPayload`,
      // porque este arma el payload con TODAS las restricciones de la fila.
      var fila = visibleRows[visualRow];
      if (fila) {
        fila[prop] = oldValue;
      }
      renderMobileCards(visibleRows);
      return;
    }
    var col = hot.propToCol(prop);
    if (col >= 0) {
      hot.setDataAtCell(visualRow, col, oldValue, 'revert');
    }
  }

  function saveRow(visualRow, prop, oldValue) {
    var db = getDb();
    var semana = getSemana();
    // Sin grilla montada (E4), `visualRow` es el indice dentro de
    // `visibleRows`, que es la misma fuente que usa renderMobileCards() para
    // pintar esa card: sin una segunda verdad. `getSourceRowDataByVisualRow`/
    // `getPhysicalRowFromVisualRow` ya devuelven null sin instancia, pero eso
    // dejaba `row` vacio y el payload invalido en cada guardado movil.
    var row = hot ? getSourceRowDataByVisualRow(hot, visualRow) : (visibleRows[visualRow] || null);
    var physicalRow = hot ? getPhysicalRowFromVisualRow(hot, visualRow) : null;

    var payload = buildPayload(row || {});
    if (!payload.valid) {
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      showFeedback('error', payload.error);
      return;
    }

    if (_saveStatus) { _saveStatus.pendiente(1); }

    $.ajax({
      method: 'POST',
      url: '/api/pi/save?db=' + encodeURIComponent(db) + '&semana=' + encodeURIComponent(semana),
      dataType: 'json',
      data: payload.data,
    }).done(function (response) {
      if (response && response.respuesta === 'BIEN') {
        var savedViewport = captureViewportState();

        if (hot && typeof hot.suspendRender === 'function') {
          hot.suspendRender();
        }

        try {
          if (row) {
            invalidatePIRowCache(physicalRow, row);

            if (response.estado_restricciones !== undefined && response.estado_restricciones !== null && response.estado_restricciones !== '') {
              row.Estado_Restricciones = response.estado_restricciones;
              invalidatePIRowCache(physicalRow, row);
              if (hot) {
                hot.setDataAtRowProp(visualRow, 'Estado_Restricciones', response.estado_restricciones, 'internal-update');
              }
            }

            if (response.semanas_inicio !== undefined && response.semanas_inicio !== null && response.semanas_inicio !== '') {
              row.Semanas_Inicio = response.semanas_inicio;
              invalidatePIRowCache(physicalRow, row);
              if (hot) {
                hot.setDataAtRowProp(visualRow, 'Semanas_Inicio', response.semanas_inicio, 'internal-update');
              }
            }

            if (response.estado !== undefined && response.estado !== null && response.estado !== '') {
              row.Estado = response.estado;
              invalidatePIRowCache(physicalRow, row);
            }

            row.estado_operativo = getStateDisplay(row);
            invalidatePIRowCache(physicalRow, row);
            if (hot) {
              hot.setDataAtRowProp(visualRow, 'estado_operativo', row.estado_operativo, 'internal-update');
            }
            refreshCellMetaForVisualRow(visualRow);
          }
        } finally {
          if (hot && typeof hot.resumeRender === 'function') {
            hot.resumeRender();
          }
        }

        if (hot) {
          hot.render();

          var fp = hot.getPlugin('filters');
          if (fp && fp.isEnabled() && fp.conditionCollection && typeof fp.conditionCollection.isEmpty === 'function' && !fp.conditionCollection.isEmpty()) {
              fp.filter();
          }
        } else {
          renderMobileCards(visibleRows);
        }

        if (savedViewport) {
          setTimeout(function () { restoreViewportState(savedViewport); }, 0);
        }

        if (piViewAll) {
          updateLegendCountsFromServer();
        } else {
          updateLegendCounts(getFilteredRows());
        }
        showFeedback('success', 'Guardado');
        return;
      }

      var message = (response && (response.mensaje || response.message)) || 'Error al guardar';
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      showFeedback('error', message);
    }).fail(function (jqXHR) {
      revertCell(visualRow, prop, oldValue);
      if (restrictionProps.indexOf(prop) > -1) {
        recalculateRestrictionStateForVisualRow(visualRow);
      }
      if (jqXHR && jqXHR.status === 409) {
        showFeedback('error', 'La semana activa cambió en otra pestaña o sesión. Recarga la página para continuar.');
        return;
      }
      showFeedback('error', 'No se pudo guardar: sin conexión con el servidor. Revisa la red y vuelve a escribir el dato.');
    });
  }


  function setupRenderers() {
    if (renderersRegistered) {
      return;
    }

    Handsontable.renderers.registerRenderer('piPercentRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = formatPercent(value);
      td.classList.add('htCenter');
    });

    // N-1 (Task 38): la celda de restriccion bloqueada dice POR QUE sin que
    // nadie la toque. El candado es el canal visible; el `title`, el respaldo.
    Handsontable.renderers.registerRenderer('piRestrictionRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = formatPercent(value);
      td.classList.add('htCenter');

      if (td.classList.contains('pi-cell-locked-resp')) {
        td.insertBefore(buildLockGlyph(), td.firstChild);

        var sr = document.createElement('span');
        sr.className = 'sr-only';
        sr.textContent = PI_LOCK_REASON;
        td.appendChild(sr);
        td.title = PI_LOCK_REASON;
      } else {
        // Con `renderAllRows: false` HOT recicla los <td> al hacer scroll y su
        // TextRenderer solo quita style/colspan/rowspan/dir/contenteditable: el
        // `title` sobreviviria al reciclado y una celda editable acabaria
        // anunciando un bloqueo que no tiene. El contenido (candado y sr-only)
        // si se limpia solo, porque aqui se reescribe el texto de la celda.
        td.removeAttribute('title');
      }
    });

    // N-1 (Task 38): el motivo en claro vive en la propia columna que falta.
    Handsontable.renderers.registerRenderer('piResponsableRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = getSourceRowDataByVisualRow(instance, row) || {};
      var physicalRow = getPhysicalRowFromVisualRow(instance, row);
      var meta = getPIRowMeta(physicalRow, rowData);

      if (!meta.isHeader && !hasAssignedValue(value, PI_CREATE_PROF)) {
        td.textContent = '';
        var mark = document.createElement('span');
        mark.className = 'pi-missing-resp';
        mark.appendChild(buildLockGlyph());
        mark.appendChild(document.createTextNode(PI_MISSING_RESP_LABEL));
        td.appendChild(mark);
        td.title = PI_LOCK_REASON;
      } else {
        // Mismo motivo que en `piRestrictionRenderer`: el <td> reciclado se
        // quedaba con el `title` del bloqueo aunque ya mostrara un responsable.
        // Ninguna otra rama de este renderer pone `title`, asi que se puede
        // quitar entero.
        td.removeAttribute('title');
      }
    });

    Handsontable.renderers.registerRenderer('piActividadRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = getSourceRowDataByVisualRow(instance, row) || {};
      var prefix = parseInt(rowData.alerta_crisis, 10) === 1 ? '🔥 ' : '';
      td.innerHTML = '<span class="pi-actividad-clamp">' + prefix + sanitizeActividadHtml(value) + '</span>';
      td.classList.add('htLeft');
      td.title = prefix + (value == null ? '' : String(value));
    });

    Handsontable.renderers.registerRenderer('piStateRenderer', function (instance, td, row, col, prop, value) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = getSourceRowDataByVisualRow(instance, row) || {};
      var physicalRow = getPhysicalRowFromVisualRow(instance, row);
      var view = getCachedStateView(rowData, physicalRow);
      td.innerHTML = renderOperationalStateCell(view);
      var trigger = td.querySelector('.ops-state-zoom');
      if (trigger) {
        trigger.setAttribute('data-row', String(row));
      }
      td.title = view.actions.length ? (view.label + ' - ' + view.actions.join('; ')) : view.label;
      td.classList.add('htLeft', 'htMiddle', 'force-wrap', 'ops-state-td');
    });

    bindOperationalStateDrawer();
    renderersRegistered = true;
  }

  function sumWidths(widths) {
    var total = 0;
    for (var i = 0; i < widths.length; i++) {
      total += Number(widths[i]) || 0;
    }
    return total;
  }

  function getColumnMinWidth(index) {
    return Number(columnMinWidths[index]) || 56;
  }

  function getColumnMaxWidth(index) {
    var max = Number(columnMaxWidths[index]);
    if (!Number.isFinite(max) || max <= 0) {
      max = 280;
    }
    var min = getColumnMinWidth(index);
    return max < min ? min : max;
  }

  function arraysEqualNumbers(a, b) {
    if (!Array.isArray(a) || !Array.isArray(b) || a.length !== b.length) {
      return false;
    }

    for (var i = 0; i < a.length; i++) {
      if (Number(a[i]) !== Number(b[i])) {
        return false;
      }
    }

    return true;
  }

  function getContainerAvailableWidth() {
    var container = document.getElementById('hot-container');
    if (!container) {
      return 0;
    }

    var width = Math.floor(container.clientWidth || container.offsetWidth || 0);
    if (!Number.isFinite(width) || width <= 0) {
      return 0;
    }

    return Math.max(260, width - 20);
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

  function getViewportScaleFactor() {
    var root = document.documentElement;
    if (!root) {
      return 1;
    }

    var zoom = parseFloat(root.style.zoom || '');
    if (Number.isFinite(zoom) && zoom > 0 && zoom < 1) {
      return zoom;
    }

    if (root.classList.contains('tablet-scale-70') || root.classList.contains('desktop-tablet-scale-70')) {
      return 0.7;
    }

    return 1;
  }

  function syncContainerHeight() {
    var container = document.getElementById('hot-container');
    if (!container || !container.getBoundingClientRect) {
      return 0;
    }

    var rect = container.getBoundingClientRect();
    var viewportHeight = getViewportHeight();
    var scaleFactor = getViewportScaleFactor();
    if (!Number.isFinite(viewportHeight) || viewportHeight <= 0) {
      return 0;
    }

    if (scaleFactor > 0 && scaleFactor < 1) {
      viewportHeight = Math.floor(viewportHeight / scaleFactor);
    }

    var top = Math.max(0, Math.floor(rect.top || 0));
    var bottomGap = 2;
    var available = Math.floor(viewportHeight - top - bottomGap);
    var resolved = Math.max(260, available);

    container.style.height = resolved + 'px';
    return resolved;
  }

  // C-19 (2026-08-05): el `title` de cabecera solo cuando el texto se recorta
  // de verdad. El task 26 lo ponia en TODAS desde `afterGetColHeader`, y ahi
  // «Id» acababa con un tooltip que decia «Id»: ruido, no ayuda. Medir dentro
  // del renderer no sirve para condicionarlo, porque Handsontable renderiza
  // varias veces y el ancho definitivo de la columna aun no esta aplicado -se
  // ven desbordes que luego no existen-. Por eso el barrido vive en
  // `afterRender`, cuando la medida ya es la final.
  function isHeaderClipped(node) {
    // Dos cortes distintos, los dos con `overflow: hidden` en el `th`
    // (handsontable-header-global.css): el vertical lo hace `-webkit-line-clamp: 2`
    // y se ve en scrollHeight; el horizontal lo produce una palabra que no cabe
    // y no se parte (`overflow-wrap: normal`) y se ve en scrollWidth. El margen
    // de 1 px absorbe el redondeo subpixel de anchos fraccionarios.
    return node.scrollWidth > node.clientWidth + 1 || node.scrollHeight > node.clientHeight + 1;
  }

  function refreshHeaderTitles(instance) {
    var hotInstance = instance || hot;
    var root = hotInstance && hotInstance.rootElement;
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    var headers = root.querySelectorAll('thead th .colHeader');
    for (var i = 0; i < headers.length; i++) {
      var node = headers[i];
      var text = String(node.textContent || '').replace(/\s+/g, ' ').trim();
      if (text && isHeaderClipped(node)) {
        node.title = text;
      } else {
        node.removeAttribute('title');
      }
    }
  }

  function syncRenderedTableWidth(instance) {
    var hotInstance = instance || hot;
    var container = document.getElementById('hot-container');
    if (!hotInstance || !container || typeof hotInstance.countCols !== 'function' || typeof hotInstance.getColWidth !== 'function') {
      return;
    }

    var totalWidth = 0;
    var columnCount = hotInstance.countCols();
    for (var col = 0; col < columnCount; col++) {
      totalWidth += Number(hotInstance.getColWidth(col)) || 0;
    }

    totalWidth = Math.max(Math.ceil(totalWidth), getContainerAvailableWidth());
    if (!Number.isFinite(totalWidth) || totalWidth <= 0) {
      return;
    }

    var width = totalWidth + 'px';
    container.classList.add('hot-fixed-columns');
    container.style.setProperty('--hot-table-width', width);

    var nodes = container.querySelectorAll('.handsontable table.htCore, .handsontable .wtHider, .handsontable .wtSpreader');
    Array.prototype.forEach.call(nodes, function (node) {
      node.style.setProperty('width', width, 'important');
      node.style.setProperty('min-width', width, 'important');
      if (node.matches && node.matches('table.htCore')) {
        node.style.setProperty('table-layout', 'fixed', 'important');
      }
    });
  }

  function getContainerAvailableHeight() {
    var container = document.getElementById('hot-container');
    if (!container) {
      return 0;
    }

    syncContainerHeight();

    var height = Math.floor(container.clientHeight || container.offsetHeight || 0);
    if (!Number.isFinite(height) || height <= 0) {
      return 0;
    }

    return Math.max(220, height - 2);
  }

  function getHotHolderElement() {
    return document.querySelector('#hot-container .ht_master .wtHolder') || document.querySelector('#hot-container .wtHolder');
  }

  function captureViewportState() {
    var holder = getHotHolderElement();
    var selected = hot && typeof hot.getSelectedLast === 'function' ? hot.getSelectedLast() : null;

    return {
      pageX: window.pageXOffset || document.documentElement.scrollLeft || 0,
      pageY: window.pageYOffset || document.documentElement.scrollTop || 0,
      holderTop: holder ? holder.scrollTop : 0,
      holderLeft: holder ? holder.scrollLeft : 0,
      selected: Array.isArray(selected) ? selected.slice() : null,
    };
  }

  function restoreViewportState(state) {
    if (!state) {
      return;
    }

    window.scrollTo(state.pageX || 0, state.pageY || 0);

    var holder = getHotHolderElement();
    if (holder) {
      holder.scrollTop = Number(state.holderTop) || 0;
      holder.scrollLeft = Number(state.holderLeft) || 0;
    }

    if (hot && Array.isArray(state.selected) && state.selected.length >= 2) {
      var row = state.selected[0];
      var col = state.selected[1];
      var endRow = Number.isInteger(state.selected[2]) ? state.selected[2] : row;
      var endCol = Number.isInteger(state.selected[3]) ? state.selected[3] : col;

      if (Number.isInteger(row) && Number.isInteger(col)) {
        try {
          hot.selectCell(row, col, endRow, endCol, false, false);
        } catch (_err) {
        }
      }
    }
  }

  function cloneHotFilterConditions(conditions) {
    if (!Array.isArray(conditions)) {
      return [];
    }

    return conditions.map(function (stack) {
      var stackConditions = Array.isArray(stack && stack.conditions) ? stack.conditions : [];

      return {
        column: stack ? stack.column : null,
        operation: (stack && stack.operation) || 'conjunction',
        conditions: stackConditions.map(function (condition) {
          return {
            name: condition ? condition.name : '',
            args: Array.isArray(condition && condition.args) ? condition.args.slice() : [],
          };
        }).filter(function (condition) {
          return condition.name;
        }),
      };
    }).filter(function (stack) {
      return Number.isInteger(stack.column) && stack.conditions.length > 0;
    });
  }

  function getHotFiltersPlugin() {
    if (!hot || typeof hot.getPlugin !== 'function') {
      return null;
    }

    try {
      return hot.getPlugin('filters') || null;
    } catch (_err) {
      return null;
    }
  }

  function captureHotFilterConditions() {
    var filtersPlugin = getHotFiltersPlugin();
    var conditionCollection = filtersPlugin && filtersPlugin.conditionCollection;

    if (!conditionCollection || typeof conditionCollection.exportAllConditions !== 'function') {
      return [];
    }

    try {
      return cloneHotFilterConditions(conditionCollection.exportAllConditions());
    } catch (_err) {
      return [];
    }
  }

  function restoreHotFilterConditions(conditions) {
    var clonedConditions = cloneHotFilterConditions(conditions);
    if (window.__PI_DEBUG_COLOR) {
      console.log('[PI-DEBUG] restoreHotFilterConditions:', {
        originalCount: Array.isArray(conditions) ? conditions.length : 0,
        clonedCount: clonedConditions.length,
        columns: clonedConditions.map(function (s) { return s.column; }),
      });
    }
    if (clonedConditions.length === 0) {
      return;
    }

    var filtersPlugin = getHotFiltersPlugin();
    var conditionCollection = filtersPlugin && filtersPlugin.conditionCollection;

    if (!conditionCollection) {
      return;
    }

    try {
      if (typeof conditionCollection.clean === 'function' && typeof conditionCollection.addCondition === 'function') {
        conditionCollection.clean();
        clonedConditions.forEach(function (stack) {
          stack.conditions.forEach(function (condition) {
            conditionCollection.addCondition(stack.column, condition, stack.operation);
          });
        });
      } else if (typeof conditionCollection.importAllConditions === 'function') {
        conditionCollection.importAllConditions(clonedConditions);
      } else {
        return;
      }

      if (filtersPlugin && typeof filtersPlugin.filter === 'function') {
        filtersPlugin.filter();
      }
    } catch (_err) {
    }
  }

  function getBaseColumnWidths(columnCount) {
    var widths = [];
    var plugin = hot && hot.getPlugin ? hot.getPlugin('autoColumnSize') : null;

    if (plugin) {
      try {
        if (typeof plugin.recalculateAllColumnsWidth === 'function') {
          plugin.recalculateAllColumnsWidth();
        } else if (typeof plugin.calculateVisibleColumnsWidth === 'function') {
          plugin.calculateVisibleColumnsWidth();
        }
      } catch (_err) {
      }
    }

    for (var col = 0; col < columnCount; col++) {
      var min = getColumnMinWidth(col);
      var max = getColumnMaxWidth(col);
      var width = null;

      if (plugin && typeof plugin.getColumnWidth === 'function') {
        try {
          width = plugin.getColumnWidth(col);
        } catch (_err2) {
          width = null;
        }
      }

      if (!Number.isFinite(width) || width <= 0) {
        var header = hot ? String(hot.getColHeader(col) || '') : '';
        width = (header.length * 8) + 24;
      }

      width = Math.ceil(width + 8);
      if (width < min) {
        width = min;
      }
      if (width > max) {
        width = max;
      }

      widths.push(width);
    }

    return widths;
  }

  function reduceWidthsToTarget(widths, targetWidth, lowerBounds) {
    var reducedWidths = widths.slice();
    var total = sumWidths(reducedWidths);
    if (total <= targetWidth) {
      return reducedWidths;
    }

    var excess = total - targetWidth;
    var capacities = [];
    var totalCapacity = 0;

    for (var col = 0; col < reducedWidths.length; col++) {
      var lowerBound = Number(lowerBounds[col]);
      if (!Number.isFinite(lowerBound) || lowerBound < 20) {
        lowerBound = 20;
      }
      var cap = Math.max(0, reducedWidths[col] - lowerBound);
      capacities[col] = cap;
      totalCapacity += cap;
    }

    if (totalCapacity <= 0) {
      return reducedWidths;
    }

    if (totalCapacity <= excess) {
      for (var c = 0; c < reducedWidths.length; c++) {
        reducedWidths[c] = Number(lowerBounds[c]) || 20;
      }
      return reducedWidths;
    }

    var reduced = 0;
    for (var i = 0; i < reducedWidths.length; i++) {
      var capacity = capacities[i];
      if (capacity <= 0) {
        continue;
      }

      var step = Math.floor((excess * capacity) / totalCapacity);
      if (step > capacity) {
        step = capacity;
      }
      if (step > 0) {
        reducedWidths[i] -= step;
        reduced += step;
      }
    }

    var remainder = excess - reduced;
    var guard = 0;
    while (remainder > 0 && guard < 4000) {
      for (var p = 0; p < columnShrinkPriority.length && remainder > 0; p++) {
        var index = columnShrinkPriority[p];
        if (index < 0 || index >= reducedWidths.length) {
          continue;
        }

        var bound = Number(lowerBounds[index]) || 20;
        if (reducedWidths[index] > bound) {
          reducedWidths[index] -= 1;
          remainder -= 1;
        }
      }
      guard += 1;
    }

    return reducedWidths;
  }

  function expandWidthsToTarget(widths, targetWidth, upperBounds) {
    var expandedWidths = widths.slice();
    var total = sumWidths(expandedWidths);
    if (total >= targetWidth) {
      return expandedWidths;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var growPriority = columnShrinkPriority.slice().reverse();

    while (remainder > 0 && guard < 5000) {
      var grew = false;
      for (var i = 0; i < growPriority.length && remainder > 0; i++) {
        var index = growPriority[i];
        if (index < 0 || index >= expandedWidths.length) {
          continue;
        }

        var upperBound = Number(upperBounds[index]);
        if (!Number.isFinite(upperBound) || upperBound <= 0) {
          upperBound = expandedWidths[index] + remainder;
        }

        if (expandedWidths[index] < upperBound) {
          expandedWidths[index] += 1;
          remainder -= 1;
          grew = true;
        }
      }

      if (!grew) {
        break;
      }
      guard += 1;
    }

    return expandedWidths;
  }

  function forceFillWidthsToTarget(widths, targetWidth) {
    var filled = widths.slice();
    var total = sumWidths(filled);
    if (total >= targetWidth) {
      return filled;
    }

    var remainder = targetWidth - total;
    var guard = 0;
    var growPriority = columnShrinkPriority.slice().reverse();

    while (remainder > 0 && guard < 6000) {
      for (var i = 0; i < growPriority.length && remainder > 0; i++) {
        var index = growPriority[i];
        if (index < 0 || index >= filled.length) {
          continue;
        }
        filled[index] += 1;
        remainder -= 1;
      }
      guard += 1;
    }

    return filled;
  }

  function constrainColumnWidthsToContainer(widths, targetWidth) {
    var constrained = reduceWidthsToTarget(widths, targetWidth, columnMinWidths);
    if (sumWidths(constrained) > targetWidth) {
      constrained = reduceWidthsToTarget(constrained, targetWidth, columnFloorWidths);
    }

    if (sumWidths(constrained) < targetWidth) {
      constrained = expandWidthsToTarget(constrained, targetWidth, columnMaxWidths);
    }

    if (sumWidths(constrained) < targetWidth) {
      constrained = forceFillWidthsToTarget(constrained, targetWidth);
    }

    return constrained;
  }

  function applyResponsiveColumnWidths(force) {
    if (!hot) {
      return;
    }

    var settings = hot.getSettings() || {};
    var columns = Array.isArray(settings.columns) ? settings.columns : [];
    var columnCount = columns.length;
    if (!columnCount) {
      return;
    }

    var containerWidth = getContainerAvailableWidth();
    if (!containerWidth) {
      return;
    }

    if (!force && containerWidth === lastAppliedContainerWidth && currentColumnWidths.length === columnCount) {
      return;
    }

    var baseWidths = getBaseColumnWidths(columnCount);
    var constrained = constrainColumnWidthsToContainer(baseWidths, containerWidth);

    if (!force && arraysEqualNumbers(currentColumnWidths, constrained)) {
      lastAppliedContainerWidth = containerWidth;
      return;
    }

    hot.updateSettings({ colWidths: constrained });
    currentColumnWidths = constrained.slice();
    lastAppliedContainerWidth = containerWidth;
  }

  function scheduleLayoutRefresh(delay, force) {
    clearTimeout(layoutTimer);
    layoutTimer = setTimeout(function () {
      if (!hot) {
        return;
      }

      var viewportState = pendingViewportState;
      pendingViewportState = null;

      syncContainerHeight();
      var containerHeight = getContainerAvailableHeight();
      if (containerHeight && (Boolean(force) || containerHeight !== lastAppliedContainerHeight)) {
        hot.updateSettings({ height: containerHeight });
        lastAppliedContainerHeight = containerHeight;
      }

      if (typeof hot.refreshDimensions === 'function') {
        hot.refreshDimensions();
      }

      applyResponsiveColumnWidths(Boolean(force));
      hot.render();
      syncRenderedTableWidth(hot);

      if (viewportState) {
        setTimeout(function () {
          restoreViewportState(viewportState);
        }, 0);
      }
    }, Number.isFinite(delay) ? delay : 24);
  }

  function rowMatchesFilters(row) {
    var activityFilter = String($('#buscadorActividad').val() || '').trim().toLowerCase();
    var semanasFilter = String($('#buscadorSemanasInicio').val() || '').trim();
    var liberadaFilter = String($('#buscadorLiberada').val() || '').trim();
    var subFilter = String($('#buscadorSubcontratista').val() || '').trim().toLowerCase();
    var respFilter = String($('#buscadorResponsableAIA').val() || '').trim().toLowerCase();

    if (activityFilter) {
      if (getActividadPlainText(row.Actividad).toLowerCase().indexOf(activityFilter) === -1) {
        return false;
      }
    }

    if (semanasFilter) {
      var si = Math.round(toNumber(row.Semanas_Inicio, 999));
      if (semanasFilter === '7') {
        if (si < 7) {
          return false;
        }
      } else if (String(si) !== semanasFilter) {
        return false;
      }
    }

    if (liberadaFilter) {
      var readyToCommit = window.PIStateMachine && typeof window.PIStateMachine.isReadyToCommit === 'function'
        ? window.PIStateMachine.isReadyToCommit(row)
        : areHardRestrictionsMet(row);
      if (liberadaFilter === 'NoLiberada' && readyToCommit) {
        return false;
      }
      if (liberadaFilter === 'Liberada' && !readyToCommit) {
        return false;
      }
    }

    if (subFilter) {
      if (String(row.Sub_Contratista || '').toLowerCase() !== subFilter) {
        return false;
      }
    }

    if (respFilter) {
      if (String(row.Responsable_AIA || '').toLowerCase() !== respFilter) {
        return false;
      }
    }

    var state = getState(row);
    if (activeFilters.length > 0 && activeFilters.indexOf(state) === -1) {
      return false;
    }

    return true;
  }

  function updateLegendCounts(rows) {
    var counts = {};
    for (var i = 0; i < trackedStates.length; i++) {
      counts[trackedStates[i]] = 0;
    }

    for (var r = 0; r < rows.length; r++) {
      var state = getState(rows[r]);
      if (counts[state] !== undefined) {
        counts[state] += 1;
      }
    }

    Object.keys(counts).forEach(function (key) {
      setLegendCount(key, counts[key]);
    });
  }

  function openDropdownEditorAtCell(instance, row, col, triggerEvent, reselectCell) {
    if (!instance || row < 0 || col < 0) {
      return;
    }

    var prop = instance.colToProp(col);
    if (!dropdownProps[prop] || !editableProps[prop]) {
      return;
    }

    var rowData = getSourceRowDataByVisualRow(instance, row) || {};
    if (getState(rowData) === 'header' || !isUserAllowedToEdit()) {
      return;
    }

    var currentValue = instance.getDataAtRowProp(row, prop);
    if (reselectCell !== false) {
      instance.selectCell(row, col, row, col, false, false);
    }

    setTimeout(function () {
      if (!instance) {
        return;
      }

      var editor = instance.getActiveEditor ? instance.getActiveEditor() : null;
      if (!editor) {
        return;
      }

      try {
        if (typeof editor.enableFullEditMode === 'function') {
          editor.enableFullEditMode();
        }

        editor.beginEditing(currentValue, triggerEvent || null);
        if (typeof editor.open === 'function' && (!editor.isOpened || !editor.isOpened())) {
          editor.open(triggerEvent || null);
        }
      } catch (_err) {
      }
    }, 0);
  }

  function updateOrInitHot(data) {
    setupRenderers();
    syncContainerHeight();
    buildRowClassCache(data);

    if (hot) {
      var filterConditions = captureHotFilterConditions();
      pendingViewportState = captureViewportState();
      hot.loadData(data);
      restoreHotFilterConditions(filterConditions);
      hot.render();
      scheduleLayoutRefresh(0, true);
      return;
    }

    var container = document.getElementById('hot-container');
    if (!container) {
      return;
    }

    hot = new Handsontable(container, {
      data: data,
      rowHeaders: false,
      colHeaders: buildColumnHeaders(),
      columns: buildColumnDefinitions(),
      licenseKey: 'non-commercial-and-evaluation',
      language: 'es-MX',
      stretchH: 'none',
      autoColumnSize: false,
      manualColumnResize: false,
      manualRowResize: true,
      autoRowSize: false,
      rowHeights: 56,
      renderAllRows: false,
      colWidths: function (index) {
        var container = document.getElementById('hot-container');
        var baseWidth = container ? container.clientWidth : window.innerWidth;
        var availableWidth = Math.max(320, Math.floor((baseWidth || 0) - 60));
        var ratio = Number(columnWidthRatios[index]);
        if (!Number.isFinite(ratio) || ratio <= 0) {
          ratio = 1 / 17;
        }

        var width = Math.floor(availableWidth * ratio);
        var min = 20;
        var max = Number(columnMaxWidths[index]) || 260;
        if (width < min) {
          width = min;
        }
        if (width > max) {
          width = max;
        }

        return width;
      },
      cells: function (row, col) {
        var cellProperties = {};
        var hotInstance = this && this.instance ? this.instance : hot;
        var physicalRow = getPhysicalRowFromVisualRow(hotInstance, row);
        var rowData = getSourceRowDataByVisualRow(hotInstance, row);

        if (!rowData) {
          if (window.__PI_DEBUG_COLOR) {
            console.warn('[PI-DEBUG] cells: no rowData for visualRow', row, 'physicalRow', physicalRow);
          }
          // Fallback a cache cuando rowData no está disponible (durante re-render post-filter)
          if (Number.isInteger(physicalRow) && physicalRow >= 0 && _rowMetaCache[physicalRow]) {
            return buildPICellProperties(
              getColumnBaseClass(hotInstance, col),
              hotInstance && typeof hotInstance.colToProp === 'function' ? hotInstance.colToProp(col) : null,
              _rowMetaCache[physicalRow]
            );
          }
          return cellProperties;
        }

        if (window.__PI_DEBUG_COLOR) {
          var targetPhys = [2, 16, 20, 21, 23, 52, 77];
          var isTarget = targetPhys.indexOf(physicalRow) >= 0 || targetPhys.indexOf(row) >= 0;
          if (isTarget) {
            console.log('[PI-DEBUG] cells called:', { cellsRow: row, visualRow: row, physicalRow: physicalRow, col: col, Id: rowData.Id, estado_operativo: String(rowData.estado_operativo).substring(0, 50) });
          }
        }

        return buildPICellProperties(
          getColumnBaseClass(hotInstance, col),
          hotInstance && typeof hotInstance.colToProp === 'function' ? hotInstance.colToProp(col) : null,
          getPIRowMeta(physicalRow, rowData)
        );
      },
      viewportRowRenderingOffset: 20,
      viewportColumnRenderingOffset: 10,
      contextMenu: true,
      dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
      filters: true,
      modifyFiltersMultiSelectValue: function (value, meta) {
        if (meta && (meta.prop === 'Actividad' || meta.data === 'Actividad')) {
          return getActividadPlainText(value);
        }

        return value;
      },
      search: false,
      exportFile: true,
      columnSorting: false,
      wordWrap: true,
      colHeaderHeight: 48,
      width: '100%',
      height: getContainerAvailableHeight() || '100%',
      afterLoadData: function (sourceData, initialLoad, source) {
        // lazy rendering con cells nativo
      },
      afterRender: function () {
        applyRowClassesToDOM(this);
        syncRenderedTableWidth(this);
        // C-19: necesita el ancho ya aplicado, por eso no va en el renderer.
        refreshHeaderTitles(this);
      },
      afterGetColHeader: function (col, TH) {
        if (!TH || !TH.querySelector) {
          return;
        }

        var headerNode = TH.querySelector('.colHeader');
        if (!headerNode) {
          return;
        }

        var headerText = String(this.getColHeader(col) || '').replace(/\s+/g, ' ').trim();
        headerNode.classList.remove('pi-header-single-word', 'pi-soft-restriction-header');
        TH.classList.remove('pi-soft-restriction-th');

        if (headerText && headerText.indexOf(' ') === -1) {
          headerNode.classList.add('pi-header-single-word');
        }

        // Task 26 ponia aqui el `title`. C-19 (2026-08-05) lo movio a
        // `refreshHeaderTitles()`, que corre en `afterRender` con el ancho ya
        // definitivo y solo lo pone donde el texto se recorta de verdad. Sigue
        // sin colisionar con .pi-help-trigger, que es un elemento aparte
        // (icono "?") con su propio tooltip de Bootstrap.

        // Inject tooltip trigger alongside changeType
        var resProp = headerIndexToRestrictionProp[col];
        if (softRestrictionProps.indexOf(resProp) > -1) {
          TH.classList.add('pi-soft-restriction-th');
          headerNode.classList.add('pi-soft-restriction-header');
        }
        if (resProp && !TH.querySelector('.pi-help-trigger')) {
          var wrapper = TH.querySelector('.relative');
          var changeBtn = wrapper ? wrapper.querySelector('.changeType') : null;
          var trigger = document.createElement('a');
          trigger.href = 'javascript:void(0);';
          trigger.className = 'pi-help-trigger';
          trigger.setAttribute('data-type', resProp);
          if (softRestrictionProps.indexOf(resProp) > -1) {
            trigger.setAttribute('data-soft-label', 'blanda');
          }
          trigger.innerHTML = '<i class="fas fa-question-circle" aria-hidden="true"></i>';
          // F-3: el gatillo entraba en el recorrido del tabulador sin nombre ni
          // contenido: ocho paradas mudas. El nombre sale del mismo titulo que
          // encabeza el tooltip, asi que no hay una segunda fuente que mantener.
          trigger.setAttribute('aria-label', 'Ayuda sobre ' + (popoverTitles[resProp] || resProp));
          $(trigger).tooltip({
            trigger: 'manual', html: true, placement: 'bottom', container: 'body', boundary: 'window',
            template: '<div class="tooltip pi-help-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner tooltip-inner--wide"></div></div>',
            title: function () {
              var type = $(this).attr('data-type') || resProp;
              return '<h6 class="font-weight-bold border-bottom pb-2 mb-2">' + (popoverTitles[type] || '') + '</h6>' + (popoverContent[type] || '');
            }
          });
          if (changeBtn) {
            // Wrap both in a horizontal row
            var row = document.createElement('div');
            row.className = 'pi-header-controls';
            changeBtn.parentNode.insertBefore(row, changeBtn);
            row.appendChild(trigger);
            row.appendChild(changeBtn);
          } else {
            (wrapper || headerNode).appendChild(trigger);
          }
        }
      },
      beforeKeyDown: function (event) {
        if (!event) {
          return;
        }

        var key = String(event.key || '');
        var isNav = key === 'Tab' || key === 'Enter' ||
                    key === 'ArrowUp' || key === 'ArrowDown' ||
                    key === 'ArrowLeft' || key === 'ArrowRight';
        if (isNav && !event.ctrlKey && !event.metaKey && !event.altKey) {
          pendingTabSelection = true;
          return;
        }
        pendingTabSelection = false;
      },
      afterSelectionEnd: function (row, col) {
        var isKeyNav = pendingTabSelection || window.__piPendingNav;
        pendingTabSelection = false;
        window.__piPendingNav = false;

        if (!isKeyNav) {
          return;
        }

        var prop = this.colToProp(col);
        if (dropdownProps[prop]) {
          var hot = this;
          openDropdownEditorAtCell(hot, row, col, null, false);
        }
      },
      afterOnCellMouseDown: function (event, coords) {
        if (!coords || coords.row < 0 || coords.col < 0) {
          return;
        }

        if (!event || event.button !== 0 || event.shiftKey || event.ctrlKey || event.metaKey || event.altKey) {
          return;
        }

        var prop = this.colToProp(coords.col);
        if (!dropdownProps[prop] || !editableProps[prop]) {
          return;
        }

        openDropdownEditorAtCell(this, coords.row, coords.col, event, true);
      },
      afterChange: function (changes, source) {
        if (!changes || source === 'loadData' || source === 'revert' || source === 'internal-update') {
          return;
        }

        for (var i = 0; i < changes.length; i++) {
          var change = changes[i];
          if (!change) continue;
          var visualRow = change[0];
          var prop = change[1];
          var oldValue = change[2];
          var newValue = change[3];

          var physicalRow = this.toPhysicalRow(visualRow);
          if (visualRow === null || visualRow < 0) {
            continue;
          }

          var isRestrictionChange = restrictionProps.indexOf(prop) > -1;

          if (prop === '__shared_selected') {
            if (oldValue !== newValue) {
              updateSharedSelectionFromVisualRow(visualRow, newValue);
            }
            continue;
          }

          if (!editableProps[prop] || oldValue === newValue) {
            continue;
          }

          if (prop === 'Sub_Contratista' && newValue && newValue.indexOf(PI_CREATE_SUB) > -1) {
            var updatedValue = newValue.replace(PI_CREATE_SUB, '').replace(/,\s*,/g, ',').replace(/(^,)|(,$)/g, '').trim();
            if (updatedValue !== (oldValue || '')) {
              hot.setDataAtCell(visualRow, hot.propToCol(prop), updatedValue, 'edit');
            } else {
              revertCell(visualRow, prop, oldValue);
            }
            window.open('/subcontratistas', '_blank');
            continue;
          }
          if (prop === 'Responsable_AIA' && newValue && newValue.indexOf(PI_CREATE_PROF) > -1) {
            var updatedValueProf = newValue.replace(PI_CREATE_PROF, '').replace(/,\s*,/g, ',').replace(/(^,)|(,$)/g, '').trim();
            if (updatedValueProf !== (oldValue || '')) {
              hot.setDataAtCell(visualRow, hot.propToCol(prop), updatedValueProf, 'edit');
            } else {
              revertCell(visualRow, prop, oldValue);
            }
            window.open('/profesionales', '_blank');
            continue;
          }

          if (isRestrictionChange) {
            var rowData = getSourceRowDataByVisualRow(this, visualRow) || {};
            var respValue = hasAssignedValue(rowData.Responsable_AIA, PI_CREATE_PROF) ? rowData.Responsable_AIA : this.getDataAtRowProp(visualRow, 'Responsable_AIA');
            var hasResp = hasAssignedValue(respValue, PI_CREATE_PROF);
            if (!hasResp) {
              // N-1 (Task 38): la celda ya nace readOnly cuando falta el
              // Responsable AIA, asi que por la UI no se llega aqui. Se
              // conserva como red de seguridad para pegados o cambios
              // programaticos, que no pasan por el editor.
              revertCell(visualRow, prop, oldValue);
              showFeedback('error', 'No puede gestionar restricciones de una actividad sin asignar Responsable AIA');
              continue;
            }
          }

          var normalized = normalizeCellValue(prop, newValue);
          if (!normalized.valid) {
            revertCell(visualRow, prop, oldValue);
            showFeedback('error', normalized.error);
            continue;
          }

          if (normalized.value !== newValue) {
            invalidatePIRowCache(physicalRow, getSourceRowDataByVisualRow(this, visualRow));
            hot.setDataAtRowProp(visualRow, prop, normalized.value, 'internal-update');
          }

          if (isRestrictionChange) {
            recalculateRestrictionStateForVisualRow(visualRow);
          }

          if (prop === 'Responsable_AIA') {
            syncRestrictionLockForVisualRow(visualRow);
          }

          saveRow(visualRow, prop, oldValue);
        }
      },
    });

    // Fix: Asegurar que HOT mantenga el listening activo.
    // Bootstrap/jQuery roban el foco a nivel de document.
    hot.listen();

    // Hook post-filter: actualiza directamente el className de las celdas visibles.
    // El callback cells de Handsontable no re-evalúa celdas con cellMeta cacheado,
    // por lo que hot.render() no corrige las clases existentes. Este hook bypass
    // el cache de cellMeta y aplica las clases correctas directamente al DOM.
    hot.addHook('afterFilter', function () {
      if (!hot) return;
      resetPIRowCaches();
      buildRowClassCache(hot.getSourceData());
      applyRowClassesToDOM(hot);
    });

    container.addEventListener('mousedown', function () {
      if (hot && !hot.isDestroyed) { hot.listen(); }
    }, true);

    // MutationObserver: estilar ítems "Crear" en dropdowns
    (function () {
      function styleCreateItems() {
        var editors = document.querySelectorAll('.autocompleteEditor:not([style*="display: none"]) td');
        for (var i = 0; i < editors.length; i++) {
          var txt = (editors[i].textContent || '').trim();
          if (txt.indexOf('Crear') > -1 && !editors[i].classList.contains('pi-create-option')) {
            editors[i].classList.add('pi-create-option');
          }
        }
      }

      var observer = new MutationObserver(function (mutations) {
        for (var m = 0; m < mutations.length; m++) {
          if (mutations[m].addedNodes.length > 0 || mutations[m].type === 'attributes') {
            styleCreateItems();
            break;
          }
        }
      });

      observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
    })();

    if (window.LPSContextualDrawer) {
      window.LPSContextualDrawer.init(hot, 'programacion-intermedia', getStateView);
    }

    scheduleLayoutRefresh(0, true);
  }

  function getFilteredRows() {
    var filtered = [];
    for (var i = 0; i < masterData.length; i++) {
      if (rowMatchesFilters(masterData[i])) {
        filtered.push(masterData[i]);
      }
    }
    return filtered;
  }

  function contarDurasLiberadas(row) {
    var duras = Array.isArray(hardRestrictionProps) ? hardRestrictionProps : [];
    var liberadas = 0;
    var faltantes = [];
    for (var i = 0; i < duras.length; i++) {
      var clave = duras[i];
      var entrada = null;
      for (var j = 0; j < _activeRestrictions.length; j++) {
        if (_activeRestrictions[j].key === clave) { entrada = _activeRestrictions[j]; break; }
      }
      var umbral = (entrada && entrada.threshold ? entrada.threshold : 100) / 100;
      var bruto = String((row && row[clave]) === null || (row && row[clave]) === undefined ? '' : row[clave]).trim().toUpperCase();
      // `N/A` cuenta como LIBERADA, no como faltante: es lo que hace
      // restrictionMeets() (stateMachine.js:114-116), del que depende
      // isReadyToCommit(). Si el contador lo tratara como pendiente, diria
      // "4 de 5" en una actividad que su propio chip da por lista — el mismo
      // desajuste que E2-bis-e vino a corregir.
      if (bruto === 'N/A' || bruto === 'NO APLICA') {
        liberadas += 1;
        continue;
      }
      var valor = normalizePercentRatio(row && row[clave]);
      if (valor !== null && valor + 0.0001 >= umbral) {
        liberadas += 1;
      } else {
        faltantes.push((entrada && entrada.label) ? entrada.label : clave);
      }
    }
    return { liberadas: liberadas, total: duras.length, faltantes: faltantes };
  }

  function construirDetalleRestricciones(row, index) {
    var detalle = document.createElement('details');
    detalle.className = 'pi-mobile-card__detalle';
    detalle.dataset.rowIndex = String(index);

    var resumen = document.createElement('summary');
    resumen.textContent = 'Liberar restricciones';
    detalle.appendChild(resumen);

    var meta = getPIRowMeta(getPhysicalRowFromVisualRow(hot, index), row || {});
    var reglas = reglasIntermediaActuales();

    for (var i = 0; i < restrictionProps.length; i++) {
      var clave = restrictionProps[i];
      var entrada = null;
      for (var j = 0; j < _activeRestrictions.length; j++) {
        if (_activeRestrictions[j].key === clave) { entrada = _activeRestrictions[j]; break; }
      }
      var puedeEditar = reglas.puedeEditarCelda({
        prop: clave,
        esHeader: meta.isHeader,
        tieneResponsable: meta.hasResponsable,
        esRestriccion: true,
      });

      var fila = document.createElement('div');
      fila.className = 'pi-mobile-card__restriccion';

      var etiqueta = document.createElement('label');
      etiqueta.className = 'pi-mobile-card__restriccion-label';
      etiqueta.textContent = (entrada && entrada.label) ? entrada.label : clave;
      etiqueta.setAttribute('for', 'pi-restr-' + index + '-' + clave);
      fila.appendChild(etiqueta);

      var control = document.createElement('select');
      control.id = 'pi-restr-' + index + '-' + clave;
      control.dataset.piRestriccion = clave;
      control.dataset.rowIndex = String(index);
      control.disabled = !puedeEditar;
      var opciones = (entrada && Array.isArray(entrada.options)) ? [''].concat(entrada.options) : [''];
      for (var k = 0; k < opciones.length; k++) {
        var opt = document.createElement('option');
        opt.value = opciones[k];
        opt.textContent = opciones[k] === '' ? '—' : opciones[k];
        if (String(row && row[clave] || '') === opciones[k]) opt.selected = true;
        control.appendChild(opt);
      }
      fila.appendChild(control);
      detalle.appendChild(fila);
    }

    if (!meta.hasResponsable) {
      var aviso = document.createElement('p');
      aviso.className = 'pi-mobile-card__aviso';
      aviso.textContent = 'Asigna un Responsable AIA para liberar restricciones.';
      detalle.appendChild(aviso);
    }

    var pie = document.createElement('p');
    pie.className = 'pi-mobile-card__pie';
    pie.textContent = ((row && row.Sub_Contratista) ? row.Sub_Contratista : 'Sin sub-contratista')
      + ' · Inicio ' + ((row && row.Semanas_Inicio !== undefined && row.Semanas_Inicio !== null) ? row.Semanas_Inicio : '—');
    detalle.appendChild(pie);

    return detalle;
  }

  function createMobileCard(row, index) {
    var view = getStateView(row || {});
    var partes = window.AIACardTitle
      ? window.AIACardTitle.separarCapitulo(row && row.Actividad)
      : { titulo: view.activity, capitulo: null };
    var conteo = contarDurasLiberadas(row || {});

    var card = document.createElement('article');
    card.className = 'pi-mobile-card';
    card.dataset.rowIndex = String(index);

    var header = document.createElement('header');
    header.className = 'pi-mobile-card__header';
    var identity = document.createElement('div');
    identity.className = 'pi-mobile-card__identity';

    var id = document.createElement('span');
    id.className = 'pi-mobile-card__id';
    id.textContent = row && row.Id ? 'ID ' + row.Id : 'Actividad';
    identity.appendChild(id);

    var title = document.createElement('h3');
    title.className = 'pi-mobile-card__title';
    title.textContent = partes.titulo || 'Actividad sin nombre';
    identity.appendChild(title);

    if (partes.capitulo) {
      var cap = document.createElement('p');
      cap.className = 'pi-mobile-card__capitulo';
      cap.textContent = partes.capitulo;
      identity.appendChild(cap);
    }

    var state = document.createElement('span');
    state.className = 'pi-mobile-card__state';
    state.textContent = conteo.liberadas + ' de ' + conteo.total;

    header.appendChild(identity);
    header.appendChild(state);
    card.appendChild(header);

    var barra = document.createElement('div');
    barra.className = 'pi-mobile-card__barra';
    for (var b = 0; b < conteo.total; b++) {
      var seg = document.createElement('span');
      seg.className = b < conteo.liberadas ? 'is-liberada' : 'is-pendiente';
      barra.appendChild(seg);
    }
    card.appendChild(barra);

    if (conteo.faltantes.length) {
      var foco = document.createElement('p');
      foco.className = 'pi-mobile-card__foco';
      foco.textContent = 'Faltan ' + conteo.faltantes.join(', ');
      card.appendChild(foco);
    }

    var resp = document.createElement('p');
    resp.className = 'pi-mobile-card__responsable';
    resp.textContent = (row && row.Responsable_AIA) ? row.Responsable_AIA : 'Sin responsable';
    card.appendChild(resp);

    card.appendChild(construirDetalleRestricciones(row, index));
    return card;
  }

  function renderMobileCards(rows) {
    var container = document.getElementById('mobile-card-view');
    if (!container) {
      return;
    }

    var isMobile = window.matchMedia('(max-width: 1179px)').matches;

    // Hallazgo 2 (revision 2026-08-14): cada guardado exitoso repintaba las
    // siete tarjetas de restriccion sin `open`, asi que liberar una
    // actividad completa cerraba el desplegable siete veces. Se captura el
    // estado abierto y el control con foco ANTES de vaciar el contenedor,
    // para reponerlos despues de reconstruir el DOM.
    var indicesAbiertos = {};
    var activo = document.activeElement;
    var focoRowIndex = null;
    var focoRestriccion = null;
    if (activo && container.contains(activo) && activo.dataset && activo.dataset.piRestriccion) {
      focoRowIndex = activo.dataset.rowIndex;
      focoRestriccion = activo.dataset.piRestriccion;
    }
    var detallesAbiertos = container.querySelectorAll('details.pi-mobile-card__detalle[open]');
    for (var d = 0; d < detallesAbiertos.length; d++) {
      if (detallesAbiertos[d].dataset.rowIndex !== undefined) {
        indicesAbiertos[detallesAbiertos[d].dataset.rowIndex] = true;
      }
    }

    container.replaceChildren();
    if (!isMobile) {
      return;
    }

    // Un solo listener delegado en el contenedor, enganchado una vez: esta
    // funcion se llama en cada filtro y repinta todas las tarjetas, asi que
    // engancharlo por control multiplicaria los listeners en cada repintado
    // (el mismo fallo que ya tuvieron las tarjetas de CNP).
    if (!container.dataset.piRestriccionBound) {
      container.addEventListener('change', function (evento) {
        var control = evento.target.closest('[data-pi-restriccion]');
        if (!control || control.disabled) return;
        var visualRow = Number(control.dataset.rowIndex);
        var prop = control.dataset.piRestriccion;
        if (!Number.isInteger(visualRow) || !prop) return;
        var fila = visibleRows[visualRow];
        if (!fila) return;

        // N-1 (Task 38): la misma red que el listener de escritorio
        // (hot.js:4229-4243) para pegados o cambios programaticos que no
        // pasan por el editor. Por la UI no se llega aqui, porque el
        // control ya nace `disabled` sin Responsable AIA, pero el servidor
        // (ProgramacionIntermediaController::save) no valida esta regla, asi
        // que se conserva como red de seguridad.
        var meta = getPIRowMeta(null, fila);
        if (!meta.hasResponsable) {
          showFeedback('error', 'No puede gestionar restricciones de una actividad sin asignar Responsable AIA');
          renderMobileCards(visibleRows);
          return;
        }

        var anterior = fila[prop];
        fila[prop] = control.value;
        saveRow(visualRow, prop, anterior);
      }, false);
      container.dataset.piRestriccionBound = 'true';
    }

    var items = Array.isArray(rows) ? rows : [];
    if (items.length === 0) {
      var empty = document.createElement('p');
      empty.className = 'pi-mobile-card__empty';
      empty.textContent = 'No hay actividades que coincidan con los filtros.';
      container.appendChild(empty);
      return;
    }

    var list = document.createElement('div');
    list.className = 'pi-mobile-card-list';
    var fragment = document.createDocumentFragment();
    for (var i = 0; i < items.length; i++) {
      fragment.appendChild(createMobileCard(items[i], i));
    }
    list.appendChild(fragment);
    container.appendChild(list);

    var tieneAbiertos = false;
    for (var key in indicesAbiertos) {
      if (Object.prototype.hasOwnProperty.call(indicesAbiertos, key)) {
        tieneAbiertos = true;
        break;
      }
    }
    if (tieneAbiertos) {
      var detallesNuevos = list.querySelectorAll('details.pi-mobile-card__detalle');
      for (var n = 0; n < detallesNuevos.length; n++) {
        if (indicesAbiertos[detallesNuevos[n].dataset.rowIndex]) {
          detallesNuevos[n].open = true;
        }
      }
    }
    if (focoRowIndex !== null && focoRestriccion !== null) {
      var selectorFoco = '[data-pi-restriccion="' + focoRestriccion + '"][data-row-index="' + focoRowIndex + '"]';
      var controlAFocar = list.querySelector(selectorFoco);
      if (controlAFocar) {
        controlAFocar.focus();
      }
    }
  }

  function applyFiltersAndRender() {
    var filtered = getFilteredRows();
    visibleRows = filtered;
    if (piViewAll) {
      updateLegendCountsFromServer();
    } else {
      updateLegendCounts(filtered);
    }
    // E4 (spec 2026-08-07-f2a-piloto-movil-programacion-design.md): bajo el
    // umbral no se instancia Handsontable. Las cards de Intermedia SI editan
    // restricciones (ver construirDetalleRestricciones/renderMobileCards, en
    // este mismo modulo), pero lo hacen contra la misma capa de guardado que
    // usa la tarjeta desktop (createMobileCard/getStateView no dependen de
    // buildRowClassCache ni de ninguna otra cosa que updateOrInitHot deje de
    // correr), asi que no hace falta un camino de guardado alterno como en
    // Semanal.
    if (!window.AIAViewSwitch || window.AIAViewSwitch.shouldRenderCards(window.innerWidth) !== true) {
      updateOrInitHot(filtered);
    }
    renderMobileCards(filtered);
    updateSharedSelectionCountIndicator();
  }

  function syncLegendVisualState() {
    if (activeFilters.length === 0) {
      $('#piLegend .pdc-legend-item').removeClass('inactive-filter');
    } else {
      $('#piLegend .pdc-legend-item').addClass('inactive-filter');
      for (var i = 0; i < activeFilters.length; i++) {
        $("#piLegend .pdc-legend-item[data-filter='" + activeFilters[i] + "']").removeClass('inactive-filter');
      }
    }

    // Estado toggle expuesto a tecnologías de asistencia
    $('#piLegend .pdc-legend-item').each(function () {
      var isActive = activeFilters.indexOf(String($(this).data('filter'))) > -1;
      $(this).attr('aria-pressed', isActive ? 'true' : 'false');
    });

    $('#mobileFilterCount').text(activeFilters.length);
  }

  function toggleLegendFilter(filterState, event) {
    event = event || {};
    var index = activeFilters.indexOf(filterState);
    if (!event.ctrlKey && !event.metaKey) {
      if (activeFilters.length === 1 && activeFilters[0] === filterState) {
        activeFilters = [];
      } else {
        activeFilters = [filterState];
      }
    } else if (index > -1) {
      activeFilters.splice(index, 1);
    } else {
      activeFilters.push(filterState);
    }

    syncLegendVisualState();
    applyFiltersAndRender();
  }

  function bindFilters() {
    $('#buscadorActividad').off('input.pi').on('input.pi', applyFiltersAndRender);
    $('#buscadorSemanasInicio').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorLiberada').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorSubcontratista').off('change.pi').on('change.pi', applyFiltersAndRender);
    $('#buscadorResponsableAIA').off('change.pi').on('change.pi', applyFiltersAndRender);

    $('#btn_limpiar_buscador').off('click.pi').on('click.pi', function () {
      $('#buscadorActividad').val('');
      $('#buscadorSemanasInicio').val('');
      $('#buscadorLiberada').val('');
      $('#buscadorSubcontratista').val('');
      $('#buscadorResponsableAIA').val('');
      activeFilters = [];
      syncLegendVisualState();
      applyFiltersAndRender();
    });

    $('#piLegend').off('click.pi keydown.pi')
      .on('click.pi', '.pdc-legend-item', function (event) {
        var key = $(this).data('filter');
        if (key) {
          toggleLegendFilter(String(key), event);
        }
      })
      .on('keydown.pi', '.pdc-legend-item', function (event) {
        if (event.key === 'Enter' || event.keyCode === 13 || event.keyCode === 32) {
          event.preventDefault();
          var key = $(this).data('filter');
          if (key) {
            toggleLegendFilter(String(key), event);
          }
        }
      });

    window.filterPDC = function (filterState, event) {
      toggleLegendFilter(filterState, event || {});
    };
  }

  function exportCsv() {
    if (!hot) {
      return;
    }

    hot.getPlugin('exportFile').downloadFile('csv', {
      filename: 'programacion_intermedia',
      columnHeaders: true,
      rowHeaders: false,
    });
  }

  function descargarReporte() {
    var db = getDb();
    var semana = getSemana();

    $('#btn_informe_compromisos').prop('disabled', true).text('Generando...');

    $.ajax({
      method: 'POST',
      url: '/reportes/restricciones',
      dataType: 'json',
      data: { db: db, semana: semana },
    }).done(function (response) {
      if (response && response.url) {
        window.location.href = response.url;
      } else {
        showFeedback('error', (response && response.error) ? response.error : 'No se pudo generar el reporte');
      }
    }).fail(function (jqXHR) {
      var serverError = (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.error) ? jqXHR.responseJSON.error : null;
      showFeedback('error', serverError || 'Error de red al generar reporte');
    }).always(function () {
      $('#btn_informe_compromisos').prop('disabled', false).html('Descargar Corte <i class="fas fa-download ml-1"></i>');
    });
  }

  // Interactive tooltips for restriction column headers
  var helpTooltipTimeout = null;
  var helpCurrentTrigger = null;

  // F-3: el tooltip estaba atado solo a mouseenter/mouseleave, asi que quien
  // llegaba con el tabulador no veia nada. `abrirAyuda`/`cerrarAyuda` sacan esa
  // logica del manejador del raton para poder reusarla desde foco y teclado.
  // Los clones de cabecera de Handsontable se hacen copiando el DOM, asi que el
  // gatillo clonado NO trae la instancia de tooltip del original: sin esta
  // inicializacion perezosa, `show` abriria un tooltip vacio.
  function asegurarTooltip($this) {
    if ($this.data('bs.tooltip')) {
      return;
    }
    var type = $this.attr('data-type');
    $this.tooltip({
      trigger: 'manual', html: true,
      placement: 'bottom', container: 'body',
      boundary: 'window',
      template: '<div class="tooltip pi-help-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner tooltip-inner--wide"></div></div>',
      title: function () {
        var typeAttr = $(this).attr('data-type') || type;
        return '<h6 class="font-weight-bold border-bottom pb-2 mb-2">' + (popoverTitles[typeAttr] || '') + '</h6>' + (popoverContent[typeAttr] || '');
      },
    });
  }

  function abrirAyuda($this) {
    if (helpCurrentTrigger && helpCurrentTrigger[0] === $this[0]) {
      clearTimeout(helpTooltipTimeout);
      return;
    }
    clearTimeout(helpTooltipTimeout);
    $('.pi-help-trigger').not($this).tooltip('hide');
    helpCurrentTrigger = $this;
    asegurarTooltip($this);
    $this.tooltip('show');
  }

  function cerrarAyuda($this) {
    $this.tooltip('hide');
    if (helpCurrentTrigger && helpCurrentTrigger[0] === $this[0]) {
      helpCurrentTrigger = null;
    }
  }

  function bindHeaderTooltips() {
    // Foco: misma ayuda que con el raton, sin retardo de cierre — al salir del
    // gatillo con el tabulador el tooltip ya no tiene a donde volver.
    $('body').off('focusin.piHelp').on('focusin.piHelp', '.pi-help-trigger', function () {
      abrirAyuda($(this));
    });
    $('body').off('focusout.piHelp').on('focusout.piHelp', '.pi-help-trigger', function () {
      cerrarAyuda($(this));
    });
    // Escape cierra sin mover el foco, como manda SC 1.4.13; Enter/Espacio
    // alternan para quien prefiere abrir a voluntad.
    $('body').off('keydown.piHelp').on('keydown.piHelp', '.pi-help-trigger', function (e) {
      var $this = $(this);
      // `keyCode` como respaldo: jQuery normaliza `which`, pero no todo emisor de
      // eventos rellena `key` (los sinteticos de automatizacion, por ejemplo).
      var codigo = e.keyCode || e.which;
      if (e.key === 'Escape' || e.key === 'Esc' || codigo === 27) {
        cerrarAyuda($this);
        return;
      }
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar' || codigo === 13 || codigo === 32) {
        e.preventDefault();
        if (helpCurrentTrigger && helpCurrentTrigger[0] === $this[0]) {
          cerrarAyuda($this);
        } else {
          abrirAyuda($this);
        }
      }
    });
    $('body').off('mouseenter.piHelp').on('mouseenter.piHelp', '.pi-help-trigger', function (e) {
      e.stopPropagation();
      abrirAyuda($(this));
    });
    $('body').off('mouseleave.piHelp').on('mouseleave.piHelp', '.pi-help-trigger', function () {
      var $this = $(this);
      helpTooltipTimeout = setTimeout(function () {
        $this.tooltip('hide');
        helpCurrentTrigger = null;
      }, 100);
    });
    $('body').off('mouseenter.piHelpTip').on('mouseenter.piHelpTip', '.pi-help-tooltip', function () {
      clearTimeout(helpTooltipTimeout);
    });
    $('body').off('mouseleave.piHelpTip').on('mouseleave.piHelpTip', '.pi-help-tooltip', function () {
      if (helpCurrentTrigger) {
        helpTooltipTimeout = setTimeout(function () {
          helpCurrentTrigger.tooltip('hide');
          helpCurrentTrigger = null;
        }, 100);
      }
    });
  }

  function refreshDropdownSources() {
    var db = getDb();
    if (!db) { showFeedback('error', 'No hay proyecto seleccionado'); return; }
    $('#btn-refresh-listas').prop('disabled', true).text('Cargando...');

    var urlSub = '/api/subcontratistas/list?db=' + encodeURIComponent(db);
    var urlProf = '/api/profesionales/list?db=' + encodeURIComponent(db);

    $.when(
      $.getJSON(urlSub),
      $.getJSON(urlProf)
    ).done(function (resSub, resProf) {
      try {
        var rawSub = resSub[0];
        var rawProf = resProf[0];
        var arrSub = (rawSub && Array.isArray(rawSub.data)) ? rawSub.data : [];
        var arrProf = (rawProf && Array.isArray(rawProf.data)) ? rawProf.data : [];

        subcontratistas = ['AIA (MO Directa)'];
        arrSub.forEach(function (s) {
          var name = (s.subcontratista || '').trim();
          if (name) subcontratistas.push(name);
        });
        subcontratistas.push(PI_CREATE_SUB);

        profesionales = [];
        arrProf.forEach(function (p) {
          var name = (p.nombre || '').trim();
          if (name) profesionales.push(name);
        });
        profesionales.push(PI_CREATE_PROF);

        if (hot) {
          var cols = hot.getSettings().columns;
          cols.forEach(function (col) {
            if (col.data === 'Sub_Contratista') {
              col.tomSelectOptions = subcontratistas;
            }
            if (col.data === 'Responsable_AIA') col.tomSelectOptions = profesionales;
          });
          hot.updateSettings({ columns: cols });
        }
        populateSharedAssignmentOptions();
        showFeedback('success', 'Listas actualizadas');
      } catch (e) {
        showFeedback('error', 'Error al procesar las listas');
      }
    }).fail(function () {
      showFeedback('error', 'Error al conectar con el servidor');
    }).always(function () {
      $('#btn-refresh-listas').prop('disabled', false).html('<i class="fas fa-sync" aria-hidden="true"></i> Listas');
    });
  }

  function bindActions() {
    bindHeaderTooltips();
    $('#btn-refresh').off('click.piRefresh').on('click.piRefresh', loadData);
    $('#btn-refresh-listas').off('click.piRefreshListas').on('click.piRefreshListas', refreshDropdownSources);
    $('#btn-export').off('click.piExport').on('click.piExport', exportCsv);
    $('#btn_informe_compromisos').off('click.piReport').on('click.piReport', descargarReporte);
    $('#btn-shared-select-visible').off('click.piSharedVisible').on('click.piSharedVisible', selectVisibleRowsForSharedConstraint);
    $('#btn-shared-clear-selection').off('click.piSharedClear').on('click.piSharedClear', clearSharedSelection);
    $('#btn-shared-constraint').off('click.piSharedOpen').on('click.piSharedOpen', function () {
      if (!hot) {
        showFeedback('error', 'La tabla aún no está lista. Espera a que termine de cargar e inténtalo de nuevo.');
        return;
      }

      if (!isUserAllowedToEdit()) {
        showFeedback('error', 'No tiene permiso para aplicar restricciones en lote.');
        return;
      }

      var selectedIdsForOpen = collectSelectedActivityIds();
      if (selectedIdsForOpen.length < 2) {
        showFeedback('error', 'Marque al menos 2 filas en la columna Lote, o use "Seleccionar visibles", antes de abrir Restricción Compartida. Con una sola actividad no hay lote que comparar.', { title: 'Selección insuficiente' });
        return;
      }

      resetSharedConstraintModal();
      $('#modal_shared_constraint').modal('show');
    });

    $('#piViewAllToggle')
      .off('change.piViewAll')
      .on('change.piViewAll', function () {
        var $checkbox = $(this);
        var activa = $checkbox.is(':checked') ? 1 : 0;
        var $wrapper = $checkbox.closest('.pi-view-all-toggle');

        $checkbox.prop('disabled', true);
        $wrapper.toggleClass('is-on', !!activa);

        $.ajax({
          method: 'GET',
          url: '/programacion-intermedia/set-view-all',
          dataType: 'json',
          data: { activa: activa, ajax: 1 },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).done(function (response) {
          if (response && response.respuesta === 'BIEN') {
            loadData();
            showFeedback(
              'success',
              activa
                ? 'Mostrando todas las actividades (incluyendo las que estan fuera de la ventana de 6 semanas).'
                : 'Vista limitada a la ventana de 6 semanas de liberacion de restricciones.'
            );
          } else {
            $checkbox.prop('checked', !activa);
            $wrapper.toggleClass('is-on', !activa);
            showFeedback('error', 'No se pudo cambiar la vista de actividades.');
          }
        }).fail(function () {
          $checkbox.prop('checked', !activa);
          $wrapper.toggleClass('is-on', !activa);
          showFeedback('error', 'Error de red al cambiar la vista de actividades.');
        }).always(function () {
          $checkbox.prop('disabled', false);
        });
      });

    $('.pi-shared-restriction-check, .pi-shared-restriction-value')
      .off('change.piSharedRestrictions')
      .on('change.piSharedRestrictions', function () {
        syncSharedRestrictionRows();
        renderSharedPreviewEmpty('Restricciones actualizadas. Pulse "Ver Conflictos" para validar impacto.');
      });

    $('#btn_pi_shared_select_all_restrictions')
      .off('click.piSharedAllRestrictions')
      .on('click.piSharedAllRestrictions', function () {
        setSharedRestrictionSelection(true);
      });

    $('#btn_pi_shared_clear_restrictions')
      .off('click.piSharedClearRestrictions')
      .on('click.piSharedClearRestrictions', function () {
        setSharedRestrictionSelection(false);
      });

    $('#piSharedApplyRestriction, #piSharedApplyAssignments')
      .off('change.piSharedOperations')
      .on('change.piSharedOperations', function () {
        syncSharedOperationControls();
        if (! $('#piSharedApplyAssignments').is(':checked')) {
          lastSharedPreviewKey = null;
          lastSharedPreviewStats = null;
        }
        renderSharedPreviewEmpty('Configuración actualizada. Pulse "Ver Conflictos" para validar impacto.');
      });

    $('#btn_pi_shared_preview').off('click.piSharedPreview').on('click.piSharedPreview', requestSharedConstraintPreview);
    $('#btn_pi_shared_apply').off('click.piSharedApply').on('click.piSharedApply', requestSharedConstraintApply);
    $('#btn_pi_shared_use_marked').off('click.piSharedUseMarked').on('click.piSharedUseMarked', loadMarkedIdsForSharedConstraint);
    $('#btn_pi_shared_use_visible').off('click.piSharedUseVisible').on('click.piSharedUseVisible', loadVisibleIdsForSharedConstraint);
    $('#btn_pi_shared_clear_ids').off('click.piSharedClearIds').on('click.piSharedClearIds', clearSharedIdsInput);

    $('#pdcFiltersMobile')
      .off('shown.bs.collapse.piLayout hidden.bs.collapse.piLayout')
      .on('shown.bs.collapse.piLayout hidden.bs.collapse.piLayout', function () {
        scheduleLayoutRefresh(0, true);
      });

    $(document)
      .off('show.bs.modal.piLegend', '#modal_leyenda_colores')
      .on('show.bs.modal.piLegend', '#modal_leyenda_colores', renderLegendModal)
      .off('shown.bs.modal.piShared', '#modal_shared_constraint')
      .on('shown.bs.modal.piShared', '#modal_shared_constraint', function () {
        var selectedIds = collectSelectedActivityIds();
        if (selectedIds.length > 0) {
          loadSharedIdsIntoInput(selectedIds, 'seleccion actual');
        }
      });
  }

  function bindResize() {
    $(window)
      .off('resize.piHot orientationchange.piHot aia:viewport-scale-change.piHot')
      .on('resize.piHot orientationchange.piHot aia:viewport-scale-change.piHot', function () {
        renderMobileCards(visibleRows);
        scheduleLayoutRefresh(80, true);
      });
  }

  function init() {
    if (!initialized) {
      bindActions();
      bindFilters();
      bindResize();
      renderLegendModal();
      // Sesión caducada: la decisión de qué hacer ante un 401 con `sessionExpired`
      // vive en AIA.SessionExpiredHandler (public/js/core/SessionExpiredHandler.js).
      if (window.AIA && window.AIA.SessionExpiredHandler) {
        window.AIA.SessionExpiredHandler.bindWithShowFeedback($, showFeedback);
      }
      initialized = true;
    }

    if (typeof window.maestroPermisos === 'function') {
      window.maestroPermisos($('#permiso_canonico').val() || getPermiso());
    }

    syncLegendVisualState();
    updateSharedSelectionCountIndicator();
    fetchRestrictionConfig(function () {
      loadData();
    });
  }

  window.PIHotModule = {
    init: init,
    getHotInstance: function () { return hot; },
  };
})(window, jQuery);
