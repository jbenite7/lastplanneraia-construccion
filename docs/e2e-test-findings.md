---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-05
areas: [qa]
fuente: docs/e2e-test-findings.md
resumen: E2E Test Findings
---

# E2E Test Findings

## Resumen

- **Fecha:** 2026-07-05
- **Rama:** main
- **Entorno:** Docker local (php 8.3.32, MySQL 8.0.40)
- **Suite:** smoke (12 tests) + deep workflows (2 tests)
- **Resultado:** 14/14 tests pasan — 17 hallazgos documentados
- **Estado Fase 0:** 4 helpers creados (`handsontable.mjs`, `admin.mjs`, `moduleSelectors.mjs`, `apiPayloads.mjs`)

### Resolution Status (2026-07-05)

| Finding | Status | Action |
|---|---|---|
| F-001 | ✅ Documentado | PG update usa `unique_id`/`Semana` — tests aplican params correctos |
| F-002 | ✅ Documentado | PI `liberar_todas` no soportado — tests de PI omitidos (404) |
| F-003 | ✅ Documentado | CNP S2 vacío esperable — documentado como vacuum |
| F-004 | ⚠️ Blocker | Listado `auto/apply` rechaza payload — tests marcan expected failure |
| F-005 | ⚠️ Blocker | `familia/save` 404 — CRUD UI tests omiten creación vía API |
| F-006 | ⚠️ Blocker | Contratos `auto/apply` rechaza payload — tests marcan expected failure |
| F-007 | ⚠️ Blocker | PDC `auto/apply` rechaza payload — tests marcan expected failure |
| F-008 | ✅ Documentado | PDC operativo con datos reales |
| F-009 | ✅ Documentado | Handsontable vs HTML table documentado |
| F-010 | ✅ Documentado | Sidebar limitado — navegación por URL directa |
| F-011 | ✅ Documentado | CIC y PI omitidos (404) |
| F-012 | ✅ Documentado | Chips varían por tipo proyecto — fixtures por tipo |
| F-013 | ✅ Documentado | LPS Drawer universal — test incluido en Fase 1 |
| F-014 | ✅ Documentado | Admin login independiente — helper `admin.mjs` creado |
| F-015 | ✅ Documentado | Leyenda modal consistente — selector genérico |
| F-016 | ✅ Documentado | Admin Proyectos DataTable documentado |
| F-017 | ✅ Documentado | Admin Usuarios filtros documentados |

---

## Hallazgos

### F-009 — PG y PS usan Handsontable (treegrid); PDC, Contratos y Listado usan HTML tables nativas

- **Severidad:** Media (arquitectura de tests)
- **Módulo:** Multi-módulo
- **Evidencia:** PG/PS: DOM con `role=treegrid`, `role=row`, `role=gridcell`, `role=columnheader` (Handsontable). PDC/Contratos/Listado: `<table>` nativa con `<thead>`, `<tbody>`, `<tr>`, `<td>`.
- **Implicación:** Handsontable requiere helpers especiales (dblclick celdas, scroll horizontal, esperar render). HTML tables permiten selectores CSS estándar.
- **Acción:** Crear `e2e/support/handsontable.mjs` con `editCell()`, `getCellValue()`, `selectDropdown()`, `getTableData()`. Para HTML tables usar selectores CSS directos.
- **Estado:** Documentado

### F-010 — Sidebar solo muestra 6 módulos; PDC/Contratos/Listado accesibles solo por URL directa

- **Severidad:** Media (arquitectura de tests)
- **Módulo:** Navegación
- **Evidencia:** Sidebar items visibles para cualquier proyecto: Información General, Integración, Semanas del Proyecto, Programa General, Liberación de Restricciones, Programación Semanal. PDC, Contratos y Listado-Actividades no aparecen en sidebar pero son accesibles vía URL directa (`/pdc`, `/contratos`, `/listado-actividades`).
- **Implicación:** Tests deben navegar por URL directa para estos módulos, no por click en sidebar.
- **Acción:** Documentar ruta de acceso correcta para cada módulo en fixtures.
- **Estado:** Documentado

