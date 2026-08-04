# Biblia de flujos · Tanda T1 (transversal) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la entrada a la aplicación —autenticación, selección de proyecto y resolución de capacidades por rol— tenga cada uno de sus escenarios descrito, verificado contra el código con cita, y los críticos cubiertos por prueba ejecutable.

**Architecture:** Se crea `docs/flujos/` con su README (la cláusula de autoridad y el formato del escenario) y tres documentos de tanda: `transversal-autenticacion.md`, `transversal-proyecto.md`, `transversal-rbac.md`. Cada escenario lleva un `id` estable que las pruebas de `e2e/` citan en su título, de modo que un fallo apunte a la línea de biblia que se incumple. Los hallazgos no se arreglan: van a `docs/EXPERIMENTS.md`.

**Tech Stack:** Markdown versionado · Playwright (`e2e/`, config propia en `e2e/playwright.config.mjs`) · la puerta de servicio `/dev/entrar` para abrir sesión con rol real · PHP 8.3 en Docker para inspección.

## Global Constraints

- **Cláusula de autoridad:** si la biblia y el código divergen, **es un bug de uno de los dos y hay que resolverlo**; no se corrige la biblia en silencio para que cuadre con el código.
- **Verificar, no sospechar:** toda afirmación comprobable lleva cita `archivo:línea` leída en la sesión. Lo que no se pueda comprobar leyendo se declara «no comprobable en lectura»; nunca se da por bueno.
- **Los hallazgos se registran y la pasada continúa.** Nada de arreglar en caliente. Si la duda es *cuál es la conducta correcta*, la decisión es del usuario.
- **Sesión local solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>`, cuentas `test.A` (rol A), `test.R` (rol R), `test.V` (rol V). **Nunca** teclear credenciales en `/login` ni pedirle a una persona que inicie sesión (`AGENTS.md`).
- **Viewport de validación:** 1180×820, **dark only**. No se genera evidencia de móvil, tablet ni tema `linen`.
- **Rol permitido y rol denegado:** todo escenario de capacidad cubre al menos uno de cada (`AGENTS.md`, routing de RBAC).
- **Formato del `id`:** `<PREFIJO>-<NNN>` con prefijo `AUTH`, `PROY` o `RBAC` y número de tres dígitos, estable para siempre. Un escenario retirado conserva su número; no se reutiliza.
- **No se hace commit sin petición explícita del usuario** (`AGENTS.md` §Publicación).

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `docs/flujos/README.md` (nuevo) | La cláusula de autoridad, el formato del escenario, el índice de tandas y cómo se cita un `id` desde una prueba. |
| `docs/flujos/transversal-autenticacion.md` (nuevo) | Escenarios `AUTH-*`: login, logout, expiración de sesión, recuperación de contraseña, puerta de servicio, modo mantenimiento. |
| `docs/flujos/transversal-proyecto.md` (nuevo) | Escenarios `PROY-*`: qué proyectos ve cada cuenta, selección, y qué pasa sin proyecto en sesión. |
| `docs/flujos/transversal-rbac.md` (nuevo) | Escenarios `RBAC-*`: una entrada por capacidad, con su tabla de roles permitidos y denegados. |
| `e2e/tests/biblia/transversal.spec.mjs` (nuevo) | Las pruebas de los escenarios críticos de T1, cada `test()` titulado con su `id`. |
| `docs/EXPERIMENTS.md` (nuevo) | El backlog único compartido con `improve-app`, con columna ICE. |
| `memoria/mapas/rbac-y-rutas.md` (modificar) | Enlaza la biblia desde el mapa del área. |

---

### Task 1: La carpeta y su contrato

**Files:**
- Create: `docs/flujos/README.md`
- Create: `docs/EXPERIMENTS.md`

**Interfaces:**
- Consumes: nada.
- Produces: el formato del escenario y los prefijos `AUTH`/`PROY`/`RBAC` que las tareas 2-5 usan; la tabla `## Experiment Backlog` que la tarea 6 rellena.

- [ ] **Step 1: Escribe `docs/flujos/README.md`**

Debe contener, en este orden:

