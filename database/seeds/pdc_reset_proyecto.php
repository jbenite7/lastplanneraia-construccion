<?php

/**
 * Reset del Plan de Compras (PDC v2) para UN proyecto, desde consola.
 *
 * Por qué existe: para probar el flujo completo del módulo desde la carga del presupuesto hace
 * falta un proyecto sin datos operativos previos. `pdc_e2e_sandbox_project.php` hace algo parecido,
 * pero está clavado al sandbox 990100 y además borra el cronograma LPS (`programa`, `programa_
 * consolidado`, `semanas_activas`) porque lo resiembra a continuación. Contra un proyecto real eso
 * sería destruir datos ajenos al PDC, así que aquí el cronograma NO se toca.
 *
 * La lógica vive en `App\Services\Pdc\PdcResetService`, compartida con la pantalla del panel admin
 * (`/admin/pdc/limpieza`), para que consola y navegador no puedan divergir. Los catálogos globales
 * (`general_maestro_insumos`, `general_paquetes_contratacion`, ...) nunca se tocan.
 *
 *   php database/seeds/pdc_reset_proyecto.php --project=73                        # dry-run, todo
 *   php database/seeds/pdc_reset_proyecto.php --project=73 --etapas=plan --apply   # solo el plan
 *   php database/seeds/pdc_reset_proyecto.php --project=73 --apply                 # todo
 *
 * Sin `--apply` no escribe nada: sólo cuenta e informa. `--sin-respaldo` omite el `.sql` previo.
 */

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PdcResetService;

$argumentos = array_slice($argv, 1);
$aplicar = in_array('--apply', $argumentos, true);
$sinRespaldo = in_array('--sin-respaldo', $argumentos, true);
$projectId = null;
$etapas = array_keys(PdcResetService::ETAPAS);

foreach ($argumentos as $argumento) {
    if (preg_match('/^--project=(\d+)$/', $argumento, $m)) {
        $projectId = (int) $m[1];
    }
    if (preg_match('/^--etapas=(.+)$/', $argumento, $m)) {
        $etapas = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
    }
}

if ($projectId === null || $projectId <= 0) {
    fwrite(STDERR, "Uso: php database/seeds/pdc_reset_proyecto.php --project=<id> [--etapas=a,b] [--apply] [--sin-respaldo]\n");
    fwrite(STDERR, 'Etapas disponibles: ' . implode(', ', array_keys(PdcResetService::ETAPAS)) . "\n");
    exit(1);
}

$db = Database::getInstance();
$servicio = new PdcResetService($db);

$proyecto = $db->query(
    'SELECT Id, Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE Id = ?',
    [$projectId],
)->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    fwrite(STDERR, "El proyecto {$projectId} no existe en general_proyectos_procesos.\n");
    exit(1);
}

try {
    $seleccion = $servicio->expandir($etapas);
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    fwrite(STDERR, 'Etapas disponibles: ' . implode(', ', array_keys(PdcResetService::ETAPAS)) . "\n");
    exit(1);
}

$tablasSeleccionadas = $servicio->tablasDe($seleccion);

$imprimirConteos = static function (array $conteos) use ($tablasSeleccionadas): void {
    foreach ($conteos['etapas'] as $clave => $etapa) {
        printf("  [%s] %-22s %6d\n", $clave, $etapa['label'], $etapa['total']);
        foreach ($etapa['tablas'] as $tabla => $filas) {
            printf("        %-34s %6d%s\n", $tabla, $filas, in_array($tabla, $tablasSeleccionadas, true) ? '  <- se borra' : '');
        }
    }
    echo "  En cascada desde pdc_presupuesto_versiones:\n";
    foreach ($conteos['cascada'] as $tabla => $filas) {
        printf("        %-34s %6d\n", $tabla, $filas);
    }
    echo "  Catálogos globales (intactos):\n";
    foreach ($conteos['catalogos'] as $tabla => $filas) {
        printf("        %-34s %6d\n", $tabla, $filas);
    }
};

printf(
    "Proyecto %d — %s (prefijo %s)\nEtapas: %s\n%s\n\n",
    $proyecto['Id'],
    $proyecto['Proyecto_Proceso'],
    $proyecto['Base_de_Datos'],
    implode(', ', $seleccion),
    $aplicar ? 'MODO APPLY: se borrarán los datos seleccionados.' : 'DRY-RUN: no se escribe nada.',
);

$antes = $servicio->contar($projectId);
$imprimirConteos($antes);

if (!$aplicar) {
    echo "\nDry-run terminado. Añade --apply para ejecutar el borrado.\n";
    exit(0);
}

if (!$sinRespaldo) {
    try {
        $ruta = $servicio->respaldar($projectId, $seleccion);
        printf("\nRespaldo escrito en %s (%d bytes).\n", $ruta, filesize($ruta));
    } catch (Throwable $e) {
        fwrite(STDERR, "\nNo se generó el respaldo, se aborta sin borrar: {$e->getMessage()}\n");
        exit(1);
    }
}

try {
    $resultado = $servicio->limpiar($projectId, $seleccion);
} catch (Throwable $e) {
    fwrite(STDERR, "\nBorrado abortado y revertido: {$e->getMessage()}\n");
    exit(1);
}

echo "\n--- Verificación posterior ---\n";
$imprimirConteos($resultado['conteos']);

$restantes = 0;
foreach ($tablasSeleccionadas as $tabla) {
    foreach ($resultado['conteos']['etapas'] as $etapa) {
        $restantes += $etapa['tablas'][$tabla] ?? 0;
    }
}

$catalogosIntactos = $antes['catalogos'] === $resultado['conteos']['catalogos'];

if ($restantes > 0 || !$catalogosIntactos) {
    fwrite(STDERR, "\nATENCIÓN: la verificación no cuadra. Revisa antes de continuar.\n");
    exit(1);
}

printf(
    "\nPDC del proyecto %d limpio en las etapas: %s. Catálogos globales intactos.\n",
    $projectId,
    implode(', ', $seleccion),
);
