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
        if (!in_array($url, [
            '/css/aia-design-system.css',
            '/css/design-system/lab-entrypoint.css',
        ], true)) {
            return (string) $version;
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname($file)));
        foreach ($files as $dependency) {
            if ($dependency->isFile() && $dependency->getExtension() === 'css') {
                $version = max($version, $dependency->getMTime());
            }
        }
        return (string) $version;
    }
}
