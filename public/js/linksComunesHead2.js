/**
 * Loader for Common Head Resources (v2)
 * Uses standard DOM manipulation to ensure scripts execute.
 */
(function () {
  var head = document.getElementById('head') || document.getElementsByTagName('head')[0];

  // Target localized static content paths
  // Using document.createElement instead of innerHTML to avoid destroying the existing head elements (scripts, styles, metadata)
  var metaCharset = document.querySelector('meta[charset]') || document.createElement('meta');
  if (!metaCharset.parentNode) {
    metaCharset.setAttribute('charset', 'UTF-8');
    head.appendChild(metaCharset);
  }

  var titleTag = document.getElementsByTagName('title')[0] || document.createElement('title');
  titleTag.textContent = 'Last Planner AIA';
  if (!titleTag.parentNode) {
    head.appendChild(titleTag);
  }

  // Helper to inject link stylesheet if not already present
  function injectStylesheet(href, id) {
    var query = id ? '#' + id : 'link[href*="' + href.split('?')[0] + '"]';
    if (!document.querySelector(query)) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      if (id) link.id = id;
      link.href = href;
      head.appendChild(link);
    }
  }

  // Inject meta viewport
  if (!document.querySelector('meta[name="viewport"]')) {
    var metaView = document.createElement('meta');
    metaView.name = 'viewport';
    metaView.content = 'width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0';
    head.appendChild(metaView);
  }

  // Icon
  if (!document.querySelector('link[rel*="icon"]')) {
    var favicon = document.createElement('link');
    favicon.rel = 'shortcut icon';
    favicon.href = '/img/florAIA.png';
    head.appendChild(favicon);
  }

  // Fuentes de Google (Mantenido CDN por ahora ya que es tipográfico, pero cargado de forma no bloqueante)
  injectStylesheet('https://fonts.googleapis.com/css?family=Roboto&display=swap');

  // VENDORS LOCALIZADOS (Carga local instantánea 0ms)
  injectStylesheet('/public/vendor/font-awesome/css/all.css');
  injectStylesheet('/public/vendor/bootstrap/bootstrap.min.css');
  
  // DataTables local
  injectStylesheet('/public/vendor/datatables/jquery.dataTables.css');
  injectStylesheet('/public/vendor/datatables/dataTables.bootstrap4.min.css');
  injectStylesheet('/public/vendor/datatables/buttons.bootstrap4.min.css');
  injectStylesheet('/public/vendor/datatables/dataTables.checkboxes.css');

  // jQuery UI local
  injectStylesheet('/public/vendor/jquery-ui.css');

  // AnyChart local
  injectStylesheet('/public/vendor/anychart/anychart-ui.min.css');
  injectStylesheet('/public/vendor/anychart/anychart-font.min.css');

  // Select2 local
  injectStylesheet('/public/vendor/select2/select2.min.css');

  // SweetAlert2 local
  injectStylesheet('/public/vendor/sweetalert2.min.css');

  // Estilos Personalizados Locales
  injectStylesheet('/css/tokens.css?v=1.0');
  injectStylesheet('/css/styles.css?v=pdcLegendSpacing2');
  injectStylesheet('/css/buttons.css?v=1.0');
  injectStylesheet('/css/access.css?v=1.0');

  // Inyectar escudo de bordes unificados de altísima especificidad contra la caché agresiva del navegador
  var dynamicStyle = document.createElement('style');
  dynamicStyle.innerHTML = "html body .table th, html body .table td, html body table.dataTable tbody tr td, html body .table-bordered tbody tr td, html body table.dataTable thead tr th, html body .table-bordered thead tr th { border-color: #cbd5e1 !important; border-right: 1px solid #cbd5e1 !important; border-bottom: 1px solid #cbd5e1 !important; border-left-color: #cbd5e1 !important; border-top-color: #cbd5e1 !important; } html body #hot-container .handsontable tbody tr td { border-right: 1px solid #cbd5e1 !important; border-bottom: 1px solid #cbd5e1 !important; }";
  head.appendChild(dynamicStyle);

  // Helper to load script
  function loadScript(src) {
    // Evitar duplicaciones
    var cleanSrc = src.split('?')[0];
    if (document.querySelector('script[src*="' + cleanSrc + '"]')) {
      return;
    }
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = src;
    script.async = false; // Execute in order
    head.appendChild(script);
  }

  loadScript('/runtime/frontend-config.js?v=20260325a');
  loadScript('https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.all.min.js');
  loadScript('/public/js/core/AiaAlertInterceptor.js?v=20260324a');
  loadScript('/public/js/core/SessionTimeoutManager.js?v=20260328a');

  loadScript('/js/tablet-viewport-scale.js?v=1.2');
  loadScript('/js/datatable-height-manager.js?v=1.1');
  loadScript('/js/mobile-table-fix.js?v=mobileFix2');
  loadScript('/js/global-table-align.js?v=1.0');
  
  // Script de Capacidades RBAC Moderno
  loadScript('/js/rbac_capabilities.js?v=1.0');
})();
