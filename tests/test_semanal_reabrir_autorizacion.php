<?php

declare(strict_types=1);
// @requiere: puro


/**
 * Autorización de "reabrir una semana" en Programación Semanal.
 *
 * Regla dada por el usuario el 2026-08-10, verbatim: "Reabren Admin y Director, siempre.
 * El Residente solo hasta el fin del día de inicio de la semana. Cualquier otro rol, nunca."
 *
 * Hallazgo previo: el servidor solo exigía lps.programacion_semanal.editar (permiso que
 * también tiene R), así que cualquiera con ese permiso podía reabrir llamando al endpoint
 * directamente sin importar la fecha ni el rol exacto. Este test fija la política pura,
 * extraída a App\Security\SemanalReabrirPolicy, que SemanalApiController::reabrir() debe
 * consultar antes de mutar.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\SemanalReabrirPolicy;

$fallos = 0;
$total = 0;

function verificar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "  OK   {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FAIL {$descripcion}\n";
}

$inicioSemana = '2026-08-03'; // lunes de inicio de semana de ejemplo
$dentroDePlazo = new DateTimeImmutable('2026-08-03 18:00:00');
$fueraDePlazo = new DateTimeImmutable('2026-08-04 00:00:01');

echo "1) A siempre permitido, sin importar la fecha\n";
verificar('A permitido dentro de plazo', SemanalReabrirPolicy::allows('A', $inicioSemana, $dentroDePlazo));
verificar('A permitido fuera de plazo', SemanalReabrirPolicy::allows('A', $inicioSemana, $fueraDePlazo));
verificar('A permitido aunque la fecha no se resuelva', SemanalReabrirPolicy::allows('A', null, $fueraDePlazo));

echo "\n2) D siempre permitido, sin importar la fecha\n";
verificar('D permitido dentro de plazo', SemanalReabrirPolicy::allows('D', $inicioSemana, $dentroDePlazo));
verificar('D permitido fuera de plazo', SemanalReabrirPolicy::allows('D', $inicioSemana, $fueraDePlazo));
verificar('D permitido aunque la fecha no se resuelva', SemanalReabrirPolicy::allows('D', null, $fueraDePlazo));

echo "\n3) R permitido solo hasta el fin del día de inicio de semana\n";
verificar('R permitido dentro de plazo', SemanalReabrirPolicy::allows('R', $inicioSemana, $dentroDePlazo));
verificar('R denegado fuera de plazo', !SemanalReabrirPolicy::allows('R', $inicioSemana, $fueraDePlazo));

echo "\n4) Si la fecha de inicio de semana no se puede resolver, se deniega (candado que no sabe, cierra)\n";
verificar('R denegado si la fecha es null', !SemanalReabrirPolicy::allows('R', null, $dentroDePlazo));
verificar('R denegado si la fecha es vacía', !SemanalReabrirPolicy::allows('R', '', $dentroDePlazo));
verificar('R denegado si la fecha es inválida', !SemanalReabrirPolicy::allows('R', 'no-es-una-fecha', $dentroDePlazo));

echo "\n5) Cualquier otro rol, nunca\n";
verificar('V denegado dentro de plazo', !SemanalReabrirPolicy::allows('V', $inicioSemana, $dentroDePlazo));
verificar('V denegado fuera de plazo', !SemanalReabrirPolicy::allows('V', $inicioSemana, $fueraDePlazo));
foreach (['DCV', 'OT', 'G', 'S', 'SG', 'C'] as $rol) {
    verificar("{$rol} denegado", !SemanalReabrirPolicy::allows($rol, $inicioSemana, $dentroDePlazo));
}

echo "\n6) El controlador consulta la política real antes de mutar (no solo el permiso de edición)\n";

$fuente = file_get_contents(__DIR__ . '/../src/Controllers/Api/SemanalApiController.php');
if ($fuente === false) {
    verificar('se pudo leer SemanalApiController.php', false);
} else {
    $inicio = strpos($fuente, 'public function reabrir(');
    if ($inicio === false) {
        verificar('existe el método reabrir()', false);
    } else {
        $cuerpo = substr($fuente, $inicio, 3200);
        $posMutacion = strpos($cuerpo, "SET Semanal_Confirmada = 0");
        $posPolicy = strpos($cuerpo, 'SemanalReabrirPolicy::allows(');
        verificar('reabrir() consulta SemanalReabrirPolicy::allows()', $posPolicy !== false);
        verificar(
            'la consulta a SemanalReabrirPolicy::allows() ocurre antes de mutar la semana',
            $posPolicy !== false && $posMutacion !== false && $posPolicy < $posMutacion
        );
    }
}

echo "\n{$total} comprobaciones, {$fallos} fallos\n";
exit($fallos === 0 ? 0 : 1);
