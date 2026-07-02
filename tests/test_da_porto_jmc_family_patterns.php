<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Support\ActivityMatcher;

$failed = 0;

function dpjPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function dpjFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function dpjAssert(bool $condition, string $message): void
{
    $condition ? dpjPass($message) : dpjFail($message);
}

function dpjMatchCode(ActivityMatcher $matcher, array $rules, string $activity, string $expected): void
{
    $match = $matcher->matchActivity(['Actividad' => $activity, '__capitulo' => 'DAPORTO TORRE 3'], $rules);
    $actual = (string) ($match['familia_codigo'] ?? '');
    dpjAssert($actual === $expected, "{$activity} -> {$expected}" . ($actual !== $expected ? " (got {$actual})" : ''));
}

function dpjFamilyActive(Database $db, string $code, string $name): void
{
    $actual = $db->query(
        'SELECT nombre FROM general_pdc_familias WHERE codigo = ? AND activa = 1 LIMIT 1',
        [$code],
    )->fetchColumn();
    dpjAssert((string) $actual === $name, "{$code} activa como {$name}");
}

echo "=== Da Porto / JMC family patterns ===\n";

try {
    $db = Database::getInstance();
    $matcher = new ActivityMatcher();
    $rules = $matcher->loadRules();

    foreach ([
        'CIMENTACIONES' => 'Cimentaciones',
        'ESTABILIZACION_SUELO' => 'Estabilización del Suelo',
        'ASEO' => 'Aseo',
        'CARPINTERIA_METALICA' => 'Carpinteria Metalica',
        'TOPOGRAFIA' => 'Topografía',
        'RED_HIDROSANITARIA' => 'Red Hidrosanitaria',
        'RED_ELECTRICA' => 'Red Eléctrica',
        'DETECCION_INCENDIO' => 'Red de Detección de Incendio',
        'RED_EXTINCION' => 'Red de Extinción de Incendios',
        'RED_TELECOMUNICACIONES' => 'Red de Telecomunicaciones',
        'SEGURIDAD_CONTROL' => 'Seguridad y Control',
        'DOTACION_ZONAS_COMUNES' => 'Dotación Zonas Comunes',
        'MESONES' => 'Mesones de Cocina y Baños',
        'PAISAJISMO' => 'Paisajismo',
    ] as $code => $name) {
        dpjFamilyActive($db, $code, $name);
    }

    foreach ([
        'CIMENTACION_LOSAS',
        'CIMENTACION_VIGAS',
        'CIMENTACION_ZAPATAS',
        'PILOTEAJE',
        'PILAS_MECANICAS',
        'PILAS_EXCAVADAS',
        'ESPEJOS',
        'BARANDAS_BALCON',
        'VENTANERIA',
        'PASAMANOS_CERRAJERIA',
        'MESONES_COCINA',
        'MESONES_BANO',
        'AMENIDADES_CUBIERTA',
        'BOMBA_CONCRETO',
        'BOTADA_ESCOMBROS',
        'CAMPAMENTO',
        'EXCAVADORA',
        'MALACATE',
        'MONTACARGAS',
        'MOTORGRUA',
        'PLANTA_CONCRETO',
        'TORREGRUA',
        'VOLQUETA',
    ] as $legacyCode) {
        $active = (int) $db->query(
            'SELECT COALESCE(activa, 1) FROM general_pdc_familias WHERE codigo = ? LIMIT 1',
            [$legacyCode],
        )->fetchColumn();
        dpjAssert($active === 0, "{$legacyCode} queda como alias inactivo, no familia activa");
    }

    dpjMatchCode($matcher, $rules, 'LOSA DE CIMENTACION TORRE 3', 'CIMENTACIONES');
    dpjMatchCode($matcher, $rules, 'MICROPILOTES INSERTOS', 'ESTABILIZACION_SUELO');
    dpjMatchCode($matcher, $rules, 'PILOTAJE - INCLUSIONES', 'ESTABILIZACION_SUELO');
    dpjMatchCode($matcher, $rules, 'ASEO FINAL DE OBRA', 'ASEO');
    dpjMatchCode($matcher, $rules, 'ESPEJOS BANO APARTAMENTOS', 'CARPINTERIA_METALICA');
    dpjMatchCode($matcher, $rules, 'BARANDAS DE BALCON EN VIDRIO', 'CARPINTERIA_METALICA');
    dpjMatchCode($matcher, $rules, 'VENTANERIA PVC Y ALUMINIO', 'CARPINTERIA_METALICA');
    dpjMatchCode($matcher, $rules, 'PASAMANOS TUBULAR ESCALERAS', 'CARPINTERIA_METALICA');
    dpjMatchCode($matcher, $rules, 'LOCALIZACION Y REPLANTEO', 'TOPOGRAFIA');
    dpjMatchCode($matcher, $rules, 'RED HIDROSANITARIA TORRE 3', 'RED_HIDROSANITARIA');
    dpjMatchCode($matcher, $rules, 'RED ELECTRICA TORRE 3', 'RED_ELECTRICA');
    dpjMatchCode($matcher, $rules, 'DETECCION DE INCENDIO', 'DETECCION_INCENDIO');
    dpjMatchCode($matcher, $rules, 'RED DE EXTINCION DE INCENDIOS', 'RED_EXTINCION');
    dpjMatchCode($matcher, $rules, 'RED DE TELECOMUNICACIONES', 'RED_TELECOMUNICACIONES');
    dpjMatchCode($matcher, $rules, 'CABLEADO ESTRUCTURADO Y RACK COMUNICACIONES', 'RED_TELECOMUNICACIONES');
    dpjMatchCode($matcher, $rules, 'CAMARAS CCTV Y CONTROL DE ACCESO', 'SEGURIDAD_CONTROL');
    dpjMatchCode($matcher, $rules, 'JACUZZI Y BBQ EN CUBIERTA', 'DOTACION_ZONAS_COMUNES');
    dpjMatchCode($matcher, $rules, 'MESONES DE COCINA Y BANOS', 'MESONES');
    dpjMatchCode($matcher, $rules, 'PAISAJISMO ZONAS VERDES', 'PAISAJISMO');

    foreach (['CIMENTACIONES', 'ESTABILIZACION_SUELO', 'TOPOGRAFIA', 'MESONES', 'RED_EXTINCION', 'SEGURIDAD_CONTROL', 'DOTACION_ZONAS_COMUNES'] as $code) {
        $count = (int) $db->query(
            'SELECT COUNT(*)
             FROM general_pdc_familias f
             JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
             JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
             WHERE f.codigo = ?',
            [$code],
        )->fetchColumn();
        dpjAssert($count > 0, "{$code} tiene paquetes contractuales para Contratos");
    }

    foreach ([
        'Bomba de Concreto',
        'Excavadora',
        'Malacate',
        'Montacargas',
        'Motorgrua',
        'Planta de Concreto',
        'Torregrua',
        'Volqueta',
        'Botada de Escombros',
        'Campamento de Obra',
        'Amenidades Especiales de Cubierta',
    ] as $contractualName) {
        $count = (int) $db->query(
            'SELECT COUNT(*) FROM general_pdc_contractual_elements WHERE nombre = ? AND activa = 1',
            [$contractualName],
        )->fetchColumn();
        dpjAssert($count > 0, "{$contractualName} queda disponible en Contratos");
    }
} catch (Throwable $e) {
    dpjFail($e->getMessage());
}

echo "=== Da Porto / JMC family patterns: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
