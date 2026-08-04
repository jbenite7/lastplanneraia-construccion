# PDC v2 · Fase B1 — Seguimiento al Plan de Compras — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cada paso de contratación de cada paquete pueda registrar **cuándo ocurrió de verdad**, se vea junto a lo programado y junto a una proyección calculada al vuelo, y que ese avance sobreviva a recálculos y reamarres.

**Architecture:** Tres columnas nuevas en `pdc_plan_paso` (`fecha_real`, `registrado_por`, `registrado_at`) que el upsert de `calcular()` ya conserva por no listarlas. Un servicio nuevo `SeguimientoService` con la aritmética de la proyección (pura, sin BD) y las tres operaciones de lectura/escritura; un controlador nuevo con el mismo RBAC que el plan; una pantalla nueva en la SPA (lista + panel de detalle) con la misma aritmética replicada en `pdc-app/src/lib/seguimiento.ts` para que la vista no tenga que consultar por cada tecla.

**Tech Stack:** PHP 8.3 sin framework (PDO vía `\Database`), MySQL 8 en Docker, tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1), SPA React + TypeScript + AG Grid + Vitest en `pdc-app/`.

**Spec:** `docs/superpowers/specs/2026-07-29-pdc-b1-seguimiento-design.md`

## Global Constraints

- Español en código, comentarios, mensajes de error y de commit. Identificadores en `snake_case` español, como el resto del módulo.
- El trabajo vive en el worktree `/Volumes/Crucial X6/Developer/lps-aia-b1` (rama `pdc-b1-seguimiento`). El checkout principal tiene cambios sin commitear de otra sesión: **no se toca**.
- **PHP:** `/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh <ruta o -r '...'>`. Monta el código del worktree en la imagen existente y lo enchufa a la red del stack principal. **Nunca un PHP del host, y nunca `docker compose up` desde el worktree**: el `name:` del compose es fijo y recrearía el stack compartido.
- **MySQL:** `docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'`. Es la base del stack principal (puerto 3307), la única que tiene DAPORTO.
- `vendor/` y `pdc-app/node_modules` del worktree apuntan al checkout principal (están en `.gitignore`; mismo `composer.lock`, mismo `package-lock.json`). No reinstalarlos.
- **Solo DAPORTO (`project_id = 73`)**, y se puede dejar alterado. No se crean proyectos sintéticos. Los tests deben ser **idempotentes**: dos corridas seguidas dejan el mismo estado.
- **`tests/test_pdc_v2_brecha_daporto.php` NO es un gate en este entorno.** Falla ya en la línea base con `FAIL: no hay versión 292 en el proyecto 73`: tiene la versión de presupuesto fijada a `$VERSION = 292` (línea 26), que existía en la base del stack retirado. Esta base tiene las versiones 24/148/180. Es un rojo preexistente y ambiental, **no** una regresión de B1, y no se arregla aquí. La red de regresión de este plan son `test_pdc_v2_plan_fechas.php`, `test_global_table_safety.php` y `test_global_table_reconciliation.php`, verdes los tres en la línea base.
- DDL en `.sql` con guardias por `information_schema` que **converjan** (no solo «no fallar»), modelo: `database/migrations/20260728_pdc_v2_plan_fechas.sql`.
- TDD estricto: test primero, verlo fallar, implementar lo mínimo, verlo pasar, commitear.
- La lógica testeable de la SPA vive en `src/lib/`, nunca en los componentes. Gates: `npx vitest run` y `npm run build` desde `pdc-app/`.
- Alcance visual: desktop ≥1180 px, dark mode. Nada de mobile, tablet ni tema `linen` (AGENTS.md).
- No commitear `.env`, evidencia local ni trabajo ajeno. Un commit por task.

## File Structure

- Create: `database/seeds/sembrar_plan_daporto.php` — deja a DAPORTO con plan calculado (T0).
- Create: `database/migrations/20260729_pdc_v2_seguimiento_avance.sql` — las tres columnas + nulabilidad (T1).
- Create: `src/Services/Pdc/SeguimientoService.php` — proyección, derivados, lectura y registro (T2, T3).
- Create: `src/Controllers/Api/PlanComprasSeguimientoController.php` — los tres endpoints (T3).
- Create: `tests/test_pdc_v2_seguimiento.php` — el gate PHP de la fase (T2, T3, T4).
- Modify: `src/Services/Pdc/PlanFechasService.php` — `limpiarPlanCalculado()` conserva el avance (T4).
- Modify: `public/index.php` — las tres rutas nuevas (T3).
- Create: `pdc-app/src/lib/seguimiento.ts` + `seguimiento.test.ts` — proyección y filtros en cliente (T5).
- Modify: `pdc-app/src/lib/types.ts` — tipos del contrato (T5).
- Create: `pdc-app/src/pages/Seguimiento.tsx` — lista + panel de detalle (T6).
- Modify: `pdc-app/src/App.tsx`, `pdc-app/src/lib/navegacion.ts` — la ruta y la pestaña (T6).

---

### Task 0: DAPORTO tiene un plan que seguir

Sin esto no hay ni una fila en `pdc_plan_paso` y B1 no tiene sobre qué correr. Es datos, no código: se reproduce con lo que ya está en `main`.

**Files:**
- Create: `database/seeds/sembrar_plan_daporto.php`

**Interfaces:**
- Consumes: nada.
- Produces: DAPORTO con `pdc_rama_frente`, `pdc_paquete_frente`, `pdc_plan_paquete` y `pdc_plan_paso` pobladas. Las tasks siguientes asumen que existen filas de paso para el proyecto 73.

- [ ] **Step 1: Medir la línea base ANTES de tocar nada**

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'-e "
SELECT (SELECT COUNT(*) FROM pdc_rama_frente WHERE project_id=73) ramas,
       (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73) frentes,
       (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73) cabeceras,
       (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73) pasos,
       (SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id=73) insumos,
       (SELECT id FROM pdc_presupuesto_versiones WHERE project_id=73 AND activa=1) version_activa;
