# A4 · El plan de compras con fechas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cada paquete de contratación tenga fecha: amarrado a un frente del cronograma, con el proceso de contratación programado hacia atrás paso a paso.

**Architecture:** Un servicio nuevo (`PlanFechasService`) traduce amarres en fechas y no toca `PaquetesService`, que ya es grande. Tres tablas: el amarre paquete↔frente, la cabecera del plan y el detalle por paso. La SPA gana una pestaña «Plan» y un asistente de amarre que reutiliza el patrón de `PaquetesAsistente`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker), FastRoute, tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) sobre MySQL real; React + TypeScript + Vite + AG Grid Community; Vitest.

## Global Constraints

- **Worktree:** todo el PHP va en `/Volumes/Crucial X6/Developer/lps-aia-pdc` (rama `pdc-dev`). La SPA en `/Volumes/Crucial X6/Developer/plan-de-compras`. **Nunca** trabajar en `../lps-aia`.
- **Puerto:** el stack del worktree publica **8091**, no 8081. Docker: `docker compose exec -T app php <ruta>`.
- **Tablas operativas:** `project_id int NOT NULL`, índice liderado por `project_id`, `utf8mb4_unicode_ci`. DDL en `.sql`, backfills en `.php` con dry-run → `--apply`.
- **RBAC:** lectura `lps.paquetes_contratacion.ver`, escritura `lps.paquetes_contratacion.editar`. CSRF form key `plan_compras_v2`.
- **Envelope de API:** `{ok, data|error}` vía el trait `PlanComprasJsonRespuestas`.
- **Idioma:** código, comentarios y mensajes de UI en español. Identificadores y rutas en su idioma original.
- **Unidad de tiempo:** días **calendario**. No hábiles, no festivos.
- **Semana del cronograma:** siempre `MAX(Semana)` de `semanas_activas` para el proyecto.
- **No tocar el motor de paquetes.** `test_pdc_v2_brecha_daporto` debe seguir en **7** al terminar cada tarea.
- **Proyecto de referencia:** DAPORTO es `project_id = 73`, versión activa `292`. Los tests usan `999901`/`999902`.

---

### Task 1: Las tres tablas

**Files:**
- Create: `database/migrations/20260728_pdc_v2_plan_fechas.sql`
- Test: se verifica corriendo la migración y consultando `information_schema`

**Interfaces:**
- Consumes: nada.
- Produces: las tablas `pdc_paquete_frente`, `pdc_plan_paquete`, `pdc_plan_paso` que todas las tareas siguientes usan.

- [ ] **Step 1: Escribir el DDL**

Crear `database/migrations/20260728_pdc_v2_plan_fechas.sql`:

```sql
-- 20260728_pdc_v2_plan_fechas.sql
-- PDC v2 / Fase A4: el plan de compras con fechas.
--
-- (A) pdc_paquete_frente — el amarre. Un paquete apunta a un encabezado del cronograma por su
--     unique_id, que es estable ante reprogramaciones (medido: los 273 de la semana 1 son los
--     mismos en la semana 4). Se guarda la fecha que tenía ese frente al amarrar para poder
--     detectar después que se movió.
-- (B) pdc_plan_paquete — la cabecera del plan calculado.
-- (C) pdc_plan_paso — una fila por paso del proceso. Tabla hija y no siete columnas porque B1
--     pondrá la fecha real junto a la programada sin rehacer el modelo.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS. No toca datos existentes.

CREATE TABLE IF NOT EXISTS pdc_paquete_frente (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  frente_nombre VARCHAR(500) NOT NULL,
  fecha_ancla DATE NOT NULL,
  semana_origen INT NOT NULL,
  origen ENUM('similitud','rama','humano') NOT NULL DEFAULT 'humano',
  confianza ENUM('alta','media','baja') NULL,
  evidencia VARCHAR(500) NOT NULL DEFAULT '',
  confirmado_humano TINYINT(1) NOT NULL DEFAULT 0,
  asignado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppf_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppf_proyecto_frente (project_id, unique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paquete (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  fecha_ancla DATE NOT NULL,
  fecha_arranque DATE NOT NULL,
  dias_totales INT NOT NULL,
  duracion_ref INT NULL,
  duracion_provisional TINYINT(1) NOT NULL DEFAULT 0,
  responsable VARCHAR(100) NOT NULL DEFAULT '',
  calculado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppp_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppp_proyecto_arranque (project_id, fecha_arranque)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paso (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  orden TINYINT NOT NULL,
  paso VARCHAR(60) NOT NULL,
  dias INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pps_proyecto_paquete_orden (project_id, paquete_id, orden),
  KEY idx_pps_proyecto_paquete (project_id, paquete_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Aplicar la migración**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
# Credenciales reales en el .env del worktree.
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/20260728_pdc_v2_plan_fechas.sql
```

Si las credenciales fallan, mirar `docker-compose.yml` para el usuario/clave y la base reales.

- [ ] **Step 3: Verificar que existen y son idempotentes**

```bash
# Credenciales reales en el .env del worktree.
# El paréntesis del OR no es opcional: `AND ... OR ...` sin él ata el AND primero y la consulta
# devuelve `pdc_paquete_frente` de cualquier esquema, no solo del que se está verificando.
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
  SELECT table_name, table_collation FROM information_schema.tables
  WHERE table_schema=DATABASE() AND (table_name LIKE 'pdc_pla%' OR table_name='pdc_paquete_frente');"
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/20260728_pdc_v2_plan_fechas.sql
```

Esperado: las 3 tablas con `utf8mb4_unicode_ci`, y la segunda ejecución sin error.

- [ ] **Step 4: Gates de tablas globales**

```bash
docker compose exec -T app php tests/test_global_table_safety.php
docker compose exec -T app php tests/test_global_table_reconciliation.php
```

Esperado: `=== Global Table Safety: OK ===` y `=== Global Table Reconciliation: OK ===`, exit 0.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260728_pdc_v2_plan_fechas.sql
git commit -m "feat(pdc): tablas del plan con fechas (amarre, cabecera y pasos)"
```

---

### Task 2: Los frentes del cronograma

El campo `Actividad` de `programa_consolidado` trae HTML con el capítulo embebido:
`<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>`.
Los frentes son las filas con `Titulo = 1` (31 en DAPORTO); las de `Titulo = 0` son actividades hoja (242).

**Files:**
- Create: `src/Services/Pdc/PlanFechasService.php`
- Create: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: las tablas de la Task 1.
- Produces:
  - `PlanFechasService::__construct(\Database $db)`
  - `public function frentesDisponibles(int $projectId): array` → lista de `['uniqueId'=>int, 'nombre'=>string, 'capitulo'=>string, 'fechaInicio'=>string]`, ordenada por `fechaInicio` ascendente.
  - `public static function limpiarActividad(string $html): array` → `['nombre'=>string, 'capitulo'=>string]`

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_plan_fechas.php`:

```php
<?php
// tests/test_pdc_v2_plan_fechas.php — PlanFechasService sobre MySQL real (proyecto 999903).
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999903;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// Fixture: dos semanas consolidadas; la activa es la 2. Encabezados (Titulo=1) y una hoja (Titulo=0).
$db->query('INSERT INTO semanas_activas (project_id, Semana) VALUES (?, 1), (?, 2)', [$P, $P]);
$filas = [
    // [unique_id, Semana, Titulo, Actividad, Fecha_Inicio]
    [9001, 1, 1, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-08-01'],
    [9001, 2, 1, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', '2026-08-18'],
    [9002, 2, 1, '<b>PRELIMINARES, </b> <small>[Capítulo: TORRE 1]</small>', '2026-05-25'],
    [9003, 2, 0, '<b>VACIADO LOSA PISO 3, </b> <small>[Capítulo: ESTRUCTURA, TORRE 1]</small>', '2026-09-10'],
];
foreach ($filas as [$uid, $sem, $tit, $act, $ini]) {
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, 1, ?, ?, 1, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, $sem, $uid, $act, $tit, $ini],
    );
}

echo "=== PDC v2 A4: plan con fechas ===\n";
$svc = new PlanFechasService($db);

// --- limpiarActividad ---
$l = PlanFechasService::limpiarActividad('<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>');
$assert($l['nombre'] === 'ESTRUCTURA', 'Quita el HTML y la coma final del nombre: ' . $l['nombre']);
$assert($l['capitulo'] === 'TORRE 1', 'Extrae el capítulo embebido: ' . $l['capitulo']);
$assert(PlanFechasService::limpiarActividad('SIN HTML')['nombre'] === 'SIN HTML', 'Un texto sin HTML pasa igual.');

// --- frentesDisponibles ---
$f = $svc->frentesDisponibles($P);
$assert(count($f) === 2, 'Solo los encabezados de la semana activa (2 de 4 filas): ' . count($f));
$uids = array_column($f, 'uniqueId');
$assert(!in_array(9003, $uids, true), 'La actividad hoja (Titulo=0) no es un frente.');
$assert($f[0]['uniqueId'] === 9002 && $f[0]['fechaInicio'] === '2026-05-25', 'Ordena por fecha ascendente: primero PRELIMINARES.');
$assert($f[1]['fechaInicio'] === '2026-08-18', 'Toma la fecha de la semana ACTIVA (2), no de la 1.');
$assert($svc->frentesDisponibles(999999) === [], 'Proyecto sin cronograma → lista vacía.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verlo fallar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: error fatal `Class "App\Services\Pdc\PlanFechasService" not found`.

- [ ] **Step 3: Implementar el servicio**

Crear `src/Services/Pdc/PlanFechasService.php`:

```php
<?php

namespace App\Services\Pdc;

/**
 * A4 · Convierte el amarre paquete↔cronograma en fechas.
 *
 * El cronograma no es el presupuesto a otra escala: tiene su propio árbol de frentes, con el
 * capítulo embebido en HTML dentro del campo `Actividad`. Los frentes (encabezados, `Titulo = 1`)
 * son los que hablan el idioma de los paquetes: ESTRUCTURA, MAMPOSTERÍA, RED ELÉCTRICA.
 */
class PlanFechasService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Separa el nombre del frente del capítulo que el cronograma embebe en un `<small>`.
     * Entrada: `<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>`
     */
    public static function limpiarActividad(string $html): array
    {
        $capitulo = '';
        if (preg_match('/\[Cap[íi]tulo:\s*([^\]]+)\]/u', $html, $m) === 1) {
            $capitulo = trim($m[1]);
        }
        $sinSmall = preg_replace('/<small>.*?<\/small>/su', '', $html);
        $nombre = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $sinSmall)));
        return ['nombre' => rtrim($nombre, ' ,'), 'capitulo' => $capitulo];
    }

    /** Frentes de obra de la semana activa, del más temprano al más tardío. */
    public function frentesDisponibles(int $projectId): array
    {
        $semana = $this->db->query(
            'SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        if ($semana === false || $semana === null) {
            return [];
        }
        $rows = $this->db->query(
            'SELECT unique_id, Actividad, Fecha_Inicio FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND Titulo = 1 AND unique_id IS NOT NULL
               AND Fecha_Inicio IS NOT NULL
             ORDER BY Fecha_Inicio ASC, unique_id ASC',
            [$projectId, (int) $semana],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static function (array $r): array {
            $l = self::limpiarActividad((string) $r['Actividad']);
            return [
                'uniqueId' => (int) $r['unique_id'],
                'nombre' => $l['nombre'],
                'capitulo' => $l['capitulo'],
                'fechaInicio' => (string) $r['Fecha_Inicio'],
            ];
        }, $rows);
    }
}
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): los frentes del cronograma, con el HTML del capítulo ya separado"
```

---

### Task 3: Proponer el frente de cada paquete

La propuesta usa dos señales: el nombre del paquete contra el del frente, y la rama del presupuesto
donde están sus insumos. Cuando el paquete toca varios frentes, se propone **el más temprano**:
es el que marca cuándo tiene que estar listo el contrato.

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Modify: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `frentesDisponibles()` de la Task 2.
- Produces: `public function sugerirFrentes(int $projectId, ?int $versionId = null): array` → mapa `paqueteId => ['uniqueId'=>int, 'nombre'=>string, 'fechaInicio'=>string, 'origen'=>'similitud'|'rama', 'confianza'=>'alta'|'media'|'baja', 'evidencia'=>string]`.

- [ ] **Step 1: Escribir el test que falla**

Añadir al final de `tests/test_pdc_v2_plan_fechas.php`, **antes** del `echo $failures`:

```php
// --- sugerirFrentes: por nombre ---
$paqEstructura = (int) $db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 ESTRUCTURA', 'TEST A4 ESTRUCTURA', 'a_todo_costo', 'contrato', 1, 'test-a4', NOW())",
) === null ? 0 : (int) $db->lastInsertId();

$sug = $svc->sugerirFrentes($P);
$s = $sug[$paqEstructura] ?? null;
$assert($s !== null && $s['uniqueId'] === 9001, 'El paquete «TEST A4 ESTRUCTURA» se propone al frente ESTRUCTURA.');
$assert($s !== null && $s['origen'] === 'similitud', 'La propuesta por nombre se marca como «similitud».');
$assert($s !== null && str_contains($s['evidencia'], 'ESTRUCTURA'), 'La evidencia nombra el frente: ' . ($s['evidencia'] ?? ''));

