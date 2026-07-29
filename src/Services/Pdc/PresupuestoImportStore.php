<?php

namespace App\Services\Pdc;

/**
 * Archivos temporales del import (entre preview y confirmar).
 * El docroot es la raíz del repo: el directorio lleva su propio .htaccess
 * de denegación total. Tokens aleatorios de 32 hex; TTL con limpieza oportunista.
 */
final class PresupuestoImportStore
{
    public const TTL = 3600;

    private string $dir;

    public function __construct(?string $baseDir = null)
    {
        $this->dir = $baseDir ?? (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)) . '/storage/pdc-imports';
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        $htaccess = $this->dir . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n");
        }
    }

    /**
     * El store es genérico y cada importador guarda su propia forma —el de presupuesto añade
     * hashes y proyecto, el de maestro Sinco sólo nombre y usuario—, así que la clave es string
     * pero el valor no se puede estrechar sin mentir sobre uno de los dos.
     *
     * @param array<string, mixed> $meta
     */
    public function guardar(string $origen, array $meta): string
    {
        $this->limpiar();
        $token = bin2hex(random_bytes(16));
        if (!copy($origen, $this->dir . "/{$token}.xlsx")) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal del import.');
        }
        file_put_contents($this->dir . "/{$token}.json", json_encode($meta, JSON_UNESCAPED_UNICODE));
        return $token;
    }

    public function ruta(string $token): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $ruta = $this->dir . "/{$token}.xlsx";
        if (!is_file($ruta) || (time() - (int) filemtime($ruta)) > self::TTL) {
            return null;
        }
        return $ruta;
    }

    /**
     * @return array<string, mixed>|null lo mismo que guardó `guardar()`, o null si el token no
     *                                   existe, caducó o el JSON quedó ilegible
     */
    public function meta(string $token): ?array
    {
        if ($this->ruta($token) === null) {
            return null;
        }
        $raw = @file_get_contents($this->dir . "/{$token}.json");
        return $raw === false ? null : (json_decode($raw, true) ?: null);
    }

    public function eliminar(string $token): void
    {
        if (preg_match('/^[a-f0-9]{32}$/', $token)) {
            @unlink($this->dir . "/{$token}.xlsx");
            @unlink($this->dir . "/{$token}.json");
        }
    }

    public function limpiar(): void
    {
        foreach (glob($this->dir . '/*.xlsx') ?: [] as $f) {
            if ((time() - (int) filemtime($f)) > self::TTL) {
                @unlink($f);
                @unlink(substr($f, 0, -5) . '.json');
            }
        }
    }
}
