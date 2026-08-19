---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-28
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-28-a41-pasos-configurables.md
resumen: Que cada proyecto pueda definir los pasos de su proceso de contratación (agregar, reordenar, apagar, renombrar, incluidas las variantes Licify y Aprobación del…
---

# A4.1 — Pasos de contratación configurables por proyecto · Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cada proyecto pueda definir los pasos de su proceso de contratación (agregar, reordenar, apagar, renombrar, incluidas las variantes Licify y Aprobación del cliente) y que el plan de fechas se recalcule con ellos, sin que un proyecto no configurado —Da Porto— cambie una sola fecha.

**Architecture:** Un catálogo global de pasos (`general_pasos_contratacion`) define la identidad y el respaldo de duración de cada paso; una tabla por proyecto (`pdc_proyecto_pasos`) dice cuáles usa una obra, en qué orden, con qué alias y con cuántos días fijos. Un servicio nuevo, `PasosContratacionService`, es la única fuente de verdad de «qué pasos tiene esta obra»: sin filas devuelve la constante de código `PlanFechasService::PASOS`, que es la garantía de cero regresión. `PlanFechasService::calcular()` deja de recorrer siete pasos fijos y recorre esa lista. `pdc_plan_paso` gana `paso_id` y su clave única pasa a ser `(project_id, paquete_id, paso_id)`, para que una fila siga al paso y no a la posición.

**Tech Stack:** PHP 8.2 (lps-aia, sin PHPUnit — tests autoejecutables), MySQL 8, FastRoute, React + Vite + AG Grid Community + Vitest (repo `plan-de-compras`), Playwright.

## Global Constraints

- **Worktree:** todo el trabajo PHP va en un worktree **propio**, creado con `git worktree add` desde `origin/main`. **NO** hacer `git checkout -b` en `/Volumes/Crucial X6/Developer/lps-aia-pdc`: lo comparten tres sesiones más.
- **Frontera de archivos.** En `PlanFechasService.php` solo se pueden tocar: `PASOS`, `calcular()`, `medianasPorTipo()`, `pesosDelCatalogo()`, `PESOS_REPARTO`, `repartirMediana()` y lo que `plan()` lea de pasos. **NO tocar** `frentesDisponibles()`, `semanaYFrentes()`, `mejorFrente()`, `subcapitulosDePaquete()`, `sugerirFrentes()`, `amarrar()`, `desamarrar()` — son de otra tarea en curso.
- **SPA:** la pantalla nueva va en un archivo nuevo bajo `src/pages/`. En `src/pages/PlanFechas.tsx` solo se permite añadir un import y un enlace; **no** reestructurar el archivo ni tocar la pestaña «Sin frente».
- **Fronteras de pasos `[inicio, fin)`:** no cambiar. Es contrato con B1 y ya hay datos guardados con esa convención (docblock de `calcular()`, líneas ~648-659).
- **Cero regresión:** un proyecto sin filas en `pdc_proyecto_pasos` usa exactamente los siete pasos actuales y produce exactamente las mismas fechas.
- **Anunciar todo `--apply` antes de correrlo.** La base es compartida con otras tres sesiones.
- **Tablas:** operativas con `project_id int NOT NULL` + índice liderado por `project_id` + `utf8mb4_unicode_ci`; catálogos `general_*` sin `project_id`.
- **Envelope JSON:** `{ok, data|error}`. CSRF form key `plan_compras_v2`.
- **Permisos:** lectura `lps.paquetes_contratacion.ver`; escritura de pasos `lps.paquetes_contratacion.reglas` (ya existe, creado en `20260726_pdc_v2_permiso_reglas_motor.php`).
- **Puertos del worktree:** app `8091`, adminer `8092`, db `3308`.
- **Idioma:** comentarios y documentación en español; identificadores en su idioma original.

---

### Task 1: Migración — catálogo de pasos, configuración por proyecto e identidad en `pdc_plan_paso`

**Files:**
- Create: `database/migrations/20260728_pdc_v2_pasos_configurables.php` (en el worktree nuevo de lps-aia)

**Interfaces:**
- Consumes: nada.
- Produces: tablas `general_pasos_contratacion` (con 9 filas sembradas) y `pdc_proyecto_pasos`; columna `pdc_plan_paso.paso_id` poblada en las 77 filas existentes; clave única `uq_pps_proyecto_paquete_paso (project_id, paquete_id, paso_id)`; `pdc_plan_paso.paso` ensanchada a `VARCHAR(120)`. Las claves sembradas son exactamente: `elaboracion_pliegos`, `entrega_pliegos`, `recibo_propuestas`, `cuadros_comparativos`, `legalizacion`, `fabricacion`, `insumos_obra`, `licify`, `aprobacion_cliente`.

- [ ] **Step 1: Confirmar el estado del worktree compartido**

**Decisión del usuario (2026-07-28):** se trabaja **en el worktree compartido**
`/Volumes/Crucial X6/Developer/lps-aia-pdc`, en la rama que esté, con **commits que listan archivo por
archivo**. Nada de `git add -A`, nada de `git checkout -b`, nada de `git stash`. Motivo: hay un solo
Docker y una sola base, y los datos reales de Da Porto son lo único con lo que se puede demostrar la
cero regresión. En este árbol conviven otras tres sesiones.

**Rutas de todos los comandos de este plan:** todas apuntan ya a
`/Volumes/Crucial X6/Developer/lps-aia-pdc`.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git branch --show-current && git status --porcelain
```

Anotar la rama y la lista de archivos sucios **antes de empezar**. Al final, los únicos archivos
nuevos o modificados por esta tarea deben ser los de las secciones «Files» de este plan; cualquier
otro es de otra sesión y **no se toca ni se commitea**.

- [ ] **Step 2: Escribir la migración**

Crear `database/migrations/20260728_pdc_v2_pasos_configurables.php`. Sigue el patrón de `20260725_pdc_v2_modalidad_contratacion.php` (DDL en `.php` con guardas de `information_schema`) y de `20260728_pdc_v2_duraciones_faltantes.php` (dry-run → `--apply`).

```php
<?php

// 20260728_pdc_v2_pasos_configurables.php
// PDC v2 / Fase A4.1 — los pasos del proceso de contratación dejan de estar escritos en el código.
//
// (A) general_pasos_contratacion — catálogo GLOBAL de pasos posibles. La `clave` es la identidad
//     estable del paso: es lo que viaja a pdc_plan_paso.paso_id y lo que permitirá comparar obras.
//     `col_legacy` dice de qué columna de general_dias_procesos_contratacion salen sus días POR
//     PAQUETE; NULL = paso sin respaldo legacy, que usa los días fijos de la obra.
// (B) pdc_proyecto_pasos — qué pasos usa una obra, en qué orden, con qué alias y días fijos.
//     CERO FILAS PARA UN PROYECTO = usa los siete de siempre. Por eso esta migración NO siembra
//     ninguna fila aquí: Da Porto (73) tiene que seguir dando exactamente las mismas fechas.
// (C) pdc_plan_paso.paso_id + cambio de clave única.
//     Hoy la única es (project_id, paquete_id, orden) y calcular() hace upsert por ella. Con pasos
//     reordenables eso corrompe datos en silencio: meter un paso en la posición 3 haría que el
//     upsert escriba encima de la fila que hoy es «Cuadros comparativos», y la fecha real que B1
//     cuelgue de esa fila pasaría a leerse como si fuera del paso nuevo. La clave pasa a ser
//     (project_id, paquete_id, paso_id): la fila sigue al paso, no a la posición.
//
// Orden deliberado: el backfill de paso_id corre ANTES del cambio de clave, y si quedara una sola
// fila sin paso_id la migración aborta sin tocar el índice.
//
// Uso:  php database/migrations/20260728_pdc_v2_pasos_configurables.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeTabla = static fn (Database $db, string $t): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    [$t],
)->fetchColumn() > 0;
$existeColumna = static fn (Database $db, string $t, string $c): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    [$t, $c],
)->fetchColumn() > 0;
$existeIndice = static fn (Database $db, string $t, string $i): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
    [$t, $i],
)->fetchColumn() > 0;

// clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default.
// Los siete primeros son copia literal de PlanFechasService::PASOS y PESOS_REPARTO: si divergen, el
// test test_pdc_v2_pasos_configurables.php falla a propósito.
$semilla = [
    ['elaboracion_pliegos', 'Elaboración de pliegos', 'diasElaboracionPliegos', null, 0.087872, 0],
    ['entrega_pliegos', 'Entrega de pliegos', 'diasEntregaPliegos', null, 0.121115, 1],
    ['recibo_propuestas', 'Recibo de propuestas', 'diasReciboPropuestas', null, 0.054079, 2],
    ['cuadros_comparativos', 'Cuadros comparativos', 'diasCuadrosComparativos', null, 0.189065, 3],
    ['legalizacion', 'Legalización', 'diasLegalizacionContrato', null, 0.178996, 4],
    ['fabricacion', 'Fabricación', 'diasFabricacion', null, 0.248792, 5],
    ['insumos_obra', 'Insumos en obra', 'diasInsumosObra', null, 0.120081, 6],
    // Los dos sin respaldo legacy. Licify: el histórico dice 0-2 días (docs/pdca-automatizacion-plan-compras.md:76).
    // Aprobación del cliente va entre Cuadros y Legalización, que es donde la tenían los dos proyectos
    // de la Variante B (2021-2 y 2024).
    ['licify', 'Ingreso a plataforma Licify', null, 1, null, 7],
    ['aprobacion_cliente', 'Aprobación del cliente', null, 15, null, 8],
];

/** Las siete claves por defecto, en el orden del proceso. El backfill mapea orden 0..6 a estas. */
$clavesDefault = ['elaboracion_pliegos', 'entrega_pliegos', 'recibo_propuestas',
    'cuadros_comparativos', 'legalizacion', 'fabricacion', 'insumos_obra'];

$faltaCatalogo = !$existeTabla($db, 'general_pasos_contratacion');
$faltaConfig = !$existeTabla($db, 'pdc_proyecto_pasos');
$faltaPasoId = !$existeColumna($db, 'pdc_plan_paso', 'paso_id');
$faltaClaveNueva = !$existeIndice($db, 'pdc_plan_paso', 'uq_pps_proyecto_paquete_paso');
$sobraClaveVieja = $existeIndice($db, 'pdc_plan_paso', 'uq_pps_proyecto_paquete_orden');
$anchoPaso = (int) ($db->query(
    "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'paso'",
)->fetchColumn() ?: 0);
$filasPlan = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso')->fetchColumn();

