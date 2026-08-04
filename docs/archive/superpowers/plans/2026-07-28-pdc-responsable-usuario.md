# Responsable de paquete como usuario — Plan de implementación

> **For agentic workers:** Ejecución **inline con TDD estricto** (`superpowers:test-driven-development`). El harness de esta sesión prohíbe despachar subagentes salvo petición explícita del usuario, así que NO se usa `superpowers:subagent-driven-development`. Los pasos usan checkbox (`- [ ]`) para seguimiento.

**Goal:** Reparar el endpoint `POST /plan-compras/api/plan/responsable`, que sigue escribiendo en la columna `responsable` eliminada por la migración `20260728_pdc_v2_responsable_usuario.sql`, y convertir la asignación de responsable en una elección de usuario real del proyecto, de punta a punta.

**Architecture:** La lógica verificable baja a `PlanFechasService` (`asignarResponsable()`, `responsablesElegibles()`) porque ningún test de este repo sabe fingir `php://input` — los 103 `tests/test_*.php` ejercitan servicios, no controladores HTTP. El controlador queda como adaptador fino: valida el cuerpo y traduce códigos a mensajes. `plan()` gana un `LEFT JOIN` que resuelve el nombre del responsable en el servidor, para que la pantalla no tenga que cruzar ids contra una lista.

**Tech Stack:** PHP 8.2 + PDO/MySQL (repo `lps-aia-pdc`), React + TypeScript + Vite + AG Grid + Vitest (repo `plan-de-compras`), Playwright para e2e.

## Estado al entregar este plan (2026-07-28, 11:2x)

Este plan se escribió y se aprobó en una sesión que **no lo ejecutó**: mientras se planificaba, otra sesión trabajando en el mismo worktree commiteó parte de lo mismo. Antes de arrancar, comprobar el estado real — puede haber avanzado más desde entonces.

| Tarea | Estado verificado |
|---|---|
| Task 1 · `responsablesElegibles()` | ❌ Pendiente. El método no existe. |
| Task 1 Step 1 · limpieza en `$limpiar()` | ⚠️ **Ya aplicado y sin commitear** en `tests/test_pdc_v2_plan_fechas.php:28-32`. Verificado en verde. No repetirlo. |
| Task 2 · `asignarResponsable()` | ❌ Pendiente. El método no existe. |
| Task 3 · `plan()` con nombre y huérfano | ✅ **Hecho** en `2b46d22`, con la misma definición de huérfano que este plan. **Falta solo `responsableCargo`** — ver Task 3-bis. |
| Task 4 · controlador y ruta | ❌ **Pendiente — este es el bug reportado.** `PlanComprasPlanController.php:140` sigue con `UPDATE ... SET responsable = ?` contra la columna eliminada. |
| Tasks 5-8 · SPA, bundle, e2e | ❌ Pendientes. |

**Ojo con el working tree:** al entregar este plan había 6 archivos de test modificados y un `tests/support/familias_revision_obligatoria.php` sin rastrear, de un tema **ajeno** a esta tarea. Por eso todo `git add` de este plan va con rutas explícitas.

## Global Constraints

- **Español** en código, comentarios, mensajes de commit y textos de interfaz.
- **Stack Docker correcto:** `lps-aia-pdc` (red `lps-aia-pdc_default`, MySQL en **3308**). El stack `last-planner-aia` (3307) pertenece a otro directorio y **no se toca**.
- **DAPORTO (`project_id = 73`) intacto:** `pdc_paquete_frente`, `pdc_plan_paquete` y `pdc_plan_paso` en **0 filas**; `pdc_insumo_paquete` en **395**; versión activa 292. Los tests usan **999903 / 999904** (PHP) y **990100** (e2e).
- **PHPStan nivel 6 sin línea base** para el módulo PDC. Los errores de tipo se arreglan **con anotaciones**, nunca añadiendo entradas a una línea base — este gate no tiene.
- **Otra sesión está commiteando en este mismo worktree.** Todo `git add` debe listar **rutas explícitas**. Prohibido `git add -A`, `git add .` o `git commit -a`.
- Un commit por repositorio (decisión del grilleo).

## Decisiones vinculantes del grilleo (`goals/pdc-responsable-usuario/interview-result.json`)

| # | Decisión |
|---|---|
| 1 | El **servidor** manda el nombre junto al plan (no la pantalla cruzando ids). |
| 2 | Se **puede vaciar** el responsable. |
| 3 | `responsable_asignado_por` guarda el **nombre de sesión** de quien asigna, igual que `amarrar`/`calcular`. |
| 4 | El **huérfano se marca ahora** (asignado pero ya no miembro activo). |
| 5 | Asignar a alguien ajeno al proyecto se **rechaza** con mensaje claro. |
| 6 | Solo **miembros activos** del proyecto son elegibles. |
| 7 | La lista devuelve **nombre y cargo**. |
| 8 | El listado exige el permiso de **ver**, no el de editar. |
| 9 | La celda es un **desplegable**. |
| 10 | Alcance: **los dos repos**. |
| 11 | **Sí** se añade una prueba de navegador nueva. |
| 12 | **Un commit por repo**. |

## Estructura de archivos

**Repo `lps-aia-pdc`:**
- Modificar `src/Services/Pdc/PlanFechasService.php` — `responsablesElegibles()`, `asignarResponsable()`, `plan()` con nombre/cargo/huérfano.
- Modificar `src/Controllers/Api/PlanComprasPlanController.php` — `responsable()` reescrito, `responsables()` nuevo.
- Modificar `public/index.php` — ruta GET nueva.
- Modificar `tests/test_pdc_v2_plan_fechas.php` — bloque de tests del responsable.
- Crear `tests/browser/pdc-v2-responsable.spec.mjs` — e2e del desplegable.
- Modificar `.gitignore` — allowlist del spec nuevo (sin esto **no se commitea**).

**Repo `plan-de-compras`:**
- Modificar `src/lib/types.ts` — `FilaPlan` y `ResponsableElegible`.
- Modificar `src/lib/planFechas.ts` — helpers puros de etiqueta/opciones/id.
- Modificar `src/lib/planFechas.test.ts` — tests de esos helpers.
- Modificar `src/pages/PlanFechas.tsx` — carga de elegibles y columna desplegable.

## Contrato de datos (fuente única de nombres)

```
GET  /plan-compras/api/plan/responsables
     → {"ok":true,"data":{"responsables":[{"id":366,"nombre":"Test Admin","cargo":"Administrador"}]}}

POST /plan-compras/api/plan/responsable
     body: {"paqueteId":123,"responsableUserId":366}   // null o ausente = dejar sin responsable
     → {"ok":true,"data":{"ok":true}}
     → 422 PAQUETE_INVALIDO | RESPONSABLE_INVALIDO | PAQUETE_SIN_PLAN | RESPONSABLE_NO_ELEGIBLE

GET  /plan-compras/api/plan  (filas del plan, campos nuevos)
     responsableUserId: int|null
     responsableNombre: string   // '' si no hay
     responsableCargo:  string   // '' si no hay
     responsableHuerfano: bool   // true si hay id pero ya no es miembro activo
```

