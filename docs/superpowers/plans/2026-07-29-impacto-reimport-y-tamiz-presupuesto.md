# Impacto al recargar el presupuesto + tamiz y cifras honestas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que recargar una versión del presupuesto informe del impacto sobre el trabajo ya hecho **antes** de confirmar, y que el visor señale insumos vacíos y partidas globales mientras toda cifra de insumos de la app dice qué cuenta.

**Architecture:** Todo es lectura pura, sin migraciones. En PHP se extrae de `PresupuestoImportService` la consolidación de insumos por `(descripcion_norm, unidad)` —que ya existe para el comparativo de A1.6— a una función que acepta filas sueltas, de modo que la misma lógica sirva para la versión activa (de la base) y para la candidata (recién parseada, aún sin persistir). Cruzada con `pdc_insumo_paquete`, cuya clave única es exactamente ese par, da las cuatro cifras del impacto; devueltas dentro de la respuesta de `preview`, que la pantalla ya pide. El tamiz viaja igual: dentro de `arbol()`, el servicio del visor, para que un presupuesto no pueda mostrarse sin sus avisos. El umbral del «globalazo» no lo fija el servidor: éste manda los candidatos con su valor y el costo total de la versión, y la vista aplica el umbral que el usuario pone en pantalla.

**Tech Stack:** PHP 8.3 (`src/Services/Pdc`, `src/Controllers/Api`), tests PHP autoejecutables (`tests/test_*.php`, sin PHPUnit), React 18 + TypeScript + Vite + AG Grid (`pdc-app/`), Vitest, Playwright (`tests/browser/pdc-v2-*.spec.mjs`).

## Global Constraints

- **Ninguna regla automática de reagrupación.** Si un insumo cambia de agrupación se **señala**; no se reasigna solo. Todo el módulo se sostiene sobre confirmación humana.
- **Los avisos del tamiz no bloquean nada:** ni importar, ni asignar, ni recalcular. Dicen «mira esto», nunca «esto está mal», y el texto tiene que sonar así.
- **Sin migraciones, sin tablas nuevas, sin endpoint nuevo.** Lectura pura sobre datos que ya están.
- **No hay consulta nueva contra el presupuesto**: se reusa el comparativo de A1.6 (`comparar()` / `insumosConsolidados()`) cruzado con `pdc_insumo_paquete`. Un join más, no un motor nuevo.
- **UI: solo desktop ≥1180 px y solo dark.** Viewport canónico de validación 1180×820. Prohibido trabajar, probar o generar evidencia para mobile, tablet o el tema `linen`.
- **Aislamiento por `project_id` en toda consulta operativa.** RBAC: lectura con `lps.pdc.ver`; el `preview` sigue exigiendo `lps.pdc.importar` + CSRF.
- **Las dos palabras de las cifras, siempre las mismas:** «apariciones en APU» y «insumos distintos». Nunca «insumos» a secas para un conteo.
- **Nada de hex ni estilos inline** en `pdc-app/src/styles.css`: tokens y clases `pdc-*` existentes.
- **No hacer commit, push ni deploy** sin petición explícita del usuario. Los `git commit` de los pasos de este plan quedan pendientes de esa autorización: agrupar el trabajo y pedirla al cierre.

## Entorno de esta rama (ya montado, no rehacer)

- Worktree: `.claude/worktrees/pdc-presupuesto-impacto-tamiz`, rama `worktree-pdc-presupuesto-impacto-tamiz`, base `1a75b19`.
- Stack de compose **propio**: proyecto `pdc-tamiz`, app en `http://localhost:8096`, MySQL en `3320`, Adminer en `8097`, volumen `pdc_tamiz_db_data` (copia de la base compartida). El override vive fuera del repo, en el scratchpad, y se activa por `COMPOSE_FILE` en el `.env` del worktree. `docker compose port app 80` devuelve `8096`, así que los e2e y el sembrado del sandbox apuntan aquí y **no** a la base de la sesión vecina.
- Comandos: `docker compose exec -T app php tests/test_X.php`, `docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress`, `cd pdc-app && npm test`, `npx playwright test tests/browser/pdc-v2-import.spec.mjs --workers=1`.

## Medición contra Da Porto (proyecto 73, versión 376) — ya hecha, es la base de los números de este plan

Costo total $29.492.804.354 · 403 actividades · **820 apariciones en APU** · **396 insumos distintos** · 12 asignaciones a paquete.

| Hecho medido | Valor |
|---|---|
| Actividades con `cantidad = 0` | **47** (son los «~46 insumos vacíos» que recordaba el comité) |
| Líneas de insumo que esas 47 arrastran a cero | **102** |
| Líneas de insumo en cero dentro de actividades **con** cantidad (residuo real) | **10** (todas con `cant_apu = 0`) |
| Suma: líneas con `cantidad_total = 0` o `valor_unitario = 0` | 112 = 102 + 10 |
| Actividades con `≤ 2` insumos | 297 de 403 (**el criterio solo es inservible**) |
| Actividades con unidad global (`SG`/`GL`) **y** `≤ 2` insumos | **57** |
| De esas 57, por encima del 0,25 % del presupuesto (≥ $73,7 M) | **17** |

**Decisiones del usuario sobre esta medición (2026-07-29):**

1. **El umbral del «globalazo» es un valor fijo que el usuario asigna en la vista.** El servidor no lo aplica. La vista arranca con `0,25 % del costo total de la versión activa` —$73.732.011 en Da Porto, 17 actividades marcadas, el listado que se juzgó accionable— y el usuario lo cambia ahí mismo. Se persiste por proyecto en `localStorage`.
2. **Los insumos vacíos se separan en dos avisos**, no en uno: «47 actividades sin cantidad» (con las 102 líneas que arrastran) y «10 insumos en cero por su propia línea de APU». Un solo aviso de 112 sería un número verdadero que señala mal: el 91 % es consecuencia de otra cosa.

## Límite honesto que hay que escribir, no tapar

La columna `Agrupacion` del Excel de SINCO **se lee y se descarta**: `PresupuestoExcelParser` no la persiste, y `pdc_presupuesto_apu_insumos` no tiene esa columna. La `agrupacion` que existe vive en `general_maestro_insumos`, indexada por `(descripcion_norm, unidad)` — es decir, es propiedad de la identidad del insumo y **no puede cambiar entre dos versiones del presupuesto**. Por tanto la tercera cifra del spec («insumos que cambian de agrupación») se implementa como **cambio de tipo de insumo** (`tipo_insumo`, que sí se persiste y sí es lo que consume el motor de sugerencias), y la copia de la pantalla dice «cambian de tipo de insumo», no «de agrupación». Añadir la agrupación real exigiría una migración, que el spec excluye. Esto va a la bitácora.

## File Structure

**Backend (PHP)**

- `src/Services/Pdc/PresupuestoImportService.php` — modificar. Extrae `consolidarInsumos()` de `insumosConsolidados()`; añade `impactoDeReimportar()` y lo cuelga de `previewDesdeArchivo()`; añade `avisosDelPresupuesto()` y lo cuelga de `arbol()`.
- `tests/test_pdc_v2_impacto_reimport.php` — crear. TDD del impacto, con fixtures xlsx sintéticos vía `tests/support/pdc_fixture_presupuesto.php`.
- `tests/test_pdc_v2_tamiz_presupuesto.php` — crear. TDD de los avisos del visor.

**Frontend (`pdc-app/`)**

- `src/lib/types.ts` — modificar. `ImpactoReimport`, `AvisosPresupuesto` y sus detalles; `ImportPreview.impacto`; `ArbolPresupuesto.avisos`.
- `src/lib/impactoReimport.ts` + `.test.ts` — crear. Derivaciones puras: ¿hay impacto?, texto de confirmación, filas del detalle.
- `src/lib/tamiz.ts` + `.test.ts` — crear. Umbral por defecto, filtrado de candidatos por umbral, textos de los avisos, lectura/escritura del umbral en `localStorage`.
- `src/lib/texto.ts` + `.test.ts` — modificar. `MAGNITUD_INSUMOS` y `contarInsumos()`: las dos palabras, en un solo sitio.
- `src/pages/ImportarPresupuesto.tsx` — modificar. Bloque de impacto en la previsualización + rótulo honesto de las dos cifras de insumos.
- `src/pages/VisorPresupuesto.tsx` — modificar. Barra de avisos con el control del umbral + las dos cifras en la cabecera.
- `src/pages/PaquetesContratacion.tsx` — modificar. Rótulos honestos.
- `src/styles.css` — modificar. Clases del bloque de impacto y de la barra de avisos.

**Tests de navegador**

- `tests/browser/pdc-v2-import.spec.mjs` — modificar (el texto «4 insumos» cambia) y ampliar con el impacto.
- `tests/browser/pdc-v2-tamiz.spec.mjs` — crear. Requiere añadir `!tests/browser/pdc-v2-tamiz.spec.mjs` a `.gitignore` (allowlist de `tests/browser`), o no se commitea.

---

### Task 1: Consolidación reutilizable (refactor sin cambio de comportamiento)

`insumosConsolidados()` hace dos cosas: consultar la base y agrupar por `(norm, unidad)`. El impacto necesita agrupar filas que **todavía no están en la base** (las que acaba de parsear el preview). Se separan las dos cosas. Sin este paso, el impacto duplicaría la clave de fusión y se desincronizaría del comparativo.

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php:686-704`
- Test: `tests/test_pdc_v2_comparar.php` (regresión, no se toca)

**Interfaces:**
- Produces: `public static function consolidarInsumos(array $rows): array` — recibe filas con las claves `descripcion`, `tipo_insumo`, `unidad`, `cantidad_total`, `valor_total` (las mismas que devuelven tanto el `SELECT` como el parser) y devuelve el mapa indexado por `"norm|unidad"` con la forma `{norm, descripcion, tipoInsumo, unidad, cantidadTotal, valorTotal, apariciones}`. `apariciones` es nuevo y lo usa Task 5.

- [ ] **Step 1: Correr el test del comparativo para tener la línea base en verde**

```bash
docker compose exec -T app php tests/test_pdc_v2_comparar.php
```
Expected: todo `PASS`, sin `FAIL`. Si ya está rojo, párate y repórtalo antes de refactorizar.

- [ ] **Step 2: Extraer la agrupación a un método estático puro**

En `src/Services/Pdc/PresupuestoImportService.php`, reemplazar el cuerpo de `insumosConsolidados()` y añadir el método estático:

```php
    /**
     * Agrupa filas de insumo por la clave de fusión del diff: `(descripcion_norm, unidad)`, que es
     * también la clave única de `pdc_insumo_paquete`. Estático y puro a propósito: lo llaman el
     * comparativo (con filas de la base) y el informe de impacto (con filas recién parseadas, de una
     * versión candidata que aún no existe). Si las dos rutas no compartieran esta función, el impacto
     * podría contar como «nuevo» un insumo que el comparativo considera el mismo.
     *
     * @param list<array<string, mixed>> $rows usa descripcion, tipo_insumo, unidad, cantidad_total,
     *                                        valor_total
     * @return array<string, array{
     *     norm: string,
     *     descripcion: mixed,
     *     tipoInsumo: mixed,
     *     unidad: mixed,
     *     cantidadTotal: float,
     *     valorTotal: float,
     *     apariciones: int
     * }>
     */
    public static function consolidarInsumos(array $rows): array
    {
        $acc = [];
        foreach ($rows as $r) {
            $norm = MaestroInsumosService::normalizar((string) ($r['descripcion'] ?? ''));
            $clave = $norm . '|' . ($r['unidad'] ?? '');
            if (!isset($acc[$clave])) {
                $acc[$clave] = [
                    'norm' => $norm,
                    'descripcion' => $r['descripcion'] ?? '',
                    'tipoInsumo' => $r['tipo_insumo'] ?? '',
                    'unidad' => $r['unidad'] ?? '',
                    'cantidadTotal' => 0.0,
                    'valorTotal' => 0.0,
                    'apariciones' => 0,
                ];
            }
            $acc[$clave]['cantidadTotal'] += (float) ($r['cantidad_total'] ?? 0);
            $acc[$clave]['valorTotal'] += (float) ($r['valor_total'] ?? 0);
            $acc[$clave]['apariciones']++;
        }
        return $acc;
    }