// Un paquete sin parecido con ningún frente no recibe propuesta: no se inventa.
$paqRaro = (int) $db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
     VALUES ('TEST A4 ZZZQQQ', 'TEST A4 ZZZQQQ', 'suministro', 'contrato', 1, 'test-a4', NOW())",
) === null ? 0 : (int) $db->lastInsertId();
$sug2 = $svc->sugerirFrentes($P);
$assert(!isset($sug2[$paqRaro]), 'Sin señal, no hay propuesta: el paquete queda pendiente.');
```

Y añadir al `$limpiar` de la cabecera, tras el `DELETE FROM pdc_paquete_frente`:

```php
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a4'");
```

- [ ] **Step 2: Correr el test y verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `FAIL: El paquete «TEST A4 ESTRUCTURA» se propone al frente ESTRUCTURA.` y `Call to undefined method ... sugerirFrentes()`.

- [ ] **Step 3: Implementar sugerirFrentes**

Añadir a `PlanFechasService`:

```php
    /** Similitud mínima de nombre para proponer un frente (Jaccard sobre palabras). */
    private const SIMILITUD_MINIMA = 0.34;

    /**
     * Propone un frente para cada paquete activo del proyecto.
     *
     * Señal 1 — el nombre: «Sum + Inst ESTRUCTURA» contra el frente «ESTRUCTURA». Se descarta el
     * prefijo de tipo de negociación, que no dice nada del oficio.
     * Señal 2 — la rama: los subcapítulos donde el paquete tiene insumos, contra el nombre del
     * frente. Entre varios candidatos gana el que arranca antes: es el que marca la fecha límite.
     */
    public function sugerirFrentes(int $projectId, ?int $versionId = null): array
    {
        $frentes = $this->frentesDisponibles($projectId);
        if ($frentes === []) {
            return [];
        }
        $paquetes = $this->db->query(
            'SELECT id, nombre FROM general_paquetes_contratacion WHERE activo = 1',
        )->fetchAll(\PDO::FETCH_ASSOC);

        $tokens = static function (string $s): array {
            $limpio = preg_replace('/^(Sum \+ Inst|Suministro|M\. de O)\s*/u', '', $s);
            return array_values(array_filter(explode(' ', MaestroInsumosService::normalizar((string) $limpio))));
        };
        $frentesTok = [];
        foreach ($frentes as $f) {
            $frentesTok[] = $f + ['tok' => $tokens($f['nombre'])];
        }

        $out = [];
        foreach ($paquetes as $p) {
            $tp = $tokens((string) $p['nombre']);
            if ($tp === []) {
                continue;
            }
            $mejor = null;
            $mejorPunt = 0.0;
            foreach ($frentesTok as $f) {
                $comunes = count(array_intersect($tp, $f['tok']));
                if ($comunes === 0) {
                    continue;
                }
                $punt = $comunes / max(1, count(array_unique(array_merge($tp, $f['tok']))));
                // Empate: gana el más temprano, que es el que fija el límite del contrato.
                if ($punt > $mejorPunt || ($punt === $mejorPunt && $mejor !== null && $f['fechaInicio'] < $mejor['fechaInicio'])) {
                    $mejor = $f;
                    $mejorPunt = $punt;
                }
            }
            if ($mejor === null || $mejorPunt < self::SIMILITUD_MINIMA) {
                continue;
            }
            $out[(int) $p['id']] = [
                'uniqueId' => $mejor['uniqueId'],
                'nombre' => $mejor['nombre'],
                'fechaInicio' => $mejor['fechaInicio'],
                'origen' => 'similitud',
                'confianza' => $mejorPunt >= 0.7 ? 'alta' : 'media',
                'evidencia' => sprintf(
                    'El nombre del paquete coincide con el frente «%s» del cronograma (arranca %s).',
                    $mejor['nombre'],
                    $mejor['fechaInicio'],
                ),
            ];
        }
        return $out;
    }
```

Añadir el `use` que falta arriba del archivo, junto a la declaración de namespace:

```php
use App\Services\Pdc\MaestroInsumosService;
```

(Si ya están en el mismo namespace `App\Services\Pdc`, el `use` sobra: usar `MaestroInsumosService::` directamente.)

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0.

- [ ] **Step 5: Añadir la segunda señal — la rama del presupuesto**

Por nombre solo casan 18 de 96 paquetes en DAPORTO: la rama es lo que hace útil la propuesta. El
paquete conoce sus insumos, el insumo su actividad y la actividad su subcapítulo; ese subcapítulo se
compara con el nombre del frente.

Test a añadir antes del `echo $failures` (usa el fixture de presupuesto que ya montan los tests de
paquetes; si no hay insumos ligados al paquete, la señal no aplica y no debe romper):

```php
// --- sugerirFrentes: la rama, cuando el nombre no basta ---
// Un paquete que no se parece a ningún frente por su nombre, pero cuyos insumos viven en un
// subcapítulo que sí: la propuesta sale igual, marcada como «rama» y con confianza media.
$sugRama = $svc->sugerirFrentes($P);
foreach ($sugRama as $pid => $s) {
    $assert(in_array($s['origen'], ['similitud', 'rama'], true), "Origen válido en el paquete $pid: {$s['origen']}");
    if ($s['origen'] === 'rama') {
        $assert($s['confianza'] === 'media', 'La propuesta por rama nunca es de confianza alta: hay un salto.');
        $assert(str_contains($s['evidencia'], 'subcap'), 'La evidencia por rama nombra el subcapítulo.');
    }
}
```

Implementación: tras el bucle de similitud, para los paquetes que quedaron sin propuesta, consultar
los subcapítulos donde tienen insumos —reutilizando la consulta de `pdc_insumo_paquete` unida a
`pdc_presupuesto_apu_insumos` y `pdc_presupuesto_items` por `codigo_padre`, igual que hace
`PaquetesService::actividadDominantePorInsumo()`— y comparar cada subcapítulo con los frentes con la
misma función de tokens. Entre varios candidatos, **el más temprano**. Origen `'rama'`, confianza
siempre `'media'`, evidencia del tipo:
`Sus insumos están en el subcapítulo «PISOS Y ENCHAPES», que en el cronograma arranca el 2027-05-12.`

- [ ] **Step 6: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0.

- [ ] **Step 7: Medir cuánto sube la cobertura sobre DAPORTO**

```bash
docker compose exec -T app php -r '
require "/var/www/html/vendor/autoload.php"; require "/var/www/html/src/Core/Database.php";
$s = new App\Services\Pdc\PlanFechasService(Database::getInstance());
$g = $s->sugerirFrentes(73);
$por = [];
foreach ($g as $x) { $por[$x["origen"]] = ($por[$x["origen"]] ?? 0) + 1; }
echo "paquetes con propuesta: ", count($g), " → ", json_encode($por), "\n";'
```

Esperado: **más de 18**. Anotar el número en el commit: es la línea base del asistente.

- [ ] **Step 8: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): propuesta de frente por nombre y por la rama del presupuesto"
```

---

### Task 4: Amarrar el paquete a su frente

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Modify: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `frentesDisponibles()`.
- Produces:
  - `public function amarrar(int $projectId, int $paqueteId, int $uniqueId, string $usuario, array $procedencia = []): array` → `['ok'=>bool, 'code'?=>string]`
  - `public function amarres(int $projectId): array` → mapa `paqueteId => ['uniqueId','frenteNombre','fechaAncla','origen','confianza','confirmadoHumano']`

- [ ] **Step 1: Escribir el test que falla**

Añadir antes del `echo $failures`:

```php
// --- amarrar ---
$r = $svc->amarrar($P, $paqEstructura, 9001, 'test-a4', [
    'origen' => 'similitud', 'confianza' => 'alta', 'evidencia' => 'Coincide el nombre.', 'confirmado' => true,
]);
$assert(($r['ok'] ?? false) === true, 'Amarrar un paquete a un frente existente.');
$a = $svc->amarres($P);
$assert(isset($a[$paqEstructura]), 'El amarre se puede leer de vuelta.');
$assert($a[$paqEstructura]['fechaAncla'] === '2026-08-18', 'El amarre guarda la fecha que el frente tenía al amarrarlo.');
$assert($a[$paqEstructura]['origen'] === 'similitud' && $a[$paqEstructura]['confirmadoHumano'] === true,
    'Aceptar la propuesta conserva la capa Y queda confirmada.');

// Reamarrar mueve, no duplica.
$svc->amarrar($P, $paqEstructura, 9002, 'test-a4');
$filas = (int) $db->query('SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id = ?', [$P])->fetchColumn();
$assert($filas === 1, 'Un paquete, un frente: reamarrar no duplica filas.');
$a2 = $svc->amarres($P);
$assert($a2[$paqEstructura]['uniqueId'] === 9002 && $a2[$paqEstructura]['fechaAncla'] === '2026-05-25',
    'Al reamarrar se actualiza también la fecha ancla.');
$assert($a2[$paqEstructura]['origen'] === 'humano', 'Elegir a mano es una decisión humana.');

// Un frente que no existe en la semana activa se rechaza.
$mal = $svc->amarrar($P, $paqEstructura, 999999, 'test-a4');
$assert(($mal['ok'] ?? true) === false && ($mal['code'] ?? '') === 'FRENTE_INVALIDO', 'Frente inexistente rechazado.');
```

