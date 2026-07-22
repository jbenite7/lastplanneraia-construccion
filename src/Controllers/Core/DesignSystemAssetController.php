<?php

namespace App\Controllers\Core;

/**
 * Sirve los entrypoints CSS del design system reescribiendo la versión de cada
 * `@import` interno (`?v=1.0.0`, la versión semántica publicada) por el mtime
 * real del archivo importado. El archivo fuente en disco no cambia — la
 * versión publicada sigue siendo el contrato — pero el cuerpo servido bustea
 * la caché del navegador en cuanto cualquier CSS anidado se edita.
 *
 * No extiende BaseController a propósito: servir CSS no necesita base de
 * datos y debe funcionar aunque la DB esté caída.
 */
final class DesignSystemAssetController
{
    private const ENTRYPOINTS = [
        'main' => '/css/aia-design-system.css',
        'laboratory' => '/css/design-system/lab-entrypoint.css',
    ];

    public function main(): void
    {
        $this->serve(self::ENTRYPOINTS['main']);
    }

    public function laboratory(): void
    {
        $this->serve(self::ENTRYPOINTS['laboratory']);
    }

    private function serve(string $url): void
    {
        $root = dirname(__DIR__, 3);
        $file = $root . '/public' . $url;
        if (!is_file($file)) {
            http_response_code(404);
            exit;
        }

        $css = (string) file_get_contents($file);
        $css = (string) preg_replace_callback(
            '/@import url\((["\'])(\/css\/[^"\'?]+)\?v=[0-9][0-9.]*\1\)/',
            static function (array $match) use ($root): string {
                $imported = $root . '/public' . $match[2];
                $version = is_file($imported) ? (string) filemtime($imported) : '0';

                return '@import url(' . $match[1] . $match[2] . '?v=' . $version . $match[1] . ')';
            },
            $css,
        );

        header('Content-Type: text/css; charset=UTF-8');
        // El <link> que apunta aquí lleva ?v=<max-mtime>, así que la URL cambia
        // con cada edición de CSS y la caché larga es segura.
        header('Cache-Control: public, max-age=31536000, immutable');
        echo $css;
        exit;
    }
}
