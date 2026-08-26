<?php
/**
 * DRY-RUN (solo lectura) — migracion CT-7.3 de `pi_shared_constraints` (Task 4, Paso 2).
 *
 * Simula, sin escribir nada, el relleno de compatibilidad de la futura columna
 * `EstadoLiberacion` (enum `sin_gestionar|en_gestion|liberada|no_aplica`) descrito en
 * docs/superpowers/specs/2026-08-26-v0-del-producto-design.md (CT-7.3):
 *
 *   ValorObjetivo = 0        -> sin_gestionar
 *   0 < ValorObjetivo < 1    -> en_gestion
 *   ValorObjetivo >= 1.0     -> liberada
 *
 * La especificacion solo nombra estos tres casos, pero el enum real de CT-7.3 tiene un
 * cuarto valor (`no_aplica`) que la prosa no explica. Este script verifico el esquema real
 * de `pi_shared_constraints` (2026-08-26, dev) antes de asumir nada:
 *
 *   DESCRIBE pi_shared_constraints ->
 *     ValorObjetivo varchar(20) NOT NULL   (NO es una columna numerica: es texto)
 *
 *   SELECT ValorObjetivo, COUNT(*) ... GROUP BY ValorObjetivo ->
 *     '1'=131, '0'=34, 'N/A'=21, '0.66'=5   (191 filas totales)
 *
 * 21 de 191 filas (11%) traen el valor literal 'N/A', que no es numerico y que la regla de
 * CT-7.3 no cubre. Este script extiende la regla de la unica forma defendible dado el enum
 * declarado: todo valor NO numerico (incluido 'N/A') se clasifica `no_aplica`. Esta extension
 * es una decision de este dry-run, no del texto de CT-7.3, y debe confirmarse explicitamente
 * en el Paso 3 junto con el resto de la salida.
 *
 * Este script NO escribe nada: no tiene `--apply`, no abre transacciones de escritura, y la
 * sesion de PDO se pone en `SET SESSION TRANSACTION READ ONLY` antes de la primera consulta
 * (barrera dura: el proceso no podria escribir aunque el codigo tuviera un error).
 *
 * Uso:
 *   docker compose exec -T app php scripts/dry-run-constraints-gestion.php
 *
 * Lee la conexion del `.env` del repositorio (mismo patron que scripts/diagnostico-unique-id-nulos.php).
 */

$repoRoot = dirname(__DIR__);

