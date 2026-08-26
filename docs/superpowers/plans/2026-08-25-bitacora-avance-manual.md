---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-25
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-25-bitacora-avance-manual.md
resumen: Que WeeklyRealProgressCarryoverService deje de adivinar si un avance lo puso una persona, consultando una bitácora de ediciones manuales en vez de deducirlo…
---

# Bitácora del avance editado a mano — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que `WeeklyRealProgressCarryoverService` deje de adivinar si un avance lo puso una persona, consultando una bitácora de ediciones manuales en vez de deducirlo del propio valor.

**Architecture:** Una tabla nueva de nombre fijo (`pg_avance_edicion_manual`), un servicio que la escribe desde `GeneralApiController::update()` con el patrón capturar-al-entrar / registrar-al-salir, y una consulta nueva en el arrastre que solo entra en juego en el caso ambiguo. No se toca el comportamiento de ninguna pantalla.

**Tech Stack:** PHP 8.3, MySQL 8, PHPUnit 12 (grupos por nivel), Docker Compose. Sin framework: routing FastRoute, acceso a datos por `Database` (PDO, prepared statements).

**Spec:** `docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md`

## Global Constraints

- **Alcance cerrado:** solo `Ejecutado`, solo `programa_consolidado`, solo `GeneralApiController::update()`. Cualquier otro campo, tabla o controlador está explícitamente fuera (ver la tabla «Qué se descartó» del spec).
- **La bitácora se consulta SOLO en el caso ambiguo del arrastre, nunca antes.** Moverla fuera de ese punto reintroduce el defecto original. La Task 5 existe para impedirlo.
- **Sin transacción envolvente en `update()`:** no la tiene hoy y no se agrega. Si el `INSERT` de bitácora falla, se registra en `error_log` y no se traga en silencio.
- **Tolerancia de comparación numérica: `0.001`**, la misma que ya usa `WeeklyRealProgressCarryoverService`.
- **Tabla de nombre fijo**, nunca por `TableResolver` — sigue la convención de `general_auditoria_acciones`.
- Usuario: `$_SESSION['usuario']`. En `update()` ya está resuelto en la variable `$auditUser`.
- Los tests de base de datos declaran `#[Group('db')]`; sin grupo, `scripts/run-php-tests.php` aborta.
- Comandos dentro del contenedor efímero, sin tocar el compartido:
  `LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app <cmd>`

---

### Task 1: La tabla

**Files:**
- Create: `database/migrations/20260826_pg_avance_edicion_manual.sql`

**Interfaces:**
- Consumes: nada.
- Produces: tabla `pg_avance_edicion_manual` con columnas `id`, `project_id`, `Semana`, `unique_id`, `valor_anterior`, `valor_nuevo`, `usuario`, `fecha`.

- [ ] **Step 1: Escribir la migración**

```sql
-- 2026-08-26 — Bitácora del avance editado a mano en Programa General.
--
-- Existe para que `WeeklyRealProgressCarryoverService` deje de adivinar, en su caso ambiguo, si
-- un valor de `Ejecutado` lo puso una persona o es residuo del defecto corregido el 2026-08-25
-- (commit c1e3365e). Sin esta evidencia, una edición real y un residuo producen el mismo dato.
--
-- Nombre fijo, nunca por TableResolver: sigue la convención de `general_auditoria_acciones`, la
-- otra tabla de auditoría del repo, que `Database::logActivity` referencia por nombre directo.
--
-- Reversión:  DROP TABLE pg_avance_edicion_manual;

CREATE TABLE IF NOT EXISTS `pg_avance_edicion_manual` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `Semana` int NOT NULL,
  `unique_id` int NOT NULL,
  `valor_anterior` decimal(12,6) DEFAULT NULL,
  `valor_nuevo` decimal(12,6) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`project_id`, `Semana`, `unique_id`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ediciones manuales de Ejecutado en programa_consolidado; la consulta el arrastre';
```

- [ ] **Step 2: Aplicarla en local**