---

### Task 1: El servicio sabe quién puede ser responsable

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Produces: `PlanFechasService::responsablesElegibles(int $projectId): list<array{id:int,nombre:string,cargo:string}>` — miembros de `project_members` con `general_usuarios.activo = 1`, ordenados por nombre.

- [ ] **Step 1: Aislar los usuarios de prueba en `$limpiar()`**

El bloque actual mete un usuario REAL en `project_members` del proyecto 999903 y nunca lo borra: residuo entre corridas que haría no determinista cualquier aserción de conteo. En `tests/test_pdc_v2_plan_fechas.php`, dentro de `$limpiar`, añadir tras el `DELETE` de `pdc_insumo_paquete`:

```php
    $db->query('DELETE FROM project_members WHERE project_id IN (?, ?)', [$P, $P2]);
    // Usuarios sintéticos del bloque «responsable»: se borran DESPUÉS de project_members y de
    // pdc_plan_paquete (la FK fk_ppp_responsable es ON DELETE SET NULL, así que el orden no rompe,
    // pero borrarlos al final deja el rastro más fácil de leer si algo falla a media corrida).
    $db->query("DELETE FROM general_usuarios WHERE usuario LIKE 'zztest-a4-%'");
```

- [ ] **Step 2: Escribir el test que falla**

Sustituir el bloque `// --- Responsable como usuario del proyecto ---` (líneas ~571-579, desde el comentario hasta el `UPDATE pdc_plan_paquete SET responsable_user_id`) por:

```php
// --- Responsable como usuario del proyecto ---
// Tres usuarios sintéticos en vez de tomar «el primero que haya» en general_usuarios: hacen falta
// los tres casos de elegibilidad (miembro activo / ajeno al proyecto / miembro dado de baja) y
// tomarlos de datos reales dejaría el test a merced de qué haya sembrado en la base.
$crearUsuario = static function (string $sufijo, string $nombre, string $cargo, int $activo) use ($db): int {
    $db->query(
        'INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, activo)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$nombre, "zztest-a4-{$sufijo}@example.test", $cargo, "zztest-a4-{$sufijo}", 'x', $activo],
    );
    return (int) $db->lastInsertId();
};
$uid = $crearUsuario('miembro', 'ZZ Test Residente', 'Residente de Obra', 1);
$uidExterno = $crearUsuario('externo', 'ZZ Test Externo', 'Ajeno al proyecto', 1);
$uidBaja = $crearUsuario('baja', 'ZZ Test De Baja', 'Dado de baja', 0);

$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uidBaja, 'U']);

$elegibles = $svc->responsablesElegibles($P);
$idsElegibles = array_column($elegibles, 'id');

$assert(in_array($uid, $idsElegibles, true),
    'Elegibles: el miembro activo del proyecto aparece en la lista. Dio ' . json_encode($idsElegibles));
$assert(!in_array($uidExterno, $idsElegibles, true),
    'Elegibles: quien NO es miembro del proyecto queda fuera de la lista.');
$assert(!in_array($uidBaja, $idsElegibles, true),
    'Elegibles: un miembro con activo = 0 queda fuera de la lista.');

$elegido = null;
foreach ($elegibles as $e) { if ($e['id'] === $uid) { $elegido = $e; } }
$assert($elegido !== null && $elegido['nombre'] === 'ZZ Test Residente' && $elegido['cargo'] === 'Residente de Obra',
    'Elegibles: cada fila trae id, nombre y cargo. Dio ' . json_encode($elegido));

$nombresElegibles = array_column($elegibles, 'nombre');
$ordenados = $nombresElegibles;
sort($ordenados, SORT_STRING);
$assert($nombresElegibles === $ordenados,
    'Elegibles: la lista sale ordenada por nombre. Dio ' . json_encode($nombresElegibles));

$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);
```

- [ ] **Step 3: Verificar que falla por el motivo correcto**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^(FAIL|PHP Fatal|Fatal)" | head -5
```

Esperado: `PHP Fatal error: Uncaught Error: Call to undefined method App\Services\Pdc\PlanFechasService::responsablesElegibles()`. Si falla por otra cosa (sintaxis, FK, columna), arreglar eso antes de seguir.

- [ ] **Step 4: Implementar lo mínimo**

En `src/Services/Pdc/PlanFechasService.php`, junto a los demás métodos de lectura:

```php
    /**
     * Usuarios que pueden ser responsables de un paquete en este proyecto.
     *
     * La FK `fk_ppp_responsable` solo garantiza que el usuario EXISTE, no que pertenezca a este
     * proyecto: sin este filtro, un id de otra obra pasaría la restricción de la base sin que nada
     * lo notara. Esta lista es, por tanto, la definición de «elegible» que usan tanto el selector de
     * la pantalla como la validación de `asignarResponsable()` — deliberadamente una sola.
     *
     * @return list<array{id: int, nombre: string, cargo: string}>
     */
    public function responsablesElegibles(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT u.id, u.nombre, u.cargo
             FROM project_members pm
             JOIN general_usuarios u ON u.id = pm.user_id
             WHERE pm.project_id = ? AND u.activo = 1
             ORDER BY u.nombre',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'nombre' => (string) $r['nombre'],
                'cargo' => (string) $r['cargo'],
            ];
        }
        return $out;
    }
```

- [ ] **Step 5: Verificar que pasa**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===` y cinco `PASS:` nuevos con prefijo `Elegibles:`.

---

### Task 2: El servicio asigna y vacía el responsable

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `responsablesElegibles()` de Task 1.
- Produces: `PlanFechasService::asignarResponsable(int $projectId, int $paqueteId, ?int $responsableUserId, string $usuario): array{ok: bool, code?: string}` — códigos `PAQUETE_SIN_PLAN`, `RESPONSABLE_NO_ELEGIBLE`.

- [ ] **Step 1: Escribir el test que falla**

Justo después del bloque de Task 1 (tras el `UPDATE pdc_plan_paquete SET responsable_user_id`), añadir:

```php
// --- Asignar responsable (lo que el endpoint roto hacía contra la columna eliminada) ---
$leerResponsable = static function () use ($db, $P, $paqEstructura): array {
    $r = $db->query(
        'SELECT responsable_user_id, responsable_asignado_por, responsable_asignado_at
         FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
        [$P, $paqEstructura],
    )->fetch(\PDO::FETCH_ASSOC);
    return $r === false ? [] : $r;
};

$r = $svc->asignarResponsable($P, $paqEstructura, $uid, 'jefa-compras');
$assert(($r['ok'] ?? false) === true, 'Asignar: asignar a un miembro activo funciona. Dio ' . json_encode($r));

$guardado = $leerResponsable();
$assert((int) ($guardado['responsable_user_id'] ?? 0) === $uid,
    'Asignar: se guarda el id del usuario. Dio ' . var_export($guardado['responsable_user_id'] ?? null, true));
$assert(($guardado['responsable_asignado_por'] ?? '') === 'jefa-compras',
    'Asignar: se guarda QUIÉN asignó. Dio ' . var_export($guardado['responsable_asignado_por'] ?? null, true));
$assert(($guardado['responsable_asignado_at'] ?? null) !== null,
    'Asignar: se guarda CUÁNDO se asignó. Dio ' . var_export($guardado['responsable_asignado_at'] ?? null, true));

// Alguien de otro proyecto: la FK lo aceptaría (el usuario existe), así que esta es la única
// defensa real contra asignar un paquete a quien no trabaja en esta obra.
$r = $svc->asignarResponsable($P, $paqEstructura, $uidExterno, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'RESPONSABLE_NO_ELEGIBLE',
    'Asignar: un usuario ajeno al proyecto se rechaza con RESPONSABLE_NO_ELEGIBLE. Dio ' . json_encode($r));
$assert((int) ($leerResponsable()['responsable_user_id'] ?? 0) === $uid,
    'Asignar: un rechazo NO pisa el responsable que ya estaba guardado.');

$r = $svc->asignarResponsable($P, $paqEstructura, $uidBaja, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'RESPONSABLE_NO_ELEGIBLE',
    'Asignar: un miembro dado de baja también se rechaza. Dio ' . json_encode($r));

// Vaciar: se conserva el rastro de quién lo quitó — la columna es «quién tocó la asignación»,
// no «quién puso a alguien», y perder eso deja un paquete sin dueño sin saber quién lo dejó así.
$r = $svc->asignarResponsable($P, $paqEstructura, null, 'auditor');
$assert(($r['ok'] ?? false) === true, 'Asignar: vaciar el responsable funciona. Dio ' . json_encode($r));
$vaciado = $leerResponsable();
$assert($vaciado['responsable_user_id'] === null,
    'Asignar: vaciar deja responsable_user_id en NULL. Dio ' . var_export($vaciado['responsable_user_id'], true));
$assert(($vaciado['responsable_asignado_por'] ?? '') === 'auditor',
    'Asignar: vaciar deja constancia de quién lo quitó. Dio ' . var_export($vaciado['responsable_asignado_por'] ?? null, true));

// Paquete sin plan calculado: el id 987654321 no existe en pdc_plan_paquete de este proyecto.
$r = $svc->asignarResponsable($P, 987654321, $uid, 'jefa-compras');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'PAQUETE_SIN_PLAN',
    'Asignar: un paquete sin plan calculado da PAQUETE_SIN_PLAN. Dio ' . json_encode($r));

// Se deja asignado para las comprobaciones de plan() que vienen después.
$svc->asignarResponsable($P, $paqEstructura, $uid, 'jefa-compras');
```

- [ ] **Step 2: Verificar que falla por el motivo correcto**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^(FAIL|PHP Fatal|Fatal)" | head -3
```

Esperado: `Call to undefined method ...::asignarResponsable()`.

- [ ] **Step 3: Implementar lo mínimo**

En `src/Services/Pdc/PlanFechasService.php`, tras `responsablesElegibles()`:

```php
    /**
     * Asigna (o retira, con `$responsableUserId = null`) el responsable de un paquete.
     *
     * No se usa `rowCount()` del UPDATE para decidir si la fila existe: este repo no activa
     * PDO::MYSQL_ATTR_FOUND_ROWS (ver Database.php), así que MySQL reporta filas MODIFICADAS, no
     * coincidentes — guardar el mismo responsable dos veces seguidas daría 0 y parecería que el
     * paquete no tiene plan. La existencia se confirma con un SELECT explícito.
     *
     * @return array{ok: bool, code?: string}
     */
    public function asignarResponsable(
        int $projectId,
        int $paqueteId,
        ?int $responsableUserId,
        string $usuario
    ): array {
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, $paqueteId],
        )->fetchColumn();
        if ($existe === false) {
            return ['ok' => false, 'code' => 'PAQUETE_SIN_PLAN'];
        }

        if ($responsableUserId !== null) {
            $elegible = false;
            foreach ($this->responsablesElegibles($projectId) as $e) {
                if ($e['id'] === $responsableUserId) {
                    $elegible = true;
                    break;
                }
            }
            if (!$elegible) {
                return ['ok' => false, 'code' => 'RESPONSABLE_NO_ELEGIBLE'];
            }
        }

        // `responsable_asignado_por` se escribe también al vaciar: la columna registra quién tocó
        // la asignación por última vez, y quitar a alguien es justo el movimiento que más interesa
        // poder rastrear después.
        $this->db->query(
            'UPDATE pdc_plan_paquete
                SET responsable_user_id = ?, responsable_asignado_por = ?, responsable_asignado_at = NOW()
              WHERE project_id = ? AND paquete_id = ?',
            [$responsableUserId, mb_substr($usuario, 0, 100), $projectId, $paqueteId],
        );

        return ['ok' => true];
    }
```

- [ ] **Step 4: Verificar que pasa**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===`.

---

### Task 3: `plan()` resuelve el nombre y marca al huérfano — ✅ YA HECHO en `2b46d22`

> **No reimplementar.** El commit `2b46d22` ya añadió los dos `LEFT JOIN`, `responsableNombre`, `responsableHuerfano` (con la misma definición que este plan: sin miembro **o** con `activo != 1`) y sus tests. **Los Steps 1-6 de abajo quedan como referencia histórica de por qué está hecho así.** Lo único que falta es la Task 3-bis.

---

### Task 3-bis: añadir `responsableCargo` a `plan()`

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php` (método `plan()` y su `@return`)
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Produces: cada fila de `plan()` gana `responsableCargo: string`.

**Por qué hace falta:** la decisión 7 del grilleo es que el desplegable muestre **nombre y cargo** (el cargo desempata nombres parecidos). La etiqueta que se ve y se elige es `«Nombre — Cargo»`, y la celda tiene que poder mostrar esa misma etiqueta para el responsable ya guardado. Con solo el nombre, la fila cargada del servidor y la opción del desplegable no coinciden, y AG Grid no puede preseleccionar el valor actual.

- [ ] **Step 1: Escribir el test que falla**

Junto a las aserciones de `responsableNombre` que dejó `2b46d22`, añadir:

```php
$assert(($porId4[$paqEstructura]['responsableCargo'] ?? null) === 'Residente de Obra',
    'Plan: la fila trae el cargo del responsable (desempata nombres parecidos en el selector). Dio '
    . var_export($porId4[$paqEstructura]['responsableCargo'] ?? null, true));
```

Ajustar el cargo esperado al del usuario que use el fixture ya existente.

- [ ] **Step 2: Verificar que falla**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep "^FAIL" | head -3
```

Esperado: `FAIL: Plan: la fila trae el cargo del responsable ... Dio NULL`.

