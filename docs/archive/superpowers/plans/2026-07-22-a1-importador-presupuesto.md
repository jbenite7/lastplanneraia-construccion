---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-22
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-22-a1-importador-presupuesto.md
resumen: Importar el presupuesto Excel (hoja Presupuesto) a 3 tablas MySQL por projectid en lps-aia, con flujo preview→confirmar, versionado con única activa…
---

# Fase A1: Importador de Presupuesto — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Importar el presupuesto Excel (hoja `Presupuesto`) a 3 tablas MySQL por `project_id` en lps-aia, con flujo preview→confirmar, versionado con única activa, validación todo-o-nada, RBAC `lps.pdc.importar`, y la vista de import que estrena la navegación Ensamble | Seguimiento en la SPA.

**Architecture:** El backend agrega un parser PhpSpreadsheet (read-only, encabezados por nombre), un store de archivos temporales privado con TTL, y un servicio transaccional de confirmación; tres endpoints JSON delgados bajo `/plan-compras/api/presupuesto/*`. La SPA agrega `apiUpload` (multipart+CSRF), un reducer puro de estados del import (testeable) y la vista con AG Grid para errores e historial. Spec: `docs/superpowers/specs/2026-07-22-a1-importador-presupuesto-design.md`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker lps-aia), PhpSpreadsheet 5.x (ya en composer, ext zip/gd OK), FastRoute, React+TS+Vite+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{"ok":true,"data":...}` | `{"ok":false,"error":{"code","message",...}}` con `JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE`.
- RBAC: importar/confirmar = `lps.pdc.importar` (nueva; A por `'*'`, D por catálogo+patch); listar versiones = `lps.pdc.ver`. CSRF form key `plan_compras_v2` (`$_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token']`).
- Upload: solo `.xlsx`, máx **10MB** (10_485_760 bytes). Códigos de error: `INVALID_FILE`, `VALIDATION_FAILED`, `FILE_TOO_LARGE`, `TOKEN_EXPIRED`, `FORBIDDEN`, `NO_PROJECT`, `CSRF_INVALID`.
- Tablas: `project_id int NOT NULL` + índices liderados por `project_id` + `utf8mb4_unicode_ci`; única versión activa por proyecto garantizada por transacción. Aislamiento explícito `project_id = ?` en todo SQL (PDO prepared).
- Archivos temporales: `storage/pdc-imports/` (raíz de lps-aia) con `.htaccess` `Require all denied` (el docroot ES la raíz del repo — SiteGround y Docker); tokens `[a-f0-9]{32}`; TTL 3600s con limpieza oportunista.
- Tope de reporte: 200 errores por archivo. Todo-o-nada: con errores no se persiste NADA.
- Sin PHPUnit: tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) contra el MySQL real del Docker; datos de prueba con `project_id` 999901/999902 (limpieza idempotente al inicio y fin del test).
- SPA: TypeScript estricto, AG Grid Community, identificadores en inglés, comentarios en español, UI en español.
- Commits: `feat(pdc-v2): ...` en lps-aia, `feat(pdc): ...` en plan-de-compras. Rama `pdc-a1-importador` en ambos repos.
- Los tests de lps-aia corren con `docker compose exec app php tests/...` (levantar con `docker compose up -d app db`, sin rebuild).

---

## File Structure

**lps-aia (rama `pdc-a1-importador`):**
```
database/migrations/20260722_pdc_v2_presupuesto_tables.sql   # T1: 3 tablas
database/patches/20260722_pdc_importar_rbac.sql              # T2: permiso en BD
src/Security/RbacCatalog.php                                 # T2: Modify (definitions + fallback D)
tests/test_pdc_v2_rbac_importar.php                          # T2
tests/support/pdc_fixture_presupuesto.php                    # T3: generador de .xlsx de prueba
src/Services/Pdc/PresupuestoExcelParser.php                  # T3
tests/test_pdc_v2_import_parser.php                          # T3
src/Services/Pdc/PresupuestoImportStore.php                  # T4: temporales privados
src/Controllers/Api/PlanComprasJsonRespuestas.php            # T4: trait ok()/fail()
src/Services/Pdc/PresupuestoImportService.php                # T4 (preview) + T5 (confirmar)
tests/test_pdc_v2_import_flujo.php                           # T5: flujo BD completo
src/Controllers/Api/PlanComprasImportController.php          # T6
src/Controllers/Api/PlanComprasApiController.php             # T6: Modify (usa el trait)
public/index.php                                             # T6: +3 rutas
tests/browser/fixtures/pdc/presupuesto-mini.xlsx             # T10: fixture e2e (generado+commiteado)
tests/browser/pdc-v2-import.spec.mjs                         # T10
public/pdc-app/**                                            # T9: bundle regenerado
```

**plan-de-compras (rama `pdc-a1-importador`):**
```
src/lib/api.ts                    # T7: Modify (apiUpload + PdcApiError.details)
src/lib/api.test.ts               # T7: Modify (+3 tests)
src/lib/types.ts                  # T7: Modify (+tipos de import)
src/lib/importState.ts            # T8: reducer puro de la vista
src/lib/importState.test.ts       # T8
src/App.tsx                       # T8: Modify (nav 2 submódulos + rutas)
src/styles.css                    # T8: Modify (nav)
src/pages/ImportarPresupuesto.tsx # T9
docs/superpowers/plans/…          # este plan (ya commiteado)
CLAUDE.md                         # T10: Modify (estado A1)
```

---

### Task 1: Migración de las 3 tablas de presupuesto (lps-aia)

**Files:**
- Create: `database/migrations/20260722_pdc_v2_presupuesto_tables.sql`

**Interfaces:**
- Produces: tablas `pdc_presupuesto_versiones`, `pdc_presupuesto_items`, `pdc_presupuesto_apu_insumos` (columnas exactas abajo — T5 inserta en ellas).

- [ ] **Step 1: Crear branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && git checkout main && git pull --ff-only 2>/dev/null; git checkout -b pdc-a1-importador
```

- [ ] **Step 2: Escribir la migración**

```sql
-- 20260722_pdc_v2_presupuesto_tables.sql
-- PDC v2 / Fase A1: presupuesto importado, versionado por proyecto.
-- Convención tablas globales: project_id NOT NULL + índice liderado por project_id.

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_versiones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_label` varchar(100) NOT NULL DEFAULT '',
  `archivo_nombre` varchar(255) NOT NULL,
  `archivo_hash` char(64) NOT NULL,
  `total_actividades` int NOT NULL DEFAULT 0,
  `total_insumos` int NOT NULL DEFAULT 0,
  `costo_total` decimal(18,2) NOT NULL DEFAULT 0,
  `activa` tinyint NOT NULL DEFAULT 0,
  `importado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpv_project_activa` (`project_id`, `activa`),
  KEY `idx_pdcpv_project_created` (`project_id`, `created_at`),
  KEY `idx_pdcpv_project_hash` (`project_id`, `archivo_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `codigo_padre` varchar(50) DEFAULT NULL,
  `nivel` tinyint NOT NULL,
  `tipo_fila` enum('capitulo','subcapitulo','grupo','actividad') NOT NULL,
  `descripcion` varchar(500) NOT NULL DEFAULT '',
  `unidad` varchar(20) DEFAULT NULL,
  `cantidad` decimal(18,4) DEFAULT NULL,
  `id_apu` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpi_project_version_codigo` (`project_id`, `version_id`, `codigo`),
  KEY `idx_pdcpi_project_version_tipo` (`project_id`, `version_id`, `tipo_fila`),
  CONSTRAINT `fk_pdcpi_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_apu_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `item_id` bigint NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `unidad` varchar(20) NOT NULL DEFAULT '',
  `cant_apu` decimal(18,6) DEFAULT NULL,
  `rendimiento` decimal(18,6) DEFAULT NULL,
  `cantidad_total` decimal(18,4) DEFAULT NULL,
  `valor_unitario` decimal(18,2) DEFAULT NULL,
  `valor_total` decimal(18,2) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpai_project_version_item` (`project_id`, `version_id`, `item_id`),
  KEY `idx_pdcpai_project_version_desc` (`project_id`, `version_id`, `descripcion`(191)),
  CONSTRAINT `fk_pdcpai_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdcpai_item` FOREIGN KEY (`item_id`) REFERENCES `pdc_presupuesto_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 3: Aplicar en Docker y verificar esquema**

```bash
docker compose up -d app db
docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/20260722_pdc_v2_presupuesto_tables.sql
docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SHOW CREATE TABLE pdc_presupuesto_versiones\G SHOW CREATE TABLE pdc_presupuesto_items\G SHOW CREATE TABLE pdc_presupuesto_apu_insumos\G"' | grep -E "Table:|utf8mb4_unicode_ci|project_id"
```

Expected: las 3 tablas con `project_id` int NOT NULL y `utf8mb4_unicode_ci`. (Si las vars MYSQL_* no existen en el contenedor db, usar las credenciales del `.env` de lps-aia: `mysql -u$DB_USER -p$DB_PASS $DB_NAME`.)

