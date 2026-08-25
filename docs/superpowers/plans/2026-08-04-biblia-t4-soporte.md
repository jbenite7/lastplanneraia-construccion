---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-04
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-04-biblia-t4-soporte.md
resumen: Que los seis módulos de soporte que alimentan la cascada LPS sin gobernarla —contratos, listado de actividades, subcontratistas, profesionales, control de…
---

# Biblia de flujos · Tanda T4 (soporte) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que los seis módulos de soporte que alimentan la cascada LPS sin gobernarla —contratos, listado de actividades, subcontratistas, profesionales, control de cambios, escalamientos— tengan cada uno de sus escenarios descrito, verificado contra el código con cita, con la invariante del contrato semi-automático (`auto/preview`, `auto/apply`, `auto/undo`, `auto/feedback`, `auto/metrics`) comprobada entre los tres módulos que dicen compartirla, y los escenarios críticos cubiertos por prueba ejecutable.

**Architecture:** Se añaden a `docs/flujos/` (creado por T1) cinco documentos de tanda: `soporte-contratos-y-actividades.md` (contratos + listado de actividades + la invariante semi-auto en un solo documento porque comparten código y no tiene sentido partirlos), `soporte-subcontratistas.md`, `soporte-profesionales.md`, `soporte-control-de-cambios.md`, `soporte-escalamientos.md`. Cada escenario lleva un `id` estable que `e2e/` cita en su título. Los hallazgos no se arreglan: van a `docs/EXPERIMENTS.md` (creado por T1).

**Tech Stack:** Markdown versionado · Playwright (`e2e/`, config propia en `e2e/playwright.config.mjs`) · la puerta de servicio `/dev/entrar` para abrir sesión con rol real · PHP 8.3 en Docker para inspección.

## Global Constraints

- **Cláusula de autoridad:** si la biblia y el código divergen, **es un bug de uno de los dos y hay que resolverlo**; no se corrige la biblia en silencio para que cuadre con el código.
- **Verificar, no sospechar:** toda afirmación comprobable lleva cita `archivo:línea` leída en la sesión. Lo que no se pueda comprobar leyendo se declara «no comprobable en lectura»; nunca se da por bueno.
- **Los hallazgos se registran en `docs/EXPERIMENTS.md` y la pasada continúa.** Nada de arreglar en caliente. Si la duda es *cuál es la conducta correcta*, la decisión es del usuario.
- **Sesión local solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>`, cuentas `test.A` (rol A), `test.R` (rol R), `test.V` (rol V). **Nunca** teclear credenciales en `/login` ni pedirle a una persona que inicie sesión (`AGENTS.md`).
- **Viewport de validación:** 1180×820, **dark only**. No se genera evidencia de móvil, tablet ni tema `linen`.
- **Rol permitido y rol denegado:** todo escenario de capacidad cubre al menos uno de cada (`AGENTS.md`, routing de RBAC).
- **Formato del `id`:** `SOP-<NNN>`, número de tres dígitos, estable para siempre. Un escenario retirado conserva su número; no se reutiliza. (Un solo prefijo `SOP` para toda la tanda, a diferencia de T1: los seis módulos son heterogéneos pero pequeños, y partir el prefijo por módulo no aporta nada que el nombre del documento no dé ya.)
- **Toda consulta operativa se aísla por `project_id`:** comprobarlo es escenario, no supuesto.
- **No se hace commit sin petición explícita del usuario** (`AGENTS.md` §Publicación).
- **Depende de T1 cerrada primero:** este plan asume que `docs/flujos/README.md` y `docs/EXPERIMENTS.md` ya existen con el formato del escenario y el esqueleto del backlog. Si no existen aún, la Tarea 1 los crea con el mismo contenido que describiría T1 (ver `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md` Tarea 1) para no bloquear T4; si T1 ya corrió, la Tarea 1 se reduce a añadir la entrada de T4 al índice.

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `docs/flujos/soporte-contratos-y-actividades.md` (nuevo) | Escenarios `SOP-*` de Contratos y Listado de Actividades (PDC v1), incluida la invariante del contrato semi-automático entre los tres módulos que dicen compartirlo. |
| `docs/flujos/soporte-subcontratistas.md` (nuevo) | Escenarios `SOP-*` del catálogo de subcontratistas: grilla editable, uso como destino de contratación, aislamiento por proyecto. |
| `docs/flujos/soporte-profesionales.md` (nuevo) | Escenarios `SOP-*` del equipo AIA asignado al proyecto: sincronización con `project_members`, consumo desde Programa General y Control de Cambios. |
| `docs/flujos/soporte-control-de-cambios.md` (nuevo) | Escenarios `SOP-*` de órdenes de cambio: sin capacidad RBAC propia, solo permisos `lps.control_cambios.*`. |
| `docs/flujos/soporte-escalamientos.md` (nuevo) | Escenarios `SOP-*` de comentarios, escalamiento y registro/cierre de crisis: infraestructura sin ruta propia, consumida por `LpsApiController`. |
| `e2e/tests/biblia/soporte.spec.mjs` (nuevo) | Las pruebas de los escenarios críticos de T4, cada `test()` titulado con su `id`. |
| `docs/EXPERIMENTS.md` (modificar) | Hallazgos de T4 añadidos a `## Experiment Backlog`. |
| `docs/flujos/README.md` (modificar) | Marca T4 como cerrada con su recuento de escenarios. |
| `memoria/mapas/pdc.md`, `memoria/mapas/lps.md` (modificar, el que exista y aplique) | Enlaza la biblia desde el mapa del área. |
| `memoria/log.md` (modificar) | Línea `ingest` de cierre de T4. |

