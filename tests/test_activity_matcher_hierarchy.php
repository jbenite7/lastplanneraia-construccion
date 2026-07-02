<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Support\ActivityMatcher;

$failed = 0;

function amhPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function amhFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function amhAssert(bool $condition, string $message): void
{
    $condition ? amhPass($message) : amhFail($message);
}

function amhRule(int $id, int $familyId, string $code, string $name, string $pattern, int $priority = 100): array
{
    return [
        'id' => $id,
        'familia_id' => $familyId,
        'patron_regex' => $pattern,
        'modalidad_sugerida' => '',
        'confianza' => 95,
        'prioridad' => $priority,
        'familia_codigo' => $code,
        'familia_nombre' => $name,
        'categoria' => 'test',
        'siempre_revision' => 0,
    ];
}

function amhMatchCode(ActivityMatcher $matcher, array $rules, array $activity): string
{
    $match = $matcher->matchActivity($activity, $rules);
    return (string) ($match['familia_codigo'] ?? '');
}

echo "=== Activity matcher hierarchy ===\n";

try {
    $matcher = new ActivityMatcher();
    $rules = [
        amhRule(1, 10, 'PISOS_ENCHAPES', 'Pisos y Enchapes', '/\b(PISO|PISOS|ENCHAPE|ENCHAPES)\b/u', 100),
        amhRule(2, 20, 'CARPINTERIA_MADERA', 'Carpinteria en Madera', '/\b(CARPINTERIA EN MADERA|CARPINTERIAS|FORMICA)\b/u', 100),
        amhRule(3, 30, 'ESTRUCTURA_CONCRETO', 'Estructura en Concreto', '/\b(EJE|EJES|CONCRETO)\b/u', 100),
        amhRule(4, 40, 'RED_EXTINCION', 'Red de Extinción', '/\b(EXTINCION|RED CONTRA INCENDIO|ROCIADOR|GABINETE)\b/u', 100),
        amhRule(5, 50, 'PRELIMINARES', 'Preliminares de Obra', '/\b(PRELIMINARES|PROVISIONALES)\b/u', 100),
    ];

    amhAssert(
        amhMatchCode($matcher, $rules, [
            'Actividad' => 'PISO 12',
            '__capitulo' => 'CARPINTERIA EN MADERA, ACABADOS, DAPORTO TORRE 3',
        ]) === 'CARPINTERIA_MADERA',
        'piso numerico no se toma como familia si la ruta trae carpinteria'
    );

    amhAssert(
        amhMatchCode($matcher, $rules, [
            'Actividad' => 'Ejes 45 y 47',
            '__capitulo' => 'Revestimiento fórmica columnas, Carpinterías, Zona Banda 5 - Zona Verde',
        ]) === 'CARPINTERIA_MADERA',
        'ejes no se toman como estructura si la ruta profunda trae carpinteria'
    );

    amhAssert(
        amhMatchCode($matcher, $rules, [
            'Actividad' => 'STAFF',
            '__capitulo' => 'Extinción, Redes, Sala de embarque remota nacional',
        ]) === 'RED_EXTINCION',
        'staff no se toma como alcance si el capitulo indica extincion'
    );

    amhAssert(
        amhMatchCode($matcher, $rules, [
            'Actividad' => 'Retiro',
            '__capitulo' => 'Provisionales para protección y adecuación espacios antes de trabajos, Preliminares',
        ]) === 'PRELIMINARES',
        'retiro solo no se toma como familia; se usa la ruta del cronograma'
    );

    $service = new SemiAutoService(Database::getInstance());
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('leafProgramActivitiesWithBreadcrumb');
    $method->setAccessible(true);
    $leaves = $method->invoke($service, [
        ['Id' => '1', 'Actividad' => 'ACABADOS', 'Titulo' => 1, 'unique_id' => 1],
        ['Id' => '1.4', 'Actividad' => 'CARPINTERIA EN MADERA', 'Titulo' => 1, 'unique_id' => 2],
        ['Id' => '1.4.7', 'Actividad' => 'PISO 12', 'Titulo' => 0, 'unique_id' => 3],
    ]);
    amhAssert(($leaves[0]['__capitulo'] ?? '') === 'CARPINTERIA EN MADERA, ACABADOS', 'preview reconstruye ruta profunda desde Id del cronograma');
} catch (Throwable $e) {
    amhFail($e->getMessage());
}

echo "=== Activity matcher hierarchy: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
