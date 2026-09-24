---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-09-23
areas: [arquitectura, lps, pdc, bi, admin, rbac]
tags: [leer-antes-de-tocar]
fuente: public/index.php, admin/public/index.php, pdc-app/, ct-app/, scripts/wiki-arquitectura.modulos.mjs
resumen: "Inventario canónico de los 12 dominios y submódulos de la aplicación: rutas, herramientas satélite, flujos y arquitectura técnica"
---
# Mapa Canónico de Módulos y Submódulos

Este documento define la taxonomía completa, real y exhaustiva de todas las superficies de la aplicación Last Planner AIA, desglosando cada módulo, sus submódulos funcionales, pantallas satélite, herramientas de gestión y controladores.

---

## 1. Núcleo, Acceso y Autenticación (`autenticacion`)

Gestiona el ciclo de vida de la sesión, recuperación de credenciales y accesos protegidos. Servido por el shell React SPA (`frontend/src/shell/auth/`) con fallback y adaptadores API en PHP.

- **1.1. Inicio de Sesión (`/login`, `/`)**: Punto de entrada con selector de credenciales, autenticación multi-rol (A, D, R, V, OT, etc.) y paridad visual con design system tokens (`PantallaLogin.tsx`, `AuthApiController`).
- **1.2. Recuperación de Contraseña (`/password/forgot`, `/app/password/forgot`)**: Solicitud de enlace temporal vía email con token CSRF (`PantallaRecuperarClave.tsx`, `PasswordRecoveryApiController`).
- **1.3. Restablecimiento de Contraseña (`/password/reset`, `/app/password/reset`)**: Validación criptográfica de token y actualización de clave (`PantallaRestablecerClave.tsx`, `PasswordResetApiController`).
- **1.4. Cambio Obligatorio de Clave (`CambioClaveObligatorio.tsx`)**: Intercepción modal forzada cuando la cuenta tiene activo el flag `must_change_password`.
- **1.5. Puerta de Servicio de Desarrollo (`/dev/entrar`)**: Candado de acceso rápido para desarrollo y QA con cuentas sembradas (`test.A`, `test.R`, `test.V`). Solo disponible con `DEV_DOOR=1` (`DevDoorController`).
- **1.6. Ruta Secreta de Acceso en Mantenimiento (`/_aia/operacion/7f3c9b`)**: Acceso administrativo de emergencia durante ventanas de mantenimiento (`MaintenanceMode::SECRET_PATH`, `MaintenanceLoginController`).

Ver detalle en [[autenticacion]].

---

## 2. Contexto de Proyecto y Shell (`selector-de-proyectos`, `nucleo-y-runtime`)

Gobernanza del entorno de trabajo, aislamiento por obra y persistencia de estado.

- **2.1. Selector de Proyectos (`/proyectos`, `/app/proyectos`)**: Grid de tarjetas de obras asignadas al usuario, filtro de búsqueda client-side, indicador de avance y selector activo (`ProjectSelectorController`, `SelectorProyectos.tsx`).
- **2.2. AppShell & Navegación Global (`BarraLateral.tsx`)**: Menú lateral adaptable (rail de escritorio, drawer colapsable para tablet y móvil), enlaces filtrados por RBAC.
- **2.3. Gestor de Contexto Temporal de Semanas (`ContextoSemana.tsx`, `/context/week`)**:
  - Selector de semana operativa activa.
  - **Creación de nueva semana** (`/api/context/weeks/create`).
  - **Eliminación de última semana** (`/api/context/weeks/delete-last`).
- **2.4. Conmutador de Tema Claro / Oscuro (`ConmutadorTema.tsx`)**: Alternancia instantánea de tokens OKLCH sobre `html[data-theme]` con persistencia local.
- **2.5. Menú de Cuenta y Perfil (`MenuCuenta.tsx`)**: Datos de usuario, rol vigente en la obra y cierre de sesión seguro.

Ver detalle en [[selector-de-proyectos]] y [[nucleo-y-runtime]].

---

## 3. Programa General y Actualizador de Cronogramas (`programa-general`, `cronograma`)

> [!important] Dos herramientas independientes, no dos vistas
> `programa-general` es el cronograma maestro y contractual de la obra. `programa-general-actualizar` es el importador y actualizador a nuevas versiones desde MS Project / Excel. Ver [[programa-general-actualizar-es-otra-herramienta]].

