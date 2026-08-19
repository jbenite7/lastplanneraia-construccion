---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-19
areas: [datos]
fuente: docs/superpowers/plans/2026-08-19-migracion-estados.md
resumen: dejar preparada, ensayada y reversible la migración de la columna Estado en los 16 proyectos — sin aplicarla.
---

# Migración de la columna `Estado` — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usá `superpowers:executing-plans` o
> `superpowers:subagent-driven-development`. Los pasos usan casillas (`- [ ]`).

**Goal:** dejar preparada, ensayada y reversible la migración de la columna `Estado` en los 16
proyectos — **sin aplicarla.**

**Architecture:** un script de migración con el patrón del repositorio (`--apply` explícito,
dry-run por defecto) que recorre `programa_consolidado` y compara el `Estado` guardado con el que
producen los calculadores canónicos ya publicados. Informa las transiciones; no escribe. El respaldo
va en una tabla hermana y **se prueba restaurando**, no se declara.

**Tech Stack:** PHP 8.3, `Database` (PDO, prepared statements), el calculador legacy
`pg_calculate_status()` como fuente única de verdad del recálculo.

**Spec:** encargo de la coordinadora del 2026-08-19 con las tres exigencias; proceso obligatorio en
`docs/global-tables-architecture.md`.

## La regla que gobierna este plan

**El `--apply` no se ejecuta aquí, y no lo habilita nadie salvo el usuario.**

Ni el visto técnico de la coordinadora, ni una autorización relatada por ella. La regla de gobierno
del 2026-08-19 —que hace valer el relato de la coordinadora como autorización del usuario— **cubre
publicar en `main` y excluye las migraciones por texto propio**. El apply exige el sí explícito del
usuario **sobre el resultado del dry-run**, es decir, con los números delante.

Este plan termina en «listo para aplicar». El apply es un acto posterior, con su propia
autorización.

## Paso 0 de toda tarea que ejecute PHP

Antes de aceptar cualquier resultado, comprobar qué árbol monta el contenedor:

```bash
docker inspect $(docker compose ps -q app) \
  --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}'
```

Si no devuelve este worktree, el resultado **no cuenta**. Ver
`memoria/trampas/contenedor-compartido-durante-verificacion.md`.

## Global Constraints

- **Cero escrituras en `programa_consolidado` durante todo el plan.** Verificable:
  `SELECT COUNT(*) ... WHERE Estado = <legacy>` da lo mismo antes y después.
- **Los ocho estados canónicos** salen de `docs/design-system/ds-f1a-escala-estado.json`.
- **No tocar los calculadores**: son del frente (A), publicados en `aeaa7a77`.
- **Sin dependencias nuevas.**
- **Todo `UPDATE` va con `project_id` en el `WHERE`**, por el aislamiento que exige `AGENTS.md`.
- **La clave de identidad es la PK real `(project_id, Consecutivo)`.** Medido antes de escribir el
  script: `(project_id, unique_id, Semana)` **no es única** —127 combinaciones duplicadas— porque
  **7 686 filas (11,7%) tienen `unique_id` vacío**; en el proyecto 65 esa clave agrupa 704 filas en
  una. Y `id` tampoco sirve: 6 454 valores distintos para 65 557 filas. `(project_id, Consecutivo)`
  da cero duplicados.

---

### Task 1: Capturar las 113 antes de nada

**Va primero a propósito.** Es el hallazgo del frente (A): después del recálculo no habría forma de
saber cuáles eran. Es una exigencia del plan, no una recomendación.

**Files:**
- Create: `goals/migracion-estados/113-contradictorias-capturadas.csv`
- Create: `goals/migracion-estados/113-contradictorias-capturadas.md`

- [ ] **Step 1: Paso 0 — comprobar el montaje del contenedor**