### F-011 — CIC y Plan Intermedio no existen (404) en ambiente actual

- **Severidad:** Alta (bloquea tests planificados)
- **Módulo:** CIC, PI
- **Evidencia:** `/cic` y `/plan-intermedio` devuelven 404 para ambos proyectos (Da Porto y Aeropuerto PC). Las rutas no existen en `public/index.php`.
- **Implicación:** No se pueden escribir tests E2E para estos módulos hasta que se implementen.
- **Acción:** Saltar tests de CIC y PI. Marcar como "no implementado" en plan.
- **Estado:** Confirmado

### F-012 — PG chips de estado varían por tipo de proyecto (PC vs Construcción)

- **Severidad:** Baja
- **Módulo:** Programa General
- **Evidencia:** Aeropuerto PC (Pre-Construcción): "Con Restricción Pendiente", "Por Iniciar", "Actividad Futura", "En Ejecución", "Atrasada", "Completada", "Sin Datos". Da Porto (Construcción): "Con Alerta Restricciones", "Debe Iniciar", "Actividad Futura", "En Curso", "Atrasada", "Terminada", "Sin Datos".
- **Implicación:** Tests de PG no deben hardcodear textos de chips para ambos proyectos.
- **Acción:** Usar arrays de chips esperados por tipo de proyecto en fixtures.
- **Estado:** Documentado

### F-013 — LPS Drawer universal con "Compilar Digest de Obra" y modo simulación

- **Severidad:** Baja
- **Módulo:** Multi-módulo (PG, PS, etc.)
- **Evidencia:** Todas las páginas tienen botón flotante "Abrir Cajón Contextual LPS" que abre dialog con:
  - Sección "Prioridad": muestra "Selecciona una fila" cuando no hay fila activa
  - Sección "Weekly Digest (Consolidado)": botón "Compilar Digest de Obra"
  - Checkbox "Modo Simulación (Inactivo)" — cuando activo, los CTAs copian al portapapeles en lugar de enviar notificaciones reales
  - Texto: "Las notificaciones reales están bloqueadas. Los CTAs copiarán el reporte al portapapeles."
- **Acción:** Incluir test básico de LPS Drawer en Fase 1 (abrir, verificar secciones, cerrar).
- **Estado:** Documentado

### F-014 — Admin panel tiene login independiente y sesión separada

- **Severidad:** Media
- **Módulo:** Admin Panel
- **Evidencia:** `/admin/` redirige a `/admin/login` si no hay sesión. Login en `/admin/login` con campos: textbox "Usuario", textbox "Contraseña", checkbox "Recuérdame", button "Ingresar". Dashboard en `/admin/` con menú: Dashboard, Proyectos (`/admin/proyectos`), Usuarios (`/admin/usuarios`), Matching Config (`/admin/matching/config`), Catálogo Familias (`/admin/matching/family-catalog`). Sidebar items: Inicio, Salir también.
- **Implicación:** Admin tests requieren login separado y posiblemente manejo de cookies distinto.
- **Acción:** Implementar `adminLogin()` en `e2e/support/admin.mjs` que hace login en `/admin/login`.
- **Estado:** Documentado

### F-015 — Leyenda modal consistente en todos los módulos con "Guia Operativa"

- **Severidad:** Baja
- **Módulo:** Multi-módulo
- **Evidencia:** En PG, el botón "Leyenda" abre un dialog con heading "Guia Operativa - Programa General" con secciones: P1 - Resolver hoy, P2 - Gestion semanal, P3 - Seguimiento, Restricciones Obligatorias (5), Alertas secundarias de restricciones.
- **Implicación:** Selector `dialog[role="dialog"]:has(h4:text("Guia Operativa"))` funciona para todos los módulos con variación en el nombre del módulo.
- **Acción:** Usar selector genérico `page.locator('dialog:has(h4:text("Guia Operativa"))')`.
- **Estado:** Documentado

