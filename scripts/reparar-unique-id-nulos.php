<?php
/**
 * REPARACION — backfill de `unique_id` / `Consecutivo_en_Programa` NULL en `programa_consolidado`
 * y `programacion_semanal`, emparejando contra `programa` del mismo proyecto.
 *
 * Reglas, en orden (las mismas que mide scripts/diagnostico-unique-id-nulos.php):
 *   A) Id + Actividad identicos y candidato unico
 *   B) Id identico y candidato unico
 *   C) sin candidato o ambiguo -> NO SE TOCA (queda NULL, se reporta)
 *
 * Dry-run por defecto; --apply para escribir. Autorizado por el usuario el 2026-08-12 para la base
 * LOCAL espejo de produccion (fase 2 del plan espejo produccion->local->pruebas). PRODUCCION NUNCA.
 *
 * Uso:
 *   php scripts/reparar-unique-id-nulos.php              # dry-run
 *   php scripts/reparar-unique-id-nulos.php --apply
 *   php scripts/reparar-unique-id-nulos.php --solo-a     # limita a la regla A
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

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'] ?? 'localhost', $_ENV['DB_PORT'] ?? '3306', $_ENV['DB_NAME'] ?? ''),
    $_ENV['DB_USER'] ?? '',
    $_ENV['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
);

$apply = in_array('--apply', $argv ?? [], true);
$soloA = in_array('--solo-a', $argv ?? [], true);

echo ($apply ? 'APPLY' : 'DRY  ') . " — base `{$_ENV['DB_NAME']}` en {$_ENV['DB_HOST']}\n\n";

/**
 * @return array{0: array<string,int>, 1: array<string,int>} mapas [claveDoble=>uid] y [Id=>uid], solo candidatos unicos
 */
function mapasDelProyecto(PDO $pdo, int $projectId): array
{
    $porDoble = [];
    $porId = [];
    $stmt = $pdo->prepare('SELECT unique_id, Id, Actividad FROM programa WHERE project_id = ? AND unique_id IS NOT NULL');
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll() as $row) {
        $porDoble[(string) $row['Id'] . '||' . (string) $row['Actividad']][] = (int) $row['unique_id'];
        $porId[(string) $row['Id']][] = (int) $row['unique_id'];
    }
    $unico = static fn (array $m): array => array_map(
        static fn ($v) => $v[0],
        array_filter($m, static fn ($v) => count(array_unique($v)) === 1),
    );
    return [$unico($porDoble), $unico($porId)];
}

$totales = ['a' => 0, 'b' => 0, 'c' => 0];

// ------------------------------------------------------------ programa_consolidado
$proyectos = $pdo->query('SELECT DISTINCT project_id FROM programa_consolidado WHERE unique_id IS NULL ORDER BY project_id')->fetchAll(PDO::FETCH_COLUMN);
$upd = $pdo->prepare('UPDATE programa_consolidado SET unique_id = ?, Consecutivo_en_Programa = ? WHERE project_id = ? AND row_id = ?');

foreach ($proyectos as $projectId) {
    $projectId = (int) $projectId;
    [$porDoble, $porId] = mapasDelProyecto($pdo, $projectId);

    $stmt = $pdo->prepare('SELECT row_id, Id, Actividad FROM programa_consolidado WHERE project_id = ? AND unique_id IS NULL');
    $stmt->execute([$projectId]);
    $conteo = ['a' => 0, 'b' => 0, 'c' => 0];

    foreach ($stmt->fetchAll() as $fila) {
        $doble = (string) $fila['Id'] . '||' . (string) $fila['Actividad'];
        $target = $porDoble[$doble] ?? null;
        $regla = 'a';
        if ($target === null && !$soloA) {
            $target = $porId[(string) $fila['Id']] ?? null;
            $regla = 'b';
        }
        if ($target === null) {
            $conteo['c']++;
            continue;
        }
        $conteo[$regla]++;
        if ($apply) {
            $upd->execute([$target, $target, $projectId, (int) $fila['row_id']]);
        }
    }

    printf("consolidado proy %-5d A=%-6d B=%-6d C(sin tocar)=%-6d\n", $projectId, $conteo['a'], $conteo['b'], $conteo['c']);
    foreach ($conteo as $k => $v) {
        $totales[$k] += $v;
    }
}

// ------------------------------------------------------------ programacion_semanal
echo "\n";
$totalesPs = ['a' => 0, 'b' => 0, 'c' => 0];
$proyectosPs = $pdo->query('SELECT DISTINCT project_id FROM programacion_semanal WHERE unique_id IS NULL ORDER BY project_id')->fetchAll(PDO::FETCH_COLUMN);
$updPs = $pdo->prepare('UPDATE programacion_semanal SET unique_id = ?, Consecutivo_En_Programa = ? WHERE project_id = ? AND Consecutivo = ?');

foreach ($proyectosPs as $projectId) {
    $projectId = (int) $projectId;
    [$porDoble, $porId] = mapasDelProyecto($pdo, $projectId);

    $stmt = $pdo->prepare('SELECT Consecutivo, Id, Actividad FROM programacion_semanal WHERE project_id = ? AND unique_id IS NULL');
    $stmt->execute([$projectId]);
    $conteo = ['a' => 0, 'b' => 0, 'c' => 0];

    foreach ($stmt->fetchAll() as $fila) {
        $doble = (string) $fila['Id'] . '||' . (string) $fila['Actividad'];
        $target = $porDoble[$doble] ?? null;
        $regla = 'a';
        if ($target === null && !$soloA) {
            $target = $porId[(string) $fila['Id']] ?? null;
            $regla = 'b';
        }
        if ($target === null) {
            $conteo['c']++;
            continue;
        }
        $conteo[$regla]++;
        if ($apply) {
            $updPs->execute([$target, $target, $projectId, (int) $fila['Consecutivo']]);
        }
    }

    printf("semanal     proy %-5d A=%-6d B=%-6d C(sin tocar)=%-6d\n", $projectId, $conteo['a'], $conteo['b'], $conteo['c']);
    foreach ($conteo as $k => $v) {
        $totalesPs[$k] += $v;
    }
}

echo "\n" . ($apply ? 'APPLY' : 'DRY  ') . " totales:\n";
printf("  consolidado: A=%d B=%d C=%d\n", $totales['a'], $totales['b'], $totales['c']);
printf("  semanal:     A=%d B=%d C=%d\n", $totalesPs['a'], $totalesPs['b'], $totalesPs['c']);