Run:
```bash
cd /Users/felipebenitez/Developer/lps-aia && set -a && . ./.env && set +a && \
docker compose exec -T db sh -c "mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" $DB_NAME" \
  < .claude/worktrees/<worktree>/database/migrations/20260826_pg_avance_edicion_manual.sql
```
Expected: sin salida (éxito silencioso de MySQL).

- [ ] **Step 3: Verificar que existe**

Run:
```bash
cd /Users/felipebenitez/Developer/lps-aia && set -a && . ./.env && set +a && \
printf "SELECT column_name FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='pg_avance_edicion_manual' ORDER BY ordinal_position;\n" \
| docker compose exec -T db sh -c "mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" $DB_NAME -N --batch"
```
Expected: las 8 columnas, en orden.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/20260826_pg_avance_edicion_manual.sql
git commit -m "feat(pg): tabla de bitacora del avance editado a mano"
```

---

### Task 2: El servicio que escribe

**Files:**
- Create: `src/Services/PgAvanceEdicionManualService.php`
- Test: `tests/unit/PgAvanceEdicionManualTest.php`

**Interfaces:**
- Consumes: tabla de Task 1; `App\Core\Lps\LpsService::toFloat($valor, $default)`.
- Produces:
  - `capturarAvancePrevio(int $projectId, int $semana, int $uniqueId): array` → `['Ejecutado' => ?float, 'programaAnteriorAsociar' => ?string]`
  - `registrarSiCambio(int $projectId, int $semana, int $uniqueId, array $previo, string $usuario, bool $huboHerencia): bool` → `true` si insertó fila.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PgAvanceEdicionManualService;
use Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre la bitacora de ediciones manuales del avance en Programa General.
 *
 * Existe porque `WeeklyRealProgressCarryoverService` no puede distinguir, en su caso ambiguo,
 * una edicion real del residente de un residuo del defecto corregido el 2026-08-25. La bitacora
 * le da esa evidencia. Spec:
 * docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md
 */
#[Group('db')]
final class PgAvanceEdicionManualTest extends TestCase
{
    private const PROJECT_ID = 990074;
    private const UID = 1;
    private const SEMANA = 2;

    private Database $db;
    private PgAvanceEdicionManualService $servicio;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->servicio = new PgAvanceEdicionManualService($this->db);
        $this->limpiar();

        $this->db->query(
            "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo)
             VALUES (?, 'Bitacora Test', 'test_bitacora_tmp', 'QA', 1)",
            [self::PROJECT_ID],
        );
        $this->db->query(
            "INSERT INTO semanas_activas (Id, project_id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
             VALUES (?, ?, ?, '2026-08-17', '2026-08-23')",
            [990100, self::PROJECT_ID, self::SEMANA],
        );
        $this->db->query(
            "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad)
             VALUES (?, ?, ?, 'Campamentos')",
            [self::PROJECT_ID, self::UID, self::UID],
        );
        $this->db->query(
            "INSERT INTO programa_consolidado
                (project_id, Consecutivo, row_id, Semana, unique_id, Consecutivo_en_Programa,
                 Actividad, Titulo, Ejecutado, unidad, programaAnteriorAsociar)
             VALUES (?, 3001, 3001, ?, ?, ?, 'Campamentos', 0, 0.7, '%', '*No Asociada*')",
            [self::PROJECT_ID, self::SEMANA, self::UID, self::UID],
        );
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    private function limpiar(): void
    {
        foreach (['pg_avance_edicion_manual', 'programa_consolidado', 'programa', 'semanas_activas'] as $t) {
            $this->db->query("DELETE FROM {$t} WHERE project_id = ?", [self::PROJECT_ID]);
        }
        $this->db->query("DELETE FROM general_proyectos_procesos WHERE Id = ?", [self::PROJECT_ID]);
    }

    private function filasEnBitacora(): array
    {
        return $this->db->query(
            "SELECT valor_anterior, valor_nuevo, usuario FROM pg_avance_edicion_manual
             WHERE project_id = ? AND Semana = ? AND unique_id = ? ORDER BY id",
            [self::PROJECT_ID, self::SEMANA, self::UID],
        )->fetchAll();
    }

    private function ponerAvance(float $valor): void
    {
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = ? WHERE project_id = ? AND Semana = ? AND unique_id = ?",
            [$valor, self::PROJECT_ID, self::SEMANA, self::UID],
        );
    }

    public function testRegistraUnaEdicionDirecta(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.85);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', false);

        $this->assertTrue($inserto);
        $filas = $this->filasEnBitacora();
        $this->assertCount(1, $filas, 'una edicion deja exactamente una fila');
        $this->assertEqualsWithDelta(0.7, (float) $filas[0]['valor_anterior'], 0.001);
        $this->assertEqualsWithDelta(0.85, (float) $filas[0]['valor_nuevo'], 0.001);
        $this->assertSame('test.A', $filas[0]['usuario']);
    }

    public function testNoRegistraSiElValorNoCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.7);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', false);

        $this->assertFalse($inserto);
        $this->assertCount(0, $this->filasEnBitacora(), 'guardar sin cambiar no es una edicion');
    }

    /**
     * El residente vino a corregir otra cosa; la herencia le reemplazo el avance sin que lo
     * pidiera. No es una decision suya sobre ese numero, asi que no se firma.
     */
    public function testNoRegistraLaHerenciaSiLaAsociacionNoCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.55);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', true);

        $this->assertFalse($inserto);
        $this->assertCount(0, $this->filasEnBitacora());
    }

    /**
     * El residente asocio la actividad a una anterior: decidio traer ese avance. Si se firma.
     */
    public function testRegistraLaHerenciaSiLaAsociacionCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = 0.55, programaAnteriorAsociar = 'Campamentos semana 1'
             WHERE project_id = ? AND Semana = ? AND unique_id = ?",
            [self::PROJECT_ID, self::SEMANA, self::UID],
        );
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', true);

        $this->assertTrue($inserto);
        $filas = $this->filasEnBitacora();
        $this->assertCount(1, $filas);
        $this->assertEqualsWithDelta(0.55, (float) $filas[0]['valor_nuevo'], 0.001);
    }
}
```