- [ ] **Step 2: Capturar las filas con su identidad**

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASS"));
$sql = "SELECT project_id, Consecutivo, unique_id, Semana, Estado, Semanas_Inicio, Ejecutado, Fecha_Inicio, Fecha_Fin
        FROM programa_consolidado
        WHERE COALESCE(Titulo,0)<>1 AND Semanas_Inicio>=7
          AND Estado NOT IN (\"Actividad Futura\",\"No Requerida\")
        ORDER BY project_id, Consecutivo";
$out = fopen("php://stdout","w");
$st = $pdo->query($sql);
fputcsv($out, array_keys($st->fetch(PDO::FETCH_ASSOC)));
$st = $pdo->query($sql);
foreach ($st as $r) fputcsv($out, $r);
' > goals/migracion-estados/113-contradictorias-capturadas.csv
wc -l goals/migracion-estados/113-contradictorias-capturadas.csv
```

Esperado: **114 líneas** (cabecera + 113) si nada se movió. **Se sabe que algo se movió**: la tabla
pasó de 65 549 a 65 557 filas mientras corría el frente (A).

**El freno no es el número, es la naturaleza.** Comparar contra el diagnóstico heredado y reportar
la deriva:

- **Si las familias siguen siendo las mismas** (`En Curso`, `Terminada`, `A Tiempo`,
  `Terminada Antes`) y los proyectos también (68 y 63), la deriva es ruido de sesiones de
  desarrollo escribiendo: se anota el número nuevo y **se sigue**.
- **Si aparece una familia nueva, un proyecto nuevo, o desaparece una de las anteriores**, la
  naturaleza de las contradicciones cambió: **parar y reportar**, porque el diagnóstico heredado ya
  no describe lo que hay.

Nota sobre el paso 0 en esta tarea: la captura va por PDO directo contra `db`, así que **el árbol
que monte el contenedor es irrelevante aquí** — la base es la misma. El paso 0 vuelve a ser
obligatorio en la Task 2, que ejecuta un script del worktree.

- [ ] **Step 3: Escribir el acta de la captura**

Crear `goals/migracion-estados/113-contradictorias-capturadas.md` con: la consulta exacta, la fecha,
el sha, el conteo obtenido, y **por qué existe este archivo** — que es el único registro de qué eran
esas filas antes de que el recálculo las reescriba. Enlazar al diagnóstico del frente (A).

- [ ] **Step 4: Commit**

```bash
git add goals/migracion-estados/113-contradictorias-capturadas.csv goals/migracion-estados/113-contradictorias-capturadas.md
git commit -m "docs(migracion): captura las 113 contradictorias antes de que el recalculo las borre"
```

---

### Task 2: El respaldo, probado restaurando

**Files:**
- Create: `database/migrations/20260819_recalculo_estados.php` (solo la parte de respaldo)

**Interfaces:**
- Produces: la tabla `programa_consolidado_estado_respaldo_20260819` y las funciones
  `respaldar(Database $db, bool $apply): array` y `restaurar(Database $db, bool $apply): array`,
  que la Task 3 reutiliza.

- [ ] **Step 1: Paso 0 — comprobar el montaje**

- [ ] **Step 2: Escribir la parte de respaldo del script**

Crear `database/migrations/20260819_recalculo_estados.php` con la cabecera del patrón del
repositorio (`--apply` explícito, dry-run por defecto) y estas dos operaciones:

```php
<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Legacy/estado_programa_general.php';

// Dry-run POR DEFECTO. El apply exige --apply Y el si explicito del usuario sobre el
// resultado del dry-run: ver la seccion «La regla que gobierna este plan» del plan.
$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

const TABLA_RESPALDO = 'programa_consolidado_estado_respaldo_20260819';

/**
 * Respalda SOLO lo necesario para restaurar la columna: la identidad de la fila y su Estado.
 * No copia la tabla entera -65.549 filas con todas sus columnas- porque restaurar no necesita
 * mas que esto, y un respaldo mas pequeno es un respaldo que se puede verificar entero.
 */
function respaldar(Database $db, bool $apply): array
{
    $existe = $db->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?",
        [TABLA_RESPALDO]
    )->fetchColumn();

    $origen = (int) $db->query("SELECT COUNT(*) FROM programa_consolidado")->fetchColumn();

    if (!$apply) {
        return ['modo' => 'DRY-RUN', 'respaldo_existe' => (bool) $existe, 'filas_a_respaldar' => $origen];
    }

    $db->query("DROP TABLE IF EXISTS " . TABLA_RESPALDO);
    $db->query(
        "CREATE TABLE " . TABLA_RESPALDO . " AS
         SELECT project_id, Consecutivo, Semana, Estado FROM programa_consolidado"
    );
    $copiadas = (int) $db->query("SELECT COUNT(*) FROM " . TABLA_RESPALDO)->fetchColumn();

    return ['modo' => 'APPLY', 'filas_origen' => $origen, 'filas_respaldadas' => $copiadas,
            'coinciden' => $origen === $copiadas];
}
```

- [ ] **Step 3: Correr el dry-run del respaldo**

```bash
docker compose exec -T app php database/migrations/20260819_recalculo_estados.php
```

Esperado: informa cuántas filas respaldaría y que la tabla no existe todavía. **Sin escribir nada.**

- [ ] **Step 4: Crear el respaldo de verdad y verificar que restaura**

El respaldo **sí se crea** en este frente: crear una tabla nueva no modifica `programa_consolidado`
y es la única forma de probar que la restauración funciona.

```bash
docker compose exec -T app php database/migrations/20260819_recalculo_estados.php --solo-respaldo --apply
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASS"));
$a = $pdo->query("SELECT COUNT(*) FROM programa_consolidado")->fetchColumn();
$b = $pdo->query("SELECT COUNT(*) FROM programa_consolidado_estado_respaldo_20260819")->fetchColumn();
$d = $pdo->query("SELECT COUNT(*) FROM programa_consolidado p
    JOIN programa_consolidado_estado_respaldo_20260819 r
      ON r.project_id=p.project_id AND r.Consecutivo=p.Consecutivo
    WHERE NOT (p.Estado <=> r.Estado)")->fetchColumn();
printf("origen=%s respaldo=%s diferencias=%s\n", $a, $b, $d);
'
```

Esperado: `origen` y `respaldo` iguales, y `diferencias=0`. **`<=>` y no `=`**: el operador seguro
para nulos, porque 7 705 filas tienen `Estado` vacío y `NULL = NULL` es `NULL`, no verdadero — con
`=` esas filas no se compararían y el respaldo parecería correcto sin serlo.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260819_recalculo_estados.php
git commit -m "feat(migracion): el respaldo de la columna Estado, verificado fila a fila"
```

---

### Task 3: El dry-run del recálculo

**Files:**
- Modify: `database/migrations/20260819_recalculo_estados.php`

**Interfaces:**
- Consumes: de Task 2, `respaldar()` y la constante `TABLA_RESPALDO`.
- Produces: la función `recalcular(Database $db, bool $apply): array`, con el resumen de
  transiciones que la Task 4 convierte en informe.

- [ ] **Step 1: Paso 0 — comprobar el montaje**

- [ ] **Step 2: Escribir el recálculo, en modo informe**

Añadir al script:

```php
/**
 * Recorre las filas y compara el Estado guardado con el que producen los calculadores
 * canonicos. En dry-run NO escribe: acumula las transiciones y las devuelve.
 *
 * Usa `pg_calculate_status()` y no `LpsService`: son la misma clasificacion y su paridad la
 * vigila `tests/unit/EstadoProgramaGeneralTest.php`, asi que basta con una — y la del legacy
 * es la que declara sus umbrales como constantes con nombre.
 */
function recalcular(Database $db, bool $apply): array
{
    $filas = $db->query(
        "SELECT p.project_id, p.Consecutivo, p.Semana, p.Estado, p.Titulo, p.Ejecutado,
                p.Fecha_Inicio, p.Fecha_Fin, s.Fecha_Inicio_Sem, s.Fecha_Fin_Sem
         FROM programa_consolidado p
         LEFT JOIN semanas_activas s
           ON s.project_id = p.project_id AND s.Semana = p.Semana
         ORDER BY p.project_id, p.Consecutivo"
    );

    $transiciones = [];
    $porProyecto = [];
    $sinSemana = 0;
    $cambios = 0;
    $iguales = 0;

    foreach ($filas as $f) {
        if ($f['Fecha_Inicio_Sem'] === null) {
            // Sin semana activa no hay contra que calcular: se cuenta y se deja intacta.
            $sinSemana++;
            continue;
        }

        $nuevo = pg_calculate_status(
            $f['Titulo'], $f['Ejecutado'], $f['Fecha_Inicio'], $f['Fecha_Fin'],
            $f['Fecha_Inicio_Sem'], $f['Fecha_Fin_Sem'],
        );
        $viejo = (string) ($f['Estado'] ?? '');

        if ($nuevo === $viejo) {
            $iguales++;
            continue;
        }

        $cambios++;
        $clave = ($viejo === '' ? '(vacio)' : $viejo) . ' -> ' . $nuevo;
        $transiciones[$clave] = ($transiciones[$clave] ?? 0) + 1;
        $porProyecto[$f['project_id']] = ($porProyecto[$f['project_id']] ?? 0) + 1;

        if ($apply) {
            $db->query(
                // La clave es la PK REAL: (project_id, Consecutivo). NO `unique_id`, que esta
                // vacio en 7.686 filas (11,7%): un UPDATE por (project_id, unique_id, Semana)
                // habria escrito 704 filas de golpe en el proyecto 65. Medido antes de ejecutar
                // nada, y es la razon por la que este plan se reviso contra la base.
                "UPDATE programa_consolidado SET Estado = ?
                 WHERE project_id = ? AND Consecutivo = ?",
                [$nuevo, $f['project_id'], $f['Consecutivo']]
            );
        }
    }

    arsort($transiciones);

    return ['modo' => $apply ? 'APPLY' : 'DRY-RUN', 'cambios' => $cambios, 'iguales' => $iguales,
            'sin_semana_activa' => $sinSemana, 'transiciones' => $transiciones,
            'por_proyecto' => $porProyecto];
}
```

- [ ] **Step 3: Correr el dry-run completo**

```bash
docker compose exec -T app php database/migrations/20260819_recalculo_estados.php
```

Esperado: el resumen de transiciones, **sin una sola escritura**.

- [ ] **Step 4: Demostrar que el dry-run no escribió**

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASS"));
foreach ($pdo->query("SELECT COUNT(*) n FROM programa_consolidado p
   JOIN programa_consolidado_estado_respaldo_20260819 r
     ON r.project_id=p.project_id AND r.Consecutivo=p.Consecutivo
   WHERE NOT (p.Estado <=> r.Estado)") as $r) printf("filas distintas del respaldo: %s\n", $r["n"]);
'
```

Esperado: **0**. Es la prueba de que el dry-run es dry: compara contra el respaldo tomado antes.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260819_recalculo_estados.php
git commit -m "feat(migracion): el recalculo en modo informe, con la prueba de que no escribe"
```

---

### Task 4: El informe del dry-run y la propuesta para las 24

**Files:**
- Create: `goals/migracion-estados/informe-dry-run.md`

- [ ] **Step 1: Escribir el informe con los números reales del dry-run**

Crear `goals/migracion-estados/informe-dry-run.md` con: el comando y su salida literal; la tabla de
transiciones ordenada por volumen; el reparto por proyecto; cuántas filas quedan igual; y cuántas se
saltaron por no tener semana activa.

- [ ] **Step 2: Proponer el tratamiento de las 24, sin ejecutarlo**

Añadir al informe una sección con **tres opciones y una recomendación**, dejando claro que **decide
el usuario**:

1. **Migrarlas como al resto** — quedan en `Fuera de Ventana` y se pierde que estaban terminadas.
2. **Excluirlas del recálculo** — conservan su estado actual, incoherente pero informativo, y
   quedan marcadas para revisión manual.
3. **Migrarlas y registrar el estado anterior** en la tabla de respaldo, que ya lo tiene por
   diseño, más una nota en el informe con sus `unique_id`.

**Recomendación: la 3.** El respaldo ya conserva el dato, así que «se pierde la información» deja de
ser cierto en cuanto el respaldo exista y esté verificado; y dejar 24 filas fuera del recálculo
crearía una excepción permanente que nadie recordaría en la siguiente migración. Pero **es decisión
del usuario** y el informe se la presenta con las tres.

- [ ] **Step 3: Commit**

```bash
git add goals/migracion-estados/informe-dry-run.md
git commit -m "docs(migracion): el informe del dry-run y las tres opciones para las 24 filas"
```

---

### Task 5: Los gates obligatorios

`docs/global-tables-architecture.md` §Gates Obligatorios. Van **antes** del apply, no después.

- [ ] **Step 1: Paso 0 — comprobar el montaje**

- [ ] **Step 2: Correr los gates de tablas globales**

```bash
docker compose exec -T app php tests/test_global_table_safety.php
docker compose exec -T app php tests/test_global_table_reconciliation.php
```

Cada uno **leyendo su código de salida en su propia línea**, sin encadenar.

- [ ] **Step 3: Correr la suite PHP y PHPStan**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=http
docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

**Los 5 fallos preexistentes de `--nivel=http` medidos en el frente (A) siguen siendo aceptables**
—`test_dev_door_http`, `test_semanal_sanear_csrf`, `test_bi_source_reconciliation`,
`test_equipment_families_require_review`, `test_report_processor_cic_project_scope`—. **Cualquier
fallo distinto de esos cinco para el plan y se reporta.**

- [ ] **Step 4: Anotar los resultados en el informe y commitear**

---

## Lo que NO hace este plan

**El `--apply`.** El plan termina con todo listo: las 113 capturadas, el respaldo creado y probado,
el dry-run corrido con sus números, los gates en verde y las tres opciones para las 24 sobre la mesa.

Aplicar exige **el sí explícito del usuario sobre el resultado del dry-run**, y ese sí no lo
sustituye ni el visto de la coordinadora ni una autorización relatada.

## Cierre del frente

- [ ] Verificar la condición de hecho con salida real, incluida la prueba de que
      `programa_consolidado` no cambió.
- [ ] `git status` limpio.
- [ ] `git fetch origin` y mirar la divergencia.
- [ ] Integrar si la hay.
- [ ] **Re-verificar después de integrar.** Anotar el sha.
- [ ] Pedir el visto a la coordinadora.
- [ ] Publicar el sha visado. Con él viajan `5759b13d` y `a7ac08d0`.
- [ ] Confirmar `origin/main`.
- [ ] Anotar el cierre en `goals/migracion-estados/goal.md`.