---

### Task 1: Confirma la base de T1 y arranca el documento de contratos y actividades

**Files:**
- Read: `docs/flujos/README.md`, `docs/EXPERIMENTS.md` (si existen)
- Create (si no existen): mismos dos archivos, con el contenido de la Tarea 1 de `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`
- Create: `docs/flujos/soporte-contratos-y-actividades.md`
- Read: `public/index.php` líneas 130-270 (bloque `Programacion`/`Gestion` de PDC y contratos), `src/Controllers/Api/SemiAutoController.php`, `src/Controllers/Api/PdcAutoGenerateController.php`, `public/js/modules/semi_auto_review.js`, `views/pdc/pdc.view.php`, `memoria/arquitectura/contratos.md`, `memoria/arquitectura/listado-de-actividades.md`

**Interfaces:**
- Consumes: el formato del escenario de T1 (o lo recrea si T1 no corrió).
- Produces: los `id` `SOP-001`…`SOP-0NN` del primer documento, que la Tarea 6 cita en las pruebas.

- [ ] **Step 1: Verifica si T1 ya dejó la base**

```bash
ls "docs/flujos/README.md" "docs/EXPERIMENTS.md" 2>&1
```

Si ambos existen, léelos y sigue con el Step 2. Si no existen, créalos ahora **con exactamente el contenido descrito en la Tarea 1 de `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`** (la cláusula de autoridad, el formato del escenario, los dos niveles de verificación, cómo se cita un `id`, el índice de las cinco tandas, la regla de los hallazgos; y el esqueleto de `## Experiment Cards` / `## Experiment Backlog` con su tabla ICE). No inventes un formato distinto: si T1 corre después, debe encontrar exactamente lo que su propio plan pide.

- [ ] **Step 2: Comprueba la invariante semi-automática ANTES de describir ningún escenario**

Lee las rutas registradas realmente en `public/index.php` (no las que el módulo JS espera):

```bash
grep -n "auto/preview\|auto/apply\|auto/undo\|auto/feedback\|auto/metrics" public/index.php
```

Resultado esperado a verificar (ya comprobado el 2026-08-04): **solo existen las seis rutas `/api/pdc/auto/*`** (`public/index.php:256-268`), todas apuntando a los métodos `*Pdc` de `SemiAutoController`. No hay ninguna ruta `/api/listado-actividades/auto/*` ni `/api/contratos/auto/*` registrada.

Contrasta con el cliente JS, que sí espera esas tres bases:

```bash
grep -n "base:" public/js/modules/semi_auto_review.js
```

`public/js/modules/semi_auto_review.js:7` declara `base: '/api/listado-actividades/auto'` y `:12` declara `base: '/api/contratos/auto'`, junto a `:17` con `base: '/api/pdc/auto'`. Solo la tercera tiene ruta registrada.

Y confirma que el controlador SÍ tiene los métodos para las otras dos (código muerto, no ausente):

```bash
grep -n "public function.*Listado\|public function.*Contratos" src/Controllers/Api/SemiAutoController.php
```

`src/Controllers/Api/SemiAutoController.php:26-81` define `previewListado`/`applyListado`/`undoListado`/`feedbackListado`/`metricsListado` y `:86-141` sus equivalentes `*Contratos` — ninguno alcanzable por ruta.

Por último, confirma que ningún consumidor real invoca `SemiAutoReview.open('listado')` ni `SemiAutoReview.open('contratos')`:

```bash
grep -rn "SemiAutoReview.open\|SemiAutoReview.init" views/ public/js/modules/
```

Esperado (ya comprobado el 2026-08-04): la única invocación real es `public/js/modules/pdc/hot.js:301` y `:317`, con `open('pdc')`. `views/pdc/pdc.view.php` es el único consumidor de `semi_auto_review.js` (`grep -rln "semi_auto_review" views/` devuelve un solo archivo).

Esto es la invariante que `AGENTS.md` da por sentada («Listado de Actividades, Contratos y PDC comparten los contratos... y no crear un flujo paralelo») y que el propio `memoria/arquitectura/contratos.md:21` documenta como reparto de criterio. **La comprobación de lectura muestra que hoy solo uno de los tres módulos la usa; los otros dos tienen el contrato declarado en tres capas (ruta ausente, controlador con métodos muertos, JS con base URL que 404ea) sin estar conectados a ninguna vista real.** Esto es candidato directo a hallazgo — no lo arregles, seguí al Step 5.

- [ ] **Step 3: Enumera los escenarios de Contratos y Listado de Actividades antes de redactarlos**

Rutas reales de ambos módulos, generadas en `memoria/arquitectura/contratos.md` y `memoria/arquitectura/listado-de-actividades.md`:

| Verbo | Ruta | Destino |
|---|---|---|
| GET | `/pdc` | `App\Controllers\Gestion\PdcController::index` |
| POST | `/api/pdc/list` | `App\Controllers\Api\PdcApiController::list` |
| POST | `/api/pdc/save` | `App\Controllers\Api\PdcApiController::save` |
| POST | `/api/pdc/update-cell` | `App\Controllers\Api\PdcApiController::updateCell` |
| GET | `/api/pdc/duracion-sugerida` | `App\Controllers\Api\PdcApiController::duracionSugerida` |
| POST | `/api/pdc/auto/preview` | `App\Controllers\Api\SemiAutoController::previewPdc` |
| POST | `/api/pdc/auto/apply` | `App\Controllers\Api\SemiAutoController::applyPdc` |
| POST | `/api/pdc/auto/undo` | `App\Controllers\Api\SemiAutoController::undoPdc` |
| POST | `/api/pdc/auto/feedback` | `App\Controllers\Api\SemiAutoController::feedbackPdc` |
| POST | `/api/pdc/auto/metrics` | `App\Controllers\Api\SemiAutoController::metricsPdc` |
| POST | `/api/pdc/auto/apply-from-contratos` | `App\Controllers\Api\PdcAutoGenerateController::applyFromContratos` |

Nota que no hay una ruta `GET /contratos`: no existe una vista independiente de «Contratos» — es una capacidad (`canManageContracts`, `canAutoDefineContracts`) que se ejerce dentro de `/pdc` sobre las mismas filas de actividades. Verifícalo con:

```bash
grep -n "'/contratos'" public/index.php
```

Esperado: sin resultados. Esto también es un escenario: describe qué ve un rol con `canManageContracts` pero sin `canManagePdC` (si existe alguno; verifícalo en el Step 1 de la Tarea 2 de T1 o en `RbacCatalog::fallbackPermissionsByRole()`), porque si «Contratos» no tiene ruta propia, esa capacidad solo puede ejercerse a través de `/pdc`.