1. **Qué es y qué autoridad tiene** — con la cláusula literal: «Si la biblia y el código divergen, es un bug de uno de los dos y hay que resolverlo». Y la diferencia explícita con `memoria/`, cuya regla es la contraria (código > AGENTS.md > wiki).
2. **El formato del escenario** — la tabla de campos: `id`, rol, precondiciones, pasos (cada uno nombrando la variable, endpoint o capacidad que toca), resultado esperado en pantalla **y** en datos, y verificación.
3. **Los dos niveles de verificación** — lectura con cita `archivo:línea` para todos; Playwright en `e2e/` para los que tocan permisos, mutan datos o cierran un periodo.
4. **Cómo se cita un `id` desde una prueba** — el título del `test()` empieza por el `id`, por ejemplo `test('RBAC-004 · Visualizador no ve los controles de semana', ...)`.
5. **El índice de las cinco tandas** con su estado.
6. **La regla de los hallazgos** — se registran en `docs/EXPERIMENTS.md` y la pasada continúa.

- [ ] **Step 2: Crea `docs/EXPERIMENTS.md` con el esqueleto de la plantilla**

Secciones obligatorias, en este orden y sin renombrar (contrato entre journeys de `improve-app`):

```markdown
# Experiments

## Experiment Cards

## Experiment Backlog

| Idea | Origen | Impact | Confidence | Ease | ICE | Owner | Status |
|---|---|---|---|---|---|---|---|
```

La columna `Origen` es la que une los dos proyectos: lleva el `id` del escenario que destapó el hallazgo, o la fase de `improve-app` que lo produjo.

- [ ] **Step 3: Comprueba que el README no promete nada que no exista**

Léelo entero y verifica que cada ruta que nombra existe:

```bash
ls docs/flujos/ e2e/tests/
```

Esperado: el README solo cita archivos ya creados o los que este plan crea en las tareas 2-5. Si nombra una tanda futura, debe decir «pendiente».

---

### Task 2: Escenarios de autenticación

**Files:**
- Create: `docs/flujos/transversal-autenticacion.md`
- Read: `src/Controllers/Auth/LoginController.php`, `src/Core/SessionMiddleware.php`, `src/Core/DevDoor.php`, `src/Core/MaintenanceMode.php`

**Interfaces:**
- Consumes: el formato del escenario de Task 1.
- Produces: los `id` `AUTH-001`…`AUTH-0NN`, que la tarea 6 cita en las pruebas.

- [ ] **Step 1: Enumera los caminos reales antes de describir ninguno**

Las rutas de este flujo, ya generadas en `memoria/arquitectura/autenticacion.md`:

| Verbo | Ruta | Destino |
|---|---|---|
| GET/POST | `/login` | `LoginController::index` / `::login` |
| GET | `/logout` | `LoginController::logout` |
| GET/POST | `/password/forgot` | `PasswordResetController::forgot` / `::sendLink` |
| GET/POST | `/password/reset` | `PasswordResetController::reset` / `::update` |
| POST | `/password/update` | `LoginController::updatePassword` |
| GET | `/dev/entrar` | `DevDoorController::enter` |
| GET/POST | `/_aia/operacion/7f3c9b` | `LoginController::index` / `::maintenanceLogin` |

Escribe la lista de escenarios **antes** de redactarlos, y que incluya obligatoriamente los caminos que no son felices: credenciales inválidas, sesión expirada por inactividad, petición que espera JSON con la sesión caída, puerta de servicio con `DEV_DOOR=0`, y acceso a una ruta protegida sin sesión.

- [ ] **Step 2: Verifica el guardián de sesión antes de describirlo**

```bash
docker compose exec -T app php -r 'echo App\Core\SessionMiddleware::idleTimeoutSeconds();'
```

Y lee `src/Core/SessionMiddleware.php:91-113`: `expectsJsonResponse()` decide si una sesión caída responde JSON con `redirect` o manda una cabecera `Location`. **Los dos comportamientos son escenarios distintos** (`AUTH` de navegación y `AUTH` de API) y hay que describirlos por separado: el segundo es el que rompe las grillas si se equivoca.

