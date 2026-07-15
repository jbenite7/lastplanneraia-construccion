<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Api\ContratosApiController;

$controller = new ContratosApiController();
$method = new ReflectionMethod($controller, 'normalizePackageQuantity');
$method->setAccessible(true);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, "PASS: {$message}\n");
        return;
    }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$assert($method->invoke($controller, '1', 'Paquete real') === 1, 'Acepta el entero mínimo 1.');
$assert($method->invoke($controller, '125', 'Paquete real') === 125, 'No aplica un máximo arbitrario.');
$assert($method->invoke($controller, '1', '') === 1, 'Un slot sin paquete conserva su valor neutro.');

foreach (['', '0', '-1', '1.5'] as $invalid) {
    $thrown = false;
    try {
        $method->invoke($controller, $invalid, 'Paquete real');
    } catch (ReflectionException $exception) {
        throw $exception;
    } catch (Throwable $exception) {
        $thrown = $exception instanceof InvalidArgumentException;
    }
    $assert($thrown, "Rechaza cantidad inválida '{$invalid}'.");
}

exit($failures === [] ? 0 : 1);