- [ ] **Step 2: Correr el test y verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `Call to undefined method ... amarrar()`.

- [ ] **Step 3: Implementar amarrar y amarres**

Añadir a `PlanFechasService`:

```php
    /**
     * Amarra un paquete a un frente del cronograma.
     *
     * Guarda la fecha que el frente tenía en este momento: es lo que permite detectar más adelante
     * que la obra se reprogramó y el plan quedó viejo. La procedencia funciona como en los insumos:
     * aceptar la propuesta conserva la capa que la produjo (acierto), elegir a mano es «humano».
     */
    public function amarrar(int $projectId, int $paqueteId, int $uniqueId, string $usuario, array $procedencia = []): array
    {
        $frente = null;
        foreach ($this->frentesDisponibles($projectId) as $f) {
            if ($f['uniqueId'] === $uniqueId) {
                $frente = $f;
                break;
            }
        }
        if ($frente === null) {
            return ['ok' => false, 'code' => 'FRENTE_INVALIDO'];
        }
        $paquete = $this->db->query(
            'SELECT id FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetchColumn();
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }

        $origen = in_array($procedencia['origen'] ?? '', ['similitud', 'rama'], true) ? $procedencia['origen'] : 'humano';
        $delMotor = $origen !== 'humano';
        $semana = (int) $this->db->query('SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?', [$projectId])->fetchColumn();

        $this->db->query(
            'INSERT INTO pdc_paquete_frente
                (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla, semana_origen,
                 origen, confianza, evidencia, confirmado_humano, asignado_por, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), frente_nombre = VALUES(frente_nombre),
                fecha_ancla = VALUES(fecha_ancla), semana_origen = VALUES(semana_origen),
                origen = VALUES(origen), confianza = VALUES(confianza), evidencia = VALUES(evidencia),
                confirmado_humano = VALUES(confirmado_humano), asignado_por = VALUES(asignado_por),
                updated_at = NOW()',
            [
                $projectId, $paqueteId, $uniqueId, $frente['nombre'], $frente['fechaInicio'], $semana,
                $origen,
                $delMotor && in_array($procedencia['confianza'] ?? '', ['alta', 'media', 'baja'], true) ? $procedencia['confianza'] : null,
                $delMotor ? mb_substr((string) ($procedencia['evidencia'] ?? ''), 0, 500) : '',
                (!$delMotor || ($procedencia['confirmado'] ?? false) === true) ? 1 : 0,
                $usuario,
            ],
        );
        return ['ok' => true];
    }

    /** Amarres vigentes del proyecto, indexados por paquete. */
    public function amarres(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT paquete_id, unique_id, frente_nombre, fecha_ancla, origen, confianza, confirmado_humano
             FROM pdc_paquete_frente WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['paquete_id']] = [
                'uniqueId' => (int) $r['unique_id'],
                'frenteNombre' => (string) $r['frente_nombre'],
                'fechaAncla' => (string) $r['fecha_ancla'],
                'origen' => (string) $r['origen'],
                'confianza' => $r['confianza'],
                'confirmadoHumano' => (int) $r['confirmado_humano'] === 1,
            ];
        }
        return $out;
    }
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): amarre paquete↔frente con procedencia y fecha ancla"
```

---

### Task 5: Las duraciones que faltan

29 de los 96 paquetes de DAPORTO que generan proceso no tienen `duracion_ref` — el 29,2 % del valor.
Los partidos heredan del pariente del que salieron; el resto toma la mediana de su tipo.

**Files:**
- Create: `database/migrations/20260728_pdc_v2_duraciones_faltantes.php`

**Interfaces:**
- Consumes: `general_paquetes_contratacion.duracion_ref`, `general_dias_procesos_contratacion`.
- Produces: `duracion_ref` poblado donde se pudo heredar; los demás quedan NULL y el cálculo usará la mediana marcándola provisional.

- [ ] **Step 1: Escribir la migración de herencia**

Crear `database/migrations/20260728_pdc_v2_duraciones_faltantes.php` con el patrón dry-run → `--apply`
de las migraciones de A3.5 (`20260727_pdc_v2_retirar_paquetes_fusionados.php` sirve de modelo exacto:
guardia, dry-run, salida legible, `exit` con código).

La herencia es por nombre: un paquete `Suministro X` o `M. de O X` hereda la `duracion_ref` de
`Sum + Inst X` si existe. Lista de parejas a resolver, con su razón:

```php
$herencias = [
    'Suministro PUERTAS EN MADERA' => 'Sum + Inst CARPINTERÍA DE MADERA',
    'M. de O CARPINTERÍA DE MADERA' => 'Sum + Inst CARPINTERÍA DE MADERA',
    'Suministro PUERTAS METÁLICAS' => 'Sum + Inst CARPINTERÍA METÁLICA',
    'M. de O PUERTAS METÁLICAS' => 'Sum + Inst CARPINTERÍA METÁLICA',
    'Suministro PUERTAS CORTAFUEGO' => 'Sum + Inst PUERTAS CORTAFUEGO',
    'M. de O PUERTAS CORTAFUEGO' => 'Sum + Inst PUERTAS CORTAFUEGO',
    'Suministro ANCLAJES' => 'Sum + Inst ANCLAJES',
    'Suministro DOTACIÓN COCINAS Y LAVADEROS' => 'Sum + Inst DOTACIÓN COCINAS Y LAVADEROS',
    'M. de O TOPELLANTAS' => 'Sum + Inst TOPELLANTAS',
    'M. de O INSTALACIÓN DE PISOS CERÁMICOS' => 'Sum + Inst PISOS Y ENCHAPES CERÁMICOS/PORCELANATO',
    'Suministro PISOS Y ENCHAPES CERÁMICOS/PORCELANATO' => 'Sum + Inst PISOS Y ENCHAPES CERÁMICOS/PORCELANATO',
    'Suministro APARATOS SANITARIOS Y GRIFERÍA' => 'Sum + Inst APARATOS SANITARIOS Y GRIFERÍA',
    'M. de O APARATOS SANITARIOS Y GRIFERÍA' => 'Sum + Inst APARATOS SANITARIOS Y GRIFERÍA',
];
```

Para cada pareja: si el destino ya tiene `duracion_ref`, no tocar; si el pariente no existe o tampoco
tiene, anotarlo en la salida y seguir. Al final, **listar los que siguen sin duración** con su cuantía
en DAPORTO, para que se vea qué se quedó con mediana provisional.

