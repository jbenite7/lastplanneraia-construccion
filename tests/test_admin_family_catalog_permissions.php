<?php

$failed = 0;

function afcpPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function afcpFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function afcpAssert(bool $condition, string $message): void
{
    $condition ? afcpPass($message) : afcpFail($message);
}

echo "=== Admin family catalog permissions ===\n";

$controllerFile = dirname(__DIR__) . '/admin/src/Controllers/FamilyCatalogController.php';
$controller = file_exists($controllerFile) ? (file_get_contents($controllerFile) ?: '') : '';
afcpAssert($controller !== '', 'controlador del catálogo existe');

foreach ([
    'index',
    'saveFamily',
    'saveAlias',
    'saveContractualElement',
    'saveRuleAssignment',
    'approveCatalogItem',
    'resolvePendingDecision',
    'importCatalog',
    'exportCatalog',
] as $method) {
    $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)\s*:\s*void\s*\{(?P<body>.*?)(?:\n    public function|\n    private function|\n\})/s';
    $matched = preg_match($pattern, $controller, $matches) === 1;
    afcpAssert($matched, "{$method} está declarado");
    afcpAssert($matched && str_contains($matches['body'], '$this->requireAdminRole()'), "{$method} exige rol administrador");
}

foreach (['saveFamily', 'saveAlias', 'saveContractualElement', 'saveRuleAssignment', 'approveCatalogItem', 'resolvePendingDecision', 'importCatalog'] as $method) {
    $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)\s*:\s*void\s*\{(?P<body>.*?)(?:\n    public function|\n    private function|\n\})/s';
    preg_match($pattern, $controller, $matches);
    afcpAssert(str_contains($matches['body'] ?? '', '$this->validatePost()'), "{$method} valida CSRF y método POST");
}

echo "=== Admin family catalog permissions: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
