# Frente 1 · Tanda 1A — Seguridad y permisos: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar los 13 hallazgos de seguridad y permisos del backlog — los únicos donde un fallo no molesta, sino que permite lo que no debe o esconde lo que sí.

**Architecture:** Se construye primero el gate que compara las dos matrices RBAC (servidor y cliente), porque hoy nada las compara y varios de los hallazgos siguientes son instancias concretas de esa divergencia. Ese gate nace **rojo**, y las tareas posteriores lo van poniendo verde: es TDD al nivel de la tanda. Después se agrupan los arreglos por el archivo que gobiernan —capacidades inertes, candado de semana, selector de proyectos— en vez de por puntuación, para que cada tarea toque una sola pieza y pueda revisarse sola.

**Tech Stack:** PHP 8.3 en `docker compose exec app`, tests autoejecutables de `tests/test_*.php`, Node para el gate de paridad, Playwright para lo que solo se ve en navegador.

**Spec:** [`2026-08-10-programa-cierre-pendientes-design.md`](../specs/2026-08-10-programa-cierre-pendientes-design.md), Frente 1, tanda 1A.

## Global Constraints

- **Docker Compose es el runtime.** Todo PHP con `docker compose exec app`. Nunca un PHP del host.
- **La sesión local se abre por la puerta de servicio**, nunca por `/login`: `http://localhost:8081/dev/entrar?u=<cuenta>`. Cuentas habilitadas: `test.A`, `test.R`, `test.V`, `test.C`, `test.D`.
- **Prepared statements siempre** a través de la capa `Database`. Nada de SQL con datos de usuario.
- **CSRF en toda mutación autenticada.** No se retira de ningún endpoint.
- **Normaliza roles con `Admin\Core\RoleManager::cleanCargo()`**, y conserva solo lectura como fallback seguro (`AGENTS.md` §Seguridad).
- **Un rol permitido y uno denegado** verificados en toda ruta protegida que se toque (`AGENTS.md` §Routing).
- **Todo gate se entrega con una mutación que lo pone rojo, ejecutada.**
- **Todo paso que quite algo de una lista mide qué cobertura pierde**, no solo qué gana.
- Los datos de prueba se restauran y se verifica con una consulta.
- Commits atómicos, uno por tarea. Nunca `.env`.
- **Consulta hacia arriba** toda decisión que cambie alcance, toque un contrato, borre algo o se desvíe del plan. Anotar una duda en el informe no es consultarla.

## Los 13 hallazgos y dónde caen

| ICE | Hallazgo | Tarea |
|---|---|---|
| 490 | `canDeleteRows` no la consulta nadie, y protege algo destructivo | 2 |
| 400 | Reabrir semana: cliente, servidor e intención del usuario dicen tres cosas distintas | 3 |
| 400 | La barra de avance del selector es `rand(0,100)` | 5 |
| 384 | Sesión caducada = trabajo perdido sin error entendible en las grillas | 7 |
| 360 | `canSeeReports` vale `true` para los diez roles y nadie la usa | 2 |
| 336 | `guard(allowIfConfirmed: true)` retorna sin comprobar nada | 4 |
| 324 | Las dos piezas del candado de semana fallan hacia lados opuestos | 4 |
| 315 | RBAC implementado dos veces sin gate que compare | **1** |
| 288 | El selector normaliza roles con una función privada incompleta | 5 |
| 280 | La invariante que el selector se impone a sí mismo no se cumple | 5 |
| 270 | El filtro «proyecto cerrado» está escrito en tres sitios con tres criterios | 5 |
| 240 | El Visualizador ve los mismos 17 botones que el Residente | 6 |
| 200 | Cuatro capacidades son alias exactos de otra | 2 |

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `scripts/rbac-parity.mjs` | **Nuevo.** Vuelca la matriz del servidor y la del cliente y las compara rol a rol, capacidad a capacidad. |
| `tests/rbac/parity.test.mjs` | **Nuevo.** Envuelve el script como prueba de Node y falla ante cualquier divergencia no declarada. |
| `docs/rbac-parity-exceptions.json` | **Nuevo.** Divergencias aceptadas, cada una con su motivo escrito y su fecha. Una excepción sin uso falla. |
| `src/Security/RbacManager.php` | Capacidades inertes y alias (Task 2). |
| `public/js/rbac_capabilities.js` | Su espejo en cliente. |
| `src/Controllers/Api/SemanalApiController.php` | Autorización real de reabrir semana (Task 3). |
| `public/js/modules/programacion_semanal/hot.js` | Quién ve el botón de reabrir (Task 3). |
| `src/Core/CommitmentLockGuard.php`, `src/Security/LpsWeekEditPolicy.php` | El candado de semana (Task 4). |
| `src/Controllers/Core/ProjectSelectorController.php`, `src/Support/BiProjectScope.php` | El selector y su filtro (Task 5). |
| `src/Core/SessionMiddleware.php`, `public/js/modules/*/hot.js` | Sesión caducada (Task 7). |

