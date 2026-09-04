<?php

// @requiere: puro

declare(strict_types=1);
// @requiere: puro

// Este test solo carga scripts/lib/php-test-lane-manifest.php y comprueba su tabla de niveles en
// memoria: no abre base de datos, no hace HTTP y no lee datos de proyecto. Su nivel es 'puro'.
//
// La etiqueta faltaba desde que el archivo nacio, y el efecto no era que el test se saltara: el
// runner ABORTA la ejecucion entera con RC=2 en cuanto encuentra un test sin declarar, asi que un
// solo archivo sin etiqueta dejaba el CI en rojo sin correr ni una prueba. Falla cerrado a
// proposito -un test sin nivel es exactamente como nacian fuera del CI-, pero eso convierte la
// omision en un apagon de la suite completa, no en un hueco de cobertura.

require_once __DIR__ . '/../scripts/lib/php-test-lane-manifest.php';

$checks = 0;
$failures = [];
$same = static function (mixed $expected, mixed $actual, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($expected !== $actual) {
        $failures[] = $message;
    }
};

$same(true, PhpTestLaneManifest::select('db', 'puro'), 'db acumula puro');
$same(true, PhpTestLaneManifest::select('http', 'db'), 'http acumula db');
$same(false, PhpTestLaneManifest::select('db', 'admin-db'), 'db nunca acumula admin-db');
$same(true, PhpTestLaneManifest::select('admin-db', 'admin-db'), 'admin-db selecciona su propia lane');
$same(false, PhpTestLaneManifest::select('admin-db', 'puro'), 'admin-db no acumula puro');
$same([], PhpTestLaneManifest::validateDeclaredLevels([
    '/tmp/runtime.php' => 'db',
    '/tmp/admin.php' => 'admin-db',
]), 'niveles declarados válidos');
$same(['/tmp/missing.php', '/tmp/unknown.php'], PhpTestLaneManifest::validateDeclaredLevels([
    '/tmp/missing.php' => '',
    '/tmp/unknown.php' => 'inventado',
]), 'niveles ausentes/desconocidos fallan cerrado');

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "PASA: manifiesto de lanes, {$checks} comprobaciones\n";
