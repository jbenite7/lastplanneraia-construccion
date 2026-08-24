---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-19-linea-base-contractual.md
resumen: que la fecha contractual del cronograma salga de la línea base declarada del proyecto y deje de borrarse cuando una reprogramación cambia las actividades.
---

# Línea base contractual — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usa `superpowers:subagent-driven-development`
> (recomendada) o `superpowers:executing-plans` para implementar tarea por tarea. Los pasos usan
> casillas (`- [ ]`) para el seguimiento.

**Spec:** `docs/superpowers/specs/2026-08-19-linea-base-contractual-design.md`

**Goal:** que la fecha contractual del cronograma salga de la línea base **declarada** del proyecto y
deje de borrarse cuando una reprogramación cambia las actividades.

**Arquitectura:** un servicio nuevo y pequeño (`LineaBaseContractualService`) es el único que sabe
leer y sembrar la línea base declarada. `ControlTowerService` lo consume en vez de deducirla de la
cohorte vigente. El camino que consolida una semana lo invoca con una línea, y un script de migración
cubre de una vez los proyectos ya cargados.

**Cómo se corren las pruebas en este frente, y por qué NO con `docker compose exec`:** el contenedor
`app` es compartido por cuatro sesiones y monta la raíz del repo, no este worktree — `exec` correría
el código de otra rama. Se usa un contenedor efímero que monta este árbol y alcanza la misma base:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/<archivo>.php
```

Comprobado el 2026-08-19: alcanza `general_proyectos_procesos` y sirve este worktree. No hace falta
reapuntar el contenedor compartido para nada de las Tareas 1-6; solo `publicar.sh`, en la Tarea 7,
exige el montaje y se coordina entonces.

**Stack:** PHP 8.3 sin framework, PDO por `Database` (singleton, `\Database::getInstance()`),
PSR-4 `App\ -> src/`. Pruebas: scripts sueltos `tests/test_*.php` que declaran su nivel con
`// @requiere: <nivel>`, ejecutados por `scripts/run-php-tests.php`. Todo dentro de Docker.

## Restricciones globales

Copiadas de la spec. **Los requisitos de toda tarea las incluyen implícitamente.**

- **No tocar** `database/fixtures/design-system-ci.sql` ni `tests/test_bi_programa_general_chart_values.php`.
  Son el caso que debe funcionar; si el arreglo es correcto se ponen verdes solos.
- **No regenerar** ningún baseline ni golden.
- **No modernizar** `src/Legacy/`: allí solo entra la línea que invoca el sembrado.
- **No ampliar** a otros gráficos del BI aunque compartan el filtro por cohorte.
- **Sin dependencias nuevas.**
- Toda escritura en base de datos va por sentencias preparadas a través de `Database`.
- Toda consulta operativa se aísla por `project_id`.
- **Cada aserción se entrega con la mutación que la pone roja, ejecutada**, y se pega la salida.
- **Toda prueba que escriba en la base GUARDA el valor original y lo RESTAURA al terminar**, también
  si falla. La base de desarrollo es compartida con otras sesiones: dejarla alterada les fabrica
  rojos y verdes ajenos. Se usa `register_shutdown_function` para que la restauración ocurra aunque
  la prueba muera a mitad.

---

### Tarea 1: el servicio que lee y siembra la línea base

**Archivos:**
- Crear: `src/Services/LineaBaseContractualService.php`
- Crear: `tests/test_linea_base_contractual_service.php`

**Interfaces:**
- Consume: `\Database::getInstance()` (clase global, sin namespace), y las tablas globales
  `general_proyectos_procesos` y `programa_consolidado`, ambas aisladas por `project_id`.
- Produce, y las tareas 2, 4 y 5 dependen de estos nombres exactos:
  - `App\Services\LineaBaseContractualService::__construct(?\Database $db = null)`
  - `declaradaDe(int $projectId): ?array` → `['inicio' => 'Y-m-d', 'fin' => 'Y-m-d']` o `null`
  - `deducidaDelPrimerCorte(int $projectId): ?array` → mismo formato o `null`
  - `sembrarSiFalta(int $projectId): bool` → `true` solo si escribió

- [ ] **Paso 1: escribir la prueba que falla**

Crear `tests/test_linea_base_contractual_service.php`:

```php
<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\LineaBaseContractualService;

$fallos = [];
$svc = new LineaBaseContractualService();
$db = \Database::getInstance();

// La base de desarrollo es COMPARTIDA con otras sesiones. Se guarda el estado del proyecto 68 y se
// restaura pase lo que pase — incluso si la prueba muere a mitad.
$original = $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC) ?: ['inicio' => null, 'fin' => null];

register_shutdown_function(static function () use ($db, $original): void {
    $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    );
});

// 1. Un proyecto con línea base declarada la devuelve tal cual.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2020-01-01', fechaFinLineaBase = '2020-12-31'
            WHERE Id = 68");
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01' || ($lb['fin'] ?? null) !== '2020-12-31') {
    $fallos[] = 'declaradaDe no devuelve las fechas declaradas';
}

// 2. sembrarSiFalta NO sobrescribe una línea base existente.
if ($svc->sembrarSiFalta(68) !== false) {
    $fallos[] = 'sembrarSiFalta sobrescribió una línea base ya declarada';
}
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01') {
    $fallos[] = 'sembrarSiFalta pisó la fecha declarada';
}

// 3. Sin línea base declarada, declaradaDe devuelve null y sembrarSiFalta escribe.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68");
if ($svc->declaradaDe(68) !== null) {
    $fallos[] = 'declaradaDe debería devolver null sin fechas declaradas';
}
$deducida = $svc->deducidaDelPrimerCorte(68);
if ($deducida === null) {
    $fallos[] = 'deducidaDelPrimerCorte no encontró el primer corte del proyecto 68';
}
if ($svc->sembrarSiFalta(68) !== true) {
    $fallos[] = 'sembrarSiFalta no escribió cuando faltaba la línea base';
}
if ($svc->declaradaDe(68) != $deducida) {
    $fallos[] = 'lo sembrado no coincide con lo deducido del primer corte';
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: linea base contractual — declarada, deducida y sembrado write-once\n";
```

- [ ] **Paso 2: correrla y ver que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_contractual_service.php
```

Esperado: FAIL con `Class "App\Services\LineaBaseContractualService" not found`.

- [ ] **Paso 3: implementar el servicio**

Crear `src/Services/LineaBaseContractualService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

/**
 * La línea base contractual de un proyecto: la fecha contra la que se mide toda desviación.
 *
 * Por qué existe este servicio y no se sigue deduciendo al vuelo: hasta el 2026-08-19 la fecha
 * contractual del cronograma se derivaba del primer corte del programa y luego se cruzaba con las
 * actividades de la semana consultada. Al reprogramar y cambiar actividades, esa intersección
 * quedaba vacía y la fecha DESAPARECÍA — justo cuando más falta hace. La línea base es el patrón,
 * no un derivado de lo vigente.
 *
 * Es la misma fuente que ya consume el PDC (`Pdc\FlujoCajaService`), a propósito: cronograma y
 * presupuesto tienen que medir contra el mismo dato.
 */
final class LineaBaseContractualService
{
    private \Database $db;