---

### Task 1: El gate que compara las dos matrices RBAC

Va primero a propósito. Hoy las capacidades se declaran **dos veces** —`src/Security/RbacManager.php` y `public/js/rbac_capabilities.js`— y **nada las compara**: una divergencia produce una interfaz que ofrece acciones que el servidor rechaza, o que esconde acciones permitidas, y nadie se entera. Este gate nace rojo y las tareas siguientes lo ponen verde.

**Files:**
- Create: `scripts/rbac-parity.mjs`, `tests/rbac/parity.test.mjs`, `docs/rbac-parity-exceptions.json`
- Read: `src/Security/RbacManager.php`, `public/js/rbac_capabilities.js`, `src/Security/RbacCatalog.php`

**Interfaces:**
- Consumes: nada.
- Produces: `npm run test:rbac-parity`, que sale `0` cuando las matrices coinciden salvo excepciones declaradas, y `1` con el detalle de cada divergencia. Las tareas 2 y 3 lo consumen.

- [ ] **Step 1: Volcar la matriz del servidor**

Los diez roles canónicos salen de `RbacCatalog` (`A`, `D`, `R`, `DCV`, `OT`, `G`, `S`, `SG`, `C`, `V`). Crear un volcador mínimo:

```bash
docker compose exec -T app php -r '
require "/var/www/html/vendor/autoload.php";
$roles = ["A","D","R","DCV","OT","G","S","SG","C","V"];
$out = [];
foreach ($roles as $r) { $out[$r] = \App\Security\RbacManager::getCapabilities($r); }
echo json_encode($out, JSON_PRETTY_PRINT);
'
```

Esperado: un objeto de 10 roles con sus capacidades booleanas. Anota cuántas capacidades trae cada uno — es la cifra que el gate comprobará.

- [ ] **Step 2: Volcar la matriz del cliente**

`public/js/rbac_capabilities.js` no es un módulo ES: termina asignando a `window`. Para leerlo desde Node hay que darle un `window` falso. En `scripts/rbac-parity.mjs`:

```js
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

// El archivo del cliente termina con `window.RbacCapabilities = RbacCapabilities`, así que
// se evalúa con un `window` postizo y se recoge lo que cuelga de él. No se modifica el
// archivo: el gate debe leer exactamente lo que el navegador carga, no una copia adaptada.
function cargarMatrizCliente(roles) {
  const fuente = readFileSync(path.join(root, 'public/js/rbac_capabilities.js'), 'utf8');
  const ventana = {};
  const ejecutar = new Function('window', `${fuente}\nreturn window;`);
  ejecutar(ventana);
  const caps = ventana.RbacCapabilities;
  if (!caps) throw new Error('rbac_capabilities.js no expuso window.RbacCapabilities');

  const matriz = {};
  for (const rol of roles) {
    matriz[rol] = {};
    for (const [nombre, valor] of Object.entries(caps)) {
      if (typeof valor !== 'function') continue;
      try {
        matriz[rol][nombre] = Boolean(valor.call(caps, rol));
      } catch {
        // Una capacidad que exige más argumentos que el rol no es comparable con la
        // matriz del servidor, que solo recibe el rol. Se declara y se salta.
        matriz[rol][nombre] = null;
      }
    }
  }
  return matriz;
}
```

- [ ] **Step 3: Comparar y explicar las diferencias**