- **3.1. Programa General — Cronograma Maestro (`/programa-general`)**:
  - Grilla Handsontable full-bleed con estructura jerárquica EDT/WBS.
  - Fechas de línea base contractual, fechas reprogramadas, avance teórico y real ejecutado.
  - Matriz de chips de filtrado por 9 estados operativos (alerta restricciones, debe iniciar, adelantada, en curso, atrasada crítica, atrasada, terminada, no requerida).
  - Control de edición temporal por RBAC: edición de semanas pasadas bloqueada salvo para roles Admin y Director (`canEditPastGeneralProgram`).
  - Estandarización y preview de Breadcrumbs EDT (`/api/pg/breadcrumb-preview`, `/api/pg/breadcrumb-estandarizar`).
- **3.2. Actualizador del Programa General — Cargue de Reprogramaciones (`/programa-general-actualizar`)**:
  - Herramienta para importar nuevas versiones del cronograma maestro desde Excel/MS Project (`/api/general/import`).
  - Grilla Handsontable interactiva (`hot_actualizar.js`) con selector TomSelect para emparejar actividades previas vs nuevas.
  - Comparador visual de cronograma base vs nuevo borrador de reprogramación.
  - Motor de auto-asociación inteligente (`/api/general/auto-associate`).
  - Confirmación y aplicación en lote (`/api/general/update-batch`) o descarte/eliminación de borrador (`/api/general/delete-update`).

Ver detalle en [[programa-general]] y [[cronograma]].

---

## 4. Programación Intermedia (`programacion-intermedia`)

Planificación lookahead a 6 semanas y liberación temprana de restricciones (LPS).

- **4.1. Programación Intermedia de 6 Semanas (`/programacion-intermedia`)**:
  - Visor de actividades programadas dentro de la ventana móvil de 6 semanas hacia adelante.
  - Grilla Handsontable con editores multi-select TomSelect (`hot.js`) para asignar responsables y tipos de restricción.
  - Matriz de 8 estados de restricción (atraso crítico, atrasada, en fecha, alertas 1 sem / 2-3 sem / 4-6 sem, bloqueada ejecución, liberada).
  - Indicadores visuales de holgura, badges delta y barra de progreso de cobertura de restricciones.
- **4.2. Gestor de Restricciones Compartidas en Lote (`#modal_shared_constraint`)**:
  - Modal masivo para asignar o levantar restricciones de forma simultánea sobre múltiples actividades seleccionadas, con preview de impacto.

Ver detalle en [[programacion-intermedia]].

---

## 5. Programación Semanal y Análisis de Causa Raíz (`programacion-semanal`)

Plan de Trabajo Semanal (Weekly Work Plan - WWP) y ciclos de aprendizaje lean.

- **5.1. Programación Semanal — Plan Semanal de Trabajo (`/programacion-semanal`)**:
  - Grilla Handsontable de compromisos semanales asumidos por los responsables de obra.
  - Motor de Auto-programación (`/api/semanal/auto-program`) y consulta de bitácora (`/api/semanal/auto-program-log`).
  - Máquina de estados de la semana (Planificación → Compromiso → Seguimiento → Cierre).
  - Modal de Cierre y Confirmación de Compromisos (`#modal_cerrar_compromisos`).
  - Modal de Monitoreo de Cambios (`_changeMonitorModal.php`).
  - Modal de Reapertura Controlada de Semana (`modal_reabrir.php`, `/api/semanal/reabrir`).
- **5.2. Submódulo CNP — Causas de No Programación (`/programacion-semanal/cnp`)**:
  - Registro de actividades listas en la intermedia que no fueron llevadas al plan semanal.
  - Asignación de categorías de no programación y flujo de reprogramación (`/api/cnp/reprogramar`).
- **5.3. Submódulo CNC — Causas de No Cumplimiento (`/programacion-semanal/cnc`)**:
  - Registro de causas raíz de compromisos semanales no cumplidos al cierre de la semana (mano de obra, materiales, diseño, clima, etc.) para cálculo del PAC (Porcentaje de Asignaciones Cumplidas).
- **5.4. Submódulo CIC — Calificación Integral de Contratistas (`/programacion-semanal/cic`)**:
  - Matriz de evaluación del desempeño de subcontratistas en 4 ejes: Calidad, Administración de Contrato, Gestión Socio-Ambiental y SST.