"' | tee /tmp/b1-linea-base.txt
```
Expected: `ramas=0 frentes=0 cabeceras=0 pasos=0 insumos=133 version_activa=180`. Si `insumos` o `version_activa` difieren, **parar**: la base no es la que este plan midió, y hay que reconciliarla antes de sembrar.

- [ ] **Step 2: Escribir el script de sembrado**

Crear `database/seeds/sembrar_plan_daporto.php`:

```php
<?php
/**
 * Deja a DAPORTO (project_id = 73) con plan de compras calculado.
 *
 * El amarre de ramas y el cálculo del plan se hicieron en su día contra el stack retirado
 * (`lps-aia-pdc`, puerto 3308), que tenía su propia base. El código viajó con el merge; los datos no.
 * Este script los reconstruye con las MISMAS rutinas del producto —nada de INSERT a mano—, así que
 * lo que quede sembrado es exactamente lo que la aplicación produciría.
 *
 * Uso:  php database/seeds/sembrar_plan_daporto.php            (dry-run: solo informa)
 *       php database/seeds/sembrar_plan_daporto.php --apply    (escribe)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\AmarreCronogramaService;
use App\Services\Pdc\PlanFechasService;

const PROYECTO = 73;
const USUARIO = 'seed-b1';

$aplicar = in_array('--apply', $argv, true);
$db = Database::getInstance();

$version = $db->query(
    'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
    [PROYECTO],
)->fetchColumn();
if ($version === false) {
    fwrite(STDERR, "ABORTADO: DAPORTO no tiene version de presupuesto activa.\n");
    exit(1);
}
$version = (int) $version;
echo 'Version activa: ', $version, $aplicar ? " (APLICANDO)\n" : " (DRY-RUN)\n";

// (1) Ramas del presupuesto -> frentes del cronograma. Idempotente por diseño (ver A4.2).
$amarre = new AmarreCronogramaService($db);
$rama = $amarre->amarrarVersion(PROYECTO, $version, $aplicar);
echo 'Ramas amarradas: ', json_encode($rama, JSON_UNESCAPED_UNICODE), "\n";

// (2) Paquetes -> frente. Se toma la propuesta del motor tal cual: es la misma que vería quien
// abriera la pantalla del Plan y pulsara «amarrar» en cada fila.
$svc = new PlanFechasService($db);
$sugerencias = $svc->sugerirFrentes(PROYECTO, $version);
echo 'Paquetes con propuesta de frente: ', count($sugerencias), "\n";

$amarrados = 0;
foreach ($sugerencias as $paqueteId => $s) {
    if (!$aplicar) {
        $amarrados++;
        continue;
    }
    $r = $svc->amarrar(PROYECTO, (int) $paqueteId, (int) $s['uniqueId'], USUARIO, [
        'origen' => 'sugerencia',
        'evidencia' => $s['evidencia'] ?? '',
    ]);
    if (($r['ok'] ?? false) === true) {
        $amarrados++;
    } else {
        echo '  paquete ', $paqueteId, ' NO amarrado: ', json_encode($r, JSON_UNESCAPED_UNICODE), "\n";
    }
}
echo 'Paquetes amarrados: ', $amarrados, "\n";

// (3) El cálculo del plan. En dry-run no se corre: sin amarres no tendria nada que calcular.
if ($aplicar) {
    $calc = $svc->calcular(PROYECTO, USUARIO);
    echo 'Calculo: ', json_encode($calc, JSON_UNESCAPED_UNICODE), "\n";

    $pasos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ?', [PROYECTO])->fetchColumn();
    echo 'Filas de paso resultantes: ', $pasos, "\n";
    if ($pasos === 0) {
        fwrite(STDERR, "ABORTADO: el calculo no dejo ninguna fila de paso.\n");
        exit(1);
    }
}

echo $aplicar ? "OK\n" : "Dry-run terminado. Repite con --apply para escribir.\n";
```

- [ ] **Step 3: Correr en dry-run**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh database/seeds/sembrar_plan_daporto.php
```
Expected: exit 0, informa la versión 180 y un número de paquetes con propuesta mayor que cero. Si sale `Paquetes con propuesta de frente: 0`, **parar**: el motor no encuentra frentes y el problema está aguas arriba (ramas sin amarrar, cronograma vacío), no en B1.

- [ ] **Step 4: Aplicar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh database/seeds/sembrar_plan_daporto.php --apply
```
Expected: exit 0 y `Filas de paso resultantes:` mayor que cero.

- [ ] **Step 5: Registrar la nueva línea base**

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'-e "
SELECT (SELECT COUNT(*) FROM pdc_rama_frente WHERE project_id=73) ramas,
       (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73) frentes,
       (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73) cabeceras,
       (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73) pasos;
"'
```
Expected: los cuatro mayores que cero. **Anotar los números en el mensaje del commit**: son la línea base de todo lo que sigue.

- [ ] **Step 6: Confirmar que el plan de fechas sigue verde**

El sembrado toca amarres y cálculo: si algo se rompió, este test lo dice.

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_plan_fechas.php > /dev/null; echo "exit=$?"
```
Expected: `exit=0`. (`test_pdc_v2_brecha_daporto.php` **no** se corre: ver Global Constraints — falla ya en la línea base por una versión fijada que esta base no tiene.)

- [ ] **Step 7: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add database/seeds/sembrar_plan_daporto.php && git commit -m "chore(pdc): DAPORTO recupera su plan de compras calculado

El amarre de ramas y el calculo del plan se hicieron contra el stack retirado, que
tenia otra base: el codigo viajo con el merge y los datos no, asi que en el stack
principal pdc_plan_paso estaba vacia y B1 no tenia pasos sobre los que registrar
avance. El script reconstruye el estado con las mismas rutinas del producto
(AmarreCronogramaService + sugerirFrentes + amarrar + calcular), en dry-run por
defecto, de modo que lo sembrado es lo que la aplicacion produciria.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 1: La migración del avance

**Files:**
- Create: `database/migrations/20260729_pdc_v2_seguimiento_avance.sql`

**Interfaces:**
- Consumes: nada.
- Produces: `pdc_plan_paso` con `fecha_real DATE NULL`, `registrado_por VARCHAR(100) NOT NULL DEFAULT ''`, `registrado_at DATETIME NULL`, y `fecha_inicio` / `fecha_fin` aceptando `NULL`. Todas las tasks siguientes dependen de este esquema.

- [ ] **Step 1: Escribir la migración**

Crear `database/migrations/20260729_pdc_v2_seguimiento_avance.sql`:

```sql
-- 20260729_pdc_v2_seguimiento_avance.sql
-- PDC v2 / Fase B1: el avance real de cada paso de contratacion.
--
-- Tres columnas sobre `pdc_plan_paso`, ninguna tabla nueva. Cuelgan de esa fila porque A4.1 le dio una
-- identidad estable (`paso_id`, no la posicion) y el upsert de `calcular()` lista solo las cuatro
-- columnas programadas: lo que no se lista, MySQL lo conserva. Es decir, estas tres sobreviven a
-- cualquier recalculo sin que haya que tocar PlanFechasService.
--
-- `fecha_real` NULL = el paso todavia no ha ocurrido. No hay columna de estado a proposito: «en curso /
-- cumplido» se deduce de la fecha, y un estado guardado se desincroniza de su fecha el primer dia en que
-- alguien corrija una y olvide la otra.
--
-- `fecha_inicio` / `fecha_fin` pasan a admitir NULL. Lo exige la regla de reamarre de B1: una fila puede
-- llevar avance real y quedarse, temporalmente, sin fechas programadas (el plan viejo se calculo contra
-- otro frente y ya no vale; el siguiente calculo las reescribe). Antes de esto, conservar la fila
-- obligaba a dejar en ella fechas mentirosas.
--
-- Sin backfill: no existe ningun dato de avance previo que migrar.
--
-- Las guardias por `information_schema` hacen que el archivo converja desde cualquier punto de partida y
-- que una segunda ejecucion sea un no-op real.

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_migra_seguimiento_avance$$
CREATE PROCEDURE pdc_v2_migra_seguimiento_avance()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'fecha_real'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `fecha_real` DATE NULL AFTER `fecha_fin`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'registrado_por'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `registrado_por` VARCHAR(100) NOT NULL DEFAULT '' AFTER `fecha_real`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'registrado_at'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `registrado_at` DATETIME NULL AFTER `registrado_por`;
  END IF;

  -- Nulabilidad de las programadas. Se comprueba IS_NULLABLE para que la segunda corrida no reescriba
  -- la tabla entera: un MODIFY sobre millones de filas no es gratis, y aqui converger significa
  -- «dejarlo como debe estar», no «volver a hacerlo».
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND COLUMN_NAME = 'fecha_inicio' AND IS_NULLABLE = 'NO'
  ) THEN
    ALTER TABLE `pdc_plan_paso` MODIFY COLUMN `fecha_inicio` DATE NULL;
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND COLUMN_NAME = 'fecha_fin' AND IS_NULLABLE = 'NO'
  ) THEN
    ALTER TABLE `pdc_plan_paso` MODIFY COLUMN `fecha_fin` DATE NULL;
  END IF;
END$$

CALL pdc_v2_migra_seguimiento_avance()$$
DROP PROCEDURE IF EXISTS pdc_v2_migra_seguimiento_avance$$

DELIMITER ;
```

- [ ] **Step 2: Aplicar**

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260729_pdc_v2_seguimiento_avance.sql; echo "exit=$?"
```
Expected: sin salida, `exit=0`.

- [ ] **Step 3: Verificar el esquema**

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'-e "SHOW COLUMNS FROM pdc_plan_paso;"'
```
Expected: aparecen `fecha_real date YES`, `registrado_por varchar(100) NO`, `registrado_at datetime YES`, y `fecha_inicio` / `fecha_fin` con `Null = YES`.

- [ ] **Step 4: Verificar que reejecutar es un no-op**

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260729_pdc_v2_seguimiento_avance.sql && docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "SHOW COLUMNS FROM pdc_plan_paso;"'
```
Expected: exit 0 y esquema idéntico al del paso anterior, sin columnas duplicadas.

- [ ] **Step 5: Confirmar que el plan sigue calculando igual**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_plan_fechas.php > /dev/null; echo "plan_fechas exit=$?"
```
Expected: `exit=0`. La migración no cambia comportamiento; si esto se pone rojo, algo del esquema se rompió.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add database/migrations/20260729_pdc_v2_seguimiento_avance.sql && git commit -m "feat(pdc): pdc_plan_paso guarda cuando ocurrio cada paso

Fase B1. Tres columnas —fecha_real, registrado_por, registrado_at— sobre la fila de
paso, que A4.1 hizo estable por identidad y que el upsert de calcular() conserva por
no listarla. Sin columna de estado a proposito: se deduce de la fecha, y un estado
guardado se desincroniza el primer dia. Las fechas programadas pasan a admitir NULL,
que es lo que permitira conservar el avance cuando un reamarre invalide el plan.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: `SeguimientoService` — proyección y lectura

**Files:**
- Create: `src/Services/Pdc/SeguimientoService.php`
- Test: `tests/test_pdc_v2_seguimiento.php`

**Interfaces:**
- Consumes: el esquema de la Task 1.
- Produces:
  - `SeguimientoService::__construct(\Database $db)`
  - `SeguimientoService::proyectar(string $fechaArranque, array $pasos, string $hoy): array` — **pura**, sin BD. `$pasos` es una lista de `['dias' => int, 'fechaFin' => ?string, 'fechaReal' => ?string]`; devuelve una lista paralela de `['proyectadoInicio' => string, 'proyectadoFin' => string, 'desfaseDias' => ?int]`.
  - `SeguimientoService::pasosDePaquete(int $projectId, int $paqueteId): array` — lista de `['pasoId' => ?int, 'orden' => int, 'paso' => string, 'dias' => int, 'fechaInicio' => ?string, 'fechaFin' => ?string, 'fechaReal' => ?string, 'proyectadoInicio' => string, 'proyectadoFin' => string, 'desfaseDias' => ?int, 'registradoPor' => string, 'registradoAt' => ?string]`.
  - `SeguimientoService::resumen(int $projectId): array` — lista de `['paqueteId' => int, 'nombre' => string, 'frenteNombre' => string, 'responsableUserId' => ?int, 'responsableNombre' => string, 'responsableHuerfano' => bool, 'pasoActual' => string, 'cumplidos' => int, 'total' => int, 'estado' => string, 'atrasado' => bool, 'finProgramado' => ?string, 'finProyectado' => string]`. `estado` ∈ `sin_empezar` | `en_curso` | `terminado`.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_seguimiento.php`:

```php
<?php
/**
 * Gate de la fase B1 (Seguimiento al Plan de Compras).
 *
 * Corre contra DAPORTO (project_id = 73) y PUEDE dejarlo alterado: es la decision de datos de B1.
 * Lo que si exige es ser idempotente — cada corrida limpia su propio rastro al empezar, de modo que
 * dos ejecuciones seguidas dejan el mismo estado.
 *
 * Autoejecutable: imprime PASS:/FAIL: y sale con 0/1. No hay PHPUnit en este repo.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

const P = 73;

$db = Database::getInstance();
$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    echo ($cond ? 'PASS: ' : 'FAIL: '), $msg, "\n";
    if (!$cond) {
        $fallos++;
    }
};

// Limpieza previa: este test escribe fechas reales sobre paquetes de verdad. Borrarlas al empezar
// —y no al terminar— es lo que hace que una corrida interrumpida no envenene la siguiente.
$db->query("UPDATE pdc_plan_paso SET fecha_real = NULL, registrado_por = '', registrado_at = NULL
            WHERE project_id = ? AND registrado_por LIKE 'test-b1%'", [P]);

$svc = new SeguimientoService($db);

// --- La proyeccion, sin base de datos ---
// Tres pasos de 5, 10 y 3 dias desde el 2026-03-02. Nada cumplido y hoy muy anterior: la proyeccion
// es identica al plan.
$pasos = [
    ['dias' => 5, 'fechaFin' => '2026-03-07', 'fechaReal' => null],
    ['dias' => 10, 'fechaFin' => '2026-03-17', 'fechaReal' => null],
    ['dias' => 3, 'fechaFin' => '2026-03-20', 'fechaReal' => null],
];
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['proyectadoInicio'] === '2026-03-02' && $p[0]['proyectadoFin'] === '2026-03-07',
    'Proyeccion: sin nada cumplido, el primer paso se proyecta donde dice el plan. Dio ' . json_encode($p[0]));
$assert($p[2]['proyectadoFin'] === '2026-03-20',
    'Proyeccion: sin nada cumplido, el ultimo paso termina donde dice el plan. Dio ' . $p[2]['proyectadoFin']);
$assert($p[0]['desfaseDias'] === null,
    'Proyeccion: un paso sin fecha real no tiene desfase, tiene null (no cero: cero significaria «llego puntual»).');

// El primer paso se cumplio 10 dias tarde: lo que sigue se corre esos 10 dias.
$pasos[0]['fechaReal'] = '2026-03-17';
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['desfaseDias'] === 10,
    'Proyeccion: el desfase de un paso cumplido es real menos programado. Dio ' . var_export($p[0]['desfaseDias'], true));
$assert($p[0]['proyectadoFin'] === '2026-03-17',
    'Proyeccion: la proyectada de un paso cumplido ES su fecha real.');
$assert($p[1]['proyectadoInicio'] === '2026-03-17' && $p[1]['proyectadoFin'] === '2026-03-27',
    'Proyeccion: el paso siguiente arranca donde termino lo real, no donde decia el plan. Dio ' . json_encode($p[1]));
$assert($p[2]['proyectadoFin'] === '2026-03-30',
    'Proyeccion: el atraso se arrastra hasta el final. Dio ' . $p[2]['proyectadoFin']);

// Un paso adelantado tambien mueve la cadena, hacia atras.
$pasos[0]['fechaReal'] = '2026-03-04';
$p = $svc->proyectar('2026-03-02', $pasos, '2026-01-01');
$assert($p[0]['desfaseDias'] === -3,
    'Proyeccion: cumplir antes de tiempo da desfase negativo. Dio ' . var_export($p[0]['desfaseDias'], true));
$assert($p[2]['proyectadoFin'] === '2026-03-17',
    'Proyeccion: adelantarse tambien mueve la cadena. Dio ' . $p[2]['proyectadoFin']);

// Todo pendiente y el plan ya vencido: proyectar hacia el pasado no informa de nada, asi que el
// cursor se adelanta a hoy.
$pasos[0]['fechaReal'] = null;
$p = $svc->proyectar('2026-03-02', $pasos, '2026-06-01');
$assert($p[0]['proyectadoInicio'] === '2026-06-01',
    'Proyeccion: si lo pendiente ya vencio, la proyeccion arranca hoy, no en el pasado. Dio ' . $p[0]['proyectadoInicio']);
$assert($p[2]['proyectadoFin'] === '2026-06-19',
    'Proyeccion: y la cadena entera se corre con el. Dio ' . $p[2]['proyectadoFin']);

// Un paso sin fecha programada (reamarre pendiente de recalculo) no tiene desfase contra que medirse.
$sinPlan = [['dias' => 4, 'fechaFin' => null, 'fechaReal' => '2026-03-10']];
$p = $svc->proyectar('2026-03-02', $sinPlan, '2026-01-01');
$assert($p[0]['desfaseDias'] === null,
    'Proyeccion: sin fecha programada no hay desfase, aunque haya fecha real.');

// --- Lectura contra la base ---
$paquete = (int) $db->query(
    'SELECT paquete_id FROM pdc_plan_paso WHERE project_id = ? ORDER BY paquete_id LIMIT 1', [P],
)->fetchColumn();
$assert($paquete > 0, 'DAPORTO tiene al menos un paquete con pasos calculados (ver Task 0 del plan).');

$detalle = $svc->pasosDePaquete(P, $paquete);
$assert(count($detalle) > 0, 'Detalle: el paquete devuelve sus pasos. Dio ' . count($detalle));
$assert(array_key_exists('proyectadoFin', $detalle[0]) && array_key_exists('fechaReal', $detalle[0]),
    'Detalle: cada paso trae programado, real y proyectado.');
$assert($detalle[0]['fechaReal'] === null,
    'Detalle: sin registrar nada, la fecha real es null.');

$resumen = $svc->resumen(P);
$assert(count($resumen) > 0, 'Resumen: devuelve una fila por paquete con plan. Dio ' . count($resumen));
$fila = null;
foreach ($resumen as $r) {
    if ($r['paqueteId'] === $paquete) {
        $fila = $r;
    }
}
$assert($fila !== null, 'Resumen: el paquete de prueba esta en el resumen.');
$assert(($fila['estado'] ?? '') === 'sin_empezar',
    'Resumen: sin ningun paso cumplido, el paquete esta «sin empezar». Dio ' . ($fila['estado'] ?? 'null'));
$assert(($fila['cumplidos'] ?? -1) === 0 && ($fila['total'] ?? 0) === count($detalle),
    'Resumen: cuenta 0 cumplidos de ' . count($detalle) . '. Dio ' . json_encode([$fila['cumplidos'] ?? null, $fila['total'] ?? null]));
$assert(($fila['pasoActual'] ?? '') === $detalle[0]['paso'],
    'Resumen: el paso actual es el primero sin fecha real. Dio ' . ($fila['pasoActual'] ?? 'null'));

$assert($fallos === 0, 'Sin fallos');
echo $fallos === 0 ? "=== OK ===\n" : "=== {$fallos} FALLOS ===\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: error fatal `Class "App\Services\Pdc\SeguimientoService" not found`. Exit distinto de 0.

- [ ] **Step 3: Implementar el servicio**

Crear `src/Services/Pdc/SeguimientoService.php`:

```php
<?php

namespace App\Services\Pdc;

/**
 * Seguimiento al plan de compras (PDC v2 / Fase B1): cuando ocurrio de verdad cada paso de
 * contratacion, y como se lee eso contra lo que estaba programado.
 *
 * Va aparte de PlanFechasService a proposito. Aquel calcula el plan —cuando deberian pasar las
 * cosas— y ya pasa de 1.600 lineas; este registra lo que paso. Son dos responsabilidades que se
 * tocan solo por la tabla, y mantenerlas separadas es lo que permite razonar sobre cualquiera de las
 * dos sin sostener la otra en la cabeza.
 */