- [ ] **Step 3: Redacta los escenarios con el formato del README**

Cada uno con `id`, rol, precondiciones, pasos citando el método del controlador, resultado esperado en pantalla y en datos, y la cita `archivo:línea` que lo respalda. Ejemplo del nivel de detalle exigido:

```markdown
### AUTH-004 · La sesión caduca por inactividad durante una petición de datos

- **Rol:** cualquiera con sesión abierta.
- **Precondiciones:** sesión iniciada; han pasado más segundos que `SessionMiddleware::idleTimeoutSeconds()` desde la última petición.
- **Pasos:**
  1. La grilla lanza una petición con la cabecera propietaria `X-AIA-Expect-Json: 1` (**no** vale `Accept: application/json` ni `X-Requested-With`; verificado el 2026-08-04 en `SessionMiddleware.php:91-96`).
  2. `SessionMiddleware::check()` detecta el vencimiento y llama a `finishUnauthorized()`.
- **Resultado esperado:** respuesta JSON con `redirect` al login **y sin cuerpo HTML**; la grilla no debe intentar pintar la respuesta como datos. En datos: ningún cambio.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:98-113`. Ejecutable — pendiente (ver Task 6).
```

- [ ] **Step 4: Comprueba que ningún escenario es transcripción del código**

Relee cada uno y pregúntate: ¿afirma algo que el código **debe** cumplir, de forma que pudiera discreparse de él? Si un escenario solo repite lo que la función hace, sobra: bórralo. Un escenario útil se puede contradecir; una transcripción, no.

- [ ] **Step 5: Registra los hallazgos, sin arreglarlos**

Si al verificar aparece una divergencia, añade una fila a `docs/EXPERIMENTS.md` `## Experiment Backlog` con el `id` del escenario en `Origen` y su ICE, y **sigue**. No toques `src/`.

---

### Task 3: Escenarios del selector de proyecto

**Files:**
- Create: `docs/flujos/transversal-proyecto.md`
- Read: `src/Controllers/Core/ProjectSelectorController.php`, `src/Core/DevDoor.php`

**Interfaces:**
- Consumes: el formato del escenario de Task 1.
- Produces: los `id` `PROY-001`…`PROY-0NN`.

- [ ] **Step 1: Verifica cómo se resuelve la visibilidad de proyectos**

Lee `src/Controllers/Core/ProjectSelectorController.php:36` y `:102`: ambas consultas parten de `project_members`. La segunda vive en `enterProject()`, cuyo comentario (`:81-85`) declara que **la normalización del rol y el respeto a `Acceso=0` deben ser idénticos** a los del camino normal.

Eso es una **invariante entre dos caminos** y merece su propio escenario: *entrar por la puerta de servicio y entrar por el selector deben dejar la misma sesión*. Descríbelo y verifícalo comparando ambas consultas línea a línea; si divergen, es hallazgo.

- [ ] **Step 2: Enumera los escenarios obligatorios**

Como mínimo: listar proyectos de una cuenta con varios; cuenta con `Acceso=0` en un proyecto (no debe verlo); selección válida; intento de seleccionar un proyecto donde la cuenta no es miembro; y **acceso a una ruta operativa sin proyecto en sesión**, que es el que más rutas afecta.

- [ ] **Step 3: Redacta con el formato del README**

Cada escenario nombra explícitamente **qué queda en `$_SESSION`** tras el paso (clave y significado), porque es el estado del que dependen las otras cuatro tandas.

- [ ] **Step 4: Comprueba la invariante en runtime por la puerta de servicio**

```bash
docker compose ps
```

Con el stack arriba, abre las dos rutas con la misma cuenta y compara la sesión resultante:

```
http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E
http://localhost:8081/proyectos
```

Esperado: el rol en sesión es el mismo por ambos caminos (el real de `project_members`). Si difieren, hallazgo con severidad alta: es una vía de escalada de privilegios.

- [ ] **Step 5: Registra hallazgos y sigue**

Igual que en Task 2: fila en `docs/EXPERIMENTS.md`, sin tocar `src/`.

---

### Task 4: Escenarios de RBAC, una entrada por capacidad

