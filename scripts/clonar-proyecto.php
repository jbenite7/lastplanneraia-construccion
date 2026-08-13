<?php

declare(strict_types=1);

/**
 * Clona TODOS los datos operativos de un proyecto sobre otro.
 *
 * El destino queda como copia del origen: primero se borra lo que el destino tenga en cada
 * tabla con `project_id`, y despues se copia fila a fila desde el origen. Es DESTRUCTIVO para
 * el destino y no se deshace sin restaurar un respaldo.
 *
 * Uso (dry-run por defecto, no escribe nada):
 *
 *   set -a; . ./.env; set +a
 *   php scripts/clonar-proyecto.php --origen=73 --destino=27
 *   php scripts/clonar-proyecto.php --origen=73 --destino=27 --apply
 *
 * ---------------------------------------------------------------------------------------------
 * Por que no es un `INSERT ... SELECT` con el project_id cambiado
 * ---------------------------------------------------------------------------------------------
 * La mayoria de tablas tiene PK compuesta `(project_id, X)` — `programa`, `programa_consolidado`,
 * `programacion_semanal`, `profesionales`, `semanas_activas`, `pdc`… — y sus claves foraneas
 * tambien incluyen `project_id`. Esas viajan intactas: al cambiar solo el proyecto, `unique_id`,
 * `Consecutivo` y `Semana` siguen apuntando a la fila correcta ya dentro del destino.
 *
 * Las que NO viajan intactas son las de `id` AUTO_INCREMENT global, porque al insertarlas MySQL
 * asigna numeros nuevos. Solo dos cadenas de esas estan referenciadas por otras tablas, y son las
 * unicas que exigen remapeo:
 *
 *   pdc_presupuesto_versiones.id  <-  pdc_presupuesto_items.version_id
 *                                 <-  pdc_presupuesto_apu_insumos.version_id
 *                                 <-  pdc_insumo_vinculos.version_id
 *   pdc_presupuesto_items.id      <-  pdc_presupuesto_apu_insumos.item_id
 *
 * El resto de tablas con `id` autoincremental (informes, curvas, logs, project_members…) no las
 * referencia nadie, asi que su id puede cambiar sin consecuencias.
 *
 * Las FK a catalogos globales (`general_maestro_insumos`, `general_paquetes_contratacion`,
 * `general_usuarios`, `general_pasos_contratacion`) apuntan a datos compartidos entre proyectos:
 * se conservan tal cual, copiarlos seria duplicar el catalogo.
 *
 * ---------------------------------------------------------------------------------------------
 * Lo que este script NO toca, a proposito
 * ---------------------------------------------------------------------------------------------
 * - El NOMBRE del proyecto destino. Sigue llamandose como se llame; si se copiara, habria dos
 *   proyectos con el mismo nombre y ya hay casos asi en la base. Se copia solo la configuracion
 *   operativa (fechas de linea base, costo de retraso, pdcActivo), que es la que cambia como se
 *   comporta el proyecto. Con --sin-config ni eso.
 * - Los catalogos globales y las tablas sin `project_id`.
 */

const TABLAS_EXCLUIDAS = [
    // Se maneja aparte: es la fila del proyecto, no datos operativos.
    'general_proyectos_procesos',

    // Residuo del PDC v1, eliminado del repo el 2026-08-04 (ver AGENTS.md). Ningun archivo de
    // src/, public/, admin/ ni pdc-app/ las menciona, asi que copiarlas no alimenta nada — y sus
    // UNIQUE globales sobre uuid (`uq_semi_auto_*`) hacen chocar el clon contra el original.
    'semi_auto_assistant_feedback',
    'semi_auto_decisions',
    'semi_auto_learning_candidates',
    'semi_auto_learning_rules',
    'semi_auto_proactive_queue',
    'semi_auto_runs',
    'semi_auto_suggestions',
];

