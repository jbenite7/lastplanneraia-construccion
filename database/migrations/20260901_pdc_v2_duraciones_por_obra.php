<?php

// 20260901_pdc_v2_duraciones_por_obra.php
// Las duraciones de los pasos de contratación dejan de ser solo de la empresa.
//
// pdc_proyecto_duraciones guarda UNA FILA POR NÚMERO CORREGIDO, no por paquete: la corrección es
// parcial por naturaleza —se ajusta Fabricación y nada más— y con siete columnas espejo habría que
// distinguir «no corregido» de «corregido a NULL». Además, si el catálogo gana un paso, esta tabla
// no necesita migración.
//
// CERO FILAS PARA UNA OBRA = manda el catálogo global, exactamente como hoy. Por eso esta migración
// NO siembra nada: aplicarla no puede mover ni una fecha.
//
// Sin clave foránea a general_dias_procesos_contratacion, por la misma razón que A4.1 aceptó para
// paso_id: el catálogo es global y su ciclo de vida no lo gobierna esta tabla. La integridad la
// sostienen la validación de escritura y una lectura que solo mira las filas que la obra usa.
//
// CONEXIÓN CRUDA, NO `Database::getInstance()` — añadido al integrar a `main` el 2026-09-02.
// Esta migración se escribió sobre el commit de producción, antes de `ProjectSqlGuard`
// (feat(security): endurecer schema y usuario runtime, 2026-08-29). Ese guardia vive dentro de
// `Database::query()` y no entiende `information_schema.TABLES` calificada por schema —lanza
// `DomainException`— ni está pensado para DDL. La propia migración de ese endurecimiento,
// `20260828_project_scope_contract.php`, resuelve esto igual: se conecta con PDO crudo para todo
// lo que es introspección de esquema y DDL, sin pasar por la capa de la aplicación.
//
// Uso:  php database/migrations/20260901_pdc_v2_duraciones_por_obra.php [--apply]

declare(strict_types=1);

function pdcDuracionesObraEnv(string $name, ?string $default = null): string
{
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Falta la variable requerida {$name}.");
    }

    return (string) $value;
}

function pdcDuracionesObraConectar(): PDO
{
    $host = pdcDuracionesObraEnv('DB_HOST', 'localhost');
    $port = pdcDuracionesObraEnv('DB_PORT', '3306');
    $database = pdcDuracionesObraEnv('DB_NAME');
    $user = pdcDuracionesObraEnv('DB_USER');
    $password = pdcDuracionesObraEnv('DB_PASS');

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
}

$apply = in_array('--apply', $argv, true);
$pdo = pdcDuracionesObraConectar();

$existeTabla = static function (PDO $pdo, string $t): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    );
    $stmt->execute([$t]);

    return (int) $stmt->fetchColumn() > 0;
};

if ($existeTabla($pdo, 'pdc_proyecto_duraciones')) {
    echo "pdc_proyecto_duraciones ya existe: nada que hacer.\n";
    exit(0);
}

echo "A crear: tabla pdc_proyecto_duraciones con clave única uq_ppd_obra_ref_col.\n";
if (!$apply) {
    echo "Simulacro. Repite con --apply para aplicar.\n";
    exit(0);
}

$pdo->exec(
    'CREATE TABLE pdc_proyecto_duraciones (
       id INT NOT NULL AUTO_INCREMENT,
       project_id INT NOT NULL,
       duracion_ref INT NOT NULL,
       columna VARCHAR(64) NOT NULL,
       dias INT NOT NULL,
       actualizado_por INT NULL,
       updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       UNIQUE KEY uq_ppd_obra_ref_col (project_id, duracion_ref, columna),
       KEY ix_ppd_obra (project_id)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
);

echo "Creada.\n";
exit(0);
