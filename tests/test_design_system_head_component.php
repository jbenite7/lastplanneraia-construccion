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

// 5. Ningún manifiesto usado por una vista puede degradar al agregador. Un
//    vendor declarado que PHP no conozca hace `null` en moduleVendors() y cae a
//    render() dejando solo un error_log: la ruta más pesada de la app estuvo así
//    sin que ninguna suite lo viera. Se asierta por la salida, no por el
//    registro, porque lo que rompe es el head emitido.
$manifestDir = $root . '/docs/design-system/manifests';
foreach (glob($manifestDir . '/*.json') as $manifestFile) {
    $manifest = json_decode((string) file_get_contents($manifestFile), true);
    $moduleId = is_array($manifest) ? ($manifest['moduleId'] ?? null) : null;
    if (!is_string($moduleId)) {
        continue; // inventory.json y goal-provenance.json no son manifiestos de módulo
    }
    $used = false;
    foreach (glob($root . '/views/*/*.php') as $view) {
        if (str_contains((string) file_get_contents($view), "renderForModule('$moduleId')")) {
            $used = true;
            break;
        }
    }
    if (!$used) {
        continue;
    }
    $head = DesignSystemHeadComponent::renderForModule($moduleId);
    $check(
        !str_contains($head, 'aia-design-system.css'),
        "$moduleId: degrada al agregador (algún vendor del manifiesto no existe en PHP)",
    );
}

// 6. /programacion-semanal y sus tres subvistas: el head segmentado debe traer
//    TODO lo que la ruta usa de verdad. Medido en las vistas: la padre carga
//    Handsontable + select2 + jquery-ui; CNC/CIC/CNP cargan DataTables +
//    AnyChart + select2 + jquery-ui; las cuatro cargan sweetalert2 vía
//    linksComunesHead2.js. Declarar de menos aquí es perder el adaptador oscuro
//    del vendor, que es la regresión que dba703b acaba de cerrar.
$weekly = DesignSystemHeadComponent::renderForModule('programacion-semanal');
foreach ([
    'entrypoints/core.css' => 'core segmentado',
    'attach-jquery-ui.css' => 'jquery-ui',
    'attach-anychart.css' => 'anychart',
    'attach-select2.css' => 'select2',
    'attach-sweetalert2.css' => 'sweetalert2',
    'attach-handsontable.css' => 'handsontable',
    'vendor-datatables-legacy.css' => 'datatables',
    'tokens.css' => 'tokens',
] as $needle => $label) {
    $check(str_contains($weekly, $needle), "programacion-semanal: falta $label ($needle)");
}

if ($failures !== []) {
    fwrite(STDERR, "DesignSystemHeadComponent: FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "DesignSystemHeadComponent: PASS\n";