- [ ] **Step 3: Implementar**

En el `SELECT` de `plan()`, añadir `u.cargo AS responsable_cargo` junto a `u.nombre AS responsable_nombre`. En el mapeo, junto a `'responsableNombre'`:

```php
                'responsableCargo' => (string) ($r['responsable_cargo'] ?? ''),
```

Y en el `@return`, tras `responsableNombre: string,`:

```
      *     responsableCargo: string,
```

- [ ] **Step 4: Verificar que pasa**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -2
```

Esperado: `=== OK ===`.

---

<details>
<summary>Task 3 original (referencia — ya implementada por <code>2b46d22</code>)</summary>

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php:875-922` (método `plan()` y su docblock en `:840-874`)
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Produces: cada fila de `plan()` gana `responsableNombre: string`, `responsableCargo: string`, `responsableHuerfano: bool`. `responsableUserId` se conserva tal cual.

- [ ] **Step 1: Escribir el test que falla**

Tras la aserción existente `'Responsable: \`responsable_user_id\` sobrevive a un recálculo...'`, añadir:

```php
// El servidor resuelve el nombre (decisión 1 del grilleo): la pantalla no cruza ids contra la
// lista de elegibles, porque a un responsable que ya salió del proyecto no lo encontraría ahí y
// la celda quedaría en blanco sin explicación.
$assert(($porId4[$paqEstructura]['responsableNombre'] ?? null) === 'ZZ Test Residente',
    'Plan: la fila trae el NOMBRE del responsable, no solo su id. Dio '
    . var_export($porId4[$paqEstructura]['responsableNombre'] ?? null, true));
$assert(($porId4[$paqEstructura]['responsableCargo'] ?? null) === 'Residente de Obra',
    'Plan: la fila trae el cargo del responsable. Dio '
    . var_export($porId4[$paqEstructura]['responsableCargo'] ?? null, true));
$assert(($porId4[$paqEstructura]['responsableHuerfano'] ?? null) === false,
    'Plan: un responsable que sigue siendo miembro activo NO está huérfano.');

$sinResponsable = null;
foreach ($plan4 as $f) { if ($f['paqueteId'] !== $paqEstructura) { $sinResponsable = $f; break; } }
$assert($sinResponsable !== null && $sinResponsable['responsableNombre'] === ''
    && $sinResponsable['responsableCargo'] === '' && $sinResponsable['responsableHuerfano'] === false,
    'Plan: un paquete sin responsable trae cadenas vacías y huérfano = false, nunca null. Dio '
    . json_encode($sinResponsable === null ? null : [
        $sinResponsable['responsableNombre'], $sinResponsable['responsableCargo'], $sinResponsable['responsableHuerfano'],
    ]));

// Huérfano (decisión 4): sacar a alguien de project_members NO borra su ficha ni dispara la FK
// ON DELETE SET NULL, así que su nombre se queda pegado al paquete. Si la lectura no lo señala,
// ese paquete queda sin dueño real y nadie se entera.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $uid]);
$planHuerfano = [];
foreach ($svc->plan($P) as $f) { $planHuerfano[$f['paqueteId']] = $f; }

$assert(($planHuerfano[$paqEstructura]['responsableHuerfano'] ?? null) === true,
    'Plan: sacar al responsable del proyecto lo marca como huérfano. Dio '
    . var_export($planHuerfano[$paqEstructura]['responsableHuerfano'] ?? null, true));
$assert(($planHuerfano[$paqEstructura]['responsableNombre'] ?? null) === 'ZZ Test Residente',
    'Plan: el huérfano CONSERVA su nombre — se señala el problema, no se borra el dato.');
$assert(($planHuerfano[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Plan: el huérfano conserva su id (la FK no se disparó: salir del proyecto no borra al usuario).');

// Se devuelve a la normalidad para no dejar el fixture a medias si alguien añade asserts debajo.
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);
```