- [ ] **Step 2: Correr el test y ver que falla**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app vendor/bin/phpunit tests/unit/PgAvanceEdicionManualTest.php
```
Expected: FAIL — `Class "App\Services\PgAvanceEdicionManualService" not found`.

- [ ] **Step 3: Escribir el servicio**

```php
<?php

namespace App\Services;

use App\Core\Lps\LpsService;

/**
 * Bitacora de ediciones manuales del avance (`Ejecutado`) en Programa General.
 *
 * Existe para que `WeeklyRealProgressCarryoverService` deje de adivinar en su caso ambiguo: sin
 * evidencia de quien escribio un valor, una edicion real del residente y un residuo del defecto
 * corregido el 2026-08-25 producen exactamente el mismo dato.
 *
 * Se usa en dos tiempos alrededor de la logica que ya existe, en vez de reemplazarla:
 * `GeneralApiController::update()` ejecuta hasta dos UPDATE por peticion —el que guarda lo
 * tecleado y el de herencia que lo reemplaza— y solo comparando el estado final contra el inicial
 * se registra lo que de verdad quedo.
 */
class PgAvanceEdicionManualService
{
    private const TOLERANCIA = 0.001;

    private $db;
    private LpsService $lpsService;

    public function __construct($db = null, ?LpsService $lpsService = null)
    {
        $this->db = $db ?: \Database::getInstance();
        $this->lpsService = $lpsService ?: new LpsService();
    }

    /**
     * Lee el estado del que se parte, antes de que corra la logica del controlador.
     *
     * @return array{Ejecutado: ?float, programaAnteriorAsociar: ?string}
     */
    public function capturarAvancePrevio(int $projectId, int $semana, int $uniqueId): array
    {
        $fila = $this->db->queryWithProject(
            "SELECT Ejecutado, programaAnteriorAsociar FROM programa_consolidado
             WHERE Semana = ? AND unique_id = ? LIMIT 1",
            [$semana, $uniqueId],
            $projectId,
        )->fetch();

        return [
            'Ejecutado' => $this->lpsService->toFloat($fila['Ejecutado'] ?? null, null),
            'programaAnteriorAsociar' => $fila['programaAnteriorAsociar'] ?? null,
        ];
    }

