# Semanas del Proyecto en el sidebar canónico: flyout de gestión con crear/eliminar

**Fecha:** 2026-07-22 · **Estado:** aprobado por el usuario (chat) · **Alcance:** desktop ≥1180px, dark

## Problema

El shell sidebar (DS-027) reemplazó al navbar legacy, pero perdió el dropdown "Semanas del
Proyecto": la lista de semanas con los botones de **crear** y **eliminar** semana. Hoy el shell
solo permite cambiar de semana (chip de la context-bar y flyouts por módulo); la gestión no
existe en ninguna superficie migrada.

## Decisiones del usuario

- Ítem "Semanas del Proyecto" en el sidebar con flyout de gestión (semanas + Nueva/Eliminar).
- El chip de la context-bar queda **solo como cambio rápido** (sin gestión) y gana un icono
  chevron que indique que es desplegable.
- Formularios como **diálogos nuevos del design system** (no los modales Bootstrap legacy).

## Comportamiento legacy de referencia (fuente: cargarDatosGeneralesPagina2.js, funcionesGenerales6.js)

- **Crear**: pre-check `POST /legacy/funciones_generales/php/verificarCICActualizada.php`
  `{db, semana}` → si ≠0, warning (faltan Calificaciones Integrales) y redirección a CIC.
  Luego `POST /legacy/funciones_generales/php/nueva_semana.php?db=X` `{f_inicio_sem, opcion:'nueva_sem'}`
  (guard server `lps.semana.crear`). Desenlaces: `{respuesta:'ERROR', mensaje}`; bloqueo si la
  semana actual no tiene compromisos confirmados y el rol no es admin (warning + ir a PS); éxito
  → redirigir a Programa General en la semana nueva. Fecha sugerida: día siguiente al
  `Fecha_Fin_Sem` de la última semana, **editable**. El servidor calcula fin = inicio + 6 días.
- **Eliminar**: `POST /legacy/funciones_generales/php/eliminar_semana.php?db=X`
  `{semana, opcion:'eliminar_sem'}` → `{puedeEliminar:'SI'|…, maxSemana}`. El servidor **solo
  permite eliminar la semana máxima** (el UI legacy mostraba trash en las 2 últimas; mentía).
- Avisos con `aiaNoticeInvoke` (adapter SweetAlert tokenizado).

## Diseño

### 1. Ítem y flyout

- Nuevo ítem en grupo **Información** (2º, tras Control Tower), icono `calendar`, en
  `views/partials/shell_sidebar.php`.
- **Extensión aditiva** de `DesignSystemComponent::sidebarNavigation`
  (src/View/Components/DesignSystemComponent.php): tipo de ítem **`action`** → renderiza
  `<button type="button" class="aia-sidebar__link" data-sidebar-action aria-haspopup="menu"
  aria-expanded>` (los ítems de navegación siguen siendo `<a>`). `aria-expanded` lo sincroniza
  el JS del flyout.
- Flyout (mismo mecanismo `.shell-week-flyout` existente, construido por el builder del partial):
  - Cabecera "Semanas del Proyecto" + botón **"+ Nueva semana"** (si permiso).
  - Lista completa de semanas "Semana N (fechas)"; click → `cambiarSemanaSesion(N, ruta actual)`
    (reutiliza el listener delegado `[data-shell-week]` sin `data-shell-path`). Activa con
    `aria-current` (tile verde).
  - **Trash solo en la última semana** (44px, `aria-label="Eliminar Semana N"`, si permiso).

### 2. Diálogos DS (server-rendered en el partial, solo con permiso)

- `<dialog id="shellWeekCreateDialog">` y `<dialog id="shellWeekDeleteDialog">` con las clases
  de la familia overlays aprobada (public/css/design-system/components/dialog.css + `aia-btn`).
- **Crear**: título "Crear Semana {max+1}", `input type="date"` (id con `<label>`) pre-sugerido
  al día siguiente del fin de la última semana, editable; preview viva "Irá del {inicio} al
  {inicio+6}" actualizada on `input`. Botones Crear (primario) / Cancelar.
- **Eliminar**: "¿Eliminar la Semana {max} (del X al Y)?" + aviso de irreversibilidad; botón
  destructivo / Cancelar.
- `showModal()`/`close()` nativos (focus-trap y Escape); restaurar foco al trigger al cerrar.

### 3. Módulo `public/js/modules/aia_ui/shell_week_admin.js`

- Sin jQuery. Config desde el JSON `#shellWeekMenusData` del partial, que gana campos
  server-side: `db`, `esAdmin`, `maxSemana`, `canCreate`, `canDelete`.
- Flujo crear: pre-check CIC → warning + redirect a CIC si falta; `POST nueva_semana.php` →
  ERROR → notice; bloqueo compromisos (no-admin) → warning + `cambiarSemanaSesion(semanaActual,
  '/programacion-semanal')`; éxito → `cambiarSemanaSesion(semanaNueva, '/programa-general')`.
  Botón disabled + `aria-busy` durante el vuelo. **Verificar en implementación el shape exacto
  de la respuesta de éxito leyendo src/Legacy/nueva_semana.php** (qué campo trae la semana
  nueva), no asumir del JS legacy.
- Flujo eliminar: `POST eliminar_semana.php` → `puedeEliminar==='SI'` →
  `cambiarSemanaSesion(maxSemana-1, ruta actual)`; si no, warning con `maxSemana`. Errores de
  red → `aiaNoticeInvoke` error y botón re-habilitado.
- Sin `location.assign('/legacy/cambiar_pagina.php…')`: siempre rutas limpias vía
  `cambiarSemanaSesion` (la ruta de CIC se resuelve en implementación mirando el router).

### 4. Chip de la context-bar

- Añadir glifo **`chevron-down`** (aditivo al mapa de iconos del componente) dentro del chip
  `#ctxSemanaBadge`, decorativo (`aria-hidden`) — el botón ya tiene semántica de menú.

### 5. RBAC

- Visibilidad decidida server-side en el partial con `RbacService::can('lps.semana.crear'|
  'lps.semana.eliminar', $rol)` — los mismos permisos que ya usan los guards de los endpoints
  (verificar nombres exactos en el catálogo al implementar). Sin permiso: ni botones ni diálogos
  en el DOM. La lista de semanas (cambio) visible para todos.

## Verificación

- Contract PHP de componentes ampliado para el ítem `action`
  (tests/test_design_system_components.php) + foundation-shell contract + biome + `php -l`.
- Probe PHP del partial: Nueva/Eliminar presentes para rol A, ausentes para rol V.
- Probe Playwright (1180×820 dark, PI): flyout con semanas + acciones; diálogo crear (fecha
  sugerida correcta, preview viva); trash solo en la última semana; flujos crear/eliminar con
  `page.route` interceptando los endpoints (payloads correctos y 3 desenlaces) **sin mutar la BD
  compartida** (crear semana real copia el programa completo).
- Validación visual en el panel del navegador.

## Fuera de alcance

- Migrar los modales legacy en las vistas no migradas (siguen con funcionesGenerales6.js).
- Tocar la lógica de negocio de nueva_semana.php / eliminar_semana.php.
- Mobile/tablet/linen (prohibidos por AGENTS.md).
