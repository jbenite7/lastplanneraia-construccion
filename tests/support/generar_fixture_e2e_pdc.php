<?php
// tests/support/generar_fixture_e2e_pdc.php — regenerar el .xlsx del e2e cuando cambie el generador.
declare(strict_types=1);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdc_fixture_presupuesto.php';
$destino = __DIR__ . '/../browser/fixtures/pdc/presupuesto-mini.xlsx';
@mkdir(dirname($destino), 0775, true);
pdcFixturePresupuestoValido($destino);
echo "OK: {$destino}\n";