El gate compara capacidad a capacidad y **nombra cada divergencia con las dos versiones**. Un mensaje de «no coinciden» sin decir en qué no sirve:

```js
function comparar(servidor, cliente, excepciones) {
  const fallos = [];
  for (const rol of Object.keys(servidor)) {
    const capsServidor = servidor[rol];
    const capsCliente = cliente[rol] || {};
    for (const [cap, valorServidor] of Object.entries(capsServidor)) {
      const valorCliente = capsCliente[cap];
      if (valorCliente === undefined) {
        fallos.push({ rol, cap, motivo: 'solo existe en servidor', servidor: valorServidor, cliente: null });
        continue;
      }
      if (valorCliente === null) continue; // no comparable, declarado en el Step 2
      if (valorCliente !== valorServidor) {
        fallos.push({ rol, cap, motivo: 'valores distintos', servidor: valorServidor, cliente: valorCliente });
      }
    }
    for (const cap of Object.keys(capsCliente)) {
      if (!(cap in capsServidor) && capsCliente[cap] !== null) {
        fallos.push({ rol, cap, motivo: 'solo existe en cliente', servidor: null, cliente: capsCliente[cap] });
      }
    }
  }
  return fallos.filter((f) => !estaExceptuado(f, excepciones));
}
```

- [ ] **Step 4: Las excepciones son contrato, no constantes**

Crear `docs/rbac-parity-exceptions.json` con la forma que ya usa el repo en `docs/design-system/evidence-exceptions.json`: cada entrada con `rol`, `capacidad`, `motivo` escrito en prosa y `fecha`. **Una excepción declarada y no usada debe fallar el gate** — esa regla ya se pagó en el design system (`6b069d16`, `9391582b`) y evita que la lista se pudra.

Arranca **vacío**: `{"schemaVersion": 1, "exceptions": []}`. Lo que aparezca se decide en las tareas siguientes, no aquí.

- [ ] **Step 5: Envolverlo como prueba y registrarlo**

`tests/rbac/parity.test.mjs` con `node:test`, y en `package.json`:

```json
"test:rbac-parity": "node --test 'tests/rbac/*.test.mjs'"
```

- [ ] **Step 6: Correrlo y **quedarse con el rojo**

```bash
npm run test:rbac-parity
```

**Esperado: FALLA**, y ese es el resultado correcto de esta tarea. Copia la lista completa de divergencias en el informe: es la entrada de las tareas 2 y 3. **No arregles ninguna aquí.**

- [ ] **Step 7: La mutación — comprobar que el gate detecta lo que promete**

Cambia temporalmente en `public/js/rbac_capabilities.js` un rol de `canEditPastGeneralProgram` (por ejemplo, añade `'R'` a la lista) y vuelve a correr. Esperado: aparece esa divergencia concreta, con rol y capacidad nombrados. Deshaz con `git checkout --` y confirma que la lista vuelve a ser la del Step 6.

- [ ] **Step 8: Commit**

```bash
git add scripts/rbac-parity.mjs tests/rbac/parity.test.mjs docs/rbac-parity-exceptions.json package.json
git commit -m "test(rbac): gate de paridad entre la matriz del servidor y la del cliente"
```

---

### Task 2: Las capacidades que no protegen nada

Tres hallazgos, un archivo. `canSeeReports` vale `true` para los diez roles y **nadie la consulta**; `canDeleteRows` tampoco la consulta nadie **y protege algo destructivo**; y cuatro capacidades son alias exactos de otra, así que el vocabulario promete una distinción entre «editar» y «gestionar» que el código no hace.

**Files:**
- Modify: `src/Security/RbacManager.php:33-49`, `public/js/rbac_capabilities.js`
- Test: `tests/rbac/parity.test.mjs` (Task 1), más un test PHP nuevo si el censo lo justifica

**Interfaces:**
- Consumes: Task 1 (la lista de divergencias reales).
- Produces: una matriz sin entradas inertes ni alias mudos.

- [ ] **Step 1: Censar consumidores antes de tocar nada**

