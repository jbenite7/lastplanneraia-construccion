var inputosOcultos =
  "<input type='hidden' name='Fecha_Fin_Sem' id='Fecha_Fin_Sem' value=''><input type='hidden' name='Fecha_Fin_SemYMD' id='Fecha_Fin_SemYMD' value=''><input type='hidden' name='Fecha_Inicio_Sem' id='Fecha_Inicio_Sem' value=''><input type='hidden' name='Fecha_Inicio_SemYMD' id='Fecha_Inicio_SemYMD' value=''><input type='hidden' name='Fecha_datepicker' id='Fecha_datepicker' value=''><input type='hidden' name='Max_Semana' id='Max_Semana' value=''><input type='hidden' name='baseDatos' id='baseDatos' value=''><input type='hidden' name='permiso_canonico' id='permiso_canonico' value=''><input type='hidden' name='proyecto' id='proyecto' value=''><input type='hidden' name='semana' id='semana' value=''><input type='hidden' name='pdcActivo' id='pdcActivo' value=''><input type='hidden' name='tituloSuperior' id='tituloSuperior' value=''><input type='hidden' name='Semanal_Confirmada' id='Semanal_Confirmada' value=''><input type='hidden' name='fechaCierreCompromisos' id='fechaCierreCompromisos' value=''><input type='hidden' name='fechaCreacionSemana' id='fechaCreacionSemana' value=''><input type='hidden' name='versionCronograma' id='versionCronograma' value=''>";

var navbarInicio =
  "<nav class='navbar navbar-expand-xl navbar-dark navbar-aia fixed-top' id='aiaNavbar'>" +
  "<div class='drawer-overlay' id='drawerOverlay'></div>" +
  "<div class='container-fluid'>" + // Use container-fluid to match component
  "<a class='navbar-brand' id='logoNavbar' href='#'>" +
  "<img src='../imagenes/florAIA.png' width='30' height='30' alt=''> Last Planner AIA" +
  '</a>' +
  "<button class='navbar-toggler' type='button' id='drawerToggle'>" +
  "<span class='navbar-toggler-icon'></span>" +
  '</button>' +
  "<div class='collapse navbar-collapse navbar-collapse-drawer' id='navbarSupportedContent'>" +
  "<div class='drawer-header d-xl-none'>" + // Simplified header structure
  "<h5 class='m-0'>Menú</h5>" +
  "<button type='button' class='close-drawer' id='drawerClose'>&times;</button>" +
  '</div>' +
  "<!--<a class='navbar-brand mr-auto ml-0' id='textoUbicacion' href='#'>--></a>";

// Inject interaction logic
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(function () {
    var toggle = document.getElementById('drawerToggle');
    var collapse = document.getElementById('navbarSupportedContent');
    var close = document.getElementById('drawerClose');
    var overlay = document.getElementById('drawerOverlay');

    function openDrawer() {
      if (collapse) collapse.classList.add('show');
      if (overlay) overlay.classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      if (collapse) collapse.classList.remove('show');
      if (overlay) overlay.classList.remove('show');
      document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openDrawer);
    if (close) close.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);
  }, 500);
});

var navInformacionGeneral =
  "<ul class='navbar-nav ml-4 mr-auto mt-2 mt-lg-0 main-links'><li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='informacionGeneral' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-info-circle nav-icon'></i> <span class='nav-text-full'>Información General</span><span class='nav-text-compact'>I. General</span></a><ul class='dropdown-menu' id='informacionGeneralMenu' aria-labelledby='informacionGeneral'>";

navInformacionGeneral +=
  "<li id='tituloInteresados' style='border-bottom: 1px solid white; border-top:1px solid white'><p class='ml-auto mr-auto' align='center' style='color:white; margin:3px 0'><b>Interesados</b></p></li><li><a class='dropdown-item' id='info_profesionales' href='#'>Profesionales AIA</a></li><li><a class='dropdown-item' id='info_subcontratistas' href='#'>Sub-Contratistas</a></li>";

navInformacionGeneral +=
  "<li id='tituloActividadesProyecto' style='border-bottom: 1px solid white; border-top:1px solid white; margin-top:10px'><p class='ml-auto mr-auto' align='center' style='color:white; margin:3px 0'><b>Actividades del Proyecto</b></p></li><li><a class='dropdown-item' id='info_listadoActividades' href='#'>Listado de Actividades</a></li><li><a class='dropdown-item' id='info_contratos' href='#'>Contratos</a></li><li><a class='dropdown-item' id='planCompras' href='#'>Plan de Compras</a></li>";

