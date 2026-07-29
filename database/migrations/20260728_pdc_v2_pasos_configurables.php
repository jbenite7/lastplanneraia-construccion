<?php

// 20260728_pdc_v2_pasos_configurables.php
// PDC v2 / Fase A4.1 — los pasos del proceso de contratación dejan de estar escritos en el código.
//
// (A) general_pasos_contratacion — catálogo GLOBAL de pasos posibles. La `clave` es la identidad
//     estable del paso: es lo que viaja a pdc_plan_paso.paso_id y lo que permitirá comparar obras.
//     `col_legacy` dice de qué columna de general_dias_procesos_contratacion salen sus días POR
//     PAQUETE; NULL = paso sin respaldo legacy, que usa los días fijos de la obra.
// (B) pdc_proyecto_pasos — qué pasos usa una obra, en qué orden, con qué alias y días fijos.
//     CERO FILAS PARA UN PROYECTO = usa los siete de siempre. Por eso esta migración NO siembra
//     ninguna fila aquí: Da Porto (73) tiene que seguir dando exactamente las mismas fechas.
// (C) pdc_plan_paso.paso_id + cambio de clave única.
//     Hoy la única es (project_id, paquete_id, orden) y calcular() hace upsert por ella. Con pasos
//     reordenables eso corrompe datos en silencio: meter un paso en la posición 3 haría que el
//     upsert escriba encima de la fila que hoy es «Cuadros comparativos», y la fecha real que B1
//     cuelgue de esa fila pasaría a leerse como si fuera del paso nuevo. La clave pasa a ser
//     (project_id, paquete_id, paso_id): la fila sigue al paso, no a la posición.
//
// Orden deliberado: el backfill de paso_id corre ANTES del cambio de clave, y si quedara una sola
// fila sin paso_id la migración aborta sin tocar el índice.
//
// Uso:  php database/migrations/20260728_pdc_v2_pasos_configurables.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeTabla = static fn (Database $db, string $t): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    [$t],
)->fetchColumn() > 0;
$existeColumna = static fn (Database $db, string $t, string $c): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    [$t, $c],
)->fetchColumn() > 0;
$existeIndice = static fn (Database $db, string $t, string $i): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
    [$t, $i],
)->fetchColumn() > 0;
// Los nombres de FOREIGN KEY son ÚNICOS EN TODO EL ESQUEMA, no por tabla: preguntar por el índice de
// una tabla concreta no basta para saber si el nombre está libre. Costó un 1826 en la primera
// aplicación de este archivo — `fk_pps_paso` ya lo usaba pdc_proyecto_pasos, creada 40 líneas antes.
$existeConstraint = static fn (Database $db, string $c): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?',
    [$c],
)->fetchColumn() > 0;

// clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default.
// Los siete primeros son copia literal de PlanFechasService::PASOS y PESOS_REPARTO: si divergen, el
// test test_pdc_v2_pasos_configurables.php falla a propósito.
// `orden_default` va de diez en diez, no de uno en uno: es el orden CANÓNICO del proceso, y la
// pantalla lo usa para decidir dónde cae un paso al agregarlo. Con numeración compacta, un paso nuevo
// que pertenece a la mitad del proceso no tendría hueco donde entrar y aterrizaría al final, obligando
// a subirlo a mano. Los huecos son para eso, y para que un paso futuro entre sin renumerar el resto.
$semilla = [
    ['elaboracion_pliegos', 'Elaboración de pliegos', 'diasElaboracionPliegos', null, 0.087872, 0],
    // Licify: en la «Variante A» del histórico era el paso 2, justo después de elaborar los pliegos
    // (docs/pdca-automatizacion-plan-compras.md:105). Duraba 0-2 días y sus columnas se dropearon a
    // propósito en jun-2026, así que no hay de dónde leer su duración: la pone la obra.
    ['licify', 'Ingreso a plataforma Licify', null, 1, null, 5],
    ['entrega_pliegos', 'Entrega de pliegos', 'diasEntregaPliegos', null, 0.121115, 10],
    ['recibo_propuestas', 'Recibo de propuestas', 'diasReciboPropuestas', null, 0.054079, 20],
    ['cuadros_comparativos', 'Cuadros comparativos', 'diasCuadrosComparativos', null, 0.189065, 30],
    // «Variante B»: entre cuadros comparativos y legalización, que es donde la tenían los dos de los
    // seis proyectos históricos que la usaban (2021-2 y 2024).
    ['aprobacion_cliente', 'Aprobación del cliente', null, 15, null, 35],
    ['legalizacion', 'Legalización', 'diasLegalizacionContrato', null, 0.178996, 40],
    ['fabricacion', 'Fabricación', 'diasFabricacion', null, 0.248792, 50],
    ['insumos_obra', 'Insumos en obra', 'diasInsumosObra', null, 0.120081, 60],
];

