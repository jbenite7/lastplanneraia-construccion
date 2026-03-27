<?php
// scripts/create_pi_shared_constraints.php

$host = 'db'; 
$db   = 'migration_workspace';
$user = 'root';
$pass = 'Jbe#1106z';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Conectado a $db...\n";

// Get all project prefixes
$stmt = $pdo->query("SELECT Base_de_Datos FROM general_proyectos_procesos WHERE Base_de_Datos IS NOT NULL AND Base_de_Datos != ''");
$prefixes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Encontrados " . count($prefixes) . " prefijos de proyectos.\n";

$createdCount = 0;

foreach ($prefixes as $prefix) {
    // Escaping prefix just in case
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
    if (empty($prefix)) continue;

    $tableConstraints = "{$prefix}_pi_shared_constraints";
    $tableLinks = "{$prefix}_pi_shared_constraint_links";

    $sqlConstraints = "
        CREATE TABLE IF NOT EXISTS `$tableConstraints` (
          `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `Semana` int NOT NULL,
          `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
          `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
          `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
          `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`Id`),
          KEY `idx_semana` (`Semana`),
          KEY `idx_restriccion` (`Restriccion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $sqlLinks = "
        CREATE TABLE IF NOT EXISTS `$tableLinks` (
          `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `SharedConstraintId` bigint unsigned NOT NULL,
          `Semana` int NOT NULL,
          `ConsecutivoEnPrograma` bigint NOT NULL,
          `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
          `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
          `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`Id`),
          KEY `idx_shared` (`SharedConstraintId`),
          KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    try {
        $pdo->exec($sqlConstraints);
        $pdo->exec($sqlLinks);
        $createdCount += 2;
        echo "OK -> $tableConstraints & $tableLinks\n";
    } catch (Exception $e) {
        echo "Error en $prefix: " . $e->getMessage() . "\n";
    }
}

echo "\nProceso completado. Se aseguraron $createdCount tablas.\n";