- [ ] **Step 4: Gates de arquitectura de datos**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Expected: ambos exit 0 (las tablas nuevas no rompen el contrato).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260722_pdc_v2_presupuesto_tables.sql
git commit -m "feat(pdc-v2): tablas de presupuesto importado versionado (versiones/items/apu_insumos)"
```

---

### Task 2: Permiso RBAC `lps.pdc.importar` (lps-aia, TDD)

**Files:**
- Modify: `src/Security/RbacCatalog.php` (dos puntos: `permissionDefinitions()` junto a las claves `lps.pdc.*` ~línea 117; y el `array_merge` del rol `'D'` en `fallbackPermissionsByRole()` ~líneas 189-203)
- Create: `database/patches/20260722_pdc_importar_rbac.sql`
- Test: `tests/test_pdc_v2_rbac_importar.php`

**Interfaces:**
- Produces: `RbacService->can('lps.pdc.importar')` = true para A y D, false para R/OT/DCV/V/C/S/G/SG. T4/T6 la consumen.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\RbacCatalog;
use App\Security\RbacService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

echo "=== PDC v2: permiso lps.pdc.importar ===\n";

$assert(in_array('lps.pdc.importar', RbacCatalog::permissionKeys(), true), 'La clave existe en el catálogo.');

$rbac = new RbacService(Database::getInstance());
$assert($rbac->can('lps.pdc.importar', 'A'), 'A puede importar (wildcard).');
$assert($rbac->can('lps.pdc.importar', 'D'), 'D puede importar.');
foreach (['R', 'OT', 'DCV', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    $assert(!$rbac->can('lps.pdc.importar', $rol), "{$rol} NO puede importar.");
}

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_rbac_importar.php
```

Expected: FAIL en "La clave existe en el catálogo."

- [ ] **Step 3: Implementar — catálogo**

En `permissionDefinitions()`, inmediatamente después de la entrada `lps.pdc.auto_generar`, añadir:

```php
['key' => 'lps.pdc.importar', 'module' => 'lps', 'action' => 'pdc_importar', 'description' => 'Importar presupuesto Excel al plan de compras v2'],
```

En `fallbackPermissionsByRole()`, dentro del array literal del `array_merge` del rol `'D'` (el bloque que ya contiene `'lps.contratos.auto_definir'`), añadir una línea:

```php
'lps.pdc.importar',
```

(No tocar `$allWrite` — la clave es solo para A y D.)

- [ ] **Step 4: Implementar — patch SQL para entornos con tablas RBAC pobladas**

```sql
-- 20260722_pdc_importar_rbac.sql
-- PDC v2 / A1: permiso de importación de presupuesto (A y D). Idempotente.
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.pdc.importar', 'lps', 'pdc_importar', 'Importar presupuesto Excel al plan de compras v2', 1, 1, NOW(), NOW());

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A', 'lps.pdc.importar', 1, 'patch_20260722', NOW(), NOW()),
    ('D', 'lps.pdc.importar', 1, 'patch_20260722', NOW(), NOW());
```

Aplicarlo en Docker (mismo comando mysql del Task 1 Step 3 con este archivo).

- [ ] **Step 5: Correr el test y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_rbac_importar.php
```

Expected: 11 PASS, `=== OK ===`, exit 0.

- [ ] **Step 6: Commit**

```bash
git add src/Security/RbacCatalog.php database/patches/20260722_pdc_importar_rbac.sql tests/test_pdc_v2_rbac_importar.php
git commit -m "feat(pdc-v2): permiso lps.pdc.importar (catálogo + patch BD, roles A y D)"
```

---

### Task 3: Generador de fixtures .xlsx + parser del presupuesto (lps-aia, TDD)

**Files:**
- Create: `tests/support/pdc_fixture_presupuesto.php`
- Create: `src/Services/Pdc/PresupuestoExcelParser.php`
- Test: `tests/test_pdc_v2_import_parser.php`

**Interfaces:**
- Consumes: PhpSpreadsheet (vendor).
- Produces:
  - `pdcFixturePresupuestoValido(string $path): void` y `pdcFixturePresupuestoInvalido(string $path): void` (funciones globales del support).
  - `App\Services\Pdc\PresupuestoExcelParser::parse(string $filePath): array` — retorna
    `['valido'=>bool, 'versionLabel'=>?string, 'resumen'=>['capitulos'=>int,'subcapitulos'=>int,'grupos'=>int,'actividades'=>int,'insumos'=>int,'costoTotal'=>float], 'items'=>list<array{codigo,codigo_padre,nivel,tipo_fila,descripcion,unidad,cantidad,id_apu}>, 'insumos'=>list<array{codigo_actividad,descripcion,tipo_insumo,unidad,cant_apu,rendimiento,cantidad_total,valor_unitario,valor_total,iva}>, 'errores'=>list<array{fila:int,columna:string,motivo:string}>]`.
  - Lanza `\RuntimeException` con mensaje claro si el archivo no es xlsx legible, falta la hoja `Presupuesto` o faltan encabezados requeridos (nivel archivo → el controller lo mapea a `INVALID_FILE`).
  - Reglas: encabezados por nombre normalizado (mayúsculas, sin acentos, trim); fila con `TIPO INSUMO` no vacío = insumo de la actividad vigente; fila jerárquica con `ID APU` no vacío = **actividad** (cualquier nivel); si no, nivel 1=capitulo, 2=subcapitulo, ≥3=grupo; `cantidad_total = rendimiento × cantidad(actividad)`; `valor_total = cantidad_total × valor_unitario`; `costoTotal` = suma de valor_total; tope 200 errores.

- [ ] **Step 1: Escribir el generador de fixtures**

```php
<?php
// tests/support/pdc_fixture_presupuesto.php
// Genera archivos .xlsx sintéticos con la estructura de la hoja "Presupuesto"
// del software de presupuestos de AIA (ver spec A1).

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const PDC_FIXTURE_HEADERS = ['Código', 'Descripción', 'Padre', 'UM', 'CANTIDAD', 'SUBCAPITULO', 'ID PROYECTO', 'VERSION', 'ID APU', 'Cant APU', 'Rend', 'IVA', 'VrUnit', 'Tipo Insumo', 'Agrupacion'];

