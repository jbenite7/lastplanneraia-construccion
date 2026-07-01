<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();
$projectId = 75;
$prefix = 'da_aeropuerto_pc';

function seedQuery(Database $db, string $sql, array $params = []): void
{
    $db->query($sql, $params);
}

function seedColumnExists(Database $db, string $table, string $column): bool
{
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    )->fetchColumn() > 0;
}

foreach (['pc_restr_2_nombre', 'pc_restr_3_nombre', 'pc_restr_4_nombre'] as $column) {
    if (!seedColumnExists($db, 'general_proyectos_procesos', $column)) {
        seedQuery($db, "ALTER TABLE general_proyectos_procesos ADD COLUMN {$column} varchar(100) DEFAULT NULL");
    }
}

$existing = $db->query(
    'SELECT Id FROM general_proyectos_procesos WHERE Base_de_Datos = ? AND Id <> ? LIMIT 1',
    [$prefix, $projectId]
)->fetchColumn();
if ($existing) {
    throw new RuntimeException("El prefijo {$prefix} ya existe con otro proyecto ({$existing}).");
}

seedQuery(
    $db,
    "INSERT INTO general_proyectos_procesos
     (Id, Proyecto_Proceso, Base_de_Datos, Area, pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre, Activo, Acceso, pdcActivo, fechaInicioLineaBase, fechaFinLineaBase, costoDiaRetraso, urlCambios)
     VALUES (?, 'Aeropuerto Regional PC', ?, 'Pre-Construccion', 'Permisos Ambientales', 'Disenos', 'Apropiacion Presupuestal', 1, 1, 0, '2026-07-01', '2026-12-31', 8000000, NULL)
     ON DUPLICATE KEY UPDATE Proyecto_Proceso = VALUES(Proyecto_Proceso), Base_de_Datos = VALUES(Base_de_Datos), Area = VALUES(Area), pc_restr_2_nombre = VALUES(pc_restr_2_nombre), pc_restr_3_nombre = VALUES(pc_restr_3_nombre), pc_restr_4_nombre = VALUES(pc_restr_4_nombre), Activo = 1, Acceso = 1, pdcActivo = 0",
    [$projectId, $prefix]
);

$username = 'test.a';
$user = $db->query('SELECT id FROM general_usuarios WHERE usuario = ? LIMIT 1', [$username])->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    seedQuery(
        $db,
        'INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, force_password_change, activo) VALUES (?, ?, ?, ?, ?, 0, 1)',
        ['Test Admin', 'test.a@aia.local', 'Administrador', $username, password_hash('aia2026', PASSWORD_DEFAULT)]
    );
    $userId = (int) $db->lastInsertId();
} else {
    $userId = (int) $user['id'];
    seedQuery(
        $db,
        'UPDATE general_usuarios SET password = ?, activo = 1, force_password_change = 0 WHERE id = ?',
        [password_hash('aia2026', PASSWORD_DEFAULT), $userId]
    );
}

seedQuery(
    $db,
    'INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE role = VALUES(role)',
    [$projectId, $userId, 'A']
);

$jbenitez = $db->query('SELECT id FROM general_usuarios WHERE usuario = ? LIMIT 1', ['jbenitez'])->fetchColumn();
if ($jbenitez) {
    seedQuery(
        $db,
        'INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE role = VALUES(role)',
        [$projectId, (int) $jbenitez, 'A']
    );
}

$weeks = [
    [1, '2026-07-01', '2026-07-07', 1, '2026-07-04'],
    [2, '2026-07-08', '2026-07-14', 1, '2026-07-11'],
    [3, '2026-07-15', '2026-07-21', 0, null],
];
foreach ($weeks as $week) {
    seedQuery(
        $db,
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCierreCompromisos, fechaCreacionSemana, reprogramacion, diferenciaEstructuraCron)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
         ON DUPLICATE KEY UPDATE Semana = VALUES(Semana), Fecha_Inicio_Sem = VALUES(Fecha_Inicio_Sem), Fecha_Fin_Sem = VALUES(Fecha_Fin_Sem), Semanal_Confirmada = VALUES(Semanal_Confirmada), fechaCierreCompromisos = VALUES(fechaCierreCompromisos)',
        [$projectId, $week[0], $week[0], $week[1], $week[2], $week[3], $week[4], $week[1]]
    );
}