if (!$apply) {
    fwrite(STDOUT, "[DRY-RUN] general_pasos_contratacion: " . ($faltaCatalogo ? 'FALTA (se crea + 9 filas)' : 'ya existe') . "\n");
    fwrite(STDOUT, "          pdc_proyecto_pasos: " . ($faltaConfig ? 'FALTA (se crea, SIN filas)' : 'ya existe') . "\n");
    fwrite(STDOUT, "          pdc_plan_paso.paso_id: " . ($faltaPasoId ? "FALTA (se añade y se rellenan {$filasPlan} filas)" : 'ya existe') . "\n");
    fwrite(STDOUT, "          pdc_plan_paso.paso ancho {$anchoPaso}: " . ($anchoPaso < 120 ? 'se ensancha a 120' : 'ya basta') . "\n");
    fwrite(STDOUT, "          clave única: " . ($faltaClaveNueva ? 'se crea por paso_id' : 'ya está') . ($sobraClaveVieja ? '; se quita la de orden' : '') . "\n");
    fwrite(STDOUT, "          NINGUNA fila de configuración se siembra: los proyectos existentes no cambian de fechas.\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($faltaCatalogo) {
    $db->query(
        'CREATE TABLE general_pasos_contratacion (
           id INT NOT NULL AUTO_INCREMENT,
           clave VARCHAR(60) NOT NULL,
           nombre VARCHAR(120) NOT NULL,
           col_legacy VARCHAR(60) NULL,
           dias_sugeridos INT NULL,
           peso_reparto DECIMAL(9,6) NULL,
           orden_default INT NOT NULL DEFAULT 0,
           activo TINYINT(1) NOT NULL DEFAULT 1,
           creado_por VARCHAR(100) NOT NULL DEFAULT "",
           updated_at DATETIME NOT NULL,
           PRIMARY KEY (id),
           UNIQUE KEY uq_gpc_clave (clave)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    );
}
// Upsert por clave, no INSERT a secas: así el archivo converge también en un entorno donde la tabla
// ya existiera con parte de la semilla.
foreach ($semilla as [$clave, $nombre, $col, $dias, $peso, $orden]) {
    $db->query(
        'INSERT INTO general_pasos_contratacion
            (clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default, activo, creado_por, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, "migracion-a41", NOW())
         ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), col_legacy = VALUES(col_legacy),
            dias_sugeridos = VALUES(dias_sugeridos), peso_reparto = VALUES(peso_reparto),
            orden_default = VALUES(orden_default), updated_at = NOW()',
        [$clave, $nombre, $col, $dias, $peso, $orden],
    );
}

if ($faltaConfig) {
    $db->query(
        'CREATE TABLE pdc_proyecto_pasos (
           id BIGINT NOT NULL AUTO_INCREMENT,
           project_id INT NOT NULL,
           paso_id INT NOT NULL,
           orden INT NOT NULL,
           alias VARCHAR(120) NOT NULL DEFAULT "",
           dias_fijos INT NULL,
           -- Sin columna `activo` a propósito: la lista ES la configuración. «Apagar» un paso es
           -- sacarlo de la lista, y `guardar()` reescribe la lista entera en una transacción. Una
           -- bandera que nadie pone nunca en 0 sería peso muerto y una segunda forma de decir lo
           -- mismo, que es como se desincronizan las cosas.
           actualizado_por VARCHAR(100) NOT NULL DEFAULT "",
           updated_at DATETIME NOT NULL,
           PRIMARY KEY (id),
           UNIQUE KEY uq_pps_proyecto_paso (project_id, paso_id),
           KEY idx_pps_proyecto_orden (project_id, orden),
           CONSTRAINT fk_pps_paso FOREIGN KEY (paso_id) REFERENCES general_pasos_contratacion (id) ON DELETE RESTRICT
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    );
}

if ($anchoPaso > 0 && $anchoPaso < 120) {
    // Un alias de obra puede llegar a 120; con los 60 de hoy se truncaría en silencio al escribir el plan.
    $db->query('ALTER TABLE pdc_plan_paso MODIFY COLUMN paso VARCHAR(120) NOT NULL');
}

if ($faltaPasoId) {
    $db->query('ALTER TABLE pdc_plan_paso ADD COLUMN paso_id INT NULL AFTER orden');
}

// Backfill por posición: las filas que existen hoy son todas del proceso de siete pasos.
foreach ($clavesDefault as $i => $clave) {
    $db->query(
        'UPDATE pdc_plan_paso p
         JOIN general_pasos_contratacion g ON g.clave = ?
         SET p.paso_id = g.id
         WHERE p.orden = ? AND p.paso_id IS NULL',
        [$clave, $i],
    );
}

$huerfanas = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE paso_id IS NULL')->fetchColumn();
if ($huerfanas > 0) {
    fwrite(STDERR, "[ABORTA] quedan {$huerfanas} filas de pdc_plan_paso sin paso_id (orden > 6 inesperado).\n");
    fwrite(STDERR, "         NO se cambió la clave única. Revisa esas filas antes de reintentar.\n");
    exit(1);
}

if ($faltaClaveNueva) {
    $db->query('ALTER TABLE pdc_plan_paso ADD UNIQUE KEY uq_pps_proyecto_paquete_paso (project_id, paquete_id, paso_id)');
}
if ($sobraClaveVieja) {
    // Se sustituye por un índice NO único: sigue sirviendo para ordenar, pero deja de impedir que
    // dos pasos distintos compartan posición durante un reordenamiento.
    $db->query('ALTER TABLE pdc_plan_paso DROP INDEX uq_pps_proyecto_paquete_orden');
    if (!$existeIndice($db, 'pdc_plan_paso', 'idx_pps_proyecto_paquete_orden')) {
        $db->query('ALTER TABLE pdc_plan_paso ADD KEY idx_pps_proyecto_paquete_orden (project_id, paquete_id, orden)');
    }
}
if (!$existeIndice($db, 'pdc_plan_paso', 'fk_pps_paso')) {
    $db->query('ALTER TABLE pdc_plan_paso ADD CONSTRAINT fk_pps_paso FOREIGN KEY (paso_id) REFERENCES general_pasos_contratacion (id) ON DELETE RESTRICT');
}

$pasos = (int) $db->query('SELECT COUNT(*) FROM general_pasos_contratacion')->fetchColumn();
$config = (int) $db->query('SELECT COUNT(*) FROM pdc_proyecto_pasos')->fetchColumn();
fwrite(STDOUT, "[APLICADO] catálogo con {$pasos} pasos; {$config} filas de configuración por proyecto (cero = todos usan el proceso por defecto); {$filasPlan} filas de plan con identidad de paso.\n");
exit(0);
```

- [ ] **Step 3: Correr el dry-run y leerlo**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php database/migrations/20260728_pdc_v2_pasos_configurables.php
```

Esperado: las tres tablas marcadas como FALTA, `77` filas a rellenar, y la línea «NINGUNA fila de configuración se siembra».

- [ ] **Step 4: Anunciar el `--apply` al usuario y aplicarlo**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php database/migrations/20260728_pdc_v2_pasos_configurables.php --apply
```

Esperado: `[APLICADO] catálogo con 9 pasos; 0 filas de configuración por proyecto ...; 77 filas de plan con identidad de paso.`

- [ ] **Step 5: Verificar que es idempotente y que el esquema quedó bien**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php database/migrations/20260728_pdc_v2_pasos_configurables.php --apply && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -D "$MYSQL_DATABASE" -e "SHOW CREATE TABLE pdc_plan_paso\G SELECT COUNT(*) sin_paso_id FROM pdc_plan_paso WHERE paso_id IS NULL;"'
```

Esperado: segunda corrida sin errores; `uq_pps_proyecto_paquete_paso` presente, `uq_pps_proyecto_paquete_orden` ausente, `paso` en `varchar(120)`, `sin_paso_id = 0`.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add database/migrations/20260728_pdc_v2_pasos_configurables.php && git commit -m "feat(pdc): los pasos de contratación tienen catálogo, configuración por obra e identidad propia"
```

---

### Task 2: `PasosContratacionService` — la única fuente de verdad de «qué pasos tiene esta obra»

**Files:**
- Create: `src/Services/Pdc/PasosContratacionService.php`
- Create: `tests/test_pdc_v2_pasos_configurables.php`
- Modify: `src/Services/Pdc/PlanFechasService.php` (solo la constante `PASOS`: se le añade `clave` a cada entrada)

**Interfaces:**
- Consumes: las tablas de la Task 1.
- Produces:
  - `PasosContratacionService::__construct(\Database $db)`
  - `catalogo(): list<array{id:int,clave:string,nombre:string,colLegacy:?string,diasSugeridos:?int,peso:?float,ordenDefault:int}>`
  - `deProyecto(int $projectId): list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}>`
  - `configurado(int $projectId): bool`
  - `guardar(int $projectId, array $pasos, string $usuario): array{ok:bool,code?:string,mensaje?:string,pasos?:int}`
  - `restablecer(int $projectId): void`
  - `PasosContratacionService::COLUMNAS_LEGACY` — lista blanca de las 7 columnas legales.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_pasos_configurables.php` con la cabecera del patrón del repo (ver `tests/test_pdc_v2_plan_fechas.php:1-20`) y este primer bloque:

```php
<?php
// tests/test_pdc_v2_pasos_configurables.php — A4.1: pasos configurables, sobre MySQL real.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PasosContratacionService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999905; // proyecto de pruebas propio de A4.1
$svc = new PasosContratacionService($db);

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$P]);
};
$limpiar();

// ── El catálogo y la constante de código no pueden divergir ──────────────────
$cat = $svc->catalogo();
$porClave = [];
foreach ($cat as $p) { $porClave[$p['clave']] = $p; }
$assert(count($cat) >= 9, 'El catálogo tiene al menos los 9 pasos sembrados. Dio ' . count($cat));
foreach (PlanFechasService::PASOS as $i => $p) {
    $c = $porClave[$p['clave']] ?? null;
    $assert($c !== null && $c['colLegacy'] === $p['col'],
        "El catálogo y PASOS coinciden en la columna legacy de «{$p['paso']}».");
    $assert($c !== null && abs((float) $c['peso'] - PlanFechasService::PESOS_REPARTO[$i]) < 0.000001,
        "El catálogo y PESOS_REPARTO coinciden en el peso de «{$p['paso']}».");
}
$assert(($porClave['aprobacion_cliente']['colLegacy'] ?? 'x') === null,
    'Aprobación del cliente no tiene columna legacy: usa días fijos.');
$assert(($porClave['licify']['diasSugeridos'] ?? 0) === 1, 'Licify sugiere 1 día, como dice el histórico.');

// ── Sin configuración: los siete de siempre ─────────────────────────────────
$def = $svc->deProyecto($P);
$assert(!$svc->configurado($P), 'Un proyecto sin filas no está configurado.');
$assert(count($def) === 7, 'Sin configuración devuelve los siete pasos. Dio ' . count($def));
$assert(array_column($def, 'clave') === array_column(PlanFechasService::PASOS, 'clave'),
    'Y en el mismo orden que la constante de código.');
$assert($def[0]['pasoId'] !== null, 'Cada paso por defecto resuelve su id del catálogo.');

// ── Guardar una configuración ───────────────────────────────────────────────
$r = $svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'],
    ['clave' => 'entrega_pliegos', 'alias' => 'Envío de pliegos'],
    ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'],
    ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'legalizacion'],
    ['clave' => 'fabricacion'],
    ['clave' => 'insumos_obra'],
], 'test-a41');
$assert(($r['ok'] ?? false) === true, 'Guardar una lista de ocho pasos.');
$cfg = $svc->deProyecto($P);
$assert(count($cfg) === 8, 'La obra ahora tiene ocho pasos. Dio ' . count($cfg));
$assert($cfg[4]['clave'] === 'aprobacion_cliente' && $cfg[4]['diasFijos'] === 15,
    'Aprobación del cliente quedó en la quinta posición con sus 15 días.');
$assert($cfg[1]['nombre'] === 'Envío de pliegos', 'El alias de la obra manda en el nombre.');
$assert($svc->configurado($P), 'Ahora sí está configurado.');

// ── Validaciones ────────────────────────────────────────────────────────────
$sinDias = $svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente']], 'test-a41');
$assert(($sinDias['ok'] ?? true) === false && ($sinDias['code'] ?? '') === 'DIAS_FIJOS_REQUERIDOS',
    'Un paso sin columna legacy exige días fijos.');
$vacia = $svc->guardar($P, [], 'test-a41');
$assert(($vacia['ok'] ?? true) === false && ($vacia['code'] ?? '') === 'SIN_PASOS',
    'Una obra no puede quedarse sin ningún paso.');
$repetida = $svc->guardar($P, [['clave' => 'legalizacion'], ['clave' => 'legalizacion']], 'test-a41');
$assert(($repetida['ok'] ?? true) === false && ($repetida['code'] ?? '') === 'PASO_REPETIDO',
    'Un paso no puede aparecer dos veces.');
$inventada = $svc->guardar($P, [['clave' => 'no_existe_este_paso']], 'test-a41');
$assert(($inventada['ok'] ?? true) === false && ($inventada['code'] ?? '') === 'PASO_DESCONOCIDO',
    'Solo se aceptan claves del catálogo activo.');
$assert(count($svc->deProyecto($P)) === 8, 'Ninguna validación fallida dejó la configuración a medias.');

// ── Restablecer ─────────────────────────────────────────────────────────────
$svc->restablecer($P);
$assert(!$svc->configurado($P) && count($svc->deProyecto($P)) === 7,
    'Restablecer devuelve la obra al proceso por defecto.');

$limpiar();
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php
```

