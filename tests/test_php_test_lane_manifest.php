<?php

declare(strict_types=1);

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

echo "Lane manifest: {$checks} checks\n";
