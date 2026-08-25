---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/soporte.md
resumen: Escenarios SOP-. Los cuatro módulos que alimentan la cascada sin gobernarla — subcontratistas, profesionales, control de cambios y escalamientos — más un hallazgo de CSRF sin corregir.
---

# Biblia · Módulos de soporte

Escenarios `SOP-*`. Los cuatro módulos que alimentan la cascada sin gobernarla:
**subcontratistas, profesionales, control de cambios y escalamientos**.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**, cerrada
el **2026-08-25**.

---

## Corrección, 2026-08-25: «Contratos» y «Listado de Actividades» ya no existen

Esta sección describía, el mismo día en que se escribió (2026-08-04), dos módulos que **se
retiraron ese mismo día** al eliminarse el PDC v1: `AGENTS.md` §Arquitectura y datos lo nombra
explícitamente («Listado de Actividades, Contratos y el PDC viejo... ya no existen en el repo»),
y `scripts/wiki-arquitectura.modulos.mjs:105` lo confirma. Verificado de nuevo hoy: cero rutas
`/pdc`, `/api/pdc/list` o `/api/pdc/auto/*` en `public/index.php`. La sección original atribuía
esos dos «módulos de la wiki» a rutas del PDC v1 que ya no están en el árbol — quedó corrigiendo
un malentendido sobre algo que dejó de existir en el mismo acto de escribirla.

Los módulos de soporte con backend propio siempre fueron cuatro, y siguen siendo cuatro:
**subcontratistas, profesionales, control de cambios** y **escalamientos**. No hay «los otros dos»
que documentar aparte: el flujo semi-automático que los acompañaba también se retiró con el PDC v1
(ver `docs/pdc-v2.md` — su sucesor no lo reintroduce).

## SOP-001 · Cada módulo de soporte separa ver de editar

- **Pasos:** los tres controladores verificados exigen `rbac_guard_require_permission` con dos
  claves distintas:

| Controlador | Claves |
|---|---|
| `SubcontratistasApiController` | `lps.subcontratistas.ver` / `.editar` |
| `ProfesionalesApiController` | `lps.profesionales.ver` / `.editar` |
| `ControlCambiosApiController` | `lps.control_cambios.ver` / `.editar` |

- **Resultado esperado:** consultar y modificar son llaves separadas en los tres. Un rol puede leer
  el registro de subcontratistas sin poder alterarlo.
- **Verificación:** lectura — los tres archivos en `src/Controllers/Api/`.

> Nótese que el Residente tiene `lps.subcontratistas.editar` pero solo `lps.profesionales.ver`
> (`RbacCatalog::fallbackPermissionsByRole()`): puede mantener el registro de subcontratistas, no el
> de profesionales.

## SOP-002 · Toda mutación autenticada debe exigir token CSRF

- **Resultado esperado:** una petición de escritura sin token válido se rechaza y no escribe nada.
  Lo exige `AGENTS.md` §Seguridad para **toda** mutación autenticada.

> **Hallazgo del 2026-08-04 — CORREGIDO el 2026-08-06 en `88ba6e0d` (más `ca642189`).** Los seis
> controladores mutaban sin validar CSRF: `CicApiController` (10 sentencias de escritura),
> `ProfesionalesApiController` (5), `SubcontratistasApiController` (4), `ControlCambiosApiController`
> (3), `CnpApiController` (2) y `CncApiController` (1). Todos autorizaban solo con
> `rbac_guard_require_permission()`, que comprueba permiso pero **no valida token**. Verificado el
> 2026-08-07: los seis validan ahora.
>
> **La lección que deja, y por eso no se borra:** la pieza ya existía. `legacy_require_csrf()` vivía
> en `src/Legacy/rbac_guard.php`, en el mismo archivo que los seis ya incluían, y solo la llamaban
> dos scripts legados. No faltaba la herramienta: faltaba llamarla. Cuando una defensa depende de
> que cada autor se acuerde de invocarla, seis módulos pueden olvidarla a la vez sin que nada avise.

## SOP-003 · El registro de subcontratistas alimenta la calificación

- **Precondiciones:** existe el subcontratista en su registro.
- **Resultado esperado:** el CIC califica **por subcontratista**, uniendo contra la tabla de
  subcontratistas del proyecto (`CicApiController:149,154`). Un subcontratista sin registro no puede
  calificarse.
- **Verificación:** lectura — `src/Controllers/Api/CicApiController.php:149-154`; escenarios
  `APR-003` y `APR-004`.

## SOP-004 · Escalamientos y comentarios heredan el permiso de Programación Semanal — no tienen capacidad propia

Diez rutas (`memoria/arquitectura/escalamientos-y-crisis.md`), infraestructura sin vista propia,
consumida desde dentro de otros módulos vía `LpsApiController` y `NotificationController`.

- **Pasos:** `comments()` exige `lps.programacion_semanal.ver`; `addComment()`, `registerCrisis()`
  y `closeCrisis()` exigen `lps.programacion_semanal.editar`. Los cuatro comparten el mismo guard
  legado (`rbac_guard_require_permission`).
- **Resultado esperado:** no hay una capacidad `lps.escalamientos.*`. Quien pueda editar Programación
  Semanal puede escalar y cerrar crisis; quien solo pueda verla, solo puede leer comentarios. Es
  decisión de diseño verificable, no un vacío — un escalamiento nace de una actividad de PS y hereda
  su permiso.
- **Verificación:** lectura — `src/Controllers/Api/LpsApiController.php:50,74,116,155`.