navInformacionGeneral +=
  "<li id='tituloIndicadores' style='border-bottom: 1px solid white; border-top:1px solid white; margin-top:10px'><p class='ml-auto mr-auto' align='center' style='color:white; margin:3px 0'><b>Indicadores </b></p></li><li><a class='dropdown-item' id='informe_lps' href='#'>Indicadores de Last Planner</a></li><!-- <li><a class='dropdown-item' id='informe_productividad' href='#'>Indicadores de Tasas de Producción</a></li> -->";

navInformacionGeneral +=
  "<li id='tituloActualizarCronograma' style='border-bottom: 1px solid white; border-top:1px solid white; margin-top:10px'><p class='ml-auto mr-auto' align='center' style='color:white; margin:3px 0'><b>Cronograma </b></p></li><li><a class='dropdown-item' id='actualizarCronograma' href='#'>Actualizar Cronograma</a></li></ul></li>";

var navIntegracion =
  "<!--<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='integracion' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-network-wired nav-icon'></i> <span class='nav-text-full'>Integración</span><span class='nav-text-compact'>Integración</span></a><ul class='dropdown-menu' id='integracionMenu' aria-labelledby='integracion'><li><a class='dropdown-item' id='controlCambios' href='#'>Control de Cambios</a></li></ul></li>-->";

var navSemanasProyecto =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='semanasProyecto' role='button' data-toggle='dropdown' aria-expanded='false'><i class='far fa-calendar-alt nav-icon'></i> <span class='nav-text-full'>Semanas del Proyecto</span><span class='nav-text-compact'>Semanas</span></a><ul class='dropdown-menu' id='semanasProyectoMenu' aria-labelledby='semanasProyecto'></ul></li>";

var navProgramaGeneral =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' id='programa_general' href='#' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-project-diagram nav-icon'></i> <span class='nav-text-full'>Programa General</span><span class='nav-text-compact'>P. General</span></a><ul class='dropdown-menu' id='programa_generalMenu' aria-labelledby='programa_general'></ul></li>";

var navProgramacionIntermedia =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' id='programacion_intermedia' href='#' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-clipboard-list nav-icon'></i> <span class='nav-text-full'>Liberación de Restricciones</span><span class='nav-text-compact'>Restricciones</span></a><ul class='dropdown-menu' id='programacion_intermediaMenu' aria-labelledby='programacion_intermedia'></ul></li>";

var navProgramacionSemanal =
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='programacion_semanal' role='button' data-toggle='dropdown' aria-expanded='false'><i class='fas fa-tasks nav-icon'></i> <span class='nav-text-full'>Programación Semanal</span><span class='nav-text-compact'>P. Semanal</span></a><ul class='dropdown-menu' id='programacion_semanalMenu' aria-labelledby='programacion_semanal'></ul></li></ul>";