class SeguimientoService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Proyeccion: cuando terminara cada paso si lo pendiente dura lo previsto.
     *
     * Es aritmetica pura, sin base de datos, para poder probarla con casos escritos a mano en vez de
     * con un plan sembrado. Lo PROGRAMADO no entra ni sale: es la linea base contra la que se mide el
     * atraso, y reescribirla dejaria al proyecto sin forma de decir cuanto se desvio de lo prometido.
     *
     * Un paso cumplido vale por si mismo: su proyectada ES su fecha real. Uno pendiente hereda el
     * cursor que dejo el anterior. Y si al llegar al primer pendiente el cursor esta en el pasado, se
     * adelanta a hoy: decir que algo que no ha ocurrido «terminara» hace tres semanas no es una
     * proyeccion, es ruido.
     *
     * @param list<array{dias: int, fechaFin: ?string, fechaReal: ?string}> $pasos en orden
     * @return list<array{proyectadoInicio: string, proyectadoFin: string, desfaseDias: ?int}>
     */
    public function proyectar(string $fechaArranque, array $pasos, string $hoy): array
    {
        $cursor = new \DateTimeImmutable($fechaArranque);
        $limite = new \DateTimeImmutable($hoy);
        $out = [];

        foreach ($pasos as $p) {
            if ($p['fechaReal'] !== null) {
                $real = new \DateTimeImmutable($p['fechaReal']);
                $inicio = $cursor;
                $cursor = $real;
                $out[] = [
                    'proyectadoInicio' => $inicio->format('Y-m-d'),
                    'proyectadoFin' => $real->format('Y-m-d'),
                    // Sin fecha programada no hay contra que medir: es el caso de un paso que
                    // conserva su avance mientras espera que el plan se recalcule tras un reamarre.
                    'desfaseDias' => $p['fechaFin'] === null
                        ? null
                        : (int) (new \DateTimeImmutable($p['fechaFin']))->diff($real)->format('%r%a'),
                ];
                continue;
            }

            if ($cursor < $limite) {
                $cursor = $limite;
            }
            $inicio = $cursor;
            $cursor = $cursor->modify(sprintf('+%d days', $p['dias']));
            $out[] = [
                'proyectadoInicio' => $inicio->format('Y-m-d'),
                'proyectadoFin' => $cursor->format('Y-m-d'),
                'desfaseDias' => null,
            ];
        }

        return $out;
    }

    /**
     * Los pasos de un paquete con las tres fechas: programada, real y proyectada.
     *
     * @return list<array<string, mixed>>
     */
    public function pasosDePaquete(int $projectId, int $paqueteId): array
    {
        $arranque = $this->db->query(
            'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, $paqueteId],
        )->fetchColumn();
        if ($arranque === false || $arranque === null) {
            // Sin cabecera con fechas no hay plan que seguir. Devolver vacio y no reventar: la
            // pantalla tiene que poder pedir el detalle de cualquier fila sin comprobar antes.
            return [];
        }

        $rows = $this->db->query(
            'SELECT paso_id, orden, paso, dias, fecha_inicio, fecha_fin, fecha_real,
                    registrado_por, registrado_at
             FROM pdc_plan_paso
             WHERE project_id = ? AND paquete_id = ?
             ORDER BY orden',
            [$projectId, $paqueteId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $paraProyectar = array_map(static fn (array $r): array => [
            'dias' => (int) $r['dias'],
            'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
            'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
        ], $rows);
        $proyeccion = $this->proyectar((string) $arranque, $paraProyectar, (new \DateTimeImmutable('today'))->format('Y-m-d'));

        $out = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'pasoId' => $r['paso_id'] === null ? null : (int) $r['paso_id'],
                'orden' => (int) $r['orden'],
                'paso' => (string) $r['paso'],
                'dias' => (int) $r['dias'],
                'fechaInicio' => $r['fecha_inicio'] === null ? null : (string) $r['fecha_inicio'],
                'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
                'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
                'proyectadoInicio' => $proyeccion[$i]['proyectadoInicio'],
                'proyectadoFin' => $proyeccion[$i]['proyectadoFin'],
                'desfaseDias' => $proyeccion[$i]['desfaseDias'],
                'registradoPor' => (string) $r['registrado_por'],
                'registradoAt' => $r['registrado_at'] === null ? null : (string) $r['registrado_at'],
            ];
        }

        return $out;
    }

    /**
     * Una fila por paquete con plan: en que paso va, cuanto lleva, si esta atrasado.
     *
     * Todos los derivados se calculan aqui y ninguno se guarda. Un estado persistido se
     * desincroniza de la fecha que lo justifica en cuanto alguien corrija una sola de las dos.
     *
     * @return list<array<string, mixed>>
     */
    public function resumen(int $projectId): array
    {
        $cabeceras = $this->db->query(
            'SELECT pp.paquete_id, pp.fecha_arranque, p.nombre, f.frente_nombre,
                    pp.responsable_user_id, u.nombre AS responsable_nombre,
                    u.activo AS responsable_activo, pm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_user_id
             WHERE pp.project_id = ? AND p.activo = 1 AND pp.fecha_arranque IS NOT NULL
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Una sola consulta para todos los pasos del proyecto: pedirlos paquete por paquete serian
        // cientos de viajes a la base para pintar una pantalla.
        $porPaquete = [];
        foreach ($this->db->query(
            'SELECT paquete_id, orden, paso, dias, fecha_fin, fecha_real
             FROM pdc_plan_paso WHERE project_id = ? ORDER BY paquete_id, orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $porPaquete[(int) $r['paquete_id']][] = $r;
        }

        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $out = [];
        foreach ($cabeceras as $c) {
            $paqueteId = (int) $c['paquete_id'];
            $pasos = $porPaquete[$paqueteId] ?? [];
            if ($pasos === []) {
                continue;
            }

            $proyeccion = $this->proyectar(
                (string) $c['fecha_arranque'],
                array_map(static fn (array $r): array => [
                    'dias' => (int) $r['dias'],
                    'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
                    'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
                ], $pasos),
                $hoy,
            );

            $cumplidos = 0;
            $pasoActual = '';
            $atrasado = false;
            $finProgramado = null;
            foreach ($pasos as $i => $r) {
                $finProgramado = $r['fecha_fin'] === null ? $finProgramado : (string) $r['fecha_fin'];
                if ($r['fecha_real'] !== null) {
                    $cumplidos++;
                    if (($proyeccion[$i]['desfaseDias'] ?? 0) > 0) {
                        $atrasado = true;
                    }
                    continue;
                }
                if ($pasoActual === '') {
                    $pasoActual = (string) $r['paso'];
                }
                // Pendiente cuya fecha programada ya paso: nadie lo ha hecho y el plazo vencio.
                if ($r['fecha_fin'] !== null && (string) $r['fecha_fin'] < $hoy) {
                    $atrasado = true;
                }
            }

            $total = count($pasos);
            $out[] = [
                'paqueteId' => $paqueteId,
                'nombre' => (string) $c['nombre'],
                'frenteNombre' => (string) ($c['frente_nombre'] ?? ''),
                'responsableUserId' => $c['responsable_user_id'] === null ? null : (int) $c['responsable_user_id'],
                'responsableNombre' => (string) ($c['responsable_nombre'] ?? ''),
                'responsableHuerfano' => $c['responsable_user_id'] !== null
                    && ($c['responsable_miembro'] === null || (int) $c['responsable_activo'] !== 1),
                'pasoActual' => $pasoActual,
                'cumplidos' => $cumplidos,
                'total' => $total,
                'estado' => $cumplidos === 0 ? 'sin_empezar' : ($cumplidos === $total ? 'terminado' : 'en_curso'),
                'atrasado' => $atrasado,
                'finProgramado' => $finProgramado,
                'finProyectado' => $proyeccion[$total - 1]['proyectadoFin'],
            ];
        }

        return $out;
    }
}
```

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: todas `PASS:`, `=== OK ===`, `exit=0`.

- [ ] **Step 5: Correrlo dos veces seguidas para probar la idempotencia**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php > /dev/null && /private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: `exit=0` las dos veces, misma salida.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add src/Services/Pdc/SeguimientoService.php tests/test_pdc_v2_seguimiento.php && git commit -m "feat(pdc): el seguimiento sabe leer el avance y proyectar lo que falta

Servicio nuevo, aparte de PlanFechasService: uno calcula cuando deberian pasar las
cosas, este lee cuando pasaron. La proyeccion es aritmetica pura y probada con casos
escritos a mano —un paso cumplido vale por si mismo y arrastra la cadena, y si lo
pendiente ya vencio se proyecta desde hoy, no desde el pasado—. Lo programado no se
toca: es la vara con la que se mide el atraso.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Registrar la fecha real (servicio + API)

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php` (método nuevo `registrarPaso()`)
- Create: `src/Controllers/Api/PlanComprasSeguimientoController.php`
- Modify: `public/index.php` (tres rutas, junto a las de `/plan-compras/api/plan/*`)
- Test: `tests/test_pdc_v2_seguimiento.php`

**Interfaces:**
- Consumes: `SeguimientoService::pasosDePaquete()` y `resumen()` de la Task 2.
- Produces:
  - `SeguimientoService::registrarPaso(int $projectId, int $paqueteId, int $pasoId, ?string $fechaReal, string $usuario): array` → `['ok' => true]` o `['ok' => false, 'code' => 'PASO_INVALIDO'|'FECHA_INVALIDA', 'mensaje' => string]`.
  - `GET /plan-compras/api/seguimiento` → `{resumen: [...]}`.
  - `GET /plan-compras/api/seguimiento/paquete?paqueteId=N` → `{pasos: [...]}`.
  - `POST /plan-compras/api/seguimiento/paso` `{paqueteId, pasoId, fechaReal}` → `{ok: true}`.

- [ ] **Step 1: Escribir el test que falla**

En `tests/test_pdc_v2_seguimiento.php`, insertar **antes** de la línea `$assert($fallos === 0, 'Sin fallos');`:

```php
// --- Registro del avance ---
$pasoId = $detalle[0]['pasoId'];
$assert($pasoId !== null, 'El paso de prueba tiene identidad (paso_id). Sin ella el registro no puede direccionarse.');

$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');
$assert(($r['ok'] ?? false) === true, 'Registro: guardar una fecha real responde ok. Dio ' . json_encode($r));

$tras = $svc->pasosDePaquete(P, $paquete);
$assert($tras[0]['fechaReal'] === '2026-04-15',
    'Registro: la fecha real queda guardada. Dio ' . var_export($tras[0]['fechaReal'], true));
$assert($tras[0]['registradoPor'] === 'test-b1',
    'Registro: queda constancia de quien la puso. Dio ' . $tras[0]['registradoPor']);
$assert($tras[0]['registradoAt'] !== null,
    'Registro: y de cuando la puso.');
$assert($tras[0]['proyectadoFin'] === '2026-04-15',
    'Registro: la proyectada del paso pasa a ser la real.');

// El paquete deja de estar «sin empezar» sin que nadie guarde ningun estado.
$fila2 = null;
foreach ($svc->resumen(P) as $x) {
    if ($x['paqueteId'] === $paquete) {
        $fila2 = $x;
    }
}
$assert(($fila2['estado'] ?? '') === 'en_curso',
    'Registro: con un paso cumplido de varios, el paquete pasa a «en curso». Dio ' . ($fila2['estado'] ?? 'null'));
$assert(($fila2['cumplidos'] ?? 0) === 1, 'Registro: el resumen cuenta un paso cumplido.');

// Un paso que no es de este paquete se rechaza. La FK garantiza que el paso existe en el catalogo,
// no que pertenezca a este plan: sin esta comprobacion, un cliente podria escribir en el paquete de
// otro pasandole el paso_id correcto y el paquete_id equivocado.
$otroPaquete = (int) $db->query(
    'SELECT paquete_id FROM pdc_plan_paso WHERE project_id = ? AND paquete_id <> ? LIMIT 1',
    [P, $paquete],
)->fetchColumn();
if ($otroPaquete > 0) {
    $r = $svc->registrarPaso(P, $otroPaquete, 999999, '2026-04-15', 'test-b1');
    $assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'PASO_INVALIDO',
        'Registro: un paso que no pertenece al plan del paquete se rechaza. Dio ' . json_encode($r));
}

// Fecha con formato invalido: se rechaza en vez de guardar basura que luego reviente al proyectar.
$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, '15/04/2026', 'test-b1');
$assert(($r['ok'] ?? true) === false && ($r['code'] ?? '') === 'FECHA_INVALIDA',
    'Registro: una fecha mal formada se rechaza. Dio ' . json_encode($r));

// Otro proyecto no puede tocar los pasos de este.
$r = $svc->registrarPaso(999999, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');
$assert(($r['ok'] ?? true) === false,
    'Registro: el aislamiento por project_id se respeta. Dio ' . json_encode($r));
$assert($svc->pasosDePaquete(P, $paquete)[0]['fechaReal'] === '2026-04-15',
    'Registro: y el intento del otro proyecto no altero el dato de este.');

// Deshacer: null borra el registro y su auditoria.
$r = $svc->registrarPaso(P, $paquete, (int) $pasoId, null, 'test-b1');
$assert(($r['ok'] ?? false) === true, 'Registro: borrar una fecha responde ok.');
$borrado = $svc->pasosDePaquete(P, $paquete);
$assert($borrado[0]['fechaReal'] === null && $borrado[0]['registradoAt'] === null,
    'Registro: deshacer deja el paso como si nunca se hubiera registrado.');

// Y se vuelve a poner, porque la Task 4 necesita un paso con avance.
$svc->registrarPaso(P, $paquete, (int) $pasoId, '2026-04-15', 'test-b1');

// --- El avance sobrevive a un recalculo ---
(new \App\Services\Pdc\PlanFechasService($db))->calcular(P, 'test-b1');
$trasCalculo = $svc->pasosDePaquete(P, $paquete);
$assert($trasCalculo[0]['fechaReal'] === '2026-04-15',
    'Recalculo: la fecha real sobrevive (el upsert de calcular() no lista esa columna). Dio ' . var_export($trasCalculo[0]['fechaReal'], true));
$assert($trasCalculo[0]['registradoPor'] === 'test-b1',
    'Recalculo: y su auditoria tambien.');
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: error fatal `Call to undefined method App\Services\Pdc\SeguimientoService::registrarPaso()`.

- [ ] **Step 3: Implementar `registrarPaso()`**

En `src/Services/Pdc/SeguimientoService.php`, añadir tras `pasosDePaquete()`:

```php
    /**
     * Registra (o borra, con null) la fecha en que ocurrio de verdad un paso.
     *
     * No hay regla de orden entre pasos a proposito. En obra la orden de compra se firma a veces
     * antes de que alguien archive el acta del paso anterior, y bloquear el registro fuera de orden
     * no produce disciplina: produce fechas inventadas para desbloquear la pantalla.
     *
     * @return array{ok: bool, code?: string, mensaje?: string}
     */
    public function registrarPaso(
        int $projectId,
        int $paqueteId,
        int $pasoId,
        ?string $fechaReal,
        string $usuario,
    ): array {
        if ($fechaReal !== null) {
            // Formato estricto: `strtotime` aceptaria '15/04/2026' y lo interpretaria al reves, y esa
            // fecha silenciosamente equivocada no la detecta nadie hasta que la proyeccion sale rara.
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaReal);
            if ($d === false || $d->format('Y-m-d') !== $fechaReal) {
                return ['ok' => false, 'code' => 'FECHA_INVALIDA', 'mensaje' => 'La fecha debe venir como AAAA-MM-DD.'];
            }
        }

        // La terna (proyecto, paquete, paso) se comprueba junta. Que el paso exista en el catalogo no
        // dice nada: lo que hay que garantizar es que ESE paso pertenece al plan de ESE paquete en
        // ESTE proyecto. Sin esto, un paquete_id equivocado escribiria en el plan de otro.
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
            [$projectId, $paqueteId, $pasoId],
        )->fetchColumn();
        if ($existe === false) {
            return [
                'ok' => false,
                'code' => 'PASO_INVALIDO',
                'mensaje' => 'Ese paso no pertenece al plan de este paquete.',
            ];
        }

        // Borrar la fecha borra tambien su auditoria: dejar «lo registro Fulano» sobre una casilla
        // vacia solo genera preguntas sin respuesta.
        $this->db->query(
            'UPDATE pdc_plan_paso
                SET fecha_real = ?,
                    registrado_por = ?,
                    registrado_at = ?
              WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
            [
                $fechaReal,
                $fechaReal === null ? '' : $usuario,
                $fechaReal === null ? null : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                $projectId, $paqueteId, $pasoId,
            ],
        );

        return ['ok' => true];
    }
