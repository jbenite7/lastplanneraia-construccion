<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Verifica que la línea de salto de tests/test_cip_poblado.php no contenga la subcadena
 * "ok" (mayúscula o minúscula), y que el runner (scripts/run-php-tests.php) la clasifique
 * como salto real, no como éxito comprobado.
 *
 * SENALES_DE_COMPROBACION en el runner incluye la subcadena 'ok'; una línea de salto como
 * "SKIP (OK): ..." hacía que seSaltoSolo() devolviera false, y el runner contaba el salto
 * como un test que pasó comprobando algo — exactamente lo que ese guardarraíl existe para
 * evitar. Hallazgo bloqueante de la revisión final de la Fase 0 de higiene de datos.
 */

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "PASA: {$descripcion}\n";
    } else {
        $fallos++;
        echo "FALLA: {$descripcion}\n";
    }
}

$rutaTest = __DIR__ . '/test_cip_poblado.php';
$contenidoTest = file_get_contents($rutaTest);
comprobar('se pudo leer test_cip_poblado.php', $contenidoTest !== false);

if (preg_match('/echo\s+"SKIP[^"]*"/', (string) $contenidoTest, $lineaSkip) === 1) {
    comprobar('la línea SKIP existe', true);
    comprobar(
        'la línea SKIP no contiene la subcadena "ok" (ni mayúscula ni minúscula)',
        stripos($lineaSkip[0], 'ok') === false
    );
} else {
    comprobar('se encontró la línea echo "SKIP..." en test_cip_poblado.php', false);
}

// Reproduce, con el mismo código fuente del runner (no una copia a mano), el criterio
// que decide si un salto cuenta como salto real o como éxito comprobado.
$rutaRunner = __DIR__ . '/../scripts/run-php-tests.php';
$contenidoRunner = file_get_contents($rutaRunner);
comprobar('se pudo leer scripts/run-php-tests.php', $contenidoRunner !== false);

if (preg_match(
    '/const\s+SENALES_DE_COMPROBACION\s*=\s*(\[[^\]]*\])\s*;/',
    (string) $contenidoRunner,
    $coincidenciaSenales
) === 1) {
    $senales = eval('return ' . $coincidenciaSenales[1] . ';');
    comprobar('se extrajo SENALES_DE_COMPROBACION del runner', is_array($senales));

    $tieneSenal = static function (string $salida) use ($senales): bool {
        $enMinusculas = mb_strtolower($salida);
        foreach ($senales as $senal) {
            if (str_contains($enMinusculas, $senal)) {
                return true;
            }
        }

        return false;
    };
    $seSaltoSolo = static function (int $codigo, string $salida) use ($tieneSenal): bool {
        if ($codigo !== 0 || preg_match('/^\s*SKIP\b/mi', $salida) !== 1) {
            return false;
        }

        return !$tieneSenal($salida);
    };

    comprobar(
        'con "SKIP (OK): ..." el runner NO lo contaba como salto (lo trataba como comprobación) — regresión confirmada',
        $seSaltoSolo(0, "SKIP (OK): el proyecto 999 no tiene responsables\n") === false
    );
    comprobar(
        'con "SKIP: ..." (la línea actual, sin "OK") el runner sí lo cuenta como salto real',
        $seSaltoSolo(0, "SKIP: el proyecto 999 no tiene responsables\n") === true
    );
} else {
    comprobar('se extrajo la constante SENALES_DE_COMPROBACION del runner', false);
}

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
