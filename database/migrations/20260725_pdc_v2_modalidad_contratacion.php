<?php

// 20260725_pdc_v2_modalidad_contratacion.php
// PDC v2 / Fase A3.1 — MODALIDAD DE CONTRATACIÓN: dimensión ORTOGONAL a tipo_negociacion.
//
// `tipo_negociacion` responde QUÉ se compra (a todo costo / mano de obra / suministro / consumibles).
// `modalidad_contratacion` responde CÓMO y con qué cadencia se compra, que es lo que determina si el
// paquete entra al plan de fechas (A4) y cómo se le hace seguimiento (B1/B2):
//   · contrato        — alcance cerrado, un proveedor, se licita y se firma una vez. Los 7 pasos del
//                       proceso de contratación aplican completos. Es el DEFAULT.
//   · orden_compra    — commodity de consumo repetido (concreto, acero): entregas múltiples y el
//                       proveedor puede cambiar. ENTRA al plan de fechas pero solo se programa el
//                       PRIMER hito (el que habilita el arranque de la actividad); las reposiciones
//                       son historial de ejecución, no filas del plan.
//   · consumo_directo — ferretería y consumibles que se piden a necesidad contra caja menor/almacén.
//                       NO tiene proceso de contratación ni fecha: se controla el GASTO, no el plazo.
//   · no_contratable  — nómina propia e imprevistos: valor presupuestal que no se le compra a nadie.
//                       Existe para que no contamine la cobertura ni los semáforos de seguimiento.
//
// Evidencia de que AIA ya tomaba esta distinción: el catálogo legacy `general_dias_procesos_contratacion`
// marca como «Orden de Compra» exactamente ACERO DE REFUERZO y CONCRETO, con ciclos propios (104 y 87
// días) distintos del perfil «Suministro» genérico (98). A3 no lo modelaba y A4 lo necesita.
//
// Con el DEFAULT 'contrato' los paquetes existentes quedan idénticos: cero regresión.
// Uso:  php database/migrations/20260725_pdc_v2_modalidad_contratacion.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeColumna = static function (Database $db, string $tabla, string $columna): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$tabla, $columna],
    )->fetchColumn() > 0;
};
$existeIndice = static function (Database $db, string $tabla, string $indice): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$tabla, $indice],
    )->fetchColumn() > 0;
};

$faltaColumna = !$existeColumna($db, 'general_paquetes_contratacion', 'modalidad_contratacion');
$faltaIndice = !$existeIndice($db, 'general_paquetes_contratacion', 'idx_gpc_modalidad');

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] columna modalidad_contratacion: ' . ($faltaColumna ? 'FALTA (se añadirá)' : 'ya existe') . "\n");
    fwrite(STDOUT, '          índice idx_gpc_modalidad: ' . ($faltaIndice ? 'FALTA (se añadirá)' : 'ya existe') . "\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($faltaColumna) {
    $db->query(
        "ALTER TABLE general_paquetes_contratacion
         ADD COLUMN modalidad_contratacion enum('contrato','orden_compra','consumo_directo','no_contratable')
             NOT NULL DEFAULT 'contrato' AFTER tipo_negociacion",
    );
}
if ($faltaIndice) {
    $db->query('ALTER TABLE general_paquetes_contratacion ADD KEY idx_gpc_modalidad (modalidad_contratacion, activo)');
}

$total = (int) $db->query('SELECT COUNT(*) FROM general_paquetes_contratacion')->fetchColumn();
fwrite(STDOUT, "[APLICADO] columna e índice listos; {$total} paquetes quedan en la modalidad por defecto 'contrato'.\n");
exit(0);