function pdcFixtureEscribir(string $path, array $rows): void
{
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Presupuesto');
    $sheet->fromArray(PDC_FIXTURE_HEADERS, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();
}

function pdcFixturePresupuestoValido(string $path): void
{
    //           Código, Descripción,             Padre,   UM,   CANT, SUBCAP, IDP, VERSION,        ID APU, CantAPU, Rend, IVA, VrUnit,  Tipo Insumo,               Agrup
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',           '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01',       'CAMPAMENTO',             '01',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01',    'INSTALACIONES',          '01.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',        '01.01.01', 'M2', 18, '', 102, 'PI_TEST_1', 'APU-001', null, null, null, null, '',                        ''],
        ['',            'TEJA DE ZINC',           '',      'M2', null, '', 102, 'PI_TEST_1', '',     1.05,  1.2, 19, 25000, 'MAT-CUBIERTAS',            ''],
        ['',            'AYUDANTE',               '',      'HC', null, '', 102, 'PI_TEST_1', '',     8.0,   0.5, null, 9500, 'MANO DE OBRA',             ''],
        ['02',          'ESTRUCTURA',             '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01',       'CONCRETOS',              '02',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01',    'LOSAS',                  '02.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01.01', 'LOSA MACIZA E=12',       '02.01.01', 'M3', 40, '', 102, 'PI_TEST_1', 'APU-002', null, null, null, null, '',                        ''],
        ['',            'CONCRETO 4000PSI',       '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.05, 19, 620000, 'MAT-CONCRETOS',           ''],
        ['',            'SERVICIO BOMBEO',        '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.0, null, 28000, 'EQUIPOS',                  ''],
    ]);
}

function pdcFixturePresupuestoInvalido(string $path): void
{
    pdcFixtureEscribir($path, [
        // Insumo sin actividad previa (fila 2) → error.
        ['',   'INSUMO HUERFANO', '', 'UN', null, '', 102, 'PI_TEST_BAD', '', 1.0, 1.0, null, 100, 'MAT-VARIOS', ''],
        ['01', 'CAPITULO',        '', '',   null, '', 102, 'PI_TEST_BAD', '', null, null, null, null, '', ''],
        ['01.01.01.01', 'ACTIVIDAD SIN PADRE', '01.01.01', 'M2', 10, '', 102, 'PI_TEST_BAD', 'APU-X', null, null, null, null, '', ''],
        // VrUnit no numérico (fila 5) y UM vacía (misma fila) → 2 errores.
        ['',   'INSUMO ROTO',     '', '',   null, '', 102, 'PI_TEST_BAD', '', 1.0, 1.0, null, 'abc', 'MAT-VARIOS', ''],
    ]);
}
```

- [ ] **Step 2: Escribir el test del parser (falla)**

```php
<?php
// tests/test_pdc_v2_import_parser.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

echo "=== PDC v2: parser del presupuesto ===\n";
$tmpDir = sys_get_temp_dir();
$valido = $tmpDir . '/pdc_fixture_valido.xlsx';
$invalido = $tmpDir . '/pdc_fixture_invalido.xlsx';
pdcFixturePresupuestoValido($valido);
pdcFixturePresupuestoInvalido($invalido);

$parser = new PresupuestoExcelParser();

// Archivo válido
$r = $parser->parse($valido);
$assert($r['valido'] === true, 'Fixture válido parsea sin errores.');
$assert($r['versionLabel'] === 'PI_TEST_1', 'versionLabel sale de la columna VERSION.');
$assert($r['resumen']['capitulos'] === 2 && $r['resumen']['subcapitulos'] === 2 && $r['resumen']['grupos'] === 2, 'Conteo de jerarquía (2/2/2).');
$assert($r['resumen']['actividades'] === 2 && $r['resumen']['insumos'] === 4, '2 actividades y 4 insumos.');
$assert(count($r['items']) === 8 && count($r['insumos']) === 4, 'Listas items/insumos completas.');
$acts = array_values(array_filter($r['items'], fn ($i) => $i['tipo_fila'] === 'actividad'));
$assert($acts[0]['codigo'] === '01.01.01.01' && $acts[0]['id_apu'] === 'APU-001', 'Actividad detectada por ID APU.');
// cantidad_total = rend × cantidad de actividad: TEJA = 1.2 × 18 = 21.6
$teja = array_values(array_filter($r['insumos'], fn ($i) => $i['descripcion'] === 'TEJA DE ZINC'))[0];
$assert(abs($teja['cantidad_total'] - 21.6) < 0.0001, 'cantidad_total = rendimiento × cantidad de la actividad.');
$assert(abs($teja['valor_total'] - (21.6 * 25000)) < 0.01, 'valor_total = cantidad_total × valor_unitario.');
$assert($teja['codigo_actividad'] === '01.01.01.01', 'Insumo amarrado a su actividad.');
// costoTotal = suma de valor_total de los 4 insumos
$esperado = (1.2 * 18 * 25000) + (0.5 * 18 * 9500) + (1.05 * 40 * 620000) + (1.0 * 40 * 28000);
$assert(abs($r['resumen']['costoTotal'] - $esperado) < 0.01, 'costoTotal es la suma de valor_total.');

// Archivo inválido: 3 errores esperados (huérfano, VrUnit, UM) y valido=false
$b = $parser->parse($invalido);
$assert($b['valido'] === false, 'Fixture inválido reporta valido=false.');
$motivos = array_map(fn ($e) => $e['motivo'], $b['errores']);
$assert(count($b['errores']) >= 3, 'Reporta al menos 3 errores (huérfano, VrUnit no numérico, UM vacía).');
$assert($b['errores'][0]['fila'] === 2, 'El error del insumo huérfano apunta a la fila 2 del Excel.');
// Padre inexistente para 01.01.01.01 (no se importó 01.01 ni 01.01.01)
$assert(count(array_filter($motivos, fn ($m) => str_contains($m, 'padre'))) >= 1, 'Detecta código padre inexistente.');

// Nivel archivo: hoja faltante → RuntimeException
$sinHoja = $tmpDir . '/pdc_fixture_sinhoja.xlsx';
$book = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$book->getActiveSheet()->setTitle('Otra');
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($sinHoja);
try {
    $parser->parse($sinHoja);
    $assert(false, 'Hoja faltante lanza RuntimeException.');
} catch (\RuntimeException $e) {
    $assert(str_contains($e->getMessage(), 'Presupuesto'), 'Hoja faltante lanza RuntimeException con mensaje claro.');
}

foreach ([$valido, $invalido, $sinHoja] as $f) { @unlink($f); }
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 3: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_import_parser.php
```

Expected: FAIL — `Class "App\Services\Pdc\PresupuestoExcelParser" not found`.

- [ ] **Step 4: Implementar el parser**

```php
<?php

namespace App\Services\Pdc;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parser de la hoja "Presupuesto" del Excel del software de presupuestos.
 * Reglas (spec A1): encabezados por nombre; fila con "Tipo Insumo" = insumo de la
 * actividad vigente; fila jerárquica con "ID APU" = actividad; validación
 * todo-o-nada con reporte por fila/columna (tope 200 errores).
 */
final class PresupuestoExcelParser
{
    public const SHEET = 'Presupuesto';
    public const MAX_ERRORES = 200;

    /** columnas requeridas → clave normalizada */
    private const REQUERIDAS = ['CODIGO', 'DESCRIPCION', 'UM', 'CANTIDAD', 'VERSION', 'ID APU', 'CANT APU', 'REND', 'VRUNIT', 'TIPO INSUMO'];

    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true); // sin estilos: menos memoria (SiteGround)
        $book = $reader->load($filePath);
        $sheet = $book->getSheetByName(self::SHEET);
        if ($sheet === null) {
            throw new \RuntimeException('El archivo no tiene la hoja "' . self::SHEET . '".');
        }
        $rows = $sheet->toArray(null, true, false, false);
        $book->disconnectWorksheets();

        if (count($rows) < 2) {
            throw new \RuntimeException('La hoja "' . self::SHEET . '" está vacía.');
        }

        $mapa = $this->mapearEncabezados($rows[0]);
        $faltantes = array_diff(self::REQUERIDAS, array_keys($mapa));
        if ($faltantes !== []) {
            throw new \RuntimeException('Faltan columnas requeridas: ' . implode(', ', $faltantes) . '.');
        }

        $items = [];
        $insumos = [];
        $errores = [];
        $porCodigo = [];
        $actividadVigente = null; // ['codigo'=>..., 'cantidad'=>float]
        $versionLabel = null;
        $conteo = ['capitulo' => 0, 'subcapitulo' => 0, 'grupo' => 0, 'actividad' => 0];
        $costoTotal = 0.0;

        $err = function (int $fila, string $col, string $motivo) use (&$errores): bool {
            if (count($errores) >= self::MAX_ERRORES) {
                return false;
            }
            $errores[] = ['fila' => $fila, 'columna' => $col, 'motivo' => $motivo];
            return count($errores) < self::MAX_ERRORES;
        };

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }
            $fila = $i + 1; // número de fila del Excel (1-based, encabezado = 1)
            // Encabezados opcionales (PADRE, SUBCAPITULO, AGRUPACION…) pueden no existir: '' en ese caso.
            $cel = function (string $k) use ($row, $mapa): string {
                $idx = $mapa[$k] ?? null;
                return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
            };

            if ($this->filaVacia($row)) {
                continue;
            }
            if ($versionLabel === null && $cel('VERSION') !== '') {
                $versionLabel = $cel('VERSION');
            }

            $tipoInsumo = $cel('TIPO INSUMO');
            if ($tipoInsumo !== '') {
                // ---- Fila de insumo ----
                if ($actividadVigente === null) {
                    if (!$err($fila, 'Tipo Insumo', 'Insumo sin actividad previa que lo contenga.')) break;
                    continue;
                }
                $um = $cel('UM');
                if ($um === '') {
                    if (!$err($fila, 'UM', 'El insumo no tiene unidad.')) break;
                }
                $rend = $this->numero($cel('REND'));
                $cantApu = $this->numero($cel('CANT APU'));
                $vrUnit = $this->numero($cel('VRUNIT'));
                if ($vrUnit === null) {
                    if (!$err($fila, 'VrUnit', 'Valor unitario no numérico o vacío.')) break;
                }
                if ($rend === null) {
                    if (!$err($fila, 'Rend', 'Rendimiento no numérico o vacío.')) break;
                }
                if ($um === '' || $vrUnit === null || $rend === null) {
                    continue; // fila insumo inválida: no acumular
                }
                $cantidadTotal = round($rend * $actividadVigente['cantidad'], 4);
                $valorTotal = round($cantidadTotal * $vrUnit, 2);
                $costoTotal += $valorTotal;
                $insumos[] = [
                    'codigo_actividad' => $actividadVigente['codigo'],
                    'descripcion' => mb_substr($cel('DESCRIPCION'), 0, 500),
                    'tipo_insumo' => mb_substr($tipoInsumo, 0, 100),
                    'unidad' => mb_substr($um, 0, 20),
                    'cant_apu' => $cantApu,
                    'rendimiento' => $rend,
                    'cantidad_total' => $cantidadTotal,
                    'valor_unitario' => $vrUnit,
                    'valor_total' => $valorTotal,
                    'iva' => $this->numero($cel('IVA')),
                ];
                continue;
            }

            // ---- Fila jerárquica ----
            $codigo = $cel('CODIGO');
            if ($codigo === '') {
                if (!$err($fila, 'Código', 'Fila sin código ni tipo de insumo.')) break;
                continue;
            }
            if (isset($porCodigo[$codigo])) {
                if (!$err($fila, 'Código', "Código duplicado: {$codigo}.")) break;
                continue;
            }
            $segmentos = explode('.', $codigo);
            $nivel = count($segmentos);
            $codigoPadre = $nivel > 1 ? implode('.', array_slice($segmentos, 0, -1)) : null;
            if ($codigoPadre !== null && !isset($porCodigo[$codigoPadre])) {
                if (!$err($fila, 'Código', "El código padre {$codigoPadre} no existe antes de {$codigo}.")) break;
            }
            $idApu = $cel('ID APU');
            $esActividad = $idApu !== '';
            $tipoFila = $esActividad ? 'actividad' : ($nivel === 1 ? 'capitulo' : ($nivel === 2 ? 'subcapitulo' : 'grupo'));
            $cantidad = $this->numero($cel('CANTIDAD'));
            if ($esActividad && $cantidad === null) {
                if (!$err($fila, 'CANTIDAD', "La actividad {$codigo} no tiene cantidad numérica.")) break;
                $cantidad = 0.0;
            }
            $conteo[$tipoFila]++;
            $porCodigo[$codigo] = true;
            $items[] = [
                'codigo' => mb_substr($codigo, 0, 50),
                'codigo_padre' => $codigoPadre,
                'nivel' => $nivel,
                'tipo_fila' => $tipoFila,
                'descripcion' => mb_substr($cel('DESCRIPCION'), 0, 500),
                'unidad' => $cel('UM') !== '' ? mb_substr($cel('UM'), 0, 20) : null,
                'cantidad' => $cantidad,
                'id_apu' => $esActividad ? mb_substr($idApu, 0, 50) : null,
            ];
            if ($esActividad) {
                $actividadVigente = ['codigo' => $codigo, 'cantidad' => (float) $cantidad];
            }
        }

        if (count($errores) >= self::MAX_ERRORES) {
            $errores[] = ['fila' => 0, 'columna' => '', 'motivo' => 'Reporte truncado en ' . self::MAX_ERRORES . ' errores.'];
        }

        return [
            'valido' => $errores === [],
            'versionLabel' => $versionLabel,
            'resumen' => [
                'capitulos' => $conteo['capitulo'],
                'subcapitulos' => $conteo['subcapitulo'],
                'grupos' => $conteo['grupo'],
                'actividades' => $conteo['actividad'],
                'insumos' => count($insumos),
                'costoTotal' => round($costoTotal, 2),
            ],
            'items' => $items,
            'insumos' => $insumos,
            'errores' => $errores,
        ];
    }

    private function mapearEncabezados(array $headerRow): array
    {
        $mapa = [];
        foreach ($headerRow as $idx => $titulo) {
            $clave = $this->normalizar((string) $titulo);
            if ($clave !== '' && !isset($mapa[$clave])) {
                $mapa[$clave] = $idx;
            }
        }
        return $mapa;
    }

    private function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        return strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }

    private function numero(string $v): ?float
    {
        if ($v === '') {
            return null;
        }
        $v = str_replace([' ', '$'], '', $v);
        // Formato es-CO: coma decimal cuando no hay punto decimal claro
        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '', $v);
        }
        return is_numeric($v) ? (float) $v : null;
    }

    private function filaVacia(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }
}
```

- [ ] **Step 5: Correr el test y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_import_parser.php
```

Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 6: Calibración con el Excel real (verificación manual, no test)**