    /**
     * Compara el estado final contra el capturado y firma si corresponde.
     *
     * `$huboHerencia` lo informa el controlador: es la misma condicion que ya decide si aplica la
     * herencia. Cuando la herencia corrio pero la asociacion no cambio, el reemplazo fue un
     * efecto secundario —el residente venia a corregir otra cosa— y no se firma.
     *
     * @param array{Ejecutado: ?float, programaAnteriorAsociar: ?string} $previo
     */
    public function registrarSiCambio(
        int $projectId,
        int $semana,
        int $uniqueId,
        array $previo,
        string $usuario,
        bool $huboHerencia,
    ): bool {
        $actual = $this->capturarAvancePrevio($projectId, $semana, $uniqueId);

        $antes = $previo['Ejecutado'];
        $despues = $actual['Ejecutado'];

        if ($antes === null && $despues === null) {
            return false;
        }
        if ($antes !== null && $despues !== null && abs($antes - $despues) <= self::TOLERANCIA) {
            return false;
        }

        if ($huboHerencia) {
            $asociacionCambio = trim((string) ($previo['programaAnteriorAsociar'] ?? ''))
                !== trim((string) ($actual['programaAnteriorAsociar'] ?? ''));
            if (!$asociacionCambio) {
                return false;
            }
        }

        try {
            $this->db->queryWithProject(
                "INSERT INTO pg_avance_edicion_manual
                    (project_id, Semana, unique_id, valor_anterior, valor_nuevo, usuario)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$projectId, $semana, $uniqueId, $antes, $despues, $usuario],
                $projectId,
            );
        } catch (\Throwable $e) {
            // Una edicion sin firma es justo lo que este servicio existe para evitar, asi que el
            // fallo no se traga: queda en el log para que se note y se pueda investigar.
            error_log(sprintf(
                '[PgAvanceEdicionManual] No se pudo registrar la edicion | proyecto=%d semana=%d actividad=%d usuario=%s | %s',
                $projectId, $semana, $uniqueId, $usuario, $e->getMessage(),
            ));
            return false;
        }

        return true;
    }
}
```

- [ ] **Step 4: Correr el test y ver que pasa**

Run: el mismo comando del Step 2.
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
git add src/Services/PgAvanceEdicionManualService.php tests/unit/PgAvanceEdicionManualTest.php
git commit -m "feat(pg): servicio que registra las ediciones manuales del avance"
```

---

### Task 3: Conectar el endpoint de edición

**Files:**
- Modify: `src/Controllers/Api/GeneralApiController.php` — `update()`, entre las líneas ~169 (validación de la fila) y ~344 (auditoría final ya existente)
- Test: `tests/test_bitacora_avance_endpoint.php`

**Interfaces:**
- Consumes: `PgAvanceEdicionManualService::capturarAvancePrevio()` y `::registrarSiCambio()` de Task 2.
- Produces: filas en `pg_avance_edicion_manual` cuando alguien edita por `POST /api/general/update`.

- [ ] **Step 1: Escribir el test de extremo a extremo que falla**

Usa el patrón de sesión por la puerta de desarrollo que ya emplean los tests `http` del repo
(`tests/test_semanal_sanear_csrf.php`), sobre el proyecto Da Porto sembrado en local.