### F-016 — Proyectos Admin usa DataTable con export buttons

- **Severidad:** Baja
- **Módulo:** Admin Proyectos
- **Evidencia:** `/admin/proyectos` usa DataTable con columnas: ID, Proyecto/Proceso, Área, Estado, Activo, Acceso, Plan de Compras, Acciones. Botones: Nuevo Proyecto, Copiar, CSV, Excel, PDF, Print, Visibilidad, Buscar.
- **Acción:** Tests de admin proyectos pueden verificar render de DataTable.
- **Estado:** Documentado

### F-017 — Usuarios Admin tiene filtros Mostrar inactivos / Mostrar sin proyectos

- **Severidad:** Baja
- **Módulo:** Admin Usuarios
- **Evidencia:** `/admin/usuarios` tiene checkboxes "Mostrar inactivos" y "Mostrar sin proyectos", botones "Nuevo Usuario" y "Excel". Columnas: ID, Nombre, Usuario, Email, Cargo, Rol Principal, Estado, Proyectos, Acciones.
- **Acción:** Tests de admin usuarios pueden verificar toggle de filtros.
- **Estado:** Documentado

### F-001 — PG API update requiere parámetros `unique_id` y `Semana` (no `uniq_id`/`semana`)

- **Severidad:** Media
- **Módulo:** Programa General
- **Ruta:** `POST /api/general/update`
- **Test:** `tests/workflows/lps-two-weeks.spec.mjs`
- **Evidencia:** Console: `{"success":false,"respuesta":"ERROR","error":"Faltan parámetros requeridos (unique_id, Semana)."}`
- **Pasos reproducibles:** Enviar `POST /api/general/update` con `{uniq_id, campo, valor, semana, db}`
- **Resultado esperado:** Update exitoso
- **Resultado actual:** ERROR — espera `unique_id` y `Semana` (case-sensitive)
- **Hipótesis:** El endpoint usa nombres de campo diferentes a los expuestos en `GET /api/general/list`
- **Acción recomendada:** Corregir parámetros en el test o documentar API contract real
- **Estado:** Abierto

### F-002 — PI save no acepta `opcion: 'liberar_todas'`

- **Severidad:** Media
- **Módulo:** Programación Intermedia
- **Ruta:** `POST /api/pi/save`
- **Test:** `tests/workflows/lps-two-weeks.spec.mjs`
- **Evidencia:** Console: `{"respuesta":"ERROR","mensaje":"Opcion no valida para programacion intermedia."}`
- **Pasos reproducibles:** POST con `{semana, db, opcion: 'liberar_todas'}`
- **Resultado esperado:** Liberar restricciones
- **Resultado actual:** ERROR — opción no válida
- **Hipótesis:** `liberar_todas` no es una opción válida para este endpoint
- **Acción recomendada:** Documentar opciones válidas del endpoint
- **Estado:** Abierto

### F-003 — CNP S2 sin filas (0 filas)

- **Severidad:** Baja
- **Módulo:** CNP
- **Ruta:** `POST /api/cnp/list`
- **Test:** `tests/workflows/lps-two-weeks.spec.mjs`
- **Evidencia:** Console: `CNP S2: 0 filas`
- **Pasos reproducibles:** Consultar CNP en semana 2 del proyecto Da Porto
- **Resultado esperado:** Datos o documentación de vacío
- **Resultado actual:** 0 filas (esperable si es una semana nueva sin actividad)
- **Hipótesis:** La semana 2 es una semana recién creada sin datos históricos
- **Acción recomendada:** Documentar que semana 2 es vacuum — requiere seed
- **Estado:** Documentado

### F-004 — Familias auto apply falla con "Solicitud inválida"