    public function __construct(?\Database $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    /** @return array{inicio: string, fin: string}|null */
    public function declaradaDe(int $projectId): ?array
    {
        $fila = $this->db->query(
            'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
               FROM general_proyectos_procesos WHERE Id = ?',
            [$projectId],
        )->fetch(\PDO::FETCH_ASSOC);

        if ($fila === false || empty($fila['inicio']) || empty($fila['fin'])) {
            return null;
        }

        return ['inicio' => (string) $fila['inicio'], 'fin' => (string) $fila['fin']];
    }

    /**
     * La línea base que se deduciría del PRIMER corte registrado del programa.
     *
     * Solo se usa para sembrar lo que nadie declaró. No es equivalente a la contractual: es «cuándo
     * empezamos a registrar», no «qué se prometió». Por eso nunca pisa una declarada.
     *
     * @return array{inicio: string, fin: string}|null
     */
    public function deducidaDelPrimerCorte(int $projectId): ?array
    {
        // `programa_consolidado` es TABLA GLOBAL, aislada por `project_id`. No pasa por
        // TableResolver a propósito: con tablas globales ese resolutor devuelve el mismo nombre, y
        // nombrarla directa evita SQL dinámico nuevo, que es lo que AGENTS.md prohíbe.
        $primera = $this->db->query(
            'SELECT MIN(Semana) FROM programa_consolidado WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();

        if ($primera === false || $primera === null) {
            return null;
        }

        $fila = $this->db->query(
            'SELECT MIN(Fecha_Inicio) AS inicio, MAX(Fecha_Fin) AS fin
               FROM programa_consolidado
              WHERE project_id = ? AND Semana = ?
                AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL',
            [$projectId, $primera],
        )->fetch(\PDO::FETCH_ASSOC);

        if ($fila === false || empty($fila['inicio']) || empty($fila['fin'])) {
            return null;
        }

        return ['inicio' => (string) $fila['inicio'], 'fin' => (string) $fila['fin']];
    }

    /**
     * Escribe la línea base deducida SOLO si el proyecto no tiene una declarada.
     *
     * Write-once por diseño: si alguien la corrigió a mano, manda la suya. Sin esa regla, cada
     * consolidación de semana reescribiría el patrón contra el que se mide, que es exactamente el
     * defecto que este servicio viene a cerrar.
     */
    public function sembrarSiFalta(int $projectId): bool
    {
        if ($this->declaradaDe($projectId) !== null) {
            return false;
        }

        $deducida = $this->deducidaDelPrimerCorte($projectId);
        if ($deducida === null) {
            return false;
        }

        $this->db->query(
            'UPDATE general_proyectos_procesos
                SET fechaInicioLineaBase = ?, fechaFinLineaBase = ?
              WHERE Id = ?
                AND (fechaInicioLineaBase IS NULL OR fechaFinLineaBase IS NULL)',
            [$deducida['inicio'], $deducida['fin'], $projectId],
        );

        return true;
    }
}
```

- [ ] **Paso 4: correrla y ver que pasa**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_contractual_service.php
```

Esperado: `OK: linea base contractual — declarada, deducida y sembrado write-once`

- [ ] **Paso 5: la mutación que la pone roja, ejecutada**

Quitar la guarda de `sembrarSiFalta` (cambiar `if ($this->declaradaDe($projectId) !== null)` por
`if (false)`), correr la prueba, y comprobar que falla con
`sembrarSiFalta sobrescribió una línea base ya declarada`. **Deshacer la mutación** y volver a correr.

Pegar las dos salidas en el `goal.md`. Si con la mutación puesta la prueba sigue verde, la aserción
no vigila lo que dice vigilar y hay que rehacerla antes de seguir.

- [ ] **Paso 6: commit**

```bash
git add src/Services/LineaBaseContractualService.php tests/test_linea_base_contractual_service.php
git commit -m "feat(cronograma): la linea base contractual tiene servicio propio, write-once"
```

---

### Tarea 2: el cronograma lee la línea base declarada

Es el arreglo del defecto. Ojo con el orden: esta tarea sola ya debería poner verde
`test_bi_programa_general_chart_values.php` **sin tocarlo**.

**Archivos:**
- Modificar: `src/Services/ControlTowerService.php:202`, `:965`, `:1867-1894`, `:1896-1900`
- Crear: `tests/test_linea_base_sobrevive_reprogramacion.php`

**Interfaces:**
- Consume de la Tarea 1: `LineaBaseContractualService::declaradaDe(int): ?array`
- Produce: `programaProjectForecast()` devuelve `contractual_finish` desde la línea base declarada, y
  `contractual_finish_basis` pasa a valer la cadena `declared_project_baseline`.

- [ ] **Paso 1: escribir la prueba que falla**

Crear `tests/test_linea_base_sobrevive_reprogramacion.php`:

```php
<?php
// @requiere: datos-proyecto
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;
use App\Services\LineaBaseContractualService;

$fallos = [];
$db = \Database::getInstance();
$svc = new LineaBaseContractualService();
$ct = new ControlTowerService();

// Base compartida: se guarda y se restaura (ver restricciones globales).
$original = $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC) ?: ['inicio' => null, 'fin' => null];
register_shutdown_function(static function () use ($db, $original): void {
    $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    );
});

// Proyecto 68: sus actividades de la primera semana y la última NO se solapan.
// Con línea base declarada, la fecha contractual tiene que sobrevivir igual.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2026-06-01', fechaFinLineaBase = '2026-07-19'
            WHERE Id = 68");

$brief = $ct->getBrief('programa-general', [68], '5', 'R', []);
$m = $brief['charts']['programa-dias-retraso']['metrics'] ?? [];

if (($m['contractual_finish'] ?? '') !== '2026-07-19') {
    $fallos[] = 'contractual_finish deberia ser la declarada 2026-07-19, y es: '
        . var_export($m['contractual_finish'] ?? null, true);
}
if (($m['contractual_finish_basis'] ?? '') !== 'declared_project_baseline') {
    $fallos[] = 'contractual_finish_basis deberia declarar la fuente nueva, y dice: '
        . var_export($m['contractual_finish_basis'] ?? null, true);
}

// Filtrar por subcontratista NO cambia la fecha contractual.
$sub = $db->query("SELECT sub_contratista FROM bi_pg_semana
                   WHERE project_id = 68 AND COALESCE(sub_contratista,'') <> '' LIMIT 1")->fetchColumn();
if ($sub !== false && $sub !== null) {
    $filtrado = $ct->getBrief('programa-general', [68], '5', 'R', ['sub_contratista' => [$sub]]);
    $mf = $filtrado['charts']['programa-dias-retraso']['metrics'] ?? [];
    if (($mf['contractual_finish'] ?? '') !== '2026-07-19') {
        $fallos[] = 'filtrar por subcontratista movio la fecha contractual a: '
            . var_export($mf['contractual_finish'] ?? null, true);
    }
}

// Sin línea base declarada NO se inventa ninguna.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68");
$sinLb = $ct->getBrief('programa-general', [68], '5', 'R', []);
$ms = $sinLb['charts']['programa-dias-retraso']['metrics'] ?? [];
if (!empty($ms['contractual_finish'])) {
    $fallos[] = 'sin linea base declarada se invento una fecha: ' . $ms['contractual_finish'];
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: la linea base contractual sobrevive a la reprogramacion y al filtro\n";
```

- [ ] **Paso 2: correrla y ver que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_sobrevive_reprogramacion.php
```

Esperado: FAIL con `contractual_finish deberia ser la declarada 2026-07-19, y es: NULL` — que es el
defecto exacto que este frente cierra.

- [ ] **Paso 3: cambiar la fuente en `programaProjectForecast`**

En `src/Services/ControlTowerService.php`, sustituir en `programaProjectForecast()` (`:1896-1900`):

```php
        $hasFilteredCohort = ($context['baseline'] ?? []) !== [];
        [, $contractualDate] = $this->programaBaselineDates($contractualBaseline);
        $contractualFinish = $contractualDate?->format('Y-m-d');
```

por:

```php
        $hasFilteredCohort = ($context['baseline'] ?? []) !== [];
        // La línea base NO se deduce de lo vigente: se lee de lo declarado. Deducirla y cruzarla con
        // la cohorte de la semana hacía que una reprogramación la borrara —medido el 2026-08-19 en
        // el proyecto 68, con intersección de actividades en cero— justo cuando más falta hace.
        $contractualFinish = $this->lineaBase->declaradaDe($projectId)['fin'] ?? null;
```

Añadir la propiedad y su construcción junto a `$this->db` (`:32`):

```php
    private LineaBaseContractualService $lineaBase;
```

```php
        $this->lineaBase = new LineaBaseContractualService($this->db);
```

Y en el array de retorno (`:1928`), junto a `'contractual_finish' => $contractualFinish,`, dejar la
base declarada explícita:

```php
            'contractual_finish_basis' => 'declared_project_baseline',
```

Hacer lo mismo en el otro punto donde se emite `contractual_finish_basis` (`:1841`).

- [ ] **Paso 4: retirar el cálculo por cohorte si queda huérfano**

```bash
grep -rn "programaContractualBaselineForCurrentCohort" src/
```

Si las únicas apariciones son su definición (`:1867`) y las dos llamadas de `:202` y `:965`, retirar
las tres: el parámetro `$contractualBaseline` de `programaProjectForecast()` y de
`programaDelayForecast()` deja de usarse y sale también. Si aparece en algún otro sitio, **no se
retira**: se deja y se anota el porqué en el `goal.md`.

- [ ] **Paso 5: correr las dos pruebas, la nueva y la que el CI tiene en rojo**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_sobrevive_reprogramacion.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_bi_programa_general_chart_values.php
```

Esperado: la primera en `OK`. La segunda **ya no debe emitir** `FAIL: baseline-drift:` — sin haberla
modificado. Si sigue emitiéndolo, el diagnóstico está mal y **hay que parar y escalar**, no tocar el
test.

- [ ] **Paso 6: la mutación que la pone roja, ejecutada**

Cambiar `declaradaDe($projectId)['fin']` por `declaradaDe($projectId)['inicio']`, correr
`test_linea_base_sobrevive_reprogramacion.php` y comprobar que falla nombrando la fecha equivocada.
Deshacer y volver a correr. Pegar las dos salidas en el `goal.md`.

- [ ] **Paso 7: commit**

```bash
git add src/Services/ControlTowerService.php tests/test_linea_base_sobrevive_reprogramacion.php
git commit -m "fix(bi): la fecha contractual sale de la linea base declarada, no de la cohorte vigente"
```

---

### Tarea 3: el gráfico dice de quién es la fecha

**Archivos:**
- Modificar: `src/Services/ControlTowerService.php` (el payload del gráfico `programa-dias-retraso`)
- Crear: `tests/test_linea_base_rotulo.php`

**Interfaces:**
- Consume de la Tarea 2: `contractual_finish_basis === 'declared_project_baseline'`
- Produce: la clave `contractual_finish_scope` con el valor `proyecto` en las métricas del gráfico.

- [ ] **Paso 1: escribir la prueba que falla**

```php
<?php
// @requiere: datos-proyecto
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$fallos = [];
$db = \Database::getInstance();

// Base compartida: se guarda y se restaura (ver restricciones globales).
$original = $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC) ?: ['inicio' => null, 'fin' => null];
register_shutdown_function(static function () use ($db, $original): void {
    $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    );
});

$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase='2026-06-01', fechaFinLineaBase='2026-07-19' WHERE Id=68");

$ct = new ControlTowerService();
$m = $ct->getBrief('programa-general', [68], '5', 'R', [])['charts']['programa-dias-retraso']['metrics'] ?? [];
if (($m['contractual_finish_scope'] ?? '') !== 'proyecto') {
    $fallos[] = 'falta contractual_finish_scope=proyecto en las metricas';
}

if ($fallos) { foreach ($fallos as $f) { echo "FAIL: $f\n"; } exit(1); }
echo "OK: el grafico declara que la fecha contractual es del proyecto\n";
```

- [ ] **Paso 2: correrla y ver que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_rotulo.php
```

Esperado: FAIL con `falta contractual_finish_scope=proyecto en las metricas`.

- [ ] **Paso 3: añadir la clave**

Junto a `'contractual_finish_basis' => 'declared_project_baseline',`, en los dos sitios:

```php
            // La fecha es SIEMPRE del proyecto, también con filtros puestos: no existe una línea
            // base por subcontratista. Se rotula para que nadie lea el número como el compromiso de
            // quien está filtrado.
            'contractual_finish_scope' => 'proyecto',
```

- [ ] **Paso 4: correr y ver que pasa**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_rotulo.php
```

Esperado: `OK: el grafico declara que la fecha contractual es del proyecto`

- [ ] **Paso 5: mostrar el rótulo en la vista**

Localizar la plantilla del gráfico:

```bash
grep -rn "programa-dias-retraso" views/ public/js/ | head
```

Añadir junto a la fecha contractual el texto `Línea base del proyecto`. **Sin hex ni estilos en
línea**: usar los tokens y primitivas `aia-*` que manda `DESIGN.md`. Validar en dark a 1180×820.

- [ ] **Paso 6: commit**

```bash
git add src/Services/ControlTowerService.php tests/test_linea_base_rotulo.php views/ public/js/
git commit -m "feat(bi): el grafico declara que la fecha contractual es del proyecto, no del filtro"
```

---

### Tarea 4: sembrar al consolidar la primera semana

**Archivos:**
- Modificar: `src/Legacy/nueva_semana.php:~193` (después de `$carryoverService->syncWeek(...)`)
- Crear: `tests/test_linea_base_sembrado_al_consolidar.php`

**Interfaces:**
- Consume de la Tarea 1: `LineaBaseContractualService::sembrarSiFalta(int): bool`

- [ ] **Paso 1: escribir la prueba que falla**

```php
<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

$fuente = file_get_contents(__DIR__ . '/../src/Legacy/nueva_semana.php');
$fallos = [];

if (!str_contains($fuente, 'LineaBaseContractualService')) {
    $fallos[] = 'nueva_semana.php no invoca el sembrado de la linea base';
}
if (!str_contains($fuente, 'sembrarSiFalta')) {
    $fallos[] = 'nueva_semana.php no llama a sembrarSiFalta';
}
// El legado se toca lo minimo: una sola invocacion, no logica.
if (substr_count($fuente, 'sembrarSiFalta') > 1) {
    $fallos[] = 'el sembrado aparece mas de una vez: la logica debe vivir en el servicio';
}

if ($fallos) { foreach ($fallos as $f) { echo "FAIL: $f\n"; } exit(1); }
echo "OK: la consolidacion de semana siembra la linea base\n";
```

- [ ] **Paso 2: correrla y ver que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_sembrado_al_consolidar.php
```

Esperado: FAIL con `nueva_semana.php no invoca el sembrado de la linea base`.

- [ ] **Paso 3: añadir la invocación**

En `src/Legacy/nueva_semana.php`, justo después de `$carryoverService->syncWeek($db, $conteo, $semana_crear);`:

```php
            // La línea base contractual se siembra sola la primera vez que un proyecto tiene
            // programa. Write-once: si ya está declarada, esto no hace nada. La lógica vive en el
            // servicio, no aquí — `src/Legacy/` es mantenimiento (AGENTS.md).
            (new \App\Services\LineaBaseContractualService($dbInstance))->sembrarSiFalta((int) $projectId);
```

- [ ] **Paso 4: correr y ver que pasa**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_sembrado_al_consolidar.php
```

Esperado: `OK: la consolidacion de semana siembra la linea base`

- [ ] **Paso 5: la mutación que la pone roja, ejecutada**

Comentar la línea añadida, correr la prueba, comprobar el FAIL, descomentar y volver a correr. Pegar
ambas salidas en el `goal.md`.

- [ ] **Paso 6: commit**

```bash
git add src/Legacy/nueva_semana.php tests/test_linea_base_sembrado_al_consolidar.php
git commit -m "feat(lps): consolidar la primera semana siembra la linea base contractual"
```

---

### Tarea 5: migración de una vez para lo ya cargado

**Archivos:**
- Crear: `scripts/sembrar-linea-base-contractual.php`
- Crear: `tests/test_linea_base_migracion_dry_run.php`

**Interfaces:**
- Consume de la Tarea 1: `declaradaDe()`, `deducidaDelPrimerCorte()`, `sembrarSiFalta()`
- Produce: script con dos modos, `--dry-run` (por defecto) y `--aplicar`.

**Contexto medido el 2026-08-19** (base de desarrollo): 49 proyectos, 15 con cronograma, y **3 con
cronograma y sin línea base**: `68 Optimización Aeropuerto JMC`,
`69 Metrolinea Confinamiento Estación 2`, `77 Preconstrucción Equipamiento Milán Campestre`.

- [ ] **Paso 1: escribir la prueba que falla**

```php
<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

$fallos = [];
$salida = shell_exec('php ' . escapeshellarg(__DIR__ . '/../scripts/sembrar-linea-base-contractual.php') . ' 2>&1');

if (!is_string($salida) || !str_contains($salida, 'DRY-RUN')) {
    $fallos[] = 'sin --aplicar el script debe anunciarse como DRY-RUN. Salida: ' . var_export($salida, true);
}
if (is_string($salida) && str_contains($salida, 'ESCRITO')) {
    $fallos[] = 'el dry-run escribio, y no debe escribir nada';
}

if ($fallos) { foreach ($fallos as $f) { echo "FAIL: $f\n"; } exit(1); }
echo "OK: la migracion en seco no escribe y enumera lo que tocaria\n";
```

- [ ] **Paso 2: correrla y ver que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_migracion_dry_run.php
```

Esperado: FAIL, porque el script todavía no existe.

- [ ] **Paso 3: escribir el script**

Crear `scripts/sembrar-linea-base-contractual.php`:

```php
<?php

declare(strict_types=1);

/**
 * Siembra la línea base contractual de los proyectos que YA tienen cronograma y no la declararon.
 *
 * El sembrado automático (`nueva_semana.php`) solo alcanza a quien consolide una semana a partir de
 * ahora. Estos proyectos no van a volver a subir su programa, así que sin este paso se quedarían sin
 * fecha contractual para siempre.
 *
 * En seco por defecto. Escribe solo con --aplicar, y NUNCA pisa una línea base declarada: usa
 * `sembrarSiFalta`, que es write-once.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\LineaBaseContractualService;

$aplicar = in_array('--aplicar', $argv, true);
echo $aplicar ? "MODO: APLICAR\n" : "MODO: DRY-RUN (no escribe nada; usa --aplicar para escribir)\n";

$db = \Database::getInstance();
$svc = new LineaBaseContractualService($db);

$proyectos = $db->query(
    'SELECT p.Id, p.Proyecto_Proceso
       FROM general_proyectos_procesos p
      WHERE (p.fechaInicioLineaBase IS NULL OR p.fechaFinLineaBase IS NULL)
        AND p.Id IN (SELECT DISTINCT project_id FROM bi_pg_semana)
      ORDER BY p.Id',
)->fetchAll(\PDO::FETCH_ASSOC);

$tocados = 0;
$sinDatos = 0;
foreach ($proyectos as $p) {
    $projectId = (int) $p['Id'];
    $deducida = $svc->deducidaDelPrimerCorte($projectId);

    if ($deducida === null) {
        echo "  OMITIDO {$projectId} {$p['Proyecto_Proceso']}: su primer corte no tiene fechas\n";
        $sinDatos++;
        continue;
    }

    if (!$aplicar) {
        echo "  TOCARIA {$projectId} {$p['Proyecto_Proceso']}: {$deducida['inicio']} -> {$deducida['fin']}\n";
        $tocados++;
        continue;
    }

    if ($svc->sembrarSiFalta($projectId)) {
        echo "  ESCRITO {$projectId} {$p['Proyecto_Proceso']}: {$deducida['inicio']} -> {$deducida['fin']}\n";
        $tocados++;
    }
}

echo "\nResumen: " . count($proyectos) . " candidatos, {$tocados} "
    . ($aplicar ? 'escritos' : 'a escribir') . ", {$sinDatos} omitidos por falta de fechas.\n";
```

- [ ] **Paso 4: correr la prueba y el dry-run**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_linea_base_migracion_dry_run.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/sembrar-linea-base-contractual.php
```

Esperado: la prueba en `OK`, y el dry-run enumerando los proyectos 68, 69 y 77 con sus fechas.
**Pegar esa salida en el `goal.md` antes de aplicar nada.**

- [ ] **Paso 5: respaldo verificable — y PARAR AQUÍ**

**El subagente NO aplica la migración.** Decisión de Felipe del 2026-08-19: el momento de escribir en
datos lo elige él, con la salida del dry-run delante. El subagente deja el respaldo hecho y
verificado, pega la salida del dry-run en su informe, y **termina la tarea ahí**.

```bash
docker compose exec db mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" general_proyectos_procesos \
  > respaldo-general_proyectos_procesos-$(date +%Y%m%d-%H%M).sql
```

Comprobar que el respaldo no está vacío y que contiene `INSERT INTO`. **Sin respaldo verificado no se
aplica** (`AGENTS.md` §Arquitectura y datos).

- [ ] **Paso 6: aplicar — SOLO con autorización explícita de Felipe, y lo hace el controlador**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/sembrar-linea-base-contractual.php --aplicar
```

- [ ] **Paso 7: verificar después de aplicar**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/sembrar-linea-base-contractual.php
```

Esperado: `0 a escribir` — no queda ningún proyecto con cronograma y sin línea base. Pegar la salida.

- [ ] **Paso 8: commit**

```bash
git add scripts/sembrar-linea-base-contractual.php tests/test_linea_base_migracion_dry_run.php
git commit -m "chore(datos): siembra la linea base de los proyectos ya cargados, en seco por defecto"
```

---

### Tarea 6: auditoría del PDC, con evidencia

Felipe extendió el principio al presupuesto. La coordinadora ratificó que esto entra **como auditoría
con evidencia, no como `grep`**.

**Archivos:**
- Crear: `docs/superpowers/evidencia/2026-08-19-auditoria-linea-base-pdc.md`

- [ ] **Paso 1: inventariar dónde el PDC usa línea base**

```bash
grep -rn "LineaBase\|linea_base\|lineaBase" src/Services/Pdc/ pdc-app/src/ | tee /tmp/pdc-lb.txt
```

Listar cada punto con archivo y línea. **Ninguno se salta**: si uno no aplica, se escribe por qué.

- [ ] **Paso 2: por cada punto, montar el caso difícil y medirlo**

Para cada uso, comprobar **con datos** que reprogramar no altera la línea base. El caso difícil es el
mismo que destapó el defecto del cronograma: un proyecto cuyas actividades de la primera semana y la
última **no se solapan** (hoy, el proyecto 68).

Registrar por cada punto: la consulta o llamada ejecutada, su salida real, y el veredicto —conserva
la línea base, o no la conserva—.

- [ ] **Paso 3: escribir el informe**

En `docs/superpowers/evidencia/2026-08-19-auditoria-linea-base-pdc.md`, una sección por punto
auditado, cada una con: ruta y línea, qué hace, comando ejecutado, salida pegada, veredicto. Al final,
**qué NO se auditó y por qué** — el límite declarado vale más que una conclusión redonda.

Lo ya sabido, que se verifica en vez de darse por bueno: `FlujoCajaService.php:275-282` lee las
fechas almacenadas, no derivadas; `SeguimientoService.php:85` conserva la línea base por diseño.

- [ ] **Paso 4: si aparece un defecto, PARAR y escalar**

Un defecto en el PDC **no se arregla en este frente**: cambia el alcance. Se anota en el informe con
su evidencia y se escala a la coordinadora.

- [ ] **Paso 5: commit**

```bash
git add docs/superpowers/evidencia/2026-08-19-auditoria-linea-base-pdc.md
git commit -m "docs(pdc): auditoria de la linea base con evidencia, punto por punto"
```

---

### Tarea 7: cerrar contra el CI, que es la condición de hecho

- [ ] **Paso 1: la suite completa en el nivel que el CI honra**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=http
```

Esperado: sin `FAIL: baseline-drift:`, y `test_bi_programa_general_chart_values.php` pasando **sin
haber sido modificado**. Confirmarlo:

```bash
git diff --stat origin/main -- tests/test_bi_programa_general_chart_values.php database/fixtures/design-system-ci.sql
```

Esperado: **vacío**. Si no lo está, se incumplió una restricción global y hay que revertir esos dos
archivos.

- [ ] **Paso 2: la suite estática y PHPStan**

```bash
npm run test:design-system:static
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

- [ ] **Paso 3: publicar por el gate de cierre**

Verificar, commitear, `git fetch`, integrar si hay divergencia, **re-verificar después de integrar**,
pedir el visto a la coordinadora con el sha medido, y publicar con `bash scripts/publicar.sh`.

**Ojo con el invariante del montaje:** `publicar.sh` deniega si el contenedor `app` no sirve el árbol
que se verifica. El contenedor es compartido; si hay que reapuntarlo, se avisa antes y se devuelve a
la raíz al terminar.

- [ ] **Paso 4: mirar la corrida real de Actions**

La condición de hecho **no es que pase en local**: es el paso «Correr la suite PHP completa que el CI
puede honrar» del job `design-system-runtime` **en verde sobre `main`**.

```bash
gh run list --workflow=design-system.yml --limit 3
gh run view <id> --json jobs -q '.jobs[]|select(.name=="design-system-runtime")|.steps[]|"\(.name) => \(.conclusion)"'
```

- [ ] **Paso 5: anotar el cierre y desbloquear lo que estaba detrás**

Anotar en `goals/linea-base-contractual/goal.md` con el sha publicado. Avisar a la coordinadora de que
las Fases 2 y 3 de `runtime-budgets-al-ci` quedan desbloqueadas: con el paso 12 en verde, los pasos
posteriores —`Measure runtime budgets` y `Check runtime budgets`— por fin se ejecutan.

---

## Riesgos y reversas

- **El test sigue rojo después de la Tarea 2** → el diagnóstico está mal. **Parar y escalar.** No
  tocar el test ni el fixture: son la condición de hecho, no una variable de ajuste.
- **`programaContractualBaselineForCurrentCohort` la usa alguien más** → no se retira; se deja y se
  anota. Retirar código vivo es peor que dejar código muerto anotado.
- **La migración escribe fechas raras** → el respaldo del Paso 5 de la Tarea 5 es la reversa, y por eso
  se verifica antes de aplicar y no después.
- **La auditoría del PDC encuentra un defecto** → se anota y se escala; no se arregla aquí.
- **`main` avanza mientras se verifica** → repetir fetch, integrar, re-verificar y pedir visto nuevo.
  El rechazo del push es el guardarraíl funcionando, no un problema.