## SOP-005 · Registrar una crisis exige un módulo válido y una actividad

- **Pasos:** `registerCrisis()` valida `in_array($modulo, ['PG', 'PI', 'PS'], true)` y
  `$consecutivo > 0` antes de llamar al servicio; si cualquiera falla, responde `ERROR` sin tocar
  la base.
- **Resultado esperado:** una crisis solo puede originarse desde Programa General, Intermedia o
  Semanal — nunca desde un módulo de soporte.
- **Verificación:** lectura — `src/Controllers/Api/LpsApiController.php:125-128`.

## SOP-006 · Cerrar una crisis exige justificación de al menos 100 caracteres

- **Pasos:** `closeCrisis()` rechaza si `mb_strlen($justificacion) < 100`.
- **Resultado esperado:** una crisis no se mitiga con una línea. El umbral es literal y duro, no
  configurable.
- **Verificación:** lectura — `src/Controllers/Api/LpsApiController.php:169`.

## SOP-007 · Un comentario sin fila de profesional para el usuario falla por FK, con mensaje genérico

- **Pasos:** `addComment()` inserta en `lps_drawer_comentarios` con `usuario_id` resuelto de
  `general_usuarios`. Si ese usuario no tiene fila en `profesionales` para el proyecto, el `INSERT`
  falla por restricción de llave foránea; `LpsService::addActivityComment()` atrapa la excepción,
  registra el detalle en `error_log` y devuelve `0`.
- **Resultado esperado:** el cliente recibe `{"respuesta":"ERROR","mensaje":"No se pudo registrar
  el comentario."}` — nunca ve la causa real (la FK), y nada distingue este error de cualquier otro
  fallo de escritura.
- **Ya conocido operacionalmente:** `memoria/trampas/bitacora-drawer-sin-profesional.md` documenta
  el caso concreto —`general_usuarios` id 366 sin fila en `profesionales` para el proyecto 73— como
  limitación de dato sembrado, no de código. Este escenario formaliza esa trampa como comportamiento
  del producto: **cualquier** usuario sin fila de profesional en el proyecto activo se topa con el
  mismo mensaje ciego, en producción y no solo en fixtures de QA.
- **Verificación:** lectura — `src/Services/LpsService.php:203-228`;
  `memoria/trampas/bitacora-drawer-sin-profesional.md`.

## SOP-008 · Control de cambios no tiene vínculo en código con la línea base del programa general

- **Pregunta que dejó pendiente la primera pasada:** qué registra exactamente y cómo se relaciona
  con la línea base.
- **Resultado esperado y medido:** `ControlCambiosApiController` registra solicitudes de cambio en
  una tabla global `cambios` (aislada por `project_id`), con sus propios campos de incidencia
  (`incidenciaCronograma`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`,
  `incidenciaRecurso`) capturados como texto/número al guardar. **No hay ninguna consulta ni
  referencia a `LineaBaseContractualService`, `declared_project_baseline` ni a las tablas de línea
  base del programa general en todo el archivo.** El vínculo, si existe, es manual: alguien lee un
  cambio registrado aquí y decide a mano si mueve la línea base en otro módulo. El código no lo
  automatiza ni lo impide.
- **Verificación:** lectura — `src/Controllers/Api/ControlCambiosApiController.php` completo (281
  líneas); `grep -c "LineaBase\|declared_project_baseline"` → 0.

## SOP-009 · `project_id` se aísla en los cuatro módulos propios, consulta a consulta

- **Resultado esperado:** cada `SELECT`, `UPDATE`, `INSERT` y `DELETE` de los cuatro módulos con
  backend propio filtra o inserta por `project_id`, sin excepción.
- **Verificación:** lectura —
  `src/Controllers/Api/SubcontratistasApiController.php` (8 sitios: `:43,49,149,186,220,235,250,263`),
  `src/Controllers/Api/ProfesionalesApiController.php` (8 sitios: `:67,73,195,221,256,286,326,333`),
  `src/Controllers/Api/ControlCambiosApiController.php` (6 sitios: `:31,46,114,132,194,276`),
  `src/Services/LpsService.php:221` (`insertProjectId()` para escalamientos y comentarios).

---

## Hallazgo de esta pasada: `LpsApiController` muta sin validar CSRF

**No se corrige aquí** — la cláusula de este documento es verificar y registrar, no arreglar en
caliente. Va a `docs/EXPERIMENTS.md`.

Las seis APIs de módulos que SOP-002 documentó sin CSRF se corrigieron el 2026-08-06/07
(`88ba6e0d`, `ca642189`): `CicApiController`, `ProfesionalesApiController`,
`SubcontratistasApiController`, `ControlCambiosApiController`, `CnpApiController`,
`CncApiController`. **`LpsApiController` no estaba en esa lista, y hoy sigue sin validar CSRF** en
sus tres mutaciones: `addComment()`, `registerCrisis()`, `closeCrisis()` — ninguna llama a
`legacy_require_csrf()`, y `public/js/modules/lps_drawer.js` (el único cliente de estas rutas) no
envía ningún token. Autorizan solo con `rbac_guard_require_permission()`, que comprueba permiso y
no valida procedencia — el mismo patrón exacto que SOP-002 ya nombró: «cuando una defensa depende
de que cada autor se acuerde de invocarla, un módulo puede quedar fuera sin que nada avise».

## Escenarios pendientes de esta pasada

- **La comprobación cruzada de `SOP-008`** con datos reales: encontrar un caso donde un cambio
  registrado sí haya movido la línea base a mano, para citar el flujo humano completo.