- [ ] **Step 2: Verificar que falla por el motivo correcto**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep "^FAIL" | head -3
```

Esperado: `FAIL: Plan: la fila trae el NOMBRE del responsable, no solo su id. Dio NULL`.

- [ ] **Step 3: Implementar — la consulta**

En `plan()`, sustituir el `SELECT ... FROM pdc_plan_paquete pp` por:

```php
        $rows = $this->db->query(
            "SELECT pp.paquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque, pp.dias_totales,
                    pp.duracion_provisional, pp.responsable_user_id, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre,
                    ru.nombre AS responsable_nombre, ru.cargo AS responsable_cargo,
                    ru.activo AS responsable_activo, rpm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             LEFT JOIN general_usuarios ru ON ru.id = pp.responsable_user_id
             LEFT JOIN project_members rpm
                    ON rpm.project_id = pp.project_id AND rpm.user_id = pp.responsable_user_id
             WHERE pp.project_id = ? AND p.activo = 1
               AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
```

- [ ] **Step 4: Implementar — el mapeo**

En el `foreach ($rows as $r)`, sustituir la línea de `'responsableUserId' => ...` por:

```php
                'responsableUserId' => $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'],
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                'responsableCargo' => (string) ($r['responsable_cargo'] ?? ''),
                // Huérfano = tiene responsable asignado pero ya no es miembro activo. Cubre los dos
                // caminos por los que alguien deja de ser elegible sin que la FK se entere: salir de
                // project_members y quedar con activo = 0. Es la misma definición que
                // responsablesElegibles(), leída al revés.
                'responsableHuerfano' => $r['responsable_user_id'] !== null
                    && ($r['responsable_miembro'] === null || (int) $r['responsable_activo'] !== 1),
```

- [ ] **Step 5: Actualizar el docblock (obligatorio para PHPStan nivel 6)**

En el `@return` de `plan()` (`:864`), sustituir la línea `*     responsableUserId: int|null,` por:

```
      *     responsableUserId: int|null,
      *     responsableNombre: string,
      *     responsableCargo: string,
      *     responsableHuerfano: bool,
```

- [ ] **Step 6: Verificar que pasa**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===`.

</details>

---

### Task 4: El controlador y la ruta nueva — ⭐ EMPEZAR POR AQUÍ

> Esta es la tarea que arregla **el bug reportado**: `PlanComprasPlanController.php:140` sigue escribiendo en la columna `responsable`, eliminada por la migración. Depende de las Tasks 1 y 2 (los dos métodos del servicio), así que el orden real de arranque es **1 → 2 → 4**.

**Files:**
- Modify: `src/Controllers/Api/PlanComprasPlanController.php:106-145`
- Modify: `public/index.php:221-227`

**Interfaces:**
- Consumes: `asignarResponsable()` y `responsablesElegibles()` de Tasks 1-2.
- Produces: `GET /plan-compras/api/plan/responsables`; `POST /plan-compras/api/plan/responsable` con `responsableUserId`.

Sin test PHP propio: ningún test de este repo sabe fingir `php://input`, y por eso la lógica vive en el servicio (ya cubierta por Tasks 1-3). Lo que este controlador añade —validación del cuerpo y traducción de códigos a mensajes— lo cubre el e2e de la Task 7.

- [ ] **Step 1: Reescribir `responsable()`**

Sustituir el método completo (`:106-145`) por:

```php
    /** POST /plan-compras/api/plan/responsable  {paqueteId, responsableUserId} — null lo deja sin responsable */
    public function responsable(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }

        // Ausente y null significan lo mismo —dejar el paquete sin responsable— y hay que
        // distinguirlos de un id con basura dentro: `filter_var(null, FILTER_VALIDATE_INT)` también
        // devuelve false, así que sin este orden «vaciar» se respondería como error de formato.
        $crudo = $body['responsableUserId'] ?? null;
        $responsableUserId = null;
        if ($crudo !== null) {
            $validado = filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($validado === false) {
                $this->fail('RESPONSABLE_INVALIDO', 'responsableUserId inválido.', 422);
                return;
            }
            $responsableUserId = (int) $validado;
        }

        $r = $this->service->asignarResponsable($projectId, (int) $paqueteId, $responsableUserId, $this->usuario());
        if (!$r['ok']) {
            $mensaje = ($r['code'] ?? '') === 'RESPONSABLE_NO_ELEGIBLE'
                ? 'Esa persona no pertenece al equipo activo de este proyecto.'
                : 'Este paquete todavía no tiene plan de compras calculado. Calcula el plan antes de asignar responsable.';
            $this->fail($r['code'] ?? 'PAQUETE_SIN_PLAN', $mensaje, 422);
            return;
        }

        $this->ok(['ok' => true]);
    }

    /** GET /plan-compras/api/plan/responsables — quién puede ser responsable en este proyecto */
    public function responsables(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['responsables' => $this->service->responsablesElegibles($projectId)]);
    }
```

- [ ] **Step 2: Registrar la ruta**

En `public/index.php`, tras la línea 223 (`.../plan/desfases`):

```php
$router->get('/plan-compras/api/plan/responsables', [\App\Controllers\Api\PlanComprasPlanController::class, 'responsables']);
```

- [ ] **Step 3: Pasar el gate de PHPStan**

```bash
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
```

Esperado: `[OK] No errors`. Si aparecen errores de tipo, arreglarlos **con anotaciones** en el código — este gate no tiene línea base y no se le añade ninguna.

- [ ] **Step 4: Verificar los gates PHP completos**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -2 && docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php 2>&1 | tail -2 && docker compose exec -T app php tests/test_pdc_phpstan_nivel6.php 2>&1 | tail -2
```

Esperado: `=== OK ===`, `difieren 7` + `PASS`, y el test de PHPStan en verde.

- [ ] **Step 5: Comprobar que DAPORTO sigue intacto**

```bash
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -N -e "SELECT (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73), (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73), (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73), (SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id=73);"'
```

Esperado exacto: `0	0	0	395`.

---

### Task 5: Los tipos y helpers puros de la pantalla

**Files:**
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/types.ts:335-349`
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/planFechas.ts:171-179`
- Test: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/planFechas.test.ts`

**Interfaces:**
- Consumes: el contrato JSON de Tasks 3-4.
- Produces: `etiquetaElegible()`, `etiquetaResponsableFila()`, `opcionesResponsable()`, `idPorEtiqueta()`, `valorResponsableMostrado()` (firma nueva), tipo `ResponsableElegible`.

- [ ] **Step 1: Actualizar los tipos**

En `src/lib/types.ts`, dentro de `FilaPlan`, sustituir `responsable: string` por:

```ts
  responsableUserId: number | null
  responsableNombre: string
  responsableCargo: string
  responsableHuerfano: boolean
```

Y añadir tras el tipo `FilaPlan`:

```ts
export type ResponsableElegible = {
  id: number
  nombre: string
  cargo: string
}
```

- [ ] **Step 2: Escribir los tests que fallan**

En `src/lib/planFechas.test.ts`, sustituir el bloque `describe` de `valorResponsableMostrado` (líneas ~284-297) por:

```ts
  const ELEGIBLES = [
    { id: 7, nombre: 'Ana Gómez', cargo: 'Residente' },
    { id: 9, nombre: 'Luis Paz', cargo: '' },
  ]

  it('etiqueta a una persona con su cargo, y sin guion cuando no tiene', () => {
    expect(etiquetaElegible(ELEGIBLES[0])).toBe('Ana Gómez — Residente')
    expect(etiquetaElegible(ELEGIBLES[1])).toBe('Luis Paz')
  })

  it('muestra vacío cuando el paquete no tiene responsable', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: null, responsableNombre: '', responsableCargo: '' }), {},
    )).toBe('')
  })

  it('muestra el nombre y el cargo que mandó el servidor', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }), {},
    )).toBe('Ana Gómez — Residente')
  })

  it('avisa cuando el responsable ya no está en el proyecto', () => {
    expect(valorResponsableMostrado(
      fila({
        paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez',
        responsableCargo: 'Residente', responsableHuerfano: true,
      }), {},
    )).toBe('Ana Gómez — Residente (ya no está en el proyecto)')
  })

  it('el override manda sobre el dato del servidor (guardado optimista)', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }),
      { 1: 'Luis Paz' },
    )).toBe('Luis Paz')
  })

  it('un override de OTRA fila no afecta a esta', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }),
      { 2: 'Luis Paz' },
    )).toBe('Ana Gómez — Residente')
  })

  it('las opciones arrancan con el vacío para poder dejar el paquete sin responsable', () => {
    const fSin = fila({ paqueteId: 1, responsableUserId: null, responsableNombre: '', responsableCargo: '' })
    expect(opcionesResponsable(ELEGIBLES, fSin)).toEqual(['', 'Ana Gómez — Residente', 'Luis Paz'])
  })

  it('las opciones incluyen al huérfano para que su celda no aparezca en blanco', () => {
    const fHuerfano = fila({
      paqueteId: 1, responsableUserId: 4, responsableNombre: 'Carla Ruiz',
      responsableCargo: 'Compras', responsableHuerfano: true,
    })
    expect(opcionesResponsable(ELEGIBLES, fHuerfano)).toEqual([
      '', 'Ana Gómez — Residente', 'Luis Paz', 'Carla Ruiz — Compras (ya no está en el proyecto)',
    ])
  })

  it('traduce la etiqueta elegida al id que espera el servidor', () => {
    expect(idPorEtiqueta(ELEGIBLES, 'Ana Gómez — Residente')).toBe(7)
    expect(idPorEtiqueta(ELEGIBLES, 'Luis Paz')).toBe(9)
  })

  it('el vacío y cualquier etiqueta desconocida se traducen a «sin responsable»', () => {
    expect(idPorEtiqueta(ELEGIBLES, '')).toBeNull()
    // El huérfano entra aquí: se puede quitar, pero no se le puede volver a elegir.
    expect(idPorEtiqueta(ELEGIBLES, 'Carla Ruiz — Compras (ya no está en el proyecto)')).toBeNull()
  })
```

Actualizar además el helper `fila()` (líneas ~12 y ~137) sustituyendo `responsable: ''` por:

```ts
  responsableUserId: null, responsableNombre: '', responsableCargo: '', responsableHuerfano: false,
```

Y añadir los nombres nuevos al `import` de `./planFechas` al principio del archivo: `etiquetaElegible`, `opcionesResponsable`, `idPorEtiqueta`.

- [ ] **Step 3: Verificar que falla**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run src/lib/planFechas.test.ts 2>&1 | tail -20
```

Esperado: fallo de importación — `etiquetaElegible is not a function` / `No "opcionesResponsable" export is defined`.

- [ ] **Step 4: Implementar los helpers**

En `src/lib/planFechas.ts`, sustituir `valorResponsableMostrado` por:

```ts
/** Lo que se añade al nombre de quien ya no puede ser responsable. Ver `responsableHuerfano`. */
export const MARCA_HUERFANO = ' (ya no está en el proyecto)'

/** Etiqueta con la que una persona se ve y se elige: el cargo desempata nombres parecidos. */
export function etiquetaElegible(persona: Pick<ResponsableElegible, 'nombre' | 'cargo'>): string {
  return persona.cargo ? `${persona.nombre} — ${persona.cargo}` : persona.nombre
}

/**
 * Etiqueta del responsable que trae la fila del servidor. El servidor manda el nombre resuelto (no
 * solo el id) justamente para este caso: a un huérfano no lo encontraríamos en la lista de
 * elegibles, y la celda quedaría en blanco sin explicar por qué.
 */
export function etiquetaResponsableFila(
  fila: Pick<FilaPlan, 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
): string {
  if (fila.responsableUserId === null || fila.responsableNombre === '') return ''
  const base = etiquetaElegible({ nombre: fila.responsableNombre, cargo: fila.responsableCargo })
  return fila.responsableHuerfano ? `${base}${MARCA_HUERFANO}` : base
}

/**
 * Opciones del desplegable. El '' inicial es lo que permite dejar el paquete sin responsable; el
 * huérfano se añade al final solo si es el valor actual de esta fila, porque AG Grid no puede
 * mostrar un valor que no esté entre las opciones — sin esto, abrir el editor de una fila huérfana
 * borraría de la vista al responsable que sí tiene.
 */
export function opcionesResponsable(
  elegibles: ResponsableElegible[],
  fila: Pick<FilaPlan, 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
): string[] {
  const opciones = ['', ...elegibles.map(etiquetaElegible)]
  const actual = etiquetaResponsableFila(fila)
  return actual !== '' && !opciones.includes(actual) ? [...opciones, actual] : opciones
}

/** Traduce lo elegido en el desplegable al id que espera el servidor. Desconocido y '' → sin responsable. */
export function idPorEtiqueta(elegibles: ResponsableElegible[], etiqueta: string): number | null {
  return elegibles.find((e) => etiquetaElegible(e) === etiqueta)?.id ?? null
}

/**
 * Valor que debe verse en la celda «Responsable». AG Grid muta la fila in-place al confirmar la
 * edición (valueSetter por defecto), sin esperar el POST — por eso el override es la única fuente
 * fiable mientras dura la sesión: guarda lo último confirmado, y si el POST falla se le devuelve el
 * valor anterior.
 */
export function valorResponsableMostrado(
  fila: Pick<FilaPlan, 'paqueteId' | 'responsableUserId' | 'responsableNombre' | 'responsableCargo' | 'responsableHuerfano'>,
  overrides: Record<number, string>,
): string {
  return overrides[fila.paqueteId] ?? etiquetaResponsableFila(fila)
}
```

Añadir `ResponsableElegible` al `import type` de `./types` que ya existe al principio del archivo.

- [ ] **Step 5: Verificar que pasa**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run 2>&1 | tail -8
```

Esperado: los 11 archivos en verde, con más de 115 tests (los 10 nuevos entran y salen 3 viejos).

---

### Task 6: La celda se convierte en desplegable

**Files:**
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/pages/PlanFechas.tsx:60-128, 215-280`

**Interfaces:**
- Consumes: helpers de Task 5, endpoints de Task 4.

- [ ] **Step 1: Cargar la lista de elegibles**

Añadir el estado junto a `responsableOverride` (`:63`):

```tsx
  const [elegibles, setElegibles] = useState<ResponsableElegible[]>([])
```

Y dentro de `cargar()`, tras el bloque de `/plan-compras/api/plan/desfases`:

```tsx
    apiGet<{ responsables: ResponsableElegible[] }>('/plan-compras/api/plan/responsables')
      .then((d) => setElegibles(d.responsables))
      .catch(() => setElegibles([]))
```

Añadir `ResponsableElegible` al import de `../lib/types` y `opcionesResponsable`, `idPorEtiqueta` al de `../lib/planFechas`.

- [ ] **Step 2: Reescribir `onResponsable`**

Sustituir el método completo (`:113-128`) por:

```tsx
  const onResponsable = async (paqueteId: number, etiqueta: string, anterior: string) => {
    // AG Grid ya mutó la fila al valor nuevo (valueSetter por defecto, corrió antes de este
    // handler). El override se fija SIEMPRE, no solo al fallar: es lo único que sabe la etiqueta
    // completa («Nombre — Cargo») que el usuario acaba de elegir, y sin él la celda volvería a
    // pintarse desde unos datos del servidor que todavía son los viejos.
    setResponsableOverride((prev) => ({ ...prev, [paqueteId]: etiqueta }))
    try {
      await apiPost('/plan-compras/api/plan/responsable', {
        paqueteId,
        responsableUserId: idPorEtiqueta(elegibles, etiqueta),
      })
    } catch (e) {
      let mensaje = mensajeError(e)
      if (e instanceof PdcApiError && e.code === 'PAQUETE_SIN_PLAN') {
        mensaje = 'Este paquete todavía no tiene plan calculado; usa «Recalcular» antes de asignar responsable.'
      } else if (e instanceof PdcApiError && e.code === 'RESPONSABLE_NO_ELEGIBLE') {
        mensaje = 'Esa persona ya no pertenece al equipo activo del proyecto; recarga la página para ver la lista al día.'
      }
      dispatch({ type: 'FALLO', mensaje })
      // El guardado no ocurrió: la celda no puede seguir mostrando lo que AG Grid ya escribió.
      setResponsableOverride((prev) => ({ ...prev, [paqueteId]: anterior }))
    }
  }
```

- [ ] **Step 3: Convertir la columna en desplegable**

Sustituir la definición de la columna «Responsable» (`:220-226`) por:

```tsx
    {
      headerName: 'Responsable', colId: 'responsable', field: 'responsableNombre',
      flex: 1, minWidth: 220, editable: true,
      cellEditor: 'agSelectCellEditor',
      // Las opciones se calculan por fila, no una sola vez para la tabla: una fila con responsable
      // huérfano necesita su propia opción extra (ver opcionesResponsable) o AG Grid no podría
      // mostrar el valor que ya tiene.
      cellEditorParams: (p: { data?: FilaPlan }) => ({
        values: p.data ? opcionesResponsable(elegibles, p.data) : [''],
      }),
      valueGetter: (p) => (p.data ? valorResponsableMostrado(p.data, responsableOverride) : ''),
      cellClass: (p) => (p.data?.responsableHuerfano ? 'pdc-plan-responsable-huerfano' : undefined),
      onCellValueChanged: (p) => {
        if (!p.data) return
        void onResponsable(p.data.paqueteId, (p.newValue ?? '').trim(), (p.oldValue ?? '').trim())
      },
    },
```

Y añadir `elegibles` a las dependencias del `useMemo` de `cols` (`:233`):

```tsx
  ], [responsableOverride, desfasePorPaquete, elegibles])
