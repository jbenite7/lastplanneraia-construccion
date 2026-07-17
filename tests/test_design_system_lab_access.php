<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\AppEnvironment;
use App\Security\DesignSystemLabAccessPolicy;
use App\Security\RbacCatalog;
use App\Security\RbacManager;

function expectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ": expected {$expected}, got {$actual}");
    }
}

expectSame('production', AppEnvironment::normalize(null), 'default environment');
expectSame('production', AppEnvironment::normalize('invalid'), 'invalid environment');
expectSame('development', AppEnvironment::normalize('development'), 'development environment');
expectSame(404, DesignSystemLabAccessPolicy::status([], 'production'), 'production is hidden');
expectSame(403, DesignSystemLabAccessPolicy::status(['permiso' => 'R'], 'testing'), 'non-admin denied');
expectSame(200, DesignSystemLabAccessPolicy::status(['permiso' => 'A'], 'testing'), 'admin allowed');
expectSame(
    true,
    RbacManager::hasCapability('A', RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW),
    'administrator has the internal laboratory capability',
);
expectSame(
    false,
    RbacManager::hasCapability('R', RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW),
    'resident does not receive the internal laboratory capability',
);
if (!in_array(RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW, RbacCatalog::permissionKeys(), true)) {
    throw new RuntimeException('internal laboratory capability must be catalogued');
}

$frontController = file_get_contents(__DIR__ . '/../public/index.php');
if (!str_contains($frontController, "AppEnvironment::allowsInternalTools()")
    || !str_contains($frontController, "'/internal/design-system'")) {
    throw new RuntimeException('internal route must be environment gated');
}

$compose = file_get_contents(__DIR__ . '/../docker-compose.yml');
if (!str_contains($compose, 'APP_ENV: ${APP_ENV:-development}')) {
    throw new RuntimeException('local Docker must declare development environment');
}

echo "Design system lab access: PASS\n";