- [ ] **Step 2: Dry-run**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T app php database/migrations/20260728_pdc_v2_duraciones_faltantes.php
```

Esperado: lista de herencias a aplicar y de los que quedarán sin duración. Exit 0. Nada escrito.

- [ ] **Step 3: Aplicar y comprobar idempotencia**

```bash
docker compose exec -T app php database/migrations/20260728_pdc_v2_duraciones_faltantes.php --apply
docker compose exec -T app php database/migrations/20260728_pdc_v2_duraciones_faltantes.php --apply
```

Esperado: la primera aplica N herencias; la segunda dice que ya estaban y aplica 0. Exit 0 en ambas.

- [ ] **Step 4: Medir cuánto bajó el hueco**

```bash
docker compose exec -T app php -r '
require "/var/www/html/vendor/autoload.php"; require "/var/www/html/src/Core/Database.php";
$db = Database::getInstance();
$u = $db->query("SELECT COUNT(DISTINCT p.id) FROM pdc_insumo_paquete ip
  JOIN general_paquetes_contratacion p ON p.id=ip.paquete_id
  WHERE ip.project_id=73 AND ip.omitido=0 AND p.duracion_ref IS NULL
    AND p.modalidad_contratacion IN (\"contrato\",\"orden_compra\")")->fetchColumn();
echo "paquetes de DAPORTO con proceso y sin duracion_ref: $u\n";'
```

Esperado: un número **menor que 29**. Anotarlo en el mensaje del commit.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260728_pdc_v2_duraciones_faltantes.php
git commit -m "feat(pdc): los paquetes partidos heredan la duración de su pariente"
```

---

### Task 6: Programar hacia atrás

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Modify: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `amarres()` de la Task 4.
- Produces:
  - `public const PASOS = [...]` (los siete, en orden, con su columna del catálogo).
  - `public function calcular(int $projectId, string $usuario): array` → `['ok'=>bool, 'calculados'=>int, 'sinDuracion'=>int]`
  - `public function plan(int $projectId): array` → lista por paquete con `fechaArranque`, `fechaAncla`, `diasTotales`, `duracionProvisional`, `responsable`, `pasos[]`, `diasRetraso`, ordenada con los vencidos primero.

- [ ] **Step 1: Escribir el test que falla**

Añadir antes del `echo $failures`:

```php
// --- calcular: la resta hacia atrás ---
// Catálogo de duraciones de juguete: 7+5+7+25+20 de proceso + 8 fabricación + 15 en obra = 87.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('TEST A4 DURACION', 'Suministro', 7, 5, 7, 25, 20, 8, 15)",
);
$durId = (int) $db->lastInsertId();
$db->query('UPDATE general_paquetes_contratacion SET duracion_ref = ? WHERE id = ?', [$durId, $paqEstructura]);

// Reamarrar a ESTRUCTURA (18-ago-2026) para tener una fecha ancla conocida.
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');
$c = $svc->calcular($P, 'test-a4');
$assert(($c['ok'] ?? false) === true && ($c['calculados'] ?? 0) === 1, 'Se calcula el plan del paquete amarrado.');

$plan = $svc->plan($P);
$fila = null;
foreach ($plan as $f) { if ($f['paqueteId'] === $paqEstructura) { $fila = $f; } }
$assert($fila !== null, 'El plan trae el paquete calculado.');
$assert($fila['diasTotales'] === 87, 'Suma los siete pasos: 87 días. Dio ' . ($fila['diasTotales'] ?? 0));
$assert($fila['fechaArranque'] === '2026-05-23', 'Arranque = ancla menos 87 días calendario. Dio ' . ($fila['fechaArranque'] ?? ''));
$assert(count($fila['pasos']) === 7, 'Guarda una fila por paso.');
$assert($fila['pasos'][0]['paso'] === 'Elaboración de pliegos' && $fila['pasos'][0]['fechaInicio'] === '2026-05-23',
    'El primer paso arranca en la fecha de arranque.');
$assert(end($fila['pasos'])['fechaFin'] === '2026-08-18', 'El último paso TERMINA en la fecha del frente.');
$assert($fila['duracionProvisional'] === false, 'Con duracion_ref real, no es provisional.');

// Recalcular no duplica pasos.
$svc->calcular($P, 'test-a4');
$np = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$P, $paqEstructura])->fetchColumn();
$assert($np === 7, 'Recalcular reemplaza los pasos, no los acumula. Hay ' . $np);

// Sin duracion_ref: se usa la mediana del tipo y queda marcado como provisional.
$db->query('UPDATE general_paquetes_contratacion SET duracion_ref = NULL WHERE id = ?', [$paqEstructura]);
$svc->calcular($P, 'test-a4');
$plan2 = $svc->plan($P);
$fila2 = null;
foreach ($plan2 as $f) { if ($f['paqueteId'] === $paqEstructura) { $fila2 = $f; } }
$assert($fila2 !== null && $fila2['duracionProvisional'] === true, 'Sin duración propia, el plazo se marca provisional.');
$assert($fila2 !== null && $fila2['diasTotales'] > 0, 'La mediana del tipo da un plazo mayor que cero.');
```

Y añadir al `$limpiar`:

```php
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'TEST A4 DURACION'");
```

- [ ] **Step 2: Correr el test y verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `Call to undefined method ... calcular()`.

- [ ] **Step 3: Implementar PASOS, calcular y plan**

Añadir a `PlanFechasService`:

```php
    /**
     * Los siete pasos del proceso de contratación, en orden, con la columna del catálogo legacy que
     * guarda su duración. El último termina en la fecha en que el paquete se necesita en obra.
     */
    public const PASOS = [
        ['paso' => 'Elaboración de pliegos', 'col' => 'diasElaboracionPliegos'],
        ['paso' => 'Entrega de pliegos', 'col' => 'diasEntregaPliegos'],
        ['paso' => 'Recibo de propuestas', 'col' => 'diasReciboPropuestas'],
        ['paso' => 'Cuadros comparativos', 'col' => 'diasCuadrosComparativos'],
        ['paso' => 'Legalización', 'col' => 'diasLegalizacionContrato'],
        ['paso' => 'Fabricación', 'col' => 'diasFabricacion'],
        ['paso' => 'Insumos en obra', 'col' => 'diasInsumosObra'],
    ];

    /**
     * Calcula el plan de todos los paquetes amarrados: resta hacia atrás desde la fecha del frente.
     *
     * En días calendario, porque así están escritos los números del catálogo: quien puso «25 días de
     * cuadros comparativos» pensaba en semanas de calendario, no en jornadas laborales.
     */
    public function calcular(int $projectId, string $usuario): array
    {
        $amarres = $this->amarres($projectId);
        if ($amarres === []) {
            return ['ok' => true, 'calculados' => 0, 'sinDuracion' => 0];
        }
        $medianas = $this->medianasPorTipo();
        $calculados = 0;
        $sinDuracion = 0;

        foreach ($amarres as $paqueteId => $a) {
            $paq = $this->db->query(
                'SELECT p.id, p.tipo_negociacion, p.duracion_ref, d.diasElaboracionPliegos, d.diasEntregaPliegos,
                        d.diasReciboPropuestas, d.diasCuadrosComparativos, d.diasLegalizacionContrato,
                        d.diasFabricacion, d.diasInsumosObra
                 FROM general_paquetes_contratacion p
                 LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
                 WHERE p.id = ? AND p.activo = 1',
                [$paqueteId],
            )->fetch(\PDO::FETCH_ASSOC);
            if ($paq === false) {
                continue;
            }

            $provisional = $paq['duracion_ref'] === null;
            if ($provisional) {
                $sinDuracion++;
                $total = $medianas[$paq['tipo_negociacion']] ?? 90;
                // Sin desglose real, la mediana se reparte proporcional al reparto típico del catálogo.
                $dias = self::repartirMediana($total);
            } else {
                $dias = [];
                foreach (self::PASOS as $p) {
                    $dias[] = (int) $paq[$p['col']];
                }
            }
            $total = array_sum($dias);

            $ancla = new \DateTimeImmutable($a['fechaAncla']);
            $cursor = $ancla->modify(sprintf('-%d days', $total));
            $arranque = $cursor->format('Y-m-d');

            $this->db->query(
                'INSERT INTO pdc_plan_paquete
                    (project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales,
                     duracion_ref, duracion_provisional, responsable, calculado_por, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, COALESCE((SELECT responsable FROM (SELECT responsable FROM pdc_plan_paquete
                     WHERE project_id = ? AND paquete_id = ?) x), ""), ?, NOW())
                 ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), fecha_ancla = VALUES(fecha_ancla),
                    fecha_arranque = VALUES(fecha_arranque), dias_totales = VALUES(dias_totales),
                    duracion_ref = VALUES(duracion_ref), duracion_provisional = VALUES(duracion_provisional),
                    calculado_por = VALUES(calculado_por), updated_at = NOW()',
                [
                    $projectId, $paqueteId, $a['uniqueId'], $a['fechaAncla'], $arranque, $total,
                    $paq['duracion_ref'], $provisional ? 1 : 0, $projectId, $paqueteId, $usuario,
                ],
            );

            // Los pasos se reemplazan enteros: recalcular no debe acumular.
            $this->db->query('DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);
            foreach (self::PASOS as $i => $p) {
                $ini = $cursor;
                $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
                $this->db->query(
                    'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso, dias, fecha_inicio, fecha_fin)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$projectId, $paqueteId, $i, $p['paso'], $dias[$i], $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
                );
            }
            $calculados++;
        }
        return ['ok' => true, 'calculados' => $calculados, 'sinDuracion' => $sinDuracion];
    }

    /** Mediana del proceso completo por tipo de negociación, entre los paquetes que sí tienen duración. */
    private function medianasPorTipo(): array
    {
        $rows = $this->db->query(
            'SELECT p.tipo_negociacion t,
                    (d.diasElaboracionPliegos + d.diasEntregaPliegos + d.diasReciboPropuestas
                     + d.diasCuadrosComparativos + d.diasLegalizacionContrato + d.diasFabricacion
                     + d.diasInsumosObra) tot
             FROM general_paquetes_contratacion p
             JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE p.activo = 1 ORDER BY tot',
        )->fetchAll(\PDO::FETCH_ASSOC);
        $porTipo = [];
        foreach ($rows as $r) {
            $porTipo[(string) $r['t']][] = (int) $r['tot'];
        }
        $out = [];
        foreach ($porTipo as $t => $v) {
            $n = count($v);
            $out[$t] = (int) round($n % 2 === 1 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2);
        }
        return $out;
    }

    /** Reparte una duración total entre los siete pasos, con el peso típico del catálogo. */
    private static function repartirMediana(int $total): array
    {
        $pesos = [0.08, 0.09, 0.08, 0.24, 0.20, 0.16, 0.15];
        $dias = [];
        $acum = 0;
        foreach ($pesos as $i => $w) {
            $d = $i === count($pesos) - 1 ? $total - $acum : (int) round($total * $w);
            $dias[] = max(0, $d);
            $acum += $d;
        }
        return $dias;
    }

    /** El plan del proyecto, con los vencidos primero. */
    public function plan(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT pp.paquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque, pp.dias_totales,
                    pp.duracion_provisional, pp.responsable, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             WHERE pp.project_id = ?
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pasos = [];
        foreach ($this->db->query(
            'SELECT paquete_id, orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
             WHERE project_id = ? ORDER BY paquete_id, orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $pasos[(int) $p['paquete_id']][] = [
                'orden' => (int) $p['orden'], 'paso' => (string) $p['paso'], 'dias' => (int) $p['dias'],
                'fechaInicio' => (string) $p['fecha_inicio'], 'fechaFin' => (string) $p['fecha_fin'],
            ];
        }

        $hoy = new \DateTimeImmutable('today');
        $out = [];
        foreach ($rows as $r) {
            $arranque = new \DateTimeImmutable((string) $r['fecha_arranque']);
            $retraso = $arranque < $hoy ? (int) $hoy->diff($arranque)->days : 0;
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'nombre' => (string) $r['nombre'],
                'tipoNegociacion' => (string) $r['tipo_negociacion'],
                'modalidad' => (string) $r['modalidad_contratacion'],
                'frenteNombre' => (string) ($r['frente_nombre'] ?? ''),
                'uniqueId' => (int) $r['unique_id'],
                'fechaAncla' => (string) $r['fecha_ancla'],
                'fechaArranque' => (string) $r['fecha_arranque'],
                'diasTotales' => (int) $r['dias_totales'],
                'duracionProvisional' => (int) $r['duracion_provisional'] === 1,
                'responsable' => (string) $r['responsable'],
                'diasRetraso' => $retraso,
                'pasos' => $pasos[(int) $r['paquete_id']] ?? [],
            ];
        }
        // Los vencidos primero, del más atrasado al menos; luego el resto por fecha de arranque.
        usort($out, static function (array $a, array $b): int {
            if ($a['diasRetraso'] !== $b['diasRetraso']) {
                return $b['diasRetraso'] <=> $a['diasRetraso'];
            }
            return strcmp($a['fechaArranque'], $b['fechaArranque']);
        });
        return $out;
    }
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0. Si el arranque no da `2026-05-23`, revisar que la resta use `-87 days`
sobre la fecha ancla y que los pasos se acumulen desde ahí.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): programación hacia atrás en días calendario, paso a paso"
```

---

### Task 7: Detectar que el plan envejeció

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Modify: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: `pdc_paquete_frente.fecha_ancla`, `frentesDisponibles()`.
- Produces: `public function desfases(int $projectId): array` → lista de `['paqueteId','nombre','frenteNombre','fechaGuardada','fechaActual','diasMovidos']`.

- [ ] **Step 1: Escribir el test que falla**

Añadir antes del `echo $failures`:

```php
// --- desfases: el cronograma se movió y el plan quedó viejo ---
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');   // ancla 2026-08-18
$assert($svc->desfases($P) === [], 'Recién amarrado no hay desfase.');

$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-09-08" WHERE project_id = ? AND unique_id = 9001 AND Semana = 2', [$P]);
$d = $svc->desfases($P);
$assert(count($d) === 1, 'Mover el frente genera un desfase. Hay ' . count($d));
$assert($d[0]['fechaGuardada'] === '2026-08-18' && $d[0]['fechaActual'] === '2026-09-08',
    'El desfase dice de qué fecha a cuál se movió.');
$assert($d[0]['diasMovidos'] === 21, 'Cuenta los días que se corrió: ' . $d[0]['diasMovidos']);
```

- [ ] **Step 2: Correr el test y verlo fallar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `Call to undefined method ... desfases()`.

- [ ] **Step 3: Implementar desfases**

```php
    /**
     * Amarres cuya fecha ancla ya no coincide con la del cronograma: el frente se movió y el plan
     * quedó viejo. No se recalcula solo — una fecha que ya se comunicó a un proveedor no debe
     * cambiar en silencio; aplicar el desfase es un acto explícito.
     */
    public function desfases(int $projectId): array
    {
        $actual = [];
        foreach ($this->frentesDisponibles($projectId) as $f) {
            $actual[$f['uniqueId']] = $f;
        }
        $rows = $this->db->query(
            'SELECT f.paquete_id, f.unique_id, f.frente_nombre, f.fecha_ancla, p.nombre
             FROM pdc_paquete_frente f
             JOIN general_paquetes_contratacion p ON p.id = f.paquete_id
             WHERE f.project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $f = $actual[(int) $r['unique_id']] ?? null;
            if ($f === null || $f['fechaInicio'] === (string) $r['fecha_ancla']) {
                continue;
            }
            $guardada = new \DateTimeImmutable((string) $r['fecha_ancla']);
            $ahora = new \DateTimeImmutable($f['fechaInicio']);
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'nombre' => (string) $r['nombre'],
                'frenteNombre' => (string) $r['frente_nombre'],
                'fechaGuardada' => (string) $r['fecha_ancla'],
                'fechaActual' => $f['fechaInicio'],
                'diasMovidos' => (int) $guardada->diff($ahora)->days * ($ahora < $guardada ? -1 : 1),
            ];
        }
        return $out;
    }
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: `=== OK ===`, exit 0.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_plan_fechas.php
git commit -m "feat(pdc): detectar el desfase cuando el cronograma se reprograma"
```

---

### Task 8: Los endpoints

**Files:**
- Create: `src/Controllers/Api/PlanComprasPlanController.php`
- Modify: `public/index.php` (tras la línea 217, junto a las rutas de paquetes)

**Interfaces:**
- Consumes: todo `PlanFechasService`.
- Produces: `GET /plan-compras/api/plan/frentes`, `GET /plan-compras/api/plan/sugerencias`, `GET /plan-compras/api/plan`, `GET /plan-compras/api/plan/desfases`, `POST /plan-compras/api/plan/amarrar`, `POST /plan-compras/api/plan/calcular`, `POST /plan-compras/api/plan/responsable`.

- [ ] **Step 1: Escribir el controller**

Copiar la estructura exacta de `src/Controllers/Api/PlanComprasPaquetesController.php`: mismo trait
`PlanComprasJsonRespuestas`, mismos `guardLectura()` (`lps.paquetes_contratacion.ver`) y
`guardEscritura()` (`lps.paquetes_contratacion.editar`), mismos helpers `body()` y `usuario()`.

Métodos, todos devolviendo por `$this->ok([...])`:

- `frentes()` → `['frentes' => $this->service->frentesDisponibles($projectId)]`
- `sugerencias()` → `['sugerencias' => $this->service->sugerirFrentes($projectId)]`
- `plan()` → `['plan' => $this->service->plan($projectId), 'amarres' => $this->service->amarres($projectId)]`
- `desfases()` → `['desfases' => $this->service->desfases($projectId)]`
- `amarrar()` → valida `paqueteId` y `uniqueId` con `filter_var(..., FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])`; si el servicio devuelve `ok=false`, responder `$this->fail($r['code'], 'El paquete o el frente no existen.', 422)`
- `calcular()` → `$this->ok($this->service->calcular($projectId, $this->usuario()))`
- `responsable()` → `UPDATE pdc_plan_paquete SET responsable = ? WHERE project_id = ? AND paquete_id = ?`, con el nombre recortado a 100 caracteres

- [ ] **Step 2: Registrar las rutas**

En `public/index.php`, después de la línea 217:

```php
$router->get('/plan-compras/api/plan/frentes', [\App\Controllers\Api\PlanComprasPlanController::class, 'frentes']);
$router->get('/plan-compras/api/plan/sugerencias', [\App\Controllers\Api\PlanComprasPlanController::class, 'sugerencias']);
$router->get('/plan-compras/api/plan/desfases', [\App\Controllers\Api\PlanComprasPlanController::class, 'desfases']);
$router->get('/plan-compras/api/plan', [\App\Controllers\Api\PlanComprasPlanController::class, 'plan']);
$router->post('/plan-compras/api/plan/amarrar', [\App\Controllers\Api\PlanComprasPlanController::class, 'amarrar']);
$router->post('/plan-compras/api/plan/calcular', [\App\Controllers\Api\PlanComprasPlanController::class, 'calcular']);
$router->post('/plan-compras/api/plan/responsable', [\App\Controllers\Api\PlanComprasPlanController::class, 'responsable']);
```

**Ojo con el orden:** `/plan-compras/api/plan` va **después** de las rutas con sufijo, o FastRoute
puede capturarlas antes.

- [ ] **Step 3: Comprobar que responden**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasPlanController.php src/Services/Pdc/PlanFechasService.php --no-progress
```

Esperado: `[OK] No errors`. Las rutas se verifican de verdad en la Task 10, desde el navegador.

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/Api/PlanComprasPlanController.php public/index.php
git commit -m "feat(pdc): endpoints del plan con fechas"
```

---

### Task 9: La pestaña «Plan»

**Files:**
- Create: `plan-de-compras/src/pages/PlanFechas.tsx`
- Create: `plan-de-compras/src/lib/planFechas.ts`
- Create: `plan-de-compras/src/lib/planFechas.test.ts`
- Modify: `plan-de-compras/src/lib/types.ts`
- Modify: el router de la SPA y la navegación de Ensamble (ver `PaquetesContratacion.tsx` y donde se declaren las rutas `#/ensamble/*`)

**Interfaces:**
- Consumes: `GET /plan-compras/api/plan`, `GET /plan-compras/api/plan/desfases`.
- Produces: la ruta `#/ensamble/plan`.

- [ ] **Step 1: Escribir el test de la lógica pura**

Crear `src/lib/planFechas.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { estadoFila, resumenPlan } from './planFechas'
import type { FilaPlan } from './types'

const fila = (over: Partial<FilaPlan> = {}): FilaPlan => ({
  paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'orden_compra',
  frenteNombre: 'ESTRUCTURA', uniqueId: 9001, fechaAncla: '2026-08-18', fechaArranque: '2026-05-23',
  diasTotales: 87, duracionProvisional: false, responsable: '', diasRetraso: 0, pasos: [],
  ...over,
})

describe('estadoFila', () => {
  it('lo vencido es «vencido», con sus días', () => {
    expect(estadoFila(fila({ diasRetraso: 65 }))).toEqual({ clave: 'vencido', etiqueta: '65 días de retraso' })
  })

  it('sin retraso es «en plazo»', () => {
    expect(estadoFila(fila()).clave).toBe('en-plazo')
  })

  it('la duración provisional se distingue, aunque esté en plazo', () => {
    expect(estadoFila(fila({ duracionProvisional: true })).clave).toBe('provisional')
  })

  it('vencido manda sobre provisional: es lo urgente', () => {
    expect(estadoFila(fila({ diasRetraso: 10, duracionProvisional: true })).clave).toBe('vencido')
  })
})

describe('resumenPlan', () => {
  it('cuenta vencidos, provisionales y total', () => {
    const r = resumenPlan([fila({ diasRetraso: 5 }), fila({ duracionProvisional: true }), fila()])
    expect(r).toEqual({ total: 3, vencidos: 1, provisionales: 1 })
  })

  it('un plan vacío no rompe', () => {
    expect(resumenPlan([])).toEqual({ total: 0, vencidos: 0, provisionales: 0 })
  })
})
```

- [ ] **Step 2: Correr y verlo fallar**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
npx vitest run src/lib/planFechas.test.ts
```

Esperado: falla por no existir `./planFechas`.

- [ ] **Step 3: Implementar la lógica y los tipos**

En `src/lib/types.ts` añadir:

```ts
export type PasoPlan = { orden: number; paso: string; dias: number; fechaInicio: string; fechaFin: string }

export type FilaPlan = {
  paqueteId: number
  nombre: string
  tipoNegociacion: string
  modalidad: string
  frenteNombre: string
  uniqueId: number
  fechaAncla: string
  fechaArranque: string
  diasTotales: number
  duracionProvisional: boolean
  responsable: string
  diasRetraso: number
  pasos: PasoPlan[]
}

export type Desfase = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  fechaGuardada: string
  fechaActual: string
  diasMovidos: number
}
```

Crear `src/lib/planFechas.ts`:

```ts
import type { FilaPlan } from './types'