```bash
for cap in canSeeReports canDeleteRows canManageGeneralProgram canManageWeeklyProgram canManageMediumTermProgram canAutoDefineContracts canManagePdC; do
  echo "== $cap: $(grep -rl "$cap" --include='*.php' --include='*.js' src admin public views 2>/dev/null | grep -v 'RbacManager.php\|rbac_capabilities.js' | wc -l | tr -d ' ') consumidores reales"
done
```

**Esto mide qué cobertura se pierde**, que la constraint global exige. Si alguna resulta tener consumidores, **no se retira**: se consulta hacia arriba, porque el hallazgo estaría equivocado.

- [ ] **Step 2: Retirar `canSeeReports`**

Es `true` para todos, así que retirarla no cambia el comportamiento de nadie — y eso hay que **demostrarlo**, no afirmarlo: con cero consumidores medidos en el Step 1, ninguna rama de código puede cambiar. El control real de quién ve informes ya existe y está en otro sitio: `IndicadoresController` usa `ROLES_SIN_INFORME` en servidor (documentado en `memoria/trampas/indicadores-oculta-en-cliente-bi-en-servidor.md`).

- [ ] **Step 3: `canDeleteRows` — decidir, no borrar por simetría**

Aquí **no** aplica el mismo razonamiento que en el Step 2. `canSeeReports` era `true` para todos: retirarla no puede abrir nada. `canDeleteRows` es `['A','D']`: si existe porque alguien pensaba proteger un borrado y nunca lo cableó, retirarla **cierra la puerta a implementarlo** y deja el borrado sin protección prevista.

Busca si hay borrado de filas sin protección:

```bash
grep -rn "eliminar\|delete" --include='*.php' src/Controllers/Api/ | grep -i "row\|fila" | head
```

Con lo que encuentres, **consulta hacia arriba** cuál de las dos: retirar la capacidad, o cablearla al borrado que hoy no la consulta. Es exactamente una decisión que cambia alcance.

- [ ] **Step 4: Los cuatro alias**

`canManageGeneralProgram`, `canManageWeeklyProgram`, `canManageMediumTermProgram` y `canAutoDefineContracts`/`canManagePdC` devuelven lo mismo que su original. Colapsarlos a un solo nombre por par, conservando el nombre que **más consumidores tenga** según el Step 1 — así el cambio toca menos sitios. Si un alias tiene consumidores y su original no, gana el alias.

- [ ] **Step 5: Espejar en el cliente y correr el gate**

```bash
npm run test:rbac-parity
```

Esperado: **menos divergencias que en la Task 1**. Anota cuántas quedan y cuáles: las que sobrevivan son material de la Task 3 o excepciones a declarar con su motivo.

- [ ] **Step 6: Un rol permitido y uno denegado**

`AGENTS.md` lo exige al tocar capacidades. Con la puerta de servicio, entra como `test.A` y como `test.V` a un módulo que use las capacidades tocadas y comprueba que ninguno gana ni pierde acceso respecto de antes.

- [ ] **Step 7: Commit**

```bash
git add src/Security/RbacManager.php public/js/rbac_capabilities.js docs/rbac-parity-exceptions.json
git commit -m "fix(rbac): fuera las capacidades que no protegen nada y los alias mudos"
```

---

### Task 3: Reabrir una semana — tres capas y ninguna coincide

El hallazgo más grave de la tanda. Medido el 2026-08-10:

- **El cliente** muestra «Reabrir Semana» solo si el rol es `A` (`public/js/modules/programacion_semanal/hot.js:3139`).
- **El servidor** solo exige `lps.programacion_semanal.editar` (`src/Controllers/Api/SemanalApiController.php:942`), permiso que el Residente tiene: **cualquiera que pueda editar la semanal puede reabrirla llamando al endpoint**. Esconder el botón es cosmético.
- **La regla que el usuario quiere** no es ninguna de las dos: reabren **Admin y Director**, y el **Residente solo hasta el fin del día de inicio de la semana**.
- Y el log escribe siempre «Semana N reabierta por Admin» (`:984`), sea quien sea.

**Files:**
- Modify: `src/Controllers/Api/SemanalApiController.php:940-995`
- Modify: `public/js/modules/programacion_semanal/hot.js:3138-3143`
- Create: `tests/test_semanal_reabrir_autorizacion.php`