Copiar temporalmente el Excel real de DAPORTO al contenedor y parsearlo con un one-liner:

```bash
docker compose cp "/Volumes/Crucial X6/Developer/plan-de-compras/docs/102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx" app:/tmp/daporto.xlsx
docker compose exec app php -r "require 'vendor/autoload.php'; \$r=(new App\Services\Pdc\PresupuestoExcelParser())->parse('/tmp/daporto.xlsx'); echo json_encode(['valido'=>\$r['valido'],'resumen'=>\$r['resumen'],'errores'=>array_slice(\$r['errores'],0,10)], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), PHP_EOL;"
```

Expected: `valido: true` (o errores accionables que revelen reglas a ajustar — si hay ajustes, corregir el parser + fixtures y re-correr el test). Anotar el resumen real en el reporte del task. Borrar el temporal: `docker compose exec app rm /tmp/daporto.xlsx`.

- [ ] **Step 7: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PresupuestoExcelParser.php --memory-limit=1G
git add tests/support/pdc_fixture_presupuesto.php src/Services/Pdc/PresupuestoExcelParser.php tests/test_pdc_v2_import_parser.php
git commit -m "feat(pdc-v2): parser del presupuesto Excel con validación todo-o-nada y fixtures sintéticos"
```

---

### Task 4: Store temporal + trait de respuestas + servicio de preview (lps-aia, TDD)

**Files:**
- Create: `src/Services/Pdc/PresupuestoImportStore.php`
- Create: `src/Controllers/Api/PlanComprasJsonRespuestas.php`
- Create: `src/Services/Pdc/PresupuestoImportService.php` (solo preview en este task)
- Test: se agregan los primeros casos de `tests/test_pdc_v2_import_flujo.php` (el archivo se completa en Task 5)

**Interfaces:**
- Consumes: `PresupuestoExcelParser::parse` (Task 3), `Database::getInstance()`.
- Produces:
  - `PresupuestoImportStore`: `__construct(?string $baseDir = null)` (default `PROJECT_ROOT . '/storage/pdc-imports'`; crea el dir con `.htaccess` "Require all denied"); `guardar(string $origen, array $meta): string` (retorna token `[a-f0-9]{32}`; copia el archivo a `{token}.xlsx` y escribe `{token}.json` con meta); `ruta(string $token): ?string` (null si token inválido/inexistente/vencido); `meta(string $token): ?array`; `eliminar(string $token): void`; `limpiar(): void` (borra vencidos, TTL 3600s); const `TTL = 3600`.
  - Trait `PlanComprasJsonRespuestas`: `ok(array $data): void` y `fail(string $code, string $message, int $status, array $extra = []): void` — mismo envelope actual, con `$extra` mergeado dentro de `error` (para `errores[]`). Guards `headers_sent()` como el patrón vigente.
  - `PresupuestoImportService::__construct(\Database $db, PresupuestoImportStore $store, PresupuestoExcelParser $parser)`; `previewDesdeArchivo(string $rutaArchivo, string $nombreOriginal, int $projectId, string $usuario): array` — retorna `['ok'=>true, 'importToken'=>string, 'resumen'=>[...], 'versionLabel'=>?string, 'advertencias'=>string[]]` o `['ok'=>false, 'errores'=>[...]]`. Advertencia si ya existe versión con el mismo sha256 en el proyecto.

- [ ] **Step 1: Escribir los primeros casos del test (falla)** — crear `tests/test_pdc_v2_import_flujo.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_TEST_PROJECT_A = 999901;
const PDC_TEST_PROJECT_B = 999902;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_TEST_PROJECT_A, PDC_TEST_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]); // CASCADE borra items/insumos
    }
};
$limpiar();

echo "=== PDC v2: flujo de import (preview) ===\n";
$storeDir = sys_get_temp_dir() . '/pdc-imports-test-' . getmypid();
$store = new PresupuestoImportStore($storeDir);
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

$tmp = sys_get_temp_dir() . '/pdc_flujo_valido.xlsx';
pdcFixturePresupuestoValido($tmp);

// Preview válido: token + resumen, archivo persistido en el store, nada en BD.
$p = $service->previewDesdeArchivo($tmp, 'presupuesto.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($p['ok'] === true, 'Preview válido responde ok.');
$assert(preg_match('/^[a-f0-9]{32}$/', $p['importToken']) === 1, 'Token de 32 hex.');
$assert($p['resumen']['actividades'] === 2 && $p['resumen']['insumos'] === 4, 'Resumen con conteos correctos.');
$assert($p['advertencias'] === [], 'Sin advertencias en el primer import.');
$assert($store->ruta($p['importToken']) !== null, 'El archivo quedó en el store.');
$enBd = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TEST_PROJECT_A])->fetchColumn();
$assert($enBd === 0, 'Preview NO escribe en BD.');

// Preview inválido: errores y nada en el store.
$tmpBad = sys_get_temp_dir() . '/pdc_flujo_invalido.xlsx';
pdcFixturePresupuestoInvalido($tmpBad);
$pb = $service->previewDesdeArchivo($tmpBad, 'malo.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($pb['ok'] === false && count($pb['errores']) >= 3, 'Preview inválido responde errores.');

// Store: token inválido y TTL.
$assert($store->ruta('zzzz') === null, 'Token con formato inválido → null (sin path traversal).');
$assert(is_file($storeDir . '/.htaccess'), 'El store crea su .htaccess de denegación.');

echo "--- casos de confirmación se agregan en Task 5 ---\n";
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_import_flujo.php
```

Expected: FAIL — clases del servicio no existen.

- [ ] **Step 3: Implementar el store**

```php
<?php

namespace App\Services\Pdc;

/**
 * Archivos temporales del import (entre preview y confirmar).
 * El docroot es la raíz del repo: el directorio lleva su propio .htaccess
 * de denegación total. Tokens aleatorios de 32 hex; TTL con limpieza oportunista.
 */
final class PresupuestoImportStore
{
    public const TTL = 3600;

    private string $dir;

    public function __construct(?string $baseDir = null)
    {
        $this->dir = $baseDir ?? (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)) . '/storage/pdc-imports';
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        $htaccess = $this->dir . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n");
        }
    }

    public function guardar(string $origen, array $meta): string
    {
        $this->limpiar();
        $token = bin2hex(random_bytes(16));
        if (!copy($origen, $this->dir . "/{$token}.xlsx")) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal del import.');
        }
        file_put_contents($this->dir . "/{$token}.json", json_encode($meta, JSON_UNESCAPED_UNICODE));
        return $token;
    }

    public function ruta(string $token): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $ruta = $this->dir . "/{$token}.xlsx";
        if (!is_file($ruta) || (time() - (int) filemtime($ruta)) > self::TTL) {
            return null;
        }
        return $ruta;
    }

    public function meta(string $token): ?array
    {
        if ($this->ruta($token) === null) {
            return null;
        }
        $raw = @file_get_contents($this->dir . "/{$token}.json");
        return $raw === false ? null : (json_decode($raw, true) ?: null);
    }

    public function eliminar(string $token): void
    {
        if (preg_match('/^[a-f0-9]{32}$/', $token)) {
            @unlink($this->dir . "/{$token}.xlsx");
            @unlink($this->dir . "/{$token}.json");
        }
    }

    public function limpiar(): void
    {
        foreach (glob($this->dir . '/*.xlsx') ?: [] as $f) {
            if ((time() - (int) filemtime($f)) > self::TTL) {
                @unlink($f);
                @unlink(substr($f, 0, -5) . '.json');
            }
        }
    }
}
```

- [ ] **Step 4: Implementar el trait de respuestas**

```php
<?php

namespace App\Controllers\Api;

/**
 * Envelope JSON del módulo Plan de Compras v2:
 * {"ok":true,"data":...} | {"ok":false,"error":{"code","message",...extra}}.
 */
trait PlanComprasJsonRespuestas
{
    private function ok(array $data): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function fail(string $code, string $message, int $status, array $extra = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(
            ['ok' => false, 'error' => array_merge(['code' => $code, 'message' => $message], $extra)],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
```

- [ ] **Step 5: Implementar el servicio (preview)**

```php
<?php

namespace App\Services\Pdc;

/**
 * Orquesta el import del presupuesto: preview (parsear + guardar temporal)
 * y confirmación transaccional (Task 5).
 */
final class PresupuestoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly PresupuestoExcelParser $parser,
    ) {
    }

    public function previewDesdeArchivo(string $rutaArchivo, string $nombreOriginal, int $projectId, string $usuario): array
    {
        $resultado = $this->parser->parse($rutaArchivo);
        if (!$resultado['valido']) {
            return ['ok' => false, 'errores' => $resultado['errores']];
        }

        $hash = hash_file('sha256', $rutaArchivo);
        $advertencias = [];
        $repetida = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ? AND archivo_hash = ?',
            [$projectId, $hash],
        )->fetchColumn();
        if ($repetida > 0) {
            $advertencias[] = 'Este archivo ya fue importado antes en este proyecto (contenido idéntico).';
        }

        $token = $this->store->guardar($rutaArchivo, [
            'nombre' => $nombreOriginal,
            'hash' => $hash,
            'projectId' => $projectId,
            'usuario' => $usuario,
        ]);

        return [
            'ok' => true,
            'importToken' => $token,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
            'advertencias' => $advertencias,
        ];
    }
}
```

- [ ] **Step 6: Correr el test y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_import_flujo.php
```

Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 7: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc --memory-limit=1G
git add src/Services/Pdc/PresupuestoImportStore.php src/Controllers/Api/PlanComprasJsonRespuestas.php src/Services/Pdc/PresupuestoImportService.php tests/test_pdc_v2_import_flujo.php
git commit -m "feat(pdc-v2): store temporal privado, trait de envelope y preview del import"
```

---

### Task 5: Confirmación transaccional + versiones (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php` (añadir `confirmar` y `versiones`)
- Test: `tests/test_pdc_v2_import_flujo.php` (añadir casos; el bloque nuevo REEMPLAZA la línea `echo "--- casos de confirmación se agregan en Task 5 ---\n";`)