```

- [ ] **Step 4: Arreglar el guard del click de expansión**

La columna ya no se identifica por `field: 'responsable'`. En `onCellClicked` (`:270`), sustituir:

```tsx
            if (!e.data || e.colDef.colId === 'responsable') return
```

- [ ] **Step 5: Verificar los gates de la SPA**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run 2>&1 | tail -6 && npm run build 2>&1 | tail -6
```

Esperado: tests en verde y build sin errores de TypeScript. Si `tsc` se queja de `responsable` en algún sitio no listado aquí, es un consumidor que este plan no detectó: arreglarlo con el campo nuevo que corresponda.

- [ ] **Step 6: Commit de la SPA**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git add src/lib/types.ts src/lib/planFechas.ts src/lib/planFechas.test.ts src/pages/PlanFechas.tsx && git commit -m "feat(pdc): el responsable del plan se elige de la gente del proyecto

La columna «Responsable» era texto libre y quedó rota por partida doble cuando el responsable
pasó a ser un usuario: al guardar apuntaba a una columna eliminada, y al leer buscaba un campo
que el API ya no manda, así que la celda se veía vacía.

Ahora es un desplegable con los miembros activos del proyecto (nombre y cargo, que desempata
nombres parecidos), el vacío deja el paquete sin responsable, y a quien salió del proyecto se le
marca en vez de borrarlo — un paquete sin dueño real tiene que notarse."
```

---

### Task 7: Republicar el bundle de la SPA en este worktree

**Files:**
- Modify: `public/pdc-app/assets/pdc.js`, `public/pdc-app/assets/pdc.css`, `public/pdc-app/BUILD.txt` (generados)

La página `/plan-compras` no sirve el código fuente de `plan-de-compras`: sirve el bundle compilado que vive **commiteado** en `public/pdc-app/`. Sin este paso, la Task 8 probaría la interfaz vieja y el desplegable no existiría.

- [ ] **Step 1: Publicar el bundle apuntando a ESTE worktree**

`scripts/sync-to-lps.sh` toma `LPS_DIR` por defecto como `../lps-aia` — que es **otro repositorio**, el de otras sesiones. Sin la variable, este comando publicaría el bundle en el sitio equivocado. La Task 6 ya dejó el commit de la SPA hecho, así que `BUILD.txt` registrará un hash limpio en vez de `(dirty)`.

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && LPS_DIR="/Volumes/Crucial X6/Developer/lps-aia-pdc" npm run sync 2>&1 | tail -8
```

