# Responsable de paquete como usuario del proyecto — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el responsable de cada paquete del plan de compras sea un usuario real del proyecto en vez de un nombre escrito a mano, antes de que B1 (Seguimiento) lo consuma para filtrar «mis paquetes» y notificar.

**Architecture:** `pdc_plan_paquete.responsable` (VARCHAR libre, tabla vacía) se sustituye por `responsable_user_id INT NULL` con FK a `general_usuarios` y dos columnas de auditoría del último cambio. La lista de candidatos sale de `project_members` del proyecto activo. La lectura distingue tres estados —sin asignar, miembro vigente, huérfano— para que sacar a alguien del proyecto no borre nombres de la pantalla en silencio. El SQL del responsable, que hoy vive en el controlador, se mueve al servicio junto al resto.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker), FastRoute, tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1 — no hay PHPUnit); React + TypeScript + Vite + AG Grid Community, Vitest.

## Global Constraints

- **Dos repos.** PHP y migraciones en `/Volumes/Crucial X6/Developer/lps-aia-pdc` (rama `pdc-a4-fechas`, Docker en **8091**). SPA en `/Volumes/Crucial X6/Developer/plan-de-compras` (misma rama).
- **NUNCA** trabajar en `/Volumes/Crucial X6/Developer/lps-aia` — es el working tree principal, de otras sesiones.
- **NUNCA** usar `npm run sync` — apunta a ese worktree ajeno. Copiar el bundle a mano a `lps-aia-pdc/public/pdc-app/assets/`.
- **Invariante que no se puede romper:** `calcular()` conserva el responsable porque su `ON DUPLICATE KEY UPDATE` **no lista** esa columna. Las tres columnas nuevas quedan igualmente fuera de esa lista. Hay un test que lo cubre.
- **Ratchet:** `docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php` debe seguir diciendo `PASS: la brecha (7) está dentro del techo (7)`.
- **Suite PHP:** `docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php` debe terminar en **0 FAIL** (hoy 270 PASS).
- **Tabla vacía:** `pdc_plan_paquete` tiene 0 filas. No hay backfill ni reconciliación de nombres. Si al ejecutar el plan la tabla ya tuviera filas, **parar y avisar** — la premisa cambió.
- Índices de tablas operativas liderados por `project_id` (`docs/global-tables-architecture.md`).
- Migraciones en `lps-aia-pdc/database/migrations/`.
- **PHPStan nivel 6 en el módulo PDC** (commit `d7687fe`, posterior a la redacción de este plan): todo método nuevo va con docblock de tipos completo — `@param list<int>`, `@return array{...}`. El código de este plan ya los lleva; si aun así PHPStan protesta, se anota el tipo, **nunca** se silencia con `mixed` ni se mete en la línea base. Comprobar antes de cada commit en lps-aia-pdc:

  ```bash
  docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --no-progress 2>&1 | tail -5
  ```

  Esperado: `[OK] No errors`.

## File Structure

**lps-aia-pdc**
- Crear `database/migrations/20260728_pdc_v2_responsable_usuario.sql` — DDL: 3 columnas, FK, índice, DROP de la vieja.
- Modificar `src/Services/Pdc/PlanFechasService.php` — el upsert de `calcular()`, el SELECT de `plan()`, y dos métodos nuevos (`responsablesDisponibles()`, `asignarResponsable()`).
- Modificar `src/Controllers/Api/PlanComprasPlanController.php` — `responsable()` cambia de contrato y adelgaza (el SQL se va al servicio); nuevo `responsables()`.
- Modificar `public/index.php` — una ruta nueva.
- Modificar `tests/test_pdc_v2_plan_fechas.php` — adaptar el test del invariante y añadir los casos nuevos.

**plan-de-compras**
- Modificar `src/lib/planFechas.ts` — lógica pura: opciones, conteo de pendientes, etiqueta de la celda.
- Modificar `src/lib/planFechas.test.ts` — Vitest de lo anterior.
- Modificar `src/pages/PlanFechas.tsx` — desplegable, selección múltiple, contador.

---

### Task 1: Migración y el invariante del upsert

**Files:**
- Create: `lps-aia-pdc/database/migrations/20260728_pdc_v2_responsable_usuario.sql`
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php:649-666` (el INSERT de `calcular()`)
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php:880` y `:919` (SELECT y mapeo de `plan()`)
- Test: `lps-aia-pdc/tests/test_pdc_v2_plan_fechas.php:571-600` (reemplaza el bloque «Importante 3» del responsable)

**Interfaces:**
- Consumes: nada (primera tarea).
- Produces: la columna `responsable_user_id INT NULL` y las de auditoría; `plan()` devuelve `responsableUserId: int|null` en cada fila (los otros dos campos llegan en la Task 2).

- [ ] **Step 1: Comprobar que la premisa sigue siendo cierta**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T db mysql -uroot -p"$(grep -m1 '^DB_PASS' .env | cut -d= -f2-)" lastplanneraia_dev \
  -e "SELECT COUNT(*) AS filas, SUM(responsable <> '') AS con_responsable FROM pdc_plan_paquete;"
