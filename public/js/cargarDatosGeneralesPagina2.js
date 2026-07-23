// Inyectar CSS de Navegación Unificada.
// Las vistas migradas al shell sidebar (window.__AIA_SHELL_SIDEBAR__) no montan
// el navbar superior: conservan inputs ocultos, AJAX de datos y permisos de
// vista, pero omiten navbar.css y el markup de navegación.
if (!window.__AIA_SHELL_SIDEBAR__) {
  var cssLink = document.createElement('link');
  cssLink.href = '/public/css/navbar.css?v=' + new Date().getTime();
  cssLink.rel = 'stylesheet';
  cssLink.type = 'text/css';
  document.head.appendChild(cssLink);
}

// Inyectar FontAwesome si no existe (para iconos extra)
if (!document.querySelector('link[href*="font-awesome"]')) {
  var faLink = document.createElement('link');
  faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
  faLink.rel = 'stylesheet';
  document.head.appendChild(faLink);
}

var inputosOcultos =
  "<input type='hidden' name='Fecha_Fin_Sem' id='Fecha_Fin_Sem' value=''><input type='hidden' name='Fecha_Fin_SemYMD' id='Fecha_Fin_SemYMD' value=''><input type='hidden' name='Fecha_Inicio_Sem' id='Fecha_Inicio_Sem' value=''><input type='hidden' name='Fecha_Inicio_SemYMD' id='Fecha_Inicio_SemYMD' value=''><input type='hidden' name='Fecha_datepicker' id='Fecha_datepicker' value=''><input type='hidden' name='Max_Semana' id='Max_Semana' value=''><input type='hidden' name='baseDatos' id='baseDatos' value=''><input type='hidden' name='permiso_canonico' id='permiso_canonico' value=''><input type='hidden' name='proyecto' id='proyecto' value=''><input type='hidden' name='semana' id='semana' value=''><input type='hidden' name='pdcActivo' id='pdcActivo' value=''><input type='hidden' name='tituloSuperior' id='tituloSuperior' value=''><input type='hidden' name='Semanal_Confirmada' id='Semanal_Confirmada' value=''><input type='hidden' name='fechaCierreCompromisos' id='fechaCierreCompromisos' value=''><input type='hidden' name='fechaCreacionSemana' id='fechaCreacionSemana' value=''><input type='hidden' name='versionCronograma' id='versionCronograma' value=''>";

function applyProjectTypeVisibility(datosGenerales) {
  var area = datosGenerales.area || datosGenerales.Area || window.__PROJECT_AREA__ || 'Construccion';
  window.__PROJECT_AREA__ = area;

  if (area !== 'Pre-Construccion') return;

  ['tituloActividadesProyecto', 'info_listadoActividades', 'info_contratos', 'planCompras'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    var container = el.closest('li') || el;
    container.style.display = 'none';
  });

  var interesados = document.getElementById('tituloInteresados');
  if (interesados) interesados.textContent = 'Interesados Externos';

  var subcontratistas = document.getElementById('info_subcontratistas');
  if (subcontratistas) subcontratistas.textContent = 'Interesados Externos';
}

// Drawer Overlay HTML
var drawerOverlay = '<div class="drawer-overlay" id="drawerOverlay"></div>';

// --- Restoring Dynamic Dropdown Variables (Adapted for New Navbar) ---

// 1. Información General
var navInformacionGeneral =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='informacionGeneral' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-info-circle nav-icon'></i> <span class='nav-text-full'>Información General</span><span class='nav-text-compact'>I. General</span></a><ul class='dropdown-menu' id='informacionGeneralMenu' aria-labelledby='informacionGeneral'>";

navInformacionGeneral +=
  "<li id='tituloInteresados' class='dropdown-header'><b>Interesados</b></li><li><a class='dropdown-item' id='info_profesionales' href='#'>Profesionales AIA</a></li><li><a class='dropdown-item' id='info_subcontratistas' href='#'>Sub-Contratistas</a></li>";

navInformacionGeneral +=
  "<li id='tituloActividadesProyecto' class='dropdown-header mt-2'><b>Familias y contratacion</b></li><li><a class='dropdown-item' id='info_listadoActividades' href='#'>Familias de obra</a></li><li><a class='dropdown-item' id='info_contratos' href='#'>Paquetes de contratacion</a></li><li><a class='dropdown-item' id='planCompras' href='#'>Plan de Compras y Contrataciones</a></li>";

navInformacionGeneral +=
  " <li id='tituloIndicadores' class='dropdown-header mt-2'><b>Indicadores</b></li><li><a class='dropdown-item' id='informe_lps' href='#'>Indicadores de Last Planner</a></li><!-- <li><a class='dropdown-item' id='informe_productividad' href='#'>Indicadores de Tasas de Producción</a></li> -->";

navInformacionGeneral +=
  "<li id='tituloActualizarCronograma' class='dropdown-header mt-2'><b>Cronograma</b></li><li><a class='dropdown-item' id='actualizarCronograma' href='#'>Actualizar Cronograma</a></li>";