Esperado: FALLA con `Class "App\Services\Pdc\PasosContratacionService" not found`.

- [ ] **Step 3: Añadir la `clave` a `PlanFechasService::PASOS`**

Reemplazar la constante (línea ~632) por:

```php
    /**
     * El proceso de contratación POR DEFECTO de la empresa, en orden, con la columna del catálogo
     * legacy que guarda la duración de cada paso y la clave que lo identifica en
     * `general_pasos_contratacion`. El último termina en la fecha en que el paquete se necesita en obra.
     *
     * Desde A4.1 esto es *el proceso por defecto*, no *el proceso*: una obra puede definir el suyo en
     * `pdc_proyecto_pasos` y entonces manda el suyo (ver PasosContratacionService::deProyecto()).
     * Esta constante se conserva como respaldo en código a propósito: es lo que garantiza que una obra
     * sin configurar —Da Porto— dé exactamente las mismas fechas aunque el catálogo de la base
     * estuviera vacío o a medio sembrar.
     */
    public const PASOS = [
        ['paso' => 'Elaboración de pliegos', 'col' => 'diasElaboracionPliegos', 'clave' => 'elaboracion_pliegos'],
        ['paso' => 'Entrega de pliegos', 'col' => 'diasEntregaPliegos', 'clave' => 'entrega_pliegos'],
        ['paso' => 'Recibo de propuestas', 'col' => 'diasReciboPropuestas', 'clave' => 'recibo_propuestas'],
        ['paso' => 'Cuadros comparativos', 'col' => 'diasCuadrosComparativos', 'clave' => 'cuadros_comparativos'],
        ['paso' => 'Legalización', 'col' => 'diasLegalizacionContrato', 'clave' => 'legalizacion'],
        ['paso' => 'Fabricación', 'col' => 'diasFabricacion', 'clave' => 'fabricacion'],
        ['paso' => 'Insumos en obra', 'col' => 'diasInsumosObra', 'clave' => 'insumos_obra'],
    ];
```

- [ ] **Step 4: Escribir el servicio**

Crear `src/Services/Pdc/PasosContratacionService.php`:

```php
<?php

namespace App\Services\Pdc;

/**
 * A4.1 · Qué pasos tiene el proceso de contratación de una obra.
 *
 * Única fuente de verdad: `calcular()`, la API y la pantalla preguntan aquí y nadie más recorre
 * `PlanFechasService::PASOS` por su cuenta. La regla de cero regresión vive en `deProyecto()`: una
 * obra sin filas propias usa la constante de código, tal cual, en el mismo orden.
 */
class PasosContratacionService
{
    /**
     * Lista blanca de columnas de `general_dias_procesos_contratacion` que un paso puede referenciar.
     *
     * `col_legacy` sale de la base y se interpola en el SELECT de `calcular()` —no puede ir como
     * parámetro, es un nombre de columna—, así que sin este filtro una fila del catálogo con texto
     * arbitrario sería una inyección SQL. Se deriva de PASOS para no poder desalinearse.
     *
     * @return list<string>
     */
    public static function columnasLegacy(): array
    {
        return array_column(PlanFechasService::PASOS, 'col');
    }

    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * @return list<array{id:int,clave:string,nombre:string,colLegacy:?string,diasSugeridos:?int,peso:?float,ordenDefault:int}>
     */
    public function catalogo(): array
    {
        $rows = $this->db->query(
            'SELECT id, clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default
             FROM general_pasos_contratacion WHERE activo = 1 ORDER BY orden_default, id',
        )->fetchAll(\PDO::FETCH_ASSOC);
        $legales = self::columnasLegacy();
        $out = [];
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $out[] = [
                'id' => (int) $r['id'],
                'clave' => (string) $r['clave'],
                'nombre' => (string) $r['nombre'],
                // Una columna que no esté en la lista blanca se trata como «sin respaldo legacy»,
                // no como error: el paso sigue siendo usable con días fijos y nunca llega al SQL.
                'colLegacy' => $col !== null && in_array($col, $legales, true) ? $col : null,
                'diasSugeridos' => $r['dias_sugeridos'] === null ? null : (int) $r['dias_sugeridos'],
                'peso' => $r['peso_reparto'] === null ? null : (float) $r['peso_reparto'],
                'ordenDefault' => (int) $r['orden_default'],
            ];
        }
        return $out;
    }

    public function configurado(int $projectId): bool
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_proyecto_pasos WHERE project_id = ?',
            [$projectId],
        )->fetchColumn() > 0;
    }

    /**
     * Los pasos efectivos de la obra. Sin filas propias, los siete por defecto.
     *
     * @return list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}>
     */
    public function deProyecto(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT c.id, c.clave, c.nombre, c.col_legacy, c.peso_reparto, p.alias, p.dias_fijos
             FROM pdc_proyecto_pasos p
             JOIN general_pasos_contratacion c ON c.id = p.paso_id
             WHERE p.project_id = ? AND c.activo = 1
             ORDER BY p.orden, p.id',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        if ($rows === []) {
            return $this->porDefecto();
        }

        $legales = self::columnasLegacy();
        $out = [];
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $col = $col !== null && in_array($col, $legales, true) ? $col : null;
            $alias = trim((string) $r['alias']);
            $out[] = [
                'pasoId' => (int) $r['id'],
                'clave' => (string) $r['clave'],
                'nombre' => $alias !== '' ? $alias : (string) $r['nombre'],
                'colLegacy' => $col,
                'diasFijos' => $r['dias_fijos'] === null ? null : (int) $r['dias_fijos'],
                'peso' => $r['peso_reparto'] === null ? null : (float) $r['peso_reparto'],
            ];
        }
        return $out;
    }

    /**
     * Los siete de la constante de código, con su id del catálogo cuando existe.
     *
     * El id se busca, pero NO se exige: si el catálogo estuviera vacío el plan se sigue calculando
     * igual (columnas y pesos salen de la constante) y las filas quedan sin identidad de paso. Las
     * fechas nunca dependen de que la semilla esté puesta.
     *
     * @return list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}>
     */
    private function porDefecto(): array
    {
        $ids = [];
        foreach ($this->db->query('SELECT id, clave FROM general_pasos_contratacion')->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $ids[(string) $r['clave']] = (int) $r['id'];
        }
        $out = [];
        foreach (PlanFechasService::PASOS as $i => $p) {
            $out[] = [
                'pasoId' => $ids[$p['clave']] ?? null,
                'clave' => $p['clave'],
                'nombre' => $p['paso'],
                'colLegacy' => $p['col'],
                'diasFijos' => null,
                'peso' => PlanFechasService::PESOS_REPARTO[$i],
            ];
        }
        return $out;
    }

    /**
     * Reemplaza la configuración de la obra. Todo o nada.
     *
     * @param list<array{clave:string,alias?:string,diasFijos?:int|null}> $pasos en el orden deseado
     * @return array{ok:bool,code?:string,mensaje?:string,pasos?:int}
     */
    public function guardar(int $projectId, array $pasos, string $usuario): array
    {
        if ($pasos === []) {
            return ['ok' => false, 'code' => 'SIN_PASOS', 'mensaje' => 'El proceso necesita al menos un paso.'];
        }
        $cat = [];
        foreach ($this->catalogo() as $c) {
            $cat[$c['clave']] = $c;
        }
        $vistas = [];
        foreach ($pasos as $p) {
            $clave = (string) ($p['clave'] ?? '');
            if (!isset($cat[$clave])) {
                return ['ok' => false, 'code' => 'PASO_DESCONOCIDO', 'mensaje' => "El paso «{$clave}» no está en el catálogo."];
            }
            if (isset($vistas[$clave])) {
                return ['ok' => false, 'code' => 'PASO_REPETIDO', 'mensaje' => "El paso «{$cat[$clave]['nombre']}» aparece dos veces."];
            }
            $vistas[$clave] = true;
            $dias = $p['diasFijos'] ?? null;
            if ($cat[$clave]['colLegacy'] === null && (!is_int($dias) || $dias < 0)) {
                return [
                    'ok' => false, 'code' => 'DIAS_FIJOS_REQUERIDOS',
                    'mensaje' => "«{$cat[$clave]['nombre']}» no tiene duración en el catálogo de la empresa: hay que decir cuántos días dura en esta obra.",
                ];
            }
        }

        $this->db->beginTransaction();
        try {
            // Se borra y se reescribe entera: la lista es corta, el orden queda contiguo desde 0, y
            // no sobrevive ninguna fila de una configuración anterior que ya nadie eligió.
            $this->db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$projectId]);
            foreach (array_values($pasos) as $i => $p) {
                $c = $cat[(string) $p['clave']];
                $this->db->query(
                    'INSERT INTO pdc_proyecto_pasos
                        (project_id, paso_id, orden, alias, dias_fijos, actualizado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $projectId, $c['id'], $i, trim((string) ($p['alias'] ?? '')),
                        $c['colLegacy'] === null ? (int) $p['diasFijos'] : null, $usuario,
                    ],
                );
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'pasos' => count($pasos)];
    }

    /** La obra vuelve al proceso por defecto de la empresa. */
    public function restablecer(int $projectId): void
    {
        $this->db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$projectId]);
    }
}
```

- [ ] **Step 5: Correr el test y verificar que pasa**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php
```

Esperado: todo `PASS` y `OK` al final, código de salida 0.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add src/Services/Pdc/PasosContratacionService.php src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_pasos_configurables.php && git commit -m "feat(pdc): un servicio dice qué pasos tiene cada obra, y sin configurar son los de siempre"
```

---