**Interfaces:**
- Consumes: store/parser/trait del Task 4; tablas del Task 1.
- Produces:
  - `confirmar(string $token, int $projectId): array` — si el token no existe/venció o su meta no corresponde al proyecto → `['ok'=>false,'code'=>'TOKEN_EXPIRED']`; si el archivo re-parseado es inválido → `['ok'=>false,'code'=>'INVALID_FILE']`. Éxito: TRANSACCIÓN (desactivar activa previa → insertar cabecera → items → insumos → commit), borra el temporal (token de un solo uso) y retorna `['ok'=>true,'versionId'=>int,'resumen'=>[...],'versionLabel'=>?string]`.
  - `versiones(int $projectId): array` — lista `[{id, versionLabel, archivoNombre, totalActividades, totalInsumos, costoTotal, activa, importadoPor, createdAt}]` orden DESC.

- [ ] **Step 1: Añadir los casos al test (fallan)** — reemplazar la línea `echo "--- casos de confirmación..."` por:

```php
echo "=== PDC v2: flujo de import (confirmar + versiones) ===\n";

// Confirmar el preview válido de arriba.
$c1 = $service->confirmar($p['importToken'], PDC_TEST_PROJECT_A);
$assert($c1['ok'] === true && $c1['versionId'] > 0, 'Confirmación crea la versión.');
$v = $db->query('SELECT * FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_TEST_PROJECT_A])->fetchAll(PDO::FETCH_ASSOC);
$assert(count($v) === 1 && $v[0]['version_label'] === 'PI_TEST_1', 'Única versión activa con label correcto.');
$nItems = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ?', [PDC_TEST_PROJECT_A, $c1['versionId']])->fetchColumn();
$nIns = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?', [PDC_TEST_PROJECT_A, $c1['versionId']])->fetchColumn();
$assert($nItems === 8 && $nIns === 4, 'Items (8) e insumos (4) persistidos.');
// item_id de los insumos apunta a la actividad correcta
$row = $db->query(
    'SELECT i.codigo FROM pdc_presupuesto_apu_insumos a JOIN pdc_presupuesto_items i ON i.id = a.item_id WHERE a.project_id = ? AND a.version_id = ? AND a.descripcion = ?',
    [PDC_TEST_PROJECT_A, $c1['versionId'], 'TEJA DE ZINC'],
)->fetchColumn();
$assert($row === '01.01.01.01', 'Insumo amarrado por item_id a su actividad.');

// Token de un solo uso.
$c2 = $service->confirmar($p['importToken'], PDC_TEST_PROJECT_A);
$assert($c2['ok'] === false && $c2['code'] === 'TOKEN_EXPIRED', 'El token no se puede reutilizar.');

// Segundo import → la primera versión queda inactiva, ambas se conservan.
$tmp2 = sys_get_temp_dir() . '/pdc_flujo_valido2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2 = $service->previewDesdeArchivo($tmp2, 'presupuesto-v2.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($p2['advertencias'] !== [], 'Re-import de contenido idéntico advierte.');
$c3 = $service->confirmar($p2['importToken'], PDC_TEST_PROJECT_A);
$versiones = $service->versiones(PDC_TEST_PROJECT_A);
$assert(count($versiones) === 2, 'Se conservan las 2 versiones.');
$activas = array_values(array_filter($versiones, fn ($x) => (int) $x['activa'] === 1));
$assert(count($activas) === 1 && $activas[0]['id'] === $c3['versionId'], 'Solo la nueva versión queda activa.');

// Aislamiento por proyecto: B no ve nada.
$assert($service->versiones(PDC_TEST_PROJECT_B) === [], 'Proyecto B no ve versiones de A.');

// Transaccionalidad: token de proyecto distinto no confirma nada en B.
$tmp3 = sys_get_temp_dir() . '/pdc_flujo_valido3.xlsx';
pdcFixturePresupuestoValido($tmp3);
$p3 = $service->previewDesdeArchivo($tmp3, 'x.xlsx', PDC_TEST_PROJECT_A, 'tester');
$cB = $service->confirmar($p3['importToken'], PDC_TEST_PROJECT_B);
$assert($cB['ok'] === false && $cB['code'] === 'TOKEN_EXPIRED', 'Token de otro proyecto se rechaza.');
foreach ([$tmp2, $tmp3] as $f) { @unlink($f); }
```

(y antes del `exit` final, mantener `@unlink($tmp); @unlink($tmpBad);` y la llamada `$limpiar();`.)

- [ ] **Step 2: Correr y ver que fallan los casos nuevos**

```bash
docker compose exec app php tests/test_pdc_v2_import_flujo.php
```

Expected: FAIL — `Call to undefined method ...::confirmar()`.

- [ ] **Step 3: Implementar `confirmar` y `versiones`** (añadir a `PresupuestoImportService`):

```php
    public function confirmar(string $token, int $projectId): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null || (int) ($meta['projectId'] ?? 0) !== $projectId) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }

        try {
            $resultado = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$resultado['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ? AND activa = 1', [$projectId]);
            $this->db->query(
                'INSERT INTO pdc_presupuesto_versiones
                    (project_id, version_label, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                [
                    $projectId,
                    (string) ($resultado['versionLabel'] ?? ''),
                    (string) ($meta['nombre'] ?? ''),
                    (string) ($meta['hash'] ?? ''),
                    $resultado['resumen']['actividades'],
                    $resultado['resumen']['insumos'],
                    $resultado['resumen']['costoTotal'],
                    (string) ($meta['usuario'] ?? ''),
                ],
            );
            $versionId = (int) $this->db->lastInsertId();

            $idPorCodigo = [];
            foreach ($resultado['items'] as $item) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_items
                        (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad, id_apu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$projectId, $versionId, $item['codigo'], $item['codigo_padre'], $item['nivel'], $item['tipo_fila'], $item['descripcion'], $item['unidad'], $item['cantidad'], $item['id_apu']],
                );
                $idPorCodigo[$item['codigo']] = (int) $this->db->lastInsertId();
            }

            foreach ($resultado['insumos'] as $insumo) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_apu_insumos
                        (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $projectId, $versionId, $idPorCodigo[$insumo['codigo_actividad']],
                        $insumo['descripcion'], $insumo['tipo_insumo'], $insumo['unidad'],
                        $insumo['cant_apu'], $insumo['rendimiento'], $insumo['cantidad_total'],
                        $insumo['valor_unitario'], $insumo['valor_total'], $insumo['iva'],
                    ],
                );
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token); // un solo uso

        return [
            'ok' => true,
            'versionId' => $versionId,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
        ];
    }

    public function versiones(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, version_label, archivo_nombre, total_actividades, total_insumos, costo_total, activa, importado_por, created_at
             FROM pdc_presupuesto_versiones WHERE project_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'versionLabel' => $r['version_label'],
            'archivoNombre' => $r['archivo_nombre'],
            'totalActividades' => (int) $r['total_actividades'],
            'totalInsumos' => (int) $r['total_insumos'],
            'costoTotal' => (float) $r['costo_total'],
            'activa' => (int) $r['activa'],
            'importadoPor' => $r['importado_por'],
            'createdAt' => $r['created_at'],
        ], $rows);
    }
```

- [ ] **Step 4: Correr el test completo y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_import_flujo.php
```

Expected: todos PASS (preview + confirmar + versiones), `=== OK ===`, exit 0.

- [ ] **Step 5: Gates + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc --memory-limit=1G
docker compose exec app php tests/test_global_table_safety.php
git add src/Services/Pdc/PresupuestoImportService.php tests/test_pdc_v2_import_flujo.php
git commit -m "feat(pdc-v2): confirmación transaccional del import con versionado y token de un solo uso"
```

---

### Task 6: Controller + rutas de los 3 endpoints (lps-aia)

**Files:**
- Create: `src/Controllers/Api/PlanComprasImportController.php`
- Modify: `src/Controllers/Api/PlanComprasApiController.php` (usar el trait — borrar sus `ok()`/`fail()` privados y añadir `use PlanComprasJsonRespuestas;`; su `fail` actual no recibe `$extra`, el trait es compatible)
- Modify: `public/index.php` (3 rutas junto al bloque `// Api/Plan de Compras v2`)

**Interfaces:**
- Consumes: `PresupuestoImportService`, `PresupuestoImportStore`, `PresupuestoExcelParser`, `RbacService->can()`, `CsrfTokenManager::validate(..., 'plan_compras_v2')`, trait.
- Produces (contrato HTTP para la SPA, Tasks 7–9):
  - `POST /plan-compras/api/presupuesto/preview` (multipart, campo `archivo`) → `{ok:true,data:{importToken,versionLabel,resumen,advertencias}}` | fail `VALIDATION_FAILED` 422 con `errores`, `INVALID_FILE` 422, `FILE_TOO_LARGE` 413, `CSRF_INVALID`/`FORBIDDEN` 403, `NO_PROJECT` 409.
  - `POST /plan-compras/api/presupuesto/confirmar` (JSON `{importToken}`) → `{ok:true,data:{versionId,versionLabel,resumen}}` | `TOKEN_EXPIRED` 410 | `INVALID_FILE` 422.
  - `GET /plan-compras/api/presupuesto/versiones` → `{ok:true,data:{versiones:[...]}}`.

