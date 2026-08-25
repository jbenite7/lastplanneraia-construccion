---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-04
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-04-biblia-t5-lectura.md
resumen: Que Indicadores LPS y la Torre de Control BI —los dos módulos de solo lectura— tengan sus escenarios de aislamiento por projectid, visibilidad por rol y…
---

# Biblia de flujos · Tanda T5 (lectura) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que Indicadores LPS y la Torre de Control BI —los dos módulos de solo lectura— tengan sus escenarios de aislamiento por `project_id`, visibilidad por rol y coherencia de cifras descritos y verificados contra el código con cita, con el caso conocido de `/indicadores` (Power BI publish-to-web sin filtro por proyecto) documentado como excepción explícita a la regla de aislamiento, y los escenarios críticos de acceso denegado cubiertos por prueba ejecutable.

**Architecture:** Se añaden a `docs/flujos/` (creado por T1) dos documentos: `lectura-indicadores.md` y `lectura-torre-de-control.md`. T5 es deliberadamente más chica que las demás tandas: es solo consulta, no hay mutación de datos ni cierre de periodos, así que el peso cae en aislamiento y visibilidad, no en caminos de escritura. Los hallazgos no se arreglan: van a `docs/EXPERIMENTS.md` (creado por T1).

**Tech Stack:** Markdown versionado · Playwright (`e2e/`, config propia en `e2e/playwright.config.mjs`) · la puerta de servicio `/dev/entrar` para abrir sesión con rol real · PHP 8.3 en Docker para inspección.

## Global Constraints