export type EstadoFila = { clave: 'vencido' | 'provisional' | 'en-plazo'; etiqueta: string }

/**
 * El estado que se pinta en cada fila. Lo vencido manda sobre lo provisional: un plazo aproximado
 * importa, pero una contratación que debió arrancar hace dos meses importa más.
 */
export function estadoFila(f: FilaPlan): EstadoFila {
  if (f.diasRetraso > 0) {
    return { clave: 'vencido', etiqueta: `${f.diasRetraso} días de retraso` }
  }
  if (f.duracionProvisional) {
    return { clave: 'provisional', etiqueta: 'plazo estimado' }
  }
  return { clave: 'en-plazo', etiqueta: 'en plazo' }
}

export function resumenPlan(filas: FilaPlan[]): { total: number; vencidos: number; provisionales: number } {
  return {
    total: filas.length,
    vencidos: filas.filter((f) => f.diasRetraso > 0).length,
    provisionales: filas.filter((f) => f.duracionProvisional).length,
  }
}
```

- [ ] **Step 4: Correr y verlo pasar**

```bash
npx vitest run
```

Esperado: todos los ficheros en verde, incluidos los 70 tests previos.

- [ ] **Step 5: Construir la vista y el amarre**

Crear `src/pages/PlanFechas.tsx` siguiendo el patrón de `PaquetesContratacion.tsx` (carga con
`apiGet`, AG Grid Community, `useReducer` para el estado). Tres bloques en la misma pantalla:

**1 · El plan.** Tabla con una fila por paquete: nombre, frente amarrado, fecha de arranque, fecha de
necesidad en obra, días totales, responsable y estado (`estadoFila`). Los vencidos llegan primero
porque el servidor ya los ordena así — no reordenar en el cliente. Fila expandible con los siete
pasos. Botón «Recalcular» → `POST /plan-compras/api/plan/calcular`.

**2 · Sin frente.** Los paquetes que generan proceso y no están en `amarres`, ordenados por cuantía
descendente. Cada uno con un `<select>` de frentes **preseleccionado con la propuesta** del motor
—mismo criterio que el asistente de insumos de A3.6— y su chip de confianza. Al elegir, llamar a
`POST /plan-compras/api/plan/amarrar` con la procedencia calculada: si el frente elegido coincide con
el propuesto, `{origen, confianza, evidencia, confirmado: true}`; si no, sin procedencia. Reutilizar
`procedenciaDeAsignacion` de `paqueteWizardState.ts` como referencia del criterio, no del tipo.

**Frentes homónimos:** `frentesDisponibles` puede traer dos con el mismo nombre y distinta fecha
—`PISOS Y ENCHAPES` el 12-may y el 8-jul—, así que cada opción del desplegable debe mostrar la fecha:
`PISOS Y ENCHAPES — 2027-05-12`. Sin eso el usuario no puede distinguirlos.

**3 · Desfases.** Los que devuelve `GET /plan-compras/api/plan/desfases`, con «se movió de X a Y,
N días» y un botón para recalcular ese paquete. No se aplica solo.

Registrar la ruta `#/ensamble/plan` y añadir «Plan» al final de la navegación de Ensamble, junto a
«Paquetes» — el mismo sitio donde se declaran las demás rutas `#/ensamble/*`.