**Interfaces:**
- Consumes: Task 1.
- Produces: una única regla de autorización, aplicada en servidor y reflejada en cliente.

- [ ] **Step 1: Escribir la prueba que falla, empezando por el servidor**

El orden importa: **el cliente puede esconder, pero solo el servidor puede impedir.** La prueba cubre los cuatro casos de la regla del usuario:

| Rol | Momento | Esperado |
|---|---|---|
| `A` | cualquiera | permitido |
| `D` | cualquiera | permitido |
| `R` | antes del fin del día de inicio de la semana | permitido |
| `R` | después | **denegado** |
| `V` | cualquiera | denegado |

Crear `tests/test_semanal_reabrir_autorizacion.php` con el patrón autoejecutable del repo (ver `tests/test_dev_door_guard.php`): contador de fallos y `exit(1)`. La política se prueba **como función pura si se puede extraer**; si exige petición HTTP, decláralo y usa el mismo enfoque que `tests/test_semanal_rbac_solo_lectura.php`.

- [ ] **Step 2: Correrla y verla fallar**

```bash
docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
```

Esperado: FALLA en los casos de `D` y de `R` fuera de plazo — hoy el servidor deja pasar a cualquiera con permiso de edición.

- [ ] **Step 3: Implementar la regla en el servidor**

En `SemanalApiController::reabrir()`, tras el guard existente y **antes** de la mutación. Normaliza el rol con `RoleManager::cleanCargo()`. La ventana del Residente se calcula contra la fecha de inicio de la semana; si esa fecha no se puede resolver, **denegar** — es la misma decisión que la Task 4 unifica para todo el candado, y aquí se aplica ya.

- [ ] **Step 4: Corregir el registro de auditoría**

`:984` escribe «reabierta por Admin» sea quien sea. Debe registrar **quién** reabrió de verdad. Un log que miente es peor que ninguno: da falsa trazabilidad justo en la operación que deshace un compromiso.

- [ ] **Step 5: Reflejarlo en el cliente**

`hot.js:3139` pasa de `getPermiso() === 'A'` a la misma regla. **El cliente refleja, no decide**: si las dos se separan otra vez, el gate de la Task 1 debe cazarlo — comprueba que así sea.

- [ ] **Step 6: Correr la prueba y el gate**

```bash
docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
npm run test:rbac-parity
docker compose exec app php tests/test_semanal_rbac_solo_lectura.php
docker compose exec app php tests/test_weekly_governance.php
```

Los dos últimos son la red que ya existía: si se ponen rojos, este cambio rompió algo que estaba bien.

- [ ] **Step 7: La mutación**

Devuelve el servidor a `lps.programacion_semanal.editar` a secas y comprueba que el caso «`R` fuera de plazo» se pone rojo. Deshaz.

- [ ] **Step 8: Verificar en navegador con dos roles**

Puerta de servicio con `test.A` y con `test.R` en `/programacion-semanal`: el botón aparece a quien debe y, **con `test.R` fuera de plazo, el endpoint responde denegado aunque se llame a mano**. Es la comprobación que distingue esconder de impedir.

- [ ] **Step 9: Commit**

```bash
git add src/Controllers/Api/SemanalApiController.php public/js/modules/programacion_semanal/hot.js tests/test_semanal_reabrir_autorizacion.php
git commit -m "fix(seguridad): reabrir semana se autoriza en el servidor, con la regla real"
```

---

### Task 4: El candado de semana, unificado hacia cerrar

Dos hallazgos de la misma pieza. `LpsWeekEditPolicy::allows()` devuelve `false` y cierra si el proyecto no se puede resolver (`src/Security/LpsWeekEditPolicy.php:23-26`), mientras `CommitmentLockGuard::guard()` retorna y **deja pasar la mutación** en la misma situación (`src/Core/CommitmentLockGuard.php:33-35`). Y `guard(..., allowIfConfirmed: true)` **retorna en su primera línea sin comprobar nada** (`:27-29`) — hoy ninguna de las nueve llamadas lo usa, así que es riesgo latente, no agujero abierto.

**Disposición del usuario:** unificar hacia cerrar. Ante la duda, un candado deniega.

