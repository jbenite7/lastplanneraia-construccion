<?php

namespace App\View\Components;

final class DesignSystemHeadComponent
{
    public static function render(bool $handsontableOnly = false): string
    {
        $markup = [
            self::renderScript('/js/modules/aia_ui/theme-bootstrap.js'),
        ];
        $assets = [
            '/css/aia-design-system.css',
            '/css/tokens.css',
        ];
        if (!$handsontableOnly) {
            $assets[] = '/css/design-system/vendor-datatables-legacy.css';
        }

        return implode("\n", array_merge(
            $markup,
            array_map([self::class, 'renderStylesheet'], $assets),
        ));
    }

    public static function renderLaboratory(): string
    {
        return implode("\n", array_map([self::class, 'renderStylesheet'], [
            '/css/tokens.css',
            '/css/design-system/lab-entrypoint.css',
        ]));
    }

    /** Vendors ya cubiertos por el core; declararlos no añade adjuntos. */
    public const CORE_VENDORS = ['bootstrap', 'jquery', 'font-awesome', 'aia-fonts'];

    /** Adjuntos por vendor, en el orden canónico del agregador. */
    public const VENDOR_ATTACHMENTS = [
        'jquery-ui' => '/css/design-system/entrypoints/attach-jquery-ui.css',
        'anychart' => '/css/design-system/entrypoints/attach-anychart.css',
        'select2' => '/css/design-system/entrypoints/attach-select2.css',
        'sweetalert2' => '/css/design-system/entrypoints/attach-sweetalert2.css',
        'handsontable' => '/css/design-system/entrypoints/attach-handsontable.css',
    ];

    private const CORE_ENTRYPOINT = '/css/design-system/entrypoints/core.css';

    /**
     * Emite el head segmentado según el manifiesto del módulo. Ante cualquier
     * problema (manifiesto ausente, JSON inválido, vendor desconocido) degrada
     * a render() completo: siempre "cargar de más", nunca "cargar de menos".
     */
    public static function renderForModule(string $moduleId): string
    {
        $vendors = self::moduleVendors($moduleId);
        if ($vendors === null) {
            return self::render();
        }
        $assets = [self::CORE_ENTRYPOINT];
        foreach (self::VENDOR_ATTACHMENTS as $vendor => $attachment) {
            if (in_array($vendor, $vendors, true)) {
                $assets[] = $attachment;
            }
        }
        $assets[] = '/css/tokens.css';

        return implode("\n", array_merge(
            [self::renderScript('/js/modules/aia_ui/theme-bootstrap.js')],
            array_map([self::class, 'renderStylesheet'], $assets),
        ));
    }

    /** @return list<string>|null null = fallback a render() */
    private static function moduleVendors(string $moduleId): ?array
    {
        /** @var array<string, list<string>|null> $cache */
        static $cache = [];
        if (array_key_exists($moduleId, $cache)) {
            return $cache[$moduleId];
        }
        if (preg_match('/^[a-z0-9-]+$/', $moduleId) !== 1) {
            error_log("design-system: moduleId inválido '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        $file = dirname(__DIR__, 3) . '/docs/design-system/manifests/' . $moduleId . '.json';
        if (!is_file($file)) {
            error_log("design-system: manifiesto ausente para '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        $manifest = json_decode((string) file_get_contents($file), true);
        $vendors = is_array($manifest) ? ($manifest['vendors'] ?? null) : null;
        if (!is_array($vendors)) {
            error_log("design-system: manifiesto ilegible para '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        foreach ($vendors as $vendor) {
            if (!is_string($vendor)
                || (!in_array($vendor, self::CORE_VENDORS, true)
                    && !isset(self::VENDOR_ATTACHMENTS[$vendor]))
            ) {
                error_log("design-system: vendor desconocido '" . var_export($vendor, true) . "' en '$moduleId', fallback al agregador");

                return $cache[$moduleId] = null;
            }
        }

        return $cache[$moduleId] = $vendors;
    }

    public static function renderScript(string $url): string
    {
        $root = dirname(__DIR__, 3);
        $relative = str_starts_with($url, '/public/')
            ? $url
            : '/public' . $url;
        $file = $root . $relative;
        $version = self::assetVersion($file, $url);
        $src = htmlspecialchars($url . '?v=' . $version, ENT_QUOTES, 'UTF-8');

        return '<script src="' . $src . '"></script>';
    }

    /**
     * Los entrypoints con @import anidados se sirven vía PHP para que cada
     * import lleve el mtime real de su archivo (los `?v=1.0.0` del fuente son
     * la versión publicada y no bustean caché). Ver DesignSystemAssetController.
     */
    private const RUNTIME_ENTRYPOINTS = [
        '/css/aia-design-system.css' => '/runtime/css/aia-design-system.css',
        '/css/design-system/lab-entrypoint.css' => '/runtime/css/design-system/lab-entrypoint.css',
        '/css/design-system/entrypoints/core.css' => '/runtime/css/design-system/entrypoints/core.css',
        '/css/design-system/entrypoints/attach-jquery-ui.css' => '/runtime/css/design-system/entrypoints/attach-jquery-ui.css',
        '/css/design-system/entrypoints/attach-anychart.css' => '/runtime/css/design-system/entrypoints/attach-anychart.css',
        '/css/design-system/entrypoints/attach-select2.css' => '/runtime/css/design-system/entrypoints/attach-select2.css',
        '/css/design-system/entrypoints/attach-sweetalert2.css' => '/runtime/css/design-system/entrypoints/attach-sweetalert2.css',
        '/css/design-system/entrypoints/attach-handsontable.css' => '/runtime/css/design-system/entrypoints/attach-handsontable.css',
    ];

    public static function renderStylesheet(string $url): string
    {
        $root = dirname(__DIR__, 3);
        $relative = str_starts_with($url, '/public/')
            ? $url
            : '/public' . $url;
        $file = $root . $relative;
        $version = self::assetVersion($file, $url);
        $servedUrl = self::RUNTIME_ENTRYPOINTS[$url] ?? $url;
        $href = htmlspecialchars($servedUrl . '?v=' . $version, ENT_QUOTES, 'UTF-8');

        return '<link rel="stylesheet" href="' . $href . '">';
    }

    private static function assetVersion(string $file, string $url): string
    {
        $version = is_file($file) ? (int) filemtime($file) : 0;
        if (!isset(self::RUNTIME_ENTRYPOINTS[$url])) {
            return (string) $version;
        }
        $root = dirname(__DIR__, 3) . '/public/css';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $dependency) {
            if ($dependency->isFile() && $dependency->getExtension() === 'css') {
                $version = max($version, $dependency->getMTime());
            }
        }
        return (string) $version;
    }
}