- [ ] **Step 6: Compilar**

```bash
npm run build
```

Esperado: build limpio, `dist/assets/pdc.js` y `pdc.css` regenerados.

- [ ] **Step 7: Commit**

```bash
git add src/lib/planFechas.ts src/lib/planFechas.test.ts src/lib/types.ts src/pages/PlanFechas.tsx
git commit -m "feat(pdc): pestaña Plan con las fechas por paquete"
```

---

### Task 10: Da Porto de extremo a extremo

**Files:**
- Modify: `lps-aia-pdc/public/pdc-app/` (bundle)
- Create: `lps-aia-pdc/tests/browser/pdc-v2-plan.spec.mjs`

- [ ] **Step 1: Desplegar el bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
npm run build
cp -R dist/. "/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/"
```

**No** usar `npm run sync`: apunta al worktree principal, que es de otras sesiones.

- [ ] **Step 2: Suite completa del backend**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
for t in test_pdc_v2_plan_fechas test_pdc_v2_paquetes test_pdc_v2_paquetes_motor \
         test_pdc_v2_brecha_daporto test_global_table_safety test_global_table_reconciliation; do
  printf "%-34s " "$t"
  docker compose exec -T app php tests/$t.php > /tmp/a4-$t.txt 2>&1
  echo "exit=$? · $(grep -cE '^PASS' /tmp/a4-$t.txt) PASS · $(grep -cE '^FAIL' /tmp/a4-$t.txt) FAIL"
done
docker compose exec -T app vendor/bin/phpstan analyse src --no-progress
```