Esperado: `OK: bundle sincronizado en /Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app`, y un `commit:` en `BUILD.txt` **sin** el sufijo ` (dirty)`.

- [ ] **Step 2: Confirmar que no se tocó el repo vecino**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && git status --short public/pdc-app
```

Esperado: sin salida. Si aparece algo, el bundle se publicó en el repo equivocado: revertirlo ahí (`git checkout -- public/pdc-app`) y repetir el paso 1 con `LPS_DIR` bien puesto.

- [ ] **Step 3: Commit del bundle (chore aparte, como manda la convención del repo)**

`df0c406` es el precedente: el bundle regenerado viaja en su propio `chore(pdc)`, separado del cambio de código.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add public/pdc-app && git commit -m "chore(pdc): republica el bundle con el selector de responsable"
```

---

### Task 8: Prueba de navegador del desplegable

**Files:**
- Create: `tests/browser/pdc-v2-responsable.spec.mjs` (repo `lps-aia-pdc`)
- Modify: `.gitignore`

**Interfaces:**
- Consumes: todo lo anterior, con el bundle ya republicado por la Task 7. Usa el sandbox e2e (proyecto **990100**), que el seed siembra con 5 miembros activos (`Test Admin`, `Test Director`, `Test Residente`, `Test Subcontratista`, `Test Visualizador`) — verificado en la base.

- [ ] **Step 1: Permitir el archivo en `.gitignore`**

`tests/browser/*` está ignorado con allowlist por archivo: sin esta línea el spec **no se commitea** y el trabajo se pierde en silencio. Añadir tras `!tests/browser/pdc-handsontable.mjs`:

```
!tests/browser/pdc-v2-responsable.spec.mjs
```

Verificar:

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git check-ignore -v tests/browser/pdc-v2-responsable.spec.mjs || echo "OK: ya no está ignorado"
```

Esperado: `OK: ya no está ignorado`.

- [ ] **Step 2: Escribir el spec**

Crear `tests/browser/pdc-v2-responsable.spec.mjs`:

```js
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
// Debe coincidir con `PDC_SANDBOX_FRENTE_PLAN` en database/seeds/pdc_e2e_sandbox_project.php.
const PAQUETE_PLAN = 'ZZTEST PAQUETE PLAN';
// Uno de los cinco miembros que el seed mete en project_members del sandbox.
const RESPONSABLE = 'Test Residente — Residente de Obra';

usarSandboxPdc();

function responsableGuardado(projectId) {
  const out = sqlEnApp(
    `$row = $db->query('SELECT responsable_user_id, responsable_asignado_por FROM pdc_plan_paquete `
    + `WHERE project_id = ? AND responsable_user_id IS NOT NULL LIMIT 1', [${projectId}])`
    + `->fetch(PDO::FETCH_ASSOC); echo json_encode($row ?: null);`,
  );
  return JSON.parse(out);
}