- [ ] **Step 1: Rutas en `public/index.php`** (después de la ruta de contexto existente):

```php
$router->post('/plan-compras/api/presupuesto/preview', [\App\Controllers\Api\PlanComprasImportController::class, 'preview']);
$router->post('/plan-compras/api/presupuesto/confirmar', [\App\Controllers\Api\PlanComprasImportController::class, 'confirmar']);
$router->get('/plan-compras/api/presupuesto/versiones', [\App\Controllers\Api\PlanComprasImportController::class, 'versiones']);
```

- [ ] **Step 2: Implementar el controller**

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

/**
 * Endpoints del importador de presupuesto (PDC v2 / Fase A1).
 * Flujo: preview (multipart, valida todo, no persiste) → confirmar (transaccional).
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasImportController
{
    use PlanComprasJsonRespuestas;

    public const MAX_BYTES = 10_485_760; // 10MB

    private \Database $db;
    private PresupuestoImportService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new PresupuestoImportService($this->db, new PresupuestoImportStore(), new PresupuestoExcelParser());
    }

    /** POST /plan-compras/api/presupuesto/preview */
    public function preview(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }

        $archivo = $_FILES['archivo'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            $this->fail('INVALID_FILE', 'No llegó ningún archivo válido.', 422);
            return;
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            $this->fail('FILE_TOO_LARGE', 'El archivo supera el límite de 10MB.', 413);
            return;
        }
        $nombre = (string) ($archivo['name'] ?? 'presupuesto.xlsx');
        if (!preg_match('/\.xlsx$/i', $nombre)) {
            $this->fail('INVALID_FILE', 'Solo se aceptan archivos .xlsx.', 422);
            return;
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'], true)) {
            $this->fail('INVALID_FILE', 'El archivo no es un Excel .xlsx válido.', 422);
            return;
        }

        try {
            $r = $this->service->previewDesdeArchivo(
                $archivo['tmp_name'],
                $nombre,
                $projectId,
                (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            );
        } catch (\RuntimeException $e) {
            $this->fail('INVALID_FILE', $e->getMessage(), 422);
            return;
        }

        if (!$r['ok']) {
            $this->fail('VALIDATION_FAILED', 'El archivo tiene errores; no se importó nada.', 422, ['errores' => $r['errores']]);
            return;
        }
        $this->ok([
            'importToken' => $r['importToken'],
            'versionLabel' => $r['versionLabel'],
            'resumen' => $r['resumen'],
            'advertencias' => $r['advertencias'],
        ]);
    }

    /** POST /plan-compras/api/presupuesto/confirmar */
    public function confirmar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $token = (string) ($body['importToken'] ?? '');

        $r = $this->service->confirmar($token, $projectId);
        if (!$r['ok']) {
            if ($r['code'] === 'TOKEN_EXPIRED') {
                $this->fail('TOKEN_EXPIRED', 'La previsualización expiró o ya fue usada. Sube el archivo de nuevo.', 410);
            } else {
                $this->fail('INVALID_FILE', 'El archivo temporal ya no es válido. Sube el archivo de nuevo.', 422);
            }
            return;
        }
        $this->ok(['versionId' => $r['versionId'], 'versionLabel' => $r['versionLabel'], 'resumen' => $r['resumen']]);
    }

    /** GET /plan-compras/api/presupuesto/versiones */
    public function versiones(): void
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $this->ok(['versiones' => $this->service->versiones($projectId)]);
    }

    /** RBAC importar + proyecto + CSRF para los POST. Retorna projectId o null (ya respondió). */
    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.pdc.importar')) {
            $this->fail('FORBIDDEN', 'No autorizado para importar presupuestos.', 403);
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
}
```

- [ ] **Step 3: Refactor mínimo del controller existente al trait**

En `PlanComprasApiController.php`: añadir `use PlanComprasJsonRespuestas;` dentro de la clase y **eliminar** sus métodos privados `ok()` y `fail()` (el trait provee ambos; la firma de `fail` del trait añade `array $extra = []` — compatible con las llamadas existentes).

- [ ] **Step 4: Verificación estática + test de regresión del contexto**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasImportController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasImportController.php src/Controllers/Api/PlanComprasApiController.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_contexto.php
```

Expected: sin errores; test del contexto sigue 11/11 (el refactor al trait no cambió el envelope).

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/Api/PlanComprasImportController.php src/Controllers/Api/PlanComprasApiController.php public/index.php
git commit -m "feat(pdc-v2): endpoints preview/confirmar/versiones del importador con RBAC, CSRF y límites de archivo"
```

---

### Task 7: `apiUpload` + tipos del import en la SPA (plan-de-compras, TDD)

**Files:**
- Modify: `src/lib/api.ts` (añadir `apiUpload`; `PdcApiError` gana `details?: unknown`)
- Modify: `src/lib/types.ts` (tipos del import)
- Test: `src/lib/api.test.ts` (añadir 3 tests)

**Interfaces:**
- Consumes: `getBootstrap()` existente.
- Produces:
  - `apiUpload<T>(path: string, file: File, field = 'archivo'): Promise<T>` — POST FormData; header `X-CSRF-Token` + `X-AIA-Expect-Json`; **sin** `Content-Type` manual (el navegador pone el boundary); mismo envelope/errores que `apiPost`.
  - `PdcApiError.details` — el objeto `error` completo del envelope (para leer `errores[]` de `VALIDATION_FAILED`).
  - Tipos: `ImportResumen {capitulos; subcapitulos; grupos; actividades; insumos; costoTotal}`, `ImportPreview {importToken: string; versionLabel: string | null; resumen: ImportResumen; advertencias: string[]}`, `ImportErrorFila {fila: number; columna: string; motivo: string}`, `VersionPresupuesto {id; versionLabel; archivoNombre; totalActividades; totalInsumos; costoTotal; activa; importadoPor; createdAt}`.

- [ ] **Step 1: Branch en plan-de-compras**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git checkout main && git checkout -b pdc-a1-importador
```

- [ ] **Step 2: Tests que fallan** (añadir al final de `src/lib/api.test.ts`; reusar el `stubBootstrap`/`afterEach` existentes):

```ts
describe('apiUpload', () => {
  it('envía FormData con X-CSRF-Token y sin Content-Type manual', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({ ok: true, data: { importToken: 't' } }),
    })
    vi.stubGlobal('fetch', fetchMock)
    const file = new File(['x'], 'p.xlsx')
    await apiUpload('/plan-compras/api/presupuesto/preview', file)
    const [, init] = fetchMock.mock.calls[0]
    expect(init.method).toBe('POST')
    expect(init.body).toBeInstanceOf(FormData)
    expect((init.body as FormData).get('archivo')).toBe(file)
    expect(init.headers['X-CSRF-Token']).toBe('tok-csrf')
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
    expect('Content-Type' in init.headers).toBe(false)
  })

  it('propaga PdcApiError con details del envelope (errores de validación)', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false, status: 422,
      json: async () => ({ ok: false, error: { code: 'VALIDATION_FAILED', message: 'Con errores', errores: [{ fila: 2, columna: 'UM', motivo: 'vacía' }] } }),
    }))
    const err = await apiUpload('/plan-compras/api/presupuesto/preview', new File(['x'], 'p.xlsx')).catch((e) => e)
    expect((err as PdcApiError).code).toBe('VALIDATION_FAILED')
    expect(((err as PdcApiError).details as { errores: unknown[] }).errores).toHaveLength(1)
  })

  it('permite cambiar el nombre del campo', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({ ok: true, data: null }) })
    vi.stubGlobal('fetch', fetchMock)
    await apiUpload('/x', new File(['x'], 'f.bin'), 'adjunto')
    expect(((fetchMock.mock.calls[0][1] as RequestInit).body as FormData).get('adjunto')).not.toBeNull()
  })
})
```

(añadir `apiUpload` al import del test: `import { apiGet, apiPost, apiUpload, PdcApiError } from './api'`.)

- [ ] **Step 3: Correr y ver que falla** — `npx vitest run src/lib/api.test.ts` → FAIL (`apiUpload` no existe).

- [ ] **Step 4: Implementar** — en `src/lib/api.ts`:

En `PdcApiError`, añadir la propiedad y tercer parámetro:

```ts
export class PdcApiError extends Error {
  code: string
  details?: unknown
  constructor(code: string, message: string, details?: unknown) {
    super(message)
    this.code = code
    this.details = details
  }
}
```

En `request()`, al lanzar el error de envelope, pasar el objeto completo: `throw new PdcApiError(body.error.code, body.error.message, body.error)`.

Añadir al final:

```ts
export async function apiUpload<T>(path: string, file: File, field = 'archivo'): Promise<T> {
  const boot = await getBootstrap()
  const form = new FormData()
  form.append(field, file)
  // Sin Content-Type manual: el navegador define el boundary del multipart.
  return request<T>(path, {
    method: 'POST',
    headers: { 'X-CSRF-Token': boot.csrfToken },
    body: form,
  })
}
```

Y en `src/lib/types.ts` añadir los 4 tipos del bloque Interfaces (verbatim).

- [ ] **Step 5: Correr todo y commit**

```bash
npm run test && npm run build
git add src/lib/api.ts src/lib/api.test.ts src/lib/types.ts
git commit -m "feat(pdc): apiUpload multipart con CSRF y detalles de error de validación"
```

Expected: 12 tests PASS (9 previos + 3 nuevos), build OK.

---

### Task 8: Navegación de 2 submódulos + reducer del import (plan-de-compras, TDD)