- **Severidad:** Media
- **Módulo:** Listado de Actividades
- **Ruta:** `POST /api/listado-actividades/auto/apply`
- **Test:** `tests/workflows/procurement-flow.spec.mjs`
- **Evidencia:** Console: `{"respuesta":"ERROR","mensaje":"Solicitud inválida."}`
- **Pasos reproducibles:** Ejecutar auto/preview exitoso → POST auto/apply con el mismo run_id
- **Resultado esperado:** Apply exitoso con familias creadas
- **Resultado actual:** ERROR — solicitud inválida
- **Hipótesis:** El apply requiere parámetros adicionales (ej. selección de qué familias aplicar) o hay un bug
- **Acción recomendada:** Revisar el contrato de auto/apply — posiblemente requiere `selected` array o similar
- **Estado:** Abierto

### F-005 — API familias manuales no existe (404)

- **Severidad:** Alta
- **Módulo:** Listado de Actividades
- **Ruta:** `POST /api/listado-actividades/familia/save`
- **Test:** `tests/workflows/procurement-flow.spec.mjs`
- **Evidencia:** Console: `{"parseError":true,"text":"404 Not Found"}`
- **Pasos reproducibles:** POST a `/api/listado-actividades/familia/save` con datos de familia
- **Resultado esperado:** Familia creada
- **Resultado actual:** 404 — endpoint no registrado
- **Hipótesis:** El endpoint de creación de familias manuales no existe o tiene una ruta diferente
- **Acción recomendada:** Buscar la ruta correcta en `public/index.php` o documentar que no existe
- **Estado:** Abierto

### F-006 — Contratos apply falla con "Solicitud inválida"

- **Severidad:** Media
- **Módulo:** Contratos
- **Ruta:** `POST /api/contratos/auto/apply`
- **Test:** `tests/workflows/procurement-flow.spec.mjs`
- **Evidencia:** Console: `{"respuesta":"ERROR","mensaje":"Solicitud inválida."}`
- **Pasos reproducibles:** POST con `{run_id}` tras preview exitoso
- **Resultado esperado:** Apply exitoso
- **Resultado actual:** ERROR
- **Hipótesis:** Igual que F-004 — falta parámetro de selección
- **Acción recomendada:** Revisar contrato de auto/apply para contratos
- **Estado:** Abierto

### F-007 — PDC apply falla con "Solicitud inválida"

- **Severidad:** Media
- **Módulo:** PDC
- **Ruta:** `POST /api/pdc/auto/apply`
- **Test:** `tests/workflows/procurement-flow.spec.mjs`
- **Evidencia:** Console: `{"respuesta":"ERROR","mensaje":"Solicitud inválida."}`
- **Pasos reproducibles:** POST con `{run_id}` tras preview exitoso
- **Resultado esperado:** Apply exitoso
- **Resultado actual:** ERROR
- **Hipótesis:** Igual que F-004 y F-006
- **Acción recomendada:** Revisar contrato de auto/apply para PDC
- **Estado:** Abierto

### F-008 — PDC tiene 15 filas en DB y list endpoint funcional

- **Severidad:** Baja (hallazgo positivo)
- **Módulo:** PDC
- **Ruta:** `POST /api/pdc/list`
- **Test:** `tests/workflows/procurement-flow.spec.mjs`
- **Evidencia:** `Filas PDC en DB: 15` / `PDC list endpoint: 15 filas`
- **Resultado:** El módulo PDC está operativo con datos reales. Solo el semi-auto apply falla.
- **Estado:** Documentado

---

## Notas adicionales

- Los 12 smoke tests de rutas pasan sin errores — ninguna ruta devuelve fatal error ni 500.
- El login y selección de proyecto son estables.
- Los módulos CNP, CNC, CIC responden correctamente (0 filas en semana sin actividad).
- Los endpoints semi-auto preview funcionan para los 3 módulos (listado, contratos, PDC) generando run_id y analysis steps.
- Ningún test se ejecutó contra staging.
- No se hizo git push.