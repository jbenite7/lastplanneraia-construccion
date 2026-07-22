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

  // Respetar el <title> definido por la vista; solo aportar el genérico si falta.
  var titleTag = document.getElementsByTagName('title')[0];
  if (!titleTag) {
    titleTag = document.createElement('title');
    titleTag.textContent = 'Last Planner AIA';
    head.appendChild(titleTag);
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
  loadScript('/public/js/core/AiaAlertInterceptor.js?v=20260722a');
  loadScript('/public/js/core/SessionTimeoutManager.js?v=20260328a');
  loadScript('/public/js/modules/aia_ui/theme.js?v=20260711foundation5');
  loadScript('/public/js/modules/aia_ui/nav_drawer.js?v=20260711foundation5');
  loadScript('/public/js/modules/aia_ui/components.js?v=20260713sprint00');

  loadScript('/js/tablet-viewport-scale.js?v=1.2');
  if (!window.__AIA_HANDSONTABLE_ONLY__) {
    loadScript('/js/datatable-height-manager.js?v=1.2');
    loadScript('/js/global-table-align.js?v=1.0');
    loadScript('/js/mobile-table-fix.js?v=mobileFix3');
  }
  
  // Script de Capacidades RBAC Moderno
  loadScript('/js/rbac_capabilities.js?v=1.0');
})();