```

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: todas `PASS:`, `exit=0`.

- [ ] **Step 5: Implementar el controlador**

Crear `src/Controllers/Api/PlanComprasSeguimientoController.php`:

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\SeguimientoService;

/**
 * Seguimiento al plan de compras (PDC v2 / Fase B1).
 *
 * Mismo RBAC que el plan —lectura `lps.paquetes_contratacion.ver`, escritura
 * `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`—: quien puede ver y editar el plan de
 * compras es exactamente quien opera su seguimiento, y un permiso propio solo añadiria una matriz
 * mas que alguien tendria que mantener alineada.
 *
 * Sesion garantizada por SessionMiddleware global.
 */
class PlanComprasSeguimientoController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private SeguimientoService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new SeguimientoService($this->db);
    }

    /** GET /plan-compras/api/seguimiento */
    public function resumen(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['resumen' => $this->service->resumen($projectId)]);
    }

    /** GET /plan-compras/api/seguimiento/paquete?paqueteId=N */
    public function paquete(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $paqueteId = filter_var($_GET['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $this->ok(['pasos' => $this->service->pasosDePaquete($projectId, (int) $paqueteId)]);
    }

    /** POST /plan-compras/api/seguimiento/paso  {paqueteId, pasoId, fechaReal} — null deshace el registro */
    public function paso(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();

        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $pasoId = filter_var($body['pasoId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($pasoId === false || $pasoId === null) {
            $this->fail('PASO_INVALIDO', 'pasoId inválido.', 422);
            return;
        }

        // `null` es un valor legitimo —deshacer un registro equivocado—, asi que se distingue de
        // «vino cualquier otra cosa». Una cadena vacia cuenta como null: es lo que manda un campo de
        // fecha que el usuario borro.
        $crudo = $body['fechaReal'] ?? null;
        if ($crudo === null || $crudo === '') {
            $fechaReal = null;
        } elseif (is_string($crudo)) {
            $fechaReal = $crudo;
        } else {
            $this->fail('FECHA_INVALIDA', 'fechaReal debe ser una fecha AAAA-MM-DD o null.', 422);
            return;
        }

        $r = $this->service->registrarPaso($projectId, (int) $paqueteId, (int) $pasoId, $fechaReal, $this->usuario());
        if (($r['ok'] ?? false) !== true) {
            $this->fail((string) ($r['code'] ?? 'ERROR'), (string) ($r['mensaje'] ?? 'No se pudo registrar el avance.'), 422);
            return;
        }

        $this->ok(['ok' => true]);
    }

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para ver el seguimiento del plan de compras.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        return $projectId;
    }

    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.editar')) {
            $this->fail('FORBIDDEN', 'No autorizado para registrar avance del plan de compras.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return null;
        }
        return $projectId;
    }

    /** @return array<string, mixed> */
    private function body(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }
}
```

