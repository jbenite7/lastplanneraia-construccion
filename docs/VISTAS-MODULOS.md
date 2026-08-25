---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-04-07
fuente: docs/VISTAS-MODULOS.md
resumen: Catálogo de Módulos de Vistas — Last Planner AIA
---

# Catálogo de Módulos de Vistas — Last Planner AIA

> Fuente de verdad para la UI del proyecto. Describe cada módulo de vistas, su propósito, dependencias, estilo visual y arquitectura técnica.

---

## Índice

- [Resumen de Arquitectura Visual](#resumen-de-arquitectura-visual)
- [Tokens de Diseño y Paleta de Colores](#tokens-de-diseño-y-paleta-de-colores)
- [Módulos de la App Principal (`views/`)](#módulos-de-la-app-principal-views)
- [Módulos del Panel Admin (`admin/views/`)](#módulos-del-panel-admin-views)
- [Hoja de Estilos CSS (`public/css/`)](#hoja-de-estilos-css-publiccss)
- [Scripts JavaScript (`public/js/`)](#scripts-javascript-publicjs)
- [Patrones Compartidos](#patrones-compartidos)
- [Notas de Migración](#notas-de-migración)

---

## Resumen de Arquitectura Visual

### Dos Paradigmas de UI Coexistentes

| Paradigma | Vistas | Motor | Características |
|-----------|--------|-------|-----------------|
| **DataTable** (legacy) | CNP, CNC, CIC, Indicadores, Control Cambios | DataTables 1.11.4 | Tablas con paginación, filtros por columna, modales de edición |
| **Handsontable** (moderno) | PDC, Programa General, Programa Actualizar, Programación Semanal, Programación Intermedia, Profesionales, Subcontratistas | Handsontable (full) | Grillas tipo spreadsheet, edición inline, auto-save, vista móvil tipo tarjetas |

### Patrón de Carga

- **Vistas DataTable**: Cada vista es un documento HTML completo. El navbar se inyecta dinámicamente vía JS (`linksComunesHead2.js` → `cargarDatosGeneralesPagina2.js`).
- **Vistas Handsontable**: Mismo patrón, pero con módulos JS específicos (`hot.js` + `stateMachine.js`).
- **Panel Admin**: Usa layout compartido (`admin/views/layouts/main.php`) con sidebar AdminLTE.

---

## Tokens de Diseño y Paleta de Colores

### Colores de Marca Principal (`public/css/tokens.css`)

| Token | OKLCH | Hex Aprox. | Uso |
|-------|-------|------------|-----|
| `--aia-green-primary` | `oklch(32% 0.07 148.5)` | `#1a5633` | Verde corporativo AIA, navbar |
| `--aia-green-dark` | `oklch(27.8% 0.05 147.1)` | `#1a3c2a` | Verde oscuro, fondo drawer móvil |
| `--aia-orange-primary` | `oklch(45% 0.16 46.2)` | `#b55211` | Naranja construcción, acentos legacy y alertas operativas |
| `--aia-orange-dark` | `oklch(35% 0.13 46.2)` | `#8b4011` | Naranja oscuro, estados y acentos legacy |
| `--aia-blue-primary` | `oklch(56% 0.12 247.1)` | `#4a81bd` | Azul arquitectura |
| `--aia-aqua-primary` | `oklch(61% 0.13 189)` | `#00a499` | Aqua, acentos de proyectos |

### Estados Semánticos (`public/css/styles.css`)

| Estado | Fondo | Borde | Texto |
|--------|-------|-------|-------|
| **Missing** (faltan datos) | `#f3e8ff` | `#c084fc` | `#6b21a8` |
| **Critical Delay** (atraso crítico) | `#fee2e2` | `#dc2626` | `#991b1b` |
| **Delayed** (atrasado) | `#fff1e7` | `#e87722` | `#8a3f12` |
| **Completed Late** (completado tarde) | `#fff7d6` | `#d4a017` | `#8a5a00` |
| **Completed On Time** (completado a tiempo) | `#e8f6ec` | `#69b578` | `#25643a` |
| **Active** (en curso) | `#e6f0ff` | `#4a81bd` | `#1f4f82` |
| **Not Started** (no iniciado) | `#f3f4f6` | `#c7cdd4` | `#4b5563` |

### Sistema de Modales `.aia-modal`

| Elemento | Estándar |
|----------|----------|
| Contenedor | Todo modal Bootstrap visible debe usar `.modal.aia-modal` |
| Header | Gradiente Verde AIA `#1a3c2a → #1a5633`, texto blanco |
| Body | Fondo Linen `#F4F1EA` |
| Footer | Fondo Alabaster `#FAFAFA`, borde verde sutil |
| Botones | `.aia-btn-primary` / `.aia-btn-secondary` o selectores descendientes `.aia-modal .modal-footer .btn` |
| Formularios | Inputs dentro de `.aia-modal` heredan borde y focus ring verde |
| Tablas | `.aia-modal .table` usa head verde claro `#d5e5db` y hover `#eef5f1` |
| Mobile | Cada `.modal-dialog` debe incluir `modal-dialog-centered`; validado a `375px` |
| IDs | Unicidad dentro del DOM renderizado; no renombrar IDs repetidos en vistas independientes |

Validación recomendada: `node tests/browser/modal-brand.mjs`.

### Colores Apple System (`public/css/styles.css`, capa theme)

| Token | Valor | Uso |
|-------|-------|-----|
| `--primary` | `#0071e3` | Azul sistema, botones de acción |

Los seis `--status-*`, los tres `--color-*`, `--primary-dark`, `--primary-light` y
`--text-inverse` **ya no existen**: F1 Task 5l los borró tras censar cero
consumidores. Para estado usa la escalera `--ds-state-tint-*` de
`public/css/tokens.css`; para gráficos de BI, los `--bi-status-*`.

### Modo Oscuro (`public/css/dark-mode.css`)

| Token | Valor | Uso |
|-------|-------|-----|
| `--surface-bg` | `#1C1C1E` | Fondo página modo oscuro |
| `--surface-card` | `#2C2C2E` | Fondo tarjetas modo oscuro |
| `--text-main` | `#F4F1EA` | Texto principal (linen) |

---

## Módulos de la App Principal (`views/`)

### 1. Login (`auth/login.view.php`)

**Propósito:** Página de inicio de sesión principal de la aplicación.

**Dependencias externas:**
- Google Fonts: Montserrat 600/700, Inter 400/500/600
- Font Awesome 5.15.4
- AdminLTE 3.2
- SweetAlert2 11.4.24
- jQuery 3.6.0, Bootstrap 4.6.1

**CSS cargados:**
- `/css/login-brand-unified.css` — estilos modernos del login con OKLCH
- `/css/styles.css` (vía linksComunesHead2.js)
- `/css/navbar.css` (vía linksComunesHead2.js)

**JS cargados:**
- `/runtime/frontend-config.js`
- `/js/tablet-viewport-scale.js`
- `/public/js/core/AiaAlertInterceptor.js`

**Variables que recibe:**
- `$errores` — errores de validación
- `$resetNotice` — mensaje de éxito tras reset de contraseña
- `$timeoutNotice` — aviso de sesión expirada
- `$inactiveNotice` — aviso de cuenta inactiva
- `$_SESSION['must_change_password']` — trigger del modal de cambio forzado

**Elementos interactivos:**
- Formulario login (`POST /login`) con usuario + contraseña, iconos Font Awesome
- Enlace "¿Olvidaste tu contraseña?" → `/password/forgot`
- Modal SweetAlert2 de cambio de contraseña forzado (valida mín. 6 chars, mayúscula, carácter especial)

**Colores:**
- Botón marca: verde AIA OKLCH `44% 0.11 152` (`btn-aia`)
- Fondo: gradientes radiales con tintes verde/azul
- Footer: texto `#424242`
- Icono de candado: `#34c759`

**Estilo de diseño:**
- Tarjeta glassm-like con header degradado
- Patrón AdminLTE login-box
- Diseño centrado, responsive con container queries

**CSS inyectado:** Sí — estilos inline para el modal de cambio de contraseña (`.aia-password-modal`, `.brand-modal-content`, etc.)

---

### 2. Registro (`auth/registrate.view.php`) ⚠️ DEPRECADO

**Propósito:** Formulario de registro de usuarios (estilo legacy).

**Estado:** **Candidato a eliminación.** La creación de usuarios se centralizó en el panel admin (`admin/views/pages/users/create.php`).

**Dependencias externas:**
- Google Fonts: Roboto
- Font Awesome 5.11.2 + 5.7.1 (duplicado)
- Favicon: `/img/florAIA.png`

**CSS cargados:**
- Ninguno propio. El registro frontend legacy ya no consume la hoja retirada `stylesLogin.css`; login y recuperación usan `/css/login-brand-unified.css` y los tokens actuales.

**Variables que recibe:**
- `$errores` — errores de validación
- Query directa a DB dentro de la vista para el dropdown de proyectos

**Elementos interactivos:**
- Formulario de registro con: nombre, email, cargo, proyecto (select poblado por DB), permisos (select de 10 roles: A, V, OT, R, S, G, SG, DCV, C), usuario, contraseña, confirmar contraseña

**Colores:** Sin paleta propia; los valores dark/lima del registro anterior pertenecían a la hoja legacy retirada.

**Estilo de diseño:** Vista legacy sin hoja propia; la experiencia vigente de autenticación se mantiene en los flujos tokenizados.

**CSS inyectado:** No

---

### 3. Olvidé mi Contraseña (`auth/password-forgot.view.php`)

**Propósito:** Página para solicitar restablecimiento de contraseña.

**Dependencias externas:** Mismas que login (Montserrat, Inter, Font Awesome, AdminLTE, SweetAlert2)

**CSS cargados:**
- `/css/login-brand-unified.css` (compartido con login)

**JS cargados:** Mismos que login

**Variables que recibe:**
- `$message`, `$messageType`, `$emailValue`, `$csrfToken`

**Elementos interactivos:**
- Formulario email (`POST /password/forgot`) con token CSRF
- Enlace de retorno a `/login`

**Colores:** Idénticos al login (comparte `login-brand-unified.css`)

**Estilo de diseño:** Tarjeta centrada, mismo patrón visual que login

**CSS inyectado:** No

---

### 4. Reset de Contraseña (`auth/password-reset.view.php`)

**Propósito:** Establecer nueva contraseña tras hacer clic en enlace de reset.

**Dependencias externas:** Mismas que login

**CSS cargados:**
- `/css/login-brand-unified.css`

**Variables que recibe:**
- `$message`, `$messageType`, `$csrfToken`, `$token`, `$isTokenValid`

**Elementos interactivos:**
- Formulario contraseña + confirmar (`POST /password/reset`) con CSRF + token
- Si el token es inválido, muestra enlace para solicitar nuevo reset

**Colores:** Idénticos al login

**Estilo de diseño:** Tarjeta centrada, mismo patrón visual que login

**CSS inyectado:** No

---

### 5. Selector de Proyectos (`core/project_selector.view.php`)

**Propósito:** Dashboard de selección de proyecto que aparece tras el login.

**Dependencias externas:**
- Font Awesome
- AdminLTE
- Google Fonts: Roboto
- jQuery, Bootstrap 4, AdminLTE JS

**CSS cargados:**
- Estilos inline en la vista (213 líneas)
- `/css/styles.css` (vía NavbarComponent)

**JS cargados:**
- `/public/js/core/SessionTimeoutManager.js`
- `/js/tablet-viewport-scale.js`

**Variables que recibe:**
- `$proyectos` — array de objetos con `Proyecto_Proceso`, `Activo`, `rol_nombre`/`permiso`, `progreso`

**Elementos interactivos:**
- Input de búsqueda para filtrar proyectos (JS client-side)
- Tarjeta por proyecto con formulario (`POST /proyecto/seleccionar`) y botón "Ingresar al Proyecto"
- Badge Activo/Inactivo por proyecto
- Barra de progreso verde por proyecto

**Colores:**
- Fondo: `#f4f6f9` (gris claro)
- Texto principal: `#333`
- Texto secundario: `#666`
- Iconos: `#adb5bd`
- Botón entrar: `#19692c` / `#124d20` (verde AIA)
- Barra progreso: `bg-success` (Bootstrap verde)

**Estilo de diseño:**
- Grid de tarjetas (3 columnas lg, 2 md)
- Filtrado client-side
- Estado vacío con ilustración
- Navbar renderizado vía componente PHP (`NavbarComponent::render('proyectos')`)

**CSS inyectado:** Sí — estilos inline extensos para `.project-card`, `.card-header-project`, `.project-title`, `.badge-status`, `.meta-row`, `.btn-enter`, `.navbar-brand-aia`

---

### 6. Plan de Compras — PDC (`pdc/pdc.view.php`)

**Propósito:** Gestión del Plan de Compras — seguimiento de adquisiciones con timeline de 9 etapas de contratación.

**Dependencias externas:**
- Google Fonts: Montserrat, Inter
- jQuery 1.12.4, Popper, Bootstrap 4.3.1
- Handsontable 14.6.1 vendored para la tabla principal y `dt_definirContratos`
- jQuery UI 1.10.1
- Google Charts, AnyChart
- Select2 4.0.6

**CSS cargados:**
- `/css/styles.css`, `/css/buttons.css`, `/css/navbar.css`, `/css/access.css` (vía linksComunesHead2.js)
- `/css/tokens.css` (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- `$_SESSION['db']`, `$_SESSION['semana']`
- `$subcontratistas`, `$profesionales` (para dropdowns)
- `$categoriasCnc`

**Elementos interactivos:**
- Handsontable (`#dt_cliente`) para el seguimiento de etapas de contratación
- Modal `#modalContrato` — formulario masivo de edición con 9 etapas de proceso de contratación, cada una con duración, fechas teóricas/proyectadas/reales
- Modal `#modalDefinirContratos` — definir número de contratos por paquete
- Acciones por registro y modal de edición sobre la fuente Handsontable
- Filtros por columna desde los encabezados Handsontable
- Chips de leyenda PDC (filtrables)

**Colores:**
- Paleta naranja construcción AIA: `#b55211`, `#8b4011`, `#e87722`, `#f6c79b`, `#fbead9`
- Texto: `#24313a`, `#46535b`, `#6b7280`
- Estados PDC: púrpura (faltan datos), rojo (crítico), naranja (atrasado), amarillo (completado tarde), verde (completado a tiempo), azul (activo), gris (no iniciado)
- Fondo gradiente: `linear-gradient(180deg, #fafafa 0%, #f4f1ea 100%)`

**Estilo de diseño:**
- Dos grids Handsontable sin runtime DataTables
- Modal multi-sección con layout grid
- Chips de filtro/leyenda
- Notificaciones toast

**CSS del módulo:** `/css/pdc.css`, capas canónicas del design system y estilos acotados del modal PDC.

---

### 7. Contratos (`contratos/contratos.view.php`)

**Propósito:** Gestión de contratos — vincula actividades con paquetes de contratación (Suministro, Mano de Obra, Suministro e Instalación).

**Dependencias externas:**
- Stack legacy del módulo: jQuery, Bootstrap, DataTables, Select2, Google Charts, AnyChart

**CSS cargados:**
- Mismos que PDC (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- `$_SESSION['db']`, `$_SESSION['Max_Semana']`
- Array PHP `$contractSections` definido inline

**Elementos interactivos:**
- DataTable con 41 columnas (actividad, tipo contrato, 15 columnas de paquetes SI/S/MO × 5)
- Modal `#modalEditarContratos` — editar contrato con 3 secciones (Suministro, Mano de Obra, Suministro e Instalación), cada una con 5 pares de select recurso/paquete
- Select2 multi-select para recursos
- Botón editar por fila, edición inline

**Colores:** Usa estilos compartidos PDC/AIA + colores Bootstrap por defecto

**Estilo de diseño:**
- DataTable con fuente de datos AJAX
- Modal con inicialización dinámica de Select2
- Barra de cambio de módulo (Actividades / Contratos / Plan de Compras)

**CSS inyectado:** No significativo — usa estilos compartidos

---

### 8. Listado de Actividades (`listado-actividades/listadoActividades.view.php`)

**Propósito:** CRUD de actividades del proyecto.

**Dependencias externas:**
- Stack DataTable estándar + Select2 + jQuery UI datepicker

**CSS cargados:**
- Mismos que PDC (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- Query DB inline para opciones de dropdown `actividadInicio`
- `$_SESSION['db']`, `$_SESSION['Max_Semana']`

**Elementos interactivos:**
- DataTable con 10 columnas
- Modal `#modalNuevaActividad` — registrar nueva actividad (actividad, descripción, actividadInicio, fechaInicio, tipoContrato)
- Modal `#modalCargarExcel` — importación masiva vía descarga/subida CSV
- Modal `#modalEliminar` — confirmación de eliminación
- Edición inline por fila con date picker
- Control RBAC: solo roles A, D, OT pueden editar

**Colores:** Estándar AIA

**Estilo de diseño:**
- DataTable con edición inline
- Import/export CSV
- Edición controlada por RBAC

**CSS inyectado:** No significativo

---

### 9. Programación Semanal (`programacion-semanal/programacion_semanal.view.php`)

**Propósito:** Programación Semanal (Last Planner) — grilla principal de planificación basada en Handsontable.

**Dependencias externas:**
- Handsontable (full) + locale es-MX
- jQuery, jQuery UI

**CSS cargados:**
- `/css/handsontable-module.css`
- `/css/handsontable-header-global.css`
- `/css/styles.css`, `/css/navbar.css`, `/css/buttons.css`, `/css/access.css` (vía linksComunesHead2.js)

**JS cargados:**
- Handsontable + es-MX locale
- `/js/modules/programacion_semanal/stateMachine.js`
- `/js/modules/programacion_semanal/hot.js`
- `window.PS_HOT_OPTIONS` con subcontratistas, profesionales, categoriasCnc serializados

**Variables que recibe:**
- `$dbName`, `$semana`, `$permiso`
- `$subcontratistas`, `$profesionales`, `$categoriasCnc` (serializados a JS)

**Elementos interactivos:**
- Contenedor Handsontable (`#hot-container`) — grilla editable full-bleed
- Toolbar: Leyenda, Autoprogramar, Agregar Actividad, Confirmar Compromisos, Imprimir, Exportar CSV, Recargar
- Dropdown nav: Actividades, CNP, CNC, Calificación Proveedores
- Modal `#modal_leyenda_colores` — leyenda de colores
- Modal `#modal_cerrar_compromisos` — confirmar compromisos semanales
- Modal `#formulario_nuevo` — agregar actividad manual con bandeja de excepciones
- Modal `#modal_eliminar_actividad` — eliminar con razón CNP
- Modal `#modal_cnc_hot` — justificación CNC con `.aia-modal` y header Verde AIA
- Chips de leyenda de filtros (colapsables en móvil)

**Colores:**
- Celdas editables: tinte verde `rgba(34, 197, 94, 0.06)`
- Celdas readonly: tinte slate `rgba(148, 163, 184, 0.08)`
- Modal CNC: header Verde AIA `#1a5633`
- Dropdown: azul `#1e5ea8` estado activo

**Estilo de diseño:**
- Spreadsheet Handsontable (desktop) + vista tarjetas móvil
- State machine para fases semanales
- Drawer de filtros colapsable
- Menú dropdown de navegación

**CSS inyectado:** Sí — **462 líneas de CSS inline** con layout full-bleed de Handsontable, escalado responsive vía CSS custom property `--ps-hot-scale` con breakpoints de 1650px a 1100px, header actions grid, toolbar buttons, dropdown nav, status badges, estilos custom del modal CNC

---

### 10. CNP — Causas de No Programación (`programacion-semanal/CNP.view.php`)

**Propósito:** Seguimiento de por qué actividades no fueron programadas.

**Dependencias externas:**
- Stack DataTable estándar

**CSS cargados:**
- Mismos que PDC (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- `$semana`, `$dbName`, `$proyecto`, `$AIA_semana_confirmada`
- Query DB inline para dropdown `Responsable_AIA`

**Elementos interactivos:**
- DataTable con 11 columnas
- Edición inline: select Responsable_AIA, select Categoria_CNC, select CNC, textarea Observaciones
- Modal `#modalReprogramar` — confirmación de reprogramación
- Botones Editar + Reprogramar por fila (Reprogramar oculto si semana confirmada)

**Colores:**
- Coloreo de filas: clases `row-critical-delay`, `row-delayed`, `row-warning`
- Navegación dropdown: naranja/azul AIA estándar

**Estilo de diseño:**
- DataTable con edición inline
- Display condicional de botones según estado de confirmación de semana

**CSS inyectado:** Sí — **124 líneas** con estilos de dropdown nav (compartido con CNC, CIC), overrides de border-radius de botones, estilos de toolbar de filtros

---

### 11. CNC — Causas de No Cumplimiento (`programacion-semanal/CNC.view.php`)

**Propósito:** Seguimiento de por qué actividades comprometidas no fueron completadas.

**Dependencias externas:** Mismas que CNP

**CSS cargados:** Mismos que CNP

**JS cargados:** Mismos que CNP

**Variables que recibe:** Mismas que CNP

**Elementos interactivos:**
- DataTable con 9 columnas
- Edición inline: Categoria_CNC, CNC, Observaciones
- Sin botón reprogramar (a diferencia de CNP)

**Colores:** Mismo lógica de coloreo de filas que CNP

**Estilo de diseño:** Igual que CNP

**CSS inyectado:** Sí — idéntico a CNP (124 líneas)

---

### 12. CIC — Calificación Integral de Contratistas (`programacion-semanal/CIC.view.php`)

**Propósito:** Evaluación de subcontratistas en 4 dimensiones.

**Dependencias externas:**
- Stack DataTable estándar

**CSS cargados:**
- Mismos que CNP/CNC

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- `$semana`, `$dbName`, `$proyecto`

**Elementos interactivos:**
- DataTable con 90+ columnas (criterios individuales de evaluación)
- Modal `#modalcic_si` — evaluar contratista Suministro e Instalación con radio buttons (0%, 50%, 100%, N/A) en:
  - Calidad (3 criterios)
  - Administración del Contrato (6 criterios)
  - Gestión Socio-Ambiental (14 criterios)
  - SST (10+ criterios)
- Modales similares para Mano de Obra (mdo_*)

**Colores:** Estándar AIA

**Estilo de diseño:**
- Formularios de evaluación con radio buttons
- Modal multi-sección de evaluación

**CSS inyectado:** Sí — mismos estilos dropdown nav que CNP/CNC

---

### 13. Programa General (`programa-general/programa_general.view.php`)

**Propósito:** Programa General (Master Schedule) — visor de cronograma maestro basado en Handsontable.

**Dependencias externas:**
- Handsontable + locale es-MX
- Toastr para notificaciones

**CSS cargados:**
- `/css/handsontable-module.css`
- `/css/handsontable-header-global.css`
- `/css/styles.css`, `/css/navbar.css`, `/css/buttons.css`, `/css/access.css` (vía linksComunesHead2.js)

**JS cargados:**
- Handsontable + es-MX
- `/js/modules/programa_general/hot.js`
- `window.PGHotModule`

**Variables que recibe:**
- `$dbName`, `$semana`, `$permiso`

**Elementos interactivos:**
- Contenedor Handsontable
- Toolbar: Leyenda, Actualizar Ejecución, Descargar Corte, Exportar CSV, Recargar
- Chips de leyenda de filtros (9 estados: con-alerta-restricciones, debe-iniciar, actividad-futura, adelantada, en-curso, atrasada-crítica, atrasada, terminada, no-requerida)
- Modal `#modal_leyenda_colores`
- Toggle de filtros en móvil

**Colores:**
- Celdas editables: tinte verde `rgba(34, 197, 94, 0.06)`
- Celdas readonly: tinte slate `rgba(148, 163, 184, 0.08)`

**Estilo de diseño:**
- Spreadsheet Handsontable
- Chips de filtro con contadores
- Vista tarjetas móvil fallback

**CSS inyectado:** Sí — **167 líneas** con layout full-bleed de Handsontable, estilos de celdas editables/readonly, layout de columnas header con iconos changeType

---

### 14. Actualizar Programa General (`programa-general-actualizar/programaGeneralActualizar.view.php`)

**Propósito:** Actualización del Programa General — importar/actualizar cronograma maestro desde Excel.

**Dependencias externas:**
- Handsontable + TomSelect + HandsontableTomSelectEditor
- jQuery UI datepicker

**CSS cargados:**
- `/css/handsontable-module.css`
- `/css/handsontable-header-global.css`
- `/css/tom-select-premium-aia.css`
- `/css/styles.css`, `/css/navbar.css`, `/css/buttons.css`, `/css/access.css` (vía linksComunesHead2.js)

**JS cargados:**
- Handsontable + TomSelect + HandsontableTomSelectEditor
- `/js/modules/programa_actualizar/hot_actualizar.js`
- jQuery UI datepicker

**Variables que recibe:**
- `$dbName`, `$semana`, `$permiso`, `$maxSemana`, `$semanalConfirmada`
- Query DB inline para dropdown TomSelect de actividades de semana anterior

**Elementos interactivos:**
- Contenedor Handsontable
- Toolbar: Cargar desde Excel, Eliminar Actualización, Toggle Mostrando Pendientes
- Modal `#modalCargarExcel` — importación XLSX con date picker para proyectos nuevos
- Modal `#modalEliminarActualizacion` — confirmar eliminación
- Modal `#modalImportacionExitosa` — pantalla de éxito con estilos marca AIA
- Modal `#modal_Ejecutado_Teorico` — explicación de fórmula
- Modal `#modal_cantidad_ejecutada_error` — modal de advertencia
- Modal `#modal_semanal_confirmada` — aviso de programa bloqueado

**Colores:**
- Modal éxito: Verde AIA `#1a5633`, verde oscuro `#1a3c2a`, fondo verde claro `#d5e5db`
- Dropdown TomSelect: paleta naranja AIA

**Estilo de diseño:**
- Handsontable con editores TomSelect
- Modal de subida de archivo
- Modal de celebración de éxito

**CSS inyectado:** Sí — **36 líneas** con layout Handsontable y estilos del modal de éxito

---

### 15. Programación Intermedia (`programacion-intermedia/programacion_intermedia.view.php`)

**Propósito:** Programación Intermedia — planificación look-ahead con gestión de restricciones.

**Dependencias externas:**
- Handsontable + TomSelect + HandsontableTomSelectEditor
- jQuery UI (resolución de conflictos de tooltip)

**CSS cargados:**
- `/css/handsontable-module.css`
- `/css/handsontable-header-global.css`
- `/css/tom-select-premium-aia.css`
- `/css/styles.css`, `/css/navbar.css`, `/css/buttons.css`, `/css/access.css` (vía linksComunesHead2.js)

**JS cargados:**
- Handsontable + TomSelect + HandsontableTomSelectEditor
- `/js/modules/programacion_intermedia/stateMachine.js`
- `/js/modules/programacion_intermedia/hot.js`

**Variables que recibe:**
- `$dbName`, `$semana`, `$permiso`, `$subcontratistas`, `$profesionales`

**Elementos interactivos:**
- Contenedor Handsontable con editores TomSelect
- Toolbar: Leyenda, Descargar Corte, Exportar CSV, Recargar, Restricción Compartida, Recargar Listas, Seleccionar visibles, Limpiar selección
- Chips de leyenda de filtros (8 estados: blocked-overdue-critical, blocked-overdue, blocked-due, alert-1-week, alert-2-3-weeks, alert-4-6-weeks, execution-blocked, liberated-control)
- Modal `#modal_shared_constraint` — aplicar restricciones compartidas en lote
- Modal `#modal_leyenda_colores`

**Colores:**
- TomSelect: naranja `#b55211`, `#e87722`, `#fbead9`, `#f6c79b`
- Badges delta: verde arriba `#0e5c32`, rojo abajo `#9d321f`
- Relleno cobertura: gradiente azul `#2f7dd6` a `#1e62b2`
- Celdas editables: tinte verde `rgba(34, 197, 94, 0.06)`
- Celdas readonly: tinte slate `rgba(148, 163, 184, 0.08)`

**Estilo de diseño:**
- Handsontable con editores multi-select TomSelect inline
- Modal de operaciones en lote con preview KPI
- Selección de filas con aplicación de restricciones compartidas

**CSS inyectado:** Sí — **753 líneas** con estilos extensivos de editores inline TomSelect, modal de restricciones compartidas, tarjetas preview KPI, barras de progreso de cobertura, badges delta, estilos de celdas, indicador de flecha en celdas dropdown

---

### 16. Profesionales (`profesionales/profesionales.view.php`)

**Propósito:** Gestión de profesionales — grilla Handsontable editable en vivo para profesionales AIA.

**Dependencias externas:**
- Handsontable + locale es-MX
- Bootstrap/Popper estándar

**CSS cargados:**
- `/css/handsontable-module.css`
- `/css/handsontable-header-global.css`
- `/css/styles.css`, `/css/navbar.css`, `/css/buttons.css`, `/css/access.css` (vía linksComunesHead2.js)

**JS cargados:**
- Handsontable + es-MX
- `/js/cargarDatosGeneralesPagina2.js`

**Variables que recibe:**
- `$_SESSION['db']`, `$_SESSION['permiso']`

**Elementos interactivos:**
- Handsontable con columnas: ID, Nombre, Correo, Cargo (dropdown), Activo (checkbox), Acciones (eliminar)
- Vista tarjetas móvil con formulario para nueva entrada + tarjetas de edición
- Operaciones CRUD vía AJAX
- Validación: formato email, duplicados, validación de cargo
- RBAC: edición de identidad y estado activo controlados por fila

**Colores:**
- Tarjeta nueva entrada: borde punteado azul `#007aff`, fondo `#f9faff`
- Botón eliminar: circular rojo
- Tarjetas móvil: blancas con borde `#f0f0f0`

**Estilo de diseño:**
- Spreadsheet editable en vivo con auto-save
- Vista dual: desktop Handsontable + tarjetas móvil
- Controles de permiso a nivel de fila

**CSS inyectado:** Sí — **299 líneas** con estilos de protección Handsontable, vista tarjetas móvil, overrides de drawer nav móvil, optimización nav desktop

---

### 17. Subcontratistas (`subcontratistas/subcontratistas.view.php`)

**Propósito:** Gestión de subcontratistas — grilla editable en vivo para subcontratistas/proveedores.

**Dependencias externas:** Mismas que Profesionales

**CSS cargados:** Mismos que Profesionales

**JS cargados:** Mismos que Profesionales

**Variables que recibe:**
- `$_SESSION['db']`, `$_SESSION['permiso']`

**Elementos interactivos:**
- Handsontable: ID, Subcontratista, Correo, NIT, Alcance, Tipo Proveedor (dropdown), Activo (checkbox), Acciones
- Vista tarjetas móvil con formulario de nueva entrada
- Export CSV vía plugin export de Handsontable
- Validación: duplicados nombre/email/NIT
- Indicador de bloqueo para registros con dependencias

**Colores:** Mismos que Profesionales

**Estilo de diseño:** Mismo patrón que Profesionales

**CSS inyectado:** Sí — **297 líneas** (mismo patrón que Profesionales)

---

### 18. Indicadores (`indicadores/indicadores.view.php`)

**Propósito:** Dashboard de KPIs — embebe reportes de Google Data Studio.

**Dependencias externas:**
- Stack estándar + Google Charts, AnyChart

**CSS cargados:**
- Mismos que DataTable (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`

**Variables que recibe:**
- Variables de sesión para db, semana, proyecto, permiso

**Elementos interactivos:**
- Grupo de botones para cambiar entre reportes: Resumen, Programa General, Liberación de Restricciones, Programación Semanal, Plan de Compras, Calificación de Subcontratistas
- iframe de altura completa embebiendo reportes Data Studio
- Control RBAC: roles G, S, SG, C ven "Calificación de Subcontratistas" en lugar de otros

**Colores:** Estándar AIA

**Estilo de diseño:**
- Visor iframe con tabs
- Enrutamiento de reportes basado en RBAC

**CSS inyectado:** No

---

### 19. Control de Cambios (`control-cambios/controlCambios.view.php`)

**Propósito:** Gestión de Órdenes de Cambio — solicitudes formales de cambio.

**Dependencias externas:**
- Stack DataTable estándar
- numeral.js, jsPDF, HTML2Canvas, Tabulator

**CSS cargados:**
- Mismos que DataTable (vía linksComunesHead2.js)

**JS cargados:**
- `/js/linksComunesHead2.js`
- `/js/cargarDatosGeneralesPagina2.js`
- `/js/funcionesGenerales6.js`
- `/js/controlCambios.js`

**Variables que recibe:**
- Variables de sesión

**Elementos interactivos:**
- DataTable con 28 columnas + segunda fila de filtros con selects/date inputs
- Modal `#modalordenDeCambio` — formulario masivo de orden de cambio con secciones:
  - Información General (número orden, proyecto, director, fecha, solicitante radio buttons, prioridad radio buttons, tipo cambio checkboxes, responsable radio buttons)
  - Detalle del Cambio (justificación, descripción, impacto alcance, impacto cronograma con días/porcentaje, impacto presupuesto con desglose costos, impacto calidad, impacto riesgo, impacto recursos)
  - Aprobación (estado radio buttons, fecha definición)
  - Archivos de Soporte (tabla dinámica de adjuntos)
- Modal `#modalEliminar` — confirmación de eliminación
- Contadores de caracteres en textareas (límite 500 chars)
- jsPDF + HTML2Canvas para generación de PDF

**Colores:**
- Headers de sección del formulario: fondo oscuro `rgba(55,68,81,1)`, texto blanco
- Filas label/value alternadas: `bg-light` / `bg-white`

**Estilo de diseño:**
- Modal multi-sección con grupos radio/checkbox
- Textareas con contador de caracteres
- Tabla dinámica de adjuntos
- Generación de PDF

**CSS inyectado:** Mínimo — bloque style vacío (2 líneas)

---

## Módulos del Panel Admin (`admin/views/`)

### Layout Compartido (`admin/views/layouts/main.php`)

**Propósito:** Wrapper de layout principal para todas las páginas admin.

**Dependencias externas:**
- Inter font, Font Awesome, SweetAlert2, Toastr, DataTables
- AdminLTE 3.2
- jQuery, Bootstrap 4
- DataTables plugins completos (JSZip, pdfmake, buttons)

**CSS cargados:**
- `/admin/public/css/admin-custom.css`
- `/runtime/frontend-config.js`
- `/public/js/core/AiaAlertInterceptor.js`

**Variables que recibe:**
- `$title`, `$pageTitle`, `$breadcrumb`
- `$_SESSION['admin_user']['nombre']`, `$_SESSION['permiso']`

**Estructura:**
- Layout AdminLTE sidebar-mini
- Navbar superior con info de usuario + logout
- Sidebar izquierdo con menú: Dashboard, Proyectos, Usuarios
- Content wrapper renderiza variable `$content`
- Footer con info de versión

**Colores:**
- Sidebar oscuro AdminLTE (`sidebar-dark-primary`)
- Navbar blanco

**Estilo de diseño:** Shell de admin AdminLTE, setup de token CSRF para todo AJAX

---

### 1. Login Admin (`admin/views/pages/login.php`)

**Propósito:** Login del panel admin.

**Dependencias externas:** Mismas que login principal

**CSS cargados:**
- `/public/css/login-brand-unified.css` (compartido con login app)

**Variables que recibe:**
- `$title`, `$csrf_token`, `$inactive_notice`, `$reset_notice`

**Elementos interactivos:**
- Formulario login AJAX (sin recarga de página)
- Checkbox "Recordarme"
- Enlace forgot password

**Colores:** Mismo estilo branded que login app

**Estilo de diseño:** Tarjeta centrada, idéntico al login principal

**CSS inyectado:** No

---

### 2. Olvidé Contraseña Admin (`admin/views/pages/password-forgot.php`)

**Propósito:** Solicitud de reset de contraseña del admin.

**Variables que recibe:**
- `$title`, `$csrf_token`, `$message`, `$messageType`, `$emailValue`

**Colores:** Mismo estilo branded login

**CSS inyectado:** No

---

### 3. Reset Contraseña Admin (`admin/views/pages/password-reset.php`)

**Propósito:** Reset de contraseña admin con token.

**Variables que recibe:**
- `$title`, `$csrf_token`, `$message`, `$messageType`, `$isTokenValid`, `$token`

**Colores:** Mismo estilo branded login

**CSS inyectado:** No

---

### 4. Dashboard Admin (`admin/views/pages/dashboard.php`)

**Propósito:** Dashboard del admin con estadísticas del sistema.

**Dependencias externas:** Mismas que layout admin

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**JS cargados:**
- Inline JS (200 líneas) para toggles, filtros, diálogos

**Variables que recibe:**
- `$stats` con: `active_projects_count`, `total_projects`, `active_projects_list`, `db_size`, `total_tables`, `total_users`, `log_errors`, `password_stats`, `php_limits`, `backup_status`, `integrity_issues`, `orphan_tables`, `audit_logs`, `recent_errors`, `console_logs_enabled`
- `$csrf_token`

**Elementos interactivos:**
- 5 tarjetas de estadísticas (Proyectos Activos, DB Size, Usuarios, Errores, Password Changes)
- Tabla de info del servidor (PHP limits, backup status)
- Toggle switch: Console Logs global
- Botón: Forzar Cambio de Clave
- Botón: Respaldo Completo
- Lista de integrity issues (clicables)
- Lista de orphan tables con botón cleanup
- Tabla de audit log
- Tabla de errores del sistema con tabs de filtro (Todos/Errores/Rutas)

**Colores:**
- Colores AdminLTE small-box: bg-info, bg-success, bg-warning, bg-danger, bg-maroon
- Bordes de cards: card-primary, card-info, card-warning, card-secondary, card-success, card-danger

**Estilo de diseño:**
- Cards dashboard AdminLTE
- Toggle switches para settings
- Tablas de log filtrables

**CSS inyectado:** No — usa `admin-custom.css`

**JS inyectado:** Sí — 200 líneas inline para toggle de console logs, tabs de filtros, confirmaciones de cleanup, diálogos de detalle

---

### 5. Usuarios — Lista (`admin/views/pages/users/index.php`)

**Propósito:** Listado de gestión de usuarios.

**Dependencias externas:**
- Select2 + Select2 Bootstrap4 theme
- DataTables con plugin de filtro custom

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$users` array

**Elementos interactivos:**
- DataTable con header sticky
- Toggle switches: "Mostrar inactivos", "Mostrar sin proyectos"
- Por fila: toggle switch activo, badge de estado, badge de conteo de proyectos, badge "Clave pendiente"
- Botón editar por fila
- Botón "Nuevo Usuario"

**Colores:**
- Badges de rol con `RoleManager::getRoleColor()`
- Activo: `badge-success`, Inactivo: `badge-secondary`
- Pendiente password: `badge-warning`

**Estilo de diseño:**
- DataTable con filtrado custom
- Toggles de estado inline con AJAX

**CSS inyectado:** Sí — **20 líneas** con estilos de header sticky y `.user-row-inactive` opacity

---

### 6. Usuarios — Crear (`admin/views/pages/users/create.php`)

**Propósito:** Crear nuevo usuario con asignaciones de proyecto.

**Dependencias externas:**
- Select2 + Select2 Bootstrap4 theme

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$projects`, `$roles`, `$csrf_token`

**Elementos interactivos:**
- Formulario usuario: nombre, email, cargo (Select2 con AJAX), usuario (auto-generado), contraseña (con botones generar + toggle)
- Filas dinámicas de asignación de proyecto (agregar/eliminar)
- Cada asignación: select proyecto + select rol
- Verificación de unicidad en tiempo real (AJAX debounced)
- Generador de contraseñas (12 chars, mixta)
- Modal de éxito con copia de credenciales lista para WhatsApp

**Colores:** AdminLTE estándar

**Estilo de diseño:**
- Filas de formulario dinámicas
- Autocomplete AJAX + validación
- Generador de contraseña con toggle de visibilidad

**CSS inyectado:** Sí — **14 líneas** con fix de altura Select2 Bootstrap4 y estilos `.assignment-row`

---

### 7. Usuarios — Editar (`admin/views/pages/users/edit.php`)

**Propósito:** Editar usuario existente.

**Dependencias externas:** Mismas que crear usuario

**CSS cargados:** Mismos que crear

**Variables que recibe:**
- `$user`, `$projects`, `$roles`, `$assignments`, `$csrf_token`

**Elementos interactivos:**
- Mismos campos que crear, pre-poblados
- Toggle activo + toggle cambio forzado de contraseña
- Filas dinámicas de asignación con eliminación server-side
- Botón "Revocar todos los permisos"

**Colores:** AdminLTE estándar

**Estilo de diseño:** Igual que crear

**CSS inyectado:** Sí — mismos 14 líneas que crear

---

### 8. Proyectos — Lista (`admin/views/pages/projects/index.php`)

**Propósito:** Listado de proyectos.

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$projects`, `$csrf_token`

**Elementos interactivos:**
- DataTable con header sticky
- Toggles por fila: Activo, Acceso, PDC Activo
- Botones de acción: Members, Backup (SQL), Edit, Delete
- Flujo delete con auto-backup luego eliminar

**Colores:**
- Activo: `badge-success`, Inactivo: `badge-danger`

**Estilo de diseño:**
- DataTable con toggles inline
- Delete en dos pasos con backup

**CSS inyectado:** Sí — **15 líneas** con sizing de tabla

---

### 9. Proyectos — Crear (`admin/views/pages/projects/create.php`)

**Propósito:** Crear nuevo proyecto.

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$csrf_token`

**Elementos interactivos:**
- Formulario simple: nombre, área (Construcción/PI), fecha inicio/fin línea base, costo retraso, URL cambios, toggles para activo/acceso/pdc_activo

**Colores:** `card-success`

**Estilo de diseño:** Formulario simple AdminLTE

**CSS inyectado:** No

---

### 10. Proyectos — Editar (`admin/views/pages/projects/edit.php`)

**Propósito:** Editar proyecto.

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$project`, `$csrf_token`

**Elementos interactivos:** Mismos que crear, pre-poblados + campo base_datos

**Colores:** `card-info`

**Estilo de diseño:** Formulario simple AdminLTE

**CSS inyectado:** No

---

### 11. Proyectos — Miembros (`admin/views/pages/projects/members.php`)

**Propósito:** Gestionar miembros de un proyecto.

**CSS cargados:**
- `/admin/public/css/admin-custom.css`

**Variables que recibe:**
- `$project`, `$members`, `$availableUsers`, `$roles`, `$csrf_token`

**Elementos interactivos:**
- Izquierda: tabla de miembros con botón eliminar
- Derecha: formulario agregar miembro (select usuario + select rol)
- Sugerencia inteligente de rol basada en normalización de cargo
- Diálogo de confirmación de eliminación

**Colores:**
- Badges de rol con `RoleManager::getRoleColor()`

**Estilo de diseño:**
- Layout dos paneles (lista + formulario)
- Auto-sugerencia inteligente de rol

**CSS inyectado:** No — JS inline para normalización de cargo, lógica de sugerencia de rol, inicialización Select2

---

## Hoja de Estilos CSS (`public/css/`)

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `styles.css` | ~5,779 | Hoja maestra. CSS Cascade Layers (`@layer reset, theme, base, layout, components, utilities`). Sistema de diseño Apple-inspired con variables CSS, paleta PDC unificada (7 estados), clases de coloreo de filas, vista tarjetas móvil, overrides de componentes, estilos de formularios PDC y Control Cambios |
| `buttons.css` | ~336 | Sistema de sizing escalable para toolbars. Variables CSS unificadas para altura, font-size, padding, gap. Layouts flex para botones de acción y filtros. Colores de indicadores por estado |
| `tokens.css` | ~129 | Tokens de diseño AIA en espacio de color OKLCH. Verde corporativo (5 sombras), Naranja construcción (5 sombras), Azul arquitectura (5 sombras), Aqua proyectos (5 sombras), Alertas (4 sombras), Advertencias (4 sombras), Tipografía y grises, Backgrounds (linen, alabaster, beige), Spacing, border-radius, z-index, opacity, transitions, shadows |
| `navbar.css` | ~517 | Estilos de navegación unificada AIA. Desktop nav (>=1400px), Tablet/intermedio (768-1399px), Premium drawer (<1400px) con sidebar slide-over iOS-style. Centro de notificaciones. Overrides unlayered para vencer Bootstrap CDN |
| `dark-mode.css` | ~153 | Implementación modo oscuro AIA. Overrides de variables para `body.dark-mode`: surface `#1C1C1E`, card `#2C2C2E`, text `#F4F1EA`. Overrides de navbar, drawer, project selector, formularios, tablas |
| `login-brand-unified.css` | ~227 | Estilos modernos de login/registro. Tokens OKLCH, gradientes radiales, tarjeta con border-radius grande, botón marca con gradiente y text-shadow, focus states, reduced-motion, responsive con container queries |
| `handsontable-module.css` | ~393 | Layout y protección de Handsontable. Layout flex full-height, sizing de `#hot-container`, vista tarjetas móvil, botón eliminar circular, loading overlay, estilos Select2 multiple para HOT, overrides drawer móvil |
| `handsontable-header-global.css` | ~108 | Overrides de layout de header Handsontable (unlayered). Texto centrado, layout flex column para header (colHeader sobre icono changeType), estilos de icono changeType (13px square) |
| `tom-select-premium-aia.css` | ~143 | Estilos TomSelect para Handsontable. Paleta naranja AIA para control, chips, dropdown, opciones. Botón clear al fondo. Estilo de opción "create action" |
| `access.css` | ~58 | Utilidades de accesibilidad. `.sr-only`, spacing utilities (`.btn-action-gap`, `.nav-link-custom`, `.p-xs`, `.px-sm`, `.py-sm`, `.m-0`), utilidad de tabla responsive |

---

## Scripts JavaScript (`public/js/`)

### Core

| Archivo | Propósito |
|---------|-----------|
| `core/ContextManager.js` | Gestiona contexto de la aplicación (proyecto, semana, permisos) |
| `core/SessionTimeoutManager.js` | Detección de timeout de sesión y redirect |
| `core/AiaAlertInterceptor.js` | Intercepta alerts nativos, reemplaza con AIA.Notice (wrapper SweetAlert2) |
| `linksComunesHead2.js` | Carga navbar, CSS común, datos de sesión, inicializa contexto |
| `cargarDatosGeneralesPagina2.js` | Carga navegación, contexto de página, llama callback `cargaParametros()` |
| `funcionesGenerales6.js` | Gestión de semanas (nueva semana, eliminar semana), utilidades comunes |
| `rbac_capabilities.js` | Verificación de permisos RBAC (canEditLps, canManageWeeks, etc.) |

### Componentes

| Archivo | Propósito |
|---------|-----------|
| `components/notifications.js` | Lógica del centro de notificaciones |

### Módulos Handsontable

| Archivo | Propósito |
|---------|-----------|
| `modules/programa_general/hot.js` | Módulo PG Handsontable: columnas, carga datos, auto-save, renderers |
| `modules/programa_actualizar/hot_actualizar.js` | Módulo Handsontable de actualización de programa |
| `modules/programacion_semanal/hot.js` | Módulo PS Handsontable: columnas, datos, lógica commit, modal CNC |
| `modules/programacion_semanal/stateMachine.js` | State machine de fases semanales PS |
| `modules/programacion_intermedia/hot.js` | Módulo PI Handsontable: columnas, editores TomSelect, restricciones compartidas |
| `modules/programacion_intermedia/stateMachine.js` | State machine del módulo PI |

### Editores Handsontable

| Archivo | Propósito |
|---------|-----------|
| `HandsontableTomSelectEditor.js` | Editor custom de celda HOT usando TomSelect |

### Utilidades

| Archivo | Propósito |
|---------|-----------|
| `datatable-height-manager.js` | Cálculo dinámico de altura de DataTable |
| `global-table-align.js` | Fixes globales de alineación de tablas |
| `mobile-table-fix.js` | Inyección de labels para vista tarjetas móvil |
| `tablet-viewport-scale.js` | Escalado de viewport para tablets |

---

## Patrones Compartidos

### Patrón de Carga de Vistas DataTable

```
linksComunesHead2.js
  └─→ carga CSS común (styles.css, buttons.css, navbar.css, tokens.css, access.css)
  └─→ carga jQuery, Bootstrap, DataTables, Select2, Google Charts, AnyChart
  └─→ llama cargarDatosGeneralesPagina2.js
        └─→ inyecta navbar dinámicamente
        └─→ llama callback cargaParametros()
  └─→ llama funcionesGenerales6.js (gestión semanas)
```

### Patrón de Carga de Vistas Handsontable

```
linksComunesHead2.js
  └─→ carga CSS común + handsontable-module.css + handsontable-header-global.css
  └─→ carga jQuery, jQuery UI
  └─→ carga Handsontable + locale es-MX
  └─→ llama módulo JS específico (hot.js + stateMachine.js)
```

### Patrón de Colores de Estado (7 estados PDC)

Todos los módulos que muestran estados comparten las mismas 7 clases CSS:

| Clase CSS | Estado | Color |
|-----------|--------|-------|
| `.pdc-missing-data` | Faltan datos | Púrpura `#c084fc` |
| `.pdc-critical-delay` | Atraso crítico | Rojo `#dc2626` |
| `.pdc-delayed` | Atrasado | Naranja `#e87722` |
| `.pdc-completed-delayed` | Completado tarde | Amarillo `#d4a017` |
| `.pdc-completed-ontime` | Completado a tiempo | Verde `#69b578` |
| `.pdc-active` | En curso | Azul `#4a81bd` |
| `.pdc-not-started` | No iniciado | Gris `#c7cdd4` |

### Patrón de Vista Dual (Desktop/Móvil)

Las vistas Handsontable implementan vista dual:
- **Desktop (>=769px):** Handsontable full-bleed con toolbar superior
- **Móvil (<=768px):** Tarjetas con formulario, navegación drawer slide-over

---

## Notas de Migración

### Vistas Candidatas a Eliminación

| Vista | Razón | Reemplazo |
|-------|-------|-----------|
| `auth/registrate.view.php` | Creación de usuarios centralizada en admin | `admin/views/pages/users/create.php` |
| `stylesLogin.css` (retirada el 14 de julio de 2026) | Estilos legacy duplicados, ya sin consumidores frontend | `login-brand-unified.css` y tokens actuales |

### Vistas con CSS Inline Excesivo

| Vista | Líneas CSS Inline | Recomendación |
|-------|-------------------|---------------|
| `pdc/pdc.view.php` | 666 | Extraer a `pdc-module.css` |
| `programacion-intermedia/programacion_intermedia.view.php` | 753 | Extraer a `pi-module.css` |
| `programacion-semanal/programacion_semanal.view.php` | 462 | Ya parcialmente en `handsontable-module.css` |
| `profesionales/profesionales.view.php` | 299 | Extraer a `profesionales-module.css` |
| `subcontratistas/subcontratistas.view.php` | 297 | Extraer a `subcontratistas-module.css` |

### Deuda Técnica Identificada

1. **Font Awesome duplicado** en `registrate.view.php` (v5.11.2 + v5.7.1)
2. **Dos versiones de jQuery** coexisten (1.12.4 en DataTable views, 3.6.0 en login)
3. **Bootstrap 4.3.1** en DataTable views vs **4.6.1** en login
4. **CSS inline masivo** en 5+ vistas que debería extraerse a archivos dedicados
5. **Sin layout compartido** en app principal — cada vista es un HTML completo (vs admin que sí usa layout)