Escenarios obligatorios como mínimo: listar actividades de un proyecto con aislamiento por `project_id` (`PdcApiController::list`, lee `src/Controllers/Api/PdcApiController.php` para el WHERE); guardar una fila nueva; actualizar una celda; correr `preview` del motor semi-auto sobre PDC y ver las sugerencias; `apply` de una sugerencia; `undo` de un run aplicado; `apply-from-contratos` (que sí tiene ruta propia — describe qué hace distinto de `applyPdc`, leyendo `src/Controllers/Api/PdcAutoGenerateController.php`); y el camino de error de intentar `undo` de un run que no existe o ya se deshizo.

- [ ] **Step 4: Redacta los escenarios con el formato del README, incluida la invariante como su propio escenario**

El escenario de la invariante no es «cómo funciona preview en PDC»: es **la comparación entre los tres módulos**. Formato mínimo:

```markdown
### SOP-0XX · La invariante del contrato semi-automático no se cumple para Listado de Actividades ni Contratos

- **Rol:** A, D, R, OT (los que tienen `canManagePdC` o `canManageContracts`).
- **Precondiciones:** ninguna — es un hallazgo de enrutamiento, no de datos.
- **Pasos:**
  1. Se listan las rutas registradas para `/api/pdc/auto/*`, `/api/listado-actividades/auto/*` y `/api/contratos/auto/*`.
  2. Se contrasta con las bases que declara `public/js/modules/semi_auto_review.js:4-18`.
  3. Se contrasta con los métodos que expone `SemiAutoController` para los tres módulos.
- **Resultado esperado (documentado, no verificado como correcto):** los tres módulos deberían exponer el mismo contrato — es la lectura razonable de `AGENTS.md` §Arquitectura y datos y de `memoria/arquitectura/contratos.md:21`.
- **Resultado real:** solo `/api/pdc/auto/*` está registrado (`public/index.php:256-268`); `previewListado`/`applyListado`/`undoListado`/`feedbackListado`/`metricsListado` (`src/Controllers/Api/SemiAutoController.php:26-81`) y sus equivalentes `*Contratos` (`:86-141`) no tienen ruta, y ningún consumidor en `views/` los invoca.
- **Verificación:** lectura — `public/index.php:256-268`, `src/Controllers/Api/SemiAutoController.php:26-141`, `public/js/modules/semi_auto_review.js:4-18`. Ejecutable — pendiente (ver Task 6).
```

Redacta el resto de los escenarios (`SOP-001` en adelante) con el mismo nivel de detalle que el ejemplo `AUTH-004` de T1: rol, precondiciones, pasos citando el método del controlador, resultado esperado en pantalla **y** en datos, y verificación con cita.

- [ ] **Step 5: Registra el hallazgo de la invariante, sin arreglarlo**

Añade una fila a `docs/EXPERIMENTS.md` `## Experiment Backlog` con `Origen` = el `id` del escenario de la invariante, `Impact` alto (si un usuario intenta usar el panel semi-auto desde Listado de Actividades o Contratos fuera de `/pdc`, el JS ya está preparado para intentarlo y fallaría en silencio con 404 — pero como el Step 2 confirmó que ningún consumidor lo invoca hoy, marca `Confidence` media: el riesgo es que alguien reactive esa UI creyendo que el backend ya la soporta). No toques `src/` ni `public/js/`.

- [ ] **Step 6: Comprueba que ningún escenario es transcripción del código**

Relee cada uno. Si solo repite lo que la función hace, sobra: bórralo.

---

### Task 2: Escenarios de Subcontratistas

**Files:**
- Create: `docs/flujos/soporte-subcontratistas.md`
- Read: `src/Controllers/Api/SubcontratistasApiController.php`, `src/Controllers/Gestion/SubcontratistasController.php`, `memoria/arquitectura/subcontratistas.md`

**Interfaces:**
- Consumes: el formato del escenario.
- Produces: `id` `SOP-0NN` de Subcontratistas.

- [ ] **Step 1: Verifica el aislamiento por proyecto antes de describirlo**

```bash
grep -n "project_id\|WHERE" src/Controllers/Api/SubcontratistasApiController.php | head -30
```

Cita las líneas reales del `WHERE project_id = ?` en `list()` y `save()`. Si alguna consulta no filtra por `project_id`, es hallazgo con severidad alta (fuga entre proyectos).

- [ ] **Step 2: Enumera los escenarios**

Como mínimo: listar el catálogo de un proyecto; guardar/editar una fila en la grilla (autosave); un subcontratista usado como destino de un paquete de contratación desde Contratos (la relación cruzada que documenta `memoria/arquitectura/subcontratistas.md:14`, verifícala citando la columna real en `pdc` o `general_pdc_family_contract_options` que referencia subcontratistas); un subcontratista calificado en CIC (`submodulo-cic`, cita la tabla `bi_cic_contratistas` si aplica); y el camino de error de guardar una fila con un campo requerido vacío.

- [ ] **Step 3: Redacta con el formato del README**

Cada escenario cita el rol permitido (`canManageContracts`: A, D, R, OT según `memoria/arquitectura/subcontratistas.md:44-46`) y qué pasa con un rol denegado que intenta `save()` — verifica si el guard es `rbac_guard_require_permission` o `authorizePermission`, y qué responde (código HTTP, cuerpo) citando la línea exacta.

- [ ] **Step 4: Registra hallazgos y sigue**

---

### Task 3: Escenarios de Profesionales

**Files:**
- Create: `docs/flujos/soporte-profesionales.md`
- Read: `src/Controllers/Api/ProfesionalesApiController.php`, `src/Services/ProjectProfessionalsSyncService.php` (o la ruta real si el nombre difiere — confírmalo con `find src -iname "*ProjectProfessionalsSync*"`), `memoria/arquitectura/profesionales.md`

**Interfaces:**
- Consumes: el formato del escenario.
- Produces: `id` `SOP-0NN` de Profesionales.

- [ ] **Step 1: Verifica la sincronización con `project_members`**

`memoria/arquitectura/profesionales.md:39` lista `ProjectProfessionalsSyncService` y las tablas `project_members`/`general_usuarios`. Léelo y describe **cuándo** se dispara la sincronización (¿al guardar una fila? ¿en un cron? ¿al entrar a la vista?) citando el método real. Esto importa porque otros módulos —Programa General, Control de Cambios— reutilizan estos nombres como responsables (`memoria/arquitectura/profesionales.md:13`): si la sincronización falla o queda desfasada, esos otros módulos muestran responsables que ya no están en el proyecto.

- [ ] **Step 2: Enumera los escenarios**

Como mínimo: listar profesionales de un proyecto; agregar un profesional nuevo (¿crea también un `project_members`, o son independientes?); editar un profesional existente; el consumo desde `programa-general` o `control-cambios` de un nombre de profesional (verifica el campo real, p. ej. `Responsable_AIA`, y en qué tabla vive); y qué pasa si se elimina/desactiva un profesional que ya tiene actividades asignadas (¿se rompe la referencia, queda huérfana, o el borrado está bloqueado?).

- [ ] **Step 3: Redacta con el formato del README**

Esta ruta no tiene capacidad RBAC propia (`memoria/arquitectura/profesionales.md:47`: «Sin capacidad propia: la ruta exige sesión y proyecto»). Verifica tú mismo qué guard usa (`requireAuth()` solo, o algo más) citando `src/Controllers/Api/ProfesionalesApiController.php` línea por línea, porque «sin capacidad propia» generado desde rutas no prueba que no haya un chequeo dentro del método.

- [ ] **Step 4: Registra hallazgos y sigue**

---

### Task 4: Escenarios de Control de Cambios

**Files:**
- Create: `docs/flujos/soporte-control-de-cambios.md`
- Read: `src/Controllers/Api/ControlCambiosApiController.php`, `src/Controllers/Integracion/ControlCambiosController.php`, `memoria/arquitectura/control-de-cambios.md`

**Interfaces:**
- Consumes: el formato del escenario.
- Produces: `id` `SOP-0NN` de Control de Cambios.

- [ ] **Step 1: Verifica el guard real, no el generado**

`src/Controllers/Api/ControlCambiosApiController.php:20-21` usa `require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php'; rbac_guard_require_permission('lps.control_cambios.ver')` en `list()`, y `lps.control_cambios.editar` en `save()` (verifica la línea exacta de `save()` con `grep -n "rbac_guard_require_permission" src/Controllers/Api/ControlCambiosApiController.php`). Esto usa el permiso fino (`lps.control_cambios.*`), no una de las 17 capacidades booleanas de `RbacManager` — es una vía RBAC distinta a la que documentó T1 para `RbacManager::getCapabilities()`. Descríbelo como tal: dos sistemas de permisos conviven (`RbacManager` por capacidad y `rbac_guard_require_permission` por permission key fino), y este módulo usa el segundo.

- [ ] **Step 2: Verifica el aislamiento por proyecto**

`src/Controllers/Api/ControlCambiosApiController.php:31` hace `queryWithProject($queryCount, [$projectId], $projectId)` — cita el método `projectId($dbPrefix)` para confirmar de dónde sale `$projectId` (¿de `TableResolver`? ¿de sesión?) y si valida que el `$dbPrefix` recibido por GET pertenece al proyecto en sesión, o si un usuario autenticado podría pasar el `db` de otro proyecto en la query string. Esto es exactamente el tipo de escenario de aislamiento que `AGENTS.md` exige comprobar, no suponer.

- [ ] **Step 3: Enumera los escenarios**

Como mínimo: listar cambios de un proyecto sin registros (el `if ($conteo == 0)` de `:33-42` devuelve una fila plantilla vacía — describe por qué y qué consume el frontend de esa fila vacía); listar con registros; guardar una orden de cambio nueva; el camino de error de un `$dbPrefix` con caracteres inválidos (`:24-27`, regex `/^[a-zA-Z0-9_-]+$/`); y el escenario de aislamiento del Step 2.

- [ ] **Step 4: Redacta con el formato del README y registra hallazgos**

---

### Task 5: Escenarios de Escalamientos, comentarios y crisis

**Files:**
- Create: `docs/flujos/soporte-escalamientos.md`
- Read: `src/Controllers/Api/LpsApiController.php` líneas 1-190, `src/Controllers/Core/DashboardController.php`, `src/Controllers/Core/NotificationController.php`, `memoria/arquitectura/escalamientos-y-crisis.md`

**Interfaces:**
- Consumes: el formato del escenario.
- Produces: `id` `SOP-0NN` de Escalamientos.

- [ ] **Step 1: Enumera los caminos reales antes de describir ninguno**

Rutas generadas en `memoria/arquitectura/escalamientos-y-crisis.md:24-36`. Este módulo no es una vista con ruta propia: es infraestructura consumida desde otros módulos. Comprueba desde dónde se invoca `addComment`/`registerCrisis`/`closeCrisis` (busca el JS que llama a estos endpoints):

```bash
grep -rln "api/lps/comments\|api/lps/crisis" public/js/modules/
```

- [ ] **Step 2: Verifica el umbral de justificación de cierre de crisis**

`src/Controllers/Api/LpsApiController.php:170` exige `mb_strlen($justificacion) < 100` para rechazar el cierre. Esto es un número de negocio verificable — descríbelo con el número exacto (100 caracteres) y qué pasa exactamente en 99 vs 100 caracteres (verifica el operador: `<` rechaza en `< 100`, así que 100 exactos pasa).

- [ ] **Step 3: Enumera los escenarios**

Como mínimo: agregar un comentario a una actividad (`addComment`, permiso `lps.programacion_semanal.editar`); listar comentarios de una actividad, con y sin `escalamiento_id`; registrar una alerta de crisis desde PG/PI/PS (`registerCrisis`, valida `in_array($modulo, ['PG','PI','PS'])` en `:135`); cerrar una crisis con justificación válida; cerrar una crisis con justificación de 99 caracteres (rechazada); y el camino de error de `consecutivo <= 0` en cualquiera de los tres.

- [ ] **Step 4: Redacta con el formato del README**

Todos estos endpoints comparten el guard `rbac_guard_require_permission('lps.programacion_semanal.editar')` o `.ver` — no hay capacidad propia de «escalamientos». Describe explícitamente que un escalamiento hereda el permiso de Programación Semanal, no uno propio: es una decisión de diseño verificable, no un vacío.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 6: Las pruebas ejecutables de los críticos

**Files:**
- Create: `e2e/tests/biblia/soporte.spec.mjs`
- Read: `e2e/playwright.config.mjs`, `e2e/support/` (fixtures existentes), `e2e/tests/biblia/transversal.spec.mjs` (si T1 ya corrió, para seguir el mismo patrón)

**Interfaces:**
- Consumes: los `id` de las tareas 1-5.
- Produces: pruebas cuyo título empieza por el `id` del escenario que cubren.

- [ ] **Step 1: Elige qué sube al nivel ejecutable**

Criterio del spec: toca permisos, muta datos, o cierra/abre un periodo. Para T4 eso son, como mínimo:

- guardar una fila en Listado de Actividades y verla persistir tras recargar (`SOP-*` de la Tarea 1);
- un rol con `canManageContracts`/`canManagePdC` y otro sin ella intentando `save` en Subcontratistas (`SOP-*` de la Tarea 2), reusando el patrón `test.R` permitido / `test.V` denegado;
- cerrar una crisis con justificación de menos de 100 caracteres y verificar el rechazo (`SOP-*` de la Tarea 5) — es una mutación de estado (`cambios`/`system_notifications` o la tabla de alertas real) con una regla de negocio dura.

Escribe en el documento **por qué** cada uno sube y por qué los demás no.

- [ ] **Step 2: Lee las fixtures antes de escribir**

```bash
ls e2e/support/; sed -n '1,40p' e2e/playwright.config.mjs
```

Reutiliza el fixture de credenciales y el `baseURL` existentes. No dupliques valores privilegiados en el spec nuevo.

- [ ] **Step 3: Escribe las pruebas con el `id` en el título**

```javascript
import { test, expect } from '@playwright/test';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('SOP-0XX · Guardar una fila en Listado de Actividades persiste tras recargar', async ({ page }) => {
  await page.goto('/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E');
  await page.goto('/pdc');
  // ajustar selectores tras inspeccionar la grilla real
});
```

Ajusta los selectores a los reales de la vista tras inspeccionarla en el navegador; el patrón de arriba no es una promesa de selector.

- [ ] **Step 4: Corre las pruebas contra el contenedor**

```bash
npx playwright test e2e/tests/biblia/soporte.spec.mjs --config=e2e/playwright.config.mjs --workers=1
```

Esperado: verde. Si una falla, no toques la prueba para que pase: o el escenario está mal descrito (corrige la biblia) o el código incumple (hallazgo al backlog).

- [ ] **Step 5: Anota en cada escenario cubierto su prueba**

En los cinco documentos de las tareas 1-5, el campo «Verificación» de los escenarios cubiertos pasa de «ejecutable — pendiente» a citar el archivo y el título del test.

---

### Task 7: Cierre de la tanda

**Files:**
- Modify: `docs/EXPERIMENTS.md`, `docs/flujos/README.md`, `memoria/mapas/pdc.md`, `memoria/mapas/lps.md` (el que exista y aplique — comprueba con `ls memoria/mapas/`), `memoria/log.md`
- Modify: `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: los hallazgos de las tareas 1-6.
- Produces: la tanda T4 cerrada y medible.

- [ ] **Step 1: Prioriza el backlog**

Cada hallazgo con Impact, Confidence y Ease de 1 a 10 y su ICE calculado. Ordena por ICE descendente. El hallazgo de la invariante semi-automática (Tarea 1, Step 5) necesita decisión del usuario: ¿se conecta Listado de Actividades y Contratos al mismo panel semi-auto que PDC, o se retira el código muerto? Márcalo explícitamente para esa decisión.

- [ ] **Step 2: Teje la biblia en la wiki**

```bash
ls memoria/mapas/ | grep -i "pdc\|lps"
```

En el mapa que cubra PDC/contratos y en el que cubra LPS/soporte, añade el enlace a los documentos de `docs/flujos/soporte-*` explicando que la biblia describe el comportamiento esperado y el mapa describe dónde vive el código.

- [ ] **Step 3: Actualiza los dos trackers**

En `docs/flujos/README.md`, marca T4 como cerrada con su recuento de escenarios. En `docs/IMPROVE-APP-PLAN.md`, añade a `## Key Decisions` la fila del cierre de T4.

- [ ] **Step 4: Corre el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos`.

- [ ] **Step 5: Deja la línea de bitácora**

Una línea `ingest` en `memoria/log.md` con: cuántos escenarios describe T4, cuántos se verificaron por lectura, cuántos subieron a ejecutable, y cuántos hallazgos entraron al backlog (incluido el de la invariante semi-automática). Números medidos, no estimados.

---

## Verificación final de T4

```bash
npx playwright test e2e/tests/biblia/soporte.spec.mjs --config=e2e/playwright.config.mjs --workers=1
npm run test:wiki
```

Y comprueba las condiciones de hecho del spec (`docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md` §Condición de hecho) que aplican a T4: escenarios descritos y verificados con cita, críticos con prueba citando su `id`, hallazgos en el backlog sin arreglar (con la invariante semi-automática marcada para decisión del usuario), wiki enlazada y en verde.

**Sobre la validación en navegador:** T4 toca superficie observable (grillas editables, panel semi-auto, cierre de crisis), así que la evidencia de Playwright es la validación exigida. No hace falta recorrido manual adicional salvo para calibrar selectores antes de escribir las pruebas (Task 6, Step 3).

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25 (dos pasadas). La primera encontró: manda 5 documentos y
solo existe `docs/flujos/soporte.md`; escalamientos entero sin verificar
(README.md:96) — ver [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]] y
[[memoria/trampas/el-goal-cierra-un-alcance-menor-que-el-del-plan]].

**Cerrado el mismo día**, con una decisión de alcance explícita: los cinco documentos del plan se
consolidan en el único `docs/flujos/soporte.md` (mismo criterio ya aceptado para T5 con
`lectura-bi.md`) — subcontratistas, profesionales y control de cambios comparten código y
escenarios contiguos; separarlos habría sido partición artificial, no claridad.

Se completó lo que faltaba: escalamientos (`SOP-004` a `SOP-007`, las diez rutas de
`memoria/arquitectura/escalamientos-y-crisis.md`, incluida la FK de `bitacora-drawer-sin-profesional`
formalizada como comportamiento del producto), el vínculo de control de cambios con la línea base
(`SOP-008` — no existe en código, es manual) y el aislamiento por `project_id` de los cuatro
módulos (`SOP-009`). `e2e/tests/biblia/soporte.spec.mjs` pasó de 2 a 4 pruebas, todas de rechazo,
sin mutar datos.

**Corrección aparte, más grave que el hueco original:** la sección de apertura del documento
describía «Contratos» y «Listado de Actividades» como módulos vigentes atribuidos al PDC —
ambos se habían retirado **el mismo día** en que se escribió, con el PDC v1 (`AGENTS.md`
§Arquitectura y datos). Once meses… no, el mismo día: la biblia nació citando un enlace roto a
`docs/flujos/pdc-v2.md` (el archivo real es `compras-v2.md`) y rutas `/pdc` que cero veces
aparecen hoy en `public/index.php`.

**Hallazgo nuevo, no en el plan original:** `LpsApiController` muta sin validar CSRF —quedó fuera
del cierre `88ba6e0d`/`ca642189` que corrigió los otros seis módulos—, registrado en
`docs/EXPERIMENTS.md` (fila «Escalamientos (T4, 2026-08-25)») y **no corregido en esta pasada**,
por la propia cláusula de autoridad del documento: verificar y registrar, no arreglar en caliente.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
