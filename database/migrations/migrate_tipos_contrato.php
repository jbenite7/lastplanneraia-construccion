<?php
/**
 * Migracion: tipoContrato 1→MO,S  2→SI
 * 
 * Lee todos los prefijos de proyecto de general_proyectos_procesos
 * y ejecuta UPDATE en cada tabla {prefix}_actividades.
 * 
 * Uso: php database/migrations/migrate_tipos_contrato.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'db';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'last_planner';
$user = $_ENV['DB_USER'] ?? 'app';
$pass = $_ENV['DB_PASS'] ?? 'secret';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Conectado a {$dbname} en {$host}:{$port}\n";

    // 1. Obtener todos los prefijos de proyecto
    $stmt = $pdo->query(
        "SELECT Base_de_Datos FROM general_proyectos_procesos 
         WHERE Base_de_Datos IS NOT NULL AND Base_de_Datos != ''"
    );
    $prefixes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($prefixes)) {
        echo "No se encontraron proyectos con Base_de_Datos configurado.\n";
        exit(0);
    }

    echo "Proyectos encontrados: " . count($prefixes) . "\n";
    echo str_repeat('-', 60) . "\n";

    $totalUpdated1 = 0;
    $totalUpdated2 = 0;

    foreach ($prefixes as $prefix) {
        $tableName = "{$prefix}_actividades";

        // Verificar que la tabla existe
        $check = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables 
             WHERE table_schema = DATABASE() AND table_name = '{$tableName}'"
        );
        if ($check->fetchColumn() == 0) {
            echo "  [SKIP] {$tableName} — tabla no existe\n";
            continue;
        }

        // Verificar que la columna tipoContrato existe
        $colCheck = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() 
               AND table_name = '{$tableName}' 
               AND column_name = 'tipoContrato'"
        );
        if ($colCheck->fetchColumn() == 0) {
            echo "  [SKIP] {$tableName} — columna tipoContrato no existe\n";
            continue;
        }

        // Migrar 1 → MO,S
        $stmt1 = $pdo->prepare(
            "UPDATE `{$tableName}` SET tipoContrato = 'MO,S' WHERE tipoContrato = '1'"
        );
        $stmt1->execute();
        $count1 = $stmt1->rowCount();

        // Migrar 2 → SI
        $stmt2 = $pdo->prepare(
            "UPDATE `{$tableName}` SET tipoContrato = 'SI' WHERE tipoContrato = '2'"
        );
        $stmt2->execute();
        $count2 = $stmt2->rowCount();

        $totalUpdated1 += $count1;
        $totalUpdated2 += $count2;

        echo "  [OK] {$tableName} — 1→MO,S: {$count1}, 2→SI: {$count2}\n";
    }

    echo str_repeat('-', 60) . "\n";
    echo "RESUMEN: 1→MO,S: {$totalUpdated1} filas, 2→SI: {$totalUpdated2} filas\n";
    echo "Migracion completada.\n";

} catch (PDOException $e) {
    echo "ERROR de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