**Files:**
- Modify: `src/Core/CommitmentLockGuard.php:27-35`, `src/Security/LpsWeekEditPolicy.php:23-26`
- Create: `tests/test_candado_semana_falla_cerrando.php`

**Interfaces:**
- Consumes: Task 3 (que ya aplicó «denegar si no se resuelve» en un sitio).
- Produces: una sola conducta ante proyecto irresoluble, en las dos piezas.

- [ ] **Step 1: Censar las nueve llamadas antes de cambiar la conducta**

```bash
grep -rn "CommitmentLockGuard::guard\|->guard(" --include='*.php' src/ | head -15
```

Cambiar un fallo-abierto a fallo-cerrado **puede romper flujos que hoy funcionan por accidente**. Anota las nueve y qué hace cada una. Si alguna depende de que el guard deje pasar, **consulta hacia arriba**: eso es un cambio de alcance.

- [ ] **Step 2: La prueba que falla**

`tests/test_candado_semana_falla_cerrando.php`: con un proyecto irresoluble, **las dos piezas deniegan**. Y `guard(allowIfConfirmed: true)` **comprueba rol y política** en vez de retornar.

- [ ] **Step 3: Correrla y verla fallar**, luego implementar, luego verla pasar.

- [ ] **Step 4: La mutación** — devuelve el `return` temprano de `:27-29` y comprueba que la prueba se pone roja. Deshaz.

- [ ] **Step 5: Regresión de lo que ya existía**

```bash
docker compose exec app php tests/test_weekly_governance.php
docker compose exec app php tests/test_semanal_rbac_solo_lectura.php
docker compose exec app php tests/test_pg_pasado_servidor.php
```

- [ ] **Step 6: Commit**

```bash
git add src/Core/CommitmentLockGuard.php src/Security/LpsWeekEditPolicy.php tests/test_candado_semana_falla_cerrando.php
git commit -m "fix(seguridad): el candado de semana falla cerrando en sus dos piezas"
```

---

### Task 5: El selector de proyectos, sus tres criterios y su número inventado

Cuatro hallazgos, dos archivos. El filtro «proyecto cerrado pero visible para la jefatura» está escrito en **tres sitios con tres criterios distintos**; el selector normaliza roles con un `normalizeRoleCode()` privado que solo conoce `P→D` y `U→V`, así que una cuenta con `role = 'Director de Obra'` entra como **Visualizador**; la invariante que el propio controlador se impone (`:81-85`) no se cumple, porque `index()` filtra con el rol crudo y `enterProject()` con el normalizado; y la barra de avance es `rand(0, 100)`.

**Files:**
- Modify: `src/Controllers/Core/ProjectSelectorController.php:41,49,122,161-179`, `src/Support/BiProjectScope.php`
- Create: `tests/test_selector_proyectos_criterio_unico.php`

**Interfaces:**
- Consumes: Task 2 (roles ya normalizados de una sola forma).
- Produces: un solo criterio de visibilidad, aplicado igual en los tres sitios.

- [ ] **Step 1: La prueba que fija el criterio único**

Casos: una cuenta con `role = 'Director de Obra'` en texto debe entrar como `D`, no como `V`; una cuenta con el alias legado `'P'` debe ver un proyecto cerrado **igual** en el selector y en la Torre de Control; y la lista y la entrada deben tratar `Acceso` de la misma forma.

- [ ] **Step 2: Correrla, verla fallar, implementar**

Sustituye `normalizeRoleCode()` por `RoleManager::cleanCargo()` y unifica el criterio de visibilidad en **una sola función** que los tres sitios consuman. `BiProjectScope` es hoy el que acierta —incluye `'P'`, que es Director en `RbacCatalog::roleAliases()`—, así que el criterio correcto es el suyo, no el del selector.

- [ ] **Step 3: Retirar la barra inventada**

`:49` usa `rand(0, 100)`. Quítala. **No la sustituyas por una métrica nueva**: cablear avance real exige definir qué cuenta como avance, y eso es otro trabajo con su propia decisión de dominio. Si al quitarla la tarjeta queda descuadrada, arregla la maqueta con los tokens del design system — nunca con hex ni estilos en línea.