Ver detalle en [[programacion-semanal]], [[submodulo-cnp]], [[submodulo-cnc]] y [[submodulo-cic]].

---

## 6. Plan de Compras v2 (`plan-de-compras`)

Cadena de abastecimiento y adquisiciones críticas enlazadas al cronograma de obra. Implementado como SPA React en `pdc-app/` con sub-router por hash y APIs dedicadas.

- **6.1. Importador y Comparador de Presupuesto (`#/ensamble/importar`, `VisorPresupuesto.tsx`, `ComparativoPresupuesto.tsx`)**:
  - Carga de presupuestos Excel (`/plan-compras/api/presupuesto/preview` y `/confirmar`).
  - Visor del árbol jerárquico WBS de insumos y cantidades.
  - Comparador analítico entre versiones de presupuesto y reporte de variaciones de insumos/costos.
- **6.2. Maestro de Insumos y Clasificación de Equipos (`#/ensamble/maestro`, `MaestroInsumos.tsx`)**:
  - Catálogo de insumos de compra, homologación con el maestro corporativo.
  - Cola de clasificación de equipos (alquilados vs comprados).
- **6.3. Paquetes de Contratación y Auto-asignador (`#/ensamble/paquetes`, `PaquetesContratacion.tsx`, `PaquetesAsistente.tsx`)**:
  - Agrupación de insumos en paquetes de licitación y compra.
  - Asistente semi-automático de auto-asignación (`/plan-compras/api/paquetes/auto-asignar`).
- **6.4. Plan de Fechas, Anclaje y Simulación (`#/plan/fechas`, `PlanFechas.tsx`)**:
  - Cronograma dinámico de adquisiciones anclado a actividades del Programa General.
  - Simulación y aplicación de reprogramaciones ante desplazamientos de obra (`/plan/reprogramacion/simular` y `/aplicar`).
- **6.5. Configuración de Pasos y Duraciones (`#/plan/configuracion`, `PasosContratacion.tsx`)**:
  - Pipeline de etapas de contratación configurable por proyecto.
  - Copia de configuraciones entre proyectos y editor de duraciones estándar.
- **6.6. Seguimiento Operativo, Subpaquetes y Flujo de Caja (`#/seguimiento`, `Seguimiento.tsx`, `SubpaquetesPanel.tsx`)**:
  - Tablero de seguimiento de hitos y vencimientos de contratación.
  - Partición de paquetes en subpaquetes de obra.
  - Proyección mensual de flujo de caja y exportación en tiempo real a CSV (`/flujo-caja.csv`).

Ver detalle en [[plan-de-compras]].

---

## 7. Directorios Maestros de Obra (`profesionales`, `subcontratistas`)

Mantenimiento de los actores clave del proyecto.

- **7.1. Directorio de Profesionales AIA (`/profesionales`)**:
  - Grilla Handsontable editable en vivo para profesionales de obra, correos y cargos.
  - Adaptación móvil por tarjetas.
- **7.2. Directorio de Subcontratistas y Proveedores (`/subcontratistas`)**:
  - Registro de empresas contratistas, NIT, alcances técnicos, tipología y estados de bloqueo.

Ver detalle en [[profesionales]] y [[subcontratistas]].

---

## 8. Integración, Control de Cambios y Descarga de Reportes (`control-de-cambios`, `integracion`)

Gestión contractual y reportes ejecutivos descargables.

- **8.1. Control de Cambios Contractual (`/control-cambios`)**:
  - Registro y seguimiento de Órdenes de Cambio (ODC) de alcance, plazo y costo.
  - Formulario multi-sección con cálculo de días de impacto y desglose presupuestal.
  - Generación formal de PDF con firmas y adjuntos.
- **8.2. Centro de Descarga y Generación de Reportes (`/reportes/{tipo}`)**:
  - Descargas instantáneas en Excel: Corte de Programación, Matriz de Restricciones, Compromisos Semanales, Consolidado ODC.
  - Procesadores asíncronos y cron: Curva S, General, Restricciones General, PDC, Subcontratistas y Run-all.

Ver detalle en [[control-de-cambios]] e [[integracion]].

---

## 9. Torre de Control BI e Indicadores (`torre-de-control-bi`, `indicadores`)

Inteligencia de negocio y tableros analíticos de supervisión.