- [ ] **Step 6: Registrar las rutas**

En `public/index.php`, tras la línea `$router->post('/plan-compras/api/plan/pasos', ...)` (la última del bloque del plan), añadir:

```php
// PDC v2 · Fase B1 — Seguimiento. Las rutas con segmento van antes que la desnuda, igual que en el
// bloque del plan, para no depender de como resuelva el router los prefijos.
$router->get('/plan-compras/api/seguimiento/paquete', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'paquete']);
$router->post('/plan-compras/api/seguimiento/paso', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'paso']);
$router->get('/plan-compras/api/seguimiento', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'resumen']);
```

- [ ] **Step 7: Verificar que las rutas existen y exigen sesión**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && for r in "/plan-compras/api/seguimiento" "/plan-compras/api/seguimiento/paquete?paqueteId=1"; do echo -n "$r -> "; curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8081$r"; done
```
Expected: **no** `404`. Un `302` al login o un `401`/`403` es lo correcto sin sesión: prueba que la ruta existe y que el guard actúa. Un `404` significa que el router no la registró.

- [ ] **Step 8: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add src/Services/Pdc/SeguimientoService.php src/Controllers/Api/PlanComprasSeguimientoController.php public/index.php tests/test_pdc_v2_seguimiento.php && git commit -m "feat(pdc): registrar cuando ocurrio de verdad cada paso de contratacion

Tres endpoints con el mismo RBAC del plan: el resumen por paquete, el detalle de un
paquete y el POST que registra o deshace una fecha real. La terna (proyecto, paquete,
paso) se valida junta —que el paso exista en el catalogo no dice que sea de este
plan— y el formato de fecha es estricto, porque strtotime aceptaria 15/04/2026 y lo
leeria al reves sin que nadie se entere. Sin regla de orden entre pasos: en obra no
se cumple, y forzarla solo produce fechas inventadas.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Un reamarre ya no borra el avance

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php` (`limpiarPlanCalculado()`, línea ~963)
- Test: `tests/test_pdc_v2_seguimiento.php`

**Interfaces:**
- Consumes: `registrarPaso()` de la Task 3.
- Produces: nada nuevo. Cambia el invariante de `limpiarPlanCalculado()`: las filas con `fecha_real` sobreviven, con `fecha_inicio`/`fecha_fin` en `NULL`.

- [ ] **Step 1: Escribir el test que falla**

En `tests/test_pdc_v2_seguimiento.php`, insertar antes de `$assert($fallos === 0, 'Sin fallos');` (después del bloque del recálculo):

