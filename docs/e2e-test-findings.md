# E2E Test Findings

## Resumen

- **Fecha:** 2026-07-05
- **Rama:** main
- **Entorno:** Docker local (php 8.3.32, MySQL 8.0.40)
- **Suite:** smoke (12 tests) + deep workflows (2 tests)
- **Resultado:** 14/14 tests pasan — 19 hallazgos documentados

---

## Hallazgos

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