$activities = [
    [1, 'Estudios Topograficos', 'Ing. Carlos Mendez', '2026-07-01', '2026-07-14', 'En Ejecucion', '100%', '100%', '100%', '100%'],
    [2, 'Estudios Geotecnicos', 'Ing. Carlos Mendez', '2026-07-08', '2026-07-28', 'En Ejecucion', '100%', '100%', '100%', '100%'],
    [3, 'Gestion Permisos Ambientales', 'Dra. Maria Lopez', '2026-07-01', '2026-09-30', 'En Ejecucion', '100%', '0%', '100%', '100%'],
    [4, 'Diseno Arquitectonico', 'Arq. Andres Garcia', '2026-07-15', '2026-09-15', 'No Iniciado', '100%', '100%', '0%', '100%'],
    [5, 'Presupuesto Detallado', 'Ing. Sandra Morales', '2026-09-15', '2026-10-31', 'No Iniciado', '100%', '100%', '0%', '0%'],
];

foreach ($activities as $row) {
    [$id, $name, $owner, $start, $end, $state, $pc1, $pc2, $pc3, $pc4] = $row;
    seedQuery(
        $db,
        'INSERT INTO programa (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4)
         VALUES (?, ?, ?, ?, ?, 1, ?, ?, 1, 0, ?, 0, 0, 0, 0, 0, 0, 0, 0, "0", ?, "Semilla global PC", ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE Actividad = VALUES(Actividad), unique_id = VALUES(unique_id), Responsable_AIA = VALUES(Responsable_AIA)',
        [$projectId, $id, $id, "PC-{$id}", $name, $start, $end, $state, $owner, $pc1, $pc2, $pc3, $pc4]
    );

    seedQuery(
        $db,
        'INSERT INTO programa_consolidado (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, Activa, codigo_actividad, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4)
         VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, ?, ?, 1, 0, ?, 0, 0, "0%", "0%", "0%", "0%", "0", "0", "0", ?, "Semilla global PC", ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE Actividad = VALUES(Actividad), unique_id = VALUES(unique_id), Responsable_AIA = VALUES(Responsable_AIA)',
        [$projectId, $id, $id, $id, $id, "PC-{$id}", $name, $start, $end, $state, $owner, $id <= 3 ? 1 : 0, "PC-00{$id}", $pc1, $pc2, $pc3, $pc4]
    );

    seedQuery(
        $db,
        'INSERT INTO programacion_semanal (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Activa, codigo_actividad)
         VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, "AIA", ?, ?)
         ON DUPLICATE KEY UPDATE Actividad = VALUES(Actividad), unique_id = VALUES(unique_id), Responsable_AIA = VALUES(Responsable_AIA)',
        [$projectId, $id, $id, $id, $id, "PC-{$id}", $name, $start, $end, $id === 2 ? 'Geotecnica del Norte SAS' : null, $owner, $id <= 3 ? '1' : '0', "PC-00{$id}"]
    );
}

$professionals = [
    [1, 'Ing. Carlos Mendez', 'cmendez@aia.com.co', 'Director de Obra'],
    [2, 'Dra. Maria Lopez', 'mlopez@aia.com.co', 'Residente Ambiental'],
    [3, 'Arq. Andres Garcia', 'agarcia@aia.com.co', 'Profesional Diseño y Construcción Virtual'],
    [4, 'Ing. Sandra Morales', 'smorales@aia.com.co', 'Gerente de Proyecto'],
];
foreach ($professionals as $row) {
    seedQuery(
        $db,
        'INSERT INTO profesionales (project_id, id, nombre, email, cargo, activo) VALUES (?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), email = VALUES(email), cargo = VALUES(cargo), activo = 1',
        [$projectId, ...$row]
    );
}

$providers = [
    [1, 'Geotecnica del Norte SAS', 'contacto@geotecnanorte.com', 890123456, 'Estudios geotecnicos y topograficos', 'Consultor'],
    [2, 'Ambiental Total LTDA', 'info@ambientaltotal.com', 890234567, 'Estudios y gestiones ambientales', 'Consultor'],
];
foreach ($providers as $row) {
    seedQuery(
        $db,
        'INSERT INTO subcontratistas (project_id, Id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE subcontratista = VALUES(subcontratista), correo_contacto = VALUES(correo_contacto), alcance = VALUES(alcance), tipo_proveedor = VALUES(tipo_proveedor), activo = 1',
        [$projectId, ...$row]
    );
    seedQuery(
        $db,
        'INSERT INTO cic (project_id, Id, Semana, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?, ?, 1, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE subcontratista = VALUES(subcontratista), correo_contacto = VALUES(correo_contacto), alcance = VALUES(alcance), tipo_proveedor = VALUES(tipo_proveedor)',
        [$projectId, ...$row]
    );
}

echo "Pre-Construccion global seed OK for project_id {$projectId}\n";