```php
// --- El avance sobrevive a un reamarre ---
// Desamarrar invalida el plan: las fechas se calcularon contra un frente que ya no es el suyo. Pero
// una propuesta ya recibida no deja de haberse recibido porque la obra se reprograme, asi que la
// fila con avance se conserva —sin fechas programadas— y el siguiente calculo se las repone.
$plan = new \App\Services\Pdc\PlanFechasService($db);
$amarreOriginal = $db->query(
    'SELECT unique_id FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [P, $paquete],
)->fetchColumn();
$assert($amarreOriginal !== false, 'El paquete de prueba esta amarrado a un frente (precondicion del reamarre).');

$plan->desamarrar(P, $paquete);
$trasDesamarrar = $db->query(
    'SELECT fecha_real, fecha_inicio, fecha_fin, registrado_por FROM pdc_plan_paso
      WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
    [P, $paquete],
)->fetchAll(\PDO::FETCH_ASSOC);
$assert(count($trasDesamarrar) === 1,
    'Reamarre: la fila con avance real sobrevive al desamarre. Quedaron ' . count($trasDesamarrar));
$assert(($trasDesamarrar[0]['fecha_real'] ?? null) === '2026-04-15',
    'Reamarre: con su fecha real intacta.');
$assert(($trasDesamarrar[0]['registrado_por'] ?? '') === 'test-b1',
    'Reamarre: y con su auditoria intacta.');
$assert($trasDesamarrar[0]['fecha_inicio'] === null && $trasDesamarrar[0]['fecha_fin'] === null,
    'Reamarre: lo programado si se limpia — se calculo contra un frente que ya no vale.');

$sinAvance = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NULL',
    [P, $paquete],
)->fetchColumn();
$assert($sinAvance === 0,
    'Reamarre: las filas SIN avance si se borran, como siempre. Quedaron ' . $sinAvance);

// Se devuelve al estado anterior y se recalcula: las programadas vuelven, el avance sigue ahi.
$plan->amarrar(P, $paquete, (int) $amarreOriginal, 'test-b1');
$plan->calcular(P, 'test-b1');
$restaurado = $svc->pasosDePaquete(P, $paquete);
$assert(count($restaurado) === count($detalle),
    'Reamarre: recalcular repone todos los pasos. Dio ' . count($restaurado) . ' de ' . count($detalle));
$assert($restaurado[0]['fechaReal'] === '2026-04-15',
    'Reamarre: el avance sigue ahi tras recalcular.');
$assert($restaurado[0]['fechaFin'] !== null,
    'Reamarre: y las fechas programadas volvieron.');
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: FAIL en `Reamarre: la fila con avance real sobrevive al desamarre. Quedaron 0` — hoy el `DELETE` se lleva todas. Exit 1.

- [ ] **Step 3: Implementar**

En `src/Services/Pdc/PlanFechasService.php`, dentro de `limpiarPlanCalculado()`, sustituir el `DELETE FROM pdc_plan_paso` (el primero del método) por:

```php
        // Las filas SIN avance se borran: son solo fechas calculadas contra un frente que ya no vale.
        $this->db->query(
            'DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NULL',
            [$projectId, $paqueteId],
        );
        // Las que SI llevan avance se conservan y se les vacian las fechas programadas. Una propuesta
        // ya recibida no deja de haberse recibido porque la obra se reprograme, y borrar la fila se
        // llevaria por delante trabajo que ocurrio de verdad — la deuda que A4 dejo anotada aqui.
        // Quedan con fecha_real y sin programadas, que es exactamente lo que significan: «esto se
        // hizo, pero el plan todavia no se ha recalculado». El siguiente calcular() las repone.
        $this->db->query(
            'UPDATE pdc_plan_paso SET fecha_inicio = NULL, fecha_fin = NULL
              WHERE project_id = ? AND paquete_id = ?',
            [$projectId, $paqueteId],
        );
```

Y borrar el comentario «Pendiente para B1» que quedó dentro de `amarrar()` describiendo esta deuda: ya no es pendiente.

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_seguimiento.php; echo "exit=$?"
```
Expected: todas `PASS:`, `exit=0`.

- [ ] **Step 5: Confirmar que el plan de fechas no se rompió**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_pdc_v2_plan_fechas.php; echo "plan_fechas exit=$?"
```
Expected: `exit=0`. Este test cubre desamarrar y reamarrar; es el que detectaría un daño colateral en `limpiarPlanCalculado()`.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_seguimiento.php && git commit -m "fix(pdc): reprogramar la obra ya no borra lo que si se hizo

limpiarPlanCalculado() borraba todos los pasos del paquete. Con el avance de B1
colgando de esas filas, eso destruia trabajo real: una propuesta ya recibida no deja
de haberse recibido porque el paquete cambie de frente. Ahora se borran solo las filas
sin avance y a las que lo tienen se les vacian las fechas programadas, que si dejaron
de valer. Queda saldada la deuda que A4 dejo anotada en amarrar().

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: La aritmética del seguimiento en la SPA

**Files:**
- Create: `pdc-app/src/lib/seguimiento.ts`
- Create: `pdc-app/src/lib/seguimiento.test.ts`
- Modify: `pdc-app/src/lib/types.ts` (añadir al final del bloque de tipos del plan)

**Interfaces:**
- Consumes: el contrato de la Task 3.
- Produces:
  - Tipos `FilaSeguimiento`, `PasoSeguimiento`, `FiltrosSeguimiento` en `types.ts`.
  - `etiquetaEstado(estado: string): string`
  - `etiquetaDesfaseDias(dias: number | null): string`
  - `filtrarSeguimiento(filas: FilaSeguimiento[], f: FiltrosSeguimiento, usuarioId: number | null): FilaSeguimiento[]`
  - `frentesDeSeguimiento(filas: FilaSeguimiento[]): string[]`

- [ ] **Step 1: Añadir los tipos**

En `pdc-app/src/lib/types.ts`, al final del bloque de tipos del plan:

```ts
// --- PDC v2 · Fase B1 (Seguimiento) ---

/** Una fila de `GET /plan-compras/api/seguimiento`: el estado de un paquete de un vistazo. */
export type FilaSeguimiento = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  responsableUserId: number | null
  responsableNombre: string
  responsableHuerfano: boolean
  pasoActual: string
  cumplidos: number
  total: number
  estado: 'sin_empezar' | 'en_curso' | 'terminado'
  atrasado: boolean
  finProgramado: string | null
  finProyectado: string
}

/**
 * Un paso en el panel de detalle, con sus tres fechas.
 *
 * `fechaInicio`/`fechaFin` en null significan «este paso lleva avance pero el plan aun no se ha
 * recalculado tras un reamarre» — no es un error, y la pantalla lo muestra tal cual.
 */
export type PasoSeguimiento = {
  pasoId: number | null
  orden: number
  paso: string
  dias: number
  fechaInicio: string | null
  fechaFin: string | null
  fechaReal: string | null
  proyectadoInicio: string
  proyectadoFin: string
  desfaseDias: number | null
  registradoPor: string
  registradoAt: string | null
}

/** Los cuatro filtros de la lista. `''` y `false` significan «no filtrar por esto». */
export type FiltrosSeguimiento = {
  soloMios: boolean
  frente: string
  estado: '' | 'sin_empezar' | 'en_curso' | 'terminado'
  soloAtrasados: boolean
}
```

- [ ] **Step 2: Escribir los tests que fallan**

Crear `pdc-app/src/lib/seguimiento.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { etiquetaDesfaseDias, etiquetaEstado, filtrarSeguimiento, frentesDeSeguimiento } from './seguimiento'
import type { FilaSeguimiento, FiltrosSeguimiento } from './types'

const fila = (over: Partial<FilaSeguimiento>): FilaSeguimiento => ({
  paqueteId: 1, nombre: 'Paquete', frenteNombre: 'ESTRUCTURA',
  responsableUserId: null, responsableNombre: '', responsableHuerfano: false,
  pasoActual: 'Cotizacion', cumplidos: 0, total: 7, estado: 'sin_empezar',
  atrasado: false, finProgramado: '2026-05-01', finProyectado: '2026-05-01',
  ...over,
})

const SIN_FILTRO: FiltrosSeguimiento = { soloMios: false, frente: '', estado: '', soloAtrasados: false }

describe('etiquetaEstado', () => {
  it('traduce los tres estados a algo legible', () => {
    expect(etiquetaEstado('sin_empezar')).toBe('Sin empezar')
    expect(etiquetaEstado('en_curso')).toBe('En curso')
    expect(etiquetaEstado('terminado')).toBe('Terminado')
  })

  it('un estado desconocido se muestra tal cual en vez de desaparecer', () => {
    expect(etiquetaEstado('otro')).toBe('otro')
  })
})

describe('etiquetaDesfaseDias', () => {
  it('sin desfase medible no dice nada', () => {
    expect(etiquetaDesfaseDias(null)).toBe('')
  })

  it('puntual se dice con palabras, no con un cero', () => {
    expect(etiquetaDesfaseDias(0)).toBe('A tiempo')
  })

  it('tarde y temprano se distinguen sin leer el signo', () => {
    expect(etiquetaDesfaseDias(10)).toBe('10 días tarde')
    expect(etiquetaDesfaseDias(1)).toBe('1 día tarde')
    expect(etiquetaDesfaseDias(-3)).toBe('3 días antes')
  })
})

describe('filtrarSeguimiento', () => {
  const filas = [
    fila({ paqueteId: 1, responsableUserId: 7, frenteNombre: 'ESTRUCTURA', estado: 'sin_empezar', atrasado: false }),
    fila({ paqueteId: 2, responsableUserId: 9, frenteNombre: 'ACABADOS', estado: 'en_curso', atrasado: true }),
    fila({ paqueteId: 3, responsableUserId: 7, frenteNombre: 'ACABADOS', estado: 'terminado', atrasado: false }),
  ]

  it('sin filtros devuelve todo', () => {
    expect(filtrarSeguimiento(filas, SIN_FILTRO, 7)).toHaveLength(3)
  })

  it('«mis paquetes» usa el usuario logueado', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([1, 3])
  })

  it('«mis paquetes» sin usuario conocido no devuelve nada, en vez de devolver todo', () => {
    expect(filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true }, null)).toHaveLength(0)
  })

  it('filtra por frente', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, frente: 'ACABADOS' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([2, 3])
  })

  it('filtra por estado', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, estado: 'terminado' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([3])
  })

  it('filtra por atraso', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloAtrasados: true }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([2])
  })

  it('los filtros se acumulan', () => {
    const r = filtrarSeguimiento(filas, { ...SIN_FILTRO, soloMios: true, frente: 'ACABADOS' }, 7)
    expect(r.map((f) => f.paqueteId)).toEqual([3])
  })
})