navInformacionGeneral +=
  "<li id='biControlTowerNavItem' class='d-none'><a class='dropdown-item' href='/bi/control-tower' data-bi-access-link='control-tower'><i class='fas fa-chart-line mr-2'></i>Control Tower</a></li></ul></li>";

// 2. Integración
var navIntegracion =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='integracion' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-network-wired nav-icon'></i> <span class='nav-text-full'>Integración</span><span class='nav-text-compact'>Integración</span></a><ul class='dropdown-menu' id='integracionMenu' aria-labelledby='integracion'><li><a class='dropdown-item' id='controlCambios' href='#'>Control de Cambios</a></li></ul></li>";

// 3. Semanas del Proyecto
var navSemanasProyecto =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='semanasProyecto' role='button' data-toggle='dropdown' aria-expanded='false'><i class='far fa-calendar-alt nav-icon'></i> <span class='nav-text-full'>Semanas del Proyecto</span><span class='nav-text-compact'>Semanas</span></a><ul class='dropdown-menu' id='semanasProyectoMenu' aria-labelledby='semanasProyecto'></ul></li>";

// 4. Programa General
var navProgramaGeneral =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' id='programa_general' href='#' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-project-diagram nav-icon'></i> <span class='nav-text-full'>Programa General</span><span class='nav-text-compact'>P. General</span></a><ul class='dropdown-menu' id='programa_generalMenu' aria-labelledby='programa_general'></ul></li>";

// 5. Programación Intermedia
var navProgramacionIntermedia =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' id='programacion_intermedia' href='#' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-clipboard-list nav-icon'></i> <span class='nav-text-full'>Liberación de Restricciones</span><span class='nav-text-compact'>Restricciones</span></a><ul class='dropdown-menu' id='programacion_intermediaMenu' aria-labelledby='programacion_intermedia'></ul></li>";

// 6. Programación Semanal
var navProgramacionSemanal =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='programacion_semanal' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-tasks nav-icon'></i> <span class='nav-text-full'>Programación Semanal</span><span class='nav-text-compact'>P. Semanal</span></a><ul class='dropdown-menu' id='programacion_semanalMenu' aria-labelledby='programacion_semanal'></ul></li>";

// Inyectar Script ContextManager
if (!document.querySelector('script[src*="ContextManager.js"]')) {
  var scriptCtx = document.createElement('script');
  scriptCtx.src = '/public/js/core/ContextManager.js?v=' + new Date().getTime();
  document.head.appendChild(scriptCtx);
}

if (!document.querySelector('script[src*="bi-access.js"]')) {
  var scriptBiAccess = document.createElement('script');
  scriptBiAccess.src = '/js/modules/bi-access.js?v=' + new Date().getTime();
  document.head.appendChild(scriptBiAccess);
}



// --- Unified Navbar Container Construction ---
var navbarComponentStart = `
<nav class="navbar navbar-expand-xl navbar-dark navbar-aia fixed-top">
    <!-- Drawer Overlay -->
    <div class="drawer-overlay" id="drawerOverlay"></div>

    <div class="container-fluid">
        <!-- Brand / Logo -->
        <a class="navbar-brand aia-brand" href="/proyectos" aria-label="Last Planner AIA">
            <span class="aia-brand-mark" aria-hidden="true"></span>
            <span class="aia-brand-name">Last Planner AIA</span>
        </a>

        <!-- Mobile Toggler -->
                <button class="navbar-toggler" type="button" id="drawerToggle" aria-label="Abrir menú" aria-controls="aiaNavbar" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content (Desktop: Row, Mobile: Drawer) -->
                <div class="collapse navbar-collapse navbar-collapse-drawer" id="aiaNavbar" role="dialog" aria-modal="true" aria-labelledby="drawerTitle">

            <!-- Mobile Drawer Header -->
            <div class="drawer-header d-xl-none">
                        <h5 class="m-0" id="drawerTitle">Menú</h5>
                        <button type="button" class="close-drawer" id="drawerClose" aria-label="Cerrar menú">&times;</button>
            </div>

            <!-- Main Navigation Links (Center/Left) -->
            <ul class="navbar-nav mr-auto ml-lg-2 main-links">
`;

