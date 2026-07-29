<?php

/**
 * Constantes que definen los entrypoints y que PHPStan nunca llega a ver.
 *
 * `PROJECT_ROOT` se define en `public/index.php` y `ADMIN_PROJECT_ROOT` en
 * `admin/public/index.php`, pero el análisis solo recorre `src/` y `admin/src/`, así que sin
 * este bootstrap PHPStan reporta 91 falsos `constant.notFound` — casi la mitad del ruido del
 * nivel 5. No es deuda de código: es un agujero de configuración.
 *
 * Los valores no importan (PHPStan solo necesita que la constante exista y sea `string`); lo que
 * importa es que el tipo coincida con el de los entrypoints reales.
 */

declare(strict_types=1);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

if (!defined('ADMIN_PROJECT_ROOT')) {
    define('ADMIN_PROJECT_ROOT', dirname(__DIR__, 2) . '/admin');
}
