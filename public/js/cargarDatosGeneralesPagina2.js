// Este loader ya no monta navegación: todas las vistas que lo cargan usan el
// shell sidebar canónico (views/partials/shell_sidebar.php) y declaran
// window.__AIA_SHELL_SIDEBAR__ = true. Aquí solo quedan los inputs ocultos, el
// AJAX de datos generales y los permisos de vista.
//
// La rama legacy inyectaba la barra superior y su hoja de estilos, borrada en
// 42ba76c. La inyección seguía viva y producía un 404 más una barra sin estilos
// (position:fixed) superpuesta a la barra de contexto en las tres vistas que aún
// no tenían shell (/contratos, /listado-actividades, /pdc). Los guards viven en
// tests/test_foundation_shell_contract.mjs y en
// tests/design-system/dead-theme-removal.test.mjs.

// Inyectar FontAwesome local si no llegó ya, sea por <link> directo o por el
// entrypoint del design system (que lo importa como vendor). Antes se traía
// 5.15.4 de cdnjs duplicando la copia local: todos los iconos usados en el
// código existen en el vendor local.
//
// El design system tiene DOS entrypoints y hay que reconocer los dos: el
// agregador (`aia-design-system.css`) que sirve a las vistas en `render()`, y
// el core segmentado (`design-system/entrypoints/core.css`) que sirve a las
// migradas vía `renderForModule()`. `font-awesome` está en CORE_VENDORS, así
// que ambos lo importan con `layer(vendor)`.
//
// Mirar solo el agregador dejó a las cuatro rutas de /programación semanal
// —movidas al head segmentado en c0dd9a4— inyectando una SEGUNDA copia de
// Font Awesome, con 1436 reglas que entraban SIN CAPA y por tanto ganaban a
// todas las capas del design system en declaraciones normales. Lo cazó
// tests/browser/design-system-unlayered-delivery.mjs.
//
// La rama de inyección se conserva: hay vistas que cargan este script sin
// ningún head del design system y se quedarían sin iconos si se retirara.
if (
  !document.querySelector('link[href*="font-awesome"]') &&
  !document.querySelector('link[href*="aia-design-system"]') &&
  !document.querySelector('link[href*="design-system/entrypoints/core.css"]')
) {
  var faLink = document.createElement('link');
  faLink.href = '/public/vendor/font-awesome/css/all.css';
  faLink.rel = 'stylesheet';
  document.head.appendChild(faLink);
}

// C-46 (paso 2/2): los cinco ids que el PHP ya emite —Max_Semana, baseDatos,
// permiso_canonico, semana y Semanal_Confirmada— salieron de esta plantilla.
// Los inyectaba en las 10 vistas que cargan este script, duplicando un id que
// el servidor ya resuelve; como el inyector hace `encabezado.innerHTML += ...`,
// la copia del PHP iba primero y se quedaba con el `getElementById`, dejando la
// inyectada vacía e inerte. Ahora hay una sola fuente por id: manda el servidor.
//
// Los `setVal(...)` de la respuesta AJAX NO se tocan: siguen escribiendo el
// valor fresco sobre el campo del PHP, que es lo que ya hacían. `setVal` está
// guardado con `if (el)`, así que en las vistas donde el PHP no emite un id
// concreto (por ejemplo `Semanal_Confirmada` en /indicadores o /control-cambios,
// donde ningún JS lo lee) la llamada queda en no-op sin romper nada.
//
// Los 11 restantes se quedan: están fuera de C-46 y ninguna vista los emite.
var inputosOcultos =
  "<input type='hidden' name='Fecha_Fin_Sem' id='Fecha_Fin_Sem' value=''><input type='hidden' name='Fecha_Fin_SemYMD' id='Fecha_Fin_SemYMD' value=''><input type='hidden' name='Fecha_Inicio_Sem' id='Fecha_Inicio_Sem' value=''><input type='hidden' name='Fecha_Inicio_SemYMD' id='Fecha_Inicio_SemYMD' value=''><input type='hidden' name='Fecha_datepicker' id='Fecha_datepicker' value=''><input type='hidden' name='proyecto' id='proyecto' value=''><input type='hidden' name='pdcActivo' id='pdcActivo' value=''><input type='hidden' name='tituloSuperior' id='tituloSuperior' value=''><input type='hidden' name='fechaCierreCompromisos' id='fechaCierreCompromisos' value=''><input type='hidden' name='fechaCreacionSemana' id='fechaCreacionSemana' value=''><input type='hidden' name='versionCronograma' id='versionCronograma' value=''>";