- **Cláusula de autoridad:** si la biblia y el código divergen, **es un bug de uno de los dos y hay que resolverlo**; no se corrige la biblia en silencio para que cuadre con el código.
- **Verificar, no sospechar:** toda afirmación comprobable lleva cita `archivo:línea` leída en la sesión. Lo que no se pueda comprobar leyendo se declara «no comprobable en lectura»; nunca se da por bueno. La coherencia de cifras contra lo que registran los módulos operativos, cuando exija correr una consulta SQL real, se marca así explícitamente si esta pasada no llega a ejecutarla.
- **Los hallazgos se registran en `docs/EXPERIMENTS.md` y la pasada continúa.** Nada de arreglar en caliente. Si la duda es *cuál es la conducta correcta*, la decisión es del usuario.
- **Sesión local solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>`, cuentas `test.A` (rol A), `test.R` (rol R), `test.V` (rol V). **Nunca** teclear credenciales en `/login` ni pedirle a una persona que inicie sesión (`AGENTS.md`).
- **Viewport de validación:** 1180×820, **dark only**. No se genera evidencia de móvil, tablet ni tema `linen`.
- **Rol permitido y rol denegado:** todo escenario de capacidad cubre al menos uno de cada (`AGENTS.md`, routing de RBAC).
- **Formato del `id`:** `BI-<NNN>`, número de tres dígitos, estable para siempre. Un escenario retirado conserva su número; no se reutiliza.
- **Toda consulta operativa se aísla por `project_id`:** comprobarlo es escenario, no supuesto. La excepción documentada de `/indicadores` (ver Task 1) no es una excepción a esta regla — es un escenario que constata que la regla no se cumple ahí y por qué está aceptado (`memoria/decisiones/powerbi-indicadores.md`).
- **No se hace commit sin petición explícita del usuario** (`AGENTS.md` §Publicación).
- **Depende de T1 cerrada primero:** este plan asume que `docs/flujos/README.md` y `docs/EXPERIMENTS.md` ya existen. Si no existen aún, la Tarea 1 los crea con el contenido descrito en la Tarea 1 de `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`.

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `docs/flujos/lectura-indicadores.md` (nuevo) | Escenarios `BI-*` de `/indicadores`: el embed Power BI publish-to-web, los cuatro roles que no lo ven, y el endpoint de generación de indicadores. |
| `docs/flujos/lectura-torre-de-control.md` (nuevo) | Escenarios `BI-*` de `/bi/*`: resolución de proyectos autorizados (`BiProjectScope`), los ocho reportes, y la comparación explícita con el modelo de autorización de `/indicadores`. |
| `e2e/tests/biblia/lectura.spec.mjs` (nuevo) | Las pruebas de los escenarios críticos de T5, cada `test()` titulado con su `id`. |
| `docs/EXPERIMENTS.md` (modificar) | Hallazgos de T5 añadidos a `## Experiment Backlog`. |
| `docs/flujos/README.md` (modificar) | Marca T5 como cerrada con su recuento de escenarios. |
| `memoria/mapas/` (modificar, el mapa que cubra BI — comprobar cuál con `ls`) | Enlaza la biblia desde el mapa del área. |
| `memoria/log.md` (modificar) | Línea `ingest` de cierre de T5. |

---

### Task 1: Confirma la base de T1 y escenarios de Indicadores LPS

**Files:**
- Read: `docs/flujos/README.md`, `docs/EXPERIMENTS.md` (si existen)
- Create (si no existen): mismos dos archivos, con el contenido de la Tarea 1 de `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`
- Create: `docs/flujos/lectura-indicadores.md`
- Read: `src/Controllers/Gestion/IndicadoresController.php`, `src/Controllers/Api/IndicadoresApiController.php`, `views/indicadores/indicadores.view.php`, `memoria/decisiones/powerbi-indicadores.md`, `memoria/arquitectura/indicadores.md`

**Interfaces:**
- Consumes: el formato del escenario de T1 (o lo recrea si T1 no corrió).
- Produces: los `id` `BI-001`…`BI-0NN` del primer documento.

- [ ] **Step 1: Verifica si T1 ya dejó la base**

```bash
ls "docs/flujos/README.md" "docs/EXPERIMENTS.md" 2>&1
```

Si no existen, créalos con el contenido exacto de la Tarea 1 de T1 (no un formato distinto).

- [ ] **Step 2: Verifica el modelo de autorización real de `/indicadores` — es client-side, no de ruta**

```bash
grep -n "requireAuth\|authorizePermission" src/Controllers/Gestion/IndicadoresController.php
```

Esperado (ya comprobado el 2026-08-04): `src/Controllers/Gestion/IndicadoresController.php:14` solo llama `$this->requireAuth()`. **No hay `authorizePermission()` ni chequeo de capacidad en el controlador de la vista.** Cualquier rol autenticado con sesión puede cargar `/indicadores`.

El filtro real está en el JavaScript embebido en la vista:

```bash
grep -n "ROLES_SIN_INFORME_INDICADORES\|permiso_canonico" views/indicadores/indicadores.view.php
```

`views/indicadores/indicadores.view.php:114` declara `var ROLES_SIN_INFORME_INDICADORES = ["G", "S", "SG", "C"]`, y `:150-154` lee `document.getElementById('permiso_canonico').value` y, si está en esa lista, reemplaza el contenedor por un mensaje (`El informe de indicadores no está disponible para tu perfil`) en vez de montar el `<iframe>`. Verifica que ese `input#permiso_canonico` efectivamente lleva el rol de sesión (búscalo en la vista o en el layout que la incluye) y no un valor por defecto o cacheable.

Esto es una diferencia de arquitectura verificable con la API asociada: `src/Controllers/Api/IndicadoresApiController.php:27` sí exige `$this->authorizePermission('lps.indicadores.ver')` en `generar()`. **La página carga para cualquier rol; el embed se oculta en el cliente; el endpoint que regenera los indicadores sí tiene guard de servidor.** Tres capas, tres criterios distintos — descríbelo como tal, no lo simplifiques a «está protegido».

- [ ] **Step 3: Verifica la limitación de Power BI documentada, cítala desde la decisión**

`memoria/decisiones/powerbi-indicadores.md:12` documenta que el publish-to-web **NO admite filtrado por proyecto vía URL ni por la JS API de Power BI**, así que «todos los proyectos ven el mismo reporte». Verifica en `views/indicadores/indicadores.view.php:111` que la URL del iframe (`POWER_BI_REPORT_URL`) es una constante fija, sin interpolar `project_id`, `dbName` ni ningún parámetro de sesión — confirma con:

```bash
grep -n "POWER_BI_REPORT_URL\|project_id\|dbName" views/indicadores/indicadores.view.php | head -20
```

Este es el escenario obligatorio del prompt: descríbelo explícitamente como **la única excepción conocida y documentada** a la regla de aislamiento por `project_id` de toda consulta operativa. No es un hallazgo nuevo — ya está en `docs/EXPERIMENTS.md` o en la decisión si allí se registró; si no está en el backlog, añádelo ahora citando `memoria/decisiones/powerbi-indicadores.md` como origen y no como hallazgo nuevo de esta pasada.

- [ ] **Step 4: Enumera y redacta los escenarios de Indicadores**

Como mínimo, con el formato del README:

- `BI-001` — Rol permitido (A/D/R/DCV/OT/V) abre `/indicadores` y ve el iframe de Power BI.
- `BI-002` — Rol restringido (G) abre `/indicadores`: la página carga con código 200 (no 403), pero el contenedor muestra el mensaje de «no disponible» en vez del iframe. Repite como escenarios separados o con tabla de roles para S, SG, C — los cuatro son el requisito explícito de esta tarea.
- `BI-003` — Cualquier rol autorizado ve el mismo reporte que un usuario de otro proyecto (la excepción del Step 3), con la cita de la decisión.
- `BI-004` — `POST /api/indicadores/generar` con un rol sin `lps.indicadores.ver`: describe el código de respuesta real de `authorizePermission()` (verifica en `src/Controllers/BaseController.php` qué hace ese método al fallar — redirect, 403 JSON, excepción) citando la línea.
- `BI-005` — `POST /api/indicadores/generar` con `db`/`semana` faltantes: `src/Controllers/Api/IndicadoresApiController.php:31-33` responde 400 con `respuesta: ERROR`.

- [ ] **Step 5: Registra hallazgos y sigue**

Si al verificar el Step 2 aparece algo no documentado (p. ej. el `input#permiso_canonico` no reflejando el rol real de sesión), regístralo en `docs/EXPERIMENTS.md` con severidad alta (bypass de un control que se cree de seguridad pero es solo de UI) y sigue sin tocar `src/` ni `views/`.

---

### Task 2: Escenarios de la Torre de Control BI

**Files:**
- Create: `docs/flujos/lectura-torre-de-control.md`
- Read: `src/Support/BiProjectScope.php`, `src/Controllers/Bi/BiViewController.php`, `src/Controllers/Api/BiControlTowerApiController.php` líneas 1-120, `src/View/Components/BiAccessComponent.php`, `memoria/arquitectura/torre-de-control-bi.md`

**Interfaces:**
- Consumes: el formato del escenario.
- Produces: `id` `BI-0NN` de Torre de Control.

- [ ] **Step 1: Verifica la resolución de proyectos autorizados — esta es la pieza central del aislamiento**

`src/Support/BiProjectScope.php:73-97` (`authorizedProjects()`) consulta `project_members` filtrando `p.Area IN ('Construccion', 'Pre-Construccion')`, `p.Activo = 1`, `(p.Acceso = 1 OR pm.role IN ('A', 'D', 'P'))`, y además descarta cada fila cuyo rol no tenga la capacidad `lps.indicadores.ver` (`:89`, `$this->rbac->can('lps.indicadores.ver', $row['role'])`). Esto significa que **G, S, SG, C —que no tienen `lps.indicadores.ver` en `RbacCatalog::fallbackPermissionsByRole()` (verificado el 2026-08-04: sus arrays en `src/Security/RbacCatalog.php:307-343` no incluyen esa clave)— quedan con la lista de proyectos autorizados vacía**, no solo con el reporte oculto.

Confirma la consecuencia en `resolve()` (`:25-48`): si `$allowed === []`, la línea `:47` lanza `DomainException('No tienes proyectos autorizados para Control Tower.')`, y `BiViewController::abortUnauthorizedProjectScope()` (`src/Controllers/Bi/BiViewController.php:177-183`) responde **HTTP 403** con ese mensaje.

Esto es el contraste explícito que pide el prompt: **`/indicadores` deja cargar la página a esos cuatro roles y oculta el embed en el cliente (Task 1); `/bi/*` responde 403 de servidor antes de renderizar nada.** Mismo grupo de roles restringidos, dos arquitecturas de negación distintas dentro del mismo módulo BI. Redáctalo como su propio escenario comparativo, con las citas de ambos lados.

- [ ] **Step 2: Verifica el multiproyecto y la petición cruzada**

`BiProjectScope::resolve()` (`:25-48`) acepta `project_ids` explícitos por query string y valida con `array_diff($requested, $allowed) !== []` que ninguno esté fuera de lo autorizado, lanzando la misma `DomainException` si lo está. Este es el escenario de intento de acceso cruzado: un usuario del proyecto A pide `?project_ids=<id-del-proyecto-B>`. Verifica el comportamiento pidiendo una prueba de lectura del flujo completo (no hace falta ejecutarlo en esta tarea si Task 3 lo sube a ejecutable).

- [ ] **Step 3: Verifica qué rol queda en pantalla cuando hay más de un proyecto**

`BiProjectScope::reportRole()` (`:135-159`) devuelve el string literal `'MULTI'` cuando `count($projectIds) !== 1`. Describe qué consume `role='MULTI'` en las vistas de `views/bi/` (busca con `grep -rn "role.*MULTI\|'MULTI'" views/bi/`) — si algo espera uno de los diez roles de `RbacCatalog` y recibe `MULTI` sin manejarlo, es hallazgo.

- [ ] **Step 4: Enumera y redacta el resto de los escenarios**

Como mínimo, con el formato del README:

- `BI-0NN` — Rol A o D con un solo proyecto en sesión abre `/bi/control-tower`: `BiViewController::controlTower()` → `renderView('overview', ...)`.
- `BI-0NN` — El mismo rol abre cualquiera de los ocho destinos (`programaGeneral`, `intermedia`, `semanal`, `pdc`, `contratistas`, `responsables`, `curvaS`, más el overview) — no hace falta un escenario por cada uno si el patrón de autorización es idéntico; descríbelo una vez y enumera los ocho `reportKey` de `BiViewController::SHELL_MODULE_LABELS` (`:26-35`) como cobertura.
- `BI-0NN` — Rol G/S/SG/C intenta cualquier ruta `/bi/*`: 403 (Step 1).
- `BI-0NN` — La coherencia de cifras: elige **un** número concreto y trazable — por ejemplo el PAC del `overview` — y compara la consulta que usa `ControlTowerService` contra la tabla operativa real (`programacion_semanal`) para un proyecto de prueba. Si esta pasada no llega a ejecutar la consulta y comparar el número real, declara explícitamente «no comprobable en lectura — requiere datos de un proyecto real en runtime» en vez de asumir que coincide.
- `BI-0NN` — El componente `BiAccessComponent` (`src/View/Components/BiAccessComponent.php:26`) que otros módulos usan para mostrar/ocultar el enlace «BI ...» (visto en `views/pdc/pdc.view.php:47`): verifica que usa el mismo `lps.indicadores.ver` y por tanto el enlace desaparece para los mismos cuatro roles, cerrando el círculo de coherencia entre el enlace de entrada y el 403 de la ruta.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 3: Las pruebas ejecutables de los críticos

**Files:**
- Create: `e2e/tests/biblia/lectura.spec.mjs`
- Read: `e2e/playwright.config.mjs`, `e2e/support/`, `e2e/tests/biblia/transversal.spec.mjs` (si T1 ya corrió)

**Interfaces:**
- Consumes: los `id` de las tareas 1-2.
- Produces: pruebas cuyo título empieza por el `id` del escenario que cubren.

- [ ] **Step 1: Elige qué sube al nivel ejecutable**

Criterio del spec: toca permisos, muta datos, o cierra/abre un periodo. T5 no muta nada ni cierra periodos, así que el criterio se reduce a **permisos** — y es exactamente donde está el hallazgo comparativo de la Task 2, Step 1. Sube:

- el contraste `/indicadores` (200, embed oculto) vs `/bi/control-tower` (403) para el mismo rol restringido, en una sola prueba que hace ambas peticiones con la misma sesión — es la forma más barata de blindar el hallazgo contra que alguien lo revierta sin darse cuenta;
- un rol permitido y uno denegado en `/bi/control-tower` (patrón `test.R` / rol sin `lps.indicadores.ver` — usa `test.V` si `V` tiene la capacidad, verificado en `RbacCatalog:346-353` que sí la tiene; para el denegado hace falta una cuenta seed con rol `G`/`S`/`SG`/`C` — comprueba con `grep -n "DEV_DOOR_USERS" .env` si existe alguna, y si no, documenta el hueco en vez de inventar una cuenta).

Escribe en el documento por qué cada uno sube y por qué los demás (los ocho `reportKey` individuales, la coherencia de cifras) no.

- [ ] **Step 2: Lee las fixtures antes de escribir**

```bash
ls e2e/support/; sed -n '1,40p' e2e/playwright.config.mjs
grep -n "DEV_DOOR_USERS" .env 2>/dev/null
```

Si no hay cuenta seed con rol restringido (G/S/SG/C) disponible en `DEV_DOOR_USERS`, documenta esa limitación en el spec y en el documento de escenarios en vez de forzar una prueba con datos inventados.

- [ ] **Step 3: Escribe las pruebas con el `id` en el título**

```javascript
import { test, expect } from '@playwright/test';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('BI-0XX · Indicadores oculta el embed en cliente pero Torre de Control responde 403 para el mismo rol restringido', async ({ page, request }) => {
  await page.goto('/dev/entrar?u=<cuenta-rol-restringido>&p=PDC%20Sandbox%20E2E');

  const indicadoresResp = await page.goto('/indicadores');
  expect(indicadoresResp.status()).toBe(200);
  await expect(page.locator('.ind-powerbi-denied')).toBeVisible();

  const biResp = await page.goto('/bi/control-tower');
  expect(biResp.status()).toBe(403);
});
```

Ajusta el selector `.ind-powerbi-denied` y la cuenta tras confirmarlos en el Step 2; el fragmento es el patrón, no una promesa.

- [ ] **Step 4: Corre las pruebas contra el contenedor**

```bash
npx playwright test e2e/tests/biblia/lectura.spec.mjs --config=e2e/playwright.config.mjs --workers=1
```

Esperado: verde. Si falla, no toques la prueba para que pase: o el escenario está mal descrito o el código incumple lo documentado — hallazgo al backlog.

- [ ] **Step 5: Anota en cada escenario cubierto su prueba**

En los dos documentos de las tareas 1-2, el campo «Verificación» pasa de «ejecutable — pendiente» a citar el archivo y el título del test.

---

### Task 4: Cierre de la tanda

**Files:**
- Modify: `docs/EXPERIMENTS.md`, `docs/flujos/README.md`, el mapa de `memoria/mapas/` que cubra BI, `memoria/log.md`
- Modify: `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: los hallazgos de las tareas 1-3.
- Produces: la tanda T5 cerrada y medible.

- [ ] **Step 1: Prioriza el backlog**

Cada hallazgo con Impact, Confidence y Ease de 1 a 10 y su ICE calculado. El contraste de autorización `/indicadores` vs `/bi/*` (Task 2, Step 1) necesita decisión del usuario: ¿ambos deberían responder 403, o `/indicadores` está bien como está porque el embed es genérico y no hay nada que proteger detrás salvo la marca? Márcalo para esa decisión explícitamente.

- [ ] **Step 2: Teje la biblia en la wiki**

```bash
ls memoria/mapas/
```

Añade el enlace a `docs/flujos/lectura-indicadores` y `docs/flujos/lectura-torre-de-control` en el mapa que corresponda, con la misma explicación de capas (biblia = comportamiento esperado, mapa = dónde vive el código) que T1 y T4.

- [ ] **Step 3: Actualiza los dos trackers**

En `docs/flujos/README.md`, marca T5 como cerrada con su recuento de escenarios. En `docs/IMPROVE-APP-PLAN.md`, añade a `## Key Decisions` la fila del cierre de T5.

- [ ] **Step 4: Corre el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos`.

- [ ] **Step 5: Deja la línea de bitácora**

Una línea `ingest` en `memoria/log.md` con: cuántos escenarios describe T5, cuántos se verificaron por lectura, cuántos subieron a ejecutable, y cuántos hallazgos entraron al backlog (incluido el contraste de autorización). Números medidos, no estimados.

---

## Verificación final de T5

```bash
npx playwright test e2e/tests/biblia/lectura.spec.mjs --config=e2e/playwright.config.mjs --workers=1
npm run test:wiki
```

Y comprueba las condiciones de hecho del spec (`docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md` §Condición de hecho) que aplican a T5: escenarios descritos y verificados con cita, críticos con prueba citando su `id`, hallazgos en el backlog sin arreglar (con el contraste de autorización marcado para decisión del usuario), wiki enlazada y en verde.

**Sobre la validación en navegador:** T5 toca superficie observable (qué embed se ve, qué código de estado responde cada ruta), así que la evidencia de Playwright es la validación exigida. No hace falta recorrido manual adicional salvo para confirmar el selector del mensaje de «informe no disponible» antes de escribir la prueba (Task 3, Step 3).

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25 (dos pasadas). La primera encontró la Task 3 sin
ejecutar: `e2e/tests/biblia/lectura.spec.mjs` no existía y el hueco no estaba documentado — ver
[[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]], Tarea 0, y
[[memoria/trampas/el-goal-cierra-un-alcance-menor-que-el-del-plan]].

**Cerrado el mismo día.** La Task 3 se ejecutó: `e2e/tests/biblia/lectura.spec.mjs` con dos
escenarios (`BI-007`, `BI-008`), verificados en ejecución contra el contenedor con la cuenta
seed `test.C` (rol `C`, uno de los cuatro restringidos). Al escribir la prueba se destapó que el
plan y la propia biblia asumían un `/bi/control-tower` respondiendo 403 para el rol restringido, y
hoy responde **404** — `BiPreviewAccessPolicy`, un gate de módulo completo añadido el 2026-08-13
(posterior a este plan), corta antes de llegar al filtro de proyecto que sí da 403. Y `/indicadores`
ya no expone el embed en cliente: corta en servidor con 403 desde el 2026-08-06 (`4b1a2be0`) — el
propio `BI-005` de la biblia seguía describiendo el estado anterior a ese fix como si fuera hoy.
Las dos divergencias se corrigieron en `docs/flujos/lectura-bi.md` (`BI-004` cita reubicada,
`BI-005` reescrita, `BI-007`/`BI-008` nuevos) en el mismo cierre — la cláusula de autoridad del
propio documento lo exige.

`docs/flujos/README.md` marca T5 cerrada con 8 escenarios y 2 pruebas en verde. Queda declarado
`BI-006` (comparación de cifras contra su origen) sin resolver, tal como el plan permite.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