- **9.1. Tablero de Mando Ejecutivo (`/bi/control-tower`, app React en `ct-app/`)**:
  - Semáforo de salud global del proyecto (`Semaforo.tsx`).
  - Titular y KPIs ejecutivos (`Titular.tsx`).
- **9.2. Gestión y Radar de Restricciones BI (`ct-app`)**:
  - Pareto de restricciones (`Pareto.tsx`).
  - Panel de gestión rápida y liberación de restricciones (`PanelGestion.tsx`).
  - Alarma de restricciones huérfanas (`AlarmaHuerfanas.tsx`).
  - Trazabilidad y linaje de datos (`Linaje.tsx`).
- **9.3. Vistas Analíticas Dedicadas de BI (`views/bi/`)**:
  - BI Programa General (`/bi/programa-general`: cumplimiento, atrasos, radar, detalle CNP/CNC).
  - BI Programación Intermedia (`/bi/intermedia`).
  - BI Programación Semanal (`/bi/semanal`).
  - BI Plan de Compras (`/bi/pdc`).
  - BI Subcontratistas (`/bi/contratistas` o CIC).
  - BI Responsables (`/bi/responsables` o CIP).
  - BI Curva S Agregada (`/bi/curva-s`).
- **9.4. Indicadores LPS Tradicionales (`/indicadores`)**:
  - Ficha resumen con medidores circulares AnyChart e iframe integrado de PowerBI con ajuste de viewport.

Ver detalle en [[torre-de-control-bi]] e [[indicadores]].

---

## 10. Comunicación Contextual, Crisis y Notificaciones (`escalamientos-y-crisis`)

Colaboración en tiempo real y atención de incidentes de obra.

- **10.1. Cajón Contextual LPS (`CajonContextualLps.tsx`)**: Panel lateral deslizante por actividad para hilos de discusión y notas técnicas.
- **10.2. Protocolo SOS y Cierre de Crisis (`AccionesSos.tsx`, `CierreCrisis.tsx`)**: Declaración de alertas críticas en obra, escalamiento jerárquico a directores y protocolo de cierre con mitigación.
- **10.3. Tablero de Escalamientos del Proyecto (`/dashboard/escalamientos`)**: Supervisión centralizada de crisis activas por proyecto.
- **10.4. Centro de Notificaciones del Sistema (`BandejaNotificaciones.tsx`)**: Bandeja de avisos y notificaciones de actividad con conteo en tiempo real.

Ver detalle en [[escalamientos-y-crisis]].

---

## 11. Panel de Administración Central (`panel-admin`)

Mini-aplicación aislada en `admin/` para gobernanza global de la plataforma.

- **11.1. Dashboard Administrativo (`/admin/dashboard`)**: Métricas de salud de BD, logs de error, forzado masivo de cambio de clave, toggle de console logs y switch global de mantenimiento.
- **11.2. Gestión de Usuarios (`/admin/usuarios`, `/crear`, `/editar`)**: Altas, edición, generador de claves seguras, asignación de proyectos y roles RBAC.
- **11.3. Gestión de Proyectos (`/admin/proyectos`, `/crear`, `/miembros`)**: Configuración de proyectos, líneas base, costo de retraso, sugerencia automática de roles por cargo, respaldos SQL y limpieza de datos huérfanos.
- **11.4. Motor de Matching y Catálogo de Familias (`/admin/matching/config`, `/matching/family-catalog`)**: Familias de insumos, gestión de alias, reglas de homologación de actividades y elementos contractuales, importador/exportador.
- **11.5. Interruptores de Módulos / Feature Flags (`/admin/modulos`)**: Activación y desactivación de módulos por proyecto (`general_flags`).
- **11.6. Mantenimiento y Purga del Plan de Compras (`/admin/pdc/limpieza`)**: Diagnóstico de tablas de compras y purga controlada.

Ver detalle en [[panel-admin]].

---

## 12. Herramientas Internas y Laboratorio (`laboratorio-design-system`, `legado`)

- **12.1. Laboratorio del Design System (`/internal/design-system`)**: Catálogo de componentes, tipografía, paletas de tokens, adaptadores de terceros (Handsontable, TomSelect, Select2, DataTables, SweetAlert2) y fixtures operacionales ejecutables.
- **12.2. Carril Legado (`/legacy/*`)**: Scripts procedurales de compatibilidad histórica aislados bajo Front Controller.

Ver detalle en [[laboratorio-design-system]] y [[legado]].
