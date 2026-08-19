---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-23
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-23-a25-importador-maestro-sinco.md
resumen: Sembrar generalmaestroinsumos con el maestro autoritativo de AIA exportado de SINCO (3.088 insumos, con código estable, Agrupación, Tipo de recurso y valor)…
---

# Fase A2.5: Importador del maestro SINCO — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sembrar `general_maestro_insumos` con el maestro autoritativo de AIA exportado de SINCO (3.088 insumos, con código estable, Agrupación, Tipo de recurso y valor), vía un importador preview→confirmar idempotente, dejando el catálogo listo para la Fase A3.

**Architecture:** Se clona el patrón del importador de presupuesto (A1): parser PhpSpreadsheet read-only + store temporal privado (reusado) + servicio preview/confirmar transaccional + controller con guards de upload + vista SPA con `apiUpload` y un reducer. La migración extiende `general_maestro_insumos` con columnas SINCO (aditivo). El upsert es explícito por `codigo_sinco` (no `ON DUPLICATE KEY` porque hay dos UNIQUEs), enriqueciendo filas huérfanas de A2 por `norma+unidad`. Spec: `docs/superpowers/specs/2026-07-23-a25-importador-maestro-sinco-design.md`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker lps-aia), PhpSpreadsheet 5.x, FastRoute, React+TS+Vite+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{"ok":true,"data":...}` | `{"ok":false,"error":{"code","message",...}}` con `JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE` (trait `PlanComprasJsonRespuestas`, ya existe — reusar).
- RBAC escritura = `lps.pdc.maestro` (ya en el catálogo, A y D); lectura del catálogo = `lps.pdc.ver`. CSRF form key `plan_compras_v2` (`$_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token']`).
- Upload: solo `.xlsx`, máx **10MB** (`10_485_760`). Códigos: `INVALID_FILE`, `VALIDATION_FAILED`, `FILE_TOO_LARGE`, `TOKEN_EXPIRED`, `FORBIDDEN`, `NO_PROJECT`, `CSRF_INVALID`.
- Normalización canónica: `\App\Services\Pdc\MaestroInsumosService::normalizar(string): string` (estática, ya existe) — NO duplicar.
- Migración aditiva (`ADD COLUMN` nullable); `project_id` NO aplica (catálogo global `general_*`); `utf8mb4_unicode_ci`. Aplicar con `docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < archivo.sql`.
- Solo insumos con `Estado = ACTIVO` se importan; los INACTIVO se cuentan como omitidos.
- Sin PHPUnit: tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) contra el MySQL real del Docker; el catálogo es global → cleanup por marca de test (`codigo_sinco` con prefijo `TEST-` y `creado_por='test-a25'`), como A2.
- SPA: TypeScript estricto, AG Grid Community (`ClientSideRowModelModule` + `ValidationModule` dev-only — patrón del repo), Vitest con exclude `.claude`; identificadores en inglés, comentarios/UI en español.
- Commits: `feat(pdc-v2): ...` en lps-aia, `feat(pdc): ...` en plan-de-compras. Rama `pdc-a25-maestro-sinco` en ambos repos.
- Los tests de lps-aia corren con `docker compose exec app php tests/...` (levantar `docker compose up -d app db`, sin rebuild — el override monta el repo en vivo).

---

## File Structure

**lps-aia (rama `pdc-a25-maestro-sinco`):**
```
database/migrations/20260723_pdc_v2_maestro_sinco_cols.sql   # T1: ALTER +5 columnas
tests/support/pdc_fixture_maestro_sinco.php                  # T2: generador de .xlsx SINCO de prueba
src/Services/Pdc/MaestroSincoParser.php                      # T2: parser de la hoja "Maestro Insumos"
tests/test_pdc_v2_maestro_sinco_parser.php                   # T2
src/Services/Pdc/MaestroSincoImportService.php               # T3: preview + confirmar (upsert)
tests/test_pdc_v2_maestro_sinco_import.php                   # T3
src/Controllers/Api/PlanComprasMaestroImportController.php   # T4: 2 endpoints
public/index.php                                             # T4: +2 rutas
tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx           # T6: fixture e2e (generado+commiteado)
tests/browser/pdc-v2-maestro-sinco.spec.mjs                  # T6
public/pdc-app/**                                            # T6: bundle regenerado
```

**plan-de-compras (rama `pdc-a25-maestro-sinco`):**
```
src/lib/maestroImportState.ts        # T5: reducer del import del maestro (clon de importState)
src/lib/maestroImportState.test.ts   # T5
src/lib/types.ts                     # T5: Modify (+tipos del import del maestro)
src/pages/MaestroInsumos.tsx         # T5: Modify (sección "Importar maestro SINCO")
src/styles.css                       # T5: Modify (estilos de la sección)
CLAUDE.md                            # T6: Modify (estado A2.5)
```

---

### Task 1: Migración — columnas SINCO en `general_maestro_insumos` (lps-aia)

**Files:**
- Create: `database/migrations/20260723_pdc_v2_maestro_sinco_cols.sql`

**Interfaces:**
- Produces: en `general_maestro_insumos`, columnas `codigo_sinco` (UNIQUE), `agrupacion` (indexada), `tipo_recurso`, `valor_unitario`, `iva`. T3 hace upsert usándolas.

- [ ] **Step 1: Crear branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git checkout main && git checkout -b pdc-a25-maestro-sinco
```

- [ ] **Step 2: Escribir la migración**

```sql
-- 20260723_pdc_v2_maestro_sinco_cols.sql
-- PDC v2 / Fase A2.5: extiende el maestro global con los datos autoritativos de SINCO.
-- Aditivo (columnas nullable). El maestro sigue casando presupuestos por (descripcion_norm, unidad);
-- codigo_sinco es la clave del upsert del import SINCO.

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `codigo_sinco` varchar(50) DEFAULT NULL AFTER `id`,
  ADD COLUMN `agrupacion` varchar(150) DEFAULT NULL AFTER `tipo_insumo`,
  ADD COLUMN `tipo_recurso` varchar(60) DEFAULT NULL AFTER `agrupacion`,
  ADD COLUMN `valor_unitario` decimal(18,4) DEFAULT NULL AFTER `tipo_recurso`,
  ADD COLUMN `iva` decimal(5,2) DEFAULT NULL AFTER `valor_unitario`,
  ADD UNIQUE KEY `uq_gmi_codigo_sinco` (`codigo_sinco`),
  ADD KEY `idx_gmi_agrupacion` (`agrupacion`);