```php
<?php
declare(strict_types=1);
// @requiere: http

// Comprueba que editar el avance por el endpoint REAL deja su firma en la bitacora. No basta con
// probar el servicio aislado: el riesgo medido es que `update()` siga escribiendo por el camino
// viejo sin que nadie lo note. Spec:
// docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';
const DB_PREFIX = 'da_porto';
const PROJECT_ID = 73;
const UID = 1473;      // Campamentos, semana 2: acumulado 0.9 en el dump de produccion
const SEMANA = 2;

function sesion(string $usuario): string {
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    [$code] = curlReq($url, null, $jar);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

$db = Database::getInstance();
$db->setProjectContext(PROJECT_ID);

$avanceOriginal = $db->queryWithProject(
    "SELECT Ejecutado FROM programa_consolidado WHERE Semana = ? AND unique_id = ?",
    [SEMANA, UID], PROJECT_ID,
)->fetchColumn();

$db->query("DELETE FROM pg_avance_edicion_manual WHERE project_id = ? AND unique_id = ?", [PROJECT_ID, UID]);

$jar = sesion('test.A');

// El token CSRF que exige `requireProgramaGeneralCsrf()` viaja en la pagina del modulo.
[, $html] = curlReq(BASE . '/programa-general?db=' . urlencode(DB_PREFIX), null, $jar);
preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $html, $m);
$token = $m[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "ABORT: no se pudo leer el token CSRF de /programa-general\n");
    exit(2);
}

$nuevoValor = 55; // 55% — distinto del 90% que trae el dump, para que cuente como cambio
[$code] = curlReq(
    BASE . '/api/general/update?db=' . urlencode(DB_PREFIX) . '&semana_objetivo=' . SEMANA,
    ['unique_id' => UID, 'Id' => UID, 'opcion' => 'editar', 'Ejecutado' => $nuevoValor,
     'unidad' => '%', 'csrf_token' => $token],
    $jar,
);

$filas = $db->query(
    "SELECT valor_anterior, valor_nuevo, usuario FROM pg_avance_edicion_manual
     WHERE project_id = ? AND unique_id = ?",
    [PROJECT_ID, UID],
)->fetchAll();

// Restaurar el dato al valor del dump y limpiar la bitacora, pase o falle.
$db->queryWithProject(
    "UPDATE programa_consolidado SET Ejecutado = ? WHERE Semana = ? AND unique_id = ?",
    [$avanceOriginal, SEMANA, UID], PROJECT_ID,
);
$db->query("DELETE FROM pg_avance_edicion_manual WHERE project_id = ? AND unique_id = ?", [PROJECT_ID, UID]);

$fallos = 0;
if ($code !== 200) { $fallos++; echo "FALLO: el endpoint devolvio $code (esperaba 200)\n"; }
if (count($filas) !== 1) {
    $fallos++;
    echo 'FALLO: se esperaba 1 fila en la bitacora, hay ' . count($filas) . "\n";
} else {
    if (abs((float) $filas[0]['valor_nuevo'] - 0.55) > 0.001) {
        $fallos++;
        echo "FALLO: valor_nuevo es {$filas[0]['valor_nuevo']} (esperaba 0.55)\n";
    }
    if ($filas[0]['usuario'] !== 'test.A') {
        $fallos++;
        echo "FALLO: usuario es {$filas[0]['usuario']} (esperaba test.A)\n";
    }
}

echo $fallos === 0 ? "OK: la edicion por el endpoint real quedo firmada\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
```

> Requisito: Da Porto tiene que estar sembrado en la base local (dump de producción del
> 2026-08-25). Si el proyecto 73 no existe, el test aborta con código 2 en vez de dar un falso
> verde. El nombre exacto del campo del token CSRF debe confirmarse contra la vista de
> `/programa-general` en el Step 2; si difiere, ajústelo antes de seguir.

- [ ] **Step 2: Correr y ver que falla**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app php tests/test_bitacora_avance_endpoint.php
```
Expected: FAIL (0 filas), porque `update()` todavía no llama al servicio.

- [ ] **Step 3: Capturar el estado previo en `update()`**

En `src/Controllers/Api/GeneralApiController.php`, justo después del bloque que valida que la fila existe y no es capítulo (el `if ($existingRow['Titulo'] == 1) { throw ... }`, alrededor de la línea 178), insertar:

```php
            // Bitacora de ediciones manuales del avance: se captura el punto de partida antes de
            // que corran los UPDATE de este metodo. Ver
            // docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md
            $bitacoraService = new \App\Services\PgAvanceEdicionManualService($this->db, $this->lpsService);
            $avancePrevio = $bitacoraService->capturarAvancePrevio($projectId, (int) $semana, (int) $id);