```

Y `insumosConsolidados()` queda solo con la consulta:

```php
    /**
     * Insumos consolidados de una versión ya persistida. La agrupación la hace
     * `consolidarInsumos()`, compartida con el informe de impacto.
     *
     * @return array<string, array{
     *     norm: string,
     *     descripcion: mixed,
     *     tipoInsumo: mixed,
     *     unidad: mixed,
     *     cantidadTotal: float,
     *     valorTotal: float,
     *     apariciones: int
     * }> indexado por "descripcion_norm|unidad", que es la clave de fusión del diff (spec A1.6)
     */
    private function insumosConsolidados(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad, cantidad_total, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return self::consolidarInsumos($rows);
    }
```

- [ ] **Step 3: Correr el test del comparativo otra vez**

```bash
docker compose exec -T app php tests/test_pdc_v2_comparar.php
```
Expected: exactamente los mismos `PASS` que en el Step 1, cero `FAIL`. El refactor no cambia comportamiento.

- [ ] **Step 4: PHPStan nivel 6 del PDC**

```bash
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
```
Expected: `[OK] No errors`.

- [ ] **Step 5: Preparar el commit (no ejecutarlo todavía)**

```bash
git add src/Services/Pdc/PresupuestoImportService.php
# commit al final, cuando el usuario autorice publicar
```

---

### Task 2: El impacto de reimportar, en PHP

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php` (nuevo método `impactoDeReimportar()`; `previewDesdeArchivo()` lo cuelga de su respuesta)
- Create: `tests/test_pdc_v2_impacto_reimport.php`

**Interfaces:**
- Consumes: `self::consolidarInsumos()` (Task 1), `versionActivaDe()` (privado, ya existe).
- Produces:

```php
/**
 * @return array{
 *   versionActiva: array{id: int, label: mixed}|null,
 *   nuevosSinPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
 *   desaparecenConPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
 *   cambianTipo: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
 *   valorAfectado: float
 * }
 */
public function impactoDeReimportar(int $projectId, array $insumosCandidatos): array
```
  Cada fila de `detalle` tiene: `descripcion`, `unidad`, `tipoInsumo`, `tipoInsumoAnterior` (`string|null`, solo en `cambianTipo`), `valorTotal`, `paquete` (`string|null`).
  `ImportPreview` gana la clave `impacto` con ese mismo contenido.

**Reglas, cerradas:**
- «Tiene destino» = existe fila en `pdc_insumo_paquete` para ese `(project_id, descripcion_norm, unidad)` **y** (`paquete_id IS NOT NULL` **o** `omitido = 1`). Omitir es una decisión tomada; no cuenta como trabajo pendiente.
- `nuevosSinPaquete` = claves de la candidata que no están en la activa **y** no tienen destino. Valor: el de la candidata.
- `desaparecenConPaquete` = claves de la activa que no están en la candidata **y** tienen `paquete_id` (omitidos fuera: no se pierde trabajo al desaparecer algo que se decidió no contratar). Valor: el de la activa.
- `cambianTipo` = claves en ambas cuyo `tipo_insumo` difiere comparando `mb_strtoupper(trim(...))`. Valor: el de la candidata.
- `valorAfectado` = suma de los tres `valor`, redondeada a 2.
- Sin versión activa: todo a cero y `versionActiva = null`. No hay trabajo previo que perder.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_impacto_reimport.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_IMP_A = 999911;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id = ?', [PDC_IMP_A]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_IMP_A]);
};
$limpiar();

echo "=== PDC v2: impacto de reimportar sobre el trabajo hecho ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-imp-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// V1: dos actividades, cuatro insumos. TEJA y AYUDANTE reciben paquete; el resto no.
$v1 = sys_get_temp_dir() . '/pdc_imp_v1.xlsx';
pdcFixtureEscribir($v1, [
    ['01',          'PRELIMINARES',    '',         '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01',       'CAMPAMENTO',      '01',       '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01.01',    'INSTALACIONES',   '01.01',    '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2', '01.01.01', 'M2', 18,   '', 102, 'IMP_1', 'APU-001', null, null, null, null,   '',              ''],
    ['',            'TEJA DE ZINC',    '',         'M2', null, '', 102, 'IMP_1', '',        1.0,  1.0,  19,   30000,  'MAT-CUBIERTAS', ''],
    ['',            'AYUDANTE',        '',         'DIA', null, '', 102, 'IMP_1', '',       1.0,  1.0,  null, 80000,  'MANO DE OBRA',  ''],
    ['02',          'ESTRUCTURA',      '',         '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01',       'CONCRETOS',       '02',       '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01.01',    'LOSAS',           '02.01',    '',   null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',     '02.01.01', 'M3', 40,   '', 102, 'IMP_1', 'APU-002', null, null, null, null,   '',              ''],
    ['',            'CONCRETO 3000PSI', '',        'M3', null, '', 102, 'IMP_1', '',        1.0,  1.0,  19,   520000, 'MAT-CONCRETOS', ''],
    ['',            'SERVICIO BOMBEO', '',         'M3', null, '', 102, 'IMP_1', '',        1.0,  1.0,  null, 63000,  'EQUIPOS',       ''],
]);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_IMP_A, 'tester');
$assert(isset($p1['impacto']), 'El preview trae un bloque de impacto.');
$assert(($p1['impacto']['versionActiva'] ?? 'x') === null, 'Sin versión activa: versionActiva = null.');
$assert(($p1['impacto']['valorAfectado'] ?? -1) === 0.0, 'Sin versión activa: valor afectado = 0 (no hay trabajo que perder).');
$c1 = $service->confirmar($p1['importToken'], PDC_IMP_A);

// Un paquete real y dos asignaciones: TEJA y AYUDANTE tienen destino.
$db->query(
    'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, created_at) VALUES (?, ?, NOW())',
    ['CUBIERTAS IMP TEST', 'CUBIERTAS IMP TEST'],
);
$paqueteId = (int) $db->lastInsertId();
foreach ([['TEJA DE ZINC', 'M2'], ['AYUDANTE', 'DIA']] as [$desc, $und]) {
    $db->query(
        'INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
         VALUES (?, ?, ?, ?, 0, ?, NOW())',
        [PDC_IMP_A, $desc, $und, $paqueteId, 'tester'],
    );
}

// Condición de hecho 1 — candidata idéntica a la activa: las cuatro cifras dan cero.
$pIgual = $service->previewDesdeArchivo($v1, 'v1-otra-vez.xlsx', PDC_IMP_A, 'tester');
$i0 = $pIgual['impacto'];
$assert($i0['nuevosSinPaquete']['cantidad'] === 0, 'Idéntica: 0 insumos nuevos sin paquete.');
$assert($i0['desaparecenConPaquete']['cantidad'] === 0, 'Idéntica: 0 insumos que desaparecen con paquete.');
$assert($i0['cambianTipo']['cantidad'] === 0, 'Idéntica: 0 insumos que cambian de tipo.');
$assert($i0['valorAfectado'] === 0.0, 'Idéntica: valor afectado = 0.');
$assert(($i0['versionActiva']['id'] ?? 0) === $c1['versionId'], 'Idéntica: informa contra la versión activa.');

// V2: MALLA es nueva y sin paquete · AYUDANTE (con paquete) desaparece · SERVICIO BOMBEO
// cambia de tipo de insumo (EQUIPOS → SUBCONTRATOS) · TEJA y CONCRETO siguen igual.
$v2 = sys_get_temp_dir() . '/pdc_imp_v2.xlsx';
pdcFixtureEscribir($v2, [
    ['01',          'PRELIMINARES',    '',         '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01',       'CAMPAMENTO',      '01',       '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01.01',    'INSTALACIONES',   '01.01',    '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2', '01.01.01', 'M2', 18,   '', 102, 'IMP_2', 'APU-001', null, null, null, null,   '',              ''],
    ['',            'TEJA DE ZINC',    '',         'M2', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   30000,  'MAT-CUBIERTAS', ''],
    ['02',          'ESTRUCTURA',      '',         '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01',       'CONCRETOS',       '02',       '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01.01',    'LOSAS',           '02.01',    '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',     '02.01.01', 'M3', 40,   '', 102, 'IMP_2', 'APU-002', null, null, null, null,   '',              ''],
    ['',            'CONCRETO 3000PSI', '',        'M3', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   520000, 'MAT-CONCRETOS', ''],
    ['',            'SERVICIO BOMBEO', '',         'M3', null, '', 102, 'IMP_2', '',        1.0,  1.0,  null, 63000,  'SUBCONTRATOS',  ''],
    ['',            'MALLA ELECTROSOLDADA', '',    'KG', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   6000,   'MAT-ACEROS',    ''],
]);
$p2 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_IMP_A, 'tester');
$i = $p2['impacto'];

// Condición de hecho 2 — 1 · 1 · 1, y el detalle nombra exactamente esos tres.
$assert($i['nuevosSinPaquete']['cantidad'] === 1, 'V2: 1 insumo nuevo sin paquete.');
$assert(($i['nuevosSinPaquete']['detalle'][0]['descripcion'] ?? '') === 'MALLA ELECTROSOLDADA', 'V2: el nuevo es MALLA ELECTROSOLDADA.');
$assert($i['desaparecenConPaquete']['cantidad'] === 1, 'V2: 1 insumo con paquete desaparece.');
$assert(($i['desaparecenConPaquete']['detalle'][0]['descripcion'] ?? '') === 'AYUDANTE', 'V2: el que desaparece es AYUDANTE.');
$assert(($i['desaparecenConPaquete']['detalle'][0]['paquete'] ?? '') === 'CUBIERTAS IMP TEST', 'V2: el detalle dice a qué paquete estaba asignado.');
$assert($i['cambianTipo']['cantidad'] === 1, 'V2: 1 insumo cambia de tipo.');
$assert(($i['cambianTipo']['detalle'][0]['descripcion'] ?? '') === 'SERVICIO BOMBEO', 'V2: el que cambia de tipo es SERVICIO BOMBEO.');
$assert(($i['cambianTipo']['detalle'][0]['tipoInsumoAnterior'] ?? '') === 'EQUIPOS', 'V2: el detalle dice de qué tipo venía.');
$assert(($i['cambianTipo']['detalle'][0]['tipoInsumo'] ?? '') === 'SUBCONTRATOS', 'V2: el detalle dice a qué tipo va.');
$assert(count($i['nuevosSinPaquete']['detalle']) === 1 && count($i['desaparecenConPaquete']['detalle']) === 1 && count($i['cambianTipo']['detalle']) === 1, 'V2: ningún grupo arrastra insumos que no cambiaron (TEJA y CONCRETO fuera).');

// Condición de hecho 3 — el valor afectado es la suma de los tres grupos.
$suma = $i['nuevosSinPaquete']['valor'] + $i['desaparecenConPaquete']['valor'] + $i['cambianTipo']['valor'];
$assert(abs($i['valorAfectado'] - round($suma, 2)) < 0.01, 'V2: valorAfectado = suma de los tres grupos.');
$assert($i['valorAfectado'] > 0, 'V2: el valor afectado no es cero.');

// Un insumo asignado que se conserva no entra en ningún grupo (TEJA sigue existiendo).
$nombres = array_merge(
    array_column($i['nuevosSinPaquete']['detalle'], 'descripcion'),
    array_column($i['desaparecenConPaquete']['detalle'], 'descripcion'),
    array_column($i['cambianTipo']['detalle'], 'descripcion'),
);
$assert(!in_array('TEJA DE ZINC', $nombres, true), 'V2: un insumo asignado que se conserva no aparece como impacto.');