```

- [ ] **Step 3: Aplicar en Docker y verificar**

```bash
docker compose up -d app db
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/20260723_pdc_v2_maestro_sinco_cols.sql
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SHOW CREATE TABLE general_maestro_insumos\G"' | grep -E "codigo_sinco|agrupacion|tipo_recurso|valor_unitario|iva|uq_gmi_codigo_sinco|idx_gmi_agrupacion"
```

Expected: las 5 columnas nuevas + la UNIQUE `uq_gmi_codigo_sinco` + la KEY `idx_gmi_agrupacion`. (El `UNIQUE(codigo_sinco)` permite múltiples NULL en MySQL — las filas de A2 sin código no colisionan.)

- [ ] **Step 4: Gates de arquitectura de datos**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Expected: ambos exit 0 (cambio aditivo sobre catálogo `general_*`).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260723_pdc_v2_maestro_sinco_cols.sql
git commit -m "feat(pdc-v2): columnas SINCO en general_maestro_insumos (codigo, agrupacion, tipo_recurso, valor, iva)"
```

---

### Task 2: Parser del maestro SINCO + fixture (lps-aia, TDD)

**Files:**
- Create: `tests/support/pdc_fixture_maestro_sinco.php`
- Create: `src/Services/Pdc/MaestroSincoParser.php`
- Test: `tests/test_pdc_v2_maestro_sinco_parser.php`

**Interfaces:**
- Consumes: PhpSpreadsheet; `\App\Services\Pdc\MaestroInsumosService::normalizar()`.
- Produces:
  - `pdcFixtureMaestroSinco(string $path): void` — genera un `.xlsx` con hoja `Maestro Insumos`, encabezados reales y ~6 filas (activas + una INACTIVO).
  - `App\Services\Pdc\MaestroSincoParser::parse(string $filePath): array` — retorna
    `['valido'=>bool, 'insumos'=>list<array{codigoSinco,descripcion,descripcionNorm,unidad,tipoInsumo,agrupacion,tipoRecurso,valorUnitario,iva}>, 'resumen'=>['total'=>int,'activos'=>int,'omitidos'=>int,'agrupaciones'=>int,'tiposRecurso'=>int], 'errores'=>list<array{fila:int,columna:string,motivo:string}>]`.
  - Reglas: hoja `Maestro Insumos` obligatoria (`RuntimeException`); encabezados por nombre normalizado; requeridos: `CODIGO INSUMO, INSUMO DESCRIPCION, UNIDAD, TIPO DESCRIPCION, AGRUPACION DESCRIPCION, ESTADO, VALOR UNITARIO`. Solo filas `ESTADO`≈`ACTIVO` van a `insumos`; INACTIVO cuentan en `omitidos`. `descripcionNorm = MaestroInsumosService::normalizar(descripcion)`. `tipoInsumo = agrupacion` (se alinea con lo que los presupuestos llaman "Tipo Insumo"; `tipoRecurso` queda aparte). Errores por fila activa: código vacío, descripción vacía, unidad vacía, valor no numérico. Tope 200 errores.

- [ ] **Step 1: Escribir el generador de fixtures**

```php
<?php
// tests/support/pdc_fixture_maestro_sinco.php
// Genera un .xlsx con la estructura de la hoja "Maestro Insumos" del export SINCO.

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const PDC_SINCO_HEADERS = [
    'Empresa', 'Codigo Insumo', 'Insumo Descripcion', 'Agrupacion', 'Agrupacion Descripcion',
    'Tipo', 'Tipo Descripcion', 'Unidad', 'Descripcion Unidad', 'Estado', 'Valor Unitario', 'Porcentaje IVA',
];

function pdcFixtureMaestroSincoEscribir(string $path, array $rows): void
{
    $book = new Spreadsheet();
    $book->getProperties()->setCreated(0)->setModified(0); // fixture determinista
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Maestro Insumos');
    $sheet->fromArray(PDC_SINCO_HEADERS, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();
}

/** 5 activos (2 comparten agrupacion) + 1 INACTIVO. Códigos con prefijo TEST- para el cleanup. */
function pdcFixtureMaestroSinco(string $path): void
{
    pdcFixtureMaestroSincoEscribir($path, [
        //Empresa, Codigo,      Insumo Descripcion,          Agrup, Agrup Desc,        Tipo, Tipo Desc,      Und, Desc Und, Estado,   VrUnit, IVA
        ['AIA', 'TEST-101', 'PISO CERAMICO 30X30',        '03', 'MAT-ACABADOS',       'M', 'MATERIAL',      'M2', 'METRO2', 'ACTIVO',  25000, 19],
        ['AIA', 'TEST-102', 'PISO PORCELANATO 60X60',     '03', 'MAT-ACABADOS',       'M', 'MATERIAL',      'M2', 'METRO2', 'ACTIVO',  48000, 19],
        ['AIA', 'TEST-103', 'ACERO DE REFUERZO 60000PSI', '05', 'MAT-ACEROS',         'M', 'MATERIAL',      'KG', 'KILO',   'ACTIVO',   4200, 19],
        ['AIA', 'TEST-104', 'AYUDANTE DE OBRA',           '10', 'SUBCONTRATACION',    'S', 'MANO DE OBRA',  'HC', 'HORA',   'ACTIVO',   9500,  0],
        ['AIA', 'TEST-105', 'ALQUILER ANDAMIO',           '21', 'ALQUILER MAQUINARIA','E', 'EQUIPO',        'DIA','DIA',    'ACTIVO', 130506, 19],
        ['AIA', 'TEST-106', 'INSUMO OBSOLETO',            '99', 'OTROS',              'M', 'MATERIAL',      'UN', 'UNIDAD', 'INACTIVO', 100,  0],
    ]);
}

/** Variante con 3 filas activas inválidas (código vacío, unidad vacía, valor no numérico). */
function pdcFixtureMaestroSincoInvalido(string $path): void
{
    pdcFixtureMaestroSincoEscribir($path, [
        ['AIA', '',         'SIN CODIGO',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', 'M2', 'METRO2', 'ACTIVO', 100, 19],
        ['AIA', 'TEST-201', 'SIN UNIDAD',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', '',   '',       'ACTIVO', 100, 19],
        ['AIA', 'TEST-202', 'VALOR ROTO',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', 'UN', 'UNIDAD', 'ACTIVO', 'abc', 19],
    ]);
}
```

- [ ] **Step 2: Escribir el test del parser (falla)**