var navbarComponentEnd = `
            </ul>

            <!-- User Profile & Actions (Right - Desktop Only) -->
            <ul class="navbar-nav ml-auto align-items-center d-none d-xl-flex">

                <!-- Notificaciones (Campana) Desktop -->
                <li class="nav-item dropdown mr-2">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="position: relative;">
                        <i class="fas fa-bell"></i>
                        <span class="badge badge-danger badge-pill" id="notificationBadge" style="position: absolute; top: 0px; right: 0; display: none; font-size: 0.6rem;">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow-sm border-0" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto; padding: 0;">
                        <h6 class="dropdown-header bg-light border-bottom py-2 font-weight-bold">Centro de Notificaciones</h6>
                        <div id="notificationList">
                            <a class="dropdown-item text-muted text-center py-3" href="#"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando...</a>
                        </div>
                    </div>
                </li>

                 <li class="nav-item dropdown">
                    <!-- Desktop Trigger -->
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar-sm">
                            <i class="fas fa-user"></i>
                        </div>
                        <span id="labelNombreUsuario">Usuario</span>
                    </a>

                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="/proyectos">
                            <i class="fas fa-exchange-alt mr-2 text-muted"></i> Cambiar Proyecto
                        </a>
                        <div class="dropdown-divider"></div>
                        <button type="button" class="dropdown-item aia-theme-switch" aria-pressed="false">
                            <i class="fas fa-moon mr-2 text-muted" aria-hidden="true"></i>
                            <span class="aia-theme-switch-text">Modo oscuro</span>
                        </button>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="/logout">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </li>
            </ul>

            <!-- Mobile User Island (Thumb Zone - Mobile Only) -->
            <div class="user-island d-xl-none">
                <div class="user-island-header">
                    <div class="user-avatar-lg">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name" id="labelNombreUsuarioMobile">Usuario</div>
                        <div class="user-role" id="labelRolUsuarioMobile">Usuario</div>
                    </div>
                </div>
                <div class="user-island-actions">
                    <a href="/proyectos" class="island-btn">
                        <i class="fas fa-exchange-alt"></i> Proyecto
                    </a>

                    <button type="button" class="island-btn aia-theme-switch" aria-pressed="false">
                        <i class="fas fa-moon" aria-hidden="true"></i>
                        <span class="aia-theme-switch-text">Modo oscuro</span>
                    </button>

                    <!-- Notificaciones Dropdown Mobile -->
                    <div class="dropdown" style="display: flex;">
                        <a href="#" class="island-btn w-100 island-notification-btn" id="notificationDropdownMobile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell"></i> Avisos
                            <span class="badge badge-danger badge-pill" id="notificationBadgeMobile" style="display: none;">0</span>
                        </a>
                        <div class="dropdown-menu shadow-sm border-0" aria-labelledby="notificationDropdownMobile" style="width: 260px; max-height: 300px; overflow-y: auto; padding: 0; bottom: 100%; top: auto !important; margin-bottom: 5px; left: 0;">
                            <h6 class="dropdown-header bg-light border-bottom py-2 font-weight-bold">Notificaciones</h6>
                            <div id="notificationListMobile">
                                <a class="dropdown-item text-muted text-center py-3" href="#"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando...</a>
                            </div>
                        </div>
                    </div>

                    <a href="/logout" class="island-btn btn-danger-soft" style="grid-column: span 2;">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="shell-nav-spacer" aria-hidden="true"></div>
<!-- Context Bar (New) -->
<div class="context-bar">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <div class="context-breadcrumb">
             <i class="fas fa-building text-muted mr-1"></i>
             <span class="font-weight-bold" id="ctxProyecto">Proyecto...</span>
             <span class="text-muted mx-2">/</span>
             <span id="ctxModulo" class="text-primary">Módulo...</span>
        </div>
        <div class="context-week-info">
             <span class="badge badge-info p-2" id="ctxSemanaBadge">
                <i class="far fa-calendar-alt mr-1"></i> <span id="ctxSemanaTexto">Semana...</span>
             </span>
        </div>
    </div>
</div>

`;

var finalNavbarHTML =
  navbarComponentStart +
  navInformacionGeneral +
  navIntegracion +
  navSemanasProyecto +
  navProgramaGeneral +
  navProgramacionIntermedia +
  navProgramacionSemanal +
  navbarComponentEnd;

document.getElementById('encabezado').innerHTML =
  document.getElementById('encabezado').innerHTML +
  inputosOcultos +
  (window.__AIA_SHELL_SIDEBAR__ ? '' : finalNavbarHTML);

// --- Pre-Construction Area: Hide construction-only modules ---
if (window.__PROJECT_AREA__ === 'Pre-Construccion') {
  // Helper: hide a nav-item (the <li> wrapper) by child element id
  function _hideNavItem(childId) {
    var el = document.getElementById(childId);
    if (el) {
      var li = el.closest('li');
      if (li) li.style.display = 'none';
    }
  }
  // Listado de Actividades
  _hideNavItem('info_listadoActividades');
  // Contratos
  _hideNavItem('info_contratos');
  // Plan de Compras (PDC)
  _hideNavItem('planCompras');
  // Programación Semanal (contains CIC / Calificación Integral de Proveedores)
  //_hideNavItem('programacion_semanal');
  // Hide "Actividades del Proyecto" section header if both children hidden
  var _titAct = document.getElementById('tituloActividadesProyecto');
  if (_titAct) _titAct.style.display = 'none';
}

// Inyectar Script Notifications (DESPUÉS del innerHTML para que existan los elementos)
if (!document.querySelector('script[src*="notifications.js"]')) {
  var scriptNotif = document.createElement('script');
  scriptNotif.src = '/public/js/components/notifications.js?v=legacy4';
  document.head.appendChild(scriptNotif);
}