/** Las siete claves por defecto, en el orden del proceso. El backfill mapea orden 0..6 a estas. */
$clavesDefault = ['elaboracion_pliegos', 'entrega_pliegos', 'recibo_propuestas',
    'cuadros_comparativos', 'legalizacion', 'fabricacion', 'insumos_obra'];

$faltaCatalogo = !$existeTabla($db, 'general_pasos_contratacion');
$faltaConfig = !$existeTabla($db, 'pdc_proyecto_pasos');
$faltaPasoId = !$existeColumna($db, 'pdc_plan_paso', 'paso_id');
$faltaClaveNueva = !$existeIndice($db, 'pdc_plan_paso', 'uq_pps_proyecto_paquete_paso');
$sobraClaveVieja = $existeIndice($db, 'pdc_plan_paso', 'uq_pps_proyecto_paquete_orden');
$anchoPaso = (int) ($db->query(
    "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'paso'",
)->fetchColumn() ?: 0);
$filasPlan = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso')->fetchColumn();

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] general_pasos_contratacion: ' . ($faltaCatalogo ? 'FALTA (se crea + 9 filas)' : 'ya existe (se re-siembran las 9 filas)') . "\n");
    fwrite(STDOUT, '          pdc_proyecto_pasos: ' . ($faltaConfig ? 'FALTA (se crea, SIN filas)' : 'ya existe') . "\n");
    fwrite(STDOUT, '          pdc_plan_paso.paso_id: ' . ($faltaPasoId ? "FALTA (se añade y se rellenan {$filasPlan} filas)" : 'ya existe') . "\n");
    fwrite(STDOUT, "          pdc_plan_paso.paso ancho {$anchoPaso}: " . ($anchoPaso < 120 ? 'se ensancha a 120' : 'ya basta') . "\n");
    fwrite(STDOUT, '          clave única: ' . ($faltaClaveNueva ? 'se crea por paso_id' : 'ya está') . ($sobraClaveVieja ? '; se quita la de orden' : '') . "\n");
    fwrite(STDOUT, "          NINGUNA fila de configuración se siembra: los proyectos existentes no cambian de fechas.\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($faltaCatalogo) {
    $db->query(
        'CREATE TABLE general_pasos_contratacion (
           id INT NOT NULL AUTO_INCREMENT,
           clave VARCHAR(60) NOT NULL,
           nombre VARCHAR(120) NOT NULL,
           col_legacy VARCHAR(60) NULL,
           dias_sugeridos INT NULL,
           peso_reparto DECIMAL(9,6) NULL,
           orden_default INT NOT NULL DEFAULT 0,
           activo TINYINT(1) NOT NULL DEFAULT 1,
           creado_por VARCHAR(100) NOT NULL DEFAULT "",
           updated_at DATETIME NOT NULL,
           PRIMARY KEY (id),
           UNIQUE KEY uq_gpc_clave (clave)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    );
}
// Upsert por clave, no INSERT a secas: así el archivo converge también en un entorno donde la tabla
// ya existiera con parte de la semilla.
foreach ($semilla as [$clave, $nombre, $col, $dias, $peso, $orden]) {
    $db->query(
        'INSERT INTO general_pasos_contratacion
            (clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default, activo, creado_por, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, "migracion-a41", NOW())
         ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), col_legacy = VALUES(col_legacy),
            dias_sugeridos = VALUES(dias_sugeridos), peso_reparto = VALUES(peso_reparto),
            orden_default = VALUES(orden_default), updated_at = NOW()',
        [$clave, $nombre, $col, $dias, $peso, $orden],
    );
}