$dotenv = $repoRoot . '/.env';
if (is_file($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASS'] ?? '';

if ($dbName === '') {
    fwrite(STDERR, "No hay DB_NAME en el .env de {$repoRoot}. Abortado.\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

/** Barrera dura: esta sesion no puede escribir aunque alguien edite el script por descuido. */
$pdo->exec('SET SESSION TRANSACTION READ ONLY');

echo "DRY-RUN CT-7.3 — `pi_shared_constraints` en `{$dbName}` ({$dbHost}:{$dbPort})\n";
echo "Solo lectura: la sesion esta en TRANSACTION READ ONLY. No se ejecuta ningun ALTER/INSERT/UPDATE/DELETE.\n";
echo str_repeat('=', 88) . "\n\n";

// ------------------------------------------------------ 1. Esquema real (verificado, no asumido)
echo "1) Esquema verificado de `pi_shared_constraints`\n";
$columnas = $pdo->query('DESCRIBE pi_shared_constraints')->fetchAll();
foreach ($columnas as $c) {
    printf("   %-20s %-20s null=%-4s key=%-4s default=%s\n", $c['Field'], $c['Type'], $c['Null'], $c['Key'], $c['Default'] ?? 'NULL');
}
echo "\n   Columna candidata a 'valor numerico de disponibilidad' segun la Task 4 brief: ValorObjetivo.\n";
echo "   Es la UNICA columna numerica/cuasi-numerica de la tabla; su tipo real es varchar(20), no un\n";
echo "   tipo numerico. Este script la trata como texto y valida cada valor con is_numeric() antes de\n";
echo "   clasificarlo — no asume que todo el contenido sea parseable como numero.\n\n";

// -------------------------------------------------- 2. Clasificacion CT-7.3 (incluye no_aplica)
echo "2) Clasificacion por EstadoLiberacion (regla de compatibilidad CT-7.3 + extension no_aplica)\n";

$filas = $pdo->query('SELECT project_id, Id, Semana, Restriccion, ValorObjetivo FROM pi_shared_constraints')->fetchAll();

$contadores = ['sin_gestionar' => 0, 'en_gestion' => 0, 'liberada' => 0, 'no_aplica' => 0];
$porProyecto = [];
$valoresNoContemplados = []; // negativos u otros fuera del dominio [0, +inf) que la regla no previo
$muestraNoAplica = [];

foreach ($filas as $fila) {
    $crudo = trim((string) $fila['ValorObjetivo']);
    $pid = (int) $fila['project_id'];

    if (!is_numeric($crudo)) {
        $estado = 'no_aplica';
        if (count($muestraNoAplica) < 5) {
            $muestraNoAplica[] = sprintf('project_id=%d Id=%s Semana=%s Restriccion=%s ValorObjetivo=%s', $pid, $fila['Id'], $fila['Semana'], $fila['Restriccion'], $crudo);
        }
    } else {
        $valor = (float) $crudo;
        if ($valor < 0.0) {
            $valoresNoContemplados[] = sprintf('project_id=%d Id=%s ValorObjetivo=%s (negativo)', $pid, $fila['Id'], $crudo);
            $estado = 'sin_gestionar'; // tratado como piso, ver aviso mas abajo
        } elseif ($valor === 0.0) {
            $estado = 'sin_gestionar';
        } elseif ($valor >= 1.0) {
            $estado = 'liberada';
        } else {
            $estado = 'en_gestion';
        }
    }

    $contadores[$estado]++;
    $porProyecto[$pid][$estado] = ($porProyecto[$pid][$estado] ?? 0) + 1;
}

$totalFilas = count($filas);
$totalReconstruido = array_sum($contadores);

foreach ($contadores as $estado => $n) {
    $pct = $totalFilas > 0 ? round($n * 100 / $totalFilas, 1) : 0;
    printf("   %-16s %5d  (%s%%)\n", $estado, $n, $pct);
}
printf("   %-16s %5d\n", 'TOTAL filas', $totalFilas);
printf("   %-16s %5d  (suma de los 4 estados; debe igualar TOTAL filas)\n", 'reconstruido', $totalReconstruido);
echo "\n";

if ($muestraNoAplica) {
    echo "   Muestra de filas clasificadas no_aplica (valor no numerico):\n";
    foreach ($muestraNoAplica as $linea) {
        echo "     {$linea}\n";
    }
    echo "\n";
}

if ($valoresNoContemplados) {
    echo "   AVISO — valores fuera del dominio que CT-7.3 previo (negativos), tratados como sin_gestionar:\n";
    foreach ($valoresNoContemplados as $linea) {
        echo "     {$linea}\n";
    }
    echo "\n";
}

echo "   Desglose por proyecto:\n";
ksort($porProyecto);
printf("     %-12s %-14s %-12s %-10s %-10s %-7s\n", 'project_id', 'sin_gestionar', 'en_gestion', 'liberada', 'no_aplica', 'total');
foreach ($porProyecto as $pid => $estados) {
    $tot = array_sum($estados);
    printf(
        "     %-12d %-14d %-12d %-10d %-10d %-7d\n",
        $pid,
        $estados['sin_gestionar'] ?? 0,
        $estados['en_gestion'] ?? 0,
        $estados['liberada'] ?? 0,
        $estados['no_aplica'] ?? 0,
        $tot,
    );
}
echo "\n";

// -------------------------------------------- 3. Reconciliacion contra "lo que hoy muestra Power BI"
echo "3) Reconciliacion contra el proxy de Power BI\n";
echo "   No hay acceso a Power BI desde este entorno. El proxy elegido, y por que:\n\n";

echo "   a) Metricas actualmente EJECUTABLES en el catalogo de BI (src/Services/Bi/MetricDictionaryService.php)\n";
echo "      que tocan restricciones son 'pi_hard_restrictions_ready_rate' y 'pi_restriction_pareto'.\n";
echo "      Ambas filtran explicitamente is_hard=1 sobre la vista bi_pi_restricciones. `pi_shared_constraints`\n";
echo "      alimenta la rama is_hard=0 de esa misma vista — es decir, la poblacion que toca esta migracion\n";
echo "      esta EXCLUIDA por diseno de las dos metricas de restricciones que hoy corren. Ademas,\n";
echo "      'pi_restriction_pareto' esta marcada 'estado_ejecucion' => 'descriptiva' en el catalogo (no\n";
echo "      ejecuta via MetricExecutor::execute() — ver el comentario de Task 3 en esa misma linea del\n";
echo "      archivo): ni siquiera si no filtrara is_hard, hoy no se muestra como numero en ningun lienzo.\n\n";

$totalLinks = (int) $pdo->query('SELECT COUNT(*) FROM pi_shared_constraint_links')->fetchColumn();
$viewIsHard0 = $pdo->query('SELECT COUNT(*) AS n, SUM(is_ready) AS listos FROM bi_pi_restricciones WHERE is_hard = 0')->fetch();
$viewIsHard1 = $pdo->query('SELECT COUNT(*) AS n, SUM(is_ready) AS listos FROM bi_pi_restricciones WHERE is_hard = 1')->fetch();

printf("   b) `pi_shared_constraint_links` (aplicaciones por actividad) — filas totales: %d\n", $totalLinks);
printf(
    "      `bi_pi_restricciones` WHERE is_hard=0 (la rama que sí incluye pi_shared_constraints, via JOIN\n" .
    "      con pi_shared_constraint_links y programa_consolidado, filtrando Titulo=0) — filas: %d, is_ready=1: %d (%s%%)\n",
    (int) $viewIsHard0['n'],
    (int) $viewIsHard0['listos'],
    $viewIsHard0['n'] > 0 ? round(100 * $viewIsHard0['listos'] / $viewIsHard0['n'], 1) : 0
);
printf(
    "      Para contraste, is_hard=1 (las 5 restricciones duras de programa_consolidado, poblacion\n" .
    "      DISTINTA a la de esta migracion) — filas: %d, is_ready=1: %d (%s%%)\n\n",
    (int) $viewIsHard1['n'],
    (int) $viewIsHard1['listos'],
    $viewIsHard1['n'] > 0 ? round(100 * $viewIsHard1['listos'] / $viewIsHard1['n'], 1) : 0
);

echo "   c) Por que esta rama de la vista NO es un proxy valido de 'el total reconstruido de este\n";
echo "      dry-run':\n";
printf(
    "      - Grano distinto: pi_shared_constraints tiene %d filas (una por project_id+Semana+Restriccion);\n" .
    "        la vista, en su rama is_hard=0, tiene %d filas (una por CADA aplicacion/link a una actividad).\n" .
    "        %d de %d links (%s%%) ni siquiera aparecen en la vista: el JOIN final descarta las filas de\n" .
    "        programa_consolidado con Titulo<>0 (encabezados). Sumar/contar en un grano y comparar contra\n" .
    "        el otro no reconcilia por construccion, no por un error de datos.\n",
    $totalFilas,
    (int) $viewIsHard0['n'],
    $totalLinks - (int) $viewIsHard0['n'],
    $totalLinks,
    $totalLinks > 0 ? round(100 * ($totalLinks - (int) $viewIsHard0['n']) / $totalLinks, 1) : 0
);
echo "      - Formula distinta: la vista calcula is_ready comparando ValorAplicado (logro, en el LINK)\n";
echo "        contra ValorObjetivo (meta, en el shared constraint): `is_ready = ValorAplicado >= ValorObjetivo`.\n";
echo "        La regla de compatibilidad de CT-7.3, en cambio, clasifica ValorObjetivo SOLO (sin comparar\n";
echo "        contra nada) como si fuera directamente un avance 0..1. Son dos preguntas distintas: una es\n";
echo "        '¿la meta declarada es 0, parcial o 1?' y la otra es '¿lo aplicado alcanzo la meta?'.\n";
printf(
    "      - Consistente con eso: el %s%% de is_ready=1 en la rama is_hard=0 es sospechoso de ser un artefacto\n" .
    "        de la formula (CAST('N/A' AS DECIMAL) y CAST('0' AS DECIMAL) truncan a 0 en MySQL sin modo\n" .
    "        estricto de estos tipos; 0 >= 0 siempre es verdadero), no evidencia real de que el 100%% de\n" .
    "        las restricciones compartidas esta liberado.\n\n",
    $viewIsHard0['n'] > 0 ? round(100 * $viewIsHard0['listos'] / $viewIsHard0['n'], 1) : 0
);

echo "   CONCLUSION DE RECONCILIACION: no existe, en este entorno, un numero de Power BI / BI ejecutable\n";
echo "   que este calculado en el mismo grano y con la misma formula que el total reconstruido de este\n";
echo "   dry-run ({$totalReconstruido} filas de pi_shared_constraints). Las dos metricas de restricciones\n";
echo "   que SI corren hoy excluyen por diseno esta poblacion (is_hard=1 unicamente), y la unica vista que\n";
echo "   SI la incluye mide algo distinto (readiness por link, no clasificacion de la meta por constraint).\n";
echo "   Por CT-16 / el brief de esta tarea: sin un proxy confiable que reconcilie, la migracion NO debe\n";
echo "   aplicarse en este dry-run — la decision sube a Felipe en el Paso 3, con esta salida completa.\n\n";

echo str_repeat('=', 88) . "\n";
echo "Fin del dry-run. Nada se escribio: sesion en TRANSACTION READ ONLY durante toda la ejecucion.\n";