// Refresca el area del proyecto desde la respuesta AJAX. La consumen
// programacion_semanal/hot.js y legacyCards.js para pedir categorias por area.
//
// Hasta el 2026-08-04 esta funcion ademas ocultaba los items del nav legado
// (tituloActividadesProyecto, info_listadoActividades, info_contratos, planCompras,
// tituloInteresados, info_subcontratistas) en proyectos de Pre-Construccion. Ese nav lo
// producia info_general_nav.js, borrado con el PDC v1: ningun documento crea ya esos ids.
// La visibilidad por rol y por area vive ahora en views/partials/shell_sidebar.php.
function applyProjectTypeVisibility(datosGenerales) {
  window.__PROJECT_AREA__ = datosGenerales.area || datosGenerales.Area || window.__PROJECT_AREA__ || 'Construccion';
}

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

document.getElementById('encabezado').innerHTML =
  document.getElementById('encabezado').innerHTML + inputosOcultos;

// El bloque que ocultaba los modulos de solo-construccion en Pre-Construccion se retiro el
// 2026-08-04: apuntaba a los ids del nav legado del PDC v1, que ya no crea ningun documento.
// La regla equivalente vive en views/partials/shell_sidebar.php ($shellArea === 'Pre-Construccion').

// Inyectar Script Notifications (DESPUÉS del innerHTML para que existan los elementos)
if (!document.querySelector('script[src*="notifications.js"]')) {
  var scriptNotif = document.createElement('script');
  scriptNotif.src = '/public/js/components/notifications.js?v=legacy4';
  document.head.appendChild(scriptNotif);
}

// --- Initialize Drawer Handling ---
$(document).ready(function () {
  if (window.AiaNavDrawer) window.AiaNavDrawer.init();

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

      var setVal = function(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = (val !== null && val !== undefined) ? val : '';
      };

      setVal('Fecha_Fin_Sem', datosGenerales.Fecha_Fin_Sem);
      setVal('Fecha_Fin_SemYMD', datosGenerales.Fecha_Fin_SemYMD);
      setVal('Fecha_Inicio_Sem', datosGenerales.Fecha_Inicio_Sem);
      setVal('Fecha_Inicio_SemYMD', datosGenerales.Fecha_Inicio_SemYMD);
      setVal('Fecha_datepicker', datosGenerales.Fecha_datepicker);
      setVal('Max_Semana', datosGenerales.Max_Semana);
      setVal('baseDatos', datosGenerales.db);
      setVal('permiso_canonico', datosGenerales.permiso_canonico || datosGenerales.permiso);
      setVal('pdcActivo', datosGenerales.pdcActivo);
      setVal('proyecto', datosGenerales.proyecto);
      setVal('semana', datosGenerales.semana);

      try {
        if (typeof cargaParametros === 'function') {
          cargaParametros();
        } else {
          console.error("🕵️ [DeepAnalysis] CRÍTICO: cargaParametros NO es una función o es undefined en este contexto global. Tipo actual:", typeof cargaParametros);
        }
      } catch (error) {
        console.error("🕵️ [DeepAnalysis] Excepción ahogada (swallowed) capturada al ejecutar cargaParametros():", error);
      }

      setVal('Semanal_Confirmada', datosGenerales.Semanal_Confirmada);
      setVal('fechaCierreCompromisos', datosGenerales.fechaCierreCompromisos);
      setVal('fechaCreacionSemana', datosGenerales.fechaCreacionSemana);
      setVal('versionCronograma', datosGenerales.versionCronograma);

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
          planCompras: 'Plan de Compras',
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

      // C-30: aqui se escribia un `<h1>Last Planner AIA - <proyecto></h1>` dentro de
      // `#tituloSuperior`, que es un <input type=hidden> creado por este mismo archivo
      // (linea 58). Un input no puede tener hijos renderizados, asi que ese h1 no se
      // veia nunca, pero si contaba: era el unico h1 del documento en todas las vistas
      // con shell y ademas decia lo mismo en todas. Cada vista declara ahora su propio
      // h1 descriptivo, asi que este quedaba como duplicado fantasma.

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

      // Los manejadores de info_listadoActividades, info_contratos y planCompras se retiraron el
      // 2026-08-04: eran los items del nav legado del PDC v1 y apuntaban a rutas ya inexistentes.
      // El enlace vigente a Plan de Compras v2 lo pinta views/partials/shell_sidebar.php.

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
        '#btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
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
        '#btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
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
        '#btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
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
        '#btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
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
        '#btn_eliminarActualizacion'
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
        '#btn_cargarCronogramaExcel, #btn_eliminarActualizacion'
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