// --- Initialize Drawer Handling ---
function bindThemeSwitchesWhenReady() {
  if (window.AiaDesignSystem && typeof window.AiaDesignSystem.bindThemeSwitches === 'function') {
    window.AiaDesignSystem.bindThemeSwitches(document);
    return;
  }
  document.addEventListener('aia-theme-ready', function () {
    window.AiaDesignSystem.bindThemeSwitches(document);
  }, { once: true });
}

$(document).ready(function () {
  if (window.AiaNavDrawer) window.AiaNavDrawer.init();

  bindThemeSwitchesWhenReady();

  // Existing initialization...
});

var cargarDatosGeneralesPagina = function (seccion) {
  $.ajax({
    method: 'POST',
    url: '/legacy/funciones_generales/php/datosGeneralesPagina.php',
    contenttype: 'charset=utf-8',
    data: {
      seccion: seccion,
    },
    success: function (info) {
      if (typeof info === 'string') {
        json_info_global = JSON.parse(info);
      } else {
        json_info_global = info;
      }
      //console.log(json_info_global["data"]);
      var responseData = json_info_global && json_info_global['data'];
      if (!responseData || typeof responseData !== 'object') {
        return;
      }
      datosGenerales = responseData;
      window.__lpsWeekCsrf = responseData.weekCsrfToken || '';
      listadoSemanas = Array.isArray(responseData.listadoSemanas)
        ? responseData.listadoSemanas
        : [];
      applyProjectTypeVisibility(datosGenerales);
      if (window.BiAccess) {
        window.BiAccess.syncAccessLinks();
      }
      var biNavItem = document.getElementById('biControlTowerNavItem');
      if (biNavItem && datosGenerales.canAccessBi === true) {
        var biLink = biNavItem.querySelector('[data-bi-access-link]');
        if (biLink) biLink.href = window.BiAccess ? window.BiAccess.buildUrl(biLink) : biLink.href;
        biNavItem.classList.remove('d-none');
      }

      // Update User Name + Role (stacked)
      if (document.getElementById('labelNombreUsuario')) {
        const el = document.getElementById('labelNombreUsuario');
        const rol = datosGenerales.rolUsuario || '';
        // Abreviatura Inteligente (Ej: "Juan Felipe Benitez Ramos" -> "Juan F. Benitez R.")
        var rawName = (datosGenerales.nombreUsuario || '').trim();
        var parts = rawName.split(' ');
        var displayName = rawName;
        if (parts.length >= 3) {
          displayName = parts[0] + ' ' + parts[1].charAt(0) + '. ' + parts[2] + (parts[3] ? ' ' + parts[3].charAt(0) + '.' : '');
        }
        el.innerHTML = displayName +
          (rol ? '<br><small style="opacity:.65;font-size:.75em">' + rol + '</small>' : '');
      }

      document.getElementById('Fecha_Fin_Sem').value = datosGenerales.Fecha_Fin_Sem;
      document.getElementById('Fecha_Fin_SemYMD').value = datosGenerales.Fecha_Fin_SemYMD;
      document.getElementById('Fecha_Inicio_Sem').value = datosGenerales.Fecha_Inicio_Sem;
      document.getElementById('Fecha_Inicio_SemYMD').value = datosGenerales.Fecha_Inicio_SemYMD;
      document.getElementById('Fecha_datepicker').value = datosGenerales.Fecha_datepicker;
      document.getElementById('Max_Semana').value = datosGenerales.Max_Semana;
      document.getElementById('baseDatos').value = datosGenerales.db;
      document.getElementById('permiso_canonico').value = datosGenerales.permiso_canonico || datosGenerales.permiso || '';
      document.getElementById('permiso_canonico').value = datosGenerales.permiso_canonico || datosGenerales.permiso || '';
      document.getElementById('pdcActivo').value = datosGenerales.pdcActivo;
      document.getElementById('proyecto').value = datosGenerales.proyecto;
      document.getElementById('semana').value = datosGenerales.semana;

      try {
        if (typeof cargaParametros === 'function') {
          cargaParametros();
        } else {
          console.error("🕵️ [DeepAnalysis] CRÍTICO: cargaParametros NO es una función o es undefined en este contexto global. Tipo actual:", typeof cargaParametros);
        }
      } catch (error) {
        console.error("🕵️ [DeepAnalysis] Excepción ahogada (swallowed) capturada al ejecutar cargaParametros():", error);
      }

      document.getElementById('Semanal_Confirmada').value = datosGenerales.Semanal_Confirmada;
      if (datosGenerales.fechaCierreCompromisos == null) {
        document.getElementById('fechaCierreCompromisos').value = '';
      } else {
        document.getElementById('fechaCierreCompromisos').value =
          datosGenerales.fechaCierreCompromisos;
      }

      if (datosGenerales.fechaCreacionSemana == null) {
        document.getElementById('fechaCreacionSemana').value = '';
      } else {
        document.getElementById('fechaCreacionSemana').value = datosGenerales.fechaCreacionSemana;
      }

      if (datosGenerales.versionCronograma == null) {
        document.getElementById('versionCronograma').value = '';
      } else {
        document.getElementById('versionCronograma').value = datosGenerales.versionCronograma;
      }

      // --- Context Bar Population ---
      // En vistas con shell sidebar la context-bar la renderiza el servidor
      // con etiquetas canónicas; el callback no debe pisarla.
      if (!window.__AIA_SHELL_SIDEBAR__ && document.getElementById('ctxProyecto')) {
        document.getElementById('ctxProyecto').textContent = datosGenerales.proyecto || 'Proyecto';
      }
      if (!window.__AIA_SHELL_SIDEBAR__ && document.getElementById('ctxModulo')) {
        var mapTitulos = {
          programa_general: 'Programa General',
          programacion_semanal: 'Programación Semanal',
          controlCambios: 'Control de Cambios',
          info_profesionales: 'Profesionales',
          info_subcontratistas: 'Subcontratistas',
          info_listadoActividades: 'Familias de obra',
          info_contratos: 'Paquetes de contratacion',
          planCompras: 'Plan de Compras y Contrataciones',
          actualizarCronograma: 'Actualizar Cronograma',
          indicadores: 'Indicadores',
          programacion_intermedia: 'Liberación de Restricciones',
        };
        document.getElementById('ctxModulo').textContent =
          mapTitulos[seccion] || seccion.replace(/_/g, ' ');
      }
      if (!window.__AIA_SHELL_SIDEBAR__ && document.getElementById('ctxSemanaTexto')) {
        if (datosGenerales.semana > 0) {
          document.getElementById('ctxSemanaTexto').textContent = 'Semana ' + datosGenerales.semana;
          // Add date details if available?
          // var semInfo = listadoSemanas.find(s => s.Semana == datosGenerales.semana);
          // if(semInfo) ...
        } else {
          document.getElementById('ctxSemanaBadge').style.display = 'none';
        }
      }

      // Update Title - Note: Title might be elsewhere in Legacy pages, we keep this for compatibility if it targets an element
      if (document.getElementById('tituloSuperior')) {
        document.getElementById('tituloSuperior').innerHTML =
          "<h1 class='titulo'>Last Planner AIA - " + datosGenerales.proyecto + '</h1>';
      }

      // Update Links based on permissions/PDC
      // Update Links based on permissions/PDC
      if (datosGenerales.pdcActivo == 1) {
        if (document.getElementById('info_profesionales'))
          $('#nav_info_general').attr('href', '/profesionales');
        if (document.getElementById('controlCambios'))
          $('#nav_integracion').attr('href', '/control-cambios');
      }

      // --- RESTORED LINK LOGIC (Fixing Broken Dropdowns) ---
      // This ensures that the dropdown items actually go somewhere
      if (document.getElementById('info_profesionales')) {
        document.getElementById('info_profesionales').href = '#';
        document.getElementById('info_profesionales').onclick = function () {
          window.Context.clearWeek('/profesionales');
          return false;
        };
      }
      if (document.getElementById('info_subcontratistas')) {
        document.getElementById('info_subcontratistas').href = '#';
        document.getElementById('info_subcontratistas').onclick = function () {
          window.Context.clearWeek('/subcontratistas');
          return false;
        };
      }

      if (document.getElementById('info_listadoActividades')) {
        document.getElementById('info_listadoActividades').href = '#';
        document.getElementById('info_listadoActividades').onclick = function () {
          window.Context.clearWeek('/listado-actividades');
          return false;
        };
      }
      if (document.getElementById('info_contratos')) {
        document.getElementById('info_contratos').href = '#';
        document.getElementById('info_contratos').onclick = function () {
          window.Context.clearWeek('/contratos');
          return false;
        };
      }

      if (document.getElementById('planCompras')) {
        document.getElementById('planCompras').href = '#';
        document.getElementById('planCompras').onclick = function () {
          window.location.href =
            '/legacy/cambiar_pagina.php?seccion=planCompras&semana=' +
            datosGenerales.Max_Semana +
            '&origen=' +
            encodeURIComponent(seccion || '');
          return false;
        };
      }

      if (document.getElementById('informe_lps')) {
        document.getElementById('informe_lps').href = '#';
        document.getElementById('informe_lps').onclick = function () {
          window.Context.clearWeek('/indicadores');
          return false;
        };
      }
      if (document.getElementById('actualizarCronograma')) {
        document.getElementById('actualizarCronograma').href = '/programa-general-actualizar';
        document.getElementById('actualizarCronograma').onclick = null; // No clearing required
      }

      if (document.getElementById('controlCambios')) {
        document.getElementById('controlCambios').href = '#';
        document.getElementById('controlCambios').onclick = function () {
          window.Context.clearWeek('/control-cambios');
          return false;
        };
      }

      // --- RESTORED LOGIC: Populate Dropdowns with Weeks (SESSION BASED) ---
      var htmlSemanasProyecto = '';
      // Button for new week
      htmlSemanasProyecto +=
        "<li><a class='dropdown-item' style='padding: 16px 0px 8px 16px'><button type='button' class='nueva_sem btn btn-primary btn-sm' title='Crear nueva semana de la programación semanal' onclick=\"nueva_sem('" +
        datosGenerales.db +
        "', 2, '" +
        seccion +
        "'); fechaNuevaSemana()\" data-toggle='modal' data-target='#modal_nueva_sem'><i class='fa fa-plus fa-m'></i> Nueva Semana</button></a></li>";

      var htmlProgramaGeneral = '';
      var htmlProgramacionIntermedia = '';
      var htmlProgramacionSemanal = '';

      if (datosGenerales.Max_Semana > 0) {
        for (var semanaContador = datosGenerales.Max_Semana; semanaContador > 0; semanaContador--) {
          var semObj = listadoSemanas[semanaContador - 1];
          if (!semObj) continue;

          var valorSemana = semObj.Semana || 0;
          var ini = semObj.Fecha_Inicio_Sem || '0000-00-00';
          var fin = semObj.Fecha_Fin_Sem || '0000-00-00';

          // Session-based navigation helper
          // We use 'href="#"' and onclick to update session then redirect
          var onclickEvent = 'cambiarSemanaSesion(' + valorSemana + ", '/programa-general')";

          // Note: For other modules not fully refactored, we might still want to use the legacy logic OR redirect to their index (which reads session)
          // Since the user insisted on "No variables en URL", we'll redirect to their clean roots:
          // /programacion-semanal, /programacion-intermedia

          var onclickIntermedia =
            'cambiarSemanaSesion(' + valorSemana + ", '/programacion-intermedia')";
          var onclickSemanal = 'cambiarSemanaSesion(' + valorSemana + ", '/programacion-semanal')";

          if (datosGenerales.Max_Semana - 2 >= semanaContador) {
            // Main Semanas Dropdown - stays on current page mostly, or could default to Programa General
            // The logic below uses the *current* section's clean URL if possible, or falls back to 'cambiar_pagina.php' but we want to avoid GET params.
            // Let's assume for Semanas Dropdown we reload the CURRENT page with new session.
            // We can pass 'window.location.pathname' as destination.
            var onclickCurrent =
              'cambiarSemanaSesion(' + valorSemana + ', window.location.pathname)';

            htmlSemanasProyecto +=
              "<li><a class='dropdown-item' id='semanaProyecto" +
              valorSemana +
              "' href='#' onclick=\"" +
              onclickCurrent +
              '">Semana ' +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';
          } else {
            var onclickCurrent =
              'cambiarSemanaSesion(' + valorSemana + ', window.location.pathname)';

            htmlSemanasProyecto +=
              "<li style='display:flex; margin-right:5px'><a class='dropdown-item' id='semanaProyecto" +
              valorSemana +
              "' href='#' onclick=\"" +
              onclickCurrent +
              '" >Semana ' +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ")</a><button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana " +
              valorSemana +
              '\' onclick="eliminar_sem(' +
              semanaContador +
              ",'" +
              datosGenerales.db +
              "', 1, '" +
              seccion +
              "')\" style='margin:0 5px' data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button></li>";
          }

          // Links for other specific modules (Clean URLs via Session Switch)
          htmlProgramaGeneral +=
            "<li><a class='dropdown-item' id='programa_generalMenu" +
            valorSemana +
            "' href='#' onclick=\"" +
            onclickEvent +
            '">Semana ' +
            valorSemana +
            ' (del ' +
            ini +
            ' al ' +
            fin +
            ')</a></li>';

          htmlProgramacionIntermedia +=
            "<li><a class='dropdown-item' id='programacion_intermediaMenu" +
            valorSemana +
            "' href='#' onclick=\"" +
            onclickIntermedia +
            '">Semana ' +
            valorSemana +
            ' (del ' +
            ini +
            ' al ' +
            fin +
            ')</a></li>';

          htmlProgramacionSemanal +=
            "<li><a class='dropdown-item' id='programacion_semanalMenu" +
            valorSemana +
            "' href='#' onclick=\"" +
            onclickSemanal +
            '">Semana ' +
            valorSemana +
            ' (del ' +
            ini +
            ' al ' +
            fin +
            ')</a></li>';
        }
      }

      // Inject into DOM
      if (document.getElementById('semanasProyectoMenu'))
        document.getElementById('semanasProyectoMenu').innerHTML = htmlSemanasProyecto;
      if (document.getElementById('programa_generalMenu'))
        document.getElementById('programa_generalMenu').innerHTML = htmlProgramaGeneral;
      if (document.getElementById('programacion_intermediaMenu'))
        document.getElementById('programacion_intermediaMenu').innerHTML =
          htmlProgramacionIntermedia;
      if (document.getElementById('programacion_semanalMenu'))
        document.getElementById('programacion_semanalMenu').innerHTML = htmlProgramacionSemanal;

      // Logic to highlight active section
      // Logic to highlight active section in Navbar
      var mapSeccion = {
        programa_general: 'programa_general',
        programacion_semanal: 'programacion_semanal',
        programacion_intermedia: 'programacion_intermedia',
        CNP: 'programacion_semanal',
        CNC: 'programacion_semanal',
        CIC: 'programacion_semanal',
        controlCambios: 'integracion',
        info_profesionales: 'informacionGeneral',
        info_subcontratistas: 'informacionGeneral',
        info_listadoActividades: 'informacionGeneral',
        info_contratos: 'informacionGeneral',
        planCompras: 'informacionGeneral',
        actualizarCronograma: 'informacionGeneral',
        indicadores: 'informacionGeneral',
      };

      var activeParentId = mapSeccion[seccion];
      if (activeParentId) {
        // Highlight Parent Dropdown
        $('#' + activeParentId).addClass('active');

        // Highlight Specific Child Item (Submenu)
        // Default: The child ID matches the 'seccion' name
        var childId = seccion;

        // Exceptions mappings (Seccion -> HTML ID)
        var mapChildIds = {
          indicadores: 'informe_lps',
        };

        if (mapChildIds[seccion]) {
          childId = mapChildIds[seccion];
        }

        // Apply active class to the specific item
        var childElem = document.getElementById(childId);
        if (childElem) {
          childElem.classList.add('active');
        }
      }

      // Highlight specific week item if selected
      if (datosGenerales.semana > 0) {
        var semProyElem = document.getElementById('semanaProyecto' + datosGenerales.semana);
        if (semProyElem) semProyElem.classList.add('active');

        var dropdownPrefix = (activeParentId && document.getElementById(activeParentId + 'Menu' + datosGenerales.semana)) ? activeParentId : seccion;
        var menuElem = document.getElementById(dropdownPrefix + 'Menu' + datosGenerales.semana);
        if (menuElem) menuElem.classList.add('active');
      }

    },
    error: function(xhr, status, error) {
      console.error("🕵️ [DeepAnalysis] Error AJAX en cargarDatosGeneralesPagina2.js:", status, error);
    }
  });
};