describe('frentesDeSeguimiento', () => {
  it('lista los frentes presentes, sin repetir y ordenados', () => {
    const filas = [fila({ frenteNombre: 'ESTRUCTURA' }), fila({ frenteNombre: 'ACABADOS' }), fila({ frenteNombre: 'ESTRUCTURA' })]
    expect(frentesDeSeguimiento(filas)).toEqual(['ACABADOS', 'ESTRUCTURA'])
  })

  it('ignora los vacios: un frente sin nombre no es una opcion que se pueda elegir', () => {
    expect(frentesDeSeguimiento([fila({ frenteNombre: '' })])).toEqual([])
  })
})
```

- [ ] **Step 3: Correr los tests y verlos fallar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1/pdc-app" && npx vitest run seguimiento
```
Expected: FAIL — no se puede resolver `./seguimiento`.

- [ ] **Step 4: Implementar**

Crear `pdc-app/src/lib/seguimiento.ts`:

```ts
import type { FilaSeguimiento, FiltrosSeguimiento } from './types'

const ESTADOS: Record<string, string> = {
  sin_empezar: 'Sin empezar',
  en_curso: 'En curso',
  terminado: 'Terminado',
}

/** Un estado que no conocemos se muestra crudo: desaparecer de la pantalla es peor que verse raro. */
export function etiquetaEstado(estado: string): string {
  return ESTADOS[estado] ?? estado
}

/**
 * El desfase en palabras. `null` es «no hay contra que medir» (paso pendiente, o paso con avance
 * cuyo plan aun no se ha recalculado) y no dice nada; cero se dice «A tiempo», porque un «0 días»
 * suelto se lee como si faltara el dato.
 */
export function etiquetaDesfaseDias(dias: number | null): string {
  if (dias === null) return ''
  if (dias === 0) return 'A tiempo'
  const n = Math.abs(dias)
  const unidad = n === 1 ? 'día' : 'días'
  return dias > 0 ? `${n} ${unidad} tarde` : `${n} ${unidad} antes`
}

/**
 * Los cuatro filtros de la lista, acumulativos.
 *
 * «Mis paquetes» sin usuario conocido devuelve vacio, no todo: si no sabemos quien eres, mostrar la
 * obra entera bajo una etiqueta que dice «mios» es mentir sobre lo que se esta viendo.
 */
export function filtrarSeguimiento(
  filas: FilaSeguimiento[],
  f: FiltrosSeguimiento,
  usuarioId: number | null,
): FilaSeguimiento[] {
  return filas.filter((fila) => {
    if (f.soloMios && (usuarioId === null || fila.responsableUserId !== usuarioId)) return false
    if (f.frente !== '' && fila.frenteNombre !== f.frente) return false
    if (f.estado !== '' && fila.estado !== f.estado) return false
    if (f.soloAtrasados && !fila.atrasado) return false
    return true
  })
}

/** Los frentes que de verdad aparecen en los datos, para poblar el desplegable sin inventar opciones. */
export function frentesDeSeguimiento(filas: FilaSeguimiento[]): string[] {
  return [...new Set(filas.map((f) => f.frenteNombre).filter((n) => n !== ''))].sort()
}
```

- [ ] **Step 5: Correr los tests y verlos pasar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1/pdc-app" && npx vitest run
```
Expected: toda la suite en verde, incluidos los 14 tests nuevos.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add pdc-app/src/lib/seguimiento.ts pdc-app/src/lib/seguimiento.test.ts pdc-app/src/lib/types.ts && git commit -m "feat(plan): la aritmetica del seguimiento, fuera del componente

Tipos del contrato de B1 y los helpers que la pantalla necesita: estado y desfase en
palabras (cero se dice «A tiempo», porque un 0 suelto se lee como dato faltante) y los
cuatro filtros acumulativos. «Mis paquetes» sin usuario conocido devuelve vacio y no
todo: mostrar la obra entera bajo esa etiqueta seria mentir sobre lo que se ve.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: La pantalla de Seguimiento

**Files:**
- Create: `pdc-app/src/pages/Seguimiento.tsx`
- Modify: `pdc-app/src/App.tsx` (import, ruta, y retirar el `<span>` deshabilitado)
- Modify: `pdc-app/src/lib/navegacion.ts` (la entrada nueva)

**Interfaces:**
- Consumes: los helpers y tipos de la Task 5, y los tres endpoints de la Task 3.
- Produces: nada que consuman tasks posteriores.

- [ ] **Step 1: Añadir la entrada de navegación**

En `pdc-app/src/lib/navegacion.ts`, añadir al final de `PANTALLAS`:

```ts
  { ruta: '/seguimiento/avance', etiqueta: 'Seguimiento' },
```

- [ ] **Step 2: Escribir la pantalla**

Crear `pdc-app/src/pages/Seguimiento.tsx`:

```tsx
import { useCallback, useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowStyleModule } from 'ag-grid-community'
import type { ColDef, RowClickedEvent } from 'ag-grid-community'
import { MODULOS_TABLA, autoSizeStrategy, columnaTexto, defaultColDef, pdcTheme, vacioTabla } from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { getBootstrap } from '../lib/bootstrap'
import { etiquetaDesfaseDias, etiquetaEstado, filtrarSeguimiento, frentesDeSeguimiento } from '../lib/seguimiento'
import type { FilaSeguimiento, FiltrosSeguimiento, PasoSeguimiento } from '../lib/types'
import { plural } from '../lib/texto'

// Solo lectura en la grilla: el avance se registra en el panel de detalle, no en la celda. Por eso
// no se registra ningun modulo de edicion aqui.
ModuleRegistry.registerModules([...MODULOS_TABLA, CellStyleModule, RowStyleModule])

const mensajeError = (e: unknown) => (e instanceof Error ? e.message : String(e))

const SIN_FILTRO: FiltrosSeguimiento = { soloMios: false, frente: '', estado: '', soloAtrasados: false }