var navNombreUsuario = 
  "<ul class='navbar-nav ml-auto align-items-center d-none d-xl-flex'>" +
  "<!-- Notificaciones (Campana) Desktop -->" +
  "<li class='nav-item dropdown mr-2'>" +
  "    <a class='nav-link dropdown-toggle' href='#' id='notificationDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' style='position: relative;'>" +
  "        <i class='fas fa-bell'></i>" +
  "        <span class='badge badge-danger badge-pill' id='notificationBadge' style='position: absolute; top: 0px; right: -5px; display: none; font-size: 0.6rem;'>0</span>" +
  "    </a>" +
  "    <div class='dropdown-menu dropdown-menu-right shadow-sm border-0' aria-labelledby='notificationDropdown' style='width: 320px; max-height: 400px; overflow-y: auto; padding: 0;'>" +
  "        <h6 class='dropdown-header bg-light border-bottom py-2 font-weight-bold'>Centro de Notificaciones</h6>" +
  "        <div id='notificationList'>" +
  "            <a class='dropdown-item text-muted text-center py-3' href='#'><i class='fas fa-spinner fa-spin mr-2'></i> Cargando...</a>" +
  "        </div>" +
  "    </div>" +
  "</li>" +
  "<li class='nav-item dropdown'><a class='nav-link dropdown-toggle' href='#' id='nombreUsuario' role='button' data-toggle='dropdown' aria-expanded='false'></a><ul class='dropdown-menu dropdown-menu-right' aria-labelledby='nombreUsuario'><li><a class='nav-link' href='../cerrar.php' tabindex='-1' aria-disabled='true'>Cerrar Sesión</a></li></ul></li></ul>" +
  "<!-- Mobile User Island (Thumb Zone - Mobile Only) -->" +
  "<div class='user-island d-xl-none'>" +
  "    <div class='user-island-header'>" +
  "        <div class='user-avatar-lg'>" +
  "            <i class='fas fa-user text-white'></i>" +
  "        </div>" +
  "        <div class='user-info'>" +
  "            <div class='user-name' id='labelNombreUsuarioMobile'>Usuario</div>" +
  "            <div class='user-role' id='labelRolUsuarioMobile'>Usuario</div>" +
  "        </div>" +
  "    </div>" +
  "    <div class='user-island-actions'>" +
  "        <a href='/proyectos' class='island-btn'>" +
  "            <i class='fas fa-exchange-alt'></i> Proyecto" +
  "        </a>" +
  "        <!-- Notificaciones Dropdown Mobile -->" +
  "        <div class='dropdown' style='display: flex;'>" +
  "            <a href='#' class='island-btn w-100 island-notification-btn' id='notificationDropdownMobile' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" +
  "                <i class='fas fa-bell'></i> Avisos" +
  "                <span class='badge badge-danger badge-pill' id='notificationBadgeMobile' style='display: none;'>0</span>" +
  "            </a>" +
  "            <div class='dropdown-menu shadow-sm border-0' aria-labelledby='notificationDropdownMobile' style='width: 260px; max-height: 300px; overflow-y: auto; padding: 0; bottom: 100%; top: auto !important; margin-bottom: 5px; left: 0;'>" +
  "                <h6 class='dropdown-header bg-light border-bottom py-2 font-weight-bold'>Notificaciones</h6>" +
  "                <div id='notificationListMobile'>" +
  "                    <a class='dropdown-item text-muted text-center py-3' href='#'><i class='fas fa-spinner fa-spin mr-2'></i> Cargando...</a>" +
  "                </div>" +
  "            </div>" +
  "        </div>" +
  "        <a href='../cerrar.php' class='island-btn btn-danger-soft' style='grid-column: span 2;'>" +
  "            <i class='fas fa-sign-out-alt'></i> Cerrar Sesión" +
  "        </a>" +
  "    </div>" +
  "</div></div></nav>";

document.getElementById('encabezado').innerHTML =
  document.getElementById('encabezado').innerHTML +
  inputosOcultos +
  navbarInicio +
  navInformacionGeneral +
  navIntegracion +
  navSemanasProyecto +
  navProgramaGeneral +
  navProgramacionIntermedia +
  navProgramacionSemanal +
  navNombreUsuario;

// Inyectar Script Notifications (DESPUÉS del innerHTML)
if (!document.querySelector('script[src*="notifications.js"]')) {
  var scriptNotif = document.createElement('script');
  scriptNotif.src = '/public/js/components/notifications.js?v=legacy4';
  document.head.appendChild(scriptNotif);
}