// Helper function to switch session week and redirect
window.cambiarSemanaSesion = function (semana, redirectUrl) {
  if (!semana) return;

  // Proper Implementation
  fetch('/context/week', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ semana: semana }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.location.href = redirectUrl;
      } else {
        if (window.AIA && window.AIA.Notice && typeof window.AIA.Notice.error === 'function') {
          AIA.Notice.error('Error al cambiar de semana: ' + (data.message || 'Desconocido'));
        } else if (typeof window.alert === 'function') {
          window.alert('Error al cambiar de semana: ' + (data.message || 'Desconocido'));
        }
      }
    })
    .catch((error) => {
      console.error('Error:', error);
      // Fallback legacy behavior if fetch fails? verify network
    });
};

var syncCicDisciplineInputs = function () {
  ['cal', 'adm', 'gsa', 'sst'].forEach(function (discipline) {
    ['si', 'mdo'].forEach(function (prefix) {
      var section = document.getElementById(prefix + '_' + discipline);
      if (!section) return;
      var blocked = window.getComputedStyle(section).display === 'none';
      $(section).find(':input').prop('disabled', blocked);
    });
  });
};

var maestroPermisos = function (permiso) {
  switch (permiso) {
    case 'R':
      //Bloqueos Integración

      //Bloqueos Semanas del Proyecto

      //Bloqueos Programa General

      //Bloqueos Liberación de Restricciones

      //Bloqueos Programación Semanal

      //Bloqueos Calificación de Proveedores
      $('#si_adm, #si_gsa, #si_sst, #mdo_adm, #mdo_gsa, #mdo_sst').css('display', 'none');
      break;

    case 'G':
      //Bloqueos Información General
      $(
        '#tituloInteresados, #info_profesionales, #btn_nuevo, #info_subcontratistas, #tituloActividadesProyecto, #info_listadoActividades, #btn_cargarActividadesExcel, #btn_nueva_actividad, #info_contratos, #btn_guardar_contratos, #planCompras, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración
      $('#integracion').css('display', 'none');

      //Bloqueos Semanas del Proyecto
      $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#programa_general, #actualizarEjecucion, #descargarCorteProgramacion').css(
        'display',
        'none'
      );

      //Bloqueos Liberación de Restricciones
      $('#programacion_intermedia').css('display', 'none');

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana, #btn_informe_compromisos '
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#si_cal, #si_adm, #si_sst, #mdo_cal, #mdo_adm, #mdo_sst, #btn-shared-constraint').css('display', 'none');
      break;

    case 'S':
      //Bloqueos Información General
      $(
        '#tituloInteresados, #info_profesionales, #btn_nuevo, #info_subcontratistas, #tituloActividadesProyecto, #info_listadoActividades, #btn_cargarActividadesExcel, #btn_nueva_actividad, #info_contratos, #btn_guardar_contratos, #planCompras, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración
      $('#integracion').css('display', 'none');

      //Bloqueos Semanas del Proyecto
      $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#programa_general, #actualizarEjecucion, #descargarCorteProgramacion').css(
        'display',
        'none'
      );

      //Bloqueos Liberación de Restricciones
      $('#programacion_intermedia').css('display', 'none');

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana, #btn_informe_compromisos '
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#si_cal, #si_adm, #si_gsa, #mdo_cal, #mdo_adm, #mdo_gsa, #btn-shared-constraint').css('display', 'none');
      break;

    case 'SG':
      //Bloqueos Información General
      $(
        '#tituloInteresados, #info_profesionales, #btn_nuevo, #info_subcontratistas, #tituloActividadesProyecto, #info_listadoActividades, #btn_cargarActividadesExcel, #btn_nueva_actividad, #info_contratos, #btn_guardar_contratos, #planCompras, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración
      $('#integracion').css('display', 'none');

      //Bloqueos Semanas del Proyecto
      $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#programa_general, #actualizarEjecucion, #descargarCorteProgramacion').css(
        'display',
        'none'
      );

      //Bloqueos Liberación de Restricciones
      $('#programacion_intermedia').css('display', 'none');

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana, #btn_informe_compromisos '
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#si_cal, #si_adm, #mdo_cal, #mdo_adm, #btn-shared-constraint').css('display', 'none');
      break;

    case 'OT':
      //Bloqueos Información General
      $(
        '#tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración

      //Bloqueos Programa General
      $('#actualizarEjecucion').css('display', 'none');

      //Bloqueos Liberación de Restricciones

      //Bloqueos Programación Semanal
      $('#btn_autoprogramar, #btn_agregar_actividad, #btn_cerrar_compromisos_semana').css(
        'display',
        'none'
      );

      //Bloqueos Calificación de Proveedores
      $('#si_cal, #si_gsa, #si_sst, #mdo_cal, #mdo_gsa, #mdo_sst').css('display', 'none');
      break;

    case 'DCV':
      //Bloqueos Información General
      $(
        '#btn_nuevo, #btn_cargarActividadesExcel, #btn_nueva_actividad, #btn_guardar_contratos, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración

      //Bloqueos Semanas del Proyecto
      $('.eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#actualizarEjecucion').css('display', 'none');

      //Bloqueos Liberación de Restricciones

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana'
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#btn_guardar_cic_si, #btn_guardar_cic_mdo').css('display', 'none');
      break;

    case 'V':
      //Bloqueos Información General
      $(
        '#btn_nuevo, #btn_cargarActividadesExcel, #btn_nueva_actividad, #btn_guardar_contratos, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_eliminarActualizacion'
      ).css('display', 'none');

      //Bloqueos Integración
      $('#integracion').css('display', 'none');

      //Bloqueos Semanas del Proyecto
      $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#actualizarEjecucion, #descargarCorteProgramacion').css('display', 'none');

      //Bloqueos Liberación de Restricciones

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana, #btn_informe_compromisos'
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#btn_guardar_cic_si, #btn_guardar_cic_mdo').css('display', 'none');
      break;

    case 'C':
      //Bloqueos Información General
      $(
        '#tituloInteresados, #info_profesionales, #btn_nuevo, #info_subcontratistas, #tituloActividadesProyecto, #info_listadoActividades, #btn_cargarActividadesExcel, #btn_nueva_actividad, #info_contratos, #btn_guardar_contratos, #planCompras, #btn_guardar_pdc, #btn_actualizarPDC, #btn_informeProgramaGeneral, #btn_informeProgramacionIntermedia, #btn_informeProgramacionSemanal, #btn_informePDC, #informe_productividad, #tituloActualizarCronograma, #btn_tutorialActualizarCronograma, #actualizarCronograma, #btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
      ).css('display', 'none');

      if (!window.__AIA_HANDSONTABLE_ONLY__) {
        // Compatibilidad temporal para módulos que aún conservan tablas legacy.
        $('body').append('<style>#dt_cliente tbody tr { cursor: default !important; } .ps-action-btn { display: none !important; }</style>');
        $(document).on('click', '#dt_cliente tbody tr', function(e) {
          if (permiso === 'C') {
            e.stopImmediatePropagation();
            return false;
          }
        });
      }

      //Bloqueos Integración
      $('#integracion').css('display', 'none');

      //Bloqueos Semanas del Proyecto
      $('.nueva_sem, .eliminar_sem, #btn_nuevo').css('display', 'none');

      //Bloqueos Programa General
      $('#programa_general, #actualizarEjecucion, #descargarCorteProgramacion').css(
        'display',
        'none'
      );

      //Bloqueos Liberación de Restricciones
      $('#programacion_intermedia').css('display', 'none');

      //Bloqueos Programación Semanal
      $(
        '#btn_autoprogramar, #btn_agregar_actividad, #btn_agregar_indicadores, #btn_cerrar_compromisos_semana, #btn_informe_compromisos, #btn_CNP'
      ).css('display', 'none');

      //Bloqueos Calificación de Proveedores
      $('#btn_guardar_cic_si, #btn_guardar_cic_mdo').css('display', 'none');
      // window.location.href = '../programacion_semanal/programacion_semanal.php';
      break;
  }
  syncCicDisciplineInputs();
};