**Files:**
- Create: `src/lib/importState.ts`
- Test: `src/lib/importState.test.ts`
- Modify: `src/App.tsx`, `src/styles.css`

**Interfaces:**
- Consumes: tipos del Task 7.
- Produces:
  - `type ImportState = { fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'; preview: ImportPreview | null; errores: ImportErrorFila[]; mensajeError: string | null }`
  - `importReducer(state: ImportState, action: ImportAction): ImportState` con `ImportAction = {type:'SUBIR'} | {type:'PREVIEW_OK'; preview: ImportPreview} | {type:'PREVIEW_ERRORES'; errores: ImportErrorFila[]} | {type:'FALLO'; mensaje: string} | {type:'CONFIRMAR'} | {type:'CONFIRMADO'} | {type:'REINICIAR'}` y `estadoInicial: ImportState`.
  - Rutas: `/` → redirect `/ensamble/importar`; `/ensamble/importar` (Task 9); `/ensamble/maestro` (la página MaestroInsumos existente); nav superior con Ensamble activo y "Seguimiento" deshabilitado (`aria-disabled`, sin ruta).

- [ ] **Step 1: Test del reducer (falla)** — `src/lib/importState.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { estadoInicial, importReducer } from './importState'
import type { ImportPreview } from './types'

const preview: ImportPreview = {
  importToken: 'a'.repeat(32),
  versionLabel: 'PI_TEST_1',
  resumen: { capitulos: 2, subcapitulos: 2, grupos: 2, actividades: 2, insumos: 4, costoTotal: 100 },
  advertencias: [],
}

describe('importReducer', () => {
  it('flujo feliz: idle → subiendo → previewOk → confirmando → confirmado', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    expect(s.fase).toBe('subiendo')
    s = importReducer(s, { type: 'PREVIEW_OK', preview })
    expect(s.fase).toBe('previewOk')
    expect(s.preview?.importToken).toBe(preview.importToken)
    s = importReducer(s, { type: 'CONFIRMAR' })
    expect(s.fase).toBe('confirmando')
    s = importReducer(s, { type: 'CONFIRMADO' })
    expect(s.fase).toBe('confirmado')
  })

  it('errores de validación llevan a previewErrores y limpian preview', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    s = importReducer(s, { type: 'PREVIEW_ERRORES', errores: [{ fila: 2, columna: 'UM', motivo: 'vacía' }] })
    expect(s.fase).toBe('previewErrores')
    expect(s.errores).toHaveLength(1)
    expect(s.preview).toBeNull()
  })

  it('FALLO desde cualquier fase guarda el mensaje y vuelve a idle', () => {
    let s = importReducer(estadoInicial, { type: 'SUBIR' })
    s = importReducer(s, { type: 'FALLO', mensaje: 'Sesión expirada' })
    expect(s.fase).toBe('idle')
    expect(s.mensajeError).toBe('Sesión expirada')
  })

  it('REINICIAR vuelve al estado inicial', () => {
    let s = importReducer(estadoInicial, { type: 'PREVIEW_OK', preview })
    s = importReducer(s, { type: 'REINICIAR' })
    expect(s).toEqual(estadoInicial)
  })
})
```

- [ ] **Step 2: Correr y ver que falla** — `npx vitest run src/lib/importState.test.ts` → FAIL.

- [ ] **Step 3: Implementar `src/lib/importState.ts`**

```ts
import type { ImportErrorFila, ImportPreview } from './types'

export type ImportState = {
  fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'
  preview: ImportPreview | null
  errores: ImportErrorFila[]
  mensajeError: string | null
}

export type ImportAction =
  | { type: 'SUBIR' }
  | { type: 'PREVIEW_OK'; preview: ImportPreview }
  | { type: 'PREVIEW_ERRORES'; errores: ImportErrorFila[] }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'CONFIRMAR' }
  | { type: 'CONFIRMADO' }
  | { type: 'REINICIAR' }

export const estadoInicial: ImportState = { fase: 'idle', preview: null, errores: [], mensajeError: null }

export function importReducer(state: ImportState, action: ImportAction): ImportState {
  switch (action.type) {
    case 'SUBIR':
      return { ...estadoInicial, fase: 'subiendo' }
    case 'PREVIEW_OK':
      return { fase: 'previewOk', preview: action.preview, errores: [], mensajeError: null }
    case 'PREVIEW_ERRORES':
      return { fase: 'previewErrores', preview: null, errores: action.errores, mensajeError: null }
    case 'FALLO':
      return { ...state, fase: 'idle', mensajeError: action.mensaje }
    case 'CONFIRMAR':
      return { ...state, fase: 'confirmando', mensajeError: null }
    case 'CONFIRMADO':
      return { ...state, fase: 'confirmado', mensajeError: null }
    case 'REINICIAR':
      return estadoInicial
  }
}
```

- [ ] **Step 4: Navegación en `src/App.tsx`** (reemplazar el componente):

```tsx
import { HashRouter, NavLink, Navigate, Route, Routes } from 'react-router-dom'
import MaestroInsumos from './pages/MaestroInsumos'
import ImportarPresupuesto from './pages/ImportarPresupuesto'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <nav className="pdc-nav" aria-label="Submódulos del plan de compras">
          <span className="pdc-nav-title">Plan de Compras</span>
          <NavLink to="/ensamble/importar" className="pdc-nav-link">Ensamble</NavLink>
          <span className="pdc-nav-link pdc-nav-disabled" aria-disabled="true" title="Disponible en la fase B">
            Seguimiento
          </span>
        </nav>
        <Routes>
          <Route path="/" element={<Navigate to="/ensamble/importar" replace />} />
          <Route path="/ensamble/importar" element={<ImportarPresupuesto />} />
          <Route path="/ensamble/maestro" element={<MaestroInsumos />} />
          <Route path="/maestro" element={<Navigate to="/ensamble/maestro" replace />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
```

(Nota: `ImportarPresupuesto` llega en Task 9 — para que este task compile de forma independiente, crear en este task el archivo `src/pages/ImportarPresupuesto.tsx` con el esqueleto mínimo `export default function ImportarPresupuesto() { return <section className="pdc-page"><h1>Importar presupuesto</h1></section> }`; Task 9 lo reemplaza.)

- [ ] **Step 5: Estilos de nav** (añadir a `src/styles.css`):

```css
.pdc-nav { display: flex; align-items: center; gap: 16px; max-width: 1180px; margin: 0 auto 20px; padding-bottom: 12px; border-bottom: 1px solid #2c2c2e; }
.pdc-nav-title { font-weight: 600; margin-right: 8px; }
.pdc-nav-link { color: #f4f1ea; text-decoration: none; opacity: 0.75; padding: 4px 2px; }
.pdc-nav-link.active { opacity: 1; border-bottom: 2px solid #69b578; }
.pdc-nav-disabled { opacity: 0.35; cursor: not-allowed; }
```

- [ ] **Step 6: Correr todo y commit**

```bash
npm run test && npm run build
git add src/lib/importState.ts src/lib/importState.test.ts src/App.tsx src/styles.css src/pages/ImportarPresupuesto.tsx
git commit -m "feat(pdc): navegación de 2 submódulos (Ensamble | Seguimiento) y reducer del flujo de import"
```

Expected: 16 tests PASS (12 + 4 del reducer), build OK.

---

### Task 9: Vista Importar Presupuesto + bundle sincronizado (plan-de-compras + lps-aia)

**Files:**
- Modify (reemplazar): `src/pages/ImportarPresupuesto.tsx`
- Generated (lps-aia): `public/pdc-app/**` vía `npm run sync`

**Interfaces:**
- Consumes: `apiUpload`/`apiGet`/`PdcApiError` (T7), `importReducer` (T8), tipos (T7), contrato HTTP (T6).
- Produces: vista completa en `#/ensamble/importar` con selectores estables para el e2e (T10): `input[data-testid="pdc-import-file"]`, `[data-testid="pdc-import-resumen"]`, botón `[data-testid="pdc-import-confirmar"]`, historial `[data-testid="pdc-import-versiones"]` (AG Grid con fila de la versión activa mostrando `Activa`).

- [ ] **Step 1: Implementar la vista** (reemplazar el esqueleto):