### Task 3: `calcular()` recorre la lista de la obra, no siete pasos fijos

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php` (`calcular()`, `repartirMediana()`, `plan()`, docblock de `medianasPorTipo()`/`pesosDelCatalogo()`)
- Modify: `tests/test_pdc_v2_plan_fechas.php:795-806` (el bloque de sobrantes, que hoy prueba por posición)
- Modify: `tests/test_pdc_v2_pasos_configurables.php` (se le añaden los bloques de aritmética y de cero regresión)

**Interfaces:**
- Consumes: `PasosContratacionService::deProyecto()` de la Task 2.
- Produces: `PlanFechasService::__construct(\Database $db, ?PasosContratacionService $pasos = null)`; `repartirMediana(int $total, ?array $pesos = null): list<int>`; cada paso de `plan()` gana `clave` en su array.

- [ ] **Step 1: Escribir el test de cero regresión que falla**

Añadir a `tests/test_pdc_v2_pasos_configurables.php`, antes del `$limpiar()` final:

```php
// ── CERO REGRESIÓN: Da Porto sin configurar no cambia ni un día ──────────────
// La foto se toma en esta misma corrida y no contra un fichero congelado: hay otras sesiones
// escribiendo en esta base, y una foto vieja probaría el estado de ayer, no la invariancia.
$DAPORTO = 73;
$antesPaquetes = $db->query(
    'SELECT paquete_id, fecha_ancla, fecha_arranque, dias_totales, duracion_provisional
     FROM pdc_plan_paquete WHERE project_id = ? ORDER BY paquete_id', [$DAPORTO],
)->fetchAll(PDO::FETCH_ASSOC);
$antesPasos = $db->query(
    'SELECT paquete_id, orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
     WHERE project_id = ? ORDER BY paquete_id, orden', [$DAPORTO],
)->fetchAll(PDO::FETCH_ASSOC);
$assert(count($antesPaquetes) > 0, 'La línea base de Da Porto no está vacía: ' . count($antesPaquetes) . ' paquetes.');
$assert(!(new PasosContratacionService($db))->configurado($DAPORTO), 'Da Porto NO tiene configuración propia de pasos.');

(new PlanFechasService($db))->calcular($DAPORTO, 'test-a41');

$despuesPaquetes = $db->query(
    'SELECT paquete_id, fecha_ancla, fecha_arranque, dias_totales, duracion_provisional
     FROM pdc_plan_paquete WHERE project_id = ? ORDER BY paquete_id', [$DAPORTO],
)->fetchAll(PDO::FETCH_ASSOC);
$despuesPasos = $db->query(
    'SELECT paquete_id, orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
     WHERE project_id = ? ORDER BY paquete_id, orden', [$DAPORTO],
)->fetchAll(PDO::FETCH_ASSOC);
$assert($antesPaquetes === $despuesPaquetes,
    'Recalcular Da Porto sin configuración deja las ' . count($antesPaquetes) . ' cabeceras idénticas.');
$assert($antesPasos === $despuesPasos,
    'Y las ' . count($antesPasos) . ' filas de pasos idénticas: mismas fechas, mismos días, mismo orden.');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paso_id IS NULL', [$DAPORTO])->fetchColumn() === 0,
    'Todas las filas de Da Porto conservan su identidad de paso.');
```

- [ ] **Step 2: Escribir el test de aritmética con pasos configurados**

Añadir a continuación, en el mismo archivo. Usa el proyecto de pruebas `999903` que ya monta `tests/test_pdc_v2_plan_fechas.php`; para no depender de él, este bloque monta su propio paquete amarrado. Copiar el helper de montaje de `tests/test_pdc_v2_plan_fechas.php:100-140` y luego:

```php
// ── Con un paso nuevo, el proceso se ALARGA exactamente lo que dura el paso ──
$svcPlan = new PlanFechasService($db);
$svcPlan->calcular($P, 'test-a41');                       // sin configurar
$base = $db->query('SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);

$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'entrega_pliegos'], ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'], ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'legalizacion'], ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$con = $db->query('SELECT fecha_ancla, fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetch(PDO::FETCH_ASSOC);

$assert((int) $con['dias_totales'] === (int) $base['dias_totales'] + 15,
    'Agregar «Aprobación del cliente, 15 días» suma exactamente 15 al total: '
    . $base['dias_totales'] . ' → ' . $con['dias_totales']);
$assert($con['fecha_arranque'] === (new DateTimeImmutable($base['fecha_arranque']))->modify('-15 days')->format('Y-m-d'),
    'Y la fecha de arranque retrocede exactamente 15 días: ' . $base['fecha_arranque'] . ' → ' . $con['fecha_arranque']);

$filas = $db->query('SELECT orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
    WHERE project_id = ? AND paquete_id = ? ORDER BY orden', [$P, $paqPrueba])->fetchAll(PDO::FETCH_ASSOC);
$assert(count($filas) === 8, 'El paquete tiene ahora ocho filas de paso. Dio ' . count($filas));
$assert($filas[4]['paso'] === 'Aprobación del cliente' && (int) $filas[4]['dias'] === 15,
    'El paso nuevo quedó en su posición con sus días.');
$assert(end($filas)['fecha_fin'] === $con['fecha_ancla'],
    'Propiedad 3 del contrato con B1: la fecha_fin del último paso ES la fecha ancla.');
$assert(array_sum(array_column($filas, 'dias')) === (int) $con['dias_totales'],
    'Propiedad 2: la suma de los días es exactamente el intervalo completo.');
foreach ($filas as $i => $f) {
    $assert((int) (new DateTimeImmutable($f['fecha_inicio']))->diff(new DateTimeImmutable($f['fecha_fin']))->days === (int) $f['dias'],
        "Propiedad 1 en el paso {$i}: dias = fin - inicio, sin sumar ni restar uno.");
    if ($i > 0) {
        $assert($filas[$i - 1]['fecha_fin'] === $f['fecha_inicio'],
            "Fronteras [inicio, fin) intactas entre el paso " . ($i - 1) . " y el {$i}.");
    }
}

// ── Reordenar no reasigna filas: la identidad manda ─────────────────────────
$idAprobacion = (int) $db->query('SELECT id FROM general_pasos_contratacion WHERE clave = "aprobacion_cliente"')->fetchColumn();
$diasAntes = (int) $db->query('SELECT dias FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
    [$P, $paqPrueba, $idAprobacion])->fetchColumn();
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'aprobacion_cliente', 'diasFijos' => 15],
    ['clave' => 'entrega_pliegos'], ['clave' => 'recibo_propuestas'], ['clave' => 'cuadros_comparativos'],
    ['clave' => 'legalizacion'], ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$fila = $db->query('SELECT orden, paso, dias FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
    [$P, $paqPrueba, $idAprobacion])->fetch(PDO::FETCH_ASSOC);
$assert($fila !== false && (int) $fila['orden'] === 1 && (int) $fila['dias'] === $diasAntes,
    'Mover el paso de la posición 4 a la 1 mueve SU fila, no reescribe la del vecino.');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 8, 'Reordenar no duplica ni pierde filas.');

// ── Acortar el proceso borra los sobrantes por identidad, no por posición ───
$svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion'], ['clave' => 'insumos_obra']], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 3, 'Bajar a tres pasos deja exactamente tres filas.');

// ── Más de siete pasos ──────────────────────────────────────────────────────
$svc->guardar($P, [
    ['clave' => 'elaboracion_pliegos'], ['clave' => 'licify', 'diasFijos' => 1], ['clave' => 'entrega_pliegos'],
    ['clave' => 'recibo_propuestas'], ['clave' => 'cuadros_comparativos'],
    ['clave' => 'aprobacion_cliente', 'diasFijos' => 15], ['clave' => 'legalizacion'],
    ['clave' => 'fabricacion'], ['clave' => 'insumos_obra'],
], 'test-a41');
$svcPlan->calcular($P, 'test-a41');
$assert((int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqPrueba])->fetchColumn() === 9, 'Nueve pasos: nada asume siete.');

// ── Reparto de la mediana con días fijos aparte ─────────────────────────────
$pesos = [0.5, 0.0, 0.5];
$r = PlanFechasService::repartirMediana(10, $pesos);
$assert($r === [5, 0, 5], 'Un paso de peso cero no recibe días del reparto: [' . implode(', ', $r) . ']');
$assert(array_sum(PlanFechasService::repartirMediana(0, $pesos)) === 0, 'Repartir cero da cero.');
$assert(PlanFechasService::repartirMediana(10, [0.0, 0.0]) === [0, 0], 'Sin ningún peso, no hay reparto ni división por cero.');
$assert(PlanFechasService::repartirMediana(90) === PlanFechasService::repartirMediana(90, PlanFechasService::PESOS_REPARTO),
    'Sin pesos explícitos sigue usando PESOS_REPARTO: los llamadores viejos no cambian de resultado.');
```

- [ ] **Step 3: Correr y verificar que falla**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php
```

Esperado: FALLA — `repartirMediana()` todavía no acepta pesos y `calcular()` sigue recorriendo siete pasos.

- [ ] **Step 4: Inyectar el servicio en `PlanFechasService`**

Reemplazar el constructor (línea ~55):

```php
    private readonly PasosContratacionService $pasos;

    /**
     * El servicio de pasos es opcional en la firma para no romper a los llamadores existentes
     * (controlador y tests construyen `new PlanFechasService($db)` a secas), pero nunca es null
     * dentro de la clase.
     */
    public function __construct(private readonly \Database $db, ?PasosContratacionService $pasos = null)
    {
        $this->pasos = $pasos ?? new PasosContratacionService($db);
    }
```

- [ ] **Step 5: Reescribir el cuerpo de `calcular()`**

Sustituir desde `$medianas = $this->medianasPorTipo();` hasta el `DELETE` de sobrantes por:

```php
        $medianas = $this->medianasPorTipo();
        $pasos = $this->pasos->deProyecto($projectId);
        // Las columnas legacy que ESTA obra necesita, no las siete siempre. `columnasLegacy()` es la
        // lista blanca: `colLegacy` viene de la base y aquí se interpola como nombre de columna.
        $cols = [];
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && in_array($p['colLegacy'], PasosContratacionService::columnasLegacy(), true)) {
                $cols[$p['colLegacy']] = true;
            }
        }
        $selectCols = $cols === []
            ? ''
            : ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, array_keys($cols)));

        $calculados = 0;
        $sinDuracion = 0;

        foreach ($amarres as $paqueteId => $a) {
            $paq = $this->db->query(
                "SELECT p.id, p.tipo_negociacion, p.duracion_ref{$selectCols}
                 FROM general_paquetes_contratacion p
                 LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
                 WHERE p.id = ? AND p.activo = 1
                   AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')',
                [$paqueteId],
            )->fetch(\PDO::FETCH_ASSOC);
            if ($paq === false) {
                continue;
            }

            // «Sin desglose» se decide solo sobre las columnas que esta obra usa: un paso de días
            // fijos siempre aporta su número y nunca vuelve provisional a un paquete.
            $desgloseCompleto = true;
            foreach ($pasos as $p) {
                if ($p['colLegacy'] !== null && ($paq[$p['colLegacy']] ?? null) === null) {
                    $desgloseCompleto = false;
                    break;
                }
            }
            $provisional = !$desgloseCompleto;

            if ($provisional) {
                $sinDuracion++;
                $mediana = $medianas[$paq['tipo_negociacion']] ?? self::DURACION_FALLBACK_DIAS;
                // Los pasos de días fijos se respetan y el RESTO de la mediana se reparte entre los
                // que tienen peso, re-normalizados sobre los activos. La mediana es la duración del
                // proceso COMPLETO para ese tipo —ya incluye el tiempo administrativo real de esas
                // obras—, así que aquí es el sobre entero y no una base a la que sumar. En un paquete
                // CON desglose sí se suma: allí cada número es una medición de su paso.
                $fijos = 0;
                $pesos = [];
                foreach ($pasos as $p) {
                    $esFijo = $p['colLegacy'] === null;
                    $fijos += $esFijo ? (int) ($p['diasFijos'] ?? 0) : 0;
                    $pesos[] = $esFijo ? 0.0 : ($p['peso'] ?? 0.0);
                }
                // Si los días fijos ya suman más que la mediana, el resto se topa en cero: el total
                // pasa a ser la suma de los fijos. Nunca un total negativo ni pasos en negativo.
                $reparto = self::repartirMediana(max(0, $mediana - $fijos), $pesos);
                $dias = [];
                foreach ($pasos as $i => $p) {
                    $dias[] = $p['colLegacy'] === null ? (int) ($p['diasFijos'] ?? 0) : $reparto[$i];
                }
            } else {
                $dias = [];
                foreach ($pasos as $p) {
                    $dias[] = $p['colLegacy'] === null
                        ? (int) ($p['diasFijos'] ?? 0)
                        : (int) $paq[$p['colLegacy']];
                }
            }
            $total = array_sum($dias);

            $ancla = new \DateTimeImmutable($a['fechaAncla']);
            $cursor = $ancla->modify(sprintf('-%d days', $total));
            $arranque = $cursor->format('Y-m-d');

            $this->db->beginTransaction();
            try {
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
                        // Las tres columnas del responsable siguen fuera a propósito: lo que no se
                        // lista, MySQL lo conserva. No añadirlas sin querer perder esa garantía.
                        $projectId, $paqueteId, $a['uniqueId'], $a['fechaAncla'], $arranque, $total,
                        $paq['duracion_ref'], $provisional ? 1 : 0, $usuario,
                    ],
                );

                // Upsert por (project_id, paquete_id, paso_id) desde A4.1: la fila sigue al PASO, no a
                // la posición. Por eso reordenar mueve `orden` dentro de la fila del paso en lugar de
                // sobrescribir la del vecino — que es lo que protegerá el avance real de B1.
                $idsVigentes = [];
                foreach ($pasos as $i => $p) {
                    $ini = $cursor;
                    $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
                    if ($p['pasoId'] !== null) {
                        $idsVigentes[] = (int) $p['pasoId'];
                    }
                    $this->db->query(
                        'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE orden = VALUES(orden), paso = VALUES(paso), dias = VALUES(dias),
                            fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin)',
                        [$projectId, $paqueteId, $i, $p['pasoId'], $p['nombre'], $dias[$i],
                            $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
                    );
                }

                // Sobrantes: filas de pasos que la obra ya no usa. Se filtran por identidad y no por
                // `orden >= N`, que es lo único que funciona cuando la lista se reordena o crece por
                // encima de siete.
                //
                // El `paso_id IS NULL` NO es decorativo: en SQL, `NULL NOT IN (...)` vale NULL —ni
                // verdadero ni falso—, así que sin él una fila sin identidad sobreviviría para siempre
                // a todos los recálculos, invisible.
                $marcas = $idsVigentes === [] ? '' : implode(',', array_fill(0, count($idsVigentes), '?'));
                $this->db->query(
                    'DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?'
                        . ($marcas === '' ? '' : " AND (paso_id IS NULL OR paso_id NOT IN ({$marcas}))"),
                    array_merge([$projectId, $paqueteId], $idsVigentes),
                );
                $this->db->commit();
            } catch (\Throwable $t) {
                $this->db->rollBack();
                throw $t;
            }
            $calculados++;
        }
        return ['ok' => true, 'calculados' => $calculados, 'sinDuracion' => $sinDuracion];
```

- [ ] **Step 6: Hacer que `repartirMediana()` acepte pesos**

Sustituir el método (línea ~897) por:

```php
    /**
     * Reparte una duración total entre pasos según sus pesos. Sin pesos explícitos, `PESOS_REPARTO`
     * —el proceso por defecto—, para que los llamadores anteriores a A4.1 den el mismo resultado.
     *
     * Un peso de cero significa «este paso no entra al reparto» (los pasos de días fijos): ni recibe
     * su parte proporcional ni puede recibir un día del residuo. El residuo de redondeo se asigna por
     * resto mayor entre los que sí tienen peso, no cargándoselo entero al último paso: así la suma
     * sigue siendo exactamente `$total` y ningún paso se desvía más de un día de su parte.
     *
     * Pura y pública a propósito: es una regla del dominio, sin estado ni base de datos.
     *
     * @param list<float>|null $pesos
     * @return list<int>
     */
    public static function repartirMediana(int $total, ?array $pesos = null): array
    {
        $pesos = $pesos ?? self::PESOS_REPARTO;
        $n = count($pesos);
        $dias = array_fill(0, $n, 0);
        $sumaPesos = array_sum($pesos);
        if ($total <= 0 || $n === 0 || $sumaPesos <= 0) {
            return $dias;
        }
        $restos = [];
        $acum = 0;
        foreach ($pesos as $i => $w) {
            $exacto = $total * $w / $sumaPesos;
            $piso = (int) floor($exacto);
            $dias[$i] = $piso;
            $restos[$i] = $w > 0 ? $exacto - $piso : -1.0; // peso cero: fuera del residuo
            $acum += $piso;
        }
        // Entre restos iguales gana el paso más temprano, para que el reparto sea determinista.
        $orden = array_values(array_filter(array_keys($restos), static fn (int $i): bool => $restos[$i] >= 0));
        usort($orden, static fn (int $a, int $b): int => $restos[$b] <=> $restos[$a] ?: $a <=> $b);
        $m = count($orden);
        for ($k = 0; $m > 0 && $k < $total - $acum; $k++) {
            $dias[$orden[$k % $m]]++;
        }
        return $dias;
    }
```

- [ ] **Step 7: Devolver la clave del paso en `plan()`**

En `plan()`, la consulta de pasos (línea ~982) y el mapeo:

```php
        foreach ($this->db->query(
            'SELECT pp.paquete_id, pp.orden, pp.paso, pp.dias, pp.fecha_inicio, pp.fecha_fin, g.clave
             FROM pdc_plan_paso pp
             LEFT JOIN general_pasos_contratacion g ON g.id = pp.paso_id
             WHERE pp.project_id = ? ORDER BY pp.paquete_id, pp.orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $pasos[(int) $p['paquete_id']][] = [
                'orden' => (int) $p['orden'], 'paso' => (string) $p['paso'], 'dias' => (int) $p['dias'],
                'fechaInicio' => (string) $p['fecha_inicio'], 'fechaFin' => (string) $p['fecha_fin'],
                'clave' => (string) ($p['clave'] ?? ''),
            ];
        }
```

Añadir `clave: string` al bloque `@return` del docblock de `plan()`, dentro de la lista `pasos`.

- [ ] **Step 8: Actualizar el docblock de `medianasPorTipo()` y `pesosDelCatalogo()`**

Añadir a ambos, al final del docblock existente:

```
     * Desde A4.1 esto NO depende de la lista de pasos de ninguna obra: es una estadística de la
     * EMPRESA, medida sobre las siete columnas del catálogo. Si dependiera del proyecto que pregunta,
     * la mediana de «a todo costo» valdría una cosa u otra según quién la consultara.
```

- [ ] **Step 9: Arreglar el test viejo de sobrantes**

En `tests/test_pdc_v2_plan_fechas.php`, el bloque de las líneas ~795-806 inserta una fila fantasma en `orden = count(PASOS)` y espera que el recálculo la borre. Con la clave nueva esa fila no tiene `paso_id`. Sustituir el INSERT y su assert por:

```php
// Los pasos sobrantes se retiran. Desde A4.1 el borrado es por IDENTIDAD del paso, no por posición:
// una fila sin `paso_id` (residuo de un esquema anterior) también sobra, y por eso el DELETE lleva
// `paso_id IS NULL OR ...` — sin esa mitad, `NULL NOT IN (...)` la dejaría viva para siempre.
$db->query(
    'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin)
     VALUES (?, ?, ?, NULL, ?, ?, ?, ?)',
    [$P, $paqEstructura, 99, 'PASO FANTASMA', 5, '2026-01-01', '2026-01-06'],
);
$svc->calcular($P, 'test');
$assert((int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso = ?',
    [$P, $paqEstructura, 'PASO FANTASMA'],
)->fetchColumn() === 0, 'Una fila sin identidad de paso se retira en el siguiente recálculo.');
```

- [ ] **Step 10: Correr los dos tests PHP y verificar que pasan**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: ambos terminan en `OK`, código de salida 0. **El assert que importa** es «Recalcular Da Porto sin configuración deja las 11 cabeceras idénticas» y su gemelo de las 77 filas.

- [ ] **Step 11: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add src/Services/Pdc/PlanFechasService.php tests/ && git commit -m "feat(pdc): el plan de fechas recorre los pasos de la obra, no siete escritos a fuego"
```

---

### Task 4: Endpoints JSON y permiso

**Files:**
- Modify: `src/Controllers/Api/PlanComprasPlanController.php` (tres métodos nuevos + un guard)
- Modify: `public/index.php` (tres rutas, junto a las de `/plan-compras/api/plan/*`)
- Create: `tests/test_pdc_v2_rbac_pasos.php`

**Interfaces:**
- Consumes: `PasosContratacionService` (Task 2), `PlanFechasService::calcular()` (Task 3).
- Produces: `GET /plan-compras/api/plan/pasos` → `{catalogo, proyecto, configurado, paquetesConPlan}`; `POST /plan-compras/api/plan/pasos` `{pasos:[{clave,alias?,diasFijos?}]}` → `{ok:true, pasos:N, calculados:N, sinDuracion:N}`; `POST /plan-compras/api/plan/pasos/restablecer` → lo mismo sin `pasos`.

- [ ] **Step 1: Escribir el test de rutas y RBAC que falla**

Crear `tests/test_pdc_v2_rbac_pasos.php`, siguiendo `tests/test_pdc_v2_rbac_paquetes.php`:

```php
<?php
// tests/test_pdc_v2_rbac_pasos.php — A4.1: rutas y permisos de la configuración de pasos.
declare(strict_types=1);

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$rutas = (string) file_get_contents(__DIR__ . '/../public/index.php');
$assert(str_contains($rutas, "\$router->get('/plan-compras/api/plan/pasos'"), 'La ruta GET de pasos está registrada.');
$assert(str_contains($rutas, "\$router->post('/plan-compras/api/plan/pasos'"), 'La ruta POST de pasos está registrada.');
$assert(str_contains($rutas, "\$router->post('/plan-compras/api/plan/pasos/restablecer'"), 'La ruta POST de restablecer está registrada.');
$assert(strpos($rutas, "/plan-compras/api/plan/pasos/restablecer") < strpos($rutas, "\$router->post('/plan-compras/api/plan/pasos',"),
    'La ruta sufijada va antes que la desnuda, como el resto del bloque.');

$ctrl = (string) file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasPlanController.php');
$assert(str_contains($ctrl, 'lps.paquetes_contratacion.reglas'),
    'Escribir pasos exige el permiso de reglas, no el de editar.');
$assert(substr_count($ctrl, 'guardReglas()') >= 3, 'Los dos POST de pasos y el guard usan guardReglas().');

$permiso = (int) (\Database::getInstance())->query(
    'SELECT COUNT(*) FROM rbac_permissions WHERE name = ?', ['lps.paquetes_contratacion.reglas'],
)->fetchColumn();
$assert($permiso === 1, 'El permiso lps.paquetes_contratacion.reglas existe en la BD.');

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " FALLOS\n");
exit($failures === [] ? 0 : 1);
```

Nota: si la columna de `rbac_permissions` no se llama `name`, ajustar leyendo `database/migrations/20260726_pdc_v2_permiso_reglas_motor.php:60-70`, que es quien inserta la fila.

- [ ] **Step 2: Correr y verificar que falla**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_rbac_pasos.php
```

Esperado: FALLA en las rutas y en `guardReglas()`.

- [ ] **Step 3: Añadir el guard y los tres métodos al controlador**

En `PlanComprasPlanController.php`, añadir tras `responsables()`:

```php
    /** GET /plan-compras/api/plan/pasos */
    public function pasos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $svc = new PasosContratacionService($this->db);
        $this->ok([
            'catalogo' => $svc->catalogo(),
            'proyecto' => $svc->deProyecto($projectId),
            'configurado' => $svc->configurado($projectId),
            // Para que el aviso de quitar un paso pueda decir un número y no un «se borrarán filas»
            // genérico: quitar un paso borra exactamente una fila por paquete con plan.
            'paquetesConPlan' => (int) $this->db->query(
                'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND fecha_arranque IS NOT NULL',
                [$projectId],
            )->fetchColumn(),
        ]);
    }

    /**
     * POST /plan-compras/api/plan/pasos  {pasos:[{clave, alias?, diasFijos?}]}
     *
     * Guarda y recalcula en la misma llamada: cambiar los pasos mueve las fechas de todos los
     * paquetes de la obra, y dejar la configuración nueva conviviendo con el plan viejo pondría en
     * pantalla unas fechas que ya no son las que produce esa configuración.
     */
    public function guardarPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $pasos = is_array($body['pasos'] ?? null) ? array_values($body['pasos']) : null;
        if ($pasos === null) {
            $this->fail('PASOS_INVALIDOS', 'Falta la lista de pasos.', 422);
            return;
        }
        $r = (new PasosContratacionService($this->db))->guardar($projectId, $pasos, $this->usuario());
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'PASOS_INVALIDOS', $r['mensaje'] ?? 'Configuración de pasos inválida.', 422);
            return;
        }
        $this->ok(array_merge($r, $this->service->calcular($projectId, $this->usuario())));
    }

    /** POST /plan-compras/api/plan/pasos/restablecer — la obra vuelve al proceso por defecto. */
    public function restablecerPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        (new PasosContratacionService($this->db))->restablecer($projectId);
        $this->ok($this->service->calcular($projectId, $this->usuario()));
    }
