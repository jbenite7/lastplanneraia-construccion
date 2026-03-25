/**
 * Loader for Common Head Resources (v2)
 * Uses standard DOM manipulation to ensure scripts execute.
 */
(function () {
  var head = document.getElementById('head') || document.getElementsByTagName('head')[0];

  // HTML String for META and static CSS/JS
  // Note: We will parse this or just purely append elements for critical scripts.
  // For simplicity given the existing structure, we will use a hybrid approach:
  // 1. Set innerHTML for static tags (Meta, Title, CSS)
  // 2. Proactively load functionality scripts via createElement

  var staticContent =
    "<meta charset='UTF-8'><title>Last Planner AIA</title><link rel='shortcut icon' href='/img/florAIA.png'><meta name='viewport' content='width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0'><!-- Fuentes de Google--><link href='https://fonts.googleapis.com/css?family=Roboto&display=swap' rel='stylesheet'><!-- Font Awesome (Íconos)--><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css'><!--Iniciar estilos de Bootstrap--><link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'><!--Iniciar estilos de Datatables--><link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/1.11.4/css/jquery.dataTables.css'><link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/1.11.4/css/dataTables.bootstrap4.min.css'><link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css'><!-- Estilos Personalizados --><link rel='stylesheet' href='/css/tokens.css?v=1.0'><link rel='stylesheet' href='/css/styles.css?v=mobileFix13'><link rel='stylesheet' href='/css/access.css?v=1.0'><!-- Checkboxes DataTables --><link type='text/css' href='//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css' rel='stylesheet'><!--Selector de fechas --><link rel='stylesheet' href='https://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css'><!--Estilos Any Chart--><link href='https://cdn.anychart.com/releases/v8/css/anychart-ui.min.css?hcode=c11e6e3cfefb406e8ce8d99fa8368d33' type='text/css' rel='stylesheet'><link href='https://cdn.anychart.com/releases/v8/fonts/css/anychart-font.min.css?hcode=c11e6e3cfefb406e8ce8d99fa8368d33' type='text/css' rel='stylesheet'><!-- Lista desplegable con buscador --><link href='https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' rel='stylesheet'/><!-- SweetAlert2 --><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.css'/>";

  head.innerHTML = staticContent;

  // Helper to load script
  function loadScript(src) {
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = src;
    script.async = false; // Execute in order
    head.appendChild(script);
  }

  loadScript('/runtime/frontend-config.js?v=20260325a');
  loadScript('https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.all.min.js');
  loadScript('/public/js/core/AiaAlertInterceptor.js?v=20260324a');

  loadScript('/js/tablet-viewport-scale.js?v=1.2');
  loadScript('/js/datatable-height-manager.js?v=1.1');
  loadScript('/js/mobile-table-fix.js?v=mobileFix2');
  loadScript('/js/global-table-align.js?v=1.0');
  
  // Script de Capacidades RBAC Moderno
  loadScript('/js/rbac_capabilities.js?v=1.0');
})();