**Files:**
- Create: `docs/flujos/transversal-rbac.md`
- Read: `src/Security/RbacManager.php`, `src/Security/RbacCatalog.php`, `admin/src/Core/RoleManager.php`

**Interfaces:**
- Consumes: el formato del escenario de Task 1.
- Produces: los `id` `RBAC-001`…`RBAC-017`, uno por capacidad.

- [ ] **Step 1: Extrae la matriz real, no la recordada**

Las 17 capacidades de `RbacManager::getCapabilities()`:

`canAutoDefineContracts` · `canDeleteRows` · `canEditAmbiental` · `canEditConstraints` · `canEditFinancial` · `canEditGeneralProgram` · `canEditMediumTerm` · `canEditPastGeneralProgram` · `canEditSST` · `canEditWeeklyProgram` · `canManageContracts` · `canManageGeneralProgram` · `canManageMediumTermProgram` · `canManagePdC` · `canManageWeeklyProgram` · `canManageWeeks` · `canSeeReports`

Vuelca la lista de roles de cada una **leyendo el `in_array` correspondiente**, no de memoria. Ejemplos ya verificados el 2026-08-04:

```php
$canManageWeeks = in_array($role, ['A', 'D', 'OT', 'R', 'DCV']);
$canEditGeneralProgram = in_array($role, ['A', 'D', 'R', 'DCV']);
$canEditWeeklyProgram = in_array($role, ['A', 'D', 'R', 'S', 'G', 'SG']);
'canDeleteRows' => in_array($role, ['A', 'D']),
'canEditPastGeneralProgram' => in_array($role, ['A', 'D']),
```

- [ ] **Step 2: Busca los alias de capacidad, que son trampa**

`canManageGeneralProgram` toma el valor de `$canEditGeneralProgram`: **son la misma variable con dos nombres**. Comprueba si hay más pares así y descríbelos como tales — un consumidor que crea que son distintos está equivocado, y ese es exactamente el tipo de afirmación que la biblia debe fijar.

- [ ] **Step 3: Redacta un escenario por capacidad**

Formato de cada uno: qué permite en términos de negocio (no «devuelve true»), la tabla de roles permitidos y denegados leída del código, y **al menos un consumidor real** de esa capacidad citado con `archivo:línea`. Una capacidad sin ningún consumidor es un hallazgo: significa que se declara un permiso que nadie comprueba.

- [ ] **Step 4: Verifica la normalización del rol**

Todo control de capacidad debe normalizar antes con `Admin\Core\RoleManager::cleanCargo()` (`AGENTS.md`). Comprueba en el código qué pasa con un rol desconocido o con alias legado:

```bash
docker compose exec -T app php -r 'require "vendor/autoload.php"; var_dump(Admin\Core\RoleManager::cleanCargo("Residente de Obra"));'
```

El resultado real de ese comando va al escenario; si un rol no reconocido no cae en solo-lectura, es hallazgo de seguridad.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 5: Las pruebas ejecutables de los críticos

**Files:**
- Create: `e2e/tests/biblia/transversal.spec.mjs`
- Read: `e2e/playwright.config.mjs`, `e2e/support/` (fixtures existentes)

**Interfaces:**
- Consumes: los `id` de las tareas 2-4.
- Produces: pruebas cuyo título empieza por el `id` del escenario que cubren.

- [ ] **Step 1: Elige qué sube al nivel ejecutable**

Criterio del spec: toca permisos, muta datos, o cierra/abre un periodo. Para T1 eso son, como mínimo:

- la invariante de sesión entre la puerta de servicio y el selector (`PROY-*`);
- una capacidad con rol permitido y otro denegado, visible en pantalla (`RBAC-*`), reusando el patrón ya verificado: `test.R` ve los controles de semana en `/programa-general` y `test.V` no;
- la sesión caída que responde JSON (`AUTH-*`), porque es la que rompe grillas en silencio.

Escribe en el documento **por qué** cada uno sube y por qué los demás no: un recorte silencioso se lee como cobertura completa.

- [ ] **Step 2: Lee las fixtures antes de escribir**