```

Añadir el guard junto a los otros dos:

```php
    /**
     * Cambiar los pasos mueve las fechas de TODOS los paquetes de la obra a la vez, así que no basta
     * con poder asignar insumos: exige el mismo permiso con el que A3.3 aprueba reglas globales del
     * motor (Oficina Técnica / Compras y Director de Obra).
     */
    private function guardReglas(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.reglas')) {
            $this->fail('FORBIDDEN', 'No autorizado para cambiar los pasos del proceso de contratación.', 403);
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
```

Y el `use` arriba: `use App\Services\Pdc\PasosContratacionService;`

- [ ] **Step 4: Registrar las rutas**

En `public/index.php`, justo antes de `$router->post('/plan-compras/api/plan/responsable', ...)`:

```php
// A4.1 — pasos del proceso de contratación configurables por proyecto. La sufijada va antes que la
// desnuda por consistencia con el resto del bloque (FastRoute resuelve estáticas por hashmap exacto).
$router->get('/plan-compras/api/plan/pasos', [\App\Controllers\Api\PlanComprasPlanController::class, 'pasos']);
$router->post('/plan-compras/api/plan/pasos/restablecer', [\App\Controllers\Api\PlanComprasPlanController::class, 'restablecerPasos']);
$router->post('/plan-compras/api/plan/pasos', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarPasos']);
```

- [ ] **Step 5: Correr el test y verificar que pasa**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_rbac_pasos.php
```

Esperado: todo `PASS`, `OK`.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add src/Controllers/Api/PlanComprasPlanController.php public/index.php tests/test_pdc_v2_rbac_pasos.php && git commit -m "feat(pdc): la API deja configurar los pasos y recalcula el plan al guardar"
```

---

### Task 5: Lógica de la pantalla en TypeScript puro

**Files:**
- Create: `src/lib/pasosState.ts` (repo `plan-de-compras`)
- Create: `src/lib/pasosState.test.ts`
- Modify: `src/lib/types.ts` (tipos `PasoCatalogo`, `PasoProyecto`, `RespuestaPasos`)

**Interfaces:**
- Consumes: el JSON de `GET /plan-compras/api/plan/pasos` (Task 4).
- Produces:
  - `type PasoEditable = { clave: string; nombre: string; alias: string; colLegacy: string | null; diasFijos: number | null; diasSugeridos: number | null }`
  - `mover(pasos: PasoEditable[], desde: number, hacia: number): PasoEditable[]`
  - `quitar(pasos: PasoEditable[], clave: string): PasoEditable[]`
  - `agregar(pasos: PasoEditable[], cat: PasoCatalogo, posicion?: number): PasoEditable[]`
  - `validar(pasos: PasoEditable[]): { ok: boolean; mensaje?: string }`
  - `aPayload(pasos: PasoEditable[]): { clave: string; alias?: string; diasFijos?: number }[]`
  - `disponibles(cat: PasoCatalogo[], pasos: PasoEditable[]): PasoCatalogo[]`

- [ ] **Step 1: Escribir los tests que fallan**

Crear `src/lib/pasosState.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { agregar, aPayload, disponibles, mover, quitar, validar, type PasoEditable } from './pasosState'

const paso = (clave: string, colLegacy: string | null = 'diasX'): PasoEditable => ({
  clave, nombre: clave, alias: '', colLegacy, diasFijos: colLegacy === null ? 15 : null, diasSugeridos: null,
})

describe('mover', () => {
  it('sube un paso y deja el resto en su orden relativo', () => {
    const r = mover([paso('a'), paso('b'), paso('c')], 2, 0)
    expect(r.map((p) => p.clave)).toEqual(['c', 'a', 'b'])
  })

  it('no hace nada si el destino queda fuera de la lista', () => {
    const antes = [paso('a'), paso('b')]
    expect(mover(antes, 0, -1)).toEqual(antes)
    expect(mover(antes, 0, 2)).toEqual(antes)
  })
})

describe('validar', () => {
  it('rechaza una lista vacía', () => {
    expect(validar([])).toEqual({ ok: false, mensaje: 'El proceso necesita al menos un paso.' })
  })

  it('rechaza un paso sin respaldo del catálogo al que no se le pusieron días', () => {
    const sinDias = { ...paso('aprobacion_cliente', null), diasFijos: null }
    expect(validar([paso('a'), sinDias]).ok).toBe(false)
  })

  it('rechaza días negativos', () => {
    const negativo = { ...paso('aprobacion_cliente', null), diasFijos: -1 }
    expect(validar([negativo]).ok).toBe(false)
  })

  it('acepta una lista de más de siete pasos', () => {
    const muchos = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'].map((c) => paso(c))
    expect(validar(muchos)).toEqual({ ok: true })
  })
})

describe('aPayload', () => {
  it('manda el alias solo cuando lo hay y los días solo cuando el paso los necesita', () => {
    const conAlias = { ...paso('entrega_pliegos'), alias: 'Envío de pliegos' }
    expect(aPayload([conAlias, paso('aprobacion_cliente', null)])).toEqual([
      { clave: 'entrega_pliegos', alias: 'Envío de pliegos' },
      { clave: 'aprobacion_cliente', diasFijos: 15 },
    ])
  })
})

describe('agregar y disponibles', () => {
  it('un paso ya en la lista no se ofrece dos veces', () => {
    const cat = [
      { id: 1, clave: 'a', nombre: 'A', colLegacy: 'diasX', diasSugeridos: null, peso: 0.5, ordenDefault: 0 },
      { id: 2, clave: 'z', nombre: 'Z', colLegacy: null, diasSugeridos: 3, peso: null, ordenDefault: 1 },
    ]
    expect(disponibles(cat, [paso('a')]).map((c) => c.clave)).toEqual(['z'])
  })

  it('al agregar un paso sin respaldo, arranca con los días que sugiere el catálogo', () => {
    const z = { id: 2, clave: 'z', nombre: 'Z', colLegacy: null, diasSugeridos: 3, peso: null, ordenDefault: 1 }
    expect(agregar([paso('a')], z)[1].diasFijos).toBe(3)
  })
})

describe('quitar', () => {
  it('saca el paso de la lista', () => {
    expect(quitar([paso('a'), paso('b')], 'a').map((p) => p.clave)).toEqual(['b'])
  })
})
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test -- pasosState
```

Esperado: FALLA con `Failed to resolve import "./pasosState"`.

- [ ] **Step 3: Escribir `src/lib/pasosState.ts`**

```ts
/**
 * A4.1 — los pasos del proceso de contratación de una obra, como los edita la pantalla.
 *
 * Lógica pura, sin React ni fetch: reordenar, agregar, quitar y validar son reglas del dominio y se
 * verifican en Vitest sin montar la pantalla. La validación es la misma que hace el servidor
 * (PasosContratacionService::guardar) — aquí para responder al instante, allá porque el servidor no
 * puede confiar en el cliente.
 */
import type { PasoCatalogo } from './types'

export type PasoEditable = {
  clave: string
  /** Nombre del catálogo de la empresa. */
  nombre: string
  /** Nombre propio de esta obra; vacío = se usa el del catálogo. */
  alias: string
  /** Columna del catálogo legacy de la que salen sus días por paquete; null = días fijos. */
  colLegacy: string | null
  diasFijos: number | null
  diasSugeridos: number | null
}

export function mover(pasos: PasoEditable[], desde: number, hacia: number): PasoEditable[] {
  if (desde < 0 || desde >= pasos.length || hacia < 0 || hacia >= pasos.length) return pasos
  const copia = [...pasos]
  const [p] = copia.splice(desde, 1)
  copia.splice(hacia, 0, p)
  return copia
}

export function quitar(pasos: PasoEditable[], clave: string): PasoEditable[] {
  return pasos.filter((p) => p.clave !== clave)
}

export function agregar(pasos: PasoEditable[], cat: PasoCatalogo, posicion?: number): PasoEditable[] {
  if (pasos.some((p) => p.clave === cat.clave)) return pasos
  const nuevo: PasoEditable = {
    clave: cat.clave,
    nombre: cat.nombre,
    alias: '',
    colLegacy: cat.colLegacy,
    // Un paso sin respaldo del catálogo necesita días sí o sí; arrancar con lo que sugiere el
    // catálogo evita que la pantalla nazca en estado inválido.
    diasFijos: cat.colLegacy === null ? (cat.diasSugeridos ?? 0) : null,
    diasSugeridos: cat.diasSugeridos,
  }
  const copia = [...pasos]
  copia.splice(posicion ?? copia.length, 0, nuevo)
  return copia
}

export function disponibles(cat: PasoCatalogo[], pasos: PasoEditable[]): PasoCatalogo[] {
  const usadas = new Set(pasos.map((p) => p.clave))
  return cat.filter((c) => !usadas.has(c.clave))
}

export function validar(pasos: PasoEditable[]): { ok: boolean; mensaje?: string } {
  if (pasos.length === 0) return { ok: false, mensaje: 'El proceso necesita al menos un paso.' }
  for (const p of pasos) {
    if (p.colLegacy === null && (p.diasFijos === null || !Number.isInteger(p.diasFijos) || p.diasFijos < 0)) {
      return {
        ok: false,
        mensaje: `«${p.alias || p.nombre}» no tiene duración en el catálogo de la empresa: escribe cuántos días dura en esta obra.`,
      }
    }
  }
  return { ok: true }
}

export function aPayload(pasos: PasoEditable[]): { clave: string; alias?: string; diasFijos?: number }[] {
  return pasos.map((p) => ({
    clave: p.clave,
    ...(p.alias.trim() !== '' ? { alias: p.alias.trim() } : {}),
    ...(p.colLegacy === null ? { diasFijos: p.diasFijos ?? 0 } : {}),
  }))
}
```

Añadir a `src/lib/types.ts`:

```ts
export type PasoCatalogo = {
  id: number
  clave: string
  nombre: string
  colLegacy: string | null
  diasSugeridos: number | null
  peso: number | null
  ordenDefault: number
}

export type PasoProyecto = {
  pasoId: number | null
  clave: string
  nombre: string
  colLegacy: string | null
  diasFijos: number | null
  peso: number | null
}

export type RespuestaPasos = {
  catalogo: PasoCatalogo[]
  proyecto: PasoProyecto[]
  configurado: boolean
  /** Cuántos paquetes tienen plan calculado: quitar un paso borra una fila por cada uno. */
  paquetesConPlan: number
}
```

- [ ] **Step 4: Correr los tests y verificar que pasan**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test -- pasosState
```

Esperado: los 9 tests en verde.

- [ ] **Step 5: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git add src/lib/pasosState.ts src/lib/pasosState.test.ts src/lib/types.ts && git commit -m "feat(pdc): la lógica de editar los pasos de una obra, verificada aparte de la pantalla"
```

---

### Task 6: La pantalla

**Files:**
- Create: `src/pages/PasosContratacion.tsx`
- Modify: `src/App.tsx` (una línea de `<Route>` y un import)
- Modify: `src/pages/PlanFechas.tsx` (un import y un enlace en `.pdc-paq-toolbar`, línea ~496 — **nada más**)
- Modify: `src/styles.css` (clases de la pantalla nueva)

**Interfaces:**
- Consumes: `pasosState.ts` (Task 5), `apiGet`/`apiPost` de `src/lib/api.ts`, endpoints de la Task 4.
- Produces: ruta `#/ensamble/plan/pasos`; testids `pdc-pasos-lista`, `pdc-pasos-guardar`, `pdc-pasos-restablecer`, `pdc-pasos-agregar`, `pdc-plan-configurar-pasos`.

- [ ] **Step 1: Escribir la pantalla**

Crear `src/pages/PasosContratacion.tsx`. Estructura: carga con `apiGet<RespuestaPasos>('/plan-compras/api/plan/pasos')`, estado local `PasoEditable[]`, botones subir/bajar/quitar por fila, campo de alias, campo de días donde `colLegacy === null`, desplegable de `disponibles()` para agregar, y los botones Guardar / Restablecer / Volver al plan.

```tsx
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiGet, apiPost, PdcApiError } from '../lib/api'
import { agregar, aPayload, disponibles, mover, quitar, validar, type PasoEditable } from '../lib/pasosState'
import type { PasoCatalogo, RespuestaPasos } from '../lib/types'

/**
 * A4.1 — el proceso de contratación de esta obra.
 *
 * Fuera de la barra de pestañas a propósito: se configura una vez por obra y casi no se vuelve a
 * tocar, así que ocupar una pestaña permanente sería caro. Se llega desde el Plan de compras.
 */
export default function PasosContratacion() {
  const [cat, setCat] = useState<PasoCatalogo[]>([])
  const [pasos, setPasos] = useState<PasoEditable[]>([])
  /** Las claves tal como estaban guardadas, para poder avisar de lo que se está quitando. */
  const [original, setOriginal] = useState<string[]>([])
  const [paquetesConPlan, setPaquetesConPlan] = useState(0)
  const [configurado, setConfigurado] = useState(false)
  const [ocupado, setOcupado] = useState(false)
  const [mensaje, setMensaje] = useState('')
  const [error, setError] = useState('')

  const cargar = async () => {
    const d = await apiGet<RespuestaPasos>('/plan-compras/api/plan/pasos')
    setCat(d.catalogo)
    setConfigurado(d.configurado)
    setPaquetesConPlan(d.paquetesConPlan)
    setOriginal(d.proyecto.map((p) => p.clave))
    setPasos(d.proyecto.map((p) => ({
      clave: p.clave, nombre: p.nombre, alias: '', colLegacy: p.colLegacy,
      diasFijos: p.diasFijos, diasSugeridos: null,
    })))
  }

  // Quitar un paso borra su fila en cada paquete con plan. El número importa: «se borrarán filas» no
  // le dice a nadie si está a punto de perder tres fechas o trescientas.
  const quitados = original.filter((c) => !pasos.some((p) => p.clave === c))

  useEffect(() => { void cargar().catch((e: PdcApiError) => setError(e.message)) }, [])

  const onGuardar = async () => {
    const v = validar(pasos)
    if (!v.ok) { setError(v.mensaje ?? ''); return }
    setOcupado(true); setError(''); setMensaje('')
    try {
      const r = await apiPost<{ pasos: number; calculados: number }>('/plan-compras/api/plan/pasos', { pasos: aPayload(pasos) })
      setMensaje(`Guardado: ${r.pasos} pasos. Se recalcularon ${r.calculados} paquetes.`)
      setConfigurado(true)
    } catch (e) { setError((e as PdcApiError).message) } finally { setOcupado(false) }
  }

  const onRestablecer = async () => {
    setOcupado(true); setError(''); setMensaje('')
    try {
      const r = await apiPost<{ calculados: number }>('/plan-compras/api/plan/pasos/restablecer', {})
      await cargar()
      setMensaje(`La obra vuelve al proceso por defecto. Se recalcularon ${r.calculados} paquetes.`)
    } catch (e) { setError((e as PdcApiError).message) } finally { setOcupado(false) }
  }

  return (
    <section className="pdc-bloque pdc-pasos">
      <header className="pdc-paq-header">
        <div>
          <h1>Pasos del proceso de contratación</h1>
          <p className="pdc-sub">
            El camino que recorre cada paquete antes de llegar a obra. Cambiarlo mueve las fechas de
            todos los paquetes de esta obra.
          </p>
        </div>
        <Link to="/ensamble/plan" className="pdc-paq-secundario">Volver al plan</Link>
      </header>

      {!configurado && (
        <p className="pdc-info" role="status">
          Esta obra usa el proceso por defecto de la empresa ({pasos.length} pasos). El primer cambio
          que guardes crea su configuración propia.
        </p>
      )}
      {error !== '' && <div className="pdc-error" role="status">{error}</div>}
      {mensaje !== '' && <div className="pdc-info" role="status">{mensaje}</div>}

      <ol className="pdc-pasos-lista" data-testid="pdc-pasos-lista">
        {pasos.map((p, i) => (
          <li key={p.clave} className="pdc-pasos-fila">
            <span className="pdc-pasos-orden">{i + 1}</span>
            <span className="pdc-pasos-nombre">{p.nombre}</span>
            <input
              className="pdc-pasos-alias" type="text" value={p.alias} placeholder="Nombre en esta obra (opcional)"
              aria-label={`Nombre de «${p.nombre}» en esta obra`}
              onChange={(e) => setPasos(pasos.map((q, j) => (j === i ? { ...q, alias: e.target.value } : q)))}
            />
            {p.colLegacy === null ? (
              <label className="pdc-pasos-dias">
                Días
                <input
                  type="number" min={0} value={p.diasFijos ?? ''}
                  aria-label={`Días que dura «${p.nombre}» en esta obra`}
                  onChange={(e) => setPasos(pasos.map((q, j) => (
                    j === i ? { ...q, diasFijos: e.target.value === '' ? null : Number(e.target.value) } : q
                  )))}
                />
              </label>
            ) : (
              // Los días salen del catálogo de la empresa y cambian por paquete (concreto no tarda lo
              // que unas puertas), así que aquí no hay un número que mostrar.
              <span className="pdc-pasos-dias-catalogo">Días según el catálogo, por paquete</span>
            )}
            <button type="button" disabled={i === 0} aria-label={`Subir ${p.nombre}`} onClick={() => setPasos(mover(pasos, i, i - 1))}>↑</button>
            <button type="button" disabled={i === pasos.length - 1} aria-label={`Bajar ${p.nombre}`} onClick={() => setPasos(mover(pasos, i, i + 1))}>↓</button>
            <button type="button" aria-label={`Quitar ${p.nombre}`} onClick={() => setPasos(quitar(pasos, p.clave))}>Quitar</button>
          </li>
        ))}
      </ol>

      <div className="pdc-paq-toolbar">
        <select
          data-testid="pdc-pasos-agregar" value="" aria-label="Agregar un paso"
          onChange={(e) => {
            const c = cat.find((x) => x.clave === e.target.value)
            if (c) setPasos(agregar(pasos, c))
          }}
        >
          <option value="">Agregar un paso…</option>
          {disponibles(cat, pasos).map((c) => <option key={c.clave} value={c.clave}>{c.nombre}</option>)}
        </select>
        <button type="button" className="pdc-paq-primario" data-testid="pdc-pasos-guardar" disabled={ocupado} onClick={() => void onGuardar()}>
          Guardar y recalcular
        </button>
        {configurado && (
          <button type="button" data-testid="pdc-pasos-restablecer" disabled={ocupado} onClick={() => void onRestablecer()}>
            Volver al proceso por defecto
          </button>
        )}
      </div>

      {/* El aviso de la respuesta 5 del grilleo, con el número delante. Cuando B1 registre avance
          real, esas mismas filas llevarán fechas reales: por eso se avisa antes de guardar, no después. */}
      {quitados.length > 0 && (
        <p className="pdc-error" role="status" data-testid="pdc-pasos-aviso-quitar">
          Vas a quitar {quitados.length === 1 ? 'un paso' : `${quitados.length} pasos`}
          {' '}({quitados.map((k) => cat.find((c) => c.clave === k)?.nombre ?? k).join(', ')}).
          {' '}Al guardar se borrarán {quitados.length * paquetesConPlan}
          {' '}fechas ya calculadas: una por cada uno de los {paquetesConPlan} paquetes con plan.
        </p>
      )}
    </section>
  )
}
```

- [ ] **Step 2: Registrar la ruta**

En `src/App.tsx`, añadir el import `import PasosContratacion from './pages/PasosContratacion'` y, tras la línea de `/ensamble/plan`:

```tsx
          {/* Fuera de PANTALLAS a propósito: se configura una vez por obra, y se llega desde el
              Plan de compras. Una pestaña permanente para eso sería ruido en la barra. */}
          <Route path="/ensamble/plan/pasos" element={<PasosContratacion />} />
```

- [ ] **Step 3: El enlace en Plan de fechas (único cambio permitido en ese archivo)**

En `src/pages/PlanFechas.tsx`, añadir `import { Link } from 'react-router-dom'` (o sumar `Link` al import existente de `react-router-dom` si lo hay) y, dentro de `.pdc-paq-toolbar` justo después del botón «Recalcular» (línea ~499):

```tsx
        <Link to="/ensamble/plan/pasos" className="pdc-paq-secundario" data-testid="pdc-plan-configurar-pasos">
          Configurar pasos
        </Link>
```

- [ ] **Step 4: Estilos**

Añadir al final de `src/styles.css`:

```css
/* A4.1 — configuración de los pasos del proceso de contratación. */
.pdc-pasos-lista { list-style: none; margin: 0 0 1rem; padding: 0; }
.pdc-pasos-fila {
  display: flex; align-items: center; gap: .5rem;
  padding: .4rem .6rem; border-bottom: 1px solid var(--aia-border, #e5e7eb);
}
.pdc-pasos-orden { min-width: 1.5rem; color: var(--aia-text-muted, #6b7280); font-variant-numeric: tabular-nums; }
.pdc-pasos-nombre { min-width: 14rem; font-weight: 600; }
.pdc-pasos-alias { flex: 1 1 12rem; min-width: 8rem; }
.pdc-pasos-dias input { width: 5rem; margin-left: .35rem; }
.pdc-pasos-dias-catalogo { color: var(--aia-text-muted, #6b7280); font-size: .85em; }
```

- [ ] **Step 5: Build y tests de la SPA**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test && npm run build
```

Esperado: Vitest completo en verde (los previos + los 9 nuevos) y `dist/assets/pdc.js` regenerado sin errores de TypeScript.

- [ ] **Step 6: PARADA — no publicar el bundle todavía**

**Decisión del usuario (2026-07-28):** otra sesión tiene cambios sin commitear en
`public/pdc-app/assets/pdc.js` y `pdc.css`, y su fuente no está en esta copia del repo de la SPA:
copiar el `dist/` encima **borraría trabajo ajeno**. Así que aquí se para, se avisa al usuario, y se
espera a que esa sesión commitee o republique lo suyo.

Comprobar el estado antes de decidir:

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git status --porcelain -- public/pdc-app/
```

Si la salida está **vacía**, el conflicto se resolvió y se puede publicar:

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && cp -R dist/. "/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/"
```

Si NO está vacía, saltar los pasos 7 y 8 de esta tarea y el e2e de la Task 7 (ambos necesitan el
bundle publicado para poder cargar la pantalla), y dejarlos anotados como lo único pendiente.

- [ ] **Step 7: Verificar en el navegador**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose up -d --build db app adminer
```

Abrir el navegador integrado en `http://localhost:8091/plan-compras#/ensamble/plan`, pulsar «Configurar pasos», comprobar que la lista muestra los siete con el aviso de «proceso por defecto», agregar «Aprobación del cliente», guardar, y leer el mensaje «Se recalcularon N paquetes». Volver al plan y comprobar que la columna de pasos muestra el paso nuevo. Revisar la consola: sin errores.

- [ ] **Step 8: Commit en los dos repos**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git add src/pages/PasosContratacion.tsx src/App.tsx src/pages/PlanFechas.tsx src/styles.css dist && git commit -m "feat(pdc): la pantalla donde una obra arma su propio proceso de contratación"
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add public/pdc-app && git commit -m "chore(pdc): publica el bundle con la configuración de pasos"
```

---

### Task 7: Gates completos, e2e y documentación

**Files:**
- Create: `tests/browser/pdc-v2-pasos.spec.mjs` (worktree lps-aia)
- Modify: `CLAUDE.md` (repo `plan-de-compras`, sección «Estado actual»)
- Modify: `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (marcar A4.1 y registrar los cuatro pendientes)

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: la evidencia de la condición de hecho.

- [ ] **Step 1: Escribir el e2e**

Crear `tests/browser/pdc-v2-pasos.spec.mjs`. **Va contra el proyecto sandbox**, no contra el real: `usarSandboxPdc()` resetea «PDC Sandbox E2E» antes de cada test, así que configurar sus pasos no ensucia a Da Porto ni exige `PDC_E2E_DESTRUCTIVO=1`. Copiar la cabecera exacta de `tests/browser/pdc-v2-plan.spec.mjs:1-16`.

```js
// tests/browser/pdc-v2-pasos.spec.mjs — A4.1: la obra arma su propio proceso de contratación.
//
// Contra el sandbox sacrificable, no contra el proyecto real: la configuración de pasos mueve las
// fechas de TODOS los paquetes de la obra, y Da Porto es la línea base con la que se demuestra la
// cero regresión. `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

usarSandboxPdc();

test('una obra agrega un paso propio y vuelve al proceso por defecto', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/plan-compras#/ensamble/plan');

  await page.getByTestId('pdc-plan-configurar-pasos').click();
  const lista = page.getByTestId('pdc-pasos-lista');
  await expect(lista.locator('li')).toHaveCount(7);
  await expect(page.getByText('usa el proceso por defecto de la empresa')).toBeVisible();

  // Licify y Aprobación del cliente son justamente las dos variantes que el roadmap pedía no
  // hardcodear: si el catálogo no las ofrece, este selectOption falla.
  await page.getByTestId('pdc-pasos-agregar').selectOption('aprobacion_cliente');
  await expect(lista.locator('li')).toHaveCount(8);
  await page.getByTestId('pdc-pasos-guardar').click();
  await expect(page.getByText(/Se recalcularon \d+ paquetes/)).toBeVisible();

  // Y la vuelta atrás, que es lo que hace seguro probar esto en cualquier obra.
  await page.getByTestId('pdc-pasos-restablecer').click();
  await expect(page.getByText('proceso por defecto')).toBeVisible();
  await expect(lista.locator('li')).toHaveCount(7);

  await logout(page);
});
```

- [ ] **Step 2: Correr toda la batería de gates**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_global_table_safety.php && docker compose exec -T app php tests/test_global_table_reconciliation.php && docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php && docker compose exec -T app php tests/test_pdc_v2_rbac_pasos.php
```

Esperado: los cinco en `OK` / código 0. **Si `test_global_table_safety.php` se queja de `general_pasos_contratacion`**, registrar la tabla donde ese gate espera los catálogos globales (leer el propio test para ver qué lista consulta) y volver a correrlo.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && npx playwright test tests/browser/pdc-v2-pasos.spec.mjs tests/browser/pdc-v2-plan.spec.mjs --workers=1
```

- [ ] **Step 3: Volver a probar la condición de hecho, de punta a punta**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -D "$MYSQL_DATABASE" -e "SELECT paquete_id, fecha_ancla, fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = 73 ORDER BY paquete_id; SELECT COUNT(*) pasos FROM pdc_plan_paso WHERE project_id = 73; SELECT COUNT(*) config FROM pdc_proyecto_pasos WHERE project_id = 73;"'
```

Esperado, comparado contra `goals/pdc-a41-pasos-configurables/linea-base.txt`: **las mismas 11 filas con las mismas fechas**, 77 pasos, y `config = 0`.

- [ ] **Step 4: Actualizar `CLAUDE.md`**

En la sección «Estado actual» del repo `plan-de-compras`, añadir A4.1 a la lista de fases implementadas y un bloque corto tras el de A3.3:

```markdown
### A4.1 — Pasos del proceso de contratación configurables por proyecto

Los siete pasos dejaron de estar escritos en el código. Catálogo global `general_pasos_contratacion`
(9 pasos: los siete de siempre + `licify` + `aprobacion_cliente`, este último entre Cuadros y
Legalización, que es donde lo tenían los 2 de 6 proyectos históricos de la «Variante B») y
configuración por obra `pdc_proyecto_pasos` (orden, alias, días fijos, apagado). **Cero filas para un
proyecto = los siete de siempre**: Da Porto no recibió configuración y sus 11 paquetes dan las mismas
fechas, verificado comparando las 11 cabeceras y las 77 filas de paso dentro de la misma corrida del
test.

- **Identidad, no posición:** `pdc_plan_paso` gana `paso_id` y su clave única pasa de
  `(project_id, paquete_id, orden)` a `(project_id, paquete_id, paso_id)`. Sin esto, meter un paso en
  medio haría que el upsert escribiera encima de la fila del vecino y el avance real que B1 cuelgue
  ahí se leería como si fuera de otro paso. El borrado de sobrantes es por identidad y lleva
  `paso_id IS NULL OR ...` a propósito: `NULL NOT IN (...)` vale NULL y dejaría vivas las filas sin
  identidad para siempre.
- **De dónde salen los días:** un paso con `col_legacy` los saca del catálogo legacy por paquete; uno
  sin ella lleva días fijos por obra (no se le agregan columnas a la tabla legacy compartida).
  `col_legacy` se filtra contra una lista blanca derivada de `PASOS` antes de interpolarse en el SQL.
- **Con desglose real el proceso se alarga** (cada número legacy es una medición de su paso); en los
  **provisionales** la mediana es el sobre completo: los días fijos se respetan y el resto se reparte
  entre los pasos con peso, re-normalizados. `total = max(mediana, Σ días fijos)`.
- `medianasPorTipo()` y `pesosDelCatalogo()` siguen midiéndose sobre las siete columnas legacy: son
  estadísticas de la empresa, no de una obra.
- RBAC `lps.paquetes_contratacion.reglas` (el de A3.3) para cambiar los pasos — mueve las fechas de
  toda la obra, así que no basta con poder asignar insumos. Pantalla en `#/ensamble/plan/pasos`, fuera
  de la barra de pestañas, accesible desde «Configurar pasos» en el Plan de compras.
```

- [ ] **Step 5: Actualizar el roadmap**

En `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`, tras la línea de la Fase A4:

```markdown
- **Fase A4.1 — Pasos configurables por proyecto:** catálogo global `general_pasos_contratacion` +
  `pdc_proyecto_pasos` por obra (orden, alias, días fijos, apagado); `pdc_plan_paso` pasa a
  identificarse por `paso_id` en vez de por posición (contrato con B1); variantes Licify y Aprobación
  del cliente disponibles sin tocar código. Cero regresión: obra sin configurar = los siete de
  siempre. *(Implementada.)*
  - **Pendientes registrados, fuera del alcance de A4.1** (decisión del grilleo 2026-07-28): listas de
    pasos distintas por modalidad o tipo de negociación dentro de una misma obra · copiar la
    configuración de una obra a otra · historial de versiones de la configuración · editar las
    duraciones del catálogo legacy desde la pantalla de pasos.
```

- [ ] **Step 6: Commit final y cierre de rama**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add tests/browser/pdc-v2-pasos.spec.mjs && git commit -m "test(pdc): el e2e arma un proceso propio y lo devuelve al de la empresa"
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git add CLAUDE.md docs/superpowers && git commit -m "docs(pdc): A4.1 cierra el último requisito escrito de A4"
```

Al terminar, usar `superpowers:finishing-a-development-branch` para decidir la integración.

---

## Notas de verificación (la condición de hecho, explícita)

Se considera hecho cuando, con salida real de comandos de la sesión:

1. Un proyecto define sus pasos incluidas las variantes Licify y Aprobación del cliente — Task 6, paso 7 (navegador) y Task 3, paso 10 (test).
2. El plan de fechas se recalcula con ellos — assert «Agregar «Aprobación del cliente, 15 días» suma exactamente 15 al total».
3. Da Porto, sin tocar su configuración, sigue dando **las mismas 11 filas con las mismas fechas** — asserts de cero regresión de la Task 3 más la comparación contra `linea-base.txt` de la Task 7, paso 3.
