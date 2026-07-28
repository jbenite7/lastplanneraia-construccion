# Plan 2 — Lo que falta poder hacer — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deshacer un amarre y cambiar de frente sin perder al responsable, elegir a mano cuál versión del presupuesto es la oficial, ver el presupuesto y el comparador desplegados y por nivel, y saltar del historial al visor y al comparador. Cumple **f10–f25**.

**Architecture:** Toca los dos repos. En el servidor: un `desamarrar()` nuevo, exponer el cambio de frente que ya existe, un `activar()` de versión que respete la columna generada `activa_unica`, y el nivel que **ya está persistido** en `pdc_presupuesto_items.nivel` desde el import. En la SPA: selector de nivel, expansión inicial, y los puentes desde el historial.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker en **8091**), FastRoute, tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1 — no hay PHPUnit), **PHPStan nivel 6** en el módulo PDC; React + TypeScript + AG Grid Community 36.0.2, Vitest.

## Global Constraints

- **Dos repos.** PHP y migraciones en `/Volumes/Crucial X6/Developer/lps-aia-pdc`; SPA en `/Volumes/Crucial X6/Developer/plan-de-compras`.
- **NUNCA** trabajar en `/Volumes/Crucial X6/Developer/lps-aia`. **NUNCA** `npm run sync`.
- **Suites que no pueden romperse:** `docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php` en 0 FAIL (base: 303 PASS) y `npm run test` en verde (base: 128).
- **Ratchet:** `docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php` → `PASS: la brecha (7) está dentro del techo (7)`.
- **PHPStan nivel 6:** `docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --no-progress` → `[OK] No errors`. Docblocks de tipos completos; nunca `mixed`, nunca tocar la línea base.
- Migraciones en `lps-aia-pdc/database/migrations/` (DDL `.sql`; backfills `.php` con dry-run → `--apply`).

## Hechos de partida verificados en el código (no volver a suponerlos)

- `pdc_paquete_frente` tiene `UNIQUE (project_id, paquete_id)`: **un paquete, un amarre**. FK solo hacia `general_paquetes_contratacion`, ninguna hacia `pdc_plan_paquete`/`pdc_plan_paso`.
- **`amarrar()` ya borra el plan calculado —y con él el responsable— cuando el reamarre invalida** (`PlanFechasService.php:501-502`). Ese borrado silencioso es lo que f11 corrige.
- `plan()` hace **`JOIN`** con `pdc_paquete_frente`, no `LEFT JOIN`: un paquete sin amarre desaparece de la grilla, pero sus filas calculadas siguen vivas e invisibles si nadie las limpia.
- La lista «Sin frente» **se calcula en el cliente** (`planFechas.ts:71-78`), no en el servidor.
- `pdc_presupuesto_versiones` tiene una columna generada `activa_unica` con índice único: **la base ya garantiza una sola versión activa por proyecto**. Cualquier `activar()` debe apagar la anterior en la misma transacción o el índice lo rechaza.
- **`pdc_insumo_paquete` y las tres tablas del plan NO tienen `version_id`**: viven a nivel de proyecto y sobreviven a un cambio de versión. Lo único versionado de verdad es `pdc_insumo_vinculos`.
- `pdc_presupuesto_items.nivel` **ya existe** (TINYINT, calculado en el import contando segmentos del código). El selector de nivel no necesita migración.
- Permisos: `lps.pdc.importar` → solo Administrador y Director. `lps.paquetes_contratacion.editar` → Administrador, Director y Planeación.

---

### Task 1: Desamarrar (f10, f11, f12, f13)

**Files:**
- Modify: `lps-aia-pdc/src/Services/Pdc/PlanFechasService.php`
- Modify: `lps-aia-pdc/src/Controllers/Api/PlanComprasPlanController.php`
- Modify: `lps-aia-pdc/public/index.php`
- Test: `lps-aia-pdc/tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Produces: `desamarrar(int $projectId, int $paqueteId, string $usuario): array{ok: bool, code?: string}` y `POST /plan-compras/api/plan/desamarrar`.
- Guard: `lps.paquetes_contratacion.editar` — el mismo que amarrar, sin permiso nuevo.

- [ ] **Step 1: Escribir los tests que fallan**

```php
// --- Desamarrar ---
// Amarrar era una decisión sin retorno: no había forma de corregir un frente mal elegido.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

