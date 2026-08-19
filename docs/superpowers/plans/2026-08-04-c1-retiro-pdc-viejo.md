---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-04
areas: [pdc]
fuente: docs/superpowers/plans/2026-08-04-c1-retiro-pdc-viejo.md
resumen: retirar del código el módulo Plan de Compras v1 (/pdc, su API de núcleo, su vista Handsontable, sus assets, su navegación, su manifiesto de diseño y sus…
---

# C1 — Retiro del PDC viejo · Plan de implementación

> ## ⛔ ARCHIVADO SIN EJECUTAR — 2026-08-04
>
> **No ejecutes este plan.** El trabajo ya se hizo por otra vía, y con más alcance.
>
> Este plan se escribió y se aprobó el 2026-08-04. Mientras se escribía, **otra sesión aplicó el
> retiro directamente sobre `main`**: 69 archivos borrados, 58 modificados y 18 tablas de MySQL
> eliminadas, con respaldo previo en
> `storage/backups/lastplanneraia_dev-pre-borrado-pdc-v1-20260804.sql` (99 MB, 15:56). Verificado
> con `git status` sobre `main` desde este worktree, no dado por bueno desde el mensaje.
>
> **Felipe decidió el 2026-08-04 archivar este plan y no ejecutarlo.** Ejecutarlo ahora duplicaría
> el trabajo y garantizaría conflictos.
>
> ### Revocación de la opción A sobre los datos
>
> **Felipe confirmó el 2026-08-04 que el PDC v1 se elimina *también en datos*.** Eso **revoca** la
> decisión del 2026-07-30 («opción A: conservar; el retiro no toca las tablas») que este plan
> recogía como restricción global, y que sigue escrita como vigente en:
>
> - `docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design.md` (apartado «Los datos
>   históricos: decidido, ya no está abierto» y punto 2 de la condición de hecho).
> - `goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1.md` (apartado de recomendación).
>
> Esos dos documentos **quedan desactualizados** y deben corregirse por quien commitee el trabajo de
> `main`, para que no se lean como contrato vigente.
>
> ### Lo que este plan sigue sirviendo
>
> Su valor ya no es de ejecución, sino de **lista de control**: el apartado «Global Constraints»
> enumera, medido, lo que **no** debía caer con el PDC viejo. Sirve para auditar lo aplicado en
> `main` antes de que se commitee. En particular quedó **sin comprobar** si entre las 18 tablas
> eliminadas están las `general_pdc_*` (`general_pdc_familias`, 205 filas;
> `general_pdc_contractual_elements`, 28; `general_pdc_family_aliases`, 13;
> `general_pdc_project_family_strategy`, 18), que **no son del PDC viejo**: las consumen
> `src/Support/ActivityMatcher.php` y `src/Services/SemiAutoService.php`, y de ellas dependen
> `/contratos` y `/listado-actividades`. Esa auditoría no se hizo por decisión explícita de Felipe.
>
> ---

> **For agentic workers:** REQUIRED SUB-SKILL: usa `superpowers:executing-plans` para ejecutar tarea
> a tarea. Los pasos usan checkbox (`- [ ]`).

**Goal:** retirar del código el módulo Plan de Compras v1 (`/pdc`, su API de núcleo, su vista
Handsontable, sus assets, su navegación, su manifiesto de diseño y sus pruebas) **sin tocar ni un
dato** y **sin arrastrar** nada de lo que el censo demostró que es compartido.

**Arquitectura:** retiro ruta por ruta, nunca por prefijo. Cuatro archivos de entrega (vista, CSS,
JS, controlador de página) caen juntos porque se consumen entre sí; tres controladores de API caen
con sus rutas; los inventarios del sistema de diseño pierden el módulo `pdc`; los tests específicos
se borran y los fixtures compartidos se podan. Entre medias, cada tarea deja el árbol arrancando.

**Tech Stack:** PHP 8.3 + FastRoute (`public/index.php`), Node test runner + Playwright, JSON de
manifiestos del design system.

**Origen:** `docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design.md`, con el alcance
reescrito por `goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1.md`.

## Global Constraints

- **Precondición del spec (validación en producción): levantada por Felipe el 2026-08-03.** La
  producción real está en `1aa7c69` (2026-07-16) sin ninguna tabla `pdc_*`; el retiro y el v2
  llegarán allí en el mismo envío. Felipe confirmó además el 2026-08-04 que nadie usa `/pdc` en
  `prueba-lps`.
- **C1 no toca datos.** Las tablas `pdc`, `papelera_pdc`, `general_pdc_*` se quedan quietas. Sin
  `DROP`, sin `DELETE`, sin migración, sin gate de Plannotator, sin respaldo por ese lado.
- **NO se retira, bajo ninguna circunstancia:**
  - `src/Support/OperationalFamilyPolicy.php` y el modelo de familias (lo consumen
    `src/Support/ActivityMatcher.php` y `src/Services/SemiAutoService.php`).
  - Los trece `/api/pdc/auto/*` (`public/index.php:256-268`), `SemiAutoController` ni
    `PdcAutoGenerateController`.
  - `PdcResetService` ni `PdcMaintenanceController` — son del **v2**.
  - Nada bajo `/plan-compras`, `pdc-app/`, `public/pdc-app/`, `/bi/pdc`, `/api/bi/report/pdc`.
  - `docs/design-system/manifests/plan-compras-v2.json` ni
    `scripts/design-system-plan-compras-gate.mjs` (son del v2 pese al nombre).
- **Regla que ya pagó dos veces:** antes de borrar cualquier asset o vista, grepear `public/js`,
  `views` y `src` ignorando comentarios, y buscar los tests que lo leen. Está incorporada como paso
  explícito en la Tarea 1.
- **No se reescribe historia.** Los `docs/**` narrativos (planes y specs pasados, `docs/qa/`,
  `docs/flujos/`, `memoria/`) mencionan `/pdc` como registro de lo que ocurrió: **no se tocan**.
  Solo cambian los JSON del design system, que son contrato ejecutable.
- **No hacer commit a `main` ni push.** Los commits van a la rama del worktree
  (`claude/modest-swirles-65a01c`).

---

## File Structure

**Se borran (9 archivos):**

| Archivo | Por qué |
|---|---|
| `views/pdc/pdc.view.php` | vista Handsontable del v1 |
| `public/css/pdc.css` | única hoja de esa vista |
| `public/js/modules/pdc/hot.js` | llama a `/api/pdc/list` y `/save`; solo lo carga esa vista |
| `src/Controllers/Gestion/PdcController.php` | controlador de la página |
| `src/Controllers/Api/PdcApiController.php` | grupo A de la API |
| `src/Controllers/Api/PdcPlantillaController.php` | grupo B, ya muerto |
| `docs/design-system/manifests/pdc.json` | manifiesto del v1 |
| `tests/browser/__screenshots__/pdc/pdc-dark-1180x820.png` | golden del manifiesto |
| `tests/browser/pdc-chips-dark.mjs` | prueba visual específica del v1 |

**Se borran (pruebas del v1, 6 archivos):** `tests/browser/pdc-handsontable.mjs`,
`tests/browser/test-pdc.mjs`, `e2e/tests/workflows/pdc-full.spec.mjs`,
`e2e/tests/biblia/pdc.spec.mjs`, `tests/test_pdc_security_and_restore_contract.php`,
`tests/test_pdc_projected_dates_reflow.php`, `tests/test_pdc_modern_replaces_legacy_update.php`.

**Se modifican:** `public/index.php`, `views/design-system/families/shell-navigation.php`,
`public/js/modules/info_general_nav.js`, los cinco JSON de inventario del design system,
`tests/design-system/contracts.test.mjs`, `scripts/wiki-arquitectura.modulos.mjs`, y los fixtures y
suites compartidos que iteran `/pdc`.

---

### Task 1: Reconfirmar el censo sobre este árbol antes de borrar nada

El censo se hizo sobre `a083d6c` en `main`. Este worktree está en otra rama. La regla del repositorio
exige medir sobre el árbol que se va a tocar, no fiarse de una medición anterior.

**Files:**
- Create: `goals/pdc-preparar-b1/evidence/c1-recenso-preborrado.md`

- [ ] **Step 1: Grep de consumidores vivos de los cuatro archivos de entrega**

```bash
cd "$(git rev-parse --show-toplevel)"
grep -rn --exclude-dir=node_modules --exclude-dir=.git \
  -E "pdc\.css|modules/pdc/hot|pdc/pdc\.view" \
  public views src tests e2e admin scripts pdc-app/src \
  | grep -v "^public/pdc-app/" \
  | grep -vE "^[^:]+:[0-9]+:[[:space:]]*(//|#|\*|/\*)"
```

Esperado: solo referencias dentro de los propios cuatro archivos, más las entradas de inventario del
design system y las pruebas ya listadas en este plan. **Si aparece un consumidor no listado aquí,
PARA y repórtalo antes de borrar.**

- [ ] **Step 2: Confirmar que las rutas del grupo C siguen apuntando a controladores compartidos**

```bash
grep -n "api/pdc/auto" public/index.php
```

Esperado: 13 líneas (`256-268`), doce hacia `SemiAutoController` y una hacia
`PdcAutoGenerateController`. Ninguna se toca en todo este plan.

- [ ] **Step 3: Confirmar que `OperationalFamilyPolicy` sigue teniendo consumidores transversales**

```bash
grep -rn "OperationalFamilyPolicy" src/ tests/ | grep -v "^src/Support/OperationalFamilyPolicy.php"
```

Esperado: al menos `src/Support/ActivityMatcher.php` y `src/Services/SemiAutoService.php`. Confirma
que queda fuera de alcance.

- [ ] **Step 4: Escribir el recenso**

Crea `goals/pdc-preparar-b1/evidence/c1-recenso-preborrado.md` con: el SHA base del worktree, la
salida literal de los tres greps, y una línea por cada divergencia respecto al censo del 2026-07-30
(o «sin divergencias»).

- [ ] **Step 5: Commit**

```bash
git add goals/pdc-preparar-b1/evidence/c1-recenso-preborrado.md
git commit -m "docs(c1): recenso de consumidores del PDC v1 sobre el arbol de trabajo"
```

---

### Task 2: Retirar la navegación hacia `/pdc`

Va primero a propósito: deja de ofrecerse el enlace antes de que la ruta desaparezca, así en ningún
momento hay un enlace visible que dé 404.

**Files:**
- Modify: `views/design-system/families/shell-navigation.php:45`
- Modify: `public/js/modules/info_general_nav.js:19-54`
- Modify: `tests/browser/shell-sidebar-rollout.mjs:29,40`
- Modify: `tests/browser/info-nav-focus-visible.mjs:163`
- Modify: `tests/browser/modales-dark-homologacion.mjs:430`
- Modify: `tests/test_design_system_components.php:14`

**Interfaces:**
- Produces: tras esta tarea, ninguna pantalla enlaza a `/pdc` (punto 4 de la condición de hecho).
  `info_general_nav.js` expone `ORDER` con dos entradas (`listado`, `contratos`).

- [ ] **Step 1: Quitar la entrada del sidebar**

En `views/design-system/families/shell-navigation.php`, elimina la línea 45 completa:

```php
                    ['id' => 'pdc', 'label' => 'Plan de Compras', 'href' => '/pdc', 'icon' => 'clipboard'],
```

Deja intacta cualquier entrada que apunte a `/plan-compras` o `/bi/pdc`.

- [ ] **Step 2: Quitar `pdc` de la nav de Info General**

En `public/js/modules/info_general_nav.js`:
- Elimina el bloque `pdc: { ... path: '/pdc' ... }` del objeto `ITEMS` (líneas 19-27).
- Cambia `var ORDER = ['listado', 'contratos', 'pdc'];` por `var ORDER = ['listado', 'contratos'];`.
- Elimina la rama `if (item.path === '/pdc') { ... }` (línea 42 y su cuerpo) — el parámetro
  `pdcOrigin` deja de tener destino.
- En `render(activeKey, semana, pdcOrigin)`: cambia el fallback
  `var active = ITEMS[activeKey] || ITEMS.pdc;` por `var active = ITEMS[activeKey] || ITEMS.listado;`
  y simplifica `var href = key === 'pdc' ? buildUrl(item, semana, pdcOrigin) : buildUrl(item, semana);`
  a `var href = buildUrl(item, semana);`. Conserva la firma `render(activeKey, semana, pdcOrigin)`
  para no romper llamadores; el tercer argumento queda sin uso.

- [ ] **Step 3: Actualizar el test que exige que Admin ya no enlace al módulo viejo**

`tests/test_shell_sidebar_partial.php:44` afirma `!str_contains($adminNav, 'href="/pdc"')`. **No se
toca:** seguirá pasando y ahora es más fuerte. Verifícalo:

```bash
docker compose exec app php tests/test_shell_sidebar_partial.php
```

Esperado: PASS.

- [ ] **Step 4: Podar `/pdc` de los tests de navegación**

- `tests/browser/shell-sidebar-rollout.mjs`: elimina la línea 29
  (`{ route: '/pdc', active: 'plan-compras', label: 'Plan de Compras (clásico)' }`) y quita
  `'/pdc'` del `Set` `MIGRATED` de la línea 40. **Conserva** `/bi/pdc` y `/plan-compras`.
- `tests/browser/info-nav-focus-visible.mjs:163`: deja
  `const ROUTES = ['/listado-actividades', '/contratos'];`.
- `tests/browser/modales-dark-homologacion.mjs:430`: deja
  `const INFO_NAV_ROUTES = ['/listado-actividades', '/contratos'];`.
- `tests/test_design_system_components.php:14`: el array `destinations` usa `/pdc` como dato de
  ejemplo del componente. Cámbialo a
  `['id' => 'pc', 'label' => 'Plan de Compras', 'href' => '/plan-compras']`.

- [ ] **Step 5: Verificar que la navegación no rompió nada**

```bash
docker compose exec app php tests/test_design_system_components.php
docker compose exec app php tests/test_shell_sidebar_partial.php
```

Esperado: PASS los dos.

- [ ] **Step 6: Commit**

```bash
git add views/design-system/families/shell-navigation.php public/js/modules/info_general_nav.js \
        tests/browser/shell-sidebar-rollout.mjs tests/browser/info-nav-focus-visible.mjs \
        tests/browser/modales-dark-homologacion.mjs tests/test_design_system_components.php
git commit -m "refactor(c1): la navegacion deja de ofrecer el PDC viejo"
```

---

### Task 3: Borrar las pruebas específicas del v1

Van antes que el código: si se borra el código primero, la suite queda roja entre commits.

**Files:**
- Delete: `tests/browser/pdc-handsontable.mjs`
- Delete: `tests/browser/test-pdc.mjs`
- Delete: `tests/browser/pdc-chips-dark.mjs`
- Delete: `e2e/tests/workflows/pdc-full.spec.mjs`
- Delete: `e2e/tests/biblia/pdc.spec.mjs`
- Delete: `tests/test_pdc_security_and_restore_contract.php`
- Delete: `tests/test_pdc_projected_dates_reflow.php`
- Delete: `tests/test_pdc_modern_replaces_legacy_update.php`

- [ ] **Step 1: Comprobar que ningún runner los referencia por nombre**

```bash
grep -rn --exclude-dir=node_modules \
  -E "pdc-handsontable|test-pdc|pdc-chips-dark|pdc-full|test_pdc_security|test_pdc_projected|test_pdc_modern" \
  package.json scripts/ tests/ e2e/ .github/ 2>/dev/null
```

Si alguno aparece en un runner o lista, anótalo: hay que quitar también esa referencia en este mismo
commit. Si no aparece nada fuera de los propios archivos, sigue.

- [ ] **Step 2: Borrar**

```bash
git rm tests/browser/pdc-handsontable.mjs tests/browser/test-pdc.mjs \
       tests/browser/pdc-chips-dark.mjs \
       e2e/tests/workflows/pdc-full.spec.mjs e2e/tests/biblia/pdc.spec.mjs \
       tests/test_pdc_security_and_restore_contract.php \
       tests/test_pdc_projected_dates_reflow.php \
       tests/test_pdc_modern_replaces_legacy_update.php
```

- [ ] **Step 3: Commit**

```bash
git commit -m "test(c1): retira las pruebas especificas del PDC viejo"
```

---

### Task 4: Podar `/pdc` de los fixtures y suites compartidos

Estos archivos sirven a otros módulos. Se podan, no se borran.

**Files:**
- Modify: `tests/browser/support/operationalCycle.mjs:695-700,753`
- Modify: `tests/browser/support/moduleFlows.mjs:298-303`
- Modify: `e2e/support/apiPayloads.mjs:89,95`
- Modify: `e2e/tests/smoke/routes.spec.mjs:35`
- Modify: `e2e/tests/workflows/procurement-flow.spec.mjs:97,111-124`
- Modify: `tests/browser/design-system-body-canvas-dark.mjs:45,155`
- Modify: `tests/browser/design-system-compliance.mjs:12`
- Modify: `tests/browser/design-system-consumer-smoke.mjs:9`
- Modify: `tests/browser/state-tint-ladder.mjs:73,102`
- Modify: `tests/browser/semi-auto-review.mjs:21`
- Modify: `tests/browser/bi_control_tower_access.spec.mjs:12`
- Modify: `tests/design-system/runtime-budget.test.mjs:152`
- Modify: `tests/design-system/runtime-budget-aggregate.test.mjs:172`

**Interfaces:**
- Consumes: la navegación ya podada en la Tarea 2.
- Produces: ninguna suite navega a `/pdc`; las que iteran rutas quedan con una entrada menos.

- [ ] **Step 1: Ciclo operativo**

En `tests/browser/support/operationalCycle.mjs`:
- Elimina `await navigatePurchasing(page, 'btn_planCompras', '/pdc');` (línea 695) y la entrada
  `'/pdc'` de la lista de la línea 700.
- Línea 753: cambia
  `expect(project.purchasingCapabilities).toEqual(['listadoActividades', 'contratos', 'pdc']);`
  por `expect(project.purchasingCapabilities).toEqual(['listadoActividades', 'contratos']);`
  **solo si** el código de producción deja de emitir `'pdc'` en esa capacidad. Compruébalo:

```bash
grep -rn "purchasingCapabilities" src/ views/ public/js/
```

Si el backend sigue emitiendo `'pdc'` (porque las rutas `auto/*` siguen vivas), **deja la aserción
como está** y anota el motivo en una línea de comentario. Esta es una decisión de medición, no de
gusto.

- [ ] **Step 2: Flujo por módulo**

`tests/browser/support/moduleFlows.mjs`: elimina el bloque de las líneas 298-303 que hace
`await expectUsablePage(page, '/pdc', ['#dt_cliente', 'body']);` y su entrada de módulo.

- [ ] **Step 3: e2e**

- `e2e/support/apiPayloads.mjs`: elimina la línea 89 (`list: { ... '/api/pdc/list' ... }`) y la
  línea 95 (`list: 'SELECT * FROM pdc WHERE ...'`). **Conserva** `autoPreview` y `autoApply`: son
  del grupo C compartido.
- `e2e/tests/smoke/routes.spec.mjs`: elimina la línea 35
  (`{ path: '/pdc', selectors: ['#dt_cliente', 'body'] }`).
- `e2e/tests/workflows/procurement-flow.spec.mjs`: elimina la navegación de la línea 97
  (`changeWeek(page, PROJECT.maxWeek, '/pdc')`) y el bloque de las líneas 111-124 que consulta
  `SELECT COUNT(*) FROM pdc` y llama a `/api/pdc/list`. **Conserva** las llamadas a
  `/api/pdc/auto/preview` y `/api/pdc/auto/apply` (líneas 98-107): son del grupo C.

- [ ] **Step 4: Suites de diseño que iteran rutas**

Elimina la entrada de `/pdc` (y solo esa) en:
- `tests/browser/design-system-body-canvas-dark.mjs`: línea 45 (`'/pdc': 'rgb(11, 16, 13)'`) y línea
  155 (`'/pdc': { selector: '#dt_cliente', ... }`).
- `tests/browser/design-system-compliance.mjs`: línea 12
  (`{ path: '/pdc', label: 'PDC', type: 'handsontable', ... }`).
- `tests/browser/design-system-consumer-smoke.mjs`: `'/pdc'` de la lista de la línea 9.
- `tests/browser/state-tint-ladder.mjs`: las claves `'/pdc'` de las líneas 73 y 102.
- `tests/browser/semi-auto-review.mjs`: la entrada `{ key: 'pdc', url: '/pdc' }` de la línea 21.
  **Conserva** las entradas de `/contratos` y `/listado-actividades`.
- `tests/browser/bi_control_tower_access.spec.mjs`: la entrada
  `{ path: '/pdc', label: /BI PDC/i, target: /\/bi\/pdc/ }` de la línea 12. **Conserva** todo lo
  demás: `/bi/pdc` sigue existiendo, lo que desaparece es el origen `/pdc`.
- `tests/design-system/runtime-budget.test.mjs:152` y
  `tests/design-system/runtime-budget-aggregate.test.mjs:172`: sustituye la ruta de ejemplo `'/pdc'`
  por `'/plan-compras'` — son datos sintéticos de un caso de prueba del validador, no medidas reales.

- [ ] **Step 5: Verificar que las suites estáticas siguen verdes**

```bash
npm run test:design-system:static
```

Esperado: todo verde salvo, quizá, `foundation.test.mjs:273` («stylesheet versions follow nested CSS
changes»), que es **ambiental del worktree** — ese test corre PHP dentro del contenedor, que sirve el
árbol principal, y lo compara contra el `tokens.css` de este worktree. Cualquier otro rojo es una
regresión real: párate y diagnostica.

- [ ] **Step 6: Commit**

```bash
git add tests/ e2e/
git commit -m "test(c1): poda /pdc de los fixtures y suites compartidos"
```

---

### Task 5: Retirar las rutas y sus controladores

**Files:**
- Modify: `public/index.php:141,157-160,252-255`
- Delete: `src/Controllers/Gestion/PdcController.php`
- Delete: `src/Controllers/Api/PdcApiController.php`
- Delete: `src/Controllers/Api/PdcPlantillaController.php`
- Modify: `tests/test_lacp_modern_navigation.php:34,69`
- Modify: `tests/test_legacy_absence_for_lacp_runtime.php:36,72`
- Modify: `tests/test_lacp_manual_crud_persistence.php:12,16,277`
- Modify: `tests/test_week_context_write_scope.php:49`

**Interfaces:**
- Consumes: fixtures ya podados (Tarea 4).
- Produces: `public/index.php` sin ninguna ruta que resuelva a `PdcController`, `PdcApiController` ni
  `PdcPlantillaController`. Las 13 rutas `/api/pdc/auto/*` siguen registradas, intactas.

- [ ] **Step 1: Eliminar las nueve rutas, una por una**

En `public/index.php` elimina **exactamente** estas líneas y ninguna otra:

```php
$router->get('/pdc', [\App\Controllers\Gestion\PdcController::class, 'index']);                                   // 141
$router->post('/api/pdc/list', [\App\Controllers\Api\PdcApiController::class, 'list']);                           // 157
$router->post('/api/pdc/save', [\App\Controllers\Api\PdcApiController::class, 'save']);                           // 158
$router->post('/api/pdc/update-cell', [\App\Controllers\Api\PdcApiController::class, 'updateCell']);              // 159
$router->get('/api/pdc/duracion-sugerida', [\App\Controllers\Api\PdcApiController::class, 'duracionSugerida']);   // 160
$router->get('/api/pdc/plantillas', [\App\Controllers\Api\PdcPlantillaController::class, 'list']);                // 252
$router->get('/api/pdc/plantillas/{id}', [\App\Controllers\Api\PdcPlantillaController::class, 'show']);           // 253
$router->get('/api/pdc/plantillas/{id}/items', [\App\Controllers\Api\PdcPlantillaController::class, 'items']);    // 254
$router->get('/api/pdc/categorias-recurso', [\App\Controllers\Api\PdcPlantillaController::class, 'categorias']);  // 255
```

Deja intacto el comentario de la línea 161 (`// Api/Plan de Compras v2 …`) y **las 13 rutas
`/api/pdc/auto/*`** que empiezan en la 256.

- [ ] **Step 2: Verificar que no queda ninguna ruta del v1**

```bash
grep -n "PdcController\|PdcApiController\|PdcPlantillaController" public/index.php
```

Esperado: **sin salida**.

```bash
grep -c "api/pdc/auto" public/index.php
```

Esperado: `13`.

- [ ] **Step 3: Borrar los tres controladores**

```bash
git rm src/Controllers/Gestion/PdcController.php \
       src/Controllers/Api/PdcApiController.php \
       src/Controllers/Api/PdcPlantillaController.php
```

- [ ] **Step 4: Podar las referencias en los tests que sobreviven**

Estos tests son de otros módulos y solo usan los archivos del PDC v1 como uno más de una lista:
- `tests/test_lacp_modern_navigation.php`: quita la entrada `'PDC' => $root . '/views/pdc/pdc.view.php'`
  (línea 34) y `$root . '/src/Controllers/Gestion/PdcController.php'` de la lista de la línea 69.
- `tests/test_legacy_absence_for_lacp_runtime.php`: quita `'views/pdc/pdc.view.php'` de las dos
  listas (líneas 36 y 72).
- `tests/test_week_context_write_scope.php`: quita
  `'src/Controllers/Gestion/PdcController.php'` de la lista de la línea 49. El comentario de la
  línea 85 nombra `PdcApiController`: actualízalo para que refleje los controladores que quedan, sin
  reescribir el resto del comentario.
- `tests/test_lacp_manual_crud_persistence.php`: usa `PdcApiController` de verdad (`require_once`
  línea 12, `use` línea 16, instanciación línea 277). **Lee ese test antes de tocarlo.** Si el bloque
  que lo instancia cubre el CRUD del PDC v1, elimina ese bloque completo y su `require_once`/`use`.
  Si cubre algo del Listado de Actividades apoyándose en el controlador del PDC, **para y repórtalo**:
  significa un acoplamiento que el censo no vio y que hay que resolver antes de borrar.

- [ ] **Step 5: Verificar que la app arranca y las rutas responden como toca**

```bash
docker compose exec app php -l public/index.php
docker compose exec app php -r 'require "/var/www/html/vendor/autoload.php"; echo "autoload OK\n";'
curl -s -o /dev/null -w "/pdc -> %{http_code}\n" http://localhost:8081/pdc
curl -s -o /dev/null -w "/plan-compras -> %{http_code}\n" http://localhost:8081/plan-compras
curl -s -o /dev/null -w "/contratos -> %{http_code}\n" http://localhost:8081/contratos
curl -s -o /dev/null -w "/listado-actividades -> %{http_code}\n" http://localhost:8081/listado-actividades
```

Esperado: `/pdc` → `404`. Los otros tres → `302` hacia `/login` (sin sesión) o `200`, **nunca `500`**.
Un `500` en `/contratos` o `/listado-actividades` significa que se arrastró algo compartido: para y
diagnostica.

- [ ] **Step 6: Verificar los tests PHP tocados**

```bash
docker compose exec app php tests/test_lacp_modern_navigation.php
docker compose exec app php tests/test_legacy_absence_for_lacp_runtime.php
docker compose exec app php tests/test_week_context_write_scope.php
docker compose exec app php tests/test_lacp_manual_crud_persistence.php
docker compose exec app php tests/test_lacp_legacy_cleanup_readiness.php
```

Esperado: PASS los cinco. El último no se modifica pero se corre a propósito: mapea el legado
`/legacy/pdc/actualizar_pdc.php` hacia `/api/pdc/auto/apply-from-contratos`, así que es el guardián de
que la sucesora moderna sigue viva.

- [ ] **Step 7: Commit**

```bash
git add public/index.php tests/
git commit -m "refactor(c1): retira ruta por ruta el PDC viejo y sus tres controladores"
```

---

### Task 6: Borrar la vista y sus assets

Los tres caen juntos: la vista carga el CSS y el JS, y el JS solo habla con rutas que ya no existen.

**Files:**
- Delete: `views/pdc/pdc.view.php`
- Delete: `public/css/pdc.css`
- Delete: `public/js/modules/pdc/hot.js`

- [ ] **Step 1: Grep final antes de borrar (la regla que ya pagó dos veces)**

```bash
grep -rn --exclude-dir=node_modules --exclude-dir=.git \
  -E "pdc\.css|modules/pdc/hot|pdc/pdc\.view" \
  public views src tests e2e admin scripts pdc-app/src \
  | grep -v "^public/pdc-app/" \
  | grep -vE "^[^:]+:[0-9]+:[[:space:]]*(//|#|\*|/\*)"
```

Esperado: solo las entradas de inventario de `docs/design-system/*.json` (que caen en la Tarea 7).
**Si aparece un `<link>`, un `<script>`, un `@import` o un `require` vivo fuera de esos JSON, PARA.**

Ojo con dos falsos positivos que **no** son del v1 y deben seguir en pie:
`public/pdc-app/assets/pdc.css` (bundle del v2) y las menciones dentro de comentarios de
`public/css/styles.css`, `public/css/control-cambios.css`,
`public/css/design-system/adapters/legacy-bridge.css`, `public/css/handsontable-header-global.css` y
`public/css/programa-general-actualizar.css`, que documentan por qué una regla quedó inerte. Esos
comentarios quedan desactualizados por el retiro; **no los reescribas en esta tarea** — se anotan
como pendiente en la Tarea 9.

- [ ] **Step 2: Borrar**

```bash
git rm views/pdc/pdc.view.php public/css/pdc.css public/js/modules/pdc/hot.js
rmdir views/pdc public/js/modules/pdc 2>/dev/null || true
```

- [ ] **Step 3: Verificar que no rompió otras pantallas**

```bash
curl -s -o /dev/null -w "/contratos -> %{http_code}\n" http://localhost:8081/contratos
curl -s -o /dev/null -w "/programacion-semanal -> %{http_code}\n" http://localhost:8081/programacion-semanal
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: nada de `500`; phpstan sin errores nuevos respecto a la línea base.

- [ ] **Step 4: Commit**

```bash
git commit -m "refactor(c1): borra la vista Handsontable del PDC viejo y sus dos assets"
```

---

### Task 7: Sacar el módulo `pdc` del sistema de diseño

**Files:**
- Delete: `docs/design-system/manifests/pdc.json`
- Delete: `tests/browser/__screenshots__/pdc/pdc-dark-1180x820.png`
- Modify: `docs/design-system/manifests/inventory.json:13,66-69,88-92`
- Modify: `docs/design-system/exceptions.json:595-609,675`
- Modify: `docs/design-system/state-token-exceptions.json:95-106`
- Modify: `docs/design-system/ui-groups-inventory.json:626,1257`
- Modify: `docs/design-system/unlayered-delivery-inventory.json:131,297-311`
- Modify: `tests/design-system/contracts.test.mjs:249`

**Interfaces:**
- Consumes: los archivos ya borrados en la Tarea 6 (los inventarios los referencian por ruta).
- Produces: `inventory.manifests` sin `pdc.json`; `inventory.modules` sin **ninguna** entrada
  `moduleId: "pdc"`.

**Trampa medida, no opcional:** `tests/design-system/contracts.test.mjs:249` es un `deepEqual` contra
una lista fija de manifiestos. Quitar `pdc.json` obliga a estrecharla **en el mismo commit**. Y el
gate `design-system-contracts.mjs` exige **árbol limpio**: hay que commitear antes de correrlo.

- [ ] **Step 1: Borrar el manifiesto y su golden**

```bash
git rm docs/design-system/manifests/pdc.json \
       tests/browser/__screenshots__/pdc/pdc-dark-1180x820.png
rmdir tests/browser/__screenshots__/pdc 2>/dev/null || true
```

- [ ] **Step 2: `inventory.json`**

- Quita `"pdc.json",` de la lista `manifests` (línea 13).
- Elimina **las dos** entradas `moduleId: "pdc"` de `modules`: la `inventory-only` (líneas 66-69) y
  la `pilot` con `"manifest": "pdc.json"` (líneas 88-92). Que hubiera dos era el defecto de
  duplicación que el censo reportó como hallazgo 6: desaparece aquí por consecuencia, no por
  arreglo aparte.
- **Conserva íntegra** la entrada `moduleId: "plan-compras-v2"` con su nota — es el v2.

- [ ] **Step 3: `exceptions.json`**

- Elimina el bloque de presupuesto `"name": "pdc"` (líneas 595-609) con su lista de tres archivos y
  su nota sobre el `rgba()` móvil.
- Línea 675: la frase enumera módulos con presupuesto cero. Quita `pdc, ` de esa enumeración.
- **No toques** las líneas 53, 242 y 332: son selectores compartidos (`.pdc-legend-item`,
  `.btn-pdc-modern`, `.pdc-legend-item`) que viven en reglas de **otros** módulos. Comprueba que
  siguen teniendo emisor vivo:

```bash
grep -rn "pdc-legend-item\|btn-pdc-modern" public/css public/js views src | grep -v node_modules
```

Si algún selector se queda **sin ningún emisor** tras el retiro, quítalo también de su lista y anota
cuál en el mensaje de commit. Si conserva emisor, déjalo.

- [ ] **Step 4: `state-token-exceptions.json`**

Elimina las dos entradas cuyo `"file"` es `public/css/pdc.css` (`.pdc-message-success` líneas 95-100
y `.pdc-message-error` líneas 102-107). El archivo ya no existe, así que son excepciones huérfanas.

- [ ] **Step 5: `ui-groups-inventory.json`**

Elimina las referencias a `views/pdc/pdc.view.php` de las líneas 626 y 1257. Si al quitarlas un
grupo se queda con la lista de archivos vacía, elimina el grupo entero.

- [ ] **Step 6: `unlayered-delivery-inventory.json`**

- Elimina `"views/pdc/pdc.view.php"` de la lista de la línea 131.
- Elimina la clave de ruta `"/pdc"` completa (líneas 297 y siguientes, hasta cerrar su objeto).
- **Conserva** la clave `"/bi/pdc"` (línea 445) y **conserva** las entradas cuyo `sheet` sea
  `/pdc-app/assets/pdc.css` (líneas 51 y 311): son del **v2**.
- Las notas `2-handsontable-doble-carga` (línea 464) y `5-handsontable-pdc` (línea 467) describen
  deudas abiertas que nombran `/pdc`. La segunda queda cerrada por el retiro: márcala como cerrada
  citando este retiro. En la primera, quita `/pdc` de su enumeración de rutas; el resto de rutas
  sigue afectado.

- [ ] **Step 7: Estrechar la lista fija de `contracts.test.mjs`**

En `tests/design-system/contracts.test.mjs:249`, quita `'pdc.json', ` del array del `deepEqual`. La
lista queda con 15 entradas, conservando `'plan-compras-v2.json'`.

- [ ] **Step 8: Commitear antes de correr el gate (lo exige el árbol limpio)**

```bash
git add docs/design-system/ tests/design-system/contracts.test.mjs
git commit -m "chore(c1): el modulo pdc sale del inventario del sistema de diseno"
```

- [ ] **Step 9: Correr el gate y la suite estática**

```bash
node scripts/design-system-contracts.mjs
npm run test:design-system:static
```

Esperado: el gate en **PASS**, y la suite verde salvo el rojo ambiental conocido de
`foundation.test.mjs:273` descrito en la Tarea 4. Si el gate falla por «missing manifest» o por un
inventario incoherente, corrígelo y commitea de nuevo antes de volver a correrlo.

---

### Task 8: Actualizar el mapa de rutas de la wiki

`scripts/wiki-arquitectura.modulos.mjs` exige que **toda** ruta de `public/index.php` case con algún
prefijo declarado. Al desaparecer nueve rutas, el módulo que las reclamaba queda declarando prefijos
sin dueño.

**Files:**
- Modify: `scripts/wiki-arquitectura.modulos.mjs:107-114`
- Modify (generado): `memoria/arquitectura/**`

- [ ] **Step 1: Ajustar el módulo que reclamaba `/pdc` y `/api/pdc`**

En el bloque `slug: 'listado-de-actividades'` (líneas 107-114), cambia
`rutas: ['/pdc', '/api/pdc'],` por `rutas: ['/api/pdc'],` **solo si** siguen quedando rutas
`/api/pdc/*` fuera de `/api/pdc/auto` tras la Tarea 5. Compruébalo:

```bash
grep -n "api/pdc" public/index.php | grep -v "api/pdc/auto"
```

Si esa salida está **vacía**, el módulo ya no reclama nada bajo ese prefijo: quita **ambos** prefijos
de su lista `rutas` y deja los que le correspondan por sus otras rutas. Si el módulo se quedara sin
ninguna ruta, elimina su bloque entero.

Actualiza también la nota del bloque `slug: 'contratos'` (líneas 122-124), que dice «el resto de
`/api/pdc/*` queda en Listado de Actividades»: si ya no queda resto, la frase es falsa.

- [ ] **Step 2: Regenerar el mapa**

```bash
node scripts/wiki-arquitectura.mjs --escribir
```

Esperado: termina sin error de «ruta sin módulo». Si lo lanza, nombra la ruta huérfana: es un
prefijo mal ajustado en el paso anterior.

- [ ] **Step 3: Revisar el diff generado**

```bash
git diff --stat memoria/arquitectura/
```

Esperado: cambios acotados a las páginas del área `pdc`. Si el script tocó módulos ajenos, revísalo
antes de commitear.

- [ ] **Step 4: Commit**

```bash
git add scripts/wiki-arquitectura.modulos.mjs memoria/arquitectura/
git commit -m "docs(c1): el inventario de rutas deja de listar el PDC viejo"
```

---

### Task 9: Verificación de la condición de hecho

No introduce cambios de comportamiento: comprueba los puntos 3, 4 y 5 del spec y deja la evidencia.

**Files:**
- Create: `goals/pdc-preparar-b1/evidence/c1-verificacion-retiro.md`
- Modify: `goals/pdc-preparar-b1/validation-log.md`

**Interfaces:**
- Consumes: todo lo anterior, ya commiteado.

- [ ] **Step 1: Punto 3 — la aplicación arranca y `/pdc` ya no existe**

```bash
docker compose ps
curl -s -o /dev/null -w "/pdc -> %{http_code}\n" http://localhost:8081/pdc
curl -s -o /dev/null -w "/api/pdc/list -> %{http_code}\n" -X POST http://localhost:8081/api/pdc/list
curl -s -o /dev/null -w "/api/pdc/plantillas -> %{http_code}\n" http://localhost:8081/api/pdc/plantillas
```

Esperado: los tres → `404`.

- [ ] **Step 2: Punto 3 — el grupo C sigue en pie**

Abre sesión por la puerta de servicio y comprueba que los módulos compartidos funcionan:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -c /tmp/c1.jar \
  "http://localhost:8081/dev/entrar?u=test.A&p=PDC%20Sandbox%20E2E"
curl -s -o /dev/null -w "/contratos -> %{http_code}\n" -b /tmp/c1.jar http://localhost:8081/contratos
curl -s -o /dev/null -w "/listado-actividades -> %{http_code}\n" -b /tmp/c1.jar http://localhost:8081/listado-actividades
curl -s -o /dev/null -w "/plan-compras -> %{http_code}\n" -b /tmp/c1.jar http://localhost:8081/plan-compras
curl -s -o /dev/null -w "auto/preview -> %{http_code}\n" -b /tmp/c1.jar \
  -X POST http://localhost:8081/api/pdc/auto/preview -H 'Content-Type: application/json' -d '{}'
```

Esperado: `200` en las tres pantallas. En `auto/preview`, cualquier cosa **menos** `404`: un `400` o
`403` por falta de CSRF o de payload demuestra que la ruta sigue registrada, que es lo que se mide.

- [ ] **Step 3: Punto 4 — ninguna pantalla enlaza a `/pdc`**

```bash
for r in /contratos /listado-actividades /plan-compras /programacion-semanal /indicadores; do
  echo -n "$r: "; curl -s -b /tmp/c1.jar "http://localhost:8081$r" | grep -c 'href="/pdc"'
done
```

Esperado: `0` en todas.

- [ ] **Step 4: Punto 4 — la suite pasa**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
docker compose exec app php tests/test_lacp_legacy_cleanup_readiness.php
docker compose exec app php tests/test_shell_sidebar_partial.php
npm run test:design-system:static
node scripts/design-system-contracts.mjs
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Anota el resultado literal de cada uno. El único rojo tolerado es
`foundation.test.mjs:273` por el motivo ambiental ya descrito; **cualquier otro rojo bloquea el
cierre**.

- [ ] **Step 5: Punto 5 — `PdcResetService` sigue funcionando**

Es el punto que el spec marca como no dable por supuesto.

```bash
ls -la src/Services/Pdc/PdcResetService.php src/Controllers/*/PdcMaintenanceController.php
grep -rn "PdcMaintenanceController" public/index.php
docker compose exec app php -r '
require "/var/www/html/vendor/autoload.php";
$c = new ReflectionClass("App\\Services\\Pdc\\PdcResetService");
echo $c->getFileName(), "\n";
foreach ($c->getMethods(ReflectionMethod::IS_PUBLIC) as $m) { echo "  ", $m->getName(), "\n"; }
'
```

Esperado: los archivos existen, sus rutas siguen registradas, y la clase carga con sus métodos
públicos. Luego ejercítalo de verdad contra el sandbox: entra por la puerta de servicio al panel de
mantenimiento del Plan de Compras y comprueba que **carga y responde** con sus cuatro salvaguardas.
Si existe un test sembrado para él, córrelo:

```bash
ls tests/ | grep -i "reset\|maintenance"
```

y ejecuta el que aparezca. Deja captura o salida como evidencia.

- [ ] **Step 6: Comprobación visual en el navegador**

Con el navegador integrado, a 1180×820 y en dark:
- `/plan-compras` — el PDC v2 sigue funcionando y es ahora **el** plan de compras.
- `/contratos` y `/listado-actividades` — la nav superior muestra dos entradas, no tres, y no hay
  hueco ni salto de maquetación donde estaba la tercera.
- El sidebar no ofrece «Plan de Compras (clásico)».
- Consola sin errores nuevos; sin overflow horizontal.

Guarda las capturas en `goals/pdc-preparar-b1/evidence/`.

- [ ] **Step 7: Escribir la evidencia y actualizar el log**

Crea `goals/pdc-preparar-b1/evidence/c1-verificacion-retiro.md` con una tabla comprobación →
comando → resultado literal, y la lista de lo que quedó **deliberadamente sin tocar** (grupo C,
`OperationalFamilyPolicy`, `PdcResetService`, las tablas, los `docs/**` narrativos, y los comentarios
de CSS que ahora citan un `pdc.css` inexistente — pendiente diferido, no olvido).

Añade la entrada correspondiente a `goals/pdc-preparar-b1/validation-log.md`.

- [ ] **Step 8: Commit**

```bash
git add goals/pdc-preparar-b1/
git commit -m "docs(c1): evidencia de verificacion del retiro del PDC viejo"
```

---

## Fuera de alcance, anotado a propósito

- **Los datos.** Las tablas `pdc` (370 filas, 4 obras) y `papelera_pdc` se quedan. Decisión de Felipe
  del 2026-07-30, opción A.
- **Renombrar el namespace `/api/pdc/auto/*`.** Es decisión de producto aparte.
- **Los comentarios de `public/css/*.css`** que explican por qué una regla quedó inerte «porque
  `pdc.css` la pisaba». Tras el retiro son historia desactualizada; reescribirlos dentro de este
  retiro mezclaría dos cosas. Se anota como pendiente diferible.
- **Los `docs/**` narrativos y `memoria/`**, salvo `memoria/arquitectura/` que se regenera desde el
  código. Registrar lo que pasó es su función.