```bash
ls e2e/support/; sed -n '1,40p' e2e/playwright.config.mjs
```

Reutiliza el fixture de credenciales y el `baseURL` existentes. **No** dupliques valores privilegiados en el spec nuevo (`docs/qa/workflows.md`): las credenciales se resuelven en tiempo de ejecución.

- [ ] **Step 3: Escribe las pruebas con el `id` en el título**

```javascript
import { test, expect } from '@playwright/test';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('RBAC-006 · Residente ve los controles de semana y Visualizador no', async ({ page }) => {
  await page.goto('/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E');
  await page.goto('/programa-general');
  await expect(page.getByRole('button', { name: /crear semana/i })).toBeVisible();

  await page.goto('/dev/entrar?u=test.V&p=PDC%20Sandbox%20E2E');
  await page.goto('/programa-general');
  await expect(page.getByRole('button', { name: /crear semana/i })).toHaveCount(0);
});
```

Ajusta los selectores a los reales de la vista tras inspeccionarla; el nombre accesible de arriba es el patrón, no una promesa.

- [ ] **Step 4: Corre las pruebas contra el contenedor**

```bash
npx playwright test e2e/tests/biblia/transversal.spec.mjs --config=e2e/playwright.config.mjs --workers=1
```

Esperado: verde. **Si una falla, no toques la prueba para que pase**: o el escenario está mal descrito (corrige la biblia) o el código incumple (hallazgo al backlog). Esa bifurcación es el motivo de todo el proyecto.

- [ ] **Step 5: Anota en cada escenario cubierto su prueba**

En los documentos de las tareas 2-4, el campo «Verificación» de los escenarios cubiertos pasa de «ejecutable — pendiente» a citar el archivo y el título del test.

---

### Task 6: Cierre de la tanda

**Files:**
- Modify: `docs/EXPERIMENTS.md`, `docs/flujos/README.md`, `memoria/mapas/rbac-y-rutas.md`, `memoria/log.md`
- Modify: `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: los hallazgos de las tareas 2-5.
- Produces: la tanda T1 cerrada y medible.

- [ ] **Step 1: Prioriza el backlog**

Cada hallazgo con Impact, Confidence y Ease de 1 a 10 y su ICE calculado. Ordena por ICE descendente. **La confianza baja cuando el hallazgo depende de tu interpretación** de cuál es la conducta correcta; esos van marcados para decisión del usuario.

- [ ] **Step 2: Teje la biblia en la wiki**

En `memoria/mapas/rbac-y-rutas.md`, sección «Qué manda», añade el enlace a `docs/flujos/transversal-rbac` explicando que **la biblia describe el comportamiento esperado y el mapa describe dónde vive el código**: son capas distintas, no duplicados.

- [ ] **Step 3: Actualiza los dos trackers**

En `docs/flujos/README.md`, marca T1 como cerrada con su recuento de escenarios. En `docs/IMPROVE-APP-PLAN.md`, añade a `## Key Decisions` la fila del cierre de T1 y su aporte a las fases 3 y 9.

- [ ] **Step 4: Corre el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos`. Si el enlace nuevo sale roto, recuerda que los wikilinks no llevan extensión.

- [ ] **Step 5: Deja la línea de bitácora**

Una línea `ingest` en `memoria/log.md` con: cuántos escenarios describe T1, cuántos se verificaron por lectura, cuántos subieron a ejecutable, y cuántos hallazgos entraron al backlog. Los números medidos, no estimados.

---

## Verificación final de T1

```bash
npx playwright test e2e/tests/biblia/transversal.spec.mjs --config=e2e/playwright.config.mjs --workers=1
npm run test:wiki
```

Y comprueba las seis condiciones de hecho del spec (`docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md` §Condición de hecho) que aplican a T1: carpeta con su cláusula, escenarios descritos y verificados con cita, críticos con prueba citando su `id`, hallazgos en el backlog sin arreglar, wiki enlazada y en verde.

**Sobre la validación en navegador:** T1 sí toca superficie observable (los controles que un rol ve y otro no), así que la evidencia de Playwright es la validación exigida. No hace falta recorrido manual adicional.