/**
 * Columnas con UNIQUE global que se anulan en el clon en vez de copiarse.
 *
 * `import_token` marca «este archivo ya se importo» para que una reimportacion no duplique el
 * presupuesto. Es unico en toda la base, no por proyecto: copiarlo choca contra el original, y
 * ademas mentiria — el clon no procede de una importacion. Admite NULL, que es su estado natural.
 */
const COLUMNAS_ANULADAS = [
    'pdc_presupuesto_versiones' => ['import_token'],
];

/** Cadenas de identificadores que hay que reescribir tras copiar. */
const REMAPEOS = [
    'pdc_presupuesto_versiones' => [
        ['tabla' => 'pdc_presupuesto_items', 'columna' => 'version_id'],
        ['tabla' => 'pdc_presupuesto_apu_insumos', 'columna' => 'version_id'],
        ['tabla' => 'pdc_insumo_vinculos', 'columna' => 'version_id'],
    ],
    'pdc_presupuesto_items' => [
        ['tabla' => 'pdc_presupuesto_apu_insumos', 'columna' => 'item_id'],
    ],
];

/** Orden de copia: las tablas cuyo id se remapea van primero, y antes las que otras necesitan. */
const ORDEN_PRIORITARIO = [
    'pdc_presupuesto_versiones',
    'pdc_presupuesto_items',
    'pdc_presupuesto_apu_insumos',
    'pdc_insumo_vinculos',
];

function argumento(string $nombre): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (str_starts_with($arg, "--$nombre=")) {
            return substr($arg, strlen($nombre) + 3);
        }
    }

    return null;
}

function tieneBandera(string $nombre): bool
{
    return in_array("--$nombre", $GLOBALS['argv'], true);
}

$origen = (int) (argumento('origen') ?? 0);
$destino = (int) (argumento('destino') ?? 0);
$aplicar = tieneBandera('apply');
$sinConfig = tieneBandera('sin-config');

if ($origen <= 0 || $destino <= 0) {
    fwrite(STDERR, "Uso: php scripts/clonar-proyecto.php --origen=<id> --destino=<id> [--apply] [--sin-config]\n");
    exit(2);
}

if ($origen === $destino) {
    fwrite(STDERR, "El origen y el destino son el mismo proyecto ($origen). Nada que hacer.\n");
    exit(2);
}

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$nombreBase = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$usuario = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
$clave = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');

if ($host === '' || $nombreBase === '') {
    fwrite(STDERR, "Falta el entorno. Exportalo primero:  set -a; . ./.env; set +a\n");
    exit(2);
}

$pdo = new PDO(
    "mysql:host=$host;dbname=$nombreBase;charset=utf8mb4",
    $usuario,
    $clave,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$modo = $aplicar ? 'APLICAR (escribe)' : 'SIMULACRO (no escribe)';
echo "Base:    $nombreBase\n";
echo "Modo:    $modo\n";

$stmt = $pdo->prepare('SELECT Id, Proyecto_Proceso FROM general_proyectos_procesos WHERE Id = ?');
$nombres = [];
foreach ([$origen, $destino] as $id) {
    $stmt->execute([$id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fila) {
        fwrite(STDERR, "El proyecto $id no existe en esta base. Abortado.\n");
        exit(1);
    }
    $nombres[$id] = (string) $fila['Proyecto_Proceso'];
}

echo "Origen:  $origen — {$nombres[$origen]}\n";
echo "Destino: $destino — {$nombres[$destino]}  (se borra su contenido)\n\n";

/**
 * Tablas REALES con `project_id`. Las vistas quedan fuera a proposito.
 *
 * Las nueve `bi_*` con `project_id` son vistas: se derivan de las tablas base, asi que al copiar
 * los datos operativos el BI del destino se recalcula solo. Ademas no se pueden escribir — MySQL
 * responde «target table … of the DELETE is not updatable», que fue como se descubrio.
 *
 * @return list<string>
 */
function tablasConProyecto(PDO $pdo): array
{
    $sql = 'SELECT c.TABLE_NAME
              FROM information_schema.COLUMNS c
              JOIN information_schema.TABLES t
                ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND c.COLUMN_NAME = "project_id"
               AND t.TABLE_TYPE = "BASE TABLE"
             ORDER BY c.TABLE_NAME';

    $tablas = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_diff($tablas, TABLAS_EXCLUIDAS));
}

function columnaAutoIncrement(PDO $pdo, string $tabla): ?string
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                             AND EXTRA LIKE "%auto_increment%"');
    $stmt->execute([$tabla]);
    $col = $stmt->fetchColumn();

    return $col === false ? null : (string) $col;
}