// Condición de hecho 4 — cancelar no escribe nada.
$activaAntes = $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_IMP_A])->fetchColumn();
$asignadosAntes = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [PDC_IMP_A])->fetchColumn();
$service->previewDesdeArchivo($v2, 'v2-que-se-cancela.xlsx', PDC_IMP_A, 'tester'); // preview y nunca confirmar
$activaDespues = $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_IMP_A])->fetchColumn();
$asignadosDespues = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [PDC_IMP_A])->fetchColumn();
$assert($activaAntes === $activaDespues, 'Cancelar: la versión activa queda intacta.');
$assert($asignadosAntes === $asignadosDespues, 'Cancelar: las asignaciones a paquete quedan intactas.');

// Aislamiento entre proyectos: el impacto no ve asignaciones de otro proyecto.
$db->query(
    'INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
     VALUES (?, ?, ?, ?, 0, ?, NOW())',
    [PDC_IMP_A + 1, 'MALLA ELECTROSOLDADA', 'KG', $paqueteId, 'tester'],
);
$p3 = $service->previewDesdeArchivo($v2, 'v2-aislamiento.xlsx', PDC_IMP_A, 'tester');
$assert($p3['impacto']['nuevosSinPaquete']['cantidad'] === 1, 'Aislamiento: una asignación de otro proyecto no da destino a MALLA aquí.');

// Condición de hecho 5 — confirmar conserva las asignaciones de lo que sigue existiendo.
$c2 = $service->confirmar($p2['importToken'], PDC_IMP_A);
$tejaSigue = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = ? AND paquete_id IS NOT NULL',
    [PDC_IMP_A, 'TEJA DE ZINC'],
)->fetchColumn();
$assert($c2['ok'] === true && $tejaSigue === 1, 'Confirmar conserva la asignación de TEJA (contrato de herencia de A3 intacto).');

$db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [PDC_IMP_A, PDC_IMP_A + 1]);
$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_IMP_A]);
$db->query('DELETE FROM general_paquetes_contratacion WHERE id = ?', [$paqueteId]);
foreach ([$v1, $v2] as $f) { @unlink($f); }