```php
<?php
// tests/test_pdc_v2_maestro_sinco_parser.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/pdc_fixture_maestro_sinco.php';

use App\Services\Pdc\MaestroSincoParser;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

echo "=== PDC v2: parser maestro SINCO ===\n";
$tmp = sys_get_temp_dir();
$ok = $tmp . '/sinco_ok.xlsx';
$bad = $tmp . '/sinco_bad.xlsx';
pdcFixtureMaestroSinco($ok);
pdcFixtureMaestroSincoInvalido($bad);

$parser = new MaestroSincoParser();

// Archivo válido: 5 activos, 1 omitido, sin errores.
$r = $parser->parse($ok);
$assert($r['valido'] === true, 'Fixture válido parsea sin errores.');
$assert($r['resumen']['total'] === 6, 'total = 6 filas.');
$assert($r['resumen']['activos'] === 5 && $r['resumen']['omitidos'] === 1, '5 activos, 1 INACTIVO omitido.');
$assert(count($r['insumos']) === 5, 'insumos solo activos.');
$assert($r['resumen']['agrupaciones'] === 4, '4 agrupaciones distintas entre activos.');
$assert($r['resumen']['tiposRecurso'] === 3, '3 tipos de recurso (MATERIAL/MANO DE OBRA/EQUIPO).');
$piso = array_values(array_filter($r['insumos'], fn ($i) => $i['codigoSinco'] === 'TEST-101'))[0];
$assert($piso['descripcionNorm'] === 'PISO CERAMICO 30X30', 'descripcionNorm normalizada.');
$assert($piso['agrupacion'] === 'MAT-ACABADOS' && $piso['tipoInsumo'] === 'MAT-ACABADOS', 'tipoInsumo = agrupacion.');
$assert($piso['tipoRecurso'] === 'MATERIAL', 'tipoRecurso viene de Tipo Descripcion.');
$assert(abs($piso['valorUnitario'] - 25000) < 0.001 && abs($piso['iva'] - 19) < 0.001, 'valor e IVA numéricos.');

// Archivo inválido: 3 errores, valido=false.
$b = $parser->parse($bad);
$assert($b['valido'] === false, 'Fixture inválido reporta valido=false.');
$assert(count($b['errores']) >= 3, 'Reporta ≥3 errores (código, unidad, valor).');
$assert($b['errores'][0]['fila'] === 2, 'El primer error apunta a la fila 2 del Excel.');

// Hoja faltante → RuntimeException.
$sinHoja = $tmp . '/sinco_sinhoja.xlsx';
$book = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$book->getActiveSheet()->setTitle('Otra');
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($sinHoja);
try {
    $parser->parse($sinHoja);
    $assert(false, 'Hoja faltante lanza RuntimeException.');
} catch (\RuntimeException $e) {
    $assert(str_contains($e->getMessage(), 'Maestro Insumos'), 'Mensaje claro de hoja faltante.');
}

foreach ([$ok, $bad, $sinHoja] as $f) { @unlink($f); }
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 3: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_maestro_sinco_parser.php
```

Expected: FAIL — `Class "App\Services\Pdc\MaestroSincoParser" not found`.

- [ ] **Step 4: Implementar el parser**

```php
<?php

namespace App\Services\Pdc;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parser de la hoja "Maestro Insumos" del export SINCO (PDC v2 / Fase A2.5).
 * Encabezados por nombre; solo insumos ACTIVO; validación todo-o-nada con
 * reporte por fila (tope 200). La normalización es la canónica del maestro.
 */
final class MaestroSincoParser
{
    public const SHEET = 'Maestro Insumos';
    public const MAX_ERRORES = 200;

    private const REQUERIDAS = ['CODIGO INSUMO', 'INSUMO DESCRIPCION', 'UNIDAD', 'TIPO DESCRIPCION', 'AGRUPACION DESCRIPCION', 'ESTADO', 'VALOR UNITARIO'];

    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
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

        $insumos = [];
        $errores = [];
        $omitidos = 0;
        $activos = 0;
        $total = 0;
        $agrup = [];
        $tipos = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }
            if ($this->filaVacia($row)) {
                continue;
            }
            $total++;
            $fila = $i + 1;
            $cel = function (string $k) use ($row, $mapa): string {
                $idx = $mapa[$k] ?? null;
                return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
            };

            $estado = mb_strtoupper($cel('ESTADO'));
            if ($estado !== 'ACTIVO') {
                $omitidos++;
                continue;
            }
            $activos++;

            $codigo = $cel('CODIGO INSUMO');
            $descripcion = $cel('INSUMO DESCRIPCION');
            $unidad = $cel('UNIDAD');
            $valor = $this->numero($cel('VALOR UNITARIO'));
            $err = function (string $col, string $motivo) use (&$errores, $fila): bool {
                if (count($errores) >= self::MAX_ERRORES) {
                    return false;
                }
                $errores[] = ['fila' => $fila, 'columna' => $col, 'motivo' => $motivo];
                return true;
            };

            $filaValida = true;
            if ($codigo === '') { $filaValida = false; $err('Codigo Insumo', 'Insumo activo sin código SINCO.'); }
            if ($descripcion === '') { $filaValida = false; $err('Insumo Descripcion', 'Insumo activo sin descripción.'); }
            if ($unidad === '') { $filaValida = false; $err('Unidad', 'Insumo activo sin unidad.'); }
            if ($valor === null) { $filaValida = false; $err('Valor Unitario', 'Valor unitario no numérico.'); }
            if (!$filaValida) {
                continue;
            }

            $agrupacion = mb_substr($cel('AGRUPACION DESCRIPCION'), 0, 150);
            $tipoRecurso = mb_substr($cel('TIPO DESCRIPCION'), 0, 60);
            if ($agrupacion !== '') { $agrup[$agrupacion] = true; }
            if ($tipoRecurso !== '') { $tipos[$tipoRecurso] = true; }

            $insumos[] = [
                'codigoSinco' => mb_substr($codigo, 0, 50),
                'descripcion' => mb_substr($descripcion, 0, 500),
                'descripcionNorm' => mb_substr(MaestroInsumosService::normalizar($descripcion), 0, 500),
                'unidad' => mb_substr($unidad, 0, 20),
                // El maestro de A2 usa `tipo_insumo`; para insumos SINCO lo alineamos a la
                // Agrupación (lo que los presupuestos llaman "Tipo Insumo"). `tipoRecurso` va aparte.
                'tipoInsumo' => $agrupacion,
                'agrupacion' => $agrupacion,
                'tipoRecurso' => $tipoRecurso,
                'valorUnitario' => $valor,
                'iva' => $this->numero($cel('PORCENTAJE IVA')),
            ];
        }

        if (count($errores) >= self::MAX_ERRORES) {
            $errores[] = ['fila' => 0, 'columna' => '', 'motivo' => 'Reporte truncado en ' . self::MAX_ERRORES . ' errores.'];
        }

        return [
            'valido' => $errores === [],
            'insumos' => $insumos,
            'resumen' => [
                'total' => $total,
                'activos' => $activos,
                'omitidos' => $omitidos,
                'agrupaciones' => count($agrup),
                'tiposRecurso' => count($tipos),
            ],
            'errores' => $errores,
        ];
    }

    private function mapearEncabezados(array $headerRow): array
    {
        $mapa = [];
        foreach ($headerRow as $idx => $titulo) {
            $clave = MaestroInsumosService::normalizar((string) $titulo);
            if ($clave !== '' && !isset($mapa[$clave])) {
                $mapa[$clave] = $idx;
            }
        }
        return $mapa;
    }

    private function numero(string $v): ?float
    {
        if ($v === '') {
            return null;
        }
        $v = str_replace([' ', '$'], '', $v);
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

- [ ] **Step 5: Correr y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_maestro_sinco_parser.php
```

Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 6: Calibración con el archivo real (verificación manual)**