```

Esperado: `filas` puede ser cualquier número (los tests dejan filas del proyecto sintético 999903), pero **`con_responsable` debe ser 0 o NULL**. Si hay responsables escritos en un `project_id` que no sea 999903, **PARAR** y avisar: la premisa «no hay backfill» ya no se cumple y el plan necesita una tarea de migración de datos.

- [ ] **Step 2: Escribir el test que falla**

En `tests/test_pdc_v2_plan_fechas.php`, sustituir el bloque de las líneas 571-600 (el que escribe `'Juan Pérez'` con un UPDATE directo) por este. Va después de que exista el plan calculado del paquete `$paqEstructura`:

```php
// --- Responsable como usuario del proyecto ---
// Antes era texto libre y el test escribía 'Juan Pérez' con un UPDATE directo. Ahora es un enlace
// a general_usuarios, así que el test necesita un usuario de verdad y un miembro de verdad.
$uid = (int) $db->query('SELECT id FROM general_usuarios ORDER BY id LIMIT 1')->fetchColumn();
$assert($uid > 0, 'Responsable: hay al menos un usuario en general_usuarios para la prueba. Dio ' . $uid);

$db->query('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

$svc->calcular($P, 'test-a4');
$planR = $svc->plan($P);
$porIdR = [];
foreach ($planR as $f) { $porIdR[$f['paqueteId']] = $f; }

$assert(($porIdR[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Responsable: `responsable_user_id` sobrevive a un recálculo (el ON DUPLICATE KEY UPDATE no lo lista). Dio '
    . var_export($porIdR[$paqEstructura]['responsableUserId'] ?? null, true) . ' esperando ' . $uid);
```

- [ ] **Step 3: Ejecutar el test para verlo fallar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^FAIL|Unknown column" | head -5
```

Esperado: FAIL con `Unknown column 'responsable_user_id'` — la columna todavía no existe.

- [ ] **Step 4: Escribir la migración**

Crear `database/migrations/20260728_pdc_v2_responsable_usuario.sql`:

```sql
-- Responsable de paquete: de texto libre a usuario del proyecto (previo a B1 · Seguimiento).
--
-- `responsable` era VARCHAR(100) escrito a mano. B1 va a colgar de este campo el filtro «mis
-- paquetes» y las notificaciones, y una cadena a mano no identifica a nadie: «Juan Pérez»,
-- «juan perez» y «J. Pérez» son tres personas para la base y una sola en la obra.
--
-- Se hace AHORA porque la tabla está vacía: no hay ni un responsable escrito, así que no hay
-- backfill que hacer ni nombres que reconciliar, y la columna vieja se puede quitar sin coste.
-- Con datos reales dentro, ninguna de las dos cosas sería gratis.
--
-- ON DELETE SET NULL implementa la decisión «si a alguien lo sacan, sus paquetes quedan sin
-- responsable» para el caso de borrar la ficha del usuario. Salir de `project_members` NO borra
-- al usuario, así que ese caso no lo cubre la FK: lo resuelve la lectura marcando al responsable
-- como huérfano. Es deliberado — no se borra el dato, se señala.
ALTER TABLE pdc_plan_paquete
  ADD COLUMN responsable_user_id INT NULL DEFAULT NULL AFTER duracion_provisional,
  ADD COLUMN responsable_asignado_por VARCHAR(100) NOT NULL DEFAULT '' AFTER responsable_user_id,
  ADD COLUMN responsable_asignado_at DATETIME NULL DEFAULT NULL AFTER responsable_asignado_por,
  ADD KEY idx_ppp_responsable (project_id, responsable_user_id),
  ADD CONSTRAINT fk_ppp_responsable FOREIGN KEY (responsable_user_id)
      REFERENCES general_usuarios (id) ON DELETE SET NULL,
  DROP COLUMN responsable;
```

- [ ] **Step 5: Aplicar la migración sobre el MySQL real**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T db mysql -uroot -p"$(grep -m1 '^DB_PASS' .env | cut -d= -f2-)" lastplanneraia_dev \
  < database/migrations/20260728_pdc_v2_responsable_usuario.sql && echo "MIGRACION OK"
```

Esperado: `MIGRACION OK`. Comprobar el resultado:

```bash
docker compose exec -T db mysql -uroot -p"$(grep -m1 '^DB_PASS' .env | cut -d= -f2-)" lastplanneraia_dev \
  -e "SHOW CREATE TABLE pdc_plan_paquete\G" | grep -E "responsable|fk_ppp_responsable"
```

Esperado: las tres columnas nuevas y la FK; **ninguna línea con `responsable` a secas**.

- [ ] **Step 6: Quitar `responsable` del INSERT de `calcular()`**

En `src/Services/Pdc/PlanFechasService.php`, el INSERT de las líneas 649-666. Quitar `responsable` de la lista de columnas y su `?`, y sustituir el comentario largo por este:

```php
                $this->db->query(
                    'INSERT INTO pdc_plan_paquete
                        (project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales,
                         duracion_ref, duracion_provisional, calculado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), fecha_ancla = VALUES(fecha_ancla),
                        fecha_arranque = VALUES(fecha_arranque), dias_totales = VALUES(dias_totales),
                        duracion_ref = VALUES(duracion_ref), duracion_provisional = VALUES(duracion_provisional),
                        calculado_por = VALUES(calculado_por), updated_at = NOW()',
                    [
                        // Las tres columnas del responsable (responsable_user_id, _asignado_por,
                        // _asignado_at) NO aparecen aquí ni en el ON DUPLICATE KEY UPDATE: lo que no
                        // se lista, MySQL lo conserva. Por eso recalcular el plan no borra a quién se
                        // le asignó cada paquete, y por eso B1 podrá añadir sus columnas sin volver a
                        // tocar este INSERT. No añadirlas sin querer perder esa garantía.
                        $projectId, $paqueteId, $a['uniqueId'], $a['fechaAncla'], $arranque, $total,
                        $paq['duracion_ref'], $provisional ? 1 : 0, $usuario,
                    ],
                );
```

- [ ] **Step 7: Que `plan()` lea la columna nueva**

En el SELECT de `plan()` (línea ~880), cambiar `pp.responsable` por `pp.responsable_user_id`. En el mapeo (línea ~919), cambiar la entrada `'responsable'` por:

```php
                'responsableUserId' => $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'],
```

- [ ] **Step 8: Ejecutar el test para verlo pasar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: termina en `=== OK ===`, **0 FAIL**.

- [ ] **Step 9: Comprobar que el ratchet no se movió**

```bash
docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php 2>&1 | tail -2
```

Esperado: `PASS: la brecha (7) está dentro del techo (7).`

- [ ] **Step 10: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add database/migrations/20260728_pdc_v2_responsable_usuario.sql src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): el responsable de paquete pasa a ser un usuario, no una cadena"
```

---

### Task 2: Lectura con tres estados

**Files:**
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php` (SELECT y mapeo de `plan()`)
- Test: `lps-aia-pdc/tests/test_pdc_v2_plan_fechas.php` (bloque nuevo tras el de la Task 1)

**Interfaces:**
- Consumes: `responsableUserId` de la Task 1.
- Produces: cada fila de `plan()` gana `responsableNombre: string` (vacío si no hay) y `responsableHuerfano: bool`. La Task 5 (SPA) consume los tres campos.

- [ ] **Step 1: Escribir el test que falla**

Añadir tras el bloque de la Task 1:

```php
// Tres estados de lectura. El tercero (huérfano) es el que impide que sacar a alguien del
// proyecto haga desaparecer su nombre de la pantalla sin decir nada.
$filaR = $porIdR[$paqEstructura];
$assert(($filaR['responsableNombre'] ?? '') !== '',
    'Responsable: un responsable vigente trae su nombre resuelto. Dio ' . var_export($filaR['responsableNombre'] ?? null, true));
$assert(($filaR['responsableHuerfano'] ?? null) === false,
    'Responsable: un miembro vigente NO es huérfano.');

// Sacarlo del proyecto (sin borrar su ficha) debe marcarlo huérfano, no borrarlo.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $uid]);
$planH = $svc->plan($P);
$porIdH = [];
foreach ($planH as $f) { $porIdH[$f['paqueteId']] = $f; }
$assert(($porIdH[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Responsable huérfano: el enlace NO se borra al salir del proyecto.');
$assert(($porIdH[$paqEstructura]['responsableNombre'] ?? '') !== '',
    'Responsable huérfano: el nombre se sigue viendo.');
$assert(($porIdH[$paqEstructura]['responsableHuerfano'] ?? null) === true,
    'Responsable huérfano: queda marcado como tal.');

// Restaurar la membresía para no dejar el estado sucio a los bloques siguientes.
$db->query('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $uid, 'U']);

// Sin asignar es un estado válido, no un error.
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = NULL WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura]);
$planN = $svc->plan($P);
$porIdN = [];
foreach ($planN as $f) { $porIdN[$f['paqueteId']] = $f; }
$assert(($porIdN[$paqEstructura]['responsableUserId'] ?? 'x') === null,
    'Responsable: «sin asignar» es NULL, no cadena vacía.');
$assert(($porIdN[$paqEstructura]['responsableHuerfano'] ?? null) === false,
    'Responsable: sin asignar NO es huérfano (no hay nadie a quien marcar).');
```

- [ ] **Step 2: Ejecutar el test para verlo fallar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep "^FAIL" | head -3
```

Esperado: FAIL en «un responsable vigente trae su nombre resuelto» — el campo todavía no existe.

- [ ] **Step 3: Implementar los tres estados en `plan()`**

En el SELECT de `plan()`, añadir el LEFT JOIN al usuario y a la membresía. El JOIN a `general_usuarios` es LEFT porque el responsable puede ser NULL; el de `project_members` es LEFT porque su ausencia es justo lo que define al huérfano:

```php
            "SELECT pp.paquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque, pp.dias_totales,
                    pp.duracion_provisional, pp.responsable_user_id, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre,
                    u.nombre AS responsable_nombre, u.activo AS responsable_activo,
                    pm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_user_id
```

(El resto del SELECT —`WHERE`, `ORDER BY`— se deja tal cual.)

En el mapeo, sustituir la entrada `'responsableUserId'` de la Task 1 por estas tres:

```php
                'responsableUserId' => $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'],
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                // Huérfano = tiene responsable, pero ya no es miembro del proyecto o está inactivo.
                // Sin responsable no hay nadie a quien marcar, así que es false, no true.
                'responsableHuerfano' => $r['responsable_user_id'] !== null
                    && ($r['responsable_miembro'] === null || (int) $r['responsable_activo'] !== 1),
```

- [ ] **Step 4: Ejecutar el test para verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===`, 0 FAIL.

- [ ] **Step 5: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): la lectura del plan distingue responsable vigente, huérfano y sin asignar"
```

---

### Task 3: Endpoint de responsables disponibles

**Files:**
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php` (método nuevo)
- Modify: `lps-aia-pdc/src/Controllers/Api/PlanComprasPlanController.php` (método nuevo)
- Modify: `lps-aia-pdc/public/index.php:221` (zona de rutas del plan)
- Test: `lps-aia-pdc/tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: nada de tareas previas.
- Produces: `PlanFechasService::responsablesDisponibles(int $projectId): array` → lista de `['id' => int, 'nombre' => string, 'cargo' => string]` ordenada por nombre. Ruta `GET /plan-compras/api/plan/responsables`. La Task 6 (SPA) la consume.

- [ ] **Step 1: Escribir el test que falla**

```php
// --- Responsables disponibles: los miembros ACTIVOS del proyecto ---
$disp = $svc->responsablesDisponibles($P);
$assert(is_array($disp), 'Disponibles: devuelve una lista.');
$ids = array_column($disp, 'id');
$assert(in_array($uid, $ids, true),
    'Disponibles: un miembro activo del proyecto aparece en la lista.');
$assert(isset($disp[0]['nombre'], $disp[0]['cargo']),
    'Disponibles: cada entrada trae nombre y cargo.');

// Un usuario de OTRO proyecto no puede aparecer: es lo que impide asignarle trabajo a alguien
// que no está en la obra.
$otro = (int) $db->query('SELECT id FROM general_usuarios WHERE id <> ? ORDER BY id DESC LIMIT 1', [$uid])->fetchColumn();
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $otro]);
$dispSinOtro = array_column($svc->responsablesDisponibles($P), 'id');
$assert(!in_array($otro, $dispSinOtro, true),
    'Disponibles: quien no es miembro del proyecto NO aparece. id=' . $otro);

// Un miembro inactivo no se ofrece para elegir (pero su nombre sí se resuelve al leer el plan,
// que es lo que cubre la Task 2).
$db->query('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $otro, 'U']);
$db->query('UPDATE general_usuarios SET activo = 0 WHERE id = ?', [$otro]);
$dispInactivo = array_column($svc->responsablesDisponibles($P), 'id');
$assert(!in_array($otro, $dispInactivo, true),
    'Disponibles: un miembro inactivo NO se ofrece para elegir.');
$db->query('UPDATE general_usuarios SET activo = 1 WHERE id = ?', [$otro]);
```

- [ ] **Step 2: Ejecutar el test para verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^FAIL|Fatal error" | head -3
```

Esperado: `Fatal error: Call to undefined method ...::responsablesDisponibles()`.

- [ ] **Step 3: Implementar el método en el servicio**

Añadir en `PlanFechasService.php`, junto a los demás métodos públicos:

```php
    /**
     * Miembros activos del proyecto, que son los únicos a quienes se puede asignar un paquete.
     *
     * Sin filtro por cargo a propósito: el campo `cargo` de general_usuarios está sucio (hay
     * usuarios con el correo metido ahí), así que filtrar por él escondería gente por un error de
     * datos. Quién es responsable lo decide quien planea, no el sistema.
     *
     * @return list<array{id: int, nombre: string, cargo: string}>
     */
    public function responsablesDisponibles(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT u.id, u.nombre, u.cargo
               FROM project_members pm
               JOIN general_usuarios u ON u.id = pm.user_id
              WHERE pm.project_id = ? AND u.activo = 1
              ORDER BY u.nombre ASC',
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

- [ ] **Step 4: Ejecutar el test para verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===`, 0 FAIL.

- [ ] **Step 5: Exponer el endpoint**

En `src/Controllers/Api/PlanComprasPlanController.php`, añadir junto a los demás GET:

```php
    /** GET /plan-compras/api/plan/responsables */
    public function responsables(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['responsables' => $this->service->responsablesDisponibles($projectId)]);
    }
```

En `public/index.php`, junto a la ruta de la línea 221:

```php
$router->get('/plan-compras/api/plan/responsables', [\App\Controllers\Api\PlanComprasPlanController::class, 'responsables']);
```

- [ ] **Step 6: Comprobar que la ruta responde**

```bash
curl -s -o /dev/null -w "responsables -> HTTP %{http_code}\n" http://localhost:8091/plan-compras/api/plan/responsables
```

Esperado: **HTTP 403 o 409** (sin sesión no hay permiso ni proyecto). Lo que NO debe salir es **404** — eso significaría que la ruta no quedó registrada.

- [ ] **Step 7: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add src/Services/Pdc/PlanFechasService.php src/Controllers/Api/PlanComprasPlanController.php public/index.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): endpoint con los miembros del proyecto que pueden ser responsables"
```

---

### Task 4: Asignar responsable, en masa y validado

**Files:**
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php` (método nuevo)
- Modify: `lps-aia-pdc/src/Controllers/Api/PlanComprasPlanController.php:107-145` (reescribe `responsable()`)
- Test: `lps-aia-pdc/tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `responsablesDisponibles()` (Task 3) para validar la membresía.
- Produces: `PlanFechasService::asignarResponsable(int $projectId, array $paqueteIds, ?int $userId, string $usuario): array` → `['asignados' => int]`, o `['error' => 'NO_MIEMBRO'|'SIN_PLAN']`. El endpoint `POST /plan-compras/api/plan/responsable` pasa a recibir `{paqueteIds: [], responsableUserId: int|null}`. La Task 6 lo consume.

- [ ] **Step 1: Escribir el test que falla**

```php
// --- Asignar responsable ---
$r1 = $svc->asignarResponsable($P, [$paqEstructura], $uid, 'test-a4');
$assert(($r1['asignados'] ?? 0) === 1, 'Asignar: un paquete. Dio ' . var_export($r1, true));

$fila = $db->query('SELECT responsable_user_id, responsable_asignado_por, responsable_asignado_at
                      FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                   [$P, $paqEstructura])->fetch(\PDO::FETCH_ASSOC);
$assert((int) $fila['responsable_user_id'] === $uid, 'Asignar: guarda el enlace al usuario.');
$assert($fila['responsable_asignado_por'] === 'test-a4', 'Asignar: audita QUIÉN asignó.');
$assert(!empty($fila['responsable_asignado_at']), 'Asignar: audita CUÁNDO se asignó.');

// Desasignar con null es un estado válido, no un error.
$r2 = $svc->asignarResponsable($P, [$paqEstructura], null, 'test-a4');
$assert(($r2['asignados'] ?? 0) === 1, 'Asignar: desasignar con null cuenta como asignación.');
$vacio = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                    [$P, $paqEstructura])->fetchColumn();
$assert($vacio === null, 'Asignar: null deja la fila sin responsable.');

// En masa: varios paquetes de una vez.
$idsPlan = array_map(static fn (array $f): int => $f['paqueteId'], $svc->plan($P));
$dos = array_slice($idsPlan, 0, 2);
$assert(count($dos) === 2, 'Asignar en masa: hay al menos 2 paquetes con plan para la prueba.');
$r3 = $svc->asignarResponsable($P, $dos, $uid, 'test-a4');
$assert(($r3['asignados'] ?? 0) === 2, 'Asignar en masa: los 2 de golpe. Dio ' . var_export($r3, true));

// Un usuario que no es miembro del proyecto se rechaza: no se guarda nada.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $otro]);
$r4 = $svc->asignarResponsable($P, [$paqEstructura], $otro, 'test-a4');
$assert(($r4['error'] ?? '') === 'NO_MIEMBRO',
    'Asignar: se rechaza a quien no es miembro del proyecto. Dio ' . var_export($r4, true));
$sigue = (int) $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                          [$P, $paqEstructura])->fetchColumn();
$assert($sigue === $uid, 'Asignar: un rechazo NO deja el responsable a medias.');
```

- [ ] **Step 2: Ejecutar el test para verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^FAIL|Fatal error" | head -3
```

Esperado: `Fatal error: Call to undefined method ...::asignarResponsable()`.

- [ ] **Step 3: Implementar el método en el servicio**

```php
    /**
     * Asigna (o quita, con $userId = null) el responsable de uno o varios paquetes.
     *
     * Valida la membresía ANTES de escribir nada: asignar a alguien que no está en la obra es un
     * error del que llama, y dejarlo a medias sería peor que rechazarlo entero. Por eso la
     * comprobación va fuera del bucle y devuelve sin tocar la base.
     *
     * @param list<int> $paqueteIds
     * @return array{asignados: int}|array{error: string}
     */
    public function asignarResponsable(int $projectId, array $paqueteIds, ?int $userId, string $usuario): array
    {
        $ids = [];
        foreach ($paqueteIds as $id) {
            $n = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($n !== false) {
                $ids[] = (int) $n;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return ['asignados' => 0];
        }

        if ($userId !== null) {
            $esMiembro = $this->db->query(
                'SELECT 1 FROM project_members pm JOIN general_usuarios u ON u.id = pm.user_id
                  WHERE pm.project_id = ? AND pm.user_id = ? AND u.activo = 1',
                [$projectId, $userId],
            )->fetchColumn();
            if ($esMiembro === false) {
                return ['error' => 'NO_MIEMBRO'];
            }
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query(
            "UPDATE pdc_plan_paquete
                SET responsable_user_id = ?, responsable_asignado_por = ?, responsable_asignado_at = NOW()
              WHERE project_id = ? AND paquete_id IN ($marcas)",
            array_merge([$userId, $usuario, $projectId], $ids),
        );

        // No se usa rowCount(): este repo no activa PDO::MYSQL_ATTR_FOUND_ROWS, así que MySQL
        // reporta filas MODIFICADAS, no coincidentes — reasignar el mismo responsable daría 0 y
        // parecería que no existe la fila. Se cuenta con un SELECT explícito.
        $existen = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id IN ($marcas)",
            array_merge([$projectId], $ids),
        )->fetchColumn();

        return ['asignados' => $existen];
    }
```

- [ ] **Step 4: Ejecutar el test para verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
```

Esperado: `=== OK ===`, 0 FAIL.

- [ ] **Step 5: Reescribir el endpoint**

Sustituir el método `responsable()` completo (líneas 107-145) por este. El SQL se va al servicio, donde vive el resto:

```php
    /** POST /plan-compras/api/plan/responsable  {paqueteIds:[...], responsableUserId:int|null} */
    public function responsable(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteIds = is_array($body['paqueteIds'] ?? null) ? $body['paqueteIds'] : [];
        if ($paqueteIds === []) {
            $this->fail('PAQUETE_INVALIDO', 'No se recibió ningún paquete.', 422);
            return;
        }
        // null explícito = desasignar. Es un estado válido, no un error.
        $userId = null;
        if (($body['responsableUserId'] ?? null) !== null) {
            $n = filter_var($body['responsableUserId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($n === false) {
                $this->fail('RESPONSABLE_INVALIDO', 'responsableUserId inválido.', 422);
                return;
            }
            $userId = (int) $n;
        }

        $r = $this->service->asignarResponsable($projectId, $paqueteIds, $userId, $this->usuario());
        if (isset($r['error']) && $r['error'] === 'NO_MIEMBRO') {
            $this->fail('NO_MIEMBRO', 'Esa persona no es miembro activo de este proyecto.', 422);
            return;
        }
        if (($r['asignados'] ?? 0) === 0) {
            $this->fail(
                'PAQUETE_SIN_PLAN',
                'Estos paquetes todavía no tienen plan de compras calculado. Calcula el plan antes de asignar responsable.',
                422
            );
            return;
        }
        $this->ok(['asignados' => $r['asignados']]);
    }
```

- [ ] **Step 6: Comprobar la suite completa y el ratchet**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -2
docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php 2>&1 | tail -2
```

Esperado: `=== OK ===` y `PASS: la brecha (7) está dentro del techo (7).`

- [ ] **Step 7: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add src/Services/Pdc/PlanFechasService.php src/Controllers/Api/PlanComprasPlanController.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): asignar responsable a varios paquetes, validando que sea del proyecto"
```

---

### Task 5: Lógica pura de la SPA

**Files:**
- Modify: `plan-de-compras/src/lib/planFechas.ts:177` (sustituye `valorResponsableMostrado`)
- Test: `plan-de-compras/src/lib/planFechas.test.ts`

**Interfaces:**
- Consumes: la forma de fila de la Task 2 (`responsableUserId`, `responsableNombre`, `responsableHuerfano`).
- Produces:
  - `opcionesResponsable(miembros: Miembro[]): OpcionResponsable[]` — antepone `{id: null, etiqueta: 'Sin asignar'}`.
  - `etiquetaResponsable(fila, overrides): string` — reemplaza a `valorResponsableMostrado`, con el mismo papel de overlay.
  - `contarSinResponsable(filas): number`.
  - Tipos `Miembro = {id: number, nombre: string, cargo: string}` y `OpcionResponsable = {id: number | null, etiqueta: string}`.

- [ ] **Step 1: Escribir los tests que fallan**

En `src/lib/planFechas.test.ts`:

```ts
import {
  opcionesResponsable, etiquetaResponsable, contarSinResponsable,
} from './planFechas'

const miembros = [
  { id: 7, nombre: 'Ana Ruiz', cargo: 'Compras' },
  { id: 9, nombre: 'Beto Sol', cargo: 'Obra' },
]

describe('opcionesResponsable', () => {
  it('antepone «Sin asignar» para que desasignar sea una opción visible', () => {
    const o = opcionesResponsable(miembros)
    expect(o[0]).toEqual({ id: null, etiqueta: 'Sin asignar' })
    expect(o).toHaveLength(3)
  })

  it('muestra el cargo junto al nombre para distinguir homónimos', () => {
    expect(opcionesResponsable(miembros)[1]).toEqual({ id: 7, etiqueta: 'Ana Ruiz — Compras' })
  })

  it('no inventa un guion cuando el cargo viene vacío', () => {
    expect(opcionesResponsable([{ id: 1, nombre: 'Solo', cargo: '' }])[1].etiqueta).toBe('Solo')
  })
})

describe('etiquetaResponsable', () => {
  const base = { paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Ruiz', responsableHuerfano: false }

  it('muestra el nombre de un responsable vigente', () => {
    expect(etiquetaResponsable(base, {})).toBe('Ana Ruiz')
  })

  it('marca al huérfano para que se vea que hay que reasignarlo', () => {
    expect(etiquetaResponsable({ ...base, responsableHuerfano: true }, {}))
      .toBe('Ana Ruiz (ya no está en el proyecto)')
  })

  it('deja vacío lo que no tiene responsable', () => {
    expect(etiquetaResponsable({ ...base, responsableUserId: null, responsableNombre: '' }, {})).toBe('')
  })

  it('el overlay gana mientras el guardado está en vuelo', () => {
    expect(etiquetaResponsable(base, { 1: 'Beto Sol' })).toBe('Beto Sol')
  })
})

describe('contarSinResponsable', () => {
  it('cuenta los que no tienen a nadie', () => {
    expect(contarSinResponsable([
      { responsableUserId: null }, { responsableUserId: 7 }, { responsableUserId: null },
    ])).toBe(2)
  })

  it('un huérfano SÍ cuenta como pendiente de reasignar', () => {
    expect(contarSinResponsable([{ responsableUserId: 7, responsableHuerfano: true }])).toBe(1)
  })
})
```

- [ ] **Step 2: Ejecutar los tests para verlos fallar**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test 2>&1 | tail -12
```

Esperado: fallan por import inexistente (`opcionesResponsable is not a function` o error de TypeScript).

- [ ] **Step 3: Implementar en `src/lib/planFechas.ts`**

Sustituir `valorResponsableMostrado` (línea 177) por:

```ts
export type Miembro = { id: number; nombre: string; cargo: string }
export type OpcionResponsable = { id: number | null; etiqueta: string }

/** Opciones del desplegable. «Sin asignar» va primero: dejar un paquete sin dueño es válido. */
export function opcionesResponsable(miembros: Miembro[]): OpcionResponsable[] {
  return [
    { id: null, etiqueta: 'Sin asignar' },
    ...miembros.map((m) => ({
      id: m.id,
      // El cargo desempata homónimos, pero viene sucio en algunas fichas: si está vacío no se
      // añade el guion, que si no quedaría un «Fulano — » colgando.
      etiqueta: m.cargo ? `${m.nombre} — ${m.cargo}` : m.nombre,
    })),
  ]
}

type FilaResponsable = {
  paqueteId: number
  responsableUserId: number | null
  responsableNombre: string
  responsableHuerfano: boolean
}

/**
 * Texto de la celda «Responsable». El overlay sigue mandando mientras un guardado está en vuelo,
 * igual que antes: es lo que evita que la celda parpadee al valor viejo entre el POST y la recarga.
 */
export function etiquetaResponsable(fila: FilaResponsable, overrides: Record<number, string>): string {
  const overlay = overrides[fila.paqueteId]
  if (overlay !== undefined) return overlay
  if (fila.responsableUserId === null) return ''
  return fila.responsableHuerfano ? `${fila.responsableNombre} (ya no está en el proyecto)` : fila.responsableNombre
}

/**
 * Cuántos paquetes están pendientes de dueño. Un huérfano cuenta: tiene un nombre escrito, pero
 * esa persona ya no está en la obra, así que sigue habiendo trabajo que repartir.
 */
export function contarSinResponsable(
  filas: Array<{ responsableUserId: number | null; responsableHuerfano?: boolean }>,
): number {
  return filas.filter((f) => f.responsableUserId === null || f.responsableHuerfano === true).length
}
```

- [ ] **Step 4: Ejecutar los tests para verlos pasar**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test 2>&1 | tail -8
```

Esperado: todos los ficheros en verde (hoy son 115 tests; deben quedar 115 + los 9 nuevos = 124).

- [ ] **Step 5: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add src/lib/planFechas.ts src/lib/planFechas.test.ts
git commit -m "feat(pdc): lógica de responsable como usuario en la SPA"
```

---

### Task 6: La pestaña Plan, con desplegable y asignación en masa

**Files:**
- Modify: `plan-de-compras/src/pages/PlanFechas.tsx` (columna, carga de miembros, selección múltiple, contador)
- Modify: `lps-aia-pdc/public/pdc-app/assets/pdc.js` y `pdc.css` (bundle republicado)

**Interfaces:**
- Consumes: `GET /plan-compras/api/plan/responsables` (Task 3), `POST /plan-compras/api/plan/responsable` con `{paqueteIds, responsableUserId}` (Task 4), y los helpers de la Task 5.
- Produces: nada que consuman otras tareas.

- [ ] **Step 1: Cargar los miembros al montar**

En `PlanFechas.tsx`, junto a las demás cargas de datos:

```tsx
const [miembros, setMiembros] = useState<Miembro[]>([])

useEffect(() => {
  void (async () => {
    try {
      const r = await apiGet('/plan-compras/api/plan/responsables')
      setMiembros(r.responsables ?? [])
    } catch {
      // Sin la lista, el desplegable queda solo con «Sin asignar»: se puede leer el plan y
      // desasignar, que es mejor que romper la pantalla entera por un fallo de red.
      setMiembros([])
    }
  })()
}, [])
```

- [ ] **Step 2: Cambiar la columna de texto libre a desplegable**

Sustituir la definición de la columna «Responsable» (línea ~221) por:

```tsx
{
  headerName: 'Responsable', field: 'responsableUserId', flex: 1, minWidth: 200, editable: true,
  cellEditor: 'agSelectCellEditor',
  cellEditorParams: { values: opcionesResponsable(miembros).map((o) => o.id) },
  valueFormatter: (p) => {
    const o = opcionesResponsable(miembros).find((x) => x.id === p.value)
    return o ? o.etiqueta : ''
  },
  valueGetter: (p) => (p.data ? etiquetaResponsable(p.data, responsableOverride) : ''),
  onCellValueChanged: (p) => {
    if (!p.data) return
    void onResponsable([p.data.paqueteId], p.newValue ?? null, p.oldValue ?? null)
  },
},
```

- [ ] **Step 3: Adaptar `onResponsable` al contrato nuevo**

Sustituir la función `onResponsable` (línea ~113):

```tsx
const onResponsable = async (paqueteIds: number[], userId: number | null, anterior: string | null) => {
  const etiqueta = opcionesResponsable(miembros).find((o) => o.id === userId)?.etiqueta ?? ''
  paqueteIds.forEach((id) => {
    setResponsableOverride((prev) => trasGuardarEdicion(prev, id, { ok: true, valor: etiqueta }))
  })
  try {
    await apiPost('/plan-compras/api/plan/responsable', { paqueteIds, responsableUserId: userId })
    await recargar()
  } catch (e: any) {
    const msg = e?.code === 'PAQUETE_SIN_PLAN'
      ? 'Estos paquetes todavía no tienen plan calculado; usa «Recalcular» antes de asignar responsable.'
      : e?.code === 'NO_MIEMBRO'
        ? 'Esa persona ya no es miembro activo de este proyecto.'
        : 'No se pudo guardar el responsable.'
    setError(msg)
    paqueteIds.forEach((id) => {
      setResponsableOverride((prev) => trasGuardarEdicion(prev, id, { ok: false, anterior: anterior ?? '' }))
    })
  }
}
```

- [ ] **Step 4: Añadir la asignación en masa y el contador**

Sobre la grilla, junto a los demás controles:

```tsx
<div className="pdc-plan-masa">
  <span data-testid="pdc-plan-sin-responsable">
    {contarSinResponsable(filas)} sin responsable
  </span>
  <select
    data-testid="pdc-plan-masa-responsable"
    value={masaUserId ?? ''}
    onChange={(e) => setMasaUserId(e.target.value === '' ? null : Number(e.target.value))}
  >
    {opcionesResponsable(miembros).map((o) => (
      <option key={o.id ?? 'null'} value={o.id ?? ''}>{o.etiqueta}</option>
    ))}
  </select>
  <button
    data-testid="pdc-plan-masa-asignar"
    disabled={seleccionados.length === 0}
    onClick={() => void onResponsable(seleccionados, masaUserId, null)}
  >
    Asignar a {seleccionados.length} seleccionados
  </button>
</div>
```

Con el estado que necesita, junto a los demás `useState`:

```tsx
const [masaUserId, setMasaUserId] = useState<number | null>(null)
const [seleccionados, setSeleccionados] = useState<number[]>([])
```

Y habilitar la selección en la grilla:

```tsx
rowSelection="multiple"
onSelectionChanged={(e) => setSeleccionados(e.api.getSelectedRows().map((r: any) => r.paqueteId))}
```

- [ ] **Step 5: Tests y build**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
npm run test 2>&1 | tail -6
npm run build 2>&1 | tail -4
```

Esperado: tests en verde y `✓ built`. Si TypeScript se queja de que `trasGuardarEdicion` no acepta `valor`, **ampliar su firma** en `planFechas.ts` para admitir `{ ok: true; valor: string }` y cubrirlo con un test — no silenciar con `any`.

- [ ] **Step 6: Republicar el bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
cp dist/assets/pdc.js dist/assets/pdc.css "/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/assets/"
curl -s http://localhost:8091/pdc-app/assets/pdc.js | grep -c "Sin asignar"
```

Esperado: `1` o más — el bundle servido ya trae el desplegable.

- [ ] **Step 7: Commit en los dos repos**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add src/pages/PlanFechas.tsx src/lib/planFechas.ts src/lib/planFechas.test.ts
git commit -m "feat(pdc): el responsable se elige de una lista y se puede asignar en masa"

cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add public/pdc-app/assets/pdc.js public/pdc-app/assets/pdc.css
git commit -m "chore(pdc): republica el bundle con el responsable como usuario"
```

- [ ] **Step 8: Verificación visual**

Abrir `http://localhost:8091/plan-compras#/ensamble/plan` en el navegador integrado. **El login lo hace la persona usuaria, nunca el agente.** Comprobar: la columna Responsable es un desplegable con los miembros del proyecto, el contador de «sin responsable» cuadra con lo que se ve, y asignar en masa a dos filas seleccionadas las cambia a la vez.

---

## Self-Review

**Cobertura del spec:** las 12 decisiones del grilleo tienen tarea. Candidatos (T3), sale del proyecto (T2, estado huérfano + FK en T1), uno solo (columna escalar, T1), solo el enlace (T1), sin filtro por cargo (T3), inactivos (T3 para elegir, T2 para mostrar), sin responsable permitido (T2, T4), masa (T4, T6), auditoría (T1 columnas, T4 escritura), usuarios de prueba (fuera de alcance, sin tarea — correcto), columna vieja borrada (T1), todos los proyectos (sin ramas por obra en ninguna tarea).

**Placeholders:** ninguno. Todos los pasos con código llevan el código.

**Consistencia de tipos:** `responsableUserId: int|null`, `responsableNombre: string`, `responsableHuerfano: bool` se definen en T1/T2 y se consumen con esos mismos nombres en T5 y T6. `responsablesDisponibles()` produce `{id, nombre, cargo}` en T3 y `Miembro` en T5 lo refleja. `asignarResponsable()` devuelve `{asignados}` o `{error}` en T4 y el controlador trata ambos.

**Riesgo asumido y señalado:** el paso 5 de la T6 avisa de que `trasGuardarEdicion` puede necesitar una firma más amplia; se resuelve con test, no con `any`.
