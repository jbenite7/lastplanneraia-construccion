<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use TableResolver;

/**
 * Estado de una semana del Last Planner: si esta confirmada, sus fechas de cierre y creacion, y la
 * version del cronograma acumulada hasta ella.
 *
 * Existe porque la consulta que resuelve eso estaba COPIADA en dos sitios —`src/Legacy/
 * datosGeneralesPagina.php`, que la sirve por AJAX, y `ProgramaGeneralController`, que la pinta en
 * servidor— con las mismas columnas, los mismos cuatro marcadores y el mismo orden. Y no es una
 * duplicacion inofensiva: el 2026-09-02 las dos reventaban con «Alias de tabla de proyecto ambiguo»
 * y hubo que arreglar el mismo fallo dos veces en el mismo turno. Mientras siga copiada, el
 * siguiente que la toque arregla una sola y cree que termino.
 *
 * `BaseController::getWeekStatusVars()` lee `Semanal_Confirmada` de aqui por la misma razon, que
 * ademas es la que su propio comentario declaraba (C-46): el valor que el PHP pinta en la cabecera y
 * el que llega despues por AJAX tienen que ser el MISMO dato, y la unica forma de garantizarlo es
 * que salgan de la misma consulta, no de dos copias que alguien mantenga a mano.
 */
final class EstadoSemanalService
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Detalles de una semana, tal cual salen de la base, o null si esa semana no existe.
     *
     * Devuelve la fila cruda a proposito, con los nombres de columna originales: los tres llamadores
     * ya los usan asi, y remodelar el contrato aqui convertiria un movimiento de codigo en un cambio
     * de comportamiento que habria que verificar aparte.
     *
     * Sobre la forma del SQL: cada referencia a la tabla lleva su alias y su `project_id`
     * calificado. Nombrarla dos veces sin alias es lo que rompio las dos pantallas — con dos raices
     * homonimas, ProjectSqlGuard no puede decidir a cual pertenece cada `project_id = ?` y falla
     * cerrado. El orden de los marcadores importa: el guard comprueba por posicion que cada uno
     * reciba el project_id del alcance activo.
     *
     * @return array<string, mixed>|null
     */
    public function detallesDeLaSemana(string $dbName, int $projectId, int $semana): ?array
    {
        $tSa = TableResolver::resolveByPrefix($dbName, 'semanas_activas');

        $sql = "SELECT s.Semanal_Confirmada, s.fechaCierreCompromisos, s.fechaCreacionSemana,
                       (SELECT SUM(r.reprogramacion) FROM {$tSa} r
                         WHERE r.Semana <= ? AND r.project_id = ?) AS versionCronograma
                  FROM {$tSa} s
                 WHERE s.Semana = ? AND s.project_id = ?";

        $fila = $this->db
            ->queryWithProject($sql, [$semana, $projectId, $semana, $projectId])
            ->fetch();

        return $fila === false ? null : $fila;
    }
}