/**
 * Columnas generadas (VIRTUAL o STORED). MySQL prohibe darles valor en un INSERT y responde
 * «The value specified for generated column … is not allowed», asi que hay que omitirlas: se
 * recalculan solas en el destino. En esta base la trae `pdc_presupuesto_versiones.activa_unica`.
 *
 * @return list<string>
 */
function columnasGeneradas(PDO $pdo, string $tabla): array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                             AND EXTRA LIKE "%GENERATED%"');
    $stmt->execute([$tabla]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function contar(PDO $pdo, string $tabla, int $proyecto): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$tabla` WHERE project_id = ?");
    $stmt->execute([$proyecto]);

    return (int) $stmt->fetchColumn();
}

$tablas = tablasConProyecto($pdo);

// Las prioritarias primero, en su orden; el resto alfabetico.
$ordenadas = [];
foreach (ORDEN_PRIORITARIO as $t) {
    if (in_array($t, $tablas, true)) {
        $ordenadas[] = $t;
    }
}
foreach ($tablas as $t) {
    if (!in_array($t, $ordenadas, true)) {
        $ordenadas[] = $t;
    }
}

$totalBorrar = 0;
$totalCopiar = 0;
$conTrabajo = [];

printf("%-44s %10s %10s\n", 'tabla', 'borra', 'copia');
echo str_repeat('-', 66) . "\n";

foreach ($ordenadas as $tabla) {
    $borra = contar($pdo, $tabla, $destino);
    $copia = contar($pdo, $tabla, $origen);
    if ($borra === 0 && $copia === 0) {
        continue;
    }
    $conTrabajo[] = $tabla;
    $totalBorrar += $borra;
    $totalCopiar += $copia;
    printf("%-44s %10d %10d\n", $tabla, $borra, $copia);
}

echo str_repeat('-', 66) . "\n";
printf("%-44s %10d %10d\n", 'TOTAL (' . count($conTrabajo) . ' tablas)', $totalBorrar, $totalCopiar);

echo "\nRemapeo de identificadores: " . count(REMAPEOS) . " cadenas\n";
foreach (REMAPEOS as $fuente => $destinos) {
    foreach ($destinos as $ref) {
        echo "  {$fuente}.id  ->  {$ref['tabla']}.{$ref['columna']}\n";
    }
}

echo "\nConfiguracion del proyecto: " . ($sinConfig ? 'NO se copia (--sin-config)' : 'se copia (linea base, costo de retraso, pdcActivo)') . "\n";
echo "Nombre del proyecto destino: se conserva («{$nombres[$destino]}»)\n";

if (!$aplicar) {
    echo "\nSimulacro: no se escribio nada. Repite con --apply para aplicarlo.\n";
    exit(0);
}

echo "\nAplicando…\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->beginTransaction();

try {
    // 1. Vaciar el destino.
    foreach ($conTrabajo as $tabla) {
        $stmt = $pdo->prepare("DELETE FROM `$tabla` WHERE project_id = ?");
        $stmt->execute([$destino]);
    }
    echo "  destino vaciado: " . count($conTrabajo) . " tablas\n";

    // 2. Copiar, guardando el mapa de ids de las tablas que otras referencian.
    $mapas = [];

    foreach ($conTrabajo as $tabla) {
        $filas = $pdo->query("SELECT * FROM `$tabla` WHERE project_id = $origen")->fetchAll(PDO::FETCH_ASSOC);
        if ($filas === []) {
            continue;
        }

        $ai = columnaAutoIncrement($pdo, $tabla);
        $necesitaMapa = array_key_exists($tabla, REMAPEOS);
        $mapas[$tabla] = [];

        $omitir = columnasGeneradas($pdo, $tabla);
        if ($ai !== null) {
            $omitir[] = $ai;
        }
        $cols = array_values(array_filter(
            array_keys($filas[0]),
            static fn (string $c): bool => !in_array($c, $omitir, true),
        ));

        $lista = '`' . implode('`, `', $cols) . '`';
        $marcas = implode(', ', array_fill(0, count($cols), '?'));
        $insert = $pdo->prepare("INSERT INTO `$tabla` ($lista) VALUES ($marcas)");

        foreach ($filas as $fila) {
            $idViejo = $ai !== null ? $fila[$ai] : null;

            $anuladas = COLUMNAS_ANULADAS[$tabla] ?? [];

            $valores = [];
            foreach ($cols as $c) {
                if ($c === 'project_id') {
                    $valores[] = $destino;
                } elseif (in_array($c, $anuladas, true)) {
                    $valores[] = null;
                } else {
                    $valores[] = $fila[$c];
                }
            }
            $insert->execute($valores);

            if ($necesitaMapa && $idViejo !== null) {
                $mapas[$tabla][(string) $idViejo] = (int) $pdo->lastInsertId();
            }
        }

        echo sprintf("  %-44s %d filas\n", $tabla, count($filas));
    }

    // 3. Reescribir las referencias a los ids que cambiaron.
    foreach (REMAPEOS as $fuente => $referencias) {
        $mapa = $mapas[$fuente] ?? [];
        if ($mapa === []) {
            continue;
        }
        foreach ($referencias as $ref) {
            if (!in_array($ref['tabla'], $conTrabajo, true)) {
                continue;
            }
            $update = $pdo->prepare(
                "UPDATE `{$ref['tabla']}` SET `{$ref['columna']}` = ? WHERE project_id = ? AND `{$ref['columna']}` = ?",
            );
            $tocadas = 0;
            foreach ($mapa as $viejo => $nuevo) {
                $update->execute([$nuevo, $destino, $viejo]);
                $tocadas += $update->rowCount();
            }
            echo sprintf("  remapeo %-36s %d filas\n", "{$ref['tabla']}.{$ref['columna']}", $tocadas);
        }
    }

    // 4. Configuracion operativa del proyecto (nunca el nombre).
    if (!$sinConfig) {
        $pdo->exec(
            'UPDATE general_proyectos_procesos d
               JOIN general_proyectos_procesos o ON o.Id = ' . $origen . '
                SET d.Area = o.Area,
                    d.pdcActivo = o.pdcActivo,
                    d.fechaInicioLineaBase = o.fechaInicioLineaBase,
                    d.fechaFinLineaBase = o.fechaFinLineaBase,
                    d.costoDiaRetraso = o.costoDiaRetraso,
                    d.urlCambios = o.urlCambios,
                    d.pc_restr_2_nombre = o.pc_restr_2_nombre,
                    d.pc_restr_3_nombre = o.pc_restr_3_nombre,
                    d.pc_restr_4_nombre = o.pc_restr_4_nombre
              WHERE d.Id = ' . $destino,
        );
        echo "  configuracion del proyecto copiada (sin el nombre)\n";
    }

    $pdo->commit();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "\nHecho. Verifica con el simulacro: los conteos de origen y destino deben coincidir.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, "\nFALLO, se deshizo todo: " . $e->getMessage() . "\n");
    exit(1);
}