Esperado: todo exit 0, PHPStan `[OK] No errors`, y **la brecha en 7** — A4 no toca el motor.

- [ ] **Step 3: Generar el plan real de Da Porto**

Amarrar unos cuantos paquetes desde el navegador (`localhost:8091`, pestaña Plan) y recalcular.
Comprobar con una consulta que el concreto sale como el spec predice:

```bash
docker compose exec -T app php -r '
require "/var/www/html/vendor/autoload.php"; require "/var/www/html/src/Core/Database.php";
$db = Database::getInstance();
foreach ($db->query("SELECT p.nombre, pp.fecha_arranque, pp.fecha_ancla, pp.dias_totales, pp.duracion_provisional
  FROM pdc_plan_paquete pp JOIN general_paquetes_contratacion p ON p.id=pp.paquete_id
  WHERE pp.project_id=73 ORDER BY pp.fecha_arranque LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $r)
  echo json_encode($r, JSON_UNESCAPED_UNICODE), "\n";'
```

Esperado: si se amarró `Suministro CONCRETO` al frente `ESTRUCTURA`, su `fecha_arranque` debe ser
**2026-05-23** con `dias_totales` 87 — 65 días de retraso respecto a hoy.

- [ ] **Step 4: Verificación visual**

Abrir el navegador integrado en `http://localhost:8091/plan-compras#/ensamble/plan` y comprobar que
los vencidos salen primero y en rojo, que los pasos se despliegan y que los desfases aparecen en su
bloque. Tomar una captura.

- [ ] **Step 5: e2e no destructivo**

Crear `tests/browser/pdc-v2-plan.spec.mjs` siguiendo `pdc-v2-modalidades.spec.mjs` (que es el patrón
**no destructivo**: no importa presupuestos, solo navega y lee). Comprobar que la pestaña Plan carga,
que la tabla trae filas y que el bloque de vencidos existe.

```bash
E2E_BASE_URL=http://localhost:8091 ./node_modules/.bin/playwright test tests/browser/pdc-v2-plan.spec.mjs --workers=1
```

- [ ] **Step 6: Commit y push**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add public/pdc-app tests/browser/pdc-v2-plan.spec.mjs
git commit -m "feat(pdc): A4 verificado sobre Da Porto"
git fetch origin && git log --oneline HEAD..origin/main   # main no debe haber avanzado
git push origin pdc-dev:main
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git push origin main
```

---

## Notas para quien ejecute

- **Los tests PHP son autoejecutables**, no PHPUnit: imprimen `PASS:`/`FAIL:` y salen con 0/1.
  El proyecto de pruebas de A4 es `999903` para no chocar con `999901`/`999902` de los otros tests.
- **Limpieza:** cada test limpia lo suyo al empezar y al terminar. Si un test se cae a medias, deja
  basura en el proyecto 999903; volver a correrlo la limpia.
- **Nunca correr `tests/browser/pdc-v2-paquetes.spec.mjs`** sin respaldar antes: es destructivo,
  arrasa las asignaciones de Da Porto y sustituye la versión activa.
- **Si una tarea se alarga**, el corte natural es entre la Task 7 y la Task 8: el backend completo y
  probado es entregable por sí solo, y la UI puede ir en una segunda tanda.