- [ ] **Step 4: La mutación** — devuelve `normalizeRoleCode()` y comprueba que el caso de `'Director de Obra'` se pone rojo.

- [ ] **Step 5: Navegador, dos roles, y la pantalla que ve todo el mundo**

Puerta de servicio con `test.A` y `test.V` en `/proyectos` a 1180×820 dark. Comprueba que la lista es la correcta para cada uno y que **la barra inventada ya no está**. Consola sin errores.

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Core/ProjectSelectorController.php src/Support/BiProjectScope.php tests/test_selector_proyectos_criterio_unico.php
git commit -m "fix(seguridad): el selector usa un solo criterio de rol y pierde el avance inventado"
```

---

### Task 6: El Visualizador y sus diecisiete botones

Medido con sonda Playwright el 2026-08-04: el Visualizador —solo lectura por definición— ve en `/programa-general` **los mismos 17 botones** que el Residente, incluidos «Actualizar Ejecución», «Descargar Corte» y «Exportar CSV». La única diferencia por rol está **dentro** del flyout de semanas.

**Lo que el hallazgo dejó sin medir, y hay que medir antes de arreglar:** si «Actualizar Ejecución» **muta datos** para un rol de solo lectura. Confirmarlo exige pulsarlo.

**Files:**
- Modify: `views/programa-general/programa_general.view.php`, `public/js/modules/programa_general/hot.js`
- Create: `tests/browser/programa-general-visualizador.mjs`

**Interfaces:**
- Consumes: Tasks 1 y 2.
- Produces: una toolbar que refleja lo que el rol puede hacer.

- [ ] **Step 1: Medir primero si hay agujero real**

Con `test.V` por la puerta de servicio, pulsa «Actualizar Ejecución» y **mira la red y la base**. Si muta, esto deja de ser un hallazgo de interfaz y pasa a ser uno de autorización: **para y consulta hacia arriba**, porque cambia la prioridad y el alcance de la tarea.

- [ ] **Step 2: La prueba que fija qué ve cada rol**

`tests/browser/programa-general-visualizador.mjs`: con `test.V` la toolbar no ofrece acciones de escritura; con `test.R` sí. Sigue el patrón del carril de roles ya existente (`tests/browser/programacion-semanal-roles-phases.mjs`) — **ojo: esa suite ya está roja por causas ajenas** (8 fallos medidos el 2026-08-10), así que mide su línea base antes y compárala después.

- [ ] **Step 3: Implementar**, ocultando por capacidad y no por rol literal, para que el gate de la Task 1 lo cubra.

- [ ] **Step 4: La mutación** — devuelve un botón de escritura a la vista del Visualizador y comprueba que la prueba se pone roja.

- [ ] **Step 5: Commit**

```bash
git add views/programa-general/ public/js/modules/programa_general/hot.js tests/browser/programa-general-visualizador.mjs
git commit -m "fix(rbac): el Visualizador deja de ver diecisiete acciones que no puede usar"
```

---

### Task 7: La sesión caduca y el trabajo se pierde sin decir por qué

Cuando la sesión caduca durante una petición de datos de una grilla, el usuario **pierde el trabajo sin ver un error entendible**. `SessionMiddleware` solo responde el 401 JSON si la petición trae la cabecera propietaria `X-AIA-Expect-Json` (`src/Core/SessionMiddleware.php:91-96`); si no, manda un `Location: /login`, que un `fetch` sigue y devuelve **el HTML del login como si fueran datos**. Y esa cabecera la mandan **solo dos archivos**: `public/js/core/SessionTimeoutManager.js:145` y `public/js/components/notifications.js:25`. Ninguna de las grillas.

Encaja con la subentrega del Residente que documenta `docs/CUSTOMER.md`: el trabajo se pierde justo cuando más caro sale.

**Files:**
- Modify: `src/Core/SessionMiddleware.php:91-96` o el punto común de las peticiones de grilla
- Modify: los `hot.js` de `programa_general`, `programacion_intermedia`, `programacion_semanal`, `programa_actualizar`
- Create: `tests/test_sesion_caducada_responde_json.php`

**Interfaces:**
- Consumes: nada.
- Produces: una sesión caducada que se anuncia como error, no como datos.

- [ ] **Step 1: Reproducir la pérdida antes de arreglarla**

```bash
grep -rl "X-AIA-Expect-Json" public/js/modules/ | wc -l
```

Esperado hoy: `0`. Con la puerta de servicio, caduca la sesión (o invalida la cookie) y provoca una carga de grilla: comprueba que llega HTML donde se esperaba JSON. **Guarda esa evidencia**: es el «antes».

- [ ] **Step 2: Decidir dónde se arregla, y consultarlo**

Hay dos caminos y **no son equivalentes**: (a) que las cuatro grillas manden la cabecera, o (b) que el servidor responda 401 JSON a toda petición que pida JSON por `Accept`, sin depender de una cabecera propietaria. (b) arregla también a cualquier consumidor futuro que nadie ha escrito aún; (a) es más acotado y no toca middleware compartido. **Consulta hacia arriba cuál**, porque cambia el alcance.

- [ ] **Step 3: La prueba que falla**, el arreglo, y la prueba en verde.

- [ ] **Step 4: La mutación** — retira la condición nueva y comprueba que la prueba se pone roja.

- [ ] **Step 5: Navegador, las cuatro grillas**

Con la sesión caducada, cada una debe mostrar un aviso entendible en vez de perder el trabajo en silencio. Consola sin errores nuevos.

- [ ] **Step 6: Commit**

```bash
git add src/Core/SessionMiddleware.php public/js/modules/ tests/test_sesion_caducada_responde_json.php
git commit -m "fix(sesion): una sesion caducada se anuncia como error, no como datos"
```

---

### Task 8: Cierre de la tanda

- [ ] **Step 1: Marcar los 13 en `docs/EXPERIMENTS.md`**, cada uno `cerrado <hash>` con el commit que lo resolvió. Los que hayan quedado abiertos por decisión, con su motivo escrito. Ninguno mudo.

- [ ] **Step 2: Suite de la tanda**

```bash
npm run test:rbac-parity
docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
docker compose exec app php tests/test_candado_semana_falla_cerrando.php
docker compose exec app php tests/test_selector_proyectos_criterio_unico.php
docker compose exec app php tests/test_sesion_caducada_responde_json.php
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npm run test:design-system:static
```

Esperado: todo verde. El gate de paridad, que nació rojo en la Task 1, debe estar **verde o con sus divergencias declaradas y justificadas** en `docs/rbac-parity-exceptions.json`.

- [ ] **Step 3: No es el gate del frente todavía.** La tanda 1A cierra, pero el Frente 1 tiene tres. El push del gate bloqueante va al cerrar la 1C. Aun así, commitea todo y deja el worktree limpio.

---

## Autorrevisión de este plan

**Cobertura:** los 13 hallazgos de la tanda tienen tarea, y la tabla de arriba lo hace verificable de un vistazo.

**Por qué el gate va primero:** las tareas 2, 3 y 6 son instancias concretas de la divergencia que RBAC-D describe. Construir el gate antes convierte esas tres en «poner verde una prueba que ya existe» en vez de «arreglar y esperar que nadie lo deshaga». Es la única dependencia dura del plan.

**Tres puntos donde el plan manda consultar en vez de decidir**, y por qué: `canDeleteRows` (retirar puede cerrar la puerta a una protección que falta, y no es simétrico con `canSeeReports`); las nueve llamadas al guard (pasar de fallo-abierto a fallo-cerrado puede romper un flujo que hoy funciona por accidente); y dónde se arregla la sesión caducada (middleware compartido frente a las cuatro grillas). Los tres cambian alcance.

**Dos mediciones obligatorias antes de tocar código:** si «Actualizar Ejecución» muta datos para el Visualizador (Task 6 Step 1), y qué consumidores reales tienen las capacidades a retirar (Task 2 Step 1). Ninguna de las dos se puede deducir leyendo.

**Riesgo conocido:** `tests/browser/programacion-semanal-roles-phases.mjs` ya está roja por causas ajenas —8 fallos medidos el 2026-08-10—, así que la Task 6 debe medir su línea base antes de comparar. Sin eso, atribuiría a este trabajo fallos que ya estaban.