export default function Seguimiento() {
  const [filas, setFilas] = useState<FilaSeguimiento[]>([])
  const [filtros, setFiltros] = useState<FiltrosSeguimiento>(SIN_FILTRO)
  const [usuarioId, setUsuarioId] = useState<number | null>(null)
  const [abierto, setAbierto] = useState<FilaSeguimiento | null>(null)
  const [pasos, setPasos] = useState<PasoSeguimiento[]>([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')

  const cargar = useCallback(async () => {
    setCargando(true)
    try {
      const d = await apiGet<{ resumen: FilaSeguimiento[] }>('/plan-compras/api/seguimiento')
      setFilas(d.resumen)
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    } finally {
      setCargando(false)
    }
  }, [])

  useEffect(() => {
    void cargar()
    // El id del usuario sale del bootstrap del modulo: es lo que hace posible el filtro «mis
    // paquetes» sin pedirle al servidor una consulta distinta.
    void getBootstrap().then((b) => setUsuarioId(b.usuarioId ?? null)).catch(() => setUsuarioId(null))
  }, [cargar])

  const abrir = useCallback(async (fila: FilaSeguimiento) => {
    setAbierto(fila)
    setPasos([])
    try {
      const d = await apiGet<{ pasos: PasoSeguimiento[] }>(
        `/plan-compras/api/seguimiento/paquete?paqueteId=${fila.paqueteId}`,
      )
      setPasos(d.pasos)
    } catch (e) {
      setError(mensajeError(e))
    }
  }, [])

  const registrar = useCallback(async (paso: PasoSeguimiento, valor: string) => {
    if (!abierto || paso.pasoId === null) return
    try {
      await apiPost('/plan-compras/api/seguimiento/paso', {
        paqueteId: abierto.paqueteId,
        pasoId: paso.pasoId,
        fechaReal: valor === '' ? null : valor,
      })
      // Se recarga en vez de mutar en local: la proyeccion de TODOS los pasos siguientes depende de
      // este cambio, y recalcularla aqui seria duplicar en el cliente la aritmetica del servidor.
      const d = await apiGet<{ pasos: PasoSeguimiento[] }>(
        `/plan-compras/api/seguimiento/paquete?paqueteId=${abierto.paqueteId}`,
      )
      setPasos(d.pasos)
      await cargar()
      setError('')
    } catch (e) {
      setError(e instanceof PdcApiError ? e.message : mensajeError(e))
    }
  }, [abierto, cargar])

  const visibles = useMemo(
    () => filtrarSeguimiento(filas, filtros, usuarioId),
    [filas, filtros, usuarioId],
  )
  const frentes = useMemo(() => frentesDeSeguimiento(filas), [filas])

  const cols = useMemo<ColDef<FilaSeguimiento>[]>(() => [
    { ...columnaTexto, headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 240 },
    { ...columnaTexto, headerName: 'Frente', field: 'frenteNombre', flex: 1, minWidth: 160 },
    {
      ...columnaTexto, headerName: 'Responsable', field: 'responsableNombre', flex: 1, minWidth: 180,
      valueFormatter: (p) => {
        const f = p.data
        if (!f || f.responsableUserId === null) return '— sin asignar —'
        return f.responsableHuerfano ? `${f.responsableNombre} (ya no está en el proyecto)` : f.responsableNombre
      },
    },
    { ...columnaTexto, headerName: 'Paso actual', field: 'pasoActual', flex: 1, minWidth: 180 },
    {
      headerName: 'Avance', field: 'cumplidos', width: 110,
      valueFormatter: (p) => (p.data ? `${p.data.cumplidos} / ${p.data.total}` : ''),
    },
    {
      ...columnaTexto, headerName: 'Estado', field: 'estado', width: 130,
      valueFormatter: (p) => etiquetaEstado(String(p.value ?? '')),
    },
    {
      headerName: 'Atraso', field: 'atrasado', width: 100,
      valueFormatter: (p) => (p.value === true ? 'Sí' : ''),
    },
    { ...columnaTexto, headerName: 'Fin programado', field: 'finProgramado', width: 150 },
    { ...columnaTexto, headerName: 'Fin proyectado', field: 'finProyectado', width: 150 },
  ], [])

  return (
    <section className="pdc-pagina">
      <header className="pdc-encabezado">
        <h1>Seguimiento del plan de compras</h1>
        <p className="pdc-sub">
          {plural(visibles.length, 'paquete', 'paquetes')} de {filas.length}. Haz clic en una fila para
          registrar cuándo ocurrió cada paso.
        </p>
      </header>

      {error !== '' && <p className="pdc-error" role="alert">{error}</p>}

      <div className="pdc-filtros">
        <label>
          <input
            type="checkbox" checked={filtros.soloMios}
            onChange={(e) => setFiltros((f) => ({ ...f, soloMios: e.target.checked }))}
          />{' '}
          Mis paquetes
        </label>
        <label>
          Frente{' '}
          <select value={filtros.frente} onChange={(e) => setFiltros((f) => ({ ...f, frente: e.target.value }))}>
            <option value="">Todos</option>
            {frentes.map((n) => <option key={n} value={n}>{n}</option>)}
          </select>
        </label>
        <label>
          Estado{' '}
          <select
            value={filtros.estado}
            onChange={(e) => setFiltros((f) => ({ ...f, estado: e.target.value as FiltrosSeguimiento['estado'] }))}
          >
            <option value="">Todos</option>
            <option value="sin_empezar">Sin empezar</option>
            <option value="en_curso">En curso</option>
            <option value="terminado">Terminado</option>
          </select>
        </label>
        <label>
          <input
            type="checkbox" checked={filtros.soloAtrasados}
            onChange={(e) => setFiltros((f) => ({ ...f, soloAtrasados: e.target.checked }))}
          />{' '}
          Solo atrasados
        </label>
      </div>

      <div className="pdc-tabla">
        <AgGridReact<FilaSeguimiento>
          theme={pdcTheme}
          rowData={visibles}
          columnDefs={cols}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          loading={cargando}
          overlayNoRowsTemplate={vacioTabla('No hay paquetes con plan calculado.')}
          onRowClicked={(e: RowClickedEvent<FilaSeguimiento>) => { if (e.data) void abrir(e.data) }}
        />
      </div>

      {abierto && (
        <aside className="pdc-panel" aria-label={`Avance de ${abierto.nombre}`}>
          <header className="pdc-panel-cabecera">
            <h2>{abierto.nombre}</h2>
            <button type="button" onClick={() => setAbierto(null)}>Cerrar</button>
          </header>
          <table className="pdc-panel-tabla">
            <thead>
              <tr>
                <th scope="col">Paso</th>
                <th scope="col">Programado</th>
                <th scope="col">Real</th>
                <th scope="col">Proyectado</th>
                <th scope="col">Desfase</th>
              </tr>
            </thead>
            <tbody>
              {pasos.map((p) => (
                <tr key={`${p.orden}-${p.paso}`}>
                  <th scope="row">{p.paso}</th>
                  {/* Sin fecha programada = el plan aun no se ha recalculado tras un reamarre. Se
                      muestra el hueco con un guion en vez de esconderlo: el usuario tiene que poder
                      distinguirlo de un cero. */}
                  <td>{p.fechaFin ?? '—'}</td>
                  <td>
                    <input
                      type="date"
                      value={p.fechaReal ?? ''}
                      onChange={(e) => void registrar(p, e.target.value)}
                      aria-label={`Fecha real de ${p.paso}`}
                    />
                  </td>
                  <td>{p.proyectadoFin}</td>
                  <td>{etiquetaDesfaseDias(p.desfaseDias)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </aside>
      )}
    </section>
  )
}
```

- [ ] **Step 3: Registrar la ruta**

En `pdc-app/src/App.tsx`: añadir `import Seguimiento from './pages/Seguimiento'` junto a los demás imports de páginas, **borrar** el `<span className="pdc-nav-link pdc-nav-disabled" …>Seguimiento</span>` entero (la pestaña ya la aporta `PANTALLAS`) y añadir dentro de `<Routes>`:

```tsx
          <Route path="/seguimiento/avance" element={<Seguimiento />} />
```

- [ ] **Step 4: Comprobar que compila y que nada se rompió**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1/pdc-app" && npx vitest run && npm run build
```
Expected: suite en verde y build sin errores de TypeScript.

Si `npm run build` se queja de `bootstrap.usuarioId` porque el tipo del bootstrap no lo declara, **no** silenciarlo con `any`: mirar `pdc-app/src/lib/bootstrap.ts`, y si el backend no expone el id del usuario, añadirlo al bootstrap del módulo (`views/` del plan de compras) y a su tipo. El filtro «mis paquetes» depende de ese dato y sin él no funciona.

- [ ] **Step 5: Publicar el bundle**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1/pdc-app" && npm run build && ls -la ../public/pdc-app/
```
Expected: los assets con marca de tiempo de ahora — `public/pdc-app/` es lo que sirve el PHP.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git add pdc-app/src/pages/Seguimiento.tsx pdc-app/src/App.tsx pdc-app/src/lib/navegacion.ts public/pdc-app && git commit -m "feat(plan): la pantalla de Seguimiento estrena el submodulo B

Lista de paquetes con paso actual, avance y atraso, y un panel de detalle donde se
registra la fecha real de cada paso junto a la programada y la proyectada. Tras cada
registro se recarga el detalle en vez de mutar en local: la proyeccion de todos los
pasos siguientes depende del cambio, y recalcularla en el cliente seria duplicar la
aritmetica del servidor. La pestana deja de estar deshabilitada.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: Verificación integrada

**Files:** ninguno (solo verificación).

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: la evidencia que respalda declarar B1 terminada.

- [ ] **Step 1: Los dos gates PHP del módulo**

Run:
```bash
SP=/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad; $SP/php-b1.sh tests/test_pdc_v2_seguimiento.php > /dev/null; echo "seguimiento exit=$?"; $SP/php-b1.sh tests/test_pdc_v2_plan_fechas.php > /dev/null; echo "plan_fechas exit=$?"
```
Expected: los dos `exit=0`. (`test_pdc_v2_brecha_daporto.php` queda fuera: rojo preexistente y ambiental, ver Global Constraints.)

- [ ] **Step 2: Los gates de arquitectura de datos**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_global_table_safety.php > /dev/null; echo "safety exit=$?"; /private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh tests/test_global_table_reconciliation.php > /dev/null; echo "reconciliation exit=$?"
```
Expected: los dos `exit=0`. Se añadieron columnas a una tabla del esquema compartido; estos son los gates que lo vigilan.

- [ ] **Step 3: Análisis estático**

Run:
```bash
/private/tmp/claude-501/-Volumes-Crucial-X6-Developer-lps-aia/b1fd4c10-20f9-45e5-8d66-71f6b61c0f9e/scratchpad/php-b1.sh vendor/bin/phpstan analyse src admin/src --memory-limit=1G 2>&1 | tail -20
```
Expected: sin errores nuevos en `src/Services/Pdc/SeguimientoService.php` ni en `src/Controllers/Api/PlanComprasSeguimientoController.php`. Si el repo ya tenía errores previos en otros archivos, no se arreglan aquí — se anotan.

- [ ] **Step 4: Los gates de la SPA**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1/pdc-app" && npx vitest run && npm run build
```
Expected: ambos en verde.

- [ ] **Step 5: Verificación en el navegador**

Con las herramientas del navegador integrado: `preview_start` en `http://localhost:8081`, iniciar sesión (credenciales del usuario), seleccionar DAPORTO y navegar a `#/seguimiento/avance`. Viewport **1180×820, dark**. Comprobar:

- La pestaña «Seguimiento» ya no está deshabilitada y la lista carga con paquetes.
- Al hacer clic en una fila se abre el panel con los pasos y sus tres fechas.
- Poner una fecha real: el desfase aparece, la proyectada de los pasos siguientes se corre, y la fila de la lista pasa a «En curso».
- **Recargar la página**: la fecha sigue ahí (es el gate de persistencia de AGENTS.md).
- Los cuatro filtros reducen la lista.
- Revisar la consola: sin errores.

Nota de entorno: la sesión del panel del navegador se cae a los ~60-90 s (limitación del panel, no de la app). Si ocurre, volver a iniciar sesión y continuar; no es un fallo de la aplicación.

- [ ] **Step 6: Dejar DAPORTO en un estado explicable**

El test deja registrada una fecha real de prueba (`registrado_por = 'test-b1'`). Está autorizado dejar DAPORTO alterado, pero conviene saber **qué** quedó.

Run:
```bash
docker exec -i last-planner-aia-db-1 sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'-e "
SELECT COUNT(*) pasos_con_avance, SUM(registrado_por LIKE \"test-b1%\") de_pruebas
  FROM pdc_plan_paso WHERE project_id = 73 AND fecha_real IS NOT NULL;
"'
```
Expected: un recuento pequeño y `de_pruebas` igual a `pasos_con_avance`. Anotarlo en el resumen final: es avance sintético, no de obra.

- [ ] **Step 7: Revisar el árbol**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-b1" && git status --short && git log --oneline -8
```
Expected: sin cambios sin commitear (salvo lo que ya venía de antes) y los siete commits de B1 en su sitio.

---

## Riesgos y preguntas abiertas

- **`bootstrap.usuarioId` puede no existir.** El filtro «mis paquetes» lo necesita. Si el bootstrap del módulo no lo expone, hay que añadirlo (Task 6, Step 4). Es el único punto del plan que puede exigir tocar PHP fuera de lo previsto.
- **`fecha_inicio`/`fecha_fin` pasan a admitir `NULL`.** Cualquier consumidor que asumiera lo contrario ahora puede recibir `null`. Los conocidos son `PlanFechasService::plan()` —que castea a `string`, y un `null` se vuelve `''`, visible pero no roto— y el propio `SeguimientoService`. Si aparece un tercero, hay que revisarlo.
- **La versión activa del presupuesto de DAPORTO está marcada `obsoleta = 1`** (id 180). No bloquea a B1 —el plan se calcula igual—, pero significa que las cifras de valor de esa versión son las ambiguas que documenta `docs/pdc-v2.md`. No se toca aquí.
- **La proyección se calcula dos veces**, en PHP para el contrato y en TypeScript nunca (la SPA recibe la del servidor). Se decidió así: un solo dueño de la aritmética. Si algún día la pantalla necesita proyectar sin ir al servidor, ese es el momento de duplicarla, no antes.