var cargarDatosGeneralesPagina = function (seccion) {
  $.ajax({
    method: 'POST',
    url: '../funciones_generales/php/datosGeneralesPagina.php',
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
      datosGenerales = json_info_global['data'];
      const _rol = datosGenerales.rolUsuario || '';
      datosGenerales._rolHtml = _rol ? '<br><small style="opacity:.65;font-size:.75em">' + _rol + '</small>' : '';
      listadoSemanas = json_info_global['data']['listadoSemanas'];

      document.getElementById('Fecha_Fin_Sem').value = datosGenerales.Fecha_Fin_Sem;
      document.getElementById('Fecha_Fin_SemYMD').value = datosGenerales.Fecha_Fin_SemYMD;
      document.getElementById('Fecha_Inicio_Sem').value = datosGenerales.Fecha_Inicio_Sem;
      document.getElementById('Fecha_Inicio_SemYMD').value = datosGenerales.Fecha_Inicio_SemYMD;
      document.getElementById('Fecha_datepicker').value = datosGenerales.Fecha_datepicker;
      document.getElementById('Max_Semana').value = datosGenerales.Max_Semana;
      document.getElementById('baseDatos').value = datosGenerales.db;
      document.getElementById('permiso_canonico').value = datosGenerales.permiso_canonico || datosGenerales.permiso || '';
      document.getElementById('pdcActivo').value = datosGenerales.pdcActivo;
      document.getElementById('proyecto').value = datosGenerales.proyecto;
      document.getElementById('semana').value = datosGenerales.semana;
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

      if (datosGenerales.pdcActivo == 1) {
        document.getElementById('tituloSuperior').innerHTML =
          "<h1 class='titulo'>Last Planner AIA - " + datosGenerales.proyecto + '</h1>';

        document.getElementById('info_profesionales').href =
          '../cambiar_pagina.php?seccion=info_profesionales&semana=' + datosGenerales.Max_Semana;

        document.getElementById('info_subcontratistas').href =
          '../cambiar_pagina.php?seccion=info_subcontratistas&semana=' + datosGenerales.Max_Semana;

        document.getElementById('info_listadoActividades').href =
          '../cambiar_pagina.php?seccion=info_listadoActividades&semana=' +
          datosGenerales.Max_Semana;

        document.getElementById('info_contratos').href =
          '../cambiar_pagina.php?seccion=info_contratos&semana=' + datosGenerales.Max_Semana;

        document.getElementById('planCompras').href =
          '../cambiar_pagina.php?seccion=planCompras&semana=' +
          datosGenerales.Max_Semana +
          '&origen=' +
          encodeURIComponent(seccion || '');

        document.getElementById('informe_lps').href =
          '../cambiar_pagina.php?seccion=indicadores&semana=' + datosGenerales.Max_Semana;

        // document.getElementById('informe_productividad').href =
        //   '../cambiar_pagina.php?seccion=informe_productividad&semana=' + datosGenerales.Max_Semana;

        document.getElementById('actualizarCronograma').href =
          '../cambiar_pagina.php?seccion=actualizarCronograma&semana=' + datosGenerales.Max_Semana;

        //document.getElementById('controlCambios').href = "../cambiar_pagina.php?seccion=controlCambios&semana="+datosGenerales.Max_Semana;
      } else {
        document.getElementById('tituloSuperior').innerHTML =
          "<h1 class='titulo'>Last Planner AIA - " + datosGenerales.proyecto + '</h1>';

        document.getElementById('info_profesionales').href =
          '../cambiar_pagina.php?seccion=info_profesionales&semana=' + datosGenerales.Max_Semana;

        document.getElementById('info_subcontratistas').href =
          '../cambiar_pagina.php?seccion=info_subcontratistas&semana=' + datosGenerales.Max_Semana;

        document.getElementById('informe_lps').href =
          '../cambiar_pagina.php?seccion=indicadores&semana=' + datosGenerales.Max_Semana;

        // document.getElementById('informe_productividad').href =
        //   '../cambiar_pagina.php?seccion=informe_productividad&semana=' + datosGenerales.Max_Semana;

        document.getElementById('actualizarCronograma').href =
          '../cambiar_pagina.php?seccion=actualizarCronograma&semana=' + datosGenerales.Max_Semana;

        //document.getElementById('controlCambios').href = "../cambiar_pagina.php?seccion=controlCambios&semana="+datosGenerales.Max_Semana;

        document.getElementById('tituloActividadesProyecto').remove();

        document.getElementById('info_listadoActividades').remove();

        document.getElementById('info_contratos').remove();

        document.getElementById('planCompras').remove();
      }

      var htmlSemanasProyecto =
        "<li><a class='dropdown-item' style='padding: 16px 0px 8px 16px'><button type='button' class='nueva_sem btn btn-primary btn-sm' title='Crear nueva semana de la programación semanal' onclick=\"nueva_sem('" +
        datosGenerales.db +
        "', 2, '" +
        seccion +
        "'); fechaNuevaSemana()\" data-toggle='modal' data-target='#modal_nueva_sem'><i class='fa fa-plus fa-m'></i> Nueva Semana</button></a></li>";

      var htmlProgramaGeneral = '';
      var htmlProgramacionIntermedia = '';
      var htmlProgramacionSemanal = '';
      var htmlIndicadores = '';

      if (datosGenerales.Max_Semana == 0) {
      } else if (datosGenerales.Max_Semana > 0) {
        for (semanaContador = datosGenerales.Max_Semana; semanaContador > 0; semanaContador--) {
          var valorSemana = !listadoSemanas[semanaContador - 1].Semana
            ? 0
            : listadoSemanas[semanaContador - 1].Semana;
          var ini = !listadoSemanas[semanaContador - 1].Fecha_Inicio_Sem
            ? '0000-00-00'
            : listadoSemanas[semanaContador - 1].Fecha_Inicio_Sem;
          var fin = !listadoSemanas[semanaContador - 1].Fecha_Fin_Sem
            ? '0000-00-00'
            : listadoSemanas[semanaContador - 1].Fecha_Fin_Sem;
          //console.log(semana, ini, fin);

          //console.log(semanaContador == datosGenerales.semana);
          if (datosGenerales.Max_Semana - 2 >= semanaContador) {
            htmlSemanasProyecto =
              htmlSemanasProyecto +
              "<li><a class='dropdown-item' id='semanaProyecto" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=" +
              seccion +
              '&semana=' +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';

            htmlProgramaGeneral =
              htmlProgramaGeneral +
              "<li><a class='dropdown-item' id='programa_generalMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programa_general&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';

            htmlProgramacionIntermedia =
              htmlProgramacionIntermedia +
              "<li><a class='dropdown-item' id='programacion_intermediaMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';

            htmlProgramacionSemanal =
              htmlProgramacionSemanal +
              "<li><a class='dropdown-item' id='programacion_semanalMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';
          } else {
            htmlSemanasProyecto =
              htmlSemanasProyecto +
              "<li style='display:flex; margin-right:5px'><a class='dropdown-item' id='semanaProyecto" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=" +
              seccion +
              '&semana=' +
              valorSemana +
              "' >Semana " +
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

            htmlProgramaGeneral =
              htmlProgramaGeneral +
              "<li><a class='dropdown-item' id='programa_generalMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programa_general&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';

            htmlProgramacionIntermedia =
              htmlProgramacionIntermedia +
              "<li><a class='dropdown-item' id='programacion_intermediaMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';

            htmlProgramacionSemanal =
              htmlProgramacionSemanal +
              "<li><a class='dropdown-item' id='programacion_semanalMenu" +
              valorSemana +
              "' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=" +
              valorSemana +
              "'>Semana " +
              valorSemana +
              ' (del ' +
              ini +
              ' al ' +
              fin +
              ')</a></li>';
          }
        }
      }

      //console.log(htmlSemanasProyecto);
      document.getElementById('semanasProyectoMenu').innerHTML = htmlSemanasProyecto;
      document.getElementById('programa_generalMenu').innerHTML = htmlProgramaGeneral;
      document.getElementById('programacion_intermediaMenu').innerHTML = htmlProgramacionIntermedia;
      document.getElementById('programacion_semanalMenu').innerHTML = htmlProgramacionSemanal;

      var seccionNombre = {
        info_profesionales: 'Profesionales AIA',
        info_subcontratistas: 'Sub-Contratistas',
        info_listadoActividades: 'Actividades del Proyecto',
        info_contratos: 'Contratos',
        info_paquetesContratacion: 'Paquetes de Contratación',
        planCompras: 'Plan de Compras',
        programa_general: 'Programa General',
        programacion_intermedia: 'Liberación de Restricciones',
        programacion_semanal: 'Programación Semanal',
        CNP: 'Causas de No Programación',
        CNC: 'Causas de No Cumplimiento',
        CIC: 'Calificación Integral de Proveedores',
        indicadores: 'Indicadores de Last Planner',
        informe_productividad: 'Indicadores de Tasas de Producción',
        actualizarCronograma: 'Actualizar Cronograma',
        controlCambios: 'Control de Cambios',
      };

      var ini =
        datosGenerales.semana == 0
          ? '0000-00-00'
          : listadoSemanas[datosGenerales.semana - 1].Fecha_Inicio_Sem;
      var fin =
        datosGenerales.semana == 0
          ? '0000-00-00'
          : listadoSemanas[datosGenerales.semana - 1].Fecha_Fin_Sem;

      if (
        seccion == 'info_profesionales' ||
        seccion == 'info_subcontratistas' ||
        seccion == 'info_listadoActividades' ||
        seccion == 'info_contratos' ||
        seccion == 'info_paquetesContratacion' ||
        seccion == 'planCompras' ||
        seccion == 'actualizarCronograma'
      ) {
        document.getElementById('nombreUsuario').innerHTML = datosGenerales.nombreUsuario + (datosGenerales._rolHtml || '');
        document.getElementById(seccion).classList.add('active');
        document.getElementById('informacionGeneral').classList.add('active');
        document.getElementById('informacionGeneral').innerHTML =
          'Información General <br>(' + seccionNombre[seccion] + ')';
        document.getElementById('textoDireccionSeccion').innerHTML =
          '<strong>' +
          datosGenerales.proyecto +
          '</strong> / Last Planner / ' +
          seccionNombre[seccion];
      } else if (seccion == 'informe_productividad' || seccion == 'indicadores') {
        document.getElementById('nombreUsuario').innerHTML = datosGenerales.nombreUsuario + (datosGenerales._rolHtml || '');
        if (seccion == 'indicadores') {
          document.getElementById('informe_lps').classList.add('active');
        } else {
          document.getElementById(seccion).classList.add('active');
        }
        document.getElementById('informacionGeneral').classList.add('active');
        document.getElementById('informacionGeneral').innerHTML =
          'Información General <br>(' + seccionNombre[seccion] + ')';
        document.getElementById('textoDireccionSeccion').innerHTML =
          '<strong>' +
          datosGenerales.proyecto +
          '</strong> / Last Planner / ' +
          seccionNombre[seccion];
      } else if (seccion == 'controlCambios') {
        document.getElementById('nombreUsuario').innerHTML = datosGenerales.nombreUsuario + (datosGenerales._rolHtml || '');
        document.getElementById(seccion).classList.add('active');
        document.getElementById('integracion').classList.add('active');
        document.getElementById('integracion').innerHTML =
          'Integración<br>(' + seccionNombre[seccion] + ')';
        document.getElementById('textoDireccionSeccion').innerHTML =
          '<strong>' +
          datosGenerales.proyecto +
          '</strong> / Last Planner / ' +
          seccionNombre[seccion];
      } else if (
        seccion == 'programacion_semanal' ||
        seccion == 'CNP' ||
        seccion == 'CNC' ||
        seccion == 'CIC'
      ) {
        document.getElementById('nombreUsuario').innerHTML = datosGenerales.nombreUsuario + (datosGenerales._rolHtml || '');
        document
          .getElementById('programacion_semanalMenu' + datosGenerales.semana)
          .classList.add('active');
        document.getElementById('programacion_semanal').classList.add('active');
        document.getElementById('programacion_semanal').innerHTML =
          seccionNombre['programacion_semanal'] + ' <br>(Semana ' + datosGenerales.semana + ')';
        document.getElementById('textoDireccionSeccion').innerHTML =
          '<strong>' +
          datosGenerales.proyecto +
          '</strong> / Last Planner / ' +
          seccionNombre[seccion] +
          ' / Semana ' +
          datosGenerales.semana +
          ' (del ' +
          ini +
          ' al ' +
          fin +
          ')';
      } else {
        document.getElementById('nombreUsuario').innerHTML = datosGenerales.nombreUsuario + (datosGenerales._rolHtml || '');
        document.getElementById(seccion + 'Menu' + datosGenerales.semana).classList.add('active');
        document.getElementById(seccion).classList.add('active');
        document.getElementById(seccion).innerHTML =
          seccionNombre[seccion] + ' <br>(Semana ' + datosGenerales.semana + ')';
        document.getElementById('textoDireccionSeccion').innerHTML =
          '<strong>' +
          datosGenerales.proyecto +
          '</strong> / Last Planner / ' +
          seccionNombre[seccion] +
          ' / Semana ' +
          datosGenerales.semana +
          ' (del ' +
          ini +
          ' al ' +
          fin +
          ')';
      }

      if (datosGenerales.semana > 0) {
        document.getElementById('semanaProyecto' + datosGenerales.semana).classList.add('active');
      }

      cargaParametros();
    },
  });
};

var maestroPermisos = function (permiso) {
  switch (permiso) {
    case 'R':
      //Bloqueos Información General
      $(
        '#btn_cargarActividadesExcel, #btn_nueva_actividad, #btn_guardar_contratos, #btn_guardar_pdc, #btn_actualizarPDC'
      ).css('display', 'none');

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
      $('#si_cal, #si_adm, #si_sst, #mdo_cal, #mdo_adm, #mdo_sst').css('display', 'none');
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
      $('#si_cal, #si_adm, #si_gsa, #mdo_cal, #mdo_adm, #mdo_gsa').css('display', 'none');
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
      $('#si_cal, #si_adm, #mdo_cal, #mdo_adm').css('display', 'none');
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
};