```

- [ ] **Step 4: Registrar al salir, junto a la auditoría que ya existe**

En el mismo archivo, inmediatamente después del `error_log("[PGAudit] FINAL ...")` (alrededor de la línea 344), insertar:

```php
            // Firma de la edicion, si de verdad hubo una. `$huboHerencia` reusa exactamente la
            // condicion que decidio aplicar la herencia mas arriba en este mismo metodo: cuando la
            // herencia reemplazo el avance pero la asociacion no cambio, el residente venia a
            // corregir otra cosa y ese numero no lo decidio el.
            $huboHerencia = !empty($_POST['editarActividadAsociar'])
                && !empty($actividadAsociar)
                && $actividadAsociar !== '*No Asociada*';
            $bitacoraService->registrarSiCambio(
                $projectId,
                (int) $semana,
                (int) $id,
                $avancePrevio,
                $auditUser,
                $huboHerencia,
            );
```

- [ ] **Step 5: Correr el test y ver que pasa**

Run: el mismo comando del Step 2.
Expected: `PASS: la edicion quedo registrada en la bitacora`.

- [ ] **Step 6: Correr la suite completa para descartar regresiones**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro
```
Expected: todos en verde, 0 fallados.

- [ ] **Step 7: Commit**

```bash
git add src/Controllers/Api/GeneralApiController.php tests/test_bitacora_avance_endpoint.php
git commit -m "feat(pg): update() firma las ediciones manuales del avance"
```

---

### Task 4: El arrastre consulta la bitácora

**Files:**
- Modify: `src/Services/WeeklyRealProgressCarryoverService.php` — el bloque `else` del criterio de preservación (el que hoy compara contra `baseRatio` y `finalRatio`)
- Test: `tests/unit/CarryoverAvanceSemanalTest.php` (añadir un caso)

**Interfaces:**
- Consumes: tabla de Task 1, escrita por Task 2/3.
- Produces: el arrastre respeta con certeza un valor que la bitácora confirma como editado a mano.

- [ ] **Step 1: Añadir el caso al test existente**

Añadir a `tests/unit/CarryoverAvanceSemanalTest.php`:

```php
    /**
     * El caso que la bitacora existe para resolver: fila sin testigo, con un valor que no coincide
     * ni con el acumulado anterior ni con el calculado. Hoy el servicio lo respeta por sospecha;
     * con la firma en la bitacora lo respeta por evidencia.
     */
    public function testRespetaConCertezaUnValorQueLaBitacoraConfirma(): void
    {
        $this->arrastrar();
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = 0.55, Ejecutado_Carryover = NULL
             WHERE project_id = ? AND Semana = 2 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        );
        $this->db->query(
            "INSERT INTO pg_avance_edicion_manual
                (project_id, Semana, unique_id, valor_anterior, valor_nuevo, usuario)
             VALUES (?, 2, ?, 0.9, 0.55, 'test.A')",
            [self::PROJECT_ID, self::UID],
        );

        $this->arrastrar();

        $this->assertEqualsWithDelta(
            0.55,
            $this->ejecutadoEnSemana(2),
            0.001,
            'con la firma en la bitacora, el valor del residente se respeta',
        );
    }
```

Y añadir `pg_avance_edicion_manual` a la lista de tablas del método `limpiar()` de esa clase.

- [ ] **Step 2: Correr y ver el estado de partida**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app vendor/bin/phpunit tests/unit/CarryoverAvanceSemanalTest.php
```
Expected: el caso nuevo pasa **por la razón equivocada** (el criterio viejo ya preserva 0.55 por sospecha). Es esperado: la Task 5 es la que prueba de verdad el cambio. Anote el resultado y siga.

- [ ] **Step 3: Consultar la bitácora en el caso ambiguo**

En `src/Services/WeeklyRealProgressCarryoverService.php`, dentro del bloque `else` del criterio de preservación (el que empieza con el comentario «Fila que nunca pasó por el arrastre nuevo»), reemplazar el cálculo de `$preserveRatio` por:

```php
                // Fila que nunca pasó por el arrastre nuevo (anterior a la migración del testigo).
                // Primero se le pregunta a la bitácora: si hay firma de una persona y coincide con
                // lo que hay hoy, es un hecho comprobado y se respeta con certeza.
                $firmado = $this->bitacoraConfirmaEdicion(
                    $projectId,
                    $targetWeek,
                    (int) ($targetRow['unique_id'] ?? $targetRow['Consecutivo_en_Programa'] ?? 0),
                    $targetCurrentRatio,
                );

                if ($firmado) {
                    $preserveRatio = true;
                } else {
                    // Sin firma no se puede distinguir una edición vieja de un residuo del defecto
                    // de julio. Se mantiene el criterio anterior: solo se preserva cuando el valor
                    // no coincide ni con el acumulado de origen ni con lo que se escribiría ahora.
                    $preserveRatio = (
                        $targetCurrentRatio !== null
                        && $targetCurrentRatio > 0
                        && abs($targetCurrentRatio - $baseRatio) > 0.001
                        && abs($targetCurrentRatio - $finalRatio) > 0.001
                    );
                }