// La regresión que cubre: `responsable()` seguía haciendo UPDATE contra la columna `responsable`,
// eliminada por 20260728_pdc_v2_responsable_usuario.sql, así que asignar desde la interfaz fallaba
// SIEMPRE con «Unknown column 'responsable'». Un test que solo mirase la pantalla no lo habría
// visto: la edición es optimista y la celda cambia igual. Por eso se comprueba contra la base.
test('plan: el responsable se elige de la gente del proyecto y se guarda', async ({ page }) => {
  expect(responsableGuardado(project.projectId), 'el sandbox debe empezar sin responsables').toBeNull();

  await loginAndSelectProject(page, project);
  try {
    // Mismo montaje por interfaz que pdc-v2-plan.spec.mjs: hacen falta presupuesto importado,
    // vínculos del maestro e insumo asignado para que exista un paquete que el motor pueda amarrar.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.locator('nav >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    await page.locator('nav >> text=Paquetes').click();
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill(PAQUETE_PLAN);
    await page.locator('[data-testid="pdc-paq-crear-tipo"]').selectOption('a_todo_costo');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    const gridPaquetes = page.locator('[data-testid="pdc-paq-grid"]');
    await page.locator('[data-testid="pdc-paq-filtro"]').selectOption('sin_asignar');
    await expect(gridPaquetes.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await gridPaquetes.locator('.ag-row').first().click();
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });

    const filaConSugerencia = page.locator('[data-testid="pdc-plan-sin-frente"] li:has(.pdc-paq-tag)').first();
    await expect(filaConSugerencia).toBeVisible({ timeout: 20000 });
    await filaConSugerencia.locator('button[data-testid^="pdc-plan-amarrar-"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    // A diferencia de pdc-v2-plan.spec.mjs, aquí SÍ se recalcula: sin fila en pdc_plan_paquete no
    // hay a quién asignarle un responsable (el endpoint respondería PAQUETE_SIN_PLAN).
    await page.locator('[data-testid="pdc-plan-recalcular"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 30000 });

    const grid = page.locator('[data-testid="pdc-plan-grid"]');
    const celda = grid.locator('.ag-row').first().locator('[col-id="responsable"]');
    await expect(celda).toBeVisible({ timeout: 20000 });
    await expect(celda, 'el paquete recién calculado arranca sin responsable').toHaveText('');

    await celda.dblclick();
    const editor = grid.locator('.ag-cell-editor select, select.ag-select-list, .ag-picker-field');
    await expect(editor.first(), 'la celda debe abrir un desplegable, no un campo de texto')
      .toBeVisible({ timeout: 10000 });

    // La lista sale del proyecto: si el endpoint nuevo fallara, no habría ninguna opción con este
    // nombre y esta línea moriría aquí en vez de guardar un valor inventado.
    await grid.locator('.ag-cell-editor select').selectOption({ label: RESPONSABLE });
    await page.keyboard.press('Enter');

    await expect(celda).toHaveText(RESPONSABLE, { timeout: 15000 });
    await expect(page.locator('.pdc-error')).toHaveCount(0);

    // La prueba de verdad: que llegó a la base. La celda cambia igual aunque el POST falle.
    await expect.poll(
      () => responsableGuardado(project.projectId),
      { timeout: 15000, message: 'el responsable elegido debe quedar guardado en pdc_plan_paquete' },
    ).not.toBeNull();

    const guardado = responsableGuardado(project.projectId);
    expect(Number(guardado.responsable_user_id), 'responsable_user_id guardado').toBeGreaterThan(0);
    expect(guardado.responsable_asignado_por, 'se registra quién hizo la asignación').not.toBe('');

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Correr el spec**

El bundle ya está republicado (Task 7), así que la página sirve el desplegable nuevo.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && npx playwright test tests/browser/pdc-v2-responsable.spec.mjs --reporter=line 2>&1 | tail -25
```

Esperado: `1 passed`. Si falla por el selector del editor de AG Grid, ajustar **el selector del test**, nunca la aserción contra la base — esa es la que prueba el arreglo.

- [ ] **Step 4: Commit del backend**

`git add` con rutas explícitas: otra sesión está commiteando en este mismo worktree y un `git add -A` se llevaría su trabajo sin avisar.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add src/Services/Pdc/PlanFechasService.php src/Controllers/Api/PlanComprasPlanController.php public/index.php tests/test_pdc_v2_plan_fechas.php tests/browser/pdc-v2-responsable.spec.mjs .gitignore && git commit -m "fix(pdc): asignar responsable vuelve a funcionar, contra el esquema nuevo

El endpoint seguía haciendo UPDATE sobre la columna \`responsable\`, que
20260728_pdc_v2_responsable_usuario.sql eliminó al convertir el responsable en un usuario: cada
asignación desde la interfaz moría con «Unknown column 'responsable'».

Ahora recibe un id de usuario y lo valida contra los miembros activos del proyecto — la FK
garantiza que la persona existe, no que trabaje en ESTA obra—, rellena el rastro de quién asignó
y cuándo, y permite dejar el paquete sin responsable. \`plan()\` resuelve el nombre en el servidor
y marca al responsable que ya salió del proyecto en vez de dejar la celda muda, tal como preveía
el comentario de la migración."
```

---

## Verificación final (todos los gates a la vez)

- [ ] **Gates PHP**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -2 && docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php 2>&1 | tail -2 && docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress 2>&1 | tail -3 && docker compose exec -T app php tests/test_pdc_phpstan_nivel6.php 2>&1 | tail -2
```

- [ ] **Gates SPA**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run 2>&1 | tail -6 && npm run build 2>&1 | tail -5
```

- [ ] **DAPORTO intacto** — debe dar exactamente `0	0	0	395`

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -N -e "SELECT (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73), (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73), (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73), (SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id=73);"'
```

- [ ] **Verificación visual** — abrir `/plan-compras#/ensamble/plan` en el navegador integrado y comprobar que la celda «Responsable» abre un desplegable con gente del proyecto.

## Riesgos y puntos abiertos

- **Worktree compartido.** Otra sesión commiteó dos veces (`8a4c542`, `9986abc`) durante la exploración de este plan. Todo `git add` va con rutas explícitas; si al hacer commit aparecen archivos ajenos en `git status`, **no incluirlos**.
- **Selector del editor de AG Grid.** `agSelectCellEditor` renderiza `<select>` nativo en unas versiones y un `.ag-picker-field` propio en otras. El paso 3 de la Task 7 puede necesitar ajuste; el criterio de éxito es la aserción contra la base, no el selector.
- **`npm run sync` apunta al repo equivocado por defecto.** `scripts/sync-to-lps.sh` resuelve `LPS_DIR` como `../lps-aia`, que es el repositorio de otras sesiones. Siempre con `LPS_DIR="/Volumes/Crucial X6/Developer/lps-aia-pdc"` delante (Task 7), y comprobando después que el vecino quedó limpio.
- **Tres commits, no dos.** El grilleo pidió «un commit por repo», pero el bundle regenerado es un artefacto y en este repo viaja en su propio `chore(pdc)` (precedente: `df0c406`). Total: uno en `plan-de-compras`, uno de código y uno de bundle en `lps-aia-pdc`.
- **`test_pdc_phpstan_nivel6.php`** puede exigir que los archivos nuevos estén en `phpstan-pdc.neon`. `PlanComprasPlanController.php` ya está listado; no se añaden archivos nuevos de producción, así que no debería hacer falta tocarlo.
