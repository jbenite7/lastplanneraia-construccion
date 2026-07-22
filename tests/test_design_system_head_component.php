<?php
// tests/test_design_system_head_component.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\DesignSystemHeadComponent;

$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    if (!$condition) {
        $failures[] = $label;
    }
};

// 1. Módulo con manifiesto sin vendors de adjunto (project-selector): core + tokens, sin attach-*.
$selector = DesignSystemHeadComponent::renderForModule('project-selector');
$check(str_contains($selector, 'theme-bootstrap.js'), 'selector: falta theme-bootstrap.js');
$check(str_contains($selector, '/runtime/css/design-system/entrypoints/core.css?v='), 'selector: falta core runtime');
$check(str_contains($selector, '/css/tokens.css?v='), 'selector: falta tokens');
$check(!str_contains($selector, 'attach-'), 'selector: no debe emitir adjuntos');
$check(!str_contains($selector, 'aia-design-system.css'), 'selector: no debe emitir el agregador');
$check(
    strpos($selector, 'theme-bootstrap.js') < strpos($selector, 'core.css'),
    'selector: theme-bootstrap debe preceder al CSS',
);

// 2. Módulo inexistente: fallback exacto a render().
$check(
    DesignSystemHeadComponent::renderForModule('no-such-module') === DesignSystemHeadComponent::render(),
    'fallback: módulo inexistente debe emitir render()',
);

// 3. moduleId inválido (path traversal): fallback exacto a render().
$check(
    DesignSystemHeadComponent::renderForModule('../secrets') === DesignSystemHeadComponent::render(),
    'fallback: moduleId inválido debe emitir render()',
);

// 4. Todo adjunto declarado en PHP existe en disco y tiene URL runtime.
$root = dirname(__DIR__);
foreach (DesignSystemHeadComponent::VENDOR_ATTACHMENTS as $vendor => $url) {
    $check(is_file($root . '/public' . $url), "attachment $vendor: no existe $url");
}

if ($failures !== []) {
    fwrite(STDERR, "DesignSystemHeadComponent: FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "DesignSystemHeadComponent: PASS\n";