```

`$projectId`, `$targetWeek` y `$targetRow` ya están disponibles en ese ámbito — es el mismo
`foreach` que más abajo hace `$updatedProgramIds[(int) ($targetRow['unique_id'] ?? ...)]`.

Y añadir el método privado al final de la clase, junto a los otros helpers:

```php
    /**
     * ¿Hay firma de una persona para este avance, y coincide con lo que hay hoy en la fila?
     *
     * Se consulta SOLO desde el caso ambiguo. Moverla fuera de ahí reintroduce el defecto que este
     * servicio corrigió el 2026-08-25: el valor que deja la herencia suele ser igual al acumulado
     * de la semana anterior, y el arrastre ya lo reconoce como propio sin necesidad de preguntar.
     */
    private function bitacoraConfirmaEdicion(?int $projectId, int $semana, int $uniqueId, ?float $valorActual): bool
    {
        if ($projectId === null || $uniqueId <= 0 || $valorActual === null) {
            return false;
        }

        $firma = $this->db->queryWithProject(
            "SELECT valor_nuevo FROM pg_avance_edicion_manual
             WHERE project_id = ? AND Semana = ? AND unique_id = ?
             ORDER BY fecha DESC, id DESC LIMIT 1",
            [$projectId, $semana, $uniqueId],
            $projectId,
        )->fetchColumn();

        if ($firma === false || $firma === null) {
            return false;
        }

        return abs(((float) $firma) - $valorActual) <= 0.001;
    }
```

- [ ] **Step 4: Correr los tests del arrastre**

Run: el mismo comando del Step 2.
Expected: los seis casos en verde.

- [ ] **Step 5: Commit**

```bash
git add src/Services/WeeklyRealProgressCarryoverService.php tests/unit/CarryoverAvanceSemanalTest.php
git commit -m "feat(pg): el arrastre consulta la bitacora en el caso ambiguo"
```

---

### Task 5: El candado contra la regresión

**Files:**
- Test: `tests/unit/CarryoverAvanceSemanalTest.php` (añadir un caso)

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: nada de código; produce el candado que impide que alguien mueva la consulta de la bitácora fuera del caso ambiguo.

Esta tarea existe sola, y no fusionada con la Task 4, a propósito: es la que un revisor debe poder aprobar o rechazar por separado. Sin ella, el error más probable de esta funcionalidad —consultar la bitácora siempre en vez de solo en el caso ambiguo— pasaría todas las demás pruebas.

- [ ] **Step 1: Escribir el candado**

```php
    /**
     * CANDADO. Una actividad recien asociada trae, por herencia, exactamente el acumulado de la
     * semana anterior. Ese valor NO es ambiguo: el arrastre lo reconoce como propio y debe
     * recalcular sumando el avance de la semanal.
     *
     * Si alguien mueve la consulta de la bitacora fuera del caso ambiguo —para "simplificar"—,
     * esa actividad quedaria marcada como editada a mano y dejaria de recibir avance: el defecto
     * del 2026-08-25, reintroducido por otra via. Este test lo impide.
     */
    public function testNoRespetaUnValorFirmadoQueCoincideConElAcumuladoAnterior(): void
    {
        // La herencia dejo en la semana 2 el mismo acumulado que traia la 1 (0.7), y quedo firmado.
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = 0.7, Ejecutado_Carryover = NULL
             WHERE project_id = ? AND Semana = 2 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        );
        $this->db->query(
            "INSERT INTO pg_avance_edicion_manual
                (project_id, Semana, unique_id, valor_anterior, valor_nuevo, usuario)
             VALUES (?, 2, ?, 0, 0.7, 'test.A')",
            [self::PROJECT_ID, self::UID],
        );

        $this->arrastrar();

        $this->assertEqualsWithDelta(
            0.90,
            $this->ejecutadoEnSemana(2),
            0.001,
            'el avance de la semanal debe sumarse igual: 0.7 + 20 puntos = 0.9',
        );
    }