```bash
docker compose cp "/Volumes/Crucial X6/Developer/plan-de-compras/docs/Maestro_Insumos_SINCO.xlsx" app:/tmp/sinco.xlsx
docker compose exec app php -r "require 'vendor/autoload.php'; \$r=(new App\Services\Pdc\MaestroSincoParser())->parse('/tmp/sinco.xlsx'); echo json_encode(['valido'=>\$r['valido'],'resumen'=>\$r['resumen'],'errores'=>array_slice(\$r['errores'],0,5)], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), PHP_EOL;"
docker compose exec app rm /tmp/sinco.xlsx
```

Expected: `valido: true`, `activos ≈ 3084`, `omitidos` pequeño, `agrupaciones ≈ 73`, `tiposRecurso ≈ 10`. Si aparecen errores masivos, ajustar parser/fixtures y re-correr el test; anotar el resumen real en el reporte.

- [ ] **Step 7: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/MaestroSincoParser.php --memory-limit=1G
git add tests/support/pdc_fixture_maestro_sinco.php src/Services/Pdc/MaestroSincoParser.php tests/test_pdc_v2_maestro_sinco_parser.php
git commit -m "feat(pdc-v2): parser del maestro SINCO con validación todo-o-nada y fixtures"
```

---

### Task 3: Servicio de import del maestro — preview + confirmar (lps-aia, TDD)

**Files:**
- Create: `src/Services/Pdc/MaestroSincoImportService.php`
- Test: `tests/test_pdc_v2_maestro_sinco_import.php`

**Interfaces:**
- Consumes: `MaestroSincoParser` (T2), `PresupuestoImportStore` (existe), `\Database`.
- Produces:
  - `__construct(\Database $db, PresupuestoImportStore $store, MaestroSincoParser $parser)`.
  - `preview(string $rutaArchivo, string $nombre, string $usuario): array` — parsea; si inválido `{ok:false, errores}`; si válido guarda temporal y retorna `{ok:true, importToken, resumen}`.
  - `confirmar(string $token): array` — re-parsea el temporal y **en una transacción** hace upsert por `codigo_sinco`: si existe ese código → UPDATE; si no, busca por `(descripcion_norm, unidad)` una fila sin `codigo_sinco` → la enriquece (setea código + datos); si esa fila ya tiene otro código → conflicto (no pisa); si nada → INSERT. Retorna `{ok:true, creados, actualizados, enriquecidos, conflictos}`. Token de un solo uso (se elimina tras commit). Token inválido → `{ok:false, code:'TOKEN_EXPIRED'}`.

- [ ] **Step 1: Escribir el test (falla)**

```php
<?php
// tests/test_pdc_v2_maestro_sinco_import.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_maestro_sinco.php';

use App\Services\Pdc\MaestroSincoImportService;
use App\Services\Pdc\MaestroSincoParser;
use App\Services\Pdc\PresupuestoImportStore;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
// Cleanup por marca de test (catálogo global): códigos TEST-% y las filas huérfanas de prueba.
$limpiar = static function () use ($db): void {
    $db->query("DELETE FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%' OR creado_por = 'test-a25'");
};
$limpiar();

echo "=== PDC v2: import maestro SINCO ===\n";
$storeDir = sys_get_temp_dir() . '/pdc-sinco-store-' . getmypid();
$store = new PresupuestoImportStore($storeDir);
$svc = new MaestroSincoImportService($db, $store, new MaestroSincoParser());

$tmp = sys_get_temp_dir() . '/sinco_import.xlsx';
pdcFixtureMaestroSinco($tmp);

// Preview válido: token + resumen, nada en BD.
$p = $svc->preview($tmp, 'maestro.xlsx', 'test-a25');
$assert($p['ok'] === true && preg_match('/^[a-f0-9]{32}$/', $p['importToken']) === 1, 'Preview ok con token.');
$antes = (int) $db->query("SELECT COUNT(*) FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%'")->fetchColumn();
$assert($antes === 0, 'Preview NO escribe en BD.');

// Confirmar: 5 insumos creados.
$c = $svc->confirmar($p['importToken']);
$assert($c['ok'] === true && $c['creados'] === 5, 'Confirmar crea 5 insumos.');
$fila = $db->query("SELECT descripcion, agrupacion, tipo_recurso, valor_unitario FROM general_maestro_insumos WHERE codigo_sinco = 'TEST-101'")->fetch(PDO::FETCH_ASSOC);
$assert($fila['agrupacion'] === 'MAT-ACABADOS' && $fila['tipo_recurso'] === 'MATERIAL', 'Columnas SINCO persistidas.');
$assert(abs((float) $fila['valor_unitario'] - 25000) < 0.001, 'valor_unitario persistido.');

// Token de un solo uso.
$c2 = $svc->confirmar($p['importToken']);
$assert($c2['ok'] === false && $c2['code'] === 'TOKEN_EXPIRED', 'Token no reutilizable.');

// Re-import idempotente: 0 creados, 5 actualizados.
$tmp2 = sys_get_temp_dir() . '/sinco_import2.xlsx';
pdcFixtureMaestroSinco($tmp2);
$p2 = $svc->preview($tmp2, 'maestro.xlsx', 'test-a25');
$c3 = $svc->confirmar($p2['importToken']);
$assert($c3['ok'] === true && $c3['creados'] === 0 && $c3['actualizados'] === 5, 'Re-import no duplica (5 actualizados).');
$totalTest = (int) $db->query("SELECT COUNT(*) FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%'")->fetchColumn();
$assert($totalTest === 5, 'Sigue habiendo 5 filas de prueba.');