echo $failures === [] ? "\nOK — todo en verde\n" : "\n" . count($failures) . " FAIL\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec -T app php tests/test_pdc_v2_impacto_reimport.php
```
Expected: FAIL en «El preview trae un bloque de impacto.» y en cascada. Si en cambio revienta con un error de columna en `general_paquetes_contratacion` o `pdc_insumo_paquete`, comprueba el esquema real antes de tocar el servicio:
```bash
docker compose exec -T db mysql -uroot -p"$DB_PASS" -e "DESCRIBE lastplanneraia_dev.general_paquetes_contratacion; DESCRIBE lastplanneraia_dev.pdc_insumo_paquete"
```
y ajusta el `INSERT` del test a las columnas que existan (no el servicio).

- [ ] **Step 3: Implementar `impactoDeReimportar()`**

Añadir a `src/Services/Pdc/PresupuestoImportService.php`:

```php
    /**
     * Qué le pasa al trabajo ya hecho si se confirma esta versión candidata.
     *
     * Se responde ANTES de confirmar porque hoy el usuario carga a ciegas: la herencia de A3 existe
     * —las asignaciones se conservan y el auto-match vuelve a correr—, pero nadie sabe cuánto de su
     * trabajo va a quedar huérfano hasta después de haberlo hecho.
     *
     * No hay consulta nueva contra el presupuesto: la versión activa se consolida con la misma
     * función que usa el comparativo de A1.6 y la candidata con esa misma función sobre las filas que
     * el parser acaba de leer. `pdc_insumo_paquete` tiene como clave única exactamente
     * `(project_id, descripcion_norm, unidad)`, así que el cruce es un join más.
     *
     * **Informa; no decide.** Un insumo que cambió de tipo se señala y nada más: reasignarlo solo
     * rompería la única regla sobre la que se sostiene el módulo, que es la confirmación humana.
     *
     * @param list<array<string, mixed>> $insumosCandidatos filas del parser (sin persistir todavía)
     * @return array{
     *     versionActiva: array{id: int, label: mixed}|null,
     *     nuevosSinPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     desaparecenConPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     cambianTipo: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     valorAfectado: float
     * }
     */
    public function impactoDeReimportar(int $projectId, array $insumosCandidatos): array
    {
        $vacio = static fn (): array => ['cantidad' => 0, 'valor' => 0.0, 'detalle' => []];
        $activa = $this->versionActivaDe($projectId);
        if ($activa === null) {
            return [
                'versionActiva' => null,
                'nuevosSinPaquete' => $vacio(),
                'desaparecenConPaquete' => $vacio(),
                'cambianTipo' => $vacio(),
                'valorAfectado' => 0.0,
            ];
        }

        $antes = $this->insumosConsolidados($projectId, (int) $activa['id']);
        $despues = self::consolidarInsumos($insumosCandidatos);

        // Destino de cada insumo: nombre del paquete, o '' cuando está omitido a propósito (omitir
        // también es una decisión tomada, así que tampoco es trabajo pendiente).
        $rows = $this->db->query(
            'SELECT a.descripcion_norm, a.unidad, a.omitido, p.nombre
             FROM pdc_insumo_paquete a
             LEFT JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE a.project_id = ? AND (a.paquete_id IS NOT NULL OR a.omitido = 1)',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $destino = [];
        foreach ($rows as $r) {
            $destino[$r['descripcion_norm'] . '|' . $r['unidad']] = $r['nombre'];
        }

        $fila = static fn (array $ins, ?string $paquete, ?string $tipoAnterior = null): array => [
            'descripcion' => $ins['descripcion'],
            'unidad' => $ins['unidad'],
            'tipoInsumo' => $ins['tipoInsumo'],
            'tipoInsumoAnterior' => $tipoAnterior,
            'valorTotal' => round($ins['valorTotal'], 2),
            'paquete' => $paquete,
        ];
        $norma = static fn (mixed $t): string => mb_strtoupper(trim((string) $t));

        $nuevos = [];
        $desaparecen = [];
        $cambian = [];
        foreach ($despues as $clave => $ins) {
            if (!isset($antes[$clave])) {
                if (!array_key_exists($clave, $destino)) {
                    $nuevos[] = $fila($ins, null);
                }
                continue;
            }
            if ($norma($ins['tipoInsumo']) !== $norma($antes[$clave]['tipoInsumo'])) {
                $cambian[] = $fila($ins, $destino[$clave] ?? null, (string) $antes[$clave]['tipoInsumo']);
            }
        }
        foreach ($antes as $clave => $ins) {
            // Omitido (destino === null en el mapa) fuera: no se pierde trabajo al desaparecer algo
            // que ya se había decidido no contratar.
            if (!isset($despues[$clave]) && ($destino[$clave] ?? null) !== null) {
                $desaparecen[] = $fila($ins, $destino[$clave]);
            }
        }

        $porValor = static function (array $x, array $y): int {
            return $y['valorTotal'] <=> $x['valorTotal'];
        };
        usort($nuevos, $porValor);
        usort($desaparecen, $porValor);
        usort($cambian, $porValor);

        $grupo = static fn (array $detalle): array => [
            'cantidad' => count($detalle),
            'valor' => round(array_sum(array_column($detalle, 'valorTotal')), 2),
            'detalle' => $detalle,
        ];
        $g1 = $grupo($nuevos);
        $g2 = $grupo($desaparecen);
        $g3 = $grupo($cambian);

        return [
            'versionActiva' => ['id' => (int) $activa['id'], 'label' => $activa['version_label']],
            'nuevosSinPaquete' => $g1,
            'desaparecenConPaquete' => $g2,
            'cambianTipo' => $g3,
            'valorAfectado' => round($g1['valor'] + $g2['valor'] + $g3['valor'], 2),
        ];
    }
```

Y colgarlo del preview: en `previewDesdeArchivo()`, añadir `'impacto' => $this->impactoDeReimportar($projectId, $resultado['insumos']),` al array de retorno, y la clave `impacto` al phpdoc `@return` del método (`array{...}` del caso `ok: true`).

- [ ] **Step 4: Correr el test hasta verde**

```bash
docker compose exec -T app php tests/test_pdc_v2_impacto_reimport.php
```
Expected: todo `PASS`, `OK — todo en verde`, exit 0.

- [ ] **Step 5: Regresión del importador y PHPStan**

```bash
docker compose exec -T app php tests/test_pdc_v2_import_flujo.php
docker compose exec -T app php tests/test_pdc_v2_import_parser.php
docker compose exec -T app php tests/test_pdc_v2_comparar.php
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
```
Expected: los tres tests sin `FAIL`; PHPStan `[OK] No errors`.

---

### Task 3: El bloque de impacto en la previsualización

**Files:**
- Modify: `pdc-app/src/lib/types.ts`
- Create: `pdc-app/src/lib/impactoReimport.ts`, `pdc-app/src/lib/impactoReimport.test.ts`
- Modify: `pdc-app/src/pages/ImportarPresupuesto.tsx:261-285`
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `ImportPreview.impacto` (Task 2).
- Produces:
  - `export type GrupoImpacto = { cantidad: number; valor: number; detalle: FilaImpacto[] }`
  - `export type FilaImpacto = { descripcion: string; unidad: string; tipoInsumo: string; tipoInsumoAnterior: string | null; valorTotal: number; paquete: string | null }`
  - `export type ImpactoReimport = { versionActiva: { id: number; label: string | null } | null; nuevosSinPaquete: GrupoImpacto; desaparecenConPaquete: GrupoImpacto; cambianTipo: GrupoImpacto; valorAfectado: number }`
  - `export function hayImpacto(i: ImpactoReimport | null | undefined): boolean`
  - `export function textoConserva(i: ImpactoReimport | null | undefined): string` — la frase de qué se conserva y qué no, en palabras, para antes del botón.

- [ ] **Step 1: Escribir los tests que fallan**

Crear `pdc-app/src/lib/impactoReimport.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { hayImpacto, textoConserva } from './impactoReimport'
import type { ImpactoReimport } from './types'

const grupoVacio = { cantidad: 0, valor: 0, detalle: [] }
const cero: ImpactoReimport = {
  versionActiva: { id: 7, label: 'V1' },
  nuevosSinPaquete: grupoVacio,
  desaparecenConPaquete: grupoVacio,
  cambianTipo: grupoVacio,
  valorAfectado: 0,
}

describe('hayImpacto', () => {
  it('es falso cuando las cuatro cifras dan cero', () => {
    expect(hayImpacto(cero)).toBe(false)
  })

  it('es falso sin versión activa (no hay trabajo previo que perder)', () => {
    expect(hayImpacto({ ...cero, versionActiva: null })).toBe(false)
  })

  it('es verdadero con un solo insumo nuevo sin paquete', () => {
    expect(hayImpacto({ ...cero, nuevosSinPaquete: { cantidad: 1, valor: 0, detalle: [] } })).toBe(true)
  })

  it('es verdadero cuando solo cambia el tipo de un insumo', () => {
    expect(hayImpacto({ ...cero, cambianTipo: { cantidad: 3, valor: 100, detalle: [] } })).toBe(true)
  })

  it('tolera la ausencia del bloque', () => {
    expect(hayImpacto(null)).toBe(false)
    expect(hayImpacto(undefined)).toBe(false)
  })
})

describe('textoConserva', () => {
  it('sin impacto dice que no se pierde nada del trabajo hecho', () => {
    expect(textoConserva(cero)).toContain('no se pierde')
  })

  it('con impacto nombra qué se conserva y qué queda por revisar', () => {
    const t = textoConserva({
      ...cero,
      nuevosSinPaquete: { cantidad: 2, valor: 500, detalle: [] },
      desaparecenConPaquete: { cantidad: 1, valor: 300, detalle: [] },
      valorAfectado: 800,
    })
    expect(t).toContain('se conservan')
    expect(t).toMatch(/2 insumos? nuevos?/)
    expect(t).toMatch(/1 insumo/)
  })

  it('nunca promete reagrupar solo', () => {
    const t = textoConserva({ ...cero, cambianTipo: { cantidad: 4, valor: 900, detalle: [] } })
    expect(t).not.toMatch(/reasign|reagrup|automátic/i)
    expect(t).toContain('revisar')
  })
})
```

- [ ] **Step 2: Correr los tests y verificar que fallan**

```bash
cd pdc-app && npx vitest run src/lib/impactoReimport.test.ts
```
Expected: FAIL — `Failed to resolve import "./impactoReimport"`.

- [ ] **Step 3: Añadir los tipos y escribir el módulo**

En `pdc-app/src/lib/types.ts`, junto a `ImpactoVersion` (que es otra cosa: el impacto de *cambiar cuál versión rige*, no el de *recargar*):

```ts
/** Una fila del detalle de impacto. `tipoInsumoAnterior` solo viene en el grupo de cambios de tipo. */
export type FilaImpacto = {
  descripcion: string
  unidad: string
  tipoInsumo: string
  tipoInsumoAnterior: string | null
  valorTotal: number
  paquete: string | null
}

export type GrupoImpacto = { cantidad: number; valor: number; detalle: FilaImpacto[] }

/**
 * Impacto de recargar una versión del presupuesto sobre el trabajo ya hecho. No confundir con
 * `ImpactoVersion`, que mide otra cosa: cuántos vínculos del maestro quedan apuntando a la versión
 * que se abandona al cambiar cuál rige.
 */
export type ImpactoReimport = {
  versionActiva: { id: number; label: string | null } | null
  nuevosSinPaquete: GrupoImpacto
  desaparecenConPaquete: GrupoImpacto
  cambianTipo: GrupoImpacto
  valorAfectado: number
}
```

y añadir `impacto: ImpactoReimport` al tipo `ImportPreview`.

Crear `pdc-app/src/lib/impactoReimport.ts`:

```ts
import { plural } from './texto'
import type { ImpactoReimport } from './types'

/**
 * ¿Hay algo que contarle al usuario antes de que confirme?
 *
 * Se mira la cantidad de los tres grupos, no `valorAfectado`: un insumo de $0 que se queda sin
 * paquete sigue siendo trabajo que aparece, y en Da Porto los insumos de valor cero existen y son
 * justo los que hay que mirar.
 */
export function hayImpacto(i: ImpactoReimport | null | undefined): boolean {
  if (!i || i.versionActiva === null) return false
  return i.nuevosSinPaquete.cantidad > 0
    || i.desaparecenConPaquete.cantidad > 0
    || i.cambianTipo.cantidad > 0
}

/**
 * Qué se conserva y qué no, en palabras, para el renglón que va antes del botón de confirmar.
 *
 * El texto no promete nada automático: los insumos que cambian de tipo quedan «por revisar», nunca
 * «reasignados». Esa es la línea del módulo entero.
 */
export function textoConserva(i: ImpactoReimport | null | undefined): string {
  const base = 'Las asignaciones a paquete de los insumos que siguen existiendo se conservan, '
    + 'y el plan de fechas no depende de la versión.'
  if (!hayImpacto(i) || !i) {
    return `${base} Con esta versión no se pierde nada del trabajo hecho.`
  }
  const partes: string[] = []
  if (i.nuevosSinPaquete.cantidad > 0) {
    partes.push(`${plural(i.nuevosSinPaquete.cantidad, 'insumo nuevo', 'insumos nuevos')} sin paquete`)
  }
  if (i.desaparecenConPaquete.cantidad > 0) {
    partes.push(`${plural(i.desaparecenConPaquete.cantidad, 'insumo asignado', 'insumos asignados')} que desaparece${i.desaparecenConPaquete.cantidad === 1 ? '' : 'n'}`)
  }
  if (i.cambianTipo.cantidad > 0) {
    partes.push(`${plural(i.cambianTipo.cantidad, 'insumo', 'insumos')} que cambia${i.cambianTipo.cantidad === 1 ? '' : 'n'} de tipo`)
  }
  return `${base} Queda por revisar a mano: ${partes.join(' · ')}.`
}
```

Comprobar la firma real de `plural()` en `pdc-app/src/lib/texto.ts` antes de usarla y ajustar la llamada si difiere.

- [ ] **Step 4: Correr los tests hasta verde**

```bash
cd pdc-app && npx vitest run src/lib/impactoReimport.test.ts
```
Expected: PASS, 8 tests.

- [ ] **Step 5: Pintar el bloque en la previsualización**

En `pdc-app/src/pages/ImportarPresupuesto.tsx`, dentro del bloque `{(state.fase === 'previewOk' || state.fase === 'confirmando') && r && (…)}`, entre las advertencias y el botón de confirmar:

```tsx
          {hayImpacto(state.preview?.impacto) && state.preview && (
            <div className="pdc-impacto" data-testid="pdc-import-impacto">
              <h3>Impacto sobre el trabajo ya hecho</h3>
              <p className="pdc-ayuda">
                Comparado con la {state.preview.impacto.versionActiva?.label || 'versión activa'}.
                Esto se informa; no se cambia nada solo.
              </p>
              <ul className="pdc-impacto-cifras">
                {([
                  ['nuevos', 'Insumos nuevos sin paquete', state.preview.impacto.nuevosSinPaquete, 'Aparecen en esta versión y no tienen destino asignado: es trabajo que se suma.'],
                  ['desaparecen', 'Insumos con paquete que desaparecen', state.preview.impacto.desaparecenConPaquete, 'Estaban asignados a un paquete y ya no existen: es trabajo que se pierde.'],
                  ['cambian', 'Insumos que cambian de tipo', state.preview.impacto.cambianTipo, 'Siguen existiendo, pero el motor los va a sugerir distinto. Se señalan: hay que revisarlos a mano.'],
                ] as const).map(([id, titulo, grupo, ayuda]) => (
                  <li key={id}>
                    <details data-testid={`pdc-impacto-${id}`}>
                      <summary>
                        <strong>{grupo.cantidad}</strong> {titulo} · {moneda(grupo.valor)}
                      </summary>
                      <p className="pdc-ayuda">{ayuda}</p>
                      {grupo.detalle.length === 0 ? (
                        <p className="pdc-vacio">Ninguno.</p>
                      ) : (
                        <table className="pdc-impacto-tabla">
                          <thead>
                            <tr><th>Insumo</th><th>Und</th><th>Tipo</th><th>Paquete actual</th><th>Valor</th></tr>
                          </thead>
                          <tbody>
                            {grupo.detalle.map((f) => (
                              <tr key={`${f.descripcion}|${f.unidad}`}>
                                <td>{f.descripcion}</td>
                                <td>{f.unidad}</td>
                                <td>{f.tipoInsumoAnterior === null ? f.tipoInsumo : `${f.tipoInsumoAnterior} → ${f.tipoInsumo}`}</td>
                                <td>{f.paquete ?? '—'}</td>
                                <td className="pdc-num">{moneda(f.valorTotal)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      )}
                    </details>
                  </li>
                ))}
              </ul>
              <p data-testid="pdc-impacto-valor">
                <strong>Valor afectado: {moneda(state.preview.impacto.valorAfectado)}</strong> — la suma de los tres grupos.
              </p>
            </div>
          )}
          <p className="pdc-ayuda" data-testid="pdc-import-conserva">{textoConserva(state.preview?.impacto)}</p>
```

Añadir a los imports: `import { hayImpacto, textoConserva } from '../lib/impactoReimport'`.

- [ ] **Step 6: Estilos, sin hex ni inline**

En `pdc-app/src/styles.css`, junto a las clases `pdc-*` existentes, usando las mismas variables de token que ya usan `.pdc-panel` y `.pdc-bloque` (copiar de ahí, no inventar colores):

```css
/* Bloque de impacto de la previsualización: informa antes de confirmar. Hereda el tratamiento de
   .pdc-panel para que se lea como un aviso y no como contenido más de la página. */
.pdc-impacto { margin-block: var(--pdc-espacio-3, 1rem); }
.pdc-impacto-cifras { list-style: none; padding: 0; margin: 0; display: grid; gap: var(--pdc-espacio-2, .5rem); }
.pdc-impacto-cifras > li > details > summary { cursor: pointer; }
.pdc-impacto-tabla { width: 100%; border-collapse: collapse; }
.pdc-impacto-tabla th, .pdc-impacto-tabla td { text-align: left; padding: .25rem .5rem; }
.pdc-impacto-tabla .pdc-num { text-align: right; font-variant-numeric: tabular-nums; }
```
Si `--pdc-espacio-*` no existe en el archivo, usar las variables que sí estén; **no** introducir valores de color.

- [ ] **Step 7: Build y suite de Vitest completa**

```bash
cd pdc-app && npm test && npm run build
```
Expected: Vitest todo verde; `tsc` sin errores; `vite build` genera el bundle en `public/pdc-app/`.

---

### Task 4: El tamiz en PHP — avisos dentro del árbol del visor

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php` (`arbol()` + nuevo `avisosDelPresupuesto()`)
- Create: `tests/test_pdc_v2_tamiz_presupuesto.php`

**Interfaces:**
- Produces: `arbol()` gana la clave `avisos`:

```php
/**
 * @return array{
 *   costoTotal: float,
 *   insumosDistintos: int,
 *   aparicionesApu: int,
 *   actividadesSinCantidad: array{cantidad: int, lineasEnCero: int, detalle: list<array<string, mixed>>},
 *   insumosEnCero: array{cantidad: int, detalle: list<array<string, mixed>>},
 *   partidasGlobales: array{unidades: list<string>, candidatos: list<array<string, mixed>>}
 * }
 */
```
  - `actividadesSinCantidad.detalle`: `{codigo, descripcion, valorTotal, lineas}`.
  - `insumosEnCero.detalle`: `{codigo, actividad, descripcion, unidad, cantidad, valorUnitario}`.
  - `partidasGlobales.candidatos`: `{codigo, descripcion, unidad, insumos, valorTotal}` — **todos** los candidatos, sin umbral; ordenados por `valorTotal` desc. El umbral lo aplica la vista (Task 5).
- Constante nueva: `public const UNIDADES_GLOBALES = ['SG', 'GL', 'GLB', 'GLOBAL'];`

**Reglas, cerradas y medidas contra Da Porto:**
- `actividadesSinCantidad`: `tipo_fila = 'actividad'` y `cantidad = 0` o `NULL` → 47. `lineasEnCero` = líneas de insumo que cuelgan de ellas → 102.
- `insumosEnCero`: líneas de insumo con (`cantidad_total = 0`/`NULL` **o** `valor_unitario = 0`/`NULL`) cuya actividad **sí** tiene `cantidad > 0` → 10. Es el residuo real; 102 + 10 = 112, la suma completa de la regla del spec, sin doble conteo.
- `partidasGlobales.candidatos`: actividades con `unidad IN UNIDADES_GLOBALES` **y** `≤ 2` insumos en su APU → 57.
- `insumosDistintos` = `count(self::consolidarInsumos(...))` → 396. `aparicionesApu` = número de filas → 820.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_tamiz_presupuesto.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_TAM = 999921;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TAM]);

echo "=== PDC v2: tamiz del presupuesto (avisos del visor) ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-tam-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Un presupuesto de juguete con los tres fenómenos y nada más:
//  · SIN CUANTIFICAR (cantidad 0) arrastra sus 2 insumos a cero    → 1 actividad, 2 líneas
//  · LOSA MACIZA tiene cantidad, pero MOLDURA CHAFLAN va con Cant APU 0 → 1 insumo en cero (residuo)
//  · RED CONTRA INCENDIO: unidad SG, un solo insumo caro           → 1 candidato a partida global
//  · CAMPAMENTO: normal, no debe aparecer en ningún aviso
//  · TEJA DE ZINC aparece dos veces (dos APU) → 5 apariciones, 4 insumos distintos
$fx = sys_get_temp_dir() . '/pdc_tam.xlsx';
pdcFixtureEscribir($fx, [
    ['01',          'PRELIMINARES',        '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['01.01',       'CAMPAMENTOS',         '01',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['01.01.01',    'INSTALACIONES',       '01.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2',     '01.01.01', 'M2', 18,   '', 102, 'TAM_1', 'APU-001', null, null, null, null,     '',              ''],
    ['',            'TEJA DE ZINC',        '',         'M2', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   30000,    'MAT-CUBIERTAS', ''],
    ['01.01.01.02', 'SIN CUANTIFICAR',     '01.01.01', 'M2', 0,    '', 102, 'TAM_1', 'APU-002', null, null, null, null,     '',              ''],
    ['',            'TEJA DE ZINC',        '',         'M2', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   30000,    'MAT-CUBIERTAS', ''],
    ['',            'ALAMBRE NEGRO',       '',         'KG', null, '', 102, 'TAM_1', '',        2.0,  1.0,  19,   5000,     'MAT-VARIOS',    ''],
    ['02',          'ESTRUCTURA',          '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['02.01',       'CONCRETOS',           '02',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['02.01.01',    'LOSAS',               '02.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',         '02.01.01', 'M3', 40,   '', 102, 'TAM_1', 'APU-003', null, null, null, null,     '',              ''],
    ['',            'CONCRETO 3000PSI',    '',         'M3', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   520000,   'MAT-CONCRETOS', ''],
    ['',            'MOLDURA CHAFLAN',     '',         'ML', null, '', 102, 'TAM_1', '',        0,    1.0,  19,   6499.78,  'MAT-VARIOS',    ''],
    ['03',          'REDES',               '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['03.01',       'CONTRA INCENDIO',     '03',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['03.01.01',    'RCI',                 '03.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,     '',              ''],
    ['03.01.01.01', 'RED CONTRA INCENDIO TODO COSTO', '03.01.01', 'SG', 1, '', 102, 'TAM_1', 'APU-004', null, null, null, null, '',      ''],
    ['',            'RCI TODO COSTO',      '',         'SG', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   548000000, 'SUBCONTRATOS', ''],
]);
$p = $service->previewDesdeArchivo($fx, 'tamiz.xlsx', PDC_TAM, 'tester');
$c = $service->confirmar($p['importToken'], PDC_TAM);
$arbol = $service->arbol(PDC_TAM, $c['versionId']);
$assert($arbol !== null && isset($arbol['avisos']), 'El árbol del visor trae sus avisos (no hay endpoint aparte).');
$a = $arbol['avisos'];

// Cifras honestas: las dos magnitudes, cada una con su número.
$assert($a['aparicionesApu'] === 5, 'aparicionesApu = 5 (líneas de insumo del presupuesto).');
$assert($a['insumosDistintos'] === 4, 'insumosDistintos = 4 (TEJA DE ZINC cuenta una vez, aparece en dos APU).');

// Aviso 1: actividades sin cantidad, con las líneas que arrastran.
$assert($a['actividadesSinCantidad']['cantidad'] === 1, 'Una actividad sin cantidad (SIN CUANTIFICAR).');
$assert($a['actividadesSinCantidad']['lineasEnCero'] === 2, 'Esa actividad arrastra sus 2 líneas de insumo a cero.');
$assert(($a['actividadesSinCantidad']['detalle'][0]['descripcion'] ?? '') === 'SIN CUANTIFICAR', 'El detalle nombra la actividad sin cantidad.');
$assert(($a['actividadesSinCantidad']['detalle'][0]['codigo'] ?? '') === '01.01.01.02', 'El detalle da su código, que es con lo que se busca en el presupuesto de origen.');

// Aviso 2: el residuo real, sin doble conteo con el aviso 1.
$assert($a['insumosEnCero']['cantidad'] === 1, 'Un insumo en cero por su propia línea de APU (MOLDURA CHAFLAN).');
$assert(($a['insumosEnCero']['detalle'][0]['descripcion'] ?? '') === 'MOLDURA CHAFLAN', 'El detalle nombra el insumo en cero.');
$assert(($a['insumosEnCero']['detalle'][0]['actividad'] ?? '') === 'LOSA MACIZA', 'El detalle dice en qué actividad está.');
$nombresEnCero = array_column($a['insumosEnCero']['detalle'], 'descripcion');
$assert(!in_array('ALAMBRE NEGRO', $nombresEnCero, true), 'Las líneas que arrastra una actividad sin cantidad NO se cuentan otra vez aquí.');

// Aviso 3: candidatos a partida global, todos, sin umbral aplicado.
$assert(count($a['partidasGlobales']['candidatos']) === 1, 'Un candidato a partida global.');
$assert(($a['partidasGlobales']['candidatos'][0]['codigo'] ?? '') === '03.01.01.01', 'El candidato es la RED CONTRA INCENDIO (unidad SG, un solo insumo).');
$assert(($a['partidasGlobales']['candidatos'][0]['insumos'] ?? 0) === 1, 'El candidato dice con cuántos insumos se resuelve el APU.');
$assert(abs(($a['partidasGlobales']['candidatos'][0]['valorTotal'] ?? 0) - 548000000.0) < 1.0, 'El candidato trae su valor, que es lo que la vista compara contra el umbral.');
$assert(in_array('SG', $a['partidasGlobales']['unidades'], true), 'Las unidades globales viajan con el aviso.');
$codigosGlobales = array_column($a['partidasGlobales']['candidatos'], 'codigo');
$assert(!in_array('01.01.01.01', $codigosGlobales, true), 'CAMPAMENTO (unidad M2, un solo insumo) no es partida global: el criterio de ≤2 insumos solo no basta.');
$assert($a['costoTotal'] > 0, 'El costo total de la versión viaja con los avisos (base del umbral por defecto).');

// Los avisos NO bloquean: con avisos abiertos, el árbol se sirve entero.
$assert(count($arbol['items']) === 12 && count($arbol['insumos']) === 5, 'Con avisos abiertos el árbol se sirve completo: los avisos no esconden ni bloquean nada.');

$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TAM]);
@unlink($fx);

echo $failures === [] ? "\nOK — todo en verde\n" : "\n" . count($failures) . " FAIL\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec -T app php tests/test_pdc_v2_tamiz_presupuesto.php
```
Expected: FAIL en «El árbol del visor trae sus avisos» y en cascada.

- [ ] **Step 3: Implementar `avisosDelPresupuesto()` y colgarlo de `arbol()`**

Añadir a `src/Services/Pdc/PresupuestoImportService.php`:

```php
    /**
     * Unidades que en el export de presupuestos significan «esto es una suma global»: el APU no se
     * descompone, se resuelve con una cifra. Medido contra Da Porto, donde son `SG` (54 actividades)
     * y `GL` (3); `GLB` y `GLOBAL` se aceptan porque el mismo software las emite según la plantilla.
     */
    public const UNIDADES_GLOBALES = ['SG', 'GL', 'GLB', 'GLOBAL'];

    /**
     * Lo que el presupuesto no explica solo, señalado sin bloquear nada.
     *
     * Viaja dentro de `arbol()` —el servicio del visor— y no en un endpoint aparte, para que un
     * presupuesto no pueda mostrarse sin sus avisos. Es la mejor oportunidad de la empresa para cazar
     * los machetazos: el que arma el plan de compras es el primero que los ve.
     *
     * Los dos avisos de ceros están **separados a propósito**. La regla del spec
     * (`cantidad = 0` o `valor_unitario = 0`) da 112 líneas en Da Porto, pero 102 de ellas son
     * consecuencia de otra cosa: 47 actividades que nadie cuantificó todavía. Reportar «112 insumos
     * vacíos» sería un número verdadero que señala mal, y el 47 es además el que le cuadra a quien
     * recorrió el presupuesto a mano. 102 + 10 = 112: no hay doble conteo ni fuga.
     *
     * El umbral del «globalazo» **no se aplica aquí**: se devuelven todos los candidatos con su
     * valor y el costo total de la versión, y quien pone el umbral es el usuario en la pantalla. Un
     * umbral cocinado en el servidor sería un juicio disfrazado de constante.
     *
     * @return array{
     *   costoTotal: float,
     *   insumosDistintos: int,
     *   aparicionesApu: int,
     *   actividadesSinCantidad: array{cantidad: int, lineasEnCero: int, detalle: list<array<string, mixed>>},
     *   insumosEnCero: array{cantidad: int, detalle: list<array<string, mixed>>},
     *   partidasGlobales: array{unidades: list<string>, candidatos: list<array<string, mixed>>}
     * }
     */
    private function avisosDelPresupuesto(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT i.descripcion, i.tipo_insumo, i.unidad, i.cantidad_total, i.valor_unitario, i.valor_total,
                    it.id AS item_id, it.codigo, it.descripcion AS actividad, it.unidad AS unidad_actividad,
                    it.cantidad AS cantidad_actividad, it.tipo_fila
             FROM pdc_presupuesto_apu_insumos i
             JOIN pdc_presupuesto_items it ON it.id = i.item_id
             WHERE i.project_id = ? AND i.version_id = ?
             ORDER BY it.codigo ASC, i.id ASC',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $sinCantidad = [];   // codigo => {codigo, descripcion, valorTotal, lineas}
        $enCero = [];
        $porItem = [];       // item_id => {codigo, descripcion, unidad, insumos, valorTotal}
        $costoTotal = 0.0;

        foreach ($rows as $r) {
            $valor = (float) ($r['valor_total'] ?? 0);
            $costoTotal += $valor;
            $itemId = (int) $r['item_id'];
            if (!isset($porItem[$itemId])) {
                $porItem[$itemId] = [
                    'codigo' => $r['codigo'], 'descripcion' => $r['actividad'],
                    'unidad' => $r['unidad_actividad'], 'insumos' => 0, 'valorTotal' => 0.0,
                ];
            }
            $porItem[$itemId]['insumos']++;
            $porItem[$itemId]['valorTotal'] += $valor;

            $actividadSinCantidad = ((float) ($r['cantidad_actividad'] ?? 0)) == 0.0;
            if ($actividadSinCantidad) {
                $cod = (string) $r['codigo'];
                if (!isset($sinCantidad[$cod])) {
                    $sinCantidad[$cod] = ['codigo' => $cod, 'descripcion' => $r['actividad'], 'valorTotal' => 0.0, 'lineas' => 0];
                }
                $sinCantidad[$cod]['lineas']++;
                $sinCantidad[$cod]['valorTotal'] += $valor;
                continue; // su línea de insumo la explica la actividad, no se cuenta dos veces
            }
            if (((float) ($r['cantidad_total'] ?? 0)) == 0.0 || ((float) ($r['valor_unitario'] ?? 0)) == 0.0) {
                $enCero[] = [
                    'codigo' => $r['codigo'], 'actividad' => $r['actividad'],
                    'descripcion' => $r['descripcion'], 'unidad' => $r['unidad'],
                    'cantidad' => (float) ($r['cantidad_total'] ?? 0),
                    'valorUnitario' => (float) ($r['valor_unitario'] ?? 0),
                ];
            }
        }

        // Actividades sin cantidad que además no tienen ninguna línea de insumo: existen y también
        // hay que mirarlas, así que el conteo sale de los items, no solo de las filas de arriba.
        $huerfanas = $this->db->query(
            "SELECT codigo, descripcion FROM pdc_presupuesto_items
             WHERE project_id = ? AND version_id = ? AND tipo_fila = 'actividad'
               AND (cantidad = 0 OR cantidad IS NULL)
               AND id NOT IN (SELECT item_id FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?)",
            [$projectId, $versionId, $projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($huerfanas as $h) {
            $sinCantidad[(string) $h['codigo']] = ['codigo' => $h['codigo'], 'descripcion' => $h['descripcion'], 'valorTotal' => 0.0, 'lineas' => 0];
        }

        $candidatos = [];
        foreach ($porItem as $it) {
            if ($it['insumos'] <= 2 && in_array(mb_strtoupper(trim((string) $it['unidad'])), self::UNIDADES_GLOBALES, true)) {
                $it['valorTotal'] = round($it['valorTotal'], 2);
                $candidatos[] = $it;
            }
        }
        usort($candidatos, static fn (array $x, array $y): int => $y['valorTotal'] <=> $x['valorTotal']);

        $detalleSinCantidad = array_values($sinCantidad);
        usort($detalleSinCantidad, static fn (array $x, array $y): int => strcmp((string) $x['codigo'], (string) $y['codigo']));

        return [
            'costoTotal' => round($costoTotal, 2),
            'insumosDistintos' => count(self::consolidarInsumos($rows)),
            'aparicionesApu' => count($rows),
            'actividadesSinCantidad' => [
                'cantidad' => count($detalleSinCantidad),
                'lineasEnCero' => array_sum(array_column($detalleSinCantidad, 'lineas')),
                'detalle' => $detalleSinCantidad,
            ],
            'insumosEnCero' => ['cantidad' => count($enCero), 'detalle' => $enCero],
            'partidasGlobales' => ['unidades' => self::UNIDADES_GLOBALES, 'candidatos' => $candidatos],
        ];
    }
```

En `arbol()`, añadir al array de retorno `'avisos' => $this->avisosDelPresupuesto($projectId, $vid),` y la clave `avisos` a su phpdoc `@return`.

- [ ] **Step 4: Correr el test hasta verde**

```bash
docker compose exec -T app php tests/test_pdc_v2_tamiz_presupuesto.php
```
Expected: todo `PASS`, exit 0. Si «insumosDistintos = 4» falla por uno, comprobar que `consolidarInsumos()` recibe filas con la clave `descripcion` del insumo y no la de la actividad — el `SELECT` alias la de la actividad como `actividad` justo por eso.

- [ ] **Step 5: Verificar los números contra Da Porto**

```bash
docker compose exec -T app php -r "require '/var/www/html/vendor/autoload.php'; require '/var/www/html/src/Core/Database.php';
\$s = new App\Services\Pdc\PresupuestoImportService(Database::getInstance(), new App\Services\Pdc\PresupuestoImportStore(), new App\Services\Pdc\PresupuestoExcelParser());
\$a = \$s->arbol(73, 376)['avisos'];
printf(\"distintos=%d apariciones=%d actSinCant=%d lineas=%d enCero=%d globales=%d costo=%.0f\n\",
  \$a['insumosDistintos'], \$a['aparicionesApu'], \$a['actividadesSinCantidad']['cantidad'],
  \$a['actividadesSinCantidad']['lineasEnCero'], \$a['insumosEnCero']['cantidad'],
  count(\$a['partidasGlobales']['candidatos']), \$a['costoTotal']);"
```
Expected, exactamente: `distintos=396 apariciones=820 actSinCant=47 lineas=102 enCero=10 globales=57 costo=29492804354`. Cualquier desviación es un error de la implementación, no de la medición: los seis números están medidos contra la base en este plan.

- [ ] **Step 6: Regresión de `arbol()` y PHPStan**

```bash
docker compose exec -T app php tests/test_pdc_v2_arbol.php
docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
```
Expected: sin `FAIL`; PHPStan `[OK] No errors`.

---

### Task 5: El tamiz en la pantalla + el umbral que pone el usuario

**Files:**
- Modify: `pdc-app/src/lib/types.ts`
- Create: `pdc-app/src/lib/tamiz.ts`, `pdc-app/src/lib/tamiz.test.ts`
- Modify: `pdc-app/src/pages/VisorPresupuesto.tsx`
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `ArbolPresupuesto.avisos` (Task 4).
- Produces:
  - `export const FRACCION_UMBRAL_POR_DEFECTO = 0.0025`
  - `export function umbralPorDefecto(costoTotal: number): number` — `0,25 %` del costo total, redondeado al millón hacia abajo para que el número que ve el usuario sea legible ($73.000.000 en Da Porto).
  - `export function partidasSobreUmbral(candidatos: CandidatoGlobal[], umbral: number): CandidatoGlobal[]`
  - `export function leerUmbral(projectId: number | string, costoTotal: number): number` / `export function guardarUmbral(projectId: number | string, umbral: number): void` — `localStorage`, clave `pdc-umbral-global:<projectId>`, tolerante a `localStorage` ausente o a un valor corrupto (cae al por defecto).

- [ ] **Step 1: Escribir los tests que fallan**

Crear `pdc-app/src/lib/tamiz.test.ts`:

```ts
import { beforeEach, describe, expect, it } from 'vitest'
import { FRACCION_UMBRAL_POR_DEFECTO, guardarUmbral, leerUmbral, partidasSobreUmbral, umbralPorDefecto } from './tamiz'
import type { CandidatoGlobal } from './types'

const c = (codigo: string, valorTotal: number): CandidatoGlobal =>
  ({ codigo, descripcion: codigo, unidad: 'SG', insumos: 1, valorTotal })

describe('umbralPorDefecto', () => {
  it('es el 0,25 % del presupuesto', () => {
    expect(FRACCION_UMBRAL_POR_DEFECTO).toBe(0.0025)
  })

  it('en Da Porto da un número legible del orden de 73 millones', () => {
    const u = umbralPorDefecto(29_492_804_354)
    expect(u).toBe(73_000_000)
  })

  it('con costo cero no explota ni devuelve NaN', () => {
    expect(umbralPorDefecto(0)).toBe(0)
  })
})

describe('partidasSobreUmbral', () => {
  const candidatos = [c('A', 890_000_000), c('B', 100_000_000), c('C', 5_000_000)]

  it('deja solo las que igualan o superan el umbral', () => {
    expect(partidasSobreUmbral(candidatos, 73_000_000).map((x) => x.codigo)).toEqual(['A', 'B'])
  })

  it('con umbral cero deja todas (el usuario pidió verlo todo)', () => {
    expect(partidasSobreUmbral(candidatos, 0)).toHaveLength(3)
  })

  it('con un umbral altísimo no deja ninguna, y eso no es un error', () => {
    expect(partidasSobreUmbral(candidatos, 10_000_000_000)).toEqual([])
  })

  it('no muta el arreglo que recibe', () => {
    const copia = [...candidatos]
    partidasSobreUmbral(candidatos, 50_000_000)
    expect(candidatos).toEqual(copia)
  })
})

describe('umbral persistido por proyecto', () => {
  beforeEach(() => { localStorage.clear() })

  it('sin nada guardado cae al valor por defecto del proyecto', () => {
    expect(leerUmbral(73, 29_492_804_354)).toBe(73_000_000)
  })

  it('devuelve lo que el usuario puso', () => {
    guardarUmbral(73, 150_000_000)
    expect(leerUmbral(73, 29_492_804_354)).toBe(150_000_000)
  })

  it('no mezcla proyectos', () => {
    guardarUmbral(73, 150_000_000)
    expect(leerUmbral(99, 4_000_000_000)).toBe(10_000_000)
  })

  it('acepta el cero como decisión del usuario, no como ausencia', () => {
    guardarUmbral(73, 0)
    expect(leerUmbral(73, 29_492_804_354)).toBe(0)
  })

  it('un valor corrupto cae al por defecto en vez de romper la pantalla', () => {
    localStorage.setItem('pdc-umbral-global:73', 'no-es-un-numero')
    expect(leerUmbral(73, 29_492_804_354)).toBe(73_000_000)
  })
})
```

- [ ] **Step 2: Correr los tests y verificar que fallan**

```bash
cd pdc-app && npx vitest run src/lib/tamiz.test.ts
```
Expected: FAIL — `Failed to resolve import "./tamiz"`. Si además falla por `localStorage is not defined`, añadir `environment: 'jsdom'` en la config de Vitest de `pdc-app` **solo si no está ya**; si el resto de la suite corre en `node`, usar en su lugar la config por test con el comentario `// @vitest-environment jsdom` en la cabecera de `tamiz.test.ts`.

- [ ] **Step 3: Añadir los tipos y escribir el módulo**

En `pdc-app/src/lib/types.ts`:

```ts
export type CandidatoGlobal = {
  codigo: string
  descripcion: string
  unidad: string
  /** Con cuántos insumos se resuelve el APU de la actividad. */
  insumos: number
  valorTotal: number
}

export type ActividadSinCantidad = { codigo: string; descripcion: string; valorTotal: number; lineas: number }

export type InsumoEnCero = {
  codigo: string
  actividad: string
  descripcion: string
  unidad: string
  cantidad: number
  valorUnitario: number
}

/**
 * Lo que el presupuesto no explica solo. Viaja dentro del árbol, no en un endpoint aparte: un
 * presupuesto no puede mostrarse sin sus avisos. Ninguno bloquea nada.
 */
export type AvisosPresupuesto = {
  costoTotal: number
  insumosDistintos: number
  aparicionesApu: number
  actividadesSinCantidad: { cantidad: number; lineasEnCero: number; detalle: ActividadSinCantidad[] }
  insumosEnCero: { cantidad: number; detalle: InsumoEnCero[] }
  partidasGlobales: { unidades: string[]; candidatos: CandidatoGlobal[] }
}
```
y añadir `avisos: AvisosPresupuesto` al tipo `ArbolPresupuesto`.

Crear `pdc-app/src/lib/tamiz.ts`:

```ts
import type { CandidatoGlobal } from './types'

/**
 * Fracción del presupuesto con la que arranca el umbral del «globalazo».
 *
 * Medida contra Da Porto ($29.492.804.354): de las 57 actividades con unidad global y APU de ≤2
 * insumos, el 0,25 % deja 17 — todos los «todo costo» reales de hidráulica y eléctrica, sin la cola
 * de maderas y bioseguridad de menos de $30 M. Con el 0,50 % quedaban 5 y se caían partidas que sí
 * hay que mirar; con el 0,10 % entraban 34 y el listado empezaba a parecer inventario.
 *
 * Es solo el punto de partida: **el umbral lo asigna el usuario en la vista**. Un umbral cerrado en
 * el código sería un juicio disfrazado de constante, y el juicio es de quien conoce la obra.
 */
export const FRACCION_UMBRAL_POR_DEFECTO = 0.0025

const CLAVE = (projectId: number | string): string => `pdc-umbral-global:${projectId}`

/** Redondeado al millón hacia abajo: el número que se ve en el control tiene que ser legible. */
export function umbralPorDefecto(costoTotal: number): number {
  if (!Number.isFinite(costoTotal) || costoTotal <= 0) return 0
  return Math.floor((costoTotal * FRACCION_UMBRAL_POR_DEFECTO) / 1_000_000) * 1_000_000
}

export function partidasSobreUmbral(candidatos: CandidatoGlobal[], umbral: number): CandidatoGlobal[] {
  return candidatos.filter((c) => c.valorTotal >= umbral)
}

/**
 * El umbral que puso el usuario en este proyecto, o el por defecto.
 *
 * El cero se respeta como decisión («quiero verlo todo»), así que la ausencia se distingue por
 * `null`, no por falsy. Un `localStorage` inaccesible o con basura cae al por defecto en silencio:
 * el visor tiene que abrir igual.
 */
export function leerUmbral(projectId: number | string, costoTotal: number): number {
  try {
    const crudo = localStorage.getItem(CLAVE(projectId))
    if (crudo === null) return umbralPorDefecto(costoTotal)
    const n = Number(crudo)
    return Number.isFinite(n) && n >= 0 ? n : umbralPorDefecto(costoTotal)
  } catch {
    return umbralPorDefecto(costoTotal)
  }
}

export function guardarUmbral(projectId: number | string, umbral: number): void {
  try {
    localStorage.setItem(CLAVE(projectId), String(umbral))
  } catch {
    // Sin almacenamiento (modo privado, cuota) el umbral vale para esta sesión y nada más.
  }
}
```

- [ ] **Step 4: Correr los tests hasta verde**

```bash
cd pdc-app && npx vitest run src/lib/tamiz.test.ts
```
Expected: PASS, 12 tests.

- [ ] **Step 5: Pintar los avisos y el control del umbral en el visor**

En `pdc-app/src/pages/VisorPresupuesto.tsx`:

1. Importar: `import { guardarUmbral, leerUmbral, partidasSobreUmbral } from '../lib/tamiz'` y `import { contarInsumos } from '../lib/texto'` (Task 6).
2. Estado del umbral, sembrado cuando llega el árbol:

```tsx
  const [umbral, setUmbral] = useState<number | null>(null)
  useEffect(() => {
    if (arbol) setUmbral(leerUmbral(arbol.version.id, arbol.avisos.costoTotal))
  }, [arbol])

  const globales = useMemo(
    () => (arbol && umbral !== null ? partidasSobreUmbral(arbol.avisos.partidasGlobales.candidatos, umbral) : []),
    [arbol, umbral],
  )
```

3. En la cabecera, bajo el `<p>` de la descripción, las dos cifras con su magnitud declarada:

```tsx
          {arbol && (
            <p className="pdc-ayuda" data-testid="pdc-visor-cifras">
              {contarInsumos(arbol.avisos.insumosDistintos, 'distintos')} ·{' '}
              {contarInsumos(arbol.avisos.aparicionesApu, 'apariciones')}
            </p>
          )}
```

4. Entre `pdc-visor-tools` y la grilla, la barra de avisos:

```tsx
          {arbol && umbral !== null && (
            <div className="pdc-bloque pdc-avisos" data-testid="pdc-visor-avisos">
              <p className="pdc-ayuda">
                Cosas que vale la pena mirar en este presupuesto. Son un dedo señalando: no impiden
                importar, ni asignar a paquetes, ni recalcular el plan.
              </p>

              <details data-testid="pdc-aviso-sin-cantidad">
                <summary>
                  <strong>{arbol.avisos.actividadesSinCantidad.cantidad}</strong> actividades sin cantidad
                  {arbol.avisos.actividadesSinCantidad.lineasEnCero > 0
                    && ` (arrastran ${arbol.avisos.actividadesSinCantidad.lineasEnCero} líneas de insumo a cero)`}
                </summary>
                <p className="pdc-ayuda">
                  Su APU tiene precios, pero nadie puso la cantidad todavía. Puede ser una partida
                  prevista sin valorar: mírala antes de confiar en su valor.
                </p>
                <table className="pdc-avisos-tabla">
                  <thead><tr><th>Código</th><th>Actividad</th><th>Líneas</th></tr></thead>
                  <tbody>
                    {arbol.avisos.actividadesSinCantidad.detalle.map((a) => (
                      <tr key={a.codigo}><td>{a.codigo}</td><td>{a.descripcion}</td><td className="pdc-num">{a.lineas}</td></tr>
                    ))}
                  </tbody>
                </table>
              </details>

              <details data-testid="pdc-aviso-en-cero">
                <summary>
                  <strong>{arbol.avisos.insumosEnCero.cantidad}</strong> insumos en cero por su propia línea de APU
                </summary>
                <p className="pdc-ayuda">
                  La actividad sí tiene cantidad, pero este insumo entra con cantidad o precio en cero.
                </p>
                <table className="pdc-avisos-tabla">
                  <thead><tr><th>Código</th><th>Actividad</th><th>Insumo</th><th>Und</th><th>Vr. unitario</th></tr></thead>
                  <tbody>
                    {arbol.avisos.insumosEnCero.detalle.map((i, idx) => (
                      <tr key={`${i.codigo}-${i.descripcion}-${idx}`}>
                        <td>{i.codigo}</td><td>{i.actividad}</td><td>{i.descripcion}</td><td>{i.unidad}</td>
                        <td className="pdc-num">{moneda(i.valorUnitario)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </details>

              <details data-testid="pdc-aviso-globales">
                <summary>
                  <strong>{globales.length}</strong> actividades resueltas con una partida global
                </summary>
                <p className="pdc-ayuda">
                  Su APU se resuelve con una o dos líneas de unidad global
                  ({arbol.avisos.partidasGlobales.unidades.join(', ')}) por encima del umbral. Un
                  imprevisto o una provisión pueden ser globales con razón; un capítulo entero
                  resuelto de un plumazo, normalmente no.
                </p>
                <label className="pdc-selector">
                  Umbral de valor{' '}
                  <input
                    type="number"
                    min={0}
                    step={1_000_000}
                    data-testid="pdc-aviso-umbral"
                    value={umbral}
                    onChange={(e) => {
                      const n = Number(e.target.value)
                      const v = Number.isFinite(n) && n >= 0 ? n : 0
                      setUmbral(v)
                      guardarUmbral(arbol.version.id, v)
                    }}
                  />
                </label>{' '}
                <span className="pdc-ayuda">
                  de {arbol.avisos.partidasGlobales.candidatos.length} candidatos con unidad global
                </span>
                <table className="pdc-avisos-tabla">
                  <thead><tr><th>Código</th><th>Actividad</th><th>Und</th><th>Insumos</th><th>Valor</th></tr></thead>
                  <tbody>
                    {globales.map((g) => (
                      <tr key={g.codigo}>
                        <td>{g.codigo}</td><td>{g.descripcion}</td><td>{g.unidad}</td>
                        <td className="pdc-num">{g.insumos}</td><td className="pdc-num">{moneda(g.valorTotal)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </details>
            </div>
          )}
```
Añadir `moneda` al import de `../lib/agGrid` (ya se importa `columnaMoneda`; comprobar si `moneda` está en ese import y añadirlo si falta).

- [ ] **Step 6: Estilos**

En `pdc-app/src/styles.css`:

```css
/* Barra de avisos del visor: señala, no bloquea. Se pliega para no robarle alto a la grilla. */
.pdc-avisos > details { margin-block-start: var(--pdc-espacio-2, .5rem); }
.pdc-avisos > details > summary { cursor: pointer; }
.pdc-avisos-tabla { width: 100%; border-collapse: collapse; }
.pdc-avisos-tabla th, .pdc-avisos-tabla td { text-align: left; padding: .25rem .5rem; }
.pdc-avisos-tabla .pdc-num { text-align: right; font-variant-numeric: tabular-nums; }
```
Reusar la variable de espacio que exista en el archivo. **Ningún color literal.**

- [ ] **Step 7: Suite de Vitest y build**

```bash
cd pdc-app && npm test && npm run build
```
Expected: todo verde, `tsc` limpio, bundle generado.

---

### Task 6: Cifras honestas en toda la app

Cada número de insumos de la app tiene que decir cuál de las dos cosas cuenta, **con la misma palabra siempre**. Las dos palabras viven en un solo sitio para que no se separen con el uso.

**Files:**
- Modify: `pdc-app/src/lib/texto.ts`, `pdc-app/src/lib/texto.test.ts`
- Modify: `pdc-app/src/pages/ImportarPresupuesto.tsx:60,266`
- Modify: `pdc-app/src/pages/PaquetesContratacion.tsx:309,504,530`
- Modify: `pdc-app/src/pages/VisorPresupuesto.tsx` (ya cubierto en Task 5, Step 5.3)
- Modify: `tests/browser/pdc-v2-import.spec.mjs:25`

**Interfaces:**
- Produces:
  - `export type MagnitudInsumos = 'apariciones' | 'distintos'`
  - `export const PALABRA_INSUMOS: Record<MagnitudInsumos, string>` — `{ apariciones: 'apariciones en APU', distintos: 'insumos distintos' }`
  - `export function contarInsumos(n: number, magnitud: MagnitudInsumos): string`

- [ ] **Step 1: Escribir los tests que fallan**

Añadir a `pdc-app/src/lib/texto.test.ts`:

```ts
import { contarInsumos, PALABRA_INSUMOS } from './texto'

describe('contarInsumos', () => {
  it('las dos magnitudes tienen una sola palabra cada una', () => {
    expect(PALABRA_INSUMOS.apariciones).toBe('apariciones en APU')
    expect(PALABRA_INSUMOS.distintos).toBe('insumos distintos')
  })

  it('el 820 y el 396 pueden convivir sin parecer un error', () => {
    expect(contarInsumos(820, 'apariciones')).toBe('820 apariciones en APU')
    expect(contarInsumos(396, 'distintos')).toBe('396 insumos distintos')
  })

  it('en singular no dice «1 insumos distintos»', () => {
    expect(contarInsumos(1, 'distintos')).toBe('1 insumo distinto')
    expect(contarInsumos(1, 'apariciones')).toBe('1 aparición en APU')
  })

  it('el cero va en plural', () => {
    expect(contarInsumos(0, 'distintos')).toBe('0 insumos distintos')
  })

  it('los miles se separan como se leen en español', () => {
    expect(contarInsumos(1343, 'apariciones')).toBe('1.343 apariciones en APU')
  })
})
```
(el `describe`/`it`/`expect` ya están importados en ese archivo; comprobarlo y no duplicar el import).

- [ ] **Step 2: Correr y verificar que falla**

```bash
cd pdc-app && npx vitest run src/lib/texto.test.ts
```
Expected: FAIL — `contarInsumos is not exported`.

- [ ] **Step 3: Implementar**

Añadir a `pdc-app/src/lib/texto.ts`:

```ts
/**
 * Las dos magnitudes de insumos que el módulo cuenta, y sus dos palabras.
 *
 * En el comité del 2026-07-29 el visor anunció 820 insumos, el dueño del producto esperaba ~390, y
 * explicar la diferencia tomó tres turnos de conversación; más adelante apareció un 396 en otra
 * pantalla. Los tres números eran verdaderos: ninguno decía de qué hablaba. Aquí viven las dos
 * palabras, en un solo sitio, para que no se separen con el uso.
 */
export type MagnitudInsumos = 'apariciones' | 'distintos'

export const PALABRA_INSUMOS: Record<MagnitudInsumos, string> = {
  apariciones: 'apariciones en APU',
  distintos: 'insumos distintos',
}

const PALABRA_INSUMOS_SINGULAR: Record<MagnitudInsumos, string> = {
  apariciones: 'aparición en APU',
  distintos: 'insumo distinto',
}

/** «820 apariciones en APU» · «396 insumos distintos». Nunca «820 insumos» a secas. */
export function contarInsumos(n: number, magnitud: MagnitudInsumos): string {
  const palabra = n === 1 ? PALABRA_INSUMOS_SINGULAR[magnitud] : PALABRA_INSUMOS[magnitud]
  return `${n.toLocaleString('es-CO')} ${palabra}`
}
```

- [ ] **Step 4: Correr hasta verde**

```bash
cd pdc-app && npx vitest run src/lib/texto.test.ts
```
Expected: PASS.

- [ ] **Step 5: Recorrer la app y etiquetar cada cifra**

`pdc-app/src/pages/ImportarPresupuesto.tsx`:
- Línea 60, la columna del historial (es `total_insumos`, o sea apariciones):
```tsx
  { ...columnaNumero('totalInsumos', 'Insumos'), colId: 'insumos', headerName: 'Aparic. APU', headerTooltip: 'Apariciones en APU: un mismo insumo cuenta una vez por cada actividad que lo usa. No es el número de insumos distintos.' },
```
- Línea 266, el renglón de la previsualización:
```tsx
            {r.actividades} actividades · {contarInsumos(r.insumos, 'apariciones')} · Costo total {moneda(r.costoTotal)}
```
  (quitar el « {r.insumos} insumos ·» anterior) y añadir `contarInsumos` al import de `../lib/texto`.

`pdc-app/src/pages/PaquetesContratacion.tsx`:
- Línea 309, la pestaña: `{ id: 'masivo', etiqueta: 'Insumos distintos', conteo: visibles.length },`
- Línea 504: `<span className="pdc-paq-meta">{contarInsumos(p.insumos, 'distintos')} · {moneda(p.subtotal)}</span>`
- Línea 530 ya dice «insumos distintos»: dejarla, y comprobar que la palabra es idéntica a `PALABRA_INSUMOS.distintos`.
- Añadir `contarInsumos` al import de `../lib/texto`.

- [ ] **Step 6: Barrer que no quede ninguna cifra muda**

```bash
cd pdc-app && grep -rnE "(insumos|insumo)\b" src/pages src/components | grep -vE "tipoInsumo|tipo insumo|InsumoActividad|descripcionNorm|import |apariciones en APU|insumos distintos|Aparic\. APU|contarInsumos|del presupuesto|sin asignar|a este paquete|los insumos del|Asignar insumos|Insumos con destino"
```
Expected: cada línea que quede es o una variable/propiedad (no una cifra en pantalla) o una frase sin número. Si aparece un `{algo} insumos` con número, etiquetarlo. Anotar en la bitácora la lista final revisada.

- [ ] **Step 7: Ajustar el e2e que afirmaba el texto viejo**

En `tests/browser/pdc-v2-import.spec.mjs`, línea 25:
```js
    await expect(resumen).toContainText('4 apariciones en APU');
```

- [ ] **Step 8: Suite completa y build**

```bash
cd pdc-app && npm test && npm run build
cd .. && npx playwright test tests/browser/pdc-v2-import.spec.mjs --workers=1
```
Expected: Vitest verde, build limpio, el e2e del importador en verde contra `http://localhost:8096`. Si el e2e se salta con el aviso del sandbox, comprobar que `docker compose port app 80` devuelve `8096` desde el worktree.

---

### Task 7: Verificación en la app real y bitácora

**Files:**
- Create: `tests/browser/pdc-v2-tamiz.spec.mjs`
- Modify: `.gitignore` (añadir `!tests/browser/pdc-v2-tamiz.spec.mjs` a la allowlist de `tests/browser`)
- Create: `goals/pdc-preparar-b1/evidence/impacto-y-tamiz-validacion.md`
- Modify: `goals/pdc-preparar-b1/estado-olas.md` (fila 2 → `HECHO`, solo si todo lo anterior está verde)
- Modify: `docs/pdc-v2.md` (una entrada sobre los dos entregables y el límite de la agrupación)

- [ ] **Step 1: e2e del tamiz — escribirlo y verlo fallar antes de mirar la pantalla**

Crear `tests/browser/pdc-v2-tamiz.spec.mjs`, con el mismo andamiaje que los otros specs del PDC:

```js
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

usarSandboxPdc();

test('el visor señala sin bloquear, y sus cifras dicen qué cuentan', async ({ page }) => {
  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.goto('/plan-compras#/ensamble/presupuesto', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="pdc-visor-arbol"]')).toBeVisible({ timeout: 20000 });

    // Las dos magnitudes, cada una con su palabra.
    const cifras = page.locator('[data-testid="pdc-visor-cifras"]');
    await expect(cifras).toContainText('insumos distintos');
    await expect(cifras).toContainText('apariciones en APU');

    // Los tres avisos existen y se pueden desplegar.
    for (const id of ['pdc-aviso-sin-cantidad', 'pdc-aviso-en-cero', 'pdc-aviso-globales']) {
      await expect(page.locator(`[data-testid="${id}"]`)).toBeVisible();
    }

    // No bloquean: el árbol se sirve y se puede seguir navegando a paquetes.
    await expect(page.locator('[data-testid="pdc-visor-arbol"] .ag-row').first()).toBeVisible();
    await page.goto('/plan-compras#/ensamble/paquetes', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toBeVisible({ timeout: 20000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```
Comprobar las rutas del hash (`#/ensamble/presupuesto`, `#/ensamble/paquetes`) contra `pdc-app/src/lib/navegacion.ts` y corregirlas si difieren; no inventarlas.

Añadir a `.gitignore`, junto a las demás excepciones de `tests/browser`:
```
!tests/browser/pdc-v2-tamiz.spec.mjs
```
y verificar que queda seguido:
```bash
git check-ignore -v tests/browser/pdc-v2-tamiz.spec.mjs; echo "rc=$? (1 = NO ignorado, que es lo que queremos)"
```

- [ ] **Step 2: Correr los e2e del PDC afectados**

```bash
npx playwright test tests/browser/pdc-v2-tamiz.spec.mjs tests/browser/pdc-v2-import.spec.mjs tests/browser/pdc-v2-versionado.spec.mjs tests/browser/pdc-v2-comparar.spec.mjs --workers=1 --reporter=line
```
Expected: todos en verde. Un `skipped` con el aviso del sandbox **no** cuenta como verde: arreglar el entorno y volver a correr.

- [ ] **Step 3: Verificar en el navegador, contra Da Porto, a 1180×820 y en dark**

Abrir el navegador integrado sobre `http://localhost:8096`, entrar con un rol con `lps.pdc.importar`, seleccionar **Da Porto (proyecto 73)** y:

1. `/plan-compras#/ensamble/presupuesto` — comprobar en pantalla: `396 insumos distintos · 820 apariciones en APU`; el aviso de actividades sin cantidad dice **47** y arrastra **102**; el de insumos en cero dice **10**; el de partidas globales, con el umbral por defecto de **$73.000.000**, dice **17**, y su lista abre con IMPREVISTOS OBRA y RED CONTRA INCENDIO TODO COSTO. Cambiar el umbral a `0` → 57; a `300000000` → 3. Recargar la página y comprobar que el umbral escrito sobrevive.
2. Con avisos abiertos, ir a paquetes y **asignar un insumo**; volver al plan y **recalcular**. Ninguna de las dos cosas debe quedar impedida. Esto es la condición de hecho 3 del tamiz, y se demuestra haciéndolo, no razonándolo.
3. Revisar la consola y la red: cero errores, ninguna petición en rojo. Guardar captura del visor con los tres avisos desplegados en `goals/pdc-preparar-b1/evidence/`.

Registrar los valores vistos. Si alguno no coincide con los seis números del Task 4 Step 5, es un defecto: pararse y arreglarlo.

- [ ] **Step 4: Verificar el impacto con la versión «clase 0» construida a mano**

El clase 0 de Da Porto **todavía no existe**. Construir una versión candidata a partir del clase 1 y decir en la bitácora que es una prueba del mecanismo, no del caso real:

```bash
docker compose exec -T app php -r "require '/var/www/html/vendor/autoload.php'; require '/var/www/html/src/Core/Database.php';
\$db = Database::getInstance();
// Asignaciones vivas de Da Porto, para que el impacto tenga sobre qué informar.
\$n = (int) \$db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = 73 AND paquete_id IS NOT NULL')->fetchColumn();
echo \"asignaciones con paquete en Da Porto: \$n\n\";"
```

Luego, en la pantalla de importación de Da Porto, subir un Excel derivado del clase 1 con tres cambios deliberados —quitar un insumo que **sí** tenga paquete, añadir uno nuevo, y cambiarle el tipo de insumo a un tercero— y comprobar en el bloque de impacto que las tres cifras nombran exactamente esos tres y que el valor afectado es su suma. **Cancelar sin confirmar** y comprobar en la base que la versión activa sigue siendo la 376 y que las asignaciones no se movieron:

```bash
docker compose exec -T db mysql -uroot -p"$DB_PASS" -N -e "
SELECT id, activa FROM lastplanneraia_dev.pdc_presupuesto_versiones WHERE project_id = 73;
SELECT COUNT(*) FROM lastplanneraia_dev.pdc_insumo_paquete WHERE project_id = 73;"
```
Expected: `376 1` y el mismo conteo de asignaciones que antes de la prueba.

- [ ] **Step 5: Cerrar con `verification-before-completion`**

REQUIRED SUB-SKILL: `superpowers:verification-before-completion`. Correr, y pegar la salida real, de:

```bash
docker compose exec -T app php tests/test_pdc_v2_impacto_reimport.php
docker compose exec -T app php tests/test_pdc_v2_tamiz_presupuesto.php
docker compose exec -T app php tests/test_pdc_v2_comparar.php
docker compose exec -T app php tests/test_pdc_v2_arbol.php
docker compose exec -T app php tests/test_pdc_v2_import_flujo.php
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
cd pdc-app && npm test && npm run build && cd ..
npx playwright test tests/browser/pdc-v2-tamiz.spec.mjs tests/browser/pdc-v2-import.spec.mjs --workers=1 --reporter=line
```
Ningún «hecho» se escribe sin la salida de estos comandos en esta sesión.

- [ ] **Step 6: Bitácora**

Crear `goals/pdc-preparar-b1/evidence/impacto-y-tamiz-validacion.md` con: los seis números medidos contra Da Porto y su método; **el listado de las 17 partidas globales y el umbral aceptado**, con las excepciones legítimas señaladas (IMPREVISTOS ×2, PRESUPUESTO AMBIENTAL); la decisión de partir el aviso de vacíos en dos y por qué el «112» se descartó; **el límite de la agrupación SINCO** (no se persiste, la tercera cifra es cambio de tipo de insumo, arreglarlo exigiría una migración que el spec excluye); y, textualmente, que **la verificación del caso «clase 0» se hizo con una versión construida a mano a partir del clase 1 — es una prueba del mecanismo, no del caso real, que se comprobará cuando llegue el presupuesto de verdad**. Cerrar con los comandos corridos y su resultado.

Añadir a `docs/pdc-v2.md` una entrada corta en la sección de fases: el impacto viaja en el `preview` y los avisos en `arbol()`, sin endpoint ni migración; el umbral del globalazo lo pone el usuario en el visor y se persiste en `localStorage`.

- [ ] **Step 7: Tablero de relevos**

Solo si el Step 5 salió entero en verde, en `goals/pdc-preparar-b1/estado-olas.md` cambiar la fila 2 a `HECHO` con el sha y la fecha. La regla del propio tablero: nadie se marca `HECHO` sin haber corrido su verificación, y una fila mentida hace arrancar a la siguiente sobre un supuesto falso.

- [ ] **Step 8: Pedir autorización para publicar**

No hacer commit, push ni deploy. Presentar al usuario el resumen de lo verificado y pedir autorización explícita para commitear, indicando qué archivos entran y que el `.env` y la evidencia local quedan fuera.

---

## Self-Review

**Cobertura del spec del impacto:** las cuatro cifras (Task 2) · detalle desplegable con paquete actual (Task 3) · texto de confirmación en palabras (Task 3, `textoConserva`) · «no entra: reglas automáticas de reagrupación» (constraint global + test «nunca promete reagrupar solo») · reuso del comparativo de A1.6 sin consulta nueva (Task 1) · sin migraciones (constraint) · las seis condiciones de hecho (Task 2 Steps 1/4 para 1-5, Task 2 Step 5 + Task 7 Step 2 para la 6) · riesgo del ruido de agrupación (medido: se implementa como cambio de tipo, documentado como límite) · riesgo del clase 0 inexistente (Task 7 Step 4, con la frase exigida en la bitácora).

**Cobertura del spec del tamiz:** aviso de insumo vacío (Task 4, partido en dos por decisión del usuario, con la aritmética 102+10=112 verificada) · aviso de partida global con umbral configurable y por defecto medido, no inventado (Tasks 4-5) · ambos desplegables a lista con capítulo/actividad/insumo/valor (Task 5 Step 5) · no bloquean (constraint + Task 4 test + Task 7 Step 3.2 demostrado en la app) · cifras honestas en toda la app con la misma palabra siempre (Task 6) · cálculo en el servicio del visor sin endpoint aparte (Task 4) · las cinco condiciones de hecho (Task 4 Step 5, Task 7 Steps 2-3, Task 6 Step 6, Task 7 Step 5).

**Consistencia de tipos:** `consolidarInsumos()` devuelve `apariciones` (nueva) y la consumen Task 4 (`insumosDistintos`) y el comparativo sin usarla — compatible. `ImpactoReimport` en TS espeja el `@return` de `impactoDeReimportar()` clave por clave. `AvisosPresupuesto` espeja el de `avisosDelPresupuesto()`. `contarInsumos(n, magnitud)` se llama con la misma firma en las tres pantallas. `partidasSobreUmbral` recibe `CandidatoGlobal[]`, que es exactamente lo que devuelve `avisos.partidasGlobales.candidatos`.

**Sin marcadores de posición:** todos los pasos de código traen el código; los dos únicos «comprobar antes» (`plural()` en Task 3 Step 3, las rutas del hash en Task 7 Step 1) son verificaciones contra el código existente con instrucción explícita de no inventar, no trabajo diferido.