```tsx
import { useEffect, useReducer, useRef, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { AllCommunityModule, ModuleRegistry, themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { estadoInicial, importReducer } from '../lib/importState'
import type { ImportErrorFila, ImportPreview, VersionPresupuesto } from '../lib/types'

ModuleRegistry.registerModules([AllCommunityModule])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const colsErrores: ColDef<ImportErrorFila>[] = [
  { field: 'fila', headerName: 'Fila', width: 90 },
  { field: 'columna', headerName: 'Columna', width: 140 },
  { field: 'motivo', headerName: 'Motivo', flex: 1 },
]

const colsVersiones: ColDef<VersionPresupuesto>[] = [
  { field: 'versionLabel', headerName: 'Versión', flex: 1 },
  { field: 'archivoNombre', headerName: 'Archivo', flex: 1 },
  { field: 'totalActividades', headerName: 'Actividades', width: 120 },
  { field: 'totalInsumos', headerName: 'Insumos', width: 110 },
  {
    field: 'costoTotal', headerName: 'Costo total', width: 150,
    valueFormatter: (p) => p.value != null ? `$ ${Number(p.value).toLocaleString('es-CO')}` : '',
  },
  { field: 'importadoPor', headerName: 'Importó', width: 130 },
  { field: 'createdAt', headerName: 'Fecha', width: 160 },
  { field: 'activa', headerName: 'Estado', width: 100, valueFormatter: (p) => (p.value ? 'Activa' : '') },
]

export default function ImportarPresupuesto() {
  const [state, dispatch] = useReducer(importReducer, estadoInicial)
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const fileRef = useRef<HTMLInputElement>(null)

  const cargarVersiones = () => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }
  useEffect(cargarVersiones, [])

  const onArchivo = async (file: File | undefined) => {
    if (!file) return
    dispatch({ type: 'SUBIR' })
    try {
      const preview = await apiUpload<ImportPreview>('/plan-compras/api/presupuesto/preview', file)
      dispatch({ type: 'PREVIEW_OK', preview })
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'VALIDATION_FAILED') {
        const detalle = e.details as { errores?: ImportErrorFila[] } | undefined
        dispatch({ type: 'PREVIEW_ERRORES', errores: detalle?.errores ?? [] })
      } else {
        dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
      }
    } finally {
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const onConfirmar = async () => {
    if (!state.preview) return
    dispatch({ type: 'CONFIRMAR' })
    try {
      await apiPost('/plan-compras/api/presupuesto/confirmar', { importToken: state.preview.importToken })
      dispatch({ type: 'CONFIRMADO' })
      cargarVersiones()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const r = state.preview?.resumen

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Importar presupuesto</h1>
        <p>Sube el Excel exportado del software de presupuestos (hoja «Presupuesto», máx. 10MB).</p>
      </header>

      <input
        ref={fileRef}
        data-testid="pdc-import-file"
        type="file"
        accept=".xlsx"
        disabled={state.fase === 'subiendo' || state.fase === 'confirmando'}
        onChange={(e) => onArchivo(e.target.files?.[0])}
      />

      {state.fase === 'subiendo' && <p>Analizando el archivo…</p>}
      {state.mensajeError && <div className="pdc-error" role="alert">{state.mensajeError}</div>}

      {state.fase === 'previewErrores' && (
        <div className="pdc-bloque">
          <div className="pdc-error" role="alert">
            El archivo tiene {state.errores.length} error(es); no se importó nada. Corrige el Excel y vuelve a subirlo.
          </div>
          <div style={{ height: 280 }}>
            <AgGridReact<ImportErrorFila> theme={pdcTheme} rowData={state.errores} columnDefs={colsErrores} />
          </div>
        </div>
      )}

      {(state.fase === 'previewOk' || state.fase === 'confirmando') && r && (
        <div className="pdc-bloque" data-testid="pdc-import-resumen">
          <h2>Previsualización — {state.preview?.versionLabel ?? 'sin versión'}</h2>
          <p>
            {r.capitulos} capítulos · {r.subcapitulos} subcapítulos · {r.grupos} grupos · {r.actividades} actividades ·{' '}
            {r.insumos} insumos · Costo total $ {r.costoTotal.toLocaleString('es-CO')}
          </p>
          {state.preview?.advertencias.map((a) => (
            <p key={a} className="pdc-advertencia">⚠ {a}</p>
          ))}
          <button
            type="button"
            data-testid="pdc-import-confirmar"
            disabled={state.fase === 'confirmando'}
            onClick={onConfirmar}
          >
            {state.fase === 'confirmando' ? 'Importando…' : 'Confirmar e importar'}
          </button>
        </div>
      )}

      {state.fase === 'confirmado' && (
        <div className="pdc-bloque pdc-exito" role="status">
          Presupuesto importado: ahora es la versión activa del proyecto.
        </div>
      )}

      <div className="pdc-bloque" data-testid="pdc-import-versiones">
        <h2>Historial de versiones</h2>
        <div style={{ height: 260 }}>
          <AgGridReact<VersionPresupuesto> theme={pdcTheme} rowData={versiones} columnDefs={colsVersiones} />
        </div>
      </div>
    </section>
  )
}
```

Añadir a `src/styles.css`:

```css
.pdc-bloque { margin-top: 20px; }
.pdc-bloque h2 { font-size: 16px; margin: 0 0 8px; }
.pdc-advertencia { color: #ffca28; }
.pdc-exito { padding: 12px 16px; border: 1px solid #69b578; background: #17251c; color: #b7e4c4; border-radius: 8px; }
.pdc-bloque button { background: #1a5633; color: #f4f1ea; border: 1px solid #69b578; border-radius: 6px; padding: 8px 16px; cursor: pointer; }
.pdc-bloque button:disabled { opacity: 0.5; cursor: wait; }
```

- [ ] **Step 2: Verificar y commitear en plan-de-compras**

```bash
npm run test && npm run build
git add src/pages/ImportarPresupuesto.tsx src/styles.css
git commit -m "feat(pdc): vista Importar Presupuesto (preview, errores, confirmación e historial)"
```

Expected: 16 tests PASS, build OK.

- [ ] **Step 3: Sincronizar bundle y commitear en lps-aia**

```bash
npm run sync
cd "/Volumes/Crucial X6/Developer/lps-aia"
git add public/pdc-app
git commit -m "feat(pdc-v2): bundle con vista de import de presupuesto y navegación de submódulos"
```

---

### Task 10: Fixture e2e + spec Playwright + CLAUDE.md (lps-aia + plan-de-compras)

**Files:**
- Create (lps-aia): `tests/support/generar_fixture_e2e_pdc.php`, `tests/browser/fixtures/pdc/presupuesto-mini.xlsx` (generado y commiteado), `tests/browser/pdc-v2-import.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md` (estado A1)

**Interfaces:**
- Consumes: helpers `loginAndSelectProject`/`logout` de `tests/browser/support/session.mjs`; fixture Da Porto (projectId 73); selectores `data-testid` del Task 9; fixture generator del Task 3.

- [ ] **Step 1: Generar y commitear el fixture e2e**

```php
<?php
// tests/support/generar_fixture_e2e_pdc.php — regenerar el .xlsx del e2e cuando cambie el generador.
declare(strict_types=1);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdc_fixture_presupuesto.php';
$destino = __DIR__ . '/../browser/fixtures/pdc/presupuesto-mini.xlsx';
@mkdir(dirname($destino), 0775, true);
pdcFixturePresupuestoValido($destino);
echo "OK: {$destino}\n";
```

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose exec app php tests/support/generar_fixture_e2e_pdc.php
```

- [ ] **Step 2: Escribir el spec e2e**

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('importar presupuesto: preview, confirmación y versión activa', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    // La ruta raíz de la SPA redirige a Ensamble → Importar.
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });

    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);

    const resumen = page.locator('[data-testid="pdc-import-resumen"]');
    await expect(resumen).toContainText('PI_TEST_1', { timeout: 20000 });
    await expect(resumen).toContainText('2 actividades');
    await expect(resumen).toContainText('4 insumos');

    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // El historial muestra una versión activa con el label del fixture.
    const versiones = page.locator('[data-testid="pdc-import-versiones"]');
    await expect(versiones.locator('.ag-cell', { hasText: 'PI_TEST_1' }).first()).toBeVisible({ timeout: 15000 });
    await expect(versiones.locator('.ag-cell', { hasText: 'Activa' }).first()).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Correr el e2e**

```bash
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-import.spec.mjs --workers=1
```

Expected: `1 passed`. (Nota: importa el fixture al proyecto Da Porto de la BD local de desarrollo; cada corrida agrega una versión — el test es idempotente porque asserta la más reciente activa.)

- [ ] **Step 4: Commit en lps-aia** (usar `git add -f` para el spec y fixture si el allowlist de `.gitignore` lo exige — precedente del repo):

```bash
git add -f tests/support/generar_fixture_e2e_pdc.php tests/browser/fixtures/pdc/presupuesto-mini.xlsx tests/browser/pdc-v2-import.spec.mjs
git commit -m "test(pdc-v2): e2e del importador — preview, confirmación y versión activa"
```

- [ ] **Step 5: Actualizar CLAUDE.md de plan-de-compras y commitear**

En la sección "Estado actual", reemplazar el primer párrafo por:

```markdown
Rama en curso: **Fase A1 implementada** — importador de presupuesto (preview→confirmar, versionado con única activa, todo-o-nada) sobre 3 tablas `pdc_presupuesto_*` en lps-aia, con RBAC `lps.pdc.importar`, vista `#/ensamble/importar` y navegación de submódulos Ensamble | Seguimiento. Verificado con Vitest (16 tests), tests PHP autoejecutables (RBAC, parser, flujo BD) y e2e Playwright de import.
```

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md && git commit -m "docs(pdc): CLAUDE.md refleja la Fase A1 implementada"
```

---

## Verificación end-to-end (tras Task 10)

1. plan-de-compras: `npm run test` → 16 PASS; `npm run build` → OK.
2. lps-aia: `docker compose exec app php tests/test_pdc_v2_rbac_importar.php && docker compose exec app php tests/test_pdc_v2_import_parser.php && docker compose exec app php tests/test_pdc_v2_import_flujo.php && docker compose exec app php tests/test_pdc_v2_contexto.php` → todos exit 0.
3. Gates BD: `test_global_table_safety.php` + `test_global_table_reconciliation.php` → exit 0. PHPStan `src/Services/Pdc src/Controllers/Api` → sin errores.
4. e2e: `npx playwright test tests/browser/pdc-v2-import.spec.mjs tests/browser/pdc-v2-fundacion.spec.mjs --workers=1` → 2 passed.
5. Manual (navegador integrado): login → Da Porto → `/plan-compras` → subir el Excel real de DAPORTO → confirmar → historial con versión activa.
6. Al cerrar: whole-branch review final (ambos repos) + merge según finishing-a-development-branch.

## Riesgos anotados

- El parser se calibra contra el Excel real en Task 3 Step 6: si el archivo real revela reglas distintas (niveles, formatos numéricos), ajustar parser+fixtures ahí, no después.
- `is_uploaded_file` impide testear `preview()` del controller por CLI — la costura de test es el servicio (Task 4/5) y el wiring HTTP lo cubre el e2e (Task 10). No debilitar esa validación.
- Cada corrida del e2e agrega una versión al Da Porto local — aceptable en dev; staging/producción usan datos reales.