if ($faltaConfig) {
    $db->query(
        'CREATE TABLE pdc_proyecto_pasos (
           id BIGINT NOT NULL AUTO_INCREMENT,
           project_id INT NOT NULL,
           paso_id INT NOT NULL,
           orden INT NOT NULL,
           alias VARCHAR(120) NOT NULL DEFAULT "",
           dias_fijos INT NULL,
           -- Sin columna `activo` a propósito: la lista ES la configuración. «Apagar» un paso es
           -- sacarlo de la lista, y guardar() reescribe la lista entera en una transacción. Una
           -- bandera que nadie pone nunca en 0 sería peso muerto y una segunda forma de decir lo
           -- mismo, que es como se desincronizan las cosas.
           actualizado_por VARCHAR(100) NOT NULL DEFAULT "",
           updated_at DATETIME NOT NULL,
           PRIMARY KEY (id),
           UNIQUE KEY uq_pps_proyecto_paso (project_id, paso_id),
           KEY idx_pps_proyecto_orden (project_id, orden),
           CONSTRAINT fk_pps_paso FOREIGN KEY (paso_id) REFERENCES general_pasos_contratacion (id) ON DELETE RESTRICT
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    );
}

if ($anchoPaso > 0 && $anchoPaso < 120) {
    // Un alias de obra puede llegar a 120; con los 60 de hoy se truncaría en silencio al escribir el plan.
    $db->query('ALTER TABLE pdc_plan_paso MODIFY COLUMN paso VARCHAR(120) NOT NULL');
}

if ($faltaPasoId) {
    $db->query('ALTER TABLE pdc_plan_paso ADD COLUMN paso_id INT NULL AFTER orden');
}

// Backfill por posición: las filas que existen hoy son todas del proceso de siete pasos.
foreach ($clavesDefault as $i => $clave) {
    $db->query(
        'UPDATE pdc_plan_paso p
         JOIN general_pasos_contratacion g ON g.clave = ?
         SET p.paso_id = g.id
         WHERE p.orden = ? AND p.paso_id IS NULL',
        [$clave, $i],
    );
}

$huerfanas = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE paso_id IS NULL')->fetchColumn();
if ($huerfanas > 0) {
    fwrite(STDERR, "[ABORTA] quedan {$huerfanas} filas de pdc_plan_paso sin paso_id (orden > 6 inesperado).\n");
    fwrite(STDERR, "         NO se cambió la clave única. Revisa esas filas antes de reintentar.\n");
    exit(1);
}

if ($faltaClaveNueva) {
    $db->query('ALTER TABLE pdc_plan_paso ADD UNIQUE KEY uq_pps_proyecto_paquete_paso (project_id, paquete_id, paso_id)');
}
if ($sobraClaveVieja) {
    // Se sustituye por un índice NO único: sigue sirviendo para ordenar, pero deja de impedir que
    // dos pasos distintos compartan posición durante un reordenamiento.
    if (!$existeIndice($db, 'pdc_plan_paso', 'idx_pps_proyecto_paquete_orden')) {
        $db->query('ALTER TABLE pdc_plan_paso ADD KEY idx_pps_proyecto_paquete_orden (project_id, paquete_id, orden)');
    }
    $db->query('ALTER TABLE pdc_plan_paso DROP INDEX uq_pps_proyecto_paquete_orden');
}
// `fk_plan_paso_paso`, no `fk_pps_paso`: ese nombre ya es de la FK de pdc_proyecto_pasos y los
// nombres de constraint son únicos en el esquema entero.
//
// ON DELETE RESTRICT: retirar un paso del catálogo global no puede llevarse por delante las fechas
// que ya se calcularon con él. Para sacarlo de circulación está `activo = 0`, que lo esconde de la
// pantalla sin tocar el historial.
if (!$existeConstraint($db, 'fk_plan_paso_paso')) {
    $db->query('ALTER TABLE pdc_plan_paso ADD CONSTRAINT fk_plan_paso_paso FOREIGN KEY (paso_id) REFERENCES general_pasos_contratacion (id) ON DELETE RESTRICT');
}

$pasos = (int) $db->query('SELECT COUNT(*) FROM general_pasos_contratacion')->fetchColumn();
$config = (int) $db->query('SELECT COUNT(*) FROM pdc_proyecto_pasos')->fetchColumn();
fwrite(STDOUT, "[APLICADO] catálogo con {$pasos} pasos; {$config} filas de configuración por proyecto (cero = todos usan el proceso por defecto); {$filasPlan} filas de plan con identidad de paso.\n");
exit(0);