// Enriquecimiento: una fila huérfana de A2 (sin codigo_sinco) con misma norma+unidad se completa.
// Borramos la TEST-101 PRIMERO (comparte norma+unidad con la huérfana → si no, choca con uq_gmi_norm_unidad).
$db->query("DELETE FROM general_maestro_insumos WHERE codigo_sinco = 'TEST-101'");
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
     VALUES ('Piso ceramico 30x30', 'PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 1, 'test-a25', NOW())",
);
$tmp3 = sys_get_temp_dir() . '/sinco_import3.xlsx';
pdcFixtureMaestroSinco($tmp3);
$p3 = $svc->preview($tmp3, 'maestro.xlsx', 'test-a25');
$c4 = $svc->confirmar($p3['importToken']);
$assert(($c4['enriquecidos'] ?? 0) >= 1, 'Fila huérfana por norma+unidad se enriquece con el código SINCO.');
$enr = $db->query("SELECT codigo_sinco FROM general_maestro_insumos WHERE descripcion_norm = 'PISO CERAMICO 30X30' AND unidad = 'M2'")->fetchColumn();
$assert($enr === 'TEST-101', 'La fila huérfana quedó con codigo_sinco = TEST-101.');

foreach ([$tmp, $tmp2, $tmp3] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_maestro_sinco_import.php
```

Expected: FAIL — `Class "App\Services\Pdc\MaestroSincoImportService" not found`.

- [ ] **Step 3: Implementar el servicio**

```php
<?php

namespace App\Services\Pdc;

/**
 * Import del maestro SINCO: preview (parsear + guardar temporal) y confirmar
 * (upsert transaccional por codigo_sinco, con enriquecimiento de filas de A2).
 */
final class MaestroSincoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly MaestroSincoParser $parser,
    ) {
    }

    public function preview(string $rutaArchivo, string $nombre, string $usuario): array
    {
        $r = $this->parser->parse($rutaArchivo);
        if (!$r['valido']) {
            return ['ok' => false, 'errores' => $r['errores']];
        }
        $token = $this->store->guardar($rutaArchivo, ['nombre' => $nombre, 'usuario' => $usuario]);
        return ['ok' => true, 'importToken' => $token, 'resumen' => $r['resumen']];
    }

    public function confirmar(string $token): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }
        try {
            $r = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$r['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        $usuario = (string) ($meta['usuario'] ?? '');

        $creados = 0; $actualizados = 0; $enriquecidos = 0; $conflictos = [];

        $this->db->beginTransaction();
        try {
            foreach ($r['insumos'] as $ins) {
                $porCodigo = $this->db->query(
                    'SELECT id FROM general_maestro_insumos WHERE codigo_sinco = ?',
                    [$ins['codigoSinco']],
                )->fetchColumn();

                if ($porCodigo !== false) {
                    $this->db->query(
                        'UPDATE general_maestro_insumos
                         SET descripcion = ?, descripcion_norm = ?, unidad = ?, tipo_insumo = ?, agrupacion = ?,
                             tipo_recurso = ?, valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                         WHERE id = ?',
                        [$ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $ins['tipoInsumo'], $ins['agrupacion'],
                         $ins['tipoRecurso'], $ins['valorUnitario'], $ins['iva'], $usuario, (int) $porCodigo],
                    );
                    $actualizados++;
                    continue;
                }

                // Sin match por código: ¿hay una fila con la misma norma+unidad?
                $huerfana = $this->db->query(
                    'SELECT id, codigo_sinco FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
                    [$ins['descripcionNorm'], $ins['unidad']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($huerfana !== false) {
                    if ($huerfana['codigo_sinco'] === null || $huerfana['codigo_sinco'] === '') {
                        $this->db->query(
                            'UPDATE general_maestro_insumos
                             SET codigo_sinco = ?, descripcion = ?, tipo_insumo = ?, agrupacion = ?, tipo_recurso = ?,
                                 valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                             WHERE id = ?',
                            [$ins['codigoSinco'], $ins['descripcion'], $ins['tipoInsumo'], $ins['agrupacion'], $ins['tipoRecurso'],
                             $ins['valorUnitario'], $ins['iva'], $usuario, (int) $huerfana['id']],
                        );
                        $enriquecidos++;
                    } else {
                        // Otro insumo SINCO ya ocupa esa norma+unidad: no pisar; reportar.
                        $conflictos[] = ['codigoSinco' => $ins['codigoSinco'], 'descripcion' => $ins['descripcion'], 'chocaCon' => $huerfana['codigo_sinco']];
                    }
                    continue;
                }

                $this->db->query(
                    'INSERT INTO general_maestro_insumos
                        (codigo_sinco, descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, valor_unitario, iva, activo, creado_por, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                    [$ins['codigoSinco'], $ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $ins['tipoInsumo'],
                     $ins['agrupacion'], $ins['tipoRecurso'], $ins['valorUnitario'], $ins['iva'], $usuario],
                );
                $creados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token);
        return ['ok' => true, 'creados' => $creados, 'actualizados' => $actualizados, 'enriquecidos' => $enriquecidos, 'conflictos' => $conflictos];
    }
}
```

- [ ] **Step 4: Correr y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_maestro_sinco_import.php
```

Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 5: Gates + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/MaestroSincoImportService.php --memory-limit=1G
docker compose exec app php tests/test_global_table_safety.php
git add src/Services/Pdc/MaestroSincoImportService.php tests/test_pdc_v2_maestro_sinco_import.php
git commit -m "feat(pdc-v2): import transaccional del maestro SINCO (upsert por codigo, enriquece filas de A2)"
```

---

### Task 4: Controller + rutas del import del maestro (lps-aia)

**Files:**
- Create: `src/Controllers/Api/PlanComprasMaestroImportController.php`
- Modify: `public/index.php` (2 rutas tras el bloque maestro A2)

**Interfaces:**
- Consumes: `MaestroSincoImportService`, `MaestroSincoParser`, `PresupuestoImportStore`, `RbacService`, `CsrfTokenManager`, trait.
- Produces (contrato HTTP para la SPA T5):
  - `POST /plan-compras/api/maestro/importar/preview` (multipart `archivo`) → `{ok:true,data:{importToken,resumen}}` | `VALIDATION_FAILED` 422 con `errores`, `INVALID_FILE` 422, `FILE_TOO_LARGE` 413, `CSRF_INVALID`/`FORBIDDEN` 403, `NO_PROJECT` 409.
  - `POST /plan-compras/api/maestro/importar/confirmar` (JSON `{importToken}`) → `{ok:true,data:{creados,actualizados,enriquecidos,conflictos}}` | `TOKEN_EXPIRED` 410 | `INVALID_FILE` 422.

- [ ] **Step 1: Rutas en `public/index.php`** (tras la ruta `POST /plan-compras/api/maestro/reactivar`, antes del comentario `// Api/PDC Plantillas`):

```php
$router->post('/plan-compras/api/maestro/importar/preview', [\App\Controllers\Api\PlanComprasMaestroImportController::class, 'preview']);
$router->post('/plan-compras/api/maestro/importar/confirmar', [\App\Controllers\Api\PlanComprasMaestroImportController::class, 'confirmar']);
```

- [ ] **Step 2: Implementar el controller**

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\MaestroSincoImportService;
use App\Services\Pdc\MaestroSincoParser;
use App\Services\Pdc\PresupuestoImportStore;

/**
 * Import del maestro SINCO (PDC v2 / Fase A2.5). Escritura del catálogo global
 * de insumos: RBAC lps.pdc.maestro + CSRF. Sesión garantizada por SessionMiddleware.
 */
class PlanComprasMaestroImportController
{
    use PlanComprasJsonRespuestas;

    public const MAX_BYTES = 10_485_760; // 10MB

    private \Database $db;
    private MaestroSincoImportService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new MaestroSincoImportService($this->db, new PresupuestoImportStore(), new MaestroSincoParser());
    }

    /** POST /plan-compras/api/maestro/importar/preview */
    public function preview(): void
    {
        if (!$this->guardEscritura()) {
            return;
        }
        $archivo = $_FILES['archivo'] ?? null;
        $errorSubida = is_array($archivo) ? (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if (in_array($errorSubida, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $this->fail('FILE_TOO_LARGE', 'El archivo supera el límite de 10MB.', 413);
            return;
        }
        if (!is_array($archivo) || $errorSubida !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            $this->fail('INVALID_FILE', 'No llegó ningún archivo válido.', 422);
            return;
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            $this->fail('FILE_TOO_LARGE', 'El archivo supera el límite de 10MB.', 413);
            return;
        }
        $nombre = mb_substr((string) ($archivo['name'] ?? 'maestro.xlsx'), 0, 255);
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
            $r = $this->service->preview($archivo['tmp_name'], $nombre, (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')));
        } catch (\PhpOffice\PhpSpreadsheet\Exception) {
            $this->fail('INVALID_FILE', 'El archivo no es un Excel .xlsx válido.', 422);
            return;
        } catch (\RuntimeException $e) {
            $this->fail('INVALID_FILE', $e->getMessage(), 422);
            return;
        }

        if (!$r['ok']) {
            $this->fail('VALIDATION_FAILED', 'El archivo tiene errores; no se importó nada.', 422, ['errores' => $r['errores']]);
            return;
        }
        $this->ok(['importToken' => $r['importToken'], 'resumen' => $r['resumen']]);
    }

    /** POST /plan-compras/api/maestro/importar/confirmar */
    public function confirmar(): void
    {
        if (!$this->guardEscritura()) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $token = (string) ($body['importToken'] ?? '');
        $r = $this->service->confirmar($token);
        if (!$r['ok']) {
            if ($r['code'] === 'TOKEN_EXPIRED') {
                $this->fail('TOKEN_EXPIRED', 'La previsualización expiró o ya fue usada. Sube el archivo de nuevo.', 410);
            } else {
                $this->fail('INVALID_FILE', 'El archivo temporal ya no es válido. Sube el archivo de nuevo.', 422);
            }
            return;
        }
        $this->ok(['creados' => $r['creados'], 'actualizados' => $r['actualizados'], 'enriquecidos' => $r['enriquecidos'], 'conflictos' => $r['conflictos']]);
    }

    /** RBAC maestro + proyecto activo + CSRF. true si pasa; false y ya respondió si no. */
    private function guardEscritura(): bool
    {
        if (!(new RbacService($this->db))->can('lps.pdc.maestro')) {
            $this->fail('FORBIDDEN', 'No autorizado para administrar el maestro de insumos.', 403);
            return false;
        }
        if ((int) ($_SESSION['project_id'] ?? 0) <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return false;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return false;
        }
        return true;
    }
}
```

- [ ] **Step 3: Verificación estática + regresión**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasMaestroImportController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasMaestroImportController.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_maestro.php
docker compose exec app php tests/test_pdc_v2_contexto.php
```

Expected: sin errores; suites de A2 y contexto siguen verdes (no tocamos su código).

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/Api/PlanComprasMaestroImportController.php public/index.php
git commit -m "feat(pdc-v2): endpoints preview/confirmar del import del maestro SINCO (RBAC maestro + CSRF)"
```

---

### Task 5: SPA — reducer + sección "Importar maestro SINCO" (plan-de-compras, TDD)

**Files:**
- Create: `src/lib/maestroImportState.ts`, `src/lib/maestroImportState.test.ts`
- Modify: `src/lib/types.ts`, `src/pages/MaestroInsumos.tsx`, `src/styles.css`

**Interfaces:**
- Consumes: `apiUpload`/`apiPost`/`PdcApiError` (existen), tipos.
- Produces:
  - `type MaestroImportResumen = { total: number; activos: number; omitidos: number; agrupaciones: number; tiposRecurso: number }`
  - `type MaestroImportResultado = { creados: number; actualizados: number; enriquecidos: number; conflictos: unknown[] }`
  - `MaestroImportState`/`maestroImportReducer`/`estadoInicialMaestroImport` — máquina idle→subiendo→previewOk→previewErrores→confirmando→confirmado (clon de `importState`, con `resumen: MaestroImportResumen | null` y `resultado: MaestroImportResultado | null`).
  - En la vista Maestro: sección con `input[data-testid="pdc-maestro-import-file"]`, resumen `[data-testid="pdc-maestro-import-resumen"]`, botón `[data-testid="pdc-maestro-import-confirmar"]`, y al confirmar recarga el catálogo existente.

- [ ] **Step 1: Branch en plan-de-compras**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git checkout main && git checkout -b pdc-a25-maestro-sinco
```

- [ ] **Step 2: Tipos en `src/lib/types.ts`** (añadir al final):

```ts
export type MaestroImportResumen = {
  total: number
  activos: number
  omitidos: number
  agrupaciones: number
  tiposRecurso: number
}

export type MaestroImportPreview = { importToken: string; resumen: MaestroImportResumen }

export type MaestroImportResultado = {
  creados: number
  actualizados: number
  enriquecidos: number
  conflictos: unknown[]
}

export type MaestroImportErrorFila = { fila: number; columna: string; motivo: string }
```

- [ ] **Step 3: Test del reducer (falla) — `src/lib/maestroImportState.test.ts`**

```ts
import { describe, expect, it } from 'vitest'
import { estadoInicialMaestroImport, maestroImportReducer } from './maestroImportState'
import type { MaestroImportPreview } from './types'

const preview: MaestroImportPreview = {
  importToken: 'a'.repeat(32),
  resumen: { total: 6, activos: 5, omitidos: 1, agrupaciones: 4, tiposRecurso: 3 },
}

describe('maestroImportReducer', () => {
  it('flujo feliz: idle → subiendo → previewOk → confirmando → confirmado', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    expect(s.fase).toBe('subiendo')
    s = maestroImportReducer(s, { type: 'PREVIEW_OK', preview })
    expect(s.fase).toBe('previewOk')
    expect(s.preview?.resumen.activos).toBe(5)
    s = maestroImportReducer(s, { type: 'CONFIRMAR' })
    expect(s.fase).toBe('confirmando')
    s = maestroImportReducer(s, { type: 'CONFIRMADO', resultado: { creados: 5, actualizados: 0, enriquecidos: 0, conflictos: [] } })
    expect(s.fase).toBe('confirmado')
    expect(s.resultado?.creados).toBe(5)
  })

  it('errores de validación llevan a previewErrores y limpian preview', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    s = maestroImportReducer(s, { type: 'PREVIEW_ERRORES', errores: [{ fila: 2, columna: 'Codigo Insumo', motivo: 'vacío' }] })
    expect(s.fase).toBe('previewErrores')
    expect(s.errores).toHaveLength(1)
    expect(s.preview).toBeNull()
  })

  it('FALLO vuelve a idle con mensaje; REINICIAR resetea', () => {
    let s = maestroImportReducer(estadoInicialMaestroImport, { type: 'SUBIR' })
    s = maestroImportReducer(s, { type: 'FALLO', mensaje: 'Sesión expirada' })
    expect(s.fase).toBe('idle')
    expect(s.mensajeError).toBe('Sesión expirada')
    expect(maestroImportReducer(s, { type: 'REINICIAR' })).toEqual(estadoInicialMaestroImport)
  })
})
```

- [ ] **Step 4: Correr y ver que falla**

```bash
npx vitest run src/lib/maestroImportState.test.ts
```

Expected: FAIL — `Cannot find module './maestroImportState'`.

- [ ] **Step 5: Implementar `src/lib/maestroImportState.ts`**

```ts
import type { MaestroImportErrorFila, MaestroImportPreview, MaestroImportResultado } from './types'

export type MaestroImportState = {
  fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'
  preview: MaestroImportPreview | null
  resultado: MaestroImportResultado | null
  errores: MaestroImportErrorFila[]
  mensajeError: string | null
}

export type MaestroImportAction =
  | { type: 'SUBIR' }
  | { type: 'PREVIEW_OK'; preview: MaestroImportPreview }
  | { type: 'PREVIEW_ERRORES'; errores: MaestroImportErrorFila[] }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'CONFIRMAR' }
  | { type: 'CONFIRMADO'; resultado: MaestroImportResultado }
  | { type: 'REINICIAR' }

export const estadoInicialMaestroImport: MaestroImportState = {
  fase: 'idle', preview: null, resultado: null, errores: [], mensajeError: null,
}

export function maestroImportReducer(state: MaestroImportState, action: MaestroImportAction): MaestroImportState {
  switch (action.type) {
    case 'SUBIR':
      return { ...estadoInicialMaestroImport, fase: 'subiendo' }
    case 'PREVIEW_OK':
      return { fase: 'previewOk', preview: action.preview, resultado: null, errores: [], mensajeError: null }
    case 'PREVIEW_ERRORES':
      return { fase: 'previewErrores', preview: null, resultado: null, errores: action.errores, mensajeError: null }
    case 'FALLO':
      return { ...state, fase: 'idle', mensajeError: action.mensaje }
    case 'CONFIRMAR':
      return { ...state, fase: 'confirmando', mensajeError: null }
    case 'CONFIRMADO':
      return { ...state, fase: 'confirmado', resultado: action.resultado, mensajeError: null }
    case 'REINICIAR':
      return estadoInicialMaestroImport
  }
}
```

- [ ] **Step 6: Sección en `src/pages/MaestroInsumos.tsx`**

Leer el archivo actual. Añadir los imports que falten al inicio:

```tsx
import { useReducer, useRef } from 'react'
import { apiUpload, PdcApiError } from '../lib/api'
import { estadoInicialMaestroImport, maestroImportReducer } from '../lib/maestroImportState'
import type { MaestroImportErrorFila, MaestroImportPreview, MaestroImportResultado } from '../lib/types'
```

Dentro del componente `MaestroInsumos`, añadir el estado del import y los handlers (reusar la función de recarga del catálogo ya existente — en el código actual se llama `cargarCatalogo`; si el nombre difiere, usar el que exista):

```tsx
  const [imp, dispatchImp] = useReducer(maestroImportReducer, estadoInicialMaestroImport)
  const impFileRef = useRef<HTMLInputElement>(null)

  const onArchivoMaestro = async (file: File | undefined) => {
    if (!file) return
    dispatchImp({ type: 'SUBIR' })
    try {
      const preview = await apiUpload<MaestroImportPreview>('/plan-compras/api/maestro/importar/preview', file)
      dispatchImp({ type: 'PREVIEW_OK', preview })
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'VALIDATION_FAILED') {
        const d = e.details as { errores?: MaestroImportErrorFila[] } | undefined
        dispatchImp({ type: 'PREVIEW_ERRORES', errores: d?.errores ?? [] })
      } else {
        dispatchImp({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
      }
    } finally {
      if (impFileRef.current) impFileRef.current.value = ''
    }
  }

  const onConfirmarMaestro = async () => {
    if (!imp.preview) return
    dispatchImp({ type: 'CONFIRMAR' })
    try {
      const resultado = await apiPost<MaestroImportResultado>('/plan-compras/api/maestro/importar/confirmar', { importToken: imp.preview.importToken })
      dispatchImp({ type: 'CONFIRMADO', resultado })
      cargarCatalogo('') // recarga el catálogo (función existente de la vista)
    } catch (e) {
      dispatchImp({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }
```

Y en el JSX, arriba de la cola de pendientes, la sección (usar `apiPost` ya importado en la vista):

```tsx
      <section className="pdc-bloque pdc-maestro-import">
        <h2>Importar maestro (SINCO)</h2>
        <p>Sube el Excel del maestro de insumos exportado de SINCO (hoja «Maestro Insumos», máx. 10MB).</p>
        <input
          ref={impFileRef}
          data-testid="pdc-maestro-import-file"
          type="file"
          accept=".xlsx"
          disabled={imp.fase === 'subiendo' || imp.fase === 'confirmando'}
          onChange={(e) => onArchivoMaestro(e.target.files?.[0])}
        />
        {imp.fase === 'subiendo' && <p>Analizando el archivo…</p>}
        {imp.mensajeError && <div className="pdc-error" role="alert">{imp.mensajeError}</div>}
        {imp.fase === 'previewErrores' && (
          <div className="pdc-error" role="alert">El archivo tiene {imp.errores.length} error(es); no se importó nada.</div>
        )}
        {(imp.fase === 'previewOk' || imp.fase === 'confirmando') && imp.preview && (
          <div data-testid="pdc-maestro-import-resumen">
            <p>{imp.preview.resumen.activos} insumos activos · {imp.preview.resumen.omitidos} omitidos · {imp.preview.resumen.agrupaciones} agrupaciones · {imp.preview.resumen.tiposRecurso} tipos</p>
            <button type="button" data-testid="pdc-maestro-import-confirmar" disabled={imp.fase === 'confirmando'} onClick={onConfirmarMaestro}>
              {imp.fase === 'confirmando' ? 'Importando…' : 'Confirmar e importar'}
            </button>
          </div>
        )}
        {imp.fase === 'confirmado' && imp.resultado && (
          <div className="pdc-exito" role="status">
            Maestro importado: {imp.resultado.creados} creados, {imp.resultado.actualizados} actualizados, {imp.resultado.enriquecidos} enriquecidos.
          </div>
        )}
      </section>
```

Estilos en `src/styles.css` (añadir):

```css
.pdc-maestro-import { border-bottom: 1px solid #2c2c2e; padding-bottom: 16px; }
```

- [ ] **Step 7: Verificar y commit**

```bash
npm run test && npm run build
git add src/lib/maestroImportState.ts src/lib/maestroImportState.test.ts src/lib/types.ts src/pages/MaestroInsumos.tsx src/styles.css
git commit -m "feat(pdc): sección de import del maestro SINCO en la vista Maestro (reducer + apiUpload)"
```

Expected: suite verde (18 base + los que sumó A2 + 3 del reducer nuevo); build OK.

---

### Task 6: Bundle + e2e + CLAUDE.md (lps-aia + plan-de-compras)

**Files:**
- Create (lps-aia): `tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx` (generado), `tests/browser/pdc-v2-maestro-sinco.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md`
- Generated (lps-aia): `public/pdc-app/**`

**Interfaces:**
- Consumes: `npm run sync`; helpers `loginAndSelectProject`/`logout`; fixture Da Porto (projectId 73); selectores del Task 5.

- [ ] **Step 1: Generar el fixture e2e y sincronizar el bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
npm run sync   # build + copia dist/ a ../lps-aia/public/pdc-app/
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose exec app php -r "require 'vendor/autoload.php'; require 'tests/support/pdc_fixture_maestro_sinco.php'; @mkdir('tests/browser/fixtures/pdc',0775,true); pdcFixtureMaestroSinco('tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx'); echo \"OK\n\";"
```

- [ ] **Step 2: Escribir el spec e2e** — `tests/browser/pdc-v2-maestro-sinco.spec.mjs`

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx';

test('importar maestro SINCO: preview, confirmación y catálogo poblado', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/maestro', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });

    await page.locator('[data-testid="pdc-maestro-import-file"]').setInputFiles(FIXTURE);
    const resumen = page.locator('[data-testid="pdc-maestro-import-resumen"]');
    await expect(resumen).toContainText('5 insumos activos', { timeout: 20000 });

    await page.locator('[data-testid="pdc-maestro-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // El catálogo global muestra un insumo del fixture (idempotente ante re-corridas).
    const catalogo = page.locator('[data-testid="pdc-maestro-catalogo"]');
    await expect(catalogo.locator('.ag-cell', { hasText: 'PISO CERAMICO 30X30' }).first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Correr el e2e (dos veces para idempotencia)**

```bash
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-maestro-sinco.spec.mjs --workers=1
npx playwright test tests/browser/pdc-v2-maestro-sinco.spec.mjs --workers=1
```

Expected: `1 passed` en ambas (el catálogo global acumula, pero el assert es por presencia del insumo, no por conteo). Regresión: `npx playwright test tests/browser/pdc-v2-maestro.spec.mjs --workers=1` → `1 passed`.

- [ ] **Step 4: Commits en lps-aia**

```bash
git add public/pdc-app && git add -f tests/browser/fixtures/pdc/maestro-sinco-mini.xlsx tests/browser/pdc-v2-maestro-sinco.spec.mjs
git commit -m "feat(pdc-v2): bundle con la sección de import del maestro + e2e del import SINCO"
```

- [ ] **Step 5: CLAUDE.md de plan-de-compras y commit**

En la sección "Estado actual" del `CLAUDE.md`, actualizar el primer párrafo a mencionar A2.5 (verbatim):

```markdown
Rama en curso: **Fases A1, A1.5, A2 y A2.5 implementadas** — importador de presupuesto, visor en árbol, maestro de insumos global (auto-match + cola de pendientes) y **importador del maestro SINCO** (siembra `general_maestro_insumos` con 3.088 insumos: código, agrupación, tipo de recurso, valor), todo bajo la navegación Ensamble | Seguimiento. Verificado con Vitest, tests PHP autoejecutables y e2e Playwright.
```

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md && git commit -m "docs(pdc): CLAUDE.md refleja la Fase A2.5 (import del maestro SINCO)"
```

---

## Verificación end-to-end (tras Task 6)

1. plan-de-compras: `npm run test` (reducer nuevo verde) + `npm run build` OK.
2. lps-aia: `docker compose exec app php tests/test_pdc_v2_maestro_sinco_parser.php && … _import.php` → exit 0; regresión `test_pdc_v2_maestro.php` + `test_pdc_v2_contexto.php` verdes.
3. Gates BD `test_global_table_safety` + `reconciliation` exit 0. PHPStan `src/Services/Pdc src/Controllers/Api` sin errores.
4. e2e: `pdc-v2-maestro-sinco` (×2) + `pdc-v2-maestro` + `pdc-v2-visor` + `pdc-v2-import` + `pdc-v2-fundacion` → todos passed.
5. Manual (navegador): login → Da Porto → Maestro → subir el **Excel real de SINCO** → preview (~3.084 activos, ~73 agrupaciones) → confirmar → catálogo poblado.

## Riesgos anotados

- Volumen real: ~3.084 upserts fila-a-fila en una transacción. Medición A1: ~800 INSERTs ≈ 0.42s; 3.084 ≈ ~1.5-2s, muy por debajo de `max_execution_time=180s`. Si un import futuro creciera mucho, batch multi-row (follow-up).
- Colisión norma+unidad con distinto `codigo_sinco`: se reporta en `conflictos` (no se pisa). El primer import real de SINCO puede revelar algunas; revisar el conteo.
- El primer import real del maestro SINCO es prerequisito de A3 para que el motor de sugerencias tenga la señal de Agrupación.