$r = $svc->desamarrar($P, $paqEstructura, 'test-a4');
$assert(($r['ok'] ?? false) === true, 'Desamarrar: responde ok. Dio ' . var_export($r, true));

$quedaAmarre = (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert($quedaAmarre === 0, 'Desamarrar: el amarre desaparece, el paquete vuelve a «sin frente». Dio ' . $quedaAmarre);

// f12: sin frente no hay fecha que pueda leerse como vigente.
$quedanPasos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert($quedanPasos === 0, 'Desamarrar: las fechas calculadas se borran. Quedaron ' . $quedanPasos . ' pasos.');

// f11: pero el responsable NO se pierde. Es el corazón de esta tarea.
$respTrasDesamarrar = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert((int) $respTrasDesamarrar === $uid,
    'Desamarrar: el responsable sobrevive. Dio ' . var_export($respTrasDesamarrar, true) . ' esperando ' . $uid);

// Reamarrar y volver a calcular deja el paquete como estaba, con su dueño.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$planTras = [];
foreach ($svc->plan($P) as $f) { $planTras[$f['paqueteId']] = $f; }
$assert(($planTras[$paqEstructura]['responsableUserId'] ?? null) === $uid,
    'Desamarrar y reamarrar: el paquete vuelve al plan con su responsable intacto.');

// Desamarrar algo que no está amarrado no es un error: es un no-op.
$rNoop = $svc->desamarrar($P, 999999, 'test-a4');
$assert(isset($rNoop['ok']), 'Desamarrar algo sin amarre responde sin reventar. Dio ' . var_export($rNoop, true));
```

- [ ] **Step 2: Ejecutar los tests para verlos fallar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -E "^FAIL|Fatal|Call to undefined" | head -3
```

Esperado: `Call to undefined method ...::desamarrar()`.

- [ ] **Step 3: Implementar `desamarrar()`**

Todo en una transacción. El orden importa: **conservar el responsable exige leerlo antes de borrar y devolverlo después**, porque la fila de `pdc_plan_paquete` es la que lo guarda.

```php
    /**
     * Deshace el amarre de un paquete: vuelve a «sin frente» y pierde sus fechas, pero NO su
     * responsable.
     *
     * Las fechas se borran a propósito. Se calculan hacia atrás desde la fecha de la actividad del
     * cronograma; sin frente no hay desde dónde calcularlas, y conservarlas dejaría en pantalla unas
     * fechas huérfanas indistinguibles de las vigentes. El responsable, en cambio, es una decisión
     * humana que no depende de ninguna fecha: quien iba a comprar ese paquete lo sigue haciendo.
     *
     * @return array{ok: bool, code?: string}
     */
    public function desamarrar(int $projectId, int $paqueteId, string $usuario): array
    {
        $this->db->beginTransaction();
        try {
            $responsable = $this->db->query(
                'SELECT responsable_user_id, responsable_asignado_por, responsable_asignado_at
                   FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                [$projectId, $paqueteId],
            )->fetch(\PDO::FETCH_ASSOC) ?: null;

            $this->db->query('DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);
            $this->db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);
            $this->db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);

            // El responsable se devuelve a una fila mínima: el paquete no tiene plan, pero sí dueño.
            // Sin esto, reamarrar obligaría a repartir el trabajo otra vez desde cero.
            if ($responsable !== null && $responsable['responsable_user_id'] !== null) {
                // ... reinsertar la fila conservando SOLO las tres columnas del responsable
            }

            $this->db->commit();
            return ['ok' => true];
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
    }
```

**Decisión que el implementador debe resolver y documentar:** `pdc_plan_paquete` tiene columnas `NOT NULL` (`fecha_ancla`, `fecha_arranque`, `dias_totales`…) que no se pueden rellenar sin frente. Dos salidas: (a) hacerlas nullable con una migración, o (b) guardar el responsable en otra parte. **Mide antes de elegir** — mira el `SHOW CREATE TABLE` real y decide con el dato delante, no de memoria. Si haces (a), la migración va en esta misma tarea.

- [ ] **Step 4: Endpoint, ruta y guard**

`POST /plan-compras/api/plan/desamarrar` con cuerpo `{paqueteId}`, guard `guardEscritura()` (que ya exige `lps.paquetes_contratacion.editar` + CSRF).

- [ ] **Step 5: Tests, ratchet y PHPStan**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | tail -3
docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php 2>&1 | tail -2
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --no-progress 2>&1 | tail -3
```

- [ ] **Step 6: Commit**

---

### Task 2: Que cambiar de frente deje de borrar al responsable (f11, f14)

**Files:** `PlanFechasService.php` (`amarrar()`, líneas ~470-513), `tests/test_pdc_v2_plan_fechas.php`.

**Esto es un arreglo de un fallo existente, no una función nueva.** Hoy reamarrar a otro frente borra `pdc_plan_paquete` entera, y con ella el responsable, sin decir nada.

- [ ] **Step 1: Escribir el test que falla**

```php
// Reamarrar a OTRO frente conserva el responsable. Hoy no: el DELETE de la invalidación se lo lleva.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$svc->calcular($P, 'test-a4');
$db->query('UPDATE pdc_plan_paquete SET responsable_user_id = ? WHERE project_id = ? AND paquete_id = ?',
    [$uid, $P, $paqEstructura]);

$svc->amarrar($P, $paqEstructura, 9002, 'test-a4');   // otro frente: invalida el plan viejo
$tras = $db->query('SELECT responsable_user_id FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqEstructura])->fetchColumn();
$assert((int) $tras === $uid,
    'Reamarre: cambiar de frente NO borra al responsable. Dio ' . var_export($tras, true));
```

- [ ] **Step 2: Verlo fallar** — debe fallar contra el código actual. Si pasa a la primera, el diagnóstico era erróneo: **para y avisa** antes de tocar nada.

- [ ] **Step 3: Implementar** — mismo patrón que la Task 1: leer el responsable antes de invalidar y devolverlo después.

- [ ] **Step 4: Tests, ratchet, PHPStan, commit**

---

### Task 3: Elegir la versión oficial (f15, f16, f17, f18, f19)

**Files:** `PresupuestoImportService.php`, `PlanComprasImportController.php`, `public/index.php`, `tests/test_pdc_v2_*.php`.

**Decisión tomada:** **avisar, no bloquear.** Se muestra qué vínculos del maestro quedarán afectados y el usuario decide.

- [ ] **Step 1: Escribir los tests que fallan**

Cubrir: (a) activar una versión anterior apaga la actual y deja exactamente una activa; (b) el conteo de trabajo afectado sobre la versión que se abandona es correcto; (c) sin permiso `lps.pdc.importar` se rechaza.

```php
$r = $svcImport->activar($P, $versionVieja, 'test');
$assert(($r['ok'] ?? false) === true, 'Activar: responde ok.');
$activas = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [$P])->fetchColumn();
$assert($activas === 1, 'Activar: queda exactamente una versión activa. Dio ' . $activas);
```

- [ ] **Step 2: Verlo fallar**

- [ ] **Step 3: Implementar `activar()`**

En una transacción: apagar la activa y encender la elegida. **La columna generada `activa_unica` con índice único rechaza dos activas a la vez**, así que el orden dentro de la transacción importa: apagar primero.

Y un método de solo lectura para el aviso:

```php
    /**
     * Qué trabajo quedará apuntando a otra versión si se cambia la oficial.
     *
     * Solo cuenta los vínculos del maestro: son lo ÚNICO atado a una versión concreta. Las
     * asignaciones a paquete y el plan de fechas no llevan version_id — viven a nivel de proyecto
     * y sobreviven al cambio. Por eso esto avisa en vez de bloquear.
     *
     * @return array{vinculosAfectados: int}
     */
    public function impactoDeCambiarVersion(int $projectId, int $versionActual): array
```

- [ ] **Step 4: Endpoint con el guard correcto**

`POST /plan-compras/api/presupuesto/activar`. Guard: **`lps.pdc.importar`** — el mismo del import, como se decidió. Ojo: eso deja fuera al rol Planeación, que sí puede amarrar y calcular. Es intencional.

- [ ] **Step 5: Tests, ratchet, PHPStan, commit**

---

### Task 4: Selector de nivel y expansión inicial (f20, f21, f22)

**Files:** `plan-de-compras/src/lib/presupuestoTree.ts`, `comparativo.ts`, sus tests, `VisorPresupuesto.tsx`, `ComparativoPresupuesto.tsx`.

**No hace falta tocar el servidor:** `nivel` ya viene en cada fila.

- [ ] **Step 1: Escribir los tests que fallan**

```ts
describe('expandirHastaNivel', () => {
  it('con nivel 2, se ven capítulos y subcapítulos, no lo de más abajo', () => { /* ... */ })
  it('con nivel «insumo», se ve todo el árbol abierto', () => { /* ... */ })
  it('el conjunto de expandidos que devuelve es el que el árbol ya sabe consumir', () => { /* ... */ })
})
```

- [ ] **Step 2: Verlo fallar**

- [ ] **Step 3: Implementar**

Una función pura que, dado el listado de filas y un nivel objetivo, devuelve el `Set<string>` de códigos expandidos — que es exactamente lo que `filasVisibles()` ya consume. **No reescribas la construcción del árbol**: solo produce el conjunto inicial.

Por defecto: **desplegado hasta insumos**. Son ~1.343 filas en la versión activa; AG Grid las virtualiza sin problema (medido).

- [ ] **Step 4: Misma función en el comparador** — mismo control, mismo defecto, para que las dos pantallas hablen igual.

- [ ] **Step 5: Tests, build, commit**

---

### Task 5: Puentes desde el historial (f23, f24, f25)

**Files:** `ImportarPresupuesto.tsx`, `src/lib/` (lógica pura de selección), `App.tsx` si hace falta pasar parámetros por la ruta.

**Decisión tomada:** **sin modal para ver** (el clic lleva directo); **sí confirmación** para fijar la versión oficial, que es lo que cambia datos.

- [ ] **Step 1: Escribir los tests que fallan**

```ts
describe('selección para comparar', () => {
  it('deja marcar dos versiones', () => { /* ... */ })
  it('al intentar una tercera, no la admite', () => { /* ... */ })
  it('con menos de dos, el botón Comparar está deshabilitado', () => { /* ... */ })
})
```

- [ ] **Step 2–3: Verlo fallar, implementar**

- Clic en la fila → navegar a `#/ensamble/presupuesto` con esa versión seleccionada.
- Selección de hasta dos + botón «Comparar» → `#/ensamble/comparar` con ambas.
- Botón de fijar como oficial → confirmación → `POST .../activar`.

**Cuidado:** el clic en la fila ahora hace tres cosas potenciales (navegar, marcar para comparar, fijar como oficial). Que la casilla marque y el resto de la fila navegue, como se decidió para el Plan.

- [ ] **Step 4: Tests, build, republicar el bundle a mano, commit en ambos repos**

---

### Task 6: Exponer desamarrar y cambiar de frente en la pantalla (f10, f14)

**Files:** `PlanFechas.tsx`, `src/lib/planFechas.ts`.

- [ ] **Step 1: Escribir los tests que fallan** — lógica pura: qué acciones ofrece una fila según tenga frente o no.

- [ ] **Step 2–3: Verlo fallar, implementar**

- La columna «Frente» pasa a ser un desplegable editable, con los mismos frentes que ofrece la sección «Sin frente».
- Una acción para desamarrar, con confirmación que diga la verdad: **se borran las fechas, se conserva el responsable**.
- Al desamarrar, el paquete debe reaparecer en «Sin frente». Recuerda que esa lista **se calcula en el cliente** cruzando el resumen de paquetes con los amarres: hay que refrescar ambos, no solo uno.

- [ ] **Step 4: Verificación visual, tests, build, bundle, commit**

---

### Task 7: Cierre — verificación completa

- [ ] Suite PHP en 0 FAIL, Vitest en verde, ratchet en 7, PHPStan `[OK]`.
- [ ] Recorrido en el navegador de las seis pantallas (**el login lo hace el usuario**).
- [ ] Bundle republicado a mano y commiteado en `lps-aia-pdc`.
- [ ] Repasar `facts.md` f10–f25 uno por uno y marcar los que quedan cumplidos.