```

- [ ] **Step 2: Correr y verificar que pasa con la implementación de la Task 4**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app vendor/bin/phpunit tests/unit/CarryoverAvanceSemanalTest.php
```
Expected: los siete casos en verde.

- [ ] **Step 3: Comprobar que el candado muerde**

Comente temporalmente la condición del caso ambiguo, de modo que la bitácora se consulte siempre, y vuelva a correr. Expected: **este test falla** (obtiene 0.7 en vez de 0.9). Restaure el código.

Un candado que no falla cuando se rompe lo que vigila no es un candado; este paso lo comprueba.

- [ ] **Step 4: Commit**

```bash
git add tests/unit/CarryoverAvanceSemanalTest.php
git commit -m "test(pg): candado contra consultar la bitacora fuera del caso ambiguo"
```

---

### Task 6: Verificación completa y cierre

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `TASKS.md`

- [ ] **Step 1: Suite completa, nivel `puro`**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro
```
Expected: 0 fallados.

- [ ] **Step 2: Análisis estático**

Run:
```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml run --rm --no-deps app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```
Expected: `[OK] No errors`.

- [ ] **Step 3: Suite nivel `http`**

Esta necesita Apache arriba, así que hay que apuntar el contenedor compartido a este worktree y devolverlo al terminar (regla 4 de `docs/coordinacion-sesiones.md`).

```bash
LPS_CODE_ROOT="$(pwd)" docker compose -f /Users/felipebenitez/Developer/lps-aia/docker-compose.yml -f /Users/felipebenitez/Developer/lps-aia/docker-compose.override.yml up -d app
sleep 6
cd /Users/felipebenitez/Developer/lps-aia && docker compose exec -T app php scripts/run-php-tests.php --nivel=http
```
Expected: 0 fallados.

Devolver el contenedor a la raíz, siempre:
```bash
cd /Users/felipebenitez/Developer/lps-aia && docker compose up -d app
```

- [ ] **Step 4: Probar el flujo real en el navegador**

Con Da Porto sembrado en local, entrar por la puerta de desarrollo y editar el avance de una actividad en Programa General:
```
http://localhost:8081/dev/entrar?u=test.A&p=Da%20Porto
```
Luego comprobar que quedó la firma:
```bash
cd /Users/felipebenitez/Developer/lps-aia && set -a && . ./.env && set +a && \
printf "SELECT * FROM pg_avance_edicion_manual WHERE project_id=73 ORDER BY id DESC LIMIT 5;\n" \
| docker compose exec -T db sh -c "mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" $DB_NAME"
```
Expected: una fila por cada edición hecha, con el valor anterior y el nuevo correctos.

- [ ] **Step 5: Anotar el cierre**

Añadir la entrada a `CHANGELOG.md` bajo `## [Sin publicar]`, y en `TASKS.md` marcar el estado del frente.

- [ ] **Step 6: Commit**

```bash
git add CHANGELOG.md TASKS.md
git commit -m "docs: cierra el frente de la bitacora del avance manual"
```

---

## Lo que este plan NO hace

Escrito a propósito, para que nadie lo agregue a mitad de camino creyendo que faltaba:

- **No repara las 27 actividades ya congeladas en producción.** La bitácora cuenta desde que se despliega. Esas se resuelven a mano, en un frente aparte.
- **No toca el comportamiento de ninguna pantalla**, ni el de "Auto-Asociar". El defecto de que Actualizar reemplace el avance sin avisar está anotado en `TASKS.md` como frente propio.
- **No registra los otros cuatro campos** ni la programación semanal. Fuera de alcance por decisión explícita.
- **No despliega.** Publicar en `main` y llevarlo a la obra son cosas distintas, y el despliegue necesita su propia autorización.
